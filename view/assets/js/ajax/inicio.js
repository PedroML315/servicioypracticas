$(document).ready(function () {
  // Cuando el documento esté listo, llama a la función eventCards para cargar los eventos.
  eventCards();
  var idStudent = $("#idStudent").val();

  if (idStudent) {
    loadStudentDashboard(idStudent);
  }
});

function formatDateTime(dateTimeString) {
  // Función para formatear una fecha y hora dada en un formato más legible.

  const months = [
    "Enero",
    "Febrero",
    "Marzo",
    "Abril",
    "Mayo",
    "Junio",
    "Julio",
    "Agosto",
    "Septiembre",
    "Octubre",
    "Noviembre",
    "Diciembre",
  ];

  const date = new Date(dateTimeString); // Convierte la cadena de fecha y hora en un objeto Date.
  const day = date.getDate(); // Obtiene el día del mes.
  const month = months[date.getMonth()]; // Obtiene el mes y lo convierte a texto.
  const year = date.getFullYear(); // Obtiene el año.

  let hours = date.getHours(); // Obtiene la hora.
  const minutes = date.getMinutes(); // Obtiene los minutos.
  const ampm = hours >= 12 ? "PM" : "AM"; // Determina si es AM o PM.

  hours = hours % 12 || 12; // Convierte la hora al formato de 12 horas y ajusta para que 0 sea 12.

  const minutesFormatted = minutes < 10 ? "0" + minutes : minutes; // Formatea los minutos para que siempre tengan dos dígitos.

  return `${day} de ${month} del ${year}, ${hours}:${minutesFormatted} ${ampm}`; // Devuelve la fecha y hora formateada.
}

async function eventCards() {
  // Función para cargar y mostrar las tarjetas de eventos.

  const role = $("#role").val(); // Obtiene el rol del usuario (admin, student, etc.).
  const idStudent = $("#idStudent").val(); // Obtiene el ID del estudiante si aplica.

  $.ajax({
    // Realiza una petición AJAX para obtener los datos de los eventos.
    url: "controller/ajax/eventCards.php",
    type: "POST",
    dataType: "json",
    success: async function (response) {
      let eventsHtml = ""; // Variable para almacenar el HTML generado para las tarjetas de eventos.
      let i = 0;
      for (const event of response) {
        i++;
        let actionHtml = "";

        if (role === "student") {
          // Si el rol es 'student', verifica si el estudiante ya está postulado al evento.
          const isApplied = await checkApplicationStatus(
            idStudent,
            event.idEvent
          );

          actionHtml = isApplied
            ? `<button class="btn w-100 fw-semibold" disabled style="border-radius:.6rem;padding:.55rem;background:#e9ecef;color:#6c757d;"><i class="fas fa-check me-1"></i>Ya postulado</button>`
            : `<button onclick="applyEvent(${event.idEvent})" class="btn w-100 fw-semibold" style="background:linear-gradient(90deg,#17c3b2,#0ea5a5);color:#fff;border:none;border-radius:.6rem;padding:.55rem;"><i class="fas fa-paper-plane me-1"></i>Postularme</button>`;
        } else if (role === "admin") {
          // Si el rol es 'admin', agrega botones para editar y borrar el evento.
          actionHtml = `
            <div class="d-flex gap-2">
              <button onclick="editEvent(${event.idEvent})" class="btn btn-sm flex-fill fw-semibold" style="background:#e8f3ff;color:#0d6efd;border:none;border-radius:.55rem;"><i class="fas fa-edit me-1"></i>Editar</button>
              <button onclick="deleteEvent(${event.idEvent})" class="btn btn-sm flex-fill fw-semibold" style="background:#fee2e2;color:#dc3545;border:none;border-radius:.55rem;"><i class="fas fa-trash me-1"></i>Borrar</button>
            </div>`;
        } else {
          // Si el rol es otro (por ejemplo, un usuario regular), agrega un botón para ver el evento.
          actionHtml = `
            <button onclick="lookCandidates(${event.idEvent})" class="btn w-100 fw-semibold" style="background:linear-gradient(135deg,#01643D,#2A7E5D);color:#fff;border:none;border-radius:.6rem;padding:.55rem;">
              <i class="fas fa-users me-1"></i>Ver candidatos
            </button>`;
        }

        // Construye la tarjeta de evento y espera a que se resuelva.
        const html = await buildEventCard(event, actionHtml);
        eventsHtml += html; // Acumula el HTML generado.
      }

      if (i == 0) {
        eventsHtml = `<div class="col-lg-12 mb-4">
                                    <div class="card shadow-sm h-100 border-0 rounded-lg">
                                        <div class="card-body d-flex flex-column">
                                        <p class="card-text text-muted">
                                            Sin eventos disponibles
                                        </p>
                                        </div>
                                    </div>
                                </div>
                                `;
      }

      // Una vez que se han generado todas las tarjetas, actualiza el HTML.
      updateEventsHtml(eventsHtml);
    },
  });
}

function getStudentsEvent(idEvent) {
  return $.ajax({
    url: "controller/ajax/ajax.forms.php",
    method: "POST",
    data: { idEvent: idEvent, search: "event", action: "studentEvents" },
    dataType: "json",
  });
}

function checkApplicationStatus(idStudent, idEvent) {
  // Función para verificar si un estudiante ya está postulado a un evento.

  return $.ajax({
    url: "controller/ajax/ajax.forms.php",
    type: "POST",
    data: {
      search: "event",
      action: "checkApplication",
      idStudent: idStudent,
      idEvent: idEvent,
    },
    dataType: "json",
  });
}

async function buildEventCard(event, actionHtml) {
  // Función para construir el HTML de una tarjeta de evento.
  const eventDateTime = new Date(`${event.date} ${event.start_time}`);
  const currentDateTime = new Date();

  // Verifica si la fecha del evento ya pasó
  if (eventDateTime < currentDateTime) {
    return ""; // Si la fecha ya pasó, no retorna nada
  }

  const formattedDateTime = formatDateTime(eventDateTime); // Formatea la fecha y hora del evento.
  let counter = 0;
  // Espera a que se resuelva la promesa para obtener el conteo de estudiantes.
  const count = await getStudentsEvent(event.idEvent);

  counter = count.students != null ? count.students : 0;
  const vacanciesAvailable = event.vacancies_available - counter;

  let role = $("#role").val();
  let idUser = $("#idUser").val();

  // Condiciones para la construcción de la tarjeta de evento
  // ── card template ──────────────────────────────────────────────
  const vacBar = Math.min(100, Math.round((vacanciesAvailable / (event.vacancies_available || 1)) * 100));
  const vacColor = vacanciesAvailable <= 0 ? '#dc3545' : vacanciesAvailable <= 3 ? '#f59e0b' : '#2A7E5D';

  const cardHtml = (actionHtmlInner) => `
    <div class="col-xl-4 col-md-6 col-12 mb-4">
      <div style="background:#fff;border-radius:1rem;box-shadow:0 2px 14px rgba(0,0,0,.08);border:1px solid #e9ecef;
                  display:flex;flex-direction:column;overflow:hidden;height:100%;transition:transform .2s,box-shadow .2s;"
           onmouseenter="this.style.transform='translateY(-4px)';this.style.boxShadow='0 8px 28px rgba(42,126,93,.15)'"
           onmouseleave="this.style.transform='';this.style.boxShadow='0 2px 14px rgba(0,0,0,.08)'">
        <!-- Franja superior -->
        <div style="background:linear-gradient(135deg,#01643D,#2A7E5D);padding:.9rem 1.1rem .75rem;">
          <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem;">
            <span style="font-size:1rem;font-weight:700;color:#fff;line-height:1.25;">${event.name}</span>
            <span style="background:rgba(255,255,255,.2);color:#fff;border-radius:1rem;padding:.18rem .65rem;
                         font-size:.72rem;font-weight:700;white-space:nowrap;flex-shrink:0;">
              <i class="fas fa-star me-1"></i>${event.points} pts
            </span>
          </div>
        </div>
        <!-- Cuerpo -->
        <div style="padding:1rem 1.1rem;flex:1;display:flex;flex-direction:column;gap:.55rem;">
          <div style="display:flex;align-items:flex-start;gap:.55rem;font-size:.84rem;color:#495057;">
            <i class="fas fa-map-marker-alt" style="color:#2A7E5D;margin-top:.15rem;flex-shrink:0;"></i>
            <span>${event.location}</span>
          </div>
          <div style="font-size:.82rem;color:#6c757d;line-height:1.5;overflow:hidden;max-height:3.5rem;
                       display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;"
               id="description-${event.idEvent}">${event.description}</div>
          <button class="btn btn-link p-0" style="font-size:.78rem;color:#2A7E5D;text-align:left;width:fit-content;"
                  onclick="toggleDescription(${event.idEvent})" id="toggleButton-${event.idEvent}">Ver más</button>
          <hr style="margin:.25rem 0;border-color:#f0f0f0;">
          <div style="display:flex;align-items:center;gap:.5rem;font-size:.82rem;color:#495057;">
            <i class="fas fa-calendar-alt" style="color:#2A7E5D;"></i>
            <span>${formattedDateTime}</span>
          </div>
          <!-- Vacantes con barra visual -->
          <div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.3rem;">
              <span style="font-size:.78rem;color:#6c757d;"><i class="fas fa-users me-1"></i>Vacantes disponibles</span>
              <span style="font-size:.82rem;font-weight:700;color:${vacColor};">${vacanciesAvailable}</span>
            </div>
            <div style="height:5px;border-radius:3px;background:#e9ecef;overflow:hidden;">
              <div style="height:5px;border-radius:3px;background:${vacColor};width:${vacBar}%;transition:width .4s;"></div>
            </div>
          </div>
          <div class="mt-auto pt-1">${actionHtmlInner}</div>
        </div>
      </div>
    </div>`;

  if (idUser == event.idUser && role == "teacher") {
    return cardHtml(actionHtml);
  } else if (role != "teacher") {
    if (
      role == "student" &&
      vacanciesAvailable <= 0 &&
      actionHtml !=
        '<button class="btn w-100 fw-semibold" disabled style="border-radius:.6rem;padding:.55rem;background:#e9ecef;color:#6c757d;"><i class="fas fa-check me-1"></i>Ya postulado</button>'
    ) {
      return "";
    } else {
      return cardHtml(actionHtml);
    }
  } else {
    return "";
  }
}

function updateEventsHtml(htmlContent) {
  // Función para actualizar el HTML de la lista de eventos.

  $(".events").html(htmlContent); // Inserta el contenido HTML en el contenedor de eventos.
}

function applyEvent(idEvent) {
  // Mostrar el modal de confirmación primero
  $("#applyEventModal .modal-body").html(
    "<p>¿Estás seguro de que deseas registrarte a este evento?</p>"
  );

  // Configurar el botón de confirmación para ejecutar la solicitud AJAX
  $("#applyEventModal .btn-primary")
    .off("click")
    .on("click", function () {
      const idStudent = $("#idStudent").val(); // Obtiene el ID del estudiante.

      $.ajax({
        url: "controller/ajax/ajax.forms.php",
        type: "POST",
        data: {
          idEvent: idEvent,
          search: "event",
          idStudent: idStudent,
          action: "applyEvent",
        },
        success: function (response) {
          // Maneja la respuesta del servidor después de intentar postularse al evento.
          $("#applyEventModal .modal-body").html(
            response
              ? "<p>Te has postulado exitosamente al evento.</p>"
              : "<p>Hubo un problema al postularte. Intenta de nuevo.</p>"
          );

          eventCards(); // Recarga la lista de eventos para reflejar el estado actualizado.
          $("#applyEventModal").modal("hide");
        },
      });
    });

  $("#applyEventModal").modal("show"); // Muestra el modal para confirmar.
}

function editEvent(idEvent) {
  $.ajax({
    url: "controller/ajax/ajax.getEvent.php",
    method: "POST",
    data: { idEvent: idEvent },
    dataType: "json",
    success: function (event) {
      options(event.eventTypeId); // Pasar el idEventType a la función options
      $("#editEventId").val(event.idEvent);
      $("#editEventName").val(event.eventName);
      $("#editDate").val(event.date);
      $("#editLocation").val(event.location);
      $("#editStartTime").val(event.start_time);
      $("#editEndTime").val(event.end_time);
      $("#editPoints").val(event.points);
      $("#editVacanciesAvailable").val(event.vacancies_available);
      $("#editDescription").val(event.description);
      $("#editEventModal").modal("show");
    },
  });
}

function lookEvent(event) {}

function lookCandidates(event) {
  $.ajax({
    url: "controller/ajax/ajax.forms.php",
    method: "POST",
    data: { idEvent: event, search: "event", action: "lookCandidates" },
    dataType: "json",
    success: function (students) {
      let user = $("#idUser").val();
      let html = "";

      if (!students || students.length === 0) {
        html = `<div style="text-align:center;padding:2.5rem 1rem;color:#adb5bd;">
          <i class="fas fa-user-slash" style="font-size:2.5rem;display:block;margin-bottom:.75rem;"></i>
          <p style="margin:0;font-size:.95rem;">Sin candidatos registrados aún</p>
        </div>`;
      } else {
        students.forEach(function (student, index) {
          let initials = (student.firstname || "?").charAt(0).toUpperCase() +
                         (student.lastname || "").charAt(0).toUpperCase();
          let avatarColors = ["#2A7E5D","#16697a","#6366f1","#8DB94A","#f59e0b"];
          let bg = avatarColors[index % avatarColors.length];

          let statusBadge = "";
          let actions = "";

          if (student.status == 1) {
            statusBadge = `<span style="background:#fef9c3;color:#a16207;border-radius:1rem;padding:.18rem .65rem;font-size:.72rem;font-weight:700;"><i class="fas fa-clock me-1"></i>En evento</span>`;
            actions = `
              <button class="btn btn-sm fw-semibold px-3" style="background:#d1fae5;color:#065f46;border:none;border-radius:.55rem;"
                onclick="aprobar(1,${event}, ${student.idStudent}, ${user})"><i class="fas fa-check me-1"></i>Aprobar</button>
              <button class="btn btn-sm fw-semibold px-3" style="background:#fee2e2;color:#b91c1c;border:none;border-radius:.55rem;"
                onclick="aprobar(0,${event}, ${student.idStudent}, ${user})"><i class="fas fa-times me-1"></i>Rechazar</button>`;
          } else if (student.status == 2) {
            statusBadge = `<span style="background:#d1fae5;color:#065f46;border-radius:1rem;padding:.18rem .65rem;font-size:.72rem;font-weight:700;"><i class="fas fa-check me-1"></i>Aprobado</span>`;
          } else if (student.status == 3) {
            statusBadge = `<span style="background:#fee2e2;color:#b91c1c;border-radius:1rem;padding:.18rem .65rem;font-size:.72rem;font-weight:700;"><i class="fas fa-times me-1"></i>Rechazado</span>`;
          } else {
            statusBadge = `<span style="background:#ede9fe;color:#6d28d9;border-radius:1rem;padding:.18rem .65rem;font-size:.72rem;font-weight:700;"><i class="fas fa-hourglass-half me-1"></i>Pendiente</span>`;
            actions = `
              <button class="btn btn-sm fw-semibold px-3" style="background:#d1fae5;color:#065f46;border:none;border-radius:.55rem;"
                onclick="accept(1,${event}, ${student.idStudent}, ${user})"><i class="fas fa-check me-1"></i>Aceptar</button>
              <button class="btn btn-sm fw-semibold px-3" style="background:#fee2e2;color:#b91c1c;border:none;border-radius:.55rem;"
                onclick="accept(0,${event}, ${student.idStudent}, ${user})"><i class="fas fa-times me-1"></i>Rechazar</button>`;
          }

          html += `
            <div style="display:flex;align-items:center;gap:.9rem;padding:.85rem 1rem;border:1.5px solid #e9ecef;
                         border-radius:.8rem;margin-bottom:.6rem;background:#fff;transition:border-color .2s;"
                 onmouseenter="this.style.borderColor='#2A7E5D'" onmouseleave="this.style.borderColor='#e9ecef'">
              <!-- Avatar -->
              <div style="width:44px;height:44px;border-radius:50%;background:${bg};color:#fff;
                           display:flex;align-items:center;justify-content:center;font-weight:700;
                           font-size:.95rem;flex-shrink:0;">${initials}</div>
              <!-- Info -->
              <div style="flex:1;min-width:0;">
                <div style="font-weight:700;font-size:.9rem;color:#1a2530;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                  ${student.firstname} ${student.lastname}
                  <span style="margin-left:.5rem;">${statusBadge}</span>
                </div>
                <div style="font-size:.78rem;color:#6c757d;margin-top:.15rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                  <i class="fas fa-envelope me-1"></i>${student.email}
                  &nbsp;&nbsp;<i class="fas fa-phone me-1"></i>${student.phone}
                </div>
              </div>
              <!-- Actions -->
              <div style="display:flex;gap:.45rem;flex-shrink:0;">${actions}</div>
            </div>`;
        });
      }

      // Inject into modal body and show
      $("#candidatesTable").hide();
      let container = $("#candidatesCardsContainer");
      if (!container.length) {
        $("#candidatesTable").after('<div id="candidatesCardsContainer"></div>');
        container = $("#candidatesCardsContainer");
      }
      container.html(html);
      // Reload after approve/reject
      container.off("click", "[data-reload-event]");
      $("#candidatesModal").modal("show");
    },
  });
}

function accept(status, event, student, user) {
  $.ajax({
    url: "controller/ajax/ajax.forms.php",
    method: "POST",
    data: {
      idEvent: event,
      idStudent: student,
      idUser: user,
      status: status,
      action: "acceptCandidate",
      search: "event",
    },
    success: function (response) {
      $("#candidatesModal").modal("hide");
      lookCandidates(event);
    },
  });
}

function aprobar(status, event, student, user) {
  $.ajax({
    url: "controller/ajax/ajax.forms.php",
    method: "POST",
    data: {
      idEvent: event,
      idStudent: student,
      idUser: user,
      status: status,
      action: "approveEvent",
      search: "event",
    },
    success: function (response) {
      $("#candidatesModal").modal("hide");
      lookCandidates(event);
    },
  });
}

$("#editEventForm").on("submit", function (event) {
  event.preventDefault();
  $.ajax({
    url: "controller/ajax/ajax.updateEvent.php",
    method: "POST",
    data: $("#editEventForm").serialize(),
    success: function (response) {
      $("#editEventModal").modal("hide");
      eventCards();
    },
  });
});

// Handle editing form submission
$("#editEventForm").on("submit", function (event) {
  handleFormSubmission(
    event,
    table,
    "controller/ajax/ajax.updateEvent.php",
    "#editEventForm",
    "#"
  );
});

function options(selectedEventTypeId) {
  $.ajax({
    url: "controller/ajax/ajax.forms.php",
    dataType: "json",
    type: "POST",
    data: {
      search: "event_types",
    },
    success: function (response) {
      var options = '<option value="">Seleccione un tipo de evento</option>';
      response.forEach(function (typeEvent) {
        options +=
          '<option value="' +
          typeEvent.idEventType +
          '">' +
          typeEvent.name +
          "</option>";
      });
      $("#eventTypeId").html(options);
      $("#editEventTypeId").html(options);

      if (selectedEventTypeId) {
        $("#editEventTypeId").val(selectedEventTypeId);
      }
    },
  });
}

function deleteEvent(idEvent) {
  // Función para borrar un evento.

  $("#deleteEventModal").modal("show"); // Muestra un modal para confirmar la eliminación.

  $("#deleteEventModal .btn-danger")
    .off("click")
    .on("click", function () {
      // Maneja la acción de confirmación cuando se hace clic en el botón de eliminar.
      $.ajax({
        url: "controller/ajax/ajax.deleteEvent.php",
        method: "POST",
        data: { idEvent: idEvent },
        success: function (response) {
          $("#deleteEventModal").modal("hide");
          eventCards(); // Recarga la lista de eventos.
        },
      });
    });
}

function loadStudentDashboard(student) {
  let eventList = $("#eventList");
  let totalPoints = 0;
  let minPoints = 0;

  const loginOn = $("#loginOn").val();
  if (loginOn == 0) {
    loadServicesActives();
    $("#firstLogModal").modal("show");
  } else {
    // Verificar si el alumno ya inició la fase 2
    checkFase2();
    showEvents(student, eventList, totalPoints, minPoints);
  }
}

function endSocialService(idStudent) {
  $.ajax({
    url: "controller/ajax/ajax.forms.php",
    type: "POST",
    data: {
      idStudent: idStudent,
      search: "student",
      action: "end social service",
    },
    dataType: "json",
    success: function (response) {
      // Asumiendo que response contiene el nombre del archivo o la ruta relativa
      var filePath = "./view/assets/documents/output/" + response;

      // Verificar la ruta generada
      console.log("File Path:", filePath);

      // Crear un enlace para descargar el archivo
      var link = document.createElement("a");
      link.href = filePath;
      link.download = response.split("/").pop(); // Obtener solo el nombre del archivo
      link.click();

      filePath2 =
        "./view/assets/documents/output/Carta_de_aceptacion_" + response;
      console.log("File Path:", filePath2);
      // Crear un enlace para descargar el archivo
      var link = document.createElement("a");
      link.href = filePath2;
      link.download = "Carta_de_aceptacion_" + response.split("/").pop(); // Obtener solo el nombre del archivo
      link.click();
    },
    error: function (xhr, status, error) {
      console.error("Error al descargar el archivo: ", status, error);
    },
  });
}

function toggleDescription(eventId) {
  const descriptionElement = document.getElementById(`description-${eventId}`);
  const toggleButton = document.getElementById(`toggleButton-${eventId}`);

  // Alternar entre agregar y remover la clase "expanded"
  if (descriptionElement.classList.contains("expanded")) {
    descriptionElement.classList.remove("expanded");
    toggleButton.textContent = "Ver más"; // Cambiar el texto del botón
  } else {
    descriptionElement.classList.add("expanded");
    toggleButton.textContent = "Ver menos"; // Cambiar el texto del botón
  }
}

function showEvents(student, eventList, totalPoints, minPoints) {
  $.ajax({
    url: "controller/ajax/ajax.forms.php",
    type: "POST",
    data: { search: "studentEvents", idStudent: student },
    dataType: "json",
    success: function (response) {
      eventList.empty(); // Limpia el contenido previo

      if (response && response.length > 0) {
        response.forEach((data) => {
          const listItem = $(`
            <li class="ss-event-item">
              <div class="ss-event-dot"><i class="fas fa-star"></i></div>
              <span class="ss-event-name">${data.eventName}</span>
              <span class="ss-event-pts">${data.points} pts</span>
            </li>
          `);
          eventList.append(listItem);
          totalPoints += data.points;
          minPoints = data.minPoints;
        });

        $("#totalPoints").html(
          `<strong>${totalPoints}</strong> / ${minPoints} puntos`
        );

        // New dashboard progress UI
        const pct = minPoints > 0 ? Math.min(100, Math.round(totalPoints / minPoints * 100)) : 0;
        $("#totalPointsNum").text(totalPoints);
        $("#totalPointsLabel").text(`de ${minPoints} puntos`);
        $("#ssProgressBar").css("width", pct + "%");
        $("#ssProgressPct").text(pct + "%");
        $("#ssProgressMax").text(minPoints + " pts requeridos");
        if (pct >= 100) $("#ssCompleteBadge").css("display","flex");

        if (totalPoints >= minPoints) {
          const idStudent = $("#idStudent").val();
          const seenKey = "achievementSeen_" + idStudent;
          if (!localStorage.getItem(seenKey)) {
            localStorage.setItem(seenKey, "1");
            showAchievementModal();
          } else {
            iniciarFase2();
          }
        }
      } else {
        eventList.html(
          '<li class="ss-empty"><i class="fas fa-calendar-times"></i><p>Sin eventos registrados aún</p></li>'
        );
      }
    },
    error: function () {
      eventList.html(
        '<li class="ss-empty"><i class="fas fa-exclamation-triangle" style="color:#f59e0b"></i><p>Error al cargar los eventos.</p></li>'
      );
    },
  });
}

function showAchievementModal() {
  let idStudent = $("#idStudent").val();
  $("#achievementModalLabel").text("¡Felicidades!");
  $("#achievementModalBody").html(`
    <div style="font-family:Arial,sans-serif;color:#333;line-height:1.6;padding:15px;background:#f8f9fa;border-radius:5px;">
      <p style="font-size:16px;font-weight:bold;color:#01643D;">¡Enhorabuena! Has completado el 100% de los puntos y horas requeridas. ¡Excelente desempeño!</p>
      <p style="font-size:14px;margin-top:10px;">Por favor, para continuar con el trámite deberás de ingresar a IJUMICH.</p>
    </div>
    <div class="d-grid gap-2">
      <button type="button" class="btn btn-success" onclick="iniciarFase2()">Iniciar trámites del servicio social</button>
    </div>
  `);
  $("#achievementModal").modal("show");
}

/* ═══════════════════════════════════════════════════════════════
   FASE 2 – Tramitación interna IJUMICH
   ═══════════════════════════════════════════════════════════════ */

/** Oculta la sección de eventos y muestra el flujo de trámites */
function iniciarFase2() {
  $("#achievementModal").modal("hide");
  $(".ss-wrap").hide();
  $("#int2Page").show();
  cargarHistorialInterno();
}

/** Comprueba al cargar si el alumno ya tiene historial interno y muestra la fase 2 automáticamente */
function checkFase2() {
  $.ajax({
    url: "controller/ajax/ajax.forms.php",
    type: "POST",
    data: { action: "get_historial_interno" },
    dataType: "json",
    success: function (data) {
      if (data && data.length > 0) {
        $(".ss-wrap").hide();
        $("#int2Page").show();
        renderInt2Timeline(data);
        updateInt2Progress(data);
      }
    }
  });
}

/* ── Actualiza el stepper de la fase 2 ── */
function updateInt2Progress(historial) {
  const praItems = historial.filter(r => r.tipo === 'carta_practicas_interno');
  const caItems  = historial.filter(r => r.tipo === 'carta_aceptacion_servicio');
  const srItems  = historial.filter(r => r.tipo === 'solicitud_registro');
  const rp1Items = historial.filter(r => r.tipo === 'reporte_parcial_1');
  const rp2Items = historial.filter(r => r.tipo === 'reporte_parcial_2');
  const rp3Items = historial.filter(r => r.tipo === 'reporte_parcial_3');
  const ccItems  = historial.filter(r => r.tipo === 'carta_conclusion_servicio');
  const clItems  = historial.filter(r => r.tipo === 'carta_liberacion_interno');
  const eupItems = historial.filter(r => r.tipo === 'evaluacion_unidad_productiva');
  const egItems  = historial.filter(r => r.tipo === 'evaluacion_global');

  const praAprobada       = praItems.some(r => r.status === 'aprobado');
  const praPendiente      = praItems.some(r => r.status === 'pendiente');
  const caAprobada        = caItems.some(r => r.status === 'aprobado');
  const caPendiente       = caItems.some(r => r.status === 'pendiente');
  const srAprobada        = srItems.some(r => r.status === 'aprobado');
  const srPendiente       = srItems.some(r => r.status === 'pendiente');
  const rp1Aprobado       = rp1Items.some(r => r.status === 'aprobado');
  const rp2Aprobado       = rp2Items.some(r => r.status === 'aprobado');
  const rp3Aprobado       = rp3Items.some(r => r.status === 'aprobado');
  const allReportsApproved = rp1Aprobado && rp2Aprobado && rp3Aprobado;
  const ccGenerada        = ccItems.length > 0;
  const clAprobada        = clItems.some(r => r.status === 'aprobado');
  const clPendiente       = clItems.some(r => r.status === 'pendiente');
  const eupAprobada       = eupItems.some(r => r.status === 'aprobado');
  const eupPendiente      = eupItems.some(r => r.status === 'pendiente');
  const egAprobada        = egItems.some(r => r.status === 'aprobado');
  const egPendiente       = egItems.some(r => r.status === 'pendiente');

  let step = 1;
  if      (egAprobada)          step = 10;
  else if (egPendiente)         step = 9;
  else if (eupAprobada)         step = 9;
  else if (eupPendiente)        step = 8;
  else if (clAprobada)          step = 8;
  else if (clPendiente)         step = 7;
  else if (ccGenerada)          step = 7;
  else if (allReportsApproved)  step = 6;
  else if (srAprobada)          step = 5;
  else if (srPendiente)         step = 4;
  else if (caAprobada)          step = 4;
  else if (caPendiente)         step = 3;
  else if (praAprobada)         step = 3;
  else if (praPendiente)        step = 2;

  // Stepper dots
  $('#int2Stepper .int2-step').each(function () {
    const s = parseInt($(this).data('step'));
    $(this).removeClass('done active');
    if      (s < step)   $(this).addClass('done');
    else if (s === step) $(this).addClass('active');
  });

  // Reset all cards
  $('.int2-step-cards .step-card').removeClass('current done-card unlocked');

  // ── Paso 1 ──
  $('#int2-card-step-1').addClass('unlocked' + (step === 1 ? ' current' : ' done-card'));

  // ── Paso 2 – Carta de prácticas ──
  const $a2 = $('#int2-action-step-2');
  if (praAprobada) {
    $('#int2-card-step-2').addClass('done-card unlocked');
    $a2.html('<span class="done-badge"><i class="fas fa-check-circle me-1"></i>Aprobada</span>');
  } else if (praPendiente) {
    $('#int2-card-step-2').addClass('done-card unlocked');
    $a2.html('<span class="pending-badge"><i class="fas fa-clock me-1"></i>En revisión</span>');
  } else {
    const rej = praItems.find(r => r.status === 'rechazado');
    if (rej) {
      $a2.html(`<div><span class="rejected-badge mb-2 d-block"><i class="fas fa-times-circle me-1"></i>Rechazada – intenta de nuevo</span><button class="btn-step-orange mt-1" id="btnCargarCartaPracticasInterno"><i class="fas fa-redo me-1"></i>Reintentar</button></div>`);
      bindInt2ModalBtns();
    }
    $('#int2-card-step-2').addClass('unlocked' + (step <= 2 ? ' current' : ''));
  }

  // ── Paso 3 – Carta de aceptación ──
  const $a3 = $('#int2-action-step-3');
  if (caAprobada) {
    const caItem = caItems.find(r => r.status === 'aprobado');
    const dlBtn  = caItem ? `<a href="controller/ajax/generarCartaAceptacionServicio.php?id=${caItem.id}" target="_blank" class="btn-step-primary mt-1"><i class="fas fa-file-download me-1"></i>Descargar Carta PDF</a>` : '';
    $('#int2-card-step-3').addClass('done-card unlocked');
    $a3.html(`<div><span class="done-badge"><i class="fas fa-check-circle me-1"></i>Aprobada</span>${dlBtn}</div>`);
  } else if (caPendiente) {
    $('#int2-card-step-3').addClass('done-card unlocked');
    $a3.html('<span class="pending-badge"><i class="fas fa-clock me-1"></i>En revisión</span>');
  } else if (praAprobada) {
    $('#int2-card-step-3').addClass('unlocked' + (step === 3 ? ' current' : ''));
    $a3.html('<span class="pending-badge" style="opacity:.7;"><i class="fas fa-hourglass-half me-1"></i>En preparación por UNIMO</span>');
    if (caItems.length === 0) {
      $.post('controller/ajax/ajax.forms.php', { action: 'solicitar_carta_aceptacion_interno' });
    }
  }

  // ── Paso 4 – Solicitud de Registro ──
  const $a4 = $('#int2-action-step-4');
  if (srAprobada) {
    const srItem = srItems.find(r => r.status === 'aprobado');
    const dlBtn  = srItem && srItem.archivo_path_firmado
      ? `<a href="${srItem.archivo_path_firmado}" target="_blank" class="btn-step-teal mt-1"><i class="fas fa-file-download me-1"></i>Descargar firmada por UNIMO</a>`
      : '';
    $('#int2-card-step-4').addClass('done-card unlocked');
    $a4.html(`<div><span class="done-badge"><i class="fas fa-check-circle me-1"></i>Aprobada</span>${dlBtn}</div>`);
  } else if (srPendiente) {
    $('#int2-card-step-4').addClass('done-card unlocked');
    $a4.html('<span class="pending-badge"><i class="fas fa-clock me-1"></i>En revisión</span>');
  } else if (caAprobada) {
    const rej = srItems.find(r => r.status === 'rechazado');
    if (rej) {
      $a4.html(`<div><span class="rejected-badge mb-2 d-block"><i class="fas fa-times-circle me-1"></i>Rechazada – intenta de nuevo</span><button class="btn-step-teal mt-1" id="btnCargarSolicitudRegistro"><i class="fas fa-redo me-1"></i>Reintentar</button></div>`);
      bindInt2ModalBtns();
    } else {
      $a4.html(`<button class="btn-step-teal" id="btnCargarSolicitudRegistro"><i class="fas fa-cloud-upload-alt me-1"></i>Subir solicitud</button>`);
      bindInt2ModalBtns();
    }
    $('#int2-card-step-4').addClass('unlocked' + (step === 4 ? ' current' : ''));
  }

  // ── Paso 5 – En servicio ──
  if (step >= 5) {
    if (allReportsApproved || step > 5) {
      $('#int2-card-step-5').addClass('done-card unlocked');
    } else {
      $('#int2-card-step-5').addClass('unlocked current');
    }
  }

  // ── Sub-pasos: Reportes Parciales ──
  function renderReporteCard(items, prevAprobado, cardId, actionId, btnId, lockLabel) {
    const aprobado  = items.some(r => r.status === 'aprobado');
    const pendiente = items.some(r => r.status === 'pendiente');
    const rechazado = items.find(r => r.status === 'rechazado');
    const $card   = $(`#${cardId}`);
    const $action = $(`#${actionId}`);
    $card.removeClass('current done-card unlocked');

    if (aprobado) {
      const item  = items.find(r => r.status === 'aprobado');
      const dlBtn = (item && item.archivo_path)
        ? `<a href="${item.archivo_path}" target="_blank" class="btn-step-violet mt-2" style="font-size:.8rem;"><i class="fas fa-file-download me-1"></i>Descargar firmado por UNIMO</a>`
        : '';
      $card.addClass('done-card unlocked');
      $action.html(`<div class="d-flex flex-column gap-1"><span class="done-badge"><i class="fas fa-check-circle me-1"></i>Aprobado</span>${dlBtn}</div>`);
    } else if (pendiente) {
      $card.addClass('done-card unlocked');
      $action.html('<span class="pending-badge"><i class="fas fa-clock me-1"></i>En revisión</span>');
    } else if (prevAprobado) {
      $card.addClass('unlocked current');
      if (rechazado) {
        $action.html(`<div><span class="rejected-badge mb-2 d-block"><i class="fas fa-times-circle me-1"></i>Rechazado – intenta de nuevo</span><button class="btn-step-violet mt-1" id="${btnId}"><i class="fas fa-redo me-1"></i>Reintentar</button></div>`);
      } else {
        $action.html(`<button class="btn-step-violet" id="${btnId}"><i class="fas fa-cloud-upload-alt me-1"></i>Subir reporte</button>`);
      }
      bindInt2ModalBtns();
    } else {
      $action.html(`<span class="pending-badge" style="opacity:.5;"><i class="fas fa-lock me-1"></i>${lockLabel}</span>`);
    }
  }

  if (srAprobada) {
    renderReporteCard(rp1Items, true,        'int2-card-step-4a', 'int2-action-step-4a', 'btnCargarReporte1', 'Pendiente paso anterior');
    renderReporteCard(rp2Items, rp1Aprobado, 'int2-card-step-4b', 'int2-action-step-4b', 'btnCargarReporte2', 'Pendiente de 1er reporte aprobado');
    renderReporteCard(rp3Items, rp2Aprobado, 'int2-card-step-4c', 'int2-action-step-4c', 'btnCargarReporte3', 'Pendiente de 2° reporte aprobado');
  }

  // ── Paso 6 – Carta de Conclusión de Servicio Social (generada por el alumno) ──
  const $a6 = $('#int2-action-step-6');
  if (ccGenerada) {
    $('#int2-card-step-6').addClass('done-card unlocked');
    $a6.html(`<div class="d-flex flex-column gap-1"><span class="done-badge"><i class="fas fa-check-circle me-1"></i>Generada</span><button class="btn-step-primary mt-1" id="btnRegen CartaConclusion"><i class="fas fa-file-download me-1"></i>Volver a descargar</button></div>`);
    bindInt2ModalBtns();
  } else if (allReportsApproved) {
    $('#int2-card-step-6').addClass('unlocked' + (step === 6 ? ' current' : ''));
    $a6.html(`<button class="btn-step-primary" id="btnAbrirCartaConclusion"><i class="fas fa-file-certificate me-1"></i>Generar carta</button>`);
    bindInt2ModalBtns();
  } else {
    $a6.html('<span class="pending-badge" style="opacity:.5;"><i class="fas fa-lock me-1"></i>Pendiente de los 3 reportes</span>');
  }

  // ── Paso 7 – Carta de liberación (solo después de carta conclusión generada) ──
  const $a7 = $('#int2-action-step-7');
  if (clAprobada) {
    $('#int2-card-step-7').addClass('done-card unlocked');
    $a7.html('<span class="done-badge"><i class="fas fa-check-circle me-1"></i>Aprobada</span>');
  } else if (clPendiente) {
    $('#int2-card-step-7').addClass('done-card unlocked');
    $a7.html('<span class="pending-badge"><i class="fas fa-clock me-1"></i>En revisión</span>');
  } else {
    const rej = clItems.find(r => r.status === 'rechazado');
    if (rej) {
      $a7.html(`<div><span class="rejected-badge mb-2 d-block"><i class="fas fa-times-circle me-1"></i>Rechazada – intenta de nuevo</span><button class="btn-step-teal mt-1" id="btnCargarCartaLiberacionInterno"><i class="fas fa-redo me-1"></i>Resubir carta</button></div>`);
      bindInt2ModalBtns();
    }
    if (ccGenerada || step >= 7) {
      $('#int2-card-step-7').addClass('unlocked' + (step === 7 ? ' current' : ''));
      if (!rej) {
        $a7.html(`<button class="btn-step-teal" id="btnCargarCartaLiberacionInterno"><i class="fas fa-cloud-upload-alt me-1"></i>Cargar carta</button>`);
        bindInt2ModalBtns();
      }
    } else if (!rej) {
      $a7.html('<span class="pending-badge" style="opacity:.5;"><i class="fas fa-lock me-1"></i>Pendiente de carta de conclusión</span>');
    }
  }

  // ── Paso 8 – Evaluación de la Unidad Productiva (solo tras carta liberación aprobada) ──
  const $a8 = $('#int2-action-step-8');
  if (eupAprobada) {
    const eupItem = eupItems.find(r => r.status === 'aprobado');
    const dlBtn   = (eupItem && eupItem.archivo_path_firmado)
      ? `<a href="${eupItem.archivo_path_firmado}" target="_blank" class="btn-step-teal mt-1"><i class="fas fa-file-download me-1"></i>Descargar firmado por UNIMO</a>`
      : '';
    $('#int2-card-step-8').addClass('done-card unlocked');
    $a8.html(`<div><span class="done-badge"><i class="fas fa-check-circle me-1"></i>Aprobada</span>${dlBtn}</div>`);
  } else if (eupPendiente) {
    $('#int2-card-step-8').addClass('done-card unlocked');
    $a8.html('<span class="pending-badge"><i class="fas fa-clock me-1"></i>En revisión</span>');
  } else if (clAprobada) {
    const rejEup = eupItems.find(r => r.status === 'rechazado');
    if (rejEup) {
      $a8.html(`<div><span class="rejected-badge mb-2 d-block"><i class="fas fa-times-circle me-1"></i>Rechazada – intenta de nuevo</span><button class="btn-step-primary mt-1" id="btnCargarEvaluacionUnidad"><i class="fas fa-redo me-1"></i>Reintentar</button></div>`);
      bindInt2ModalBtns();
    } else {
      $a8.html(`<button class="btn-step-primary" id="btnCargarEvaluacionUnidad"><i class="fas fa-cloud-upload-alt me-1"></i>Subir evaluación</button>`);
      bindInt2ModalBtns();
    }
    $('#int2-card-step-8').addClass('unlocked' + (step === 8 ? ' current' : ''));
  } else {
    $a8.html('<span class="pending-badge" style="opacity:.5;"><i class="fas fa-lock me-1"></i>Pendiente de carta de liberación aprobada</span>');
  }

  // ── Paso 9 – Evaluación Global (solo tras Evaluación Unidad aprobada) ──
  const $a9 = $('#int2-action-step-9');
  if (egAprobada) {
    const egItem = egItems.find(r => r.status === 'aprobado');
    const dlBtn  = (egItem && egItem.archivo_path_firmado)
      ? `<a href="${egItem.archivo_path_firmado}" target="_blank" class="btn-step-primary mt-1"><i class="fas fa-file-download me-1"></i>Descargar firmado por UNIMO</a>`
      : '';
    $('#int2-card-step-9').addClass('done-card unlocked');
    $a9.html(`<div><span class="done-badge"><i class="fas fa-check-circle me-1"></i>Aprobada</span>${dlBtn}</div>`);
  } else if (egPendiente) {
    $('#int2-card-step-9').addClass('done-card unlocked');
    $a9.html('<span class="pending-badge"><i class="fas fa-clock me-1"></i>En revisión</span>');
  } else if (eupAprobada) {
    const rejEg = egItems.find(r => r.status === 'rechazado');
    if (rejEg) {
      $a9.html(`<div><span class="rejected-badge mb-2 d-block"><i class="fas fa-times-circle me-1"></i>Rechazada – intenta de nuevo</span><button class="btn-step-primary mt-1" id="btnCargarEvaluacionGlobal"><i class="fas fa-redo me-1"></i>Reintentar</button></div>`);
      bindInt2ModalBtns();
    } else {
      $a9.html(`<button class="btn-step-primary" id="btnCargarEvaluacionGlobal"><i class="fas fa-cloud-upload-alt me-1"></i>Subir evaluación</button>`);
      bindInt2ModalBtns();
    }
    $('#int2-card-step-9').addClass('unlocked' + (step === 9 ? ' current' : ''));
  } else {
    $a9.html('<span class="pending-badge" style="opacity:.5;"><i class="fas fa-lock me-1"></i>Pendiente de Evaluación de Unidad aprobada</span>');
  }

  // ── Paso 10 – Liberado ──
  const $a10 = $('#int2-action-step-10');
  if (egAprobada) {
    $('#int2-card-step-10').addClass('done-card unlocked current');
    $a10.html('<span class="done-badge" style="font-size:.88rem;padding:.45rem 1.1rem;"><i class="fas fa-graduation-cap me-1"></i>¡Servicio Social Liberado!</span>');
  }
}

/* ── Timeline historial interno ── */
const int2TipoLabel   = {
  solicitud_registro:             'Solicitud de Registro IJUMICH',
  carta_practicas_interno:        'Carta de Finalización de Prácticas',
  carta_aceptacion_servicio:      'Carta de Aceptación de Servicio Social',
  reporte_parcial_1:              'Reporte Parcial 1 de 3',
  reporte_parcial_2:              'Reporte Parcial 2 de 3',
  reporte_parcial_3:              'Reporte Parcial 3 de 3',
  carta_conclusion_servicio:      'Carta de Conclusión de Servicio Social',
  carta_liberacion_interno:       'Carta de Liberación',
  evaluacion_unidad_productiva:   'Evaluación de la Unidad Productiva',
  evaluacion_global:              'Evaluación Global'
};
const int2StatusLabel = { pendiente: 'En revisión', aprobado: 'Aprobada', rechazado: 'Rechazada' };
const int2StatusIcon  = { pendiente: 'fas fa-clock', aprobado: 'fas fa-check', rechazado: 'fas fa-times' };

function renderInt2Timeline(data) {
  const $c = $('#int2TimelineContainer');
  if (!data || !data.length) {
    $c.html('<li style="list-style:none;text-align:center;color:#94a3b8;padding:1.5rem 0;"><i class="fas fa-inbox me-2"></i>Aún no has enviado ningún documento.<br><small>Comienza por el Paso 2.</small></li>');
    $('#int2HistorialBadge').text('Sin documentos');
    return;
  }
  $('#int2HistorialBadge').text(data.length + ' documento' + (data.length !== 1 ? 's' : ''));

  const items = data.map(function (r) {
    const tipo  = int2TipoLabel[r.tipo] || r.tipo;
    const fecha = r.created_at ? r.created_at.slice(0,10) : '';
    const icon  = int2StatusIcon[r.status] || 'fas fa-circle';
    let detalle = '';
    if (r.tipo === 'carta_aceptacion_servicio' && r.status === 'aprobado') {
      detalle = `<a href="controller/ajax/generarCartaAceptacionServicio.php?id=${r.id}" target="_blank" class="btn btn-sm btn-success mt-1"><i class="fas fa-file-download me-1"></i>Descargar Carta PDF</a>`;
    } else if (r.tipo === 'carta_conclusion_servicio') {
      const csrf = $('meta[name="csrf-token"]').attr('content') || '';
      detalle = `<div class="tc-sub"><a href="controller/ajax/generarCartaConclusionServicio.php?fecha_inicio=${r.fecha_inicio || ''}&fecha_fin=${r.fecha_fin || ''}&csrf_token=${encodeURIComponent(csrf)}" target="_blank" class="btn btn-sm btn-outline-success" style="font-size:.75rem;"><i class="fas fa-file-pdf me-1"></i>Volver a descargar PDF</a></div>`;
    } else if (r.tipo === 'solicitud_registro') {
      if (r.archivo_nombre) {
        const link = (r.archivo_path)
          ? `<a href="${r.archivo_path}" target="_blank" rel="noopener"><i class="fas fa-file me-1"></i>${r.archivo_nombre}</a>`
          : `<span><i class="fas fa-file me-1"></i>${r.archivo_nombre}</span>`;
        detalle = `<div class="tc-sub">${link}</div>`;
      }
      if (r.status === 'aprobado' && r.archivo_path_firmado) {
        detalle += `<div class="tc-sub mt-1"><a href="${r.archivo_path_firmado}" target="_blank" class="btn btn-sm btn-outline-success" style="font-size:.75rem;"><i class="fas fa-stamp me-1"></i>Descargar firmada por UNIMO</a></div>`;
      }
    } else if ((
        r.tipo === 'carta_practicas_interno' ||
        r.tipo === 'carta_liberacion_interno' ||
        r.tipo === 'reporte_parcial_1' ||
        r.tipo === 'reporte_parcial_2' ||
        r.tipo === 'reporte_parcial_3' ||
        r.tipo === 'evaluacion_unidad_productiva' ||
        r.tipo === 'evaluacion_global'
      ) && r.archivo_nombre) {
      const link = (r.status === 'aprobado' && r.archivo_path)
        ? `<a href="${r.archivo_path}" target="_blank" rel="noopener"><i class="fas fa-file-download me-1"></i>${r.archivo_nombre}</a>`
        : `<span><i class="fas fa-file me-1"></i>${r.archivo_nombre}</span>`;
      detalle = `<div class="tc-sub">${link}</div>`;
      // Si el admin subió la versión firmada (reportes parciales)
      if (r.status === 'aprobado' && r.archivo_path_firmado) {
        detalle += `<div class="tc-sub mt-1"><a href="${r.archivo_path_firmado}" target="_blank" class="btn btn-sm btn-outline-success" style="font-size:.75rem;"><i class="fas fa-stamp me-1"></i>Descargar firmado por UNIMO</a></div>`;
      }
    }
    const comentario = r.comentario_admin
      ? `<div class="tc-comment"><i class="fas fa-comment-alt me-1"></i>${r.comentario_admin}</div>` : '';
    return `
      <li class="timeline-item">
        <div class="timeline-dot ${r.status}"><i class="${icon}"></i></div>
        <div class="timeline-content">
          <div class="tc-title">${tipo} <span class="fw-normal text-muted">– ${int2StatusLabel[r.status] || r.status}</span></div>
          <div class="tc-sub">${fecha}</div>
          ${detalle}${comentario}
        </div>
      </li>`;
  }).join('');
  $c.html(items);
}

function cargarHistorialInterno() {
  $.ajax({
    url: 'controller/ajax/ajax.forms.php',
    type: 'POST',
    data: { action: 'get_historial_interno' },
    dataType: 'json',
    success: function (data) {
      renderInt2Timeline(data || []);
      updateInt2Progress(data || []);
    },
    error: function () {
      $('#int2TimelineContainer').html('<li style="list-style:none;text-align:center;color:#dc3545;padding:1.5rem;">Error al cargar el historial.</li>');
    }
  });
}

function bindInt2ModalBtns() {
  $(document).off('click', '#btnCargarSolicitudRegistro').on('click', '#btnCargarSolicitudRegistro', function () {
    $('#modalSolicitudRegistro').modal('show');
  });
  $(document).off('click', '#btnCargarCartaPracticasInterno').on('click', '#btnCargarCartaPracticasInterno', function () {
    $('#modalCartaPracticasInterno').modal('show');
  });
  $(document).off('click', '#btnCargarCartaLiberacionInterno').on('click', '#btnCargarCartaLiberacionInterno', function () {
    $('#modalCartaLiberacionInterno').modal('show');
  });
  $(document).off('click', '#btnCargarEvaluacionUnidad').on('click', '#btnCargarEvaluacionUnidad', function () {
    $('#modalEvaluacionUnidad').modal('show');
  });
  $(document).off('click', '#btnCargarEvaluacionGlobal').on('click', '#btnCargarEvaluacionGlobal', function () {
    $('#modalEvaluacionGlobal').modal('show');
  });
  $(document).off('click', '#btnAbrirCartaConclusion').on('click', '#btnAbrirCartaConclusion', function () {
    $('#modalCartaConclusionServicio').modal('show');
  });
  $(document).off('click', '#btnRegenCartaConclusion').on('click', '#btnRegenCartaConclusion', function () {
    $('#modalCartaConclusionServicio').modal('show');
  });
  [1, 2, 3].forEach(function (num) {
    $(document).off('click', `#btnCargarReporte${num}`).on('click', `#btnCargarReporte${num}`, function () {
      $(`#modalReporte${num}`).modal('show');
    });
  });
}
bindInt2ModalBtns();

/* ── Formulario: solicitud de registro IJUMICH ── */
$(document).on('submit', '#formSolicitudRegistro', function (e) {
  e.preventDefault();
  const archivo = $('#solRegArchivo')[0].files[0];
  if (!archivo) { Swal.fire({ icon: 'warning', title: 'Archivo requerido', text: 'Debes seleccionar el PDF de Solicitud de Registro.', confirmButtonColor: '#01643D' }); return; }
  if (archivo.size > 5 * 1024 * 1024) { Swal.fire({ icon: 'warning', title: 'Archivo muy grande', text: 'El archivo no debe superar los 5 MB.', confirmButtonColor: '#01643D' }); return; }
  if (archivo.type !== 'application/pdf') { Swal.fire({ icon: 'warning', title: 'Solo PDF', text: 'El archivo debe ser un PDF.', confirmButtonColor: '#01643D' }); return; }
  const $btn = $('#btnSubmitSolicitudRegistro').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Subiendo...');
  const formData = new FormData();
  formData.append('action', 'cargar_solicitud_registro');
  formData.append('solicitud_registro', archivo);
  formData.append('observaciones', $('#solRegObs').val());
  $.ajax({
    url: 'controller/ajax/ajax.forms.php', type: 'POST',
    data: formData, processData: false, contentType: false, dataType: 'json',
    success: function (res) {
      $('#modalSolicitudRegistro').modal('hide');
      $('#formSolicitudRegistro')[0].reset();
      Swal.fire({ icon: 'success', title: '¡Solicitud enviada!', text: 'Tu solicitud fue registrada. El área administrativa la revisará y te la regresará firmada y sellada.', confirmButtonColor: '#01643D' });
      cargarHistorialInterno();
    },
    error: function () { Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error al subir el archivo.', confirmButtonColor: '#01643D' }); },
    complete: function () { $btn.prop('disabled', false).html('<i class="fas fa-cloud-upload-alt me-1"></i>Enviar solicitud'); }
  });
});

/* ── Formulario: carta de prácticas interno ── */
$(document).on('submit', '#formCartaPracticasInterno', function (e) {
  e.preventDefault();
  const archivo = $('#praInternoArchivo')[0].files[0];
  if (!archivo) { Swal.fire({ icon: 'warning', title: 'Archivo requerido', text: 'Debes seleccionar la carta de finalización de prácticas.', confirmButtonColor: '#d97706' }); return; }
  if (archivo.size > 5 * 1024 * 1024) { Swal.fire({ icon: 'warning', title: 'Archivo muy grande', text: 'El archivo no debe superar los 5 MB.', confirmButtonColor: '#d97706' }); return; }
  const $btn = $('#btnSubmitCartaPracticasInterno').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Subiendo...');
  const formData = new FormData();
  formData.append('action', 'cargar_carta_practicas_interno');
  formData.append('carta_practicas_interno', archivo);
  formData.append('observaciones', $('#praInternoObs').val());
  $.ajax({
    url: 'controller/ajax/ajax.forms.php', type: 'POST',
    data: formData, processData: false, contentType: false, dataType: 'json',
    success: function (res) {
      $('#modalCartaPracticasInterno').modal('hide');
      $('#formCartaPracticasInterno')[0].reset();
      Swal.fire({ icon: 'success', title: '¡Documento enviado!', text: 'Tu carta fue registrada. El área administrativa la revisará.', confirmButtonColor: '#d97706' });
      cargarHistorialInterno();
    },
    error: function () { Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error al subir el documento.', confirmButtonColor: '#d97706' }); },
    complete: function () { $btn.prop('disabled', false).html('<i class="fas fa-cloud-upload-alt me-1"></i>Enviar documento'); }
  });
});

/* ── Generar Carta de Conclusión de Servicio Social ── */
$(document).on('click', '#btnGenerarCartaConclusion', function () {
  const fi = $('#concl_fecha_inicio').val();
  const ff = $('#concl_fecha_fin').val();
  if (!fi || !ff) {
    Swal.fire({ icon: 'warning', title: 'Fechas requeridas', text: 'Ingresa la fecha de inicio y de término de tu servicio.', confirmButtonColor: '#01643D' });
    return;
  }
  if (new Date(ff) <= new Date(fi)) {
    Swal.fire({ icon: 'warning', title: 'Fecha inválida', text: 'La fecha de término debe ser posterior a la de inicio.', confirmButtonColor: '#01643D' });
    return;
  }
  const csrf = $('meta[name="csrf-token"]').attr('content') || '';
  const url  = `controller/ajax/generarCartaConclusionServicio.php?fecha_inicio=${encodeURIComponent(fi)}&fecha_fin=${encodeURIComponent(ff)}&csrf_token=${encodeURIComponent(csrf)}`;

  // Abrir en nueva pestaña para descarga
  const link = document.createElement('a');
  link.href     = url;
  link.target   = '_blank';
  link.download = '';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);

  $('#modalCartaConclusionServicio').modal('hide');
  Swal.fire({ icon: 'success', title: '¡PDF generado!', text: 'Tu Carta de Conclusión de Servicio Social se está descargando.', confirmButtonColor: '#01643D' });
  setTimeout(function () { cargarHistorialInterno(); }, 2000);
});

/* ── Formulario: carta de liberación interno ── */
$(document).on('submit', '#formCartaLiberacionInterno', function (e) {
  e.preventDefault();
  const archivo = $('#libInternoArchivo')[0].files[0];
  if (!archivo) { Swal.fire({ icon: 'warning', title: 'Archivo requerido', text: 'Debes seleccionar la carta de liberación.', confirmButtonColor: '#01643D' }); return; }
  if (archivo.size > 5 * 1024 * 1024) { Swal.fire({ icon: 'warning', title: 'Archivo muy grande', text: 'El archivo no debe superar los 5 MB.', confirmButtonColor: '#01643D' }); return; }
  const $btn = $('#btnSubmitCartaLiberacionInterno').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Cargando...');
  const formData = new FormData();
  formData.append('action', 'cargar_carta_liberacion_interno');
  formData.append('carta_liberacion_interno', archivo);
  formData.append('observaciones', $('#libInternoObs').val());
  $.ajax({
    url: 'controller/ajax/ajax.forms.php', type: 'POST',
    data: formData, processData: false, contentType: false, dataType: 'json',
    success: function (res) {
      $('#modalCartaLiberacionInterno').modal('hide');
      $('#formCartaLiberacionInterno')[0].reset();
      Swal.fire({ icon: 'success', title: '¡Carta cargada!', text: 'Tu carta fue registrada. Quedará liberada una vez validada.', confirmButtonColor: '#01643D' });
      cargarHistorialInterno();
    },
    error: function () { Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error al cargar el archivo.', confirmButtonColor: '#01643D' }); },
    complete: function () { $btn.prop('disabled', false).html('<i class="fas fa-cloud-upload-alt me-1"></i>Cargar carta'); }
  });
});

/* ── Formulario: Evaluación de la Unidad Productiva (Paso 8) ── */
$(document).on('submit', '#formEvaluacionUnidad', function (e) {
  e.preventDefault();
  const archivo = $('#evalUnidadArchivo')[0].files[0];
  if (!archivo) { Swal.fire({ icon: 'warning', title: 'Archivo requerido', text: 'Debes seleccionar el PDF de Evaluación de la Unidad Productiva.', confirmButtonColor: '#01643D' }); return; }
  if (archivo.size > 10 * 1024 * 1024) { Swal.fire({ icon: 'warning', title: 'Archivo muy grande', text: 'El archivo no debe superar los 10 MB.', confirmButtonColor: '#01643D' }); return; }
  if (archivo.type !== 'application/pdf') { Swal.fire({ icon: 'warning', title: 'Solo PDF', text: 'El archivo debe ser un PDF.', confirmButtonColor: '#01643D' }); return; }
  const $btn = $('#btnSubmitEvaluacionUnidad').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Subiendo...');
  const formData = new FormData();
  formData.append('action', 'cargar_evaluacion_unidad_productiva');
  formData.append('evaluacion_unidad_productiva', archivo);
  formData.append('observaciones', $('#evalUnidadObs').val());
  $.ajax({
    url: 'controller/ajax/ajax.forms.php', type: 'POST',
    data: formData, processData: false, contentType: false, dataType: 'json',
    success: function (res) {
      $('#modalEvaluacionUnidad').modal('hide');
      $('#formEvaluacionUnidad')[0].reset();
      Swal.fire({ icon: 'success', title: '¡Evaluación enviada!', text: 'Tu Evaluación de la Unidad Productiva fue registrada. El área administrativa la revisará, completará los campos y te la regresará firmada y sellada.', confirmButtonColor: '#01643D' });
      cargarHistorialInterno();
    },
    error: function () { Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error al subir el archivo.', confirmButtonColor: '#01643D' }); },
    complete: function () { $btn.prop('disabled', false).html('<i class="fas fa-cloud-upload-alt me-1"></i>Enviar evaluación'); }
  });
});

/* ── Formulario: Evaluación Global (Paso 9) ── */
$(document).on('submit', '#formEvaluacionGlobal', function (e) {
  e.preventDefault();
  const archivo = $('#evalGlobalArchivo')[0].files[0];
  if (!archivo) { Swal.fire({ icon: 'warning', title: 'Archivo requerido', text: 'Debes seleccionar el PDF de Evaluación Global.', confirmButtonColor: '#7c3aed' }); return; }
  if (archivo.size > 10 * 1024 * 1024) { Swal.fire({ icon: 'warning', title: 'Archivo muy grande', text: 'El archivo no debe superar los 10 MB.', confirmButtonColor: '#7c3aed' }); return; }
  if (archivo.type !== 'application/pdf') { Swal.fire({ icon: 'warning', title: 'Solo PDF', text: 'El archivo debe ser un PDF.', confirmButtonColor: '#7c3aed' }); return; }
  const $btn = $('#btnSubmitEvaluacionGlobal').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Subiendo...');
  const formData = new FormData();
  formData.append('action', 'cargar_evaluacion_global');
  formData.append('evaluacion_global', archivo);
  formData.append('observaciones', $('#evalGlobalObs').val());
  $.ajax({
    url: 'controller/ajax/ajax.forms.php', type: 'POST',
    data: formData, processData: false, contentType: false, dataType: 'json',
    success: function (res) {
      if (res && res.success) {
        $('#modalEvaluacionGlobal').modal('hide');
        $('#formEvaluacionGlobal')[0].reset();
        Swal.fire({ icon: 'success', title: '¡Evaluación enviada!', text: 'Tu Evaluación Global fue registrada. El área administrativa la revisará, la firmará con sello oficial y te la regresará.', confirmButtonColor: '#7c3aed' });
        cargarHistorialInterno();
      } else {
        Swal.fire({ icon: 'error', title: 'Error', text: (res && res.message) || 'No se pudo registrar el archivo.', confirmButtonColor: '#7c3aed' });
      }
    },
    error: function () { Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error al subir el archivo.', confirmButtonColor: '#7c3aed' }); },
    complete: function () { $btn.prop('disabled', false).html('<i class="fas fa-cloud-upload-alt me-1"></i>Enviar evaluación'); }
  });
});

/* ── Formularios: reportes parciales (1, 2, 3) ── */
[1, 2, 3].forEach(function (num) {
  $(document).on('submit', `#formReporte${num}`, function (e) {
    e.preventDefault();
    const archivo = $(`#repArchivo${num}`)[0].files[0];
    if (!archivo) {
      Swal.fire({ icon: 'warning', title: 'Archivo requerido', text: 'Debes seleccionar el reporte parcial.', confirmButtonColor: '#0b5911' });
      return;
    }
    if (archivo.size > 5 * 1024 * 1024) {
      Swal.fire({ icon: 'warning', title: 'Archivo muy grande', text: 'El archivo no debe superar los 5 MB.', confirmButtonColor: '#0b5911' });
      return;
    }
    const $btn = $(`#btnSubmitReporte${num}`).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Subiendo...');
    const formData = new FormData();
    formData.append('action', 'cargar_reporte_parcial');
    formData.append('numero_reporte', num);
    formData.append('reporte_parcial', archivo);
    formData.append('observaciones', $(`#repObs${num}`).val());
    $.ajax({
      url: 'controller/ajax/ajax.forms.php', type: 'POST',
      data: formData, processData: false, contentType: false, dataType: 'json',
      success: function () {
        $(`#modalReporte${num}`).modal('hide');
        $(`#formReporte${num}`)[0].reset();
        Swal.fire({ icon: 'success', title: '¡Reporte enviado!', text: 'Tu reporte fue registrado. UNIMO lo revisará, firmará y te lo regresará sellado.', confirmButtonColor: '#0b5911' });
        cargarHistorialInterno();
      },
      error: function () {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error al subir el reporte.', confirmButtonColor: '#0b5911' });
      },
      complete: function () {
        $btn.prop('disabled', false).html('<i class="fas fa-cloud-upload-alt me-1"></i>Enviar reporte');
      }
    });
  });
});

/* ═══════════════════════════════════════════════════════════════ */

function loadServicesActives() {
  let serviceSelection = $(".serviceSelection");
  $.ajax({
    url: "controller/ajax/ajax.forms.php",
    type: "POST",
    data: { search: "servicesTypeActives" },
    dataType: "json",
    success: function (response) {
      serviceSelection.empty();
      const radioHtml = `
                    <input type="radio" class="btn-check" name="tipoServicio" id="servicioNull" value="null" autocomplete="off" required>
                    <label class="btn btn-outline-default" for="servicioNull">Servicio social normal</label>
                `;
      serviceSelection.append(radioHtml);
      if (response && response.length > 0) {
        response.forEach(function (service) {
          const radioHtml = `
                        <input type="radio" class="btn-check" name="tipoServicio" id="servicio${service.idTipoSer}" value="${service.idTipoSer}" autocomplete="off" required>
                        <label class="btn btn-outline-success" for="servicio${service.idTipoSer}">${service.nombre}</label>
                    `;
          serviceSelection.append(radioHtml);
        });
      } else {
        serviceSelection.html(
          '<span class="text-muted">No hay servicios activos disponibles.</span>'
        );
      }
    },
  });
}