<?php

require_once __DIR__ . '/../model/conection.php';

$pdo = Conexion::conectar();

echo "Iniciando actualización Fase 2...\n\n";

try {
    // 1. Añadir columnas a students_in_practices
    echo "1. Alterando tabla students_in_practices...\n";
    
    // Verificar si existe la columna isRejected para evitar errores
    $check = $pdo->query("SHOW COLUMNS FROM students_in_practices LIKE 'isRejected'")->fetch();
    if (!$check) {
        $pdo->exec("
            ALTER TABLE students_in_practices
            ADD COLUMN decision_motivo TEXT DEFAULT NULL COMMENT 'Motivo de aceptación o rechazo',
            ADD COLUMN decision_fecha DATETIME DEFAULT NULL COMMENT 'Fecha y hora de la decisión',
            ADD COLUMN isRejected TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = rechazado explícitamente'
        ");
        echo "   [OK] Columnas añadidas correctamente a students_in_practices.\n";
    } else {
        echo "   [INFO] Las columnas ya existen en students_in_practices.\n";
    }

    // 2. Insertar plantillas de correo si no existen
    echo "2. Insertando plantillas de correo...\n";
    
    $templates = [
        [
            'tkey' => 'pp_prospecto_aceptado_motivo',
            'name' => 'Plantilla Prospecto Aceptado con Motivo',
            'subject' => '¡Has sido aceptado para tus Prácticas Profesionales!',
            'html' => '<p>Hola <strong>{{studentName}}</strong>,</p>
<p>Nos complace informarte que has sido aceptado en <strong>{{organismoName}}</strong> para realizar tus prácticas profesionales.</p>
<p><strong>Fecha de inicio:</strong> {{fechaInicio}}</p>
<p><strong>Mensaje del organismo:</strong></p>
<blockquote style="border-left: 4px solid #4CAF50; padding-left: 10px; font-style: italic;">{{motivo}}</blockquote>
<p>Por favor, ponte en contacto con ellos lo antes posible para coordinar tu horario y primeras actividades.</p>',
            'text_plain' => 'Has sido aceptado en {{organismoName}}. Fecha de inicio: {{fechaInicio}}. Mensaje: {{motivo}}'
        ],
        [
            'tkey' => 'pp_prospecto_rechazado_motivo',
            'name' => 'Plantilla Prospecto Rechazado con Motivo',
            'subject' => 'Aviso sobre tu postulación a Prácticas Profesionales',
            'html' => '<p>Hola <strong>{{studentName}}</strong>,</p>
<p>Te informamos que tu postulación en <strong>{{organismoName}}</strong> ha sido revisada, pero lamentablemente no fue aceptada en esta ocasión.</p>
<p><strong>Motivo indicado por el organismo:</strong></p>
<blockquote style="border-left: 4px solid #f44336; padding-left: 10px; font-style: italic;">{{motivo}}</blockquote>
<p>Te invitamos a buscar otras oportunidades disponibles en el sistema.</p>',
            'text_plain' => 'Tu postulación en {{organismoName}} no fue aceptada. Motivo: {{motivo}}'
        ]
    ];

    foreach ($templates as $t) {
        $stmt = $pdo->prepare("SELECT id FROM email_templates WHERE tkey = ?");
        $stmt->execute([$t['tkey']]);
        if (!$stmt->fetch()) {
            $insert = $pdo->prepare("INSERT INTO email_templates (tkey, name, subject, html, text_plain) VALUES (?, ?, ?, ?, ?)");
            $insert->execute([$t['tkey'], $t['name'], $t['subject'], $t['html'], $t['text_plain']]);
            echo "   [OK] Plantilla insertada: {$t['tkey']}\n";
        } else {
            echo "   [INFO] La plantilla ya existe: {$t['tkey']}\n";
        }
    }

    echo "\n¡Actualización Fase 2 completada con éxito!\n";

} catch (PDOException $e) {
    echo "\n[ERROR] Ocurrió un error en la base de datos: " . $e->getMessage() . "\n";
}
