/**
 * incidencias.js — Panel de seguimiento de incidencias de practicantes (admin).
 *
 * Consume `controller/ajax/ajax.forms.php` con search: 'incidencias'.
 * Permite leer cada reporte, escribir al alumno o a la empresa, agendar juntas
 * (virtuales o presenciales) y mover el caso entre pendiente / en proceso /
 * atendida (con la solución registrada).
 */
(function () {
  "use strict";

  const ENDPOINT = "controller/ajax/ajax.forms.php";

  const TIPOS = {
    inasistencias: "Faltas o retardos",
    conducta: "Conducta o actitud inadecuada",
    desempeno: "Bajo desempeño en sus actividades",
    incumplimiento: "Incumplimiento de reglas o políticas",
    seguridad: "Riesgo de seguridad o daño",
    otro: "Otro",
  };
  const TIPO_ICONOS = {
    inasistencias: "fas fa-calendar-times",
    conducta: "fas fa-comment-slash",
    desempeno: "fas fa-chart-line",
    incumplimiento: "fas fa-ban",
    seguridad: "fas fa-triangle-exclamation",
    otro: "fas fa-ellipsis",
  };
  const GRAVEDADES = { alta: "Grave", media: "Media", baja: "Leve" };
  const ACCIONES = {
    orientacion: "Pide orientar al alumno",
    reunion: "Pide reunión de las 3 partes",
    baja: "Solicita la BAJA del practicante",
  };
  const ESTADOS = [
    { id: 0, key: "pendientes", label: "Pendientes", color: "#dc2626", bg: "#fef2f2", icon: "fas fa-inbox" },
    { id: 1, key: "en_proceso", label: "En proceso", color: "#d97706", bg: "#fffbeb", icon: "fas fa-spinner" },
    { id: 2, key: "atendidas", label: "Atendidas", color: "#01643D", bg: "#f0fdf4", icon: "fas fa-circle-check" },
  ];

  const state = {
    items: [],
    resumen: {},
    filtro: { estado: "todos", q: "", empresa: "", tipo: "", gravedad: "", soloBajas: false },
    detalle: null,
  };

  /* ── Utilidades ─────────────────────────────────────────────────────── */

  function esc(s) {
    return String(s == null ? "" : s).replace(/[&<>"']/g, (c) =>
      ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c])
    );
  }

  function fmtFecha(f, conHora) {
    if (!f) return "";
    const d = new Date(String(f).replace(" ", "T"));
    if (isNaN(d)) return String(f);
    const fecha = d.toLocaleDateString("es-MX", { day: "2-digit", month: "2-digit", year: "numeric" });
    if (!conHora) return fecha;
    return fecha + " " + d.toLocaleTimeString("es-MX", { hour: "2-digit", minute: "2-digit" });
  }

  function post(data) {
    return $.ajax({ url: ENDPOINT, method: "POST", data: $.extend({ search: "incidencias" }, data), dataType: "json" });
  }

  function toast(icon, title, text) {
    Swal.fire({ icon, title, text, confirmButtonColor: "#01643D" });
  }

  function badge(text, color, bg, icon) {
    return `<span class="inc-badge" style="background:${bg};color:${color};">${icon ? `<i class="${icon}"></i>` : ""}${esc(text)}</span>`;
  }

  /* ── Carga y filtros ────────────────────────────────────────────────── */

  function load() {
    post({ action: "getIncidencias" })
      .done((r) => {
        if (!r || r.success === false) {
          $("#incBloques").html(
            `<div class="empty-state"><i class="fas fa-lock"></i><span>${esc((r && r.message) || "No fue posible cargar las incidencias.")}</span></div>`
          );
          return;
        }
        state.items = r.items || [];
        state.resumen = r.resumen || {};
        llenarEmpresas();
        renderResumen();
        renderBloques();
      })
      .fail(() => {
        $("#incBloques").html('<div class="empty-state"><i class="fas fa-triangle-exclamation"></i><span>Error de conexión al cargar las incidencias.</span></div>');
      });
  }

  function llenarEmpresas() {
    const $sel = $("#incEmpresa");
    const actual = $sel.val();
    const empresas = [...new Set(state.items.map((i) => i.empresa).filter(Boolean))].sort();
    $sel.html('<option value="">Todas las empresas</option>' + empresas.map((e) => `<option value="${esc(e)}">${esc(e)}</option>`).join(""));
    if (actual && empresas.includes(actual)) $sel.val(actual);
  }

  function itemsFiltrados() {
    const f = state.filtro;
    const q = f.q.trim().toLowerCase();
    return state.items.filter((i) => {
      if (f.estado !== "todos" && Number(i.status) !== Number(f.estado)) return false;
      if (f.empresa && i.empresa !== f.empresa) return false;
      if (f.tipo && i.tipo !== f.tipo) return false;
      if (f.gravedad && i.gravedad !== f.gravedad) return false;
      if (f.soloBajas && i.accion_solicitada !== "baja") return false;
      if (q) {
        const blob = [i.nombre_completo, i.empresa, i.descripcion, i.student_matricula, i.acciones_tomadas, i.solucion]
          .join(" ")
          .toLowerCase();
        if (!blob.includes(q)) return false;
      }
      return true;
    });
  }

  /* ── Render: resumen ────────────────────────────────────────────────── */

  function renderResumen() {
    const r = state.resumen;
    const tarjetas = [
      { key: "todos", label: "Todas", value: r.total || 0, color: "#334155", bg: "#f1f5f9", icon: "fas fa-layer-group" },
      ...ESTADOS.map((e) => ({ key: String(e.id), label: e.label, value: r[e.key] || 0, color: e.color, bg: e.bg, icon: e.icon })),
    ];
    const bajas = r.bajas_abiertas || 0;

    $("#incResumen").html(
      tarjetas
        .map(
          (t) => `
        <button type="button" class="inc-kpi${state.filtro.estado === t.key ? " active" : ""}"
                data-estado="${t.key}" style="--kc:${t.color};--kbg:${t.bg};">
          <div class="inc-kpi-lbl"><i class="${t.icon}"></i> ${t.label}</div>
          <div class="inc-kpi-val">${t.value}</div>
          ${t.key === "0" && bajas ? `<div style="font-size:.72rem;font-weight:800;color:#dc2626;margin-top:.2rem;">${bajas} pide${bajas === 1 ? "" : "n"} baja</div>` : ""}
        </button>`
        )
        .join("")
    );
  }

  /* ── Render: bloques y tarjetas ─────────────────────────────────────── */

  function renderCard(i) {
    const pideBaja = i.accion_solicitada === "baja";
    const grav = i.gravedad || "media";
    const gravColor = grav === "alta" ? "#dc2626" : grav === "media" ? "#b45309" : "#0369a1";
    const gravBg = grav === "alta" ? "#fef2f2" : grav === "media" ? "#fffbeb" : "#f0f9ff";
    const desc = String(i.descripcion || "");
    const recorte = desc.length > 260 ? desc.slice(0, 260) + "…" : desc;

    return `
      <div class="inc-card g-${esc(grav)}" data-id="${esc(i.idIncidencia)}">
        <div class="inc-card-top">
          <div style="min-width:0;flex:1 1 320px;">
            <div class="inc-alumno">${esc(i.nombre_completo || "Alumno")} <span style="font-weight:700;color:#94a3b8;font-size:.85rem;">#${esc(i.idIncidencia)}</span></div>
            <div class="inc-meta">
              <span><i class="fas fa-building me-1"></i>${esc(i.empresa || "—")}</span>
              <span><i class="fas fa-id-card me-1"></i>${esc(i.student_matricula || "—")}</span>
              <span><i class="far fa-clock me-1"></i>Reportado el ${esc(fmtFecha(i.dateCreated, true))}</span>
            </div>
          </div>
          <div class="d-flex gap-2 flex-wrap align-items-start">
            ${badge(TIPOS[i.tipo] || i.tipo, "#334155", "#f1f5f9", TIPO_ICONOS[i.tipo] || "fas fa-tag")}
            ${badge(GRAVEDADES[grav] || grav, gravColor, gravBg, "fas fa-signal")}
            ${pideBaja ? badge("Solicita baja", "#fff", "#dc2626", "fas fa-user-minus") : ""}
          </div>
        </div>
        <div class="inc-desc">${esc(recorte).replace(/\r?\n/g, "<br>")}</div>
        ${
          Number(i.status) === 2 && i.solucion
            ? `<div class="inc-desc" style="background:#f0fdf4;border-color:#bbf7d0;color:#166534;"><b>Solución:</b> ${esc(i.solucion).replace(/\r?\n/g, "<br>")}</div>`
            : ""
        }
        <div class="inc-acts">
          <button type="button" class="inc-btn inc-btn-pri btn-inc-detalle" data-id="${esc(i.idIncidencia)}">
            <i class="fas fa-folder-open me-1"></i> Abrir y atender
          </button>
          ${
            Number(i.status) === 0
              ? `<button type="button" class="inc-btn inc-btn-warn btn-inc-proceso" data-id="${esc(i.idIncidencia)}"><i class="fas fa-play me-1"></i> Tomar el caso</button>`
              : ""
          }
          ${
            Number(i.status) !== 2
              ? `<button type="button" class="inc-btn btn-inc-cerrar" data-id="${esc(i.idIncidencia)}"><i class="fas fa-circle-check me-1"></i> Marcar atendida</button>`
              : ""
          }
          ${Number(i.num_juntas) > 0 ? `<span class="inc-btn" style="cursor:default;"><i class="fas fa-handshake me-1 text-success"></i>${i.num_juntas} junta(s)</span>` : ""}
          ${Number(i.num_mensajes) > 0 ? `<span class="inc-btn" style="cursor:default;"><i class="fas fa-envelope me-1 text-primary"></i>${i.num_mensajes} mensaje(s)</span>` : ""}
        </div>
      </div>`;
  }

  function renderBloques() {
    const items = itemsFiltrados();
    const $cont = $("#incBloques");

    if (!state.items.length) {
      $cont.html('<div class="empty-state"><i class="fa-regular fa-circle-check"></i><span>No hay incidencias reportadas. ¡Buenas noticias!</span></div>');
      return;
    }
    if (!items.length) {
      $cont.html('<div class="empty-state"><i class="fas fa-filter-circle-xmark"></i><span>Ninguna incidencia coincide con los filtros seleccionados.</span></div>');
      return;
    }

    const html = ESTADOS.map((e) => {
      const grupo = items.filter((i) => Number(i.status) === e.id);
      if (!grupo.length) return "";
      return `
        <div class="inc-bloque">
          <div class="inc-bloque-head">
            <span class="inc-dot" style="background:${e.color};"></span>
            <span>${e.label}</span>
            <span class="inc-num">${grupo.length}</span>
          </div>
          ${grupo.map(renderCard).join("")}
        </div>`;
    }).join("");

    $cont.html(html);
  }

  /* ── Detalle ────────────────────────────────────────────────────────── */

  /**
   * Instancia del modal de detalle con `focus: false`.
   * Con el focus trap activo, Bootstrap devuelve el foco al modal y no se
   * puede escribir en los campos de los diálogos de SweetAlert que se abren
   * encima (mensaje, junta, solución).
   */
  function modalDetalle() {
    const el = document.getElementById("incDetalleModal");
    return bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el, { focus: false });
  }

  function abrirDetalle(id) {
    const modal = modalDetalle();
    $("#incDetBody").html('<div class="empty-state"><i class="fas fa-spinner fa-spin"></i><span>Cargando…</span></div>');
    $("#incDetFooter").html("");
    modal.show();

    post({ action: "getIncidencia", idIncidencia: id })
      .done((d) => {
        if (!d || !d.idIncidencia) {
          $("#incDetBody").html('<div class="empty-state"><i class="fas fa-triangle-exclamation"></i><span>No se encontró la incidencia.</span></div>');
          return;
        }
        state.detalle = d;
        renderDetalle(d);
      })
      .fail(() => {
        $("#incDetBody").html('<div class="empty-state"><i class="fas fa-triangle-exclamation"></i><span>Error de conexión.</span></div>');
      });
  }

  function renderDetalle(d) {
    const estado = ESTADOS.find((e) => e.id === Number(d.status)) || ESTADOS[0];
    const pideBaja = d.accion_solicitada === "baja";

    $("#incDetTitulo").text("Incidencia #" + d.idIncidencia + " · " + (d.nombre_completo || ""));
    $("#incDetSub").html(
      `${esc(d.empresa || "")} · ${esc(TIPOS[d.tipo] || d.tipo)} · <span style="color:${estado.color};">${estado.label}</span>`
    );

    const item = (label, val, href) =>
      `<div class="inc-dl-item"><b>${esc(label)}</b>${
        href && val ? `<a href="${esc(href)}">${esc(val)}</a>` : `<span>${esc(val || "—")}</span>`
      }</div>`;

    const bitacora = []
      .concat(
        (d.juntas || []).map((j) => ({
          orden: j.created_at,
          icon: "fas fa-handshake",
          head: `Junta ${j.modalidad.toLowerCase()} · ${fmtFecha(j.fecha)} ${String(j.hora || "").slice(0, 5)}`,
          meta: `Agendada por ${j.admin_nombre || "el administrador"} el ${fmtFecha(j.created_at, true)} · Convocados: ${
            [j.invita_alumno == 1 ? "alumno" : null, j.invita_empresa == 1 ? "empresa" : null].filter(Boolean).join(" y ") || "nadie"
          }`,
          body: (j.modalidad === "Virtual" ? "Enlace: " + (j.url_sesion || "—") : "Lugar: " + (j.lugar || "—")) + (j.agenda ? "\n" + j.agenda : ""),
        }))
      )
      .concat(
        (d.mensajes || []).map((m) => ({
          orden: m.created_at,
          icon: "fas fa-envelope",
          head: `Mensaje a ${m.destinatario === "ambos" ? "alumno y empresa" : m.destinatario} · ${m.asunto}`,
          meta: `Enviado por ${m.admin_nombre || "el administrador"} el ${fmtFecha(m.created_at, true)} a ${m.enviado_a || "—"}`,
          body: m.mensaje,
        }))
      )
      .sort((a, b) => String(b.orden).localeCompare(String(a.orden)));

    $("#incDetBody").html(`
      ${
        pideBaja
          ? '<div class="ic-neo-banner" style="background:#fef2f2;border-color:#fecaca;color:#991b1b;"><i class="fas fa-user-minus" style="color:#dc2626;"></i><div>La empresa <strong>solicita la baja del practicante</strong>. Revisa el caso y comunícate con ambas partes antes de autorizarla.</div></div>'
          : ""
      }

      <div class="inc-sec"><i class="fas fa-circle-info"></i> Datos del reporte</div>
      <div class="inc-dl">
        ${item("Tipo de incidencia", TIPOS[d.tipo] || d.tipo)}
        ${item("Gravedad", GRAVEDADES[d.gravedad] || d.gravedad)}
        ${item("Qué pide la empresa", ACCIONES[d.accion_solicitada] || d.accion_solicitada)}
        ${item("Fecha del incidente", d.fecha_incidente ? fmtFecha(d.fecha_incidente) : "No especificada")}
        ${item("Reporte levantado", fmtFecha(d.dateCreated, true))}
        ${item("Estado actual", estado.label)}
        ${item("Se comenzó a atender", d.fecha_atencion ? fmtFecha(d.fecha_atencion, true) : "—")}
        ${item("Finalizado", d.fecha_cierre ? fmtFecha(d.fecha_cierre, true) : "—")}
      </div>

      <div class="inc-sec"><i class="fas fa-comment-dots"></i> Qué ocurrió</div>
      <div class="inc-desc" style="margin-top:0;">${esc(d.descripcion || "").replace(/\r?\n/g, "<br>")}</div>
      ${
        d.acciones_tomadas
          ? `<div class="inc-sec"><i class="fas fa-screwdriver-wrench"></i> Acciones que ya tomó la empresa</div>
             <div class="inc-desc" style="margin-top:0;">${esc(d.acciones_tomadas).replace(/\r?\n/g, "<br>")}</div>`
          : ""
      }
      ${
        d.solucion
          ? `<div class="inc-sec"><i class="fas fa-circle-check"></i> Solución registrada</div>
             <div class="inc-desc" style="margin-top:0;background:#f0fdf4;border-color:#bbf7d0;color:#166534;">${esc(d.solucion).replace(/\r?\n/g, "<br>")}</div>`
          : ""
      }

      <div class="inc-sec"><i class="fas fa-user-graduate"></i> Contacto del alumno</div>
      <div class="inc-dl">
        ${item("Nombre", d.nombre_completo)}
        ${item("Matrícula", d.student_matricula)}
        ${item("Correo", d.student_email, "mailto:" + (d.student_email || ""))}
        ${item("Teléfono", d.student_telefono, "tel:" + (d.student_telefono || ""))}
        ${item("Programa académico", d.programa_academico)}
        ${item("Actividades en la empresa", d.actividades)}
      </div>

      <div class="inc-sec"><i class="fas fa-building"></i> Contacto de la empresa</div>
      <div class="inc-dl">
        ${item("Empresa", d.empresa)}
        ${item("Persona de contacto", d.nombre_contacto)}
        ${item("Correo", d.org_email, "mailto:" + (d.org_email || ""))}
        ${item("Teléfono", d.org_telefono, "tel:" + (d.org_telefono || ""))}
        ${item("Celular", d.org_celular, "tel:" + (d.org_celular || ""))}
      </div>

      <div class="inc-sec"><i class="fas fa-clock-rotate-left"></i> Bitácora del seguimiento</div>
      ${
        bitacora.length
          ? `<div class="inc-time">${bitacora
              .map(
                (b) => `
            <div class="inc-time-item">
              <div class="t-head"><i class="${b.icon} me-1" style="color:#01643D;"></i>${esc(b.head)}</div>
              <div class="t-meta">${esc(b.meta)}</div>
              <div class="t-body">${esc(b.body)}</div>
            </div>`
              )
              .join("")}</div>`
          : '<div class="inc-desc" style="margin-top:0;">Todavía no hay comunicaciones ni juntas registradas en este caso.</div>'
      }
    `);

    $("#incDetFooter").html(`
      <button type="button" class="ic-neo-btn-ghost btn-inc-msg" data-dest="alumno"><i class="fas fa-envelope me-1"></i> Escribir al alumno</button>
      <button type="button" class="ic-neo-btn-ghost btn-inc-msg" data-dest="empresa"><i class="fas fa-envelope me-1"></i> Escribir a la empresa</button>
      <button type="button" class="ic-neo-btn-ghost btn-inc-junta"><i class="fas fa-handshake me-1"></i> Agendar junta</button>
      ${
        Number(d.status) === 0
          ? '<button type="button" class="ic-neo-btn btn-inc-proceso" style="background:#d97706;"><i class="fas fa-play"></i> Tomar el caso</button>'
          : ""
      }
      ${
        Number(d.status) !== 2
          ? '<button type="button" class="ic-neo-btn btn-inc-cerrar" style="background:#01643D;"><i class="fas fa-circle-check"></i> Marcar como atendida</button>'
          : '<button type="button" class="ic-neo-btn-ghost btn-inc-reabrir"><i class="fas fa-rotate-left me-1"></i> Reabrir caso</button>'
      }
    `);
  }

  /* ── Acciones ───────────────────────────────────────────────────────── */

  function cambiarEstado(id, status, solucion) {
    return post({ action: "updateEstado", idIncidencia: id, status: status, solucion: solucion || "" })
      .done((r) => {
        if (r && r.success) {
          toast("success", "Listo", r.message || "Estado actualizado.");
          load();
          if (state.detalle && Number(state.detalle.idIncidencia) === Number(id)) abrirDetalle(id);
        } else {
          toast("error", "Error", (r && r.message) || "No se pudo actualizar el estado.");
        }
      })
      .fail(() => toast("error", "Error", "Error de conexión."));
  }

  function pedirSolucion(id) {
    Swal.fire({
      title: "Marcar como atendida",
      html: `
        <p class="text-muted" style="font-size:.9rem;">Se enviará un correo a la empresa y al alumno con la solución que escribas aquí.</p>
        <textarea id="inc-solucion" class="form-control" rows="5"
          placeholder="Ejemplo: Se realizó una junta el 12/03. El alumno se comprometió a cubrir sus horas y la empresa aceptó darle una segunda oportunidad."></textarea>`,
      showCancelButton: true,
      confirmButtonText: "Guardar y notificar",
      cancelButtonText: "Cancelar",
      confirmButtonColor: "#01643D",
      preConfirm: () => {
        const v = document.getElementById("inc-solucion").value.trim();
        if (v.length < 15) {
          Swal.showValidationMessage("Describe la solución con al menos 15 caracteres.");
          return false;
        }
        return v;
      },
    }).then((res) => {
      if (res.isConfirmed) cambiarEstado(id, 2, res.value);
    });
  }

  function dialogoMensaje(id, destPre) {
    const d = state.detalle || {};
    const sel = (v, l, sub) =>
      `<option value="${v}"${v === destPre ? " selected" : ""}>${l}${sub ? " — " + sub : ""}</option>`;

    const PH_ALUMNO =
      "Ejemplo: Recibimos un reporte de la empresa por tus inasistencias. Te pedimos presentarte en el área de Prácticas Profesionales esta semana para conocer tu versión y definir un plan de regularización.";
    const PH_EMPRESA =
      "Ejemplo: Agradecemos su reporte. Ya contactamos al practicante y le solicitamos una explicación por escrito. Le informaremos del resultado en un plazo de tres días hábiles.";

    Swal.fire({
      title: "Escribir comunicado",
      width: 680,
      html: `
        <div class="text-start">
          <label class="form-label fw-bold">¿A quién le escribes?</label>
          <select id="inc-dest" class="form-select mb-3">
            ${sel("alumno", "Al alumno", d.student_email || "sin correo")}
            ${sel("empresa", "A la empresa", d.org_email || "sin correo")}
            ${sel("ambos", "A ambos (un mensaje para cada uno)", "")}
          </select>

          <label class="form-label fw-bold">Asunto</label>
          <input id="inc-asunto" class="form-control mb-3" maxlength="180"
                 value="Seguimiento del reporte de incidencia">

          <div id="inc-msg-uno">
            <label class="form-label fw-bold" id="inc-msg-uno-lbl">Mensaje</label>
            <textarea id="inc-mensaje" class="form-control" rows="6" maxlength="4000"></textarea>
          </div>

          <div id="inc-msg-dos" style="display:none;">
            <label class="form-label fw-bold"><i class="fas fa-user-graduate me-1 text-success"></i> Mensaje para el alumno</label>
            <textarea id="inc-mensaje-alumno" class="form-control mb-3" rows="5" maxlength="4000"
              placeholder="${PH_ALUMNO}"></textarea>
            <label class="form-label fw-bold"><i class="fas fa-building me-1 text-primary"></i> Mensaje para la empresa</label>
            <textarea id="inc-mensaje-empresa" class="form-control" rows="5" maxlength="4000"
              placeholder="${PH_EMPRESA}"></textarea>
          </div>

          <small class="text-muted d-block mt-2">
            Cada parte recibe un correo institucional independiente, con el encabezado y los datos que le corresponden.
            Todo queda guardado en la bitácora del caso.
          </small>
        </div>`,
      showCancelButton: true,
      confirmButtonText: "Enviar comunicado",
      cancelButtonText: "Cancelar",
      confirmButtonColor: "#01643D",
      didOpen: () => {
        const dest = document.getElementById("inc-dest");
        const uno = document.getElementById("inc-msg-uno");
        const dos = document.getElementById("inc-msg-dos");
        const lbl = document.getElementById("inc-msg-uno-lbl");
        const txt = document.getElementById("inc-mensaje");
        const toggle = () => {
          const v = dest.value;
          uno.style.display = v === "ambos" ? "none" : "block";
          dos.style.display = v === "ambos" ? "block" : "none";
          lbl.textContent = v === "empresa" ? "Mensaje para la empresa" : "Mensaje para el alumno";
          txt.placeholder = v === "empresa" ? PH_EMPRESA : PH_ALUMNO;
        };
        dest.addEventListener("change", toggle);
        toggle();
      },
      preConfirm: () => {
        const destinatario = document.getElementById("inc-dest").value;
        const asunto = document.getElementById("inc-asunto").value.trim();
        if (asunto.length < 4) {
          Swal.showValidationMessage("Escribe un asunto.");
          return false;
        }
        if (destinatario === "ambos") {
          const mAlumno = document.getElementById("inc-mensaje-alumno").value.trim();
          const mEmpresa = document.getElementById("inc-mensaje-empresa").value.trim();
          if (mAlumno.length < 15) {
            Swal.showValidationMessage("El mensaje para el alumno debe tener al menos 15 caracteres.");
            return false;
          }
          if (mEmpresa.length < 15) {
            Swal.showValidationMessage("El mensaje para la empresa debe tener al menos 15 caracteres.");
            return false;
          }
          return { destinatario, asunto, mensaje_alumno: mAlumno, mensaje_empresa: mEmpresa };
        }
        const mensaje = document.getElementById("inc-mensaje").value.trim();
        if (mensaje.length < 15) {
          Swal.showValidationMessage("El mensaje debe tener al menos 15 caracteres.");
          return false;
        }
        return { destinatario, asunto, mensaje };
      },
    }).then((res) => {
      if (!res.isConfirmed) return;
      post($.extend({ action: "enviarMensaje", idIncidencia: id }, res.value))
        .done((r) => {
          if (r && r.success) {
            toast("success", "Mensaje enviado", r.message || "");
            abrirDetalle(id);
            load();
          } else {
            toast("error", "Error", (r && r.message) || "No se pudo enviar el mensaje.");
          }
        })
        .fail(() => toast("error", "Error", "Error de conexión."));
    });
  }

  function dialogoJunta(id) {
    const hoy = new Date().toISOString().split("T")[0];

    Swal.fire({
      title: "Agendar junta de seguimiento",
      width: 660,
      html: `
        <div class="text-start">
          <label class="form-label fw-bold">Modalidad</label>
          <select id="inc-j-mod" class="form-select mb-3">
            <option value="Presencial">Presencial — en la Universidad o en la empresa</option>
            <option value="Virtual">Virtual — por Meet o Teams</option>
          </select>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label fw-bold">Fecha</label>
              <input type="date" id="inc-j-fecha" class="form-control" min="${hoy}" value="${hoy}">
            </div>
            <div class="col-6">
              <label class="form-label fw-bold">Hora</label>
              <input type="time" id="inc-j-hora" class="form-control" value="10:00">
            </div>
          </div>
          <div id="inc-j-lugar-wrap" class="mb-3">
            <label class="form-label fw-bold">Lugar</label>
            <input id="inc-j-lugar" class="form-control" placeholder="Ej. Sala de juntas, Universidad Montrer">
          </div>
          <div id="inc-j-url-wrap" class="mb-3" style="display:none;">
            <label class="form-label fw-bold">Enlace de la sesión</label>
            <input id="inc-j-url" class="form-control" placeholder="https://meet.google.com/…">
          </div>
          <label class="form-label fw-bold">Puntos a tratar (opcional)</label>
          <textarea id="inc-j-agenda" class="form-control mb-3" rows="3"
            placeholder="Ejemplo: Escuchar la versión del alumno, acordar un plan de recuperación de horas y definir compromisos."></textarea>
          <label class="form-label fw-bold">¿A quién convocas?</label>
          <div class="d-flex gap-3 mt-1">
            <label class="d-flex align-items-center gap-2"><input type="checkbox" id="inc-j-alumno" checked> Alumno</label>
            <label class="d-flex align-items-center gap-2"><input type="checkbox" id="inc-j-empresa" checked> Empresa</label>
          </div>
        </div>`,
      showCancelButton: true,
      confirmButtonText: "Agendar y convocar",
      cancelButtonText: "Cancelar",
      confirmButtonColor: "#01643D",
      didOpen: () => {
        const mod = document.getElementById("inc-j-mod");
        const toggle = () => {
          const v = mod.value;
          document.getElementById("inc-j-url-wrap").style.display = v === "Virtual" ? "block" : "none";
          document.getElementById("inc-j-lugar-wrap").style.display = v === "Presencial" ? "block" : "none";
        };
        mod.addEventListener("change", toggle);
        toggle();
      },
      preConfirm: () => {
        const modalidad = document.getElementById("inc-j-mod").value;
        const fecha = document.getElementById("inc-j-fecha").value;
        const hora = document.getElementById("inc-j-hora").value;
        const url = document.getElementById("inc-j-url").value.trim();
        const lugar = document.getElementById("inc-j-lugar").value.trim();
        const agenda = document.getElementById("inc-j-agenda").value.trim();
        const invita_alumno = document.getElementById("inc-j-alumno").checked ? 1 : 0;
        const invita_empresa = document.getElementById("inc-j-empresa").checked ? 1 : 0;

        if (!fecha || !hora) {
          Swal.showValidationMessage("Indica la fecha y la hora.");
          return false;
        }
        if (modalidad === "Virtual" && !/^https?:\/\/.+/i.test(url)) {
          Swal.showValidationMessage("Indica el enlace de la sesión (debe iniciar con http).");
          return false;
        }
        if (modalidad === "Presencial" && !lugar) {
          Swal.showValidationMessage("Indica el lugar de la junta.");
          return false;
        }
        if (!invita_alumno && !invita_empresa) {
          Swal.showValidationMessage("Selecciona al menos un convocado.");
          return false;
        }
        return { modalidad, fecha, hora, url_sesion: url, lugar, agenda, invita_alumno, invita_empresa };
      },
    }).then((res) => {
      if (!res.isConfirmed) return;
      post($.extend({ action: "agendarJunta", idIncidencia: id }, res.value))
        .done((r) => {
          if (r && r.success) {
            toast("success", "Junta agendada", r.message || "");
            abrirDetalle(id);
            load();
          } else {
            toast("error", "Error", (r && r.message) || "No se pudo agendar la junta.");
          }
        })
        .fail(() => toast("error", "Error", "Error de conexión."));
    });
  }

  /* ── Eventos ────────────────────────────────────────────────────────── */

  function idActual(el) {
    return $(el).data("id") || (state.detalle && state.detalle.idIncidencia);
  }

  $(function () {
    // Filtros
    $("#incResumen").on("click", ".inc-kpi", function () {
      state.filtro.estado = String($(this).data("estado"));
      renderResumen();
      renderBloques();
    });
    $("#incBuscar").on("input", function () {
      state.filtro.q = this.value;
      renderBloques();
    });
    $("#incEmpresa, #incTipo, #incGravedad").on("change", function () {
      state.filtro.empresa = $("#incEmpresa").val();
      state.filtro.tipo = $("#incTipo").val();
      state.filtro.gravedad = $("#incGravedad").val();
      renderBloques();
    });
    $("#incSoloBajas").on("change", function () {
      state.filtro.soloBajas = this.checked;
      renderBloques();
    });
    $("#btnRefrescarIncidencias").on("click", load);

    // Tarjetas
    $("#incBloques")
      .on("click", ".btn-inc-detalle", function () {
        abrirDetalle($(this).data("id"));
      })
      .on("click", ".btn-inc-proceso", function () {
        cambiarEstado($(this).data("id"), 1);
      })
      .on("click", ".btn-inc-cerrar", function () {
        pedirSolucion($(this).data("id"));
      });

    // Modal de detalle
    $("#incDetFooter")
      .on("click", ".btn-inc-msg", function () {
        dialogoMensaje(idActual(this), $(this).data("dest"));
      })
      .on("click", ".btn-inc-junta", function () {
        dialogoJunta(idActual(this));
      })
      .on("click", ".btn-inc-proceso", function () {
        cambiarEstado(idActual(this), 1);
      })
      .on("click", ".btn-inc-cerrar", function () {
        pedirSolucion(idActual(this));
      })
      .on("click", ".btn-inc-reabrir", function () {
        cambiarEstado(idActual(this), 1);
      });

    load();
  });
})();
