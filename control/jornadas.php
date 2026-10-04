<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$collectionType = 'campaigns';
$collectionNav = 'campaigns';
$collectionTitle = 'TNR · Jornadas';
$collectionIntro = 'Jornadas de esterilización y TNR. Las que tienen registro abierto o están próximas salen en /tnr/ y en el inicio; las finalizadas pasan al historial.';

require __DIR__ . '/partials/collection-admin.php';
