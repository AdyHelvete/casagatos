<?php $twRoot = __DIR__; while (!is_file($twRoot . '/lib/page-boot.php') && dirname($twRoot) !== $twRoot) { $twRoot = dirname($twRoot); } require_once $twRoot . '/lib/page-boot.php'; ?>
<?php
$content = tw_content('directorio');
$clinics = ContentCollection::published('clinics');
$zones = [];
foreach (array_keys(ContentCollection::optionLabels('clinics', 'zone')) as $zone) {
    $zones[$zone] = array_values(array_filter(
        $clinics,
        static fn(array $item): bool => (string) ($item['zone'] ?? '') === $zone
    ));
}
?>
<?php tw_page_start('directorio'); ?>
  <main id="contenido">
    <?php tw_page_hero('Directorio', $content['hero']); ?>

    <nav class="container" aria-label="Zonas del directorio">
      <div class="filter-bar">
        <a href="#tizayuca">Tizayuca (<?php echo count($zones['tizayuca']); ?>)</a>
        <a href="#zumpango">Zumpango (<?php echo count($zones['zumpango']); ?>)</a>
      </div>
    </nav>

    <?php foreach ($zones as $zone => $items): ?>
    <section class="section<?php echo $zone === 'tizayuca' ? '' : ' section--tight'; ?>" id="<?php echo tw_esc($zone); ?>"><div class="container">
      <?php tw_heading($content[$zone]); ?>
      <?php if ($items === []): ?>
        <div class="empty-state reveal">
          <h2>Aún no hay clínicas registradas en esta zona</h2>
          <p>Si conoces una, escríbenos y la agregamos al directorio.</p>
        </div>
      <?php else: ?>
        <div class="clinic-grid reveal">
          <?php foreach ($items as $item) { tw_clinic_card($item); } ?>
        </div>
      <?php endif; ?>
    </div></section>
    <?php endforeach; ?>

    <section class="section section--tight"><div class="container reveal">
      <?php if ($content['note']['disclaimer'] !== ''): ?>
        <p class="notice"><?php echo tw_esc($content['note']['disclaimer']); ?></p>
      <?php endif; ?>
      <div class="cta-band">
        <div><h2><?php echo tw_esc($content['note']['title']); ?></h2><p><?php echo tw_esc($content['note']['text']); ?></p></div>
        <a class="btn" href="<?php echo tw_esc(tw_url('/contacto/')); ?>">Sugerir una clínica</a>
      </div>
    </div></section>
  </main>
<?php tw_page_end(); ?>
