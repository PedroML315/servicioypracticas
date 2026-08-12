<?php
// carta-aceptacion-editor.php — Carta de Aceptación de Servicio Social

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
    position: fixed;
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

  .membrete-nota {
    font-size: .8rem;
    color: #475569;
    background: #f1f5f9;
    border-left: 3px solid #01643D;
    border-radius: 4px;
    padding: .5rem .7rem;
    margin-bottom: 1rem;
  }
</style>

<div class="container row has-rail">
  <div id="alertBox"></div>

  <form class="col-md-12" id="cfgForm" onsubmit="return false;">
    <!-- ENCABEZADO -->
    <div class="form-section">
      <h2 class="h6">Encabezado</h2>
      <p class="membrete-nota">
        <i class="fa-solid fa-circle-info me-1"></i>
        El membrete institucional (barra lateral con el escudo, los domicilios de los campus y el QR,
        más la barra verde inferior) viene de la <strong>plantilla oficial</strong>, la misma que usan
        los documentos de Prácticas Profesionales. Se imprime igual en todas las hojas y no se edita aquí.
      </p>
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Línea de ciudad y fecha</label>
          <input type="text" class="form-control" id="header_city_line"
            placeholder="Morelia, Mich., a {{fecha}}.">
          <div class="form-text">Usa <code class="k" hover="Fecha de hoy">{{fecha}}</code> para la fecha automática.</div>
        </div>

        <div class="col-md-8">
          <label class="form-label">Asunto</label>
          <input type="text" class="form-control" id="header_subject"
            placeholder="Ej: Carta de aceptación de Servicio Social.">
        </div>

        <div class="col-md-4 d-flex align-items-end">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="header_show_folio">
            <label class="form-check-label" for="header_show_folio">Mostrar FOLIO</label>
          </div>
        </div>
      </div>
    </div>

    <!-- DESTINATARIO -->
    <div class="form-section">
      <h2 class="h6">Destinatario</h2>
      <p class="text-muted small mb-2">
        A diferencia de la carta de presentación, aquí el destinatario es fijo para todos los alumnos:
        es la dependencia a la que se dirige la aceptación.
      </p>
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Nombre</label>
          <input type="text" class="form-control" id="recipient_nombre"
            placeholder="Ej: Lic. Alejandro Cruz Ferreyra">
        </div>
        <div class="col-md-4">
          <label class="form-label">Cargo</label>
          <input type="text" class="form-control" id="recipient_cargo"
            placeholder="Ej: Subdirector de Servicio Social y Pasantes">
        </div>
        <div class="col-md-4">
          <label class="form-label">Organismo</label>
          <input type="text" class="form-control" id="recipient_organismo"
            placeholder="Ej: Instituto de la Juventud Michoacana">
        </div>
      </div>
    </div>

    <!-- CUERPO -->
    <div class="form-section">
      <h2 class="h6">Texto principal</h2>
      <div class="row g-3">
        <div class="col-12">
          <label class="form-label">Párrafos de la carta</label>
          <div id="body_paragraphs_editor" class="form-control" style="height:auto; padding:0;"></div>
          <input type="hidden" id="body_paragraphs_html">
          <div class="form-text">
            Variables disponibles:
            <code class="k" hover="Nombre del alumno">{{studentName}}</code>
            <code class="k" hover="Matrícula">{{matricula}}</code>
            <code class="k" hover="Programa">{{degreeName}}</code>
            <code class="k" hover="&ldquo;el&rdquo;/&ldquo;la&rdquo;">{{genero}}</code>
            <code class="k" hover="&ldquo;alumno&rdquo;/&ldquo;alumna&rdquo;">{{alumnoGenero}}</code>
            <code class="k" hover="Grado (ej. Noveno Cuatrimestre)">{{gradoTexto}}</code>
            <code class="k" hover="Horas requeridas">{{horas}}</code>
            <code class="k" hover="Meses de servicio">{{meses}}</code>
            <code class="k" hover="Fecha de inicio">{{fechaInicio}}</code>
            <code class="k" hover="Fecha de t&#233;rmino">{{fechaTermino}}</code>
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

    <!-- DISEÑO -->
    <div class="form-section">
      <h2 class="h6">Diseño y tamaño del texto</h2>
      <p class="membrete-nota">
        <i class="fa-solid fa-circle-info me-1"></i>
        Los márgenes los fija la plantilla: el texto arranca justo a la derecha de la barra lateral
        y termina antes de la barra verde.
      </p>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Fuente</label>
          <input type="text" class="form-control" id="layout_font_family" value="Arial, sans-serif"
            placeholder="Ej: Arial, sans-serif">
          <div class="form-text">Si no sabes, deja “Arial, sans-serif”.</div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Tamaño de letra (pt)</label>
          <input type="number" class="form-control" id="layout_font_size_pt" value="11" min="8">
          <div class="form-text">La plantilla usa 11 pt. Súbelo con cuidado: la columna es angosta y la carta podría irse a dos hojas.</div>
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
          <code class="k" hover="&ldquo;el&rdquo;/&ldquo;la&rdquo;">{{genero}}</code>
          <code class="k" hover="&ldquo;alumno&rdquo;/&ldquo;alumna&rdquo;">{{alumnoGenero}}</code>
          <code class="k" hover="Grado (ej. Noveno Cuatrimestre)">{{gradoTexto}}</code>
          <code class="k" hover="Horas requeridas">{{horas}}</code>
          <code class="k" hover="Meses de servicio">{{meses}}</code>
          <code class="k" hover="Fecha de inicio">{{fechaInicio}}</code>
          <code class="k" hover="Fecha de t&#233;rmino">{{fechaTermino}}</code>
          <code class="k" hover="Fecha de hoy">{{fecha}}</code>
          <code class="k" hover="Folio generado">{{folio}}</code>
        </div>
        <small class="d-block mt-3 text-muted">
          Tip: Puedes escribir estas variables dentro de cualquier campo de texto; el sistema las cambiará al generar el
          PDF.
        </small>

        <button type="button" class="btn btn-light btn-sm mt-3 w-100" id="btnPreviewPdf">
          <i class="fa-solid fa-file-pdf me-1"></i> Ver PDF de prueba
        </button>
        <small class="d-block mt-2 text-muted" style="font-size:.75rem">
          Muestra el documento aquí mismo con los datos de ejemplo de la plantilla,
          usando lo que tienes en pantalla <strong>aunque no lo hayas guardado</strong>.
          No consume folio ni guarda nada.
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
window.CARTA_PREVIEW = {
  doc: 'aceptacion',
  titulo: 'Carta de Aceptación de Servicio Social — vista previa',
  endpoint: 'controller/servicio/previewCartaSS.php',
  filename: 'Vista_previa_carta_aceptacion_servicio.pdf'
};
(function(){
  var dep='view/assets/js/ajax/config/carta_presentacion.js';
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
