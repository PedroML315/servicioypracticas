(() => {
  "use strict";

  // ===== Constantes
  const ENDPOINT = "controller/organismo/forms.php";
  const SELECTORS = {
    table: "#solicitudesTable",

    modalParcial: "#reporteModal",
    objetivoParcial: "#objetivoReporte",
    actividadesParcial: "#actividadesReporte",
    btnAceptarParcial: "#btnAceptarReporte",
    btnRechazarParcial: "#btnRechazarReporte",

    modalEvalParcial: "#evaluacionParcialParticipanteModal",
    formEvalParcial: "#evaluacionParticipanteForm",

    modalFinal: "#reporteFinalModal",
    objetivoFinal: "#objetivoFinal",
    actividadesFinal: "#actividadesFinal",
    capacitacionRecibida: "#capacitacionRecibida",
    experienciaPersonal: "#experienciaPersonal",
    experienciaProfesional: "#experienciaProfesional",
    resultadosObtenidos: "#resultadosObtenidos",
    btnAceptarFinal: "#btnAceptarReporteFinal",
    btnRechazarFinal: "#btnRechazarReporteFinal",

    modalEvalFinal: "#evaluacionFinalParticipanteModal",
    formEvalFinal: "#evaluacionFinalParticipanteForm",

    btnVerParcial: ".reporte-btn",
    btnVerFinal: ".reporte-final-btn",
  };

  // ===== Helpers
  const ajaxJSON = (data) =>
    $.ajax({ url: ENDPOINT, method: "POST", data, dataType: "json" });

  const reloadTable = () => window.customReportesTable && window.customReportesTable.reload();

  const normaliza = (json) => {
    const parciales = (json.parciales || []).map((r) => ({
      tipo: "Reporte Parcial",
      id: r.idReporteParcial,
      idStudent: r.idStudent,
      idPractica: r.idPractica,
      nombre_completo: r.nombre_completo,
      dateCreated: r.dateCreated,
      objetivo: r.objetivo ?? "",
      actividades: r.actividades_repotadas ?? "",
      raw: r,
    }));

    const finales = (json.finales || []).map((r) => ({
      tipo: "Reporte Final",
      id: r.idReporteFinal,
      idStudent: r.idStudent,
      idPractica: r.idPractica,
      nombre_completo: r.nombre_completo,
      dateCreated: r.dateCreated,
      objetivo: r.objetivo_general ?? "",
      actividades: r.actividades_realizadas ?? "",
      capacitacion_recibida: r.capacitacion_recibida ?? "",
      experiencia_personal: r.experiencia_personal ?? "",
      experiencia_profesional: r.experiencia_profesional ?? "",
      resultados_obtenidos: r.resultados_obtenidos ?? "",
      raw: r,
    }));

    return [...parciales, ...finales];
  };

  const renderAcciones = (row) => {
    if (row.tipo === "Reporte Parcial") {
      return `
        <button type="button" class="btn btn-primary btn-sm reporte-btn px-3" data-action="ver-parcial" style="border-radius:1rem;">
          <i class="fas fa-eye me-1"></i> Ver Parcial
        </button>`;
    }
    return `
      <button type="button" class="btn btn-success btn-sm reporte-final-btn px-3" data-action="ver-final" style="border-radius:1rem;">
        <i class="fas fa-eye me-1"></i> Ver Final
      </button>`;
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
        this.$pagination.empty();
        return;
      }
      
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

  // ===== Init 
  $(function () {
    window.customReportesTable = new CustomTable({
      containerId: SELECTORS.table,
      url: ENDPOINT,
      dataPayload: { action: "getReportsPractices" },
      dataSrc: normaliza,
      cardRender: function(row) {
        const accionesHTML = renderAcciones(row);
        const tipoBadge = `<span class="badge ${row.tipo === 'Reporte Parcial' ? 'bg-primary' : 'bg-success'}" style="border-radius:100px;">${row.tipo}</span>`;
        return `
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 py-3 px-4 bg-white border" style="border-radius: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
            <div class="d-flex align-items-center gap-3">
              <div style="width:50px;height:50px;border-radius:14px;background:#fefce8;color:#ca8a04;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0;">
                <i class="fas fa-file-alt"></i>
              </div>
              <div>
                <div class="fw-bold d-flex align-items-center flex-wrap gap-2" style="font-size:1.15rem; color:#0f172a;">
                  ${row.nombre_completo}
                  ${tipoBadge}
                </div>
                <div class="text-muted d-flex align-items-center flex-wrap gap-3 mt-1" style="font-size:0.85rem;">
                  <span><i class="fas fa-calendar-alt me-1 text-info"></i> Entregado: ${row.dateCreated || '–'}</span>
                  ${row.licenciatura ? `<span><i class="fas fa-book me-1 text-primary"></i> ${row.licenciatura}</span>` : ''}
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


  // ===== Handlers delegados (una sola vez)

  // Abrir modal PARCIAL
  $(document).on("click", SELECTORS.btnVerParcial, function () {
    const rowNode = $(this).closest(".mb-3")[0];
    const rowData = rowNode ? rowNode.rowData : null;
    if(!rowData) return;
    
    const $modal = $(SELECTORS.modalParcial);

    $modal.data({
      idReporteParcial: rowData.id,
      idStudent: rowData.idStudent,
      idPractica: rowData.idPractica,
    });

    $(SELECTORS.objetivoParcial).text(rowData.objetivo);
    $(SELECTORS.actividadesParcial).text(rowData.actividades);

    $modal.modal("show");
  });

  // Abrir modal FINAL
  $(document).on("click", SELECTORS.btnVerFinal, function () {
    const rowNode = $(this).closest(".mb-3")[0];
    const rowData = rowNode ? rowNode.rowData : null;
    if(!rowData) return;
    
    const $modal = $(SELECTORS.modalFinal);

    $modal.data({
      idReporteFinal: rowData.id,
      idStudent: rowData.idStudent,
      idPractica: rowData.idPractica,
    });

    $(SELECTORS.objetivoFinal).text(rowData.objetivo);
    $(SELECTORS.actividadesFinal).text(rowData.actividades);
    $(SELECTORS.capacitacionRecibida).text(rowData.capacitacion_recibida);
    $(SELECTORS.experienciaPersonal).text(rowData.experiencia_personal);
    $(SELECTORS.experienciaProfesional).text(rowData.experiencia_profesional);
    $(SELECTORS.resultadosObtenidos).text(rowData.resultados_obtenidos);

    $modal.modal("show");
  });

  // ===== Aceptar/Reject PARCIAL
  $(document).on("click", SELECTORS.btnAceptarParcial, async function () {
    const $modal = $(SELECTORS.modalParcial);
    const { idReporteParcial, idStudent, idPractica } = $modal.data() || {};

    $modal.modal("hide");

    const $evalModal = $('#modalEvalIntegralEmpresa');
    $evalModal.find('.hito-label').text('180 Horas');
    $evalModal.find('#evalA_idStudent').val(idStudent);
    $evalModal.find('#evalA_idPractica').val(idPractica);
    $evalModal.find('#evalA_tipoHito').val('intermedia');
    $evalModal.find('#pregunta_recomienda_container').show(); // Mostrar pregunta 9

    const formEval = '#formEvalIntegralEmpresa';

    $evalModal.off("submit", formEval);
    $evalModal.on("submit", formEval, async function (e) {
      e.preventDefault();
      const form = this;
      const formData = new FormData(form);
      formData.append("action", "saveEvalIntegralEmpresa");

      try {
        const resp = await $.ajax({
          url: "controller/organismo/eval_integral.php",
          method: "POST",
          data: formData,
          processData: false,
          contentType: false,
          dataType: "json",
        });

        if (resp?.success) {
          await Swal.fire({
            icon: "success",
            title: "Evaluación enviada",
            text: "La evaluación ha sido registrada correctamente.",
            timer: 1800,
            showConfirmButton: false,
          });
          $evalModal.modal("hide");
          await ajaxJSON({ action: "acceptReport", idReporteParcial });
          reloadTable();
        } else {
          Swal.fire("Error", resp?.message || "No fue posible registrar la evaluación.", "error");
        }
      } catch (err) {
        Swal.fire("Error", "Fallo de red o servidor.", "error");
      }
    });

    setTimeout(() => $evalModal.modal("show"), 300);
  });

  $(document).on("click", SELECTORS.btnRechazarParcial, async function () {
    const $modal = $(SELECTORS.modalParcial);
    const { idReporteParcial } = $modal.data() || {};
    $modal.modal("hide");

    const result = await Swal.fire({
      title: "Rechazar reporte",
      input: "textarea",
      inputLabel: "Comentarios",
      inputPlaceholder: "Escribe los motivos del rechazo...",
      showCancelButton: true,
      confirmButtonText: "Enviar",
      cancelButtonText: "Cancelar",
      inputValidator: (v) => !v ? "Debes escribir un comentario para rechazar el reporte" : undefined,
    });

    if (!result.isConfirmed) return;

    try {
      const resp = await ajaxJSON({
        action: "rejectReport",
        idReporteParcial,
        comentarios: result.value,
      });

      if (resp?.success) {
        await Swal.fire({
          icon: "success",
          title: "Reporte rechazado",
          text: "El reporte ha sido rechazado correctamente.",
          timer: 1800,
          showConfirmButton: false,
        });
        reloadTable();
      } else {
        Swal.fire("Error", resp?.message || "No fue posible rechazar el reporte.", "error");
      }
    } catch {
      Swal.fire("Error", "Fallo de red o servidor.", "error");
    }
  });

  // ===== Aceptar/Reject FINAL
  $(document).on("click", SELECTORS.btnAceptarFinal, async function () {
    const $modal = $(SELECTORS.modalFinal);
    const { idReporteFinal, idStudent, idPractica } = $modal.data() || {};
    $modal.modal("hide");

    const $evalModal = $('#modalEvalIntegralEmpresa');
    $evalModal.find('.hito-label').text('360 Horas (Final)');
    $evalModal.find('#evalA_idStudent').val(idStudent);
    $evalModal.find('#evalA_idPractica').val(idPractica);
    $evalModal.find('#evalA_tipoHito').val('final');
    $evalModal.find('#pregunta_recomienda_container').hide(); // Ocultar pregunta 9 en la final
    // Deshabilitar required de pregunta 9
    $evalModal.find('input[name="q9"]').prop('required', false);

    const formEval = '#formEvalIntegralEmpresa';

    $evalModal.off("submit", formEval);
    $evalModal.on("submit", formEval, async function (e) {
      e.preventDefault();
      const formData = new FormData(this);
      formData.append("action", "saveEvalIntegralEmpresa");

      try {
        const resp = await $.ajax({
          url: "controller/organismo/eval_integral.php",
          method: "POST",
          data: formData,
          processData: false,
          contentType: false,
          dataType: "json",
        });

        if (resp?.success) {
          await Swal.fire({
            icon: "success",
            title: "Evaluación enviada",
            text: "La evaluación ha sido registrada correctamente.",
            timer: 1800,
            showConfirmButton: false,
          });
          $evalModal.modal("hide");
          await ajaxJSON({ action: "acceptReportFinal", idReporteFinal });
          reloadTable();
        } else {
          Swal.fire("Error", resp?.message || "No fue posible registrar la evaluación.", "error");
        }
      } catch {
        Swal.fire("Error", "Fallo de red o servidor.", "error");
      }
    });

    setTimeout(() => $evalModal.modal("show"), 300);
  });

  $(document).on("click", SELECTORS.btnRechazarFinal, async function () {
    const $modal = $(SELECTORS.modalFinal);
    const { idReporteFinal } = $modal.data() || {};
    $modal.modal("hide");

    const result = await Swal.fire({
      title: "Rechazar reporte",
      input: "textarea",
      inputLabel: "Comentarios",
      inputPlaceholder: "Escribe los motivos del rechazo...",
      showCancelButton: true,
      confirmButtonText: "Enviar",
      cancelButtonText: "Cancelar",
      inputValidator: (v) => !v ? "Debes escribir un comentario para rechazar el reporte" : undefined,
    });

    if (!result.isConfirmed) return;

    try {
      const resp = await ajaxJSON({
        action: "rejectReportFinal",
        idReporteFinal,
        comentarios: result.value,
      });

      if (resp?.success) {
        await Swal.fire({
          icon: "success",
          title: "Reporte rechazado",
          text: "El reporte ha sido rechazado correctamente.",
          timer: 1800,
          showConfirmButton: false,
        });
        reloadTable();
      } else {
        Swal.fire("Error", resp?.message || "No fue posible rechazar el reporte.", "error");
      }
    } catch {
      Swal.fire("Error", "Fallo de red o servidor.", "error");
    }
  });
})();
