$(document).ready(function () {
  // Inicializar DataTable
  var table = $("#degreesTable").DataTable({
    ajax: {
      type: "POST",
      url: "controller/ajax/ajax.forms.php",
      dataSrc: "",
      data: {
        search: "degrees",
      },
      dataType: "json",
    },
    columns: [
      {
        data: null,
        render: function (data, type, row, meta) {
          return meta.row + 1;
        },
      },
      { data: "nameDegree" },
      { data: "minPoints" },
      {
        data: null,
        render: function (data, type, row) {
          return `
                        <div class="btn-group btn-block" role="group" aria-label="Acciones">
                            <button type="button" class="btn btn-primary" onclick="editDegree(${data.idDegree})"><i class="fad fa-edit"></i></button>
                            <button type="button" class="btn btn-danger" onclick="deleteDegree(${data.idDegree})"><i class="fad fa-trash-alt"></i></button>
                        </div>`;
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
});

$(".registerLicenciaturaModal").on("click", function () {
  $("#registerLicenciaturaModal").modal("show");
});

$("#licenciaturaForm").on("submit", function (event) {
  event.preventDefault();
  $.ajax({
    url: "controller/ajax/ajax.forms.php",
    method: "POST",
    data: $(this).serialize(),
    success: function (response) {
      if (response === "success") {
        alert("Nueva licenciatura creada");
        $("#licenciaturaForm")[0].reset();
        $("#degreesTable").DataTable().ajax.reload();
        $("#registerLicenciaturaModal").modal("hide");
      } else {
        alert("Error al crear la licenciatura");
      }
    },
  });
});

function editDegree(idDegree) {
  $.ajax({
    url: "controller/ajax/ajax.forms.php",
    type: "POST",
    data: { search: "degrees", idDegree: idDegree },
    success: function (response) {
      response = JSON.parse(response);
      $("#editDegreeModal").modal("show");
      $("#editDegreeName").val(response.nameDegree);
      $("#editDegreeMinPoints").val(response.minPoints);
    },
  });

  $(".editLicenciaturaForm").on("click", function () {
    event.preventDefault();
    $.ajax({
      url: "controller/ajax/ajax.forms.php",
      type: "POST",
      data: {
        search: "degrees",
        action: "editDegree",
        idDegree: idDegree,
        nameDegree: $("#editDegreeName").val(),
        minPoints: $("#editDegreeMinPoints").val(),
      },
      success: function (response) {
        if (response === '"success"') {
          alert("Licenciatura editada correctamente");
          $("#editDegreeModal").modal("hide");
          $("#degreesTable").DataTable().ajax.reload();
        } else {
          alert("Error al editar la licenciatura");
        }
      },
    });
  });
}

function deleteDegree(idDegree) {
  if (confirm("¿Estás seguro de que quieres eliminar esta licenciatura?")) {
    $.ajax({
      url: "controller/ajax/ajax.forms.php",
      type: "POST",
      data: { idDegree: idDegree, search: "degrees", action: "deleteDegree" },
      success: function (response) {
        if (response === '"success"') {
          alert("Licenciatura eliminada correctamente");
          $("#degreesTable").DataTable().ajax.reload();
        } else {
          alert("Error al eliminar la licenciatura");
        }
      },
    });
  }
}
