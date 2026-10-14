<?php
// Solo desde la línea de comandos. Por URL lo podría correr cualquiera sin sesión (ver README.md).
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

require_once __DIR__ . '/../../model/conection.php';

$pdo = Conexion::conectar();

echo "Iniciando actualización: Plan Formativo en solicitudes_practicantes...\n\n";

try {
    $columnas = [
        'funciones'            => "ADD COLUMN funciones TEXT DEFAULT NULL COMMENT 'Funciones que desempeñará el practicante'",
        'objetivos'            => "ADD COLUMN objetivos TEXT DEFAULT NULL COMMENT 'Objetivos de la práctica'",
        'competencias'         => "ADD COLUMN competencias TEXT DEFAULT NULL COMMENT 'Competencias a desarrollar'",
        'resultados_esperados' => "ADD COLUMN resultados_esperados TEXT DEFAULT NULL COMMENT 'Resultados esperados de la práctica'",
    ];

    foreach ($columnas as $col => $ddl) {
        $check = $pdo->query("SHOW COLUMNS FROM solicitudes_practicantes LIKE '$col'")->fetch();
        if (!$check) {
            $pdo->exec("ALTER TABLE solicitudes_practicantes $ddl");
            echo "   [OK] Columna '$col' añadida.\n";
        } else {
            echo "   [INFO] La columna '$col' ya existe.\n";
        }
    }

    echo "\nActualización completada correctamente.\n";
} catch (PDOException $e) {
    echo "\n[ERROR] " . $e->getMessage() . "\n";
}
