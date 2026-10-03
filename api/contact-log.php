<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/security.php';
require_once dirname(__DIR__) . '/lib/SecurityLog.php';
require_once __DIR__ . '/lib/SimpleXLSXGen.php';

use Shuchkin\SimpleXLSXGen;

date_default_timezone_set('America/Mexico_City');

const MAX_ERROR = 500;

$dataDir = getDataDir();
$jsonFile = $dataDir . '/contact-submissions.json';
$xlsxFile = $dataDir . '/contact-submissions.xlsx';

function respond(bool $ok, string $message = '', int $code = 200): void
{
    http_response_code($code);
    echo json_encode([
        'ok' => $ok,
        'error' => $ok ? '' : $message,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function ensureStorage(string $dataDir, string $jsonFile): void
{
    if (!is_dir($dataDir) && !mkdir($dataDir, 0750, true) && !is_dir($dataDir)) {
        respond(false, 'No se pudo crear el directorio de registros.', 500);
    }

    if (!file_exists($jsonFile)) {
        if (file_put_contents($jsonFile, "[]\n", LOCK_EX) === false) {
            respond(false, 'No se pudo inicializar el archivo de registros.', 500);
        }
    }
}

function loadRecords(string $jsonFile): array
{
    $raw = file_get_contents($jsonFile);
    if ($raw === false || $raw === '') {
        return [];
    }

    $records = json_decode($raw, true);
    return is_array($records) ? $records : [];
}

function saveRecords(string $jsonFile, array $records): void
{
    $encoded = json_encode($records, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if ($encoded === false || file_put_contents($jsonFile, $encoded . "\n", LOCK_EX) === false) {
        respond(false, 'No se pudo guardar el registro.', 500);
    }
}

function writeXlsx(string $xlsxFile, array $records): void
{
    $rows = [[
        'Fecha (MX)',
        'Estado',
        'Detalle del error',
        'Nombre',
        'Correo',
        'Teléfono',
        'Servicio',
        'Mensaje',
        'IP',
        'Navegador',
    ]];

    foreach ($records as $record) {
        $rows[] = [
            $record['fecha_mx'] ?? '',
            $record['estado'] ?? '',
            $record['error'] ?? '',
            $record['nombre'] ?? '',
            $record['email'] ?? '',
            $record['telefono'] ?? '',
            $record['servicio'] ?? '',
            $record['mensaje'] ?? '',
            $record['ip'] ?? '',
            $record['user_agent'] ?? '',
        ];
    }

    $saved = SimpleXLSXGen::fromArray($rows)->saveAs($xlsxFile);
    if (!$saved) {
        respond(false, 'No se pudo generar el archivo XLSX.', 500);
    }
}

function appendRecord(array $record): void
{
    global $dataDir, $jsonFile, $xlsxFile;

    ensureStorage($dataDir, $jsonFile);
    $records = loadRecords($jsonFile);
    $records[] = $record;
    saveRecords($jsonFile, $records);
    writeXlsx($xlsxFile, $records);
}

$rawBody = file_get_contents('php://input');
if ($rawBody === false || $rawBody === '') {
    respond(false, 'Solicitud vacía.', 400);
}

if (strlen($rawBody) > 12000) {
    respond(false, 'La solicitud supera el tamaño permitido.', 413);
}

$payload = json_decode($rawBody, true);
if (!is_array($payload)) {
    respond(false, 'Formato JSON inválido.', 400);
}

$estado = sanitizeField((string) ($payload['estado'] ?? 'error'), 20);
if (!in_array($estado, ['exitoso', 'error'], true)) {
    $estado = 'error';
}

$validation = validateContactPayload($payload, true);
$validated = $validation['data'];
$validationErrors = $validation['errors'];

$clientError = sanitizeField((string) ($payload['error'] ?? ''), MAX_ERROR);
$errorMessage = $clientError;

if ($validationErrors !== []) {
    $estado = 'error';
    $errorMessage = implode(' ', $validationErrors);
    SecurityLog::record('contact_blocked', substr($errorMessage, 0, 200), sanitizeField(getClientIp(), 64));

    $record = [
        'fecha' => date('c'),
        'fecha_mx' => date('Y-m-d H:i:s'),
        'estado' => $estado,
        'error' => $errorMessage,
        'nombre' => $validated['nombre'],
        'email' => $validated['email'],
        'telefono' => $validated['telefono'],
        'servicio' => $validated['servicio'],
        'mensaje' => $validated['mensaje'],
        'ip' => sanitizeField(getClientIp(), 64),
        'user_agent' => sanitizeField((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 255),
    ];
    appendRecord($record);
    respond(false, $errorMessage, 422);
}

$record = [
    'fecha' => date('c'),
    'fecha_mx' => date('Y-m-d H:i:s'),
    'estado' => $estado,
    'error' => $errorMessage,
    'nombre' => $validated['nombre'],
    'email' => $validated['email'],
    'telefono' => $validated['telefono'],
    'servicio' => $validated['servicio'],
    'mensaje' => $validated['mensaje'],
    'ip' => sanitizeField(getClientIp(), 64),
    'user_agent' => sanitizeField((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 255),
];

appendRecord($record);
respond(true);
