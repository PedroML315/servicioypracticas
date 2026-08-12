<?php
// view/pages/configs/mail_bulk.php — Gestor de envío masivo de correos
// Módulo aislado: solo usa sus propios endpoints (controller/ajax/mail_bulk/*.php)
// y su propia configuración de correo. No comparte nada con las plantillas
// automáticas del sistema ni con la cola de correo transaccional.

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];
$currentUserEmail = htmlspecialchars($_SESSION['user']['email'] ?? '', ENT_QUOTES, 'UTF-8');
?>
<meta name="csrf-token" content="<?= htmlspecialchars($csrf) ?>">

<!-- CKEditor5 auto-hospedado (build "super-build" 39.0.0 + traducción al español).
     Se sirve desde el propio dominio a propósito: el build "classic" del CDN no
     incluye subrayado/tachado/alineación/separador/quitar-formato, y cargarlo desde
     cdn.ckeditor.com chocaba con la Content-Security-Policy del sitio y con la
     prevención de rastreo del navegador. Expone window.CKEDITOR.ClassicEditor
     (no window.ClassicEditor, como el build classic). -->
<script src="view/assets/libs/ckeditor5/ckeditor.js"></script>
<script src="view/assets/libs/ckeditor5/translations/es.js"></script>

<style>
    .mb-steps {
        display: flex;
        gap: 8px;
        margin-bottom: 18px;
        flex-wrap: wrap;
    }

    .mb-step {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 9px 16px;
        border-radius: 10px;
        border: 1px solid #e6eaf0;
        background: #fff;
        font-size: .84rem;
        font-weight: 600;
        color: #94a3b8;
        cursor: pointer;
        user-select: none;
    }

    .mb-step .num {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        background: #f1f5f9;
        color: #64748b;
        font-size: .74rem;
    }

    .mb-step.active { border-color: #16a34a; color: #14532d; background: #ecfdf5; }
    .mb-step.active .num { background: #16a34a; color: #fff; }
    .mb-step.done .num { background: #16a34a; color: #fff; }
    .mb-step.done { color: #15803d; }

    .mb-panel { display: none; }
    .mb-panel.active { display: block; }

    .mb-toolbar {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
        margin-bottom: 12px;
    }

    .mb-toolbar .mb-count {
        margin-left: auto;
        font-size: .82rem;
        color: #64748b;
        font-weight: 600;
    }

    #mbRecipientsTable { width: 100% !important; font-size: .84rem; }

    .mb-quickadd {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr auto;
        gap: 8px;
        margin-bottom: 14px;
    }

    @media (max-width: 900px) { .mb-quickadd { grid-template-columns: 1fr; } }

    .mb-dropzone {
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        padding: 22px;
        text-align: center;
        color: #64748b;
        font-size: .85rem;
        cursor: pointer;
        transition: border-color .15s, background .15s;
    }

    .mb-dropzone.is-drag { border-color: #16a34a; background: #f0fdf4; }

    .mb-attachment-list { margin-top: 10px; display: flex; flex-direction: column; gap: 6px; }

    .mb-attachment {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 7px 10px;
        border: 1px solid #e6eaf0;
        border-radius: 8px;
        font-size: .82rem;
        background: #fff;
    }

    .mb-attachment .name { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .mb-attachment .size { color: #94a3b8; font-size: .76rem; }

    .mb-var-chip {
        display: inline-block;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #15803d;
        border-radius: 6px;
        padding: 2px 8px;
        font-family: monospace;
        font-size: .78rem;
        cursor: pointer;
        margin-right: 4px;
    }

    #mbEditorWrap .ck-editor__editable { min-height: 260px; }

    /* ── Editor visual / Código HTML ── */
    .mb-viewtabs {
        display: inline-flex;
        border: 1px solid #d7dee7;
        border-radius: 9px;
        overflow: hidden;
    }

    .mb-viewtabs button {
        border: 0;
        background: #fff;
        padding: 7px 14px;
        font-size: .78rem;
        font-weight: 600;
        color: #334155;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .mb-viewtabs button + button { border-left: 1px solid #d7dee7; }
    .mb-viewtabs button:hover:not(.active) { background: #f5f7fa; }
    .mb-viewtabs button.active { background: #111827; color: #fff; }
    .mb-viewtabs button:disabled { opacity: .45; cursor: not-allowed; }

    #mbEditorCode {
        width: 100%;
        min-height: 340px;
        border: 1px solid #d7dee7;
        border-radius: 9px;
        padding: 12px 14px;
        font-family: "Fira Code", Consolas, Monaco, monospace;
        font-size: .8rem;
        line-height: 1.55;
        color: #0f172a;
        background: #f8fafc;
        white-space: pre;
        overflow: auto;
        tab-size: 2;
        resize: vertical;
    }

    #mbEditorCode:focus {
        outline: none;
        border-color: #16a34a;
        box-shadow: 0 0 0 3px rgba(22, 163, 74, .18);
    }

    .mb-code-help {
        margin-top: 10px;
        font-size: .78rem;
        color: #64748b;
        line-height: 1.55;
    }

    .mb-code-help ul { padding-left: 18px; margin-bottom: 0; }
    .mb-code-help li + li { margin-top: 4px; }
    .mb-code-help code {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #15803d;
        padding: 1px 5px;
        border-radius: 5px;
        font-size: .75rem;
    }

    /* ── Diseño institucional sí/no ── */
    .mb-layout-box {
        margin-top: 16px;
        padding: 13px 15px;
        border: 1px solid #e6eaf0;
        border-radius: 10px;
        background: #fbfdfc;
    }

    .mb-layout-box.is-locked { opacity: .65; }

    .mb-layout-check {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        margin: 0;
        cursor: pointer;
        font-size: .84rem;
    }

    .mb-layout-check input { margin-top: 3px; flex: 0 0 auto; }
    .mb-layout-check strong { display: block; color: #0f172a; }

    .mb-layout-desc {
        display: block;
        font-size: .78rem;
        color: #64748b;
        line-height: 1.5;
        margin-top: 2px;
    }

    .mb-summary-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eef2f7; font-size: .87rem; }
    .mb-summary-row:last-child { border-bottom: 0; }
    .mb-summary-row .lbl { color: #64748b; }
    .mb-summary-row .val { font-weight: 600; text-align: right; }

    #mbPreviewFrame { width: 100%; min-height: 480px; border: 1px solid #e6eaf0; border-radius: 10px; background: #f8fafc; }

    .mb-preview-note {
        display: flex;
        align-items: flex-start;
        gap: 7px;
        font-size: .78rem;
        color: #15803d;
        margin: 0 0 10px;
    }

    .mb-preview-note i { margin-top: 2px; }
    .mb-preview-note.is-approx { color: #b45309; }

    .mb-progress-wrap { margin: 18px 0; }
    .mb-progress-bar-bg { height: 14px; border-radius: 7px; background: #eef2f7; overflow: hidden; }
    .mb-progress-bar-fg { height: 100%; background: #16a34a; width: 0%; transition: width .3s; }
    .mb-progress-counts { display: flex; gap: 18px; margin-top: 10px; font-size: .85rem; }
    .mb-progress-counts b { font-weight: 700; }

    .mb-result-table { width: 100%; font-size: .82rem; }
    .mb-result-table th, .mb-result-table td { padding: 6px 8px; border-bottom: 1px solid #eef2f7; text-align: left; }

    .mb-history-row { cursor: pointer; }
    .mb-history-row:hover { background: #f8fafc; }

    .mb-status-badge { display: inline-block; padding: 2px 9px; border-radius: 999px; font-size: .72rem; font-weight: 700; }
    .mb-status-badge.completed { background: #ecfdf5; color: #15803d; }
    .mb-status-badge.completed_with_errors { background: #fffbeb; color: #b45309; }
    .mb-status-badge.failed { background: #fef2f2; color: #b91c1c; }
    .mb-status-badge.sending, .mb-status-badge.queued { background: #eff6ff; color: #1d4ed8; }

    /* ═══ Modales: misma imagen visual que el resto de Configuraciones ═══ */
    .mb-modal .modal-dialog { --bs-modal-margin: 1.75rem; }

    .mb-modal .modal-content {
        border: none;
        border-radius: 16px;
        box-shadow: 0 24px 64px rgba(15, 23, 42, .22);
        overflow: hidden;
    }

    .mb-modal .modal-header {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        padding: 18px 22px;
        background: linear-gradient(180deg, #fbfdfc, #ffffff);
        border-bottom: 1px solid #e6eaf0;
    }

    .mb-modal-icon {
        flex: 0 0 auto;
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        font-size: 1.05rem;
        background: var(--t-bg, #f1f5f9);
        color: var(--t-fg, #475569);
    }

    .mb-modal .modal-title-wrap { min-width: 0; flex: 1 1 auto; }
    .mb-modal .modal-title { font-size: 1.02rem; font-weight: 700; margin: 0; color: #0f172a; }
    .mb-modal .modal-subtitle { margin: 2px 0 0; font-size: .8rem; color: #64748b; line-height: 1.4; }
    .mb-modal .btn-close { flex: 0 0 auto; margin-top: 3px; }

    .mb-modal .modal-body { padding: 22px; }
    .mb-modal .modal-footer {
        border-top: 1px solid #e6eaf0;
        padding: 14px 22px;
        background: #fbfdfc;
    }

    .mb-modal .form-label { font-size: .78rem; font-weight: 600; color: #334155; margin-bottom: 4px; }
    .mb-modal .form-control, .mb-modal .form-select {
        border-radius: 9px;
        border: 1px solid #d7dee7;
        font-size: .85rem;
        padding: 8px 11px;
        transition: border-color .14s, box-shadow .14s;
    }
    .mb-modal .form-control:focus, .mb-modal .form-select:focus {
        border-color: #16a34a;
        box-shadow: 0 0 0 3px rgba(22, 163, 74, .18);
        outline: none;
    }

    .mb-modal .btn-primary {
        background: #111827;
        border-color: #111827;
        border-radius: 9px;
        font-weight: 600;
        font-size: .82rem;
        padding: 8px 18px;
    }
    .mb-modal .btn-primary:hover { background: #1f2937; border-color: #1f2937; }
    .mb-modal .btn-light {
        border-radius: 9px;
        font-weight: 600;
        font-size: .82rem;
        border: 1px solid #e6eaf0;
        background: #fff;
    }
    .mb-modal .btn-light:hover { background: #f5f7fa; }

    .mb-test-box {
        margin-top: 18px;
        padding: 14px 16px;
        background: #f8fafc;
        border: 1px solid #e6eaf0;
        border-radius: 10px;
    }

    .mb-csv-example {
        background: #f8fafc;
        border: 1px solid #e6eaf0;
        border-radius: 10px;
        padding: 12px 14px;
        font-family: "Fira Code", Consolas, monospace;
        font-size: .78rem;
        color: #334155;
        line-height: 1.6;
        margin: 10px 0 16px;
    }

    .mb-dropzone-link { color: #16a34a; font-weight: 700; text-decoration: underline; }

    .mb-csv-filename {
        display: none;
        align-items: center;
        gap: 8px;
        margin-top: 10px;
        padding: 8px 12px;
        border: 1px solid #e6eaf0;
        border-radius: 8px;
        background: #fff;
        font-size: .84rem;
    }
    .mb-csv-filename.is-shown { display: flex; }
    .mb-csv-filename .name { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 600; }
    .mb-csv-filename .size { color: #94a3b8; font-size: .78rem; }

    .mb-history-table { width: 100%; font-size: .85rem; border-collapse: collapse; }
    .mb-history-table th {
        text-align: left;
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: #94a3b8;
        padding: 8px;
        border-bottom: 1px solid #e6eaf0;
    }
    .mb-history-table td { padding: 10px 8px; border-bottom: 1px solid #eef2f7; }
    .mb-history-empty { color: #94a3b8; font-size: .85rem; padding: 10px 2px; }
</style>

<div id="mbAlert"></div>

<!-- Pasos -->
<div class="mb-steps" role="tablist">
    <div class="mb-step active" data-step="1"><span class="num">1</span> Destinatarios</div>
    <div class="mb-step" data-step="2"><span class="num">2</span> Mensaje</div>
    <div class="mb-step" data-step="3"><span class="num">3</span> Revisar y enviar</div>
</div>

<!-- ══ Paso 1: Destinatarios ══ -->
<section class="mb-panel active" data-panel="1">
    <div class="form-section">
        <h2>Personas que recibirán el correo</h2>

        <div class="mb-quickadd">
            <input type="text" class="form-control" id="mbNewName" placeholder="Nombre">
            <input type="email" class="form-control" id="mbNewEmail" placeholder="Correo electrónico">
            <input type="text" class="form-control" id="mbNewNotes" placeholder="Notas (opcional)">
            <button type="button" class="btn btn-primary" id="mbBtnAddRecipient">Agregar</button>
        </div>

        <div class="mb-toolbar">
            <button type="button" class="btn btn-light" id="mbBtnImportCsv">
                <i class="fa-solid fa-file-csv me-1"></i> Importar desde CSV
            </button>
            <button type="button" class="btn btn-light" id="mbBtnDeleteSelected" disabled>
                <i class="fa-solid fa-trash me-1"></i> Eliminar seleccionados
            </button>
            <span class="mb-count" id="mbSelectedCount">0 seleccionadas</span>
        </div>

        <table id="mbRecipientsTable" class="display">
            <thead>
                <tr>
                    <th></th>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Notas</th>
                    <th></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</section>

<!-- ══ Paso 2: Mensaje ══ -->
<section class="mb-panel" data-panel="2">
    <div class="form-section">
        <h2>Redacciones guardadas</h2>
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <select class="form-select" id="mbSavedMessageSelect" style="max-width:320px">
                <option value="">— Cargar una redacción guardada —</option>
            </select>
            <button type="button" class="btn btn-light" id="mbBtnSaveMessage">Guardar esta redacción</button>
            <button type="button" class="btn btn-light text-danger" id="mbBtnDeleteMessage" disabled>Eliminar</button>
        </div>
    </div>

    <div class="form-section">
        <h2>Asunto</h2>
        <input type="text" class="form-control" id="mbSubject" placeholder="Ej. Información importante sobre tu servicio social">
        <div class="form-text mt-2">
            Puedes personalizar el asunto y el mensaje usando
            <span class="mb-var-chip" data-token="{nombre}">{nombre}</span> y
            <span class="mb-var-chip" data-token="{correo}">{correo}</span>.
            Al enviar, se reemplazan automáticamente por los datos de cada persona.
        </div>
    </div>

    <div class="form-section">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <h2 class="mb-0">Mensaje</h2>
            <div class="mb-viewtabs" role="tablist" aria-label="Modo de edición del mensaje">
                <button type="button" id="mbTabVisual" class="active" role="tab" aria-selected="true">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> Editor visual
                </button>
                <button type="button" id="mbTabCode" role="tab" aria-selected="false">
                    <i class="fa-solid fa-code"></i> Código HTML (avanzado)
                </button>
            </div>
        </div>

        <div id="mbEditorWrap">
            <textarea id="mbEditor"></textarea>
        </div>

        <div id="mbCodeWrap" style="display:none">
            <textarea id="mbEditorCode" spellcheck="false"
                placeholder="Pega aquí el HTML de tu plantilla…"></textarea>
            <div class="mb-code-help">
                <p class="mb-2">
                    <i class="fa-solid fa-circle-info me-1"></i>
                    Aquí puedes pegar una plantilla de correo hecha con HTML —tablas, colores, botones e
                    imágenes—. Se admite tanto un fragmento como un <strong>documento completo</strong>
                    (con <code>&lt;html&gt;</code>, <code>&lt;head&gt;</code> y <code>&lt;style&gt;</code>).
                </p>
                <ul class="mb-0">
                    <li>Las imágenes se insertan con su dirección de internet:
                        <code>&lt;img src="https://…/imagen.jpg"&gt;</code>. Deben estar publicadas en un
                        sitio público, porque quien reciba el correo no tiene sesión en el sistema.</li>
                    <li>Sigues pudiendo usar <code>{nombre}</code> y <code>{correo}</code> dentro del HTML.</li>
                    <li>Al guardar o enviar se revisa el HTML por seguridad: se quitan
                        <code>&lt;script&gt;</code>, los eventos tipo <code>onclick</code> y los enlaces
                        <code>javascript:</code>. El diseño no se toca.</li>
                </ul>
            </div>
        </div>

        <div class="mb-layout-box" id="mbLayoutBox">
            <label class="mb-layout-check">
                <input type="checkbox" id="mbUseLayout" checked>
                <span>
                    <strong>Usar el diseño institucional</strong>
                    <span class="mb-layout-desc" id="mbUseLayoutDesc">
                        Tu mensaje se envía dentro de la plantilla de la Universidad: logo arriba y pie de
                        página con los datos institucionales. Desactívalo si tu HTML ya trae su propio diseño.
                    </span>
                </span>
            </label>
        </div>
    </div>

    <div class="form-section">
        <h2>Archivos adjuntos</h2>
        <div class="mb-dropzone" id="mbDropzone">
            <i class="fa-solid fa-cloud-arrow-up fa-lg mb-2 d-block"></i>
            Arrastra tus archivos aquí o haz clic para seleccionarlos
            <input type="file" id="mbFileInput" multiple hidden>
        </div>
        <div class="mb-attachment-list" id="mbAttachmentList"></div>
    </div>
</section>

<!-- ══ Paso 3: Revisar y enviar ══ -->
<section class="mb-panel" data-panel="3">
    <div class="form-section">
        <h2>Resumen del envío</h2>
        <div class="mb-summary-row"><span class="lbl">Destinatarios</span><span class="val" id="mbSumRecipients">0 personas</span></div>
        <div class="mb-summary-row"><span class="lbl">Asunto</span><span class="val" id="mbSumSubject">—</span></div>
        <div class="mb-summary-row"><span class="lbl">Archivos adjuntos</span><span class="val" id="mbSumAttachments">Ninguno</span></div>
        <div class="mb-summary-row"><span class="lbl">Remitente</span><span class="val" id="mbSumFrom">—</span></div>
    </div>

    <div class="form-section">
        <h2>Nombre del remitente</h2>
        <input type="text" class="form-control" id="mbFromName" placeholder="Ej. Universidad Montrer" style="max-width:360px">
        <div class="form-text mt-2">Este es el nombre que las personas verán como remitente del correo.</div>
    </div>

    <div class="form-section">
        <h2>Vista previa con datos de ejemplo</h2>
        <p class="form-text">{nombre} → Juan Pérez de ejemplo · {correo} → juan.perez@ejemplo.com</p>
        <p class="mb-preview-note" id="mbPreviewNote">
            <i class="fa-solid fa-circle-check"></i>
            Esto es exactamente lo que se enviará: el correo ya armado y revisado por seguridad.
        </p>
        <iframe id="mbPreviewFrame" sandbox="allow-same-origin"></iframe>
    </div>

    <div class="form-section" id="mbSendSection">
        <h2>Enviar</h2>
        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-light" id="mbBtnSendTest">
                <i class="fa-regular fa-paper-plane me-1"></i> Enviar correo de prueba
            </button>
            <button type="button" class="btn btn-success" id="mbBtnConfirmSend">
                <i class="fa-solid fa-paper-plane me-1"></i> Enviar a <span id="mbBtnSendCount">0</span> personas
            </button>
        </div>
    </div>

    <!-- Progreso del envío -->
    <div class="form-section mb-progress-wrap" id="mbProgressSection" style="display:none">
        <h2>Enviando correos…</h2>
        <div class="mb-progress-bar-bg"><div class="mb-progress-bar-fg" id="mbProgressBar"></div></div>
        <div class="mb-progress-counts">
            <span>✓ Enviados: <b id="mbProgressSent">0</b></span>
            <span>✕ Con error: <b id="mbProgressFailed">0</b></span>
            <span>⏳ Pendientes: <b id="mbProgressPending">0</b></span>
        </div>
    </div>

    <!-- Resultado -->
    <div class="form-section" id="mbResultSection" style="display:none">
        <h2>Envío terminado</h2>
        <p id="mbResultSummary"></p>
        <div id="mbResultFailedWrap" style="display:none">
            <strong>Personas que no recibieron el correo:</strong>
            <table class="mb-result-table mt-2">
                <thead><tr><th>Nombre</th><th>Correo</th><th>Motivo</th></tr></thead>
                <tbody id="mbResultFailedBody"></tbody>
            </table>
        </div>
    </div>
</section>

<!-- ══ Panel fijo: configuración, ayuda e historial ══ -->
<aside class="fixed-rail" aria-label="Panel fijo de configuración, ayuda e historial">
    <div class="vars-fixed card">
        <div class="card-body">
            <p class="text-muted mb-2" style="font-size:.78rem">
                <i class="fa-solid fa-circle-info me-1"></i>
                Este envío masivo usa su <strong>propio servidor de correo</strong>, distinto del que usa el resto del sistema.
            </p>
            <button type="button" class="btn btn-light w-100 mb-2" id="mbBtnOpenSmtp">
                <i class="fa-solid fa-server me-2"></i>Configurar servidor de correo
            </button>
            <button type="button" class="btn btn-light w-100" id="mbBtnOpenHistory">
                <i class="fa-solid fa-clock-rotate-left me-2"></i>Historial de envíos
            </button>
        </div>
    </div>

    <div class="sticky-actions d-flex flex-wrap gap-2">
        <button class="btn btn-light" id="mbBtnPrev">Anterior</button>
        <button class="btn btn-primary" id="mbBtnNext">Siguiente</button>
    </div>
</aside>

<!-- ══ Modal: configuración del correo (SMTP propio) ══ -->
<div class="modal fade mb-modal" id="mbSmtpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div class="mb-modal-icon tone-blue"><i class="fa-solid fa-server"></i></div>
                <div class="modal-title-wrap">
                    <h5 class="modal-title">Configuración del correo</h5>
                    <p class="modal-subtitle">Servidor propio de este módulo — no afecta al resto del sistema.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="mbSmtpAlert"></div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Correo que aparecerá como remitente</label>
                        <input type="email" class="form-control" id="mbSmtpFromEmail" placeholder="administracion@unimontrer.edu.mx">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nombre del remitente</label>
                        <input type="text" class="form-control" id="mbSmtpFromName" placeholder="Universidad Montrer">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Servidor de correo</label>
                        <input type="text" class="form-control" id="mbSmtpHost" placeholder="smtp.example.com">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Puerto</label>
                        <input type="number" class="form-control" id="mbSmtpPort" placeholder="587">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Seguridad de conexión</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="mbSmtpEnc" id="mbEncNone" value="none">
                                <label class="form-check-label" for="mbEncNone">Ninguna</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="mbSmtpEnc" id="mbEncTls" value="tls" checked>
                                <label class="form-check-label" for="mbEncTls">TLS</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="mbSmtpEnc" id="mbEncSsl" value="ssl">
                                <label class="form-check-label" for="mbEncSsl">SSL</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Usuario</label>
                        <input type="text" class="form-control" id="mbSmtpUsername" placeholder="administracion@unimontrer.edu.mx">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="mbSmtpPassword" placeholder="•••••••••••••••">
                        <div class="form-text" id="mbSmtpPasswordHint"></div>
                    </div>
                </div>

                <div class="mb-test-box">
                    <label class="form-label mb-2">Probar configuración</label>
                    <div class="d-flex gap-2">
                        <input type="email" class="form-control" id="mbSmtpTestTo" placeholder="correo@ejemplo.com" value="<?= $currentUserEmail ?>">
                        <button type="button" class="btn btn-light text-nowrap" id="mbBtnSmtpTest">Enviar prueba</button>
                    </div>
                    <p class="form-text mb-0 mt-2">Comprueba que la configuración funciona antes de guardarla.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="mbBtnSmtpSave">Guardar configuración</button>
            </div>
        </div>
    </div>
</div>

<!-- ══ Modal: importar CSV ══ -->
<div class="modal fade mb-modal" id="mbCsvModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="mb-modal-icon tone-green"><i class="fa-solid fa-file-csv"></i></div>
                <div class="modal-title-wrap">
                    <h5 class="modal-title">Importar destinatarios</h5>
                    <p class="modal-subtitle">Agrega muchas personas a la vez desde un archivo CSV.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" style="font-size:.85rem;color:#334155">
                    El archivo debe tener las columnas <strong>Nombre, Correo</strong>. Ejemplo:
                </p>
                <div class="mb-csv-example">Juan Pérez,juan@example.com<br>Ana López,ana@example.com</div>

                <div class="mb-dropzone" id="mbCsvDropzone">
                    <i class="fa-solid fa-cloud-arrow-up fa-lg mb-2 d-block"></i>
                    Arrastra tu archivo CSV aquí o <span class="mb-dropzone-link">selecciona un archivo</span>
                    <input type="file" id="mbCsvFile" accept=".csv" hidden>
                </div>

                <div class="mb-csv-filename" id="mbCsvFileName">
                    <i class="fa-regular fa-file-lines"></i>
                    <span class="name" id="mbCsvFileNameText"></span>
                    <span class="size" id="mbCsvFileSizeText"></span>
                    <button type="button" class="btn btn-sm btn-light text-danger" id="mbCsvFileClear">Quitar</button>
                </div>

                <div id="mbCsvResult" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="mbBtnCsvUpload">Importar</button>
            </div>
        </div>
    </div>
</div>

<!-- ══ Modal: historial de envíos ══ -->
<div class="modal fade mb-modal" id="mbHistoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div class="mb-modal-icon tone-slate"><i class="fa-solid fa-clock-rotate-left"></i></div>
                <div class="modal-title-wrap">
                    <h5 class="modal-title">Historial de envíos</h5>
                    <p class="modal-subtitle">Envíos masivos realizados anteriormente.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="mbHistoryList"></div>
                <div id="mbHistoryDetail" style="display:none"></div>
            </div>
        </div>
    </div>
</div>

<script>
    window.MB_CONFIG = {
        csrf: <?= json_encode($csrf) ?>,
        userEmail: <?= json_encode($_SESSION['user']['email'] ?? '') ?>,
        emailCss: <?= json_encode(is_file(__DIR__ . '/../../assets/css/email_templates/email.css') ? file_get_contents(__DIR__ . '/../../assets/css/email_templates/email.css') : '') ?>,
        endpoints: {
            smtp: 'controller/ajax/mail_bulk/smtp_config.php',
            recipients: 'controller/ajax/mail_bulk/recipients.php',
            messages: 'controller/ajax/mail_bulk/messages.php',
            campaigns: 'controller/ajax/mail_bulk/campaigns.php',
            upload: 'controller/ajax/mail_bulk/upload_attachment.php'
        }
    };
</script>
<script src="view/assets/js/configs/mail-bulk.js"></script>
