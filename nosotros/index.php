<?php $twRoot = __DIR__; while (!is_file($twRoot . '/lib/page-boot.php') && dirname($twRoot) !== $twRoot) { $twRoot = dirname($twRoot); } require_once $twRoot . '/lib/page-boot.php'; ?>
<?php
$facebook = (string) (tw_config()['social']['facebook'] ?? '');
?>
<?php tw_page_start('nosotros'); ?>
  <main id="contenido">
    <section class="page-hero page-hero--plain"><div class="container">
      <p class="breadcrumbs"><a href="<?php echo tw_esc(tw_url('/')); ?>">Inicio</a> / Nosotros</p>
      <span class="eyebrow eyebrow--lime">Quiénes somos</span>
      <h1>Vecinos que decidieron <span class="accent">no voltear a otro lado.</span></h1>
      <p>La Casa de los Gatos nació en Tizayuca de un grupo de personas que empezó rescatando gatos de su propia colonia y hoy coordina hogares temporales, jornadas de esterilización y adopciones en toda la zona.</p>
    </div></section>

    <section class="section"><div class="container">
      <div class="split reveal">
        <div class="detail-body">
          <span class="eyebrow">Nuestra misión</span>
          <h2>Reducir el abandono con trabajo constante, no con campañas de temporada.</h2>
          <p>No tenemos un albergue: cada gato rescatado vive en casa de un voluntario mientras se recupera y encuentra familia. Eso limita cuántos casos podemos atender a la vez, pero nos permite conocer a cada gato y entregarlo con información real.</p>
          <p>Nuestro trabajo se sostiene con recursos propios, donativos de la comunidad y alianzas con clínicas veterinarias y otras asociaciones de la región.</p>
          <p><strong>#AdoptaNoCompres</strong> no es un lema decorativo: mientras haya gatos en la calle esperando, comprar uno de criadero es sostener un problema que ya existe.</p>
        </div>
        <div class="media-card media-card--illustration">
          <img src="<?php echo tw_esc(tw_url('/assets/images/evento-tizayuca-01.jpg')); ?>" alt="Voluntarias de La Casa de los Gatos entregando una propuesta de bienestar animal en Tizayuca" loading="lazy" width="771" height="1024">
        </div>
      </div>
    </div></section>

    <section class="section section--tight"><div class="container">
      <div class="section-heading reveal">
        <div><span class="eyebrow">Qué hacemos</span><h2>Cuatro frentes, un mismo objetivo.</h2></div>
        <p>Todo lo que hacemos apunta a que menos gatos terminen en la calle y a que los que ya están ahí tengan una salida.</p>
      </div>
      <div class="values-grid values-grid--five reveal">
        <article class="value-card">
          <h3>Rescate</h3>
          <p>Atendemos reportes de gatos heridos, camadas abandonadas y casos de maltrato dentro de nuestra zona de cobertura.</p>
        </article>
        <article class="value-card">
          <h3>Asistencia veterinaria</h3>
          <p>Desparasitación, vacunas, tratamientos y esterilización antes de entregar a cada gato en adopción.</p>
        </article>
        <article class="value-card">
          <h3>Hogares temporales</h3>
          <p>Coordinamos voluntarios que reciben a un gato unas semanas. Nosotros cubrimos alimento y gastos médicos.</p>
        </article>
        <article class="value-card">
          <h3>Incidencia y difusión</h3>
          <p>Participamos en propuestas de reglamento municipal y usamos redes para dar visibilidad a cada caso.</p>
        </article>
      </div>
    </div></section>

    <section class="section section--dark"><div class="container">
      <div class="section-heading reveal">
        <div><span class="eyebrow eyebrow--lime">Dónde operamos</span><h2>Tizayuca y municipios vecinos.</h2></div>
        <p>Trabajamos principalmente en Tizayuca, Hidalgo, con presencia en Tecámac, Zumpango y comunidades cercanas.</p>
      </div>
      <ul class="check-list check-list--dark reveal">
        <li>Rescates y entregas dentro de la zona de cobertura</li>
        <li>Jornadas de esterilización en coordinación con clínicas aliadas</li>
        <li>Colectas y módulos informativos en eventos públicos</li>
        <li>Seguimiento posterior a cada adopción</li>
      </ul>
    </div></section>

    <section class="section section--tight"><div class="container reveal"><div class="cta-band">
      <div><h2>¿Te interesa sumarte?</h2><p>Buscamos hogares temporales, apoyo para veterinario y manos para las jornadas.</p></div>
      <?php if ($facebook !== ''): ?>
        <a class="btn" href="<?php echo tw_esc($facebook); ?>" target="_blank" rel="noopener">Síguenos en Facebook</a>
      <?php else: ?>
        <a class="btn" href="<?php echo tw_esc(tw_url('/contacto/')); ?>">Escríbenos</a>
      <?php endif; ?>
    </div></div></section>
  </main>
<?php tw_page_end(); ?>
