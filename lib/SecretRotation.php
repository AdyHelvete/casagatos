<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/SiteStorage.php';
require_once dirname(__DIR__) . '/lib/SecurityPolicy.php';

/**
 * Rotación de secretos HMAC almacenados en data/.
 */
class SecretRotation
{
    public static function status(): array
    {
        $config = SecurityPolicy::config();
        $files = [
            'contact' => [
                'label' => 'Formulario de contacto',
                'path' => SiteStorage::path('.contact-secret'),
                'rotatedAt' => $config['secrets']['contactRotatedAt'] ?? null,
            ],
        ];

        foreach ($files as $key => $meta) {
            $files[$key]['exists'] = is_file($meta['path']);
            $files[$key]['writable'] = is_writable(SiteStorage::dataDir()) || (is_file($meta['path']) && is_writable($meta['path']));
        }

        return $files;
    }

    public static function rotate(string $key): array
    {
        $map = [
            'contact' => '.contact-secret',
        ];

        if (!isset($map[$key])) {
            return ['success' => false, 'message' => 'Secreto no reconocido.'];
        }

        $path = SiteStorage::path($map[$key]);
        $dir = SiteStorage::dataDir();
        if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
            return ['success' => false, 'message' => 'No se pudo preparar la carpeta data/.'];
        }

        $secret = bin2hex(random_bytes(32));
        if (file_put_contents($path, $secret . "\n", LOCK_EX) === false) {
            return ['success' => false, 'message' => 'No se pudo escribir el nuevo secreto.'];
        }

        @chmod($path, 0640);

        $config = SecurityPolicy::config();
        $config['secrets'][$key . 'RotatedAt'] = date('c');
        SecurityPolicy::saveConfig($config);

        $notes = [
            'contact' => 'Los visitantes deben recargar la página de contacto para obtener un token nuevo.',
        ];

        return [
            'success' => true,
            'message' => 'Secreto rotado correctamente. ' . ($notes[$key] ?? ''),
        ];
    }
}
