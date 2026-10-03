<?php $twRoot = __DIR__; while (!is_file($twRoot . '/lib/page-boot.php') && dirname($twRoot) !== $twRoot) { $twRoot = dirname($twRoot); } require_once $twRoot . '/lib/page-boot.php'; ?>
<?php $whatsapp = tw_whatsapp_url('Hola La Casa de los Gatos, ya leí el proceso de adopción y quiero empezar.'); ?>
<?php tw_page_start('como-adoptar'); ?>
  <main id="contenido">
    <section class="page-hero page-hero--plain"><div class="container">
      <p class="breadcrumbs"><a href="<?php echo tw_esc(tw_url('/')); ?>">Inicio</a> / Cómo adoptar</p>
      <span class="eyebrow eyebrow--lime">El proceso</span>
      <h1>Adoptar toma unos días. <span class="accent">Vivir juntos, 15 años.</span></h1>
      <p>Nuestro proceso no busca complicarte la vida: busca que la adopción sea definitiva y que el gato no regrese a la calle en tres meses.</p>
    </div></section>

    <section class="section"><div class="container">
      <div class="section-heading reveal">
        <div><span class="eyebrow">Paso a paso</span><h2>Cinco pasos, sin sorpresas.</h2></div>
        <p>Desde que nos escribes hasta la entrega suelen pasar entre 5 y 10 días.</p>
      </div>
      <div class="process-grid process-grid--five reveal">
        <article class="process-step">
          <h3>1. Elige y escríbenos</h3>
          <p>Revisa las fichas en <a href="<?php echo tw_esc(tw_url('/adopciones/')); ?>">Adopciones</a> y mándanos un mensaje diciendo cuál te interesa y por qué.</p>
        </article>
        <article class="process-step">
          <h3>2. Entrevista</h3>
          <p>Una charla por teléfono o videollamada. Preguntamos con quién vives, si hay otras mascotas y cómo es tu espacio.</p>
        </article>
        <article class="process-step">
          <h3>3. Visita o fotos del hogar</h3>
          <p>Verificamos que haya protección en ventanas y balcones. Es el punto donde más adopciones se detienen, y por buenas razones.</p>
        </article>
        <article class="process-step">
          <h3>4. Carta compromiso</h3>
          <p>Firmas un acuerdo simple: esterilización, no declarar, no liberar y avisarnos si algo cambia.</p>
        </article>
        <article class="process-step">
          <h3>5. Entrega y seguimiento</h3>
          <p>Te entregamos al gato con su historial. Damos seguimiento al mes y a los tres meses.</p>
        </article>
      </div>
    </div></section>

    <section class="section section--dark"><div class="container">
      <div class="section-heading reveal">
        <div><span class="eyebrow eyebrow--lime">Requisitos</span><h2>Lo que pedimos.</h2></div>
        <p>Nada aquí es negociable, y todo tiene una razón detrás de una experiencia previa.</p>
      </div>
      <ul class="check-list check-list--dark reveal">
        <li>Ser mayor de edad y presentar identificación oficial</li>
        <li>Que todas las personas de la casa estén de acuerdo con la adopción</li>
        <li>Ventanas y balcones protegidos con malla o red</li>
        <li>Compromiso de esterilizar al gato cuando tenga la edad adecuada</li>
        <li>No declarar (la onicectomía es una amputación, no un corte de uñas)</li>
        <li>Mantenerlo dentro de casa; nada de "salidas libres" a la calle</li>
        <li>Disposición a recibir el seguimiento posterior a la entrega</li>
      </ul>
    </div></section>

    <section class="section"><div class="container">
      <div class="section-heading reveal">
        <div><span class="eyebrow">Antes de decidir</span><h2>Preguntas que conviene responderte.</h2></div>
        <p>Si alguna te genera duda, escríbenos: preferimos resolverla ahora y no después.</p>
      </div>
      <div class="faq-list reveal">
        <details class="faq-item">
          <summary>¿La adopción tiene costo?</summary>
          <p>No cobramos por el gato. Pedimos una cuota de recuperación voluntaria que cubre parte de lo invertido en vacunas, desparasitación y esterilización. Si no puedes cubrirla, dilo: no es un filtro económico.</p>
        </details>
        <details class="faq-item">
          <summary>Vivo en departamento, ¿es un problema?</summary>
          <p>Al contrario. Un departamento con ventanas protegidas es un excelente hogar para un gato. Lo que sí revisamos es que no haya riesgo de caída.</p>
        </details>
        <details class="faq-item">
          <summary>Ya tengo otro gato o un perro</summary>
          <p>Es compatible en la mayoría de los casos. Te damos indicaciones para la presentación gradual, que suele tomar de una a tres semanas.</p>
        </details>
        <details class="faq-item">
          <summary>¿Puedo adoptar si no vivo en Tizayuca?</summary>
          <p>Sí, siempre que podamos coordinar la entrega y el seguimiento. Trabajamos principalmente en Tizayuca, Tecámac, Zumpango y municipios cercanos.</p>
        </details>
        <details class="faq-item">
          <summary>Quiero regalarle un gato a alguien</summary>
          <p>No entregamos gatos como sorpresa. La persona que va a convivir con el gato tiene que ser parte del proceso desde el inicio.</p>
        </details>
        <details class="faq-item">
          <summary>¿Y si no funciona la convivencia?</summary>
          <p>Avísanos. Preferimos recibirlo de vuelta y buscarle otro hogar antes de que termine en la calle o con alguien que no lo quiera.</p>
        </details>
      </div>
    </div></section>

    <section class="section section--tight"><div class="container reveal"><div class="cta-band">
      <div><h2>¿Todo claro?</h2><p>Elige a quién quieres adoptar y empecemos el proceso.</p></div>
      <div class="button-row">
        <a class="btn" href="<?php echo tw_esc(tw_url('/adopciones/')); ?>">Ver disponibles</a>
        <?php if ($whatsapp !== ''): ?>
          <a class="btn btn--outline" href="<?php echo tw_esc($whatsapp); ?>" target="_blank" rel="noopener" data-track-button="como-adoptar-whatsapp">Escribir por WhatsApp</a>
        <?php endif; ?>
      </div>
    </div></div></section>
  </main>
<?php tw_page_end(); ?>
