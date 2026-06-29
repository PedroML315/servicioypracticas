<?php 

class ServicioModel
{

    public static function generateFolio(int $studentId): string
    {
        $pdo = Conexion::conectar();
        $year = date('Y');

        // 1) Buscar si ya existe un folio para este estudiante en el año actual
        $sql = "SELECT code FROM cartas_servicio_social WHERE student_id = :student_id AND YEAR(created_at) = :year LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':student_id' => $studentId,
            ':year' => $year
        ]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing && !empty($existing['code'])) {
            return $existing['code'];
        }

        // 2) Obtener el mayor número de folio para el año actual
        $sql = "
            SELECT MAX(
                CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(code, '-', 2), '-', -1) AS UNSIGNED)
            ) AS max_num
            FROM cartas_servicio_social
            WHERE YEAR(created_at) = :year
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':year' => $year]);
        $maxNum = (int) $stmt->fetch(PDO::FETCH_ASSOC)['max_num'];

        // 3) Incrementar y formatear
        $nextNum = $maxNum + 1;
        $folio = sprintf('DSS-%03d-%s', $nextNum, $year);

        // 4) Insertar el folio
        $insert = "INSERT INTO cartas_servicio_social (code, student_id) VALUES (:code, :student_id)";
        $stmt = $pdo->prepare($insert);
        $stmt->execute([
            ':code' => $folio,
            ':student_id' => $studentId
        ]);

        return $folio;
    }

    static public function mdlGetTipoServicios()
    {
        $sql = "SELECT * FROM tipo_servicio";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();
        $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    // ──────────────────────────────────────────────────
    //  IJUMICH – Carta de presentación
    // ──────────────────────────────────────────────────

    /**
     * Registra una solicitud de carta de presentación enviada por el alumno.
     */
    static public function mdlSolicitarCartaPresentacion(array $data): bool
    {
        $pdo  = Conexion::conectar();
        $sql  = "INSERT INTO servicio_social_ijumich
                    (student_id, tipo,
                     nombre_organismo, rfc_clave, area_departamento, telefono_organismo, email_organismo, pagina_web,
                     calle_numero, colonia, codigo_postal, municipio, estado, pais,
                     estudios_responsable, responsable, puesto_responsable, telefono_responsable, email_responsable,
                     domicilio, observaciones)
                 VALUES
                    (:student_id, 'carta_presentacion',
                     :nombre_organismo, :rfc_clave, :area_departamento, :telefono_organismo, :email_organismo, :pagina_web,
                     :calle_numero, :colonia, :codigo_postal, :municipio, :estado, :pais,
                     :estudios_responsable, :responsable, :puesto_responsable, :telefono_responsable, :email_responsable,
                     :domicilio, :observaciones)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':student_id'           => $data['student_id'],
            ':nombre_organismo'     => $data['nombre_organismo']     ?? null,
            ':rfc_clave'            => $data['rfc_clave']            ?? null,
            ':area_departamento'    => $data['area_departamento']    ?? null,
            ':telefono_organismo'   => $data['telefono_organismo']   ?? null,
            ':email_organismo'      => $data['email_organismo']      ?? null,
            ':pagina_web'           => $data['pagina_web']           ?? null,
            ':calle_numero'         => $data['calle_numero']         ?? null,
            ':colonia'              => $data['colonia']              ?? null,
            ':codigo_postal'        => $data['codigo_postal']        ?? null,
            ':municipio'            => $data['municipio']            ?? null,
            ':estado'               => $data['estado']               ?? null,
            ':pais'                 => $data['pais']                 ?? null,
            ':estudios_responsable' => $data['estudios_responsable'] ?? null,
            ':responsable'          => $data['responsable']          ?? null,
            ':puesto_responsable'   => $data['puesto_responsable']   ?? null,
            ':telefono_responsable' => $data['telefono_responsable'] ?? null,
            ':email_responsable'    => $data['email_responsable']    ?? null,
            ':domicilio'            => $data['domicilio']            ?? null,
            ':observaciones'        => $data['observaciones']        ?? null,
        ]);
    }

    // ──────────────────────────────────────────────────
    //  IJUMICH – Carta de acreditación de prácticas
    // ──────────────────────────────────────────────────

    /**
     * Registra la carga de una carta de acreditación de prácticas profesionales.
     */
    static public function mdlCargarCartaPracticas(array $data): bool
    {
        $pdo  = Conexion::conectar();
        $sql  = "INSERT INTO servicio_social_ijumich
                    (student_id, tipo, archivo_path, archivo_nombre, observaciones)
                 VALUES
                    (:student_id, 'carta_practicas', :archivo_path, :archivo_nombre, :observaciones)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':student_id'    => $data['student_id'],
            ':archivo_path'  => $data['archivo_path'],
            ':archivo_nombre'=> $data['archivo_nombre'],
            ':observaciones' => $data['observaciones'] ?? null,
        ]);
    }

    // ──────────────────────────────────────────────────
    //  IJUMICH – Carta de liberación
    // ──────────────────────────────────────────────────

    /**
     * Registra la carga de una carta de liberación por parte del alumno.
     */
    static public function mdlCargarCartaLiberacion(array $data): bool
    {
        $pdo  = Conexion::conectar();
        $sql  = "INSERT INTO servicio_social_ijumich
                    (student_id, tipo, archivo_path, archivo_nombre, observaciones)
                 VALUES
                    (:student_id, 'carta_liberacion', :archivo_path, :archivo_nombre, :observaciones)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':student_id'    => $data['student_id'],
            ':archivo_path'  => $data['archivo_path'],
            ':archivo_nombre'=> $data['archivo_nombre'],
            ':observaciones' => $data['observaciones'] ?? null,
        ]);
    }

    // ──────────────────────────────────────────────────
    //  IJUMICH – Consultas para el administrador
    // ──────────────────────────────────────────────────

    /**
     * Devuelve todas las solicitudes IJUMICH pendientes, junto con
     * datos básicos del alumno (nombre, matrícula, email).
     */
    static public function mdlGetSolicitudesIjumichPendientes(): array
    {
        $pdo = Conexion::conectar();
        $sql = "SELECT
                    s.id,
                    s.student_id,
                    s.tipo,
                    s.nombre_organismo,
                    s.rfc_clave,
                    s.area_departamento,
                    s.telefono_organismo,
                    s.email_organismo,
                    s.pagina_web,
                    s.calle_numero,
                    s.colonia,
                    s.codigo_postal,
                    s.municipio,
                    s.estado,
                    s.pais,
                    s.estudios_responsable,
                    s.responsable,
                    s.puesto_responsable,
                    s.telefono_responsable,
                    s.email_responsable,
                    s.domicilio,
                    s.observaciones,
                    s.archivo_path,
                    s.archivo_nombre,
                    s.status,
                    s.created_at,
                    CONCAT(st.firstname, ' ', st.lastname) AS nombre_alumno,
                    st.email AS email_alumno,
                    st.matricula
                FROM servicio_social_ijumich s
                JOIN student st ON st.idStudent = s.student_id
                WHERE s.status = 'pendiente'
                ORDER BY s.created_at ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un registro IJUMICH por su ID.
     */
    static public function mdlGetSolicitudIjumichById(int $id): ?array
    {
        $pdo  = Conexion::conectar();
        $stmt = $pdo->prepare("SELECT * FROM servicio_social_ijumich WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Aprueba una solicitud IJUMICH.
     */
    static public function mdlAprobarSolicitudIjumich(int $id, int $adminId, ?string $comentario = null): bool
    {
        $pdo  = Conexion::conectar();
        $sql  = "UPDATE servicio_social_ijumich
                 SET status = 'aprobado',
                     comentario_admin = :comentario,
                     reviewed_by = :reviewed_by,
                     reviewed_at = NOW()
                 WHERE id = :id AND status = 'pendiente'";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':id'          => $id,
            ':reviewed_by' => $adminId,
            ':comentario'  => $comentario,
        ]);
    }

    /**
     * Rechaza una solicitud IJUMICH.
     */
    static public function mdlRechazarSolicitudIjumich(int $id, int $adminId, string $comentario): bool
    {
        $pdo  = Conexion::conectar();
        $sql  = "UPDATE servicio_social_ijumich
                 SET status = 'rechazado',
                     comentario_admin = :comentario,
                     reviewed_by = :reviewed_by,
                     reviewed_at = NOW()
                 WHERE id = :id AND status = 'pendiente'";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':id'          => $id,
            ':reviewed_by' => $adminId,
            ':comentario'  => $comentario,
        ]);
    }

    /**
     * Devuelve el historial completo de solicitudes IJUMICH de un alumno.
     */
    static public function mdlGetHistorialIjumichAlumno(int $studentId): array
    {
        $pdo  = Conexion::conectar();
        $sql  = "SELECT * FROM servicio_social_ijumich
                 WHERE student_id = :student_id
                 ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':student_id' => $studentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ──────────────────────────────────────────────────────────
    //  INTERNO – Carta de Aceptación de Servicio Social
    // ──────────────────────────────────────────────────────────

    /**
     * Genera (o recupera) un folio DSS-CASS-NNN-YYYY para la carta de aceptación.
     */
    public static function generateFolioAceptacion(int $studentId): string
    {
        $pdo  = Conexion::conectar();
        $year = date('Y');

        // 1) ¿Ya existe folio para este alumno en el año actual?
        $stmt = $pdo->prepare(
            "SELECT code FROM cartas_aceptacion_servicio
             WHERE student_id = :student_id AND YEAR(created_at) = :year LIMIT 1"
        );
        $stmt->execute([':student_id' => $studentId, ':year' => $year]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existing && !empty($existing['code'])) {
            return $existing['code'];
        }

        // 2) Máximo número actual
        $stmt = $pdo->prepare(
            "SELECT MAX(CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(code, '-', 3), '-', -1) AS UNSIGNED)) AS max_num
             FROM cartas_aceptacion_servicio WHERE YEAR(created_at) = :year"
        );
        $stmt->execute([':year' => $year]);
        $maxNum  = (int)$stmt->fetch(PDO::FETCH_ASSOC)['max_num'];
        $nextNum = $maxNum + 1;
        $folio   = sprintf('DSS-CASS-%03d-%s', $nextNum, $year);

        // 3) Insertar
        $stmt = $pdo->prepare(
            "INSERT INTO cartas_aceptacion_servicio (code, student_id) VALUES (:code, :student_id)"
        );
        $stmt->execute([':code' => $folio, ':student_id' => $studentId]);
        return $folio;
    }

    /**
     * Registra la carga de la carta de finalización de prácticas (alumno interno).
     */
    static public function mdlCargarCartaPracticasInterno(array $data): bool
    {
        $pdo  = Conexion::conectar();
        $sql  = "INSERT INTO servicio_social_ijumich
                    (student_id, tipo, archivo_path, archivo_nombre, observaciones)
                 VALUES
                    (:student_id, 'carta_practicas_interno', :archivo_path, :archivo_nombre, :observaciones)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':student_id'     => $data['student_id'],
            ':archivo_path'   => $data['archivo_path'],
            ':archivo_nombre' => $data['archivo_nombre'],
            ':observaciones'  => $data['observaciones'] ?? null,
        ]);
    }

    /**
     * Registra la solicitud de carta de aceptación de servicio social (generada por admin/sistema).
     */
    static public function mdlSolicitarCartaAceptacionServicio(int $studentId): bool
    {
        $pdo  = Conexion::conectar();
        // Solo insertar si no hay una pendiente o aprobada ya
        $check = $pdo->prepare(
            "SELECT id FROM servicio_social_ijumich
             WHERE student_id = :student_id AND tipo = 'carta_aceptacion_servicio'
               AND status IN ('pendiente','aprobado') LIMIT 1"
        );
        $check->execute([':student_id' => $studentId]);
        if ($check->fetch()) return true; // ya existe

        $sql  = "INSERT INTO servicio_social_ijumich (student_id, tipo) VALUES (:student_id, 'carta_aceptacion_servicio')";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':student_id' => $studentId]);
    }

    /**
     * Registra la carga de carta de liberación para alumno interno.
     */
    static public function mdlCargarCartaLiberacionInterno(array $data): bool
    {
        $pdo  = Conexion::conectar();
        $sql  = "INSERT INTO servicio_social_ijumich
                    (student_id, tipo, archivo_path, archivo_nombre, observaciones)
                 VALUES
                    (:student_id, 'carta_liberacion_interno', :archivo_path, :archivo_nombre, :observaciones)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':student_id'     => $data['student_id'],
            ':archivo_path'   => $data['archivo_path'],
            ':archivo_nombre' => $data['archivo_nombre'],
            ':observaciones'  => $data['observaciones'] ?? null,
        ]);
    }

    /**
     * Registra la carga de un reporte parcial (1, 2 o 3) para alumno interno.
     */
    static public function mdlCargarReporteParcial(array $data): bool
    {
        $num  = (int)$data['numero_reporte'];
        if (!in_array($num, [1, 2, 3])) return false;
        $tipo = 'reporte_parcial_' . $num;
        $pdo  = Conexion::conectar();
        $sql  = "INSERT INTO servicio_social_ijumich
                    (student_id, tipo, archivo_path, archivo_nombre, observaciones)
                 VALUES
                    (:student_id, :tipo, :archivo_path, :archivo_nombre, :observaciones)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':student_id'     => $data['student_id'],
            ':tipo'           => $tipo,
            ':archivo_path'   => $data['archivo_path'],
            ':archivo_nombre' => $data['archivo_nombre'],
            ':observaciones'  => $data['observaciones'] ?? null,
        ]);
    }

    /**
     * Devuelve el historial del flujo interno de un alumno.
     */
    static public function mdlGetHistorialInternoAlumno(int $studentId): array
    {
        $pdo  = Conexion::conectar();
        // Incluye documentos del flujo IJUMICH + cartas de conclusión generadas por el alumno
        $sql  = "
            SELECT id, student_id, tipo, archivo_path, archivo_nombre,
                   archivo_path_firmado, archivo_nombre_firmado,
                   status, comentario_admin, reviewed_at, created_at,
                   NULL AS fecha_inicio, NULL AS fecha_fin
            FROM servicio_social_ijumich
            WHERE student_id = :student_id1
              AND tipo IN ('carta_practicas_interno','carta_aceptacion_servicio','carta_liberacion_interno',
                           'reporte_parcial_1','reporte_parcial_2','reporte_parcial_3','solicitud_registro',
                           'evaluacion_unidad_productiva','evaluacion_global')

            UNION ALL

            SELECT id, student_id,
                   'carta_conclusion_servicio' AS tipo,
                   NULL AS archivo_path, NULL AS archivo_nombre,
                   NULL AS archivo_path_firmado, NULL AS archivo_nombre_firmado,
                   'aprobado' AS status, NULL AS comentario_admin, NULL AS reviewed_at, created_at,
                   DATE_FORMAT(fecha_inicio,'%Y-%m-%d') AS fecha_inicio,
                   DATE_FORMAT(fecha_fin,'%Y-%m-%d') AS fecha_fin
            FROM cartas_conclusion_servicio
            WHERE student_id = :student_id2

            ORDER BY created_at ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':student_id1' => $studentId, ':student_id2' => $studentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Registra la carga de la solicitud de registro IJUMICH (alumno interno).
     */
    static public function mdlCargarSolicitudRegistro(array $data): bool
    {
        $pdo  = Conexion::conectar();
        $sql  = "INSERT INTO servicio_social_ijumich
                    (student_id, tipo, archivo_path, archivo_nombre, observaciones)
                 VALUES
                    (:student_id, 'solicitud_registro', :archivo_path, :archivo_nombre, :observaciones)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':student_id'     => $data['student_id'],
            ':archivo_path'   => $data['archivo_path'],
            ':archivo_nombre' => $data['archivo_nombre'],
            ':observaciones'  => $data['observaciones'] ?? null,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    //  PASO 8 – Evaluación de la Unidad Productiva
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Registra la carga del PDF "Evaluación de la Unidad Productiva" por el alumno.
     */
    static public function mdlCargarEvaluacionUnidadProductiva(array $data): bool
    {
        $pdo  = Conexion::conectar();
        $sql  = "INSERT INTO servicio_social_ijumich
                    (student_id, tipo, archivo_path, archivo_nombre, observaciones)
                 VALUES
                    (:student_id, 'evaluacion_unidad_productiva', :archivo_path, :archivo_nombre, :observaciones)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':student_id'     => $data['student_id'],
            ':archivo_path'   => $data['archivo_path'],
            ':archivo_nombre' => $data['archivo_nombre'],
            ':observaciones'  => $data['observaciones'] ?? null,
        ]);
    }

    //  PASO 9 – Evaluación Global
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Registra la carga del PDF "Evaluación Global" por el alumno.
     */
    static public function mdlCargarEvaluacionGlobal(array $data): bool
    {
        $pdo  = Conexion::conectar();
        $sql  = "INSERT INTO servicio_social_ijumich
                    (student_id, tipo, archivo_path, archivo_nombre, observaciones)
                 VALUES
                    (:student_id, 'evaluacion_global', :archivo_path, :archivo_nombre, :observaciones)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':student_id'     => $data['student_id'],
            ':archivo_path'   => $data['archivo_path'],
            ':archivo_nombre' => $data['archivo_nombre'],
            ':observaciones'  => $data['observaciones'] ?? null,
        ]);
    }

    /**
     * Guarda la ruta del PDF firmado y sellado (devuelto al alumno tras aprobación de solicitud_registro).
     */
    static public function mdlGuardarArchivoFirmado(int $id, string $path, string $nombre): bool
    {
        $pdo  = Conexion::conectar();
        $sql  = "UPDATE servicio_social_ijumich
                 SET archivo_path_firmado = :path, archivo_nombre_firmado = :nombre
                 WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $id, ':path' => $path, ':nombre' => $nombre]);
    }

}
