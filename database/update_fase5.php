<?php

require_once __DIR__ . '/../model/conection.php';

$pdo = Conexion::conectar();

echo "Iniciando actualización Fase 5...\n\n";

try {
    // 1. Crear tabla entrevistas_practicas
    echo "1. Creando tabla entrevistas_practicas...\n";
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS entrevistas_practicas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            idStudent INT NOT NULL,
            idPractica INT NOT NULL,
            llego_a_tiempo TINYINT(1) DEFAULT 0,
            llego_formal TINYINT(1) DEFAULT 0,
            calificacion_respuestas INT DEFAULT 0,
            comentarios TEXT DEFAULT NULL,
            fecha_entrevista DATETIME DEFAULT CURRENT_TIMESTAMP,
            evaluado_por INT DEFAULT NULL COMMENT 'ID del organismo o admin que evalúa',
            KEY idx_student_practica (idStudent, idPractica)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "   [OK] Tabla entrevistas_practicas creada o ya existe.\n";

    echo "\n¡Actualización Fase 5 completada con éxito!\n";

} catch (PDOException $e) {
    echo "\n[ERROR] Ocurrió un error en la base de datos: " . $e->getMessage() . "\n";
}
