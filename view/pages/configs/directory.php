<?php
// view/pages/configs/directory.php — Directorio institucional
// Lista de directores, vicerrectores y responsables de área a quienes se avisa
// cuando se aprueba una vacante de prácticas profesionales.

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];
?>
<meta name="csrf-token" content="<?= htmlspecialchars($csrf) ?>">

<style>
    /* style.css global pinta h1–h5 en blanco; aquí van sobre fondo claro. */
    .dir-wrap h1,
    .dir-wrap h2,
    .dir-wrap h3,
    .dir-wrap h4,
    .dir-wrap h5 {
        color: #111827;
    }

    .dir-intro {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 12px;
        padding: 14px 18px;
        margin-bottom: 18px;
        display: flex;
        gap: 12px;
        align-items: flex-start;
    }

    .dir-intro i {
        color: #2563eb;
        font-size: 1.05rem;
        margin-top: 2px;
    }

    .dir-intro p {
        margin: 0;
        font-size: .88rem;
        color: #1e3a8a;
        line-height: 1.5;
    }

    .dir-card {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 20px 24px;
        background: #fff;
    }

    .dir-card-head {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 4px;
    }

    .dir-card-head h2 {
        font-size: 1rem;
        font-weight: 600;
        margin: 0;
    }

    .dir-card-desc {
        font-size: .875rem;
        color: #6b7280;
        margin-bottom: 16px;
    }

    .dir-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        margin-bottom: 14px;
    }

    .dir-count {
        font-size: .82rem;
        color: #64748b;
        margin-left: auto;
    }

    #dirTable td {
        vertical-align: middle;
    }

    .dir-job {
        display: inline-block;
        background: #f1f5f9;
        color: #475569;
        border-radius: 999px;
        padding: 3px 12px;
        font-size: .78rem;
    }

    .dir-modal .modal-header {
        display: flex;
        gap: 14px;
        align-items: flex-start;
    }

    .dir-modal-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: grid;
        place-items: center;
        background: #dbeafe;
        color: #2563eb;
        font-size: 1.05rem;
        flex: none;
    }

    .dir-modal .modal-title {
        font-size: 1rem;
        font-weight: 600;
        margin: 0;
        color: #111827;
    }

    .dir-modal .modal-subtitle {
        font-size: .82rem;
        color: #6b7280;
        margin: 2px 0 0;
    }
</style>

<div class="dir-wrap">

    <div id="dirAlert"></div>

    <div class="dir-intro">
        <i class="fa-solid fa-circle-info"></i>
        <p>
            Cada persona de esta lista recibe un correo automático con los datos de la vacante
            <strong>cada vez que se aprueba una solicitud de practicantes</strong>, para que pueda
            difundirla entre sus estudiantes. Mantén la lista al día: si alguien deja de aparecer aquí,
            deja de recibir esos avisos.
        </p>
    </div>

    <div class="dir-card">
        <div class="dir-card-head">
            <h2>Directorio institucional</h2>
            <button type="button" class="btn btn-primary" id="dirBtnNew">
                <i class="fa-solid fa-plus me-1"></i> Agregar persona
            </button>
        </div>
        <p class="dir-card-desc">
            Directores de escuela, vicerrectores y responsables de área.
        </p>

        <div class="dir-toolbar">
            <button type="button" class="btn btn-light text-danger" id="dirBtnDeleteSelected" disabled>
                <i class="fa-solid fa-trash me-1"></i> Eliminar seleccionadas
            </button>
            <span class="dir-count" id="dirCount">0 personas</span>
        </div>

        <table id="dirTable" class="display" style="width:100%">
            <thead>
                <tr>
                    <th style="width:20px"></th>
                    <th>Nombre</th>
                    <th>Correo electrónico</th>
                    <th>Cargo</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- ══ Modal: alta / edición ══ -->
<div class="modal fade dir-modal" id="dirModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <div class="dir-modal-icon"><i class="fa-solid fa-address-book"></i></div>
                <div>
                    <h5 class="modal-title" id="dirModalTitle">Agregar persona</h5>
                    <p class="modal-subtitle text-white">Recibirá los avisos de vacantes aprobadas.</p>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="dirModalAlert"></div>
                <input type="hidden" id="dirFieldId">
                <div class="mb-3">
                    <label class="form-label" for="dirFieldName">Nombre completo</label>
                    <input type="text" class="form-control" id="dirFieldName" maxlength="150"
                        placeholder="Ej. Ramírez Rodríguez Martha Citlali">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="dirFieldEmail">Correo electrónico</label>
                    <input type="email" class="form-control" id="dirFieldEmail" maxlength="150"
                        placeholder="Ej. arquitecturaeingenierias@unimontrer.edu.mx">
                    <div class="form-text">No puede repetirse: cada persona aparece una sola vez en el directorio.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="dirFieldJob">Cargo</label>
                    <input type="text" class="form-control" id="dirFieldJob" maxlength="100"
                        placeholder="Ej. Directora de la Escuela de Arquitectura, Espacio y Diseño">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="dirBtnSave">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script>
    window.DIR_CONFIG = {
        csrf: <?= json_encode($csrf) ?>,
        endpoint: 'controller/ajax/directory.php'
    };
</script>
<script src="view/assets/js/configs/directory.js"></script>
