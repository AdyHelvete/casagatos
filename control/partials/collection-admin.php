<?php
declare(strict_types=1);

/**
 * Editor compartido de las colecciones de contenido (adopciones, campañas y
 * álbumes). Cada página del panel define $collectionType, $collectionNav,
 * $collectionTitle y $collectionIntro, e incluye este archivo.
 *
 * @var string $collectionType   Clave en ContentCollection::types()
 * @var string $collectionNav    Clave del menú lateral
 * @var string $collectionTitle  Título de la página
 * @var string $collectionIntro  Texto de ayuda bajo el título
 */

if (!isset($collectionType) || !function_exists('controlHeader')) {
    http_response_code(404);
    exit;
}

ControlAuth::requireLogin();

$schema = ContentCollection::schema($collectionType);
$titleField = $schema['titleField'];
$singular = $schema['singular'];

$error = '';
$success = '';
$editItem = null;
$editId = ContentCollection::slugify((string) ($_GET['edit'] ?? ''));

$tabIds = ['edit', 'list'];
$activeTab = trim((string) ($_GET['tab'] ?? ($editId !== '' ? 'edit' : 'list')));
if (!in_array($activeTab, $tabIds, true)) {
    $activeTab = 'list';
}

if ($editId !== '') {
    $editItem = ContentCollection::find($collectionType, $editId);
    if ($editItem === null) {
        $error = 'No encontramos ese elemento; puede que se haya eliminado.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    controlRequirePost();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'save') {
        $input = $_POST;
        $input['gallery'] = (string) ($_POST['gallery'] ?? '');

        if (!empty($_FILES['cover_file']['name'])) {
            $upload = uploadGalleryImage($_FILES['cover_file'], $collectionType);
            if (!empty($upload['success'])) {
                $input['cover'] = $upload['path'];
            } else {
                $error = (string) ($upload['message'] ?? 'No se pudo subir la portada.');
            }
        }

        if ($error === '' && !empty($_FILES['gallery_files']['name'][0])) {
            $uploaded = uploadContentImages($_FILES['gallery_files'], $collectionType);
            if ($uploaded['paths'] !== []) {
                $existing = trim((string) $input['gallery']);
                $input['gallery'] = ($existing === '' ? '' : $existing . "\n") . implode("\n", $uploaded['paths']);
            }
            if ($uploaded['errors'] !== []) {
                $error = 'Algunas imágenes no se subieron: ' . implode(' · ', $uploaded['errors']);
            }
        }

        if ($error === '') {
            $result = ContentCollection::save($collectionType, $input);
            if ($result['success']) {
                $success = 'Cambios guardados.';
                $editId = $result['id'];
                $editItem = ContentCollection::find($collectionType, $editId);
            } else {
                $error = $result['message'];
                // Se conserva lo capturado para no perder el trabajo; la
                // galería llega como texto y hay que devolverla a lista.
                $input['gallery'] = array_values(array_filter(array_map(
                    'trim',
                    preg_split('/\R+/', (string) $input['gallery']) ?: []
                ), static fn(string $line): bool => $line !== ''));
                $editItem = array_replace(ContentCollection::blank($collectionType), $input);
            }
        }

        $activeTab = 'edit';
    } elseif ($action === 'delete') {
        $deleteId = ContentCollection::slugify((string) ($_POST['delete_id'] ?? ''));
        if ($deleteId !== '' && ContentCollection::delete($collectionType, $deleteId)) {
            $success = 'Elemento eliminado.';
            $editItem = null;
            $editId = '';
        } else {
            $error = 'No se pudo eliminar el elemento.';
        }
        $activeTab = 'list';
    } elseif ($action === 'toggle') {
        $toggleId = ContentCollection::slugify((string) ($_POST['toggle_id'] ?? ''));
        if ($toggleId !== '' && ContentCollection::togglePublished($collectionType, $toggleId)) {
            $success = 'Visibilidad actualizada.';
        } else {
            $error = 'No se pudo cambiar la visibilidad.';
        }
        $activeTab = 'list';
    }
}

$items = ContentCollection::all($collectionType);
$form = $editItem ?? array_replace(ContentCollection::blank($collectionType), [
    'order' => (count($items) + 1) * 10,
]);
$isEditing = $editItem !== null && trim((string) $form['id']) !== '';

$statusOptions = [
    'adoptions' => [
        'disponible' => 'En adopción',
        'en-proceso' => 'En proceso',
        'adoptado' => 'Adoptado',
    ],
    'campaigns' => [
        'activa' => 'Activa',
        'proxima' => 'Próxima',
        'finalizada' => 'Finalizada',
    ],
][$collectionType] ?? [];

controlHeader($collectionTitle, $collectionNav);
?>
<?php if ($error): ?><div class="control-alert control-alert--error"><?php echo h($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="control-alert control-alert--success"><?php echo h($success); ?></div><?php endif; ?>

<?php
controlTabsStart($collectionType, [
    'list' => 'Listado (' . count($items) . ')',
    'edit' => $isEditing ? 'Editando: ' . (string) $form[$titleField] : 'Nueva ' . $singular,
], $activeTab);
?>

<?php controlTabPanelStart($collectionType, 'list', $activeTab === 'list'); ?>
<p class="control-tabs__intro"><?php echo h($collectionIntro); ?></p>

<?php if ($items === []): ?>
    <div class="control-alert">Todavía no hay contenido. Usa la pestaña <strong>Nueva <?php echo h($singular); ?></strong> para crear el primero.</div>
<?php else: ?>
<div class="control-table-wrap">
    <table class="control-table">
        <thead>
            <tr>
                <th>Nombre</th>
                <?php if ($statusOptions !== []): ?><th>Estado</th><?php endif; ?>
                <th>Visibilidad</th>
                <th>Orden</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td>
                    <strong><?php echo h((string) $item[$titleField]); ?></strong><br>
                    <span class="control-muted"><code><?php echo h(cu($schema['baseUrl'] . $item['id'] . '/')); ?></code></span>
                </td>
                <?php if ($statusOptions !== []): ?>
                    <td><?php echo h($statusOptions[(string) ($item['status'] ?? '')] ?? '—'); ?></td>
                <?php endif; ?>
                <td><?php echo !empty($item['published']) ? 'Publicado' : 'Oculto'; ?><?php echo !empty($item['featured']) ? ' · Destacado' : ''; ?></td>
                <td><?php echo (int) $item['order']; ?></td>
                <td class="control-table__actions">
                    <a href="?edit=<?php echo urlencode((string) $item['id']); ?>&amp;tab=edit">Editar</a>
                    <a href="<?php echo h(cu($schema['baseUrl'] . $item['id'] . '/')); ?>" target="_blank" rel="noopener">Ver</a>
                    <form method="POST" class="control-inline-form">
                        <?php echo controlCsrfField(); ?>
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="toggle_id" value="<?php echo h((string) $item['id']); ?>">
                        <button type="submit" class="control-link"><?php echo !empty($item['published']) ? 'Ocultar' : 'Publicar'; ?></button>
                    </form>
                    <form method="POST" class="control-inline-form" data-confirm="¿Eliminar este elemento? No se puede deshacer.">
                        <?php echo controlCsrfField(); ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="delete_id" value="<?php echo h((string) $item['id']); ?>">
                        <button type="submit" class="control-link-danger">Eliminar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
<?php controlTabPanelEnd(); ?>

<?php controlTabPanelStart($collectionType, 'edit', $activeTab === 'edit'); ?>
<form method="POST" enctype="multipart/form-data">
    <?php echo controlCsrfField(); ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="originalId" value="<?php echo h((string) $form['id']); ?>">

    <div class="control-form control-form--grid">
        <?php if ($collectionType === 'adoptions'): ?>
            <label><?php echo controlFieldLabel('Nombre del gato', 'Aparece como título de la ficha y en las tarjetas.'); ?><input type="text" name="name" value="<?php echo h((string) $form['name']); ?>" required></label>
            <label><?php echo controlFieldLabel('Estado', 'Solo los que están "En adopción" muestran el botón de contacto.'); ?>
                <select name="status">
                    <?php foreach ($statusOptions as $value => $label): ?>
                        <option value="<?php echo h($value); ?>"<?php echo (string) $form['status'] === $value ? ' selected' : ''; ?>><?php echo h($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Edad<input type="text" name="age" value="<?php echo h((string) $form['age']); ?>" placeholder="3 meses"></label>
            <label>Sexo<input type="text" name="sex" value="<?php echo h((string) $form['sex']); ?>" placeholder="Hembra"></label>
            <label>Tamaño<input type="text" name="size" value="<?php echo h((string) $form['size']); ?>" placeholder="Pequeña"></label>
            <label>Carácter<input type="text" name="temperament" value="<?php echo h((string) $form['temperament']); ?>" placeholder="Tranquila, juguetona"></label>
            <label class="control-check"><input type="checkbox" name="sterilized" value="1"<?php echo !empty($form['sterilized']) ? ' checked' : ''; ?>><span>Esterilizado</span></label>
            <label class="control-check"><input type="checkbox" name="vaccinated" value="1"<?php echo !empty($form['vaccinated']) ? ' checked' : ''; ?>><span>Vacunado</span></label>
            <label class="control-field--full"><?php echo controlFieldLabel('Resumen', 'Una o dos frases. Se usa en las tarjetas y en la meta description.'); ?><textarea name="summary" rows="2" required><?php echo h((string) $form['summary']); ?></textarea></label>
            <label class="control-field--full"><?php echo controlFieldLabel('Historia', 'Texto largo. Puedes usar <p>, <ul>, <li>, <strong> y enlaces.'); ?><textarea name="story" rows="10"><?php echo h((string) $form['story']); ?></textarea></label>
            <label>Texto del botón<input type="text" name="ctaLabel" value="<?php echo h((string) $form['ctaLabel']); ?>" placeholder="Quiero adoptar a…"></label>
            <label><?php echo controlFieldLabel('URL del botón', 'Vacío = abre WhatsApp con un mensaje prellenado.'); ?><input type="text" name="ctaUrl" value="<?php echo h((string) $form['ctaUrl']); ?>" placeholder="/contacto/"></label>

        <?php elseif ($collectionType === 'campaigns'): ?>
            <label class="control-field--full">Título<input type="text" name="title" value="<?php echo h((string) $form['title']); ?>" required></label>
            <label>Tipo
                <select name="kind">
                    <option value="campana"<?php echo (string) $form['kind'] === 'campana' ? ' selected' : ''; ?>>Campaña</option>
                    <option value="evento"<?php echo (string) $form['kind'] === 'evento' ? ' selected' : ''; ?>>Evento</option>
                </select>
            </label>
            <label><?php echo controlFieldLabel('Estado', 'Las finalizadas pasan al historial y ocultan el botón de registro.'); ?>
                <select name="status">
                    <?php foreach ($statusOptions as $value => $label): ?>
                        <option value="<?php echo h($value); ?>"<?php echo (string) $form['status'] === $value ? ' selected' : ''; ?>><?php echo h($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Fecha de inicio<input type="date" name="startDate" value="<?php echo h((string) $form['startDate']); ?>"></label>
            <label><?php echo controlFieldLabel('Fecha de fin', 'Déjala vacía si es de un solo día.'); ?><input type="date" name="endDate" value="<?php echo h((string) $form['endDate']); ?>"></label>
            <label class="control-field--full">Lugar<input type="text" name="place" value="<?php echo h((string) $form['place']); ?>" placeholder="Explanada municipal, Tizayuca"></label>
            <label class="control-field--full"><?php echo controlFieldLabel('Resumen', 'Una o dos frases. Se usa en las tarjetas y en la meta description.'); ?><textarea name="summary" rows="2" required><?php echo h((string) $form['summary']); ?></textarea></label>
            <label class="control-field--full"><?php echo controlFieldLabel('Contenido', 'Texto largo. Puedes usar <p>, <h3>, <ul>, <li>, <strong> y enlaces.'); ?><textarea name="body" rows="12"><?php echo h((string) $form['body']); ?></textarea></label>
            <label>Texto del botón<input type="text" name="ctaLabel" value="<?php echo h((string) $form['ctaLabel']); ?>" placeholder="Apartar lugar"></label>
            <label>URL del botón<input type="text" name="ctaUrl" value="<?php echo h((string) $form['ctaUrl']); ?>" placeholder="/contacto/"></label>

        <?php else: ?>
            <label class="control-field--full">Título del álbum<input type="text" name="title" value="<?php echo h((string) $form['title']); ?>" required></label>
            <label class="control-field--full"><?php echo controlFieldLabel('Descripción', 'Una frase que explique qué muestra el álbum.'); ?><textarea name="summary" rows="2"><?php echo h((string) $form['summary']); ?></textarea></label>
        <?php endif; ?>

        <label class="control-field--full"><?php echo controlFieldLabel('Portada (ruta)', 'Vacío = usa la primera imagen de la galería.'); ?><input type="text" name="cover" value="<?php echo h((string) $form['cover']); ?>" placeholder="/assets/images/…"></label>
        <label>Subir portada<input type="file" name="cover_file" accept="image/*"></label>
        <label class="control-field--full"><?php echo controlFieldLabel('Galería (una ruta por línea)', 'Rutas públicas de las imágenes. Puedes copiarlas desde Biblioteca de imágenes.'); ?><textarea name="gallery" rows="6" placeholder="/assets/images/foto-1.jpg"><?php echo h(implode("\n", is_array($form['gallery']) ? $form['gallery'] : [])); ?></textarea></label>
        <label>Subir imágenes a la galería<input type="file" name="gallery_files[]" accept="image/*" multiple></label>

        <label><?php echo controlFieldLabel('Identificador (URL)', 'Vacío = se genera del título. Cambiarlo rompe los enlaces existentes.'); ?><input type="text" name="id" value="<?php echo h((string) $form['id']); ?>" placeholder="se-genera-solo"></label>
        <label><?php echo controlFieldLabel('Orden', 'Menor número aparece primero.'); ?><input type="number" name="order" value="<?php echo (int) $form['order']; ?>"></label>
        <label class="control-check"><input type="checkbox" name="published" value="1"<?php echo !empty($form['published']) ? ' checked' : ''; ?>><span>Publicado</span></label>
        <label class="control-check"><input type="checkbox" name="featured" value="1"<?php echo !empty($form['featured']) ? ' checked' : ''; ?>><span>Destacado en el inicio</span></label>
    </div>

    <?php controlTabsFooterStart(); ?>
        <button type="submit" class="control-btn">Guardar</button>
        <?php if ($isEditing): ?>
            <a class="control-btn control-btn--ghost" href="?tab=edit">Crear uno nuevo</a>
            <a class="control-btn control-btn--ghost" href="<?php echo h(cu($schema['baseUrl'] . $form['id'] . '/')); ?>" target="_blank" rel="noopener">Ver en el sitio</a>
        <?php endif; ?>
    <?php controlTabsFooterEnd(); ?>
</form>
<?php controlTabPanelEnd(); ?>

<?php controlTabsEnd(); ?>
<?php controlFooter(); ?>
