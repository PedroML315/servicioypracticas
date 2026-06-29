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
  border-bottom: 1px solid rgba(0,0,0,0.05);
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.modal-header-neo .modal-title { font-weight: 900; font-size: 1.5rem; letter-spacing: -0.02em; color: #1e293b !important; display: flex; align-items: center; gap: 0.75rem; margin: 0; }
.modal-header-neo .modal-title i { color: #01643d; background: rgba(1, 100, 61, 0.1); padding: 0.75rem; border-radius: 1rem; }
.btn-close-neo { background: #f1f5f9; border: none; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; color: #64748b; }
.btn-close-neo:hover { background: #e2e8f0; color: #0f172a; transform: rotate(90deg); }

.modal-body-neo { padding: 2.5rem; }
.modal-footer-neo { padding: 1.5rem 2.5rem; background: rgba(255,255,255,0.8); border-top: 1px solid rgba(0,0,0,0.05); display: flex; justify-content: flex-end; gap: 1rem; }

/* Formularios "Float/Clean" */
.form-label-neo { font-size: 0.85rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #475569; margin-bottom: 0.5rem; display: block; }
.form-control-neo, .form-select-neo {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 1rem;
  padding: 1rem 1.25rem;
  font-size: 1rem;
  color: #0f172a;
  font-weight: 600;
  transition: all 0.3s;
  box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
  width: 100%;
}
.form-control-neo:focus, .form-select-neo:focus {
  background: white; border-color: var(--brand-main);
  box-shadow: 0 0 0 4px rgba(1, 100, 61, 0.1); outline: none;
}

/* WIZARD PROGRESS */
.wizard-progress { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2.5rem; position: relative; }
.wizard-progress::before { content: ''; position: absolute; top: 50%; left: 0; right: 0; height: 4px; background: #e2e8f0; z-index: 1; border-radius: 2px; }
.wizard-step-indicator { position: relative; z-index: 2; background: white; border: 4px solid #e2e8f0; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; font-weight: 900; color: #94a3b8; transition: all 0.3s; }
.wizard-step-indicator.active { border-color: var(--brand-main); color: var(--brand-main); box-shadow: 0 0 0 4px rgba(1, 100, 61, 0.1); }
.wizard-step-indicator.completed { background: var(--brand-main); border-color: var(--brand-main); color: white; }

.wizard-step { display: none; animation: slideIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
.wizard-step.active { display: block; }

@keyframes slideIn {
  from { opacity: 0; transform: translateX(20px); }
  to { opacity: 1; transform: translateX(0); }
}

/* HORARIO GRID */
.horario-grid-neo { background: rgba(255,255,255,0.4); border-radius: 1.5rem; padding: 1.5rem; border: 1px dashed rgba(0,0,0,0.1); }
.hf-auto-value-neo { display: flex; align-items: center; justify-content: center; padding: 1rem; background: rgba(1,100,61,0.05); color: var(--brand-main); font-weight: 900; border-radius: 1rem; border: 1px solid rgba(1,100,61,0.2); }

/* BOTONES */
.btn-neo-secondary { background: white; border: 1px solid #e2e8f0; color: var(--text-primary); font-weight: 800; border-radius: 100px; padding: 0.75rem 1.5rem; transition: all 0.2s; }
.btn-neo-secondary:hover { background: #f8fafc; border-color: #cbd5e1; }
.btn-neo-primary { background: var(--brand-main); border: none; color: white; font-weight: 800; border-radius: 100px; padding: 0.75rem 2rem; box-shadow: inset 0 -3px 0 rgba(0,0,0,0.1); transition: all 0.2s; }
.btn-neo-primary:hover { transform: translateY(-2px); box-shadow: inset 0 -3px 0 rgba(0,0,0,0.1), 0 10px 20px -5px rgba(1, 100, 61, 0.4); }
</style>

<!-- ==========================================
     MODAL: MIS SOLICITUDES (MASTER-DETAIL)
     ========================================== -->
<div class="modal fade" id="solicitudesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content modal-content-neo" style="border-radius: 0; border: none;">
            <div class="modal-header-neo bg-white" style="padding: 1.5rem 2.5rem;">
                <h5 class="modal-title"><i class="fas fa-briefcase"></i> Gestor de Vacantes y Postulantes</h5>
                <button type="button" class="btn-close-neo" data-bs-dismiss="modal"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body-neo p-0 d-flex flex-column" style="height: calc(100vh - 90px); overflow: hidden;">
                <div class="row g-0 flex-grow-1" style="height: 100%;">
                    
                    <!-- Left Sidebar (Master) -->
                    <div class="col-md-4 col-lg-3 border-end" style="height: 100%; overflow-y: auto; background: #f8fafc;">
                        <div class="p-4 border-bottom d-flex flex-column gap-3 bg-white sticky-top">
                            <button id="btnSolicitarPract" class="btn-neo-primary w-100" onclick="$('#solicitudesModal').modal('hide'); $('#solicitarPractModal').modal('show');" style="padding: 0.75rem;">
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
                                <p style="font-size: 1.1rem;">Haz clic en una vacante del panel izquierdo para ver sus detalles y administrar a los alumnos postulados.</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================
     MODAL: NUEVA SOLICITUD (WIZARD)
     ========================================== -->
<div class="modal fade" id="solicitarPractModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content modal-content-neo">
            <div class="modal-header-neo">
                <h5 class="modal-title"><i class="fas fa-magic"></i> Nueva Vacante</h5>
                <button type="button" class="btn-close-neo" data-bs-dismiss="modal"><i class="fas fa-times"></i></button>
            </div>
            <form id="solicitarForm" method="POST">
                <div class="modal-body-neo">
                    
                    <!-- Progress Bar -->
                    <div class="wizard-progress">
                        <div class="wizard-step-indicator active" id="ind-1">1</div>
                        <div class="wizard-step-indicator" id="ind-2">2</div>
                        <div class="wizard-step-indicator" id="ind-3">3</div>
                    </div>

                    <!-- STEP 1: PERFIL -->
                    <div class="wizard-step active" id="step-1">
                        <h3 class="mb-4" style="font-weight:900; color:var(--brand-dark);">1. Perfil del Estudiante</h3>
                        <div class="row g-4">
                            <div class="col-md-8">
                                <label class="form-label-neo">Licenciatura Solicitada *</label>
                                <select id="licenciatura" name="licenciatura" class="form-select-neo" required>
                                    <option value="">Selecciona una opción</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-neo">Vacantes *</label>
                                <input type="number" class="form-control-neo" id="numPract" name="numPract" min="1" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label-neo">Actividades a Realizar *</label>
                                <textarea id="actividades" name="actividades" class="form-control-neo" rows="3" required></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2: CONDICIONES -->
                    <div class="wizard-step" id="step-2">
                        <h3 class="mb-4" style="font-weight:900; color:var(--brand-dark);">2. Condiciones y Horario</h3>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label-neo">Modalidad *</label>
                                <select id="modalidad" name="modalidad" class="form-select-neo" required>
                                    <option value="">Selecciona</option><option value="Presencial">Presencial</option><option value="Híbrido">Híbrido</option><option value="Virtual">Virtual</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-neo">Fecha Límite *</label>
                                <input type="date" class="form-control-neo" id="fechaLimite" name="fechaLimite" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-neo">Apoyo Económico *</label>
                                <select id="apoyoEconomico" name="apoyoEconomico" class="form-select-neo" onchange="toggleMonto()" required>
                                    <option value="">Selecciona</option><option value="Sí">Sí</option><option value="No">No</option>
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
                                            <select class="form-select-neo" id="diaInicio" name="diaInicio" required>
                                                <option value="">Desde</option><option value="L">Lunes</option><option value="M">Martes</option><option value="X">Miércoles</option><option value="J">Jueves</option><option value="V">Viernes</option>
                                            </select>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <select class="form-select-neo" id="diaFin" name="diaFin" required>
                                                <option value="">Hasta</option><option value="L">Lunes</option><option value="M">Martes</option><option value="X">Miércoles</option><option value="J">Jueves</option><option value="V">Viernes</option>
                                            </select>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <select class="form-select-neo" id="horaInicio" name="horaInicio" required>
                                                <option value="">Hora Entrada</option>
                                                <optgroup label="Mañana"><option value="07:00">07:00</option><option value="08:00">08:00</option><option value="09:00">09:00</option><option value="10:00">10:00</option><option value="11:00">11:00</option></optgroup>
                                                <optgroup label="Tarde"><option value="12:00">12:00</option><option value="13:00">13:00</option><option value="14:00">14:00</option><option value="15:00">15:00</option><option value="16:00">16:00</option></optgroup>
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

                    <!-- STEP 3: SEDE -->
                    <div class="wizard-step" id="step-3">
                        <h3 class="mb-4" style="font-weight:900; color:var(--brand-dark);">3. Sede y Responsable</h3>
                        <div class="row g-4">
                            <div class="col-12">
                                <label class="form-label-neo">Habilidades Deseadas</label>
                                <textarea id="capacidades" name="capacidades" class="form-control-neo" rows="2"></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label-neo">Dirección Sede *</label>
                                <input type="text" class="form-control-neo" id="direccionPractica" name="direccionPractica" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-neo">Nombre Responsable *</label>
                                <input type="text" class="form-control-neo" id="nombreResponsable" name="nombreResponsable" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-neo">Teléfono *</label>
                                <input type="text" class="form-control-neo" id="contactoResponsable" name="contactoResponsable" required>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer-neo">
                    <button type="button" class="btn-neo-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn-neo-secondary" id="btn-prev" style="display:none;" onclick="wizardStep(-1)">Anterior</button>
                    <button type="button" class="btn-neo-primary" id="btn-next" onclick="wizardStep(1)">Siguiente <i class="fas fa-arrow-right ms-2"></i></button>
                    <button type="submit" class="btn-neo-primary" id="btn-submit" style="display:none;"><i class="fas fa-paper-plane me-2"></i> Publicar Vacante</button>
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
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content modal-content-neo">
            <div class="modal-header-neo">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Editar Vacante</h5>
                <button type="button" class="btn-close-neo" data-bs-dismiss="modal"><i class="fas fa-times"></i></button>
            </div>
            <form id="editarForm" method="POST">
                <div class="modal-body-neo row g-4">
                    <input type="hidden" id="editarIdSolicitud" name="idSolicitud">
                    
                    <div class="col-md-6"><label class="form-label-neo">Licenciatura</label><select id="editarLicenciatura" name="licenciatura" class="form-select-neo" required><option value="">Selecciona una opción</option></select></div>
                    <div class="col-md-6"><label class="form-label-neo">Vacantes</label><input type="number" class="form-control-neo" id="editarNumPract" name="numPract" required></div>
                    <div class="col-12"><label class="form-label-neo">Actividades</label><textarea id="editarActividades" name="actividades" class="form-control-neo" rows="2" required></textarea></div>
                    
                    <div class="col-md-4"><label class="form-label-neo">Modalidad</label><select id="editarModalidad" name="modalidad" class="form-select-neo" required><option value="Presencial">Presencial</option><option value="Híbrido">Híbrido</option><option value="Virtual">Virtual</option></select></div>
                    <div class="col-md-4"><label class="form-label-neo">Apoyo</label><select id="editarApoyoEconomico" name="apoyoEconomico" class="form-select-neo" onchange="toggleEditarMonto()" required><option value="Sí">Sí</option><option value="No">No</option></select></div>
                    <div class="col-md-4" id="editarGrupoMonto" style="display:none;"><label class="form-label-neo">Monto</label><input type="text" class="form-control-neo" id="editarMontoApoyo" name="montoApoyo"></div>
                    
                    <div class="col-md-4"><label class="form-label-neo">Día Inicio</label><select id="editarDiaInicio" name="diaInicio" class="form-select-neo"><option value="L">Lunes</option><option value="M">Martes</option><option value="X">Miércoles</option><option value="J">Jueves</option><option value="V">Viernes</option></select></div>
                    <div class="col-md-4"><label class="form-label-neo">Día Fin</label><select id="editarDiaFin" name="diaFin" class="form-select-neo"><option value="L">Lunes</option><option value="M">Martes</option><option value="X">Miércoles</option><option value="J">Jueves</option><option value="V">Viernes</option></select></div>
                    <div class="col-md-4"><label class="form-label-neo">Hora Entrada</label><select id="editarHoraInicio" name="horaInicio" class="form-select-neo"><option value="07:00">07:00</option><option value="08:00">08:00</option><option value="09:00">09:00</option><option value="10:00">10:00</option><option value="11:00">11:00</option><option value="12:00">12:00</option><option value="13:00">13:00</option><option value="14:00">14:00</option><option value="15:00">15:00</option><option value="16:00">16:00</option></select></div>
                    <input type="hidden" id="editarHoraFin" name="horaFin">
                    <input type="hidden" id="editarFechaLimite" name="fechaLimite">

                    <div class="col-12"><label class="form-label-neo">Habilidades</label><textarea id="editarCapacidades" name="capacidades" class="form-control-neo" rows="2"></textarea></div>
                    <div class="col-12"><label class="form-label-neo">Dirección</label><input type="text" class="form-control-neo" id="editarDireccionPractica" name="direccionPractica" required></div>
                    <div class="col-md-6"><label class="form-label-neo">Responsable</label><input type="text" class="form-control-neo" id="editarNombreResponsable" name="nombreResponsable" required></div>
                    <div class="col-md-6"><label class="form-label-neo">Teléfono</label><input type="text" class="form-control-neo" id="editarContactoResponsable" name="contactoResponsable" required></div>
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
                <button type="button" class="btn-close-neo" data-bs-dismiss="modal"><i class="fas fa-times"></i></button>
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
                <button type="button" class="btn-close-neo" data-bs-dismiss="modal"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body-neo row g-4">
                <div class="col-12"><label class="form-label-neo">Objetivo</label><div class="form-control-neo" style="min-height:3rem;background:white;" id="objetivoReporte"></div></div>
                <div class="col-12"><label class="form-label-neo">Actividades</label><div class="form-control-neo" style="min-height:3rem;background:white;" id="actividadesReporte"></div></div>
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
                <button type="button" class="btn-close-neo" data-bs-dismiss="modal"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body-neo row g-4">
                <div class="col-12"><label class="form-label-neo">Objetivo</label><div class="form-control-neo" style="background:white;" id="objetivoFinal"></div></div>
                <div class="col-12"><label class="form-label-neo">Actividades</label><div class="form-control-neo" style="background:white;" id="actividadesFinal"></div></div>
                <div class="col-md-6"><label class="form-label-neo">Capacitación</label><div class="form-control-neo" style="background:white;" id="capacitacionRecibida"></div></div>
                <div class="col-md-6"><label class="form-label-neo">Exp. Personal</label><div class="form-control-neo" style="background:white;" id="experienciaPersonal"></div></div>
                <div class="col-md-6"><label class="form-label-neo">Exp. Profesional</label><div class="form-control-neo" style="background:white;" id="experienciaProfesional"></div></div>
                <div class="col-md-6"><label class="form-label-neo">Resultados</label><div class="form-control-neo" style="background:white;" id="resultadosObtenidos"></div></div>
            </div>
            <div class="modal-footer-neo" style="justify-content:center;">
                <button type="button" class="btn-neo-secondary text-danger" id="btnRechazarReporteFinal">Rechazar</button>
                <button type="button" class="btn-neo-primary" id="btnAceptarReporteFinal">Aceptar y Evaluar</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts de utilería para el Wizard -->
<script>
    let currentStep = 1;
    function wizardStep(direction) {
        // Validación básica HTML5 antes de avanzar
        if (direction === 1) {
            const currentInputs = document.querySelectorAll('#step-' + currentStep + ' [required]');
            let valid = true;
            currentInputs.forEach(inp => { if (!inp.checkValidity()) { inp.reportValidity(); valid = false; } });
            if (!valid) return;
        }

        document.getElementById('step-' + currentStep).classList.remove('active');
        document.getElementById('ind-' + currentStep).classList.remove('active');
        if(direction === 1) document.getElementById('ind-' + currentStep).classList.add('completed');
        else document.getElementById('ind-' + currentStep).classList.remove('completed');

        currentStep += direction;

        document.getElementById('step-' + currentStep).classList.add('active');
        document.getElementById('ind-' + currentStep).classList.add('active');
        document.getElementById('ind-' + currentStep).classList.remove('completed');

        document.getElementById('btn-prev').style.display = currentStep === 1 ? 'none' : 'block';
        if (currentStep === 3) {
            document.getElementById('btn-next').style.display = 'none';
            document.getElementById('btn-submit').style.display = 'block';
        } else {
            document.getElementById('btn-next').style.display = 'block';
            document.getElementById('btn-submit').style.display = 'none';
        }
    }

    // Limpiar wizard al cerrar el modal
    document.getElementById('solicitarPractModal').addEventListener('hidden.bs.modal', function () {
        currentStep = 1;
        document.querySelectorAll('.wizard-step').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.wizard-step-indicator').forEach(el => { el.classList.remove('active'); el.classList.remove('completed'); });
        document.getElementById('step-1').classList.add('active');
        document.getElementById('ind-1').classList.add('active');
        document.getElementById('btn-prev').style.display = 'none';
        document.getElementById('btn-next').style.display = 'block';
        document.getElementById('btn-submit').style.display = 'none';
        document.getElementById('solicitarForm').reset();
        document.getElementById('grupoMonto').style.display = 'none';
    });
</script>