<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
ControlAuth::requireLogin();

$error = '';
$success = '';

$tabIds = ['services', 'messages', 'security'];
$activeTab = trim((string) ($_GET['tab'] ?? 'services'));
if (!in_array($activeTab, $tabIds, true)) {
    $activeTab = 'services';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    controlRequirePost();
    $activeTab = trim((string) ($_POST['_active_tab'] ?? $activeTab));
    if (!in_array($activeTab, $tabIds, true)) {
        $activeTab = 'services';
    }

    $services = array_values(array_filter(array_map(
        static fn(string $line): string => trim($line),
        preg_split('/\r\n|\r|\n/', (string) ($_POST['services'] ?? '')) ?: []
    ), static fn(string $line): bool => $line !== ''));

    $notifyEmail = trim((string) ($_POST['notify_email'] ?? ''));

    if ($services === []) {
        $error = 'Necesitas al menos un servicio en la lista.';
    } elseif ($notifyEmail !== '' && !filter_var($notifyEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo de notificación no es válido.';
    } else {
        $payload = [
            'forms' => [
                'contact' => [
                    'services' => $services,
                    'minSeconds' => max(0, (int) ($_POST['min_seconds'] ?? 3)),
                    'maxAgeSeconds' => max(60, (int) ($_POST['max_age_seconds'] ?? 7200)),
                    'tokenTtl' => max(60, (int) ($_POST['token_ttl'] ?? 3600)),
                    'rateHour' => max(1, (int) ($_POST['rate_hour'] ?? 5)),
                    'rateDay' => max(1, (int) ($_POST['rate_day'] ?? 20)),
                    'minMessageLength' => max(1, (int) ($_POST['min_message_length'] ?? 10)),
                    'maxLinks' => max(0, (int) ($_POST['max_links'] ?? 3)),
                    'whatsappHandoff' => isset($_POST['whatsapp_handoff']),
                    'successMessage' => trim((string) ($_POST['success_message'] ?? '')),
                    'notifyEmail' => $notifyEmail,
                ],
            ],
        ];

        if (SiteStorage::saveSiteConfig($payload)) {
            $success = 'Configuración del formulario guardada. Recuerda renovar la caché de JS en SEO si no ves los cambios en el sitio.';
        } else {
            $error = 'No se pudo guardar la configuración.';
        }
    }
}

$form = SiteStorage::contactFormConfig();
$submissions = ContactAdmin::getAll();
$contactPage = PageRegistry::find('contacto');

controlHeader('Formularios', 'forms');
?>
<?php if ($error): ?><div class="control-alert control-alert--error"><?php echo h($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="control-alert control-alert--success"><?php echo h($success); ?></div><?php endif; ?>

<form method="POST">
    <?php echo controlCsrfField(); ?>
    <input type="hidden" name="_active_tab" value="<?php echo h($activeTab); ?>" data-active-tab-field>

    <?php
    controlTabsStart('forms', [
        'services' => 'Servicios',
        'messages' => 'Mensajes',
        'security' => 'Validación',
    ], $activeTab);
    ?>

    <?php controlTabPanelStart('forms', 'services', $activeTab === 'services'); ?>
    <p class="control-tabs__intro">
        Un servicio por línea. Es la lista del desplegable en <strong>/contacto/</strong> y la única que el servidor acepta.
        <?php if ($contactPage !== null): ?>
            <a href="<?php echo h(PageRegistry::pageUrl($contactPage)); ?>" target="_blank" rel="noopener">Ver formulario</a>.
        <?php endif; ?>
        Solicitudes recibidas: <strong><?php echo count($submissions); ?></strong>.
    </p>
    <div class="control-form control-form--grid">
        <label class="control-field--full"><?php echo controlFieldLabel('Servicios', 'Un servicio por línea, exactamente como debe verse en el desplegable. El servidor rechaza cualquier valor que no esté aquí.'); ?>
            <textarea name="services" rows="8" class="control-code" spellcheck="false"><?php echo h(implode("\n", $form['services'])); ?></textarea>
        </label>
    </div>
    <?php controlTabPanelEnd(); ?>

    <?php controlTabPanelStart('forms', 'messages', $activeTab === 'messages'); ?>
    <p class="control-tabs__intro">Textos que ve el visitante después de enviar y opciones de notificación.</p>
    <div class="control-form control-form--grid">
        <label class="control-field--full"><?php echo controlFieldLabel('Mensaje de éxito', 'Texto que ve el visitante cuando el envío se registra bien.'); ?>
            <input type="text" name="success_message" value="<?php echo h($form['successMessage']); ?>">
        </label>
        <label class="control-field--full"><?php echo controlFieldLabel('Correo de notificación', 'Reservado para avisos futuros. Hoy las solicitudes se guardan en Contactos.'); ?>
            <input type="email" name="notify_email" value="<?php echo h($form['notifyEmail']); ?>" placeholder="Opcional">
            <span class="control-hint">Se guarda para futuras notificaciones. Hoy las solicitudes se registran en <a href="<?php echo h(cu('/control/contacts.php')); ?>">Contactos</a>.</span>
        </label>
        <label class="control-check">
            <input type="checkbox" name="whatsapp_handoff" <?php echo $form['whatsappHandoff'] ? 'checked' : ''; ?>>
            <span>Abrir WhatsApp con el mensaje después de enviar <?php echo controlHelp('Tras guardar el envío, abre wa.me con los datos del formulario para continuar la conversación.'); ?></span>
        </label>
    </div>
    <?php controlTabPanelEnd(); ?>

    <?php controlTabPanelStart('forms', 'security', $activeTab === 'security'); ?>
    <p class="control-tabs__intro">Límites anti-spam y reglas de validación. Valores más estrictos reducen spam pero pueden bloquear envíos legítimos.</p>
    <div class="control-form control-form--grid">
        <label><?php echo controlFieldLabel('Segundos mínimos antes de enviar', 'Si alguien envía más rápido, se trata como bot. 3 segundos es un buen equilibrio.'); ?>
            <input type="number" name="min_seconds" value="<?php echo (int) $form['minSeconds']; ?>" min="0" max="60">
            <span class="control-hint">Recomendado: 3</span>
        </label>
        <label><?php echo controlFieldLabel('Vigencia del formulario (segundos)', 'Tras este tiempo hay que recargar la página para enviar. 7200 = 2 horas.'); ?>
            <input type="number" name="max_age_seconds" value="<?php echo (int) $form['maxAgeSeconds']; ?>" min="60">
            <span class="control-hint">Recomendado: 7200 (2 h)</span>
        </label>
        <label><?php echo controlFieldLabel('Vigencia del token (segundos)', 'Caducidad del token HMAC que pide el navegador al cargar el formulario.'); ?>
            <input type="number" name="token_ttl" value="<?php echo (int) $form['tokenTtl']; ?>" min="60">
            <span class="control-hint">Recomendado: 3600 (1 h)</span>
        </label>
        <label><?php echo controlFieldLabel('Envíos máximos por hora', 'Límite por dirección IP. Reduce spam repetido.'); ?>
            <input type="number" name="rate_hour" value="<?php echo (int) $form['rateHour']; ?>" min="1">
        </label>
        <label><?php echo controlFieldLabel('Envíos máximos por día', 'Tope diario por IP, además del límite por hora.'); ?>
            <input type="number" name="rate_day" value="<?php echo (int) $form['rateDay']; ?>" min="1">
        </label>
        <label><?php echo controlFieldLabel('Longitud mínima del mensaje', 'Mensajes más cortos se rechazan. Evita envíos vacíos o de una palabra.'); ?>
            <input type="number" name="min_message_length" value="<?php echo (int) $form['minMessageLength']; ?>" min="1">
        </label>
        <label><?php echo controlFieldLabel('Enlaces permitidos en el mensaje', 'Más URLs de las indicadas suelen ser spam. 3 es un límite razonable.'); ?>
            <input type="number" name="max_links" value="<?php echo (int) $form['maxLinks']; ?>" min="0">
        </label>
    </div>
    <?php controlTabPanelEnd(); ?>

    <?php controlTabsFooterStart(); ?>
        <button type="submit" class="control-btn">Guardar formulario</button>
        <a class="control-btn control-btn--ghost control-btn--sm" href="<?php echo h(cu('/control/contacts.php')); ?>">Ver solicitudes</a>
    <?php controlTabsFooterEnd(); ?>
    <?php controlTabsEnd(); ?>
</form>
<?php controlFooter(); ?>
