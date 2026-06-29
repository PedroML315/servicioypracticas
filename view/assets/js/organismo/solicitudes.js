// 1) Declara un mapa global donde guardarás los prospects por solicitud
let prospectsMap = {};

document.addEventListener("DOMContentLoaded", function () {
  solicitudes();
  const input = document.getElementById("nombreResponsable");
  if (!input) return;
  input.addEventListener("input", function () {
    let value = this.value.toLowerCase().replace(/\b\w/g, function (l) {
      return l.toUpperCase();
    });
    this.value = value;
  });
});

document.addEventListener("DOMContentLoaded", bindPhoneFormatter);

function bindPhoneFormatter() {
  const inputs = [document.getElementById("contactoResponsable")];
  inputs.forEach((input) => {
    if (!input) return;
    input.addEventListener("input", function () {
      // 1) Sólo dígitos, máximo 14
      let v = this.value.replace(/\D/g, "").slice(0, 12);

      // 2) Si es internacional (>10 dígitos)
      if (v.length > 10) {
        // longitud del código de país = total - 10
        const ccLen = v.length - 10;
        const cc = v.slice(0, ccLen);
        const p1 = v.slice(ccLen, ccLen + 3);
        const p2 = v.slice(ccLen + 3, ccLen + 6);
        const p3 = v.slice(ccLen + 6);
        // +CC… CCC-CCC-CCCC
        this.value = `+${cc} ${p1}-${p2}-${p3}`.trim();
      }
      // 3) Nacional (hasta 10 dígitos)
      else if (v.length > 6) {
        // 3-3-4
        this.value = v.replace(/(\d{3})(\d{3})(\d{1,4})/, "$1-$2-$3");
      } else if (v.length > 3) {
        // 3-rest
        this.value = v.replace(/(\d{3})(\d{1,3})/, "$1-$2");
      } else {
        // menos de 4 dígitos, sin formato
        this.value = v;
      }
    });
  });
}

/**
 * Maneja la lógica de selección de días y horas para el horario propuesto.
 * – Día Inicio  → habilita Día Fin
 * – Día Fin     → habilita Hora Inicio
 * – Hora Inicio → calcula Hora Fin automáticamente (+4 h)
 */

// ─── Etiquetas de días ────────────────────────────────────────────────────
const DIA_LABELS = { L: "Lunes", M: "Martes", X: "Miércoles", J: "Jueves", V: "Viernes" };
const DIAS_ORDEN = ["", "L", "M", "X", "J", "V"];

// ─── Calcula hora fin = hora inicio + 4 horas ─────────────────────────────
function calcHoraFin(v) {
  if (!v) return "";
  const [hh, mm] = v.split(":").map(Number);
  const t = hh * 60 + mm + 240;
  return `${String(Math.floor(t / 60)).padStart(2, "0")}:${String(t % 60).padStart(2, "0")}`;
}

// ─── Actualiza step-pills ─────────────────────────────────────────────────
function updateSteps(pfx, dI, dF, hI, hF) {
  $(`#${pfx}step-diaInicio`).toggleClass("done", !!dI).toggleClass("active", !dI);
  $(`#${pfx}step-diaFin`).toggleClass("done", !!dF).toggleClass("active", !!dI && !dF);
  $(`#${pfx}step-horaInicio`).toggleClass("done", !!hI).toggleClass("active", !!dF && !hI);
  $(`#${pfx}step-horaFin`).toggleClass("done", !!hF);
}

// ─── Actualiza barra de preview ────────────────────────────────────────────
function updatePreview(previewId, textId, dI, dF, hI, hF) {
  if (dI && dF && hI && hF) {
    const lI = DIA_LABELS[dI] || dI, lF = DIA_LABELS[dF] || dF;
    const txt = lI === lF ? `${lI} &nbsp;·&nbsp; ${hI} – ${hF}` : `${lI} a ${lF} &nbsp;·&nbsp; ${hI} – ${hF}`;
    $(`#${textId}`).html(txt);
    $(`#${previewId}`).stop(true).fadeIn(220);
  } else {
    $(`#${previewId}`).stop(true).fadeOut(160);
  }
}

// ─── Función genérica de horario ──────────────────────────────────────────
function armarHorarioFor(ids, changed) {
  const $dI = $(`#${ids.diaInicio}`), $dF = $(`#${ids.diaFin}`);
  const $hI = $(`#${ids.horaInicio}`), $hF = $(`#${ids.horaFin}`);
  const $hFD = $(`#${ids.horaFinDisplay}`);

  if (changed === ids.diaInicio)  { $dF.val(""); $hI.val(""); $hF.val(""); $hFD.text("–").removeClass("has-value"); }
  if (changed === ids.diaFin)     { $hI.val(""); $hF.val(""); $hFD.text("–").removeClass("has-value"); }
  if (changed === ids.horaInicio) { $hF.val(""); $hFD.text("–").removeClass("has-value"); }

  const dI = $dI.val(), dF = $dF.val(), hI = $hI.val();
  $dF.prop("disabled", !dI);
  $hI.prop("disabled", !dF);
  $(`#${ids.wrapperDiaFin}`).toggleClass("unlocked", !!dI);
  $(`#${ids.wrapperHoraInicio}`).toggleClass("unlocked", !!dF);

  $dF.find("option").show();
  if (dI) {
    const idxI = DIAS_ORDEN.indexOf(dI);
    $dF.find("option").each(function () {
      const idx = DIAS_ORDEN.indexOf(this.value);
      if (idx !== 0 && idx <= idxI) $(this).hide();
    });
  }

  const hF = calcHoraFin(hI);
  if (hF) { $hF.val(hF); $hFD.text(hF).addClass("has-value"); }
  else     { $hF.val(""); $hFD.text("–").removeClass("has-value"); }

  updateSteps(ids.stepPrefix, dI, dF, hI, hF);
  updatePreview(ids.previewId, ids.previewTextId, dI, dF, hI, hF);
}

// ─── Wrapper creación ─────────────────────────────────────────────────────
function armarHorario(e) {
  armarHorarioFor({
    diaInicio: "diaInicio", diaFin: "diaFin",
    horaInicio: "horaInicio", horaFin: "horaFin",
    horaFinDisplay: "horaFinDisplay",
    wrapperDiaFin: "wrapper-diaFin", wrapperHoraInicio: "wrapper-horaInicio",
    stepPrefix: "step-",
    previewId: "horarioPreview", previewTextId: "previewText",
  }, e?.target?.id || null);
}

// ─── Wrapper edición ──────────────────────────────────────────────────────
function armarHorarioEditar(e) {
  armarHorarioFor({
    diaInicio: "editarDiaInicio", diaFin: "editarDiaFin",
    horaInicio: "editarHoraInicio", horaFin: "editarHoraFin",
    horaFinDisplay: "editarHoraFinDisplay",
    wrapperDiaFin: "ewrapper-diaFin", wrapperHoraInicio: "ewrapper-horaInicio",
    stepPrefix: "estep-",
    previewId: "editarHorarioPreview", previewTextId: "editarPreviewText",
  }, e?.target?.id || null);
}

/* Asignar listeners */
["#diaInicio", "#diaFin", "#horaInicio"].forEach((sel) => {
  $(document).on("change", sel, armarHorario);
});
["#editarDiaInicio", "#editarDiaFin", "#editarHoraInicio"].forEach((sel) => {
  $(document).on("change", sel, armarHorarioEditar);
});

// Mostrar/ocultar campo de monto
function toggleMonto() {
  const apoyo = document.getElementById("apoyoEconomico").value;
  const grupoMonto = document.getElementById("grupoMonto");
  const montoInput = document.getElementById("montoApoyo");
  grupoMonto.style.display = apoyo === "Sí" ? "block" : "none";
  if (apoyo !== "Sí") montoInput.value = "";
}

// Aplicar máscara al escribir
document.addEventListener("DOMContentLoaded", function () {
  const montoInput = document.getElementById("montoApoyo");

  montoInput.addEventListener("input", function (e) {
    let val = e.target.value.replace(/\D/g, "");
    if (val === "") {
      e.target.value = "";
      return;
    }

    let num = parseFloat(val) / 100;
    let formatted = num.toLocaleString("es-MX", {
      style: "currency",
      currency: "MXN",
      minimumFractionDigits: 2,
    });

    e.target.value = formatted;
  });

  // Opcional: al copiar/pegar o dejar el campo
  montoInput.addEventListener("blur", function (e) {
    if (!e.target.value) return;
    let val = e.target.value.replace(/\D/g, "");
    let num = parseFloat(val) / 100;
    e.target.value = num.toLocaleString("es-MX", {
      style: "currency",
      currency: "MXN",
      minimumFractionDigits: 2,
    });
  });
});

document
  .getElementById("btnSolicitarPract")
  .addEventListener("click", function () {
    $("#solicitarPractModal").modal("show");
  });

$(document).ready(function () {
  // Inicializa máscara para teléfono y monto
  $("#contactoResponsable").inputmask("+52 999 999 9999");
  $("#montoApoyo").inputmask("currency", {
    prefix: "$ ",
    digits: 2,
    rightAlign: false,
  });

  // Envío AJAX del formulario
  $("#solicitarForm").on("submit", function (e) {
    e.preventDefault(); // evita envío normal

    const $form = $(this);
    const url = "controller/organismo/forms.php";
    const method = $form.attr("method");

    // Recolecta datos
    let formData = new FormData(this);
    formData.append("action", "solicitarPracticas"); // agrega acción al FormData

    $.ajax({
      url: url,
      type: method.toUpperCase(),
      data: formData,
      contentType: false,
      processData: false,
      dataType: "json", // asumiendo que el PHP devuelve JSON
      beforeSend: function () {
        // opcional: deshabilitar botón para evitar doble envío
        $form
          .find('button[type="submit"]')
          .prop("disabled", true)
          .text("Enviando...");
      },
      success: function (response) {
        if (response.success) {
          // muestra mensaje de éxito (puedes usar Bootstrap alerts o sweetalert)
          alert("Solicitud enviada correctamente.");
          // limpia formulario si lo deseas
          $form[0].reset();
          $("#grupoMonto").hide();
          solicitudes(); // recarga las solicitudes
          // cierra modal
          $("#solicitarPractModal").modal("hide");
        } else {
          // muestra mensaje de error enviado desde el servidor
          alert("Error: " + (response.message || "Ocurrió un problema."));
        }
      },
      error: function (xhr, status, error) {
        console.error(error);
        alert("Error al enviar la solicitud. Intenta de nuevo.");
      },
      complete: function () {
        // vuelve a habilitar el botón
        $form
          .find('button[type="submit"]')
          .prop("disabled", false)
          .text("Enviar Solicitud");
      },
    });
  });
});

// Función para mostrar/ocultar el campo de monto
function toggleMonto() {
  if ($("#apoyoEconomico").val() === "Sí") {
    $("#grupoMonto").slideDown();
    $("#montoApoyo").prop("required", true);
  } else {
    $("#grupoMonto").slideUp();
    $("#montoApoyo").prop("required", false).val("");
  }
}

// 1. Array global to hold the raw data
let windowSolicitudesRaw = [];

function solicitudes() {
  $.ajax({
    method: "POST",
    url: "controller/organismo/forms.php",
    data: { action: "getSolicitudes" },
    dataType: "json",
    success: function (response) {
      if (!Array.isArray(response) || response.length === 0) {
        $(".solicitudes").html(
          '<div class="alert alert-info" style="border-radius:1rem;">No hay solicitudes registradas.</div>'
        );
        $("#solicitud-detalle-container").html(`
          <div class="text-center text-muted mt-5 pt-5">
              <i class="fas fa-folder-open fa-4x mb-3" style="opacity:0.2;"></i>
              <h4 style="font-weight: 800; color: var(--brand-dark);">Aún no tienes vacantes</h4>
              <p style="font-size: 1.1rem;">Haz clic en "Nueva Vacante" para crear tu primera solicitud.</p>
          </div>
        `);
        return;
      }
      
      // Filter out expired applications
      windowSolicitudesRaw = response.filter(item => item.fecha_limite >= new Date().toISOString().split("T")[0]);
      
      prospectsMap = {}; // keep just in case other things use it
      
      // Render the Left Sidebar Master List
      let listHtml = '<div class="d-flex flex-column gap-2">';
      windowSolicitudesRaw.forEach((item, index) => {
        prospectsMap[item.id] = item.prospects || [];
        const numProspects = item.prospects ? item.prospects.length : 0;
        
        // Colores predefinidos rotativos para el ícono
        const colors = [
          {bg: "#e0e7ff", text: "#4f46e5", border: "#c7d2fe"}, // Indigo
          {bg: "#dcfce7", text: "#16a34a", border: "#bbf7d0"}, // Green
          {bg: "#fef3c7", text: "#d97706", border: "#fde68a"}, // Amber
          {bg: "#fee2e2", text: "#dc2626", border: "#fecaca"}, // Red
          {bg: "#f3e8ff", text: "#9333ea", border: "#e9d5ff"}  // Purple
        ];
        const color = colors[item.id % colors.length];

        const statusBadge = item.aceptado == 1 
          ? `<span class="badge bg-success" style="font-size: 0.65rem;">Activa</span>` 
          : `<span class="badge bg-warning text-dark" style="font-size: 0.65rem;">Pendiente</span>`;

        listHtml += `
          <div class="master-list-item p-3 bg-white border" 
               style="border-radius: 1rem; transition: all 0.2s; cursor: pointer;"
               onclick="renderDetalleSolicitud(${index}, this)">
              <div class="d-flex justify-content-between align-items-start mb-2">
                  <div class="d-flex gap-2 align-items-center">
                      <div style="width: 32px; height: 32px; border-radius: 8px; background: ${color.bg}; color: ${color.text}; border: 1px solid ${color.border}; display:flex; align-items:center; justify-content:center; font-weight: 800; font-size: 0.8rem; flex-shrink:0;">
                          ${item.licenciatura.substring(0,2).toUpperCase()}
                      </div>
                      <h6 class="mb-0 fw-bold" style="font-size: 0.95rem; color: #0f172a; line-height: 1.2;">${item.licenciatura}</h6>
                  </div>
              </div>
              <div class="d-flex justify-content-between align-items-end mt-3">
                  <div class="d-flex gap-1 flex-wrap">
                      ${statusBadge}
                      <span class="badge border text-dark bg-light" style="font-size: 0.65rem;">${item.modalidad}</span>
                  </div>
                  ${numProspects > 0 ? `<span class="badge rounded-pill shadow-sm" style="background:#ef4444; font-size: 0.7rem;"><i class="fas fa-users me-1"></i>${numProspects}</span>` : ''}
              </div>
          </div>
        `;
      });
      listHtml += '</div>';
      $(".solicitudes").html(listHtml);

      // Select the first one automatically
      if(windowSolicitudesRaw.length > 0) {
        setTimeout(() => {
          $(".master-list-item").first().click();
        }, 50);
      }
    },
    error: console.error,
  });
}

function renderDetalleSolicitud(index, element) {
  // Update active state in master list
  $(".master-list-item").css({"border-color": "#e2e8f0", "box-shadow": "none", "background": "white"});
  if(element) {
    $(element).css({"border-color": "#01643D", "box-shadow": "0 4px 15px -5px rgba(1,100,61,0.15)", "background": "#f0fdf4"});
  }

  const item = windowSolicitudesRaw[index];
  if(!item) return;

  const horario = `${item.dia_inicio} → ${item.dia_fin}, ${item.hora_inicio.slice(0, 5)}–${item.hora_fin.slice(0, 5)}`;
  const prospects = item.prospects || [];

  // Generate Action Buttons for Vacancy (Edit/Delete)
  const isAceptado = item.aceptado == 1;
  const vacanteActions = isAceptado ? '' : `
    <button class="btn btn-sm btn-light border edit-solicitud" title="Editar Vacante" data-id="${item.id}" style="color:#64748b; font-weight:700;"><i class="fas fa-edit me-1"></i>Editar</button>
    <button class="btn btn-sm btn-light border delete-solicitud" title="Eliminar Vacante" data-id="${item.id}" style="color:#ef4444; font-weight:700;"><i class="fas fa-trash-alt me-1"></i>Eliminar</button>
  `;

  // Generate Prospects HTML inline
  let prospectsHtml = '';
  if (prospects.length > 0) {
    prospectsHtml = prospects.map(p => {
      let statusBanner = "";
      let actions = "";

      if (p.practicas_finalizadas == 1) {
        const fDate = p.fecha_finalizacion ? new Date(p.fecha_finalizacion).toLocaleDateString('es-MX') : '';
        statusBanner = `<span class="badge ms-2" style="background:#d1fae5;color:#059669;border:1.5px solid #059669; border-radius:100px;">🎓 Prácticas Finalizadas${fDate ? ' · ' + fDate : ''}</span>`;
      } else if (p.isAcepted == 1) {
        statusBanner = `<span class="badge bg-success ms-2" style="border-radius:100px;">Aceptado</span>`;
      } else if (p.isAcepted == 2) {
        statusBanner = `<span class="badge bg-danger ms-2" style="border-radius:100px;">Rechazado</span>`;
      } else if (p.status_carta === 'expirada') {
        statusBanner = `<span class="badge bg-danger ms-2" style="border-radius:100px;">Carta Expirada</span>`;
        actions = `<span class="text-danger small fw-bold"><i class="fas fa-exclamation-triangle me-1"></i>El alumno no se presentó en tiempo</span>`;
      } else if (p.status_carta === 'vigente') {
        statusBanner = `<span class="badge bg-warning text-dark ms-2" style="border-radius:100px;">Pendiente Entrevista</span>`;
        actions = `<button class="btn btn-sm btn-info btn-evaluar-entrevista px-3 text-white shadow-sm rounded-pill" data-idstudent="${p.idStudent}" data-idsolicitud="${item.id}"><i class="fas fa-clipboard-check me-1"></i>Evaluar Entrevista</button>`;
      } else if (p.status_carta === 'presentada') {
        statusBanner = `<span class="badge bg-primary ms-2" style="border-radius:100px;">En Revisión (Entrevistado)</span>`;
        actions = `
          <button class="btn btn-sm btn-success btn-aceptar-prospecto px-3 shadow-sm rounded-pill" data-idStudent="${p.idStudent}" data-idSolicitud="${item.id}"><i class="fas fa-check me-1"></i>Aceptar</button>
          <button class="btn btn-sm btn-danger btn-rechazar-prospecto px-3 shadow-sm rounded-pill" data-idStudent="${p.idStudent}" data-idSolicitud="${item.id}"><i class="fas fa-times me-1"></i>Rechazar</button>
        `;
      } else {
        actions = `
          <button class="btn btn-sm btn-success btn-aceptar-prospecto px-3 shadow-sm rounded-pill" data-idStudent="${p.idStudent}" data-idSolicitud="${item.id}"><i class="fas fa-check me-1"></i>Aceptar</button>
          <button class="btn btn-sm btn-danger btn-rechazar-prospecto px-3 shadow-sm rounded-pill" data-idStudent="${p.idStudent}" data-idSolicitud="${item.id}"><i class="fas fa-times me-1"></i>Rechazar</button>
        `;
      }

      return `
        <div class="p-4 mb-3" style="background: white; border: 1px solid #e2e8f0; border-radius: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
              <div style="width:50px;height:50px;border-radius:14px;background:rgba(1,100,61,.1);display:flex;align-items:center;justify-content:center;color:#01643D;font-size:1.5rem;flex-shrink:0;">
                <i class="fas fa-user-graduate"></i>
              </div>
              <div>
                <div class="fw-bold" style="font-size: 1.15rem; color: #0f172a;">${p.nombre_completo} ${statusBanner}</div>
                <div class="text-muted mt-1" style="font-size: 0.85rem; color:#64748b;">
                  <span class="me-3"><i class="fas fa-id-card me-1 text-primary"></i> ${p.matricula}</span>
                  <span class="me-3"><i class="fas fa-users-class me-1 text-info"></i> ${p.grupo}</span>
                  <span><i class="fas fa-phone-alt me-1 text-success"></i> ${p.telefono}</span>
                </div>
              </div>
            </div>
            <div class="d-flex gap-2">
              ${actions}
            </div>
          </div>
        </div>
      `;
    }).join('');
  } else {
    prospectsHtml = `
      <div class="text-center p-5 mt-3" style="background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 1.5rem;">
        <div style="width:80px;height:80px;border-radius:50%;background:white;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;box-shadow:0 4px 6px rgba(0,0,0,0.05);">
            <i class="fas fa-user-times fa-2x text-muted" style="opacity: 0.5;"></i>
        </div>
        <h5 class="text-muted fw-bold">No hay postulantes aún</h5>
        <p class="text-muted mb-0">Los alumnos que apliquen a esta vacante aparecerán aquí.</p>
      </div>
    `;
  }

  const detailHtml = `
    <!-- Cabecera de la Vacante -->
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h2 style="font-weight: 900; color: var(--brand-dark); font-size: 2.2rem; letter-spacing: -0.03em; margin-bottom:0.5rem;">${item.licenciatura}</h2>
            <div class="d-flex gap-2 flex-wrap">
                <span class="badge bg-white text-dark border px-3 py-2 shadow-sm" style="border-radius: 100px; font-size: 0.8rem; font-weight:600;"><i class="fas fa-building me-1 text-primary"></i> ${item.empresa || "Sin Empresa"}</span>
                <span class="badge bg-white text-dark border px-3 py-2 shadow-sm" style="border-radius: 100px; font-size: 0.8rem; font-weight:600;"><i class="fas fa-laptop-house me-1 text-info"></i> ${item.modalidad}</span>
                <span class="badge bg-white text-dark border px-3 py-2 shadow-sm" style="border-radius: 100px; font-size: 0.8rem; font-weight:600;"><i class="fas fa-user-friends me-1 text-success"></i> ${item.num_practicantes} Vacantes</span>
            </div>
        </div>
        <div class="d-flex gap-2">
            ${vacanteActions}
        </div>
    </div>

    <!-- Info de la Vacante -->
    <div class="row g-4 mb-5">
        <div class="col-md-6">
            <div class="p-4 h-100" style="background: #f8fafc; border-radius: 1.5rem; border: 1px solid #e2e8f0;">
                <h6 style="font-weight: 900; color: #475569; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em;" class="mb-4">Información Operativa</h6>
                <div class="d-flex align-items-start gap-3 mb-4">
                    <div style="color: #3b82f6; background:#eff6ff; padding:0.5rem; border-radius:0.5rem;"><i class="fas fa-clock fa-lg"></i></div>
                    <div><strong class="d-block text-dark mb-1">Horario</strong><span class="text-muted" style="font-size:0.9rem;">${horario}</span></div>
                </div>
                <div class="d-flex align-items-start gap-3 mb-4">
                    <div style="color: #f59e0b; background:#fffbeb; padding:0.5rem; border-radius:0.5rem;"><i class="fas fa-hand-holding-usd fa-lg"></i></div>
                    <div><strong class="d-block text-dark mb-1">Apoyo Económico</strong><span class="text-muted" style="font-size:0.9rem;">${item.ofrece_apoyo_economico == 1 ? "Sí ("+item.monto_apoyo+")" : "No"}</span></div>
                </div>
                <div class="d-flex align-items-start gap-3">
                    <div style="color: #ef4444; background:#fef2f2; padding:0.5rem; border-radius:0.5rem;"><i class="fas fa-hourglass-end fa-lg"></i></div>
                    <div><strong class="d-block text-dark mb-1">Límite de Incorporación</strong><span class="text-muted" style="font-size:0.9rem;">${item.fecha_limite}</span></div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="p-4 h-100" style="background: #f8fafc; border-radius: 1.5rem; border: 1px solid #e2e8f0;">
                <h6 style="font-weight: 900; color: #475569; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em;" class="mb-4">Sede y Contacto</h6>
                <div class="d-flex align-items-start gap-3 mb-4">
                    <div style="color: #10b981; background:#ecfdf5; padding:0.5rem; border-radius:0.5rem;"><i class="fas fa-map-marker-alt fa-lg"></i></div>
                    <div><strong class="d-block text-dark mb-1">${item.ciudad || "Sede"}</strong><span class="text-muted" style="font-size:0.9rem;">${item.direccion_practica || "–"}</span></div>
                </div>
                <div class="d-flex align-items-start gap-3 mb-4">
                    <div style="color: #8b5cf6; background:#f5f3ff; padding:0.5rem; border-radius:0.5rem;"><i class="fas fa-user-tie fa-lg"></i></div>
                    <div><strong class="d-block text-dark mb-1">${item.nombre_responsable}</strong><span class="text-muted" style="font-size:0.9rem;">${item.telefono}</span></div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="p-4" style="background: white; border-radius: 1.5rem; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);">
                <h6 style="font-weight: 900; color: #475569; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em;" class="mb-2">Actividades formativas que desarrollarán los practicantes</h6>
                <p style="color: #334155; line-height: 1.6; margin-bottom: 1.5rem; font-size:0.95rem;">${item.actividades}</p>

                <div class="row g-4 mb-2">
                    <div class="col-md-6">
                        <h6 style="font-weight: 900; color: #475569; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em;" class="mb-2"><i class="fas fa-tasks me-1 text-primary"></i> Funciones</h6>
                        <p style="color: #334155; line-height: 1.6; margin-bottom: 0; font-size:0.95rem;">${item.funciones || "No especificado."}</p>
                    </div>
                    <div class="col-md-6">
                        <h6 style="font-weight: 900; color: #475569; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em;" class="mb-2"><i class="fas fa-bullseye me-1 text-danger"></i> Objetivos</h6>
                        <p style="color: #334155; line-height: 1.6; margin-bottom: 0; font-size:0.95rem;">${item.objetivos || "No especificado."}</p>
                    </div>
                    <div class="col-md-6">
                        <h6 style="font-weight: 900; color: #475569; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em;" class="mb-2"><i class="fas fa-medal me-1 text-warning"></i> Competencias</h6>
                        <p style="color: #334155; line-height: 1.6; margin-bottom: 0; font-size:0.95rem;">${item.competencias || "No especificado."}</p>
                    </div>
                    <div class="col-md-6">
                        <h6 style="font-weight: 900; color: #475569; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em;" class="mb-2"><i class="fas fa-flag-checkered me-1 text-success"></i> Resultados esperados</h6>
                        <p style="color: #334155; line-height: 1.6; margin-bottom: 0; font-size:0.95rem;">${item.resultados_esperados || "No especificado."}</p>
                    </div>
                </div>

                <hr style="border-color:#e2e8f0;">
                <h6 style="font-weight: 900; color: #475569; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em;" class="mb-2">Capacidades Requeridas</h6>
                <p style="color: #334155; line-height: 1.6; margin-bottom: 0; font-size:0.95rem;">${item.capacidades || "No se especificaron capacidades particulares."}</p>
            </div>
        </div>
    </div>

    <!-- Lista de Postulantes en línea -->
    <div>
        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
            <h4 style="font-weight: 900; color: var(--brand-dark); margin:0;"><i class="fas fa-users-class me-2" style="color: #3b82f6;"></i> Postulantes <span class="badge bg-secondary ms-2 rounded-pill">${prospects.length}</span></h4>
        </div>
        ${prospectsHtml}
    </div>
  `;

  $("#solicitud-detalle-container").html(detailHtml).hide().fadeIn(300);
}

$(document).on("click", ".btn-evaluar-entrevista", function () {
  const idStudent = $(this).data("idstudent");
  const idSolicitud = $(this).data("idsolicitud");
  
  Swal.fire({
    title: "Evaluación de la Entrevista",
    html: `
      <div class="text-start mb-3">
        <label class="form-label fw-bold">¿Llegó a tiempo a la cita?</label>
        <select id="eval-tiempo" class="form-select">
          <option value="1">Sí, puntual</option>
          <option value="0">No, llegó tarde</option>
        </select>
      </div>
      <div class="text-start mb-3">
        <label class="form-label fw-bold">¿Su vestimenta y presentación fueron adecuadas?</label>
        <select id="eval-formal" class="form-select">
          <option value="1">Sí, formal / adecuado</option>
          <option value="0">No adecuado</option>
        </select>
      </div>
      <div class="text-start mb-3">
        <label class="form-label fw-bold">Desempeño general en la entrevista (1 al 5)</label>
        <input type="number" id="eval-calif" class="form-control" min="1" max="5" value="5">
      </div>
      <div class="text-start mb-3">
        <label class="form-label fw-bold">Comentarios adicionales</label>
        <textarea id="eval-comentarios" class="form-control" rows="2" placeholder="Observaciones sobre sus respuestas, actitud, etc."></textarea>
      </div>
    `,
    icon: "info",
    showCancelButton: true,
    confirmButtonText: "Guardar y Marcar en Proceso",
    cancelButtonText: "Cancelar",
    preConfirm: () => {
      const calif = document.getElementById('eval-calif').value;
      if (calif < 1 || calif > 5) {
        Swal.showValidationMessage('La calificación debe estar entre 1 y 5.');
        return false;
      }
      return {
        llego_a_tiempo: document.getElementById('eval-tiempo').value,
        llego_formal: document.getElementById('eval-formal').value,
        calificacion_respuestas: calif,
        comentarios: document.getElementById('eval-comentarios').value
      };
    }
  }).then((result) => {
    if (result.isConfirmed) {
      const data = result.value;
      $.ajax({
        method: "POST",
        url: "controller/organismo/forms.php",
        data: {
          action: "evaluarEntrevista",
          idStudent: idStudent,
          idSolicitud: idSolicitud,
          ...data
        },
        dataType: "json",
        success: function (response) {
          if (response.success) {
            Swal.fire("Entrevista guardada", "El candidato está ahora en revisión.", "success");
            solicitudes(); // recarga las solicitudes
            if(window.orgDashboard) {
              if(window.orgDashboard.loadHistorialAlumnos) window.orgDashboard.loadHistorialAlumnos();
              if(window.orgDashboard.loadPostulaciones) window.orgDashboard.loadPostulaciones();
            }
            $("#viewUsersModal").modal("hide");
          } else {
            Swal.fire("Error", response.message || "Ocurrió un problema.", "error");
          }
        },
        error: function () {
          Swal.fire("Error", "No se pudo guardar la evaluación.", "error");
        }
      });
    }
  });
});

$(document).on("click", ".btn-aceptar-prospecto", function () {
  const idStudent = $(this).data("idstudent");
  const idSolicitud = $(this).data("idsolicitud");

  Swal.fire({
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
  }).then((result) => {
    if (result.isConfirmed) {
      $.ajax({
        method: "POST",
        url: "controller/organismo/forms.php",
        data: { 
          action: "aceptarProspecto", 
          idStudent, 
          idSolicitud,
          fechaInicio: result.value.fechaInicio,
          motivoAceptacion: result.value.motivo
        },
        dataType: "json",
        success: function (response) {
          if (response.success) {
            Swal.fire("Prospecto aceptado correctamente.", "", "success");
            solicitudes(); // recarga las solicitudes
            if (window.orgDashboard) { window.orgDashboard.loadSummary(); window.orgDashboard.loadPostulaciones(); }
            $("#viewUsersModal").modal("hide");
          } else {
            Swal.fire("Error", response.message || "Ocurrió un problema.", "error");
          }
        },
        error: function (xhr, status, error) {
          console.error(error);
          Swal.fire("Error al aceptar el prospecto. Intenta de nuevo.", "", "error");
        },
      });
    }
  });
});

$(document).on("click", ".btn-rechazar-prospecto", function () {
  const idStudent = $(this).data("idstudent");
  const idSolicitud = $(this).data("idsolicitud");

  Swal.fire({
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
  }).then((result) => {
    if (result.isConfirmed) {
      $.ajax({
        method: "POST",
        url: "controller/organismo/forms.php",
        data: { 
          action: "rechazarProspecto", 
          idStudent, 
          idSolicitud,
          motivoRechazo: result.value.motivo
        },
        dataType: "json",
        success: function (response) {
          if (response.success) {
            Swal.fire("Prospecto rechazado correctamente.", "", "success");
            solicitudes(); // recarga las solicitudes
            if (window.orgDashboard) { window.orgDashboard.loadSummary(); window.orgDashboard.loadPostulaciones(); }
            $("#viewUsersModal").modal("hide");
          } else {
            Swal.fire("Error", response.message || "Ocurrió un problema.", "error");
          }
        },
        error: function (xhr, status, error) {
          console.error(error);
          Swal.fire("Error al rechazar el prospecto. Intenta de nuevo.", "", "error");
        },
      });
    }
  });
});