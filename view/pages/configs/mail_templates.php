<?php
// admin/email-templates-editor.php

// CSRF para incrustar en meta y usarlo por AJAX
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// CSS real que el mailer inyecta al enviar (mismo archivo que usa controller/emails.php)
$emailCssPath = __DIR__ . '/../../assets/css/email_templates/email.css';
$emailCss = is_file($emailCssPath) ? (string) file_get_contents($emailCssPath) : '';
?>
<meta name="csrf-token" content="<?= htmlspecialchars($csrf) ?>">

<style>
    /* mail-templates specific */
    code.k {
        background: #f6f8fa;
        padding: 3px 7px;
        border-radius: 6px;
        font-family: monospace;
        font-size: .9rem;
        color: #0d6efd;
        cursor: pointer;
        position: relative;
        transition: all .2s ease-in-out;
    }

    code.k:hover {
        background: #0d6efd;
        color: #fff;
        box-shadow: 0 2px 6px rgba(13, 110, 253, .3);
        transform: translateY(-2px);
    }

    code.k::after {
        content: attr(hover);
        position: absolute;
        bottom: 125%;
        left: 50%;
        transform: translateX(-50%);
        background: #212529;
        color: #fff;
        padding: 5px 8px;
        border-radius: 6px;
        font-size: .75rem;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: opacity .2s, transform .2s;
        z-index: 10;
    }

    code.k:hover::after {
        opacity: 1;
        transform: translateX(-50%) translateY(-2px);
    }

    code.k.copied::after {
        content: "✅ Copiado";
        background: #198754;
    }

    /* Editor de código HTML (modo avanzado) */
    #f_html_code {
        font-family: "Fira Code", Consolas, Monaco, monospace;
        font-size: .82rem;
        line-height: 1.55;
        min-height: 300px;
        background: #f8fafc;
        color: #0f172a;
        border-radius: 8px;
        white-space: pre-wrap;
        tab-size: 2;
        resize: vertical;
    }

    /* Toggle Editor / Código */
    .mt-viewtabs {
        display: inline-flex;
        gap: 0;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        overflow: hidden;
    }

    .mt-viewtabs button {
        border: 0;
        background: #fff;
        padding: 6px 14px;
        font-size: .78rem;
        font-weight: 600;
        color: #374151;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .mt-viewtabs button + button {
        border-left: 1px solid #d1d5db;
    }

    .mt-viewtabs button.active {
        background: #111827;
        color: #fff;
    }

    /* Marco de la vista/editor visual */
    .mt-preview-wrap {
        border: 1px solid #d1d5db;
        border-radius: 10px;
        overflow: hidden;
        background: #F2F2F5; /* mismo fondo que el body del correo */
    }

    .mt-preview-bar {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 14px;
        background: #fff;
        border-bottom: 1px solid #e5e7eb;
        font-size: .78rem;
        color: #6b7280;
    }

    .mt-preview-bar .dot {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        display: inline-block;
    }

    .mt-preview-subject {
        font-weight: 600;
        color: #111827;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* Barra de herramientas del editor visual */
    .mt-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 3px;
        padding: 6px 10px;
        background: #fff;
        border-bottom: 1px solid #e5e7eb;
    }

    .mt-toolbar .tb {
        border: 0;
        background: transparent;
        border-radius: 6px;
        width: 32px;
        height: 30px;
        font-size: .82rem;
        color: #374151;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .mt-toolbar .tb:hover {
        background: #f3f4f6;
        color: #111827;
    }

    .mt-toolbar .tb.wide {
        width: auto;
        padding: 0 10px;
        gap: 6px;
        font-size: .76rem;
        font-weight: 600;
    }

    .mt-toolbar .tb-sep {
        width: 1px;
        height: 20px;
        background: #e5e7eb;
        margin: 0 5px;
    }

    .mt-toolbar .tb-color {
        width: auto;
        padding: 0 6px;
        gap: 4px;
        margin-bottom: 0;
        cursor: pointer;
    }

    .mt-toolbar .tb-color input[type="color"] {
        width: 22px;
        height: 20px;
        border: 1px solid #d1d5db;
        border-radius: 4px;
        padding: 0;
        background: none;
        cursor: pointer;
    }

    .mt-toolbar select {
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: .76rem;
        padding: 4px 6px;
        color: #374151;
        background: #fff;
    }

    /* Lienzo del correo (réplica del mail enviado) */
    .mail-canvas {
        padding: 4px 12px;
    }

    .mail-canvas .content[contenteditable="true"] {
        outline: none;
        min-height: 120px;
        cursor: text;
    }

    .mail-canvas .content[contenteditable="true"]:hover {
        box-shadow: inset 0 0 0 1px rgba(34, 197, 94, .25);
        border-radius: 6px;
    }

    .mail-canvas .content[contenteditable="true"]:focus {
        box-shadow: inset 0 0 0 2px rgba(34, 197, 94, .4);
        border-radius: 6px;
    }

    .mt-edit-hint {
        text-align: center;
        font-size: .74rem;
        color: #9ca3af;
        padding: 0 0 12px;
    }

    /* Chips de variables dentro del editor visual.
       color/tamaño se heredan para que el formato aplicado se vea tal cual. */
    .var-token {
        background: #FEF3C7;
        color: inherit;
        font-size: inherit;
        border: 1px dashed #F59E0B;
        border-radius: 5px;
        padding: 0 5px;
        white-space: nowrap;
        cursor: pointer;
    }

    .var-token:hover {
        border-style: solid;
        box-shadow: 0 0 0 2px rgba(245, 158, 11, .25);
    }

    .var-token.is-selected {
        border-style: solid;
        box-shadow: 0 0 0 2px rgba(34, 197, 94, .45);
    }

    #previewFrame {
        display: block;
        width: 100%;
        border: 0;
        min-height: 420px;
        background: #F2F2F5;
    }

    .badge-locale {
        font-weight: 500;
    }

    @media (max-width: 991.98px) {
        :root {
            --rail-w: 100vw;
        }

        .has-rail {
            padding-right: 0;
            padding-bottom: 44vh;
        }

        .fixed-rail {
            left: 0;
            right: 0;
            width: 100%;
            top: auto;
            bottom: 0;
            height: 42vh;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
            background: #f6f7fb;
            padding: 8px 12px;
        }
    }
</style>

<div class="container has-rail">
  <div class="mb-4">
    <h1 class="h4 fw-bold mb-1"><i class="fa-solid fa-envelope me-2 text-primary"></i>Plantillas de Correo</h1>
    <p class="text-muted mb-0" style="font-size:.85rem">Sistema · Edita el asunto y cuerpo de cada notificación tal como se enviará</p>
  </div>

    <div id="alertBox"></div>

    <div class="form-section">
        <div class="row g-3 align-items-end">
            <div class="col-md-12">
                <label class="form-label">Plantilla</label>
                <div class="d-flex gap-2">
                    <select id="tplSelect" class="form-select">
                        <option value="">— Selecciona una plantilla —</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <form id="tplForm" onsubmit="return false;">
        <div class="form-section">
            <h2 class="h6 mb-3">Asunto</h2>
            <input type="text" class="form-control" id="f_subject"
                placeholder="Asunto del correo" disabled>
        </div>

        <div class="form-section">
            <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                <h2 class="h6 mb-0">Contenido del correo</h2>
                <div class="mt-viewtabs" role="tablist" aria-label="Modo de edición">
                    <button type="button" id="tabVisual" class="active">
                        <i class="fa-solid fa-wand-magic-sparkles"></i> Editor
                    </button>
                    <button type="button" id="tabCode">
                        <i class="fa-solid fa-code"></i> Código HTML (avanzado)
                    </button>
                </div>
            </div>

            <!-- Editor visual: se edita directamente sobre la réplica del correo -->
            <div id="visualPane" class="mt-preview-wrap">
                <div class="mt-preview-bar">
                    <span class="dot" style="background:#fc5753"></span>
                    <span class="dot" style="background:#fdbc40"></span>
                    <span class="dot" style="background:#33c748"></span>
                    <i class="fa-solid fa-envelope-open-text ms-2"></i>
                    <span class="mt-preview-subject" id="previewSubject">—</span>
                </div>

                <div class="mt-toolbar" id="mtToolbar" style="display:none">
                    <select id="mtBlock" title="Tipo de texto">
                        <option value="P">Párrafo</option>
                        <option value="H1">Título grande</option>
                        <option value="H2">Título</option>
                        <option value="H3">Subtítulo</option>
                    </select>
                    <select id="mtFontSize" title="Tamaño de letra">
                        <option value="">Tamaño</option>
                        <option value="12px">Pequeño</option>
                        <option value="14px">Chico</option>
                        <option value="16px">Normal</option>
                        <option value="20px">Grande</option>
                        <option value="26px">Muy grande</option>
                        <option value="34px">Enorme</option>
                    </select>
                    <span class="tb-sep"></span>
                    <button type="button" class="tb" data-cmd="bold" title="Negritas"><i class="fa-solid fa-bold"></i></button>
                    <button type="button" class="tb" data-cmd="italic" title="Cursiva"><i class="fa-solid fa-italic"></i></button>
                    <button type="button" class="tb" data-cmd="underline" title="Subrayado"><i class="fa-solid fa-underline"></i></button>
                    <button type="button" class="tb" data-cmd="strikeThrough" title="Tachado"><i class="fa-solid fa-strikethrough"></i></button>
                    <span class="tb-sep"></span>
                    <label class="tb tb-color" title="Color del texto">
                        <i class="fa-solid fa-font"></i>
                        <input type="color" id="colorFore" value="#1C1C1E">
                    </label>
                    <label class="tb tb-color" title="Color de resaltado (marcatextos)">
                        <i class="fa-solid fa-highlighter"></i>
                        <input type="color" id="colorBack" value="#FFF59D">
                    </label>
                    <span class="tb-sep"></span>
                    <button type="button" class="tb" data-cmd="justifyLeft" title="Alinear a la izquierda"><i class="fa-solid fa-align-left"></i></button>
                    <button type="button" class="tb" data-cmd="justifyCenter" title="Centrar"><i class="fa-solid fa-align-center"></i></button>
                    <button type="button" class="tb" data-cmd="justifyRight" title="Alinear a la derecha"><i class="fa-solid fa-align-right"></i></button>
                    <span class="tb-sep"></span>
                    <button type="button" class="tb" data-cmd="insertUnorderedList" title="Lista con viñetas"><i class="fa-solid fa-list-ul"></i></button>
                    <button type="button" class="tb" data-cmd="insertOrderedList" title="Lista numerada"><i class="fa-solid fa-list-ol"></i></button>
                    <span class="tb-sep"></span>
                    <button type="button" class="tb" id="tbLink" title="Insertar enlace"><i class="fa-solid fa-link"></i></button>
                    <button type="button" class="tb" data-cmd="unlink" title="Quitar enlace"><i class="fa-solid fa-link-slash"></i></button>
                    <button type="button" class="tb wide" id="tbButton" title="Insertar botón como el del correo">
                        <i class="fa-solid fa-square-plus"></i> Botón
                    </button>
                    <span class="tb-sep"></span>
                    <button type="button" class="tb wide" id="tbInfoBox" title="Resaltar el texto seleccionado como dato importante (estilo del correo)">
                        <i class="fa-solid fa-square-check"></i> Destacado
                    </button>
                    <button type="button" class="tb wide" id="tbNota" title="Convertir el texto seleccionado en nota gris pequeña (estilo del correo)">
                        <i class="fa-solid fa-circle-info"></i> Nota
                    </button>
                    <span class="tb-sep"></span>
                    <button type="button" class="tb" data-cmd="removeFormat" title="Limpiar formato"><i class="fa-solid fa-eraser"></i></button>
                    <button type="button" class="tb" data-cmd="undo" title="Deshacer"><i class="fa-solid fa-rotate-left"></i></button>
                    <button type="button" class="tb" data-cmd="redo" title="Rehacer"><i class="fa-solid fa-rotate-right"></i></button>
                </div>

                <!-- Lienzo: misma estructura que arma controller/emails.php al enviar -->
                <div class="mail-canvas" id="mailCanvas" style="display:none">
                    <div class="container">
                        <div class="header">
                            <img src="view/assets/images/logo-color.png" alt="Logo UNIMO">
                        </div>
                        <div class="content" id="mailContent"></div>
                        <div class="footer">Universidad Montrer (UNIMO) • Av Lázaro Cárdenas 1760, Chapultepec Sur, 58260 Morelia, Mich.</div>
                    </div>
                    <div class="mt-edit-hint">
                        <i class="fa-solid fa-pen"></i> Haz clic sobre el texto del correo para editarlo.
                        Las etiquetas ámbar son datos automáticos (nombre del alumno, fechas…):
                        haz clic en una para seleccionarla y aplicarle formato (tamaño, color, centrado, negritas…).
                        Su texto interior no se edita porque lo rellena el sistema.
                    </div>
                </div>

                <!-- Aviso + vista previa de solo lectura para plantillas HTML completas -->
                <div id="fullHtmlNotice" class="alert alert-info m-3" style="display:none; font-size:.82rem">
                    <i class="fa-solid fa-circle-info me-1"></i>
                    Esta plantilla es un documento HTML completo con diseño propio, por lo que aquí se muestra
                    la <strong>vista previa exacta</strong> del correo. Para modificarla usa la pestaña
                    <strong>Código HTML (avanzado)</strong>.
                </div>
                <iframe id="previewFrame" title="Vista previa del correo" sandbox="allow-same-origin" style="display:none"></iframe>

                <div id="emptyState" class="text-center text-muted py-5" style="font-size:.85rem">
                    <i class="fa-regular fa-envelope fa-2x d-block mb-2"></i>
                    Selecciona una plantilla para ver y editar el correo.
                </div>
            </div>

            <!-- Código HTML crudo: es exactamente lo que se guarda y se envía -->
            <div id="codePane" style="display:none">
                <textarea class="form-control" id="f_html_code" spellcheck="false" disabled
                    placeholder="HTML de la plantilla…"></textarea>
                <div class="form-text mt-2">
                    Inyecta CSS con <code class="k" hover="CSS embebido en &lt;style&gt;">{{{css}}}</code>.
                    Para HTML confiable usa <code class="k" hover="HTML sin escapar">{{{description}}}</code>.
                    Por defecto <code>{{var}}</code> se escapa.
                </div>
            </div>
        </div>
    </form>

</div>

<aside class="fixed-rail" aria-label="Panel fijo de variables y acciones">
    <div class="vars-fixed card">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
                <strong>Datos automáticos</strong>
            </div>
            <p class="text-muted mb-0 mt-1" style="font-size:.72rem">
                El sistema los rellena solo al enviar cada correo (nombre del alumno, fechas, folios…).
            </p>
            <div id="varsWrap" class="mt-2 d-flex flex-wrap gap-2"></div>
            <small class="d-block mt-3 text-muted">
                Haz clic en uno para insertarlo donde esté el cursor (correo o asunto).
            </small>
            <hr>
            <div>
                <strong>Atajos comunes:</strong>
                <div class="mt-2 d-flex flex-wrap gap-2">
                    <code class="k" hover="Año actual">{{year}}</code>
                    <code class="k" hover="Asunto actual">{{subject}}</code>
                </div>
            </div>
        </div>
    </div>

    <div class="sticky-actions d-flex flex-wrap gap-2">
        <button class="btn btn-primary" id="btnSave">Guardar cambios</button>
        <button class="btn btn-light" id="btnReload">Reiniciar</button>
        <button class="btn btn-outline-success w-100" id="btnTest" disabled>
            <i class="fa-solid fa-paper-plane me-1"></i>Enviar correo de prueba
        </button>
    </div>
</aside>

<!-- Modal: correo de prueba -->
<div class="modal fade" id="testMailModal" tabindex="-1" aria-labelledby="testMailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="testMailModalLabel">
                    <i class="fa-solid fa-paper-plane me-2 text-success"></i>Enviar correo de prueba
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted" style="font-size:.82rem">
                    Se enviará el correo <strong>tal como se ve en el editor</strong>, con datos de ejemplo
                    y el asunto con el prefijo <span class="badge bg-warning text-dark">[PRUEBA]</span>.
                    No se guarda nada: es solo para verificar cómo llega al buzón.
                </p>
                <label class="form-label" for="testMailTo">¿A qué correo enviamos la prueba?</label>
                <input type="email" class="form-control" id="testMailTo" placeholder="tucorreo@ejemplo.com" autocomplete="email">
                <div class="invalid-feedback">Escribe un correo válido, por ejemplo: nombre@dominio.com</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btnTestSendConfirm">
                    <i class="fa-solid fa-paper-plane me-1"></i>Enviar prueba
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    // ── CSS real del mailer (mismo archivo que inyecta controller/emails.php) ──
    var EMAIL_CSS = <?= json_encode($emailCss, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    var USER_EMAIL = <?= json_encode($_SESSION['user']['email'] ?? '') ?>;
    var CSRF = <?= json_encode($csrf) ?>;

    // ── Datos de ejemplo para las variables más comunes de las plantillas ──
    var SAMPLE_VARS = {
        studentName: 'Juan Pérez López',
        nameStudent: 'Juan Pérez López',
        contactName: 'María García Ruiz',
        teacherName: 'Mtro. Carlos Sánchez',
        adminName: 'Coordinación UNIMO',
        organizerName: 'Coordinación de Eventos',
        name: 'Juan Pérez López',
        email: 'alumno@ejemplo.com',
        studentEmail: 'alumno@ejemplo.com',
        correo: 'contacto@ejemplo.com',
        emailPP: 'practicas@unimontrer.edu.mx',
        password: 'Abc123!x',
        role: 'alumno_servicio',
        login_url: 'https://servicioypracticas.unimontrer.edu.mx/',
        enlaceFirma: 'https://servicioypracticas.unimontrer.edu.mx/',
        enlaceCorreccion: 'https://servicioypracticas.unimontrer.edu.mx/',
        eventName: 'Feria de Salud Comunitaria',
        eventDate: '15/08/2026',
        description: 'Descripción de ejemplo del evento.',
        empresa: 'Empresa Ejemplo, S.A. de C.V.',
        orgName: 'Organismo Ejemplo, A.C.',
        organismoName: 'Organismo Ejemplo, A.C.',
        organismoNombre: 'Organismo Ejemplo, A.C.',
        areaNombre: 'Sistemas y Tecnología',
        degreeName: 'Lic. en Derecho',
        practice: 'Práctica #42',
        matricula: '2023001234',
        folio: 'SS-2026-0001',
        orgId: '42',
        responsable: 'Lic. Ana Torres',
        archivoNombre: 'documento_firmado.pdf',
        numeroReporte: '2',
        points: '10',
        horas: '480',
        meses: '6',
        horasReales: '2.5',
        numStrike: '1',
        strikesCount: '1',
        diasHabilesDisponibles: '2',
        fecha: '09/07/2026',
        date: '09/07/2026',
        dateCreated: '09/07/2026',
        fechaInicio: '01/08/2026',
        fechaFin: '01/02/2027',
        fechaEnvio: '09/07/2026 13:45',
        fechaGeneracion: '09/07/2026 13:45',
        fechaCorreccion: '09/07/2026 13:45',
        fechaLiberacion: '09/07/2026 13:45',
        fechaVencimiento: '11/07/2026',
        expiraEn: '12/07/2026 13:45',
        horaEntrada: '09:00',
        horaSalida: '14:00',
        actividad: 'Apoyo en actividades administrativas.',
        comentario: 'Comentario de ejemplo del revisor.',
        comments: 'Comentario de ejemplo del revisor.',
        motivo: 'Motivo de ejemplo capturado por el administrador.',
        motivoGeneral: 'Motivo general de ejemplo.',
        nextStepMsg: 'Siguiente paso: sube tu Reporte Parcial #2 en 2 meses.',
        solicitud: 'Solicito capacitación en primeros auxilios.',
        direccionPractica: 'Av. Ejemplo 123, Morelia, Mich.',
        actividades: 'Apoyo jurídico y atención a usuarios.',
        userType: 'organismo externo',
        reason: 'Motivo de ejemplo.',
        otp: '482913'
    };

    // Descripciones amigables para el panel de "Datos automáticos"
    var VAR_LABELS = {
        studentName: 'Nombre del alumno', nameStudent: 'Nombre del alumno',
        contactName: 'Nombre del contacto', teacherName: 'Nombre del docente',
        adminName: 'Nombre del administrador', organizerName: 'Organizador del evento',
        name: 'Nombre del usuario', email: 'Correo del destinatario',
        studentEmail: 'Correo del alumno', correo: 'Correo de contacto',
        emailPP: 'Correo de Prácticas Profesionales', password: 'Contraseña generada',
        role: 'Tipo de usuario', login_url: 'Enlace para iniciar sesión',
        enlaceFirma: 'Enlace para firmar', enlaceCorreccion: 'Enlace de corrección',
        eventName: 'Nombre del evento', eventDate: 'Fecha del evento',
        description: 'Descripción', empresa: 'Nombre de la empresa',
        orgName: 'Nombre del organismo', organismoName: 'Nombre del organismo',
        organismoNombre: 'Nombre del organismo', areaNombre: 'Área interna',
        degreeName: 'Licenciatura', practice: 'Práctica',
        matricula: 'Matrícula', folio: 'Folio', orgId: 'ID del organismo',
        responsable: 'Responsable', archivoNombre: 'Nombre del archivo',
        numeroReporte: 'Número de reporte', points: 'Puntos', horas: 'Horas',
        meses: 'Meses', horasReales: 'Horas registradas', numStrike: 'Número de strike',
        strikesCount: 'Total de strikes', diasHabilesDisponibles: 'Días hábiles',
        fecha: 'Fecha', date: 'Fecha', dateCreated: 'Fecha de creación',
        fechaInicio: 'Fecha de inicio', fechaFin: 'Fecha de fin',
        fechaEnvio: 'Fecha de envío', fechaGeneracion: 'Fecha de generación',
        fechaCorreccion: 'Fecha de corrección', fechaLiberacion: 'Fecha de liberación',
        fechaVencimiento: 'Fecha de vencimiento', expiraEn: 'Fecha de expiración',
        horaEntrada: 'Hora de entrada', horaSalida: 'Hora de salida',
        actividad: 'Actividad', comentario: 'Comentario', comments: 'Comentarios',
        motivo: 'Motivo', motivoGeneral: 'Motivo general',
        nextStepMsg: 'Mensaje de siguiente paso', solicitud: 'Solicitud',
        direccionPractica: 'Dirección de la práctica', actividades: 'Actividades',
        userType: 'Tipo de revisor', reason: 'Motivo', otp: 'Código de verificación',
        year: 'Año actual', subject: 'Asunto del correo'
    };

    var HTML_ESCAPES = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, function (c) { return HTML_ESCAPES[c]; });
    }

    var TOKEN_RE = /\{\{\{?\s*([a-zA-Z0-9_]+)\s*\}?\}\}/g;

    function sampleText(name) {
        if (Object.prototype.hasOwnProperty.call(SAMPLE_VARS, name)) {
            return String(SAMPLE_VARS[name]).replace(/<[^>]*>/g, '');
        }
        return name;
    }

    /* ═══════════ CSS del correo, acotado al lienzo del editor ═══════════ */
    // Convierte "body {...}" en ".mail-canvas {...}" y prefija el resto de
    // selectores para que los estilos reales del correo apliquen solo dentro
    // del lienzo (sin afectar al panel de administración).
    function scopeEmailCss(css, scope) {
        return css.replace(/(^|\})([^{}]+)\{/g, function (_, close, selectors) {
            var scoped = selectors.split(',').map(function (sel) {
                var s = sel.trim();
                if (!s) return s;
                if (/^(html|body)$/i.test(s)) return scope;
                return scope + ' ' + s;
            }).join(', ');
            return close + ' ' + scoped + ' {';
        });
    }

    var styleTag = document.createElement('style');
    styleTag.id = 'mailCanvasCss';
    styleTag.textContent = scopeEmailCss(EMAIL_CSS, '.mail-canvas');
    document.head.appendChild(styleTag);

    /* ═══════════ Interpolación (réplica de controller/emails.php) ═══════════ */
    function varChipHtml(name) {
        return '<span style="background:#FEF3C7;color:#92400E;border:1px dashed #F59E0B;' +
            'border-radius:4px;padding:0 5px;font-family:monospace;font-size:.9em;">' + escapeHtml(name) + '</span>';
    }

    function interpolate(tpl, vars) {
        tpl = tpl.replace(/\{\{\{\s*([a-zA-Z0-9_]+)\s*\}\}\}/g, function (_, k) {
            if (Object.prototype.hasOwnProperty.call(vars, k)) return String(vars[k]);
            if (Object.prototype.hasOwnProperty.call(SAMPLE_VARS, k)) return String(SAMPLE_VARS[k]);
            return varChipHtml(k);
        });
        tpl = tpl.replace(/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/g, function (_, k) {
            if (Object.prototype.hasOwnProperty.call(vars, k)) return escapeHtml(String(vars[k]));
            if (Object.prototype.hasOwnProperty.call(SAMPLE_VARS, k)) return escapeHtml(String(SAMPLE_VARS[k]));
            return varChipHtml(k);
        });
        return tpl;
    }

    function interpolateSubject(tpl) {
        return (tpl || '').replace(TOKEN_RE, function (_, k) {
            if (k === 'year') return String(new Date().getFullYear());
            return sampleText(k) === k ? '[' + k + ']' : sampleText(k);
        });
    }

    // Documento completo tal como lo arma sendTemplateByKey() al enviar
    function buildEmailDoc(rawHtml, subjectText) {
        var vars = {
            css: EMAIL_CSS,
            year: String(new Date().getFullYear()),
            subject: subjectText
        };
        var rendered = interpolate(rawHtml || '', vars);
        var isFullHtml = rendered.toLowerCase().indexOf('<html') !== -1;
        if (isFullHtml) return rendered;

        return '<html><head><meta charset="UTF-8"><title>' + escapeHtml(subjectText) + '</title>' +
            '<style>' + EMAIL_CSS + '</style></head><body>' +
            '<div class="container">' +
            '<div class="header"><img src="https://servicioypracticas.unimontrer.edu.mx/view/assets/images/logo-color.png" alt="Logo UNIMO"></div>' +
            '<div class="content">' + rendered + '</div>' +
            '<div class="footer">Universidad Montrer (UNIMO) • Av Lázaro Cárdenas 1760, Chapultepec Sur, 58260 Morelia, Mich.</div>' +
            '</div></body></html>';
    }

    /* ═══════════ Editor visual: tokens ⇄ chips ═══════════ */
    var mailContent = document.getElementById('mailContent');

    function makeChip(raw, name) {
        var span = document.createElement('span');
        span.className = 'var-token';
        span.contentEditable = 'false';
        span.setAttribute('data-raw', raw);
        var label = VAR_LABELS[name] || name;
        span.title = label + ' — se rellena automáticamente al enviar (' + raw + ')';
        span.textContent = sampleText(name);
        return span;
    }

    // Convierte {{var}}/{{{var}}} de los NODOS DE TEXTO en chips protegidos.
    // Los tokens dentro de atributos (href="{{login_url}}") se conservan tal cual.
    function renderTokensAsChips(html) {
        var tpl = document.createElement('template');
        tpl.innerHTML = html || '';
        var walker = document.createTreeWalker(tpl.content, NodeFilter.SHOW_TEXT, null);
        var nodes = [], n;
        while ((n = walker.nextNode())) {
            var p = n.parentNode;
            if (p && (p.nodeName === 'STYLE' || p.nodeName === 'SCRIPT')) continue;
            if (n.nodeValue.indexOf('{{') !== -1) nodes.push(n);
        }
        nodes.forEach(function (node) {
            var text = node.nodeValue;
            var frag = document.createDocumentFragment();
            var last = 0, m;
            TOKEN_RE.lastIndex = 0;
            while ((m = TOKEN_RE.exec(text)) !== null) {
                if (m.index > last) frag.appendChild(document.createTextNode(text.slice(last, m.index)));
                frag.appendChild(makeChip(m[0], m[1]));
                last = m.index + m[0].length;
            }
            if (last < text.length) frag.appendChild(document.createTextNode(text.slice(last)));
            node.parentNode.replaceChild(frag, node);
        });
        return tpl.content;
    }

    // Vuelve a convertir los chips en sus tokens originales y devuelve el HTML final.
    function serializeVisual() {
        var clone = mailContent.cloneNode(true);
        Array.prototype.slice.call(clone.querySelectorAll('.var-token')).forEach(function (el) {
            el.parentNode.replaceChild(document.createTextNode(el.getAttribute('data-raw') || ''), el);
        });
        // Duplicar la alineación como atributo align: clientes de correo viejos
        // (Outlook clásico) ignoran text-align CSS pero sí respetan align="".
        Array.prototype.slice.call(clone.querySelectorAll('*')).forEach(function (el) {
            var ta = el.style && el.style.textAlign;
            if (ta) el.setAttribute('align', ta);
        });
        return clone.innerHTML;
    }

    /* ═══════════ Estado y render de paneles ═══════════ */
    var currentIsFullHtml = false;
    var templateLoaded = false;

    function isFullHtmlTpl(html) {
        return (html || '').toLowerCase().indexOf('<html') !== -1;
    }

    function updateSubjectBar() {
        var subj = interpolateSubject($('#f_subject').val());
        $('#previewSubject').text(subj || '—');
    }

    function renderVisual(rawHtml) {
        updateSubjectBar();
        $('#emptyState').hide();
        currentIsFullHtml = isFullHtmlTpl(rawHtml);

        if (currentIsFullHtml) {
            // Documento completo → vista previa exacta de solo lectura
            $('#mailCanvas, #mtToolbar').hide();
            $('#fullHtmlNotice').show();
            var frame = document.getElementById('previewFrame');
            $(frame).show();
            frame.srcdoc = buildEmailDoc(rawHtml, interpolateSubject($('#f_subject').val()));
        } else {
            $('#fullHtmlNotice, #previewFrame').hide();
            $('#mtToolbar, #mailCanvas').show();
            mailContent.innerHTML = '';
            mailContent.appendChild(renderTokensAsChips(rawHtml));
            mailContent.setAttribute('contenteditable', 'true');
        }
    }

    function showEmpty() {
        templateLoaded = false;
        $('#mailCanvas, #mtToolbar, #fullHtmlNotice, #previewFrame').hide();
        $('#emptyState').show();
        $('#previewSubject').text('—');
    }

    // Ajustar altura del iframe (solo plantillas full-HTML)
    document.getElementById('previewFrame').addEventListener('load', function () {
        try {
            var doc = this.contentDocument;
            if (doc && doc.documentElement) {
                var h = Math.max(420, doc.documentElement.scrollHeight + 20);
                this.style.height = Math.min(h, 1400) + 'px';
            }
        } catch (e) { /* sandbox */ }
    });

    /* ═══════════ Pestañas Editor / Código ═══════════ */
    var visualActive = true;

    function syncCodeFromVisual() {
        if (templateLoaded && !currentIsFullHtml) {
            $('#f_html_code').val(serializeVisual());
        }
    }

    $('#tabVisual').on('click', function () {
        if (visualActive) return;
        visualActive = true;
        $('#tabVisual').addClass('active');
        $('#tabCode').removeClass('active');
        if (templateLoaded) renderVisual($('#f_html_code').val());
        $('#visualPane').show();
        $('#codePane').hide();
    });

    $('#tabCode').on('click', function () {
        if (!visualActive) return;
        visualActive = false;
        syncCodeFromVisual();
        $('#tabCode').addClass('active');
        $('#tabVisual').removeClass('active');
        $('#codePane').show();
        $('#visualPane').hide();
    });

    /* ═══════════ Barra de herramientas ═══════════ */
    // Selección guardada: los prompts y los selectores de color roban el foco,
    // así que recordamos el último rango dentro del área editable y lo
    // restauramos antes de aplicar cada comando.
    var savedRange = null;
    document.addEventListener('selectionchange', function () {
        var sel = document.getSelection();
        if (sel && sel.rangeCount && mailContent.contains(sel.anchorNode)) {
            savedRange = sel.getRangeAt(0).cloneRange();
            markSelectedChip(selectedChip());
        }
    });

    function restoreSelection() {
        mailContent.focus();
        if (savedRange) {
            var sel = document.getSelection();
            sel.removeAllRanges();
            sel.addRange(savedRange);
        }
    }

    /* ── Formato sobre chips de variables ──
       Los chips son contenteditable=false, así que execCommand los ignora.
       Un clic los selecciona completos y los comandos los envuelven en el
       estilo pedido (<strong>, <span style="...">), que es exactamente como
       se estiliza una variable en la plantilla real. */
    function selectedChip() {
        if (!savedRange) return null;
        var node = savedRange.commonAncestorContainer;
        if (node.nodeType !== 1) return null;
        if (node.classList && node.classList.contains('var-token')) return node;
        if (savedRange.endOffset - savedRange.startOffset === 1) {
            var child = node.childNodes[savedRange.startOffset];
            if (child && child.nodeType === 1 && child.classList && child.classList.contains('var-token')) {
                return child;
            }
        }
        return null;
    }

    function markSelectedChip(chip) {
        Array.prototype.slice.call(mailContent.querySelectorAll('.var-token.is-selected')).forEach(function (el) {
            if (el !== chip) el.classList.remove('is-selected');
        });
        if (chip) chip.classList.add('is-selected');
    }

    function reselectChip(chip) {
        mailContent.focus();
        var r = document.createRange();
        r.selectNode(chip);
        var sel = document.getSelection();
        sel.removeAllRanges();
        sel.addRange(r);
        savedRange = r.cloneRange();
        markSelectedChip(chip);
    }

    // Envoltorio de estilo reutilizable inmediatamente alrededor del chip
    function styleWrapOf(chip) {
        var p = chip.parentNode;
        if (p && p !== mailContent && p.nodeName === 'SPAN' && p.childNodes.length === 1 &&
            !(p.classList && (p.classList.contains('var-token') || p.classList.contains('info-box') || p.classList.contains('nota')))) {
            return p;
        }
        var span = document.createElement('span');
        chip.parentNode.insertBefore(span, chip);
        span.appendChild(chip);
        return span;
    }

    function findWrapOf(chip, names) {
        var el = chip.parentNode;
        while (el && el !== mailContent && el.childNodes.length === 1) {
            if (names.indexOf(el.nodeName) !== -1) return el;
            el = el.parentNode;
        }
        return null;
    }

    function toggleWrapOf(chip, namesCsv, tag) {
        var w = findWrapOf(chip, namesCsv.split(','));
        if (w) {
            while (w.firstChild) w.parentNode.insertBefore(w.firstChild, w);
            w.parentNode.removeChild(w);
        } else {
            var el = document.createElement(tag);
            chip.parentNode.insertBefore(el, chip);
            el.appendChild(chip);
        }
    }

    function setBlockAlignOf(node, align) {
        var el = node;
        while (el && el !== mailContent && !/^(P|H1|H2|H3|H4|H5|H6|DIV|LI|BLOCKQUOTE)$/.test(el.nodeName)) {
            el = el.parentNode;
        }
        if (el && el !== mailContent) {
            el.style.textAlign = align;
        } else {
            // El chip está suelto en la raíz: envolver su cadena en un párrafo
            var top = node;
            while (top.parentNode && top.parentNode !== mailContent) top = top.parentNode;
            var p = document.createElement('p');
            mailContent.insertBefore(p, top);
            p.appendChild(top);
            p.style.textAlign = align;
        }
    }

    function clearFormatOf(chip) {
        var el = chip.parentNode;
        while (el && el !== mailContent && el.childNodes.length === 1 &&
            /^(SPAN|STRONG|B|EM|I|U|S|STRIKE|DEL|FONT)$/.test(el.nodeName) &&
            !(el.classList && el.classList.contains('var-token'))) {
            var parent = el.parentNode;
            parent.insertBefore(chip, el);
            parent.removeChild(el);
            el = chip.parentNode;
        }
    }

    function applyToChip(cmd, val, chip) {
        switch (cmd) {
            case 'bold': toggleWrapOf(chip, 'STRONG,B', 'strong'); return true;
            case 'italic': toggleWrapOf(chip, 'EM,I', 'em'); return true;
            case 'underline': toggleWrapOf(chip, 'U', 'u'); return true;
            case 'strikeThrough': toggleWrapOf(chip, 'S,STRIKE,DEL', 's'); return true;
            case 'foreColor': styleWrapOf(chip).style.color = val; return true;
            case 'hiliteColor': styleWrapOf(chip).style.backgroundColor = val; return true;
            case 'justifyLeft': setBlockAlignOf(chip, 'left'); return true;
            case 'justifyCenter': setBlockAlignOf(chip, 'center'); return true;
            case 'justifyRight': setBlockAlignOf(chip, 'right'); return true;
            case 'removeFormat': clearFormatOf(chip); return true;
            default: return false; // undo/redo/listas/etc. → flujo normal
        }
    }

    function exec(cmd, val, withCss) {
        var chip = selectedChip();
        if (chip && applyToChip(cmd, val, chip)) {
            reselectChip(chip);
            return;
        }
        restoreSelection();
        if (withCss) { try { document.execCommand('styleWithCSS', false, true); } catch (e) {} }
        document.execCommand(cmd, false, val || null);
        if (withCss) { try { document.execCommand('styleWithCSS', false, false); } catch (e) {} }
    }

    // Clic sobre un chip → seleccionarlo completo para poder darle formato
    $(document).on('click', '#mailContent .var-token', function (e) {
        e.preventDefault();
        reselectChip(this);
    });

    // mousedown + preventDefault: no robar la selección del área editable
    $('#mtToolbar').on('mousedown', 'button', function (e) {
        e.preventDefault();
    });

    $('#mtToolbar').on('click', '.tb[data-cmd]', function () {
        exec($(this).data('cmd'));
    });

    $('#mtBlock').on('change', function () {
        exec('formatBlock', '<' + this.value + '>');
    });

    // Tamaño de letra: execCommand solo maneja tamaños 1-7, así que aplicamos
    // el 7 como marcador y lo convertimos a un estilo en píxeles (válido en correos).
    $('#mtFontSize').on('change', function () {
        var px = this.value;
        this.selectedIndex = 0;
        if (!px) return;
        var chip = selectedChip();
        if (chip) {
            styleWrapOf(chip).style.fontSize = px;
            reselectChip(chip);
            return;
        }
        exec('fontSize', '7');
        Array.prototype.slice.call(mailContent.querySelectorAll('font[size="7"]')).forEach(function (f) {
            f.removeAttribute('size');
            f.style.fontSize = px;
        });
    });

    // Colores: styleWithCSS genera style="color: ..." en lugar de <font>, más fiable en correos
    $('#colorFore').on('input change', function () {
        exec('foreColor', this.value, true);
    });
    $('#colorBack').on('input change', function () {
        exec('hiliteColor', this.value, true);
    });

    // Estilos propios del correo (clases de email.css)
    function wrapSelectionWith(cls) {
        restoreSelection();
        var sel = document.getSelection();
        if (!sel || !sel.rangeCount || sel.isCollapsed) {
            showAlert('warning', 'Primero selecciona el texto al que quieres aplicar el estilo.');
            return;
        }
        var range = sel.getRangeAt(0);
        var span = document.createElement('span');
        span.className = cls;
        try {
            range.surroundContents(span);
        } catch (e) {
            // La selección cruza varios elementos: extraerla y envolverla
            span.appendChild(range.extractContents());
            range.insertNode(span);
        }
        sel.removeAllRanges();
        savedRange = null;
    }

    $('#tbInfoBox').on('click', function () { wrapSelectionWith('info-box'); });
    $('#tbNota').on('click', function () { wrapSelectionWith('nota'); });

    $('#tbLink').on('click', function () {
        var url = window.prompt('Dirección del enlace (URL):', 'https://');
        if (!url) return;
        restoreSelection();
        var sel = window.getSelection();
        if (sel && !sel.isCollapsed) {
            document.execCommand('createLink', false, url);
        } else {
            document.execCommand('insertHTML', false, '<a href="' + escapeHtml(url) + '">' + escapeHtml(url) + '</a>');
        }
    });

    $('#tbButton').on('click', function () {
        var texto = window.prompt('Texto del botón:', 'Ir al sistema');
        if (!texto) return;
        var url = window.prompt('Dirección a la que llevará el botón (URL):', 'https://servicioypracticas.unimontrer.edu.mx/');
        if (!url) return;
        restoreSelection();
        // Misma clase .button que estiliza email.css en el correo real
        document.execCommand('insertHTML', false,
            '<p style="text-align:center;"><a class="button" href="' + escapeHtml(url) + '">' + escapeHtml(texto) + '</a></p>');
    });

    /* ═══════════ Chips del panel lateral ═══════════ */
    function renderVarChips(subject, html) {
        var found = {};
        var source = (subject || '') + ' ' + (html || '');
        var m;
        TOKEN_RE.lastIndex = 0;
        while ((m = TOKEN_RE.exec(source)) !== null) {
            if (m[1] !== 'css') found[m[1]] = m[0];
        }
        var wrap = $('#varsWrap').empty();
        var names = Object.keys(found).sort();
        if (!names.length) {
            wrap.append('<small class="text-muted">Esta plantilla no usa datos automáticos.</small>');
            return;
        }
        names.forEach(function (nm) {
            var label = VAR_LABELS[nm] || nm;
            wrap.append(
                $('<code class="k"></code>')
                    .attr('hover', 'Insertar: ' + found[nm])
                    .attr('data-token', found[nm])
                    .attr('data-name', nm)
                    .text(label)
            );
        });
    }

    // Insertar en el último punto enfocado (editor visual, código o asunto)
    var lastFocused = null;
    $(document).on('focusin', '#f_html_code, #f_subject, #mailContent', function () { lastFocused = this; });

    $(document).on('click', 'code.k', function () {
        var token = $(this).attr('data-token') || $(this).text();
        var name = $(this).attr('data-name');
        if (!name) {
            var mm = /\{\{\{?\s*([a-zA-Z0-9_]+)\s*\}?\}\}/.exec(token);
            name = mm ? mm[1] : token;
        }

        if (lastFocused === mailContent && templateLoaded && !currentIsFullHtml) {
            restoreSelection();
            document.execCommand('insertHTML', false, makeChip(token, name).outerHTML + '&nbsp;');
        } else if (lastFocused && lastFocused !== mailContent && !lastFocused.disabled) {
            var el = lastFocused;
            var start = el.selectionStart != null ? el.selectionStart : el.value.length;
            var end = el.selectionEnd != null ? el.selectionEnd : el.value.length;
            el.value = el.value.slice(0, start) + token + el.value.slice(end);
            el.selectionStart = el.selectionEnd = start + token.length;
            el.focus();
            if (el.id === 'f_subject') updateSubjectBar();
        } else if (navigator.clipboard) {
            var chip = $(this);
            navigator.clipboard.writeText(token).then(function () {
                chip.addClass('copied');
                setTimeout(function () { chip.removeClass('copied'); }, 1200);
            });
        }
    });

    /* ═══════════ Correo de prueba ═══════════ */
    var TEST_BTN_HTML = '<i class="fa-solid fa-paper-plane me-1"></i>Enviar correo de prueba';

    // HTML actual del editor, esté en la pestaña que esté
    function currentRawHtml() {
        if (visualActive && templateLoaded && !currentIsFullHtml) {
            return serializeVisual();
        }
        return $('#f_html_code').val();
    }

    function sendTestMail(to) {
        if (!templateLoaded) {
            showAlert('warning', 'Primero selecciona una plantilla.');
            return;
        }
        var subj = interpolateSubject($('#f_subject').val().trim());
        var doc = buildEmailDoc(currentRawHtml(), subj);
        var btn = $('#btnTest');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Enviando…');
        $.ajax({
            url: 'controller/ajax/mail_templates.php',
            method: 'POST',
            dataType: 'json',
            data: { action: 'send_test', to: to, subject: subj, html: doc, csrf_token: CSRF }
        }).done(function (r) {
            showAlert(r.success ? 'success' : 'danger', r.message || (r.success ? 'Correo de prueba enviado.' : 'No se pudo enviar el correo de prueba.'));
        }).fail(function () {
            showAlert('danger', 'No se pudo enviar el correo de prueba.');
        }).always(function () {
            btn.html(TEST_BTN_HTML).prop('disabled', !templateLoaded);
        });
    }

    var testModal = null;
    $('#btnTest').on('click', function () {
        var last = '';
        try { last = localStorage.getItem('mt_test_email') || ''; } catch (e) {}
        $('#testMailTo').val(last || USER_EMAIL).removeClass('is-invalid');
        if (window.bootstrap && window.bootstrap.Modal) {
            testModal = window.bootstrap.Modal.getOrCreateInstance(document.getElementById('testMailModal'));
            testModal.show();
            setTimeout(function () { document.getElementById('testMailTo').focus(); }, 350);
        } else {
            // Respaldo si Bootstrap JS no está disponible
            var addr = window.prompt('¿A qué correo enviamos la prueba?', last || USER_EMAIL);
            if (addr) confirmTestSend(addr);
        }
    });

    function confirmTestSend(to) {
        to = (to || '').trim();
        if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(to)) {
            $('#testMailTo').addClass('is-invalid');
            return false;
        }
        try { localStorage.setItem('mt_test_email', to); } catch (e) {}
        if (testModal) testModal.hide();
        sendTestMail(to);
        return true;
    }

    $('#btnTestSendConfirm').on('click', function () {
        confirmTestSend($('#testMailTo').val());
    });

    $('#testMailTo').on('input', function () {
        $(this).removeClass('is-invalid');
    }).on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            confirmTestSend(this.value);
        }
    });

    /* ═══════════ Carga / guardado ═══════════ */
    function loadTemplates() {
        $.ajax({
            url: 'controller/ajax/mail_templates.php',
            method: 'POST',
            data: { action: 'list' },
            dataType: 'json'
        }).done(function (response) {
            if (response.success) {
                var tplSelect = $('#tplSelect');
                tplSelect.empty().append('<option value="">— Selecciona una plantilla —</option>');
                response.templates.forEach(function (tpl) {
                    tplSelect.append($('<option></option>').val(tpl.id).text(tpl.name));
                });
                tplSelect.prop('disabled', false);
            } else {
                showAlert('danger', 'Error al cargar las plantillas.');
            }
        }).fail(function () {
            showAlert('danger', 'Error al cargar las plantillas.');
        });
    }

    function loadTemplateDetails(tplId) {
        $.ajax({
            url: 'controller/ajax/mail_templates.php',
            method: 'POST',
            data: { action: 'get', id: tplId },
            dataType: 'json'
        }).done(function (response) {
            if (response.success) {
                var tpl = response.template;
                templateLoaded = true;
                $('#f_subject').val(tpl.subject).prop('disabled', false);
                $('#f_html_code').val(tpl.html).prop('disabled', false);
                $('#btnSave').prop('disabled', false);
                $('#btnReload').prop('disabled', false);
                $('#btnTest').prop('disabled', false);
                renderVarChips(tpl.subject, tpl.html);
                if (visualActive) renderVisual(tpl.html);
            } else {
                showAlert('danger', 'Error al cargar los detalles de la plantilla.');
            }
        }).fail(function () {
            showAlert('danger', 'Error al cargar los detalles de la plantilla.');
        });
    }

    function saveTemplate(tplId, subject, html) {
        $.ajax({
            url: 'controller/ajax/mail_templates.php',
            method: 'POST',
            data: { action: 'save', id: tplId, subject: subject, html: html, csrf_token: '<?= htmlspecialchars($csrf) ?>' },
            dataType: 'json'
        }).done(function (response) {
            if (response.success) {
                showAlert('success', 'Plantilla guardada con éxito.');
            } else {
                showAlert('danger', 'Error al guardar la plantilla.');
            }
        }).fail(function () {
            showAlert('danger', 'Error al guardar la plantilla.');
        });
    }

    function showAlert(type, msg) {
        $('#alertBox').html(
            '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
            msg +
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>' +
            '</div>'
        );
        setTimeout(function () { $('#alertBox').html(''); }, 3500);
    }

    /* ═══════════ Init ═══════════ */
    $(function () {
        loadTemplates();
        $('#btnSave').prop('disabled', true);
        $('#btnReload').prop('disabled', true);
        $('#btnTest').prop('disabled', true);
        showEmpty();

        $('#tplSelect').on('change', function () {
            var tplId = $(this).val();
            if (tplId) {
                loadTemplateDetails(tplId);
            } else {
                $('#f_subject').val('').prop('disabled', true);
                $('#f_html_code').val('').prop('disabled', true);
                $('#btnSave').prop('disabled', true);
                $('#btnReload').prop('disabled', true);
                $('#btnTest').prop('disabled', true);
                $('#varsWrap').empty();
                showEmpty();
            }
        });

        $('#f_subject').on('input', updateSubjectBar);

        $('#btnReload').on('click', function () {
            var tplId = $('#tplSelect').val();
            if (tplId) loadTemplateDetails(tplId);
        });

        $('#btnSave').on('click', function () {
            var tplId = $('#tplSelect').val();
            var subject = $('#f_subject').val().trim();
            if (visualActive) syncCodeFromVisual();
            var html = $('#f_html_code').val().trim();
            if (tplId && subject && html) {
                saveTemplate(tplId, subject, html);
            } else {
                showAlert('warning', 'Asunto y contenido no pueden estar vacíos.');
            }
        });
    });
})();
</script>
