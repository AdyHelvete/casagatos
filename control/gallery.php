<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
ControlAuth::requireLogin();

$error = '';
$success = '';
$editItem = null;
$editId = trim((string) ($_GET['edit'] ?? ''));

if ($editId !== '') {
    $editItem = MediaGallery::getById($editId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    controlRequirePost();
    $action = (string) ($_POST['action'] ?? '');
    $wantsJson = str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')
        || (string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

    if ($action === 'upload') {
        $uploaded = [];
        $failures = [];
        $files = $_FILES['images'] ?? null;

        if (is_array($files) && isset($files['name']) && is_array($files['name'])) {
            $count = count($files['name']);
            for ($i = 0; $i < $count; $i++) {
                $file = [
                    'name' => $files['name'][$i] ?? '',
                    'type' => $files['type'][$i] ?? '',
                    'tmp_name' => $files['tmp_name'][$i] ?? '',
                    'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $files['size'][$i] ?? 0,
                ];

                if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                $result = uploadGalleryImage($file, 'gallery');
                if (!$result['success']) {
                    $failures[] = $result['message'];
                    continue;
                }

                $item = MediaGallery::addUploadedFile($result['path'], $result['filename']);
                if ($item) {
                    $uploaded[] = $item;
                } else {
                    $failures[] = 'No se pudo registrar la imagen en la galería.';
                }
            }
        }

        if ($wantsJson) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => $uploaded !== [] || $failures === [],
                'uploaded' => $uploaded,
                'errors' => $failures,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($uploaded !== []) {
            $success = count($uploaded) . ' imagen(es) subida(s).';
        }
        if ($failures !== []) {
            $error = implode(' ', $failures);
        }
    } elseif ($action === 'update') {
        $id = trim((string) ($_POST['item_id'] ?? ''));
        if (MediaGallery::update($id, [
            'title' => $_POST['title'] ?? '',
            'alt' => $_POST['alt'] ?? '',
        ])) {
            $success = 'Imagen actualizada.';
            $editItem = null;
            $editId = '';
        } else {
            $error = 'No se pudo actualizar la imagen.';
        }
    } elseif ($action === 'delete') {
        $id = trim((string) ($_POST['item_id'] ?? ''));
        if (MediaGallery::delete($id)) {
            if ($wantsJson) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $success = 'Imagen eliminada.';
        } else {
            if ($wantsJson) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'No se pudo eliminar.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $error = 'No se pudo eliminar la imagen.';
        }
    }
}

$items = MediaGallery::getItems();

controlHeader('Galería de imágenes', 'gallery');
?>
<?php if ($error): ?><div class="control-alert control-alert--error"><?php echo h($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="control-alert control-alert--success"><?php echo h($success); ?></div><?php endif; ?>

<section class="control-panel">
    <div class="control-panel__head">
        <div>
            <h2>Subir imágenes</h2>
            <p class="control-muted">Arrastra archivos aquí o selecciónalos. JPG, PNG, WEBP o GIF · máx. 8 MB.</p>
        </div>
        <span class="control-badge"><?php echo count($items); ?> en galería</span>
    </div>

    <form method="POST" enctype="multipart/form-data" class="control-dropzone" id="galleryUploadForm" data-upload-endpoint="<?php echo h(cu('/control/gallery.php')); ?>">
        <?php echo controlCsrfField(); ?>
        <input type="hidden" name="action" value="upload">
        <input type="file" name="images[]" id="galleryFileInput" accept="image/jpeg,image/png,image/webp,image/gif" multiple hidden>
        <div class="control-dropzone__inner">
            <p class="control-dropzone__title">Suelta tus imágenes aquí</p>
            <p class="control-muted">o</p>
            <button type="button" class="control-btn control-btn--ghost" data-gallery-pick>Seleccionar archivos</button>
        </div>
    </form>
</section>

<?php if ($editItem): ?>
<section class="control-panel control-panel--accent">
    <div class="control-panel__head">
        <h2>Editar imagen</h2>
        <a class="control-btn control-btn--ghost control-btn--sm" href="<?php echo h(cu('/control/gallery.php')); ?>">Cancelar</a>
    </div>
    <div class="control-gallery-edit">
        <img src="<?php echo h($editItem['path']); ?>" alt="" class="control-preview control-preview--lg">
        <form method="POST" class="control-form">
            <?php echo controlCsrfField(); ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="item_id" value="<?php echo h($editItem['id']); ?>">
            <label>Título<input type="text" name="title" value="<?php echo h((string) ($editItem['title'] ?? '')); ?>" placeholder="Nombre descriptivo"></label>
            <label>Texto alternativo<input type="text" name="alt" value="<?php echo h((string) ($editItem['alt'] ?? '')); ?>" placeholder="Descripción para accesibilidad"></label>
            <label>Ruta
                <div class="control-copy-field">
                    <input type="text" readonly value="<?php echo h($editItem['path']); ?>" data-copy-source>
                    <button type="button" class="control-btn control-btn--ghost control-btn--sm" data-copy-url data-copy-value="<?php echo h($editItem['path']); ?>">Copiar ruta</button>
                </div>
            </label>
            <label>URL completa
                <div class="control-copy-field">
                    <input type="text" readonly value="<?php echo h(MediaGallery::publicUrl((string) $editItem['path'])); ?>" data-copy-source>
                    <button type="button" class="control-btn control-btn--ghost control-btn--sm" data-copy-url data-copy-value="<?php echo h(MediaGallery::publicUrl((string) $editItem['path'])); ?>">Copiar URL</button>
                </div>
            </label>
            <div class="control-form-footer control-form-footer--flush">
                <button type="submit" class="control-btn">Guardar cambios</button>
            </div>
        </form>
    </div>
</section>
<?php endif; ?>

<section class="control-panel">
    <div class="control-panel__head">
        <h2>Imágenes en galería</h2>
        <?php if (!empty($items)): ?><span class="control-badge"><?php echo count($items); ?> archivos</span><?php endif; ?>
    </div>
    <?php if (empty($items)): ?>
        <p class="control-empty">Aún no hay imágenes. Sube la primera arriba.</p>
    <?php else: ?>
        <div class="control-gallery-grid">
            <?php foreach ($items as $item): ?>
                <?php
                $path = (string) ($item['path'] ?? '');
                $fullUrl = MediaGallery::publicUrl($path);
                ?>
                <article class="control-gallery-card" data-gallery-id="<?php echo h((string) ($item['id'] ?? '')); ?>">
                    <div class="control-gallery-card__media">
                        <img src="<?php echo h($path); ?>" alt="<?php echo h((string) ($item['alt'] ?? $item['title'] ?? '')); ?>" loading="lazy">
                    </div>
                    <div class="control-gallery-card__body">
                        <p class="control-gallery-card__title"><?php echo h((string) ($item['title'] ?? $item['filename'] ?? 'Sin título')); ?></p>
                        <p class="control-muted control-gallery-card__path"><?php echo h($path); ?></p>
                        <div class="control-gallery-card__actions">
                            <button type="button" class="control-btn control-btn--ghost control-btn--sm" data-copy-url data-copy-value="<?php echo h($path); ?>">Copiar ruta</button>
                            <button type="button" class="control-btn control-btn--ghost control-btn--sm" data-copy-url data-copy-value="<?php echo h($fullUrl); ?>">Copiar URL</button>
                            <a class="control-btn control-btn--ghost control-btn--sm" href="<?php echo h(cu('/control/gallery.php')); ?>?edit=<?php echo urlencode((string) ($item['id'] ?? '')); ?>">Editar</a>
                            <form method="POST" class="control-inline-form" data-confirm="¿Eliminar esta imagen del servidor?">
                                <?php echo controlCsrfField(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="item_id" value="<?php echo h((string) ($item['id'] ?? '')); ?>">
                                <button type="submit" class="control-link-danger">Eliminar</button>
                            </form>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php controlFooter(); ?>
