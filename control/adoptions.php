<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$collectionType = 'adoptions';
$collectionNav = 'adoptions';
$collectionTitle = 'Adopción · Gatos e historias';
$collectionIntro = 'Cada ficha se publica en /adopcion/. Cuando un gato encuentre hogar, cambia el estado a "Adoptado" en lugar de borrarla: pasa a la sección de Historias felices.';

require __DIR__ . '/partials/collection-admin.php';
