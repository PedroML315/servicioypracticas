<?php
/**
 * REGISTRO UNIMO · Piezas compartidas por los formularios públicos de registro
 * ---------------------------------------------------------------------------
 * Lo usan RegisterEmpresas.php, RegisterPracticas.php y RegisterStudent.php.
 * Aquí vive únicamente lo que comparten palabra por palabra, para que un cambio
 * de marca, de buzón de contacto o de documento legal se haga en un solo lugar.
 */

if (!function_exists('rgCorreoArea')) {
    /**
     * Buzón de atención del área, leído de config/general_settings.json para no
     * dejar direcciones a mano regadas por las vistas.
     *
     * @param string $clave 'email_ss' | 'email_pp' | 'email_capacitacion'
     */
    function rgCorreoArea(string $clave, string $fallback = 'serviciosocial@unimontrer.edu.mx'): string
    {
        static $cfg = null;
        if ($cfg === null) {
            $ruta = __DIR__ . '/../../../config/general_settings.json';
            $cfg = is_file($ruta) ? (json_decode((string) file_get_contents($ruta), true) ?: []) : [];
        }
        $valor = trim((string) ($cfg[$clave] ?? ''));
        return $valor !== '' ? $valor : $fallback;
    }
}

if (!function_exists('rgAssets')) {
    /**
     * Hojas de estilo e íconos del sistema visual de registro.
     *
     * Estas vistas se incluyen dentro del <body> de view/dashboard.php, que ya
     * emitió su propio <head> con <base href="/"> y un <title> genérico. Por eso
     * el título de la pestaña se ajusta desde JS en lugar de con una etiqueta
     * <title> suelta, que el navegador ignoraría.
     *
     * Se protege con una bandera estática por si la vista llegara a incluirse
     * más de una vez en la misma respuesta.
     */
    function rgAssets(string $titulo = ''): void
    {
        static $impreso = false;
        if ($impreso) {
            return;
        }
        $impreso = true;
        // ?v= corta la caché del navegador cuando se edite el CSS o el JS.
        $v = '2026.08';
        if ($titulo !== '') {
            echo '<script>document.title = ' . json_encode($titulo, JSON_UNESCAPED_UNICODE) . ';</script>';
        }
        ?>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Work+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
        <link href="view/assets/css/registro-unimo.css?v=<?= $v ?>" rel="stylesheet">
        <script defer src="view/assets/js/registro/registro-core.js?v=<?= $v ?>"></script>
        <?php
    }
}

if (!function_exists('rgTopbar')) {
    /**
     * Barra superior: logo, nombre del trámite y acceso a ayuda.
     *
     * @param string $tramite Texto corto que identifica el trámite.
     * @param string $correo  Buzón de soporte que se muestra en "¿Necesitas ayuda?".
     */
    function rgTopbar(string $tramite, string $correo = ''): void
    {
        $correo = $correo !== '' ? $correo : rgCorreoArea('email_pp');
        ?>
        <div class="rg-topbar" role="banner">
            <div class="rg-topbar__brand">
                <img src="view/assets/images/logo-color.png" alt="Universidad Montrer">
                <span><?= htmlspecialchars($tramite, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <a class="rg-topbar__help" href="mailto:<?= htmlspecialchars($correo, ENT_QUOTES, 'UTF-8') ?>">
                <i class="fa-solid fa-headset" aria-hidden="true"></i>
                <span>¿Necesitas ayuda?</span>
            </a>
        </div>
        <?php
    }
}

if (!function_exists('rgLegalDialog')) {
    /**
     * Visor de un documento legal en PDF con lectura mínima obligatoria.
     * El botón "Acepto" se habilita hasta que transcurren unos segundos, para
     * que aceptar no sea un reflejo automático.
     *
     * @param array{id:string,titulo:string,icono:string,pdf:string,boton:string} $o
     */
    function rgLegalDialog(array $o): void
    {
        $id     = $o['id'];
        $titulo = $o['titulo'];
        $icono  = $o['icono'] ?? 'fa-file-signature';
        $pdf    = $o['pdf'];
        $boton  = $o['boton'] ?? 'Acepto el documento';
        ?>
        <dialog class="rg-dialog" id="dlg-<?= $id ?>" aria-labelledby="dlg-<?= $id ?>-title">
            <div class="rg-dialog__inner">
                <div class="rg-dialog__head">
                    <h2 id="dlg-<?= $id ?>-title">
                        <i class="fa-solid <?= htmlspecialchars($icono, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
                        <?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?>
                    </h2>
                    <button type="button" class="rg-iconbtn" data-close-dialog aria-label="Cerrar el documento">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="rg-dialog__body">
                    <iframe src="<?= htmlspecialchars($pdf, ENT_QUOTES, 'UTF-8') ?>#toolbar=0&navpanes=0&scrollbar=0"
                        title="<?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?>"></iframe>
                </div>
                <div class="rg-dialog__foot">
                    <p class="rg-dialog__timer" id="dlg-<?= $id ?>-timer" role="status">
                        <i class="fa-solid fa-clock" aria-hidden="true"></i> Tómate un momento para leerlo…
                    </p>
                    <div class="rg-actions__group">
                        <a class="rg-btn rg-btn--ghost" href="<?= htmlspecialchars($pdf, ENT_QUOTES, 'UTF-8') ?>"
                            target="_blank" rel="noopener">
                            <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Abrir aparte
                        </a>
                        <button type="button" class="rg-btn rg-btn--primary" id="dlg-<?= $id ?>-accept" disabled>
                            <i class="fa-solid fa-check" aria-hidden="true"></i> <?= htmlspecialchars($boton, ENT_QUOTES, 'UTF-8') ?>
                        </button>
                    </div>
                </div>
            </div>
        </dialog>
        <?php
    }
}

if (!function_exists('rgConsent')) {
    /**
     * Casilla de consentimiento. No se marca a mano: se activa al aceptar el
     * documento dentro del visor, así queda constancia de que se abrió.
     *
     * @param array{id:string,name:string,titulo:string,enlace:string} $o
     */
    function rgConsent(array $o): void
    {
        ?>
        <div class="rg-consent" id="consent-<?= $o['id'] ?>">
            <span class="rg-consent__check" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
            <div class="rg-consent__body">
                <p class="rg-consent__title">
                    <?= $o['titulo'] ?>
                    <button type="button" class="rg-review__edit" id="open-<?= $o['id'] ?>">
                        <i class="fa-solid fa-book-open" aria-hidden="true"></i> <?= htmlspecialchars($o['enlace'], ENT_QUOTES, 'UTF-8') ?>
                    </button>
                </p>
                <p class="rg-consent__status" id="status-<?= $o['id'] ?>">
                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i> Pendiente de lectura
                </p>
                <input type="checkbox" name="<?= htmlspecialchars($o['name'], ENT_QUOTES, 'UTF-8') ?>"
                    id="chk-<?= $o['id'] ?>" value="1" tabindex="-1" aria-hidden="true">
            </div>
        </div>
        <?php
    }
}
