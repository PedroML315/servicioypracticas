import PracticesApp from "./practicesApp.js";
$(document).ready(() => new PracticesApp());

document.addEventListener("DOMContentLoaded", function () {
  const entrada = document.getElementById("horaEntrada");
  const salida = document.getElementById("horaSalida");
  const horasTrabajadas = document.getElementById("horasTrabajadas");
  const fechaAsistencia = document.getElementById("fechaAsistencia");

  if (fechaAsistencia) {
    const today = new Date();
    // Ajuste para zona horaria local (evitar problemas de UTC)
    today.setMinutes(today.getMinutes() - today.getTimezoneOffset());
    const todayStr = today.toISOString().split('T')[0];
    fechaAsistencia.setAttribute("max", todayStr);
    fechaAsistencia.value = todayStr;
  }

  function calcularHoras() {
    const hEntrada = entrada.value;
    const hSalida = salida.value;

    if (hEntrada && hSalida) {
      const [h1, m1] = hEntrada.split(":").map(Number);
      const [h2, m2] = hSalida.split(":").map(Number);

      let inicio = new Date(0, 0, 0, h1, m1, 0);
      let fin = new Date(0, 0, 0, h2, m2, 0);

      let diff = (fin - inicio) / (1000 * 60 * 60); // horas decimales

      if (diff < 0) diff += 24; // por si cruza medianoche

      const horas = Math.floor(diff);
      const minutos = Math.round((diff - horas) * 60);

      horasTrabajadas.textContent = `Horas trabajadas: ${horas}h ${minutos}m`;
    } else {
      horasTrabajadas.textContent = "Horas trabajadas: —";
    }
  }

  entrada.addEventListener("input", calcularHoras);
  salida.addEventListener("input", calcularHoras);

  // Optimized attendance form submission
  $("#formAsistencia").on("submit", function (e) {
    e.preventDefault();

    // ── Validación cliente: día autorizado ──────────────────────────────────
    const fechaVal = $("#fechaAsistencia").val();
    const diaInicioAttr = parseInt($("#fechaAsistencia").attr("data-dia-inicio"), 10);
    const diaFinAttr    = parseInt($("#fechaAsistencia").attr("data-dia-fin"),    10);

    if (fechaVal && !isNaN(diaInicioAttr) && !isNaN(diaFinAttr)) {
      const jsDay = new Date(fechaVal + "T00:00:00").getDay();
      const isoDay = jsDay === 0 ? 7 : jsDay;
      const DIA_ISO_MAP = { 1:"Lunes", 2:"Martes", 3:"Miércoles", 4:"Jueves", 5:"Viernes", 6:"Sábado", 7:"Domingo" };
      const diaInicioISO = diaInicioAttr === 0 ? 7 : diaInicioAttr;
      const diaFinISO    = diaFinAttr    === 0 ? 7 : diaFinAttr;
      if (isoDay < diaInicioISO || isoDay > diaFinISO) {
        Swal.fire({
          icon: "warning",
          title: "Día no autorizado",
          text: `El día seleccionado (${DIA_ISO_MAP[isoDay] ?? ""}) no está dentro del horario autorizado por el organismo externo.`,
          confirmButtonText: "Entendido",
        });
        return;
      }
    }

    const submitForm = () => {
      const formData = new FormData(this);
      formData.append("action", "registerAttendance");

      $.ajax({
        type: "POST",
        url: "controller/practices/students.php",
        data: formData,
        processData: false,
        contentType: false,
        dataType: "json",
        success(response) {
          const {
            success,
            icon = "error",
            title = "Error",
            message = "Error al registrar asistencia.",
          } = response;
          if (success) {
            $("#modalAsistencia").modal("hide");
            Swal.fire({
              icon: "success",
              title: "¡Éxito!",
              text: "Asistencia registrada correctamente.",
              confirmButtonText: "Aceptar",
            });
            // limpiar formulario
            $("#formAsistencia")[0].reset();
          } else {
            Swal.fire({
              icon,
              title,
              text: message,
              confirmButtonText: "Aceptar",
            });
          }
        },
        error() {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: "No se pudo registrar la asistencia. Intente de nuevo.",
            confirmButtonText: "Aceptar",
          });
        },
      });
    };

    // ── Validación cliente: máximo 4 horas (240 minutos) ───────────────────
    const entradaStr = $("#horaEntrada").val();
    const salidaStr  = $("#horaSalida").val();
    if (entradaStr && salidaStr) {
      const toMin = (t) => { const [h, m] = t.split(":").map(Number); return h * 60 + m; };
      const diffMin = toMin(salidaStr) - toMin(entradaStr);
      if (diffMin > 240) {
        Swal.fire({
          icon: "warning",
          title: "Atención: Exceso de horas",
          text: `El registro supera las 4 horas diarias (registrando ${Math.floor(diffMin/60)}h ${diffMin%60}m). Dependiendo del excedente esto podría generarte un strike. ¿Deseas continuar?`,
          showCancelButton: true,
          confirmButtonText: "Sí, registrar",
          cancelButtonText: "Corregir",
          confirmButtonColor: "#d33",
        }).then((result) => {
          if (result.isConfirmed) {
            submitForm();
          }
        });
        return;
      }
    }

    submitForm();
  });

$("#horaEntrada").on("input", updateSalidaInput);

});

// Entrada/Salida time logic
function updateSalidaInput() {
  const entradaVal = $("#horaEntrada").val();
  const salidaInput = $("#horaSalida");

  if (entradaVal) {
    salidaInput.prop("disabled", false).attr("min", entradaVal);
    if (salidaInput.val() && salidaInput.val() < entradaVal) {
      salidaInput.val("");
    }
  } else {
    salidaInput.prop("disabled", true).val("").removeAttr("min");
  }
}

// Inicialmente deshabilitar horaSalida si horaEntrada está vacío
$(document).ready(updateSalidaInput);


$('#formReporteParcial').on('submit', function (e) {
  e.preventDefault();

  const formData = new FormData(this);
  formData.append("action", "generatePartialReport");

  $.ajax({
    type: "POST",
    url: "controller/practices/students.php",
    data: formData,
    processData: false,
    contentType: false,
    dataType: "json",
    success(response) {
      const {
        success,
        icon = "error",
        title = "Error",
        message = "Error al generar el reporte.",
      } = response;
      if (success) {
        Swal.fire({
          icon: "success",
          title: "¡Éxito!",
          text: "Reporte generado correctamente.",
          confirmButtonText: "Aceptar",
        }).then(() => {
          $("#modalReporteParcial").modal("hide");
          location.reload();
        });
      } else {
        Swal.fire({
          icon,
          title,
          text: message,
          confirmButtonText: "Aceptar",
        });
      }
    },
    error() {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "No se pudo generar el reporte. Intente de nuevo.",
        confirmButtonText: "Aceptar",
      });
    },
  });
});

$('#formReporteFinal').on('submit', function (e) {
  e.preventDefault();

  const formData = new FormData(this);
  formData.append("action", "generateFinalReport");

  $.ajax({
    type: "POST",
    url: "controller/practices/students.php",
    data: formData,
    processData: false,
    contentType: false,
    dataType: "json",
    success(response) {
      const {
        success,
        icon = "error",
        title = "Error",
        message = "Error al generar el reporte.",
      } = response;
      if (success) {
        Swal.fire({
          icon: "success",
          title: "¡Éxito!",
          text: "Reporte generado correctamente.",
          confirmButtonText: "Aceptar",
        }).then(() => {
          $("#modalReporteFinal").modal("hide");
          location.reload();
        });
      } else {
        Swal.fire({
          icon,
          title,
          text: message,
          confirmButtonText: "Aceptar",
        });
      }
    },
    error() {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "No se pudo generar el reporte. Intente de nuevo.",
        confirmButtonText: "Aceptar",
      });
    },
  });
});

$('#modalEvalIntegralAlumno').on('show.bs.modal', function (event) {
  const button = $(event.relatedTarget);
  const hito = button.data('hito');
  $(this).find('#evalB_tipoHito').val(hito);
});

$('#formEvalIntegralAlumno').on('submit', function (e) {
  e.preventDefault();

  const formData = new FormData(this);
  formData.append("action", "saveEvalIntegralAlumno");

  $.ajax({
    type: "POST",
    url: "controller/practices/students.php",
    data: formData,
    processData: false,
    contentType: false,
    dataType: "json",
    success(response) {
      if (response.success) {
        Swal.fire({
          icon: "success",
          title: "¡Gracias!",
          text: "Evaluación guardada correctamente.",
          confirmButtonText: "Aceptar",
        }).then(() => {
          $("#modalEvalIntegralAlumno").modal("hide");
          location.reload();
        });
      } else {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: response.message || "Error al guardar la evaluación.",
          confirmButtonText: "Aceptar",
        });
      }
    },
    error() {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "No se pudo guardar la evaluación. Intente de nuevo.",
        confirmButtonText: "Aceptar",
      });
    },
  });
});