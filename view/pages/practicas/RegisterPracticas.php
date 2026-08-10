<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro de Prácticas Profesionales – Universidad Montrer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #01643D;
            --primary-dark: #014d2f;
            --primary-light: #e6f4ee;
        }

        body {
            background: linear-gradient(135deg, #e8f5ee 0%, #f8fafb 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        .page-header {
            background: var(--primary);
            color: #fff;
            padding: 1.25rem 0 0;
            text-align: center;
            border-radius: 0 0 2rem 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 18px rgba(1, 100, 61, .25);
        }

        .page-header img {
            max-height: 56px;
            margin-bottom: .6rem;
            filter: brightness(0) invert(1);
        }

        .page-header h1 {
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0;
            padding-bottom: 1rem;
        }

        .form-card {
            background: #fff;
            border-radius: 1.25rem;
            box-shadow: 0 6px 32px rgba(1, 100, 61, .10);
            overflow: hidden;
            max-width: 860px;
            margin: 0 auto;
        }

        .card-section-header {
            background: var(--primary-light);
            border-bottom: 2px solid rgba(1, 100, 61, .12);
            padding: 1rem 1.75rem .8rem;
        }

        .card-section-header h2 {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--primary);
            margin: 0;
        }

        .card-section-header p {
            font-size: .83rem;
            color: #5a7060;
            margin: .2rem 0 0;
        }

        .form-body {
            padding: 1.75rem;
        }

        .input-icon-wrap {
            position: relative;
        }

        .input-icon-wrap .fas,
        .input-icon-wrap .fa-solid {
            position: absolute;
            left: .9rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9db8a8;
            pointer-events: none;
            font-size: .85rem;
        }

        .input-icon-wrap input,
        .input-icon-wrap select {
            padding-left: 2.2rem !important;
        }

        .form-label {
            font-weight: 600;
            font-size: .87rem;
            color: #344c3d;
            margin-bottom: .3rem;
        }

        .form-text {
            font-size: .78rem;
        }

        #matricula-banner {
            display: none;
            background: var(--primary-light);
            border: 1px solid rgba(1, 100, 61, .25);
            border-radius: .75rem;
            padding: .75rem 1rem;
            margin-bottom: 1.2rem;
            font-size: .88rem;
        }

        #matricula-banner strong {
            color: var(--primary);
        }

        .option-card {
            border: 2px solid #dee2e6;
            border-radius: .9rem;
            padding: 1rem 1.2rem;
            cursor: pointer;
            transition: all .2s;
            display: flex;
            gap: .85rem;
            align-items: flex-start;
            height: 100%;
        }

        .option-card:hover {
            border-color: var(--primary);
            background: var(--primary-light);
        }

        .option-card input[type=radio] {
            margin-top: .2rem;
            accent-color: var(--primary);
            width: 1.1em;
            height: 1.1em;
            flex-shrink: 0;
        }

        .option-card .oc-icon {
            font-size: 1.5rem;
            color: var(--primary);
            flex-shrink: 0;
        }

        .option-card .oc-title {
            font-weight: 700;
            font-size: .93rem;
        }

        .option-card .oc-desc {
            font-size: .81rem;
            color: #6c757d;
            margin-top: .15rem;
        }

        .btn-primary {
            background: var(--primary);
            border-color: var(--primary);
            border-radius: .65rem;
            font-weight: 600;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .submit-button {
            min-width: 160px;
        }

        .submit-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #f0f0f0;
            gap: 1rem;
        }

        @media(max-width:576px) {
            .form-body {
                padding: 1rem;
            }
        }
    </style>
</head>
<?php
require_once __DIR__ . "/../../../controller/forms.controller.php";
$degrees = FormsController::ctrSearchDegrees(null);
?>

<body>

    <div class="page-header">
        <img src="view/assets/images/logo-color.png" alt="Universidad Montrer">
        <h1><i class="fa-solid fa-briefcase me-2"></i>Registro de Prácticas Profesionales</h1>
    </div>

    <div class="container pb-5">
        <div class="form-card">

            <div class="card-section-header">
                <h2><i class="fa-regular fa-id-card me-2"></i>Datos del Alumno</h2>
                <p>Ingresa tu matrícula — el sistema llenará tus datos automáticamente.</p>
            </div>

            <div class="form-body">
                <div id="matricula-banner">
                    <i class="fa-solid fa-circle-check text-success me-1"></i>
                    ¡Datos encontrados! Revisa que la información sea correcta.
                    <strong id="banner-name"></strong>
                </div>

                <form id="registerForm" novalidate>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="matricula" class="form-label">Matrícula <span
                                    class="text-danger">*</span></label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-id-badge"></i>
                                <input type="text" class="form-control" id="matricula" placeholder="Ej. 20230001"
                                    required>
                            </div>
                            <div class="invalid-feedback" id="matricula-feedback">Matrícula no encontrada o inválida.
                            </div>
                            <div class="form-text">
                                <i class="fas fa-spinner fa-spin me-1 text-secondary" id="loadingSpinner"
                                    style="display:none"></i>
                                Tus datos se completarán automáticamente.
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="grupo" class="form-label">Grupo</label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-users"></i>
                                <input type="text" class="form-control" id="grupo" placeholder="Ej. 6A" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="nacimiento" class="form-label">Fecha de nacimiento <span
                                    class="text-danger">*</span></label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-calendar-alt"></i>
                                <input type="date" class="form-control" id="nacimiento" required>
                            </div>
                        </div>

                        <div class="col-md-8">
                            <label for="nombre" class="form-label">Nombre completo <span
                                    class="text-danger">*</span></label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-user"></i>
                                <input type="text" class="form-control" id="nombre"
                                    placeholder="Nombre(s) Apellido Paterno Materno" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="curp" class="form-label">CURP <span class="text-danger">*</span></label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-fingerprint"></i>
                                <input type="text" class="form-control" id="curp" placeholder="LLLL000101HDFABC01"
                                    required maxlength="18" style="text-transform:uppercase">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label for="genero" class="form-label">Género <span class="text-danger">*</span></label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-venus-mars"></i>
                                <select id="genero" class="form-select" required>
                                    <option value="">Selecciona...</option>
                                    <option>Masculino</option>
                                    <option>Femenino</option>
                                    <option>Otro</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="email-alumno" class="form-label">Correo electrónico <span
                                    class="text-danger">*</span></label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-envelope"></i>
                                <input type="email" class="form-control" id="email-alumno"
                                    placeholder="ejemplo@dominio.com" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="telefono-alumno" class="form-label">Teléfono <span
                                    class="text-danger">*</span></label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-mobile-alt"></i>
                                <input type="tel" class="form-control" id="telefono-alumno" placeholder="10 dígitos"
                                    required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="programa" class="form-label">Programa Académico <span
                                    class="text-danger">*</span></label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-university"></i>
                                <select id="programa" class="form-select" required>
                                    <option value="">Selecciona tu programa...</option>
                                    <?php foreach ($degrees as $degree): ?>
                                        <option value="<?= htmlspecialchars($degree['nameDegree']) ?>">
                                            <?= htmlspecialchars($degree['nameDegree']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <input type="hidden" id="periodo">
                    </div>

                    <hr class="my-4">
                    <h6 class="fw-bold text-secondary mb-3"><i class="fas fa-route me-2"></i>Modalidad de Prácticas
                        <span class="text-danger">*</span>
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="option-card d-flex" for="opcionUniv">
                                <input class="form-check-input" type="radio" name="tipoPractica" id="opcionUniv"
                                    value="universidad" required>
                                <div class="ms-3">
                                    <i class="fa-solid fa-school oc-icon mb-1 d-block"></i>
                                    <div class="oc-title">Con la Universidad</div>
                                    <div class="oc-desc">La Universidad asigna el organismo donde realizarás tus
                                        prácticas.</div>
                                </div>
                            </label>
                        </div>
                        <div class="col-md-6">
                            <label class="option-card d-flex" for="opcionEmp">
                                <input class="form-check-input" type="radio" name="tipoPractica" id="opcionEmp"
                                    value="empresa" required>
                                <div class="ms-3">
                                    <i class="fa-solid fa-building oc-icon mb-1 d-block"></i>
                                    <div class="oc-title">Con organismo receptor externo</div>
                                    <div class="oc-desc">Realizarás tus prácticas en un organismo receptor externo que
                                        tú o la Universidad consiguió.</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="form-check mb-2 mt-4">
                        <input class="form-check-input border-secondary" type="checkbox" id="aceptoTerminos"
                            name="aceptoTerminos" required style="pointer-events: none;">
                        <label class="form-check-label fw-bold text-secondary" for="aceptoTerminos">
                            He leído y acepto los
                            <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#modalTerminos"
                                class="text-primary text-decoration-underline">
                                Aviso de privacidad
                            </a> <span class="text-danger">*</span>
                        </label>
                        <div class="invalid-feedback">Es obligatorio abrir y aceptar el Aviso de privacidad.</div>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input border-secondary" type="checkbox" id="aceptoReglamento"
                            name="aceptoReglamento" required style="pointer-events: none;">
                        <label class="form-check-label fw-bold text-secondary" for="aceptoReglamento">
                            He leído y acepto el
                            <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#modalReglamento"
                                class="text-primary text-decoration-underline">
                                Reglamento de Prácticas Profesionales
                            </a> <span class="text-danger">*</span>
                        </label>
                        <div class="invalid-feedback">Es obligatorio abrir y aceptar el Reglamento de Prácticas
                            Profesionales.</div>
                    </div>

                    <div class="submit-row">
                        <span class="text-muted small"><span class="text-danger">*</span> Campos obligatorios</span>
                        <button type="submit" class="btn btn-primary submit-button d-none">
                            <i class="fas fa-paper-plane me-2"></i>Registrarme
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="modalTerminos" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
            aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title text-success fw-bold">
                            <i class="fas fa-file-signature me-2"></i>Aviso de privacidad
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body p-0" style="height: 65vh;">
                        <iframe src="docs/Términos y condiciones Pp (Alumno).pdf#toolbar=0&navpanes=0&scrollbar=0"
                            width="100%" height="100%" style="border: none;"></iframe>
                    </div>
                    <div class="modal-footer bg-light d-flex justify-content-between align-items-center">
                        <span class="text-muted small fw-bold" id="leyendoMensaje">
                            <i class="fas fa-clock me-1"></i> Por favor, lee el documento...
                        </span>
                        <div>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                            <button type="button" class="btn btn-success" id="btnAceptarTerminosModal" disabled>
                                Aceptar Términos
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modalReglamento" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
            aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title text-success fw-bold">
                            <i class="fas fa-file-contract me-2"></i>Reglamento de Prácticas Profesionales
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body p-0" style="height: 65vh;">
                        <iframe src="docs/REGLAMENTO PRÁCTICAS PROFESIONALES.pdf#toolbar=0&navpanes=0&scrollbar=0"
                            width="100%" height="100%" style="border: none;"></iframe>
                    </div>
                    <div class="modal-footer bg-light d-flex justify-content-between align-items-center">
                        <span class="text-muted small fw-bold" id="leyendoMensajeReglamento">
                            <i class="fas fa-clock me-1"></i> Por favor, lee el documento...
                        </span>
                        <div>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                            <button type="button" class="btn btn-success" id="btnAceptarReglamentoModal" disabled>
                                Aceptar Reglamento
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        function debounce(fn, delay) {
            let timer;
            return function (...args) { clearTimeout(timer); timer = setTimeout(() => fn.apply(this, args), delay); };
        }

        $(function () {
            disableForm(true);
            const genderMap = { 'F': 'Femenino', 'M': 'Masculino', 'O': 'Otro' };
            const matInput = document.getElementById('matricula');
            const spinner = document.getElementById('loadingSpinner');
            const banner = document.getElementById('matricula-banner');

            // Búsqueda de datos del alumno
            matInput.addEventListener('input', debounce(async function () {
                const val = this.value.trim();
                banner.style.display = 'none';
                if (!/^\d+$/.test(val)) {
                    disableForm(true);
                    $('#matricula-feedback').text('La matrícula debe ser numérica.');
                    this.classList.add('is-invalid'); this.classList.remove('is-valid');
                    return;
                }
                spinner.style.display = 'inline-block';
                try {
                    const res = await fetch('controller/ajax/ajax.forms.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ action: 'checkMatricula', matricula: val })
                    });
                    const response = await res.json();

                    if (response.student && response.student.id) {
                        disableForm(false);
                        this.classList.remove('is-invalid'); this.classList.add('is-valid');
                        const s = response.student;
                        const a = response.academic || {};

                        if (a.nombre_periodo == 'Trimestral') {
                            $('#matricula-feedback').text('Programa trimestral no elegible para prácticas.');
                            disableForm(true); spinner.style.display = 'none'; return;
                        }

                        $('#grupo').val(s.CODIGO_GRUPO);
                        $('#nombre').val(`${s.NOMBRE} ${s.MATERNO} ${s.PATERNO}`);
                        $('#curp').val((s.CURP || a.claveCiudadano || '').toUpperCase());
                        $('#nacimiento').val(s.fecha_nacimiento || (a.fechaNacimiento || '').split(' ')[0] || '');
                        $('#genero').val(genderMap[s.genero] || genderMap[a.genero] || '');
                        $('#email-alumno').val(s.correo_institucional || a.matricula + '@unimontrer.edu.mx');
                        $('#telefono-alumno').val(s.telefono || a.telefono || '');

                        let programa = '';
                        if (s.CODIGO_GRUPO && s.CODIGO_GRUPO.includes('FISIO')) programa = 'FISIOTERAPIA';
                        else if (a.nameOferta) {
                            programa = a.nameOferta.toUpperCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/\u00d1/g, 'N').replace(/\u00f1/g, 'N');
                        }
                        if (programa) {
                            if (!$('#programa option[value="' + programa + '"]').length) $('#programa').append(new Option(programa, programa));
                            $('#programa').val(programa);
                        }
                        if (s.CODIGO_GRUPO) {
                            const m = s.CODIGO_GRUPO.match(/(\d+)/);
                            if (m) $('#periodo').val(m[1]);
                        }
                        banner.style.display = 'block';
                        document.getElementById('banner-name').textContent = ' — ' + s.NOMBRE + ' ' + s.PATERNO + ' ' + s.MATERNO;

                    } else if (response.academic) {
                        if (response.academic.nombre_periodo === 'Trimestre') {
                            $('#matricula-feedback').text('Programa trimestral no elegible para prácticas profesionales.');
                            this.classList.add('is-invalid'); this.classList.remove('is-valid');
                            disableForm(true);
                        }
                    } else {
                        disableForm(true);
                        $('#matricula-feedback').text('Matrícula no encontrada. Verifica e intenta de nuevo.');
                        this.classList.add('is-invalid'); this.classList.remove('is-valid');
                    }
                } catch (err) {
                    disableForm(true);
                    $('#matricula-feedback').text('Error al procesar la solicitud. Intenta de nuevo.');
                    this.classList.add('is-invalid'); this.classList.remove('is-valid');
                    console.error(err);
                }
                spinner.style.display = 'none';
            }, 800));

            // ── LÓGICA DE DOCUMENTOS DE ACEPTACIÓN (aviso de privacidad y reglamento) ──
            function configurarDocumentoAceptacion(cfg) {
                let aceptado = false;
                let temporizadorLectura;

                $(cfg.modal).on('shown.bs.modal', function () {
                    if (aceptado) return;
                    let tiempoRestante = 5; // Segundos obligatorios
                    $(cfg.mensaje).html(`<i class="fas fa-clock me-1"></i> Podrás aceptar en ${tiempoRestante} segundos...`).removeClass('text-success').addClass('text-muted');
                    $(cfg.boton).prop('disabled', true);

                    clearInterval(temporizadorLectura);
                    temporizadorLectura = setInterval(function () {
                        tiempoRestante--;
                        $(cfg.mensaje).html(`<i class="fas fa-clock me-1"></i> Podrás aceptar en ${tiempoRestante} segundos...`);

                        if (tiempoRestante <= 0) {
                            clearInterval(temporizadorLectura);
                            $(cfg.mensaje).html(`<i class="fas fa-check-circle me-1"></i> Ya puedes aceptar el documento.`).removeClass('text-muted').addClass('text-success');
                            $(cfg.boton).prop('disabled', false);
                        }
                    }, 1000);
                });

                $(cfg.modal).on('hidden.bs.modal', function () {
                    if (!aceptado) {
                        clearInterval(temporizadorLectura);
                    }
                });

                $(cfg.boton).click(function () {
                    aceptado = true;
                    $(cfg.checkbox).prop('checked', true).removeClass('is-invalid');
                    $(cfg.modal).modal('hide');
                    $(cfg.mensaje).html(`<i class="fas fa-check-circle me-1"></i> ${cfg.textoAceptado}`);
                });
            }

            configurarDocumentoAceptacion({
                modal: '#modalTerminos',
                mensaje: '#leyendoMensaje',
                boton: '#btnAceptarTerminosModal',
                checkbox: '#aceptoTerminos',
                textoAceptado: 'Términos aceptados.'
            });

            configurarDocumentoAceptacion({
                modal: '#modalReglamento',
                mensaje: '#leyendoMensajeReglamento',
                boton: '#btnAceptarReglamentoModal',
                checkbox: '#aceptoReglamento',
                textoAceptado: 'Reglamento aceptado.'
            });

            // ── ENVÍO DE FORMULARIO ──
            $('#registerForm').on('submit', function (e) {
                e.preventDefault();
                if (!this.checkValidity()) {
                    Swal.fire({ icon: 'warning', title: 'Campos incompletos', text: 'Por favor completa todos los campos obligatorios, incluyendo la aceptación de términos.', confirmButtonColor: '#01643D' });
                    $(this).addClass('was-validated');
                    return;
                }
                const formData = {
                    action: 'registerStudentPracticas',
                    matricula: $('#matricula').val().trim(),
                    grupo: $('#grupo').val().trim(),
                    nombre: $('#nombre').val().trim(),
                    curp: $('#curp').val().trim(),
                    nacimiento: $('#nacimiento').val(),
                    genero: $('#genero').val(),
                    email: $('#email-alumno').val().trim(),
                    telefono: $('#telefono-alumno').val().trim(),
                    programa: $('#programa').val(),
                    periodo: $('#periodo').val(),
                    tipoPractica: $('input[name="tipoPractica"]:checked').val(),
                    aceptoTerminos: $('#aceptoTerminos').is(':checked') ? 1 : 0,
                    aceptoReglamento: $('#aceptoReglamento').is(':checked') ? 1 : 0
                };

                $.ajax({
                    url: 'controller/ajax/ajax.forms.php',
                    method: 'POST',
                    data: formData,
                    beforeSend: function () {
                        Swal.fire({ title: 'Procesando...', text: 'Enviando datos, por favor espera', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                    },
                    success: function (response) {
                        try {
                            const json = typeof response === 'string' ? JSON.parse(response) : response;
                            if (json.status === 'success') {
                                Swal.fire({ icon: 'success', title: '¡Registro exitoso!', text: json.message, confirmButtonColor: '#01643D' })
                                    .then(() => { window.location.href = './'; });
                            } else {
                                Swal.fire({ icon: 'error', title: 'Error al registrar', text: json.message || 'Intenta nuevamente.', confirmButtonColor: '#01643D' });
                            }
                        } catch {
                            Swal.fire({ icon: 'error', title: 'Error inesperado', text: 'Respuesta del servidor no válida.', confirmButtonColor: '#01643D' });
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error(status, error);
                        Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'Falló la conexión con el servidor.', confirmButtonColor: '#01643D' });
                    }
                });
            });

            // Habilita/Deshabilita los inputs excluyendo matricula
            function disableForm(lock = true) {
                document.getElementById('registerForm').querySelectorAll('input, select, textarea').forEach(el => {
                    if (el.id === 'matricula') return;
                    el.disabled = lock;
                });
                document.querySelectorAll('.submit-button').forEach(btn => btn.classList.toggle('d-none', lock));
            }
        });
    </script>
</body>

</html>