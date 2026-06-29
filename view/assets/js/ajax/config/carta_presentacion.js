
let quill = null;

  (() => {
    // API_URL se puede sobreescribir desde PHP con: window.CARTA_API_URL = '...';
    const API_URL = window.CARTA_API_URL || 'controller/carta-config.php';
    const UPLOAD_URL = 'controller/upload-image.php';

    const $ = jQuery;
    const el = id => document.getElementById(id);
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    const showAlert = (type, msg) => {
      $('#alertBox').html(
        `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
         ${msg}
         <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
       </div>`
      );
      setTimeout(() => {
        $('#alertBox').html('');
      }, 3500);
    };

    // ========= Hover preview bubble =========
    function ensureBubble() {
      const bubble = document.getElementById('imgHoverPreviewBubble');
      return { bubble, bubbleImg: bubble ? bubble.querySelector('img') : null };
    }
    const posBubble  = (e) => { const { bubble } = ensureBubble(); if (bubble) { bubble.style.left = (e.clientX + 14) + 'px'; bubble.style.top = (e.clientY + 14) + 'px'; } };
    const showBubble = (url, e) => { if (!url) return; const { bubble, bubbleImg } = ensureBubble(); if (!bubble) return; bubbleImg.src = url; bubble.style.display = 'block'; posBubble(e); };
    const hideBubble = () => { const { bubble } = ensureBubble(); if (bubble) bubble.style.display = 'none'; };

    // ========= Upload de imágenes =========
    function bindImageUploader(fileId, hiddenId) {
      const input  = el(fileId);
      const hidden = el(hiddenId);
      if (!input || !hidden) return;

      if (hidden.value) input.dataset.previewUrl = hidden.value;

      input.addEventListener('mouseenter', (e) => showBubble(input.dataset.previewUrl || '', e));
      input.addEventListener('mousemove', posBubble);
      input.addEventListener('mouseleave', hideBubble);

      input.addEventListener('change', () => {
        const f = input.files?.[0];
        if (!f) return;

        if (!/^image\/(png|jpeg|jpg|webp|svg\+xml)$/i.test(f.type)) {
          showAlert('danger', 'Formato no permitido. Usa PNG, JPG, WEBP o SVG.');
          input.value = '';
          return;
        }
        if (f.size > 5 * 1024 * 1024) {
          showAlert('danger', 'La imagen supera 5 MB.');
          input.value = '';
          return;
        }

        input.dataset.previewUrl = URL.createObjectURL(f);

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
    function htmlToParagraphs(html) {
      // Convierte el HTML enriquecido en un arreglo de fragmentos HTML (sin envolver en <p>)
      const tmp = document.createElement('div');
      tmp.innerHTML = html || '';
      // Si hay párrafos, extrae solo el contenido interno
      const ps = Array.from(tmp.querySelectorAll('p'));
      if (ps.length) {
      return ps.map(p => p.innerHTML.trim()).filter(Boolean);
      }
      // Si no hay <p>, divide por dobles saltos y toma el texto plano
      return (tmp.innerHTML || '')
      .split(/\n{2,}/)
      .map(s => s.trim())
      .filter(Boolean);
    }

    // Inserta HTML en Quill
    function setEditorHTML(html) {
      quill.root.innerHTML = (html || '');
      document.getElementById('body_paragraphs_html').value = quill.root.innerHTML.trim();
    }

    const fillForm = (cfg) => {
      // encabezado
      el('header_bar_color').value = cfg?.header?.bar_color ?? '#006837';
      el('header_logo_url').value = cfg?.header?.logo_url ?? '';
      el('header_city_line').value = cfg?.header?.city_line ?? '';
      el('header_subject').value = cfg?.header?.subject ?? '';
      el('header_show_folio').checked = !!(cfg?.header?.show_folio ?? true);

      const hFile = el('header_logo_file');
      if (hFile) hFile.dataset.previewUrl = el('header_logo_url').value || '';
      el('body_recipient_name').value = cfg?.body?.recipient_name ?? '';
      el('body_recipient_role').value = cfg?.body?.recipient_role ?? '';
      el('body_recipient_address').value = cfg?.body?.recipient_address ?? '';
      el('body_show_address').checked = !!(cfg?.body?.show_address ?? false);

      // Soporta ambos formatos: el nuevo (HTML) y el anterior (array de párrafos)
      const html = cfg?.body?.paragraphs_html
        ?? (Array.isArray(cfg?.body?.paragraphs) ? cfg.body.paragraphs.map(p => `<p>${p}</p>`).join('') : '');
      setEditorHTML(html);

      // firma / sello
      el('sig_legend').value = cfg?.signature?.legend ?? 'ATENTAMENTE';
      el('sig_signature_img_url').value = cfg?.signature?.signature_img_url ?? '';
      el('sig_signature_width').value = cfg?.signature?.signature_width ?? 200;
      el('sig_seal_img_url').value = cfg?.signature?.seal_img_url ?? '';

      const sigFile  = el('sig_signature_file'); if (sigFile)  sigFile.dataset.previewUrl  = el('sig_signature_img_url').value || '';
      const sealFile = el('sig_seal_file');       if (sealFile) sealFile.dataset.previewUrl = el('sig_seal_img_url').value || '';

      el('sig_seal_width').value = cfg?.signature?.seal?.width ?? 240;
      el('sig_seal_top').value = cfg?.signature?.seal?.top ?? -60;
      el('sig_seal_left_percent').value = cfg?.signature?.seal?.left_percent ?? 50;
      el('sig_seal_opacity').value = cfg?.signature?.seal?.opacity ?? 0.8;
      el('sig_signer_name').value = cfg?.signature?.signer_name ?? '';
      el('sig_signer_role').value = cfg?.signature?.signer_role ?? '';

      // pie de página
      el('footer_logo_url').value = cfg?.footer?.logo_url ?? '';

      const footFile = el('footer_logo_file'); if (footFile) footFile.dataset.previewUrl = el('footer_logo_url').value || '';

      el('footer_contact_line').value = cfg?.footer?.contact_line ?? '';
      el('footer_bottom_bar_color').value = cfg?.footer?.bottom_bar_color ?? '#006837';
      el('footer_bottom_text').value = cfg?.footer?.bottom_text ?? '';

      // diseño
      el('layout_font_family').value = cfg?.layout?.font_family ?? 'Arial, sans-serif';
      el('layout_font_size_pt').value = cfg?.layout?.font_size_pt ?? 12;
      el('layout_content_padding_px').value = cfg?.layout?.content_padding_px ?? 40;
      el('layout_page_margin_px').value = cfg?.layout?.page_margin_px ?? 0;
      el('layout_header_logo_width').value = cfg?.layout?.header_logo_width ?? 150;
      el('layout_footer_logo_width').value = cfg?.layout?.footer_logo_width ?? 150;
    };

    const collectConfig = () => {

      // Obtiene el HTML del editor si existe; si no, del input hidden
      const html = (window.quill && quill)
        ? quill.root.innerHTML.trim()
        : (document.getElementById('body_paragraphs_html').value || '').trim();

      return {
        header: {
          bar_color: el('header_bar_color').value || '#006837',
          logo_url: el('header_logo_url').value.trim(),
          city_line: el('header_city_line').value.trim(),
          subject: el('header_subject').value.trim(),
          show_folio: el('header_show_folio').checked
        },
        body: {
          recipient_name: el('body_recipient_name').value.trim(),
          recipient_role: el('body_recipient_role').value.trim(),
          recipient_address: el('body_recipient_address').value.trim(),
          show_address: el('body_show_address').checked,
          paragraphs: htmlToParagraphs(html)
        },
        signature: {
          legend: el('sig_legend').value.trim(),
          signature_img_url: el('sig_signature_img_url').value.trim(),
          signature_width: Number(el('sig_signature_width').value || 200),
          seal_img_url: el('sig_seal_img_url').value.trim(),
          seal: {
            width: Number(el('sig_seal_width').value || 240),
            top: Number(el('sig_seal_top').value || -60),
            left_percent: Number(el('sig_seal_left_percent').value || 50),
            opacity: Number(el('sig_seal_opacity').value || 0.8)
          },
          signer_name: el('sig_signer_name').value.trim(),
          signer_role: el('sig_signer_role').value.trim()
        },
        footer: {
          logo_url: el('footer_logo_url').value.trim(),
          contact_line: el('footer_contact_line').value.trim(),
          bottom_bar_color: el('footer_bottom_bar_color').value || '#006837',
          bottom_text: el('footer_bottom_text').value.trim()
        },
        layout: {
          font_family: el('layout_font_family').value.trim() || 'Arial, sans-serif',
          font_size_pt: Number(el('layout_font_size_pt').value || 12),
          content_padding_px: Number(el('layout_content_padding_px').value || 40),
          page_margin_px: Number(el('layout_page_margin_px').value || 0),
          header_logo_width: Number(el('layout_header_logo_width').value || 150),
          footer_logo_width: Number(el('layout_footer_logo_width').value || 150)
        }
      };
    };

    const loadConfig = () => {
      $.ajax({
        url: API_URL,
        method: 'GET',
        dataType: 'json'
      }).done((res) => {
        if (!res?.ok) return showAlert('danger', res?.error || 'No pude cargar la configuración.');
        fillForm(res.config || {});
        showAlert('success', 'Configuración cargada.');
      }).fail((xhr) => {
        showAlert('danger', 'Error al cargar la configuración (código ' + xhr.status + ').');
      });
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
        if (res?.ok) {
          showAlert('success', '¡Listo! Guardé los cambios.');
        } else {
          showAlert('danger', res?.error || 'No pude guardar los cambios.');
        }
      }).fail((xhr) => {
        const msg = xhr.responseJSON?.error || 'Ocurrió un error al guardar (código ' + xhr.status + ').';
        showAlert('danger', msg);
      });
    };

    // Eventos
    $('#btnSave').on('click', saveConfig);
    $('#btnReload').on('click', loadConfig);

    // Vincular inputs de imagen
    bindImageUploader('header_logo_file', 'header_logo_url');
    bindImageUploader('sig_signature_file', 'sig_signature_img_url');
    bindImageUploader('sig_seal_file', 'sig_seal_img_url');
    bindImageUploader('footer_logo_file', 'footer_logo_url');
    window._cartaLoadConfig = loadConfig;
  })();

  (function onReady(fn){ document.readyState!=='loading' ? fn() : document.addEventListener('DOMContentLoaded',fn); })(function(){
    initQuill();
    // Cargar config DESPUÉS de que Quill esté inicializado
    if (typeof window._cartaLoadConfig === 'function') window._cartaLoadConfig();
    document.querySelectorAll("code.k").forEach(el => {
      el.addEventListener("click", () => {
        const text = el.textContent.trim();
        navigator.clipboard.writeText(text).then(() => {
          // Cambia tooltip a "Copiado"
          el.classList.add("copied");
          setTimeout(() => el.classList.remove("copied"), 1500);
        }).catch(err => {
          console.error("Error al copiar:", err);
        });
      });
    });
  });

  function initQuill() {
    quill = new Quill('#body_paragraphs_editor', {
      theme: 'snow',
      placeholder: 'Escribe el texto de la carta…',
      modules: {
        toolbar: [
          ['bold', 'italic', 'underline'],
          [{ 'list': 'ordered' }, { 'list': 'bullet' }],
          [{ 'align': [] }],
          ['link', 'blockquote', 'clean']
        ]
      }
    });

    // Cuando cambie el contenido, almacena el HTML en el hidden
    quill.on('text-change', () => {
      document.getElementById('body_paragraphs_html').value = quill.root.innerHTML.trim();
    });
  }
