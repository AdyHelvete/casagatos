<?php
declare(strict_types=1);

require_once __DIR__ . '/SiteStorage.php';
require_once __DIR__ . '/SecurityPolicy.php';

/**
 * Comprobaciones automáticas del estado de seguridad del sitio.
 */
class SecurityAudit
{
    /**
     * @return list<array{id: string, label: string, status: string, message: string}>
     */
    public static function run(): array
    {
        $root = dirname(__DIR__);
        $checks = [];

        $checks[] = self::checkHtaccessDeny(
            'data_htaccess',
            'Carpeta data/ bloqueada',
            $root . '/data/.htaccess'
        );

        $checks[] = self::checkHtaccessDeny(
            'lib_htaccess',
            'Carpeta lib/ bloqueada',
            $root . '/lib/.htaccess'
        );

        $checks[] = self::checkHtaccessDeny(
            'backups_htaccess',
            'Carpeta backups/ bloqueada',
            $root . '/backups/.htaccess'
        );

        $checks[] = self::checkFile(
            'control_users',
            'Usuarios del panel control',
            is_file(SiteStorage::path('admin-users.json')),
            'No existe data/admin-users.json. Entra a /control/ para crearlo.'
        );

        $checks[] = self::checkFile(
            'contact_secret',
            'Secreto formulario de contacto',
            is_file(SiteStorage::path('.contact-secret')),
            'Se generará en el primer envío del formulario.'
        );

        $initialPw = SiteStorage::path('.initial-control-password');
        if (is_file($initialPw)) {
            $checks[] = [
                'id' => 'initial_password',
                'label' => 'Contraseña inicial del control',
                'status' => 'warn',
                'message' => 'Existe data/.initial-control-password. Cámbiala en Mi cuenta y elimina ese archivo.',
            ];
        } else {
            $checks[] = [
                'id' => 'initial_password',
                'label' => 'Contraseña inicial del control',
                'status' => 'ok',
                'message' => 'No hay archivo de contraseña temporal pendiente.',
            ];
        }

        $cspMode = SecurityPolicy::cspMode();
        $checks[] = [
            'id' => 'csp_mode',
            'label' => 'Content Security Policy',
            'status' => $cspMode === 'enforce' ? 'ok' : ($cspMode === 'report-only' ? 'warn' : 'warn'),
            'message' => match ($cspMode) {
                'enforce' => 'CSP activa en modo enforce.',
                'report-only' => 'CSP en report-only. Revisa informes y pasa a enforce cuando esté limpio.',
                default => 'CSP desactivada.',
            },
        ];

        $reports = SiteStorage::read('csp-reports.json', ['reports' => []]);
        $reportCount = count(is_array($reports['reports'] ?? null) ? $reports['reports'] : []);
        $checks[] = [
            'id' => 'csp_reports',
            'label' => 'Informes CSP pendientes',
            'status' => $reportCount === 0 ? 'ok' : ($reportCount < 20 ? 'warn' : 'warn'),
            'message' => $reportCount === 0
                ? 'Sin violaciones registradas.'
                : $reportCount . ' violación(es). Revisa la pestaña CSP antes de enforce.',
        ];

        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $checks[] = [
            'id' => 'https',
            'label' => 'Conexión HTTPS',
            'status' => $https ? 'ok' : 'warn',
            'message' => $https ? 'Sesión del panel servida por HTTPS.' : 'No se detectó HTTPS en esta petición.',
        ];

        $blockedControl = self::countRecentAttempts(SiteStorage::path('control-login-attempts.json'));
        $checks[] = [
            'id' => 'login_attempts',
            'label' => 'Intentos de login recientes',
            'status' => $blockedControl > 10 ? 'warn' : 'ok',
            'message' => $blockedControl . ' IP(s) con intentos en el panel (últimos 15 min).',
        ];

        return $checks;
    }

    /**
     * @param list<array{status: string}> $checks
     */
    public static function summary(array $checks): array
    {
        $ok = 0;
        $warn = 0;
        $fail = 0;

        foreach ($checks as $check) {
            match ($check['status'] ?? '') {
                'ok' => $ok++,
                'fail' => $fail++,
                default => $warn++,
            };
        }

        return ['ok' => $ok, 'warn' => $warn, 'fail' => $fail, 'total' => count($checks)];
    }

    private static function checkFile(
        string $id,
        string $label,
        bool $ok,
        string $failMessage,
        bool $warnIfMissing = false
    ): array {
        if ($ok) {
            return ['id' => $id, 'label' => $label, 'status' => 'ok', 'message' => 'Configurado correctamente.'];
        }

        return [
            'id' => $id,
            'label' => $label,
            'status' => $warnIfMissing ? 'warn' : 'fail',
            'message' => $failMessage,
        ];
    }

    private static function checkHtaccessDeny(string $id, string $label, string $path): array
    {
        if (!is_file($path)) {
            return ['id' => $id, 'label' => $label, 'status' => 'fail', 'message' => 'Falta ' . basename(dirname($path)) . '/.htaccess'];
        }

        $contents = (string) file_get_contents($path);
        $denies = str_contains($contents, 'Require all denied') || str_contains($contents, 'Deny from all');

        return [
            'id' => $id,
            'label' => $label,
            'status' => $denies ? 'ok' : 'fail',
            'message' => $denies ? 'Acceso HTTP denegado.' : 'El .htaccess no parece bloquear acceso.',
        ];
    }

    private static function countRecentAttempts(string $path): int
    {
        if (!is_readable($path)) {
            return 0;
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (!is_array($decoded)) {
            return 0;
        }

        $attempts = is_array($decoded['attempts'] ?? null) ? $decoded['attempts'] : $decoded;
        $now = time();
        $count = 0;
        foreach ($attempts as $ip => $timestamps) {
            if (!is_array($timestamps)) {
                continue;
            }
            $recent = array_filter(
                $timestamps,
                static fn($ts) => is_int($ts) && ($now - $ts) < 900
            );
            if (count($recent) >= 1) {
                $count++;
            }
        }

        return $count;
    }
}
