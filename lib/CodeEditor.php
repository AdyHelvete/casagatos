<?php
declare(strict_types=1);

require_once __DIR__ . '/PageRegistry.php';

/**
 * Edición controlada de plantillas PHP, hojas de estilo y archivos de texto
 * públicos desde el panel.
 *
 * Reglas de seguridad:
 *  - Sólo se pueden abrir rutas que resuelvan dentro de la lista blanca.
 *  - Nunca se exponen lib/, control/, api/, data/ ni .htaccess.
 *  - Cada escritura genera un respaldo previo restaurable.
 *  - Las plantillas PHP se validan sintácticamente y deben conservar el
 *    envoltorio tw_page_start()/tw_page_end().
 */
class CodeEditor
{
    private const MAX_BYTES = 1048576;

    /** Funciones sin ningún uso legítimo en una plantilla de página. */
    private const BLOCKED_FUNCTIONS = [
        'exec', 'shell_exec', 'system', 'passthru', 'proc_open', 'popen',
        'eval', 'assert', 'create_function', 'pcntl_exec', 'putenv',
        'ini_set', 'set_include_path', 'move_uploaded_file',
    ];

    private const BLOCKED_PATH_SEGMENTS = [
        '/lib/', '/control/', '/api/', '/data/', '/backups/', '/.git/',
    ];

    public static function root(): string
    {
        return self::normalize(PageRegistry::projectRoot());
    }

    private static function normalize(string $path): string
    {
        return rtrim(str_replace('\\', '/', $path), '/');
    }

    /**
     * Grupos de archivos editables que se muestran en el panel.
     */
    public static function catalog(): array
    {
        $root = self::root();
        $groups = [];

        $templates = [];
        foreach (PageRegistry::pages() as $page) {
            if (($page['type'] ?? 'page') !== 'page') {
                continue;
            }
            $relative = PageRegistry::pageFileRelative($page);
            if (!is_file($root . '/' . $relative)) {
                continue;
            }
            $templates[$relative] = ($page['menu']['label'] ?: $page['id']) . ' — ' . PageRegistry::pageUrl($page);
        }
        if ($templates !== []) {
            $groups['Plantillas de página (PHP)'] = $templates;
        }

        $styles = [];
        foreach (self::globRelative('assets/css/*.css') as $relative) {
            $styles[$relative] = $relative;
        }
        if ($styles !== []) {
            $groups['Hojas de estilo (CSS)'] = $styles;
        }

        $partials = [];
        foreach (self::globRelative('assets/partials/*.html') as $relative) {
            $partials[$relative] = $relative;
        }
        if ($partials !== []) {
            $groups['Parciales incluidos'] = $partials;
        }

        $texts = [];
        foreach (['robots.txt', 'llms.txt', 'llms-full.txt', 'site.webmanifest'] as $name) {
            if (is_file($root . '/' . $name)) {
                $texts[$name] = $name;
            }
        }
        if ($texts !== []) {
            $groups['SEO y texto plano'] = $texts;
        }

        $scripts = [];
        foreach (self::globRelative('assets/js/*.js') as $relative) {
            $scripts[$relative] = $relative;
        }
        if ($scripts !== []) {
            $groups['JavaScript'] = $scripts;
        }

        return $groups;
    }

    private static function globRelative(string $pattern): array
    {
        $root = self::root();
        $found = (array) glob($root . '/' . $pattern);
        $result = [];

        foreach ($found as $absolute) {
            if (!is_file((string) $absolute)) {
                continue;
            }
            $result[] = ltrim(str_replace($root, '', self::normalize((string) $absolute)), '/');
        }

        sort($result);

        return $result;
    }

    public static function isEditable(string $relative): bool
    {
        foreach (self::catalog() as $files) {
            if (isset($files[$relative])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convierte una ruta relativa en absoluta validando que sea segura.
     */
    public static function resolve(string $relative): ?string
    {
        $relative = str_replace('\\', '/', trim($relative));
        $relative = ltrim($relative, '/');

        if ($relative === '' || str_contains($relative, '..') || str_contains($relative, "\0")) {
            return null;
        }

        $root = self::root();
        $absolute = $root . '/' . $relative;
        $real = realpath($absolute);

        if ($real === false) {
            return null;
        }

        $real = self::normalize($real);

        if (!str_starts_with($real . '/', $root . '/')) {
            return null;
        }

        $probe = str_replace($root, '', $real) . '/';
        foreach (self::BLOCKED_PATH_SEGMENTS as $segment) {
            if (str_contains($probe, $segment)) {
                return null;
            }
        }

        if (is_link($absolute)) {
            return null;
        }

        if (!self::isEditable($relative)) {
            return null;
        }

        return $real;
    }

    public static function read(string $relative): ?array
    {
        $absolute = self::resolve($relative);
        if ($absolute === null || !is_file($absolute)) {
            return null;
        }

        $size = (int) filesize($absolute);
        if ($size > self::MAX_BYTES) {
            return null;
        }

        return [
            'relative' => $relative,
            'absolute' => $absolute,
            'contents' => (string) file_get_contents($absolute),
            'size' => $size,
            'modified' => (int) filemtime($absolute),
            'writable' => is_writable($absolute),
            'language' => self::languageFor($relative),
        ];
    }

    public static function languageFor(string $relative): string
    {
        return match (strtolower((string) pathinfo($relative, PATHINFO_EXTENSION))) {
            'php' => 'php',
            'css' => 'css',
            'js' => 'javascript',
            'html' => 'html',
            'webmanifest', 'json' => 'json',
            default => 'text',
        };
    }

    public static function save(string $relative, string $contents): array
    {
        $absolute = self::resolve($relative);
        if ($absolute === null) {
            return ['success' => false, 'message' => 'Archivo no editable o ruta no permitida.'];
        }

        if (!is_writable($absolute)) {
            return ['success' => false, 'message' => 'El archivo no tiene permisos de escritura en el servidor.'];
        }

        $contents = str_replace("\r\n", "\n", $contents);

        if (strlen($contents) > self::MAX_BYTES) {
            return ['success' => false, 'message' => 'El contenido supera el tamaño máximo permitido (1 MB).'];
        }

        $validation = self::validate($relative, $contents);
        if ($validation !== null) {
            return ['success' => false, 'message' => $validation];
        }

        $backup = self::backup($absolute);

        if (file_put_contents($absolute, $contents, LOCK_EX) === false) {
            return ['success' => false, 'message' => 'No se pudo guardar el archivo.'];
        }

        return [
            'success' => true,
            'message' => 'Cambios guardados.' . ($backup !== null ? ' Respaldo: ' . basename($backup) : ''),
            'backup' => $backup,
        ];
    }

    /**
     * Devuelve un mensaje de error si el contenido no es válido, o null si pasa.
     */
    public static function validate(string $relative, string $contents): ?string
    {
        $extension = strtolower((string) pathinfo($relative, PATHINFO_EXTENSION));

        if ($extension === 'php') {
            $blocked = self::findBlockedFunction($contents);
            if ($blocked !== null) {
                return "No se permite usar «$blocked()» en las plantillas del sitio.";
            }

            $syntax = self::checkPhpSyntax($contents);
            if ($syntax !== null) {
                return 'Error de sintaxis PHP: ' . $syntax;
            }

            if (self::isPageTemplate($relative)) {
                if (!str_contains($contents, 'tw_page_start(')) {
                    return 'La plantilla debe conservar la llamada a tw_page_start() para renderizar el encabezado y el SEO.';
                }
                if (!str_contains($contents, 'tw_page_end(')) {
                    return 'La plantilla debe conservar la llamada a tw_page_end() para renderizar el footer.';
                }
            }
        }

        if ($extension === 'css') {
            $open = substr_count($contents, '{');
            $close = substr_count($contents, '}');
            if ($open !== $close) {
                return "Las llaves del CSS no están balanceadas ($open «{» y $close «}»).";
            }
        }

        if (in_array($extension, ['json', 'webmanifest'], true)) {
            json_decode($contents);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return 'JSON inválido: ' . json_last_error_msg();
            }
        }

        return null;
    }

    private static function isPageTemplate(string $relative): bool
    {
        foreach (PageRegistry::pages() as $page) {
            if (($page['type'] ?? 'page') !== 'page') {
                continue;
            }
            if (PageRegistry::pageFileRelative($page) === $relative) {
                return true;
            }
        }

        return false;
    }

    private static function findBlockedFunction(string $contents): ?string
    {
        foreach (self::BLOCKED_FUNCTIONS as $function) {
            if (preg_match('/(?<![\w$>:])' . preg_quote($function, '/') . '\s*\(/i', $contents) === 1) {
                return $function;
            }
        }

        return null;
    }

    /**
     * Valida sintaxis PHP sin ejecutar el código, usando el tokenizador.
     */
    public static function checkPhpSyntax(string $contents): ?string
    {
        try {
            token_get_all($contents, TOKEN_PARSE);
        } catch (ParseError $error) {
            return $error->getMessage() . ' (línea ' . $error->getLine() . ')';
        } catch (Throwable $error) {
            return $error->getMessage();
        }

        return null;
    }

    public static function backupsDir(): string
    {
        return PageRegistry::projectRoot() . '/backups/code';
    }

    public static function backup(string $absolutePath): ?string
    {
        if (!is_file($absolutePath)) {
            return null;
        }

        $dir = self::backupsDir();
        if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
            return null;
        }

        $relative = ltrim(str_replace(self::root(), '', self::normalize($absolutePath)), '/');
        $name = str_replace('/', '__', $relative) . '.' . date('Ymd-His') . '.bak';
        $target = $dir . '/' . $name;

        if (!copy($absolutePath, $target)) {
            return null;
        }

        self::pruneBackups($relative);

        return $target;
    }

    /** Conserva los 15 respaldos más recientes de cada archivo. */
    private static function pruneBackups(string $relative, int $keep = 15): void
    {
        $prefix = str_replace('/', '__', $relative) . '.';
        $files = (array) glob(self::backupsDir() . '/' . $prefix . '*.bak');

        if (count($files) <= $keep) {
            return;
        }

        rsort($files);
        foreach (array_slice($files, $keep) as $old) {
            @unlink((string) $old);
        }
    }

    public static function backupsFor(string $relative): array
    {
        $prefix = str_replace('/', '__', $relative) . '.';
        $files = (array) glob(self::backupsDir() . '/' . $prefix . '*.bak');
        $result = [];

        foreach ($files as $file) {
            $file = (string) $file;
            $result[] = [
                'name' => basename($file),
                'modified' => (int) filemtime($file),
                'size' => (int) filesize($file),
            ];
        }

        usort($result, static fn(array $a, array $b): int => $b['modified'] <=> $a['modified']);

        return $result;
    }

    public static function restore(string $relative, string $backupName): array
    {
        $absolute = self::resolve($relative);
        if ($absolute === null) {
            return ['success' => false, 'message' => 'Archivo no editable.'];
        }

        if (basename($backupName) !== $backupName || !str_ends_with($backupName, '.bak')) {
            return ['success' => false, 'message' => 'Nombre de respaldo inválido.'];
        }

        $expectedPrefix = str_replace('/', '__', $relative) . '.';
        if (!str_starts_with($backupName, $expectedPrefix)) {
            return ['success' => false, 'message' => 'Ese respaldo no corresponde a este archivo.'];
        }

        $source = self::backupsDir() . '/' . $backupName;
        if (!is_file($source)) {
            return ['success' => false, 'message' => 'El respaldo no existe.'];
        }

        $contents = (string) file_get_contents($source);
        self::backup($absolute);

        if (file_put_contents($absolute, $contents, LOCK_EX) === false) {
            return ['success' => false, 'message' => 'No se pudo restaurar el archivo.'];
        }

        return ['success' => true, 'message' => 'Archivo restaurado desde ' . $backupName];
    }
}
