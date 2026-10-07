<?php $twRoot = __DIR__; while (!is_file($twRoot . '/lib/page-boot.php') && dirname($twRoot) !== $twRoot) { $twRoot = dirname($twRoot); } require_once $twRoot . '/lib/page-boot.php'; ?>
<?php
$content = tw_content('directorio');
$clinics = ContentCollection::published('clinics');
$categories = ContentCollection::optionLabels('clinics', 'category');
$zones = [];
foreach (array_keys(ContentCollection::optionLabels('clinics', 'zone')) as $zone) {
    $zones[$zone] = array_values(array_filter(
        $clinics,
        static fn(array $item): bool => (string) ($item['zone'] ?? '') === $zone
    ));
}
// Dentro de cada zona, los lugares se agrupan por tipo (clínica, tienda, estética).
$byCategory = static function (array $items) use ($categories): array {
    $groups = [];
    foreach (array_keys($categories) as $category) {
        $group = array_values(array_filter(
            $items,
            static fn(array $item): bool => (string) ($item['category'] ?? '') === $category
        ));
        if ($group !== []) {
            $groups[$category] = $group;
        }
    }

    return $groups;
};
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
        <?php $groups = $byCategory($items); ?>
        <?php foreach ($groups as $category => $group): ?>
          <?php if (count($groups) > 1): ?>
            <h3 class="subheading reveal" id="<?php echo tw_esc($zone . '-' . $category); ?>"><?php echo tw_esc($categories[$category]); ?> (<?php echo count($group); ?>)</h3>
          <?php endif; ?>
          <div class="clinic-grid reveal">
            <?php foreach ($group as $item) { tw_clinic_card($item); } ?>
          </div>
        <?php endforeach; ?>
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
