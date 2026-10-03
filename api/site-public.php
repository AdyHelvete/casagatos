<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');
header('X-Content-Type-Options: nosniff');

require_once dirname(__DIR__) . '/lib/SiteStorage.php';

echo json_encode([
    'ok' => true,
    'config' => SiteStorage::publicSiteConfig(),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
