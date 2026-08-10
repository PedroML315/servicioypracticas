// Editor compartido por las cartas de Servicio Social (presentación y aceptación).
// Cada página fija su endpoint con window.CARTA_API_URL y describe su vista previa
// con window.CARTA_PREVIEW. Los campos que no existan en una u otra página se
// ignoran solos, así el mismo archivo sirve para las dos.

let quill = null;

  (() => {
    // API_URL se puede sobreescribir desde PHP con: window.CARTA_API_URL = '...';
    const API_URL = window.CARTA_API_URL || 'controller/carta-config.php';
    const UPLOAD_URL = 'controller/upload-image.php';
    const PREVIEW = window.CARTA_PREVIEW || null;

    const $ = jQuery;
    const el = id => document.getElementById(id);
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Config tal como llegó del servidor. Sirve de base al guardar para no borrar
    // claves que este editor ya no muestra (el membrete sustituyó al encabezado
    // y al pie configurables).
    let cfgCargada = {};

    const setVal = (id, v) => { const n = el(id); if (n) n.value = v; };
    const getVal = (id, def = '') => { const n = el(id); return n ? n.value.trim() : def; };
    const getNum = (id, def) => { const n = el(id); return Number((n && n.value) || def); };

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
      const hidden = el('body_paragraphs_html');
      if (quill) {
        quill.root.innerHTML = (html || '');
        if (hidden) hidden.value = quill.root.innerHTML.trim();
      } else if (hidden) {
        hidden.value = (html || '').trim();
      }
    }

    const fillForm = (cfg) => {
      cfg = cfg || {};
      cfgCargada = cfg;

      // encabezado (el membrete lo aporta la plantilla oficial: no es editable)
      setVal('header_city_line', cfg?.header?.city_line ?? '');
      setVal('header_subject', cfg?.header?.subject ?? '');
      const chkFolio = el('header_show_folio');
      if (chkFolio) chkFolio.checked = !!(cfg?.header?.show_folio ?? true);

      // destinatario fijo (sólo la carta de aceptación lo tiene)
      setVal('recipient_nombre', cfg?.recipient?.nombre ?? '');
      setVal('recipient_cargo', cfg?.recipient?.cargo ?? '');
      setVal('recipient_organismo', cfg?.recipient?.organismo ?? '');

      // Soporta ambos formatos: el nuevo (HTML) y el anterior (array de párrafos)
      const html = cfg?.body?.paragraphs_html
        ?? (Array.isArray(cfg?.body?.paragraphs) ? cfg.body.paragraphs.map(p => `<p>${p}</p>`).join('') : '');
      setEditorHTML(html);

      // firma / sello
      setVal('sig_legend', cfg?.signature?.legend ?? 'ATENTAMENTE');
      setVal('sig_signature_img_url', cfg?.signature?.signature_img_url ?? '');
      setVal('sig_signature_width', cfg?.signature?.signature_width ?? 200);
      setVal('sig_seal_img_url', cfg?.signature?.seal_img_url ?? '');

      const sigFile  = el('sig_signature_file'); if (sigFile)  sigFile.dataset.previewUrl  = getVal('sig_signature_img_url');
      const sealFile = el('sig_seal_file');       if (sealFile) sealFile.dataset.previewUrl = getVal('sig_seal_img_url');

      setVal('sig_seal_width', cfg?.signature?.seal?.width ?? 240);
      setVal('sig_seal_top', cfg?.signature?.seal?.top ?? -60);
      setVal('sig_seal_left_percent', cfg?.signature?.seal?.left_percent ?? 50);
      setVal('sig_seal_opacity', cfg?.signature?.seal?.opacity ?? 0.8);
      setVal('sig_signer_name', cfg?.signature?.signer_name ?? '');
      setVal('sig_signer_role', cfg?.signature?.signer_role ?? '');

      // diseño (los márgenes los fija la plantilla)
      setVal('layout_font_family', cfg?.layout?.font_family ?? 'Arial, sans-serif');
      setVal('layout_font_size_pt', cfg?.layout?.font_size_pt ?? 11);
    };

    const collectConfig = () => {

      // Obtiene el HTML del editor si existe; si no, del input hidden
      const html = quill
        ? quill.root.innerHTML.trim()
        : (el('body_paragraphs_html')?.value || '').trim();

      // Se parte de lo que había guardado para no perder claves que este editor
      // ya no muestra.
      const cfg = JSON.parse(JSON.stringify(cfgCargada || {}));

      cfg.header = Object.assign({}, cfg.header, {
        city_line: getVal('header_city_line'),
        subject: getVal('header_subject'),
        show_folio: !!el('header_show_folio')?.checked
      });

      if (el('recipient_nombre')) {
        cfg.recipient = Object.assign({}, cfg.recipient, {
          nombre: getVal('recipient_nombre'),
          cargo: getVal('recipient_cargo'),
          organismo: getVal('recipient_organismo')
        });
      }

      cfg.body = Object.assign({}, cfg.body, {
        paragraphs_html: html,
        paragraphs: htmlToParagraphs(html)
      });

      cfg.signature = Object.assign({}, cfg.signature, {
        legend: getVal('sig_legend') || 'ATENTAMENTE',
        signature_img_url: getVal('sig_signature_img_url'),
        signature_width: getNum('sig_signature_width', 200),
        seal_img_url: getVal('sig_seal_img_url'),
        seal: {
          width: getNum('sig_seal_width', 240),
          top: getNum('sig_seal_top', -60),
          left_percent: getNum('sig_seal_left_percent', 50),
          opacity: getNum('sig_seal_opacity', 0.8)
        },
        signer_name: getVal('sig_signer_name'),
        signer_role: getVal('sig_signer_role')
      });

      cfg.layout = Object.assign({}, cfg.layout, {
        font_family: getVal('layout_font_family') || 'Arial, sans-serif',
        font_size_pt: getNum('layout_font_size_pt', 11)
      });

      return cfg;
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

    // Vista previa con datos de prueba, sin necesidad de guardar.
    $('#btnPreviewPdf').on('click', () => {
      if (!PREVIEW || typeof window.abrirVistaPreviaPP !== 'function') {
        return showAlert('danger', 'La vista previa aún no está lista. Espera un momento y vuelve a intentarlo.');
      }
      window.abrirVistaPreviaPP(PREVIEW.doc, collectConfig(), PREVIEW.titulo, {
        endpoint: PREVIEW.endpoint,
        filename: PREVIEW.filename
      });
    });

    // Vincular inputs de imagen
    bindImageUploader('sig_signature_file', 'sig_signature_img_url');
    bindImageUploader('sig_seal_file', 'sig_seal_img_url');
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
