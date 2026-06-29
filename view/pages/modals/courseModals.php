
<!-- Modal para registrar curso nuevo -->
<div class="modal fade" id="registerCourseModal" tabindex="-1" aria-labelledby="registerCourseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title" id="registerCourseModalLabel">Registrar Curso Nuevo</h5>
            </div>
            <div class="modal-body">
                <form id="registerCourseForm">
                    <div class="form-group">
                        <label for="nameCourse">Nombre del curso:</label>
                        <input type="text" class="form-control" id="nameCourse" name="nameCourse" placeholder="Ingrese el nombre del curso" required>
                    </div>
                    <div class="row my-3">
                        <div class="form-group col-6">
                            <label for="startCourse">Fecha de inicio:</label>
                            <input type="date" class="form-control" id="startCourse" name="startCourse" required>
                        </div>
                        <div class="form-group col-6">
                            <label for="endCourse">Fecha de finalización:</label>
                            <input type="date" class="form-control" id="endCourse" name="endCourse" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Registrar</button>
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal para editar curso -->
<div class="modal fade" id="editCourseModal" tabindex="-1" aria-labelledby="editCourseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCourseModalLabel">Editar Curso</h5>
            </div>
            <div class="modal-body">
                <form id="editCourseForm">
                    <input type="hidden" id="editCourseId" name="idCourse">
                    <div class="form-group">
                        <label for="editNameCourse">Nombre del curso:</label>
                        <input type="text" class="form-control" id="editNameCourse" name="nameCourse" placeholder="Ingrese el nombre del curso" required>
                    </div>
                    <div class="row my-3">
                        <div class="form-group col-6">
                            <label for="editStartCourse">Fecha de inicio:</label>
                            <input type="date" class="form-control" id="editStartCourse" name="startCourse" required>
                        </div>
                        <div class="form-group col-6">
                            <label for="editEndCourse">Fecha de finalización:</label>
                            <input type="date" class="form-control" id="editEndCourse" name="endCourse" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>