<?php
declare(strict_types=1);

require_once __DIR__ . '/PageRegistry.php';

/**
 * Migra las páginas estáticas (.html con SSI) al registro de páginas y genera
 * las plantillas index.php equivalentes. Los archivos .html originales se
 * conservan como respaldo: Apache sirve index.php primero.
 */
class PageImporter
{
    private const SKIP_DIRS = [
        'assets', 'api', 'control', 'data', 'lib', 'backups',
        'node_modules', '.git', '.github', '.vscode', 'vendor',
    ];

    /** Slugs que no deben eliminarse ni renombrarse desde el panel. */
    private const SYSTEM_SLUGS = ['', 'tnr', 'adopcion', 'asistencia', 'directorio', 'contacto', 'aviso-de-privacidad'];

    /**
     * Slugs de primer nivel que tienen un index.html o index.php.
     */
    public static function scan(): array
    {
        $root = PageRegistry::projectRoot();
        $slugs = [];

        if (self::hasIndex($root, '')) {
            $slugs[] = '';
        }

        foreach ((array) glob($root . '/*', GLOB_ONLYDIR) as $dir) {
            $name = basename((string) $dir);
            if (in_array($name, self::SKIP_DIRS, true) || str_starts_with($name, '.') || str_starts_with($name, '_')) {
                continue;
            }

            if (self::hasIndex($root, $name)) {
                $slugs[] = $name;
            }
        }

        return $slugs;
    }

    private static function hasIndex(string $root, string $slug): bool
    {
        $base = $slug === '' ? $root : $root . '/' . $slug;

        return is_file($base . '/index.php') || is_file($base . '/index.html');
    }

    private static function pathFor(string $slug, string $filename): string
    {
        $root = PageRegistry::projectRoot();

        return ($slug === '' ? $root : $root . '/' . $slug) . '/' . $filename;
    }

    private static function isWrapped(string $path): bool
    {
        if (!is_file($path)) {
            return false;
        }

        return str_contains((string) file_get_contents($path), 'tw_page_start(');
    }

    /**
     * Archivo del que se leen metadatos y contenido. Devuelve null cuando la
     * página ya está migrada y no queda ningún original por leer.
     */
    private static function metadataSource(string $slug, bool $allowLegacyFallback): ?string
    {
        $template = self::pathFor($slug, 'index.php');
        $legacy = self::pathFor($slug, 'index.html');

        if (is_file($template)) {
            if (!self::isWrapped($template)) {
                return $template;
            }

            // La plantilla ya está migrada: sólo se relee el original cuando se
            // pide regenerar de forma explícita.
            return $allowLegacyFallback && is_file($legacy) ? $legacy : null;
        }

        return is_file($legacy) ? $legacy : null;
    }

    /**
     * Ejecuta la importación. $overwriteTemplates fuerza regenerar index.php
     * incluso si ya existe.
     */
    public static function run(bool $overwriteTemplates = false): array
    {
        $root = PageRegistry::projectRoot();
        $navLabels = self::parseNav($root);

        $registry = PageRegistry::all();
        $existing = [];
        foreach ($registry['pages'] as $page) {
            $existing[(string) ($page['id'] ?? '')] = $page;
        }

        $imported = [];
        $skipped = [];
        $order = 1;

        foreach (self::orderSlugs(self::scan(), $navLabels) as $slug) {
            $url = self::hrefFor($slug);
            $id = $slug === '' ? 'home' : PageRegistry::sanitizeId($slug);
            $sourceFile = self::metadataSource($slug, $overwriteTemplates);

            if ($sourceFile === null) {
                if (!isset($existing[$id])) {
                    $skipped[] = $url . ' (la plantilla ya está migrada pero no está registrada)';
                }
                continue;
            }

            $html = (string) @file_get_contents($sourceFile);

            if ($html === '') {
                $skipped[] = $url . ' (archivo vacío o ilegible)';
                continue;
            }

            $meta = self::extractMeta($html);
            $body = self::extractMain($html);

            if ($body === null) {
                $skipped[] = $url . ' (no se encontró <main>)';
                continue;
            }

            $label = $navLabels[$url]['label']
                ?? ($existing[$id]['menu']['label'] ?? self::labelFromSlug($slug));
            $style = $navLabels[$url]['style'] ?? 'link';
            $inNav = isset($navLabels[$url]);

            $page = array_replace_recursive(
                PageRegistry::blankPage(),
                $existing[$id] ?? [],
                [
                    'id' => $id,
                    'type' => 'page',
                    'slug' => $slug,
                    'status' => 'published',
                    'system' => in_array($slug, self::SYSTEM_SLUGS, true),
                    'title' => $meta['title'],
                    'description' => $meta['description'],
                    'canonical' => $meta['canonical'],
                    'robots' => $meta['robots'],
                    'ogType' => $meta['ogType'] ?: 'website',
                    'ogTitle' => $meta['ogTitle'],
                    'ogDescription' => $meta['ogDescription'],
                    'ogImage' => $meta['ogImage'],
                    'twitterCard' => $meta['twitterCard'] ?: 'summary_large_image',
                    'twitterTitle' => $meta['twitterTitle'],
                    'twitterDescription' => $meta['twitterDescription'],
                    'manifest' => $meta['manifest'],
                    'jsonLd' => $meta['jsonLd'],
                    'extraHead' => $meta['extraHead'] !== ''
                        ? $meta['extraHead']
                        : (string) ($existing[$id]['extraHead'] ?? ''),
                    'menu' => [
                        'show' => $inNav,
                        'label' => $label,
                        'order' => $navLabels[$url]['order'] ?? ($order * 10),
                        'style' => $style,
                        'target' => '',
                    ],
                    'sitemap' => [
                        'include' => true,
                        'changefreq' => self::changefreqFor($slug),
                        'priority' => self::priorityFor($slug),
                        'lastmod' => date('Y-m-d', (int) @filemtime($sourceFile) ?: time()),
                    ],
                ]
            );

            $targetFile = PageRegistry::pageFilePath($page);
            $alreadyWrapped = self::isWrapped($targetFile);

            if (!$alreadyWrapped || $overwriteTemplates) {
                if (is_file($targetFile)) {
                    self::backupFile($targetFile);
                }

                $dir = dirname($targetFile);
                if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                    $skipped[] = $url . ' (no se pudo crear la carpeta)';
                    continue;
                }

                if (file_put_contents($targetFile, PageRegistry::wrapTemplate($id, $body), LOCK_EX) === false) {
                    $skipped[] = $url . ' (no se pudo escribir ' . PageRegistry::pageFileRelative($page) . ')';
                    continue;
                }
            }

            $existing[$id] = $page;
            $imported[] = $url;
            $order++;
        }

        $registry['pages'] = array_values($existing);

        if (!PageRegistry::save($registry)) {
            return ['success' => false, 'message' => 'No se pudo guardar data/pages.json', 'imported' => [], 'skipped' => $skipped];
        }

        return [
            'success' => true,
            'imported' => $imported,
            'skipped' => $skipped,
            'message' => count($imported) . ' página(s) registradas.',
        ];
    }

    private static function orderSlugs(array $slugs, array $navLabels): array
    {
        usort($slugs, static function (string $a, string $b) use ($navLabels): int {
            $oa = $navLabels[self::hrefFor($a)]['order'] ?? 900;
            $ob = $navLabels[self::hrefFor($b)]['order'] ?? 900;

            return $oa <=> $ob ?: strcmp($a, $b);
        });

        return $slugs;
    }

    private static function hrefFor(string $slug): string
    {
        return $slug === '' ? '/' : '/' . $slug . '/';
    }

    private static function labelFromSlug(string $slug): string
    {
        if ($slug === '') {
            return 'Inicio';
        }

        return ucfirst(str_replace('-', ' ', $slug));
    }

    private static function changefreqFor(string $slug): string
    {
        return match ($slug) {
            '' => 'weekly',
            'aviso-de-privacidad' => 'yearly',
            default => 'monthly',
        };
    }

    private static function priorityFor(string $slug): string
    {
        return match ($slug) {
            '' => '1.0',
            'servicios' => '0.9',
            'aviso-de-privacidad' => '0.3',
            default => '0.8',
        };
    }

    /**
     * Lee el menú del index.html original para conservar etiquetas y orden.
     */
    private static function parseNav(string $root): array
    {
        foreach (['index.html', 'index.php'] as $filename) {
            $path = $root . '/' . $filename;
            if (!is_file($path)) {
                continue;
            }

            $html = (string) @file_get_contents($path);
            if (!preg_match('#<ul class="nav-links"[^>]*>(.*?)</ul>#s', $html, $listMatch)) {
                continue;
            }

            preg_match_all('#<a\s+([^>]*)>(.*?)</a>#s', $listMatch[1], $matches, PREG_SET_ORDER);
            $items = [];
            $order = 10;

            foreach ($matches as $match) {
                $attrs = $match[1];
                $label = trim(strip_tags($match[2]));

                if (!preg_match('#href="([^"]+)"#', $attrs, $hrefMatch) || $label === '') {
                    continue;
                }

                $href = $hrefMatch[1];
                $items[$href] = [
                    'label' => $label,
                    'order' => $order,
                    'style' => str_contains($attrs, 'btn--lime') ? 'cta' : 'link',
                ];
                $order += 10;
            }

            if ($items !== []) {
                return $items;
            }
        }

        return [];
    }

    /**
     * Extrae los metadatos del <head>.
     */
    public static function extractMeta(string $html): array
    {
        $head = $html;
        if (preg_match('#<head\b[^>]*>(.*?)</head>#s', $html, $headMatch)) {
            $head = $headMatch[1];
        }

        $structured = self::extractJsonLd($head);

        return [
            'jsonLd' => $structured['primary'],
            'extraHead' => $structured['extra'],
            'title' => self::firstMatch('#<title>(.*?)</title>#s', $head),
            'description' => self::metaContent($head, 'name', 'description'),
            'robots' => self::metaContent($head, 'name', 'robots'),
            'canonical' => self::firstMatch('#<link[^>]+rel="canonical"[^>]+href="([^"]*)"#i', $head),
            'ogType' => self::metaContent($head, 'property', 'og:type'),
            'ogTitle' => self::metaContent($head, 'property', 'og:title'),
            'ogDescription' => self::metaContent($head, 'property', 'og:description'),
            'ogImage' => self::metaContent($head, 'property', 'og:image'),
            'twitterCard' => self::metaContent($head, 'name', 'twitter:card'),
            'twitterTitle' => self::metaContent($head, 'name', 'twitter:title'),
            'twitterDescription' => self::metaContent($head, 'name', 'twitter:description'),
            'manifest' => (bool) preg_match('#<link[^>]+rel="manifest"#i', $head),
        ];
    }

    private static function metaContent(string $head, string $attr, string $value): string
    {
        $quoted = preg_quote($value, '#');
        $patterns = [
            '#<meta[^>]+' . preg_quote($attr, '#') . '="' . $quoted . '"[^>]*content="([^"]*)"#i',
            '#<meta[^>]+content="([^"]*)"[^>]*' . preg_quote($attr, '#') . '="' . $quoted . '"#i',
        ];

        foreach ($patterns as $pattern) {
            $found = self::firstMatch($pattern, $head);
            if ($found !== '') {
                return $found;
            }
        }

        return '';
    }

    private static function firstMatch(string $pattern, string $subject): string
    {
        if (preg_match($pattern, $subject, $match)) {
            return trim(html_entity_decode($match[1], ENT_QUOTES, 'UTF-8'));
        }

        return '';
    }

    /**
     * El registro guarda un único bloque JSON-LD editable. Si la página tenía
     * más de uno, los adicionales se conservan como etiquetas sueltas del head.
     */
    private static function extractJsonLd(string $head): array
    {
        if (!preg_match_all('#<script[^>]+type="application/ld\+json"[^>]*>(.*?)</script>#s', $head, $matches)) {
            return ['primary' => '', 'extra' => ''];
        }

        $blocks = array_values(array_filter(
            array_map('trim', $matches[1]),
            static fn(string $block): bool => $block !== ''
        ));

        if ($blocks === []) {
            return ['primary' => '', 'extra' => ''];
        }

        $primary = array_shift($blocks);
        $extra = implode("\n", array_map(
            static fn(string $block): string => '<script type="application/ld+json">' . $block . '</script>',
            $blocks
        ));

        return ['primary' => $primary, 'extra' => $extra];
    }

    /**
     * Extrae el bloque <main> ... </main> que constituye el cuerpo editable.
     */
    public static function extractMain(string $html): ?string
    {
        $start = stripos($html, '<main');
        if ($start === false) {
            return null;
        }

        $endTag = '</main>';
        $end = strripos($html, $endTag);
        if ($end === false || $end < $start) {
            return null;
        }

        $body = substr($html, $start, ($end - $start) + strlen($endTag));

        return "  " . trim(self::transformBody($body));
    }

    /**
     * Sustituye fragmentos que ahora se generan desde la configuración, para
     * que dejen de estar escritos a mano en la plantilla.
     */
    private static function transformBody(string $body): string
    {
        // Lista de servicios del formulario de contacto.
        $body = preg_replace(
            '#(<select[^>]*name="servicio"[^>]*>).*?(</select>)#s',
            '$1<option value="">Selecciona una opción</option><?php echo tw_service_options(); ?>$2',
            $body
        ) ?? $body;

        return $body;
    }

    public static function backupsDir(): string
    {
        return PageRegistry::projectRoot() . '/backups/pages';
    }

    public static function backupFile(string $absolutePath): ?string
    {
        if (!is_file($absolutePath)) {
            return null;
        }

        $dir = self::backupsDir();
        if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
            return null;
        }

        $root = PageRegistry::projectRoot();
        $relative = ltrim(str_replace([$root, '\\'], ['', '/'], $absolutePath), '/');
        $name = str_replace('/', '__', $relative) . '.' . date('Ymd-His') . '.bak';
        $target = $dir . '/' . $name;

        return copy($absolutePath, $target) ? $target : null;
    }
}
