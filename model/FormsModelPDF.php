<?php 
require_once __DIR__ . '/../vendor/autoload.php';

use setasign\Fpdi\Fpdi;

class FormsModelPDF
{

    static public function mdlEndSocialService($student, $degree)
    { // Cargar el PDF original
        $pdf = new Fpdi();

        $numeroATexto = numeroATexto($student['grado']);
        $gradoAcademico = ($student['type_lic'] == 'cuatrimestral') ? $numeroATexto . ' cuatrimestre' : $numeroATexto . ' sementre';

        // Establecer márgenes más pequeños (0 para que no haya margen)
        $pdf->SetMargins(5, 5, 5); // Izquierdo, Superior, Derecho
        require_once __DIR__ . '/../vendor/autoload.php';

        // Cargar el archivo PDF original
        $pageCount = $pdf->setSourceFile(__DIR__ . '/../view/assets/documents/Formato_Solicitud-Registro-1.pdf');
        $templateId = $pdf->importPage(1);
        $size = $pdf->getTemplateSize($templateId);

        // Añadir una página con el tamaño exacto del contenido
        $pdf->AddPage($size['orientation'], array($size['width'], $size['height']));

        // Usar la plantilla del PDF original con las dimensiones correctas
        $pdf->useTemplate($templateId, 0, 0, $size['width'], $size['height']);

        // Configurar la fuente para el texto
        $pdf->SetFont('Helvetica', '', 7);

        // Llenar campos de texto con conversión a ISO-8859-1 para evitar problemas de codificación
        $pdf->SetXY(25, 43.5); // Coordenadas aproximadas del campo "Apellido Paterno"
        $pdf->Write(0, iconv('UTF-8', 'ISO-8859-1', mb_strtoupper($student['lastname'])));

        $pdf->SetXY(72, 43.5); // Coordenadas aproximadas del campo "Apellido Materno"
        $pdf->Write(0, iconv('UTF-8', 'ISO-8859-1', mb_strtoupper($student['lastnameMom'])));

        $pdf->SetXY(113, 43.5); // Coordenadas aproximadas del campo "Nombre"
        $pdf->Write(0, iconv('UTF-8', 'ISO-8859-1', mb_strtoupper($student['firstname'])));

        $pdf->SetXY(25, 54); // Coordenadas aproximadas del campo "Calle y número"
        $pdf->Write(0, iconv('UTF-8', 'ISO-8859-1', mb_strtoupper($student['street'] . ' ' . $student['nInt'] . ' ' . $student['nExt'])));

        $pdf->SetXY(72, 54); // Coordenadas aproximadas del campo "Colonia"
        $pdf->Write(0, iconv('UTF-8', 'ISO-8859-1', mb_strtoupper($student['colony'])));

        $pdf->SetXY(113, 54); // Coordenadas aproximadas del campo "Población"
        $pdf->Write(0, iconv('UTF-8', 'ISO-8859-1', mb_strtoupper('Michoacán')));

        $pdf->SetXY(25, 64.5); // Coordenadas aproximadas del campo "Teléfono"
        $pdf->Write(0, $student['phone']);

        $pdf->SetXY(95, 64.5); // Coordenadas aproximadas del campo "correo"
        $pdf->Write(0, $student['email']);

        $pdf->SetXY(25, 71.8); // Coordenadas aproximadas del campo "Carrera"
        $pdf->Write(0, mb_strtoupper($degree['nameDegree']));

        $pdf->SetXY(166, 71.8); // Coordenadas aproximadas del campo "Año o semestre concluido"
        $pdf->Write(0, mb_strtoupper($gradoAcademico));

        $pdf->SetXY(54, 79); // Coordenadas aproximadas del campo "Nombre de la institución"
        $pdf->Write(0, 'UNIVERSIDAD MONTRER');

        $pdf->SetXY(174, 64.5); // Coordenadas aproximadas del campo "Dia"
        $pdf->Write(0, $student['dayBirthday']);

        $pdf->SetXY(184, 64.5); // Coordenadas aproximadas del campo "Més"
        $pdf->Write(0, $student['monthBirthday']);

        $pdf->SetXY(193, 64.5); // Coordenadas aproximadas del campo "Año"
        $pdf->Write(0, $student['yearBirthday']);

        $pdf->SetXY(25, 90); // Coordenadas aproximadas del campo "Datos del programa (NOMBRE)"
        $pdf->Write(0, 'PROGRAMA GENERAL DE SERVICIO SOCIAL DE UNIVERSIDAD MONTRER');

        $pdf->SetXY(58, 101); // Coordenadas aproximadas del campo "Datos del programa (ACTIVIDADES)"
        $pdf->Write(0, 'APOYO EN ACTIVIDADES ACADEMICAS');

        $pdf->SetXY(157, 112.5); // Coordenadas aproximadas del campo "Datos del programa (HORARIO)"
        $pdf->Write(0, '8:00 A 12:00 HRS.');

        $fechaInicio = new DateTime();

        // Clonar la fecha de inicio para obtener la fecha de término
        $fechaTermino = clone $fechaInicio;
        if ($degree['minPoints'] == 480) {
            $fechaTermino->modify('+6 months');
        } else {
            $fechaTermino->modify('+12 months');
        }

        // Escribir la fecha de inicio
        $pdf->SetXY(58, 116); // Coordenadas aproximadas del campo "Datos del programa (DÍA INICIO)"
        $pdf->Write(0, $fechaInicio->format('d'));

        $pdf->SetXY(69, 116); // Coordenadas aproximadas del campo "Datos del programa (MES INICIO)"
        $pdf->Write(0, $fechaInicio->format('m'));

        $pdf->SetXY(78, 116); // Coordenadas aproximadas del campo "Datos del programa (AÑO INICIO)"
        $pdf->Write(0, $fechaInicio->format('Y'));

        // Escribir la fecha de término
        $pdf->SetXY(111, 116); // Coordenadas aproximadas del campo "Datos del programa (DÍA TERMINO)"
        $pdf->Write(0, $fechaTermino->format('d'));

        $pdf->SetXY(121, 116); // Coordenadas aproximadas del campo "Datos del programa (MES TERMINO)"
        $pdf->Write(0, $fechaTermino->format('m'));

        $pdf->SetXY(130, 116); // Coordenadas aproximadas del campo "Datos del programa (AÑO TERMINO)"
        $pdf->Write(0, $fechaTermino->format('Y'));

        $pdf->SetXY(50, 124.5); // Coordenadas aproximadas del campo "Datos del programa (HORAS)"
        $pdf->Write(0, '480');

        $pdf->SetXY(145, 124.5); // Coordenadas aproximadas del campo "Datos del programa (HORAS)"
        $pdf->Write(0, 'UNIVERSIDAD MONTRER');

        $pdf->SetXY(52, 130); // Coordenadas aproximadas del campo "Datos del programa (HORAS)"
        $pdf->Write(0, 'UNIVERSIDAD MONTRER');

        $pdf->SetXY(38, 135.3); // Coordenadas aproximadas del campo "Datos del programa (HORAS)"
        $pdf->Write(0, 'SERVICIO SOCIAL');

        $pdf->SetXY(54, 140.3); // Coordenadas aproximadas del campo "Datos del programa (HORAS)"
        $pdf->Write(0, iconv('UTF-8', 'ISO-8859-1', 'AV. LAZARO CARDENAS 1760'));

        $pdf->SetXY(98, 140.3); // Coordenadas aproximadas del campo "Datos del programa (HORAS)"
        $pdf->Write(0, iconv('UTF-8', 'ISO-8859-1', 'CHAPULTEPEC SUR'));

        $pdf->SetXY(155, 140.3); // Coordenadas aproximadas del campo "Datos del programa (HORAS)"
        $pdf->Write(0, iconv('UTF-8', 'ISO-8859-1', 'MORELIA, MICHOACÁN'));

        $pdf->SetXY(68, 151.3); // Coordenadas aproximadas del campo "Datos del programa (HORAS)"
        $pdf->Write(0, iconv('UTF-8', 'ISO-8859-1', 'OSCAR LOPEZ GARCIA'));

        $pdf->SetXY(162.5, 179); // Coordenadas aproximadas del campo "Datos del programa (HORAS)"
        $pdf->Write(0, iconv('UTF-8', 'ISO-8859-1', 'OSCAR LOPEZ GARCIA'));

        // Obtener la longitud del texto
        $texto = mb_strtoupper($student['firstname'] . ' ' . $student['lastname'] . ' ' . $student['lastnameMom'] . ' ');
        $textoConvertido = iconv('UTF-8', 'ISO-8859-1', $texto);

        // Calcular el ancho del texto en el PDF
        $anchoTexto = $pdf->GetStringWidth($textoConvertido);

        // Ancho de la página o del área donde deseas centrar
        $anchoPagina = 210; // Ancho de la página A4 en mm, por ejemplo
        $margenIzquierdo = 0; // Si tienes márgenes específicos

        // Calcular la posición X para centrar el texto
        $posicionX = ($anchoPagina - $anchoTexto) / 2 + $margenIzquierdo;

        // Escribir el texto centrado
        $pdf->SetXY($posicionX, 179); // Coordenadas Y especificadas
        $pdf->Write(0, $textoConvertido);


        // Configurar la fuente para el texto
        $pdf->SetFont('Helvetica', '', 5);

        $pdf->SetXY(23, 96); // Coordenadas aproximadas del campo "Datos del programa (OBJETIVO)"
        $pdf->Write(0, iconv('UTF-8', 'ISO-8859-1', 'CONTRIBUIR EN LA FORMACION PROFESIONAL DE LOS ESTUDIANTES DEL ESTADO DE MICHOACAN A TRAVES DE LA CREACION DE ESPACIOS QUE LES PERMITAN INTEGRARSE A UN AMBIENTE DE TRABAJO'));

        // Usar la fuente ZapfDingbats para insertar una casilla de verificación
        $pdf->SetFont('ZapfDingbats', '', 8);

        $pdf->SetXY(50, 157);
        $pdf->Write(0, '4');

        if ($student['gender'] == 2) {
            // Colocar la segunda casilla de verificación en una posición ajustada
            $pdf->SetXY(199, 44); // Coordenadas ajustadas para la casilla de verificación para "Sexo: F"
            $pdf->Write(0, '4'); // '4' en ZapfDingbats es una marca de verificación
        } else {
            // Colocar la primera casilla de verificación en la posición deseada
            $pdf->SetXY(190, 44); // Coordenadas aproximadas de la casilla de verificación para "Sexo: M"
            $pdf->Write(0, '4'); // '4' en ZapfDingbats es una marca de verificación
        }

        // Puedes agregar más campos de esta manera, ajustando las coordenadas
        // Guarda el nuevo archivo PDF
        $pdf->Output('F', __DIR__ . '/../view/assets/documents/output/' . $texto . '.pdf');
        return $texto . '.pdf'; // Devuelve el nombre del archivo PDF generado

    }

    static public function getAceptationCard($student, $degree)
    {

        $month = 6;
        $fechaInicio = new DateTime();
        $fechaTermino = clone $fechaInicio;

        if ($degree['minPoints'] == 480) {
            $month = 6;
            $fechaTermino->modify('+6 months');
        } else {
            $month = 12;
            $fechaTermino->modify('+12 months');
        }

        $numeroATexto = numeroATexto($student['grado']);
        $gradoAcademico = ($student['type_lic'] == 'cuatrimestral') ? $numeroATexto . ' cuatrimestre' : $numeroATexto . ' sementre';

        // Configurar la localización para fechas en español
        setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'Spanish_Spain');

        // Cargar el PDF original
        $pdf = new Fpdi();

        // Establecer márgenes más pequeños (0 para que no haya margen)
        $pdf->SetMargins(45, 5, 20); // Izquierdo, Superior, Derecho

        // Cargar el archivo PDF original
        $pageCount = $pdf->setSourceFile(__DIR__ . '/../view/assets/documents/Carta_aceptacion.pdf');
        $templateId = $pdf->importPage(1);
        $size = $pdf->getTemplateSize($templateId);

        // Añadir una página con el tamaño exacto del contenido
        $pdf->AddPage($size['orientation'], array($size['width'], $size['height']));

        // Usar la plantilla del PDF original con las dimensiones correctas
        $pdf->useTemplate($templateId, 0, 0, $size['width'], $size['height']);

        // Configurar la fuente para el texto
        $pdf->SetFont('Helvetica', '', 12);

        // Obtener la fecha actual en español
        $fechaEmision = strftime('%d de %B de %Y'); // Ejemplo: "22 de agosto de 2024"

        // Escribir la fecha
        $pdf->SetXY(10, 25);
        $pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-1', "Morelia, Mich., a $fechaEmision."), 0, 1, 'R');

        // Escribir "Asunto:"
        $pdf->SetXY(99, 40);
        $pdf->Write(10, iconv('UTF-8', 'ISO-8859-1', 'Asunto: '));
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Write(10, iconv('UTF-8', 'ISO-8859-1', 'Carta de aceptación de Servicio Social.'));


        // Escribir DSS-CASS
        $pdf->SetXY(10, 50);
        $pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-1', "DSS-CASS-004-2024 DD"), 0, 1, 'R');

        // Escribir los datos del destinatario
        $pdf->SetXY(45, 68);
        $pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-1', "Lic. Luz Selene Archundia Sánchez"), 0, 1, 'L');
        $pdf->SetXY(45, 73);
        $pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-1', "Subdirectora de Servicio Social y Pasantes"), 0, 1, 'L');
        $pdf->SetXY(45, 78);
        $pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-1', "Instituto de la Juventud Michoacana"), 0, 1, 'L');

        // Escribir "Presente"
        $pdf->SetXY(45, 90);
        $pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-1', "Presente"), 0, 1, 'L');

        // Restablecer la fuente normal
        $pdf->SetFont('Helvetica', '', 12);
        // Escribir el cuerpo de la carta
        $texto = mb_strtoupper($student['firstname'] . ' ' . $student['lastname'] . ' ' . $student['lastnameMom'] . ' ');

        $pdf->SetXY(45, 105);
        $pdf->Write(7, iconv('UTF-8', 'ISO-8859-1', "En relación a la solicitud del alumno "));
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Write(7, iconv('UTF-8', 'ISO-8859-1', $texto));
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Write(7, iconv('UTF-8', 'ISO-8859-1', ", quien concluyó el "));
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Write(7, iconv('UTF-8', 'ISO-8859-1', mb_strtoupper($gradoAcademico)));
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Write(7, iconv('UTF-8', 'ISO-8859-1', " de la LICENCIATURA EN "));
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Write(7, iconv('UTF-8', 'ISO-8859-1', mb_strtoupper($degree['nameDegree'])));
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Write(7, iconv('UTF-8', 'ISO-8859-1', " de "));
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Write(7, iconv('UTF-8', 'ISO-8859-1', "UNIVERSIDAD MONTRER"));
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Write(7, iconv('UTF-8', 'ISO-8859-1', ", con número de matrícula "));
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Write(7, iconv('UTF-8', 'ISO-8859-1', $student['matricula']));
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Write(7, iconv('UTF-8', 'ISO-8859-1', ", me permito informarle que ha sido aceptado en este organismo receptor denominado: "));
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Write(7, iconv('UTF-8', 'ISO-8859-1', "UNIVERSIDAD MONTRER"));
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Write(7, iconv('UTF-8', 'ISO-8859-1', ", para realizar el "));
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Write(7, iconv('UTF-8', 'ISO-8859-1', "SERVICIO SOCIAL"));
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Write(7, iconv('UTF-8', 'ISO-8859-1', " y cubrir un total de "));
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Write(7, iconv('UTF-8', 'ISO-8859-1', $degree['minPoints']));
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Write(7, iconv('UTF-8', 'ISO-8859-1', " HORAS en un periodo de "));
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Write(7, iconv('UTF-8', 'ISO-8859-1', $month . ' MESES'));
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Write(7, iconv('UTF-8', 'ISO-8859-1', " comprendido del "));
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Write(7, iconv('UTF-8', 'ISO-8859-1', $fechaInicio->format('d') . "/" . $fechaInicio->format('m') . "/" . $fechaInicio->format('y')));
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Write(7, iconv('UTF-8', 'ISO-8859-1', " al "));
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Write(7, iconv('UTF-8', 'ISO-8859-1', $fechaTermino->format('d') . "/" . $fechaTermino->format('m') . "/" . $fechaTermino->format('y')));
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Write(7, iconv('UTF-8', 'ISO-8859-1', ".

Sin otro asunto en particular, agradezco de antemano la atención que se sirva brindar a nuestros alumnos, enviándole un cordial saludo."));

        // Guardar el nuevo archivo PDF
        $filename = 'Carta_de_aceptacion_' . $texto . '.pdf';
        $pdf->Output('F', __DIR__ . '/../view/assets/documents/output/' . $filename);
        return $filename;
    }
}

function numeroATexto($numero)
{
    $numerosEnTexto = [
        1 => 'Primero',
        2 => 'Segundo',
        3 => 'Tercero',
        4 => 'Cuarto',
        5 => 'Quinto',
        6 => 'Sexto',
        7 => 'Séptimo',
        8 => 'Octavo',
        9 => 'Noveno',
        10 => 'Décimo',
        11 => 'Undécimo',
        12 => 'Duodécimo'
    ];

    return isset($numerosEnTexto[$numero]) ? $numerosEnTexto[$numero] : $numero;
}
