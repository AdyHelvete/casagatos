<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$collectionType = 'adoptions';
$collectionNav = 'adoptions';
$collectionTitle = 'Adopciones';
$collectionIntro = 'Cada ficha se publica en /adopciones/. Cambia el estado a "Adoptado" en lugar de borrarla: las historias con final feliz ayudan a convencer a quien todavía lo está pensando.';

require __DIR__ . '/partials/collection-admin.php';
