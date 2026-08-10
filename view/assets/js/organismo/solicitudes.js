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
function toggleEditarMonto() {
  const apoyo = document.getElementById("editarApoyoEconomico").value;
  const grupoMonto = document.getElementById("editarGrupoMonto");
  const montoInput = document.getElementById("editarMontoApoyo");
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
  $("#montoApoyo, #editarMontoApoyo").inputmask("currency", {
    prefix: "$ ",
    digits: 2,
    rightAlign: false,
  });

  // Envío AJAX del formulario
  $("#solicitarForm").on("submit", function (e) {
    e.preventDefault(); // evita envío normal

    // El perfil requiere al menos una habilidad
    if (window.skillsPickerNueva && !window.skillsPickerNueva.validate()) return;

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
          if (window.skillsPickerNueva) window.skillsPickerNueva.clear();
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
      
      prospectsMap = {};
      
      let listHtml = '<div class="d-flex flex-column gap-3">';
      windowSolicitudesRaw.forEach((item, index) => {
        prospectsMap[item.id] = item.prospects || [];
        const numProspects = item.prospects ? item.prospects.length : 0;
        
        const colors = [
          {bg: "#f3e8ff", text: "#9333ea"}, // Purple
          {bg: "#dbeafe", text: "#1d4ed8"}, // Blue
          {bg: "#dcfce7", text: "#16a34a"}, // Green
          {bg: "#fee2e2", text: "#dc2626"}, // Red
          {bg: "#fef3c7", text: "#d97706"}  // Amber
        ];
        const color = colors[item.id % colors.length];

        // Etiquetas estilo píldora
        const statusBadge = item.aceptado == 1
          ? `<span class="badge rounded-pill" style="background-color: #dcfce7; color: #16a34a; font-size: 0.8rem; font-weight: 600; padding: 0.4rem 0.8rem;">Activa</span>`
          : `<span class="badge rounded-pill" style="background-color: #fef3c7; color: #d97706; font-size: 0.8rem; font-weight: 600; padding: 0.4rem 0.8rem;">Pendiente</span>`;
          
        const modalidadBadge = `<span class="badge rounded-pill" style="background-color: #f1f5f9; color: #475569; font-size: 0.8rem; font-weight: 600; padding: 0.4rem 0.8rem;">${item.modalidad || 'Presencial'}</span>`;

        // Botones de acción aislados
        const isAceptado = item.aceptado == 1;
        const vacanteActions = isAceptado ? '' : `
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill d-flex align-items-center px-3 fw-bold edit-solicitud" title="Editar Vacante" data-id="${item.id}" style="border-width: 1.5px;">
              <i class="fas fa-pen me-1" style="font-size: 0.75rem;"></i>Editar
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill d-flex align-items-center px-3 fw-bold delete-solicitud" title="Eliminar Vacante" data-id="${item.id}" style="border-width: 1.5px;">
              <i class="fas fa-trash-alt me-1" style="font-size: 0.75rem;"></i>Eliminar
            </button>
          </div>
        `;

        const skills = item.habilidades ? item.habilidades.split("|") : [];
        const perfilTitulo = item.licenciatura || (skills.length ? skills.slice(0, 2).join(" · ") + (skills.length > 2 ? ` +${skills.length - 2}` : "") : "Vacante");
        const perfilIniciales = (skills[0] || item.licenciatura || "PP").substring(0, 2).toUpperCase();
        
        // Mock de datos para el subtítulo (Ajusta a tus columnas SQL)
        const fechaTxt = item.fecha_creacion ? `Creado hace X días` : 'Creado recientemente';
        const ubicacionTxt = item.ubicacion ? item.ubicacion : 'Ubicación no especificada';

        listHtml += `
          <div class="master-list-item bg-white border"
               style="border-radius: 12px; transition: box-shadow 0.2s; cursor: pointer; overflow: hidden;"
               onclick="renderDetalleSolicitud(${index}, this)"
               onmouseover="this.style.boxShadow='0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)'"
               onmouseout="this.style.boxShadow='none'">
              
              <!-- Zona de Información -->
              <div class="p-3 d-flex align-items-center gap-3">
                  <div style="width: 50px; height: 50px; border-radius: 50%; background: ${color.bg}; color: ${color.text}; display:flex; align-items:center; justify-content:center; font-weight: 700; font-size: 1.1rem; flex-shrink:0;">
                      ${perfilIniciales}
                  </div>
                  <div>
                      <h6 class="mb-1 fw-bold" style="font-size: 1.05rem; color: #111827;">${perfilTitulo}</h6>
                      <p class="mb-0" style="font-size: 0.85rem; color: #4b5563;">
                          ${fechaTxt} | Ubicación: ${ubicacionTxt}
                      </p>
                  </div>
              </div>

              <!-- Separador -->
              <hr class="m-0" style="border-color: #e5e7eb; opacity: 1;">

              <!-- Zona de Controles -->
              <div class="p-3 d-flex justify-content-between align-items-center bg-white">
                  <div class="d-flex gap-2">
                      ${statusBadge}
                      ${modalidadBadge}
                  </div>
                  <div class="d-flex gap-2 align-items-center">
                      ${vacanteActions}
                      ${numProspects > 0 ? `<span class="badge rounded-pill bg-danger shadow-sm ms-2"><i class="fas fa-users me-1"></i>${numProspects}</span>` : ''}
                  </div>
              </div>
          </div>
        `;
      });
      listHtml += '</div>';
      $(".solicitudes").html(listHtml);

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
      const est = p.estado || null;
      const ds = `data-idstudent="${p.idStudent}" data-idsolicitud="${item.id}"`;

      if (p.practicas_finalizadas == 1) {
        const fDate = p.fecha_finalizacion ? new Date(p.fecha_finalizacion).toLocaleDateString('es-MX') : '';
        statusBanner = `<span class="badge ms-2" style="background:#d1fae5;color:#059669;border:1.5px solid #059669; border-radius:100px;">🎓 Prácticas Finalizadas${fDate ? ' · ' + fDate : ''}</span>`;
      } else if (est === 'ACEPTADO_FINAL' || p.isAcepted == 1) {
        statusBanner = `<span class="badge bg-success ms-2" style="border-radius:100px;">Aceptado</span>`;
      } else if (est === 'RECHAZADO_PREPOSTULACION') {
        statusBanner = `<span class="badge bg-danger ms-2" style="border-radius:100px;">Rechazado en prepostulación</span>`;
      } else if (est === 'RECHAZADO_FINAL') {
        statusBanner = `<span class="badge bg-danger ms-2" style="border-radius:100px;">Rechazado</span>`;
      } else if (est === 'ENTREVISTA_PROGRAMADA') {
        statusBanner = `<span class="badge bg-info text-dark ms-2" style="border-radius:100px;">Entrevista programada</span>`;
        actions = `
          <button class="btn btn-sm btn-light border btn-ver-entrevista px-3 rounded-pill" ${ds}><i class="fas fa-calendar-day me-1"></i>Ver entrevista</button>
          <button class="btn btn-sm btn-primary btn-cerrar-entrevista px-3 shadow-sm rounded-pill" ${ds}><i class="fas fa-clipboard-check me-1"></i>Cerrar y evaluar</button>`;
      } else if (est === 'ENTREVISTA_CERRADA') {
        statusBanner = `<span class="badge bg-primary ms-2" style="border-radius:100px;">Entrevistado</span>`;
        actions = `
          <button class="btn btn-sm btn-success btn-aceptar-final px-3 shadow-sm rounded-pill" ${ds}><i class="fas fa-check me-1"></i>Aceptar</button>
          <button class="btn btn-sm btn-danger btn-rechazar-final px-3 shadow-sm rounded-pill" ${ds}><i class="fas fa-times me-1"></i>Rechazar</button>`;
      } else {
        // PREPOSTULADO (o sin estado, por compatibilidad)
        statusBanner = `<span class="badge bg-warning text-dark ms-2" style="border-radius:100px;">Prepostulado</span>`;
        actions = `
          <button class="btn btn-sm btn-light border btn-ver-prepostulacion px-3 rounded-pill" ${ds}><i class="fas fa-file-alt me-1"></i>Ver prepostulación</button>
          <button class="btn btn-sm btn-success btn-aceptar-prepostulacion px-3 shadow-sm rounded-pill" ${ds}><i class="fas fa-user-check me-1"></i>Aceptar para entrevista</button>
          <button class="btn btn-sm btn-danger btn-rechazar-prepostulacion px-3 shadow-sm rounded-pill" ${ds}><i class="fas fa-times me-1"></i>Rechazar</button>`;
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

  // Perfil de la vacante: licenciatura (legado) o habilidades (modelo nuevo)
  const detSkills = item.habilidades ? item.habilidades.split("|") : [];
  const detTitulo = item.licenciatura || (detSkills.length ? "Perfil por Habilidades" : "Vacante");
  const skillBadges = detSkills.map(s =>
    `<span class="badge px-3 py-2" style="background: rgba(1,100,61,0.08); color: #01643D; border: 1px solid rgba(1,100,61,0.25); border-radius: 100px; font-size: 0.8rem; font-weight: 700;"><i class="fas fa-check me-1"></i>${s}</span>`
  ).join("");

  const detailHtml = `
    <!-- Cabecera de la Vacante -->
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h2 style="font-weight: 900; color: var(--brand-dark); font-size: 2.2rem; letter-spacing: -0.03em; margin-bottom:0.5rem;">${detTitulo}</h2>
            <div class="d-flex gap-2 flex-wrap">
                <span class="badge bg-white text-dark border px-3 py-2 shadow-sm" style="border-radius: 100px; font-size: 0.8rem; font-weight:600;"><i class="fas fa-building me-1 text-primary"></i> ${item.empresa || "Sin Empresa"}</span>
                <span class="badge bg-white text-dark border px-3 py-2 shadow-sm" style="border-radius: 100px; font-size: 0.8rem; font-weight:600;"><i class="fas fa-laptop-house me-1 text-info"></i> ${item.modalidad}</span>
                <span class="badge bg-white text-dark border px-3 py-2 shadow-sm" style="border-radius: 100px; font-size: 0.8rem; font-weight:600;"><i class="fas fa-user-friends me-1 text-success"></i> ${item.num_practicantes} Vacantes</span>
            </div>
            ${skillBadges ? `<div class="d-flex gap-2 flex-wrap mt-2">${skillBadges}</div>` : ""}
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
                        <h6 style="font-weight: 900; color: #475569; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em;" class="mb-2"><i class="fas fa-flag-checkered me-1 text-success"></i> Resultados esperados</h6>
                        <p style="color: #334155; line-height: 1.6; margin-bottom: 0; font-size:0.95rem;">${item.resultados_esperados || "No especificado."}</p>
                    </div>
                </div>

                <hr style="border-color:#e2e8f0;">
                <h6 style="font-weight: 900; color: #475569; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em;" class="mb-2">Capacidades Requeridas</h6>
                <p style="color: #334155; line-height: 1.6; margin-bottom: 0; font-size:0.95rem;">${item.capacidades || "No se especificaron capacidades particulares."}</p>

                <hr style="border-color:#e2e8f0;">
                <h6 style="font-weight: 900; color: #475569; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em;" class="mb-2">Actitudes Requeridas</h6>
                <p style="color: #334155; line-height: 1.6; margin-bottom: 0; font-size:0.95rem;">${item.actitudes || "No se especificaron actitudes particulares."}</p>
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

/* ============================================================
   EDICIÓN Y ELIMINACIÓN DE VACANTES
   (antes en modalFuncion.js, archivo legado que no se carga)
   ============================================================ */
$(document).on("click", ".edit-solicitud", function () {
  const id = $(this).data("id");
  $.ajax({
    method: "POST",
    url: "controller/organismo/forms.php",
    data: { action: "getSolicitudById", id },
    dataType: "json",
    success: function (data) {
      if (!data || !data.id) {
        Swal.fire("Error", "No se pudo cargar la vacante.", "error");
        return;
      }
      $("#editarIdSolicitud").val(data.id);
      // Perfil: habilidades del modelo nuevo; una vacante legada inicia vacío
      // y se le deben capturar habilidades para poder guardar
      if (window.skillsPickerEditar) {
        window.skillsPickerEditar.set(data.habilidades || []);
      }
      $("#editarNumPract").val(data.num_practicantes);
      $("#editarActividades").val(data.actividades);
      $("#editarFunciones").val(data.funciones);
      $("#editarResultadosEsperados").val(data.resultados_esperados);
      $("#editarApoyoEconomico").val(data.ofrece_apoyo_economico == 1 ? "Sí" : "No").trigger("change");
      if (data.ofrece_apoyo_economico == 1) {
        $("#editarGrupoMonto").show();
        $("#editarMontoApoyo").val(data.monto_apoyo).prop("required", true);
      } else {
        $("#editarGrupoMonto").hide();
        $("#editarMontoApoyo").val("").prop("required", false);
      }
      $("#editarFechaLimite").val(data.fecha_limite);
      $("#editarModalidad").val(data.modalidad);
      $("#editarDiaInicio").val(data.dia_inicio);
      $("#editarDiaFin").val(data.dia_fin);
      $("#editarHoraInicio").val(data.hora_inicio.slice(0, 5));
      $("#editarHoraFin").val(data.hora_fin.slice(0, 5));
      $("#editarCapacidades").val(data.capacidades);
      $("#editarActitudes").val(data.actitudes);
      $("#editarDireccionPractica").val(data.direccion_practica);
      $("#editarNombreResponsable").val(data.nombre_responsable);
      $("#editarContactoResponsable").val(data.telefono);

      $("#editarPractModal").modal("show");
    },
    error: function () {
      Swal.fire("Error", "No se pudo cargar la vacante.", "error");
    },
  });
});

$("#editarForm").on("submit", function (e) {
  e.preventDefault();

  if (window.skillsPickerEditar && !window.skillsPickerEditar.validate()) return;

  const $form = $(this);
  let formData = new FormData(this);
  formData.append("action", "updateSolicitud");

  $.ajax({
    url: "controller/organismo/forms.php",
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,
    dataType: "json",
    beforeSend: function () {
      $form.find('button[type="submit"]').prop("disabled", true).text("Actualizando...");
    },
    success: function (response) {
      if (response.success) {
        Swal.fire("Vacante actualizada", "", "success");
        $("#editarPractModal").modal("hide");
        solicitudes();
      } else {
        Swal.fire("Error", response.message || "Ocurrió un problema.", "error");
      }
    },
    error: function () {
      Swal.fire("Error", "No se pudo actualizar la vacante. Intenta de nuevo.", "error");
    },
    complete: function () {
      $form.find('button[type="submit"]').prop("disabled", false).html('<i class="fas fa-save"></i> Guardar Cambios');
    },
  });
});

$(document).on("click", ".delete-solicitud", function () {
  const id = $(this).data("id");
  Swal.fire({
    title: "¿Eliminar esta vacante?",
    text: "Los alumnos ya no podrán postularse a ella.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
    confirmButtonColor: "#ef4444",
  }).then((result) => {
    if (!result.isConfirmed) return;
    $.ajax({
      method: "POST",
      url: "controller/organismo/forms.php",
      data: { action: "deleteSolicitud", id },
      dataType: "json",
      success: function (response) {
        if (response.success) {
          Swal.fire("Vacante eliminada", "", "success");
          solicitudes();
        } else {
          Swal.fire("Error", response.message || "Ocurrió un problema.", "error");
        }
      },
      error: function () {
        Swal.fire("Error", "No se pudo eliminar la vacante.", "error");
      },
    });
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

/* ============================================================
   FASE 6 · Nuevo flujo de postulación (prepostulación → entrevista → final)
   ============================================================ */

function escHtml(s) {
  return String(s == null ? "" : s).replace(/[&<>"]/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[c]));
}

/* ── Estilos compartidos de los diálogos del flujo (mismo lenguaje que los modales neo) ── */
(function () {
  if (document.getElementById("ppo-styles")) return;
  const css = `
  .swal2-popup.ppo-pop{border-radius:1.8rem;padding:2rem 1.9rem 1.7rem;width:34em;max-width:94vw;font-family:inherit}
  .swal2-popup.ppo-pop.ppo-wide{width:44em}
  .ppo-actions{gap:.6rem;margin-top:1.35rem}
  .ppo-btn{border-radius:100px;font-weight:800;padding:.7rem 1.6rem;border:none;transition:all .2s;font-size:.95rem}
  .ppo-btn-pri{background:#01643D;color:#fff;box-shadow:inset 0 -3px 0 rgba(0,0,0,.1)}
  .ppo-btn-pri:hover{transform:translateY(-2px);box-shadow:inset 0 -3px 0 rgba(0,0,0,.1),0 10px 20px -5px rgba(1,100,61,.4)}
  .ppo-btn-danger{background:#dc2626;color:#fff;box-shadow:inset 0 -3px 0 rgba(0,0,0,.12)}
  .ppo-btn-danger:hover{transform:translateY(-2px);box-shadow:0 10px 20px -5px rgba(220,38,38,.4)}
  .ppo-btn-sec{background:#fff;border:1px solid #e2e8f0;color:#334155}
  .ppo-btn-sec:hover{background:#f8fafc}
  .ppo-head{display:flex;align-items:center;gap:.85rem;margin-bottom:1.2rem;text-align:left}
  .ppo-head-ic{width:48px;height:48px;border-radius:1rem;display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;background:rgba(1,100,61,.1);color:#01643D}
  .ppo-head-ic.warn{background:#fef2f2;color:#dc2626}
  .ppo-head-ic.info{background:#eff6ff;color:#2563eb}
  .ppo-head-tx h4{margin:0;font-weight:900;color:#0f172a;font-size:1.2rem;letter-spacing:-.02em;text-align:left}
  .ppo-head-tx small{display:block;color:#64748b;font-weight:500;font-size:.82rem;margin-top:.1rem;text-align:left}
  .ppo-lbl{font-size:.76rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#475569;margin:0 0 .4rem;display:block;text-align:left}
  .ppo-input{width:100%;background:#fff;border:1px solid #cbd5e1;border-radius:.9rem;padding:.75rem 1rem;font-weight:600;color:#0f172a;font-size:.95rem;transition:all .25s;box-sizing:border-box}
  .ppo-input:focus{border-color:#01643D;box-shadow:0 0 0 4px rgba(1,100,61,.1);outline:none}
  textarea.ppo-input{resize:vertical}
  .ppo-fld{margin-bottom:1rem;text-align:left}
  .ppo-grid2{display:grid;grid-template-columns:1fr 1fr;gap:.8rem}
  .ppo-cards{display:grid;grid-template-columns:1fr 1fr;gap:.7rem}
  .ppo-card{border:2px solid #e2e8f0;background:#fff;border-radius:1.1rem;padding:1rem .8rem;cursor:pointer;text-align:center;transition:all .2s;font-weight:800;color:#334155}
  .ppo-card i{display:block;font-size:1.5rem;margin-bottom:.4rem;color:#94a3b8;transition:color .2s}
  .ppo-card small{display:block;font-weight:500;color:#94a3b8;font-size:.72rem;margin-top:.2rem}
  .ppo-card.sel{border-color:#01643D;background:#f0fdf4;color:#01643D;box-shadow:0 8px 18px -10px rgba(1,100,61,.45)}
  .ppo-card.sel i{color:#01643D}
  .ppo-seg{display:flex;background:#f1f5f9;border-radius:100px;padding:.25rem;gap:.25rem}
  .ppo-seg button{flex:1;border:none;background:transparent;border-radius:100px;padding:.55rem .9rem;font-weight:700;color:#64748b;cursor:pointer;transition:all .2s;font-size:.88rem}
  .ppo-seg button.sel{background:#fff;color:#01643D;box-shadow:0 2px 8px rgba(0,0,0,.08)}
  .ppo-stars{display:flex;gap:.4rem}
  .ppo-star{font-size:1.8rem;color:#e2e8f0;cursor:pointer;transition:all .15s}
  .ppo-star.sel{color:#f59e0b}
  .ppo-star:hover{transform:scale(1.15)}
  .ppo-sheet{max-height:56vh;overflow:auto;text-align:left;padding-right:.25rem}
  .ppo-sec{font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#01643D;margin:1.15rem 0 .5rem;display:flex;align-items:center;gap:.45rem}
  .ppo-sheet .ppo-sec:first-child{margin-top:0}
  .ppo-rows{display:grid;grid-template-columns:1fr 1fr;gap:.6rem}
  .ppo-item{background:#f8fafc;border:1px solid #eef2f6;border-radius:1rem;padding:.65rem .9rem}
  .ppo-item b{display:block;font-size:.68rem;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;font-weight:800;margin-bottom:.15rem}
  .ppo-item span{font-weight:700;color:#0f172a;font-size:.9rem}
  .ppo-chips{display:flex;flex-wrap:wrap;gap:.4rem}
  .ppo-chip{background:rgba(1,100,61,.08);color:#01643D;border:1px solid rgba(1,100,61,.22);border-radius:100px;padding:.3rem .8rem;font-weight:700;font-size:.8rem}
  .ppo-note{background:#eff6ff;border:1px solid #dbeafe;color:#1e3a8a;border-radius:1rem;padding:.7rem .95rem;font-size:.83rem;text-align:left;margin-bottom:1rem}
  .ppo-note.warn{background:#fff7ed;border-color:#fed7aa;color:#9a3412}
  @media(max-width:560px){.ppo-rows,.ppo-grid2,.ppo-cards{grid-template-columns:1fr}}`;
  const st = document.createElement("style");
  st.id = "ppo-styles";
  st.textContent = css;
  document.head.appendChild(st);
})();

/** Config compartida de SweetAlert (botones pill de marca). */
function ppoSwalCfg(danger = false, wide = false) {
  return {
    buttonsStyling: false,
    reverseButtons: true,
    customClass: {
      popup: "ppo-pop" + (wide ? " ppo-wide" : ""),
      actions: "ppo-actions",
      confirmButton: "ppo-btn " + (danger ? "ppo-btn-danger" : "ppo-btn-pri"),
      cancelButton: "ppo-btn ppo-btn-sec",
    },
  };
}

/** Encabezado visual de los diálogos. */
function ppoHead(icon, tone, title, sub) {
  return `<div class="ppo-head"><div class="ppo-head-ic ${tone}"><i class="${icon}"></i></div><div class="ppo-head-tx"><h4>${title}</h4>${sub ? `<small>${sub}</small>` : ""}</div></div>`;
}

function refrescarPanelOrganismo() {
  solicitudes();
  if (window.orgDashboard) {
    if (window.orgDashboard.loadSummary) window.orgDashboard.loadSummary();
    if (window.orgDashboard.loadPostulaciones) window.orgDashboard.loadPostulaciones();
    if (window.orgDashboard.loadHistorialAlumnos) window.orgDashboard.loadHistorialAlumnos();
  }
}

// Ver respuestas de la prepostulación
$(document).on("click", ".btn-ver-prepostulacion", function () {
  const idStudent = $(this).data("idstudent");
  const idSolicitud = $(this).data("idsolicitud");
  $.ajax({
    method: "POST",
    url: "controller/organismo/forms.php",
    data: { action: "getPrepostulacion", idStudent, idSolicitud },
    dataType: "json",
    success: function (d) {
      if (!d || !d.id) {
        Swal.fire({ html: ppoHead("fas fa-file-alt", "info", "Sin formulario", "Este alumno no cuenta con prepostulación registrada."), confirmButtonText: "Cerrar", ...ppoSwalCfg() });
        return;
      }
      const item = (label, val) => (val ? `<div class="ppo-item"><b>${label}</b><span>${escHtml(val)}</span></div>` : "");
      const herr = [
        ...(d.herramientas ? String(d.herramientas).split(",").map((h) => h.trim()).filter(Boolean) : []),
        ...(d.herramientas_otro ? [d.herramientas_otro] : []),
      ];
      const chips = herr.length
        ? `<div class="ppo-chips">${herr.map((h) => `<span class="ppo-chip"><i class="fas fa-check me-1"></i>${escHtml(h)}</span>`).join("")}</div>`
        : `<span class="text-muted" style="font-size:.85rem;">No indicó herramientas.</span>`;
      Swal.fire({
        html: `
          ${ppoHead("fas fa-id-card", "", "Prepostulación del alumno", "Respuestas del formulario de preselección")}
          <div class="ppo-sheet">
            <div class="ppo-sec"><i class="fas fa-user-graduate"></i> Perfil académico</div>
            <div class="ppo-rows">
              ${item("Licenciatura", d.licenciatura)}
              ${item("Disponibilidad de horario", d.disponibilidad_horario)}
              ${item("Modalidad", d.modalidad)}
              ${item("Disponibilidad de inicio", d.disponibilidad_inicio)}
            </div>
            <div class="ppo-sec"><i class="fas fa-tools"></i> Habilidades</div>
            <div class="ppo-rows" style="margin-bottom:.6rem;">
              ${item("Nivel de Office", d.nivel_office)}
              ${item("Nivel de inglés", d.nivel_ingles)}
              ${item("Equipo e internet (remoto)", d.equipo_remoto)}
            </div>
            ${chips}
            <div class="ppo-sec"><i class="fas fa-bullseye"></i> Intereses</div>
            <div class="ppo-rows">
              ${item("Área de interés", d.area_interes)}
              ${item("Acepta capacitación previa", d.acepta_capacitacion)}
              ${item("Objetivo principal", d.objetivo_practicas)}
            </div>
            <div class="ppo-sec"><i class="fas fa-video"></i> Entrevista</div>
            <div class="ppo-rows">
              ${item("Modalidad preferida", d.modalidad_entrevista_pref)}
              ${item("Horario propuesto", d.horario_propuesto)}
            </div>
          </div>`,
        confirmButtonText: "Cerrar",
        ...ppoSwalCfg(false, true),
      });
    },
    error: function () {
      Swal.fire("Error", "No se pudo cargar la prepostulación.", "error");
    },
  });
});

// Aceptar para entrevista → programar agenda
$(document).on("click", ".btn-aceptar-prepostulacion", function () {
  const idStudent = $(this).data("idstudent");
  const idSolicitud = $(this).data("idsolicitud");
  const hoy = new Date().toISOString().split("T")[0];
  Swal.fire({
    html: `
      ${ppoHead("fas fa-calendar-plus", "", "Programar entrevista", "Aceptarás a este candidato para entrevista")}
      <div class="ppo-note"><i class="fas fa-envelope me-1"></i> El alumno recibirá los datos por correo, junto con su <strong>carta de presentación</strong>.</div>
      <div class="ppo-grid2">
        <div class="ppo-fld"><label class="ppo-lbl">Fecha *</label><input type="date" id="ent-fecha" class="ppo-input" min="${hoy}"></div>
        <div class="ppo-fld"><label class="ppo-lbl">Hora *</label><input type="time" id="ent-hora" class="ppo-input"></div>
      </div>
      <div class="ppo-fld">
        <label class="ppo-lbl">Modalidad *</label>
        <div class="ppo-cards" id="ent-mod-cards">
          <div class="ppo-card sel" data-value="Presencial"><i class="fas fa-building"></i>Presencial<small>En las instalaciones</small></div>
          <div class="ppo-card" data-value="Virtual"><i class="fas fa-video"></i>Virtual<small>Meet / Teams</small></div>
        </div>
      </div>
      <div class="ppo-fld" id="ent-url-wrap" style="display:none;"><label class="ppo-lbl">URL de la sesión (Meet/Teams) *</label><input type="url" id="ent-url" class="ppo-input" placeholder="https://meet.google.com/…"></div>
      <div class="ppo-fld" id="ent-dir-wrap"><label class="ppo-lbl">Dirección de la empresa *</label><input type="text" id="ent-dir" class="ppo-input" placeholder="Calle, número, colonia, ciudad"></div>`,
    showCancelButton: true,
    confirmButtonText: '<i class="fas fa-calendar-check me-1"></i> Programar y notificar',
    cancelButtonText: "Cancelar",
    ...ppoSwalCfg(),
    didOpen: () => {
      const cards = document.querySelectorAll("#ent-mod-cards .ppo-card");
      const toggle = () => {
        const v = document.querySelector("#ent-mod-cards .ppo-card.sel")?.dataset.value || "Presencial";
        document.getElementById("ent-url-wrap").style.display = v === "Virtual" ? "block" : "none";
        document.getElementById("ent-dir-wrap").style.display = v === "Presencial" ? "block" : "none";
      };
      cards.forEach((c) => c.addEventListener("click", () => {
        cards.forEach((x) => x.classList.remove("sel"));
        c.classList.add("sel");
        toggle();
      }));
      toggle();
    },
    preConfirm: () => {
      const fecha = document.getElementById("ent-fecha").value;
      const hora = document.getElementById("ent-hora").value;
      const modalidad = document.querySelector("#ent-mod-cards .ppo-card.sel")?.dataset.value || "";
      const url = document.getElementById("ent-url").value.trim();
      const dir = document.getElementById("ent-dir").value.trim();
      if (!fecha || !hora) { Swal.showValidationMessage("Indica la fecha y la hora."); return false; }
      if (modalidad === "Virtual" && !url) { Swal.showValidationMessage("Indica la URL de la sesión virtual."); return false; }
      if (modalidad === "Presencial" && !dir) { Swal.showValidationMessage("Indica la dirección de la entrevista."); return false; }
      return { fecha, hora, modalidad, url_sesion: url, direccion: dir };
    },
  }).then((result) => {
    if (!result.isConfirmed) return;
    const v = result.value;
    $.ajax({
      method: "POST",
      url: "controller/organismo/forms.php",
      data: { action: "programarEntrevista", idStudent, idSolicitud, fecha: v.fecha, hora: v.hora, modalidad: v.modalidad, url_sesion: v.url_sesion, direccion: v.direccion },
      dataType: "json",
      success: function (r) {
        if (r.success) { Swal.fire("Entrevista programada", "Se notificará al alumno.", "success"); refrescarPanelOrganismo(); }
        else { Swal.fire("Error", r.message || "Ocurrió un problema.", "error"); }
      },
      error: function () { Swal.fire("Error", "No se pudo programar la entrevista.", "error"); },
    });
  });
});

// Rechazar prepostulación
$(document).on("click", ".btn-rechazar-prepostulacion", function () {
  const idStudent = $(this).data("idstudent");
  const idSolicitud = $(this).data("idsolicitud");
  Swal.fire({
    html: `
      ${ppoHead("fas fa-user-times", "warn", "Rechazar prepostulación", "El candidato no pasará a entrevista")}
      <div class="ppo-note warn"><i class="fas fa-info-circle me-1"></i> El alumno recibirá el motivo por correo y quedará libre para postularse a otras vacantes. <strong>No podrá volver a postularse a esta vacante.</strong></div>
      <div class="ppo-fld"><label class="ppo-lbl">Motivo del rechazo *</label><textarea id="pre-motivo" class="ppo-input" rows="3" placeholder="Explica brevemente el motivo (mínimo 10 caracteres)…"></textarea></div>`,
    showCancelButton: true,
    confirmButtonText: '<i class="fas fa-times me-1"></i> Confirmar rechazo',
    cancelButtonText: "Cancelar",
    ...ppoSwalCfg(true),
    preConfirm: () => {
      const m = document.getElementById("pre-motivo").value.trim();
      if (!m || m.length < 10) { Swal.showValidationMessage("El motivo debe tener al menos 10 caracteres."); return false; }
      return m;
    },
  }).then((result) => {
    if (!result.isConfirmed) return;
    $.ajax({
      method: "POST",
      url: "controller/organismo/forms.php",
      data: { action: "rechazarPrepostulacion", idStudent, idSolicitud, motivo: result.value },
      dataType: "json",
      success: function (r) {
        if (r.success) { Swal.fire("Prepostulación rechazada", "", "success"); refrescarPanelOrganismo(); }
        else { Swal.fire("Error", r.message || "Ocurrió un problema.", "error"); }
      },
      error: function () { Swal.fire("Error", "No se pudo rechazar la prepostulación.", "error"); },
    });
  });
});

// Ver entrevista programada
$(document).on("click", ".btn-ver-entrevista", function () {
  const idStudent = $(this).data("idstudent");
  const idSolicitud = $(this).data("idsolicitud");
  $.ajax({
    method: "POST",
    url: "controller/organismo/forms.php",
    data: { action: "getEntrevistaProgramada", idStudent, idSolicitud },
    dataType: "json",
    success: function (d) {
      if (!d || !d.id) {
        Swal.fire({ html: ppoHead("fas fa-calendar-day", "info", "Sin entrevista", "No hay entrevista programada para este candidato."), confirmButtonText: "Cerrar", ...ppoSwalCfg() });
        return;
      }
      const esVirtual = d.modalidad === "Virtual";
      const extra = esVirtual
        ? `<div class="ppo-item" style="grid-column:1/-1;"><b>Enlace de la sesión</b><span><a href="${escHtml(d.url_sesion)}" target="_blank" style="color:#01643D;">${escHtml(d.url_sesion)}</a></span></div>`
        : `<div class="ppo-item" style="grid-column:1/-1;"><b>Dirección</b><span>${escHtml(d.direccion)}</span></div>`;
      Swal.fire({
        html: `
          ${ppoHead("fas fa-calendar-day", "info", "Entrevista programada", "Datos enviados al alumno por correo")}
          <div class="ppo-rows">
            <div class="ppo-item"><b>Fecha</b><span>${escHtml(d.fecha)}</span></div>
            <div class="ppo-item"><b>Hora</b><span>${escHtml((d.hora || "").slice(0, 5))}</span></div>
            <div class="ppo-item" style="grid-column:1/-1;"><b>Modalidad</b><span><i class="fas ${esVirtual ? "fa-video" : "fa-building"} me-1" style="color:#01643D;"></i>${escHtml(d.modalidad)}</span></div>
            ${extra}
          </div>`,
        confirmButtonText: "Cerrar",
        ...ppoSwalCfg(),
      });
    },
    error: function () { Swal.fire("Error", "No se pudo cargar la entrevista.", "error"); },
  });
});

// Cerrar entrevista + evaluación
$(document).on("click", ".btn-cerrar-entrevista", function () {
  const idStudent = $(this).data("idstudent");
  const idSolicitud = $(this).data("idsolicitud");
  Swal.fire({
    html: `
      ${ppoHead("fas fa-clipboard-check", "", "Cerrar entrevista", "Registra cómo le fue al candidato")}
      <div class="ppo-grid2">
        <div class="ppo-fld">
          <label class="ppo-lbl">¿Llegó a tiempo?</label>
          <div class="ppo-seg" id="ce-tiempo"><button type="button" class="sel" data-value="1"><i class="fas fa-check me-1"></i>Sí, puntual</button><button type="button" data-value="0"><i class="fas fa-times me-1"></i>No</button></div>
        </div>
        <div class="ppo-fld">
          <label class="ppo-lbl">¿Presentación adecuada?</label>
          <div class="ppo-seg" id="ce-formal"><button type="button" class="sel" data-value="1"><i class="fas fa-check me-1"></i>Sí, adecuada</button><button type="button" data-value="0"><i class="fas fa-times me-1"></i>No</button></div>
        </div>
      </div>
      <div class="ppo-fld">
        <label class="ppo-lbl">Desempeño general</label>
        <div class="ppo-stars" id="ce-stars">
          ${[1, 2, 3, 4, 5].map((n) => `<i class="fas fa-star ppo-star sel" data-value="${n}"></i>`).join("")}
        </div>
      </div>
      <div class="ppo-fld"><label class="ppo-lbl">Comentarios</label><textarea id="ce-com" class="ppo-input" rows="2" placeholder="Observaciones sobre sus respuestas, actitud, etc."></textarea></div>`,
    showCancelButton: true,
    confirmButtonText: '<i class="fas fa-clipboard-check me-1"></i> Cerrar entrevista',
    cancelButtonText: "Cancelar",
    ...ppoSwalCfg(),
    didOpen: () => {
      // Segmentados Sí/No
      document.querySelectorAll("#ce-tiempo button, #ce-formal button").forEach((b) => {
        b.addEventListener("click", () => {
          b.parentElement.querySelectorAll("button").forEach((x) => x.classList.remove("sel"));
          b.classList.add("sel");
        });
      });
      // Estrellas 1-5
      const stars = Array.from(document.querySelectorAll("#ce-stars .ppo-star"));
      stars.forEach((s) => s.addEventListener("click", () => {
        const v = parseInt(s.dataset.value, 10);
        stars.forEach((x) => x.classList.toggle("sel", parseInt(x.dataset.value, 10) <= v));
      }));
    },
    preConfirm: () => {
      const c = document.querySelectorAll("#ce-stars .ppo-star.sel").length;
      if (c < 1) { Swal.showValidationMessage("Elige una calificación de 1 a 5 estrellas."); return false; }
      return {
        llego_a_tiempo: document.querySelector("#ce-tiempo button.sel")?.dataset.value || "0",
        llego_formal: document.querySelector("#ce-formal button.sel")?.dataset.value || "0",
        calificacion_respuestas: c,
        comentarios: document.getElementById("ce-com").value,
      };
    },
  }).then((result) => {
    if (!result.isConfirmed) return;
    const v = result.value;
    $.ajax({
      method: "POST",
      url: "controller/organismo/forms.php",
      data: { action: "cerrarEntrevista", idStudent, idSolicitud, llego_a_tiempo: v.llego_a_tiempo, llego_formal: v.llego_formal, calificacion_respuestas: v.calificacion_respuestas, comentarios: v.comentarios },
      dataType: "json",
      success: function (r) {
        if (r.success) { Swal.fire("Entrevista cerrada", "Ahora puedes aceptar o rechazar al alumno.", "success"); refrescarPanelOrganismo(); }
        else { Swal.fire("Error", r.message || "Ocurrió un problema.", "error"); }
      },
      error: function () { Swal.fire("Error", "No se pudo cerrar la entrevista.", "error"); },
    });
  });
});

// Decisión final: aceptar
$(document).on("click", ".btn-aceptar-final", function () {
  const idStudent = $(this).data("idstudent");
  const idSolicitud = $(this).data("idsolicitud");
  const hoyAf = new Date().toISOString().split("T")[0];
  Swal.fire({
    html: `
      ${ppoHead("fas fa-user-check", "", "Aceptar alumno", "¡El candidato se une a tu organización!")}
      <div class="ppo-note"><i class="fas fa-envelope me-1"></i> El alumno recibirá tu mensaje y la fecha de inicio por correo.</div>
      <div class="ppo-fld"><label class="ppo-lbl">Mensaje de aceptación *</label><textarea id="af-motivo" class="ppo-input" rows="3" placeholder="Ej. Cumple con el perfil y pasó la entrevista con éxito… (mínimo 10 caracteres)"></textarea></div>
      <div class="ppo-fld"><label class="ppo-lbl">Fecha de inicio *</label><input type="date" id="af-fecha" class="ppo-input" min="${hoyAf}"></div>`,
    showCancelButton: true,
    confirmButtonText: '<i class="fas fa-check me-1"></i> Confirmar aceptación',
    cancelButtonText: "Cancelar",
    ...ppoSwalCfg(),
    preConfirm: () => {
      const m = document.getElementById("af-motivo").value.trim();
      const f = document.getElementById("af-fecha").value;
      if (!m || m.length < 10) { Swal.showValidationMessage("El mensaje debe tener al menos 10 caracteres."); return false; }
      if (!f) { Swal.showValidationMessage("Indica la fecha de inicio."); return false; }
      return { motivo: m, fechaInicio: f };
    },
  }).then((result) => {
    if (!result.isConfirmed) return;
    $.ajax({
      method: "POST",
      url: "controller/organismo/forms.php",
      data: { action: "decisionFinalAceptar", idStudent, idSolicitud, motivo: result.value.motivo, fechaInicio: result.value.fechaInicio },
      dataType: "json",
      success: function (r) {
        if (r.success) { Swal.fire("Alumno aceptado", "", "success"); refrescarPanelOrganismo(); }
        else { Swal.fire("Error", r.message || "Ocurrió un problema.", "error"); }
      },
      error: function () { Swal.fire("Error", "No se pudo aceptar al alumno.", "error"); },
    });
  });
});

// Decisión final: rechazar
$(document).on("click", ".btn-rechazar-final", function () {
  const idStudent = $(this).data("idstudent");
  const idSolicitud = $(this).data("idsolicitud");
  Swal.fire({
    html: `
      ${ppoHead("fas fa-user-slash", "warn", "Rechazar alumno", "Decisión final tras la entrevista")}
      <div class="ppo-note warn"><i class="fas fa-info-circle me-1"></i> El alumno recibirá el motivo por correo, quedará libre para otras vacantes y <strong>no podrá volver a postularse a esta</strong>.</div>
      <div class="ppo-fld"><label class="ppo-lbl">Motivo del rechazo *</label><textarea id="rf-motivo" class="ppo-input" rows="3" placeholder="Explica brevemente el motivo (mínimo 10 caracteres)…"></textarea></div>`,
    showCancelButton: true,
    confirmButtonText: '<i class="fas fa-times me-1"></i> Confirmar rechazo',
    cancelButtonText: "Cancelar",
    ...ppoSwalCfg(true),
    preConfirm: () => {
      const m = document.getElementById("rf-motivo").value.trim();
      if (!m || m.length < 10) { Swal.showValidationMessage("El motivo debe tener al menos 10 caracteres."); return false; }
      return m;
    },
  }).then((result) => {
    if (!result.isConfirmed) return;
    $.ajax({
      method: "POST",
      url: "controller/organismo/forms.php",
      data: { action: "decisionFinalRechazar", idStudent, idSolicitud, motivo: result.value },
      dataType: "json",
      success: function (r) {
        if (r.success) { Swal.fire("Alumno rechazado", "", "success"); refrescarPanelOrganismo(); }
        else { Swal.fire("Error", r.message || "Ocurrió un problema.", "error"); }
      },
      error: function () { Swal.fire("Error", "No se pudo rechazar al alumno.", "error"); },
    });
  });
});