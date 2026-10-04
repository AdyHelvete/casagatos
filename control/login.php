<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$error = '';

if (ControlAuth::isLoggedIn()) {
    header('Location: ' . cu('/control/'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    controlRequirePost();

    if (ControlAuth::isLoginBlocked()) {
        require_once dirname(__DIR__) . '/lib/SecurityLog.php';
        SecurityLog::record('control_login_blocked', 'Intento con IP ya bloqueada');
        $error = ControlAuth::loginBlockedMessage();
    } else {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if (ControlAuth::login($username, $password)) {
            header('Location: ' . cu('/control/'));
            exit;
        }

        $error = ControlAuth::isLoginBlocked()
            ? ControlAuth::loginBlockedMessage()
            : 'Usuario o contraseña incorrectos';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo h(ControlAuth::csrfToken()); ?>">
    <title>Acceder · Control · La Casa de los Gatos</title>
    <link rel="icon" href="<?php echo h(cu('/assets/logo/casa_gatos_logo.jpg')); ?>?v=1" type="image/png">
    <link rel="stylesheet" href="<?php echo h(cu('/control/assets/control.css')); ?>?v=11">
</head>
<body class="control-login">
    <main class="control-login__card">
        <img src="<?php echo h(cu('/assets/logo/casa_gatos_logo.jpg')); ?>?v=1" alt="La Casa de los Gatos" width="180" height="48">
        <h1>Panel de control</h1>
        <p>Administra los textos del sitio, las jornadas TNR, las adopciones, la orientación, el directorio de clínicas y los mensajes recibidos.</p>

        <?php if ($error): ?>
            <div class="control-alert control-alert--error"><?php echo h($error); ?></div>
        <?php endif; ?>

        <form method="POST" class="control-form">
            <?php echo controlCsrfField(); ?>
            <label>
                Usuario
                <input type="text" name="username" required autocomplete="username">
            </label>
            <label>
                Contraseña
                <input type="password" name="password" required autocomplete="current-password">
            </label>
            <button type="submit" class="control-btn">Entrar</button>
        </form>
    </main>
</body>
</html>
