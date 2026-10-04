<?php $twRoot = __DIR__; while (!is_file($twRoot . '/lib/page-boot.php') && dirname($twRoot) !== $twRoot) { $twRoot = dirname($twRoot); } require_once $twRoot . '/lib/page-boot.php'; ?>
<?php
$id = ContentCollection::slugify((string) ($_GET['id'] ?? ''));
$item = $id === '' ? null : ContentCollection::find('campaigns', $id);

if ($item === null || empty($item['published'])) {
    PageRenderer::render404();
    exit;
}

$title = (string) $item['title'];
$status = (string) ($item['status'] ?? '');
$statusLabel = ContentCollection::statusLabel('campaigns', $item);
$kind = ContentCollection::optionLabels('campaigns', 'kind')[(string) ($item['kind'] ?? '')] ?? 'Jornada';
$coverPath = ContentCollection::coverOf($item, '/assets/images/campana-esterilizacion.jpg');
$cover = tw_url($coverPath);
$when = tw_date_range((string) ($item['startDate'] ?? ''), (string) ($item['endDate'] ?? ''));
$gallery = array_values(array_filter(
    is_array($item['gallery'] ?? null) ? $item['gallery'] : [],
    static fn($image): bool => trim((string) $image) !== '' && $image !== $coverPath
));

$facts = array_filter([
    'Tipo' => $kind,
    'Estado' => $statusLabel,
    'Fecha' => $when,
    'Horario' => trim((string) ($item['schedule'] ?? '')),
    'Lugar' => trim((string) ($item['place'] ?? '')),
    'Costo' => trim((string) ($item['cost'] ?? '')),
], static fn(string $value): bool => trim($value) !== '');

$ctaLabel = trim((string) ($item['ctaLabel'] ?? ''));
$ctaUrl = tw_url(trim((string) ($item['ctaUrl'] ?? '')));
$whatsapp = tw_whatsapp_url('Hola La Casa de los Gatos, quiero registrarme o pedir información sobre: ' . $title);
$open = $status !== 'finalizada';

$overrides = [
    'title' => $title . ' | La Casa de los Gatos',
    'description' => (string) $item['summary'],
    'canonical' => '/tnr/' . $item['id'] . '/',
    'ogTitle' => $title,
    'ogDescription' => (string) $item['summary'],
    'ogImage' => $coverPath,
    'ogType' => 'article',
    'jsonLd' => '',
];
?>
<?php tw_page_start('tnr', $overrides); ?>
  <main id="contenido">
    <section class="page-hero page-hero--plain"><div class="container">
      <p class="breadcrumbs"><a href="<?php echo tw_esc(tw_url('/')); ?>">Inicio</a> / <a href="<?php echo tw_esc(tw_url('/tnr/')); ?>">TNR</a> / <?php echo tw_esc($title); ?></p>
      <?php if ($statusLabel !== ''): ?>
        <span class="badge <?php echo tw_esc(tw_badge_class($status)); ?>"><?php echo tw_esc($statusLabel); ?></span>
      <?php endif; ?>
      <h1><?php echo tw_esc($title); ?></h1>
      <p><?php echo tw_esc((string) $item['summary']); ?></p>
    </div></section>

    <section class="section"><div class="container">
      <div class="detail-layout">
        <div>
          <div class="detail-media reveal">
            <img src="<?php echo tw_esc($cover); ?>" alt="<?php echo tw_esc($title); ?>" width="1024" height="1024" fetchpriority="high">
          </div>

          <?php if (trim((string) $item['body']) !== ''): ?>
            <div class="detail-body reveal" style="margin-top:32px">
              <?php echo $item['body']; ?>
            </div>
          <?php endif; ?>

          <?php if ($gallery !== []): ?>
            <div class="photo-grid reveal" style="margin-top:32px">
              <?php foreach ($gallery as $image): ?>
                <img src="<?php echo tw_esc(tw_url((string) $image)); ?>" alt="<?php echo tw_esc($title); ?>" loading="lazy" width="600" height="600">
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <aside class="detail-panel reveal">
          <h2 style="font-size:1.4rem">Datos</h2>
          <dl class="detail-facts">
            <?php foreach ($facts as $label => $value): ?>
              <div><dt><?php echo tw_esc((string) $label); ?></dt><dd><?php echo tw_esc($value); ?></dd></div>
            <?php endforeach; ?>
          </dl>

          <?php if ($open): ?>
            <div class="button-row">
              <?php if ($ctaUrl !== '' && $ctaLabel !== ''): ?>
                <a class="btn" href="<?php echo tw_esc($ctaUrl); ?>"><?php echo tw_esc($ctaLabel); ?></a>
              <?php endif; ?>
              <?php if ($whatsapp !== ''): ?>
                <a class="btn btn--outline" href="<?php echo tw_esc($whatsapp); ?>" target="_blank" rel="noopener" data-track-button="jornada-whatsapp">Registrarme por WhatsApp</a>
              <?php endif; ?>
            </div>
            <p style="font-size:.86rem">Antes de la cirugía revisa <a href="<?php echo tw_esc(tw_url('/tnr/')); ?>#esterilizacion" style="color:var(--blue);font-weight:600">cómo preparar a tu gato</a>.</p>
          <?php else: ?>
            <p>Esta jornada ya concluyó. Consulta las convocatorias abiertas.</p>
            <a class="btn" href="<?php echo tw_esc(tw_url('/tnr/')); ?>#jornadas">Ver jornadas disponibles</a>
          <?php endif; ?>
        </aside>
      </div>
    </div></section>
  </main>
<?php tw_page_end(); ?>
