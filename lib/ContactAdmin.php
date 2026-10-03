<?php
declare(strict_types=1);

require_once __DIR__ . '/SiteStorage.php';

class ContactAdmin
{
    private const JSON_FILE = 'contact-submissions.json';
    private const XLSX_FILE = 'contact-submissions.xlsx';

    public static function getAll(): array
    {
        $records = SiteStorage::read(self::JSON_FILE, []);
        if (!is_array($records)) {
            return [];
        }

        $changed = false;
        foreach ($records as &$record) {
            if (empty($record['_id'])) {
                $record['_id'] = bin2hex(random_bytes(8));
                $changed = true;
            }
        }
        unset($record);

        if ($changed) {
            self::saveAll($records);
        }

        return $records;
    }

    public static function getById(string $id): ?array
    {
        foreach (self::getAll() as $record) {
            if (($record['_id'] ?? '') === $id) {
                return $record;
            }
        }

        return null;
    }

    public static function saveAll(array $records): bool
    {
        if (!SiteStorage::write(self::JSON_FILE, array_values($records))) {
            return false;
        }

        self::regenerateXlsx($records);
        return true;
    }

    public static function update(string $id, array $fields): bool
    {
        $records = self::getAll();
        $updated = false;

        foreach ($records as &$record) {
            if (($record['_id'] ?? '') !== $id) {
                continue;
            }

            foreach (['nombre', 'email', 'telefono', 'servicio', 'mensaje', 'estado', 'error'] as $key) {
                if (array_key_exists($key, $fields)) {
                    $record[$key] = trim((string) $fields[$key]);
                }
            }

            if (!in_array($record['estado'] ?? '', ['exitoso', 'error'], true)) {
                $record['estado'] = 'exitoso';
            }

            $updated = true;
            break;
        }
        unset($record);

        return $updated && self::saveAll($records);
    }

    public static function delete(string $id): bool
    {
        $records = array_values(array_filter(
            self::getAll(),
            fn(array $record): bool => ($record['_id'] ?? '') !== $id
        ));

        if (count($records) === count(self::getAll())) {
            return false;
        }

        return self::saveAll($records);
    }

    public static function clearAll(): bool
    {
        return self::saveAll([]);
    }

    public static function regenerateXlsx(?array $records = null): void
    {
        require_once dirname(__DIR__) . '/api/lib/SimpleXLSXGen.php';

        $records ??= self::getAll();

        $rows = [[
            'Fecha (MX)',
            'Estado',
            'Detalle del error',
            'Nombre',
            'Correo',
            'Teléfono',
            'Servicio',
            'Mensaje',
            'IP',
            'Navegador',
        ]];

        foreach ($records as $record) {
            $rows[] = [
                $record['fecha_mx'] ?? '',
                $record['estado'] ?? '',
                $record['error'] ?? '',
                $record['nombre'] ?? '',
                $record['email'] ?? '',
                $record['telefono'] ?? '',
                $record['servicio'] ?? '',
                $record['mensaje'] ?? '',
                $record['ip'] ?? '',
                $record['user_agent'] ?? '',
            ];
        }

        \Shuchkin\SimpleXLSXGen::fromArray($rows)->saveAs(SiteStorage::path(self::XLSX_FILE));
    }

    public static function xlsxPath(): string
    {
        return SiteStorage::path(self::XLSX_FILE);
    }
}
