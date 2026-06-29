<?php
// convenio-editor.php — Editor del Convenio de Prácticas Profesionales

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
  .cfg-body #content_html_editor .ql-editor { min-height: 340px; }
  .rep-highlight { border:1px solid #bbf7d0 !important; background:#f0fdf4; }
  .rep-highlight h2 { color:#15803d !important; }
</style>

<div class="container row has-rail">
  <div class="mb-4">
    <h1 class="h4 fw-bold mb-1"><i class="fa-solid fa-file-signature me-2 text-success"></i>Convenio de Prácticas Profesionales</h1>
    <p class="text-muted mb-0" style="font-size:.85rem">
      Edita el documento que el Organismo Receptor descargará para firmar de forma autógrafa.
      Aquí <strong>no</strong> se coloca firma ni sello: solo el nombre del representante de la institución.
      Al guardar, se genera automáticamente el PDF.
    </p>
  </div>
  <div id="alertBox"></div>

  <form class="col-md-12" id="cfgForm" onsubmit="return false;">

    <!-- ENCABEZADO -->
    <div class="form-section">
      <h2 class="h6">Encabezado (membrete superior)</h2>
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Color barra superior</label>
          <input type="color" class="form-control form-control-color" id="header_bar_color" value="#006837">
        </div>
        <div class="col-md-5">
          <label class="form-label">Logo superior (imagen)</label>
          <input type="file" class="form-control img-hover-input" id="header_logo_file" accept="image/*">
          <div class="help-hover">Pasa el cursor para previsualizar</div>
          <input type="hidden" id="header_logo_url">
        </div>
        <div class="col-md-2">
          <label class="form-label">Ancho logo (px)</label>
          <input type="number" class="form-control" id="layout_header_logo_width" value="150" min="50">
        </div>
        <div class="col-md-2">
          <label class="form-label">URL logo (opcional)</label>
          <input type="text" class="form-control" id="header_logo_url_text" placeholder="https://...">
        </div>
        <div class="col-md-8">
          <label class="form-label">Título del documento</label>
          <input type="text" class="form-control" id="header_title" value="CONVENIO DE PRÁCTICAS PROFESIONALES">
        </div>
        <div class="col-md-4">
          <label class="form-label">Línea de fecha / ciudad</label>
          <input type="text" class="form-control" id="header_city_line" value="Morelia, Michoacán, a {{fecha}}">
          <div class="form-text">Usa <code class="k" hover="Fecha de hoy">{{fecha}}</code></div>
        </div>
      </div>
    </div>

    <!-- REPRESENTANTE DE LA INSTITUCIÓN -->
    <div class="form-section rep-highlight">
      <h2 class="h6"><i class="fa-solid fa-user-tie me-1"></i>Representante de la institución</h2>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Nombre del representante <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="sig_signer_name" placeholder="Lic. Nombre Apellidos">
          <div class="form-text">
            Se inserta en el cuerpo con <code class="k" hover="Nombre del representante de la institución">{{repUniversidad}}</code>
            y en el bloque de firma de “La Universidad”.
          </div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Cargo del representante</label>
          <input type="text" class="form-control" id="sig_signer_role" placeholder="Representante Legal – Instituto Montrer, S.C.">
        </div>
      </div>
    </div>

    <!-- CUERPO DEL CONVENIO -->
    <div class="form-section">
      <h2 class="h6">Cuerpo del convenio</h2>
      <div class="row g-3">
        <div class="col-12">
          <label class="form-label">Texto del documento</label>
          <div id="content_html_editor" class="form-control" style="height:auto; padding:0;"></div>
          <input type="hidden" id="content_html">
          <div class="form-text">
            Variables: <code class="k" hover="Fecha de hoy">{{fecha}}</code>
            <code class="k" hover="Nombre del representante de la institución">{{repUniversidad}}</code>.
            Las firmas autógrafas se agregan automáticamente al final (no las escribas aquí).
          </div>
        </div>
      </div>
    </div>

    <!-- ETIQUETAS DE FIRMAS -->
    <div class="form-section">
      <h2 class="h6">Bloques de firma (4 campos: 2 representantes + 2 testigos)</h2>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Etiqueta del representante de “La Empresa”</label>
          <input type="text" class="form-control" id="sig_empresa_label" value="Representante Legal de “La Empresa”">
          <div class="form-text">Se firma de forma autógrafa (queda en blanco para el organismo).</div>
        </div>
        <div class="col-md-3">
          <label class="form-label">Etiqueta Testigo 1</label>
          <input type="text" class="form-control" id="sig_testigo1_label" value="Testigo">
        </div>
        <div class="col-md-3">
          <label class="form-label">Etiqueta Testigo 2</label>
          <input type="text" class="form-control" id="sig_testigo2_label" value="Testigo">
        </div>
      </div>
    </div>

    <!-- PIE -->
    <div class="form-section">
      <h2 class="h6">Pie de página (membrete inferior)</h2>
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Color barra inferior</label>
          <input type="color" class="form-control form-control-color" id="footer_bottom_bar_color" value="#006837">
        </div>
        <div class="col-md-5">
          <label class="form-label">Logo pie (imagen)</label>
          <input type="file" class="form-control img-hover-input" id="footer_logo_file" accept="image/*">
          <div class="help-hover">Pasa el cursor para previsualizar</div>
          <input type="hidden" id="footer_logo_url">
        </div>
        <div class="col-md-2">
          <label class="form-label">Ancho logo (px)</label>
          <input type="number" class="form-control" id="layout_footer_logo_width" value="130" min="50">
        </div>
        <div class="col-md-2">
          <label class="form-label">URL logo (opcional)</label>
          <input type="text" class="form-control" id="footer_logo_url_text" placeholder="https://...">
        </div>
        <div class="col-md-7">
          <label class="form-label">Contacto</label>
          <input type="text" class="form-control" id="footer_contact_line"
            placeholder="Av. Lázaro Cárdenas #1760, Col. Chapultepec Sur, Morelia, Mich.">
        </div>
        <div class="col-md-12">
          <label class="form-label">Texto barra inferior</label>
          <input type="text" class="form-control" id="footer_bottom_text"
            value="INSTITUTO MONTRER, S.C. · Universidad en movimiento · www.unimontrer.edu.mx">
        </div>
      </div>
    </div>

    <!-- DISEÑO -->
    <div class="form-section">
      <h2 class="h6">Diseño</h2>
      <div class="row g-3">
        <div class="col-md-3"><label class="form-label">Fuente</label>
          <input type="text" class="form-control" id="layout_font_family" value="Arial, Helvetica, sans-serif"></div>
        <div class="col-md-2"><label class="form-label">Tamaño (pt)</label>
          <input type="number" class="form-control" id="layout_font_size_pt" value="11" min="8"></div>
        <div class="col-md-3"><label class="form-label">Padding contenido (px)</label>
          <input type="number" class="form-control" id="layout_content_padding_px" value="40" min="0"></div>
        <div class="col-md-2"><label class="form-label">Margen página (px)</label>
          <input type="number" class="form-control" id="layout_page_margin_px" value="0" min="0"></div>
      </div>
    </div>
  </form>

  <!-- RAIL DERECHO -->
  <aside class="fixed-rail" aria-label="Panel fijo de acciones">
    <div class="vars-fixed card">
      <div class="card-body">
        <strong>Variables automáticas:</strong>
        <div class="mt-2 d-flex flex-wrap gap-2">
          <code class="k" hover="Fecha de hoy">{{fecha}}</code>
          <code class="k" hover="Nombre del representante de la institución">{{repUniversidad}}</code>
        </div>
        <small class="d-block mt-3 text-muted">
          El PDF lleva membrete (header y footer) y paginación “Página X de Y” en cada hoja.
          Al final se incluyen 4 firmas: 2 representantes y 2 testigos.
        </small>
        <a class="btn btn-light btn-sm mt-3 w-100" id="btnPreviewPdf"
           href="controller/ajax/generarConvenio.php" target="_blank" rel="noopener">
          <i class="fa-solid fa-file-pdf me-1"></i> Ver PDF generado
        </a>
      </div>
    </div>

    <div class="sticky-actions d-flex flex-wrap gap-2">
      <button class="btn btn-primary" id="btnSave">Guardar y generar PDF</button>
      <button class="btn btn-light" id="btnReload">Reiniciar</button>
    </div>
  </aside>
</div>

<script>
(function(){
  var dep='view/assets/js/ajax/config/convenio_editor.js';
  function loadDep(){ var s=document.createElement('script'); s.src=dep; document.head.appendChild(s); }
  if(window.Quill){ loadDep(); return; }
  if(!document.querySelector('link[href*="quill"]')){
    var l=document.createElement('link'); l.rel='stylesheet';
    l.href='https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css';
    document.head.appendChild(l);
  }
  var q=document.createElement('script');
  q.src='https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js';
  q.onload=loadDep;
  document.head.appendChild(q);
})();
</script>

<div id="imgHoverPreviewBubble"><img alt="preview"></div>
