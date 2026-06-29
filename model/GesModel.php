<?php

class GESModel
{
    static public function mdlSearchStudentGES($matricula)
    {
        $sql = "SELECT a.*, c.OFERTA, c.SUBCLASIFICACION
                FROM [GES_UNIMO].[dbo].[estudiantes] a
                LEFT JOIN [GES_UNIMO].[dbo].[TABLA_CONVERSION] c ON a.CODIGO_GRUPO = c.GRUPO
                WHERE a.matricula = :matricula AND c.SUBCLASIFICACION LIKE '%LICENCIATURA%'";
        $stmt = Conexion::conectarGES()->prepare($sql);
        $stmt->bindParam(":matricula", $matricula, PDO::PARAM_STR);
        $stmt->execute();
        $response = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlSearchAcademic($matricula)
    {
        $sql = "SELECT 
                    a.*, 
                    o.nombre       AS nameOferta, 
                    p.nombre_periodo,
                    f.TipoFamiliar,
                    f.Nombre       AS Familiar_Nombre,
                    f.Apellido1    AS Familiar_Apellido1,
                    f.Apellido2    AS Familiar_Apellido2,
                    f.Telefono     AS Familiar_Telefono,
                    f.Direccion    AS Familiar_Direccion,
                    f.CP           AS Familiar_CP,
                    f.Numero       AS Familiar_Numero,
                    f.Vive         AS Familiar_Vive,
                    f.Email        AS Familiar_Email
                FROM alumno a
                LEFT JOIN inscripcion i 
                    ON a.id = i.idAlumno
                LEFT JOIN nivel n 
                    ON n.id = i.idNivel
                LEFT JOIN oferta o 
                    ON o.id = i.idOferta
                LEFT JOIN periodo p 
                    ON p.id_periodo = o.idPeriodo
                LEFT JOIN alumno_familia af 
                    ON a.id = af.idAlumno
                CROSS APPLY (
                    SELECT TOP 1
                        v.TipoFamiliar,
                        v.Nombre,    v.Apellido1,  v.Apellido2,
                        v.Telefono,  v.Direccion,  v.CP,
                        v.Numero,    v.Vive,       v.Email
                    FROM (VALUES
                        ('Padre',
                        af.nombrePapa,    af.apellido1Papa,  af.apellido2Papa,
                        af.telefonoPapa,  af.direccionPapa,   af.cpPapa,
                        af.numPapa,       af.vivePapa,        af.emailPapa),
                        ('Madre',
                        af.nombreMama,    af.apellido1Mama,  af.apellid2Mama,
                        af.telefonoMama,  af.direccionMama,   af.cpMama,
                        af.numMama,       af.viveMama,        af.emailMama),
                        ('Tutor',
                        af.nombreTutor,   af.apellido1Tutor, af.apellido2Tutor,
                        af.telefonoTutor, af.direccionTutor,  af.cpTutor,
                        af.numTutor,      NULL,               af.emailTutor)
                    ) AS v(
                        TipoFamiliar,
                        Nombre,   Apellido1,  Apellido2,
                        Telefono, Direccion,  CP,
                        Numero,   Vive,       Email
                    )
                    ORDER BY 
                        CASE v.TipoFamiliar 
                            WHEN 'Padre' THEN 1 
                            WHEN 'Madre' THEN 2 
                            WHEN 'Tutor' THEN 3 
                        END
                ) AS f
                WHERE a.matricula = :matricula";
        $stmt = Conexion::conectarSIL()->prepare($sql);
        $stmt->bindParam(":matricula", $matricula, PDO::PARAM_STR);
        $stmt->execute();
        $response = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    static public function mdlDataExtra($matricula)
    {
        $sql = "SELECT * 
        FROM [SCM_MontrerDB].[dbo].[InfoExtra] a
        WHERE a.matricula = :matricula";
        $stmt = Conexion::conectarGES()->prepare($sql);
        $stmt->bindParam(":matricula", $matricula, PDO::PARAM_STR);
        $stmt->execute();
        $response = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }
}
