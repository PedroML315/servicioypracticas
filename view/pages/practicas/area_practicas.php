<?php
if (($_SESSION['user']['role'] ?? '') !== 'teacher') {
    header("Location: ./");
    exit;
}
$userId = (int)$_SESSION['user']['id'];
$teacherName = htmlspecialchars($_SESSION['user']['nombre_completo'] ?? '', ENT_QUOTES, 'UTF-8');
$teacherInitials = strtoupper(implode('', array_map(fn($w) => substr($w,0,1), array_slice(explode(' ', $teacherName), 0, 2))));
?>

<style>
/* ============================================================
   Dashboard Docente – Áreas de Prácticas Profesionales
   ============================================================ */
:root {
  --ap-purple:  #7c3aed;
  --ap-purple2: #4c1d95;
  --ap-light:   #f4f6f8;
  --ap-border:  #e4e9ef;
}

.ap-wrap {
  background: var(--ap-light);
  padding: 1.5rem 2rem 3rem;
  min-height: calc(100vh - 58px);
}

/* ── Hero ── */
.ap-hero {
  background: linear-gradient(135deg, #01643D 0%, #1a7a52 55%, #c6db53 100%);
  border-radius: 1rem;
  color: #fff;
  padding: 1.6rem 2rem;
  margin-bottom: 1.75rem;
  display: flex;
  align-items: center;
  gap: 1.25rem;
  box-shadow: 0 6px 24px rgba(109,40,217,.22);
  position: relative;
  overflow: hidden;
}
.ap-hero::before {
  content: '';
  position: absolute; right: -50px; top: -50px;
  width: 200px; height: 200px; border-radius: 50%;
  background: rgba(255,255,255,.07);
}
.ap-hero::after {
  content: '';
  position: absolute; right: 80px; bottom: -60px;
  width: 130px; height: 130px; border-radius: 50%;
  background: rgba(255,255,255,.05);
}
.ap-avatar {
  width: 56px; height: 56px; border-radius: 50%;
  background: rgba(255,255,255,.2);
  border: 2px solid rgba(255,255,255,.35);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.3rem; font-weight: 700; flex-shrink: 0; z-index: 1;
}
.ap-hero-text { z-index: 1; }
.ap-hero-text h2 { font-size: 1.25rem; font-weight: 700; margin: 0 0 .2rem; }
.ap-hero-text p  { margin: 0; opacity: .88; font-size: .85rem; }
.ap-hero-badge {
  margin-left: auto; z-index: 1;
  background: rgba(255,255,255,.2);
  border: 1px solid rgba(255,255,255,.35);
  border-radius: 2rem;
  padding: .28rem .85rem;
  font-size: .75rem; font-weight: 600; white-space: nowrap;
}

/* ── Selector de áreas ── */
.ap-areas-card {
  background: #fff;
  border-radius: 1rem;
  border: 1px solid var(--ap-border);
  box-shadow: 0 2px 10px rgba(0,0,0,.06);
  padding: 1.25rem 1.5rem;
  margin-bottom: 1.5rem;
}
.ap-areas-card .ap-section-title {
  font-size: .7rem;
  font-weight: 700;
  letter-spacing: .08em;
  text-transform: uppercase;
  color: #8492a6;
  margin-bottom: .85rem;
}
.btn-area-selector {
  border-radius: 2rem !important;
  font-size: .83rem;
  font-weight: 600;
  padding: .38rem 1rem;
  transition: all .18s;
  border: 1.5px solid var(--ap-purple) !important;
  color: var(--ap-purple) !important;
  background: #fff !important;
}
.btn-area-selector:hover {
  background: #ede9fe !important;
}
.btn-area-selector.btn-primary,
.btn-area-selector.active {
  background: var(--ap-purple) !important;
  color: #fff !important;
  box-shadow: 0 3px 10px rgba(124,58,237,.3);
}

/* ── Panel cards ── */
.ap-panel-card {
  border-radius: 1rem;
  border: 1px solid var(--ap-border);
  box-shadow: 0 2px 10px rgba(0,0,0,.06);
  background: #fff;
  overflow: hidden;
}
.ap-panel-card .ap-card-header {
  padding: .9rem 1.25rem;
  border-bottom: 1px solid var(--ap-border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: .5rem;
}
.ap-card-header-title {
  font-size: .95rem;
  font-weight: 700;
  color: #1a2530;
  display: flex;
  align-items: center;
  gap: .5rem;
}
.ap-card-header-title .icon-wrap {
  width: 30px; height: 30px; border-radius: 8px;
  display: inline-flex; align-items: center; justify-content: center;
  font-size: .8rem;
}
.icon-wrap-yellow { background: #fef9c3; color: #b45309; }
.icon-wrap-blue   { background: #dbeafe; color: #1d4ed8; }

/* ── Tables ── */
.ap-table { margin: 0; font-size: .82rem; }
.ap-table thead th {
  background: #f8f9fc;
  color: #6b7280;
  font-size: .7rem;
  font-weight: 700;
  letter-spacing: .06em;
  text-transform: uppercase;
  padding: .65rem 1rem;
  border-bottom: 1px solid var(--ap-border);
}
.ap-table tbody tr { transition: background .15s; }
.ap-table tbody tr:hover { background: #f8f5ff; }
.ap-table tbody td {
  padding: .65rem 1rem;
  vertical-align: middle;
  border-bottom: 1px solid #f0f3f7;
}
.ap-table tbody tr:last-child td { border-bottom: none; }

/* action buttons */
.ap-btn-icon {
  width: 30px; height: 30px; border-radius: 8px;
  display: inline-flex; align-items: center; justify-content: center;
  font-size: .78rem; border: none; cursor: pointer; transition: all .15s;
}
.ap-btn-approve { background: #dcfce7; color: #16a34a; }
.ap-btn-approve:hover { background: #16a34a; color: #fff; }
.ap-btn-reject  { background: #fee2e2; color: #dc2626; }
.ap-btn-reject:hover  { background: #dc2626; color: #fff; }

/* badges */
.ap-badge {
  border-radius: 2rem; padding: .28rem .75rem;
  font-size: .72rem; font-weight: 700;
  display: inline-flex; align-items: center; gap: .3rem;
}
.ap-badge-warning { background: #fef3c7; color: #b45309; border: 1.5px solid #fcd34d; }
.ap-badge-blue    { background: #dbeafe; color: #1d4ed8; border: 1.5px solid #93c5fd; }
.ap-badge-green   { background: #d1fae5; color: #059669; border: 1.5px solid #6ee7b7; }
.ap-badge-purple  { background: #ede9fe; color: #7c3aed; border: 1.5px solid #c4b5fd; }

/* ── Empty state ── */
.ap-empty {
  padding: 4rem 2rem;
  text-align: center;
}
.ap-empty .ap-empty-icon {
  width: 72px; height: 72px; border-radius: 50%;
  background: #ede9fe; color: var(--ap-purple);
  font-size: 1.7rem;
  display: inline-flex; align-items: center; justify-content: center;
  margin-bottom: 1rem;
}
.ap-empty h5 { font-weight: 700; color: #374151; margin-bottom: .4rem; }
.ap-empty p  { color: #9ca3af; font-size: .88rem; margin: 0; }

/* ── Scrollable table body ── */
.ap-table-scroll { max-height: 340px; overflow-y: auto; }
.ap-table-scroll::-webkit-scrollbar { width: 5px; }
.ap-table-scroll::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }
</style>

<div class="ap-wrap">

  <!-- ══ HERO ══ -->
  <div class="ap-hero">
    <div class="ap-avatar"><?= $teacherInitials ?: 'DO' ?></div>
    <div class="ap-hero-text">
      <h2><?= $teacherName ?: 'Docente' ?></h2>
      <p><i class="fas fa-chalkboard-teacher me-1"></i>Panel de Prácticas Profesionales</p>
    </div>
    <span class="ap-hero-badge"><i class="fas fa-layer-group me-1"></i>Mis Áreas</span>
  </div>

  <!-- ══ SELECTOR DE ÁREAS ══ -->
  <div class="ap-areas-card">
    <div class="ap-section-title"><i class="fas fa-map-marker-alt me-1"></i>Selecciona un área</div>
    <div id="selectorAreas" class="d-flex flex-wrap gap-2">
      <span class="text-muted small"><i class="fas fa-spinner fa-spin me-1"></i>Cargando áreas…</span>
    </div>
  </div>

  <!-- ══ PANEL ÁREA ══ -->
  <div id="panelArea" style="display:none">
    <div class="row g-3">

      <!-- Asistencias pendientes -->
      <div class="col-lg-6">
        <div class="ap-panel-card h-100">
          <div class="ap-card-header">
            <div class="ap-card-header-title">
              <span class="icon-wrap icon-wrap-yellow"><i class="fas fa-clock"></i></span>
              Asistencias pendientes
            </div>
            <span class="ap-badge ap-badge-warning" id="badgePendientes"><i class="fas fa-hourglass-half"></i> 0</span>
          </div>
          <div class="ap-table-scroll">
            <table id="tblAsistencias" class="table ap-table mb-0">
              <thead>
                <tr>
                  <th>Alumno</th>
                  <th>Fecha</th>
                  <th>Entrada</th>
                  <th>Salida</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody id="tbodyAsistencias"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Alumnos en el área -->
      <div class="col-lg-6">
        <div class="ap-panel-card h-100">
          <div class="ap-card-header">
            <div class="ap-card-header-title">
              <span class="icon-wrap icon-wrap-blue"><i class="fas fa-users"></i></span>
              Alumnos en el área
            </div>
            <div class="d-flex gap-2 flex-wrap">
              <span class="ap-badge ap-badge-blue" id="badgeAlumnosActivos"><i class="fas fa-sync-alt"></i> En curso: 0</span>
              <span class="ap-badge ap-badge-green" id="badgeAlumnosFinalizados"><i class="fas fa-graduation-cap"></i> Finalizados: 0</span>
            </div>
          </div>
          <div class="ap-table-scroll">
            <table id="tblAlumnos" class="table ap-table mb-0">
              <thead>
                <tr>
                  <th>Alumno</th>
                  <th>Matrícula</th>
                  <th>Programa</th>
                  <th>Horas</th>
                  <th>Estado</th>
                </tr>
              </thead>
              <tbody id="tbodyAlumnos"></tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- ══ SIN ÁREAS ══ -->
  <div id="sinAreas" class="ap-empty" style="display:none">
    <div class="ap-empty-icon"><i class="fas fa-inbox"></i></div>
    <h5>Sin áreas asignadas</h5>
    <p>No tienes áreas de prácticas asignadas.<br>Comunícate con el administrador.</p>
  </div>

</div><!-- /ap-wrap -->

<script src="view/assets/js/organismo/area_practicas.js"></script>
