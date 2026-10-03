<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/SiteStorage.php';

/**
 * Parámetros del formulario de contacto administrados desde /control/forms.php.
 */
function contactConfig(): array
{
    static $config = null;
    $config ??= SiteStorage::contactFormConfig();

    return $config;
}

function contactSetting(string $key)
{
    return contactConfig()[$key] ?? null;
}

const INJECTION_PATTERNS = [
    '/<\s*script/i',
    '/javascript\s*:/i',
    '/<\s*iframe/i',
    '/<\s*object/i',
    '/<\s*embed/i',
    '/<\s*svg/i',
    '/on\w+\s*=/i',
    '/<\?php/i',
    '/\bdata:text\/html/i',
    '/\beval\s*\(/i',
    '/\bdocument\s*\.\s*cookie/i',
];

function getDataDir(): string
{
    return dirname(__DIR__) . '/data';
}

function getSecretFile(): string
{
    return getDataDir() . '/.contact-secret';
}

function getSecret(): string
{
    $file = getSecretFile();
    $dir = getDataDir();

    if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
        throw new RuntimeException('No se pudo preparar el directorio de datos.');
    }

    if (is_readable($file)) {
        $secret = trim((string) file_get_contents($file));
        if ($secret !== '') {
            return $secret;
        }
    }

    $secret = bin2hex(random_bytes(32));
    if (file_put_contents($file, $secret . "\n", LOCK_EX) === false) {
        throw new RuntimeException('No se pudo generar la clave de seguridad.');
    }

    @chmod($file, 0640);
    return $secret;
}

function sanitizeField(string $value, int $maxLength): string
{
    $value = trim(strip_tags($value));
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
    $value = preg_replace('/\s+/u', ' ', $value) ?? '';

    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $maxLength);
    }

    return substr($value, 0, $maxLength);
}

function containsInjection(string $value): bool
{
    foreach (INJECTION_PATTERNS as $pattern) {
        if (preg_match($pattern, $value) === 1) {
            return true;
        }
    }

    return false;
}

function isValidEmail(string $email): bool
{
    if ($email === '' || strlen($email) > 160) {
        return false;
    }

    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function isValidPhone(string $phone): bool
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    return strlen($digits) >= 10 && strlen($digits) <= 15;
}

function isValidName(string $name): bool
{
    if ($name === '' || strlen($name) < 2) {
        return false;
    }

    return preg_match('/^[\p{L}\p{M}\s\'\-.]{2,120}$/u', $name) === 1;
}

function isAllowedService(string $service): bool
{
    return in_array($service, (array) contactSetting('services'), true);
}

function issueContactToken(): array
{
    $issued = time();
    $nonce = bin2hex(random_bytes(8));
    $signature = hash_hmac('sha256', $issued . '.' . $nonce, getSecret());

    return [
        'token' => base64_encode($issued . '.' . $nonce . '.' . $signature),
        'issued_at' => $issued,
        'ttl' => (int) contactSetting('tokenTtl'),
        'min_seconds' => (int) contactSetting('minSeconds'),
    ];
}

function verifyContactToken(string $token): bool
{
    if ($token === '') {
        return false;
    }

    $decoded = base64_decode($token, true);
    if ($decoded === false) {
        return false;
    }

    $parts = explode('.', $decoded);
    if (count($parts) !== 3) {
        return false;
    }

    [$issued, $nonce, $signature] = $parts;
    if (!ctype_digit($issued) || !ctype_xdigit($nonce) || !ctype_xdigit($signature)) {
        return false;
    }

    $issuedAt = (int) $issued;
    $now = time();

    if ($issuedAt > $now || ($now - $issuedAt) > (int) contactSetting('tokenTtl')) {
        return false;
    }

    $expected = hash_hmac('sha256', $issued . '.' . $nonce, getSecret());
    return hash_equals($expected, $signature);
}

function isValidFormTiming(int $loadedAt): bool
{
    if ($loadedAt <= 0) {
        return false;
    }

    $now = time();
    $elapsed = $now - $loadedAt;

    if ($elapsed < (int) contactSetting('minSeconds')) {
        return false;
    }

    if ($elapsed > (int) contactSetting('maxAgeSeconds')) {
        return false;
    }

    return true;
}

function getClientIp(): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

function checkRateLimit(string $ip): ?string
{
    $file = getDataDir() . '/rate-limit.json';
    $now = time();
    $store = [];

    if (is_readable($file)) {
        $decoded = json_decode((string) file_get_contents($file), true);
        if (is_array($decoded)) {
            $store = $decoded;
        }
    }

    $events = array_values(array_filter(
        $store[$ip] ?? [],
        static fn ($timestamp) => is_int($timestamp) && ($now - $timestamp) <= 86400
    ));

    $lastHour = array_filter($events, static fn ($timestamp) => ($now - $timestamp) <= 3600);

    if (count($lastHour) >= (int) contactSetting('rateHour')) {
        return 'Demasiados envíos desde esta dirección. Intenta más tarde.';
    }

    if (count($events) >= (int) contactSetting('rateDay')) {
        return 'Se alcanzó el límite diario de envíos desde esta dirección.';
    }

    $events[] = $now;
    $store[$ip] = $events;

    file_put_contents(
        $file,
        json_encode($store, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n",
        LOCK_EX
    );

    return null;
}

function checkTokenIssueRateLimit(string $ip): ?string
{
    $file = getDataDir() . '/contact-token-rate.json';
    $now = time();
    $store = [];

    if (is_readable($file)) {
        $decoded = json_decode((string) file_get_contents($file), true);
        if (is_array($decoded)) {
            $store = $decoded;
        }
    }

    $events = array_values(array_filter(
        $store[$ip] ?? [],
        static fn($timestamp) => is_int($timestamp) && ($now - $timestamp) <= 3600
    ));

    if (count($events) >= 30) {
        return 'rate_limited';
    }

    $events[] = $now;
    $store[$ip] = $events;
    file_put_contents($file, json_encode($store, JSON_UNESCAPED_UNICODE) . "\n", LOCK_EX);

    return null;
}

function validateContactPayload(array $payload, bool $enforceSecurity = true): array
{
    $errors = [];

    if ($enforceSecurity) {
        $honeypot = sanitizeField((string) ($payload['_hp'] ?? $payload['empresa_web'] ?? ''), 120);
        if ($honeypot !== '') {
            $errors[] = 'Envío bloqueado por verificación anti-spam.';
        }

        if (!verifyContactToken((string) ($payload['form_token'] ?? ''))) {
            $errors[] = 'Token de seguridad inválido o expirado.';
        }

        $loadedAt = (int) ($payload['form_loaded_at'] ?? 0);
        if (!isValidFormTiming($loadedAt)) {
            $errors[] = 'El formulario se envió demasiado rápido o expiró. Recarga la página.';
        }

        $rateError = checkRateLimit(getClientIp());
        if ($rateError !== null) {
            $errors[] = $rateError;
        }
    }

    $nombre = sanitizeField((string) ($payload['nombre'] ?? ''), 120);
    $email = sanitizeField((string) ($payload['email'] ?? ''), 160);
    $telefono = sanitizeField((string) ($payload['telefono'] ?? ''), 40);
    $servicio = sanitizeField((string) ($payload['servicio'] ?? ''), 80);
    $mensaje = sanitizeField((string) ($payload['mensaje'] ?? ''), 4000);

    if (!isValidName($nombre)) {
        $errors[] = 'Nombre inválido.';
    }

    if (!isValidEmail($email)) {
        $errors[] = 'Correo electrónico inválido.';
    }

    if (!isValidPhone($telefono)) {
        $errors[] = 'Teléfono inválido.';
    }

    if (!isAllowedService($servicio)) {
        $errors[] = 'Servicio no permitido.';
    }

    if ($mensaje === '' || strlen($mensaje) < (int) contactSetting('minMessageLength')) {
        $errors[] = 'El mensaje es demasiado corto.';
    }

    foreach ([$nombre, $email, $telefono, $servicio, $mensaje] as $fieldValue) {
        if (containsInjection($fieldValue)) {
            $errors[] = 'Se detectó contenido no permitido en el formulario.';
            break;
        }
    }

    if (preg_match_all('/https?:\/\//i', $mensaje, $matches) && count($matches[0]) > (int) contactSetting('maxLinks')) {
        $errors[] = 'El mensaje contiene demasiados enlaces.';
    }

    return [
        'errors' => $errors,
        'data' => [
            'nombre' => $nombre,
            'email' => $email,
            'telefono' => $telefono,
            'servicio' => $servicio,
            'mensaje' => $mensaje,
        ],
    ];
}
