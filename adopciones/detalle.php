<?php $twRoot = __DIR__; while (!is_file($twRoot . '/lib/page-boot.php') && dirname($twRoot) !== $twRoot) { $twRoot = dirname($twRoot); } require_once $twRoot . '/lib/page-boot.php'; ?>
<?php
$id = ContentCollection::slugify((string) ($_GET['id'] ?? ''));
$item = $id === '' ? null : ContentCollection::find('adoptions', $id);

if ($item === null || empty($item['published'])) {
    PageRenderer::render404();
    exit;
}

$name = (string) $item['name'];
$status = (string) ($item['status'] ?? '');
$statusLabel = ContentCollection::statusLabel('adoptions', $item);
$coverPath = ContentCollection::coverOf($item, '/assets/images/portada.jpg');
$cover = tw_url($coverPath);
$gallery = array_values(array_filter(
    is_array($item['gallery'] ?? null) ? $item['gallery'] : [],
    static fn($image): bool => trim((string) $image) !== '' && $image !== $coverPath
));

$facts = array_filter([
    'Edad' => (string) ($item['age'] ?? ''),
    'Sexo' => (string) ($item['sex'] ?? ''),
    'Tamaño' => (string) ($item['size'] ?? ''),
    'Carácter' => (string) ($item['temperament'] ?? ''),
    'Esterilizado' => !empty($item['sterilized']) ? 'Sí' : 'Aún no',
    'Vacunado' => !empty($item['vaccinated']) ? 'Sí' : 'Aún no',
], static fn(string $value): bool => trim($value) !== '');

$ctaLabel = trim((string) ($item['ctaLabel'] ?? '')) ?: 'Quiero adoptar a ' . $name;
$ctaUrl = tw_url(trim((string) ($item['ctaUrl'] ?? '')));
$whatsapp = tw_whatsapp_url('Hola La Casa de los Gatos, me interesa adoptar a ' . $name . '. ¿Sigue disponible?');
$adoptable = $status === 'disponible';

$overrides = [
    'title' => $name . ' en adopción | La Casa de los Gatos',
    'description' => (string) $item['summary'],
    'canonical' => '/adopciones/' . $item['id'] . '/',
    'ogTitle' => $name . ' busca hogar',
    'ogDescription' => (string) $item['summary'],
    'ogImage' => $cover,
    'ogType' => 'article',
];
?>
<?php tw_page_start('adopciones', $overrides); ?>
  <main id="contenido">
    <section class="page-hero page-hero--plain"><div class="container">
      <p class="breadcrumbs"><a href="<?php echo tw_esc(tw_url('/')); ?>">Inicio</a> / <a href="<?php echo tw_esc(tw_url('/adopciones/')); ?>">Adopciones</a> / <?php echo tw_esc($name); ?></p>
      <?php if ($statusLabel !== ''): ?>
        <span class="badge <?php echo tw_esc(tw_badge_class($status)); ?>"><?php echo tw_esc($statusLabel); ?></span>
      <?php endif; ?>
      <h1><?php echo tw_esc($name); ?></h1>
      <p><?php echo tw_esc((string) $item['summary']); ?></p>
    </div></section>

    <section class="section"><div class="container">
      <div class="detail-layout">
        <div>
          <div class="detail-media reveal">
            <img src="<?php echo tw_esc($cover); ?>" alt="<?php echo tw_esc($name); ?> en adopción" width="1024" height="1024" fetchpriority="high">
          </div>

          <?php if (trim((string) $item['story']) !== ''): ?>
            <div class="detail-body reveal" style="margin-top:32px">
              <h2>Su historia</h2>
              <?php echo $item['story']; ?>
            </div>
          <?php endif; ?>

          <?php if ($gallery !== []): ?>
            <div class="photo-grid reveal" style="margin-top:32px">
              <?php foreach ($gallery as $image): ?>
                <img src="<?php echo tw_esc(tw_url((string) $image)); ?>" alt="<?php echo tw_esc($name); ?>" loading="lazy" width="600" height="600">
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <aside class="detail-panel reveal">
          <h2 style="font-size:1.4rem">Ficha</h2>
          <dl class="detail-facts">
            <?php foreach ($facts as $label => $value): ?>
              <div><dt><?php echo tw_esc((string) $label); ?></dt><dd><?php echo tw_esc($value); ?></dd></div>
            <?php endforeach; ?>
          </dl>

          <?php if ($adoptable): ?>
            <div class="button-row">
              <?php if ($ctaUrl !== ''): ?>
                <a class="btn" href="<?php echo tw_esc($ctaUrl); ?>"><?php echo tw_esc($ctaLabel); ?></a>
              <?php elseif ($whatsapp !== ''): ?>
                <a class="btn" href="<?php echo tw_esc($whatsapp); ?>" target="_blank" rel="noopener" data-track-button="adopcion-whatsapp"><?php echo tw_esc($ctaLabel); ?></a>
              <?php endif; ?>
              <a class="btn btn--outline" href="<?php echo tw_esc(tw_url('/contacto/')); ?>">Enviar solicitud</a>
            </div>
            <p style="font-size:.86rem">Antes de escribir, revisa <a href="<?php echo tw_esc(tw_url('/como-adoptar/')); ?>" style="color:var(--blue);font-weight:600">cómo es el proceso</a>.</p>
          <?php elseif ($status === 'en-proceso'): ?>
            <p>Esta ficha tiene una solicitud en revisión. Si el proceso no se concreta, vuelve a estar disponible.</p>
            <a class="btn btn--outline" href="<?php echo tw_esc(tw_url('/adopciones/')); ?>">Ver otros disponibles</a>
          <?php else: ?>
            <p>¡<?php echo tw_esc($name); ?> ya encontró familia! Gracias a quienes compartieron su historia.</p>
            <a class="btn" href="<?php echo tw_esc(tw_url('/adopciones/')); ?>?estado=disponible">Ver quiénes siguen esperando</a>
          <?php endif; ?>
        </aside>
      </div>
    </div></section>
  </main>
<?php tw_page_end(); ?>
