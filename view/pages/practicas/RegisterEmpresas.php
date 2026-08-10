<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro de Organismo Receptor – Universidad Montrer</title>
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

        /* ── Stepper ── */
        .stepper-wrap {
            display: flex;
            justify-content: center;
            gap: 0;
            margin-bottom: 2rem;
            padding: 0 1rem;
        }

        .stepper-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            max-width: 180px;
            position: relative;
        }

        .stepper-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 20px;
            left: 60%;
            width: calc(100% - 20px);
            height: 3px;
            background: #dee2e6;
            z-index: 0;
            transition: background .4s;
        }

        .stepper-step.done:not(:last-child)::after,
        .stepper-step.active:not(:last-child)::after {
            background: var(--primary);
        }

        .stepper-circle {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: .95rem;
            background: #dee2e6;
            color: #6c757d;
            border: 3px solid #dee2e6;
            z-index: 1;
            transition: all .3s;
            position: relative;
        }

        .stepper-step.active .stepper-circle {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
            transform: scale(1.1);
            box-shadow: 0 0 0 5px rgba(1, 100, 61, .15);
        }

        .stepper-step.done .stepper-circle {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }

        .stepper-label {
            font-size: .72rem;
            text-align: center;
            margin-top: .35rem;
            font-weight: 600;
            color: #6c757d;
        }

        .stepper-step.active .stepper-label,
        .stepper-step.done .stepper-label {
            color: var(--primary);
        }

        /* ── Card ── */
        .form-card {
            background: #fff;
            border-radius: 1.25rem;
            box-shadow: 0 6px 32px rgba(1, 100, 61, .10);
            overflow: hidden;
            max-width: 860px;
            margin: 0 auto;
        }

        .step {
            display: none;
        }

        .step.active {
            display: block;
        }

        .step-header {
            background: var(--primary-light);
            border-bottom: 2px solid rgba(1, 100, 61, .12);
            padding: 1.1rem 1.75rem .85rem;
        }

        .step-header h2 {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--primary);
            margin: 0;
        }

        .step-header p {
            font-size: .83rem;
            color: #5a7060;
            margin: .2rem 0 0;
        }

        .form-body {
            padding: 1.75rem;
        }

        /* ── Inputs ── */
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

        .input-icon-wrap textarea {
            padding-left: 2.2rem !important;
            padding-top: .5rem;
        }

        .input-icon-wrap .textarea-icon {
            top: .85rem;
            transform: none;
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

        .required::after {
            content: " *";
            color: #dc3545;
        }

        /* ── Tipo de persona ── */
        .tipo-card {
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

        .tipo-card:hover {
            border-color: var(--primary);
            background: var(--primary-light);
        }

        .tipo-card input[type=radio] {
            margin-top: .2rem;
            accent-color: var(--primary);
            width: 1.2em;
            height: 1.2em;
            flex-shrink: 0;
        }

        .tipo-card .tc-icon {
            font-size: 1.8rem;
            color: var(--primary);
            flex-shrink: 0;
            display: block;
            margin-bottom: .3rem;
        }

        .tipo-card .tc-title {
            font-weight: 700;
            font-size: .95rem;
        }

        .tipo-card .tc-desc {
            font-size: .81rem;
            color: #6c757d;
            margin-top: .1rem;
        }

        /* ── Docs ── */
        .doc-item {
            background: #f8fafb;
            border: 1px solid #dee2e6;
            border-radius: .6rem;
            padding: .75rem 1rem;
            margin-bottom: .6rem;
        }

        .doc-item label {
            font-weight: 600;
            font-size: .86rem;
            color: #344c3d;
            display: block;
            margin-bottom: .4rem;
        }

        /* ── Compromisos ── */
        .compromise-list li {
            font-size: .9rem;
            padding: .3rem 0;
        }

        .compromise-list li::marker {
            color: var(--primary);
            font-size: 1.1rem;
        }

        /* ── Botones ── */
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

        .btn-secondary {
            border-radius: .65rem;
            font-weight: 600;
        }

        .btn-success {
            border-radius: .65rem;
            font-weight: 600;
        }

        .btn-action-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #f0f0f0;
        }

        @media(max-width:576px) {
            .stepper-label {
                display: none;
            }

            .form-body {
                padding: 1rem;
            }
        }
    </style>
</head>

<body>

    <div class="page-header">
        <img src="view/assets/images/logo-color.png" alt="Universidad Montrer">
        <h1><i class="fa-solid fa-building-columns me-2"></i>Registro de Organismo Receptor</h1>
    </div>

    <div class="container pb-5">

        <!-- ── Stepper Ordenado ── -->
        <div class="stepper-wrap">
            <div class="stepper-step active" data-step="0">
                <div class="stepper-circle"><i class="fa-solid fa-folder-open"></i></div>
                <div class="stepper-label">Documentos</div>
            </div>
            <div class="stepper-step" data-step="1">
                <div class="stepper-circle"><i class="fa-solid fa-user-shield"></i></div>
                <div class="stepper-label">Representante</div>
            </div>
            <div class="stepper-step" data-step="2">
                <div class="stepper-circle"><i class="fa-solid fa-building"></i></div>
                <div class="stepper-label">Empresa y Dirección</div>
            </div>
        </div>

        <div class="form-card">
            <form id="evaluationForm" enctype="multipart/form-data" novalidate>

                <!-- ════ PASO 1: Compromisos y Documentos ════ -->
                <div class="step active" id="step-0">
                    <div class="step-header">
                        <h2><i class="fa-solid fa-handshake me-2"></i>Compromisos y Documentación</h2>
                        <p>Lee los compromisos y adjunta los documentos de tu organización.</p>
                    </div>
                    <div class="form-body">
                        <div class="alert alert-light border-start border-4 mb-4"
                            style="border-color:var(--primary) !important">
                            <h6 class="fw-bold text-success mb-2">
                                <i class="fas fa-info-circle me-1"></i>Al registrarte como Organismo Receptor te
                                comprometes a:
                            </h6>
                            <ul class="compromise-list mb-0">
                                <li>Asignar actividades relevantes para la formación académica del estudiante.</li>
                                <li>Mantener un ambiente seguro y propicio para el aprendizaje.</li>
                                <li>Respetar los horarios acordados con la Universidad Montrer.</li>
                                <li>Facilitar la supervisión adecuada del estudiante durante sus prácticas.</li>
                                <li>Emitir los documentos requeridos (carta de aceptación, reportes, etc.).</li>
                            </ul>
                        </div>

                        <h6 class="fw-bold mb-3">
                            ¿Cómo está constituida tu organización? <span class="text-danger">*</span>
                        </h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="tipo-card d-flex" for="personaMoral">
                                    <input type="radio" class="form-check-input" name="tipoPersona" id="personaMoral"
                                        value="moral" required>
                                    <div>
                                        <i class="fa-solid fa-landmark tc-icon"></i>
                                        <div class="tc-title">Persona Moral</div>
                                        <div class="tc-desc">Empresa, institución, asociación u organización con RFC
                                            corporativo.</div>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="tipo-card d-flex" for="personaFisica">
                                    <input type="radio" class="form-check-input" name="tipoPersona" id="personaFisica"
                                        value="fisica" required>
                                    <div>
                                        <i class="fa-solid fa-user-tie tc-icon"></i>
                                        <div class="tc-title">Persona Física</div>
                                        <div class="tc-desc">Profesionista independiente o negocio registrado a nombre
                                            propio.</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div id="documentosRequeridos" class="mt-3"></div>

                        <div class="btn-action-row">
                            <span class="text-muted small">Selecciona el tipo de persona para continuar.</span>
                            <button type="button" class="btn btn-primary next-step" id="btnNext0" disabled>
                                Siguiente <i class="fas fa-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ════ PASO 2: Representante Legal y Contacto ════ -->
                <div class="step" id="step-1">
                    <div class="step-header">
                        <h2><i class="fa-solid fa-user-shield me-2"></i>Responsables del Organismo</h2>
                        <p>Proporciona los datos del representante legal y del contacto operativo.</p>
                    </div>
                    <div class="form-body">
                        <h6 class="fw-bold text-secondary mb-3">Datos del Representante Legal</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label required">Nombre</label>
                                <div class="input-icon-wrap"><i class="fas fa-user"></i>
                                    <input type="text" class="form-control" name="rep_legal"
                                        placeholder="Nombre del representante legal" required>
                                </div>
                                <div class="invalid-feedback">Ingresa el nombre del representante.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Cargo</label>
                                <div class="input-icon-wrap"><i class="fas fa-id-badge"></i>
                                    <input type="text" class="form-control" name="cargo_legal"
                                        placeholder="Ej. Director General" required>
                                </div>
                                <div class="invalid-feedback">Ingresa el cargo.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Correo del Representante</label>
                                <div class="input-icon-wrap"><i class="fas fa-envelope"></i>
                                    <input type="email" class="form-control" name="email_legal"
                                        placeholder="correo@empresa.com" required>
                                </div>
                                <div class="invalid-feedback">Ingresa un correo válido.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Teléfono de Oficina</label>
                                <div class="input-icon-wrap"><i class="fas fa-phone-alt"></i>
                                    <input type="tel" class="form-control" name="tel_oficina"
                                        placeholder="Ej. 4431234567 ext. 10" required>
                                </div>
                                <div class="invalid-feedback">Ingresa un teléfono válido.</div>
                            </div>
                        </div>

                        <hr class="my-4">
                        <h6 class="fw-bold text-secondary mb-3">Responsable Operativo del Programa</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label required">Nombre del Responsable</label>
                                <div class="input-icon-wrap"><i class="fas fa-user-tie"></i>
                                    <input type="text" class="form-control" name="nombre_contacto"
                                        placeholder="Ej. Lic. Juan Pérez López" required>
                                </div>
                                <div class="invalid-feedback">Ingresa el nombre del contacto.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Teléfono Directo</label>
                                <div class="input-icon-wrap"><i class="fas fa-phone"></i>
                                    <input type="tel" class="form-control" name="telefonos" placeholder="Ej. 4431234567"
                                        required>
                                </div>
                                <div class="invalid-feedback">Ingresa un teléfono.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Correo Electrónico</label>
                                <div class="input-icon-wrap"><i class="fas fa-envelope"></i>
                                    <input type="email" class="form-control" name="email"
                                        placeholder="contacto@empresa.com" required>
                                </div>
                                <div class="invalid-feedback">Ingresa un correo válido.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Celular <span class="text-muted">(opcional)</span></label>
                                <div class="input-icon-wrap"><i class="fas fa-mobile-alt"></i>
                                    <input type="tel" class="form-control" name="celular" placeholder="10 dígitos">
                                </div>
                            </div>
                        </div>

                        <div class="btn-action-row">
                            <button type="button" class="btn btn-secondary prev-step"><i
                                    class="fas fa-arrow-left me-1"></i> Anterior</button>
                            <button type="button" class="btn btn-primary next-step">Siguiente <i
                                    class="fas fa-arrow-right ms-1"></i></button>
                        </div>
                    </div>
                </div>

                <!-- ════ PASO 3: Empresa, Dirección y Términos ════ -->
                <div class="step" id="step-2">
                    <div class="step-header">
                        <h2><i class="fa-solid fa-building me-2"></i>Información de la Empresa</h2>
                        <p>Datos generales, domicilio y actividades a realizar.</p>
                    </div>
                    <div class="form-body">
                        <h6 class="fw-bold text-secondary mb-3">Datos Generales</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label required">Nombre del Organismo / Empresa</label>
                                <div class="input-icon-wrap"><i class="fas fa-building"></i>
                                    <input type="text" class="form-control" name="empresa"
                                        placeholder="Ej. ACME S.A. de C.V." required>
                                </div>
                                <div class="invalid-feedback">Ingresa el nombre.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Giro o Actividad Principal</label>
                                <div class="input-icon-wrap"><i class="fas fa-industry"></i>
                                    <input type="text" class="form-control" name="giro"
                                        placeholder="Ej. Educación, Salud, etc." required>
                                </div>
                                <div class="invalid-feedback">Ingresa el giro.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Fecha de Constitución <span
                                        class="text-muted">(opcional)</span></label>
                                <div class="input-icon-wrap"><i class="fas fa-calendar-alt"></i>
                                    <input type="date" class="form-control" name="fecha_constitucion">
                                </div>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Página Web <span class="text-muted">(opcional)</span></label>
                                <div class="input-icon-wrap"><i class="fas fa-globe"></i>
                                    <input type="url" class="form-control" name="web"
                                        placeholder="https://www.empresa.com">
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">
                        <h6 class="fw-bold text-secondary mb-3"><i class="fas fa-map-marker-alt me-1"></i>Domicilio del
                            Organismo</h6>
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label required">Calle y Número</label>
                                <div class="input-icon-wrap"><i class="fas fa-road"></i>
                                    <input type="text" class="form-control" name="calle" required>
                                </div>
                                <div class="invalid-feedback">Ingresa la dirección.</div>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label required">Colonia</label>
                                <div class="input-icon-wrap"><i class="fas fa-map-signs"></i>
                                    <input type="text" class="form-control" name="colonia" required>
                                </div>
                                <div class="invalid-feedback">Ingresa la colonia.</div>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label required">Ciudad</label>
                                <div class="input-icon-wrap"><i class="fas fa-city"></i>
                                    <input type="text" class="form-control" name="ciudad" required>
                                </div>
                                <div class="invalid-feedback">Ingresa la ciudad.</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label required">Código Postal</label>
                                <div class="input-icon-wrap"><i class="fas fa-mail-bulk"></i>
                                    <input type="text" class="form-control" name="cp" required pattern="[0-9]{5}">
                                </div>
                                <div class="invalid-feedback">Ingresa un CP válido.</div>
                            </div>
                        </div>

                        <div class="alert mt-4 mb-0 p-3"
                            style="background:linear-gradient(135deg,#e6f4ee,#f0faf4);border:1px solid #b2d8c5;border-radius:.75rem;">
                            <div class="d-flex align-items-start gap-2">
                                <i class="fas fa-shield-alt mt-1" style="color:var(--primary);font-size:1.15rem;"></i>
                                <div>
                                    <strong style="color:var(--primary);font-size:.9rem;">Tus datos están
                                        protegidos</strong>
                                    <p class="mb-0 mt-1" style="font-size:.82rem;color:#4a6b58;">
                                        La información que proporciones será utilizada <strong>exclusivamente</strong>
                                        para los fines académicos y administrativos del programa de prácticas
                                        profesionales de la <strong>Universidad Montrer</strong>. Tus datos no serán
                                        compartidos con terceros, vendidos ni utilizados con fines comerciales y son
                                        tratados conforme a la <em>Ley Federal de Protección de Datos Personales en
                                            Posesión de los Particulares (LFPDPPP)</em>.
                                    </p>
                                </div>
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
                            <div class="invalid-feedback">Es obligatorio abrir y aceptar los Aviso de privacidad.</div>
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

                        <div class="btn-action-row">
                            <button type="button" class="btn btn-secondary prev-step"><i
                                    class="fas fa-arrow-left me-1"></i> Anterior</button>
                            <button type="submit" class="btn btn-success px-4"><i
                                    class="fas fa-paper-plane me-2"></i>Enviar registro</button>
                        </div>
                    </div>
                </div>

                <!-- ════ Modal Visor de PDF (Aviso de privacidad) ════ -->
                <div class="modal fade" id="modalTerminos" data-bs-backdrop="static" data-bs-keyboard="false"
                    tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header bg-light">
                                <h5 class="modal-title text-success fw-bold">
                                    <i class="fas fa-file-signature me-2"></i>Aviso de privacidad
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Cerrar"></button>
                            </div>
                            <div class="modal-body p-0" style="height: 65vh;">
                                <iframe
                                    src="docs/Términos y condiciones Pp (Organismo externo).pdf#toolbar=0&navpanes=0&scrollbar=0"
                                    width="100%" height="100%" style="border: none;"></iframe>
                            </div>
                            <div class="modal-footer bg-light d-flex justify-content-between align-items-center">
                                <span class="text-muted small fw-bold" id="leyendoMensaje">
                                    <i class="fas fa-clock me-1"></i> Por favor, lee el documento...
                                </span>
                                <div>
                                    <button type="button" class="btn btn-secondary"
                                        data-bs-dismiss="modal">Cerrar</button>
                                    <button type="button" class="btn btn-success" id="btnAceptarTerminosModal" disabled>
                                        Aceptar Términos
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ════ Modal Visor de PDF (Reglamento de Prácticas Profesionales) ════ -->
                <div class="modal fade" id="modalReglamento" data-bs-backdrop="static" data-bs-keyboard="false"
                    tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header bg-light">
                                <h5 class="modal-title text-success fw-bold">
                                    <i class="fas fa-file-contract me-2"></i>Reglamento de Prácticas Profesionales
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Cerrar"></button>
                            </div>
                            <div class="modal-body p-0" style="height: 65vh;">
                                <iframe
                                    src="docs/REGLAMENTO PRÁCTICAS PROFESIONALES.pdf#toolbar=0&navpanes=0&scrollbar=0"
                                    width="100%" height="100%" style="border: none;"></iframe>
                            </div>
                            <div class="modal-footer bg-light d-flex justify-content-between align-items-center">
                                <span class="text-muted small fw-bold" id="leyendoMensajeReglamento">
                                    <i class="fas fa-clock me-1"></i> Por favor, lee el documento...
                                </span>
                                <div>
                                    <button type="button" class="btn btn-secondary"
                                        data-bs-dismiss="modal">Cerrar</button>
                                    <button type="button" class="btn btn-success" id="btnAceptarReglamentoModal"
                                        disabled>
                                        Aceptar Reglamento
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        $(function () {
            let currentStep = 0;
            const steps = $('.step');
            const totalSteps = steps.length;
            const stepperDots = $('.stepper-step');

            const documentos = {
                moral: [
                    'Acta Constitutiva (PDF)',
                    'Constancia de Situación Fiscal (PDF)',
                    'Comprobante de domicilio (vigencia máx. 2 meses)',
                    'INE o identificación oficial vigente del representante legal'
                ],
                fisica: [
                    'Constancia de Situación Fiscal (PDF)',
                    'Comprobante de domicilio (vigencia máx. 2 meses)',
                    'INE o identificación oficial vigente del representante legal'
                ]
            };

            function showStep(index) {
                steps.removeClass('active').eq(index).addClass('active');
                stepperDots.each(function (idx) {
                    $(this).toggleClass('active', idx === index);
                    $(this).toggleClass('done', idx < index);
                });
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }

            function updateDocumentos(tipo) {
                const list = documentos[tipo] || [];
                const container = $('#documentosRequeridos');
                container.empty();
                if (!list.length) return;

                container.append('<h6 class="fw-bold text-secondary mb-2"><i class="fas fa-paperclip me-1"></i>Documentos requeridos</h6>');
                list.forEach(function (doc) {
                    var key = doc.replace(/[^a-z0-9]/gi, '_').toLowerCase();
                    container.append(
                        '<div class="doc-item">' +
                        '<label class="required" for="' + key + '"><i class="fas fa-file-alt me-1 text-success"></i>' + doc + '</label>' +
                        '<input type="file" class="form-control" name="docs[' + key + ']" id="' + key + '" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp" required>' +
                        '<div class="invalid-feedback">Adjunta: ' + doc + '</div>' +
                        '</div>'
                    );
                });

                // Habilitamos el botón de siguiente cuando se elige el tipo de persona y se generan los inputs
                $('#btnNext0').prop('disabled', false);
            }

            $('input[name="tipoPersona"]').change(function () {
                updateDocumentos(this.value);
            });

            function validateStep(step) {
                var valid = true;
                $('#step-' + step).find('input, select, textarea').each(function () {
                    if (!this.checkValidity()) { $(this).addClass('is-invalid'); valid = false; }
                    else $(this).removeClass('is-invalid');
                });
                return valid;
            }

            $('.next-step').click(function () {
                if (!validateStep(currentStep)) return;
                if (currentStep < totalSteps - 1) { currentStep++; showStep(currentStep); }
            });

            $('.prev-step').click(function () {
                if (currentStep > 0) { currentStep--; showStep(currentStep); }
            });

            // ── LÓGICA DE DOCUMENTOS DE ACEPTACIÓN (aviso de privacidad y reglamento) ──
            function configurarDocumentoAceptacion(cfg) {
                let aceptado = false;
                let temporizadorLectura;

                $(cfg.modal).on('shown.bs.modal', function () {
                    if (aceptado) return;
                    let tiempoRestante = 5; // Segundos de lectura obligatoria
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

            $('#evaluationForm').on('submit', function (e) {
                e.preventDefault();
                if (!validateStep(currentStep)) return;

                var form = this;
                var formData = new FormData(form);

                $.ajax({
                    url: 'controller/ajax/ajax.registroOrganismos.php',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    dataType: 'json',
                    beforeSend: function () {
                        Swal.fire({
                            title: 'Enviando registro\u2026',
                            text: 'Por favor espera un momento.',
                            allowOutsideClick: false,
                            didOpen: function () { Swal.showLoading(); }
                        });
                    },
                    success: function (response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Registro enviado!',
                                html: 'Revisaremos tu información y, una vez aprobada, te enviaremos por correo el convenio generado para tu firma.<br><br><small class="text-muted">El proceso de aprobación puede tomar de 1 a 3 días hábiles.</small>',
                                confirmButtonColor: '#01643D'
                            }).then(function () { location.reload(); });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error al enviar',
                                text: response.message || 'Ocurrió un problema. Intenta de nuevo.',
                                confirmButtonColor: '#01643D'
                            });
                        }
                    },
                    error: function () {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de conexión',
                            text: 'No se pudo enviar el formulario. Verifica tu conexión e intenta de nuevo.',
                            confirmButtonColor: '#01643D'
                        });
                    }
                });
            });

            showStep(0);
        });
    </script>
</body>

</html>