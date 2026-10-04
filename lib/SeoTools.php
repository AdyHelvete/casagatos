<?php
declare(strict_types=1);

require_once __DIR__ . '/PageRegistry.php';
require_once __DIR__ . '/ContentCollection.php';

/**
 * Genera sitemap.xml a partir del registro de páginas y ofrece utilidades para
 * revisar el estado SEO de cada sección.
 */
class SeoTools
{
    public static function sitemapPath(): string
    {
        return PageRegistry::projectRoot() . '/sitemap.xml';
    }

    public static function robotsPath(): string
    {
        return PageRegistry::projectRoot() . '/robots.txt';
    }

    /**
     * URLs que entran al sitemap, en el orden del menú.
     */
    public static function sitemapEntries(): array
    {
        $registry = PageRegistry::all();
        $base = rtrim((string) ($registry['settings']['siteUrl'] ?? ''), '/');
        $entries = [];

        foreach ($registry['pages'] as $page) {
            if (($page['status'] ?? '') !== 'published') {
                continue;
            }
            if (empty($page['sitemap']['include'])) {
                continue;
            }

            $robots = (string) ($page['robots'] ?? '');
            if (stripos($robots, 'noindex') !== false) {
                continue;
            }

            $path = PageRegistry::pagePath($page);
            if ($path === '' || str_starts_with($path, 'http')) {
                $loc = $path === '' ? $base . '/' : $path;
            } else {
                $loc = $base . $path;
            }

            $entries[] = [
                'loc' => $loc,
                'lastmod' => self::resolveLastmod($page),
                'changefreq' => (string) ($page['sitemap']['changefreq'] ?? 'monthly'),
                'priority' => (string) ($page['sitemap']['priority'] ?? '0.6'),
            ];
        }

        // Fichas con URL propia (gatos en adopción y jornadas), siempre que su
        // página contenedora esté publicada.
        foreach (['adoptions' => 'adopcion', 'campaigns' => 'tnr'] as $type => $parentId) {
            $parent = PageRegistry::find($parentId);
            if ($parent === null || ($parent['status'] ?? '') !== 'published') {
                continue;
            }

            foreach (ContentCollection::published($type) as $item) {
                $entries[] = [
                    'loc' => $base . ContentCollection::itemPath($type, $item),
                    'lastmod' => substr((string) ($item['updatedAt'] ?? ''), 0, 10),
                    'changefreq' => 'monthly',
                    'priority' => '0.5',
                ];
            }
        }

        return $entries;
    }

    private static function resolveLastmod(array $page): string
    {
        $lastmod = trim((string) ($page['sitemap']['lastmod'] ?? ''));
        if ($lastmod !== '') {
            return $lastmod;
        }

        if (($page['type'] ?? 'page') === 'page') {
            $file = PageRegistry::pageFilePath($page);
            if (is_file($file)) {
                return date('Y-m-d', (int) filemtime($file));
            }
        }

        return '';
    }

    public static function buildSitemap(): string
    {
        $lines = [];
        $lines[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $lines[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach (self::sitemapEntries() as $entry) {
            $url = '  <url><loc>' . htmlspecialchars($entry['loc'], ENT_QUOTES, 'UTF-8') . '</loc>';

            if ($entry['lastmod'] !== '') {
                $url .= '<lastmod>' . htmlspecialchars($entry['lastmod'], ENT_QUOTES, 'UTF-8') . '</lastmod>';
            }
            if ($entry['changefreq'] !== '') {
                $url .= '<changefreq>' . htmlspecialchars($entry['changefreq'], ENT_QUOTES, 'UTF-8') . '</changefreq>';
            }
            if ($entry['priority'] !== '') {
                $url .= '<priority>' . htmlspecialchars($entry['priority'], ENT_QUOTES, 'UTF-8') . '</priority>';
            }

            $lines[] = $url . '</url>';
        }

        $lines[] = '</urlset>';

        return implode("\n", $lines) . "\n";
    }

    public static function writeSitemap(): array
    {
        $xml = self::buildSitemap();
        $path = self::sitemapPath();

        if (file_put_contents($path, $xml, LOCK_EX) === false) {
            return ['success' => false, 'message' => 'No se pudo escribir sitemap.xml. Revisa permisos de escritura en la raíz.'];
        }

        $count = substr_count($xml, '<url>');

        return ['success' => true, 'message' => "sitemap.xml regenerado con $count URL(s)."];
    }

    /**
     * Revisión rápida de cada página: campos faltantes o longitudes fuera de rango.
     */
    public static function audit(): array
    {
        $report = [];

        foreach (PageRegistry::pages() as $page) {
            if (($page['type'] ?? 'page') !== 'page') {
                continue;
            }

            $issues = [];
            $title = trim((string) ($page['title'] ?? ''));
            $description = trim((string) ($page['description'] ?? ''));

            if ($title === '') {
                $issues[] = 'Falta el título.';
            } elseif (mb_strlen($title) > 65) {
                $issues[] = 'Título largo (' . mb_strlen($title) . ' caracteres, ideal ≤ 60).';
            } elseif (mb_strlen($title) < 20) {
                $issues[] = 'Título corto (' . mb_strlen($title) . ' caracteres).';
            }

            if ($description === '') {
                $issues[] = 'Falta la meta description.';
            } elseif (mb_strlen($description) > 165) {
                $issues[] = 'Description larga (' . mb_strlen($description) . ' caracteres, ideal ≤ 160).';
            } elseif (mb_strlen($description) < 70) {
                $issues[] = 'Description corta (' . mb_strlen($description) . ' caracteres).';
            }

            if (trim((string) ($page['ogImage'] ?? '')) === '') {
                $issues[] = 'Sin imagen para redes sociales (og:image).';
            }

            if (trim((string) ($page['jsonLd'] ?? '')) === '') {
                $issues[] = 'Sin datos estructurados (JSON-LD).';
            } elseif (json_decode((string) $page['jsonLd'], true) === null) {
                $issues[] = 'El JSON-LD no es JSON válido.';
            }

            if (!is_file(PageRegistry::pageFilePath($page))) {
                $issues[] = 'Falta el archivo ' . PageRegistry::pageFileRelative($page) . '.';
            }

            $report[] = [
                'id' => (string) ($page['id'] ?? ''),
                'url' => PageRegistry::pageUrl($page),
                'label' => (string) ($page['menu']['label'] ?? $page['id']),
                'issues' => $issues,
            ];
        }

        return $report;
    }
}
