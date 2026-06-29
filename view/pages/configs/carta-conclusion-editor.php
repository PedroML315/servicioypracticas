<?php
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];
?>
<meta name="csrf-token" content="<?= htmlspecialchars($csrf) ?>">

<style>
  #imgHoverPreviewBubble { position:fixed; display:none; pointer-events:none; z-index:9999; background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:6px; box-shadow:0 4px 18px rgba(0,0,0,.12); }
  #imgHoverPreviewBubble img { max-width:240px; max-height:180px; display:block; }
</style>

<div class="container row has-rail">
  <div class="mb-4">
    <h1 class="h4 fw-bold mb-1"><i class="fa-solid fa-file-certificate me-2 text-success"></i>Carta de Conclusión de Servicio Social</h1>
    <p class="text-muted mb-0" style="font-size:.85rem">SS Interno · Configura el texto, firma y diseño del documento</p>
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
          <input type="file" class="form-control" id="header_logo_file" accept="image/*">
          <input type="hidden" id="header_logo_url">
        </div>
        <div class="col-md-5">
          <label class="form-label">Línea de lugar y fecha</label>
          <input type="text" class="form-control" id="header_city_line" placeholder="Morelia, Mich., a {{fecha}}.">
        </div>
        <div class="col-md-6">
          <label class="form-label">Asunto de la carta</label>
          <input type="text" class="form-control" id="header_subject" placeholder="Carta de conclusión de Servicio Social.">
        </div>
        <div class="col-md-3">
          <label class="form-label">Prefijo del folio</label>
          <input type="text" class="form-control" id="header_folio_prefix" placeholder="DSS-CCSS">
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="header_show_folio">
            <label class="form-check-label" for="header_show_folio">Mostrar folio</label>
          </div>
        </div>
      </div>
    </div>

    <!-- DESTINATARIO -->
    <div class="form-section">
      <h2 class="h6">Destinatario (Receptor)</h2>
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Nombre</label>
          <input type="text" class="form-control" id="recipient_nombre" placeholder="Lic. Alejandro Cruz Ferreyra">
        </div>
        <div class="col-md-4">
          <label class="form-label">Cargo</label>
          <input type="text" class="form-control" id="recipient_cargo" placeholder="Subdirector de Servicio Social y Pasantes">
        </div>
        <div class="col-md-4">
          <label class="form-label">Organismo</label>
          <input type="text" class="form-control" id="recipient_organismo" placeholder="Instituto de la Juventud Michoacana">
        </div>
      </div>
    </div>

    <!-- HORAS/MESES CONFIG -->
    <div class="form-section">
      <h2 class="h6">Configuración del servicio</h2>
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Horas requeridas</label>
          <input type="number" class="form-control" id="config_horas" value="480" min="1">
          <div class="form-text">Variable <code>{{horas}}</code></div>
        </div>
        <div class="col-md-3">
          <label class="form-label">Meses de duración</label>
          <input type="number" class="form-control" id="config_meses" value="6" min="1">
          <div class="form-text">Variable <code>{{meses}}</code></div>
        </div>
      </div>
    </div>

    <!-- CUERPO -->
    <div class="form-section">
      <h2 class="h6">Texto principal (párrafos)</h2>
      <div class="col-12">
        <div id="body_paragraphs_editor" class="form-control" style="height:auto;padding:0;"></div>
        <input type="hidden" id="body_paragraphs_html">
        <div class="form-text mt-1">
          Variables disponibles:
          <code class="k">{{studentName}}</code>
          <code class="k">{{matricula}}</code>
          <code class="k">{{degreeName}}</code>
          <code class="k">{{horas}}</code>
          <code class="k">{{meses}}</code>
          <code class="k">{{fechaInicio}}</code>
          <code class="k">{{fechaFin}}</code>
          <code class="k">{{fecha}}</code>
          <code class="k">{{folio}}</code>
        </div>
      </div>
    </div>

    <!-- FIRMA -->
    <div class="form-section">
      <h2 class="h6">Firma y sello</h2>
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Leyenda sobre la firma</label>
          <input type="text" class="form-control" id="sig_legend" placeholder="Atentamente">
        </div>
        <div class="col-md-4">
          <label class="form-label">Imagen de firma</label>
          <input type="file" class="form-control" id="sig_signature_file" accept="image/*">
          <input type="hidden" id="sig_signature_img_url">
        </div>
        <div class="col-md-2">
          <label class="form-label">Ancho firma (px)</label>
          <input type="number" class="form-control" id="sig_signature_width" value="200" min="50">
        </div>
        <div class="col-md-4">
          <label class="form-label">Imagen de sello</label>
          <input type="file" class="form-control" id="sig_seal_file" accept="image/*">
          <input type="hidden" id="sig_seal_img_url">
        </div>
        <div class="col-md-2">
          <label class="form-label">Ancho sello (px)</label>
          <input type="number" class="form-control" id="sig_seal_width" value="240" min="50">
        </div>
        <div class="col-md-2">
          <label class="form-label">Pos. vertical sello (px)</label>
          <input type="number" class="form-control" id="sig_seal_top" value="-72">
        </div>
        <div class="col-md-2">
          <label class="form-label">Pos. horizontal sello (%)</label>
          <input type="number" class="form-control" id="sig_seal_left_percent" value="47" min="0" max="100">
        </div>
        <div class="col-md-2">
          <label class="form-label">Opacidad sello</label>
          <input type="number" step="0.05" min="0" max="1" class="form-control" id="sig_seal_opacity" value="0.8">
        </div>
        <div class="col-md-4">
          <label class="form-label">Nombre del firmante</label>
          <input type="text" class="form-control" id="sig_signer_name">
        </div>
        <div class="col-md-4">
          <label class="form-label">Cargo del firmante</label>
          <input type="text" class="form-control" id="sig_signer_role">
        </div>
      </div>
    </div>

    <!-- PIE -->
    <div class="form-section">
      <h2 class="h6">Pie de página</h2>
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Color barra inferior</label>
          <input type="color" class="form-control form-control-color" id="footer_bottom_bar_color" value="#006837">
        </div>
        <div class="col-md-4">
          <label class="form-label">Logo pie (imagen)</label>
          <input type="file" class="form-control" id="footer_logo_file" accept="image/*">
          <input type="hidden" id="footer_logo_url">
        </div>
        <div class="col-md-5">
          <label class="form-label">Texto de contacto</label>
          <input type="text" class="form-control" id="footer_contact_line">
        </div>
        <div class="col-md-12">
          <label class="form-label">Texto inferior</label>
          <input type="text" class="form-control" id="footer_bottom_text">
        </div>
      </div>
    </div>

    <!-- DISEÑO -->
    <div class="form-section">
      <h2 class="h6">Diseño y tipografía</h2>
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Fuente</label>
          <input type="text" class="form-control" id="layout_font_family" value="Arial, sans-serif">
        </div>
        <div class="col-md-2">
          <label class="form-label">Tamaño letra (pt)</label>
          <input type="number" class="form-control" id="layout_font_size_pt" value="12" min="8">
        </div>
        <div class="col-md-2">
          <label class="form-label">Padding contenido (px)</label>
          <input type="number" class="form-control" id="layout_content_padding_px" value="40" min="0">
        </div>
        <div class="col-md-2">
          <label class="form-label">Ancho logo superior (px)</label>
          <input type="number" class="form-control" id="layout_header_logo_width" value="150" min="50">
        </div>
        <div class="col-md-2">
          <label class="form-label">Ancho logo inferior (px)</label>
          <input type="number" class="form-control" id="layout_footer_logo_width" value="150" min="50">
        </div>
      </div>
    </div>

  </form>

  <!-- RAIL FIJO -->
  <aside class="fixed-rail" aria-label="Variables y acciones">
    <div class="vars-fixed card">
      <div class="card-body">
        <strong>Variables disponibles</strong>
        <div class="mt-2 d-flex flex-wrap gap-2">
          <code class="k">{{studentName}}</code>
          <code class="k">{{matricula}}</code>
          <code class="k">{{degreeName}}</code>
          <code class="k">{{horas}}</code>
          <code class="k">{{meses}}</code>
          <code class="k">{{fechaInicio}}</code>
          <code class="k">{{fechaFin}}</code>
          <code class="k">{{fecha}}</code>
          <code class="k">{{folio}}</code>
        </div>
        <small class="d-block mt-2 text-muted">Se reemplazan automáticamente al generar el PDF.</small>
      </div>
    </div>
    <div class="sticky-actions d-flex gap-2">
      <button class="btn btn-success" id="btnSave"><i class="fas fa-save me-1"></i>Guardar</button>
      <button class="btn btn-light" id="btnReload"><i class="fas fa-undo me-1"></i>Recargar</button>
    </div>
  </aside>
</div>
<div id="imgHoverPreviewBubble"><img alt="preview"></div>

<script>
function _initConclusionEditor() {
(function () {
  'use strict';
  const API      = 'controller/carta-conclusion-config.php';
  const CSRF_KEY = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

  // Quill
  const quill = new Quill('#body_paragraphs_editor', {
    theme: 'snow',
    modules: { toolbar: [['bold','italic','underline'],['link'],['clean']] }
  });

  // ── Helper: leer campo ───────────────────────────────────────
  const g = id => document.getElementById(id);

  // ── Cargar config ────────────────────────────────────────────
  async function loadConfig() {
    try {
      const r = await fetch(API, { credentials:'same-origin' });
      const j = await r.json();
      if (!j.ok) throw new Error(j.error);
      const c = j.config ?? {};

      g('header_bar_color').value         = c.header?.bar_color             ?? '#006837';
      g('header_logo_url').value          = c.header?.logo_url              ?? '';
      g('header_city_line').value         = c.header?.city_line             ?? 'Morelia, Mich., a {{fecha}}.';
      g('header_subject').value           = c.header?.subject               ?? 'Carta de conclusión de Servicio Social.';
      g('header_folio_prefix').value      = c.header?.folio_prefix          ?? 'DSS-CCSS';
      g('header_show_folio').checked      = c.header?.show_folio            ?? true;

      g('recipient_nombre').value         = c.recipient?.nombre             ?? 'Lic. Alejandro Cruz Ferreyra';
      g('recipient_cargo').value          = c.recipient?.cargo              ?? 'Subdirector de Servicio Social y Pasantes';
      g('recipient_organismo').value      = c.recipient?.organismo          ?? 'Instituto de la Juventud Michoacana';

      g('config_horas').value             = c.config?.horas                 ?? 480;
      g('config_meses').value             = c.config?.meses                 ?? 6;

      // párrafos
      const paras = c.body?.paragraphs ?? [];
      quill.root.innerHTML = paras.map(p => `<p>${p}</p>`).join('');

      g('sig_legend').value               = c.signature?.legend             ?? 'Atentamente';
      g('sig_signature_img_url').value    = c.signature?.signature_img_url  ?? '';
      g('sig_signature_width').value      = c.signature?.signature_width    ?? 200;
      g('sig_seal_img_url').value         = c.signature?.seal_img_url       ?? '';
      g('sig_seal_width').value           = c.signature?.seal?.width        ?? 240;
      g('sig_seal_top').value             = c.signature?.seal?.top          ?? -72;
      g('sig_seal_left_percent').value    = c.signature?.seal?.left_percent ?? 47;
      g('sig_seal_opacity').value         = c.signature?.seal?.opacity      ?? 0.8;
      g('sig_signer_name').value          = c.signature?.signer_name        ?? '';
      g('sig_signer_role').value          = c.signature?.signer_role        ?? '';

      g('footer_bottom_bar_color').value  = c.footer?.bottom_bar_color      ?? '#006837';
      g('footer_logo_url').value          = c.footer?.logo_url              ?? '';
      g('footer_contact_line').value      = c.footer?.contact_line          ?? '';
      g('footer_bottom_text').value       = c.footer?.bottom_text           ?? '';

      g('layout_font_family').value       = c.layout?.font_family           ?? 'Arial, sans-serif';
      g('layout_font_size_pt').value      = c.layout?.font_size_pt          ?? 12;
      g('layout_content_padding_px').value= c.layout?.content_padding_px   ?? 40;
      g('layout_header_logo_width').value = c.layout?.header_logo_width     ?? 150;
      g('layout_footer_logo_width').value = c.layout?.footer_logo_width     ?? 150;

      // Sync hover preview URLs after form is filled
      [['header_logo_file','header_logo_url'],['sig_signature_file','sig_signature_img_url'],
       ['sig_seal_file','sig_seal_img_url'],['footer_logo_file','footer_logo_url']].forEach(([fid,hid]) => {
        const fi = g(fid), hi = g(hid);
        if (fi && hi && hi.value) fi.dataset.previewUrl = hi.value;
      });

      showAlert('Configuración cargada correctamente.', 'success', 2000);
    } catch (err) {
      showAlert('Error al cargar: ' + err.message, 'danger');
    }
  }

  // ── Construir objeto config ──────────────────────────────────
  function buildConfig() {
    // Extraer párrafos del quill
    const qlHtml = quill.root.innerHTML;
    const div = document.createElement('div');
    div.innerHTML = qlHtml;
    const paras = Array.from(div.querySelectorAll('p')).map(p => p.innerHTML.trim()).filter(Boolean);

    return {
      header: {
        bar_color:    g('header_bar_color').value,
        logo_url:     g('header_logo_url').value,
        city_line:    g('header_city_line').value,
        subject:      g('header_subject').value,
        folio_prefix: g('header_folio_prefix').value,
        show_folio:   g('header_show_folio').checked
      },
      recipient: {
        nombre:    g('recipient_nombre').value,
        cargo:     g('recipient_cargo').value,
        organismo: g('recipient_organismo').value,
      },
      config: {
        horas: parseInt(g('config_horas').value) || 480,
        meses: parseInt(g('config_meses').value) || 6,
      },
      body: { paragraphs: paras },
      signature: {
        legend:              g('sig_legend').value,
        signature_img_url:   g('sig_signature_img_url').value,
        signature_width:     parseInt(g('sig_signature_width').value) || 200,
        seal_img_url:        g('sig_seal_img_url').value,
        seal: {
          width:        parseInt(g('sig_seal_width').value)        || 240,
          top:          parseInt(g('sig_seal_top').value)          || -72,
          left_percent: parseInt(g('sig_seal_left_percent').value) || 47,
          opacity:      parseFloat(g('sig_seal_opacity').value)    || 0.8,
        },
        signer_name: g('sig_signer_name').value,
        signer_role: g('sig_signer_role').value,
      },
      footer: {
        bottom_bar_color: g('footer_bottom_bar_color').value,
        logo_url:         g('footer_logo_url').value,
        contact_line:     g('footer_contact_line').value,
        bottom_text:      g('footer_bottom_text').value,
      },
      layout: {
        font_family:         g('layout_font_family').value,
        font_size_pt:        parseInt(g('layout_font_size_pt').value) || 12,
        content_padding_px:  parseInt(g('layout_content_padding_px').value) || 40,
        header_logo_width:   parseInt(g('layout_header_logo_width').value) || 150,
        footer_logo_width:   parseInt(g('layout_footer_logo_width').value) || 150,
      }
    };
  }

  // ── Guardar ───────────────────────────────────────────────────
  async function saveConfig() {
    try {
      const r = await fetch(API, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': CSRF_KEY
        },
        body: JSON.stringify(buildConfig())
      });
      const j = await r.json();
      if (!j.ok) throw new Error(j.error);
      showAlert('✅ Guardado correctamente.', 'success', 3000);
    } catch (err) {
      showAlert('Error al guardar: ' + err.message, 'danger');
    }
  }

  // ── Hover preview bubble ─────────────────────────────────────
  const _bubble = () => document.getElementById('imgHoverPreviewBubble');
  const _posBubble = (e) => { const b = _bubble(); if (b) { b.style.left=(e.clientX+14)+'px'; b.style.top=(e.clientY+14)+'px'; } };
  const _showBubble = (url, e) => { if (!url) return; const b = _bubble(); if (!b) return; b.querySelector('img').src=url; b.style.display='block'; _posBubble(e); };
  const _hideBubble = () => { const b = _bubble(); if (b) b.style.display='none'; };

  // ── Upload imagen ─────────────────────────────────────────────
  function bindImageUpload(fileInputId, hiddenId) {
    const fileInput  = g(fileInputId);
    const hiddenInput = g(hiddenId);
    if (!fileInput) return;

    // Set initial preview URL from hidden value (populated by fillForm)
    if (hiddenInput && hiddenInput.value) fileInput.dataset.previewUrl = hiddenInput.value;
    fileInput.addEventListener('mouseenter', (e) => _showBubble(fileInput.dataset.previewUrl || '', e));
    fileInput.addEventListener('mousemove', _posBubble);
    fileInput.addEventListener('mouseleave', _hideBubble);

    fileInput.addEventListener('change', async function () {
      const file = this.files[0];
      if (!file) return;
      const fd = new FormData();
      fd.append('image', file);
      try {
        const r = await fetch('controller/upload-image.php', { method:'POST', body:fd, credentials:'same-origin', headers:{ 'X-CSRF-Token': CSRF_KEY } });
        const j = await r.json();
        if (j.ok && j.url) {
          hiddenInput.value = j.url;
          fileInput.dataset.previewUrl = j.url;
          showAlert('Imagen subida correctamente.', 'success', 3000);
        } else {
          throw new Error(j.error || 'Error');
        }
      } catch (err) {
        showAlert('Error al subir imagen: ' + err.message, 'danger');
      }
    });
  }

  bindImageUpload('header_logo_file',    'header_logo_url');
  bindImageUpload('sig_signature_file',  'sig_signature_img_url');
  bindImageUpload('sig_seal_file',       'sig_seal_img_url');
  bindImageUpload('footer_logo_file',    'footer_logo_url');

  // ── Alertas ────────────────────────────────────────────────────
  function showAlert(msg, type='info', ms=0) {
    const box = g('alertBox');
    box.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
      ${msg}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`;
    if (ms > 0) setTimeout(() => box.innerHTML = '', ms);
  }

  // ── Botones ────────────────────────────────────────────────────
  g('btnSave').addEventListener('click', saveConfig);
  g('btnReload').addEventListener('click', loadConfig);

  // ── Inicio ─────────────────────────────────────────────────────
  loadConfig();
})();
} // end _initConclusionEditor

(function(){
  if(window.Quill){ _initConclusionEditor(); return; }
  var q=document.createElement('script');
  q.src='https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js';
  q.onload=_initConclusionEditor;
  document.head.appendChild(q);
})();
</script>
