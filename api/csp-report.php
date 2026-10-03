<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/SiteStorage.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false]);
    exit;
}

$ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
if ($ip === '' || !selfRateLimit($ip)) {
    http_response_code(429);
    echo json_encode(['ok' => false]);
    exit;
}

$raw = file_get_contents('php://input');
if ($raw === false || trim($raw) === '') {
    http_response_code(400);
    echo json_encode(['ok' => false]);
    exit;
}

$payload = json_decode($raw, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['ok' => false]);
    exit;
}

$report = $payload['csp-report'] ?? $payload;
if (!is_array($report)) {
    http_response_code(400);
    echo json_encode(['ok' => false]);
    exit;
}

$entry = [
    'at' => date('c'),
    'ip' => $ip,
    'document_uri' => substr((string) ($report['document-uri'] ?? ''), 0, 500),
    'violated_directive' => substr((string) ($report['violated-directive'] ?? ''), 0, 200),
    'blocked_uri' => substr((string) ($report['blocked-uri'] ?? ''), 0, 500),
    'source_file' => substr((string) ($report['source-file'] ?? ''), 0, 500),
];

$file = SiteStorage::path('csp-reports.json');
$store = SiteStorage::read('csp-reports.json', ['reports' => []]);
$reports = is_array($store['reports'] ?? null) ? $store['reports'] : [];
array_unshift($reports, $entry);
$reports = array_slice($reports, 0, 200);
SiteStorage::write('csp-reports.json', ['reports' => $reports]);

echo json_encode(['ok' => true]);

function selfRateLimit(string $ip): bool
{
    $file = SiteStorage::path('csp-report-rate.json');
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

    if (count($events) >= 60) {
        return false;
    }

    $events[] = $now;
    $store[$ip] = $events;
    file_put_contents($file, json_encode($store, JSON_UNESCAPED_UNICODE) . "\n", LOCK_EX);

    return true;
}
