<div class="container">
    <h2>Licenciaturas Registradas</h2>
    <button class="btn btn-primary mb-3 registerLicenciaturaModal">Registrar Nueva Licenciatura</button>
    <div class="table-responsive">
        <table id="degreesTable" class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Licenciatura</th>
                    <th>Puntage minimo requerido</th>
                    <th>Acciones</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
<?php
    include 'view/pages/modals/degreesModals.php';
?>
<script src="view/assets/js/ajax/degrees.js"></script>