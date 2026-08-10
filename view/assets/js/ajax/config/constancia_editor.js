// Editor de Constancia – jQuery + Quill + AJAX + Upload imágenes + Hover preview (robusto)

let quill = null;
let _pendingEditorHTML = '';

(() => {
  const API_URL    = 'controller/constancia-config.php';
  const UPLOAD_URL = 'controller/upload-image.php';
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
    setTimeout(() => { $('#alertBox').html(''); }, 3500);
  };

  // ========= Hover preview: crear bubble si no existe =========
  function ensureBubble() {
    let bubble = document.getElementById('imgHoverPreviewBubble');
    if (!bubble) {
      bubble = document.createElement('div');
      bubble.id = 'imgHoverPreviewBubble';
      bubble.style.position = 'fixed';
      bubble.style.display = 'none';
      bubble.style.pointerEvents = 'none';
      bubble.style.zIndex = '9999';
      bubble.style.background = '#fff';
      bubble.style.border = '1px solid #e5e7eb';
      bubble.style.borderRadius = '8px';
      bubble.style.padding = '6px';
      bubble.style.boxShadow = '0 4px 18px rgba(0,0,0,.12)';
      bubble.innerHTML = '<img alt="preview" style="max-width:240px;max-height:180px;display:block">';
      document.body.appendChild(bubble);
    }
    const bubbleImg = bubble.querySelector('img');
    return { bubble, bubbleImg };
  }
  const posBubble  = (e) => { const { bubble } = ensureBubble(); bubble.style.left = (e.clientX + 14) + 'px'; bubble.style.top = (e.clientY + 14) + 'px'; };
  const showBubble = (url, e) => { if (!url) return; const { bubble, bubbleImg } = ensureBubble(); bubbleImg.src = url; bubble.style.display = 'block'; posBubble(e); };
  const hideBubble = () => { const { bubble } = ensureBubble(); bubble.style.display = 'none'; };

  // ========= Upload de imágenes + hover por input =========
  function bindImageUploader(fileId, hiddenId) {
    const input  = el(fileId);
    const hidden = el(hiddenId);
    if (!input || !hidden) return;

    // Si ya hay url, úsala en hover
    if (hidden.value) input.dataset.previewUrl = hidden.value;

    // Hover preview
    input.addEventListener('mouseenter', (e) => showBubble(input.dataset.previewUrl || '', e));
    input.addEventListener('mousemove', posBubble);
    input.addEventListener('mouseleave', hideBubble);

    input.addEventListener('change', () => {
      const f = input.files?.[0];
      if (!f) return;

      // Validaciones
      const okType = /^image\/(png|jpeg|jpg|webp|svg\+xml)$/i.test(f.type);
      if (!okType) {
        showAlert('danger', 'Formato no permitido. Usa PNG, JPG, WEBP o SVG.');
        input.value = '';
        return;
      }
      if (f.size > 5 * 1024 * 1024) {
        showAlert('danger', 'La imagen supera 5 MB.');
        input.value = '';
        return;
      }

      // Preview local inmediato
      const blobUrl = URL.createObjectURL(f);
      input.dataset.previewUrl = blobUrl;

      // Subir a servidor
      const fd = new FormData();
      fd.append('image', f);
      fd.append('slot', hiddenId);

      $.ajax({
        url: UPLOAD_URL,
        method: 'POST',
        headers: { 'X-CSRF-Token': csrf },
        data: fd,
        processData: false,
        contentType: false,
        dataType: 'json'
      }).done((res) => {
        if (res?.ok && res.url) {
          hidden.value = res.url;
          input.dataset.previewUrl = res.url;
          showAlert('success', 'Imagen subida correctamente.');
        } else {
          showAlert('danger', res?.error || 'No pude subir la imagen.');
        }
      }).fail((xhr) => {
        showAlert('danger', xhr.responseJSON?.error || 'Error al subir la imagen.');
      });
    });
  }

  // ========= Helpers de contenido =========
  function htmlToParagraphs(html) {
    const tmp = document.createElement('div'); tmp.innerHTML = html || '';
    const ps = Array.from(tmp.querySelectorAll('p'));
    if (ps.length) return ps.map(p => p.innerHTML.trim()).filter(Boolean);
    return (tmp.innerHTML || '').split(/\n{2,}/).map(s => s.trim()).filter(Boolean);
  }

  function setEditorHTML(html) {
    html = html || '';
    if (quill) { quill.root.innerHTML = html; }
    else { const hidden = el('body_paragraphs_html'); if (hidden) hidden.value = html; _pendingEditorHTML = html; }
    const hidden = el('body_paragraphs_html');
    if (hidden) hidden.value = quill ? quill.root.innerHTML.trim() : html.trim();
  }

  const get = (obj, path, fallbackKeys = [], defVal = undefined) => {
    try {
      const val = path.split('.').reduce((o, k) => (o && o[k] !== undefined ? o[k] : undefined), obj);
      if (val !== undefined) return val;
      for (const k of fallbackKeys) if (obj && obj[k] !== undefined) return obj[k];
      return defVal;
    } catch { return defVal; }
  };

  // ========= Cargar config -> formulario =========
  const fillForm = (cfg) => {
    cfg = cfg || {};

    // Encabezado (el membrete lo aporta la plantilla oficial: no es editable)
    el('header_subject').value            = get(cfg,'header.subject',['header_subject'],'Constancia de Acreditación de Prácticas Profesionales');
    el('header_show_folio').checked       = !!get(cfg,'header.show_folio',['header_show_folio'],true);

    // Resumen
    el('summary_show_card').checked       = !!get(cfg,'summary.show_card',['summary_show_card'],true);
    el('summary_hours').value             = get(cfg,'summary.hours',['summary_hours'],360);
    el('summary_condition').value         = get(cfg,'summary.condition',['summary_condition'],'ACREDITADO');
    el('summary_title').value             = get(cfg,'summary.title',['summary_title'],'Resumen de acreditación');

    // Destinatario
    el('recipient_name').value            = get(cfg,'recipient.name',['recipient_name'],'L.A.E. Diana Dolores Huitrón Alvarez');
    el('recipient_role').value            = get(cfg,'recipient.role',['recipient_role'],'Directora de Control Escolar, Servicio Social y Titulación');
    el('recipient_org').value             = get(cfg,'recipient.org',['recipient_org'],'Universidad Montrer');

    // Cuerpo
    const bodyHtml = get(cfg,'body.paragraphs_html',['body_paragraphs_html'],null);
    if (bodyHtml) setEditorHTML(bodyHtml);
    else {
      const arr = get(cfg,'body.paragraphs',['body_paragraphs'],[]);
      if (Array.isArray(arr) && arr.length) setEditorHTML(arr.map(p=>`<p>${p}</p>`).join(''));
      else setEditorHTML(`<p>Por este medio, se hace de su conocimiento que {{generoArt}} <strong>{{studentName}}</strong>, con matrícula <strong>{{matricula}}</strong>, de la <strong>{{degreeName}}</strong> en esta Universidad, acreditó las <strong>Prácticas Profesionales</strong> en el Organismo Receptor <strong>{{nameOrganismo}}</strong>, cubriendo <strong>{{summary_hours}}</strong> horas.</p><p>Sin otro asunto en particular, agradezco de antemano la atención brindada.</p>`);
    }

    // Firma / Sello
    el('sig_legend').value               = get(cfg,'signature.legend',['sig_legend'],'ATENTAMENTE');
    el('sig_signature_img_url').value    = get(cfg,'signature.signature_img_url',['sig_signature_img_url'],'');
    el('sig_signature_width').value      = get(cfg,'signature.signature_width',['sig_signature_width'],200);
    el('sig_seal_img_url').value         = get(cfg,'signature.seal_img_url',['sig_seal_img_url'],'');
    el('sig_seal_width').value           = get(cfg,'signature.seal.width',['sig_seal_width'],220);
    el('sig_seal_top').value             = get(cfg,'signature.seal.top',['sig_seal_top'],-52);
    el('sig_seal_left_percent').value    = get(cfg,'signature.seal.left_percent',['sig_seal_left_percent'],60);
    el('sig_seal_opacity').value         = get(cfg,'signature.seal.opacity',['sig_seal_opacity'],0.85);
    el('sig_signer_name').value          = get(cfg,'signature.signer_name',['sig_signer_name'],'MGH Karla Mariana Fonseca Munguia');
    el('sig_signer_role').value          = get(cfg,'signature.signer_role',['sig_signer_role'],'Coordinador de Prácticas Profesionales UNIMO');

    const sigFile  = el('sig_signature_file'); if (sigFile) sigFile.dataset.previewUrl = el('sig_signature_img_url').value || '';
    const sealFile = el('sig_seal_file');      if (sealFile) sealFile.dataset.previewUrl = el('sig_seal_img_url').value || '';

  };

  // ========= Recolectar -> JSON =========
  const collectConfig = () => {
    const html = quill ? quill.root.innerHTML.trim() : (el('body_paragraphs_html').value || '').trim();
    return {
      header: {
        subject: el('header_subject').value.trim(),
        show_folio: el('header_show_folio').checked
      },
      summary: {
        show_card: el('summary_show_card').checked,
        title: el('summary_title').value.trim(),
        hours: Number(el('summary_hours').value || 360),
        condition: el('summary_condition').value.trim() || 'ACREDITADO'
      },
      recipient: {
        name: el('recipient_name').value.trim(),
        role: el('recipient_role').value.trim(),
        org:  el('recipient_org').value.trim()
      },
      body: {
        paragraphs_html: html,
        paragraphs: htmlToParagraphs(html)
      },
      signature: {
        legend: el('sig_legend').value.trim() || 'ATENTAMENTE',
        signature_img_url: el('sig_signature_img_url').value.trim(),
        signature_width: Number(el('sig_signature_width').value || 200),
        seal_img_url: el('sig_seal_img_url').value.trim(),
        seal: {
          width: Number(el('sig_seal_width').value || 220),
          top: Number(el('sig_seal_top').value || -52),
          left_percent: Number(el('sig_seal_left_percent').value || 60),
          opacity: Number(el('sig_seal_opacity').value || 0.85)
        },
        signer_name: el('sig_signer_name').value.trim(),
        signer_role: el('sig_signer_role').value.trim()
      },
      // El membrete (barra lateral y barra verde) viene de la plantilla oficial.
      layout: {
        font_family: el('layout_font_family').value.trim() || 'Arial, Helvetica, sans-serif',
        font_size_pt: Number(el('layout_font_size_pt').value || 11)
      }
    };
  };

  // ========= AJAX Cargar / Guardar =========
  const loadConfig = () => {
    $.ajax({ url: API_URL, method: 'GET', dataType: 'json', cache: false })
      .done((res) => {
        if (!res?.ok) return showAlert('danger', res?.error || 'No pude cargar la configuración.');
        fillForm(res.config || {});
        showAlert('success', 'Configuración cargada.');
      })
      .fail((xhr) => showAlert('danger', 'Error al cargar la configuración (código ' + xhr.status + ').'));
  };

  const saveConfig = () => {
    const cfg = collectConfig();
    $.ajax({
      url: API_URL,
      method: 'POST',
      data: JSON.stringify(cfg),
      contentType: 'application/json; charset=utf-8',
      headers: { 'X-CSRF-Token': csrf },
      dataType: 'json'
    }).done((res) => {
      if (res?.ok) showAlert('success', '¡Listo! Guardé los cambios.');
      else showAlert('danger', res?.error || 'No pude guardar los cambios.');
    }).fail((xhr) => showAlert('danger', xhr.responseJSON?.error || 'Ocurrió un error al guardar.'));
  };

  // Botones
  $('#btnSave').on('click', saveConfig);
  $('#btnReload').on('click', loadConfig);
  // Vista previa con datos de prueba, sin necesidad de guardar.
  $('#btnPreviewPdf').on('click', () =>
    window.abrirVistaPreviaPP('constancia', collectConfig(), 'Constancia de Acreditación — vista previa'));

  // Vincular inputs de imagen (upload + hover)
  bindImageUploader('sig_signature_file', 'sig_signature_img_url');
  bindImageUploader('sig_seal_file', 'sig_seal_img_url');
})();

// ========= Quill + chips =========
(function onReady(fn){ document.readyState!=='loading' ? fn() : document.addEventListener('DOMContentLoaded',fn); })(function(){
  initQuill();
  const btnReload = document.getElementById('btnReload');
  if (btnReload) btnReload.click();

  document.querySelectorAll('code.k').forEach(k => {
    k.addEventListener('click', () => {
      const text = k.textContent.trim();
      navigator.clipboard.writeText(text).then(() => {
        k.classList.add('copied');
        setTimeout(() => k.classList.remove('copied'), 1500);
      }).catch(err => console.error('Error al copiar:', err));
    });
  });
});

function initQuill() {
  quill = new Quill('#body_paragraphs_editor', {
    theme: 'snow',
    placeholder: 'Escribe el cuerpo de la constancia…',
    modules: {
      toolbar: [
        [{ header: [false, 3, 4] }],
        ['bold', 'italic', 'underline'],
        [{ list: 'ordered' }, { list: 'bullet' }],
        [{ align: [] }],
        ['link', 'blockquote', 'clean']
      ]
    }
  });

  if (_pendingEditorHTML && _pendingEditorHTML.trim() !== '') {
    quill.root.innerHTML = _pendingEditorHTML;
  } else {
    const hidden = document.getElementById('body_paragraphs_html');
    if (hidden && hidden.value.trim() !== '') quill.root.innerHTML = hidden.value.trim();
  }

  quill.on('text-change', () => {
    const hidden = document.getElementById('body_paragraphs_html');
    if (hidden) hidden.value = quill.root.innerHTML.trim();
  });
}
