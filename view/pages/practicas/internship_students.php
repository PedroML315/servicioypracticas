<?php
if (!in_array($_SESSION['user']['role'] ?? '', ['admin', 'admin_practicas'], true)) {
    header("Location: ./");
    exit;
}
?>
<style>
/* ══════════════════════════════════════════════════════
   Internship Students — Paleta Unimo v2
   navy #00204a | green #01643D | mid-green #2A7E5D | lime #c6db53
   ══════════════════════════════════════════════════════ */
:root {
  --g1: #01643D; --g2: #2A7E5D; --lime: #c6db53;
  --border: #e4e9ef; --bg: #f4f6f8;
}

.is-wrap { background: var(--bg); padding: 1.5rem 2rem 3rem; min-height: calc(100vh - 58px); }

/* ── Hero ── */
.is-hero {
  background: linear-gradient(135deg, #00204a 0%, #01643D 60%, #2A7E5D 100%);
  border-radius: 1rem; color: #fff;
  padding: 1.6rem 2rem; margin-bottom: 1.75rem;
  display: flex; align-items: center; gap: 1.25rem;
  box-shadow: 0 6px 24px rgba(0,32,74,.2);
  position: relative; overflow: hidden;
}
.is-hero::before {
  content:''; position:absolute; right:-40px; top:-40px;
  width:180px; height:180px; border-radius:50%;
  background:rgba(198,219,83,.08);
}
.is-hero::after {
  content:''; position:absolute; right:100px; bottom:-50px;
  width:120px; height:120px; border-radius:50%;
  background:rgba(255,255,255,.05);
}
.is-hero-icon {
  width:58px; height:58px; border-radius:50%;
  background:rgba(255,255,255,.15); border:2px solid rgba(255,255,255,.25);
  display:flex; align-items:center; justify-content:center;
  font-size:1.5rem; flex-shrink:0; z-index:1;
}
.is-hero-text { z-index:1; }
.is-hero-text h2 { font-size:1.25rem; font-weight:700; margin:0 0 .2rem; }
.is-hero-text p  { margin:0; opacity:.88; font-size:.85rem; }
.is-hero-badge {
  margin-left:auto; z-index:1;
  background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.3);
  border-radius:2rem; padding:.3rem .85rem;
  font-size:.75rem; font-weight:600; white-space:nowrap;
}

/* ── Stat cards ── */
.is-stat {
  border:0; border-radius:1rem;
  box-shadow:0 2px 10px rgba(0,0,0,.07);
  background:#fff; transition:transform .2s, box-shadow .2s;
}
.is-stat:hover { transform:translateY(-3px); box-shadow:0 6px 20px rgba(0,0,0,.1); }
.is-stat .icon-box {
  width:48px; height:48px; border-radius:.75rem;
  display:flex; align-items:center; justify-content:center; font-size:1.2rem;
}
.is-stat .stat-val  { font-size:1.8rem; font-weight:800; line-height:1; }
.is-stat .stat-lbl  { font-size:.75rem; color:#6c757d; margin-top:.15rem; }
.is-stat .stat-bar  {
  height:4px; border-radius:2px; background:#f0f3f7; margin-top:.75rem; overflow:hidden;
}
.is-stat .stat-bar-fill { height:100%; border-radius:2px; transition:width .6s ease; }

/* ── Table card ── */
.is-table-card {
  background:#fff; border-radius:1rem;
  border:1px solid var(--border);
  box-shadow:0 2px 12px rgba(0,0,0,.07);
  overflow:hidden;
}

/* ── Filter tabs ── */
.is-tabs {
  padding:.85rem 1.25rem .75rem;
  border-bottom:1px solid var(--border);
  display:flex; gap:.5rem; flex-wrap:wrap; align-items:center;
}
.is-tab-btn {
  font-size:.78rem; font-weight:600; border-radius:2rem;
  padding:.3rem .9rem; border:1.5px solid transparent;
  background:transparent; cursor:pointer; transition:all .15s;
  display:inline-flex; align-items:center; gap:.35rem;
}
.is-tab-btn .tab-cnt {
  background:rgba(0,0,0,.08); border-radius:2rem;
  padding:.05rem .45rem; font-size:.7rem; font-weight:700;
}
.is-tab-btn[data-filter="all"]      { border-color:#00204a; color:#00204a; }
.is-tab-btn[data-filter="0"]        { border-color:#d97706; color:#d97706; }
.is-tab-btn[data-filter="1"]        { border-color:var(--g1); color:var(--g1); }
.is-tab-btn[data-filter="2"]        { border-color:#dc2626; color:#dc2626; }
.is-tab-btn[data-filter="finished"] { border-color:#059669; color:#059669; }

.is-tab-btn.active[data-filter="all"]      { background:#00204a; color:#fff; }
.is-tab-btn.active[data-filter="0"]        { background:#d97706; color:#fff; }
.is-tab-btn.active[data-filter="1"]        { background:var(--g1); color:#fff; }
.is-tab-btn.active[data-filter="2"]        { background:#dc2626; color:#fff; }
.is-tab-btn.active[data-filter="finished"] { background:#059669; color:#fff; }
.is-tab-btn.active .tab-cnt { background:rgba(255,255,255,.3); }

/* search row */
.is-search-row {
  padding:.65rem 1.25rem;
  border-bottom:1px solid var(--border);
  display:flex; align-items:center; gap:.6rem;
}
.is-search-row input {
  border:0; outline:none; flex:1; font-size:.85rem; background:transparent;
}
.is-search-row input::placeholder { color:#adb5bd; }

/* ── Table ── */
.is-table { margin:0; font-size:.82rem; }
.is-table thead th {
  background:#f8f9fc; color:#6b7280;
  font-size:.7rem; font-weight:700;
  letter-spacing:.06em; text-transform:uppercase;
  padding:.65rem 1rem; border-bottom:1px solid var(--border); white-space:nowrap;
}
.is-table tbody tr { transition:background .15s; }
.is-table tbody tr:hover { background:#f0faf5; }
.is-table tbody td {
  padding:.65rem 1rem; vertical-align:middle;
  border-bottom:1px solid #f0f3f7;
}
.is-table tbody tr:last-child td { border-bottom:0; }

/* student avatar */
.stu-avatar {
  width:34px; height:34px; border-radius:50%;
  display:inline-flex; align-items:center; justify-content:center;
  font-size:.7rem; font-weight:700; flex-shrink:0;
  background:linear-gradient(135deg,#00204a,#01643D); color:#fff;
}

/* ── Status pills ── */
.pill { display:inline-flex; align-items:center; gap:.3em; font-size:.72rem; font-weight:700; padding:.28em .75em; border-radius:50rem; white-space:nowrap; }
.pill-pending  { background:#fef3c7; color:#92400e; border:1.5px solid #fcd34d; }
.pill-accepted { background:#d1fae5; color:#065f46; border:1.5px solid #6ee7b7; }
.pill-rejected { background:#fee2e2; color:#991b1b; border:1.5px solid #fca5a5; }
.pill-inactive { background:#f3f4f6; color:#6b7280; }
.pill-finished { background:#d1fae5; color:#059669; border:1.5px solid #059669; }

/* ── Action icon buttons ── */
.is-btn-icon {
  width:30px; height:30px; border-radius:8px;
  display:inline-flex; align-items:center; justify-content:center;
  font-size:.78rem; border:none; cursor:pointer; transition:all .15s;
}
.is-btn-accept   { background:#dcfce7; color:#16a34a; }
.is-btn-accept:hover { background:#16a34a; color:#fff; }
.is-btn-reject   { background:#fee2e2; color:#dc2626; }
.is-btn-reject:hover { background:#dc2626; color:#fff; }
.is-btn-edit     { background:#dbeafe; color:#2563eb; }
.is-btn-edit:hover { background:#2563eb; color:#fff; }
.is-btn-disable  { background:#f3f4f6; color:#6b7280; }
.is-btn-disable:hover { background:#6b7280; color:#fff; }

/* ── Btn ── */
.btn-unimo { background:var(--g1); border-color:var(--g1); color:#fff; }
.btn-unimo:hover { background:var(--g2); border-color:var(--g2); color:#fff; }

/* ── Modal header ── */
.is-modal-header {
  background:linear-gradient(135deg,#00204a,#01643D);
  color:#fff; border-radius:.5rem .5rem 0 0; padding:1rem 1.5rem;
}
.is-modal-header .btn-close { filter:invert(1) brightness(2); }

/* ── Empty state ── */
.is-empty { padding:4rem 2rem; text-align:center; }
.is-empty-icon {
  width:72px; height:72px; border-radius:50%;
  background:#f0faf5; color:var(--g1); font-size:1.7rem;
  display:inline-flex; align-items:center; justify-content:center; margin-bottom:1rem;
}
.is-empty h5 { font-weight:700; color:#374151; margin-bottom:.4rem; }
.is-empty p  { color:#9ca3af; font-size:.88rem; margin:0; }

/* ── Table scroll ── */
.is-table-scroll { max-height: 480px; overflow-y: auto; }
.is-table-scroll::-webkit-scrollbar { width:5px; }
.is-table-scroll::-webkit-scrollbar-thumb { background:#d1d5db; border-radius:10px; }
</style>

<div class="is-wrap">

  <!-- ════════════════ 1. HERO ════════════════ -->
  <div class="is-hero">
    <div class="is-hero-icon"><i class="fas fa-user-tie"></i></div>
    <div class="is-hero-text">
      <h2>Alumnos en Prácticas Profesionales</h2>
      <p>Gestiona alumnos, acepta solicitudes y actualiza su información en organismos receptores.</p>
    </div>
    <div class="ms-auto d-flex flex-column align-items-end gap-2" style="z-index:1;">
      <span class="is-hero-badge"><i class="fas fa-shield-alt me-1"></i>Administración</span>
      <button type="button" id="btnEvaluacionesIntegrales"
        class="btn btn-sm fw-semibold position-relative"
        style="background:rgba(255,255,255,.15);border:1.5px solid rgba(255,255,255,.35);color:#fff;border-radius:2rem;padding:.4rem 1rem;">
        <i class="fas fa-star me-1"></i>Nuevas Evaluaciones
        <span id="badgeEvaluaciones" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none"></span>
      </button>
    </div>
  </div>

  <!-- ════════════════ 2. STAT CARDS ════════════════ -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md col-xl">
      <div class="card is-stat p-3">
        <div class="d-flex align-items-start gap-3">
          <div class="icon-box" style="background:rgba(0,32,74,.1);color:#00204a;"><i class="fas fa-users"></i></div>
          <div>
            <div class="stat-val" id="statTotal" style="color:#00204a;">—</div>
            <div class="stat-lbl">Total</div>
          </div>
        </div>
        <div class="stat-bar"><div class="stat-bar-fill" id="barTotal" style="width:100%;background:#00204a;"></div></div>
      </div>
    </div>
    <div class="col-6 col-md col-xl">
      <div class="card is-stat p-3">
        <div class="d-flex align-items-start gap-3">
          <div class="icon-box" style="background:#fef3c7;color:#d97706;"><i class="fas fa-user-clock"></i></div>
          <div>
            <div class="stat-val" id="statPending" style="color:#d97706;">—</div>
            <div class="stat-lbl">Pendientes</div>
          </div>
        </div>
        <div class="stat-bar"><div class="stat-bar-fill" id="barPending" style="width:0%;background:#d97706;"></div></div>
      </div>
    </div>
    <div class="col-6 col-md col-xl">
      <div class="card is-stat p-3">
        <div class="d-flex align-items-start gap-3">
          <div class="icon-box" style="background:#d1fae5;color:#01643D;"><i class="fas fa-user-check"></i></div>
          <div>
            <div class="stat-val" id="statAccepted" style="color:#01643D;">—</div>
            <div class="stat-lbl">Aceptados</div>
          </div>
        </div>
        <div class="stat-bar"><div class="stat-bar-fill" id="barAccepted" style="width:0%;background:#01643D;"></div></div>
      </div>
    </div>
    <div class="col-6 col-md col-xl">
      <div class="card is-stat p-3">
        <div class="d-flex align-items-start gap-3">
          <div class="icon-box" style="background:#fee2e2;color:#dc2626;"><i class="fas fa-user-slash"></i></div>
          <div>
            <div class="stat-val" id="statRejected" style="color:#dc2626;">—</div>
            <div class="stat-lbl">Rechazados</div>
          </div>
        </div>
        <div class="stat-bar"><div class="stat-bar-fill" id="barRejected" style="width:0%;background:#dc2626;"></div></div>
      </div>
    </div>
    <div class="col-6 col-md col-xl">
      <div class="card is-stat p-3">
        <div class="d-flex align-items-start gap-3">
          <div class="icon-box" style="background:#d1fae5;color:#059669;"><i class="fas fa-graduation-cap"></i></div>
          <div>
            <div class="stat-val" id="statFinished" style="color:#059669;">—</div>
            <div class="stat-lbl">Finalizados</div>
          </div>
        </div>
        <div class="stat-bar"><div class="stat-bar-fill" id="barFinished" style="width:0%;background:#059669;"></div></div>
      </div>
    </div>
  </div>

  <!-- ════════════════ 3. TABLA ════════════════ -->
  <div class="is-table-card">

    <!-- Filtros -->
    <div class="is-tabs">
      <span class="fw-semibold small me-1" style="color:#6b7280;font-size:.7rem;letter-spacing:.06em;text-transform:uppercase;">
        <i class="fas fa-filter me-1"></i>Filtrar
      </span>
      <button class="is-tab-btn is-filter-btn active" data-filter="all"><i class="fas fa-border-all"></i> Todos <span class="tab-cnt" id="cntAll">—</span></button>
      <button class="is-tab-btn is-filter-btn" data-filter="0"><i class="fas fa-clock"></i> Pendientes <span class="tab-cnt" id="cntPending">—</span></button>
      <button class="is-tab-btn is-filter-btn" data-filter="1"><i class="fas fa-check-circle"></i> Aceptados <span class="tab-cnt" id="cntAccepted">—</span></button>
      <button class="is-tab-btn is-filter-btn" data-filter="2"><i class="fas fa-times-circle"></i> Rechazados <span class="tab-cnt" id="cntRejected">—</span></button>
      <button class="is-tab-btn is-filter-btn" data-filter="finished"><i class="fas fa-graduation-cap"></i> Finalizados <span class="tab-cnt" id="cntFinished">—</span></button>
    </div>

    <!-- Buscador -->
    <div class="is-search-row">
      <i class="fas fa-search" style="color:#01643D;flex-shrink:0;"></i>
      <input type="text" id="searchStudents" placeholder="Buscar por nombre, matrícula, programa…">
    </div>

    <!-- Tabla -->
    <div class="is-table-scroll">
      <table class="table is-table align-middle mb-0 w-100" id="studentsTable">
        <thead><tr>
          <th>#</th>
          <th>Alumno</th>
          <th>Matrícula</th>
          <th class="d-none d-md-table-cell">Programa</th>
          <th class="d-none d-md-table-cell">Período</th>
          <th class="d-none d-lg-table-cell">Tipo</th>
          <th>Estado</th>
          <th class="d-none d-md-table-cell">Registro</th>
          <th>Acciones</th>
        </tr></thead>
        <tbody id="tbodyStudents">
          <tr><td colspan="9" class="text-center py-5 text-muted">
            <i class="fas fa-spinner fa-spin me-2" style="color:#01643D;"></i>Cargando alumnos…
          </td></tr>
        </tbody>
      </table>
    </div>
  </div>

</div><!-- /is-wrap -->

<!-- ════════════ MODAL EDITAR ALUMNO ════════════ -->
<div class="modal fade" id="editStudentModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0 rounded-4 shadow-lg">
      <div class="is-modal-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold text-white">
          <i class="fas fa-user-edit me-2"></i>Editar Alumno
        </h6>
        <button type="button" class="btn-close is-modal-header" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body p-4">
        <input type="hidden" id="editId">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label small fw-semibold">Matrícula <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="editMatricula" required>
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">Grupo</label>
            <input type="text" class="form-control" id="editGrupo">
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">Nombre Completo <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="editNombre" required>
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">CURP</label>
            <input type="text" class="form-control" id="editCurp">
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">Fecha de Nacimiento</label>
            <input type="date" class="form-control" id="editFechaNacimiento">
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">Género</label>
            <select class="form-select" id="editGenero">
              <option value="Femenino">Femenino</option>
              <option value="Masculino">Masculino</option>
              <option value="Otro">Otro</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Correo electrónico <span class="text-danger">*</span></label>
            <input type="email" class="form-control" id="editEmail" required>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Teléfono</label>
            <input type="text" class="form-control" id="editTelefono">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Programa Académico</label>
            <input type="text" class="form-control" id="editPrograma">
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-semibold">Período</label>
            <input type="number" class="form-control" id="editPeriodo" min="1">
          </div>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-unimo rounded-pill px-4" id="saveEditBtn">
          <i class="fas fa-save me-1"></i>Guardar cambios
        </button>
      </div>
    </div>
  </div>
</div>

<?php include 'modalVerEvaluacionesAdmin.php'; ?>

<script src="view/assets/js/ajax/practices/students.js"></script>
