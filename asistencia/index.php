<?php $twRoot = __DIR__; while (!is_file($twRoot . '/lib/page-boot.php') && dirname($twRoot) !== $twRoot) { $twRoot = dirname($twRoot); } require_once $twRoot . '/lib/page-boot.php'; ?>
<?php
$content = tw_content('asistencia');
$report = $content['report'];
$where = $content['where'];
$cecobam = $content['cecobam'];
$responder = $content['responder'];
$civic = $content['civic'];
$evidence = $content['evidence'];
$summary = $content['summary'];
$guides = ContentCollection::published('guides');
$whatsapp = tw_whatsapp_url('Hola La Casa de los Gatos, necesito orientación sobre un caso.');
?>
<?php tw_page_start('asistencia'); ?>
  <main id="contenido">
    <?php tw_page_hero('Asistencia y orientación', $content['hero']); ?>

    <nav class="container" aria-label="En esta página">
      <div class="filter-bar">
        <a href="#orientacion">Qué hacer en cada caso</a>
        <a href="#denuncias">Cómo denunciar</a>
        <a href="#a-donde-acudir">A dónde acudir</a>
        <a href="#que-reunir">Qué reunir</a>
      </div>
    </nav>

    <section class="section" id="orientacion"><div class="container">
      <?php tw_heading($content['cases']); ?>
      <?php if ($guides === []): ?>
        <div class="empty-state reveal">
          <h2>Estamos preparando esta sección</h2>
          <p>Mientras tanto, escríbenos y te orientamos directamente.</p>
        </div>
      <?php else: ?>
        <div class="faq-list reveal">
          <?php foreach ($guides as $guide): ?>
          <details class="faq-item guide-item" id="<?php echo tw_esc((string) $guide['id']); ?>">
            <summary>
              <?php if (!empty($guide['urgent'])): ?><span class="badge badge--process">Urgente</span> <?php endif; ?>
              <?php echo tw_esc((string) $guide['title']); ?>
            </summary>
            <?php if (trim((string) $guide['summary']) !== ''): ?>
              <p><strong><?php echo tw_esc((string) $guide['summary']); ?></strong></p>
            <?php endif; ?>
            <?php if (trim((string) $guide['body']) !== ''): ?>
              <div class="detail-body"><?php echo $guide['body']; ?></div>
            <?php endif; ?>
            <?php if (trim((string) $guide['ctaLabel']) !== '' && trim((string) $guide['ctaUrl']) !== ''): ?>
              <p><a class="content-card__link" href="<?php echo tw_esc(tw_url((string) $guide['ctaUrl'])); ?>"><?php echo tw_esc((string) $guide['ctaLabel']); ?></a></p>
            <?php endif; ?>
          </details>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div></section>

    <section class="section section--dark" id="denuncias"><div class="container">
      <?php tw_heading($report, true); ?>
      <div class="report-grid reveal">
        <div>
          <h3><?php echo tw_esc($report['whatTitle']); ?></h3>
          <?php tw_paragraphs($report['whatText']); ?>
        </div>
        <div>
          <h3><?php echo tw_esc($report['howTitle']); ?></h3>
          <?php tw_paragraphs($report['howIntro']); ?>
          <?php if ($report['howList'] !== []): ?>
            <ul class="check-list check-list--dark">
              <?php foreach ($report['howList'] as $line): ?><li><?php echo tw_esc($line); ?></li><?php endforeach; ?>
            </ul>
          <?php endif; ?>
          <?php if ($report['howNote'] !== ''): ?>
            <p class="report-grid__note"><?php echo tw_esc($report['howNote']); ?></p>
          <?php endif; ?>
        </div>
      </div>
    </div></section>

    <section class="section" id="a-donde-acudir"><div class="container">
      <?php tw_heading($where); ?>
      <div class="values-grid reveal">
        <?php foreach ($where['places'] as $place): ?>
        <article class="value-card">
          <h3><?php echo tw_esc($place['title']); ?></h3>
          <p><?php echo tw_esc($place['text']); ?></p>
          <?php if ($place['when'] !== ''): ?>
            <p class="value-card__when"><strong>Cuándo interviene:</strong> <?php echo tw_esc($place['when']); ?></p>
          <?php endif; ?>
        </article>
        <?php endforeach; ?>
      </div>
      <?php if ($where['note'] !== ''): ?>
        <p class="notice reveal"><?php echo tw_esc($where['note']); ?></p>
      <?php endif; ?>

      <div class="care-grid reveal">
        <div class="care-card" id="cecobam">
          <h3><?php echo tw_esc($cecobam['title']); ?></h3>
          <?php tw_paragraphs($cecobam['text']); ?>
          <?php if ($cecobam['list'] !== []): ?>
            <p><strong><?php echo tw_esc($cecobam['listTitle']); ?></strong></p>
            <ul class="check-list">
              <?php foreach ($cecobam['list'] as $line): ?><li><?php echo tw_esc($line); ?></li><?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
        <div class="care-card" id="seguridad-ciudadana">
          <h3><?php echo tw_esc($responder['title']); ?></h3>
          <?php tw_paragraphs($responder['text']); ?>
          <?php if ($responder['routes'] !== []): ?>
            <p><strong><?php echo tw_esc($responder['listTitle']); ?></strong></p>
            <ul class="check-list">
              <?php foreach ($responder['routes'] as $route): ?>
                <li><span><strong><?php echo tw_esc($route['title']); ?></strong><?php echo $route['text'] !== '' ? ' — ' . tw_esc($route['text']) : ''; ?></span></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($civic['text'] !== ''): ?>
      <div class="care-card care-card--wide reveal" id="justicia-civica">
        <h3><?php echo tw_esc($civic['title']); ?></h3>
        <?php tw_paragraphs($civic['text']); ?>
      </div>
      <?php endif; ?>
    </div></section>

    <section class="section section--tight" id="que-reunir"><div class="container">
      <?php tw_heading($evidence); ?>
      <?php if ($evidence['list'] !== []): ?>
        <ul class="check-list check-list--columns reveal">
          <?php foreach ($evidence['list'] as $line): ?><li><?php echo tw_esc($line); ?></li><?php endforeach; ?>
        </ul>
      <?php endif; ?>
      <?php if ($evidence['note'] !== ''): ?>
        <p class="notice reveal"><?php echo tw_esc($evidence['note']); ?></p>
      <?php endif; ?>

      <?php if ($summary['text'] !== ''): ?>
      <div class="callout reveal">
        <?php if ($summary['eyebrow'] !== ''): ?><span class="eyebrow"><?php echo tw_esc($summary['eyebrow']); ?></span><?php endif; ?>
        <p class="callout__lead"><?php echo tw_esc($summary['text']); ?></p>
        <?php if ($summary['source'] !== ''): ?><p class="callout__source"><?php echo tw_esc($summary['source']); ?></p><?php endif; ?>
      </div>
      <?php endif; ?>
    </div></section>

    <section class="section section--tight"><div class="container reveal"><div class="cta-band">
      <div><h2><?php echo tw_esc($content['cta']['title']); ?></h2><p><?php echo tw_esc($content['cta']['text']); ?></p></div>
      <div class="button-row">
        <?php if ($whatsapp !== ''): ?>
          <a class="btn" href="<?php echo tw_esc($whatsapp); ?>" target="_blank" rel="noopener" data-track-button="asistencia-whatsapp">Escribir por WhatsApp</a>
        <?php endif; ?>
        <a class="btn btn--outline" href="<?php echo tw_esc(tw_url('/directorio/')); ?>">Ver clínicas</a>
      </div>
    </div></div></section>
  </main>
<?php tw_page_end(); ?>
