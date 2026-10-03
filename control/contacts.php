<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
ControlAuth::requireLogin();

$error = '';
$success = '';
$editRecord = null;
$editId = trim((string) ($_GET['edit'] ?? ''));

if ($editId !== '') {
    $editRecord = ContactAdmin::getById($editId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    controlRequirePost();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'update') {
        $id = trim((string) ($_POST['record_id'] ?? ''));
        if (ContactAdmin::update($id, [
            'nombre' => $_POST['nombre'] ?? '',
            'email' => $_POST['email'] ?? '',
            'telefono' => $_POST['telefono'] ?? '',
            'servicio' => $_POST['servicio'] ?? '',
            'mensaje' => $_POST['mensaje'] ?? '',
            'estado' => $_POST['estado'] ?? 'exitoso',
            'error' => $_POST['error'] ?? '',
        ])) {
            $success = 'Contacto actualizado.';
            $editRecord = null;
            $editId = '';
        } else {
            $error = 'No se pudo actualizar el contacto.';
        }
    } elseif ($action === 'delete') {
        $id = trim((string) ($_POST['record_id'] ?? ''));
        if (ContactAdmin::delete($id)) {
            $success = 'Contacto eliminado.';
        } else {
            $error = 'No se pudo eliminar el contacto.';
        }
    } elseif ($action === 'clear_all') {
        if (ContactAdmin::clearAll()) {
            $success = 'Todos los contactos fueron eliminados.';
        } else {
            $error = 'No se pudo limpiar la lista.';
        }
    }
}

$records = array_reverse(ContactAdmin::getAll());

if (isset($_GET['download']) && $_GET['download'] === 'xlsx') {
    $xlsxPath = ContactAdmin::xlsxPath();
    if (is_file($xlsxPath)) {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="contactos-casa-de-los-gatos.xlsx"');
        readfile($xlsxPath);
        exit;
    }
    ContactAdmin::regenerateXlsx();
    if (is_file($xlsxPath)) {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="contactos-casa-de-los-gatos.xlsx"');
        readfile($xlsxPath);
        exit;
    }
}

controlHeader('Contactos recibidos', 'contacts');
?>
<?php if ($error): ?><div class="control-alert control-alert--error"><?php echo h($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="control-alert control-alert--success"><?php echo h($success); ?></div><?php endif; ?>

<div class="control-toolbar">
    <div class="control-toolbar__left">
        <a class="control-btn control-btn--ghost" href="<?php echo h(cu('/control/contacts.php')); ?>?download=xlsx">Descargar Excel</a>
        <span class="control-badge"><?php echo count($records); ?> registros</span>
    </div>
    <?php if (!empty($records)): ?>
        <form method="POST" class="control-inline-form" data-confirm="¿Eliminar TODOS los contactos? Esta acción no se puede deshacer.">
            <?php echo controlCsrfField(); ?>
            <input type="hidden" name="action" value="clear_all">
            <button type="submit" class="control-btn control-btn--danger">Limpiar todo</button>
        </form>
    <?php endif; ?>
</div>

<?php if ($editRecord): ?>
<section class="control-panel control-panel--accent">
    <div class="control-panel__head">
        <h2>Editar contacto</h2>
        <a class="control-btn control-btn--ghost control-btn--sm" href="<?php echo h(cu('/control/contacts.php')); ?>">Cancelar</a>
    </div>
    <form method="POST" class="control-form control-form--grid">
        <?php echo controlCsrfField(); ?>
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="record_id" value="<?php echo h($editRecord['_id']); ?>">
        <label>Nombre<input type="text" name="nombre" value="<?php echo h((string) ($editRecord['nombre'] ?? '')); ?>" required></label>
        <label>Correo<input type="email" name="email" value="<?php echo h((string) ($editRecord['email'] ?? '')); ?>" required></label>
        <label>Teléfono<input type="text" name="telefono" value="<?php echo h((string) ($editRecord['telefono'] ?? '')); ?>"></label>
        <label>Servicio<input type="text" name="servicio" value="<?php echo h((string) ($editRecord['servicio'] ?? '')); ?>"></label>
        <label>Estado
            <select name="estado">
                <option value="exitoso" <?php echo ($editRecord['estado'] ?? '') === 'exitoso' ? 'selected' : ''; ?>>Exitoso</option>
                <option value="error" <?php echo ($editRecord['estado'] ?? '') === 'error' ? 'selected' : ''; ?>>Error</option>
            </select>
        </label>
        <label class="control-field--full">Mensaje<textarea name="mensaje" rows="4"><?php echo h((string) ($editRecord['mensaje'] ?? '')); ?></textarea></label>
        <label class="control-field--full">Detalle del error<textarea name="error" rows="2"><?php echo h((string) ($editRecord['error'] ?? '')); ?></textarea></label>
        <div class="control-form-footer control-form-footer--flush">
            <button type="submit" class="control-btn">Guardar cambios</button>
        </div>
    </form>
</section>
<?php endif; ?>

<section class="control-panel">
    <div class="control-table-wrap">
    <table class="control-table">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Estado</th>
                <th>Contacto</th>
                <th>Servicio</th>
                <th>Mensaje</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($records)): ?>
                <tr><td colspan="6" class="control-empty">No hay contactos registrados todavía.</td></tr>
            <?php else: ?>
                <?php foreach ($records as $row): ?>
                    <?php $estado = (string) ($row['estado'] ?? ''); ?>
                    <tr>
                        <td><?php echo h((string) ($row['fecha_mx'] ?? $row['fecha'] ?? '')); ?></td>
                        <td><span class="control-status control-status--<?php echo $estado === 'exitoso' ? 'ok' : 'error'; ?>"><?php echo h($estado); ?></span></td>
                        <td>
                            <strong><?php echo h((string) ($row['nombre'] ?? '')); ?></strong><br>
                            <span class="control-muted"><?php echo h((string) ($row['email'] ?? '')); ?></span><br>
                            <span class="control-muted"><?php echo h((string) ($row['telefono'] ?? '')); ?></span>
                        </td>
                        <td><?php echo h((string) ($row['servicio'] ?? '')); ?></td>
                        <td class="control-table__message"><?php echo h((string) ($row['mensaje'] ?? '')); ?></td>
                        <td class="control-table__actions">
                            <a class="control-btn control-btn--ghost control-btn--sm" href="<?php echo h(cu('/control/contacts.php')); ?>?edit=<?php echo urlencode((string) ($row['_id'] ?? '')); ?>">Editar</a>
                            <form method="POST" class="control-inline-form" data-confirm="¿Eliminar este contacto?">
                                <?php echo controlCsrfField(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="record_id" value="<?php echo h((string) ($row['_id'] ?? '')); ?>">
                                <button type="submit" class="control-link-danger">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</section>
<?php controlFooter(); ?>
