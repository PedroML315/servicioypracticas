<?php

require_once __DIR__ . '/../config/Crypto.php';

/**
 * MailBulkModel — Gestor de envío masivo de correos (módulo aislado).
 * No toca ninguna tabla del sistema existente (email_queue, email_templates, etc.).
 */
class MailBulkModel
{
    private static $pdo = null;

    public const MAX_ATTEMPTS = 3;
    public const BATCH_SIZE = 6;
    public const STALE_RESERVATION_MINUTES = 2;

    private static function db(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }
        require_once __DIR__ . '/conection.php';
        $pdo = Conexion::conectar();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        self::$pdo = $pdo;
        return $pdo;
    }

    private static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    /**
     * ¿El HTML es un documento completo (lo típico de una plantilla profesional
     * pegada en el modo “Código HTML”) en vez de un fragmento del editor visual?
     */
    public static function isFullHtmlDocument(string $html): bool
    {
        return stripos($html, '<html') !== false || stripos($html, '<!doctype') !== false;
    }

    /**
     * Sanea el HTML del editor (nunca confiar en lo que llega del navegador,
     * aunque venga de CKEditor5) antes de guardarlo o enviarlo.
     *
     * Dos caminos, porque el módulo acepta dos formas de escribir el correo:
     *  · Fragmento (editor visual o HTML suelto) → se purifica y ya.
     *  · Documento completo (modo “Código HTML”) → HTMLPurifier tiraría
     *    <html>/<head>/<style>, así que primero se separan las hojas de estilo
     *    y el cuerpo, se purifica cada parte por su lado y se rearma el documento.
     */
    public static function sanitizeHtml(string $html): string
    {
        if (!self::isFullHtmlDocument($html)) {
            return self::purifyFragment($html);
        }

        // 1) Hojas de estilo del <head> (o de donde estén).
        $css = '';
        if (preg_match_all('#<style\b[^>]*>(.*?)</style>#is', $html, $m)) {
            $css = implode("\n", $m[1]);
        }

        // 2) Atributos visibles de <body> (el color de fondo es muy notorio en correo).
        $bodyAttrs = '';
        if (preg_match('#<body\b([^>]*)>#i', $html, $m)) {
            $bodyAttrs = self::sanitizeBodyAttributes($m[1]);
        }

        // 3) Contenido del cuerpo.
        if (preg_match('#<body\b[^>]*>(.*)</body>#is', $html, $m)) {
            $body = $m[1];
        } else {
            $body = preg_replace('#<head\b.*?</head>#is', '', $html);
            $body = preg_replace('#<!doctype[^>]*>#i', '', (string) $body);
            $body = preg_replace('#</?(?:html|body)[^>]*>#i', '', (string) $body);
        }
        $body = preg_replace('#<style\b[^>]*>.*?</style>#is', '', (string) $body);

        $cleanBody = self::purifyFragment((string) $body);
        $cleanCss = self::sanitizeCss($css);
        $styleTag = $cleanCss !== '' ? "\n<style>\n{$cleanCss}\n</style>" : '';

        return "<!DOCTYPE html>\n<html>\n<head>\n<meta charset=\"UTF-8\">\n"
            . "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">{$styleTag}\n"
            . "</head>\n<body{$bodyAttrs}>\n{$cleanBody}\n</body>\n</html>";
    }

    /**
     * Purifica un fragmento conservando los comentarios condicionales de Outlook
     * (`<!--[if mso]> … <![endif]-->`), que son la única forma de darle a Outlook una
     * alternativa cuando ignora algo —típicamente las imágenes de fondo—.
     *
     * No basta con dejarlos pasar: su contenido va DENTRO del comentario, así que
     * HTMLPurifier no lo revisaría y se podría colar cualquier cosa. Por eso se apartan
     * antes, se purifica su interior por separado y se vuelven a armar al final. Los
     * condicionales que no son de Outlook (IE) se descartan: en correo no sirven y sí
     * serían una vía para esconder marcado.
     */
    private static function purifyFragment(string $html): string
    {
        $blocks = [];
        $html = (string) preg_replace_callback(
            '#<!--\[if\s+([^\]<>]{1,40})\]>(.*?)<!\[endif\]-->#is',
            function (array $m) use (&$blocks): string {
                $cond = trim($m[1]);
                if (!preg_match('#^(?:(?:gte|lte|lt|gt)\s+)?!?\s*mso(?:\s+\d+)?$#i', $cond)) {
                    return '';
                }
                $i = count($blocks);
                $blocks[$i] = ['cond' => $cond, 'inner' => $m[2]];
                return "@@MBCOND{$i}@@";
            },
            $html
        );

        $clean = self::runPurifier($html);

        foreach ($blocks as $i => $b) {
            $inner = self::runPurifier($b['inner']);
            $clean = str_replace("@@MBCOND{$i}@@", "<!--[if {$b['cond']}]>{$inner}<![endif]-->", $clean);
        }

        return $clean;
    }

    /** Pasada de HTMLPurifier con el vocabulario HTML/CSS que usan los correos. */
    private static function runPurifier(string $html): string
    {
        require_once __DIR__ . '/../vendor/autoload.php';

        $cacheDir = __DIR__ . '/../storage/htmlpurifier_cache';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0775, true);
        }

        $config = HTMLPurifier_Config::createDefault();
        // Ojo: los elementos de bloque llevan [style] porque la alineación del editor
        // viaja como style="text-align:…". Sin eso, el administrador vería el texto
        // centrado en el editor y en la vista previa, pero el correo saldría sin centrar.
        //
        // Los atributos “antiguos” (align, valign, bgcolor, cellpadding, width en las
        // celdas…) no son un descuido: el HTML de correo se sigue maquetando con tablas
        // porque Outlook ignora buena parte del CSS moderno, y las plantillas
        // profesionales que se peguen en el modo “Código HTML” los traen. Sin ellos, la
        // maquetación se perdía al guardar. Son válidos en HTML 4.01 Transitional, que
        // es el doctype por omisión de HTMLPurifier.
        // `class` es imprescindible: las plantillas completas traen su diseño en un
        // <style> que selecciona por clase (y sus @media para móvil). Sin permitirla,
        // el CSS sobrevivía pero no aplicaba a nada.
        $c = 'style|class';
        $config->set('HTML.Allowed', implode(',', [
            "p[{$c}|align]", "br[{$c}]", "hr[{$c}|width|size|align|noshade]",
            "h1[{$c}|align]", "h2[{$c}|align]", "h3[{$c}|align]",
            "h4[{$c}|align]", "h5[{$c}|align]", "h6[{$c}|align]",
            "strong[{$c}]", "b[{$c}]", "em[{$c}]", "i[{$c}]", "u[{$c}]", "s[{$c}]",
            "strike[{$c}]", "small[{$c}]", "big[{$c}]", "sub[{$c}]", "sup[{$c}]",
            "ul[{$c}|type]", "ol[{$c}|type|start]", "li[{$c}|value]",
            "blockquote[{$c}|cite]", "pre[{$c}]", "code[{$c}]", "address[{$c}]",
            "a[href|title|target|name|{$c}]",
            "img[src|alt|title|width|height|align|border|hspace|vspace|{$c}]",
            "table[{$c}|width|cellpadding|cellspacing|border|align|bgcolor|summary]",
            "thead[{$c}]", "tbody[{$c}]", "tfoot[{$c}]", "caption[{$c}|align]",
            "colgroup[span|width|{$c}]", "col[span|width|{$c}]",
            // Ni table ni tr aceptan `height` en HTML 4.01 Transitional: pedirlo hace
            // que HTMLPurifier emita un PHP Warning por cada llamada, y ese texto se
            // colaría dentro del JSON de los endpoints. En td/th sí es válido.
            "tr[{$c}|align|valign|bgcolor]",
            "td[{$c}|colspan|rowspan|align|valign|bgcolor|width|height|nowrap]",
            "th[{$c}|colspan|rowspan|align|valign|bgcolor|width|height|nowrap]",
            "span[{$c}]", "div[{$c}|align]", "center[{$c}]", 'font[color|face|size]',
        ]));
        $config->set('CSS.AllowedProperties', implode(',', [
            'color', 'background', 'background-color', 'background-image',
            'background-position', 'background-repeat', 'background-size',
            'text-align', 'text-decoration', 'text-indent', 'text-transform',
            'font', 'font-weight', 'font-style', 'font-size', 'font-family',
            'font-variant', 'line-height', 'letter-spacing', 'word-spacing',
            'white-space', 'direction', 'vertical-align',
            'padding', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
            'margin', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
            'border', 'border-top', 'border-right', 'border-bottom', 'border-left',
            'border-color', 'border-style', 'border-width', 'border-collapse', 'border-spacing',
            'width', 'height', 'max-width', 'max-height', 'min-width', 'min-height',
            'float', 'clear', 'table-layout', 'caption-side',
            'list-style', 'list-style-type', 'list-style-position',
            // No agregar 'border-radius' ni 'display': HTMLPurifier no los soporta y
            // emite un PHP Warning por cada llamada, que se colaría dentro del JSON
            // de los endpoints y rompería la respuesta. Tampoco hacen falta en correo
            // (muchos clientes, como Outlook, los ignoran de todos modos).
        ]));
        $config->set('HTML.TargetBlank', true);
        $config->set('URI.AllowedSchemes', [
            'http' => true, 'https' => true, 'mailto' => true, 'tel' => true,
        ]);
        if (is_dir($cacheDir) && is_writable($cacheDir)) {
            $config->set('Cache.SerializerPath', $cacheDir);
        } else {
            $config->set('Cache.DefinitionImpl', null);
        }

        self::extendCssDefinition($config);

        $purifier = new HTMLPurifier($config);
        return $purifier->purify($html);
    }

    /**
     * HTMLPurifier valida el CSS pensando en comentarios de blog, no en correo: lo que
     * no reconoce lo borra **en silencio**, y basta una declaración perdida para que la
     * plantilla se desarme al llegar al buzón.
     *
     * Caso real que motivó esto: un `background-size:600px auto` en la cabecera. Al
     * perderse, la imagen de fondo (1200 px, con el logo al centro) se dibujaba a tamaño
     * natural y su logo dejaba de coincidir con el <img> del logo que va encima → el
     * correo llegaba con el logo repetido y encimado.
     *
     * Aquí se le enseñan las propiedades que el HTML de correo sí usa. Se agregan al
     * vocabulario (no se saltan la validación): cada valor sigue revisándose.
     */
    private static function extendCssDefinition(HTMLPurifier_Config $config): void
    {
        $css = $config->getCSSDefinition();

        $lengthOrPercent = new HTMLPurifier_AttrDef_CSS_Composite([
            new HTMLPurifier_AttrDef_CSS_Length(),
            new HTMLPurifier_AttrDef_CSS_Percentage(),
        ]);

        // background-size: además de las palabras clave, el par de medidas (600px auto).
        $css->info['background-size'] = new HTMLPurifier_AttrDef_CSS_Composite([
            new HTMLPurifier_AttrDef_Enum(['auto', 'cover', 'contain', 'inherit', 'initial'], false),
            new HTMLPurifier_AttrDef_CSS_Multiple(
                new HTMLPurifier_AttrDef_CSS_Composite([
                    new HTMLPurifier_AttrDef_Enum(['auto'], false),
                    new HTMLPurifier_AttrDef_CSS_Length(),
                    new HTMLPurifier_AttrDef_CSS_Percentage(),
                ]),
                2
            ),
        ]);

        // display: imprescindible en correo (img{display:block} contra el hueco bajo las
        // imágenes, y bloques que solo se muestran en móvil).
        $css->info['display'] = new HTMLPurifier_AttrDef_Enum([
            'block', 'inline', 'inline-block', 'inline-table', 'none',
            'table', 'table-row', 'table-cell', 'table-header-group', 'table-row-group',
            'list-item',
        ], false);

        $css->info['overflow'] = new HTMLPurifier_AttrDef_Enum(['visible', 'hidden', 'scroll', 'auto'], false);

        // Esquinas redondeadas: hasta 4 medidas.
        $css->info['border-radius'] = new HTMLPurifier_AttrDef_CSS_Multiple($lengthOrPercent, 4);
        foreach (['border-top-left-radius', 'border-top-right-radius',
                  'border-bottom-left-radius', 'border-bottom-right-radius'] as $corner) {
            $css->info[$corner] = new HTMLPurifier_AttrDef_CSS_Multiple($lengthOrPercent, 2);
        }

        // Extensiones que los clientes de correo esperan (Outlook / WebKit).
        $css->info['mso-hide'] = new HTMLPurifier_AttrDef_Enum(['all', 'none'], false);
        $css->info['mso-line-height-rule'] = new HTMLPurifier_AttrDef_Enum(['exactly', 'at-least'], false);
        $css->info['mso-table-lspace'] = new HTMLPurifier_AttrDef_CSS_Length();
        $css->info['mso-table-rspace'] = new HTMLPurifier_AttrDef_CSS_Length();
        $css->info['-ms-interpolation-mode'] = new HTMLPurifier_AttrDef_Enum(['bicubic', 'nearest-neighbor'], false);
        $css->info['-webkit-text-size-adjust'] = new HTMLPurifier_AttrDef_CSS_Composite([
            new HTMLPurifier_AttrDef_Enum(['none', 'auto'], false),
            new HTMLPurifier_AttrDef_CSS_Percentage(),
        ]);
        $css->info['-ms-text-size-adjust'] = $css->info['-webkit-text-size-adjust'];
    }

    /**
     * Limpia el CSS de los bloques <style> de una plantilla completa.
     * HTMLPurifier no sanea CSS por sí solo (su filtro para ello exige CSSTidy, que
     * no está instalado), así que aquí se corta lo que puede ejecutar código o traer
     * contenido de fuera; el resto del CSS se respeta para no romper el diseño.
     */
    public static function sanitizeCss(string $css): string
    {
        $css = preg_replace('#/\*.*?\*/#s', '', $css);                       // comentarios
        $css = preg_replace('#@(?:import|charset|namespace)[^;]*;#i', '', (string) $css);
        $css = preg_replace('#expression\s*\(#i', 'void(', (string) $css);    // expression() de IE
        $css = preg_replace('#(?:javascript|vbscript|data)\s*:#i', 'blocked:', (string) $css);
        $css = preg_replace('#(?:behaviou?r|-moz-binding)\s*:[^;}]*#i', '', (string) $css);
        $css = str_replace(['<', '>'], '', (string) $css);                   // no cerrar el <style>
        return trim($css);
    }

    /** Deja solo bgcolor y style (saneados) del <body> de una plantilla completa. */
    private static function sanitizeBodyAttributes(string $attrs): string
    {
        $out = '';
        if (preg_match('~\bbgcolor\s*=\s*["\']?(#?[a-z0-9]+)["\']?~i', $attrs, $m)) {
            $out .= ' bgcolor="' . htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8') . '"';
        }
        if (preg_match('~\bstyle\s*=\s*"([^"]*)"~i', $attrs, $m)
            || preg_match("~\bstyle\s*=\s*'([^']*)'~i", $attrs, $m)) {
            $style = self::sanitizeCss($m[1]);
            if ($style !== '') {
                $out .= ' style="' . htmlspecialchars($style, ENT_QUOTES, 'UTF-8') . '"';
            }
        }
        return $out;
    }

    /**
     * Arma el correo tal como saldrá: documento completo intacto, fragmento dentro
     * del diseño institucional, o fragmento suelto si el administrador apagó esa
     * opción. Es la única fuente de verdad del envío; la vista previa del navegador
     * replica estas tres ramas.
     */
    public static function buildEmailHtml(string $subject, string $bodyHtml, bool $useLayout): string
    {
        if (self::isFullHtmlDocument($bodyHtml)) {
            return $bodyHtml;
        }

        $safeSubject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');

        if (!$useLayout) {
            return "<!DOCTYPE html>\n<html>\n<head>\n<meta charset=\"UTF-8\">\n"
                . "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n"
                . "<title>{$safeSubject}</title>\n</head>\n<body>\n{$bodyHtml}\n</body>\n</html>";
        }

        $cssPath = __DIR__ . '/../view/assets/css/email_templates/email.css';
        $css = is_file($cssPath) ? (string) file_get_contents($cssPath) : '';
        $logo = 'https://servicioypracticas.unimontrer.edu.mx/view/assets/images/logo-color.png';

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$safeSubject}</title>
    <style>{$css}</style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{$logo}" alt="Logo UNIMO">
        </div>
        <div class="content">
            {$bodyHtml}
        </div>
        <div class="footer">
            Universidad Montrer (UNIMO) • Av Lázaro Cárdenas 1760, Chapultepec Sur, 58260 Morelia, Mich.
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * ¿El mensaje tiene algo que mostrar? Una plantilla puede ser solo imágenes o
     * una tabla maquetada, así que no basta con exigir texto (y strip_tags() a secas
     * daría por “lleno” un documento cuyo único contenido es el CSS del <style>).
     */
    public static function hasVisibleContent(string $html): bool
    {
        if (self::htmlToPlainText($html) !== '') {
            return true;
        }
        return (bool) preg_match('#<(?:img|table|hr)\b#i', $html);
    }

    /**
     * Versión en texto plano del correo (AltBody). No basta strip_tags(): en una
     * plantilla completa arrastraría todo el contenido de <style> al texto.
     */
    public static function htmlToPlainText(string $html): string
    {
        $t = preg_replace('#<(style|script|head)\b.*?</\1>#is', ' ', $html);
        $t = preg_replace('#<br\s*/?>#i', "\n", (string) $t);
        $t = preg_replace('#</(p|div|tr|li|h[1-6])>#i', "\n", (string) $t);
        $t = strip_tags((string) $t);
        $t = html_entity_decode($t, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $t = preg_replace('#[ \t]+#', ' ', $t);
        $t = preg_replace('#\n{3,}#', "\n\n", (string) $t);
        return trim((string) $t);
    }

    /* ═══════════════════════════ Configuración SMTP propia ═══════════════════════════ */

    /** Config sin la contraseña (para mostrar en el formulario). */
    public static function getSmtpConfigPublic(): array
    {
        $stmt = self::db()->query("SELECT host, port, encryption, username, from_email, from_name,
                                           (password_enc IS NOT NULL AND password_enc <> '') AS has_password,
                                           updated_at
                                    FROM mail_bulk_smtp_config WHERE id = 1");
        $row = $stmt->fetch();
        if (!$row) {
            return [
                'host' => '', 'port' => 587, 'encryption' => 'tls', 'username' => '',
                'from_email' => '', 'from_name' => '', 'has_password' => false, 'updated_at' => null,
            ];
        }
        $row['has_password'] = (bool) $row['has_password'];
        $row['port'] = (int) $row['port'];
        return $row;
    }

    /** Config completa, con la contraseña ya descifrada, para uso interno (envío real). */
    public static function getSmtpConfigForSending(): ?array
    {
        $stmt = self::db()->query("SELECT * FROM mail_bulk_smtp_config WHERE id = 1");
        $row = $stmt->fetch();
        if (!$row || $row['host'] === '' || $row['username'] === '') {
            return null;
        }
        $password = Crypto::decrypt($row['password_enc']);
        return [
            'host' => $row['host'],
            'port' => (int) $row['port'],
            'encryption' => $row['encryption'],
            'username' => $row['username'],
            'password' => $password ?? '',
            'from_email' => $row['from_email'],
            'from_name' => $row['from_name'],
        ];
    }

    public static function saveSmtpConfig(array $data, ?int $userId): void
    {
        $pdo = self::db();
        $current = self::getSmtpConfigForSending();
        $passwordEnc = $current['password'] ?? '';
        if (!empty($data['password'])) {
            $passwordEnc = Crypto::encrypt($data['password']);
        } else {
            // No se capturó una nueva contraseña: conservar la cifrada que ya existía.
            $stmt = $pdo->query("SELECT password_enc FROM mail_bulk_smtp_config WHERE id = 1");
            $row = $stmt->fetch();
            $passwordEnc = $row['password_enc'] ?? null;
        }

        $stmt = $pdo->prepare("
            INSERT INTO mail_bulk_smtp_config (id, host, port, encryption, username, password_enc, from_email, from_name, updated_by, updated_at)
            VALUES (1, :host, :port, :encryption, :username, :password_enc, :from_email, :from_name, :updated_by, NOW())
            ON DUPLICATE KEY UPDATE
                host = VALUES(host), port = VALUES(port), encryption = VALUES(encryption),
                username = VALUES(username), password_enc = VALUES(password_enc),
                from_email = VALUES(from_email), from_name = VALUES(from_name),
                updated_by = VALUES(updated_by), updated_at = VALUES(updated_at)
        ");
        $stmt->execute([
            ':host' => $data['host'],
            ':port' => $data['port'],
            ':encryption' => $data['encryption'],
            ':username' => $data['username'],
            ':password_enc' => $passwordEnc,
            ':from_email' => $data['from_email'],
            ':from_name' => $data['from_name'],
            ':updated_by' => $userId,
        ]);
    }

    /* ═══════════════════════════ Destinatarios ═══════════════════════════ */

    public static function listRecipients(string $search = ''): array
    {
        $pdo = self::db();
        if ($search !== '') {
            $stmt = $pdo->prepare("SELECT id, name, email, notes, created_at FROM mail_recipients
                                    WHERE name LIKE :q OR email LIKE :q ORDER BY name ASC");
            $stmt->execute([':q' => '%' . $search . '%']);
        } else {
            $stmt = $pdo->query("SELECT id, name, email, notes, created_at FROM mail_recipients ORDER BY name ASC");
        }
        return $stmt->fetchAll();
    }

    public static function createRecipient(string $name, string $email, string $notes, ?int $userId): array
    {
        $email = self::normalizeEmail($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'El correo electrónico no es válido.'];
        }
        if (trim($name) === '') {
            return ['ok' => false, 'error' => 'El nombre no puede estar vacío.'];
        }
        try {
            $stmt = self::db()->prepare("INSERT INTO mail_recipients (name, email, notes, created_by) VALUES (:name, :email, :notes, :created_by)");
            $stmt->execute([
                ':name' => trim($name), ':email' => $email,
                ':notes' => $notes !== '' ? $notes : null, ':created_by' => $userId,
            ]);
            return ['ok' => true, 'id' => (int) self::db()->lastInsertId()];
        } catch (PDOException $e) {
            if ((int) $e->errorInfo[1] === 1062) {
                return ['ok' => false, 'error' => 'Ya existe una persona con ese correo en la lista.'];
            }
            throw $e;
        }
    }

    public static function updateRecipient(int $id, string $name, string $email, string $notes): array
    {
        $email = self::normalizeEmail($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'El correo electrónico no es válido.'];
        }
        if (trim($name) === '') {
            return ['ok' => false, 'error' => 'El nombre no puede estar vacío.'];
        }
        try {
            $stmt = self::db()->prepare("UPDATE mail_recipients SET name = :name, email = :email, notes = :notes WHERE id = :id");
            $stmt->execute([
                ':name' => trim($name), ':email' => $email,
                ':notes' => $notes !== '' ? $notes : null, ':id' => $id,
            ]);
            return ['ok' => true];
        } catch (PDOException $e) {
            if ((int) $e->errorInfo[1] === 1062) {
                return ['ok' => false, 'error' => 'Ya existe otra persona con ese correo en la lista.'];
            }
            throw $e;
        }
    }

    public static function deleteRecipients(array $ids): int
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!$ids) {
            return 0;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = self::db()->prepare("DELETE FROM mail_recipients WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        return $stmt->rowCount();
    }

    public static function getRecipientsByIds(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!$ids) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = self::db()->prepare("SELECT id, name, email FROM mail_recipients WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        return $stmt->fetchAll();
    }

    /**
     * Importa destinatarios desde un archivo CSV (columnas: Nombre, Correo).
     * Tolera encabezado presente o ausente, BOM, separador coma o punto y coma.
     */
    public static function importCsv(string $filePath, ?int $userId): array
    {
        $report = ['imported' => 0, 'skipped_duplicates' => 0, 'invalid' => []];

        $content = file_get_contents($filePath);
        if ($content === false) {
            return ['ok' => false, 'error' => 'No se pudo leer el archivo.'];
        }
        // Quitar BOM UTF-8 si existe.
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }
        $delimiter = substr_count($content, ';') > substr_count($content, ',') ? ';' : ',';

        $lines = preg_split('/\r\n|\r|\n/', $content);
        $rowNum = 0;
        $firstRow = true;
        $pdo = self::db();

        foreach ($lines as $line) {
            $rowNum++;
            if (trim($line) === '') {
                continue;
            }
            $cols = str_getcsv($line, $delimiter);
            $name = trim($cols[0] ?? '');
            $email = self::normalizeEmail($cols[1] ?? '');

            // Detectar y omitir fila de encabezado (Nombre,Correo).
            if ($firstRow) {
                $firstRow = false;
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }
            }

            if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $report['invalid'][] = ['fila' => $rowNum, 'motivo' => 'Nombre vacío o correo con formato inválido.'];
                continue;
            }

            try {
                $stmt = $pdo->prepare("INSERT INTO mail_recipients (name, email, created_by) VALUES (:name, :email, :created_by)");
                $stmt->execute([':name' => $name, ':email' => $email, ':created_by' => $userId]);
                $report['imported']++;
            } catch (PDOException $e) {
                if ((int) $e->errorInfo[1] === 1062) {
                    $report['skipped_duplicates']++;
                } else {
                    $report['invalid'][] = ['fila' => $rowNum, 'motivo' => 'No se pudo guardar este registro.'];
                }
            }
        }

        $report['ok'] = true;
        return $report;
    }

    /* ═══════════════════════════ Mensajes guardados ═══════════════════════════ */

    public static function listSavedMessages(): array
    {
        return self::db()->query("SELECT id, name, subject, updated_at FROM mail_saved_messages ORDER BY updated_at DESC")->fetchAll();
    }

    public static function getSavedMessage(int $id): ?array
    {
        $stmt = self::db()->prepare("SELECT * FROM mail_saved_messages WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function saveSavedMessage(?int $id, string $name, string $subject, string $bodyHtmlPurified, ?int $userId, bool $useLayout = true): int
    {
        $pdo = self::db();
        if ($id) {
            $stmt = $pdo->prepare("UPDATE mail_saved_messages SET name=:name, subject=:subject, body_html=:body, use_layout=:layout WHERE id=:id");
            $stmt->execute([':name' => $name, ':subject' => $subject, ':body' => $bodyHtmlPurified, ':layout' => $useLayout ? 1 : 0, ':id' => $id]);
            return $id;
        }
        $stmt = $pdo->prepare("INSERT INTO mail_saved_messages (name, subject, body_html, use_layout, created_by) VALUES (:name, :subject, :body, :layout, :created_by)");
        $stmt->execute([':name' => $name, ':subject' => $subject, ':body' => $bodyHtmlPurified, ':layout' => $useLayout ? 1 : 0, ':created_by' => $userId]);
        return (int) $pdo->lastInsertId();
    }

    public static function deleteSavedMessage(int $id): bool
    {
        $stmt = self::db()->prepare("DELETE FROM mail_saved_messages WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /* ═══════════════════════════ Campañas ═══════════════════════════ */

    /**
     * Crea una campaña + sus filas de destinatarios en una transacción.
     * Si client_token ya existe (doble clic/doble pestaña/recarga), NO crea nada
     * nuevo y devuelve la campaña ya existente — esta es la guarda anti-doble-envío.
     */
    public static function createCampaign(
        string $clientToken,
        string $subject,
        string $bodyHtmlPurified,
        string $fromName,
        array $attachmentPaths,
        array $recipients, // [['id'=>?,'name'=>..,'email'=>..], ...]
        ?int $userId,
        bool $useLayout = true
    ): array {
        $pdo = self::db();

        $existing = self::getCampaignByToken($clientToken);
        if ($existing) {
            return ['created' => false, 'campaign' => $existing];
        }

        $attachmentsJson = $attachmentPaths ? json_encode($attachmentPaths, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("
                INSERT INTO mail_campaigns (client_token, subject, body_html, use_layout, from_name, attachments, status, total_recipients, created_by, created_at)
                VALUES (:token, :subject, :body, :layout, :from_name, :attachments, 'sending', :total, :created_by, NOW())
            ");
            $stmt->execute([
                ':token' => $clientToken,
                ':subject' => $subject,
                ':body' => $bodyHtmlPurified,
                ':layout' => $useLayout ? 1 : 0,
                ':from_name' => $fromName,
                ':attachments' => $attachmentsJson,
                ':total' => count($recipients),
                ':created_by' => $userId,
            ]);
            $campaignId = (int) $pdo->lastInsertId();

            $insRecipient = $pdo->prepare("
                INSERT INTO mail_campaign_recipients (campaign_id, recipient_id, recipient_name, recipient_email, status)
                VALUES (:cid, :rid, :name, :email, 'pending')
            ");
            foreach ($recipients as $r) {
                $insRecipient->execute([
                    ':cid' => $campaignId,
                    ':rid' => $r['id'] ?? null,
                    ':name' => $r['name'],
                    ':email' => $r['email'],
                ]);
            }

            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            // Condición de carrera: otra petición ganó la inserción del mismo client_token.
            if ((int) $e->errorInfo[1] === 1062) {
                $existing = self::getCampaignByToken($clientToken);
                if ($existing) {
                    return ['created' => false, 'campaign' => $existing];
                }
            }
            throw $e;
        }

        return ['created' => true, 'campaign' => self::getCampaignById($campaignId)];
    }

    public static function getCampaignByToken(string $token): ?array
    {
        $stmt = self::db()->prepare("SELECT * FROM mail_campaigns WHERE client_token = :t");
        $stmt->execute([':t' => $token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function getCampaignById(int $id): ?array
    {
        $stmt = self::db()->prepare("SELECT * FROM mail_campaigns WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function listCampaignHistory(int $limit = 50): array
    {
        $stmt = self::db()->prepare("SELECT id, subject, status, total_recipients, sent_count, failed_count, created_at, completed_at
                                      FROM mail_campaigns ORDER BY created_at DESC LIMIT :lim");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getCampaignFailedRecipients(int $campaignId): array
    {
        $stmt = self::db()->prepare("SELECT recipient_name, recipient_email, error_message, sent_at
                                      FROM mail_campaign_recipients WHERE campaign_id = :cid AND status = 'failed'
                                      ORDER BY id ASC");
        $stmt->execute([':cid' => $campaignId]);
        return $stmt->fetchAll();
    }

    public static function getProgress(int $campaignId): ?array
    {
        $campaign = self::getCampaignById($campaignId);
        if (!$campaign) {
            return null;
        }
        $stmt = self::db()->prepare("
            SELECT
                SUM(status IN ('pending','sending')) AS pending,
                SUM(status = 'sent') AS sent,
                SUM(status = 'failed') AS failed
            FROM mail_campaign_recipients WHERE campaign_id = :cid
        ");
        $stmt->execute([':cid' => $campaignId]);
        $counts = $stmt->fetch();
        return [
            'campaign_id' => $campaignId,
            'status' => $campaign['status'],
            'total' => (int) $campaign['total_recipients'],
            'pending' => (int) ($counts['pending'] ?? 0),
            'sent' => (int) ($counts['sent'] ?? 0),
            'failed' => (int) ($counts['failed'] ?? 0),
        ];
    }

    /* ── Procesamiento de la cola propia (llamado por el cron y, para el primer
       lote, de forma síncrona justo al confirmar el envío) ────────────────── */

    public static function releaseStaleReservations(int $campaignId): void
    {
        $stmt = self::db()->prepare("
            UPDATE mail_campaign_recipients
            SET status = 'pending'
            WHERE campaign_id = :cid AND status = 'sending'
              AND reserved_at < (NOW() - INTERVAL " . self::STALE_RESERVATION_MINUTES . " MINUTE)
        ");
        $stmt->execute([':cid' => $campaignId]);
    }

    /** Reserva atómicamente el siguiente lote y lo devuelve ya leído. */
    public static function reserveNextBatch(int $campaignId, int $batchSize = self::BATCH_SIZE): array
    {
        $pdo = self::db();
        self::releaseStaleReservations($campaignId);

        $upd = $pdo->prepare("
            UPDATE mail_campaign_recipients
            SET status = 'sending', attempts = attempts + 1, reserved_at = NOW()
            WHERE campaign_id = :cid AND status = 'pending' AND attempts < :max
            LIMIT :batch
        ");
        $upd->bindValue(':cid', $campaignId, PDO::PARAM_INT);
        $upd->bindValue(':max', self::MAX_ATTEMPTS, PDO::PARAM_INT);
        $upd->bindValue(':batch', $batchSize, PDO::PARAM_INT);
        $upd->execute();

        if ($upd->rowCount() === 0) {
            return [];
        }

        $sel = $pdo->prepare("
            SELECT * FROM mail_campaign_recipients
            WHERE campaign_id = :cid AND status = 'sending'
            ORDER BY id ASC LIMIT :batch
        ");
        $sel->bindValue(':cid', $campaignId, PDO::PARAM_INT);
        $sel->bindValue(':batch', $batchSize, PDO::PARAM_INT);
        $sel->execute();
        return $sel->fetchAll();
    }

    public static function markSent(int $rowId): void
    {
        $stmt = self::db()->prepare("UPDATE mail_campaign_recipients SET status='sent', sent_at=NOW(), error_message=NULL WHERE id=:id");
        $stmt->execute([':id' => $rowId]);
    }

    public static function markFailed(int $rowId, int $attempts, string $errorMessage): void
    {
        $status = $attempts >= self::MAX_ATTEMPTS ? 'failed' : 'pending';
        $stmt = self::db()->prepare("UPDATE mail_campaign_recipients SET status=:status, error_message=:err WHERE id=:id");
        $stmt->execute([':status' => $status, ':err' => $errorMessage, ':id' => $rowId]);
    }

    /** Recalcula contadores de la campaña y cierra su estado si ya no quedan filas pendientes. */
    public static function refreshCampaignStatus(int $campaignId): void
    {
        $pdo = self::db();
        $stmt = $pdo->prepare("
            SELECT
                SUM(status IN ('pending','sending')) AS pending,
                SUM(status = 'sent') AS sent,
                SUM(status = 'failed') AS failed
            FROM mail_campaign_recipients WHERE campaign_id = :cid
        ");
        $stmt->execute([':cid' => $campaignId]);
        $c = $stmt->fetch();
        $pending = (int) ($c['pending'] ?? 0);
        $sent = (int) ($c['sent'] ?? 0);
        $failed = (int) ($c['failed'] ?? 0);

        if ($pending > 0) {
            $upd = $pdo->prepare("UPDATE mail_campaigns SET sent_count=:s, failed_count=:f WHERE id=:id");
            $upd->execute([':s' => $sent, ':f' => $failed, ':id' => $campaignId]);
            return;
        }

        $status = $failed > 0 ? ($sent > 0 ? 'completed_with_errors' : 'failed') : 'completed';
        $upd = $pdo->prepare("UPDATE mail_campaigns SET sent_count=:s, failed_count=:f, status=:status, completed_at=NOW() WHERE id=:id");
        $upd->execute([':s' => $sent, ':f' => $failed, ':status' => $status, ':id' => $campaignId]);
    }

    /** Sustituye {nombre} y {correo} por los datos reales del destinatario. */
    public static function interpolatePersonal(string $tpl, string $name, string $email): string
    {
        return str_ireplace(['{nombre}', '{correo}'], [$name, $email], $tpl);
    }

    /**
     * Procesa un lote de una campaña: reserva filas pendientes, envía cada correo
     * con el SMTP propio del módulo, y actualiza estados + contadores.
     * Usado tanto por el cron (controller/cron/process_mail_bulk_queue.php) como,
     * para el primer lote, de forma síncrona justo al confirmar el envío.
     *
     * @return array{sent:int,failed:int,remaining:int}
     */
    public static function processBatch(int $campaignId, array $smtpConfig, int $batchSize = self::BATCH_SIZE): array
    {
        require_once __DIR__ . '/../controller/emails.php';

        $campaign = self::getCampaignById($campaignId);
        if (!$campaign) {
            return ['sent' => 0, 'failed' => 0, 'remaining' => 0];
        }

        $attachments = [];
        if (!empty($campaign['attachments'])) {
            $decoded = json_decode($campaign['attachments'], true);
            if (is_array($decoded)) {
                $attachments = $decoded;
            }
        }

        $rows = self::reserveNextBatch($campaignId, $batchSize);
        $sent = 0;
        $failed = 0;

        foreach ($rows as $row) {
            $subject = self::interpolatePersonal($campaign['subject'], $row['recipient_name'], $row['recipient_email']);
            $bodyHtml = self::interpolatePersonal($campaign['body_html'], $row['recipient_name'], $row['recipient_email']);
            // El correo se arma aquí, no al guardar la campaña: así el cuerpo almacenado
            // sigue siendo lo que el administrador escribió y la vista previa puede
            // reproducirlo con la misma regla.
            $bodyHtml = self::buildEmailHtml($subject, $bodyHtml, !empty($campaign['use_layout']));
            $plainText = self::htmlToPlainText($bodyHtml);

            $errorOut = null;
            $result = MailService::dispatchMail(
                $row['recipient_email'],
                $subject,
                $bodyHtml,
                $plainText,
                $campaign['from_name'],
                $attachments,
                $smtpConfig,
                $errorOut
            );

            if ($result === 'ok') {
                self::markSent((int) $row['id']);
                $sent++;
            } else {
                self::markFailed((int) $row['id'], (int) $row['attempts'], self::translateMailError($errorOut));
                $failed++;
            }
        }

        self::refreshCampaignStatus($campaignId);
        $progress = self::getProgress($campaignId);

        return ['sent' => $sent, 'failed' => $failed, 'remaining' => $progress['pending'] ?? 0];
    }

    /**
     * Traduce mensajes técnicos de PHPMailer a español claro para el administrador.
     */
    public static function translateMailError(?string $raw): string
    {
        $raw = (string) $raw;
        $map = [
            'could not authenticate' => 'El usuario o la contraseña del servidor de correo son incorrectos.',
            'smtp connect() failed' => 'No se pudo conectar con el servidor de correo. Revisa el servidor y el puerto.',
            'invalid address' => 'El correo del destinatario no es válido.',
            'sender address rejected' => 'El servidor de correo rechazó la dirección de remitente configurada.',
            'recipients could not be added' => 'El servidor de correo rechazó al destinatario.',
        ];
        $lower = mb_strtolower($raw);
        foreach ($map as $needle => $friendly) {
            if (str_contains($lower, $needle)) {
                return $friendly;
            }
        }
        return $raw !== '' ? 'No se pudo enviar el correo. Revisa la configuración del servidor de correo.' : 'No se pudo enviar el correo por un error desconocido.';
    }
}
