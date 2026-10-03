<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$collectionType = 'campaigns';
$collectionNav = 'campaigns';
$collectionTitle = 'Campañas y eventos';
$collectionIntro = 'Jornadas de esterilización, colectas y eventos. Las activas y próximas salen arriba en /campanas/; las finalizadas pasan al historial. Marca "Destacado" para que aparezcan en el inicio.';

require __DIR__ . '/partials/collection-admin.php';
