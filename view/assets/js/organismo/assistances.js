(() => {
  "use strict";

  // ===== Config
  const ENDPOINT = "controller/organismo/forms.php";
  const TABLE_SEL = "#assistancesTable";

  // ===== Helpers
  const postJSON = (payload) =>
    $.ajax({
      url: ENDPOINT,
      method: "POST",
      data: payload,
      dataType: "json",
    });

  const notify = (title, text, icon = "success") =>
    Swal.fire({ title, text, icon, timer: 1600, showConfirmButton: false });

  const confirmAction = async ({ title, icon = "question", confirmText = "Sí" }) =>
    Swal.fire({
      title,
      icon,
      showCancelButton: true,
      confirmButtonText: confirmText,
      cancelButtonText: "Cancelar",
    }).then((r) => r.isConfirmed);

  const reloadTable = () => window.customAssistancesTable && window.customAssistancesTable.reload();

  const fmtDate = (fecha, hora) => {
    if (!fecha || !hora) return "";
    const date = new Date(`${fecha}T${hora}`);
    if (Number.isNaN(date.getTime())) return "";
    const fmt = new Intl.DateTimeFormat("es-MX", {
      weekday: "long",
      year: "numeric",
      month: "short",
      day: "2-digit",
      hour: "numeric",
      minute: "2-digit",
      hour12: true,
    });
    const s = fmt.format(date).replace(/\./g, "");
    return s.charAt(0).toUpperCase() + s.slice(1);
  };

  const renderEstado = (status) => {
    const map = {
      pendiente: ["warning", "Pendiente"],
      aprobada: ["success", "Aprobada"],
      rechazada: ["danger", "Rechazada"],
    };
    const [cls, text] = map[status] || ["secondary", "Desconocido"];
    return `<span class="badge bg-${cls}">${text}</span>`;
  };

  const renderAcciones = (id) => `
    <div class="action-buttons-container d-flex gap-1 flex-wrap align-items-center" id="actions-${id}">
      <button class="btn btn-success btn-sm btn-modern-action" data-action="aprobar" data-id="${id}" title="Aprobar" style="border-radius:0.5rem;">
        <i class="fas fa-check"></i>
      </button>
      <button class="btn btn-danger btn-sm btn-modern-action" data-action="rechazar" data-id="${id}" title="Rechazar" style="border-radius:0.5rem;">
        <i class="fas fa-times"></i>
      </button>
      <button class="btn btn-warning btn-sm btn-modern-action" data-action="cambiar" data-id="${id}" title="Corregir horarios" style="border-radius:0.5rem; color:#fff;">
        <i class="fas fa-clock"></i>
      </button>
    </div>
    <div class="inline-confirm-container d-none flex-wrap align-items-center gap-2" id="confirm-${id}">
       <span class="confirm-text fw-bold text-secondary" style="font-size:0.85rem;"></span>
       <button class="btn btn-sm btn-primary btn-confirm-yes" style="border-radius:0.5rem;">Sí</button>
       <button class="btn btn-sm btn-light btn-confirm-cancel" style="border-radius:0.5rem;">Cancelar</button>
    </div>
    <div class="inline-edit-container d-none flex-wrap align-items-center gap-1" id="edit-${id}">
       <input type="time" class="form-control form-control-sm edit-he" style="max-width:90px; border-radius:0.5rem;">
       <input type="time" class="form-control form-control-sm edit-hs" style="max-width:90px; border-radius:0.5rem;">
       <button class="btn btn-sm btn-success btn-edit-save" style="border-radius:0.5rem;"><i class="fas fa-save"></i></button>
       <button class="btn btn-sm btn-light btn-edit-cancel" style="border-radius:0.5rem;"><i class="fas fa-times"></i></button>
    </div>
  `;

  const validarRangoHoras = (hEntrada, hSalida) => {
    if (!hEntrada || !hSalida) return true;
    const toMin = (h) => {
      const [H, M] = h.split(":").map(Number);
      return H * 60 + M;
    };
    return toMin(hSalida) > toMin(hEntrada);
  };

  // ===== Motor de Tablas Custom
  class CustomTable {
    constructor(options) {
      this.containerId = options.containerId;
      this.url = options.url;
      this.dataPayload = options.dataPayload;
      this.columns = options.columns || [];
      this.cardRender = options.cardRender || null;
      this.dataSrc = options.dataSrc || ((d) => d);
      this.pageSize = 10;
      this.currentPage = 1;
      this.data = [];
      this.filteredData = [];
      
      this.$target = $(this.containerId);
      // Keep ID on the wrapper so other code using the selector doesn't completely fail, but actually we will wrap it.
      this.$wrapper = $(`<div class="custom-table-wrapper" id="${this.containerId.replace('#','')}"></div>`);
      this.$target.replaceWith(this.$wrapper);
      
      this.init();
    }
    
    async init() {
      this.$wrapper.html(`<div class="text-center py-5 text-muted"><i class="fas fa-spinner fa-spin fa-2x mb-3"></i><p>Cargando datos...</p></div>`);
      await this.fetchData();
      this.renderLayout();
      this.renderBody();
    }

    async fetchData() {
      try {
        const resp = await $.ajax({
          url: this.url,
          type: "POST",
          data: this.dataPayload,
          dataType: "json"
        });
        this.data = this.dataSrc(resp) || [];
        this.filteredData = [...this.data];
      } catch(e) {
        console.error(e);
        this.data = [];
        this.filteredData = [];
      }
    }

    async reload() {
      await this.fetchData();
      this.handleSearch();
    }

    renderLayout() {
      this.$wrapper.html(`
        <div class="d-flex justify-content-end mb-3 search-container">
          <div class="search-box position-relative" style="max-width: 300px; width: 100%;">
            <input type="text" class="form-control" style="border-radius: 2rem; padding: 0.5rem 1.25rem; background: #f8fafc; border: 1px solid #e2e8f0; outline: none; transition: all 0.2s;" placeholder="Buscar...">
          </div>
        </div>
        <div class="list-container d-flex flex-column gap-0"></div>
        <div class="pagination-container d-flex justify-content-between align-items-center mt-3"></div>
      `);
      
      this.$searchInput = this.$wrapper.find('input');
      this.$tbody = this.$wrapper.find('.list-container');
      this.$pagination = this.$wrapper.find('.pagination-container');
      
      this.$searchInput.on('input', () => {
        this.currentPage = 1;
        this.handleSearch();
      });
    }

    handleSearch() {
      const term = this.$searchInput.val().toLowerCase();
      if (!term) {
        this.filteredData = [...this.data];
      } else {
        this.filteredData = this.data.filter(row => JSON.stringify(row).toLowerCase().includes(term));
      }
      this.renderBody();
    }

    renderBody() {
      if (this.filteredData.length === 0) {
        this.$tbody.html(`<div class="text-center py-5 text-muted border rounded-3 bg-light">No se encontraron registros.</div>`);
        this.$pagination.empty();
        return;
      }
      
      const totalPages = Math.ceil(this.filteredData.length / this.pageSize);
      if (this.currentPage > totalPages) this.currentPage = totalPages;
      
      const startIdx = (this.currentPage - 1) * this.pageSize;
      const endIdx = startIdx + this.pageSize;
      const pageData = this.filteredData.slice(startIdx, endIdx);
      
      this.$tbody.empty();
      pageData.forEach(row => {
        const div = document.createElement('div');
        div.rowData = row;
        div.className = "mb-3";
        if (this.cardRender) {
          div.innerHTML = this.cardRender(row);
        } else {
          div.innerHTML = this.columns.map(c => `<span>${c.render ? c.render(row) : (row[c.data]||'')}</span>`).join(' | ');
        }
        this.$tbody.append(div);
      });
      
      this.renderPagination(totalPages);
    }

    renderPagination(totalPages) {
      if (this.data.length <= 10) {
        // Ocultar paginación si son 10 datos o menos
        this.$pagination.empty();
        // this.$wrapper.find('.search-container').hide(); // La búsqueda se mantiene
        return;
      }
      // Mostrar buscador si estaba oculto
      this.$wrapper.find('.search-container').show();
      
      let html = `<div class="text-muted" style="font-size:0.85rem;">Mostrando ${this.filteredData.length} registros</div>`;
      
      if (totalPages > 1) {
        let btns = `<button class="btn btn-sm btn-light me-1 px-3 custom-page-btn" data-page="${this.currentPage - 1}" ${this.currentPage === 1 ? 'disabled' : ''} style="border-radius:0.5rem; background:#f1f5f9; font-weight:600; color:#475569;">Anterior</button>`;
        
        for(let i=1; i<=totalPages; i++) {
          btns += `<button class="btn btn-sm me-1 custom-page-btn" data-page="${i}" style="border-radius:0.5rem; font-weight:600; ${this.currentPage === i ? 'background:#01643D; color:white;' : 'background:#f1f5f9; color:#475569;'}">${i}</button>`;
        }
        
        btns += `<button class="btn btn-sm btn-light ms-1 px-3 custom-page-btn" data-page="${this.currentPage + 1}" ${this.currentPage === totalPages ? 'disabled' : ''} style="border-radius:0.5rem; background:#f1f5f9; font-weight:600; color:#475569;">Siguiente</button>`;
        
        html += `<div>${btns}</div>`;
      } else {
         html += `<div></div>`;
      }
      
      this.$pagination.html(html);
      
      this.$pagination.find('.custom-page-btn').on('click', (e) => {
         const p = parseInt($(e.currentTarget).data('page'));
         if(p >= 1 && p <= totalPages) {
           this.currentPage = p;
           this.renderBody();
         }
      });
    }
  }

  // ===== Inicializar
  $(function () {
    window.customAssistancesTable = new CustomTable({
      containerId: TABLE_SEL,
      url: ENDPOINT,
      dataPayload: { action: "getAssistancesPractices" },
      cardRender: function(row) {
        const estadoHTML = renderEstado(row.status);
        const accionesHTML = renderAcciones(row.idAsistencia);
        return `
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 py-3 px-4 bg-white border" style="border-radius: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
            <div class="d-flex align-items-center gap-3">
              <div style="width:50px;height:50px;border-radius:14px;background:#f8fafc;color:#64748b;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0;">
                <i class="fas fa-clock"></i>
              </div>
              <div>
                <div class="fw-bold d-flex align-items-center flex-wrap gap-2" style="font-size:1.15rem; color:#0f172a;">
                  ${row.nombre_completo}
                  ${estadoHTML}
                </div>
                <div class="text-muted d-flex align-items-center flex-wrap gap-3 mt-1" style="font-size:0.85rem;">
                  <span><i class="fas fa-book me-1 text-primary"></i> ${row.licenciatura}</span>
                  <span><i class="fas fa-sign-in-alt me-1 text-success"></i> Entrada: ${fmtDate(row.fecha, row.hora_entrada)}</span>
                  <span><i class="fas fa-sign-out-alt me-1 text-danger"></i> Salida: ${fmtDate(row.fecha, row.hora_salida)}</span>
                </div>
                <div class="text-secondary mt-2" style="font-size:0.9rem;">
                  <strong>Actividad:</strong> ${(row.actividad && String(row.actividad).trim()) || "<span class='text-muted'>No especificada</span>"}
                </div>
              </div>
            </div>
            <div class="d-flex gap-2 align-items-center">
              ${accionesHTML}
            </div>
          </div>
        `;
      }
    });
  });

  // ===== Handlers delegados
  $(document).on("click", `${TABLE_SEL} [data-action]`, function (e) {
    e.preventDefault();
    const action = $(this).data("action");
    const id = $(this).data("id");
    const rowDiv = $(this).closest(".mb-3")[0];
    const rowData = rowDiv ? rowDiv.rowData : null;

    if (!id || !rowData) return;

    const $actions = $(`#actions-${id}`);
    const $confirm = $(`#confirm-${id}`);
    const $edit = $(`#edit-${id}`);

    if (action === "aprobar") {
      $actions.addClass("d-none");
      $confirm.find(".confirm-text").text("¿Aprobar asistencia?");
      $confirm.find(".btn-confirm-yes").data("action", "aprobar").data("id", id).removeClass("btn-danger").addClass("btn-success");
      $confirm.removeClass("d-none").addClass("d-flex");
    }

    if (action === "rechazar") {
      $actions.addClass("d-none");
      $confirm.find(".confirm-text").text("¿Rechazar asistencia?");
      $confirm.find(".btn-confirm-yes").data("action", "rechazar").data("id", id).removeClass("btn-success").addClass("btn-danger");
      $confirm.removeClass("d-none").addClass("d-flex");
    }

    if (action === "cambiar") {
      $actions.addClass("d-none");
      $edit.find(".edit-he").val(rowData.hora_entrada || "");
      $edit.find(".edit-hs").val(rowData.hora_salida || "");
      $edit.find(".btn-edit-save").data("id", id);
      $edit.removeClass("d-none").addClass("d-flex");
    }
  });

  $(document).on("click", ".btn-confirm-cancel, .btn-edit-cancel", function (e) {
    e.preventDefault();
    const $container = $(this).closest(".d-flex");
    const id = $container.attr("id").split("-")[1];
    $container.removeClass("d-flex").addClass("d-none");
    $(`#actions-${id}`).removeClass("d-none");
  });

  $(document).on("click", ".btn-confirm-yes", async function (e) {
    e.preventDefault();
    const action = $(this).data("action");
    const id = $(this).data("id");
    const rowDiv = $(this).closest(".mb-3");
    
    // Disable button to prevent double click
    const $btn = $(this);
    $btn.prop("disabled", true).html('<i class="fas fa-spinner fa-spin"></i>');

    try {
      const backendAction = action === "aprobar" ? "aprobarAsistencia" : "rechazarAsistencia";
      const resp = await postJSON({ action: backendAction, idAsistencia: id });
      
      if (resp?.success) {
        // Soft fade out without reloading the whole table
        rowDiv.fadeOut(300, function() {
           // Silently reload in background so pagination updates if needed
           reloadTable(); 
        });
        if (window.orgDashboard && typeof window.orgDashboard.loadSummary === 'function') {
          window.orgDashboard.loadSummary();
        }
      } else {
        alert(resp?.message || "Error al procesar la solicitud.");
        $btn.prop("disabled", false).text("Sí");
      }
    } catch (err) {
      alert("Error de conexión.");
      $btn.prop("disabled", false).text("Sí");
    }
  });

  $(document).on("click", ".btn-edit-save", async function (e) {
    e.preventDefault();
    const id = $(this).data("id");
    const rowDiv = $(this).closest(".mb-3");
    const $editContainer = $(`#edit-${id}`);
    
    const he = $editContainer.find(".edit-he").val();
    const hs = $editContainer.find(".edit-hs").val();
    
    if (!validarRangoHoras(he, hs)) {
      alert("La hora de salida debe ser mayor que la de entrada.");
      return;
    }

    const $btn = $(this);
    const originalHtml = $btn.html();
    $btn.prop("disabled", true).html('<i class="fas fa-spinner fa-spin"></i>');

    try {
      const resp = await postJSON({
        action: "actualizarHorarios",
        idAsistencia: id,
        hora_entrada: he,
        hora_salida: hs
      });

      if (resp?.success) {
        // Instead of fadeOut, reload cleanly but without scrolling
        rowDiv.find(".inline-edit-container").html('<span class="text-success fw-bold"><i class="fas fa-check"></i> Actualizado</span>');
        setTimeout(() => reloadTable(), 800);
      } else {
        alert(resp?.message || "Error al actualizar.");
        $btn.prop("disabled", false).html(originalHtml);
      }
    } catch (err) {
      alert("Error de conexión.");
      $btn.prop("disabled", false).html(originalHtml);
    }
  });
})();
