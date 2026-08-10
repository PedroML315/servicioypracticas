<?php
/**
 * Registro público de Organismos Receptores · Universidad Montrer
 * ---------------------------------------------------------------------------
 * Ruta pública: /inscripcionEmpresas   (config/whiteList.php)
 * Endpoint:     controller/ajax/ajax.registroOrganismos.php
 *
 * Los names de los campos son contrato con el endpoint: no cambiarlos sin
 * ajustar también el arreglo $data de ajax.registroOrganismos.php.
 *
 * Sistema visual: view/assets/css/registro-unimo.css
 * Motor multipaso: view/assets/js/registro/registro-core.js
 */

require_once __DIR__ . '/../partials/registro-ui.php';

$rgCorreo = rgCorreoArea('email_pp');

$rgPdfPrivacidad = 'docs/' . rawurlencode('Términos y condiciones Pp (Organismo externo).pdf');
$rgPdfReglamento = 'docs/' . rawurlencode('REGLAMENTO PRÁCTICAS PROFESIONALES.pdf');

/* Pasos del trámite. El riel lateral y el motor de JS leen el mismo arreglo,
   así que agregar o quitar un paso es un cambio en un solo lugar. */
$rgPasos = [
    ['titulo' => 'Tu organización',      'meta' => 'Cómo está constituida'],
    ['titulo' => 'Datos del organismo',  'meta' => 'Razón social y domicilio'],
    ['titulo' => 'Personas responsables', 'meta' => 'Representante y contacto'],
    ['titulo' => 'Documentos',           'meta' => 'Expediente digital'],
    ['titulo' => 'Revisión y envío',     'meta' => 'Confirma y firma'],
];
?>
<?php rgAssets('Registro de Organismo Receptor – Universidad Montrer'); ?>

<div class="rg">
    <div class="rg-page">

        <?php rgTopbar('Prácticas Profesionales', $rgCorreo); ?>

        <!-- Progreso en móvil: siempre visible al hacer scroll -->
        <div class="rg-mobar" id="rgMobar">
            <div class="rg-mobar__row">
                <span class="rg-mobar__step">Tu organización</span>
                <span class="rg-mobar__count">Paso 1 de <?= count($rgPasos) ?></span>
            </div>
            <div class="rg-mobar__track" role="progressbar" aria-label="Avance del registro" aria-valuemin="1"
                aria-valuemax="<?= count($rgPasos) ?>" aria-valuenow="1">
                <span class="rg-mobar__fill"></span>
            </div>
        </div>

        <div class="rg-shell">

            <div class="rg-intro">
                <h1>Registra tu organización como Organismo Receptor</h1>
                <p>
                    Toma alrededor de <strong>10 minutos</strong> y puedes regresar a cualquier paso
                    antes de enviar. Al terminar, el área de Prácticas Profesionales revisará tu
                    expediente y te dará seguimiento por correo.
                </p>
            </div>

            <!-- ══ Riel de progreso (escritorio) ══ -->
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

            <!-- ══ Tarjeta del formulario ══ -->
            <div class="rg-card">
                <div id="rgAlert" hidden></div>

                <form id="rgFormOrganismo" enctype="multipart/form-data" novalidate autocomplete="on">

                    <!-- ═══════════ PASO 1 · Tu organización ═══════════ -->
                    <section class="rg-step is-active" data-title="Tu organización" id="rgStep0">
                        <div class="rg-step__head">
                            <p class="rg-step__kicker">Paso 1 de <?= count($rgPasos) ?></p>
                            <h2>¿Cómo está constituida tu organización?</h2>
                            <p>De esto depende qué documentos te pediremos más adelante. Si tienes duda,
                                revisa tu Constancia de Situación Fiscal.</p>
                        </div>

                        <div class="rg-step__body">
                            <div class="rg-field">
                                <div class="rg-choices">
                                    <label class="rg-choice" for="personaMoral">
                                        <input type="radio" name="tipoPersona" id="personaMoral" value="moral" required
                                            data-review data-label="Tipo de organización"
                                            data-msg-required="Elige cómo está constituida tu organización para continuar.">
                                        <span class="rg-choice__mark" aria-hidden="true"></span>
                                        <span>
                                            <i class="fa-solid fa-landmark rg-choice__icon" aria-hidden="true"></i>
                                            <span class="rg-choice__title">Persona Moral</span>
                                            <span class="rg-choice__desc">Empresa, institución, asociación civil o
                                                dependencia con acta constitutiva y RFC corporativo.</span>
                                        </span>
                                    </label>

                                    <label class="rg-choice" for="personaFisica">
                                        <input type="radio" name="tipoPersona" id="personaFisica" value="fisica" required
                                            data-review data-label="Tipo de organización">
                                        <span class="rg-choice__mark" aria-hidden="true"></span>
                                        <span>
                                            <i class="fa-solid fa-user-tie rg-choice__icon" aria-hidden="true"></i>
                                            <span class="rg-choice__title">Persona Física</span>
                                            <span class="rg-choice__desc">Profesionista independiente, consultorio o
                                                negocio registrado a nombre propio.</span>
                                        </span>
                                    </label>
                                </div>
                                <p class="rg-error" role="alert" hidden></p>
                            </div>

                            <!-- Se avisa desde el inicio qué documentos hará falta escanear -->
                            <div class="rg-section" id="rgChecklist" hidden>
                                <p class="rg-section__title">
                                    <i class="fa-solid fa-clipboard-check" aria-hidden="true"></i> Ten a la mano
                                </p>
                                <div class="rg-note rg-note--info">
                                    <i class="fa-solid fa-folder-open" aria-hidden="true"></i>
                                    <div>
                                        <strong>Necesitarás escanear estos documentos</strong> (PDF o foto legible,
                                        máximo 8 MB cada uno). Los subirás en el paso 4.
                                        <ul class="rg-list" id="rgChecklistItems" style="margin-top:.6rem"></ul>
                                    </div>
                                </div>
                            </div>

                            <div class="rg-section">
                                <p class="rg-section__title">
                                    <i class="fa-solid fa-handshake-angle" aria-hidden="true"></i> Al registrarte te comprometes a
                                </p>
                                <ul class="rg-list">
                                    <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Asignar actividades
                                        relacionadas con la formación académica del estudiante.</li>
                                    <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Mantener un ambiente
                                        seguro y propicio para el aprendizaje.</li>
                                    <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Respetar los horarios
                                        acordados con la Universidad Montrer.</li>
                                    <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Supervisar al
                                        estudiante durante toda su estancia.</li>
                                    <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Emitir los documentos
                                        requeridos: carta de aceptación, reportes y evaluación final.</li>
                                </ul>
                            </div>
                        </div>

                        <div class="rg-actions">
                            <p class="rg-actions__hint">Elige una opción para continuar.</p>
                            <div class="rg-actions__group">
                                <button type="button" class="rg-btn rg-btn--primary rg-next">
                                    Continuar <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </section>

                    <!-- ═══════════ PASO 2 · Datos del organismo ═══════════ -->
                    <section class="rg-step" data-title="Datos del organismo" id="rgStep1">
                        <div class="rg-step__head">
                            <p class="rg-step__kicker">Paso 2 de <?= count($rgPasos) ?></p>
                            <h2>Datos del organismo</h2>
                            <p>Escríbelos tal como aparecen en tu Constancia de Situación Fiscal: así se
                                imprimirán en el convenio y en las cartas de los practicantes.</p>
                        </div>

                        <div class="rg-step__body">
                            <div class="rg-section">
                                <p class="rg-section__title"><i class="fa-solid fa-building" aria-hidden="true"></i> Datos generales</p>
                                <div class="rg-grid">

                                    <div class="rg-field rg-c6">
                                        <label class="rg-label" for="empresa">Nombre o razón social <span class="rg-req" aria-hidden="true">*</span></label>
                                        <span class="rg-control">
                                            <i class="fa-solid fa-building rg-control__icon" aria-hidden="true"></i>
                                            <input class="rg-input" type="text" id="empresa" name="empresa" required
                                                maxlength="150" autocomplete="organization"
                                                placeholder="Ej. Grupo Hospitalario del Bajío S.A. de C.V."
                                                data-review data-label="Nombre o razón social">
                                            <span class="rg-control__state" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                                        </span>
                                        <p class="rg-hint" id="hint-empresa">Incluye el régimen (S.A. de C.V., A.C., etc.) si lo tiene.</p>
                                        <p class="rg-error" role="alert" hidden></p>
                                    </div>

                                    <div class="rg-field rg-c6">
                                        <label class="rg-label" for="giro">Giro o actividad principal <span class="rg-req" aria-hidden="true">*</span></label>
                                        <span class="rg-control">
                                            <i class="fa-solid fa-industry rg-control__icon" aria-hidden="true"></i>
                                            <input class="rg-input" type="text" id="giro" name="giro" required
                                                maxlength="100" placeholder="Ej. Servicios de salud"
                                                data-review data-label="Giro o actividad">
                                            <span class="rg-control__state" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                                        </span>
                                        <p class="rg-hint">A qué se dedica la organización, en pocas palabras.</p>
                                        <p class="rg-error" role="alert" hidden></p>
                                    </div>

                                    <div class="rg-field rg-c4">
                                        <label class="rg-label" for="fecha_constitucion">
                                            Fecha de constitución <span class="rg-opt">(opcional)</span>
                                        </label>
                                        <span class="rg-control">
                                            <i class="fa-solid fa-calendar-day rg-control__icon" aria-hidden="true"></i>
                                            <input class="rg-input" type="date" id="fecha_constitucion"
                                                name="fecha_constitucion" data-rule="no-futuro"
                                                data-review data-label="Fecha de constitución">
                                            <span class="rg-control__state" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                                        </span>
                                        <p class="rg-error" role="alert" hidden></p>
                                    </div>

                                    <div class="rg-field rg-c8">
                                        <label class="rg-label" for="web">
                                            Página web o red social <span class="rg-opt">(opcional)</span>
                                        </label>
                                        <span class="rg-control">
                                            <i class="fa-solid fa-globe rg-control__icon" aria-hidden="true"></i>
                                            <input class="rg-input" type="url" id="web" name="web" maxlength="255"
                                                placeholder="https://www.miorganizacion.com"
                                                data-review data-label="Sitio web">
                                            <span class="rg-control__state" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                                        </span>
                                        <p class="rg-hint">Nos ayuda a validar tu organización más rápido.</p>
                                        <p class="rg-error" role="alert" hidden></p>
                                    </div>
                                </div>
                            </div>

                            <div class="rg-section">
                                <p class="rg-section__title"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Domicilio</p>
                                <div class="rg-grid">

                                    <div class="rg-field rg-c8">
                                        <label class="rg-label" for="calle">Calle y número <span class="rg-req" aria-hidden="true">*</span></label>
                                        <span class="rg-control">
                                            <i class="fa-solid fa-road rg-control__icon" aria-hidden="true"></i>
                                            <input class="rg-input" type="text" id="calle" name="calle" required
                                                maxlength="150" autocomplete="street-address"
                                                placeholder="Ej. Av. Madero Poniente 1250, interior 3"
                                                data-review data-label="Calle y número">
                                            <span class="rg-control__state" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                                        </span>
                                        <p class="rg-error" role="alert" hidden></p>
                                    </div>

                                    <div class="rg-field rg-c4">
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

                                    <div class="rg-field rg-c8">
                                        <label class="rg-label" for="ciudad">Ciudad y estado <span class="rg-req" aria-hidden="true">*</span></label>
                                        <span class="rg-control">
                                            <i class="fa-solid fa-city rg-control__icon" aria-hidden="true"></i>
                                            <input class="rg-input" type="text" id="ciudad" name="ciudad" required
                                                maxlength="100" autocomplete="address-level2"
                                                placeholder="Ej. Morelia, Michoacán"
                                                data-review data-label="Ciudad y estado">
                                            <span class="rg-control__state" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                                        </span>
                                        <p class="rg-error" role="alert" hidden></p>
                                    </div>

                                    <div class="rg-field rg-c4">
                                        <label class="rg-label" for="cp">Código postal <span class="rg-req" aria-hidden="true">*</span></label>
                                        <span class="rg-control">
                                            <i class="fa-solid fa-envelopes-bulk rg-control__icon" aria-hidden="true"></i>
                                            <input class="rg-input" type="text" id="cp" name="cp" required
                                                inputmode="numeric" maxlength="5" data-rule="cp" data-mask="digits"
                                                autocomplete="postal-code" placeholder="58000"
                                                data-review data-label="Código postal">
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

                    <!-- ═══════════ PASO 3 · Personas responsables ═══════════ -->
                    <section class="rg-step" data-title="Personas responsables" id="rgStep2">
                        <div class="rg-step__head">
                            <p class="rg-step__kicker">Paso 3 de <?= count($rgPasos) ?></p>
                            <h2>¿Quién representa a la organización?</h2>
                            <p>Necesitamos dos contactos: quien firma el convenio y quien acompañará
                                día a día a los practicantes. Pueden ser la misma persona.</p>
                        </div>

                        <div class="rg-step__body">
                            <div class="rg-section">
                                <p class="rg-section__title"><i class="fa-solid fa-user-shield" aria-hidden="true"></i> Representante legal · firma el convenio</p>
                                <div class="rg-grid">

                                    <div class="rg-field rg-c6">
                                        <label class="rg-label" for="rep_legal">Nombre completo <span class="rg-req" aria-hidden="true">*</span></label>
                                        <span class="rg-control">
                                            <i class="fa-solid fa-user rg-control__icon" aria-hidden="true"></i>
                                            <input class="rg-input" type="text" id="rep_legal" name="rep_legal" required
                                                maxlength="100" minlength="5" autocomplete="name"
                                                placeholder="Ej. Lic. María Fernanda Ruiz Salgado"
                                                data-review data-label="Representante legal">
                                            <span class="rg-control__state" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                                        </span>
                                        <p class="rg-hint">Tal como firmará el convenio.</p>
                                        <p class="rg-error" role="alert" hidden></p>
                                    </div>

                                    <div class="rg-field rg-c6">
                                        <label class="rg-label" for="cargo_legal">Cargo <span class="rg-req" aria-hidden="true">*</span></label>
                                        <span class="rg-control">
                                            <i class="fa-solid fa-id-badge rg-control__icon" aria-hidden="true"></i>
                                            <input class="rg-input" type="text" id="cargo_legal" name="cargo_legal"
                                                required maxlength="100" placeholder="Ej. Directora General"
                                                data-review data-label="Cargo del representante">
                                            <span class="rg-control__state" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                                        </span>
                                        <p class="rg-error" role="alert" hidden></p>
                                    </div>

                                    <div class="rg-field rg-c6">
                                        <label class="rg-label" for="email_legal">Correo electrónico <span class="rg-req" aria-hidden="true">*</span></label>
                                        <span class="rg-control">
                                            <i class="fa-solid fa-envelope rg-control__icon" aria-hidden="true"></i>
                                            <input class="rg-input" type="email" id="email_legal" name="email_legal"
                                                required maxlength="100" placeholder="direccion@miorganizacion.com"
                                                data-review data-label="Correo del representante">
                                            <span class="rg-control__state" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                                        </span>
                                        <p class="rg-hint">Aquí llegará el convenio para firma.</p>
                                        <p class="rg-error" role="alert" hidden></p>
                                    </div>

                                    <div class="rg-field rg-c6">
                                        <label class="rg-label" for="tel_oficina">Teléfono de oficina <span class="rg-req" aria-hidden="true">*</span></label>
                                        <span class="rg-control">
                                            <i class="fa-solid fa-phone rg-control__icon" aria-hidden="true"></i>
                                            <input class="rg-input" type="tel" id="tel_oficina" name="tel_oficina"
                                                required maxlength="10" inputmode="numeric" data-rule="tel"
                                                data-mask="digits" placeholder="4431234567"
                                                data-review data-label="Teléfono de oficina">
                                            <span class="rg-control__state" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                                        </span>
                                        <p class="rg-hint">10 dígitos, sin espacios ni guiones.</p>
                                        <p class="rg-error" role="alert" hidden></p>
                                    </div>
                                </div>
                            </div>

                            <div class="rg-section">
                                <p class="rg-section__title"><i class="fa-solid fa-user-gear" aria-hidden="true"></i> Responsable operativo · atiende a los practicantes</p>

                                <label class="rg-choice" for="mismoContacto" style="margin-bottom:1.1rem">
                                    <input type="checkbox" id="mismoContacto">
                                    <span class="rg-choice__mark" aria-hidden="true" style="border-radius:7px"></span>
                                    <span>
                                        <span class="rg-choice__title">Es la misma persona que el representante legal</span>
                                        <span class="rg-choice__desc">Copiamos los datos de arriba para que no los escribas dos veces.</span>
                                    </span>
                                </label>

                                <div class="rg-grid">
                                    <div class="rg-field rg-c6">
                                        <label class="rg-label" for="nombre_contacto">Nombre completo <span class="rg-req" aria-hidden="true">*</span></label>
                                        <span class="rg-control">
                                            <i class="fa-solid fa-user-tie rg-control__icon" aria-hidden="true"></i>
                                            <input class="rg-input" type="text" id="nombre_contacto"
                                                name="nombre_contacto" required maxlength="100" minlength="5"
                                                placeholder="Ej. Ing. Juan Pérez López"
                                                data-review data-label="Responsable operativo">
                                            <span class="rg-control__state" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                                        </span>
                                        <p class="rg-error" role="alert" hidden></p>
                                    </div>

                                    <div class="rg-field rg-c6">
                                        <label class="rg-label" for="telefonos">Teléfono directo <span class="rg-req" aria-hidden="true">*</span></label>
                                        <span class="rg-control">
                                            <i class="fa-solid fa-phone-volume rg-control__icon" aria-hidden="true"></i>
                                            <input class="rg-input" type="tel" id="telefonos" name="telefonos" required
                                                maxlength="10" inputmode="numeric" data-rule="tel"
                                                data-mask="digits" placeholder="4431234567"
                                                data-review data-label="Teléfono del responsable">
                                            <span class="rg-control__state" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                                        </span>
                                        <p class="rg-error" role="alert" hidden></p>
                                    </div>

                                    <div class="rg-field rg-c6">
                                        <label class="rg-label" for="email">Correo electrónico <span class="rg-req" aria-hidden="true">*</span></label>
                                        <span class="rg-control">
                                            <i class="fa-solid fa-at rg-control__icon" aria-hidden="true"></i>
                                            <input class="rg-input" type="email" id="email" name="email" required
                                                maxlength="100" placeholder="contacto@miorganizacion.com"
                                                data-review data-label="Correo del responsable">
                                            <span class="rg-control__state" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                                        </span>
                                        <p class="rg-hint">Será tu usuario para entrar a la plataforma.</p>
                                        <p class="rg-error" role="alert" hidden></p>
                                    </div>

                                    <div class="rg-field rg-c6">
                                        <label class="rg-label" for="celular">
                                            Celular <span class="rg-opt">(opcional)</span>
                                        </label>
                                        <span class="rg-control">
                                            <i class="fa-solid fa-mobile-screen rg-control__icon" aria-hidden="true"></i>
                                            <input class="rg-input" type="tel" id="celular" name="celular"
                                                maxlength="10" inputmode="numeric" data-rule="tel"
                                                data-mask="digits" placeholder="10 dígitos"
                                                data-review data-label="Celular">
                                            <span class="rg-control__state" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                                        </span>
                                        <p class="rg-hint">Para avisos urgentes sobre practicantes.</p>
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

                    <!-- ═══════════ PASO 4 · Documentos ═══════════ -->
                    <section class="rg-step" data-title="Documentos" id="rgStep3">
                        <div class="rg-step__head">
                            <p class="rg-step__kicker">Paso 4 de <?= count($rgPasos) ?></p>
                            <h2>Expediente digital</h2>
                            <p>Arrastra cada archivo o toca la tarjeta para buscarlo. Aceptamos PDF y
                                fotos legibles (JPG, PNG o WEBP) de hasta 8 MB cada uno.</p>
                        </div>

                        <div class="rg-step__body">
                            <div class="rg-docs" id="rgDocs">
                                <!-- Se generan según el tipo de organización elegido en el paso 1 -->
                            </div>

                            <div class="rg-note rg-note--warn" style="margin-top:1.25rem">
                                <i class="fa-solid fa-camera" aria-hidden="true"></i>
                                <div>
                                    <strong>¿Vas a tomar una foto?</strong> Cuida que se lean todos los datos,
                                    que no haya reflejos y que el documento salga completo dentro del encuadre.
                                    Un documento ilegible retrasa la aprobación.
                                </div>
                            </div>
                        </div>

                        <div class="rg-actions">
                            <p class="rg-actions__hint" id="rgDocsHint">Todos los documentos son obligatorios.</p>
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

                    <!-- ═══════════ PASO 5 · Revisión y envío ═══════════ -->
                    <section class="rg-step" data-title="Revisión y envío" id="rgStep4">
                        <div class="rg-step__head">
                            <p class="rg-step__kicker">Paso 5 de <?= count($rgPasos) ?></p>
                            <h2>Revisa antes de enviar</h2>
                            <p>Verifica que todo esté correcto. Si algo no coincide, usa el botón
                                <em>Editar</em> del bloque para corregirlo sin perder lo demás.</p>
                        </div>

                        <div class="rg-step__body">
                            <div class="rg-review" id="rgReview"></div>

                            <div class="rg-section">
                                <p class="rg-section__title"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Protección de datos</p>
                                <div class="rg-note rg-note--info">
                                    <i class="fa-solid fa-lock" aria-hidden="true"></i>
                                    <div>
                                        La información que proporciones se usará <strong>exclusivamente</strong> para
                                        fines académicos y administrativos del programa de prácticas profesionales de la
                                        <strong>Universidad Montrer</strong>. No se comparte con terceros, no se vende y
                                        no se usa con fines comerciales; se trata conforme a la <em>Ley Federal de
                                        Protección de Datos Personales en Posesión de los Particulares</em>.
                                    </div>
                                </div>
                            </div>

                            <div class="rg-section">
                                <p class="rg-section__title"><i class="fa-solid fa-file-signature" aria-hidden="true"></i> Documentos que debes aceptar</p>
                                <div class="rg-legal">
                                    <?php
                                    rgConsent([
                                        'id'     => 'privacidad',
                                        'name'   => 'aceptoTerminos',
                                        'titulo' => 'He leído y acepto el <strong>Aviso de privacidad</strong> para organismos receptores.',
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
                            <p class="rg-actions__hint">Recibirás una copia de este registro en el correo del responsable operativo.</p>
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
                    <h2>¡Registro enviado!</h2>
                    <p>
                        Recibimos el expediente de <strong id="rgDoneEmpresa">tu organización</strong>.
                        Te avisaremos por correo en cada paso; no necesitas hacer nada más por ahora.
                    </p>
                    <ol class="rg-timeline">
                        <li class="is-now">
                            <span class="rg-timeline__dot"><i class="fa-solid fa-check" aria-hidden="true"></i></span>
                            <div>
                                <p class="rg-timeline__title">Registro recibido</p>
                                <p class="rg-timeline__desc">Tu expediente ya está en la bandeja del área de Prácticas Profesionales.</p>
                            </div>
                        </li>
                        <li>
                            <span class="rg-timeline__dot">2</span>
                            <div>
                                <p class="rg-timeline__title">Revisión de documentos · 1 a 3 días hábiles</p>
                                <p class="rg-timeline__desc">Si algo falta o no se lee bien, te escribiremos con un enlace para corregirlo.</p>
                            </div>
                        </li>
                        <li>
                            <span class="rg-timeline__dot">3</span>
                            <div>
                                <p class="rg-timeline__title">Convenio para firma</p>
                                <p class="rg-timeline__desc">Enviaremos el convenio en PDF al correo del representante legal. Se firma, se escanea y se sube por el enlace del mismo correo.</p>
                            </div>
                        </li>
                        <li>
                            <span class="rg-timeline__dot">4</span>
                            <div>
                                <p class="rg-timeline__title">Acceso a la plataforma</p>
                                <p class="rg-timeline__desc">Al validar el convenio te enviamos usuario y contraseña para publicar vacantes y recibir practicantes.</p>
                            </div>
                        </li>
                    </ol>
                    <div style="margin-top:2rem">
                        <a class="rg-btn rg-btn--ghost" href="login">
                            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Volver al inicio
                        </a>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <?php
    rgLegalDialog([
        'id'     => 'privacidad',
        'titulo' => 'Aviso de privacidad · Organismo receptor',
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

        var form = document.getElementById('rgFormOrganismo');
        var alertHost = document.getElementById('rgAlert');
        var docsHost = document.getElementById('rgDocs');
        var PASO_DOCS = 3;
        var PASO_REVISION = 4;

        /* ── Catálogo de documentos por tipo de constitución ───────────────
           La clave se vuelve el nombre del archivo en uploads/{id}/, así que
           conviene que sea corta y legible para quien revisa el expediente. */
        var CATALOGO = {
            moral: [
                {
                    key: 'acta_constitutiva',
                    nombre: 'Acta constitutiva',
                    desc: 'Escritura notarial con la que se constituyó la sociedad o asociación.'
                },
                {
                    key: 'constancia_situacion_fiscal',
                    nombre: 'Constancia de Situación Fiscal',
                    desc: 'Emitida por el SAT. Debe ser la versión vigente.'
                },
                {
                    key: 'comprobante_domicilio',
                    nombre: 'Comprobante de domicilio',
                    desc: 'Recibo de luz, agua o predial con antigüedad máxima de 2 meses.'
                },
                {
                    key: 'identificacion_representante',
                    nombre: 'Identificación del representante legal',
                    desc: 'INE, pasaporte o cédula profesional vigente, por ambos lados.'
                }
            ],
            fisica: [
                {
                    key: 'constancia_situacion_fiscal',
                    nombre: 'Constancia de Situación Fiscal',
                    desc: 'Emitida por el SAT. Debe ser la versión vigente.'
                },
                {
                    key: 'comprobante_domicilio',
                    nombre: 'Comprobante de domicilio',
                    desc: 'Recibo de luz, agua o predial con antigüedad máxima de 2 meses.'
                },
                {
                    key: 'identificacion_representante',
                    nombre: 'Identificación oficial',
                    desc: 'INE, pasaporte o cédula profesional vigente, por ambos lados.'
                }
            ]
        };

        var zonas = [];       // instancias de RG.dropzone del tipo activo
        var tipoRenderizado = null;

        function plantillaDoc(doc, indice) {
            return '' +
                '<div class="rg-doc" data-key="' + doc.key + '">' +
                '  <div class="rg-doc__head">' +
                '    <span class="rg-doc__num">' + indice + '</span>' +
                '    <span>' +
                '      <span class="rg-doc__name">' + doc.nombre + '</span>' +
                '      <span class="rg-doc__desc">' + doc.desc + '</span>' +
                '    </span>' +
                '  </div>' +
                '  <div class="rg-drop" tabindex="0" role="button"' +
                '       aria-label="Adjuntar ' + doc.nombre + '">' +
                '    <span class="rg-drop__box"><i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i></span>' +
                '    <span class="rg-drop__text">' +
                '      <span class="rg-drop__title">Arrastra el archivo aquí o <u>búscalo en tu equipo</u></span>' +
                '      <span class="rg-drop__meta">PDF, JPG, PNG o WEBP · hasta 8 MB</span>' +
                '    </span>' +
                '  </div>' +
                '  <div class="rg-file">' +
                '    <span class="rg-file__icon"><i class="fa-solid fa-file-pdf" aria-hidden="true"></i></span>' +
                '    <span class="rg-file__info">' +
                '      <span class="rg-file__name"></span>' +
                '      <span class="rg-file__size"></span>' +
                '    </span>' +
                '    <span class="rg-file__actions">' +
                '      <button type="button" class="rg-iconbtn rg-file__replace" title="Cambiar archivo" aria-label="Cambiar el archivo de ' + doc.nombre + '"><i class="fa-solid fa-rotate" aria-hidden="true"></i></button>' +
                '      <button type="button" class="rg-iconbtn rg-iconbtn--danger rg-file__remove" title="Quitar archivo" aria-label="Quitar el archivo de ' + doc.nombre + '"><i class="fa-solid fa-trash-can" aria-hidden="true"></i></button>' +
                '    </span>' +
                '  </div>' +
                '  <input type="file" name="docs[' + doc.key + ']" data-review data-label="' + doc.nombre + '"' +
                '         accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/*">' +
                '  <p class="rg-error" role="alert" hidden style="padding:0 1.1rem 1rem"></p>' +
                '</div>';
        }

        function pintarDocumentos(tipo) {
            if (tipo === tipoRenderizado) return;      // no perder lo ya adjuntado
            tipoRenderizado = tipo;
            var lista = CATALOGO[tipo] || [];
            docsHost.innerHTML = lista.map(function (d, i) { return plantillaDoc(d, i + 1); }).join('');
            zonas = RG.$$('.rg-doc', docsHost).map(function (el) {
                return RG.dropzone(el, { maxMB: 8, onChange: refrescarDocumentos });
            });
            refrescarDocumentos();
        }

        /* Qué alerta está puesta ahora mismo: 'faltan' | 'peso' | null.
           Se lleva el registro para poder retirarla en cuanto deje de aplicar y
           para no borrar una alerta de otro origen (p. ej. un error de envío). */
        var avisoActual = null;
        var LIMITE_TOTAL = 20 * 1024 * 1024;

        function limpiarAviso() {
            avisoActual = null;
            RG.alert({ host: alertHost, message: '' });
        }

        function avisoFaltantes(faltan, scroll) {
            avisoActual = 'faltan';
            RG.alert({
                host: alertHost, type: 'error', title: 'Faltan documentos por adjuntar',
                scroll: scroll,
                message: faltan === 1
                    ? 'Marcamos en rojo el documento que falta. Adjúntalo para continuar.'
                    : 'Marcamos en rojo los ' + faltan + ' documentos que faltan. Adjúntalos para continuar.'
            });
        }

        /* Se ejecuta cada vez que cambia un archivo: actualiza el contador y, sobre
           todo, retira o recalcula el aviso en cuanto el usuario resuelve el faltante.
           El límite de peso existe porque el servidor rechaza el envío completo si se
           pasa de post_max_size: más vale avisarlo antes de subir 30 MB por red lenta. */
        function refrescarDocumentos() {
            var total = 0, cargados = 0;
            zonas.forEach(function (z) {
                var f = z.input.files && z.input.files[0];
                if (f) { total += f.size; cargados++; }
            });

            var hint = document.getElementById('rgDocsHint');
            if (!zonas.length) { hint.textContent = 'Todos los documentos son obligatorios.'; return; }
            hint.textContent = cargados + ' de ' + zonas.length + ' documentos adjuntos'
                + (total ? ' · ' + RG.formatBytes(total) + ' en total' : '');

            var faltan = zonas.length - cargados;

            if (total > LIMITE_TOTAL) {
                avisoActual = 'peso';
                RG.alert({
                    host: alertHost, type: 'warn', title: 'Los archivos pesan mucho',
                    scroll: false,
                    message: 'En total suman ' + RG.formatBytes(total) + '. Si el envío falla, vuelve a escanear los documentos en menor resolución.'
                });
                return;
            }

            if (avisoActual === 'peso') { limpiarAviso(); }
            if (avisoActual === 'faltan') {
                if (!faltan) limpiarAviso();
                else avisoFaltantes(faltan, false);   // sólo actualiza el conteo
            }
        }

        function listaChecklist(tipo) {
            var cont = document.getElementById('rgChecklistItems');
            var caja = document.getElementById('rgChecklist');
            var lista = CATALOGO[tipo] || [];
            if (!lista.length) { caja.hidden = true; return; }
            cont.innerHTML = lista.map(function (d) {
                return '<li><i class="fa-solid fa-file-arrow-up" aria-hidden="true"></i> ' + d.nombre + '</li>';
            }).join('');
            caja.hidden = false;
        }

        /* ── Motor multipaso ──────────────────────────────────────────────── */
        var wizard = RG.wizard({
            root: form,
            rail: document.getElementById('rgRail'),
            mobar: document.getElementById('rgMobar'),
            onEnter: function (i) {
                if (i === PASO_DOCS) {
                    var tipo = (form.querySelector('input[name="tipoPersona"]:checked') || {}).value;
                    pintarDocumentos(tipo);
                }
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
                if (i !== PASO_DOCS) return true;

                if (!zonas.length) {                     // aún no se eligió el tipo
                    wizard.go(0);
                    return false;
                }
                var faltantes = zonas.filter(function (z) { return !z.filled; });
                if (!faltantes.length) return true;

                faltantes.forEach(function (z, n) { z.markMissing(n === 0); });
                avisoFaltantes(faltantes.length, true);
                return false;
            }
        });

        /* Al elegir el tipo de organización, se anuncia el expediente que hará falta */
        RG.$$('input[name="tipoPersona"]', form).forEach(function (r) {
            r.addEventListener('change', function () {
                listaChecklist(r.value);
                if (tipoRenderizado && tipoRenderizado !== r.value) {
                    tipoRenderizado = null;              // el catálogo cambió: se regenera
                    zonas = [];
                    docsHost.innerHTML = '';
                }
            });
        });

        /* Copiar los datos del representante al responsable operativo */
        var mismo = document.getElementById('mismoContacto');
        var PARES = [['rep_legal', 'nombre_contacto'], ['tel_oficina', 'telefonos'], ['email_legal', 'email']];
        mismo.addEventListener('change', function () {
            PARES.forEach(function (par) {
                var origen = document.getElementById(par[0]);
                var destino = document.getElementById(par[1]);
                if (mismo.checked) {
                    destino.value = origen.value;
                    destino.readOnly = true;
                    RG.validateField(destino);
                } else {
                    destino.readOnly = false;
                }
            });
            RG.syncChoices(form);
        });
        PARES.forEach(function (par) {
            document.getElementById(par[0]).addEventListener('input', function () {
                if (!mismo.checked) return;
                var destino = document.getElementById(par[1]);
                destino.value = this.value;
                RG.validateField(destino);
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

        /* ── Borrador de la sesión ────────────────────────────────────────── */
        var borrador = RG.draft({ key: 'rg_organismo_v1', form: form });
        var recuperado = borrador.restore();
        if (recuperado && Object.keys(recuperado).length > 2) {
            RG.alert({
                host: alertHost, type: 'info', title: 'Recuperamos lo que habías capturado',
                message: 'Continúa donde te quedaste. Los documentos sí debes volver a adjuntarlos.'
            });
            var tipoPrevio = (form.querySelector('input[name="tipoPersona"]:checked') || {}).value;
            if (tipoPrevio) listaChecklist(tipoPrevio);
        }
        borrador.watch();

        /* ── Envío ────────────────────────────────────────────────────────── */
        form.addEventListener('submit', function (ev) {
            ev.preventDefault();
            limpiarAviso();

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

            fetch('controller/ajax/ajax.registroOrganismos.php', {
                method: 'POST',
                body: new FormData(form)
            })
                .then(function (res) {
                    return res.json().then(function (json) { return { ok: res.ok, status: res.status, json: json }; })
                        .catch(function () { return { ok: false, status: res.status, json: null }; });
                })
                .then(function (r) {
                    if (r.json && r.json.success) {
                        borrador.clear();
                        document.getElementById('rgDoneEmpresa').textContent =
                            document.getElementById('empresa').value.trim() || 'tu organización';
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
                        host: alertHost,
                        type: r.status === 429 ? 'warn' : 'error',
                        title: r.status === 429 ? 'Demasiados intentos' : 'No pudimos guardar el registro',
                        message: (r.json && r.json.message)
                            ? r.json.message
                            : 'Ocurrió un problema en el servidor. Intenta de nuevo en unos minutos o escríbenos a <?= $rgCorreo ?>.'
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
