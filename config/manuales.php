<?php
/**
 * Manuales de la plataforma (PDF en docs/manuales/; la carpeta está bloqueada en web.config y .htaccess).
 * Solo se entregan con sesión, desde controller/manuales.php: cada perfil abre los suyos y la
 * Coordinación (rol admin) todos. Cómo se regeneran los PDF: docs/manuales/README.md.
 *
 * 'roles' usa el rol de sesión ($_SESSION['user']['role']); admin no se lista porque ve todos.
 */
return [
    'empresa' => [
        'archivo'     => 'practicas/manual_empresa.pdf',
        'titulo'      => 'Manual de la Empresa',
        'descripcion' => 'Registro, convenio, vacantes, selección del practicante, asistencias, reportes, incidencias y capacitaciones.',
        'roles'       => ['organismo_externo'],
    ],
    'alumno' => [
        'archivo'     => 'practicas/manual_alumno.pdf',
        'titulo'      => 'Manual del Alumno',
        'descripcion' => 'Inscripción, prácticas en una empresa o en un área interna de la Universidad, asistencias, reportes y constancia.',
        'roles'       => ['alumno_practicas'],
    ],
    'encargado' => [
        'archivo'     => 'practicas/manual_encargado_area.pdf',
        'titulo'      => 'Manual del Encargado de Área Interna',
        'descripcion' => 'Postulaciones, asistencias, seguimiento, reportes y evaluaciones de los alumnos de su área.',
        'roles'       => ['teacher'],
    ],
    'coordinacion' => [
        'archivo'     => 'practicas/manual_coordinacion.pdf',
        'titulo'      => 'Manual de la Coordinación',
        'descripcion' => 'Tablero, revisión de empresas, vacantes, alumnos y reportes, áreas internas, incidencias y capacitaciones.',
        'roles'       => [],
    ],
    'completo' => [
        'archivo'     => 'practicas/tutorial_completo.pdf',
        'titulo'      => 'Tutorial completo de Prácticas Profesionales',
        'descripcion' => 'Todo el proceso, paso a paso, para la empresa, el alumno, el encargado de área interna y la Coordinación.',
        'roles'       => [],
    ],
];
