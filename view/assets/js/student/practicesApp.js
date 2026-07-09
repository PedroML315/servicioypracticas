import { CONFIG } from "./config.js";
import { Utils } from "./utils.js?v=20260706";
import AppState from "./appState.js";

var assistances;
export default class PracticesApp {
  constructor() {
    this.state = new AppState();
    this.init();
  }

  /* ---------- Init ---------- */
  init() {
    this.bindEvents();
    this.loadSolicitudes();
  }

  bindEvents() {
    $(document)
      .on("click", CONFIG.SELECTORS.APPLY_BTN, (e) => this.handleApplyClick(e))
      .on("click", CONFIG.SELECTORS.GENERATE_LETTER_BTN, (e) =>
        this.handleGenerateLetterClick(e)
      )
      .on("click", CONFIG.SELECTORS.NEW_ATTENDANCE_BTN, (e) =>
        this.handleNewAttendanceClick(e)
      )
      .on("click", ".btn-ver-todas-asistencias", (e) =>
        this.handleVerTodasClick(e)
      );

    $(CONFIG.SELECTORS.SEARCH).on(
      "input",
      Utils.debounce((e) => this.handleSearch(e), 300)
    );
  }

  handleVerTodasClick(e) {
    // Ordenar asistencias por fecha descendente (más reciente primero)
    const sortedAssistances = Array.isArray(assistances)
      ? [...assistances].sort((a, b) => b.fecha.localeCompare(a.fecha))
      : [];

    if (!sortedAssistances.length) {
      Swal.fire({
        title: "Asistencias",
        html: "<div class='text-center'>No hay asistencias registradas.</div>",
        icon: "info",
      });
      return;
    }

    // Generar el HTML de la lista
    const listHTML = sortedAssistances
      .map((attendance) => {
        const workingHours = Utils.calculateWorkingHours(
          attendance.hora_entrada,
          attendance.hora_salida
        );
        const formattedHours = Utils.formatWorkingHours(workingHours);
        const formattedDate = Utils.formatDateSpanish(attendance.fecha);

        return `
          <!-- Markup de cada asistencia -->
          <div class="card attendance-card mb-3 shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-start">
              <!-- Izquierda: fecha y detalles -->
              <div class="flex-grow-1 pe-3">
                <h6 class="text-primary mb-2">
                  <i class="fas fa-calendar-day me-2"></i>${formattedDate}
                </h6>

                <div class="d-flex flex-wrap text-muted small mb-2">
                  <div class="me-4 d-flex align-items-center">
                    <i class="fas fa-sign-in-alt me-1"></i>
                    <span><strong>Entrada:</strong> ${attendance.hora_entrada || "–"
          }</span>
                  </div>
                  <div class="d-flex align-items-center">
                    <i class="fas fa-sign-out-alt me-1"></i>
                    <span><strong>Salida:</strong> ${attendance.hora_salida || "–"
          }</span>
                  </div>
                </div>

                <p class="mb-0 small text-secondary">
                  <i class="fas fa-tasks me-1"></i>
                  <strong>Actividad:</strong> ${attendance.actividad}
                </p>
              </div>

              <!-- Derecha: duración -->
              <div class="text-end">
                <span class="badge bg-success rounded-pill attendance-time-badge py-2 px-3">
                  <i class="fas fa-clock me-1"></i>${formattedHours}
                </span>
              </div>
            </div>
          </div>
        `;
      })
      .join("");

    Swal.fire({
      title: "Todas las asistencias",
      html: `<div style="max-height:400px;overflow-y:auto;">${listHTML}</div>`,
      width: 600,
      showCloseButton: true,
      confirmButtonText: "Cerrar",
    });
  }

  /* ---------- Buscador ---------- */
  handleSearch(e) {
    const term = e.target.value.trim().toLowerCase();
    if (!term) {
      this.state.setFilteredData([...this.state.getSolicitudes()]);
    } else {
      const filtered = this.state.getSolicitudes().filter((item) => {
        const fields = [
          item.empresa,
          item.actividades,
          item.capacidades,
          item.ciudad,
          item.licenciatura,
          item.habilidades,
        ];
        return fields.some((f) => f && f.toLowerCase().includes(term));
      });
      this.state.setFilteredData(filtered);
    }
    this.renderSolicitudes();
  }

  /* ---------- Carga AJAX ---------- */
  async loadSolicitudes() {
    if (this.state.isLoading) return;
    this.state.isLoading = true;
    try {
      const response = await this.ajax({
        method: "POST",
        url: CONFIG.ENDPOINTS.STUDENTS,
        data: { action: "start" },
      });
      if (response?.studentInfo) {
        this.renderStudentHero(response.studentInfo);
      }

      if (response?.type === "areas") {
        $(CONFIG.SELECTORS.SEARCHBAR).hide();
        this.renderAreasDashboard(response);
      } else if (response?.type === "practices") {
        assistances = response.asistencias || [];
        let practicesData = response.practices || [];
        this.renderPracticesDashboard(response);
        this.renderDataPractices(practicesData);
        $(CONFIG.SELECTORS.SEARCHBAR).hide();
      } else if (response?.type === "solicitudes") {
        this.state.setSolicitudes(response.practices || []);
        $(CONFIG.SELECTORS.SEARCHBAR).show();
        this.renderSolicitudes();
      } else {
        this.state.setSolicitudes(response);
        this.renderSolicitudes();
      }
    } catch (err) {
      console.error(err);
      Utils.showMessage(
        $(CONFIG.SELECTORS.SOLICITUDES),
        CONFIG.MESSAGES.LOAD_ERROR,
        "danger"
      );
    } finally {
      this.state.isLoading = false;
    }
  }

  ajax(opts) {
    return new Promise((res, rej) => {
      $.ajax({ ...opts, dataType: "json", success: res, error: rej });
    });
  }

  /* ---------- Render Hero ---------- */
  renderStudentHero(info) {
    if (!info) return;

    let alertsHtml = '';
    if (info.isBajaStrike) {
      alertsHtml = `
        <div class="alert alert-danger shadow-sm d-flex align-items-center mb-4" role="alert"
            style="border-radius: .85rem; border-left: 5px solid #dc3545;">
            <i class="fas fa-ban fs-4 me-3"></i>
            <div>
                <strong>Baja del programa:</strong> Has sido dado de baja de tus prácticas profesionales por incumplimiento reiterado de horario (2 strikes).
            </div>
        </div>`;
    } else if (info.countStrikes > 0) {
      alertsHtml = `
        <div class="alert alert-warning shadow-sm d-flex align-items-center mb-4" role="alert"
            style="border-radius: .85rem; border-left: 5px solid #ffc107;">
            <i class="fas fa-exclamation-triangle fs-4 me-3" style="color: #d39e00;"></i>
            <div>
                <strong>Advertencia (1er Strike):</strong> Tienes 1 incumplimiento de horario registrado. Un segundo incumplimiento resultará en la baja automática de tus prácticas.
            </div>
        </div>`;
    }

    const heroHtml = `
      <input type="hidden" id="programa_academico" value="${info.programa_academico || ''}">
      <div class="bento-card d-flex flex-wrap align-items-center gap-4 p-4" style="background: linear-gradient(135deg, #01643D, #00204a); color: white; border: none; margin-bottom: 2rem;">
          <div class="hero-blob"></div>
          <div style="width: 70px; height: 70px; border-radius: 50%; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); display: flex; align-items: center; justify-content: center; font-size: 1.8rem; font-weight: 800; position: relative; z-index: 2;">
            ${info.initials || 'PP'}
          </div>
          <div class="flex-grow-1" style="position: relative; z-index: 2;">
              <h2 style="font-size: 1.8rem; font-weight: 900; margin: 0 0 0.2rem;">${info.nombre_completo || 'Alumno'}</h2>
              <p style="margin: 0; opacity: 0.9; font-size: 1rem;"><i class="fas fa-graduation-cap me-2"></i>${info.programa_academico || 'Alumno de Prácticas Profesionales'}</p>
          </div>
          <span style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); border-radius: 100px; padding: 0.5rem 1rem; font-size: 0.85rem; font-weight: 700; white-space: nowrap; position: relative; z-index: 2;">
            <i class="fas fa-briefcase me-2"></i>Prácticas Profesionales
          </span>
      </div>
      ${alertsHtml}
    `;

    $("#student-hero-container").html(heroHtml);
  }

  /* ---------- Render Solicitudes ---------- */
  renderSolicitudes() {
    const $c = $(CONFIG.SELECTORS.SOLICITUDES);
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    // Filtro base (vacante publicada, vigente y con cupo disponible)
    const isPostulable = (i) => {
      const okAcept = i.aceptado == 1;
      const okDate = this.isFuture(i.fecha_limite, today);
      const okVac = i.num_practicantes - i.num_students > 0;
      return okAcept && okDate && okVac;
    };

    // Lista única: el perfil de cada vacante se describe por habilidades
    // (las legadas muestran su licenciatura como insignia)
    const allList = this.state.getFilteredData().filter(isPostulable);

    // Índice por id para el modal de detalle
    this._vacantesById = {};
    allList.forEach((i) => (this._vacantesById[i.id] = i));

    if (!allList.length) {
      $c.html(`
        <div class="pp-empty-state">
          <div class="pp-empty-icon"><i class="fas fa-compass"></i></div>
          <h4>No hay vacantes disponibles</h4>
          <p>Por ahora no encontramos oportunidades que coincidan con tu búsqueda. Vuelve a intentarlo más tarde.</p>
        </div>`);
      return;
    }

    const cardsHtml = allList.map((i) => this.card(i)).join("");
    const plural = allList.length === 1 ? "vacante disponible" : "vacantes disponibles";

    $c.html(`
      <div class="pp-vacantes-head">
        <div>
          <h3 class="pp-vacantes-title">Oportunidades de prácticas</h3>
          <p class="pp-vacantes-sub">Explora las vacantes y postúlate a las que mejor se ajusten a tu perfil.</p>
        </div>
        <span class="pp-vacantes-count"><i class="fas fa-briefcase me-2"></i>${allList.length} ${plural}</span>
      </div>
      <div class="row gx-3 gy-4 pp-vacantes-grid">${cardsHtml}</div>
    `);

    this.ensureDetalleModal();
  }

  /* ---------- Chips de habilidades ---------- */
  skillList(item) {
    return item.habilidades ? item.habilidades.split("|").filter(Boolean) : [];
  }

  isFuture(dateStr, today) {
    if (!dateStr) return false;
    const [y, m, d] = dateStr.split("-");
    const lim = new Date(y, m - 1, d);
    lim.setHours(0, 0, 0, 0);
    return lim >= today;
  }

  card(item) {
    const btn = this.buttonHTML(item);
    const disponibles = Math.max(0, (item.num_practicantes || 0) - (item.num_students || 0));
    const initials = (item.empresa || "PP").trim().substring(0, 2).toUpperCase();

    // Chips de habilidades (máx 4 visibles + contador)
    const skills = this.skillList(item);
    const MAX = 4;
    let skillsHtml = "";
    if (skills.length) {
      const shown = skills.slice(0, MAX)
        .map((s) => `<span class="pp-skill-chip">${Utils.escape(s)}</span>`)
        .join("");
      const extra = skills.length > MAX
        ? `<span class="pp-skill-chip pp-skill-more">+${skills.length - MAX}</span>`
        : "";
      skillsHtml = `<div class="pp-skill-wrap">${shown}${extra}</div>`;
    } else if (item.licenciatura) {
      skillsHtml = `<div class="pp-skill-wrap"><span class="pp-skill-chip"><i class="fas fa-graduation-cap me-1"></i>${item.licenciatura}</span></div>`;
    }

    const apoyo = item.ofrece_apoyo_economico == 1
      ? `<span class="pp-stat pp-stat-apoyo"><i class="fas fa-hand-holding-usd"></i> Con apoyo</span>`
      : `<span class="pp-stat"><i class="fas fa-hand-holding-usd"></i> Sin apoyo</span>`;

    return `<div class="col-md-6 col-xl-4">
      <div class="pp-vac-card">
        <!-- Encabezado -->
        <div class="pp-vac-head">
          <div class="pp-vac-avatar">${initials}</div>
          <div class="pp-vac-headtext">
            <h5 class="pp-vac-empresa" title="${Utils.escape(item.empresa || '')}">${Utils.escape(item.empresa || 'Organismo')}</h5>
            <span class="pp-vac-giro">${Utils.escape(item.giro || 'Prácticas profesionales')}</span>
          </div>
          <span class="pp-vac-ciudad"><i class="fas fa-map-marker-alt"></i>${Utils.escape(item.ciudad || '—')}</span>
        </div>

        <!-- Habilidades requeridas -->
        <div class="pp-vac-skills">
          <span class="pp-vac-label"><i class="fas fa-bolt me-1"></i>Habilidades requeridas</span>
          ${skillsHtml || '<span class="pp-skill-empty">No especificadas</span>'}
        </div>

        <!-- Datos rápidos -->
        <div class="pp-vac-stats">
          <span class="pp-stat pp-stat-vac"><i class="fas fa-user-friends"></i> ${disponibles} ${disponibles === 1 ? 'lugar' : 'lugares'}</span>
          ${apoyo}
          <span class="pp-stat"><i class="fas fa-laptop-house"></i> ${item.modalidad || '—'}</span>
          <span class="pp-stat"><i class="far fa-calendar-alt"></i> Hasta ${item.fecha_limite || '—'}</span>
        </div>

        <!-- Acciones -->
        <div class="pp-vac-actions">
          <button class="pp-btn-detalle" data-detalle-id="${item.id}"><i class="fas fa-eye me-1"></i>Ver detalles</button>
          <div class="pp-vac-cta">${btn}</div>
        </div>
      </div>
    </div>`;
  }

  /* ---------- Modal de detalle de vacante ---------- */
  ensureDetalleModal() {
    if (document.getElementById("ppDetalleModal")) return;
    const modal = `
      <div class="modal fade" id="ppDetalleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
          <div class="modal-content pp-detalle-content">
            <div class="pp-detalle-header">
              <div id="ppDetalleHeadInfo"></div>
              <button type="button" class="pp-detalle-close" data-bs-dismiss="modal" aria-label="Cerrar"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body pp-detalle-body" id="ppDetalleBody"></div>
            <div class="pp-detalle-footer" id="ppDetalleFooter"></div>
          </div>
        </div>
      </div>`;
    $("body").append(modal);
    $(document).on("click", "[data-detalle-id]", (e) => {
      this.showDetalle($(e.currentTarget).data("detalle-id"));
    });
  }

  detalleField(icon, label, value) {
    if (!value) return "";
    return `
      <div class="pp-det-field">
        <div class="pp-det-field-label"><i class="${icon}"></i>${label}</div>
        <div class="pp-det-field-value">${Utils.escape(value).replace(/\r?\n/g, "<br>")}</div>
      </div>`;
  }

  showDetalle(id) {
    const item = (this._vacantesById || {})[id];
    if (!item) return;

    const disponibles = Math.max(0, (item.num_practicantes || 0) - (item.num_students || 0));
    const skills = this.skillList(item);
    const schedule = `${item.dia_inicio || ''} a ${item.dia_fin || ''}, ${item.hora_inicio?.slice(0, 5) || ''}–${item.hora_fin?.slice(0, 5) || ''}`;

    const skillsHtml = skills.length
      ? `<div class="pp-det-skills">${skills.map((s) => `<span class="pp-skill-chip">${Utils.escape(s)}</span>`).join("")}</div>`
      : (item.licenciatura
        ? `<span class="pp-skill-chip"><i class="fas fa-graduation-cap me-1"></i>${Utils.escape(item.licenciatura)}</span>`
        : '<span class="text-muted">No especificadas</span>');

    $("#ppDetalleHeadInfo").html(`
      <div class="pp-detalle-avatar">${(item.empresa || 'PP').substring(0, 2).toUpperCase()}</div>
      <div>
        <h5 class="pp-detalle-empresa">${Utils.escape(item.empresa || 'Organismo')}</h5>
        <span class="pp-detalle-meta"><i class="fas fa-map-marker-alt me-1"></i>${Utils.escape(item.ciudad || '—')} · ${Utils.escape(item.giro || 'Prácticas')}</span>
      </div>
    `);

    const chips = `
      <div class="pp-det-quickrow">
        <span class="pp-stat pp-stat-vac"><i class="fas fa-user-friends"></i> ${disponibles} ${disponibles === 1 ? 'lugar' : 'lugares'}</span>
        <span class="pp-stat ${item.ofrece_apoyo_economico == 1 ? 'pp-stat-apoyo' : ''}"><i class="fas fa-hand-holding-usd"></i> ${item.ofrece_apoyo_economico == 1 ? 'Con apoyo — ' + item.monto_apoyo : 'Sin apoyo'}</span>
        <span class="pp-stat"><i class="fas fa-laptop-house"></i> ${item.modalidad || '—'}</span>
        <span class="pp-stat"><i class="far fa-calendar-alt"></i> Hasta ${item.fecha_limite || '—'}</span>
      </div>`;

    const body = `
      ${chips}
      <div class="pp-det-block">
        <div class="pp-det-field-label"><i class="fas fa-bolt"></i>Habilidades requeridas</div>
        ${skillsHtml}
      </div>
      ${this.detalleField("fas fa-clipboard-list", "Actividades", item.actividades)}
      ${this.detalleField("fas fa-tasks", "Funciones", item.funciones)}
      ${this.detalleField("fas fa-bullseye", "Objetivos", item.objetivos)}
      ${this.detalleField("fas fa-medal", "Competencias a desarrollar", item.competencias)}
      ${this.detalleField("fas fa-flag-checkered", "Resultados esperados", item.resultados_esperados)}
      ${this.detalleField("fas fa-star", "Habilidades deseadas (adicionales)", item.capacidades)}
      <div class="pp-det-grid">
        ${this.detalleField("far fa-clock", "Horario", schedule)}
        ${this.detalleField("fas fa-map-marker-alt", "Sede", item.direccion_practica)}
        ${this.detalleField("fas fa-user-tie", "Responsable", (item.nombre_responsable || '') + (item.telefono ? ' · ' + item.telefono : ''))}
        ${this.detalleField("far fa-calendar-plus", "Publicada", Utils.formatDate(item.created_at))}
      </div>`;

    $("#ppDetalleBody").html(body);

    // El botón de acción reutiliza el mismo estado (postularse, carta, etc.)
    $("#ppDetalleFooter").html(`<div class="pp-detalle-cta">${this.buttonHTML(item)}</div>`);

    $("#ppDetalleModal").modal("show");
  }

  /* ---------- Botones ---------- */
  buttonHTML(i) {
    const base = {
      class: "btn btn-sm fw-semibold d-flex align-items-center justify-content-center gap-2",
      style: "width: 100%; border-radius: 8px; transition: all 0.2s;",
    };

    const numStudents = parseInt(i.num_students) || 0;
    const numPracticantes = parseInt(i.num_practicantes) || 0;
    const isCupoLleno = numStudents >= numPracticantes;

    if (i.pending > 0) {
      if (i.status_carta === 'presentada') {
        return this.btn({
          ...base,
          class: `${base.class} btn-primary text-white`,
          icon: "fas fa-user-clock",
          text: "En Entrevista",
          title: "Tu carta ha sido presentada y la empresa está evaluando",
          disabled: true,
        });
      }
      if (i.status_carta === 'vigente' && i.fecha_vencimiento) {
        const diffMs = new Date(i.fecha_vencimiento) - new Date();
        const diffHours = diffMs / (1000 * 60 * 60);
        if (diffHours < 0) {
          return this.btn({
            ...base,
            class: `${base.class} btn-danger`,
            icon: "fas fa-exclamation-circle",
            text: "Expirada",
            title: "Tu carta de presentación ha expirado",
            disabled: true,
          });
        } else if (diffHours <= 48) {
          return this.btn({
            ...base,
            class: `${base.class} btn-warning text-dark generate-letter`,
            icon: "fas fa-clock",
            text: `Vence pronto (${Math.ceil(diffHours)}h)`,
            title: "Tu carta está a punto de expirar. ¡Preséntate pronto!",
            data: this.dataAttrs(i),
          });
        } else {
          return this.btn({
            ...base,
            class: `${base.class} btn-info generate-letter`,
            icon: "fas fa-file-pdf",
            text: "Descargar Carta",
            title: `Vigente hasta el ${i.fecha_vencimiento.split(' ')[0]}`,
            data: this.dataAttrs(i),
          });
        }
      }
      if (i.status_carta === 'generada') {
        return this.btn({
          ...base,
          class: `${base.class} btn-info text-white btn-print-letter`,
          icon: "fas fa-print",
          text: "Imprimir Carta",
          title: "Imprimir carta de presentación",
          data: this.dataAttrs(i),
        });
      }
      return this.btn({
        ...base,
        class: `${base.class} btn-warning text-dark btn-gen-letter`,
        icon: "fas fa-file-alt",
        text: "Carta de presentación",
        title: "Generar carta de presentación",
        data: this.dataAttrs(i),
      });
    }

    if (i.accepted > 0) {
      return this.btn({
        ...base,
        class: `${base.class} btn-success`,
        icon: "fas fa-check-circle",
        text: "Aceptado",
        title: "Tu solicitud fue aceptada",
        disabled: true,
      });
    }

    if (isCupoLleno) {
      return this.btn({
        ...base,
        class: `${base.class} btn-secondary`,
        icon: "fas fa-ban",
        text: "Cupo lleno",
        title: "Ya no hay vacantes disponibles",
        disabled: true,
      });
    }

    return this.btn({
      ...base,
      class: `${base.class} apply-practice`,
      style: `${base.style}background:linear-gradient(135deg,#01643D,#c6db53);color:#fff;border:none;`,
      icon: "fas fa-paper-plane",
      text: "Postularse",
      title: "Postularse",
      data: { ...this.dataAttrs(i), id: i.id },
    });
  }
  btn({
    class: cls,
    icon,
    text,
    title,
    disabled = false,
    data = {},
    style = "",
  }) {
    const attrs = Object.entries(data)
      .map(([k, v]) => `data-${k}="${v}"`)
      .join(" ");
    return `<button class="${cls}" ${attrs} title="${title}" style="${style}" ${disabled ? "disabled" : ""
      }><i class="${icon}"></i> ${text}</button>`;
  }
  dataAttrs(i) {
    return {
      empresa: i.empresa,
      "cargo-responsable": i.cargo_responsable,
      "nombre-responsable": i.nombre_responsable,
      domicilio: i.direccion_practica,
    };
  }

  /* ---------- Eventos ---------- */
  async handleApplyClick(e) {
    const $btn = $(e.currentTarget);
    const d = this.extract($btn);
    const ok = await this.confirm(
      "¿Confirmar postulación?",
      `¿Deseas postularte a la empresa "${d.empresa}" para tus prácticas profesionales?`
    );
    if (!ok) return;
    try {
      const r = await this.ajax({
        method: "POST",
        url: CONFIG.ENDPOINTS.STUDENTS,
        data: { action: "apply", id: d.id },
      });
      if (r?.success) {
        $("#ppDetalleModal").modal("hide");
        this.success();
        this.generateLetter(d);
        this.loadSolicitudes();
      } else this.error(r?.message || "Ocurrió un error al postularte.");
    } catch {
      this.error(
        "No se pudo completar la postulación. Intenta de nuevo más tarde."
      );
    }
  }

  async handleGenerateLetterClick(e) {
    const d = this.extract($(e.currentTarget));
    const ok = await this.confirm(
      "Generar carta de presentación",
      `¿Deseas generar la carta de presentación para la empresa "${d.empresa}"?`
    );
    if (ok) this.generateLetter(d);
  }

  handleNewAttendanceClick() {
    $(document).trigger("abrirFormularioAsistencia");
  }

  extract($b) {
    return {
      id: $b.data("id"),
      empresa: $b.data("empresa"),
      cargoResponsable: $b.data("cargo-responsable"),
      nombreResponsable: $b.data("nombre-responsable"),
      domicilio: $b.data("domicilio"),
    };
  }

  confirm(title, text) {
    return Swal.fire({
      title,
      text,
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Sí, continuar",
      cancelButtonText: "Cancelar",
      reverseButtons: true,
    }).then((r) => r.isConfirmed);
  }
  success() {
    Swal.fire({
      title: "¡Postulación registrada!",
      text: "Revisa tu correo para ver los siguientes pasos y descargar tu carta de presentación.",
      icon: "success",
      timer: 5000,
      showConfirmButton: false,
    });
  }
  error(msg) {
    Swal.fire({ title: "Error", text: msg, icon: "error" });
  }

  generateLetter({ empresa, cargoResponsable, nombreResponsable, domicilio }) {
    $("<form>", {
      action: CONFIG.ENDPOINTS.GENERATE_LETTER,
      method: "POST",
      target: "_blank",
    })
      .append($("<input>", { type: "hidden", name: "empresa", value: empresa }))
      .append(
        $("<input>", {
          type: "hidden",
          name: "cargoResponsable",
          value: cargoResponsable,
        })
      )
      .append(
        $("<input>", {
          type: "hidden",
          name: "nombreResponsable",
          value: nombreResponsable,
        })
      )
      .append(
        $("<input>", { type: "hidden", name: "domicilio", value: domicilio })
      )
      .appendTo("body")
      .submit()
      .remove();
  }

  /* ---------- Dashboard Áreas Internas (tipo_practica = universidad) ---------- */

  renderAreasDashboard(data) {
    const $c = $(CONFIG.SELECTORS.SOLICITUDES);
    const areas = data.areas || [];
    const postulacion = data.postulacion || null;

    // ── Instrucciones por estado ──────────────────────────────
    let instrHtml = '';
    if (!postulacion) {
      instrHtml = `
      <div class="alert alert-info d-flex gap-3 align-items-start mb-4 shadow-sm" role="alert" style="border-radius:.75rem;">
        <i class="fas fa-circle-info fa-lg mt-1 text-info flex-shrink-0"></i>
        <div>
          <strong>¿Cómo funciona?</strong>
          <ol class="mb-0 mt-1 ps-3 small">
            <li>Revisa las áreas disponibles y selecciona la que más te interese.</li>
            <li>Haz clic en <strong>Postularse</strong> para enviar tu solicitud. Solo puedes postularte a <strong>una</strong> área.</li>
            <li>El administrador revisará tu solicitud y recibirás una notificación por correo con la respuesta.</li>
            <li>Si tu solicitud es aceptada, podrás comenzar a registrar tus asistencias desde este panel.</li>
          </ol>
        </div>
      </div>`;
    } else if (postulacion.status == 0) {
      instrHtml = `
      <div class="alert alert-warning d-flex gap-3 align-items-start mb-4 shadow-sm" role="alert" style="border-radius:.75rem;">
        <i class="fas fa-clock fa-lg mt-1 text-warning flex-shrink-0"></i>
        <div>
          <strong>Tu solicitud está en revisión</strong>
          <p class="mb-0 mt-1 small">Postulaste al área <strong>${postulacion.nombre_area ?? ''}</strong>. El administrador revisará tu solicitud pronto y recibirás un correo con la respuesta. No es necesario que hagas nada más por ahora.</p>
        </div>
      </div>`;
    } else if (postulacion.status == 2) {
      instrHtml = `
      <div class="alert alert-danger d-flex gap-3 align-items-start mb-4 shadow-sm" role="alert" style="border-radius:.75rem;">
        <i class="fas fa-circle-exclamation fa-lg mt-1 flex-shrink-0"></i>
        <div>
          <strong>Tu postulación no fue aceptada</strong>
          <p class="mb-0 mt-1 small">Lamentablemente tu solicitud al área <strong>${postulacion.nombre_area ?? ''}</strong> no fue aceptada. Comunícate con la coordinación de prácticas para más información.</p>
        </div>
      </div>`;
    }

    if (!areas.length && !postulacion) {
      $c.html(instrHtml + `<div class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>No hay áreas abiertas en este momento.</div>`);
      return;
    }

    const cards = areas.map(a => {
      const vacantes = Math.max(0, a.cupo - (a.ocupadas || a.postulados || 0));
      let btnHtml = '';

      if (postulacion) {
        if (postulacion.status == 0) {
          btnHtml = postulacion.area_id == a.id
            ? `<span class="badge bg-warning text-dark mt-2"><i class="fas fa-clock me-1"></i>Tu solicitud está pendiente</span>`
            : `<span class="badge bg-secondary mt-2">Ya tienes una solicitud activa</span>`;
        } else if (postulacion.status == 1) {
          btnHtml = postulacion.area_id == a.id
            ? `<span class="badge bg-success mt-2"><i class="fas fa-check me-1"></i>Aceptado en esta área</span>`
            : `<span class="badge bg-secondary mt-2"></span>`;
        } else {
          btnHtml = vacantes > 0
            ? `<button class="btn btn-primary btn-sm mt-2 btn-postularse-area" data-id="${a.id}"><i class="fas fa-paper-plane me-1"></i>Postularse</button>`
            : `<span class="badge bg-secondary mt-2">Sin vacantes</span>`;
        }
      } else if (vacantes > 0) {
        btnHtml = `<button class="btn btn-primary btn-sm mt-2 btn-postularse-area" data-id="${a.id}"><i class="fas fa-paper-plane me-1"></i>Postularse</button>`;
      } else {
        btnHtml = `<span class="badge bg-secondary mt-2">Sin vacantes</span>`;
      }

      const pct = a.cupo > 0 ? Math.round(((a.ocupadas || a.postulados || 0) / a.cupo) * 100) : 0;

      return `<div class="col-md-4 mb-4">
        <div class="bento-card d-flex flex-column h-100 p-0" style="padding:0 !important;">
          <div style="background:linear-gradient(135deg,#01643D,#c6db53);padding:1.5rem 1.5rem 1rem;">
            <span style="font-size:1.2rem;font-weight:900;color:#fff;">
              <i class="fas fa-building-columns me-2"></i>${a.nombre}
            </span>
          </div>
          <div class="card-body d-flex flex-column" style="padding:1.5rem;">
            <p class="text-secondary small flex-grow-1 mb-3">${a.descripcion || '<em>Sin descripción.</em>'}</p>
            <div class="d-flex justify-content-between small mb-1">
              <span class="text-secondary fw-semibold">Cupo disponible</span>
              <span class="fw-bold" style="color:var(--brand-main);">${vacantes} vacante${vacantes !== 1 ? 's' : ''} de ${a.cupo}</span>
            </div>
            <div style="height:6px;border-radius:3px;background:#e2e8f0;overflow:hidden;" class="mb-3">
              <div style="height:100%;width:${pct}%;border-radius:3px;background:var(--brand-main);"></div>
            </div>
            ${btnHtml}
          </div>
        </div>
      </div>`;
    }).join('');

    $c.html(instrHtml + `<div class="row gx-3 gy-4">${cards}</div>`);

    // Handler postularse
    $(document).off('click', '.btn-postularse-area').on('click', '.btn-postularse-area', async (e) => {
      const areaId = $(e.currentTarget).data('id');
      const result = await Swal.fire({
        title: '¿Postularse a esta área?',
        text: 'Solo puedes postularte a un área. Esta acción no se puede deshacer.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, postularme',
        cancelButtonText: 'Cancelar'
      });
      if (!result.isConfirmed) return;
      try {
        const res = await this.ajax({
          method: 'POST',
          url: CONFIG.ENDPOINTS.STUDENTS,
          data: { action: 'applyArea', area_id: areaId }
        });
        if (res.success) {
          Swal.fire({ icon: 'success', title: '¡Solicitud enviada!', text: res.message, timer: 2000, showConfirmButton: false });
          this.loadSolicitudes();
        } else {
          Swal.fire({ icon: 'error', title: 'Error', text: res.message });
        }
      } catch (err) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo procesar la solicitud.' });
      }
    });
  }

  /* ---------- Dashboard (idéntico a tu lógica original) ---------- */

  renderPracticesDashboard(data) {
    // Validar que data tenga la estructura esperada
    if (!data || typeof data !== "object") {
      console.error("Invalid data for practices dashboard:", data);
      Utils.showMessage(
        $(CONFIG.SELECTORS.SOLICITUDES),
        "Error: Datos inválidos para el dashboard",
        "danger"
      );
      return;
    }

    // Asegurar que asistencias sea un array
    if (!Array.isArray(data.asistencias)) {
      console.warn("asistencias is not an array, initializing empty array");
      data.asistencias = [];
    }

    const dashboardHTML = this.generateDashboardHTML(data);
    $(CONFIG.SELECTORS.SOLICITUDES).html(dashboardHTML);
  }

  generateDashboardHTML(data) {
    // 1) Calcular progreso global
    const { totalHours, progressPercentage: rawProgress } =
      this.calculateProgress(data.asistencias);
    const progressPercentage = Math.min(rawProgress, 100);
    const remainingHours = Math.max(CONFIG.HORAS_TOTALES - totalHours, 0)
      .toFixed(2)
      .replace(/\.00$/, "");

    // 2) Generar botones según el estado actual
    const reportBtnHTML = this.generateReportButtons(data, totalHours);

    // 3) Determinar color de la barra de progreso
    const progressBarColor = this.getProgressBarColor(progressPercentage);

    // 4) Construir y retornar HTML
    return this.buildDashboardHTML({
      totalHours,
      progressPercentage,
      remainingHours,
      reportBtnHTML,
      progressBarColor,
      attendanceCount: data.asistencias.length,
      asistencias: data.asistencias,
      practicasFinalizadas: !!data.practicas_finalizadas,
      fechaFinalizacion: data.fecha_finalizacion || null,
    });
  }

  // Método auxiliar para generar botones de reporte
  generateReportButtons(data, totalHours) {
    // Si el alumno ya finalizó (descargó la constancia), mostrar solo el botón de re-descarga
    if (data.practicas_finalizadas) {
      return `
      <span class="badge bg-success me-2" style="font-size:.8rem;padding:.5rem .75rem;">
        <i class="fas fa-check-circle me-1"></i>Prácticas Finalizadas
      </span>
      <button class="btn-neo" id="btn-download-constancia" style="background:var(--brand-main); color:white;">
        <i class="fas fa-file-download me-1"></i> Re-descargar constancia
      </button>`;
    }

    // Botón por defecto
    const defaultBtn = `
    <button class="btn-neo" id="btn-nueva-asistencia" data-bs-toggle="modal" data-bs-target="#modalAsistencia">
      <i class="fas fa-plus"></i> Pasar asistencia
    </button>`;

    // Si no ha completado 180 horas, solo mostrar botón por defecto
    if (totalHours < 180) {
      return defaultBtn;
    }

    // Lógica para reporte parcial (180+ horas)
    if (totalHours >= 180 && totalHours < 360) {
      return this.generatePartialReportButtons(data, defaultBtn);
    }

    // Lógica para reporte final (360+ horas)
    if (totalHours >= 360) {
      return this.generateFinalReportButtons(data);
    }

    return defaultBtn;
  }

  // Método auxiliar para botones de reporte parcial
  generatePartialReportButtons(data, defaultBtn) {
    const { reporte_parcial } = data;

    // Si no hay reporte parcial, mostrar botón para entregarlo
    let btnReporte = "";
    if (reporte_parcial === false) {
      btnReporte = `
      <button class="btn-neo" id="btn-entregar-reporte-parcial" data-bs-toggle="modal" data-bs-target="#modalReporteParcial" style="background:#0ea5e9; color:white;">
        <i class="fas fa-file-upload"></i> Entregar reporte parcial
      </button>`;
    }

    let btnEval = "";
    if (data.eval_integral_180 && data.eval_integral_180.alumno === false) {
      btnEval = `
      <button class="btn-neo" id="btn-eval-integral-180" data-bs-toggle="modal" data-bs-target="#modalEvalIntegralAlumno" data-hito="intermedia" style="background:#f59e0b; color:white;">
        <i class="fas fa-star-half-alt"></i> Completar Evaluación Intermedia
      </button>`;
    }

    if (reporte_parcial === false || (data.eval_integral_180 && data.eval_integral_180.alumno === false)) {
        return `${btnReporte}${btnEval}`;
    }

    // Si hay reporte parcial pero no está completado
    if (reporte_parcial && reporte_parcial.completed === 0) {
      const {
        aproveOrganismo = 0,
        aproveAdmin = 0,
        comentarios = "",
        objetivo = "",
        actividades_repotadas = "",
      } = reporte_parcial;

      let updateButton = "";
      let alertMessage = "";

      // Si fue rechazado por alguna entidad
      if (aproveOrganismo === 2 || aproveAdmin === 2) {
        updateButton = `
        <button class="btn-neo" id="btn-entregar-reporte-parcial" data-bs-toggle="modal" data-bs-target="#modalReporteParcial" style="background:#dc2626; color:white;">
          <i class="fas fa-file-upload"></i> Actualizar reporte rechazado
        </button>`;

        alertMessage = `
        <div class="alert alert-danger mt-2 p-2 small border-0" style="border-radius:1rem; background:rgba(220,38,38,0.1); color:#dc2626;">
          <strong>Motivo del rechazo:</strong> ${comentarios || "Sin comentarios"}
        </div>`;
      }

      // Botón de asistencia con restricción
      const restrictedAttendanceBtn = `
      <button class="btn-neo" id="btn-nueva-asistencia" data-motivo="espera-revision">
        <i class="fas fa-plus"></i> Pasar asistencia
      </button>`;

      // Configurar handlers después del renderizado
      this.setupPartialReportHandlers(objetivo, actividades_repotadas, data.tipo_practica);

      return `${restrictedAttendanceBtn}${updateButton}${alertMessage}`;
    }

    return defaultBtn;
  }

  // Método auxiliar para configurar handlers del reporte parcial
  setupPartialReportHandlers(objetivo, actividades_repotadas, tipoPractica) {
    setTimeout(() => {
      // Handler para botón de asistencia restringido
      $('#btn-nueva-asistencia[data-motivo="espera-revision"]')
        .off("click")
        .on("click", function (e) {
          e.preventDefault();
          const revisor = tipoPractica === 'universidad'
            ? 'el encargado del área y el administrador'
            : 'el organismo externo y el administrador';
          Swal.fire({
            icon: "info",
            title: "Espere la revisión",
            text: `Debe esperar a que ${revisor} revisen el reporte antes de poder pasar más asistencias.`,
            confirmButtonText: "Entendido",
          });
        });

      // Handler para botón de entregar reporte parcial
      $("#btn-entregar-reporte-parcial").on("click", function () {
        $("#objetivoGeneral").val(objetivo);
        $("#actividadesReportadas").val(actividades_repotadas);
      });
    }, 0);
  }

  // Método auxiliar para botones de reporte final
  generateFinalReportButtons(data) {
    const { reporte_final } = data;
    let updateButton = "";
    let alertMessage = "";
    let buttonsHTML = "";

    if (reporte_final === false) {
      buttonsHTML += `
      <button class="btn-neo" id="btn-entregar-reporte-final" data-bs-toggle="modal" data-bs-target="#modalReporteFinal" style="background:#f59e0b; color:white;">
        <i class="fas fa-file-upload"></i> Entregar reporte final
      </button>
    `;
    } 
    
    if (data.eval_integral_360 && data.eval_integral_360.alumno === false) {
      buttonsHTML += `
      <button class="btn-neo" id="btn-eval-integral-360" data-bs-toggle="modal" data-bs-target="#modalEvalIntegralAlumno" data-hito="final" style="background:#0ea5e9; color:white;">
        <i class="fas fa-star-half-alt"></i> Completar Evaluación Final
      </button>
    `;
    }

    if (reporte_final !== false && data.eval_integral_360 && data.eval_integral_360.alumno !== false && reporte_final.completed === 1) {
      buttonsHTML = `
      <button class="btn-neo" id="btn-download-constancia" style="background:#10b981; color:white;">
        <i class="fas fa-file-download"></i> Descargar constancia
      </button>
    `;
    } else if (
      reporte_final.aproveOrganismo === 2 ||
      reporte_final.aproveAdmin === 2
    ) {
      updateButton = `
      <button class="btn-neo" id="btn-entregar-reporte-final" data-bs-toggle="modal" data-bs-target="#modalReporteFinal" style="background:#dc2626; color:white;">
        <i class="fas fa-file-upload"></i> Actualizar reporte final
      </button>
    `;
      alertMessage = `
      <div class="alert alert-danger mt-2 p-2 small border-0" style="border-radius:1rem; background:rgba(220,38,38,0.1); color:#dc2626;">
        <strong>Motivo del rechazo:</strong> ${reporte_final.comentarios || "Sin comentarios"}
      </div>
    `;
      buttonsHTML = updateButton + alertMessage;
    }

    // SIEMPRE registrar eventos
    this.setupFinalReportHandlers(reporte_final);

    return buttonsHTML;
  }

  // Método auxiliar para configurar handlers del report final
  setupFinalReportHandlers(reporte_final) {
    setTimeout(() => {
      // Handler para botón de entregar reporte final
      $("#btn-entregar-reporte-final").on("click", function () {
        $("#objetivoGeneralFinal").val(reporte_final.objetivo_general || "");
        $("#actividadesRealizadasFinal").val(
          reporte_final.actividades_realizadas || ""
        );
        $("#resultadosObtenidosFinal").val(
          reporte_final.resultados_obtenidos || ""
        );
        $("#capacitacionRecibidaFinal").val(
          reporte_final.capacitacion_recibida || ""
        );
        $("#experienciaProfesionalFinal").val(
          reporte_final.experiencia_profesional || ""
        );
        $("#experienciaPersonalFinal").val(
          reporte_final.experiencia_personal || ""
        );
      });

      // Handler para botón de actualizar reporte final
      $("#btn-download-constancia").on("click", function () {
        generateConstancia();
      });
    }, 0);
  }

  // Método auxiliar para determinar color de la barra de progreso
  getProgressBarColor(progressPercentage) {
    if (progressPercentage > 75) return "bg-success";
    if (progressPercentage > 25) return "bg-warning";
    return "bg-danger";
  }

  // Método auxiliar para construir el HTML final
  buildDashboardHTML(params) {
    const {
      totalHours,
      progressPercentage,
      remainingHours,
      reportBtnHTML,
      progressBarColor,
      attendanceCount,
      asistencias,
      practicasFinalizadas = false,
      fechaFinalizacion = null,
    } = params;

    const pctColor = progressPercentage > 75 ? 'var(--brand-main)' : progressPercentage > 25 ? '#d97706' : '#dc2626';

    const finalizadoBanner = practicasFinalizadas ? `
      <div class="bento-card" style="grid-column: span 12; background: linear-gradient(135deg, #d1fae5, #a7f3d0); border: 2px solid #059669; padding: 1.5rem;">
        <div class="d-flex align-items-center gap-3">
          <div style="font-size:2.5rem;">🎓</div>
          <div>
            <h5 class="mb-1" style="color:#065f46;font-weight:900;">¡Prácticas Profesionales Finalizadas!</h5>
            <p class="mb-0" style="color:#047857;font-size:1rem;">
              Has completado exitosamente tus prácticas profesionales y descargado tu constancia de acreditación.
              ${fechaFinalizacion ? `<br><strong>Fecha de finalización:</strong> ${fechaFinalizacion.split(' ')[0]}` : ''}
            </p>
          </div>
        </div>
      </div>` : '';

    return `
      <!-- BENTO GRID -->
      <div class="bento-grid">
        ${finalizadoBanner}

        <!-- Hero Box -->
        <div class="bento-card bento-hero">
          <div class="hero-blob"></div>
          <h2 class="hero-title">Tu Progreso</h2>
          <p class="hero-subtitle">Sigue acumulando horas para finalizar tus prácticas.</p>
          <div class="d-flex gap-2 flex-wrap">
            ${reportBtnHTML}
          </div>
        </div>

        <!-- KPI 1 -->
        <div class="bento-card bento-kpi-1">
          <div class="kpi-title"><i class="fas fa-clock text-warning"></i> Horas Acumuladas</div>
          <div class="kpi-value" style="color:var(--brand-main);">${totalHours}</div>
          <div class="small text-muted mt-2">de ${CONFIG.HORAS_TOTALES} requeridas</div>
        </div>

        <!-- KPI 2 -->
        <div class="bento-card bento-kpi-2">
          <div class="kpi-title"><i class="fas fa-calendar-check text-info"></i> Días Asistidos</div>
          <div class="kpi-value">${attendanceCount}</div>
        </div>

        <!-- KPI 3 -->
        <div class="bento-card bento-kpi-3">
          <div class="kpi-title"><i class="fas fa-hourglass-half text-danger"></i> Horas Restantes</div>
          <div class="kpi-value" style="color:#dc2626;">${remainingHours}</div>
        </div>

        <!-- KPI 4 -->
        <div class="bento-card bento-kpi-4">
          <div class="kpi-title"><i class="fas fa-chart-pie text-success"></i> Avance Total</div>
          <div class="kpi-value" style="color:${pctColor};">${progressPercentage.toFixed(1)}%</div>
          <div style="height:8px; border-radius:4px; background:#e2e8f0; overflow:hidden; width:80%; margin-top:1rem;">
            <div style="height:100%; width:${progressPercentage}%; background:${pctColor};"></div>
          </div>
        </div>

        <!-- Lista de asistencias (Ocupa todo el ancho debajo de los KPIs) -->
        <div class="bento-card" style="grid-column: span 12; padding: 2.5rem;">
          ${this.generateAttendanceCard(asistencias)}
        </div>
      </div>`;
  }

  renderDataPractices(data) {
    // Establecer el mínimo de fecha para el campo #fechaAsistencia según start_date
    if (data && data.start_date) {
      $("#fechaAsistencia").attr("min", data.start_date);
    }

    // ── Guardar días autorizados para validación frontend ──────────────────
    // dia_inicio y dia_fin usan los códigos: L M X J V S D
    if (data && data.dia_inicio && data.dia_fin) {
      const DIA_ISO = { L: 1, M: 2, X: 3, J: 4, V: 5, S: 6, D: 0 };
      const numInicio = DIA_ISO[data.dia_inicio.toUpperCase()] ?? 1;
      const numFin = DIA_ISO[data.dia_fin.toUpperCase()] ?? 5;

      // Guardar en el input como data attributes para que main.js pueda leerlos
      $("#fechaAsistencia")
        .data("dia-inicio", numInicio)
        .data("dia-fin", numFin)
        .attr("data-dia-inicio", numInicio)
        .attr("data-dia-fin", numFin);

      // Bind: al cambiar la fecha, validar el día en tiempo real
      $("#fechaAsistencia").off("change.diasAutorizados").on("change.diasAutorizados", function () {
        const val = $(this).val();
        if (!val) return;
        // getDay(): 0=Dom, 1=Lun … 6=Sáb
        // Convertir a ISO (1=Lun … 7=Dom) para comparar con el mismo criterio del backend
        const jsDay = new Date(val + "T00:00:00").getDay();
        const isoDay = jsDay === 0 ? 7 : jsDay;
        const NOMBRES = { 1: "Lunes", 2: "Martes", 3: "Miércoles", 4: "Jueves", 5: "Viernes", 6: "Sábado", 7: "Domingo" };
        // Mapear numInicio/numFin a ISO también (L→1 … D→7, excepto D=0 en JS → 7 ISO)
        const diaInicioISO = numInicio === 0 ? 7 : numInicio;
        const diaFinISO = numFin === 0 ? 7 : numFin;
        const DIA_NOMBRE_MAP = { L: "Lunes", M: "Martes", X: "Miércoles", J: "Jueves", V: "Viernes", S: "Sábado", D: "Domingo" };
        const diaInicioNombre = DIA_NOMBRE_MAP[data.dia_inicio.toUpperCase()] ?? data.dia_inicio;
        const diaFinNombre = DIA_NOMBRE_MAP[data.dia_fin.toUpperCase()] ?? data.dia_fin;

        if (isoDay < diaInicioISO || isoDay > diaFinISO) {
          Swal.fire({
            icon: "warning",
            title: "Día no autorizado",
            text: `Solo puedes registrar asistencia de ${diaInicioNombre} a ${diaFinNombre}. El día seleccionado (${NOMBRES[isoDay] ?? "desconocido"}) no está dentro del horario autorizado.`,
            confirmButtonText: "Entendido",
          });
          $(this).val(""); // limpiar la fecha inválida
        }
      });
    }
  }

  calculateProgress(asistencias) {
    // Validar que asistencias sea un array
    if (!Array.isArray(asistencias)) {
      console.warn("asistencias is not an array:", asistencias);
      return {
        totalHours: 0,
        progressPercentage: 0,
      };
    }

    const totalHours = asistencias.reduce((sum, attendance) => {
      // Validar que attendance tenga las propiedades necesarias
      if (!attendance || typeof attendance !== "object") {
        return sum;
      }

      const hours = attendance.horas_validadas !== null && attendance.horas_validadas !== undefined
        ? parseFloat(attendance.horas_validadas)
        : Utils.calculateWorkingHours(attendance.hora_entrada, attendance.hora_salida);
      return sum + hours;
    }, 0);

    const progressPercentage = (totalHours / CONFIG.HORAS_TOTALES) * 100;

    return {
      totalHours: Number(totalHours.toFixed(2)),
      progressPercentage,
    };
  }

  generateAttendanceCard(asistencias) {
    // Validar que asistencias sea un array
    if (!Array.isArray(asistencias)) {
      console.warn(
        "asistencias is not an array in generateAttendanceCard:",
        asistencias
      );
      asistencias = [];
    }

    // Ordenar asistencias por fecha descendente (más reciente primero)
    const sortedAsistencias = [...asistencias].sort((a, b) => {
      // Suponiendo formato 'YYYY-MM-DD'
      return b.fecha.localeCompare(a.fecha);
    });

    const total = sortedAsistencias.length;
    const maxToShow = 5;
    let visibleAsistencias = sortedAsistencias.slice(0, maxToShow);

    // Si hay más de 5, mostrar solo las últimas 5 y un botón para ver todas
    let showMoreBtn = "";
    if (total > maxToShow) {
      showMoreBtn = `
        <div class="text-center py-2">
          <button class="btn btn-link btn-sm btn-ver-todas-asistencias">
            Ver todas las asistencias (${total})
          </button>
        </div>
      `;
    }

    const attendanceItems =
      total === 0
        ? `<div class="text-center p-3">${CONFIG.MESSAGES.NO_ATTENDANCE}</div>`
        : visibleAsistencias
          .map((attendance) => this.generateAttendanceItem(attendance))
          .join("");

    // El contenedor tendrá un data attribute para saber si está expandido
    return `
      <div>
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h2 class="pane-title"><i class="fas fa-calendar-check me-2" style="color:var(--brand-main);"></i>Asistencias registradas</h2>
          ${total > maxToShow ? `<span class="badge bg-light text-dark" style="font-size:1rem;">${total} registros</span>` : ''}
        </div>
        <div class="asistencias-list" data-expanded="false" style="display:flex; flex-direction:column; gap:1rem;">
          ${attendanceItems}
        </div>
        ${showMoreBtn}
      </div>
    `;
  }

  generateAttendanceItem(attendance) {
    // Validar que attendance sea un objeto válido
    if (!attendance || typeof attendance !== "object") {
      console.warn("Invalid attendance object:", attendance);
      return "";
    }

    const workingHours = attendance.horas_validadas !== null && attendance.horas_validadas !== undefined
      ? parseFloat(attendance.horas_validadas)
      : Utils.calculateWorkingHours(attendance.hora_entrada, attendance.hora_salida);
    const formattedHours = Utils.formatWorkingHours(workingHours);
    const formattedDate = Utils.formatDateSpanish(attendance.fecha);

    return `
      <div class="asistencia-item px-4 py-3 d-flex align-items-center justify-content-between"
           style="background:rgba(255,255,255,0.8); border:1px solid rgba(0,0,0,0.05); border-radius:1rem; transition:transform .2s;" 
           onmouseenter="this.style.transform='translateX(5px)'" onmouseleave="this.style.transform=''">
        <div class="d-flex flex-column flex-grow-1">
          <div class="fw-bold mb-1" style="color:var(--brand-main); font-size:1.1rem;">
            <i class="fas fa-calendar-day me-1"></i> ${formattedDate}
          </div>
          <div class="small text-muted mb-2">
            <i class="fas fa-sign-in-alt me-1"></i> Entrada: <span class="text-dark fw-semibold">${attendance.hora_entrada || '–'}</span>
            &nbsp;|&nbsp;
            <i class="fas fa-sign-out-alt me-1"></i> Salida: <span class="text-dark fw-semibold">${attendance.hora_salida || '–'}</span>
          </div>
          <div class="text-secondary" style="font-size:0.95rem;">
            <i class="fas fa-tasks me-1"></i> ${attendance.actividad || ''}
          </div>
        </div>
        <div class="text-end ms-3">
          <span style="background:var(--brand-accent); color:var(--brand-dark); border-radius:100px; padding:.5rem 1rem; font-size:1.1rem; font-weight:800; white-space:nowrap; box-shadow:0 4px 10px rgba(198, 219, 83, 0.3);">
            <i class="fas fa-clock me-1"></i>${formattedHours}
          </span>
        </div>
      </div>`;
  }
}

function generateConstancia() {
  // Mostrar pantalla de carga
  Swal.fire({
    title: "Generando constancia...",
    text: "Por favor espera mientras se genera tu constancia.",
    allowOutsideClick: false,
    allowEscapeKey: false,
    didOpen: () => {
      Swal.showLoading();
    },
  });

  const formData = new FormData();
  formData.append("action", "generate_constancia");

  $.ajax({
    type: "POST",
    url: CONFIG.ENDPOINTS.STUDENTS,
    data: formData,
    processData: false,
    contentType: false,
    xhrFields: {
      responseType: "blob",
    },
    success(blob, status, xhr) {
      Swal.close(); // Ocultar pantalla de carga

      const disposition = xhr.getResponseHeader("Content-Disposition");
      let filename = "constancia.pdf";
      if (disposition && disposition.indexOf("filename=") !== -1) {
        filename = disposition.split("filename=")[1].replace(/"/g, "");
      }
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      a.remove();
      window.URL.revokeObjectURL(url);

      Swal.fire({
        icon: "success",
        title: "¡Éxito!",
        text: "Constancia generada y descargada correctamente.",
        confirmButtonText: "Aceptar",
      }).then(() => {
        $("#modalConstancia").modal("hide");
      });
    },
    error() {
      Swal.close(); // Ocultar pantalla de carga
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "No se pudo generar la constancia. Intente de nuevo.",
        confirmButtonText: "Aceptar",
      });
    },
  });
}
