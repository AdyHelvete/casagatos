<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
ControlAuth::requireLogin();

$config = SiteStorage::getSiteConfig();
$error = '';
$success = '';

$tabIds = ['logos', 'contact', 'whatsapp', 'social', 'footer'];
$activeTab = trim((string) ($_GET['tab'] ?? 'logos'));
if (!in_array($activeTab, $tabIds, true)) {
    $activeTab = 'logos';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    controlRequirePost();
    $activeTab = trim((string) ($_POST['_active_tab'] ?? $activeTab));
    if (!in_array($activeTab, $tabIds, true)) {
        $activeTab = 'logos';
    }

    $payload = [
        'brand' => ['name' => trim((string) ($_POST['brand_name'] ?? 'La Casa de los Gatos'))],
        'logos' => [
            'primary' => trim((string) ($_POST['logo_primary'] ?? $config['logos']['primary'])),
            'icon' => trim((string) ($_POST['logo_icon'] ?? $config['logos']['icon'])),
            'version' => (int) ($config['logos']['version'] ?? 1) + 1,
        ],
        'contact' => [
            'phone' => trim((string) ($_POST['contact_phone'] ?? '')),
            'phoneDisplay' => trim((string) ($_POST['contact_phone_display'] ?? '')),
            'phoneDisplayIntl' => trim((string) ($_POST['contact_phone_display_intl'] ?? '')),
            'email' => trim((string) ($_POST['contact_email'] ?? '')),
            'location' => trim((string) ($_POST['contact_location'] ?? '')),
        ],
        'whatsapp' => [
            'phone' => preg_replace('/\D+/', '', (string) ($_POST['whatsapp_phone'] ?? '')),
            'defaultMessage' => trim((string) ($_POST['whatsapp_message'] ?? '')),
            'floatLabel' => trim((string) ($_POST['whatsapp_label'] ?? '')),
        ],
        'social' => [
            'facebook' => trim((string) ($_POST['social_facebook'] ?? '')),
            'instagram' => trim((string) ($_POST['social_instagram'] ?? '')),
            'tiktok' => trim((string) ($_POST['social_tiktok'] ?? '')),
        ],
        'footer' => [
            'tagline' => trim((string) ($_POST['footer_tagline'] ?? '')),
            'geoText' => trim((string) ($_POST['footer_geo_text'] ?? '')),
            'privacyLabel' => trim((string) ($_POST['footer_privacy_label'] ?? '')),
            'bottomNote' => trim((string) ($_POST['footer_bottom_note'] ?? '')),
        ],
    ];

    if (!empty($_FILES['logo_primary_file']['name'])) {
        $upload = uploadLogoFile($_FILES['logo_primary_file'], 'casa-gatos-logo');
        if ($upload['success']) {
            $payload['logos']['primary'] = $upload['path'];
        } else {
            $error = $upload['message'];
        }
    }

    if (!$error && !empty($_FILES['logo_icon_file']['name'])) {
        $upload = uploadLogoFile($_FILES['logo_icon_file'], 'casa-gatos-logo-icon');
        if ($upload['success']) {
            $payload['logos']['icon'] = $upload['path'];
        } else {
            $error = $upload['message'];
        }
    }

    if (!$error && SiteStorage::saveSiteConfig($payload)) {
        $config = SiteStorage::getSiteConfig();
        $success = 'Configuración guardada correctamente.';
    } elseif (!$error) {
        $error = 'No se pudo guardar la configuración.';
    }
}

controlHeader('Configuración del sitio', 'settings');
?>
<?php if ($error): ?><div class="control-alert control-alert--error"><?php echo h($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="control-alert control-alert--success"><?php echo h($success); ?></div><?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <?php echo controlCsrfField(); ?>
    <input type="hidden" name="_active_tab" value="<?php echo h($activeTab); ?>" data-active-tab-field>

    <?php
    controlTabsStart('settings', [
        'logos' => 'Logotipos',
        'contact' => 'Contacto',
        'whatsapp' => 'WhatsApp',
        'social' => 'Redes sociales',
        'footer' => 'Footer y marca',
    ], $activeTab);
    ?>

    <?php controlTabPanelStart('settings', 'logos', $activeTab === 'logos'); ?>
    <p class="control-tabs__intro">Logo principal, favicon y rutas de archivos. Al subir una imagen nueva se incrementa la versión de caché automáticamente.</p>
    <div class="control-form control-form--grid">
        <label><?php echo controlFieldLabel('Ruta logo principal', 'Ruta pública del archivo, por ejemplo /assets/logo/casa_gatos_logo.jpg. Se usa en el encabezado y el pie.'); ?><input type="text" name="logo_primary" value="<?php echo h($config['logos']['primary']); ?>"></label>
        <label><?php echo controlFieldLabel('Subir logo principal', 'JPG, PNG, WebP o GIF. Al subir se incrementa la versión de caché para que los visitantes vean el logo nuevo.'); ?><input type="file" name="logo_primary_file" accept="image/*"></label>
        <?php if (!empty($config['logos']['primary'])): ?>
            <div class="control-field--full"><img src="<?php echo h($config['logos']['primary'] . '?v=' . (int) $config['logos']['version']); ?>" alt="" class="control-preview"></div>
        <?php endif; ?>
        <label><?php echo controlFieldLabel('Ruta favicon / icono', 'Icono de pestaña del navegador. Idealmente un PNG cuadrado.'); ?><input type="text" name="logo_icon" value="<?php echo h($config['logos']['icon']); ?>"></label>
        <label><?php echo controlFieldLabel('Subir favicon', 'Reemplaza el icono actual. Formatos: JPG, PNG, WebP o GIF (no SVG).'); ?><input type="file" name="logo_icon_file" accept="image/*"></label>
    </div>
    <?php controlTabPanelEnd(); ?>

    <?php controlTabPanelStart('settings', 'contact', $activeTab === 'contact'); ?>
    <p class="control-tabs__intro">Datos de contacto visibles en el footer y enlaces <code>tel:</code> / <code>mailto:</code> del sitio.</p>
    <div class="control-form control-form--grid">
        <label><?php echo controlFieldLabel('Teléfono (tel:)', 'Número que usa el enlace tel: del sitio. Incluye código de país, sin espacios: +525579832034.'); ?><input type="text" name="contact_phone" value="<?php echo h($config['contact']['phone']); ?>"></label>
        <label><?php echo controlFieldLabel('Teléfono visible', 'Cómo se muestra a las personas, por ejemplo 55 7983 2034.'); ?><input type="text" name="contact_phone_display" value="<?php echo h($config['contact']['phoneDisplay']); ?>"></label>
        <label><?php echo controlFieldLabel('Teléfono internacional visible', 'Versión con código de país para el bloque de datos verificados del pie.'); ?><input type="text" name="contact_phone_display_intl" value="<?php echo h($config['contact']['phoneDisplayIntl']); ?>"></label>
        <label><?php echo controlFieldLabel('Correo', 'Correo público del sitio (footer, contacto y enlaces mailto).'); ?><input type="email" name="contact_email" value="<?php echo h($config['contact']['email']); ?>"></label>
        <label class="control-field--full"><?php echo controlFieldLabel('Ubicación', 'Ciudad o zona que aparece en el pie, por ejemplo Tizayuca, Hidalgo.'); ?><input type="text" name="contact_location" value="<?php echo h($config['contact']['location']); ?>"></label>
    </div>
    <?php controlTabPanelEnd(); ?>

    <?php controlTabPanelStart('settings', 'whatsapp', $activeTab === 'whatsapp'); ?>
    <p class="control-tabs__intro">Botón flotante de WhatsApp y mensaje predeterminado al abrir una conversación.</p>
    <div class="control-form control-form--grid">
        <label><?php echo controlFieldLabel('Número (solo dígitos)', 'Código de país + número, sin + ni espacios. Ejemplo: 525579832034. Es el destino de wa.me.'); ?><input type="text" name="whatsapp_phone" value="<?php echo h($config['whatsapp']['phone']); ?>"></label>
        <label><?php echo controlFieldLabel('Etiqueta del botón flotante', 'Texto junto al botón verde, por ejemplo ¿Necesitas ayuda?'); ?><input type="text" name="whatsapp_label" value="<?php echo h($config['whatsapp']['floatLabel']); ?>"></label>
        <label class="control-field--full"><?php echo controlFieldLabel('Mensaje predeterminado', 'Se rellena solo al abrir WhatsApp desde el sitio. El visitante puede editarlo antes de enviar.'); ?><textarea name="whatsapp_message" rows="3"><?php echo h($config['whatsapp']['defaultMessage']); ?></textarea></label>
    </div>
    <?php controlTabPanelEnd(); ?>

    <?php controlTabPanelStart('settings', 'social', $activeTab === 'social'); ?>
    <p class="control-tabs__intro">Enlaces a perfiles sociales. Se muestran como iconos en el encabezado y el pie del sitio.</p>
    <div class="control-form control-form--grid">
        <label><?php echo controlFieldLabel('Facebook', 'URL completa del perfil o página. Si se deja vacío, no se muestra el icono.'); ?><input type="url" name="social_facebook" value="<?php echo h($config['social']['facebook']); ?>" placeholder="https://facebook.com/..."></label>
        <label><?php echo controlFieldLabel('Instagram', 'URL completa del perfil. Vacío = oculto en encabezado y pie.'); ?><input type="url" name="social_instagram" value="<?php echo h($config['social']['instagram']); ?>" placeholder="https://instagram.com/..."></label>
        <label><?php echo controlFieldLabel('TikTok', 'URL completa del perfil, por ejemplo https://tiktok.com/@marca'); ?><input type="url" name="social_tiktok" value="<?php echo h($config['social']['tiktok']); ?>" placeholder="https://tiktok.com/@..."></label>
    </div>
    <?php controlTabPanelEnd(); ?>

    <?php controlTabPanelStart('settings', 'footer', $activeTab === 'footer'); ?>
    <p class="control-tabs__intro">Textos del pie de página, bloque verificado para SEO y nombre de la marca.</p>
    <div class="control-form control-form--grid">
        <label><?php echo controlFieldLabel('Nombre del sitio', 'Marca que aparece en el pie, metadatos y textos automáticos.'); ?><input type="text" name="brand_name" value="<?php echo h($config['brand']['name']); ?>"></label>
        <label class="control-field--full"><?php echo controlFieldLabel('Texto bajo el logo', 'Frase corta junto al logo del pie. Describe a quién atiendes.'); ?><textarea name="footer_tagline" rows="3"><?php echo h($config['footer']['tagline']); ?></textarea></label>
        <label class="control-field--full"><?php echo controlFieldLabel('Texto SEO (bloque verificado)', 'Párrafo con ubicación y especialidades. Ayuda a buscadores y a visitantes que leen el pie.'); ?><textarea name="footer_geo_text" rows="4"><?php echo h($config['footer']['geoText']); ?></textarea></label>
        <label><?php echo controlFieldLabel('Etiqueta aviso de privacidad', 'Texto del enlace legal, por ejemplo Aviso de privacidad.'); ?><input type="text" name="footer_privacy_label" value="<?php echo h($config['footer']['privacyLabel']); ?>"></label>
        <label class="control-field--full"><?php echo controlFieldLabel('Nota inferior del footer', 'Línea pequeña al final, por ejemplo Hecho para crecer en digital.'); ?><input type="text" name="footer_bottom_note" value="<?php echo h($config['footer']['bottomNote']); ?>"></label>
    </div>
    <?php controlTabPanelEnd(); ?>

    <?php controlTabsFooterStart(); ?>
        <button type="submit" class="control-btn">Guardar cambios</button>
    <?php controlTabsFooterEnd(); ?>
    <?php controlTabsEnd(); ?>
</form>
<?php controlFooter(); ?>
