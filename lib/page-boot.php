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
        echo '<a class="content-card__media" href="' . tw_esc(tw_url('/adopciones/' . $id . '/')) . '">';
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
        echo '<a class="content-card__link" href="' . tw_esc(tw_url('/adopciones/' . $id . '/')) . '">Ver ficha</a>';
        echo '</div></article>';
    }
}

if (!function_exists('tw_campaign_card')) {
    function tw_campaign_card(array $item): void
    {
        $id = tw_esc((string) $item['id']);
        $status = (string) ($item['status'] ?? '');
        $label = ContentCollection::statusLabel('campaigns', $item);
        $cover = tw_url(ContentCollection::coverOf($item, '/assets/images/portada.jpg'));
        $when = tw_date_range((string) ($item['startDate'] ?? ''), (string) ($item['endDate'] ?? ''));
        $kind = ($item['kind'] ?? '') === 'evento' ? 'Evento' : 'Campaña';

        echo '<article class="content-card">';
        echo '<a class="content-card__media" href="' . tw_esc(tw_url('/campanas/' . $id . '/')) . '">';
        if ($label !== '') {
            echo '<span class="badge badge--floating ' . tw_badge_class($status) . '">' . tw_esc($label) . '</span>';
        }
        echo '<img src="' . tw_esc($cover) . '" alt="' . tw_esc((string) $item['title']) . '" loading="lazy" width="600" height="450">';
        echo '</a>';
        echo '<div class="content-card__body">';
        echo '<p class="content-card__meta"><span>' . $kind . '</span>';
        if ($when !== '') {
            echo '<span>' . tw_esc($when) . '</span>';
        }
        echo '</p>';
        echo '<h3>' . tw_esc((string) $item['title']) . '</h3>';
        echo '<p>' . tw_esc((string) $item['summary']) . '</p>';
        echo '<a class="content-card__link" href="' . tw_esc(tw_url('/campanas/' . $id . '/')) . '">Ver detalle</a>';
        echo '</div></article>';
    }
}

if (!function_exists('tw_album_card')) {
    function tw_album_card(array $item): void
    {
        $id = tw_esc((string) $item['id']);
        $cover = tw_url(ContentCollection::coverOf($item, '/assets/images/portada.jpg'));
        $count = count(is_array($item['gallery'] ?? null) ? $item['gallery'] : []);

        echo '<article class="content-card">';
        echo '<a class="content-card__media" href="' . tw_esc(tw_url('/galeria/' . $id . '/')) . '">';
        echo '<img src="' . tw_esc($cover) . '" alt="' . tw_esc((string) $item['title']) . '" loading="lazy" width="600" height="450">';
        if ($count > 0) {
            echo '<span class="album-count">' . $count . ' foto' . ($count === 1 ? '' : 's') . '</span>';
        }
        echo '</a>';
        echo '<div class="content-card__body">';
        echo '<h3>' . tw_esc((string) $item['title']) . '</h3>';
        echo '<p>' . tw_esc((string) $item['summary']) . '</p>';
        echo '<a class="content-card__link" href="' . tw_esc(tw_url('/galeria/' . $id . '/')) . '">Abrir álbum</a>';
        echo '</div></article>';
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
