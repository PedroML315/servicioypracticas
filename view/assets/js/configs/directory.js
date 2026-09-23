// view/assets/js/configs/directory.js
// Directorio institucional — alta, edición, borrado y búsqueda.
(function () {
    'use strict';

    var CFG = window.DIR_CONFIG || {};
    var CSRF = CFG.csrf;
    var EP = CFG.endpoint;

    var state = {
        table: null,
        editingId: null,
        modal: null,
    };

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

    function syncCsrf(resp) {
        if (resp && resp.csrf) CSRF = resp.csrf;
    }

    /* ═══════════════════════ Tabla ═══════════════════════ */

    function initTable() {
        state.table = $('#dirTable').DataTable({
            columns: [
                { data: null, orderable: false, className: 'select-checkbox', defaultContent: '', width: '20px' },
                { data: 'full_name' },
                { data: 'email' },
                {
                    data: 'job_title',
                    render: function (v) { return '<span class="dir-job">' + escapeHtml(v) + '</span>'; },
                },
                {
                    data: null, orderable: false, className: 'text-end',
                    render: function (row) {
                        return '<button class="btn btn-sm btn-light dir-edit" data-id="' + row.id + '" title="Editar"><i class="fa-solid fa-pen"></i></button> ' +
                            '<button class="btn btn-sm btn-light text-danger dir-delete" data-id="' + row.id + '" title="Eliminar"><i class="fa-solid fa-trash"></i></button>';
                    },
                },
            ],
            select: { style: 'multi', selector: 'td:first-child' },
            order: [[1, 'asc']],
            language: { url: null, emptyTable: 'Aún no hay personas en el directorio.' },
            pageLength: 25,
        });

        state.table.on('select deselect draw', updateSelectedCount);

        $('#dirTable tbody').on('click', '.dir-edit', function () {
            var id = parseInt($(this).data('id'), 10);
            var row = state.table.rows().data().toArray().find(function (r) { return r.id === id; });
            if (row) openModal(row);
        });

        $('#dirTable tbody').on('click', '.dir-delete', function () {
            var id = parseInt($(this).data('id'), 10);
            var row = state.table.rows().data().toArray().find(function (r) { return r.id === id; });
            var nombre = row ? row.full_name : 'esta persona';
            Swal.fire({
                title: '¿Eliminar del directorio?',
                html: 'Se quitará a <strong>' + escapeHtml(nombre) + '</strong> y dejará de recibir los avisos de vacantes aprobadas.',
                icon: 'warning', showCancelButton: true,
                confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545',
            }).then(function (r) {
                if (!r.isConfirmed) return;
                deleteContacts([id]);
            });
        });
    }

    function updateSelectedCount() {
        var n = state.table.rows({ selected: true }).count();
        document.getElementById('dirBtnDeleteSelected').disabled = n === 0;
    }

    function loadContacts() {
        return apiJson(EP + '?action=list', 'GET').then(function (resp) {
            syncCsrf(resp);
            if (!resp.ok) {
                showAlert('dirAlert', 'danger', resp.error || 'No se pudo cargar el directorio.');
                return;
            }
            var rows = resp.contacts || [];
            state.table.clear().rows.add(rows).draw();
            var n = rows.length;
            document.getElementById('dirCount').textContent = n + (n === 1 ? ' persona' : ' personas');
            updateSelectedCount();
        }).catch(function () {
            showAlert('dirAlert', 'danger', friendlyNetworkError());
        });
    }

    function deleteContacts(ids) {
        return apiJson(EP, 'POST', { action: 'delete', ids: ids }).then(function (resp) {
            if (!resp.ok) {
                showAlert('dirAlert', 'danger', resp.error || 'No se pudo eliminar.');
                return;
            }
            showAlert('dirAlert', 'success',
                resp.deleted === 1 ? 'Se eliminó 1 persona del directorio.'
                    : 'Se eliminaron ' + resp.deleted + ' personas del directorio.');
            loadContacts();
        }).catch(function () {
            showAlert('dirAlert', 'danger', friendlyNetworkError());
        });
    }

    /* ═══════════════════════ Modal ═══════════════════════ */

    function openModal(row) {
        state.editingId = row ? row.id : null;
        document.getElementById('dirModalTitle').textContent = row ? 'Editar persona' : 'Agregar persona';
        document.getElementById('dirFieldId').value = row ? row.id : '';
        document.getElementById('dirFieldName').value = row ? row.full_name : '';
        document.getElementById('dirFieldEmail').value = row ? row.email : '';
        document.getElementById('dirFieldJob').value = row ? row.job_title : '';
        document.getElementById('dirModalAlert').innerHTML = '';
        state.modal.show();
        setTimeout(function () { document.getElementById('dirFieldName').focus(); }, 300);
    }

    function saveContact() {
        var payload = {
            action: state.editingId ? 'update' : 'create',
            full_name: document.getElementById('dirFieldName').value,
            email: document.getElementById('dirFieldEmail').value,
            job_title: document.getElementById('dirFieldJob').value,
        };
        if (state.editingId) payload.id = state.editingId;

        var btn = document.getElementById('dirBtnSave');
        btn.disabled = true;

        apiJson(EP, 'POST', payload).then(function (resp) {
            btn.disabled = false;
            if (!resp.ok) {
                showAlert('dirModalAlert', 'danger', resp.error || 'No se pudo guardar.');
                return;
            }
            state.modal.hide();
            showAlert('dirAlert', 'success',
                payload.action === 'create' ? 'Persona agregada al directorio.' : 'Datos actualizados.');
            loadContacts();
        }).catch(function () {
            btn.disabled = false;
            showAlert('dirModalAlert', 'danger', friendlyNetworkError());
        });
    }

    /* ═══════════════════════ Arranque ═══════════════════════ */

    /** Arranca al terminar de cargar el documento, o de inmediato si ya cargó. */
    function onReady(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    onReady(function () {
        state.modal = new bootstrap.Modal(document.getElementById('dirModal'));
        initTable();
        loadContacts();

        document.getElementById('dirBtnNew').addEventListener('click', function () { openModal(null); });
        document.getElementById('dirBtnSave').addEventListener('click', saveContact);

        // Enter en cualquier campo del modal = guardar.
        ['dirFieldName', 'dirFieldEmail', 'dirFieldJob'].forEach(function (id) {
            document.getElementById(id).addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); saveContact(); }
            });
        });

        document.getElementById('dirBtnDeleteSelected').addEventListener('click', function () {
            var rows = state.table.rows({ selected: true }).data().toArray();
            if (!rows.length) return;
            var ids = rows.map(function (r) { return r.id; });
            Swal.fire({
                title: '¿Eliminar ' + ids.length + (ids.length === 1 ? ' persona?' : ' personas?'),
                text: 'Dejarán de recibir los avisos de vacantes aprobadas.',
                icon: 'warning', showCancelButton: true,
                confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545',
            }).then(function (r) {
                if (r.isConfirmed) deleteContacts(ids);
            });
        });
    });
})();
