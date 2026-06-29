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
    <title>Iniciar sesión</title>
    <script src="https://kit.fontawesome.com/f4781c35cc.js" crossorigin="anonymous"></script>
    <style>
        :root {
            --page-bg: #f2f5f5;
            --card-bg: #ffffff;
            --text-color: #333333;
            --input-underline: #cccccc;
            --input-focus: #333333;
            --accent-blue: rgb(24, 90, 7);
            --accent-blue-hover: rgb(41, 122, 38);
        }

        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
            background-color: var(--page-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .login-card {
            position: relative;
            background-color: var(--card-bg);
            border-radius: 3rem;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-wrap: wrap;
            max-width: 860px;
            width: 100%;
            overflow: hidden;
            min-height: 560px;
        }

        .login-card .left-side {
            flex: 1 1 45%;
            position: relative;
            overflow: hidden;
            min-height: 420px;
            padding: 0;
        }

        .login-card .left-side img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center top;
            display: block;
        }

        .login-card .right-side {
            flex: 1 1 50%;
            padding: 3rem 2.5rem 3rem 0rem;
            color: var(--text-color);
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: #fff;
        }

        .login-card .right-side h3 {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .input-group-underline {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .input-group-underline input {
            width: 100%;
            border: none;
            border-bottom: 1px dashed var(--input-underline);
            padding: 0.75rem 2.5rem 0.25rem 2.5rem;
            background: transparent;
            font-size: 1rem;
            color: var(--text-color);
            outline: none;
        }

        .input-group-underline input:focus {
            border-bottom: 1px solid var(--input-focus);
        }

        .input-group-underline select {
            width: 100%;
            border: none;
            border-bottom: 1px dashed var(--input-underline);
            padding: 0.75rem 2.5rem 0.25rem 2.5rem;
            background: transparent;
            font-size: 1rem;
            color: var(--text-color);
            outline: none;
        }
        .input-group-underline select:focus {
            border-bottom: 1px solid var(--input-focus);
        }

        .input-group-underline .icon-left,
        .input-group-underline .icon-right {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1rem;
            color: #888888;
        }

        .icon-left {
            left: 0.5rem;
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
            accent-color: var(--input-focus);
            margin-right: 0.5rem;
            width: 1rem;
            height: 1rem;
        }

        .form-check-custom label {
            font-size: 0.9rem;
            color: #555555;
        }

        .btn-login {
            width: 100%;
            background-color: var(--accent-blue);
            border: none;
            border-radius: 0.5rem;
            padding: 0.75rem;
            font-size: 1rem;
            font-weight: 500;
            color: #fff;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn-login:hover {
            background-color: var(--accent-blue-hover);
        }

        /* ==== NUEVO: selector ligero ==== */
        .register-select {
            display: flex;
            gap: 0.5rem;
            margin-top: 1.5rem;
            justify-content: center;
        }

        .register-select select {
            flex: 1;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            border: 1px solid #ccc;
            border-radius: 0.5rem;
            outline: none;
            background: #fff;
            color: var(--text-color);
        }

        .btn-go {
            padding: 0.75rem 1.5rem;
            background-color: var(--accent-blue);
            color: #fff;
            border: none;
            border-radius: 0.5rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn-go:hover {
            background-color: var(--accent-blue-hover);
        }

        /* ── Tablet (≤ 900px) ── */
        @media (max-width: 900px) {
            .login-card {
                max-width: 680px;
            }

            .login-card .left-side {
                flex: 1 1 40%;
                min-height: 340px;
            }

            .login-card .right-side {
                flex: 1 1 55%;
                padding: 2.5rem 2rem 2.5rem 1.5rem;
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
                padding: 0.7rem;
            }

            .register-select {
                flex-direction: column;
            }

            .btn-go {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="login-wrapper">
        <div class="login-card">

            <!-- ILUSTRACIÓN -->
            <div class="left-side">
                <img src="view/assets/images/login-ilustration.jpg" alt="Ilustración">
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
                    <center style="margin-top: 1rem; font-size: 0.9rem; color: #555; text-align: center;">
                        ¿No tienes cuenta?
                    </center>
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