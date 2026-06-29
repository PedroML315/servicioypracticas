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

    const html = `
      <div class="hero-blob"></div>
      <h1 class="hero-title">Hola, ${name}.</h1>
      <p class="hero-subtitle">Tu centro de comando para gestionar el talento joven.</p>
      <div class="d-flex align-items-center gap-3" style="position: relative; z-index: 2;">
          ${btnHtml}
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

        // Populate selects
        if (data.degrees && Array.isArray(data.degrees)) {
          const $licenciatura = $('#licenciatura');
          const $editarLicenciatura = $('#editarLicenciatura');
          
          let optionsHtml = '<option value="">Selecciona una opción</option>';
          data.degrees.forEach(degree => {
            if (degree.minPoints == '480') {
              optionsHtml += `<option value="${degree.nameDegree}">${degree.nameDegree}</option>`;
            }
          });
          
          $licenciatura.html(optionsHtml);
          $editarLicenciatura.html(optionsHtml);
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

        // Agrupar por solicitud (idPractica + licenciatura)
        const grouped = {};
        list.forEach((p) => {
          const key = p.idPractica;
          if (!grouped[key]) {
            grouped[key] = {
              idPractica: p.idPractica,
              licenciatura: p.licenciatura,
              actividades: p.actividades,
              modalidad: p.modalidad,
              students: [],
            };
          }
          grouped[key].students.push(p);
        });

        let html = "";
        Object.values(grouped).forEach((grp) => {
          const rows = grp.students
            .map(
              (p) => `
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 py-3 px-4 mb-3 bg-white border" style="border-radius: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
              <div class="d-flex align-items-center gap-3">
                <div style="width:50px;height:50px;border-radius:14px;background:#edfdf2;color:#16a34a;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0;">
                  <i class="fas fa-user-graduate"></i>
                </div>
                <div>
                  <div class="fw-bold d-flex align-items-center flex-wrap gap-2" style="font-size:1.15rem; color:#0f172a;">
                    ${p.nombre_completo}
                    ${p.status_carta === 'vigente' ? `<span class="badge bg-warning text-dark" style="border-radius:100px; font-size:0.75rem;">Pendiente Entrevista</span>` : (p.status_carta === 'presentada' || !p.status_carta ? `<span class="badge bg-primary" style="border-radius:100px; font-size:0.75rem;">En Revisión (Entrevistado)</span>` : '')}
                  </div>
                  <div class="text-muted d-flex align-items-center flex-wrap gap-3 mt-1" style="font-size:0.85rem;">
                    <span><i class="fas fa-id-card me-1 text-primary"></i> ${p.matricula}</span>
                    <span><i class="fas fa-book me-1 text-info"></i> ${p.programa_academico || "–"}</span>
                    <span><i class="fas fa-calendar-alt me-1 text-success"></i> ${fmtFecha(p.fecha_postulacion)}</span>
                  </div>
                </div>
              </div>
              <div class="d-flex gap-2 align-items-center">
                  ${p.status_carta === 'vigente' ? `
                    <button class="btn btn-info btn-evaluar-entrevista px-4 text-white rounded-pill shadow-sm"
                      data-idstudent="${p.idStudent}" data-idsolicitud="${p.idPractica}">
                      <i class="fas fa-clipboard-check me-1"></i> Evaluar
                    </button>
                  ` : (p.status_carta === 'presentada' || !p.status_carta ? `
                    <button class="btn btn-success btn-aceptar-dash px-4 rounded-pill shadow-sm"
                      data-idstudent="${p.idStudent}" data-idsolicitud="${p.idPractica}">
                      <i class="fas fa-check me-1"></i> Aceptar
                    </button>
                    <button class="btn btn-danger btn-rechazar-dash px-4 rounded-pill shadow-sm"
                      data-idstudent="${p.idStudent}" data-idsolicitud="${p.idPractica}">
                      <i class="fas fa-times me-1"></i> Rechazar
                    </button>
                  ` : '')}
              </div>
            </div>`
            )
            .join("");

          html += `
          <div class="postulacion-group mb-4">
            <div class="postulacion-group-header d-flex align-items-center mb-3">
              <h5 class="mb-0 fw-bold" style="color:var(--brand-dark);"><i class="fas fa-briefcase me-2 text-primary"></i>${grp.licenciatura}</h5>
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
          let statusBadge = '';
          if(row.isAcepted == 1) statusBadge = '<span class="badge bg-success" style="border-radius:100px;">Aceptado</span>';
          else if(row.isAcepted == 2) statusBadge = '<span class="badge bg-danger" style="border-radius:100px;">Rechazado</span>';
          else if(row.status_carta === 'presentada') statusBadge = '<span class="badge bg-primary" style="border-radius:100px;">En Revisión</span>';
          else if(row.status_carta === 'vigente') statusBadge = '<span class="badge bg-warning text-dark" style="border-radius:100px;">Pendiente Entrevista</span>';
          else if(row.status_carta === 'expirada') statusBadge = '<span class="badge bg-danger" style="border-radius:100px;">Expirada</span>';
          else statusBadge = '<span class="badge bg-secondary" style="border-radius:100px;">Desconocido</span>';

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
          } else if (row.status_carta === 'vigente') {
            actions = `<button class="btn btn-info btn-evaluar-entrevista text-white px-4 rounded-pill shadow-sm" data-idstudent="${row.idStudent}" data-idsolicitud="${row.idPractica}"><i class="fas fa-clipboard-check me-1"></i> Evaluar</button>`;
          }

          if (row.isAcepted == 1 && row.status_carta !== 'concluida' && row.status_carta !== 'cancelada') {
            actions += `
              <button class="btn btn-outline-primary px-3 rounded-pill shadow-sm mt-2" style="font-size: 0.85rem;"
                      onclick="solicitarCapacitacionDash('${row.matricula}')">
                <i class="fas fa-chalkboard-teacher me-1"></i> Solicitar Capacitación
              </button>
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
              <div class="d-flex gap-2 align-items-center">
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
