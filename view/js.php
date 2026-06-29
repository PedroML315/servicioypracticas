<script src="view/assets/libs/Datatables/datatables.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://kit.fontawesome.com/f4781c35cc.js" crossorigin="anonymous"></script>

<script>
$(document).ready(function() {
    // Manejar el clic en el botón de navegación
    $('.navbar-toggler').on('click', function(e) {
        e.stopPropagation(); // Evitamos que este clic se propague al documento
        $(this).toggleClass('active');
        $('.navbar-collapse').toggleClass('show');
    });

    // Cerrar el menú al hacer clic en un enlace del menú
    $('.nav-link').on('click', function() {
        $('.navbar-collapse').removeClass('show');
        $('.navbar-toggler').removeClass('active');
        // Remover el backdrop si existe
        const existingBackdrop = document.querySelector('.modal-backdrop');
        if (existingBackdrop) {
            existingBackdrop.classList.remove('show');
            existingBackdrop.remove();
        }
    });

    // Cerrar el menú al hacer clic fuera de él
    $(document).on('click', function(event) {
        // Si el menú está abierto (tiene la clase "show")
        if ($('.navbar-collapse').hasClass('show')) {
            // Y el clic NO ocurrió dentro de .navbar-collapse ni en .navbar-toggler
            if (!$(event.target).closest('.navbar-collapse, .navbar-toggler').length) {
                $('.navbar-collapse').removeClass('show');
                $('.navbar-toggler').removeClass('active');

                // Remover el backdrop si existe
                const existingBackdrop = document.querySelector('.modal-backdrop');
                if (existingBackdrop) {
                    existingBackdrop.classList.remove('show');
                    existingBackdrop.remove();
                }
            }
        }
    });
});

function closeMenu(navbarId) {
    document.getElementById(navbarId).classList.remove('show');
    $('.navbar-toggler').removeClass('active');
}

function logout() {
    $.ajax({
        type: 'POST',
        url: 'controller/ajax/logout.php',
        success: function(response) {
            window.location.href = 'login';
        },
        error: function(xhr, status, error) {
            console.error('Error al cerrar sesión:', error);
            alert('Hubo un error al intentar cerrar sesión. Inténtalo de nuevo más tarde.');
        }
    });
}

$(document).on('click', '#modalAcceptButton', function() {
    var formData = $('#modalForm').serialize();
    $.ajax({
        type: "POST",
        url: "controller/ajax/ajax.form.php",
        data: formData,
        success: function(response) {

            $('#tableEvents').DataTable().ajax.reload();
            var formDataArray = formData.split('&');
            var downloadAttendanceListPresent = false;
            for (var i = 0; i < formDataArray.length; i++) {
                var pair = formDataArray[i].split('=');
                if (pair[0] === 'downloadAttendanceList') {

                    if (response === 'ok') {
                        downloadAttendanceListPresent = pair[1];
                        $('.titleEvent').html('Descargar asistencia');
                        $('.resultFooter').html('');
                        $('.resultModal').html(`
                            <center>
                            <a class="btn btn-success btn-download" href="view/assets/docs/${downloadAttendanceListPresent}/lista_invitados.xlsx" download>
                                <span class="download-text">Descargar</span>
                                <i class="fas fa-arrow-down"></i>
                            </a>
                            </center>
                        `);
                        $('#resultModal').modal('show');
                    }
                }
            }
            verificarEventosActivos();
            $('#tableEvents').DataTable().ajax.reload();
        },
        error: function(error) {
            // Maneja el error si es necesario
            console.log("Error en la solicitud AJAX:", error);
        }
    });

    $('#tableEvents').DataTable().ajax.reload();
});

function verificarEventosActivos() {
    return new Promise((resolve, reject) => {
        $.ajax({
            type: "GET",
            url: "controller/ajax/verificarEventosActivos.php",
            success: function(response) {
                active = response;
            },
            error: function(error) {
                console.log("Error en la solicitud AJAX:", error);
                reject(error);
            }
        });
    });
}

function showAlertBootstrap(title, message) {
    $('#modalLabel').text(title);
    $('.modal-body-extra').html(message);
    $('.modal-footer-extra').html(
        '<button type="button" class="btn btn-success" data-bs-dismiss="modal">Aceptar</button>');
    $('#alertModal').modal('show');
}

function showAlertBootstrap1(title, message, id) {
    $('#modalLabel').text(title);
    $('.modal-body-extra').html(message);
    $('.modal-footer-extra').html(
        '<button type="button" class="btn btn-success" data-bs-dismiss="modal" onclick="showModal(\'' + id +
        '\')">Aceptar</button>');
    $('#alertModal').modal('show');
}
</script>

<!-- Bootstrap Modal for Alerts -->
<div class="modal fade modal2" id="alertModal" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true"
    data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalLabel">Alert</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body modal-body-extra">
                Alert message.
            </div>
            <div class="modal-footer modal-footer-extra">
            </div>
        </div>
    </div>
</div>