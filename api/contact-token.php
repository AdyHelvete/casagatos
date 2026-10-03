<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/security.php';

if (checkTokenIssueRateLimit(getClientIp()) !== null) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'Demasiadas solicitudes. Intenta más tarde.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $token = issueContactToken();
    echo json_encode([
        'ok' => true,
        'token' => $token['token'],
        'issued_at' => $token['issued_at'],
        'ttl' => $token['ttl'],
        'min_seconds' => $token['min_seconds'],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'No se pudo generar el token de seguridad.',
    ], JSON_UNESCAPED_UNICODE);
}
