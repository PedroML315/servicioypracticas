<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro de Servicio Social – Universidad Montrer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --primary:#01643D; --primary-dark:#014d2f; --primary-light:#e6f4ee; }
        body { background:linear-gradient(135deg,#e8f5ee 0%,#f8fafb 100%); font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; min-height:100vh; }

        .page-header { background:var(--primary); color:#fff; padding:1.25rem 0 0; text-align:center; border-radius:0 0 2rem 2rem; margin-bottom:2rem; box-shadow:0 4px 18px rgba(1,100,61,.25); }
        .page-header img { max-height:56px; margin-bottom:.6rem; filter:brightness(0) invert(1); }
        .page-header h1 { font-size:1.25rem; font-weight:700; margin:0; padding-bottom:1rem; }

        .stepper-wrap { display:flex; justify-content:center; gap:0; margin-bottom:2rem; padding:0 1rem; }
        .stepper-step { display:flex; flex-direction:column; align-items:center; flex:1; max-width:160px; position:relative; }
        .stepper-step:not(:last-child)::after { content:''; position:absolute; top:20px; left:60%; width:calc(100% - 20px); height:3px; background:#dee2e6; z-index:0; transition:background .4s; }
        .stepper-step.done:not(:last-child)::after,.stepper-step.active:not(:last-child)::after { background:var(--primary); }
        .stepper-circle { width:42px; height:42px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:.95rem; background:#dee2e6; color:#6c757d; border:3px solid #dee2e6; z-index:1; transition:all .3s; position:relative; }
        .stepper-step.active .stepper-circle { background:var(--primary); color:#fff; border-color:var(--primary); transform:scale(1.1); box-shadow:0 0 0 5px rgba(1,100,61,.15); }
        .stepper-step.done .stepper-circle { background:var(--primary); color:#fff; border-color:var(--primary); }
        .stepper-label { font-size:.72rem; text-align:center; margin-top:.35rem; font-weight:600; color:#6c757d; }
        .stepper-step.active .stepper-label,.stepper-step.done .stepper-label { color:var(--primary); }

        .form-card { background:#fff; border-radius:1.25rem; box-shadow:0 6px 32px rgba(1,100,61,.10); overflow:hidden; max-width:820px; margin:0 auto; }
        .step-header { background:var(--primary-light); border-bottom:2px solid rgba(1,100,61,.12); padding:1.25rem 1.75rem .9rem; }
        .step-header h2 { font-size:1.1rem; font-weight:700; color:var(--primary); margin:0; }
        .step-header p { font-size:.84rem; color:#5a7060; margin:.25rem 0 0; }
        .form-body { padding:1.75rem; }

        .input-icon-wrap { position:relative; }
        .input-icon-wrap .fas,.input-icon-wrap .far,.input-icon-wrap .fa-solid,.input-icon-wrap .fa { position:absolute; left:.9rem; top:50%; transform:translateY(-50%); color:#9db8a8; pointer-events:none; font-size:.85rem; }
        .input-icon-wrap input,.input-icon-wrap select { padding-left:2.2rem !important; }
        .form-label { font-weight:600; font-size:.87rem; color:#344c3d; margin-bottom:.3rem; }

        #matricula-banner { display:none; background:var(--primary-light); border:1px solid rgba(1,100,61,.25); border-radius:.75rem; padding:.75rem 1rem; margin-bottom:1rem; font-size:.88rem; }
        #matricula-banner strong { color:var(--primary); }

        .option-card { border:2px solid #dee2e6; border-radius:.9rem; padding:1rem 1.2rem; cursor:pointer; transition:all .2s; display:flex; gap:.85rem; align-items:flex-start; }
        .option-card:hover { border-color:var(--primary); background:var(--primary-light); }
        .option-card input[type=radio] { margin-top:.15rem; accent-color:var(--primary); width:1.1em; height:1.1em; flex-shrink:0; }
        .option-card .oc-icon { font-size:1.6rem; color:var(--primary); flex-shrink:0; }
        .option-card .oc-title { font-weight:700; font-size:.95rem; }
        .option-card .oc-desc { font-size:.82rem; color:#6c757d; margin-top:.1rem; }

        .btn-primary { background:var(--primary); border-color:var(--primary); border-radius:.65rem; font-weight:600; }
        .btn-primary:hover { background:var(--primary-dark); border-color:var(--primary-dark); }
        .btn-secondary { border-radius:.65rem; font-weight:600; }
        .btn-action-row { display:flex; justify-content:space-between; align-items:center; margin-top:1.5rem; padding-top:1rem; border-top:1px solid #f0f0f0; }

        @media(max-width:576px){ .stepper-label{display:none;} .form-body{padding:1rem;} }
    </style>
</head>
<body>

<div class="page-header">
    <img src="view/assets/images/logo-color.png" alt="Universidad Montrer">
    <h1><i class="fa-solid fa-graduation-cap me-2"></i>Registro de Servicio Social Universitario</h1>
</div>

<div class="container pb-5">
    <div class="stepper-wrap" id="stepper">
        <div class="stepper-step active" data-step="0">
            <div class="stepper-circle"><i class="fa-solid fa-user"></i></div>
            <div class="stepper-label">Personal</div>
        </div>
        <div class="stepper-step" data-step="1">
            <div class="stepper-circle"><i class="fa-solid fa-book-open"></i></div>
            <div class="stepper-label">Académico</div>
        </div>
        <div class="stepper-step" data-step="2">
            <div class="stepper-circle"><i class="fa-solid fa-location-dot"></i></div>
            <div class="stepper-label">Dirección</div>
        </div>
        <div class="stepper-step" data-step="3">
            <div class="stepper-circle"><i class="fa-solid fa-flag-checkered"></i></div>
            <div class="stepper-label">Modalidad</div>
        </div>
    </div>

    <div class="form-card">
        <form id="registerStudentForm" class="needs-validation" novalidate>

            <!-- PASO 1 -->
            <div class="step" data-step="0">
                <div class="step-header">
                    <h2><i class="fa-regular fa-id-card me-2"></i>Información Personal</h2>
                    <p>Ingresa tu matrícula — el sistema completará tus datos automáticamente.</p>
                </div>
                <div class="form-body">
                    <div id="matricula-banner">
                        <i class="fa-solid fa-circle-check text-success me-1"></i>
                        ¡Datos encontrados! Revisa que la información sea correcta.
                        <strong id="banner-name"></strong>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-5">
                            <label for="matricula" class="form-label">Matrícula <span class="text-danger">*</span></label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-id-badge"></i>
                                <input type="text" class="form-control" id="matricula" name="matricula" required pattern="\d+" placeholder="Ej. 20230001" autocomplete="off">
                            </div>
                            <div class="invalid-feedback" id="matricula_feedback">La matrícula debe contener solo números.</div>
                            <div class="form-text">
                                <i class="fas fa-spinner fa-spin me-1 text-secondary" id="loadingSpinner" style="display:none"></i>
                                Al escribir tu matrícula, tus datos se completarán automáticamente.
                            </div>
                        </div>
                    </div>
                    <hr class="my-3">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="nombre" class="form-label">Nombre(s) <span class="text-danger">*</span></label>
                            <div class="input-icon-wrap"><i class="fas fa-user"></i>
                                <input type="text" class="form-control" id="nombre" name="nombre" required placeholder="Ej. Juan">
                            </div>
                            <div class="invalid-feedback">Este campo es obligatorio.</div>
                        </div>
                        <div class="col-md-4">
                            <label for="apellidoPaterno" class="form-label">Apellido paterno <span class="text-danger">*</span></label>
                            <div class="input-icon-wrap"><i class="fas fa-user"></i>
                                <input type="text" class="form-control" id="apellidoPaterno" name="apellidoPaterno" required>
                            </div>
                            <div class="invalid-feedback">Este campo es obligatorio.</div>
                        </div>
                        <div class="col-md-4">
                            <label for="apellidoMaterno" class="form-label">Apellido materno</label>
                            <div class="input-icon-wrap"><i class="fas fa-user"></i>
                                <input type="text" class="form-control" id="apellidoMaterno" name="apellidoMaterno">
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-12"><label class="form-label">Fecha de nacimiento <span class="text-danger">*</span></label></div>
                        <div class="col-md-3">
                            <div class="input-icon-wrap"><i class="fas fa-calendar-day"></i>
                                <input type="number" class="form-control" id="diaNacimiento" name="diaNacimiento" min="1" max="31" required placeholder="Día">
                            </div>
                            <div class="invalid-feedback">Día inválido.</div>
                        </div>
                        <div class="col-md-3">
                            <div class="input-icon-wrap"><i class="fas fa-calendar-day"></i>
                                <input type="number" class="form-control" id="mesNacimiento" name="mesNacimiento" min="1" max="12" required placeholder="Mes">
                            </div>
                            <div class="invalid-feedback">Mes inválido.</div>
                        </div>
                        <div class="col-md-3">
                            <div class="input-icon-wrap"><i class="fas fa-calendar-alt"></i>
                                <select class="form-select" id="anioNacimiento" name="anioNacimiento" required>
                                    <option value="" selected disabled>Año</option>
                                </select>
                            </div>
                            <div class="invalid-feedback">Selecciona un año.</div>
                        </div>
                        <div class="col-md-3">
                            <div class="input-icon-wrap"><i class="fas fa-venus-mars"></i>
                                <select class="form-select" id="genero" name="genero" required>
                                    <option value="" disabled selected>Género</option>
                                    <option value="1">Masculino</option>
                                    <option value="2">Femenino</option>
                                    <option value="0">Otro</option>
                                </select>
                            </div>
                            <div class="invalid-feedback">Selecciona un género.</div>
                        </div>
                    </div>
                    <div class="btn-action-row">
                        <span class="text-muted small"><span class="text-danger">*</span> Campos obligatorios</span>
                        <button type="button" class="btn btn-primary next-btn">Siguiente <i class="fas fa-arrow-right ms-1"></i></button>
                    </div>
                </div>
            </div>

            <!-- PASO 2 -->
            <div class="step d-none" data-step="1">
                <div class="step-header">
                    <h2><i class="fa-solid fa-book-open me-2"></i>Datos Académicos y Contacto</h2>
                    <p>Información de tu carrera y datos de contacto de emergencia.</p>
                </div>
                <div class="form-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="licenciatura" class="form-label">Licenciatura <span class="text-danger">*</span></label>
                            <div class="input-icon-wrap"><i class="fas fa-university"></i>
                                <select class="form-select" id="licenciatura" name="licenciatura" required>
                                    <option value="">Selecciona tu licenciatura</option>
                                </select>
                            </div>
                            <div class="invalid-feedback">Selecciona una licenciatura.</div>
                        </div>
                        <div class="col-md-3">
                            <label for="tipoLicenciatura" class="form-label">Modalidad <span class="text-danger">*</span></label>
                            <div class="input-icon-wrap"><i class="fas fa-layer-group"></i>
                                <select class="form-select" id="tipoLicenciatura" name="tipoLicenciatura" required>
                                    <option value="" disabled selected>Tipo</option>
                                    <option value="semestral">Semestral</option>
                                    <option value="cuatrimestral">Cuatrimestral</option>
                                </select>
                            </div>
                            <div class="invalid-feedback">Selecciona un tipo.</div>
                        </div>
                        <div class="col-md-3">
                            <label for="grado" class="form-label">Grado actual <span class="text-danger">*</span></label>
                            <div class="input-icon-wrap"><i class="fas fa-sort-numeric-up"></i>
                                <input type="number" class="form-control" id="grado" name="grado" required placeholder="Ej. 8">
                            </div>
                            <div class="invalid-feedback">Ingresa tu grado.</div>
                        </div>
                    </div>
                    <hr class="my-3">
                    <p class="text-muted small mb-3"><i class="fas fa-info-circle me-1 text-success"></i>Proporciona tu correo y el teléfono de alguien de confianza para emergencias.</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="correoInstitucional" class="form-label">Correo institucional <span class="text-danger">*</span></label>
                            <div class="input-icon-wrap"><i class="fas fa-envelope"></i>
                                <input type="email" class="form-control" id="correoInstitucional" name="correoInstitucional" required placeholder="matricula@unimontrer.edu.mx">
                            </div>
                            <div class="invalid-feedback">El correo debe ser @unimontrer.edu.mx.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="telefonoContacto" class="form-label">Tu celular <span class="text-danger">*</span></label>
                            <div class="input-icon-wrap"><i class="fas fa-mobile-alt"></i>
                                <input type="tel" class="form-control" id="telefonoContacto" name="telefonoContacto" required pattern="\d{10}" placeholder="10 dígitos, Ej. 4431234567">
                            </div>
                            <div class="invalid-feedback">Debe contener 10 dígitos.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="telefonoEmergencia" class="form-label">Teléfono de emergencia <span class="text-danger">*</span></label>
                            <div class="input-icon-wrap"><i class="fas fa-phone-alt"></i>
                                <input type="tel" class="form-control" id="telefonoEmergencia" name="telefonoEmergencia" required pattern="\d{10}" placeholder="10 dígitos">
                            </div>
                            <div class="invalid-feedback">Debe contener 10 dígitos.</div>
                        </div>
                        <div class="col-md-3">
                            <label for="parentesco" class="form-label">Parentesco <span class="text-danger">*</span></label>
                            <div class="input-icon-wrap"><i class="fas fa-people-arrows"></i>
                                <select class="form-select" id="parentesco" name="parentesco" required>
                                    <option value="" disabled selected>Selecciona</option>
                                    <option value="Padre">Padre</option>
                                    <option value="Madre">Madre</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                            <div class="invalid-feedback">Selecciona un parentesco.</div>
                        </div>
                        <div class="col-md-3 d-none" id="parentescoEspecificar">
                            <label for="otroParentesco" class="form-label">Especificar <span class="text-danger">*</span></label>
                            <div class="input-icon-wrap"><i class="fas fa-pencil-alt"></i>
                                <input type="text" class="form-control" id="otroParentesco" name="otroParentesco" placeholder="Ej. Tutor">
                            </div>
                            <div class="invalid-feedback">Especifica el parentesco.</div>
                        </div>
                    </div>
                    <div class="btn-action-row">
                        <button type="button" class="btn btn-secondary prev-btn"><i class="fas fa-arrow-left me-1"></i> Anterior</button>
                        <button type="button" class="btn btn-primary next-btn">Siguiente <i class="fas fa-arrow-right ms-1"></i></button>
                    </div>
                </div>
            </div>

            <!-- PASO 3 -->
            <div class="step d-none" data-step="2">
                <div class="step-header">
                    <h2><i class="fa-solid fa-location-dot me-2"></i>Domicilio</h2>
                    <p>Ingresa tu dirección de residencia actual.</p>
                </div>
                <div class="form-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="calle" class="form-label">Calle <span class="text-danger">*</span></label>
                            <div class="input-icon-wrap"><i class="fas fa-road"></i>
                                <input type="text" class="form-control" id="calle" name="calle" required placeholder="Nombre de la calle">
                            </div>
                            <div class="invalid-feedback">Ingresa tu calle.</div>
                        </div>
                        <div class="col-md-3">
                            <label for="numeroExterior" class="form-label">Núm. exterior <span class="text-danger">*</span></label>
                            <div class="input-icon-wrap"><i class="fas fa-hashtag"></i>
                                <input type="text" class="form-control" id="numeroExterior" name="numeroExterior" required placeholder="Ej. 45">
                            </div>
                            <div class="invalid-feedback">Ingresa el número exterior.</div>
                        </div>
                        <div class="col-md-3">
                            <label for="numeroInterior" class="form-label">Núm. interior <span class="text-muted">(opcional)</span></label>
                            <div class="input-icon-wrap"><i class="fas fa-door-open"></i>
                                <input type="text" class="form-control" id="numeroInterior" name="numeroInterior" placeholder="Ej. 2B">
                            </div>
                        </div>
                        <div class="col-md-5">
                            <label for="colonia" class="form-label">Colonia <span class="text-danger">*</span></label>
                            <div class="input-icon-wrap"><i class="fas fa-map-signs"></i>
                                <input type="text" class="form-control" id="colonia" name="colonia" required placeholder="Nombre de tu colonia">
                            </div>
                            <div class="invalid-feedback">Ingresa tu colonia.</div>
                        </div>
                        <div class="col-md-4">
                            <label for="municipio" class="form-label">Municipio / Ciudad</label>
                            <div class="input-icon-wrap"><i class="fas fa-city"></i>
                                <input type="text" class="form-control" id="municipio" name="municipio" placeholder="Ej. Morelia">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="codigoPostal" class="form-label">Código postal <span class="text-danger">*</span></label>
                            <div class="input-icon-wrap"><i class="fas fa-mail-bulk"></i>
                                <input type="text" class="form-control" id="codigoPostal" name="codigoPostal" required placeholder="Ej. 58000">
                            </div>
                            <div class="invalid-feedback">Ingresa el código postal.</div>
                        </div>
                    </div>
                    <div class="btn-action-row">
                        <button type="button" class="btn btn-secondary prev-btn"><i class="fas fa-arrow-left me-1"></i> Anterior</button>
                        <button type="button" class="btn btn-primary next-btn">Siguiente <i class="fas fa-arrow-right ms-1"></i></button>
                    </div>
                </div>
            </div>

            <!-- PASO 4 -->
            <div class="step d-none" data-step="3">
                <div class="step-header">
                    <h2><i class="fa-solid fa-flag-checkered me-2"></i>Modalidad de Servicio Social</h2>
                    <p>Elige cómo deseas realizar tu servicio social.</p>
                </div>
                <div class="form-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="option-card d-flex" for="opcionUniv">
                                <input class="form-check-input" type="radio" name="tipoPractica" id="opcionUniv" value="universidad" required>
                                <div class="ms-3">
                                    <i class="fa-solid fa-school oc-icon mb-1 d-block"></i>
                                    <div class="oc-title">A través de la Universidad</div>
                                    <div class="oc-desc">Proyectos institucionales, eventos y actividades organizadas por Universidad Montrer.</div>
                                </div>
                            </label>
                        </div>
                        <div class="col-md-6">
                            <label class="option-card d-flex" for="opcionEmp">
                                <input class="form-check-input" type="radio" name="tipoPractica" id="opcionEmp" value="empresa">
                                <div class="ms-3">
                                    <i class="fa-solid fa-building oc-icon mb-1 d-block"></i>
                                    <div class="oc-title">En empresa u organismo externo</div>
                                    <div class="oc-desc">En una empresa, institución pública o privada u organización.</div>
                                </div>
                            </label>
                        </div>
                    </div>
                    <div class="btn-action-row">
                        <button type="button" class="btn btn-secondary prev-btn"><i class="fas fa-arrow-left me-1"></i> Anterior</button>
                        <button type="submit" class="btn btn-primary px-4"><i class="fas fa-paper-plane me-2"></i>Enviar registro</button>
                    </div>
                </div>
            </div>

        </form>
    </div>
</div>

<!-- Toasts legado -->
<div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="successToast"   class="toast align-items-center text-bg-success border-0" role="alert"><div class="d-flex"><div class="toast-body">¡Éxito! Datos registrados.</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>
    <div id="duplicateToast" class="toast align-items-center text-bg-warning border-0" role="alert"><div class="d-flex"><div class="toast-body">¡Atención! Alumno ya registrado.</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>
    <div id="errorToast"     class="toast align-items-center text-bg-danger  border-0" role="alert"><div class="d-flex"><div class="toast-body">Ocurrió un error. Intenta de nuevo.</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script type="module">
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
    disableForm(true);

    const yearSel = document.getElementById('anioNacimiento');
    for (let y = new Date().getFullYear() - 15; y >= 1970; y--) yearSel.add(new Option(y, y));

    async function loadDegrees() {
        try {
            const res  = await fetch('controller/ajax/ajax.forms.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: new URLSearchParams({ search:'degrees' }) });
            const data = await res.json();
            const sel  = document.getElementById('licenciatura');
            sel.innerHTML = '<option value="">Selecciona tu licenciatura</option>' + data.map(d => `<option value="${d.idDegree}">${d.nameDegree}</option>`).join('');
        } catch {}
    }
    loadDegrees();

    const steps       = [...document.querySelectorAll('.step')];
    const stepperDots = [...document.querySelectorAll('.stepper-step')];
    let current = 0;

    function show(i) {
        steps.forEach((s, idx) => s.classList.toggle('d-none', idx !== i));
        stepperDots.forEach((dot, idx) => { dot.classList.toggle('active', idx === i); dot.classList.toggle('done', idx < i); });
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    document.querySelectorAll('.next-btn').forEach(b => b.addEventListener('click', () => { if (validateStep(current)) show(++current); }));
    document.querySelectorAll('.prev-btn').forEach(b => b.addEventListener('click', () => show(--current)));
    show(0);

    function validateStep(i) {
        const elems = [...steps[i].querySelectorAll('input,select')];
        let ok = true;
        elems.forEach(f => { if (!f.checkValidity()) { f.classList.add('is-invalid'); ok = false; } else f.classList.remove('is-invalid'); });
        return ok;
    }

    function toast(id) { new bootstrap.Toast(document.getElementById(id), { delay: 3000 }).show(); }
    const debounce = (fn, ms = 500) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; };

    const matInput = document.getElementById('matricula');
    const spinner  = document.getElementById('loadingSpinner');
    const banner   = document.getElementById('matricula-banner');

    matInput.addEventListener('input', debounce(async () => {
        const val = matInput.value.trim();
        banner.style.display = 'none';
        if (!/^\d+$/.test(val)) {
            document.getElementById('matricula_feedback').textContent = 'La matrícula debe contener solo números.';
            disableForm(true); return matInput.classList.add('is-invalid');
        }
        spinner.style.display = 'inline-block';
        try {
            const res  = await fetch('controller/ajax/ajax.forms.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: new URLSearchParams({ action:'checkMatricula', matricula:val }) });
            const json = await res.json();
            const student  = json.student  || {};
            const academic = json.academic || {};
            const extra    = json.extra    || {};

            if (student.MATRICULA) {
                disableForm(false);
                let telefonoFamiliar = (academic.Familiar_Telefono && academic.Familiar_Telefono !== 'NULL') ? academic.Familiar_Telefono : extra.Numero || '';
                document.getElementById('nombre').value           = student.NOMBRE;
                document.getElementById('apellidoPaterno').value  = student.PATERNO;
                document.getElementById('apellidoMaterno').value  = student.MATERNO;
                document.getElementById('genero').value           = student.genero === 'F' ? '2' : student.genero === 'M' ? '1' : '0';
                const [anio, mes, dia] = student.fecha_nacimiento.split('-');
                document.getElementById('anioNacimiento').value   = anio;
                document.getElementById('mesNacimiento').value    = mes;
                document.getElementById('diaNacimiento').value    = dia;
                document.getElementById('telefonoContacto').value    = (student.telefono || '').replace(/\D/g,'').slice(-10);
                document.getElementById('telefonoEmergencia').value  = (telefonoFamiliar).replace(/\D/g,'').slice(-10);
                document.getElementById('correoInstitucional').value = student.correo_institucional || academic.matricula + '@unimontrer.edu.mx';
                document.getElementById('calle').value           = academic.direcionCasa || '';
                document.getElementById('numeroExterior').value  = academic.numCasa || '';
                document.getElementById('codigoPostal').value    = academic.cpCasa || '';
                document.getElementById('colonia').value         = (academic.Familiar_Direccion != 'NULL') ? academic.Familiar_Direccion : '';
                const licSel    = document.getElementById('licenciatura');
                const normalize = str => str?.toUpperCase().normalize("NFD").replace(/[\u0300-\u036f]/g,"").replace(/Ñ/g,"N").replace(/ñ/g,"N").trim();
                const target    = normalize(academic.nameOferta);
                const found     = Array.from(licSel.options).find(opt => normalize(opt.textContent) === target);
                if (found) licSel.value = found.value;
                const periodo = academic.nombre_periodo?.toLowerCase() || '';
                document.getElementById('tipoLicenciatura').value = periodo.includes('cuatri') ? 'cuatrimestral' : periodo.includes('semes') ? 'semestral' : '';
                const parentesco   = academic.TipoFamiliar;
                const parentSelect = document.getElementById('parentesco');
                parentSelect.value = ['Padre','Madre'].includes(parentesco) ? parentesco : 'Otro';
                const especDiv   = document.getElementById('parentescoEspecificar');
                const especInput = document.getElementById('otroParentesco');
                if (parentSelect.value === 'Otro') {
                    especDiv.classList.remove('d-none'); especInput.required = true;
                    especInput.value = [academic.Familiar_Nombre, academic.Familiar_Apellido1, academic.Familiar_Apellido2].filter(Boolean).join(' ').trim();
                } else { especDiv.classList.add('d-none'); especInput.required = false; especInput.value = ''; }
                const codigoGrupo = student.CODIGO_GRUPO || '';
                const matchGrado  = codigoGrupo.match(/\d+/);
                document.getElementById('grado').value = matchGrado ? matchGrado[0] : '';
                matInput.classList.remove('is-invalid'); matInput.classList.add('is-valid');
                banner.style.display = 'block';
                document.getElementById('banner-name').textContent = ` — ${student.NOMBRE} ${student.PATERNO} ${student.MATERNO}`;

            } else if (academic.matricula && academic.nombre_periodo != 'Trimestre') {
                disableForm(false);
                document.getElementById('nombre').value           = academic.nombre || '';
                document.getElementById('apellidoPaterno').value  = academic.apellido1 || '';
                document.getElementById('apellidoMaterno').value  = academic.apellido2 || '';
                document.getElementById('genero').value           = academic.genero === 'F' ? '2' : academic.genero === 'M' ? '1' : '0';
                if (academic.fechaNacimiento) {
                    const [fecha] = academic.fechaNacimiento.split(' ');
                    const [anio, mes, dia] = fecha.split('-');
                    document.getElementById('anioNacimiento').value = anio;
                    document.getElementById('mesNacimiento').value  = mes;
                    document.getElementById('diaNacimiento').value  = dia;
                }
                document.getElementById('telefonoContacto').value    = (academic.telefono || '').replace(/\D/g,'').slice(-10);
                document.getElementById('telefonoEmergencia').value  = (academic.Familiar_Telefono || '').replace(/\D/g,'').slice(-10);
                document.getElementById('correoInstitucional').value = academic.matricula + '@unimontrer.edu.mx';
                document.getElementById('calle').value           = academic.direcionCasa || '';
                document.getElementById('numeroExterior').value  = academic.numCasa || '';
                document.getElementById('codigoPostal').value    = academic.cpCasa || '';
                document.getElementById('colonia').value         = academic.Familiar_Direccion || '';
                const licSel    = document.getElementById('licenciatura');
                const normalize = str => str?.toUpperCase().normalize("NFD").replace(/[\u0300-\u036f]/g,"").replace(/Ñ/g,"N").replace(/ñ/g,"N").trim();
                const found     = Array.from(licSel.options).find(opt => normalize(opt.textContent) === normalize(academic.nameOferta));
                if (found) licSel.value = found.value;
                const periodo = academic.nombre_periodo?.toLowerCase() || '';
                document.getElementById('tipoLicenciatura').value = periodo.includes('cuatri') ? 'cuatrimestral' : periodo.includes('semes') ? 'semestral' : '';
                const parentesco   = academic.TipoFamiliar;
                const parentSelect = document.getElementById('parentesco');
                parentSelect.value = ['Padre','Madre'].includes(parentesco) ? parentesco : 'Otro';
                const especDiv   = document.getElementById('parentescoEspecificar');
                const especInput = document.getElementById('otroParentesco');
                if (parentSelect.value === 'Otro') {
                    especDiv.classList.remove('d-none'); especInput.required = true;
                    especInput.value = [academic.Familiar_Nombre, academic.Familiar_Apellido1, academic.Familiar_Apellido2].filter(Boolean).join(' ').trim();
                } else { especDiv.classList.add('d-none'); especInput.required = false; especInput.value = ''; }
                document.getElementById('grado').value = academic.avance || '';
                matInput.classList.remove('is-invalid'); matInput.classList.add('is-valid');
                banner.style.display = 'block';
                document.getElementById('banner-name').textContent = ` — ${academic.nombre} ${academic.apellido1} ${academic.apellido2}`;

            } else if (academic.nombre_periodo === 'Trimestre') {
                document.getElementById('matricula_feedback').textContent = 'El alumno debe estar inscrito en una licenciatura.';
                disableForm(true); matInput.classList.add('is-invalid');
            } else {
                document.getElementById('matricula_feedback').textContent = 'Matrícula no encontrada. Verifica e intenta de nuevo.';
                disableForm(true); matInput.classList.add('is-invalid');
            }
        } catch {
            disableForm(true);
            document.getElementById('matricula_feedback').textContent = 'Error al buscar matrícula. Intenta de nuevo.';
            matInput.classList.add('is-invalid');
        }
        spinner.style.display = 'none';
    }, 1000));

    document.getElementById('parentesco').addEventListener('change', e => {
        const div = document.getElementById('parentescoEspecificar');
        const inp = document.getElementById('otroParentesco');
        if (e.target.value === 'Otro') { div.classList.remove('d-none'); inp.required = true; }
        else { div.classList.add('d-none'); inp.required = false; }
    });

    document.getElementById('registerStudentForm').addEventListener('submit', async e => {
        e.preventDefault();
        const form = e.target;
        if (!form.checkValidity()) return;
        const data = new FormData(form);
        data.append('search', 'student');
        data.append('action', 'addStudent');
        Swal.fire({ title: 'Registrando...', text: 'Por favor espera un momento.', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        try {
            const res = await fetch('controller/ajax/ajax.forms.php', { method: 'POST', body: data });
            const txt = await res.text();
            if (txt.includes('success')) {
                Swal.fire({ icon:'success', title:'¡Registro exitoso!', text:'Tu registro de Servicio Social fue completado.', confirmButtonColor:'#01643D' }).then(() => { window.location.href = './'; });
                form.reset(); show(0);
            } else if (txt.includes('duplicate')) {
                Swal.fire({ icon:'warning', title:'Ya estás registrado', text:'Esta matrícula ya se encuentra en el sistema.', confirmButtonColor:'#01643D' });
            } else {
                Swal.fire({ icon:'error', title:'Error', text:'Ocurrió un problema al registrar. Intenta de nuevo.', confirmButtonColor:'#01643D' });
            }
        } catch {
            Swal.fire({ icon:'error', title:'Error de conexión', text:'No se pudo conectar con el servidor.', confirmButtonColor:'#01643D' });
        }
    });

    function disableForm(lock = true) {
        const form = document.getElementById('registerStudentForm');
        form.querySelectorAll('input, select, textarea').forEach(el => { if (el.id === 'matricula') return; el.disabled = lock; });
        form.querySelectorAll('.next-btn').forEach(btn => btn.classList.toggle('d-none', lock));
    }
</script>
</body>
</html>