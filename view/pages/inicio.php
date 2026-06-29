<?php
$role = $_SESSION['user']['role'] ?? '';
?>
<style>
    :root {
        --page-bg: #f2f5f5;
        /* Fondo de toda la página */
        --card-bg: #ffffff;
        /* Fondo de la tarjeta */
        --text-color: #333333;
        /* Color principal del texto */
        --input-underline: #cccccc;
        /* Color de la línea inferior de inputs */
        --input-focus: #333333;
        /* Color de la línea inferior al enfocar */
        --accent-blue: rgb(24, 90, 7);
        /* Color del botón “Log In” */
        --accent-blue-hover: rgb(41, 122, 38);
        /* Hover del botón */
    }

    /* Estilo general */
    html,
    body {
        height: 100%;
        margin: 0;
        padding: 0;
        background-color: var(--page-bg);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    /* Título del tablero */
    #namePage {
        font-size: 1.25rem;
        text-transform: uppercase;
        letter-spacing: 0.05rem;
    }


    /* Estilo de la tarjeta de puntos totales */
    .card-body {
        padding: 20px;
        /* text-align: center; */
    }

    .card-subtitle {
        font-size: 0.75rem;
        color: #888;
        margin-bottom: 5px;
    }

    #totalPoints {
        font-size: 1rem;
        font-weight: 600;
        color: #565656;
    }

    /* Sección de eventos asistidos */
    .events-header {
        font-size: 0.875rem;
        font-weight: 500;
        color: #666;
        margin-bottom: 10px;
        text-align: center;
        border-bottom: 1px solid #eee;
        padding-bottom: 5px;
    }

    #eventList {
        max-height: 300px;
        overflow-y: auto;
        padding: 0;
        margin: 0;
        list-style: none;
    }

    .list-group-item {
        background-color: #fafafa !important;
        border: 1px solid #e0e0e0;
        border-radius: 5px;
        margin-bottom: 8px;
        padding: 10px;
        font-size: 0.75rem;
        color: #444;
        transition: background-color 0.3s ease;
    }

    .list-group-item:hover {
        background-color: #f0f0f0 !important;
    }

    /* Scrollbar personalizado */
    #eventList::-webkit-scrollbar {
        width: 6px;
    }

    #eventList::-webkit-scrollbar-thumb {
        background-color: #c0c0c0;
        border-radius: 3px;
    }

    #eventList::-webkit-scrollbar-track {
        background-color: #f5f5f5;
    }

    /* Diseño responsivo */
    @media (max-width: 768px) {
        #namePage {
            font-size: 1rem;
        }

        .card-subtitle {
            font-size: 0.625rem;
        }

        #totalPoints {
            font-size: 0.875rem;
        }

        .events-header {
            font-size: 0.75rem;
        }
    }

    td {
        font-family: 'Roboto', Arial, sans-serif !important;
    }
</style>

<div class="container-fluid">
    <?php 
    require_once 'config/selectDashboard.php';
    ?>
</div>