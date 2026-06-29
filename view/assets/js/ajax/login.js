$(document).ready(function() {
    $('form').submit(function(event) {
        event.preventDefault(); // Evitar que el formulario se envíe normalmente

        // Obtener los valores del formulario
        var email = $('#email').val();
        var password = $('#password').val();

        // Realizar la petición AJAX
        $.ajax({
            type: 'POST',
            url: 'controller/ajax/login.php', // Reemplaza con la URL de tu backend para iniciar sesión
            data: {
                email: email,
                password: password
            },
            success: function(response) {
                // Manejar la respuesta del servidor
                if (response === 'ok') {
                    window.location.href = 'inicio'; // Redirigir a la página de inicio de sesión exitoso
                } else if(response === 'status') {
                    // Si el inicio de sesión falló, mostrar un mensaje de error
                    alert('Cuenta Deshabilitada, consulta con el administrador.');
                } else {
                    // Si el inicio de sesión falló, mostrar un mensaje de error
                    alert('Inicio de sesión fallido. Verifica tus credenciales.');
                }
            },
            error: function(xhr, status, error) {
                // Manejar errores de la petición AJAX
                console.error('Error al iniciar sesión:', error);
                alert('Hubo un error al intentar iniciar sesión. Inténtalo de nuevo más tarde.');
            }
        });
    });
});