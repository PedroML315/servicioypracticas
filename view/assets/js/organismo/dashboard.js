/**
 * dashboard.js — Dashboard Organismo Externo
 * Maneja: KPI cards, "Requiere tu Atención", "Postulaciones Recientes"
 */
(() => {
  "use strict";

  const ENDPOINT = "controller/organismo/forms.php";

  // ─── Helpers ────────────────────────────────────────────────────────────────
  const post = (data) =>
    $.ajax({ url: ENDPOINT, method: "POST", data, dataType: "json" });

  const notify = (title, text, icon = "success") =>
    Swal.fire({ title, text, icon, timer: 1800, showConfirmButton: false });

  const fmtFecha = (str) => {
    if (!str) return "–";
    const d = new Date(str.includes("T") ? str : str + "T00:00:00");
    if (isNaN(d)) return str;
    return d.toLocaleDateString("es-MX", {
      day: "2-digit",
      month: "short",
      year: "numeric",
    });
  };

  // ─── Renderizar Hero ─────────────────────────────────────────────────────────
  function renderHero(orgInfo) {
    if (!orgInfo) return;
    
    const isBloqueado = parseInt(orgInfo.solicitudes_bloqueadas, 10) === 1;
    const strikesCount = parseInt(orgInfo.strikes_count, 10) || 0;
    const motivoBloqueo = orgInfo.motivo_bloqueo || '';
    const name = orgInfo.nombre_contacto || orgInfo.empresa || 'Organismo Externo';

    const strikeColor = strikesCount === 0 ? '#198754' : (strikesCount === 1 ? '#ffc107' : '#dc3545');
    const strikeText = strikesCount === 0 ? 'white' : (strikesCount === 1 ? 'black' : 'white');

    let btnHtml = '';
    if (isBloqueado) {
      btnHtml = `<button class="btn-neo" style="opacity:0.6; cursor:not-allowed;" title="Solicitudes bloqueadas: ${motivoBloqueo}">
                  <i class="fas fa-lock"></i> Solicitudes Bloqueadas
                 </button>`;
    } else {
      btnHtml = `<button class="btn-neo" data-bs-toggle="modal" data-bs-target="#solicitudesModal">
                  <i class="fas fa-bolt"></i> Gestionar Vacantes
                 </button>`;
    }

    // ── Convenio validado por la institución ──
    let convenioHtml = '';
    if (orgInfo.convenio_validado && orgInfo.id) {
      const convUrl = 'controller/serve_pdf.php?file=' + orgInfo.id + '/' + encodeURIComponent(orgInfo.convenio_validado);
      convenioHtml = `<a class="btn-neo" href="${convUrl}" target="_blank"
                        style="background:rgba(255,255,255,0.2); color:#fff; box-shadow:inset 0 -4px 0 rgba(0,0,0,0.1);">
                        <i class="fas fa-file-contract"></i> Ver convenio
                      </a>`;
    } else {
      convenioHtml = `<span class="badge rounded-pill shadow-sm"
                        style="background:rgba(255,255,255,0.12); color:#fff; font-size:0.95rem; padding:0.6rem 1.2rem; border:1px dashed rgba(255,255,255,0.5);">
                        <i class="fas fa-info-circle me-1"></i> Convenio no cargado por Universidad Montrer
                      </span>`;
    }

    const html = `
      <div class="hero-blob"></div>
      <h1 class="hero-title">Hola, ${name}.</h1>
      <p class="hero-subtitle">Tu centro de comando para gestionar el talento joven.</p>
      <div class="d-flex align-items-center gap-3 flex-wrap" style="position: relative; z-index: 2;">
          ${btnHtml}
          ${convenioHtml}
          <span class="badge rounded-pill shadow-sm" style="background-color: ${strikeColor}; color: ${strikeText}; font-size: 0.95rem; padding: 0.6rem 1.2rem; border: 1px solid rgba(255,255,255,0.2);">
              <i class="fas fa-exclamation-triangle me-1"></i> ${strikesCount} Strike(s)
          </span>
      </div>
    `;
    
    $('#org-hero-container').html(html);
  }

  // ─── Cargar resumen KPI ──────────────────────────────────────────────────────
  function loadSummary() {
    post({ action: "getDashboardSummary" })
      .done((data) => {
        if (!data) return;

        // Render Hero
        renderHero(data.orgInfo);

        // Catálogo de habilidades para el selector del perfil de la vacante
        if (window.setHabilidadesCatalogo) {
          window.setHabilidadesCatalogo(data.habilidades_catalogo || []);
        }

        // Set min date for fechaLimite to tomorrow
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const yyyy = tomorrow.getFullYear();
        const mm = String(tomorrow.getMonth() + 1).padStart(2, '0');
        const dd = String(tomorrow.getDate()).padStart(2, '0');
        const dateStr = `${yyyy}-${mm}-${dd}`;
        
        $('#fechaLimite').attr('min', dateStr);
        $('#editarFechaLimite').attr('min', dateStr);

        // KPI cards
        $("#kpi-solicitudes").text(data.solicitudes_activas ?? 0);
        $("#kpi-vacantes").text(data.vacantes_disponibles ?? 0);
        $("#kpi-candidatos").text(data.candidatos_pendientes ?? 0);
        $("#kpi-asistencias").text(data.asistencias_pendientes ?? 0);
        $("#kpi-reportes").text(data.reportes_pendientes ?? 0);

        // Pulso visual en candidatos si hay pendientes
        const $kpiCand = $("#kpi-candidatos");
        if ((data.candidatos_pendientes ?? 0) > 0) {
          $kpiCand.closest(".kpi-card").addClass("kpi-urgent");
        }

        // Sección "Requiere tu Atención"
        buildAttentionSection(data);

        // Badge en la acción rápida de Mis Solicitudes
        const total =
          (data.candidatos_pendientes ?? 0) +
          (data.asistencias_pendientes ?? 0) +
          (data.reportes_pendientes ?? 0);
        if (total > 0) {
          $("#action-badge-total")
            .text(total)
            .removeClass("d-none");
        }
      })
      .fail(() => console.warn("No se pudo cargar el resumen del dashboard."));
  }

  // ─── Sección "Requiere tu Atención" ─────────────────────────────────────────
  function buildAttentionSection(data) {
    const items = [];

    if (data.candidatos_pendientes > 0) {
      items.push({
        icon: "fas fa-user-clock",
        color: "#01643D",
        bg: "rgba(1,100,61,.1)",
        label: "Candidatos pendientes de revisión",
        count: data.candidatos_pendientes,
        cta: "Ver candidatos",
        action: () => {
          $('[data-target="tab-candidatos"]').click();
          document.querySelector('.neo-tabs-nav').scrollIntoView({ behavior: "smooth" });
        },
      });
    }

    if (data.asistencias_pendientes > 0) {
      items.push({
        icon: "fas fa-calendar-check",
        color: "#0062cc",
        bg: "rgba(0,98,204,.1)",
        label: "Asistencias pendientes de aprobación",
        count: data.asistencias_pendientes,
        cta: "Ir a Asistencias",
        action: () => {
          $('[data-target="tab-asistencias"]').click();
          document.querySelector('.neo-tabs-nav').scrollIntoView({ behavior: "smooth" });
        },
      });
    }

    if (data.reportes_pendientes > 0) {
      items.push({
        icon: "fas fa-file-alt",
        color: "#856404",
        bg: "rgba(133,100,4,.1)",
        label: "Reportes pendientes de revisión",
        count: data.reportes_pendientes,
        cta: "Ir a Reportes",
        action: () => {
          $('[data-target="tab-reportes"]').click();
          document.querySelector('.neo-tabs-nav').scrollIntoView({ behavior: "smooth" });
        },
      });
    }

    const $section = $("#attention-section");
    if (items.length === 0) {
      $section.hide();
      return;
    }

    const html = items
      .map(
        (it, idx) => `
      <div class="attention-item" data-attention-idx="${idx}">
        <div class="attention-icon" style="background:${it.bg};color:${it.color};">
          <i class="${it.icon}"></i>
        </div>
        <div class="attention-body">
          <span class="attention-label">${it.label}</span>
          <span class="attention-count" style="color:${it.color};">${it.count}</span>
        </div>
        <button class="attention-cta" data-attention-idx="${idx}" style="border-color:${it.color};color:${it.color};">
          ${it.cta} <i class="fas fa-arrow-right ms-1"></i>
        </button>
      </div>`
      )
      .join("");

    $("#attention-list").html(html);
    $section.show();

    // Guardar acciones para los botones
    window._attentionActions = items.map((it) => it.action);
  }

  // Delegado para botones de atención
  $(document).on("click", "[data-attention-idx]", function () {
    const idx = parseInt($(this).data("attention-idx"), 10);
    if (window._attentionActions && window._attentionActions[idx]) {
      window._attentionActions[idx]();
    }
  });

  // ─── Cargar Postulaciones Recientes ─────────────────────────────────────────
  function loadPostulaciones() {
    const $wrap = $("#postulaciones-wrap");
    $wrap.html(
      '<div class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Cargando candidatos…</div>'
    );

    post({ action: "getRecentProspectos" })
      .done((list) => {
        if (!Array.isArray(list) || list.length === 0) {
          $wrap.html(`
            <div class="text-center py-4 text-muted">
              <i class="fas fa-user-graduate fa-2x mb-2 d-block" style="opacity:.3;"></i>
              No hay candidatos pendientes de revisión.
            </div>`);
          return;
        }

        // Agrupar por solicitud (idPractica); el título es la licenciatura
        // (vacantes legadas) o las habilidades del perfil (modelo nuevo)
        const grouped = {};
        list.forEach((p) => {
          const key = p.idPractica;
          if (!grouped[key]) {
            const skills = p.habilidades ? p.habilidades.split("|") : [];
            const perfil = p.licenciatura ||
              (skills.length ? skills.slice(0, 3).join(" · ") + (skills.length > 3 ? " …" : "") : "Vacante");
            grouped[key] = {
              idPractica: p.idPractica,
              perfil: perfil,
              actividades: p.actividades,
              modalidad: p.modalidad,
              students: [],
            };
          }
          grouped[key].students.push(p);
        });

        // FASE 6 · Badge y acciones según el estado de la postulación.
        // Los handlers (.btn-ver-prepostulacion, .btn-aceptar-prepostulacion, etc.)
        // viven en solicitudes.js con delegación a document.
        const estadoUi = (p) => {
          const ds = `data-idstudent="${p.idStudent}" data-idsolicitud="${p.idPractica}"`;
          const est = p.estado || "PREPOSTULADO";
          if (est === "ENTREVISTA_PROGRAMADA") {
            return {
              badge: `<span class="badge bg-info text-dark" style="border-radius:100px; font-size:0.75rem;">Entrevista programada</span>`,
              actions: `
                <button class="btn btn-light border btn-ver-entrevista px-3 rounded-pill" ${ds}><i class="fas fa-calendar-day me-1"></i> Ver entrevista</button>
                <button class="btn btn-primary btn-cerrar-entrevista px-4 rounded-pill shadow-sm" ${ds}><i class="fas fa-clipboard-check me-1"></i> Cerrar y evaluar</button>`,
            };
          }
          if (est === "ENTREVISTA_CERRADA") {
            return {
              badge: `<span class="badge bg-primary" style="border-radius:100px; font-size:0.75rem;">Entrevistado</span>`,
              actions: `
                <button class="btn btn-success btn-aceptar-final px-4 rounded-pill shadow-sm" ${ds}><i class="fas fa-check me-1"></i> Aceptar</button>
                <button class="btn btn-danger btn-rechazar-final px-4 rounded-pill shadow-sm" ${ds}><i class="fas fa-times me-1"></i> Rechazar</button>`,
            };
          }
          // PREPOSTULADO (o registros previos sin estado)
          return {
            badge: `<span class="badge bg-warning text-dark" style="border-radius:100px; font-size:0.75rem;">Prepostulado</span>`,
            actions: `
              <button class="btn btn-light border btn-ver-prepostulacion px-3 rounded-pill" ${ds}><i class="fas fa-file-alt me-1"></i> Ver prepostulación</button>
              <button class="btn btn-success btn-aceptar-prepostulacion px-4 rounded-pill shadow-sm" ${ds}><i class="fas fa-user-check me-1"></i> Aceptar para entrevista</button>
              <button class="btn btn-danger btn-rechazar-prepostulacion px-4 rounded-pill shadow-sm" ${ds}><i class="fas fa-times me-1"></i> Rechazar</button>`,
          };
        };

        let html = "";
        Object.values(grouped).forEach((grp) => {
          const rows = grp.students
            .map((p) => {
              const ui = estadoUi(p);
              return `
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 py-3 px-4 mb-3 bg-white border" style="border-radius: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
              <div class="d-flex align-items-center gap-3">
                <div style="width:50px;height:50px;border-radius:14px;background:#edfdf2;color:#16a34a;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0;">
                  <i class="fas fa-user-graduate"></i>
                </div>
                <div>
                  <div class="fw-bold d-flex align-items-center flex-wrap gap-2" style="font-size:1.15rem; color:#0f172a;">
                    ${p.nombre_completo}
                    ${ui.badge}
                  </div>
                  <div class="text-muted d-flex align-items-center flex-wrap gap-3 mt-1" style="font-size:0.85rem;">
                    <span><i class="fas fa-id-card me-1 text-primary"></i> ${p.matricula}</span>
                    <span><i class="fas fa-book me-1 text-info"></i> ${p.programa_academico || "–"}</span>
                    <span><i class="fas fa-calendar-alt me-1 text-success"></i> ${fmtFecha(p.fecha_postulacion)}</span>
                  </div>
                </div>
              </div>
              <div class="d-flex gap-2 align-items-center flex-wrap">
                  ${ui.actions}
              </div>
            </div>`;
            })
            .join("");

          html += `
          <div class="postulacion-group mb-4">
            <div class="postulacion-group-header d-flex align-items-center mb-3">
              <h5 class="mb-0 fw-bold" style="color:var(--brand-dark);"><i class="fas fa-briefcase me-2 text-primary"></i>${grp.perfil}</h5>
              <span class="badge bg-secondary ms-2 rounded-pill">${grp.students.length} candidato${grp.students.length > 1 ? "s" : ""}</span>
              <span class="badge border ms-2 bg-light text-dark rounded-pill">${grp.modalidad || ""}</span>
            </div>
            <div>${rows}</div>
          </div>`;
        });

        $wrap.html(html);
      })
      .fail(() => {
        $wrap.html(
          '<div class="alert alert-warning">Aún no hay candidatos.</div>'
        );
      });
  }

  // ─── Aceptar candidato desde dashboard ──────────────────────────────────────
  $(document).on("click", ".btn-aceptar-dash", async function () {
    const idStudent = $(this).data("idstudent");
    const idSolicitud = $(this).data("idsolicitud");

    const result = await Swal.fire({
      title: "Aceptar Practicante",
      html: `
        <p class="mb-3">Ingresa el motivo de aceptación y la fecha de inicio. Esta información se enviará al alumno.</p>
        <div class="mb-3 text-start">
          <label for="motivoAceptacion" class="form-label fw-bold">Motivo de aceptación <span class="text-danger">*</span></label>
          <textarea id="motivoAceptacion" class="form-control" rows="3" placeholder="Ej. Cumple con el perfil, pasó la entrevista..." minlength="10" required></textarea>
        </div>
        <div class="mb-3 text-start">
          <label for="fechaInicio" class="form-label fw-bold">Fecha de inicio <span class="text-danger">*</span></label>
          <input type="date" id="fechaInicio" class="form-control" required>
        </div>
      `,
      icon: "info",
      showCancelButton: true,
      confirmButtonText: "Confirmar Aceptación",
      cancelButtonText: "Cancelar",
      preConfirm: () => {
        const motivo = Swal.getPopup().querySelector('#motivoAceptacion').value.trim();
        const fecha = Swal.getPopup().querySelector('#fechaInicio').value;
        if (!motivo || motivo.length < 10) {
          Swal.showValidationMessage(`El motivo debe tener al menos 10 caracteres.`);
          return false;
        }
        if (!fecha) {
          Swal.showValidationMessage(`Selecciona una fecha de inicio.`);
          return false;
        }
        return { motivo: motivo, fechaInicio: fecha };
      }
    });

    if (!result.isConfirmed) return;

    try {
      const resp = await post({
        action: "aceptarProspecto",
        idStudent,
        idSolicitud,
        fechaInicio: result.value.fechaInicio,
        motivoAceptacion: result.value.motivo
      });

      if (resp?.success) {
        await notify("¡Candidato aceptado!", "", "success");
        loadPostulaciones();
        loadSummary();
        // Actualizar también el mapa de prospectos en solicitudes.js si existe
        if (typeof solicitudes === "function") solicitudes();
      } else {
        Swal.fire("Error", resp?.message || "No se pudo aceptar.", "error");
      }
    } catch (e) {
      Swal.fire("Error", "Ocurrió un problema al conectar con el servidor.", "error");
    }
  });

  // ─── Rechazar candidato desde dashboard ─────────────────────────────────────
  $(document).on("click", ".btn-rechazar-dash", async function () {
    const idStudent = $(this).data("idstudent");
    const idSolicitud = $(this).data("idsolicitud");

    const result = await Swal.fire({
      title: "Rechazar Practicante",
      html: `
        <p class="mb-3">Ingresa el motivo de rechazo. Esta información se enviará al alumno.</p>
        <div class="mb-3 text-start">
          <label for="motivoRechazo" class="form-label fw-bold">Motivo de rechazo <span class="text-danger">*</span></label>
          <textarea id="motivoRechazo" class="form-control" rows="3" placeholder="Ej. No cumple el perfil, cupo lleno..." minlength="10" required></textarea>
        </div>
      `,
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Confirmar Rechazo",
      cancelButtonText: "Cancelar",
      confirmButtonColor: "#dc3545",
      preConfirm: () => {
        const motivo = Swal.getPopup().querySelector('#motivoRechazo').value.trim();
        if (!motivo || motivo.length < 10) {
          Swal.showValidationMessage(`El motivo debe tener al menos 10 caracteres.`);
          return false;
        }
        return { motivo: motivo };
      }
    });

    if (!result.isConfirmed) return;

    try {
      const resp = await post({
        action: "rechazarProspecto",
        idStudent,
        idSolicitud,
        motivoRechazo: result.value.motivo
      });

      if (resp?.success) {
        await notify("Candidato rechazado.", "", "info");
        loadPostulaciones();
        loadSummary();
        if (typeof solicitudes === "function") solicitudes();
      } else {
        Swal.fire("Error", resp?.message || "No se pudo rechazar.", "error");
      }
    } catch (e) {
      Swal.fire("Error", "Ocurrió un problema al conectar con el servidor.", "error");
    }
  });

// ===== Motor de Tablas Custom
  class CustomTable {
    constructor(options) {
      this.containerId = options.containerId;
      this.url = options.url;
      this.dataPayload = options.dataPayload;
      this.columns = options.columns || [];
      this.cardRender = options.cardRender || null;
      this.filterFn = options.filterFn || null;
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

      // Delegated events
      this.$wrapper.on('click', '.btn-ver-comentario', function() {
        const comment = $(this).data('comentario');
        Swal.fire({
          title: "Notas de la Entrevista",
          text: comment || "No se registraron comentarios adicionales.",
          icon: "info"
        });
      });
    }

    handleSearch() {
      let temp = [...this.data];
      if (this.filterFn) {
        temp = temp.filter(this.filterFn);
      }
      const term = this.$searchInput.val().toLowerCase();
      if (term) {
        temp = temp.filter(row => JSON.stringify(row).toLowerCase().includes(term));
      }
      this.filteredData = temp;
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
        } else if (typeof renderCard === 'function') {
          // fallback
          div.innerHTML = renderCard(row, this.columns);
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

  window.solicitarCapacitacionDash = function(matricula) {
    Swal.fire({
      title: 'Solicitar Capacitación',
      html: `
        <div class="text-start">
          <label class="form-label fw-bold">Describe la capacitación requerida</label>
          <textarea id="capacitacionTexto" class="form-control" rows="4" placeholder="Ej.: Inducción a procesos, Excel intermedio, atención telefónica…"></textarea>
          <small class="text-muted d-block mt-1">Esta solicitud se enviará al administrador escolar.</small>
        </div>
      `,
      icon: 'info',
      showCancelButton: true,
      confirmButtonText: 'Enviar solicitud',
      cancelButtonText: 'Cancelar',
      preConfirm: () => {
        const text = Swal.getPopup().querySelector('#capacitacionTexto').value.trim();
        if (text.length < 5) {
          Swal.showValidationMessage('Escribe al menos 5 caracteres.');
          return false;
        }
        return text;
      }
    }).then(res => {
      if (!res.isConfirmed) return;
      $.ajax({
        type: 'POST',
        url: 'controller/organismo/forms.php',
        data: {
          action: 'solicitarCapacitacion',
          matricula: matricula,
          solicitud: res.value
        }
      }).done(() => {
        Swal.fire('Solicitud enviada', 'Se ha notificado al administrador.', 'success');
      }).fail(() => {
        Swal.fire('Error', 'No se pudo enviar la solicitud.', 'error');
      });
    });
  };

  /* ─── Reporte de incidencias de un practicante ─────────────────────────── */

  // Estilos propios del diálogo (extienden el lenguaje visual ppo-* de solicitudes.js)
  (function () {
    if (document.getElementById('ppi-styles')) return;
    const st = document.createElement('style');
    st.id = 'ppi-styles';
    st.textContent = `
      .ppo-cards.ppi-c3{grid-template-columns:repeat(3,1fr)}
      .ppi-card-sm{padding:.8rem .5rem;font-size:.85rem}
      .ppi-card-sm i{font-size:1.25rem;margin-bottom:.3rem}
      .ppo-card.ppi-danger.sel{border-color:#dc2626;background:#fef2f2;color:#b91c1c;box-shadow:0 8px 18px -10px rgba(220,38,38,.45)}
      .ppo-card.ppi-danger.sel i{color:#dc2626}
      .ppi-count{display:block;text-align:right;font-size:.72rem;font-weight:700;color:#94a3b8;margin-top:.25rem}
      .ppi-count.ok{color:#01643D}
      @media(max-width:560px){.ppo-cards.ppi-c3{grid-template-columns:1fr 1fr}}`;
    document.head.appendChild(st);
  })();

  const PPI_TIPOS = [
    { v: 'inasistencias',  ic: 'fas fa-calendar-times', t: 'Faltas o retardos',  s: 'No asiste o llega tarde' },
    { v: 'conducta',       ic: 'fas fa-comment-slash',  t: 'Conducta',           s: 'Actitud o trato inadecuado' },
    { v: 'desempeno',      ic: 'fas fa-chart-line',     t: 'Desempeño',          s: 'No cumple sus actividades' },
    { v: 'incumplimiento', ic: 'fas fa-ban',            t: 'Incumplimiento',     s: 'Rompe reglas o políticas' },
    { v: 'seguridad',      ic: 'fas fa-triangle-exclamation', t: 'Seguridad',    s: 'Riesgo o daño' },
    { v: 'otro',           ic: 'fas fa-ellipsis',       t: 'Otro',               s: 'Distinto a los anteriores' },
  ];

  const PPI_ACCIONES = [
    { v: 'orientacion', ic: 'fas fa-comments',   t: 'Orientar al alumno', s: 'La Universidad habla con él', danger: false },
    { v: 'reunion',     ic: 'fas fa-handshake',  t: 'Reunión de las 3 partes', s: 'Empresa, alumno y Universidad', danger: false },
    { v: 'baja',        ic: 'fas fa-user-minus', t: 'Solicitar su baja',  s: 'Ya no puede continuar aquí', danger: true },
  ];

  window.reportarIncidenciaDash = function (idStudent, nombre) {
    const hoy = new Date().toISOString().split('T')[0];
    const esc = (typeof escHtml === 'function') ? escHtml : (s) => String(s == null ? '' : s);

    const cardsTipo = PPI_TIPOS.map(o =>
      `<div class="ppo-card ppi-card-sm" data-value="${o.v}"><i class="${o.ic}"></i>${o.t}<small>${o.s}</small></div>`
    ).join('');

    const cardsAccion = PPI_ACCIONES.map(o =>
      `<div class="ppo-card ppi-card-sm${o.danger ? ' ppi-danger' : ''}" data-value="${o.v}"><i class="${o.ic}"></i>${o.t}<small>${o.s}</small></div>`
    ).join('');

    Swal.fire({
      html: `
        ${ppoHead('fas fa-flag', 'warn', 'Reportar incidencia', 'Practicante: ' + esc(nombre))}
        <div class="ppo-note"><i class="fas fa-envelope me-1"></i> Este reporte se envía al <strong>administrador de Prácticas Profesionales</strong> de la Universidad, quien te contactará para darle solución.</div>

        <div class="ppo-fld">
          <label class="ppo-lbl">1 · ¿Qué tipo de problema es? *</label>
          <div class="ppo-cards ppi-c3" id="ppi-tipo">${cardsTipo}</div>
        </div>

        <div class="ppo-grid2">
          <div class="ppo-fld">
            <label class="ppo-lbl">2 · ¿Cuándo ocurrió?</label>
            <input type="date" id="ppi-fecha" class="ppo-input" max="${hoy}">
          </div>
          <div class="ppo-fld">
            <label class="ppo-lbl">3 · ¿Qué tan grave es? *</label>
            <div class="ppo-seg" id="ppi-gravedad">
              <button type="button" data-value="baja">Leve</button>
              <button type="button" data-value="media" class="sel">Media</button>
              <button type="button" data-value="alta">Grave</button>
            </div>
          </div>
        </div>

        <div class="ppo-fld">
          <label class="ppo-lbl">4 · Cuéntanos qué pasó *</label>
          <textarea id="ppi-desc" class="ppo-input" rows="4" maxlength="3000"
            placeholder="Ejemplo: El 12 de marzo no se presentó y no avisó. Es la tercera vez este mes…"></textarea>
          <small class="ppi-count" id="ppi-desc-count">Mínimo 20 caracteres</small>
        </div>

        <div class="ppo-fld">
          <label class="ppo-lbl">5 · ¿Qué han hecho ustedes al respecto? <span style="text-transform:none;font-weight:600;color:#94a3b8;">(opcional)</span></label>
          <textarea id="ppi-acciones" class="ppo-input" rows="2" maxlength="2000"
            placeholder="Ejemplo: Ya se le llamó la atención dos veces por su supervisor."></textarea>
        </div>

        <div class="ppo-fld">
          <label class="ppo-lbl">6 · ¿Qué necesitas de la Universidad? *</label>
          <div class="ppo-cards ppi-c3" id="ppi-accion">${cardsAccion}</div>
          <div class="ppo-note warn mt-2" id="ppi-baja-aviso" style="display:none;">
            <i class="fas fa-triangle-exclamation me-1"></i> Estás solicitando la <strong>baja del practicante</strong>. La Universidad revisará el caso antes de autorizarla.
          </div>
        </div>`,
      showCancelButton: true,
      confirmButtonText: '<i class="fas fa-paper-plane me-1"></i> Enviar reporte',
      cancelButtonText: 'Cancelar',
      ...ppoSwalCfg(true, true),
      didOpen: () => {
        // Selección tipo tarjeta (tipo y acción solicitada)
        ['#ppi-tipo', '#ppi-accion'].forEach(sel => {
          const cards = document.querySelectorAll(sel + ' .ppo-card');
          cards.forEach(c => c.addEventListener('click', () => {
            cards.forEach(x => x.classList.remove('sel'));
            c.classList.add('sel');
            if (sel === '#ppi-accion') {
              document.getElementById('ppi-baja-aviso').style.display =
                c.dataset.value === 'baja' ? 'block' : 'none';
            }
          }));
        });

        // Selector segmentado de gravedad
        const segs = document.querySelectorAll('#ppi-gravedad button');
        segs.forEach(b => b.addEventListener('click', () => {
          segs.forEach(x => x.classList.remove('sel'));
          b.classList.add('sel');
        }));

        // Contador guía de la descripción
        const desc = document.getElementById('ppi-desc');
        const count = document.getElementById('ppi-desc-count');
        desc.addEventListener('input', () => {
          const n = desc.value.trim().length;
          count.textContent = n < 20 ? `Faltan ${20 - n} caracteres` : `${n} caracteres`;
          count.classList.toggle('ok', n >= 20);
        });
      },
      preConfirm: () => {
        const tipo = document.querySelector('#ppi-tipo .ppo-card.sel')?.dataset.value || '';
        const accion = document.querySelector('#ppi-accion .ppo-card.sel')?.dataset.value || '';
        const gravedad = document.querySelector('#ppi-gravedad button.sel')?.dataset.value || 'media';
        const descripcion = document.getElementById('ppi-desc').value.trim();
        const acciones = document.getElementById('ppi-acciones').value.trim();
        const fecha = document.getElementById('ppi-fecha').value;

        if (!tipo) { Swal.showValidationMessage('Elige el tipo de problema (paso 1).'); return false; }
        if (descripcion.length < 20) { Swal.showValidationMessage('Describe lo ocurrido con al menos 20 caracteres (paso 4).'); return false; }
        if (!accion) { Swal.showValidationMessage('Indica qué necesitas de la Universidad (paso 6).'); return false; }

        return { tipo, gravedad, fecha_incidente: fecha, descripcion, acciones_tomadas: acciones, accion_solicitada: accion };
      }
    }).then(res => {
      if (!res.isConfirmed) return;
      $.ajax({
        method: 'POST',
        url: ENDPOINT,
        data: { action: 'reportarIncidencia', idStudent, ...res.value },
        dataType: 'json'
      }).done(r => {
        if (r && r.success) {
          Swal.fire('Reporte enviado', r.message || 'El administrador lo revisará y se pondrá en contacto contigo.', 'success');
        } else {
          Swal.fire('Error', (r && r.message) || 'No se pudo enviar el reporte.', 'error');
        }
      }).fail(() => {
        Swal.fire('Error', 'No se pudo enviar el reporte.', 'error');
      });
    });
  };

  $(document).on('click', '.btn-reportar-incidencia', function () {
    window.reportarIncidenciaDash($(this).data('idstudent'), $(this).data('nombre'));
  });

  function loadHistorialAlumnos() {
    const $filters = $("#practicantesFilters");
    if ($filters.is(':empty')) {
      const btnAll = $('<button class="btn btn-sm btn-dark text-white fw-semibold px-4 py-1 rounded-pill shadow-sm" style="border: 1px solid transparent;">Todos</button>');
      const btnAceptados = $('<button class="btn btn-sm btn-light text-success fw-semibold px-4 py-1 rounded-pill shadow-sm" style="border: 1px solid #e2e8f0; background: #fff;">Aceptados</button>');
      const btnRechazados = $('<button class="btn btn-sm btn-light text-danger fw-semibold px-4 py-1 rounded-pill shadow-sm" style="border: 1px solid #e2e8f0; background: #fff;">Rechazados</button>');
      
      $filters.append(btnAll, btnAceptados, btnRechazados);
      
      $filters.on('click', 'button', function() {
         $filters.find('button').each(function() {
             const t = $(this).text();
             $(this).removeClass('btn-dark btn-success btn-danger text-white')
                    .addClass('btn-light')
                    .css({ border: '1px solid #e2e8f0', background: '#fff' });
                    
             if (t === 'Todos') $(this).addClass('text-dark').removeClass('text-success text-danger');
             if (t === 'Aceptados') $(this).addClass('text-success').removeClass('text-dark text-danger');
             if (t === 'Rechazados') $(this).addClass('text-danger').removeClass('text-dark text-success');
         });
         
         const text = $(this).text();
         $(this).removeClass('btn-light text-dark text-success text-danger')
                .css({ border: '1px solid transparent', background: '' });
         
         if (text === 'Aceptados') {
             $(this).addClass('btn-success text-white');
             if(window.customHistorialTable) window.customHistorialTable.filterFn = row => row.isAcepted == 1;
         } else if (text === 'Rechazados') {
             $(this).addClass('btn-danger text-white');
             if(window.customHistorialTable) window.customHistorialTable.filterFn = row => row.isAcepted == 2;
         } else {
             $(this).addClass('btn-dark text-white');
             if(window.customHistorialTable) window.customHistorialTable.filterFn = null;
         }
         
         if(window.customHistorialTable) {
             window.customHistorialTable.currentPage = 1;
             window.customHistorialTable.handleSearch();
         }
      });
    }

    if (!window.customHistorialTable) {
      window.customHistorialTable = new CustomTable({
        containerId: '#historialAlumnosTable',
        url: ENDPOINT,
        dataPayload: { action: "getHistorialAlumnos" },
        cardRender: function(row) {
          // FASE 6 · Badge según el estado de la postulación (isAcepted queda sincronizado)
          const est = row.estado || null;
          let statusBadge = '';
          if(row.isAcepted == 1) statusBadge = '<span class="badge bg-success" style="border-radius:100px;">Aceptado</span>';
          else if(est === 'RECHAZADO_PREPOSTULACION') statusBadge = '<span class="badge bg-danger" style="border-radius:100px;">Rechazado en prepostulación</span>';
          else if(row.isAcepted == 2) statusBadge = '<span class="badge bg-danger" style="border-radius:100px;">Rechazado</span>';
          else if(est === 'ENTREVISTA_PROGRAMADA') statusBadge = '<span class="badge bg-info text-dark" style="border-radius:100px;">Entrevista programada</span>';
          else if(est === 'ENTREVISTA_CERRADA') statusBadge = '<span class="badge bg-primary" style="border-radius:100px;">Entrevistado</span>';
          else if(est === 'PREPOSTULADO') statusBadge = '<span class="badge bg-warning text-dark" style="border-radius:100px;">Prepostulado</span>';
          else statusBadge = '<span class="badge bg-secondary" style="border-radius:100px;">En proceso</span>';

          let actions = '';
          if (row.calificacion_respuestas) {
            let stars = '';
            for(let i=0; i<5; i++) stars += `<i class="fa${i < row.calificacion_respuestas ? 's' : 'r'} fa-star text-warning" style="font-size:.85rem;"></i>`;
            actions = `
              <div class="d-flex flex-column align-items-end gap-1">
                <div>${stars}</div>
                <div class="text-muted" style="font-size:.75rem;">${row.llego_a_tiempo == 1 ? 'Puntual' : 'Impuntual'} | ${row.llego_formal == 1 ? 'Formal' : 'Informal'}</div>
                <button class="btn btn-sm btn-light p-1 mt-1 btn-ver-comentario rounded" data-comentario="${row.comentarios || ''}" style="font-size:.75rem;">Ver Notas</button>
              </div>
            `;
          } else if (est === 'ENTREVISTA_PROGRAMADA') {
            actions = `<button class="btn btn-primary btn-cerrar-entrevista px-4 rounded-pill shadow-sm" data-idstudent="${row.idStudent}" data-idsolicitud="${row.idPractica}"><i class="fas fa-clipboard-check me-1"></i> Cerrar y evaluar</button>`;
          } else if (est === 'PREPOSTULADO') {
            actions = `<button class="btn btn-light border btn-ver-prepostulacion px-3 rounded-pill" data-idstudent="${row.idStudent}" data-idsolicitud="${row.idPractica}"><i class="fas fa-file-alt me-1"></i> Ver prepostulación</button>`;
          }

          if (row.isAcepted == 1 && row.status_carta !== 'concluida' && row.status_carta !== 'cancelada') {
            const nombreAttr = (typeof escHtml === 'function' ? escHtml(row.nombre_completo) : row.nombre_completo || '');
            actions += `
              <div class="d-flex flex-column gap-2">
                <button class="btn btn-outline-primary px-3 rounded-pill shadow-sm" style="font-size: 0.85rem;"
                        onclick="solicitarCapacitacionDash('${row.matricula}')">
                  <i class="fas fa-chalkboard-teacher me-1"></i> Solicitar Capacitación
                </button>
                <button class="btn btn-outline-danger px-3 rounded-pill shadow-sm btn-reportar-incidencia" style="font-size: 0.85rem;"
                        data-idstudent="${row.idStudent}" data-nombre="${nombreAttr}">
                  <i class="fas fa-flag me-1"></i> Reportar Incidencia
                </button>
              </div>
            `;
          }

          return `
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 py-3 px-4 bg-white border" style="border-radius: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
              <div class="d-flex align-items-center gap-3">
                <div style="width:50px;height:50px;border-radius:14px;background:#eef2ff;color:#6366f1;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0;">
                  <i class="fas fa-user"></i>
                </div>
                <div>
                  <div class="fw-bold d-flex align-items-center flex-wrap gap-2" style="font-size:1.15rem; color:#0f172a;">
                    ${row.nombre_completo}
                    ${statusBadge}
                  </div>
                  <div class="text-muted d-flex align-items-center flex-wrap gap-3 mt-1" style="font-size:0.85rem;">
                    <span><i class="fas fa-envelope me-1 text-primary"></i> ${row.email || '–'}</span>
                    <span><i class="fas fa-phone me-1 text-success"></i> ${row.telefono || '–'}</span>
                    <span><i class="fas fa-calendar-alt me-1 text-info"></i> ${fmtFecha(row.dateCreated)}</span>
                  </div>
                  <div class="text-secondary mt-2" style="font-size:0.9rem;">
                    <strong>Actividad:</strong> ${row.actividades || '–'}
                  </div>
                </div>
              </div>
              <div class="d-flex gap-2 align-items-center flex-wrap justify-content-end">
                ${actions}
              </div>
            </div>
          `;
        }
      });
    } else {
      window.customHistorialTable.reload();
    }
  }

  // ─── Init ────────────────────────────────────────────────────────────────────
  $(function () {
    loadSummary();
    loadPostulaciones();
    loadHistorialAlumnos();
  });

  // Exponer para uso externo si es necesario
  window.orgDashboard = { loadSummary, loadPostulaciones, loadHistorialAlumnos };
})();
