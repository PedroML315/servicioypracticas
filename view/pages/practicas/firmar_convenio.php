<?php
$token = $_GET['token'] ?? '';
if (!$token) {
    echo '<div class="alert alert-danger m-5">Enlace no válido.</div>';
    return;
}

$data = PracticasModel::mdlGetTokenConvenio($token);

if (!$data) {
    echo '<div class="container mt-5">
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
                <i class="fas fa-times-circle text-danger mb-3" style="font-size: 4rem;"></i>
                <h3 class="fw-bold">Enlace inválido o expirado</h3>
                <p class="text-muted">Este enlace para subir el convenio firmado ha expirado o ya fue utilizado. Si necesitas volver a enviarlo, contacta al equipo de Prácticas Profesionales de la Universidad Montrer.</p>
            </div>
          </div>';
    return;
}

$esRecarga     = ($data['tipo'] ?? 'firma') === 'recarga';
$motivoRechazo = '';
if ($esRecarga) {
    $org = PracticasModel::mdlGetOrganismoById((int) $data['organismo_id']);
    $motivoRechazo = $org['convenio_motivo_rechazo'] ?? '';
}

$hasOtpSession = isset($_SESSION['firma_token']) && $_SESSION['firma_token'] === $token;
?>

<style>
    body {
        background: #f8faf9;
    }

    .fc-hero {
        background: linear-gradient(135deg, #00204a 0%, #01643D 100%);
        color: white;
        padding: 3rem 0 2rem 0;
        margin-bottom: -3rem;
    }

    .fc-card {
        border: none;
        border-radius: 1rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    }

    .otp-inputs input {
        width: 3rem;
        height: 4rem;
        font-size: 2rem;
        text-align: center;
        border-radius: .5rem;
        border: 2px solid #dee2e6;
        margin: 0 0.3rem;
    }

    .otp-inputs input:focus {
        border-color: #01643D;
        box-shadow: 0 0 0 0.25rem rgba(1, 100, 61, 0.25);
        outline: none;
    }

    .step-num {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: #01643D;
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        flex-shrink: 0;
    }
</style>

<div class="fc-hero">
    <div class="container text-center">
        <div
            style="width: 70px; height: 70px; background: rgba(255,255,255,0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 1rem auto;">
            <i class="fas fa-file-signature"></i>
        </div>
        <h2 class="fw-bold"><?= $esRecarga ? 'Reenvío de Convenio Firmado' : 'Firma de Convenio' ?></h2>
        <p class="opacity-75 mb-0"><?= htmlspecialchars($data['empresa']) ?></p>
    </div>
</div>

<div class="container position-relative"
    style="z-index: 10; padding-top: 4rem; padding-bottom: 5rem; max-width: 760px;">

    <?php if (!$hasOtpSession): ?>
        <!-- ── Paso 1: OTP ── -->
        <div class="card fc-card" id="stepOtp">
            <div class="card-body p-5 text-center">
                <h4 class="fw-bold mb-3" style="color: #00204a">Verificación de Seguridad</h4>
                <p class="text-muted mb-4">Para proteger tu información, enviaremos un código de seguridad de 6 dígitos a
                    tu correo registrado (<strong><?= htmlspecialchars($data['email']) ?></strong>).</p>

                <button class="btn btn-lg px-5 mb-4" style="background:#c6db53; color:#00204a; font-weight: 600;"
                    id="btnRequestOtp">
                    <i class="fas fa-paper-plane me-2"></i> Enviar Código al Correo
                </button>

                <div id="otpInputSection" class="d-none mt-3">
                    <p class="fw-bold mb-2 text-success"><i class="fas fa-check-circle me-1"></i> Código enviado</p>
                    <p class="small text-muted mb-3">Ingresa los 6 dígitos que recibiste:</p>
                    <div class="otp-inputs d-flex justify-content-center mb-4">
                        <input type="text" maxlength="1" autofocus>
                        <input type="text" maxlength="1">
                        <input type="text" maxlength="1">
                        <input type="text" maxlength="1">
                        <input type="text" maxlength="1">
                        <input type="text" maxlength="1">
                    </div>
                    <button class="btn px-5" style="background:#01643D;color:#fff;font-weight:600;" id="btnVerifyOtp">
                        <i class="fas fa-lock-open me-2"></i> Verificar y Entrar
                    </button>
                </div>
            </div>
        </div>

    <?php else: ?>

        <!-- ── Paso 2: Descargar + subir firmado ── -->
        <div class="card fc-card" id="stepFirma">
            <div class="card-body p-4 p-md-5">

                <?php if ($esRecarga && $motivoRechazo !== ''): ?>
                    <div class="alert alert-warning border-0 rounded-3 mb-4">
                        <h6 class="fw-bold text-dark mb-2"><i class="fas fa-exclamation-triangle text-warning me-2"></i>Motivo
                            del rechazo anterior</h6>
                        <p class="mb-0 text-dark"><?= nl2br(htmlspecialchars($motivoRechazo)) ?></p>
                    </div>
                <?php endif; ?>

                <div class="d-flex align-items-start gap-3 mb-4">
                    <span class="step-num">1</span>
                    <div>
                        <h6 class="fw-bold mb-1">Descarga tu convenio</h6>
                        <p class="small text-muted mb-2">Descarga el convenio generado con los datos de tu organismo,
                            imprímelo y fírmalo de forma <u>autógrafa</u> (firma a mano; no digital).</p>
                        <button type="button" class="btn btn-outline-success btn-sm" id="btnDescargar"
                            data-empresa="<?= htmlspecialchars($data['empresa']) ?>">
                            <i class="fas fa-download me-1"></i> Descargar convenio (PDF)
                        </button>
                    </div>
                </div>

                <hr>

                <div class="d-flex align-items-start gap-3 mt-4">
                    <span class="step-num">2</span>
                    <div class="flex-grow-1">
                        <h6 class="fw-bold mb-1">Sube el convenio firmado</h6>
                        <p class="small text-muted mb-3">Escanéalo en PDF y súbelo aquí. Este enlace es de un solo uso.</p>

                        <form id="formFirma" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="upload_firmado">
                            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                            <input type="file" class="form-control mb-3" name="convenio" accept=".pdf" required>
                            <button type="submit" class="btn px-5" style="background:#01643D;color:#fff;font-weight:600;"
                                id="btnSubmitFirma">
                                <i class="fas fa-paper-plane me-2"></i> Enviar convenio firmado
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function () {
        const token = '<?= htmlspecialchars($token) ?>';
        const ENDPOINT = 'controller/practices/firma-convenio.php';

        // --- OTP ---
        $('#btnRequestOtp').click(function () {
            const btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Enviando...');

            $.post(ENDPOINT, { action: 'request_otp', token: token }, function (res) {
                btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-2"></i>Enviar Código al Correo');
                if (res.success) {
                    btn.hide();
                    $('#otpInputSection').removeClass('d-none');
                    $('.otp-inputs input').first().focus();
                    Swal.fire({ icon: 'success', title: '¡Enviado!', text: res.message, timer: 2000, showConfirmButton: false });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.message });
                }
            }, 'json').fail(function () {
                btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-2"></i>Enviar Código al Correo');
                Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión.' });
            });
        });

        $('.otp-inputs input').on('input', function () {
            if (this.value.length === 1) { $(this).next('input').focus(); }
        }).on('keydown', function (e) {
            if (e.key === 'Backspace' && this.value.length === 0) { $(this).prev('input').focus(); }
            else if (e.key === 'Enter') { $('#btnVerifyOtp').click(); }
        });

        $('#btnVerifyOtp').click(function () {
            let otp = '';
            $('.otp-inputs input').each(function () { otp += $(this).val(); });
            if (otp.length < 6) {
                Swal.fire({ icon: 'warning', text: 'Ingresa los 6 dígitos del código.' });
                return;
            }
            const btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Verificando...');

            $.post(ENDPOINT, { action: 'verify_otp', token: token, otp: otp }, function (res) {
                if (res.success) {
                    Swal.fire({ icon: 'success', title: '¡Correcto!', text: res.message, timer: 1500, showConfirmButton: false }).then(() => location.reload());
                } else {
                    btn.prop('disabled', false).html('<i class="fas fa-lock-open me-2"></i>Verificar y Entrar');
                    Swal.fire({ icon: 'error', title: 'Error', text: res.message });
                    $('.otp-inputs input').val('').first().focus();
                }
            }, 'json');
        });

        // --- Descarga asíncrona del convenio generado (fetch -> blob) ---
        $('#btnDescargar').click(function () {
            const btn = $(this);
            const empresa = (btn.data('empresa') || 'organismo').toString().replace(/[^A-Za-z0-9]+/g, '_');
            const original = btn.html();
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Descargando…');

            fetch(ENDPOINT, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=descargar&token=' + encodeURIComponent(token)
            }).then(async (r) => {
                const ct = r.headers.get('Content-Type') || '';
                if (!r.ok || ct.indexOf('application/pdf') === -1) {
                    let msg = 'No se pudo descargar el convenio.';
                    try { const j = await r.json(); msg = j.message || msg; } catch (e) {}
                    throw new Error(msg);
                }
                const blob = await r.blob();
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'Convenio_' + empresa + '.pdf';
                document.body.appendChild(a);
                a.click();
                a.remove();
                setTimeout(() => URL.revokeObjectURL(url), 4000);
            }).catch((e) => {
                Swal.fire({ icon: 'error', title: 'Error', text: e.message || 'No se pudo descargar el convenio.' });
            }).finally(() => {
                btn.prop('disabled', false).html(original);
            });
        });

        // --- Subida del firmado ---
        $('#formFirma').submit(function (e) {
            e.preventDefault();
            const btn = $('#btnSubmitFirma');
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Enviando...');

            $.ajax({
                url: ENDPOINT,
                type: 'POST',
                data: new FormData(this),
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function (res) {
                    if (res.success) {
                        Swal.fire({ icon: 'success', title: '¡Convenio enviado!', text: res.message, allowOutsideClick: false })
                            .then(() => { window.location.href = './login'; });
                    } else {
                        btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-2"></i>Enviar convenio firmado');
                        Swal.fire({ icon: 'error', title: 'Error', text: res.message });
                    }
                },
                error: function () {
                    btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-2"></i>Enviar convenio firmado');
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión.' });
                }
            });
        });
    });
</script>
