<?php
declare(strict_types=1);

require_once __DIR__ . '/SiteStorage.php';

/**
 * Registro de páginas del sitio. Guarda metadatos SEO, posición en el menú y
 * configuración de sitemap para cada sección, además del archivo de plantilla
 * que contiene el cuerpo editable.
 */
class PageRegistry
{
    private const FILE = 'pages.json';

    public static function defaults(): array
    {
        return [
            'settings' => [
                'siteUrl' => 'https://lacasadelosgatos.mx',
                'lang' => 'es-MX',
                'locale' => 'es_MX',
                'siteName' => 'La Casa de los Gatos',
                'author' => 'La Casa de los Gatos',
                'defaultRobots' => 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1',
                'defaultOgImage' => '/assets/images/portada.jpg',
                'themeColor' => '#1a1612',
                'geoRegion' => 'MX-HGO',
                'geoPlacename' => 'Tizayuca',
                'cssVersion' => 10,
                'jsVersion' => 3,
                'configJsVersion' => 3,
                'ctaLabel' => 'Contacto',
                'ctaHref' => '/contacto/',
                'basePath' => 'auto',
            ],
            'pages' => [],
        ];
    }

    public static function blankPage(): array
    {
        return [
            'id' => '',
            'type' => 'page',
            'slug' => '',
            'file' => '',
            'status' => 'published',
            'system' => false,
            'title' => '',
            'description' => '',
            'canonical' => '',
            'robots' => '',
            'ogType' => 'website',
            'ogTitle' => '',
            'ogDescription' => '',
            'ogImage' => '',
            'twitterCard' => 'summary_large_image',
            'twitterTitle' => '',
            'twitterDescription' => '',
            'manifest' => false,
            'extraHead' => '',
            'jsonLd' => '',
            'bodyClass' => '',
            'menu' => [
                'show' => true,
                'label' => '',
                'order' => 99,
                'style' => 'link',
                'target' => '',
            ],
            'sitemap' => [
                'include' => true,
                'changefreq' => 'monthly',
                'priority' => '0.6',
                'lastmod' => '',
            ],
        ];
    }

    public static function all(): array
    {
        $data = SiteStorage::read(self::FILE, self::defaults());
        $data = array_replace_recursive(self::defaults(), is_array($data) ? $data : []);

        if (!isset($data['pages']) || !is_array($data['pages'])) {
            $data['pages'] = [];
        }

        $data['pages'] = array_values(array_map(
            static fn(array $page): array => array_replace_recursive(self::blankPage(), $page),
            array_filter($data['pages'], 'is_array')
        ));

        usort($data['pages'], static function (array $a, array $b): int {
            return ((int) ($a['menu']['order'] ?? 99)) <=> ((int) ($b['menu']['order'] ?? 99));
        });

        return $data;
    }

    public static function settings(): array
    {
        return self::all()['settings'];
    }

    public static function pages(): array
    {
        return self::all()['pages'];
    }

    public static function save(array $data): bool
    {
        return SiteStorage::write(self::FILE, [
            'settings' => array_replace(self::defaults()['settings'], $data['settings'] ?? []),
            'pages' => array_values($data['pages'] ?? []),
        ]);
    }

    public static function saveSettings(array $settings): bool
    {
        $data = self::all();
        $data['settings'] = array_replace($data['settings'], $settings);

        return self::save($data);
    }

    public static function find(string $id): ?array
    {
        foreach (self::pages() as $page) {
            if (($page['id'] ?? '') === $id) {
                return $page;
            }
        }

        return null;
    }

    public static function savePage(array $page): bool
    {
        $page = array_replace_recursive(self::blankPage(), $page);
        $page['id'] = self::sanitizeId((string) $page['id']);

        if ($page['id'] === '') {
            return false;
        }

        $data = self::all();
        $found = false;

        foreach ($data['pages'] as $index => $existing) {
            if (($existing['id'] ?? '') === $page['id']) {
                $page['system'] = (bool) ($existing['system'] ?? false);
                $data['pages'][$index] = $page;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $data['pages'][] = $page;
        }

        return self::save($data);
    }

    public static function deletePage(string $id): array
    {
        $page = self::find($id);
        if (!$page) {
            return ['success' => false, 'message' => 'La página no existe.'];
        }

        if (!empty($page['system'])) {
            return ['success' => false, 'message' => 'Esta página es del sistema y no se puede eliminar. Puedes ocultarla del menú o despublicarla.'];
        }

        $data = self::all();
        $data['pages'] = array_values(array_filter(
            $data['pages'],
            static fn(array $entry): bool => ($entry['id'] ?? '') !== $id
        ));

        if (!self::save($data)) {
            return ['success' => false, 'message' => 'No se pudo actualizar el registro de páginas.'];
        }

        if (($page['type'] ?? 'page') !== 'page') {
            return ['success' => true, 'message' => 'Enlace eliminado del menú.'];
        }

        $leftovers = self::removePageDirectory((string) ($page['slug'] ?? ''));

        if ($leftovers !== []) {
            return [
                'success' => true,
                'message' => 'Página eliminada del sitio. Quedaron archivos en la carpeta que debes revisar por FTP: ' . implode(', ', $leftovers),
            ];
        }

        return ['success' => true, 'message' => 'Página eliminada.'];
    }

    public static function sanitizeId(string $value): string
    {
        $value = strtolower(trim($value));
        $value = self::asciiSlug($value);

        return $value;
    }

    public static function sanitizeSlug(string $value): string
    {
        $value = trim($value, "/ \t\n\r\0\x0B");
        if ($value === '') {
            return '';
        }

        $parts = array_filter(array_map(
            static fn(string $part): string => self::asciiSlug(strtolower($part)),
            explode('/', $value)
        ), static fn(string $part): bool => $part !== '');

        return implode('/', $parts);
    }

    private static function asciiSlug(string $value): string
    {
        $map = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ü' => 'u', 'Ñ' => 'n',
        ];
        $value = strtr($value, $map);
        $value = preg_replace('/[^a-z0-9\-]+/', '-', $value) ?? '';

        return trim($value, '-');
    }

    public static function projectRoot(): string
    {
        return dirname(__DIR__);
    }

    /**
     * Prefijo público cuando el sitio no está en la raíz del dominio
     * (ej. /prospectos/casa_gatos). "auto" lo detecta con DOCUMENT_ROOT.
     * Cadena vacía fuerza raíz. Al migrar a dominio propio, dejar "" o "auto".
     */
    public static function basePath(): string
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $raw = self::settings()['basePath'] ?? 'auto';
        $raw = is_string($raw) ? trim($raw) : 'auto';

        if ($raw === '') {
            $cached = '';
            return $cached;
        }

        if ($raw !== 'auto') {
            $cached = '/' . trim($raw, '/');
            return $cached;
        }

        $doc = rtrim(str_replace('\\', '/', (string) ($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
        $root = rtrim(str_replace('\\', '/', self::projectRoot()), '/');

        if ($doc !== '' && $root !== '' && str_starts_with($root, $doc)) {
            $base = substr($root, strlen($doc));
            $cached = ($base === '/' || $base === false) ? '' : $base;

            return $cached;
        }

        $cached = '';

        return $cached;
    }

    /**
     * Convierte una ruta interna (/adopciones/) en URL pública, con subcarpeta
     * si el sitio se está previsualizando fuera de la raíz.
     */
    public static function url(string $path): string
    {
        $path = trim($path);
        if ($path === '' || preg_match('~^(https?:|mailto:|tel:|#|javascript:)~i', $path) === 1) {
            return $path;
        }

        $base = self::basePath();
        if ($path === '/') {
            return $base === '' ? '/' : $base . '/';
        }

        $path = '/' . ltrim($path, '/');
        if ($base !== '' && (str_starts_with($path, $base . '/') || $path === $base)) {
            return $path;
        }

        return $base . $path;
    }

    /**
     * Ruta canónica sin prefijo de subcarpeta. El sitemap de producción usa
     * esta forma + siteUrl; los enlaces públicos usan pageUrl().
     */
    public static function pagePath(array $page): string
    {
        if (($page['type'] ?? 'page') === 'link') {
            return (string) ($page['menu']['target'] ?? '/');
        }

        $slug = (string) ($page['slug'] ?? '');

        return $slug === '' ? '/' : '/' . $slug . '/';
    }

    public static function pageUrl(array $page): string
    {
        return self::url(self::pagePath($page));
    }

    public static function pageFilePath(array $page): string
    {
        $slug = (string) ($page['slug'] ?? '');
        $relative = $slug === '' ? 'index.php' : $slug . '/index.php';

        return self::projectRoot() . '/' . $relative;
    }

    public static function pageFileRelative(array $page): string
    {
        $slug = (string) ($page['slug'] ?? '');

        return $slug === '' ? 'index.php' : $slug . '/index.php';
    }

    /**
     * Crea el archivo de plantilla de una página nueva. Si $sourceBody viene
     * dado se usa como cuerpo (para duplicar secciones existentes).
     */
    public static function createPageFile(array $page, ?string $sourceBody = null): array
    {
        $target = self::pageFilePath($page);
        $dir = dirname($target);

        if (is_file($target)) {
            return ['success' => false, 'message' => 'Ya existe un archivo en ' . self::pageFileRelative($page)];
        }

        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return ['success' => false, 'message' => 'No se pudo crear la carpeta de la página.'];
        }

        $body = $sourceBody ?? self::starterBody($page);
        $contents = self::wrapTemplate((string) $page['id'], $body);

        if (file_put_contents($target, $contents, LOCK_EX) === false) {
            return ['success' => false, 'message' => 'No se pudo escribir el archivo de la página.'];
        }

        return ['success' => true, 'path' => self::pageFileRelative($page)];
    }

    /**
     * Línea de arranque que localiza la raíz del proyecto sin importar a qué
     * profundidad viva la página.
     */
    public const BOOT_LINE = '<?php $twRoot = __DIR__; while (!is_file($twRoot . \'/lib/page-boot.php\') && dirname($twRoot) !== $twRoot) { $twRoot = dirname($twRoot); } require_once $twRoot . \'/lib/page-boot.php\'; ?>';

    public static function wrapTemplate(string $pageId, string $body): string
    {
        $id = str_replace("'", "\\'", $pageId);
        $body = rtrim($body, "\r\n");

        return self::BOOT_LINE . "\n"
            . "<?php tw_page_start('" . $id . "'); ?>\n"
            . $body . "\n"
            . "<?php tw_page_end(); ?>\n";
    }

    private static function starterBody(array $page): string
    {
        $title = htmlspecialchars((string) ($page['title'] ?: 'Nueva sección'), ENT_QUOTES, 'UTF-8');
        $description = htmlspecialchars((string) ($page['description'] ?: 'Describe aquí el contenido de esta sección.'), ENT_QUOTES, 'UTF-8');
        $label = htmlspecialchars((string) ($page['menu']['label'] ?: 'Nueva sección'), ENT_QUOTES, 'UTF-8');

        return <<<HTML
  <main id="contenido">
    <section class="page-hero page-hero--plain"><div class="container">
      <p class="breadcrumbs"><a href="<?php echo tw_esc(tw_url('/')); ?>">Inicio</a> / {$label}</p>
      <span class="eyebrow eyebrow--lime">{$label}</span>
      <h1>{$title}</h1>
      <p>{$description}</p>
    </div></section>
    <section class="section"><div class="container">
      <div class="content-block reveal">
        <h2>Contenido de la sección</h2>
        <p>Edita este bloque desde <strong>Control · Código</strong> para construir la sección.</p>
      </div>
    </div></section>
    <section class="section section--tight"><div class="container reveal"><div class="cta-band">
      <div><h2>¿Quieres adoptar o apoyar?</h2><p>Escríbenos y te contamos el siguiente paso. Cada hogar cuenta.</p></div>
      <a class="btn" href="<?php echo tw_esc(tw_url('/contacto/')); ?>">Escríbenos</a>
    </div></div></section>
  </main>
HTML;
    }

    /**
     * Extrae el cuerpo (todo lo que hay entre tw_page_start y tw_page_end) de
     * una plantilla existente, para poder duplicarla.
     */
    public static function extractBody(string $absolutePath): ?string
    {
        if (!is_file($absolutePath)) {
            return null;
        }

        $contents = (string) file_get_contents($absolutePath);
        $startMarker = 'tw_page_start(';
        $endMarker = '<?php tw_page_end(); ?>';

        $startPos = strpos($contents, $startMarker);
        if ($startPos === false) {
            return null;
        }

        $afterStart = strpos($contents, '?>', $startPos);
        if ($afterStart === false) {
            return null;
        }

        $bodyStart = $afterStart + 2;
        $endPos = strpos($contents, $endMarker, $bodyStart);
        $body = $endPos === false
            ? substr($contents, $bodyStart)
            : substr($contents, $bodyStart, $endPos - $bodyStart);

        return trim($body, "\r\n");
    }

    /**
     * Borra la plantilla y, si queda vacía, la carpeta de la página. Devuelve
     * los archivos que quedaron dentro para poder avisar al administrador.
     */
    private static function removePageDirectory(string $slug): array
    {
        $slug = self::sanitizeSlug($slug);
        if ($slug === '') {
            return [];
        }

        $dir = self::projectRoot() . '/' . $slug;
        $file = $dir . '/index.php';

        if (is_file($file)) {
            @unlink($file);
        }

        if (!is_dir($dir)) {
            return [];
        }

        $remaining = self::directoryEntries($dir);

        if ($remaining === []) {
            @rmdir($dir);
        }

        return $remaining;
    }

    private static function directoryEntries(string $dir): array
    {
        $entries = @scandir($dir);
        if ($entries === false) {
            return [];
        }

        return array_values(array_diff($entries, ['.', '..']));
    }

    public static function menuItems(): array
    {
        $items = [];

        foreach (self::pages() as $page) {
            if (($page['status'] ?? '') !== 'published') {
                continue;
            }
            if (empty($page['menu']['show'])) {
                continue;
            }

            $label = trim((string) ($page['menu']['label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $items[] = [
                'id' => (string) ($page['id'] ?? ''),
                'label' => $label,
                'href' => self::pageUrl($page),
                'style' => ($page['menu']['style'] ?? 'link') === 'cta' ? 'cta' : 'link',
                'order' => (int) ($page['menu']['order'] ?? 99),
            ];
        }

        return $items;
    }
}
