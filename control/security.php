<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__) . '/lib/SecurityPolicy.php';
require_once dirname(__DIR__) . '/lib/SecretRotation.php';
require_once dirname(__DIR__) . '/lib/SecurityAudit.php';
require_once dirname(__DIR__) . '/lib/SecurityLog.php';
ControlAuth::requireLogin();

$error = '';
$success = '';
$config = SecurityPolicy::config();
$reports = SiteStorage::read('csp-reports.json', ['reports' => []]);
$reportItems = is_array($reports['reports'] ?? null) ? $reports['reports'] : [];
$secretStatus = SecretRotation::status();
$auditChecks = SecurityAudit::run();
$auditSummary = SecurityAudit::summary($auditChecks);
$securityEvents = SecurityLog::recent(25);

$tabIds = ['auditoria', 'eventos', 'csp', 'secretos'];
$activeTab = trim((string) ($_GET['tab'] ?? 'auditoria'));
if (!in_array($activeTab, $tabIds, true)) {
    $activeTab = 'auditoria';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    controlRequirePost();
    $action = (string) ($_POST['action'] ?? '');
    $activeTab = trim((string) ($_POST['_active_tab'] ?? $activeTab));
    if (!in_array($activeTab, $tabIds, true)) {
        $activeTab = 'auditoria';
    }

    if ($action === 'csp_mode') {
        $activeTab = 'csp';
        $mode = (string) ($_POST['csp_mode'] ?? 'report-only');
        if (!in_array($mode, ['off', 'report-only', 'enforce'], true)) {
            $error = 'Modo CSP no válido.';
        } else {
            $config['csp']['mode'] = $mode;
            if (SecurityPolicy::saveConfig($config)) {
                $success = $mode === 'off'
                    ? 'CSP desactivada.'
                    : ($mode === 'enforce'
                        ? 'CSP activa en modo enforce.'
                        : 'CSP en modo report-only.');
                $auditChecks = SecurityAudit::run();
                $auditSummary = SecurityAudit::summary($auditChecks);
            } else {
                $error = 'No se pudo guardar la configuración.';
            }
        }
    } elseif ($action === 'clear_reports') {
        $activeTab = 'csp';
        if (SiteStorage::write('csp-reports.json', ['reports' => []])) {
            $reportItems = [];
            $success = 'Informes CSP eliminados.';
            $auditChecks = SecurityAudit::run();
            $auditSummary = SecurityAudit::summary($auditChecks);
        } else {
            $error = 'No se pudieron eliminar los informes.';
        }
    } elseif ($action === 'clear_events') {
        $activeTab = 'eventos';
        if (SecurityLog::clear()) {
            $securityEvents = [];
            $success = 'Registro de eventos limpiado.';
        } else {
            $error = 'No se pudo limpiar el registro.';
        }
    } elseif (str_starts_with($action, 'rotate_')) {
        $activeTab = 'secretos';
        $key = substr($action, 7);
        $result = SecretRotation::rotate($key);
        if ($result['success']) {
            $success = $result['message'];
            $secretStatus = SecretRotation::status();
            $config = SecurityPolicy::config();
            $auditChecks = SecurityAudit::run();
            $auditSummary = SecurityAudit::summary($auditChecks);
        } else {
            $error = $result['message'];
        }
    }
}

$cspMode = SecurityPolicy::cspMode();

controlHeader('Seguridad', 'security');
?>
<?php if ($error): ?><div class="control-alert control-alert--error"><?php echo h($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="control-alert control-alert--success"><?php echo h($success); ?></div><?php endif; ?>

<?php
controlTabsStart('security', [
    'auditoria' => 'Auditoría',
    'eventos' => 'Eventos',
    'csp' => 'CSP',
    'secretos' => 'Secretos',
], $activeTab);
?>

<?php controlTabPanelStart('security', 'auditoria', $activeTab === 'auditoria'); ?>
<p class="control-tabs__intro">Comprobaciones automáticas del estado de seguridad. Corrige primero los puntos en <strong>fail</strong>, luego revisa las advertencias.</p>

<div class="control-audit-summary">
    <span class="control-audit-pill control-audit-pill--ok"><?php echo (int) $auditSummary['ok']; ?> OK</span>
    <span class="control-audit-pill control-audit-pill--warn"><?php echo (int) $auditSummary['warn']; ?> aviso(s)</span>
    <span class="control-audit-pill control-audit-pill--fail"><?php echo (int) $auditSummary['fail']; ?> crítico(s)</span>
</div>

<div class="control-table-wrap">
    <table class="control-table control-table--compact">
        <thead>
            <tr>
                <th>Comprobación</th>
                <th>Estado</th>
                <th>Detalle</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($auditChecks as $check): ?>
                <tr>
                    <td><?php echo h($check['label']); ?></td>
                    <td><span class="control-audit-badge control-audit-badge--<?php echo h($check['status']); ?>"><?php echo h(strtoupper($check['status'])); ?></span></td>
                    <td><?php echo h($check['message']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<section class="control-panel control-panel--accent">
    <div class="control-panel__head">
        <h3>Mantenimiento recomendado</h3>
    </div>
    <ul class="control-list">
        <li>Revisa la pestaña <strong>Eventos</strong> si hay picos de login fallido o spam en contacto.</li>
        <li>Pasa CSP a <strong>enforce</strong> cuando la pestaña CSP no muestre violaciones legítimas.</li>
        <li>Rota secretos cada 6–12 meses o si sospechas filtración.</li>
        <li>Mantén PHP y MySQL actualizados desde el panel de tu hosting.</li>
        <li>Revisa logs del servidor (403/404 masivos, POST a <code>/api/</code>).</li>
    </ul>
</section>
<?php controlTabPanelEnd(); ?>

<?php controlTabPanelStart('security', 'eventos', $activeTab === 'eventos'); ?>
<p class="control-tabs__intro">Actividad sospechosa registrada: logins fallidos, bloqueos por rate limit y envíos de contacto rechazados.</p>

<div class="control-panel__head control-panel__head--sub">
    <h3>Últimos eventos</h3>
    <form method="POST" class="control-inline-form">
        <?php echo controlCsrfField(); ?>
        <input type="hidden" name="action" value="clear_events">
        <input type="hidden" name="_active_tab" value="eventos">
        <button type="submit" class="control-btn control-btn--ghost control-btn--sm">Limpiar registro</button>
    </form>
</div>

<?php if ($securityEvents === []): ?>
    <p class="control-muted">Sin eventos registrados. Es normal si no ha habido intentos fallidos recientes.</p>
<?php else: ?>
    <div class="control-table-wrap">
        <table class="control-table control-table--compact">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>IP</th>
                    <th>Detalle</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($securityEvents as $event): ?>
                    <tr>
                        <td><?php echo h(substr((string) ($event['at'] ?? ''), 0, 19)); ?></td>
                        <td><?php echo h(SecurityLog::label((string) ($event['type'] ?? ''))); ?></td>
                        <td><code><?php echo h((string) ($event['ip'] ?? '')); ?></code></td>
                        <td><?php echo h((string) ($event['detail'] ?? '')); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php controlTabPanelEnd(); ?>

<?php controlTabPanelStart('security', 'csp', $activeTab === 'csp'); ?>
<p class="control-tabs__intro">Protege contra inyección de scripts. Las páginas HTML estáticas usan report-only fijo en <code>.htaccess</code>; el modo aquí aplica a las páginas PHP (sitio público, control y 404).</p>

<form method="POST" class="control-form control-form--narrow">
    <?php echo controlCsrfField(); ?>
    <input type="hidden" name="action" value="csp_mode">
    <input type="hidden" name="_active_tab" value="csp">
    <label><?php echo controlFieldLabel('Modo CSP', 'Report-only solo registra. Enforce bloquea scripts no permitidos. Off desactiva la política en PHP (el HTML estático sigue con report-only en .htaccess).'); ?>
        <select name="csp_mode">
            <option value="report-only" <?php echo $cspMode === 'report-only' ? 'selected' : ''; ?>>Report-only (recomendado)</option>
            <option value="enforce" <?php echo $cspMode === 'enforce' ? 'selected' : ''; ?>>Enforce (bloquear violaciones)</option>
            <option value="off" <?php echo $cspMode === 'off' ? 'selected' : ''; ?>>Desactivada</option>
        </select>
    </label>
    <div class="control-form-footer control-form-footer--flush">
        <button type="submit" class="control-btn">Guardar modo</button>
    </div>
</form>

<dl class="control-dl">
    <div><dt>Informes</dt><dd><code>/api/csp-report.php</code></dd></div>
    <div><dt>Violaciones registradas</dt><dd><?php echo count($reportItems); ?></dd></div>
</dl>

<div class="control-panel__head control-panel__head--sub">
    <h3>Últimos informes CSP</h3>
    <form method="POST" class="control-inline-form">
        <?php echo controlCsrfField(); ?>
        <input type="hidden" name="action" value="clear_reports">
        <input type="hidden" name="_active_tab" value="csp">
        <button type="submit" class="control-btn control-btn--ghost control-btn--sm">Limpiar informes</button>
    </form>
</div>

<?php if ($reportItems === []): ?>
    <p class="control-muted">Sin violaciones reportadas. Navega el sitio para generar datos antes de enforce.</p>
<?php else: ?>
    <div class="control-table-wrap">
        <table class="control-table control-table--compact">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Página</th>
                    <th>Directiva</th>
                    <th>Recurso</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_slice($reportItems, 0, 15) as $row): ?>
                    <tr>
                        <td><?php echo h(substr((string) ($row['at'] ?? ''), 0, 19)); ?></td>
                        <td><?php echo h((string) ($row['document_uri'] ?? '')); ?></td>
                        <td><code><?php echo h((string) ($row['violated_directive'] ?? '')); ?></code></td>
                        <td><?php echo h((string) ($row['blocked_uri'] ?? '')); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php controlTabPanelEnd(); ?>

<?php controlTabPanelStart('security', 'secretos', $activeTab === 'secretos'); ?>
<p class="control-tabs__intro">Regenera claves HMAC si sospechas filtración. Los archivos viven en <code>data/</code> (bloqueados por Apache).</p>

<div class="control-cards control-cards--compact">
    <?php foreach ($secretStatus as $key => $meta): ?>
        <article class="control-card">
            <h2><?php echo h($meta['label']); ?></h2>
            <p><?php echo $meta['exists'] ? 'Activo' : 'Se creará al rotar o al primer uso'; ?></p>
            <?php if (!empty($meta['rotatedAt'])): ?>
                <p class="control-muted">Última rotación: <?php echo h((string) $meta['rotatedAt']); ?></p>
            <?php endif; ?>
            <form method="POST" onsubmit="return confirm('¿Rotar este secreto? Los formularios abiertos deberán recargarse.');">
                <?php echo controlCsrfField(); ?>
                <input type="hidden" name="action" value="rotate_<?php echo h($key); ?>">
                <input type="hidden" name="_active_tab" value="secretos">
                <button type="submit" class="control-btn control-btn--ghost" <?php echo $meta['writable'] ? '' : 'disabled'; ?>>Rotar secreto</button>
            </form>
        </article>
    <?php endforeach; ?>
</div>
<?php controlTabPanelEnd(); ?>

<?php controlTabsEnd(); ?>
<?php controlFooter(); ?>
