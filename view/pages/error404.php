<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error 404 - Página no encontrada</title>
    <style>
        :root {
            --page-bg: #f2f5f5;          /* Fondo de toda la página */
            --card-bg: #ffffff;         /* Fondo de las tarjetas */
            --text-color: #333333;      /* Color principal del texto */
            --accent-green: rgb(24, 90, 7);         /* Color de acento (botones, enlaces) */
            --accent-green-hover: rgb(41, 122, 38); /* Hover para acento */
            --input-underline: #cccccc;   /* Color de las líneas de inputs (no se usa aquí, pero para homogeneidad) */
            --input-focus: #333333;       /* Color de enfoque (idem) */
        }

        /* ===== ESTILOS BASE ===== */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            background-color: var(--page-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-color);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        img {
            max-width: 100%;
            height: auto;
        }

        /* ===== ESTRUCTURA DE LA TARJETA CENTRAL ===== */
        .error-wrapper {
            width: 100%;
            max-width: 800px;
            padding: 1rem;
        }

        .error-card {
            background-color: var(--card-bg);
            border-radius: 1rem;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            display: flex;
            flex-wrap: wrap;
        }

        /* ===== SECCIÓN IZQUIERDA: ilustración (oculta en móviles) ===== */
        .error-card .left-side {
            flex: 1 1 50%;
            background-color: var(--card-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .error-card .left-side img {
            max-width: 80%;
            height: auto;
            border-radius: 0.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: transform 0.4s;
        }

        .error-card .left-side img:hover {
            transform: scale(1.05);
        }

        /* ===== SECCIÓN DERECHA: contenido del error ===== */
        .error-card .right-side {
            flex: 1 1 50%;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .error-card .right-side .logo-container {
            position: absolute;
            top: 1rem;
            left: 1rem;
        }

        .error-card .right-side .logo-container img {
            max-width: 120px;
            height: auto;
            filter: drop-shadow(2px 2px 4px rgba(0, 0, 0, 0.2));
        }

        .error-card .right-side h1 {
            font-size: 3rem;
            font-weight: 700;
            margin: 0;
            margin-bottom: 0.5rem;
            color: var(--text-color);
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
        }

        .error-card .right-side p {
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            line-height: 1.4;
            color: #555555;
        }

        .error-card .right-side .btn-home {
            display: inline-block;
            background-color: var(--accent-green);
            color: #ffffff;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 500;
            transition: background-color 0.2s ease-in-out, transform 0.2s ease-in-out;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: fit-content;
        }

        .error-card .right-side .btn-home:hover {
            background-color: var(--accent-green-hover);
            transform: translateY(-2px);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .error-card {
                flex-direction: column;
            }
            .error-card .left-side {
                display: none;
            }
            .error-card .right-side {
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="error-wrapper">
        <div class="error-card">
            <!-- --------------------------------------------------
                 IZQUIERDA: ilustración de error (oculto en <768px)
            -------------------------------------------------- -->
            <div class="left-side">
                <!-- Puedes reemplazar esta ruta por cualquier imagen de tu carpeta /view/assets/images/error404/ -->
                <img src="view/assets/images/error404/illustration.png" alt="Ilustración 404">
            </div>

            <!-- --------------------------------------------------
                 DERECHA: contenido del mensaje de error
            -------------------------------------------------- -->
            <div class="right-side">
                <!-- Logo fijo en la esquina superior izquierda -->
                <div class="logo-container">
                    <img src="view/assets/images/logo-color.png" alt="Logo">
                </div>

                <h1>Error 404</h1>
                <p>
                    Lo sentimos, la página que estás buscando no existe o ha sido movida. 
                    Verifica la dirección e intenta nuevamente. Si crees que esto es un error, 
                    ponte en contacto con el administrador del sitio.
                </p>
                <a href="./" class="btn-home">Volver al inicio</a>
            </div>
        </div>
    </div>
</body>
</html>
