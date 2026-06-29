(() => {
  "use strict";

  // ====================== Constantes / Helpers ======================
  const DT_LANG_ES = {
    sProcessing: "Procesando...",
    sLengthMenu: "Mostrar _MENU_ registros",
    sZeroRecords: "No se encontraron resultados",
    sEmptyTable: "Ningún dato disponible en esta tabla",
    sInfo: "Mostrando _START_ a _END_ de _TOTAL_",
    sInfoEmpty: "Mostrando 0 a 0 de 0",
    sInfoFiltered: "(filtrado de _MAX_ en total)",
    sSearch: "Buscar:",
    sLoadingRecords: "Cargando...",
    oPaginate: { sFirst: "Primero", sLast: "Último", sNext: "Siguiente", sPrevious: "Anterior" },
    oAria: { sSortAscending: ": activar para ordenar ascendente", sSortDescending: ": activar para ordenar descendente" },
    buttons: { copy: "Copiar", colvis: "Visibilidad" },
  };

  const ROLE_BADGE = {
    admin: `<span class="badge bg-success">Administrador</span>`,
    teacher: `<span class="badge bg-info text-dark">Encargado</span>`,
  };

  const textRenderer = $.fn.dataTable.render.text(); // anti-XSS para campos de texto

  const toast = (type, title, text, timer = 2000) =>
    Swal.fire({ icon: type, title, text, timer, showConfirmButton: false });

  const setSubmitting = ($form, isOn) => {
    const $btn = $form.find("[data-submit]");
    $btn.prop("disabled", !!isOn);
    $btn.find(".submit-label").toggleClass("d-none", !!isOn);
    $btn.find(".submit-spinner").toggleClass("d-none", !isOn);
  };

  const resetPwdMeter = ($bar, $help) => {
    $bar.css("width", "0%").removeClass("bg-danger bg-warning bg-success");
    $help.text("—");
  };

  const scorePassword = (pwd) => {
    let s = 0;
    if (pwd.length >= 8) s += 25;
    if (/[A-Z]/.test(pwd)) s += 25;
    if (/\d/.test(pwd)) s += 25;
    if (/[^A-Za-z0-9]/.test(pwd)) s += 25;
    return s;
  };

  const bindPasswordMeter = (inputSel, barSel, helpSel) => {
    const $inp = $(inputSel), $bar = $(barSel), $help = $(helpSel);
    $inp.on("input", () => {
      $(".password-div").toggleClass("d-none", !$inp.val());
      const sc = scorePassword($inp.val());
      $bar.removeClass("bg-danger bg-warning bg-success").css("width", sc + "%");
      if (sc < 50) { $bar.addClass("bg-danger"); $help.text("Débil"); }
      else if (sc < 75) { $bar.addClass("bg-warning"); $help.text("Media"); }
      else { $bar.addClass("bg-success"); $help.text("Fuerte"); }
    });
  };

  const isPracticas = (val) => /practicas_(escolares|profesionales)/i.test(String(val || ""));

  const setupRoleToggle = (cfg) => {
    const {
      adminRadioId, directorRadioId,
      optionBlockId, checkboxId, selectBlockId,
      adminTypeBlockId, eventPermissionSelectId
    } = cfg;

    const adminRadio = document.getElementById(adminRadioId);
    const directorRadio = document.getElementById(directorRadioId);
    const optionBlock = document.getElementById(optionBlockId);
    const adminTypeBlock = document.getElementById(adminTypeBlockId);
    const eventPermSelect = document.getElementById(eventPermissionSelectId);
    const checkbox = document.getElementById(checkboxId);
    const selectBlock = document.getElementById(selectBlockId);

    if (!adminRadio || !directorRadio || !optionBlock || !eventPermSelect) return;

    const toggleSelectVisibility = () => {
      const forbid = isPracticas(eventPermSelect.value);
      const show = adminRadio.checked && checkbox?.checked && !forbid;
      if (selectBlock) selectBlock.style.display = show ? "block" : "none";
      if (!show) $(selectBlock).find("select").val("");
    };

    const applyVisibility = () => {
      const forbid = isPracticas(eventPermSelect.value);
      if (adminTypeBlock) adminTypeBlock.style.display = adminRadio.checked ? "block" : "none";
      optionBlock.style.display = (adminRadio.checked && !forbid) ? "block" : "none";
      if (forbid && checkbox) checkbox.checked = false;
      toggleSelectVisibility();
    };

    adminRadio.addEventListener("change", applyVisibility);
    directorRadio.addEventListener("change", applyVisibility);
    checkbox?.addEventListener("change", toggleSelectVisibility);
    eventPermSelect.addEventListener("change", applyVisibility);
    applyVisibility(); // estado inicial
  };

  // ====================== DataTable ======================
  const table = $("#usersTable").DataTable({
    ajax: { url: "controller/ajax/ajax.getUsers.php", dataSrc: "" },
    processing: true,
    deferRender: true,
    responsive: true,
    rowId: "id",
    language: DT_LANG_ES,
    order: [[5, "desc"]], // created_at desc
    columnDefs: [
      { targets: "_all", defaultContent: "" },
      { targets: [1, 2, 3], render: textRenderer }, // firstname, lastname, email
      { targets: 0, orderable: false, className: "text-center" },
      { targets: -1, orderable: false, searchable: false, className: "text-center" },
    ],
    columns: [
      {
        data: null,
        render: (d, t, r, m) => m.row + m.settings._iDisplayStart + 1,
      },
      { data: "firstname" },
      { data: "lastname" },
      { data: "email" },
      {
        data: "role",
        render: (role) => ROLE_BADGE[role] || `<span class="badge bg-secondary">Sin rol</span>`,
      },
      { data: "created_at" },
      {
        data: null,
        render: (row) => `
          <div class="btn-group btn-block" role="group" aria-label="Acciones">
            <button type="button" class="btn btn-primary btn-edit" data-id="${row.id}" title="Editar">
              <i class="fad fa-edit"></i>
            </button>
            <button type="button" class="btn btn-danger btn-delete" data-id="${row.id}" title="Eliminar">
              <i class="fad fa-trash-alt"></i>
            </button>
          </div>`,
      },
    ],
  });

  // ====================== Eventos de tabla (delegados) ======================
  $("#usersTable")
    .on("click", ".btn-edit", function () {
      const id = $(this).data("id");
      editUser(id);
    })
    .on("click", ".btn-delete", function () {
      const id = $(this).data("id");
      deleteUser(id);
    });

  // ====================== Registro / Edición (DRY) ======================
  const wireForm = (formSel, url, modalSel, barSel, helpSel, successMsg) => {
    $(formSel).on("submit", function (e) {
      e.preventDefault();
      const $form = $(this);
      setSubmitting($form, true);
      $.ajax({
        url,
        method: "POST",
        data: $form.serialize(),
      })
        .done(() => {
          $form[0].reset();
          resetPwdMeter($(barSel), $(helpSel));
          $(modalSel).modal("hide");
          table.ajax.reload(null, false); // recarga sin perder página
          toast("success", successMsg, "Operación exitosa.");
        })
        .fail(() => {
          Swal.fire({ icon: "error", title: "Error", text: "Operación no completada." });
        })
        .always(() => setSubmitting($form, false));
    });
  };

  wireForm("#registerUserForm", "controller/ajax/ajax.forms.php", "#registerUserModal", "#registerPwdBar", "#registerPwdHelp", "Usuario registrado");
  wireForm("#editUserForm", "controller/ajax/ajax.updateUser.php", "#editUserModal", "#editPwdBar", "#editPwdHelp", "Usuario actualizado");

  // ====================== Reset barras al cerrar modales ======================
  $("#registerUserModal, #editUserModal").on("hidden.bs.modal", () => {
    resetPwdMeter($("#registerPwdBar"), $("#registerPwdHelp"));
    resetPwdMeter($("#editPwdBar"), $("#editPwdHelp"));
  });

  // ====================== Mostrar/Ocultar Contraseña (delegado) ======================
  $(document).on("click", "[data-toggle-password]", function () {
    const input = $($(this).data("target"));
    const icon = $(this).find("i");
    if (!input.length) return;
    input.attr("type", input.attr("type") === "password" ? "text" : "password");
    icon.toggleClass("fa-eye fa-eye-slash");
  });

  // ====================== Medidor de Contraseña ======================
  bindPasswordMeter("#password", "#registerPwdBar", "#registerPwdHelp");
  bindPasswordMeter("#editPassword", "#editPwdBar", "#editPwdHelp");

  // ====================== Roles / Permisos (Registro & Edición) ======================
  setupRoleToggle({
    adminRadioId: "roleAdmin",
    directorRadioId: "roleDirector",
    optionBlockId: "adminEventTypeOption",
    checkboxId: "singleEventType",
    selectBlockId: "eventTypeSelect",
    adminTypeBlockId: "adminType",
    eventPermissionSelectId: "event_permission_type",
  });

  setupRoleToggle({
    adminRadioId: "editRoleAdmin",
    directorRadioId: "editRoleDirector",
    optionBlockId: "editAdminEventTypeOption",
    checkboxId: "editSingleEventType",
    selectBlockId: "editEventTypeSelect",
    adminTypeBlockId: "editAdminType",
    eventPermissionSelectId: "edit_event_permission_type",
  });

  // Enum legible para #edit_event_permission_type (ajusta si tus valores reales difieren)
  const ADMIN_PERM = Object.freeze({
    AMBOS: "0",
    SERVICIO_SOCIAL: "1",
    PRACTICAS_ESCOLARES: "2",
  });

  // ====================== Editar / Eliminar usuario ======================
  window.editUser = function (id) {
    $.ajax({
      url: "controller/ajax/ajax.getUser.php",
      method: "GET",
      data: { id },
      dataType: "json",
    })
      .done((u) => {
        // --- Campos base
        $("#editUserId").val(u?.id ?? "");
        $("#editFirstname").val(u?.firstname ?? "");
        $("#editLastname").val(u?.lastname ?? "");
        $("#editEmail").val(u?.email ?? "");

        // --- Cache DOM (menos re-selecciones)
        const $roleAdmin = $("#editRoleAdmin");
        const $roleDir = $("#editRoleDirector");
        const $optAdminEvt = $("#editAdminEventTypeOption");
        const $adminBlock = $("#editAdminType");
        const $permSelect = $("#edit_event_permission_type");
        const $singleCB = $("#editSingleEventType");
        const $singleBlock = $("#editEventTypeSelect");
        const $singleSel = $("#editEventType");

        // --- Lógica declarativa
        const isAdmin = u?.role === "admin";
        const permType = String(u?.type_admin ?? ADMIN_PERM.AMBOS);
        const hasSingle = !!u?.idTipoSer;
        const isPract = permType === ADMIN_PERM.PRACTICAS_ESCOLARES;

        // Radios de rol
        $roleAdmin.prop("checked", isAdmin);
        $roleDir.prop("checked", !isAdmin);

        // Bloques visibles sólo si es admin
        $optAdminEvt.toggle(isAdmin);
        $adminBlock.toggle(isAdmin);

        // Tipo de permisos
        $permSelect.val(permType);

        // Checkbox "Asignar solo un tipo…"
        //  - Forzamos desactivar/limpiar cuando es Prácticas
        $singleCB
          .prop("disabled", isPract)
          .prop("checked", isAdmin && hasSingle && !isPract)
          .attr("title", isPract ? "No aplica para Prácticas Escolares" : "");

        // Select dependiente
        const showSingleSelect = isAdmin && $singleCB.is(":checked");
        $singleBlock.toggle(showSingleSelect);
        $singleSel.val(showSingleSelect ? (u?.idTipoSer ?? "") : "");

        // Password meter reset
        $("#editPassword").val("");
        $(".password-div").toggleClass("d-none", true);
        resetPwdMeter($("#editPwdBar"), $("#editPwdHelp"));

        // Si tu UI tiene listeners de change que controlan la visibilidad, respétalos:
        $roleAdmin.add($roleDir).add($permSelect).add($singleCB).trigger("change");

        $("#editUserModal").modal("show");
      })
      .fail(() => {
        Swal.fire({ icon: "error", title: "Error", text: "No se pudo cargar el usuario." });
      });
  };

  window.deleteUser = function (id) {
    Swal.fire({
      title: "¿Estás seguro?",
      text: "Este usuario será eliminado permanentemente.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#6c757d",
      confirmButtonText: "Sí, eliminar",
      cancelButtonText: "Cancelar",
    }).then((res) => {
      if (!res.isConfirmed) return;
      $.ajax({
        url: "controller/ajax/ajax.deleteUser.php",
        method: "POST",
        data: { id },
      })
        .done(() => {
          table.ajax.reload(null, false);
          toast("success", "Eliminado", "El usuario ha sido eliminado.");
        })
        .fail(() => {
          Swal.fire({ icon: "error", title: "Error", text: "No se pudo eliminar el usuario." });
        });
    });
  };
})();
