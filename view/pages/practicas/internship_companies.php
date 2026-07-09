<?php
if (!in_array($_SESSION['user']['role'] ?? '', ['admin', 'admin_practicas'], true)) {
  header("Location: ./");
  exit;
}
?>
<style>
  /* ══════════════════════════════════════════════════════
   Internship Companies — Paleta Unimo
   navy #00204a | green #01643D | mid-green #2A7E5D | lime #c6db53
   ══════════════════════════════════════════════════════ */

  /* ── Hero ── */
  .ic-hero {
    background: linear-gradient(135deg, #c6db53 0%, #01643D 100%);
    border-radius: 1rem;
    color: #fff;
    padding: 1.75rem 2rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1.25rem;
    box-shadow: 0 4px 18px rgba(0, 32, 74, .18);
  }

  .ic-hero-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    flex-shrink: 0;
  }

  .ic-hero h2 {
    font-size: 1.4rem;
    font-weight: 700;
    margin: 0 0 .2rem;
  }

  .ic-hero p {
    margin: 0;
    opacity: .88;
    font-size: .92rem;
  }

  .ic-hero .hero-badge {
    margin-left: auto;
    background: rgba(255, 255, 255, .18);
    border: 1px solid rgba(255, 255, 255, .3);
    border-radius: 2rem;
    padding: .35rem .9rem;
    font-size: .8rem;
    white-space: nowrap;
  }

  /* ── Stat cards ── */
  .ic-stat {
    border: 0;
    border-radius: 1rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, .07);
  }

  .ic-stat .icon-box {
    width: 52px;
    height: 52px;
    border-radius: .75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
  }

  .ic-stat .stat-val {
    font-size: 1.75rem;
    font-weight: 700;
    line-height: 1;
    color: #01643D;
  }

  .ic-stat .stat-lbl {
    font-size: .78rem;
    color: #6c757d;
  }

  /* ── Tabs ── */
  .ic-tabs .nav-link {
    border-radius: .5rem .5rem 0 0;
    font-weight: 500;
    color: #495057;
    border: 0;
    padding: .6rem 1.1rem;
  }

  .ic-tabs .nav-link.active {
    background: #fff;
    color: #01643D;
    border-bottom: 2.5px solid #01643D;
    font-weight: 600;
  }

  .ic-tabs .nav-link:hover:not(.active) {
    background: rgba(1, 100, 61, .06);
    color: #01643D;
  }

  /* ── Search bar ── */
  .ic-search {
    border-radius: 2rem;
    border: 1.5px solid #dee2e6;
    padding: .45rem 1rem .45rem 2.4rem;
    font-size: .87rem;
    width: 100%;
  }

  .ic-search:focus {
    border-color: #01643D;
    box-shadow: 0 0 0 3px rgba(1, 100, 61, .1);
    outline: none;
  }

  .ic-search-wrap {
    position: relative;
  }

  .ic-search-wrap .ic-search-icon {
    position: absolute;
    left: .85rem;
    top: 50%;
    transform: translateY(-50%);
    color: #adb5bd;
    font-size: .85rem;
  }

  /* ── Organismo card ── */
  .org-card {
    border: 0;
    border-radius: .85rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, .07);
    margin-bottom: .85rem;
    overflow: visible; /* Changed from hidden to show dropdowns */
    transition: box-shadow .2s;
  }

  .org-card:hover {
    box-shadow: 0 4px 16px rgba(1, 100, 61, .14);
  }

  .org-card-header {
    background: #fff;
    padding: .9rem 1.1rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: .75rem;
    border-bottom: 1px solid transparent;
    user-select: none;
    border-radius: .85rem; /* Added border-radius */
  }

  .org-card-header.open {
    border-bottom-color: #e9ecef;
    border-radius: .85rem .85rem 0 0; /* Flat bottom when open */
  }

  .org-card-header .org-avatar {
    width: 42px;
    height: 42px;
    border-radius: .6rem;
    background: linear-gradient(135deg, #c6db53, #01643D);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: 1rem;
    flex-shrink: 0;
  }

  .org-card-header .org-name {
    font-weight: 600;
    font-size: .95rem;
    margin: 0;
  }

  .org-card-header .org-meta {
    font-size: .78rem;
    color: #6c757d;
    margin: 0;
  }

  .org-card-header .org-badges {
    margin-left: auto;
    display: flex;
    gap: .4rem;
    align-items: center;
    flex-shrink: 0;
  }

  .org-card-header .chevron {
    color: #adb5bd;
    transition: transform .25s;
    font-size: .85rem;
    margin-left: .5rem;
  }

  .org-card-header.open .chevron {
    transform: rotate(180deg);
  }

  /* ── Estudiantes dentro del organismo ── */
  .org-students {
    background: #f8faf9;
    padding: .75rem 1rem;
    border-radius: 0 0 .85rem .85rem;
  }

  .student-row {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: .55rem .6rem;
    border-radius: .5rem;
    background: #fff;
    margin-bottom: .4rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, .06);
    flex-wrap: wrap;
  }

  .student-row:last-child {
    margin-bottom: 0;
  }

  .student-avatar {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: rgba(1, 100, 61, .1);
    color: #01643D;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .8rem;
    font-weight: 700;
    flex-shrink: 0;
  }

  .student-name {
    font-weight: 600;
    font-size: .87rem;
    margin: 0;
    line-height: 1.2;
  }

  .student-sub {
    font-size: .75rem;
    color: #6c757d;
  }

  .student-meta {
    margin-left: auto;
    text-align: right;
    flex-shrink: 0;
  }

  /* ── Proceso etapa (pasos del proceso) ── */
  .proceso-steps {
    display: flex;
    gap: 0;
    align-items: flex-start;
    margin: .35rem 0;
  }

  .p-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
    position: relative;
  }

  .p-step .s-dot {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    border: 2px solid #dee2e6;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .6rem;
    z-index: 1;
  }

  .p-step.done .s-dot {
    background: #01643D;
    border-color: #01643D;
    color: #fff;
  }

  .p-step.current .s-dot {
    background: #c6db53;
    border-color: #2A7E5D;
    color: #00204a;
  }

  .p-step .s-label {
    font-size: .6rem;
    text-align: center;
    color: #adb5bd;
    margin-top: .15rem;
    line-height: 1.1;
    max-width: 60px;
  }

  .p-step.done .s-label {
    color: #01643D;
    font-weight: 600;
  }

  .p-step.current .s-label {
    color: #2A7E5D;
    font-weight: 600;
  }

  .p-step::before {
    content: '';
    position: absolute;
    top: 11px;
    right: 50%;
    left: -50%;
    height: 2px;
    background: #dee2e6;
    z-index: 0;
  }

  .p-step:first-child::before {
    display: none;
  }

  .p-step.done::before {
    background: #01643D;
  }

  /* ── Pills ── */
  .pill {
    display: inline-flex;
    align-items: center;
    gap: .3em;
    font-size: .72rem;
    font-weight: 600;
    padding: .25em .65em;
    border-radius: 50rem;
  }

  .pill-pending {
    background: #fef3c7;
    color: #92400e;
  }

  .pill-accepted {
    background: #d1fae5;
    color: #065f46;
  }

  .pill-rejected {
    background: #fee2e2;
    color: #991b1b;
  }

  .pill-new {
    background: rgba(198, 219, 83, .3);
    color: #01643D;
  }

  /* ── Tip ── */
  .ic-tip {
    background: rgba(198, 219, 83, .18);
    border-left: 3px solid #01643D;
    border-radius: 0 .5rem .5rem 0;
    padding: .6rem .9rem;
    font-size: .78rem;
    color: #2A7E5D;
    display: flex;
    align-items: flex-start;
    gap: .5rem;
  }

  /* ── Filter active ── */
  .ic-filter-btn.active {
    background: #01643D !important;
    color: #fff !important;
    border-color: #01643D !important;
  }

  .btn-unimo {
    background: #01643D;
    border-color: #01643D;
    color: #fff;
  }

  .btn-unimo:hover {
    background: #2A7E5D;
    border-color: #2A7E5D;
    color: #fff;
  }

  /* ── Semáforos de Evaluación ── */
  .semaforo-dual {
    display: flex;
    gap: 4px;
    align-items: center;
    background: #f8fafc;
    padding: 3px 6px;
    border-radius: 20px;
    border: 1px solid #e2e8f0;
  }

  .semaforo-dot {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    display: inline-block;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
  }

  .sem-green {
    background: #22c55e;
    border: 1px solid #16a34a;
  }

  .sem-yellow {
    background: #eab308;
    border: 1px solid #ca8a04;
  }

  .sem-red {
    background: #ef4444;
    border: 1px solid #dc2626;
  }

  .sem-gray {
    background: #9ca3af;
    border: 1px solid #6b7280;
    opacity: 0.5;
  }
</style>

<div class="container-fluid px-4 pt-3 pb-5">

  <!-- ════════════════════════════════════════
         1. Hero
    ════════════════════════════════════════ -->
  <div class="ic-hero">
    <div class="ic-hero-icon"><i class="fas fa-building"></i></div>
    <div>
      <h2>Organismos Receptores y sus Practicantes</h2>
      <p>Vista unificada: organismos externos, sus alumnos postulados y el estado de cada proceso.</p>
    </div>
    <span class="hero-badge"><i class="fas fa-shield-alt me-1"></i>Administración</span>
  </div>

  <!-- ════════════════════════════════════════
         2. Stat cards
    ════════════════════════════════════════ -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-xl">
      <div class="card ic-stat p-3">
        <div class="d-flex align-items-center gap-3">
          <div class="icon-box" style="background:rgba(1,100,61,.1);color:#01643D;"><i class="fas fa-building"></i>
          </div>
          <div>
            <div class="stat-val" id="statOrganismos">—</div>
            <div class="stat-lbl">Organismos activos</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-xl">
      <div class="card ic-stat p-3">
        <div class="d-flex align-items-center gap-3">
          <div class="icon-box" style="background:rgba(198,219,83,.25);color:#01643D;"><i class="fas fa-user-check"></i>
          </div>
          <div>
            <div class="stat-val" id="statEnPractica">—</div>
            <div class="stat-lbl">En práctica activa</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-xl">
      <div class="card ic-stat p-3">
        <div class="d-flex align-items-center gap-3">
          <div class="icon-box" style="background:#fef3c7;color:#92400e;"><i class="fas fa-user-clock"></i></div>
          <div>
            <div class="stat-val" id="statPendientes" style="color:#92400e;">—</div>
            <div class="stat-lbl">Pendientes de respuesta</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-xl">
      <div class="card ic-stat p-3">
        <div class="d-flex align-items-center gap-3">
          <div class="icon-box" style="background:#f3f4f6;color:#6b7280;"><i class="fas fa-user-slash"></i></div>
          <div>
            <div class="stat-val" id="statSinOrg" style="color:#6b7280;">—</div>
            <div class="stat-lbl">Sin organismo</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-xl">
      <div class="card ic-stat p-3">
        <div class="d-flex align-items-center gap-3">
          <div class="icon-box" style="background:#fee2e2;color:#991b1b;"><i class="fas fa-exclamation-triangle"></i>
          </div>
          <div>
            <div class="stat-val" id="statConStrikes" style="color:#991b1b;">—</div>
            <div class="stat-lbl">Con strikes</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ════════════════════════════════════════
         3. Tabs
    ════════════════════════════════════════ -->
  <!-- ════════════════════════════════════════
         Barra de acciones (exportar)
    ════════════════════════════════════════ -->
  <div class="d-flex justify-content-end mb-2">
    <a href="controller/practices/export_companies_excel.php" class="btn btn-sm btn-unimo rounded-pill px-3 shadow-sm"
      title="Descargar Excel con organismos y alumnos">
      <i class="fas fa-file-excel me-1"></i> Exportar Excel
    </a>
  </div>

  <ul class="nav nav-tabs ic-tabs border-bottom mb-0" id="icTabs">
    <li class="nav-item">
      <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabOrganismos">
        <i class="fas fa-building me-1"></i> Organismos
        <span class="badge ms-1" style="background:#c6db53;" id="badgeOrgs">0</span>
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabSinOrg">
        <i class="fas fa-user-slash me-1"></i> Sin organismo
        <span class="badge ms-1 bg-warning text-dark" id="badgeSinOrg">0</span>
      </button>
    </li>
  </ul>

  <div class="tab-content bg-white rounded-bottom shadow-sm">

    <!-- ── TAB: ORGANISMOS ── -->
    <div class="tab-pane fade show active p-4" id="tabOrganismos">
      <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="ic-search-wrap flex-grow-1" style="max-width:360px;">
          <i class="fas fa-search ic-search-icon"></i>
          <input type="text" class="form-control ic-search" id="searchOrganismos"
            placeholder="Buscar organismo, ciudad o giro…">
        </div>
        <button class="btn btn-sm rounded-pill ic-filter-btn active" style="background:#01643D;color:#fff;border:0;"
          data-orgfilter="all">Todos</button>
        <button class="btn btn-sm btn-outline-secondary rounded-pill ic-filter-btn" data-orgfilter="0">Sin
          aceptar</button>
        <button class="btn btn-sm rounded-pill ic-filter-btn"
          style="color:#01643D;border:1.5px solid #01643D;background:transparent;" data-orgfilter="1">Aceptados</button>
      </div>
      <div id="orgsContainer">
        <div class="text-center py-5 text-muted">
          <i class="fas fa-spinner fa-spin me-2" style="color:#01643D;"></i>Cargando organismos…
        </div>
      </div>
    </div>

    <!-- ── TAB: SIN ORGANISMO ── -->
    <div class="tab-pane fade p-4" id="tabSinOrg">
      <div class="ic-tip mb-3">
        <i class="fas fa-info-circle mt-1" style="flex-shrink:0;"></i>
        <span>Estos alumnos están <strong>registrados como practicantes de organismo externo</strong> pero aún no tienen
          una práctica activa asignada. El semáforo muestra en qué paso se encuentran.</span>
      </div>
      <div class="ic-search-wrap mb-3" style="max-width:360px;">
        <i class="fas fa-search ic-search-icon"></i>
        <input type="text" class="form-control ic-search" id="searchSinOrg" placeholder="Buscar alumno o matrícula…">
      </div>
      <div id="sinOrgContainer">
        <div class="text-center py-5 text-muted">
          <i class="fas fa-spinner fa-spin me-2" style="color:#01643D;"></i>Cargando…
        </div>
      </div>
    </div>

  </div>
</div>

<!-- ════ MODAL DATOS ORGANISMO ════ -->
<div class="modal fade" id="icDatosModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content border-0 rounded-4 shadow">
      <div class="modal-header border-0 pb-2"
        style="background:linear-gradient(135deg,#c6db53 0%,#01643D 100%);border-radius:1rem 1rem 0 0;">
        <div class="d-flex align-items-center gap-3">
          <div
            style="width:46px;height:46px;border-radius:.6rem;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:#fff;">
            <i class="fas fa-building"></i>
          </div>
          <div>
            <h5 class="modal-title fw-bold text-white mb-0" id="icDatosModalTitle">Datos del Organismo</h5>
            <small class="text-white opacity-75" id="icDatosModalSub"></small>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4" id="icDatosModalBody">
        <div class="text-center py-5"><i class="fas fa-spinner fa-spin" style="color:#01643D;font-size:1.8rem;"></i>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- ════ MODAL CONFIRMAR ACCIÓN ════ -->
<div class="modal fade" id="icConfirmModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content border-0 rounded-4 shadow">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" id="icConfirmTitle">Confirmar</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p id="icConfirmText" class="text-muted mb-0"></p>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-unimo rounded-pill px-4" id="icConfirmBtn">Confirmar</button>
      </div>
    </div>
  </div>
</div>


<!-- ═══════════════════════════════════════════════════════
     MODAL: HISTORIAL DE SOLICITUDES DE PRACTICANTES
     ═══════════════════════════════════════════════════════ -->
<div class="modal fade" id="icSolicitudesModal" tabindex="-1" aria-labelledby="icSolicitudesModalLabel">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content border-0 rounded-4 shadow">

      <!-- Header -->
      <div class="modal-header border-0 pb-2"
        style="background:linear-gradient(135deg,#00204a 0%,#01643D 100%);border-radius:1rem 1rem 0 0;">
        <div class="d-flex align-items-center gap-3">
          <div
            style="width:46px;height:46px;border-radius:.6rem;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:#fff;">
            <i class="fas fa-file-alt"></i>
          </div>
          <div>
            <h5 class="modal-title fw-bold text-white mb-0" id="icSolicitudesModalLabel">Historial de Solicitudes</h5>
            <small class="text-white opacity-75" id="icSolicitudesModalSub">Organismo receptor</small>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
      </div>

      <!-- Stats bar -->
      <div class="px-4 pt-3 pb-0" id="icSolicitudesStats" style="display:none;">
        <div class="row g-2 mb-3">
          <div class="col-6 col-md">
            <div class="p-2 rounded-3 text-center" style="background:#f0faf5;border:1px solid #c3e6d0;">
              <div style="font-size:1.4rem;font-weight:700;color:#01643D;" id="ss-total">0</div>
              <div style="font-size:.7rem;color:#6c757d;">Total solicitudes</div>
            </div>
          </div>
          <div class="col-6 col-md">
            <div class="p-2 rounded-3 text-center" style="background:#d1fae5;border:1px solid #a7f3d0;">
              <div style="font-size:1.4rem;font-weight:700;color:#065f46;" id="ss-aceptadas">0</div>
              <div style="font-size:.7rem;color:#065f46;">Aceptadas</div>
            </div>
          </div>
          <div class="col-6 col-md">
            <div class="p-2 rounded-3 text-center" style="background:#fee2e2;border:1px solid #fca5a5;">
              <div style="font-size:1.4rem;font-weight:700;color:#991b1b;" id="ss-rechazadas">0</div>
              <div style="font-size:.7rem;color:#991b1b;">Rechazadas</div>
            </div>
          </div>
          <div class="col-6 col-md">
            <div class="p-2 rounded-3 text-center" style="background:#fef3c7;border:1px solid #fcd34d;">
              <div style="font-size:1.4rem;font-weight:700;color:#92400e;" id="ss-pendientes">0</div>
              <div style="font-size:.7rem;color:#92400e;">Pendientes</div>
            </div>
          </div>
          <div class="col-6 col-md">
            <div class="p-2 rounded-3 text-center" style="background:#ede9fe;border:1px solid #c4b5fd;">
              <div style="font-size:1.4rem;font-weight:700;color:#5b21b6;" id="ss-practicantes">0</div>
              <div style="font-size:.7rem;color:#5b21b6;">Practicantes solicitados</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Body -->
      <div class="modal-body p-4" id="icSolicitudesBody">
        <div class="text-center py-5"><i class="fas fa-spinner fa-spin" style="color:#01643D;font-size:1.8rem;"></i>
        </div>
      </div>

      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>
<?php /* ── Estilos "neo" (glassmorphism) para modales de organismo ── */ ?>
<style>
  .ic-neo-content {
    background: rgba(255, 255, 255, .9);
    backdrop-filter: blur(30px);
    -webkit-backdrop-filter: blur(30px);
    border: 1px solid rgba(255, 255, 255, .5);
    border-radius: 1.75rem;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, .25);
    overflow: hidden
  }

  .ic-neo-header {
    padding: 1.6rem 2rem;
    border-bottom: 1px solid rgba(0, 0, 0, .05);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-shrink: 0
  }

  .ic-neo-titlewrap {
    display: flex;
    align-items: center;
    gap: 1rem;
    min-width: 0
  }

  .ic-neo-ico {
    width: 54px;
    height: 54px;
    border-radius: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    flex-shrink: 0;
    background: var(--neo-soft);
    color: var(--neo-accent)
  }

  .ic-neo-title {
    font-weight: 900;
    font-size: 1.3rem;
    letter-spacing: -.02em;
    color: #1e293b;
    margin: 0;
    line-height: 1.15
  }

  .ic-neo-sub {
    font-size: .8rem;
    color: #64748b;
    font-weight: 700;
    margin-top: .15rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis
  }

  .ic-neo-close {
    background: #f1f5f9;
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all .2s;
    flex-shrink: 0
  }

  .ic-neo-close:hover {
    background: #e2e8f0;
    color: #0f172a;
    transform: rotate(90deg)
  }

  .ic-neo-body {
    padding: 2rem;
    background: #f8fafc;
    flex: 1 1 auto;
    overflow-y: auto;
    min-height: 0
  }

  .modal-dialog-scrollable .ic-neo-content {
    max-height: 100%;
    overflow: hidden;
    display: flex;
    flex-direction: column
  }

  .ic-neo-banner {
    display: flex;
    align-items: flex-start;
    gap: .85rem;
    background: var(--neo-soft);
    border: 1px solid var(--neo-border);
    border-radius: 1.1rem;
    padding: 1rem 1.15rem;
    color: #334155;
    font-size: .9rem;
    line-height: 1.45;
    margin-bottom: 1.5rem
  }

  .ic-neo-banner i {
    color: var(--neo-accent);
    font-size: 1.05rem;
    margin-top: .15rem;
    flex-shrink: 0
  }

  .ic-neo-card {
    background: #fff;
    border: 1px solid #eef2f7;
    border-radius: 1.25rem;
    box-shadow: 0 6px 16px rgba(15, 23, 42, .04)
  }

  .ic-neo-card-pad {
    padding: 1.5rem
  }

  .ic-neo-label {
    font-size: .76rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #475569;
    margin-bottom: .6rem;
    display: flex;
    align-items: center;
    gap: .5rem
  }

  .ic-neo-label i {
    color: var(--neo-accent)
  }

  .ic-neo-input {
    background: #fff;
    border: 1px solid #cbd5e1;
    border-radius: 1rem;
    padding: .9rem 1.1rem;
    font-size: .95rem;
    color: #0f172a;
    font-weight: 500;
    width: 100%;
    transition: all .25s;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, .02)
  }

  .ic-neo-input:focus {
    outline: none;
    border-color: var(--neo-accent);
    box-shadow: 0 0 0 4px var(--neo-soft)
  }

  .ic-neo-hint {
    font-size: .78rem;
    color: #94a3b8;
    margin-top: .5rem;
    font-weight: 500
  }

  .ic-neo-footer {
    padding: 1.25rem 2rem;
    background: rgba(255, 255, 255, .85);
    border-top: 1px solid rgba(0, 0, 0, .05);
    display: flex;
    justify-content: flex-end;
    gap: .75rem;
    flex-shrink: 0
  }

  .ic-neo-btn-ghost {
    background: #fff;
    border: 1px solid #e2e8f0;
    color: #334155;
    font-weight: 800;
    border-radius: 100px;
    padding: .7rem 1.6rem;
    transition: all .2s
  }

  .ic-neo-btn-ghost:hover {
    background: #f8fafc;
    border-color: #cbd5e1
  }

  .ic-neo-btn {
    border: none;
    color: #fff;
    font-weight: 800;
    border-radius: 100px;
    padding: .7rem 1.9rem;
    background: var(--neo-accent);
    box-shadow: inset 0 -3px 0 rgba(0, 0, 0, .12);
    transition: all .2s;
    display: inline-flex;
    align-items: center;
    gap: .5rem
  }

  .ic-neo-btn:hover {
    transform: translateY(-2px);
    box-shadow: inset 0 -3px 0 rgba(0, 0, 0, .12), 0 12px 24px -6px var(--neo-strong)
  }

  .ic-neo-btn:disabled {
    opacity: .7;
    transform: none
  }

  /* Tabla de campos */
  #tablaCamposRechazo {
    margin: 0;
    border-collapse: separate;
    border-spacing: 0
  }

  #tablaCamposRechazo thead th {
    background: transparent;
    border: none;
    border-bottom: 2px solid #eef2f7;
    font-size: .72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #94a3b8;
    padding: .85rem 1rem
  }

  #tablaCamposRechazo tbody td {
    border: none;
    border-bottom: 1px solid #f1f5f9;
    padding: .85rem 1rem;
    vertical-align: middle
  }

  #tablaCamposRechazo tbody tr:last-child td {
    border-bottom: none
  }

  #tablaCamposRechazo tbody tr:hover {
    background: #fafbfc
  }

  #tablaCamposRechazo .field-inputs .form-select,
  #tablaCamposRechazo .field-inputs .form-control {
    border-radius: .7rem;
    border-color: #e2e8f0;
    font-size: .85rem
  }

  #tablaCamposRechazo .field-cb {
    border-radius: .4rem;
    border: 1.5px solid #cbd5e1;
    cursor: pointer
  }

  #tablaCamposRechazo .field-cb:checked {
    background-color: var(--neo-accent);
    border-color: var(--neo-accent)
  }

  @media (max-width:576px) {
    .ic-neo-header {
      padding: 1.25rem
    }

    .ic-neo-body {
      padding: 1.25rem
    }

    .ic-neo-footer {
      padding: 1rem 1.25rem
    }
  }
</style>

<!-- ════ MODAL OBSERVACIONES ════ -->
<div class="modal fade" id="icRechazoModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content ic-neo-content"
      style="--neo-accent:#d97706;--neo-soft:rgba(217,119,6,.1);--neo-border:rgba(217,119,6,.25);--neo-strong:rgba(217,119,6,.45)">
      <div class="ic-neo-header">
        <div class="ic-neo-titlewrap">
          <div class="ic-neo-ico"><i class="fas fa-comment-dots"></i></div>
          <div style="min-width:0">
            <h5 class="ic-neo-title">Observaciones a la Solicitud</h5>
            <div class="ic-neo-sub" id="icRechazoModalSub"></div>
          </div>
        </div>
        <button type="button" class="ic-neo-close" data-bs-dismiss="modal"><i class="fas fa-times"></i></button>
      </div>
      <div class="ic-neo-body">
        <form id="formRechazoOrganismo">
          <input type="hidden" id="rechazoOrgId" name="id">

          <div class="ic-neo-banner">
            <i class="fas fa-info-circle"></i>
            <div>Marca los campos o documentos que sean incorrectos y detalla el motivo. Se enviará un correo al
              organismo con un enlace temporal para que corrija su información.</div>
          </div>

          <!-- Motivo General -->
          <div class="ic-neo-card ic-neo-card-pad mb-4">
            <label class="ic-neo-label" for="rechazoMotivoGeneral"><i class="fas fa-comment-alt"></i>Motivo General
              (Obligatorio)</label>
            <textarea class="ic-neo-input" name="motivo_general" id="rechazoMotivoGeneral" rows="3"
              placeholder="Ej. Tu solicitud requiere correcciones en los documentos adjuntos y en la dirección..."
              required></textarea>
          </div>

          <!-- Tabla de campos -->
          <div class="ic-neo-card">
            <div class="table-responsive" style="border-radius:1.25rem">
              <table class="table align-middle" id="tablaCamposRechazo">
                <thead>
                  <tr>
                    <th style="width:40px" class="text-center"><i class="fas fa-check-square"></i></th>
                    <th style="width:22%">Campo / Documento</th>
                    <th style="width:30%">Valor actual</th>
                    <th>Motivo del error y observaciones</th>
                  </tr>
                </thead>
                <tbody>
                  <!-- Llenado por JS -->
                </tbody>
              </table>
            </div>
          </div>
        </form>
      </div>
      <div class="ic-neo-footer">
        <button type="button" class="ic-neo-btn-ghost" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="ic-neo-btn" id="btnConfirmarRechazo">
          <i class="fas fa-paper-plane"></i> Enviar Observaciones
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ════ MODAL NO PROCEDENTE (Rechazo definitivo) ════ -->
<div class="modal fade" id="icNoProcedenteModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content ic-neo-content"
      style="--neo-accent:#dc3545;--neo-soft:rgba(220,53,69,.1);--neo-border:rgba(220,53,69,.25);--neo-strong:rgba(220,53,69,.45)">
      <div class="ic-neo-header">
        <div class="ic-neo-titlewrap">
          <div class="ic-neo-ico"><i class="fas fa-ban"></i></div>
          <div style="min-width:0">
            <h5 class="ic-neo-title">Marcar como No Procedente</h5>
            <div class="ic-neo-sub" id="icNoProcedenteSub"></div>
          </div>
        </div>
        <button type="button" class="ic-neo-close" data-bs-dismiss="modal"><i class="fas fa-times"></i></button>
      </div>
      <div class="ic-neo-body">
        <input type="hidden" id="noProcedenteOrgId">
        <div class="ic-neo-banner">
          <i class="fas fa-exclamation-triangle"></i>
          <div>Esta acción <strong>cierra definitivamente</strong> el proceso de vinculación del organismo. Se enviará
            un correo profesional informando que su solicitud no puede continuar. Esta resolución no abre un enlace de
            corrección.</div>
        </div>
        <div class="ic-neo-card ic-neo-card-pad">
          <label class="ic-neo-label" for="noProcedenteMotivo"><i class="fas fa-comment-alt"></i>Motivo de la resolución
            (Obligatorio)</label>
          <textarea class="ic-neo-input" id="noProcedenteMotivo" rows="4"
            placeholder="Ej. La documentación presentada no cumple con los requisitos institucionales para establecer un convenio..."
            required></textarea>
          <div class="ic-neo-hint">Este motivo se incluirá textualmente en el correo enviado al organismo.</div>
        </div>
      </div>
      <div class="ic-neo-footer">
        <button type="button" class="ic-neo-btn-ghost" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="ic-neo-btn" id="btnConfirmarNoProcedente">
          <i class="fas fa-ban"></i> Confirmar No Procedente
        </button>
      </div>
</div>
</div>
</div>

<!-- ════ MODAL EVALUACIONES INTEGRALES ════ -->
<div class="modal fade" id="icEvaluacionesModal" tabindex="-1" aria-labelledby="icEvaluacionesModalLabel">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content border-0 rounded-4 shadow">
      <div class="modal-header border-0 pb-2"
        style="background:linear-gradient(135deg,#1e3a5f 0%,#01643D 100%);border-radius:1rem 1rem 0 0;">
        <div class="d-flex align-items-center gap-3">
          <div
            style="width:46px;height:46px;border-radius:.6rem;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:#fff;">
            <i class="fas fa-clipboard-check"></i>
          </div>
          <div>
            <h5 class="modal-title fw-bold text-white mb-0" id="icEvaluacionesModalLabel">Evaluaciones Integrales</h5>
            <small class="text-white opacity-75" id="icEvaluacionesModalSub"></small>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4" id="icEvaluacionesBody">
        <div class="text-center py-5"><i class="fas fa-spinner fa-spin" style="color:#01643D;font-size:1.8rem;"></i></div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<style>
  /* ── Dropdown de acciones del organismo ── */
  .org-actions-dropdown {
    position: relative;
    display: inline-block;
  }
  .org-actions-dropdown .btn-actions-toggle {
    background: #01643D;
    color: #fff;
    border: none;
    border-radius: 50rem;
    padding: .35rem .85rem;
    font-size: .78rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    transition: all .2s;
    white-space: nowrap;
  }
  .org-actions-dropdown .btn-actions-toggle:hover {
    background: #2A7E5D;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(1, 100, 61, .25);
  }
  .org-actions-dropdown .actions-menu {
    display: none;
    position: absolute;
    right: 0;
    top: calc(100% + 6px);
    min-width: 230px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: .85rem;
    box-shadow: 0 12px 32px rgba(0,0,0,.12);
    z-index: 1050;
    padding: .4rem 0;
    animation: fadeInMenu .15s ease-out;
  }
  @keyframes fadeInMenu {
    from { opacity: 0; transform: translateY(-6px); }
    to { opacity: 1; transform: translateY(0); }
  }
  .org-actions-dropdown.open .actions-menu {
    display: block;
  }
  .actions-menu .action-item {
    display: flex;
    align-items: center;
    gap: .6rem;
    padding: .55rem 1rem;
    font-size: .8rem;
    font-weight: 500;
    color: #334155;
    cursor: pointer;
    transition: background .15s;
    border: none;
    background: transparent;
    width: 100%;
    text-align: left;
    white-space: nowrap;
  }
  .actions-menu .action-item:hover {
    background: #f0faf5;
    color: #01643D;
  }
  .actions-menu .action-item i {
    width: 18px;
    text-align: center;
    font-size: .82rem;
    flex-shrink: 0;
  }
  .actions-menu .action-divider {
    height: 1px;
    background: #f1f5f9;
    margin: .3rem 0;
  }
  .actions-menu .action-item.danger {
    color: #dc3545;
  }
  .actions-menu .action-item.danger:hover {
    background: #fef2f2;
    color: #b91c1c;
  }
  .actions-menu .action-item.warning {
    color: #b45309;
  }
  .actions-menu .action-item.warning:hover {
    background: #fffbeb;
    color: #92400e;
  }
  .actions-menu .action-item.success {
    color: #01643D;
  }
  .actions-menu .action-item.success:hover {
    background: #f0faf5;
    color: #015a36;
  }

  /* ── Evaluaciones modal ── */
  .eval-card {
    border: 1px solid #e2e8f0;
    border-radius: .85rem;
    overflow: hidden;
    margin-bottom: 1rem;
  }
  .eval-card-header {
    padding: .75rem 1rem;
    font-weight: 700;
    font-size: .82rem;
    display: flex;
    align-items: center;
    gap: .5rem;
  }
  .eval-card-header.alumno {
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    color: #1e40af;
  }
  .eval-card-header.empresa {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    color: #166534;
  }
  .eval-card-body {
    padding: 1rem;
  }
  .eval-likert {
    display: flex;
    align-items: center;
    gap: .5rem;
    padding: .4rem 0;
    border-bottom: 1px solid #f1f5f9;
  }
  .eval-likert:last-child { border-bottom: 0; }
  .eval-likert .eval-bar-bg {
    flex: 1;
    height: 8px;
    background: #f1f5f9;
    border-radius: 99px;
    overflow: hidden;
  }
  .eval-likert .eval-bar-fill {
    height: 100%;
    border-radius: 99px;
    transition: width .3s;
  }
  .eval-likert .eval-val {
    font-weight: 700;
    font-size: .82rem;
    min-width: 30px;
    text-align: right;
  }
  .eval-text-response {
    background: #f8fafc;
    border-radius: .6rem;
    padding: .6rem .8rem;
    font-size: .83rem;
    color: #334155;
    margin-top: .4rem;
    border-left: 3px solid #01643D;
  }
</style>

<script src="view/assets/js/ajax/practices/companies_admin.js"></script>