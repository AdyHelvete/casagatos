<?php $twRoot = __DIR__; while (!is_file($twRoot . '/lib/page-boot.php') && dirname($twRoot) !== $twRoot) { $twRoot = dirname($twRoot); } require_once $twRoot . '/lib/page-boot.php'; ?>
<?php
$content = tw_content('tnr');
$process = $content['process'];
$whatsapp = tw_whatsapp_url('Hola La Casa de los Gatos, quiero orientación sobre TNR y esterilización.');

$all = ContentCollection::published('campaigns');
$open = array_values(array_filter(
    $all,
    static fn(array $item): bool => in_array((string) ($item['status'] ?? ''), ['activa', 'proxima'], true)
));
$past = array_values(array_filter(
    $all,
    static fn(array $item): bool => (string) ($item['status'] ?? '') === 'finalizada'
));
?>
<?php tw_page_start('tnr'); ?>
  <main id="contenido">
    <?php tw_page_hero('TNR', $content['hero']); ?>

    <section class="section" id="que-es"><div class="container">
      <?php tw_heading($content['what']); ?>
      <div class="process-grid reveal">
        <?php foreach ($content['what']['steps'] as $step): ?>
        <article class="process-step">
          <h3><?php echo tw_esc($step['title']); ?></h3>
          <p><?php echo tw_esc($step['text']); ?></p>
        </article>
        <?php endforeach; ?>
      </div>
    </div></section>

    <section class="section section--dark" id="control-etico"><div class="container">
      <?php tw_heading($content['why'], true); ?>
      <div class="process-grid process-grid--auto reveal">
        <?php foreach ($content['why']['points'] as $point): ?>
        <article class="process-step">
          <h3><?php echo tw_esc($point['title']); ?></h3>
          <p><?php echo tw_esc($point['text']); ?></p>
        </article>
        <?php endforeach; ?>
      </div>
    </div></section>

    <section class="section" id="esterilizacion"><div class="container">
      <?php tw_heading($process); ?>
      <div class="care-grid reveal">
        <div class="care-card">
          <h3><?php echo tw_esc($process['beforeTitle']); ?></h3>
          <ul class="check-list">
            <?php foreach ($process['before'] as $line): ?><li><?php echo tw_esc($line); ?></li><?php endforeach; ?>
          </ul>
        </div>
        <div class="care-card">
          <h3><?php echo tw_esc($process['afterTitle']); ?></h3>
          <ul class="check-list">
            <?php foreach ($process['after'] as $line): ?><li><?php echo tw_esc($line); ?></li><?php endforeach; ?>
          </ul>
        </div>
      </div>
      <div class="button-row" style="margin-top:28px">
        <a class="btn btn--outline" href="<?php echo tw_esc(tw_url('/directorio/')); ?>">Ver clínicas del directorio</a>
      </div>
    </div></section>

    <section class="section section--tight" id="jornadas"><div class="container">
      <?php tw_heading($content['jornadas']); ?>

      <?php if ($open === []): ?>
        <div class="empty-state reveal">
          <h2><?php echo tw_esc($content['jornadas']['emptyTitle']); ?></h2>
          <p><?php echo tw_esc($content['jornadas']['emptyText']); ?></p>
          <p><a class="btn btn--outline" href="<?php echo tw_esc(tw_url('/contacto/')); ?>">Avísame de la próxima</a></p>
        </div>
      <?php else: ?>
        <div class="content-grid content-grid--wide reveal">
          <?php foreach ($open as $item) { tw_campaign_card($item); } ?>
        </div>
      <?php endif; ?>

      <?php if ($past !== []): ?>
        <h3 class="subheading reveal"><?php echo tw_esc($content['jornadas']['pastTitle']); ?></h3>
        <div class="content-grid content-grid--wide reveal">
          <?php foreach ($past as $item) { tw_campaign_card($item); } ?>
        </div>
      <?php endif; ?>
    </div></section>

    <?php if ($content['faq']['items'] !== []): ?>
    <section class="section" id="preguntas"><div class="container">
      <?php tw_heading($content['faq']); ?>
      <div class="faq-list reveal">
        <?php foreach ($content['faq']['items'] as $faq): ?>
        <details class="faq-item">
          <summary><?php echo tw_esc($faq['title']); ?></summary>
          <p><?php echo tw_esc($faq['text']); ?></p>
        </details>
        <?php endforeach; ?>
      </div>
    </div></section>
    <?php endif; ?>

    <section class="section section--tight"><div class="container reveal"><div class="cta-band">
      <div><h2><?php echo tw_esc($content['cta']['title']); ?></h2><p><?php echo tw_esc($content['cta']['text']); ?></p></div>
      <?php if ($whatsapp !== ''): ?>
        <a class="btn" href="<?php echo tw_esc($whatsapp); ?>" target="_blank" rel="noopener" data-track-button="tnr-whatsapp">Escribir por WhatsApp</a>
      <?php else: ?>
        <a class="btn" href="<?php echo tw_esc(tw_url('/contacto/')); ?>">Escríbenos</a>
      <?php endif; ?>
    </div></div></section>
  </main>
<?php tw_page_end(); ?>
