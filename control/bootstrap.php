<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/SiteStorage.php';
require_once dirname(__DIR__) . '/lib/ControlAuth.php';
require_once dirname(__DIR__) . '/lib/SecurityPolicy.php';
require_once dirname(__DIR__) . '/lib/ContactAdmin.php';
require_once dirname(__DIR__) . '/lib/MediaGallery.php';
require_once dirname(__DIR__) . '/lib/PageRegistry.php';
require_once dirname(__DIR__) . '/lib/PageImporter.php';
require_once dirname(__DIR__) . '/lib/ContentCollection.php';
require_once dirname(__DIR__) . '/lib/PageContent.php';
require_once dirname(__DIR__) . '/lib/SeoTools.php';
require_once dirname(__DIR__) . '/lib/CodeEditor.php';

ControlAuth::ensureDefaultAdmin();
ControlAuth::startSession();
SecurityPolicy::apply('admin-control');

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function cu(string $path = '/'): string
{
    return PageRegistry::url($path);
}

function controlCsrfField(): string
{
    return '<input type="hidden" name="_csrf" value="' . h(ControlAuth::csrfToken()) . '">';
}

function controlRequirePost(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        ControlAuth::requireCsrf();
    }
}

/**
 * Inicia un bloque de pestañas horizontales (estilo WordPress).
 *
 * @param array<string, string> $tabs Mapa id => etiqueta visible
 */
function controlTabsStart(string $groupId, array $tabs, string $activeId = ''): void
{
    if ($tabs === []) {
        return;
    }

    if ($activeId === '' || !isset($tabs[$activeId])) {
        $activeId = (string) array_key_first($tabs);
    }

    echo '<div class="control-tabs" data-control-tabs data-tabs-group="' . h($groupId) . '" data-initial-tab="' . h($activeId) . '">';
    echo '<div class="control-tabs__nav" role="tablist" aria-label="Secciones">';
    foreach ($tabs as $id => $label) {
        $isActive = $id === $activeId;
        $tabId = 'tab-' . $groupId . '-' . $id;
        $panelId = 'panel-' . $groupId . '-' . $id;
        echo '<button type="button" class="control-tabs__tab' . ($isActive ? ' is-active' : '') . '" role="tab" id="' . h($tabId) . '" aria-selected="' . ($isActive ? 'true' : 'false') . '" aria-controls="' . h($panelId) . '" data-tab="' . h($id) . '" tabindex="' . ($isActive ? '0' : '-1') . '">' . h($label) . '</button>';
    }
    echo '</div><div class="control-tabs__panels">';
}

function controlTabPanelStart(string $groupId, string $tabId, bool $active = false): void
{
    $panelId = 'panel-' . $groupId . '-' . $tabId;
    $tabBtnId = 'tab-' . $groupId . '-' . $tabId;
    echo '<section class="control-tabs__panel' . ($active ? ' is-active' : '') . '" role="tabpanel" id="' . h($panelId) . '" aria-labelledby="' . h($tabBtnId) . '" data-panel="' . h($tabId) . '"' . ($active ? '' : ' hidden') . '>';
}

function controlTabPanelEnd(): void
{
    echo '</section>';
}

function controlTabsFooterStart(): void
{
    echo '<div class="control-tabs__footer">';
}

function controlTabsFooterEnd(): void
{
    echo '</div>';
}

function controlTabsEnd(): void
{
    echo '</div></div>';
}

/**
 * Icono de ayuda con tooltip (hover, foco y toque).
 */
function controlHelp(string $text, string $label = 'Más información'): string
{
    $text = trim($text);
    if ($text === '') {
        return '';
    }

    $id = 'help-' . bin2hex(random_bytes(3));

    return '<button type="button" class="control-help" aria-describedby="' . h($id) . '" aria-label="' . h($label) . '">'
        . '<span class="control-help__mark" aria-hidden="true">?</span>'
        . '<span class="control-help__tip" id="' . h($id) . '" role="tooltip">' . h($text) . '</span>'
        . '</button>';
}

/**
 * Etiqueta de campo con botón de ayuda opcional.
 */
function controlFieldLabel(string $label, string $help = ''): string
{
    return '<span class="control-field-label">' . h($label) . ($help !== '' ? ' ' . controlHelp($help) : '') . '</span>';
}

function controlPageGuides(): array
{
    return [
        'index' => 'Resumen del sitio. Cada tarjeta abre un área: páginas, SEO, contenido o ajustes del sistema.',
        'pages' => 'Crea secciones, decide qué aparece en el menú y publícalas. El orden numérico más bajo sale primero.',
        'seo' => 'Títulos, descripciones y sitemap para Google. Elige una página a la izquierda y guarda por pestaña.',
        'code' => 'Edita el HTML, CSS o textos públicos. Cada guardado crea un respaldo. No toca carpetas del sistema.',
        'content' => 'Textos de cada pestaña del sitio. Elige la página, edita la sección que necesites y guarda: el cambio se ve de inmediato en el sitio.',
        'adoptions' => 'Fichas de gatos que se muestran en /adopcion/. Al cambiar el estado a Adoptado, la ficha pasa a "Historias felices".',
        'campaigns' => 'Jornadas de esterilización y TNR que se muestran en /tnr/. Las finalizadas pasan al historial.',
        'guides' => 'Casos de "Qué hacer en cada caso" de /asistencia/. Cada caso se abre como un acordeón con sus pasos.',
        'clinics' => 'Clínicas veterinarias de /directorio/, separadas por zona (Tizayuca y Zumpango) con los servicios de cada una.',
        'gallery' => 'Biblioteca de imágenes para el sitio. Arrastra archivos o elige desde el disco (JPG, PNG, WebP, GIF).',
        'forms' => 'Motivos de contacto del formulario y límites anti-spam. Lo que no esté en la lista no se acepta.',
        'contacts' => 'Mensajes de /contacto/ y cuestionarios de adopción de /adopcion/. Puedes editar, exportar o eliminar registros.',
        'settings' => 'Marca visible: logos, teléfono, WhatsApp, redes y textos del pie. Los cambios se ven en todo el sitio.',
        'security' => 'Auditoría, eventos, política CSP y rotación de secretos. Revisa avisos en rojo antes de enforce.',
        'account' => 'Tu usuario y contraseña del panel.',
    ];
}

function controlNavIcon(string $key): string
{
    $icons = [
        'index' => '<svg viewBox="0 0 20 20" aria-hidden="true"><path fill="currentColor" d="M3 3h6v8H3V3zm8 0h6v5h-6V3zM3 13h6v4H3v-4zm8 3h6v-6h-6v6z"/></svg>',
        'pages' => '<svg viewBox="0 0 20 20" aria-hidden="true"><path fill="currentColor" d="M5 2h7l5 5v11a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V3a1 1 0 0 1 1-1zm7 1.5V8h4.5L12 3.5zM6 11h8v1.5H6V11zm0 3h8v1.5H6V14z"/></svg>',
        'seo' => '<svg viewBox="0 0 20 20" aria-hidden="true"><path fill="currentColor" d="M9 3a6 6 0 1 1 3.8 10.7l3.7 3.8-1.4 1.4-3.8-3.7A6 6 0 0 1 9 3zm0 1.5a4.5 4.5 0 1 0 0 9 4.5 4.5 0 0 0 0-9z"/></svg>',
        'code' => '<svg viewBox="0 0 20 20" aria-hidden="true"><path fill="currentColor" d="M7.4 4.2 2.6 10l4.8 5.8 1.2-1-4-4.8 4-4.8-1.2-1zm5.2 0-1.2 1 4 4.8-4 4.8 1.2 1 4.8-5.8-4.8-5.8z"/></svg>',
        'adoptions' => '<svg viewBox="0 0 20 20" aria-hidden="true"><path fill="currentColor" d="M10 17.5 3.6 11.6a3.9 3.9 0 0 1 5.5-5.5l.9.9.9-.9a3.9 3.9 0 0 1 5.5 5.5L10 17.5z"/></svg>',
        'campaigns' => '<svg viewBox="0 0 20 20" aria-hidden="true"><path fill="currentColor" d="M6 2h8a1 1 0 0 1 1 1v1h2v2h-2v10a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6H3V4h2V3a1 1 0 0 1 1-1zm1 6v7h2V8H7zm4 0v7h2V8h-2z"/></svg>',
        'content' => '<svg viewBox="0 0 20 20" aria-hidden="true"><path fill="currentColor" d="M3 4h14v2H3V4zm0 4h14v2H3V8zm0 4h9v2H3v-2zm11.5 0 1.5 1.5-3.5 3.5H11v-1.5l3.5-3.5z"/></svg>',
        'guides' => '<svg viewBox="0 0 20 20" aria-hidden="true"><path fill="currentColor" d="M10 2a8 8 0 1 0 0 16 8 8 0 0 0 0-16zm-.9 12.5v-1.8h1.8v1.8H9.1zm2.6-5.1c-.6.5-.8.8-.8 1.6H9.1c0-1.4.5-2 1.200-2.600.5-.4.8-.7.8-1.300 0-.6-.5-1-1.100-1-.7 0-1.200.4-1.300 1.200H6.900C7 5.700 8.300 4.600 10 4.600c1.700 0 2.900 1 2.900 2.500 0 1.100-.6 1.700-1.200 2.300z"/></svg>',
        'clinics' => '<svg viewBox="0 0 20 20" aria-hidden="true"><path fill="currentColor" d="M3 3h14v14H3V3zm6 3v3H6v2h3v3h2v-3h3V9h-3V6H9z"/></svg>',
        'gallery' => '<svg viewBox="0 0 20 20" aria-hidden="true"><path fill="currentColor" d="M3 4h14v12H3V4zm2 2v8h10V6H5zm2 6 2-2.5 1.5 2L13 8.5 15 12H7z"/></svg>',
        'forms' => '<svg viewBox="0 0 20 20" aria-hidden="true"><path fill="currentColor" d="M5 2h10a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V3a1 1 0 0 1 1-1zm2 4h6v1.5H7V6zm0 3h6v1.5H7V9zm0 3h4v1.5H7V12z"/></svg>',
        'contacts' => '<svg viewBox="0 0 20 20" aria-hidden="true"><path fill="currentColor" d="M3 4h14v2H3V4zm0 4h14v9H3V8zm2 2v2h10v-2H5z"/></svg>',
        'settings' => '<svg viewBox="0 0 20 20" aria-hidden="true"><path fill="currentColor" d="M8.2 2h3.6l.4 2.1 1.8.7 1.8-1.1 2.5 2.5-1.1 1.8.7 1.8L20 9.2v3.6l-2.1.4-.7 1.8 1.1 1.8-2.5 2.5-1.8-1.1-1.8.7L11.8 20H8.2l-.4-2.1-1.8-.7-1.8 1.1L1.7 15.8l1.1-1.8-.7-1.8L0 10.8V7.2l2.1-.4.7-1.8L1.7 3.2 4.2.7 6 1.8l1.8-.7L8.2 2zM10 7a3 3 0 1 0 0 6 3 3 0 0 0 0-6z"/></svg>',
        'security' => '<svg viewBox="0 0 20 20" aria-hidden="true"><path fill="currentColor" d="M10 2 4 5v5c0 4.2 2.8 7.1 6 8.5 3.2-1.4 6-4.3 6-8.5V5l-6-3zm0 3.2 4 2v2.8c0 2.7-1.7 4.7-4 5.8-2.3-1.1-4-3.1-4-5.8V7.2l4-2z"/></svg>',
        'account' => '<svg viewBox="0 0 20 20" aria-hidden="true"><path fill="currentColor" d="M10 9a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7zm-6 8a6 6 0 0 1 12 0v1H4v-1z"/></svg>',
        'logout' => '<svg viewBox="0 0 20 20" aria-hidden="true"><path fill="currentColor" d="M7 3h6v2H9v10h4v2H7V3zm7 5 3 3-3 3v-2H9v-2h5V8z"/></svg>',
    ];

    return $icons[$key] ?? $icons['pages'];
}

function controlNav(string $active = ''): string
{
    $groups = [
        'Sitio' => [
            'index' => ['Dashboard', '/control/', 'Resumen y atajos a cada área del sitio.'],
            'pages' => ['Páginas y menú', '/control/pages.php', 'Secciones públicas, orden del menú y publicación.'],
            'seo' => ['SEO y sitemap', '/control/seo.php', 'Títulos, Open Graph y mapa XML para buscadores.'],
            'code' => ['Código', '/control/code.php', 'Editar plantillas, CSS y textos con respaldo.'],
        ],
        'Contenido' => [
            'content' => ['Textos de las páginas', '/control/content.php', 'Títulos, textos y listas de cada pestaña del sitio.'],
            'campaigns' => ['TNR · Jornadas', '/control/jornadas.php', 'Jornadas de esterilización y TNR.'],
            'adoptions' => ['Adopción · Gatos e historias', '/control/adoptions.php', 'Fichas de gatos en adopción e historias felices.'],
            'guides' => ['Orientación · Casos', '/control/guides.php', 'Qué hacer en cada caso (Asistencia y orientación).'],
            'clinics' => ['Directorio · Clínicas', '/control/clinics.php', 'Clínicas veterinarias de Tizayuca y Zumpango.'],
            'gallery' => ['Biblioteca de imágenes', '/control/gallery.php', 'Sube y organiza imágenes del sitio.'],
            'forms' => ['Formularios', '/control/forms.php', 'Motivos de contacto y reglas anti-spam.'],
            'contacts' => ['Contactos y cuestionarios', '/control/contacts.php', 'Mensajes y cuestionarios de adopción recibidos.'],
        ],
        'Sistema' => [
            'settings' => ['Configuración', '/control/settings.php', 'Logos, WhatsApp, redes y pie de página.'],
            'security' => ['Seguridad', '/control/security.php', 'Auditoría, CSP, eventos y secretos.'],
            'account' => ['Mi cuenta', '/control/account.php', 'Usuario y contraseña del panel.'],
        ],
    ];

    $html = '<nav class="control-nav" aria-label="Panel de control">';
    foreach ($groups as $label => $items) {
        $html .= '<div class="control-nav__group"><p class="control-nav__label">' . h($label) . '</p>';
        foreach ($items as $key => [$itemLabel, $href, $help]) {
            $class = $key === $active ? ' is-active' : '';
            $html .= '<a class="control-nav__link' . $class . '" href="' . h(cu($href)) . '" data-help="' . h($help) . '">';
            $html .= '<span class="control-nav__icon">' . controlNavIcon($key) . '</span>';
            $html .= '<span class="control-nav__name">' . h($itemLabel) . '</span>';
            $html .= '</a>';
        }
        $html .= '</div>';
    }
    $html .= '<div class="control-nav__footer">';
    $html .= '<a class="control-nav__link control-nav__link--muted" href="' . h(cu('/control/logout.php')) . '" data-help="Cierra la sesión de este panel.">';
    $html .= '<span class="control-nav__icon">' . controlNavIcon('logout') . '</span>';
    $html .= '<span class="control-nav__name">Cerrar sesión</span></a></div>';
    $html .= '</nav>';

    return $html;
}

function controlHeader(string $title, string $active = '', string $guide = ''): void
{
    $user = ControlAuth::currentUser();
    $guide = $guide !== '' ? $guide : (controlPageGuides()[$active] ?? '');

    echo '<!DOCTYPE html><html lang="es"><head>';
    echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<meta name="csrf-token" content="' . h(ControlAuth::csrfToken()) . '">';
    echo '<title>' . h($title) . ' · Control · La Casa de los Gatos</title>';
    echo '<link rel="icon" href="' . h(cu('/assets/logo/casa_gatos_logo.jpg')) . '?v=1" type="image/jpeg">';
    echo '<link rel="stylesheet" href="' . h(cu('/control/assets/control.css')) . '?v=11">';
    echo '</head><body class="control-app">';
    echo '<button type="button" class="control-sidebar__overlay" id="controlSidebarOverlay" aria-label="Cerrar menú" hidden></button>';
    echo '<div class="control-shell">';
    echo '<aside class="control-sidebar" id="controlSidebar">';
    echo '<a class="control-brand" href="' . h(cu('/control/')) . '"><img src="' . h(cu('/assets/logo/casa_gatos_logo.jpg')) . '?v=1" alt="La Casa de los Gatos" width="160" height="42"><span>Control</span></a>';
    echo controlNav($active);
    echo '</aside>';
    echo '<div class="control-main">';
    echo '<header class="control-topbar">';
    echo '<div class="control-topbar__left">';
    echo '<button type="button" class="control-menu-toggle" id="controlMenuToggle" aria-label="Abrir menú" aria-controls="controlSidebar" aria-expanded="false"><span aria-hidden="true"></span></button>';
    echo '<div><p class="control-topbar__eyebrow">Panel de administración</p><h1>' . h($title) . '</h1></div>';
    echo '</div>';
    echo '<div class="control-topbar__actions">';
    echo '<a class="control-btn control-btn--ghost control-btn--sm" href="' . h(cu('/')) . '" target="_blank" rel="noopener" data-help="Abre el sitio público en una pestaña nueva para ver los cambios.">Ver sitio</a>';
    echo '<a class="control-user" href="' . h(cu('/control/account.php')) . '" data-help="Usuario y contraseña del panel.">' . h($user['username'] ?? 'Admin') . '</a>';
    echo '</div>';
    echo '</header><main class="control-content">';
    if ($guide !== '') {
        echo '<div class="control-guide" role="note"><span class="control-guide__mark" aria-hidden="true">i</span><p>' . h($guide) . '</p></div>';
    }
}

function controlFooter(): void
{
    echo '</main></div></div>';
    echo '<div class="control-toast" id="controlToast" role="status" aria-live="polite" hidden></div>';
    echo '<script src="' . h(cu('/control/assets/control.js')) . '?v=8" defer></script>';
    echo '</body></html>';
}

function validateUploadedImage(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'No se recibió la imagen'];
    }

    $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        return ['success' => false, 'message' => 'Formato no permitido'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, (string) ($file['tmp_name'] ?? '')) : '';
    if ($finfo) {
        finfo_close($finfo);
    }

    $allowedMime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($mime, $allowedMime, true)) {
        return ['success' => false, 'message' => 'El archivo no es una imagen válida'];
    }

    if ((int) ($file['size'] ?? 0) > 8 * 1024 * 1024) {
        return ['success' => false, 'message' => 'La imagen supera el límite de 8 MB'];
    }

    return [
        'success' => true,
        'ext' => $ext === 'jpeg' ? 'jpg' : $ext,
        'mime' => $mime,
    ];
}

function uploadLogoFile(array $file, string $targetBasename): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'No se recibió la imagen'];
    }

    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        return ['success' => false, 'message' => 'Formato no permitido'];
    }

    $validation = validateUploadedImage($file);
    if (!$validation['success']) {
        return $validation;
    }

    $dir = dirname(__DIR__) . '/assets/logo';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return ['success' => false, 'message' => 'No se pudo crear la carpeta de logos'];
    }

    $filename = $targetBasename . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
    $destination = $dir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'message' => 'No se pudo guardar el logo'];
    }

    return [
        'success' => true,
        'path' => '/assets/logo/' . $filename,
    ];
}

function uploadGalleryImage(array $file, string $prefix = 'gallery'): array
{
    $validation = validateUploadedImage($file);
    if (!$validation['success']) {
        return $validation;
    }

    $prefix = preg_replace('/[^a-z0-9-]/', '', strtolower($prefix)) ?: 'gallery';
    $dir = MediaGallery::galleryDir();

    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return ['success' => false, 'message' => 'No se pudo crear la carpeta de imágenes'];
    }

    $filename = $prefix . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.' . $validation['ext'];
    $destination = $dir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'message' => 'No se pudo guardar la imagen'];
    }

    return [
        'success' => true,
        'path' => '/assets/images/gallery/' . $filename,
        'filename' => $filename,
    ];
}

/**
 * Sube varias imágenes de una sola entrada de formulario ($_FILES['x'][...]).
 *
 * @return array{paths: list<string>, errors: list<string>}
 */
function uploadContentImages(array $files, string $prefix): array
{
    $paths = [];
    $errors = [];
    $names = is_array($files['name'] ?? null) ? $files['name'] : [];

    foreach (array_keys($names) as $index) {
        $single = [
            'name' => $files['name'][$index] ?? '',
            'type' => $files['type'][$index] ?? '',
            'tmp_name' => $files['tmp_name'][$index] ?? '',
            'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$index] ?? 0,
        ];

        if ((int) $single['error'] === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        $result = uploadGalleryImage($single, $prefix);
        if (!empty($result['success'])) {
            $paths[] = $result['path'];
        } else {
            $errors[] = (string) ($single['name'] ?: 'imagen') . ': ' . (string) ($result['message'] ?? 'error');
        }
    }

    return ['paths' => $paths, 'errors' => $errors];
}
