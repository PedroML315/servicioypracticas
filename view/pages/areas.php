<div class="container">
    <h2>Areas Registradas</h2>
    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addAreaModal">Registrar Nueva Area</button>
    <div class="table-responsive">
        <table id="areasTable" class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre del area</th>
                    <th width="10%">Acciones</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- Modal Agregar Área -->
<div class="modal fade" id="addAreaModal" tabindex="-1" role="dialog" aria-labelledby="addAreaModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addAreaModalLabel">Registrar Nueva Área</h5>
                <button type="button" class="btn btn-close" data-bs-dismiss="modal" aria-label="Cerrar">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="addAreaForm">
                    <div class="form-group mb-3">
                        <label for="addAreaName">Nombre del área</label>
                        <input type="text" class="form-control" id="addAreaName" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Registrar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Área -->
<div class="modal fade" id="editAreaModal" tabindex="-1" role="dialog" aria-labelledby="editAreaModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editAreaModalLabel">Editar Área</h5>
                <button type="button" class="btn btn-close" data-bs-dismiss="modal" aria-label="Cerrar">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="editAreaForm">
                    <input type="hidden" id="editAreaId">
                    <div class="form-group mb-3">
                        <label for="editAreaName">Nombre del área</label>
                        <input type="text" class="form-control" id="editAreaName" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Eliminar Área -->
<div class="modal fade" id="deleteAreaModal" tabindex="-1" role="dialog" aria-labelledby="deleteAreaModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteAreaModalLabel">Eliminar Área</h5>
                <button type="button" class="btn btn-close" data-bs-dismiss="modal" aria-label="Cerrar">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <div class="modal-body">
                <div style="font-family: Arial, sans-serif; color: #333; line-height: 1.6; padding: 15px; background-color: #f8f9fa; border-radius: 5px;">
                    <p style="font-size: 16px; font-weight: bold; color: #01643D;">¿Estás seguro de que deseas eliminar esta área?</p>
                    <p style="font-size: 14px; margin-top: 10px;">esta acción es irreversible.</p>
                </div>
                <input type="hidden" id="deleteAreaId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteArea">Eliminar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="userToAreaModal" tabindex="-1" role="dialog" aria-labelledby="userToAreaModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="userToAreaModalLabel">Seleccione un Usuario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form>
          <div class="form-group">
            <label for="userToArea">Usuarios</label>
            <select class="form-control" id="userToArea">
              <!-- Las opciones se cargarán aquí dinámicamente -->
            </select>
            <input type="hidden" id="areaSelected">
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-primary" id="saveUserToArea">Guardar cambios</button>
      </div>
    </div>
  </div>
</div>


<script src="view/assets/js/ajax/areas.js"></script>
