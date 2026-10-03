<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$collectionType = 'albums';
$collectionNav = 'albums';
$collectionTitle = 'Galerías';
$collectionIntro = 'Cada álbum se publica en /galeria/ con su portada y sus fotos. Sube las imágenes aquí mismo o pega rutas que ya existan en la biblioteca.';

require __DIR__ . '/partials/collection-admin.php';
