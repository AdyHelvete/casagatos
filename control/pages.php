<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
ControlAuth::requireLogin();

$error = '';
$success = '';
$notice = '';

$tabIds = ['menu', 'manage', 'create', 'link', 'import'];
$activeTab = trim((string) ($_GET['tab'] ?? 'menu'));
if (!in_array($activeTab, $tabIds, true)) {
    $activeTab = 'menu';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    controlRequirePost();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'import') {
        $result = PageImporter::run(isset($_POST['overwrite']));
        if ($result['success']) {
            $success = $result['message'];
            if ($result['imported'] !== []) {
                $success .= ' ' . implode(', ', $result['imported']);
            }
            if ($result['skipped'] !== []) {
                $notice = 'Omitidas: ' . implode(' · ', $result['skipped']);
            }
            SeoTools::writeSitemap();
        } else {
            $error = $result['message'];
        }
    } elseif ($action === 'create') {
        $slug = PageRegistry::sanitizeSlug((string) ($_POST['slug'] ?? ''));
        $label = trim((string) ($_POST['label'] ?? ''));
        $title = trim((string) ($_POST['title'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $duplicateFrom = trim((string) ($_POST['duplicate_from'] ?? ''));

        if ($slug === '') {
            $error = 'Indica la dirección (slug) de la nueva página.';
        } elseif ($label === '') {
            $error = 'Indica el nombre que aparecerá en el menú.';
        } elseif (PageRegistry::find(PageRegistry::sanitizeId($slug)) !== null) {
            $error = 'Ya existe una página con esa dirección.';
        } else {
            $page = array_replace_recursive(PageRegistry::blankPage(), [
                'id' => PageRegistry::sanitizeId($slug),
                'type' => 'page',
                'slug' => $slug,
                'status' => (string) ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft',
                'title' => $title !== '' ? $title : $label,
                'description' => $description,
                'menu' => [
                    'show' => isset($_POST['menu_show']),
                    'label' => $label,
                    'order' => (int) ($_POST['order'] ?? 90),
                    'style' => (string) ($_POST['style'] ?? 'link') === 'cta' ? 'cta' : 'link',
                ],
            ]);

            $body = null;
            if ($duplicateFrom !== '') {
                $source = PageRegistry::find($duplicateFrom);
                if ($source === null) {
                    $error = 'La página que quieres duplicar no existe.';
                } else {
                    $body = PageRegistry::extractBody(PageRegistry::pageFilePath($source));
                    if ($body === null) {
                        $error = 'No se pudo leer el contenido de la página origen.';
                    } else {
                        foreach (['ogType', 'twitterCard', 'jsonLd', 'extraHead', 'bodyClass', 'ogImage'] as $field) {
                            $page[$field] = $source[$field];
                        }
                    }
                }
            }

            if ($error === '') {
                $created = PageRegistry::createPageFile($page, $body);
                if (!$created['success']) {
                    $error = $created['message'];
                } elseif (!PageRegistry::savePage($page)) {
                    $error = 'La plantilla se creó pero no se pudo guardar el registro.';
                } else {
                    SeoTools::writeSitemap();
                    $success = 'Página creada en ' . $created['path'] . '. Edita su contenido en la sección Código.';
                }
            }
        }
    } elseif ($action === 'create_link') {
        $label = trim((string) ($_POST['link_label'] ?? ''));
        $target = trim((string) ($_POST['link_target'] ?? ''));
        $id = PageRegistry::sanitizeId((string) ($_POST['link_id'] ?? $label));

        if ($label === '' || $target === '') {
            $error = 'El enlace necesita nombre y destino.';
        } elseif ($id === '') {
            $error = 'El identificador del enlace no es válido.';
        } elseif (PageRegistry::find($id) !== null) {
            $error = 'Ya existe una entrada con ese identificador.';
        } else {
            $page = array_replace_recursive(PageRegistry::blankPage(), [
                'id' => $id,
                'type' => 'link',
                'slug' => '',
                'status' => 'published',
                'title' => $label,
                'menu' => [
                    'show' => true,
                    'label' => $label,
                    'order' => (int) ($_POST['link_order'] ?? 95),
                    'style' => (string) ($_POST['link_style'] ?? 'link') === 'cta' ? 'cta' : 'link',
                    'target' => $target,
                ],
                'sitemap' => ['include' => false, 'changefreq' => 'monthly', 'priority' => '0.5', 'lastmod' => ''],
            ]);

            if (PageRegistry::savePage($page)) {
                $success = 'Enlace agregado al menú.';
            } else {
                $error = 'No se pudo guardar el enlace.';
            }
        }
    } elseif ($action === 'duplicate') {
        $sourceId = trim((string) ($_POST['source_id'] ?? ''));
        $source = PageRegistry::find($sourceId);

        if ($source === null) {
            $error = 'La página a duplicar no existe.';
        } else {
            $base = ((string) $source['slug']) === '' ? 'inicio' : (string) $source['slug'];
            $slug = PageRegistry::sanitizeSlug($base . '-copia');
            $suffix = 2;
            while (PageRegistry::find(PageRegistry::sanitizeId($slug)) !== null) {
                $slug = PageRegistry::sanitizeSlug($base . '-copia-' . $suffix);
                $suffix++;
            }

            $page = $source;
            $page['id'] = PageRegistry::sanitizeId($slug);
            $page['slug'] = $slug;
            $page['status'] = 'draft';
            $page['system'] = false;
            $page['canonical'] = '';
            $page['title'] = trim((string) $source['title']) . ' (copia)';
            $page['menu']['show'] = false;
            $page['menu']['label'] = trim((string) $source['menu']['label']) . ' (copia)';
            $page['menu']['order'] = (int) $source['menu']['order'] + 1;
            $page['sitemap']['include'] = false;
            $page['sitemap']['lastmod'] = '';

            $body = PageRegistry::extractBody(PageRegistry::pageFilePath($source));
            $created = PageRegistry::createPageFile($page, $body);

            if (!$created['success']) {
                $error = $created['message'];
            } elseif (!PageRegistry::savePage($page)) {
                $error = 'No se pudo registrar la copia.';
            } else {
                $success = 'Copia creada como borrador en ' . $created['path'] . '.';
            }
        }
    } elseif ($action === 'delete') {
        $result = PageRegistry::deletePage(trim((string) ($_POST['delete_id'] ?? '')));
        if ($result['success']) {
            $success = $result['message'];
            SeoTools::writeSitemap();
        } else {
            $error = $result['message'];
        }
    } elseif ($action === 'save_menu') {
        $registry = PageRegistry::all();
        $rows = is_array($_POST['pages'] ?? null) ? $_POST['pages'] : [];

        foreach ($registry['pages'] as $index => $page) {
            $id = (string) ($page['id'] ?? '');
            if (!isset($rows[$id]) || !is_array($rows[$id])) {
                continue;
            }
            $row = $rows[$id];

            $registry['pages'][$index]['menu']['label'] = trim((string) ($row['label'] ?? $page['menu']['label']));
            $registry['pages'][$index]['menu']['order'] = (int) ($row['order'] ?? $page['menu']['order']);
            $registry['pages'][$index]['menu']['style'] = ($row['style'] ?? 'link') === 'cta' ? 'cta' : 'link';
            $registry['pages'][$index]['menu']['show'] = !empty($row['show']);
            $registry['pages'][$index]['status'] = ($row['status'] ?? 'published') === 'published' ? 'published' : 'draft';

            if (($page['type'] ?? 'page') === 'link') {
                $target = trim((string) ($row['target'] ?? $page['menu']['target']));
                $registry['pages'][$index]['menu']['target'] = $target;
            }
        }

        if (PageRegistry::save($registry)) {
            SeoTools::writeSitemap();
            $success = 'Menú y estados actualizados.';
        } else {
            $error = 'No se pudo guardar el menú.';
        }
    }

    $postedTab = trim((string) ($_POST['_active_tab'] ?? ''));
    if (in_array($postedTab, $tabIds, true)) {
        $activeTab = $postedTab;
    }
}

$registry = PageRegistry::all();
$pages = $registry['pages'];
$templatePages = array_values(array_filter($pages, static fn(array $p): bool => ($p['type'] ?? 'page') === 'page'));

controlHeader('Páginas y menú', 'pages');
?>
<?php if ($error): ?><div class="control-alert control-alert--error"><?php echo h($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="control-alert control-alert--success"><?php echo h($success); ?></div><?php endif; ?>
<?php if ($notice): ?><div class="control-alert"><?php echo h($notice); ?></div><?php endif; ?>

<?php if ($pages === []): ?>
<section class="control-panel control-panel--accent">
    <div class="control-panel__head">
        <h2>Primer paso: importar las páginas actuales</h2>
        <p class="control-muted">Se leerán las páginas que ya existen en el sitio para registrar su SEO, su lugar en el menú y generar las plantillas administrables. Los archivos <code>.html</code> originales se conservan como respaldo.</p>
    </div>
    <form method="POST" class="control-inline-form">
        <?php echo controlCsrfField(); ?>
        <input type="hidden" name="action" value="import">
        <button type="submit" class="control-btn">Importar páginas existentes</button>
    </form>
</section>
<?php else: ?>

<?php
controlTabsStart('pages', [
    'menu' => 'Menú y publicación',
    'manage' => 'Duplicar y eliminar',
    'create' => 'Crear página',
    'link' => 'Enlace externo',
    'import' => 'Importar',
], $activeTab);
?>

<?php controlTabPanelStart('pages', 'menu', $activeTab === 'menu'); ?>
<form method="POST">
    <?php echo controlCsrfField(); ?>
    <input type="hidden" name="action" value="save_menu">
    <input type="hidden" name="_active_tab" value="menu" data-active-tab-field>
    <p class="control-tabs__intro">El orden controla la posición en el encabezado y en el footer. El estilo «Botón» la muestra como botón de llamada a la acción.</p>
    <div class="control-table-wrap">
        <table class="control-table">
            <thead>
                <tr>
                    <th>Página</th>
                    <th>Nombre en el menú</th>
                    <th>Orden</th>
                    <th>Estilo</th>
                    <th>En el menú</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pages as $page): $id = (string) $page['id']; $isLink = ($page['type'] ?? 'page') === 'link'; ?>
                <tr>
                    <td>
                        <strong><?php echo h((string) ($page['menu']['label'] ?: $id)); ?></strong>
                        <?php if (!empty($page['system'])): ?><span class="control-badge">Sistema</span><?php endif; ?>
                        <br>
                        <?php if ($isLink): ?>
                            <label class="control-muted control-inline-field">Destino
                                <input type="text" name="pages[<?php echo h($id); ?>][target]" value="<?php echo h((string) $page['menu']['target']); ?>">
                            </label>
                        <?php else: ?>
                            <a class="control-muted" href="<?php echo h(PageRegistry::pageUrl($page)); ?>" target="_blank" rel="noopener"><?php echo h(PageRegistry::pageUrl($page)); ?></a>
                            <br><span class="control-muted"><code><?php echo h(PageRegistry::pageFileRelative($page)); ?></code></span>
                        <?php endif; ?>
                    </td>
                    <td><input type="text" name="pages[<?php echo h($id); ?>][label]" value="<?php echo h((string) $page['menu']['label']); ?>"></td>
                    <td><input type="number" class="control-input--xs" name="pages[<?php echo h($id); ?>][order]" value="<?php echo (int) $page['menu']['order']; ?>" step="10"></td>
                    <td>
                        <select name="pages[<?php echo h($id); ?>][style]">
                            <option value="link" <?php echo $page['menu']['style'] === 'link' ? 'selected' : ''; ?>>Enlace</option>
                            <option value="cta" <?php echo $page['menu']['style'] === 'cta' ? 'selected' : ''; ?>>Botón</option>
                        </select>
                    </td>
                    <td><label class="control-check"><input type="checkbox" name="pages[<?php echo h($id); ?>][show]" <?php echo !empty($page['menu']['show']) ? 'checked' : ''; ?>><span>Visible</span></label></td>
                    <td>
                        <select name="pages[<?php echo h($id); ?>][status]">
                            <option value="published" <?php echo $page['status'] === 'published' ? 'selected' : ''; ?>>Publicada</option>
                            <option value="draft" <?php echo $page['status'] !== 'published' ? 'selected' : ''; ?>>Borrador</option>
                        </select>
                    </td>
                    <td class="control-table__actions">
                        <a href="<?php echo h(cu('/control/seo.php')); ?>?page=<?php echo urlencode($id); ?>&amp;section=meta">SEO</a>
                        <?php if (!$isLink): ?>
                            <a href="<?php echo h(cu('/control/code.php')); ?>?file=<?php echo urlencode(PageRegistry::pageFileRelative($page)); ?>">Código</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php controlTabsFooterStart(); ?>
        <button type="submit" class="control-btn">Guardar menú</button>
    <?php controlTabsFooterEnd(); ?>
</form>
<?php controlTabPanelEnd(); ?>

<?php controlTabPanelStart('pages', 'manage', $activeTab === 'manage'); ?>
    <p class="control-tabs__intro">Duplicar crea una copia como borrador, fuera del menú, para que puedas editarla sin afectar la página original.</p>
    <div class="control-table-wrap">
        <table class="control-table">
            <thead><tr><th>Página</th><th>Dirección</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($pages as $page): $id = (string) $page['id']; $isLink = ($page['type'] ?? 'page') === 'link'; ?>
                <tr>
                    <td><strong><?php echo h((string) ($page['menu']['label'] ?: $id)); ?></strong></td>
                    <td><code><?php echo h(PageRegistry::pageUrl($page)); ?></code></td>
                    <td class="control-table__actions">
                        <?php if (!$isLink): ?>
                        <form method="POST" class="control-inline-form">
                            <?php echo controlCsrfField(); ?>
                            <input type="hidden" name="action" value="duplicate">
                            <input type="hidden" name="source_id" value="<?php echo h($id); ?>">
                            <input type="hidden" name="_active_tab" value="manage">
                            <button type="submit" class="control-btn control-btn--sm control-btn--ghost">Duplicar</button>
                        </form>
                        <?php endif; ?>
                        <?php if (empty($page['system'])): ?>
                        <form method="POST" class="control-inline-form" data-confirm="Se eliminará la página y su archivo de plantilla. ¿Continuar?">
                            <?php echo controlCsrfField(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="delete_id" value="<?php echo h($id); ?>">
                            <input type="hidden" name="_active_tab" value="manage">
                            <button type="submit" class="control-link-danger">Eliminar</button>
                        </form>
                        <?php else: ?>
                            <span class="control-muted">Protegida</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php controlTabPanelEnd(); ?>

<?php controlTabPanelStart('pages', 'create', $activeTab === 'create'); ?>
<form method="POST">
    <?php echo controlCsrfField(); ?>
    <input type="hidden" name="action" value="create">
    <input type="hidden" name="_active_tab" value="create" data-active-tab-field>
    <p class="control-tabs__intro">Se creará la carpeta y el archivo <code>index.php</code>. Después podrás editar su contenido en la sección Código.</p>
    <div class="control-form control-form--grid">
        <label>Dirección (slug)<input type="text" name="slug" placeholder="planes-web" required><span class="control-hint">La página quedará en /planes-web/</span></label>
        <label>Nombre en el menú<input type="text" name="label" placeholder="Planes" required></label>
        <label>Título SEO<input type="text" name="title" placeholder="Cómo adoptar | La Casa de los Gatos"></label>
        <label class="control-field--full">Meta description<textarea name="description" rows="2" placeholder="Resumen de la página para buscadores (máximo 160 caracteres)."></textarea></label>
        <label>Orden en el menú<input type="number" name="order" value="45" step="10"></label>
        <label>Estilo
            <select name="style">
                <option value="link">Enlace</option>
                <option value="cta">Botón</option>
            </select>
        </label>
        <label>Estado inicial
            <select name="status">
                <option value="draft">Borrador</option>
                <option value="published">Publicada</option>
            </select>
        </label>
        <label>Copiar contenido de
            <select name="duplicate_from">
                <option value="">Plantilla en blanco</option>
                <?php foreach ($templatePages as $page): ?>
                    <option value="<?php echo h((string) $page['id']); ?>"><?php echo h((string) ($page['menu']['label'] ?: $page['id'])); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="control-check"><input type="checkbox" name="menu_show"><span>Mostrar en el menú</span></label>
    </div>
    <?php controlTabsFooterStart(); ?>
        <button type="submit" class="control-btn">Crear página</button>
    <?php controlTabsFooterEnd(); ?>
</form>
<?php controlTabPanelEnd(); ?>

<?php controlTabPanelStart('pages', 'link', $activeTab === 'link'); ?>
<form method="POST">
    <?php echo controlCsrfField(); ?>
    <input type="hidden" name="action" value="create_link">
    <input type="hidden" name="_active_tab" value="link" data-active-tab-field>
    <p class="control-tabs__intro">Para apuntar a secciones que no son páginas de este sistema, como una URL externa o una red social.</p>
    <div class="control-form control-form--grid">
        <label>Nombre<input type="text" name="link_label" placeholder="Facebook" required></label>
        <label>Destino<input type="text" name="link_target" placeholder="https://www.facebook.com/mx.lacasadelosgatos" required></label>
        <label>Identificador<input type="text" name="link_id" placeholder="facebook"></label>
        <label>Orden<input type="number" name="link_order" value="50" step="10"></label>
        <label>Estilo
            <select name="link_style">
                <option value="link">Enlace</option>
                <option value="cta">Botón</option>
            </select>
        </label>
    </div>
    <?php controlTabsFooterStart(); ?>
        <button type="submit" class="control-btn">Agregar enlace</button>
    <?php controlTabsFooterEnd(); ?>
</form>
<?php controlTabPanelEnd(); ?>

<?php controlTabPanelStart('pages', 'import', $activeTab === 'import'); ?>
    <p class="control-tabs__intro">Vuelve a leer los archivos del sitio. Útil si agregaste una carpeta manualmente por FTP. Marca la casilla sólo si quieres regenerar las plantillas desde los <code>.html</code> originales; se hará un respaldo antes.</p>
    <form method="POST" class="control-inline-form" data-confirm="¿Volver a importar las páginas del sitio?">
        <?php echo controlCsrfField(); ?>
        <input type="hidden" name="action" value="import">
        <input type="hidden" name="_active_tab" value="import">
        <label class="control-check"><input type="checkbox" name="overwrite"><span>Regenerar plantillas existentes</span></label>
        <button type="submit" class="control-btn control-btn--ghost">Importar de nuevo</button>
    </form>
<?php controlTabPanelEnd(); ?>

<?php controlTabsEnd(); ?>

<?php endif; ?>
<?php controlFooter(); ?>
