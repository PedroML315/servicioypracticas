<?php
$_role        = $_SESSION['user']['role'] ?? '';
$_teacherName = htmlspecialchars($_SESSION['user']['nombre_completo'] ?? '', ENT_QUOTES, 'UTF-8');
$_initials    = strtoupper(implode('', array_map(fn($w) => substr($w, 0, 1), array_slice(explode(' ', $_teacherName), 0, 2))));
?>
<!-- SheetJS para exportar Excel -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<style>
/* ============================================================
   Estudiantes — Vista Admin / Encargado
   Paleta: navy #c2bf11 | green #01643D | lime #c6db53
   ============================================================ */
:root {
  --st-navy:   #c2bf11;
  --st-green:  #01643D;
  --st-green2: #2A7E5D;
  --st-bg:     #f4f6f8;
  --st-border: #e4e9ef;
}

.st-wrap {
  background: var(--st-bg);
  padding: 1.5rem 2rem 3rem;
  min-height: calc(100vh - 58px);
}

/* ── Hero ── */
.st-hero {
  background: linear-gradient(135deg, var(--st-navy) 0%, var(--st-green) 65%, var(--st-green2) 100%);
  border-radius: 1rem; color: #fff;
  padding: 1.6rem 2rem; margin-bottom: 1.75rem;
  display: flex; align-items: center; gap: 1.25rem;
  box-shadow: 0 6px 24px rgba(0,32,74,.2);
  position: relative; overflow: hidden;
}
.st-hero::before {
  content: ''; position: absolute; right: -40px; top: -40px;
  width: 180px; height: 180px; border-radius: 50%;
  background: rgba(198,219,83,.1);
}
.st-hero::after {
  content: ''; position: absolute; right: 100px; bottom: -55px;
  width: 120px; height: 120px; border-radius: 50%;
  background: rgba(255,255,255,.05);
}
.st-hero-avatar {
  width: 58px; height: 58px; border-radius: 50%;
  background: rgba(255,255,255,.15); border: 2px solid rgba(255,255,255,.25);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.3rem; font-weight: 700; flex-shrink: 0; z-index: 1;
}
.st-hero-text { z-index: 1; }
.st-hero-text h2 { font-size: 1.25rem; font-weight: 700; margin: 0 0 .2rem; }
.st-hero-text p  { margin: 0; opacity: .88; font-size: .85rem; }
.st-hero-badge {
  margin-left: auto; z-index: 1;
  background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.3);
  border-radius: 2rem; padding: .3rem .85rem;
  font-size: .75rem; font-weight: 600; white-space: nowrap;
}

/* ── Table card ── */
.st-card {
  background: #fff; border-radius: 1rem;
  border: 1px solid var(--st-border);
  box-shadow: 0 2px 12px rgba(0,0,0,.07);
  overflow: hidden;
}
.st-card-header {
  padding: .9rem 1.25rem; border-bottom: 1px solid var(--st-border);
  display: flex; align-items: center; gap: .6rem; flex-wrap: wrap;
}
.st-card-title {
  font-size: .95rem; font-weight: 700; color: #1a2530;
  display: flex; align-items: center; gap: .5rem;
}
.st-icon-wrap {
  width: 30px; height: 30px; border-radius: 8px;
  display: inline-flex; align-items: center; justify-content: center;
  font-size: .8rem; background: #d1fae5; color: var(--st-green);
}
.st-card .card-body { padding: 1.25rem; }

/* ── DataTable overrides ── */
.st-card .dataTables_filter input {
  border-radius: 2rem; border: 1.5px solid var(--st-border);
  padding: .3rem .85rem; font-size: .82rem;
}
.st-card .dataTables_filter input:focus {
  outline: none; border-color: var(--st-green);
  box-shadow: 0 0 0 3px rgba(1,100,61,.1);
}
.st-card table.dataTable thead th {
  background: #f8f9fc; color: #6b7280;
  font-size: .7rem; font-weight: 700;
  letter-spacing: .06em; text-transform: uppercase;
  border-bottom: 1px solid var(--st-border); white-space: nowrap;
  padding: .7rem 1rem;
}
.st-card table.dataTable tbody tr { transition: background .15s; }
.st-card table.dataTable tbody tr:hover { background: #f0faf5 !important; }
.st-card table.dataTable tbody td {
  border-bottom: 1px solid #f0f3f7; vertical-align: middle;
  font-size: .83rem; padding: .65rem 1rem;
}
.st-card table.dataTable tbody tr:last-child td { border-bottom: 0; }

/* Matrícula badge */
.st-matricula-btn {
  background: linear-gradient(135deg, var(--st-green), var(--st-green2));
  color: #fff; border: none; border-radius: 2rem;
  padding: .25rem .85rem; font-size: .75rem; font-weight: 700;
  cursor: pointer; transition: all .15s; white-space: nowrap;
}
.st-matricula-btn:hover {
  background: linear-gradient(135deg, var(--st-navy), var(--st-green));
  box-shadow: 0 3px 10px rgba(1,100,61,.3);
}

/* Action buttons */
.st-btn-icon {
  width: 30px; height: 30px; border-radius: 8px; border: none; cursor: pointer;
  display: inline-flex; align-items: center; justify-content: center;
  font-size: .78rem; transition: all .15s;
}
.st-btn-accept  { background: #dcfce7; color: #16a34a; }
.st-btn-accept:hover  { background: #16a34a; color: #fff; }
.st-btn-reject  { background: #fee2e2; color: #dc2626; }
.st-btn-reject:hover  { background: #dc2626; color: #fff; }
.st-btn-edit    { background: #ecfccb; color: #3f6212; }
.st-btn-edit:hover    { background: #65a30d; color: #fff; }
.st-btn-delete  { background: #fee2e2; color: #dc2626; }
.st-btn-delete:hover  { background: #dc2626; color: #fff; }
.st-btn-info    { background: #fef9c3; color: #854d0e; }
.st-btn-info:hover    { background: #ca8a04; color: #fff; }

/* ── DataTable pagination & sorting overrides (quitar azul Bootstrap/DT) ── */
.st-card .dataTables_wrapper .page-item.active .page-link {
  background-color: var(--st-green) !important;
  border-color: var(--st-green) !important;
  color: #fff !important;
}
.st-card .dataTables_wrapper .page-link {
  color: var(--st-green) !important;
}
.st-card .dataTables_wrapper .page-link:hover {
  background-color: #d1fae5 !important;
  border-color: var(--st-green) !important;
  color: var(--st-green) !important;
}
/* Columna ordenada activa — quitar fondo azul */
.st-card table.dataTable thead th.sorting_asc,
.st-card table.dataTable thead th.sorting_desc,
.st-card table.dataTable thead th.sorting_asc_disabled,
.st-card table.dataTable thead th.sorting_desc_disabled {
  background-color: #e6f4ee !important;
  color: var(--st-green) !important;
}
/* Highlight de fila ordenada */
.st-card table.dataTable tbody td.sorting_1 {
  background-color: #f0faf5 !important;
}
/* Links dentro de la tabla */
.st-card table.dataTable a { color: var(--st-green) !important; }
.st-card table.dataTable a:hover { color: var(--st-green2) !important; }
/* Select "Mostrar N registros" y botones DT */
.st-card .dt-length select:focus,
.st-card .dataTables_length select:focus {
  border-color: var(--st-green) !important;
  box-shadow: 0 0 0 3px rgba(1,100,61,.1) !important;
}

/* Register btn */
.btn-st-register {
  background: rgba(255,255,255,.15); border: 1.5px solid rgba(255,255,255,.35);
  color: #fff; border-radius: 2rem; padding: .38rem 1rem; font-size: .8rem; font-weight: 600;
  transition: all .15s;
}
.btn-st-register:hover {
  background: rgba(255,255,255,.25); color: #fff;
}

/* ── Tabs ── */
.st-tabs {
  display: flex; gap: .5rem;
  margin-bottom: 1.25rem; flex-wrap: wrap;
}
.st-tab-btn {
  display: flex; align-items: center; gap: .5rem;
  padding: .55rem 1.2rem; border-radius: 2rem;
  font-size: .85rem; font-weight: 600; cursor: pointer;
  border: 2px solid var(--st-border); transition: all .2s;
  background: #fff; color: #6b7280;
}
.st-tab-btn.active-interno {
  background: var(--st-green); color: #fff; border-color: var(--st-green);
  box-shadow: 0 3px 12px rgba(1,100,61,.25);
}
.st-tab-btn.active-externo {
  background: #7c3aed; color: #fff; border-color: #7c3aed;
  box-shadow: 0 3px 12px rgba(124,58,237,.25);
}
.st-tab-btn .tab-count {
  background: rgba(255,255,255,.25); border-radius: 2rem;
  padding: .1rem .5rem; font-size: .72rem; font-weight: 700;
}
.st-tab-btn:not(.active-interno):not(.active-externo) .tab-count {
  background: #e4e9ef; color: #6b7280;
}

/* Tab panel */
.tab-panel { display: none; }
.tab-panel.active { display: block; }

/* Icon wraps */
.st-icon-wrap {
  width: 30px; height: 30px; border-radius: 8px;
  display: inline-flex; align-items: center; justify-content: center;
  font-size: .8rem;
}
.st-icon-interno { background: #d1fae5; color: var(--st-green); }
.st-icon-externo { background: #ede9fe; color: #7c3aed; }

/* Process step circles */
.step-track { display: flex; align-items: center; gap: .25rem; flex-wrap: nowrap; }
.step-circle {
  width: 20px; height: 20px; border-radius: 50%; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: .55rem; font-weight: 700; cursor: pointer;
  transition: transform .15s; border: 2px solid transparent;
}
.step-circle:hover { transform: scale(1.3); }
.step-circle.done     { background: #dcfce7; color: #16a34a; border-color: #16a34a; }
.step-circle.pending  { background: #fef9c3; color: #b45309; border-color: #d97706; }
.step-circle.rejected { background: #fee2e2; color: #dc2626; border-color: #dc2626; }
.step-circle.empty    { background: #f1f5f9; color: #d1d5db; border-color: #e2e8f0; }
.step-line { width: 10px; height: 2px; background: #e2e8f0; flex-shrink: 0; }
.step-line.done { background: #16a34a; }

/* Last status badge */
.proc-badge {
  display: inline-flex; align-items: center; gap: .3rem;
  font-size: .7rem; font-weight: 600; border-radius: 2rem;
  padding: .18rem .65rem; white-space: nowrap;
}
.proc-badge.aprobado  { background: #dcfce7; color: #16a34a; }
.proc-badge.pendiente { background: #fef9c3; color: #b45309; }
.proc-badge.rechazado { background: #fee2e2; color: #dc2626; }
.proc-badge.sin-doc   { background: #f1f5f9; color: #94a3b8; }

/* Action button override */
.st-btn-history { background: #ede9fe; color: #7c3aed; }
.st-btn-history:hover { background: #7c3aed; color: #fff; }

/* Export button */
.btn-export-excel {
  background: #16a34a; color: #fff; border: none;
  border-radius: 2rem; padding: .38rem 1rem;
  font-size: .8rem; font-weight: 600; cursor: pointer;
  display: inline-flex; align-items: center; gap: .4rem;
  transition: all .15s;
}
.btn-export-excel:hover { background: #15803d; box-shadow: 0 3px 10px rgba(21,128,61,.3); }

/* History modal timeline */
.hist-timeline { display: flex; flex-direction: column; gap: .6rem; }
.hist-item { display: flex; gap: .75rem; align-items: flex-start; }
.hist-dot {
  width: 30px; height: 30px; border-radius: 50%; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center; font-size: .7rem;
}
.hist-dot.aprobado  { background: #dcfce7; color: #16a34a; border: 2px solid #16a34a; }
.hist-dot.pendiente { background: #fef9c3; color: #b45309; border: 2px solid #d97706; }
.hist-dot.rechazado { background: #fee2e2; color: #dc2626; border: 2px solid #dc2626; }
.hist-dot.sin-doc   { background: #f1f5f9; color: #94a3b8; border: 2px dashed #94a3b8; }
.hist-content h6 { font-size: .82rem; font-weight: 700; margin: 0 0 .1rem; }
.hist-content small { font-size: .7rem; color: #94a3b8; }
</style>

<div class="st-wrap">

  <!-- ══ HERO ══ -->
  <div class="st-hero">
    <div class="st-hero-avatar"><?= $_initials ?: '<i class="fas fa-users"></i>' ?></div>
    <div class="st-hero-text">
      <h2><i class="fas fa-users me-2"></i>Gestión de Estudiantes</h2>
      <p>Historial y estado del proceso de servicio social por alumno</p>
    </div>
    <?php if (in_array($_role, ['admin', 'admin_servicio'])): ?>
      <div class="ms-auto" style="z-index:1;">
        <button class="btn btn-st-register registerStudentModal">
          <i class="fas fa-user-plus me-1"></i>Registrar Estudiante
        </button>
      </div>
    <?php else: ?>
      <span class="st-hero-badge"><i class="fas fa-chalkboard-teacher me-1"></i>Encargado</span>
    <?php endif; ?>
  </div>

  <!-- ══ TABS ══ -->
  <div class="st-tabs">
    <button class="st-tab-btn active-interno" id="tabBtnInterno" onclick="switchTab('interno')">
      <i class="fas fa-university"></i>
      Estudiantes Internos
      <span class="tab-count" id="countInterno">–</span>
    </button>
    <button class="st-tab-btn" id="tabBtnExterno" onclick="switchTab('externo')">
      <i class="fas fa-building"></i>
      Estudiantes Externos
      <span class="tab-count" id="countExterno">–</span>
    </button>
  </div>

  <!-- ══ PANEL INTERNO ══ -->
  <div class="tab-panel active" id="panelInterno">
    <div class="st-card">
      <div class="st-card-header">
        <div class="st-card-title">
          <span class="st-icon-wrap st-icon-interno"><i class="fas fa-user-graduate"></i></span>
          Alumnos — Servicio Social Interno (Universidad)
        </div>
        <div class="ms-auto d-flex gap-2 align-items-center">
          <button class="btn-export-excel" onclick="exportExcel('interno')">
            <i class="fas fa-file-excel"></i> Exportar Excel
          </button>
        </div>
      </div>
      <div class="card-body">
        <div class="d-flex flex-wrap gap-2 mb-3" style="font-size:.72rem;color:#6b7280;">
          <b>Etapas del proceso:</b>
          <span><span class="step-circle done" style="display:inline-flex;width:14px;height:14px;">✓</span> Aprobado</span>
          <span><span class="step-circle pending" style="display:inline-flex;width:14px;height:14px;">⏳</span> Pendiente</span>
          <span><span class="step-circle rejected" style="display:inline-flex;width:14px;height:14px;">✗</span> Rechazado</span>
          <span><span class="step-circle empty" style="display:inline-flex;width:14px;height:14px;">–</span> Sin doc.</span>
          <span class="ms-1" style="opacity:.65;">Pasa el cursor sobre los círculos para ver el nombre del paso</span>
        </div>
        <div class="table-responsive">
          <table id="tableInterno" class="table table-sm align-middle table-borderless w-100">
            <thead>
              <tr>
                <th>#</th>
                <th>Matrícula</th>
                <th>Nombre</th>
                <th>Licenciatura</th>
                <th>Correo</th>
                <th>Proceso</th>
                <th>Último estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- ══ PANEL EXTERNO ══ -->
  <div class="tab-panel" id="panelExterno">
    <div class="st-card">
      <div class="st-card-header">
        <div class="st-card-title">
          <span class="st-icon-wrap st-icon-externo"><i class="fas fa-building"></i></span>
          Alumnos — Servicio Social Externo (Empresa / IJUMICH)
        </div>
        <div class="ms-auto d-flex gap-2 align-items-center">
          <button class="btn-export-excel" onclick="exportExcel('externo')">
            <i class="fas fa-file-excel"></i> Exportar Excel
          </button>
        </div>
      </div>
      <div class="card-body">
        <div class="d-flex flex-wrap gap-2 mb-3" style="font-size:.72rem;color:#6b7280;">
          <b>Etapas del proceso externo:</b>
          <span><span class="step-circle done" style="display:inline-flex;width:14px;height:14px;">✓</span> Aprobado</span>
          <span><span class="step-circle pending" style="display:inline-flex;width:14px;height:14px;">⏳</span> Pendiente</span>
          <span><span class="step-circle rejected" style="display:inline-flex;width:14px;height:14px;">✗</span> Rechazado</span>
          <span><span class="step-circle empty" style="display:inline-flex;width:14px;height:14px;">–</span> Sin doc.</span>
        </div>
        <div class="table-responsive">
          <table id="tableExterno" class="table table-sm align-middle table-borderless w-100">
            <thead>
              <tr>
                <th>#</th>
                <th>Matrícula</th>
                <th>Nombre</th>
                <th>Licenciatura</th>
                <th>Correo</th>
                <th>Tipo</th>
                <th>Proceso</th>
                <th>Último estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>
    </div>
  </div>

</div><!-- /st-wrap -->

<!-- ══ MODAL HISTORIAL DEL ALUMNO ══ -->
<div class="modal fade" id="historyModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:520px;">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0" style="background:linear-gradient(135deg,#01643D,#2A7E5D);color:#fff;padding:.9rem 1.25rem;">
        <h5 class="modal-title fw-bold" style="font-size:.95rem;">
          <i class="fas fa-list-timeline me-2"></i><span id="historyModalTitle">Historial del alumno</span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-3">
        <div id="historyModalContent"></div>
      </div>
    </div>
  </div>
</div>

<?php include 'view/pages/modals/studentModals.php'; ?>
<script>var _st_role = "<?= $_role ?>";</script>
<script src="view/assets/js/ajax/students.js"></script>