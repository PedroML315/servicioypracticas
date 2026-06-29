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
 * – Hora Inicio → habilita Hora Fin (sólo horas posteriores)
 */
function armarHorario(e) {
  // Referencias
  const $diaInicio = $("#diaInicio");
  const $diaFin = $("#diaFin");
  const $horaInicio = $("#horaInicio");
  const $horaFin = $("#horaFin");

  // Valores actuales
  const diaInicioVal = $diaInicio.val();
  const diaFinVal = $diaFin.val();
  const horaInicioVal = $horaInicio.val();

  /* --- 1. Reset en cascada ------------------------------------ */
  const changed = e?.target?.id || null; // quién disparó el cambio

  if (changed === "diaInicio") {
    // cambió el día de inicio
    $diaFin.val("");
    $horaInicio.val("");
    $horaFin.val("");
  }
  if (changed === "diaFin") {
    // cambió el día de fin
    $horaInicio.val("");
    $horaFin.val("");
  }
  if (changed === "horaInicio") {
    // cambió la hora de inicio
    $horaFin.val("");
  }

  /* --- 2. Habilitar / deshabilitar ---------------------------- */
  $diaFin.prop("disabled", !diaInicioVal); // sólo día fin
  $horaInicio.prop("disabled", !diaFinVal); // sólo hora inicio
  $horaFin.prop("disabled", !(diaFinVal && horaInicioVal)); // hora fin

  /* --- 3. Filtrar opciones de Día Fin ------------------------- */
  $diaFin.find("option").show(); // mostrar todo
  if (diaInicioVal) {
    const diasOrden = ["", "L", "M", "X", "J", "V", "S", "D"]; // '' para la opción vacía
    const idxInicio = diasOrden.indexOf(diaInicioVal);
    $diaFin.find("option").each(function () {
      const idx = diasOrden.indexOf(this.value);
      if (idx !== 0 && idx <= idxInicio) $(this).hide();
    });
  }

  /* --- 4. Filtrar opciones de Hora Fin ------------------------ */
  $horaFin.find("option").each(function () {
    if (!this.value) {
      $(this).prop("disabled", false).show();
      return;
    }
    if (horaInicioVal && this.value <= horaInicioVal) {
      $(this).prop("disabled", true).hide(); // anteriores o iguales → fuera
    } else {
      $(this).prop("disabled", false).show();
    }
  });
}

/* Asignar listeners (una sola vez) */
["#diaInicio", "#diaFin", "#horaInicio", "#horaFin"].forEach((sel) => {
  $(document).on("change", sel, armarHorario);
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

function solicitudes() {
  $.ajax({
    method: "POST",
    url: "controller/organismo/forms.php",
    data: { action: "getSolicitudes" },
    dataType: "json",
    success: function (response) {
      if (!Array.isArray(response) || response.length === 0) {
        $(".solicitudes").html(
          '<div class="alert alert-info">No hay solicitudes registradas.</div>'
        );
        return;
      }

      let html = '<div class="row gx-3 gy-4">';
      response.forEach((item) => {
        // formatea horario y apoyo
        const horario = `${item.dia_inicio} → ${item.dia_fin
          }, ${item.hora_inicio.slice(0, 5)}–${item.hora_fin.slice(0, 5)}`;
        const apoyo =
          item.ofrece_apoyo_economico == 1
            ? `<dt class="col-sm-4">Monto</dt><dd class="col-sm-8">${item.monto_apoyo}</dd>`
            : "";

        // badge de estado
        const badgeClass =
          item.aceptado == 1 ? "bg-success" : "bg-warning text-dark";
        const badgeText = item.aceptado == 1 ? "Aceptado" : "Pendiente";

        const buttons =
          item.aceptado == 1
            ? `<div>
                    <button class="btn btn-sm btn-outline-info me-1 look-users" title="Ver practicantes" data-id="${item.id}">
                        <i class="fas fa-users"></i>
                    </button>
                </div>`
            : `<div>
                    <button class="btn btn-sm btn-outline-primary edit-solicitud" title="Editar" data-id="${item.id}">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger me-1 delete-solicitud" title="Eliminar" data-id="${item.id}">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>`;

        html += `
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">${item.licenciatura}</h5>
                        ${buttons}
                    </div>
                    <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Empresa</dt><dd class="col-sm-8">${item.empresa || "–"
          }</dd>
                        <dt class="col-sm-4">Giro</dt><dd class="col-sm-8">${item.giro || "–"
          }</dd>
                        <dt class="col-sm-4">Ciudad</dt><dd class="col-sm-8">${item.ciudad || "–"
          }</dd>
                        <dt class="col-sm-4">Dirección</dt><dd class="col-sm-8">${item.direccion_practica || "–"
          }</dd>
                        
                        <dt class="col-sm-4">Responsable</dt>
                        <dd class="col-sm-8">
                        ${item.nombre_responsable}<br>
                        <small><i class="fas fa-phone-alt"></i> ${item.telefono
          }</small>
                        </dd>

                        <dt class="col-sm-4"># Practicantes</dt>
                        <dd class="col-sm-8">${item.num_practicantes}</dd>

                        <dt class="col-sm-4">Actividades</dt>
                        <dd class="col-sm-8">${item.actividades}</dd>

                        <dt class="col-sm-4">Capacidades</dt>
                        <dd class="col-sm-8">${item.capacidades || "–"}</dd>

                        <dt class="col-sm-4">Horario</dt><dd class="col-sm-8">${horario}</dd>

                        <dt class="col-sm-4">Apoyo Económico</dt>
                        <dd class="col-sm-8">${item.ofrece_apoyo_economico == 1 ? "Sí" : "No"
          }</dd>
                        ${apoyo}

                        <dt class="col-sm-4">Límite incorporación</dt>
                        <dd class="col-sm-8">${item.fecha_limite}</dd>
                    </dl>
                    </div>
                    <div class="card-footer text-muted d-flex justify-content-between align-items-center">
                        <small>Creado: ${formatFecha(item.created_at)}</small>
                        <div>
                            <span class="badge ${badgeClass}">${badgeText}</span>
                            <span class="badge bg-secondary">${item.modalidad
          }</span>
                        </div>
                    </div>
                </div>
            </div>
            `;
      });
      html += "</div>";
      $(".solicitudes").html(html);
    },
    error: console.error,
  });
}

// Maneja eventos de edición y eliminación de solicitudes
$(document).on("click", ".edit-solicitud", function () {
  const id = $(this).data("id");
  // Abrir modal de edición y cargar datos
  $.ajax({
    method: "POST",
    url: "controller/organismo/forms.php",
    data: { action: "getSolicitudById", id },
    dataType: "json",
    success: function (data) {
      if (!data || !data.id) {
        alert("No se pudo cargar la solicitud.");
        return;
      }
      // Rellena los campos del formulario de edición
      $("#editarIdSolicitud").val(data.id);
      $("#editarLicenciatura").val(data.licenciatura);
      $("#editarNumPract").val(data.num_practicantes);
      $("#editarActividades").val(data.actividades);
      $("#editarApoyoEconomico").val(data.ofrece_apoyo_economico == 1 ? "Sí" : "No").trigger("change");
      if (data.ofrece_apoyo_economico == 1) {
        $("#editarGrupoMonto").show();
        $("#editarMontoApoyo").val(data.monto_apoyo);
        $("#editarMontoApoyo").prop("required", true);
      } else {
        $("#editarGrupoMonto").hide();
        $("#editarMontoApoyo").val("");
        $("#editarMontoApoyo").prop("required", false);
      }
      $("#editarFechaLimite").val(data.fecha_limite);
      $("#editarModalidad").val(data.modalidad);
      $("#editarDiaInicio").val(data.dia_inicio);
      $("#editarDiaFin").val(data.dia_fin);
      $("#editarHoraInicio").val(data.hora_inicio.slice(0, 5));
      $("#editarHoraFin").val(data.hora_fin.slice(0, 5));
      $("#editarCapacidades").val(data.capacidades);
      $("#editarDireccionPractica").val(data.direccion_practica);
      $("#editarNombreResponsable").val(data.nombre_responsable);
      $("#editarContactoResponsable").val(data.telefono);

      // Mostrar el modal
      $("#editarPractModal").modal("show");

      // Asignar evento de envío del formulario de edición con id editarForm
      $("#editarForm").off("submit").on("submit", function (e) {
        e.preventDefault(); // evita envío normal

        const $form = $(this);
        const url = "controller/organismo/forms.php";
        const method = $form.attr("method");

        // Recolecta datos
        let formData = new FormData(this);
        formData.append("action", "updateSolicitud"); // agrega acción al FormData

        $.ajax({
          url: url,
          type: method.toUpperCase(),
          data: formData,
          contentType: false,
          processData: false,
          dataType: "json",
          beforeSend: function () {
            // opcional: deshabilitar botón para evitar doble envío
            $form
              .find('button[type="submit"]')
              .prop("disabled", true)
              .text("Actualizando...");
          },
          success: function (response) {
            if (response.success) {
              alert("Solicitud actualizada correctamente.");
              $("#editarPractModal").modal("hide");
              solicitudes(); // recarga las solicitudes
            } else {
              alert("Error: " + (response.message || "Ocurrió un problema."));
            }
          },
          error: function (xhr, status, error) {
            console.error(error);
            alert("Error al actualizar la solicitud. Intenta de nuevo.");
          },
          complete: function () {
            // vuelve a habilitar el botón
            $form
              .find('button[type="submit"]')
              .prop("disabled", false)
              .text("Actualizar Solicitud");
          },
        });
      });

    },
    error: function () {
      alert("Error al cargar la solicitud.");
    }
  });
});

$(document).on("click", ".delete-solicitud", function () {
  const id = $(this).data("id");
  if (confirm("¿Estás seguro de eliminar esta solicitud?")) {
    $.ajax({
      method: "POST",
      url: "controller/organismo/forms.php",
      data: { action: "deleteSolicitud", id },
      dataType: "json",
      success: function (response) {
        if (response.success) {
          alert("Solicitud eliminada correctamente.");
          solicitudes(); // recarga las solicitudes
        } else {
          alert("Error al eliminar la solicitud: " + response.message);
        }
      },
      error: function (xhr, status, error) {
        console.error(error);
        alert("Error al eliminar la solicitud. Intenta de nuevo.");
      },
    });
  }
});

// Formatea la fecha de creación
function formatFecha(fechaStr) {
  if (!fechaStr) return "–";
  const meses = [
    "Ene",
    "Feb",
    "Mar",
    "Abr",
    "May",
    "Jun",
    "Jul",
    "Ago",
    "Sep",
    "Oct",
    "Nov",
    "Dic",
  ];
  const [fecha, hora] = fechaStr.split(" ");
  if (!fecha || !hora) return fechaStr;
  const [anio, mes, dia] = fecha.split("-");
  let [hh, mm] = hora.split(":");
  let ampm = "am";
  let h = parseInt(hh, 10);
  if (h === 0) {
    h = 12;
  } else if (h >= 12) {
    ampm = "pm";
    if (h > 12) h -= 12;
  }
  return `${parseInt(dia, 10)}/${meses[parseInt(mes, 10) - 1]
    }/${anio} ${h}:${mm} ${ampm}`;
}

