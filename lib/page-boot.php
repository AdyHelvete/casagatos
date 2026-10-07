<?php
declare(strict_types=1);

/**
 * Arranque de las páginas públicas. Cada plantilla incluye este archivo y usa
 * tw_page_start() / tw_page_end() para envolver su contenido.
 */

require_once __DIR__ . '/SiteStorage.php';
require_once __DIR__ . '/PageRegistry.php';
require_once __DIR__ . '/PageRenderer.php';
require_once __DIR__ . '/ContentCollection.php';
require_once __DIR__ . '/PageContent.php';

if (!function_exists('tw_page_start')) {
    function tw_page_start(string $pageId, array $overrides = []): void
    {
        PageRenderer::startDocument($pageId, $overrides);
    }
}

if (!function_exists('tw_page_end')) {
    function tw_page_end(): void
    {
        PageRenderer::endDocument();
    }
}

if (!function_exists('tw_page')) {
    function tw_page(): array
    {
        return PageRenderer::current();
    }
}

if (!function_exists('tw_config')) {
    function tw_config(): array
    {
        return PageRenderer::siteConfig();
    }
}

if (!function_exists('tw_esc')) {
    function tw_esc(?string $value): string
    {
        return PageRenderer::esc($value);
    }
}

if (!function_exists('tw_url')) {
    function tw_url(string $path): string
    {
        return PageRegistry::url($path);
    }
}

if (!function_exists('tw_content')) {
    /**
     * Textos editables de una página (Control · Textos de las páginas).
     */
    function tw_content(string $page): array
    {
        return PageContent::get($page);
    }
}

if (!function_exists('tw_paragraphs')) {
    /**
     * Texto largo del panel convertido en párrafos: una línea en blanco
     * separa un párrafo del siguiente.
     */
    function tw_paragraphs(string $text, string $class = ''): void
    {
        $attr = $class !== '' ? ' class="' . tw_esc($class) . '"' : '';
        foreach (preg_split('/\R\s*\R/', trim($text)) ?: [] as $paragraph) {
            if (trim($paragraph) !== '') {
                echo '<p' . $attr . '>' . tw_esc(trim($paragraph)) . '</p>';
            }
        }
    }
}

if (!function_exists('tw_page_hero')) {
    /**
     * Encabezado estándar de las páginas interiores.
     *
     * @param array<string, mixed> $hero eyebrow, title, accent, lead
     */
    function tw_page_hero(string $crumb, array $hero): void
    {
        $accent = trim((string) ($hero['accent'] ?? ''));

        echo '<section class="page-hero page-hero--plain"><div class="container">';
        echo '<p class="breadcrumbs"><a href="' . tw_esc(tw_url('/')) . '">Inicio</a> / ' . tw_esc($crumb) . '</p>';
        if (trim((string) ($hero['eyebrow'] ?? '')) !== '') {
            echo '<span class="eyebrow eyebrow--lime">' . tw_esc((string) $hero['eyebrow']) . '</span>';
        }
        echo '<h1>' . tw_esc((string) ($hero['title'] ?? ''));
        if ($accent !== '') {
            echo ' <span class="accent">' . tw_esc($accent) . '</span>';
        }
        echo '</h1>';
        if (trim((string) ($hero['lead'] ?? '')) !== '') {
            echo '<p>' . tw_esc((string) $hero['lead']) . '</p>';
        }
        echo '</div></section>';
    }
}

if (!function_exists('tw_heading')) {
    /**
     * Título de sección a partir de los campos eyebrow, title e intro.
     *
     * @param array<string, mixed> $section
     */
    function tw_heading(array $section, bool $lime = false): void
    {
        echo '<div class="section-heading reveal"><div>';
        if (trim((string) ($section['eyebrow'] ?? '')) !== '') {
            echo '<span class="eyebrow' . ($lime ? ' eyebrow--lime' : '') . '">' . tw_esc((string) $section['eyebrow']) . '</span>';
        }
        echo '<h2>' . tw_esc((string) ($section['title'] ?? '')) . '</h2></div>';
        if (trim((string) ($section['intro'] ?? '')) !== '') {
            echo '<p>' . tw_esc((string) $section['intro']) . '</p>';
        }
        echo '</div>';
    }
}

if (!function_exists('tw_contact_form_config')) {
    function tw_contact_form_config(): array
    {
        return SiteStorage::contactFormConfig();
    }
}

if (!function_exists('tw_whatsapp_url')) {
    /**
     * Enlace de WhatsApp con el número de la configuración y un mensaje propio
     * de la página; si no hay número configurado devuelve cadena vacía.
     */
    function tw_whatsapp_url(string $message = ''): string
    {
        $whatsapp = tw_config()['whatsapp'] ?? [];
        $phone = preg_replace('/\D+/', '', (string) ($whatsapp['phone'] ?? '')) ?? '';

        if ($phone === '') {
            return '';
        }

        if ($message === '') {
            $message = (string) ($whatsapp['defaultMessage'] ?? '');
        }

        return 'https://wa.me/' . $phone . '?text=' . rawurlencode($message);
    }
}

if (!function_exists('tw_date_range')) {
    /**
     * "14 de septiembre de 2026" o "5 al 7 de octubre de 2026" según las fechas
     * ISO capturadas en el panel.
     */
    function tw_date_range(string $start, string $end = ''): string
    {
        $months = [
            1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
            'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre',
        ];

        $parse = static function (string $value): ?array {
            $value = trim($value);
            if ($value === '') {
                return null;
            }
            $time = strtotime($value);

            return $time === false ? null : [(int) date('j', $time), (int) date('n', $time), (int) date('Y', $time)];
        };

        $from = $parse($start);
        if ($from === null) {
            return '';
        }

        $to = $parse($end);
        [$d1, $m1, $y1] = $from;

        if ($to === null || $to === $from) {
            return $d1 . ' de ' . $months[$m1] . ' de ' . $y1;
        }

        [$d2, $m2, $y2] = $to;

        if ($y1 === $y2 && $m1 === $m2) {
            return $d1 . ' al ' . $d2 . ' de ' . $months[$m1] . ' de ' . $y1;
        }

        if ($y1 === $y2) {
            return $d1 . ' de ' . $months[$m1] . ' al ' . $d2 . ' de ' . $months[$m2] . ' de ' . $y1;
        }

        return $d1 . ' de ' . $months[$m1] . ' de ' . $y1 . ' al ' . $d2 . ' de ' . $months[$m2] . ' de ' . $y2;
    }
}

if (!function_exists('tw_badge_class')) {
    function tw_badge_class(string $status): string
    {
        return match ($status) {
            'disponible', 'activa' => 'badge--available',
            'en-proceso', 'proxima' => 'badge--process',
            default => 'badge--adopted',
        };
    }
}

if (!function_exists('tw_adoption_card')) {
    function tw_adoption_card(array $item): void
    {
        $id = tw_esc((string) $item['id']);
        $status = (string) ($item['status'] ?? '');
        $label = ContentCollection::statusLabel('adoptions', $item);
        $cover = tw_url(ContentCollection::coverOf($item, '/assets/images/portada.jpg'));
        $meta = array_values(array_filter([
            trim((string) ($item['age'] ?? '')),
            trim((string) ($item['sex'] ?? '')),
            !empty($item['sterilized']) ? 'Esterilizado' : '',
        ]));

        echo '<article class="content-card' . ($status === 'adoptado' ? ' content-card--adopted' : '') . '">';
        echo '<a class="content-card__media" href="' . tw_esc(tw_url('/adopcion/' . $id . '/')) . '">';
        if ($label !== '') {
            echo '<span class="badge badge--floating ' . tw_badge_class($status) . '">' . tw_esc($label) . '</span>';
        }
        echo '<img src="' . tw_esc($cover) . '" alt="' . tw_esc((string) $item['name']) . '" loading="lazy" width="600" height="450">';
        echo '</a>';
        echo '<div class="content-card__body">';
        echo '<h3>' . tw_esc((string) $item['name']) . '</h3>';
        if ($meta !== []) {
            echo '<p class="content-card__meta">';
            foreach ($meta as $entry) {
                echo '<span>' . tw_esc($entry) . '</span>';
            }
            echo '</p>';
        }
        echo '<p>' . tw_esc((string) $item['summary']) . '</p>';
        echo '<a class="content-card__link" href="' . tw_esc(tw_url('/adopcion/' . $id . '/')) . '">Ver ficha</a>';
        echo '</div></article>';
    }
}

if (!function_exists('tw_campaign_card')) {
    function tw_campaign_card(array $item): void
    {
        $href = tw_esc(tw_url(ContentCollection::itemPath('campaigns', $item)));
        $status = (string) ($item['status'] ?? '');
        $label = ContentCollection::statusLabel('campaigns', $item);
        $cover = tw_url(ContentCollection::coverOf($item, '/assets/images/campana-esterilizacion.jpg'));
        $when = tw_date_range((string) ($item['startDate'] ?? ''), (string) ($item['endDate'] ?? ''));
        $kind = ContentCollection::optionLabels('campaigns', 'kind')[(string) ($item['kind'] ?? '')] ?? 'Jornada';
        $place = trim((string) ($item['place'] ?? ''));

        echo '<article class="content-card">';
        echo '<a class="content-card__media" href="' . $href . '">';
        if ($label !== '') {
            echo '<span class="badge badge--floating ' . tw_badge_class($status) . '">' . tw_esc($label) . '</span>';
        }
        echo '<img src="' . tw_esc($cover) . '" alt="' . tw_esc((string) $item['title']) . '" loading="lazy" width="600" height="450">';
        echo '</a>';
        echo '<div class="content-card__body">';
        echo '<p class="content-card__meta"><span>' . tw_esc($kind) . '</span>';
        if ($when !== '') {
            echo '<span>' . tw_esc($when) . '</span>';
        }
        if ($place !== '') {
            echo '<span>' . tw_esc($place) . '</span>';
        }
        echo '</p>';
        echo '<h3>' . tw_esc((string) $item['title']) . '</h3>';
        echo '<p>' . tw_esc((string) $item['summary']) . '</p>';
        echo '<a class="content-card__link" href="' . $href . '">Ver detalle</a>';
        echo '</div></article>';
    }
}

if (!function_exists('tw_story_card')) {
    /**
     * Historia feliz: una ficha de adopción con estado "Adoptado".
     */
    function tw_story_card(array $item): void
    {
        $href = tw_esc(tw_url(ContentCollection::itemPath('adoptions', $item)));
        $cover = tw_url(ContentCollection::coverOf($item, '/assets/images/portada.jpg'));

        echo '<article class="content-card">';
        echo '<a class="content-card__media" href="' . $href . '">';
        echo '<span class="badge badge--floating badge--adopted">Adoptado</span>';
        echo '<img src="' . tw_esc($cover) . '" alt="' . tw_esc((string) $item['name']) . ' con su familia" loading="lazy" width="600" height="450">';
        echo '</a>';
        echo '<div class="content-card__body">';
        echo '<h3>' . tw_esc((string) $item['name']) . '</h3>';
        echo '<p>' . tw_esc((string) $item['summary']) . '</p>';
        echo '<a class="content-card__link" href="' . $href . '">Leer su historia</a>';
        echo '</div></article>';
    }
}

if (!function_exists('tw_clinic_card')) {
    function tw_clinic_card(array $item): void
    {
        $name = (string) $item['name'];
        $services = is_array($item['services'] ?? null) ? $item['services'] : [];
        $phone = trim((string) ($item['phone'] ?? ''));
        $whatsapp = preg_replace('/\D+/', '', (string) ($item['whatsapp'] ?? '')) ?? '';
        $mapUrl = trim((string) ($item['mapUrl'] ?? ''));
        $specialty = trim((string) ($item['specialty'] ?? ''));

        echo '<article class="clinic-card" id="' . tw_esc((string) $item['id']) . '">';
        echo '<header class="clinic-card__head"><h3>' . tw_esc($name) . '</h3>';
        if (!empty($item['emergency'])) {
            echo '<span class="badge badge--process">Urgencias</span>';
        }
        echo '</header>';

        if (trim((string) ($item['summary'] ?? '')) !== '') {
            echo '<p>' . tw_esc((string) $item['summary']) . '</p>';
        }
        if ($specialty !== '') {
            echo '<p class="clinic-card__specialty"><strong>Acude aquí para:</strong> ' . tw_esc($specialty) . '</p>';
        }
        if ($services !== []) {
            echo '<ul class="tag-list" aria-label="Servicios">';
            foreach ($services as $service) {
                echo '<li>' . tw_esc((string) $service) . '</li>';
            }
            echo '</ul>';
        }

        echo '<dl class="clinic-card__facts">';
        foreach (['Dirección' => 'address', 'Horario' => 'hours'] as $label => $field) {
            $value = trim((string) ($item[$field] ?? ''));
            if ($value !== '') {
                echo '<div><dt>' . $label . '</dt><dd>' . tw_esc($value) . '</dd></div>';
            }
        }
        if ($phone !== '') {
            echo '<div><dt>Teléfono</dt><dd><a href="tel:' . tw_esc(preg_replace('/[^\d+]/', '', $phone) ?? '') . '">' . tw_esc($phone) . '</a></dd></div>';
        }
        echo '</dl>';

        if ($whatsapp !== '' || $mapUrl !== '') {
            echo '<div class="button-row">';
            if ($whatsapp !== '') {
                echo '<a class="btn btn--sm" href="https://wa.me/' . tw_esc($whatsapp) . '" target="_blank" rel="noopener" data-track-button="directorio-whatsapp">WhatsApp</a>';
            }
            if ($mapUrl !== '' && preg_match('~^https?://~i', $mapUrl) === 1) {
                echo '<a class="btn btn--sm btn--outline" href="' . tw_esc($mapUrl) . '" target="_blank" rel="noopener" data-track-button="directorio-mapa">Cómo llegar</a>';
            }
            echo '</div>';
        }

        echo '</article>';
    }
}

if (!function_exists('tw_service_options')) {
    /**
     * Opciones del select de servicios del formulario de contacto, tomadas de
     * la configuración editable del panel.
     */
    function tw_service_options(): string
    {
        $services = tw_contact_form_config()['services'] ?? [];
        $html = '';

        foreach ($services as $service) {
            $service = trim((string) $service);
            if ($service === '') {
                continue;
            }
            $html .= '<option value="' . tw_esc($service) . '">' . tw_esc($service) . '</option>';
        }

        return $html;
    }
}
