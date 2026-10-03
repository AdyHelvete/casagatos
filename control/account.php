<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
ControlAuth::requireLogin();

$error = '';
$success = '';

$tabIds = ['acceso', 'seguridad'];
$activeTab = trim((string) ($_GET['tab'] ?? 'acceso'));
if (!in_array($activeTab, $tabIds, true)) {
    $activeTab = 'acceso';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    controlRequirePost();
    $activeTab = 'seguridad';

    $result = ControlAuth::changePassword(
        (string) ($_POST['current_password'] ?? ''),
        (string) ($_POST['new_password'] ?? '')
    );

    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['message'];
    }
}

$user = ControlAuth::currentUser();
$role = (string) ($user['role'] ?? 'admin');
$roleLabel = $role === 'admin' ? 'Administrador' : ucfirst($role);

controlHeader('Mi cuenta', 'account');
?>
<?php if ($error): ?><div class="control-alert control-alert--error"><?php echo h($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="control-alert control-alert--success"><?php echo h($success); ?></div><?php endif; ?>

<?php
controlTabsStart('account', [
    'acceso' => 'Acceso al panel',
    'seguridad' => 'Seguridad',
], $activeTab);
?>

<?php controlTabPanelStart('account', 'acceso', $activeTab === 'acceso'); ?>
<p class="control-tabs__intro">Gestiona cómo entras al panel de control del sitio. Guarda esta URL en favoritos y cierra sesión cuando termines en un equipo compartido.</p>

<section class="control-panel">
    <div class="control-panel__head">
        <h2>Tu sesión activa</h2>
        <p class="control-muted">Estás conectado como administrador del sitio.</p>
    </div>
    <dl class="control-dl">
        <div><dt>Usuario</dt><dd><strong><?php echo h($user['username'] ?? ''); ?></strong></dd></div>
        <div><dt>Rol</dt><dd><?php echo h($roleLabel); ?></dd></div>
        <div><dt>URL del panel</dt><dd><code>/control/</code></dd></div>
    </dl>
    <div class="control-form-footer control-form-footer--flush">
        <a class="control-btn" href="<?php echo h(cu('/control/')); ?>">Ir al dashboard</a>
        <a class="control-btn control-btn--ghost" href="<?php echo h(cu('/')); ?>" target="_blank" rel="noopener">Ver sitio público</a>
        <a class="control-btn control-btn--ghost" href="<?php echo h(cu('/control/logout.php')); ?>">Cerrar sesión</a>
    </div>
</section>

<section class="control-panel control-panel--accent">
    <div class="control-panel__head">
        <h2>Consejos de acceso</h2>
    </div>
    <ul class="control-list">
        <li>Usa una contraseña única de al menos 8 caracteres.</li>
        <li>Tras 5 intentos fallidos, el acceso se bloquea 15 minutos.</li>
        <li>Si existe <code>data/.initial-control-password</code>, cambia la contraseña y borra ese archivo.</li>
    </ul>
</section>
<?php controlTabPanelEnd(); ?>

<?php controlTabPanelStart('account', 'seguridad', $activeTab === 'seguridad'); ?>
<p class="control-tabs__intro">Cambia la contraseña de acceso al panel de control. Necesitarás la contraseña actual para confirmar el cambio.</p>

<section class="control-panel">
    <div class="control-panel__head">
        <h2>Contraseña del panel</h2>
        <p class="control-muted">Usuario: <strong><?php echo h($user['username'] ?? ''); ?></strong></p>
    </div>
    <form method="POST" class="control-form control-form--narrow">
        <?php echo controlCsrfField(); ?>
        <label><?php echo controlFieldLabel('Contraseña actual', 'La que usas ahora para entrar a /control/.'); ?><input type="password" name="current_password" required autocomplete="current-password"></label>
        <label><?php echo controlFieldLabel('Nueva contraseña', 'Mínimo 8 caracteres. Tras 5 fallos el acceso se bloquea 15 minutos.'); ?><input type="password" name="new_password" required autocomplete="new-password" minlength="8"><span class="control-hint">Mínimo 8 caracteres.</span></label>
        <div class="control-form-footer control-form-footer--flush">
            <button type="submit" class="control-btn">Actualizar contraseña</button>
        </div>
    </form>
</section>
<?php controlTabPanelEnd(); ?>

<?php controlTabsEnd(); ?>
<?php controlFooter(); ?>
