<?php
/**
 * Capitalización de las variables que se imprimen en los documentos oficiales.
 *
 * Los generadores de PDF venían pasando por mb_strtoupper() casi todo lo que
 * sustituyen en las plantillas —nombre del alumno, licenciatura, organismo,
 * responsable, domicilio…— así que las cartas y la constancia salían GRITADAS EN
 * MAYÚSCULAS. Aquí se normalizan a "Nombre Propio": cada palabra con inicial
 * mayúscula, respetando los conectores del español y las siglas.
 *
 * Vive aparte de membreteCarta.php porque la carta de conclusión de Servicio
 * Social todavía usa su propio diseño (sin membrete) y también la necesita.
 *
 * La usan:
 *   - controller/ajax/generarCartaIjumich.php            (carta presentación SS)
 *   - controller/ajax/generarCartaAceptacionServicio.php (carta aceptación SS)
 *   - controller/ajax/generarCartaConclusionServicio.php (carta conclusión SS)
 *   - controller/practices/cartaPresentacionGenerator.php (carta presentación PP)
 *   - controller/practices/generarConstanciaAcreditacion.php (constancia PP)
 */
declare(strict_types=1);

if (!function_exists('ppCapitalizar')) {
    /**
     * Pasa un texto a "Nombre Propio", venga como venga de la base de datos.
     *
     *   "MARÍA DE LA LUZ PÉREZ"                  → "María de la Luz Pérez"
     *   "AV. LÁZARO CÁRDENAS 1000, C.P. 58260"   → "Av. Lázaro Cárdenas 1000, C.P. 58260"
     *   "COMERCIALIZADORA DEL BAJÍO S.A. DE C.V." → "Comercializadora del Bajío S.A. de C.V."
     *   "COLONIA LOS PINOS"                      → "Colonia Los Pinos"
     *
     * Se dejan tal cual las siglas con punto (C.P., S.A.), los acrónimos de
     * $siglas y las palabras de tres o más letras sin vocales (MGH, SGC):
     * capitalizarlas las desfigura.
     */
    function ppCapitalizar(string $texto): string
    {
        $texto = trim($texto);
        if ($texto === '') {
            return '';
        }

        // Preposiciones y conjunciones: minúscula siempre, salvo al inicio.
        static $enlaces = [
            'de', 'del', 'y', 'e', 'o', 'u', 'en', 'a', 'al', 'con',
            'por', 'para', 'sin', 'sobre', 'entre', 'desde', 'hasta',
        ];
        // Artículos: sólo bajan si vienen detrás de una preposición, para no
        // estropear nombres propios ("Instituto de la Juventud", pero
        // "Colonia Los Pinos").
        static $articulos = ['el', 'la', 'los', 'las'];
        // Acrónimos institucionales frecuentes. Ampliar aquí si aparece alguno
        // nuevo que el sistema esté imprimiendo mal.
        static $siglas = [
            'UNIMO', 'UNIMONTRER', 'IJUMICH', 'IMSS', 'ISSSTE', 'SEP', 'SEE',
            'DIF', 'CFE', 'SAT', 'UNAM', 'IPN', 'UMSNH', 'INE', 'INEGI',
            'ONG', 'PYME', 'SA', 'CV', 'RL',
        ];

        // Se parte conservando los separadores para poder rearmar el texto igual
        // que entró. El punto NO separa: así "C.P." y "AV." llegan enteros.
        $piezas = preg_split('/([^\p{L}\p{N}.]+)/u', $texto, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($piezas === false) {
            return $texto;
        }

        $primera  = true;
        $anterior = '';

        foreach ($piezas as $i => $pieza) {
            // Separadores y puntuación suelta: se copian sin tocar.
            if ($pieza === '' || !preg_match('/[\p{L}\p{N}]/u', $pieza)) {
                continue;
            }

            $nucleo = trim($pieza, ".,;:()[]«»\"'");
            if ($nucleo === '') {
                continue;
            }
            $bajo = mb_strtolower($nucleo, 'UTF-8');

            $intacta = preg_match('/^(?:\p{L}\.){2,}$/u', $pieza) === 1
                || in_array(mb_strtoupper($nucleo, 'UTF-8'), $siglas, true)
                || (mb_strlen($nucleo, 'UTF-8') >= 3 && !preg_match('/[aeiouáéíóúü]/u', $bajo));

            if (!$intacta) {
                $esEnlace = in_array($bajo, $enlaces, true)
                    || (in_array($bajo, $articulos, true) && in_array($anterior, $enlaces, true));

                $nuevo = (!$primera && $esEnlace)
                    ? $bajo
                    : mb_strtoupper(mb_substr($bajo, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($bajo, 1, null, 'UTF-8');

                // Se reinserta respetando la puntuación que rodeaba la palabra.
                $pos = (int) mb_strpos($pieza, $nucleo, 0, 'UTF-8');
                $piezas[$i] = mb_substr($pieza, 0, $pos, 'UTF-8')
                    . $nuevo
                    . mb_substr($pieza, $pos + mb_strlen($nucleo, 'UTF-8'), null, 'UTF-8');
            }

            $primera  = false;
            $anterior = $bajo;
        }

        return implode('', $piezas);
    }
}
