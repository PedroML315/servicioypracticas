<?php

require_once __DIR__ . '/../../model/conection.php';

try {
    $db = Conexion::conectar();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Iniciando migración Fase 1...\n";

    // 1. Tabla dias_festivos
    $db->exec("
        CREATE TABLE IF NOT EXISTS `dias_festivos` (
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
          `fecha` DATE NOT NULL COMMENT 'Fecha del día festivo (YYYY-MM-DD)',
          `descripcion` VARCHAR(100) NOT NULL COMMENT 'Ej: Día de la Independencia',
          `is_active` TINYINT(1) NOT NULL DEFAULT 1,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uq_fecha` (`fecha`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
          COMMENT='Catálogo de días festivos oficiales que se excluyen del cálculo de horas hábiles';
    ");
    echo "Tabla dias_festivos verificada/creada.\n";

    // Datos iniciales
    $festivos = [
        ['2026-01-01', 'Año Nuevo'],
        ['2026-02-02', 'Día de la Constitución'],
        ['2026-03-16', 'Natalicio de Benito Juárez'],
        ['2026-05-01', 'Día del Trabajo'],
        ['2026-09-16', 'Día de la Independencia'],
        ['2026-11-16', 'Revolución Mexicana'],
        ['2026-12-25', 'Navidad']
    ];

    $stmtInsertFestivo = $db->prepare("INSERT IGNORE INTO `dias_festivos` (`fecha`, `descripcion`) VALUES (?, ?)");
    foreach ($festivos as $f) {
        $stmtInsertFestivo->execute($f);
    }
    echo "Datos iniciales de dias_festivos insertados.\n";

    // 2. Modificar cartas_practicas_profesionales
    $columnsToAdd = [
        "ADD COLUMN `fecha_generacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Momento exacto de generación (CDMX)'",
        "ADD COLUMN `fecha_vencimiento` DATETIME DEFAULT NULL COMMENT 'Fecha/hora límite para presentarse (calculada a 24 hrs hábiles)'",
        "ADD COLUMN `status_carta` ENUM('vigente','presentada','expirada') NOT NULL DEFAULT 'vigente' COMMENT 'Estado actual de la carta de presentación'",
        "ADD COLUMN `fecha_presentacion` DATETIME DEFAULT NULL COMMENT 'Fecha en que el organismo confirmó la presentación del alumno'",
        "ADD COLUMN `confirmada_por` INT UNSIGNED DEFAULT NULL COMMENT 'ID del organismo o admin que confirmó la presentación'"
    ];

    foreach ($columnsToAdd as $colDef) {
        $colNameMatch = [];
        preg_match('/ADD COLUMN `([^`]+)`/', $colDef, $colNameMatch);
        $colName = $colNameMatch[1];

        $checkCol = $db->query("SHOW COLUMNS FROM `cartas_practicas_profesionales` LIKE '$colName'");
        if ($checkCol->rowCount() == 0) {
            $db->exec("ALTER TABLE `cartas_practicas_profesionales` $colDef");
            echo "Columna $colName agregada a cartas_practicas_profesionales.\n";
        }
    }

    // 3. Crear índice idx_carta_vencimiento
    $checkIdx = $db->query("SHOW INDEX FROM `cartas_practicas_profesionales` WHERE Key_name = 'idx_carta_vencimiento'");
    if ($checkIdx->rowCount() == 0) {
        $db->exec("CREATE INDEX idx_carta_vencimiento ON cartas_practicas_profesionales (status_carta, fecha_vencimiento)");
        echo "Índice idx_carta_vencimiento creado.\n";
    }

    // 4. Plantillas de correo electrónico
    $templates = [
        [
            'pp_carta_presentacion_generada',
            'Plantilla Carta Presentación Generada',
            'Carta de presentación generada - PP',
            'Hola {{studentName}}, tu carta de presentación ha sido generada. Tienes hasta el {{fechaVencimiento}} para presentarte en el organismo externo. Cuentas con {{diasHabilesDisponibles}} días hábiles.',
            'Hola {{studentName}}, tu carta de presentación ha sido generada. Tienes hasta el {{fechaVencimiento}} para presentarte en el organismo externo. Cuentas con {{diasHabilesDisponibles}} días hábiles.'
        ],
        [
            'pp_carta_presentacion_expirada',
            'Plantilla Carta Presentación Expirada',
            'Carta de presentación expirada - PP',
            'Hola {{studentName}}, tu carta de presentación ha expirado debido a que no confirmaste tu presentación en el organismo dentro del plazo de 24 horas hábiles.',
            'Hola {{studentName}}, tu carta de presentación ha expirado debido a que no confirmaste tu presentación en el organismo dentro del plazo de 24 horas hábiles.'
        ],
        [
            'pp_carta_presentacion_expirada_admin',
            'Plantilla Carta Presentación Expirada Admin',
            'Carta de presentación expirada (Admin) - PP',
            'El alumno {{studentName}} no se presentó a tiempo y su carta de presentación ha expirado.',
            'El alumno {{studentName}} no se presentó a tiempo y su carta de presentación ha expirado.'
        ],
        [
            'pp_carta_presentacion_confirmada',
            'Plantilla Presentación Confirmada',
            'Presentación confirmada - PP',
            'Hola {{studentName}}, el organismo ha confirmado tu presentación. Puedes comenzar tus prácticas.',
            'Hola {{studentName}}, el organismo ha confirmado tu presentación. Puedes comenzar tus prácticas.'
        ]
    ];

    // Verificar si la tabla email_templates existe, y agregar las plantillas si es así.
    $checkTable = $db->query("SHOW TABLES LIKE 'email_templates'");
    if ($checkTable->rowCount() > 0) {
        $stmtInsertTemplate = $db->prepare("INSERT IGNORE INTO email_templates (tkey, name, subject, html, text_plain) VALUES (?, ?, ?, ?, ?)");
        foreach ($templates as $t) {
            $stmtInsertTemplate->execute($t);
        }
        echo "Plantillas de correo insertadas/verificadas.\n";
    } else {
        echo "Advertencia: La tabla email_templates no existe. Omitiendo la inserción de plantillas.\n";
    }

    echo "Migración Fase 1 completada exitosamente.\n";

} catch (Exception $e) {
    echo "Error en migración: " . $e->getMessage() . "\n";
}
