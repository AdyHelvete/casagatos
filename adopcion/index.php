<?php $twRoot = __DIR__; while (!is_file($twRoot . '/lib/page-boot.php') && dirname($twRoot) !== $twRoot) { $twRoot = dirname($twRoot); } require_once $twRoot . '/lib/page-boot.php'; ?>
<?php
$content = tw_content('adopcion');
$form = $content['questionnaire'];
$oni = $content['onychectomy'];
$whatsapp = tw_whatsapp_url('Hola La Casa de los Gatos, tengo dudas sobre el proceso de adopción.');

$all = ContentCollection::published('adoptions');
$cats = array_values(array_filter($all, static fn(array $item): bool => ($item['status'] ?? '') !== 'adoptado'));
$stories = array_values(array_filter($all, static fn(array $item): bool => ($item['status'] ?? '') === 'adoptado'));
$available = array_values(array_filter($all, static fn(array $item): bool => ($item['status'] ?? '') === 'disponible'));
$preselected = ContentCollection::slugify((string) ($_GET['gato'] ?? ''));
?>
<?php tw_page_start('adopcion'); ?>
  <main id="contenido">
    <?php tw_page_hero('Adopción', $content['hero']); ?>

    <nav class="container" aria-label="En esta página">
      <div class="filter-bar">
        <a href="#requisitos">Requisitos</a>
        <a href="#proceso">Proceso</a>
        <a href="#gatos">Gatos en adopción</a>
        <a href="#cuestionario">Cuestionario</a>
        <a href="#seguimiento">Seguimiento</a>
        <a href="#historias">Historias felices</a>
      </div>
    </nav>

    <section class="section section--dark" id="requisitos"><div class="container">
      <?php tw_heading($content['requirements'], true); ?>
      <ul class="check-list check-list--dark check-list--columns reveal">
        <?php foreach ($content['requirements']['list'] as $line): ?><li><?php echo tw_esc($line); ?></li><?php endforeach; ?>
      </ul>
    </div></section>

    <section class="section" id="onicectomia"><div class="container reveal">
      <div class="callout">
        <span class="eyebrow eyebrow--lime"><?php echo tw_esc($oni['eyebrow']); ?></span>
        <h2><?php echo tw_esc($oni['title']); ?></h2>
        <p class="callout__lead"><?php echo tw_esc($oni['text']); ?></p>
        <div class="callout__grid">
          <?php if ($oni['consequences'] !== []): ?>
          <div>
            <h3><?php echo tw_esc($oni['consequencesTitle']); ?></h3>
            <ul class="plain-list">
              <?php foreach ($oni['consequences'] as $line): ?><li><?php echo tw_esc($line); ?></li><?php endforeach; ?>
            </ul>
          </div>
          <?php endif; ?>
          <?php if ($oni['alternatives'] !== []): ?>
          <div>
            <h3><?php echo tw_esc($oni['alternativesTitle']); ?></h3>
            <ul class="plain-list">
              <?php foreach ($oni['alternatives'] as $line): ?><li><?php echo tw_esc($line); ?></li><?php endforeach; ?>
            </ul>
          </div>
          <?php endif; ?>
        </div>
        <?php if ($oni['note'] !== ''): ?><p class="callout__note"><?php echo tw_esc($oni['note']); ?></p><?php endif; ?>
      </div>
    </div></section>

    <section class="section section--tight" id="proceso"><div class="container">
      <?php tw_heading($content['process']); ?>
      <div class="process-grid process-grid--five reveal">
        <?php foreach ($content['process']['steps'] as $step): ?>
        <article class="process-step">
          <h3><?php echo tw_esc($step['title']); ?></h3>
          <p><?php echo tw_esc($step['text']); ?></p>
        </article>
        <?php endforeach; ?>
      </div>
    </div></section>

    <section class="section" id="visita"><div class="container">
      <?php tw_heading($content['visit']); ?>
      <ul class="check-list check-list--columns reveal">
        <?php foreach ($content['visit']['list'] as $line): ?><li><?php echo tw_esc($line); ?></li><?php endforeach; ?>
      </ul>
    </div></section>

    <section class="section section--tight" id="gatos"><div class="container">
      <?php tw_heading($content['cats']); ?>
      <?php if ($cats === []): ?>
        <div class="empty-state reveal">
          <h2><?php echo tw_esc($content['cats']['emptyTitle']); ?></h2>
          <p><?php echo tw_esc($content['cats']['emptyText']); ?></p>
        </div>
      <?php else: ?>
        <div class="content-grid reveal">
          <?php foreach ($cats as $item) { tw_adoption_card($item); } ?>
        </div>
      <?php endif; ?>
    </div></section>

    <section class="section" id="cuestionario"><div class="container">
      <?php tw_heading($form); ?>
      <form class="contact-form contact-form--wide reveal" data-contact-form data-form-kind="adopcion" novalidate>
        <input type="hidden" name="servicio" value="<?php echo tw_esc(PageContent::ADOPTION_FORM_SERVICE); ?>">
        <div class="form-grid">
          <div class="field"><label for="nombre">Nombre completo</label><input id="nombre" name="nombre" autocomplete="name" required maxlength="120" pattern="[\p{L}\p{M}\s'.-]{2,120}" title="Usa solo letras, espacios, puntos o guiones." placeholder="Tu nombre"></div>
          <div class="field"><label for="telefono">Teléfono o WhatsApp</label><input id="telefono" name="telefono" type="tel" autocomplete="tel" required maxlength="40" inputmode="tel" pattern="[\d\s+().-]{10,40}" title="Ingresa un teléfono válido de al menos 10 dígitos." placeholder="55 0000 0000"></div>
          <div class="field"><label for="email">Correo</label><input id="email" name="email" type="email" autocomplete="email" required maxlength="160" inputmode="email" placeholder="nombre@correo.com"></div>
          <div class="field"><label for="gato"><?php echo tw_esc($form['catLabel']); ?></label>
            <select id="gato" name="gato">
              <option value="">Aún no lo decido</option>
              <?php foreach ($available as $cat): ?>
                <option value="<?php echo tw_esc((string) $cat['name']); ?>"<?php echo $preselected !== '' && $preselected === (string) $cat['id'] ? ' selected' : ''; ?>><?php echo tw_esc((string) $cat['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php foreach ($form['questions'] as $index => $question): ?>
          <div class="field field--full">
            <label for="pregunta-<?php echo (int) $index; ?>"><?php echo ((int) $index + 1) . '. ' . tw_esc($question); ?></label>
            <textarea class="field__answer" id="pregunta-<?php echo (int) $index; ?>" data-question="<?php echo tw_esc($question); ?>" required maxlength="280" rows="2"></textarea>
          </div>
          <?php endforeach; ?>
          <div class="field field--full field--check">
            <label><input type="checkbox" name="mayor_edad" value="1" required> <span><?php echo tw_esc($form['ageConfirm']); ?></span></label>
          </div>
          <div class="field field--honeypot" aria-hidden="true">
            <label for="contact_hp">No completar</label>
            <input id="contact_hp" name="_hp" type="text" tabindex="-1" autocomplete="off" inputmode="none" aria-hidden="true" data-lpignore="true" data-1p-ignore data-bwignore value="">
          </div>
        </div>
        <p class="form-note">Al enviar aceptas nuestro <a href="<?php echo tw_esc(tw_url('/aviso-de-privacidad/')); ?>">Aviso de privacidad</a>. Guardamos tus respuestas y abrimos WhatsApp para continuar la conversación.</p>
        <button class="btn" type="submit"><?php echo tw_esc($form['button']); ?></button>
        <p class="form-status" aria-live="polite"></p>
      </form>
    </div></section>

    <section class="section section--dark" id="seguimiento"><div class="container">
      <?php tw_heading($content['followup'], true); ?>
      <div class="process-grid process-grid--auto reveal">
        <?php foreach ($content['followup']['steps'] as $step): ?>
        <article class="process-step">
          <h3><?php echo tw_esc($step['title']); ?></h3>
          <p><?php echo tw_esc($step['text']); ?></p>
        </article>
        <?php endforeach; ?>
      </div>
    </div></section>

    <?php if ($stories !== []): ?>
    <section class="section" id="historias"><div class="container">
      <?php tw_heading($content['stories']); ?>
      <div class="content-grid reveal">
        <?php foreach ($stories as $item) { tw_story_card($item); } ?>
      </div>
    </div></section>
    <?php endif; ?>

    <section class="section section--tight"><div class="container reveal"><div class="cta-band">
      <div><h2><?php echo tw_esc($content['cta']['title']); ?></h2><p><?php echo tw_esc($content['cta']['text']); ?></p></div>
      <div class="button-row">
        <a class="btn" href="#cuestionario">Ir al cuestionario</a>
        <?php if ($whatsapp !== ''): ?>
          <a class="btn btn--outline" href="<?php echo tw_esc($whatsapp); ?>" target="_blank" rel="noopener" data-track-button="adopcion-whatsapp">Tengo una duda</a>
        <?php endif; ?>
      </div>
    </div></div></section>
  </main>
<?php tw_page_end(); ?>
