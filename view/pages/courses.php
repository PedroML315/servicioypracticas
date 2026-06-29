<div class="container">
    <h2>Cursos Registrados</h2>
    <button class="btn btn-primary mb-3 registerCourseModal">Registrar Curso Nuevo</button>
    <div class="table-responsive">
        <table id="coursesTable" class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre del curso</th>
                    <th>Fecha de inicio</th>
                    <th>Fecha de finalización</th>
                    <th width="10%">Acciones</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<?php
    include 'view/pages/modals/courseModals.php';
?>

<script src="view/assets/js/ajax/courses.js"></script>