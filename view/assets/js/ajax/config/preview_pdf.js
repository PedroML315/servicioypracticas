/**
 * Vista previa con datos de prueba de los documentos de Prácticas Profesionales.
 *
 * Pide el PDF por AJAX mandando la configuración tal como está en el editor
 * —aunque no se haya guardado— y lo muestra en un modal, sin navegar al
 * controlador ni abrir pestañas. El endpoint no guarda nada: no consume folio ni
 * escribe en la base de datos.
 */
(function () {
  const ENDPOINT = 'controller/practices/previewCartaPP.php';
  const MODAL_ID = 'previewPdfModal';

  let urlActual = null;   // object URL del PDF en pantalla, para liberarlo al cerrar

  /** Crea el modal la primera vez y lo reutiliza en adelante. */
  function obtenerModal() {
    let el = document.getElementById(MODAL_ID);
    if (el) return el;

    el = document.createElement('div');
    el.id = MODAL_ID;
    el.className = 'modal fade';
    el.tabIndex = -1;
    el.setAttribute('aria-hidden', 'true');
    el.innerHTML = `
      <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content" style="height:90vh">
          <div class="modal-header py-2">
            <h5 class="modal-title" style="font-size:1rem">
              <i class="fa-solid fa-file-pdf me-2 text-danger"></i>
              <span class="preview-titulo">Vista previa</span>
              <small class="text-muted ms-2" style="font-size:.75rem">datos de prueba · no se guarda nada</small>
            </h5>
            <div class="d-flex align-items-center gap-2">
              <a class="btn btn-sm btn-outline-secondary preview-abrir" target="_blank" rel="noopener">
                <i class="fa-solid fa-up-right-from-square me-1"></i>Abrir aparte
              </a>
              <a class="btn btn-sm btn-outline-secondary preview-descargar" download>
                <i class="fa-solid fa-download me-1"></i>Descargar
              </a>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
          </div>
          <div class="modal-body p-0" style="background:#525659">
            <iframe class="preview-visor" style="width:100%;height:100%;border:0" title="Vista previa del documento"></iframe>
          </div>
        </div>
      </div>`;
    document.body.appendChild(el);

    // Al cerrar se libera el object URL: si no, el PDF se queda en memoria.
    el.addEventListener('hidden.bs.modal', () => {
      el.querySelector('.preview-visor').src = 'about:blank';
      if (urlActual) {
        URL.revokeObjectURL(urlActual);
        urlActual = null;
      }
    });

    return el;
  }

  /**
   * @param {'carta'|'constancia'} doc    Documento a previsualizar.
   * @param {Object}               config Configuración recolectada del formulario.
   * @param {string}               titulo Encabezado del modal.
   */
  window.abrirVistaPreviaPP = async function (doc, config, titulo) {
    const meta = document.querySelector('meta[name="csrf-token"]');
    const boton = document.getElementById('btnPreviewPdf');
    const textoOriginal = boton ? boton.innerHTML : '';

    if (boton) {
      boton.disabled = true;
      boton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Generando…';
    }

    try {
      const res = await fetch(ENDPOINT, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': meta ? meta.getAttribute('content') : ''
        },
        body: JSON.stringify({ doc, config })
      });

      if (!res.ok) {
        let msg = 'No se pudo generar la vista previa.';
        try {
          const data = await res.json();
          if (data && data.error) msg = data.error;
        } catch (_) { /* la respuesta no era JSON: se queda el mensaje genérico */ }
        throw new Error(msg);
      }

      const blob = await res.blob();
      if (urlActual) URL.revokeObjectURL(urlActual);
      urlActual = URL.createObjectURL(blob);

      const el = obtenerModal();
      el.querySelector('.preview-titulo').textContent = titulo || 'Vista previa';
      el.querySelector('.preview-visor').src = urlActual;
      const descargar = el.querySelector('.preview-descargar');
      descargar.href = urlActual;
      descargar.setAttribute('download', doc === 'carta'
        ? 'Vista_previa_carta_presentacion.pdf'
        : 'Vista_previa_constancia.pdf');
      // Salida alterna por si el visor embebido del navegador falla. Apunta al
      // object URL, no al controlador, así que tampoco se navega al endpoint.
      el.querySelector('.preview-abrir').href = urlActual;

      bootstrap.Modal.getOrCreateInstance(el).show();
    } catch (err) {
      if (window.Swal) {
        Swal.fire({ icon: 'error', title: 'Vista previa', text: err.message });
      } else {
        alert(err.message);
      }
    } finally {
      if (boton) {
        boton.disabled = false;
        boton.innerHTML = textoOriginal;
      }
    }
  };
})();
