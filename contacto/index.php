<?php $twRoot = __DIR__; while (!is_file($twRoot . '/lib/page-boot.php') && dirname($twRoot) !== $twRoot) { $twRoot = dirname($twRoot); } require_once $twRoot . '/lib/page-boot.php'; ?>
<?php
$config = tw_config();
$contact = $config['contact'] ?? [];
$facebook = (string) ($config['social']['facebook'] ?? '');
$whatsapp = tw_whatsapp_url();
$phoneDisplay = (string) ($contact['phoneDisplay'] ?? '');
$email = (string) ($contact['email'] ?? '');
$location = (string) ($contact['location'] ?? '');
$content = tw_content('contacto');
$intro = $content['intro'];
?>
<?php tw_page_start('contacto'); ?>
  <main id="contenido">
    <?php tw_page_hero('Contacto', $content['hero']); ?>

    <section class="section"><div class="container contact-grid">
      <div class="content-block reveal">
        <span class="eyebrow"><?php echo tw_esc($intro['eyebrow']); ?></span>
        <h2><?php echo tw_esc($intro['title']); ?></h2>
        <p><?php echo tw_esc($intro['text']); ?></p>
        <ul class="check-list">
          <?php foreach ($intro['list'] as $line): ?><li><?php echo tw_esc($line); ?></li><?php endforeach; ?>
        </ul>
        <div class="contact-details">
          <?php if ($whatsapp !== ''): ?>
          <div><span>WhatsApp / Teléfono</span><a href="<?php echo tw_esc($whatsapp); ?>" target="_blank" rel="noopener"><?php echo tw_esc($phoneDisplay); ?></a></div>
          <?php endif; ?>
          <div><span>Correo</span><a href="mailto:<?php echo tw_esc($email); ?>"><?php echo tw_esc($email); ?></a></div>
          <div><span>Zona de operación</span><strong><?php echo tw_esc($location); ?> · Tecámac, Zumpango y alrededores</strong></div>
          <?php if ($facebook !== ''): ?>
          <div><span>Facebook</span><a href="<?php echo tw_esc($facebook); ?>" target="_blank" rel="noopener">La Casa de los Gatos</a></div>
          <?php endif; ?>
        </div>
      </div>

      <form class="contact-form reveal" data-contact-form novalidate>
        <span class="eyebrow">Déjanos tu mensaje</span>
        <h3><?php echo tw_esc($intro['formTitle']); ?></h3>
        <div class="form-grid">
          <div class="field"><label for="nombre">Nombre</label><input id="nombre" name="nombre" autocomplete="name" required maxlength="120" pattern="[\p{L}\p{M}\s'.-]{2,120}" title="Usa solo letras, espacios, puntos o guiones." placeholder="Tu nombre"></div>
          <div class="field"><label for="email">Correo</label><input id="email" name="email" type="email" autocomplete="email" required maxlength="160" inputmode="email" placeholder="nombre@correo.com"></div>
          <div class="field"><label for="telefono">Teléfono</label><input id="telefono" name="telefono" type="tel" autocomplete="tel" required maxlength="40" inputmode="tel" pattern="[\d\s+().-]{10,40}" title="Ingresa un teléfono válido de al menos 10 dígitos." placeholder="55 0000 0000"></div>
          <div class="field"><label for="servicio">Motivo</label><select id="servicio" name="servicio" required><option value="">Selecciona una opción</option><?php echo tw_service_options(); ?></select></div>
          <div class="field field--full"><label for="mensaje">Cuéntanos más</label><textarea id="mensaje" name="mensaje" required maxlength="4000" placeholder="Cuéntanos el caso: dónde está el gato, qué pasó y cómo podemos ayudarte."></textarea></div>
          <div class="field field--honeypot" aria-hidden="true">
            <label for="contact_hp">No completar</label>
            <input id="contact_hp" name="_hp" type="text" tabindex="-1" autocomplete="off" inputmode="none" aria-hidden="true" data-lpignore="true" data-1p-ignore data-bwignore value="">
          </div>
        </div>
        <p class="form-note">Al enviar aceptas nuestro <a href="<?php echo tw_esc(tw_url('/aviso-de-privacidad/')); ?>">Aviso de privacidad</a> y abriremos WhatsApp con tu mensaje listo para continuar la conversación.</p>
        <button class="btn" type="submit">Enviar mensaje</button>
        <p class="form-status" aria-live="polite"></p>
      </form>
    </div></section>

    <section class="section section--tight"><div class="container reveal"><div class="cta-band">
      <div><h2>¿Quieres adoptar?</h2><p>El proceso empieza con el cuestionario de adopción, no con este formulario.</p></div>
      <a class="btn" href="<?php echo tw_esc(tw_url('/adopcion/')); ?>#cuestionario">Ir al cuestionario</a>
    </div></div></section>
  </main>
<?php tw_page_end(); ?>
