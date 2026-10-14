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
              activateButton = `<button type="button" class="btn btn-success" onclick="activeCourse(${data.idCourse})"><i class="fas fa-check"></i></button>`;
            } else {
              activateButton = `<button type="button" class="btn btn-success" disabled><i class="fas fa-check"></i></button>`;
            }

            return `
              <div class="btn-group btn-block" role="group" aria-label="Acciones">
                  ${activateButton}
                  <button type="button" class="btn btn-primary" onclick="editCourse(${data.idCourse})"><i class="fas fa-edit"></i></button>
                  <button type="button" class="btn btn-danger" onclick="deleteCourse(${data.idCourse})"><i class="fas fa-trash-alt"></i></button>
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
      dataType: "json",
      success: function (response) {
        if (response !== "success") {
          Swal.fire({ icon: "error", title: "Error", text: "No se pudo registrar el ciclo escolar." });
          return;
        }
        $("#registerCourseForm")[0].reset();
        $("#registerCourseModal").modal("hide");
        table.ajax.reload();
        Swal.fire({
          icon: "success",
          title: "Ciclo escolar registrado",
          text: "Para que los alumnos y eventos nuevos se registren en él, actívalo con el botón verde de su fila.",
        });
      },
      error: function () {
        Swal.fire({ icon: "error", title: "Error", text: "No se pudo registrar el ciclo escolar." });
      },
    });
    return false;
  });

  // Activar curso
  window.activeCourse = function (idCourse) {
    Swal.fire({
      title: "¿Activar este ciclo escolar?",
      text: "Los alumnos y eventos nuevos se registrarán en él. El ciclo activo anterior quedará inactivo.",
      icon: "question",
      showCancelButton: true,
      confirmButtonColor: "#01643d",
      cancelButtonColor: "#6c757d",
      confirmButtonText: "Sí, activar",
      cancelButtonText: "Cancelar",
    }).then((result) => {
      if (!result.isConfirmed) return;
      $.ajax({
        url: "controller/ajax/ajax.activateCourse.php",
        type: "POST",
        data: { idCourse: idCourse },
        success: function (response) {
          table.ajax.reload();
          if (String(response).trim() !== "success") {
            Swal.fire({ icon: "error", title: "Error", text: "No se pudo activar el ciclo escolar." });
            return;
          }
          Swal.fire({ icon: "success", title: "Ciclo escolar activado", text: "Desde ahora, los alumnos y eventos nuevos se registran en este ciclo." });
        },
        error: function (xhr, status, error) {
          console.error("Error activando el curso:", error);
          Swal.fire({ icon: "error", title: "Error", text: "No se pudo activar el ciclo escolar. Inténtalo de nuevo." });
        },
      });
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
      dataType: "json",
      success: function (response) {
        if (response !== "success") {
          Swal.fire({ icon: "error", title: "Error", text: "No se pudieron guardar los cambios." });
          return;
        }
        $("#editCourseForm")[0].reset();
        $("#editCourseModal").modal("hide");
        table.ajax.reload();
        Swal.fire({ icon: "success", title: "Ciclo escolar actualizado", timer: 1800, showConfirmButton: false });
      },
      error: function () {
        Swal.fire({ icon: "error", title: "Error", text: "No se pudieron guardar los cambios." });
      },
    });
    return false;
  });

  // Eliminar curso
  window.deleteCourse = function (idCourse) {
    Swal.fire({
      title: "¿Eliminar este ciclo escolar?",
      text: "Esta acción no se puede deshacer.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#6c757d",
      confirmButtonText: "Sí, eliminar",
      cancelButtonText: "Cancelar",
    }).then((result) => {
      if (!result.isConfirmed) return;
      $.ajax({
        url: "controller/ajax/ajax.forms.php",
        type: "POST",
        data: { deleteCourse: idCourse, search: "courses" },
        dataType: "json",
        success: function (response) {
          table.ajax.reload();
          if (response === "activo") {
            Swal.fire({ icon: "info", title: "Es el ciclo activo", text: "Activa otro ciclo escolar antes de eliminar este." });
            return;
          }
          if (response === "en_uso") {
            Swal.fire({ icon: "info", title: "No se puede eliminar", text: "Este ciclo escolar ya tiene alumnos o eventos registrados." });
            return;
          }
          if (response !== "success") {
            Swal.fire({ icon: "error", title: "Error", text: "No se pudo eliminar el ciclo escolar." });
            return;
          }
          Swal.fire({ icon: "success", title: "Ciclo escolar eliminado", timer: 1800, showConfirmButton: false });
        },
        error: function (xhr, status, error) {
          console.error("Error eliminando el curso:", error);
          Swal.fire({ icon: "error", title: "Error", text: "No se pudo eliminar el ciclo escolar. Inténtalo de nuevo." });
        },
      });
    });
  };
});
