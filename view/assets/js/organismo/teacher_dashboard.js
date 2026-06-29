/**
 * teacher_dashboard.js
 * JS para el Dashboard del Encargado/Profesor — v2 (diseño mejorado)
 */

const TEACHER_API = 'controller/practices/teacher.php';
const AREAS_API   = 'controller/practices/areas.php';

// Paleta de colores para avatares
const AVATAR_COLORS = [
    '#2A7E5D','#1e5e44','#15803d','#00594F','#c2410c',
    '#be185d','#7c3aed','#a21caf','#b45309','#01643D'
];

let horasChartInstance = null;
let currentPpAreaId    = 0;

// ══════════════════════════════════════════════════════════
//  Inicialización
// ══════════════════════════════════════════════════════════
$(document).ready(function () {
    loadAllPendientes();
    loadSsStudents();
    loadPpAreas();
    initSearch();
    initAnnotationHandlers();
});

// ══════════════════════════════════════════════════════════
//  Utilidades
// ══════════════════════════════════════════════════════════
function escHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function initials(name) {
    const parts = String(name || '').trim().split(/\s+/);
    return (parts[0]?.[0] || '') + (parts[1]?.[0] || '');
}

function avatarColor(name) {
    let hash = 0;
    for (const c of String(name)) hash = c.charCodeAt(0) + (hash << 5) - hash;
    return AVATAR_COLORS[Math.abs(hash) % AVATAR_COLORS.length];
}

function stuAvatar(name) {
    const bg = avatarColor(name);
    return `<span class="stu-avatar me-2" style="background:${bg};">${escHtml(initials(name).toUpperCase())}</span>`;
}

function statusBadge(status) {
    const map = {
        pendiente: '<span class="badge rounded-pill" style="background:#fef9c3;color:#a16207;">Pendiente</span>',
        aprobada:  '<span class="badge rounded-pill" style="background:#d1ead9;color:#2A7E5D;">Aprobada</span>',
        rechazada: '<span class="badge rounded-pill" style="background:#fee2e2;color:#b91c1c;">Rechazada</span>',
    };
    return map[status] || `<span class="badge bg-secondary">${escHtml(status)}</span>`;
}

function emptyRow(cols, icon, msg) {
    return `<tr><td colspan="${cols}">
        <div class="td-empty">
            <i class="fas ${icon}" style="font-size:2rem;color:#cbd5e1;display:block;margin-bottom:.5rem;"></i>
            ${msg}
        </div>
    </td></tr>`;
}

// ══════════════════════════════════════════════════════════
//  TAB PENDIENTES — carga unificada
// ══════════════════════════════════════════════════════════
function loadAllPendientes() {
    $.post(TEACHER_API, { action: 'getAllPendientes' }, function (res) {
        const posts  = Array.isArray(res.postulaciones) ? res.postulaciones : [];
        const asist  = Array.isArray(res.asistencias)   ? res.asistencias   : [];
        const total  = posts.length + asist.length;

        // Badge en la pestaña
        const $badge = $('#badgeTabPendientes');
        if (total > 0) { $badge.text(total).removeClass('d-none'); }
        else           { $badge.addClass('d-none'); }

        // ── Postulaciones ────────────────────────────────
        $('#badgePendPostulaciones').text(posts.length);
        const $tp = $('#tbodyPendPostulaciones');
        if (!posts.length) {
            $tp.html(emptyRow(6, 'fa-check-circle', 'Sin postulaciones pendientes'));
        } else {
            $tp.html(posts.map(r => {
                const name  = escHtml(r.nombre_completo || '—');
                const fecha = escHtml((r.created_at || '').split(' ')[0]);
                return `<tr>
                    <td>
                        <div class="d-flex align-items-center">
                            ${stuAvatar(r.nombre_completo || '')}
                            <div>
                                <div style="font-size:.875rem;font-weight:600;">${name}</div>
                                <small class="text-muted">${escHtml(r.email || '')}</small>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge bg-light text-dark border">${escHtml(r.matricula || '—')}</span></td>
                    <td class="text-muted small">${escHtml(r.programa_academico || '—')}</td>
                    <td><span class="badge rounded-pill" style="background:#d1ead9;color:#2A7E5D;">${escHtml(r.area_nombre || '—')}</span></td>
                    <td class="text-muted small">${fecha}</td>
                    <td class="text-center">
                        <button class="btn btn-sm me-1"
                            style="background:#d1ead9;color:#2A7E5D;border:1px solid #a8d4bc;border-radius:.5rem;"
                            onclick="aceptarPostulacionGlobal(${r.id})">
                            <i class="fas fa-check me-1"></i>Aceptar
                        </button>
                        <button class="btn btn-sm"
                            style="background:#fee2e2;color:#b91c1c;border:1px solid #fecaca;border-radius:.5rem;"
                            onclick="rechazarPostulacionGlobal(${r.id})">
                            <i class="fas fa-times me-1"></i>Rechazar
                        </button>
                    </td>
                </tr>`;
            }).join(''));
        }

        // ── Asistencias ──────────────────────────────────
        $('#badgePendAsistenciasPP').text(asist.length);
        const $ta = $('#tbodyPendAsistenciasPP');
        if (!asist.length) {
            $ta.html(emptyRow(6, 'fa-check-circle', 'Sin asistencias pendientes'));
        } else {
            $ta.html(asist.map(r => {
                const alumno = escHtml(r.nombre_completo || '—');
                return `<tr>
                    <td>
                        <div class="d-flex align-items-center">
                            ${stuAvatar(r.nombre_completo || '')}
                            <span style="font-size:.875rem;">${alumno}</span>
                        </div>
                    </td>
                    <td><span class="badge rounded-pill" style="background:#d1ead9;color:#2A7E5D;">${escHtml(r.area_nombre || '—')}</span></td>
                    <td><span class="badge bg-light text-dark border">${escHtml(r.fecha)}</span></td>
                    <td><strong>${r.horas ?? '—'}</strong> <small class="text-muted">hrs</small></td>
                    <td class="text-muted small">${escHtml(r.actividad || '—')}</td>
                    <td class="text-center">
                        <button class="btn btn-sm me-1"
                            style="background:#d1ead9;color:#2A7E5D;border:1px solid #a8d4bc;border-radius:.5rem;"
                            onclick="aprobarAsistGlobal(${r.id})">
                            <i class="fas fa-check me-1"></i>Aprobar
                        </button>
                        <button class="btn btn-sm"
                            style="background:#fee2e2;color:#b91c1c;border:1px solid #fecaca;border-radius:.5rem;"
                            onclick="rechazarAsistGlobal(${r.id})">
                            <i class="fas fa-times me-1"></i>Rechazar
                        </button>
                    </td>
                </tr>`;
            }).join(''));
        }

    }, 'json').fail(function () {
        $('#tbodyPendPostulaciones').html(emptyRow(6, 'fa-exclamation-circle', 'Error al cargar'));
        $('#tbodyPendAsistenciasPP').html(emptyRow(6, 'fa-exclamation-circle', 'Error al cargar'));
    });
}

function aceptarPostulacionGlobal(postId) {
    Swal.fire({ title: '¿Aceptar alumno al área?', icon: 'question',
        showCancelButton: true, confirmButtonText: 'Aceptar', cancelButtonText: 'Cancelar',
        confirmButtonColor: '#2A7E5D'
    }).then(r => {
        if (!r.isConfirmed) return;
        $.post(TEACHER_API, { action: 'aceptarPostulacion', postulacion_id: postId }, function (res) {
            if (res.success) {
                Swal.fire({ icon: 'success', title: '¡Aceptado!', timer: 1500, showConfirmButton: false });
                loadAllPendientes();
                if (currentPpAreaId) { loadPpStudents(currentPpAreaId); loadPpPostulaciones(currentPpAreaId); }
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: res.message });
            }
        }, 'json');
    });
}

function rechazarPostulacionGlobal(postId) {
    Swal.fire({ title: '¿Rechazar solicitud?', icon: 'warning',
        showCancelButton: true, confirmButtonText: 'Rechazar', cancelButtonText: 'Cancelar',
        confirmButtonColor: '#b91c1c'
    }).then(r => {
        if (!r.isConfirmed) return;
        $.post(TEACHER_API, { action: 'rechazarPostulacion', postulacion_id: postId }, function (res) {
            if (res.success) {
                Swal.fire({ icon: 'info', title: 'Rechazada', timer: 1500, showConfirmButton: false });
                loadAllPendientes();
                if (currentPpAreaId) loadPpPostulaciones(currentPpAreaId);
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: res.message });
            }
        }, 'json');
    });
}

function aprobarAsistGlobal(id) {
    Swal.fire({ title: 'Aprobar asistencia', icon: 'question',
        showCancelButton: true, confirmButtonText: 'Aprobar', cancelButtonText: 'Cancelar',
        confirmButtonColor: '#2A7E5D'
    }).then(r => {
        if (!r.isConfirmed) return;
        $.post(AREAS_API, { action: 'aprobarAsistencia', id: id }, function (res) {
            if (!res || res.success !== false) {
                Swal.fire({ icon: 'success', title: 'Aprobada', timer: 1200, showConfirmButton: false });
                loadAllPendientes();
                if (currentPpAreaId) { loadPpPending(currentPpAreaId); loadPpStudents(currentPpAreaId); }
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: res.message });
            }
        }, 'json');
    });
}

function rechazarAsistGlobal(id) {
    Swal.fire({ title: 'Rechazar asistencia', icon: 'warning',
        showCancelButton: true, confirmButtonText: 'Rechazar', cancelButtonText: 'Cancelar',
        confirmButtonColor: '#b91c1c'
    }).then(r => {
        if (!r.isConfirmed) return;
        $.post(AREAS_API, { action: 'rechazarAsistencia', id: id }, function (res) {
            if (!res || res.success !== false) {
                Swal.fire({ icon: 'info', title: 'Rechazada', timer: 1200, showConfirmButton: false });
                loadAllPendientes();
                if (currentPpAreaId) loadPpPending(currentPpAreaId);
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: res.message });
            }
        }, 'json');
    });
}

// ══════════════════════════════════════════════════════════
//  Servicio Social – alumnos
// ══════════════════════════════════════════════════════════
function loadSsStudents() {
    $.post(TEACHER_API, { action: 'getSsStudents' }, function (data) {
        const students = Array.isArray(data) ? data : [];
        $('#statSsStudents').text(students.length);
        renderSsStudents(students);
    }, 'json').fail(function () {
        $('#tbodySsStudents').html(emptyRow(5, 'fa-exclamation-circle', 'Error al cargar alumnos'));
    });
}

function renderSsStudents(students) {
    const $tbody = $('#tbodySsStudents');
    if (!students.length) {
        $tbody.html(emptyRow(5, 'fa-users-slash', 'Sin alumnos en tus eventos'));
        return;
    }
    // Mapa global para acceder al objeto sin pasar JSON en onclick
    window._ssStuMap = {};
    let html = '';
    students.forEach((s, idx) => {
        const key  = 'k' + idx;
        window._ssStuMap[key] = s;
        const name  = escHtml(s.firstname + ' ' + s.lastname);
        const nEvts = Array.isArray(s.eventos) ? s.eventos.length : 0;
        html += `<tr data-name="${name.toLowerCase()}">
            <td>
                <div class="d-flex align-items-center">
                    ${stuAvatar(s.firstname + ' ' + s.lastname)}
                    <div>
                        <div class="fw-semibold" style="font-size:.875rem;">${name}</div>
                    </div>
                </div>
            </td>
            <td><span class="badge bg-light text-dark border" style="font-size:.78rem;">${escHtml(s.matricula || '—')}</span></td>
            <td><a href="mailto:${escHtml(s.email)}" class="text-decoration-none small text-primary">${escHtml(s.email)}</a></td>
            <td class="text-center">
                <span class="badge rounded-pill" style="background:#d4eef1;color:#16697a;font-size:.82rem;">
                    <i class="fas fa-calendar-check me-1"></i>${nEvts}
                </span>
            </td>
            <td class="text-center">
                <button class="btn btn-sm btn-ss-hist" data-key="${key}"
                    style="background:#e6f4ee;color:#2A7E5D;border:1px solid #a8d4bc;border-radius:.5rem;">
                    <i class="fas fa-history me-1"></i>Ver historial
                </button>
            </td>
        </tr>`;
    });
    $tbody.html(html);

    // Delegar click en los botones de historial
    $tbody.off('click', '.btn-ss-hist').on('click', '.btn-ss-hist', function () {
        const s = window._ssStuMap[$(this).data('key')];
        if (s) openSsHistory(s);
    });
}

function openSsHistory(s) {
    const nombre  = (s.firstname || '') + ' ' + (s.lastname || '');
    const email   = s.email || '';
    const eventos = Array.isArray(s.eventos) ? s.eventos : [];

    const colors   = ['#2A7E5D','#16697a','#7c3aed','#01643D','#b45309','#be185d'];
    const bg       = colors[(nombre.charCodeAt(0) || 0) % colors.length];
    const initials = nombre.trim().split(/\s+/).map(w => w[0] || '').slice(0,2).join('').toUpperCase();

    $('#modalHistorialAvatar').css('background', bg).text(initials);
    $('#modalHistorialNombre').text(nombre.trim());
    $('#modalHistorialEmail').text(email);

    const statusLabel = {
        0: { txt: 'Pendiente',  color: '#a16207', bg: '#fef9c3', icon: 'fa-hourglass-half' },
        1: { txt: 'Inscrito',   color: '#01643D', bg: '#dbeafe', icon: 'fa-calendar-check'  },
        2: { txt: 'Asistió',    color: '#2A7E5D', bg: '#d1fae5', icon: 'fa-check-circle'    },
        3: { txt: 'No asistió', color: '#b91c1c', bg: '#fee2e2', icon: 'fa-times-circle'    }
    };

    let tlHtml = '';
    if (!eventos.length) {
        tlHtml = '<p class="text-muted text-center py-3">Sin eventos registrados</p>';
    } else {
        eventos.forEach(ev => {
            const lbl  = statusLabel[ev.status] ?? statusLabel[0];
            const fecha = ev.fecha ? ev.fecha.split(' ')[0] : '—';
            const pts   = ev.puntos ? `<span class="badge ms-1" style="background:#fef9c3;color:#a16207;"><i class="fas fa-star me-1"></i>${ev.puntos} pts</span>` : '';
            const lugar = ev.lugar ? `<span class="me-2"><i class="fas fa-map-marker-alt me-1"></i>${ev.lugar}</span>` : '';
            tlHtml += `
            <div class="ss-tl-item">
                <span class="ss-tl-dot" style="background:${lbl.color};box-shadow:0 0 0 2px ${lbl.bg};"></span>
                <div class="ss-tl-card">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-1">
                        <span class="ss-tl-title">${ev.nombre || '—'}</span>
                        <span class="badge" style="background:${lbl.bg};color:${lbl.color};font-size:.75rem;">
                            <i class="fas ${lbl.icon} me-1"></i>${lbl.txt}
                        </span>
                    </div>
                    <div class="ss-tl-meta d-flex flex-wrap align-items-center gap-2 mt-1">
                        <span><i class="fas fa-calendar me-1"></i>${fecha}</span>
                        ${lugar}
                        ${pts}
                    </div>
                </div>
            </div>`;
        });
    }

    $('#timelineSS').html(tlHtml);
    const modal = new bootstrap.Modal(document.getElementById('modalHistorialSS'));
    modal.show();
}

function initSearch() {
    $('#searchSsStudent').on('input', function () {
        const q = $(this).val().toLowerCase();
        $('#tbodySsStudents tr').each(function () {
            const name = $(this).data('name') || '';
            $(this).toggle(name.includes(q));
        });
    });
}

// ══════════════════════════════════════════════════════════
//  Prácticas – áreas
// ══════════════════════════════════════════════════════════
function loadPpAreas() {
    $.post(TEACHER_API, { action: 'getMyAreas' }, function (areas) {
        areas = Array.isArray(areas) ? areas : [];
        $('#statPpAreas').text(areas.length);

        const $btns = $('#ppAreaButtons').empty();
        if (!areas.length) {
            $btns.html(`<div class="text-muted small d-flex align-items-center gap-2">
                <i class="fas fa-info-circle"></i>No tienes areas asignadas de Practicas Profesionales.
            </div>`);
            return;
        }

        areas.forEach(a => {
            $btns.append(
                `<button class="btn btn-outline-primary pp-area-btn"
                    onclick="selectPpArea(${a.id},'${escHtml(a.nombre)}')">
                    <i class="fas fa-folder me-1"></i>${escHtml(a.nombre)}
                </button>`
            );
        });

        // Auto-seleccionar primera área
        if (areas.length) selectPpArea(areas[0].id, areas[0].nombre);
    }, 'json');
}

function selectPpArea(areaId, areaNombre) {
    currentPpAreaId = areaId;
    $('#currentPpAreaId').val(areaId);

    $('#ppAreaButtons .pp-area-btn').removeClass('active');
    $(`#ppAreaButtons .pp-area-btn`).filter((_, el) => $(el).text().trim().includes(areaNombre))
        .addClass('active');

    $('#panelPostulacionesPP, #panelPendingPP, #panelStudentsPP').removeClass('d-none');
    loadPpPostulaciones(areaId);
    loadPpPending(areaId);
    loadPpStudents(areaId);
}

// ── Postulaciones pendientes de alumno al área ──────────
function loadPpPostulaciones(areaId) {
    $.post(TEACHER_API, { action: 'getPostulacionesPendientes', area_id: areaId }, function (data) {
        const rows = Array.isArray(data) ? data : [];
        $('#badgePostulacionesPP').text(rows.length);

        const $tbody = $('#tbodyPostulacionesPP');
        if (!rows.length) {
            $tbody.html(emptyRow(5, 'fa-check-circle', 'Sin solicitudes pendientes'));
            return;
        }
        let html = '';
        rows.forEach(r => {
            const name  = escHtml(r.nombre_completo || '—');
            const fecha = escHtml((r.created_at || '').split(' ')[0]);
            html += `<tr>
                <td>
                    <div class="d-flex align-items-center">
                        ${stuAvatar(r.nombre_completo || '')}
                        <div>
                            <div style="font-size:.875rem;font-weight:600;">${name}</div>
                            <small class="text-muted">${escHtml(r.email || '')}</small>
                        </div>
                    </div>
                </td>
                <td><span class="badge bg-light text-dark border">${escHtml(r.matricula || '—')}</span></td>
                <td class="text-muted small">${escHtml(r.programa_academico || '—')}</td>
                <td class="text-muted small">${fecha}</td>
                <td class="text-center">
                    <button class="btn btn-sm me-1"
                        style="background:#d1ead9;color:#2A7E5D;border:1px solid #a8d4bc;border-radius:.5rem;"
                        onclick="aceptarPostulacion(${r.id},${areaId})">
                        <i class="fas fa-check me-1"></i>Aceptar
                    </button>
                    <button class="btn btn-sm"
                        style="background:#fee2e2;color:#b91c1c;border:1px solid #fecaca;border-radius:.5rem;"
                        onclick="rechazarPostulacion(${r.id},${areaId})">
                        <i class="fas fa-times me-1"></i>Rechazar
                    </button>
                </td>
            </tr>`;
        });
        $tbody.html(html);
    }, 'json').fail(function () {
        $('#tbodyPostulacionesPP').html(emptyRow(5, 'fa-exclamation-circle', 'Error al cargar solicitudes'));
    });
}

function aceptarPostulacion(postulacionId, areaId) {
    Swal.fire({
        title: '¿Aceptar alumno al área?',
        text: 'El alumno podrá comenzar a registrar asistencias.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-check me-1"></i>Aceptar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#2A7E5D'
    }).then(r => {
        if (!r.isConfirmed) return;
        $.post(TEACHER_API, { action: 'aceptarPostulacion', postulacion_id: postulacionId }, function (res) {
            if (res && res.success) {
                Swal.fire({ icon: 'success', title: '¡Aceptado!', text: res.message, timer: 1600, showConfirmButton: false });
                loadPpPostulaciones(areaId);
                loadPpStudents(areaId);
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'No se pudo aceptar.' });
            }
        }, 'json');
    });
}

function rechazarPostulacion(postulacionId, areaId) {
    Swal.fire({
        title: '¿Rechazar solicitud?',
        text: 'Se notificará al alumno que su solicitud no fue aceptada.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-times me-1"></i>Rechazar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#b91c1c'
    }).then(r => {
        if (!r.isConfirmed) return;
        $.post(TEACHER_API, { action: 'rechazarPostulacion', postulacion_id: postulacionId }, function (res) {
            if (res && res.success) {
                Swal.fire({ icon: 'info', title: 'Rechazada', text: res.message, timer: 1600, showConfirmButton: false });
                loadPpPostulaciones(areaId);
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'No se pudo rechazar.' });
            }
        }, 'json');
    });
}

// ── Asistencias pendientes ──────────────────────────────
function loadPpPending(areaId) {
    $.post(AREAS_API, { action: 'getPendingAsistencias', area_id: areaId }, function (data) {
        const rows = Array.isArray(data) ? data : [];
        $('#badgePendingPP').text(rows.length);
        $('#statPendingAtt').text(rows.length);

        const $tbody = $('#tbodyPendingPP');
        if (!rows.length) {
            $tbody.html(emptyRow(5, 'fa-check-circle', 'Sin asistencias pendientes'));
            return;
        }
        let html = '';
        rows.forEach(r => {
            const alumnoName = r.nombre_completo || r.alumno_nombre || 'Alumno';
            html += `<tr>
                <td>
                    <div class="d-flex align-items-center">
                        ${stuAvatar(alumnoName)}
                        <span style="font-size:.875rem;">${escHtml(alumnoName)}</span>
                    </div>
                </td>
                <td><span class="badge bg-light text-dark border">${escHtml(r.fecha)}</span></td>
                <td><strong>${r.horas ?? '—'}</strong> <small class="text-muted">hrs</small></td>
                <td class="text-muted small">${escHtml(r.actividad || '—')}</td>
                <td class="text-center">
                    <button class="btn btn-sm me-1"
                        style="background:#d1ead9;color:#2A7E5D;border:1px solid #a8d4bc;border-radius:.5rem;"
                        onclick="approvePpAttendance(${r.id},${areaId})">
                        <i class="fas fa-check me-1"></i>Aprobar
                    </button>
                    <button class="btn btn-sm"
                        style="background:#fee2e2;color:#b91c1c;border:1px solid #fecaca;border-radius:.5rem;"
                        onclick="rejectPpAttendance(${r.id},${areaId})">
                        <i class="fas fa-times me-1"></i>Rechazar
                    </button>
                </td>
            </tr>`;
        });
        $tbody.html(html);
    }, 'json');
}

function approvePpAttendance(id, areaId) {
    Swal.fire({
        title: 'Aprobar asistencia',
        text: 'Esta accion sumara las horas al alumno.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-check me-1"></i>Aprobar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#2A7E5D'
    }).then(r => {
        if (!r.isConfirmed) return;
        $.post(AREAS_API, { action: 'aprobarAsistencia', id: id }, function (res) {
            if (res && res.success === false) {
                Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'No se pudo aprobar.' });
                return;
            }
            Swal.fire({ icon: 'success', title: 'Aprobada', timer: 1200, showConfirmButton: false });
            loadPpPending(areaId);
            loadPpStudents(areaId);
        }, 'json');
    });
}

function rejectPpAttendance(id, areaId) {
    Swal.fire({
        title: 'Rechazar asistencia',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-times me-1"></i>Rechazar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#b91c1c'
    }).then(r => {
        if (!r.isConfirmed) return;
        $.post(AREAS_API, { action: 'rechazarAsistencia', id: id }, function (res) {
            if (res && res.success === false) {
                Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'No se pudo rechazar.' });
                return;
            }
            Swal.fire({ icon: 'info', title: 'Rechazada', timer: 1200, showConfirmButton: false });
            loadPpPending(areaId);
        }, 'json');
    });
}

// ── Alumnos activos ─────────────────────────────────────
function loadPpStudents(areaId) {
    $.post(TEACHER_API, { action: 'getStudentsInArea', area_id: areaId }, function (students) {
        students = Array.isArray(students) ? students : [];
        $('#statPpStudents').text(students.length);

        const $tbody = $('#tbodyPpStudents');
        if (!students.length) {
            $tbody.html(emptyRow(4, 'fa-user-graduate', 'Sin alumnos activos en esta area'));
            return;
        }
        let pendingReportsTotal = 0;
        let html = '';
        students.forEach(s => {
            const horas  = parseFloat(s.horas_acumuladas || 0);
            const pct    = Math.min(100, (horas / 360) * 100);
            const name   = s.nombre_completo;
            const hasParcial = parseInt(s.reporte_parcial_pendiente || 0) > 0;
            const hasFinal   = parseInt(s.reporte_final_pendiente   || 0) > 0;
            const hasPending = hasParcial || hasFinal;
            if (hasPending) pendingReportsTotal++;

            const reportBadge = hasPending
                ? `<span class="badge ms-1" style="background:#fef9c3;color:#a16207;font-size:.72rem;vertical-align:middle;"
                      title="Reporte pendiente de revisión">
                      <i class="fas fa-file-alt me-1"></i>Reporte pendiente
                   </span>`
                : '';

            html += `<tr${hasPending ? ' style="background:#fffbeb;"' : ''}>
                <td>
                    <div class="d-flex align-items-center">
                        ${stuAvatar(name)}
                        <div>
                            <span class="fw-semibold" style="font-size:.875rem;">${escHtml(name)}</span>
                            ${reportBadge}
                        </div>
                    </div>
                </td>
                <td><span class="badge bg-light text-dark border">${escHtml(s.matricula)}</span></td>
                <td style="min-width:160px;">
                    <div class="d-flex align-items-center gap-2">
                        <div class="td-progress flex-grow-1">
                            <div class="td-progress-bar" style="width:${pct}%;"></div>
                        </div>
                        <span class="small fw-semibold" style="min-width:46px;text-align:right;">${horas.toFixed(1)} h</span>
                    </div>
                </td>
                <td class="text-center">
                    <button class="btn btn-sm d-flex align-items-center gap-1"
                        style="background:#e6f4ee;color:#2A7E5D;border:1px solid #a8d4bc;border-radius:.5rem;"
                        onclick="openPpStudentModal(${s.id},'${escHtml(name)}','${escHtml(s.matricula)}',${areaId},${s.postulacion_id},${hasPending})">
                        <i class="fas fa-chart-bar"></i>
                        <span class="d-none d-sm-inline">Ver detalle</span>
                    </button>
                </td>
            </tr>`;
        });
        // Mostrar contador de reportes pendientes en la sección
        $('#badgePendingReports span').text(pendingReportsTotal);
        $('#badgePendingReports').toggle(pendingReportsTotal > 0);
        $tbody.html(html);
    }, 'json');
}

// ══════════════════════════════════════════════════════════
//  Modal: Detalle alumno PP
// ══════════════════════════════════════════════════════════
function openPpStudentModal(studentId, studentName, matricula, areaId, postulacionId, openReport) {
    $('#currentPpStudentId').val(studentId);
    $('#currentPpAreaId').val(areaId);
    $('#currentPpPostulacionId').val(postulacionId || 0);
    $('#modalAlumnoNombre').text(studentName);
    $('#modalAlumnoMatricula').text(matricula || '');

    const bg  = avatarColor(studentName);
    const ini = initials(studentName).toUpperCase();
    $('#modalAlumnoAvatar').css('background', bg).text(ini);

    // Reset contenidos
    $('#tbodyHistorial').html(`<tr><td colspan="4" class="td-empty"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>`);
    $('#annotationsList').html(`<div class="td-empty"><i class="fas fa-spinner fa-spin"></i></div>`);
    $('#reportContent').html(`<div class="td-empty"><i class="fas fa-spinner fa-spin"></i> Cargando reporte...</div>`);
    $('#badgeReport').hide();
    $('#newAnnotationText').val('');
    $('#badgeAnnotations').text('');

    // Si hay reporte pendiente, abrir directamente en la pestaña Reporte
    const targetTab = openReport ? '#mpReport' : '#mpChart';
    const targetPill = document.querySelector(`#modalAlumnoDetalle [data-bs-target="${targetTab}"]`);
    if (targetPill) bootstrap.Tab.getOrCreateInstance(targetPill).show();

    new bootstrap.Modal(document.getElementById('modalAlumnoDetalle')).show();

    loadPpChart(studentId, areaId);
    loadPpHistory(studentId, areaId);
    loadPpAnnotations(studentId);
    loadPpReport(studentId, postulacionId || 0);
}

// ── Gráfica ─────────────────────────────────────────────
function loadPpChart(studentId, areaId) {
    $.post(TEACHER_API,
        { action: 'getStudentChartData', student_id: studentId, area_id: areaId },
        function (res) {
            const labels = res.labels || [];
            const data   = res.data   || [];

            if (horasChartInstance) { horasChartInstance.destroy(); horasChartInstance = null; }

            const $canvas = $('#horasChart');
            const $noData = $('#chartNoData');

            if (!labels.length) {
                $canvas.hide();
                $noData.removeClass('d-none');
                return;
            }
            $canvas.show();
            $noData.addClass('d-none');

            const ctx = document.getElementById('horasChart').getContext('2d');
            horasChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Horas aprobadas',
                        data: data,
                        backgroundColor: 'rgba(42,126,93,.7)',
                        borderColor: 'rgba(42,126,93,1)',
                        borderWidth: 0,
                        borderRadius: 6,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => ` ${ctx.parsed.y} horas`
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9' },
                            ticks: { font: { size: 12 } },
                            title: { display: true, text: 'Horas', font: { size: 12 } }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 11 } },
                            title: { display: true, text: 'Semana', font: { size: 12 } }
                        }
                    }
                }
            });
        }, 'json'
    );
}

// ── Historial ────────────────────────────────────────────
function loadPpHistory(studentId, areaId) {
    $.post(TEACHER_API,
        { action: 'getStudentAttendanceHistory', student_id: studentId, area_id: areaId },
        function (rows) {
            rows = Array.isArray(rows) ? rows : [];
            const $tbody = $('#tbodyHistorial');
            if (!rows.length) {
                $tbody.html(emptyRow(4, 'fa-calendar-times', 'Sin registros de asistencia'));
                return;
            }
            let html = '';
            rows.forEach(r => {
                html += `<tr>
                    <td><span class="badge bg-light text-dark border">${escHtml(r.fecha)}</span></td>
                    <td><strong>${r.horas}</strong> <small class="text-muted">hrs</small></td>
                    <td>${statusBadge(r.status)}</td>
                    <td class="text-muted small">${escHtml(r.actividad || '—')}</td>
                </tr>`;
            });
            $tbody.html(html);
        }, 'json'
    );
}

// ── Reporte parcial / final ──────────────────────────────
function loadPpReport(studentId, postulacionId) {
    if (!postulacionId) {
        $('#reportContent').html(`<div class="td-empty"><i class="fas fa-info-circle" style="font-size:2rem;color:#cbd5e1;display:block;margin-bottom:.5rem;"></i>Sin información de postulación</div>`);
        return;
    }
    $.post(TEACHER_API, { action: 'getStudentReport', student_id: studentId, postulacion_id: postulacionId }, function (res) {
        renderPpReport(res, studentId, postulacionId);
    }, 'json').fail(function () {
        $('#reportContent').html(`<div class="td-empty text-danger"><i class="fas fa-exclamation-circle"></i> Error al cargar el reporte.</div>`);
    });
}

function reportStatusBadge(aproveOrganismo, aproveAdmin, completed) {
    if (completed == 1)      return `<span class="badge rounded-pill" style="background:#d1ead9;color:#2A7E5D;"><i class="fas fa-check-double me-1"></i>Completado</span>`;
    if (aproveOrganismo == 2) return `<span class="badge rounded-pill" style="background:#fee2e2;color:#b91c1c;"><i class="fas fa-times me-1"></i>Rechazado por encargado</span>`;
    if (aproveAdmin == 2)     return `<span class="badge rounded-pill" style="background:#fee2e2;color:#b91c1c;"><i class="fas fa-times me-1"></i>Rechazado por admin</span>`;
    if (aproveOrganismo == 1 && aproveAdmin == 0) return `<span class="badge rounded-pill" style="background:#dbeafe;color:#1d4ed8;"><i class="fas fa-clock me-1"></i>En revisión del admin</span>`;
    if (aproveOrganismo == 0) return `<span class="badge rounded-pill" style="background:#fef9c3;color:#a16207;"><i class="fas fa-hourglass-half me-1"></i>Pendiente de tu revisión</span>`;
    return `<span class="badge bg-secondary">Desconocido</span>`;
}

function renderReportCard(report, type, studentId, postulacionId) {
    if (!report) {
        return `<div class="alert alert-light border text-muted small mb-0 p-3" style="border-radius:.75rem;">
            <i class="fas fa-file-slash me-1"></i>El alumno aún no ha entregado el reporte ${type === 'parcial' ? 'parcial' : 'final'}.
        </div>`;
    }

    const isParcial = type === 'parcial';
    const id        = isParcial ? report.idReporteParcial : report.idReporteFinal;
    const aOrg      = parseInt(report.aproveOrganismo ?? 0);
    const aAdmin    = parseInt(report.aproveAdmin ?? 0);
    const completed = parseInt(report.completed ?? 0);
    const badge     = reportStatusBadge(aOrg, aAdmin, completed);

    // Contenido del reporte
    let contenido = '';
    if (isParcial) {
        contenido = `
        <div class="mb-2"><span class="fw-semibold small text-muted text-uppercase" style="letter-spacing:.04em;">Objetivo general</span>
            <p class="mb-0 mt-1" style="font-size:.9rem;">${escHtml(report.objetivo || '—')}</p>
        </div>
        <div class="mb-2"><span class="fw-semibold small text-muted text-uppercase" style="letter-spacing:.04em;">Actividades reportadas</span>
            <p class="mb-0 mt-1" style="font-size:.9rem;">${escHtml(report.actividades_repotadas || '—')}</p>
        </div>`;
    } else {
        contenido = `
        <div class="mb-2"><span class="fw-semibold small text-muted text-uppercase" style="letter-spacing:.04em;">Objetivo general</span>
            <p class="mb-0 mt-1" style="font-size:.9rem;">${escHtml(report.objetivo_general || '—')}</p>
        </div>
        <div class="mb-2"><span class="fw-semibold small text-muted text-uppercase" style="letter-spacing:.04em;">Actividades realizadas</span>
            <p class="mb-0 mt-1" style="font-size:.9rem;">${escHtml(report.actividades_realizadas || '—')}</p>
        </div>
        <div class="mb-2"><span class="fw-semibold small text-muted text-uppercase" style="letter-spacing:.04em;">Resultados obtenidos</span>
            <p class="mb-0 mt-1" style="font-size:.9rem;">${escHtml(report.resultados_obtenidos || '—')}</p>
        </div>
        <div class="mb-2"><span class="fw-semibold small text-muted text-uppercase" style="letter-spacing:.04em;">Capacitación recibida</span>
            <p class="mb-0 mt-1" style="font-size:.9rem;">${escHtml(report.capacitacion_recibida || '—')}</p>
        </div>
        <div class="mb-2"><span class="fw-semibold small text-muted text-uppercase" style="letter-spacing:.04em;">Experiencia profesional</span>
            <p class="mb-0 mt-1" style="font-size:.9rem;">${escHtml(report.experiencia_profesional || '—')}</p>
        </div>
        <div class="mb-2"><span class="fw-semibold small text-muted text-uppercase" style="letter-spacing:.04em;">Experiencia personal</span>
            <p class="mb-0 mt-1" style="font-size:.9rem;">${escHtml(report.experiencia_personal || '—')}</p>
        </div>`;
    }

    // Comentarios de rechazo
    let comentariosHtml = '';
    if ((aOrg === 2 || aAdmin === 2) && report.comentarios) {
        comentariosHtml = `<div class="alert alert-danger p-2 small mt-2 mb-0" style="border-radius:.5rem;">
            <strong>Motivo del rechazo:</strong> ${escHtml(report.comentarios)}
        </div>`;
    }

    // Botones de acción (solo si pendiente de revisión del encargado)
    let acciones = '';
    if (aOrg === 0 && completed === 0) {
        const acceptAction = isParcial ? `acceptPpReport(${id},${studentId},${postulacionId})`  : `acceptPpFinalReport(${id},${studentId},${postulacionId})`;
        const rejectAction = isParcial ? `rejectPpReport(${id},${studentId},${postulacionId})`  : `rejectPpFinalReport(${id},${studentId},${postulacionId})`;
        acciones = `
        <div class="d-flex gap-2 mt-3">
            <button class="btn btn-sm flex-fill" style="background:#d1ead9;color:#2A7E5D;border:1px solid #a8d4bc;border-radius:.5rem;"
                onclick="${acceptAction}">
                <i class="fas fa-check me-1"></i>Aprobar reporte
            </button>
            <button class="btn btn-sm flex-fill" style="background:#fee2e2;color:#b91c1c;border:1px solid #fecaca;border-radius:.5rem;"
                onclick="${rejectAction}">
                <i class="fas fa-times me-1"></i>Rechazar reporte
            </button>
        </div>`;
    }

    return `
    <div class="card border-0 shadow-sm mb-3" style="border-radius:.75rem;overflow:hidden;">
        <div class="card-header d-flex justify-content-between align-items-center py-2 px-3"
            style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
            <span class="fw-semibold" style="font-size:.9rem;">
                <i class="fas fa-file-alt me-1" style="color:#2A7E5D;"></i>
                Reporte ${isParcial ? 'Parcial' : 'Final'}
            </span>
            ${badge}
        </div>
        <div class="card-body px-3 py-3">
            ${contenido}
            ${comentariosHtml}
            ${acciones}
        </div>
    </div>`;
}

function renderPpReport(res, studentId, postulacionId) {
    const hasPending = (res.parcial && parseInt(res.parcial.aproveOrganismo ?? 0) === 0 && parseInt(res.parcial.completed ?? 0) === 0)
                    || (res.final   && parseInt(res.final.aproveOrganismo   ?? 0) === 0 && parseInt(res.final.completed   ?? 0) === 0);

    $('#badgeReport').toggle(hasPending);

    const html = renderReportCard(res.parcial, 'parcial', studentId, postulacionId)
               + renderReportCard(res.final,   'final',   studentId, postulacionId);
    $('#reportContent').html(html);
}

// Aprobar / rechazar reporte parcial
function acceptPpReport(reportId, studentId, postulacionId) {
    Swal.fire({
        title: 'Aprobar reporte parcial',
        text: 'Para aprobar deberás evaluar al alumno. ¿Continuar?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-clipboard-check me-1"></i>Evaluar y aprobar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#2A7E5D'
    }).then(r => {
        if (!r.isConfirmed) return;

        const $evalModal = $('#evaluacionParcialParticipanteModal');
        $evalModal.data({ reportId: reportId, studentId: studentId, postulacionId: postulacionId });

        $('#evaluacionParticipanteForm').off('submit.teacher').on('submit.teacher', async function (e) {
            e.preventDefault();
            const ctx = $evalModal.data();
            const formData = new FormData(this);
            formData.append('action', 'evaluarParticipanteParcial');
            formData.append('idStudent', ctx.studentId);
            formData.append('idPractica', ctx.postulacionId);
            formData.append('idReporteParcial', ctx.reportId);
            try {
                const resp = await $.ajax({
                    url: TEACHER_API, method: 'POST',
                    data: formData, processData: false, contentType: false, dataType: 'json'
                });
                if (!resp?.success) {
                    Swal.fire('Error', resp?.message || 'No se pudo guardar la evaluación.', 'error');
                    return;
                }
                await $.post(TEACHER_API, { action: 'acceptReport', report_id: ctx.reportId });
                $evalModal.modal('hide');
                await Swal.fire({ icon: 'success', title: 'Evaluación guardada y reporte aprobado', timer: 1600, showConfirmButton: false });
                loadPpReport(ctx.studentId, ctx.postulacionId);
                loadPpStudents(currentPpAreaId);
            } catch (err) {
                Swal.fire('Error', 'Fallo de red o servidor.', 'error');
            }
        });

        setTimeout(() => $evalModal.modal('show'), 300);
    });
}

function rejectPpReport(reportId, studentId, postulacionId) {
    Swal.fire({
        title: 'Rechazar reporte parcial',
        input: 'textarea',
        inputLabel: 'Motivo del rechazo',
        inputPlaceholder: 'Escribe el motivo...',
        inputAttributes: { rows: 3 },
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-times me-1"></i>Rechazar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#b91c1c',
        preConfirm: (val) => {
            if (!val || !val.trim()) { Swal.showValidationMessage('El motivo es requerido'); }
            return val;
        }
    }).then(r => {
        if (!r.isConfirmed) return;
        $.post(TEACHER_API, { action: 'rejectReport', report_id: reportId, comentarios: r.value }, function (res) {
            if (res && res.success === false) {
                Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'No se pudo rechazar.' });
                return;
            }
            Swal.fire({ icon: 'info', title: 'Reporte rechazado', timer: 1400, showConfirmButton: false });
            loadPpReport(studentId, postulacionId);
        }, 'json');
    });
}

// Aprobar / rechazar reporte final
function acceptPpFinalReport(reportId, studentId, postulacionId) {
    Swal.fire({
        title: 'Aprobar reporte final',
        text: 'Para aprobar deberás evaluar al alumno. ¿Continuar?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-clipboard-check me-1"></i>Evaluar y aprobar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#2A7E5D'
    }).then(r => {
        if (!r.isConfirmed) return;

        const $evalModal = $('#evaluacionFinalParticipanteModal');
        $evalModal.data({ reportId: reportId, studentId: studentId, postulacionId: postulacionId });

        $('#evaluacionFinalParticipanteForm').off('submit.teacher').on('submit.teacher', async function (e) {
            e.preventDefault();
            const ctx = $evalModal.data();
            const formData = new FormData(this);
            formData.append('action', 'evaluarParticipanteFinal');
            formData.append('idStudent', ctx.studentId);
            formData.append('idPractica', ctx.postulacionId);
            formData.append('idReporteFinal', ctx.reportId);
            try {
                const resp = await $.ajax({
                    url: TEACHER_API, method: 'POST',
                    data: formData, processData: false, contentType: false, dataType: 'json'
                });
                if (!resp?.success) {
                    Swal.fire('Error', resp?.message || 'No se pudo guardar la evaluación.', 'error');
                    return;
                }
                await $.post(TEACHER_API, { action: 'acceptFinalReport', report_id: ctx.reportId });
                $evalModal.modal('hide');
                await Swal.fire({ icon: 'success', title: 'Evaluación guardada y reporte final aprobado', timer: 1600, showConfirmButton: false });
                loadPpReport(ctx.studentId, ctx.postulacionId);
                loadPpStudents(currentPpAreaId);
            } catch (err) {
                Swal.fire('Error', 'Fallo de red o servidor.', 'error');
            }
        });

        setTimeout(() => $evalModal.modal('show'), 300);
    });
}

function rejectPpFinalReport(reportId, studentId, postulacionId) {
    Swal.fire({
        title: 'Rechazar reporte final',
        input: 'textarea',
        inputLabel: 'Motivo del rechazo',
        inputPlaceholder: 'Escribe el motivo...',
        inputAttributes: { rows: 3 },
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-times me-1"></i>Rechazar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#b91c1c',
        preConfirm: (val) => {
            if (!val || !val.trim()) { Swal.showValidationMessage('El motivo es requerido'); }
            return val;
        }
    }).then(r => {
        if (!r.isConfirmed) return;
        $.post(TEACHER_API, { action: 'rejectFinalReport', report_id: reportId, comentarios: r.value }, function (res) {
            if (res && res.success === false) {
                Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'No se pudo rechazar.' });
                return;
            }
            Swal.fire({ icon: 'info', title: 'Reporte final rechazado', timer: 1400, showConfirmButton: false });
            loadPpReport(studentId, postulacionId);
        }, 'json');
    });
}

// ── Anotaciones PP ───────────────────────────────────────
function loadPpAnnotations(studentId) {
    $.post(TEACHER_API,
        { action: 'getAnnotations', type: 'practicas', student_id: studentId },
        function (annotations) {
            annotations = Array.isArray(annotations) ? annotations : [];
            const badge = annotations.length ? String(annotations.length) : '';
            $('#badgeAnnotations').text(badge);
            renderAnnotations(annotations, '#annotationsList', 'practicas');
        }, 'json'
    );
}

// ══════════════════════════════════════════════════════════
//  Annotation handlers
// ══════════════════════════════════════════════════════════
function initAnnotationHandlers() {
    // PP
    $('#btnAddAnnotation').on('click', function () {
        const studentId = parseInt($('#currentPpStudentId').val());
        const nota = $('#newAnnotationText').val().trim();
        if (!nota) return;
        $.post(TEACHER_API,
            { action: 'addAnnotation', type: 'practicas', student_id: studentId, nota },
            function (res) {
                if (res.status === 'success') {
                    $('#newAnnotationText').val('');
                    loadPpAnnotations(studentId);
                }
            }, 'json'
        );
    });

    // SS
    $('#btnAddSsAnnotation').on('click', function () {
        const studentId = parseInt($('#currentSsStudentId').val());
        const nota = $('#newSsAnnotationText').val().trim();
        if (!nota) return;
        $.post(TEACHER_API,
            { action: 'addAnnotation', type: 'servicio', student_id: studentId, nota },
            function (res) {
                if (res.status === 'success') {
                    $('#newSsAnnotationText').val('');
                    loadSsAnnotations(studentId);
                }
            }, 'json'
        );
    });

    // Enter en textarea → agregar (Ctrl+Enter)
    $('#newAnnotationText, #newSsAnnotationText').on('keydown', function (e) {
        if (e.ctrlKey && e.key === 'Enter') {
            $(this).closest('.modal-body').find('button[id^="btnAdd"]').trigger('click');
        }
    });
}

function renderAnnotations(annotations, containerId, type) {
    const $el = $(containerId);
    if (!annotations.length) {
        $el.html(`<div class="td-empty">
            <i class="fas fa-sticky-note" style="font-size:2rem;color:#cbd5e1;display:block;margin-bottom:.5rem;"></i>
            <p class="mb-0">Sin anotaciones registradas</p>
        </div>`);
        return;
    }
    let html = '';
    annotations.forEach(a => {
        html += `<div class="annotation-item d-flex justify-content-between align-items-start gap-2">
            <div class="flex-grow-1">
                <p class="mb-1" style="font-size:.875rem;line-height:1.5;">${escHtml(a.nota)}</p>
                <small class="text-muted"><i class="fas fa-clock me-1"></i>${escHtml(a.created_at)}</small>
            </div>
            <button class="btn btn-sm p-1"
                style="color:#94a3b8;border:none;background:none;flex-shrink:0;"
                onclick="deleteAnnotation(${a.id},'${type}',${a.student_id})"
                title="Eliminar">
                <i class="fas fa-trash-alt"></i>
            </button>
        </div>`;
    });
    $el.html(html);
}

function deleteAnnotation(id, type, studentId) {
    Swal.fire({
        icon: 'warning',
        title: 'Eliminar anotacion',
        text: 'Esta accion no se puede deshacer.',
        showCancelButton: true,
        confirmButtonText: 'Eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#b91c1c'
    }).then(r => {
        if (!r.isConfirmed) return;
        $.post(TEACHER_API, { action: 'deleteAnnotation', annotation_id: id }, function () {
            if (type === 'practicas') loadPpAnnotations(studentId);
            else loadSsAnnotations(studentId);
        }, 'json');
    });
}

// ══════════════════════════════════════════════════════════
//  Modal: Anotaciones alumno SS
// ══════════════════════════════════════════════════════════
function openSsAnnotations(studentId, studentName) {
    $('#currentSsStudentId').val(studentId);
    $('#modalSsNombre').text(studentName);
    $('#newSsAnnotationText').val('');
    loadSsAnnotations(studentId);
    new bootstrap.Modal(document.getElementById('modalSsAnnotations')).show();
}

function loadSsAnnotations(studentId) {
    $.post(TEACHER_API,
        { action: 'getAnnotations', type: 'servicio', student_id: studentId },
        function (annotations) {
            annotations = Array.isArray(annotations) ? annotations : [];
            renderAnnotations(annotations, '#ssAnnotationsList', 'servicio');
        }, 'json'
    );
}
