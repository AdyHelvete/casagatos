<?php $twRoot = __DIR__; while (!is_file($twRoot . '/lib/page-boot.php') && dirname($twRoot) !== $twRoot) { $twRoot = dirname($twRoot); } require_once $twRoot . '/lib/page-boot.php'; ?>
<?php
$filters = [
    'disponible' => 'En adopción',
    'en-proceso' => 'En proceso',
    'adoptado' => 'Ya adoptados',
];

$active = (string) ($_GET['estado'] ?? '');
if (!isset($filters[$active])) {
    $active = '';
}

$all = ContentCollection::published('adoptions');
$items = $active === ''
    ? $all
    : array_values(array_filter($all, static fn(array $item): bool => ($item['status'] ?? '') === $active));

$counts = [];
foreach ($filters as $key => $label) {
    $counts[$key] = count(array_filter($all, static fn(array $item): bool => ($item['status'] ?? '') === $key));
}
?>
<?php tw_page_start('adopciones'); ?>
  <main id="contenido">
    <section class="page-hero page-hero--plain"><div class="container">
      <p class="breadcrumbs"><a href="<?php echo tw_esc(tw_url('/')); ?>">Inicio</a> / Adopciones</p>
      <span class="eyebrow eyebrow--lime">Adopciones</span>
      <h1>Buscan una familia <span class="accent">como la tuya.</span></h1>
      <p>Cada ficha incluye edad, carácter y estado de salud. Todos salen desparasitados y con revisión veterinaria; los adultos, además, esterilizados.</p>
    </div></section>

    <section class="section"><div class="container">
      <nav class="filter-bar reveal" aria-label="Filtrar adopciones">
        <a href="<?php echo tw_esc(tw_url('/adopciones/')); ?>"<?php echo $active === '' ? ' aria-current="true"' : ''; ?>>Todos (<?php echo count($all); ?>)</a>
        <?php foreach ($filters as $key => $label): ?>
          <a href="<?php echo tw_esc(tw_url('/adopciones/')); ?>?estado=<?php echo tw_esc($key); ?>"<?php echo $active === $key ? ' aria-current="true"' : ''; ?>><?php echo tw_esc($label); ?> (<?php echo (int) $counts[$key]; ?>)</a>
        <?php endforeach; ?>
      </nav>

      <?php if ($items === []): ?>
        <div class="empty-state reveal">
          <h2>Nada por aquí ahora mismo</h2>
          <p>No hay fichas en esta categoría. Revisa las otras o síguenos en redes: publicamos cada rescate nuevo.</p>
          <p><a class="btn btn--outline" href="<?php echo tw_esc(tw_url('/adopciones/')); ?>">Ver todas las fichas</a></p>
        </div>
      <?php else: ?>
        <div class="content-grid reveal">
          <?php foreach ($items as $item) { tw_adoption_card($item); } ?>
        </div>
      <?php endif; ?>
    </div></section>

    <section class="section section--tight"><div class="container reveal"><div class="cta-band">
      <div><h2>¿Ya tienes uno en mente?</h2><p>Antes de escribir, revisa el proceso: te tomará dos minutos y evita malentendidos.</p></div>
      <a class="btn" href="<?php echo tw_esc(tw_url('/como-adoptar/')); ?>">Ver cómo adoptar</a>
    </div></div></section>
  </main>
<?php tw_page_end(); ?>
