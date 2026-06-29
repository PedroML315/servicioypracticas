<?php
// admin/email-templates-editor.php

// CSRF para incrustar en meta y usarlo por AJAX
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];
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

    /* Quill */
    #html_editor .ql-container {
        border: none;
    }

    #html_editor .ql-toolbar {
        border: 1px solid #dee2e6;
        border-radius: .375rem .375rem 0 0;
    }

    #html_editor .ql-container.ql-snow {
        border: 1px solid #dee2e6;
        border-top: 0;
        border-radius: 0 0 .375rem .375rem;
    }

    #html_editor .ql-editor {
        min-height: 280px;
    }

    .ql-container {
        font-size: 16px !important;
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

    .ql-editor { background-color: #f8fafc; }
</style>

<div class="container has-rail">
  <div class="mb-4">
    <h1 class="h4 fw-bold mb-1"><i class="fa-solid fa-envelope me-2 text-primary"></i>Plantillas de Correo</h1>
    <p class="text-muted mb-0" style="font-size:.85rem">Sistema · Edita el asunto y cuerpo HTML de cada notificación</p>
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
                placeholder="Asunto del correo (puedes usar {{eventName}}, {{studentName}}, etc.)" disabled>
        </div>

        <div class="form-section">
            <h2 class="h6 mb-2">HTML</h2>
            <div id="html_editor" class="form-control p-0"></div>
            <input type="hidden" id="f_html">
            <div class="form-text">
                Inyecta CSS con <code class="k" hover="CSS embebido en &lt;style&gt;">{{{css}}}</code>.
                Para HTML confiable usa <code class="k" hover="HTML sin escapar">{{{description}}}</code>.
                Por defecto <code>{{var}}</code> se escapa.
            </div>
        </div>
    </form>

</div>

<aside class="fixed-rail" aria-label="Panel fijo de variables y acciones">
    <div class="vars-fixed card">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
                <strong>Variables de la plantilla</strong>
            </div>
            <div id="varsWrap" class="mt-2 d-flex flex-wrap gap-2"></div>
            <small class="d-block mt-3 text-muted">
                Clic para insertar en el cursor (Quill o input). Si nada está enfocado, se copia al portapapeles.
            </small>
            <hr>
            <div>
                <strong>Atajos comunes:</strong>
                <div class="mt-2 d-flex flex-wrap gap-2">
                    <code class="k" hover="CSS embebido">{{{css}}}</code>
                    <code class="k" hover="Año actual">{{year}}</code>
                    <code class="k" hover="Asunto actual">{{subject}}</code>
                </div>
            </div>
        </div>
    </div>

    <div class="sticky-actions d-flex flex-wrap gap-2">
        <button class="btn btn-primary" id="btnSave">Guardar cambios</button>
        <button class="btn btn-light" id="btnReload">Reiniciar</button>
    </div>
</aside>

<script>
function _initMailQuill() {
    const quill = window._mailQuill = new Quill('#html_editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ header: [1, 2, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                ['blockquote', 'code-block'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['link', 'image'],
                ['clean']
            ]
        }
    });

    function loadTemplates() {
        $.ajax({
            url: 'controller/ajax/mail_templates.php',
            method: 'POST',
            data: { action: 'list' },
            dataType: 'json'
        }).done(function (response) {
            if (response.success) {
                const tplSelect = $('#tplSelect');
                tplSelect.empty().append('<option value="">— Selecciona una plantilla —</option>');
                response.templates.forEach(tpl => {
                    tplSelect.append(`<option value="${tpl.id}">${tpl.name}</option>`);
                });
                tplSelect.prop('disabled', false);
            } else {
                showAlert('Error al cargar las plantillas.', 'danger');
            }
        }).fail(function () {
            showAlert('Error al cargar las plantillas.', 'danger');
        });
    }

    loadTemplates();

    $(document).ready(function () {
        $('#btnSave').prop('disabled', true);
        $('#btnReload').prop('disabled', true);
        $('#tplSelect').on('change', function () {
            const tplId = $(this).val();
            if (tplId) {
                loadTemplateDetails(tplId);
            } else {
                $('#f_subject').val('').prop('disabled', true);
                quill.setContents([]);
                quill.disable();
                $('#f_html').val('');
                $('#varsWrap').empty();
            }
        });

        $('#btnReload').on('click', function () {
            const tplId = $('#tplSelect').val();
            if (tplId) {
                loadTemplateDetails(tplId);
            }
        });

        $('#btnSave').on('click', function () {
            const tplId = $('#tplSelect').val();
            const subject = $('#f_subject').val().trim();
            const html = quill.root.innerHTML.trim();
            if (tplId && subject && html) {
                saveTemplate(tplId, subject, html);
            } else {
                showAlert('Asunto y HTML no pueden estar vacíos.', 'warning');
            }
        });
    });

    function loadTemplateDetails(tplId) {
        $.ajax({
            url: 'controller/ajax/mail_templates.php',
            method: 'POST',
            data: { action: 'get', id: tplId },
            dataType: 'json'
        }).done(function (response) {
            if (response.success) {
                const tpl = response.template;
                $('#f_subject').val(tpl.subject).prop('disabled', false);
                quill.setContents(quill.clipboard.convert(tpl.html));
                quill.enable();
                $('#f_html').val(tpl.html);
                $('#btnSave').prop('disabled', false);
                $('#btnReload').prop('disabled', false);
            } else {
                showAlert('Error al cargar los detalles de la plantilla.', 'danger');
            }
        }).fail(function () {
            showAlert('Error al cargar los detalles de la plantilla.', 'danger');
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
                showAlert('Plantilla guardada con éxito.', 'success');
            } else {
                showAlert('Error al guardar la plantilla.', 'danger');
            }
        }).fail(function () {
            showAlert('Error al guardar la plantilla.', 'danger');
        });
    }

    const showAlert = (type, msg) => {
        $('#alertBox').html(
            `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
         ${msg}
         <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
       </div>`
        );
        setTimeout(() => {
            $('#alertBox').html('');
        }, 3500);
    };
} // end _initMailQuill

(function () {
    if (window.Quill) { _initMailQuill(); return; }
    var s = document.createElement('script');
    s.src = 'https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js';
    s.onload = _initMailQuill;
    document.head.appendChild(s);
})();
</script>