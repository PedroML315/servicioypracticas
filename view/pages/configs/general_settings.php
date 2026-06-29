<?php
// view/pages/configs/general_settings.php

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];
?>
<meta name="csrf-token" content="<?= htmlspecialchars($csrf) ?>">

<style>
    .gs-section {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 20px 24px;
        margin-bottom: 20px;
        background: #fff;
    }

    .gs-section h2 {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 4px;
        color: #111827;
    }

    .gs-section .section-desc {
        font-size: .875rem;
        color: #6b7280;
        margin-bottom: 16px;
    }

    .gs-actions {
        display: flex;
        gap: 10px;
        margin-top: 8px;
    }

    #gs-alert {
        max-width: 680px;
    }
</style>

<div class="container mt-0" style="max-width:760px">
    <div class="mb-4">
        <h1 class="h4 fw-bold mb-1"><i class="fa-solid fa-sliders me-2"></i>Configuración General</h1>
        <p class="text-muted mb-0" style="font-size:.85rem">Sistema · Ajusta los parámetros globales de la plataforma</p>
    </div>

    <div id="gs-alert"></div>

    <!-- ===== Correo para solicitudes de capacitación ===== -->
    <div class="gs-section">
        <h2><i class="fa-solid fa-envelope me-2 text-primary"></i>Correo para solicitudes de capacitación</h2>
        <p class="section-desc">
            Cuando un organismo externo solicita una capacitación para un alumno, se envía un correo a esta dirección.
            Puede ser el correo del coordinador de prácticas o cualquier responsable de atender dichas solicitudes.
        </p>
        <div class="row g-3 align-items-end">
            <div class="col-md-8">
                <label class="form-label" for="email_capacitacion">Correo electrónico de destino</label>
                <input
                    type="email"
                    class="form-control"
                    id="email_capacitacion"
                    placeholder="ejemplo@unimontrer.edu.mx"
                    autocomplete="off"
                    disabled>
                <div class="form-text">Deja en blanco para usar el correo por defecto del sistema.</div>
            </div>
            <div class="col-md-4">
                <div class="gs-actions">
                    <button class="btn btn-primary" id="btnGuardarGS" disabled>
                        <i class="fa-solid fa-floppy-disk me-1"></i> Guardar
                    </button>
                    <button class="btn btn-light" id="btnRecargarGS" disabled>
                        <i class="fa-solid fa-rotate-right me-1"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    const ENDPOINT = 'controller/general-settings-config.php';
    let csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    function showAlert(msg, type = 'success') {
        const box = document.getElementById('gs-alert');
        box.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${msg}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>`;
        setTimeout(() => { box.innerHTML = ''; }, 4000);
    }

    function setLoading(state) {
        document.getElementById('email_capacitacion').disabled = state;
        document.getElementById('btnGuardarGS').disabled = state;
        document.getElementById('btnRecargarGS').disabled = state;
    }

    async function loadConfig() {
        setLoading(true);
        try {
            const resp = await fetch(ENDPOINT, { method: 'GET', credentials: 'same-origin' });
            const json = await resp.json();
            if (json.ok) {
                csrfToken = json.csrf || csrfToken;
                document.getElementById('email_capacitacion').value = json.config?.email_capacitacion || '';
            } else {
                showAlert('Error al cargar la configuración: ' + (json.error || ''), 'danger');
            }
        } catch (e) {
            showAlert('Error de red al cargar la configuración.', 'danger');
        } finally {
            setLoading(false);
        }
    }

    async function saveConfig() {
        const email = document.getElementById('email_capacitacion').value.trim();
        setLoading(true);
        try {
            const resp = await fetch(ENDPOINT, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ email_capacitacion: email })
            });
            const json = await resp.json();
            if (json.ok) {
                showAlert('<i class="fa-solid fa-circle-check me-1"></i>' + (json.message || 'Configuración guardada correctamente.'), 'success');
            } else {
                showAlert('Error: ' + (json.error || 'No se pudo guardar.'), 'danger');
            }
        } catch (e) {
            showAlert('Error de red al guardar la configuración.', 'danger');
        } finally {
            setLoading(false);
        }
    }

    document.getElementById('btnGuardarGS').addEventListener('click', saveConfig);
    document.getElementById('btnRecargarGS').addEventListener('click', loadConfig);

    // Carga inicial
    loadConfig();
})();
</script>
