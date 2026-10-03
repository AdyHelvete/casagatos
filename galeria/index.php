<?php $twRoot = __DIR__; while (!is_file($twRoot . '/lib/page-boot.php') && dirname($twRoot) !== $twRoot) { $twRoot = dirname($twRoot); } require_once $twRoot . '/lib/page-boot.php'; ?>
<?php $albums = ContentCollection::published('albums'); ?>
<?php tw_page_start('galeria'); ?>
  <main id="contenido">
    <section class="page-hero page-hero--plain"><div class="container">
      <p class="breadcrumbs"><a href="<?php echo tw_esc(tw_url('/')); ?>">Inicio</a> / Galería</p>
      <span class="eyebrow eyebrow--lime">Galería</span>
      <h1>Lo que hacemos, <span class="accent">en fotos.</span></h1>
      <p>Rescates, hogares temporales, jornadas y eventos. Sin filtros ni producción: así es el trabajo real.</p>
    </div></section>

    <section class="section"><div class="container">
      <?php if ($albums === []): ?>
        <div class="empty-state reveal">
          <h2>Todavía no hay álbumes publicados</h2>
          <p>Estamos organizando el material. Mientras tanto, encuentras todo en nuestras redes.</p>
        </div>
      <?php else: ?>
        <div class="content-grid content-grid--wide reveal">
          <?php foreach ($albums as $album) { tw_album_card($album); } ?>
        </div>
      <?php endif; ?>
    </div></section>

    <section class="section section--tight"><div class="container reveal"><div class="cta-band">
      <div><h2>¿Quieres aparecer aquí?</h2><p>Adopta, sé hogar temporal o súmate como voluntario en la próxima jornada.</p></div>
      <a class="btn" href="<?php echo tw_esc(tw_url('/contacto/')); ?>">Quiero participar</a>
    </div></div></section>
  </main>
<?php tw_page_end(); ?>
