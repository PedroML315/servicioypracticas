<style>
    /* ============================================================
   Modales Organismo — "2026 UI Trends" (Glassmorphism + Wizard)
   ============================================================ */

    .modal-content-neo {
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(40px);
        -webkit-backdrop-filter: blur(40px);
        border: 1px solid rgba(255, 255, 255, 0.4);
        border-radius: 2rem;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        overflow: hidden;
    }

    .modal-header-neo {
        padding: 2rem 2.5rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .modal-header-neo .modal-title {
        font-weight: 900;
        font-size: 1.5rem;
        letter-spacing: -0.02em;
        color: #1e293b !important;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin: 0;
    }

    .modal-header-neo .modal-title i {
        color: #01643d;
        background: rgba(1, 100, 61, 0.1);
        padding: 0.75rem;
        border-radius: 1rem;
    }

    .btn-close-neo {
        background: #f1f5f9;
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        color: #64748b;
    }

    .btn-close-neo:hover {
        background: #e2e8f0;
        color: #0f172a;
        transform: rotate(90deg);
    }

    .modal-body-neo {
        padding: 2.5rem;
    }

    .modal-footer-neo {
        padding: 1.5rem 2.5rem;
        background: rgba(255, 255, 255, 0.8);
        border-top: 1px solid rgba(0, 0, 0, 0.05);
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
    }

    /* Formularios "Float/Clean" */
    .form-label-neo {
        font-size: 0.85rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #475569;
        margin-bottom: 0.5rem;
        display: block;
    }

    .form-control-neo,
    .form-select-neo {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 1rem;
        padding: 1rem 1.25rem;
        font-size: 1rem;
        color: #0f172a;
        font-weight: 600;
        transition: all 0.3s;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.02);
        width: 100%;
    }

    .form-control-neo:focus,
    .form-select-neo:focus {
        background: white;
        border-color: var(--brand-main);
        box-shadow: 0 0 0 4px rgba(1, 100, 61, 0.1);
        outline: none;
    }

    .wizard-step {
        display: none;
        animation: slideIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    .wizard-step.active {
        display: block;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(20px);
        }

        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    /* HORARIO GRID */
    .horario-grid-neo {
        background: rgba(255, 255, 255, 0.4);
        border-radius: 1.5rem;
        padding: 1.5rem;
        border: 1px dashed rgba(0, 0, 0, 0.1);
    }

    .hf-auto-value-neo {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        background: rgba(1, 100, 61, 0.05);
        color: var(--brand-main);
        font-weight: 900;
        border-radius: 1rem;
        border: 1px solid rgba(1, 100, 61, 0.2);
    }

    /* BOTONES */
    .btn-neo-secondary {
        background: white;
        border: 1px solid #e2e8f0;
        color: var(--text-primary);
        font-weight: 800;
        border-radius: 100px;
        padding: 0.75rem 1.5rem;
        transition: all 0.2s;
    }

    .btn-neo-secondary:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    .btn-neo-primary {
        background: var(--brand-main);
        border: none;
        color: white;
        font-weight: 800;
        border-radius: 100px;
        padding: 0.75rem 2rem;
        box-shadow: inset 0 -3px 0 rgba(0, 0, 0, 0.1);
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
    }

    .btn-neo-primary:hover {
        transform: translateY(-2px);
        box-shadow: inset 0 -3px 0 rgba(0, 0, 0, 0.1), 0 10px 20px -5px rgba(1, 100, 61, 0.4);
    }

    /* ============================================================
   WIZARD REDISEÑO — "Nueva Vacante" (Rail vertical + Dashboard look)
   ============================================================ */
    .wizard-shell {
        display: flex;
        min-height: 580px;
    }

    /* --- Rail izquierdo (gradiente de marca, igual al hero del dashboard) --- */
    .wizard-rail {
        flex: 0 0 310px;
        background: linear-gradient(160deg, #01643D 0%, #00321f 55%, #00204a 100%);
        color: #fff;
        padding: 2.5rem 2rem;
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .wizard-rail::before {
        content: '';
        position: absolute;
        width: 280px;
        height: 280px;
        background: var(--brand-accent);
        filter: blur(90px);
        opacity: 0.22;
        border-radius: 50%;
        top: -90px;
        right: -100px;
        pointer-events: none;
    }

    .wizard-rail-head {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 2.75rem;
        position: relative;
        z-index: 2;
    }

    .wizard-rail-icon {
        width: 56px;
        height: 56px;
        border-radius: 1.1rem;
        background: rgba(255, 255, 255, 0.12);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.45rem;
        color: var(--brand-accent);
        flex-shrink: 0;
    }

    .wizard-rail-title {
        font-weight: 900;
        font-size: 1.4rem;
        margin: 0;
        letter-spacing: -0.02em;
    }

    .wizard-rail-sub {
        font-size: 0.82rem;
        opacity: 0.7;
        margin: 0.2rem 0 0;
        font-weight: 300;
    }

    .wizard-rail-steps {
        list-style: none;
        padding: 0;
        margin: 0;
        position: relative;
        z-index: 2;
        flex-grow: 1;
    }

    .rail-step {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.7rem 0;
        position: relative;
        opacity: 0.5;
        transition: opacity 0.3s;
        cursor: pointer;
    }

    .rail-step::after {
        content: '';
        position: absolute;
        left: 18px;
        top: 46px;
        bottom: -6px;
        width: 2px;
        background: rgba(255, 255, 255, 0.15);
    }

    .rail-step:last-child::after {
        display: none;
    }

    .rail-step.active,
    .rail-step.completed {
        opacity: 1;
    }

    .rail-step-dot {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 900;
        font-size: 0.95rem;
        background: rgba(255, 255, 255, 0.1);
        border: 2px solid rgba(255, 255, 255, 0.3);
        transition: all 0.3s;
        z-index: 2;
    }

    .rail-step.active .rail-step-dot {
        background: var(--brand-accent);
        color: var(--brand-dark);
        border-color: var(--brand-accent);
        box-shadow: 0 0 0 5px rgba(198, 219, 83, 0.18);
    }

    .rail-step.completed .rail-step-dot {
        background: #fff;
        color: var(--brand-main);
        border-color: #fff;
    }

    .rail-step.completed .rail-step-dot::before {
        content: '\f00c';
        font-family: 'Font Awesome 5 Free';
        font-weight: 900;
    }

    .rail-step.completed .rail-step-dot span {
        display: none;
    }

    .rail-step-text {
        display: flex;
        flex-direction: column;
        line-height: 1.2;
    }

    .rail-step-text strong {
        font-weight: 700;
        font-size: 0.95rem;
    }

    .rail-step-text small {
        font-size: 0.74rem;
        opacity: 0.7;
        font-weight: 300;
    }

    .wizard-rail-foot {
        position: relative;
        z-index: 2;
        font-size: 0.76rem;
        opacity: 0.55;
        font-weight: 300;
        border-top: 1px solid rgba(255, 255, 255, 0.12);
        padding-top: 1.2rem;
        margin-top: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }

    /* --- Panel derecho (formulario) --- */
    .wizard-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        position: relative;
        background: rgba(255, 255, 255, 0.55);
        min-width: 0;
    }

    .wizard-close {
        position: absolute;
        top: 1.5rem;
        right: 1.5rem;
        z-index: 5;
    }

    .wizard-main-body {
        padding: 3rem 3rem 1.5rem;
        flex-grow: 1;
        overflow-y: auto;
        max-height: 72vh;
    }

    .wizard-main-foot {
        padding: 1.25rem 3rem;
        background: rgba(255, 255, 255, 0.85);
        border-top: 1px solid rgba(0, 0, 0, 0.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }

    .wizard-step-counter {
        font-size: 0.85rem;
        font-weight: 800;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .wizard-step-eyebrow {
        font-size: 0.76rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: var(--brand-main);
    }

    .wizard-step-title {
        font-weight: 900;
        font-size: 1.85rem;
        color: var(--brand-dark);
        letter-spacing: -0.03em;
        margin: 0.25rem 0 1.75rem;
    }

    /* ============================================================
   SELECTOR DE HABILIDADES (perfil de la vacante)
   ============================================================ */
    .skills-picker {
        position: relative;
    }

    .skills-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 0.6rem;
    }

    .skills-chips:empty {
        display: none;
    }

    .skill-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        background: rgba(1, 100, 61, 0.08);
        color: #01643D;
        border: 1px solid rgba(1, 100, 61, 0.25);
        border-radius: 100px;
        padding: 0.35rem 0.9rem;
        font-weight: 700;
        font-size: 0.85rem;
    }

    .skill-chip.custom {
        background: #fffbeb;
        color: #92400e;
        border-color: #fde68a;
    }

    .skill-chip .chip-remove {
        cursor: pointer;
        opacity: 0.6;
    }

    .skill-chip .chip-remove:hover {
        opacity: 1;
    }

    .skills-suggestions {
        position: absolute;
        z-index: 30;
        left: 0;
        right: 0;
        margin-top: 0.35rem;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        box-shadow: 0 15px 30px -10px rgba(0, 0, 0, 0.15);
        max-height: 260px;
        overflow-y: auto;
        display: none;
    }

    .skills-suggestions .sugg-area {
        padding: 0.45rem 1rem 0.2rem;
        font-size: 0.68rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #94a3b8;
    }

    .skills-suggestions .sugg-item {
        padding: 0.55rem 1rem;
        cursor: pointer;
        font-weight: 600;
        color: #0f172a;
    }

    .skills-suggestions .sugg-item:hover {
        background: #f0fdf4;
        color: #01643D;
    }

    .skills-suggestions .sugg-item.custom-add {
        color: #92400e;
        background: #fffbeb;
        border-top: 1px dashed #fde68a;
    }

    .skills-hint {
        font-size: 0.78rem;
        color: #64748b;
        margin-top: 0.45rem;
        display: block;
        font-weight: 500;
    }

    @media (max-width: 768px) {
        .wizard-shell {
            flex-direction: column;
            min-height: 0;
        }

        .wizard-rail {
            flex-basis: auto;
            padding: 1.5rem;
        }

        .wizard-rail-head {
            margin-bottom: 1.5rem;
        }

        .wizard-rail-steps {
            display: flex;
            overflow-x: auto;
            gap: 1rem;
            padding-bottom: 0.5rem;
        }

        .rail-step {
            flex-direction: column;
            text-align: center;
            padding: 0;
            min-width: 70px;
            gap: 0.4rem;
        }

        .rail-step::after {
            display: none;
        }

        .rail-step-text small {
            display: none;
        }

        .wizard-rail-foot {
            display: none;
        }

        .wizard-main-body {
            padding: 1.75rem;
            max-height: none;
        }

        .wizard-main-foot {
            padding: 1rem 1.75rem;
        }

        .wizard-step-title {
            font-size: 1.4rem;
        }
    }
</style>

<!-- ==========================================
     MODAL: MIS SOLICITUDES (MASTER-DETAIL)
     ========================================== -->
<div class="modal fade" id="solicitudesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content modal-content-neo" style="border-radius: 0; border: none;">
            <div class="modal-header-neo bg-white" style="padding: 1.5rem 2.5rem;">
                <h5 class="modal-title"><i class="fas fa-briefcase"></i> Gestor de Vacantes y Postulantes</h5>
                <button type="button" class="btn-close-neo" data-bs-dismiss="modal"><i
                        class="fas fa-times"></i></button>
            </div>
            <div class="modal-body-neo p-0 d-flex flex-column" style="height: calc(100vh - 90px); overflow: hidden;">
                <div class="row g-0 flex-grow-1" style="height: 100%;">

                    <!-- Left Sidebar (Master) -->
                    <div class="col-md-4 col-lg-3 border-end"
                        style="height: 100%; overflow-y: auto; background: #f8fafc;">
                        <div class="p-4 border-bottom d-flex flex-column gap-3 bg-white sticky-top">
                            <button id="btnSolicitarPract" class="btn-neo-primary w-100"
                                onclick="$('#solicitudesModal').modal('hide'); $('#solicitarPractModal').modal('show');"
                                style="padding: 0.75rem;">
                                <i class="fas fa-plus me-2"></i> Nueva Vacante
                            </button>
                        </div>
                        <!-- Contenedor para inyectar la lista (Master) -->
                        <div class="solicitudes p-3"></div>
                    </div>

                    <!-- Right Panel (Detail) -->
                    <div class="col-md-8 col-lg-9" style="height: 100%; overflow-y: auto; background: white;">
                        <div id="solicitud-detalle-container" class="p-4 p-lg-5">
                            <div class="text-center text-muted mt-5 pt-5">
                                <i class="fas fa-hand-pointer fa-4x mb-3" style="opacity:0.2;"></i>
                                <h4 style="font-weight: 800; color: var(--brand-dark);">Selecciona una vacante</h4>
                                <p style="font-size: 1.1rem;">Haz clic en una vacante del panel izquierdo para ver sus
                                    detalles y administrar a los alumnos postulados.</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================
     MODAL: NUEVA SOLICITUD (WIZARD REDISEÑADO)
     ========================================== -->
<div class="modal fade" id="solicitarPractModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content modal-content-neo">
            <form id="solicitarForm" method="POST">
                <div class="wizard-shell">

                    <!-- RAIL LATERAL -->
                    <aside class="wizard-rail">
                        <div class="wizard-rail-head">
                            <div class="wizard-rail-icon"><i class="fas fa-briefcase"></i></div>
                            <div>
                                <h5 class="wizard-rail-title">Nueva Vacante</h5>
                                <p class="wizard-rail-sub">Publica una oportunidad de prácticas</p>
                            </div>
                        </div>
                        <ul class="wizard-rail-steps">
                            <li class="rail-step active" data-step="1">
                                <span class="rail-step-dot"><span>1</span></span>
                                <span class="rail-step-text"><strong>Perfil del Estudiante</strong><small>Habilidades y
                                        vacantes</small></span>
                            </li>
                            <li class="rail-step" data-step="2">
                                <span class="rail-step-dot"><span>2</span></span>
                                <span class="rail-step-text"><strong>Plan Formativo</strong><small>Actividades y
                                        funciones</small></span>
                            </li>
                            <li class="rail-step" data-step="3">
                                <span class="rail-step-dot"><span>3</span></span>
                                <span class="rail-step-text"><strong>Condiciones y Horario</strong><small>Modalidad y
                                        jornada</small></span>
                            </li>
                            <li class="rail-step" data-step="4">
                                <span class="rail-step-dot"><span>4</span></span>
                                <span class="rail-step-text"><strong>Sede y Responsable</strong><small>Ubicación y
                                        contacto</small></span>
                            </li>
                        </ul>
                        <div class="wizard-rail-foot">
                            <i class="fas fa-shield-alt"></i>
                            <span>La información se compartirá con los alumnos postulantes.</span>
                        </div>
                    </aside>

                    <!-- PANEL FORMULARIO -->
                    <div class="wizard-main">
                        <button type="button" class="btn-close-neo wizard-close" data-bs-dismiss="modal"><i
                                class="fas fa-times"></i></button>

                        <div class="wizard-main-body">

                            <!-- STEP 1: PERFIL -->
                            <div class="wizard-step active" id="step-1">
                                <div class="wizard-step-eyebrow">Paso 1</div>
                                <h3 class="wizard-step-title">Perfil del Estudiante</h3>
                                <div class="row g-4">
                                    <div class="col-md-8">
                                        <label class="form-label-neo">Habilidades del Perfil *</label>
                                        <div class="skills-picker">
                                            <div class="skills-chips" id="chipsHabilidades"></div>
                                            <input type="text" id="buscarHabilidad" class="form-control-neo"
                                                placeholder="Busca una habilidad: Excel, Diseño gráfico..."
                                                autocomplete="off">
                                            <div class="skills-suggestions" id="suggHabilidades"></div>
                                            <input type="hidden" name="habilidades" id="habilidades">
                                            <small class="skills-hint"><i class="fas fa-info-circle me-1"></i>Agrega
                                                todas las habilidades que necesite el perfil. Si alguna no aparece en el
                                                listado, escríbela y presiona Enter.</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label-neo">Vacantes *</label>
                                        <input type="number" class="form-control-neo" id="numPract" name="numPract"
                                            placeholder = "1"
                                            min="1" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label-neo">Aptitudes deseadas</label>
                                        <textarea id="capacidades" name="capacidades" class="form-control-neo" rows="3"
                                            placeholder="Conocimientos técnicos, software, idiomas o aptitudes recomendadas..."></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label-neo">Actitudes deseadas</label>
                                        <textarea id="actitudes" name="actitudes" class="form-control-neo" rows="3"
                                            placeholder="Responsabilidad, proactividad, trabajo en equipo, comunicación efectiva, compromiso, puntualidad..."></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- STEP 2: PLAN FORMATIVO -->
                            <div class="wizard-step" id="step-2">
                                <div class="wizard-step-eyebrow">Paso 2</div>
                                <h3 class="wizard-step-title">Plan Formativo</h3>
                                <div class="row g-4">
                                    <div class="col-12">
                                        <label class="form-label-neo">Actividades formativas que desarrollarán los
                                            practicantes *</label>
                                        <textarea id="actividades" name="actividades" class="form-control-neo" rows="3"
                                            required
                                            placeholder="Describe las actividades formativas que realizarán los practicantes..."></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label-neo">Funciones *</label>
                                        <textarea id="funciones" name="funciones" class="form-control-neo" rows="3"
                                            required
                                            placeholder="Funciones que desempeñará el practicante..."></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label-neo">Resultados esperados *</label>
                                        <textarea id="resultadosEsperados" name="resultadosEsperados"
                                            class="form-control-neo" rows="3" required
                                            placeholder="Resultados o entregables esperados..."></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- STEP 3: CONDICIONES -->
                            <div class="wizard-step" id="step-3">
                                <div class="wizard-step-eyebrow">Paso 3</div>
                                <h3 class="wizard-step-title">Condiciones y Horario</h3>
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label-neo">Modalidad *</label>
                                        <select id="modalidad" name="modalidad" class="form-select-neo" required>
                                            <option value="">Selecciona</option>
                                            <option value="Presencial">Presencial</option>
                                            <option value="Híbrido">Híbrido</option>
                                            <option value="Virtual">Virtual</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label-neo">Se reciben solicitudes hasta... *</label>
                                        <input type="date" class="form-control-neo" id="fechaLimite" name="fechaLimite"
                                            required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label-neo">Apoyo Económico *</label>
                                        <select id="apoyoEconomico" name="apoyoEconomico" class="form-select-neo"
                                            onchange="toggleMonto()" required>
                                            <option value="">Selecciona</option>
                                            <option value="Sí">Sí</option>
                                            <option value="No">No</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6" id="grupoMonto" style="display: none;">
                                        <label class="form-label-neo">Monto Mensual</label>
                                        <input type="text" class="form-control-neo" id="montoApoyo" name="montoApoyo">
                                    </div>
                                    <div class="col-12">
                                        <div class="horario-grid-neo">
                                            <label class="form-label-neo mb-3">Horario Propuesto (Lun-Vie 4hrs)</label>
                                            <div class="row g-3">
                                                <div class="col-6 col-md-3">
                                                    <select class="form-select-neo" id="diaInicio" name="diaInicio"
                                                        required>
                                                        <option value="">Desde</option>
                                                        <option value="L">Lunes</option>
                                                        <option value="M">Martes</option>
                                                        <option value="X">Miércoles</option>
                                                        <option value="J">Jueves</option>
                                                        <option value="V">Viernes</option>
                                                    </select>
                                                </div>
                                                <div class="col-6 col-md-3">
                                                    <select class="form-select-neo" id="diaFin" name="diaFin" required>
                                                        <option value="">Hasta</option>
                                                        <option value="L">Lunes</option>
                                                        <option value="M">Martes</option>
                                                        <option value="X">Miércoles</option>
                                                        <option value="J">Jueves</option>
                                                        <option value="V">Viernes</option>
                                                    </select>
                                                </div>
                                                <div class="col-6 col-md-3">
                                                    <select class="form-select-neo" id="horaInicio" name="horaInicio"
                                                        required>
                                                        <option value="">Hora Entrada</option>
                                                        <optgroup label="Mañana">
                                                            <option value="07:00">07:00</option>
                                                            <option value="08:00">08:00</option>
                                                            <option value="09:00">09:00</option>
                                                            <option value="10:00">10:00</option>
                                                            <option value="11:00">11:00</option>
                                                        </optgroup>
                                                        <optgroup label="Tarde">
                                                            <option value="12:00">12:00</option>
                                                            <option value="13:00">13:00</option>
                                                            <option value="14:00">14:00</option>
                                                            <option value="15:00">15:00</option>
                                                            <option value="16:00">16:00</option>
                                                        </optgroup>
                                                    </select>
                                                </div>
                                                <div class="col-6 col-md-3">
                                                    <div class="hf-auto-value-neo" id="horaFinDisplay">Salida Auto</div>
                                                    <input type="hidden" id="horaFin" name="horaFin">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- STEP 4: SEDE -->
                            <div class="wizard-step" id="step-4">
                                <div class="wizard-step-eyebrow">Paso 4</div>
                                <h3 class="wizard-step-title">Sede y Responsable</h3>
                                <div class="row g-4">
                                    <div class="col-12">
                                        <label class="form-label-neo">Dirección Sede *</label>
                                        <input type="text" class="form-control-neo" id="direccionPractica"
                                            name="direccionPractica" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label-neo">Nombre Responsable *</label>
                                        <input type="text" class="form-control-neo" id="nombreResponsable"
                                            name="nombreResponsable" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label-neo">Teléfono *</label>
                                        <input type="text" class="form-control-neo" id="contactoResponsable"
                                            name="contactoResponsable" required>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="wizard-main-foot">
                            <span class="wizard-step-counter" id="wizard-counter">Paso 1 de 4</span>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn-neo-secondary" id="btn-prev" style="display:none;"
                                    onclick="wizardStep(-1)"><i class="fas fa-arrow-left me-2"></i>Anterior</button>
                                <button type="button" class="btn-neo-primary" id="btn-next"
                                    onclick="wizardStep(1)">Siguiente <i class="fas fa-arrow-right ms-2"></i></button>
                                <button type="submit" class="btn-neo-primary" id="btn-submit" style="display:none;"><i
                                        class="fas fa-paper-plane me-2"></i> Publicar Vacante</button>
                            </div>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==========================================
     MODAL: EDITAR SOLICITUD (COPIA WIZARD)
     ========================================== -->
<!-- Por motivos de simplicidad y tiempo, usamos un diseño plano 2026 para el modo edición en lugar del wizard completo, ya que la edición se realiza rápidamente. -->
<div class="modal fade" id="editarPractModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content modal-content-neo">
            <div class="modal-header-neo">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Editar Vacante</h5>
                <button type="button" class="btn-close-neo" data-bs-dismiss="modal"><i
                        class="fas fa-times"></i></button>
            </div>
            <form id="editarForm" method="POST">
                <div class="modal-body-neo row g-4">
                    <input type="hidden" id="editarIdSolicitud" name="idSolicitud">

                    <div class="col-md-8"><label class="form-label-neo">Habilidades del Perfil</label>
                        <div class="skills-picker">
                            <div class="skills-chips" id="chipsHabilidadesEditar"></div>
                            <input type="text" id="buscarHabilidadEditar" class="form-control-neo"
                                placeholder="Busca o escribe una habilidad..." autocomplete="off">
                            <div class="skills-suggestions" id="suggHabilidadesEditar"></div>
                            <input type="hidden" name="habilidades" id="habilidadesEditar">
                        </div>
                    </div>
                    <div class="col-md-4"><label class="form-label-neo">Vacantes</label><input type="number"
                            class="form-control-neo" id="editarNumPract" name="numPract" required></div>
                    <div class="col-12"><label class="form-label-neo">Actividades formativas que desarrollarán los
                            practicantes</label><textarea id="editarActividades" name="actividades"
                            class="form-control-neo" rows="2" required></textarea></div>
                    <div class="col-md-6"><label class="form-label-neo">Funciones</label><textarea id="editarFunciones"
                            name="funciones" class="form-control-neo" rows="2" required></textarea></div>
                    <div class="col-md-6"><label class="form-label-neo">Resultados esperados</label><textarea
                            id="editarResultadosEsperados" name="resultadosEsperados" class="form-control-neo" rows="2"
                            required></textarea></div>

                    <div class="col-md-4"><label class="form-label-neo">Modalidad</label><select id="editarModalidad"
                            name="modalidad" class="form-select-neo" required>
                            <option value="Presencial">Presencial</option>
                            <option value="Híbrido">Híbrido</option>
                            <option value="Virtual">Virtual</option>
                        </select></div>
                    <div class="col-md-4"><label class="form-label-neo">Apoyo</label><select id="editarApoyoEconomico"
                            name="apoyoEconomico" class="form-select-neo" onchange="toggleEditarMonto()" required>
                            <option value="Sí">Sí</option>
                            <option value="No">No</option>
                        </select></div>
                    <div class="col-md-4" id="editarGrupoMonto" style="display:none;"><label
                            class="form-label-neo">Monto</label><input type="text" class="form-control-neo"
                            id="editarMontoApoyo" name="montoApoyo"></div>

                    <div class="col-md-4"><label class="form-label-neo">Día Inicio</label><select id="editarDiaInicio"
                            name="diaInicio" class="form-select-neo">
                            <option value="L">Lunes</option>
                            <option value="M">Martes</option>
                            <option value="X">Miércoles</option>
                            <option value="J">Jueves</option>
                            <option value="V">Viernes</option>
                        </select></div>
                    <div class="col-md-4"><label class="form-label-neo">Día Fin</label><select id="editarDiaFin"
                            name="diaFin" class="form-select-neo">
                            <option value="L">Lunes</option>
                            <option value="M">Martes</option>
                            <option value="X">Miércoles</option>
                            <option value="J">Jueves</option>
                            <option value="V">Viernes</option>
                        </select></div>
                    <div class="col-md-4"><label class="form-label-neo">Hora Entrada</label><select
                            id="editarHoraInicio" name="horaInicio" class="form-select-neo">
                            <option value="07:00">07:00</option>
                            <option value="08:00">08:00</option>
                            <option value="09:00">09:00</option>
                            <option value="10:00">10:00</option>
                            <option value="11:00">11:00</option>
                            <option value="12:00">12:00</option>
                            <option value="13:00">13:00</option>
                            <option value="14:00">14:00</option>
                            <option value="15:00">15:00</option>
                            <option value="16:00">16:00</option>
                        </select></div>
                    <input type="hidden" id="editarHoraFin" name="horaFin">
                    <input type="hidden" id="editarFechaLimite" name="fechaLimite">

                    <div class="col-12"><label class="form-label-neo">Habilidades</label><textarea
                            id="editarCapacidades" name="capacidades" class="form-control-neo" rows="2"></textarea>
                    </div>
                    <div class="col-12"><label class="form-label-neo">Actitudes</label><textarea
                            id="editarActitudes" name="actitudes" class="form-control-neo" rows="2"></textarea>
                    </div>
                    <div class="col-12"><label class="form-label-neo">Dirección</label><input type="text"
                            class="form-control-neo" id="editarDireccionPractica" name="direccionPractica" required>
                    </div>
                    <div class="col-md-6"><label class="form-label-neo">Responsable</label><input type="text"
                            class="form-control-neo" id="editarNombreResponsable" name="nombreResponsable" required>
                    </div>
                    <div class="col-md-6"><label class="form-label-neo">Teléfono</label><input type="text"
                            class="form-control-neo" id="editarContactoResponsable" name="contactoResponsable" required>
                    </div>
                </div>
                <div class="modal-footer-neo">
                    <button type="button" class="btn-neo-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-neo-primary"><i class="fas fa-save"></i> Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==========================================
     MODAL: VER CANDIDATOS
     ========================================== -->
<div class="modal fade" id="viewUsersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content modal-content-neo">
            <div class="modal-header-neo">
                <h5 class="modal-title"><i class="fas fa-users"></i> Candidatos Postulados</h5>
                <button type="button" class="btn-close-neo" data-bs-dismiss="modal"><i
                        class="fas fa-times"></i></button>
            </div>
            <div class="modal-body-neo">
                <div id="prospectsList"></div> <!-- Llenado por solicitudes.js -->
            </div>
            <div class="modal-footer-neo">
                <button type="button" class="btn-neo-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================
     MODAL: REPORTES
     ========================================== -->
<div class="modal fade" id="reporteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content modal-content-neo">
            <div class="modal-header-neo">
                <h5 class="modal-title"><i class="fas fa-file-alt"></i> Reporte Parcial</h5>
                <button type="button" class="btn-close-neo" data-bs-dismiss="modal"><i
                        class="fas fa-times"></i></button>
            </div>
            <div class="modal-body-neo row g-4">
                <div class="col-12"><label class="form-label-neo">Objetivo</label>
                    <div class="form-control-neo" style="min-height:3rem;background:white;" id="objetivoReporte"></div>
                </div>
                <div class="col-12"><label class="form-label-neo">Actividades</label>
                    <div class="form-control-neo" style="min-height:3rem;background:white;" id="actividadesReporte">
                    </div>
                </div>
            </div>
            <div class="modal-footer-neo" style="justify-content:center;">
                <button type="button" class="btn-neo-secondary text-danger" id="btnRechazarReporte">Rechazar</button>
                <button type="button" class="btn-neo-primary" id="btnAceptarReporte">Aceptar y Evaluar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="reporteFinalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content modal-content-neo">
            <div class="modal-header-neo">
                <h5 class="modal-title"><i class="fas fa-flag-checkered"></i> Reporte Final</h5>
                <button type="button" class="btn-close-neo" data-bs-dismiss="modal"><i
                        class="fas fa-times"></i></button>
            </div>
            <div class="modal-body-neo row g-4">
                <div class="col-12"><label class="form-label-neo">Objetivo</label>
                    <div class="form-control-neo" style="background:white;" id="objetivoFinal"></div>
                </div>
                <div class="col-12"><label class="form-label-neo">Actividades</label>
                    <div class="form-control-neo" style="background:white;" id="actividadesFinal"></div>
                </div>
                <div class="col-md-6"><label class="form-label-neo">Capacitación</label>
                    <div class="form-control-neo" style="background:white;" id="capacitacionRecibida"></div>
                </div>
                <div class="col-md-6"><label class="form-label-neo">Exp. Personal</label>
                    <div class="form-control-neo" style="background:white;" id="experienciaPersonal"></div>
                </div>
                <div class="col-md-6"><label class="form-label-neo">Exp. Profesional</label>
                    <div class="form-control-neo" style="background:white;" id="experienciaProfesional"></div>
                </div>
                <div class="col-md-6"><label class="form-label-neo">Resultados</label>
                    <div class="form-control-neo" style="background:white;" id="resultadosObtenidos"></div>
                </div>
            </div>
            <div class="modal-footer-neo" style="justify-content:center;">
                <button type="button" class="btn-neo-secondary text-danger"
                    id="btnRechazarReporteFinal">Rechazar</button>
                <button type="button" class="btn-neo-primary" id="btnAceptarReporteFinal">Aceptar y Evaluar</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts de utilería para el Wizard -->
<script>
    let currentStep = 1;
    const TOTAL_STEPS = 4;

    function syncWizardUI() {
        for (let i = 1; i <= TOTAL_STEPS; i++) {
            const stepEl = document.getElementById('step-' + i);
            const railEl = document.querySelector('.rail-step[data-step="' + i + '"]');
            if (stepEl) stepEl.classList.toggle('active', i === currentStep);
            if (railEl) {
                railEl.classList.toggle('active', i === currentStep);
                railEl.classList.toggle('completed', i < currentStep);
            }
        }
        document.getElementById('wizard-counter').textContent = 'Paso ' + currentStep + ' de ' + TOTAL_STEPS;
        document.getElementById('btn-prev').style.display = currentStep === 1 ? 'none' : 'inline-flex';
        document.getElementById('btn-next').style.display = currentStep === TOTAL_STEPS ? 'none' : 'inline-flex';
        document.getElementById('btn-submit').style.display = currentStep === TOTAL_STEPS ? 'inline-flex' : 'none';

        const body = document.querySelector('#solicitarPractModal .wizard-main-body');
        if (body) body.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function wizardStep(direction) {
        // Validación HTML5 al avanzar
        if (direction === 1) {
            const currentInputs = document.querySelectorAll('#step-' + currentStep + ' [required]');
            let valid = true;
            currentInputs.forEach(inp => { if (!inp.checkValidity()) { inp.reportValidity(); valid = false; } });
            if (!valid) return;
            // El perfil requiere al menos una habilidad seleccionada
            if (currentStep === 1 && window.skillsPickerNueva && !window.skillsPickerNueva.validate()) return;
        }
        const next = currentStep + direction;
        if (next < 1 || next > TOTAL_STEPS) return;
        currentStep = next;
        syncWizardUI();
    }

    // Navegación directa desde el rail (solo hacia pasos ya visitados)
    document.querySelectorAll('#solicitarPractModal .rail-step').forEach(el => {
        el.addEventListener('click', function () {
            const target = parseInt(this.getAttribute('data-step'), 10);
            if (target < currentStep) { currentStep = target; syncWizardUI(); }
        });
    });

    // Limpiar wizard al cerrar el modal
    document.getElementById('solicitarPractModal').addEventListener('hidden.bs.modal', function () {
        currentStep = 1;
        document.getElementById('solicitarForm').reset();
        document.getElementById('grupoMonto').style.display = 'none';
        if (window.skillsPickerNueva) window.skillsPickerNueva.clear();
        syncWizardUI();
    });
</script>