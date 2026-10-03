<?php $twRoot = __DIR__; while (!is_file($twRoot . '/lib/page-boot.php') && dirname($twRoot) !== $twRoot) { $twRoot = dirname($twRoot); } require_once $twRoot . '/lib/page-boot.php'; ?>
<?php
$all = ContentCollection::published('campaigns');
$current = array_values(array_filter(
    $all,
    static fn(array $item): bool => in_array((string) ($item['status'] ?? ''), ['activa', 'proxima'], true)
));
$past = array_values(array_filter(
    $all,
    static fn(array $item): bool => (string) ($item['status'] ?? '') === 'finalizada'
));
?>
<?php tw_page_start('campanas'); ?>
  <main id="contenido">
    <section class="page-hero page-hero--plain"><div class="container">
      <p class="breadcrumbs"><a href="<?php echo tw_esc(tw_url('/')); ?>">Inicio</a> / Campañas y eventos</p>
      <span class="eyebrow eyebrow--lime">Campañas y eventos</span>
      <h1>Aquí puedes participar <span class="accent">aunque no adoptes.</span></h1>
      <p>Jornadas de esterilización, colectas de alimento, módulos informativos y trabajo de incidencia en el municipio.</p>
    </div></section>

    <section class="section"><div class="container">
      <div class="section-heading reveal">
        <div><span class="eyebrow">Ahora mismo</span><h2>Activas y próximas</h2></div>
        <p>Los cupos de las jornadas son limitados y se asignan por registro previo.</p>
      </div>

      <?php if ($current === []): ?>
        <div class="empty-state reveal">
          <h2>No hay campañas abiertas en este momento</h2>
          <p>Publicamos cada convocatoria con anticipación. Síguenos en redes para enterarte primero.</p>
          <p><a class="btn btn--outline" href="<?php echo tw_esc(tw_url('/contacto/')); ?>">Avísame de la próxima</a></p>
        </div>
      <?php else: ?>
        <div class="content-grid content-grid--wide reveal">
          <?php foreach ($current as $item) { tw_campaign_card($item); } ?>
        </div>
      <?php endif; ?>
    </div></section>

    <?php if ($past !== []): ?>
    <section class="section section--tight"><div class="container">
      <div class="section-heading reveal">
        <div><span class="eyebrow">Historial</span><h2>Lo que ya hicimos</h2></div>
        <p>El registro de campañas anteriores. Sirve para rendir cuentas de en qué se usó el apoyo recibido.</p>
      </div>
      <div class="content-grid content-grid--wide reveal">
        <?php foreach ($past as $item) { tw_campaign_card($item); } ?>
      </div>
    </div></section>
    <?php endif; ?>

    <section class="section section--tight"><div class="container reveal"><div class="cta-band">
      <div><h2>¿Quieres apoyar una campaña?</h2><p>Con alimento, arena, transporte o difusión. Todo cuenta.</p></div>
      <a class="btn" href="<?php echo tw_esc(tw_url('/contacto/')); ?>">Quiero apoyar</a>
    </div></div></section>
  </main>
<?php tw_page_end(); ?>
