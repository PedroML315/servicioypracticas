document.addEventListener("DOMContentLoaded", function () {
  solicitudes();
});

function solicitudes() {
  $.ajax({
    method: "POST",
    url: "controller/practices/students.php",
    data: { action: "start" },
    dataType: "json",
    success: function (response) {
      if (!Array.isArray(response) || response.length === 0) {
        $(".solicitudes").html(
          '<div class="alert alert-info">No hay solicitudes registradas.</div>'
        );
        return;
      }

      let html = '<div class="row gx-3 gy-4">';
      response.forEach((item) => {
        // formatea horario y apoyo
        const horario = `${item.dia_inicio} → ${
          item.dia_fin
        }, ${item.hora_inicio.slice(0, 5)}–${item.hora_fin.slice(0, 5)}`;
        const apoyo =
          item.ofrece_apoyo_economico == 1
            ? `<dt class="col-sm-4">Monto</dt><dd class="col-sm-8">${item.monto_apoyo}</dd>`
            : "";

        // badge de estado
        const badgeClass =
          item.aceptado == 1 ? "bg-success" : "bg-warning text-dark";
        const badgeText = item.aceptado == 1 ? "Aceptado" : "Pendiente";

        const buttons =
          item.aceptado == 1
            ? `<div>
                    <button class="btn btn-sm btn-outline-info me-1 look-users" title="Ver practicantes" data-id="${item.id}">
                        <i class="fas fa-users"></i>
                    </button>
                </div>`
            : `<div>
                    <button class="btn btn-sm btn-outline-primary edit-solicitud" title="Editar" data-id="${item.id}">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger me-1 delete-solicitud" title="Eliminar" data-id="${item.id}">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>`;

        html += `
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">${item.licenciatura}</h5>
                        ${buttons}
                    </div>
                    <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Empresa</dt><dd class="col-sm-8">${
                          item.empresa || "–"
                        }</dd>
                        <dt class="col-sm-4">Giro</dt><dd class="col-sm-8">${
                          item.giro || "–"
                        }</dd>
                        <dt class="col-sm-4">Ciudad</dt><dd class="col-sm-8">${
                          item.ciudad || "–"
                        }</dd>
                        <dt class="col-sm-4">Dirección</dt><dd class="col-sm-8">${
                          item.direccion_practica || "–"
                        }</dd>
                        
                        <dt class="col-sm-4">Responsable</dt>
                        <dd class="col-sm-8">
                        ${item.nombre_responsable}<br>
                        <small><i class="fas fa-phone-alt"></i> ${
                          item.telefono
                        }</small>
                        </dd>

                        <dt class="col-sm-4"># Practicantes</dt>
                        <dd class="col-sm-8">${item.num_practicantes}</dd>

                        <dt class="col-sm-4">Actividades</dt>
                        <dd class="col-sm-8">${item.actividades}</dd>

                        <dt class="col-sm-4">Capacidades</dt>
                        <dd class="col-sm-8">${item.capacidades || "–"}</dd>

                        <dt class="col-sm-4">Actitudes</dt>
                        <dd class="col-sm-8">${item.actitudes || "–"}</dd>

                        <dt class="col-sm-4">Horario</dt><dd class="col-sm-8">${horario}</dd>

                        <dt class="col-sm-4">Apoyo Económico</dt>
                        <dd class="col-sm-8">${
                          item.ofrece_apoyo_economico == 1 ? "Sí" : "No"
                        }</dd>
                        ${apoyo}

                        <dt class="col-sm-4">Límite incorporación</dt>
                        <dd class="col-sm-8">${item.fecha_limite}</dd>
                    </dl>
                    </div>
                    <div class="card-footer text-muted d-flex justify-content-between align-items-center">
                        <small>Creado: ${formatFecha(item.created_at)}</small>
                        <div>
                            <span class="badge ${badgeClass}">${badgeText}</span>
                            <span class="badge bg-secondary">${
                              item.modalidad
                            }</span>
                        </div>
                    </div>
                </div>
            </div>
            `;
      });
      html += "</div>";
      $(".solicitudes").html(html);
    },
    error: console.error,
  });
}
