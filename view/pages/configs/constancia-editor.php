<?php
// constancia-editor.php

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];
?>
<meta name="csrf-token" content="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

<style>
  #imgHoverPreviewBubble { position:fixed; display:none; pointer-events:none; z-index:9999; background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:6px; box-shadow:0 4px 18px rgba(0,0,0,.12); }
  #imgHoverPreviewBubble img { max-width:240px; max-height:180px; display:block; }
  .membrete-nota { font-size:.8rem; color:#475569; background:#f1f5f9; border-left:3px solid #01643D; border-radius:4px; padding:.5rem .7rem; margin-bottom:1rem; }
</style>

<div class="container row has-rail">
  <div class="mb-4">
    <h1 class="h4 fw-bold mb-1"><i class="fa-solid fa-award me-2 text-danger"></i>Constancia de Acreditación</h1>
    <p class="text-muted mb-0" style="font-size:.85rem">Prácticas Profesionales · Configura el texto, firma y diseño del documento</p>
  </div>
  <div id="alertBox"></div>

    <form class="col-md-12" id="cfgForm" onsubmit="return false;">

        <!-- ENCABEZADO -->
        <div class="form-section">
            <h2 class="h6">Encabezado</h2>
            <p class="membrete-nota">
                <i class="fa-solid fa-circle-info me-1"></i>
                El membrete institucional (barra lateral con el escudo, los domicilios de los campus y el QR,
                más la barra verde inferior) viene de la <strong>plantilla oficial</strong>, la misma de la
                carta de presentación. No se edita aquí.
            </p>
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Asunto</label>
                    <input type="text" class="form-control" id="header_subject"
                        value="Constancia de Acreditación de Prácticas Profesionales">
                </div>
                <div class="col-md-4 d-flex align-items-end gap-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="header_show_folio" checked>
                        <label class="form-check-label" for="header_show_folio">Mostrar FOLIO</label>
                    </div>
                    <div class="form-text">Puedes usar <code class="k" hover="Fecha de hoy">{{fecha}}</code> en textos.
                    </div>
                </div>
            </div>
        </div>

        <!-- RESUMEN -->
        <div class="form-section">
            <h2 class="h6">Tarjeta de resumen</h2>
            <div class="row g-3">
                <div class="col-md-2 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="summary_show_card" checked>
                        <label class="form-check-label" for="summary_show_card">Mostrar</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Horas cubiertas</label>
                    <input type="number" class="form-control" id="summary_hours" value="360" min="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Condición</label>
                    <input type="text" class="form-control" id="summary_condition" value="ACREDITADO">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Título de la tarjeta</label>
                    <input type="text" class="form-control" id="summary_title" value="Resumen de acreditación">
                </div>
            </div>
        </div>

        <!-- DESTINATARIO -->
        <div class="form-section">
            <h2 class="h6">Destinatario</h2>
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">Nombre</label><input type="text" class="form-control"
                        id="recipient_name" placeholder="L.A.E. Nombre Apellidos"></div>
                <div class="col-md-4"><label class="form-label">Cargo</label><input type="text" class="form-control"
                        id="recipient_role" placeholder="Directora de ..."></div>
                <div class="col-md-4"><label class="form-label">Institución</label><input type="text"
                        class="form-control" id="recipient_org" placeholder="Universidad Montrer"></div>
            </div>
        </div>

        <!-- CUERPO -->
        <div class="form-section">
            <h2 class="h6">Texto principal</h2>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Párrafos</label>
                    <div id="body_paragraphs_editor" class="form-control" style="height:auto; padding:0;"></div>
                    <input type="hidden" id="body_paragraphs_html">
                    <div class="form-text">
                        Variables disponibles:
                        <code class="k" hover="Nombre del alumno">{{studentName}}</code>
                        <code class="k" hover="Matrícula">{{matricula}}</code>
                        <code class="k" hover="Programa">{{degreeName}}</code>
                        <code class="k" hover="‘el alumno’ / ‘la alumna’">{{generoArt}}</code>
                        <code class="k" hover="Organismo receptor">{{nameOrganismo}}</code>
                        <code class="k" hover="Fecha de hoy">{{fecha}}</code>
                        <code class="k" hover="Folio generado">{{folio}}</code>
                    </div>
                </div>
            </div>
        </div>

        <!-- FIRMA / SELLO -->
        <div class="form-section">
            <h2 class="h6">Firma y sello</h2>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Leyenda sobre la firma</label>
                    <input type="text" class="form-control" id="sig_legend" value="ATENTAMENTE">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Ancho firma (px)</label>
                    <input type="number" class="form-control" id="sig_signature_width" value="200" min="50">
                </div>

                <!-- Sello: FILE + hidden -->
                <div class="col-md-2"><label class="form-label">Ancho sello (px)</label><input type="number"
                        class="form-control" id="sig_seal_width" value="220" min="50"></div>
                <div class="col-md-2"><label class="form-label">Top sello (px)</label><input type="number"
                        class="form-control" id="sig_seal_top" value="-52"></div>
                <div class="col-md-2"><label class="form-label">Left sello (%)</label><input type="number"
                        class="form-control" id="sig_seal_left_percent" value="60" min="0" max="100"></div>
                <div class="col-md-2"><label class="form-label">Opacidad sello (0-1)</label><input type="number"
                        step="0.05" min="0" max="1" class="form-control" id="sig_seal_opacity" value="0.85"></div>
                <div class="col-md-5"><label class="form-label">Nombre del firmante</label><input type="text"
                        class="form-control" id="sig_signer_name" placeholder="C.P. Nombre Apellidos"></div>
                <div class="col-md-5"><label class="form-label">Cargo del firmante</label><input type="text"
                        class="form-control" id="sig_signer_role"
                        placeholder="Coordinador(a) de Prácticas Profesionales UNIMO"></div>

                <!-- Firma: FILE + hidden -->
                <div class="col-md-5">
                    <label class="form-label">Firma (imagen)</label>
                    <input type="file" class="form-control img-hover-input" id="sig_signature_file" accept="image/*">
                    <div class="help-hover">Pasa el cursor para previsualizar</div>
                    <input type="hidden" id="sig_signature_img_url">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Sello (imagen)</label>
                    <input type="file" class="form-control img-hover-input" id="sig_seal_file" accept="image/*">
                    <div class="help-hover">Pasa el cursor para previsualizar</div>
                    <input type="hidden" id="sig_seal_img_url">
                </div>
            </div>
        </div>

        <!-- DISEÑO -->
        <div class="form-section">
            <h2 class="h6">Diseño</h2>
            <p class="membrete-nota">
                <i class="fa-solid fa-circle-info me-1"></i>
                Los márgenes los fija la plantilla: el texto arranca justo a la derecha de la barra lateral
                y termina antes de la barra verde.
            </p>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Fuente</label><input type="text" class="form-control"
                        id="layout_font_family" value="Arial, Helvetica, sans-serif"></div>
                <div class="col-md-6"><label class="form-label">Tamaño (pt)</label><input type="number"
                        class="form-control" id="layout_font_size_pt" value="11" min="8">
                    <div class="form-text">La plantilla usa 11 pt. Súbelo con cuidado: la columna es angosta.</div>
                </div>
            </div>
        </div>
    </form>

    <!-- RAIL DERECHO -->
    <aside class="fixed-rail" aria-label="Panel fijo de variables y acciones">
        <div class="vars-fixed card">
            <div class="card-body">
                <strong>Variables automáticas:</strong>
                <div class="mt-2 d-flex flex-wrap gap-2">
                    <code class="k" hover="Nombre del alumno">{{studentName}}</code>
                    <code class="k" hover="Matrícula">{{matricula}}</code>
                    <code class="k" hover="Programa">{{degreeName}}</code>
                    <code class="k" hover="“el alumno”/“la alumna”">{{generoArt}}</code>
                    <code class="k" hover="Organismo receptor">{{nameOrganismo}}</code>
                    <code class="k" hover="Fecha de hoy">{{fecha}}</code>
                    <code class="k" hover="Folio">{{folio}}</code>
                </div>
                <small class="d-block mt-3 text-muted">Tip: haz clic en cualquier chip para copiarlo y pégalo en los
                    campos/Quill.</small>

                <button type="button" class="btn btn-light btn-sm mt-3 w-100" id="btnPreviewPdf">
                    <i class="fa-solid fa-file-pdf me-1"></i> Ver PDF de prueba
                </button>
                <small class="d-block mt-2 text-muted" style="font-size:.75rem">
                    Muestra el documento aquí mismo con datos de ejemplo, usando lo que tienes
                    en pantalla <strong>aunque no lo hayas guardado</strong>.
                    No consume folio ni marca prácticas como finalizadas.
                </small>
            </div>
        </div>

        <div class="sticky-actions d-flex flex-wrap gap-2">
            <button class="btn btn-primary" id="btnSave">Guardar</button>
            <button class="btn btn-light" id="btnReload">Reiniciar</button>
        </div>

    </aside>
</div>

<script>
(function(){
  var dep='view/assets/js/ajax/config/constancia_editor.js';
  function loadDep(){
    var p=document.createElement('script'); p.src='view/assets/js/ajax/config/preview_pdf.js'; document.head.appendChild(p);
    var s=document.createElement('script'); s.src=dep; document.head.appendChild(s);
  }
  if(window.Quill){ loadDep(); return; }
  var q=document.createElement('script');
  q.src='https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js';
  q.onload=loadDep;
  document.head.appendChild(q);
})();
</script>

<!-- Bubble global de preview (se controla desde JS) -->
<div id="imgHoverPreviewBubble"><img alt="preview"></div>