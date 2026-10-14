<?php
// Solo desde la línea de comandos. Por URL lo podría correr cualquiera sin sesión (ver README.md).
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

require_once __DIR__ . '/../../model/conection.php';

$pdo = Conexion::conectar();

echo "Iniciando actualización: Drop de objetivos y competencias...\n\n";

try {
    /* ── 1. Drop de objetivos y competencias ── */
    $pdo->exec("ALTER TABLE `solicitudes_practicantes`
        DROP `objetivos`,
        DROP `competencias`;");
    echo "   [OK] Tabla 'Eliminacion de objetivos y competencias.\n";

    /* ── 2. Anexar actitudes ── */
    $pdo->exec("ALTER TABLE `solicitudes_practicantes` ADD `actitudes` TEXT NULL AFTER `capacidades`;");
    echo "   [OK] Tabla 'Se añadio actitudes.\n";

    echo "\nActualización completada correctamente.\n";
} catch (PDOException $e) {
    echo "\n[ERROR] " . $e->getMessage() . "\n";
}
