<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$collectionType = 'clinics';
$collectionNav = 'clinics';
$collectionTitle = 'Directorio · Clínicas';
$collectionIntro = 'Clínicas veterinarias que aparecen en /directorio/. Elige la zona (Tizayuca o Zumpango) y explica qué servicio ofrece cada una: consulta, esterilización, urgencias, etc.';

require __DIR__ . '/partials/collection-admin.php';
