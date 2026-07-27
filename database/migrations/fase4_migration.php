<?php
require_once __DIR__ . '/../../model/conection.php';
$db = Conexion::conectar();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Iniciando migración Fase 4...\n";

// 1. Crear historial_bloqueos_organismos
$sql = "
CREATE TABLE IF NOT EXISTS `historial_bloqueos_organismos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `idOrganismo` INT UNSIGNED NOT NULL,
  `accion` ENUM('bloqueo','desbloqueo') NOT NULL,
  `motivo` TEXT NOT NULL,
  `admin_id` INT UNSIGNED NOT NULL COMMENT 'ID del admin que ejecutó la acción',
  `admin_name` VARCHAR(100) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_hbo_organismo` (`idOrganismo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Historial de bloqueos/desbloqueos manuales de organismos';
";
$db->exec($sql);
echo "- Tabla historial_bloqueos_organismos creada.\n";

// 2. Crear historial_hard_reset_alumnos
$sql = "
CREATE TABLE IF NOT EXISTS `historial_hard_reset_alumnos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `idStudent` INT NOT NULL,
  `nombre_alumno` VARCHAR(100) NOT NULL,
  `matricula` VARCHAR(20) NOT NULL,
  `datos_previos` JSON NOT NULL COMMENT 'Snapshot de datos del alumno antes del reset',
  `motivo` TEXT NOT NULL,
  `admin_id` INT UNSIGNED NOT NULL,
  `admin_name` VARCHAR(100) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_hhr_student` (`idStudent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Registro de hard resets de alumnos PP (no reversible)';
";
$db->exec($sql);
echo "- Tabla historial_hard_reset_alumnos creada.\n";

// 3. Modificar organismos_externos (si no tiene las columnas)
$cols = $db->query("SHOW COLUMNS FROM organismos_externos")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('solicitudes_bloqueadas', $cols)) {
    $db->exec("ALTER TABLE organismos_externos ADD COLUMN solicitudes_bloqueadas TINYINT(1) NOT NULL DEFAULT 0");
    $db->exec("ALTER TABLE organismos_externos ADD COLUMN motivo_bloqueo TEXT DEFAULT NULL");
    $db->exec("ALTER TABLE organismos_externos ADD COLUMN fecha_bloqueo DATETIME DEFAULT NULL");
    $db->exec("ALTER TABLE organismos_externos ADD COLUMN bloqueado_por INT UNSIGNED DEFAULT NULL");
    echo "- Columnas de bloqueo agregadas a organismos_externos.\n";
} else {
    echo "- Columnas de bloqueo ya existen en organismos_externos.\n";
}

// 4. Insertar templates de correo
$templates = [
    [
        'tkey' => 'pp_organismo_bloqueado_manual',
        'name' => 'Organismo Bloqueado Manualmente',
        'subject' => 'Notificación de Bloqueo de Solicitudes - UNIMO',
        'html' => '<p>Estimado Organismo,</p><p>Le informamos que se ha bloqueado la recepción de nuevas solicitudes de practicantes por el siguiente motivo:</p><p><strong>{{motivo}}</strong></p><p>Los alumnos activos no se verán afectados. Para más información comuníquese con administración.</p>',
        'text_plain' => "Estimado Organismo,\n\nLe informamos que se ha bloqueado la recepción de nuevas solicitudes de practicantes por el siguiente motivo:\n\n{{motivo}}\n\nLos alumnos activos no se verán afectados."
    ],
    [
        'tkey' => 'pp_organismo_desbloqueado',
        'name' => 'Organismo Desbloqueado',
        'subject' => 'Notificación de Desbloqueo de Solicitudes - UNIMO',
        'html' => '<p>Estimado Organismo,</p><p>Le informamos que su capacidad de solicitar nuevos practicantes ha sido restaurada.</p>',
        'text_plain' => "Estimado Organismo,\n\nLe informamos que su capacidad de solicitar nuevos practicantes ha sido restaurada."
    ],
    [
        'tkey' => 'pp_hard_reset_alumno',
        'name' => 'Hard Reset de Alumno',
        'subject' => 'Reinicio de Proceso de Prácticas Profesionales',
        'html' => '<p>Estimado Alumno,</p><p>Le informamos que su proceso de prácticas ha sido reiniciado. Motivo:</p><p><strong>{{motivo}}</strong></p><p>Por favor, inicie su proceso nuevamente.</p>',
        'text_plain' => "Estimado Alumno,\n\nLe informamos que su proceso de prácticas ha sido reiniciado. Motivo:\n{{motivo}}\n\nPor favor, inicie su proceso nuevamente."
    ]
];

$stmt = $db->prepare("INSERT IGNORE INTO email_templates (tkey, name, subject, html, text_plain) VALUES (:tkey, :name, :subject, :html, :text_plain)");
foreach ($templates as $t) {
    $stmt->execute($t);
}
echo "- Templates de correo verificados/insertados.\n";

echo "Migración Fase 4 Finalizada.\n";
