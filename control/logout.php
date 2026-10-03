<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    controlHeader('Cerrar sesión', 'account');
    ?>
    <section class="control-panel">
        <div class="control-panel__head">
            <h2>¿Cerrar sesión?</h2>
            <p class="control-muted">Confirma que deseas salir del panel de control.</p>
        </div>
        <form method="POST" class="control-form control-form--narrow">
            <?php echo controlCsrfField(); ?>
            <div class="control-form-footer control-form-footer--flush">
                <button type="submit" class="control-btn">Cerrar sesión</button>
                <a class="control-btn control-btn--ghost" href="<?php echo h(cu('/control/')); ?>">Cancelar</a>
            </div>
        </form>
    </section>
    <?php
    controlFooter();
    exit;
}

controlRequirePost();
ControlAuth::logout();
header('Location: ' . cu('/control/login.php'));
exit;
