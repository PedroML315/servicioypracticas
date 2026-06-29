<?php
$teacherName      = trim(($_SESSION['user']['firstname'] ?? '') . ' ' . ($_SESSION['user']['lastname'] ?? ''));
$teacherInitials  = strtoupper(substr($_SESSION['user']['firstname'] ?? 'E', 0, 1) . substr($_SESSION['user']['lastname'] ?? '', 0, 1));
?>
<div style="padding:1.5rem 2rem 3rem;">
<style>
/* ── Dashboard Encargado ─────────────────────────────── */
.td-hero {
    background: linear-gradient(135deg, #2A7E5D 0%, #1e5e44 100%);
    border-radius: 1rem;
    color: #fff;
    padding: 1.75rem 2rem;
    margin-bottom: 1.75rem;
    position: relative;
    overflow: hidden;
}
.td-hero::after {
    content: '';
    position: absolute;
    width: 220px; height: 220px;
    border-radius: 50%;
    background: rgba(255,255,255,.08);
    right: -50px; top: -70px;
    pointer-events: none;
}
.td-hero::before {
    content: '';
    position: absolute;
    width: 140px; height: 140px;
    border-radius: 50%;
    background: rgba(255,255,255,.06);
    right: 100px; bottom: -60px;
    pointer-events: none;
}
.td-avatar {
    width: 54px; height: 54px;
    border-radius: 50%;
    background: rgba(255,255,255,.25);
    font-size: 1.25rem;
    font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    color: #fff;
    flex-shrink: 0;
}
.td-stat-card {
    border-radius: .875rem;
    border: none;
    box-shadow: 0 2px 12px rgba(0,0,0,.07);
    transition: transform .18s, box-shadow .18s;
    overflow: hidden;
}
.td-stat-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,.12); }
.td-stat-icon {
    width: 52px; height: 52px;
    border-radius: .75rem;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
}
.td-stat-icon.blue   { background: #d1ead9; color: #2A7E5D; }
.td-stat-icon.green  { background: #d1ead9; color: #2A7E5D; }
.td-stat-icon.yellow { background: #fef9c3; color: #a16207; }
.td-stat-icon.red    { background: #fee2e2; color: #b91c1c; }

/* Tabs */
#tabsProfesor .nav-link {
    color: #2A7E5D;
    font-weight: 500;
    border: none;
    border-bottom: 3px solid transparent;
    padding: .6rem 1.1rem;
    border-radius: 0;
    transition: color .15s, border-color .15s;
}
#tabsProfesor .nav-link:hover { color: #1e5e44; background: #f0f9f4; }
#tabsProfesor .nav-link.active {
    color: #2A7E5D;
    border-bottom-color: #2A7E5D;
    background: transparent;
}

/* Area buttons */
.pp-area-btn {
    border-radius: .625rem !important;
    font-weight: 500;
    padding: .45rem 1rem;
    transition: all .15s;
}
.pp-area-btn.active {
    background: #2A7E5D !important;
    border-color: #2A7E5D !important;
    color: #fff !important;
    box-shadow: 0 3px 10px rgba(42,126,93,.3);
}
.pp-area-btn:not(.active):hover {
    background: #e6f4ee !important;
    border-color: #2A7E5D !important;
    color: #2A7E5D !important;
}

/* Tables */
.td-table thead th {
    background: #f8fafc;
    font-size: .78rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: #2A7E5D;
    border-bottom: 2px solid #e2e8f0;
    padding: .65rem 1rem;
}
.td-table tbody td { padding: .7rem 1rem; vertical-align: middle; }
.td-table tbody tr:hover { background: #f8fafc; }

/* Student avatar circle */
.stu-avatar {
    width: 34px; height: 34px;
    border-radius: 50%;
    font-size: .8rem; font-weight: 700;
    display: inline-flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    color: #fff;
}

/* Card section headers */
.section-card { border-radius: .875rem; border: none; box-shadow: 0 2px 12px rgba(0,0,0,.07); overflow: hidden; }
.section-card .card-header {
    background: #fff;
    border-bottom: 1px solid #f1f5f9;
    padding: .9rem 1.25rem;
}

/* Annotation cards */
.annotation-item {
    background: #f8fafc;
    border-left: 3px solid #2A7E5D;
    border-radius: 0 .5rem .5rem 0;
    padding: .75rem 1rem;
    margin-bottom: .625rem;
}
.annotation-item:hover { background: #f1f5f9; }

/* Modal pills */
#tabsModal .nav-link {
    border-radius: .5rem;
    color: #2A7E5D;
    font-size: .875rem;
    transition: background .15s, color .15s;
}
#tabsModal .nav-link:hover:not(.active) { background: #e6f4ee; color: #1e5e44; }
#tabsModal .nav-link.active { background: #2A7E5D; color: #fff; }

/* Progress bar */
.td-progress { height: 6px; border-radius: 3px; background: #e2e8f0; }
.td-progress-bar { height: 6px; border-radius: 3px; background: linear-gradient(90deg,#2A7E5D,#1e5e44); }

/* Empty state */
.td-empty { text-align: center; padding: 2rem 1rem; color: #94a3b8; }
.td-empty i { font-size: 2.5rem; margin-bottom: .5rem; display: block; }

/* Pending badge pill */
.pend-section-title {
    font-size: .8rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .06em; color: #6b7280; margin-bottom: .6rem;
    display: flex; align-items: center; gap: .5rem;
}
.pend-cnt {
    background: #fee2e2; color: #b91c1c;
    border-radius: 2rem; padding: .15rem .55rem;
    font-size: .72rem; font-weight: 700;
}
.pend-cnt.yellow { background: #fef9c3; color: #a16207; }
.pend-cnt.green  { background: #d1fae5; color: #065f46; }

/* ── Línea de tiempo SS ── */
.ss-timeline { position: relative; padding-left: 2rem; }
.ss-timeline::before { content: ''; position: absolute; left: .65rem; top: 0; bottom: 0;
    width: 2px; background: #e2e8f0; border-radius: 2px; }
.ss-tl-item { position: relative; margin-bottom: 1.4rem; }
.ss-tl-item:last-child { margin-bottom: 0; }
.ss-tl-dot { position: absolute; left: -1.65rem; top: .3rem;
    width: 13px; height: 13px; border-radius: 50%; border: 2px solid #fff;
    box-shadow: 0 0 0 2px #94a3b8; background: #16697a; }
.ss-tl-card { background: #f8fafc; border: 1px solid #e2e8f0;
    border-radius: .6rem; padding: .65rem .9rem; }
.ss-tl-title { font-weight: 600; font-size: .875rem; color: #037435; }
.ss-tl-meta  { font-size: .78rem; color: #64748b; margin-top: .15rem; }
</style>

<!-- ══════════════════════════════════════════════════════
     HERO HEADER
══════════════════════════════════════════════════════ -->
<div class="td-hero d-flex align-items-center gap-3">
    <div class="td-avatar"><?= $teacherInitials ?></div>
    <div>
        <div class="fw-bold fs-5 mb-0">Bienvenido, <?= htmlspecialchars($teacherName) ?></div>
        <div style="opacity:.8;font-size:.9rem;">Panel de encargado &mdash; <?= date('d \d\e F, Y') ?></div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════
     STAT CARDS
══════════════════════════════════════════════════════ -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="td-stat-card card h-100">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="td-stat-icon blue"><i class="fas fa-users"></i></div>
                <div>
                    <div class="fs-3 fw-bold lh-1 mb-1" id="statSsStudents">&#8211;</div>
                    <div class="small text-muted">Alumnos SS</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="td-stat-card card h-100">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="td-stat-icon green"><i class="fas fa-folder-open"></i></div>
                <div>
                    <div class="fs-3 fw-bold lh-1 mb-1" id="statPpAreas">&#8211;</div>
                    <div class="small text-muted">Areas PP</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="td-stat-card card h-100">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="td-stat-icon yellow"><i class="fas fa-user-graduate"></i></div>
                <div>
                    <div class="fs-3 fw-bold lh-1 mb-1" id="statPpStudents">&#8211;</div>
                    <div class="small text-muted">Alumnos PP activos</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="td-stat-card card h-100">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="td-stat-icon red"><i class="fas fa-clock"></i></div>
                <div>
                    <div class="fs-3 fw-bold lh-1 mb-1" id="statPendingAtt">&#8211;</div>
                    <div class="small text-muted">Asistencias pendientes</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════
     TABS
══════════════════════════════════════════════════════ -->
<ul class="nav nav-tabs mb-3" id="tabsProfesor" style="border-bottom:2px solid #e2e8f0;">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabPendientes">
            <i class="fas fa-bell me-1"></i>Pendientes
            <span id="badgeTabPendientes" class="badge rounded-pill ms-1 d-none"
                style="background:#b91c1c;color:#fff;font-size:.7rem;"></span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabSS">
            <i class="fas fa-users me-1"></i>Servicio Social
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabPP">
            <i class="fas fa-briefcase me-1"></i>Practicas Profesionales
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabEvents">
            <i class="fas fa-calendar-alt me-1"></i>Mis Eventos
        </button>
    </li>
</ul>

<div class="tab-content" id="tabsProfesorContent">

    <!-- ─── TAB: Pendientes ──────────────────────────────── -->
    <div class="tab-pane fade show active" id="tabPendientes">

        <!-- Postulaciones PP pendientes -->
        <div class="section-card card mb-3">
            <div class="card-header d-flex align-items-center gap-2">
                <span class="td-stat-icon yellow" style="width:32px;height:32px;font-size:.95rem;border-radius:.5rem;">
                    <i class="fas fa-user-clock"></i>
                </span>
                <span class="fw-semibold">Postulaciones a áreas de Prácticas pendientes</span>
                <span class="badge rounded-pill ms-1" id="badgePendPostulaciones"
                    style="background:#fef9c3;color:#a16207;font-size:.78rem;">0</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table td-table mb-0">
                        <thead><tr>
                            <th>Alumno</th><th>Matrícula</th><th>Programa</th>
                            <th>Área solicitada</th><th>Fecha</th>
                            <th class="text-center">Acciones</th>
                        </tr></thead>
                        <tbody id="tbodyPendPostulaciones">
                            <tr><td colspan="6" class="td-empty"><i class="fas fa-spinner fa-spin"></i>Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Asistencias PP pendientes -->
        <div class="section-card card mb-3">
            <div class="card-header d-flex align-items-center gap-2">
                <span class="td-stat-icon red" style="width:32px;height:32px;font-size:.95rem;border-radius:.5rem;">
                    <i class="fas fa-clock"></i>
                </span>
                <span class="fw-semibold">Asistencias de Prácticas pendientes de aprobación</span>
                <span class="badge rounded-pill ms-1" id="badgePendAsistenciasPP"
                    style="background:#fee2e2;color:#b91c1c;font-size:.78rem;">0</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table td-table mb-0">
                        <thead><tr>
                            <th>Alumno</th><th>Área</th><th>Fecha</th>
                            <th>Horas</th><th>Actividad</th>
                            <th class="text-center">Acciones</th>
                        </tr></thead>
                        <tbody id="tbodyPendAsistenciasPP">
                            <tr><td colspan="6" class="td-empty"><i class="fas fa-spinner fa-spin"></i>Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div><!-- /tabPendientes -->

    <!-- ─── TAB: Servicio Social ─────────────────────────── -->
    <div class="tab-pane fade" id="tabSS">
        <div class="section-card card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="fw-semibold d-flex align-items-center gap-2">
                    <span class="td-stat-icon blue" style="width:32px;height:32px;font-size:.95rem;border-radius:.5rem;background:#d4eef1;color:#16697a;">
                        <i class="fas fa-users"></i>
                    </span>
                    Alumnos en mis eventos
                </span>
                <div class="input-group input-group-sm" style="max-width:220px;">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="searchSsStudent" class="form-control border-start-0 ps-0"
                        placeholder="Buscar alumno...">
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table td-table mb-0">
                        <thead>
                            <tr>
                                <th>Alumno</th>
                                <th>Matrícula</th>
                                <th>Email</th>
                                <th class="text-center">Eventos</th>
                                <th class="text-center">Historial</th>
                            </tr>
                        </thead>
                        <tbody id="tbodySsStudents">
                            <tr><td colspan="5" class="td-empty"><i class="fas fa-spinner fa-spin"></i>Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal historial de eventos del alumno -->
    <div class="modal fade" id="modalHistorialSS" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border-radius:1rem;border:none;">
                <div class="modal-header border-0 pb-0">
                    <div class="d-flex align-items-center gap-3">
                        <span id="modalHistorialAvatar" style="width:44px;height:44px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:1.1rem;color:#fff;"></span>
                        <div>
                            <h5 class="modal-title mb-0 fw-bold" id="modalHistorialNombre">—</h5>
                            <small class="text-muted" id="modalHistorialEmail"></small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-2">
                    <p class="text-muted small mb-3"><i class="fas fa-stream me-1"></i>Historial de participación en eventos</p>
                    <div id="timelineSS" class="ss-timeline">
                        <div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── TAB: Practicas Profesionales ─────────────────── -->
    <div class="tab-pane fade" id="tabPP">

        <!-- Area selector -->
        <div class="section-card card mb-3">
            <div class="card-header">
                <span class="fw-semibold d-flex align-items-center gap-2">
                    <span class="td-stat-icon green" style="width:32px;height:32px;font-size:.95rem;border-radius:.5rem;">
                        <i class="fas fa-folder-open"></i>
                    </span>
                    Selecciona un area
                </span>
            </div>
            <div class="card-body d-flex flex-wrap gap-2" id="ppAreaButtons">
                <span class="text-muted small align-self-center">
                    <i class="fas fa-spinner fa-spin me-1"></i>Cargando areas...
                </span>
            </div>
        </div>

        <!-- Solicitudes de postulación pendientes -->
        <div id="panelPostulacionesPP" class="section-card card mb-3 d-none">
            <div class="card-header d-flex align-items-center gap-2">
                <span class="td-stat-icon" style="background:#fef3c7;color:#d97706;width:32px;height:32px;font-size:.95rem;border-radius:.5rem;display:inline-flex;align-items:center;justify-content:center;">
                    <i class="fas fa-user-clock"></i>
                </span>
                <span class="fw-semibold">Solicitudes de postulación pendientes</span>
                <span class="badge rounded-pill ms-1" id="badgePostulacionesPP"
                    style="background:#fef9c3;color:#a16207;font-size:.78rem;">0</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table td-table mb-0">
                        <thead>
                            <tr>
                                <th>Alumno</th><th>Matrícula</th>
                                <th>Programa</th><th>Fecha solicitud</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyPostulacionesPP">
                            <tr><td colspan="5" class="td-empty">Sin solicitudes pendientes</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Asistencias pendientes -->
        <div id="panelPendingPP" class="section-card card mb-3 d-none">
            <div class="card-header d-flex align-items-center gap-2">
                <span class="td-stat-icon yellow" style="width:32px;height:32px;font-size:.95rem;border-radius:.5rem;">
                    <i class="fas fa-clock"></i>
                </span>
                <span class="fw-semibold">Asistencias pendientes de aprobacion</span>
                <span class="badge rounded-pill ms-1" id="badgePendingPP"
                    style="background:#fef9c3;color:#a16207;font-size:.78rem;">0</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table td-table mb-0">
                        <thead>
                            <tr>
                                <th>Alumno</th><th>Fecha</th><th>Horas</th>
                                <th>Observaciones</th><th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyPendingPP">
                            <tr><td colspan="5" class="td-empty">Sin asistencias pendientes</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Alumnos activos -->
        <div id="panelStudentsPP" class="section-card card d-none">
            <div class="card-header d-flex align-items-center gap-2">
                <span class="td-stat-icon green" style="width:32px;height:32px;font-size:.95rem;border-radius:.5rem;">
                    <i class="fas fa-user-graduate"></i>
                </span>
                <span class="fw-semibold">Alumnos activos en el area</span>
                <span class="badge ms-1" id="badgePendingReports"
                    style="background:#fef9c3;color:#a16207;font-size:.78rem;display:none;">
                    <i class="fas fa-file-alt me-1"></i><span></span> reporte(s) pendiente(s)
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table td-table mb-0">
                        <thead>
                            <tr>
                                <th>Alumno</th><th>Matricula</th>
                                <th>Progreso de horas</th><th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyPpStudents">
                            <tr><td colspan="4" class="td-empty">
                                <i class="fas fa-hand-pointer"></i>Selecciona un area para ver los alumnos
                            </td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── TAB: Mis Eventos ──────────────────────────────── -->
    <div class="tab-pane fade" id="tabEvents">
        <div class="mt-3 events row">
            <!-- Cargado por inicio.js -->
        </div>
    </div>

</div>

<!-- ══════════════════════════════════════════════════════
     MODAL: Detalle alumno PP
══════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalAlumnoDetalle" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius:1rem;overflow:hidden;">

            <!-- Header con gradiente -->
            <div class="modal-header border-0 text-white px-4 py-3"
                style="background:linear-gradient(135deg,#2A7E5D 0%,#1e5e44 100%);">
                <div class="d-flex align-items-center gap-3">
                    <div class="td-avatar" id="modalAlumnoAvatar" style="background:rgba(255,255,255,.25);font-size:1rem;">??</div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="modalAlumnoNombre">Alumno</h5>
                        <small style="opacity:.8;" id="modalAlumnoMatricula"></small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body px-4 pt-3">
                <!-- Pills internas -->
                <ul class="nav nav-pills gap-1 mb-4" id="tabsModal">
                    <li class="nav-item">
                        <button class="nav-link active d-flex align-items-center gap-1"
                            data-bs-toggle="pill" data-bs-target="#mpChart">
                            <i class="fas fa-chart-bar"></i><span class="d-none d-sm-inline ms-1">Grafica de horas</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link d-flex align-items-center gap-1"
                            data-bs-toggle="pill" data-bs-target="#mpHistory">
                            <i class="fas fa-list-alt"></i><span class="d-none d-sm-inline ms-1">Historial</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link d-flex align-items-center gap-1"
                            data-bs-toggle="pill" data-bs-target="#mpAnnotations">
                            <i class="fas fa-sticky-note"></i>
                            <span class="d-none d-sm-inline ms-1">Anotaciones</span>
                            <span class="badge bg-white ms-1" id="badgeAnnotations"
                                style="color:#16697a;font-size:.72rem;"></span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link d-flex align-items-center gap-1"
                            data-bs-toggle="pill" data-bs-target="#mpReport">
                            <i class="fas fa-file-alt"></i>
                            <span class="d-none d-sm-inline ms-1">Reporte</span>
                            <span class="badge ms-1" id="badgeReport"
                                style="background:#fef9c3;color:#a16207;font-size:.72rem;display:none;">Pendiente</span>
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- Grafica -->
                    <div class="tab-pane fade show active" id="mpChart">
                        <div style="position:relative;height:300px;">
                            <canvas id="horasChart"></canvas>
                        </div>
                        <div class="td-empty d-none" id="chartNoData">
                            <i class="fas fa-chart-bar" style="font-size:2.5rem;color:#cbd5e1;"></i>
                            <p class="mt-2 mb-0">Sin registros de asistencia aprobados</p>
                        </div>
                    </div>

                    <!-- Historial -->
                    <div class="tab-pane fade" id="mpHistory">
                        <div class="table-responsive">
                            <table class="table td-table">
                                <thead>
                                    <tr><th>Fecha</th><th>Horas</th><th>Estado</th><th>Observaciones</th></tr>
                                </thead>
                                <tbody id="tbodyHistorial">
                                    <tr><td colspan="4" class="td-empty"><i class="fas fa-spinner fa-spin"></i>Cargando...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Anotaciones -->
                    <div class="tab-pane fade" id="mpAnnotations">
                        <div class="d-flex gap-2 mb-3">
                            <textarea id="newAnnotationText" class="form-control" rows="2"
                                style="resize:none;border-radius:.625rem;"
                                placeholder="Escribe una nueva anotacion..."></textarea>
                            <button class="btn btn-primary align-self-end flex-shrink-0" id="btnAddAnnotation"
                                style="border-radius:.625rem;">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <div id="annotationsList">
                            <div class="td-empty"><i class="fas fa-sticky-note"></i><p class="mt-2 mb-0">Sin anotaciones</p></div>
                        </div>
                    </div>

                    <!-- Reporte -->
                    <div class="tab-pane fade" id="mpReport">
                        <div id="reportContent">
                            <div class="td-empty"><i class="fas fa-spinner fa-spin"></i> Cargando reporte...</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════
     MODAL: Anotaciones alumno SS
══════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalSsAnnotations" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius:1rem;overflow:hidden;">
            <div class="modal-header border-0 text-white px-4 py-3"
                style="background:linear-gradient(135deg,#2A7E5D 0%,#1e5e44 100%);">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,.25);
                        display:flex;align-items:center;justify-content:center;font-size:1.1rem;">
                        <i class="fas fa-sticky-note"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0">Anotaciones</h5>
                        <small style="opacity:.8;" id="modalSsNombre"></small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 pt-3">
                <div class="d-flex gap-2 mb-3">
                    <textarea id="newSsAnnotationText" class="form-control" rows="2"
                        style="resize:none;border-radius:.625rem;"
                        placeholder="Escribe una anotacion..."></textarea>
                    <button class="btn btn-info text-white align-self-end flex-shrink-0" id="btnAddSsAnnotation"
                        style="border-radius:.625rem;">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                <div id="ssAnnotationsList">
                    <div class="td-empty"><i class="fas fa-sticky-note"></i><p class="mt-2 mb-0">Sin anotaciones</p></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Campos ocultos para contexto JS -->
<input type="hidden" id="currentPpStudentId" value="">
<input type="hidden" id="currentPpAreaId" value="">
<input type="hidden" id="currentPpPostulacionId" value="">
<input type="hidden" id="currentSsStudentId" value="">

<?php include 'view/pages/practicas/modalevaluaciones.php'; ?>

<script src="view/assets/js/ajax/inicio.js"></script>
<script src="view/assets/js/organismo/teacher_dashboard.js"></script>
