<?php
declare(strict_types=1);

require_once __DIR__ . '/SiteStorage.php';

/**
 * CRUD genérico sobre colecciones JSON de contenido (fichas de adopción,
 * jornadas, casos de orientación y clínicas del directorio). Cada tipo declara su esquema de campos; el saneado y el
 * guardado son comunes para que el panel use siempre las mismas rutinas.
 */
class ContentCollection
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function types(): array
    {
        return [
            'adoptions' => [
                'file' => 'adoptions.json',
                'label' => 'Adopción',
                'singular' => 'ficha de adopción',
                'baseUrl' => '/adopcion/',
                'detail' => true,
                'text' => ['name', 'age', 'sex', 'size', 'temperament', 'summary', 'cover', 'ctaLabel', 'ctaUrl'],
                'html' => ['story'],
                'bool' => ['published', 'featured', 'sterilized', 'vaccinated'],
                'list' => ['gallery'],
                'enum' => ['status' => ['disponible', 'en-proceso', 'adoptado']],
                'titleField' => 'name',
            ],
            'campaigns' => [
                'file' => 'campaigns.json',
                'label' => 'Jornadas',
                'singular' => 'jornada',
                'baseUrl' => '/tnr/',
                'detail' => true,
                'text' => ['title', 'startDate', 'endDate', 'schedule', 'place', 'cost', 'summary', 'cover', 'ctaLabel', 'ctaUrl'],
                'html' => ['body'],
                'bool' => ['published', 'featured'],
                'list' => ['gallery'],
                'enum' => [
                    'kind' => ['esterilizacion', 'tnr', 'platica'],
                    'status' => ['activa', 'proxima', 'finalizada'],
                ],
                'titleField' => 'title',
            ],
            'guides' => [
                'file' => 'guides.json',
                'label' => 'Orientación',
                'singular' => 'caso de orientación',
                'baseUrl' => '/asistencia/',
                'detail' => false,
                'text' => ['title', 'summary', 'ctaLabel', 'ctaUrl'],
                'html' => ['body'],
                'bool' => ['published', 'urgent'],
                'list' => [],
                'enum' => [],
                'titleField' => 'title',
            ],
            'clinics' => [
                'file' => 'clinics.json',
                'label' => 'Directorio de clínicas',
                'singular' => 'clínica',
                'baseUrl' => '/directorio/',
                'detail' => false,
                'text' => ['name', 'summary', 'specialty', 'address', 'phone', 'whatsapp', 'hours', 'mapUrl'],
                'html' => [],
                'bool' => ['published', 'emergency'],
                'list' => ['services'],
                'enum' => ['zone' => ['tizayuca', 'zumpango']],
                'titleField' => 'name',
            ],
        ];
    }

    public static function schema(string $type): array
    {
        $types = self::types();

        if (!isset($types[$type])) {
            throw new InvalidArgumentException('Colección desconocida: ' . $type);
        }

        return $types[$type];
    }

    public static function blank(string $type): array
    {
        $schema = self::schema($type);
        $item = ['id' => '', 'order' => 99, 'createdAt' => '', 'updatedAt' => ''];

        foreach ($schema['text'] as $field) {
            $item[$field] = '';
        }
        foreach ($schema['html'] as $field) {
            $item[$field] = '';
        }
        foreach ($schema['bool'] as $field) {
            $item[$field] = false;
        }
        foreach ($schema['list'] as $field) {
            $item[$field] = [];
        }
        foreach ($schema['enum'] as $field => $options) {
            $item[$field] = $options[0];
        }

        $item['published'] = true;

        return $item;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(string $type): array
    {
        $schema = self::schema($type);
        $data = SiteStorage::read($schema['file'], ['items' => []]);
        $items = is_array($data['items'] ?? null) ? $data['items'] : [];

        $items = array_values(array_map(
            static fn(array $item): array => array_replace(self::blank($type), $item),
            array_filter($items, 'is_array')
        ));

        usort($items, static function (array $a, array $b): int {
            return [(int) $a['order'], (string) $a['id']] <=> [(int) $b['order'], (string) $b['id']];
        });

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function published(string $type, int $limit = 0): array
    {
        $items = array_values(array_filter(
            self::all($type),
            static fn(array $item): bool => !empty($item['published'])
        ));

        return $limit > 0 ? array_slice($items, 0, $limit) : $items;
    }

    /**
     * Publicados marcados como destacados; si no hay ninguno, devuelve los
     * primeros publicados para que los teasers del home nunca queden vacíos.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function featured(string $type, int $limit = 3): array
    {
        $published = self::published($type);
        $featured = array_values(array_filter(
            $published,
            static fn(array $item): bool => !empty($item['featured'])
        ));

        $pool = $featured !== [] ? $featured : $published;

        return array_slice($pool, 0, max(1, $limit));
    }

    public static function find(string $type, string $id): ?array
    {
        foreach (self::all($type) as $item) {
            if ((string) $item['id'] === $id) {
                return $item;
            }
        }

        return null;
    }

    public static function slugify(string $value): string
    {
        $value = trim($value);
        // Los acentos del español se resuelven aquí: iconv los translitera
        // distinto según el sistema ("í" => "'i" en macOS/BSD).
        $value = strtr($value, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
        ]);
        $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT', $value);
        if (is_string($transliterated) && $transliterated !== '') {
            $value = $transliterated;
        }

        $value = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $value) ?? '');

        return trim($value, '-');
    }

    /**
     * @return array{success: bool, message: string, id: string}
     */
    public static function save(string $type, array $input): array
    {
        $schema = self::schema($type);
        $item = array_replace(self::blank($type), []);

        foreach ($schema['text'] as $field) {
            $item[$field] = trim((string) ($input[$field] ?? ''));
        }

        foreach ($schema['html'] as $field) {
            $item[$field] = self::sanitizeRichText((string) ($input[$field] ?? ''));
        }

        foreach ($schema['bool'] as $field) {
            $item[$field] = !empty($input[$field]);
        }

        foreach ($schema['list'] as $field) {
            $item[$field] = self::sanitizeList($input[$field] ?? []);
        }

        foreach ($schema['enum'] as $field => $options) {
            $value = (string) ($input[$field] ?? '');
            $item[$field] = in_array($value, $options, true) ? $value : $options[0];
        }

        $titleField = $schema['titleField'];
        if ($item[$titleField] === '') {
            return ['success' => false, 'message' => 'Falta el nombre o título.', 'id' => ''];
        }

        $item['order'] = max(0, (int) ($input['order'] ?? 99));

        $id = self::slugify((string) ($input['id'] ?? ''));
        if ($id === '') {
            $id = self::slugify((string) $item[$titleField]);
        }
        if ($id === '') {
            return ['success' => false, 'message' => 'No se pudo generar un identificador válido.', 'id' => ''];
        }

        $originalId = self::slugify((string) ($input['originalId'] ?? ''));
        $targetId = $originalId !== '' ? $originalId : $id;
        $items = self::all($type);
        $now = date('c');

        $index = null;
        foreach ($items as $i => $existing) {
            if ((string) $existing['id'] === $targetId) {
                $index = $i;
                break;
            }
        }

        foreach ($items as $i => $existing) {
            if ($i !== $index && (string) $existing['id'] === $id) {
                return ['success' => false, 'message' => 'Ya existe un elemento con ese identificador.', 'id' => ''];
            }
        }

        $item['id'] = $id;
        $item['updatedAt'] = $now;

        if ($index === null) {
            $item['createdAt'] = $now;
            $items[] = $item;
        } else {
            $item['createdAt'] = (string) ($items[$index]['createdAt'] ?: $now);
            $items[$index] = $item;
        }

        if (!SiteStorage::write($schema['file'], ['items' => array_values($items)])) {
            return ['success' => false, 'message' => 'No se pudo guardar el archivo de datos.', 'id' => ''];
        }

        return ['success' => true, 'message' => 'Cambios guardados.', 'id' => $id];
    }

    public static function delete(string $type, string $id): bool
    {
        $schema = self::schema($type);
        $items = array_values(array_filter(
            self::all($type),
            static fn(array $item): bool => (string) $item['id'] !== $id
        ));

        return SiteStorage::write($schema['file'], ['items' => $items]);
    }

    public static function togglePublished(string $type, string $id): bool
    {
        $schema = self::schema($type);
        $items = self::all($type);

        foreach ($items as $index => $item) {
            if ((string) $item['id'] === $id) {
                $items[$index]['published'] = empty($item['published']);
                $items[$index]['updatedAt'] = date('c');

                return SiteStorage::write($schema['file'], ['items' => $items]);
            }
        }

        return false;
    }

    /**
     * Primera imagen utilizable de un elemento: portada explícita o la primera
     * de su galería.
     */
    public static function coverOf(array $item, string $fallback = ''): string
    {
        $cover = trim((string) ($item['cover'] ?? ''));
        if ($cover !== '') {
            return $cover;
        }

        $gallery = is_array($item['gallery'] ?? null) ? $item['gallery'] : [];
        foreach ($gallery as $image) {
            $image = trim((string) $image);
            if ($image !== '') {
                return $image;
            }
        }

        return $fallback;
    }

    /**
     * Ruta pública de un elemento: su ficha propia o, en las colecciones que
     * se pintan dentro de una sola página, el ancla dentro de esa página.
     */
    public static function itemPath(string $type, array $item): string
    {
        $schema = self::schema($type);
        $id = (string) ($item['id'] ?? '');

        return !empty($schema['detail'])
            ? $schema['baseUrl'] . $id . '/'
            : $schema['baseUrl'] . '#' . $id;
    }

    /**
     * Etiquetas visibles de los campos de opción, por colección.
     *
     * @return array<string, string>
     */
    public static function optionLabels(string $type, string $field): array
    {
        $labels = [
            'adoptions' => [
                'status' => [
                    'disponible' => 'En adopción',
                    'en-proceso' => 'En proceso',
                    'adoptado' => 'Adoptado',
                ],
            ],
            'campaigns' => [
                'status' => [
                    'activa' => 'Registro abierto',
                    'proxima' => 'Próxima',
                    'finalizada' => 'Finalizada',
                ],
                'kind' => [
                    'esterilizacion' => 'Jornada de esterilización',
                    'tnr' => 'Jornada TNR',
                    'platica' => 'Plática o taller',
                ],
            ],
            'clinics' => [
                'zone' => [
                    'tizayuca' => 'Tizayuca',
                    'zumpango' => 'Zumpango',
                ],
            ],
        ];

        return $labels[$type][$field] ?? [];
    }

    public static function statusLabel(string $type, array $item): string
    {
        $status = (string) ($item['status'] ?? '');

        return self::optionLabels($type, 'status')[$status] ?? '';
    }

    /**
     * @param mixed $value
     * @return array<int, string>
     */
    private static function sanitizeList($value): array
    {
        if (is_string($value)) {
            $value = preg_split('/\R+/', $value) ?: [];
        }

        if (!is_array($value)) {
            return [];
        }

        $clean = [];
        foreach ($value as $entry) {
            $entry = trim((string) $entry);
            if ($entry !== '' && !in_array($entry, $clean, true)) {
                $clean[] = $entry;
            }
        }

        return $clean;
    }

    /**
     * Texto largo editable desde el panel: se permite un subconjunto de HTML y
     * se eliminan scripts, estilos y atributos de evento.
     */
    private static function sanitizeRichText(string $html): string
    {
        $html = preg_replace('#<(script|style|iframe|object|embed)\b[^>]*>.*?</\1>#is', '', $html) ?? '';
        $html = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';
        $html = preg_replace('/(href|src)\s*=\s*("|\')\s*javascript:[^"\']*\2/i', '$1="#"', $html) ?? '';

        $allowed = '<p><br><strong><em><b><i><ul><ol><li><a><h2><h3><h4><blockquote><hr>';

        return trim(strip_tags($html, $allowed));
    }
}
