<?php
declare(strict_types=1);

/**
 * Editor compartido de las colecciones de contenido (fichas de adopción,
 * jornadas, casos de orientación y clínicas). Cada página del panel define
 * $collectionType, $collectionNav, $collectionTitle y $collectionIntro, e
 * incluye este archivo.
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
$hasCover = in_array('cover', $schema['text'], true);
$hasGallery = in_array('gallery', $schema['list'], true);
$hasFeatured = in_array('featured', $schema['bool'], true);

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

/**
 * Devuelve a lista los campos que el formulario envía como texto (uno por
 * línea), para repintar el formulario sin perder lo capturado.
 */
$linesToList = static fn(string $value): array => array_values(array_filter(
    array_map('trim', preg_split('/\R+/', $value) ?: []),
    static fn(string $line): bool => $line !== ''
));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    controlRequirePost();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'save') {
        $input = $_POST;
        foreach ($schema['list'] as $listField) {
            $input[$listField] = (string) ($_POST[$listField] ?? '');
        }

        if ($hasCover && !empty($_FILES['cover_file']['name'])) {
            $upload = uploadGalleryImage($_FILES['cover_file'], $collectionType);
            if (!empty($upload['success'])) {
                $input['cover'] = $upload['path'];
            } else {
                $error = (string) ($upload['message'] ?? 'No se pudo subir la portada.');
            }
        }

        if ($hasGallery && $error === '' && !empty($_FILES['gallery_files']['name'][0])) {
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
                // Se conserva lo capturado para no perder el trabajo.
                foreach ($schema['list'] as $listField) {
                    $input[$listField] = $linesToList((string) $input[$listField]);
                }
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

// Columna de clasificación del listado: estado o, en el directorio, zona.
$groupField = $collectionType === 'clinics' ? 'zone' : 'status';
$groupOptions = ContentCollection::optionLabels($collectionType, $groupField);
$groupHeading = $collectionType === 'clinics' ? 'Zona' : 'Estado';
$statusOptions = ContentCollection::optionLabels($collectionType, 'status');

$optionTags = static function (array $options, string $current): string {
    $html = '';
    foreach ($options as $value => $label) {
        $html .= '<option value="' . h((string) $value) . '"' . ($current === (string) $value ? ' selected' : '') . '>' . h($label) . '</option>';
    }

    return $html;
};

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
                <?php if ($groupOptions !== []): ?><th><?php echo h($groupHeading); ?></th><?php endif; ?>
                <th>Visibilidad</th>
                <th>Orden</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <?php $publicUrl = cu(ContentCollection::itemPath($collectionType, $item)); ?>
            <tr>
                <td>
                    <strong><?php echo h((string) $item[$titleField]); ?></strong><br>
                    <span class="control-muted"><code><?php echo h($publicUrl); ?></code></span>
                </td>
                <?php if ($groupOptions !== []): ?>
                    <td>
                        <?php echo h($groupOptions[(string) ($item[$groupField] ?? '')] ?? '—'); ?>
                        <?php if ($collectionType === 'clinics'): ?>
                            <br><span class="control-muted"><?php echo h(ContentCollection::optionLabels('clinics', 'category')[(string) ($item['category'] ?? '')] ?? ''); ?></span>
                        <?php endif; ?>
                    </td>
                <?php endif; ?>
                <td><?php echo !empty($item['published']) ? 'Publicado' : 'Oculto'; ?><?php echo !empty($item['featured']) ? ' · Destacado' : ''; ?></td>
                <td><?php echo (int) $item['order']; ?></td>
                <td class="control-table__actions">
                    <a href="?edit=<?php echo urlencode((string) $item['id']); ?>&amp;tab=edit">Editar</a>
                    <a href="<?php echo h($publicUrl); ?>" target="_blank" rel="noopener">Ver</a>
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
            <label><?php echo controlFieldLabel('Estado', '"En adopción" y "En proceso" salen en Gatos en adopción. "Adoptado" pasa la ficha a Historias felices.'); ?>
                <select name="status"><?php echo $optionTags($statusOptions, (string) $form['status']); ?></select>
            </label>
            <label>Edad<input type="text" name="age" value="<?php echo h((string) $form['age']); ?>" placeholder="3 meses"></label>
            <label>Sexo<input type="text" name="sex" value="<?php echo h((string) $form['sex']); ?>" placeholder="Hembra"></label>
            <label>Tamaño<input type="text" name="size" value="<?php echo h((string) $form['size']); ?>" placeholder="Pequeña"></label>
            <label>Carácter<input type="text" name="temperament" value="<?php echo h((string) $form['temperament']); ?>" placeholder="Tranquila, juguetona"></label>
            <label class="control-check"><input type="checkbox" name="sterilized" value="1"<?php echo !empty($form['sterilized']) ? ' checked' : ''; ?>><span>Esterilizado</span></label>
            <label class="control-check"><input type="checkbox" name="vaccinated" value="1"<?php echo !empty($form['vaccinated']) ? ' checked' : ''; ?>><span>Vacunado</span></label>
            <label class="control-field--full"><?php echo controlFieldLabel('Resumen', 'Una o dos frases. Se usa en las tarjetas y en la meta description.'); ?><textarea name="summary" rows="2" required><?php echo h((string) $form['summary']); ?></textarea></label>
            <label class="control-field--full"><?php echo controlFieldLabel('Historia', 'Texto largo. En las historias felices cuenta cómo le va con su familia. Puedes usar <p>, <ul>, <li>, <strong> y enlaces.'); ?><textarea name="story" rows="10"><?php echo h((string) $form['story']); ?></textarea></label>
            <label>Texto del botón<input type="text" name="ctaLabel" value="<?php echo h((string) $form['ctaLabel']); ?>" placeholder="Quiero adoptar a…"></label>
            <label><?php echo controlFieldLabel('URL del botón', 'Vacío = abre WhatsApp con un mensaje prellenado.'); ?><input type="text" name="ctaUrl" value="<?php echo h((string) $form['ctaUrl']); ?>" placeholder="/contacto/"></label>

        <?php elseif ($collectionType === 'campaigns'): ?>
            <label class="control-field--full">Título<input type="text" name="title" value="<?php echo h((string) $form['title']); ?>" required></label>
            <label>Tipo
                <select name="kind"><?php echo $optionTags(ContentCollection::optionLabels('campaigns', 'kind'), (string) $form['kind']); ?></select>
            </label>
            <label><?php echo controlFieldLabel('Estado', 'Las finalizadas pasan al historial y ocultan el botón de registro.'); ?>
                <select name="status"><?php echo $optionTags($statusOptions, (string) $form['status']); ?></select>
            </label>
            <label><?php echo controlFieldLabel('Fecha de inicio', 'Déjala vacía si aún no está confirmada.'); ?><input type="date" name="startDate" value="<?php echo h((string) $form['startDate']); ?>"></label>
            <label><?php echo controlFieldLabel('Fecha de fin', 'Déjala vacía si es de un solo día.'); ?><input type="date" name="endDate" value="<?php echo h((string) $form['endDate']); ?>"></label>
            <label>Horario<input type="text" name="schedule" value="<?php echo h((string) $form['schedule']); ?>" placeholder="8:00 a 14:00 h"></label>
            <label>Costo<input type="text" name="cost" value="<?php echo h((string) $form['cost']); ?>" placeholder="Gratuita / cuota de recuperación"></label>
            <label class="control-field--full">Lugar<input type="text" name="place" value="<?php echo h((string) $form['place']); ?>" placeholder="Explanada municipal, Tizayuca"></label>
            <label class="control-field--full"><?php echo controlFieldLabel('Resumen', 'Una o dos frases. Se usa en las tarjetas y en la meta description.'); ?><textarea name="summary" rows="2" required><?php echo h((string) $form['summary']); ?></textarea></label>
            <label class="control-field--full"><?php echo controlFieldLabel('Contenido', 'Requisitos e indicaciones. Puedes usar <p>, <h3>, <ul>, <li>, <strong> y enlaces.'); ?><textarea name="body" rows="12"><?php echo h((string) $form['body']); ?></textarea></label>
            <label>Texto del botón<input type="text" name="ctaLabel" value="<?php echo h((string) $form['ctaLabel']); ?>" placeholder="Quiero registrarme"></label>
            <label>URL del botón<input type="text" name="ctaUrl" value="<?php echo h((string) $form['ctaUrl']); ?>" placeholder="/contacto/"></label>

        <?php elseif ($collectionType === 'guides'): ?>
            <label class="control-field--full"><?php echo controlFieldLabel('Título del caso', 'Escríbelo como lo diría la persona: "Encontré un gato herido".'); ?><input type="text" name="title" value="<?php echo h((string) $form['title']); ?>" required></label>
            <label class="control-field--full"><?php echo controlFieldLabel('Idea principal', 'Una frase. Aparece en negritas al abrir el caso.'); ?><textarea name="summary" rows="2"><?php echo h((string) $form['summary']); ?></textarea></label>
            <label class="control-field--full"><?php echo controlFieldLabel('Pasos a seguir', 'Usa <ol><li>…</li></ol> para pasos numerados o <ul><li>…</li></ul> para viñetas. También <p>, <strong> y enlaces.'); ?><textarea name="body" rows="12"><?php echo h((string) $form['body']); ?></textarea></label>
            <label>Texto del enlace<input type="text" name="ctaLabel" value="<?php echo h((string) $form['ctaLabel']); ?>" placeholder="Ver clínicas con urgencias"></label>
            <label><?php echo controlFieldLabel('URL del enlace', 'Ruta interna (/directorio/) o enlace completo.'); ?><input type="text" name="ctaUrl" value="<?php echo h((string) $form['ctaUrl']); ?>" placeholder="/directorio/"></label>
            <label class="control-check"><input type="checkbox" name="urgent" value="1"<?php echo !empty($form['urgent']) ? ' checked' : ''; ?>><span>Marcar como urgente</span></label>

        <?php elseif ($collectionType === 'clinics'): ?>
            <label><?php echo controlFieldLabel('Nombre del lugar'); ?><input type="text" name="name" value="<?php echo h((string) $form['name']); ?>" required></label>
            <label><?php echo controlFieldLabel('Zona', 'Zumpango se muestra aparte, como opción para casos específicos.'); ?>
                <select name="zone"><?php echo $optionTags(ContentCollection::optionLabels('clinics', 'zone'), (string) $form['zone']); ?></select>
            </label>
            <label><?php echo controlFieldLabel('Tipo de lugar', 'En /directorio/ cada zona se divide en clínicas, tiendas de mascotas y estéticas.'); ?>
                <select name="category"><?php echo $optionTags(ContentCollection::optionLabels('clinics', 'category'), (string) $form['category']); ?></select>
            </label>
            <label class="control-field--full"><?php echo controlFieldLabel('Nota breve', 'Opcional. Una frase sobre el lugar: "Farmacia veterinaria", "También atiende exóticos".'); ?><textarea name="summary" rows="2"><?php echo h((string) $form['summary']); ?></textarea></label>
            <label class="control-field--full"><?php echo controlFieldLabel('Servicios (uno por línea)', 'Se muestran como etiquetas: Consulta, Esterilización, Urgencias, Rayos X…'); ?><textarea name="services" rows="5" placeholder="Consulta general&#10;Esterilización&#10;Urgencias"><?php echo h(implode("\n", is_array($form['services']) ? $form['services'] : [])); ?></textarea></label>
            <label class="control-field--full"><?php echo controlFieldLabel('Caso específico', 'Opcional. Para qué caso se recomienda esta clínica (útil en las de Zumpango).'); ?><input type="text" name="specialty" value="<?php echo h((string) $form['specialty']); ?>" placeholder="Estudios de imagen y cirugía ortopédica"></label>
            <label class="control-field--full">Dirección<input type="text" name="address" value="<?php echo h((string) $form['address']); ?>"></label>
            <label>Teléfono<input type="text" name="phone" value="<?php echo h((string) $form['phone']); ?>" placeholder="779 000 0000"></label>
            <label><?php echo controlFieldLabel('WhatsApp', 'Solo dígitos, con lada del país: 527790000000.'); ?><input type="text" name="whatsapp" value="<?php echo h((string) $form['whatsapp']); ?>" placeholder="527790000000"></label>
            <label>Horario<input type="text" name="hours" value="<?php echo h((string) $form['hours']); ?>" placeholder="Lunes a sábado, 10:00 a 19:00 h"></label>
            <label><?php echo controlFieldLabel('Enlace de Google Maps', 'Pega el enlace completo (https://…) de "Compartir" en Google Maps.'); ?><input type="url" name="mapUrl" value="<?php echo h((string) $form['mapUrl']); ?>" placeholder="https://maps.app.goo.gl/…"></label>
            <label class="control-check"><input type="checkbox" name="emergency" value="1"<?php echo !empty($form['emergency']) ? ' checked' : ''; ?>><span>Atiende urgencias</span></label>
        <?php endif; ?>

        <?php if ($hasCover): ?>
            <label class="control-field--full"><?php echo controlFieldLabel('Portada (ruta)', 'Vacío = usa la primera imagen de la galería.'); ?><input type="text" name="cover" value="<?php echo h((string) $form['cover']); ?>" placeholder="/assets/images/…"></label>
            <label>Subir portada<input type="file" name="cover_file" accept="image/*"></label>
        <?php endif; ?>
        <?php if ($hasGallery): ?>
            <label class="control-field--full"><?php echo controlFieldLabel('Galería (una ruta por línea)', 'Rutas públicas de las imágenes. Puedes copiarlas desde Biblioteca de imágenes.'); ?><textarea name="gallery" rows="6" placeholder="/assets/images/foto-1.jpg"><?php echo h(implode("\n", is_array($form['gallery']) ? $form['gallery'] : [])); ?></textarea></label>
            <label>Subir imágenes a la galería<input type="file" name="gallery_files[]" accept="image/*" multiple></label>
        <?php endif; ?>

        <label><?php echo controlFieldLabel('Identificador (URL)', 'Vacío = se genera del título. Cambiarlo rompe los enlaces existentes.'); ?><input type="text" name="id" value="<?php echo h((string) $form['id']); ?>" placeholder="se-genera-solo"></label>
        <label><?php echo controlFieldLabel('Orden', 'Menor número aparece primero.'); ?><input type="number" name="order" value="<?php echo (int) $form['order']; ?>"></label>
        <label class="control-check"><input type="checkbox" name="published" value="1"<?php echo !empty($form['published']) ? ' checked' : ''; ?>><span>Publicado</span></label>
        <?php if ($hasFeatured): ?>
            <label class="control-check"><input type="checkbox" name="featured" value="1"<?php echo !empty($form['featured']) ? ' checked' : ''; ?>><span>Destacado</span></label>
        <?php endif; ?>
    </div>

    <?php controlTabsFooterStart(); ?>
        <button type="submit" class="control-btn">Guardar</button>
        <?php if ($isEditing): ?>
            <a class="control-btn control-btn--ghost" href="?tab=edit">Crear uno nuevo</a>
            <a class="control-btn control-btn--ghost" href="<?php echo h(cu(ContentCollection::itemPath($collectionType, $form))); ?>" target="_blank" rel="noopener">Ver en el sitio</a>
        <?php endif; ?>
    <?php controlTabsFooterEnd(); ?>
</form>
<?php controlTabPanelEnd(); ?>

<?php controlTabsEnd(); ?>
<?php controlFooter(); ?>
