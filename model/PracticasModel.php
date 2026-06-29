<?php

class PracticasModel
{
    /* =========================
     * Helpers (privados, DRY)
     * ========================= */

    /** Cache de conexión para evitar reconectar en cada llamada */
    private static $pdo = null;

    private const RUBROS_LABELS = [
        'Asistencia y puntualidad',
        'Aplicación de conocimientos',
        'Contribución a la solución de problemas',
        'Iniciativa',
        'Responsabilidad',
        'Logro de objetivos planteados'
    ];

    private const ACTITUDES_LABELS = [
        'Demuestra interés en identificar y subsanar sus propias necesidades de aprendizaje',
        'Coopera eficiente y eficazmente con sus compañeros de trabajo, obtiene resultados esperados al trabajar en equipo',
        'Demuestra tolerancia ante las diferencias y propone estrategias para conciliar alternativas',
        'Expresa ideas verbalmente de forma clara, redacta distintos tipos de documentos con mínimos errores',
        'Busca soluciones efectivas tomando en cuenta reglas, normas y lineamientos establecidos por la autoridad',
        'Organiza actividades y materiales para obtener resultados en los tiempos establecidos, establece prioridades y las respeta',
        'Muestra interés y busca información adicional a la proporcionada',
        'Se adapta a situaciones nuevas, tolera el cambio y puede trabajar bajo presión',
        'Demuestra automotivación, se esmera en conseguir los resultados esperados, es dedicado y no espera necesariamente reconocimiento externo',
        'Se muestra receptivo, atento con la autoridad y responde de forma amable ante las exigencias'
    ];

    private static function db(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }
        $pdo = Conexion::conectar();
        // Endurecemos atributos, sin fallar si ya vienen configurados.
        try {
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (\Throwable $e) {
        }
        try {
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
        }
        try {
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (\Throwable $e) {
        }
        self::$pdo = $pdo;
        return self::$pdo;
    }

    private static function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    private static function all(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function one(string $sql, array $params = [])
    {
        return self::run($sql, $params)->fetch(PDO::FETCH_ASSOC);
    }

    private static function col(string $sql, array $params = [])
    {
        return self::run($sql, $params)->fetchColumn();
    }

    private static function aff(string $sql, array $params = []): int
    {
        return self::run($sql, $params)->rowCount();
    }

    private static function lastId(): string
    {
        return self::db()->lastInsertId();
    }

    private static function ok($message = null, $extra = [])
    {
        return ['success' => true, 'message' => $message] + $extra;
    }

    private static function fail($message, $extra = [])
    {
        return ['success' => false, 'message' => $message] + $extra;
    }

    /** Validación simple de identificadores (tabla/campo) */
    private static function isSafeIdent(string $ident): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9_]+$/', $ident);
    }

    /** Locks por nombre: evita condiciones de carrera (folios, etc.) */
    private static function acquireLock(string $name, int $timeout = 5): bool
    {
        return (bool) self::col("SELECT GET_LOCK(:n, :t)", [':n' => $name, ':t' => $timeout]);
    }

    private static function releaseLock(string $name): void
    {
        try {
            self::run("SELECT RELEASE_LOCK(:n)", [':n' => $name]);
        } catch (\Throwable $e) {
        }
    }

    /** Generador genérico de folios (con lock, sin cambios de firma pública) */
    private static function generateFolioGeneric(string $table, string $prefix, int $idStudent): string
    {
        // Whitelist de tablas soportadas para folio
        $allowed = ['cartas_practicas_profesionales', 'constancias_acreditacion'];
        if (!in_array($table, $allowed, true)) {
            throw new RuntimeException('Tabla no permitida para folios');
        }

        $year = date('Y');
        $like = "%-{$year}";

        // 1) ¿Ya existe uno para el alumno este año?
        $existing = self::col(
            "SELECT code FROM {$table} WHERE student_id = :sid AND code LIKE :p LIMIT 1",
            [':sid' => $idStudent, ':p' => $like]
        );
        if ($existing) {
            return $existing;
        }

        // 2) Lock nombrado para evitar colisiones de correlativo
        $lockName = "folio_{$table}_{$year}";
        $gotLock = self::acquireLock($lockName, 5);
        if (!$gotLock) {
            // Si no se pudo tomar lock, intentamos una sola vez sin lock para no bloquear UX,
            // pero el riesgo de colisión es muy bajo; si falla INSERT, reintentar con otro número.
        }

        try {
            // Recalcular con lock tomado (si aplica)
            $maxNum = (int) self::col(
                "SELECT MAX(
                    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(code, '-', 2), '-', -1) AS UNSIGNED)
                 ) AS max_num
                 FROM {$table}
                 WHERE code LIKE :p",
                [':p' => $like]
            );

            $next = $maxNum + 1;
            // Intento y, en caso raro de colisión, reintento breve (2 veces)
            for ($i = 0; $i < 3; $i++) {
                $folio = sprintf('%s-%03d-%s', $prefix, $next, $year);
                try {
                    self::aff(
                        "INSERT INTO {$table} (code, student_id) VALUES (:code, :sid)",
                        [':code' => $folio, ':sid' => $idStudent]
                    );
                    return $folio;
                } catch (\PDOException $e) {
                    // Si colisiona por UNIQUE externo, incrementa y reintenta
                    $next++;
                }
            }
            // Si llegamos aquí, algo va mal
            throw new RuntimeException('No fue posible generar el folio de forma segura.');
        } finally {
            if ($gotLock) {
                self::releaseLock($lockName);
            }
        }
    }

    /* =========================
     * ORGANISMOS EXTERNOS
     * ========================= */

    // Equivale a mdlGetNewOrganismosExternos (deja funcionalidad)
    static public function mdlGetNewOrganismosExternos()
    {
        return self::all(
            "SELECT * FROM organismos_externos WHERE isActive = 1 AND isAcepted = 0"
        );
    }

    static public function saveOrganismoExterno($data)
    {
        try {
            $sql = "INSERT INTO organismos_externos
                (tipo_persona, empresa, giro, fecha_constitucion, web,
                 calle, cp, colonia, ciudad, telefonos,
                 email, nombre_contacto, celular, rep_legal,
                 cargo_legal, email_legal, tel_oficina, actividades)
            VALUES
                (:tipoPersona, :empresa, :giro, :fecha_constitucion, :web,
                 :calle, :cp, :colonia, :ciudad, :telefonos,
                 :email, :nombre_contacto, :celular, :rep_legal,
                 :cargo_legal, :email_legal, :tel_oficina, :actividades)";
            self::run($sql, [
                ':tipoPersona' => $data['tipoPersona'] ?? null,
                ':empresa' => $data['empresa'] ?? null,
                ':giro' => $data['giro'] ?? null,
                ':fecha_constitucion' => $data['fecha_constitucion'] ?? null,
                ':web' => $data['web'] ?? null,
                ':calle' => $data['calle'] ?? null,
                ':cp' => $data['cp'] ?? null,
                ':colonia' => $data['colonia'] ?? null,
                ':ciudad' => $data['ciudad'] ?? null,
                ':telefonos' => $data['telefonos'] ?? null,
                ':email' => $data['email'] ?? null,
                ':nombre_contacto' => $data['nombre_contacto'] ?? null,
                ':celular' => $data['celular'] ?? null,
                ':rep_legal' => $data['rep_legal'] ?? null,
                ':cargo_legal' => $data['cargo_legal'] ?? null,
                ':email_legal' => $data['email_legal'] ?? null,
                ':tel_oficina' => $data['tel_oficina'] ?? null,
                ':actividades' => $data['actividades'] ?? null,
            ]);
            return [
                'success' => true,
                'id' => (int) self::lastId(),
                'message' => null
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'id' => null,
                'message' => 'Error BD: ' . $e->getMessage()
            ];
        }
    }

    static public function mdlGetExternals($idExternal = null)
    {
        if ($idExternal === null) {
            return self::all("SELECT * FROM organismos_externos WHERE isActive = 1");
        }
        return self::one(
            "SELECT * FROM organismos_externos WHERE isActive = 1 AND id = :id",
            [':id' => $idExternal]
        );
    }

    /* ─── Exportación Excel: organismos con datos completos + conteos ─── */
    static public function mdlExportOrganismos(): array
    {
        $sql = "SELECT
                    oe.id,
                    oe.empresa,
                    oe.tipo_persona,
                    oe.giro,
                    oe.fecha_constitucion,
                    oe.web,
                    oe.calle,
                    oe.cp,
                    oe.colonia,
                    oe.ciudad,
                    oe.telefonos,
                    oe.email,
                    oe.nombre_contacto,
                    oe.celular,
                    oe.rep_legal,
                    oe.cargo_legal,
                    oe.email_legal,
                    oe.tel_oficina,
                    oe.actividades,
                    oe.isAcepted,
                    oe.isActive,
                    oe.created_at,
                    COUNT(DISTINCT sip.idStudent)                                       AS num_students_total,
                    SUM(CASE WHEN sip.isAcepted = 0 THEN 1 ELSE 0 END)                AS num_pendientes,
                    SUM(CASE WHEN sip.isAcepted = 1 THEN 1 ELSE 0 END)                AS num_aceptados,
                    SUM(CASE WHEN sip.isAcepted = 2 THEN 1 ELSE 0 END)                AS num_rechazados
                FROM organismos_externos oe
                LEFT JOIN solicitudes_practicantes sp
                       ON sp.organismo_externo_id = oe.id AND sp.activo = 1
                LEFT JOIN students_in_practices sip
                       ON sip.idPractica = sp.id
                WHERE oe.isActive = 1
                GROUP BY oe.id
                ORDER BY oe.empresa ASC";
        return self::all($sql);
    }

    /* ─── Exportación Excel: todos los alumnos de todos los organismos (sin N+1) ─── */
    static public function mdlExportStudentsAllOrganismos(): array
    {
        $sql = "SELECT
                    oe.id                               AS organismo_id,
                    sip.isAcepted                       AS sip_status,
                    sip.start_date,
                    sp.id                               AS student_id,
                    sp.matricula,
                    sp.nombre_completo,
                    sp.email,
                    sp.telefono,
                    sp.programa_academico,
                    sp.periodo,
                    sp.curp,
                    sp.genero,
                    sp.fecha_nacimiento,
                    sp.tipo_practica,
                    sp.isActive,
                    sp.practicas_finalizadas,
                    sp.fecha_finalizacion,
                    sol.licenciatura,
                    sol.actividades,
                    sol.modalidad,
                    ROUND(
                        COALESCE(
                            SUM(
                                CASE WHEN ap.status = 'aprobado'
                                     THEN COALESCE(ap.horas_validadas, TIME_TO_SEC(TIMEDIFF(ap.hora_salida, ap.hora_entrada)) / 3600.0)
                                     ELSE 0
                                END
                            ), 0
                        ), 2
                    )                                   AS horas_acumuladas
                FROM organismos_externos oe
                JOIN solicitudes_practicantes sol
                       ON sol.organismo_externo_id = oe.id AND sol.activo = 1
                JOIN students_in_practices sip
                       ON sip.idPractica = sol.id
                JOIN students_practicas sp
                       ON sp.id = sip.idStudent
                LEFT JOIN asistencias_practicas ap
                       ON ap.idStudent = sp.id AND ap.idPractica = sol.id
                WHERE oe.isActive = 1
                GROUP BY sip.idSiP
                ORDER BY oe.empresa ASC, sip.isAcepted ASC, sp.nombre_completo ASC";
        return self::all($sql);
    }

    /* ─── Dashboard admin: organismos con conteo de estudiantes ─── */
    static public function mdlGetOrganismosConEstudiantes(): array
    {
        $sql = "SELECT
                    oe.id,
                    oe.empresa,
                    oe.ciudad,
                    oe.giro,
                    oe.email,
                    oe.nombre_contacto,
                    oe.isAcepted,
                    oe.solicitudes_bloqueadas,
                    oe.strikes_count,
                    COUNT(DISTINCT sp.id)                                           AS num_solicitudes,
                    COUNT(DISTINCT sip.idStudent)                                   AS num_students_total,
                    SUM(CASE WHEN sip.isAcepted = 0 THEN 1 ELSE 0 END)             AS num_pendientes,
                    SUM(CASE WHEN sip.isAcepted = 1 THEN 1 ELSE 0 END)             AS num_aceptados,
                    SUM(CASE WHEN sip.isAcepted = 2 THEN 1 ELSE 0 END)             AS num_rechazados
                FROM organismos_externos oe
                LEFT JOIN solicitudes_practicantes sp
                       ON sp.organismo_externo_id = oe.id AND sp.activo = 1
                LEFT JOIN students_in_practices sip
                       ON sip.idPractica = sp.id
                WHERE oe.isActive = 1
                GROUP BY oe.id
                ORDER BY oe.empresa ASC";
        return self::all($sql);
    }

    static public function mdlGetOrganismoById(int $id): ?array
    {
        $sql = "SELECT * FROM organismos_externos WHERE id = :id AND isActive = 1 LIMIT 1";
        $result = self::all($sql, [':id' => $id]);
        return $result[0] ?? null;
    }

    static public function mdlGetStudentsByOrganismo(int $orgId): array
    {
        $sql = "SELECT
                    sip.idStudent     AS sip_id,
                    sip.isAcepted     AS sip_status,
                    sip.start_date,
                    stud.id           AS student_id,
                    stud.nombre_completo,
                    stud.matricula,
                    stud.email,
                    stud.programa_academico,
                    stud.periodo,
                    sol.licenciatura,
                    sol.actividades,
                    sol.modalidad
                FROM students_in_practices sip
                JOIN students_practicas stud ON stud.id = sip.idStudent
                JOIN solicitudes_practicantes sol ON sol.id = sip.idPractica
                WHERE sol.organismo_externo_id = :id AND sol.activo = 1
                ORDER BY sip.isAcepted ASC, stud.nombre_completo ASC";
        return self::all($sql, [':id' => $orgId]);
    }

    static public function mdlGetStudentsSinOrganismo(): array
    {
        // Alumnos de tipo empresa que NO tienen práctica aceptada activa
        $sql = "SELECT
                    sp.id,
                    sp.nombre_completo,
                    sp.matricula,
                    sp.email,
                    sp.programa_academico,
                    sp.periodo,
                    sp.fecha_registro,
                    sp.isAcepted AS student_status,
                    CASE
                        WHEN sp.isAcepted = 0 THEN 'Registro pendiente de aprobación'
                        WHEN sp.isAcepted = 2 THEN 'Registro rechazado'
                        WHEN EXISTS (
                            SELECT 1 FROM students_in_practices sip2
                            JOIN solicitudes_practicantes sol2 ON sol2.id = sip2.idPractica
                            WHERE sip2.idStudent = sp.id AND sip2.isAcepted = 1
                        ) THEN 'activo'  -- filtrar luego
                        WHEN EXISTS (
                            SELECT 1 FROM students_in_practices sip3
                            JOIN solicitudes_practicantes sol3 ON sol3.id = sip3.idPractica
                            WHERE sip3.idStudent = sp.id AND sip3.isAcepted = 0
                        ) THEN 'Postulado — esperando respuesta del organismo'
                        ELSE 'Aceptado — sin postulación a organismo'
                    END AS proceso_etapa
                FROM students_practicas sp
                WHERE sp.isActive = 1
                  AND (sp.tipo_practica = 'empresa' OR sp.tipo_practica IS NULL)
                  AND NOT EXISTS (
                      SELECT 1 FROM students_in_practices sip
                      JOIN solicitudes_practicantes sol ON sol.id = sip.idPractica
                      WHERE sip.idStudent = sp.id AND sip.isAcepted = 1
                  )
                ORDER BY sp.isAcepted ASC, sp.fecha_registro DESC";
        // Exclude 'activo' rows (already placed) from the result
        $rows = self::all($sql);
        return array_values(
            array_filter(
                $rows,
                function ($r) {
                    return $r['proceso_etapa'] !== 'activo';
                }
            )
        );
    }

    static public function mdlGetOrganismoStats(): array
    {
        return [
            'total_organismos' => (int) self::col("SELECT COUNT(*) FROM organismos_externos WHERE isActive = 1"),
            'organismos_activos' => (int) self::col("SELECT COUNT(*) FROM organismos_externos WHERE isActive = 1 AND isAcepted = 1"),
            'total_en_practica' => (int) self::col(
                "SELECT COUNT(*) FROM students_in_practices sip
                 JOIN solicitudes_practicantes sp ON sp.id = sip.idPractica AND sp.activo = 1
                 WHERE sip.isAcepted = 1"
            ),
            'pendientes_org' => (int) self::col(
                "SELECT COUNT(*) FROM students_in_practices sip
                 JOIN solicitudes_practicantes sp ON sp.id = sip.idPractica AND sp.activo = 1
                 WHERE sip.isAcepted = 0"
            ),
            'sin_organismo' => (int) self::col(
                "SELECT COUNT(*) FROM students_practicas sp
                 WHERE sp.isActive = 1 AND (sp.tipo_practica = 'empresa' OR sp.tipo_practica IS NULL)
                   AND NOT EXISTS (
                       SELECT 1 FROM students_in_practices sip
                       JOIN solicitudes_practicantes sol ON sol.id = sip.idPractica
                       WHERE sip.idStudent = sp.id AND sip.isAcepted = 1
                   )"
            ),
        ];
    }

    // ── Historial completo de solicitudes de practicantes de un organismo ──
    static public function mdlGetHistorialSolicitudesByOrganismo(int $orgId): array
    {
        $sql = "SELECT
                    sp.id,
                    sp.licenciatura,
                    sp.num_practicantes,
                    sp.actividades,
                    sp.ofrece_apoyo_economico,
                    sp.monto_apoyo,
                    sp.fecha_limite,
                    sp.modalidad,
                    sp.dia_inicio,
                    sp.dia_fin,
                    sp.hora_inicio,
                    sp.hora_fin,
                    sp.capacidades,
                    sp.direccion_practica,
                    sp.nombre_responsable,
                    sp.telefono,
                    sp.aceptado,
                    sp.activo,
                    sp.created_at,
                    sp.updated_at,
                    (SELECT COUNT(*) FROM students_in_practices sip WHERE sip.idPractica = sp.id) AS total_postulados,
                    (SELECT COUNT(*) FROM students_in_practices sip WHERE sip.idPractica = sp.id AND sip.isAcepted = 1) AS total_aceptados
                FROM solicitudes_practicantes sp
                WHERE sp.organismo_externo_id = :id
                ORDER BY sp.created_at DESC";
        return self::all($sql, [':id' => $orgId]);
    }

    // ── Postulantes de una solicitud específica (historial) ──
    static public function mdlGetPostuladosByHistorialSolicitud(int $idSolicitud): array
    {
        $sql = "SELECT
                    sip.isAcepted  AS sip_status,
                    sip.start_date,
                    s.matricula,
                    s.nombre_completo,
                    s.programa_academico,
                    s.email,
                    s.telefono
                FROM students_in_practices sip
                LEFT JOIN students_practicas s ON s.id = sip.idStudent
                WHERE sip.idPractica = :id
                ORDER BY sip.start_date DESC";
        return self::all($sql, [':id' => $idSolicitud]);
    }

    static public function mdlGetNewsExternals()
    {
        // misma consulta que mdlGetNewOrganismosExternos (se respeta método)
        return self::all(
            "SELECT * FROM organismos_externos WHERE isActive = 1 AND isAcepted = 0"
        );
    }

    static public function mdlAcceptExternal($id)
    {
        return self::aff(
            "UPDATE organismos_externos SET isAcepted = 1 WHERE id = :id",
            [':id' => $id]
        ) > 0;
    }

    static public function mdlDisableExternal($id)
    {
        return self::aff(
            "UPDATE organismos_externos SET isActive = 0 WHERE id = :id",
            [':id' => $id]
        ) > 0;
    }

    static public function mdlAddPasswordExternal($cryptPass, $id)
    {
        return self::aff(
            "UPDATE organismos_externos SET password = :password WHERE id = :id",
            [':password' => $cryptPass, ':id' => $id]
        ) > 0;
    }

    static public function mdlShowUsersPP($table, $item, $value)
    {
        // Validación mínima para evitar SQLi con nombres de tabla/campo
        if (!self::isSafeIdent($table) || !self::isSafeIdent($item)) {
            return null; // misma firma, fallo silencioso
        }
        $sql = "SELECT * FROM {$table} WHERE {$item} = :val AND isActive = 1";
        return self::one($sql, [':val' => $value]);
    }

    /* =========================
     * SOLICITUDES PRACTICANTES
     * ========================= */

    static public function mdlSolicitarPracticas($data)
    {
        $sql = "INSERT INTO solicitudes_practicantes (
                organismo_externo_id, licenciatura, num_practicantes, actividades,
                ofrece_apoyo_economico, monto_apoyo, fecha_limite, modalidad,
                dia_inicio, dia_fin, hora_inicio, hora_fin, capacidades,
                direccion_practica, nombre_responsable, telefono
            ) VALUES (
                :organismo_externo_id, :licenciatura, :numPract, :actividades,
                :apoyoEconomico, :montoApoyo, :fechaLimite, :modalidad,
                :diaInicio, :diaFin, :horaInicio, :horaFin, :capacidades,
                :direccionPractica, :nombreResponsable, :contactoResponsable
            )";
        $ok = self::aff($sql, [
            ':organismo_externo_id' => $data['organismo_externo_id'],
            ':licenciatura' => $data['licenciatura'],
            ':numPract' => $data['numPract'],
            ':actividades' => $data['actividades'],
            ':apoyoEconomico' => $data['apoyoEconomico'],
            ':montoApoyo' => $data['montoApoyo'],
            ':fechaLimite' => $data['fechaLimite'],
            ':modalidad' => $data['modalidad'],
            ':diaInicio' => $data['diaInicio'],
            ':diaFin' => $data['diaFin'],
            ':horaInicio' => $data['horaInicio'],
            ':horaFin' => $data['horaFin'],
            ':capacidades' => $data['capacidades'],
            ':direccionPractica' => $data['direccionPractica'],
            ':nombreResponsable' => $data['nombreResponsable'],
            ':contactoResponsable' => $data['contactoResponsable'],
        ]) > 0;

        return $ok
            ? ['success' => true, 'id' => (int) self::lastId(), 'message' => null]
            : ['success' => false, 'id' => null, 'message' => 'Error al registrar la solicitud de prácticas.'];
    }

    static public function mdlGetSolicitudesPracticas($organismo_externo_id)
    {
        $sql = "SELECT sp.*, oe.empresa, oe.giro, oe.web, oe.ciudad
                FROM solicitudes_practicantes sp
                JOIN organismos_externos oe ON sp.organismo_externo_id = oe.id
                WHERE sp.organismo_externo_id = :id AND sp.activo = 1
                ORDER BY sp.fecha_limite DESC";
        return self::all($sql, [':id' => $organismo_externo_id]);
    }

    static public function mdlDeleteSolicitudPractica($idSolicitud)
    {
        $ok = self::aff(
            "UPDATE solicitudes_practicantes SET activo = 0 WHERE id = :id",
            [':id' => $idSolicitud]
        ) > 0;

        return $ok ? self::ok(null) : self::fail('Error al eliminar la solicitud de prácticas.');
    }

    static public function mdlGetSolicitudPracticaById($idSolicitud)
    {
        return self::one(
            "SELECT sp.* FROM solicitudes_practicantes sp WHERE sp.id = :id AND sp.activo = 1",
            [':id' => $idSolicitud]
        );
    }

    static public function mdlUpdateSolicitudPractica($data)
    {
        $sql = "UPDATE solicitudes_practicantes SET 
                licenciatura = :licenciatura,
                num_practicantes = :numPract,
                actividades = :actividades,
                ofrece_apoyo_economico = :apoyoEconomico,
                monto_apoyo = :montoApoyo,
                fecha_limite = :fechaLimite,
                modalidad = :modalidad,
                dia_inicio = :diaInicio,
                dia_fin = :diaFin,
                hora_inicio = :horaInicio,
                hora_fin = :horaFin,
                capacidades = :capacidades,
                direccion_practica = :direccionPractica,
                nombre_responsable = :nombreResponsable,
                telefono = :contactoResponsable
            WHERE id = :idSolicitud";
        $ok = self::aff($sql, [
            ':licenciatura' => $data['licenciatura'],
            ':numPract' => $data['numPract'],
            ':actividades' => $data['actividades'],
            ':apoyoEconomico' => $data['apoyoEconomico'],
            ':montoApoyo' => $data['montoApoyo'],
            ':fechaLimite' => $data['fechaLimite'],
            ':modalidad' => $data['modalidad'],
            ':diaInicio' => $data['diaInicio'],
            ':diaFin' => $data['diaFin'],
            ':horaInicio' => $data['horaInicio'],
            ':horaFin' => $data['horaFin'],
            ':capacidades' => $data['capacidades'],
            ':direccionPractica' => $data['direccionPractica'],
            ':nombreResponsable' => $data['nombreResponsable'],
            ':contactoResponsable' => $data['contactoResponsable'],
            ':idSolicitud' => $data['idSolicitud'],
        ]) > 0;

        return $ok ? self::ok($data['licenciatura']) : self::fail('Error al actualizar la solicitud de prácticas.');
    }

    /* =========================
     * STUDENTS PRACTICAS
     * ========================= */

    static public function mdlGetStudentsPractices()
    {
        return self::all(
            "SELECT s.*, (SELECT COUNT(*) FROM strikes_practicas WHERE idStudent = s.id) AS strikes_count FROM students_practicas s WHERE s.isActive = 1 ORDER BY s.fecha_registro DESC"
        );
    }

    static public function mdlGetStudentsPracticesByStatus($status)
    {
        return self::all(
            "SELECT * FROM students_practicas WHERE isActive = 1 AND isAcepted = :status ORDER BY fecha_registro DESC",
            [':status' => (int) $status]
        );
    }

    static public function mdlGetStudentPracticesById($id)
    {
        return self::one(
            "SELECT * FROM students_practicas WHERE id = :id AND isActive = 1",
            [':id' => $id]
        );
    }

    static public function mdlGetStudentPracticesByMatricula($matricula)
    {
        return self::one(
            "SELECT * FROM students_practicas WHERE matricula = :m AND isActive = 1",
            [':m' => $matricula]
        );
    }

    static public function mdlUpdateStudentPractices($data)
    {
        $sql = "UPDATE students_practicas SET 
            matricula = :matricula,
            grupo = :grupo,
            nombre_completo = :nombre_completo,
            curp = :curp,
            fecha_nacimiento = :fecha_nacimiento,
            genero = :genero,
            email = :email,
            telefono = :telefono,
            programa_academico = :programa_academico,
            periodo = :periodo
        WHERE id = :idstudent";

        $ok = self::aff($sql, [
            ':matricula' => $data['matricula'],
            ':grupo' => $data['grupo'],
            ':nombre_completo' => $data['nombre_completo'],
            ':curp' => $data['curp'],
            ':fecha_nacimiento' => $data['fecha_nacimiento'],
            ':genero' => $data['genero'],
            ':email' => $data['email'],
            ':telefono' => $data['telefono'],
            ':programa_academico' => $data['programa_academico'],
            ':periodo' => $data['periodo'],
            ':idstudent' => $data['idstudent'],
        ]) > 0;

        return $ok ? self::ok('Actualización exitosa') : self::fail('Error al actualizar el registro');
    }

    static public function mdlDisableStudentPractices($id)
    {
        return self::aff(
            "UPDATE students_practicas SET isActive = 0 WHERE id = :id",
            [':id' => $id]
        ) > 0;
    }

    /**
     * Marca al alumno como "prácticas finalizadas" al descargar su constancia.
     * Idempotente: si ya estaba marcado, conserva la fecha original.
     */
    static public function mdlFinalizarPracticas(int $idStudent): void
    {
        self::aff(
            "UPDATE students_practicas
             SET practicas_finalizadas = 1,
                 fecha_finalizacion    = COALESCE(fecha_finalizacion, NOW())
             WHERE id = :id",
            [':id' => $idStudent]
        );
    }

    static public function mdlUpdateVencimientoCarta(string $folio, string $fechaVencimiento): void
    {
        self::aff(
            "UPDATE cartas_practicas_profesionales 
             SET fecha_generacion = NOW(),
                 fecha_vencimiento = :venc,
                 status_carta = 'vigente'
             WHERE code = :code",
            [':venc' => $fechaVencimiento, ':code' => $folio]
        );
    }

    static public function mdlGetCartasExpiradas()
    {
        $sql = "SELECT cpp.id, cpp.code, cpp.student_id, cpp.fecha_vencimiento, 
                       sp.nombre_completo, sp.email, sp.matricula
                FROM cartas_practicas_profesionales cpp
                JOIN students_practicas sp ON sp.id = cpp.student_id
                WHERE cpp.status_carta = 'vigente' 
                  AND cpp.fecha_vencimiento < NOW()";
        return self::all($sql);
    }

    static public function mdlExpirarCarta(int $idCarta, int $idStudent)
    {
        // 1. Expirar la carta
        self::aff(
            "UPDATE cartas_practicas_profesionales 
             SET status_carta = 'expirada' 
             WHERE id = :id",
            [':id' => $idCarta]
        );

        // 2. Rechazar la postulación pendiente del alumno (si la hay)
        self::aff(
            "UPDATE students_in_practices 
             SET isAcepted = 2 
             WHERE idStudent = :s AND isAcepted = 0",
            [':s' => $idStudent]
        );
    }

    static public function mdlConfirmarPresentacion(int $idStudent, int $idOrganismo)
    {
        $sql = "UPDATE cartas_practicas_profesionales 
                SET status_carta = 'presentada',
                    fecha_presentacion = NOW(),
                    confirmada_por = :idOrg
                WHERE student_id = :idStudent 
                  AND status_carta = 'vigente'";

        $ok = self::aff($sql, [':idOrg' => $idOrganismo, ':idStudent' => $idStudent]) > 0;

        return $ok ? self::ok('Presentación confirmada exitosamente.')
            : self::fail('Error al confirmar la presentación o la carta no está vigente.');
    }

    static public function mdlGetPracticesSolicitudes()
    {
        $sql = "SELECT * FROM organismos_externos oe
                LEFT JOIN solicitudes_practicantes sp ON sp.organismo_externo_id = oe.id
                WHERE oe.isActive = 1 AND sp.activo = 1
                ORDER BY sp.created_at DESC";
        return self::all($sql);
    }

    static public function mdlNewSolicitudesPracticantes()
    {
        $sql = "SELECT *, sp.id AS idSolPracticantes
                FROM solicitudes_practicantes sp
                LEFT JOIN organismos_externos oe ON oe.id = sp.organismo_externo_id
                WHERE sp.aceptado = 0 AND sp.activo = 1
                ORDER BY sp.id DESC";
        return self::all($sql);
    }

    static public function mdlGetPractices($idStudent)
    {
        $sql = "SELECT 
                sp.*,
                oe.empresa,
                oe.giro,
                oe.web,
                oe.ciudad,
                (SELECT COUNT(*) 
                   FROM students_in_practices sip 
                  WHERE sip.idPractica = sp.id AND sip.isAcepted = 1) AS num_students,
                (SELECT COUNT(*) 
                   FROM students_in_practices sip 
                  WHERE sip.idPractica = sp.id AND sip.idStudent = :id1 AND sip.isAcepted = 0) AS pending,
                (SELECT COUNT(*) 
                   FROM students_in_practices sip 
                  WHERE sip.idPractica = sp.id AND sip.idStudent = :id2 AND sip.isAcepted = 1) AS accepted,
                (SELECT COUNT(*) 
                   FROM students_in_practices sip 
                  WHERE sip.idPractica = sp.id AND sip.idStudent = :id3 AND sip.isAcepted = 2) AS notAccepted,
                cpp.status_carta,
                cpp.fecha_vencimiento
            FROM solicitudes_practicantes sp
            JOIN organismos_externos oe ON sp.organismo_externo_id = oe.id
            LEFT JOIN (
                SELECT status_carta, fecha_vencimiento, student_id FROM cartas_practicas_profesionales WHERE student_id = :id4 ORDER BY id DESC LIMIT 1
            ) cpp ON cpp.student_id = :id5
            WHERE sp.activo = 1
              AND sp.organismo_externo_id NOT IN (
                  SELECT idOrganismo FROM alumno_empresa_bloqueo WHERE idStudent = :id6 AND estado = 'activo'
              )
            ORDER BY sp.fecha_limite DESC";
        return self::all($sql, [':id1' => $idStudent, ':id2' => $idStudent, ':id3' => $idStudent, ':id4' => $idStudent, ':id5' => $idStudent, ':id6' => $idStudent]);
    }


    static public function mdlIsStudentRegisteredInPractices($idStudent)
    {
        $sql = "SELECT sp.idPractica, sp2.organismo_externo_id, sp.start_date, sp2.dia_fin, sp2.dia_inicio
                FROM students_in_practices sp
                LEFT JOIN solicitudes_practicantes sp2 ON sp2.id = sp.idPractica
                WHERE sp.idStudent = :idStudent AND sp.isAcepted = 1";
        return self::one($sql, [':idStudent' => $idStudent]);
    }

    public static function mdlGetAcceptedCount($idPractice)
    {
        return (int) self::col(
            "SELECT COUNT(*) FROM students_in_practices WHERE idPractica = :p AND isAcepted = 1",
            [':p' => $idPractice]
        );
    }

    public static function mdlGetPendingStudentsByPractice($idPractice)
    {
        return self::all(
            "SELECT idStudent FROM students_in_practices WHERE idPractica = :p AND isAcepted = 0",
            [':p' => $idPractice]
        );
    }

    public static function mdlApplyForPractice($idPractice, $idStudent)
    {
        // 1. Obtener idOrganismo y verificar bloqueo activo
        $idOrganismo = (int) self::col("SELECT organismo_externo_id FROM solicitudes_practicantes WHERE id = :p", [':p' => $idPractice]);
        if ($idOrganismo && self::mdlVerificarBloqueoActivo($idStudent, $idOrganismo)) {
            return self::fail('No puedes postularte a esta empresa porque tienes un bloqueo activo debido a una baja anterior.');
        }

        // Verificar cuántas vacantes tiene la práctica y cuántos aceptados hay
        $practiceInfo = self::one("SELECT num_practicantes FROM solicitudes_practicantes WHERE id = :p", [':p' => $idPractice]);
        if (!$practiceInfo)
            return self::fail('La práctica no existe.');

        $acceptedCount = (int) self::col(
            "SELECT COUNT(*) FROM students_in_practices WHERE idPractica = :p AND isAcepted = 1",
            [':p' => $idPractice]
        );

        if ($acceptedCount >= $practiceInfo['num_practicantes']) {
            return self::fail('Se ha llenado el cupo de vacantes para esta práctica.');
        }

        // Verificar si el alumno ya está postulado
        $existing = self::one(
            "SELECT isAcepted FROM students_in_practices WHERE idPractica = :p AND idStudent = :s",
            [':p' => $idPractice, ':s' => $idStudent]
        );

        if ($existing) {
            // isAcepted: 0 = Pendiente, 1 = Aceptado, 2 = Rechazado/Expirado
            if ($existing['isAcepted'] == 1) {
                return self::fail('Ya fuiste aceptado en esta práctica.');
            } else if ($existing['isAcepted'] == 0) {
                return self::fail('Ya te has postulado a esta práctica y está pendiente de revisión.');
            } else {
                // isAcepted == 2 (Rechazado o carta expirada) -> Permitir re-postulación
                $ok = self::aff(
                    "UPDATE students_in_practices SET isAcepted = 0, isRejected = 0, decision_fecha = NULL, decision_motivo = NULL, start_date = NULL WHERE idPractica = :p AND idStudent = :s",
                    [':p' => $idPractice, ':s' => $idStudent]
                ) > 0;

                return $ok
                    ? self::ok('Solicitud enviada nuevamente de forma correcta.')
                    : self::fail('Error al enviar la solicitud nuevamente.');
            }
        }

        $ok = self::aff(
            "INSERT INTO students_in_practices (idPractica, idStudent, isAcepted) VALUES (:p, :s, 0)",
            [':p' => $idPractice, ':s' => $idStudent]
        ) > 0;

        return $ok
            ? self::ok('Solicitud enviada correctamente.')
            : self::fail('Error al enviar la solicitud.');
    }

    static public function mdlSearchPractices($id)
    {
        if ($id === null) {
            $sql = "SELECT *, sp.id AS idSolPracticantes
                    FROM solicitudes_practicantes sp
                    LEFT JOIN organismos_externos oe ON oe.id = sp.organismo_externo_id
                    WHERE sp.activo = 1
                    ORDER BY sp.id DESC";
            return self::all($sql);
        }
        return self::one(
            "SELECT * FROM solicitudes_practicantes WHERE id = :id AND activo = 1",
            [':id' => $id]
        );
    }

    static public function mdlAcceptSolicitudPracticante($id)
    {
        $ok = self::aff(
            "UPDATE solicitudes_practicantes SET aceptado = 1 WHERE id = :id",
            [':id' => $id]
        ) > 0;

        return $ok ? 'success' : 'error';
    }

    static public function mdlRejectSolicitudPracticante($id)
    {
        $ok = self::aff(
            "UPDATE solicitudes_practicantes SET aceptado = 2 WHERE id = :id",
            [':id' => $id]
        ) > 0;

        return $ok ? 'success' : 'error';
    }

    static public function mdlGetSolicitudPracticanteById($id)
    {
        return self::one(
            "SELECT * FROM solicitudes_practicantes WHERE id = :id AND activo = 1",
            [':id' => $id]
        );
    }

    static public function generateFolioPracticas($idStudent)
    {
        return self::generateFolioGeneric('cartas_practicas_profesionales', 'DPP', (int) $idStudent);
    }

    static public function generateFolioConstancias($idStudent)
    {
        return self::generateFolioGeneric('constancias_acreditacion', 'CAPP', (int) $idStudent);
    }

    /* =========================
     * PROSPECTOS / ASISTENCIAS
     * ========================= */

    static public function mdlGetProspectsBySolicitud($idSolicitud)
    {
        $sql = "SELECT
                    s.id AS idStudent, s.matricula, s.grupo, s.nombre_completo, s.genero, s.telefono, s.email, s.periodo,
                    sp.isAcepted, s.practicas_finalizadas, s.fecha_finalizacion,
                    cpp.status_carta, cpp.fecha_vencimiento
                FROM students_in_practices sp
                LEFT JOIN students_practicas s ON s.id = sp.idStudent
                LEFT JOIN cartas_practicas_profesionales cpp ON cpp.id = (
                    SELECT MAX(id) FROM cartas_practicas_profesionales WHERE student_id = s.id
                )
                WHERE sp.idPractica = :id";
        return self::all($sql, [':id' => $idSolicitud]);
    }

    static public function mdlAceptarProspecto($idSolicitud, $idStudent, $fechaInicio, $motivo)
    {
        $ok = self::aff(
            "UPDATE students_in_practices
             SET isAcepted = 1, 
                 start_date = :f,
                 decision_motivo = :motivo,
                 decision_fecha = NOW()
             WHERE idPractica = :p AND idStudent = :s",
            [':f' => $fechaInicio, ':p' => $idSolicitud, ':s' => $idStudent, ':motivo' => $motivo]
        ) > 0;

        if ($ok) {
            $sol = self::one("SELECT organismo_externo_id FROM solicitudes_practicantes WHERE id = :id", [':id' => $idSolicitud]);
            $idOrganismo = $sol ? $sol['organismo_externo_id'] : null;

            // Tratamos de obtener quién confirmó (admin, teacher o el mismo organismo)
            $confirmadaPor = $_SESSION['idAdmin'] ?? $_SESSION['idTeacher'] ?? $idOrganismo;

            self::aff(
                "UPDATE cartas_practicas_profesionales 
                 SET status_carta = 'presentada', 
                     fecha_presentacion = NOW(), 
                     confirmada_por = :org 
                 WHERE student_id = :sid AND status_carta = 'vigente'",
                [':org' => $confirmadaPor, ':sid' => $idStudent]
            );

            // Remover el estatus de baja si el alumno venía de ser dado de baja
            self::aff(
                "UPDATE students_practicas SET dado_de_baja_por_strike = 0, fecha_baja_strike = NULL WHERE id = :sid",
                [':sid' => $idStudent]
            );
        }

        return $ok ? self::ok('Prospecto aceptado correctamente.')
            : self::fail('Error al aceptar el prospecto.');
    }

    static public function mdlRechazarProspecto($idSolicitud, $idStudent, $motivo)
    {
        $ok = self::aff(
            "UPDATE students_in_practices
             SET isAcepted = 2,
                 isRejected = 1,
                 decision_motivo = :motivo,
                 decision_fecha = NOW()
             WHERE idPractica = :p AND idStudent = :s",
            [':p' => $idSolicitud, ':s' => $idStudent, ':motivo' => $motivo]
        ) > 0;

        return $ok ? self::ok('Prospecto rechazado correctamente.')
            : self::fail('Error al rechazar el prospecto.');
    }

    static public function mdlEvaluarEntrevista($data)
    {
        $evaluador = $_SESSION['idAdmin'] ?? $_SESSION['idTeacher'] ?? $_SESSION['idOrganismo'] ?? null;

        $ok = self::aff(
            "INSERT INTO entrevistas_practicas 
                (idStudent, idPractica, llego_a_tiempo, llego_formal, calificacion_respuestas, comentarios, evaluado_por) 
             VALUES 
                (:idStudent, :idPractica, :tiempo, :formal, :calif, :comentarios, :evaluador)
             ON DUPLICATE KEY UPDATE 
                llego_a_tiempo = :tiempo2,
                llego_formal = :formal2,
                calificacion_respuestas = :calif2,
                comentarios = :comentarios2,
                evaluado_por = :evaluador2,
                fecha_entrevista = NOW()",
            [
                ':idStudent' => $data['idStudent'],
                ':idPractica' => $data['idPractica'],
                ':tiempo' => $data['llego_a_tiempo'],
                ':formal' => $data['llego_formal'],
                ':calif' => $data['calificacion_respuestas'],
                ':comentarios' => $data['comentarios'],
                ':evaluador' => $evaluador,
                ':tiempo2' => $data['llego_a_tiempo'],
                ':formal2' => $data['llego_formal'],
                ':calif2' => $data['calificacion_respuestas'],
                ':comentarios2' => $data['comentarios'],
                ':evaluador2' => $evaluador
            ]
        ) > 0;

        if ($ok) {
            self::aff(
                "UPDATE cartas_practicas_profesionales 
                 SET status_carta = 'presentada', 
                     fecha_presentacion = NOW(),
                     confirmada_por = :org
                 WHERE student_id = :sid AND status_carta = 'vigente'",
                [':sid' => $data['idStudent'], ':org' => $evaluador]
            );
        }

        return $ok ? self::ok('Entrevista evaluada correctamente y estatus actualizado.') : self::fail('Error al registrar la entrevista.');
    }

    static public function mdlGetHistorialAlumnosOrganismo($idOrganismo)
    {
        $sql = "SELECT sp.idPractica, sp.idStudent, sp.isAcepted, sp.start_date, sp.dateCreated,
                       s.nombre_completo, s.matricula, s.email, s.telefono,
                       sol.licenciatura, sol.actividades,
                       cpp.status_carta, cpp.fecha_presentacion,
                       ep.calificacion_respuestas, ep.llego_a_tiempo, ep.llego_formal, ep.comentarios, ep.fecha_entrevista
                FROM students_in_practices sp
                JOIN students_practicas s ON s.id = sp.idStudent
                JOIN solicitudes_practicantes sol ON sol.id = sp.idPractica
                LEFT JOIN cartas_practicas_profesionales cpp ON cpp.student_id = s.id 
                     AND cpp.id = (SELECT MAX(id) FROM cartas_practicas_profesionales WHERE student_id = s.id)
                LEFT JOIN entrevistas_practicas ep ON ep.idStudent = sp.idStudent AND ep.idPractica = sp.idPractica
                WHERE sol.organismo_externo_id = :idOrg
                ORDER BY sp.dateCreated DESC";
        return self::all($sql, [':idOrg' => $idOrganismo]);
    }

    static public function mdlCheckAssistance($idPractica, $idStudent)
    {
        $sql = "SELECT * FROM asistencias_practicas
                WHERE idPractica = :p AND idStudent = :s AND status <> 'pendiente'";
        return self::all($sql, [':p' => $idPractica, ':s' => $idStudent]);
    }

    static public function mdlGetPartialReport($idPractica, $idStudent)
    {
        return self::one(
            "SELECT * FROM reporte_parcial_practicas WHERE idPractica = :p AND idStudent = :s",
            [':p' => $idPractica, ':s' => $idStudent]
        );
    }

    static public function mdlGetFinalReport($idPractica, $idStudent)
    {
        return self::one(
            "SELECT * FROM reporte_final_practicas WHERE idPractica = :p AND idStudent = :s",
            [':p' => $idPractica, ':s' => $idStudent]
        );
    }

    /**
     * Mapa de código de día (como se guarda en solicitudes_practicantes) a número ISO de día de semana.
     * PHP: date('N') => 1=Lun, 2=Mar, 3=Mié, 4=Jue, 5=Vie, 6=Sáb, 7=Dom
     */
    private const DIA_CODE_MAP = [
        'L' => 1, // Lunes
        'M' => 2, // Martes
        'X' => 3, // Miércoles
        'J' => 4, // Jueves
        'V' => 5, // Viernes
        'S' => 6, // Sábado
        'D' => 7, // Domingo
    ];

    /**
     * Convierte "HH:MM" o "HH:MM:SS" a minutos enteros.
     */
    private static function toMinutes(string $hora): int
    {
        [$h, $m] = array_map('intval', explode(':', $hora));
        return $h * 60 + $m;
    }

    static public function mdlRegisterAttendance($data)
    {
        // ── Validación 1: duplicado ────────────────────────────────────────────
        $exists = (int) self::col(
            "SELECT COUNT(*) FROM asistencias_practicas
             WHERE idPractica = :p AND idStudent = :s AND fecha = :f AND status <> 'rechazado'",
            [':p' => $data['idPractica'], ':s' => $data['idStudent'], ':f' => $data['fechaAsistencia']]
        );

        if ($exists > 0) {
            return [
                'success' => false,
                'icon' => 'warning',
                'title' => 'Asistencia ya registrada',
                'message' => 'Ya existe una asistencia registrada para este estudiante en esta práctica y fecha. Si no aparece, puede que aún no ha sido aprobada por el organismo externo.'
            ];
        }

        // ── Validación 2: día autorizado por la solicitud ─────────────────────
        $solicitud = self::one(
            "SELECT dia_inicio, dia_fin FROM solicitudes_practicantes WHERE id = :p AND activo = 1",
            [':p' => $data['idPractica']]
        );

        if ($solicitud) {
            $diaInicioCode = strtoupper(trim($solicitud['dia_inicio'] ?? ''));
            $diaFinCode = strtoupper(trim($solicitud['dia_fin'] ?? ''));
            $mapDias = self::DIA_CODE_MAP;

            if (isset($mapDias[$diaInicioCode], $mapDias[$diaFinCode])) {
                $numInicio = $mapDias[$diaInicioCode];
                $numFin = $mapDias[$diaFinCode];
                $fechaDt = new \DateTime($data['fechaAsistencia']);
                $numDia = (int) $fechaDt->format('N'); // 1=Lun … 7=Dom

                // Rango puede ser por ejemplo L(1) a V(5) o M(2) a J(4).
                // Como el rango siempre avanza en la semana, basta verificar numInicio <= numDia <= numFin.
                if ($numDia < $numInicio || $numDia > $numFin) {
                    $nombresMap = [
                        'L' => 'Lunes',
                        'M' => 'Martes',
                        'X' => 'Miércoles',
                        'J' => 'Jueves',
                        'V' => 'Viernes',
                        'S' => 'Sábado',
                        'D' => 'Domingo',
                    ];
                    $diaInicioNombre = $nombresMap[$diaInicioCode] ?? $diaInicioCode;
                    $diaFinNombre = $nombresMap[$diaFinCode] ?? $diaFinCode;
                    return [
                        'success' => false,
                        'icon' => 'warning',
                        'title' => 'Día no autorizado',
                        'message' => "Solo puedes registrar asistencia de $diaInicioNombre a $diaFinNombre, según el horario establecido por el organismo externo.",
                    ];
                }
            }
        }

        // ── Validación 3: máximo 4 horas acumuladas por día ───────────────────
        $minutosNuevo = self::toMinutes($data['horaSalida']) - self::toMinutes($data['horaEntrada']);
        if ($minutosNuevo <= 0) {
            return [
                'success' => false,
                'icon' => 'warning',
                'title' => 'Horario inválido',
                'message' => 'La hora de salida debe ser posterior a la hora de entrada.',
            ];
        }

        $minutosAcumulados = (int) self::col(
            "SELECT COALESCE(SUM(
                TIME_TO_SEC(hora_salida) - TIME_TO_SEC(hora_entrada)
             ) / 60, 0)
             FROM asistencias_practicas
             WHERE idPractica = :p AND idStudent = :s AND fecha = :f
               AND status <> 'rechazado'",
            [':p' => $data['idPractica'], ':s' => $data['idStudent'], ':f' => $data['fechaAsistencia']]
        );

        /* --- FASE 3: Se omite la validación estricta de 4 horas en el registro 
               para permitir que el organismo evalúe y asigne el strike si excede ---
        $maxMinutos = 4 * 60; // 240 minutos = 4 horas
        if ($minutosAcumulados + $minutosNuevo > $maxMinutos) {
            $yaHoras = intdiv($minutosAcumulados, 60);
            $yaMinutos = $minutosAcumulados % 60;
            $nuevo_h = intdiv($minutosNuevo, 60);
            $nuevo_m = $minutosNuevo % 60;
            $restoMin = $maxMinutos - $minutosAcumulados;
            return [
                'success' => false,
                'icon' => 'warning',
                'title' => 'Límite diario de 4 horas excedido',
                'message' => "Intentas agregar {$nuevo_h}h {$nuevo_m}m, lo que superaría el máximo de 4 horas diarias. "
                    . "Solo puedes registrar hasta " . intdiv($restoMin, 60) . "h " . ($restoMin % 60) . "m más.",
            ];
        }
        */

        // ── Insertar ──────────────────────────────────────────────────────────
        $ok = self::aff(
            "INSERT INTO asistencias_practicas
             (idOrganismo, idPractica, idStudent, fecha, hora_entrada, hora_salida, actividad, status)
             VALUES (:o, :p, :s, :f, :he, :hs, :act, 'pendiente')",
            [
                ':o' => $data['idOrganismo'],
                ':p' => $data['idPractica'],
                ':s' => $data['idStudent'],
                ':f' => $data['fechaAsistencia'],
                ':he' => $data['horaEntrada'],
                ':hs' => $data['horaSalida'],
                ':act' => $data['actividad'],
            ]
        ) > 0;

        return $ok ? self::ok('Asistencia registrada correctamente.')
            : ['success' => false, 'icon' => 'error', 'title' => 'Error', 'message' => 'Error al registrar la asistencia.'];
    }

    static public function mdlGetAssistancesPractices($idOrganismo)
    {
        $sql = "SELECT ap.*, sp.nombre_completo, sp2.*
                FROM asistencias_practicas ap
                LEFT JOIN students_practicas sp ON sp.id = ap.idStudent
                LEFT JOIN solicitudes_practicantes sp2 ON sp2.id = ap.idPractica
                WHERE ap.idOrganismo = :o AND ap.status = 'pendiente'";
        return self::all($sql, [':o' => $idOrganismo]);
    }

    static public function mdlGetAllPractices($idOrganismo)
    {
        $sql = "SELECT sp.*, sp2.*
                FROM students_practicas sp
                INNER JOIN students_in_practices sip ON sip.idStudent = sp.id
                INNER JOIN solicitudes_practicantes sp2 ON sp2.id = sip.idPractica
                WHERE sp2.organismo_externo_id = :o";
        return self::all($sql, [':o' => $idOrganismo]);
    }

    static public function mdlGetDataPracticesStudent($idOrganismo, $matricula)
    {
        $sql = "SELECT sp.*, sp2.*
                FROM students_practicas sp
                INNER JOIN students_in_practices sip ON sip.idStudent = sp.id
                INNER JOIN solicitudes_practicantes sp2 ON sp2.id = sip.idPractica
                WHERE sp2.organismo_externo_id = :o AND sp.matricula = :m";
        return self::one($sql, [':o' => $idOrganismo, ':m' => $matricula]);
    }

    /**
     * Resumen de contadores para el dashboard del organismo externo.
     */
    public static function mdlGetDashboardSummary(int $idOrganismo): array
    {
        $solicitudes_activas = (int) self::col(
            "SELECT COUNT(*) FROM solicitudes_practicantes
             WHERE organismo_externo_id = :o AND activo = 1",
            [':o' => $idOrganismo]
        );

        $candidatos_pendientes = (int) self::col(
            "SELECT COUNT(*)
             FROM students_in_practices sip
             JOIN solicitudes_practicantes sp ON sp.id = sip.idPractica
             WHERE sp.organismo_externo_id = :o AND sip.isAcepted = 0",
            [':o' => $idOrganismo]
        );

        $asistencias_pendientes = (int) self::col(
            "SELECT COUNT(*) FROM asistencias_practicas
             WHERE idOrganismo = :o AND status = 'pendiente'",
            [':o' => $idOrganismo]
        );

        $reportes_pendientes = (int) self::col(
            "SELECT COUNT(*) FROM (
                SELECT rp.idReporteParcial AS id
                FROM reporte_parcial_practicas rp
                JOIN solicitudes_practicantes sp ON sp.id = rp.idPractica
                WHERE sp.organismo_externo_id = :o1
                  AND rp.completed = 0 AND rp.aproveOrganismo = 0
                UNION ALL
                SELECT rf.idReporteFinal AS id
                FROM reporte_final_practicas rf
                JOIN solicitudes_practicantes sp ON sp.id = rf.idPractica
                WHERE sp.organismo_externo_id = :o2
                  AND rf.completed = 0 AND rf.aproveOrganismo = 0
            ) t",
            [':o1' => $idOrganismo, ':o2' => $idOrganismo]
        );

        $vacantes_disponibles = max(0, (int) self::col(
            "SELECT COALESCE(SUM(
                sp.num_practicantes - (
                    SELECT COUNT(*) FROM students_in_practices sip
                    WHERE sip.idPractica = sp.id AND sip.isAcepted = 1
                )
             ), 0)
             FROM solicitudes_practicantes sp
             WHERE sp.organismo_externo_id = :o AND sp.activo = 1 AND sp.aceptado = 1",
            [':o' => $idOrganismo]
        ));

        $orgInfo = self::one("SELECT empresa, nombre_contacto, strikes_count, solicitudes_bloqueadas, motivo_bloqueo FROM organismos_externos WHERE id = :id", [':id' => $idOrganismo]);
        $degrees = FormsModel::mdlSearchDegrees(null);

        return [
            'solicitudes_activas' => $solicitudes_activas,
            'candidatos_pendientes' => $candidatos_pendientes,
            'asistencias_pendientes' => $asistencias_pendientes,
            'reportes_pendientes' => $reportes_pendientes,
            'vacantes_disponibles' => $vacantes_disponibles,
            'orgInfo' => $orgInfo,
            'degrees' => $degrees,
        ];
    }

    /**
     * Retorna los candidatos pendientes de revisión para el organismo externo.
     */
    public static function mdlGetRecentProspectos(int $idOrganismo): array
    {
        $sql = "SELECT
                    sip.idSiP           AS idPostulacion,
                    sip.idPractica,
                    sip.idStudent,
                    sip.isAcepted,
                    sip.start_date,
                    sip.dateCreated     AS fecha_postulacion,
                    s.nombre_completo,
                    s.matricula,
                    s.programa_academico,
                    s.email,
                    s.telefono,
                    sp.licenciatura,
                    sp.actividades,
                    sp.modalidad,
                    (SELECT status_carta FROM cartas_practicas_profesionales WHERE student_id = s.id ORDER BY id DESC LIMIT 1) AS status_carta
                FROM students_in_practices sip
                JOIN students_practicas s ON s.id = sip.idStudent
                JOIN solicitudes_practicantes sp ON sp.id = sip.idPractica
                WHERE sp.organismo_externo_id = :o AND sip.isAcepted = 0
                ORDER BY sip.idSiP DESC
                LIMIT 50";
        return self::all($sql, [':o' => $idOrganismo]);
    }

    public static function mdlSolicitarCapacitacion($idOrganismo, $matricula, $solicitud)
    {
        $ok = self::aff(
            "INSERT INTO solicitudes_capacitacion (idOrganismo, matricula, solicitud, dateUpdate)
             VALUES (:o, :m, :s, NOW())",
            [':o' => $idOrganismo, ':m' => $matricula, ':s' => $solicitud]
        ) > 0;

        return $ok ? self::ok('Solicitud de capacitación enviada correctamente.')
            : self::fail('Error al enviar la solicitud de capacitación.');
    }

    public static function mdlGetSolicitudesCapacitacion($idOrganismo)
    {
        if ($idOrganismo === null) {
            $sql = "SELECT sc.*, s.nombre_completo FROM solicitudes_capacitacion sc LEFT JOIN students_practicas s ON s.matricula = sc.matricula WHERE sc.status = 0 ORDER BY sc.idSolicitudCap DESC";
            return self::all($sql);
        }
        $sql = "SELECT sc.*, s.nombre_completo FROM solicitudes_capacitacion sc LEFT JOIN students_practicas s ON s.matricula = sc.matricula WHERE sc.idOrganismo = :o ORDER BY sc.idSolicitudCap DESC";
        return self::all($sql, [':o' => $idOrganismo]);
    }

    public static function mdlGetSolicitudCapacitacionById($idSolicitudCap)
    {
        return self::one(
            "SELECT sc.*, oe.empresa, oe.email, oe.nombre_contacto, sp.nombre_completo FROM solicitudes_capacitacion sc 
                LEFT JOIN organismos_externos oe ON oe.id = sc.idOrganismo
                LEFT JOIN students_practicas sp ON sp.matricula = sc.matricula
                WHERE sc.idSolicitudCap = :id",
            [':id' => $idSolicitudCap]
        );
    }

    public static function mdlAcceptSolicitudCapacitacion($idSolicitudCap, $comentarios)
    {
        $ok = self::aff(
            "UPDATE solicitudes_capacitacion SET status = 1, dateUpdate = NOW(), comentarios = :c WHERE idSolicitudCap = :id",
            [':id' => $idSolicitudCap, ':c' => $comentarios]
        ) > 0;

        return $ok ? self::ok('Solicitud de capacitación aceptada correctamente.')
            : self::fail('Error al aceptar la solicitud de capacitación.');
    }

    public static function mdlRejectSolicitudCapacitacion($idSolicitudCap, $comentarios)
    {
        $ok = self::aff(
            "UPDATE solicitudes_capacitacion SET status = 2, dateUpdate = NOW(), comentarios = :c WHERE idSolicitudCap = :id",
            [':id' => $idSolicitudCap, ':c' => $comentarios]
        ) > 0;

        return $ok ? self::ok('Solicitud de capacitación rechazada correctamente.')
            : self::fail('Error al rechazar la solicitud de capacitación.');
    }

    public static function mdlGetAsistenciaById($idAsistencia)
    {
        return self::one(
            "SELECT ap.*, sp.nombre_completo, sp2.*
             FROM asistencias_practicas ap
             LEFT JOIN students_practicas sp ON sp.id = ap.idStudent
             LEFT JOIN solicitudes_practicantes sp2 ON sp2.id = ap.idPractica
             WHERE ap.idAsistencia = :id",
            [':id' => $idAsistencia]
        );
    }

    public static function mdlAprobarAsistencia($idAsistencia)
    {
        $ok = self::aff(
            "UPDATE asistencias_practicas SET status = 'aprobado' WHERE idAsistencia = :id",
            [':id' => $idAsistencia]
        ) > 0;

        return $ok ? self::ok('Asistencia aprobada correctamente.')
            : self::fail('Error al aprobar la asistencia.');
    }

    public static function mdlRechazarAsistencia($idAsistencia)
    {
        $ok = self::aff(
            "UPDATE asistencias_practicas SET status = 'rechazado' WHERE idAsistencia = :id",
            [':id' => $idAsistencia]
        ) > 0;

        return $ok ? self::ok('Asistencia rechazada correctamente.')
            : self::fail('Error al rechazar la asistencia.');
    }

    public static function mdlActualizarHorarios($idAsistencia, $hora_entrada, $hora_salida)
    {
        $ok = self::aff(
            "UPDATE asistencias_practicas
             SET hora_entrada = :he, hora_salida = :hs, status = 'aprobado'
             WHERE idAsistencia = :id",
            [':he' => $hora_entrada, ':hs' => $hora_salida, ':id' => $idAsistencia]
        ) > 0;

        return $ok ? self::ok('Horarios actualizados correctamente.')
            : self::fail('Error al actualizar los horarios.');
    }

    /* =========================
     * REPORTES (ORGANISMO / ADMIN)
     * ========================= */

    public static function mdlGetParcialReportsPractices($idOrganismo)
    {
        $sql = "SELECT rp.*, sp2.nombre_completo
                FROM reporte_parcial_practicas rp
                LEFT JOIN solicitudes_practicantes sp ON sp.id = rp.idPractica
                LEFT JOIN students_practicas sp2 ON sp2.id = rp.idStudent
                WHERE sp.organismo_externo_id = :o
                  AND rp.completed = 0
                  AND rp.aproveOrganismo = 0
                ORDER BY rp.idReporteParcial DESC";
        return self::all($sql, [':o' => $idOrganismo]);
    }

    public static function mdlGetFinalReports($idOrganismo)
    {
        $sql = "SELECT rp.*, sp2.nombre_completo
                FROM reporte_final_practicas rp
                LEFT JOIN solicitudes_practicantes sp ON sp.id = rp.idPractica
                LEFT JOIN students_practicas sp2 ON sp2.id = rp.idStudent
                WHERE sp.organismo_externo_id = :o
                  AND rp.completed = 0
                  AND rp.aproveOrganismo = 0
                ORDER BY rp.idReporteFinal DESC";
        return self::all($sql, [':o' => $idOrganismo]);
    }

    public static function mdlGetParcialReportsAdmin()
    {
        $sql = "SELECT rp.*, sp2.nombre_completo, sp2.matricula
                FROM reporte_parcial_practicas rp
                LEFT JOIN solicitudes_practicantes sp ON sp.id = rp.idPractica
                LEFT JOIN students_practicas sp2 ON sp2.id = rp.idStudent
                WHERE rp.completed = 0 AND rp.aproveAdmin = 0 AND rp.aproveOrganismo = 1
                ORDER BY rp.idReporteParcial DESC";
        return self::all($sql);
    }

    public static function mdlGetFinalReportsAdmin()
    {
        $sql = "SELECT rp.*, sp2.nombre_completo, sp2.matricula
                FROM reporte_final_practicas rp
                LEFT JOIN solicitudes_practicantes sp ON sp.id = rp.idPractica
                LEFT JOIN students_practicas sp2 ON sp2.id = rp.idStudent
                WHERE rp.completed = 0 AND rp.aproveAdmin = 0 AND rp.aproveOrganismo = 1
                ORDER BY rp.idReporteFinal DESC";
        return self::all($sql);
    }

    public static function mdlGeneratePartialReport($data)
    {
        $exists = (int) self::col(
            "SELECT COUNT(*) FROM reporte_parcial_practicas WHERE idStudent = :s AND idPractica = :p",
            [':s' => $data['idStudent'], ':p' => $data['idPractica']]
        );

        if ($exists > 0) {
            $sql = "UPDATE reporte_parcial_practicas 
                    SET objetivo = :objetivo,
                        actividades_repotadas = :actividades,
                        aproveOrganismo = 0,
                        aproveAdmin = 0,
                        completed = 0,
                        comentarios = NULL
                    WHERE idStudent = :s AND idPractica = :p";
            $ok = self::aff($sql, [
                ':objetivo' => $data['objetivo'],
                ':actividades' => $data['actividades_repotadas'],
                ':s' => $data['idStudent'],
                ':p' => $data['idPractica'],
            ]) > 0;
        } else {
            $sql = "INSERT INTO reporte_parcial_practicas
                    (objetivo, actividades_repotadas, idStudent, idPractica)
                    VALUES (:objetivo, :actividades, :s, :p)";
            $ok = self::aff($sql, [
                ':objetivo' => $data['objetivo'],
                ':actividades' => $data['actividades_repotadas'],
                ':s' => $data['idStudent'],
                ':p' => $data['idPractica'],
            ]) > 0;
        }

        return $ok ? self::ok('Reporte parcial generado/actualizado correctamente.')
            : self::fail('Error al generar/actualizar el reporte parcial.');
    }

    static public function mdlGenerateFinalReport($data)
    {
        $exists = (int) self::col(
            "SELECT COUNT(*) FROM reporte_final_practicas WHERE idStudent = :s AND idPractica = :p",
            [':s' => $data['idStudent'], ':p' => $data['idPractica']]
        );

        if ($exists > 0) {
            $sql = "UPDATE reporte_final_practicas
                    SET objetivo_general = :og,
                        actividades_realizadas = :ar,
                        resultados_obtenidos = :ro,
                        capacitacion_recibida = :cr,
                        experiencia_profesional = :exp_p,
                        experiencia_personal = :exp_per,
                        aproveOrganismo = 0,
                        aproveAdmin = 0,
                        comentarios = NULL
                    WHERE idStudent = :s AND idPractica = :p";
            $params = [
                ':og' => $data['objetivo_general'],
                ':ar' => $data['actividades_realizadas'],
                ':ro' => $data['resultados_obtenidos'],
                ':cr' => $data['capacitacion_recibida'],
                ':exp_p' => $data['experiencia_profesional'],
                ':exp_per' => $data['experiencia_personal'],
                ':s' => $data['idStudent'],
                ':p' => $data['idPractica'],
            ];
            $ok = self::aff($sql, $params) > 0;
            $msgOk = 'Reporte final actualizado correctamente.';
            $msgEr = 'Error al actualizar el reporte final.';
        } else {
            $sql = "INSERT INTO reporte_final_practicas
                    (objetivo_general, actividades_realizadas, resultados_obtenidos, capacitacion_recibida,
                     experiencia_profesional, experiencia_personal, idStudent, idPractica)
                    VALUES (:og, :ar, :ro, :cr, :exp_p, :exp_per, :s, :p)";
            $params = [
                ':og' => $data['objetivo_general'],
                ':ar' => $data['actividades_realizadas'],
                ':ro' => $data['resultados_obtenidos'],
                ':cr' => $data['capacitacion_recibida'],
                ':exp_p' => $data['experiencia_profesional'],
                ':exp_per' => $data['experiencia_personal'],
                ':s' => $data['idStudent'],
                ':p' => $data['idPractica'],
            ];
            $ok = self::aff($sql, $params) > 0;
            $msgOk = 'Reporte final generado correctamente.';
            $msgEr = 'Error al generar el reporte final.';
        }

        return $ok ? self::ok($msgOk) : self::fail($msgEr);
    }

    static public function mdlAcceptReport($idReporteParcial)
    {
        $ok = self::aff(
            "UPDATE reporte_parcial_practicas SET aproveOrganismo = 1 WHERE idReporteParcial = :id",
            [':id' => $idReporteParcial]
        ) > 0;

        return $ok ? self::ok('Reporte aceptado correctamente.')
            : self::fail('Error al aceptar el reporte.');
    }

    static public function mdlAcceptReportFinal($idReporteFinal)
    {
        $ok = self::aff(
            "UPDATE reporte_final_practicas SET aproveOrganismo = 1 WHERE idReporteFinal = :id",
            [':id' => $idReporteFinal]
        ) > 0;

        return $ok ? self::ok('Reporte final aceptado correctamente.')
            : self::fail('Error al aceptar el reporte final.');
    }

    static public function mdlRejectReport($idReporteParcial, $comentarios)
    {
        $ok = self::aff(
            "UPDATE reporte_parcial_practicas
             SET aproveOrganismo = 2, comentarios = :c
             WHERE idReporteParcial = :id",
            [':c' => $comentarios, ':id' => $idReporteParcial]
        ) > 0;

        return $ok ? self::ok('Reporte rechazado correctamente.')
            : self::fail('Error al rechazar el reporte.');
    }

    static public function mdlRejectReportFinal($idReporteFinal, $comentarios)
    {
        $ok = self::aff(
            "UPDATE reporte_final_practicas
             SET aproveOrganismo = 2, comentarios = :c
             WHERE idReporteFinal = :id",
            [':c' => $comentarios, ':id' => $idReporteFinal]
        ) > 0;

        return $ok ? self::ok('Reporte final rechazado correctamente.')
            : self::fail('Error al rechazar el reporte final.');
    }

    static public function mdlGetStudentReportById($idReporteParcial)
    {
        $sql = "SELECT sp.nombre_completo, sp.email
                FROM reporte_parcial_practicas rp
                LEFT JOIN students_practicas sp ON sp.id = rp.idStudent
                WHERE rp.idReporteParcial = :id";
        return self::one($sql, [':id' => $idReporteParcial]);
    }

    static public function mdlGetStudentReportFinalById($idReporteFinal)
    {
        $sql = "SELECT sp.nombre_completo, sp.email
                FROM reporte_final_practicas rp
                LEFT JOIN students_practicas sp ON sp.id = rp.idStudent
                WHERE rp.idReporteFinal = :id";
        return self::one($sql, [':id' => $idReporteFinal]);
    }

    static public function mdlAcceptReportPracticebyAdmin($idReporteParcial)
    {
        $ok = self::aff(
            "UPDATE reporte_parcial_practicas
             SET aproveAdmin = 1, completed = 1
             WHERE idReporteParcial = :id",
            [':id' => $idReporteParcial]
        ) > 0;

        return $ok ? self::ok('Reporte aceptado correctamente.')
            : self::fail('Error al aceptar el reporte.');
    }

    static public function mdlRejectReportPracticebyAdmin($idReporteParcial, $comentarios)
    {
        $ok = self::aff(
            "UPDATE reporte_parcial_practicas
             SET aproveAdmin = 2, comentarios = :c
             WHERE idReporteParcial = :id",
            [':c' => $comentarios, ':id' => $idReporteParcial]
        ) > 0;

        return $ok ? self::ok('Reporte rechazado correctamente.')
            : self::fail('Error al rechazar el reporte.');
    }

    static public function mdlAcceptReportPracticeFinalbyAdmin($idReporteFinal)
    {
        $ok = self::aff(
            "UPDATE reporte_final_practicas
             SET aproveAdmin = 1, completed = 1
             WHERE idReporteFinal = :id",
            [':id' => $idReporteFinal]
        ) > 0;

        return $ok ? self::ok('Reporte final aceptado correctamente.')
            : self::fail('Error al aceptar el reporte final.');
    }

    static public function mdlRejectReportPracticeFinalbyAdmin($idReporteFinal, $comentario)
    {
        $ok = self::aff(
            "UPDATE reporte_final_practicas
             SET aproveAdmin = 2, comentarios = :c
             WHERE idReporteFinal = :id",
            [':c' => $comentario, ':id' => $idReporteFinal]
        ) > 0;

        return $ok ? self::ok('Reporte final rechazado correctamente.')
            : self::fail('Error al rechazar el reporte final.');
    }

    static public function mdlEvaluarParticipanteParcial($idStudent, $evaluacion)
    {
        try {
            $pdo = self::db();
            $pdo->beginTransaction();

            // 1) Insertar o actualizar encabezado
            $sqlEncabezado = "INSERT INTO evaluaciones_practicas 
                (idStudent, idPractica, tipo, fortalezas, debilidades)
                VALUES (:idStudent, :idPractica, 'parcial', :fortalezas, :debilidades)
                ON DUPLICATE KEY UPDATE 
                    fortalezas = VALUES(fortalezas),
                    debilidades = VALUES(debilidades),
                    updated_at = CURRENT_TIMESTAMP()";

            $stmt = $pdo->prepare($sqlEncabezado);
            $stmt->bindValue(":idStudent", $idStudent, PDO::PARAM_INT);
            $stmt->bindValue(":idPractica", $evaluacion['idPractica'], PDO::PARAM_INT);
            $stmt->bindValue(":fortalezas", $evaluacion['fortalezas'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(":debilidades", $evaluacion['debilidades'] ?? null, PDO::PARAM_STR);
            $stmt->execute();

            // Obtener idEvaluacion (por la UNIQUE key)
            $sqlGetEval = "SELECT idEvaluacion FROM evaluaciones_practicas 
                        WHERE idStudent = :idStudent AND idPractica = :idPractica AND tipo = 'parcial' 
                        LIMIT 1";
            $stmt = $pdo->prepare($sqlGetEval);
            $stmt->execute([
                ":idStudent" => $idStudent,
                ":idPractica" => $evaluacion['idPractica']
            ]);
            $idEvaluacion = (int) $stmt->fetchColumn();

            // 2) Guardar rubros (máximo labels definidos)
            $sqlRubros = "INSERT INTO evaluaciones_practicas_rubros 
                (idEvaluacion, rubro_index, rubro_label, calificacion)
                VALUES (:idEvaluacion, :rubro_index, :rubro_label, :calificacion)
                ON DUPLICATE KEY UPDATE 
                    rubro_label = VALUES(rubro_label),
                    calificacion = VALUES(calificacion)";

            $stmtRub = $pdo->prepare($sqlRubros);
            $rubros = $evaluacion['rubros'] ?? [];
            $lenR = min(count($rubros), count(self::RUBROS_LABELS));
            for ($i = 0; $i < $lenR; $i++) {
                $stmtRub->execute([
                    ":idEvaluacion" => $idEvaluacion,
                    ":rubro_index" => $i,
                    ":rubro_label" => self::RUBROS_LABELS[$i],
                    ":calificacion" => $rubros[$i],
                ]);
            }

            // 3) Guardar actitudes (máximo labels definidos)
            $sqlActitudes = "INSERT INTO evaluaciones_practicas_actitudes 
                (idEvaluacion, actitud_index, actitud_label, valor)
                VALUES (:idEvaluacion, :actitud_index, :actitud_label, :valor)
                ON DUPLICATE KEY UPDATE 
                    actitud_label = VALUES(actitud_label),
                    valor = VALUES(valor)";

            $stmtAct = $pdo->prepare($sqlActitudes);
            $acts = $evaluacion['actitudes'] ?? [];
            $lenA = min(count($acts), count(self::ACTITUDES_LABELS));
            for ($i = 0; $i < $lenA; $i++) {
                $stmtAct->execute([
                    ":idEvaluacion" => $idEvaluacion,
                    ":actitud_index" => $i,
                    ":actitud_label" => self::ACTITUDES_LABELS[$i],
                    ":valor" => $acts[$i],
                ]);
            }

            $pdo->commit();
            return ["success" => true, "message" => "Evaluación parcial registrada/actualizada con éxito"];
        } catch (Exception $e) {
            try {
                self::db()->rollBack();
            } catch (\Throwable $e2) {
            }
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    static public function mdlEvaluarParticipanteFinal($idStudent, $evaluacion)
    {
        try {
            $pdo = self::db();
            $pdo->beginTransaction();

            // 1) Insertar o actualizar encabezado
            $sqlEncabezado = "INSERT INTO evaluaciones_practicas 
                (idStudent, idPractica, tipo, fortalezas, debilidades)
                VALUES (:idStudent, :idPractica, 'final', :fortalezas, :debilidades)
                ON DUPLICATE KEY UPDATE 
                    fortalezas = VALUES(fortalezas),
                    debilidades = VALUES(debilidades),
                    updated_at = CURRENT_TIMESTAMP()";

            $stmt = $pdo->prepare($sqlEncabezado);
            $stmt->bindValue(":idStudent", $idStudent, PDO::PARAM_INT);
            $stmt->bindValue(":idPractica", $evaluacion['idPractica'], PDO::PARAM_INT);
            $stmt->bindValue(":fortalezas", $evaluacion['fortalezas'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(":debilidades", $evaluacion['debilidades'] ?? null, PDO::PARAM_STR);
            $stmt->execute();

            // 2) Obtener idEvaluacion
            $sqlGetEval = "SELECT idEvaluacion FROM evaluaciones_practicas 
                        WHERE idStudent = :idStudent 
                            AND idPractica = :idPractica 
                            AND tipo = 'final'
                        LIMIT 1";
            $stmt = $pdo->prepare($sqlGetEval);
            $stmt->execute([
                ":idStudent" => $idStudent,
                ":idPractica" => $evaluacion['idPractica']
            ]);
            $idEvaluacion = (int) $stmt->fetchColumn();

            // 3) Guardar rubros
            $sqlRubros = "INSERT INTO evaluaciones_practicas_rubros 
                (idEvaluacion, rubro_index, rubro_label, calificacion)
                VALUES (:idEvaluacion, :rubro_index, :rubro_label, :calificacion)
                ON DUPLICATE KEY UPDATE 
                    rubro_label = VALUES(rubro_label),
                    calificacion = VALUES(calificacion)";
            $stmtRub = $pdo->prepare($sqlRubros);
            $rubros = $evaluacion['rubros'] ?? [];
            $lenR = min(count($rubros), count(self::RUBROS_LABELS));
            for ($i = 0; $i < $lenR; $i++) {
                $stmtRub->execute([
                    ":idEvaluacion" => $idEvaluacion,
                    ":rubro_index" => $i,
                    ":rubro_label" => self::RUBROS_LABELS[$i],
                    ":calificacion" => $rubros[$i]
                ]);
            }

            // 4) Guardar actitudes
            $sqlActitudes = "INSERT INTO evaluaciones_practicas_actitudes 
                (idEvaluacion, actitud_index, actitud_label, valor)
                VALUES (:idEvaluacion, :actitud_index, :actitud_label, :valor)
                ON DUPLICATE KEY UPDATE 
                    actitud_label = VALUES(actitud_label),
                    valor = VALUES(valor)";
            $stmtAct = $pdo->prepare($sqlActitudes);
            $acts = $evaluacion['actitudes'] ?? [];
            $lenA = min(count($acts), count(self::ACTITUDES_LABELS));
            for ($i = 0; $i < $lenA; $i++) {
                $stmtAct->execute([
                    ":idEvaluacion" => $idEvaluacion,
                    ":actitud_index" => $i,
                    ":actitud_label" => self::ACTITUDES_LABELS[$i],
                    ":valor" => $acts[$i]
                ]);
            }

            $pdo->commit();
            return ["success" => true, "message" => "Evaluación final registrada/actualizada con éxito"];
        } catch (Exception $e) {
            try {
                self::db()->rollBack();
            } catch (\Throwable $e2) {
            }
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    /* =====================================================
     * ÁREAS INTERNAS DE PRÁCTICAS PROFESIONALES
     * ===================================================== */

    // --- CRUD Áreas (admin) ---

    static public function mdlGetAllAreasPracticas()
    {
        return self::all(
            "SELECT ap.*, u.firstname AS encargado_nombre, u.lastname AS encargado_apellido,
                    (SELECT COUNT(*) FROM postulaciones_areas_practicas pap
                     WHERE pap.area_id = ap.id AND pap.status = 1) AS postulados
             FROM areas_practicas ap
             LEFT JOIN users u ON u.id = ap.encargado_user_id
             WHERE ap.isActive = 1
             ORDER BY ap.created_at DESC"
        );
    }

    static public function mdlGetAreaPracticaById(int $id)
    {
        return self::one(
            "SELECT ap.*, u.firstname AS encargado_nombre
             FROM areas_practicas ap
             LEFT JOIN users u ON u.id = ap.encargado_user_id
             WHERE ap.id = :id AND ap.isActive = 1",
            [':id' => $id]
        );
    }

    static public function mdlCreateAreaPractica(array $data)
    {
        $ok = self::aff(
            "INSERT INTO areas_practicas (nombre, descripcion, cupo, isOpen, encargado_user_id)
             VALUES (:nombre, :desc, :cupo, 1, :enc)",
            [
                ':nombre' => $data['nombre'],
                ':desc' => $data['descripcion'] ?? null,
                ':cupo' => (int) ($data['cupo'] ?? 10),
                ':enc' => $data['encargado_user_id'] ?: null,
            ]
        ) > 0;
        return $ok
            ? self::ok('Área creada correctamente.', ['id' => (int) self::lastId()])
            : self::fail('Error al crear el área.');
    }

    static public function mdlUpdateAreaPractica(array $data)
    {
        $ok = self::aff(
            "UPDATE areas_practicas
             SET nombre = :nombre, descripcion = :desc, cupo = :cupo, encargado_user_id = :enc
             WHERE id = :id AND isActive = 1",
            [
                ':nombre' => $data['nombre'],
                ':desc' => $data['descripcion'] ?? null,
                ':cupo' => (int) ($data['cupo'] ?? 10),
                ':enc' => $data['encargado_user_id'] ?: null,
                ':id' => (int) $data['id'],
            ]
        ) > 0;
        return $ok ? self::ok('Área actualizada.') : self::fail('Error al actualizar.');
    }

    static public function mdlToggleAreaPractica(int $id, int $isOpen)
    {
        $ok = self::aff(
            "UPDATE areas_practicas SET isOpen = :o WHERE id = :id AND isActive = 1",
            [':o' => $isOpen, ':id' => $id]
        ) > 0;
        return $ok ? self::ok() : self::fail('Error al cambiar estado.');
    }

    static public function mdlDeleteAreaPractica(int $id)
    {
        $ok = self::aff(
            "UPDATE areas_practicas SET isActive = 0 WHERE id = :id",
            [':id' => $id]
        ) > 0;
        return $ok ? self::ok('Área eliminada.') : self::fail('Error al eliminar.');
    }

    // --- Postulaciones (student + admin) ---

    /**
     * Devuelve áreas abiertas con el estado de postulación del alumno.
     */
    static public function mdlGetAreasPracticasAbiertas(int $studentId)
    {
        $sql = "SELECT ap.*,
                    (SELECT COUNT(*) FROM postulaciones_areas_practicas p
                     WHERE p.area_id = ap.id AND p.status = 1) AS ocupadas,
                    (SELECT p2.status FROM postulaciones_areas_practicas p2
                     WHERE p2.student_id = :sid AND p2.area_id = ap.id
                     LIMIT 1) AS postulacion_status,
                    (SELECT p3.id FROM postulaciones_areas_practicas p3
                     WHERE p3.student_id = :sid2 AND p3.area_id = ap.id
                     LIMIT 1) AS postulacion_id
                FROM areas_practicas ap
                WHERE ap.isOpen = 1 AND ap.isActive = 1
                ORDER BY ap.nombre ASC";
        $areas = self::all($sql, [':sid' => $studentId, ':sid2' => $studentId]);

        // postulacion activa del alumno (si existe)
        $postulacion = self::one(
            "SELECT pap.*, ap.nombre AS area_nombre
             FROM postulaciones_areas_practicas pap
             JOIN areas_practicas ap ON ap.id = pap.area_id
             WHERE pap.student_id = :sid",
            [':sid' => $studentId]
        );

        return ['areas' => $areas, 'postulacion' => $postulacion];
    }

    static public function mdlGetPostulacionAreaByStudent(int $studentId)
    {
        return self::one(
            "SELECT pap.*, ap.nombre AS area_nombre, ap.encargado_user_id
             FROM postulaciones_areas_practicas pap
             JOIN areas_practicas ap ON ap.id = pap.area_id
             WHERE pap.student_id = :sid",
            [':sid' => $studentId]
        );
    }

    static public function mdlPostularseArea(int $studentId, int $areaId)
    {
        // Solo una postulación por alumno
        $exists = (int) self::col(
            "SELECT COUNT(*) FROM postulaciones_areas_practicas WHERE student_id = :s",
            [':s' => $studentId]
        );
        if ($exists > 0) {
            return self::fail('Ya tienes una postulación activa.');
        }

        // Verificar cupo
        $area = self::one(
            "SELECT cupo, (SELECT COUNT(*) FROM postulaciones_areas_practicas
              WHERE area_id = :a AND status = 1) AS ocupadas
             FROM areas_practicas WHERE id = :a2 AND isOpen = 1 AND isActive = 1",
            [':a' => $areaId, ':a2' => $areaId]
        );
        if (!$area)
            return self::fail('El área no está disponible.');
        if ($area['ocupadas'] >= $area['cupo'])
            return self::fail('El área ya no tiene cupo disponible.');

        $ok = self::aff(
            "INSERT INTO postulaciones_areas_practicas (student_id, area_id) VALUES (:s, :a)",
            [':s' => $studentId, ':a' => $areaId]
        ) > 0;
        return $ok ? self::ok('Postulación enviada correctamente.') : self::fail('Error al postularse.');
    }

    static public function mdlGetPostulacionesByArea(int $areaId)
    {
        return self::all(
            "SELECT pap.*, sp.nombre_completo, sp.matricula, sp.programa_academico, sp.email, sp.telefono
             FROM postulaciones_areas_practicas pap
             JOIN students_practicas sp ON sp.id = pap.student_id
             WHERE pap.area_id = :a
             ORDER BY pap.created_at DESC",
            [':a' => $areaId]
        );
    }

    static public function mdlGetAllPostulacionesAreas()
    {
        return self::all(
            "SELECT pap.*, sp.nombre_completo, sp.matricula, sp.programa_academico, sp.email,
                    ap.nombre AS area_nombre,
                    sp.practicas_finalizadas, sp.fecha_finalizacion
             FROM postulaciones_areas_practicas pap
             JOIN students_practicas sp ON sp.id = pap.student_id
             JOIN areas_practicas ap ON ap.id = pap.area_id
             ORDER BY pap.created_at DESC"
        );
    }

    static public function mdlGetPostulacionAreaById(int $id)
    {
        return self::one(
            "SELECT pap.*, sp.nombre_completo, sp.email, ap.nombre AS area_nombre
             FROM postulaciones_areas_practicas pap
             JOIN students_practicas sp ON sp.id = pap.student_id
             JOIN areas_practicas ap ON ap.id = pap.area_id
             WHERE pap.id = :id",
            [':id' => $id]
        );
    }

    /** Devuelve email, nombre completo del alumno y nombre del área para una postulación */
    static public function mdlGetEncargadoInfoByArea(int $areaId): ?array
    {
        try {
            $pdo = Conexion::conectar();
            $stmt = $pdo->prepare(
                "SELECT u.email,
                        CONCAT(u.firstname, ' ', u.lastname) AS nombre_completo,
                        a.nombre AS area_nombre
                 FROM areas_practicas a
                 JOIN users u ON u.id = a.encargado_user_id
                 WHERE a.id = :id"
            );
            $stmt->execute([':id' => $areaId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $row ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    static public function mdlGetPostulacionInfo(int $id): ?array
    {
        try {
            $pdo = Conexion::conectar();
            $stmt = $pdo->prepare(
                "SELECT sp.email,
                        sp.nombre_completo,
                        a.nombre AS area_nombre
                 FROM postulaciones_areas_practicas p
                 JOIN students_practicas sp ON sp.id = p.student_id
                 JOIN areas_practicas a ON a.id = p.area_id
                 WHERE p.id = :id"
            );
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $row ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    static public function mdlAceptarPostulacionArea(int $id, string $fechaInicio)
    {
        $ok = self::aff(
            "UPDATE postulaciones_areas_practicas SET status = 1, start_date = :f WHERE id = :id",
            [':f' => $fechaInicio, ':id' => $id]
        ) > 0;
        return $ok ? self::ok('Postulación aceptada.') : self::fail('Error al aceptar.');
    }

    static public function mdlRechazarPostulacionArea(int $id)
    {
        $ok = self::aff(
            "UPDATE postulaciones_areas_practicas SET status = 2 WHERE id = :id",
            [':id' => $id]
        ) > 0;
        return $ok ? self::ok('Postulación rechazada.') : self::fail('Error al rechazar.');
    }

    /**
     * Devuelve evaluaciones (parcial y/o final) de un alumno en una práctica de área interna.
     * $idPractica = postulaciones_areas_practicas.id
     */
    static public function mdlGetEvaluacionesByEstudiante(int $idStudent, int $idPractica): array
    {
        $pdo = self::db();

        $evals = $pdo->prepare(
            "SELECT idEvaluacion, tipo, fortalezas, debilidades, updated_at
             FROM evaluaciones_practicas
             WHERE idStudent = :s AND idPractica = :p
             ORDER BY FIELD(tipo,'parcial','final')"
        );
        $evals->execute([':s' => $idStudent, ':p' => $idPractica]);
        $rows = $evals->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($rows as $row) {
            $id = (int) $row['idEvaluacion'];

            $rubros = $pdo->prepare(
                "SELECT rubro_index, rubro_label, calificacion
                 FROM evaluaciones_practicas_rubros
                 WHERE idEvaluacion = :e ORDER BY rubro_index"
            );
            $rubros->execute([':e' => $id]);

            $actitudes = $pdo->prepare(
                "SELECT actitud_index, actitud_label, valor
                 FROM evaluaciones_practicas_actitudes
                 WHERE idEvaluacion = :e ORDER BY actitud_index"
            );
            $actitudes->execute([':e' => $id]);

            $result[$row['tipo']] = [
                'idEvaluacion' => $id,
                'fortalezas' => $row['fortalezas'],
                'debilidades' => $row['debilidades'],
                'updated_at' => $row['updated_at'],
                'rubros' => $rubros->fetchAll(PDO::FETCH_ASSOC),
                'actitudes' => $actitudes->fetchAll(PDO::FETCH_ASSOC),
            ];
        }
        return $result;
    }

    // --- Asistencias internas ---

    static public function mdlRegisterAttendanceArea(array $data)
    {
        $exists = (int) self::col(
            "SELECT COUNT(*) FROM asistencias_areas_practicas
             WHERE postulacion_id = :p AND student_id = :s AND fecha = :f AND status <> 'rechazada'",
            [':p' => $data['postulacion_id'], ':s' => $data['student_id'], ':f' => $data['fecha']]
        );
        if ($exists > 0) {
            return [
                'success' => false,
                'icon' => 'warning',
                'title' => 'Asistencia ya registrada',
                'message' => 'Ya existe una asistencia para esta fecha. Si no aparece, puede estar pendiente de aprobación.'
            ];
        }

        $ok = self::aff(
            "INSERT INTO asistencias_areas_practicas
             (postulacion_id, area_id, student_id, fecha, hora_entrada, hora_salida, actividad, status)
             VALUES (:p, :a, :s, :f, :he, :hs, :act, 'pendiente')",
            [
                ':p' => $data['postulacion_id'],
                ':a' => $data['area_id'],
                ':s' => $data['student_id'],
                ':f' => $data['fecha'],
                ':he' => $data['hora_entrada'],
                ':hs' => $data['hora_salida'],
                ':act' => $data['actividad'],
            ]
        ) > 0;
        return $ok ? self::ok('Asistencia registrada correctamente.')
            : ['success' => false, 'icon' => 'error', 'title' => 'Error', 'message' => 'Error al registrar la asistencia.'];
    }

    static public function mdlGetAsistenciasArea(int $postulacionId, int $studentId)
    {
        return self::all(
            "SELECT * FROM asistencias_areas_practicas
             WHERE postulacion_id = :p AND student_id = :s AND status = 'aprobada'
             ORDER BY fecha ASC",
            [':p' => $postulacionId, ':s' => $studentId]
        );
    }

    /** Asistencias pendientes por área (para el encargado) */
    static public function mdlGetPendingAsistenciasArea(int $areaId)
    {
        return self::all(
            "SELECT aap.*,
                    ROUND(TIME_TO_SEC(TIMEDIFF(aap.hora_salida, aap.hora_entrada)) / 3600, 2) AS horas,
                    sp.nombre_completo
             FROM asistencias_areas_practicas aap
             JOIN students_practicas sp ON sp.id = aap.student_id
             WHERE aap.area_id = :a AND aap.status = 'pendiente'
             ORDER BY aap.fecha ASC",
            [':a' => $areaId]
        );
    }

    static public function mdlAprobarAsistenciaArea(int $id)
    {
        $ok = self::aff(
            "UPDATE asistencias_areas_practicas SET status = 'aprobada' WHERE id = :id",
            [':id' => $id]
        ) > 0;
        return $ok ? self::ok('Asistencia aprobada.') : self::fail('Error al aprobar.');
    }

    static public function mdlRechazarAsistenciaArea(int $id)
    {
        $ok = self::aff(
            "UPDATE asistencias_areas_practicas SET status = 'rechazada' WHERE id = :id",
            [':id' => $id]
        ) > 0;
        return $ok ? self::ok('Asistencia rechazada.') : self::fail('Error al rechazar.');
    }

    /** Alumnos activos en el área (encargado dashboard) */
    static public function mdlGetStudentsInArea(int $areaId)
    {
        return self::all(
            "SELECT sp.*, pap.id AS postulacion_id, pap.start_date,
                    (SELECT SUM(TIMESTAMPDIFF(SECOND, aap.hora_entrada, aap.hora_salida))/3600
                     FROM asistencias_areas_practicas aap
                     WHERE aap.postulacion_id = pap.id AND aap.status = 'aprobada') AS horas_acumuladas,
                    (SELECT COUNT(*) FROM reporte_parcial_practicas rpp
                     WHERE rpp.idPractica = pap.id AND rpp.idStudent = sp.id
                       AND rpp.aproveOrganismo = 0 AND rpp.completed = 0) AS reporte_parcial_pendiente,
                    (SELECT COUNT(*) FROM reporte_final_practicas rfp
                     WHERE rfp.idPractica = pap.id AND rfp.idStudent = sp.id
                       AND rfp.aproveOrganismo = 0 AND rfp.completed = 0) AS reporte_final_pendiente
             FROM postulaciones_areas_practicas pap
             JOIN students_practicas sp ON sp.id = pap.student_id
             WHERE pap.area_id = :a AND pap.status = 1",
            [':a' => $areaId]
        );
    }

    /** Postulaciones pendientes (status=0) de un área específica */
    static public function mdlGetPostulacionesPendientesByArea(int $areaId)
    {
        return self::all(
            "SELECT pap.*, sp.nombre_completo, sp.matricula, sp.email, sp.programa_academico
             FROM postulaciones_areas_practicas pap
             JOIN students_practicas sp ON sp.id = pap.student_id
             WHERE pap.area_id = :a AND pap.status = 0
             ORDER BY pap.created_at ASC",
            [':a' => $areaId]
        );
    }

    /** Asistencias pendientes de aprobación de un área */
    static public function mdlGetPendingAsistenciasByArea(int $areaId)
    {
        return self::all(
            "SELECT aap.*, sp.nombre_completo, sp.matricula
             FROM asistencias_areas_practicas aap
             JOIN postulaciones_areas_practicas pap ON pap.id = aap.postulacion_id
             JOIN students_practicas sp ON sp.id = aap.student_id
             WHERE pap.area_id = :a AND aap.status = 'pendiente'
             ORDER BY aap.fecha ASC",
            [':a' => $areaId]
        );
    }

    /** Áreas asignadas a un usuario encargado */
    static public function mdlGetAreasByEncargado(int $userId)
    {
        return self::all(
            "SELECT * FROM areas_practicas WHERE encargado_user_id = :u AND isActive = 1",
            [':u' => $userId]
        );
    }

    /** Todas las asistencias de un alumno en un área (para gráfica) */
    static public function mdlGetAllAsistenciasAreaByStudent(int $studentId, int $areaId)
    {
        return self::all(
            "SELECT a.*,
                    p.area_id,
                    ROUND(TIME_TO_SEC(TIMEDIFF(a.hora_salida, a.hora_entrada)) / 3600, 2) AS horas
             FROM asistencias_areas_practicas a
             JOIN postulaciones_areas_practicas p ON p.id = a.postulacion_id
             WHERE p.student_id = :s AND p.area_id = :ar AND a.status = 'aprobada'
             ORDER BY a.fecha ASC",
            [':s' => $studentId, ':ar' => $areaId]
        );
    }

    /** Alumnos de servicio social relacionados con eventos de un teacher */
    static public function mdlGetSsStudentsByTeacher(int $teacherUserId)
    {
        try {
            $pdo = Conexion::conectar();
            $stmt = $pdo->prepare(
                "SELECT s.idStudent, s.firstname, s.lastname, s.email, s.matricula,
                        e.idEvent, e.eventName AS evento_nombre, e.date AS evento_fecha,
                        e.location AS evento_lugar, e.points AS evento_puntos,
                        se.status AS inscripcion_status, se.applicationDate
                 FROM student s
                 JOIN students_events se ON se.idStudent = s.idStudent
                 JOIN events e ON e.idEvent = se.idEvent
                 WHERE e.idUser = :teacher AND s.status = 1
                 ORDER BY s.lastname, s.firstname, e.date DESC"
            );
            $stmt->bindParam(':teacher', $teacherUserId, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            // Agrupar por alumno
            $map = [];
            foreach ($rows as $r) {
                $sid = $r['idStudent'];
                if (!isset($map[$sid])) {
                    $map[$sid] = [
                        'idStudent' => $r['idStudent'],
                        'firstname' => $r['firstname'],
                        'lastname' => $r['lastname'],
                        'email' => $r['email'],
                        'matricula' => $r['matricula'],
                        'eventos' => []
                    ];
                }
                $map[$sid]['eventos'][] = [
                    'idEvent' => $r['idEvent'],
                    'nombre' => $r['evento_nombre'],
                    'fecha' => $r['evento_fecha'],
                    'lugar' => $r['evento_lugar'],
                    'puntos' => $r['evento_puntos'],
                    'status' => $r['inscripcion_status'],
                    'applicationDate' => $r['applicationDate'],
                ];
            }
            return array_values($map);
        } catch (PDOException $e) {
            return [];
        }
    }

    /** Agregar anotación del encargado sobre un alumno */
    static public function mdlAddTeacherAnnotation(int $teacherUserId, string $type, int $studentId, string $nota)
    {
        try {
            $pdo = Conexion::conectar();
            $stmt = $pdo->prepare(
                "INSERT INTO teacher_annotations (teacher_user_id, student_ref_type, student_id, nota)
                 VALUES (:t, :tp, :s, :n)"
            );
            $stmt->bindParam(':t', $teacherUserId, PDO::PARAM_INT);
            $stmt->bindParam(':tp', $type, PDO::PARAM_STR);
            $stmt->bindParam(':s', $studentId, PDO::PARAM_INT);
            $stmt->bindParam(':n', $nota, PDO::PARAM_STR);
            $stmt->execute();
            $newId = (int) $pdo->lastInsertId();
            $stmt->closeCursor();
            return ['status' => 'success', 'id' => $newId];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /** Obtener anotaciones del encargado sobre un alumno */
    static public function mdlGetTeacherAnnotations(int $teacherUserId, string $type, int $studentId)
    {
        return self::all(
            "SELECT * FROM teacher_annotations
             WHERE teacher_user_id = :t AND student_ref_type = :tp AND student_id = :s
             ORDER BY created_at DESC",
            [':t' => $teacherUserId, ':tp' => $type, ':s' => $studentId]
        );
    }

    /** Eliminar anotación (solo la del propio teacher) */
    static public function mdlDeleteTeacherAnnotation(int $id, int $teacherUserId)
    {
        try {
            $pdo = Conexion::conectar();
            $stmt = $pdo->prepare(
                "DELETE FROM teacher_annotations WHERE id = :id AND teacher_user_id = :t"
            );
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':t', $teacherUserId, PDO::PARAM_INT);
            $stmt->execute();
            $affected = $stmt->rowCount();
            $stmt->closeCursor();
            return $affected > 0
                ? ['status' => 'success']
                : ['status' => 'error', 'message' => 'No encontrado'];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /** Obtener todas las asistencias recientes de un alumno para mostrarlas en historial */
    static public function mdlGetStudentAttendanceHistory(int $studentId, int $areaId)
    {
        return self::all(
            "SELECT a.id, a.fecha, a.hora_entrada, a.hora_salida,
                    ROUND(TIME_TO_SEC(TIMEDIFF(a.hora_salida, a.hora_entrada)) / 3600, 2) AS horas,
                    a.actividad, a.status, a.created_at
             FROM asistencias_areas_practicas a
             JOIN postulaciones_areas_practicas p ON p.id = a.postulacion_id
             WHERE p.student_id = :s AND p.area_id = :ar
             ORDER BY a.fecha DESC",
            [':s' => $studentId, ':ar' => $areaId]
        );
    }

    // --- MÉTODOS PARA FASE 3: STRIKES Y VALIDACIÓN DE HORAS ---

    static public function mdlUpdateAsistenciaHorasValidadas($idAsistencia, $horasValidadas, $tieneStrike)
    {
        $ok = self::aff(
            "UPDATE asistencias_practicas
             SET horas_validadas = :hv, tiene_strike = :ts
             WHERE idAsistencia = :id",
            [':hv' => $horasValidadas, ':ts' => $tieneStrike, ':id' => $idAsistencia]
        ) > 0;
        return $ok;
    }

    static public function mdlGetStrikesCountAlumno($idStudent, $idPractica = null)
    {
        if ($idPractica) {
            return (int) self::col(
                "SELECT COUNT(*) FROM strikes_practicas WHERE idStudent = :s AND idPractica = :p",
                [':s' => $idStudent, ':p' => $idPractica]
            );
        }
        return (int) self::col(
            "SELECT COUNT(*) FROM strikes_practicas WHERE idStudent = :s",
            [':s' => $idStudent]
        );
    }

    static public function mdlGetStrikesCountOrganismo($idOrganismo)
    {
        return (int) self::col(
            "SELECT strikes_count FROM organismos_externos WHERE id = :o",
            [':o' => $idOrganismo]
        );
    }

    static public function mdlIncrementStrikesOrganismo($idOrganismo)
    {
        $ok = self::aff(
            "UPDATE organismos_externos SET strikes_count = strikes_count + 1 WHERE id = :o",
            [':o' => $idOrganismo]
        ) > 0;
        return self::mdlGetStrikesCountOrganismo($idOrganismo);
    }

    static public function mdlInsertStrike($data)
    {
        $ok = self::aff(
            "INSERT INTO strikes_practicas 
             (idAsistencia, idStudent, idOrganismo, idPractica, horas_reportadas, horas_validadas, horas_excedente, tipo_strike, consecuencia_alumno, consecuencia_empresa)
             VALUES (:idAsist, :idStud, :idOrg, :idPrac, :hr, :hv, :he, :ts, :ca, :ce)",
            [
                ':idAsist' => $data['idAsistencia'],
                ':idStud' => $data['idStudent'],
                ':idOrg' => $data['idOrganismo'],
                ':idPrac' => $data['idPractica'],
                ':hr' => $data['horas_reportadas'],
                ':hv' => $data['horas_validadas'],
                ':he' => $data['horas_excedente'],
                ':ts' => $data['tipo_strike'] ?? 'exceso_horas',
                ':ca' => $data['consecuencia_alumno'],
                ':ce' => $data['consecuencia_empresa'],
            ]
        ) > 0;
        return $ok;
    }

    public static function mdlRemoveStrikeStudent($idStudent) {
        $db = self::db();
        try {
            $db->beginTransaction();
            self::run("DELETE FROM strikes_practicas WHERE idStudent = :id ORDER BY created_at DESC LIMIT 1", [':id' => $idStudent]);
            $strikes = self::col("SELECT COUNT(*) FROM strikes_practicas WHERE idStudent = :id", [':id' => $idStudent]);
            if ($strikes < 2) {
                self::run("UPDATE students_practicas SET dado_de_baja_por_strike = 0, fecha_baja_strike = NULL WHERE id = :id", [':id' => $idStudent]);
            }
            $db->commit();
            return true;
        } catch (\Throwable $e) {
            $db->rollBack();
            return false;
        }
    }

    public static function mdlVerificarBloqueoActivo($idStudent, $idOrganismo) {
        $db = self::db();
        $stmt = $db->prepare("SELECT id FROM alumno_empresa_bloqueo WHERE idStudent = :idStudent AND idOrganismo = :idOrganismo AND estado = 'activo' LIMIT 1");
        $stmt->execute([':idStudent' => $idStudent, ':idOrganismo' => $idOrganismo]);
        return $stmt->rowCount() > 0;
    }

    public static function mdlBloquearAlumnoDeEmpresa($idStudent, $idOrganismo, $motivo) {
        $db = self::db();
        try {
            $sql = "INSERT INTO alumno_empresa_bloqueo (idStudent, idOrganismo, motivo, estado, fecha_bloqueo) 
                    VALUES (:idStudent, :idOrganismo, :motivo, 'activo', CURRENT_TIMESTAMP)
                    ON DUPLICATE KEY UPDATE 
                        estado = 'activo', 
                        motivo = VALUES(motivo), 
                        fecha_bloqueo = CURRENT_TIMESTAMP";
            $stmt = $db->prepare($sql);
            $stmt->execute([':idStudent' => $idStudent, ':idOrganismo' => $idOrganismo, ':motivo' => $motivo]);
            return true;
        } catch (\Throwable $e) {
            error_log("Error al bloquear alumno de empresa: " . $e->getMessage());
            return false;
        }
    }

    public static function mdlUnblockStudent($idStudent) {
        try {
            self::run("UPDATE students_practicas SET dado_de_baja_por_strike = 0, fecha_baja_strike = NULL WHERE id = :id", [':id' => $idStudent]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    static public function mdlBajaAlumnoPorStrike($idStudent, $idPractica)
    {
        try {
            $pdo = self::db();
            $pdo->beginTransaction();

            // Bloquear en la lista negra
            $idOrganismo = (int) self::col("SELECT organismo_externo_id FROM solicitudes_practicantes WHERE id = :p", [':p' => $idPractica]);
            if ($idOrganismo) {
                self::mdlBloquearAlumnoDeEmpresa($idStudent, $idOrganismo, 'Baja por strikes o penalizaciones');
            }

            $stmt1 = $pdo->prepare("UPDATE students_practicas SET dado_de_baja_por_strike = 1, fecha_baja_strike = NOW() WHERE id = :idStud");
            $stmt1->execute([':idStud' => $idStudent]);

            $stmt2 = $pdo->prepare("DELETE FROM students_in_practices WHERE idStudent = :idStud AND idPractica = :idPrac");
            $stmt2->execute([':idStud' => $idStudent, ':idPrac' => $idPractica]);

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            try {
                self::db()->rollBack();
            } catch (\Throwable $t) {
            }
            return false;
        }
    }

    static public function mdlBloquearSolicitudesOrganismo($idOrganismo, $motivo, $bloqueadoPor = null)
    {
        try {
            $pdo = self::db();
            $pdo->beginTransaction();

            $stmt1 = $pdo->prepare("UPDATE organismos_externos SET solicitudes_bloqueadas = 1, motivo_bloqueo = :motivo, fecha_bloqueo = NOW(), bloqueado_por = :bp WHERE id = :o");
            $stmt1->execute([':motivo' => $motivo, ':bp' => $bloqueadoPor, ':o' => $idOrganismo]);

            $stmt2 = $pdo->prepare("INSERT INTO historial_bloqueos_organismos (idOrganismo, accion, motivo, admin_id) VALUES (:o, 'bloqueo', :motivo, :bp)");
            $stmt2->execute([':o' => $idOrganismo, ':motivo' => $motivo, ':bp' => $bloqueadoPor]);

            $stmt3 = $pdo->prepare("UPDATE solicitudes_practicantes SET activo = 0 WHERE organismo_externo_id = :o AND activo = 1");
            $stmt3->execute([':o' => $idOrganismo]);

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            try {
                self::db()->rollBack();
            } catch (\Throwable $t) {
            }
            return false;
        }
    }

    public static function mdlBlockExternal($id, $motivo, $adminId, $adminName) {
        $db = self::db();
        try {
            $db->beginTransaction();
            self::run("UPDATE organismos_externos SET solicitudes_bloqueadas = 1, motivo_bloqueo = :motivo, fecha_bloqueo = NOW(), bloqueado_por = :adminId WHERE id = :id", [
                ':motivo' => $motivo,
                ':adminId' => $adminId,
                ':id' => $id
            ]);
            self::run("INSERT INTO historial_bloqueos_organismos (idOrganismo, accion, motivo, admin_id, admin_name) VALUES (:id, 'bloqueo', :motivo, :adminId, :adminName)", [
                ':id' => $id,
                ':motivo' => $motivo,
                ':adminId' => $adminId,
                ':adminName' => $adminName
            ]);
            $db->commit();
            $email = self::col("SELECT email FROM organismos_externos WHERE id = :id", [':id' => $id]);
            if ($email) {
                require_once __DIR__ . '/../controller/emails.php';
                if (function_exists('sendTemplateByKey')) {
                    sendTemplateByKey('pp_organismo_bloqueado_manual', $email, ['motivo' => $motivo], 'Notificación de Bloqueo de Solicitudes - UNIMO');
                }
            }
            return true;
        } catch (\Throwable $e) {
            $db->rollBack();
            error_log($e->getMessage());
            return false;
        }
    }

    public static function mdlUnblockExternal($id, $motivo, $adminId, $adminName) {
        $db = self::db();
        try {
            $db->beginTransaction();
            self::run("UPDATE organismos_externos SET solicitudes_bloqueadas = 0, motivo_bloqueo = NULL, fecha_bloqueo = NULL, bloqueado_por = NULL WHERE id = :id", [
                ':id' => $id
            ]);
            self::run("INSERT INTO historial_bloqueos_organismos (idOrganismo, accion, motivo, admin_id, admin_name) VALUES (:id, 'desbloqueo', :motivo, :adminId, :adminName)", [
                ':id' => $id,
                ':motivo' => $motivo,
                ':adminId' => $adminId,
                ':adminName' => $adminName
            ]);
            $db->commit();
            $email = self::col("SELECT email FROM organismos_externos WHERE id = :id", [':id' => $id]);
            if ($email) {
                require_once __DIR__ . '/../controller/emails.php';
                if (function_exists('sendTemplateByKey')) {
                    sendTemplateByKey('pp_organismo_desbloqueado', $email, [], 'Notificación de Desbloqueo de Solicitudes - UNIMO');
                }
            }
            return true;
        } catch (\Throwable $e) {
            $db->rollBack();
            error_log($e->getMessage());
            return false;
        }
    }

    public static function mdlRemoveStrikeOrganismo($idOrganismo) {
        $db = self::db();
        try {
            $db->beginTransaction();
            self::run("UPDATE organismos_externos SET strikes_count = GREATEST(0, strikes_count - 1) WHERE id = :id", [':id' => $idOrganismo]);
            $org = self::one("SELECT strikes_count, solicitudes_bloqueadas FROM organismos_externos WHERE id = :id", [':id' => $idOrganismo]);
            if ($org && $org['strikes_count'] < 2 && $org['solicitudes_bloqueadas'] == 1) {
                self::run("UPDATE organismos_externos SET solicitudes_bloqueadas = 0, motivo_bloqueo = NULL, fecha_bloqueo = NULL, bloqueado_por = NULL WHERE id = :id", [':id' => $idOrganismo]);
                self::run("INSERT INTO historial_bloqueos_organismos (idOrganismo, accion, motivo, admin_id, admin_name) VALUES (:id, 'desbloqueo', 'Strikes reducidos a menos de 2 automáticamente', NULL, 'Sistema')", [':id' => $idOrganismo]);
            }
            $db->commit();
            return true;
        } catch (\Throwable $e) {
            $db->rollBack();
            return false;
        }
    }

    public static function mdlHardResetStudent($idStudent, $motivo, $adminId, $adminName) {
        $db = self::db();
        try {
            $db->beginTransaction();
            $studentData = self::one("SELECT * FROM students_practicas WHERE id = :id", [':id' => $idStudent]);
            if (!$studentData) {
                $db->rollBack();
                return false;
            }
            self::run("INSERT INTO historial_hard_reset_alumnos (idStudent, nombre_alumno, matricula, datos_previos, motivo, admin_id, admin_name) VALUES (:id, :nombre, :matricula, :snapshot, :motivo, :adminId, :adminName)", [
                ':id' => $idStudent,
                ':nombre' => ($studentData['name'] ?? '') . ' ' . ($studentData['last_name'] ?? ''),
                ':matricula' => $studentData['matricula'] ?? 'N/A',
                ':snapshot' => json_encode($studentData),
                ':motivo' => $motivo,
                ':adminId' => $adminId,
                ':adminName' => $adminName
            ]);
            
            $tablesToDelete = [
                'asistencias_practicas' => 'idStudent',
                'students_in_practices' => 'idStudent',
                'reporte_parcial_practicas' => 'idStudent',
                'reporte_final_practicas' => 'idStudent',
                'evaluaciones_practicas' => 'idStudent',
                'cartas_practicas_profesionales' => 'student_id',
                'constancias_acreditacion' => 'student_id',
                'strikes_practicas' => 'idStudent'
            ];
            foreach ($tablesToDelete as $table => $col) {
                self::run("DELETE FROM $table WHERE $col = :id", [':id' => $idStudent]);
            }

            self::run("UPDATE students_practicas SET isAcepted = 1, isActive = 1, practicas_finalizadas = 0, fecha_finalizacion = NULL, dado_de_baja_por_strike = 0, fecha_baja_strike = NULL WHERE id = :id", [':id' => $idStudent]);

            $db->commit();
            $email = $studentData['email'] ?? '';
            if ($email) {
                require_once __DIR__ . '/../controller/emails.php';
                if (function_exists('sendTemplateByKey')) {
                    sendTemplateByKey('pp_hard_reset_alumno', $email, ['motivo' => $motivo], 'Reinicio de Proceso de Prácticas Profesionales');
                }
            }
            return true;
        } catch (\Throwable $e) {
            $db->rollBack();
            $db->rollBack();
            error_log($e->getMessage());
            return false;
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // ── FLUJO DE RECHAZO Y CORRECCIÓN DE ORGANISMOS EXTERNOS ────────────────
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Crea un registro de rechazo para un organismo externo.
     * Cambia isAcepted=2 y actualiza rechazo_activo_id.
     * Invalida tokens/rechazos anteriores pendientes.
     *
     * @return int  ID del rechazo creado, o 0 en caso de error.
     */
    public static function mdlRejectOrganismo(int $orgId, string $motivoGeneral, int $adminId, string $adminName): int
    {
        try {
            $db = Conexion::conectar();
            $db->beginTransaction();

            // Invalidar tokens anteriores activos del organismo
            $db->prepare(
                "UPDATE tokens_correccion_organismos SET usado = 1, usado_at = NOW()
                 WHERE organismo_id = :id AND usado = 0"
            )->execute([':id' => $orgId]);

            // Marcar rechazos anteriores como expirados
            $db->prepare(
                "UPDATE rechazos_organismos SET estado = 'expirado'
                 WHERE organismo_id = :id AND estado = 'pendiente_correccion'"
            )->execute([':id' => $orgId]);

            // Insertar nuevo rechazo
            $stmt = $db->prepare(
                "INSERT INTO rechazos_organismos
                    (organismo_id, motivo_general, admin_id, admin_name, estado, created_at)
                 VALUES (:org, :motivo, :admin, :aname, 'pendiente_correccion', NOW())"
            );
            $stmt->execute([
                ':org'    => $orgId,
                ':motivo' => $motivoGeneral,
                ':admin'  => $adminId,
                ':aname'  => $adminName,
            ]);
            $rechazoId = (int) $db->lastInsertId();

            // Cambiar estado del organismo a 2=rechazado y actualizar rechazo_activo_id
            $db->prepare(
                "UPDATE organismos_externos
                 SET isAcepted = 2, rechazo_activo_id = :rid, updated_at = NOW()
                 WHERE id = :id"
            )->execute([':rid' => $rechazoId, ':id' => $orgId]);

            $db->commit();
            return $rechazoId;
        } catch (\Throwable $e) {
            if (isset($db) && $db->inTransaction()) $db->rollBack();
            error_log('[PracticasModel::mdlRejectOrganismo] ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Guarda los campos marcados como erróneos en un rechazo.
     *
     * @param array $campos  Cada elemento: ['campo', 'campo_label', 'estado', 'motivo', 'observacion', 'valor_original']
     */
    public static function mdlSaveRechazoCampos(int $rechazoId, array $campos): bool
    {
        if (empty($campos)) return false;
        try {
            $stmt = Conexion::conectar()->prepare(
                "INSERT INTO rechazo_campos
                    (rechazo_id, campo, campo_label, estado, motivo, observacion, valor_original)
                 VALUES (:rid, :campo, :label, :estado, :motivo, :obs, :val)"
            );
            foreach ($campos as $c) {
                $stmt->execute([
                    ':rid'    => $rechazoId,
                    ':campo'  => $c['campo'],
                    ':label'  => $c['campo_label'],
                    ':estado' => $c['estado'] ?? 'incorrecto',
                    ':motivo' => $c['motivo'],
                    ':obs'    => $c['observacion'] ?? null,
                    ':val'    => $c['valor_original'] ?? null,
                ]);
            }
            return true;
        } catch (\Throwable $e) {
            error_log('[PracticasModel::mdlSaveRechazoCampos] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene el rechazo activo de un organismo (estado = 'pendiente_correccion').
     */
    public static function mdlGetRechazoActivo(int $orgId): ?array
    {
        $stmt = self::run(
            "SELECT * FROM rechazos_organismos
             WHERE organismo_id = :id AND estado = 'pendiente_correccion'
             ORDER BY created_at DESC LIMIT 1",
            [':id' => $orgId]
        );
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Obtiene los campos rechazados de un rechazo.
     */
    public static function mdlGetRechazoCampos(int $rechazoId): array
    {
        $stmt = self::run(
            "SELECT * FROM rechazo_campos WHERE rechazo_id = :rid ORDER BY id ASC",
            [':rid' => $rechazoId]
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el historial completo de rechazos de un organismo.
     */
    public static function mdlGetHistorialRechazos(int $orgId): array
    {
        $stmt = self::run(
            "SELECT r.*,
                    (SELECT COUNT(*) FROM rechazo_campos rc WHERE rc.rechazo_id = r.id) AS total_campos
             FROM rechazos_organismos r
             WHERE r.organismo_id = :id
             ORDER BY r.created_at DESC",
            [':id' => $orgId]
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── TOKENS ───────────────────────────────────────────────────────────────

    /**
     * Crea un token de corrección para un rechazo.
     */
    public static function mdlCreateTokenCorreccion(int $rechazoId, int $orgId, string $token, string $expiraAt): bool
    {
        try {
            $stmt = Conexion::conectar()->prepare(
                "INSERT INTO tokens_correccion_organismos
                    (rechazo_id, organismo_id, token, expira_at, usado, created_at)
                 VALUES (:rid, :oid, :token, :expira, 0, NOW())"
            );
            $stmt->execute([
                ':rid'    => $rechazoId,
                ':oid'    => $orgId,
                ':token'  => $token,
                ':expira' => $expiraAt,
            ]);
            return true;
        } catch (\Throwable $e) {
            error_log('[PracticasModel::mdlCreateTokenCorreccion] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene el registro de un token (con datos del organismo y rechazo).
     */
    public static function mdlGetTokenCorreccion(string $token): ?array
    {
        $stmt = self::run(
            "SELECT t.*,
                    o.empresa, o.email, o.email_legal, o.nombre_contacto,
                    o.isAcepted AS org_status,
                    r.motivo_general, r.admin_name, r.estado AS rechazo_estado
             FROM tokens_correccion_organismos t
             JOIN organismos_externos o ON o.id = t.organismo_id
             JOIN rechazos_organismos r ON r.id = t.rechazo_id
             WHERE t.token = :token
             LIMIT 1",
            [':token' => $token]
        );
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Invalida un token marcándolo como usado.
     */
    public static function mdlInvalidateToken(string $token): bool
    {
        return self::aff(
            "UPDATE tokens_correccion_organismos SET usado = 1, usado_at = NOW() WHERE token = :token",
            [':token' => $token]
        ) > 0;
    }

    /**
     * Guarda el OTP (hasheado) en el token, reinicia intentos.
     */
    public static function mdlSetOtp(string $token, string $otpHash, string $expiraAt): bool
    {
        return self::aff(
            "UPDATE tokens_correccion_organismos
             SET otp_code = :hash, otp_expira_at = :exp, otp_intentos = 0, otp_bloqueado_hasta = NULL
             WHERE token = :token",
            [':hash' => $otpHash, ':exp' => $expiraAt, ':token' => $token]
        ) > 0;
    }

    /**
     * Incrementa el contador de intentos fallidos de OTP y retorna el total.
     */
    public static function mdlIncrementOtpIntentos(string $token): int
    {
        self::run(
            "UPDATE tokens_correccion_organismos SET otp_intentos = otp_intentos + 1 WHERE token = :token",
            [':token' => $token]
        );
        $stmt = self::run(
            "SELECT otp_intentos FROM tokens_correccion_organismos WHERE token = :token",
            [':token' => $token]
        );
        return (int) ($stmt->fetchColumn() ?? 0);
    }

    /**
     * Aplica bloqueo por intentos de OTP fallidos.
     */
    public static function mdlSetOtpBloqueo(string $token, string $bloqueadoHasta): bool
    {
        return self::aff(
            "UPDATE tokens_correccion_organismos SET otp_bloqueado_hasta = :hasta WHERE token = :token",
            [':hasta' => $bloqueadoHasta, ':token' => $token]
        ) > 0;
    }

    /**
     * Incrementa el contador de intentos fallidos de verificación de email.
     */
    public static function mdlIncrementEmailIntentos(string $token): int
    {
        self::run(
            "UPDATE tokens_correccion_organismos SET email_intentos = email_intentos + 1 WHERE token = :token",
            [':token' => $token]
        );
        $stmt = self::run(
            "SELECT email_intentos FROM tokens_correccion_organismos WHERE token = :token",
            [':token' => $token]
        );
        return (int) ($stmt->fetchColumn() ?? 0);
    }

    /**
     * Incrementa el contador de reenvíos de OTP y retorna el total.
     */
    public static function mdlIncrementOtpReenvios(string $token): int
    {
        self::run(
            "UPDATE tokens_correccion_organismos SET otp_reenvios = otp_reenvios + 1 WHERE token = :token",
            [':token' => $token]
        );
        $stmt = self::run(
            "SELECT otp_reenvios FROM tokens_correccion_organismos WHERE token = :token",
            [':token' => $token]
        );
        return (int) ($stmt->fetchColumn() ?? 0);
    }

    /**
     * Registra la IP de acceso al token.
     */
    public static function mdlSetTokenIpAcceso(string $token, string $ip): void
    {
        self::run(
            "UPDATE tokens_correccion_organismos SET ip_acceso = :ip WHERE token = :token",
            [':ip' => $ip, ':token' => $token]
        );
    }

    // ── CORRECCIONES ─────────────────────────────────────────────────────────

    /**
     * Actualiza campos del organismo (solo los permitidos por el rechazo).
     * Nunca actualiza campos que no están en $camposPermitidos.
     *
     * @param array $cambios          ['campo' => 'valor_nuevo', ...]
     * @param array $camposPermitidos Lista de nombres de campo permitidos (de rechazo_campos)
     */
    public static function mdlUpdateOrganismoFields(int $orgId, array $cambios, array $camposPermitidos): bool
    {
        if (empty($cambios) || empty($camposPermitidos)) return false;

        // Columnas de BD editables permitidas
        $allowedDbCols = [
            'empresa', 'tipo_persona', 'giro', 'fecha_constitucion', 'web',
            'calle', 'cp', 'colonia', 'ciudad',
            'telefonos', 'email', 'nombre_contacto', 'celular',
            'rep_legal', 'cargo_legal', 'email_legal', 'tel_oficina',
            'actividades',
        ];

        $sets   = [];
        $params = [':id' => $orgId];

        foreach ($cambios as $col => $val) {
            if (!in_array($col, $allowedDbCols, true)) continue;
            if (!in_array($col, $camposPermitidos, true)) continue;
            $sets[]            = "`{$col}` = :{$col}";
            $params[":{$col}"] = $val;
        }

        if (empty($sets)) return false;

        $sets[] = '`updated_at` = NOW()';
        $sql    = "UPDATE organismos_externos SET " . implode(', ', $sets) . " WHERE id = :id";

        return self::aff($sql, $params) >= 0;
    }

    /**
     * Registra en el historial los cambios realizados por el organismo.
     *
     * @param array $cambios [['campo'=>..., 'valor_anterior'=>..., 'valor_nuevo'=>...], ...]
     */
    public static function mdlSaveHistorialCorreccion(int $rechazoId, int $orgId, array $cambios, string $ip): bool
    {
        if (empty($cambios)) return true;
        try {
            $stmt = Conexion::conectar()->prepare(
                "INSERT INTO historial_correcciones_organismos
                    (rechazo_id, organismo_id, campo, valor_anterior, valor_nuevo, modificado_por, ip, created_at)
                 VALUES (:rid, :oid, :campo, :ant, :nuevo, 'organismo', :ip, NOW())"
            );
            foreach ($cambios as $c) {
                $stmt->execute([
                    ':rid'   => $rechazoId,
                    ':oid'   => $orgId,
                    ':campo' => $c['campo'],
                    ':ant'   => $c['valor_anterior'] ?? null,
                    ':nuevo' => $c['valor_nuevo'] ?? null,
                    ':ip'    => $ip,
                ]);
            }
            return true;
        } catch (\Throwable $e) {
            error_log('[PracticasModel::mdlSaveHistorialCorreccion] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Marca un campo específico como corregido en rechazo_campos.
     */
    public static function mdlMarcarCampoCorregido(int $rechazoId, string $campo, string $valorNuevo): void
    {
        self::run(
            "UPDATE rechazo_campos SET corregido = 1, valor_corregido = :val
             WHERE rechazo_id = :rid AND campo = :campo",
            [':val' => $valorNuevo, ':rid' => $rechazoId, ':campo' => $campo]
        );
    }

    /**
     * Marca el rechazo como corregido y actualiza el organismo a estado 3.
     */
    public static function mdlMarcarRechazoCorregido(int $rechazoId, int $orgId): bool
    {
        try {
            $db = Conexion::conectar();
            $db->beginTransaction();

            $db->prepare(
                "UPDATE rechazos_organismos SET estado = 'corregido', corregido_at = NOW() WHERE id = :id"
            )->execute([':id' => $rechazoId]);

            $db->prepare(
                "UPDATE organismos_externos SET isAcepted = 3, updated_at = NOW() WHERE id = :id"
            )->execute([':id' => $orgId]);

            $db->commit();
            return true;
        } catch (\Throwable $e) {
            if (isset($db) && $db->inTransaction()) $db->rollBack();
            error_log('[PracticasModel::mdlMarcarRechazoCorregido] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cambia el estado isAcepted de un organismo (uso genérico).
     * 0=pendiente, 1=aceptado, 2=rechazado, 3=corregido pendiente revisión
     */
    public static function mdlChangeOrganismoStatus(int $orgId, int $nuevoEstado): bool
    {
        return self::aff(
            "UPDATE organismos_externos SET isAcepted = :estado, updated_at = NOW() WHERE id = :id",
            [':estado' => $nuevoEstado, ':id' => $orgId]
        ) >= 0;
    }

    /**
     * Obtiene los datos completos de un organismo para el formulario de corrección.
     */
    public static function mdlGetOrganismoParaCorreccion(int $orgId): ?array
    {
        $stmt = self::run(
            "SELECT id, empresa, tipo_persona, giro, fecha_constitucion, web,
                    calle, cp, colonia, ciudad,
                    telefonos, email, nombre_contacto, celular,
                    rep_legal, cargo_legal, email_legal, tel_oficina,
                    actividades, isAcepted, rechazo_activo_id
             FROM organismos_externos
             WHERE id = :id AND isActive = 1
             LIMIT 1",
            [':id' => $orgId]
        );
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /* =========================================================================
     * EVALUACIÓN INTEGRAL DE EXPERIENCIA EN PRÁCTICAS
     * ========================================================================= */

    /**
     * Verifica si las evaluaciones integrales están completas para un hito dado.
     * Retorna ['empresa' => bool, 'alumno' => bool]
     */
    public static function mdlGetEstadoEvaluacionesIntegrales(int $idStudent, int $idPractica, string $tipoHito): array
    {
        $sql = "SELECT tipo_evaluador, completada 
                FROM evaluacion_integral_practicas 
                WHERE idStudent = :student AND idPractica = :practica AND tipo_hito = :hito";
        $rows = self::all($sql, [
            ':student' => $idStudent,
            ':practica' => $idPractica,
            ':hito' => $tipoHito
        ]);

        $estado = ['empresa' => false, 'alumno' => false];
        foreach ($rows as $row) {
            $estado[$row['tipo_evaluador']] = (bool) $row['completada'];
        }
        return $estado;
    }

    /**
     * Obtiene la evaluación integral completa (cabecera + respuestas).
     */
    public static function mdlGetEvaluacionIntegral(int $idStudent, int $idPractica, string $tipoHito, string $tipoEvaluador): ?array
    {
        $cabecera = self::one(
            "SELECT * FROM evaluacion_integral_practicas 
             WHERE idStudent = :s AND idPractica = :p AND tipo_hito = :h AND tipo_evaluador = :e",
            [':s' => $idStudent, ':p' => $idPractica, ':h' => $tipoHito, ':e' => $tipoEvaluador]
        );

        if (!$cabecera) {
            return null;
        }

        $respuestas = self::all(
            "SELECT * FROM evaluacion_integral_respuestas WHERE idEvaluacion = :id ORDER BY pregunta_index ASC",
            [':id' => $cabecera['id']]
        );

        $cabecera['respuestas'] = $respuestas;
        return $cabecera;
    }

    /**
     * Guarda una evaluación integral completa (transacción PDO).
     */
    public static function mdlSaveEvaluacionIntegral(array $data): array
    {
        $conn = Conexion::conectar();
        try {
            $conn->beginTransaction();

            // Verificar si ya existe
            $stmt = $conn->prepare(
                "SELECT id FROM evaluacion_integral_practicas 
                 WHERE idStudent = :s AND idPractica = :p AND tipo_hito = :h AND tipo_evaluador = :e"
            );
            $stmt->execute([
                ':s' => $data['idStudent'],
                ':p' => $data['idPractica'],
                ':h' => $data['tipoHito'],
                ':e' => $data['tipoEvaluador']
            ]);
            $exist = $stmt->fetchColumn();

            if ($exist) {
                // Son inmutables según requerimiento, si ya existe y está completada, no actualizar
                $conn->rollBack();
                return ['success' => false, 'message' => 'La evaluación ya fue completada previamente.'];
            }

            // Insertar cabecera
            $stmtInsert = $conn->prepare(
                "INSERT INTO evaluacion_integral_practicas 
                 (idStudent, idPractica, idOrganismo, tipo_hito, tipo_evaluador, completada) 
                 VALUES (:s, :p, :o, :h, :e, 1)"
            );
            $stmtInsert->execute([
                ':s' => $data['idStudent'],
                ':p' => $data['idPractica'],
                ':o' => $data['idOrganismo'],
                ':h' => $data['tipoHito'],
                ':e' => $data['tipoEvaluador']
            ]);
            $idEvaluacion = $conn->lastInsertId();

            // Insertar respuestas
            $stmtResp = $conn->prepare(
                "INSERT INTO evaluacion_integral_respuestas 
                 (idEvaluacion, pregunta_index, pregunta_texto, tipo_respuesta, valor_numerico, valor_texto) 
                 VALUES (:id, :idx, :txt, :tipo, :valNum, :valTxt)"
            );

            foreach ($data['respuestas'] as $resp) {
                $stmtResp->execute([
                    ':id' => $idEvaluacion,
                    ':idx' => $resp['index'],
                    ':txt' => $resp['texto'],
                    ':tipo' => $resp['tipo'],
                    ':valNum' => $resp['valorNumerico'],
                    ':valTxt' => $resp['valorTexto']
                ]);
            }

            $conn->commit();
            return ['success' => true, 'message' => 'Evaluación guardada correctamente.'];

        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            return ['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()];
        }
    }

    /**
     * Calcula las horas aprobadas acumuladas de un alumno en una práctica.
     */
    public static function mdlCalcularHorasAcumuladas(int $idStudent, int $idPractica): float
    {
        $asistencias = self::all(
            "SELECT hora_entrada, hora_salida, horas_validadas FROM asistencias_practicas 
             WHERE idStudent = :s AND idPractica = :p AND status = 'aprobado'",
            [':s' => $idStudent, ':p' => $idPractica]
        );

        $totalHours = 0;
        foreach ($asistencias as $attendance) {
            if ($attendance['horas_validadas'] !== null) {
                $totalHours += (float)$attendance['horas_validadas'];
            } else if ($attendance['hora_entrada'] && $attendance['hora_salida']) {
                $h1 = strtotime($attendance['hora_entrada']);
                $h2 = strtotime($attendance['hora_salida']);
                if ($h1 && $h2) {
                    $diff = ($h2 - $h1) / 3600;
                    if ($diff < 0) $diff += 24;
                    $totalHours += $diff;
                }
            }
        }
        return (float) number_format($totalHours, 2, '.', '');
    }

    /**
     * Determina si el alumno está bloqueado por evaluaciones pendientes.
     * Retorna null si no hay bloqueo, o un array con info del bloqueo.
     */
    public static function mdlCheckBloqueoEvaluaciones(int $idStudent, int $idPractica): ?array
    {
        $horasAcumuladas = self::mdlCalcularHorasAcumuladas($idStudent, $idPractica);
        
        // Bloqueo 180 horas
        if ($horasAcumuladas >= 180 && $horasAcumuladas < 360) {
            // Verificar si entregó reporte parcial
            $reporteParcial = self::col(
                "SELECT idReporteParcial FROM reporte_parcial_practicas 
                 WHERE idStudent = :s AND idPractica = :p",
                [':s' => $idStudent, ':p' => $idPractica]
            );
            
            if (!$reporteParcial) {
                return ['motivo' => 'reporte_parcial', 'mensaje' => 'Debes entregar el Reporte Parcial de 180 horas.'];
            }

            // Verificar evaluaciones integrales
            $evalEstado = self::mdlGetEstadoEvaluacionesIntegrales($idStudent, $idPractica, 'intermedia');
            if (!$evalEstado['alumno']) {
                return ['motivo' => 'eval_integral_alumno_180', 'mensaje' => 'Debes completar la Evaluación Integral de 180 horas.'];
            }
            if (!$evalEstado['empresa']) {
                return ['motivo' => 'eval_integral_empresa_180', 'mensaje' => 'La empresa debe completar tu Evaluación Integral de 180 horas.'];
            }
        }
        
        // Bloqueo 360 horas
        if ($horasAcumuladas >= 360) {
            // Verificar reporte final
            $reporteFinal = self::col(
                "SELECT idReporteFinal FROM reporte_final_practicas 
                 WHERE idStudent = :s AND idPractica = :p",
                [':s' => $idStudent, ':p' => $idPractica]
            );
            
            if (!$reporteFinal) {
                return ['motivo' => 'reporte_final', 'mensaje' => 'Debes entregar el Reporte Final de 360 horas.'];
            }

            // Verificar evaluaciones integrales
            $evalEstado = self::mdlGetEstadoEvaluacionesIntegrales($idStudent, $idPractica, 'final');
            if (!$evalEstado['alumno']) {
                return ['motivo' => 'eval_integral_alumno_360', 'mensaje' => 'Debes completar la Evaluación Integral Final de 360 horas.'];
            }
            if (!$evalEstado['empresa']) {
                return ['motivo' => 'eval_integral_empresa_360', 'mensaje' => 'La empresa debe completar tu Evaluación Integral Final de 360 horas.'];
            }
        }

        return null;
    }

    public static function mdlGetEvaluacionesRecientesAdmin()
    {
        $stmt = Conexion::conectar()->prepare("SELECT COUNT(*) FROM evaluacion_integral_practicas WHERE vista_por_admin = 0");
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public static function mdlGetSemaforoEvaluaciones()
    {
        $sql = "SELECT p.idStudent, p.tipo_evaluador, AVG(r.valor_numerico) as promedio
                FROM evaluacion_integral_practicas p
                JOIN evaluacion_integral_respuestas r ON p.id = r.idEvaluacion
                WHERE r.tipo_respuesta = 'likert'
                GROUP BY p.idStudent, p.tipo_evaluador";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $semaforos = [];
        foreach ($results as $row) {
            $studentId = $row['idStudent'];
            if (!isset($semaforos[$studentId])) {
                $semaforos[$studentId] = ['empresa' => null, 'alumno' => null];
            }
            $semaforos[$studentId][$row['tipo_evaluador']] = round($row['promedio'], 1);
        }
        return $semaforos;
    }

    public static function mdlGetDetalleEvaluacionesAlumno($idStudent)
    {
        $sql = "SELECT p.id as id_evaluacion, p.tipo_hito, p.tipo_evaluador, p.fecha_evaluacion, p.comentarios_generales,
                       r.pregunta_index, r.tipo_respuesta, r.valor_numerico, r.valor_texto
                FROM evaluacion_integral_practicas p
                LEFT JOIN evaluacion_integral_respuestas r ON p.id = r.idEvaluacion
                WHERE p.idStudent = :idStudent
                ORDER BY p.fecha_evaluacion DESC, p.tipo_hito, p.tipo_evaluador, r.pregunta_index";
        
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":idStudent", $idStudent, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $evaluaciones = [];
        foreach ($rows as $row) {
            $key = $row['tipo_hito'] . '_' . $row['tipo_evaluador'];
            if (!isset($evaluaciones[$key])) {
                $evaluaciones[$key] = [
                    'id' => $row['id_evaluacion'],
                    'hito' => $row['tipo_hito'],
                    'evaluador' => $row['tipo_evaluador'],
                    'fecha' => $row['fecha_evaluacion'],
                    'comentarios_generales' => $row['comentarios_generales'],
                    'respuestas' => []
                ];
            }
            if ($row['pregunta_index'] !== null) {
                $evaluaciones[$key]['respuestas'][] = [
                    'index' => $row['pregunta_index'],
                    'tipo' => $row['tipo_respuesta'],
                    'valor_numerico' => $row['valor_numerico'],
                    'valor_texto' => $row['valor_texto']
                ];
            }
        }
        return array_values($evaluaciones);
    }

    public static function mdlMarcarEvaluacionesVista($idStudent)
    {
        $stmt = Conexion::conectar()->prepare("UPDATE evaluacion_integral_practicas SET vista_por_admin = 1 WHERE idStudent = :idStudent AND vista_por_admin = 0");
        $stmt->bindParam(":idStudent", $idStudent, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
