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
 *
 * Aquí vive también ppAlumnoGenero(), que resuelve la variable {{alumnoGenero}}
 * de las plantillas.
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

if (!function_exists('ppSustituirVars')) {
    /**
     * Sustituye las {{variables}} del cuerpo, aguantando el marcado que mete el
     * editor.
     *
     * Un str_replace('{{alumnoGenero}}') sólo acierta si la variable quedó
     * limpia en el HTML, y casi nunca queda: al pegar el nombre desde el panel
     * de variables, Quill suele envolver el texto interior —
     * `{{<span style="color:#e67e22">alumnoGenero</span>}}`— y entonces el
     * literal ya no existe. En el PDF se veía la variable en crudo, con las
     * llaves de un color y el nombre de otro.
     *
     * Aquí se busca cualquier {{…}}, se limpia el nombre para localizarlo en
     * $vars y el valor se devuelve DENTRO de las mismas etiquetas, para no
     * desbalancear el HTML que recibe Dompdf y conservar el formato que el
     * admin le haya dado (negritas, color, etc.).
     *
     * @param array<string,string> $vars Claves con llaves: ['{{folio}}' => 'DPP-1-2026'].
     */
    function ppSustituirVars(string $html, array $vars): string
    {
        if ($html === '' || $vars === []) {
            return $html;
        }

        $resultado = preg_replace_callback(
            '/\{\{(.*?)\}\}/us',
            static function (array $m) use ($vars): string {
                // Nombre sin etiquetas, entidades, espacios ni caracteres
                // invisibles (el pegado arrastra &nbsp; y marcas de ancho cero).
                $nombre = html_entity_decode(strip_tags($m[1]), ENT_QUOTES, 'UTF-8');
                $nombre = preg_replace('/[\s\x{00A0}\x{200B}-\x{200D}\x{FEFF}]+/u', '', $nombre) ?? '';

                $clave = '{{' . $nombre . '}}';
                if (!array_key_exists($clave, $vars)) {
                    return $m[0]; // No es una variable nuestra: se deja tal cual.
                }
                $valor = $vars[$clave];

                if (!str_contains($m[1], '<')) {
                    return $valor;
                }

                // Había marcado dentro: se conservan las etiquetas en su sitio y
                // el valor ocupa el lugar del texto.
                $piezas = preg_split('/(<[^>]*>)/u', $m[1], -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
                $salida = '';
                $colocado = false;
                foreach ($piezas as $pieza) {
                    if ($pieza === '') {
                        continue;
                    }
                    if ($pieza[0] === '<') {
                        $salida .= $pieza;
                    } elseif (!$colocado) {
                        $salida .= $valor;
                        $colocado = true;
                    }
                }

                return $colocado ? $salida : $salida . $valor;
            },
            $html
        );

        return $resultado ?? $html;
    }
}

if (!function_exists('ppAlumnoGenero')) {
    /**
     * Sustantivo del alumno para la variable {{alumnoGenero}}: "alumno" o "alumna".
     *
     * Es el gemelo de {{genero}} ("el"/"la"), para poder redactar "…que la
     * alumna cumplió…" sin depender de una sola forma. No se pide aparte a los
     * generadores: se deduce del género que ya calculan, así las dos variables
     * no pueden quedar desincronizadas.
     *
     * Acepta lo que cada documento tenga a mano —el artículo ("la"), el valor
     * de la base ("Femenino") o la frase completa de la constancia
     * ("la alumna")— y ante la duda devuelve el masculino.
     */
    function ppAlumnoGenero(string $genero): string
    {
        $g = mb_strtolower(trim($genero), 'UTF-8');
        $esFemenino = str_contains($g, 'alumna')
            || in_array($g, ['la', 'f', 'femenino', 'mujer'], true);

        return $esFemenino ? 'alumna' : 'alumno';
    }
}
