<!-- Modal para aplicar al evento -->
<div class="modal fade" id="applyEventModal" tabindex="-1" role="dialog" aria-labelledby="applyEventModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="applyEventModalLabel">Postularme al Evento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Contenido del modal para aplicar al evento -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-primary">Confirmar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal para editar evento -->
<div class="modal fade" id="editEventModal" tabindex="-1" aria-labelledby="editEventModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title" id="editEventModalLabel">Editar Evento</h5>
                <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="editEventForm">
                    <input type="hidden" id="editEventId" name="idEvent">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editEventTypeId" class="form-label">Tipo de Evento</label>
                            <select name="editEventTypeId" id="editEventTypeId" class="form-select" required>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editEventName" class="form-label">Nombre del Evento</label>
                            <input type="text" class="form-control" id="editEventName" name="eventName" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editDate" class="form-label">Fecha</label>
                            <input type="date" class="form-control" id="editDate" name="date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editLocation" class="form-label">Ubicación</label>
                            <input type="text" class="form-control" id="editLocation" name="location" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editStartTime" class="form-label">Hora de Inicio</label>
                            <input type="time" class="form-control" id="editStartTime" name="start_time" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editEndTime" class="form-label">Hora de Fin</label>
                            <input type="time" class="form-control" id="editEndTime" name="end_time" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editPoints" class="form-label">Puntos</label>
                            <input type="number" class="form-control" id="editPoints" name="points" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editVacanciesAvailable" class="form-label">Vacantes Disponibles</label>
                            <input type="number" class="form-control" id="editVacanciesAvailable" name="vacancies_available" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="editDescription" class="form-label">Descripción</label>
                        <textarea class="form-control" id="editDescription" name="description" rows="4" required></textarea>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success btn-block">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal para borrar el evento -->
<div class="modal fade" id="deleteEventModal" tabindex="-1" role="dialog" aria-labelledby="deleteEventModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteEventModalLabel">Borrar Evento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
            <i class="fas fa-times" aria-hidden="true"></i>
        </button>
      </div>
      <div class="modal-body">
        
        ¿Está seguro de que quiere borrar este evento? Esto no se puede deshacer.

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-danger">Borrar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="candidatesModal" tabindex="-1" role="dialog" aria-labelledby="candidatesModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
    <div class="modal-content border-0 shadow-lg" style="border-radius:1rem;overflow:hidden;">
      <div class="modal-header border-0 text-white px-4 py-3"
           style="background:linear-gradient(135deg,#01643D 0%,#2A7E5D 100%);">
        <div class="d-flex align-items-center gap-3">
          <div style="width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,.2);
                      display:flex;align-items:center;justify-content:center;font-size:1.1rem;">
            <i class="fas fa-users"></i>
          </div>
          <div>
            <h5 class="modal-title fw-bold mb-0" id="candidatesModalLabel">Lista de Candidatos</h5>
            <small style="opacity:.8;font-size:.78rem;">Acepta o rechaza los postulantes a este evento</small>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body px-4 py-3">
        <!-- Cards de candidatos (inyectadas por inicio.js) -->
        <div id="candidatesCardsContainer"></div>
        <!-- Tabla oculta (legado) -->
        <table class="table table-striped d-none" id="candidatesTable">
          <thead>
            <tr><th>#</th><th>Nombre</th><th>Apellido</th><th>Correo Electrónico</th><th>Teléfono</th><th></th></tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
      <div class="modal-footer border-0 px-4">
        <button type="button" class="btn fw-semibold px-4" data-bs-dismiss="modal"
                style="background:#01643D;color:#fff;border:none;border-radius:.6rem;">
          <i class="fas fa-times me-1"></i>Cerrar
        </button>
      </div>
    </div>
  </div>
</div>

<?php if ($_SESSION['user']['role'] == 'student'):?>

  <div class="modal fade" id="achievementModal" tabindex="-1" role="dialog" aria-labelledby="achievementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="achievementModalLabel"></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="achievementModalBody">
            </div>
        </div>
    </div>
</div>

<?php endif ?>