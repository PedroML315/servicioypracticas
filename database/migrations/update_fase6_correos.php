<?php

/**
 * FASE 6 · Plantillas de correo del nuevo flujo de postulación.
 * Idempotente: inserta cada plantilla solo si su tkey no existe.
 *
 * Ejecutar:  php database/update_fase6_correos.php
 */

require_once __DIR__ . '/../../model/conection.php';

$pdo = Conexion::conectar();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Iniciando actualización Fase 6 (correos)...\n\n";

$templates = [
    [
        'tkey' => 'pp_prepostulacion_empresa',
        'name' => 'Prepostulación recibida (empresa)',
        'subject' => 'Nueva prepostulación de {{studentName}}',
        'html' => '<h2 style="color:#01643D;">Nueva prepostulación recibida</h2>
<p>Hola <strong>{{contactName}}</strong>,</p>
<p>El alumno <strong>{{studentName}}</strong> (matrícula {{matricula}}) se ha prepostulado a tu vacante <strong>{{practiceTitle}}</strong>.</p>
<h3 style="color:#01643D;">Respuestas del formulario de preselección</h3>
{{{respuestas}}}
<p style="margin-top:16px;">Ingresa a la plataforma para revisar su perfil y decidir si lo aceptas para entrevista o lo rechazas.</p>
<p><a href="{{link}}" style="display:inline-block;background:#01643D;color:#fff;padding:10px 18px;border-radius:6px;text-decoration:none;">Revisar en e la plataforma</a></p>
<p style="color:#64748b;font-size:.85rem;margin-top:16px;">La información proporcionada será utilizada exclusivamente para fines de preselección.</p>',
        'text_plain' => 'El alumno {{studentName}} (matrícula {{matricula}}) se ha prepostulado a tu vacante {{practiceTitle}}. Ingresa a la plataforma para revisar su perfil: {{link}}',
    ],
    [
        'tkey' => 'pp_entrevista_programada_alumno',
        'name' => 'Entrevista programada (alumno)',
        'subject' => '¡Fuiste seleccionado para entrevista! - {{empresa}}',
        'html' => '<h2 style="color:#01643D;">¡Fuiste seleccionado para entrevista!</h2>
<p>Hola <strong>{{studentName}}</strong>,</p>
<p>La empresa <strong>{{empresa}}</strong> ha revisado tu perfil y te ha seleccionado para una entrevista.</p>
<ul>
  <li><strong>Fecha:</strong> {{fecha}}</li>
  <li><strong>Hora:</strong> {{hora}}</li>
  <li><strong>Modalidad:</strong> {{modalidad}}</li>
  {{{detalleModalidad}}}
</ul>
<p>Adjuntamos tu <strong>carta de presentación</strong> en PDF. Preséntala el día de tu entrevista.</p>
<p>¡Mucho éxito!</p>',
        'text_plain' => 'Fuiste seleccionado para entrevista en {{empresa}}. Fecha: {{fecha}}, Hora: {{hora}}, Modalidad: {{modalidad}}. Adjuntamos tu carta de presentación.',
    ],
    [
        'tkey' => 'pp_entrevista_virtual_empresa',
        'name' => 'Entrevista virtual (empresa)',
        'subject' => 'Entrevista virtual con {{studentName}}',
        'html' => '<h2 style="color:#01643D;">Entrevista virtual programada</h2>
<p>Hola <strong>{{contactName}}</strong>,</p>
<p>Se ha programado la entrevista virtual con el alumno <strong>{{studentName}}</strong>.</p>
<ul>
  <li><strong>Fecha:</strong> {{fecha}}</li>
  <li><strong>Hora:</strong> {{hora}}</li>
  <li><strong>Enlace de la sesión:</strong> <a href="{{url}}">{{url}}</a></li>
</ul>
<p>Adjuntamos la <strong>carta de presentación</strong> del alumno.</p>',
        'text_plain' => 'Entrevista virtual con {{studentName}}. Fecha: {{fecha}}, Hora: {{hora}}. Enlace: {{url}}. Adjuntamos la carta de presentación del alumno.',
    ],
    [
        'tkey' => 'pp_entrevista_presencial_empresa',
        'name' => 'Recordatorio entrevista presencial (empresa)',
        'subject' => 'Recordatorio: entrevista presencial con {{studentName}}',
        'html' => '<h2 style="color:#01643D;">Entrevista presencial programada</h2>
<p>Hola <strong>{{contactName}}</strong>,</p>
<p>Te recordamos que aceptaste para entrevista al alumno <strong>{{studentName}}</strong> y quedó programada de manera <strong>presencial</strong>:</p>
<ul>
  <li><strong>Fecha:</strong> {{fecha}}</li>
  <li><strong>Hora:</strong> {{hora}}</li>
  <li><strong>Lugar:</strong> {{direccion}}</li>
</ul>
<p>Adjuntamos la <strong>carta de presentación</strong> del alumno para tu registro.</p>
<p>Al terminar la entrevista, ingresa a la plataforma para cerrarla y registrar tu evaluación.</p>',
        'text_plain' => 'Recordatorio: entrevista presencial con {{studentName}}. Fecha: {{fecha}}, Hora: {{hora}}, Lugar: {{direccion}}. Adjuntamos la carta de presentación del alumno.',
    ],
    [
        'tkey' => 'pp_retroalimentacion_empresa',
        'name' => 'Retroalimentación post-entrevista (empresa)',
        'subject' => 'Retroalimentación de la entrevista con {{studentName}}',
        'html' => '<h2 style="color:#01643D;">¿Cómo te fue en la entrevista?</h2>
<p>Hola <strong>{{contactName}}</strong>,</p>
<p>La entrevista con <strong>{{studentName}}</strong> ya se realizó. Ingresa a la plataforma para <strong>cerrar la entrevista</strong>, registrar tu evaluación y decidir si aceptas o rechazas al alumno.</p>
<p><a href="{{link}}" style="display:inline-block;background:#01643D;color:#fff;padding:10px 18px;border-radius:6px;text-decoration:none;">Ir a la plataforma</a></p>',
        'text_plain' => 'La entrevista con {{studentName}} ya se realizó. Ingresa a la plataforma para cerrarla y evaluar: {{link}}',
    ],
];

try {
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
    echo "\n¡Actualización Fase 6 (correos) completada con éxito!\n";
} catch (PDOException $e) {
    echo "\n[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}
