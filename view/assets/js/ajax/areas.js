$(document).ready(function () {
  // Initialize DataTable
  var table = initializeDataTable();

  // Handle add area form submission
  $("#addAreaForm").on("submit", function (e) {
    e.preventDefault();
    var nameArea = $("#addAreaName").val();

    $.ajax({
      url: "controller/ajax/ajax.forms.php",
      type: "POST",
      data: {
        search: "areas",
        addArea: true,
        nameArea: nameArea,
      },
      success: function (response) {
        $("#addAreaModal").modal("hide");
        table.ajax.reload();
        alert("Área registrada exitosamente");
      },
      error: function (xhr, status, error) {
        console.error("Error registrando el área:", error);
        alert(
          "Hubo un error al registrar el área. Por favor, inténtelo de nuevo."
        );
      },
    });
  });

  // Handle edit area form submission
  $("#editAreaForm").on("submit", function (e) {
    e.preventDefault();
    var idArea = $("#editAreaId").val();
    var nameArea = $("#editAreaName").val();

    $.ajax({
      url: "controller/ajax/ajax.forms.php",
      type: "POST",
      data: {
        search: "areas",
        editArea: idArea,
        nameArea: nameArea,
      },
      success: function (response) {
        $("#editAreaModal").modal("hide");
        table.ajax.reload();
        alert("Área actualizada exitosamente");
      },
      error: function (xhr, status, error) {
        console.error("Error actualizando el área:", error);
        alert(
          "Hubo un error al actualizar el área. Por favor, inténtelo de nuevo."
        );
      },
    });
  });

  // Handle delete area confirmation
  $("#confirmDeleteArea").on("click", function () {
    var idArea = $("#deleteAreaId").val();

    $.ajax({
      url: "controller/ajax/ajax.forms.php",
      type: "POST",
      data: {
        search: "areas",
        deleteArea: idArea,
      },
      success: function (response) {
        $("#deleteAreaModal").modal("hide");
        table.ajax.reload();
        alert("Área eliminada exitosamente");
      },
      error: function (xhr, status, error) {
        console.error("Error eliminando el área:", error);
        alert(
          "Hubo un error al eliminar el área. Por favor, inténtelo de nuevo."
        );
      },
    });
  });
});

function editArea(idArea) {
  $.ajax({
    url: "controller/ajax/ajax.forms.php",
    type: "POST",
    data: { search: "areas", area: idArea },
    success: function (data) {
      var area = JSON.parse(data);
      $("#editAreaId").val(area.idArea);
      $("#editAreaName").val(area.nameArea);
      $("#editAreaModal").modal("show");
    },
    error: function (xhr, status, error) {
      console.error("Error obteniendo el área:", error);
      alert(
        "Hubo un error al obtener los datos del área. Por favor, inténtelo de nuevo."
      );
    },
  });
}

function deleteArea(idArea) {
  $("#deleteAreaId").val(idArea);
  $("#deleteAreaModal").modal("show");
}

function initializeDataTable() {
  return $("#areasTable").DataTable({
    ajax: {
      type: "POST",
      url: "controller/ajax/ajax.forms.php",
      dataSrc: "",
      data: { search: "areas" },
    },
    columns: [
      { data: "idArea" },
      { data: "nameArea" },
      {
        data: null,
        render: function (data) {
          return `
                  <div class="btn-group btn-block" role="group" aria-label="Acciones">
                      <button type="button" class="btn btn-primary" onclick="editArea(${data.idArea})"><i class="fad fa-edit"></i></button>
                      <button type="button" class="btn btn-danger" onclick="deleteArea(${data.idArea})"><i class="fad fa-trash-alt"></i></button>
                  </div>
                  `;
          //<button type="button" class="btn btn-info" onclick="selectUser(${data.idArea})"><i class="fad fa-user-tag"></i></button>
        },
      },
    ],
    language: {
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
        sSortAscending:
          ": Activar para ordenar la columna de manera ascendente",
        sSortDescending:
          ": Activar para ordenar la columna de manera descendente",
      },
      buttons: {
        copy: "Copiar",
        colvis: "Visibilidad",
      },
    },
  });
}

function selectUser(idArea) {
  $.ajax({
    url: "controller/ajax/ajax.forms.php",
    type: "POST",
    data: { search: "users", idArea: idArea, action: "usersToAreas" },
    dataType: "json",
    success: function (data) {
      var options = '<option value="">Seleccione un usuario</option>';
      data.forEach(function (user) {
        options +=
          '<option value="' +
          user.idUser +
          '"' +
          (user.pertenece == 1 ? " selected" : "") +
          ">" +
          user.firstname +
          " " +
          user.lastname +
          "</option>";
      });
      $("#areaSelected").val(idArea);
      $("#userToArea").html(options);
      $("#userToAreaModal").modal("show");
    },
  });
}

$(document).ready(function () {
  $("#saveUserToArea").on("click", function () {
    var idArea = $("#areaSelected").val(); // Supongo que tienes un input o variable para el idArea
    var selectedUsers = $("#userToArea").val(); // Obtenemos los usuarios seleccionados

    $.ajax({
      url: "controller/ajax/ajax.forms.php",
      type: "POST",
      data: {
        search: "users",
        idArea: idArea,
        idUser: selectedUsers, // Aquí enviamos los usuarios seleccionados
        action: "updateUsersToArea",
      },
      success: function (response) {
        // Aquí puedes manejar la respuesta del servidor
        if (response == '"success"') {
          alert("Usuarios actualizados correctamente.");
          $("#userToAreaModal").modal("hide"); // Cerramos el modal si todo va bien
        } else {
          alert("Hubo un problema al actualizar los usuarios.");
        }
      },
      error: function () {
        alert("Error al intentar actualizar los usuarios.");
      },
    });
  });
});
