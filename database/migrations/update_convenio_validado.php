<?php
/**
 * Migración: Convenio Validado por la Institución (Montrer)
 *
 * Idempotente: puede ejecutarse múltiples veces sin errores.
 * Ejecutar desde CLI:  php database/update_convenio_validado.php
 * O desde navegador en entorno local.
 *
 * Modifica:
 *   - organismos_externos: agrega convenio_validado (nombre de archivo PDF subido
 *     por el administrador, relativo a uploads/{id}/) y convenio_validado_at.
 *
 * El convenio validado es el convenio de prácticas firmado por la institución
 * (Montrer) que el administrador sube al aceptar a un organismo receptor. Una vez
 * cargado queda disponible para consulta del administrador y del organismo.
 */

require_once __DIR__ . '/../../model/conection.php';

try {
    $pdo = Conexion::conectar();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Iniciando migración: Convenio Validado por la Institución...\n";

    $cols = $pdo->query("SHOW COLUMNS FROM organismos_externos")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('convenio_validado', $cols)) {
        $pdo->exec("ALTER TABLE organismos_externos
            ADD COLUMN `convenio_validado` VARCHAR(255) DEFAULT NULL
            COMMENT 'Nombre del PDF del convenio firmado por la institución (relativo a uploads/{id}/)'");
        echo "1a. Columna convenio_validado agregada.\n";
    } else {
        echo "1a. Columna convenio_validado ya existe (omitida).\n";
    }

    if (!in_array('convenio_validado_at', $cols)) {
        $pdo->exec("ALTER TABLE organismos_externos
            ADD COLUMN `convenio_validado_at` DATETIME DEFAULT NULL
            COMMENT 'Fecha/hora en que el administrador cargó el convenio validado'");
        echo "1b. Columna convenio_validado_at agregada.\n";
    } else {
        echo "1b. Columna convenio_validado_at ya existe (omitida).\n";
    }

    echo "\n✅ Migración 'Convenio Validado' completada con éxito.\n";

} catch (PDOException $e) {
    echo "❌ Error PDO: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
