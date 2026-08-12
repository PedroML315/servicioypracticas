<?php
// view/pages/configs.php — Shell de Configuraciones del administrador

/* ─────────────────────────────────────────────────────────────
   REGISTRO DE EDITORES
   Un solo lugar define: archivo, título, descripción, icono,
   grupo y palabras clave (para el buscador y la vista de inicio).
   ───────────────────────────────────────────────────────────── */
$CFG_EDITORS = [
    'email' => [
        'file'  => 'view/pages/configs/mail_templates.php',
        'title' => 'Plantillas de correo',
        'short' => 'Plantillas de correo',
        'desc'  => 'Asunto y contenido de cada notificación automática que envía el sistema.',
        'icon'  => 'fa-envelope',
        'tone'  => 'blue',
        'group' => 'comunicacion',
        'kw'    => 'correo correos email mail notificaciones plantillas mensajes asunto',
    ],
    'mail_bulk' => [
        'file'  => 'view/pages/configs/mail_bulk.php',
        'title' => 'Envío masivo de correos',
        'short' => 'Envío masivo',
        'desc'  => 'Redacta y envía un correo a una lista propia de personas, con seguimiento del envío.',
        'icon'  => 'fa-paper-plane',
        'tone'  => 'blue',
        'group' => 'comunicacion',
        'kw'    => 'correo masivo campaña boletin destinatarios envio masivo mailing',
    ],
    'carta' => [
        'file'  => 'view/pages/configs/carta-editor.php',
        'title' => 'Carta de presentación',
        'short' => 'Carta de presentación',
        'desc'  => 'Documento con el que el alumno se presenta ante la institución receptora.',
        'icon'  => 'fa-file-lines',
        'tone'  => 'green',
        'group' => 'servicio',
        'kw'    => 'carta presentacion servicio social pdf documento',
    ],
    'carta_aceptacion' => [
        'file'  => 'view/pages/configs/carta-aceptacion-editor.php',
        'title' => 'Carta de aceptación',
        'short' => 'Carta de aceptación',
        'desc'  => 'Documento que confirma que el alumno fue aceptado para su servicio interno.',
        'icon'  => 'fa-file-check',
        'tone'  => 'green',
        'group' => 'servicio',
        'kw'    => 'carta aceptacion servicio social interno pdf documento',
    ],
    'carta_conclusion' => [
        'file'  => 'view/pages/configs/carta-conclusion-editor.php',
        'title' => 'Carta de conclusión',
        'short' => 'Carta de conclusión',
        'desc'  => 'Documento que acredita que el alumno terminó su servicio interno.',
        'icon'  => 'fa-file-certificate',
        'tone'  => 'green',
        'group' => 'servicio',
        'kw'    => 'carta conclusion termino liberacion servicio social interno pdf',
    ],
    'carta_practicas' => [
        'file'  => 'view/pages/configs/carta-practicas-editor.php',
        'title' => 'Carta de presentación',
        'short' => 'Carta de presentación',
        'desc'  => 'Documento con el que el alumno se presenta ante el organismo receptor.',
        'icon'  => 'fa-briefcase',
        'tone'  => 'amber',
        'group' => 'practicas',
        'kw'    => 'carta presentacion practicas profesionales pdf documento',
    ],
    'constancia' => [
        'file'  => 'view/pages/configs/constancia-editor.php',
        'title' => 'Constancia de acreditación',
        'short' => 'Constancia',
        'desc'  => 'Comprobante que se entrega al alumno al terminar sus prácticas.',
        'icon'  => 'fa-award',
        'tone'  => 'amber',
        'group' => 'practicas',
        'kw'    => 'constancia acreditacion practicas profesionales pdf documento termino',
    ],
    'convenio' => [
        'file'  => 'view/pages/configs/convenio-editor.php',
        'title' => 'Convenio de prácticas',
        'short' => 'Convenio',
        'desc'  => 'Acuerdo que firma cada organismo receptor antes de recibir practicantes.',
        'icon'  => 'fa-file-signature',
        'tone'  => 'amber',
        'group' => 'practicas',
        'kw'    => 'convenio organismo empresa firma practicas profesionales pdf',
    ],
    'general_settings' => [
        'file'  => 'view/pages/configs/general_settings.php',
        'title' => 'Ajustes generales',
        'short' => 'Ajustes generales',
        'desc'  => 'Parámetros globales de la plataforma, como los correos de contacto.',
        'icon'  => 'fa-sliders',
        'tone'  => 'slate',
        'group' => 'sistema',
        'kw'    => 'general sistema correo capacitacion parametros globales ajustes',
    ],
];

$CFG_GROUPS = [
    'comunicacion' => [
        'label' => 'Comunicación',
        'icon'  => 'fa-paper-plane',
        'desc'  => 'Lo que el sistema envía por correo.',
    ],
    'servicio' => [
        'label' => 'Servicio social',
        'icon'  => 'fa-hand-holding-heart',
        'desc'  => 'Documentos que se generan en el proceso de servicio social.',
    ],
    'practicas' => [
        'label' => 'Prácticas profesionales',
        'icon'  => 'fa-briefcase',
        'desc'  => 'Documentos y convenios del proceso de prácticas.',
    ],
    'sistema' => [
        'label' => 'Sistema',
        'icon'  => 'fa-gear',
        'desc'  => 'Configuración general de la plataforma.',
    ],
];

$editor = $_GET['editor'] ?? 'home';
if ($editor !== 'home' && !isset($CFG_EDITORS[$editor])) {
    $editor = 'home';
}

/** Editores que muestran su propio panel lateral de variables. */
$hasSidePanel = $editor !== 'home' && $editor !== 'general_settings';

function cfgRenderEditor(string $which, array $registry): void
{
    $file = $registry[$which]['file'] ?? '';
    if ($file && is_file($file)) {
        include $file;
        return;
    }
    echo '<div class="editor-not-found">
            <i class="fa-solid fa-triangle-exclamation"></i>
            No se encontró el editor: <code>' . htmlspecialchars($which) . '</code>
          </div>';
}
?>
<script>
    (function () {
        if (!document.querySelector('link[href*="quill"]')) {
            var l = document.createElement('link');
            l.rel = 'stylesheet';
            l.href = 'https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css';
            document.head.appendChild(l);
        }
    })();
</script>

<style>
    /* ═══ TOKENS ═══════════════════════════════════════════════ */
    .cfg-shell {
        --cfg-accent: #16a34a;
        --cfg-accent-soft: #ecfdf5;
        --cfg-card: #ffffff;
        --cfg-line: #e6eaf0;
        --cfg-line-soft: #eef2f7;
        --cfg-radius: 14px;
        --cfg-text: #0f172a;
        --cfg-muted: #64748b;
        --cfg-faint: #94a3b8;
        --cfg-focus: 0 0 0 3px rgba(22, 163, 74, .18);
        --cfg-shadow: 0 1px 2px rgba(15, 23, 42, .04), 0 1px 3px rgba(15, 23, 42, .03);
        --cfg-nav-w: 260px;
        --cfg-side-w: 300px;

        width: 100%;
        color: var(--cfg-text);
    }

    /* La hoja global pinta los encabezados de blanco: aquí se recuperan. */
    .cfg-shell h1, .cfg-shell h2, .cfg-shell h3,
    .cfg-shell h4, .cfg-shell h5, .cfg-shell h6,
    .cfg-shell .h1, .cfg-shell .h2, .cfg-shell .h3,
    .cfg-shell .h4, .cfg-shell .h5, .cfg-shell .h6 {
        color: var(--cfg-text);
    }

    /* Paletas por tono (icono de cada editor) */
    .cfg-shell .tone-blue { --t-bg: #eff6ff; --t-fg: #1d4ed8; }
    .cfg-shell .tone-green { --t-bg: #ecfdf5; --t-fg: #15803d; }
    .cfg-shell .tone-amber { --t-bg: #fffbeb; --t-fg: #b45309; }
    .cfg-shell .tone-slate { --t-bg: #f1f5f9; --t-fg: #475569; }

    /* ═══ ENCABEZADO DE LA PÁGINA ══════════════════════════════ */
    .cfg-topbar {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 18px;
    }

    .cfg-topbar h1 {
        font-size: 1.35rem;
        font-weight: 700;
        margin: 0;
        letter-spacing: -.01em;
    }

    .cfg-topbar p {
        margin: 2px 0 0;
        font-size: .82rem;
        color: var(--cfg-muted);
    }

    /* ═══ LAYOUT DE DOS COLUMNAS ═══════════════════════════════ */
    .cfg-layout {
        display: grid;
        grid-template-columns: var(--cfg-nav-w) minmax(0, 1fr);
        gap: 22px;
        align-items: start;
    }

    /* ═══ NAVEGACIÓN LATERAL ═══════════════════════════════════ */
    .cfg-nav {
        position: sticky;
        top: 14px;
        background: var(--cfg-card);
        border: 1px solid var(--cfg-line);
        border-radius: var(--cfg-radius);
        padding: 12px;
        box-shadow: var(--cfg-shadow);
        max-height: calc(100vh - 28px);
        overflow-y: auto;
        overscroll-behavior: contain;
    }

    .cfg-search {
        position: relative;
        margin-bottom: 10px;
    }

    .cfg-search i {
        position: absolute;
        left: 11px;
        top: 50%;
        transform: translateY(-50%);
        font-size: .78rem;
        color: var(--cfg-faint);
        pointer-events: none;
    }

    .cfg-search input {
        width: 100%;
        border: 1px solid var(--cfg-line);
        background: #f8fafc;
        border-radius: 9px;
        padding: 8px 10px 8px 31px;
        font-size: .81rem;
        color: var(--cfg-text);
        transition: border-color .14s, box-shadow .14s, background .14s;
    }

    .cfg-search input::placeholder { color: var(--cfg-faint); }

    .cfg-search input:focus {
        outline: none;
        background: #fff;
        border-color: var(--cfg-accent);
        box-shadow: var(--cfg-focus);
    }

    .cfg-navgroup + .cfg-navgroup { margin-top: 14px; }

    .cfg-navgroup-title {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: .66rem;
        font-weight: 700;
        letter-spacing: .09em;
        text-transform: uppercase;
        color: var(--cfg-faint);
        padding: 0 8px 6px;
    }

    .cfg-navitem {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 9px;
        border-radius: 10px;
        text-decoration: none;
        color: #334155;
        font-size: .845rem;
        font-weight: 500;
        line-height: 1.25;
        transition: background .13s, color .13s;
    }

    .cfg-navitem + .cfg-navitem { margin-top: 2px; }

    .cfg-navitem .ico {
        flex: 0 0 auto;
        width: 28px;
        height: 28px;
        border-radius: 8px;
        display: grid;
        place-items: center;
        font-size: .78rem;
        background: var(--t-bg, #f1f5f9);
        color: var(--t-fg, #475569);
        transition: background .13s, color .13s;
    }

    .cfg-navitem:hover {
        background: #f5f7fa;
        color: var(--cfg-text);
    }

    .cfg-navitem.active {
        background: var(--cfg-accent-soft);
        color: #14532d;
        font-weight: 650;
    }

    .cfg-navitem.active .ico {
        background: var(--cfg-accent);
        color: #fff;
    }

    .cfg-nav-home {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 7px 9px;
        margin-bottom: 8px;
        border-radius: 9px;
        font-size: .8rem;
        font-weight: 600;
        color: var(--cfg-muted);
        text-decoration: none;
        border: 1px dashed var(--cfg-line);
        transition: background .13s, color .13s, border-color .13s;
    }

    .cfg-nav-home:hover {
        background: #f5f7fa;
        color: var(--cfg-text);
        border-color: #cbd5e1;
    }

    .cfg-nav-empty {
        display: none;
        padding: 14px 8px;
        font-size: .8rem;
        color: var(--cfg-faint);
        text-align: center;
    }

    /* ═══ CABECERA DEL EDITOR ACTIVO ═══════════════════════════ */
    .cfg-pagehead {
        display: flex;
        align-items: center;
        gap: 14px;
        background: var(--cfg-card);
        border: 1px solid var(--cfg-line);
        border-radius: var(--cfg-radius);
        padding: 15px 18px;
        margin-bottom: 16px;
        box-shadow: var(--cfg-shadow);
    }

    .cfg-pagehead .ico {
        flex: 0 0 auto;
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        font-size: 1.05rem;
        background: var(--t-bg, #f1f5f9);
        color: var(--t-fg, #475569);
    }

    .cfg-pagehead .txt { min-width: 0; flex: 1 1 auto; }

    .cfg-crumb {
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--cfg-faint);
        margin-bottom: 1px;
    }

    .cfg-pagehead h2 {
        font-size: 1.05rem;
        font-weight: 700;
        margin: 0;
        letter-spacing: -.01em;
    }

    .cfg-pagehead p {
        margin: 2px 0 0;
        font-size: .8rem;
        color: var(--cfg-muted);
    }

    .cfg-sidetoggle {
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        border: 1px solid var(--cfg-line);
        background: #fff;
        border-radius: 9px;
        padding: 7px 12px;
        font-size: .78rem;
        font-weight: 600;
        color: #334155;
        cursor: pointer;
        transition: background .13s, border-color .13s, color .13s;
    }

    .cfg-sidetoggle:hover { background: #f5f7fa; border-color: #cbd5e1; }
    .cfg-sidetoggle.is-on { background: var(--cfg-accent-soft); border-color: #bbf7d0; color: #14532d; }

    /* ═══ ÁREA DE TRABAJO (contenido + panel de ayuda) ═════════ */
    .cfg-workarea {
        display: grid;
        grid-template-columns: minmax(0, 1fr) var(--cfg-side-w);
        gap: 18px;
        align-items: start;
    }

    .cfg-workarea.side-hidden { grid-template-columns: minmax(0, 1fr); }
    .cfg-workarea.side-hidden .cfg-side { display: none; }

    .cfg-side {
        position: sticky;
        top: 14px;
        max-height: calc(100vh - 28px);
        display: flex;
        flex-direction: column;
        background: var(--cfg-card);
        border: 1px solid var(--cfg-line);
        border-radius: var(--cfg-radius);
        box-shadow: var(--cfg-shadow);
        overflow: hidden;
    }

    .cfg-side-head {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 11px 14px;
        border-bottom: 1px solid var(--cfg-line-soft);
        font-size: .78rem;
        font-weight: 700;
        color: var(--cfg-text);
        background: #fbfdfc;
    }

    .cfg-side-head .fa-circle-info { color: var(--cfg-accent); }

    .cfg-side-close {
        margin-left: auto;
        border: 0;
        background: transparent;
        color: var(--cfg-faint);
        font-size: .82rem;
        line-height: 1;
        padding: 4px 6px;
        border-radius: 6px;
        cursor: pointer;
    }

    .cfg-side-close:hover { background: #f1f5f9; color: var(--cfg-text); }

    .cfg-side-body {
        padding: 14px;
        overflow-y: auto;
        overscroll-behavior: contain;
        font-size: .8rem;
    }

    .cfg-side-body strong { font-size: .78rem; }
    .cfg-side-body small { line-height: 1.45; }

    /* ═══ BARRA DE ACCIONES FIJA ═══════════════════════════════ */
    .cfg-actionbar {
        position: sticky;
        bottom: 12px;
        z-index: 40;
        display: flex;
        align-items: center;
        gap: 14px;
        flex-wrap: wrap;
        margin-top: 18px;
        padding: 11px 16px;
        background: rgba(255, 255, 255, .94);
        backdrop-filter: blur(8px);
        border: 1px solid var(--cfg-line);
        border-radius: var(--cfg-radius);
        box-shadow: 0 8px 26px rgba(15, 23, 42, .10);
        transition: border-color .16s, box-shadow .16s;
    }

    .cfg-actionbar:not(.is-ready) { display: none; }
    .cfg-actionbar.is-dirty { border-color: #fcd34d; box-shadow: 0 8px 26px rgba(180, 83, 9, .13); }

    .cfg-status {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: .8rem;
        font-weight: 600;
        color: var(--cfg-muted);
        margin-right: auto;
    }

    .cfg-status .dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #cbd5e1;
        flex: 0 0 auto;
    }

    .cfg-actionbar.is-dirty .cfg-status { color: #b45309; }
    .cfg-actionbar.is-dirty .cfg-status .dot { background: #f59e0b; box-shadow: 0 0 0 3px rgba(245, 158, 11, .18); }

    .cfg-actionbar.is-ok { border-color: #86efac; }
    .cfg-actionbar.is-ok .cfg-status { color: #15803d; }
    .cfg-actionbar.is-ok .cfg-status .dot { background: var(--cfg-accent); box-shadow: 0 0 0 3px rgba(22, 163, 74, .16); }

    .cfg-actionbar.is-error { border-color: #fca5a5; }
    .cfg-actionbar.is-error .cfg-status { color: #b91c1c; }
    .cfg-actionbar.is-error .cfg-status .dot { background: #ef4444; box-shadow: 0 0 0 3px rgba(239, 68, 68, .16); }

    .cfg-actions-slot {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .cfg-actions-slot .btn.w-100 { width: auto !important; }
    .cfg-actions-slot .btn { margin: 0 !important; }

    /* ═══ INICIO (vista general) ═══════════════════════════════ */
    .cfg-home-intro {
        display: flex;
        align-items: flex-start;
        gap: 13px;
        background: linear-gradient(180deg, #f8fefb, #ffffff);
        border: 1px solid #d9f2e3;
        border-radius: var(--cfg-radius);
        padding: 16px 18px;
        margin-bottom: 20px;
    }

    .cfg-home-intro i {
        color: var(--cfg-accent);
        font-size: 1.05rem;
        margin-top: 2px;
    }

    .cfg-home-intro p {
        margin: 0;
        font-size: .84rem;
        color: #334155;
        line-height: 1.55;
    }

    .cfg-home-group + .cfg-home-group { margin-top: 26px; }

    .cfg-home-grouphead {
        display: flex;
        align-items: baseline;
        gap: 10px;
        margin-bottom: 11px;
    }

    .cfg-home-grouphead h3 {
        font-size: .95rem;
        font-weight: 700;
        margin: 0;
    }

    .cfg-home-grouphead span {
        font-size: .78rem;
        color: var(--cfg-muted);
    }

    .cfg-home-intro,
    .cfg-home-group { max-width: 1160px; }

    .cfg-cards {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 13px;
    }

    @media (max-width: 1399.98px) {
        .cfg-cards { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    @media (max-width: 767.98px) {
        .cfg-cards { grid-template-columns: minmax(0, 1fr); }
    }

    .cfg-card {
        display: flex;
        gap: 12px;
        background: var(--cfg-card);
        border: 1px solid var(--cfg-line);
        border-radius: var(--cfg-radius);
        padding: 15px 16px;
        text-decoration: none;
        color: inherit;
        box-shadow: var(--cfg-shadow);
        transition: transform .14s, box-shadow .14s, border-color .14s;
    }

    .cfg-card:hover {
        transform: translateY(-2px);
        border-color: #cbd5e1;
        box-shadow: 0 8px 22px rgba(15, 23, 42, .09);
        color: inherit;
    }

    .cfg-card .ico {
        flex: 0 0 auto;
        width: 38px;
        height: 38px;
        border-radius: 11px;
        display: grid;
        place-items: center;
        font-size: .95rem;
        background: var(--t-bg, #f1f5f9);
        color: var(--t-fg, #475569);
    }

    .cfg-card .txt {
        min-width: 0;
        flex: 1 1 auto;
    }

    .cfg-card .ttl {
        display: block;
        font-size: .88rem;
        font-weight: 650;
        color: var(--cfg-text);
        margin-bottom: 3px;
    }

    .cfg-card .dsc {
        display: block;
        font-size: .78rem;
        color: var(--cfg-muted);
        line-height: 1.45;
    }

    .cfg-card .go {
        margin-left: auto;
        align-self: center;
        color: var(--cfg-faint);
        font-size: .75rem;
        transition: transform .14s, color .14s;
    }

    .cfg-card:hover .go { transform: translateX(3px); color: var(--cfg-accent); }

    /* ═══ NORMALIZACIÓN DE LOS EDITORES INCLUIDOS ══════════════ */
    .cfg-body { position: relative; }

    /* Los editores traen .container (y a veces .row) de Bootstrap:
       aquí ocupan el ancho de la columna, sin gutters negativos. */
    .cfg-body > .container,
    .cfg-body > form > .container {
        width: 100%;
        max-width: 100%;
        padding-left: 0;
        padding-right: 0;
        margin-left: 0;
        margin-right: 0;
    }

    .cfg-body > .container.row { --bs-gutter-x: 0; }
    .cfg-body .has-rail { padding-right: 0; }

    /* Tarjetas de sección */
    .cfg-body .form-section,
    .cfg-body .gs-section {
        background: var(--cfg-card);
        border: 1px solid var(--cfg-line);
        border-radius: var(--cfg-radius);
        padding: 20px 22px;
        margin-bottom: 16px;
        box-shadow: var(--cfg-shadow);
        position: relative;
    }

    .cfg-body .form-section h2,
    .cfg-body .gs-section h2 {
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .07em;
        text-transform: uppercase;
        color: var(--cfg-muted);
        margin: 0 0 14px;
    }

    /* Solo el título que abre la tarjeta lleva línea divisoria. */
    .cfg-body .form-section > h2,
    .cfg-body .gs-section > h2 {
        padding-bottom: 10px;
        border-bottom: 1px solid var(--cfg-line-soft);
    }

    .cfg-body .gs-section > h2 { margin-bottom: 6px; }
    .cfg-body .gs-section .section-desc { font-size: .8rem; color: var(--cfg-muted); margin: 0 0 14px; }

    /* Controles de formulario */
    .cfg-body .form-label {
        font-size: .78rem;
        font-weight: 600;
        color: #334155;
        margin-bottom: 4px;
    }

    .cfg-body .form-text,
    .cfg-body .help-hover {
        font-size: .73rem;
        color: var(--cfg-muted);
    }

    .cfg-body .form-control,
    .cfg-body .form-select {
        border-radius: 9px;
        border: 1px solid #d7dee7;
        font-size: .85rem;
        padding: 8px 11px;
        transition: border-color .14s, box-shadow .14s;
    }

    .cfg-body .form-control:focus,
    .cfg-body .form-select:focus {
        border-color: var(--cfg-accent);
        box-shadow: var(--cfg-focus);
        outline: none;
    }

    .cfg-body .form-control-color { padding: 4px 6px; height: 38px; }

    /* Chips de variables (también en el panel lateral) */
    .cfg-shell code.k {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        padding: 2px 8px;
        border-radius: 6px;
        font-family: "Fira Code", Consolas, monospace;
        font-size: .74rem;
        color: #15803d;
        cursor: pointer;
        transition: background .14s, color .14s, border-color .14s, transform .14s;
        display: inline-block;
    }

    .cfg-shell code.k:hover {
        background: var(--cfg-accent);
        color: #fff;
        border-color: #15803d;
        transform: translateY(-1px);
    }

    /* Confirmación al copiar una variable */
    .cfg-shell code.k.copied {
        background: var(--cfg-accent);
        border-color: #15803d;
        color: #fff;
    }

    /* Quill */
    .cfg-body #body_paragraphs_editor .ql-toolbar,
    .cfg-body #html_editor .ql-toolbar {
        border-radius: 9px 9px 0 0;
        border-color: #d7dee7;
    }

    .cfg-body #body_paragraphs_editor .ql-container.ql-snow,
    .cfg-body #html_editor .ql-container.ql-snow {
        border-radius: 0 0 9px 9px;
        border-color: #d7dee7;
        border-top: 0;
    }

    .cfg-body #body_paragraphs_editor .ql-editor { min-height: 200px; }
    .cfg-body #html_editor .ql-editor { min-height: 260px; background: #f8fafc; }

    /* Botones */
    .cfg-shell .btn-primary {
        background: #111827;
        border-color: #111827;
        border-radius: 9px;
        font-weight: 600;
        font-size: .82rem;
        padding: 8px 18px;
    }

    .cfg-shell .btn-primary:hover { background: #1f2937; border-color: #1f2937; }

    .cfg-shell .btn-light {
        border-radius: 9px;
        font-weight: 600;
        font-size: .82rem;
        border: 1px solid var(--cfg-line);
        background: #fff;
    }

    .cfg-shell .btn-light:hover { background: #f5f7fa; }
    .cfg-shell .btn-success, .cfg-shell .btn-outline-success { border-radius: 9px; font-weight: 600; font-size: .82rem; }
    .cfg-body .alert { border-radius: 10px; font-size: .84rem; }

    /* Panel lateral original: si el JS no corriera, se ve en flujo */
    .cfg-body .fixed-rail {
        position: static;
        width: auto;
        display: block;
        margin-top: 16px;
    }

    .editor-not-found {
        background: #fff5f5;
        border: 1px solid #fecaca;
        color: #991b1b;
        border-radius: 10px;
        padding: 16px 20px;
        font-size: .88rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* ═══ RESPONSIVE ═══════════════════════════════════════════ */
    @media (max-width: 1399.98px) {
        .cfg-shell { --cfg-side-w: 270px; }
    }

    @media (max-width: 1199.98px) {
        .cfg-workarea { grid-template-columns: minmax(0, 1fr); }
        .cfg-side { position: static; max-height: none; }
    }

    @media (max-width: 991.98px) {
        .cfg-layout { grid-template-columns: minmax(0, 1fr); }

        .cfg-nav {
            position: static;
            max-height: none;
        }

        .cfg-navgroup-items {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 4px;
        }

        .cfg-navitem + .cfg-navitem { margin-top: 0; }
        .cfg-pagehead { flex-wrap: wrap; }
        .cfg-actionbar { bottom: 0; border-radius: 12px 12px 0 0; }
        .cfg-status { width: 100%; margin-bottom: 4px; }
    }
</style>

<div class="cfg-shell">

    <!-- ══ ENCABEZADO ══ -->
    <div class="cfg-topbar">
        <div>
            <h1><i class="fa-solid fa-gear me-2" style="color:var(--cfg-accent)"></i>Configuraciones</h1>
            <p>Personaliza los correos y documentos que el sistema genera automáticamente.</p>
        </div>
    </div>

    <div class="cfg-layout">

        <!-- ══ NAVEGACIÓN ══ -->
        <nav class="cfg-nav" aria-label="Secciones de configuración">
            <div class="cfg-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" id="cfgSearch" placeholder="Buscar configuración…"
                    autocomplete="off" aria-label="Buscar configuración">
            </div>

            <?php if ($editor !== 'home'): ?>
                <a class="cfg-nav-home" href="configs">
                    <i class="fa-solid fa-arrow-left"></i> Ver todas las configuraciones
                </a>
            <?php endif; ?>

            <?php foreach ($CFG_GROUPS as $gKey => $group): ?>
                <div class="cfg-navgroup" data-group="<?= htmlspecialchars($gKey) ?>">
                    <div class="cfg-navgroup-title">
                        <i class="fa-solid <?= htmlspecialchars($group['icon']) ?>"></i>
                        <?= htmlspecialchars($group['label']) ?>
                    </div>
                    <div class="cfg-navgroup-items">
                        <?php foreach ($CFG_EDITORS as $key => $meta): ?>
                            <?php if ($meta['group'] !== $gKey) continue; ?>
                            <a class="cfg-navitem tone-<?= htmlspecialchars($meta['tone']) ?> <?= $editor === $key ? 'active' : '' ?>"
                                href="configs&editor=<?= urlencode($key) ?>"
                                <?= $editor === $key ? 'aria-current="page"' : '' ?>
                                data-search="<?= htmlspecialchars(mb_strtolower($meta['title'] . ' ' . $meta['desc'] . ' ' . $meta['kw'] . ' ' . $group['label'])) ?>">
                                <span class="ico"><i class="fa-solid <?= htmlspecialchars($meta['icon']) ?>"></i></span>
                                <span><?= htmlspecialchars($meta['short']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="cfg-nav-empty" id="cfgNavEmpty">
                <i class="fa-regular fa-face-frown d-block mb-1"></i>
                Sin resultados
            </div>
        </nav>

        <!-- ══ CONTENIDO ══ -->
        <div class="cfg-main">

            <?php if ($editor === 'home'): ?>

                <div class="cfg-home-intro">
                    <i class="fa-solid fa-lightbulb"></i>
                    <p>
                        Aquí defines <strong>cómo se ven y qué dicen</strong> los correos y documentos que el sistema
                        envía por ti. Elige abajo lo que quieres modificar; cada apartado se edita de forma visual y
                        siempre puedes revertir los cambios con el botón <strong>Reiniciar</strong>.
                    </p>
                </div>

                <?php foreach ($CFG_GROUPS as $gKey => $group): ?>
                    <section class="cfg-home-group">
                        <div class="cfg-home-grouphead">
                            <h3><i class="fa-solid <?= htmlspecialchars($group['icon']) ?> me-2" style="color:var(--cfg-faint)"></i><?= htmlspecialchars($group['label']) ?></h3>
                            <span><?= htmlspecialchars($group['desc']) ?></span>
                        </div>
                        <div class="cfg-cards">
                            <?php foreach ($CFG_EDITORS as $key => $meta): ?>
                                <?php if ($meta['group'] !== $gKey) continue; ?>
                                <a class="cfg-card tone-<?= htmlspecialchars($meta['tone']) ?>" href="configs&editor=<?= urlencode($key) ?>">
                                    <span class="ico"><i class="fa-solid <?= htmlspecialchars($meta['icon']) ?>"></i></span>
                                    <span class="txt">
                                        <span class="ttl"><?= htmlspecialchars($meta['title']) ?></span>
                                        <span class="dsc"><?= htmlspecialchars($meta['desc']) ?></span>
                                    </span>
                                    <i class="fa-solid fa-arrow-right go"></i>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>

            <?php else: ?>
                <?php $meta = $CFG_EDITORS[$editor]; ?>

                <div class="cfg-pagehead tone-<?= htmlspecialchars($meta['tone']) ?>">
                    <span class="ico"><i class="fa-solid <?= htmlspecialchars($meta['icon']) ?>"></i></span>
                    <div class="txt">
                        <div class="cfg-crumb"><?= htmlspecialchars($CFG_GROUPS[$meta['group']]['label']) ?></div>
                        <h2><?= htmlspecialchars($meta['title']) ?></h2>
                        <p><?= htmlspecialchars($meta['desc']) ?></p>
                    </div>
                    <?php if ($hasSidePanel): ?>
                        <button type="button" class="cfg-sidetoggle is-on" id="cfgSideToggle"
                            aria-expanded="true" aria-controls="cfgSide">
                            <i class="fa-solid fa-wand-magic-sparkles"></i>
                            <span>Datos automáticos</span>
                        </button>
                    <?php endif; ?>
                </div>

                <div class="cfg-workarea<?= $hasSidePanel ? '' : ' side-hidden' ?>" id="cfgWorkarea">
                    <div class="cfg-body" id="editor-mount">
                        <?php cfgRenderEditor($editor, $CFG_EDITORS); ?>
                    </div>

                    <?php if ($hasSidePanel): ?>
                        <aside class="cfg-side" id="cfgSide" aria-label="Datos automáticos y ayuda">
                            <div class="cfg-side-head">
                                <i class="fa-solid fa-circle-info"></i>
                                <span>Datos automáticos</span>
                                <button type="button" class="cfg-side-close" id="cfgSideClose" aria-label="Ocultar panel">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                            <div class="cfg-side-body" id="cfgSideBody"></div>
                        </aside>
                    <?php endif; ?>
                </div>

                <div class="cfg-actionbar" id="cfgActionbar">
                    <span class="cfg-status" id="cfgStatus">
                        <span class="dot"></span>
                        <span class="txt">Sin cambios pendientes</span>
                    </span>
                    <div class="cfg-actions-slot" id="cfgActionsSlot"></div>
                </div>

            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    /* ── Buscador de la navegación ───────────────────────────── */
    var search = document.getElementById('cfgSearch');
    if (search) {
        var items = Array.prototype.slice.call(document.querySelectorAll('.cfg-navitem'));
        var groups = Array.prototype.slice.call(document.querySelectorAll('.cfg-navgroup'));
        var emptyMsg = document.getElementById('cfgNavEmpty');

        var normalize = function (s) {
            return (s || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        };

        search.addEventListener('input', function () {
            var q = normalize(search.value.trim());
            var found = 0;

            items.forEach(function (a) {
                var hit = !q || normalize(a.dataset.search).indexOf(q) !== -1;
                a.style.display = hit ? '' : 'none';
                if (hit) found++;
            });

            groups.forEach(function (g) {
                var visible = g.querySelectorAll('.cfg-navitem:not([style*="display: none"])').length;
                g.style.display = visible ? '' : 'none';
            });

            if (emptyMsg) emptyMsg.style.display = found ? 'none' : 'block';
        });

        search.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter') return;
            var first = items.filter(function (a) { return a.style.display !== 'none'; })[0];
            if (first) window.location.href = first.getAttribute('href');
        });
    }

    var mount = document.getElementById('editor-mount');
    if (!mount) return;

    /* ── Reubicación del panel fijo que traen los editores ────
       Cada editor incluye <aside class="fixed-rail"> con:
         · .vars-fixed  → ayuda y variables  → panel lateral
         · .sticky-actions → botones         → barra de acciones
       Se mueven los nodos (conservan sus listeners y sus IDs). */
    var rail = mount.querySelector('.fixed-rail');
    var sideBody = document.getElementById('cfgSideBody');
    var slot = document.getElementById('cfgActionsSlot');
    var workarea = document.getElementById('cfgWorkarea');

    if (rail) {
        var vars = rail.querySelector('.vars-fixed');
        var body = vars ? (vars.querySelector('.card-body') || vars) : null;

        if (body && sideBody) {
            while (body.firstChild) sideBody.appendChild(body.firstChild);
        }

        var actions = rail.querySelector('.sticky-actions');
        if (actions && slot) {
            var btns = Array.prototype.slice.call(actions.children);
            // El botón principal (guardar) queda a la derecha del todo.
            btns.sort(function (a, b) {
                var pa = /btn-primary|btn-success(?!\s*outline)/.test(a.className) ? 1 : 0;
                var pb = /btn-primary|btn-success(?!\s*outline)/.test(b.className) ? 1 : 0;
                return pa - pb;
            });
            btns.forEach(function (b) { slot.appendChild(b); });
        }

        rail.parentNode.removeChild(rail);
    }

    // Sin panel lateral que mostrar → recuperar el ancho completo.
    if (workarea && sideBody && !sideBody.children.length) {
        workarea.classList.add('side-hidden');
        var tgl = document.getElementById('cfgSideToggle');
        if (tgl) tgl.style.display = 'none';
    }

    /* ── Explicación de cada variable como tooltip nativo ─────
       (el atributo `hover` solo se pinta con CSS en un editor;
        el `title` funciona igual dentro del panel con scroll). */
    function titleFromHover(c) { if (c && !c.title) c.title = c.getAttribute('hover') || ''; }

    Array.prototype.forEach.call(
        document.querySelectorAll('.cfg-shell code.k[hover]'), titleFromHover
    );

    // Las variables del editor de correos se crean después: se cubren al pasar el cursor.
    document.addEventListener('mouseover', function (e) {
        if (!e.target || !e.target.closest) return;
        titleFromHover(e.target.closest('code.k[hover]'));
    }, true);

    /* ── Mostrar/ocultar el panel lateral (se recuerda) ───────── */
    var toggle = document.getElementById('cfgSideToggle');
    var closeBtn = document.getElementById('cfgSideClose');
    var KEY = 'cfgSidePanel';

    function setSide(open) {
        if (!workarea) return;
        workarea.classList.toggle('side-hidden', !open);
        if (toggle) {
            toggle.classList.toggle('is-on', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
        try { localStorage.setItem(KEY, open ? '1' : '0'); } catch (e) {}
    }

    if (toggle && workarea && !workarea.classList.contains('side-hidden')) {
        var stored = null;
        try { stored = localStorage.getItem(KEY); } catch (e) {}
        if (stored === '0') setSide(false);

        toggle.addEventListener('click', function () {
            setSide(workarea.classList.contains('side-hidden'));
        });
        if (closeBtn) closeBtn.addEventListener('click', function () { setSide(false); });
    }

    /* ── Barra de acciones + aviso de cambios sin guardar ─────── */
    var bar = document.getElementById('cfgActionbar');
    if (!bar || !slot || !slot.children.length) return;

    bar.classList.add('is-ready');

    var status = document.getElementById('cfgStatus');
    var statusTxt = status ? status.querySelector('.txt') : null;
    var dirty = false;
    var flashTimer = null;
    // Controles que no representan un cambio en la configuración.
    var IGNORE = ['tplSelect', 'testMailTo', 'cfgSearch'];

    function paint() {
        bar.classList.remove('is-ok', 'is-error');
        bar.classList.toggle('is-dirty', dirty);
        if (statusTxt) {
            statusTxt.textContent = dirty
                ? 'Tienes cambios sin guardar'
                : 'Sin cambios pendientes';
        }
    }

    function flash(text, kind, ms) {
        if (statusTxt) statusTxt.textContent = text;
        bar.classList.remove('is-dirty', 'is-ok', 'is-error');
        if (kind) bar.classList.add(kind);
        clearTimeout(flashTimer);
        if (ms) flashTimer = setTimeout(paint, ms);
    }

    function setDirty(v) {
        if (dirty === v) return;
        dirty = v;
        clearTimeout(flashTimer);
        paint();
    }

    function markDirty(e) {
        var t = e.target;
        if (t && t.id && IGNORE.indexOf(t.id) !== -1) return;
        if (t && t.closest && t.closest('.cfg-actionbar')) return;
        setDirty(true);
    }

    // Se ignoran los eventos disparados mientras el editor carga sus datos.
    setTimeout(function () {
        mount.addEventListener('input', markDirty, true);
        mount.addEventListener('change', markDirty, true);
    }, 1200);

    slot.addEventListener('click', function (e) {
        var b = e.target.closest('button, a');
        if (!b) return;
        if (b.id === 'btnSave') flash('Guardando…', null, 0);
        if (b.id === 'btnReload') { dirty = false; flash('Configuración recargada', 'is-ok', 3000); }
    });

    /* El resultado real lo publica cada editor en #alertBox: se refleja
       en la barra para que el aviso quede junto al botón que se pulsó. */
    var alertBox = mount.querySelector('#alertBox') || mount.querySelector('#gs-alert');
    if (alertBox && window.MutationObserver) {
        new MutationObserver(function () {
            if (alertBox.querySelector('.alert-success')) {
                dirty = false;
                flash('Cambios guardados', 'is-ok', 4000);
            } else if (alertBox.querySelector('.alert-danger')) {
                flash('No se pudo guardar. Revisa el aviso de arriba.', 'is-error', 6000);
            }
        }).observe(alertBox, { childList: true, subtree: true });
    }

    window.addEventListener('beforeunload', function (e) {
        if (!dirty) return;
        e.preventDefault();
        e.returnValue = '';
    });
})();
</script>
