(() => {
  'use strict';

  const API = 'controller/practices/areas.php';
  const post = (data) => $.ajax({ url: API, method: 'POST', data, dataType: 'json' });
  const ok   = (msg) => Swal.fire({ icon: 'success', title: '¡Listo!', text: msg, timer: 1600, showConfirmButton: false });
  const err  = (msg) => Swal.fire({ icon: 'error',   title: 'Error',   text: msg });
  const confirm = (title) =>
    Swal.fire({ title, icon: 'question', showCancelButton: true, confirmButtonText: 'Sí', cancelButtonText: 'Cancelar' })
      .then(r => r.isConfirmed);

  let currentAreaId = null;

  function escHtml(s) {
    if (!s) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  // ─── Cargar áreas del encargado ───────────────────────────
  function loadMyAreas() {
    $('#selectorAreas').html('<span class="text-muted small"><i class="fas fa-spinner fa-spin me-1"></i>Cargando áreas…</span>');
    post({ action: 'getMyAreas' })
      .then(areas => {
        if (!Array.isArray(areas) || areas.length === 0) {
          $('#selectorAreas').hide();
          $('#sinAreas').show();
          return;
        }
        const btns = areas.map(a => `
          <button class="btn btn-outline-primary btn-area-selector" data-id="${a.id}" data-nombre="${escHtml(a.nombre)}">
            <i class="fas fa-building-columns me-1"></i>${escHtml(a.nombre)}
          </button>`).join('');
        $('#selectorAreas').html(btns);
        selectArea(areas[0].id);
      })
      .fail((xhr, status, errorThrown) => {
        $('#selectorAreas').html(
          `<div class="alert alert-danger w-100">
            <i class="fas fa-exclamation-triangle me-1"></i>
            Error al cargar las áreas. <small class="text-muted">${status}: ${errorThrown}</small>
            <br><small>Respuesta: ${xhr.responseText?.substring(0, 200)}</small>
          </div>`
        );
      });
  }

  function selectArea(areaId) {
    currentAreaId = areaId;
    $('#panelArea').show();
    // Resaltar botón activo
    $('.btn-area-selector').removeClass('btn-primary').addClass('btn-outline-primary');
    $(`.btn-area-selector[data-id="${areaId}"]`).removeClass('btn-outline-primary').addClass('btn-primary');
    loadAsistencias();
    loadAlumnos();
  }

  // ─── Asistencias pendientes ───────────────────────────────
  function loadAsistencias() {
    if (!currentAreaId) return;
    post({ action: 'getPendingAsistencias', area_id: currentAreaId })
      .fail(() => { $('#tbodyAsistencias').html('<tr><td colspan="5" class="text-center text-danger py-3">Error al cargar asistencias.</td></tr>'); })
      .then(data => {
      const rows = (data || []).map(a => `
        <tr>
          <td>${escHtml(a.nombre_completo)}</td>
          <td>${a.fecha}</td>
          <td>${a.hora_entrada?.slice(0,5)}</td>
          <td>${a.hora_salida?.slice(0,5)}</td>
          <td>
            <button class="ap-btn-icon ap-btn-approve btn-aprobar" data-id="${a.id}" title="Aprobar"><i class="fas fa-check"></i></button>
            <button class="ap-btn-icon ap-btn-reject btn-rechazar" data-id="${a.id}" title="Rechazar"><i class="fas fa-times"></i></button>
          </td>
        </tr>`).join('');
      $('#tbodyAsistencias').html(rows || '<tr><td colspan="5" class="text-center text-muted py-3">Sin asistencias pendientes.</td></tr>');
      $('#badgePendientes').html(`<i class="fas fa-hourglass-half"></i> ${data?.length ?? 0}`);
    });
  }

  // ─── Alumnos activos ──────────────────────────────────────
  function loadAlumnos() {
    if (!currentAreaId) return;
    post({ action: 'getStudentsInArea', area_id: currentAreaId })
      .fail(() => { $('#tbodyAlumnos').html('<tr><td colspan="5" class="text-center text-danger py-3">Error al cargar alumnos.</td></tr>'); })
      .then(data => {
      const list = data || [];
      const finalizados = list.filter(s => s.practicas_finalizadas == 1).length;
      const activos = list.length - finalizados;
      $('#badgeAlumnosActivos').html(`<i class="fas fa-sync-alt"></i> En curso: ${activos}`);
      $('#badgeAlumnosFinalizados').html(`<i class="fas fa-graduation-cap"></i> Finalizados: ${finalizados}`);
      const rows = list.map(s => {
        const fin = s.practicas_finalizadas == 1;
        const estadoBadge = fin
          ? `<span class="ap-badge ap-badge-green"><i class="fas fa-graduation-cap"></i> Finalizado</span>`
          : `<span class="ap-badge ap-badge-blue"><i class="fas fa-sync-alt"></i> En curso</span>`;
        return `
        <tr${fin ? ' class="table-success"' : ''}>
          <td>${escHtml(s.nombre_completo)}</td>
          <td>${escHtml(s.matricula)}</td>
          <td>${escHtml(s.programa_academico)}</td>
          <td><span class="ap-badge ap-badge-purple">${parseFloat(s.horas_acumuladas || 0).toFixed(1)} h</span></td>
          <td>${estadoBadge}</td>
        </tr>`;
      }).join('');
      $('#tbodyAlumnos').html(rows || '<tr><td colspan="5" class="text-center text-muted py-3">Sin alumnos registrados.</td></tr>');
    });
  }

  // ─── Eventos ──────────────────────────────────────────────
  $(document).on('click', '.btn-area-selector', function () {
    selectArea($(this).data('id'));
  });

  $(document).on('click', '.btn-aprobar', async function () {
    const id = $(this).data('id');
    if (!await confirm('¿Aprobar asistencia?')) return;
    post({ action: 'aprobarAsistencia', id }).then(res => {
      if (res.success) { ok(res.message); loadAsistencias(); loadAlumnos(); }
      else err(res.message);
    });
  });

  $(document).on('click', '.btn-rechazar', async function () {
    const id = $(this).data('id');
    if (!await confirm('¿Rechazar asistencia?')) return;
    post({ action: 'rechazarAsistencia', id }).then(res => {
      if (res.success) { ok(res.message); loadAsistencias(); }
      else err(res.message);
    });
  });

  // ─── Init ─────────────────────────────────────────────────
  loadMyAreas();

  // Refresco automático cada 60 s
  setInterval(() => { if (currentAreaId) loadAsistencias(); }, 60000);
})();
