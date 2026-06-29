// =====================================
// Config Debug
// =====================================
$.fn.dataTable.ext.errMode = 'console';

// =====================================
// Helpers
// =====================================
function pad2(n) { return String(n).padStart(2, '0'); }

function fmtFechaHora(v) {
  if (!v) return '';
  // soporta 'YYYY-MM-DD HH:mm:ss' o 'YYYY-MM-DD'
  const [d, h] = String(v).split(' ');
  const [Y, M, D] = d.split('-');
  return `${D}/${M}/${Y}${h ? ' ' + h.slice(0, 5) : ''}`;
}

function ellipsis(val, max) {
  if (val == null) return '';
  const s = String(val);
  return s.length > max ? s.slice(0, max - 1) + '…' : s;
}

function escapeHtml(str) {
  return String(str ?? '')
    .replace(/&/g, '&amp;').replace(/</g, '&lt;')
    .replace(/>/g, '&gt;').replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

// Acepta numérico (1-7) o iniciales MX (L, M, MI, J, V, S, D)
function mapDia(val) {
  if (val == null) return '';
  const numMap = { 1: 'Lun', 2: 'Mar', 3: 'Mié', 4: 'Jue', 5: 'Vie', 6: 'Sáb', 7: 'Dom' };
  const k = parseInt(val, 10);
  if (!isNaN(k) && numMap[k]) return numMap[k];
  const txt = String(val).toUpperCase();
  const txtMap = { 'L': 'Lun', 'M': 'Mar', 'MI': 'Mié', 'J': 'Jue', 'V': 'Vie', 'S': 'Sáb', 'D': 'Dom' };
  return txtMap[txt] || String(val);
}

// Longitud del texto plano (para validar Quill)
function _plainTextLen(html) {
  const tmp = document.createElement('div');
  tmp.innerHTML = html || '';
  return (tmp.textContent || tmp.innerText || '').trim().length;
}

// =====================================
// DataTable
// =====================================
$(document).ready(function () {

  if ($.fn.DataTable.isDataTable('#studentsTable')) {
    $('#studentsTable').DataTable().destroy();
  }

  const dt = $('#studentsTable')
    .addClass('compact nowrap')
    .DataTable({
      language: { url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
      processing: true,
      deferRender: true,
      autoWidth: false,
      stateSave: true,
      pageLength: 10,
      lengthMenu: [5, 10, 25, 50],
      dom: "<'row mb-2'<'col-md-6'f><'col-md-6 text-end'B>>" +
        "tr" +
        "<'row mt-2'<'col-md-6'i><'col-md-6'p>>",
      buttons: [
        { extend: 'colvis', text: 'Columnas' },
        { extend: 'excelHtml5', text: 'Excel', title: 'alumnos', exportOptions: { columns: ':visible:not(.dt-noexport)' } },
        { extend: 'copyHtml5', text: 'Copiar', exportOptions: { columns: ':visible:not(.dt-noexport)' } }
      ],
      responsive: {
        details: {
          type: 'inline',
          target: 'tr',
          renderer: function (api, rowIdx, columns) {
            // Card 2 columnas (k:v) para móvil
            const rows = [];
            columns.forEach(c => {
              if (c.hidden) {
                rows.push(
                  `<div class="row">
                     <div class="col-5 dt-k">${c.title}:</div>
                     <div class="col-7 dt-v">${c.data || ''}</div>
                   </div>`
                );
              }
            });
            return rows.length
              ? $('<div class="dt-details-card p-2"/>').append(rows.join(''))
              : false;
          }
        }
      },
      ajax: {
        type: 'POST',
        url: 'controller/organismo/forms.php',
        data: { action: 'getAllPractices' },
        dataSrc: '',
        error: function (xhr) {
          console.error('Error AJAX:', xhr.responseText || xhr.statusText);
          alert('No se pudieron cargar los datos.');
        }
      },
      order: [[2, 'asc']], // Orden por Nombre
      columnDefs: [
        { targets: '_all', defaultContent: '' }
      ],
      columns: [
        // #
        {
          data: null, title: '#', width: 28, className: 'text-center dt-noexport',
          render: (d, t, r, m) => m.row + 1, orderable: false, searchable: false, responsivePriority: 1
        },
        // Matrícula
        { data: 'matricula', title: 'Matrícula', width: 90, className: 'text-nowrap', responsivePriority: 3 },
        // Nombre
        {
          data: 'nombre_completo', title: 'Nombre', className: 'dt-clip',
          render: (val, type) => {
            if (type !== 'display') return val;
            const full = val || '';
            const short = ellipsis(full, 40);
            return short === full ? escapeHtml(short)
              : `<span title="${escapeHtml(full)}">${escapeHtml(short)}</span>`;
          },
          responsivePriority: 1
        },
        // Email
        {
          data: 'email', title: 'Email', className: 'dt-clip',
          render: (val, type) => {
            if (!val) return '';
            if (type !== 'display') return val;
            const v = escapeHtml(val);
            return `<a href="mailto:${v}" class="text-decoration-none">${v}</a>`;
          },
          responsivePriority: 10002
        },
        // Teléfono
        {
          data: 'telefono', title: 'Teléfono', width: 130, className: 'text-nowrap',
          render: (val, type) => {
            if (!val) return '';
            if (type !== 'display') return val;
            const limpio = String(val).replace(/[^\d+]/g, '');
            return `<a href="tel:${limpio}" class="text-decoration-none">${escapeHtml(val)}</a>`;
          },
          responsivePriority: 2
        },
        // Licenciatura
        {
          data: 'licenciatura', title: 'Licenciatura', className: 'dt-clip',
          render: (val, type) => {
            if (type !== 'display') return val;
            const full = val || '';
            const short = ellipsis(full, 28);
            return short === full ? escapeHtml(short)
              : `<span title="${escapeHtml(full)}">${escapeHtml(short)}</span>`;
          },
          responsivePriority: 10001
        },
        // Horario (badges)
        {
          data: null, title: 'Horario', className: 'text-nowrap',
          render: function (row, type) {
            if (type !== 'display') {
              const di = mapDia(row.dia_inicio), df = mapDia(row.dia_fin);
              const hi = row.hora_inicio ? String(row.hora_inicio).slice(0, 5) : '';
              const hf = row.hora_fin ? String(row.hora_fin).slice(0, 5) : '';
              const mod = row.modalidad || '';
              return `${mod} ${di}${df ? ' - ' + df : ''} ${hi}${hf ? ' - ' + hf : ''}`.trim().replace(/\s+/g, ' ');
            }
            const di = mapDia(row.dia_inicio), df = mapDia(row.dia_fin);
            const hi = row.hora_inicio ? String(row.hora_inicio).slice(0, 5) : '';
            const hf = row.hora_fin ? String(row.hora_fin).slice(0, 5) : '';
            const mod = row.modalidad ? `<span class="badge bg-secondary me-1">${escapeHtml(row.modalidad)}</span>` : '';
            const rangoDias = (di || df) ? `<span class="badge bg-info text-dark me-1">${escapeHtml([di, df].filter(Boolean).join(' - '))}</span>` : '';
            const rangoHoras = (hi || hf) ? `<span class="badge bg-success">${escapeHtml([hi, hf].filter(Boolean).join(' - '))}</span>` : '';
            return `${mod}${rangoDias}${rangoHoras}`;
          },
          responsivePriority: 3
        },
        // Fecha
        {
          data: 'fecha_registro', title: 'Fecha', width: 140, className: 'text-nowrap',
          render: v => escapeHtml(fmtFechaHora(v)),
          responsivePriority: 4
        },
        // Estado de finalización
        {
          data: 'practicas_finalizadas', title: 'Estado', width: 120,
          className: 'text-center',
          responsivePriority: 2,
          render: function (val, type) {
            if (type !== 'display') return val == 1 ? 'Finalizado' : 'En curso';
            if (val == 1) {
              return `<span class="sip-pill sip-pill-green"><i class="fas fa-graduation-cap me-1"></i>Finalizado</span>`;
            }
            return `<span class="sip-pill sip-pill-blue"><i class="fas fa-sync-alt me-1"></i>En curso</span>`;
          }
        },
        // Semáforo de evaluaciones
        {
          data: null, title: 'Evaluaciones', width: 90, className: 'text-center',
          responsivePriority: 2,
          render: function (row, type) {
            const pe = row.semaforo_empresa;
            const pa = row.semaforo_alumno;
            
            if (type !== 'display') return (pe || pa) ? 'Evaluado' : 'Pendiente';
            if (pe === null && pa === null) {
              return `<span class="badge bg-light text-muted border" title="Sin evaluaciones">N/A</span>`;
            }
            
            // Función auxiliar para semáforo: <3 Rojo, <4 Amarillo, >=4 Verde
            const getSemColor = (val) => {
              if (val === null) return '#e2e8f0'; // gris
              if (val < 3) return '#ef4444'; // rojo
              if (val < 4) return '#f59e0b'; // amarillo
              return '#10b981'; // verde
            };
            
            return `
              <div class="d-flex justify-content-center gap-1" title="Empresa: ${pe||'N/A'} | Alumno: ${pa||'N/A'}">
                <div style="width:12px;height:12px;border-radius:50%;background-color:${getSemColor(pe)};"></div>
                <div style="width:12px;height:12px;border-radius:50%;background-color:${getSemColor(pa)};"></div>
              </div>
            `;
          }
        },
        // Acciones — botón solo-ícono FA6 -> solicitar capacitación y ver evaluaciones
        {
          data: 'matricula', title: 'Acciones', width: 80,
          className: 'text-center text-nowrap dt-noexport',
          orderable: false, searchable: false, responsivePriority: 1,
          render: function (matricula) {
            const m = matricula ? String(matricula).replace(/"/g, '&quot;') : '';
            const idS = row.idStudent || row.id;
            return `
              <div class="d-flex justify-content-center gap-1">
                <button type="button" class="sip-action-btn" style="background:#dbeafe; color:#2563eb;"
                        title="Solicitar capacitación"
                        onclick="solicitarCapacitacion('${m}')">
                  <i class="fa-solid fa-person-chalkboard"></i>
                </button>
                <button type="button" class="sip-action-btn" style="background:#fef08a; color:#854d0e;"
                        title="Ver Evaluaciones Integrales"
                        onclick="verEvaluacionesIntegrales(${idS}, '${m}')">
                  <i class="fa-solid fa-star"></i>
                </button>
              </div>`;
          }
        }
      ]
    });

  // Recalcular cuando cambia layout (tabs/modals, etc.)
  setTimeout(() => dt.columns.adjust().responsive.recalc(), 300);

  // Si tienes <div id="toolbar"></div>, mueve ahí los botones
  if (dt.buttons) dt.buttons().container().appendTo('#toolbar');
  buscarSolicitudesCapacitacion();
});

// =====================================
// Acciones
// =====================================
window.solicitarCapacitacion = function (matricula) {
  let quill;

  Swal.fire({
    title: 'Solicitud de capacitación',
    html: `
      <div class="text-start">
        <label class="form-label fw-semibold">Describe la capacitación requerida</label>
        <div id="quill-toolbar">
          <span class="ql-formats">
            <button class="ql-bold"></button>
            <button class="ql-italic"></button>
            <button class="ql-underline"></button>
          </span>
          <span class="ql-formats">
            <button class="ql-list" value="ordered"></button>
            <button class="ql-list" value="bullet"></button>
          </span>
          <span class="ql-formats">
            <select class="ql-header">
              <option selected></option>
              <option value="3"></option>
              <option value="4"></option>
            </select>
          </span>
          <span class="ql-formats">
            <button class="ql-link"></button>
          </span>
        </div>
        <div id="quill-editor" style="height:160px;background:#fff;"></div>
        <small class="text-muted d-block mt-1">Puedes usar negritas, listas y enlaces. Máx. 2000 caracteres.</small>
      </div>
    `,
    focusConfirm: false,
    showCancelButton: true,
    confirmButtonText: 'Enviar solicitud',
    cancelButtonText: 'Cancelar',
    willOpen: () => {
      const ed = document.getElementById('quill-editor');
      if (ed) ed.style.backgroundColor = '#fff';
    },
    didOpen: () => {
      quill = new Quill('#quill-editor', {
        theme: 'snow',
        placeholder: 'Ej.: Inducción a procesos, Excel intermedio, atención telefónica…',
        modules: { toolbar: '#quill-toolbar', clipboard: { matchVisual: false } }
      });
    },
    preConfirm: () => {
      const html = quill ? quill.root.innerHTML : '';
      const len = _plainTextLen(html);
      if (len < 5) {
        Swal.showValidationMessage('Escribe al menos 5 caracteres.');
        return false;
      }
      if (html.length > 2000) {
        Swal.showValidationMessage('El contenido excede 2000 caracteres.');
        return false;
      }
      return html;
    }
  }).then(res => {
    if (!res.isConfirmed) return;
    const html = res.value;

    $.ajax({
      type: 'POST',
      url: 'controller/organismo/forms.php',
      data: {
        action: 'solicitarCapacitacion',
        matricula: matricula,
        solicitud: html,
      }
    })
      .done(() => Swal.fire('Solicitud enviada', 'Se ha solicitado la capacitación.', 'success'))
      .fail(() => Swal.fire('Error', 'No se pudo enviar la solicitud.', 'error'));
  });
};

// Utils
function escapeHtml(s=''){ return String(s)
  .replace(/&/g,'&amp;').replace(/</g,'&lt;')
  .replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }

function pad2(n){ return String(n).padStart(2,'0'); }
function fmtFechaHora(s){
  if(!s) return '';
  const iso = String(s).replace(' ', 'T');
  const d = new Date(iso);
  if (isNaN(d)) return s;
  return `${d.getFullYear()}-${pad2(d.getMonth()+1)}-${pad2(d.getDate())} ${pad2(d.getHours())}:${pad2(d.getMinutes())}`;
}
function timeAgo(s){
  const d = new Date(String(s).replace(' ','T'));
  if (isNaN(d)) return '';
  const diff = Date.now() - d.getTime();
  const sec = Math.max(1, Math.floor(diff/1000));
  const mins = Math.floor(sec/60);
  const hrs = Math.floor(mins/60);
  const days = Math.floor(hrs/24);
  if (days>0) return `hace ${days} día${days>1?'s':''}`;
  if (hrs>0) return `hace ${hrs} h`;
  if (mins>0) return `hace ${mins} min`;
  return `hace ${sec} s`;
}

// Sanitizador básico con whitelist de etiquetas y atributos seguros
function sanitizeBasic(html=''){
  const allowedTags = new Set(['b','strong','i','em','u','br','p','ul','ol','li','a']);
  const allowedAttrs = { 'a': new Set(['href','target','rel','title']) };
  const tmpl = document.createElement('template');
  tmpl.innerHTML = html;
  const walk = (node) => {
    [...node.childNodes].forEach(child=>{
      if (child.nodeType === 1) { // element
        const tag = child.tagName.toLowerCase();
        if (!allowedTags.has(tag)) {
          // Reemplaza el nodo por su texto plano
          const span = document.createTextNode(child.textContent || '');
          child.replaceWith(span);
          return;
        }
        // Limpia attrs peligrosos
        [...child.attributes].forEach(attr=>{
          const name = attr.name.toLowerCase();
          if (!(allowedAttrs[tag] && allowedAttrs[tag].has(name))) {
            child.removeAttribute(attr.name);
          }
        });
        // Seguridad en enlaces
        if (tag==='a') {
          const href = child.getAttribute('href') || '#';
          const safe = /^https?:\/\//i.test(href) ? href : '#';
          child.setAttribute('href', safe);
          child.setAttribute('target','_blank');
          child.setAttribute('rel','noopener nofollow');
        }
        walk(child);
      } else if (child.nodeType === 8) {
        // comentario -> elimina
        child.remove();
      }
    });
  };
  walk(tmpl.content);
  return tmpl.innerHTML;
}

// Estado → etiqueta y clase
function estadoInfo(item){
  const raw = item.estado ?? (typeof item.status === 'number'
    ? (item.status===1 ? 'Aprobada' : item.status===2 ? 'Rechazada' : 'Pendiente')
    : 'Pendiente');
  const estado = String(raw);
  let cls = 'bg-secondary';
  if (/aprob/i.test(estado)) cls = 'bg-success';
  else if (/rech/i.test(estado)) cls = 'bg-danger';
  else if (/pend/i.test(estado)) cls = 'bg-warning text-dark';
  return { texto: estado, cls };
}

// Crear contenedores de UI si no existen
function ensureUI(){
  const modalBody = document.querySelector('#modalSolicitudCapacitaciones .modal-body');
  if (!modalBody) return null;

  let ui = modalBody.querySelector('.solicitudes-ui');
  if (!ui) {
    ui = document.createElement('div');
    ui.className = 'solicitudes-ui';
    ui.innerHTML = `
      <div id="solicitudesHeader" class="mb-3">
        <div class="d-flex gap-2 flex-wrap align-items-center">
          <div class="input-group" style="max-width:360px;">
            <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
            <input id="solicitudesSearch" type="search" class="form-control" placeholder="Buscar por persona o contenido...">
          </div>
          <div id="solicitudesFilters" class="d-flex gap-2 flex-wrap"></div>
          <div class="ms-auto small text-muted" id="solicitudesCounter"></div>
        </div>
      </div>
    `;
    modalBody.prepend(ui);
  }
  return {
    filters: ui.querySelector('#solicitudesFilters'),
    counter: ui.querySelector('#solicitudesCounter'),
    search:  ui.querySelector('#solicitudesSearch'),
    list:    modalBody.querySelector('.list-group')
  };
}

// Skeleton simple
function renderSkeleton(list, rows=4){
  list.innerHTML = '';
  for (let i=0;i<rows;i++){
    const s = document.createElement('div');
    s.className = 'card mb-3 border-0';
    s.innerHTML = `
      <div class="card-body">
        <div class="placeholder-glow mb-2">
          <span class="placeholder col-8"></span>
          <span class="placeholder col-2"></span>
        </div>
        <div class="placeholder-glow">
          <span class="placeholder col-12"></span>
          <span class="placeholder col-10"></span>
          <span class="placeholder col-6"></span>
        </div>
      </div>
    `;
    list.appendChild(s);
  }
}

// Chips de filtro por estado
function buildFilterChips(filtersEl, counts, current){
  const total = (counts['Todos'] ?? 0);
  const entries = Object.entries(counts).sort((a,b)=> a[0]==='Todos' ? -1 : (b[0]==='Todos'?1: a[0].localeCompare(b[0])));
  filtersEl.innerHTML = '';
  for (const [estado, cnt] of entries){
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = `btn btn-sm ${current===estado ? 'btn-primary' : 'btn-outline-primary'}`;
    btn.dataset.estado = estado;
    btn.innerHTML = `${estado} <span class="badge bg-light text-dark ms-1">${cnt}</span>`;
    filtersEl.appendChild(btn);
  }
}

function showComentarios(htmlComentarios = ''){
  const safe = sanitizeBasic(String(htmlComentarios || '').replace(/\n/g, '<br>'));
  Swal.fire({
    icon: 'info',
    title: 'Comentarios de respuesta del administrador',
    html: `<div class="text-start" style="max-height:50vh;overflow:auto">${safe || '<em>No hay comentarios del administrador.</em>'}</div>`,
    width: 700,
    confirmButtonText: 'Cerrar'
  });
}

// Tarjeta de solicitud (DOM, sin concatenar innerHTML)
function renderCard(item){
  const nombre = item.nombre_completo || item.nombre || 'Desconocido';
  const fechaRaw = item.fecha_solicitud || item.fecha_registro || item.dateCreated || '';
  const fecha = fmtFechaHora(fechaRaw);
  const ago = timeAgo(fechaRaw);
  const comentarios = item.comentarios || '';
  const {texto: estado, cls} = estadoInfo(item);

  const card = document.createElement('div');
  card.className = 'card mb-3 shadow-sm border-0';
  card.innerHTML = `
    <div class="card-body" style="border-left:6px solid transparent;">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="fw-semibold text-primary mb-0">
          <i class="fa-solid fa-chalkboard-user me-1"></i>
          ${escapeHtml(nombre)}
          <span class="badge ${cls} ms-2">${escapeHtml(estado)}</span>
        </h6>
        <div class="d-flex align-items-center gap-2">
          <small class="text-muted me-1" title="${escapeHtml(fecha)}">${escapeHtml(fecha)} • ${escapeHtml(ago)}</small>
          <!-- contenedor para acciones de cabecera -->
          <span class="card-actions"></span>
        </div>
      </div>
      <div class="mt-2">
        <div class="solicitud-text clamp" style="-webkit-line-clamp:4; display:-webkit-box; -webkit-box-orient:vertical; overflow:hidden;"></div>
        <div class="d-flex align-items-center gap-3 mt-1">
          <button class="btn btn-link btn-sm p-0 ver-mas">ver más</button>
          <!-- aquí podría insertarse el botón de comentarios -->
          <span class="slot-comentarios"></span>
        </div>
      </div>
    </div>
  `;

  // Color de borde según estado
  const left = card.querySelector('.card-body');
  if (/aprob/i.test(estado)) left.style.borderLeftColor = '#198754';
  else if (/rech/i.test(estado)) left.style.borderLeftColor = '#dc3545';
  else if (/pend/i.test(estado)) left.style.borderLeftColor = '#ffc107';

  // Contenido sanitizado y truncable
  const solicitudHTML = sanitizeBasic(item.solicitud || '');
  const txt = card.querySelector('.solicitud-text');
  txt.innerHTML = solicitudHTML;

  // Ver más / ver menos
  const btnMore = card.querySelector('.ver-mas');
  let expanded = false;
  btnMore.addEventListener('click', ()=>{
    expanded = !expanded;
    txt.style['-webkit-line-clamp'] = expanded ? 'unset' : '4';
    txt.style.display = expanded ? 'block' : '-webkit-box';
    txt.style.overflow = expanded ? 'visible' : 'hidden';
    btnMore.textContent = expanded ? 'ver menos' : 'ver más';
  });

  // === Botón "Ver comentarios" SOLO si hay contenido ===
  if (String(comentarios).trim().length > 0){
    const slot = card.querySelector('.slot-comentarios') || card.querySelector('.card-actions');
    const btnC = document.createElement('button');
    btnC.type = 'button';
    btnC.className = 'btn btn-outline-secondary btn-sm';
    btnC.innerHTML = `<i class="fa-regular fa-comments me-1"></i> Ver comentarios`;
    btnC.addEventListener('click', ()=> showComentarios(comentarios));
    // si hay slot inferior, lo usamos; si no, lo ponemos en acciones de cabecera
    (slot || card.querySelector('.card-actions')).appendChild(btnC);
  }

  return card;
}


// Estado global simple
const SolicitudesStore = {
  all: [],
  view: { estado: 'Todos', q: '' }
};

function applyFilters(){
  const {all, view} = SolicitudesStore;
  const q = view.q.trim().toLowerCase();
  return all.filter(x=>{
    const {texto: est} = estadoInfo(x);
    const byEstado = (view.estado==='Todos') || new RegExp(view.estado, 'i').test(est);
    if (!byEstado) return false;
    if (!q) return true;
    const nombre = (x.nombre_completo || x.nombre || '').toLowerCase();
    const contenido = (x.solicitud || '').toLowerCase().replace(/<[^>]+>/g,' ');
    return nombre.includes(q) || contenido.includes(q);
  });
}

function countByEstado(list){
  const counts = {'Todos': list.length};
  for (const it of list){
    const {texto} = estadoInfo(it);
    const key = (/aprob/i.test(texto) ? 'Aprobada' : /rech/i.test(texto) ? 'Rechazada' : 'Pendiente');
    counts[key] = (counts[key]||0)+1;
  }
  return counts;
}

function sortByFechaDesc(list){
  return [...list].sort((a,b)=>{
    const da = new Date(String(a.fecha_solicitud || a.fecha_registro || a.dateCreated || '').replace(' ','T')).getTime() || 0;
    const db = new Date(String(b.fecha_solicitud || b.fecha_registro || b.dateCreated || '').replace(' ','T')).getTime() || 0;
    return db - da;
  });
}

function debounce(fn, ms){ let t; return (...args)=>{ clearTimeout(t); t=setTimeout(()=>fn(...args), ms); }; }

// Buscar solicitudes por organismo (mejorado)
window.buscarSolicitudesCapacitacion = async function (){
  const ui = ensureUI();
  if (!ui) return null;

  // Skeleton mientras carga
  renderSkeleton(ui.list);

  try{
    const data = await $.ajax({
      type: 'POST',
      url: 'controller/organismo/forms.php',
      data: { action: 'buscarSolicitudesCapacitacion' },
      dataType: 'json', // asegura parseo JSON
      cache: false
    });

    const arr = Array.isArray(data) ? data : [];
    const sorted = sortByFechaDesc(arr);
    SolicitudesStore.all = sorted;

    // Conteos y chips
    const counts = countByEstado(sorted);
    buildFilterChips(ui.filters, counts, SolicitudesStore.view.estado);
    ui.counter.textContent = `${sorted.length} en total`;

    // Eventos de UI (una sola vez)
    if (!ui.filters.dataset.bound){
      ui.filters.addEventListener('click', (ev)=>{
        const btn = ev.target.closest('button[data-estado]');
        if(!btn) return;
        SolicitudesStore.view.estado = btn.dataset.estado;
        buildFilterChips(ui.filters, counts, SolicitudesStore.view.estado);
        renderList();
      });
      ui.filters.dataset.bound = '1';
    }
    if (!ui.search.dataset.bound){
      ui.search.addEventListener('input', debounce(()=>{
        SolicitudesStore.view.q = ui.search.value || '';
        renderList();
      }, 220));
      ui.search.dataset.bound = '1';
    }

    // Render inicial
    renderList();

  } catch(err){
    console.error('Error al buscar solicitudes de capacitación:', err);
    ui.list.innerHTML = `<div class="text-center text-muted py-4">
      <i class="fa-regular fa-face-frown me-1"></i> No se pudieron cargar las solicitudes.
    </div>`;
    return null;
  }

  function renderList(){
    const {list, counter} = ui;
    const filtered = applyFilters();
    counter.textContent = `${filtered.length} coinciden`;

    list.innerHTML = '';
    if (filtered.length === 0){
      list.innerHTML = '<div class="text-center text-muted py-4">No hay solicitudes registradas.</div>';
      return;
    }

    // Render incremental para grandes volúmenes
    const pageSize = 30;
    let idx = 0;
    const frag = document.createDocumentFragment();

    function appendChunk(){
      const end = Math.min(idx+pageSize, filtered.length);
      for (let i=idx;i<end;i++){
        frag.appendChild(renderCard(filtered[i]));
      }
      list.appendChild(frag);
      idx = end;
    }

    appendChunk();

    // Carga bajo demanda al hacer scroll
    const onScroll = ()=>{
      const nearBottom = list.scrollTop + list.clientHeight >= list.scrollHeight - 60;
      if (nearBottom && idx < filtered.length){
        appendChunk();
      }
    };
    // Asegura el listener una sola vez por render
    list.removeEventListener('scroll', onScroll);
    list.addEventListener('scroll', onScroll, {passive:true});
  }
};

// =====================================
// Aspirantes Pendientes (Teacher)
// =====================================
(function () {
  'use strict';

  const TEACHER_API = 'controller/practices/teacher.php';
  const post = (data) => $.ajax({ url: TEACHER_API, method: 'POST', data, dataType: 'json' });
  const ok  = (msg) => Swal.fire({ icon: 'success', title: '¡Listo!', text: msg, timer: 1800, showConfirmButton: false });
  const err = (msg) => Swal.fire({ icon: 'error', title: 'Error', text: msg });
  const confirmDlg = (title, text) =>
    Swal.fire({ title, text, icon: 'question', showCancelButton: true,
                confirmButtonText: 'Sí', cancelButtonText: 'Cancelar' })
      .then(r => r.isConfirmed);

  let _aspirantes = [];

  function initials(name) {
    return (name || '').split(' ').slice(0, 2).map(w => (w[0] || '').toUpperCase()).join('');
  }

  function renderAspirantes(list) {
    const container = document.getElementById('listaAspirantes');
    if (!container) return;
    if (!list.length) {
      container.innerHTML = `
        <div class="text-center py-5 text-muted">
          <div style="font-size:2.5rem;margin-bottom:.5rem;">🎉</div>
          <strong>Sin aspirantes pendientes</strong>
          <p class="mt-1 small">No hay solicitudes en espera de revisión.</p>
        </div>`;
      return;
    }
    container.innerHTML = list.map(s => `
      <div class="card asp-card" data-id="${s.id}">
        <div class="card-body">
          <div class="d-flex align-items-start gap-3">
            <div class="asp-avatar">${initials(s.nombre_completo)}</div>
            <div class="flex-grow-1">
              <div class="fw-semibold">${escapeHtml(s.nombre_completo || '—')}</div>
              <div class="small text-muted">${escapeHtml(s.matricula || '')} &bull; ${escapeHtml(s.email || '')}</div>
              <div class="small text-muted mt-1">
                <i class="fas fa-book me-1"></i>${escapeHtml(s.programa_academico || '—')}
                &nbsp;|&nbsp;
                <i class="fas fa-calendar-alt me-1"></i>${(s.fecha_registro || '').split(' ')[0]}
              </div>
            </div>
            <div class="d-flex gap-2 flex-shrink-0">
              <button class="asp-btn-accept btn-asp-accept" data-id="${s.id}">
                <i class="fas fa-check"></i> Aceptar
              </button>
              <button class="asp-btn-reject btn-asp-reject" data-id="${s.id}">
                <i class="fas fa-times"></i> Rechazar
              </button>
            </div>
          </div>
        </div>
      </div>`).join('');
  }

  function loadAspirantes() {
    const container = document.getElementById('listaAspirantes');
    if (container) {
      container.innerHTML = '<div class="text-center py-5 text-muted"><i class="fas fa-spinner fa-spin me-2" style="color:#01643D;"></i>Cargando aspirantes…</div>';
    }
    post({ action: 'getAspirantesPendientes' }).then(data => {
      _aspirantes = Array.isArray(data) ? data : [];
      // Badge en el botón
      const badge = document.getElementById('badgeAspirantes');
      if (badge) {
        if (_aspirantes.length > 0) {
          badge.textContent = _aspirantes.length;
          badge.classList.remove('d-none');
        } else {
          badge.classList.add('d-none');
        }
      }
      renderAspirantes(_aspirantes);
    }).fail(() => {
      if (container) container.innerHTML = '<div class="text-center text-danger py-4"><i class="fas fa-exclamation-circle me-2"></i>Error al cargar los aspirantes.</div>';
    });
  }

  // Cargar al abrir el modal
  document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('modalAspirantes');
    if (modalEl) {
      modalEl.addEventListener('show.bs.modal', loadAspirantes);
    }
    // Carga el badge al iniciar la página
    post({ action: 'getAspirantesPendientes' }).then(data => {
      const list = Array.isArray(data) ? data : [];
      const badge = document.getElementById('badgeAspirantes');
      if (badge && list.length > 0) {
        badge.textContent = list.length;
        badge.classList.remove('d-none');
      }
    });

    // Búsqueda en tiempo real
    const searchInput = document.getElementById('inputBuscarAspirante');
    if (searchInput) {
      searchInput.addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        if (!q) { renderAspirantes(_aspirantes); return; }
        renderAspirantes(_aspirantes.filter(s =>
          (s.nombre_completo || '').toLowerCase().includes(q) ||
          (s.matricula || '').toLowerCase().includes(q)
        ));
      });
    }

    // Botón Ver Evaluaciones
    document.addEventListener('click', function(e) {
      const btn = e.target.closest('.btn-asp-eval');
      if (!btn) return;
      window.verEvaluacionesIntegrales(btn.dataset.id, btn.dataset.matricula);
    });

    // Aceptar aspirante
    document.addEventListener('click', async function (e) {
      const btn = e.target.closest('.btn-asp-accept');
      if (!btn) return;
      const id = btn.dataset.id;
      if (!await confirmDlg('¿Aceptar aspirante?', 'Se le enviará un correo con sus credenciales de acceso.')) return;
      btn.disabled = true;
      post({ action: 'accept_student', idStudent: id }).then(res => {
        if (res.success) {
          ok('Aspirante aceptado. Se envió el correo con credenciales.');
          loadAspirantes();
        } else {
          err(res.message || 'No se pudo aceptar al aspirante.');
          btn.disabled = false;
        }
      }).fail(() => { err('Error de comunicación.'); btn.disabled = false; });
    });

    // Rechazar aspirante
    document.addEventListener('click', async function (e) {
      const btn = e.target.closest('.btn-asp-reject');
      if (!btn) return;
      const id = btn.dataset.id;
      if (!await confirmDlg('¿Rechazar solicitud?', 'Esta acción marcará la solicitud como rechazada.')) return;
      btn.disabled = true;
      post({ action: 'denegate_student', idStudent: id }).then(res => {
        if (res.success) {
          ok('Solicitud rechazada.');
          loadAspirantes();
        } else {
          err(res.message || 'No se pudo rechazar la solicitud.');
      btn.disabled = false;
        }
      }).fail(() => { err('Error de comunicación.'); btn.disabled = false; });
    });
  });

})();

// =====================================
// Visor de Evaluaciones Integrales
// =====================================
$(document).ready(function() {
  cargarNotificacionesEvaluaciones();

  $('#btnEvaluacionesIntegrales').on('click', function() {
    Swal.fire({
      icon: 'info',
      title: 'Nuevas Evaluaciones',
      text: 'Abre el panel de cualquier alumno en la tabla usando el botón de estrella para ver sus evaluaciones.',
      confirmButtonColor: '#01643D'
    });
  });
});

function cargarNotificacionesEvaluaciones() {
  $.ajax({
    url: 'controller/organismo/forms.php',
    method: 'POST',
    data: { action: 'getEvaluacionesRecientesAdmin' },
    dataType: 'json',
    success: function(res) {
      if (res && res.count > 0) {
        $('#badgeEvaluaciones').text(res.count).removeClass('d-none');
      } else {
        $('#badgeEvaluaciones').addClass('d-none');
      }
    }
  });
}

window.verEvaluacionesIntegrales = function(idStudent, matricula) {
  // Limpiar modal
  $('.visor-empty').html('<i class="fas fa-spinner fa-spin me-2"></i>Cargando datos...');
  $('#visor-fase1-empresa, #visor-fase1-alumno, #visor-fase2-empresa, #visor-fase2-alumno').html('<div class="visor-empty"><i class="fas fa-spinner fa-spin me-2"></i>Cargando datos...</div>');
  
  // Abrir modal
  const modal = new bootstrap.Modal(document.getElementById('modalVerEvaluacionesAdmin'));
  modal.show();

  // Marcar como vistas
  $.ajax({
    url: 'controller/organismo/forms.php',
    method: 'POST',
    data: { action: 'marcarEvaluacionesVistas', idStudent: idStudent },
    success: function() {
      cargarNotificacionesEvaluaciones();
    }
  });

  // Cargar datos
  $.ajax({
    url: 'controller/organismo/forms.php',
    method: 'POST',
    data: { action: 'getEvaluacionesAlumnoCompleta', idStudent: idStudent },
    dataType: 'json',
    success: function(evaluaciones) {
      const renderEval = (container, data) => {
        if (!data) {
          container.html('<div class="visor-empty"><i class="fas fa-folder-open me-2"></i>Aún no hay evaluación registrada.</div>');
          return;
        }
        let html = '';
        if (data.comentarios_generales) {
          html += `<div class="alert alert-info py-2 px-3 small mb-3"><strong>Comentarios generales:</strong><br>${escapeHtml(data.comentarios_generales)}</div>`;
        }
        data.respuestas.forEach(r => {
          html += `<div class="visor-question">${r.index}. Pregunta/Criterio</div>`;
          if (r.tipo === 'likert') {
            const stars = Array.from({length: 5}, (_, i) => i < r.valor_numerico ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>').join('');
            html += `<div class="visor-answer d-flex justify-content-between align-items-center">
                       <span>Valor: ${r.valor_numerico}/5</span>
                       <span class="visor-stars">${stars}</span>
                     </div>`;
          } else {
            html += `<div class="visor-answer">${escapeHtml(r.valor_texto || 'No respondió')}</div>`;
          }
        });
        container.html(html);
      };

      const map = {
        'intermedia_empresa': $('#visor-fase1-empresa'),
        'intermedia_alumno': $('#visor-fase1-alumno'),
        'final_empresa': $('#visor-fase2-empresa'),
        'final_alumno': $('#visor-fase2-alumno')
      };

      // Resetear
      Object.values(map).forEach(el => el.html('<div class="visor-empty"><i class="fas fa-folder-open me-2"></i>Aún no hay evaluación registrada.</div>'));

      // Poblar
      evaluaciones.forEach(ev => {
        const key = `${ev.hito}_${ev.evaluador}`;
        if (map[key]) renderEval(map[key], ev);
      });
    },
    error: function() {
      $('.visor-empty').html('<i class="fas fa-exclamation-circle text-danger me-2"></i>Error al cargar los datos.');
    }
  });
};
