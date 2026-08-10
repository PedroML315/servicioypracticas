<?php
/**
 * Registro público de alumnos en Prácticas Profesionales · Universidad Montrer
 * ---------------------------------------------------------------------------
 * Ruta pública: /inscripcionPracticas   (config/whiteList.php)
 * Endpoints:    controller/ajax/ajax.forms.php
 *                 · action=checkMatricula            → consulta a GES
 *                 · action=registerStudentPracticas  → alta del alumno
 *
 * Las claves del POST son contrato con ajax.forms.php: no cambiarlas sin
 * ajustar también el arreglo $data de ese archivo.
 *
 * Sistema visual: view/assets/css/registro-unimo.css
 * Motor multipaso: view/assets/js/registro/registro-core.js
 */

require_once __DIR__ . '/../partials/registro-ui.php';

$rgCorreo = rgCorreoArea('email_pp');
require_once __DIR__ . '/../../../controller/forms.controller.php';

$degrees = FormsController::ctrSearchDegrees(null);

$rgPdfPrivacidad = 'docs/' . rawurlencode('Términos y condiciones Pp (Alumno).pdf');
$rgPdfReglamento = 'docs/' . rawurlencode('REGLAMENTO PRÁCTICAS PROFESIONALES.pdf');

$rgPasos = [
    ['titulo' => 'Identifícate',      'meta' => 'Con tu matrícula'],
    ['titulo' => 'Tus datos',         'meta' => 'Verifica que estén bien'],
    ['titulo' => 'Contacto y modalidad', 'meta' => 'Dónde harás prácticas'],
    ['titulo' => 'Revisión y envío',  'meta' => 'Confirma y acepta'],
];
?>
<?php rgAssets('Registro de Prácticas Profesionales – Universidad Montrer'); ?>

<div class="rg">
    <div class="rg-page">

        <?php rgTopbar('Prácticas Profesionales', $rgCorreo); ?>

        <div class="rg-mobar" id="rgMobar">
            <div class="rg-mobar__row">
                <span class="rg-mobar__step">Identifícate</span>
                <span class="rg-mobar__count">Paso 1 de <?= count($rgPasos) ?></span>
            </div>
            <div class="rg-mobar__track" role="progressbar" aria-label="Avance del registro" aria-valuemin="1"
                aria-valuemax="<?= count($rgPasos) ?>" aria-valuenow="1">
                <span class="rg-mobar__fill"></span>
            </div>
        </div>

        <div class="rg-shell">

            <div class="rg-intro">
                <p class="rg-intro__eyebrow"><i class="fa-solid fa-graduation-cap" aria-hidden="true"></i> Alumnos</p>
                <h1>Inscríbete a Prácticas Profesionales</h1>
                <p>
                    Empieza con tu matrícula: traemos tus datos de Control Escolar y sólo tienes que
                    revisarlos. Son <strong>4 pasos</strong> y toma menos de <strong>5 minutos</strong>.
                </p>
            </div>

            <nav class="rg-rail" id="rgRail" aria-label="Avance del registro">
                <p class="rg-rail__title">Tu avance</p>
                <ol class="rg-rail__list">
                    <?php foreach ($rgPasos as $i => $p): ?>
                        <li>
                            <button type="button" class="rg-rail__step<?= $i === 0 ? ' is-active' : '' ?>"
                                data-step="<?= $i ?>" data-clickable="0" disabled>
                                <span class="rg-rail__disc"><?= $i + 1 ?></span>
                                <span class="rg-rail__label">
                                    <?= htmlspecialchars($p['titulo'], ENT_QUOTES, 'UTF-8') ?>
                                    <span class="rg-rail__meta"><?= htmlspecialchars($p['meta'], ENT_QUOTES, 'UTF-8') ?></span>
                                </span>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ol>
                <p class="rg-rail__foot">
                    <i class="fa-solid fa-lock" aria-hidden="true"></i>
                    Conexión segura · Datos protegidos por la LFPDPPP
                </p>
            </nav>

            <div class="rg-card">
                <div id="rgAlert" hidden></div>

                <form id="rgFormAlumno" novalidate autocomplete="on">

                    <!-- ═══════════ PASO 1 · Identifícate ═══════════ -->
                    <section class="rg-step is-active" data-title="Identifícate" id="rgStep0">
                        <div class="rg-step__head">
                            <p class="rg-step__kicker">Paso 1 de <?= count($rgPasos) ?></p>
                            <h2>Empecemos con tu matrícula</h2>
                            <p>Es el número que usas para entrar a la plataforma escolar. Con él traemos
                                tu nombre, tu programa y tu grupo automáticamente.</p>
                        </div>

                        <div class="rg-step__body">
                            <div class="rg-grid">
                                <div class="rg-field rg-c6">
                                    <label class="rg-label" for="matricula">Matrícula <span class="rg-req" aria-hidden="true">*</span></label>
                                    <span class="rg-control">
                                        <i class="fa-solid fa-id-card rg-control__icon" aria-hidden="true"></i>
                                        <input class="rg-input" type="text" id="matricula" name="matricula" required
                                            inputmode="numeric" maxlength="10" data-rule="matricula" data-mask="digits"
                                            placeholder="Ej. 46684" autocomplete="off"
                                            aria-describedby="matriculaEstado"
                                            data-review data-label="Matrícula">
                                        <span class="rg-control__state" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                                    </span>
                                    <p class="rg-hint">Sólo números, sin letras ni espacios.</p>
                                    <p class="rg-error" role="alert" hidden></p>
                                </div>
                            </div>

                            <!-- Resultado de la búsqueda en Control Escolar -->
                            <div id="matriculaEstado" role="status" aria-live="polite" style="margin-top:1.25rem">
                                <div class="rg-note">
                                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                                    <div>Escribe tu matrícula y espera un momento: la buscaremos en Control Escolar.</div>
                                </div>
                            </div>

                            <div class="rg-note rg-note--info" style="margin-top:1.25rem">
                                <i class="fa-solid fa-circle-question" aria-hidden="true"></i>
                                <div>
                                    <strong>¿No aparece tu matrícula?</strong> Puede ser que tengas un adeudo o que tu
                                    inscripción del periodo aún no esté cargada. Escribe a
                                    <a href="mailto:<?= $rgCorreo ?>"><?= $rgCorreo ?></a>
                                    con tu nombre completo y tu matrícula.
                                </div>
                            </div>
                        </div>

                        <div class="rg-actions">
                            <p class="rg-actions__hint">Necesitamos encontrarte para continuar.</p>
                            <div class="rg-actions__group">
                                <button type="button" class="rg-btn rg-btn--primary rg-next" id="btnPaso0" disabled>
                                    Continuar <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </section>

                    <!-- ═══════════ PASO 2 · Tus datos ═══════════ -->
                    <section class="rg-step" data-title="Tus datos" id="rgStep1">
                        <div class="rg-step__head">
                            <p class="rg-step__kicker">Paso 2 de <?= count($rgPasos) ?></p>
                            <h2>Verifica tus datos</h2>
                            <p>Los trajimos de Control Escolar. Si algo está mal escrito, corrígelo aquí:
                                así aparecerá en tu carta de presentación y en tu constancia.</p>
                        </div>

                        <div class="rg-step__body">
                            <div class="rg-note rg-note--info" style="margin-bottom:1.5rem">
                                <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>
                                <div>Los campos con fondo verde se llenaron solos. Revísalos con calma —
                                    <strong>tú eres responsable de que estén correctos</strong>.</div>
                            </div>

                            <div class="rg-grid">
                                <div class="rg-field rg-c8">
                                    <label class="rg-label" for="nombre">Nombre completo <span class="rg-req" aria-hidden="true">*</span></label>
                                    <span class="rg-control">
                                        <i class="fa-solid fa-user rg-control__icon" aria-hidden="true"></i>
                                        <input class="rg-input" type="text" id="nombre" name="nombre" required
                                            minlength="5" maxlength="150" autocomplete="name"
                                            placeholder="Nombre(s) Apellido paterno Apellido materno"
                                            data-review data-label="Nombre completo">
                                        <span class="rg-control__state" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                                    </span>
                                    <p class="rg-error" role="alert" hidden></p>
                                </div>

                                <div class="rg-field rg-c4">
                                    <label class="rg-label" for="curp">CURP <span class="rg-req" aria-hidden="true">*</span></label>
                                    <span class="rg-control">
                                        <i class="fa-solid fa-fingerprint rg-control__icon" aria-hidden="true"></i>
                                        <input class="rg-input" type="text" id="curp" name="curp" required
                                            maxlength="18" data-rule="curp" style="text-transform:uppercase"
                                            placeholder="RUSF010203MMNZLR09"
                                            data-review data-label="CURP">
                                        <span class="rg-control__state" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                                    </span>
                                    <p class="rg-hint">18 caracteres, como aparece en tu acta.</p>
                                    <p class="rg-error" role="alert" hidden></p>
                                </div>

                                <div class="rg-field rg-c4">
                                    <label class="rg-label" for="nacimiento">Fecha de nacimiento <span class="rg-req" aria-hidden="true">*</span></label>
                                    <span class="rg-control">
                                        <i class="fa-solid fa-cake-candles rg-control__icon" aria-hidden="true"></i>
                                        <input class="rg-input" type="date" id="nacimiento" name="nacimiento" required
                                            data-rule="edad" data-review data-label="Fecha de nacimiento">
                                        <span class="rg-control__state" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                                    </span>
                                    <p class="rg-error" role="alert" hidden></p>
                                </div>

                                <div class="rg-field rg-c4">
                                    <label class="rg-label" for="genero">Género <span class="rg-req" aria-hidden="true">*</span></label>
                                    <span class="rg-control">
                                        <i class="fa-solid fa-venus-mars rg-control__icon" aria-hidden="true"></i>
                                        <select class="rg-select" id="genero" name="genero" required
                                            data-review data-label="Género">
                                            <option value="">Selecciona…</option>
                                            <option value="Femenino">Femenino</option>
                                            <option value="Masculino">Masculino</option>
                                            <option value="Otro">Otro</option>
                                        </select>
                                    </span>
                                    <p class="rg-error" role="alert" hidden></p>
                                </div>

                                <div class="rg-field rg-c4">
                                    <label class="rg-label" for="grupo">Grupo <span class="rg-req" aria-hidden="true">*</span></label>
                                    <span class="rg-control">
                                        <i class="fa-solid fa-users-rectangle rg-control__icon" aria-hidden="true"></i>
                                        <input class="rg-input" type="text" id="grupo" name="grupo" required
                                            maxlength="30" placeholder="Ej. 8ADMON"
                                            data-review data-label="Grupo">
                                        <span class="rg-control__state" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                                    </span>
                                    <p class="rg-error" role="alert" hidden></p>
                                </div>

                                <div class="rg-field rg-c8">
                                    <label class="rg-label" for="programa">Programa académico <span class="rg-req" aria-hidden="true">*</span></label>
                                    <span class="rg-control">
                                        <i class="fa-solid fa-book-open-reader rg-control__icon" aria-hidden="true"></i>
                                        <select class="rg-select" id="programa" name="programa" required
                                            data-review data-label="Programa académico">
                                            <option value="">Selecciona tu programa…</option>
                                            <?php foreach ($degrees as $degree): ?>
                                                <option value="<?= htmlspecialchars($degree['nameDegree'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= htmlspecialchars($degree['nameDegree'], ENT_QUOTES, 'UTF-8') ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </span>
                                    <p class="rg-error" role="alert" hidden></p>
                                </div>
                            </div>

                            <input type="hidden" id="periodo" name="periodo">
                        </div>

                        <div class="rg-actions">
                            <p class="rg-actions__hint"><span class="rg-req" aria-hidden="true">*</span> Campos obligatorios</p>
                            <div class="rg-actions__group">
                                <button type="button" class="rg-btn rg-btn--ghost rg-prev">
                                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Atrás
                                </button>
                                <button type="button" class="rg-btn rg-btn--primary rg-next">
                                    Continuar <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </section>

                    <!-- ═══════════ PASO 3 · Contacto y modalidad ═══════════ -->
                    <section class="rg-step" data-title="Contacto y modalidad" id="rgStep2">
                        <div class="rg-step__head">
                            <p class="rg-step__kicker">Paso 3 de <?= count($rgPasos) ?></p>
                            <h2>¿Cómo te localizamos y dónde practicarás?</h2>
                            <p>A este correo llegarán tus accesos, tu carta de presentación y los avisos
                                del área. Revisa que sea uno que consultes seguido.</p>
                        </div>

                        <div class="rg-step__body">
                            <div class="rg-section">
                                <p class="rg-section__title"><i class="fa-solid fa-address-book" aria-hidden="true"></i> Datos de contacto</p>
                                <div class="rg-grid">
                                    <div class="rg-field rg-c6">
                                        <label class="rg-label" for="email">Correo electrónico <span class="rg-req" aria-hidden="true">*</span></label>
                                        <span class="rg-control">
                                            <i class="fa-solid fa-envelope rg-control__icon" aria-hidden="true"></i>
                                            <input class="rg-input" type="email" id="email" name="email" required
                                                maxlength="100" autocomplete="email"
                                                placeholder="tumatricula@unimontrer.edu.mx"
                                                data-review data-label="Correo electrónico">
                                            <span class="rg-control__state" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                                        </span>
                                        <p class="rg-hint">Preferentemente tu correo institucional.</p>
                                        <p class="rg-error" role="alert" hidden></p>
                                    </div>

                                    <div class="rg-field rg-c6">
                                        <label class="rg-label" for="telefono">Teléfono celular <span class="rg-req" aria-hidden="true">*</span></label>
                                        <span class="rg-control">
                                            <i class="fa-solid fa-mobile-screen rg-control__icon" aria-hidden="true"></i>
                                            <input class="rg-input" type="tel" id="telefono" name="telefono" required
                                                maxlength="10" inputmode="numeric" data-rule="tel" data-mask="digits"
                                                autocomplete="tel" placeholder="10 dígitos"
                                                data-review data-label="Teléfono celular">
                                            <span class="rg-control__state" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                                        </span>
                                        <p class="rg-error" role="alert" hidden></p>
                                    </div>
                                </div>
                            </div>

                            <div class="rg-section">
                                <p class="rg-section__title"><i class="fa-solid fa-route" aria-hidden="true"></i> Modalidad de prácticas</p>
                                <div class="rg-field">
                                    <div class="rg-choices">
                                        <label class="rg-choice" for="opcionUniv">
                                            <input type="radio" name="tipoPractica" id="opcionUniv" value="universidad"
                                                required data-review data-label="Modalidad"
                                                data-msg-required="Elige una modalidad para continuar.">
                                            <span class="rg-choice__mark" aria-hidden="true"></span>
                                            <span>
                                                <i class="fa-solid fa-school rg-choice__icon" aria-hidden="true"></i>
                                                <span class="rg-choice__title">Directamente con la Universidad</span>
                                                <span class="rg-choice__desc">Te postulas a las vacantes publicadas de areas internas de la universidad.</span>
                                            </span>
                                        </label>

                                        <label class="rg-choice" for="opcionEmp">
                                            <input type="radio" name="tipoPractica" id="opcionEmp" value="empresa"
                                                required data-review data-label="Modalidad">
                                            <span class="rg-choice__mark" aria-hidden="true"></span>
                                            <span>
                                                <i class="fa-solid fa-building rg-choice__icon" aria-hidden="true"></i>
                                                <span class="rg-choice__title">Desde un organismo receptor</span>
                                                <span class="rg-choice__desc">Realizarás tus prácticas en una
                                                    organización externa a la universidad.</span>
                                            </span>
                                        </label>
                                    </div>
                                    <p class="rg-error" role="alert" hidden></p>
                                </div>

                                <div class="rg-note rg-note--warn" style="margin-top:1rem" id="avisoEmpresa" hidden>
                                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                                    <div>
                                        <strong>Ojo:</strong> ese organismo debe registrarse y firmar convenio con la
                                        Universidad antes de que puedas iniciar. Compártele la liga
                                        <a href="inscripcionEmpresas" target="_blank" rel="noopener">de registro para
                                        organismos receptores</a>.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rg-actions">
                            <p class="rg-actions__hint"><span class="rg-req" aria-hidden="true">*</span> Campos obligatorios</p>
                            <div class="rg-actions__group">
                                <button type="button" class="rg-btn rg-btn--ghost rg-prev">
                                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Atrás
                                </button>
                                <button type="button" class="rg-btn rg-btn--primary rg-next">
                                    Revisar registro <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </section>

                    <!-- ═══════════ PASO 4 · Revisión y envío ═══════════ -->
                    <section class="rg-step" data-title="Revisión y envío" id="rgStep3">
                        <div class="rg-step__head">
                            <p class="rg-step__kicker">Paso 4 de <?= count($rgPasos) ?></p>
                            <h2>Revisa antes de enviar</h2>
                            <p>Estos datos se imprimirán en tus documentos oficiales. Usa <em>Editar</em>
                                si necesitas corregir algo.</p>
                        </div>

                        <div class="rg-step__body">
                            <div class="rg-review" id="rgReview"></div>

                            <div class="rg-section">
                                <p class="rg-section__title"><i class="fa-solid fa-file-signature" aria-hidden="true"></i> Documentos que debes aceptar</p>
                                <div class="rg-legal">
                                    <?php
                                    rgConsent([
                                        'id'     => 'privacidad',
                                        'name'   => 'aceptoTerminos',
                                        'titulo' => 'He leído y acepto el <strong>Aviso de privacidad</strong> para alumnos.',
                                        'enlace' => 'Leer el aviso',
                                    ]);
                                    rgConsent([
                                        'id'     => 'reglamento',
                                        'name'   => 'aceptoReglamento',
                                        'titulo' => 'He leído y acepto el <strong>Reglamento de Prácticas Profesionales</strong>.',
                                        'enlace' => 'Leer el reglamento',
                                    ]);
                                    ?>
                                </div>
                                <p class="rg-error" id="rgLegalError" role="alert" hidden></p>
                            </div>
                        </div>

                        <div class="rg-actions">
                            <p class="rg-actions__hint">Al enviar, tu solicitud pasa a revisión del área de Prácticas Profesionales.</p>
                            <div class="rg-actions__group">
                                <button type="button" class="rg-btn rg-btn--ghost rg-prev">
                                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Atrás
                                </button>
                                <button type="submit" class="rg-btn rg-btn--primary" id="rgSubmit">
                                    <i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Enviar registro
                                </button>
                            </div>
                        </div>
                    </section>
                </form>

                <!-- ═══════════ Confirmación ═══════════ -->
                <section class="rg-done" id="rgDone" hidden>
                    <div class="rg-done__ring">
                        <svg viewBox="0 0 52 52" aria-hidden="true">
                            <path d="M14 27l8 8 16-17" />
                        </svg>
                    </div>
                    <h2>¡Listo, <span id="rgDoneNombre">tu registro</span>!</h2>
                    <p id="rgDoneMsg">
                        Tu solicitud quedó registrada. El área de Prácticas Profesionales la revisará y te
                        enviará tus accesos por correo.
                    </p>
                    <ol class="rg-timeline">
                        <li class="is-now">
                            <span class="rg-timeline__dot"><i class="fa-solid fa-check" aria-hidden="true"></i></span>
                            <div>
                                <p class="rg-timeline__title">Solicitud enviada</p>
                                <p class="rg-timeline__desc">Ya está en la bandeja del área de Prácticas Profesionales.</p>
                            </div>
                        </li>
                        <li>
                            <span class="rg-timeline__dot">2</span>
                            <div>
                                <p class="rg-timeline__title">Revisión de tu expediente</p>
                                <p class="rg-timeline__desc">Se valida que cumplas los requisitos del periodo.</p>
                            </div>
                        </li>
                        <li>
                            <span class="rg-timeline__dot">3</span>
                            <div>
                                <p class="rg-timeline__title">Recibes tus accesos</p>
                                <p class="rg-timeline__desc">Llegarán a <strong id="rgDoneCorreo">tu correo</strong> con
                                    tu contraseña temporal.</p>
                            </div>
                        </li>
                        <li>
                            <span class="rg-timeline__dot">4</span>
                            <div>
                                <p class="rg-timeline__title">Eliges tu organismo receptor</p>
                                <p class="rg-timeline__desc">Desde la plataforma te postulas a las vacantes disponibles
                                    y das seguimiento a tus horas.</p>
                            </div>
                        </li>
                    </ol>
                    <div style="margin-top:2rem">
                        <a class="rg-btn rg-btn--primary" href="login">
                            <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i> Ir a iniciar sesión
                        </a>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <?php
    rgLegalDialog([
        'id'     => 'privacidad',
        'titulo' => 'Aviso de privacidad · Alumnos',
        'icono'  => 'fa-user-shield',
        'pdf'    => $rgPdfPrivacidad,
        'boton'  => 'Acepto el aviso',
    ]);
    rgLegalDialog([
        'id'     => 'reglamento',
        'titulo' => 'Reglamento de Prácticas Profesionales',
        'icono'  => 'fa-file-contract',
        'pdf'    => $rgPdfReglamento,
        'boton'  => 'Acepto el reglamento',
    ]);
    ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        var form = document.getElementById('rgFormAlumno');
        var alertHost = document.getElementById('rgAlert');
        var estadoBox = document.getElementById('matriculaEstado');
        var btnPaso0 = document.getElementById('btnPaso0');
        var PASO_REVISION = 3;

        var GENEROS = { F: 'Femenino', M: 'Masculino', O: 'Otro' };
        var alumnoEncontrado = false;
        var ultimaBusqueda = '';

        /* ── Estados de la búsqueda de matrícula ──────────────────────────── */

        function estado(tipo, titulo, texto) {
            var iconos = {
                buscando: 'fa-spinner fa-spin', ok: 'fa-circle-check',
                error: 'fa-circle-exclamation', aviso: 'fa-triangle-exclamation',
                idle: 'fa-magnifying-glass'
            };
            var clases = { ok: 'rg-note--ok', error: 'rg-note--error', aviso: 'rg-note--warn', buscando: '', idle: '' };
            estadoBox.innerHTML =
                '<div class="rg-note ' + (clases[tipo] || '') + '">' +
                '<i class="fa-solid ' + iconos[tipo] + '" aria-hidden="true"></i>' +
                '<div>' + (titulo ? '<strong>' + titulo + '</strong><br>' : '') + texto + '</div>' +
                '</div>';
        }

        /** Tarjeta de identidad: confirma visualmente a quién encontramos. */
        function estadoAlumno(nombre, programa, grupo) {
            estadoBox.innerHTML =
                '<div class="rg-note rg-note--ok">' +
                '<i class="fa-solid fa-circle-check" aria-hidden="true"></i>' +
                '<div>' +
                '<strong>Te encontramos: ' + nombre + '</strong><br>' +
                (programa ? programa : 'Programa por confirmar') + (grupo ? ' · Grupo ' + grupo : '') +
                '<br><span style="font-size:.84rem">¿No eres tú? Revisa el número de tu matrícula.</span>' +
                '</div></div>';
        }

        function bloquear() {
            alumnoEncontrado = false;
            btnPaso0.disabled = true;
        }

        function desbloquear() {
            alumnoEncontrado = true;
            btnPaso0.disabled = false;
        }

        /* Normaliza el nombre del programa al catálogo de la plataforma
           (mayúsculas y sin acentos), como lo espera FormsModel. */
        function normalizaPrograma(texto) {
            return (texto || '').toUpperCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/Ñ/g, 'N').replace(/ñ/g, 'N');
        }

        function volcarDatos(s, a) {
            a = a || {};
            document.getElementById('grupo').value = s.CODIGO_GRUPO || '';
            document.getElementById('nombre').value = [s.NOMBRE, s.MATERNO, s.PATERNO].filter(Boolean).join(' ');
            document.getElementById('curp').value = (s.CURP || a.claveCiudadano || '').toUpperCase();
            document.getElementById('nacimiento').value = s.fecha_nacimiento || (a.fechaNacimiento || '').split(' ')[0] || '';
            document.getElementById('genero').value = GENEROS[s.genero] || GENEROS[a.genero] || '';
            document.getElementById('email').value = s.correo_institucional || (a.matricula ? a.matricula + '@unimontrer.edu.mx' : '');
            document.getElementById('telefono').value = s.telefono || a.telefono || '';

            var programa = '';
            if (s.CODIGO_GRUPO && s.CODIGO_GRUPO.indexOf('FISIO') !== -1) programa = 'FISIOTERAPIA';
            else if (a.nameOferta) programa = normalizaPrograma(a.nameOferta);

            var sel = document.getElementById('programa');
            if (programa) {
                if (!sel.querySelector('option[value="' + programa.replace(/"/g, '\\"') + '"]')) {
                    sel.appendChild(new Option(programa, programa));
                }
                sel.value = programa;
            }

            if (s.CODIGO_GRUPO) {
                var m = s.CODIGO_GRUPO.match(/(\d+)/);
                if (m) document.getElementById('periodo').value = m[1];
            }

            // GES devuelve el teléfono con formato ("(443) 123-4567"): asignar el
            // valor por JS no dispara input ni respeta maxlength, así que la
            // máscara se reaplica a mano.
            RG.applyMasks(form);

            // Se marcan los campos autocompletados para que se distingan a simple vista
            ['grupo', 'nombre', 'curp', 'nacimiento', 'genero', 'email', 'telefono', 'programa']
                .forEach(function (id) {
                    var el = document.getElementById(id);
                    var campo = el.closest('.rg-field');
                    if (campo && (el.value || '').trim()) campo.classList.add('is-prefilled');
                });

            return {
                nombre: [s.NOMBRE, s.PATERNO, s.MATERNO].filter(Boolean).join(' '),
                programa: programa,
                grupo: s.CODIGO_GRUPO || ''
            };
        }

        /* ── Búsqueda en Control Escolar ──────────────────────────────────── */

        var input = document.getElementById('matricula');

        var buscar = RG.debounce(function () {
            var val = input.value.trim();
            if (val === ultimaBusqueda) return;
            ultimaBusqueda = val;
            bloquear();

            if (!val) {
                estado('idle', '', 'Escribe tu matrícula y espera un momento: la buscaremos en Control Escolar.');
                return;
            }
            if (!/^\d{4,10}$/.test(val)) {
                estado('error', 'Matrícula no válida', 'La matrícula sólo lleva números. Revisa que no tenga espacios ni letras.');
                return;
            }

            estado('buscando', '', 'Buscando la matrícula <strong>' + val + '</strong> en Control Escolar…');

            fetch('controller/ajax/ajax.forms.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'checkMatricula', matricula: val })
            })
                .then(function (r) { return r.json(); })
                .then(function (resp) {
                    var a = resp.academic || {};

                    // Los programas trimestrales no son elegibles para prácticas
                    if (a.nombre_periodo === 'Trimestral' || a.nombre_periodo === 'Trimestre') {
                        estado('aviso', 'Tu programa no aplica a este trámite',
                            'Los programas de periodo trimestral no realizan prácticas profesionales por esta vía. ' +
                            'Consulta con tu coordinación académica.');
                        return;
                    }

                    if (resp.student && resp.student.id) {
                        var datos = volcarDatos(resp.student, a);
                        estadoAlumno(datos.nombre, datos.programa, datos.grupo);
                        RG.validateField(input);
                        desbloquear();
                        return;
                    }

                    estado('error', 'No encontramos esa matrícula',
                        'Verifica el número. Si estás seguro de que es correcto, escríbenos a ' +
                        '<a href="mailto:<?= $rgCorreo ?>"><?= $rgCorreo ?></a>.');
                })
                .catch(function () {
                    estado('error', 'No pudimos consultar Control Escolar',
                        'Revisa tu conexión e inténtalo otra vez en un momento.');
                });
        }, 700);

        // El campo ya trae data-mask="digits": el núcleo lo limpia antes de esto.
        input.addEventListener('input', buscar);

        /* ── Motor multipaso ──────────────────────────────────────────────── */
        var wizard = RG.wizard({
            root: form,
            rail: document.getElementById('rgRail'),
            mobar: document.getElementById('rgMobar'),
            onEnter: function (i) {
                if (i === PASO_REVISION) {
                    RG.buildReview({
                        target: document.getElementById('rgReview'),
                        steps: wizard.steps,
                        upTo: PASO_REVISION,
                        onEdit: function (idx) { wizard.go(idx); }
                    });
                }
            },
            canLeave: function (i) {
                if (i !== 0) return true;
                if (alumnoEncontrado) return true;
                estadoBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }
        });

        /* La CURP siempre en mayúsculas, sin que el usuario tenga que pensarlo */
        document.getElementById('curp').addEventListener('input', function () {
            var pos = this.selectionStart;
            this.value = this.value.toUpperCase();
            try { this.setSelectionRange(pos, pos); } catch (e) { }
        });

        /* Aviso contextual al elegir "ya tengo organismo receptor" */
        RG.$$('input[name="tipoPractica"]', form).forEach(function (r) {
            r.addEventListener('change', function () {
                document.getElementById('avisoEmpresa').hidden = (r.value !== 'empresa');
            });
        });

        /* ── Documentos legales ───────────────────────────────────────────── */
        var legales = [
            RG.legalDoc({
                dialog: document.getElementById('dlg-privacidad'),
                opener: document.getElementById('open-privacidad'),
                accept: document.getElementById('dlg-privacidad-accept'),
                timer: document.getElementById('dlg-privacidad-timer'),
                checkbox: document.getElementById('chk-privacidad'),
                consent: document.getElementById('consent-privacidad'),
                acceptedText: 'Aviso de privacidad aceptado'
            }),
            RG.legalDoc({
                dialog: document.getElementById('dlg-reglamento'),
                opener: document.getElementById('open-reglamento'),
                accept: document.getElementById('dlg-reglamento-accept'),
                timer: document.getElementById('dlg-reglamento-timer'),
                checkbox: document.getElementById('chk-reglamento'),
                consent: document.getElementById('consent-reglamento'),
                acceptedText: 'Reglamento aceptado'
            })
        ];

        /* ── Envío ────────────────────────────────────────────────────────── */
        form.addEventListener('submit', function (ev) {
            ev.preventDefault();
            RG.alert({ host: alertHost, message: '' });

            if (!wizard.validateAll()) return;

            var pendientes = legales.filter(function (l) { return !l.accepted; });
            if (pendientes.length) {
                pendientes.forEach(function (l) { l.markMissing(); });
                var err = document.getElementById('rgLegalError');
                err.hidden = false;
                err.textContent = 'Abre y acepta los dos documentos para poder enviar tu registro.';
                return;
            }
            document.getElementById('rgLegalError').hidden = true;

            var btn = document.getElementById('rgSubmit');
            RG.button(btn, 'loading', 'Enviando registro…');

            var v = function (id) { return (document.getElementById(id).value || '').trim(); };
            var payload = new URLSearchParams({
                action: 'registerStudentPracticas',
                matricula: v('matricula'),
                grupo: v('grupo'),
                nombre: v('nombre'),
                curp: v('curp'),
                nacimiento: v('nacimiento'),
                genero: v('genero'),
                email: v('email'),
                telefono: v('telefono'),
                programa: v('programa'),
                periodo: v('periodo'),
                tipoPractica: (form.querySelector('input[name="tipoPractica"]:checked') || {}).value || '',
                aceptoTerminos: document.getElementById('chk-privacidad').checked ? 1 : 0,
                aceptoReglamento: document.getElementById('chk-reglamento').checked ? 1 : 0
            });

            fetch('controller/ajax/ajax.forms.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: payload
            })
                .then(function (res) { return res.text(); })
                .then(function (texto) {
                    var json;
                    try { json = JSON.parse(texto); } catch (e) { json = null; }

                    if (json && json.status === 'success') {
                        document.getElementById('rgDoneNombre').textContent = v('nombre').split(' ')[0] || 'listo';
                        document.getElementById('rgDoneCorreo').textContent = v('email');
                        if (json.message) document.getElementById('rgDoneMsg').textContent = json.message;
                        form.hidden = true;
                        document.getElementById('rgRail').hidden = true;
                        document.getElementById('rgMobar').hidden = true;
                        var done = document.getElementById('rgDone');
                        done.hidden = false;
                        done.setAttribute('tabindex', '-1');
                        done.focus({ preventScroll: true });
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                        return;
                    }

                    RG.button(btn, 'idle');
                    RG.alert({
                        host: alertHost, type: 'error', title: 'No pudimos completar tu registro',
                        message: (json && json.message)
                            ? json.message
                            : 'Ocurrió un problema en el servidor. Intenta de nuevo o escríbenos a <?= $rgCorreo ?>.'
                    });
                })
                .catch(function () {
                    RG.button(btn, 'idle');
                    RG.alert({
                        host: alertHost, type: 'error', title: 'Sin conexión con el servidor',
                        message: 'Revisa tu conexión a internet y vuelve a intentarlo. Lo que capturaste no se perdió.'
                    });
                });
        });
    });
</script>
