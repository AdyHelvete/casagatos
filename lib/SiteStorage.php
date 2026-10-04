<?php
declare(strict_types=1);

class SiteStorage
{
    public static function dataDir(): string
    {
        return dirname(__DIR__) . '/data';
    }

    public static function read(string $filename, array $default = []): array
    {
        $path = self::path($filename);
        if (!is_file($path)) {
            return $default;
        }

        $raw = file_get_contents($path);
        if ($raw === false || trim($raw) === '') {
            return $default;
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : $default;
    }

    public static function write(string $filename, array $data): bool
    {
        $dir = self::dataDir();
        if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
            return false;
        }

        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($encoded === false) {
            return false;
        }

        return file_put_contents(self::path($filename), $encoded . "\n", LOCK_EX) !== false;
    }

    public static function path(string $filename): string
    {
        return self::dataDir() . '/' . ltrim($filename, '/');
    }

    public static function defaultSiteConfig(): array
    {
        return [
            'brand' => ['name' => 'La Casa de los Gatos'],
            'logos' => [
                'primary' => '/assets/logo/casa_gatos_logo.jpg',
                'icon' => '/assets/logo/casa_gatos_logo.jpg',
                'version' => 1,
            ],
            'contact' => [
                'phone' => '+527791234567',
                'phoneDisplay' => '779 123 4567',
                'phoneDisplayIntl' => '+52 779 123 4567',
                'email' => 'contacto@lacasadelosgatos.org',
                'location' => 'Tizayuca, Hidalgo',
            ],
            'whatsapp' => [
                'phone' => '527791234567',
                'defaultMessage' => 'Hola La Casa de los Gatos, necesito orientación sobre adopción, esterilización o un caso.',
                'floatLabel' => '¿Necesitas orientación?',
            ],
            'social' => [
                'facebook' => 'https://www.facebook.com/mx.lacasadelosgatos',
                'instagram' => 'https://www.instagram.com/lacasadelosgatos',
                'tiktok' => 'https://www.tiktok.com/@lacasadelosgatos',
            ],
            'footer' => [
                'tagline' => 'TNR, adopción responsable y orientación en Tizayuca y la zona. #AdoptaNoCompres',
                'geoText' => 'es un proyecto vecinal de Tizayuca, Hidalgo, dedicado al control ético de gatos de calle (TNR), la adopción responsable, la orientación para rescates y denuncias, y un directorio de clínicas veterinarias de Tizayuca y Zumpango.',
                'privacyLabel' => 'Aviso de privacidad',
                'bottomNote' => 'Adopta, no compres.',
            ],
            'forms' => [
                'contact' => [
                    'services' => [
                        'Registro a una jornada de esterilización o TNR',
                        'Orientación sobre un caso',
                        'Denuncia de maltrato',
                        'Dudas sobre adopción',
                        'Sugerir una clínica para el directorio',
                        'Otro',
                    ],
                    'minSeconds' => 3,
                    'maxAgeSeconds' => 7200,
                    'tokenTtl' => 3600,
                    'rateHour' => 5,
                    'rateDay' => 20,
                    'minMessageLength' => 10,
                    'maxLinks' => 3,
                    'whatsappHandoff' => true,
                    'successMessage' => 'Gracias. Te contactaremos pronto. Mientras tanto síguenos en Facebook.',
                    'notifyEmail' => '',
                ],
            ],
        ];
    }

    /**
     * Configuración del formulario de contacto con valores saneados para uso
     * directo en la API pública.
     */
    public static function contactFormConfig(): array
    {
        $defaults = self::defaultSiteConfig()['forms']['contact'];
        $config = self::getSiteConfig()['forms']['contact'] ?? [];
        $config = array_replace($defaults, is_array($config) ? $config : []);

        $services = array_values(array_filter(array_map(
            static fn($service): string => trim((string) $service),
            is_array($config['services'] ?? null) ? $config['services'] : []
        ), static fn(string $service): bool => $service !== ''));

        if ($services === []) {
            $services = $defaults['services'];
        }

        return [
            'services' => $services,
            'minSeconds' => max(0, (int) $config['minSeconds']),
            'maxAgeSeconds' => max(60, (int) $config['maxAgeSeconds']),
            'tokenTtl' => max(60, (int) $config['tokenTtl']),
            'rateHour' => max(1, (int) $config['rateHour']),
            'rateDay' => max(1, (int) $config['rateDay']),
            'minMessageLength' => max(1, (int) $config['minMessageLength']),
            'maxLinks' => max(0, (int) $config['maxLinks']),
            'whatsappHandoff' => (bool) $config['whatsappHandoff'],
            'successMessage' => (string) $config['successMessage'],
            'notifyEmail' => (string) $config['notifyEmail'],
        ];
    }

    public static function getSiteConfig(): array
    {
        $config = self::read('site-config.json', self::defaultSiteConfig());
        $merged = array_replace_recursive(self::defaultSiteConfig(), $config);

        // La lista de motivos se toma tal cual: fusionada por índice, una lista
        // guardada más corta que la de fábrica recuperaría los motivos borrados.
        if (is_array($config['forms']['contact']['services'] ?? null)) {
            $merged['forms']['contact']['services'] = array_values($config['forms']['contact']['services']);
        }

        return $merged;
    }

    /**
     * Guarda la configuración fusionando con lo que ya estaba almacenado, para
     * que un formulario que sólo edita una sección no borre las demás.
     */
    public static function saveSiteConfig(array $config): bool
    {
        $current = self::read('site-config.json', []);
        $merged = array_replace_recursive(self::defaultSiteConfig(), $current, $config);

        // Las listas se sustituyen completas: fusionar por índice impediría
        // eliminar elementos (por ejemplo, quitar un servicio del formulario).
        if (isset($config['forms']['contact']['services'])) {
            $merged['forms']['contact']['services'] = array_values($config['forms']['contact']['services']);
        }

        return self::write('site-config.json', $merged);
    }

    public static function publicSiteConfig(): array
    {
        require_once __DIR__ . '/PageRegistry.php';

        $config = self::getSiteConfig();
        $logos = $config['logos'];
        $version = (int) ($logos['version'] ?? 1);
        $query = $version > 0 ? '?v=' . $version : '';
        $form = self::contactFormConfig();

        return [
            'brand' => $config['brand'],
            'logos' => [
                'primary' => PageRegistry::url((string) ($logos['primary'] ?? '')) . $query,
                'icon' => PageRegistry::url((string) ($logos['icon'] ?? '')) . $query,
            ],
            'contact' => $config['contact'],
            'whatsapp' => $config['whatsapp'],
            'social' => $config['social'],
            'footer' => $config['footer'],
            'contactForm' => [
                'services' => $form['services'],
                'minSeconds' => $form['minSeconds'],
                'minMessageLength' => $form['minMessageLength'],
                'maxLinks' => $form['maxLinks'],
                'whatsappHandoff' => $form['whatsappHandoff'],
                'successMessage' => $form['successMessage'],
            ],
        ];
    }
}
