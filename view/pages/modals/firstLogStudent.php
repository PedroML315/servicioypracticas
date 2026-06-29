<!-- Modal para seleccionar tipo de servicio social en primer inicio de sesión -->
<div class="modal fade" id="firstLogModal" tabindex="-1" aria-labelledby="firstLogModalLabel" aria-hidden="true"
  data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title mx-auto" id="firstLogModalLabel">Selecciona el tipo de servicio social</h5>
      </div>
      <div class="modal-body">
        <form id="servicioSocialForm" novalidate>
          <div class="mb-4 text-center">
            <p class="fs-5 mb-3">Por favor selecciona una opción para tu servicio social:</p>
          </div>
          <div class="row justify-content-center mb-3">
            <div class="col-md-6">
              <div class="alert alert-info d-flex align-items-center" role="alert">
                <i class="bi bi-info-circle-fill me-2"></i>
                <div>
                  <strong>Nota:</strong> Dependiendo del tipo de servicio social que elijas, solo recibirás eventos
                  relacionados con esa opción.
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="alert alert-warning d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <div>
                  <strong>Importante:</strong> Esta opción no se puede cambiar después de seleccionarla.
                </div>
              </div>
            </div>
          </div>
          <div class="mb-4">
            <div class="d-flex flex-wrap justify-content-center gap-4 serviceSelection">
              <!-- Aquí se agregarán los botones de selección de servicio social -->
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer justify-content-center">
        <button id="btnContinuar" type="submit" form="servicioSocialForm" class="btn btn-primary px-5"
          disabled>Continuar</button>
      </div>
    </div>
  </div>
</div>
<script>
  const config = {
    API: 'controller/ajax/ajax.forms.php'
  }
  document.addEventListener('DOMContentLoaded', () => {
    // Carga los tipos de servicio social disponibles
    GetSetvices();

    const modalEl = document.getElementById('firstLogModal');
    if (!modalEl) return;

    // Instancia con bloqueo total (clic fuera / ESC)
    const modal = new bootstrap.Modal(modalEl, {
      backdrop: 'static',
      keyboard: false
    });

    // Muestra el modal (haz esto solo si es "primer inicio")
    // Por ejemplo: si tu backend te pone una variable global window.necesitaElegirServicio = true;
    if (window.necesitaElegirServicio !== false) {
      modal.show();
    }

    // Flag para permitir cierre solo cuando el backend confirme guardado
    let puedeCerrar = false;

    // Si alguien intenta cerrar por código o eventos, lo bloqueamos
    modalEl.addEventListener('hide.bs.modal', (ev) => {
      if (!puedeCerrar) {
        ev.preventDefault();
        ev.stopImmediatePropagation();
      }
    }, true);

    const btnContinuar = document.getElementById('btnContinuar');
    const form = document.getElementById('servicioSocialForm');

    // Habilita el botón solo si hay selección
    const updateBtnState = () => {
      const seleccionado = form.querySelector('input[name="tipoServicio"]:checked');
      btnContinuar.disabled = !seleccionado;
    };
    form.addEventListener('change', updateBtnState);
    updateBtnState();

    // Maneja el envío del formulario
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const seleccionado = form.querySelector('input[name="tipoServicio"]:checked');
      if (seleccionado) {
        console.log('Seleccionado:', seleccionado.value);
        selectServices(seleccionado.value);
      }
    });
  });

  function GetSetvices() {
    $.ajax({
      url: config.API,
      method: 'POST',
      data: { action: 'getServicesActives' },
      dataType: 'json',
      success: function (response) {
        if (response && Array.isArray(response)) {
          const container = document.querySelector('.serviceSelection');
          container.innerHTML = ''; // Limpia contenido previo
          response.forEach(service => {
            const button = document.createElement('div');
            button.className = 'service-option';
            button.innerHTML = `
              <input type="radio" class="btn-check" name="tipoServicio" id="service${service.idTipoSer}" value="${service.idTipoSer}" autocomplete="off">
              <label class="btn btn-outline-primary d-flex flex-column align-items-center p-3" for="service${service.idTipoSer}">
                <span>${service.nombre}</span>
              </label>
            `;
            container.appendChild(button);
          });
        } else {
          alert('Error al cargar los tipos de servicio. Intenta recargar la página.');
        }
      },
      error: function () {
        alert('Error en la comunicación con el servidor. Intenta recargar la página.');
      }
    });
  }

  function selectServices(serviceType) {
    $.ajax({
      url: config.API,
      method: 'POST',
      data: { action: 'selectServiceActive', serviceType: serviceType },
      dataType: 'json',
      success: function (response) {
        if (response == 'success') {
          location.reload();
        } else {
          alert('Error al guardar la selección. Intenta de nuevo.');
        }
      }
    });
  }
</script>