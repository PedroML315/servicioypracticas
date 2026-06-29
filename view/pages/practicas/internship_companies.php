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
  border-radius: 1rem; color: #fff; padding: 1.75rem 2rem;
  margin-bottom: 1.5rem; display: flex; align-items: center; gap: 1.25rem;
  box-shadow: 0 4px 18px rgba(0,32,74,.18);
}
.ic-hero-icon {
  width: 64px; height: 64px; border-radius: 50%;
  background: rgba(255,255,255,.15);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.8rem; flex-shrink: 0;
}
.ic-hero h2 { font-size: 1.4rem; font-weight: 700; margin: 0 0 .2rem; }
.ic-hero p  { margin: 0; opacity: .88; font-size: .92rem; }
.ic-hero .hero-badge {
  margin-left: auto; background: rgba(255,255,255,.18);
  border: 1px solid rgba(255,255,255,.3); border-radius: 2rem;
  padding: .35rem .9rem; font-size: .8rem; white-space: nowrap;
}

/* ── Stat cards ── */
.ic-stat { border: 0; border-radius: 1rem; box-shadow: 0 2px 10px rgba(0,0,0,.07); }
.ic-stat .icon-box { width: 52px; height: 52px; border-radius: .75rem; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
.ic-stat .stat-val  { font-size: 1.75rem; font-weight: 700; line-height: 1; color: #01643D; }
.ic-stat .stat-lbl  { font-size: .78rem; color: #6c757d; }

/* ── Tabs ── */
.ic-tabs .nav-link { border-radius: .5rem .5rem 0 0; font-weight: 500; color: #495057; border: 0; padding: .6rem 1.1rem; }
.ic-tabs .nav-link.active { background: #fff; color: #01643D; border-bottom: 2.5px solid #01643D; font-weight: 600; }
.ic-tabs .nav-link:hover:not(.active) { background: rgba(1,100,61,.06); color: #01643D; }

/* ── Search bar ── */
.ic-search { border-radius: 2rem; border: 1.5px solid #dee2e6; padding: .45rem 1rem .45rem 2.4rem; font-size: .87rem; width: 100%; }
.ic-search:focus { border-color: #01643D; box-shadow: 0 0 0 3px rgba(1,100,61,.1); outline: none; }
.ic-search-wrap { position: relative; }
.ic-search-wrap .ic-search-icon { position: absolute; left: .85rem; top: 50%; transform: translateY(-50%); color: #adb5bd; font-size: .85rem; }

/* ── Organismo card ── */
.org-card { border: 0; border-radius: .85rem; box-shadow: 0 2px 8px rgba(0,0,0,.07); margin-bottom: .85rem; overflow: hidden; transition: box-shadow .2s; }
.org-card:hover { box-shadow: 0 4px 16px rgba(1,100,61,.14); }
.org-card-header { background: #fff; padding: .9rem 1.1rem; cursor: pointer; display: flex; align-items: center; gap: .75rem; border-bottom: 1px solid transparent; user-select: none; }
.org-card-header.open { border-bottom-color: #e9ecef; }
.org-card-header .org-avatar { width: 42px; height: 42px; border-radius: .6rem; background: linear-gradient(135deg, #c6db53, #01643D); display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 1rem; flex-shrink: 0; }
.org-card-header .org-name { font-weight: 600; font-size: .95rem; margin: 0; }
.org-card-header .org-meta { font-size: .78rem; color: #6c757d; margin: 0; }
.org-card-header .org-badges { margin-left: auto; display: flex; gap: .4rem; align-items: center; flex-shrink: 0; }
.org-card-header .chevron { color: #adb5bd; transition: transform .25s; font-size: .85rem; margin-left: .5rem; }
.org-card-header.open .chevron { transform: rotate(180deg); }

/* ── Estudiantes dentro del organismo ── */
.org-students { background: #f8faf9; padding: .75rem 1rem; }
.student-row { display: flex; align-items: center; gap: .75rem; padding: .55rem .6rem; border-radius: .5rem; background: #fff; margin-bottom: .4rem; box-shadow: 0 1px 3px rgba(0,0,0,.06); flex-wrap: wrap; }
.student-row:last-child { margin-bottom: 0; }
.student-avatar { width: 34px; height: 34px; border-radius: 50%; background: rgba(1,100,61,.1); color: #01643D; display: flex; align-items: center; justify-content: center; font-size: .8rem; font-weight: 700; flex-shrink: 0; }
.student-name { font-weight: 600; font-size: .87rem; margin: 0; line-height: 1.2; }
.student-sub  { font-size: .75rem; color: #6c757d; }
.student-meta { margin-left: auto; text-align: right; flex-shrink: 0; }

/* ── Proceso etapa (pasos del proceso) ── */
.proceso-steps { display: flex; gap: 0; align-items: flex-start; margin: .35rem 0; }
.p-step { display: flex; flex-direction: column; align-items: center; flex: 1; position: relative; }
.p-step .s-dot {
  width: 22px; height: 22px; border-radius: 50%; border: 2px solid #dee2e6;
  background: #fff; display: flex; align-items: center; justify-content: center;
  font-size: .6rem; z-index: 1;
}
.p-step.done .s-dot    { background: #01643D; border-color: #01643D; color: #fff; }
.p-step.current .s-dot { background: #c6db53; border-color: #2A7E5D; color: #00204a; }
.p-step .s-label { font-size: .6rem; text-align: center; color: #adb5bd; margin-top: .15rem; line-height: 1.1; max-width: 60px; }
.p-step.done .s-label    { color: #01643D; font-weight: 600; }
.p-step.current .s-label { color: #2A7E5D; font-weight: 600; }
.p-step::before { content:''; position:absolute; top:11px; right:50%; left:-50%; height:2px; background:#dee2e6; z-index:0; }
.p-step:first-child::before { display:none; }
.p-step.done::before { background:#01643D; }

/* ── Pills ── */
.pill { display: inline-flex; align-items: center; gap: .3em; font-size: .72rem; font-weight: 600; padding: .25em .65em; border-radius: 50rem; }
.pill-pending  { background: #fef3c7; color: #92400e; }
.pill-accepted { background: #d1fae5; color: #065f46; }
.pill-rejected { background: #fee2e2; color: #991b1b; }
.pill-new      { background: rgba(198,219,83,.3); color: #01643D; }

/* ── Tip ── */
.ic-tip {
  background: rgba(198,219,83,.18); border-left: 3px solid #01643D;
  border-radius: 0 .5rem .5rem 0; padding: .6rem .9rem; font-size: .78rem;
  color: #2A7E5D; display: flex; align-items: flex-start; gap: .5rem;
}

/* ── Filter active ── */
.ic-filter-btn.active { background: #01643D !important; color: #fff !important; border-color: #01643D !important; }

/* ── Btn ── */
.btn-unimo { background: #01643D; border-color: #01643D; color: #fff; }
.btn-unimo:hover { background: #2A7E5D; border-color: #2A7E5D; color: #fff; }
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
                    <div class="icon-box" style="background:rgba(1,100,61,.1);color:#01643D;"><i class="fas fa-building"></i></div>
                    <div><div class="stat-val" id="statOrganismos">—</div><div class="stat-lbl">Organismos activos</div></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl">
            <div class="card ic-stat p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="icon-box" style="background:rgba(198,219,83,.25);color:#01643D;"><i class="fas fa-user-check"></i></div>
                    <div><div class="stat-val" id="statEnPractica">—</div><div class="stat-lbl">En práctica activa</div></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl">
            <div class="card ic-stat p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="icon-box" style="background:#fef3c7;color:#92400e;"><i class="fas fa-user-clock"></i></div>
                    <div><div class="stat-val" id="statPendientes" style="color:#92400e;">—</div><div class="stat-lbl">Pendientes de respuesta</div></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl">
            <div class="card ic-stat p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="icon-box" style="background:#f3f4f6;color:#6b7280;"><i class="fas fa-user-slash"></i></div>
                    <div><div class="stat-val" id="statSinOrg" style="color:#6b7280;">—</div><div class="stat-lbl">Sin organismo</div></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl">
            <div class="card ic-stat p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="icon-box" style="background:#fee2e2;color:#991b1b;"><i class="fas fa-exclamation-triangle"></i></div>
                    <div><div class="stat-val" id="statConStrikes" style="color:#991b1b;">—</div><div class="stat-lbl">Con strikes</div></div>
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
        <a href="controller/practices/export_companies_excel.php"
           class="btn btn-sm btn-unimo rounded-pill px-3 shadow-sm"
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
                    <input type="text" class="form-control ic-search" id="searchOrganismos" placeholder="Buscar organismo, ciudad o giro…">
                </div>
                <button class="btn btn-sm rounded-pill ic-filter-btn active" style="background:#01643D;color:#fff;border:0;" data-orgfilter="all">Todos</button>
                <button class="btn btn-sm btn-outline-secondary rounded-pill ic-filter-btn" data-orgfilter="0">Sin aceptar</button>
                <button class="btn btn-sm rounded-pill ic-filter-btn" style="color:#01643D;border:1.5px solid #01643D;background:transparent;" data-orgfilter="1">Aceptados</button>
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
                <span>Estos alumnos están <strong>registrados como practicantes de organismo externo</strong> pero aún no tienen una práctica activa asignada. El semáforo muestra en qué paso se encuentran.</span>
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
      <div class="modal-header border-0 pb-2" style="background:linear-gradient(135deg,#c6db53 0%,#01643D 100%);border-radius:1rem 1rem 0 0;">
        <div class="d-flex align-items-center gap-3">
          <div style="width:46px;height:46px;border-radius:.6rem;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:#fff;">
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
        <div class="text-center py-5"><i class="fas fa-spinner fa-spin" style="color:#01643D;font-size:1.8rem;"></i></div>
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
            <div class="modal-body"><p id="icConfirmText" class="text-muted mb-0"></p></div>
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
      <div class="modal-header border-0 pb-2" style="background:linear-gradient(135deg,#00204a 0%,#01643D 100%);border-radius:1rem 1rem 0 0;">
        <div class="d-flex align-items-center gap-3">
          <div style="width:46px;height:46px;border-radius:.6rem;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:#fff;">
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
        <div class="text-center py-5"><i class="fas fa-spinner fa-spin" style="color:#01643D;font-size:1.8rem;"></i></div>
      </div>

      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>
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

<script src="view/assets/js/ajax/practices/companies_admin.js"></script>