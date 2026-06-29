<link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>

<style>
/* ============================================================
   Estudiantes en Prácticas — Vista Encargado (Teacher)
   Paleta: navy #00204a | green #01643D | lime #c6db53
   ============================================================ */
:root {
  --sip-navy:   #00204a;
  --sip-green:  #01643D;
  --sip-green2: #2A7E5D;
  --sip-lime:   #c6db53;
  --sip-bg:     #f4f6f8;
  --sip-border: #e4e9ef;
}

.sip-wrap {
  background: var(--sip-bg);
  padding: 1.5rem 2rem 3rem;
  min-height: calc(100vh - 58px);
}

/* ── Hero ── */
.sip-hero {
  background: linear-gradient(135deg, var(--sip-navy) 0%, var(--sip-green) 65%, var(--sip-green2) 100%);
  border-radius: 1rem;
  color: #fff;
  padding: 1.6rem 2rem;
  margin-bottom: 1.75rem;
  display: flex;
  align-items: center;
  gap: 1.25rem;
  box-shadow: 0 6px 24px rgba(0,32,74,.2);
  position: relative;
  overflow: hidden;
}
.sip-hero::before {
  content: ''; position: absolute; right: -40px; top: -40px;
  width: 180px; height: 180px; border-radius: 50%;
  background: rgba(198,219,83,.1);
}
.sip-hero::after {
  content: ''; position: absolute; right: 100px; bottom: -55px;
  width: 120px; height: 120px; border-radius: 50%;
  background: rgba(255,255,255,.05);
}
.sip-hero-icon {
  width: 58px; height: 58px; border-radius: 50%;
  background: rgba(255,255,255,.15);
  border: 2px solid rgba(255,255,255,.25);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.5rem; flex-shrink: 0; z-index: 1;
}
.sip-hero-text { z-index: 1; }
.sip-hero-text h2 { font-size: 1.25rem; font-weight: 700; margin: 0 0 .2rem; }
.sip-hero-text p  { margin: 0; opacity: .88; font-size: .85rem; }
.sip-hero-actions { margin-left: auto; z-index: 1; display: flex; gap: .6rem; flex-wrap: wrap; align-items: center; }

/* ── Table card ── */
.sip-card {
  background: #fff;
  border-radius: 1rem;
  border: 1px solid var(--sip-border);
  box-shadow: 0 2px 12px rgba(0,0,0,.07);
  overflow: hidden;
}
.sip-card-header {
  padding: .9rem 1.25rem;
  border-bottom: 1px solid var(--sip-border);
  display: flex; align-items: center; gap: .6rem;
}
.sip-card-header-title {
  font-size: .95rem; font-weight: 700; color: #1a2530;
  display: flex; align-items: center; gap: .5rem;
}
.sip-icon-wrap {
  width: 30px; height: 30px; border-radius: 8px;
  display: inline-flex; align-items: center; justify-content: center; font-size: .8rem;
  background: #d1fae5; color: var(--sip-green);
}
.sip-card .card-body { padding: 1.25rem; }

/* ── DataTable overrides ── */
.sip-card .dataTables_wrapper .dataTables_filter input {
  border-radius: 2rem; border: 1.5px solid var(--sip-border);
  padding: .3rem .85rem; font-size: .82rem;
}
.sip-card .dataTables_wrapper .dataTables_filter input:focus {
  outline: none; border-color: var(--sip-green); box-shadow: 0 0 0 3px rgba(1,100,61,.1);
}
.sip-card .dt-buttons .btn {
  border-radius: 2rem; font-size: .78rem; padding: .3rem .85rem;
  border: 1.5px solid var(--sip-border); background: #fff; color: #374151;
}
.sip-card .dt-buttons .btn:hover { background: #f0faf5; border-color: var(--sip-green); color: var(--sip-green); }

/* Sticky header */
.sip-card .dataTables_scrollHead { position: sticky; top: 0; z-index: 3; }

table.dataTable.compact > thead > tr > th,
table.dataTable.compact > tbody > tr > td { padding-top: .5rem; padding-bottom: .5rem; }

/* Header row */
.sip-card table.dataTable thead th {
  background: #f8f9fc; color: #6b7280;
  font-size: .7rem; font-weight: 700;
  letter-spacing: .06em; text-transform: uppercase;
  border-bottom: 1px solid var(--sip-border);
}
.sip-card table.dataTable tbody tr { transition: background .15s; }
.sip-card table.dataTable tbody tr:hover { background: #f0faf5 !important; }
.sip-card table.dataTable tbody td { border-bottom: 1px solid #f0f3f7; vertical-align: middle; font-size: .83rem; }

/* Clip cells */
.dt-clip { max-width: 260px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
@media (max-width: 992px) { .dt-clip { max-width: 160px; } }

/* Detail card (responsive) */
.dt-details-card { width: 100%; }
.dt-details-card .row + .row { margin-top: .35rem; }
.dt-k { font-weight: 600; color: var(--sip-green); }
.dt-v { word-break: break-word; }

/* Action button */
.sip-action-btn {
  width: 32px; height: 32px; border-radius: 8px; border: none; cursor: pointer;
  display: inline-flex; align-items: center; justify-content: center;
  font-size: .8rem; transition: all .15s;
  background: #dbeafe; color: #2563eb;
}
.sip-action-btn:hover { background: #2563eb; color: #fff; }

/* ── Modal solicitudes ── */
.sip-modal-header {
  background: linear-gradient(135deg, var(--sip-navy), var(--sip-green));
  color: #fff; border-radius: .5rem .5rem 0 0; padding: 1rem 1.5rem;
}
.sip-modal-header .btn-close { filter: invert(1) brightness(2); }

/* Solicitud cards in modal */
.sol-card {
  border: 0; border-radius: .85rem;
  box-shadow: 0 2px 8px rgba(0,0,0,.07);
  margin-bottom: .85rem; overflow: hidden;
}
.sol-card .card-body { border-left: 5px solid #e5e7eb; padding: 1rem 1.1rem; }
.sol-card.sol-aprobada .card-body { border-left-color: #16a34a; }
.sol-card.sol-rechazada .card-body { border-left-color: #dc2626; }
.sol-card.sol-pendiente .card-body { border-left-color: #d97706; }

/* Pills */
.sip-pill {
  display: inline-flex; align-items: center; gap: .3em;
  font-size: .72rem; font-weight: 700; padding: .28em .72em; border-radius: 50rem;
}
.sip-pill-green   { background: #d1fae5; color: #059669; border: 1.5px solid #6ee7b7; }
.sip-pill-blue    { background: #dbeafe; color: #1d4ed8; border: 1.5px solid #93c5fd; }
.sip-pill-warning { background: #fef3c7; color: #b45309; border: 1.5px solid #fcd34d; }
/* ── Cards de aspirantes ── */
.asp-card {
  border: 0; border-radius: .85rem;
  box-shadow: 0 2px 8px rgba(0,0,0,.07);
  margin-bottom: .85rem; overflow: hidden;
}
.asp-card .card-body { border-left: 5px solid #d97706; padding: 1rem 1.1rem; }
.asp-avatar {
  width: 42px; height: 42px; border-radius: 50%;
  background: linear-gradient(135deg, #01643D, #2A7E5D);
  color: #fff; font-weight: 700; font-size: .95rem;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.asp-btn-accept {
  background: #dcfce7; color: #16a34a; border: none;
  border-radius: 2rem; padding: .3rem .85rem; font-size: .8rem; font-weight: 600; cursor: pointer;
  transition: all .15s; display: inline-flex; align-items: center; gap: .35rem;
}
.asp-btn-accept:hover { background: #16a34a; color: #fff; }
.asp-btn-reject {
  background: #fee2e2; color: #dc2626; border: none;
  border-radius: 2rem; padding: .3rem .85rem; font-size: .8rem; font-weight: 600; cursor: pointer;
  transition: all .15s; display: inline-flex; align-items: center; gap: .35rem;
}
.asp-btn-reject:hover { background: #dc2626; color: #fff; }
</style>

<div class="sip-wrap">

  <!-- ══ HERO ══ -->
  <div class="sip-hero">
    <div class="sip-hero-icon"><i class="fas fa-users"></i></div>
    <div class="sip-hero-text">
      <h2>Estudiantes en Prácticas Profesionales</h2>
      <p><i class="fas fa-chalkboard-teacher me-1"></i>Consulta y gestiona a los alumnos bajo tu supervisión</p>
    </div>
    <div class="sip-hero-actions">
      <button type="button" id="btnAspirantes"
        class="btn btn-sm fw-semibold position-relative"
        style="background:rgba(255,255,255,.15);border:1.5px solid rgba(255,255,255,.35);color:#fff;border-radius:2rem;padding:.4rem 1rem;"
        data-bs-toggle="modal" data-bs-target="#modalAspirantes">
        <i class="fas fa-user-clock me-1"></i>Aspirantes
        <span id="badgeAspirantes" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark d-none"></span>
      </button>
      <button type="button"
        class="btn btn-sm fw-semibold"
        style="background:rgba(255,255,255,.15);border:1.5px solid rgba(255,255,255,.35);color:#fff;border-radius:2rem;padding:.4rem 1rem;"
        data-bs-toggle="modal" data-bs-target="#modalSolicitudCapacitaciones">
        <i class="fas fa-chalkboard me-1"></i>Solicitudes de Capacitación
      </button>
      <button type="button" id="btnEvaluacionesIntegrales"
        class="btn btn-sm fw-semibold position-relative"
        style="background:rgba(255,255,255,.15);border:1.5px solid rgba(255,255,255,.35);color:#fff;border-radius:2rem;padding:.4rem 1rem;">
        <i class="fas fa-star me-1"></i>Nuevas Evaluaciones
        <span id="badgeEvaluaciones" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none"></span>
      </button>
    </div>
  </div>

  <!-- ══ TABLA ══ -->
  <div class="sip-card">
    <div class="sip-card-header">
      <div class="sip-card-header-title">
        <span class="sip-icon-wrap"><i class="fas fa-table"></i></span>
        Listado de Alumnos
      </div>
      <div id="toolbar" class="ms-auto"></div>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table id="studentsTable"
          class="table table-sm align-middle table-borderless w-100">
        </table>
      </div>
    </div>
  </div>

</div><!-- /sip-wrap -->

<!-- ══ MODAL ASPIRANTES PENDIENTES ══ -->
<div id="modalAspirantes" class="modal fade" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content border-0 rounded-4 shadow-lg">
      <div class="sip-modal-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold text-white">
          <i class="fas fa-user-clock me-2"></i>Aspirantes a Prácticas Profesionales
        </h6>
        <button type="button" class="btn-close sip-modal-header" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body p-3">
        <p class="text-muted small mb-3">
          <i class="fas fa-info-circle me-1"></i>
          Revisa las solicitudes pendientes y acepta o rechaza a cada aspirante.
        </p>
        <div id="aspirantesSearch" class="mb-3">
          <div class="input-group" style="max-width:360px;">
            <span class="input-group-text"><i class="fas fa-search"></i></span>
            <input type="search" id="inputBuscarAspirante" class="form-control" placeholder="Buscar por nombre o matrícula…">
          </div>
        </div>
        <div id="listaAspirantes">
          <div class="text-center py-5 text-muted">
            <i class="fas fa-spinner fa-spin me-2" style="color:#01643D;"></i>Cargando aspirantes…
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ══ MODAL SOLICITUDES DE CAPACITACIÓN ══ -->
<div id="modalSolicitudCapacitaciones" class="modal fade" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content border-0 rounded-4 shadow-lg">
      <div class="sip-modal-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold text-white">
          <i class="fas fa-chalkboard me-2"></i>Solicitudes de Capacitación
        </h6>
        <button type="button" class="btn-close sip-modal-header" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body p-3">
        <!-- La UI de filtros/búsqueda se inyecta aquí -->
        <div class="list-group"></div>
      </div>
    </div>
  </div>
</div>

<?php include 'modalVerEvaluacionesAdmin.php'; ?>

<script src="view/assets/js/organismo/students.js"></script>