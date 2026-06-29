<?php
// carta-practicas-editor.php

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
</style>

<div class="container row has-rail">
  <div class="mb-4">
    <h1 class="h4 fw-bold mb-1"><i class="fa-solid fa-briefcase me-2 text-info"></i>Carta de Presentación de Prácticas Profesionales</h1>
    <p class="text-muted mb-0" style="font-size:.85rem">Prácticas Profesionales · Configura el texto, firma y diseño del documento</p>
  </div>

    <div id="alertBox"></div>

    <form class="col-md-12" id="cfgForm" onsubmit="return false;">

        <!-- ENCABEZADO -->
        <div class="form-section">
            <h2 class="h6">Encabezado</h2>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Color barra superior</label>
                    <input type="color" class="form-control form-control-color" id="header_bar_color" value="#006837">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Logo superior (imagen)</label>
                    <input type="file" class="form-control img-hover-input" id="header_logo_file" accept="image/*">
                    <div class="help-hover">Pasa el cursor para previsualizar</div>
                    <input type="hidden" id="header_logo_url">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Ancho logo sup. (px)</label>
                    <input type="number" class="form-control" id="layout_header_logo_width" value="130" min="50">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Línea de ciudad y fecha</label>
                    <input type="text" class="form-control" id="header_city_line"
                        placeholder="Morelia, Michoacán, México, a {{fecha}}.">
                    <div class="form-text">Usa <code class="k" hover="Fecha de hoy">{{fecha}}</code> para la fecha automática.</div>
                </div>

                <div class="col-md-8">
                    <label class="form-label">Asunto</label>
                    <input type="text" class="form-control" id="header_subject"
                        value="Carta de Presentación de Prácticas Profesionales">
                </div>

                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="header_show_folio" checked>
                        <label class="form-check-label" for="header_show_folio">Mostrar FOLIO</label>
                    </div>
                </div>
            </div>
        </div>

        <!-- CUERPO -->
        <div class="form-section">
            <h2 class="h6">Texto principal</h2>
            <p class="text-muted small mb-2">
                El destinatario (empresa, cargo y responsable) lo escribe el alumno al generar la carta. Aquí editas el cuerpo del documento, incluyendo párrafos y la lista de documentos solicitados.
            </p>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Párrafos y lista de la carta</label>
                    <div id="body_paragraphs_editor" class="form-control" style="height:auto; padding:0;"></div>
                    <input type="hidden" id="body_paragraphs_html">
                    <div class="form-text">
                        Variables disponibles:
                        <code class="k" hover="Nombre del alumno">{{studentName}}</code>
                        <code class="k" hover="Matrícula">{{matricula}}</code>
                        <code class="k" hover="Programa">{{degreeName}}</code>
                        <code class="k" hover=""el"/"la"">{{genero}}</code>
                        <code class="k" hover="Fecha de hoy">{{fecha}}</code>
                        <code class="k" hover="Folio generado">{{folio}}</code>
                        <code class="k" hover="Nombre empresa/institución">{{empresa}}</code>
                        <code class="k" hover="Cargo del responsable">{{cargoResponsable}}</code>
                        <code class="k" hover="Nombre del responsable">{{responsable}}</code>
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
                <div class="col-md-2">
                    <label class="form-label">Ancho sello (px)</label>
                    <input type="number" class="form-control" id="sig_seal_width" value="240" min="50">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Top sello (px)</label>
                    <input type="number" class="form-control" id="sig_seal_top" value="-60">
                    <div class="form-text">Negativo = sube.</div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Left sello (%)</label>
                    <input type="number" class="form-control" id="sig_seal_left_percent" value="50" min="0" max="100">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Opacidad sello (0-1)</label>
                    <input type="number" step="0.05" min="0" max="1" class="form-control" id="sig_seal_opacity" value="0.8">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Nombre del firmante</label>
                    <input type="text" class="form-control" id="sig_signer_name" placeholder="Ej: MGH Karla Mariana Fonseca Munguia">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Cargo del firmante</label>
                    <input type="text" class="form-control" id="sig_signer_role" placeholder="Ej: Coordinador de Prácticas Profesionales UNIMO">
                </div>

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

        <!-- PIE DE PÁGINA -->
        <div class="form-section">
            <h2 class="h6">Pie de página</h2>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Color barra inferior</label>
                    <input type="color" class="form-control form-control-color" id="footer_bottom_bar_color" value="#006837">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Logo pie (imagen)</label>
                    <input type="file" class="form-control img-hover-input" id="footer_logo_file" accept="image/*">
                    <div class="help-hover">Pasa el cursor para previsualizar</div>
                    <input type="hidden" id="footer_logo_url">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Ancho logo pie (px)</label>
                    <input type="number" class="form-control" id="layout_footer_logo_width" value="130" min="50">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Texto de contacto</label>
                    <input type="text" class="form-control" id="footer_contact_line" placeholder="Tel. 52 (443) 324 0439 · contacto@unimontrer.edu.mx">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Texto barra inferior</label>
                    <input type="text" class="form-control" id="footer_bottom_text" value="UNIVERSIDAD MONTRER · Universidad en movimiento · www.unimontrer.edu.mx">
                </div>
            </div>
        </div>

        <!-- DISEÑO -->
        <div class="form-section">
            <h2 class="h6">Diseño</h2>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Fuente</label>
                    <input type="text" class="form-control" id="layout_font_family" value="Arial, sans-serif">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tamaño (pt)</label>
                    <input type="number" class="form-control" id="layout_font_size_pt" value="12" min="8">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Padding contenido (px)</label>
                    <input type="number" class="form-control" id="layout_content_padding_px" value="30" min="0">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Margen página (px)</label>
                    <input type="number" class="form-control" id="layout_page_margin_px" value="0" min="0">
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
                    <code class="k" hover=""el"/"la"">{{genero}}</code>
                    <code class="k" hover="Fecha de hoy">{{fecha}}</code>
                    <code class="k" hover="Folio generado">{{folio}}</code>
                    <code class="k" hover="Nombre empresa/institución">{{empresa}}</code>
                    <code class="k" hover="Cargo del responsable">{{cargoResponsable}}</code>
                    <code class="k" hover="Nombre del responsable">{{responsable}}</code>
                </div>
                <small class="d-block mt-3 text-muted">Tip: haz clic en cualquier chip para copiarlo y pégalo donde lo necesites.</small>
            </div>
        </div>

        <div class="sticky-actions d-flex gap-2">
            <button class="btn btn-primary" id="btnSave">Guardar cambios</button>
            <button class="btn btn-light" id="btnReload">Reiniciar</button>
        </div>
    </aside>
</div>

<script>
(function(){
  var dep='view/assets/js/ajax/config/carta_practicas_editor.js';
  function loadDep(){ var s=document.createElement('script'); s.src=dep; document.head.appendChild(s); }
  if(window.Quill){ loadDep(); return; }
  var q=document.createElement('script');
  q.src='https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js';
  q.onload=loadDep;
  document.head.appendChild(q);
})();
</script>
