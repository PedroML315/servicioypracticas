<?php
/**
 * Migración: correos de Servicio Social con formato o textos equivocados.
 *
 * Idempotente: puede ejecutarse múltiples veces sin errores.
 * Ejecutar desde CLI:  php database/migrations/update_plantillas_servicio_social.php
 *
 * 1. `new_event` («Nuevo evento disponible»): al editar la plantilla en Configuraciones quedó la
 *    descripción del evento envuelta en un <span> con letra monoespaciada, blanca y fondo azul
 *    (rgb(13, 110, 253)), que en el correo parece texto seleccionado. Solo se quita ese <span>.
 * 2. `ss_interno_evaluacion_global_alumno` («¡Tu Servicio Social ha sido liberado!»): el emoji del
 *    asunto y del título se guardó como «?» (la conexión usaba utf8, que no admite emojis de 4
 *    bytes). Se quita el «? » del inicio de ambos.
 * 3. Avisos a la Coordinación del trámite IJUMICH (`ss_interno_*_admin`): los datos del alumno
 *    salían pegados en un renglón («Alumno:Carlos… Correo:…»); quedan uno por renglón.
 * 4. `ss_interno_solicitud_registro_admin` pedía descargar, firmar y volver a subir el PDF (la
 *    plataforma lo firma y sella sola al aprobar), `ss_interno_carta_liberacion_admin` decía que
 *    era el último paso (siguen las dos evaluaciones) y `ss_interno_carta_presentacion_aprobada`
 *    (alumno externo) mandaba a descargar en el Paso 1 y a subir una «Carta de Finalización».
 * 5. `ss_interno_evaluacion_global_admin` y `ss_interno_evaluacion_global_alumno` venían en morado,
 *    `ss_interno_reporte_parcial_aprobado` («Reporte Parcial #N Aprobado») en morado y
 *    `ss_interno_carta_conclusion_alumno` en azul marino; pasan al verde de la plataforma, como el
 *    resto de los correos del trámite.
 * 6. `ss_interno_liberacion_completa` se reescribe: ahora es el correo de liberación del alumno
 *    externo (antes no se enviaba) y traía un folio que el externo no tiene.
 */

require_once __DIR__ . '/../../model/conection.php';

try {
    $pdo = Conexion::conectar();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Base de datos: " . $pdo->query("SELECT DATABASE()")->fetchColumn() . "\n";

    $leer = $pdo->prepare("SELECT id, subject, html FROM email_templates WHERE tkey = ?");
    $guardar = $pdo->prepare("UPDATE email_templates SET subject = :subject, html = :html WHERE id = :id");

    // 1. new_event: descripción sin fondo azul
    $leer->execute(['new_event']);
    $tpl = $leer->fetch(PDO::FETCH_ASSOC);
    if (!$tpl) {
        echo "   - No existe la plantilla new_event.\n";
    } else {
        $patron = '~<span\b[^>]*background-color:\s*rgb\(13,\s*110,\s*253\)[^>]*>\s*(\{\{\{?description\}?\}\})\s*</span>~i';
        $html = preg_replace($patron, '$1', $tpl['html'], -1, $cambios);
        if ($cambios === 0) {
            echo "   - new_event: la descripción ya no tiene el fondo azul.\n";
        } else {
            $guardar->execute([':subject' => $tpl['subject'], ':html' => $html, ':id' => $tpl['id']]);
            echo "   - new_event: se quitó el fondo azul de la descripción.\n";
        }
    }

    // 2. ss_interno_evaluacion_global_alumno: sin el «?» del emoji perdido
    $leer->execute(['ss_interno_evaluacion_global_alumno']);
    $tpl = $leer->fetch(PDO::FETCH_ASSOC);
    if (!$tpl) {
        echo "   - No existe la plantilla ss_interno_evaluacion_global_alumno.\n";
    } else {
        $subject = preg_replace('/^\?\s+/u', '', $tpl['subject']);
        $html = preg_replace('~(<h1[^>]*>)\?\s+~u', '$1', $tpl['html']);
        if ($subject === $tpl['subject'] && $html === $tpl['html']) {
            echo "   - ss_interno_evaluacion_global_alumno: ya no tiene el «?».\n";
        } else {
            $guardar->execute([':subject' => $subject, ':html' => $html, ':id' => $tpl['id']]);
            echo "   - ss_interno_evaluacion_global_alumno: se quitó el «?» del asunto y del título.\n";
        }
    }

    // 3. Datos del alumno uno por renglón en los avisos a la Coordinación
    $conDatosPegados = $pdo->query(
        "SELECT id, tkey, subject, html FROM email_templates WHERE html LIKE '%Alumno:{{studentName}}%'"
    )->fetchAll(PDO::FETCH_ASSOC);
    $separar = function (array $m): string {
        $texto = trim(str_replace('&nbsp;', ' ', $m[1]));
        $partes = preg_split('/\s*(?=\b(?:Alumno|Correo|Reporte|Archivo|Folio|Periodo|Horas):)/u', $texto, -1, PREG_SPLIT_NO_EMPTY);
        $renglones = array_map(fn($p) => preg_replace('/^(\p{L}+):\s*/u', '<strong>$1:</strong> ', trim($p)), $partes);
        return '<p>' . implode('<br>', $renglones) . '</p>';
    };
    if (!$conDatosPegados) {
        echo "   - Los avisos a la Coordinación ya muestran los datos del alumno uno por renglón.\n";
    }
    foreach ($conDatosPegados as $tpl) {
        $html = preg_replace_callback('~<p>\s*(?:Datos\s+)?(Alumno:\s*\{\{studentName\}\}.*?)</p>~su', $separar, $tpl['html']);
        if ($html !== $tpl['html']) {
            $guardar->execute([':subject' => $tpl['subject'], ':html' => $html, ':id' => $tpl['id']]);
            echo "   - {$tpl['tkey']}: datos del alumno uno por renglón.\n";
        }
    }

    // 3b. ss_interno_carta_presentacion_admin (alumno externo) tiene sus propios bloques
    $leer->execute(['ss_interno_carta_presentacion_admin']);
    $tpl = $leer->fetch(PDO::FETCH_ASSOC);
    if ($tpl) {
        $html = preg_replace(
            '~<p>\s*Datos del alumno\s+Alumno:\s*\{\{studentName\}\}\s*Correo:\s*\{\{studentEmail\}\}(?:&nbsp;|\s)*Matrícula:\s*\{\{matricula\}\}(?:&nbsp;|\s)*</p>~su',
            '<p><strong>Datos del alumno</strong><br><strong>Alumno:</strong> {{studentName}}<br><strong>Correo:</strong> {{studentEmail}}<br><strong>Matrícula:</strong> {{matricula}}</p>',
            $tpl['html']
        );
        $html = preg_replace(
            '~<p>\s*Datos del organismo receptor\s+Organismo:\s*\{\{organismoNombre\}\}\s*</p>\s*(?:<p>\s*<br>\s*</p>\s*)?<p>\s*Responsable:\s*\{\{responsable\}\}\s*(Ingresa al panel.*?)</p>~su',
            '<p><strong>Datos del organismo receptor</strong><br><strong>Organismo:</strong> {{organismoNombre}}<br><strong>Responsable:</strong> {{responsable}}</p><p>$1</p>',
            $html
        );
        if ($html !== $tpl['html']) {
            $guardar->execute([':subject' => $tpl['subject'], ':html' => $html, ':id' => $tpl['id']]);
            echo "   - ss_interno_carta_presentacion_admin: datos del alumno y del organismo uno por renglón.\n";
        }
    }

    // 4. Textos que describían mal el trámite
    $textos = [
        'ss_interno_solicitud_registro_admin' => [
            '~<p>\s*⚠️\s*<strong>Acción requerida:</strong>\s*Descarga el PDF.*?</p>~su',
            '<p>⚠️ <strong>Acción requerida:</strong> Revisa la solicitud en «Solicitudes IJUMICH» y apruébala: la plataforma le pone la firma y el sello y el alumno la puede descargar.</p>',
        ],
        'ss_interno_carta_presentacion_aprobada' => [
            '~desde el portal \(Paso 1\) en cualquier momento\.</p>\s*<p>\{\{comentario\}\}</p>\s*<p><strong>Siguiente paso:</strong>.*?</p>~su',
            'desde el portal (Paso 3) en cualquier momento.</p>{{#if comentario}}<p>{{comentario}}</p>{{/if}}<p><strong>Siguiente paso:</strong> Presenta la carta en tu organismo receptor y realiza tu servicio social. Al concluirlo, sube tu <strong>Carta de Liberación (Paso 5)</strong>.</p>',
        ],
        'ss_interno_carta_liberacion_admin' => [
            '~<p>\s*✅\s*<strong>Último paso:</strong>.*?</p>~su',
            '<p>✅ <strong>Siguiente paso:</strong> Al aprobar la carta, el alumno podrá subir su Evaluación de la Unidad Productiva (Paso 8) y después su Evaluación Global (Paso 9), con la que queda liberado.</p>',
        ],
    ];
    foreach ($textos as $tkey => [$patron, $nuevo]) {
        $leer->execute([$tkey]);
        $tpl = $leer->fetch(PDO::FETCH_ASSOC);
        if (!$tpl) {
            echo "   - No existe la plantilla {$tkey}.\n";
            continue;
        }
        $html = preg_replace($patron, $nuevo, $tpl['html'], 1, $cambios);
        if ($cambios === 0) {
            echo "   - {$tkey}: el texto ya está corregido.\n";
        } else {
            $guardar->execute([':subject' => $tpl['subject'], ':html' => $html, ':id' => $tpl['id']]);
            echo "   - {$tkey}: texto corregido.\n";
        }
    }

    // 5. Colores de la plataforma (verde #01643D) en los correos que venían en morado
    $colores = [
        'ss_interno_evaluacion_global_admin' => [
            '#7c3aed' => '#01643D', '#f5f3ff' => '#fffbeb', '#ddd6fe' => '#fde68a', '#5b21b6' => '#92400e',
        ],
        'ss_interno_evaluacion_global_alumno' => [
            'linear-gradient(135deg,#01643D,#7c3aed)' => '#01643D',
        ],
        // Encabezado, etiqueta «Reporte Parcial #N» y recuadro «Siguiente paso»
        'ss_interno_reporte_parcial_aprobado' => [
            '#4a1486' => '#01643D', '#f3e5f5' => '#E8F5E9',
        ],
        // Encabezado y recuadro del folio
        'ss_interno_carta_conclusion_alumno' => [
            '#1a3a5c' => '#01643D', '#e8f4fd' => '#E8F5E9',
        ],
    ];
    foreach ($colores as $tkey => $cambiosColor) {
        $leer->execute([$tkey]);
        $tpl = $leer->fetch(PDO::FETCH_ASSOC);
        if (!$tpl) continue;
        $html = str_ireplace(array_keys($cambiosColor), array_values($cambiosColor), $tpl['html']);
        if ($html === $tpl['html']) {
            echo "   - {$tkey}: ya usa los colores de la plataforma.\n";
        } else {
            $guardar->execute([':subject' => $tpl['subject'], ':html' => $html, ':id' => $tpl['id']]);
            echo "   - {$tkey}: colores de la plataforma (verde).\n";
        }
    }

    // 6. ss_interno_liberacion_completa: ahora la usa el alumno externo al aprobarse su carta de
    //    liberación (antes no se enviaba nunca). Traía un «Folio de conclusión» que el externo no
    //    tiene y los datos pegados; se reescribe completa.
    $liberacionHtml = '<h1><font style="font-size: 16px;">¡Servicio Social Liberado!</font></h1>'
        . '<p>Hola <strong>{{studentName}}</strong>,</p>'
        . '<p>¡Felicidades! Tu <strong>Servicio Social</strong> ha sido liberado oficialmente: la Coordinación aprobó tu '
        . '<strong>Carta de Liberación</strong> y con ella completaste todos los requisitos.</p>'
        . '<p><strong>Fecha de liberación:</strong> {{fechaLiberacion}}<br><strong>Aprobado por:</strong> {{adminName}}'
        . '{{#if folio}}<br><strong>Folio:</strong> {{folio}}{{/if}}</p>'
        . '{{#if comentario}}<p><em>Comentario: {{comentario}}</em></p>{{/if}}'
        . '<p>Guarda este correo como comprobante.</p>'
        . '<p>Atentamente,<br><strong>Universidad Montrer</strong><br>Coordinación de Servicio Social</p>';
    $liberacionTexto = "¡Servicio Social Liberado!\n\nHola {{studentName}},\n\n"
        . "¡Felicidades! Tu Servicio Social ha sido liberado oficialmente: la Coordinación aprobó tu Carta de Liberación "
        . "y con ella completaste todos los requisitos.\n\nFecha de liberación: {{fechaLiberacion}}\nAprobado por: {{adminName}}"
        . "{{#if folio}}\nFolio: {{folio}}{{/if}}{{#if comentario}}\n\nComentario: {{comentario}}{{/if}}\n\n"
        . "Guarda este correo como comprobante.\n\nAtentamente,\nUniversidad Montrer\nCoordinación de Servicio Social";
    $leer->execute(['ss_interno_liberacion_completa']);
    $tpl = $leer->fetch(PDO::FETCH_ASSOC);
    if (!$tpl) {
        echo "   - No existe la plantilla ss_interno_liberacion_completa.\n";
    } elseif ($tpl['html'] === $liberacionHtml) {
        echo "   - ss_interno_liberacion_completa: ya está reescrita.\n";
    } else {
        $pdo->prepare("UPDATE email_templates SET html = :html, text_plain = :txt WHERE id = :id")
            ->execute([':html' => $liberacionHtml, ':txt' => $liberacionTexto, ':id' => $tpl['id']]);
        echo "   - ss_interno_liberacion_completa: reescrita (correo de liberación del alumno externo).\n";
    }

    echo "\n✅ Migración completada con éxito.\n";

} catch (PDOException $e) {
    echo "❌ Error PDO: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
