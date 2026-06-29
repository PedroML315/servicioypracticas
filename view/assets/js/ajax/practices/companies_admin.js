(() => {
  'use strict';

  const API  = 'controller/practices/companies.php';
  const post = (data) => $.ajax({ url: API, method: 'POST', data, dataType: 'json' });
  const ok   = (msg)  => Swal.fire({ icon: 'success', title: '¡Listo!', text: msg, timer: 1800, showConfirmButton: false });
  const err  = (msg)  => Swal.fire({ icon: 'error',   title: 'Error',   text: msg });

  function esc(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  // ── Estado organismo ────────────────────────────────────
  const pillOrg = (isAcepted) => {
    switch (String(isAcepted)) {
        case '1': return `<span class="pill pill-accepted"><i class="fas fa-check-circle me-1"></i>Aceptado</span>`;
        case '2': return `<span class="pill pill-rejected"><i class="fas fa-times-circle me-1"></i>Rechazado (Pendiente corrección)</span>`;
        case '3': return `<span class="pill pill-pending"><i class="fas fa-clock me-1"></i>Corregido (Pendiente revisión)</span>`;
        default:  return `<span class="pill pill-new"><i class="fas fa-clock me-1"></i>Nuevo</span>`;
    }
  };

  // ── Estado estudiante en práctica ───────────────────────
  const pillSip = (s) => ({
    0: `<span class="pill pill-pending"><i class="fas fa-clock me-1"></i>Pendiente</span>`,
    1: `<span class="pill pill-accepted"><i class="fas fa-check-circle me-1"></i>Aceptado</span>`,
    2: `<span class="pill pill-rejected"><i class="fas fa-times-circle me-1"></i>Rechazado</span>`,
  }[s] ?? '—');

  // ── Pasos del proceso (sin organismo) ───────────────────
  function procesoPasos(s) {
    const etapa = s.proceso_etapa;
    const st = parseInt(s.student_status ?? 0);
    let steps;
    if (etapa === 'Registro pendiente de aprobación') {
      steps = [
        { lbl: 'Registro', state: 'current' },
        { lbl: 'Aprobado', state: 'wait' },
        { lbl: 'Postulado', state: 'wait' },
        { lbl: 'En práctica', state: 'wait' },
      ];
    } else if (etapa === 'Registro rechazado') {
      steps = [
        { lbl: 'Registro', state: 'done' },
        { lbl: 'Rechazado', state: 'current' },
        { lbl: 'Postulado', state: 'wait' },
        { lbl: 'En práctica', state: 'wait' },
      ];
    } else if (etapa === 'Postulado — esperando respuesta del organismo') {
      steps = [
        { lbl: 'Registro', state: 'done' },
        { lbl: 'Aprobado', state: 'done' },
        { lbl: 'Postulado', state: 'current' },
        { lbl: 'En práctica', state: 'wait' },
      ];
    } else {
      steps = [
        { lbl: 'Registro', state: 'done' },
        { lbl: 'Aprobado', state: 'done' },
        { lbl: 'Postulado', state: 'wait' },
        { lbl: 'En práctica', state: 'wait' },
      ];
    }

    const iconMap = { done: 'fa-check', current: 'fa-circle-dot', wait: '' };
    const html = steps.map(p => `
      <div class="p-step ${p.state}">
        <div class="s-dot"><i class="fas ${iconMap[p.state] || 'fa-circle'} fa-xs"></i></div>
        <div class="s-label">${p.lbl}</div>
      </div>`).join('');

    return `<div class="proceso-steps">${html}</div>`;
  }

  const initials = (name) => {
    const parts = String(name ?? '').trim().split(' ').filter(Boolean);
    if (parts.length === 0) return '?';
    return (parts[0][0] + (parts[1] ? parts[1][0] : '')).toUpperCase();
  };

  let _allOrgs     = [];
  let _sinOrg      = [];
  let _orgFilter   = 'all';
  let _orgSearch   = '';
  let _sinOrgSearch = '';

  function loadDashboard() {
    post({ action: 'get_dashboard' }).then(res => {
      if (!res.success) { err(res.message ?? 'Error al cargar datos'); return; }
      _allOrgs = res.organismos || [];
      _sinOrg  = res.sin_organismo || [];
      updateStats(res.stats || {});
      renderOrganismos();
      renderSinOrganismo();
      $('#badgeOrgs').text(_allOrgs.length);
      $('#badgeSinOrg').text(_sinOrg.length);
    }).fail(() => {
      $('#orgsContainer').html('<div class="alert alert-danger">Error al conectar con el servidor.</div>');
    });
  }

  function updateStats(s) {
    $('#statOrganismos').text(s.organismos_activos ?? '—');
    $('#statEnPractica').text(s.total_en_practica ?? '—');
    $('#statPendientes').text(s.pendientes_org ?? '—');
    $('#statSinOrg').text(s.sin_organismo ?? '—');
    
    // Calcular organismos con strikes basándonos en _allOrgs
    const conStrikes = _allOrgs.filter(o => parseInt(o.strikes_count || 0) > 0).length;
    $('#statConStrikes').text(conStrikes);
  }

  function renderOrganismos() {
    let list = _allOrgs;

    if (_orgFilter !== 'all') {
      list = list.filter(o => String(o.isAcepted) === String(_orgFilter));
    }
    if (_orgSearch) {
      const q = _orgSearch.toLowerCase();
      list = list.filter(o =>
        (o.empresa ?? '').toLowerCase().includes(q) ||
        (o.ciudad  ?? '').toLowerCase().includes(q) ||
        (o.giro    ?? '').toLowerCase().includes(q)
      );
    }

    if (!list.length) {
      $('#orgsContainer').html('<div class="text-center text-muted py-5"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>Sin organismos para mostrar.</div>');
      return;
    }

    const cards = list.map(o => {
      const pending  = parseInt(o.num_pendientes  ?? 0);
      const accepted = parseInt(o.num_aceptados   ?? 0);
      const total    = parseInt(o.num_students_total ?? 0);

      const badges = `
        ${total ? `<span class="badge bg-light text-dark border small">${total} alumno${total !== 1 ? 's' : ''}</span>` : ''}
        ${pending  ? `<span class="pill pill-pending">${pending} pend.</span>` : ''}
        ${accepted ? `<span class="pill pill-accepted">${accepted} activo${accepted !== 1 ? 's' : ''}</span>` : ''}
        ${pillOrg(o.isAcepted)}
        ${o.solicitudes_bloqueadas == 1 ? '<span class="badge bg-danger rounded-pill ms-1" title="Solicitudes Bloqueadas"><i class="fas fa-ban"></i> Bloqueado</span>' : ''}
        ${o.strikes_count > 0 ? `<span class="badge bg-warning text-dark rounded-pill ms-1"><i class="fas fa-exclamation-triangle"></i> ${o.strikes_count} Strike(s)</span>` : ''}
      `;

      const btnDatos = `<button class="btn btn-sm btn-outline-primary rounded-pill px-3 btn-datos-org ms-1" data-id="${o.id}" data-empresa="${esc(o.empresa)}" title="Ver datos del organismo">
             <i class="fas fa-info-circle me-1"></i>Datos
           </button>`;

      const btnSolicitudes = `<button class="btn btn-sm rounded-pill px-3 btn-solicitudes-org ms-1" data-id="${o.id}" data-empresa="${esc(o.empresa)}" title="Ver historial de solicitudes de practicantes" style="background:transparent;border:1.5px solid #2A7E5D;color:#2A7E5D;">
             <i class="fas fa-file-alt me-1"></i>Solicitudes
           </button>`;

      let actionButtons = btnSolicitudes + btnDatos;
      if (o.isAcepted == 0 || o.isAcepted == 3) {
          actionButtons += `<button class="btn btn-sm btn-success rounded-pill px-3 btn-accept-org ms-1" data-id="${o.id}" title="Aceptar organismo"><i class="fas fa-check me-1"></i>Aceptar</button>`;
          actionButtons += `<button class="btn btn-sm btn-outline-danger rounded-pill px-3 btn-reject-org ms-1" data-id="${o.id}" title="Rechazar con motivos"><i class="fas fa-times me-1"></i>Rechazar</button>`;
      } else if (o.isAcepted == 1 || o.isAcepted == 2) {
          if (o.solicitudes_bloqueadas == 1) {
              actionButtons += `<button class="btn btn-sm btn-success rounded-pill px-3 btn-unblock-org ms-1" data-id="${o.id}" title="Desbloquear Solicitudes"><i class="fas fa-lock-open me-1"></i>Desbloquear</button>`;
          } else {
              actionButtons += `<button class="btn btn-sm btn-outline-danger rounded-pill px-3 btn-block-org ms-1" data-id="${o.id}" title="Bloquear Solicitudes"><i class="fas fa-lock me-1"></i>Bloquear</button>`;
          }
          if (o.strikes_count > 0) {
              actionButtons += `<button class="btn btn-sm btn-warning rounded-pill px-3 btn-remove-strike-org ms-1" data-id="${o.id}" title="Remover Strike"><i class="fas fa-eraser me-1"></i>Remover Strike</button>`;
          }
          actionButtons += `<button class="btn btn-sm btn-outline-danger btn-icon btn-disable-org ms-1" data-id="${o.id}" title="Deshabilitar"><i class="fas fa-ban"></i></button>`;
      }
      const actions = actionButtons;

      return `
      <div class="org-card" data-org-id="${o.id}" data-org-acepted="${o.isAcepted}">
        <div class="org-card-header" onclick="toggleOrgCard(this, ${o.id})">
          <div class="org-avatar">${esc(o.empresa[0] ?? '?').toUpperCase()}</div>
          <div>
            <p class="org-name">${esc(o.empresa)}</p>
            <p class="org-meta"><i class="fas fa-map-marker-alt me-1"></i>${esc(o.ciudad ?? '—')}
              ${o.giro ? ` &middot; ${esc(o.giro)}` : ''}
              ${o.nombre_contacto ? ` &middot; <i class="fas fa-user me-1"></i>${esc(o.nombre_contacto)}` : ''}
            </p>
          </div>
          <div class="org-badges">${badges}${actions}</div>
          <i class="fas fa-chevron-down chevron"></i>
        </div>
        <div class="org-students d-none" id="orgStudents_${o.id}">
          <div class="text-center py-3 text-muted small">
            <i class="fas fa-spinner fa-spin me-1" style="color:#01643D;"></i>Cargando practicantes…
          </div>
        </div>
      </div>`;
    }).join('');

    $('#orgsContainer').html(cards);
  }

  window.toggleOrgCard = function(headerEl, orgId) {
    const $header   = $(headerEl);
    const $students = $(`#orgStudents_${orgId}`);

    if ($header.hasClass('open')) {
      $header.removeClass('open');
      $students.addClass('d-none');
      return;
    }

    $header.addClass('open');
    $students.removeClass('d-none');

    if ($students.data('loaded')) return;

    post({ action: 'get_students_by_organismo', organismo_id: orgId }).then(res => {
      $students.data('loaded', true);
      if (!res.success || !res.data.length) {
        $students.html('<p class="text-muted small text-center py-2 mb-0"><i class="fas fa-info-circle me-1"></i>Sin practicantes registrados aún.</p>');
        return;
      }
      const rows = res.data.map(s => `
        <div class="student-row">
          <div class="student-avatar">${esc(initials(s.nombre_completo))}</div>
          <div>
            <p class="student-name">${esc(s.nombre_completo)}</p>
            <p class="student-sub">
              ${esc(s.matricula)} &nbsp;·&nbsp; ${esc(s.programa_academico ?? '—')}
              ${s.modalidad ? ` &nbsp;·&nbsp; ${esc(s.modalidad)}` : ''}
            </p>
          </div>
          <div class="student-meta">
            ${pillSip(s.sip_status)}
            ${s.start_date ? `<div class="text-muted" style="font-size:.7rem;">Inicio: ${esc(s.start_date)}</div>` : ''}
          </div>
        </div>`).join('');
      $students.html(rows);
    });
  };

  function renderSinOrganismo() {
    let list = _sinOrg;
    if (_sinOrgSearch) {
      const q = _sinOrgSearch.toLowerCase();
      list = list.filter(s =>
        (s.nombre_completo ?? '').toLowerCase().includes(q) ||
        (s.matricula       ?? '').toLowerCase().includes(q) ||
        (s.programa_academico ?? '').toLowerCase().includes(q)
      );
    }

    if (!list.length) {
      $('#sinOrgContainer').html('<div class="text-center text-muted py-5"><i class="fas fa-check-circle fa-2x mb-2 d-block" style="color:#01643D;"></i>Todos los alumnos tienen organismo asignado.</div>');
      return;
    }

    const cards = list.map(s => `
      <div class="student-row mb-2">
        <div class="student-avatar">${esc(initials(s.nombre_completo))}</div>
        <div style="flex:1;min-width:0;">
          <p class="student-name">${esc(s.nombre_completo)}</p>
          <p class="student-sub">
            ${esc(s.matricula)} &nbsp;·&nbsp; ${esc(s.programa_academico ?? '—')}
            &nbsp;·&nbsp; Período ${esc(s.periodo ?? '—')}
          </p>
          ${procesoPasos(s)}
          <p class="text-muted mb-0" style="font-size:.7rem;margin-top:.15rem;">
            <i class="fas fa-info-circle me-1"></i>${esc(s.proceso_etapa)}
          </p>
        </div>
        <div class="student-meta">
          <div class="text-muted" style="font-size:.7rem;">Reg. ${(s.fecha_registro ?? '').split(' ')[0]}</div>
        </div>
      </div>`).join('');

    $('#sinOrgContainer').html(`<div>${cards}</div>`);
  }

  $(document).on('click', '.ic-filter-btn', function () {
    $('.ic-filter-btn').removeClass('active').css({ background:'', color:'', borderColor:'' });
    $(this).addClass('active');
    _orgFilter = String($(this).data('orgfilter') ?? 'all');
    renderOrganismos();
  });

  $('#searchOrganismos').on('input', function () {
    _orgSearch = $(this).val().trim();
    renderOrganismos();
  });

  $('#searchSinOrg').on('input', function () {
    _sinOrgSearch = $(this).val().trim();
    renderSinOrganismo();
  });

  $(document).on('click', '.btn-accept-org', function (e) {
    e.stopPropagation();
    const id = $(this).data('id');
    Swal.fire({
      title: '¿Aceptar organismo?',
      text: 'Se enviará un correo con sus credenciales de acceso.',
      icon: 'question', showCancelButton: true,
      confirmButtonText: 'Sí, aceptar', cancelButtonText: 'Cancelar',
      confirmButtonColor: '#01643D',
    }).then(r => {
      if (!r.isConfirmed) return;
      post({ action: 'accept_external', id }).then(res => {
        if (res.success) { ok(res.message); loadDashboard(); }
        else err(res.message);
      });
    });
  });

  $(document).on('click', '.btn-disable-org', function (e) {
    e.stopPropagation();
    const id = $(this).data('id');
    Swal.fire({
      title: '¿Deshabilitar organismo?',
      text: 'El organismo dejará de aparecer en el sistema.',
      icon: 'warning', showCancelButton: true,
      confirmButtonText: 'Sí, deshabilitar', cancelButtonText: 'Cancelar',
      confirmButtonColor: '#dc3545',
    }).then(r => {
      if (!r.isConfirmed) return;
      post({ action: 'disable_external', id }).then(res => {
        if (res.success) { ok(res.message); loadDashboard(); }
        else err(res.message);
      });
    });
  });

  $(document).on('click', '.btn-block-org', function (e) {
    e.stopPropagation();
    const id = $(this).data('id');
    Swal.fire({
      title: 'Bloquear Solicitudes',
      html: `<p>¿Está seguro? Los alumnos activos no se verán afectados.</p><textarea id="swal-motivo-bloqueo" class="swal2-textarea" placeholder="Motivo del bloqueo (Obligatorio)" required></textarea>`,
      icon: 'warning', showCancelButton: true, confirmButtonText: 'Sí, bloquear', cancelButtonText: 'Cancelar', confirmButtonColor: '#dc3545',
      preConfirm: () => {
        const motivo = document.getElementById('swal-motivo-bloqueo').value.trim();
        if (!motivo) { Swal.showValidationMessage('El motivo es obligatorio'); return false; }
        return motivo;
      }
    }).then(r => {
      if (!r.isConfirmed) return;
      post({ action: 'block_external', id, motivo: r.value }).then(res => {
        if (res.success) { ok(res.message); loadDashboard(); } else err(res.message);
      });
    });
  });

  $(document).on('click', '.btn-unblock-org', function (e) {
    e.stopPropagation();
    const id = $(this).data('id');
    Swal.fire({
      title: 'Desbloquear Solicitudes',
      html: `<textarea id="swal-motivo-desbloqueo" class="swal2-textarea" placeholder="Motivo del desbloqueo (Opcional)"></textarea>`,
      icon: 'info', showCancelButton: true, confirmButtonText: 'Sí, desbloquear', cancelButtonText: 'Cancelar', confirmButtonColor: '#01643D',
      preConfirm: () => { return document.getElementById('swal-motivo-desbloqueo').value.trim(); }
    }).then(r => {
      if (!r.isConfirmed) return;
      post({ action: 'unblock_external', id, motivo: r.value }).then(res => {
        if (res.success) { ok(res.message); loadDashboard(); } else err(res.message);
      });
    });
  });

  $(document).on('click', '.btn-remove-strike-org', function (e) {
    e.stopPropagation();
    const id = $(this).data('id');
    Swal.fire({
      title: 'Eliminar Strike',
      text: '¿Está seguro de remover un strike a este organismo?',
      icon: 'warning', showCancelButton: true, confirmButtonText: 'Sí, remover', cancelButtonText: 'Cancelar', confirmButtonColor: '#f5b041'
    }).then(r => {
      if (!r.isConfirmed) return;
      post({ action: 'remove_strike_org', id }).then(res => {
        if (res.success) { ok(res.message); loadDashboard(); } else err(res.message);
      });
    });
  });

  $(document).on('click', '.btn-datos-org', function (e) {
    e.stopPropagation();
    const id      = $(this).data('id');
    const empresa = $(this).data('empresa');
    const $modal  = $('#icDatosModal');
    const $body   = $('#icDatosModalBody');

    $('#icDatosModalTitle').text(empresa);
    $('#icDatosModalSub').text('ID #' + id + ' · Datos completos del organismo');
    $body.html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin" style="color:#01643D;font-size:1.8rem;"></i></div>');
    $modal.modal('show');

    post({ action: 'get_organismo_details', id }).then(res => {
      if (!res.success) { $body.html('<div class="alert alert-danger">' + esc(res.message) + '</div>'); return; }
      const d = res.data;
      const val  = (v) => v ? esc(v) : '<span class="text-muted fst-italic">No registrado</span>';
      const row  = (icon, label, value) => `
        <div class="d-flex align-items-start gap-2 py-2 border-bottom" style="border-color:#f0f0f0!important;">
          <div style="width:28px;height:28px;border-radius:.4rem;background:rgba(1,100,61,.08);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fas ${icon}" style="color:#01643D;font-size:.75rem;"></i>
          </div>
          <div style="min-width:0;">
            <div class="text-muted" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.04em;">${label}</div>
            <div style="font-size:.88rem;font-weight:500;">${value}</div>
          </div>
        </div>`;

      let docsHtml = '<p class="text-muted fst-italic small mb-0">Sin documentos subidos.</p>';
      if (d.documentos && d.documentos.length) {
        const iconExt = (ext) => (['jpg','jpeg','png','gif','webp'].includes(ext) ? 'fa-file-image text-info' : 'fa-file-pdf text-danger');
        docsHtml = d.documentos.map(doc => `
          <a href="${esc(doc.url)}" target="_blank" class="d-flex align-items-center gap-2 p-2 rounded-3 text-decoration-none mb-2"
             style="background:#f8faf9;border:1px solid #e2ede9;">
            <i class="fas ${iconExt(doc.ext)}" style="font-size:1.1rem;"></i>
            <span style="font-size:.82rem;color:#00204a;font-weight:500;word-break:break-all;">${esc(doc.name)}</span>
            <i class="fas fa-external-link-alt ms-auto" style="color:#adb5bd;font-size:.7rem;"></i>
          </a>`).join('');
      }

      const statusBadge = d.isAcepted == 1
        ? '<span class="pill pill-accepted"><i class="fas fa-check-circle me-1"></i>Aceptado</span>'
        : '<span class="pill pill-new"><i class="fas fa-clock me-1"></i>Pendiente</span>';

      $body.html(`
        <div class="d-flex align-items-center gap-2 mb-3">
          ${statusBadge}
          <small class="text-muted">Registrado: ${val(d.created_at ? d.created_at.split(' ')[0] : null)}</small>
        </div>

        <div class="row g-3">
          <div class="col-md-6">
            <div class="p-3 rounded-3 h-100" style="background:#f8faf9;border:1px solid #e2ede9;">
              <p class="fw-bold mb-2" style="color:#01643D;font-size:.8rem;text-transform:uppercase;letter-spacing:.05em;">
                <i class="fas fa-building me-1"></i>Datos Generales
              </p>
              ${row('fa-tag',         'Tipo de persona',      val(d.tipo_persona))}
              ${row('fa-industry',    'Giro / Actividad',     val(d.giro))}
              ${row('fa-calendar',    'Fecha de constitución',val(d.fecha_constitucion))}
              ${row('fa-globe',       'Sitio web',            d.web ? `<a href="${esc(d.web)}" target="_blank" style="color:#01643D;">${esc(d.web)}</a>` : '<span class="text-muted fst-italic">No registrado</span>')}
              ${row('fa-tasks',       'Actividades',          val(d.actividades))}
            </div>
          </div>
          <div class="col-md-6">
            <div class="p-3 rounded-3 h-100" style="background:#f8faf9;border:1px solid #e2ede9;">
              <p class="fw-bold mb-2" style="color:#01643D;font-size:.8rem;text-transform:uppercase;letter-spacing:.05em;">
                <i class="fas fa-map-marker-alt me-1"></i>Domicilio
              </p>
              ${row('fa-road',        'Calle',                val(d.calle))}
              ${row('fa-mail-bulk',   'Código postal',        val(d.cp))}
              ${row('fa-map',         'Colonia',              val(d.colonia))}
              ${row('fa-city',        'Ciudad',               val(d.ciudad))}
            </div>
          </div>
          <div class="col-md-6">
            <div class="p-3 rounded-3 h-100" style="background:#f8faf9;border:1px solid #e2ede9;">
              <p class="fw-bold mb-2" style="color:#01643D;font-size:.8rem;text-transform:uppercase;letter-spacing:.05em;">
                <i class="fas fa-address-card me-1"></i>Contacto
              </p>
              ${row('fa-user',        'Nombre contacto',      val(d.nombre_contacto))}
              ${row('fa-phone',       'Teléfonos',            val(d.telefonos))}
              ${row('fa-mobile-alt',  'Celular',              val(d.celular))}
              ${row('fa-envelope',    'Correo',               d.email ? `<a href="mailto:${esc(d.email)}" style="color:#01643D;">${esc(d.email)}</a>` : '<span class="text-muted fst-italic">No registrado</span>')}
            </div>
          </div>
          <div class="col-md-6">
            <div class="p-3 rounded-3 h-100" style="background:#f8faf9;border:1px solid #e2ede9;">
              <p class="fw-bold mb-2" style="color:#01643D;font-size:.8rem;text-transform:uppercase;letter-spacing:.05em;">
                <i class="fas fa-user-tie me-1"></i>Representante Legal
              </p>
              ${row('fa-user-tie',    'Nombre',               val(d.rep_legal))}
              ${row('fa-briefcase',   'Cargo',                val(d.cargo_legal))}
              ${row('fa-envelope',    'Correo',               d.email_legal ? `<a href="mailto:${esc(d.email_legal)}" style="color:#01643D;">${esc(d.email_legal)}</a>` : '<span class="text-muted fst-italic">No registrado</span>')}
              ${row('fa-phone-office','Tel. oficina',         val(d.tel_oficina))}
            </div>
          </div>
          <div class="col-12">
            <div class="p-3 rounded-3" style="background:#f8faf9;border:1px solid #e2ede9;">
              <p class="fw-bold mb-3" style="color:#01643D;font-size:.8rem;text-transform:uppercase;letter-spacing:.05em;">
                <i class="fas fa-paperclip me-1"></i>Documentos adjuntos
              </p>
              ${docsHtml}
            </div>
          </div>
        </div>`);
    }).fail(() => {
      $body.html('<div class="alert alert-danger">Error al obtener los datos del organismo.</div>');
    });
  });

  function pillSolicitud(s) {
    if (s.aceptado == 1)                           return '<span class="pill pill-accepted"><i class="fas fa-check-circle me-1"></i>Aceptada</span>';
    if (s.activo   == 0 && s.aceptado == 0)        return '<span class="pill pill-rejected"><i class="fas fa-times-circle me-1"></i>Rechazada</span>';
    return '<span class="pill pill-pending"><i class="fas fa-clock me-1"></i>Pendiente</span>';
  }

  const diasMap = { L:'Lunes', M:'Martes', X:'Miércoles', J:'Jueves', V:'Viernes', S:'Sábado', D:'Domingo' };

  function renderSolicitudCard(s) {
    const postulados    = parseInt(s.total_postulados ?? 0);
    const aceptados     = parseInt(s.total_aceptados  ?? 0);
    const fechaCreacion = (s.created_at ?? '').split(' ')[0];
    const fechaUpdate   = (s.updated_at ?? '').split(' ')[0];
    const horario       = `${diasMap[s.dia_inicio] ?? s.dia_inicio} – ${diasMap[s.dia_fin] ?? s.dia_fin}, ${esc(s.hora_inicio ?? '?')} – ${esc(s.hora_fin ?? '?')}`;
    const apoyo         = s.ofrece_apoyo_economico == 1
      ? `<span class="pill" style="background:#d1fae5;color:#065f46;"><i class="fas fa-dollar-sign me-1"></i>Apoyo $${esc(s.monto_apoyo ?? '?')}</span>`
      : '<span class="pill" style="background:#f3f4f6;color:#6b7280;">Sin apoyo económico</span>';

    const postBadge = postulados > 0
      ? `<button class="btn btn-sm rounded-pill btn-ver-postulados ms-1" data-id="${s.id}" style="background:rgba(0,32,74,.08);color:#00204a;border:1px solid #c5cee0;font-size:.72rem;">
           <i class="fas fa-users me-1"></i>Postulados: ${postulados} <span style="color:#065f46;">(${aceptados} aceptados)</span>
         </button>`
      : '<span class="text-muted" style="font-size:.75rem;"><i class="fas fa-users me-1"></i>Sin postulantes</span>';

    return `
    <div class="card border-0 rounded-3 shadow-sm mb-3" style="overflow:hidden;" id="solicitud_card_${s.id}">
      <div class="card-header d-flex align-items-center gap-2 flex-wrap py-2 px-3" style="background:#f8faf9;border-bottom:1px solid #e2ede9;">
        <span style="font-size:.78rem;color:#6c757d;">ID #${esc(s.id)}</span>
        ${pillSolicitud(s)}
        <span class="pill" style="background:rgba(198,219,83,.25);color:#01643D;"><i class="fas fa-graduation-cap me-1"></i>${esc(s.licenciatura)}</span>
        <span class="pill" style="background:#ede9fe;color:#5b21b6;"><i class="fas fa-laptop-house me-1"></i>${esc(s.modalidad)}</span>
        ${apoyo}
        <span class="ms-auto text-muted" style="font-size:.72rem;"><i class="fas fa-calendar me-1"></i>${fechaCreacion}</span>
      </div>
      <div class="card-body p-3">
        <div class="row g-2 mb-2">
          <div class="col-md-4">
            <div style="font-size:.7rem;color:#6c757d;text-transform:uppercase;letter-spacing:.04em;">Practicantes solicitados</div>
            <div style="font-weight:700;font-size:1.1rem;color:#01643D;">${esc(s.num_practicantes)}</div>
          </div>
          <div class="col-md-4">
            <div style="font-size:.7rem;color:#6c757d;text-transform:uppercase;letter-spacing:.04em;">Horario</div>
            <div style="font-size:.83rem;font-weight:500;">${horario}</div>
          </div>
          <div class="col-md-4">
            <div style="font-size:.7rem;color:#6c757d;text-transform:uppercase;letter-spacing:.04em;">Fecha límite incorporación</div>
            <div style="font-size:.83rem;font-weight:500;">${esc(s.fecha_limite)}</div>
          </div>
        </div>
        ${s.actividades ? `<div class="mb-2"><span style="font-size:.7rem;color:#6c757d;text-transform:uppercase;letter-spacing:.04em;">Actividades</span><p class="mb-0" style="font-size:.83rem;">${esc(s.actividades)}</p></div>` : ''}
        ${s.capacidades ? `<div class="mb-2"><span style="font-size:.7rem;color:#6c757d;text-transform:uppercase;letter-spacing:.04em;">Capacidades requeridas</span><p class="mb-0" style="font-size:.83rem;">${esc(s.capacidades)}</p></div>` : ''}
        <div class="d-flex align-items-center gap-2 flex-wrap mt-2">
          ${postBadge}
          <small class="text-muted ms-auto"><i class="fas fa-sync-alt me-1"></i>Actualizado: ${fechaUpdate}</small>
        </div>
        <div class="mt-2" id="postulados_container_${s.id}" style="display:none;"></div>
      </div>
    </div>`;
  }

  $(document).on('click', '.btn-solicitudes-org', function (e) {
    e.stopPropagation();
    const id      = $(this).data('id');
    const empresa = $(this).data('empresa');
    const $modal  = $('#icSolicitudesModal');
    const $body   = $('#icSolicitudesBody');
    const $stats  = $('#icSolicitudesStats');

    $('#icSolicitudesModalLabel').text('Historial de Solicitudes');
    $('#icSolicitudesModalSub').text(empresa + ' · ID #' + id);
    $stats.hide();
    $body.html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin" style="color:#01643D;font-size:1.8rem;"></i></div>');
    $modal.modal('show');

    post({ action: 'get_historial_solicitudes', organismo_id: id }).then(res => {
      if (!res.success) {
        $body.html('<div class="alert alert-danger">' + esc(res.message ?? 'Error') + '</div>');
        return;
      }
      const s = res.stats || {};
      $('#ss-total').text(s.total ?? 0);
      $('#ss-aceptadas').text(s.aceptadas ?? 0);
      $('#ss-rechazadas').text(s.rechazadas ?? 0);
      $('#ss-pendientes').text(s.pendientes ?? 0);
      $('#ss-practicantes').text(s.total_practicantes ?? 0);
      $stats.show();

      if (!res.solicitudes || !res.solicitudes.length) {
        $body.html('<div class="text-center py-5 text-muted"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>Este organismo no tiene solicitudes registradas aún.</div>');
        return;
      }
      $body.html(res.solicitudes.map(renderSolicitudCard).join(''));
    }).fail(() => {
      $body.html('<div class="alert alert-danger">Error al conectar con el servidor.</div>');
    });
  });

  $(document).on('click', '.btn-ver-postulados', function (e) {
    e.stopPropagation();
    const id  = $(this).data('id');
    const $ct = $(`#postulados_container_${id}`);

    if ($ct.is(':visible')) { $ct.slideUp(); return; }
    if ($ct.data('loaded')) { $ct.slideDown(); return; }

    $ct.html('<div class="text-center py-2 text-muted small"><i class="fas fa-spinner fa-spin me-1"></i>Cargando postulantes…</div>').slideDown();

    post({ action: 'get_postulados_solicitud', solicitud_id: id }).then(res => {
      $ct.data('loaded', true);
      if (!res.success || !res.data.length) {
        $ct.html('<p class="text-muted small mb-0 py-1">Sin postulantes registrados.</p>');
        return;
      }
      const rows = res.data.map(p => {
        const sipBadge = ({
          0: '<span class="pill pill-pending" style="font-size:.66rem;">Pendiente</span>',
          1: '<span class="pill pill-accepted" style="font-size:.66rem;">Aceptado</span>',
          2: '<span class="pill pill-rejected" style="font-size:.66rem;">Rechazado</span>',
        }[p.sip_status] ?? '');
        return `
          <div class="d-flex align-items-center gap-2 py-1 px-2 rounded-2 mb-1" style="background:#f0faf5;border:1px solid #c3e6d0;">
            <div class="student-avatar" style="width:28px;height:28px;font-size:.7rem;">${esc(initials(p.nombre_completo))}</div>
            <div style="flex:1;min-width:0;">
              <div style="font-weight:600;font-size:.8rem;">${esc(p.nombre_completo)}</div>
              <div style="font-size:.72rem;color:#6c757d;">${esc(p.matricula)} · ${esc(p.programa_academico ?? '—')}</div>
            </div>
            <div style="text-align:right;flex-shrink:0;">
              ${sipBadge}
              ${p.start_date ? `<div style="font-size:.66rem;color:#6c757d;">Inicio: ${esc(p.start_date)}</div>` : ''}
            </div>
          </div>`;
      }).join('');
      $ct.html(`<div class="mt-2 pt-2" style="border-top:1px solid #e2ede9;">${rows}</div>`);
    });
  });

  // ── Modal Rechazo Detallado ─────────────────────────────
  $(document).on('click', '.btn-reject-org', function (e) {
      e.stopPropagation();
      const id = $(this).data('id');
      
      Swal.fire({ title: 'Cargando...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

      post({ action: 'get_organismo_details', id }).then(res => {
          Swal.close();
          if (!res.success) { err(res.message); return; }

          const org = res.data;
          $('#rechazoOrgId').val(id);
          $('#icRechazoModalSub').text(org.empresa);
          $('#rechazoMotivoGeneral').val('');
          
          const tbody = $('#tablaCamposRechazo tbody');
          tbody.empty();

          const addFieldRow = (key, label, val) => {
              if (val === null || val === '') val = '<em class="text-muted">Vacío</em>';
              tbody.append(`
                  <tr>
                      <td class="text-center">
                          <input class="form-check-input field-cb" type="checkbox" data-campo="${key}" data-label="${esc(label)}" style="transform: scale(1.3);">
                      </td>
                      <td><strong>${esc(label)}</strong></td>
                      <td class="text-break" style="font-size:0.9rem;">${val}</td>
                      <td>
                          <div class="field-inputs d-none">
                              <select class="form-select form-select-sm mb-1 type-select">
                                  <option value="incorrecto">Dato incorrecto</option>
                                  <option value="faltante">Falta información</option>
                                  <option value="invalido">Formato inválido</option>
                              </select>
                              <textarea class="form-control form-control-sm mb-1 reason-text" placeholder="Motivo del error..." rows="1"></textarea>
                              <textarea class="form-control form-control-sm obs-text" placeholder="Observación (opcional)..." rows="1"></textarea>
                          </div>
                      </td>
                  </tr>
              `);
          };

          // Datos Generales
          addFieldRow('empresa', 'Nombre de la Institución/Organismo', esc(org.empresa));
          addFieldRow('tipo_persona', 'Tipo de persona', esc(org.tipo_persona));
          addFieldRow('giro', 'Giro o actividad', esc(org.giro));
          addFieldRow('fecha_constitucion', 'Fecha de constitución', esc(org.fecha_constitucion));
          addFieldRow('web', 'Sitio Web', esc(org.web));
          addFieldRow('actividades', 'Actividades del practicante', esc(org.actividades));

          // Domicilio
          addFieldRow('calle', 'Calle y número', esc(org.calle));
          addFieldRow('colonia', 'Colonia', esc(org.colonia));
          addFieldRow('cp', 'Código Postal', esc(org.cp));
          addFieldRow('ciudad', 'Ciudad', esc(org.ciudad));

          // Contacto Operativo
          addFieldRow('nombre_contacto', 'Nombre del Contacto Operativo', esc(org.nombre_contacto));
          addFieldRow('telefonos', 'Teléfonos (Contacto)', esc(org.telefonos));
          addFieldRow('celular', 'Celular (Contacto)', esc(org.celular));
          addFieldRow('email', 'Correo (Contacto)', esc(org.email));

          // Representante Legal
          addFieldRow('rep_legal', 'Nombre Representante Legal', esc(org.rep_legal));
          addFieldRow('cargo_legal', 'Cargo Representante Legal', esc(org.cargo_legal));
          addFieldRow('tel_oficina', 'Teléfono Oficina (Rep. Legal)', esc(org.tel_oficina));
          addFieldRow('email_legal', 'Correo (Rep. Legal)', esc(org.email_legal));

          // Documentos
          if (org.documentos && org.documentos.length > 0) {
              org.documentos.forEach(doc => {
                  const valHtml = `<a href="${doc.url}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-external-link-alt me-1"></i>Ver Documento</a>`;
                  addFieldRow(`doc:${doc.name}`, `Documento: ${doc.name}`, valHtml);
              });
          }

          $('#icRechazoModal').modal('show');
      });
  });

  $(document).on('change', '.field-cb', function() {
      const inputsDiv = $(this).closest('tr').find('.field-inputs');
      if ($(this).is(':checked')) {
          inputsDiv.removeClass('d-none');
          inputsDiv.find('.reason-text').attr('required', true);
      } else {
          inputsDiv.addClass('d-none');
          inputsDiv.find('.reason-text').removeAttr('required').val('');
          inputsDiv.find('.obs-text').val('');
      }
  });

  $('#btnConfirmarRechazo').click(function() {
      const form = $('#formRechazoOrganismo')[0];
      if (!form.checkValidity()) {
          form.reportValidity();
          return;
      }

      const id = $('#rechazoOrgId').val();
      const motivoGeneral = $('#rechazoMotivoGeneral').val().trim();
      
      const campos = [];
      $('.field-cb:checked').each(function() {
          const tr = $(this).closest('tr');
          campos.push({
              campo: $(this).data('campo'),
              campo_label: $(this).data('label'),
              estado: tr.find('.type-select').val(),
              motivo: tr.find('.reason-text').val().trim(),
              observacion: tr.find('.obs-text').val().trim(),
              valor_original: tr.find('td:nth-child(3)').text().trim()
          });
      });

      if (campos.length === 0) {
          err('Debes seleccionar al menos un campo o documento incorrecto.');
          return;
      }

      const btn = $(this);
      btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Enviando...');

      post({
          action: 'reject_external_with_reasons',
          id: id,
          motivo_general: motivoGeneral,
          campos: JSON.stringify(campos)
      }).then(res => {
          if (res.success) {
              $('#icRechazoModal').modal('hide');
              ok(res.message);
              loadDashboard();
          } else {
              err(res.message);
          }
      }).always(() => {
          btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-2"></i>Enviar Rechazo');
      });
  });

  loadDashboard();
})();
