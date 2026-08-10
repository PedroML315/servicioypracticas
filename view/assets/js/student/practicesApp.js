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
        this.bloqueoActivo = !!response.bloqueoActivo;
        this.postulacionActiva = response.postulacionActiva || null;
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

    // Banner de bloqueo: el alumno ya tiene una postulación en proceso (estilo bento)
    const estadoTxt = {
      PREPOSTULADO: "La empresa está revisando tu perfil",
      ENTREVISTA_PROGRAMADA: "Tienes una entrevista programada — revisa tu correo",
      ENTREVISTA_CERRADA: "La empresa está evaluando tu entrevista",
    }[this.postulacionActiva?.estado] || "Esperando respuesta de la empresa";
    const banner = this.bloqueoActivo ? `
      <div class="bento-card d-flex flex-wrap align-items-center gap-3 mb-4" style="padding:1.4rem 1.6rem; border-left:6px solid var(--brand-accent); background:linear-gradient(120deg, rgba(1,100,61,.06), rgba(198,219,83,.10));">
        <div style="width:56px;height:56px;border-radius:1.1rem;background:rgba(1,100,61,.1);color:var(--brand-main);display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0;">
          <i class="fas fa-hourglass-half"></i>
        </div>
        <div class="flex-grow-1" style="min-width:220px;">
          <div style="font-weight:900;color:var(--brand-dark);font-size:1.05rem;">
            Postulación en proceso${this.postulacionActiva?.empresa ? ` · ${Utils.escape(this.postulacionActiva.empresa)}` : ""}
          </div>
          <div style="color:var(--text-secondary);font-size:.9rem;margin-top:.15rem;">
            ${estadoTxt}. Mientras tanto no puedes postularte a otra vacante; te avisaremos por correo.
          </div>
        </div>
        <span style="background:#fff;border:1px solid rgba(1,100,61,.25);color:var(--brand-main);border-radius:100px;padding:.45rem 1rem;font-size:.8rem;font-weight:800;white-space:nowrap;">
          <i class="fas fa-envelope me-1"></i>Te llegará un correo
        </span>
      </div>` : "";

    if (!allList.length) {
      $c.html(banner + `
        <div class="pp-empty-state">
          <div class="pp-empty-icon"><i class="fas fa-compass"></i></div>
          <h4>No hay vacantes disponibles</h4>
          <p>Por ahora no encontramos oportunidades que coincidan con tu búsqueda. Vuelve a intentarlo más tarde.</p>
        </div>`);
      return;
    }

    const cardsHtml = allList.map((i) => this.card(i)).join("");
    const plural = allList.length === 1 ? "vacante disponible" : "vacantes disponibles";

    $c.html(banner + `
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
      ${this.detalleField("fas fa-flag-checkered", "Resultados esperados", item.resultados_esperados)}
      ${this.detalleField("fas fa-star", "Habilidades deseadas (adicionales)", item.capacidades)}
      ${this.detalleField("fas fa-star", "Actitudes deseadas (adicionales)", item.actitudes)}
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

    const estado = i.estado_postulacion || null;
    const numStudents = parseInt(i.num_students) || 0;
    const numPracticantes = parseInt(i.num_practicantes) || 0;
    const isCupoLleno = numStudents >= numPracticantes;

    // 1. Vacante bloqueada para este alumno (rechazo previo, prepostulación o final)
    if (parseInt(i.bloqueada) > 0 || estado === 'RECHAZADO_PREPOSTULACION' || estado === 'RECHAZADO_FINAL') {
      return this.btn({
        ...base,
        class: `${base.class} btn-secondary`,
        icon: "fas fa-ban",
        text: "No disponible",
        title: "Tu postulación a esta vacante fue rechazada",
        disabled: true,
      });
    }

    // 2. Estado de la postulación del alumno EN ESTA vacante
    if (estado === 'PREPOSTULADO') {
      return this.btn({
        ...base,
        class: `${base.class} btn-info text-white`,
        icon: "fas fa-hourglass-half",
        text: "Prepostulación enviada",
        title: "La empresa está revisando tu perfil",
        disabled: true,
      });
    }
    if (estado === 'ENTREVISTA_PROGRAMADA') {
      return this.btn({
        ...base,
        class: `${base.class} btn-primary text-white`,
        icon: "fas fa-calendar-check",
        text: "Entrevista programada",
        title: "Revisa tu correo con los datos de la entrevista",
        disabled: true,
      });
    }
    if (estado === 'ENTREVISTA_CERRADA') {
      return this.btn({
        ...base,
        class: `${base.class} btn-primary text-white`,
        icon: "fas fa-user-clock",
        text: "En evaluación",
        title: "La empresa está evaluando tu entrevista",
        disabled: true,
      });
    }
    if (estado === 'ACEPTADO_FINAL') {
      return this.btn({
        ...base,
        class: `${base.class} btn-success`,
        icon: "fas fa-check-circle",
        text: "Aceptado",
        title: "¡Fuiste aceptado!",
        disabled: true,
      });
    }

    // 3. Bloqueo global: tiene una postulación activa en otra vacante
    if (this.bloqueoActivo) {
      return this.btn({
        ...base,
        class: `${base.class} btn-light text-muted`,
        style: `${base.style}border:1px dashed #cbd5e1;`,
        icon: "fas fa-lock",
        text: "Postulación en proceso",
        title: "Ya tienes una postulación activa. Espera la respuesta de la empresa.",
        disabled: true,
      });
    }

    // 4. Cupo lleno
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

    // 5. Postularme (abre el formulario de prepostulación)
    return this.btn({
      ...base,
      class: `${base.class} apply-practice`,
      style: `${base.style}background:linear-gradient(135deg,#01643D,#c6db53);color:#fff;border:none;`,
      icon: "fas fa-paper-plane",
      text: "Postularme",
      title: "Postularme",
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
    const d = this.extract($(e.currentTarget));
    await this.openPrepostulacion(d);
  }

  /* ---------- Prepostulación (wizard obligatorio, estilo "rail") ---------- */
  async openPrepostulacion(d) {
    this._prepostVacante = d;
    this.ensurePrepostModal();
    this.buildPrepostSteps();
    $("#ppwEmpresa").text(d.empresa || "la empresa");
    this.gotoPrepostStep(0);
    $("#ppDetalleModal").modal("hide");
    $("#ppPrepostModal").modal("show");
  }

  async submitPrepostulacion() {
    const d = this._prepostVacante || {};
    const formData = this.collectPrepostForm();
    if (!formData) return;
    const $btn = $("#ppwNext").prop("disabled", true);
    try {
      const r = await this.ajax({
        method: "POST",
        url: CONFIG.ENDPOINTS.STUDENTS,
        data: { action: "apply", id: d.id, ...formData },
      });
      if (r?.success) {
        $("#ppPrepostModal").modal("hide");
        this.prepostSuccess();
        this.loadSolicitudes();
      } else {
        this.showPrepostError(r?.message || "No se pudo enviar tu prepostulación.");
      }
    } catch {
      this.showPrepostError("No se pudo completar la prepostulación. Intenta de nuevo más tarde.");
    } finally {
      $btn.prop("disabled", false);
    }
  }

  /** Definición de los pasos del wizard de prepostulación. */
  prepostStepsDef() {
    return [
      {
        icon: "fas fa-user-graduate", title: "Mi perfil", sub: "Datos académicos",
        fields: [
          { k: "licenciatura", type: "text", req: true, icon: "fas fa-graduation-cap", label: "Licenciatura que estoy cursando", ph: "Tu licenciatura" },
          { k: "disponibilidad_horario", type: "pills", req: true, icon: "far fa-clock", label: "Disponibilidad de horario", opts: ["Matutino", "Vespertino", "Tiempo completo", "Flexible"] },
          { k: "modalidad", type: "pills", req: true, icon: "fas fa-laptop-house", label: "Modalidad en la que puedo realizar las actividades", opts: ["Presencial", "Híbrida", "Remota"] },
        ],
      },
      {
        icon: "fas fa-tools", title: "Mis habilidades", sub: "Herramientas e idiomas",
        fields: [
          { k: "nivel_office", type: "pills", req: true, icon: "fas fa-file-excel", label: "Nivel de dominio de Microsoft Office", opts: ["Básico", "Intermedio", "Avanzado"] },
          { k: "herramientas", type: "chips", req: false, icon: "fas fa-toolbox", label: "Herramientas informáticas que manejo", hint: "Toca todas las que manejes; si falta alguna, escríbela en «Otra».", opts: ["Microsoft Excel", "Microsoft Word", "Microsoft PowerPoint", "Power BI", "Canva", "Google Workspace", "SAP", "AutoCAD", "SQL"] },
          { k: "herramientas_otro", type: "text", req: false, icon: "fas fa-plus-circle", label: "Otra herramienta", ph: "Otra (especifica)…" },
          { k: "nivel_ingles", type: "pills", req: true, icon: "fas fa-language", label: "Nivel de inglés", opts: ["Básico", "Intermedio", "Avanzado", "No aplica"] },
          { k: "equipo_remoto", type: "pills", req: true, icon: "fas fa-wifi", label: "¿Cuento con equipo de cómputo e internet para modalidad remota?", opts: ["Sí", "No", "No aplica"] },
        ],
      },
      {
        icon: "far fa-calendar-check", title: "Disponibilidad", sub: "Inicio e intereses",
        fields: [
          { k: "disponibilidad_inicio", type: "pills", req: true, icon: "far fa-calendar-check", label: "Disponibilidad para iniciar prácticas", opts: ["Inmediata", "En una semana", "En dos semanas", "En un mes"] },
          { k: "area_interes", type: "pills", req: true, icon: "fas fa-briefcase", label: "Área o tipo de actividades de interés", opts: ["Administrativas", "Operativas", "Análisis de datos", "Desarrollo de proyectos", "Investigación", "Cualquier actividad relacionada con mi licenciatura"] },
          { k: "acepta_capacitacion", type: "pills", req: true, icon: "fas fa-chalkboard-teacher", label: "¿Estoy dispuesto(a) a recibir capacitación previa?", opts: ["Sí", "No"] },
          { k: "objetivo_practicas", type: "pills", req: true, icon: "fas fa-bullseye", label: "Principal objetivo al realizar prácticas", opts: ["Adquirir experiencia profesional", "Desarrollar habilidades técnicas y profesionales", "Fortalecer conocimientos en el área de interés", "Generar oportunidades de contratación", "Cumplir con el requisito académico"] },
        ],
      },
      {
        icon: "fas fa-video", title: "Entrevista", sub: "Preferencias y aviso",
        fields: [
          { k: "modalidad_entrevista_pref", type: "pills", req: true, icon: "fas fa-video", label: "Modalidad de entrevista preferida", opts: ["Virtual", "Presencial"] },
          { k: "horario_propuesto", type: "text", req: false, icon: "far fa-calendar-alt", label: "Horario propuesto", ph: "Ej. Lunes a viernes de 8:00 a 12:00" },
          { k: "_aviso", type: "aviso", req: true },
        ],
      },
    ];
  }

  /** Inyecta (una sola vez) los estilos y el cascarón del modal wizard. */
  ensurePrepostModal() {
    if (document.getElementById("ppPrepostModal")) return;

    const css = `
      #ppPrepostModal .ppw-content { border: none; border-radius: 2rem; overflow: hidden; background: rgba(255,255,255,.92); backdrop-filter: blur(30px); box-shadow: 0 25px 50px -12px rgba(0,0,0,.25); }
      .ppw-shell { display: flex; min-height: 560px; }
      .ppw-rail { flex: 0 0 290px; background: linear-gradient(160deg,#01643D 0%,#00321f 55%,#00204a 100%); color: #fff; padding: 2.3rem 1.9rem; position: relative; overflow: hidden; display: flex; flex-direction: column; }
      .ppw-rail::before { content:''; position:absolute; width:260px; height:260px; background: var(--brand-accent, #c6db53); filter: blur(90px); opacity:.22; border-radius:50%; top:-90px; right:-100px; pointer-events:none; }
      .ppw-rail-head { display:flex; align-items:center; gap:1rem; margin-bottom:2.4rem; position:relative; z-index:2; }
      .ppw-rail-icon { width:54px; height:54px; border-radius:1.1rem; background:rgba(255,255,255,.12); display:flex; align-items:center; justify-content:center; font-size:1.35rem; color:var(--brand-accent,#c6db53); flex-shrink:0; }
      .ppw-rail-title { font-weight:900; font-size:1.3rem; margin:0; letter-spacing:-.02em; }
      .ppw-rail-sub { font-size:.8rem; opacity:.72; margin:.2rem 0 0; font-weight:300; }
      .ppw-steps { list-style:none; padding:0; margin:0; position:relative; z-index:2; flex-grow:1; }
      .ppw-step-it { display:flex; align-items:center; gap:1rem; padding:.65rem 0; position:relative; opacity:.5; transition:opacity .3s; }
      .ppw-step-it::after { content:''; position:absolute; left:18px; top:44px; bottom:-6px; width:2px; background:rgba(255,255,255,.15); }
      .ppw-step-it:last-child::after { display:none; }
      .ppw-step-it.active, .ppw-step-it.completed { opacity:1; }
      .ppw-dot { width:38px; height:38px; border-radius:50%; flex-shrink:0; display:flex; align-items:center; justify-content:center; font-weight:900; font-size:.92rem; background:rgba(255,255,255,.1); border:2px solid rgba(255,255,255,.3); transition:all .3s; }
      .ppw-step-it.active .ppw-dot { background:var(--brand-accent,#c6db53); color:#0f172a; border-color:var(--brand-accent,#c6db53); box-shadow:0 0 0 5px rgba(198,219,83,.18); }
      .ppw-step-it.completed .ppw-dot { background:#fff; color:#01643D; border-color:#fff; }
      .ppw-step-it.completed .ppw-dot span { display:none; }
      .ppw-step-it.completed .ppw-dot::before { content:'\\f00c'; font-family:'Font Awesome 5 Free'; font-weight:900; }
      .ppw-step-tx { display:flex; flex-direction:column; line-height:1.2; }
      .ppw-step-tx strong { font-weight:700; font-size:.92rem; }
      .ppw-step-tx small { font-size:.73rem; opacity:.7; font-weight:300; }
      .ppw-rail-foot { position:relative; z-index:2; font-size:.75rem; opacity:.6; font-weight:300; border-top:1px solid rgba(255,255,255,.12); padding-top:1.1rem; margin-top:1.2rem; display:flex; align-items:center; gap:.6rem; }
      .ppw-main { flex:1; display:flex; flex-direction:column; position:relative; background:rgba(255,255,255,.6); min-width:0; }
      .ppw-close { position:absolute; top:1.25rem; right:1.25rem; z-index:5; background:#f1f5f9; border:none; width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer; color:#64748b; transition:all .2s; }
      .ppw-close:hover { background:#e2e8f0; color:#0f172a; transform:rotate(90deg); }
      .ppw-body { padding:2.6rem 2.6rem 1rem; flex-grow:1; overflow-y:auto; max-height:66vh; }
      .ppw-eyebrow { font-size:.74rem; font-weight:800; text-transform:uppercase; letter-spacing:.1em; color:#01643D; }
      .ppw-title { font-weight:900; font-size:1.7rem; color:#0f172a; letter-spacing:-.03em; margin:.25rem 0 1.4rem; }
      .ppw-pane { display:none; animation:ppwSlide .35s cubic-bezier(.16,1,.3,1) forwards; }
      .ppw-pane.active { display:block; }
      @keyframes ppwSlide { from { opacity:0; transform:translateX(18px);} to { opacity:1; transform:translateX(0);} }
      .ppw-fld { margin-bottom:1.35rem; }
      .ppw-lbl { font-size:.82rem; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:#475569; margin-bottom:.55rem; display:flex; align-items:center; gap:.45rem; }
      .ppw-lbl i { color:#01643D; }
      .ppw-lbl .req { color:#dc2626; }
      .ppw-input { background:#fff; border:1px solid #cbd5e1; border-radius:1rem; padding:.85rem 1.1rem; font-size:.98rem; color:#0f172a; font-weight:600; transition:all .3s; box-shadow:inset 0 2px 4px rgba(0,0,0,.02); width:100%; }
      .ppw-input:focus { border-color:#01643D; box-shadow:0 0 0 4px rgba(1,100,61,.1); outline:none; }
      .ppw-pills { display:flex; flex-wrap:wrap; gap:.5rem; }
      .ppw-pill { border:1.5px solid #e2e8f0; background:#fff; color:#334155; border-radius:100px; padding:.55rem 1.05rem; font-weight:700; font-size:.86rem; cursor:pointer; transition:all .18s; line-height:1.2; }
      .ppw-pill:hover { border-color:#01643D; color:#01643D; }
      .ppw-pill.sel { background:#01643D; border-color:#01643D; color:#fff; box-shadow:0 6px 14px -6px rgba(1,100,61,.5); }
      .ppw-pill.sel::before { content:'\\f00c'; font-family:'Font Awesome 5 Free'; font-weight:900; margin-right:.45rem; font-size:.75rem; }
      .ppw-chips { display:flex; flex-wrap:wrap; gap:.5rem; }
      .ppw-chip { border:1.5px dashed #cbd5e1; background:#f8fafc; color:#475569; border-radius:100px; padding:.5rem 1rem; font-weight:600; font-size:.85rem; cursor:pointer; transition:all .18s; }
      .ppw-chip:hover { border-color:#01643D; color:#01643D; background:#f0fdf4; }
      .ppw-chip.sel { border-style:solid; background:rgba(1,100,61,.08); border-color:rgba(1,100,61,.4); color:#01643D; font-weight:700; }
      .ppw-chip.sel::before { content:'\\f00c'; font-family:'Font Awesome 5 Free'; font-weight:900; margin-right:.4rem; font-size:.72rem; }
      .ppw-hint { font-size:.78rem; color:#64748b; margin-top:.5rem; font-weight:500; }
      .ppw-aviso { background:#f8fafc; border:1px solid #e2e8f0; border-radius:1.25rem; padding:1.25rem 1.4rem; font-size:.84rem; color:#475569; line-height:1.55; }
      .ppw-aviso-head { display:flex; align-items:center; gap:.5rem; font-weight:800; color:#0f172a; margin-bottom:.5rem; }
      .ppw-aviso-head i { color:#01643D; }
      .ppw-acepto { display:flex; align-items:center; gap:.65rem; margin-top:1rem; padding:.8rem 1rem; background:#fff; border:1.5px solid #e2e8f0; border-radius:1rem; cursor:pointer; font-weight:700; color:#0f172a; transition:all .2s; }
      .ppw-acepto:hover { border-color:#01643D; }
      .ppw-acepto input { width:1.15rem; height:1.15rem; accent-color:#01643D; }
      .ppw-foot { padding:1.1rem 2.6rem; background:rgba(255,255,255,.9); border-top:1px solid rgba(0,0,0,.05); display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap; }
      .ppw-counter { font-size:.82rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.05em; }
      .ppw-err { display:none; align-items:center; gap:.5rem; color:#b91c1c; background:#fef2f2; border:1px solid #fecaca; border-radius:100px; padding:.45rem 1rem; font-size:.82rem; font-weight:700; }
      .ppw-btn-sec { background:#fff; border:1px solid #e2e8f0; color:#334155; font-weight:800; border-radius:100px; padding:.7rem 1.4rem; transition:all .2s; }
      .ppw-btn-sec:hover { background:#f8fafc; border-color:#cbd5e1; }
      .ppw-btn-pri { background:#01643D; border:none; color:#fff; font-weight:800; border-radius:100px; padding:.7rem 1.8rem; box-shadow:inset 0 -3px 0 rgba(0,0,0,.1); transition:all .2s; display:inline-flex; align-items:center; gap:.5rem; }
      .ppw-btn-pri:hover { transform:translateY(-2px); box-shadow:inset 0 -3px 0 rgba(0,0,0,.1), 0 10px 20px -5px rgba(1,100,61,.4); }
      .ppw-btn-pri:disabled { opacity:.6; transform:none; }
      @media (max-width: 768px) {
        .ppw-shell { flex-direction:column; min-height:0; }
        .ppw-rail { flex-basis:auto; padding:1.4rem; }
        .ppw-rail-head { margin-bottom:1.2rem; }
        .ppw-steps { display:flex; overflow-x:auto; gap:1rem; padding-bottom:.4rem; }
        .ppw-step-it { flex-direction:column; text-align:center; padding:0; min-width:68px; gap:.35rem; }
        .ppw-step-it::after { display:none; }
        .ppw-step-tx small { display:none; }
        .ppw-rail-foot { display:none; }
        .ppw-body { padding:1.6rem; max-height:none; }
        .ppw-foot { padding:1rem 1.6rem; }
        .ppw-title { font-size:1.35rem; }
      }`;

    const modal = `
      <div class="modal fade" id="ppPrepostModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-centered">
          <div class="modal-content ppw-content">
            <div class="ppw-shell">
              <aside class="ppw-rail">
                <div class="ppw-rail-head">
                  <div class="ppw-rail-icon"><i class="fas fa-paper-plane"></i></div>
                  <div>
                    <h5 class="ppw-rail-title">Prepostulación</h5>
                    <p class="ppw-rail-sub">Cuéntale tu perfil a <span id="ppwEmpresa">la empresa</span></p>
                  </div>
                </div>
                <ul class="ppw-steps" id="ppwRail"></ul>
                <div class="ppw-rail-foot">
                  <i class="fas fa-shield-alt"></i>
                  <span>Tu información se usa solo para la preselección de esta vacante.</span>
                </div>
              </aside>
              <div class="ppw-main">
                <button type="button" class="ppw-close" data-bs-dismiss="modal"><i class="fas fa-times"></i></button>
                <div class="ppw-body" id="ppwBody"></div>
                <div class="ppw-foot">
                  <span class="ppw-counter" id="ppwCounter"></span>
                  <span class="ppw-err" id="ppwErr"><i class="fas fa-exclamation-circle"></i><span id="ppwErrTx"></span></span>
                  <div class="d-flex gap-2">
                    <button type="button" class="ppw-btn-sec" id="ppwPrev"><i class="fas fa-arrow-left me-1"></i> Anterior</button>
                    <button type="button" class="ppw-btn-pri" id="ppwNext"></button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>`;

    $("head").append(`<style id="ppw-styles">${css}</style>`);
    $("body").append(modal);

    // Navegación
    $(document).on("click", "#ppwPrev", () => this.gotoPrepostStep(this._prepostStep - 1));
    $(document).on("click", "#ppwNext", () => {
      const last = this.prepostStepsDef().length - 1;
      if (!this.validatePrepostStep(this._prepostStep)) return;
      if (this._prepostStep >= last) this.submitPrepostulacion();
      else this.gotoPrepostStep(this._prepostStep + 1);
    });
    // Pills (selección única) y chips (multi)
    $(document).on("click", "#ppPrepostModal .ppw-pill", function () {
      $(this).siblings(".ppw-pill").removeClass("sel");
      $(this).addClass("sel");
    });
    $(document).on("click", "#ppPrepostModal .ppw-chip", function () {
      $(this).toggleClass("sel");
    });
  }

  /** Construye (o reconstruye) los pasos del wizard y precarga la licenciatura. */
  buildPrepostSteps() {
    const steps = this.prepostStepsDef();
    const programa = ($(CONFIG.SELECTORS.PROGRAMA).val() || "").trim();

    $("#ppwRail").html(steps.map((s, i) => `
      <li class="ppw-step-it" data-step="${i}">
        <span class="ppw-dot"><span>${i + 1}</span></span>
        <span class="ppw-step-tx"><strong>${s.title}</strong><small>${s.sub}</small></span>
      </li>`).join(""));

    const fieldHtml = (f) => {
      const req = f.req ? '<span class="req">*</span>' : "";
      const lbl = `<label class="ppw-lbl"><i class="${f.icon}"></i> ${f.label} ${req}</label>`;
      if (f.type === "text") {
        const val = f.k === "licenciatura" ? Utils.escape(programa) : "";
        return `<div class="ppw-fld">${lbl}<input type="text" class="ppw-input" data-k="${f.k}" value="${val}" placeholder="${f.ph || ""}"></div>`;
      }
      if (f.type === "pills") {
        return `<div class="ppw-fld">${lbl}<div class="ppw-pills" data-k="${f.k}">
          ${f.opts.map((o) => `<button type="button" class="ppw-pill" data-value="${Utils.escape(o)}">${Utils.escape(o)}</button>`).join("")}
        </div></div>`;
      }
      if (f.type === "chips") {
        return `<div class="ppw-fld">${lbl}<div class="ppw-chips" data-k="${f.k}">
          ${f.opts.map((o) => `<button type="button" class="ppw-chip" data-value="${Utils.escape(o)}">${Utils.escape(o)}</button>`).join("")}
        </div>${f.hint ? `<div class="ppw-hint"><i class="fas fa-info-circle me-1"></i>${f.hint}</div>` : ""}</div>`;
      }
      if (f.type === "aviso") {
        return `<div class="ppw-aviso">
            <div class="ppw-aviso-head"><i class="fas fa-shield-alt"></i> Aviso legal</div>
            La información proporcionada será utilizada exclusivamente para fines de preselección. La empresa evaluará el perfil del candidato y, en caso de que cumpla con los requisitos de la vacante, programará una entrevista a través de la plataforma de Universidad Montrer. La entrevista podrá llevarse a cabo de manera presencial o virtual, de acuerdo con las necesidades de la organización receptora.
            <label class="ppw-acepto"><input type="checkbox" id="ppwAcepto"> He leído y acepto el aviso.</label>
          </div>`;
      }
      return "";
    };

    $("#ppwBody").html(steps.map((s, i) => `
      <div class="ppw-pane" data-pane="${i}">
        <div class="ppw-eyebrow">Paso ${i + 1} de ${steps.length}</div>
        <h3 class="ppw-title">${s.title}</h3>
        ${s.fields.map(fieldHtml).join("")}
      </div>`).join(""));
  }

  gotoPrepostStep(n) {
    const steps = this.prepostStepsDef();
    this._prepostStep = Math.max(0, Math.min(n, steps.length - 1));
    const i = this._prepostStep;

    $("#ppPrepostModal .ppw-pane").removeClass("active");
    $(`#ppPrepostModal .ppw-pane[data-pane="${i}"]`).addClass("active");
    $("#ppwRail .ppw-step-it").each(function () {
      const s = parseInt($(this).data("step"), 10);
      $(this).toggleClass("active", s === i).toggleClass("completed", s < i);
    });
    $("#ppwPrev").toggle(i > 0);
    $("#ppwNext").html(i === steps.length - 1
      ? '<i class="fas fa-paper-plane"></i> Enviar prepostulación'
      : 'Siguiente <i class="fas fa-arrow-right ms-1"></i>');
    $("#ppwCounter").text(`Paso ${i + 1} de ${steps.length}`);
    this.hidePrepostError();
    $("#ppwBody").scrollTop(0);
  }

  showPrepostError(msg) {
    $("#ppwErrTx").text(msg);
    $("#ppwErr").css("display", "inline-flex");
  }
  hidePrepostError() {
    $("#ppwErr").hide();
  }

  /** Valida los campos requeridos del paso visible. */
  validatePrepostStep(i) {
    const step = this.prepostStepsDef()[i];
    for (const f of step.fields) {
      if (!f.req) continue;
      if (f.type === "text") {
        const v = ($(`#ppPrepostModal .ppw-input[data-k="${f.k}"]`).val() || "").trim();
        if (!v) { this.showPrepostError(`Completa: ${f.label}.`); return false; }
      } else if (f.type === "pills") {
        if (!$(`#ppPrepostModal .ppw-pills[data-k="${f.k}"] .ppw-pill.sel`).length) {
          this.showPrepostError(`Elige una opción en: ${f.label}.`); return false;
        }
      } else if (f.type === "aviso") {
        if (!$("#ppwAcepto").is(":checked")) {
          this.showPrepostError("Debes leer y aceptar el aviso legal para continuar."); return false;
        }
      }
    }
    this.hidePrepostError();
    return true;
  }

  /** Recolecta las respuestas del wizard (mismo contrato que el backend espera). */
  collectPrepostForm() {
    for (let i = 0; i < this.prepostStepsDef().length; i++) {
      if (!this.validatePrepostStep(i)) { this.gotoPrepostStep(i); return false; }
    }
    const $m = $("#ppPrepostModal");
    const data = {};
    $m.find(".ppw-input[data-k]").each(function () {
      const k = $(this).data("k");
      const v = ($(this).val() || "").trim();
      if (k === "licenciatura") data.licenciatura = v;
      else if (k === "herramientas_otro") data.herramientas_otro = v;
      else if (k === "horario_propuesto") data.horario_propuesto = v;
      else data[k] = v;
    });
    $m.find(".ppw-pills[data-k]").each(function () {
      data[$(this).data("k")] = $(this).find(".ppw-pill.sel").data("value") || "";
    });
    data.herramientas = $m.find('.ppw-chips[data-k="herramientas"] .ppw-chip.sel')
      .map(function () { return $(this).data("value"); }).get();
    return data;
  }

  prepostSuccess() {
    Swal.fire({
      title: "¡Prepostulación enviada!",
      html: "La empresa revisará tu perfil. Si cumples con los requisitos, programará una entrevista y te avisaremos por correo.<br><br><strong>Recuerda:</strong> mientras esperas su respuesta no podrás postularte a otra vacante.",
      icon: "success",
    });
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
