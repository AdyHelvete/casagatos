<?php
declare(strict_types=1);

require_once __DIR__ . '/SiteStorage.php';

/**
 * Políticas CSP por perfil y configuración de modo (off / report-only / enforce).
 */
class SecurityPolicy
{
    public const CONFIG_FILE = 'security-config.json';

    /** @var array<string, string> */
    private const PROFILES = [
        'public' => "default-src 'self'; script-src 'self' 'unsafe-inline' https://www.googletagmanager.com https://tagmanager.google.com https://www.google-analytics.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; img-src 'self' data: https: blob:; connect-src 'self' https://www.google-analytics.com https://analytics.google.com https://region1.google-analytics.com https://www.googletagmanager.com https://stats.g.doubleclick.net; frame-src 'self' https://www.googletagmanager.com https://www.youtube.com https://www.youtube-nocookie.com https://player.vimeo.com; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'; upgrade-insecure-requests",
        'admin-control' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; font-src 'self' data:; img-src 'self' data:; connect-src 'self'; frame-src 'none'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'",
    ];

    public static function defaults(): array
    {
        return [
            'csp' => [
                'mode' => 'report-only',
            ],
            'secrets' => [
                'contactRotatedAt' => null,
            ],
        ];
    }

    public static function config(): array
    {
        static $config = null;
        if ($config !== null) {
            return $config;
        }

        $config = array_replace_recursive(
            self::defaults(),
            SiteStorage::read(self::CONFIG_FILE, self::defaults())
        );

        return $config;
    }

    public static function saveConfig(array $config): bool
    {
        return SiteStorage::write(self::CONFIG_FILE, $config);
    }

    public static function cspMode(): string
    {
        $mode = (string) (self::config()['csp']['mode'] ?? 'report-only');

        return in_array($mode, ['off', 'report-only', 'enforce'], true) ? $mode : 'report-only';
    }

    public static function policy(string $profile): string
    {
        $base = self::PROFILES[$profile] ?? self::PROFILES['public'];
        $reportUri = self::reportUri();

        return $reportUri !== '' ? $base . '; report-uri ' . $reportUri : $base;
    }

    public static function apply(string $profile): void
    {
        if (headers_sent()) {
            return;
        }

        $mode = self::cspMode();
        if ($mode === 'off') {
            return;
        }

        $header = $mode === 'enforce'
            ? 'Content-Security-Policy'
            : 'Content-Security-Policy-Report-Only';

        header($header . ': ' . self::policy($profile), true);
    }

    public static function reportUri(): string
    {
        $settings = [];
        if (class_exists('PageRegistry', false)) {
            require_once __DIR__ . '/PageRegistry.php';
            $settings = PageRegistry::settings();
        }

        $siteUrl = rtrim((string) ($settings['siteUrl'] ?? 'https://lacasadelosgatos.mx'), '/');

        return $siteUrl . '/api/csp-report.php';
    }

    /**
     * @return list<string>
     */
    public static function profileLabels(): array
    {
        return [
            'public' => 'Sitio público',
            'admin-control' => 'Panel de control',
        ];
    }
}
