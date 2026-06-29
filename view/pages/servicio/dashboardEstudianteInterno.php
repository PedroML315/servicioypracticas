<?php
$studentName = htmlspecialchars(trim(($_SESSION['user']['firstname'] ?? '') . ' ' . ($_SESSION['user']['lastname'] ?? '')), ENT_QUOTES, 'UTF-8');
$studentInitials = strtoupper(substr($_SESSION['user']['firstname'] ?? 'A', 0, 1) . substr($_SESSION['user']['lastname'] ?? '', 0, 1));
$degreeInfo = htmlspecialchars($_SESSION['user']['degree'] ?? '', ENT_QUOTES, 'UTF-8');

// CSRF token para generarCartaConclusionServicio
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$_csrfToken = $_SESSION['csrf_token'];
?>
<meta name="csrf-token" content="<?= htmlspecialchars($_csrfToken, ENT_QUOTES, 'UTF-8') ?>">
<!-- Plyr (video player) -->
<link rel="stylesheet" href="view/assets/libs/plyr/plyr.css">
<style>
/* ═══════════════════════════════════════════════
   MODAL VIDEO / PDF
═══════════════════════════════════════════════ */
.modal-video-content,
.modal-pdf-content {
    background: #0f172a;
    border-radius: 1rem;
    border: none;
    overflow: hidden;
}
.modal-video-content .modal-header,
.modal-pdf-content .modal-header {
    background: #037435;
    border-bottom: 1px solid rgba(255,255,255,.08);
    color: #fff;
    padding: .85rem 1.25rem;
}
.modal-video-content .modal-header .btn-close,
.modal-pdf-content .modal-header .btn-close {
    filter: invert(1) brightness(2);
}
.modal-video-content .modal-body {
    padding: 0;
    background: #000;
}
.modal-pdf-content .modal-body {
    padding: 0;
    background: #525659;
}
#pdfViewerIframeInterno {
    width: 100%;
    height: 76vh;
    border: none;
    display: block;
}
.plyr {
    border-radius: 0;
    --plyr-color-main: #01643D;
}
.hero-resource-btns-int2 {
    display: flex;
    gap: .65rem;
    flex-wrap: wrap;
    margin-top: 1.1rem;
}
.btn-hero-resource {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    padding: .5rem 1.15rem;
    border-radius: .6rem;
    font-size: .82rem;
    font-weight: 700;
    cursor: pointer;
    border: none;
    transition: transform .18s, box-shadow .18s;
    text-decoration: none;
}
.btn-hero-resource:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,.25); }
.btn-hero-video {
    background: rgba(255,255,255,.18);
    color: #fff;
    border: 1.5px solid rgba(255,255,255,.35);
    backdrop-filter: blur(4px);
}
.btn-hero-video:hover { background: rgba(255,255,255,.28); color: #fff; }
.btn-hero-pdf {
    background: rgba(198,219,83,.2);
    color: #f0ffd0;
    border: 1.5px solid rgba(198,219,83,.45);
    backdrop-filter: blur(4px);
}
.btn-hero-pdf:hover { background: rgba(198,219,83,.35); color: #fff; }
</style>
<style>
/* ============================================================
   Dashboard Alumno Servicio Social
   ============================================================ */
.ss-wrap {
  background: #f4f6f8;
  padding: 1.5rem 2rem 3rem;
  min-height: calc(100vh - 58px);
}

/* Hero */
.ss-hero {
  background: linear-gradient(135deg, #004a28 0%, #01643D 60%, #a4ad22 100%);
  border-radius: 1rem;
  color: #fff;
  padding: 1.6rem 2rem;
  margin-bottom: 1.75rem;
  display: flex;
  align-items: center;
  gap: 1.25rem;
  box-shadow: 0 6px 24px rgba(1,100,61,.22);
  position: relative;
  overflow: hidden;
}
.ss-hero::before {
  content: '';
  position: absolute;
  right: -50px; top: -50px;
  width: 200px; height: 200px;
  border-radius: 50%;
  background: rgba(255,255,255,.07);
}
.ss-hero::after {
  content: '';
  position: absolute;
  right: 80px; bottom: -60px;
  width: 130px; height: 130px;
  border-radius: 50%;
  background: rgba(255,255,255,.05);
}
.ss-avatar {
  width: 56px; height: 56px;
  border-radius: 50%;
  background: rgba(255,255,255,.22);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.3rem; font-weight: 700;
  flex-shrink: 0;
  border: 2px solid rgba(255,255,255,.35);
  z-index: 1;
}
.ss-hero-text { z-index: 1; }
.ss-hero-text h2 { font-size: 1.25rem; font-weight: 700; margin: 0 0 .2rem; }
.ss-hero-text p  { margin: 0; opacity: .88; font-size: .85rem; }
.ss-hero-badge {
  margin-left: auto;
  background: rgba(255,255,255,.2);
  border: 1px solid rgba(255,255,255,.35);
  border-radius: 2rem;
  padding: .28rem .85rem;
  font-size: .75rem; font-weight: 600;
  white-space: nowrap;
  z-index: 1;
}

/* Progress card */
.ss-progress-card {
  background: #fff;
  border-radius: 1rem;
  box-shadow: 0 2px 14px rgba(0,0,0,.07);
  border: 1px solid #e4e9ef;
  padding: 1.25rem 1.5rem;
  margin-bottom: 1.75rem;
  display: flex;
  align-items: center;
  gap: 2rem;
  flex-wrap: wrap;
}
.ss-points-big {
  font-size: 2.8rem;
  font-weight: 800;
  color: #01643D;
  line-height: 1;
}
.ss-points-of {
  font-size: .85rem;
  color: #6c757d;
  margin-top: .2rem;
}
.ss-progress-bar-wrap {
  flex: 1;
  min-width: 200px;
}
.ss-progress-bar-track {
  height: 10px;
  border-radius: 5px;
  background: #e9ecef;
  overflow: hidden;
  margin-top: .5rem;
}
.ss-progress-bar-fill {
  height: 10px;
  border-radius: 5px;
  background: linear-gradient(90deg, #01643D, #8DB94A);
  transition: width .6s ease;
}
.ss-progress-label {
  font-size: .78rem;
  color: #6c757d;
  display: flex;
  justify-content: space-between;
  margin-top: .35rem;
}

/* Main grid */
.ss-grid {
  display: grid;
  grid-template-columns: 1fr 340px;
  gap: 1.25rem;
  align-items: start;
}
@media (max-width: 1024px) {
  .ss-grid { grid-template-columns: 1fr; }
}

/* Panel cards */
.ss-panel {
  background: #fff;
  border-radius: 1rem;
  box-shadow: 0 2px 14px rgba(0,0,0,.07);
  border: 1px solid #e4e9ef;
  overflow: hidden;
}
.ss-panel-header {
  display: flex;
  align-items: center;
  gap: .65rem;
  padding: .9rem 1.25rem;
  border-bottom: 1px solid #f0f3f7;
  background: #fafbfc;
}
.ss-panel-icon {
  width: 36px; height: 36px;
  border-radius: .55rem;
  display: flex; align-items: center; justify-content: center;
  font-size: .95rem; flex-shrink: 0;
}
.ss-panel-icon.blue  { background: rgba(1,100,61,.1);  color: #01643D; }
.ss-panel-icon.teal  { background: rgba(141,185,74,.1); color: #8DB94A; }
.ss-panel-header .ss-panel-title {
  font-size: .92rem; font-weight: 700; color: #1a2530; margin: 0;
}
.ss-panel-header .ss-panel-sub {
  font-size: .72rem; color: #8492a6; margin: .1rem 0 0;
}
.ss-panel-body { padding: 1rem 1.25rem; }

/* Event history items */
.ss-event-item {
  display: flex;
  align-items: center;
  gap: .8rem;
  padding: .7rem .85rem;
  border-radius: .65rem;
  border: 1.5px solid #f0f3f7;
  margin-bottom: .5rem;
  transition: border-color .15s;
}
.ss-event-item:hover { border-color: #8DB94A; }
.ss-event-item:last-child { margin-bottom: 0; }
.ss-event-dot {
  width: 36px; height: 36px;
  border-radius: 50%;
  background: rgba(141,185,74,.12);
  display: flex; align-items: center; justify-content: center;
  color: #01643D; font-size: .85rem; flex-shrink: 0;
}
.ss-event-name { font-size: .85rem; font-weight: 600; color: #1a2530; }
.ss-event-pts {
  margin-left: auto;
  background: rgba(1,100,61,.1);
  color: #01643D;
  border-radius: 1rem;
  padding: .18rem .65rem;
  font-size: .75rem; font-weight: 700;
  white-space: nowrap;
}

/* Empty state */
.ss-empty {
  text-align: center;
  padding: 2.5rem 1rem;
  color: #adb5bd;
}
.ss-empty i { font-size: 2.5rem; display: block; margin-bottom: .5rem; }
</style>

<div class="ss-wrap">

  <!-- ══ HERO ══ -->
  <div class="ss-hero">
    <div class="ss-avatar"><?= $studentInitials ?></div>
    <div class="ss-hero-text">
      <h2><?= $studentName ?></h2>
      <p><i class="fas fa-graduation-cap me-1"></i><?= $degreeInfo ?: 'Alumno de Servicio Social' ?></p>
    </div>
    <span class="ss-hero-badge"><i class="fas fa-shield-alt me-1"></i>Servicio Social</span>
  </div>

  <!-- ══ PROGRESO ══ -->
  <div class="ss-progress-card">
    <div style="text-align:center;min-width:90px;">
      <div class="ss-points-big" id="totalPointsNum">0</div>
      <div class="ss-points-of" id="totalPointsLabel">de — puntos</div>
    </div>
    <div class="ss-progress-bar-wrap">
      <div style="font-size:.82rem;font-weight:700;color:#1a2530;">Progreso de Servicio Social</div>
      <div class="ss-progress-bar-track">
        <div class="ss-progress-bar-fill" id="ssProgressBar" style="width:0%;"></div>
      </div>
      <div class="ss-progress-label">
        <span>0 pts</span>
        <span id="ssProgressPct">0%</span>
        <span id="ssProgressMax">— pts requeridos</span>
      </div>
    </div>
    <div id="ssCompleteBadge" style="display:none;flex-shrink:0;">
      <span style="background:#d1fae5;color:#065f46;border-radius:.75rem;padding:.5rem 1rem;font-size:.85rem;font-weight:700;display:flex;align-items:center;gap:.4rem;">
        <i class="fas fa-check-circle"></i> ¡Completado!
      </span>
    </div>
  </div>

  <!-- ══ GRID PRINCIPAL ══ -->
  <div class="ss-grid">

    <!-- ── Eventos disponibles ── -->
    <div class="ss-panel">
      <div class="ss-panel-header">
        <div class="ss-panel-icon blue"><i class="fas fa-calendar-alt"></i></div>
        <div>
          <div class="ss-panel-title">Eventos Disponibles</div>
          <div class="ss-panel-sub">Postúlate a los eventos de servicio social</div>
        </div>
      </div>
      <!-- Eventos se inyectan aquí -->
      <div class="events row g-3 p-3" id="eventsContainer">
        <div class="ss-empty" style="grid-column:1/-1;">
          <i class="fas fa-spinner fa-spin"></i>
          <p>Cargando eventos...</p>
        </div>
      </div>
    </div>

    <!-- ── Historial de eventos ── -->
    <div class="ss-panel">
      <div class="ss-panel-header">
        <div class="ss-panel-icon teal"><i class="fas fa-list-check"></i></div>
        <div>
          <div class="ss-panel-title">Mis Eventos Asistidos</div>
          <div class="ss-panel-sub">Puntos acumulados por evento</div>
        </div>
      </div>
      <div class="ss-panel-body">
        <ul class="list-unstyled mb-0" id="eventList">
          <li class="ss-empty">
            <i class="fas fa-calendar-times"></i>
            <p>Sin eventos registrados aún</p>
          </li>
        </ul>
      </div>
    </div>

  </div><!-- /ss-grid -->
</div><!-- /ss-wrap -->

<!-- ══════════════════════════════════════════════════════════
     FASE 2 – Tramitación con IJUMICH (visible tras completar 100%)
     Misma estética que el dashboard de alumno externo
     ══════════════════════════════════════════════════════════ -->
<style>
/* ═══════════════════════════════════════════════════════════════════
   FASE 2 – Tramitación IJUMICH (alumno interno que completó 100%)
═══════════════════════════════════════════════════════════════════ */
@keyframes fadeUp {
    from { opacity:0; transform:translateY(18px); }
    to   { opacity:1; transform:translateY(0); }
}
@keyframes cardPulse {
    0%,100% { box-shadow:0 2px 10px rgba(0,0,0,.05); }
    50%     { box-shadow:0 5px 28px rgba(1,100,61,.2); }
}

.int2-page {
    /* margin: -1.5rem; */
    background: #f0f4fb;
    min-height: calc(100vh - 58px);
    font-family: 'Montserrat','Segoe UI',sans-serif;
    display: none;
}

/* ── Hero ── */
.int2-hero {
    background: linear-gradient(135deg, #01643D 0%, #1a7a52 55%, #c6db53 100%);
    padding: 2.5rem 2.5rem 2.5rem 2.5rem;
    display: flex; align-items: flex-start; gap: 1.5rem; flex-wrap: wrap;
    position: relative; overflow: hidden; min-height: 240px;
}
.int2-hero::before {
    content:''; position:absolute; inset:0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
.int2-hero-text { flex:1; min-width:220px; position:relative; z-index:1; padding-top:.25rem; }
.int2-hero-text .hero-eyebrow {
    background:rgba(255,255,255,.18); border:1px solid rgba(255,255,255,.3);
    border-radius:2rem; color:#fff; font-size:.72rem; font-weight:600;
    letter-spacing:.06em; text-transform:uppercase; padding:.3rem 1rem;
    display:inline-flex; align-items:center; gap:.4rem; margin-bottom:1rem;
}
.int2-hero-text h2 {
    color:#fff; font-weight:800; font-size:clamp(1.25rem,2.5vw,1.8rem);
    margin:0 0 .55rem; line-height:1.25;
}
.int2-hero-text p { color:rgba(255,255,255,.88); font-size:.9rem; margin:0; max-width:520px; line-height:1.6; }

/* Stepper */
.int2-progress-wrap {
    background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.22);
    border-radius:1.1rem; backdrop-filter:blur(8px); padding:1.1rem 1.4rem;
    margin-top:1.4rem; position:relative; z-index:1;
}
.int2-stepper { display:flex; align-items:flex-start; justify-content:space-between; width:100%; gap:0; }
.int2-step { display:flex; flex-direction:column; align-items:center; gap:.35rem; flex:1; position:relative; }
.int2-step:not(:last-child)::after {
    content:''; position:absolute; top:16px; left:calc(50% + 18px); right:calc(-50% + 18px);
    height:2.5px; background:rgba(255,255,255,.2); border-radius:2px; z-index:0;
}
.int2-step.done:not(:last-child)::after   { background:rgba(255,255,255,.8); }
.int2-step.active:not(:last-child)::after { background:linear-gradient(90deg,rgba(255,255,255,.8) 40%,rgba(255,255,255,.18)); }
.int2-dot {
    width:34px; height:34px; border-radius:50%;
    background:rgba(255,255,255,.15); color:rgba(255,255,255,.55);
    display:flex; align-items:center; justify-content:center;
    font-size:.78rem; font-weight:700;
    border:2px solid rgba(255,255,255,.25);
    position:relative; z-index:1; transition:all .35s;
}
.int2-step.done   .int2-dot { background:#fff; border-color:#fff; color:#01643D; box-shadow:0 0 0 5px rgba(255,255,255,.18); }
.int2-step.active .int2-dot { background:rgba(255,255,255,.22); border-color:#fff; color:#fff; box-shadow:0 0 0 5px rgba(255,255,255,.14); }
.int2-label { font-size:.58rem; font-weight:600; color:rgba(255,255,255,.5); text-align:center; line-height:1.3; max-width:70px; }
.int2-step.done   .int2-label { color:rgba(255,255,255,.92); }
.int2-step.active .int2-label { color:#fff; font-weight:700; }

/* ── Body ── */
.int2-body { padding:2rem 2rem 3rem; max-width:960px; margin:0 auto; }
.int2-step-cards { display:flex; flex-direction:column; gap:1rem; margin-bottom:2rem; }

/* ── Step Cards ── */
.step-card {
    background:#fff; border-radius:1rem;
    box-shadow:0 2px 10px rgba(0,0,0,.05);
    display:flex; overflow:hidden;
    border:2px solid transparent;
    transition:box-shadow .25s, border-color .25s, transform .2s, opacity .3s;
    opacity:.45; pointer-events:none;
    animation: fadeUp .5s ease both;
}
.step-card:nth-child(1){animation-delay:.05s}
.step-card:nth-child(2){animation-delay:.10s}
.step-card:nth-child(3){animation-delay:.15s}
.step-card:nth-child(4){animation-delay:.20s}
.step-card:nth-child(5){animation-delay:.25s}
.step-card:nth-child(6){animation-delay:.30s}
.step-card.unlocked { opacity:1; pointer-events:auto; }
.step-card.unlocked:hover { box-shadow:0 6px 24px rgba(1,100,61,.13); transform:translateY(-2px); }
.step-card.current  { border-color:#01643D; opacity:1; pointer-events:auto; animation:fadeUp .5s ease both, cardPulse 2.8s 1s ease-in-out infinite; }
.step-card.done-card{ border-color:#bbf7d0; opacity:1; pointer-events:auto; }
.step-card.done-card .step-card-side { background:#d1fae5 !important; color:#065f46; }

.step-card-side {
    width:64px; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    font-size:1.3rem; color:#fff;
}
.sc-blue    .step-card-side { background:#01643D; }
.sc-orange  .step-card-side { background:#d97706; }
.sc-teal    .step-card-side { background:#01643D; }
.sc-indigo  .step-card-side { background:#0b5911; }
.sc-lime    .step-card-side { background:#65a30d; }
.sc-emerald .step-card-side { background:#059669; }
.sc-purple  .step-card-side { background:#7c3aed; }

.step-card-body {
    flex:1; padding:1rem 1.4rem;
    display:flex; align-items:center; justify-content:space-between;
    gap:1rem; flex-wrap:wrap;
}
.step-card-info .step-num-label {
    font-size:.68rem; font-weight:700; letter-spacing:.08em;
    text-transform:uppercase; color:#94a3b8; margin-bottom:.2rem;
}
.step-card-info h5 { font-size:.97rem; font-weight:700; color:#037435; margin:0 0 .25rem; }
.step-card-info p  { font-size:.84rem; color:#64748b; margin:0; line-height:1.55; max-width:530px; }
.step-card-action  { flex-shrink:0; }

.btn-step-primary,.btn-step-teal,.btn-step-link,.btn-step-orange,.btn-step-purple {
    border:none; border-radius:.6rem; padding:.52rem 1.25rem;
    font-size:.84rem; font-weight:700; cursor:pointer;
    display:inline-flex; align-items:center; gap:.45rem;
    transition:background .2s, transform .15s; white-space:nowrap; text-decoration:none;
}
.btn-step-primary { background:#01643D; color:#fff; }
.btn-step-primary:hover { background:#01643D; transform:scale(1.04); color:#fff; }
.btn-step-teal   { background:#01643D; color:#fff; }
.btn-step-teal:hover   { background:#00594F; transform:scale(1.04); color:#fff; }
.btn-step-link   { background:rgba(1,100,61,.07); color:#01643D; border:1.5px solid #01643D; }
.btn-step-link:hover   { background:#01643D; color:#fff; transform:scale(1.04); }
.btn-step-orange { background:#d97706; color:#fff; }
.btn-step-orange:hover { background:#b45309; transform:scale(1.04); color:#fff; }
.btn-step-purple { background:#7c3aed; color:#fff; }
.btn-step-purple:hover { background:#6d28d9; transform:scale(1.04); color:#fff; }

.done-badge {
    display:inline-flex; align-items:center; gap:.4rem;
    background:#f0fdf4; color:#16a34a;
    border:1.5px solid #bbf7d0; border-radius:2rem;
    padding:.35rem .9rem; font-size:.8rem; font-weight:700;
}
.pending-badge {
    display:inline-flex; align-items:center; gap:.4rem;
    background:#fffbeb; color:#b45309;
    border:1.5px solid #fde68a; border-radius:2rem;
    padding:.35rem .9rem; font-size:.8rem; font-weight:700;
}
.rejected-badge {
    display:inline-flex; align-items:center; gap:.4rem;
    background:#fff1f2; color:#be123c;
    border:1.5px solid #fecdd3; border-radius:2rem;
    padding:.35rem .9rem; font-size:.8rem; font-weight:700;
}

/* ── Sub-pasos: Reportes Parciales ── */
.sub-steps-group {
    padding-left: 1.5rem;
    border-left: 3px solid rgba(99,102,241,.2);
    margin-left: .4rem;
    display: flex;
    flex-direction: column;
    gap: .85rem;
    position: relative;
}
.sub-steps-group::before {
    content: '';
    position: absolute;
    left: -3px; top: 0; bottom: 0;
    width: 3px;
    background: linear-gradient(180deg, rgba(99,102,241,.6) 0%, rgba(99,102,241,.1) 100%);
    border-radius: 2px;
}
.sub-step-card { border-radius: .85rem !important; }
.sc-violet .step-card-side { background: #0b5911; }
.sc-violet.done-card .step-card-side { background: #d1fae5 !important; color: #065f46; }
.rp-badge {
    background: rgba(99,102,241,.12);
    color: #0b5911;
    border-radius: 1rem;
    padding: .1rem .5rem;
    font-size: .67rem;
    font-weight: 700;
    margin-left: .3rem;
    vertical-align: middle;
}
.btn-step-violet {
    border: none; border-radius: .6rem; padding: .52rem 1.25rem;
    font-size: .84rem; font-weight: 700; cursor: pointer;
    display: inline-flex; align-items: center; gap: .45rem;
    transition: background .2s, transform .15s; white-space: nowrap;
    background: #0b774f; color: #fff;
}
.btn-step-violet:hover { background: #0b5911; transform: scale(1.04); color: #fff; }

/* ── Historial Timeline ── */
.historial-panel {
    background:#fff; border-radius:1rem;
    box-shadow:0 2px 10px rgba(0,0,0,.05);
    overflow:hidden; margin-bottom:2rem;
    animation:fadeUp .55s .3s ease both;
}
.historial-header {
    background:linear-gradient(135deg,#037435,#334155);
    color:#fff; padding:1rem 1.4rem;
    display:flex; align-items:center; gap:.8rem;
}
.historial-header h6 { margin:0; font-weight:700; font-size:.95rem; }
.historial-body { padding:1.2rem 1.4rem; }

.timeline { list-style:none; padding:0; margin:0; }
.timeline-item { display:flex; gap:1rem; padding-bottom:1.2rem; position:relative; }
.timeline-item:not(:last-child)::before {
    content:''; position:absolute; left:15px; top:32px; bottom:0;
    width:2px; background:#e2e8f0;
}
.timeline-dot {
    width:32px; height:32px; border-radius:50%; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    font-size:.78rem; position:relative; z-index:1;
}
.timeline-dot.pendiente { background:#fffbeb; color:#b45309; border:2px solid #fde68a; }
.timeline-dot.aprobado  { background:#f0fdf4; color:#16a34a; border:2px solid #bbf7d0; }
.timeline-dot.rechazado { background:#fff1f2; color:#be123c; border:2px solid #fecdd3; }
.timeline-content .tc-title  { font-size:.88rem; font-weight:700; color:#037435; margin-bottom:.15rem; }
.timeline-content .tc-sub    { font-size:.78rem; color:#64748b; margin-bottom:.2rem; }
.timeline-content .tc-comment {
    font-size:.78rem; color:#475569;
    background:#f8fafc; border-left:3px solid #e2e8f0;
    padding:.3rem .6rem; border-radius:0 .4rem .4rem 0; margin-top:.3rem;
}
</style>

<div class="int2-page" id="int2Page">
    <!-- ── Hero ── -->
    <div class="int2-hero">
        <div class="int2-hero-text">
            <div class="hero-eyebrow"><i class="fas fa-university me-1"></i>Servicio Social Interno &middot; IJUMICH</div>
            <h2>¡Listo, <?= htmlspecialchars(explode(' ', trim($studentName))[0]) ?>! 🎉<br>Ahora tramita tu servicio con IJUMICH</h2>
            <p>Sigue los pasos para formalizar tu servicio social interno ante el IJUMICH. Esta plataforma genera tu carta de aceptación oficial.</p>
            <div class="hero-resource-btns-int2">
                <button class="btn-hero-resource btn-hero-video" data-bs-toggle="modal" data-bs-target="#modalVideoGuiaInterno">
                    <i class="fas fa-play-circle"></i> Ver video tutorial
                </button>
                <button class="btn-hero-resource btn-hero-pdf" data-bs-toggle="modal" data-bs-target="#modalPdfGuiaInterno">
                    <i class="fas fa-file-pdf"></i> Ver guía PDF
                </button>
            </div>
            <div class="int2-progress-wrap">
                <div class="int2-stepper" id="int2Stepper">
                    <div class="int2-step" data-step="1"><div class="int2-dot"><i class="fas fa-mouse-pointer" style="font-size:.6rem;"></i></div><span class="int2-label">Ingresa a IJUMICH</span></div>
                    <div class="int2-step" data-step="2"><div class="int2-dot"><i class="fas fa-award" style="font-size:.6rem;"></i></div><span class="int2-label">Carta prácticas</span></div>
                    <div class="int2-step" data-step="3"><div class="int2-dot"><i class="fas fa-file-alt" style="font-size:.6rem;"></i></div><span class="int2-label">Carta aceptación</span></div>
                    <div class="int2-step" data-step="4"><div class="int2-dot"><i class="fas fa-clipboard-list" style="font-size:.6rem;"></i></div><span class="int2-label">Sol. registro</span></div>
                    <div class="int2-step" data-step="5"><div class="int2-dot"><i class="fas fa-tasks" style="font-size:.6rem;"></i></div><span class="int2-label">En servicio</span></div>
                    <div class="int2-step" data-step="6"><div class="int2-dot"><i class="fas fa-file-certificate" style="font-size:.6rem;"></i></div><span class="int2-label">Carta conclusión</span></div>
                    <div class="int2-step" data-step="7"><div class="int2-dot"><i class="fas fa-upload" style="font-size:.6rem;"></i></div><span class="int2-label">Carta liberación</span></div>
                    <div class="int2-step" data-step="8"><div class="int2-dot"><i class="fas fa-star" style="font-size:.6rem;"></i></div><span class="int2-label">Eval. Unidad</span></div>
                    <div class="int2-step" data-step="9"><div class="int2-dot"><i class="fas fa-globe" style="font-size:.6rem;"></i></div><span class="int2-label">Eval. Global</span></div>
                    <div class="int2-step" data-step="10"><div class="int2-dot"><i class="fas fa-graduation-cap" style="font-size:.6rem;"></i></div><span class="int2-label">¡Liberado!</span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Cuerpo ── -->
    <div class="int2-body">

        <div class="int2-step-cards">

            <!-- PASO 1 -->
            <div class="step-card sc-blue unlocked current" id="int2-card-step-1">
                <div class="step-card-side"><i class="fas fa-mouse-pointer"></i></div>
                <div class="step-card-body">
                    <div class="step-card-info">
                        <div class="step-num-label">Paso 1</div>
                        <h5>Ingresa al portal del IJUMICH</h5>
                        <p>Regístrate o inicia sesión en la plataforma del IJUMICH y selecciona a <strong>UNIVERSIDAD MONTRER</strong> como organismo receptor interno para tu servicio social.</p>
                    </div>
                    <div class="step-card-action">
                        <a class="btn-step-link" href="https://www.serviciosocial.ijumich.michoacan.gob.mx/" target="_blank" rel="noopener noreferrer">
                            <i class="fas fa-external-link-alt"></i> Abrir IJUMICH
                        </a>
                    </div>
                </div>
            </div>

            <!-- PASO 2 – Carta de finalización de prácticas -->
            <div class="step-card sc-orange" id="int2-card-step-2">
                <div class="step-card-side"><i class="fas fa-award"></i></div>
                <div class="step-card-body">
                    <div class="step-card-info">
                        <div class="step-num-label">Paso 2</div>
                        <h5>Carta de finalización de Prácticas Profesionales</h5>
                        <p>Sube tu <strong>Carta de Finalización de Prácticas Profesionales</strong>. El área administrativa la revisará y aprobará antes de que puedas avanzar.</p>
                    </div>
                    <div class="step-card-action" id="int2-action-step-2">
                        <button class="btn-step-orange" id="btnCargarCartaPracticasInterno">
                            <i class="fas fa-cloud-upload-alt"></i> Subir carta
                        </button>
                    </div>
                </div>
            </div>

            <!-- PASO 3 – Carta de aceptación -->
            <div class="step-card sc-teal" id="int2-card-step-3">
                <div class="step-card-side"><i class="fas fa-file-alt"></i></div>
                <div class="step-card-body">
                    <div class="step-card-info">
                        <div class="step-num-label">Paso 3</div>
                        <h5>Carta de Aceptación de Servicio Social</h5>
                        <p>Una vez aprobada tu carta de prácticas, UNIMO generará tu <strong>Carta de Aceptación de Servicio Social</strong> dirigida al IJUMICH. Descárgala cuando esté lista.</p>
                    </div>
                    <div class="step-card-action" id="int2-action-step-3">
                        <span class="pending-badge" style="opacity:.5;"><i class="fas fa-lock me-1"></i>Pendiente de paso 2</span>
                    </div>
                </div>
            </div>

            <!-- PASO 4 – Carga tu solicitud de registro -->
            <div class="step-card sc-teal" id="int2-card-step-4">
                <div class="step-card-side"><i class="fas fa-clipboard-list"></i></div>
                <div class="step-card-body">
                    <div class="step-card-info">
                        <div class="step-num-label">Paso 4</div>
                        <h5>Carga tu Solicitud de Registro</h5>
                        <p>Descarga y llena la <strong>Solicitud de Registro</strong> que te proporciona el IJUMICH (PDF v1.7). Súbela aquí para que el área administrativa la revise y te la devuelva firmada y sellada en la esquina inferior derecha.</p>
                    </div>
                    <div class="step-card-action" id="int2-action-step-4">
                        <span class="pending-badge" style="opacity:.5;"><i class="fas fa-lock me-1"></i>Pendiente de paso 3</span>
                    </div>
                </div>
            </div>

            <!-- PASO 5 – Realiza tu servicio social -->
            <div class="step-card sc-indigo" id="int2-card-step-5">
                <div class="step-card-side"><i class="fas fa-tasks"></i></div>
                <div class="step-card-body">
                    <div class="step-card-info">
                        <div class="step-num-label">Paso 5</div>
                        <h5>Realiza tu servicio social</h5>
                        <p>Con tu carta en mano, continúa el proceso directamente en la plataforma del IJUMICH. Cumple con las 480 horas en el periodo establecido.</p>
                    </div>
                    <div class="step-card-action">
                        <a class="btn-step-link" href="https://www.serviciosocial.ijumich.michoacan.gob.mx/" target="_blank" rel="noopener noreferrer">
                            <i class="fas fa-external-link-alt"></i> Portal IJUMICH
                        </a>
                    </div>
                </div>
            </div>

            <!-- ══ SUB-PASOS 4a/4b/4c – Reportes Parciales (cada 2 meses) ══ -->
            <div class="sub-steps-group" id="int2-reportes-group">

                <!-- SUB-PASO 4a – Reporte Parcial 1 -->
                <div class="step-card sc-violet sub-step-card" id="int2-card-step-4a">
                    <div class="step-card-side"><i class="fas fa-file-signature"></i></div>
                    <div class="step-card-body">
                        <div class="step-card-info">
                            <div class="step-num-label">Reporte Parcial <span class="rp-badge">1 de 3</span></div>
                            <h5>Primer Reporte de Actividades <small style="font-size:.72rem;color:#64748b;font-weight:500;">(al 2° mes)</small></h5>
                            <p>Sube tu primer reporte de actividades. UNIMO lo revisará, lo <strong>firmará con sello oficial</strong> y te lo regresará para que lo descargues y lo presentes ante el IJUMICH.</p>
                        </div>
                        <div class="step-card-action" id="int2-action-step-4a">
                            <span class="pending-badge" style="opacity:.5;"><i class="fas fa-lock me-1"></i>Pendiente paso anterior</span>
                        </div>
                    </div>
                </div>

                <!-- SUB-PASO 4b – Reporte Parcial 2 -->
                <div class="step-card sc-violet sub-step-card" id="int2-card-step-4b">
                    <div class="step-card-side"><i class="fas fa-file-signature"></i></div>
                    <div class="step-card-body">
                        <div class="step-card-info">
                            <div class="step-num-label">Reporte Parcial <span class="rp-badge">2 de 3</span></div>
                            <h5>Segundo Reporte de Actividades <small style="font-size:.72rem;color:#64748b;font-weight:500;">(al 4° mes)</small></h5>
                            <p>Sube tu segundo reporte de actividades parcial. Recuerda que UNIMO lo firmará y sellará antes de que puedas continuar con el siguiente reporte.</p>
                        </div>
                        <div class="step-card-action" id="int2-action-step-4b">
                            <span class="pending-badge" style="opacity:.5;"><i class="fas fa-lock me-1"></i>Pendiente de 1er reporte aprobado</span>
                        </div>
                    </div>
                </div>

                <!-- SUB-PASO 4c – Reporte Parcial 3 -->
                <div class="step-card sc-violet sub-step-card" id="int2-card-step-4c">
                    <div class="step-card-side"><i class="fas fa-file-signature"></i></div>
                    <div class="step-card-body">
                        <div class="step-card-info">
                            <div class="step-num-label">Reporte Parcial <span class="rp-badge">3 de 3</span></div>
                            <h5>Tercer Reporte de Actividades <small style="font-size:.72rem;color:#64748b;font-weight:500;">(al 6° mes)</small></h5>
                            <p>Sube tu tercer y último reporte de actividades. Una vez aprobado por UNIMO, podrás cargar tu <strong>carta de liberación</strong> emitida por el IJUMICH.</p>
                        </div>
                        <div class="step-card-action" id="int2-action-step-4c">
                            <span class="pending-badge" style="opacity:.5;"><i class="fas fa-lock me-1"></i>Pendiente de 2° reporte aprobado</span>
                        </div>
                    </div>
                </div>

            </div><!-- /sub-steps-group -->

            <!-- PASO 6 – Carta de Conclusión de Servicio Social (generada por el alumno) -->
            <div class="step-card sc-lime" id="int2-card-step-6">
                <div class="step-card-side"><i class="fas fa-file-certificate"></i></div>
                <div class="step-card-body">
                    <div class="step-card-info">
                        <div class="step-num-label">Paso 6</div>
                        <h5>Solicitar Carta de Finalización de Servicio Social</h5>
                        <p>Una vez aprobados los 3 reportes parciales, genera aquí tu <strong>Carta de Conclusión de Servicio Social</strong> expedida por UNIMO. Ingresa las fechas de inicio y término de tu servicio y descarga el PDF. <strong>Solo tú puedes generarla</strong>; no requiere intervención del área administrativa.</p>
                    </div>
                    <div class="step-card-action" id="int2-action-step-6">
                        <span class="pending-badge" style="opacity:.5;"><i class="fas fa-lock me-1"></i>Pendiente de los 3 reportes</span>
                    </div>
                </div>
            </div>

            <!-- PASO 7 – Carga tu carta de liberación (IJUMICH) -->
            <div class="step-card sc-teal" id="int2-card-step-7">
                <div class="step-card-side"><i class="fas fa-cloud-upload-alt"></i></div>
                <div class="step-card-body">
                    <div class="step-card-info">
                        <div class="step-num-label">Paso 7</div>
                        <h5>Carga tu carta de liberación</h5>
                        <p>Al concluir tu servicio social, sube aquí la <strong>carta de liberación expedida por el IJUMICH</strong> para que UNIMO valide y acredite tu proceso.</p>
                    </div>
                    <div class="step-card-action" id="int2-action-step-7">
                        <button class="btn-step-teal" id="btnCargarCartaLiberacionInterno">
                            <i class="fas fa-cloud-upload-alt"></i> Cargar carta
                        </button>
                    </div>
                </div>
            </div>

            <!-- PASO 8 – Evaluación de la Unidad Productiva -->
            <div class="step-card sc-teal" id="int2-card-step-8">
                <div class="step-card-side"><i class="fas fa-star"></i></div>
                <div class="step-card-body">
                    <div class="step-card-info">
                        <div class="step-num-label">Paso 8</div>
                        <h5>Evaluación de la Unidad Productiva</h5>
                        <p>Sube el PDF de <strong>Evaluación de la Unidad Productiva</strong> proporcionado por el IJUMICH. El área administrativa lo revisará, completará los campos de evaluación, lo firmará con sello oficial y te lo regresará para que lo presentes.</p>
                    </div>
                    <div class="step-card-action" id="int2-action-step-8">
                        <span class="pending-badge" style="opacity:.5;"><i class="fas fa-lock me-1"></i>Pendiente de carta de liberación aprobada</span>
                    </div>
                </div>
            </div>

            <!-- PASO 9 – Evaluación Global -->
            <div class="step-card sc-emerald" id="int2-card-step-9">
                <div class="step-card-side"><i class="fas fa-globe"></i></div>
                <div class="step-card-body">
                    <div class="step-card-info">
                        <div class="step-num-label">Paso 9</div>
                        <h5>Evaluación Global</h5>
                        <p>Una vez aprobada la Evaluación de la Unidad Productiva, sube el PDF de <strong>Evaluación Global</strong> proporcionado por el IJUMICH. El área administrativa lo firmará y sellará como último requisito para tu liberación definitiva.</p>
                    </div>
                    <div class="step-card-action" id="int2-action-step-9">
                        <span class="pending-badge" style="opacity:.5;"><i class="fas fa-lock me-1"></i>Pendiente de Evaluación de Unidad aprobada</span>
                    </div>
                </div>
            </div>

            <!-- PASO 10 – ¡Servicio social liberado! -->
            <div class="step-card sc-emerald" id="int2-card-step-10">
                <div class="step-card-side"><i class="fas fa-graduation-cap"></i></div>
                <div class="step-card-body">
                    <div class="step-card-info">
                        <div class="step-num-label">Paso 10</div>
                        <h5>¡Servicio social liberado!</h5>
                        <p>Una vez que el área administrativa valide tu Evaluación Global, tu servicio social quedará formalmente acreditado. ¡Habrás completado el proceso!</p>
                    </div>
                    <div class="step-card-action" id="int2-action-step-10">
                        <span class="pending-badge" style="opacity:.5;"><i class="fas fa-lock me-1"></i>Pendiente de validación</span>
                    </div>
                </div>
            </div>

        </div><!-- /int2-step-cards -->

        <!-- Historial -->
        <div class="historial-panel">
            <div class="historial-header">
                <i class="fas fa-history"></i>
                <h6>Mis documentos enviados</h6>
                <span id="int2HistorialBadge" class="ms-auto" style="font-size:.74rem;opacity:.7;">Cargando…</span>
            </div>
            <div class="historial-body">
                <ul class="timeline" id="int2TimelineContainer">
                    <li style="list-style:none;text-align:center;color:#94a3b8;padding:1.5rem 0;">
                        <i class="fas fa-spinner fa-spin me-2"></i>Cargando historial...
                    </li>
                </ul>
            </div>
        </div>

    </div><!-- /int2-body -->
</div><!-- /int2-page -->

<!-- Modal: Carta de Conclusión de Servicio Social -->
<div class="modal fade" id="modalCartaConclusionServicio" tabindex="-1" aria-labelledby="modalCartaConclusionServicioLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:1rem;">
            <div class="modal-header" style="background:linear-gradient(135deg,#01643D,#65a30d);color:#fff;border:none;">
                <h5 class="modal-title fw-bold" id="modalCartaConclusionServicioLabel">
                    <i class="fas fa-file-certificate me-2"></i>Carta de Conclusión de Servicio Social
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-success d-flex gap-2 align-items-start mb-3" style="font-size:.84rem;border-radius:.65rem;">
                    <i class="fas fa-info-circle mt-1 flex-shrink-0"></i>
                    <span>UNIMO generará tu <strong>Carta de Conclusión de Servicio Social</strong> de forma automática.<br>
                    Solo debes indicar las fechas exactas de inicio y fin de tu servicio establecidas en tu carta de aceptación. El PDF quedará firmado y sellado electrónicamente.</span>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="concl_fecha_inicio" class="form-label fw-semibold" style="font-size:.88rem;">Fecha de inicio <span class="text-danger">*</span></label>
                        <input type="date" class="form-control form-control-sm" id="concl_fecha_inicio" required>
                    </div>
                    <div class="col-md-6">
                        <label for="concl_fecha_fin" class="form-label fw-semibold" style="font-size:.88rem;">Fecha de término <span class="text-danger">*</span></label>
                        <input type="date" class="form-control form-control-sm" id="concl_fecha_fin" required>
                    </div>
                </div>
                <div class="alert alert-warning mt-3 mb-0" style="font-size:.8rem;border-radius:.6rem;">
                    <i class="fas fa-shield-alt me-1"></i>
                    <strong>Seguridad:</strong> Esta carta está vinculada exclusivamente a tu cuenta. Solo tú puedes generarla y no puede ser descargada por otro usuario.
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm text-white fw-semibold" style="background:#01643D;border-radius:.5rem;" id="btnGenerarCartaConclusion">
                    <i class="fas fa-file-pdf me-1"></i>Generar y Descargar PDF
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Solicitud de Registro (IJUMICH) -->
<div class="modal fade" id="modalSolicitudRegistro" tabindex="-1" aria-labelledby="modalSolicitudRegistroLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:1rem;">
            <div class="modal-header" style="background:linear-gradient(135deg,#01643D,#00594F);color:#fff;border:none;">
                <h5 class="modal-title fw-bold" id="modalSolicitudRegistroLabel">
                    <i class="fas fa-clipboard-list me-2"></i>Solicitud de Registro IJUMICH
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formSolicitudRegistro" novalidate enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <div class="alert alert-info d-flex gap-2 align-items-start" style="font-size:.84rem;border-radius:.65rem;">
                        <i class="fas fa-info-circle mt-1 flex-shrink-0"></i>
                        <span>Sube el <strong>PDF de Solicitud de Registro</strong> que te entrega el IJUMICH (versión 1.7). El área administrativa lo aprobará y te lo regresará firmado y sellado en la esquina inferior derecha.</span>
                    </div>
                    <div class="mb-3">
                        <label for="solRegArchivo" class="form-label fw-semibold" style="font-size:.88rem;">Archivo PDF <span class="text-danger">*</span></label>
                        <input type="file" class="form-control form-control-sm" id="solRegArchivo" name="solicitud_registro" accept="application/pdf" required>
                        <div class="form-text">Solo PDF. Máximo 5 MB.</div>
                    </div>
                    <div class="mb-0">
                        <label for="solRegObs" class="form-label fw-semibold" style="font-size:.88rem;">Observaciones <small class="text-muted fw-normal">(opcional)</small></label>
                        <textarea class="form-control form-control-sm" id="solRegObs" name="observaciones" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm text-white fw-semibold" style="background:#01643D;border-radius:.5rem;" id="btnSubmitSolicitudRegistro">
                        <i class="fas fa-cloud-upload-alt me-1"></i>Enviar solicitud
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Carta de finalización de prácticas (interno) -->
<div class="modal fade" id="modalCartaPracticasInterno" tabindex="-1" aria-labelledby="modalCartaPracticasInternoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:1rem;">
            <div class="modal-header" style="background:linear-gradient(135deg,#d97706,#b45309);color:#fff;border:none;">
                <h5 class="modal-title fw-bold" id="modalCartaPracticasInternoLabel">
                    <i class="fas fa-award me-2"></i>Carta de Finalización de Prácticas
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formCartaPracticasInterno" novalidate enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <p class="text-muted mb-3" style="font-size:.88rem;">
                        Sube tu <strong>Carta de Finalización de Prácticas Profesionales</strong>. El área administrativa la revisará antes de generar tu carta de aceptación de servicio social.
                    </p>
                    <div class="mb-3">
                        <label for="praInternoArchivo" class="form-label fw-semibold" style="font-size:.88rem;">Documento (PDF o imagen) <span class="text-danger">*</span></label>
                        <input type="file" class="form-control form-control-sm" id="praInternoArchivo" name="carta_practicas_interno" accept="application/pdf,image/*" required>
                        <div class="form-text">Formatos: PDF, JPG, PNG. Máximo 5 MB.</div>
                    </div>
                    <div class="mb-0">
                        <label for="praInternoObs" class="form-label fw-semibold" style="font-size:.88rem;">Observaciones <small class="text-muted fw-normal">(opcional)</small></label>
                        <textarea class="form-control form-control-sm" id="praInternoObs" name="observaciones" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm text-white fw-semibold" style="background:#d97706;border-radius:.5rem;" id="btnSubmitCartaPracticasInterno">
                        <i class="fas fa-cloud-upload-alt me-1"></i>Enviar documento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Carta de liberación (interno) -->
<div class="modal fade" id="modalCartaLiberacionInterno" tabindex="-1" aria-labelledby="modalCartaLiberacionInternoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:1rem;">
            <div class="modal-header" style="background:linear-gradient(135deg,#01643D,#00594F);color:#fff;border:none;">
                <h5 class="modal-title fw-bold" id="modalCartaLiberacionInternoLabel">
                    <i class="fas fa-upload me-2"></i>Cargar Carta de Liberación
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formCartaLiberacionInterno" novalidate enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <p class="text-muted mb-3" style="font-size:.88rem;">
                        Sube la carta de liberación expedida por el IJUMICH al concluir tu servicio social.
                    </p>
                    <div class="mb-3">
                        <label for="libInternoArchivo" class="form-label fw-semibold" style="font-size:.88rem;">Archivo (PDF o imagen) <span class="text-danger">*</span></label>
                        <input type="file" class="form-control form-control-sm" id="libInternoArchivo" name="carta_liberacion_interno" accept="application/pdf,image/*" required>
                        <div class="form-text">Formatos: PDF, JPG, PNG. Máximo 5 MB.</div>
                    </div>
                    <div class="mb-0">
                        <label for="libInternoObs" class="form-label fw-semibold" style="font-size:.88rem;">Observaciones</label>
                        <textarea class="form-control form-control-sm" id="libInternoObs" name="observaciones" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm text-white fw-semibold" style="background:#01643D;border-radius:.5rem;" id="btnSubmitCartaLiberacionInterno">
                        <i class="fas fa-cloud-upload-alt me-1"></i>Cargar carta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Evaluación de la Unidad Productiva (Paso 8) -->
<div class="modal fade" id="modalEvaluacionUnidad" tabindex="-1" aria-labelledby="modalEvaluacionUnidadLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:1rem;">
            <div class="modal-header" style="background:linear-gradient(135deg, #01643D, #a4ad22);color:#fff;border:none;">
                <h5 class="modal-title fw-bold" id="modalEvaluacionUnidadLabel">
                    <i class="fas fa-star me-2"></i>Evaluación de la Unidad Productiva
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formEvaluacionUnidad" novalidate enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <p class="text-muted mb-3" style="font-size:.88rem;">
                        Sube el PDF de <strong>Evaluación de la Unidad Productiva</strong> que te proporcionó el IJUMICH. El área administrativa lo revisará, completará los campos de evaluación, lo firmará con sello oficial y te lo regresará para que puedas descargarlo y presentarlo.
                    </p>
                    <div class="mb-3">
                        <label for="evalUnidadArchivo" class="form-label fw-semibold" style="font-size:.88rem;">Archivo PDF <span class="text-danger">*</span></label>
                        <input type="file" class="form-control form-control-sm" id="evalUnidadArchivo" name="evaluacion_unidad_productiva" accept="application/pdf" required>
                        <div class="form-text">Solo PDF (versión 1.7). Máximo 10 MB.</div>
                    </div>
                    <div class="mb-0">
                        <label for="evalUnidadObs" class="form-label fw-semibold" style="font-size:.88rem;">Observaciones</label>
                        <textarea class="form-control form-control-sm" id="evalUnidadObs" name="observaciones" rows="2" placeholder="Opcional"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm text-white fw-semibold" style="background:#01643D;border-radius:.5rem;" id="btnSubmitEvaluacionUnidad">
                        <i class="fas fa-cloud-upload-alt me-1"></i>Enviar evaluación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Evaluación Global -->
<div class="modal fade" id="modalEvaluacionGlobal" tabindex="-1" aria-labelledby="modalEvaluacionGlobalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:1rem;">
            <div class="modal-header" style="background:linear-gradient(135deg, #0b5911, #38ca72);color:#fff;border:none;">
                <h5 class="modal-title fw-bold" id="modalEvaluacionGlobalLabel">
                    <i class="fas fa-globe me-2"></i>Evaluación Global
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formEvaluacionGlobal" novalidate enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <p class="text-muted mb-3" style="font-size:.88rem;">
                        Sube el PDF de <strong>Evaluación Global</strong> proporcionado por el IJUMICH. El área administrativa lo firmará con sello oficial y te lo regresará como último comprobante de tu liberación.
                    </p>
                    <div class="mb-3">
                        <label for="evalGlobalArchivo" class="form-label fw-semibold" style="font-size:.88rem;">Documento PDF <span class="text-danger">*</span></label>
                        <input type="file" class="form-control form-control-sm" id="evalGlobalArchivo" name="evaluacion_global" accept="application/pdf" required>
                        <div class="form-text">Solo PDF. Máximo 10 MB.</div>
                    </div>
                    <div class="mb-0">
                        <label for="evalGlobalObs" class="form-label fw-semibold" style="font-size:.88rem;">Observaciones <small class="text-muted fw-normal">(opcional)</small></label>
                        <textarea class="form-control form-control-sm" id="evalGlobalObs" name="observaciones" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm text-white fw-semibold" style="background:#0b5911;border-radius:.5rem;" id="btnSubmitEvaluacionGlobal">
                        <i class="fas fa-cloud-upload-alt me-1"></i>Enviar evaluación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Reporte Parcial 1 -->
<div class="modal fade" id="modalReporte1" tabindex="-1" aria-labelledby="modalReporte1Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:1rem;">
            <div class="modal-header" style="background:linear-gradient(135deg, #0b5911, #38ca72);color:#fff;border:none;">
                <h5 class="modal-title fw-bold" id="modalReporte1Label">
                    <i class="fas fa-file-signature me-2"></i>Reporte Parcial 1 de 3
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formReporte1" novalidate enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <p class="text-muted mb-3" style="font-size:.88rem;">
                        Sube tu <strong>primer reporte de actividades</strong> (al 2° mes de servicio). UNIMO lo revisará, lo firmará con sello oficial y te lo regresará para descargarlo.
                    </p>
                    <div class="mb-3">
                        <label for="repArchivo1" class="form-label fw-semibold" style="font-size:.88rem;">Documento (PDF o imagen) <span class="text-danger">*</span></label>
                        <input type="file" class="form-control form-control-sm" id="repArchivo1" name="reporte_parcial" accept="application/pdf,image/*" required>
                        <div class="form-text">Formatos: PDF, JPG, PNG. Máximo 5 MB.</div>
                    </div>
                    <div class="mb-0">
                        <label for="repObs1" class="form-label fw-semibold" style="font-size:.88rem;">Observaciones <small class="text-muted fw-normal">(opcional)</small></label>
                        <textarea class="form-control form-control-sm" id="repObs1" name="observaciones" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm text-white fw-semibold" style="background:#0b5911;border-radius:.5rem;" id="btnSubmitReporte1">
                        <i class="fas fa-cloud-upload-alt me-1"></i>Enviar reporte
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Reporte Parcial 2 -->
<div class="modal fade" id="modalReporte2" tabindex="-1" aria-labelledby="modalReporte2Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:1rem;">
            <div class="modal-header" style="background:linear-gradient(135deg, #0b5911, #38ca72);color:#fff;border:none;">
                <h5 class="modal-title fw-bold" id="modalReporte2Label">
                    <i class="fas fa-file-signature me-2"></i>Reporte Parcial 2 de 3
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formReporte2" novalidate enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <p class="text-muted mb-3" style="font-size:.88rem;">
                        Sube tu <strong>segundo reporte de actividades</strong> (al 4° mes de servicio). UNIMO lo revisará, firmará y sellará antes de regresártelo.
                    </p>
                    <div class="mb-3">
                        <label for="repArchivo2" class="form-label fw-semibold" style="font-size:.88rem;">Documento (PDF o imagen) <span class="text-danger">*</span></label>
                        <input type="file" class="form-control form-control-sm" id="repArchivo2" name="reporte_parcial" accept="application/pdf,image/*" required>
                        <div class="form-text">Formatos: PDF, JPG, PNG. Máximo 5 MB.</div>
                    </div>
                    <div class="mb-0">
                        <label for="repObs2" class="form-label fw-semibold" style="font-size:.88rem;">Observaciones <small class="text-muted fw-normal">(opcional)</small></label>
                        <textarea class="form-control form-control-sm" id="repObs2" name="observaciones" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm text-white fw-semibold" style="background:#0b5911;border-radius:.5rem;" id="btnSubmitReporte2">
                        <i class="fas fa-cloud-upload-alt me-1"></i>Enviar reporte
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Reporte Parcial 3 -->
<div class="modal fade" id="modalReporte3" tabindex="-1" aria-labelledby="modalReporte3Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:1rem;">
            <div class="modal-header" style="background:linear-gradient(135deg, #0b5911, #38ca72);color:#fff;border:none;">
                <h5 class="modal-title fw-bold" id="modalReporte3Label">
                    <i class="fas fa-file-signature me-2"></i>Reporte Parcial 3 de 3
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formReporte3" novalidate enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <p class="text-muted mb-3" style="font-size:.88rem;">
                        Sube tu <strong>tercer y último reporte de actividades</strong> (al 6° mes). Una vez aprobado por UNIMO, podrás cargar tu carta de liberación del IJUMICH.
                    </p>
                    <div class="mb-3">
                        <label for="repArchivo3" class="form-label fw-semibold" style="font-size:.88rem;">Documento (PDF o imagen) <span class="text-danger">*</span></label>
                        <input type="file" class="form-control form-control-sm" id="repArchivo3" name="reporte_parcial" accept="application/pdf,image/*" required>
                        <div class="form-text">Formatos: PDF, JPG, PNG. Máximo 5 MB.</div>
                    </div>
                    <div class="mb-0">
                        <label for="repObs3" class="form-label fw-semibold" style="font-size:.88rem;">Observaciones <small class="text-muted fw-normal">(opcional)</small></label>
                        <textarea class="form-control form-control-sm" id="repObs3" name="observaciones" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm text-white fw-semibold" style="background:#0b5911;border-radius:.5rem;" id="btnSubmitReporte3">
                        <i class="fas fa-cloud-upload-alt me-1"></i>Enviar reporte
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══ MODAL VIDEO INTERNO ══ -->
<div class="modal fade" id="modalVideoGuiaInterno" tabindex="-1" aria-labelledby="modalVideoGuiaInternoLabel" aria-hidden="true" data-bs-backdrop="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content modal-video-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold" id="modalVideoGuiaInternoLabel"><i class="fas fa-play-circle me-2"></i>Video tutorial — Servicio Social Interno</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <video id="heroVideoPlayerInterno" playsinline controls>
                    <source src="view/assets/documents/files/Alumnos.mp4" type="video/mp4">
                    Tu navegador no soporta reproducción de video HTML5.
                </video>
            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL PDF INTERNO ══ -->
<div class="modal fade" id="modalPdfGuiaInterno" tabindex="-1" aria-labelledby="modalPdfGuiaInternoLabel" aria-hidden="true" data-bs-backdrop="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content modal-pdf-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold" id="modalPdfGuiaInternoLabel"><i class="fas fa-file-pdf me-2 text-danger"></i>Guía de Alumnos — Servicio Social Interno</h6>
                <a href="view/assets/documents/files/Alumnos.pdf" download class="btn btn-sm btn-outline-light ms-auto me-2" style="font-size:.78rem;">
                    <i class="fas fa-download me-1"></i>Descargar
                </a>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <iframe id="pdfViewerIframeInterno" src="about:blank" title="Guía PDF Alumnos Interno"></iframe>
            </div>
        </div>
    </div>
</div>

<!-- Plyr JS -->
<script src="view/assets/libs/plyr/plyr.min.js"></script>
<script>
(function () {
    /* ══ MODAL VIDEO — Plyr ══ */
    let plyrPlayerInterno = null;
    const videoModalInterno = document.getElementById('modalVideoGuiaInterno');
    if (videoModalInterno) {
        videoModalInterno.addEventListener('shown.bs.modal', function () {
            if (!plyrPlayerInterno) {
                plyrPlayerInterno = new Plyr('#heroVideoPlayerInterno', {
                    controls: ['play-large','play','progress','current-time','mute','volume','fullscreen'],
                    tooltips: { controls: true, seek: true },
                    i18n: { play: 'Reproducir', pause: 'Pausar', mute: 'Silenciar', unmute: 'Activar sonido', fullscreen: 'Pantalla completa' }
                });
            }
            plyrPlayerInterno.play();
        });
        videoModalInterno.addEventListener('hide.bs.modal', function () {
            if (plyrPlayerInterno) plyrPlayerInterno.pause();
        });
    }

    /* ══ MODAL PDF — iframe ══ */
    const pdfModalInterno = document.getElementById('modalPdfGuiaInterno');
    if (pdfModalInterno) {
        pdfModalInterno.addEventListener('show.bs.modal', function () {
            const iframe = document.getElementById('pdfViewerIframeInterno');
            if (!iframe.src || iframe.src === 'about:blank') {
                iframe.src = 'view/assets/documents/files/Alumnos.pdf';
            }
        });
    }
})();
</script>

<script src="view/assets/js/ajax/inicio.js"></script>
