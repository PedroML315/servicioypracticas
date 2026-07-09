<!-- Inputmask -->
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
  .bento-hero { grid-column: span 8; grid-row: span 2; display: flex; flex-direction: column; justify-content: center; background: linear-gradient(135deg, #01643D, #00204a); color: white; border: none; }
  .bento-kpi-1 { grid-column: span 2; grid-row: span 1; }
  .bento-kpi-2 { grid-column: span 2; grid-row: span 1; }
  .bento-kpi-3 { grid-column: span 2; grid-row: span 1; }
  .bento-kpi-4 { grid-column: span 2; grid-row: span 1; }
  .bento-attention-box { grid-column: span 12; grid-row: span 1; background: rgba(255,255,255,0.9); }

  @media (max-width: 1200px) {
    .bento-hero { grid-column: span 12; }
    .bento-kpi-1, .bento-kpi-2, .bento-kpi-3, .bento-kpi-4 { grid-column: span 3; }
  }
  @media (max-width: 768px) {
    .bento-kpi-1, .bento-kpi-2, .bento-kpi-3, .bento-kpi-4 { grid-column: span 6; }
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
  .hero-title { font-size: 3rem; font-weight: 900; letter-spacing: -0.03em; margin-bottom: 0.5rem; position: relative; z-index: 2; }
  .hero-subtitle { font-size: 1.25rem; font-weight: 300; opacity: 0.9; margin-bottom: 2rem; position: relative; z-index: 2; }
  
  .btn-neo {
    display: inline-flex; align-items: center; gap: 0.75rem;
    background: var(--brand-accent); color: var(--brand-dark);
    font-weight: 800; font-size: 1.1rem;
    padding: 1rem 2rem; border-radius: 100px; border: none;
    text-decoration: none; position: relative; z-index: 2;
    box-shadow: inset 0 -4px 0 rgba(0,0,0,0.1);
    transition: all 0.2s;
    width: fit-content;
  }
  .btn-neo:hover { transform: translateY(-2px); box-shadow: inset 0 -4px 0 rgba(0,0,0,0.1), 0 10px 20px -5px rgba(198, 219, 83, 0.4); color: var(--brand-dark); }
  .btn-neo:active { transform: translateY(2px); box-shadow: none; }

  /* KPIs */
  .kpi-title { font-size: 0.9rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-secondary); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
  .kpi-value { font-size: 3.5rem; font-weight: 900; line-height: 1; color: var(--text-primary); letter-spacing: -0.04em; }

  /* ATTENTION BOX */
  #attention-section { display: none; }
  .attention-title { font-weight: 800; font-size: 1.25rem; color: var(--text-primary); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
  .attention-list-neo { display: flex; gap: 1rem; flex-wrap: wrap; }
  .attention-item {
    background: white; border: 1px solid #e2e8f0; border-radius: 1rem;
    padding: 1rem 1.5rem; display: flex; align-items: center; gap: 1rem;
    flex: 1; min-width: 250px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
  }
  .attention-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
  .attention-body { flex: 1; display: flex; flex-direction: column; }
  .attention-label { font-size: 0.85rem; font-weight: 600; color: var(--text-secondary); }
  .attention-count { font-size: 1.25rem; font-weight: 900; line-height: 1.2; }
  .attention-cta { border: 2px solid; background: transparent; font-weight: 800; border-radius: 100px; padding: 0.25rem 1rem; font-size: 0.85rem; cursor: pointer; transition: all 0.2s; }
  .attention-cta:hover { background: #f8fafc; transform: scale(1.05); }

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
    border: var(--bento-border); border-radius: 2rem; padding: 3rem;
    box-shadow: var(--bento-shadow);
  }
  .pane-header { margin-bottom: 2rem; }
  .pane-title { font-size: 2rem; font-weight: 900; letter-spacing: -0.03em; margin: 0; color: var(--brand-dark); }
  .pane-desc { font-size: 1.1rem; color: var(--text-secondary); margin-top: 0.5rem; }

  /* OVERRIDE TABLAS */
  .table-responsive { background: white; border-radius: 1.5rem; padding: 1rem; border: 1px solid #e2e8f0; }
  table { width: 100%; border-collapse: collapse; }
  th { text-align: left; padding: 1rem; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-secondary); border-bottom: 2px solid #f1f5f9; }
  td { padding: 1rem; border-bottom: 1px solid #f8fafc; vertical-align: middle; }
</style>

<div class="ui-2026">

  <!-- BENTO GRID -->
  <div class="bento-grid">
    
    <!-- Hero Box (Rellenado por JS) -->
    <div class="bento-card bento-hero" id="org-hero-container">
        <!-- Contenido generado dinámicamente -->
    </div>

    <!-- KPI 1 -->
    <div class="bento-card bento-kpi-1 text-center d-flex flex-column justify-content-center align-items-center">
      <div class="kpi-title"><i class="fas fa-briefcase text-primary"></i> Vacantes</div>
      <div class="kpi-value" id="kpi-vacantes">0</div>
    </div>

    <!-- KPI 2 -->
    <div class="bento-card bento-kpi-2 text-center d-flex flex-column justify-content-center align-items-center">
      <div class="kpi-title"><i class="fas fa-users text-success"></i> Candidatos</div>
      <div class="kpi-value" id="kpi-candidatos">0</div>
    </div>

    <!-- KPI 3 -->
    <div class="bento-card bento-kpi-3 text-center d-flex flex-column justify-content-center align-items-center">
      <div class="kpi-title"><i class="fas fa-clock text-warning"></i> Asistencias</div>
      <div class="kpi-value" id="kpi-asistencias">0</div>
    </div>

    <!-- KPI 4 -->
    <div class="bento-card bento-kpi-4 text-center d-flex flex-column justify-content-center align-items-center">
      <div class="kpi-title"><i class="fas fa-file-alt text-danger"></i> Reportes</div>
      <div class="kpi-value" id="kpi-reportes">0</div>
    </div>

    <!-- Attention Box -->
    <div class="bento-card bento-attention-box" id="attention-section">
      <div class="attention-title"><i class="fas fa-exclamation-circle text-danger"></i> Requiere tu Atención</div>
      <div class="attention-list-neo" id="attention-list">
        <!-- Generado por JS -->
      </div>
    </div>

  </div>

  <!-- NEO TABS NAVIGATION -->
  <div class="neo-tabs-nav">
    <button class="neo-tab-btn active" data-target="tab-candidatos"><i class="fas fa-user-clock me-2"></i>Candidatos</button>
    <button class="neo-tab-btn" data-target="tab-asistencias"><i class="fas fa-calendar-check me-2"></i>Asistencias</button>
    <button class="neo-tab-btn" data-target="tab-reportes"><i class="fas fa-folder-open me-2"></i>Reportes</button>
    <button class="neo-tab-btn" data-target="tab-practicantes"><i class="fas fa-users-cog me-2"></i>Practicantes</button>
  </div>

  <!-- TABS CONTENT -->
  <div class="neo-tabs-content">
    
    <!-- Tab 1: Candidatos -->
    <div class="neo-tab-pane active" id="tab-candidatos">
      <div class="pane-card">
        <div class="pane-header">
          <h2 class="pane-title">Nuevos Candidatos</h2>
          <p class="pane-desc">Alumnos que desean formar parte de tu organización. Revisa su perfil y acepta sus postulaciones.</p>
        </div>
        <div id="postulaciones-wrap">
          <div class="text-center py-5 text-muted">
            <i class="fas fa-spinner fa-spin fa-2x mb-3"></i><p>Cargando candidatos...</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Tab 2: Asistencias -->
    <div class="neo-tab-pane" id="tab-asistencias">
      <div class="pane-card">
        <div class="pane-header">
          <h2 class="pane-title">Control de Asistencias</h2>
          <p class="pane-desc">Autoriza las horas que los practicantes han registrado día a día.</p>
        </div>
        <div id="assistancesTable"></div> <!-- Contenedor para CustomTable -->
      </div>
    </div>

    <!-- Tab 3: Reportes -->
    <div class="neo-tab-pane" id="tab-reportes">
      <div class="pane-card">
        <div class="pane-header">
          <h2 class="pane-title">Reportes Entregados</h2>
          <p class="pane-desc">Evalúa el desempeño de tus alumnos leyendo sus reportes parciales y finales.</p>
        </div>
        <div id="solicitudesTable"></div> <!-- Contenedor para CustomTable -->
      </div>
    </div>

    <!-- Tab 4: Practicantes -->
    <div class="neo-tab-pane" id="tab-practicantes">
      <div class="pane-card" style="border: 2px dashed #cbd5e1; background: rgba(255,255,255,0.4);">
        <div class="pane-header">
          <h2 class="pane-title text-muted">Gestión de Practicantes</h2>
          <p class="pane-desc">Administración de practicantes activos, históricos y filtrado por estatus.</p>
        </div>
        <div id="practicantesFilters" class="mb-3 d-flex flex-wrap gap-2"></div>
        <div id="historialAlumnosTable"></div> <!-- Contenedor para CustomTable -->
      </div>
    </div>

  </div>

</div>

<!-- Funcionalidad Tabs -->
<script>
  document.querySelectorAll('.neo-tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.neo-tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.neo-tab-pane').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById(btn.getAttribute('data-target')).classList.add('active');
    });
  });
</script>

<?php
include 'view/pages/practicas/modalSolPracticantes.php';
include 'view/pages/practicas/modalevaluaciones.php';
include 'view/pages/practicas/modalEvaluacionIntegral.php';
?>

<!-- Scripts de funcionalidad -->
<script src="view/assets/js/organismo/habilidades.js?v=20260706"></script>
<script src="view/assets/js/organismo/assistances.js"></script>
<script src="view/assets/js/organismo/solicitudes.js?v=20260706"></script>
<script src="view/assets/js/organismo/Reportes.js"></script>
<script src="view/assets/js/organismo/dashboard.js?v=20260706"></script>