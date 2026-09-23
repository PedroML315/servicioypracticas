<?php
// -------------------------------------------------------------------
// 1) Lectura de cookies para prellenar campos si “Remember me” fue marcado
// -------------------------------------------------------------------
$coockieUserType = isset($_COOKIE['user_type']) ? $_COOKIE['user_type'] : '';
$cookieMail = isset($_COOKIE['email']) ? $_COOKIE['email'] : '';
$cookiePass = isset($_COOKIE['password']) ? $_COOKIE['password'] : '';

// Si se pasan parámetros por GET, se usan para prellenar los campos
$userType = (isset($_GET['user_type']) && $_GET['user_type'] !== '') ? $_GET['user_type'] : $coockieUserType;
$mail = (isset($_GET['mail']) && $_GET['mail'] !== '') ? $_GET['mail'] : $cookieMail;
$password = (isset($_GET['password']) && $_GET['password'] !== '') ? $_GET['password'] : $cookiePass;
$rememberChecked = !empty($cookieMail);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Iniciar sesión2</title>
    <script src="https://kit.fontawesome.com/f4781c35cc.js" crossorigin="anonymous"></script>
    <style>
        :root {
            --page-bg: #ffffff;
            --card-bg: #ffffff;
            --text-color: #3a3a3a;
            --title-color: #16225c;
            --muted-color: #6b7280;
            --input-underline: #cfcfcf;
            --input-focus: #333333;
            --accent-green: #14651a;
            --accent-green-hover: #1b8324;
            --field-border: #d8dbdf;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
            background-color: var(--page-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            /* el brazo sobresale de la tarjeta: evita scroll horizontal */
            overflow-x: hidden;
        }

        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem 3.5rem;
        }

        .login-card {
            position: relative;
            background-color: var(--card-bg);
            border-radius: 28px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.10);
            display: flex;
            align-items: stretch;
            max-width: 900px;
            width: 100%;
            /* la ilustración va a sangre: sin marco blanco alrededor */
            padding: 0;
            gap: 3.5rem;
            overflow: visible;
        }

        /* El PNG mide 644x831, pero la tarjeta verde ocupa sólo x=65..643
           (578x831). Los 65px restantes son el brazo, en transparencia, que debe
           SOBRESALIR hacia la izquierda.
           - .left-side = tarjeta verde (proporción 578/831), a ras de los bordes
             superior, inferior e izquierdo de la tarjeta blanca.
           - imagen = 644/578 = 111.418% del panel, desplazada 65/578 = 11.245%.
           Se pintan DOS capas de la misma imagen (una sola descarga):
             · .left-side__card  recortada con el radio de la tarjeta → verde.
             · .left-side__arm   sin recortar, pero limitada con clip-path a la
               franja del brazo (65/644 = 10.09% del ancho de la imagen), que es
               justo lo que debe salirse. */
        .login-card .left-side {
            flex: 0 0 43%;
            align-self: flex-start;
            position: relative;
            overflow: visible;
            aspect-ratio: 578 / 831;
            padding: 0;
        }

        .login-card .left-side__card {
            position: absolute;
            inset: 0;
            overflow: hidden;
            /* border-radius: 28px 0 0 28px; */
        }

        .login-card .left-side img {
            position: absolute;
            top: 0;
            left: -11.245%;
            width: 111.418%;
            height: 100%;
            object-fit: fill;
            display: block;
        }

        .login-card .left-side__arm {
            /* sólo la franja transparente con el brazo (un pelo más ancha para
               que no quede costura contra la capa recortada) */
            clip-path: inset(0 89.8% 0 0);
            pointer-events: none;
        }

        .login-card .right-side {
            flex: 1 1 0;
            min-width: 0;
            padding: 1rem 4rem 0rem 0;
            color: var(--text-color);
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: #fff;
        }

        .login-card .right-side h3 {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1.2;
            color: var(--title-color);
            margin: 0 0 2rem;
        }

        .input-group-underline {
            position: relative;
            margin-bottom: 1.75rem;
        }

        .input-group-underline input,
        .input-group-underline select {
            width: 100%;
            border: none;
            border-bottom: 1px dashed var(--input-underline);
            padding: 0.6rem 2.25rem 0.6rem 2.75rem;
            background: transparent;
            font-size: 1.05rem;
            font-family: inherit;
            color: var(--text-color);
            outline: none;
        }

        .input-group-underline input:focus,
        .input-group-underline select:focus {
            border-bottom: 1px solid var(--input-focus);
        }

        .input-group-underline input::placeholder {
            color: #9aa0a6;
        }

        .input-group-underline .icon-left,
        .input-group-underline .icon-right {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.05rem;
            color: #9aa0a6;
        }

        .icon-left {
            left: 0.35rem;
        }

        .icon-right {
            right: 0.5rem;
            cursor: pointer;
        }

        .form-check-custom {
            display: flex;
            align-items: center;
            margin-bottom: 1.75rem;
        }

        .form-check-custom input {
            appearance: none;
            -webkit-appearance: none;
            margin: 0 0.65rem 0 0;
            width: 1.15rem;
            height: 1.15rem;
            border: 1px solid #c4c8cd;
            border-radius: 4px;
            background: #fff;
            cursor: pointer;
            position: relative;
            flex: 0 0 auto;
        }

        .form-check-custom input:checked {
            background: var(--accent-green);
            border-color: var(--accent-green);
        }

        .form-check-custom input:checked::after {
            content: '';
            position: absolute;
            left: 0.34rem;
            top: 0.1rem;
            width: 0.3rem;
            height: 0.6rem;
            border: solid #fff;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }

        .form-check-custom label {
            font-size: 0.95rem;
            color: var(--muted-color);
            cursor: pointer;
        }

        .btn-login {
            width: 100%;
            background-color: var(--accent-green);
            border: none;
            border-radius: 0.5rem;
            padding: 0.95rem;
            font-size: 1.05rem;
            font-family: inherit;
            font-weight: 700;
            color: #fff;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn-login:hover {
            background-color: var(--accent-green-hover);
        }

        .no-account {
            margin: 1.25rem 0 1.5rem;
            text-align: center;
            font-size: 0.95rem;
            color: var(--accent-green);
        }

        /* ==== Selector de registro ==== */
        .register-select select {
            width: 100%;
            padding: 0.9rem 1rem;
            font-size: 1rem;
            font-family: inherit;
            border: 1px solid var(--field-border);
            border-radius: 12px;
            outline: none;
            background: #fff;
            color: var(--text-color);
            cursor: pointer;
        }

        .register-select select:focus {
            border-color: var(--accent-green);
        }

        /* ── Tablet (≤ 900px) ── */
        @media (max-width: 900px) {
            .login-card {
                max-width: 720px;
                gap: 2rem;
            }

            .login-card .right-side {
                padding: 1.5rem 2rem 1.5rem 0;
            }

            .login-card .right-side h3 {
                font-size: 1.7rem;
                margin-bottom: 1.5rem;
            }
        }

        /* ── Mobile landscape / small tablet (≤ 768px) ── */
        @media (max-width: 768px) {
            .login-wrapper {
                padding: 0;
                align-items: stretch;
            }

            .login-card {
                flex-direction: column;
                border-radius: 0;
                box-shadow: none;
                max-width: 100%;
                min-height: 100vh;
                padding: 0;
                gap: 0;
            }

            .login-card .left-side {
                display: none;
            }

            .login-card .right-side {
                flex: 1 1 auto;
                padding: 3rem 2rem;
                justify-content: center;
            }
        }

        /* ── Mobile portrait (≤ 480px) ── */
        @media (max-width: 480px) {
            .login-card .right-side {
                padding: 2rem 1.25rem;
            }

            .login-card .right-side h3 {
                font-size: 1.5rem;
                margin-bottom: 1.25rem;
            }

            .input-group-underline {
                margin-bottom: 1.25rem;
            }

            .input-group-underline input,
            .input-group-underline select {
                font-size: 0.95rem;
            }

            .btn-login {
                font-size: 0.95rem;
                padding: 0.8rem;
            }
        }
    </style>
</head>

<body>
    <div class="login-wrapper">
        <div class="login-card">

            <!-- ILUSTRACIÓN -->
            <div class="left-side">
                <!-- capa recortada: la tarjeta verde con el radio de la card -->
                <div class="left-side__card">
                    <img src="view/assets/images/login-ilustration.png" alt="Ilustración">
                </div>
                <!-- capa sin recorte: sólo el brazo, que sobresale -->
                <img class="left-side__arm" src="view/assets/images/login-ilustration.png" alt="" aria-hidden="true">
            </div>

            <!-- FORMULARIO -->
            <div class="right-side">
                <h3>Iniciar sesión</h3>
                <form id="loginForm" autocomplete="off">
                    <!-- Selector de tipo de usuario para login -->
                    <div class="input-group-underline">
                        <i class="fas fa-users icon-left"></i>
                        <select 
                            id="userType" 
                            name="user_type"
                            required
                        >
                            <option value="">— Selecciona tipo de usuario —</option>
                            <option value="alumno_servicio"   <?= $userType === 'alumno_servicio'   ? 'selected' : '' ?>>Alumno (Servicio Social)</option>
                            <option value="alumno_practicas"   <?= $userType === 'alumno_practicas'   ? 'selected' : '' ?>>Alumno (Prácticas Profesionales)</option>
                            <option value="administrativo"     <?= $userType === 'administrativo'     ? 'selected' : '' ?>>Usuario Administrativo</option>
                            <option value="organismo_externo"  <?= $userType === 'organismo_externo'  ? 'selected' : '' ?>>Organismo Externo</option>
                        </select>
                    </div>
                    <div class="input-group-underline">
                        <i class="fas fa-user icon-left"></i>
                        <input
                            type="text"
                            id="email"
                            name="email"
                            placeholder="Correo institucional"
                            value="<?php echo htmlspecialchars($mail, ENT_QUOTES, 'UTF-8'); ?>"
                            required>
                    </div>
                    <div class="input-group-underline">
                        <i class="fas fa-lock icon-left"></i>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Contraseña"
                            value="<?php echo htmlspecialchars($password, ENT_QUOTES, 'UTF-8'); ?>"
                            required>
                    </div>
                    <div class="form-check-custom">
                        <input
                            type="checkbox"
                            id="rememberMe"
                            name="remember_me"
                            <?php echo $rememberChecked ? 'checked' : ''; ?>>
                        <label for="rememberMe">Recuérdame</label>
                    </div>
                    <button type="submit" class="btn-login">Iniciar sesión</button>

                    <!-- texto “¿No tienes cuenta?” -->
                    <p class="no-account">¿No tienes cuenta?</p>
                    <!-- SELECT UN SOLO CONTROL -->
                    <div class="register-select">
                        <select id="registerSelect">
                            <option value="">— Regístrate —</option>
                            <option value="inscripcionServicio">Alumno (Servicio Social)</option>
                            <option value="inscripcionPracticas">Alumno (Prácticas Profesionales)</option>
                            <option value="inscripcionEmpresas">Organismo externo</option>
                        </select>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        // cookies
        function setCookie(n, v, a) {
            document.cookie = n + "=" + encodeURIComponent(v) + "; max-age=" + a + "; path=/";
        }

        function deleteCookie(n) {
            document.cookie = n + "=; max-age=0; path=/";
        }

        $(function() {
            $('#loginForm').on('submit', function(e) {
                e.preventDefault();
                var remember = $('#rememberMe').is(':checked');
                var data = $(this).serialize();
                $.post('controller/ajax/ajax.login.php', data)
                    .done(function(resp) {
                        if (resp.trim() === 'success') {
                            if (remember) {
                                // Guardar cookies si "Recuérdame" está marcado
                                setCookie('user_type', $('#userType').val(), 60 * 60 * 24 * 30);
                                setCookie('email', $('#email').val(), 60 * 60 * 24 * 30);
                                setCookie('password', $('#password').val(), 60 * 60 * 24 * 30);
                            } else {
                                deleteCookie('user_type');
                                deleteCookie('email');
                                deleteCookie('password');
                            }
                            location.href = './';
                        } else {
                            alert('Correo o contraseña incorrectos');
                        }
                    })
                    .fail(function() {
                        alert('Error en la petición. Intenta de nuevo.');
                    });
            });

            $('#registerSelect').on('change', function() {
                var url = $(this).val();
                if (url) {
                    window.open(url, '_blank');
                }
            });
        });
    </script>
</body>

</html>