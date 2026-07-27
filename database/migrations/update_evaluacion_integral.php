<?php
/**
 * Script DDL para crear las tablas del módulo de Evaluación Integral de Prácticas
 * Ejecutar desde el navegador o CLI para instalar las tablas
 */

require_once __DIR__ . '/../../model/conection.php';

try {
    $conn = Conexion::conectar();
    
    // Iniciar transacción si es posible (DDL puede no ser transaccional en MySQL, pero es buena práctica intentar)
    $conn->beginTransaction();

    // 1. Crear tabla cabecera: evaluacion_integral_practicas
    $sqlCabecera = "CREATE TABLE IF NOT EXISTS evaluacion_integral_practicas (
        id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
        idStudent      INT NOT NULL           COMMENT 'FK students_practicas.id',
        idPractica     INT NOT NULL           COMMENT 'FK solicitudes_practicantes.id',
        idOrganismo    INT UNSIGNED NOT NULL  COMMENT 'FK organismos_externos.id',
        tipo_hito      ENUM('intermedia','final') NOT NULL COMMENT '180h o 360h',
        tipo_evaluador ENUM('empresa','alumno') NOT NULL   COMMENT 'Quién respondió',
        completada     TINYINT(1) NOT NULL DEFAULT 0,
        created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_eval (idStudent, idPractica, tipo_hito, tipo_evaluador),
        KEY idx_student_hito (idStudent, tipo_hito),
        KEY idx_practica (idPractica)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Cabecera de evaluaciones integrales de experiencia';";
    
    $conn->exec($sqlCabecera);
    echo "Tabla 'evaluacion_integral_practicas' creada o verificada.<br>";

    // 2. Crear tabla de respuestas: evaluacion_integral_respuestas
    $sqlRespuestas = "CREATE TABLE IF NOT EXISTS evaluacion_integral_respuestas (
        id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
        idEvaluacion    INT UNSIGNED NOT NULL COMMENT 'FK evaluacion_integral_practicas.id',
        pregunta_index  TINYINT UNSIGNED NOT NULL COMMENT 'Número de pregunta (1..15)',
        pregunta_texto  VARCHAR(500) NOT NULL    COMMENT 'Texto de la pregunta para trazabilidad',
        tipo_respuesta  ENUM('likert','si_no','abierta') NOT NULL,
        valor_numerico  TINYINT UNSIGNED DEFAULT NULL COMMENT '1-5 para Likert, 1/0 para Sí/No',
        valor_texto     TEXT DEFAULT NULL         COMMENT 'Para respuestas abiertas',
        created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_evaluacion (idEvaluacion),
        CONSTRAINT fk_resp_eval FOREIGN KEY (idEvaluacion) 
          REFERENCES evaluacion_integral_practicas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Respuestas individuales de cada evaluación integral';";
    
    $conn->exec($sqlRespuestas);
    echo "Tabla 'evaluacion_integral_respuestas' creada o verificada.<br>";

    $conn->commit();
    echo "<h3 style='color:green;'>¡Migración completada con éxito!</h3>";

} catch (PDOException $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    echo "<h3 style='color:red;'>Error de base de datos:</h3>";
    echo "<pre>" . $e->getMessage() . "</pre>";
} catch (Exception $e) {
    echo "<h3 style='color:red;'>Error general:</h3>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
