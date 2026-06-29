/* =====================================================================
   students.js — Gestión de Estudiantes (Internos y Externos)
   Tab 1: Internos  (type='universidad') — con progreso de SS interno
   Tab 2: Externos  (type!='universidad') — con progreso de SS externo
   Excel export con SheetJS
   ===================================================================== */

// Etapas para el progreso visual
// Orden real del proceso interno del alumno:
// Prácticas → Aceptación SS → Solicitud Registro → Reportes 1-3 → Conclusión (autogenerada) → Liberación (validación final)
const STEPS_INTERNO = [
  { key: 'carta_practicas_interno',    label: 'Fin. Practicas',     icon: '🗂' },
  { key: 'carta_aceptacion_servicio',  label: 'Carta Aceptacion',   icon: '📜' },
  { key: 'solicitud_registro',         label: 'Sol. Registro',      icon: '📋' },
  { key: 'reporte_parcial_1',          label: 'Reporte 1',          icon: '📄' },
  { key: 'reporte_parcial_2',          label: 'Reporte 2',          icon: '📄' },
  { key: 'reporte_parcial_3',          label: 'Reporte 3',          icon: '📄' },
  { key: 'carta_conclusion_servicio',  label: 'Carta Conclusion',   icon: '🎓' },
  { key: 'carta_liberacion_interno',   label: 'Carta Liberacion',   icon: '✅' },
];

const STEPS_EXTERNO = [
  { key: 'carta_presentacion',  label: 'Carta Presentacion', icon: '📋' },
  { key: 'carta_practicas',     label: 'Carta Practicas',    icon: '🗂' },
  { key: 'carta_liberacion',    label: 'Carta Liberacion',   icon: '🔓' },
];

const TIPO_LABELS = {
  solicitud_registro:        'Solicitud de Registro',
  carta_aceptacion_servicio: 'Carta de Aceptacion',
  reporte_parcial_1:         'Reporte Parcial 1',
  reporte_parcial_2:         'Reporte Parcial 2',
  reporte_parcial_3:         'Reporte Parcial 3',
  carta_practicas_interno:   'Carta de Practicas (Interno)',
  carta_liberacion_interno:  'Carta de Liberacion (Interno)',
  carta_conclusion_servicio: 'Carta de Conclusion',
  carta_presentacion:        'Carta de Presentacion',
  carta_practicas:           'Carta de Practicas',
  carta_liberacion:          'Carta de Liberacion',
};

let dtInterno = null;
let dtExterno = null;
let _currentTab = 'interno';

$(document).ready(function () {
  initializeLicenciatura();
  initTableInterno();
  initTableExterno();

  if (window.location.hash === '#tab=externo') {
    switchTab('externo');
  }

  $(".registerStudentModal").on("click", function () {
    $("#registerStudentModal").modal("show");
  });

  initializeValidation();
});

// Tab switching
function switchTab(tab) {
  _currentTab = tab;
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
  document.getElementById('tabBtnInterno').className = 'st-tab-btn';
  document.getElementById('tabBtnExterno').className = 'st-tab-btn';
  if (tab === 'interno') {
    document.getElementById('panelInterno').classList.add('active');
    document.getElementById('tabBtnInterno').classList.add('active-interno');
    if (dtInterno) dtInterno.columns.adjust().draw(false);
  } else {
    document.getElementById('panelExterno').classList.add('active');
    document.getElementById('tabBtnExterno').classList.add('active-externo');
    if (dtExterno) dtExterno.columns.adjust().draw(false);
  }
}

// Build step progress track
function buildStepTrack(documentos, steps) {
  let docMap = {};
  if (documentos) {
    try {
      const docs = typeof documentos === 'string' ? JSON.parse(documentos) : documentos;
      if (Array.isArray(docs)) {
        docs.forEach(d => { if (!docMap[d.tipo] || d.status === 'aprobado') docMap[d.tipo] = d; });
      }
    } catch(e) {}
  }
  let html = '<div class="step-track">';
  steps.forEach((step, i) => {
    const doc = docMap[step.key];
    let cls = 'empty', icon = '–', title = step.label + ': Sin documento';
    if (doc) {
      if (doc.status === 'aprobado')       { cls = 'done';     icon = '✓'; title = step.label + ': Aprobado'; }
      else if (doc.status === 'pendiente') { cls = 'pending';  icon = '…'; title = step.label + ': Pendiente'; }
      else if (doc.status === 'rechazado') { cls = 'rejected'; icon = '✗'; title = step.label + ': Rechazado'; }
    }
    if (i > 0) {
      const prevDoc = docMap[steps[i-1].key];
      const lc = (prevDoc && prevDoc.status === 'aprobado') ? 'done' : '';
      html += '<div class="step-line ' + lc + '"></div>';
    }
    html += '<div class="step-circle ' + cls + '" title="' + title + '" data-bs-toggle="tooltip" data-bs-placement="top">' + icon + '</div>';
  });
  html += '</div>';
  return html;
}

function buildLastStatusBadge(tipo, status) {
  if (!tipo) return '<span class="proc-badge sin-doc">Sin actividad</span>';
  const label = TIPO_LABELS[tipo] || tipo;
  const icons = { aprobado: '✓', pendiente: '…', rechazado: '✗' };
  const icon = icons[status] || '–';
  return '<span class="proc-badge ' + (status||'sin-doc') + '">' + icon + ' ' + label + '</span>';
}

// Init DataTable Internos
function initTableInterno() {
  dtInterno = $('#tableInterno').DataTable({
    ajax: {
      type: 'POST',
      url: 'controller/ajax/ajax.forms.php',
      dataSrc: function(data) {
        document.getElementById('countInterno').textContent = Array.isArray(data) ? data.length : 0;
        return data || [];
      },
      data: { search: 'students_history', type: 'universidad' },
      dataType: 'json',
    },
    columns: [
      { data: null, render: function(d,t,r,m){ return m.row+1; }, width: '40px' },
      { data: 'matricula', render: function(d,t,row){ return '<button class="st-matricula-btn" onclick="showStudentModal('+row.idStudent+')">'+d+'</button>'; }},
      { data: null, render: function(d){ return d.firstname+' '+d.lastname; }},
      { data: 'nameDegree', defaultContent: '–' },
      { data: 'email', defaultContent: '–' },
      { data: null, orderable: false, render: function(d){ return buildStepTrack(d.documentos, STEPS_INTERNO); }},
      { data: null, orderable: false, render: function(d){ return buildLastStatusBadge(d.ultimo_tipo, d.ultimo_status); }},
      { data: null, orderable: false, render: function(d){ return buildActionsRow(d); }},
    ],
    language: getDtLang(),
    drawCallback: activateTooltips,
    pageLength: 15,
    order: [[2, 'asc']],
  });
}

// Init DataTable Externos
function initTableExterno() {
  dtExterno = $('#tableExterno').DataTable({
    ajax: {
      type: 'POST',
      url: 'controller/ajax/ajax.forms.php',
      dataSrc: function(data) {
        document.getElementById('countExterno').textContent = Array.isArray(data) ? data.length : 0;
        return data || [];
      },
      data: { search: 'students_history', type: 'externo' },
      dataType: 'json',
    },
    columns: [
      { data: null, render: function(d,t,r,m){ return m.row+1; }, width: '40px' },
      { data: 'matricula', render: function(d,t,row){ return '<button class="st-matricula-btn" style="background:linear-gradient(135deg,#7c3aed,#a855f7);" onclick="showStudentModal('+row.idStudent+')">'+d+'</button>'; }},
      { data: null, render: function(d){ return d.firstname+' '+d.lastname; }},
      { data: 'nameDegree', defaultContent: '–' },
      { data: 'email', defaultContent: '–' },
      { data: 'type', render: function(d){ return '<span style="font-size:.72rem;background:#ede9fe;color:#7c3aed;border-radius:2rem;padding:.15rem .55rem;font-weight:600;">'+(d||'empresa')+'</span>'; }},
      { data: null, orderable: false, render: function(d){ return buildStepTrack(d.documentos, STEPS_EXTERNO); }},
      { data: null, orderable: false, render: function(d){ return buildLastStatusBadge(d.ultimo_tipo, d.ultimo_status); }},
      { data: null, orderable: false, render: function(d){ return buildActionsRow(d); }},
    ],
    language: getDtLang(),
    drawCallback: activateTooltips,
    pageLength: 15,
    order: [[2, 'asc']],
  });
}

function buildActionsRow(data) {
  const isAdmin = (_st_role === 'admin' || _st_role === 'admin_servicio');
  const esc = (s) => String(s||'').replace(/'/g, "\\'").replace(/"/g, '&quot;');
  let btns = '<button class="st-btn-icon st-btn-history" onclick="showHistory('+data.idStudent+',\''+esc(data.firstname)+' '+esc(data.lastname)+'\')" title="Ver historial"><i class="fas fa-clock-rotate-left"></i></button> ';
  if (isAdmin) {
    if (data.accepted == 0) {
      btns += '<button class="st-btn-icon st-btn-accept" onclick="acceptStudent('+data.idStudent+')" title="Aceptar"><i class="fas fa-check"></i></button> ';
      btns += '<button class="st-btn-icon st-btn-reject" onclick="denegateStudent('+data.idStudent+')" title="Rechazar"><i class="fas fa-times"></i></button>';
    } else {
      btns += '<button class="st-btn-icon st-btn-edit" onclick="showEditModal('+data.idStudent+')" title="Editar"><i class="fas fa-edit"></i></button> ';
      btns += '<button class="st-btn-icon st-btn-delete" onclick="confirmDeleteStudent('+data.idStudent+')" title="Eliminar"><i class="fas fa-trash-alt"></i></button>';
    }
  }
  return '<div class="d-flex gap-1">'+btns+'</div>';
}

function activateTooltips() {
  var els = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  els.forEach(function(el) {
    if (!el._bsTooltip) {
      try { el._bsTooltip = new bootstrap.Tooltip(el, { trigger: 'hover' }); } catch(e) {}
    }
  });
}

// Show History Modal
function showHistory(idStudent, name) {
  document.getElementById('historyModalTitle').textContent = 'Historial: ' + name;
  document.getElementById('historyModalContent').innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm" style="color:#01643D;"></div> Cargando...</div>';
  $('#historyModal').modal('show');

  // Search in already-loaded DataTable data (avoids fetching wrong student)
  var found = null;
  [dtInterno, dtExterno].forEach(function(dt) {
    if (found || !dt) return;
    dt.rows().data().each(function(row) {
      if (String(row.idStudent) === String(idStudent)) found = row;
    });
  });

  if (found) {
    renderHistoryModal(found);
  } else {
    document.getElementById('historyModalContent').innerHTML = '<p class="text-muted text-center py-3">Sin datos disponibles.</p>';
  }
}

function renderHistoryModal(data) {
  var docs = [];
  try { docs = data.documentos ? (typeof data.documentos === 'string' ? JSON.parse(data.documentos) : data.documentos) : []; } catch(e) {}
  var steps = (data.type === 'universidad') ? STEPS_INTERNO : STEPS_EXTERNO;
  var docMap = {};
  docs.forEach(function(d) { if (!docMap[d.tipo] || d.status === 'aprobado') docMap[d.tipo] = d; });

  var fn = data.firstname || '?';
  var ln = data.lastname || '?';
  var html = '<div class="d-flex gap-3 align-items-center mb-3 p-3 rounded" style="background:#f8f9fc;">'
    + '<div style="width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,#01643D,#2A7E5D);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:1.1rem;flex-shrink:0;">' + fn.charAt(0) + ln.charAt(0) + '</div>'
    + '<div style="flex:1;">'
    + '<div style="font-weight:700;font-size:.9rem;">' + fn + ' ' + ln + '</div>'
    + '<div style="font-size:.75rem;color:#6b7280;">' + (data.email||'') + ' &middot; Mat. ' + (data.matricula||'') + '</div>'
    + '<div style="font-size:.75rem;color:#6b7280;">' + (data.nameDegree||'') + '</div>'
    + '</div>'
    + '<span style="font-size:.7rem;padding:.15rem .6rem;border-radius:2rem;background:' + (data.type==='universidad'?'#dcfce7':'#ede9fe') + ';color:' + (data.type==='universidad'?'#16a34a':'#7c3aed') + ';font-weight:600;">'
    + (data.type === 'universidad' ? 'Interno' : 'Externo') + '</span>'
    + '</div>';

  html += '<div style="font-size:.8rem;font-weight:700;color:#374151;margin-bottom:.75rem;"><i class="fas fa-route me-1" style="color:#01643D;"></i>Estado del proceso</div>';
  html += '<div class="hist-timeline">';

  steps.forEach(function(step) {
    var doc = docMap[step.key];
    var cls = 'sin-doc', icon = '–', fecha = '';
    if (doc) {
      if (doc.status === 'aprobado')       { cls = 'aprobado';  icon = '✓'; }
      else if (doc.status === 'pendiente') { cls = 'pendiente'; icon = '…'; }
      else if (doc.status === 'rechazado') { cls = 'rechazado'; icon = '✗'; }
      if (doc.created_at) fecha = '<small>Fecha: ' + doc.created_at + '</small>';
    }
    var statusText = cls === 'sin-doc' ? 'Sin documento enviado' : cls.charAt(0).toUpperCase() + cls.slice(1);
    var statusColor = cls==='aprobado'?'#16a34a':cls==='pendiente'?'#b45309':cls==='rechazado'?'#dc2626':'#94a3b8';
    html += '<div class="hist-item"><div class="hist-dot ' + cls + '">' + icon + '</div>'
      + '<div class="hist-content"><h6>' + step.icon + ' ' + step.label + '</h6>'
      + '<div style="font-size:.73rem;color:' + statusColor + ';">' + statusText + '</div>'
      + fecha + '</div></div>';
  });

  html += '</div>';
  document.getElementById('historyModalContent').innerHTML = html;
}

// Excel Export
function exportExcel(tab) {
  if (typeof XLSX === 'undefined') { alert('La libreria de Excel aun no carga. Intenta de nuevo.'); return; }
  var dt = (tab === 'interno') ? dtInterno : dtExterno;
  if (!dt) return;
  var steps = (tab === 'interno') ? STEPS_INTERNO : STEPS_EXTERNO;
  var rows = dt.rows({ search: 'applied' }).data().toArray();

  var headers = ['#', 'Matricula', 'Nombre', 'Licenciatura', 'Correo', 'Telefono', 'Tipo'];
  steps.forEach(function(s) { headers.push(s.label); });
  headers.push('Ultimo Paso', 'Ultimo Estado');

  var data = rows.map(function(row, i) {
    var docMap = {};
    try {
      var docs = row.documentos ? (typeof row.documentos === 'string' ? JSON.parse(row.documentos) : row.documentos) : [];
      docs.forEach(function(d) { if (!docMap[d.tipo] || d.status === 'aprobado') docMap[d.tipo] = d; });
    } catch(e) {}
    var base = [i+1, row.matricula, row.firstname+' '+row.lastname, row.nameDegree||'', row.email||'', row.phone||'', row.type||''];
    steps.forEach(function(s) {
      var doc = docMap[s.key];
      base.push(doc ? doc.status : 'sin documento');
    });
    base.push(TIPO_LABELS[row.ultimo_tipo] || row.ultimo_tipo || '–');
    base.push(row.ultimo_status || '–');
    return base;
  });

  var ws = XLSX.utils.aoa_to_sheet([headers].concat(data));
  ws['!cols'] = headers.map(function(h,i){ return { wch: Math.max(h.length+2, i < 7 ? 18 : 14) }; });
  var wb = XLSX.utils.book_new();
  var sheetName = tab === 'interno' ? 'Est Internos' : 'Est Externos';
  XLSX.utils.book_append_sheet(wb, ws, sheetName);
  var fecha = new Date().toISOString().slice(0,10);
  XLSX.writeFile(wb, 'estudiantes_' + tab + '_' + fecha + '.xlsx');
}

// DataTable language
function getDtLang() {
  return {
    sProcessing: 'Procesando...', sLengthMenu: 'Mostrar _MENU_ registros',
    sZeroRecords: 'No se encontraron resultados', sEmptyTable: 'Sin datos disponibles',
    sInfo: 'Registros _START_ al _END_ de _TOTAL_', sInfoEmpty: 'Sin registros',
    sInfoFiltered: '(filtrado de _MAX_)', sSearch: 'Buscar:',
    sLoadingRecords: 'Cargando...', sUrl: '',
    oPaginate: { sFirst: 'Primero', sLast: 'Ultimo', sNext: 'Siguiente', sPrevious: 'Anterior' },
  };
}

// ── Legacy functions ─────────────────────────────────────────────────────────
function initializeLicenciatura() {
  $.ajax({
    type: 'POST', url: 'controller/ajax/ajax.forms.php',
    data: { search: 'degrees' }, dataType: 'json',
    success: function(response) {
      var options = '<option value="">Seleccione licenciatura</option>';
      response.forEach(function(item) { options += '<option value="'+item.idDegree+'">'+item.nameDegree+'</option>'; });
      $('#licenciatura').html(options);
      $('#editLicenciatura').html(options);
    },
  });
}

function showStudentModal(idStudent) {
  $.ajax({
    type: 'POST', url: 'controller/ajax/ajax.forms.php',
    data: { search: 'student', idStudent: idStudent }, dataType: 'json',
    success: function(data) {
      $('#studentMatricula').text(data.matricula);
      $('#studentFullName').text(data.firstname + ' ' + data.lastname);
      $('#studentEmail').text(data.email);
      $('#studentPhone').text(data.phone);
      $('#studentAddress').text(data.street + ' ' + data.nInt + ' ' + data.nExt + ', ' + data.colony + ', CP: ' + data.cp);
      $('#studentDegree').text(data.nameDegree);
      $('#studentBirthday').text(data.dayBirthday+'/'+data.monthBirthday+'/'+data.yearBirthday);
      $('#studentGender').text(data.gender === 1 ? 'Masculino' : 'Femenino');
      $('#studentParent').text(data.parent + ': ' + data.emergenci_phone);
      $('#studentModal').modal('show');
    },
    error: function() { alert('Error al obtener los detalles del alumno.'); },
  });
}

function acceptStudent(id) {
  $.post('controller/ajax/ajax.forms.php', { search: 'student', action: 'acceptStudent', idStudent: id },
    function() { alert('Estudiante aceptado'); reloadAll(); }
  ).fail(function() { alert('Error al aceptar'); });
}

function denegateStudent(id) {
  $.post('controller/ajax/ajax.forms.php', { search: 'student', action: 'denegateStudent', idStudent: id },
    function() { alert('Estudiante denegado'); reloadAll(); }
  ).fail(function() { alert('Error al denegar'); });
}

function reloadAll() {
  if (dtInterno) dtInterno.ajax.reload();
  if (dtExterno) dtExterno.ajax.reload();
}

function showEditModal(id) {
  $.post('controller/ajax/ajax.forms.php',
    { search: 'student', action: 'getStudent', idStudent: id },
    function(data) {
      $('#editStudentModal #editMatricula').val(data.matricula);
      $('#editStudentModal #editNombre').val(data.firstname);
      $('#editStudentModal #editLastname').val(data.lastname);
      $('#editStudentModal #editCorreoInstitucional').val(data.email);
      $('#editStudentModal #editTelefonoContacto').val(data.phone);
      $('#editStudentModal #editTelefonoEmergencia').val(data.emergenci_phone);
      $('#editStudentModal #editParentesco').val(data.parent);
      $('#editStudentModal #editTipoLicenciatura').val(data.type_lic);
      $('#editStudentModal #editCalle').val(data.street);
      $('#editStudentModal #editNumeroInterior').val(data.nInt);
      $('#editStudentModal #editNumeroExterior').val(data.nExt);
      $('#editStudentModal #editColonia').val(data.colony);
      $('#editStudentModal #editCodigoPostal').val(data.cp);
      $('#editStudentModal #editDiaNacimiento').val(data.dayBirthday);
      $('#editStudentModal #editMesNacimiento').val(data.monthBirthday);
      $('#editStudentModal #editAnioNacimiento').val(data.yearBirthday);
      $('#editStudentModal #editGenero').val(data.gender);
      $('#editStudentModal #editLicenciatura').val(data.idDegree);
      $('#editStudentModal #editGrado').val(data.grado);
      $('#idStudent').val(id);
      $('#editStudentModal').data('idStudent', id).modal('show');
    }, 'json'
  );
}

function confirmDeleteStudent(id) {
  $('#deleteStudentModal').data('idStudent', id).modal('show');
}

function deleteStudent() {
  var id = $('#deleteStudentModal').data('idStudent');
  var reason = $('#deleteReason').val();
  if (reason !== '') {
    $.post('controller/ajax/ajax.forms.php',
      { search: 'student', action: 'dropStudent', idStudent: id, reason: reason },
      function() { alert('Estudiante eliminado'); reloadAll(); $('#deleteStudentModal').modal('hide'); $('#deleteReason').val(''); }
    ).fail(function() { alert('Error al eliminar'); });
  } else {
    alert('Por favor, especifique el motivo de la eliminacion');
  }
}

function showCommentsModal(comments) {
  $('#commentsModal #commentsContent').text(comments);
  $('#commentsModal').modal('show');
}

function initializeValidation() {
  $('#matricula').on('input', function() { toggleValidation($(this), /^\d+$/); });
  $('#correoInstitucional').on('input', function() { toggleValidation($(this), /^[a-zA-Z0-9._%+-]+@unimontrer\.edu\.mx$/); });
  $('#telefonoContacto, #telefonoEmergencia').on('input', function() { toggleValidation($(this), /^\d{10}$/); });
  $('#registerStudentForm').on('submit', function(e) {
    e.preventDefault();
    if (validateForm()) handleSubmitForm($(this));
  });
}

function toggleValidation($el, regex) {
  $el.toggleClass('is-valid', regex.test($el.val())).toggleClass('is-invalid', !regex.test($el.val()));
}

function validateForm() {
  var valid = true;
  $('#matricula, #correoInstitucional, #telefonoContacto, #telefonoEmergencia').each(function() {
    if ($(this).hasClass('is-invalid')) valid = false;
  });
  return valid;
}

function handleSubmitForm($form) {
  var btn = $('#submitBtn');
  btn.prop('disabled', true).text('Enviando...');
  var formData = $form.serializeArray();
  formData.push({ name: 'search', value: 'student' }, { name: 'action', value: 'addStudent' });
  $.post('controller/ajax/ajax.forms.php', formData, function(response) {
    btn.prop('disabled', false).text('Registrar');
    if (response === '"success"') { alert('Alumno registrado'); $form[0].reset(); $('#registerStudentModal').modal('hide'); reloadAll(); }
    else if (response === '"duplicate"') { alert('Registro duplicado'); }
    else { alert('Error al registrar alumno'); }
  });
}

$('#editStudentForm').on('submit', function(e) {
  e.preventDefault();
  var btn = $(this).find('button[type="submit"]');
  btn.prop('disabled', true).text('Guardando...');
  $.ajax({
    url: 'controller/ajax/ajax.forms.php', method: 'POST',
    data: $(this).serialize() + '&action=editStudent&search=student',
    success: function(response) {
      btn.prop('disabled', false).text('Guardar');
      if (response === '"success"') { alert('Estudiante actualizado'); $('#editStudentModal').modal('hide'); reloadAll(); }
      else { alert('Error al actualizar'); }
    },
    error: function() { btn.prop('disabled', false).text('Guardar'); alert('Error en la solicitud'); },
  });
});

document.addEventListener('DOMContentLoaded', function() {
  var selectors = [
    ['anioNacimiento', 'mesNacimiento', 'diaNacimiento'],
    ['editAnioNacimiento', 'editMesNacimiento', 'editDiaNacimiento'],
  ];
  var currentYear = new Date().getFullYear();
  selectors.forEach(function(pair) {
    var yrEl = document.getElementById(pair[0]);
    var moEl = document.getElementById(pair[1]);
    var dyEl = document.getElementById(pair[2]);
    if (!yrEl) return;
    for (var y = currentYear; y >= 1900; y--) {
      var opt = document.createElement('option'); opt.value = y; opt.textContent = y; yrEl.appendChild(opt);
    }
    var updateDays = function() {
      if (!dyEl || !moEl || !yrEl) return;
      var selY = parseInt(yrEl.value), selM = parseInt(moEl.value);
      dyEl.innerHTML = '<option value="">Dia</option>';
      if (!isNaN(selY) && !isNaN(selM)) {
        var n = new Date(selY, selM, 0).getDate();
        for (var d = 1; d <= n; d++) {
          var o = document.createElement('option'); o.value = d; o.textContent = d; dyEl.appendChild(o);
        }
      }
    };
    if (moEl) moEl.addEventListener('change', updateDays);
    yrEl.addEventListener('change', updateDays);
  });
});
