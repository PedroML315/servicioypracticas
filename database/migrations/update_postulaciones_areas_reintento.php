<?php
/**
 * Migración: un alumno rechazado en un área interna puede postularse a otra.
 *
 * Idempotente: puede ejecutarse múltiples veces sin errores.
 * Ejecutar desde CLI:  php database/migrations/update_postulaciones_areas_reintento.php
 *
 * En postulaciones_areas_practicas cambia la llave única `uq_student` (student_id)
 * por `uq_student_area` (student_id, area_id): una postulación por alumno y área.
 * Que solo haya una pendiente o aceptada a la vez lo valida PracticasModel::mdlPostularseArea().
 * La nueva llave se crea antes de quitar la vieja porque la llave foránea de student_id
 * necesita un índice que empiece por esa columna.
 */

require_once __DIR__ . '/../../model/conection.php';

try {
    $pdo = Conexion::conectar();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Base de datos: " . $pdo->query("SELECT DATABASE()")->fetchColumn() . "\n";

    $tieneIndice = function (string $nombre) use ($pdo): bool {
        $stmt = $pdo->prepare("SHOW INDEX FROM postulaciones_areas_practicas WHERE Key_name = ?");
        $stmt->execute([$nombre]);
        return $stmt->rowCount() > 0;
    };

    if ($tieneIndice('uq_student_area')) {
        echo "   - La llave uq_student_area ya existe.\n";
    } else {
        $pdo->exec("ALTER TABLE postulaciones_areas_practicas ADD UNIQUE KEY uq_student_area (student_id, area_id)");
        echo "   - Llave uq_student_area (student_id, area_id) creada.\n";
    }

    if ($tieneIndice('uq_student')) {
        $pdo->exec("ALTER TABLE postulaciones_areas_practicas DROP INDEX uq_student");
        echo "   - Llave uq_student (student_id) eliminada.\n";
    } else {
        echo "   - La llave uq_student ya no existe.\n";
    }

    echo "\n✅ Migración completada con éxito.\n";

} catch (PDOException $e) {
    echo "❌ Error PDO: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
