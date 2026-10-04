<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
ControlAuth::requireLogin();

$config = SiteStorage::getSiteConfig();
$contactCount = count(ContactAdmin::getAll());
$galleryCount = count(MediaGallery::getItems());
$pages = PageRegistry::pages();
$publishedPages = count(array_filter($pages, fn($page) => ($page['status'] ?? '') === 'published'));
$menuCount = count(PageRegistry::menuItems());
$seoIssues = array_sum(array_map(fn(array $row): int => count($row['issues']), SeoTools::audit()));

$adoptions = ContentCollection::all('adoptions');
$available = count(array_filter(
    $adoptions,
    fn(array $item): bool => !empty($item['published']) && ($item['status'] ?? '') === 'disponible'
));
$adopted = count(array_filter($adoptions, fn(array $item): bool => ($item['status'] ?? '') === 'adoptado'));
$campaignsActive = count(array_filter(
    ContentCollection::published('campaigns'),
    fn(array $item): bool => in_array((string) ($item['status'] ?? ''), ['activa', 'proxima'], true)
));
$guideCount = count(ContentCollection::published('guides'));
$clinics = ContentCollection::published('clinics');
$clinicsTizayuca = count(array_filter($clinics, fn(array $item): bool => ($item['zone'] ?? '') === 'tizayuca'));
$clinicsZumpango = count($clinics) - $clinicsTizayuca;

controlHeader('Dashboard', 'index');
?>
<?php if ($pages === []): ?>
<div class="control-alert">
    Todavía no has importado las páginas del sitio. Ve a <a href="<?php echo h(cu('/control/pages.php')); ?>">Páginas y menú</a> para registrarlas y poder administrar su SEO, el menú y su contenido.
</div>
<?php endif; ?>
<div class="control-cards">
    <article class="control-card">
        <h2>Textos de las páginas <?php echo controlHelp('Títulos, textos, listas y preguntas del cuestionario de cada pestaña: Inicio, TNR, Adopción, Asistencia y orientación, Directorio y Contacto.'); ?></h2>
        <p>Edita el contenido de cada pestaña sin tocar código.</p>
        <a class="control-btn control-btn--ghost" href="<?php echo h(cu('/control/content.php')); ?>">Editar textos</a>
    </article>
    <article class="control-card">
        <h2>TNR · Jornadas <?php echo controlHelp('Jornadas de esterilización y TNR de /tnr/. Las finalizadas pasan al historial.'); ?></h2>
        <p><?php echo (int) $campaignsActive; ?> abierta(s) o próxima(s).</p>
        <a class="control-btn control-btn--ghost" href="<?php echo h(cu('/control/jornadas.php')); ?>">Gestionar jornadas</a>
    </article>
    <article class="control-card">
        <h2>Adopción <?php echo controlHelp('Fichas de /adopcion/. Las marcadas como Adoptado se muestran en Historias felices.'); ?></h2>
        <p><?php echo (int) $available; ?> en adopción · <?php echo (int) $adopted; ?> historias felices.</p>
        <a class="control-btn control-btn--ghost" href="<?php echo h(cu('/control/adoptions.php')); ?>">Gestionar fichas</a>
    </article>
    <article class="control-card">
        <h2>Orientación <?php echo controlHelp('Casos de "Qué hacer en cada caso" de /asistencia/. Las denuncias se editan en Textos de las páginas.'); ?></h2>
        <p><?php echo (int) $guideCount; ?> caso(s) publicados.</p>
        <a class="control-btn control-btn--ghost" href="<?php echo h(cu('/control/guides.php')); ?>">Gestionar casos</a>
    </article>
    <article class="control-card">
        <h2>Directorio <?php echo controlHelp('Clínicas veterinarias de /directorio/, por zona y con sus servicios.'); ?></h2>
        <p><?php echo (int) $clinicsTizayuca; ?> en Tizayuca · <?php echo (int) $clinicsZumpango; ?> en Zumpango.</p>
        <a class="control-btn control-btn--ghost" href="<?php echo h(cu('/control/clinics.php')); ?>">Gestionar clínicas</a>
    </article>
    <article class="control-card">
        <h2>Páginas y menú <?php echo controlHelp('Crea secciones, el menú superior y publícalas. El orden más bajo sale primero. Los borradores no se ven salvo con ?preview y sesión activa.'); ?></h2>
        <p><?php echo (int) $publishedPages; ?> publicadas · <?php echo (int) $menuCount; ?> en el menú.</p>
        <a class="control-btn control-btn--ghost" href="<?php echo h(cu('/control/pages.php')); ?>">Administrar páginas</a>
    </article>
    <article class="control-card">
        <h2>SEO y sitemap <?php echo controlHelp('Título, descripción y datos para redes. El sitemap.xml se regenera al guardar.'); ?></h2>
        <p><?php echo $seoIssues === 0 ? 'Sin observaciones pendientes.' : $seoIssues . ' observación(es) por revisar.'; ?></p>
        <a class="control-btn control-btn--ghost" href="<?php echo h(cu('/control/seo.php')); ?>">Revisar SEO</a>
    </article>
    <article class="control-card">
        <h2>Código <?php echo controlHelp('Edita HTML, CSS o textos. Cada guardado deja un respaldo. No abre lib/, data/ ni api/.'); ?></h2>
        <p>Contenido de páginas, hojas de estilo y archivos de texto.</p>
        <a class="control-btn control-btn--ghost" href="<?php echo h(cu('/control/code.php')); ?>">Abrir editor</a>
    </article>
    <article class="control-card">
        <h2>Formularios <?php echo controlHelp('Lista de motivos del contacto y límites anti-spam. Lo que no esté en la lista se rechaza.'); ?></h2>
        <p>Motivos de contacto, validaciones y límites anti-spam.</p>
        <a class="control-btn control-btn--ghost" href="<?php echo h(cu('/control/forms.php')); ?>">Configurar formularios</a>
    </article>
    <article class="control-card">
        <h2>Configuración <?php echo controlHelp('Logos, WhatsApp, redes y textos del pie. Se reflejan en todo el sitio.'); ?></h2>
        <p>Logotipos, WhatsApp, correo, ubicación y redes sociales.</p>
        <a class="control-btn control-btn--ghost" href="<?php echo h(cu('/control/settings.php')); ?>">Editar configuración</a>
    </article>
    <article class="control-card">
        <h2>Biblioteca de imágenes <?php echo controlHelp('Imágenes reutilizables. Formatos: JPG, PNG, WebP y GIF. Máximo 8 MB.'); ?></h2>
        <p><?php echo (int) $galleryCount; ?> imágenes disponibles.</p>
        <a class="control-btn control-btn--ghost" href="<?php echo h(cu('/control/gallery.php')); ?>">Gestionar biblioteca</a>
    </article>
    <article class="control-card">
        <h2>Contactos <?php echo controlHelp('Registros del formulario público. Puedes exportar a Excel o eliminar.'); ?></h2>
        <p><?php echo (int) $contactCount; ?> registros guardados.</p>
        <a class="control-btn control-btn--ghost" href="<?php echo h(cu('/control/contacts.php')); ?>">Administrar contactos</a>
    </article>
    <article class="control-card">
        <h2>Seguridad <?php echo controlHelp('Auditoría, eventos, CSP y rotación de secretos HMAC.'); ?></h2>
        <p>Auditoría, eventos, CSP y rotación de secretos.</p>
        <a class="control-btn control-btn--ghost" href="<?php echo h(cu('/control/security.php')); ?>">Abrir seguridad</a>
    </article>
    <article class="control-card">
        <h2>Redes activas <?php echo controlHelp('Se configuran en Configuración → Redes sociales. Vacío = icono oculto.'); ?></h2>
        <ul class="control-list">
            <?php foreach (['facebook' => 'Facebook', 'instagram' => 'Instagram', 'tiktok' => 'TikTok'] as $key => $label): ?>
                <li><?php echo h($label); ?>: <?php echo !empty($config['social'][$key]) ? 'Configurada' : 'Sin enlace'; ?></li>
            <?php endforeach; ?>
        </ul>
    </article>
</div>
<?php controlFooter(); ?>
