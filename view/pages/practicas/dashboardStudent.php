<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.9/jquery.inputmask.min.js"></script>

<style>
  @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800;900&display=swap');

  :root {
    --bg-page: #f0f2f5;
    --bento-bg: rgba(255, 255, 255, 0.75);
    --bento-blur: blur(24px);
    --bento-border: 1px solid rgba(255, 255, 255, 0.6);
    --bento-shadow: 0 16px 40px -10px rgba(0, 0, 0, 0.05);
    
    --brand-dark: #0f172a;
    --brand-main: #01643D;
    --brand-accent: #c6db53;
    
    --text-primary: #1e293b;
    --text-secondary: #64748b;
  }

  .ui-2026 {
    background-color: var(--bg-page);
    min-height: calc(100vh - 58px);
    padding: 2rem;
    font-family: 'Outfit', sans-serif;
  }

  /* BENTO GRID LAYOUT */
  .bento-grid {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    grid-auto-rows: minmax(140px, auto);
    gap: 1.5rem;
    margin-bottom: 3rem;
  }

  .bento-card {
    background: var(--bento-bg);
    backdrop-filter: var(--bento-blur);
    -webkit-backdrop-filter: var(--bento-blur);
    border: var(--bento-border);
    border-radius: 2rem;
    padding: 2rem;
    box-shadow: var(--bento-shadow);
    transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.4s ease;
    overflow: hidden;
    position: relative;
  }
  .bento-card:hover {
    transform: translateY(-4px) scale(1.01);
    box-shadow: 0 24px 50px -12px rgba(1, 100, 61, 0.15);
  }

  /* Grid Area Assignments */
  .bento-hero { grid-column: span 12; grid-row: span 2; display: flex; flex-direction: column; justify-content: center; background: linear-gradient(135deg, #01643D, #00204a); color: white; border: none; }
  .bento-kpi-1, .bento-kpi-2, .bento-kpi-3, .bento-kpi-4 { grid-column: span 3; grid-row: span 1; text-align: center; display: flex; flex-direction: column; justify-content: center; align-items: center; }

  @media (max-width: 1200px) {
    .bento-kpi-1, .bento-kpi-2, .bento-kpi-3, .bento-kpi-4 { grid-column: span 6; }
  }
  @media (max-width: 768px) {
    .ui-2026 { padding: 1rem; }
  }

  /* HERO CONTENT */
  .hero-blob {
    position: absolute;
    width: 300px; height: 300px;
    background: var(--brand-accent);
    filter: blur(80px);
    opacity: 0.3;
    border-radius: 50%;
    top: -50px; right: -50px;
    pointer-events: none;
  }
  .hero-title { font-size: 2.5rem; font-weight: 900; letter-spacing: -0.03em; margin-bottom: 0.5rem; position: relative; z-index: 2; }
  .hero-subtitle { font-size: 1.1rem; font-weight: 300; opacity: 0.9; margin-bottom: 1.5rem; position: relative; z-index: 2; }
  
  .btn-neo {
    display: inline-flex; align-items: center; gap: 0.75rem;
    background: var(--brand-accent); color: var(--brand-dark);
    font-weight: 800; font-size: 1rem;
    padding: 0.8rem 1.5rem; border-radius: 100px; border: none;
    text-decoration: none; position: relative; z-index: 2;
    box-shadow: inset 0 -4px 0 rgba(0,0,0,0.1);
    transition: all 0.2s;
    width: fit-content;
  }
  .btn-neo:hover { transform: translateY(-2px); box-shadow: inset 0 -4px 0 rgba(0,0,0,0.1), 0 10px 20px -5px rgba(198, 219, 83, 0.4); color: var(--brand-dark); }
  .btn-neo:active { transform: translateY(2px); box-shadow: none; }

  /* KPIs */
  .kpi-title { font-size: 0.9rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-secondary); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
  .kpi-value { font-size: 3rem; font-weight: 900; line-height: 1; color: var(--text-primary); letter-spacing: -0.04em; }

  /* TAB NAVIGATION 2026 */
  .neo-tabs-nav {
    display: flex; gap: 0.5rem;
    background: rgba(255,255,255,0.5); backdrop-filter: blur(12px);
    padding: 0.5rem; border-radius: 100px;
    width: fit-content; margin: 0 auto 2rem auto;
    border: 1px solid rgba(255,255,255,0.8);
    box-shadow: 0 4px 20px -5px rgba(0,0,0,0.05);
  }
  .neo-tab-btn {
    border: none; background: transparent;
    padding: 0.75rem 1.5rem; border-radius: 100px;
    font-weight: 700; font-size: 1rem; color: var(--text-secondary);
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
  }
  .neo-tab-btn.active {
    background: white; color: var(--brand-main);
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
  }
  .neo-tab-btn:hover:not(.active) { color: var(--text-primary); }

  /* TAB PANES */
  .neo-tab-pane { display: none; animation: neoFadeIn 0.4s ease forwards; }
  .neo-tab-pane.active { display: block; }
  
  @keyframes neoFadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
  }

  .pane-card {
    background: var(--bento-bg); backdrop-filter: var(--bento-blur); -webkit-backdrop-filter: var(--bento-blur);
    border: var(--bento-border); border-radius: 2rem; padding: 2.5rem;
    box-shadow: var(--bento-shadow);
    margin-bottom: 2rem;
  }
  .pane-header { margin-bottom: 2rem; }
  .pane-title { font-size: 1.8rem; font-weight: 900; letter-spacing: -0.03em; margin: 0; color: var(--brand-dark); }
  .pane-desc { font-size: 1.05rem; color: var(--text-secondary); margin-top: 0.5rem; }

  /* Modales */
  .pp-modal-header {
    background: linear-gradient(135deg, #01643D, #00204a);
    color: #fff;
    border-radius: 1rem 1rem 0 0;
  }
  .pp-modal-header .btn-close { filter: invert(1) brightness(2); }

  /* Search bar neo */
  .pp-search-wrap {
    background: white;
    border-radius: 100px;
    border: 1px solid rgba(0,0,0,0.1);
    box-shadow: 0 10px 25px rgba(0,0,0,0.05);
    padding: 1rem 1.5rem;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    max-width: 600px;
    margin-left: auto; margin-right: auto;
  }
  .pp-search-wrap input {
    border: none; outline: none; background: transparent; flex: 1;
    font-size: 1.1rem; font-family: 'Outfit', sans-serif;
  }

  /* ════════════════════════════════════════════════
     VACANTES DEL ALUMNO — tarjetas compactas + modal
     ════════════════════════════════════════════════ */
  .pp-vacantes-head {
    display: flex; align-items: flex-end; justify-content: space-between;
    gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem;
  }
  .pp-vacantes-title { font-size: 1.5rem; font-weight: 900; color: var(--brand-dark); margin: 0; letter-spacing: -.02em; }
  .pp-vacantes-sub { margin: .25rem 0 0; color: var(--text-secondary); font-size: .95rem; }
  .pp-vacantes-count {
    background: rgba(1,100,61,.1); color: var(--brand-main);
    border-radius: 100px; padding: .55rem 1.1rem; font-weight: 800; font-size: .9rem; white-space: nowrap;
  }

  .pp-vac-card {
    background: #fff; border: 1px solid #e8edf1; border-radius: 1.5rem;
    padding: 1.35rem; height: 100%; display: flex; flex-direction: column; gap: 1rem;
    box-shadow: 0 6px 18px -12px rgba(0,0,0,.15);
    transition: transform .25s cubic-bezier(.16,1,.3,1), box-shadow .25s ease, border-color .25s ease;
  }
  .pp-vac-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 18px 36px -14px rgba(1,100,61,.28);
    border-color: rgba(1,100,61,.35);
  }

  .pp-vac-head { display: flex; align-items: center; gap: .85rem; }
  .pp-vac-avatar {
    width: 46px; height: 46px; flex-shrink: 0; border-radius: 13px;
    background: linear-gradient(135deg, #01643D, #c6db53); color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-weight: 900; font-size: 1rem;
  }
  .pp-vac-headtext { flex: 1; min-width: 0; }
  .pp-vac-empresa {
    margin: 0; font-size: 1.05rem; font-weight: 800; color: var(--brand-dark);
    line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  }
  .pp-vac-giro {
    font-size: .82rem; color: var(--text-secondary);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block;
  }
  .pp-vac-ciudad {
    flex-shrink: 0; font-size: .75rem; font-weight: 700; color: var(--brand-main);
    background: rgba(1,100,61,.08); border-radius: 100px; padding: .3rem .65rem;
  }
  .pp-vac-ciudad i { margin-right: .3rem; }

  .pp-vac-skills { display: flex; flex-direction: column; gap: .5rem; }
  .pp-vac-label {
    font-size: .68rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em;
    color: #94a3b8;
  }
  .pp-skill-wrap { display: flex; flex-wrap: wrap; gap: .4rem; }
  .pp-skill-chip {
    background: rgba(1,100,61,.08); color: #01643D; border: 1px solid rgba(1,100,61,.18);
    border-radius: 100px; padding: .28rem .7rem; font-size: .78rem; font-weight: 700; line-height: 1.2;
  }
  .pp-skill-chip.pp-skill-more { background: #f1f5f9; color: #64748b; border-color: #e2e8f0; }
  .pp-skill-empty { font-size: .82rem; color: #94a3b8; font-style: italic; }

  .pp-vac-stats { display: flex; flex-wrap: wrap; gap: .45rem; }
  .pp-stat {
    display: inline-flex; align-items: center; gap: .35rem;
    background: #f6f8fa; color: #475569; border-radius: 8px;
    padding: .35rem .6rem; font-size: .78rem; font-weight: 600;
  }
  .pp-stat i { color: #94a3b8; }
  .pp-stat-vac { background: #eef2ff; color: #4338ca; } .pp-stat-vac i { color: #6366f1; }
  .pp-stat-apoyo { background: #dcfce7; color: #15803d; } .pp-stat-apoyo i { color: #22c55e; }

  .pp-vac-actions { margin-top: auto; padding-top: .5rem; display: flex; gap: .6rem; align-items: stretch; }
  .pp-btn-detalle {
    flex-shrink: 0; background: #fff; border: 1px solid #d5dde3; color: #475569;
    border-radius: 9px; padding: .5rem .9rem; font-size: .82rem; font-weight: 700; cursor: pointer;
    transition: all .2s;
  }
  .pp-btn-detalle:hover { background: #f6f8fa; border-color: #01643D; color: #01643D; }
  .pp-vac-cta { flex: 1; display: flex; }
  .pp-vac-cta > button { width: 100%; }

  /* Estado vacío */
  .pp-empty-state { text-align: center; padding: 4rem 1rem; color: var(--text-secondary); }
  .pp-empty-icon {
    width: 84px; height: 84px; border-radius: 50%; margin: 0 auto 1.25rem;
    background: rgba(1,100,61,.08); color: var(--brand-main);
    display: flex; align-items: center; justify-content: center; font-size: 2rem;
  }
  .pp-empty-state h4 { font-weight: 800; color: var(--brand-dark); margin-bottom: .4rem; }
  .pp-empty-state p { max-width: 420px; margin: 0 auto; }

  /* ── Modal de detalle ── */
  .pp-detalle-content { border: none; border-radius: 1.5rem; overflow: hidden; }
  .pp-detalle-header {
    background: linear-gradient(135deg, #01643D, #00204a); color: #fff;
    padding: 1.5rem 1.75rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem;
  }
  #ppDetalleHeadInfo { display: flex; align-items: center; gap: 1rem; min-width: 0; }
  .pp-detalle-avatar {
    width: 52px; height: 52px; flex-shrink: 0; border-radius: 14px;
    background: rgba(255,255,255,.18); display: flex; align-items: center; justify-content: center;
    font-weight: 900; font-size: 1.15rem;
  }
  .pp-detalle-empresa { margin: 0; font-size: 1.35rem; font-weight: 900; }
  .pp-detalle-meta { font-size: .85rem; opacity: .9; }
  .pp-detalle-close {
    background: rgba(255,255,255,.15); border: none; color: #fff; width: 38px; height: 38px;
    border-radius: 50%; cursor: pointer; flex-shrink: 0; transition: all .2s;
  }
  .pp-detalle-close:hover { background: rgba(255,255,255,.3); transform: rotate(90deg); }

  .pp-detalle-body { padding: 1.75rem; }
  .pp-det-quickrow { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: 1.5rem; }
  .pp-det-block { margin-bottom: 1.4rem; }
  .pp-det-skills { display: flex; flex-wrap: wrap; gap: .45rem; margin-top: .6rem; }
  .pp-det-field { margin-bottom: 1.25rem; }
  .pp-det-field-label {
    font-size: .72rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em;
    color: var(--brand-main); margin-bottom: .4rem; display: flex; align-items: center; gap: .5rem;
  }
  .pp-det-field-value { font-size: .95rem; color: #334155; line-height: 1.55; }
  .pp-det-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem 1.75rem; margin-top: .5rem; }
  @media (max-width: 576px) { .pp-det-grid { grid-template-columns: 1fr; } }

  .pp-detalle-footer { padding: 1.1rem 1.75rem; border-top: 1px solid #eef1f4; background: #fafbfc; }
  .pp-detalle-cta > button { width: 100%; padding: .7rem; font-size: 1rem; }
</style>

<div class="ui-2026">

    <!-- ══ CONTENEDOR DEL HERO DINÁMICO ══ -->
    <div id="student-hero-container"></div>

    <!-- ══ BUSCADOR (visible solo para solicitudes externas) ══ -->
    <div class="pp-search-wrap searchTab" style="display:none;">
        <i class="fas fa-search" style="color:var(--brand-main);flex-shrink:0;"></i>
        <input type="text" id="searchPractices" class="form-control border-0 ps-1"
            placeholder="Buscar por empresa, habilidad, actividad o ciudad…">
    </div>

    <!-- ══ CONTENEDOR PRINCIPAL DINÁMICO ══ -->
    <div id="student-main-container">
        <div class="text-center py-5 text-muted">
            <i class="fas fa-spinner fa-spin fa-2x mb-3"></i><p>Cargando información...</p>
        </div>
    </div>

</div><!-- /ui-2026 -->


<!-- Modal para pase de asistencia -->
<div class="modal fade" id="modalAsistencia" tabindex="-1" aria-labelledby="modalAsistenciaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form id="formAsistencia" class="modal-content">
            <!-- header gradient -->
            <div class="modal-header pp-modal-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold text-white" id="modalAsistenciaLabel">
                    <i class="fas fa-calendar-check me-2"></i>Registro de Prácticas Profesionales
                </h6>
                <button type="button" class="btn-close pp-modal-header" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <!-- cuerpo -->
            <div class="modal-body px-4">
                <!-- TÍTULO + HORAS EN UNA LÍNEA -->
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h6 class="fw-semibold mb-0">Control de Horas Diarias</h6>
                    <p class="small text-primary text-decoration-underline mb-0" id="horasTrabajadas">Horas trabajadas: — </p>
                </div>

                <!-- FECHA -->
                <div class="row">
                    <div class="mb-3">
                        <label for="fechaAsistencia" class="form-label small fw-semibold">Fecha</label>
                        <input type="date" class="form-control" id="fechaAsistencia" name="fechaAsistencia" required>
                    </div>

                    <!-- HORAS -->
                    <div class="col-6 mb-3">
                        <label for="horaEntrada" class="form-label small fw-semibold">Hora de Ingreso</label>
                        <div class="input-group">
                            <input type="time" class="form-control" id="horaEntrada" name="horaEntrada" required>
                        </div>
                    </div>

                    <div class="col-6 mb-3">
                        <label for="horaSalida" class="form-label small fw-semibold">Hora de Salida</label>
                        <div class="input-group">
                            <input type="time" class="form-control" id="horaSalida" name="horaSalida" required disabled>
                        </div>
                    </div>

                    <!-- Actividad realizada -->
                    <div class="col-12 mb-3">
                        <label for="actividadRealizada" class="form-label small fw-semibold">Actividad Realizada</label>
                        <textarea class="form-control" id="actividadRealizada" name="actividadRealizada" rows="3" required></textarea>
                    </div>
                </div>
            </div><!-- /modal-body -->

            <!-- footer -->
            <div class="modal-footer bg-white border-0 py-3 d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Registrar Asistencia</button>
            </div>
        </form>
    </div>
</div>


<!-- Modal modalReporteParcial -->
<div class="modal fade" id="modalReporteParcial" tabindex="-1" aria-labelledby="modalReporteParcialLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form id="formReporteParcial" class="modal-content">
            <!-- header gradient -->
            <div class="modal-header pp-modal-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold text-white" id="modalReporteParcialLabel">
                    <i class="fas fa-file-upload me-2"></i>Entregar Reporte Parcial
                </h6>
                <button type="button" class="btn-close pp-modal-header" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <!-- cuerpo -->
            <div class="modal-body px-4">
                <div class="mb-4 mt-2">
                    <h6 class="fw-semibold mb-0">Reporte Parcial de Prácticas</h6>
                </div>
                <div class="row">
                    <div class="col-12 mb-3">
                        <label for="objetivoGeneral" class="form-label small fw-semibold">Objetivo general del programa</label>
                        <textarea class="form-control" id="objetivoGeneral" name="objetivoGeneral" rows="3" required></textarea>
                    </div>
                    <div class="col-12 mb-3">
                        <label for="actividadesReportadas" class="form-label small fw-semibold">Actividades reportadas del periodo y su avance en relación con las metas</label>
                        <textarea class="form-control" id="actividadesReportadas" name="actividadesReportadas" rows="4" required></textarea>
                    </div>
                </div>
            </div><!-- /modal-body -->
            
            <!-- footer -->
            <div class="modal-footer bg-white border-0 py-3 d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Entregar Reporte</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal modalReporteFinal -->
<div class="modal fade" id="modalReporteFinal" tabindex="-1" aria-labelledby="modalReporteFinalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form id="formReporteFinal" class="modal-content">
            <!-- header gradient -->
            <div class="modal-header pp-modal-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold text-white" id="modalReporteFinalLabel">
                    <i class="fas fa-file-alt me-2"></i>Entregar Reporte Final de Prácticas
                </h6>
                <button type="button" class="btn-close pp-modal-header" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <!-- cuerpo -->
            <div class="modal-body px-4">
                <div class="mb-4 mt-2">
                    <h6 class="fw-semibold mb-0">Reporte Final de Prácticas</h6>
                </div>
                <div class="row">
                    <div class="col-12 mb-3">
                        <label for="objetivoGeneralFinal" class="form-label small fw-semibold">Objetivo general del programa</label>
                        <textarea class="form-control" id="objetivoGeneralFinal" name="objetivoGeneralFinal" rows="3" required></textarea>
                    </div>
                    <div class="col-12 mb-3">
                        <label for="actividadesRealizadasFinal" class="form-label small fw-semibold">Actividades realizadas</label>
                        <textarea class="form-control" id="actividadesRealizadasFinal" name="actividadesRealizadasFinal" rows="3" required></textarea>
                    </div>
                    <div class="col-12 mb-3">
                        <label for="resultadosObtenidosFinal" class="form-label small fw-semibold">Resultados obtenidos</label>
                        <textarea class="form-control" id="resultadosObtenidosFinal" name="resultadosObtenidosFinal" rows="3" required></textarea>
                    </div>
                    <div class="col-12 mb-3">
                        <label for="capacitacionRecibidaFinal" class="form-label small fw-semibold">Capacitación recibida</label>
                        <textarea class="form-control" id="capacitacionRecibidaFinal" name="capacitacionRecibidaFinal" rows="3" required></textarea>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label small fw-semibold">Experiencia profesional</label>
                        <textarea class="form-control" id="experienciaProfesionalFinal" name="experienciaProfesionalFinal" rows="2" required></textarea>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label small fw-semibold">Experiencia personal</label>
                        <textarea class="form-control" id="experienciaPersonalFinal" name="experienciaPersonalFinal" rows="2" required></textarea>
                    </div>
                </div>
            </div><!-- /modal-body -->
            
            <!-- footer -->
            <div class="modal-footer bg-white border-0 py-3 d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Entregar Reporte Final</button>
            </div>
        </form>
    </div>
</div>

<!-- ---------- ESTILOS EXTRAS ---------- -->
<style>
    /* Modales Globales UI 2026 */
    .modal-content {
        border: none;
        border-radius: 1.5rem;
        box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        overflow: hidden;
    }
    .modal-content .form-control {
        background: #f8fafc;
        border: 2px solid transparent;
        border-radius: 0.75rem;
        padding: 0.75rem 1rem;
        transition: all 0.2s;
    }
    .modal-content .form-control:focus {
        background: #fff;
        border-color: #c6db53;
        box-shadow: 0 0 0 4px rgba(198,219,83,0.15);
        outline: none;
    }
    .modal-content label.form-label {
        font-weight: 700;
        color: #00204a;
    }
    
    /* Botones dentro de modales */
    .modal-content .btn-primary {
        background: #01643D;
        border: none;
        border-radius: 100px;
        padding: 0.6rem 1.5rem;
        font-weight: 600;
        transition: all 0.2s;
    }
    .modal-content .btn-primary:hover {
        background: #004d2e;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(1,100,61,0.2);
    }
    .modal-content .btn-outline-secondary, .modal-content .btn-outline-danger {
        border-radius: 100px;
        padding: 0.6rem 1.5rem;
        font-weight: 600;
    }

    /* redondeo + efecto “pill” más suave que Badge default */
    .chip {
        background: var(--bs-light);
        border: 1px solid var(--bs-border-color);
        border-radius: 50rem;
        padding: 0.35rem 0.75rem;
        font-size: 0.85rem;
        font-weight: 500;
        white-space: nowrap;
    }

    .attendance-card {
        border: none;
        border-left: 4px solid var(--brand-main);
        border-radius: .75rem;
        background: #ffffff;
        transition: transform .2s, box-shadow .2s;
    }

    .attendance-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15);
    }

    .attendance-time-badge {
        font-size: 1rem;
    }
</style>
<?php include 'view/pages/practicas/modalEvaluacionIntegral.php'; ?>
<script type="module" src="view/assets/js/student/main.js?v=20260706"></script>