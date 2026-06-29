<?php
$tipoServicios = ServicioController::ctrGetTipoServicios();
?>
<style>
  :root {
    --brand: #01643d;
    --brand-2: #06a36c;
    /* gradiente */
  }

  .modal-backdrop.show {
    backdrop-filter: blur(2px);
  }

  .modal-modern .modal-content {
    border: 0;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 24px 64px rgba(2, 6, 23, .28);
  }

  .modal-modern .modal-header {
    background: linear-gradient(135deg, var(--brand), var(--brand-2));
    color: #fff;
  }

  .modal-modern .modal-title i {
    opacity: .9;
    margin-right: .5rem
  }

  .modal-modern .btn-close {
    filter: invert(1) grayscale(100%);
  }

  .modal-modern .section-title {
    font-size: .9rem;
    font-weight: 600;
    color: #0f172a;
    opacity: .7;
    letter-spacing: .02em
  }

  /* Inputs con icono */
  .input-icon .input-group-text {
    background: #f8fafc;
    border-right: 0;
  }

  .input-icon .form-control {
    border-left: 0;
  }

  .input-icon .form-control:focus {
    box-shadow: 0 0 0 .25rem rgba(1, 100, 61, .2);
    border-color: var(--brand);
  }

  /* Botón primario marca */
  .btn-brand {
    --bs-btn-bg: var(--brand);
    --bs-btn-border-color: var(--brand);
    --bs-btn-hover-bg: #015233;
    --bs-btn-hover-border-color: #015233;
    --bs-btn-active-bg: #013e27;
    --bs-btn-active-border-color: #013e27;
    color: #fff;
  }

  /* Chips de rol */
  .btn-outline-brand {
    border-color: var(--brand);
    color: var(--brand);
  }

  .btn-outline-brand:hover {
    background: rgba(1, 100, 61, .08);
    color: var(--brand)
  }

  .btn-check:checked+.btn-outline-brand {
    background: rgba(1, 100, 61, .12);
    color: var(--brand);
    border-color: var(--brand);
  }

  /* Medidor de contraseña */
  .password-meter {
    height: 6px
  }

  .password-meter .progress-bar {
    transition: width .25s ease
  }

  /* Footer sticky interno en scroll */
  .modal-modern .modal-footer {
    position: sticky;
    bottom: 0;
    background: #fff;
    z-index: 2;
    border-top: 1px solid rgba(15, 23, 42, .08);
  }
</style>

<h2>Admin y encargados</h2>
<div class="container">
  <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#registerUserModal">Registrar Usuario
    Nuevo</button>
  <div class="table-responsive">
    <table id="usersTable" class="table table-striped table-bordered">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Apellidos</th>
          <th>Correo Electrónico</th>
          <th>Rol</th>
          <th>Fecha de Registro</th>
          <th width="10%">Acciones</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

<!-- Modal para registrar usuario nuevo -->
<div class="modal fade" id="registerUserModal" tabindex="-1" aria-labelledby="registerUserModalLabel" aria-hidden="true"
  data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg modal-modern">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title d-flex align-items-center" id="registerUserModalLabel">
          <i class="fa-solid fa-user-plus"></i> Registrar Usuario Nuevo
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <form id="registerUserForm" class="needs-validation" novalidate>
        <div class="modal-body">
          <div class="mb-2 section-title">Información básica</div>
          <div class="row g-3">
            <div class="col-md-6">
              <label for="firstname" class="form-label">Nombre (s)</label>
              <div class="input-group input-icon">
                <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                <input type="text" class="form-control" id="firstname" name="firstname" required>
                <div class="invalid-feedback">Ingresa el nombre.</div>
              </div>
            </div>
            <div class="col-md-6">
              <label for="lastname" class="form-label">Apellidos</label>
              <div class="input-group input-icon">
                <span class="input-group-text"><i class="fa-solid fa-id-card"></i></span>
                <input type="text" class="form-control" id="lastname" name="lastname" required>
                <div class="invalid-feedback">Ingresa los apellidos.</div>
              </div>
            </div>

            <div class="col-12">
              <label for="email" class="form-label">Correo Electrónico</label>
              <div class="input-group input-icon">
                <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                <input type="email" class="form-control" id="email" name="email" required>
                <div class="invalid-feedback">Ingresa un correo válido.</div>
              </div>
            </div>

            <div class="col-12">
              <label for="password" class="form-label">Contraseña</label>
              <div class="input-group input-icon">
                <span class="input-group-text"><i class="fa-solid fa-key"></i></span>
                <input type="password" class="form-control" id="password" name="password" required minlength="8"
                  placeholder="Mínimo 8 caracteres">
                <button class="btn btn-outline-secondary" type="button" data-toggle-password data-target="#password"
                  aria-label="Mostrar/Ocultar">
                  <i class="fa-solid fa-eye"></i>
                </button>
                <div class="invalid-feedback">La contraseña debe tener al menos 8 caracteres.</div>
              </div>
              <div class="d-flex align-items-center gap-2 mt-2 password-div">
                <div class="progress flex-grow-1 password-meter">
                  <div id="registerPwdBar" class="progress-bar" role="progressbar"></div>
                </div>
                <small id="registerPwdHelp" class="text-muted">Débil</small>
              </div>
            </div>
          </div>

          <div class="mt-4 mb-2 section-title">Permisos</div>
          <div class="d-flex flex-wrap gap-2">
            <input class="btn-check" type="radio" name="role" id="roleDirector" value="teacher" checked>
            <label class="btn btn-outline-brand btn-sm" for="roleDirector">
              <i class="fa-solid fa-user-tie me-1"></i> Encargado
            </label>

            <input class="btn-check" type="radio" name="role" id="roleAdmin" value="admin">
            <label class="btn btn-outline-brand btn-sm" for="roleAdmin">
              <i class="fa-solid fa-shield-halved me-1"></i> Administrador
            </label>
          </div>

          <div class="row mt-3">
            <div id="adminType" class="col-md-6" style="display:none;">
              <div class="mb-2 section-title">Tipo de permisos de eventos</div>
              <div class="d-flex flex-wrap gap-2">
                <label for="event_permission_type" class="form-label">Tipo de permisos de eventos</label>
                <select class="form-select" id="event_permission_type" name="event_permission_type">
                  <option value="0" selected>Ambos</option>
                  <option value="1">Servicio Social</option>
                  <option value="2">Prácticas Escolares</option>
                </select>
              </div>
            </div>

            <div id="adminEventTypeOption" class="col-md-6" style="display:none;">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="singleEventType" name="single_event_type">
                <label class="form-check-label" for="singleEventType">
                  Asignar solo un tipo de eventos para servicio social a este administrador
                </label>
              </div>
              <div id="eventTypeSelect" class="mt-2" style="display:none;">
                <label for="eventType" class="form-label">Tipo de evento</label>
                <select class="form-select" id="eventType" name="event_type">
                  <option value="" selected>Selecciona un tipo de evento</option>
                  <?php foreach ($tipoServicios as $tipo): ?>
                    <option value="<?= htmlspecialchars($tipo['idTipoSer']) ?>"><?= htmlspecialchars($tipo['nombre']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer d-flex justify-content-between">
          <div class="text-muted small"><i class="fa-regular fa-circle-question me-1"></i>Puedes cambiar el rol más
            tarde.</div>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-outline-success" data-submit>
              <span class="submit-label"><i class="fa-solid fa-floppy-disk me-1"></i> Registrar</span>
              <span class="submit-spinner d-none"><span class="spinner-border spinner-border-sm me-1" role="status"
                  aria-hidden="true"></span>Guardando…</span>
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal para editar usuario -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true"
  data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg modal-modern">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title d-flex align-items-center" id="editUserModalLabel">
          <i class="fa-solid fa-user-pen"></i> Editar Usuario
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <form id="editUserForm" class="needs-validation" novalidate>
        <input type="hidden" id="editUserId" name="id">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="editFirstname" class="form-label">Nombre (s)</label>
              <div class="input-group input-icon">
                <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                <input type="text" class="form-control" id="editFirstname" name="firstname" required>
                <div class="invalid-feedback">Ingresa el nombre.</div>
              </div>
            </div>
            <div class="col-md-6">
              <label for="editLastname" class="form-label">Apellidos</label>
              <div class="input-group input-icon">
                <span class="input-group-text"><i class="fa-solid fa-id-card"></i></span>
                <input type="text" class="form-control" id="editLastname" name="lastname" required>
                <div class="invalid-feedback">Ingresa los apellidos.</div>
              </div>
            </div>

            <div class="col-12">
              <label for="editEmail" class="form-label">Correo Electrónico</label>
              <div class="input-group input-icon">
                <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                <input type="email" class="form-control" id="editEmail" name="email" required>
                <div class="invalid-feedback">Ingresa un correo válido.</div>
              </div>
            </div>

            <!-- Opcional: reset de contraseña -->
            <div class="col-12">
              <label for="editPassword" class="form-label">Reestablecer contraseña (opcional)</label>
              <div class="input-group input-icon">
                <span class="input-group-text"><i class="fa-solid fa-key"></i></span>
                <input type="password" class="form-control" id="editPassword" name="password"
                  placeholder="Dejar vacío para no cambiar">
                <button class="btn btn-outline-secondary" type="button" data-toggle-password data-target="#editPassword"
                  aria-label="Mostrar/Ocultar">
                  <i class="fa-solid fa-eye"></i>
                </button>
              </div>
              <div class="d-flex align-items-center gap-2 mt-2 password-div">
                <div class="progress flex-grow-1 password-meter">
                  <div id="editPwdBar" class="progress-bar" role="progressbar"></div>
                </div>
                <small id="editPwdHelp" class="text-muted">—</small>
              </div>
            </div>

            <!-- Roles y permisos -->
            <div class="col-12">
              <label class="form-label d-block">Permisos</label>
              <div class="d-flex flex-wrap gap-2">
                <input class="btn-check" type="radio" name="role" id="editRoleDirector" value="teacher">
                <label class="btn btn-outline-brand btn-sm" for="editRoleDirector">
                  <i class="fa-solid fa-user-tie me-1"></i> Encargado
                </label>

                <input class="btn-check" type="radio" name="role" id="editRoleAdmin" value="admin">
                <label class="btn btn-outline-brand btn-sm" for="editRoleAdmin">
                  <i class="fa-solid fa-shield-halved me-1"></i> Administrador
                </label>
              </div>

              <div class="row mt-3">

                <div id="editAdminType" class="col-md-6" style="display:none;">
                  <div class="mb-2 section-title">Tipo de permisos de eventos</div>
                  <label for="edit_event_permission_type" class="form-label">Tipo de permisos de eventos</label>
                  <select class="form-select" id="edit_event_permission_type" name="event_permission_type">
                    <option value="0">Ambos</option>
                    <option value="1">Servicio Social</option>
                    <option value="2">Prácticas Escolares</option>
                    <!-- Si tu backend guarda "practicas_profesionales", puedes duplicar la opción o mapear en backend -->
                  </select>
                </div>

                <div id="editAdminEventTypeOption" class="col-md-6" style="display:none;">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="editSingleEventType" name="single_event_type">
                    <label class="form-check-label" for="editSingleEventType">
                      Asignar solo un tipo de eventos a este administrador
                    </label>
                  </div>
                  <div id="editEventTypeSelect" class="mt-2" style="display:none;">
                    <label for="editEventType" class="form-label">Tipo de evento</label>
                    <select class="form-select" id="editEventType" name="event_type">
                      <option value="" selected>Selecciona un tipo de evento</option>
                      <?php foreach ($tipoServicios as $tipo): ?>
                        <option value="<?= htmlspecialchars($tipo['idTipoSer']) ?>">
                          <?= htmlspecialchars($tipo['nombre']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer d-flex justify-content-between">
          <div></div>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-outline-success" data-submit>
              <span class="submit-label"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar cambios</span>
              <span class="submit-spinner d-none"><span
                  class="spinner-border spinner-border-sm me-1"></span>Guardando…</span>
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="view/assets/js/ajax/users.js"></script>