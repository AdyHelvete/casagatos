<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
ControlAuth::requireLogin();

$error = '';
$success = '';
$selectedId = trim((string) ($_GET['page'] ?? ''));

$mainSectionIds = ['meta', 'global', 'sitemap', 'audit'];
$metaTabIds = ['general', 'social', 'schema', 'page-sitemap'];
$mainSection = trim((string) ($_GET['section'] ?? 'meta'));
$metaTab = trim((string) ($_GET['tab'] ?? 'general'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    controlRequirePost();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'save_page') {
        $id = trim((string) ($_POST['id'] ?? ''));
        $page = PageRegistry::find($id);
        $selectedId = $id;

        if ($page === null) {
            $error = 'La página no existe en el registro.';
        } else {
            $jsonLd = trim((string) ($_POST['json_ld'] ?? ''));
            if ($jsonLd !== '' && json_decode($jsonLd) === null) {
                $error = 'Los datos estructurados no son JSON válido: ' . json_last_error_msg();
            } else {
                $page = array_replace_recursive($page, [
                    'title' => trim((string) ($_POST['title'] ?? '')),
                    'description' => trim((string) ($_POST['description'] ?? '')),
                    'canonical' => trim((string) ($_POST['canonical'] ?? '')),
                    'robots' => trim((string) ($_POST['robots'] ?? '')),
                    'ogType' => trim((string) ($_POST['og_type'] ?? 'website')),
                    'ogTitle' => trim((string) ($_POST['og_title'] ?? '')),
                    'ogDescription' => trim((string) ($_POST['og_description'] ?? '')),
                    'ogImage' => trim((string) ($_POST['og_image'] ?? '')),
                    'twitterCard' => trim((string) ($_POST['twitter_card'] ?? 'summary_large_image')),
                    'twitterTitle' => trim((string) ($_POST['twitter_title'] ?? '')),
                    'twitterDescription' => trim((string) ($_POST['twitter_description'] ?? '')),
                    'manifest' => isset($_POST['manifest']),
                    'bodyClass' => trim((string) ($_POST['body_class'] ?? '')),
                    'extraHead' => trim((string) ($_POST['extra_head'] ?? '')),
                    'jsonLd' => $jsonLd,
                    'sitemap' => [
                        'include' => isset($_POST['sitemap_include']),
                        'changefreq' => trim((string) ($_POST['changefreq'] ?? 'monthly')),
                        'priority' => trim((string) ($_POST['priority'] ?? '0.6')),
                        'lastmod' => trim((string) ($_POST['lastmod'] ?? '')),
                    ],
                ]);

                if (PageRegistry::savePage($page)) {
                    SeoTools::writeSitemap();
                    $success = 'Metadatos guardados y sitemap actualizado.';
                } else {
                    $error = 'No se pudieron guardar los metadatos.';
                }
            }
        }
    } elseif ($action === 'save_settings') {
        $settings = [
            'siteUrl' => rtrim(trim((string) ($_POST['site_url'] ?? '')), '/'),
            'siteName' => trim((string) ($_POST['site_name'] ?? '')),
            'author' => trim((string) ($_POST['author'] ?? '')),
            'lang' => trim((string) ($_POST['lang'] ?? 'es-MX')),
            'locale' => trim((string) ($_POST['locale'] ?? 'es_MX')),
            'defaultRobots' => trim((string) ($_POST['default_robots'] ?? '')),
            'defaultOgImage' => trim((string) ($_POST['default_og_image'] ?? '')),
            'themeColor' => trim((string) ($_POST['theme_color'] ?? '#071225')),
            'geoRegion' => trim((string) ($_POST['geo_region'] ?? '')),
            'geoPlacename' => trim((string) ($_POST['geo_placename'] ?? '')),
            'cssVersion' => max(1, (int) ($_POST['css_version'] ?? 1)),
            'jsVersion' => max(1, (int) ($_POST['js_version'] ?? 1)),
            'configJsVersion' => max(1, (int) ($_POST['config_js_version'] ?? 1)),
        ];

        if (PageRegistry::saveSettings($settings)) {
            SeoTools::writeSitemap();
            $success = 'Ajustes globales guardados.';
        } else {
            $error = 'No se pudieron guardar los ajustes globales.';
        }
    } elseif ($action === 'bump_css') {
        $settings = PageRegistry::settings();
        $settings['cssVersion'] = ((int) $settings['cssVersion']) + 1;
        $settings['jsVersion'] = ((int) $settings['jsVersion']) + 1;
        $settings['configJsVersion'] = ((int) $settings['configJsVersion']) + 1;

        if (PageRegistry::saveSettings($settings)) {
            $success = 'Caché de CSS y JS renovada (v' . $settings['cssVersion'] . ').';
        } else {
            $error = 'No se pudo actualizar la versión de los archivos.';
        }
    } elseif ($action === 'regenerate_sitemap') {
        $result = SeoTools::writeSitemap();
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }

    $postedMain = trim((string) ($_POST['_main_section'] ?? ''));
    if (in_array($postedMain, $mainSectionIds, true)) {
        $mainSection = $postedMain;
    }
    $postedMetaTab = trim((string) ($_POST['_active_tab'] ?? ''));
    if (in_array($postedMetaTab, $metaTabIds, true)) {
        $metaTab = $postedMetaTab;
    }
}

if (!in_array($mainSection, $mainSectionIds, true)) {
    $mainSection = 'meta';
}
if (!in_array($metaTab, $metaTabIds, true)) {
    $metaTab = 'general';
}

$registry = PageRegistry::all();
$pages = $registry['pages'];
$settings = $registry['settings'];
$editablePages = array_values(array_filter($pages, static fn(array $p): bool => ($p['type'] ?? 'page') === 'page'));

if ($selectedId === '' && $editablePages !== []) {
    $selectedId = (string) $editablePages[0]['id'];
}

$page = PageRegistry::find($selectedId);
$audit = SeoTools::audit();
$sitemapEntries = SeoTools::sitemapEntries();
$sitemapFile = SeoTools::sitemapPath();

controlHeader('SEO y sitemap', 'seo');
?>
<?php if ($error): ?><div class="control-alert control-alert--error"><?php echo h($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="control-alert control-alert--success"><?php echo h($success); ?></div><?php endif; ?>

<?php if ($pages === []): ?>
<section class="control-panel control-panel--accent">
    <h2>Aún no hay páginas registradas</h2>
    <p class="control-muted">Importa las páginas del sitio desde <a href="<?php echo h(cu('/control/pages.php')); ?>">Páginas y menú</a> para poder editar sus metadatos.</p>
</section>
<?php else: ?>

<?php
controlTabsStart('seo-main', [
    'meta' => 'Metadatos',
    'global' => 'Global',
    'sitemap' => 'Sitemap y caché',
    'audit' => 'Auditoría',
], $mainSection);
?>

<?php controlTabPanelStart('seo-main', 'meta', $mainSection === 'meta'); ?>
    <form method="GET" class="control-inline-form control-tabs__toolbar">
        <input type="hidden" name="section" value="meta">
        <input type="hidden" name="tab" value="<?php echo h($metaTab); ?>">
        <label>Página
            <select name="page" onchange="this.form.submit()">
                <?php foreach ($pages as $item): ?>
                    <option value="<?php echo h((string) $item['id']); ?>" <?php echo (string) $item['id'] === $selectedId ? 'selected' : ''; ?>>
                        <?php echo h((string) ($item['menu']['label'] ?: $item['id'])); ?> — <?php echo h(PageRegistry::pageUrl($item)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit" class="control-btn control-btn--sm control-btn--ghost">Abrir</button>
    </form>

    <?php if ($page !== null): ?>
    <form method="POST">
        <?php echo controlCsrfField(); ?>
        <input type="hidden" name="action" value="save_page">
        <input type="hidden" name="id" value="<?php echo h((string) $page['id']); ?>">
        <input type="hidden" name="_main_section" value="meta">
        <input type="hidden" name="_active_tab" value="<?php echo h($metaTab); ?>" data-active-tab-field>

        <p class="control-tabs__intro">
            <strong><?php echo h((string) ($page['menu']['label'] ?: $page['id'])); ?></strong>
            · <a href="<?php echo h(PageRegistry::pageUrl($page)); ?>" target="_blank" rel="noopener"><?php echo h(PageRegistry::pageUrl($page)); ?></a>
            <?php if (($page['type'] ?? 'page') === 'page'): ?> · <code><?php echo h(PageRegistry::pageFileRelative($page)); ?></code><?php endif; ?>
        </p>

        <?php
        controlTabsStart('seo-meta', [
            'general' => 'General',
            'social' => 'Redes sociales',
            'schema' => 'Datos estructurados',
            'page-sitemap' => 'Sitemap',
        ], $metaTab);
        ?>

        <?php controlTabPanelStart('seo-meta', 'general', $metaTab === 'general'); ?>
        <div class="control-form control-form--grid">
            <label class="control-field--full">Título (title)
                <input type="text" name="title" value="<?php echo h((string) $page['title']); ?>" maxlength="120">
                <span class="control-hint">Ideal entre 40 y 60 caracteres. Actual: <?php echo mb_strlen((string) $page['title']); ?>.</span>
            </label>
            <label class="control-field--full">Meta description
                <textarea name="description" rows="3" maxlength="320"><?php echo h((string) $page['description']); ?></textarea>
                <span class="control-hint">Ideal entre 120 y 160 caracteres. Actual: <?php echo mb_strlen((string) $page['description']); ?>.</span>
            </label>
            <label>URL canónica
                <input type="text" name="canonical" value="<?php echo h((string) $page['canonical']); ?>" placeholder="Se calcula automáticamente">
            </label>
            <label>Robots
                <input type="text" name="robots" value="<?php echo h((string) $page['robots']); ?>" placeholder="<?php echo h((string) $settings['defaultRobots']); ?>">
                <span class="control-hint">Vacío usa el valor global. Escribe <code>noindex, nofollow</code> para excluirla de buscadores.</span>
            </label>
        </div>
        <?php controlTabPanelEnd(); ?>

        <?php controlTabPanelStart('seo-meta', 'social', $metaTab === 'social'); ?>
        <div class="control-form control-form--grid">
            <label>og:type
                <select name="og_type">
                    <?php foreach (['website', 'article', 'profile'] as $type): ?>
                        <option value="<?php echo $type; ?>" <?php echo ($page['ogType'] ?: 'website') === $type ? 'selected' : ''; ?>><?php echo $type; ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Tipo de tarjeta en X/Twitter
                <select name="twitter_card">
                    <?php foreach (['summary_large_image', 'summary'] as $card): ?>
                        <option value="<?php echo $card; ?>" <?php echo ($page['twitterCard'] ?: 'summary_large_image') === $card ? 'selected' : ''; ?>><?php echo $card; ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="control-field--full">Imagen para compartir (og:image)
                <input type="text" name="og_image" value="<?php echo h((string) $page['ogImage']); ?>" placeholder="<?php echo h((string) $settings['defaultOgImage']); ?>">
                <span class="control-hint">Ruta del sitio o URL completa. Puedes copiar una desde la <a href="<?php echo h(cu('/control/gallery.php')); ?>">galería</a>.</span>
            </label>
            <label>og:title<input type="text" name="og_title" value="<?php echo h((string) $page['ogTitle']); ?>" placeholder="Usa el título de la página"></label>
            <label>twitter:title<input type="text" name="twitter_title" value="<?php echo h((string) $page['twitterTitle']); ?>" placeholder="Usa og:title"></label>
            <label class="control-field--full">og:description<textarea name="og_description" rows="2"><?php echo h((string) $page['ogDescription']); ?></textarea></label>
            <label class="control-field--full">twitter:description<textarea name="twitter_description" rows="2"><?php echo h((string) $page['twitterDescription']); ?></textarea></label>
        </div>
        <?php controlTabPanelEnd(); ?>

        <?php controlTabPanelStart('seo-meta', 'schema', $metaTab === 'schema'); ?>
        <div class="control-form control-form--grid">
            <label class="control-field--full">JSON-LD (schema.org)
                <textarea name="json_ld" rows="14" class="control-code" spellcheck="false"><?php echo h((string) $page['jsonLd']); ?></textarea>
                <span class="control-hint">Se valida como JSON antes de guardar. Se inserta dentro de <code>&lt;script type="application/ld+json"&gt;</code>.</span>
            </label>
            <label class="control-field--full">Etiquetas extra para el &lt;head&gt;
                <textarea name="extra_head" rows="4" class="control-code" spellcheck="false"><?php echo h((string) $page['extraHead']); ?></textarea>
                <span class="control-hint">HTML que se agrega tal cual antes del JSON-LD. Útil para verificaciones o CSS específico de la página.</span>
            </label>
            <label>Clase CSS del &lt;body&gt;<input type="text" name="body_class" value="<?php echo h((string) $page['bodyClass']); ?>"></label>
            <label class="control-check"><input type="checkbox" name="manifest" <?php echo !empty($page['manifest']) ? 'checked' : ''; ?>><span>Enlazar site.webmanifest</span></label>
        </div>
        <?php controlTabPanelEnd(); ?>

        <?php controlTabPanelStart('seo-meta', 'page-sitemap', $metaTab === 'page-sitemap'); ?>
        <div class="control-form control-form--grid">
            <label class="control-check"><input type="checkbox" name="sitemap_include" <?php echo !empty($page['sitemap']['include']) ? 'checked' : ''; ?>><span>Incluir en sitemap.xml</span></label>
            <label>Frecuencia de cambio
                <select name="changefreq">
                    <?php foreach (['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'] as $freq): ?>
                        <option value="<?php echo $freq; ?>" <?php echo ($page['sitemap']['changefreq'] ?? '') === $freq ? 'selected' : ''; ?>><?php echo $freq; ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Prioridad
                <select name="priority">
                    <?php foreach (['1.0', '0.9', '0.8', '0.7', '0.6', '0.5', '0.3', '0.1'] as $priority): ?>
                        <option value="<?php echo $priority; ?>" <?php echo ($page['sitemap']['priority'] ?? '') === $priority ? 'selected' : ''; ?>><?php echo $priority; ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Última modificación
                <input type="date" name="lastmod" value="<?php echo h((string) ($page['sitemap']['lastmod'] ?? '')); ?>">
                <span class="control-hint">Vacío usa la fecha del archivo.</span>
            </label>
        </div>
        <?php controlTabPanelEnd(); ?>

        <?php controlTabsFooterStart(); ?>
            <button type="submit" class="control-btn">Guardar metadatos</button>
            <a class="control-btn control-btn--ghost control-btn--sm" href="<?php echo h(PageRegistry::pageUrl($page)); ?>" target="_blank" rel="noopener">Ver página</a>
        <?php controlTabsFooterEnd(); ?>
        <?php controlTabsEnd(); ?>
    </form>
    <?php endif; ?>
<?php controlTabPanelEnd(); ?>

<?php controlTabPanelStart('seo-main', 'global', $mainSection === 'global'); ?>
    <form method="POST">
        <?php echo controlCsrfField(); ?>
        <input type="hidden" name="action" value="save_settings">
        <input type="hidden" name="_main_section" value="global">
        <p class="control-tabs__intro">Valores que se aplican a todas las páginas cuando no tienen uno propio.</p>
        <div class="control-form control-form--grid">
            <label>URL del sitio<input type="url" name="site_url" value="<?php echo h((string) $settings['siteUrl']); ?>" required></label>
            <label>Nombre del sitio<input type="text" name="site_name" value="<?php echo h((string) $settings['siteName']); ?>"></label>
            <label>Autor<input type="text" name="author" value="<?php echo h((string) $settings['author']); ?>"></label>
            <label>Idioma (lang)<input type="text" name="lang" value="<?php echo h((string) $settings['lang']); ?>"></label>
            <label>Locale (og:locale)<input type="text" name="locale" value="<?php echo h((string) $settings['locale']); ?>"></label>
            <label>Color del tema<input type="text" name="theme_color" value="<?php echo h((string) $settings['themeColor']); ?>"></label>
            <label>geo.region<input type="text" name="geo_region" value="<?php echo h((string) $settings['geoRegion']); ?>"></label>
            <label>geo.placename<input type="text" name="geo_placename" value="<?php echo h((string) $settings['geoPlacename']); ?>"></label>
            <label class="control-field--full">Robots por defecto<input type="text" name="default_robots" value="<?php echo h((string) $settings['defaultRobots']); ?>"></label>
            <label class="control-field--full">Imagen social por defecto<input type="text" name="default_og_image" value="<?php echo h((string) $settings['defaultOgImage']); ?>"></label>
            <label>Versión CSS<input type="number" name="css_version" value="<?php echo (int) $settings['cssVersion']; ?>" min="1"></label>
            <label>Versión JS<input type="number" name="js_version" value="<?php echo (int) $settings['jsVersion']; ?>" min="1"></label>
            <label>Versión site-config.js<input type="number" name="config_js_version" value="<?php echo (int) $settings['configJsVersion']; ?>" min="1"></label>
        </div>
        <div class="control-form-footer">
            <button type="submit" class="control-btn">Guardar ajustes</button>
        </div>
    </form>
<?php controlTabPanelEnd(); ?>

<?php controlTabPanelStart('seo-main', 'sitemap', $mainSection === 'sitemap'); ?>
    <p class="control-tabs__intro">
        <?php echo count($sitemapEntries); ?> URL(s) en el sitemap.
        <?php if (is_file($sitemapFile)): ?>
            Última escritura: <?php echo h(date('d/m/Y H:i', (int) filemtime($sitemapFile))); ?>.
        <?php else: ?>
            El archivo aún no existe.
        <?php endif; ?>
        <?php if (!is_writable(is_file($sitemapFile) ? $sitemapFile : dirname($sitemapFile))): ?>
            <strong>Atención:</strong> el servidor no tiene permisos de escritura en la raíz del sitio.
        <?php endif; ?>
    </p>
    <div class="control-table-wrap">
        <table class="control-table">
            <thead><tr><th>URL</th><th>Modificada</th><th>Frecuencia</th><th>Prioridad</th></tr></thead>
            <tbody>
            <?php foreach ($sitemapEntries as $entry): ?>
                <tr>
                    <td><code><?php echo h($entry['loc']); ?></code></td>
                    <td><?php echo h($entry['lastmod'] !== '' ? $entry['lastmod'] : '—'); ?></td>
                    <td><?php echo h($entry['changefreq']); ?></td>
                    <td><?php echo h($entry['priority']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="control-toolbar">
        <form method="POST" class="control-inline-form">
            <?php echo controlCsrfField(); ?>
            <input type="hidden" name="action" value="regenerate_sitemap">
            <input type="hidden" name="_main_section" value="sitemap">
            <button type="submit" class="control-btn">Regenerar sitemap.xml</button>
        </form>
        <a class="control-btn control-btn--ghost control-btn--sm" href="<?php echo h(cu('/sitemap.xml')); ?>" target="_blank" rel="noopener">Ver sitemap</a>
        <a class="control-btn control-btn--ghost control-btn--sm" href="<?php echo h(cu('/control/code.php')); ?>?file=robots.txt">Editar robots.txt</a>
    </div>
    <div class="control-panel__head control-panel__head--sub"><h3>Caché del navegador</h3></div>
    <p class="control-muted">Si editaste CSS o JavaScript y no ves los cambios en el sitio, sube el número de versión para que los navegadores descarguen los archivos nuevos.</p>
    <form method="POST" class="control-inline-form">
        <?php echo controlCsrfField(); ?>
        <input type="hidden" name="action" value="bump_css">
        <input type="hidden" name="_main_section" value="sitemap">
        <button type="submit" class="control-btn control-btn--ghost">Renovar caché (CSS v<?php echo (int) $settings['cssVersion']; ?> → v<?php echo ((int) $settings['cssVersion']) + 1; ?>)</button>
    </form>
<?php controlTabPanelEnd(); ?>

<?php controlTabPanelStart('seo-main', 'audit', $mainSection === 'audit'); ?>
    <p class="control-tabs__intro">Detalles que conviene corregir en cada página.</p>
    <div class="control-table-wrap">
        <table class="control-table">
            <thead><tr><th>Página</th><th>Observaciones</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($audit as $row): ?>
                <tr>
                    <td><strong><?php echo h($row['label']); ?></strong><br><span class="control-muted"><?php echo h($row['url']); ?></span></td>
                    <td>
                        <?php if ($row['issues'] === []): ?>
                            <span class="control-status control-status--ok">Sin observaciones</span>
                        <?php else: ?>
                            <ul class="control-issues">
                                <?php foreach ($row['issues'] as $issue): ?><li><?php echo h($issue); ?></li><?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </td>
                    <td><a href="<?php echo h(cu('/control/seo.php')); ?>?page=<?php echo urlencode($row['id']); ?>&amp;section=meta">Editar</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php controlTabPanelEnd(); ?>

<?php controlTabsEnd(); ?>

<?php endif; ?>
<?php controlFooter(); ?>
