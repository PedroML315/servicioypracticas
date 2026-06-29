<?php
/**
 * Script de simulación para pruebas del módulo de Evaluación Integral.
 * Modo de uso (desde CLI o navegador):
 *
 * Fase 0 (Reiniciar de cero al alumno):
 * php test_evaluacion_simulation.php fase=0
 *
 * Fase 1 (Simular 180h):
 * php test_evaluacion_simulation.php fase=1
 *
 * Fase 2 (Simular 360h):
 * php test_evaluacion_simulation.php fase=2
 */

require_once __DIR__ . '/../model/conection.php';
require_once __DIR__ . '/../model/PracticasModel.php';

$fase = $_GET['fase'] ?? null;
if (!$fase) {
    foreach ($argv as $arg) {
        if (strpos($arg, 'fase=') === 0) {
            $fase = substr($arg, 5);
        }
    }
}

if ($fase === null || !in_array($fase, ['0', '1', '2'], true)) {
    die("Debes especificar fase=0, fase=1 o fase=2\n");
}

$idStudent = 22; // Alumno de prueba fijo

echo "<h2>Simulación Fase $fase para Alumno ID $idStudent</h2>\n";

try {
    $conn = Conexion::conectar();
    
    // Buscar datos del alumno
    $stmt = $conn->prepare("SELECT id, nombre_completo FROM students_practicas WHERE id = ?");
    $stmt->execute([$idStudent]);
    $student = $stmt->fetch();
    if (!$student) die("Error: Alumno $idStudent no existe.\n");
    echo "Alumno: {$student['nombre_completo']}<br>\n";

    if ($fase == '0') {
        // Reiniciar de cero al alumno: borrar todo su historial de prácticas
        // para que únicamente pueda seleccionar solicitudes disponibles.
        echo "Reiniciando de cero al alumno (asistencias, reportes, evaluaciones y prácticas)...<br>\n";
        $conn->exec("DELETE FROM asistencias_practicas WHERE idStudent = $idStudent");
        $conn->exec("DELETE FROM reporte_parcial_practicas WHERE idStudent = $idStudent");
        $conn->exec("DELETE FROM reporte_final_practicas WHERE idStudent = $idStudent");
        $conn->exec("DELETE FROM evaluacion_integral_respuestas WHERE idEvaluacion IN (SELECT id FROM evaluacion_integral_practicas WHERE idStudent = $idStudent)");
        $conn->exec("DELETE FROM evaluacion_integral_practicas WHERE idStudent = $idStudent");
        $conn->exec("DELETE FROM students_in_practices WHERE idStudent = $idStudent");

        echo "<h3 style='color:green;'>Fase 0 Completada.</h3>";
        echo "<ul>
                <li>El alumno $idStudent quedó reiniciado de cero, sin práctica asignada.</li>
                <li>Entra como el alumno y verifica que solo puede seleccionar solicitudes disponibles.</li>
              </ul>";

    } else if ($fase == '1') {
        // Limpiar datos previos de este alumno para empezar limpio
        echo "Limpiando asistencias, reportes y evaluaciones previas...<br>\n";
        $conn->exec("DELETE FROM asistencias_practicas WHERE idStudent = $idStudent");
        $conn->exec("DELETE FROM reporte_parcial_practicas WHERE idStudent = $idStudent");
        $conn->exec("DELETE FROM reporte_final_practicas WHERE idStudent = $idStudent");
        $conn->exec("DELETE FROM evaluacion_integral_respuestas WHERE idEvaluacion IN (SELECT id FROM evaluacion_integral_practicas WHERE idStudent = $idStudent)");
        $conn->exec("DELETE FROM evaluacion_integral_practicas WHERE idStudent = $idStudent");
        $conn->exec("DELETE FROM students_in_practices WHERE idStudent = $idStudent");

        // Asignar una práctica de empresa
        $stmt = $conn->query("SELECT id, organismo_externo_id FROM solicitudes_practicantes WHERE aceptado = 1 AND activo = 1 LIMIT 1");
        $rowPractica = $stmt->fetch();
        if (!$rowPractica) die("Error: No hay prácticas disponibles en solicitudes_practicantes.\n");
        $idPractica = $rowPractica['id'];
        $idOrganismo = $rowPractica['organismo_externo_id'] ?: 0;

        echo "Asignando a práctica ID $idPractica...<br>\n";
        $stmt = $conn->prepare("INSERT INTO students_in_practices (idPractica, idStudent, isAcepted) VALUES (?, ?, 1)");
        $stmt->execute([$idPractica, $idStudent]);

        // Generar 180 horas (45 asistencias de 4 horas)
        echo "Generando 45 asistencias de 4 horas (180h total)...<br>\n";
        $stmt = $conn->prepare(
            "INSERT INTO asistencias_practicas 
            (idOrganismo, idPractica, idStudent, fecha, hora_entrada, hora_salida, actividad, status, horas_validadas) 
            VALUES (?, ?, ?, ?, '08:00:00', '12:00:00', 'Actividades de simulación', 'aprobado', 4)"
        );
        
        $fecha = new DateTime('2024-01-01');
        for ($i = 0; $i < 45; $i++) {
            // Evitar fines de semana
            while ($fecha->format('N') >= 6) {
                $fecha->modify('+1 day');
            }
            $stmt->execute([$idOrganismo, $idPractica, $idStudent, $fecha->format('Y-m-d')]);
            $fecha->modify('+1 day');
        }

        echo "<h3 style='color:green;'>Fase 1 Completada.</h3>";
        echo "<ul>
                <li>El alumno $idStudent tiene 180 horas exactas.</li>
                <li>Entra como el alumno y verifica que aparece el bloqueo de Reporte Parcial y Evaluación Intermedia.</li>
                <li>Luego entra como el organismo de la práctica $idPractica para probar la Evaluación Empresa.</li>
              </ul>";

    } else if ($fase == '2') {
        // Verificar que ya está en una práctica
        $stmt = $conn->prepare(
            "SELECT sip.idPractica, sp.organismo_externo_id 
             FROM students_in_practices sip
             JOIN solicitudes_practicantes sp ON sip.idPractica = sp.id
             WHERE sip.idStudent = ? AND sip.isAcepted = 1"
        );
        $stmt->execute([$idStudent]);
        $rowPractica = $stmt->fetch();
        if (!$rowPractica) die("Error: El alumno no tiene práctica asignada. Ejecuta la fase 1 primero.\n");
        $idPractica = $rowPractica['idPractica'];
        $idOrganismo = $rowPractica['organismo_externo_id'] ?: 0;

        // Generar 180 horas adicionales (total 360h)
        echo "Generando 45 asistencias adicionales de 4 horas (para alcanzar 360h)...<br>\n";
        $stmt = $conn->prepare(
            "INSERT INTO asistencias_practicas 
            (idOrganismo, idPractica, idStudent, fecha, hora_entrada, hora_salida, actividad, status, horas_validadas) 
            VALUES (?, ?, ?, ?, '08:00:00', '12:00:00', 'Actividades Fase 2', 'aprobado', 4)"
        );
        
        // Empezar desde abril para fase 2
        $fecha = new DateTime('2024-04-01');
        for ($i = 0; $i < 45; $i++) {
            while ($fecha->format('N') >= 6) {
                $fecha->modify('+1 day');
            }
            $stmt->execute([$idOrganismo, $idPractica, $idStudent, $fecha->format('Y-m-d')]);
            $fecha->modify('+1 day');
        }

        echo "<h3 style='color:green;'>Fase 2 Completada.</h3>";
        echo "<ul>
                <li>El alumno $idStudent ha alcanzado las 360 horas.</li>
                <li>Verifica que aparece el bloqueo del Reporte Final y Evaluación Final.</li>
              </ul>";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
