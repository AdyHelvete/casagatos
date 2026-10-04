<?php $twRoot = __DIR__; while (!is_file($twRoot . '/lib/page-boot.php') && dirname($twRoot) !== $twRoot) { $twRoot = dirname($twRoot); } require_once $twRoot . '/lib/page-boot.php'; ?>
<?php
$config = tw_config();
$brand = (string) ($config['brand']['name'] ?? 'La Casa de los Gatos');
$email = (string) ($config['contact']['email'] ?? '');
$location = (string) ($config['contact']['location'] ?? '');
$phone = (string) ($config['contact']['phoneDisplay'] ?? '');
?>
<?php tw_page_start('aviso-de-privacidad'); ?>
  <main id="contenido">
    <section class="page-hero page-hero--plain"><div class="container">
      <p class="breadcrumbs"><a href="<?php echo tw_esc(tw_url('/')); ?>">Inicio</a> / Aviso de privacidad</p>
      <span class="eyebrow eyebrow--lime">Legal</span>
      <h1>Aviso de privacidad</h1>
      <p>Cómo tratamos los datos personales que nos compartes al contactarnos, solicitar una adopción o apoyar una campaña.</p>
    </div></section>

    <section class="section"><div class="container">
      <div class="legal-document reveal">
        <p class="legal-meta">Última actualización: <?php echo tw_esc(date('d/m/Y')); ?>. Este aviso puede actualizarse; la versión vigente siempre es la publicada en esta página.</p>

        <h2>1. Responsable de los datos</h2>
        <p><strong><?php echo tw_esc($brand); ?></strong>, iniciativa ciudadana de rescate y adopción responsable con operación en <?php echo tw_esc($location); ?> y municipios cercanos, es responsable del tratamiento de tus datos personales.</p>
        <p>Puedes contactarnos en <a href="mailto:<?php echo tw_esc($email); ?>"><?php echo tw_esc($email); ?></a><?php if ($phone !== ''): ?> o al <?php echo tw_esc($phone); ?><?php endif; ?>.</p>

        <h2>2. Datos que recabamos</h2>
        <p>Recabamos únicamente los datos que nos proporcionas de forma voluntaria a través del formulario de contacto, WhatsApp o redes sociales:</p>
        <ul>
          <li>Nombre.</li>
          <li>Correo electrónico.</li>
          <li>Teléfono.</li>
          <li>Motivo de contacto y el contenido del mensaje.</li>
        </ul>
        <p>No solicitamos datos sensibles ni datos financieros a través de este sitio.</p>

        <h2>3. Finalidades del tratamiento</h2>
        <p>Usamos tus datos para las siguientes finalidades primarias:</p>
        <ul>
          <li>Atender y dar seguimiento a solicitudes de adopción, incluidos el cuestionario de adopción, la visita y entrevista en el hogar y el seguimiento posterior.</li>
          <li>Registrar tu participación en jornadas de esterilización y TNR.</li>
          <li>Orientarte sobre un caso de rescate o una denuncia de maltrato animal.</li>
          <li>Recibir sugerencias y correcciones para el directorio de clínicas veterinarias.</li>
          <li>Responder dudas y mantener comunicación contigo sobre tu solicitud.</li>
        </ul>
        <p>Como finalidad secundaria podemos enviarte avisos ocasionales sobre campañas. Puedes negarte en cualquier momento escribiéndonos al correo indicado, sin que ello afecte tu solicitud principal.</p>

        <h2>4. Transferencia de datos</h2>
        <p>No vendemos, rentamos ni comercializamos tus datos personales. Podemos compartirlos únicamente cuando sea indispensable para atender tu solicitud, por ejemplo con la clínica veterinaria que participa en una jornada a la que te registraste, o con otra asociación aliada cuando el caso corresponda a su zona de cobertura. En esos supuestos se comparte lo mínimo necesario.</p>
        <p>También podríamos revelarlos cuando lo requiera una autoridad competente en ejercicio de sus funciones.</p>

        <h2>5. Conservación</h2>
        <p>Conservamos las solicitudes de contacto por el tiempo necesario para dar seguimiento al caso y, en adopciones concretadas, mientras dure el acompañamiento posterior. Después se eliminan o se anonimizan.</p>

        <h2>6. Derechos ARCO</h2>
        <p>Tienes derecho a acceder, rectificar, cancelar u oponerte al tratamiento de tus datos personales, así como a revocar tu consentimiento. Para ejercer cualquiera de estos derechos escribe a <a href="mailto:<?php echo tw_esc($email); ?>"><?php echo tw_esc($email); ?></a> indicando:</p>
        <ol>
          <li>Tu nombre y un medio de contacto para responderte.</li>
          <li>El derecho que deseas ejercer y sobre qué datos.</li>
          <li>Cualquier documento que ayude a localizar tu registro.</li>
        </ol>
        <p>Responderemos en un plazo máximo de 20 días hábiles.</p>

        <h2>7. Cookies y tecnologías de medición</h2>
        <p>Este sitio puede utilizar cookies propias y de terceros con fines estadísticos, para entender qué contenidos son más consultados. No se usan para identificarte personalmente. Puedes bloquearlas desde la configuración de tu navegador; el sitio seguirá funcionando.</p>

        <h2>8. Seguridad</h2>
        <p>Aplicamos medidas técnicas y administrativas razonables para proteger la información: conexión cifrada (HTTPS), acceso restringido al panel de administración y validaciones contra envíos automatizados. Ningún sistema es infalible, por lo que te pedimos no compartir información sensible por estos medios.</p>

        <h2>9. Menores de edad</h2>
        <p>Las solicitudes de adopción deben ser realizadas por personas mayores de edad. Si detectamos que un formulario fue enviado por un menor, eliminaremos el registro.</p>

        <div class="legal-contact">
          <h3>Contacto para temas de privacidad</h3>
          <p><strong><?php echo tw_esc($brand); ?></strong><br>
          <?php echo tw_esc($location); ?><br>
          <a href="mailto:<?php echo tw_esc($email); ?>"><?php echo tw_esc($email); ?></a></p>
        </div>
      </div>
    </div></section>
  </main>
<?php tw_page_end(); ?>
