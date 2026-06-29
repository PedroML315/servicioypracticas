<script src="https://cdn.ckeditor.com/ckeditor5/39.0.0/classic/ckeditor.js"></script>

<!-- Modal para registrar tipo de evento nuevo -->
<div class="modal fade" id="registerEventTypeModal" tabindex="-1" aria-labelledby="registerEventTypeModalLabel"
  aria-hidden="true">
  <div class="modal-dialog modal-xxl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="registerEventTypeModalLabel">
          <i class="fa-solid fa-layer-group me-2"></i>Tipos de eventos
        </h5>
        <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close">
          <i class="fas fa-times" aria-hidden="true"></i>
      </div>

      <div class="modal-body">

        <!-- Tabs -->
        <ul class="nav nav-tabs" id="eventTypeTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tabLista" data-bs-toggle="tab" data-bs-target="#paneLista" type="button"
              role="tab" aria-controls="paneLista" aria-selected="true">
              <i class="fa-solid fa-table-list me-1"></i> Lista
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="tabNuevo" data-bs-toggle="tab" data-bs-target="#paneNuevo" type="button"
              role="tab" aria-controls="paneNuevo" aria-selected="false">
              <i class="fa-solid fa-plus me-1"></i> Nuevo
            </button>
          </li>
          <li class="nav-item d-none" role="presentation">
            <button class="nav-link" id="tabEditor" data-bs-toggle="tab" data-bs-target="#paneEditor" type="button"
              role="tab" aria-controls="paneEditor" aria-selected="false" tabindex="-1" aria-disabled="true">
              <i class="fa-solid fa-pen-to-square me-1"></i> Editor
            </button>
          </li>
        </ul>

        <!-- Contenido de tabs -->
        <div class="tab-content pt-3" id="eventTypeTabsContent">

          <!-- TAB: LISTA -->
          <div class="tab-pane fade show active" id="paneLista" role="tabpanel" aria-labelledby="tabLista">
            <div class="table-responsive">
              <table id="eventTypesTable" class="table table-striped table-bordered w-100">
                <thead>
                  <tr>
                    <th>#</th>
                    <th width="40%">Nombre del Tipo de Evento</th>
                    <th width="20%">Área encargada</th>
                    <th width="10%">Puntos por Evento</th>
                    <th width="10%">Prestadores por Año</th>
                    <th width="10%">Tipo de Servicio</th>
                    <th width="10%">Acciones</th>
                  </tr>
                </thead>
              </table>
            </div>
          </div>

          <!-- TAB: NUEVO -->
          <div class="tab-pane fade" id="paneNuevo" role="tabpanel" aria-labelledby="tabNuevo">
            <form id="registerEventTypeForm" class="row g-3">
              <div class="col-md-6">
                <label for="eventTypeName" class="form-label">Nombre del Tipo de Evento</label>
                <input type="text" class="form-control" id="eventTypeName" name="eventTypeName" required>
              </div>

              <div class="col-md-6">
                <label for="areaEncargada" class="form-label">Área encargada</label>
                <select name="areaEncargada" id="areaEncargada" class="form-select" required>
                  <option value="">Seleccione una opción</option>
                </select>
              </div>

              <div class="col-md-6">
                <label for="eventTypePoints" class="form-label">Puntos por Evento</label>
                <input type="number" class="form-control" id="eventTypePoints" name="eventTypePoints" required min="0"
                  step="1">
              </div>

              <div class="col-md-6">
                <label for="eventTypeBenefits" class="form-label">Beneficios por Año</label>
                <input type="number" class="form-control" id="eventTypeBenefits" name="eventTypeBenefits" required
                  min="0" step="1">
              </div>

              <div class="col-md-6">
                <label for="eventTypeService" class="form-label">Tipo de Servicio</label>
                <select name="eventTypeService" id="eventTypeService" class="form-select" required>
                  <option value="">Seleccione una opción</option>
                </select>
              </div>

              <input type="hidden" name="search" value="event_types">

              <div class="col-12 d-flex justify-content-end gap-2">
                <button type="reset" class="btn btn-outline-secondary">Limpiar</button>
                <button type="submit" class="btn btn-primary">
                  <i class="fa-solid fa-floppy-disk me-1"></i> Registrar
                </button>
              </div>
            </form>
          </div>

          <!-- TAB: EDITOR -->
          <div class="tab-pane fade" id="paneEditor" role="tabpanel" aria-labelledby="tabEditor">
            <form id="editEventTypeForm" class="row g-3">
              <div class="col-md-6">
                <label for="editEventTypeName" class="form-label">Nombre del Tipo de Evento</label>
                <input type="text" class="form-control" id="editEventTypeName" name="editEventTypeName" required>
              </div>

              <div class="col-md-6">
                <label for="editAreaEncargada" class="form-label">Área encargada</label>
                <select name="editAreaEncargada" id="editAreaEncargada" class="form-select" required>
                  <option value="">Seleccione una opción</option>
                </select>
              </div>

              <div class="col-md-6">
                <label for="editEventTypePoints" class="form-label">Puntos por Evento</label>
                <input type="number" class="form-control" id="editEventTypePoints" name="editEventTypePoints" required
                  min="0" step="1">
              </div>

              <div class="col-md-6">
                <label for="editEventTypeBenefits" class="form-label">Beneficios por Año</label>
                <input type="number" class="form-control" id="editEventTypeBenefits" name="editEventTypeBenefits"
                  required min="0" step="1">
              </div>

              <div class="col-md-6">
                <label for="editEventTypeService" class="form-label">Tipo de Servicio</label>
                <select name="editEventTypeService" id="editEventTypeService" class="form-select" required>
                  <option value="">Seleccione una opción</option>
                </select>
              </div>

              <input type="hidden" id="editEventType" name="editEventType">
              <input type="hidden" id="event_types" name="search" value="event_types">

              <div class="col-12 d-flex justify-content-between flex-wrap gap-2">
                <button type="button" class="btn btn-outline-secondary" id="btnBackToList">
                  <i class="fa-solid fa-arrow-left me-1"></i> Volver a lista
                </button>
                <div class="d-flex gap-2">
                  <button type="submit" class="btn btn-success">
                    <i class="fa-solid fa-save me-1"></i> Guardar cambios
                  </button>
                </div>
              </div>
            </form>
          </div>

        </div> <!-- /tab-content -->

      </div> <!-- /modal-body -->
    </div>
  </div>
</div>

<!-- Modal para registrar evento nuevo -->
<div class="modal fade" id="registerEventModal" tabindex="-1" aria-labelledby="registerEventModalLabel"
  aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-success">
        <h5 class="modal-title" id="registerEventModalLabel">Registrar Evento Nuevo</h5>
        <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close">
          <i class="fas fa-times" aria-hidden="true"></i>
        </button>
      </div>
      <div class="modal-body">
        <form id="registerEventForm">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="eventTypeId" class="form-label">Tipo de Evento</label>
              <select name="eventTypeId" id="eventTypeId" class="form-select" required>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label for="eventName" class="form-label">Nombre del Evento</label>
              <input type="text" class="form-control" id="eventName" name="eventName" required>
            </div>
            <div class="col-12 mb-3">
              <label for="eventUser" class="form-label">Encargado del evento</label>
              <select name="eventUser" id="eventUser" class="form-select">

              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="date" class="form-label">Fecha</label>
              <input type="date" class="form-control" id="date" name="date" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="location" class="form-label">Ubicación</label>
              <input type="text" class="form-control" id="location" name="location" required>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="start_time" class="form-label">Hora de Inicio</label>
              <input type="time" class="form-control" id="start_time" name="start_time" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="end_time" class="form-label">Hora de Fin</label>
              <input type="time" class="form-control" id="end_time" name="end_time" required>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="points" class="form-label">Puntos</label>
              <input type="number" class="form-control" id="points" name="points" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="vacancies_available" class="form-label">Vacantes Disponibles</label>
              <input type="number" class="form-control" id="vacancies_available" name="vacancies_available" required>
            </div>
          </div>
          <div class="mb-3">
            <label for="description" class="form-label">Descripción</label>
            <textarea class="form-control" id="description" name="description" rows="4"></textarea>
          </div>
          <div class="d-grid">
            <button type="submit" class="btn btn-primary btn-block">Registrar</button>
          </div>
        </form>
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
            <textarea class="form-control" id="editDescription" name="description" rows="4"></textarea>
          </div>
          <div class="d-grid">
            <button type="submit" class="btn btn-success btn-block">Guardar Cambios</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal para ver la descripción -->
<div class="modal fade" id="viewDescriptionModal" tabindex="-1" aria-labelledby="viewDescriptionModalLabel"
  aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-success">
        <h5 class="modal-title" id="viewDescriptionModalLabel">Descripción</h5>
        <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close">
          <i class="fas fa-times" aria-hidden="true"></i>
        </button>
      </div>
      <div class="modal-body">
        <div id="descriptionContainer"></div>
      </div>
    </div>
  </div>
</div>

<script>
  // Inicializar CKEditor en el campo de descripción del formulario de registro
  let registerEditor, editEditor;

  ClassicEditor
    .create(document.querySelector('#description'))
    .then(editor => {
      registerEditor = editor;
    })
    .catch(error => {
      console.error(error);
    });

  // Inicializar CKEditor en el campo de descripción del formulario de edición
  ClassicEditor
    .create(document.querySelector('#editDescription'))
    .then(editor => {
      editEditor = editor;
    })
    .catch(error => {
      console.error(error);
    });

  // Validar el contenido de CKEditor antes de enviar el formulario de registro
  document.getElementById('registerEventForm').addEventListener('submit', function (event) {
    // Obtener el contenido de CKEditor
    const descriptionContent = registerEditor.getData();
    if (descriptionContent.trim() === '') {
      alert('La descripción es requerida.');
      event.preventDefault();  // Evitar que el formulario se envíe
    }
  });

  // Validar el contenido de CKEditor antes de enviar el formulario de edición
  document.getElementById('editEventForm').addEventListener('submit', function (event) {
    // Obtener el contenido de CKEditor
    const editDescriptionContent = editEditor.getData();
    if (editDescriptionContent.trim() === '') {
      alert('La descripción es requerida.');
      event.preventDefault();  // Evitar que el formulario se envíe
    }
  });

  document.getElementById('btnBackToList').addEventListener('click', function () {
    const trigger = document.querySelector('#tabLista'); // el tab original
    const tab = new bootstrap.Tab(trigger);
    tab.show();
  });

</script>