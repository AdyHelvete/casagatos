<?php
declare(strict_types=1);

require_once __DIR__ . '/SiteStorage.php';

/**
 * Registro central de eventos de seguridad (login fallido, spam, etc.).
 */
class SecurityLog
{
    public const FILE = 'security-events.json';
    private const MAX_EVENTS = 300;

    public static function record(string $type, string $detail = '', ?string $ip = null): void
    {
        $type = trim($type);
        if ($type === '') {
            return;
        }

        $ip = $ip ?? self::clientIp();
        $store = SiteStorage::read(self::FILE, ['events' => []]);
        $events = is_array($store['events'] ?? null) ? $store['events'] : [];

        array_unshift($events, [
            'at' => date('c'),
            'type' => substr($type, 0, 64),
            'ip' => substr($ip, 0, 64),
            'detail' => substr(trim($detail), 0, 500),
        ]);

        $events = array_slice($events, 0, self::MAX_EVENTS);
        SiteStorage::write(self::FILE, ['events' => $events]);
    }

    /**
     * @return list<array{at: string, type: string, ip: string, detail: string}>
     */
    public static function recent(int $limit = 30): array
    {
        $store = SiteStorage::read(self::FILE, ['events' => []]);
        $events = is_array($store['events'] ?? null) ? $store['events'] : [];

        return array_slice($events, 0, max(1, $limit));
    }

    public static function clear(): bool
    {
        return SiteStorage::write(self::FILE, ['events' => []]);
    }

    public static function label(string $type): string
    {
        $map = [
            'control_login_failed' => 'Login fallido (control)',
            'control_login_blocked' => 'Login bloqueado (control)',
            'contact_blocked' => 'Formulario contacto bloqueado',
            'csp_violation' => 'Violación CSP',
        ];

        return $map[$type] ?? $type;
    }

    private static function clientIp(): string
    {
        $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'unknown';
    }
}
