<?php
$nombreAlumno = htmlspecialchars(trim(
    ($_SESSION['user']['firstname'] ?? '') . ' ' . ($_SESSION['user']['lastname'] ?? '')
));
?>
<!-- Plyr (video player) -->
<link rel="stylesheet" href="view/assets/libs/plyr/plyr.css">
<!-- PDF.js viewer styles -->
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
#pdfViewerIframe {
    width: 100%;
    height: 76vh;
    border: none;
    display: block;
}
.plyr {
    border-radius: 0;
    --plyr-color-main: #01643D;
}
.hero-resource-btns {
    display: flex;
    gap: .65rem;
    flex-wrap: wrap;
    margin-top: 1.1rem;
    animation: fadeDown .65s .3s ease both;
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

/* Fix scroll en modales */
.modal-dialog-scrollable .modal-content {
    overflow: visible !important;
}
.modal-dialog-scrollable .modal-body {
    overflow-y: auto !important;
    max-height: 72vh;
}
</style>
<style>
/* ═══════════════════════════════════════════════
   RESET para el panel externo
═══════════════════════════════════════════════ */
.ext-page {
    margin: -1.5rem;
    background: #f0f4fb;
    min-height: calc(100vh - 58px);
    font-family: 'Montserrat', 'Segoe UI', sans-serif;
}

/* ═══════════════════════════════════════════════
   HERO
═══════════════════════════════════════════════ */
.ext-hero {
    background: linear-gradient(135deg, #01643D 0%, #1a7a52 55%, #c6db53 100%);
    padding: 2.5rem 2.5rem 2.5rem 0;
    display: flex;
    align-items: stretch;
    justify-content: flex-start;
    gap: 0;
    flex-wrap: wrap;
    position: relative;
    overflow: hidden;
    min-height: 300px;
}
.ext-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
.ext-hero-img {
    flex-shrink: 0;
    width: clamp(280px, 32vw, 420px);
    position: relative;
    z-index: 1;
    align-self: stretch;
    display: flex;
    align-items: flex-end;
    margin: -2.5rem 0 -2rem -2rem;
    padding: 0;
    overflow: hidden;
}
.ext-hero-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: top center;
    display: block;
}
/* Seamless fade on all edges — no visible border */
.ext-hero-img::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        linear-gradient(to bottom, rgba(1,100,61,.7) 0%, transparent 22%),
        linear-gradient(to top,    rgba(1,100,61,.7) 0%, transparent 20%);
    pointer-events: none;
    z-index: 1;
}
.ext-hero-img::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(
        to right,
        transparent 0%,
        transparent 42%,
        rgba(26,122,82,.5) 62%,
        rgba(1,100,61,.88) 78%,
        #01643D 100%
    );
    pointer-events: none;
    z-index: 2;
}
.ext-hero-text { flex: 1; min-width: 220px; position: relative; z-index: 1; }
.ext-hero-text .hero-eyebrow {
    background: rgba(255,255,255,.18);
    border: 1px solid rgba(255,255,255,.3);
    border-radius: 2rem;
    color: #fff;
    font-size: .72rem;
    font-weight: 600;
    letter-spacing: .06em;
    text-transform: uppercase;
    padding: .25rem .85rem;
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    margin-bottom: .9rem;
    animation: fadeDown .5s ease both;
}
.ext-hero-text h2 {
    color: #fff;
    font-weight: 800;
    font-size: clamp(1.35rem, 3vw, 2rem);
    margin: 0 0 .6rem;
    line-height: 1.25;
    animation: fadeDown .55s .1s ease both;
}
.ext-hero-text p {
    color: rgba(255,255,255,.88);
    font-size: .92rem;
    margin: 0;
    max-width: 500px;
    line-height: 1.6;
    animation: fadeDown .6s .2s ease both;
}
@keyframes heroFloat {
    0%, 100% { transform: translateY(0); }
    50%       { transform: translateY(-10px); }
}
@keyframes fadeDown {
    from { opacity: 0; transform: translateY(-18px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(18px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ═══════════════════════════════════════════════
   HERO CONTENT AREA
═══════════════════════════════════════════════ */
.ext-hero-content {
    flex: 1;
    min-width: 280px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 1.8rem;
    padding: 0 0 0 2rem;
    position: relative;
    z-index: 1;
}

/* ═══════════════════════════════════════════════
   PROGRESS STEPPER (dentro del hero)
═══════════════════════════════════════════════ */
.ext-progress-wrap {
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.2);
    border-radius: 1rem;
    backdrop-filter: blur(6px);
    padding: 1rem 1.4rem;
    animation: fadeDown .6s .35s ease both;
}
.progress-stepper {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0;
    width: 100%;
}
.ps-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .3rem;
    flex: 1;
    position: relative;
}
.ps-step:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 15px;
    left: calc(50% + 16px);
    right: calc(-50% + 16px);
    height: 3px;
    background: rgba(255,255,255,.25);
    border-radius: 2px;
    transition: background .6s ease;
    z-index: 0;
}
.ps-step.done:not(:last-child)::after  { background: rgba(255,255,255,.85); }
.ps-step.active:not(:last-child)::after { background: linear-gradient(90deg, rgba(255,255,255,.85) 40%, rgba(255,255,255,.2)); }
.ps-dot {
    width: 32px; height: 32px;
    border-radius: 50%;
    background: rgba(255,255,255,.18);
    color: rgba(255,255,255,.6);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .78rem;
    font-weight: 700;
    border: 2.5px solid rgba(255,255,255,.3);
    transition: all .4s ease;
    position: relative;
    z-index: 1;
}
.ps-step.done .ps-dot {
    background: #fff;
    border-color: #fff;
    color: #01643D;
    box-shadow: 0 0 0 4px rgba(255,255,255,.2);
    animation: popIn .4s ease;
}
.ps-step.active .ps-dot {
    background: rgba(255,255,255,.15);
    border-color: #fff;
    color: #fff;
    box-shadow: 0 0 0 5px rgba(255,255,255,.15);
}
.ps-label {
    font-size: .62rem;
    font-weight: 600;
    color: rgba(255,255,255,.55);
    text-align: center;
    line-height: 1.2;
    max-width: 70px;
    transition: color .4s;
}
.ps-step.done .ps-label  { color: rgba(255,255,255,.9); }
.ps-step.active .ps-label { color: #fff; font-weight: 700; }
@keyframes popIn {
    0%  { transform: scale(0.5); }
    70% { transform: scale(1.25); }
    100%{ transform: scale(1); }
}

/* ═══════════════════════════════════════════════
   CUERPO
═══════════════════════════════════════════════ */
.ext-body { padding: 2rem; max-width: 1100px; margin: 0 auto; }

/* ═══════════════════════════════════════════════
   STEP CARDS
═══════════════════════════════════════════════ */
.step-cards { display: flex; flex-direction: column; gap: 1rem; margin-bottom: 2rem; }
.step-card {
    background: #fff;
    border-radius: 1rem;
    box-shadow: 0 2px 10px rgba(0,0,0,.05);
    display: flex;
    overflow: hidden;
    border: 2px solid transparent;
    transition: box-shadow .25s, border-color .25s, transform .2s, opacity .3s;
    opacity: .5;
    pointer-events: none;
    animation: fadeUp .5s ease both;
}
.step-card:nth-child(1) { animation-delay: .05s; }
.step-card:nth-child(2) { animation-delay: .1s; }
.step-card:nth-child(3) { animation-delay: .15s; }
.step-card:nth-child(4) { animation-delay: .2s; }
.step-card:nth-child(5) { animation-delay: .25s; }
.step-card:nth-child(6) { animation-delay: .3s; }
.step-card.unlocked {
    opacity: 1;
    pointer-events: auto;
}
.step-card.unlocked:hover {
    box-shadow: 0 6px 24px rgba(1,100,61,.13);
    transform: translateY(-2px);
}
.step-card.current {
    border-color: #01643D;
    opacity: 1;
    pointer-events: auto;
    animation: fadeUp .5s ease both, cardPulse 2.8s 1s ease-in-out infinite;
}
.step-card.done-card {
    border-color: #bbf7d0;
    opacity: 1;
    pointer-events: auto;
}
@keyframes cardPulse {
    0%, 100% { box-shadow: 0 2px 10px rgba(0,0,0,.05); }
    50%       { box-shadow: 0 5px 28px rgba(1,100,61,.2); }
}

.step-card-side {
    width: 62px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    color: #fff;
}
.sc-blue   .step-card-side { background: #01643D; }
.sc-teal   .step-card-side { background: #01643D; }
.sc-indigo .step-card-side { background: #0b5911; }
.sc-lime   .step-card-side { background: #65a30d; }
.sc-emerald .step-card-side { background: #059669; }
.sc-orange  .step-card-side { background: #d97706; }
.step-card.done-card .step-card-side { background: #d1fae5 !important; color: #065f46; }

/* Botón naranja para carta de prácticas */
/* .btn-step-orange {
    background: #d97706;
    color: #fff;
}
.btn-step-orange:hover { background: #b45309; transform: scale(1.04); color: #fff; } */

/* Alerta requisito prácticas */
.practicas-alert {
    display: flex;
    gap: .85rem;
    align-items: flex-start;
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    border: 1.5px solid #fbbf24;
    border-radius: .75rem;
    padding: .85rem 1.1rem;
    margin: .6rem 0 .5rem;
    box-shadow: 0 2px 10px rgba(217,119,6,.1);
}
.practicas-alert-icon {
    flex-shrink: 0;
    width: 36px; height: 36px;
    border-radius: 50%;
    background: #d97706;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    margin-top: .1rem;
    box-shadow: 0 3px 10px rgba(217,119,6,.35);
}
.practicas-alert-body strong {
    display: block;
    font-size: .85rem;
    font-weight: 800;
    color: #92400e;
    margin-bottom: .3rem;
}
.practicas-alert-body p {
    font-size: .82rem;
    color: #78350f;
    margin: 0 0 .4rem;
    line-height: 1.55;
    max-width: 480px;
}
.practicas-alert-tag {
    display: inline-flex;
    align-items: center;
    font-size: .75rem;
    font-weight: 700;
    color: #92400e;
    background: rgba(217,119,6,.12);
    border: 1px solid #fbbf24;
    border-radius: 2rem;
    padding: .2rem .7rem;
    white-space: nowrap;
    flex-wrap: wrap;
}

.step-card-body {
    flex: 1;
    padding: 1rem 1.4rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
}
.step-card-info .step-num-label {
    font-size: .68rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #94a3b8;
    margin-bottom: .2rem;
}
.step-card-info h5 {
    font-size: .97rem;
    font-weight: 700;
    color: #037435;
    margin: 0 0 .25rem;
}
.step-card-info p {
    font-size: .84rem;
    color: #64748b;
    margin: 0;
    line-height: 1.55;
    max-width: 530px;
}
.step-card-action { flex-shrink: 0; }

.btn-step-primary, .btn-step-teal, .btn-step-link, .btn-step-orange {
    border: none;
    border-radius: .6rem;
    padding: .52rem 1.25rem;
    font-size: .84rem;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    transition: background .2s, transform .15s;
    white-space: nowrap;
    text-decoration: none;
}
.btn-step-primary { background: #01643D; color: #fff; }
.btn-step-primary:hover { background: #01643D; transform: scale(1.04); color: #fff; }
.btn-step-teal    { background: #01643D; color: #fff; }
.btn-step-teal:hover { background: #00594F; transform: scale(1.04); color: #fff; }
.btn-step-link    { background: rgba(1,100,61,.07); color: #01643D; border: 1.5px solid #01643D; }
.btn-step-link:hover { background: #01643D; color: #fff; transform: scale(1.04); }
.btn-step-orange  { background: #d97706; color: #fff; }
.btn-step-orange:hover { background: #b45309; transform: scale(1.04); color: #fff; }

.done-badge {
    display: inline-flex; align-items: center; gap: .4rem;
    background: #f0fdf4; color: #16a34a;
    border: 1.5px solid #bbf7d0; border-radius: 2rem;
    padding: .35rem .9rem; font-size: .8rem; font-weight: 700;
}
.pending-badge {
    display: inline-flex; align-items: center; gap: .4rem;
    background: #fffbeb; color: #b45309;
    border: 1.5px solid #fde68a; border-radius: 2rem;
    padding: .35rem .9rem; font-size: .8rem; font-weight: 700;
}
.rejected-badge {
    display: inline-flex; align-items: center; gap: .4rem;
    background: #fff1f2; color: #be123c;
    border: 1.5px solid #fecdd3; border-radius: 2rem;
    padding: .35rem .9rem; font-size: .8rem; font-weight: 700;
}

/* ═══════════════════════════════════════════════
   HISTORIAL TIMELINE
═══════════════════════════════════════════════ */
.historial-panel {
    background: #fff;
    border-radius: 1rem;
    box-shadow: 0 2px 10px rgba(0,0,0,.05);
    overflow: hidden;
    margin-bottom: 2rem;
    animation: fadeUp .55s .3s ease both;
}
.historial-header {
    background: linear-gradient(135deg, #037435, #334155);
    color: #fff;
    padding: 1rem 1.4rem;
    display: flex;
    align-items: center;
    gap: .8rem;
}
.historial-header h6 { margin: 0; font-weight: 700; font-size: .95rem; }
.historial-body { padding: 1.2rem 1.4rem; }

.timeline { list-style: none; padding: 0; margin: 0; }
.timeline-item {
    display: flex;
    gap: 1rem;
    padding-bottom: 1.2rem;
    position: relative;
}
.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: 15px; top: 32px; bottom: 0;
    width: 2px;
    background: #e2e8f0;
}
.timeline-dot {
    width: 32px; height: 32px;
    border-radius: 50%;
    flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: .78rem;
    position: relative; z-index: 1;
}
.timeline-dot.pendiente { background: #fffbeb; color: #b45309; border: 2px solid #fde68a; }
.timeline-dot.aprobado  { background: #f0fdf4; color: #16a34a; border: 2px solid #bbf7d0; }
.timeline-dot.rechazado { background: #fff1f2; color: #be123c; border: 2px solid #fecdd3; }
.timeline-content .tc-title { font-size: .88rem; font-weight: 700; color: #037435; margin-bottom: .15rem; }
.timeline-content .tc-sub   { font-size: .78rem; color: #64748b; margin-bottom: .2rem; }
.timeline-content .tc-comment {
    font-size: .78rem; color: #475569;
    background: #f8fafc;
    border-left: 3px solid #e2e8f0;
    padding: .3rem .6rem;
    border-radius: 0 .4rem .4rem 0;
    margin-top: .3rem;
}

/* IJUMICH info */
.ijumich-info-box {
    background: linear-gradient(135deg, rgba(1,100,61,.05), rgba(198,219,83,.08));
    border: 1px solid rgba(1,100,61,.18);
    border-radius: .85rem;
    padding: .9rem 1.2rem;
    display: flex;
    align-items: flex-start;
    gap: .8rem;
    font-size: .82rem;
    color: #166534;
    margin-bottom: 1.8rem;
    animation: fadeDown .5s .3s ease both;
}
.ijumich-info-box i { font-size: 1.1rem; flex-shrink: 0; margin-top: .1rem; }
.ijumich-info-box--hero {
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.3);
    color: rgba(255,255,255,.95);
    margin-bottom: 0;
    animation: fadeDown .7s .35s ease both;
}
.ijumich-info-box--hero strong { color: #fff; }

/* Responsive */
@media (max-width: 640px) {
    .ext-hero { padding: 1.5rem 1rem 1.2rem 0; gap: 1rem; }
    .ext-hero-img { width: 140px; min-height: 180px; margin: -1.5rem 0 -1.2rem -0rem; }
    .ext-body { padding: 1rem; }
    .step-card-side { width: 48px; font-size: 1rem; }
    .ext-progress-wrap { padding: .8rem 1rem; }
    .ps-label { display: none; }
    .step-card-body { padding: .85rem 1rem; }
}
</style>

<div class="ext-page">

    <!-- ══ HERO ══ -->
    <div class="ext-hero">
        <div class="ext-hero-img">
            <img src="view/assets/images/Imagenjpg.jpg" alt="Plataforma IJUMICH">
        </div>
        <div class="ext-hero-content">
            <div class="ext-hero-text">
                <div class="hero-eyebrow">
                    <i class="fas fa-university"></i>
                    Servicio Social Externo &middot; IJUMICH
                </div>
                <h2>Hola, <?= htmlspecialchars(explode(' ', trim($nombreAlumno))[0]) ?> 👋<br>¡Tu servicio social comienza aquí!</h2>
                <p>Sigue los pasos de esta guía para realizar tu servicio social en un organismo externo receptor. Esta plataforma te acompaña en cada etapa del proceso.</p>
                <div class="hero-resource-btns">
                    <button class="btn-hero-resource btn-hero-video" data-bs-toggle="modal" data-bs-target="#modalVideoGuia">
                        <i class="fas fa-play-circle"></i> Ver video tutorial
                    </button>
                    <button class="btn-hero-resource btn-hero-pdf" data-bs-toggle="modal" data-bs-target="#modalPdfGuia">
                        <i class="fas fa-file-pdf"></i> Ver guía PDF
                    </button>
                </div>
            </div>

            <!-- ══ PROGRESS STEPPER ══ -->
            <div class="ext-progress-wrap">
                <div class="progress-stepper" id="progressStepper">
                    <div class="ps-step" data-step="1">
                        <div class="ps-dot"><i class="fas fa-mouse-pointer" style="font-size:.6rem;"></i></div>
                        <span class="ps-label">Elige organismo</span>
                    </div>
                    <div class="ps-step" data-step="2">
                        <div class="ps-dot"><i class="fas fa-award" style="font-size:.6rem;"></i></div>
                        <span class="ps-label">Acred. prácticas</span>
                    </div>
                    <div class="ps-step" data-step="3">
                        <div class="ps-dot"><i class="fas fa-file-alt" style="font-size:.6rem;"></i></div>
                        <span class="ps-label">Carta presentación</span>
                    </div>
                    <div class="ps-step" data-step="4">
                        <div class="ps-dot"><i class="fas fa-tasks" style="font-size:.6rem;"></i></div>
                        <span class="ps-label">En servicio</span>
                    </div>
                    <div class="ps-step" data-step="5">
                        <div class="ps-dot"><i class="fas fa-upload" style="font-size:.6rem;"></i></div>
                        <span class="ps-label">Carta liberación</span>
                    </div>
                    <div class="ps-step" data-step="6">
                        <div class="ps-dot"><i class="fas fa-graduation-cap" style="font-size:.6rem;"></i></div>
                        <span class="ps-label">¡Liberado!</span>
                    </div>
                </div>
            </div>

            <!-- Aviso IJUMICH dentro del hero -->
            <div class="ijumich-info-box ijumich-info-box--hero">
                <i class="fas fa-info-circle"></i>
                <span>
                    A partir de los cambios del <strong>IJUMICH</strong>, toda la gestión con los organismos receptores se realiza en su plataforma. Esta plataforma de UNIMO complementa el proceso generando tu carta de presentación y registrando tu carta de liberación para acreditar formalmente tu servicio.
                </span>
            </div>
        </div>
    </div>

    <!-- ══ BODY ══ -->
    <div class="ext-body">

        <!-- ══ STEP CARDS ══ -->
        <div class="step-cards">

            <!-- PASO 1 -->
            <div class="step-card sc-blue unlocked current" id="card-step-1">
                <div class="step-card-side"><i class="fas fa-mouse-pointer"></i></div>
                <div class="step-card-body">
                    <div class="step-card-info">
                        <div class="step-num-label">Paso 1</div>
                        <h5>Selecciona tu organismo receptor en el IJUMICH</h5>
                        <p>Ingresa al portal del IJUMICH, explora los organismos receptores disponibles y elige el que mejor se ajuste a tu perfil y carrera. Una vez seleccionado, regresa aquí.</p>
                    </div>
                    <div class="step-card-action">
                        <a class="btn-step-link" href="https://www.serviciosocial.ijumich.michoacan.gob.mx/" target="_blank" rel="noopener noreferrer">
                            <i class="fas fa-external-link-alt"></i> Abrir IJUMICH
                        </a>
                    </div>
                </div>
            </div>

            <!-- PASO 2 — Acreditación de Prácticas Profesionales -->
            <div class="step-card sc-orange" id="card-step-2">
                <div class="step-card-side"><i class="fas fa-award"></i></div>
                <div class="step-card-body">
                    <div class="step-card-info">
                        <div class="step-num-label">Paso 2</div>
                        <h5>Acredita tus Prácticas Profesionales</h5>

                        <!-- Aviso importante -->
                        <div class="practicas-alert">
                            <div class="practicas-alert-icon"><i class="fas fa-exclamation-triangle"></i></div>
                            <div class="practicas-alert-body">
                                <strong>Requisito indispensable</strong>
                                <p>Para iniciar tu Servicio Social <u>debes tener tus Prácticas Profesionales debidamente acreditadas</u>. Sin la carta de acreditación aprobada <strong>no podrás avanzar al siguiente paso</strong>.</p>
                                <span class="practicas-alert-tag"><i class="fas fa-lock me-1"></i>El Paso 3 se habilitará automáticamente una vez que el área administrativa valide este documento.</span>
                            </div>
                        </div>

                        <p class="mt-2" style="font-size:.84rem;color:#64748b;">Sube aquí tu <strong>Carta de Acreditación de Prácticas Profesionales</strong> emitida por UNIMO. El área administrativa la revisará y aprobará antes de que puedas solicitar tu carta de presentación.</p>
                    </div>
                    <div class="step-card-action" id="action-step-2">
                        <button class="btn-step-orange" id="btnCargarCartaPracticas">
                            <i class="fas fa-cloud-upload-alt"></i> Subir acreditación
                        </button>
                    </div>
                </div>
            </div>

            <!-- PASO 3 -->
            <div class="step-card sc-teal" id="card-step-3">
                <div class="step-card-side"><i class="fas fa-file-alt"></i></div>
                <div class="step-card-body">
                    <div class="step-card-info">
                        <div class="step-num-label">Paso 3</div>
                        <h5>Solicita tu carta de presentación</h5>
                        <p>Una vez que elegiste tu organismo receptor en IJUMICH y tus prácticas profesionales estén acreditadas, llena el formulario para que UNIMO genere tu carta de presentación oficial.</p>
                    </div>
                    <div class="step-card-action" id="action-step-3">
                        <button class="btn-step-primary" id="btnSolicitarCartaPresentacion">
                            <i class="fas fa-file-alt"></i> Solicitar carta
                        </button>
                    </div>
                </div>
            </div>

            <!-- PASO 4 -->
            <div class="step-card sc-indigo" id="card-step-4">
                <div class="step-card-side"><i class="fas fa-tasks"></i></div>
                <div class="step-card-body">
                    <div class="step-card-info">
                        <div class="step-num-label">Paso 4</div>
                        <h5>Realiza tu servicio social</h5>
                        <p>Con tu carta en mano, continúa el proceso directamente en la plataforma del IJUMICH y con tu organismo receptor. Cumple con todas las actividades y horas requeridas.</p>
                    </div>
                    <div class="step-card-action">
                        <a class="btn-step-link" href="https://www.serviciosocial.ijumich.michoacan.gob.mx/" target="_blank" rel="noopener noreferrer">
                            <i class="fas fa-external-link-alt"></i> Portal IJUMICH
                        </a>
                    </div>
                </div>
            </div>

            <!-- PASO 5 -->
            <div class="step-card sc-lime" id="card-step-5">
                <div class="step-card-side"><i class="fas fa-cloud-upload-alt"></i></div>
                <div class="step-card-body">
                    <div class="step-card-info">
                        <div class="step-num-label">Paso 5</div>
                        <h5>Carga tu carta de liberación</h5>
                        <p>Al concluir tu servicio social, sube aquí la carta de liberación expedida por tu organismo receptor o por el IJUMICH para que UNIMO valide y acredite tu proceso.</p>
                    </div>
                    <div class="step-card-action" id="action-step-5">
                        <button class="btn-step-teal" id="btnCargarCartaLiberacion">
                            <i class="fas fa-cloud-upload-alt"></i> Cargar carta
                        </button>
                    </div>
                </div>
            </div>

            <!-- PASO 6 -->
            <div class="step-card sc-emerald" id="card-step-6">
                <div class="step-card-side"><i class="fas fa-graduation-cap"></i></div>
                <div class="step-card-body">
                    <div class="step-card-info">
                        <div class="step-num-label">Paso 6</div>
                        <h5>¡Servicio social liberado!</h5>
                        <p>Una vez que el área administrativa valide tu carta de liberación, tu servicio social quedará formalmente acreditado en UNIMO. ¡Habrás completado el proceso!</p>
                    </div>
                    <div class="step-card-action" id="action-step-6">
                        <span class="pending-badge" style="opacity:.5;"><i class="fas fa-lock me-1"></i>Pendiente de validación</span>
                    </div>
                </div>
            </div>

        </div><!-- /step-cards -->


        <!-- ══ HISTORIAL ══ -->
        <div class="historial-panel">
            <div class="historial-header">
                <i class="fas fa-history"></i>
                <h6>Mis solicitudes enviadas</h6>
                <span id="historialBadge" class="ms-auto" style="font-size:.74rem;opacity:.7;">Cargando…</span>
            </div>
            <div class="historial-body">
                <ul class="timeline" id="timelineContainer">
                    <li style="list-style:none;text-align:center;color:#94a3b8;padding:1.5rem 0;">
                        <i class="fas fa-spinner fa-spin me-2"></i>Cargando historial...
                    </li>
                </ul>
            </div>
        </div>

    </div><!-- /ext-body -->
</div><!-- /ext-page -->


<!-- ══ Modal: Cargar carta de acreditación de prácticas profesionales ══ -->
<div class="modal fade" id="modalCartaPracticas" tabindex="-1" aria-labelledby="modalCartaPracticasLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:1rem;">
            <div class="modal-header" style="background:linear-gradient(135deg,#d97706,#b45309);color:#fff;border:none;">
                <h5 class="modal-title fw-bold" id="modalCartaPracticasLabel">
                    <i class="fas fa-award me-2"></i>Acreditación de Prácticas Profesionales
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formCartaPracticas" novalidate enctype="multipart/form-data">
                <div class="modal-body p-4">

                    <!-- Aviso importante -->
                    <div style="display:flex;gap:.75rem;align-items:flex-start;background:linear-gradient(135deg,#fffbeb,#fef3c7);border:1.5px solid #fbbf24;border-radius:.75rem;padding:.9rem 1.1rem;margin-bottom:1.2rem;">
                        <div style="flex-shrink:0;width:38px;height:38px;border-radius:50%;background:#d97706;color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.05rem;box-shadow:0 3px 10px rgba(217,119,6,.35);">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div>
                            <strong style="display:block;font-size:.84rem;font-weight:800;color:#92400e;margin-bottom:.3rem;">Requisito indispensable para tu Servicio Social</strong>
                            <p style="font-size:.82rem;color:#78350f;margin:0;line-height:1.6;">
                                Sin contar con tus <strong>Prácticas Profesionales debidamente acreditadas</strong>, no podrás iniciar ni completar tu Servicio Social. Asegúrate de que el documento que subas sea el <u>oficial y firmado</u> por la institución correspondiente.
                            </p>
                        </div>
                    </div>

                    <p class="text-muted mb-3" style="font-size:.88rem;">
                        Sube tu <strong>Carta de Acreditación de Prácticas Profesionales</strong>. Una vez revisada y aprobada por el área administrativa, podrás avanzar al Paso 3 y solicitar tu carta de presentación.
                    </p>
                    <div class="mb-3">
                        <label for="praArchivo" class="form-label fw-semibold" style="font-size:.88rem;">Documento (PDF o imagen) <span class="text-danger">*</span></label>
                        <input type="file" class="form-control form-control-sm" id="praArchivo" name="carta_practicas" accept="application/pdf,image/*" required>
                        <div class="form-text">Formatos aceptados: PDF, JPG, PNG. Tamaño máximo: 5 MB.</div>
                    </div>
                    <div class="mb-0">
                        <label for="praObservaciones" class="form-label fw-semibold" style="font-size:.88rem;">Observaciones <small class="text-muted fw-normal">(opcional)</small></label>
                        <textarea class="form-control form-control-sm" id="praObservaciones" name="observaciones" rows="2" placeholder="Información adicional si es necesario..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm text-white fw-semibold" style="background:#d97706;border-radius:.5rem;" id="btnSubmitCartaPracticas">
                        <i class="fas fa-cloud-upload-alt me-1"></i>Enviar acreditación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- ══ Modal: Solicitar carta de presentación ══ -->
<div class="modal fade" id="modalCartaPresentacion" tabindex="-1" aria-labelledby="modalCartaPresentacionLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:1rem;">
            <div class="modal-header" style="background:linear-gradient(135deg,#01643D,#2A7E5D);color:#fff;border:none;">
                <h5 class="modal-title fw-bold" id="modalCartaPresentacionLabel">
                    <i class="fas fa-file-alt me-2"></i>Solicitar Carta de Presentación
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formCartaPresentacion" novalidate>
                <div class="modal-body p-4">
                    <p class="text-muted mb-4" style="font-size:.87rem;">
                        <i class="fas fa-info-circle me-1 text-success"></i>
                        Ingresa los datos del organismo receptor que seleccionaste en la plataforma del IJUMICH.
                        Los campos marcados con <span class="text-danger fw-bold">*</span> son obligatorios.
                    </p>

                    <!-- ── Sección 1: Datos del organismo ── -->
                    <div class="mb-1" style="background:#f0fdf4;border-left:4px solid #01643D;border-radius:.5rem;padding:.55rem .9rem;margin-bottom:.9rem!important;">
                        <span style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#01643D;">
                            <i class="fas fa-building me-1"></i>Datos del organismo receptor
                        </span>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-8">
                            <label for="cpNombreOrganismo" class="form-label fw-semibold" style="font-size:.84rem;">Denominación o razón social <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="cpNombreOrganismo" name="nombre_organismo" placeholder="Ej. DIF Municipal de Morelia" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="cpRfc" class="form-label fw-semibold" style="font-size:.84rem;">RFC / Clave</label>
                            <input type="text" class="form-control form-control-sm" id="cpRfc" name="rfc_clave" placeholder="Ej. DIF-123456">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="cpArea" class="form-label fw-semibold" style="font-size:.84rem;">Área o departamento de desarrollo</label>
                            <input type="text" class="form-control form-control-sm" id="cpArea" name="area_departamento" placeholder="Ej. Departamento de Trabajo Social">
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="cpTelOrganismo" class="form-label fw-semibold" style="font-size:.84rem;">Teléfono(s)</label>
                            <input type="text" class="form-control form-control-sm" id="cpTelOrganismo" name="telefono_organismo" placeholder="Ej. 443 123 4567">
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="cpEmailOrganismo" class="form-label fw-semibold" style="font-size:.84rem;">Correo electrónico</label>
                            <input type="email" class="form-control form-control-sm" id="cpEmailOrganismo" name="email_organismo" placeholder="contacto@organismo.gob.mx">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label for="cpPaginaWeb" class="form-label fw-semibold" style="font-size:.84rem;">Página web</label>
                            <input type="url" class="form-control form-control-sm" id="cpPaginaWeb" name="pagina_web" placeholder="https://www.organismo.gob.mx">
                        </div>
                    </div>

                    <!-- ── Sección 2: Domicilio ── -->
                    <div class="mb-1" style="background:#f0fdf4;border-left:4px solid #01643D;border-radius:.5rem;padding:.55rem .9rem;margin-bottom:.9rem!important;">
                        <span style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#01643D;">
                            <i class="fas fa-map-marker-alt me-1"></i>Domicilio del organismo
                        </span>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-8">
                            <label for="cpCalle" class="form-label fw-semibold" style="font-size:.84rem;">Calle y número <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="cpCalle" name="calle_numero" placeholder="Ej. Av. Madero #100" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="cpColonia" class="form-label fw-semibold" style="font-size:.84rem;">Colonia</label>
                            <input type="text" class="form-control form-control-sm" id="cpColonia" name="colonia" placeholder="Ej. Centro Histórico">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-2">
                            <label for="cpCP" class="form-label fw-semibold" style="font-size:.84rem;">C.P.</label>
                            <input type="text" class="form-control form-control-sm" id="cpCP" name="codigo_postal" placeholder="58000" maxlength="6">
                        </div>
                        <div class="col-6 col-md-4">
                            <label for="cpMunicipio" class="form-label fw-semibold" style="font-size:.84rem;">Municipio <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="cpMunicipio" name="municipio" placeholder="Ej. Morelia" required>
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="cpEstado" class="form-label fw-semibold" style="font-size:.84rem;">Estado</label>
                            <input type="text" class="form-control form-control-sm" id="cpEstado" name="estado" placeholder="Ej. Michoacán">
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="cpPais" class="form-label fw-semibold" style="font-size:.84rem;">País</label>
                            <input type="text" class="form-control form-control-sm" id="cpPais" name="pais" placeholder="México" value="México">
                        </div>
                    </div>

                    <!-- ── Sección 3: Responsable ── -->
                    <div class="mb-1" style="background:#f0fdf4;border-left:4px solid #01643D;border-radius:.5rem;padding:.55rem .9rem;margin-bottom:.9rem!important;">
                        <span style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#01643D;">
                            <i class="fas fa-user-tie me-1"></i>Datos del responsable
                        </span>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="cpResponsable" class="form-label fw-semibold" style="font-size:.84rem;">Nombre completo <small class="text-muted fw-normal">(confirmar cómo se escribe)</small> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="cpResponsable" name="responsable" placeholder="Ej. Lic. Juan Pérez García" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="cpEstudios" class="form-label fw-semibold" style="font-size:.84rem;">Estudios profesionales</label>
                            <input type="text" class="form-control form-control-sm" id="cpEstudios" name="estudios_responsable" placeholder="Ej. Licenciado en Derecho">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-4">
                            <label for="cpPuesto" class="form-label fw-semibold" style="font-size:.84rem;">Cargo</label>
                            <input type="text" class="form-control form-control-sm" id="cpPuesto" name="puesto_responsable" placeholder="Ej. Director General">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="cpTelResponsable" class="form-label fw-semibold" style="font-size:.84rem;">Teléfono(s)</label>
                            <input type="text" class="form-control form-control-sm" id="cpTelResponsable" name="telefono_responsable" placeholder="Ej. 443 987 6543">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="cpEmailResponsable" class="form-label fw-semibold" style="font-size:.84rem;">Correo electrónico</label>
                            <input type="email" class="form-control form-control-sm" id="cpEmailResponsable" name="email_responsable" placeholder="responsable@organismo.gob.mx">
                        </div>
                    </div>

                    <!-- Observaciones -->
                    <div class="mb-0">
                        <label for="cpObservaciones" class="form-label fw-semibold" style="font-size:.84rem;">Observaciones adicionales</label>
                        <textarea class="form-control form-control-sm" id="cpObservaciones" name="observaciones" rows="2" placeholder="Información adicional que desees incluir..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm text-white fw-semibold" style="background:#01643D;border-radius:.5rem;" id="btnSubmitCartaPresentacion">
                        <i class="fas fa-paper-plane me-1"></i>Enviar solicitud
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- ══ Modal: Cargar carta de liberación ══ -->
<div class="modal fade" id="modalCartaLiberacion" tabindex="-1" aria-labelledby="modalCartaLiberacionLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:1rem;">
            <div class="modal-header" style="background:linear-gradient(135deg,#01643D,#00594F);color:#fff;border:none;">
                <h5 class="modal-title fw-bold" id="modalCartaLiberacionLabel">
                    <i class="fas fa-upload me-2"></i>Cargar Carta de Liberación
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formCartaLiberacion" novalidate enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <p class="text-muted mb-3" style="font-size:.88rem;">
                        Sube la carta de liberación expedida por el organismo receptor o el IJUMICH al concluir tu servicio social.
                    </p>
                    <div class="mb-3">
                        <label for="clArchivo" class="form-label fw-semibold" style="font-size:.88rem;">Archivo (PDF o imagen) <span class="text-danger">*</span></label>
                        <input type="file" class="form-control form-control-sm" id="clArchivo" name="carta_liberacion" accept="application/pdf,image/*" required>
                        <div class="form-text">Formatos aceptados: PDF, JPG, PNG. Tamaño máximo: 5 MB.</div>
                    </div>
                    <div class="mb-0">
                        <label for="clObservaciones" class="form-label fw-semibold" style="font-size:.88rem;">Observaciones</label>
                        <textarea class="form-control form-control-sm" id="clObservaciones" name="observaciones" rows="2" placeholder="Información adicional si es necesario..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm text-white fw-semibold" style="background:#01643D;border-radius:.5rem;" id="btnSubmitCartaLiberacion">
                        <i class="fas fa-cloud-upload-alt me-1"></i>Cargar carta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
$(document).ready(function () {

    /* ══════════════════════════════════════════
       Progress stepper
    ══════════════════════════════════════════ */
    function updateProgress(historial) {
        let step = 1;
        const praItems = historial.filter(r => r.tipo === 'carta_practicas');
        const cpItems  = historial.filter(r => r.tipo === 'carta_presentacion');
        const clItems  = historial.filter(r => r.tipo === 'carta_liberacion');

        const praAprobada  = praItems.some(r => r.status === 'aprobado');
        const praPendiente = praItems.some(r => r.status === 'pendiente');
        const cpAprobada   = cpItems.some(r => r.status === 'aprobado');
        const cpPendiente  = cpItems.some(r => r.status === 'pendiente');
        const clAprobada   = clItems.some(r => r.status === 'aprobado');
        const clPendiente  = clItems.some(r => r.status === 'pendiente');

        if      (clAprobada)   step = 6;
        else if (clPendiente)  step = 5;
        else if (cpAprobada)   step = 4;
        else if (cpPendiente)  step = 3;
        else if (praAprobada)  step = 3;
        else if (praPendiente) step = 2;

        $('#progressStepper .ps-step').each(function () {
            const s = parseInt($(this).data('step'));
            $(this).removeClass('done active');
            if      (s < step)  $(this).addClass('done');
            else if (s === step) $(this).addClass('active');
        });

        updateCards(step, praAprobada, praPendiente, cpAprobada, cpPendiente, clAprobada, clPendiente, praItems, cpItems, clItems);
    }

    function updateCards(step, praAprobada, praPendiente, cpAprobada, cpPendiente, clAprobada, clPendiente, praItems, cpItems, clItems) {
        $('.step-card').removeClass('current done-card unlocked');

        // Paso 1: siempre disponible
        $('#card-step-1').addClass('unlocked' + (step === 1 ? ' current' : ' done-card'));

        // Paso 2 — Acreditación de prácticas profesionales
        const $a2 = $('#action-step-2');
        if (praAprobada) {
            $('#card-step-2').addClass('done-card unlocked');
            $a2.html('<span class="done-badge"><i class="fas fa-check-circle me-1"></i>Acreditación aprobada</span>');
        } else if (praPendiente) {
            $('#card-step-2').addClass('done-card unlocked');
            $a2.html('<span class="pending-badge"><i class="fas fa-clock me-1"></i>En revisión por el área administrativa</span>');
        } else {
            const lastPraRejected = praItems.find(r => r.status === 'rechazado');
            if (lastPraRejected) {
                $a2.html(`<div><span class="rejected-badge mb-2 d-block"><i class="fas fa-times-circle me-1"></i>Documento rechazado — intenta de nuevo</span><button class="btn-step-orange mt-1" id="btnCargarCartaPracticas"><i class="fas fa-redo me-1"></i>Reintentar</button></div>`);
                bindModalBtns();
            }
            $('#card-step-2').addClass('unlocked' + (step <= 2 ? ' current' : ''));
        }

        // Paso 3 — Carta de presentación (requiere prácticas aprobadas)
        const $a3 = $('#action-step-3');
        if (cpAprobada) {
            $('#card-step-3').addClass('done-card unlocked');
            $a3.html('<span class="done-badge"><i class="fas fa-check-circle me-1"></i>Aprobada</span>');
        } else if (cpPendiente) {
            $('#card-step-3').addClass('done-card unlocked');
            $a3.html('<span class="pending-badge"><i class="fas fa-clock me-1"></i>En revisión</span>');
        } else {
            const lastCpRejected = cpItems.find(r => r.status === 'rechazado');
            if (lastCpRejected) {
                $a3.html(`<div><span class="rejected-badge mb-2 d-block"><i class="fas fa-times-circle me-1"></i>Rechazada — intenta de nuevo</span><button class="btn-step-primary mt-1" id="btnSolicitarCartaPresentacion"><i class="fas fa-redo me-1"></i>Nueva solicitud</button></div>`);
                bindModalBtns();
            }
            if (praAprobada) {
                $('#card-step-3').addClass('unlocked' + (step === 3 ? ' current' : ''));
            }
        }

        // Paso 4 — En servicio
        if (step >= 4) $('#card-step-4').addClass('unlocked' + (step === 4 ? ' current' : ' done-card'));

        // Paso 5 — Carta de liberación
        const $a5 = $('#action-step-5');
        if (clAprobada) {
            $('#card-step-5').addClass('done-card unlocked');
            $a5.html('<span class="done-badge"><i class="fas fa-check-circle me-1"></i>Aprobada</span>');
        } else if (clPendiente) {
            $('#card-step-5').addClass('done-card unlocked');
            $a5.html('<span class="pending-badge"><i class="fas fa-clock me-1"></i>En revisión</span>');
        } else {
            const lastClRejected = clItems.find(r => r.status === 'rechazado');
            if (lastClRejected) {
                $a5.html(`<div><span class="rejected-badge mb-2 d-block"><i class="fas fa-times-circle me-1"></i>Rechazada — intenta de nuevo</span><button class="btn-step-teal mt-1" id="btnCargarCartaLiberacion"><i class="fas fa-redo me-1"></i>Resubir carta</button></div>`);
                bindModalBtns();
            }
            if (step >= 5 || cpAprobada) $('#card-step-5').addClass('unlocked' + (step === 5 ? ' current' : ''));
        }

        // Paso 6 — Liberado
        const $a6 = $('#action-step-6');
        if (clAprobada) {
            $('#card-step-6').addClass('done-card unlocked current');
            $a6.html('<span class="done-badge" style="font-size:.88rem;padding:.45rem 1.1rem;"><i class="fas fa-graduation-cap me-1"></i>¡Servicio Social Liberado!</span>');
        }
    }

    /* ══════════════════════════════════════════
       Timeline historial
    ══════════════════════════════════════════ */
    const tipoLabel   = { carta_practicas: 'Acreditación de Prácticas Profesionales', carta_presentacion: 'Carta de Presentación', carta_liberacion: 'Carta de Liberación' };
    const statusLabel = { pendiente: 'En revisión', aprobado: 'Aprobada', rechazado: 'Rechazada' };
    const statusIcon  = { pendiente: 'fas fa-clock', aprobado: 'fas fa-check', rechazado: 'fas fa-times' };

    function renderTimeline(data) {
        const $c = $('#timelineContainer');
        if (!data || !data.length) {
            $c.html('<li style="list-style:none;text-align:center;color:#94a3b8;padding:1.5rem 0;"><i class="fas fa-inbox me-2"></i>Aún no has enviado ninguna solicitud.<br><small>Comienza por el Paso 1.</small></li>');
            $('#historialBadge').text('Sin solicitudes');
            return;
        }
        $('#historialBadge').text(data.length + ' solicitud' + (data.length !== 1 ? 'es' : ''));

        const items = data.map(function (r) {
            const tipo  = tipoLabel[r.tipo] || r.tipo;
            const fecha = r.created_at ? r.created_at.slice(0,10) : '';
            const icon  = statusIcon[r.status] || 'fas fa-circle';
            let detalle = '';
            if (r.tipo === 'carta_presentacion' && r.nombre_organismo) {
                const dlBtn = r.status === 'aprobado'
                    ? `<a href="controller/ajax/generarCartaIjumich.php?id=${r.id}" target="_blank" class="btn btn-sm btn-success mt-1"><i class="fas fa-file-download me-1"></i>Descargar Carta PDF</a>`
                    : '';
                detalle = `<div class="tc-sub"><i class="fas fa-building me-1"></i>${r.nombre_organismo}</div>${dlBtn}`;
            } else if (r.tipo === 'carta_liberacion' && r.archivo_nombre) {
                const link = (r.status === 'aprobado' && r.archivo_path)
                    ? `<a href="${r.archivo_path}" target="_blank" rel="noopener"><i class="fas fa-file-download me-1"></i>${r.archivo_nombre}</a>`
                    : `<span><i class="fas fa-file me-1"></i>${r.archivo_nombre}</span>`;
                detalle = `<div class="tc-sub">${link}</div>`;
            }
            const comentario = r.comentario_admin
                ? `<div class="tc-comment"><i class="fas fa-comment-alt me-1"></i>${r.comentario_admin}</div>` : '';

            return `
                <li class="timeline-item">
                    <div class="timeline-dot ${r.status}"><i class="${icon}"></i></div>
                    <div class="timeline-content">
                        <div class="tc-title">${tipo} <span class="fw-normal text-muted">— ${statusLabel[r.status] || r.status}</span></div>
                        <div class="tc-sub">${fecha}</div>
                        ${detalle}${comentario}
                    </div>
                </li>`;
        }).join('');
        $c.html(items);
    }

    function cargarHistorial() {
        $.ajax({
            url: 'controller/ajax/ajax.forms.php',
            type: 'POST',
            data: { action: 'get_historial_ijumich' },
            dataType: 'json',
            success: function (data) {
                renderTimeline(data || []);
                updateProgress(data || []);
            },
            error: function () {
                $('#timelineContainer').html('<li style="list-style:none;text-align:center;color:#dc3545;padding:1.5rem;">Error al cargar el historial.</li>');
            }
        });
    }

    /* ══════════════════════════════════════════
       Bind modal buttons (re-bindable after DOM replace)
    ══════════════════════════════════════════ */
    function bindModalBtns() {
        $(document).off('click', '#btnCargarCartaPracticas').on('click', '#btnCargarCartaPracticas', function () {
            $('#modalCartaPracticas').modal('show');
        });
        $(document).off('click', '#btnSolicitarCartaPresentacion').on('click', '#btnSolicitarCartaPresentacion', function () {
            $('#modalCartaPresentacion').modal('show');
        });
        $(document).off('click', '#btnCargarCartaLiberacion').on('click', '#btnCargarCartaLiberacion', function () {
            $('#modalCartaLiberacion').modal('show');
        });
    }
    bindModalBtns();

    /* ══════════════════════════════════════════
       Form: Carta de acreditación de prácticas
    ══════════════════════════════════════════ */
    $('#formCartaPracticas').on('submit', function (e) {
        e.preventDefault();
        const archivo = $('#praArchivo')[0].files[0];
        if (!archivo) {
            Swal.fire({ icon: 'warning', title: 'Archivo requerido', text: 'Debes seleccionar tu carta de acreditación de prácticas profesionales.', confirmButtonColor: '#d97706' });
            return;
        }
        if (archivo.size > 5 * 1024 * 1024) {
            Swal.fire({ icon: 'warning', title: 'Archivo muy grande', text: 'El archivo no debe superar los 5 MB.', confirmButtonColor: '#d97706' });
            return;
        }
        const $btn = $('#btnSubmitCartaPracticas').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Subiendo...');
        const formData = new FormData();
        formData.append('action', 'cargar_carta_practicas_ijumich');
        formData.append('carta_practicas', archivo);
        formData.append('observaciones', $('#praObservaciones').val());
        $.ajax({
            url: 'controller/ajax/ajax.forms.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (res) {
                $('#modalCartaPracticas').modal('hide');
                $('#formCartaPracticas')[0].reset();
                Swal.fire({ icon: 'success', title: '¡Documento enviado!', text: 'Tu carta de acreditación de prácticas fue registrada. El área administrativa la revisará y te notificará.', confirmButtonColor: '#d97706' });
                cargarHistorial();
            },
            error: function () {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error al subir el documento. Inténtalo de nuevo.', confirmButtonColor: '#d97706' });
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fas fa-cloud-upload-alt me-1"></i>Enviar acreditación');
            }
        });
    });

    /* ══════════════════════════════════════════
       Form: Carta de presentación
    ══════════════════════════════════════════ */
    $('#formCartaPresentacion').on('submit', function (e) {
        e.preventDefault();
        const nombre      = $.trim($('#cpNombreOrganismo').val());
        const responsable = $.trim($('#cpResponsable').val());
        const calle       = $.trim($('#cpCalle').val());
        const municipio   = $.trim($('#cpMunicipio').val());
        if (!nombre || !responsable || !calle || !municipio) {
            Swal.fire({ icon: 'warning', title: 'Campos requeridos', text: 'Por favor completa los campos obligatorios.', confirmButtonColor: '#01643D' });
            return;
        }
        const $btn = $('#btnSubmitCartaPresentacion').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Enviando...');
        $.ajax({
            url: 'controller/ajax/ajax.forms.php',
            type: 'POST',
            data: {
                action:               'solicitar_carta_presentacion_ijumich',
                nombre_organismo:     nombre,
                rfc_clave:            $('#cpRfc').val(),
                area_departamento:    $('#cpArea').val(),
                telefono_organismo:   $('#cpTelOrganismo').val(),
                email_organismo:      $('#cpEmailOrganismo').val(),
                pagina_web:           $('#cpPaginaWeb').val(),
                calle_numero:         calle,
                colonia:              $('#cpColonia').val(),
                codigo_postal:        $('#cpCP').val(),
                municipio:            municipio,
                estado:               $('#cpEstado').val(),
                pais:                 $('#cpPais').val(),
                estudios_responsable: $('#cpEstudios').val(),
                responsable:          responsable,
                puesto_responsable:   $('#cpPuesto').val(),
                telefono_responsable: $('#cpTelResponsable').val(),
                email_responsable:    $('#cpEmailResponsable').val(),
                observaciones:        $('#cpObservaciones').val()
            },
            dataType: 'json',
            success: function (res) {
                $('#modalCartaPresentacion').modal('hide');
                $('#formCartaPresentacion')[0].reset();
                Swal.fire({ icon: 'success', title: '¡Solicitud enviada!', text: 'Tu solicitud fue registrada. El área administrativa te notificará cuando esté lista.', confirmButtonColor: '#01643D' });
                cargarHistorial();
            },
            error: function () {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error al enviar la solicitud.', confirmButtonColor: '#01643D' });
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i>Enviar solicitud');
            }
        });
    });

    /* ══════════════════════════════════════════
       Form: Carta de liberación
    ══════════════════════════════════════════ */
    $('#formCartaLiberacion').on('submit', function (e) {
        e.preventDefault();
        const archivo = $('#clArchivo')[0].files[0];
        if (!archivo) {
            Swal.fire({ icon: 'warning', title: 'Archivo requerido', text: 'Debes seleccionar la carta de liberación.', confirmButtonColor: '#01643D' });
            return;
        }
        if (archivo.size > 5 * 1024 * 1024) {
            Swal.fire({ icon: 'warning', title: 'Archivo muy grande', text: 'El archivo no debe superar los 5 MB.', confirmButtonColor: '#01643D' });
            return;
        }
        const $btn = $('#btnSubmitCartaLiberacion').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Cargando...');
        const formData = new FormData();
        formData.append('action', 'cargar_carta_liberacion_ijumich');
        formData.append('carta_liberacion', archivo);
        formData.append('observaciones', $('#clObservaciones').val());
        $.ajax({
            url: 'controller/ajax/ajax.forms.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (res) {
                $('#modalCartaLiberacion').modal('hide');
                $('#formCartaLiberacion')[0].reset();
                Swal.fire({ icon: 'success', title: '¡Carta cargada!', text: 'Tu carta fue registrada. Tu servicio social quedará formalmente liberado una vez validada.', confirmButtonColor: '#01643D' });
                cargarHistorial();
            },
            error: function () {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error al cargar el archivo.', confirmButtonColor: '#01643D' });
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fas fa-cloud-upload-alt me-1"></i>Cargar carta');
            }
        });
    });

    /* ── Init ── */
    cargarHistorial();

    /* ══════════════════════════════════════════
       MODAL VIDEO — Plyr
    ══════════════════════════════════════════ */
    let plyrPlayer = null;
    const videoModal = document.getElementById('modalVideoGuia');
    videoModal.addEventListener('shown.bs.modal', function () {
        if (!plyrPlayer) {
            plyrPlayer = new Plyr('#heroVideoPlayer', {
                controls: ['play-large','play','progress','current-time','mute','volume','fullscreen'],
                tooltips: { controls: true, seek: true },
                i18n: { play: 'Reproducir', pause: 'Pausar', mute: 'Silenciar', unmute: 'Activar sonido', fullscreen: 'Pantalla completa' }
            });
        }
        plyrPlayer.play();
    });
    videoModal.addEventListener('hide.bs.modal', function () {
        if (plyrPlayer) plyrPlayer.pause();
    });

    /* ══════════════════════════════════════════
       MODAL PDF — iframe
    ══════════════════════════════════════════ */
    const pdfModal = document.getElementById('modalPdfGuia');
    pdfModal.addEventListener('show.bs.modal', function () {
        const iframe = document.getElementById('pdfViewerIframe');
        if (!iframe.src || iframe.src === 'about:blank') {
            iframe.src = 'view/assets/documents/files/Alumnos.pdf';
        }
    });
    pdfModal.addEventListener('hide.bs.modal', function () {
        // keep src so reopening is instant
    });
});
</script>

<!-- ══ MODAL VIDEO ══ -->
<div class="modal fade" id="modalVideoGuia" tabindex="-1" aria-labelledby="modalVideoGuiaLabel" aria-hidden="true" data-bs-backdrop="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content modal-video-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold" id="modalVideoGuiaLabel"><i class="fas fa-play-circle me-2"></i>Video tutorial — Servicio Social Externo</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <video id="heroVideoPlayer" playsinline controls>
                    <source src="view/assets/documents/files/Alumnos.mp4" type="video/mp4">
                    Tu navegador no soporta reproducción de video HTML5.
                </video>
            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL PDF ══ -->
<div class="modal fade" id="modalPdfGuia" tabindex="-1" aria-labelledby="modalPdfGuiaLabel" aria-hidden="true" data-bs-backdrop="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content modal-pdf-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold" id="modalPdfGuiaLabel"><i class="fas fa-file-pdf me-2 text-danger"></i>Guía de Alumnos — Servicio Social Externo</h6>
                <a href="view/assets/documents/files/Alumnos.pdf" download class="btn btn-sm btn-outline-light ms-auto me-2" style="font-size:.78rem;">
                    <i class="fas fa-download me-1"></i>Descargar
                </a>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <iframe id="pdfViewerIframe" src="about:blank" title="Guía PDF Alumnos"></iframe>
            </div>
        </div>
    </div>
</div>

<!-- Plyr JS -->
<script src="view/assets/libs/plyr/plyr.min.js"></script>
