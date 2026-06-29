<?php
$token = $_GET['token'] ?? '';
if (!$token) {
    echo '<div class="alert alert-danger m-5">Enlace no válido.</div>';
    return;
}

// Obtener datos del rechazo
$data = PracticasModel::mdlGetTokenCorreccion($token);

if (!$data) {
    // Verificar si expiró o ya fue usado
    echo '<div class="container mt-5">
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
                <i class="fas fa-times-circle text-danger mb-3" style="font-size: 4rem;"></i>
                <h3 class="fw-bold">Enlace Invalido o Expirado</h3>
                <p class="text-muted">El enlace de corrección ha expirado (pasaron 72 horas) o ya ha sido utilizado para enviar una corrección. Si necesitas volver a enviar información, por favor contacta al administrador.</p>
            </div>
          </div>';
    return;
}

$camposRechazados = PracticasModel::mdlGetRechazoCampos($data['rechazo_id']);
$orgInfo = PracticasModel::mdlGetExternals($data['organismo_id']);

$hasOtpSession = isset($_SESSION['correccion_token']) && $_SESSION['correccion_token'] === $token;
?>

<style>
    body {
        background: #f8faf9;
    }

    .co-hero {
        background: linear-gradient(135deg, #00204a 0%, #01643D 100%);
        color: white;
        padding: 3rem 0 2rem 0;
        margin-bottom: -3rem;
    }

    .co-card {
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

    .field-error-card {
        border-left: 4px solid #dc3545;
        background: #fff5f5;
        margin-bottom: 1rem;
    }
</style>

<div class="co-hero">
    <div class="container text-center">
        <div
            style="width: 70px; height: 70px; background: rgba(255,255,255,0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 1rem auto;">
            <i class="fas fa-clipboard-check"></i>
        </div>
        <h2 class="fw-bold">Corrección de Solicitud</h2>
        <p class="opacity-75 mb-0"><?= htmlspecialchars($orgInfo['empresa']) ?></p>
    </div>
</div>

<div class="container position-relative"
    style="z-index: 10; padding-top: 4rem; padding-bottom: 5rem; max-width: 800px;">

    <?php if (!$hasOtpSession): ?>
        <!-- ── Paso 1: OTP ── -->
        <div class="card co-card" id="stepOtp">
            <div class="card-body p-5 text-center">
                <h4 class="fw-bold mb-3" style="color: #00204a">Verificación de Seguridad</h4>
                <p class="text-muted mb-4">Para proteger tu información, enviaremos un código de seguridad de 6 dígitos a tu
                    correo registrado (<strong><?= htmlspecialchars($data['email']) ?></strong>).</p>

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
                    <button class="btn btn-unimo px-5" id="btnVerifyOtp">
                        <i class="fas fa-lock-open me-2"></i> Verificar y Entrar
                    </button>
                </div>
            </div>
        </div>

    <?php else: ?>

        <!-- ── Paso 2: Corrección ── -->
        <div class="card co-card" id="stepCorrection">
            <div class="card-body p-4 p-md-5">
                <div class="alert alert-warning border-0 rounded-3 mb-4">
                    <h5 class="fw-bold text-dark mb-2"><i class="fas fa-exclamation-triangle text-warning me-2"></i>Motivo
                        del rechazo</h5>
                    <p class="mb-0 text-dark"><?= nl2br(htmlspecialchars($data['motivo_general'])) ?></p>
                </div>

                <h5 class="fw-bold mb-4 border-bottom pb-2">Campos a corregir</h5>

                <form id="formCorreccion" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="save_correccion">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                    <?php foreach ($camposRechazados as $c): ?>
                        <div class="card field-error-card border-0 shadow-sm p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="fw-bold mb-0 text-danger"><?= htmlspecialchars($c['campo_label']) ?></h6>
                                <span class="badge bg-danger">Dato <?= htmlspecialchars($c['estado']) ?></span>
                            </div>
                            <p class="small text-muted mb-2"><strong>Motivo:</strong> <?= htmlspecialchars($c['motivo']) ?></p>
                            <?php if ($c['observacion']): ?>
                                <p class="small text-muted mb-3"><strong>Observación:</strong>
                                    <?= htmlspecialchars($c['observacion']) ?></p>
                            <?php endif; ?>

                            <div class="mt-2">
                                <?php if (strpos($c['campo'], 'doc:') === 0): ?>
                                    <!-- Subida de documento -->
                                    <label class="form-label small fw-bold">Sube el documento corregido (PDF):</label>
                                    <?php $safeName = str_replace([':', ' '], '_', $c['campo']); ?>
                                    <input type="file" class="form-control form-control-sm correction-input"
                                        name="file_<?= htmlspecialchars($safeName) ?>" accept=".pdf" required>
                                <?php else: ?>
                                    <!-- Campo de texto -->
                                    <label class="form-label small fw-bold">Ingresa el valor correcto:</label>
                                    <input type="text" class="form-control form-control-sm correction-input"
                                        name="<?= htmlspecialchars($c['campo']) ?>"
                                        value="<?= htmlspecialchars($orgInfo[$c['campo']] ?? '') ?>" required>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="text-end mt-4 pt-3 border-top">
                        <button type="submit" class="btn btn-unimo px-5 rounded-pill" id="btnSubmitCorreccion">
                            <i class="fas fa-save me-2"></i> Enviar Correcciones
                        </button>
                    </div>
                </form>
            </div>
        </div>

    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function () {
        const token = '<?= htmlspecialchars($token) ?>';

        // --- Lógica OTP ---
        $('#btnRequestOtp').click(function () {
            const btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Enviando...');

            $.post('controller/practices/correccion-organismo.php', { action: 'request_otp', token: token }, function (res) {
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

        // Inputs OTP
        $('.otp-inputs input').on('input', function () {
            if (this.value.length === 1) {
                $(this).next('input').focus();
            }
        }).on('keydown', function (e) {
            if (e.key === 'Backspace' && this.value.length === 0) {
                $(this).prev('input').focus();
            } else if (e.key === 'Enter') {
                $('#btnVerifyOtp').click();
            }
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

            $.post('controller/practices/correccion-organismo.php', { action: 'verify_otp', token: token, otp: otp }, function (res) {
                if (res.success) {
                    Swal.fire({ icon: 'success', title: '¡Correcto!', text: res.message, timer: 1500, showConfirmButton: false }).then(() => {
                        location.reload();
                    });
                } else {
                    btn.prop('disabled', false).html('<i class="fas fa-lock-open me-2"></i>Verificar y Entrar');
                    Swal.fire({ icon: 'error', title: 'Error', text: res.message });
                    $('.otp-inputs input').val('');
                    $('.otp-inputs input').first().focus();
                }
            }, 'json');
        });

        // --- Lógica Corrección ---
        $('#formCorreccion').submit(function (e) {
            e.preventDefault();

            const btn = $('#btnSubmitCorreccion');
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Enviando...');

            const formData = new FormData(this);

            // Recopilar info para el JSON interno
            const camposArr = [];
            $('.correction-input').each(function () {
                const name = $(this).attr('name');
                const esFile = $(this).attr('type') === 'file';
                let label = $(this).closest('.field-error-card').find('h6').text();
                let valAnterior = ''; // Opcional, pero útil

                camposArr.push({
                    campo: esFile ? name.replace('file_', 'doc:') : name,
                    campo_label: label,
                    valor_nuevo: esFile ? (this.files.length > 0 ? this.files[0].name : '') : $(this).val()
                });
            });
            formData.append('campos_corregidos', JSON.stringify(camposArr));

            $.ajax({
                url: 'controller/practices/correccion-organismo.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function (res) {
                    if (res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Correcciones enviadas!',
                            text: res.message,
                            allowOutsideClick: false
                        }).then(() => {
                            window.location.href = './inicio'; // Redirigir a inicio
                        });
                    } else {
                        btn.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Enviar Correcciones');
                        Swal.fire({ icon: 'error', title: 'Error', text: res.message });
                    }
                },
                error: function () {
                    btn.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Enviar Correcciones');
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión.' });
                }
            });
        });
    });
</script>