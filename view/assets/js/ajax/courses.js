$(document).ready(function () {
  var table = initializeDataTable();

  $(".registerCourseModal").on("click", function (e) {
    $("#registerCourseForm")[0].reset();
    $("#registerCourseModal").modal("show");
  });

  // Inicializa DataTable
  function initializeDataTable() {
    return $("#coursesTable").DataTable({
      ajax: {
        type: "POST",
        url: "controller/ajax/ajax.forms.php",
        dataSrc: "",
        data: { search: "courses" },
      },
      columns: [
        {
          data: null,
          render: function (data, type, row, meta) {
            return meta.row + 1;
          },
        },
        { data: "nameCourse" },
        { data: "startCourse" },
        { data: "endCourse" },
        {
          data: null,
          render: function (data) {
            let activateButton = "";
            if (data.active == 0) {
              activateButton = `<button type="button" class="btn btn-success" onclick="activeCourse(${data.idCourse})"><i class="fad fa-check"></i></button>`;
            } else {
              activateButton = `<button type="button" class="btn btn-success" disabled><i class="fad fa-check"></i></button>`;
            }

            return `
              <div class="btn-group btn-block" role="group" aria-label="Acciones">
                  ${activateButton}
                  <button type="button" class="btn btn-primary" onclick="editCourse(${data.idCourse})"><i class="fad fa-edit"></i></button>
                  <button type="button" class="btn btn-danger" onclick="deleteCourse(${data.idCourse})"><i class="fad fa-trash-alt"></i></button>
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
      rowCallback: function (row, data, index) {
        // Agregar el índice incrementable
        $("td:eq(0)", row).html(index + 1);
      },
    });
  }

  // Añadir curso
  $("#registerCourseForm").on("submit", function (event) {
    event.preventDefault();
    var formData = $(this).serializeArray(); // Serializa el formulario a un array de objetos
    formData.push({ name: "search", value: "courses" });
    formData.push({ name: "addCourse", value: true });

    $.ajax({
      url: "controller/ajax/ajax.forms.php",
      method: "POST",
      data: $.param(formData),
      success: function (response) {
        $("#registerCourseForm")[0].reset();
        $("#registerCourseModal").modal("hide");
        table.ajax.reload();
      },
    });
    return false;
  });

  // Activar curso
  window.activeCourse = function (idCourse) {
    $.ajax({
      url: "controller/ajax/ajax.activateCourse.php",
      type: "POST",
      data: { idCourse: idCourse },
      success: function (response) {
        table.ajax.reload();
        alert("Curso activado exitosamente");
      },
      error: function (xhr, status, error) {
        console.error("Error activando el curso:", error);
        alert(
          "Hubo un error al activar el curso. Por favor, inténtelo de nuevo."
        );
      },
    });
  };

  // Editar curso
  window.editCourse = function (idCourse) {
    $.ajax({
      url: "controller/ajax/ajax.forms.php",
      type: "POST",
      data: { search: "courses", idCourse: idCourse },
      dataType: "json",
      success: function (course) {
        $("#editCourseId").val(course.idCourse);
        $("#editNameCourse").val(course.nameCourse);
        $("#editStartCourse").val(course.startCourse);
        $("#editEndCourse").val(course.endCourse);
        $("#editCourseModal").modal("show");
      },
      error: function (xhr, status, error) {
        console.error("Error cargando los datos del curso:", error);
        alert(
          "Hubo un error al cargar los datos del curso. Por favor, inténtelo de nuevo."
        );
      },
    });
  };

  // Guardar cambios al editar curso
  $("#editCourseForm").on("submit", function (event) {
    event.preventDefault();
    var formData = $(this).serializeArray(); // Serializa el formulario a un array de objetos
    formData.push({ name: "search", value: "courses" });
    formData.push({ name: "editCourse", value: true });

    $.ajax({
      url: "controller/ajax/ajax.forms.php",
      method: "POST",
      data: $.param(formData),
      success: function (response) {
        $("#editCourseForm")[0].reset();
        $("#editCourseModal").modal("hide");
        table.ajax.reload();
      },
    });
    return false;
  });

  // Eliminar curso
  window.deleteCourse = function (idCourse) {
    if (confirm("¿Estás seguro de que quieres eliminar este curso?")) {
      $.ajax({
        url: "controller/ajax/ajax.forms.php",
        type: "POST",
        data: { deleteCourse: idCourse, search: "courses" },
        success: function (response) {
          table.ajax.reload();
          alert("Curso eliminado exitosamente");
        },
        error: function (xhr, status, error) {
          console.error("Error eliminando el curso:", error);
          alert(
            "Hubo un error al eliminar el curso. Por favor, inténtelo de nuevo."
          );
        },
      });
    }
  };
});
