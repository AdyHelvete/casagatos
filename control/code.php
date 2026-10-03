<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
ControlAuth::requireLogin();

$error = '';
$success = '';
$catalog = CodeEditor::catalog();
$selected = trim((string) ($_GET['file'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    controlRequirePost();
    $action = (string) ($_POST['action'] ?? '');
    $selected = trim((string) ($_POST['file'] ?? $selected));

    if ($action === 'save') {
        $result = CodeEditor::save($selected, (string) ($_POST['contents'] ?? ''));
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
            $pendingContents = str_replace("\r\n", "\n", (string) ($_POST['contents'] ?? ''));
        }
    } elseif ($action === 'restore') {
        $result = CodeEditor::restore($selected, (string) ($_POST['backup'] ?? ''));
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

if ($selected === '') {
    foreach ($catalog as $files) {
        $selected = (string) array_key_first($files);
        break;
    }
}

$file = $selected !== '' ? CodeEditor::read($selected) : null;
$backups = $file !== null ? CodeEditor::backupsFor($selected) : [];
$contents = $pendingContents ?? ($file['contents'] ?? '');

controlHeader('Código', 'code');
?>
<?php if ($error): ?><div class="control-alert control-alert--error"><?php echo h($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="control-alert control-alert--success"><?php echo h($success); ?></div><?php endif; ?>

<section class="control-panel control-panel--accent">
    <div class="control-panel__head">
        <h2>Editor de archivos del sitio</h2>
        <p class="control-muted">
            Puedes editar el contenido de cada página, las hojas de estilo y los archivos de texto públicos.
            Cada guardado crea un respaldo restaurable y se valida la sintaxis antes de escribir.
            Las carpetas del sistema (<code>lib/</code>, <code>control/</code>, <code>api/</code>, <code>data/</code>) no son accesibles desde aquí.
        </p>
    </div>
    <form method="GET" class="control-inline-form">
        <label>Archivo
            <select name="file" onchange="this.form.submit()">
                <?php foreach ($catalog as $group => $files): ?>
                    <optgroup label="<?php echo h((string) $group); ?>">
                        <?php foreach ($files as $relative => $label): ?>
                            <option value="<?php echo h((string) $relative); ?>" <?php echo (string) $relative === $selected ? 'selected' : ''; ?>>
                                <?php echo h((string) $label); ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit" class="control-btn control-btn--sm control-btn--ghost">Abrir</button>
    </form>
</section>

<?php if ($file === null): ?>
<section class="control-panel">
    <p class="control-empty">No se pudo abrir el archivo seleccionado. Puede que no exista o que no esté permitido editarlo.</p>
</section>
<?php else: ?>

<?php if (!$file['writable']): ?>
<div class="control-alert control-alert--error">
    El servidor no tiene permisos de escritura sobre <code><?php echo h($file['relative']); ?></code>. Ajusta los permisos del archivo para poder guardar cambios.
</div>
<?php endif; ?>

<?php if ($file['language'] === 'php'): ?>
<div class="control-alert">
    Esta es una plantilla PHP. Conserva las líneas <code>tw_page_start()</code> y <code>tw_page_end()</code>: son las que insertan el encabezado, el menú, los metadatos SEO y el footer. Edita solamente el contenido entre ambas.
</div>
<?php endif; ?>

<form method="POST" class="control-panel">
    <?php echo controlCsrfField(); ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="file" value="<?php echo h($file['relative']); ?>">
    <div class="control-panel__head">
        <h2><code><?php echo h($file['relative']); ?></code></h2>
        <p class="control-muted">
            <?php echo number_format($file['size'] / 1024, 1); ?> KB ·
            Modificado el <?php echo h(date('d/m/Y H:i', $file['modified'])); ?> ·
            Lenguaje: <?php echo h($file['language']); ?>
        </p>
    </div>
    <label class="control-field--full control-field--code">
        <textarea name="contents" class="control-code control-code--editor" spellcheck="false" wrap="off" data-code-editor rows="30"><?php echo h($contents); ?></textarea>
    </label>
    <div class="control-form-footer">
        <button type="submit" class="control-btn" <?php echo $file['writable'] ? '' : 'disabled'; ?>>Guardar cambios</button>
        <a class="control-btn control-btn--ghost control-btn--sm" href="<?php echo h(cu('/control/code.php')); ?>?file=<?php echo urlencode($file['relative']); ?>">Descartar cambios</a>
        <?php if (str_ends_with($file['relative'], 'index.php')): ?>
            <?php $pageUrl = $file['relative'] === 'index.php' ? '/' : '/' . dirname($file['relative']) . '/'; ?>
            <a class="control-btn control-btn--ghost control-btn--sm" href="<?php echo h($pageUrl); ?>" target="_blank" rel="noopener">Ver página</a>
        <?php endif; ?>
    </div>
</form>

<section class="control-panel">
    <div class="control-panel__head">
        <h2>Respaldos</h2>
        <p class="control-muted">Se guardan las 15 versiones más recientes de este archivo.</p>
    </div>
    <?php if ($backups === []): ?>
        <p class="control-empty">Todavía no hay respaldos de este archivo.</p>
    <?php else: ?>
    <div class="control-table-wrap">
        <table class="control-table">
            <thead><tr><th>Fecha</th><th>Tamaño</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($backups as $backup): ?>
                <tr>
                    <td><?php echo h(date('d/m/Y H:i:s', $backup['modified'])); ?></td>
                    <td><?php echo number_format($backup['size'] / 1024, 1); ?> KB</td>
                    <td class="control-table__actions">
                        <form method="POST" class="control-inline-form" data-confirm="Se reemplazará el archivo actual con esta versión. ¿Continuar?">
                            <?php echo controlCsrfField(); ?>
                            <input type="hidden" name="action" value="restore">
                            <input type="hidden" name="file" value="<?php echo h($file['relative']); ?>">
                            <input type="hidden" name="backup" value="<?php echo h($backup['name']); ?>">
                            <button type="submit" class="control-btn control-btn--sm control-btn--ghost">Restaurar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</section>
<?php endif; ?>
<?php controlFooter(); ?>
