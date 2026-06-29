<?php
// admin/email-templates-editor.php

// CSRF para incrustar en meta y usarlo por AJAX
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];
?>
<meta name="csrf-token" content="<?= htmlspecialchars($csrf) ?>">

<style>
  /* Chips de variables */
  code.k {
    background: #f6f8fa;
    padding: 3px 7px;
    border-radius: 6px;
    font-family: monospace;
    font-size: .9rem;
    color: #0d6efd;
    cursor: pointer;
    position: relative;
    transition: all .2s ease-in-out;
  }

  code.k:hover {
    background: #0d6efd;
    color: #fff;
    box-shadow: 0 2px 6px rgba(13, 110, 253, .3);
    transform: translateY(-2px);
  }

  code.k::after {
    content: attr(hover);
    position: absolute;
    bottom: 125%;
    left: 50%;
    transform: translateX(-50%);
    background: #212529;
    color: #fff;
    padding: 5px 8px;
    border-radius: 6px;
    font-size: .75rem;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    transition: opacity .2s, transform .2s;
    z-index: 10;
  }

  code.k:hover::after {
    opacity: 1;
    transform: translateX(-50%) translateY(-2px);
  }

  code.k.copied::after {
    content: "✅ Copiado";
    background: #198754;
  }

  /* Quill */
  #body_paragraphs_editor .ql-container {
    border: none;
  }

  #body_paragraphs_editor .ql-toolbar {
    border: 1px solid #dee2e6;
    border-radius: .375rem .375rem 0 0;
  }

  #body_paragraphs_editor .ql-container.ql-snow {
    border: 1px solid #dee2e6;
    border-top: 0;
    border-radius: 0 0 .375rem .375rem;
  }

  #body_paragraphs_editor .ql-editor {
    min-height: 220px;
  }

  .ql-container {
    font-size: 18px !important;
  }

  #imgHoverPreviewBubble {
    position: absolute;
    display: none;
    pointer-events: none;
    z-index: 9999;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 6px;
    box-shadow: 0 4px 18px rgba(0, 0, 0, .12);
  }

  #imgHoverPreviewBubble img {
    max-width: 240px;
    max-height: 180px;
    display: block;
  }

</style>

<div class="container row has-rail">
  <div class="mb-4">
    <h1 class="h4 fw-bold mb-1"><i class="fa-solid fa-file-circle-check me-2 text-warning"></i>Carta de Aceptación de Servicio Social</h1>
    <p class="text-muted mb-0" style="font-size:.85rem">SS Interno · Configura el texto, firma y diseño del documento</p>
  </div>
  <div id="alertBox"></div>

  <form class="col-md-12" id="cfgForm" onsubmit="return false;">
    <!-- ENCABEZADO -->
    <div class="form-section">
      <h2 class="h6">Encabezado (parte de arriba)</h2>
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Color de la franja superior</label>
          <input type="color" class="form-control form-control-color" id="header_bar_color" value="#006837">
          <div class="form-text">Elige el color de la barra de arriba.</div>
        </div>
        <div class="col-md-4">
          <label class="form-label">Logo superior (imagen)</label>
          <input type="file" class="form-control img-hover-input" id="header_logo_file" accept="image/*">
          <div class="help-hover">Pasa el cursor para previsualizar</div>
          <input type="hidden" id="header_logo_url">
        </div>
        <div class="col-md-5">
          <label class="form-label">Texto de lugar y fecha</label>
          <input type="text" class="form-control" id="header_city_line"
            placeholder="Ej: Morelia, Michoacán, México, a {{fecha}}.">
          <div class="form-text">Puedes usar <code class="k" hover="Fecha de hoy">{{fecha}}</code> para que se ponga
            la fecha actual.</div>
        </div>
        <div class="col-md-8">
          <label class="form-label">Asunto de la carta</label>
          <input type="text" class="form-control" id="header_subject"
            placeholder="Ej: Carta de Aceptación de Servicio Social">
        </div>
        <div class="col-md-4 d-flex align-items-end">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="header_show_folio">
            <label class="form-check-label" for="header_show_folio">Mostrar número de folio</label>
          </div>
        </div>
      </div>
    </div>

    <!-- CUERPO -->
    <div class="form-section">
      <h2 class="h6">Texto principal</h2>
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Destinatario – Nombre o institución</label>
          <input type="text" class="form-control" id="body_recipient_name"
            placeholder="Ej: Hospital General de... o Nombre de la persona">
        </div>
        <div class="col-md-3">
          <label class="form-label">Destinatario – Puesto o cargo</label>
          <input type="text" class="form-control" id="body_recipient_role"
            placeholder="Ej: Director(a), Coordinador(a)...">
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="body_show_address">
            <label class="form-check-label" for="body_show_address">Mostrar el domicilio en la carta</label>
          </div>
        </div>
        <div class="col-md-3">
          <label class="form-label">Domicilio (opcional)</label>
          <input type="text" class="form-control" id="body_recipient_address"
            placeholder="Calle, número, colonia, ciudad">
        </div>
        <div class="col-12">
          <label class="form-label">Párrafos de la carta</label>
          <div id="body_paragraphs_editor" class="form-control" style="height:auto; padding:0;">
          </div>
          <input type="hidden" id="body_paragraphs_html">
          <div class="form-text">
            Puedes usar variables como <code class="k" hover="Nombre del alumno">{{studentName}}</code> y
            <code class="k" hover="Matrícula">{{matricula}}</code>. Se reemplazan al generar el PDF.
          </div>
        </div>
      </div>
    </div>

    <!-- FIRMA / SELLO -->
    <div class="form-section">
      <h2 class="h6">Firma y sello</h2>
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Texto encima de la firma</label>
          <input type="text" class="form-control" id="sig_legend" placeholder="Ej: ATENTAMENTE">
        </div>
        <div class="col-md-5">
          <label class="form-label">Firma (imagen)</label>
          <input type="file" class="form-control img-hover-input" id="sig_signature_file" accept="image/*">
          <div class="help-hover">Pasa el cursor para previsualizar</div>
          <input type="hidden" id="sig_signature_img_url">
        </div>
        <div class="col-md-4">
          <label class="form-label">Ancho de la firma (px)</label>
          <input type="number" class="form-control" id="sig_signature_width" value="200" min="50">
        </div>
        <div class="col-md-4">
          <label class="form-label">Sello (imagen)</label>
          <input type="file" class="form-control img-hover-input" id="sig_seal_file" accept="image/*">
          <div class="help-hover">Pasa el cursor para previsualizar</div>
          <input type="hidden" id="sig_seal_img_url">
        </div>
        <div class="col-md-2">
          <label class="form-label">Ancho del sello (px)</label>
          <input type="number" class="form-control" id="sig_seal_width" value="240" min="50">
        </div>
        <div class="col-md-3">
          <label class="form-label">Posición vertical del sello (px)</label>
          <input type="number" class="form-control" id="sig_seal_top" value="-60">
          <div class="form-text">Negativo = sube, positivo = baja.</div>
        </div>
        <div class="col-md-3">
          <label class="form-label">Posición horizontal del sello (%)</label>
          <input type="number" class="form-control" id="sig_seal_left_percent" value="50" min="0" max="100">
          <div class="form-text">0 = izquierda, 100 = derecha.</div>
        </div>
        <div class="col-md-3">
          <label class="form-label">Transparencia del sello (0 a 1)</label>
          <input type="number" step="0.05" min="0" max="1" class="form-control" id="sig_seal_opacity" value="0.8">
          <div class="form-text">0 = invisible, 1 = sólido.</div>
        </div>
        <div class="col-md-3">
          <label class="form-label">Nombre de quien firma</label>
          <input type="text" class="form-control" id="sig_signer_name" placeholder="Ej: C.P. Nombre Apellidos">
        </div>
        <div class="col-md-6">
          <label class="form-label">Cargo de quien firma</label>
          <input type="text" class="form-control" id="sig_signer_role"
            placeholder="Ej: Coordinador(a) de Servicio Social">
        </div>
      </div>
    </div>

    <!-- PIE DE PÁGINA -->
    <div class="form-section">
      <h2 class="h6">Pie de página (parte de abajo)</h2>
      <div class="row g-3">
        <div class="col-md-12">
          <label class="form-label">Color de la franja inferior</label>
          <input type="color" class="form-control form-control-color" id="footer_bottom_bar_color" value="#006837">
        </div>
        <div class="col-md-4">
          <label class="form-label">Logo pie (imagen)</label>
          <input type="file" class="form-control img-hover-input" id="footer_logo_file" accept="image/*">
          <div class="help-hover">Pasa el cursor para previsualizar</div>
          <input type="hidden" id="footer_logo_url">
        </div>
        <div class="col-md-4">
          <label class="form-label">Texto de contacto</label>
          <input type="text" class="form-control" id="footer_contact_line"
            placeholder="Ej: Tel. (443) 000 0000 · correo@dominio.com">
        </div>
        <div class="col-md-4">
          <label class="form-label">Texto de la franja inferior</label>
          <input type="text" class="form-control" id="footer_bottom_text"
            placeholder="Ej: UNIVERSIDAD MONTRER · Universidad en movimiento · www.unimontrer.edu.mx">
        </div>
      </div>
    </div>

    <!-- DISEÑO -->
    <div class="form-section">
      <h2 class="h6">Diseño y tamaño del texto</h2>
      <div class="row g-3">
        <div class="col-md-2">
          <label class="form-label">Fuente</label>
          <input type="text" class="form-control" id="layout_font_family" value="Arial, sans-serif"
            placeholder="Ej: Arial, sans-serif">
          <div class="form-text">Si no sabes, deja “Arial, sans-serif”.</div>
        </div>
        <div class="col-md-2">
          <label class="form-label">Tamaño de letra (pt)</label>
          <input type="number" class="form-control" id="layout_font_size_pt" value="12" min="8">
        </div>
        <div class="col-md-2">
          <label class="form-label">Espacio interno (px)</label>
          <input type="number" class="form-control" id="layout_content_padding_px" value="40" min="0">
          <div class="form-text">Margen interno del contenido.</div>
        </div>
        <div class="col-md-2">
          <label class="form-label">Margen de la página (px)</label>
          <input type="number" class="form-control" id="layout_page_margin_px" value="0" min="0">
        </div>
        <div class="col-md-2">
          <label class="form-label">Ancho del logo superior (px)</label>
          <input type="number" class="form-control" id="layout_header_logo_width" value="150" min="50">
        </div>
        <div class="col-md-2">
          <label class="form-label">Ancho del logo inferior (px)</label>
          <input type="number" class="form-control" id="layout_footer_logo_width" value="150" min="50">
        </div>
      </div>
    </div>
  </form>

  <aside class="fixed-rail" aria-label="Panel fijo de variables y acciones">
    <div class="vars-fixed card">
      <div class="card-body">
        <strong>Variables automáticas (se reemplazan solas en el PDF):</strong>
        <div class="mt-2 d-flex flex-wrap gap-2">
          <code class="k" hover="Nombre del alumno">{{studentName}}</code>
          <code class="k" hover="Matrícula">{{matricula}}</code>
          <code class="k" hover="Programa">{{degreeName}}</code>
          <code class="k" hover="Grado (ej. NOVENO CUATRIMESTRE)">{{gradoTexto}}</code>
          <code class="k" hover="Horas requeridas">{{horas}}</code>
          <code class="k" hover="Meses de servicio">{{meses}}</code>
          <code class="k" hover="Fecha de inicio">{{fechaInicio}}</code>
          <code class="k" hover="Fecha de t&#233;rmino">{{fechaTermino}}</code>
          <code class="k" hover="“el”/“la”">{{genero}}</code>
          <code class="k" hover="Fecha de hoy">{{fecha}}</code>
          <code class="k" hover="Folio generado">{{folio}}</code>
          <code class="k" hover="Nombre de la institución">{{nameUR}}</code>
          <code class="k" hover="Persona responsable">{{responsable}}</code>
          <code class="k" hover="Dirección">{{domicilio}}</code>
        </div>
        <small class="d-block mt-3 text-muted">
          Tip: Puedes escribir estas variables dentro de cualquier campo de texto; el sistema las cambiará al generar el
          PDF.
        </small>
      </div>
    </div>

    <div class="sticky-actions d-flex gap-2">
      <button class="btn btn-primary" id="btnSave">Guardar cambios</button>
      <button class="btn btn-light" id="btnReload">Reiniciar</button>
    </div>
  </aside>

</div>
<div id="imgHoverPreviewBubble"><img alt="preview"></div>

<script>
window.CARTA_API_URL = 'controller/carta-aceptacion-config.php';
(function(){
  var dep='view/assets/js/ajax/config/carta_presentacion.js';
  function loadDep(){ var s=document.createElement('script'); s.src=dep; document.head.appendChild(s); }
  if(window.Quill){ loadDep(); return; }
  var q=document.createElement('script');
  q.src='https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js';
  q.onload=loadDep;
  document.head.appendChild(q);
})();
</script>

