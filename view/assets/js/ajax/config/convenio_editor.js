// Editor de Convenio de Prácticas Profesionales – jQuery + Quill + AJAX
// Guarda el editable y genera automáticamente el PDF.

let convenioQuill = null;
let _convenioPendingHTML = '';

(() => {
  const API_URL    = 'controller/convenio-config.php';
  const UPLOAD_URL = 'controller/upload-image.php';
  const PDF_URL    = 'controller/ajax/generarConvenio.php';
  const $  = jQuery;
  const el = id => document.getElementById(id);
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  const showAlert = (type, msg) => {
    $('#alertBox').html(
      `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
        ${msg}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
      </div>`
    );
    setTimeout(() => { $('#alertBox').html(''); }, 4000);
  };

  // ── Hover preview bubble ──
  function ensureBubble() {
    let bubble = document.getElementById('imgHoverPreviewBubble');
    if (!bubble) {
      bubble = document.createElement('div');
      bubble.id = 'imgHoverPreviewBubble';
      Object.assign(bubble.style, { position:'fixed', display:'none', pointerEvents:'none', zIndex:'9999',
        background:'#fff', border:'1px solid #e5e7eb', borderRadius:'8px', padding:'6px', boxShadow:'0 4px 18px rgba(0,0,0,.12)' });
      bubble.innerHTML = '<img alt="preview" style="max-width:240px;max-height:180px;display:block">';
      document.body.appendChild(bubble);
    }
    return { bubble, bubbleImg: bubble.querySelector('img') };
  }
  const posBubble  = (e) => { const { bubble } = ensureBubble(); bubble.style.left = (e.clientX + 14) + 'px'; bubble.style.top = (e.clientY + 14) + 'px'; };
  const showBubble = (url, e) => { if (!url) return; const { bubble, bubbleImg } = ensureBubble(); bubbleImg.src = url; bubble.style.display = 'block'; posBubble(e); };
  const hideBubble = () => { const { bubble } = ensureBubble(); bubble.style.display = 'none'; };

  // ── Upload de imágenes (sincroniza hidden + campo de texto URL) ──
  function bindImageUploader(fileId, hiddenId, textId) {
    const input  = el(fileId);
    const hidden = el(hiddenId);
    const text   = textId ? el(textId) : null;
    if (!input || !hidden) return;

    if (hidden.value) input.dataset.previewUrl = hidden.value;

    input.addEventListener('mouseenter', (e) => showBubble(input.dataset.previewUrl || '', e));
    input.addEventListener('mousemove', posBubble);
    input.addEventListener('mouseleave', hideBubble);

    input.addEventListener('change', () => {
      const f = input.files?.[0];
      if (!f) return;
      if (!/^image\/(png|jpeg|jpg|webp|svg\+xml)$/i.test(f.type)) {
        showAlert('danger', 'Formato no permitido. Usa PNG, JPG, WEBP o SVG.'); input.value = ''; return;
      }
      if (f.size > 5 * 1024 * 1024) { showAlert('danger', 'La imagen supera 5 MB.'); input.value = ''; return; }

      input.dataset.previewUrl = URL.createObjectURL(f);

      const fd = new FormData();
      fd.append('image', f);
      fd.append('slot', hiddenId);

      $.ajax({ url: UPLOAD_URL, method: 'POST', headers: { 'X-CSRF-Token': csrf },
        data: fd, processData: false, contentType: false, dataType: 'json' })
        .done((res) => {
          if (res?.ok && res.url) {
            hidden.value = res.url;
            if (text) text.value = res.url;
            input.dataset.previewUrl = res.url;
            showAlert('success', 'Imagen subida correctamente.');
          } else showAlert('danger', res?.error || 'No pude subir la imagen.');
        })
        .fail((xhr) => showAlert('danger', xhr.responseJSON?.error || 'Error al subir la imagen.'));
    });
  }

  function setEditorHTML(html) {
    html = html || '';
    if (convenioQuill) convenioQuill.root.innerHTML = html;
    else { _convenioPendingHTML = html; const h = el('content_html'); if (h) h.value = html; }
    const h = el('content_html');
    if (h) h.value = convenioQuill ? convenioQuill.root.innerHTML.trim() : html.trim();
  }

  const get = (obj, path, def) => {
    try {
      const v = path.split('.').reduce((o, k) => (o && o[k] !== undefined ? o[k] : undefined), obj);
      return v !== undefined ? v : def;
    } catch { return def; }
  };

  // ── Cargar config -> formulario ──
  const fillForm = (cfg) => {
    cfg = cfg || {};
    el('header_bar_color').value         = get(cfg,'header.bar_color','#006837');
    el('header_logo_url').value          = get(cfg,'header.logo_url','');
    el('header_logo_url_text').value     = get(cfg,'header.logo_url','');
    el('layout_header_logo_width').value = get(cfg,'layout.header_logo_width',150);
    el('header_title').value             = get(cfg,'header.title','CONVENIO DE PRÁCTICAS PROFESIONALES');
    el('header_city_line').value         = get(cfg,'header.city_line','Morelia, Michoacán, a {{fecha}}');

    el('sig_signer_name').value          = get(cfg,'signature.signer_name','');
    el('sig_signer_role').value          = get(cfg,'signature.signer_role','Representante Legal – Instituto Montrer, S.C.');
    el('sig_empresa_label').value        = get(cfg,'signature.empresa_label','Representante Legal de “La Empresa”');
    el('sig_testigo1_label').value       = get(cfg,'signature.testigo1_label','Testigo');
    el('sig_testigo2_label').value       = get(cfg,'signature.testigo2_label','Testigo');

    setEditorHTML(get(cfg,'body.content_html',''));

    el('footer_bottom_bar_color').value  = get(cfg,'footer.bottom_bar_color','#006837');
    el('footer_logo_url').value          = get(cfg,'footer.logo_url','');
    el('footer_logo_url_text').value     = get(cfg,'footer.logo_url','');
    el('layout_footer_logo_width').value = get(cfg,'layout.footer_logo_width',130);
    el('footer_contact_line').value      = get(cfg,'footer.contact_line','');
    el('footer_bottom_text').value       = get(cfg,'footer.bottom_text','');

    el('layout_font_family').value       = get(cfg,'layout.font_family','Arial, Helvetica, sans-serif');
    el('layout_font_size_pt').value      = get(cfg,'layout.font_size_pt',11);
    el('layout_content_padding_px').value= get(cfg,'layout.content_padding_px',40);
    el('layout_page_margin_px').value    = get(cfg,'layout.page_margin_px',0);

    const hf = el('header_logo_file'); if (hf) hf.dataset.previewUrl = el('header_logo_url').value || '';
    const ff = el('footer_logo_file'); if (ff) ff.dataset.previewUrl = el('footer_logo_url').value || '';
  };

  // ── Recolectar -> JSON ──
  const collectConfig = () => {
    const html = convenioQuill ? convenioQuill.root.innerHTML.trim() : (el('content_html').value || '').trim();
    const headerLogo = (el('header_logo_url_text').value.trim() || el('header_logo_url').value.trim());
    const footerLogo = (el('footer_logo_url_text').value.trim() || el('footer_logo_url').value.trim());
    return {
      header: {
        bar_color: el('header_bar_color').value || '#006837',
        logo_url: headerLogo,
        city_line: el('header_city_line').value.trim(),
        title: el('header_title').value.trim()
      },
      body: { content_html: html },
      signature: {
        signer_name: el('sig_signer_name').value.trim(),
        signer_role: el('sig_signer_role').value.trim(),
        empresa_label: el('sig_empresa_label').value.trim() || 'Representante Legal de “La Empresa”',
        testigo1_label: el('sig_testigo1_label').value.trim() || 'Testigo',
        testigo2_label: el('sig_testigo2_label').value.trim() || 'Testigo'
      },
      footer: {
        bottom_bar_color: el('footer_bottom_bar_color').value || '#006837',
        logo_url: footerLogo,
        contact_line: el('footer_contact_line').value.trim(),
        bottom_text: el('footer_bottom_text').value.trim()
      },
      layout: {
        font_family: el('layout_font_family').value.trim() || 'Arial, Helvetica, sans-serif',
        font_size_pt: Number(el('layout_font_size_pt').value || 11),
        content_padding_px: Number(el('layout_content_padding_px').value || 40),
        page_margin_px: Number(el('layout_page_margin_px').value || 0),
        header_logo_width: Number(el('layout_header_logo_width').value || 150),
        footer_logo_width: Number(el('layout_footer_logo_width').value || 130)
      }
    };
  };

  const refreshPdfLink = () => {
    const a = el('btnPreviewPdf');
    if (a) a.href = PDF_URL + '?t=' + Date.now();
  };

  const loadConfig = () => {
    $.ajax({ url: API_URL, method: 'GET', dataType: 'json', cache: false })
      .done((res) => {
        if (!res?.ok) return showAlert('danger', res?.error || 'No pude cargar la configuración.');
        fillForm(res.config || {});
      })
      .fail((xhr) => showAlert('danger', 'Error al cargar la configuración (código ' + xhr.status + ').'));
  };

  const saveConfig = () => {
    if (!el('sig_signer_name').value.trim()) {
      showAlert('danger', 'Escribe el nombre del representante de la institución.');
      el('sig_signer_name').focus();
      return;
    }
    const cfg = collectConfig();
    $('#btnSave').prop('disabled', true).text('Guardando…');
    $.ajax({ url: API_URL, method: 'POST', data: JSON.stringify(cfg),
      contentType: 'application/json; charset=utf-8', headers: { 'X-CSRF-Token': csrf }, dataType: 'json' })
      .done((res) => {
        if (res?.ok) {
          showAlert(res.pdf_ok ? 'success' : 'warning', res.message || 'Guardado.');
          refreshPdfLink();
        } else showAlert('danger', res?.error || 'No pude guardar los cambios.');
      })
      .fail((xhr) => showAlert('danger', xhr.responseJSON?.error || 'Ocurrió un error al guardar.'))
      .always(() => $('#btnSave').prop('disabled', false).text('Guardar y generar PDF'));
  };

  $('#btnSave').on('click', saveConfig);
  $('#btnReload').on('click', loadConfig);

  bindImageUploader('header_logo_file', 'header_logo_url', 'header_logo_url_text');
  bindImageUploader('footer_logo_file', 'footer_logo_url', 'footer_logo_url_text');
})();

// ── Quill + chips ──
(function onReady(fn){ document.readyState!=='loading' ? fn() : document.addEventListener('DOMContentLoaded',fn); })(function(){
  convenioQuill = new Quill('#content_html_editor', {
    theme: 'snow',
    placeholder: 'Escribe el cuerpo del convenio…',
    modules: { toolbar: [
      [{ header: [false, 3, 4] }],
      ['bold', 'italic', 'underline'],
      [{ list: 'ordered' }, { list: 'bullet' }],
      [{ align: [] }],
      ['link', 'blockquote', 'clean']
    ] }
  });

  if (_convenioPendingHTML && _convenioPendingHTML.trim() !== '') {
    convenioQuill.root.innerHTML = _convenioPendingHTML;
  } else {
    const hidden = document.getElementById('content_html');
    if (hidden && hidden.value.trim() !== '') convenioQuill.root.innerHTML = hidden.value.trim();
  }

  convenioQuill.on('text-change', () => {
    const hidden = document.getElementById('content_html');
    if (hidden) hidden.value = convenioQuill.root.innerHTML.trim();
  });

  // Cargar config al iniciar
  const btnReload = document.getElementById('btnReload');
  if (btnReload) btnReload.click();

  document.querySelectorAll('code.k').forEach(k => {
    k.addEventListener('click', () => {
      navigator.clipboard.writeText(k.textContent.trim())
        .then(() => { k.classList.add('copied'); setTimeout(() => k.classList.remove('copied'), 1200); })
        .catch(() => {});
    });
  });
});
