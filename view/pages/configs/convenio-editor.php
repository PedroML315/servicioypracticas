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
  code.k { cursor:pointer; user-select:none; background:#eef7f1; color:#01643D; border:1px solid #cfe8db; border-radius:6px; padding:.1rem .4rem; transition:all .15s; position:relative; }
  code.k:hover { background:#dcefe4; }
  code.k.copied { background:#01643D; color:#fff; }
  code.k.copied::after { content:"¡Copiado!"; position:absolute; left:50%; top:-1.6rem; transform:translateX(-50%); background:#01643D; color:#fff; font-size:.68rem; padding:.1rem .4rem; border-radius:4px; white-space:nowrap; }
  .membrete-nota { font-size:.8rem; color:#475569; background:#f1f5f9; border-left:3px solid #01643D; border-radius:4px; padding:.5rem .7rem; margin-bottom:1rem; }
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
      <h2 class="h6">Encabezado</h2>
      <p class="membrete-nota">
        <i class="fa-solid fa-circle-info me-1"></i>
        El membrete institucional (banda verde con el logo arriba y banda inferior) viene de la
        <strong>plantilla oficial</strong> y se imprime igual en todas las hojas. No se edita aquí.
      </p>
      <div class="row g-3">
        <div class="col-md-8">
          <label class="form-label">Título del documento</label>
          <input type="text" class="form-control" id="header_title" value="CONVENIO DE PRÁCTICAS PROFESIONALES">
          <div class="form-text">Se imprime centrado en la primera hoja, debajo de la banda verde.</div>
        </div>
        <div class="col-md-4">
          <label class="form-label">Línea de fecha / ciudad</label>
          <input type="text" class="form-control" id="header_city_line" value="Morelia, Michoacán, a {{fecha}}">
          <div class="form-text">Alineada a la derecha. Usa <code class="k" hover="Fecha de hoy">{{fecha}}</code></div>
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
        <div class="col-md-5">
          <label class="form-label"><i class="fa-solid fa-signature me-1"></i>Firma del representante (imagen)</label>
          <input type="file" class="form-control img-hover-input" id="signer_sig_file" accept="image/*">
          <div class="help-hover">Pasa el cursor para previsualizar. Se imprime sobre la línea de firma de “La Universidad”.</div>
          <input type="hidden" id="signer_sig_url">
        </div>
        <div class="col-md-4">
          <label class="form-label">URL firma (opcional)</label>
          <input type="text" class="form-control" id="signer_sig_url_text" placeholder="https://...">
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="signer_sig_clear">
            <i class="fa-solid fa-eraser me-1"></i>Quitar firma
          </button>
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
            <code class="k" hover="Fecha completa en letras">{{fecha_larga}}</code>
            <code class="k" hover="Nombre del representante de la institución">{{repUniversidad}}</code>.
            Para la cláusula de firma, sustituye “el día ____ del mes de ____ en el año de dos mil ____” por
            <code class="k" hover="Fecha completa en letras">{{fecha_larga}}</code>.
            Las firmas autógrafas se agregan automáticamente al final (no las escribas aquí).
          </div>
        </div>
      </div>
    </div>

    <!-- ETIQUETAS DE FIRMAS -->
    <div class="form-section">
      <h2 class="h6">Bloques de firma (2 representantes + 2 testigos)</h2>
      <p class="text-muted mb-3" style="font-size:.82rem">
        <strong>“La Universidad”</strong> firma con el <strong>representante</strong> (nombre configurado arriba en
        “Representante de la institución”) y su <strong>testigo</strong> (abajo). <strong>“La Empresa”</strong> firma de
        forma autógrafa (queda en blanco para el organismo).
      </p>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Representante de “La Empresa” (etiqueta)</label>
          <input type="text" class="form-control" id="sig_empresa_label" value="Representante Legal de “La Empresa”">
          <div class="form-text">Firma autógrafa; queda en blanco para el organismo.</div>
        </div>
        <div class="col-md-6">
          <label class="form-label"><i class="fa-solid fa-user-tie me-1 text-success"></i>Firma del representante de la Universidad</label>
          <input type="text" class="form-control" value="Se toma del nombre configurado en “Representante de la institución”." disabled>
          <div class="form-text">Aparece como firmante de “La Universidad” con su nombre y cargo.</div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Testigo de “La Empresa” (etiqueta)</label>
          <input type="text" class="form-control" id="sig_testigo1_label" value="Testigo">
          <div class="form-text">Firma autógrafa; queda en blanco para el organismo.</div>
        </div>
        <div class="col-md-6">
          <label class="form-label"><i class="fa-solid fa-user-check me-1 text-success"></i>Nombre del testigo de la Universidad</label>
          <input type="text" class="form-control" id="sig_testigo_universidad" placeholder="Nombre del testigo de la Universidad">
          <div class="form-text">Se imprime como testigo de “La Universidad”. Si lo dejas vacío, aparece la etiqueta genérica “Testigo”.</div>
        </div>
        <div class="col-md-5">
          <label class="form-label"><i class="fa-solid fa-signature me-1"></i>Firma del testigo de la Universidad (imagen)</label>
          <input type="file" class="form-control img-hover-input" id="testigo_universidad_sig_file" accept="image/*">
          <div class="help-hover">Pasa el cursor para previsualizar. Se imprime sobre su línea de firma.</div>
          <input type="hidden" id="testigo_universidad_sig_url">
        </div>
        <div class="col-md-4">
          <label class="form-label">URL firma (opcional)</label>
          <input type="text" class="form-control" id="testigo_universidad_sig_url_text" placeholder="https://...">
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="testigo_universidad_sig_clear">
            <i class="fa-solid fa-eraser me-1"></i>Quitar firma
          </button>
        </div>
      </div>
    </div>

    <!-- PIE -->
    <div class="form-section">
      <h2 class="h6">Pie de página</h2>
      <p class="membrete-nota">
        <i class="fa-solid fa-circle-info me-1"></i>
        La banda de color del pie viene de la plantilla oficial. Lo único editable es la línea de
        contacto, que se imprime justo encima de ella junto con “Página X de Y”.
      </p>
      <div class="row g-3">
        <div class="col-md-12">
          <label class="form-label">Contacto</label>
          <input type="text" class="form-control" id="footer_contact_line"
            placeholder="Av. Lázaro Cárdenas #1760, Col. Chapultepec Sur, Morelia, Mich.">
          <div class="form-text">Déjalo vacío si no quieres que aparezca.</div>
        </div>
      </div>
    </div>

    <!-- DISEÑO -->
    <div class="form-section">
      <h2 class="h6">Diseño</h2>
      <div class="row g-3">
        <div class="col-md-4"><label class="form-label">Fuente</label>
          <input type="text" class="form-control" id="layout_font_family" value="Arial, Helvetica, sans-serif"></div>
        <div class="col-md-3"><label class="form-label">Tamaño (pt)</label>
          <input type="number" class="form-control" id="layout_font_size_pt" value="11" min="8"></div>
        <div class="col-md-5"><label class="form-label">Margen lateral del texto (px)</label>
          <input type="number" class="form-control" id="layout_content_padding_px" value="96" min="0">
          <div class="form-text">96 px = 1 pulgada, igual que la plantilla oficial.</div></div>
      </div>
    </div>
  </form>

  <!-- RAIL DERECHO -->
  <aside class="fixed-rail" aria-label="Panel fijo de acciones">
    <div class="vars-fixed card">
      <div class="card-body">
        <strong>Variables automáticas:</strong>
        <small class="d-block text-muted mb-2">Haz clic en una variable para copiarla al portapapeles.</small>
        <div class="mt-2 d-flex flex-wrap gap-2">
          <code class="k" hover="Fecha de hoy (3 de julio de 2026)">{{fecha}}</code>
          <code class="k" hover="Fecha completa en letras (3 del mes de julio en el año de dos mil veintiséis)">{{fecha_larga}}</code>
          <code class="k" hover="Nombre del representante de la institución">{{repUniversidad}}</code>
        </div>
        <strong class="d-block mt-3">Variables del organismo:</strong>
        <small class="d-block text-muted mb-2">Se sustituyen automáticamente con los datos de cada organismo al generar su convenio.</small>
        <div class="d-flex flex-wrap gap-2">
          <code class="k" hover="Nombre de la empresa/organismo">{{nombre_empresa}}</code>
          <code class="k" hover="Nombre del representante legal">{{representante_legal}}</code>
          <code class="k" hover="Cargo del representante legal">{{cargo_representante}}</code>
          <code class="k" hover="Domicilio (calle, colonia, ciudad, C.P.)">{{direccion_empresa}}</code>
          <code class="k" hover="Teléfono de contacto">{{telefono}}</code>
          <code class="k" hover="Correo de contacto">{{correo}}</code>
          <code class="k" hover="Giro o actividad">{{giro}}</code>
          <code class="k" hover="Ciudad">{{ciudad}}</code>
        </div>
        <small class="d-block mt-3 text-muted">
          El PDF se genera en hoja <strong>Carta</strong> con el membrete de la plantilla oficial
          y paginación “Página X de Y” en cada hoja.
          Al final se incluyen 4 firmas: 2 representantes y 2 testigos.
          Sugerencia: en el bloque “La Empresa” puedes usar {{representante_legal}} como etiqueta de firma.
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
