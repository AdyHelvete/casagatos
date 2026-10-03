<?php
declare(strict_types=1);

require_once __DIR__ . '/SiteStorage.php';
require_once __DIR__ . '/PageRegistry.php';

/**
 * Construye las partes compartidas de cada página pública: <head> con todos los
 * metadatos SEO, encabezado con menú dinámico y footer.
 */
class PageRenderer
{
    private static ?array $settings = null;
    private static ?array $siteConfig = null;
    private static array $current = [];

    public static function settings(): array
    {
        self::$settings ??= PageRegistry::settings();

        return self::$settings;
    }

    public static function siteConfig(): array
    {
        self::$siteConfig ??= SiteStorage::getSiteConfig();

        return self::$siteConfig;
    }

    public static function current(): array
    {
        return self::$current;
    }

    public static function esc(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    public static function absoluteUrl(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
        if ($host !== '' && PageRegistry::basePath() !== '') {
            return ($https ? 'https://' : 'http://') . $host . PageRegistry::url($path);
        }

        $base = rtrim((string) (self::settings()['siteUrl'] ?? ''), '/');

        return $base . '/' . ltrim($path, '/');
    }

    public static function assetVersion(string $kind): int
    {
        $settings = self::settings();
        $map = [
            'css' => 'cssVersion',
            'js' => 'jsVersion',
            'config' => 'configJsVersion',
        ];
        $key = $map[$kind] ?? 'cssVersion';

        return max(1, (int) ($settings[$key] ?? 1));
    }

    public static function resolve(string $pageId): array
    {
        $page = PageRegistry::find($pageId);

        if ($page === null) {
            $page = array_replace_recursive(PageRegistry::blankPage(), [
                'id' => $pageId,
                'title' => self::settings()['siteName'] ?? 'La Casa de los Gatos',
            ]);
        }

        self::$current = $page;

        return $page;
    }

    /**
     * @param array<string, mixed> $overrides Metadatos que sustituyen a los del
     *        registro; lo usan las fichas de detalle (adopción, campaña, álbum)
     *        que comparten plantilla pero necesitan title/canonical propios.
     */
    public static function startDocument(string $pageId, array $overrides = []): void
    {
        $page = self::resolve($pageId);

        if ($overrides !== []) {
            $page = array_replace($page, $overrides);
            self::$current = $page;
        }

        if (($page['status'] ?? 'published') !== 'published' && !self::isPreview()) {
            self::renderUnavailable();
            exit;
        }

        $settings = self::settings();
        $lang = self::esc((string) ($settings['lang'] ?? 'es-MX'));
        $bodyClass = trim((string) ($page['bodyClass'] ?? ''));

        echo "<!doctype html>\n";
        echo '<html lang="' . $lang . '">' . "\n";
        self::renderHead($page);
        echo '<body' . ($bodyClass !== '' ? ' class="' . self::esc($bodyClass) . '"' : '') . ">\n";
        self::includePartial('gtm-body.html');
        echo '  <a class="skip-link" href="#contenido">Saltar al contenido</a>' . "\n";
        self::renderHeader($page);
    }

    public static function endDocument(): void
    {
        self::renderFooter();
        self::renderWhatsAppFloat();
        self::renderScripts();
        echo "</body>\n</html>\n";
    }

    /**
     * Página 404 pública con el mismo encabezado, footer y estilos del sitio.
     */
    public static function render404(): void
    {
        http_response_code(404);
        $settings = self::settings();
        $name = (string) ($settings['siteName'] ?? 'La Casa de los Gatos');
        $lang = self::esc((string) ($settings['lang'] ?? 'es-MX'));

        self::$current = array_replace(PageRegistry::blankPage(), [
            'id' => '404',
            'title' => 'Página no encontrada · ' . $name,
            'description' => 'La página que buscas no existe o fue movida.',
            'robots' => 'noindex, nofollow',
            'ogType' => 'website',
            'bodyClass' => 'page-404',
        ]);

        echo "<!doctype html>\n";
        echo '<html lang="' . $lang . '">' . "\n";
        self::renderHead(self::$current);
        echo '<body class="page-404">' . "\n";
        self::includePartial('gtm-body.html');
        echo '  <a class="skip-link" href="#contenido">Saltar al contenido</a>' . "\n";
        self::renderHeader(self::$current);
        self::render404Content();
        self::renderFooter();
        self::renderWhatsAppFloat();
        self::renderScripts();
        echo "</body>\n</html>\n";
    }

    /**
     * Permite ver una página en borrador cuando hay sesión de administrador
     * activa, añadiendo ?preview a la URL.
     */
    private static function isPreview(): bool
    {
        if (!isset($_GET['preview'])) {
            return false;
        }

        require_once __DIR__ . '/ControlAuth.php';
        ControlAuth::startSession();

        return ControlAuth::isLoggedIn();
    }

    private static function renderUnavailable(): void
    {
        http_response_code(404);
        $settings = self::settings();
        $lang = self::esc((string) ($settings['lang'] ?? 'es-MX'));
        $name = self::esc((string) ($settings['siteName'] ?? 'La Casa de los Gatos'));

        echo "<!doctype html>\n<html lang=\"$lang\">\n<head><meta charset=\"utf-8\">";
        echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
        echo '<meta name="robots" content="noindex, nofollow">';
        echo "<title>Página no disponible · $name</title>";
        echo '<link rel="stylesheet" href="' . self::esc(PageRegistry::url('/assets/css/style.css')) . '?v=' . self::assetVersion('css') . '">';
        echo "</head>\n<body><main id=\"contenido\"><section class=\"section\"><div class=\"container\">";
        echo '<h1>Página no disponible</h1><p>Esta sección no está publicada por el momento.</p>';
        echo '<p><a class="btn" href="' . self::esc(PageRegistry::url('/')) . '">Volver al inicio</a></p>';
        echo "</div></section></main></body>\n</html>\n";
    }

    private static function renderHead(array $page): void
    {
        $settings = self::settings();
        $url = PageRegistry::pageUrl($page);
        $absolute = self::absoluteUrl($url);

        $title = (string) ($page['title'] ?? '');
        $description = (string) ($page['description'] ?? '');
        $canonical = trim((string) ($page['canonical'] ?? '')) !== ''
            ? self::absoluteUrl((string) $page['canonical'])
            : $absolute;
        $robots = trim((string) ($page['robots'] ?? '')) !== ''
            ? (string) $page['robots']
            : (string) ($settings['defaultRobots'] ?? '');
        $ogImage = trim((string) ($page['ogImage'] ?? '')) !== ''
            ? (string) $page['ogImage']
            : (string) ($settings['defaultOgImage'] ?? '');

        echo "<head>\n";
        echo '  <meta charset="utf-8">' . "\n";
        echo '  <meta name="viewport" content="width=device-width,initial-scale=1">' . "\n";
        self::includePartial('gtm-head.html');
        echo '  <title>' . self::esc($title) . '</title>' . "\n";
        echo '  <meta name="description" content="' . self::esc($description) . '">' . "\n";
        echo '  <meta name="author" content="' . self::esc((string) ($settings['author'] ?? '')) . '">' . "\n";

        if ($robots !== '') {
            echo '  <meta name="robots" content="' . self::esc($robots) . '">' . "\n";
        }

        if (!empty($settings['geoRegion'])) {
            echo '  <meta name="geo.region" content="' . self::esc((string) $settings['geoRegion']) . '">' . "\n";
        }
        if (!empty($settings['geoPlacename'])) {
            echo '  <meta name="geo.placename" content="' . self::esc((string) $settings['geoPlacename']) . '">' . "\n";
        }

        echo '  <link rel="canonical" href="' . self::esc($canonical) . '">' . "\n";
        echo '  <link rel="alternate" type="text/plain" href="' . self::esc(self::absoluteUrl('/llms.txt')) . '" title="LLMs.txt">' . "\n";
        echo '  <link rel="alternate" type="text/plain" href="' . self::esc(self::absoluteUrl('/llms-full.txt')) . '" title="Contenido completo para IA">' . "\n";

        echo '  <meta property="og:type" content="' . self::esc((string) ($page['ogType'] ?: 'website')) . '">' . "\n";
        echo '  <meta property="og:locale" content="' . self::esc((string) ($settings['locale'] ?? 'es_MX')) . '">' . "\n";
        echo '  <meta property="og:site_name" content="' . self::esc((string) ($settings['siteName'] ?? '')) . '">' . "\n";
        echo '  <meta property="og:title" content="' . self::esc((string) ($page['ogTitle'] ?: $title)) . '">' . "\n";
        echo '  <meta property="og:description" content="' . self::esc((string) ($page['ogDescription'] ?: $description)) . '">' . "\n";
        echo '  <meta property="og:url" content="' . self::esc($absolute) . '">' . "\n";

        if ($ogImage !== '') {
            echo '  <meta property="og:image" content="' . self::esc(self::absoluteUrl($ogImage)) . '">' . "\n";
        }

        echo '  <meta name="twitter:card" content="' . self::esc((string) ($page['twitterCard'] ?: 'summary_large_image')) . '">' . "\n";
        echo '  <meta name="twitter:title" content="' . self::esc((string) ($page['twitterTitle'] ?: ($page['ogTitle'] ?: $title))) . '">' . "\n";
        echo '  <meta name="twitter:description" content="' . self::esc((string) ($page['twitterDescription'] ?: ($page['ogDescription'] ?: $description))) . '">' . "\n";

        if ($ogImage !== '') {
            echo '  <meta name="twitter:image" content="' . self::esc(self::absoluteUrl($ogImage)) . '">' . "\n";
        }

        echo '  <meta name="theme-color" content="' . self::esc((string) ($settings['themeColor'] ?? '#071225')) . '">' . "\n";

        $logos = self::siteConfig()['logos'] ?? [];
        $iconPath = PageRegistry::url((string) ($logos['icon'] ?? '/assets/logo/casa_gatos_logo.jpg'));
        $logoVersion = (int) ($logos['version'] ?? 5);
        $iconType = match (strtolower(pathinfo($iconPath, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            'ico' => 'image/x-icon',
            default => 'image/png',
        };
        echo '  <link rel="icon" href="' . self::esc($iconPath . '?v=' . $logoVersion) . '" type="' . $iconType . '">' . "\n";
        echo '  <link rel="apple-touch-icon" href="' . self::esc($iconPath . '?v=' . $logoVersion) . '">' . "\n";

        if (!empty($page['manifest'])) {
            echo '  <link rel="manifest" href="' . self::esc(PageRegistry::url('/site.webmanifest')) . '">' . "\n";
        }

        echo '  <link rel="stylesheet" href="' . self::esc(PageRegistry::url('/assets/css/style.css')) . '?v=' . self::assetVersion('css') . '">' . "\n";

        $extraHead = trim((string) ($page['extraHead'] ?? ''));
        if ($extraHead !== '') {
            echo '  ' . $extraHead . "\n";
        }

        $jsonLd = trim((string) ($page['jsonLd'] ?? ''));
        if ($jsonLd !== '') {
            echo '  <script type="application/ld+json">' . "\n" . $jsonLd . "\n" . '  </script>' . "\n";
        }

        echo "</head>\n";
    }

    /**
     * @return array<int, array{id: string, label: string, href: string, style: string, order: int}>
     */
    private static function navigationItems(): array
    {
        $items = PageRegistry::menuItems();

        if ($items === []) {
            $items = [
                ['id' => 'home', 'label' => 'Inicio', 'href' => PageRegistry::url('/'), 'style' => 'link', 'order' => 10],
                ['id' => 'nosotros', 'label' => 'Nosotros', 'href' => PageRegistry::url('/nosotros/'), 'style' => 'link', 'order' => 20],
                ['id' => 'adopciones', 'label' => 'Adopciones', 'href' => PageRegistry::url('/adopciones/'), 'style' => 'link', 'order' => 30],
                ['id' => 'como-adoptar', 'label' => 'Cómo adoptar', 'href' => PageRegistry::url('/como-adoptar/'), 'style' => 'link', 'order' => 40],
                ['id' => 'campanas', 'label' => 'Campañas', 'href' => PageRegistry::url('/campanas/'), 'style' => 'link', 'order' => 50],
                ['id' => 'galeria', 'label' => 'Galería', 'href' => PageRegistry::url('/galeria/'), 'style' => 'link', 'order' => 60],
                ['id' => 'contacto', 'label' => (string) (self::settings()['ctaLabel'] ?? 'Contacto'), 'href' => PageRegistry::url((string) (self::settings()['ctaHref'] ?? '/contacto/')), 'style' => 'cta', 'order' => 90],
            ];
        }

        usort($items, static fn(array $a, array $b): int => ($a['order'] ?? 99) <=> ($b['order'] ?? 99));

        return $items;
    }

    private static function renderHeader(array $page): void
    {
        $config = self::siteConfig();
        $logos = $config['logos'] ?? [];
        $logo = PageRegistry::url((string) ($logos['primary'] ?? '/assets/logo/casa_gatos_logo.jpg'));
        $logoVersion = (int) ($logos['version'] ?? 5);
        $brand = (string) ($config['brand']['name'] ?? 'La Casa de los Gatos');

        $items = self::navigationItems();
        $links = array_values(array_filter($items, static fn(array $i): bool => $i['style'] !== 'cta'));
        $ctas = array_values(array_filter($items, static fn(array $i): bool => $i['style'] === 'cta'));
        $currentId = (string) ($page['id'] ?? '');

        echo '  <header class="site-header">' . "\n";
        echo '    <nav class="nav container" aria-label="Navegación principal">' . "\n";
        echo '      <a class="logo" href="' . self::esc(PageRegistry::url('/')) . '" aria-label="Inicio">';
        echo '<img src="' . self::esc($logo . '?v=' . $logoVersion) . '" alt="' . self::esc($brand) . '" width="120" height="120">';
        echo '<span class="logo__word">' . self::esc($brand) . '</span></a>' . "\n";
        echo '      <button class="menu-toggle" aria-expanded="false" aria-controls="menu" aria-label="Abrir menú"><span></span></button>' . "\n";
        echo '      <div class="nav-cluster"><ul class="nav-links" id="menu">' . "\n";

        foreach ($links as $item) {
            $aria = $item['id'] === $currentId ? ' aria-current="page"' : '';
            echo '        <li><a href="' . self::esc($item['href']) . '"' . $aria . '>' . self::esc($item['label']) . '</a></li>' . "\n";
        }

        echo '        <li class="nav-social-item"><div class="social-links social-links--header" data-site-social data-site-social-variant="header" aria-label="Redes sociales"></div></li>' . "\n";

        foreach ($ctas as $item) {
            $aria = $item['id'] === $currentId ? ' aria-current="page"' : '';
            echo '        <li><a class="btn" href="' . self::esc($item['href']) . '"' . $aria . '>' . self::esc($item['label']) . '</a></li>' . "\n";
        }

        echo '      </ul></div>' . "\n";
        echo '    </nav>' . "\n";
        echo '  </header>' . "\n";
    }

    public static function renderFooter(): void
    {
        $config = self::siteConfig();
        $logos = $config['logos'] ?? [];
        $contact = $config['contact'] ?? [];
        $footer = $config['footer'] ?? [];
        $brand = (string) ($config['brand']['name'] ?? 'La Casa de los Gatos');
        $logo = PageRegistry::url((string) ($logos['primary'] ?? '/assets/logo/casa_gatos_logo.jpg'));
        $logoVersion = (int) ($logos['version'] ?? 5);

        $explore = array_values(array_filter(
            self::navigationItems(),
            static fn(array $i): bool => $i['style'] !== 'cta' && $i['href'] !== '/'
        ));

        $privacyLabel = (string) ($footer['privacyLabel'] ?? 'Aviso de privacidad');
        $phone = (string) ($contact['phone'] ?? '');
        $phoneDisplay = (string) ($contact['phoneDisplay'] ?? '');
        $phoneIntl = (string) ($contact['phoneDisplayIntl'] ?? $phoneDisplay);
        $email = (string) ($contact['email'] ?? '');
        $location = (string) ($contact['location'] ?? '');
        $phoneHref = 'tel:' . preg_replace('/\s+/', '', $phone);

        echo '  <footer class="site-footer"><div class="container"><div class="footer-grid">' . "\n";
        echo '    <div class="footer-brand"><a class="logo" href="' . self::esc(PageRegistry::url('/')) . '">';
        echo '<img src="' . self::esc($logo . '?v=' . $logoVersion) . '" alt="' . self::esc($brand) . '" width="120" height="120">';
        echo '<span class="logo__word">' . self::esc($brand) . '</span></a>';
        echo '<p data-site-footer-tagline>' . self::esc((string) ($footer['tagline'] ?? '')) . '</p>';
        echo '<div class="social-links social-links--footer" data-site-social data-site-social-variant="footer" aria-label="Redes sociales"></div></div>' . "\n";

        echo '    <div><p class="footer-title">Explora</p><ul class="footer-links">';
        foreach ($explore as $item) {
            echo '<li><a href="' . self::esc($item['href']) . '">' . self::esc($item['label']) . '</a></li>';
        }
        echo '</ul></div>' . "\n";

        echo '    <div><p class="footer-title">Legal</p><ul class="footer-links"><li><a href="' . self::esc(PageRegistry::url('/aviso-de-privacidad/')) . '" data-site-privacy-link data-site-privacy-label>' . self::esc($privacyLabel) . '</a></li></ul></div>' . "\n";

        echo '    <div><p class="footer-title">Contacto</p><ul class="footer-links">';
        echo '<li><a href="' . self::esc($phoneHref) . '" data-site-phone-link><span data-site-phone>' . self::esc($phoneDisplay) . '</span></a></li>';
        echo '<li><a href="mailto:' . self::esc($email) . '" data-site-email-link><span data-site-email>' . self::esc($email) . '</span></a></li>';
        echo '<li><span data-site-location>' . self::esc($location) . '</span></li>';
        echo '</ul></div>' . "\n";

        echo '  </div><aside class="geo-entity" aria-label="Información verificada de ' . self::esc($brand) . '"><p><strong data-site-brand>' . self::esc($brand) . '</strong> ';
        echo '<span data-site-geo-text>' . self::esc((string) ($footer['geoText'] ?? '')) . '</span> Contacto: ';
        echo '<a href="' . self::esc($phoneHref) . '" data-site-phone-link><span data-site-phone-intl>' . self::esc($phoneIntl) . '</span></a> · ';
        echo '<a href="mailto:' . self::esc($email) . '" data-site-email-link><span data-site-email>' . self::esc($email) . '</span></a> · ';
        echo '<a href="' . self::esc(PageRegistry::url('/llms.txt')) . '">llms.txt</a></p></aside>' . "\n";

        echo '  <div class="footer-bottom"><span>&copy; <span data-year>' . date('Y') . '</span> <span data-site-brand>' . self::esc($brand) . '</span></span>';
        echo '<span><a href="' . self::esc(PageRegistry::url('/aviso-de-privacidad/')) . '" data-site-privacy-link data-site-privacy-label>' . self::esc($privacyLabel) . '</a> · ';
        echo '<span data-site-footer-note>' . self::esc((string) ($footer['bottomNote'] ?? '')) . '</span></span></div>' . "\n";
        echo '  </div></footer>' . "\n";
    }

    private static function renderWhatsAppFloat(): void
    {
        $whatsapp = self::siteConfig()['whatsapp'] ?? [];
        $phone = preg_replace('/\D+/', '', (string) ($whatsapp['phone'] ?? '')) ?? '';

        if ($phone === '') {
            return;
        }

        $message = rawurlencode((string) ($whatsapp['defaultMessage'] ?? ''));
        $label = (string) ($whatsapp['floatLabel'] ?? '');
        $href = 'https://wa.me/' . $phone . '?text=' . $message;

        echo '  <a class="whatsapp-float" href="' . self::esc($href) . '" target="_blank" rel="noopener noreferrer" aria-label="Contactar por WhatsApp">';
        echo '<span class="whatsapp-float__label">' . self::esc($label) . '</span>';
        echo '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg></a>' . "\n";
    }

    private static function render404Content(): void
    {
        $ctaHref = PageRegistry::url((string) (self::settings()['ctaHref'] ?? '/contacto/'));
        $ctaLabel = (string) (self::settings()['ctaLabel'] ?? 'Hablemos');
        $home = PageRegistry::url('/');
        $quickLinks = array_values(array_filter(
            self::navigationItems(),
            static fn(array $item): bool => $item['href'] !== $home
        ));

        echo '  <main id="contenido">' . "\n";
        echo '    <section class="page-hero page-hero--plain page-hero--404">' . "\n";
        echo '      <div class="container">' . "\n";
        echo '        <p class="breadcrumbs"><a href="' . self::esc(PageRegistry::url('/')) . '">Inicio</a> / Error 404</p>' . "\n";
        echo '        <p class="error-page__code" aria-hidden="true">404</p>' . "\n";
        echo '        <span class="eyebrow eyebrow--lime">Página no encontrada</span>' . "\n";
        echo '        <h1>Esta ruta no existe <span class="accent">en nuestro sitio.</span></h1>' . "\n";
        echo '        <p>Puede que el enlace esté desactualizado, que hayas escrito mal la dirección o que la página se haya movido.</p>' . "\n";
        echo '        <div class="page-hero__actions button-row">' . "\n";
        echo '          <a class="btn btn--lime" href="' . self::esc(PageRegistry::url('/')) . '">Volver al inicio</a>' . "\n";
        echo '          <a class="btn btn--ghost" href="' . self::esc($ctaHref) . '">' . self::esc($ctaLabel) . '</a>' . "\n";
        echo '        </div>' . "\n";
        echo '      </div>' . "\n";
        echo '    </section>' . "\n";

        echo '    <section class="section section--tight">' . "\n";
        echo '      <div class="container">' . "\n";
        echo '        <div class="section-heading reveal">' . "\n";
        echo '          <div><span class="eyebrow">¿A dónde quieres ir?</span><h2>Explora el sitio</h2></div>' . "\n";
        echo '          <p>Estas son las secciones principales de ' . self::esc((string) (self::settings()['siteName'] ?? 'La Casa de los Gatos')) . '.</p>' . "\n";
        echo '        </div>' . "\n";
        echo '        <div class="error-links reveal">' . "\n";

        foreach ($quickLinks as $item) {
            echo '          <a class="error-links__card" href="' . self::esc($item['href']) . '">';
            echo '<span class="error-links__label">' . self::esc($item['label']) . '</span>';
            echo '<span class="error-links__hint">Ir a la sección</span></a>' . "\n";
        }

        echo '        </div>' . "\n";
        echo '      </div>' . "\n";
        echo '    </section>' . "\n";

        echo '    <section class="section section--tight"><div class="container reveal"><div class="cta-band">' . "\n";
        echo '      <div><h2>¿Buscabas ayuda con tu proyecto?</h2><p>Cuéntanos qué necesitas y te orientamos con la mejor opción para tu negocio.</p></div>' . "\n";
        echo '      <a class="btn" href="' . self::esc($ctaHref) . '">' . self::esc($ctaLabel) . '</a>' . "\n";
        echo '    </div></div></section>' . "\n";
        echo '  </main>' . "\n";
    }

    private static function renderScripts(): void
    {
        echo '  <script>window.TW_BASE=' . json_encode(PageRegistry::basePath(), JSON_UNESCAPED_SLASHES) . ';</script>' . "\n";
        echo '  <script src="' . self::esc(PageRegistry::url('/assets/js/site-config.js')) . '?v=' . self::assetVersion('config') . '" defer></script>' . "\n";
        echo '  <script src="' . self::esc(PageRegistry::url('/assets/js/main.js')) . '?v=' . self::assetVersion('js') . '" defer></script>' . "\n";
    }

    private static function includePartial(string $filename): void
    {
        $path = PageRegistry::projectRoot() . '/assets/partials/' . $filename;
        if (is_file($path)) {
            readfile($path);
        }
    }
}
