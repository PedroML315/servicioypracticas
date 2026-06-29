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
    min-height: 100vh;
    padding: 2rem;
    font-family: 'Outfit', sans-serif;
  }

  /* BENTO GRID LAYOUT */
  .bento-grid {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    grid-auto-rows: minmax(140px, auto);
    gap: 1.5rem;
    margin-bottom: 2rem;
  }

  .bento-card {
    background: var(--bento-bg);
    backdrop-filter: var(--bento-blur);
    -webkit-backdrop-filter: var(--bento-blur);
    border: var(--bento-border);
    border-radius: 2rem;
    padding: 1.5rem;
    box-shadow: var(--bento-shadow);
    transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.4s ease;
    overflow: hidden;
    position: relative;
    display: flex;
    flex-direction: column;
  }
  .bento-card:hover {
    transform: translateY(-4px) scale(1.01);
    box-shadow: 0 24px 50px -12px rgba(1, 100, 61, 0.15);
  }

  /* Grid Area Assignments */
  .bento-hero { 
    grid-column: span 12; 
    grid-row: span 1; 
    display: flex; 
    flex-direction: row; 
    align-items: center;
    justify-content: space-between; 
    background: linear-gradient(135deg, #01643D, #00204a); 
    color: white; 
    border: none; 
    padding: 2rem 3rem;
  }
  .bento-kpi { grid-column: span 3; grid-row: span 1; justify-content: center; align-items: center; text-align: center; }

  @media (max-width: 1200px) {
    .bento-kpi { grid-column: span 6; }
  }
  @media (max-width: 768px) {
    .bento-kpi { grid-column: span 6; }
    .ui-2026 { padding: 1rem; }
    .bento-hero { flex-direction: column; text-align: center; padding: 2rem 1.5rem; }
  }

  /* HERO CONTENT */
  .hero-blob {
    position: absolute; width: 300px; height: 300px; background: var(--brand-accent); filter: blur(80px); opacity: 0.3; border-radius: 50%; top: -50px; right: -50px; pointer-events: none;
  }
  .hero-title { font-size: 2.2rem; font-weight: 900; letter-spacing: -0.03em; margin-bottom: 0.5rem; position: relative; z-index: 2; }
  .hero-subtitle { font-size: 1.1rem; font-weight: 300; opacity: 0.9; margin-bottom: 0; position: relative; z-index: 2; }
  
  /* KPIs */
  .kpi-title { font-size: 0.9rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-secondary); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem; }
  .kpi-value { font-size: 3.5rem; font-weight: 900; line-height: 1; color: var(--text-primary); letter-spacing: -0.04em; }

  /* TAB NAVIGATION 2026 */
  .neo-tabs-nav {
    display: flex; gap: 0.5rem;
    background: rgba(255,255,255,0.5); backdrop-filter: blur(12px);
    padding: 0.5rem; border-radius: 100px;
    width: fit-content; margin: 0 auto 2rem auto;
    border: 1px solid rgba(255,255,255,0.8);
    box-shadow: 0 4px 20px -5px rgba(0,0,0,0.05);
    flex-wrap: wrap;
    justify-content: center;
  }
  .neo-tab-btn {
    border: none; background: transparent;
    padding: 0.75rem 1.5rem; border-radius: 100px;
    font-weight: 700; font-size: 1rem; color: var(--text-secondary);
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
    cursor: pointer;
  }
  .neo-tab-btn.active {
    background: white; color: var(--brand-main);
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
  }
  .neo-tab-btn:hover:not(.active) { color: var(--text-primary); }

  /* TAB PANES */
  .neo-tab-pane { display: none; animation: neoFadeIn 0.4s ease forwards; }
  .neo-tab-pane.active { display: block; }
  @keyframes neoFadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

  .pane-card {
    background: var(--bento-bg); backdrop-filter: var(--bento-blur); -webkit-backdrop-filter: var(--bento-blur);
    border: var(--bento-border); border-radius: 2rem; padding: 2rem;
    box-shadow: var(--bento-shadow); margin-bottom: 2rem;
  }
  .pane-header { margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; }
  .pane-title { font-size: 1.5rem; font-weight: 800; letter-spacing: -0.02em; margin: 0; color: var(--brand-dark); display: flex; align-items: center; gap: 0.75rem; }
  .pane-desc { font-size: 1rem; color: var(--text-secondary); margin-top: 0.25rem; }

  /* LIST CONTENEDORES Y EMPTY STATES */
  .dash-list-container {
    max-height: 400px; overflow-y: auto; padding-right: 0.5rem;
  }
  .empty-state {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    padding: 3rem 1rem; color: #94a3b8; text-align: center; height: 100%; gap: 0.5rem;
  }
  .empty-state i { font-size: 2.5rem; opacity: 0.5; margin-bottom: 0.5rem; }
  .empty-state span { font-size: 1rem; font-weight: 500; }

  /* Tarjetas de elementos dentro de las listas (preservar los estilos anteriores aprox) */
  .org-card, .student-card, .report-card { border:1px solid #e2e8f0; border-radius:1rem; padding: 1rem; margin-bottom: 1rem; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
  .org-header h5 { margin:0; font-weight: bold; }
  .org-meta .badge { font-weight:500; font-size: 0.85rem; }
  .org-dl dt { color:#6c757d; font-size: 0.85rem; margin-top: 0.5rem; }
  .org-dl dd { margin-bottom:.5rem; font-size: 0.95rem; }
  .org-files .list-group-item { background:transparent; border:0; padding:.25rem 0; }
  .org-files a { display:inline-block; max-width:100%; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  @media (min-width:768px) { .org-right { border-left:1px solid #f1f3f5; } }
  .org-actividades { max-height:96px; overflow:hidden; }
</style>

<div class="ui-2026">

  <!-- BENTO GRID (HERO & KPIs) -->
  <div class="bento-grid">
    
    <!-- Hero Box -->
    <div class="bento-card bento-hero">
        <div class="hero-blob"></div>
        <div style="z-index:2;">
            <h1 class="hero-title"><i class="fa-solid fa-shield-halved me-3"></i>Centro de Administración</h1>
            <p class="hero-subtitle">Gestión centralizada de solicitudes, organismos y alumnos de Prácticas y Servicio Social.</p>
        </div>
        <div style="z-index:2; background: rgba(255,255,255,0.1); padding: 1rem 1.5rem; border-radius: 1rem; border: 1px solid rgba(255,255,255,0.2);" class="d-none d-md-block">
            <div class="fw-bold fs-5"><i class="fa-regular fa-calendar text-white me-2"></i><?= date('d M, Y') ?></div>
        </div>
    </div>

    <!-- KPIs -->
    <div class="bento-card bento-kpi">
      <div class="kpi-title"><i class="fas fa-building text-primary"></i> Org. Nuevos</div>
      <div class="kpi-value" id="kpi-organismos">0</div>
    </div>
    <div class="bento-card bento-kpi">
      <div class="kpi-title"><i class="fas fa-users text-success"></i> Alumnos</div>
      <div class="kpi-value" id="kpi-alumnos">0</div>
    </div>
    <div class="bento-card bento-kpi">
      <div class="kpi-title"><i class="fas fa-file-signature text-warning"></i> Solicitudes</div>
      <div class="kpi-value" id="kpi-solicitudes">0</div>
    </div>
    <div class="bento-card bento-kpi">
      <div class="kpi-title"><i class="fas fa-file-lines text-danger"></i> Reportes</div>
      <div class="kpi-value" id="kpi-reportes">0</div>
    </div>
  </div>

  <!-- NEO TABS NAVIGATION -->
  <div class="neo-tabs-nav">
    <button class="neo-tab-btn active" data-target="tab-practicas"><i class="fas fa-briefcase me-2"></i>Prácticas Profesionales</button>
    <button class="neo-tab-btn" data-target="tab-servicio"><i class="fas fa-hands-helping me-2"></i>Servicio Social</button>
    <button class="neo-tab-btn" data-target="tab-capacitaciones"><i class="fas fa-chalkboard-teacher me-2"></i>Capacitaciones</button>
  </div>

  <!-- TABS CONTENT -->
  <div class="neo-tabs-content">
    
    <!-- ==========================================
         TAB 1: PRÁCTICAS PROFESIONALES
    =========================================== -->
    <div class="neo-tab-pane active" id="tab-practicas">

      <!-- Advertencia: organismos aceptados sin convenio validado -->
      <div id="convenioFaltanteAlert" class="mb-4"></div>

      <div class="row g-4">
        <!-- Organismos Externos -->
        <div class="col-12 col-xl-6">
          <div class="pane-card h-100" style="border-top: 4px solid #7c3aed;">
            <div class="pane-header">
              <div>
                <h2 class="pane-title"><i class="fa-solid fa-building text-primary"></i> Organismos Externos Nuevos</h2>
                <p class="pane-desc">Empresas o instituciones que solicitan registro</p>
              </div>
              <a href="internship_companies" class="btn btn-outline-primary rounded-pill btn-sm fw-bold">Ver todo</a>
            </div>
            <div class="dash-list-container list-organism-external" id="kpi-source-organismos">
                <div class="empty-state"><i class="fa-regular fa-circle-check"></i><span>Sin organismos pendientes</span></div>
            </div>
          </div>
        </div>

        <!-- Alumnos Prácticas -->
        <div class="col-12 col-xl-6">
          <div class="pane-card h-100" style="border-top: 4px solid #0d9488;">
            <div class="pane-header">
              <div>
                <h2 class="pane-title"><i class="fa-solid fa-user-graduate text-success"></i> Alumnos - Prácticas</h2>
                <p class="pane-desc">Alumnos nuevos que esperan ser aprobados</p>
              </div>
              <a href="internship_students" class="btn btn-outline-success rounded-pill btn-sm fw-bold">Ver todo</a>
            </div>
            <div class="dash-list-container list-student-practice-professional" id="kpi-source-alumnos-prac">
                <div class="empty-state"><i class="fa-regular fa-circle-check"></i><span>Sin alumnos pendientes</span></div>
            </div>
          </div>
        </div>

        <!-- Solicitudes de Practicantes -->
        <div class="col-12 col-xl-6">
          <div class="pane-card h-100" style="border-top: 4px solid #d97706;">
            <div class="pane-header">
              <div>
                <h2 class="pane-title"><i class="fa-solid fa-file-signature text-warning"></i> Solicitudes de Practicantes</h2>
                <p class="pane-desc">Solicitudes enviadas por empresas para recibir alumnos</p>
              </div>
              <button type="button" id="btnVerVacantesActivas" class="btn btn-outline-warning rounded-pill btn-sm fw-bold">
                <i class="fas fa-list-ul me-1"></i> Ver vacantes activas
              </button>
            </div>
            <div class="dash-list-container list-request-practice-professional" id="kpi-source-sol-prac">
                <div class="empty-state"><i class="fa-regular fa-circle-check"></i><span>Sin solicitudes pendientes</span></div>
            </div>
          </div>
        </div>

        <!-- Reportes de Prácticas -->
        <div class="col-12 col-xl-6">
          <div class="pane-card h-100" style="border-top: 4px solid #e11d48;">
            <div class="pane-header">
              <div>
                <h2 class="pane-title"><i class="fa-solid fa-file-lines text-danger"></i> Reportes de Prácticas</h2>
                <p class="pane-desc">Reportes parciales y finales en espera de revisión</p>
              </div>
            </div>
            <div class="dash-list-container list-reports-practices" id="kpi-source-reportes-prac">
                <div class="empty-state"><i class="fa-regular fa-circle-check"></i><span>Sin reportes pendientes</span></div>
            </div>
          </div>
        </div>

        <!-- Postulaciones a Áreas Internas -->
        <div class="col-12">
          <div class="pane-card" style="border-top: 4px solid #4f46e5;">
            <div class="pane-header">
              <div>
                <h2 class="pane-title"><i class="fa-solid fa-building-columns text-indigo"></i> Postulaciones a Áreas Internas</h2>
                <p class="pane-desc">Alumnos que se postulan a áreas dentro de la institución</p>
              </div>
              <a href="internship_areas" class="btn btn-outline-primary rounded-pill btn-sm fw-bold">Ver todo</a>
            </div>
            <div class="dash-list-container list-postulaciones-areas">
                <div class="empty-state"><i class="fa-regular fa-circle-check"></i><span>Sin postulaciones pendientes</span></div>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- ==========================================
         TAB 2: SERVICIO SOCIAL
    =========================================== -->
    <div class="neo-tab-pane" id="tab-servicio">
      
      <div class="row g-4">
        <!-- Alumnos Servicio Social -->
        <div class="col-12 col-xl-6">
          <div class="pane-card h-100" style="border-top: 4px solid #2563eb;">
            <div class="pane-header">
              <div>
                <h2 class="pane-title"><i class="fa-solid fa-user-graduate text-primary"></i> Alumnos - Servicio Social</h2>
                <p class="pane-desc">Alumnos nuevos que esperan ser aprobados</p>
              </div>
              <a href="students" class="btn btn-outline-primary rounded-pill btn-sm fw-bold">Ver todo</a>
            </div>
            <div class="dash-list-container list-student-service-external" id="kpi-source-alumnos-ser">
                <div class="empty-state"><i class="fa-regular fa-circle-check"></i><span>Sin alumnos pendientes</span></div>
            </div>
          </div>
        </div>

        <!-- Solicitudes IJUMICH -->
        <div class="col-12 col-xl-6">
          <div class="pane-card h-100" style="border-top: 4px solid #16a34a;">
            <div class="pane-header">
              <div>
                <h2 class="pane-title"><i class="fa-solid fa-file-circle-check text-success"></i> Solicitudes IJUMICH</h2>
                <p class="pane-desc">Cartas de presentación y liberación enviadas por alumnos</p>
              </div>
            </div>
            <div class="dash-list-container list-ijumich-requests" style="max-height: 500px;" id="kpi-source-ijumich">
                <div class="empty-state"><i class="fa-regular fa-circle-check"></i><span>Sin solicitudes pendientes</span></div>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- ==========================================
         TAB 3: CAPACITACIONES
    =========================================== -->
    <div class="neo-tab-pane" id="tab-capacitaciones">
      
      <div class="row g-4">
        <!-- Solicitudes de Capacitación -->
        <div class="col-12">
          <div class="pane-card" style="border-top: 4px solid #059669;">
            <div class="pane-header">
              <div>
                <h2 class="pane-title"><i class="fa-solid fa-chalkboard-user text-success"></i> Solicitudes de Capacitaciones</h2>
                <p class="pane-desc">Alumnos que solicitan una capacitación especial</p>
              </div>
            </div>
            <div class="dash-list-container list-solicitudes-capacitacion" id="kpi-source-capacitacion">
                <div class="empty-state"><i class="fa-regular fa-circle-check"></i><span>Sin solicitudes pendientes</span></div>
            </div>
          </div>
        </div>
      </div>

    </div>

  </div> <!-- End Tabs Content -->
</div> <!-- End UI 2026 -->

<!-- Lógica de Pestañas y KPIs -->
<script>
  document.addEventListener("DOMContentLoaded", function() {
    // Manejo de Neo Tabs
    const tabBtns = document.querySelectorAll('.neo-tab-btn');
    const tabPanes = document.querySelectorAll('.neo-tab-pane');

    tabBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        tabBtns.forEach(b => b.classList.remove('active'));
        tabPanes.forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById(btn.getAttribute('data-target')).classList.add('active');
      });
    });

    // Observador para actualizar KPIs automáticamente cuando cambian los contenedores
    function updateKPIs() {
      // Organismos
      const orgsCount = $('#kpi-source-organismos').children('.org-card').length;
      $('#kpi-organismos').text(orgsCount);
      
      // Alumnos (Prácticas + Servicio)
      const alumnosPrac = $('#kpi-source-alumnos-prac').children('.student-card, .card').length;
      const alumnosSer = $('#kpi-source-alumnos-ser').children('.student-card, .card').length;
      $('#kpi-alumnos').text(alumnosPrac + alumnosSer);
      
      // Solicitudes (Prácticas + Capacitación + IJUMICH)
      const solPrac = $('#kpi-source-sol-prac').children('.request-card, .card').length;
      const solCap = $('#kpi-source-capacitacion').children('.request-card, .card').length;
      const solIju = $('#kpi-source-ijumich').children('.card').length;
      $('#kpi-solicitudes').text(solPrac + solCap + solIju);
      
      // Reportes
      const reportesCount = $('#kpi-source-reportes-prac').children('.report-card, .card').length;
      $('#kpi-reportes').text(reportesCount);
    }

    const observer = new MutationObserver(updateKPIs);
    const config = { childList: true, subtree: true };
    
    document.querySelectorAll('.dash-list-container').forEach(container => {
        observer.observe(container, config);
    });
  });
</script>

<!-- ════ MODAL RECHAZO DETALLADO ════ -->
<div class="modal fade" id="icRechazoModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content border-0 rounded-4 shadow">
      <div class="modal-header border-0 pb-2" style="background:linear-gradient(135deg,#dc3545 0%,#991b1b 100%);border-radius:1rem 1rem 0 0;">
        <div class="d-flex align-items-center gap-3">
          <div style="width:46px;height:46px;border-radius:.6rem;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:#fff;">
            <i class="fas fa-times-circle"></i>
          </div>
          <div>
            <h5 class="modal-title fw-bold text-white mb-0">Rechazar Solicitud de Organismo</h5>
            <small class="text-white opacity-75" id="icRechazoModalSub"></small>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4 bg-light">
        <form id="formRechazoOrganismo">
            <input type="hidden" id="rechazoOrgId" name="id">
            
            <div class="alert alert-warning border-0 shadow-sm" role="alert">
                <i class="fas fa-info-circle me-2"></i> Marca los campos o documentos que sean incorrectos y detalla el motivo. Se enviará un correo al organismo con un enlace temporal para que corrija su información.
            </div>

            <!-- Motivo General -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="fas fa-comment-alt text-danger me-2"></i>Motivo General (Obligatorio)</h6>
                    <textarea class="form-control" name="motivo_general" id="rechazoMotivoGeneral" rows="3" placeholder="Ej. Tu solicitud requiere correcciones en los documentos adjuntos y en la dirección..." required></textarea>
                </div>
            </div>

            <!-- Tabla de campos -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle" id="tablaCamposRechazo">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40px;" class="text-center"><i class="fas fa-check-square text-muted"></i></th>
                                    <th style="width: 20%;">Campo / Documento</th>
                                    <th style="width: 30%;">Valor actual</th>
                                    <th>Motivo del error y observaciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Llenado por JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </form>
      </div>
      <div class="modal-footer border-0 pt-3">
        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger rounded-pill px-4" id="btnConfirmarRechazo">
            <i class="fas fa-paper-plane me-2"></i> Enviar Rechazo
        </button>
      </div>
    </div>
  </div>
</div>
<!-- Modal Visor de PDF -->
<div class="modal fade" id="pdfViewerModal" tabindex="-1" aria-labelledby="pdfViewerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content shadow-lg" style="border-radius: 1rem; overflow: hidden; height: 90vh;">
      <div class="modal-header bg-dark text-white border-0 py-3">
        <h5 class="modal-title mb-0" id="pdfViewerModalLabel"><i class="fas fa-file-pdf text-danger me-2"></i>Visor de Documento</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0 bg-secondary position-relative">
        <!-- Loader mientras carga el iframe -->
        <div id="pdfLoader" class="position-absolute top-50 start-50 translate-middle text-white text-center" style="z-index: 1;">
           <i class="fas fa-circle-notch fa-spin fa-3x mb-2"></i>
           <p>Cargando documento...</p>
        </div>
        <iframe id="pdfIframe" src="" style="width:100%; height:100%; border:none; position: relative; z-index: 2;" onload="document.getElementById('pdfLoader').style.display='none';"></iframe>
      </div>
    </div>
  </div>
</div>

<!-- ==========================================
     MODAL: VACANTES ACTIVAS (Detalle completo)
     ========================================== -->
<style>
  #vacantesActivasModal .modal-content { border:none; border-radius:2rem; overflow:hidden; font-family:'Outfit',sans-serif; box-shadow:0 25px 60px -15px rgba(0,0,0,.3); }
  #vacantesActivasModal .va-modal-head { position:relative; border:none; padding:2rem 2.5rem; background:linear-gradient(135deg,#01643D,#00204a); color:#fff; overflow:hidden; }
  #vacantesActivasModal .va-blob { position:absolute; width:300px; height:300px; background:var(--brand-accent); filter:blur(85px); opacity:.3; border-radius:50%; top:-110px; right:-60px; pointer-events:none; }
  #vacantesActivasModal .va-head-icon { width:54px; height:54px; border-radius:1rem; background:rgba(255,255,255,.14); display:flex; align-items:center; justify-content:center; font-size:1.4rem; color:var(--brand-accent); flex-shrink:0; }
  #vacantesActivasModal .va-head-title { font-weight:900; font-size:1.6rem; letter-spacing:-.02em; margin:0; }
  #vacantesActivasModal .va-head-sub { font-size:.95rem; font-weight:300; opacity:.85; margin:.15rem 0 0; }
  #vacantesActivasModal .va-count-chip { z-index:2; background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.2); border-radius:1rem; padding:.6rem 1.25rem; text-align:center; min-width:90px; }
  #vacantesActivasModal .va-count-chip .n { font-size:1.7rem; font-weight:900; line-height:1; }
  #vacantesActivasModal .va-count-chip .l { font-size:.68rem; text-transform:uppercase; letter-spacing:.06em; opacity:.8; margin-top:.15rem; }
  #vacantesActivasModal .modal-body { background:var(--bg-page); padding:1.75rem; max-height:74vh; }

  .va-card { background:#fff; border:1px solid rgba(0,0,0,.05); border-radius:1.5rem; box-shadow:0 10px 30px -14px rgba(0,0,0,.1); overflow:hidden; margin-bottom:1.25rem; transition:box-shadow .3s, transform .3s; }
  .va-card:hover { box-shadow:0 20px 45px -16px rgba(1,100,61,.22); transform:translateY(-2px); }
  .va-card-head { display:flex; align-items:center; gap:.85rem; padding:1.15rem 1.5rem; border-bottom:1px solid #f1f5f9; background:rgba(248,250,252,.6); }
  .va-card-avatar { width:46px; height:46px; border-radius:14px; background:rgba(1,100,61,.1); color:var(--brand-main); display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0; }
  .va-company { font-weight:800; font-size:1.15rem; color:var(--brand-dark); letter-spacing:-.01em; }
  .va-id { font-size:.74rem; color:#94a3b8; font-weight:600; }
  .va-pill { font-size:.72rem; font-weight:700; border-radius:100px; padding:.35rem .8rem; display:inline-flex; align-items:center; white-space:nowrap; }
  .va-tag { font-size:.75rem; font-weight:700; border-radius:9px; padding:.4rem .7rem; display:inline-flex; align-items:center; }
  .va-card-body { padding:1.5rem; }
  .va-stat-label { font-size:.68rem; color:#94a3b8; text-transform:uppercase; letter-spacing:.05em; font-weight:700; margin-bottom:.15rem; }
  .va-stat-value { font-size:.9rem; font-weight:700; color:var(--text-primary); }
  .va-section { background:#f8fafc; border:1px solid #eef2f6; border-radius:1rem; padding:1rem 1.15rem; height:100%; }
  .va-section-title { font-size:.72rem; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:var(--brand-main); margin-bottom:.45rem; display:flex; align-items:center; gap:.45rem; }
  .va-section-text { font-size:.88rem; color:#334155; line-height:1.55; white-space:pre-line; margin:0; }
  .va-contact { background:linear-gradient(135deg, rgba(1,100,61,.06), rgba(0,32,74,.05)); border:1px solid rgba(1,100,61,.12); border-radius:1rem; padding:1rem 1.15rem; }
</style>
<div class="modal fade" id="vacantesActivasModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header va-modal-head">
        <div class="va-blob"></div>
        <button type="button" class="btn-close btn-close-white position-absolute" style="top:1.5rem; right:1.5rem; z-index:3;" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        <div class="d-flex align-items-center gap-3" style="z-index:2;">
          <div class="va-head-icon"><i class="fas fa-briefcase"></i></div>
          <div>
            <h5 class="va-head-title">Vacantes de Practicantes</h5>
            <p class="va-head-sub">Detalle completo de todas las vacantes activas</p>
          </div>
        </div>
        <div class="va-count-chip d-none d-md-block">
          <div class="n" id="va-count">0</div>
          <div class="l">Activas</div>
        </div>
      </div>
      <div class="modal-body" id="vacantesActivasBody" style="min-height:240px;">
        <div class="text-center py-5"><i class="fas fa-spinner fa-spin" style="color:var(--brand-main);font-size:1.8rem;"></i></div>
      </div>
    </div>
  </div>
</div>

<script>
  // Función global para abrir el visor de PDF
  window.openPdfViewer = function(url) {
    document.getElementById('pdfLoader').style.display = 'block';
    document.getElementById('pdfIframe').src = url;
    var pdfModal = new bootstrap.Modal(document.getElementById('pdfViewerModal'));
    pdfModal.show();
    return false;
  };

  // Limpiar iframe al cerrar para detener descargas o audio en background
  document.getElementById('pdfViewerModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('pdfIframe').src = '';
  });

  $(function () {
    // ============================
    // Helpers seguros / utilidades
    // ============================
    function safeTxt(v, fb = '—') {
      const s = (v ?? '').toString().trim();
      return s || fb;
    }
    function safeTime5(v) {
      const s = (v ?? '').toString();
      return s ? s.slice(0, 5) : '—';
    }
    function safeDateYMD(d) {
      if (!d) return '—';
      const dt = new Date(String(d).replace(' ', 'T'));
      return isNaN(dt) ? '—' : dt.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }
    function escapeHtml(str) {
      return String(str ?? '')
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
    function joinPipe(items) {
      return items.map(x => (x ?? '').toString().trim()).filter(Boolean).join(' | ');
    }

    // =====================================
    // Cargadores genéricos (AJAX -> render)
    // =====================================
    function normalizeArrayResponse(data) {
      if (Array.isArray(data)) return data;
      if (data && Array.isArray(data.data)) return data.data;
      if (data && typeof data === 'object') return [data];
      return [];
    }

    function loadList(selector, requestData, renderFn, noDataText) {
      const $container = $(selector);
      $.ajax({
        url: 'controller/ajax/ajax.forms.php',
        method: 'POST',
        data: requestData,
        dataType: 'json',
      })
        .done(function (data) {
          const arr = normalizeArrayResponse(data);
          if (arr.length) {
            $container.html(arr.map(renderFn).join(''));
          } else {
            $container.html(`
              <div class="d-flex align-items-center justify-content-center" style="height: 100%;">
                <span class="text-gray-600">${noDataText}</span>
              </div>
            `);
          }
        })
        .fail(function () {
          $container.html(`
            <div class="text-danger text-center p-3">Error al cargar datos.</div>
          `);
        });
    }

    function LoadListReports(selector, requestData, renderFn, noDataText) {
      const $container = $(selector);
      $.ajax({
        url: 'controller/ajax/ajax.forms.php',
        method: 'POST',
        data: requestData,
        dataType: 'json',
      })
        .done(function (data) {
          const parciales = Array.isArray(data?.parciales) ? data.parciales : [];
          const finales = Array.isArray(data?.finales) ? data.finales : [];
          let html = '';
          if (parciales.length) html += parciales.map(r => renderReportPractices(r, 'parcial')).join('');
          if (finales.length) html += finales.map(r => renderReportPractices(r, 'final')).join('');
          if (!html) {
            html = `
              <div class="d-flex align-items-center justify-content-center" style="height: 100%;">
                <span class="text-gray-600">${noDataText}</span>
              </div>
            `;
          }
          $container.html(html);
        })
        .fail(function () {
          $container.html(`
            <div class="text-danger text-center p-3">Error al cargar datos.</div>
          `);
        });
    }

    // =====================================
    // Renders
    // =====================================

    // Alumnos (servicio / prácticas) – conserva tu lógica
    function renderServiceStudent(student, type) {
      let fullname = '';
      let identifier = '';
      if (!student.lastnameMom) {
        fullname = student.nombre_completo;
        identifier = student.id;
      } else {
        fullname = `${student.firstname} ${student.lastname} ${student.lastnameMom}`;
        identifier = student.idStudent;
      }
      return `
        <div class="card shadow-sm border-0 mb-3" style="border-radius: 1rem;">
          <div class="card-body p-3 d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
              <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 45px; height: 45px; font-size: 1.2rem;">
                ${escapeHtml(fullname ? fullname.charAt(0).toUpperCase() : '?')}
              </div>
              <div>
                <h6 class="mb-0 fw-bold text-dark" style="font-size: 1.05rem;">${escapeHtml(fullname || '—')}</h6>
                <div class="small text-muted mt-1 d-flex flex-wrap gap-2">
                  ${student.matricula ? `<span><i class="fas fa-id-card me-1 text-secondary"></i>${escapeHtml(student.matricula)}</span>` : ''}
                  ${student.email ? `<span><i class="fas fa-envelope me-1 text-secondary"></i>${escapeHtml(student.email)}</span>` : ''}
                </div>
              </div>
            </div>
            <div class="d-flex gap-2">
              <button class="btn btn-sm px-3 fw-bold rounded-pill shadow-sm btn-accept-${type}" data-id="${escapeHtml(identifier || '')}" style="background: #10b981; color: white; border: none;">
                <i class="fas fa-check me-1"></i> Aceptar
              </button>
              <button class="btn btn-sm px-3 fw-bold rounded-pill shadow-sm btn-reject-${type}" data-id="${escapeHtml(identifier || '')}" style="background: #ef4444; color: white; border: none;">
                <i class="fas fa-times me-1"></i> Rechazar
              </button>
            </div>
          </div>
        </div>
      `;
    }
    renderServiceStudent.noDataText = 'No hay nuevos alumnos';

    // Solicitudes de practicantes (tienen hora_inicio/hora_fin/días/etc.)
    function renderSolicitudPracticante(sol) {
      const fechaLimite = safeDateYMD(sol.fecha_limite);
      const horaIni = safeTime5(sol.hora_inicio);
      const horaFin = safeTime5(sol.hora_fin);
      return `
        <div class="card shadow-sm border-0 mb-3" style="border-radius: 1rem;">
          <div class="card-body p-3 p-md-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
              <div style="flex:1; min-width:280px;">
                <h6 class="mb-2 fw-bold text-dark d-flex align-items-center gap-2" style="font-size: 1.1rem;">
                  <i class="fas fa-briefcase text-primary"></i>
                  ${escapeHtml(safeTxt(sol.empresa))}
                </h6>
                <div class="d-flex flex-wrap gap-2 mb-2">
                  <span class="badge" style="background:#eef2ff; color:#4f46e5; border-radius:8px; padding: 0.4rem 0.6rem;">${escapeHtml(safeTxt(sol.licenciatura))}</span>
                  <span class="badge" style="background:#f1f5f9; color:#64748b; border-radius:8px; padding: 0.4rem 0.6rem;">${escapeHtml(safeTxt(sol.num_practicantes, '0'))} vacantes</span>
                  <span class="badge" style="background:#fef3c7; color:#d97706; border-radius:8px; padding: 0.4rem 0.6rem;">Modalidad: ${escapeHtml(safeTxt(sol.modalidad))}</span>
                </div>
                
                <div class="d-flex flex-column gap-1 text-secondary mt-3" style="font-size: 0.9rem;">
                  <div class="d-flex align-items-start gap-2">
                    <i class="far fa-calendar-alt mt-1 text-muted" style="min-width: 14px;"></i>
                    <span><strong>Periodo:</strong> ${escapeHtml(safeTxt(sol.dia_inicio))} a ${escapeHtml(safeTxt(sol.dia_fin))} (${escapeHtml(horaIni)}–${escapeHtml(horaFin)})</span>
                  </div>
                  <div class="d-flex align-items-start gap-2">
                    <i class="fas fa-hourglass-half mt-1 text-warning" style="min-width: 14px;"></i>
                    <span><strong>Límite de inscripción:</strong> ${fechaLimite}</span>
                  </div>
                  <div class="d-flex align-items-start gap-2">
                    <i class="fas fa-map-marker-alt mt-1 text-info" style="min-width: 14px;"></i>
                    <span><strong>Ubicación:</strong> ${escapeHtml(safeTxt(sol.direccion_practica))}</span>
                  </div>
                  <div class="d-flex align-items-start gap-2">
                    <i class="fas fa-user mt-1 text-success" style="min-width: 14px;"></i>
                    <span><strong>Contacto:</strong> ${escapeHtml(safeTxt(sol.nombre_responsable))} (${escapeHtml(safeTxt(sol.telefono))})</span>
                  </div>
                </div>

                <div class="mt-3 p-2 rounded" style="background: #f8fafc; border: 1px dashed #cbd5e1;">
                  <strong class="small text-muted d-block mb-1">ACTIVIDADES</strong>
                  <div class="small text-dark">${escapeHtml(safeTxt(sol.actividades)).replace(/\r?\n/g,'<br>')}</div>
                </div>
              </div>
              
              <div class="d-flex flex-column gap-2" style="min-width: 140px;">
                <button class="btn btn-sm w-100 fw-bold rounded-pill shadow-sm btn-accept-request-practice" data-id="${escapeHtml(safeTxt(sol.idSolPracticantes))}" style="background: #10b981; color: white; border: none;">
                  <i class="fas fa-check me-1"></i> Aceptar
                </button>
                <button class="btn btn-sm w-100 fw-bold rounded-pill shadow-sm btn-reject-request-practice" data-id="${escapeHtml(safeTxt(sol.idSolPracticantes))}" style="background: #ef4444; color: white; border: none;">
                  <i class="fas fa-times me-1"></i> Rechazar
                </button>
              </div>
            </div>
          </div>
        </div>
      `;
    }
    // =====================================
    // Vacantes activas (detalle completo) — Panel administrador
    // =====================================
    const DIAS_LABEL = { L:'Lunes', M:'Martes', X:'Miércoles', J:'Jueves', V:'Viernes', S:'Sábado', D:'Domingo' };

    function vacanteSection(titulo, icono, valor) {
      return `
        <div class="col-md-6">
          <div class="va-section">
            <div class="va-section-title">${icono}${titulo}</div>
            <p class="va-section-text">${valor}</p>
          </div>
        </div>`;
    }

    function renderVacanteActivaCard(sol) {
      const id = escapeHtml(safeTxt(sol.idSolPracticantes ?? sol.id));
      const activa = String(sol.aceptado) === '1';
      const estado = activa
        ? '<span class="va-pill" style="background:#dcfce7;color:#166534;"><i class="fas fa-check-circle me-1"></i>Activa</span>'
        : '<span class="va-pill" style="background:#fef3c7;color:#92400e;"><i class="fas fa-clock me-1"></i>Pendiente</span>';
      const horario = `${DIAS_LABEL[sol.dia_inicio] ?? safeTxt(sol.dia_inicio)} a ${DIAS_LABEL[sol.dia_fin] ?? safeTxt(sol.dia_fin)}, ${safeTime5(sol.hora_inicio)}–${safeTime5(sol.hora_fin)}`;
      const apoyo = String(sol.ofrece_apoyo_economico) === '1'
        ? `Sí · ${escapeHtml(safeTxt(sol.monto_apoyo))}`
        : 'No';
      const postulados = parseInt(sol.total_postulados ?? 0, 10);
      const aceptados  = parseInt(sol.total_aceptados ?? 0, 10);

      return `
      <div class="va-card">
        <div class="va-card-head">
          <div class="va-card-avatar"><i class="fas fa-building"></i></div>
          <div style="flex:1;min-width:0;">
            <div class="va-company text-truncate">${escapeHtml(safeTxt(sol.empresa))}</div>
            <div class="va-id">ID #${id}</div>
          </div>
          ${estado}
        </div>
        <div class="va-card-body">
          <div class="d-flex flex-wrap gap-2 mb-3">
            <span class="va-tag" style="background:rgba(198,219,83,.3);color:#01643D;"><i class="fas fa-graduation-cap me-1"></i>${escapeHtml(safeTxt(sol.licenciatura))}</span>
            <span class="va-tag" style="background:#ede9fe;color:#5b21b6;"><i class="fas fa-laptop-house me-1"></i>${escapeHtml(safeTxt(sol.modalidad))}</span>
            <span class="va-tag" style="background:#eef2ff;color:#4f46e5;"><i class="fas fa-user-friends me-1"></i>${escapeHtml(safeTxt(sol.num_practicantes,'0'))} vacantes</span>
            <span class="va-tag" style="background:#dcfce7;color:#065f46;"><i class="fas fa-users me-1"></i>${postulados} postulados · ${aceptados} aceptados</span>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-6 col-md-3"><div class="va-stat-label"><i class="far fa-clock me-1"></i>Horario</div><div class="va-stat-value">${horario}</div></div>
            <div class="col-6 col-md-3"><div class="va-stat-label"><i class="fas fa-hourglass-half me-1"></i>Fecha límite</div><div class="va-stat-value">${safeDateYMD(sol.fecha_limite)}</div></div>
            <div class="col-6 col-md-3"><div class="va-stat-label"><i class="fas fa-hand-holding-usd me-1"></i>Apoyo econ.</div><div class="va-stat-value">${apoyo}</div></div>
            <div class="col-6 col-md-3"><div class="va-stat-label"><i class="fas fa-map-marker-alt me-1"></i>Ubicación</div><div class="va-stat-value">${escapeHtml(safeTxt(sol.direccion_practica))}</div></div>
          </div>

          <div class="va-contact mb-3">
            <div class="va-stat-label"><i class="fas fa-user-tie me-1"></i>Responsable / Contacto</div>
            <div class="va-stat-value">${escapeHtml(safeTxt(sol.nombre_responsable))} · ${escapeHtml(safeTxt(sol.telefono))}${sol.email_organismo ? ' · ' + escapeHtml(sol.email_organismo) : ''}</div>
          </div>

          <div class="row g-2">
            ${vacanteSection('Actividades formativas', '<i class="fas fa-clipboard-list"></i>', escapeHtml(safeTxt(sol.actividades)))}
            ${vacanteSection('Funciones', '<i class="fas fa-tasks"></i>', escapeHtml(safeTxt(sol.funciones)))}
            ${vacanteSection('Objetivos', '<i class="fas fa-bullseye"></i>', escapeHtml(safeTxt(sol.objetivos)))}
            ${vacanteSection('Competencias', '<i class="fas fa-medal"></i>', escapeHtml(safeTxt(sol.competencias)))}
            ${vacanteSection('Resultados esperados', '<i class="fas fa-flag-checkered"></i>', escapeHtml(safeTxt(sol.resultados_esperados)))}
            ${vacanteSection('Capacidades requeridas', '<i class="fas fa-star"></i>', escapeHtml(safeTxt(sol.capacidades)))}
          </div>
        </div>
      </div>`;
    }

    function loadVacantesActivas() {
      const $body = $('#vacantesActivasBody');
      $body.html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin" style="color:var(--brand-main);font-size:1.8rem;"></i></div>');
      $.ajax({
        url: 'controller/ajax/ajax.forms.php',
        method: 'POST',
        data: { search: 'practices', action: 'allSolicitudesPracticantes' },
        dataType: 'json',
      }).done(function (data) {
        const arr = normalizeArrayResponse(data);
        $('#va-count').text(arr.length);
        if (!arr.length) {
          $body.html('<div class="empty-state"><i class="fa-regular fa-folder-open"></i><span>No hay vacantes activas registradas.</span></div>');
          return;
        }
        $body.html(arr.map(renderVacanteActivaCard).join(''));
      }).fail(function () {
        $body.html('<div class="alert alert-danger">Error al cargar las vacantes activas.</div>');
      });
    }

    $(document).on('click', '#btnVerVacantesActivas', function () {
      bootstrap.Modal.getOrCreateInstance(document.getElementById('vacantesActivasModal')).show();
      loadVacantesActivas();
    });

// Toggle "Ver más / Ver menos" para Actividades
    window.toggleOrgAct = function(id){
        const box = document.getElementById(id);
        const link = document.getElementById(id+'-toggle');
        const expanded = box.getAttribute('data-expanded') === '1';
        if (expanded){
        box.style.maxHeight = '96px';
        link.textContent = 'Ver más';
        box.setAttribute('data-expanded','0');
        } else {
        box.style.maxHeight = 'none';
        link.textContent = 'Ver menos';
        box.setAttribute('data-expanded','1');
        }
        return false;
    };

    function renderOrganismoExterno(org) {
    const id = org.idOrganismo ?? org.id ?? '';
    const nombre = org.empresa || 'Organismo sin nombre';
    const giro = org.giro || '';
    const tipo = org.tipo_persona || '';
    const web  = (org.web || '').trim();
    const fechaConst = safeDateYMD(org.fecha_constitucion);
    const creado     = safeDateYMD(org.created_at);

    const direccion = joinPipe([org.calle, org.colonia, org.ciudad, org.cp ? `CP ${org.cp}` : '']);
    const contacto  = joinPipe([org.nombre_contacto, org.email, org.celular, org.telefonos, org.tel_oficina]);
    const legal     = joinPipe([org.rep_legal, org.cargo_legal, org.email_legal]);
    const files     = Array.isArray(org.files) ? org.files : [];

    return `
      <div class="card org-card shadow-sm border-0 mb-3" style="border-radius: 1.25rem;">
        <div class="card-body p-4">
          <div class="row g-4">
            <!-- Columna izquierda: datos -->
            <div class="col-lg-8 border-end-lg">
              
              <div class="d-flex align-items-center mb-3 flex-wrap gap-2">
                <h5 class="fw-bold mb-0 text-dark" style="font-size: 1.2rem;">${escapeHtml(nombre)}</h5>
                ${giro ? `<span class="badge" style="background:#eef2ff; color:#4f46e5; border-radius:10px; font-weight:600; padding: 0.4rem 0.6rem;">${escapeHtml(giro)}</span>` : ''}
                ${tipo ? `<span class="badge" style="background:#f1f5f9; color:#64748b; border-radius:10px; font-weight:600; padding: 0.4rem 0.6rem;">${escapeHtml(tipo)}</span>` : ''}
              </div>

              <div class="d-flex flex-column gap-2 text-secondary" style="font-size: 0.95rem;">
                ${direccion ? `
                <div class="d-flex align-items-start gap-2">
                  <i class="fas fa-map-marker-alt mt-1 text-primary" style="min-width: 16px;"></i>
                  <span>${escapeHtml(direccion)}</span>
                </div>` : ''}
                
                ${contacto ? `
                <div class="d-flex align-items-start gap-2">
                  <i class="fas fa-address-book mt-1 text-success" style="min-width: 16px;"></i>
                  <span>${escapeHtml(contacto)}</span>
                </div>` : ''}
                
                ${legal ? `
                <div class="d-flex align-items-start gap-2">
                  <i class="fas fa-user-tie mt-1 text-warning" style="min-width: 16px;"></i>
                  <span><strong>Rep. Legal:</strong> ${escapeHtml(legal)}</span>
                </div>` : ''}
                
                <div class="d-flex flex-wrap gap-4 mt-1 small">
                  <div><strong>Constitución:</strong> ${fechaConst}</div>
                  <div><strong>Registro:</strong> ${creado}</div>
                  ${web ? `<div><strong>Web:</strong> <a href="${encodeURI(web)}" target="_blank" rel="noopener" class="text-decoration-none">${escapeHtml(web)}</a></div>` : ''}
                </div>
              </div>
            </div>

            <!-- Columna derecha: acciones + documentos -->
            <div class="col-lg-4 d-flex flex-column">
              <div class="d-flex gap-2 mb-4 w-100">
                <button class="btn flex-fill fw-bold rounded-pill shadow-sm btn-accept-organism-external" data-id="${escapeHtml(String(id))}" style="background: #10b981; color: white; border: none;">
                  <i class="fas fa-check-circle me-1"></i> Aceptar
                </button>
                <button class="btn flex-fill fw-bold rounded-pill shadow-sm btn-reject-organism-external" data-id="${escapeHtml(String(id))}" style="background: #ef4444; color: white; border: none;">
                  <i class="fas fa-times-circle me-1"></i> Rechazar
                </button>
              </div>

              <div class="flex-grow-1 bg-light rounded-4 p-3 border">
                <div class="text-uppercase text-muted fw-bold mb-2" style="font-size: 0.75rem; letter-spacing: 0.05em;">Documentos Adjuntos</div>
                ${
                  files.length
                  ? `<div class="d-flex flex-column gap-2">
                      ${files.map(f => `
                        <a href="#" onclick="return openPdfViewer('uploads/${encodeURIComponent(id)}/${encodeURIComponent(f)}')" class="d-flex align-items-center gap-2 text-decoration-none text-dark bg-white border p-2 rounded-3 shadow-sm hover-shadow transition">
                          <div class="bg-primary bg-opacity-10 text-primary rounded p-1"><i class="fas fa-file-pdf"></i></div>
                          <span class="text-truncate small fw-medium">${escapeHtml(f)}</span>
                        </a>
                      `).join('')}
                    </div>`
                  : `<div class="text-muted small text-center py-3"><i class="fas fa-folder-open mb-1 d-block opacity-50"></i> Sin documentos</div>`
                }
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
  }

    // Reportes de prácticas (parcial/final)
    function renderReportPractices(report, type) {
      let btnReport = '';
      let data = '';
      if (type === 'parcial') {
        btnReport = 'btn-view-report';
        data = `data-id="${report.idReporteParcial}" data-objetivo="${escapeHtml(report.objetivo || '')}" data-actividades="${escapeHtml(report.actividades_repotadas || '')}"`;
      } else {
        btnReport = 'btn-view-final-report';
        data = `data-id="${report.idReporteFinal}"
                data-objetivo="${escapeHtml(report.objetivo_general || '')}"
                data-actividades="${escapeHtml(report.actividades_realizadas || '')}"
                data-resultados="${escapeHtml(report.resultados_obtenidos || '')}"
                data-capacitacion="${escapeHtml(report.capacitacion_recibida || '')}"
                data-exp-profesional="${escapeHtml(report.experiencia_profesional || '')}"
                data-exp-personal="${escapeHtml(report.experiencia_personal || '')}"
                data-comentarios="${escapeHtml(report.comentarios || '')}"`;
      }

      const fecha = new Date(report.dateCreated);
      const fechaFormateada = isNaN(fecha)
        ? safeTxt(report.dateCreated, '')
        : fecha.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' });
      const horaFormateada = isNaN(fecha)
        ? ''
        : fecha.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', hour12: true });

      const when = [fechaFormateada, horaFormateada].filter(Boolean).join(' ');

      return `
        <div class="card shadow-sm border-0 mb-3" style="border-radius: 1rem;">
          <div class="card-body p-3 d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
              <div class="bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; font-size: 1.2rem;">
                <i class="fas fa-file-alt"></i>
              </div>
              <div>
                <h6 class="mb-0 fw-bold text-dark" style="font-size: 1.05rem;">${escapeHtml(report.nombre_completo || 'Nombre no disponible')}</h6>
                <div class="small text-muted mt-1 d-flex flex-wrap gap-2">
                  <span class="badge ${type === 'parcial' ? 'bg-primary' : 'bg-success'} text-white rounded-pill px-2">Reporte ${type === 'parcial' ? 'Parcial' : 'Final'}</span>
                  ${report.matricula ? `<span><i class="fas fa-id-card me-1"></i>${escapeHtml(report.matricula)}</span>` : ''}
                  ${when ? `<span><i class="far fa-clock me-1"></i>${escapeHtml(when)}</span>` : ''}
                </div>
              </div>
            </div>
            <div class="d-flex gap-2">
              <button class="btn btn-sm px-3 fw-bold rounded-pill shadow-sm btn-outline-primary ${btnReport}" ${data}>
                <i class="fas fa-eye me-1"></i> Ver Reporte
              </button>
            </div>
          </div>
        </div>
      `;
    }

    // Solicitud de capacitación (alias flexibles)
    function renderSolicitudCapacitacion(sol) {
      const id = sol.idSolicitudCap;
      const alumno = sol.studentName || sol.nombre_completo || sol.alumno || 'Alumno no disponible';
      const correo = sol.email || sol.correo || '';

      const fechaRaw = sol.dateCreated || sol.created_at || sol.fecha || sol.fecha_solicitud || '';
      let fechaFmt = '';
      if (fechaRaw) {
        const d = new Date(String(fechaRaw).replace(' ', 'T'));
        if (!isNaN(d)) {
          const f = d.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' });
          const h = d.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', hour12: true });
          fechaFmt = `${f} ${h}`;
        } else {
          fechaFmt = fechaRaw;
        }
      }

      let contenidoHTML = sol.solicitud ? $('<div>').html(sol.solicitud).text() : '';
      if (contenidoHTML.startsWith(' ')) contenidoHTML = contenidoHTML.substring(2);

      return `
        <div class="card shadow-sm border-0 mb-3" style="border-radius: 1rem;">
          <div class="card-body p-3 p-md-4">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
              <div class="d-flex gap-3 w-100">
                <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 45px; height: 45px; font-size: 1.2rem;">
                  <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="flex-grow-1">
                  <h6 class="mb-0 fw-bold text-dark" style="font-size: 1.1rem;">${escapeHtml(alumno)}</h6>
                  <div class="small text-muted mt-1 d-flex flex-wrap gap-2 mb-2">
                    ${correo ? `<span><i class="fas fa-envelope me-1"></i>${escapeHtml(correo)}</span>` : ''}
                    ${fechaFmt ? `<span><i class="far fa-clock me-1"></i>${escapeHtml(fechaFmt)}</span>` : ''}
                  </div>
                  <div class="p-3 bg-light rounded text-secondary small border" style="max-height: 120px; overflow-y: auto;">
                    ${contenidoHTML ? escapeHtml(contenidoHTML).replace(/\r?\n/g, '<br>') : '<em class="text-muted">Sin contenido de solicitud proporcionado</em>'}
                  </div>
                </div>
              </div>
              <div class="d-flex gap-2 ms-auto mt-2 w-100 justify-content-end align-self-start">
                <button class="btn btn-sm px-4 fw-bold rounded-pill shadow-sm btn-accept-solicitud-capacitacion" data-id="${escapeHtml(String(id ?? ''))}" style="background: #10b981; color: white; border: none;">
                  <i class="fas fa-check me-1"></i> Aceptar
                </button>
                <button class="btn btn-sm px-4 fw-bold rounded-pill shadow-sm btn-reject-solicitud-capacitacion" data-id="${escapeHtml(String(id ?? ''))}" style="background: #ef4444; color: white; border: none;">
                  <i class="fas fa-times me-1"></i> Rechazar
                </button>
              </div>
            </div>
          </div>
        </div>
      `;
    }

    // =====================================
    // Loaders por bloque
    // =====================================
    function loadServiceStudents() {
      loadList(
        '.list-student-service-external',
        { search: 'student', action: 'noaceptedStudents' },
        (student) => renderServiceStudent(student, 'student-services'),
        'No hay nuevos alumnos de servicio social'
      );
    }

    function loadPracticeStudents() {
      loadList(
        '.list-student-practice-professional',
        { search: 'student', action: 'noAceptedStudentsPractice' },
        (student) => renderServiceStudent(student, 'student-practices'),
        'No hay nuevos alumnos de prácticas profesionales'
      );
    }

    function loadRequestPracticeStudents() {
      loadList(
        '.list-request-practice-professional',
        { search: 'practices', action: 'newSolicitudesPracticantes' },
        renderSolicitudPracticante,
        'No hay solicitudes de practicantes'
      );
    }

    function loadOrganismExternal() {
      loadList(
        '.list-organism-external',
        { search: 'organismos_externos', action: 'getNewOrganismosExternos' },
        renderOrganismoExterno, // <- importante: usar el render correcto
        'No hay nuevos organismos externos'
      );
    }

    function loadPracticeReports() {
      LoadListReports(
        '.list-reports-practices',
        { search: 'reports', action: 'getReportsPractices' },
        renderReportPractices,
        'No hay reportes de prácticas'
      );
    }

    function loadSolicitudesCapacitacion() {
      loadList(
        '.list-solicitudes-capacitacion',
        { search: 'reports', action: 'getSolicitudesCapacitacion' },
        renderSolicitudCapacitacion,
        'No hay solicitudes de capacitaciones'
      );
    }

    // ── IJUMICH ──
    function renderIjumichRequest(req) {
      const tipoLabels = {
        carta_presentacion:           'Carta de Presentación',
        carta_liberacion:             'Carta de Liberación',
        carta_practicas_interno:      'Carta Finalización Prácticas (Interno)',
        carta_aceptacion_servicio:    'Carta de Aceptación SS (Interno)',
        carta_liberacion_interno:     'Carta de Liberación SS (Interno)',
        reporte_parcial_1:            'Reporte Parcial 1 de 3',
        reporte_parcial_2:            'Reporte Parcial 2 de 3',
        reporte_parcial_3:            'Reporte Parcial 3 de 3',
        evaluacion_unidad_productiva: 'Evaluación de la Unidad Receptora',
        evaluacion_global:            'Evaluación Global',
      };
      const badgeColors = {
        carta_presentacion:           '#01643D',
        carta_liberacion:             '#01643D',
        carta_practicas_interno:      '#7c3aed',
        carta_aceptacion_servicio:    '#1d4ed8',
        carta_liberacion_interno:     '#00594F',
        reporte_parcial_1:            '#0b5911',
        reporte_parcial_2:            '#0b5911',
        reporte_parcial_3:            '#0b5911',
        evaluacion_unidad_productiva: '#b45309',
        evaluacion_global:            '#7c3aed',
      };
      const tipoLabel = tipoLabels[req.tipo] || req.tipo;
      const badgeColor = badgeColors[req.tipo] || '#6b7280';
      const badge = `<span class="badge text-white" style="background:${badgeColor}; border-radius: 8px; padding: 0.4rem 0.6rem;">${tipoLabel}</span>`;
      const alumno    = escapeHtml(req.nombre_alumno || '—');
      const matricula = escapeHtml(req.matricula || '');
      const email     = escapeHtml(req.email || '');
      const fecha     = safeDateYMD(req.created_at);

      let detalle = '';
      if (req.tipo === 'carta_presentacion') {
        detalle = `
          <div class="mt-3 p-3 bg-light rounded text-secondary small border">
            <div class="mb-1"><i class="fas fa-building me-1 text-primary"></i> <strong>Organismo:</strong> ${escapeHtml(req.nombre_organismo || '—')}</div>
            <div class="mb-1"><i class="fas fa-user-tie me-1 text-success"></i> <strong>Responsable:</strong> ${escapeHtml(req.responsable || '—')} ${req.puesto_responsable ? `(${escapeHtml(req.puesto_responsable)})` : ''}</div>
            <div class="mb-1"><i class="fas fa-map-marker-alt me-1 text-danger"></i> <strong>Domicilio:</strong> ${escapeHtml(req.domicilio || '—')}</div>
            ${req.observaciones ? `<div class="mt-2 fst-italic border-top pt-2"><i class="fas fa-comment-dots me-1"></i>${escapeHtml(req.observaciones)}</div>` : ''}
          </div>`;
      } else if (req.tipo === 'carta_aceptacion_servicio') {
        detalle = `
          <div class="mt-3 p-3 rounded" style="background: #eff6ff; border: 1px solid #bfdbfe;">
            <div class="text-primary small"><i class="fas fa-info-circle me-2"></i><strong>Nota:</strong> Al aprobar, se generará la Carta de Aceptación de Servicio Social para el alumno.</div>
            ${req.observaciones ? `<div class="mt-2 small text-secondary fst-italic">${escapeHtml(req.observaciones)}</div>` : ''}
          </div>`;
      } else {
        const archivoLink = req.archivo_path
          ? `<a href="#" onclick="return openPdfViewer('${encodeURI(req.archivo_path)}')" class="d-inline-flex align-items-center gap-2 text-decoration-none text-dark bg-white border p-2 rounded shadow-sm hover-shadow transition mt-2"><div class="bg-primary bg-opacity-10 text-primary rounded p-1"><i class="fas fa-file-pdf"></i></div><span class="small fw-medium">${escapeHtml(req.archivo_nombre || 'Ver documento adjunto')}</span></a>`
          : '<div class="mt-2 text-danger small"><i class="fas fa-exclamation-triangle me-1"></i>Sin archivo adjunto</div>';
        detalle = `
          <div class="mt-3">
            ${req.observaciones ? `<div class="p-2 mb-2 bg-light rounded text-secondary small border"><em>${escapeHtml(req.observaciones)}</em></div>` : ''}
            ${archivoLink}
          </div>`;
      }

      let pdfBtn = '';
      if (req.tipo === 'carta_presentacion') {
        pdfBtn = `<a href="#" onclick="return openPdfViewer('controller/ajax/generarCartaIjumich.php?id=${encodeURIComponent(req.id)}')" class="btn btn-sm btn-outline-primary fw-bold rounded-pill shadow-sm"><i class="fas fa-file-pdf me-1"></i>Ver PDF</a>`;
      } else if (req.tipo === 'carta_aceptacion_servicio') {
        pdfBtn = `<a href="#" onclick="return openPdfViewer('controller/ajax/generarCartaAceptacionServicio.php?id=${encodeURIComponent(req.id)}')" class="btn btn-sm btn-outline-primary fw-bold rounded-pill shadow-sm"><i class="fas fa-file-pdf me-1"></i>Ver PDF</a>`;
      } else if (req.tipo === 'evaluacion_unidad_productiva' && req.archivo_path) {
        pdfBtn = `<a href="#" onclick="return openPdfViewer('controller/ajax/previewEvaluacionFirmada.php?id=${encodeURIComponent(req.id)}')" class="btn btn-sm btn-outline-warning fw-bold rounded-pill shadow-sm text-dark"><i class="fas fa-eye me-1"></i>Preview firmado</a>`;
      } else if (req.tipo === 'evaluacion_global' && req.archivo_path) {
        pdfBtn = `<a href="#" onclick="return openPdfViewer('controller/ajax/previewEvaluacionGlobal.php?id=${encodeURIComponent(req.id)}')" class="btn btn-sm btn-outline-warning fw-bold rounded-pill shadow-sm text-dark"><i class="fas fa-eye me-1"></i>Preview firmado</a>`;
      }

      return `
        <div class="card shadow-sm border-0 mb-3" style="border-radius: 1rem;">
          <div class="card-body p-3 p-md-4">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
              <div style="flex:1; min-width: 250px;">
                <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                  ${badge}
                  <span class="text-muted small ms-auto"><i class="far fa-calendar-alt me-1"></i>${fecha}</span>
                </div>
                <h6 class="mb-1 fw-bold text-dark" style="font-size: 1.1rem;">${alumno}</h6>
                <div class="small text-muted d-flex flex-wrap gap-2">
                  ${matricula ? `<span><i class="fas fa-id-card me-1"></i>${matricula}</span>` : ''}
                  ${email ? `<span><i class="fas fa-envelope me-1"></i>${email}</span>` : ''}
                </div>
                ${detalle}
              </div>
              <div class="d-flex flex-column gap-2" style="min-width: 130px;">
                ${pdfBtn ? `<div class="mb-1 w-100 d-grid">${pdfBtn}</div>` : ''}
                <button class="btn btn-sm w-100 fw-bold rounded-pill shadow-sm btn-approve-ijumich" data-id="${escapeHtml(String(req.id))}" data-tipo="${escapeHtml(req.tipo)}" style="background: #10b981; color: white; border: none;">
                  <i class="fas fa-check me-1"></i> Aprobar
                </button>
                <button class="btn btn-sm w-100 fw-bold rounded-pill shadow-sm btn-reject-ijumich" data-id="${escapeHtml(String(req.id))}" data-tipo="${escapeHtml(req.tipo)}" style="background: #ef4444; color: white; border: none;">
                  <i class="fas fa-times me-1"></i> Rechazar
                </button>
              </div>
            </div>
          </div>
        </div>
      `;
    }

    function loadIjumichRequests() {
      loadList(
        '.list-ijumich-requests',
        { search: 'ijumich_requests', action: 'getPendingRequests' },
        renderIjumichRequest,
        'No hay solicitudes IJUMICH pendientes'
      );
    }

    function loadPostulacionesAreas() {
      $.ajax({
        url: 'controller/practices/areas.php',
        method: 'POST',
        data: { action: 'getPostulaciones' },
        dataType: 'json'
      }).done(function (data) {
        const pending = (data || []).filter(p => p.status == 0);
        const $c = $('.list-postulaciones-areas');
        if (!pending.length) {
          $c.html('<div class="d-flex align-items-center justify-content-center" style="height:100%;"><span class="text-gray-600">Sin postulaciones pendientes</span></div>');
          return;
        }
        const items = pending.slice(0, 5).map(p => `
          <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
            <div>
              <strong class="small">${(p.nombre_completo ?? '').replace(/</g,'&lt;')}</strong>
              <span class="badge bg-light text-dark border ms-1 small">${(p.area_nombre ?? '').replace(/</g,'&lt;')}</span>
            </div>
            <a href="internship_areas" class="btn btn-xs btn-outline-primary btn-sm py-0 px-2">Ver</a>
          </div>`).join('');
        const extra = pending.length > 5 ? `<p class="text-muted small text-end mt-1">+${pending.length - 5} más…</p>` : '';
        $c.html(items + extra);
      });
    }

    // Advertencia: organismos aceptados a los que les falta el convenio validado
    function loadConveniosFaltantes() {
      $.ajax({
        url: 'controller/practices/companies.php',
        method: 'POST',
        data: { action: 'get_convenios_faltantes' },
        dataType: 'json'
      }).done(function (res) {
        if (!res || !res.success || !Array.isArray(res.data) || !res.data.length) {
          $('#convenioFaltanteAlert').empty();
          return;
        }
        const names = res.data.map(o => escapeHtml(o.empresa)).join(', ');
        $('#convenioFaltanteAlert').html(`
          <div class="alert alert-warning border-0 shadow-sm d-flex align-items-start gap-3 mb-0" role="alert" style="border-radius:1rem;">
            <i class="fas fa-exclamation-triangle mt-1" style="flex-shrink:0; font-size:1.2rem;"></i>
            <div class="flex-grow-1">
              <strong>${res.data.length} organismo(s) aceptado(s) sin convenio cargado por la institución.</strong>
              <div class="small mt-1">Falta subir el PDF del convenio firmado de: ${names}.</div>
            </div>
            <a href="internship_companies" class="btn btn-sm btn-warning rounded-pill fw-bold align-self-center text-nowrap">
              <i class="fas fa-file-upload me-1"></i> Gestionar
            </a>
          </div>
        `);
      });
    }

    // ============================
    // Inicialización
    // ============================
    loadConveniosFaltantes();
    loadServiceStudents();
    loadPracticeStudents();
    loadRequestPracticeStudents();
    loadOrganismExternal();
    loadPracticeReports();
    loadSolicitudesCapacitacion();
    loadPostulacionesAreas();
    loadIjumichRequests();

    // =====================================
    // Delegación de eventos (acciones)
    // =====================================

    // Alumnos Servicio Social
    $('.list-student-service-external')
      .on('click', '.btn-accept-student-services', function () {
        const id = $(this).data('id');
        if (!confirm('¿Está seguro de aceptar a este alumno?')) return;
        $.post('controller/ajax/ajax.forms.php',
          { search: 'student', action: 'acceptStudent', idStudent: id },
          function (response) {
            if (response === 'success' || response?.success === true) {
              loadServiceStudents();
            } else {
              alert(response?.message || 'No se pudo aceptar al alumno.');
            }
          }, 'json'
        ).fail(function () {
          alert('Error al procesar la solicitud.');
        });
      })
      .on('click', '.btn-reject-student-services', function () {
        const id = $(this).data('id');
        if (!confirm('¿Está seguro de rechazar a este alumno?')) return;
        $.post('controller/ajax/ajax.forms.php',
          { search: 'student', action: 'denegateStudent', idStudent: id },
          function (response) {
            if (response === 'success' || response?.success === true) {
              loadServiceStudents();
            } else {
              alert(response?.message || 'No se pudo rechazar al alumno.');
            }
          }, 'json'
        ).fail(function () {
          alert('Error al procesar la solicitud.');
        });
      });

    // Alumnos Prácticas
    $('.list-student-practice-professional')
      .on('click', '.btn-accept-student-practices', function () {
        const id = $(this).data('id');
        if (!confirm('¿Está seguro de aceptar a este alumno?')) return;
        $.post('controller/ajax/ajax.forms.php',
          { search: 'student', action: 'acceptStudentPractice', idStudent: id },
          function (response) {
            if (response === 'success' || response?.success === true) {
              loadPracticeStudents();
            } else {
              alert(response?.message || 'No se pudo aceptar al alumno.');
            }
          }, 'json'
        ).fail(function () {
          alert('Error al procesar la solicitud.');
        });
      })
      .on('click', '.btn-reject-student-practices', function () {
        const id = $(this).data('id');
        if (!confirm('¿Está seguro de rechazar a este alumno?')) return;
        $.post('controller/ajax/ajax.forms.php',
          { search: 'student', action: 'denegateStudentPractice', idStudent: id },
          function (response) {
            if (response === 'success' || response?.success === true) {
              loadPracticeStudents();
            } else {
              alert(response?.message || 'No se pudo rechazar al alumno.');
            }
          }, 'json'
        ).fail(function () {
          alert('Error al procesar la solicitud.');
        });
      });

    // Solicitudes de practicantes (aceptar/rechazar)
    $('.list-request-practice-professional')
      .on('click', '.btn-accept-request-practice', function () {
        const id = $(this).data('id');
        if (!confirm('¿Aceptar esta solicitud de practicante?')) return;
        $.post('controller/ajax/ajax.forms.php',
          { search: 'practices', action: 'acceptSolicitudPracticante', idSolicitud: id },
          resp => {
            if (resp === 'success' || resp?.success === true) {
              loadRequestPracticeStudents();
            } else {
              alert(resp?.message || 'No se pudo aceptar la solicitud.');
            }
          }, 'json'
        ).fail(() => alert('Error al procesar la solicitud.'));
      })
      .on('click', '.btn-reject-request-practice', function () {
        const id = $(this).data('id');
        if (!confirm('¿Rechazar esta solicitud de practicante?')) return;
        $.post('controller/ajax/ajax.forms.php',
          { search: 'practices', action: 'rejectSolicitudPracticante', idSolicitud: id },
          resp => {
            if (resp === 'success' || resp?.success === true) {
              loadRequestPracticeStudents();
            } else {
              alert(resp?.message || 'No se pudo rechazar la solicitud.');
            }
          }, 'json'
        ).fail(() => alert('Error al procesar la solicitud.'));
      });

    // Organismos externos (aceptar/rechazar)
    $('.list-organism-external')
      .on('click', '.btn-accept-organism-external', function () {
        const id = $(this).data('id');
        if (!confirm('¿Aceptar este organismo externo?')) return;
        $.post('controller/ajax/ajax.forms.php',
          { search: 'organismos_externos', action: 'acceptOrganismoExterno', idOrganismo: id },
          resp => {
            if (resp === 'ok' || resp?.success === true) {
              loadOrganismExternal();
            } else {
              alert(resp?.message || 'No se pudo aceptar el organismo externo.');
            }
          }, 'json'
        ).fail(() => alert('Error al procesar la solicitud.'));
      })
      .on('click', '.btn-reject-organism-external', function () {
        const id = $(this).data('id');
        
        Swal.fire({ title: 'Cargando...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

        $.post('controller/practices/companies.php', { action: 'get_organismo_details', id: id }, function(res) {
            Swal.close();
            if (!res.success) { Swal.fire('Error', res.message, 'error'); return; }

            const org = res.data;
            $('#rechazoOrgId').val(id);
            $('#icRechazoModalSub').text(org.empresa);
            $('#rechazoMotivoGeneral').val('');
            
            const tbody = $('#tablaCamposRechazo tbody');
            tbody.empty();

            const addFieldRow = (key, label, val) => {
                if (val === null || val === '') val = '<em class="text-muted">Vacío</em>';
                tbody.append(`
                    <tr>
                        <td class="text-center">
                            <input class="form-check-input field-cb" type="checkbox" data-campo="${key}" data-label="${escapeHtml(label)}" style="transform: scale(1.3);">
                        </td>
                        <td><strong>${escapeHtml(label)}</strong></td>
                        <td class="text-break" style="font-size:0.9rem;">${val}</td>
                        <td>
                            <div class="field-inputs d-none">
                                <select class="form-select form-select-sm mb-1 type-select">
                                    <option value="incorrecto">Dato incorrecto</option>
                                    <option value="faltante">Falta información</option>
                                    <option value="invalido">Formato inválido</option>
                                </select>
                                <textarea class="form-control form-control-sm mb-1 reason-text" placeholder="Motivo del error..." rows="1"></textarea>
                                <textarea class="form-control form-control-sm obs-text" placeholder="Observación (opcional)..." rows="1"></textarea>
                            </div>
                        </td>
                    </tr>
                `);
            };

            // Datos Generales
            addFieldRow('empresa', 'Nombre de la Institución/Organismo', escapeHtml(org.empresa));
            addFieldRow('tipo_persona', 'Tipo de persona', escapeHtml(org.tipo_persona));
            addFieldRow('giro', 'Giro o actividad', escapeHtml(org.giro));
            addFieldRow('fecha_constitucion', 'Fecha de constitución', escapeHtml(org.fecha_constitucion));
            addFieldRow('web', 'Sitio Web', escapeHtml(org.web));

            // Domicilio
            addFieldRow('calle', 'Calle y número', escapeHtml(org.calle));
            addFieldRow('colonia', 'Colonia', escapeHtml(org.colonia));
            addFieldRow('cp', 'Código Postal', escapeHtml(org.cp));
            addFieldRow('ciudad', 'Ciudad', escapeHtml(org.ciudad));

            // Contacto Operativo
            addFieldRow('nombre_contacto', 'Nombre del Contacto Operativo', escapeHtml(org.nombre_contacto));
            addFieldRow('telefonos', 'Teléfonos (Contacto)', escapeHtml(org.telefonos));
            addFieldRow('celular', 'Celular (Contacto)', escapeHtml(org.celular));
            addFieldRow('email', 'Correo (Contacto)', escapeHtml(org.email));

            // Representante Legal
            addFieldRow('rep_legal', 'Nombre Representante Legal', escapeHtml(org.rep_legal));
            addFieldRow('cargo_legal', 'Cargo Representante Legal', escapeHtml(org.cargo_legal));
            addFieldRow('tel_oficina', 'Teléfono Oficina (Rep. Legal)', escapeHtml(org.tel_oficina));
            addFieldRow('email_legal', 'Correo (Rep. Legal)', escapeHtml(org.email_legal));

            // Documentos
            if (org.documentos && org.documentos.length > 0) {
                org.documentos.forEach(doc => {
                    const valHtml = `<a href="${doc.url}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-external-link-alt me-1"></i>Ver Documento</a>`;
                    addFieldRow(`doc:${doc.name}`, `Documento: ${doc.name}`, valHtml);
                });
            }

            $('#icRechazoModal').modal('show');
        }, 'json').fail(function() {
            Swal.fire('Error', 'Error al conectar con el servidor', 'error');
        });
      });

    // Eventos del modal de rechazo
    $(document).on('change', '.field-cb', function() {
        const inputsDiv = $(this).closest('tr').find('.field-inputs');
        if ($(this).is(':checked')) {
            inputsDiv.removeClass('d-none');
            inputsDiv.find('.reason-text').attr('required', true);
        } else {
            inputsDiv.addClass('d-none');
            inputsDiv.find('.reason-text').removeAttr('required').val('');
            inputsDiv.find('.obs-text').val('');
        }
    });

    $('#btnConfirmarRechazo').click(function() {
        const form = $('#formRechazoOrganismo')[0];
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const id = $('#rechazoOrgId').val();
        const motivoGeneral = $('#rechazoMotivoGeneral').val().trim();
        
        const campos = [];
        $('.field-cb:checked').each(function() {
            const tr = $(this).closest('tr');
            campos.push({
                campo: $(this).data('campo'),
                campo_label: $(this).data('label'),
                estado: tr.find('.type-select').val(),
                motivo: tr.find('.reason-text').val().trim(),
                observacion: tr.find('.obs-text').val().trim(),
                valor_original: tr.find('td:nth-child(3)').text().trim()
            });
        });

        if (campos.length === 0) {
            Swal.fire('Error', 'Debes seleccionar al menos un campo o documento incorrecto.', 'error');
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Enviando...');

        $.post('controller/practices/companies.php', {
            action: 'reject_external_with_reasons',
            id: id,
            motivo_general: motivoGeneral,
            campos: JSON.stringify(campos)
        }, function(res) {
            if (res.success) {
                $('#icRechazoModal').modal('hide');
                Swal.fire({ icon: 'success', title: '¡Listo!', text: res.message, timer: 1800, showConfirmButton: false });
                loadOrganismExternal();
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }, 'json').fail(function() {
            Swal.fire('Error', 'Error al procesar la solicitud.', 'error');
        }).always(function() {
            btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-2"></i>Enviar Rechazo');
        });
    });

    // Reportes – Parciales
    $('.list-reports-practices')
      .on('click', '.btn-view-report', function () {
        const id = $(this).data('id');
        const objetivo = $(this).data('objetivo');
        const actividades = $(this).data('actividades');

        Swal.fire({
          title: 'Reporte de Prácticas',
          html: `
            <div class="mb-3 text-start">
              <strong>Objetivo:</strong>
              <div class="border rounded p-2 mb-2 bg-light">${escapeHtml(objetivo || 'Sin objetivo')}</div>
              <strong>Actividades realizadas:</strong>
              <div class="border rounded p-2 bg-light">${escapeHtml(actividades || 'Sin actividades')}</div>
            </div>
          `,
          showCancelButton: true,
          confirmButtonText: 'Aceptar',
          cancelButtonText: 'Rechazar',
          reverseButtons: true,
          focusConfirm: false
        }).then(result => {
          if (result.isConfirmed) {
            $.post('controller/ajax/ajax.forms.php', {
              search: 'reports',
              action: 'acceptReportPractice',
              idReporte: id
            }, resp => {
              if (resp?.success === true || resp === 'success') {
                Swal.fire('Aceptado', resp?.message || 'El reporte fue aceptado.', 'success');
                loadPracticeReports();
              } else {
                Swal.fire('Error', resp?.message || 'No se pudo aceptar el reporte.', 'error');
              }
            }, 'json').fail(() => {
              Swal.fire('Error', 'Error al procesar la solicitud.', 'error');
            });
          } else if (result.dismiss === Swal.DismissReason.cancel) {
            Swal.fire({
              title: 'Rechazar reporte',
              input: 'textarea',
              inputLabel: 'Comentario de rechazo',
              inputPlaceholder: 'Escriba el motivo del rechazo...',
              showCancelButton: true,
              confirmButtonText: 'Enviar',
              cancelButtonText: 'Cancelar',
              inputValidator: value => {
                if (!value) return 'Debe ingresar un comentario';
              }
            }).then(res => {
              if (res.isConfirmed) {
                $.post('controller/ajax/ajax.forms.php', {
                  search: 'reports',
                  action: 'rejectReportPractice',
                  idReporte: id,
                  comentario: res.value
                }, resp => {
                  if (resp?.success === true || resp === 'success') {
                    Swal.fire('Rechazado', resp?.message || 'El reporte fue rechazado.', 'success');
                    loadPracticeReports();
                  } else {
                    Swal.fire('Error', resp?.message || 'No se pudo rechazar el reporte.', 'error');
                  }
                }, 'json').fail(() => {
                  Swal.fire('Error', 'Error al procesar la solicitud.', 'error');
                });
              }
            });
          }
        });
      });

    // Reportes – Finales
    $('.list-reports-practices')
      .on('click', '.btn-view-final-report', function () {
        const id = $(this).data('id');
        const objetivo = $(this).data('objetivo');
        const actividades = $(this).data('actividades');
        const resultados = $(this).data('resultados');
        const capacitacion = $(this).data('capacitacion');
        const expProfesional = $(this).data('exp-profesional');
        const expPersonal = $(this).data('exp-personal');

        Swal.fire({
          title: 'Reporte Final',
          html: `
            <div style="max-width:1200px;text-align:left;">
              <strong>Objetivo:</strong>
              <div class="border rounded p-2 mb-2 bg-light">${escapeHtml(objetivo || 'Sin objetivo')}</div>
              <strong>Actividades:</strong>
              <div class="border rounded p-2 mb-2 bg-light">${escapeHtml(actividades || 'Sin actividades')}</div>
              <strong>Resultados:</strong>
              <div class="border rounded p-2 mb-2 bg-light">${escapeHtml(resultados || 'Sin resultados')}</div>
              <strong>Capacitación:</strong>
              <div class="border rounded p-2 mb-2 bg-light">${escapeHtml(capacitacion || 'Sin capacitación')}</div>
              <strong>Experiencia Profesional:</strong>
              <div class="border rounded p-2 mb-2 bg-light">${escapeHtml(expProfesional || 'Sin experiencia profesional')}</div>
              <strong>Experiencia Personal:</strong>
              <div class="border rounded p-2 mb-2 bg-light">${escapeHtml(expPersonal || 'Sin experiencia personal')}</div>
            </div>
          `,
          icon: 'info',
          showCancelButton: true,
          confirmButtonText: 'Aceptar',
          cancelButtonText: 'Rechazar',
          reverseButtons: true,
          customClass: { popup: 'swal2-wide-modal' },
          width: '1200px',
          focusConfirm: false
        }).then(result => {
          if (result.isConfirmed) {
            $.post('controller/ajax/ajax.forms.php', {
              search: 'reports',
              action: 'acceptReportPracticeFinal',
              idReporte: id
            }, resp => {
              if (resp?.success === true || resp === 'success') {
                Swal.fire('Aceptado', resp?.message || 'El reporte final fue aceptado.', 'success');
                loadPracticeReports();
              } else {
                Swal.fire('Error', resp?.message || 'No se pudo aceptar el reporte final.', 'error');
              }
            }, 'json').fail(() => {
              Swal.fire('Error', 'Error al procesar la solicitud.', 'error');
            });
          } else if (result.dismiss === Swal.DismissReason.cancel) {
            Swal.fire({
              title: 'Rechazar reporte final',
              input: 'textarea',
              inputLabel: 'Comentario de rechazo',
              inputPlaceholder: 'Escriba el motivo del rechazo...',
              showCancelButton: true,
              confirmButtonText: 'Enviar',
              cancelButtonText: 'Cancelar',
              inputValidator: value => {
                if (!value) return 'Debe ingresar un comentario';
              }
            }).then(res => {
              if (res.isConfirmed) {
                $.post('controller/ajax/ajax.forms.php', {
                  search: 'reports',
                  action: 'rejectReportPracticeFinal',
                  idReporte: id,
                  comentario: res.value
                }, resp => {
                  if (resp?.success === true || resp === 'success') {
                    Swal.fire('Rechazado', resp?.message || 'El reporte final fue rechazado.', 'success');
                    loadPracticeReports();
                  } else {
                    Swal.fire('Error', resp?.message || 'No se pudo rechazar el reporte final.', 'error');
                  }
                }, 'json').fail(() => {
                  Swal.fire('Error', 'Error al procesar la solicitud.', 'error');
                });
              }
            });
          }
        });
      });

    // Solicitudes IJUMICH
    $('.list-ijumich-requests')
      .on('click', '.btn-approve-ijumich', function () {
        const id   = $(this).data('id');
        const tipoMap = { carta_presentacion: 'carta de presentación', carta_liberacion: 'carta de liberación', carta_practicas_interno: 'carta de finalización de prácticas', carta_aceptacion_servicio: 'carta de aceptación de servicio social', carta_liberacion_interno: 'carta de liberación (interno)', reporte_parcial_1: 'reporte parcial 1', reporte_parcial_2: 'reporte parcial 2', reporte_parcial_3: 'reporte parcial 3', evaluacion_unidad_productiva: 'evaluación de la unidad receptora', evaluacion_global: 'evaluación global' };
        const tipo = tipoMap[$(this).data('tipo')] || $(this).data('tipo');
        Swal.fire({
          title: 'Aprobar solicitud',
          text: `¿Confirmas aprobar esta ${tipo}?`,
          input: 'textarea',
          inputLabel: 'Comentario (opcional)',
          inputPlaceholder: 'Puedes dejar un comentario para el alumno...',
          showCancelButton: true,
          confirmButtonText: 'Aprobar',
          cancelButtonText: 'Cancelar',
          confirmButtonColor: '#01643D'
        }).then(res => {
          if (!res.isConfirmed) return;
          $.post('controller/ajax/ajax.forms.php', {
            search: 'ijumich_requests',
            action: 'approveRequest',
            id: id,
            comentario: res.value || ''
          }, function (resp) {
            if (resp?.success) {
              Swal.fire({ icon: 'success', title: 'Aprobado', text: 'La solicitud fue aprobada.', confirmButtonColor: '#01643D' });
              loadIjumichRequests();
            } else {
              Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo aprobar la solicitud.' });
            }
          }, 'json').fail(() => Swal.fire('Error', 'Error de conexión.', 'error'));
        });
      })
      .on('click', '.btn-reject-ijumich', function () {
        const id   = $(this).data('id');
        const tipoMap = { carta_presentacion: 'carta de presentación', carta_liberacion: 'carta de liberación', carta_practicas_interno: 'carta de finalización de prácticas', carta_aceptacion_servicio: 'carta de aceptación de servicio social', carta_liberacion_interno: 'carta de liberación (interno)', reporte_parcial_1: 'reporte parcial 1', reporte_parcial_2: 'reporte parcial 2', reporte_parcial_3: 'reporte parcial 3', evaluacion_unidad_productiva: 'evaluación de la unidad receptora', evaluacion_global: 'evaluación global' };
        const tipo = tipoMap[$(this).data('tipo')] || $(this).data('tipo');
        Swal.fire({
          title: 'Rechazar solicitud',
          html: `Indica el motivo para rechazar esta <strong>${tipo}</strong>.`,
          input: 'textarea',
          inputLabel: 'Motivo del rechazo',
          inputPlaceholder: 'Escribe el motivo...',
          showCancelButton: true,
          confirmButtonText: 'Rechazar',
          cancelButtonText: 'Cancelar',
          confirmButtonColor: '#dc3545',
          inputValidator: v => { if (!v) return 'Debes ingresar un motivo'; }
        }).then(res => {
          if (!res.isConfirmed) return;
          $.post('controller/ajax/ajax.forms.php', {
            search: 'ijumich_requests',
            action: 'rejectRequest',
            id: id,
            comentario: res.value
          }, function (resp) {
            if (resp?.success) {
              Swal.fire({ icon: 'success', title: 'Rechazado', text: 'La solicitud fue rechazada.', confirmButtonColor: '#dc3545' });
              loadIjumichRequests();
            } else {
              Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo rechazar la solicitud.' });
            }
          }, 'json').fail(() => Swal.fire('Error', 'Error de conexión.', 'error'));
        });
      });

    // Solicitudes de capacitación
    $('.list-solicitudes-capacitacion')
      .on('click', '.btn-accept-solicitud-capacitacion', function () {
        const id = $(this).data('id');
        if (!id) return alert('ID de solicitud inválido.');

        Swal.fire({
          title: 'Aceptar solicitud',
          input: 'textarea',
          inputLabel: 'Comentario de aceptación',
          inputPlaceholder: 'Escriba un comentario (opcional)...',
          showCancelButton: true,
          confirmButtonText: 'Aceptar',
          cancelButtonText: 'Cancelar'
        }).then(res => {
          if (!res.isConfirmed) return;

          $.post('controller/ajax/ajax.forms.php', {
            search: 'reports',
            action: 'acceptSolicitudCapacitacion',
            idSolicitud: id,
            comentario: res.value || ''
          }, function (resp) {
            const ok = (resp === 'success') || (resp && resp.success === true);
            if (ok) {
              Swal.fire('Aceptada', (resp?.message || 'La solicitud fue aceptada.'), 'success');
              loadSolicitudesCapacitacion();
            } else {
              Swal.fire('Error', (resp?.message || 'No se pudo aceptar la solicitud.'), 'error');
            }
          }, 'json').fail(function () {
            Swal.fire('Error', 'Error al procesar la solicitud.', 'error');
          });
        });
      })
      .on('click', '.btn-reject-solicitud-capacitacion', function () {
        const id = $(this).data('id');
        if (!id) return alert('ID de solicitud inválido.');

        Swal.fire({
          title: 'Rechazar solicitud',
          input: 'textarea',
          inputLabel: 'Comentario de rechazo',
          inputPlaceholder: 'Escriba el motivo del rechazo...',
          showCancelButton: true,
          confirmButtonText: 'Enviar',
          cancelButtonText: 'Cancelar',
          inputValidator: value => {
            if (!value) return 'Debe ingresar un comentario';
          }
        }).then(res => {
          if (!res.isConfirmed) return;

          $.post('controller/ajax/ajax.forms.php', {
            search: 'reports',
            action: 'rejectSolicitudCapacitacion',
            idSolicitud: id,
            comentario: res.value
          }, function (resp) {
            const ok = (resp === 'success') || (resp && resp.success === true);
            if (ok) {
              Swal.fire('Rechazada', (resp?.message || 'La solicitud fue rechazada.'), 'success');
              loadSolicitudesCapacitacion();
            } else {
              Swal.fire('Error', (resp?.message || 'No se pudo rechazar la solicitud.'), 'error');
            }
          }, 'json').fail(function () {
            Swal.fire('Error', 'Error al procesar la solicitud.', 'error');
          });
        });
      });

  });
</script>
