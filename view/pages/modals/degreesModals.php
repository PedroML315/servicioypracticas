
<!-- Modal para registrar una nueva licenciatura -->
<div class="modal fade" id="registerLicenciaturaModal" tabindex="-1" aria-labelledby="registerLicenciaturaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title" id="registerLicenciaturaModalLabel">Registrar nueva licenciatura</h5>
                <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="licenciaturaForm">
                    <div class="mb-3">
                        <label for="nombreLicenciatura" class="form-label">Nombre de la licenciatura</label>
                        <input type="text" class="form-control" id="nombreLicenciatura" name="nombreLicenciatura" placeholder="Ingrese el nombre de la licenciatura" required>
                    </div>
                    <div class="mb-3">
                        <label for="puntajeMinimo" class="form-label">Puntaje mínimo</label>
                        <input type="number" class="form-control" id="puntajeMinimo" name="puntajeMinimo" placeholder="Ingrese el puntaje mínimo" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="licenciaturaForm" class="btn btn-primary">Guardar</button>
            </div>
        </div>
    </div>
</div>


<!-- Modal para editar una nueva licenciatura -->
 <!-- Modal para editar una nueva licenciatura -->
<div class="modal fade" id="editDegreeModal" tabindex="-1" aria-labelledby="editLicenciaturaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title" id="editLicenciaturaModalLabel">Editar licenciatura</h5>
                <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="editLicenciaturaForm">
                    <input type="hidden" id="editId" name="editId">
                    <div class="mb-3">
                        <label for="editDegreeName" class="form-label">Nombre de la licenciatura</label>
                        <input type="text" class="form-control" id="editDegreeName" name="editDegreeName" placeholder="Ingrese el nombre de la licenciatura" required>
                    </div>
                    <div class="mb-3">
                        <label for="editDegreeMinPoints" class="form-label">Puntaje mínimo</label>
                        <input type="number" class="form-control" id="editDegreeMinPoints" name="editDegreeMinPoints" placeholder="Ingrese el puntaje mínimo" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary editLicenciaturaForm">Guardar</button>
            </div>
        </div>
    </div>
</div>