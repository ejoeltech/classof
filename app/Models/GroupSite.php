<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class GroupSite
{
    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM group_sites WHERE id = ? LIMIT 1', [$id]);
    }

    /** @return array<string, mixed>|null */
    public static function findByGroupId(int $groupId): ?array
    {
        return Database::fetch('SELECT * FROM group_sites WHERE group_id = ? LIMIT 1', [$groupId]);
    }

    /** @return array<string, mixed>|null */
    public static function findBySubdomain(string $subdomain): ?array
    {
        return Database::fetch('SELECT * FROM group_sites WHERE subdomain = ? LIMIT 1', [$subdomain]);
    }

    /**
     * Every group has a site. The first visit creates a published page for a
     * public group and a draft for a private group.
     *
     * @param array<string, mixed> $group
     * @return array<string, mixed>
     */
    public static function forGroup(array $group): array
    {
        $existing = self::findByGroupId((int) $group['id']);

        if ($existing !== null) {
            return $existing;
        }

        return self::createForGroup($group);
    }

    /**
     * @param array<string, mixed> $group
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    public static function createForGroup(array $group, array $overrides = []): array
    {
        $content = self::defaultContent($group);
        $status = ($group['visibility'] ?? 'public') === 'private' ? 'draft' : 'published';
        $now = date('Y-m-d H:i:s');

        $data = array_merge([
            'group_id' => (int) $group['id'],
            'subdomain' => self::uniqueSubdomain(self::suggestSubdomain($group)),
            'status' => $status,
            'headline' => $content['headline'],
            'tagline' => $content['tagline'],
            'about' => $content['about'],
            'accent_color' => $group['cover_color'] ?: '#0B6E4F',
            'show_announcements' => 1,
            'show_events' => 1,
            'show_leaders' => 1,
            'show_stats' => 1,
            'show_join' => 1,
            'published_at' => $status === 'published' ? $now : null,
            'created_at' => $now,
            'updated_at' => $now,
        ], $overrides);

        $data['status'] = in_array(($data['status'] ?? ''), ['draft', 'published'], true) ? $data['status'] : $status;
        $data['published_at'] = $data['status'] === 'published' ? ($data['published_at'] ?? $now) : null;

        $id = Database::insert('group_sites', $data);

        return self::find($id) ?? $data;
    }

    /** @param array<string, mixed> $data */
    public static function update(int $id, array $data): void
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        Database::update('group_sites', $data, ['id' => $id]);
    }

    /** @param array<string, mixed> $group */
    public static function suggestSubdomain(array $group): string
    {
        $base = self::normalizeSubdomain((string) ($group['slug'] ?? $group['name'] ?? 'group'));

        if ($base === '') {
            $base = 'group-' . (int) $group['id'];
        }

        $reserved = (array) config('site.reserved', []);

        if (in_array($base, $reserved, true)) {
            $base .= '-group';
        }

        return self::uniqueSubdomain($base);
    }

    public static function normalizeSubdomain(string $value): string
    {
        $value = strtolower(trim($value));
        $value = (string) preg_replace('/[^a-z0-9-]+/', '-', $value);
        $value = (string) preg_replace('/-+/', '-', $value);
        $value = trim($value, '-');

        return substr($value, 0, 63);
    }

    public static function validSubdomain(string $value): bool
    {
        if ($value === '' || strlen($value) > 63) {
            return false;
        }

        if (preg_match('/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/', $value) !== 1) {
            return false;
        }

        return !in_array($value, (array) config('site.reserved', []), true);
    }

    public static function subdomainTaken(string $subdomain, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM group_sites WHERE subdomain = ?';
        $params = [$subdomain];

        if ($ignoreId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreId;
        }

        return (int) Database::value($sql, $params, 0) > 0;
    }

    /**
     * Websites assigned to a specific editor, regardless of group membership.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function editableForUser(int $userId): array
    {
        return Database::fetchAll(
            'SELECT s.*, g.name AS group_name, g.slug AS group_slug
             FROM group_sites s
             INNER JOIN `alumni_groups` g ON g.id = s.group_id
             WHERE s.editor_user_id = ? AND g.status = "active"
             ORDER BY g.name',
            [$userId]
        );
    }

    public static function uniqueSubdomain(string $base, ?int $ignoreId = null): string
    {
        $base = self::normalizeSubdomain($base);

        if ($base === '') {
            $base = 'group';
        }

        $candidate = $base;
        $counter = 2;

        while (self::subdomainTaken($candidate, $ignoreId)) {
            $suffix = '-' . $counter;
            $candidate = rtrim(substr($base, 0, 63 - strlen($suffix)), '-') . $suffix;
            $counter++;
        }

        return $candidate;
    }

    /**
     * @param array<string, mixed> $group
     * @return array{headline: string, tagline: string, about: string}
     */
    public static function defaultContent(array $group): array
    {
        $institution = trim((string) ($group['institution'] ?? '')) ?: 'your alma mater';
        $descriptor = Group::descriptor($group);
        $scope = (string) ($group['scope'] ?? 'class');

        $tagline = match ($scope) {
            'institution' => 'The alumni home for every ' . $institution . ' graduate.',
            'department' => 'Connecting alumni of ' . (($group['department'] ?? '') ?: 'the department') . ' at ' . $institution . '.',
            'faculty' => 'Connecting alumni of ' . (($group['faculty'] ?? '') ?: 'the faculty') . ' at ' . $institution . '.',
            'association' => 'The alumni association for ' . $institution . ' graduates.',
            'class' => 'Keeping the ' . $descriptor . ' community connected.',
            default => 'The alumni community for ' . $institution . '.',
        };

        $intro = trim((string) ($group['description'] ?? ''));

        if ($intro === '') {
            $intro = 'This is the alumni home for ' . $descriptor . ' at ' . $institution . '.';
        }

        $about = $intro . "\n\n"
            . 'Members use Classof to share news, organise events, support one another through the welfare fund, '
            . 'vote in executive elections and manage group finances. New members join through a verified request '
            . 'reviewed by a group admin.';

        return [
            'headline' => mb_substr((string) ($group['name'] ?? 'Alumni group'), 0, 160),
            'tagline' => mb_substr($tagline, 0, 255),
            'about' => $about,
        ];
    }
}
