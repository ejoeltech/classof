<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Institution
{
    public const TYPES = [
        'primary' => 'Primary school',
        'secondary' => 'Secondary school',
        'university' => 'University',
        'polytechnic' => 'Polytechnic',
        'college' => 'College of education',
        'vocational' => 'Vocational / technical',
        'other' => 'Other institution',
    ];

    public const OWNERSHIPS = [
        'federal' => 'Federal',
        'state' => 'State',
        'private' => 'Private',
        'other' => 'Other',
    ];

    public static function normalizeKey(string $name): string
    {
        $key = mb_strtolower(trim($name));
        $key = str_replace(['&', '’', "'"], [' and ', '', ''], $key);
        $key = preg_replace('/[^a-z0-9]+/u', ' ', $key) ?? $key;
        $key = trim(preg_replace('/\s+/', ' ', $key) ?? $key);

        if (str_starts_with($key, 'the ')) {
            $key = substr($key, 4);
        }

        return $key;
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM institutions WHERE id = ? LIMIT 1', [$id]);
    }

    /** @return array<string, mixed>|null */
    public static function findByKey(string $key): ?array
    {
        return Database::fetch('SELECT * FROM institutions WHERE name_key = ? LIMIT 1', [self::normalizeKey($key)]);
    }

    /** @return array<int, array<string, mixed>> */
    public static function verified(?string $type = null): array
    {
        $sql = 'SELECT * FROM institutions WHERE status = "verified"';
        $params = [];

        if ($type !== null && isset(self::TYPES[$type])) {
            $sql .= ' AND type = ?';
            $params[] = $type;
        }

        $sql .= ' ORDER BY name';

        return Database::fetchAll($sql, $params);
    }

    /**
     * Verified institutions plus the viewer's own pending submissions.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function selectable(int $userId): array
    {
        return Database::fetchAll(
            'SELECT * FROM institutions
             WHERE status = "verified" OR (status = "pending" AND created_by = ?)
             ORDER BY status, name',
            [$userId]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public static function pending(): array
    {
        return Database::fetchAll(
            'SELECT i.*, u.name AS submitter_name, u.email AS submitter_email
             FROM institutions i
             LEFT JOIN users u ON u.id = i.created_by
             WHERE i.status = "pending"
             ORDER BY i.created_at ASC'
        );
    }

    /** @return array<int, array<string, mixed>> */
    public static function search(string $term, int $limit = 30): array
    {
        $like = '%' . $term . '%';

        return Database::fetchAll(
            'SELECT * FROM institutions
             WHERE status = "verified" AND (name LIKE ? OR name_key LIKE ? OR state LIKE ?)
             ORDER BY name
             LIMIT ' . $limit,
            [$like, '%' . self::normalizeKey($term) . '%', $like]
        );
    }

    /** @param array<string, mixed> $data */
    public static function create(array $data): int
    {
        $name = trim((string) $data['name']);
        $nameKey = self::normalizeKey($name);
        $existing = self::findByKey($nameKey);

        if ($existing !== null) {
            return (int) $existing['id'];
        }

        $id = Database::insert('institutions', [
            'name' => $name,
            'name_key' => $nameKey,
            'slug' => self::uniqueSlug($name),
            'type' => $data['type'] ?? 'university',
            'ownership' => $data['ownership'] ?? 'other',
            'state' => $data['state'] ?? null,
            'country_code' => strtoupper((string) ($data['country_code'] ?? 'NG')),
            'status' => $data['status'] ?? 'pending',
            'source' => $data['source'] ?? null,
            'source_url' => $data['source_url'] ?? null,
            'dataset_version' => $data['dataset_version'] ?? null,
            'created_by' => $data['created_by'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        foreach ((array) ($data['aliases'] ?? []) as $alias) {
            self::addAlias($id, (string) $alias);
        }

        return $id;
    }

    public static function uniqueSlug(string $name): string
    {
        $base = slugify($name);
        $slug = $base;
        $counter = 1;

        while (Database::value('SELECT COUNT(*) FROM institutions WHERE slug = ?', [$slug], 0) > 0) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public static function addAlias(int $institutionId, string $alias): void
    {
        $key = self::normalizeKey($alias);

        if ($key === '') {
            return;
        }

        if ((int) Database::value('SELECT COUNT(*) FROM institution_aliases WHERE alias_key = ?', [$key], 0) > 0) {
            return;
        }

        Database::insert('institution_aliases', [
            'institution_id' => $institutionId,
            'alias' => trim($alias),
            'alias_key' => $key,
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public static function aliases(int $institutionId): array
    {
        return Database::fetchAll(
            'SELECT * FROM institution_aliases WHERE institution_id = ? ORDER BY alias',
            [$institutionId]
        );
    }

    public static function approve(int $id, int $reviewerId): void
    {
        Database::update('institutions', [
            'status' => 'verified',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => date('Y-m-d H:i:s'),
            'rejection_reason' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);
    }

    public static function reject(int $id, int $reviewerId, ?string $reason = null): void
    {
        Database::update('institutions', [
            'status' => 'rejected',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => date('Y-m-d H:i:s'),
            'rejection_reason' => $reason,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);
    }

    public static function label(array $institution): string
    {
        $parts = [
            self::TYPES[$institution['type']] ?? ucfirst((string) $institution['type']),
            self::OWNERSHIPS[$institution['ownership']] ?? ucfirst((string) $institution['ownership']),
        ];

        if (!empty($institution['state'])) {
            $parts[] = $institution['state'];
        }

        return implode(' · ', $parts);
    }
}
