<?php $twRoot = __DIR__; while (!is_file($twRoot . '/lib/page-boot.php') && dirname($twRoot) !== $twRoot) { $twRoot = dirname($twRoot); } require_once $twRoot . '/lib/page-boot.php'; ?>
<?php
$config = tw_config();
$facebook = (string) ($config['social']['facebook'] ?? '');
$instagram = (string) ($config['social']['instagram'] ?? '');
$tiktok = (string) ($config['social']['tiktok'] ?? '');
$whatsapp = tw_whatsapp_url();

$campaigns = ContentCollection::featured('campaigns', 3);
$adoptions = array_values(array_filter(
    ContentCollection::published('adoptions'),
    static fn(array $item): bool => ($item['status'] ?? '') !== 'adoptado'
));
$adoptions = array_slice($adoptions, 0, 4);
?>
<?php tw_page_start('home'); ?>
  <main id="contenido">
    <section class="hero">
      <div class="container hero-stage">
        <div class="hero-content">
          <span class="eyebrow">Rescate y adopción · Tizayuca y zona</span>
          <h1>Adopta. No compres. <span>La Casa de los Gatos</span></h1>
          <p class="lead">Rescatamos, damos asistencia y buscamos hogar responsable para gatos en Tizayuca, Tecámac, Zumpango y municipios cercanos.</p>
          <ul class="hero-bullets">
            <li>Jornadas de esterilización y vacunación con costo accesible o gratuitas.</li>
            <li>Hogares temporales mientras cada gato encuentra familia.</li>
            <li>Acompañamiento antes y después de la adopción.</li>
          </ul>
          <div class="button-row">
            <?php if ($facebook !== ''): ?>
              <a class="btn btn--lime" href="<?php echo tw_esc($facebook); ?>" target="_blank" rel="noopener" data-track-button="hero-facebook">Síguenos en Facebook</a>
            <?php endif; ?>
            <a class="btn btn--ghost" href="<?php echo tw_esc(tw_url('/adopciones/')); ?>" data-track-button="hero-adopciones">Ver gatos en adopción</a>
          </div>
        </div>
        <figure class="hero-frame">
          <img class="hero-bg" src="<?php echo tw_esc(tw_url('/assets/images/hero-adopcion.jpg')); ?>" alt="Gato negro del logo de La Casa de los Gatos en una caja, esperando adopción en un hogar" width="1024" height="1024" fetchpriority="high">
          <figcaption>#Adopta<span>NO</span>Compres</figcaption>
        </figure>
      </div>
    </section>

    <section class="trust-strip" aria-label="Nuestra causa">
      <div class="marquee" aria-hidden="true">
        <span>#AdoptaNoCompres</span><span>Esteriliza</span><span>Rescata</span><span>Comparte</span><span>Dona</span>
        <span>#AdoptaNoCompres</span><span>Esteriliza</span><span>Rescata</span><span>Comparte</span><span>Dona</span>
      </div>
    </section>

    <section class="section">
      <div class="container">
        <div class="section-heading reveal">
          <div>
            <span class="eyebrow">Por qué existimos</span>
            <h2>Cada gato en la calle <span class="accent">tuvo una oportunidad perdida.</span></h2>
          </div>
          <p>No somos un refugio con instalaciones: somos personas de la zona que rescatan, curan y buscan hogar con recursos propios y de la comunidad.</p>
        </div>
        <div class="values-grid reveal">
          <article class="value-card">
            <h3>Rescate y asistencia</h3>
            <p>Atendemos casos de abandono, camadas recién nacidas y gatos heridos. Cubrimos veterinario, alimento y cuidados hasta que se recuperan.</p>
          </article>
          <article class="value-card">
            <h3>Esterilización</h3>
            <p>Es la única forma real de frenar el abandono. Organizamos jornadas y conectamos a las familias con clínicas aliadas.</p>
          </article>
          <article class="value-card">
            <h3>Adopción responsable</h3>
            <p>Entrevistamos, damos seguimiento y explicamos el compromiso. Un gato vive 15 años o más: no es un regalo de temporada.</p>
          </article>
        </div>
      </div>
    </section>

    <?php if ($campaigns !== []): ?>
    <section class="section section--tight">
      <div class="container">
        <div class="section-heading reveal">
          <div>
            <span class="eyebrow">Campañas y eventos</span>
            <h2>Lo que está pasando ahora.</h2>
          </div>
          <p>Jornadas de esterilización, colectas y eventos donde puedes participar aunque no adoptes.</p>
        </div>
        <div class="content-grid content-grid--wide reveal">
          <?php foreach ($campaigns as $item) { tw_campaign_card($item); } ?>
        </div>
        <div class="button-row" style="margin-top:28px">
          <a class="btn btn--outline" href="<?php echo tw_esc(tw_url('/campanas/')); ?>">Ver todas las campañas</a>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <?php if ($adoptions !== []): ?>
    <section class="section section--tight">
      <div class="container">
        <div class="section-heading reveal">
          <div>
            <span class="eyebrow">Buscan hogar</span>
            <h2>Ellos están esperando.</h2>
          </div>
          <p>Cada ficha incluye edad, carácter y estado de salud. Si alguno te late, escríbenos y platicamos.</p>
        </div>
        <div class="content-grid reveal">
          <?php foreach ($adoptions as $item) { tw_adoption_card($item); } ?>
        </div>
        <div class="button-row" style="margin-top:28px">
          <a class="btn" href="<?php echo tw_esc(tw_url('/adopciones/')); ?>">Ver todos en adopción</a>
          <a class="btn btn--outline" href="<?php echo tw_esc(tw_url('/como-adoptar/')); ?>">Cómo es el proceso</a>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <section class="section section--tight">
      <div class="container reveal">
        <div class="social-cta">
          <div>
            <span class="eyebrow eyebrow--lime">Comunidad</span>
            <h2>El día a día está en redes.</h2>
            <p>Publicamos rescates, avances de los gatos en tratamiento y avisos de campañas. Seguirnos y compartir es la ayuda que más multiplica.</p>
          </div>
          <div class="social-cta__links">
            <?php if ($facebook !== ''): ?>
            <a class="social-cta__link" href="<?php echo tw_esc($facebook); ?>" target="_blank" rel="noopener" data-track-button="social-facebook">
              <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.68.24 2.68.24v2.96h-1.51c-1.49 0-1.96.93-1.96 1.89v2.26h3.33l-.53 3.49h-2.8V24C19.61 23.1 24 18.1 24 12.07z"/></svg>
              <span><strong>Facebook</strong><span>Nuestra comunidad más activa</span></span>
            </a>
            <?php endif; ?>
            <?php if ($instagram !== ''): ?>
            <a class="social-cta__link" href="<?php echo tw_esc($instagram); ?>" target="_blank" rel="noopener" data-track-button="social-instagram">
              <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.16c3.2 0 3.58.01 4.85.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.36 1.06.41 2.23.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.05 1.17-.25 1.8-.41 2.23-.22.56-.48.96-.9 1.38-.42.42-.82.68-1.38.9-.42.16-1.06.36-2.23.41-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-1.17-.05-1.8-.25-2.23-.41-.56-.22-.96-.48-1.38-.9-.42-.42-.68-.82-.9-1.38-.16-.42-.36-1.06-.41-2.23C2.17 15.58 2.16 15.2 2.16 12s.01-3.58.07-4.85c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.36 2.23-.41C8.42 2.17 8.8 2.16 12 2.16zM12 0C8.74 0 8.33.01 7.05.07 5.78.13 4.9.33 4.14.63c-.79.3-1.46.72-2.13 1.38C1.35 2.68.93 3.35.63 4.14.33 4.9.13 5.78.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.06 1.27.26 2.15.56 2.91.3.79.72 1.46 1.38 2.13.67.66 1.34 1.08 2.13 1.38.76.3 1.64.5 2.91.56C8.33 23.99 8.74 24 12 24s3.67-.01 4.95-.07c1.27-.06 2.15-.26 2.91-.56.79-.3 1.46-.72 2.13-1.38.66-.67 1.08-1.34 1.38-2.13.3-.76.5-1.64.56-2.91.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95c-.06-1.27-.26-2.15-.56-2.91-.3-.79-.72-1.46-1.38-2.13C21.32 1.35 20.65.93 19.86.63 19.1.33 18.22.13 16.95.07 15.67.01 15.26 0 12 0zm0 5.84a6.16 6.16 0 1 0 0 12.32 6.16 6.16 0 0 0 0-12.32zm0 10.16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm7.85-10.4a1.44 1.44 0 1 1-2.88 0 1.44 1.44 0 0 1 2.88 0z"/></svg>
              <span><strong>Instagram</strong><span>Fotos y avances de cada rescate</span></span>
            </a>
            <?php endif; ?>
            <?php if ($tiktok !== ''): ?>
            <a class="social-cta__link" href="<?php echo tw_esc($tiktok); ?>" target="_blank" rel="noopener" data-track-button="social-tiktok">
              <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M16.6 5.82A4.28 4.28 0 0 1 15.54 3h-3.09v12.4a2.59 2.59 0 1 1-1.84-2.48V9.77a5.75 5.75 0 1 0 4.93 5.69V9.01a7.35 7.35 0 0 0 4.3 1.38V7.3a4.29 4.29 0 0 1-3.24-1.48z"/></svg>
              <span><strong>TikTok</strong><span>Historias cortas de la causa</span></span>
            </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </section>

    <section class="section">
      <div class="container">
        <div class="section-heading reveal">
          <div>
            <span class="eyebrow">Cómo apoyar</span>
            <h2>No hace falta adoptar <span class="accent">para ayudar.</span></h2>
          </div>
          <p>Todo suma: desde compartir una publicación hasta abrir tu casa unas semanas.</p>
        </div>
        <div class="highlight-grid reveal">
          <article class="highlight-card">
            <h3>Adopta</h3>
            <p>Revisa las fichas disponibles y empieza el proceso. Te acompañamos en la adaptación.</p>
            <a class="content-card__link" href="<?php echo tw_esc(tw_url('/adopciones/')); ?>">Ver disponibles</a>
          </article>
          <article class="highlight-card">
            <h3>Sé hogar temporal</h3>
            <p>Cuidas a un gato unas semanas mientras encuentra familia. Nosotros cubrimos alimento y veterinario.</p>
            <a class="content-card__link" href="<?php echo tw_esc(tw_url('/contacto/')); ?>">Quiero postularme</a>
          </article>
          <article class="highlight-card">
            <h3>Dona o comparte</h3>
            <p>Alimento, arena, cobijas o difusión. Compartir una publicación cuesta cero y salva vidas.</p>
            <a class="content-card__link" href="<?php echo tw_esc(tw_url('/campanas/')); ?>">Ver campañas</a>
          </article>
        </div>
      </div>
    </section>

    <section class="section section--tight">
      <div class="container reveal">
        <div class="cta-band">
          <div>
            <h2>¿Listo para dar el paso?</h2>
            <p>Escríbenos por WhatsApp o llena el formulario y te contamos cómo seguir.</p>
          </div>
          <?php if ($whatsapp !== ''): ?>
            <a class="btn" href="<?php echo tw_esc($whatsapp); ?>" target="_blank" rel="noopener" data-track-button="home-cta-whatsapp">Escribir por WhatsApp</a>
          <?php else: ?>
            <a class="btn" href="<?php echo tw_esc(tw_url('/contacto/')); ?>">Escríbenos</a>
          <?php endif; ?>
        </div>
      </div>
    </section>
  </main>
<?php tw_page_end(); ?>
