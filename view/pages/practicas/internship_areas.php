<?php
if (!in_array($_SESSION['user']['role'] ?? '', ['admin', 'admin_practicas'], true)) {
    header("Location: ./");
    exit;
}
$users = FormsController::ctrSearchUsers(null);
?>
<style>
/* ══════════════════════════════════════════════════════
   Internship Areas — Paleta Unimo
   navy #00204a | green #01643D | mid-green #2A7E5D | lime #c6db53
   ══════════════════════════════════════════════════════ */

/* ── Hero banner ── */
.ia-hero {
  background: linear-gradient(135deg, #c6db53 0%, #01643D 100%);
  border-radius: 1rem;
  color: #fff;
  padding: 1.75rem 2rem;
  margin-bottom: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1.25rem;
  box-shadow: 0 4px 18px rgba(0,32,74,.18);
}
.ia-hero-icon {
  width: 64px; height: 64px; border-radius: 50%;
  background: rgba(255,255,255,.15);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.8rem; flex-shrink: 0;
}
.ia-hero h2 { font-size: 1.4rem; font-weight: 700; margin: 0 0 .2rem; }
.ia-hero p  { margin: 0; opacity: .88; font-size: .92rem; }
.ia-hero .hero-badge {
  margin-left: auto;
  background: rgba(255,255,255,.18);
  border: 1px solid rgba(255,255,255,.3);
  border-radius: 2rem; padding: .35rem .9rem;
  font-size: .8rem; white-space: nowrap;
}

/* ── Stat cards ── */
.ia-stat { border: 0; border-radius: 1rem; box-shadow: 0 2px 10px rgba(0,0,0,.07); }
.ia-stat .icon-box { width: 52px; height: 52px; border-radius: .75rem; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
.ia-stat .stat-val  { font-size: 1.75rem; font-weight: 700; line-height: 1; color: #01643D; }
.ia-stat .stat-lbl  { font-size: .78rem; color: #6c757d; }

/* ── Tab nav ── */
.ia-tabs .nav-link { border-radius: .5rem .5rem 0 0; font-weight: 500; color: #495057; border: 0; padding: .6rem 1.1rem; }
.ia-tabs .nav-link.active { background: #fff; color: #01643D; border-bottom: 2.5px solid #01643D; font-weight: 600; }
.ia-tabs .nav-link:hover:not(.active) { background: rgba(1,100,61,.06); color: #01643D; }

/* ── Tables ── */
.ia-table thead th { background: #f8f9fa; font-size: .78rem; text-transform: uppercase; letter-spacing: .04em; color: #6c757d; border-bottom: 2px solid #dee2e6; }
.ia-table tbody tr { transition: background .15s; }
.ia-table tbody tr:hover { background: rgba(1,100,61,.05); }

/* ── Status pills ── */
.pill { display: inline-flex; align-items: center; gap: .3em; font-size: .75rem; font-weight: 600; padding: .3em .75em; border-radius: 50rem; }
.pill-open     { background: #d1fae5; color: #065f46; }
.pill-closed   { background: #f3f4f6; color: #6b7280; }
.pill-pending  { background: #fef3c7; color: #92400e; }
.pill-accepted { background: #d1fae5; color: #065f46; }
.pill-rejected { background: #fee2e2; color: #991b1b; }
.pill-finished { background: #d1fae5; color: #059669; border: 1.5px solid #059669; }

/* ── Area cards (grid) ── */
.area-card { border: 0; border-radius: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,.07); transition: transform .2s, box-shadow .2s; }
.area-card:hover { transform: translateY(-3px); box-shadow: 0 6px 18px rgba(1,100,61,.15); }
.area-card .card-header { border-radius: 1rem 1rem 0 0; border-bottom: 1px solid rgba(0,0,0,.06); background: #fff; }
.area-card .cupo-bar { height: 6px; border-radius: 3px; background: #e9ecef; overflow: hidden; }
.area-card .cupo-fill { height: 100%; border-radius: 3px; background: linear-gradient(90deg, #01643D, #c6db53); transition: width .4s; }

/* ── Action btns ── */
.btn-icon { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: .4rem; font-size: .82rem; }

/* ── Context tip ── */
.ia-tip {
  background: rgba(198,219,83,.18);
  border-left: 3px solid #01643D;
  border-radius: 0 .5rem .5rem 0;
  padding: .6rem .9rem; font-size: .78rem; color: #2A7E5D;
  margin-bottom: 1rem; display: flex; align-items: flex-start; gap: .5rem;
}

/* ── Nueva área btn ── */
.btn-unimo { background: #01643D; border-color: #01643D; color: #fff; }
.btn-unimo:hover { background: #2A7E5D; border-color: #2A7E5D; color: #fff; }
</style>

<div class="container-fluid px-4 pt-3 pb-5">

    <!-- ════════════════════════════════════════
         1. Hero banner
    ════════════════════════════════════════ -->
    <div class="ia-hero">
        <div class="ia-hero-icon">
            <i class="fas fa-building-columns"></i>
        </div>
        <div>
            <h2>Áreas Internas de Prácticas</h2>
            <p>Gestiona áreas disponibles, cupos y postulaciones de alumnos con prácticas <em>tipo universidad</em>.</p>
        </div>
        <span class="hero-badge"><i class="fas fa-shield-alt me-1"></i>Administración</span>
    </div>

    <!-- ════════════════════════════════════════
         2. Stat cards
    ════════════════════════════════════════ -->
    <div class="row g-3 mb-4" id="statsRow">
        <div class="col-6 col-md-3">
            <div class="card ia-stat p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="icon-box" style="background:rgba(1,100,61,.1);color:#01643D;"><i class="fas fa-layer-group"></i></div>
                    <div><div class="stat-val" id="statTotal">—</div><div class="stat-lbl">Áreas totales</div></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card ia-stat p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="icon-box" style="background:rgba(42,126,93,.12);color:#2A7E5D;"><i class="fas fa-door-open"></i></div>
                    <div><div class="stat-val" id="statOpen">—</div><div class="stat-lbl">Abiertas</div></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card ia-stat p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="icon-box" style="background:#fef3c7;color:#92400e;"><i class="fas fa-user-clock"></i></div>
                    <div><div class="stat-val" id="statPending" style="color:#92400e;">—</div><div class="stat-lbl">Pendientes</div></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card ia-stat p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="icon-box" style="background:rgba(198,219,83,.25);color:#01643D;"><i class="fas fa-user-check"></i></div>
                    <div><div class="stat-val" id="statAccepted">—</div><div class="stat-lbl">Aceptados</div></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card ia-stat p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="icon-box" style="background:rgba(5,150,105,.1);color:#059669;"><i class="fas fa-graduation-cap"></i></div>
                    <div><div class="stat-val" id="statFinished" style="color:#059669;">—</div><div class="stat-lbl">Finalizados</div></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════
         3. Tabs + contenido
    ════════════════════════════════════════ -->
    <div class="d-flex justify-content-between align-items-center mb-0">
        <ul class="nav nav-tabs ia-tabs border-bottom flex-grow-1" id="tabAreas">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabPanelAreas">
                    <i class="fas fa-layer-group me-1"></i> Áreas
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabPanelPostulaciones">
                    <i class="fas fa-user-clock me-1"></i> Postulaciones
                    <span class="badge ms-1" style="background:#c6db53;color:#00204a;" id="badgePostPending" style="display:none">0</span>
                </button>
            </li>
        </ul>
        <button class="btn btn-unimo rounded-pill px-4 ms-3 mb-0" style="white-space:nowrap;" data-bs-toggle="modal" data-bs-target="#modalArea" id="btnNuevaArea">
            <i class="fas fa-plus me-2"></i>Nueva área
        </button>
    </div>

    <div class="tab-content bg-white rounded-bottom shadow-sm p-0">

        <!-- TAB: ÁREAS (cards grid) -->
        <div class="tab-pane fade show active p-4" id="tabPanelAreas">
            <div class="ia-tip mb-3">
                <i class="fas fa-info-circle mt-1" style="flex-shrink:0;"></i>
                <span>Cada tarjeta muestra el cupo disponible. Usa <strong>Nueva área</strong> para registrar una nueva vacante de prácticas internas.</span>
            </div>
            <div class="row g-3" id="areasGrid">
                <div class="col-12 text-center py-5 text-muted" id="areasLoading">
                    <i class="fas fa-spinner fa-spin me-2" style="color:#01643D;"></i>Cargando áreas…
                </div>
            </div>
            <!-- Tabla por si hay muchas áreas (toggle) -->
            <div class="mt-4 d-none" id="tblAreasWrap">
                <table id="tblAreas" class="table ia-table align-middle mb-0 w-100">
                    <thead><tr>
                        <th>#</th><th>Nombre</th><th>Cupo</th><th>Encargado</th><th>Estado</th><th>Acciones</th>
                    </tr></thead>
                    <tbody id="tbodyAreas"></tbody>
                </table>
            </div>
        </div>

        <!-- TAB: POSTULACIONES -->
        <div class="tab-pane fade" id="tabPanelPostulaciones">
            <!-- Filtro rápido -->
            <div class="px-4 pt-3 pb-2 d-flex gap-2 flex-wrap align-items-center border-bottom">
                <span class="fw-semibold small me-2" style="color:#01643D;"><i class="fas fa-filter me-1"></i>Filtrar:</span>
                <button class="btn btn-sm rounded-pill ia-filter-btn active" style="background:#01643D;color:#fff;border:0;" data-filter="all">Todas</button>
                <button class="btn btn-sm btn-outline-warning rounded-pill ia-filter-btn" data-filter="0">Pendientes</button>
                <button class="btn btn-sm rounded-pill ia-filter-btn" style="color:#01643D;border:1.5px solid #01643D;background:transparent;" data-filter="1">Aceptadas</button>
                <button class="btn btn-sm btn-outline-danger rounded-pill ia-filter-btn" data-filter="2">Rechazadas</button>
                <button class="btn btn-sm rounded-pill ia-filter-btn" style="color:#059669;border:1.5px solid #059669;background:transparent;" data-filter="finished">Finalizadas</button>
            </div>
            <div class="table-responsive">
                <table id="tblPostulaciones" class="table ia-table align-middle mb-0 w-100">
                    <thead><tr>
                        <th>Alumno</th><th>Matrícula</th><th>Programa</th><th>Área</th><th>Estado</th><th>Fecha solicitud</th><th>Acciones</th>
                    </tr></thead>
                    <tbody id="tbodyPostulaciones"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ============ MODAL CREAR / EDITAR ÁREA ============ -->
<div class="modal fade" id="modalArea" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalAreaTitle"><i class="fas fa-building-columns me-2" style="color:#01643D;"></i>Nueva Área</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                <input type="hidden" id="areaId">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="areaNombre" placeholder="Ej. Tecnologías de la Información">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Cupo máximo</label>
                        <input type="number" class="form-control" id="areaCupo" value="10" min="1">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Descripción <span class="text-muted fw-normal">(actividades, requisitos, etc.)</span></label>
                        <textarea class="form-control" id="areaDescripcion" rows="3" placeholder="Describe las actividades que realizará el practicante…"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Encargado del área</label>
                        <select class="form-select" id="areaEncargado">
                            <option value="">— Sin encargado asignado —</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['firstname'] . ' ' . $u['lastname']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">El encargado podrá aprobar/rechazar asistencias desde su panel.</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-unimo rounded-pill px-4" id="btnGuardarArea">
                    <i class="fas fa-save me-1"></i>Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============ MODAL ACEPTAR POSTULACIÓN ============ -->
<div class="modal fade" id="modalAceptar" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-calendar-check me-2 text-success"></i>Aceptar postulación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="postId">
                <p class="text-muted small mb-3">Se notificará al alumno por correo electrónico.</p>
                <label class="form-label fw-semibold">Fecha de inicio</label>
                <input type="date" class="form-control" id="postFechaInicio" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success rounded-pill px-4" id="btnConfirmarAceptar">
                    <i class="fas fa-check me-1"></i>Aceptar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============ MODAL VER EVALUACIÓN ============ -->
<div class="modal fade" id="modalVerEvaluacion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header text-white border-0" style="background:#01643D;">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-clipboard-list me-2"></i>
                    Evaluaciones de <span id="evalStudentName"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="evalModalBody">
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-spinner fa-spin fa-2x mb-3 d-block"></i>Cargando evaluaciones…
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div><!-- /container-fluid -->

<script src="view/assets/js/ajax/practices/areas_admin.js"></script>
