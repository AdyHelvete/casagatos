<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
ControlAuth::requireLogin();

/**
 * Textos de las páginas: un formulario por sección de cada pestaña del sitio.
 * El esquema (secciones, campos y textos de fábrica) vive en PageContent.
 */

$schema = PageContent::schema();
$pageKey = (string) ($_GET['page'] ?? '');
if (!isset($schema[$pageKey])) {
    $pageKey = (string) array_key_first($schema);
}

$error = '';
$success = '';
$openSection = (string) ($_GET['section'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    controlRequirePost();
    $action = (string) ($_POST['action'] ?? '');
    $postPage = (string) ($_POST['page'] ?? '');
    $postSection = (string) ($_POST['section'] ?? '');

    if (!isset($schema[$postPage]['sections'][$postSection])) {
        $error = 'La sección que intentas guardar no existe.';
    } else {
        $pageKey = $postPage;
        $openSection = $postSection;
        $sectionLabel = (string) $schema[$postPage]['sections'][$postSection]['label'];

        if ($action === 'save') {
            $fields = is_array($_POST['fields'] ?? null) ? $_POST['fields'] : [];
            if (PageContent::saveSection($postPage, $postSection, $fields)) {
                $success = 'Sección "' . $sectionLabel . '" guardada. Ya está visible en el sitio.';
            } else {
                $error = 'No se pudo guardar. Revisa los permisos de escritura de la carpeta data/.';
            }
        } elseif ($action === 'reset') {
            if (PageContent::resetSection($postPage, $postSection)) {
                $success = 'Sección "' . $sectionLabel . '" restaurada a sus textos originales.';
            } else {
                $error = 'No se pudo restaurar la sección.';
            }
        }
    }
}

$page = $schema[$pageKey];
$content = PageContent::get($pageKey);

controlHeader('Textos de las páginas', 'content');
?>
<?php if ($error): ?><div class="control-alert control-alert--error"><?php echo h($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="control-alert control-alert--success"><?php echo h($success); ?></div><?php endif; ?>

<nav class="control-pagepicker" aria-label="Página a editar">
    <?php foreach ($schema as $key => $entry): ?>
        <a class="control-pagepicker__link<?php echo $key === $pageKey ? ' is-active' : ''; ?>" href="?page=<?php echo h($key); ?>"<?php echo $key === $pageKey ? ' aria-current="page"' : ''; ?>><?php echo h((string) $entry['label']); ?></a>
    <?php endforeach; ?>
</nav>

<p class="control-tabs__intro">
    Estás editando <strong><?php echo h((string) $page['label']); ?></strong>
    (<a href="<?php echo h(cu((string) $page['url'])); ?>" target="_blank" rel="noopener">ver página</a>).
    Cada sección se guarda por separado. Los textos se escriben sin formato: el diseño lo pone el sitio.
</p>

<?php foreach ($page['sections'] as $sectionKey => $section): ?>
<details class="control-panel control-section" id="seccion-<?php echo h($sectionKey); ?>"<?php echo $openSection === $sectionKey ? ' open' : ''; ?>>
    <summary class="control-section__summary">
        <span class="control-section__title"><?php echo h((string) $section['label']); ?></span>
        <span class="control-section__hint">Editar</span>
    </summary>

    <?php if (!empty($section['help'])): ?>
        <p class="control-muted control-section__help"><?php echo h((string) $section['help']); ?></p>
    <?php endif; ?>

    <form method="POST" action="?page=<?php echo h($pageKey); ?>#seccion-<?php echo h($sectionKey); ?>" class="control-form">
        <?php echo controlCsrfField(); ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="page" value="<?php echo h($pageKey); ?>">
        <input type="hidden" name="section" value="<?php echo h($sectionKey); ?>">

        <?php foreach ($section['fields'] as $fieldKey => $field): ?>
            <?php
            $value = $content[$sectionKey][$fieldKey];
            $name = 'fields[' . $fieldKey . ']';
            $help = (string) ($field['help'] ?? '');
            ?>
            <?php if ($field['type'] === 'text' || $field['type'] === 'image'): ?>
                <label><?php echo controlFieldLabel((string) $field['label'], $help); ?>
                    <input type="text" name="<?php echo h($name); ?>" value="<?php echo h((string) $value); ?>"<?php echo $field['type'] === 'image' ? ' placeholder="/assets/images/…"' : ''; ?>>
                </label>

            <?php elseif ($field['type'] === 'textarea'): ?>
                <label><?php echo controlFieldLabel((string) $field['label'], $help); ?>
                    <textarea name="<?php echo h($name); ?>" rows="4"><?php echo h((string) $value); ?></textarea>
                </label>

            <?php elseif ($field['type'] === 'lines'): ?>
                <label><?php echo controlFieldLabel((string) $field['label'], $help); ?>
                    <textarea name="<?php echo h($name); ?>" rows="<?php echo max(4, count($value) + 2); ?>"><?php echo h(implode("\n", $value)); ?></textarea>
                </label>

            <?php elseif ($field['type'] === 'items'): ?>
                <?php
                // Bloques existentes más dos en blanco para agregar nuevos.
                $blankRow = array_fill_keys(array_keys($field['fields']), '');
                $rows = array_merge($value, [$blankRow, $blankRow]);
                $existing = count($value);
                ?>
                <fieldset class="control-repeater">
                    <legend><?php echo controlFieldLabel((string) $field['label'], $help); ?></legend>
                    <?php foreach ($rows as $index => $row): ?>
                    <div class="control-repeater__item<?php echo $index >= $existing ? ' control-repeater__item--new' : ''; ?>">
                        <p class="control-repeater__tag"><?php echo $index >= $existing ? 'Nuevo bloque (opcional)' : 'Bloque ' . ($index + 1); ?></p>
                        <?php foreach ($field['fields'] as $subKey => $subLabel): ?>
                            <?php $subName = $name . '[' . $index . '][' . $subKey . ']'; ?>
                            <label><?php echo h($subLabel); ?>
                                <?php if ($subKey === 'text'): ?>
                                    <textarea name="<?php echo h($subName); ?>" rows="3"><?php echo h((string) ($row[$subKey] ?? '')); ?></textarea>
                                <?php else: ?>
                                    <input type="text" name="<?php echo h($subName); ?>" value="<?php echo h((string) ($row[$subKey] ?? '')); ?>">
                                <?php endif; ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                </fieldset>
            <?php endif; ?>
        <?php endforeach; ?>

        <div class="control-form-footer control-form-footer--flush">
            <button type="submit" class="control-btn">Guardar sección</button>
            <a class="control-btn control-btn--ghost" href="<?php echo h(cu((string) $page['url'])); ?>" target="_blank" rel="noopener">Ver en el sitio</a>
        </div>
    </form>

    <form method="POST" action="?page=<?php echo h($pageKey); ?>#seccion-<?php echo h($sectionKey); ?>" class="control-section__reset" data-confirm="¿Restaurar esta sección a sus textos originales? Se pierde lo que hayas escrito aquí.">
        <?php echo controlCsrfField(); ?>
        <input type="hidden" name="action" value="reset">
        <input type="hidden" name="page" value="<?php echo h($pageKey); ?>">
        <input type="hidden" name="section" value="<?php echo h($sectionKey); ?>">
        <button type="submit" class="control-link-danger">Restaurar textos originales</button>
    </form>
</details>
<?php endforeach; ?>

<?php controlFooter(); ?>
