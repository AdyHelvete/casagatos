<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$collectionType = 'guides';
$collectionNav = 'guides';
$collectionTitle = 'Orientación · Casos';
$collectionIntro = 'Cada caso aparece en /asistencia/ como un bloque desplegable con sus pasos. Los textos de "Cómo denunciar" y "A dónde acudir" se editan en Textos de las páginas → Asistencia y orientación.';

require __DIR__ . '/partials/collection-admin.php';
