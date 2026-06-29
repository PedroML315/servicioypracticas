// view/assets/js/externals_complete.js

$(document).ready(function () {
  // ===========================
  // 0) CSS inyectado (ligero)
  // ===========================
  (function injectExternalsStyles() {
    if (document.getElementById('externals-css')) return;
    const css = `
    .ext-card{border:1px solid #e9ecef;border-radius:.75rem}
    .ext-card .card-body{padding:1rem 1rem}
    .ext-grid{display:grid;grid-template-columns:1fr;gap:12px}
    @media(min-width:768px){.ext-grid{grid-template-columns:1.2fr .8fr}}
    .ext-dl{margin:0}
    .ext-dl dt{color:#6c757d;font-weight:600}
    .ext-dl dd{margin-bottom:.5rem}
    .ext-meta .badge{font-weight:500;margin-right:.35rem}
    .ext-files .list-group-item{background:transparent;border:0;padding:.25rem 0}
    .ext-files a{display:inline-block;max-width:100%;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .ext-actividades{max-height:96px;overflow:hidden;transition:max-height .2s ease}
    .ext-title{margin:0}
    .pill{display:inline-block;padding:.2rem .5rem;border-radius:999px;font-size:.75rem;vertical-align:middle}
    .pill-green{background:#e6f4ea;color:#1f7a3e;border:1px solid #cdebd7}
    .pill-gray{background:#f1f3f5;color:#495057;border:1px solid #e9ecef}
    .pill-yellow{background:#fff3cd;color:#664d03;border:1px solid #ffe69c}
    .pill-red{background:#fdecea;color:#b42318;border:1px solid #f5c2c7}
    .text-sub{color:#6c757d;font-size:.85rem}
    `;
    const tag = document.createElement('style');
    tag.id = 'externals-css';
    tag.appendChild(document.createTextNode(css));
    document.head.appendChild(tag);
  })();

  // ===========================
  // 1) Helpers
  // ===========================
  function esc(v) {
    return String(v ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;')
      .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }
  function joinPipe(items) { return items.map(x => (x ?? '').toString().trim()).filter(Boolean).join(' | '); }
  function dateMX(d) {
    if (!d) return '—';
    const dt = new Date(String(d).replace(' ', 'T'));
    return isNaN(dt) ? '—' : dt.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' });
  }
  function toHref(url) {
    const u = (url || '').trim();
    if (!u) return '';
    if (/^https?:\/\//i.test(u)) return u;
    return 'http://' + u; // fallback si viene sin esquema
  }
  function pill(text, kind = 'gray') {
    const cls = kind === 'green' ? 'pill-green' : kind === 'yellow' ? 'pill-yellow' : kind === 'red' ? 'pill-red' : 'pill-gray';
    return `<span class="pill ${cls}">${esc(text)}</span>`;
  }

  // ===========================
  // 2) DataTable
  // ===========================
  var table = $("#externalsTable").DataTable({
    ajax: {
      url: "controller/ajax/externals.php",
      type: "POST",
      data: { action: "get_externals" },
      dataType: "json",
      dataSrc: "",
    },
    columns: [
      { className: "dt-control", orderable: false, data: null, defaultContent: "" },
      { data: null, render: (data, type, row, meta) => meta.row + 1 },
      {
        data: null, name: "empresa",
        render: function (row) {
          const title = esc(row.empresa || 'Organismo sin nombre');
          const sub = joinPipe([row.ciudad, row.email]);
          return `
            <div>
              <div class="font-weight-bold">${title}</div>
              ${sub ? `<div class="text-sub">${esc(sub)}</div>` : ''}
            </div>
          `;
        }
      },
      { data: "ciudad", name: "ciudad", render: v => esc(v || '—') },
      { data: "email", name: "email", render: v => v ? `<a href="mailto:${esc(v)}">${esc(v)}</a>` : '—' },
      {
        data: null, name: "estado",
        render: function (row) {
          const active = Number(row.isActive) === 1;
          const accepted = Number(row.isAcepted);
          const base = active ? pill('Activo', 'green') : pill('Inactivo', 'gray');
          const sec = accepted === 1 ? pill('Aceptado', 'green') :
            accepted === 0 ? pill('Pendiente', 'yellow') :
              accepted === 2 ? pill('Rechazado', 'red') : '';
          return `<div class="d-flex align-items-center">${base}${sec ? `<span class="ml-2">${sec}</span>` : ''}</div>`;
        }
      },
      {
        data: null, orderable: false,
        render: function (row) {
          if (Number(row.isAcepted) === 0) {
            return `
              <div class="btn-group">
                <button class="btn btn-sm btn-success accept-external"
                        data-bs-toggle="tooltip" data-bs-placement="top"
                        data-bs-title="Aceptar" data-id="${row.id}">
                  <i class="fas fa-check"></i>
                </button>
                <button class="btn btn-sm btn-warning reject-external"
                        data-bs-toggle="tooltip" data-bs-placement="top"
                        data-bs-title="Rechazar" data-id="${row.id}">
                  <i class="fas fa-times"></i>
                </button>
              </div>`;
          } else if (Number(row.isAcepted) === 1) {
            return `
              <div class="btn-group">
                <button class="btn btn-sm btn-danger disable-external"
                        data-bs-toggle="tooltip" data-bs-placement="top"
                        data-bs-title="Deshabilitar" data-id="${row.id}">
                  <i class="fas fa-ban"></i>
                </button>
              </div>`;
          }
          return "";
        },
      },
    ],
    responsive: {
      details: { type: "column", target: "td.dt-control" },
    },
    order: [[1, "asc"]],
    language: {
      sProcessing: "Procesando...",
      sLengthMenu: "Mostrar _MENU_ registros",
      sZeroRecords: "No se encontraron resultados",
      sEmptyTable: "Ningún dato disponible en esta tabla",
      sInfo: "Mostrando _START_ a _END_ de _TOTAL_",
      sInfoEmpty: "Mostrando 0 a 0 de 0",
      sInfoFiltered: "(filtrado de _MAX_ en total)",
      sSearch: "Buscar:",
      sLoadingRecords: "Cargando...",
      oPaginate: { sFirst: "Primero", sLast: "Último", sNext: "Siguiente", sPrevious: "Anterior" },
      buttons: { copy: "Copiar", colvis: "Columnas" }
    }
  });

  // Tooltips al iniciar y en cada redraw
  initTooltips();
  table.on('draw.dt', function () { initTooltips('#externalsTable'); });

  // ===========================
  // 3) Detalle expandible bonito
  // ===========================
  function formatDetails(d) {
    const dir = joinPipe([d.calle, d.colonia, d.ciudad, d.cp ? `CP ${d.cp}` : '']);
    const cont = joinPipe([d.nombre_contacto, d.email, d.celular, d.telefonos, d.tel_oficina]);
    const legal = joinPipe([d.rep_legal, d.cargo_legal, d.email_legal]);
    const web = (d.web || '').trim();
    const fechaConst = dateMX(d.fecha_constitucion);
    const creado = dateMX(d.created_at);
    const files = Array.isArray(d.files) ? d.files : [];

    return `
      <div class="card ext-card shadow-sm">
      <div class="card-body">
        <div class="ext-grid">
        <!-- Columna izquierda -->
        <div>
          <div class="d-flex align-items-center mb-2">
          <h5 class="ext-title mr-2">${esc(d.empresa || 'Organismo sin nombre')}</h5>
          <div class="ext-meta">
            ${d.giro ? `<span class="badge badge-secondary">Giro: ${esc(d.giro)}</span>` : ''}
            ${d.tipo_persona ? `<span class="badge badge-secondary">Tipo: ${esc(d.tipo_persona)}</span>` : ''}
          </div>
          </div>

          <dl class="row ext-dl">
          <dt class="col-sm-2">Constitución</dt>
          <dd class="col-sm-10">${esc(fechaConst)}</dd>

          <dt class="col-sm-2">Dirección</dt>
          <dd class="col-sm-10">${esc(dir || '—')}</dd>

          <dt class="col-sm-2">Contacto</dt>
          <dd class="col-sm-10">${esc(cont || '—')}</dd>

          <dt class="col-sm-2">Rep. legal</dt>
          <dd class="col-sm-10">${esc(legal || '—')}</dd>

          <dt class="col-sm-2">Registrado</dt>
          <dd class="col-sm-10">${esc(creado)}</dd>

          ${web ? `
            <dt class="col-sm-2">Web</dt>
            <dd class="col-sm-10"><a href="${toHref(web)}" target="_blank" rel="noopener">${esc(web)}</a></dd>
          `: ''}
          </dl>
        </div>

        <!-- Columna derecha -->
        <div class="pl-md-3">
          <div>
          <div class="text-uppercase text-muted small mb-1">Documentos</div>
          ${files.length
          ? `<div class="d-flex flex-column gap-2">
              ${files.map(f => `
              <button class="btn btn-outline-primary btn-sm text-truncate" style="max-width: 100%;"
                onclick="window.open('uploads/${encodeURIComponent(d.id)}/${encodeURIComponent(f)}', '_blank')"
                title="${esc(f)}">
                <i class="far fa-file mr-1"></i> ${esc(f)}
              </button>
              `).join('')}
            </div>`
          : `<div class="text-muted small">Sin documentos adjuntos</div>`
          }
          </div>
        </div>
        </div>
      </div>
      </div>
    `;
  }

  // 3.1) Toggle de "Ver más / Ver menos" dentro del child row
  $('#externalsTable tbody').on('click', 'a[data-act-toggle]', function (e) {
    e.preventDefault();
    const id = $(this).attr('data-act-toggle');
    const box = document.getElementById(id);
    if (!box) return;
    const expanded = box.getAttribute('data-expanded') === '1';
    if (expanded) {
      box.style.maxHeight = '96px';
      box.setAttribute('data-expanded', '0');
      this.textContent = 'Ver más';
    } else {
      box.style.maxHeight = 'none';
      box.setAttribute('data-expanded', '1');
      this.textContent = 'Ver menos';
    }
  });

  // 3.2) Toggle detalles (row child)
  $("#externalsTable tbody").on("click", "td.dt-control", function () {
    var tr = $(this).closest("tr");
    var row = table.row(tr);
    if (row.child.isShown()) {
      row.child.hide();
      tr.removeClass("shown");
    } else {
      row.child(formatDetails(row.data())).show();
      tr.addClass("shown");
      initTooltips(tr.next('tr')); // tooltips dentro del child
    }
  });

  // ===========================
  // 4) Confirmación y acciones
  // ===========================
  function showConfirmation(message, callback) {
    $("#confirmationModal .modal-body").text(message);
    $("#confirmationModal").modal("show");
    $("#confirmBtn").off("click").on("click", function () {
      $("#confirmationModal").modal("hide");
      callback();
    });
  }

  $("#externalsTable").on(
    "click",
    ".accept-external, .reject-external, .disable-external, .delete-external",
    function (e) {
      e.preventDefault();
      var btn = $(this);
      var id = btn.data("id");
      var action = btn.hasClass("accept-external")
        ? "aceptar"
        : btn.hasClass("reject-external")
          ? "rechazar"
          : btn.hasClass("disable-external")
            ? "inhabilitar"
            : "eliminar";
      var message = `¿Estás seguro que deseas ${action} este organismo externo?`;

      showConfirmation(message, function () {
        var url, data;
        if (action === "aceptar") {
          url = "controller/ajax/externals.php";
          data = { action: "accept_external", id: id };
        } else if (action === "rechazar") {
          url = "controller/ajax/externals.php";
          data = { action: "reject_external", id: id };
        } else if (action === "inhabilitar") {
          url = "controller/ajax/externals.php";
          data = { action: "disable_external", id: id };
        } else {
          url = "ajax/deleteExternal.php";
          data = { id: id };
        }
        $.post(url, data, function (res) {
          if (res && (res.success || res === true)) {
            table.ajax.reload(null, false);
          } else if (res && res.error) {
            alert("Error: " + res.error);
          } else {
            table.ajax.reload(null, false);
          }
        }, "json");
      });
    }
  );

  // ===========================
  // 5) Editar (igual que tu flujo)
  // ===========================
  $("#externalsTable").on("click", ".edit-external", function (e) {
    e.preventDefault();
    var id = $(this).data("id");
    $.getJSON("controller/ajax/externals.php", { action: "get_external", id: id }, function (data) {
      $("#editId").val(data.id);
      $("#editEmpresa").val(data.empresa);
      $("#editCiudad").val(data.ciudad);
      $("#editEmail").val(data.email);
      $("#editEstado").val(data.isActive);
      $("#editExternalModal").modal("show");
    });
  });

  $("#saveEditBtn").on("click", function () {
    var payload = {
      action: "update_external",
      id: $("#editId").val(),
      empresa: $("#editEmpresa").val(),
      ciudad: $("#editCiudad").val(),
      email: $("#editEmail").val(),
      isActive: $("#editEstado").val(),
    };
    $.post("controller/ajax/externals.php", payload, function (res) {
      if (res && res.success) {
        $("#editExternalModal").modal("hide");
        table.ajax.reload(null, false);
      } else {
        alert("Error al guardar: " + (res?.error || ''));
      }
    }, "json");
  });
});

function initTooltips(context) {
  const $scope = context ? $(context) : $(document);
  $scope.find('[data-bs-toggle="tooltip"]').each(function () {
    const inst = bootstrap.Tooltip.getInstance(this);
    if (inst) inst.dispose();
    new bootstrap.Tooltip(this, { container: 'body', trigger: 'hover focus', boundary: document.body });
  });
}
