<?php
/**
 * Registro público de alumnos en Servicio Social · Universidad Montrer
 * ---------------------------------------------------------------------------
 * Ruta pública: /inscripcionServicio   (config/whiteList.php)
 * Endpoints:    controller/ajax/ajax.forms.php
 *                 · action=checkMatricula                    → consulta a GES
 *                 · search=student & action=addStudent       → alta del alumno
 *
 * CONTRATO DE CAMPOS — el submit manda el FormData completo y el modelo
 * FormsModel::mdlRegisterStudent() lee estos names tal cual. No renombrar
 * ninguno sin ajustar model/generalModels.php:642:
 *   matricula, nombre, apellidoPaterno, apellidoMaterno, licenciatura (idDegree),
 *   grado, correoInstitucional, telefonoContacto, telefonoEmergencia,
 *   parentesco (+ otroParentesco), tipoLicenciatura, calle, numeroExterior,
 *   numeroInterior, colonia, codigoPostal, diaNacimiento, mesNacimiento,
 *   anioNacimiento, genero (1=M, 2=F, 0=Otro), tipoPractica.
 *
 * Sistema visual: view/assets/css/registro-unimo.css
 * Motor multipaso: view/assets/js/registro/registro-core.js
 */

require_once __DIR__ . '/partials/registro-ui.php';
require_once __DIR__ . '/../../controller/forms.controller.php';

$rgCorreo  = rgCorreoArea('email_ss');
$degrees   = FormsController::ctrSearchDegrees(null);

/* La fecha de nacimiento se captura con un solo control nativo y se parte en
   día/mes/año al enviar, que es como los espera la base de datos. */
$rgEdadMin = (new DateTime('-15 years'))->format('Y-m-d');

$rgPasos = [
    ['titulo' => 'Identifícate',        'meta' => 'Con tu matrícula'],
    ['titulo' => 'Tus datos',           'meta' => 'Verifica que estén bien'],
    ['titulo' => 'Estudios y contacto', 'meta' => 'Carrera y emergencias'],
    ['titulo' => 'Domicilio',           'meta' => 'Dónde vives'],
    ['titulo' => 'Modalidad y envío',   'meta' => 'Confirma y envía'],
];
?>
<?php rgAssets('Registro de Servicio Social – Universidad Montrer'); ?>

<div class="rg">
    <div class="rg-page">

        <?php rgTopbar('Servicio Social', $rgCorreo); ?>

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
                <p class="rg-intro__eyebrow"><i class="fa-solid fa-hand-holding-heart" aria-hidden="true"></i> Alumnos</p>
                <h1>Inscríbete a Servicio Social Universitario</h1>
                <p>
                    Empieza con tu matrícula: traemos tus datos de Control Escolar y sólo tienes que
                    revisarlos. Son <strong><?= count($rgPasos) ?> pasos</strong> y puedes regresar a
                    cualquiera antes de enviar.
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

                <form id="rgFormServicio" novalidate autocomplete="on">

                    <!-- ═══════════ PASO 1 · Identifícate ═══════════ -->
                    <section class="rg-step is-active" data-title="Identifícate" id="rgStep0">
                        <div class="rg-step__head">
                            <p class="rg-step__kicker">Paso 1 de <?= count($rgPasos) ?></p>
                            <h2>Empecemos con tu matrícula</h2>
                            <p>Es el número que usas para entrar a la plataforma escolar. Con él traemos
                                tu nombre, tu carrera y tus datos de contacto automáticamente.</p>
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
                                así aparecerá en tu constancia de liberación.</p>
                        </div>

                        <div class="rg-step__body">
                            <div class="rg-note rg-note--info" style="margin-bottom:1.5rem">
                                <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>
                                <div>Los campos con fondo verde se llenaron solos. Revísalos con calma —
                                    <strong>tú eres responsable de que estén correctos</strong>.</div>
                            </div>

                            <div class="rg-grid">
                                <div class="rg-field rg-c4">
                                    <label class="rg-label" for="nombre">Nombre(s) <span class="rg-req" aria-hidden="true">*</span></label>
                                    <span class="rg-control">
                                        <i class="fa-solid fa-user rg-control__icon" aria-hidden="true"></i>
                                        <input class="rg-input" type="text" id="nombre" name="nombre" required
                                            maxlength="80" autocomplete="given-name" placeholder="Ej. María Fernanda"
                                            data-review data-label="Nombre(s)">
                                        <span class="rg-control__state" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                                    </span>
                                    <p class="rg-error" role="alert" hidden></p>
                                </div>

                                <div class="rg-field rg-c4">
                                    <label class="rg-label" for="apellidoPaterno">Apellido paterno <span class="rg-req" aria-hidden="true">*</span></label>
                                    <span class="rg-control">
                                        <i class="fa-solid fa-user rg-control__icon" aria-hidden="true"></i>
                                        <input class="rg-input" type="text" id="apellidoPaterno" name="apellidoPaterno"
                                            required maxlength="80" autocomplete="family-name" placeholder="Ej. Ruiz"
                                            data-review data-label="Apellido paterno">
                                        <span class="rg-control__state" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                                    </span>
                                    <p class="rg-error" role="alert" hidden></p>
                                </div>

                                <div class="rg-field rg-c4">
                                    <label class="rg-label" for="apellidoMaterno">
                                        Apellido materno <span class="rg-opt">(opcional)</span>
                                    </label>
                                    <span class="rg-control">
                                        <i class="fa-solid fa-user rg-control__icon" aria-hidden="true"></i>
                                        <input class="rg-input" type="text" id="apellidoMaterno" name="apellidoMaterno"
                                            maxlength="80" placeholder="Ej. Salgado"
                                            data-review data-label="Apellido materno">
                                        <span class="rg-control__state" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                                    </span>
                                    <p class="rg-error" role="alert" hidden></p>
                                </div>

                                <div class="rg-field rg-c6">
                                    <label class="rg-label" for="fechaNacimiento">Fecha de nacimiento <span class="rg-req" aria-hidden="true">*</span></label>
                                    <span class="rg-control">
                                        <i class="fa-solid fa-cake-candles rg-control__icon" aria-hidden="true"></i>
                                        <input class="rg-input" type="date" id="fechaNacimiento" required
                                            data-rule="edad" min="1940-01-01" max="<?= $rgEdadMin ?>"
                                            data-review data-label="Fecha de nacimiento">
                                        <span class="rg-control__state" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                                    </span>
                                    <p class="rg-hint">Se guarda en día, mes y año por separado.</p>
                                    <p class="rg-error" role="alert" hidden></p>
                                </div>

                                <div class="rg-field rg-c6">
                                    <label class="rg-label" for="genero">Género <span class="rg-req" aria-hidden="true">*</span></label>
                                    <span class="rg-control">
                                        <i class="fa-solid fa-venus-mars rg-control__icon" aria-hidden="true"></i>
                                        <select class="rg-select" id="genero" name="genero" required
                                            data-review data-label="Género">
                                            <option value="">Selecciona…</option>
                                            <option value="2">Femenino</option>
                                            <option value="1">Masculino</option>
                                            <option value="0">Otro</option>
                                        </select>
                                    </span>
                                    <p class="rg-error" role="alert" hidden></p>
                                </div>
                            </div>

                            <!-- La base de datos guarda la fecha en tres columnas -->
                            <input type="hidden" id="diaNacimiento" name="diaNacimiento">
                            <input type="hidden" id="mesNacimiento" name="mesNacimiento">
                            <input type="hidden" id="anioNacimiento" name="anioNacimiento">
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

                    <!-- ═══════════ PASO 3 · Estudios y contacto ═══════════ -->
                    <section class="rg-step" data-title="Estudios y contacto" id="rgStep2">
                        <div class="rg-step__head">
                            <p class="rg-step__kicker">Paso 3 de <?= count($rgPasos) ?></p>
                            <h2>Tu carrera y cómo localizarte</h2>
                            <p>Necesitamos saber en qué programa vas y a quién avisar si llegara a
                                pasar algo mientras prestas tu servicio.</p>
                        </div>

                        <div class="rg-step__body">
                            <div class="rg-section">
                                <p class="rg-section__title"><i class="fa-solid fa-graduation-cap" aria-hidden="true"></i> Datos académicos</p>
                                <div class="rg-grid">
                                    <div class="rg-field rg-c6">
                                        <label class="rg-label" for="licenciatura">Licenciatura <span class="rg-req" aria-hidden="true">*</span></label>
                                        <span class="rg-control">
                                            <i class="fa-solid fa-book-open-reader rg-control__icon" aria-hidden="true"></i>
                                            <select class="rg-select" id="licenciatura" name="licenciatura" required
                                                data-review data-label="Licenciatura">
                                                <option value="">Selecciona tu licenciatura…</option>
                                                <?php foreach ($degrees as $degree): ?>
                                                    <option value="<?= (int) $degree['idDegree'] ?>">
                                                        <?= htmlspecialchars($degree['nameDegree'], ENT_QUOTES, 'UTF-8') ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </span>
                                        <p class="rg-error" role="alert" hidden></p>
                                    </div>

                                    <div class="rg-field rg-c3">
                                        <label class="rg-label" for="tipoLicenciatura">Periodo <span class="rg-req" aria-hidden="true">*</span></label>
                                        <span class="rg-control">
                                            <i class="fa-solid fa-layer-group rg-control__icon" aria-hidden="true"></i>
                                            <select class="rg-select" id="tipoLicenciatura" name="tipoLicenciatura" required
                                                data-review data-label="Periodo">
                                                <option value="">Selecciona…</option>
                                                <option value="semestral">Semestral</option>
                                                <option value="cuatrimestral">Cuatrimestral</option>
                                            </select>
                                        </span>
                                        <p class="rg-error" role="alert" hidden></p>
                                    </div>

                                    <div class="rg-field rg-c3">
                                        <label class="rg-label" for="grado">Grado actual <span class="rg-req" aria-hidden="true">*</span></label>
                                        <span class="rg-control">
                                            <i class="fa-solid fa-hashtag rg-control__icon" aria-hidden="true"></i>
                                            <input class="rg-input" type="number" id="grado" name="grado" required
                                                min="1" max="15" inputmode="numeric" placeholder="Ej. 8"
                                                data-review data-label="Grado actual">
                                            <span class="rg-control__state" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                                        </span>
                                        <p class="rg-hint">Semestre o cuatrimestre.</p>
                                        <p class="rg-error" role="alert" hidden></p>
                                    </div>
                                </div>
                            </div>

                            <div class="rg-section">
                                <p class="rg-section__title"><i class="fa-solid fa-address-book" aria-hidden="true"></i> Tus datos de contacto</p>
                                <div class="rg-grid">
                                    <div class="rg-field rg-c6">
                                        <label class="rg-label" for="correoInstitucional">Correo institucional <span class="rg-req" aria-hidden="true">*</span></label>
                                        <span class="rg-control">
                                            <i class="fa-solid fa-envelope rg-control__icon" aria-hidden="true"></i>
                                            <input class="rg-input" type="email" id="correoInstitucional"
                                                name="correoInstitucional" required maxlength="100" autocomplete="email"
                                                placeholder="tumatricula@unimontrer.edu.mx"
                                                data-review data-label="Correo institucional">
                                            <span class="rg-control__state" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                                        </span>
                                        <p class="rg-hint">Aquí llegarán tus accesos y los avisos del área.</p>
                                        <p class="rg-error" role="alert" hidden></p>
                                        <div id="avisoDominio" hidden style="margin-top:.55rem">
                                            <div class="rg-note rg-note--warn">
                                                <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                                                <div>No es un correo <strong>@unimontrer.edu.mx</strong>. Puedes continuar,
                                                    pero revisa que sea uno que consultes seguido.</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="rg-field rg-c6">
                                        <label class="rg-label" for="telefonoContacto">Tu celular <span class="rg-req" aria-hidden="true">*</span></label>
                                        <span class="rg-control">
                                            <i class="fa-solid fa-mobile-screen rg-control__icon" aria-hidden="true"></i>
                                            <input class="rg-input" type="tel" id="telefonoContacto" name="telefonoContacto"
                                                required maxlength="10" inputmode="numeric" data-rule="tel" data-mask="digits"
                                                autocomplete="tel" placeholder="10 dígitos"
                                                data-review data-label="Tu celular">
                                            <span class="rg-control__state" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                                        </span>
                                        <p class="rg-error" role="alert" hidden></p>
                                    </div>
                                </div>
                            </div>

                            <div class="rg-section">
                                <p class="rg-section__title"><i class="fa-solid fa-house-medical" aria-hidden="true"></i> Contacto de emergencia</p>
                                <div class="rg-grid">
                                    <div class="rg-field rg-c4">
                                        <label class="rg-label" for="telefonoEmergencia">Teléfono <span class="rg-req" aria-hidden="true">*</span></label>
                                        <span class="rg-control">
                                            <i class="fa-solid fa-phone-volume rg-control__icon" aria-hidden="true"></i>
                                            <input class="rg-input" type="tel" id="telefonoEmergencia"
                                                name="telefonoEmergencia" required maxlength="10" inputmode="numeric"
                                                data-rule="tel" data-mask="digits" placeholder="10 dígitos"
                                                data-review data-label="Teléfono de emergencia">
                                            <span class="rg-control__state" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                                        </span>
                                        <p class="rg-hint">De alguien de confianza, no el tuyo.</p>
                                        <p class="rg-error" role="alert" hidden></p>
                                    </div>

                                    <div class="rg-field rg-c4">
                                        <label class="rg-label" for="parentesco">¿Quién es? <span class="rg-req" aria-hidden="true">*</span></label>
                                        <span class="rg-control">
                                            <i class="fa-solid fa-people-roof rg-control__icon" aria-hidden="true"></i>
                                            <select class="rg-select" id="parentesco" name="parentesco" required
                                                data-review data-label="Parentesco">
                                                <option value="">Selecciona…</option>
                                                <option value="Padre">Padre</option>
                                                <option value="Madre">Madre</option>
                                                <option value="Otro">Otro</option>
                                            </select>
                                        </span>
                                        <p class="rg-error" role="alert" hidden></p>
                                    </div>

                                    <div class="rg-field rg-c4" id="campoOtroParentesco" hidden>
                                        <label class="rg-label" for="otroParentesco">Especifica el parentesco <span class="rg-req" aria-hidden="true">*</span></label>
                                        <span class="rg-control">
                                            <i class="fa-solid fa-pen rg-control__icon" aria-hidden="true"></i>
                                            <input class="rg-input" type="text" id="otroParentesco" name="otroParentesco"
                                                maxlength="60" placeholder="Ej. Tutor, hermana, tía"
                                                data-review data-label="Parentesco (otro)">
                                            <span class="rg-control__state" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                                        </span>
                                        <p class="rg-error" role="alert" hidden></p>
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
                                    Continuar <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </section>

                    <!-- ═══════════ PASO 4 · Domicilio ═══════════ -->
                    <section class="rg-step" data-title="Domicilio" id="rgStep3">
                        <div class="rg-step__head">
                            <p class="rg-step__kicker">Paso 4 de <?= count($rgPasos) ?></p>
                            <h2>¿Dónde vives?</h2>
                            <p>Tu domicilio actual. Nos sirve para asignarte proyectos cerca de ti
                                cuando sea posible.</p>
                        </div>

                        <div class="rg-step__body">
                            <div class="rg-grid">
                                <div class="rg-field rg-c6">
                                    <label class="rg-label" for="calle">Calle <span class="rg-req" aria-hidden="true">*</span></label>
                                    <span class="rg-control">
                                        <i class="fa-solid fa-road rg-control__icon" aria-hidden="true"></i>
                                        <input class="rg-input" type="text" id="calle" name="calle" required
                                            maxlength="150" autocomplete="address-line1"
                                            placeholder="Ej. Av. Madero Poniente"
                                            data-review data-label="Calle">
                                        <span class="rg-control__state" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                                    </span>
                                    <p class="rg-error" role="alert" hidden></p>
                                </div>

                                <div class="rg-field rg-c3">
                                    <label class="rg-label" for="numeroExterior">Núm. exterior <span class="rg-req" aria-hidden="true">*</span></label>
                                    <span class="rg-control">
                                        <i class="fa-solid fa-hashtag rg-control__icon" aria-hidden="true"></i>
                                        <input class="rg-input" type="text" id="numeroExterior" name="numeroExterior"
                                            required maxlength="12" placeholder="Ej. 1250"
                                            data-review data-label="Número exterior">
                                        <span class="rg-control__state" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                                    </span>
                                    <p class="rg-error" role="alert" hidden></p>
                                </div>

                                <div class="rg-field rg-c3">
                                    <label class="rg-label" for="numeroInterior">
                                        Núm. interior <span class="rg-opt">(opcional)</span>
                                    </label>
                                    <span class="rg-control">
                                        <i class="fa-solid fa-door-open rg-control__icon" aria-hidden="true"></i>
                                        <input class="rg-input" type="text" id="numeroInterior" name="numeroInterior"
                                            maxlength="12" placeholder="Ej. 2B"
                                            data-review data-label="Número interior">
                                        <span class="rg-control__state" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                                    </span>
                                    <p class="rg-error" role="alert" hidden></p>
                                </div>

                                <div class="rg-field rg-c5">
                                    <label class="rg-label" for="colonia">Colonia <span class="rg-req" aria-hidden="true">*</span></label>
                                    <span class="rg-control">
                                        <i class="fa-solid fa-map rg-control__icon" aria-hidden="true"></i>
                                        <input class="rg-input" type="text" id="colonia" name="colonia" required
                                            maxlength="100" placeholder="Ej. Centro"
                                            data-review data-label="Colonia">
                                        <span class="rg-control__state" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                                    </span>
                                    <p class="rg-error" role="alert" hidden></p>
                                </div>

                                <div class="rg-field rg-c4">
                                    <label class="rg-label" for="municipio">
                                        Municipio o ciudad <span class="rg-opt">(opcional)</span>
                                    </label>
                                    <span class="rg-control">
                                        <i class="fa-solid fa-city rg-control__icon" aria-hidden="true"></i>
                                        <input class="rg-input" type="text" id="municipio" name="municipio"
                                            maxlength="100" autocomplete="address-level2" placeholder="Ej. Morelia"
                                            data-review data-label="Municipio o ciudad">
                                        <span class="rg-control__state" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                                    </span>
                                    <p class="rg-error" role="alert" hidden></p>
                                </div>

                                <div class="rg-field rg-c3">
                                    <label class="rg-label" for="codigoPostal">Código postal <span class="rg-req" aria-hidden="true">*</span></label>
                                    <span class="rg-control">
                                        <i class="fa-solid fa-envelopes-bulk rg-control__icon" aria-hidden="true"></i>
                                        <input class="rg-input" type="text" id="codigoPostal" name="codigoPostal"
                                            required inputmode="numeric" maxlength="5" data-rule="cp" data-mask="digits"
                                            autocomplete="postal-code" placeholder="58000"
                                            data-review data-label="Código postal">
                                        <span class="rg-control__state" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                                    </span>
                                    <p class="rg-error" role="alert" hidden></p>
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
                                    Continuar <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </section>

                    <!-- ═══════════ PASO 5 · Modalidad y envío ═══════════ -->
                    <section class="rg-step" data-title="Modalidad y envío" id="rgStep4">
                        <div class="rg-step__head">
                            <p class="rg-step__kicker">Paso 5 de <?= count($rgPasos) ?></p>
                            <h2>¿Cómo vas a prestar tu servicio social?</h2>
                            <p>Elige una opción, revisa que todo esté correcto y envía tu registro.</p>
                        </div>

                        <div class="rg-step__body">
                            <div class="rg-field">
                                <div class="rg-choices">
                                    <label class="rg-choice" for="opcionUniv">
                                        <input type="radio" name="tipoPractica" id="opcionUniv" value="universidad"
                                            required
                                            data-msg-required="Elige una modalidad para continuar.">
                                        <span class="rg-choice__mark" aria-hidden="true"></span>
                                        <span>
                                            <i class="fa-solid fa-school rg-choice__icon" aria-hidden="true"></i>
                                            <span class="rg-choice__title">A través de la Universidad</span>
                                            <span class="rg-choice__desc">Proyectos institucionales, eventos y
                                                actividades organizadas por Universidad Montrer.</span>
                                        </span>
                                    </label>

                                    <label class="rg-choice" for="opcionEmp">
                                        <input type="radio" name="tipoPractica" id="opcionEmp" value="empresa"
                                            required>
                                        <span class="rg-choice__mark" aria-hidden="true"></span>
                                        <span>
                                            <i class="fa-solid fa-building rg-choice__icon" aria-hidden="true"></i>
                                            <span class="rg-choice__title">En una institución externa</span>
                                            <span class="rg-choice__desc">En una empresa, dependencia pública u
                                                organización que tú conseguiste.</span>
                                        </span>
                                    </label>
                                </div>
                                <p class="rg-error" role="alert" hidden></p>
                            </div>

                            <div class="rg-note rg-note--info" id="avisoModalidad" hidden style="margin-top:1rem">
                                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                                <div id="avisoModalidadTexto"></div>
                            </div>

                            <div class="rg-section">
                                <p class="rg-section__title"><i class="fa-solid fa-list-check" aria-hidden="true"></i> Revisa antes de enviar</p>
                                <div class="rg-review" id="rgReview"></div>
                            </div>

                            <div class="rg-section">
                                <p class="rg-section__title"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Protección de datos</p>
                                <div class="rg-note rg-note--info">
                                    <i class="fa-solid fa-lock" aria-hidden="true"></i>
                                    <div>
                                        Tus datos se usarán <strong>exclusivamente</strong> para fines académicos y
                                        administrativos del programa de servicio social de la
                                        <strong>Universidad Montrer</strong>. No se comparten con terceros, no se venden
                                        y se tratan conforme a la <em>Ley Federal de Protección de Datos Personales en
                                        Posesión de los Particulares</em>.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rg-actions">
                            <p class="rg-actions__hint">Recibirás la confirmación en tu correo institucional.</p>
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
                    <p id="rgDoneMsg">Tu registro de Servicio Social quedó completado.</p>
                    <ol class="rg-timeline" id="rgDoneTimeline"></ol>
                    <div style="margin-top:2rem">
                        <a class="rg-btn rg-btn--primary" href="login">
                            <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i> Ir a iniciar sesión
                        </a>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        var form = document.getElementById('rgFormServicio');
        var alertHost = document.getElementById('rgAlert');
        var estadoBox = document.getElementById('matriculaEstado');
        var btnPaso0 = document.getElementById('btnPaso0');
        var CORREO_AREA = <?= json_encode($rgCorreo, JSON_UNESCAPED_UNICODE) ?>;
        var PASO_FINAL = 4;

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

        function estadoAlumno(nombre, carrera) {
            estadoBox.innerHTML =
                '<div class="rg-note rg-note--ok">' +
                '<i class="fa-solid fa-circle-check" aria-hidden="true"></i>' +
                '<div><strong>Te encontramos: ' + nombre + '</strong><br>' +
                (carrera || 'Carrera por confirmar') +
                '<br><span style="font-size:.84rem">¿No eres tú? Revisa el número de tu matrícula.</span>' +
                '</div></div>';
        }

        function bloquear() { alumnoEncontrado = false; btnPaso0.disabled = true; }
        function desbloquear() { alumnoEncontrado = true; btnPaso0.disabled = false; }

        function normaliza(texto) {
            return (texto || '').toUpperCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/Ñ/g, 'N').replace(/ñ/g, 'N').trim();
        }

        function val(id, v) {
            var el = document.getElementById(id);
            if (v !== undefined && v !== null) el.value = v;
            return el.value;
        }

        function soloDiez(t) { return (t || '').replace(/\D/g, '').slice(-10); }

        /** Marca visualmente los campos que llegaron de Control Escolar. */
        function marcarPrellenados(ids) {
            ids.forEach(function (id) {
                var el = document.getElementById(id);
                var campo = el && el.closest('.rg-field');
                if (campo && (el.value || '').trim()) campo.classList.add('is-prefilled');
            });
        }

        /** Selecciona la licenciatura comparando nombres normalizados. */
        function elegirLicenciatura(nombreOferta) {
            var sel = document.getElementById('licenciatura');
            var meta = normaliza(nombreOferta);
            if (!meta) return;
            for (var i = 0; i < sel.options.length; i++) {
                if (normaliza(sel.options[i].textContent) === meta) { sel.value = sel.options[i].value; return; }
            }
        }

        function elegirPeriodo(nombrePeriodo) {
            var p = (nombrePeriodo || '').toLowerCase();
            val('tipoLicenciatura', p.indexOf('cuatri') !== -1 ? 'cuatrimestral'
                : p.indexOf('semes') !== -1 ? 'semestral' : '');
        }

        function ponerFecha(iso) {
            if (!iso) return;
            var f = String(iso).split(' ')[0];
            var p = f.split('-');
            if (p.length !== 3) return;
            val('fechaNacimiento', f);
            sincronizarFecha();
        }

        /* La BD guarda día, mes y año por separado: el control nativo es sólo la
           cara visible y aquí se reparte a los tres campos ocultos. */
        function sincronizarFecha() {
            var p = (val('fechaNacimiento') || '').split('-');
            if (p.length === 3) {
                val('anioNacimiento', String(parseInt(p[0], 10)));
                val('mesNacimiento', String(parseInt(p[1], 10)));
                val('diaNacimiento', String(parseInt(p[2], 10)));
            } else {
                val('anioNacimiento', ''); val('mesNacimiento', ''); val('diaNacimiento', '');
            }
        }

        /* El parentesco que reporta GES: Padre y Madre caen en el select; cualquier
           otro valor se escribe en el campo libre. */
        function ponerParentesco(tipoFamiliar) {
            var sel = document.getElementById('parentesco');
            var esConocido = ['Padre', 'Madre'].indexOf(tipoFamiliar) !== -1;
            sel.value = esConocido ? tipoFamiliar : (tipoFamiliar ? 'Otro' : '');
            if (sel.value === 'Otro') val('otroParentesco', tipoFamiliar || '');
            alternarOtroParentesco();
        }

        function alternarOtroParentesco() {
            var sel = document.getElementById('parentesco');
            var campo = document.getElementById('campoOtroParentesco');
            var input = document.getElementById('otroParentesco');
            var esOtro = sel.value === 'Otro';
            campo.hidden = !esOtro;
            if (esOtro) {
                input.setAttribute('required', 'required');
            } else {
                input.removeAttribute('required');
                input.value = '';
                RG.clearField(input);
            }
        }

        document.getElementById('parentesco').addEventListener('change', alternarOtroParentesco);
        document.getElementById('fechaNacimiento').addEventListener('change', sincronizarFecha);

        /* Aviso no bloqueante si el correo no es institucional */
        document.getElementById('correoInstitucional').addEventListener('input', function () {
            var v = this.value.trim().toLowerCase();
            document.getElementById('avisoDominio').hidden =
                (v === '' || v.indexOf('@') === -1 || /@unimontrer\.edu\.mx$/.test(v));
        });

        /* ── Volcado de datos según lo que devuelva GES ───────────────────── */

        function desdeStudent(s, a, extra) {
            var telFamiliar = (a.Familiar_Telefono && a.Familiar_Telefono !== 'NULL')
                ? a.Familiar_Telefono : (extra.Numero || '');

            val('nombre', s.NOMBRE || '');
            val('apellidoPaterno', s.PATERNO || '');
            val('apellidoMaterno', s.MATERNO || '');
            val('genero', s.genero === 'F' ? '2' : s.genero === 'M' ? '1' : '0');
            ponerFecha(s.fecha_nacimiento);
            val('telefonoContacto', soloDiez(s.telefono));
            val('telefonoEmergencia', soloDiez(telFamiliar));
            val('correoInstitucional', s.correo_institucional || ((a.matricula || '') + '@unimontrer.edu.mx'));
            val('calle', a.direcionCasa || '');
            val('numeroExterior', a.numCasa || '');
            val('codigoPostal', a.cpCasa || '');
            val('colonia', (a.Familiar_Direccion && a.Familiar_Direccion !== 'NULL') ? a.Familiar_Direccion : '');
            elegirLicenciatura(a.nameOferta);
            elegirPeriodo(a.nombre_periodo);
            ponerParentesco(a.TipoFamiliar);
            var m = (s.CODIGO_GRUPO || '').match(/\d+/);
            val('grado', m ? m[0] : '');

            return { nombre: [s.NOMBRE, s.PATERNO, s.MATERNO].filter(Boolean).join(' '), carrera: a.nameOferta || '' };
        }

        function desdeAcademic(a) {
            val('nombre', a.nombre || '');
            val('apellidoPaterno', a.apellido1 || '');
            val('apellidoMaterno', a.apellido2 || '');
            val('genero', a.genero === 'F' ? '2' : a.genero === 'M' ? '1' : '0');
            ponerFecha(a.fechaNacimiento);
            val('telefonoContacto', soloDiez(a.telefono));
            val('telefonoEmergencia', soloDiez(a.Familiar_Telefono));
            val('correoInstitucional', (a.matricula || '') + '@unimontrer.edu.mx');
            val('calle', a.direcionCasa || '');
            val('numeroExterior', a.numCasa || '');
            val('codigoPostal', a.cpCasa || '');
            val('colonia', (a.Familiar_Direccion && a.Familiar_Direccion !== 'NULL') ? a.Familiar_Direccion : '');
            elegirLicenciatura(a.nameOferta);
            elegirPeriodo(a.nombre_periodo);
            ponerParentesco(a.TipoFamiliar);
            val('grado', a.avance || '');

            return { nombre: [a.nombre, a.apellido1, a.apellido2].filter(Boolean).join(' '), carrera: a.nameOferta || '' };
        }

        var CAMPOS_PRELLENADOS = ['nombre', 'apellidoPaterno', 'apellidoMaterno', 'genero',
            'fechaNacimiento', 'telefonoContacto', 'telefonoEmergencia', 'correoInstitucional',
            'calle', 'numeroExterior', 'codigoPostal', 'colonia', 'licenciatura',
            'tipoLicenciatura', 'parentesco', 'grado'];

        /* ── Búsqueda en Control Escolar ──────────────────────────────────── */

        var input = document.getElementById('matricula');

        var buscar = RG.debounce(function () {
            var v = input.value.trim();
            if (v === ultimaBusqueda) return;
            ultimaBusqueda = v;
            bloquear();

            if (!v) {
                estado('idle', '', 'Escribe tu matrícula y espera un momento: la buscaremos en Control Escolar.');
                return;
            }
            if (!/^\d{4,10}$/.test(v)) {
                estado('error', 'Matrícula no válida', 'La matrícula sólo lleva números. Revisa que no tenga espacios ni letras.');
                return;
            }

            estado('buscando', '', 'Buscando la matrícula <strong>' + v + '</strong> en Control Escolar…');

            fetch('controller/ajax/ajax.forms.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'checkMatricula', matricula: v })
            })
                .then(function (r) { return r.json(); })
                .then(function (resp) {
                    var s = resp.student || {};
                    var a = resp.academic || {};
                    var extra = resp.extra || {};
                    var datos = null;

                    if (s.MATRICULA) {
                        datos = desdeStudent(s, a, extra);
                    } else if (a.nombre_periodo === 'Trimestre' || a.nombre_periodo === 'Trimestral') {
                        estado('aviso', 'Tu programa no aplica a este trámite',
                            'El servicio social universitario es para alumnos inscritos en una licenciatura. ' +
                            'Consulta con tu coordinación académica.');
                        return;
                    } else if (a.matricula) {
                        datos = desdeAcademic(a);
                    }

                    if (!datos) {
                        estado('error', 'No encontramos esa matrícula',
                            'Verifica el número. Si estás seguro de que es correcto, escríbenos a ' +
                            '<a href="mailto:' + CORREO_AREA + '">' + CORREO_AREA + '</a>.');
                        return;
                    }

                    // Asignar .value por JS no dispara input ni respeta maxlength,
                    // así que las máscaras se reaplican sobre lo que llegó de GES.
                    RG.applyMasks(form);
                    marcarPrellenados(CAMPOS_PRELLENADOS);
                    estadoAlumno(datos.nombre, datos.carrera);
                    RG.validateField(input);
                    desbloquear();
                })
                .catch(function () {
                    estado('error', 'No pudimos consultar Control Escolar',
                        'Revisa tu conexión e inténtalo otra vez en un momento.');
                });
        }, 700);

        // Matrícula, teléfonos y CP llevan data-mask="digits": el núcleo los
        // limpia y los recorta a su maxlength antes de llegar aquí.
        input.addEventListener('input', buscar);

        /* ── Motor multipaso ──────────────────────────────────────────────── */
        var wizard = RG.wizard({
            root: form,
            rail: document.getElementById('rgRail'),
            mobar: document.getElementById('rgMobar'),
            onEnter: function (i) {
                if (i === PASO_FINAL) construirResumen();
            },
            canLeave: function (i) {
                if (i !== 0) return true;
                if (alumnoEncontrado) return true;
                estadoBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }
        });

        function construirResumen() {
            RG.buildReview({
                target: document.getElementById('rgReview'),
                steps: wizard.steps,
                upTo: PASO_FINAL,
                onEdit: function (idx) { wizard.go(idx); }
            });
        }

        /* La modalidad cambia lo que pasa después del envío, así que se explica
           en cuanto se elige. No entra al resumen: la tarjeta elegida queda
           resaltada justo arriba, repetirla sería ruido. */
        var AVISOS = {
            universidad: 'Tu registro quedará <strong>en revisión</strong>. El área de Servicio Social lo valida ' +
                'y después te envía tus accesos y los proyectos disponibles.',
            empresa: 'Al enviar recibirás <strong>tus accesos por correo</strong> para dar de alta la institución ' +
                'donde prestarás tu servicio y subir tus reportes.'
        };
        RG.$$('input[name="tipoPractica"]', form).forEach(function (r) {
            r.addEventListener('change', function () {
                document.getElementById('avisoModalidadTexto').innerHTML = AVISOS[r.value] || '';
                document.getElementById('avisoModalidad').hidden = !AVISOS[r.value];
            });
        });

        /* ── Envío ────────────────────────────────────────────────────────── */
        form.addEventListener('submit', function (ev) {
            ev.preventDefault();
            RG.alert({ host: alertHost, message: '' });

            sincronizarFecha();
            if (!wizard.validateAll()) return;

            var btn = document.getElementById('rgSubmit');
            RG.button(btn, 'loading', 'Enviando registro…');

            var datos = new FormData(form);
            datos.append('search', 'student');
            datos.append('action', 'addStudent');

            fetch('controller/ajax/ajax.forms.php', { method: 'POST', body: datos })
                .then(function (res) { return res.text(); })
                .then(function (texto) {
                    // El controlador responde con una cadena JSON: 'success',
                    // 'successed', 'duplicate…' o 'error'.
                    if (texto.indexOf('success') !== -1) {
                        mostrarConfirmacion();
                        return;
                    }
                    RG.button(btn, 'idle');
                    if (texto.indexOf('duplicate') !== -1) {
                        RG.alert({
                            host: alertHost, type: 'warn', title: 'Esta matrícula ya está registrada',
                            message: 'Ya existe un registro de servicio social con la matrícula ' +
                                val('matricula') + '. Si crees que es un error, escríbenos a ' +
                                '<a href="mailto:' + CORREO_AREA + '">' + CORREO_AREA + '</a>.'
                        });
                        return;
                    }
                    RG.alert({
                        host: alertHost, type: 'error', title: 'No pudimos completar tu registro',
                        message: 'Ocurrió un problema en el servidor. Intenta de nuevo o escríbenos a ' +
                            '<a href="mailto:' + CORREO_AREA + '">' + CORREO_AREA + '</a>.'
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

        function paso(estadoActual, titulo, texto) {
            return '<li' + (estadoActual === 'now' ? ' class="is-now"' : '') + '>' +
                '<span class="rg-timeline__dot">' +
                (estadoActual === 'now' ? '<i class="fa-solid fa-check" aria-hidden="true"></i>' : estadoActual) +
                '</span><div><p class="rg-timeline__title">' + titulo + '</p>' +
                '<p class="rg-timeline__desc">' + texto + '</p></div></li>';
        }

        function mostrarConfirmacion() {
            var modalidad = (form.querySelector('input[name="tipoPractica"]:checked') || {}).value;
            var correo = val('correoInstitucional');

            document.getElementById('rgDoneNombre').textContent = (val('nombre').split(' ')[0] || 'listo');
            document.getElementById('rgDoneMsg').textContent = modalidad === 'universidad'
                ? 'Tu solicitud de servicio social quedó registrada y pasa a revisión del área.'
                : 'Tu registro de servicio social quedó completado.';

            var linea = paso('now', 'Registro enviado',
                'Recibimos tus datos en el área de Servicio Social.');

            if (modalidad === 'universidad') {
                linea += paso('2', 'Revisión de tu solicitud',
                    'Se valida que cumplas los requisitos del periodo.');
                linea += paso('3', 'Recibes tus accesos',
                    'Llegarán a <strong>' + correo + '</strong> con tu contraseña temporal.');
                linea += paso('4', 'Eliges tu proyecto',
                    'Desde la plataforma te inscribes a los proyectos y eventos disponibles y registras tus horas.');
            } else {
                linea += paso('2', 'Revisa tu correo',
                    'Te enviamos tus accesos a <strong>' + correo + '</strong> con tu contraseña temporal.');
                linea += paso('3', 'Registras tu institución',
                    'Desde la plataforma das de alta dónde prestarás tu servicio social.');
                linea += paso('4', 'Reportas tus horas',
                    'Subes tus reportes hasta completar las horas requeridas por tu programa.');
            }

            document.getElementById('rgDoneTimeline').innerHTML = linea;
            form.hidden = true;
            document.getElementById('rgRail').hidden = true;
            document.getElementById('rgMobar').hidden = true;
            var done = document.getElementById('rgDone');
            done.hidden = false;
            done.setAttribute('tabindex', '-1');
            done.focus({ preventScroll: true });
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });
</script>
