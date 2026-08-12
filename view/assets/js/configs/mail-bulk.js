// view/assets/js/configs/mail-bulk.js
// Gestor de envío masivo de correos — lógica del wizard de 3 pasos.
(function () {
    'use strict';

    var CFG = window.MB_CONFIG || {};
    var CSRF = CFG.csrf;
    var EP = CFG.endpoints || {};

    var state = {
        step: 1,
        editor: null,
        table: null,
        attachments: [], // [{path, original_name, size}]
        csvFile: null,
        lastFocused: 'editor', // 'subject' | 'editor' — dónde insertar la próxima variable
        htmlMode: false,       // true = se está editando el HTML a mano
        layoutPref: true,      // preferencia de diseño institucional del administrador
        previewTimer: null,
        previewSeq: 0,
        editingRecipientId: null,
        clientToken: null,
        campaignId: null,
        pollTimer: null,
        currentSavedMessageId: null,
    };

    function uuidv4() {
        if (window.crypto && window.crypto.randomUUID) return window.crypto.randomUUID();
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = Math.random() * 16 | 0, v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    function escapeHtml(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function showAlert(containerId, type, msg) {
        var box = document.getElementById(containerId);
        if (!box) return;
        box.innerHTML = '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
            escapeHtml(msg) +
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        if (type === 'success') {
            setTimeout(function () { box.innerHTML = ''; }, 4500);
        }
    }

    function friendlyNetworkError() {
        return 'No se pudo completar la acción. Revisa tu conexión e inténtalo de nuevo.';
    }

    /** fetch con JSON, agrega el token CSRF por header. */
    function apiJson(url, method, body) {
        var opts = {
            method: method || 'GET',
            headers: { 'X-CSRF-Token': CSRF },
        };
        if (body !== undefined) {
            opts.headers['Content-Type'] = 'application/json';
            opts.body = JSON.stringify(body);
        }
        return fetch(url, opts).then(function (r) { return r.json(); });
    }

    function apiForm(url, formData) {
        return fetch(url, { method: 'POST', body: formData }).then(function (r) { return r.json(); });
    }

    function syncCsrf(resp) {
        if (resp && resp.csrf) CSRF = resp.csrf;
    }

    /* ═══════════════════════ Navegación de pasos ═══════════════════════ */

    function goToStep(n) {
        state.step = n;
        document.querySelectorAll('.mb-step').forEach(function (el) {
            var s = parseInt(el.dataset.step, 10);
            el.classList.toggle('active', s === n);
            el.classList.toggle('done', s < n);
        });
        document.querySelectorAll('.mb-panel').forEach(function (el) {
            el.classList.toggle('active', parseInt(el.dataset.panel, 10) === n);
        });

        var prevBtn = document.getElementById('mbBtnPrev');
        var nextBtn = document.getElementById('mbBtnNext');
        prevBtn.style.display = n === 1 ? 'none' : '';
        nextBtn.style.display = n === 3 ? 'none' : '';

        if (n === 3) renderStep3();
    }

    document.querySelectorAll('.mb-step').forEach(function (el) {
        el.addEventListener('click', function () { goToStep(parseInt(el.dataset.step, 10)); });
    });
    document.getElementById('mbBtnPrev').addEventListener('click', function () {
        if (state.step > 1) goToStep(state.step - 1);
    });
    document.getElementById('mbBtnNext').addEventListener('click', function () {
        if (state.step < 3) goToStep(state.step + 1);
    });

    /* ═══════════════════════ Paso 1: Destinatarios ═══════════════════════ */

    function initTable() {
        state.table = $('#mbRecipientsTable').DataTable({
            columns: [
                { data: null, orderable: false, className: 'select-checkbox', defaultContent: '', width: '20px' },
                { data: 'name' },
                { data: 'email' },
                { data: 'notes', defaultContent: '' },
                {
                    data: null, orderable: false, className: 'text-end',
                    render: function (row) {
                        return '<button class="btn btn-sm btn-light mb-edit" data-id="' + row.id + '" title="Editar"><i class="fa-solid fa-pen"></i></button> ' +
                            '<button class="btn btn-sm btn-light text-danger mb-delete" data-id="' + row.id + '" title="Eliminar"><i class="fa-solid fa-trash"></i></button>';
                    },
                },
            ],
            select: { style: 'multi', selector: 'td:first-child' },
            order: [[1, 'asc']],
            language: { url: null, emptyTable: 'Aún no has agregado destinatarios.' },
            pageLength: 25,
        });

        state.table.on('select deselect', updateSelectedCount);

        $('#mbRecipientsTable tbody').on('click', '.mb-edit', function () {
            var id = parseInt($(this).data('id'), 10);
            var row = state.table.rows().data().toArray().find(function (r) { return r.id === id; });
            if (!row) return;
            state.editingRecipientId = id;
            document.getElementById('mbNewName').value = row.name;
            document.getElementById('mbNewEmail').value = row.email;
            document.getElementById('mbNewNotes').value = row.notes || '';
            document.getElementById('mbBtnAddRecipient').textContent = 'Guardar cambios';
            document.getElementById('mbNewName').focus();
        });

        $('#mbRecipientsTable tbody').on('click', '.mb-delete', function () {
            var id = parseInt($(this).data('id'), 10);
            Swal.fire({
                title: '¿Eliminar a esta persona de la lista?',
                icon: 'warning', showCancelButton: true,
                confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar',
            }).then(function (r) {
                if (!r.isConfirmed) return;
                apiJson(EP.recipients, 'POST', { action: 'delete', ids: [id] }).then(function () {
                    loadRecipients();
                });
            });
        });
    }

    function updateSelectedCount() {
        var n = state.table.rows({ selected: true }).count();
        document.getElementById('mbSelectedCount').textContent = n + (n === 1 ? ' seleccionada' : ' seleccionadas');
        document.getElementById('mbBtnDeleteSelected').disabled = n === 0;
    }

    function selectedRecipients() {
        return state.table.rows({ selected: true }).data().toArray();
    }

    function loadRecipients() {
        return apiJson(EP.recipients + '?action=list', 'GET').then(function (resp) {
            syncCsrf(resp);
            if (!resp.ok) return;
            state.table.clear().rows.add(resp.recipients).draw();
            updateSelectedCount();
        }).catch(function () { showAlert('mbAlert', 'danger', friendlyNetworkError()); });
    }

    document.getElementById('mbBtnAddRecipient').addEventListener('click', function () {
        var name = document.getElementById('mbNewName').value.trim();
        var email = document.getElementById('mbNewEmail').value.trim();
        var notes = document.getElementById('mbNewNotes').value.trim();
        if (!name || !email) {
            showAlert('mbAlert', 'danger', 'Escribe el nombre y el correo de la persona.');
            return;
        }
        var payload = { action: state.editingRecipientId ? 'update' : 'create', name: name, email: email, notes: notes };
        if (state.editingRecipientId) payload.id = state.editingRecipientId;

        apiJson(EP.recipients, 'POST', payload).then(function (resp) {
            if (!resp.ok) {
                showAlert('mbAlert', 'danger', resp.error || 'No se pudo guardar a la persona.');
                return;
            }
            document.getElementById('mbNewName').value = '';
            document.getElementById('mbNewEmail').value = '';
            document.getElementById('mbNewNotes').value = '';
            document.getElementById('mbBtnAddRecipient').textContent = 'Agregar';
            state.editingRecipientId = null;
            loadRecipients();
            showAlert('mbAlert', 'success', 'Guardado correctamente.');
        }).catch(function () { showAlert('mbAlert', 'danger', friendlyNetworkError()); });
    });

    document.getElementById('mbBtnDeleteSelected').addEventListener('click', function () {
        var ids = selectedRecipients().map(function (r) { return r.id; });
        if (!ids.length) return;
        Swal.fire({
            title: '¿Eliminar ' + ids.length + (ids.length === 1 ? ' persona' : ' personas') + ' de la lista?',
            icon: 'warning', showCancelButton: true,
            confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            apiJson(EP.recipients, 'POST', { action: 'bulk_delete', ids: ids }).then(function () {
                loadRecipients();
            });
        });
    });

    /* ── Importar CSV (con dropzone) ── */
    var csvModal = null;

    function setCsvFile(file) {
        state.csvFile = file || null;
        var box = document.getElementById('mbCsvFileName');
        if (file) {
            document.getElementById('mbCsvFileNameText').textContent = file.name;
            document.getElementById('mbCsvFileSizeText').textContent = Math.round(file.size / 1024) + ' KB';
            box.classList.add('is-shown');
        } else {
            box.classList.remove('is-shown');
        }
    }

    document.getElementById('mbBtnImportCsv').addEventListener('click', function () {
        document.getElementById('mbCsvResult').innerHTML = '';
        document.getElementById('mbCsvFile').value = '';
        setCsvFile(null);
        csvModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('mbCsvModal'));
        csvModal.show();
    });

    var csvDz = document.getElementById('mbCsvDropzone');
    csvDz.addEventListener('click', function () { document.getElementById('mbCsvFile').click(); });
    document.getElementById('mbCsvFile').addEventListener('change', function () {
        if (this.files.length) setCsvFile(this.files[0]);
    });
    ['dragenter', 'dragover'].forEach(function (ev) {
        csvDz.addEventListener(ev, function (e) { e.preventDefault(); csvDz.classList.add('is-drag'); });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
        csvDz.addEventListener(ev, function (e) { e.preventDefault(); csvDz.classList.remove('is-drag'); });
    });
    csvDz.addEventListener('drop', function (e) {
        if (e.dataTransfer && e.dataTransfer.files.length) setCsvFile(e.dataTransfer.files[0]);
    });
    document.getElementById('mbCsvFileClear').addEventListener('click', function (e) {
        e.stopPropagation();
        document.getElementById('mbCsvFile').value = '';
        setCsvFile(null);
    });

    document.getElementById('mbBtnCsvUpload').addEventListener('click', function () {
        if (!state.csvFile) {
            document.getElementById('mbCsvResult').innerHTML = '<div class="alert alert-danger">Selecciona o arrastra un archivo CSV.</div>';
            return;
        }
        var fd = new FormData();
        fd.append('csv', state.csvFile);
        fd.append('csrf_token', CSRF);

        var btn = document.getElementById('mbBtnCsvUpload');
        btn.disabled = true;
        apiForm(EP.recipients, fd).then(function (resp) {
            btn.disabled = false;
            if (!resp.ok) {
                document.getElementById('mbCsvResult').innerHTML = '<div class="alert alert-danger">' + escapeHtml(resp.error || 'No se pudo importar el archivo.') + '</div>';
                return;
            }
            var html = '<div class="alert alert-success">Importación completada.<br>' +
                '<strong>' + resp.imported + '</strong> personas agregadas correctamente.<br>' +
                (resp.skipped_duplicates ? '<strong>' + resp.skipped_duplicates + '</strong> correos ya estaban en la lista.<br>' : '') +
                (resp.invalid.length ? '<strong>' + resp.invalid.length + '</strong> registros no pudieron agregarse.' : '') +
                '</div>';
            if (resp.invalid.length) {
                html += '<div class="small text-muted">' + resp.invalid.map(function (i) {
                    return 'Fila ' + i.fila + ': ' + escapeHtml(i.motivo);
                }).join('<br>') + '</div>';
            }
            document.getElementById('mbCsvResult').innerHTML = html;
            document.getElementById('mbCsvFile').value = '';
            setCsvFile(null);
            loadRecipients();
        }).catch(function () {
            btn.disabled = false;
            document.getElementById('mbCsvResult').innerHTML = '<div class="alert alert-danger">' + friendlyNetworkError() + '</div>';
        });
    });

    /* ═══════════════════════ Paso 2: Mensaje ═══════════════════════ */

    // El build auto-hospedado ("super-build") expone window.CKEDITOR.ClassicEditor;
    // el build "classic" exponía window.ClassicEditor. Se aceptan ambos por si algún
    // día se cambia de build, para que el editor no deje de cargar en silencio.
    function getEditorClass() {
        return (window.CKEDITOR && window.CKEDITOR.ClassicEditor) || window.ClassicEditor || null;
    }

    /**
     * Sin adaptador de subida, arrastrar una imagen al editor termina en un error
     * técnico en consola y un recuadro roto. Aquí se rechaza a propósito, con el
     * mensaje que le sirve al administrador.
     */
    function rejectImageUploads(editor) {
        var repo = editor.plugins.get('FileRepository');
        repo.createUploadAdapter = function () {
            return {
                upload: function () {
                    return Promise.reject(
                        'Este correo no guarda imágenes en el sistema. Publica la imagen en internet ' +
                        '(por ejemplo en el sitio de la Universidad) y pega su dirección con el botón “Insertar imagen”.'
                    );
                },
                abort: function () { },
            };
        };
    }

    function initEditor() {
        var Editor = getEditorClass();
        if (!Editor) {
            showAlert('mbAlert', 'danger', 'No se pudo cargar el editor de texto. Recarga la página; si el problema continúa, avisa al área de sistemas.');
            return Promise.resolve();
        }
        return Editor.create(document.getElementById('mbEditor'), {
            language: 'es',
            placeholder: 'Escribe aquí el contenido del correo…',
            toolbar: {
                items: [
                    'undo', 'redo', '|', 'heading', 'fontFamily', 'fontSize', '|',
                    'bold', 'italic', 'underline', 'strikethrough', 'fontColor', 'fontBackgroundColor', '|',
                    'bulletedList', 'numberedList', 'alignment', '|',
                    'link', 'insertImage', 'insertTable', 'horizontalLine', '|', 'removeFormat',
                ],
                shouldNotGroupWhenFull: true,
            },
            image: {
                // Sin subida de archivos: las imágenes se insertan pegando su dirección
                // (la decisión fue no crear un almacén público de imágenes en el sitio).
                insert: { type: 'auto' },
                toolbar: [
                    'imageTextAlternative', '|',
                    'imageStyle:inline', 'imageStyle:alignLeft', 'imageStyle:alignCenter', 'imageStyle:alignRight',
                ],
            },
            table: { contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells', 'tableProperties', 'tableCellProperties'] },
            // GeneralHtmlSupport: sin esto, al volver del modo "Código HTML" el editor
            // visual tiraba las etiquetas y atributos que no entiende (tablas con
            // align/bgcolor, clases…), que son justo los que usan las plantillas de
            // correo. Con esto los conserva aunque no sepa editarlos.
            htmlSupport: {
                allow: [{ name: /.*/, attributes: true, classes: true, styles: true }],
                disallow: [{ name: /^(script|style|iframe|form|input)$/ }],
            },
            extraPlugins: [rejectImageUploads],
            // El super-build trae además funciones de pago (colaboración, exportar a
            // PDF/Word, revisión ortográfica, etc.). Se desactivan porque no se usan
            // aquí y, sin licencia, ensucian la consola con avisos.
            removePlugins: [
                'CKBox', 'CKFinder', 'EasyImage', 'MathType',
                'RealTimeCollaborativeComments', 'RealTimeCollaborativeTrackChanges', 'RealTimeCollaborativeRevisionHistory',
                'RealTimeCollaborativeEditing', 'PresenceList', 'Comments', 'TrackChanges', 'TrackChangesData',
                'RevisionHistory', 'Pagination', 'WProofreader',
                'SlashCommand', 'Template', 'DocumentOutline', 'TableOfContents',
                'FormatPainter', 'PasteFromOfficeEnhanced', 'CaseChange',
                'ExportPdf', 'ExportWord', 'AIAssistant', 'MultiLevelList'
            ],
        }).then(function (editor) {
            state.editor = editor;
            editor.editing.view.document.on('focus', function () { state.lastFocused = 'editor'; });
        }).catch(function (err) {
            console.error(err);
            showAlert('mbAlert', 'danger', 'No se pudo iniciar el editor de texto. Recarga la página e inténtalo de nuevo.');
        });
    }

    /* ── Editor visual ⇄ Código HTML ───────────────────────────────────────
       El cuerpo del correo vive en uno de los dos: CKEditor o el textarea.
       getBodyHtml() es la única fuente de verdad para guardar, previsualizar
       y enviar, para que las tres cosas nunca se desincronicen. */

    var codeArea = document.getElementById('mbEditorCode');
    var tabVisual = document.getElementById('mbTabVisual');
    var tabCode = document.getElementById('mbTabCode');
    var layoutCheck = document.getElementById('mbUseLayout');
    var layoutBox = document.getElementById('mbLayoutBox');
    var layoutDesc = document.getElementById('mbUseLayoutDesc');

    /** ¿El HTML es un documento completo y no un fragmento? */
    function isFullHtmlDoc(html) {
        return /<html[\s>]/i.test(html) || /<!doctype/i.test(html);
    }

    function getBodyHtml() {
        if (state.htmlMode) return codeArea.value;
        return state.editor ? state.editor.getData() : '';
    }

    function setBodyHtml(html) {
        html = html || '';
        codeArea.value = html;
        if (isFullHtmlDoc(html)) {
            // El editor visual no puede sostener un documento completo: se queda en código.
            setMode(true, true);
        } else if (state.editor) {
            state.editor.setData(html);
        }
        syncLayoutAvailability();
    }

    /**
     * Un documento HTML completo ya trae su propio <html>, así que envolverlo en la
     * plantilla institucional produciría un correo con dos documentos anidados.
     * En ese caso la opción se apaga y se bloquea, explicando por qué.
     */
    function syncLayoutAvailability() {
        var full = isFullHtmlDoc(getBodyHtml());
        layoutBox.classList.toggle('is-locked', full);
        if (full) {
            // Se recuerda la preferencia para devolverla si el HTML vuelve a ser un
            // fragmento; si no, apagar el diseño quedaba pegado sin que nadie lo pidiera.
            if (!layoutCheck.disabled) state.layoutPref = layoutCheck.checked;
            layoutCheck.checked = false;
            layoutCheck.disabled = true;
            layoutDesc.textContent = 'Tu HTML ya es un documento completo con su propio diseño, '
                + 'así que se envía tal cual, sin agregarle nada alrededor.';
        } else {
            if (layoutCheck.disabled) {
                layoutCheck.checked = state.layoutPref !== false;
            }
            layoutCheck.disabled = false;
            layoutDesc.textContent = 'Tu mensaje se envía dentro de la plantilla de la Universidad: logo arriba '
                + 'y pie de página con los datos institucionales. Desactívalo si tu HTML ya trae su propio diseño.';
        }
        if (state.step === 3) renderStep3();
    }

    function useLayout() {
        return !!(layoutCheck && layoutCheck.checked && !layoutCheck.disabled);
    }

    function setMode(toCode, skipTransfer) {
        if (state.htmlMode === toCode) {
            if (toCode) syncLayoutAvailability();
            return;
        }
        if (!skipTransfer) {
            if (toCode) {
                codeArea.value = state.editor ? state.editor.getData() : '';
            } else if (state.editor) {
                state.editor.setData(codeArea.value);
            }
        }
        state.htmlMode = toCode;
        document.getElementById('mbEditorWrap').style.display = toCode ? 'none' : '';
        document.getElementById('mbCodeWrap').style.display = toCode ? '' : 'none';
        tabVisual.classList.toggle('active', !toCode);
        tabCode.classList.toggle('active', toCode);
        tabVisual.setAttribute('aria-selected', String(!toCode));
        tabCode.setAttribute('aria-selected', String(toCode));
        state.lastFocused = toCode ? 'code' : 'editor';
        syncLayoutAvailability();
    }

    tabCode.addEventListener('click', function () { setMode(true); });

    tabVisual.addEventListener('click', function () {
        if (!state.htmlMode) return;
        if (isFullHtmlDoc(codeArea.value)) {
            Swal.fire({
                icon: 'info',
                title: 'Esta plantilla solo se edita como código',
                text: 'Escribiste un documento HTML completo. El editor visual no puede mostrarlo sin '
                    + 'deshacer su diseño, así que sigue editándolo aquí. La vista previa del paso 3 te '
                    + 'muestra cómo va quedando.',
                confirmButtonText: 'Entendido',
            });
            return;
        }
        if (!codeArea.value.trim()) { setMode(false); return; }
        Swal.fire({
            icon: 'warning',
            title: '¿Volver al editor visual?',
            text: 'El editor visual puede simplificar el HTML avanzado (por ejemplo, algunas etiquetas o '
                + 'estilos poco comunes). Si tu plantilla es delicada, conviene seguir en modo código.',
            showCancelButton: true,
            confirmButtonText: 'Sí, volver al editor visual',
            cancelButtonText: 'Seguir en código',
        }).then(function (r) { if (r.isConfirmed) setMode(false); });
    });

    codeArea.addEventListener('input', function () {
        clearTimeout(state.codeTimer);
        state.codeTimer = setTimeout(syncLayoutAvailability, 350);
    });
    codeArea.addEventListener('focus', function () { state.lastFocused = 'code'; });
    layoutCheck.addEventListener('change', function () {
        state.layoutPref = layoutCheck.checked;
        if (state.step === 3) renderStep3();
    });

    document.getElementById('mbSubject').addEventListener('focus', function () { state.lastFocused = 'subject'; });

    document.querySelectorAll('.mb-var-chip').forEach(function (chip) {
        chip.addEventListener('click', function () {
            var token = chip.dataset.token;
            if (state.lastFocused === 'subject') {
                var input = document.getElementById('mbSubject');
                var pos = input.selectionStart || input.value.length;
                input.value = input.value.slice(0, pos) + token + input.value.slice(pos);
                input.focus();
                input.selectionStart = input.selectionEnd = pos + token.length;
            } else if (state.htmlMode) {
                var cpos = codeArea.selectionStart != null ? codeArea.selectionStart : codeArea.value.length;
                codeArea.value = codeArea.value.slice(0, cpos) + token + codeArea.value.slice(cpos);
                codeArea.focus();
                codeArea.selectionStart = codeArea.selectionEnd = cpos + token.length;
            } else if (state.editor) {
                state.editor.model.change(function (writer) {
                    state.editor.model.insertContent(writer.createText(token), state.editor.model.document.selection);
                });
                state.editor.editing.view.focus();
            }
        });
    });

    /* ── Redacciones guardadas ── */
    function loadSavedMessages() {
        return apiJson(EP.messages + '?action=list', 'GET').then(function (resp) {
            syncCsrf(resp);
            var sel = document.getElementById('mbSavedMessageSelect');
            sel.innerHTML = '<option value="">— Cargar una redacción guardada —</option>';
            (resp.messages || []).forEach(function (m) {
                var opt = document.createElement('option');
                opt.value = m.id;
                opt.textContent = m.name;
                sel.appendChild(opt);
            });
        });
    }

    document.getElementById('mbSavedMessageSelect').addEventListener('change', function () {
        var id = this.value;
        document.getElementById('mbBtnDeleteMessage').disabled = !id;
        state.currentSavedMessageId = id ? parseInt(id, 10) : null;
        if (!id) return;
        apiJson(EP.messages + '?action=get&id=' + id, 'GET').then(function (resp) {
            if (!resp.ok) return;
            document.getElementById('mbSubject').value = resp.message.subject;
            state.layoutPref = resp.message.use_layout === undefined
                || String(resp.message.use_layout) === '1';
            setBodyHtml(resp.message.body_html);
            if (!layoutCheck.disabled) layoutCheck.checked = state.layoutPref;
        });
    });

    document.getElementById('mbBtnSaveMessage').addEventListener('click', function () {
        var subject = document.getElementById('mbSubject').value.trim();
        var body = getBodyHtml();
        if (!subject || !body.trim()) {
            showAlert('mbAlert', 'danger', 'Escribe el asunto y el mensaje antes de guardarlo.');
            return;
        }
        Swal.fire({
            title: 'Guardar esta redacción',
            input: 'text',
            inputLabel: 'Nombre para identificarla después',
            inputValue: '',
            showCancelButton: true,
            confirmButtonText: 'Guardar', cancelButtonText: 'Cancelar',
        }).then(function (r) {
            if (!r.isConfirmed || !r.value) return;
            apiJson(EP.messages, 'POST', {
                action: 'save', name: r.value, subject: subject,
                body_html: body, use_layout: useLayout() ? 1 : 0,
            }).then(function (resp) {
                if (!resp.ok) {
                    showAlert('mbAlert', 'danger', resp.error || 'No se pudo guardar la redacción.');
                    return;
                }
                showAlert('mbAlert', 'success', 'Redacción guardada correctamente.');
                loadSavedMessages();
            });
        });
    });

    document.getElementById('mbBtnDeleteMessage').addEventListener('click', function () {
        if (!state.currentSavedMessageId) return;
        Swal.fire({
            title: '¿Eliminar esta redacción guardada?', icon: 'warning',
            showCancelButton: true, confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            apiJson(EP.messages, 'POST', { action: 'delete', id: state.currentSavedMessageId }).then(function () {
                document.getElementById('mbSavedMessageSelect').value = '';
                document.getElementById('mbBtnDeleteMessage').disabled = true;
                state.currentSavedMessageId = null;
                loadSavedMessages();
            });
        });
    });

    /* ── Archivos adjuntos ── */
    function renderAttachments() {
        var list = document.getElementById('mbAttachmentList');
        list.innerHTML = state.attachments.map(function (a, i) {
            return '<div class="mb-attachment">' +
                '<i class="fa-regular fa-file"></i>' +
                '<span class="name">' + escapeHtml(a.original_name) + '</span>' +
                '<span class="size">' + Math.round(a.size / 1024) + ' KB</span>' +
                '<button type="button" class="btn btn-sm btn-light text-danger mb-remove-att" data-i="' + i + '">Eliminar</button>' +
                '</div>';
        }).join('');
        list.querySelectorAll('.mb-remove-att').forEach(function (btn) {
            btn.addEventListener('click', function () {
                state.attachments.splice(parseInt(btn.dataset.i, 10), 1);
                renderAttachments();
            });
        });
    }

    function uploadFiles(files) {
        Array.prototype.forEach.call(files, function (file) {
            var fd = new FormData();
            fd.append('file', file);
            fd.append('csrf_token', CSRF);
            apiForm(EP.upload, fd).then(function (resp) {
                if (!resp.ok) {
                    showAlert('mbAlert', 'danger', resp.error || ('No se pudo adjuntar "' + file.name + '".'));
                    return;
                }
                state.attachments.push(resp);
                renderAttachments();
            }).catch(function () { showAlert('mbAlert', 'danger', friendlyNetworkError()); });
        });
    }

    var dz = document.getElementById('mbDropzone');
    dz.addEventListener('click', function () { document.getElementById('mbFileInput').click(); });
    document.getElementById('mbFileInput').addEventListener('change', function () { uploadFiles(this.files); this.value = ''; });
    ['dragenter', 'dragover'].forEach(function (ev) {
        dz.addEventListener(ev, function (e) { e.preventDefault(); dz.classList.add('is-drag'); });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
        dz.addEventListener(ev, function (e) { e.preventDefault(); dz.classList.remove('is-drag'); });
    });
    dz.addEventListener('drop', function (e) {
        if (e.dataTransfer && e.dataTransfer.files.length) uploadFiles(e.dataTransfer.files);
    });

    /* ═══════════════════════ Paso 3: Revisar y enviar ═══════════════════════ */

    /**
     * Reproduce las tres ramas de MailBulkModel::buildEmailHtml() (PHP), que es lo
     * que realmente se manda. Si allá cambia el armado del correo, hay que cambiarlo
     * aquí también o la vista previa volverá a mentir.
     */
    function buildEmailDoc(subjectText, bodyHtml, withLayout) {
        if (isFullHtmlDoc(bodyHtml)) return bodyHtml;

        if (!withLayout) {
            return '<html><head><meta charset="UTF-8"><title>' + escapeHtml(subjectText) + '</title></head>' +
                '<body>' + bodyHtml + '</body></html>';
        }

        return '<html><head><meta charset="UTF-8"><title>' + escapeHtml(subjectText) + '</title>' +
            '<style>' + (CFG.emailCss || '') + '</style></head><body>' +
            '<div class="container">' +
            '<div class="header"><img src="view/assets/images/logo-color.png" alt="Logo UNIMO"></div>' +
            '<div class="content">' + bodyHtml + '</div>' +
            '<div class="footer">Universidad Montrer (UNIMO) • Av Lázaro Cárdenas 1760, Chapultepec Sur, 58260 Morelia, Mich.</div>' +
            '</div></body></html>';
    }

    function interpolatePersonal(tpl, name, email) {
        return String(tpl || '').split('{nombre}').join(name).split('{correo}').join(email);
    }

    var SAMPLE_NAME = 'Juan Pérez de ejemplo';
    var SAMPLE_EMAIL = 'juan.perez@ejemplo.com';

    /**
     * La vista previa la arma el SERVIDOR, con el mismo saneado y el mismo armado del
     * envío real. Antes se construía aquí con el HTML crudo, así que no mostraba lo que
     * el saneado quitaba: una plantilla podía verse perfecta en pantalla y llegar
     * desarmada al buzón. Si el servidor no responde se cae a la versión local, avisando
     * que es aproximada.
     */
    function refreshPreview(subject, bodyHtml, withLayout) {
        var frame = document.getElementById('mbPreviewFrame');
        var note = document.getElementById('mbPreviewNote');

        clearTimeout(state.previewTimer);
        state.previewTimer = setTimeout(function () {
            var seq = ++state.previewSeq;
            apiJson(EP.campaigns, 'POST', {
                action: 'preview', subject: subject, body_html: bodyHtml,
                use_layout: withLayout ? 1 : 0,
                sample_name: SAMPLE_NAME, sample_email: SAMPLE_EMAIL,
            }).then(function (resp) {
                if (seq !== state.previewSeq) return; // ya salió una previa más nueva
                if (!resp || !resp.ok || typeof resp.html !== 'string') throw new Error('respuesta inválida');
                frame.srcdoc = resp.html;
                if (note) {
                    note.classList.remove('is-approx');
                    note.innerHTML = '<i class="fa-solid fa-circle-check"></i> Esto es exactamente lo que se '
                        + 'enviará: el correo ya armado y revisado por seguridad.';
                }
            }).catch(function () {
                if (seq !== state.previewSeq) return;
                frame.srcdoc = buildEmailDoc(
                    interpolatePersonal(subject, SAMPLE_NAME, SAMPLE_EMAIL),
                    interpolatePersonal(bodyHtml, SAMPLE_NAME, SAMPLE_EMAIL),
                    withLayout
                );
                if (note) {
                    note.classList.add('is-approx');
                    note.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Vista previa aproximada: '
                        + 'no se pudo consultar al servidor, así que se armó en tu navegador. Envía un correo de '
                        + 'prueba antes del envío masivo.';
                }
            });
        }, 350);
    }

    function renderStep3() {
        var count = selectedRecipients().length;
        var subject = document.getElementById('mbSubject').value.trim();
        var fromName = document.getElementById('mbFromName').value.trim();
        var bodyHtml = getBodyHtml();

        document.getElementById('mbSumRecipients').textContent = count + (count === 1 ? ' persona' : ' personas');
        document.getElementById('mbSumSubject').textContent = subject || '—';
        document.getElementById('mbSumAttachments').textContent = state.attachments.length
            ? (state.attachments.length + (state.attachments.length === 1 ? ' archivo' : ' archivos'))
            : 'Ninguno';
        document.getElementById('mbSumFrom').textContent = fromName || '—';
        document.getElementById('mbBtnSendCount').textContent = count;
        document.getElementById('mbBtnConfirmSend').disabled = count === 0 || !subject || !bodyHtml.trim() || !fromName;

        refreshPreview(subject, bodyHtml, useLayout());

        // Ocultar progreso/resultado de un envío anterior al reeditar.
        if (!state.campaignId) {
            document.getElementById('mbProgressSection').style.display = 'none';
            document.getElementById('mbResultSection').style.display = 'none';
            document.getElementById('mbSendSection').style.display = '';
        }
    }

    document.getElementById('mbSubject').addEventListener('input', function () { if (state.step === 3) renderStep3(); });
    document.getElementById('mbFromName').addEventListener('input', function () { if (state.step === 3) renderStep3(); });

    /* ── Enviar correo de prueba ── */
    document.getElementById('mbBtnSendTest').addEventListener('click', function () {
        Swal.fire({
            title: '¿A qué correo enviamos la prueba?',
            input: 'email',
            inputValue: CFG.userEmail || '',
            showCancelButton: true,
            confirmButtonText: 'Enviar prueba', cancelButtonText: 'Cancelar',
        }).then(function (r) {
            if (!r.isConfirmed || !r.value) return;
            var subject = document.getElementById('mbSubject').value.trim();
            var bodyHtml = getBodyHtml();
            var fromName = document.getElementById('mbFromName').value.trim();
            var btn = document.getElementById('mbBtnSendTest');
            btn.disabled = true;
            apiJson(EP.campaigns, 'POST', {
                action: 'send_test', to: r.value, subject: subject, body_html: bodyHtml,
                from_name: fromName, use_layout: useLayout() ? 1 : 0,
                attachments: state.attachments.map(function (a) { return a.path; }),
            }).then(function (resp) {
                btn.disabled = false;
                showAlert('mbAlert', resp.ok ? 'success' : 'danger', resp.message || resp.error || 'No se pudo enviar el correo de prueba.');
            }).catch(function () { btn.disabled = false; showAlert('mbAlert', 'danger', friendlyNetworkError()); });
        });
    });

    /* ── Confirmar y enviar a toda la lista ── */
    document.getElementById('mbBtnConfirmSend').addEventListener('click', function () {
        var recipients = selectedRecipients();
        if (!recipients.length) return;

        Swal.fire({
            title: 'Estás a punto de enviar este correo a ' + recipients.length + (recipients.length === 1 ? ' persona' : ' personas'),
            text: 'Esta acción comenzará el envío de los mensajes y no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, enviar correo', cancelButtonText: 'Cancelar',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            confirmSend(recipients);
        });
    });

    function confirmSend(recipients) {
        if (!state.clientToken) state.clientToken = uuidv4();

        var subject = document.getElementById('mbSubject').value.trim();
        var bodyHtml = getBodyHtml();
        var fromName = document.getElementById('mbFromName').value.trim();

        var btn = document.getElementById('mbBtnConfirmSend');
        var testBtn = document.getElementById('mbBtnSendTest');
        btn.disabled = true;
        testBtn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Enviando…';

        apiJson(EP.campaigns, 'POST', {
            action: 'confirm_send',
            client_token: state.clientToken,
            subject: subject, body_html: bodyHtml, from_name: fromName,
            use_layout: useLayout() ? 1 : 0,
            recipient_ids: recipients.map(function (r) { return r.id; }),
            attachments: state.attachments.map(function (a) { return a.path; }),
        }).then(function (resp) {
            if (!resp.ok) {
                showAlert('mbAlert', 'danger', resp.error || 'No se pudo iniciar el envío.');
                btn.disabled = false;
                testBtn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i> Enviar a <span id="mbBtnSendCount">' + recipients.length + '</span> personas';
                return;
            }
            state.campaignId = resp.campaign_id;
            document.getElementById('mbSendSection').style.display = 'none';
            document.getElementById('mbProgressSection').style.display = '';
            document.getElementById('mbResultSection').style.display = 'none';
            pollProgress();
        }).catch(function () {
            showAlert('mbAlert', 'danger', friendlyNetworkError());
            btn.disabled = false;
            testBtn.disabled = false;
        });
    }

    function pollProgress() {
        clearTimeout(state.pollTimer);
        apiJson(EP.campaigns + '?action=get_progress&campaign_id=' + state.campaignId, 'GET').then(function (resp) {
            if (!resp.ok) return;
            var doneCount = resp.sent + resp.failed;
            var pct = resp.total > 0 ? Math.round((doneCount / resp.total) * 100) : 100;
            document.getElementById('mbProgressBar').style.width = pct + '%';
            document.getElementById('mbProgressSent').textContent = resp.sent;
            document.getElementById('mbProgressFailed').textContent = resp.failed;
            document.getElementById('mbProgressPending').textContent = resp.pending;

            if (resp.pending > 0) {
                state.pollTimer = setTimeout(pollProgress, 3000);
            } else {
                showResult(resp);
            }
        }).catch(function () {
            state.pollTimer = setTimeout(pollProgress, 5000);
        });
    }

    function showResult(progress) {
        document.getElementById('mbProgressSection').style.display = 'none';
        var section = document.getElementById('mbResultSection');
        section.style.display = '';
        document.getElementById('mbResultSummary').textContent =
            progress.total + ' correos procesados. Enviados correctamente: ' + progress.sent + '. No enviados: ' + progress.failed + '.';

        if (progress.failed > 0) {
            apiJson(EP.campaigns + '?action=get&id=' + state.campaignId, 'GET').then(function (resp) {
                if (!resp.ok) return;
                var wrap = document.getElementById('mbResultFailedWrap');
                wrap.style.display = '';
                document.getElementById('mbResultFailedBody').innerHTML = resp.campaign.failed_recipients.map(function (f) {
                    return '<tr><td>' + escapeHtml(f.recipient_name) + '</td><td>' + escapeHtml(f.recipient_email) + '</td><td>' + escapeHtml(f.error_message || '') + '</td></tr>';
                }).join('');
            });
        }
    }

    /* ═══════════════════════ Configuración SMTP (modal) ═══════════════════════ */

    document.getElementById('mbBtnOpenSmtp').addEventListener('click', function () {
        apiJson(EP.smtp, 'GET').then(function (resp) {
            syncCsrf(resp);
            if (!resp.ok) return;
            var c = resp.config;
            document.getElementById('mbSmtpFromEmail').value = c.from_email;
            document.getElementById('mbSmtpFromName').value = c.from_name;
            document.getElementById('mbSmtpHost').value = c.host;
            document.getElementById('mbSmtpPort').value = c.port;
            document.getElementById('mbSmtpUsername').value = c.username;
            document.getElementById('mbSmtpPassword').value = '';
            document.getElementById('mbSmtpPasswordHint').textContent = c.has_password
                ? 'Ya hay una contraseña guardada. Escribe una nueva solo si quieres cambiarla.'
                : 'Aún no se ha guardado ninguna contraseña.';
            document.querySelector('input[name="mbSmtpEnc"][value="' + c.encryption + '"]').checked = true;
            document.getElementById('mbSmtpAlert').innerHTML = '';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('mbSmtpModal')).show();
        });
    });

    document.getElementById('mbBtnSmtpSave').addEventListener('click', function () {
        var payload = {
            host: document.getElementById('mbSmtpHost').value.trim(),
            port: parseInt(document.getElementById('mbSmtpPort').value, 10) || 587,
            encryption: document.querySelector('input[name="mbSmtpEnc"]:checked').value,
            username: document.getElementById('mbSmtpUsername').value.trim(),
            password: document.getElementById('mbSmtpPassword').value,
            from_email: document.getElementById('mbSmtpFromEmail').value.trim(),
            from_name: document.getElementById('mbSmtpFromName').value.trim(),
        };
        apiJson(EP.smtp, 'POST', payload).then(function (resp) {
            showAlert('mbSmtpAlert', resp.ok ? 'success' : 'danger', resp.message || resp.error);
        }).catch(function () { showAlert('mbSmtpAlert', 'danger', friendlyNetworkError()); });
    });

    document.getElementById('mbBtnSmtpTest').addEventListener('click', function () {
        var to = document.getElementById('mbSmtpTestTo').value.trim();
        if (!to) return;
        var btn = document.getElementById('mbBtnSmtpTest');
        btn.disabled = true;
        apiJson(EP.campaigns, 'POST', {
            action: 'send_test', to: to,
            subject: 'Prueba de configuración de correo',
            body_html: '<p>Este es un correo de prueba del Gestor de envío masivo de correos de la Universidad Montrer.</p><p>Si lo recibiste, la configuración es correcta.</p>',
            from_name: document.getElementById('mbSmtpFromName').value.trim() || 'Universidad Montrer',
            attachments: [],
        }).then(function (resp) {
            btn.disabled = false;
            showAlert('mbSmtpAlert', resp.ok ? 'success' : 'danger',
                resp.ok ? 'El correo de prueba se envió correctamente.' : (resp.error || 'No fue posible enviar el correo. Revisa la configuración del servidor de correo y vuelve a intentarlo.'));
        }).catch(function () { btn.disabled = false; showAlert('mbSmtpAlert', 'danger', friendlyNetworkError()); });
    });

    /* ═══════════════════════ Historial (modal) ═══════════════════════ */

    var STATUS_LABELS = {
        queued: 'En cola', sending: 'Enviando', completed: 'Completado',
        completed_with_errors: 'Completado con errores', failed: 'Fallido',
    };

    document.getElementById('mbBtnOpenHistory').addEventListener('click', function () {
        document.getElementById('mbHistoryDetail').style.display = 'none';
        document.getElementById('mbHistoryList').style.display = '';
        apiJson(EP.campaigns + '?action=list_history', 'GET').then(function (resp) {
            syncCsrf(resp);
            if (!resp.ok) return;
            var html = '<table class="mb-history-table"><thead><tr><th>Fecha</th><th>Asunto</th><th>Destinatarios</th><th>Estado</th></tr></thead><tbody>';
            (resp.campaigns || []).forEach(function (c) {
                html += '<tr class="mb-history-row" data-id="' + c.id + '">' +
                    '<td>' + escapeHtml(c.created_at) + '</td>' +
                    '<td>' + escapeHtml(c.subject) + '</td>' +
                    '<td>' + c.sent_count + ' / ' + c.total_recipients + '</td>' +
                    '<td><span class="mb-status-badge ' + c.status + '">' + (STATUS_LABELS[c.status] || c.status) + '</span></td>' +
                    '</tr>';
            });
            html += '</tbody></table>';
            if (!resp.campaigns.length) html = '<p class="mb-history-empty"><i class="fa-regular fa-clock me-1"></i>Todavía no se ha realizado ningún envío.</p>';
            document.getElementById('mbHistoryList').innerHTML = html;

            document.querySelectorAll('.mb-history-row').forEach(function (row) {
                row.addEventListener('click', function () { showHistoryDetail(row.dataset.id); });
            });

            bootstrap.Modal.getOrCreateInstance(document.getElementById('mbHistoryModal')).show();
        });
    });

    function showHistoryDetail(id) {
        apiJson(EP.campaigns + '?action=get&id=' + id, 'GET').then(function (resp) {
            if (!resp.ok) return;
            var c = resp.campaign;
            var html = '<button class="btn btn-sm btn-light mb-2" id="mbBackToHistory"><i class="fa-solid fa-arrow-left me-1"></i>Volver</button>' +
                '<h6>' + escapeHtml(c.subject) + '</h6>' +
                '<p class="text-muted">' + escapeHtml(c.created_at) + '</p>' +
                '<p>Destinatarios: <strong>' + c.total_recipients + '</strong> · Enviados: <strong>' + c.sent_count + '</strong> · Con error: <strong>' + c.failed_count + '</strong></p>';
            if (c.failed_recipients.length) {
                html += '<table class="mb-result-table"><thead><tr><th>Nombre</th><th>Correo</th><th>Motivo</th></tr></thead><tbody>' +
                    c.failed_recipients.map(function (f) {
                        return '<tr><td>' + escapeHtml(f.recipient_name) + '</td><td>' + escapeHtml(f.recipient_email) + '</td><td>' + escapeHtml(f.error_message || '') + '</td></tr>';
                    }).join('') + '</tbody></table>';
            }
            document.getElementById('mbHistoryDetail').innerHTML = html;
            document.getElementById('mbHistoryDetail').style.display = '';
            document.getElementById('mbHistoryList').style.display = 'none';
            document.getElementById('mbBackToHistory').addEventListener('click', function () {
                document.getElementById('mbHistoryDetail').style.display = 'none';
                document.getElementById('mbHistoryList').style.display = '';
            });
        });
    }

    /* ═══════════════════════ Inicio ═══════════════════════ */

    document.addEventListener('DOMContentLoaded', function () {
        // El shell de Configuraciones ya movió .fixed-rail al montar la página;
        // este script corre después porque va al final del editor incluido.
    });

    initTable();
    loadRecipients();
    loadSavedMessages();
    initEditor();
    syncLayoutAvailability();
})();
