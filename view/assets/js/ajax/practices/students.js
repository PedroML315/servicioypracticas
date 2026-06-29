(() => {
  'use strict';

  const API  = 'controller/practices/students.php';
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

  const pillStatus = (isAcepted, finalizadas, baja) => {
    if (baja == 1) return `<span class="badge bg-danger rounded-pill"><i class="fas fa-ban me-1"></i>Baja (Strike)</span>`;
    if (finalizadas == 1) return `<span class="pill pill-finished"><i class="fas fa-graduation-cap me-1"></i>Finalizado</span>`;
    return ({
      0: `<span class="pill pill-pending"><i class="fas fa-clock me-1"></i>Pendiente</span>`,
      1: `<span class="pill pill-accepted"><i class="fas fa-check-circle me-1"></i>Aceptado</span>`,
      2: `<span class="pill pill-rejected"><i class="fas fa-times-circle me-1"></i>Rechazado</span>`,
    }[isAcepted] ?? `<span class="pill pill-inactive">—</span>`);
  };

  const tipoPill = (t) => {
    if (!t) return '—';
    const map = { empresa: ['bg-primary', 'fa-building', 'Externo'], universidad: ['bg-success', 'fa-university', 'Interno'] };
    const [cls, ico, lbl] = map[t] ?? ['bg-secondary', 'fa-question', t];
    return `<span class="badge ${cls} rounded-pill"><i class="fas ${ico} me-1"></i>${lbl}</span>`;
  };

  let _all = [];
  let _activeFilter = 'all';

  function loadStudents() {
    $('#tbodyStudents').html(`<tr><td colspan="9" class="text-center py-5 text-muted"><i class="fas fa-spinner fa-spin me-2" style="color:#01643D;"></i>Cargando alumnos...</td></tr>`);
    post({ action: 'get_students_practices' }).then(data => {
      _all = Array.isArray(data) ? data : [];
      renderTable();
      updateStats();
    }).fail(() => {
      $('#tbodyStudents').html(`<tr><td colspan="9" class="text-center text-danger py-4"><i class="fas fa-exclamation-circle me-2"></i>Error al cargar los alumnos.</td></tr>`);
    });
  }

  function renderTable() {
    const filtered = _activeFilter === 'all'
      ? _all
      : _activeFilter === 'finished'
        ? _all.filter(s => s.practicas_finalizadas == 1)
        : _all.filter(s => String(s.isAcepted) === String(_activeFilter) && s.practicas_finalizadas != 1);

    if (!filtered.length) {
      $('#tbodyStudents').html(`<tr><td colspan="9"><div class="is-empty"><div class="is-empty-icon"><i class="fas fa-inbox"></i></div><h5>Sin alumnos</h5><p>${_activeFilter !== 'all' ? 'No hay alumnos en este estado.' : 'Aún no hay alumnos registrados.'}</p></div></td></tr>`);
      return;
    }

    const rows = filtered.map((s, i) => {
      const finalizadas = s.practicas_finalizadas == 1;
      const initials = (s.nombre_completo ?? '').split(' ').slice(0,2).map(w=>w[0]?.toUpperCase()||'').join('');
      let acciones = '';
      if (s.isAcepted == 0) {
        acciones = `
          <button class="is-btn-icon is-btn-accept btn-accept-student" data-id="${s.id}" title="Aceptar"><i class="fas fa-check"></i></button>
          <button class="is-btn-icon is-btn-reject btn-denegate-student" data-id="${s.id}" title="Rechazar"><i class="fas fa-times"></i></button>`;
      } else if (s.isAcepted == 1 && !finalizadas && s.dado_de_baja_por_strike != 1) {
        acciones = `
          <button class="is-btn-icon is-btn-edit btn-edit-student" data-id="${s.id}" title="Editar"><i class="fas fa-edit"></i></button>
          <button class="is-btn-icon is-btn-disable btn-disable-student" data-id="${s.id}" title="Deshabilitar"><i class="fas fa-user-slash"></i></button>
          <button class="is-btn-icon btn-resend-credentials-student ms-1" style="background:#e0e7ff;color:#4f46e5;" data-id="${s.id}" title="Regenerar contraseña y enviar correo"><i class="fas fa-key"></i></button>`;
      } else if (finalizadas) {
        acciones = `
          <button class="is-btn-icon is-btn-edit btn-edit-student" data-id="${s.id}" title="Editar"><i class="fas fa-edit"></i></button>`;
      }
      
      if (s.dado_de_baja_por_strike == 1) {
          acciones += `<button class="is-btn-icon btn-unblock-student ms-1" style="color:#28a745;" data-id="${s.id}" title="Desbloquear Alumno"><i class="fas fa-lock-open"></i></button>`;
      }
      if (s.strikes_count > 0) {
          acciones += `<button class="is-btn-icon btn-remove-strike-student ms-1" style="color:#ffc107;" data-id="${s.id}" title="Eliminar Strike"><i class="fas fa-eraser"></i></button>`;
      }
      acciones += `<button class="is-btn-icon btn-hard-reset-student ms-1" style="color:#dc3545;" data-id="${s.id}" title="Hard Reset"><i class="fas fa-skull-crossbones"></i></button>`;
      
      return `<tr>
        <td class="text-muted small">${i + 1}</td>
        <td>
          <div class="d-flex align-items-center gap-2">
            <div class="stu-avatar">${initials || '?'}</div>
            <div>
              <div class="fw-semibold">${esc(s.nombre_completo)}</div>
              <small class="text-muted">${esc(s.email)}</small>
            </div>
          </div>
        </td>
        <td class="small">${esc(s.matricula)}</td>
        <td class="d-none d-md-table-cell small">${esc(s.programa_academico ?? '—')}</td>
        <td class="d-none d-md-table-cell small text-center">${esc(s.periodo ?? '—')}</td>
        <td class="d-none d-lg-table-cell">${tipoPill(s.tipo_practica)}</td>
        <td>${pillStatus(s.isAcepted, s.practicas_finalizadas, s.dado_de_baja_por_strike)}</td>
        <td class="d-none d-md-table-cell text-muted small">${(s.fecha_registro ?? '').split(' ')[0]}</td>
        <td><div class="d-flex gap-1">${acciones}</div></td>
      </tr>`;
    }).join('');

    $('#tbodyStudents').html(rows);
  }

  function updateStats() {
    const total      = _all.length;
    const pending    = _all.filter(s => s.isAcepted == 0).length;
    const accepted   = _all.filter(s => s.isAcepted == 1 && s.practicas_finalizadas != 1).length;
    const rejected   = _all.filter(s => s.isAcepted == 2).length;
    const finalizados = _all.filter(s => s.practicas_finalizadas == 1).length;
    $('#statTotal').text(total);
    $('#statPending').text(pending);
    $('#statAccepted').text(accepted);
    $('#statRejected').text(rejected);
    $('#statFinished').text(finalizados);
    // animate bars
    const pct = (n) => total ? Math.round(n / total * 100) : 0;
    setTimeout(() => {
      $('#barPending').css('width', pct(pending) + '%');
      $('#barAccepted').css('width', pct(accepted) + '%');
      $('#barRejected').css('width', pct(rejected) + '%');
      $('#barFinished').css('width', pct(finalizados) + '%');
    }, 100);
    // tab counters
    $('#cntAll').text(total);
    $('#cntPending').text(pending);
    $('#cntAccepted').text(accepted);
    $('#cntRejected').text(rejected);
    $('#cntFinished').text(finalizados);
  }

  // —— Filtros ————————————————————————————————————————
  $(document).on('click', '.btn-unblock-student', async function(e) {
    e.stopPropagation();
    const id = $(this).data('id');
    if (!await confirmDlg('¿Desbloquear alumno?', 'El alumno volverá a estar activo y podrá realizar prácticas.')) return;
    post({ action: 'unblock_student', idStudent: id }).then(res => {
      if (res.success) { ok(res.message); loadStudents(); } else err(res.message);
    });
  });

  $(document).on('click', '.btn-remove-strike-student', async function(e) {
    e.stopPropagation();
    const id = $(this).data('id');
    if (!await confirmDlg('¿Eliminar strike?', 'Se eliminará el strike más reciente del alumno.')) return;
    post({ action: 'remove_strike_student', idStudent: id }).then(res => {
      if (res.success) { ok(res.message); loadStudents(); } else err(res.message);
    });
  });

  $(document).on('click', '.btn-resend-credentials-student', async function(e) {
    e.stopPropagation();
    const id = $(this).data('id');
    if (!await confirmDlg('¿Regenerar credenciales?', 'Se generará una nueva contraseña y se enviará al correo del alumno.')) return;
    
    Swal.fire({ title: 'Generando...', text: 'Por favor espera', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    
    post({ action: 'resend_credentials_student', idStudent: id }).then(res => {
      if (res.success || res === 'success') {
        ok('Credenciales regeneradas y enviadas por correo.');
      } else {
        err(res.message ?? res.error ?? 'Error al regenerar credenciales.');
      }
    });
  });

  $(document).on('click', '.btn-hard-reset-student', function (e) {
    e.stopPropagation();
    const id = $(this).data('id');
    Swal.fire({
      title: 'Hard Reset de Alumno',
      html: `
        <p class="text-danger fw-bold" style="font-size:0.95rem; margin-bottom:1rem;">⚠️ Esta acción NO es reversible. Se borrará todo el progreso del alumno.</p>
        <textarea id="swal-motivo-reset" class="swal2-textarea" placeholder="Motivo del reset (Obligatorio)" style="margin:0 auto 15px auto; width: 90%; font-size:0.95rem; padding:10px; border-radius:8px;" required></textarea>
        <p style="font-size:0.9rem; color:#495057; margin:0 0 5px 0;">Para continuar, escribe <strong>CONFIRMAR RESET</strong>:</p>
        <input type="text" id="swal-confirm-reset" class="swal2-input" placeholder="CONFIRMAR RESET" style="margin:0 auto; width: 90%; max-width:100%; text-align: center; font-size: 1rem; border-radius:8px; text-transform:uppercase;" required>
      `,
      icon: 'warning', showCancelButton: true, confirmButtonText: 'Ejecutar Reset', cancelButtonText: 'Cancelar', confirmButtonColor: '#dc3545',
      preConfirm: () => {
        const motivo = document.getElementById('swal-motivo-reset').value.trim();
        const confirmacion = document.getElementById('swal-confirm-reset').value.trim();
        if (!motivo) { Swal.showValidationMessage('El motivo es obligatorio'); return false; }
        if (confirmacion !== 'CONFIRMAR RESET') { Swal.showValidationMessage('Debe escribir CONFIRMAR RESET'); return false; }
        return { motivo, confirmacion };
      }
    }).then(r => {
      if (!r.isConfirmed) return;
      post({ action: 'hard_reset_student', idStudent: id, motivo: r.value.motivo, confirmacion: r.value.confirmacion }).then(res => {
        if (res.success) { ok(res.message); loadStudents(); } else err(res.message);
      });
    });
  });

  $(document).on('click', '.is-filter-btn', function () {
    $('.is-filter-btn').removeClass('active');
    $(this).addClass('active');
    _activeFilter = String($(this).data('filter'));
    renderTable();
  });

  // —— Búsqueda en tiempo real ————————————————————————
  $(document).on('input', '#searchStudents', function () {
    const q = $(this).val().toLowerCase().trim();
    if (!q) { renderTable(); return; }
    const prev = _activeFilter;
    const source = prev === 'all' ? _all
      : prev === 'finished' ? _all.filter(s => s.practicas_finalizadas == 1)
      : _all.filter(s => String(s.isAcepted) === prev && s.practicas_finalizadas != 1);
    const filtered = source.filter(s =>
      (s.nombre_completo ?? '').toLowerCase().includes(q) ||
      (s.matricula ?? '').toLowerCase().includes(q) ||
      (s.programa_academico ?? '').toLowerCase().includes(q) ||
      (s.email ?? '').toLowerCase().includes(q)
    );
    if (!filtered.length) {
      $('#tbodyStudents').html(`<tr><td colspan="9"><div class="is-empty"><div class="is-empty-icon"><i class="fas fa-search"></i></div><h5>Sin resultados</h5><p>No se encontraron alumnos que coincidan con "${q}".</p></div></td></tr>`);
      return;
    }
    const rows = filtered.map((s, i) => {
      const initials = (s.nombre_completo ?? '').split(' ').slice(0,2).map(w=>w[0]?.toUpperCase()||'').join('');
      let acciones = '';
      if (s.isAcepted == 0) {
        acciones = `
          <button class="is-btn-icon is-btn-accept btn-accept-student" data-id="${s.id}" title="Aceptar"><i class="fas fa-check"></i></button>
          <button class="is-btn-icon is-btn-reject btn-denegate-student" data-id="${s.id}" title="Rechazar"><i class="fas fa-times"></i></button>`;
      } else if (s.isAcepted == 1 && s.practicas_finalizadas != 1 && s.dado_de_baja_por_strike != 1) {
        acciones = `
          <button class="is-btn-icon is-btn-edit btn-edit-student" data-id="${s.id}" title="Editar"><i class="fas fa-edit"></i></button>
          <button class="is-btn-icon is-btn-disable btn-disable-student" data-id="${s.id}" title="Deshabilitar"><i class="fas fa-user-slash"></i></button>`;
      } else if (s.practicas_finalizadas == 1) {
        acciones = `
          <button class="is-btn-icon is-btn-edit btn-edit-student" data-id="${s.id}" title="Editar"><i class="fas fa-edit"></i></button>`;
      }
      if (s.dado_de_baja_por_strike == 1) {
          acciones += `<button class="is-btn-icon btn-unblock-student ms-1" style="color:#28a745;" data-id="${s.id}" title="Desbloquear Alumno"><i class="fas fa-lock-open"></i></button>`;
      }
      if (s.strikes_count > 0) {
          acciones += `<button class="is-btn-icon btn-remove-strike-student ms-1" style="color:#ffc107;" data-id="${s.id}" title="Eliminar Strike"><i class="fas fa-eraser"></i></button>`;
      }
      acciones += `<button class="is-btn-icon btn-hard-reset-student ms-1" style="color:#dc3545;" data-id="${s.id}" title="Hard Reset"><i class="fas fa-skull-crossbones"></i></button>`;

      return `<tr>
        <td class="text-muted small">${i + 1}</td>
        <td><div class="d-flex align-items-center gap-2"><div class="stu-avatar">${initials||'?'}</div><div><div class="fw-semibold">${esc(s.nombre_completo)}</div><small class="text-muted">${esc(s.email)}</small></div></div></td>
        <td class="small">${esc(s.matricula)}</td>
        <td class="d-none d-md-table-cell small">${esc(s.programa_academico ?? '—')}</td>
        <td class="d-none d-md-table-cell small text-center">${esc(s.periodo ?? '—')}</td>
        <td class="d-none d-lg-table-cell">${tipoPill(s.tipo_practica)}</td>
        <td>${pillStatus(s.isAcepted, s.practicas_finalizadas, s.dado_de_baja_por_strike)}</td>
        <td class="d-none d-md-table-cell text-muted small">${(s.fecha_registro ?? '').split(' ')[0]}</td>
        <td><div class="d-flex gap-1">${acciones}</div></td>
      </tr>`;
    }).join('');
    $('#tbodyStudents').html(rows);
  });

  // —— Aceptar alumno —————————————————————————————————
  $(document).on('click', '.btn-accept-student', async function () {
    const id = $(this).data('id');
    if (!await confirmDlg('¿Aceptar alumno?', 'Se le enviará un correo con sus credenciales de acceso.')) return;
    post({ action: 'accept_student', idStudent: id }).then(res => {
      if (res.success || res === 'success') {
        ok('Alumno aceptado. Se envió el correo con credenciales.');
        loadStudents();
      } else {
        err(res.message ?? res.error ?? 'No se pudo aceptar al alumno.');
      }
    });
  });

  // —— Rechazar alumno ————————————————————————————————
  $(document).on('click', '.btn-denegate-student', async function () {
    const id = $(this).data('id');
    if (!await confirmDlg('¿Rechazar alumno?', 'Esta acción marcará la solicitud como rechazada.')) return;
    post({ action: 'denegate_student', idStudent: id }).then(res => {
      if (res.success || res === 'success') {
        ok('Solicitud rechazada.');
        loadStudents();
      } else {
        err(res.message ?? res.error ?? 'No se pudo rechazar al alumno.');
      }
    });
  });

  // —— Deshabilitar alumno ——————————————————————————————
  $(document).on('click', '.btn-disable-student', async function () {
    const id = $(this).data('id');
    if (!await confirmDlg('¿Deshabilitar alumno?', 'El alumno dejará de aparecer en el listado activo.')) return;
    post({ action: 'disable_student', idStudent: id }).then(res => {
      if (res.success || res === 'success') {
        ok('Alumno deshabilitado.');
        loadStudents();
      } else {
        err(res.message ?? res.error ?? 'No se pudo deshabilitar al alumno.');
      }
    });
  });

  // —— Editar alumno: cargar datos en modal ———————————————
  $(document).on('click', '.btn-edit-student', function () {
    const id = $(this).data('id');
    post({ action: 'get_student_by_id', idStudent: id }).then(data => {
      if (!data || data.error) { err('No se pudo cargar el alumno.'); return; }
      $('#editId').val(data.id);
      $('#editMatricula').val(data.matricula);
      $('#editGrupo').val(data.grupo);
      $('#editNombre').val(data.nombre_completo);
      $('#editCurp').val(data.curp);
      $('#editFechaNacimiento').val(data.fecha_nacimiento);
      $('#editGenero').val(data.genero);
      $('#editEmail').val(data.email);
      $('#editTelefono').val(data.telefono);
      $('#editPrograma').val(data.programa_academico);
      $('#editPeriodo').val(data.periodo);
      $('#editStudentModal').modal('show');
    });
  });

  // —— Guardar cambios de edición ———————————————————————
  $('#saveEditBtn').on('click', function () {
    const nombre = $('#editNombre').val().trim();
    const matricula = $('#editMatricula').val().trim();
    if (!nombre || !matricula) { err('El nombre y la matrícula son obligatorios.'); return; }
    const payload = {
      action            : 'update_student_practices',
      idstudent         : $('#editId').val(),
      matricula,
      grupo             : $('#editGrupo').val(),
      nombre_completo   : nombre,
      curp              : $('#editCurp').val(),
      fecha_nacimiento  : $('#editFechaNacimiento').val(),
      genero            : $('#editGenero').val(),
      email             : $('#editEmail').val(),
      telefono          : $('#editTelefono').val(),
      programa_academico: $('#editPrograma').val(),
      periodo           : $('#editPeriodo').val(),
    };
    post(payload).then(res => {
      if (res.success) {
        ok('Datos actualizados correctamente.');
        $('#editStudentModal').modal('hide');
        loadStudents();
      } else {
        err(res.error ?? res.message ?? 'No se pudo guardar.');
      }
    });
  });

  loadStudents();
})();
