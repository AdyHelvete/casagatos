<?php $twRoot = __DIR__; while (!is_file($twRoot . '/lib/page-boot.php') && dirname($twRoot) !== $twRoot) { $twRoot = dirname($twRoot); } require_once $twRoot . '/lib/page-boot.php'; ?>
<?php
$id = ContentCollection::slugify((string) ($_GET['id'] ?? ''));
$album = $id === '' ? null : ContentCollection::find('albums', $id);

if ($album === null || empty($album['published'])) {
    PageRenderer::render404();
    exit;
}

$title = (string) $album['title'];
$cover = tw_url(ContentCollection::coverOf($album, '/assets/images/portada.jpg'));
$images = array_values(array_filter(
    is_array($album['gallery'] ?? null) ? $album['gallery'] : [],
    static fn($image): bool => trim((string) $image) !== ''
));

$overrides = [
    'title' => $title . ' | Galería · La Casa de los Gatos',
    'description' => (string) $album['summary'],
    'canonical' => '/galeria/' . $album['id'] . '/',
    'ogTitle' => $title,
    'ogDescription' => (string) $album['summary'],
    'ogImage' => $cover,
];
?>
<?php tw_page_start('galeria', $overrides); ?>
  <main id="contenido">
    <section class="page-hero page-hero--plain"><div class="container">
      <p class="breadcrumbs"><a href="<?php echo tw_esc(tw_url('/')); ?>">Inicio</a> / <a href="<?php echo tw_esc(tw_url('/galeria/')); ?>">Galería</a> / <?php echo tw_esc($title); ?></p>
      <span class="eyebrow eyebrow--lime">Álbum</span>
      <h1><?php echo tw_esc($title); ?></h1>
      <p><?php echo tw_esc((string) $album['summary']); ?></p>
    </div></section>

    <section class="section"><div class="container">
      <?php if ($images === []): ?>
        <div class="empty-state reveal">
          <h2>Este álbum aún no tiene fotos</h2>
          <p><a class="btn btn--outline" href="<?php echo tw_esc(tw_url('/galeria/')); ?>">Volver a la galería</a></p>
        </div>
      <?php else: ?>
        <div class="photo-grid reveal">
          <?php foreach ($images as $index => $image): ?>
            <img src="<?php echo tw_esc(tw_url((string) $image)); ?>" alt="<?php echo tw_esc($title . ' · foto ' . ($index + 1)); ?>" loading="<?php echo $index < 4 ? 'eager' : 'lazy'; ?>" width="600" height="600">
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="button-row" style="margin-top:32px">
        <a class="btn btn--outline" href="<?php echo tw_esc(tw_url('/galeria/')); ?>">Ver otros álbumes</a>
      </div>
    </div></section>
  </main>
<?php tw_page_end(); ?>
