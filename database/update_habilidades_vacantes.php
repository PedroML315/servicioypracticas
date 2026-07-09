<?php

require_once __DIR__ . '/../model/conection.php';

$pdo = Conexion::conectar();

echo "Iniciando actualización: Habilidades en solicitudes de practicantes...\n\n";

try {
    /* ── 1. Catálogo institucional de habilidades ── */
    $pdo->exec("CREATE TABLE IF NOT EXISTS habilidades_catalogo (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        nombre VARCHAR(120) COLLATE utf8mb4_unicode_ci NOT NULL,
        area VARCHAR(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Área de conocimiento para agrupar en la UI',
        activo TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_habilidad_nombre (nombre)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "   [OK] Tabla 'habilidades_catalogo' verificada/creada.\n";

    /* ── 2. Relación vacante ↔ habilidades ──
       habilidad_id NULL = habilidad personalizada escrita por el organismo
       (queda identificable para una futura curaduría del catálogo).
       'nombre' guarda siempre el texto tal como se publicó (snapshot). */
    $pdo->exec("CREATE TABLE IF NOT EXISTS solicitud_habilidades (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        solicitud_id INT UNSIGNED NOT NULL COMMENT 'FK a solicitudes_practicantes.id',
        habilidad_id INT UNSIGNED DEFAULT NULL COMMENT 'FK a habilidades_catalogo.id; NULL = personalizada',
        nombre VARCHAR(120) COLLATE utf8mb4_unicode_ci NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_sh_solicitud (solicitud_id),
        KEY idx_sh_habilidad (habilidad_id),
        CONSTRAINT fk_sh_solicitud FOREIGN KEY (solicitud_id)
            REFERENCES solicitudes_practicantes (id) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT fk_sh_habilidad FOREIGN KEY (habilidad_id)
            REFERENCES habilidades_catalogo (id) ON DELETE SET NULL ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "   [OK] Tabla 'solicitud_habilidades' verificada/creada.\n";

    /* ── 3. licenciatura pasa a ser opcional (dato legado) ── */
    $col = $pdo->query("SHOW COLUMNS FROM solicitudes_practicantes LIKE 'licenciatura'")->fetch();
    if ($col && strtoupper($col['Null']) === 'NO') {
        $pdo->exec("ALTER TABLE solicitudes_practicantes
                    MODIFY licenciatura VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL
                    COMMENT 'Legado: vacantes previas al modelo de habilidades'");
        echo "   [OK] Columna 'licenciatura' ahora permite NULL (legado).\n";
    } else {
        echo "   [INFO] La columna 'licenciatura' ya permite NULL.\n";
    }

    /* ── 4. Catálogo semilla ── */
    $catalogo = [
        'Administración y Negocios' => [
            'Administración de proyectos',
            'Atención al cliente',
            'Negociación',
            'Gestión de inventarios y almacén',
            'Reclutamiento y selección de personal',
            'Análisis financiero',
            'Contabilidad general',
            'Facturación y nómina',
            'Logística y cadena de suministro',
            'Elaboración de reportes ejecutivos',
            'Comercio internacional',
            'Servicio y hospitalidad turística',
            'Organización de eventos',
        ],
        'Jurídico y Gestión Pública' => [
            'Redacción de documentos legales',
            'Análisis y revisión de contratos',
            'Trámites y gestión administrativa',
            'Atención y orientación ciudadana',
            'Archivo y control documental',
        ],
        'Tecnología y Datos' => [
            'Ofimática (Word, PowerPoint, correo)',
            'Excel intermedio-avanzado',
            'Programación y desarrollo web',
            'Bases de datos',
            'Soporte técnico',
            'Análisis de datos',
            'Administración de sitios web',
            'Redes y telecomunicaciones',
        ],
        'Comunicación y Diseño' => [
            'Diseño gráfico',
            'Edición de video',
            'Fotografía',
            'Producción audiovisual',
            'Producción y edición de audio',
            'Redacción y corrección de estilo',
            'Manejo de redes sociales (community management)',
            'Mercadotecnia digital',
            'Diseño editorial',
            'Animación y multimedia',
        ],
        'Salud y Bienestar' => [
            'Atención al paciente',
            'Rehabilitación y evaluación física',
            'Orientación nutricional',
            'Primeros auxilios',
            'Acompañamiento psicológico',
        ],
        'Educación y Desarrollo Humano' => [
            'Docencia y apoyo escolar',
            'Diseño de material didáctico',
            'Cuidado y desarrollo infantil',
            'Actividades deportivas y recreativas',
            'Tutoría y acompañamiento académico',
            'Inglés u otros idiomas',
        ],
        'Arquitectura e Ingeniería' => [
            'Dibujo arquitectónico (AutoCAD)',
            'Supervisión de obra',
            'Diseño industrial',
            'Gestión ambiental y sustentabilidad',
        ],
        'Habilidades Generales' => [
            'Trabajo en equipo',
            'Comunicación efectiva',
            'Liderazgo',
            'Organización y puntualidad',
            'Resolución de problemas',
            'Pensamiento analítico',
            'Creatividad',
            'Responsabilidad y ética',
        ],
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO habilidades_catalogo (nombre, area) VALUES (:nombre, :area)");
    $insertadas = 0;
    foreach ($catalogo as $area => $habilidades) {
        foreach ($habilidades as $nombre) {
            $stmt->execute([':nombre' => $nombre, ':area' => $area]);
            $insertadas += $stmt->rowCount();
        }
    }
    echo "   [OK] Catálogo semilla: $insertadas habilidades nuevas insertadas.\n";

    /* ── 5. Redacción de plantillas de correo ──
       {{degreeName}} ahora recibe el resumen de habilidades del perfil
       (o la licenciatura en vacantes legadas); se ajusta el texto. */
    $afectadas = $pdo->exec(
        "UPDATE email_templates
         SET html = REPLACE(html, 'para la carrera de', 'con el perfil solicitado:'),
             text_plain = REPLACE(text_plain, 'para la carrera de', 'con el perfil solicitado:')
         WHERE tkey IN ('solicitud_practicantes_aceptada', 'solicitud_practicantes_rechazada')
           AND (html LIKE '%para la carrera de%' OR text_plain LIKE '%para la carrera de%')"
    );
    echo "   [OK] Plantillas de correo actualizadas: $afectadas.\n";

    echo "\nActualización completada correctamente.\n";
} catch (PDOException $e) {
    echo "\n[ERROR] " . $e->getMessage() . "\n";
}
