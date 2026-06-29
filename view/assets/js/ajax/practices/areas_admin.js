(() => {
  'use strict';

  const API  = 'controller/practices/areas.php';
  const post = (data) => $.ajax({ url: API, method: 'POST', data, dataType: 'json' });
  const ok   = (msg)  => Swal.fire({ icon: 'success', title: '¡Listo!', text: msg, timer: 1800, showConfirmButton: false });
  const err  = (msg)  => Swal.fire({ icon: 'error',   title: 'Error',   text: msg });
  const confirmDlg = (title, text) =>
    Swal.fire({ title, text, icon: 'question', showCancelButton: true,
                confirmButtonText: 'Sí', cancelButtonText: 'Cancelar' })
      .then(r => r.isConfirmed);

  function esc(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  const pillOpen   = `<span class="pill pill-open"><i class="fas fa-circle-dot me-1"></i>Abierta</span>`;
  const pillClosed = `<span class="pill pill-closed"><i class="fas fa-circle me-1"></i>Cerrada</span>`;
  const pillStatus = (s, finalizadas) => {
    if (finalizadas == 1) return `<span class="pill pill-finished"><i class="fas fa-graduation-cap me-1"></i>Finalizado</span>`;
    return ({
      0: `<span class="pill pill-pending"><i class="fas fa-clock me-1"></i>Pendiente</span>`,
      1: `<span class="pill pill-accepted"><i class="fas fa-check-circle me-1"></i>Aceptada</span>`,
      2: `<span class="pill pill-rejected"><i class="fas fa-times-circle me-1"></i>Rechazada</span>`,
    }[s] ?? `<span class="pill pill-closed">—</span>`);
  };

  let _allAreas  = [];
  let _allPosts  = [];
  let _activeFilter = 'all';

  function loadAreas() {
    post({ action: 'getAreas' }).then(data => {
      _allAreas = data || [];
      renderAreaCards();
      updateStats();
    });
  }

  function renderAreaCards() {
    if (!_allAreas.length) {
      $('#areasGrid').html(`<div class="col-12 text-center py-5 text-muted"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>No hay áreas creadas aún.</div>`);
      return;
    }
    const cards = _allAreas.map(a => {
      const ocupadas = parseInt(a.postulados ?? 0);
      const pct      = a.cupo > 0 ? Math.round((ocupadas / a.cupo) * 100) : 0;
      const encargado = a.encargado_nombre
        ? `<i class="fas fa-user-tie me-1 text-muted"></i>${esc(a.encargado_nombre + ' ' + (a.encargado_apellido || ''))}`
        : `<span class="text-muted">Sin encargado</span>`;
      return `
      <div class="col-md-4 col-sm-6">
        <div class="card area-card h-100">
          <div class="card-header d-flex justify-content-between align-items-start py-3 px-3">
            <div><h6 class="fw-bold mb-1">${esc(a.nombre)}</h6><small>${encargado}</small></div>
            ${a.isOpen == 1 ? pillOpen : pillClosed}
          </div>
          <div class="card-body px-3 py-2">
            <p class="text-muted small mb-3" style="min-height:2.5em">${esc(a.descripcion || 'Sin descripción.')}</p>
            <div class="d-flex justify-content-between small mb-1">
              <span class="text-muted">Cupo ocupado</span>
              <span class="fw-semibold">${ocupadas} / ${a.cupo}</span>
            </div>
            <div class="cupo-bar mb-1"><div class="cupo-fill" style="width:${pct}%"></div></div>
            <small class="text-muted">${Math.max(0, a.cupo - ocupadas)} vacante${(a.cupo - ocupadas) !== 1 ? 's' : ''}</small>
          </div>
          <div class="card-footer bg-transparent border-0 d-flex gap-2 px-3 pb-3">
            <button class="btn btn-sm btn-outline-primary flex-fill btn-edit-area" data-area='${JSON.stringify(a).replace(/'/g,"&#39;")}'>
              <i class="fas fa-edit me-1"></i>Editar
            </button>
            <button class="btn btn-sm ${a.isOpen == 1 ? 'btn-warning' : 'btn-success'} btn-icon btn-toggle-area"
                    data-id="${a.id}" data-open="${a.isOpen == 1 ? 0 : 1}"
                    title="${a.isOpen == 1 ? 'Cerrar área' : 'Abrir área'}">
              <i class="fas fa-${a.isOpen == 1 ? 'lock' : 'lock-open'}"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger btn-icon btn-delete-area" data-id="${a.id}" title="Eliminar">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>
      </div>`;
    }).join('');
    $('#areasGrid').html(cards);
  }

  function loadPostulaciones() {
    post({ action: 'getPostulaciones' }).then(data => {
      _allPosts = data || [];
      renderPostulaciones();
      updateStats();
    });
  }

  function renderPostulaciones() {
    const filtered = _activeFilter === 'all'
      ? _allPosts
      : _activeFilter === 'finished'
        ? _allPosts.filter(p => p.practicas_finalizadas == 1)
        : _allPosts.filter(p => String(p.status) === String(_activeFilter) && p.practicas_finalizadas != 1);
    if (!filtered.length) {
      $('#tbodyPostulaciones').html(`<tr><td colspan="6" class="text-center text-muted py-4">Sin postulaciones${_activeFilter !== 'all' ? ' en este estado' : ''}.</td></tr>`);
      return;
    }
    const rows = filtered.map(p => {
      let acciones;
      if (p.practicas_finalizadas == 1) {
        acciones = `<span class="badge" style="background:#d1fae5;color:#059669;font-size:.78rem;"><i class="fas fa-graduation-cap me-1"></i>Prácticas finalizadas${p.fecha_finalizacion ? ' · ' + String(p.fecha_finalizacion).split(' ')[0] : ''}</span>`;
      } else if (p.status == 0) {
        acciones = `
          <button class="btn btn-sm btn-success me-1 btn-aceptar" data-id="${p.id}"><i class="fas fa-check me-1"></i>Aceptar</button>
          <button class="btn btn-sm btn-outline-danger btn-rechazar" data-id="${p.id}"><i class="fas fa-times me-1"></i>Rechazar</button>`;
      } else if (p.status == 1) {
        acciones = `
          <button class="btn btn-sm btn-outline-primary btn-ver-evaluacion"
            data-student-id="${p.student_id}" data-postulacion-id="${p.id}" data-nombre="${esc(p.nombre_completo)}">
            <i class="fas fa-clipboard-list me-1"></i>Ver evaluación
          </button>`;
      } else {
        acciones = '—';
      }
      return `<tr>
        <td><strong>${esc(p.nombre_completo)}</strong></td>
        <td class="small text-muted">${esc(p.matricula)}</td>
        <td class="d-none d-md-table-cell small">${esc(p.programa_academico)}</td>
        <td><span class="badge bg-light text-dark border">${esc(p.area_nombre)}</span></td>
        <td>${pillStatus(p.status, p.practicas_finalizadas)}</td>
        <td class="text-muted small">${(p.created_at ?? '').split(' ')[0]}</td>
        <td>${acciones}</td>
      </tr>`;
    }).join('');
    $('#tbodyPostulaciones').html(rows);
  }

  function updateStats() {
    const pending     = _allPosts.filter(p => p.status == 0 && p.practicas_finalizadas != 1).length;
    const accepted    = _allPosts.filter(p => p.status == 1 && p.practicas_finalizadas != 1).length;
    const finalizados = _allPosts.filter(p => p.practicas_finalizadas == 1).length;
    $('#statTotal').text(_allAreas.length);
    $('#statOpen').text(_allAreas.filter(a => a.isOpen == 1).length);
    $('#statPending').text(pending);
    $('#statAccepted').text(accepted);
    if ($('#statFinished').length) $('#statFinished').text(finalizados);
    if (pending > 0) { $('#badgePostPending').text(pending).show(); } else { $('#badgePostPending').hide(); }
  }

  $(document).on('click', '.ia-filter-btn', function () {
    $('.ia-filter-btn').removeClass('active');
    $(this).addClass('active');
    _activeFilter = String($(this).data('filter'));
    renderPostulaciones();
  });

  $(document).on('click', '#btnGuardarArea', function () {
    const id     = $('#areaId').val();
    const nombre = $('#areaNombre').val().trim();
    if (!nombre) { err('El nombre es obligatorio.'); return; }
    const payload = {
      action: id ? 'updateArea' : 'createArea',
      id, nombre,
      descripcion       : $('#areaDescripcion').val().trim(),
      cupo              : $('#areaCupo').val(),
      encargado_user_id : $('#areaEncargado').val(),
    };
    post(payload).then(res => {
      if (res.success) { ok(res.message); $('#modalArea').modal('hide'); loadAreas(); }
      else err(res.message);
    });
  });

  $(document).on('click', '.btn-edit-area', function () {
    const a = $(this).data('area');
    $('#areaId').val(a.id);
    $('#areaNombre').val(a.nombre);
    $('#areaDescripcion').val(a.descripcion || '');
    $('#areaCupo').val(a.cupo);
    $('#areaEncargado').val(a.encargado_user_id || '');
    $('#modalAreaTitle').html('<i class="fas fa-building-columns me-2 text-primary"></i>Editar Área');
    $('#modalArea').modal('show');
  });

  $('#btnNuevaArea').on('click', function () {
    $('#areaId').val('');
    $('#areaNombre, #areaDescripcion').val('');
    $('#areaCupo').val(10);
    $('#areaEncargado').val('');
    $('#modalAreaTitle').html('<i class="fas fa-building-columns me-2 text-primary"></i>Nueva Área');
  });

  $(document).on('click', '.btn-toggle-area', function () {
    const id   = $(this).data('id');
    const open = $(this).data('open');
    post({ action: 'toggleArea', id, isOpen: open }).then(res => {
      if (res.success) { ok(open == 1 ? 'Área abierta.' : 'Área cerrada.'); loadAreas(); }
      else err(res.message);
    });
  });

  $(document).on('click', '.btn-delete-area', async function () {
    const id = $(this).data('id');
    if (!await confirmDlg('¿Eliminar área?', 'Esta acción no se puede deshacer.')) return;
    post({ action: 'deleteArea', id }).then(res => {
      if (res.success) { ok(res.message); loadAreas(); }
      else err(res.message);
    });
  });

  $(document).on('click', '.btn-aceptar', function () {
    $('#postId').val($(this).data('id'));
    $('#postFechaInicio').val(new Date().toISOString().split('T')[0]);
    $('#modalAceptar').modal('show');
  });

  $('#btnConfirmarAceptar').on('click', function () {
    const id    = $('#postId').val();
    const fecha = $('#postFechaInicio').val();
    post({ action: 'aceptarPostulacion', id, fecha_inicio: fecha }).then(res => {
      if (res.success) { ok(res.message + ' Se notificó al alumno.'); $('#modalAceptar').modal('hide'); loadPostulaciones(); loadAreas(); }
      else err(res.message);
    });
  });

  $(document).on('click', '.btn-rechazar', async function () {
    const id = $(this).data('id');
    if (!await confirmDlg('¿Rechazar postulación?', 'El alumno será notificado por correo.')) return;
    post({ action: 'rechazarPostulacion', id }).then(res => {
      if (res.success) { ok(res.message + ' Se notificó al alumno.'); loadPostulaciones(); }
      else err(res.message);
    });
  });

  // ── Ver evaluación ──────────────────────────────────────
  const RUBROS_LABELS_VALS = { 5: 'No aprobado', 6: 'Insuficiente', 7: 'Suficiente', 8: 'Bien', 9: 'Muy bien', 10: 'Sobresaliente' };
  const ACTITUD_VALS       = { TE: 'Totalmente evidente', ME: 'Muy evidente', E: 'Evidente', PE: 'Poco evidente', NE: 'No evidente' };

  function renderEvalSection(evalData, titulo, colorClass) {
    if (!evalData) return `<p class="text-muted fst-italic">Sin evaluación ${titulo.toLowerCase()} registrada.</p>`;

    const rubrosRows = (evalData.rubros || []).map(r => {
      const badge = parseInt(r.calificacion) >= 9 ? 'bg-success' : parseInt(r.calificacion) >= 7 ? 'bg-warning text-dark' : 'bg-danger';
      return `<tr>
        <td>${esc(r.rubro_label)}</td>
        <td class="text-center"><span class="badge ${badge}">${r.calificacion} — ${esc(RUBROS_LABELS_VALS[r.calificacion] || r.calificacion)}</span></td>
      </tr>`;
    }).join('');

    const actitudesRows = (evalData.actitudes || []).map(a => {
      const cls = { TE:'bg-success', ME:'bg-primary', E:'bg-info text-dark', PE:'bg-warning text-dark', NE:'bg-danger' }[a.valor] || 'bg-secondary';
      return `<tr>
        <td class="small">${esc(a.actitud_label)}</td>
        <td class="text-center"><span class="badge ${cls}">${esc(a.valor)} — ${esc(ACTITUD_VALS[a.valor] || a.valor)}</span></td>
      </tr>`;
    }).join('');

    const fecha = evalData.updated_at ? `<small class="text-muted">Actualizado: ${esc(evalData.updated_at.split(' ')[0])}</small>` : '';

    return `
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header ${colorClass} text-white fw-semibold">
          <i class="fas fa-star me-1"></i>${titulo} ${fecha}
        </div>
        <div class="card-body">
          <h6 class="fw-bold mb-2">1. Competencia y calidad en la práctica</h6>
          <div class="table-responsive mb-3">
            <table class="table table-sm table-bordered align-middle mb-0">
              <thead class="table-light"><tr><th>Rubro</th><th class="text-center" style="width:220px">Calificación</th></tr></thead>
              <tbody>${rubrosRows || '<tr><td colspan="2" class="text-muted text-center">Sin datos</td></tr>'}</tbody>
            </table>
          </div>
          ${evalData.fortalezas ? `<p class="mb-1"><strong>Fortalezas:</strong> ${esc(evalData.fortalezas)}</p>` : ''}
          ${evalData.debilidades ? `<p class="mb-3"><strong>Debilidades:</strong> ${esc(evalData.debilidades)}</p>` : ''}
          <h6 class="fw-bold mt-3 mb-2">2. Evaluación de actitudes</h6>
          <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-0">
              <thead class="table-light"><tr><th>Actitud</th><th class="text-center" style="width:220px">Valor</th></tr></thead>
              <tbody>${actitudesRows || '<tr><td colspan="2" class="text-muted text-center">Sin datos</td></tr>'}</tbody>
            </table>
          </div>
        </div>
      </div>`;
  }

  $(document).on('click', '.btn-ver-evaluacion', function () {
    const studentId    = $(this).data('student-id');
    const postulacionId = $(this).data('postulacion-id');
    const nombre        = $(this).data('nombre');

    $('#evalStudentName').text(nombre);
    $('#evalModalBody').html('<div class="text-center py-5 text-muted"><i class="fas fa-spinner fa-spin fa-2x mb-3 d-block"></i>Cargando evaluaciones…</div>');
    $('#modalVerEvaluacion').modal('show');

    post({ action: 'getEvaluaciones', student_id: studentId, postulacion_id: postulacionId })
      .then(res => {
        if (!res.success) { $('#evalModalBody').html(`<div class="alert alert-danger">${esc(res.message)}</div>`); return; }
        const d = res.data || {};
        const hasAny = d.parcial || d.final;
        if (!hasAny) {
          $('#evalModalBody').html('<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Este alumno aún no tiene evaluaciones registradas por el encargado.</div>');
          return;
        }
        $('#evalModalBody').html(
          renderEvalSection(d.parcial, 'Evaluación Parcial', 'bg-primary') +
          renderEvalSection(d.final,   'Evaluación Final',   'bg-success')
        );
      })
      .fail(() => $('#evalModalBody').html('<div class="alert alert-danger">Error al cargar las evaluaciones.</div>'));
  });

  loadAreas();
  loadPostulaciones();
  $('[data-bs-target="#tabPanelPostulaciones"]').on('shown.bs.tab', loadPostulaciones);
  $('[data-bs-target="#tabPanelAreas"]').on('shown.bs.tab', loadAreas);
})();
