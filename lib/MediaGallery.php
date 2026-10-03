<?php
declare(strict_types=1);

require_once __DIR__ . '/SiteStorage.php';

class MediaGallery
{
    private const METADATA_FILE = 'media-gallery.json';

    public static function galleryDir(): string
    {
        return dirname(__DIR__) . '/assets/images/gallery';
    }

    public static function getItems(): array
    {
        $data = SiteStorage::read(self::METADATA_FILE, ['items' => []]);
        if (!isset($data['items']) || !is_array($data['items'])) {
            $data['items'] = [];
        }

        usort($data['items'], fn(array $a, array $b): int => strcmp(
            (string) ($b['created_at'] ?? ''),
            (string) ($a['created_at'] ?? '')
        ));

        return $data['items'];
    }

    public static function saveItems(array $items): bool
    {
        return SiteStorage::write(self::METADATA_FILE, ['items' => array_values($items)]);
    }

    public static function getById(string $id): ?array
    {
        foreach (self::getItems() as $item) {
            if (($item['id'] ?? '') === $id) {
                return $item;
            }
        }

        return null;
    }

    public static function addUploadedFile(string $webPath, string $filename, array $meta = []): ?array
    {
        $item = [
            'id' => 'img-' . bin2hex(random_bytes(6)),
            'filename' => $filename,
            'path' => $webPath,
            'title' => trim((string) ($meta['title'] ?? '')),
            'alt' => trim((string) ($meta['alt'] ?? '')),
            'created_at' => date('c'),
        ];

        $items = self::getItems();
        array_unshift($items, $item);

        return self::saveItems($items) ? $item : null;
    }

    public static function update(string $id, array $fields): bool
    {
        $items = self::getItems();
        $updated = false;

        foreach ($items as &$item) {
            if (($item['id'] ?? '') !== $id) {
                continue;
            }

            if (array_key_exists('title', $fields)) {
                $item['title'] = trim((string) $fields['title']);
            }
            if (array_key_exists('alt', $fields)) {
                $item['alt'] = trim((string) $fields['alt']);
            }

            $updated = true;
            break;
        }
        unset($item);

        return $updated && self::saveItems($items);
    }

    public static function delete(string $id): bool
    {
        $items = self::getItems();
        $target = null;

        foreach ($items as $item) {
            if (($item['id'] ?? '') === $id) {
                $target = $item;
                break;
            }
        }

        if (!$target) {
            return false;
        }

        $path = self::absolutePath((string) ($target['path'] ?? ''));
        if ($path && is_file($path)) {
            @unlink($path);
        }

        $items = array_values(array_filter($items, fn(array $item): bool => ($item['id'] ?? '') !== $id));
        return self::saveItems($items);
    }

    public static function absolutePath(string $webPath): ?string
    {
        $webPath = trim($webPath);
        if ($webPath === '' || !str_starts_with($webPath, '/assets/images/gallery/')) {
            return null;
        }

        $root = dirname(__DIR__);
        $full = $root . $webPath;

        return is_file($full) ? $full : null;
    }

    public static function publicUrl(string $webPath): string
    {
        $host = $_SERVER['HTTP_HOST'] ?? 'lacasadelosgatos.org';
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'https';

        return $scheme . '://' . $host . $webPath;
    }
}
