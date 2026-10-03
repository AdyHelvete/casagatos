<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/page-boot.php';

require_once __DIR__ . '/lib/SecurityPolicy.php';
SecurityPolicy::apply('public');

PageRenderer::render404();
