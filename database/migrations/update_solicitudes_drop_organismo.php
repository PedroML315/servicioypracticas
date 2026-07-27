<?php

require_once __DIR__ . '/../../model/conection.php';

$pdo = Conexion::conectar();

echo "Iniciando actualización: Drop de objetivos y competencias...\n\n";

try {
    /* ── 1. Catálogo institucional de habilidades ── */
    $pdo->exec("ALTER TABLE `solicitudes_practicantes`
        DROP `objetivos`,
        DROP `competencias`;");
    echo "   [OK] Tabla 'solicitudes_practicantes' verificada/actualizada.\n";

    echo "\nActualización completada correctamente.\n";
} catch (PDOException $e) {
    echo "\n[ERROR] " . $e->getMessage() . "\n";
}
