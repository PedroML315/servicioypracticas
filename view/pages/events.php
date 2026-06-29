<div class="container">
    <h2>Eventos Registrados</h2>
    <button class="btn btn-primary mb-3 registerEventModal">Registrar Evento Nuevo</button>
    <button class="btn btn-primary mb-3 registerEventTypeModal">Tipos de Evento</button>
    <div class="table-responsive">
        <table id="eventsTable" class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tipo de Evento</th>
                    <th>Nombre del Evento</th>
                    <th>Fecha</th>
                    <th>Ubicación</th>
                    <th>Puntos</th>
                    <th>Vacantes Disponibles</th>
                    <th>Descripción</th>
                    <th>Acciones</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
<?php
    include 'view/pages/modals/eventModals.php';
?>
<script src="view/assets/js/ajax/events.js"></script>