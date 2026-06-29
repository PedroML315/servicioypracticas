$(function () {
  // ===================== Estado global (única carga de catálogos) =====================
  let AllDataEventsCache = {
    users: [],
    areas: [],
    services: [],
    event_types: [],
  };

  // ===================== Fechas mínimas para registro y edición de eventos =====================
  const minDate = getMinDate();
  setMinDate("#date", minDate);
  setMinDate("#editDate", minDate);

  // ===================== Cargar TODO en una sola llamada =====================
  loadAllDataEvents().then(() => {
    // Si existe el select de usuarios, ya quedó poblado
    // Si no existe, no pasa nada (no rompemos nada)
  });

  // ===================== Inicializar DataTables =====================
  const table = initializeDataTable();
  const tableTypesEvents = initializeDataTableTypesEvents(); // Inicializa vacío; se llenará con el caché
  // Llenar tabla tipos de evento con el caché cuando llegue la data
  $(document).on("AllDataEventsLoaded", function () {
    refreshEventTypesTable(tableTypesEvents);
  });

  // ===================== Registro de eventos =====================
  $("#registerEventForm").on("submit", function (event) {
    handleFormSubmission(
      event,
      table,
      "controller/ajax/ajax.addEvent.php",
      "#registerEventForm",
      "#registerEventModal",
      "start_time",
      "end_time"
    );
  });

  // ===================== Edición de eventos =====================
  $("#editEventForm").on("submit", function (event) {
    handleFormSubmission(
      event,
      table,
      "controller/ajax/ajax.updateEvent.php",
      "#editEventForm",
      "#editEventModal",
      "editStartTime",
      "editEndTime"
    );
  });

  // ===================== Mostrar modal de registro de eventos =====================
  $(".registerEventModal").on("click", function () {
    $("#registerEventModal").modal("show");
    // Asegurar opciones cargadas
    options();
  });

  // ===================== Mostrar modal de tipos de evento (Lista por defecto) =====================
  $(".registerEventTypeModal").on("click", function () {
    const modalEl = document.getElementById("registerEventTypeModal");
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
    const trigger = document.querySelector("#tabLista");
    if (trigger) bootstrap.Tab.getOrCreateInstance(trigger).show();
  });

  // ===================== Registro de tipos de evento (pestaña Nuevo) =====================
  $("#registerEventTypeForm").on("submit", function (event) {
    event.preventDefault();
    $.ajax({
      url: "controller/ajax/ajax.forms.php",
      method: "POST",
      data: $("#registerEventTypeForm").serialize(),
      success: function () {
        // Limpiar formulario
        $("#registerEventTypeForm")[0].reset();
        // Cambiar a pestaña Lista
        const trg = document.querySelector("#tabLista");
        if (trg) bootstrap.Tab.getOrCreateInstance(trg).show();
        // Refrescar catálogos y tabla local desde AllDataEvents
        reloadAllDataEvents().then(() => {
          refreshEventTypesTable(tableTypesEvents);
          // Repoblar selects dependientes
          applyAllDataToSelects();
        });
      },
    });
  });

  // ===================== Edición de tipos de evento (pestaña Editor) =====================
  $("#editEventTypeForm").on("submit", function (event) {
    event.preventDefault();
    $.ajax({
      url: "controller/ajax/ajax.forms.php",
      method: "POST",
      data: $("#editEventTypeForm").serialize(),
      success: function () {
        // Cambiar a pestaña Lista
        const trg = document.querySelector("#tabLista");
        if (trg) bootstrap.Tab.getOrCreateInstance(trg).show();
        // Refrescar catálogos y tabla local desde AllDataEvents
        reloadAllDataEvents().then(() => {
          refreshEventTypesTable(tableTypesEvents);
          applyAllDataToSelects();
        });
      },
    });
  });

  // ===================== Actualizar puntos al cambiar tipo de evento (sin más AJAX) =====================
  $("#eventTypeId, #editEventTypeId").on("change", function () {
    const pointsEvent = $(this).find("option:selected").data("puntos");
    const pointsField = this.id === "eventTypeId" ? "#points" : "#editPoints";
    $(pointsField).val(pointsEvent || "");
  });

  // ===================== Al cerrar modal de tipos, reset y volver a Lista =====================
  const etModal = document.getElementById("registerEventTypeModal");
  if (etModal) {
    etModal.addEventListener("hidden.bs.modal", () => {
      $("#editEventTypeForm")[0]?.reset();
      const trg = document.querySelector("#tabLista");
      if (trg) bootstrap.Tab.getOrCreateInstance(trg).show();
    });
  }

  // ===================== Disparar repintado de selects cuando se carguen los datos =====================
  $(document).on("AllDataEventsLoaded", applyAllDataToSelects);

  /* ===================== Fin document ready ===================== */
});

/* ===================== Carga ÚNICA de catálogos ===================== */
function loadAllDataEvents() {
  return $.ajax({
    type: "POST",
    url: "controller/ajax/ajax.forms.php",
    data: { search: "AllDataEvents" },
    dataType: "json",
    success: function (response) {
      // Esperamos las claves: users, areas, services, event_types
      AllDataEventsCache = {
        users: Array.isArray(response.users) ? response.users : [],
        areas: Array.isArray(response.areas) ? response.areas : [],
        services: Array.isArray(response.services) ? response.services : [],
        event_types: Array.isArray(response.event_types)
          ? response.event_types
          : [],
      };
      // Notificar que ya tenemos data
      $(document).trigger("AllDataEventsLoaded");
    },
  });
}

function reloadAllDataEvents() {
  // Alias para refrescar
  return loadAllDataEvents();
}

/* ===================== Aplicar catálogos a selects (sin AJAX extra) ===================== */
function applyAllDataToSelects(selectedEventTypeId) {
  // --- Usuarios ---
  if (document.getElementById("eventUser")) {
    const $select = $("#eventUser");
    $select.empty().append('<option value="">Selecciona al encargado</option>');
    AllDataEventsCache.users.forEach((u) => {
      $select.append(
        `<option value="${u.id}">${u.firstname} ${u.lastname}</option>`
      );
    });
  }

  // --- Áreas ---
  const areaSelects = ["#areaEncargada", "#editAreaEncargada"];
  areaSelects.forEach((id) =>
    $(id).empty().append('<option value="">Seleccione un área</option>')
  );
  AllDataEventsCache.areas.forEach((a) => {
    areaSelects.forEach((id) =>
      $(id).append(`<option value="${a.idArea}">${a.nameArea}</option>`)
    );
  });

  // --- Tipos de Servicio ---
  const serviceSelects = ["#eventTypeService", "#editEventTypeService"];
  serviceSelects.forEach((id) =>
    $(id)
      .empty()
      .append('<option value="">Seleccione un tipo de servicio</option>')
  );
  AllDataEventsCache.services.forEach((s) => {
    serviceSelects.forEach((id) =>
      $(id).append(`<option value="${s.idTipoSer}">${s.nombre}</option>`)
    );
  });

  // --- Tipos de Evento ---
  let optionsHtml = '<option value="">Seleccione un tipo de evento</option>';
  AllDataEventsCache.event_types.forEach((t) => {
    optionsHtml += `<option value="${t.idEventType}" data-puntos="${t.pointsPerEvent}">${t.name}</option>`;
  });
  $("#eventTypeId, #editEventTypeId").html(optionsHtml);

  if (selectedEventTypeId) {
    $("#editEventTypeId").val(selectedEventTypeId);
  }
}

/* ===================== Equivalente a options() pero con caché ===================== */
function options(selectedEventTypeId) {
  // Si aún no hay caché, cargamos y luego aplicamos
  if (!AllDataEventsCache.event_types.length) {
    loadAllDataEvents().then(() => applyAllDataToSelects(selectedEventTypeId));
  } else {
    applyAllDataToSelects(selectedEventTypeId);
  }
}

/* ===================== DataTables ===================== */
function initializeDataTable() {
  return $("#eventsTable").DataTable({
    ajax: {
      url: "controller/ajax/ajax.getEvents.php",
      dataSrc: "",
    },
    columns: [
      {
        data: null,
        render: function (data, type, row, meta) {
          return meta.row + 1;
        },
      },
      { data: "name" },
      { data: "eventName" },
      {
        data: null,
        render: function (data) {
          return formatDateTime(data.date + " " + data.start_time);
        },
      },
      { data: "location" },
      { data: "points" },
      { data: "vacancies_available" },
      {
        data: null,
        render: function (data) {
          return `<button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#viewDescriptionModal" onclick="showDescription('${data.description}')">descripción</button>`;
        },
      },
      {
        data: null,
        render: function (data) {
          return `
            <div class="btn-group btn-block" role="group" aria-label="Acciones">
              <button type="button" class="btn btn-primary" onclick="editEvent(${data.idEvent})"><i class="fad fa-edit"></i></button>
              <button type="button" class="btn btn-danger" onclick="deleteEvent(${data.idEvent})"><i class="fad fa-trash-alt"></i></button>
            </div>`;
        },
      },
    ],
    language: getDataTableLanguage(),
  });
}

function initializeDataTableTypesEvents() {
  // Se inicializa sin ajax; la data vendrá del caché AllDataEventsCache
  return $("#eventTypesTable").DataTable({
    data: [], // Se llenará cuando "AllDataEventsLoaded" dispare
    columns: [
      { data: "idEventType" },
      { data: "name" },
      { data: "nameArea" },
      { data: "pointsPerEvent" },
      { data: "benefitsPerYear" },
      {
        data: "nombreTipoServicio",
        render: function (d) {
          return d ? d : "N/A";
        },
      },
      {
        data: null,
        render: function (d) {
          return `
            <div class="btn-group" role="group" aria-label="Acciones">
              <button type="button" class="btn btn-primary"
                onclick="editTypeEvent(${d.idEventType}, '${d.name}', ${d.idArea}, ${d.pointsPerEvent}, ${d.benefitsPerYear}, ${d.typeService})">
                <i class="fad fa-edit"></i>
              </button>
              <button type="button" class="btn btn-danger"
                onclick="deleteTypeEvent(${d.idEventType})">
                <i class="fad fa-trash-alt"></i>
              </button>
            </div>`;
        },
      },
    ],
    language: getDataTableLanguage(),
  });
}

function refreshEventTypesTable(dtInstance) {
  if (!dtInstance) return;
  dtInstance.clear();
  dtInstance.rows.add(AllDataEventsCache.event_types || []);
  dtInstance.draw(false);
}

function getDataTableLanguage() {
  return {
    sProcessing: "Procesando...",
    sLengthMenu: "Mostrar _MENU_ registros",
    sZeroRecords: "No se encontraron resultados",
    sEmptyTable: "Ningún dato disponible en esta tabla",
    sInfo:
      "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
    sInfoEmpty: "Mostrando registros del 0 al 0 de un total de 0 registros",
    sInfoFiltered: "(filtrado de un total de _MAX_ registros)",
    sInfoPostFix: "",
    sSearch: "Buscar:",
    sUrl: "",
    sInfoThousands: ",",
    sLoadingRecords: "Cargando...",
    oPaginate: {
      sFirst: "Primero",
      sLast: "Último",
      sNext: "Siguiente",
      sPrevious: "Anterior",
    },
    oAria: {
      sSortAscending: ": Activar para ordenar la columna de manera ascendente",
      sSortDescending:
        ": Activar para ordenar la columna de manera descendente",
    },
    buttons: {
      copy: "Copiar",
      colvis: "Visibilidad",
    },
  };
}

/* ===================== Acciones Eventos ===================== */
function showDescription(description) {
  $("#descriptionContainer").html(description);
}

function editEvent(idEvent) {
  $.ajax({
    url: "controller/ajax/ajax.getEvent.php",
    method: "POST",
    data: { idEvent: idEvent },
    dataType: "json",
    success: function (event) {
      // Asegurar que las opciones están listas y seleccionar la correcta
      options(event.eventTypeId);

      $("#editEventId").val(event.idEvent);
      $("#editEventName").val(event.eventName);
      $("#editDate").val(event.date);
      $("#editLocation").val(event.location);
      $("#editStartTime").val(event.start_time);
      $("#editEndTime").val(event.end_time);
      $("#editPoints").val(event.points);
      $("#editVacanciesAvailable").val(event.vacancies_available);

      // Establecer el contenido en CKEditor para la descripción
      if (typeof editEditor !== "undefined" && editEditor) {
        editEditor.setData(event.description);
      }

      $("#editEventModal").modal("show");
    },
  });
}

function deleteEvent(idEvent) {
  if (confirm("¿Estás seguro de que quieres eliminar este evento?")) {
    $.ajax({
      url: "controller/ajax/ajax.deleteEvent.php",
      method: "POST",
      data: { idEvent: idEvent },
      success: function (response) {
        if (response === "success") {
          alert("El evento ha sido eliminado correctamente.");
          $("#eventsTable").DataTable().ajax.reload();
        } else {
          alert("Hubo un error al eliminar el evento.");
        }
      },
    });
  }
}

/* ===================== Catálogo: Tipos de Evento (acciones) ===================== */
function deleteTypeEvent(idEventType) {
  if (confirm("¿Estás seguro de que quieres eliminar este tipo de evento?")) {
    $.ajax({
      url: "controller/ajax/ajax.forms.php",
      method: "POST",
      data: {
        search: "event_types",
        deleteEventType: idEventType,
      },
      success: function () {
        // Tras eliminar, refrescamos la caché y la tabla
        reloadAllDataEvents().then(() => {
          refreshEventTypesTable($("#eventTypesTable").DataTable());
          applyAllDataToSelects();
        });
      },
    });
  }
}

function editTypeEvent(idEventType, name, idArea, pointsPerEvent, benefitsPerYear, typeService) {
  // Rellenar campos del editor
  $("#editEventType").val(idEventType);
  $("#editEventTypeName").val(name);
  $("#editAreaEncargada").val(idArea);
  $("#editEventTypePoints").val(pointsPerEvent);
  $("#editEventTypeBenefits").val(benefitsPerYear);
  $("#editEventTypeService").val(typeService);

  // Asegurar que el modal esté visible
  const modalEl = document.getElementById("registerEventTypeModal");
  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  modal.show();

  // Cambiar a pestaña "Editor"
  const trigger = document.querySelector("#tabEditor");
  if (trigger) bootstrap.Tab.getOrCreateInstance(trigger).show();
}

/* ===================== Utilidades ===================== */
function getMinDate() {
  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);
  const dd = String(tomorrow.getDate()).padStart(2, "0");
  const mm = String(tomorrow.getMonth() + 1).padStart(2, "0");
  const yyyy = tomorrow.getFullYear();
  return `${yyyy}-${mm}-${dd}`;
}

function setMinDate(selector, minDate) {
  $(selector).attr("min", minDate);
}

function handleFormSubmission(event, table, url, formSelector, modalSelector, startTimeName, endTimeName) {
  event.preventDefault();
  const form = $(formSelector);
  const startTime = form.find(`[name="${startTimeName}"]`).val();
  const endTime = form.find(`[name="${endTimeName}"]`).val();

  if (startTime >= endTime) {
    alert(
      "La hora de fin debe ser mayor que la hora de inicio y no pueden ser iguales."
    );
    return false;
  }

  $.ajax({
    url: url,
    method: "POST",
    data: form.serialize(),
    success: function (response) {
      if (response === "success") {
        alert("El evento se ha guardado correctamente.");
        form[0].reset();
        $(modalSelector).modal("hide");
        table.ajax.reload();
      } else {
        alert("Hubo un error al guardar el evento.");
      }
    },
  });
}

/* ===================== Formato de fecha ===================== */
function formatDateTime(dateTimeString) {
  const months = [
    "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
    "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
  ];
  const date = new Date(dateTimeString);
  const day = date.getDate();
  const month = months[date.getMonth()];
  const year = date.getFullYear();
  let hours = date.getHours();
  const minutes = date.getMinutes().toString().padStart(2, "0");
  const ampm = hours >= 12 ? "PM" : "AM";
  hours = hours % 12 || 12;
  return `${day} de ${month} del ${year}, ${hours}:${minutes} ${ampm}`;
}
