<?php
$editor = $_GET['editor'] ?? 'email';
$allowed = ['email', 'carta', 'constancia', 'carta_practicas', 'carta_aceptacion', 'carta_conclusion', 'general_settings'];
if (!in_array($editor, $allowed, true)) {
    $editor = 'email';
}

function renderEditor(string $which): void
{
    $map = [
        'carta' => 'view/pages/configs/carta-editor.php',
        'constancia' => 'view/pages/configs/constancia-editor.php',
        'carta_practicas' => 'view/pages/configs/carta-practicas-editor.php',
        'carta_aceptacion' => 'view/pages/configs/carta-aceptacion-editor.php',
        'carta_conclusion' => 'view/pages/configs/carta-conclusion-editor.php',
        'general_settings' => 'view/pages/configs/general_settings.php',
        'email' => 'view/pages/configs/mail_templates.php',
    ];
    $file = $map[$which] ?? '';
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
    /* â”€â”€â”€ TOKENS â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    :root {
        --cfg-accent: #22c55e;
        --cfg-card: #ffffff;
        --cfg-card-border: #e2e8f0;
        --cfg-card-radius: 14px;
        --cfg-text: #0f172a;
        --cfg-muted: #64748b;
        --cfg-focus: 0 0 0 3px rgba(34, 197, 94, .22);
        --cfg-rail-w: 290px;
    }

    /* â”€â”€â”€ TABS â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .cfg-tabs-wrap {
        background: #fff;
        border: 1px solid var(--cfg-card-border);
        border-radius: var(--cfg-card-radius);
        padding: 6px 10px;
        margin-bottom: 22px;
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        align-items: center;
        box-shadow: 0 1px 3px rgba(0, 0, 0, .04);
    }

    .cfg-tabs-title {
        font-size: .7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .8px;
        color: #9ca3af;
        padding: 4px 8px;
        margin-right: 4px;
        white-space: nowrap;
    }

    .cfg-tab {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 7px 13px;
        border-radius: 9px;
        text-decoration: none;
        font-size: .8rem;
        font-weight: 600;
        color: #374151;
        background: transparent;
        border: 1px solid transparent;
        transition: background .12s, color .12s, border-color .12s;
        white-space: nowrap;
    }

    .cfg-tab:hover {
        background: #f3f4f6;
        color: #111827;
    }

    .cfg-tab.active {
        background: #111827;
        color: #fff;
        border-color: #111827;
    }

    .cfg-tab .cfg-tab-sub {
        font-size: .68rem;
        font-weight: 400;
        opacity: .7;
    }

    .cfg-sep {
        width: 1px;
        height: 22px;
        background: #e5e7eb;
        margin: 0 4px;
        flex-shrink: 0;
    }

    /* â”€â”€â”€ CONTENT WRAP â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .cfg-body {
        position: relative;
    }

    .cfg-body .has-rail {
        padding-right: calc(var(--cfg-rail-w) + 20px);
    }

    /* â”€â”€â”€ SECTION CARDS â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .cfg-body .form-section,
    .cfg-body .gs-section {
        background: var(--cfg-card);
        border: 1px solid var(--cfg-card-border);
        border-radius: var(--cfg-card-radius);
        padding: 22px 26px;
        margin-bottom: 18px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, .04);
        position: relative;
    }

    .cfg-body .form-section::before,
    .cfg-body .gs-section::before {
        content: '';
        position: absolute;
        top: 16px;
        bottom: 16px;
        left: 0;
        width: 3px;
        border-radius: 0 3px 3px 0;
        background: var(--cfg-accent);
        opacity: .5;
    }

    .cfg-body .form-section h2,
    .cfg-body .gs-section h2 {
        font-size: .75rem;
        font-weight: 700;
        letter-spacing: .6px;
        text-transform: uppercase;
        color: var(--cfg-muted);
        margin: 0 0 16px;
    }

    /* â”€â”€â”€ PAGE TITLES â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .cfg-body .h4 {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--cfg-text);
    }

    /* â”€â”€â”€ FORM CONTROLS â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .cfg-body .form-label {
        font-size: .78rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 4px;
    }

    .cfg-body .form-text,
    .cfg-body .help-hover {
        font-size: .73rem;
        color: var(--cfg-muted);
    }

    .cfg-body .form-control,
    .cfg-body .form-select {
        border-radius: 8px;
        border: 1px solid #d1d5db;
        font-size: .85rem;
        padding: 7px 11px;
        transition: border-color .14s, box-shadow .14s;
    }

    .cfg-body .form-control:focus,
    .cfg-body .form-select:focus {
        border-color: #22c55e;
        box-shadow: var(--cfg-focus);
        outline: none;
    }

    .cfg-body .form-control-color {
        padding: 4px 6px;
        height: 38px;
    }

    /* â”€â”€â”€ VARIABLE CHIPS â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .cfg-body code.k {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        padding: 2px 8px;
        border-radius: 6px;
        font-family: "Fira Code", monospace;
        font-size: .76rem;
        color: #15803d;
        cursor: pointer;
        transition: all .14s;
        display: inline-block;
    }

    .cfg-body code.k:hover {
        background: #22c55e;
        color: #fff;
        border-color: #16a34a;
        transform: translateY(-1px);
    }

    /* â”€â”€â”€ QUILL â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .cfg-body #body_paragraphs_editor .ql-toolbar,
    .cfg-body #html_editor .ql-toolbar {
        border-radius: 8px 8px 0 0;
        border-color: #d1d5db;
    }

    .cfg-body #body_paragraphs_editor .ql-container.ql-snow,
    .cfg-body #html_editor .ql-container.ql-snow {
        border-radius: 0 0 8px 8px;
        border-color: #d1d5db;
        border-top: 0;
    }

    .cfg-body #body_paragraphs_editor .ql-editor {
        min-height: 200px;
    }

    .cfg-body #html_editor .ql-editor {
        min-height: 260px;
        background: #f8fafc;
    }

    /* â”€â”€â”€ FIXED RAIL â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .cfg-body .fixed-rail {
        position: fixed;
        right: 20px;
        top: 80px;
        bottom: 20px;
        width: var(--cfg-rail-w);
        display: flex;
        flex-direction: column;
        gap: 10px;
        z-index: 50;
    }

    .cfg-body .fixed-rail .vars-fixed {
        flex: 1 1 auto;
        overflow: auto;
        background: var(--cfg-card);
        border: 1px solid var(--cfg-card-border);
        border-radius: var(--cfg-card-radius);
        box-shadow: 0 1px 4px rgba(0, 0, 0, .05);
    }

    .cfg-body .fixed-rail .sticky-actions {
        background: var(--cfg-card);
        border: 1px solid var(--cfg-card-border);
        border-radius: var(--cfg-card-radius);
        padding: 13px 15px;
        box-shadow: 0 4px 14px rgba(0, 0, 0, .07);
    }

    /* â”€â”€â”€ BUTTONS â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .cfg-body .btn-primary {
        background: #111827;
        border-color: #111827;
        border-radius: 8px;
        font-weight: 600;
        font-size: .84rem;
        padding: 8px 18px;
        transition: background .14s, transform .1s;
    }

    .cfg-body .btn-primary:hover {
        background: #1f2937;
        transform: translateY(-1px);
    }

    .cfg-body .btn-light {
        border-radius: 8px;
        font-weight: 600;
        font-size: .84rem;
    }

    .cfg-body .alert {
        border-radius: 9px;
        font-size: .85rem;
    }

    /* â”€â”€â”€ ERROR â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
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

    /* â”€â”€â”€ RESPONSIVE â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    @media (max-width: 991.98px) {
        .cfg-body .has-rail {
            padding-right: 0;
            padding-bottom: 46vh;
        }

        .cfg-body .fixed-rail {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            top: auto;
            width: 100%;
            height: 44vh;
            border-radius: 16px 16px 0 0;
            background: #fff;
            padding: 10px 14px;
            box-shadow: 0 -4px 24px rgba(0, 0, 0, .1);
        }

        .cfg-tabs-wrap {
            overflow-x: auto;
            flex-wrap: nowrap;
        }
    }
</style>

<!-- â”€â”€ TABS â”€â”€ -->
<div class="cfg-tabs-wrap" role="navigation" aria-label="Editores de configuración">
    <span class="cfg-tabs-title"><i class="fa-solid fa-screwdriver-wrench me-1"></i>Editores</span>
    <a class="cfg-tab <?= $editor === 'email' ? 'active' : '' ?>" href="configs&editor=email">
        <i class="fa-solid fa-envelope"></i> Correos
    </a>
    <div class="cfg-sep"></div>
    <a class="cfg-tab <?= $editor === 'carta' ? 'active' : '' ?>" href="configs&editor=carta">
        <i class="fa-solid fa-file-lines"></i> Carta presentación
        <span class="cfg-tab-sub">SS</span>
    </a>
    <a class="cfg-tab <?= $editor === 'carta_aceptacion' ? 'active' : '' ?>" href="configs&editor=carta_aceptacion">
        <i class="fa-solid fa-file-circle-check"></i> Carta aceptación
        <span class="cfg-tab-sub">SS Interno</span>
    </a>
    <a class="cfg-tab <?= $editor === 'carta_conclusion' ? 'active' : '' ?>" href="configs&editor=carta_conclusion">
        <i class="fa-solid fa-file-certificate"></i> Carta conclusión
        <span class="cfg-tab-sub">SS Interno</span>
    </a>
    <div class="cfg-sep"></div>
    <a class="cfg-tab <?= $editor === 'constancia' ? 'active' : '' ?>" href="configs&editor=constancia">
        <i class="fa-solid fa-award"></i> Constancia
        <span class="cfg-tab-sub">Prácticas</span>
    </a>
    <a class="cfg-tab <?= $editor === 'carta_practicas' ? 'active' : '' ?>" href="configs&editor=carta_practicas">
        <i class="fa-solid fa-briefcase"></i> Carta prácticas
        <span class="cfg-tab-sub">Prácticas</span>
    </a>
    <div class="cfg-sep"></div>
    <a class="cfg-tab <?= $editor === 'general_settings' ? 'active' : '' ?>" href="configs&editor=general_settings">
        <i class="fa-solid fa-sliders"></i> General
    </a>
</div>

<!-- â”€â”€ EDITOR CONTENT â”€â”€ -->
<div class="cfg-body" id="editor-mount">
    <?php renderEditor($editor); ?>
</div>