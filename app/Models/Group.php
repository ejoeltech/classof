<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Group
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

    /**
     * The alumni affiliation a group represents. This is independent of the
     * institution type, so a university can have institution-wide, class,
     * faculty, department and association groups side by side.
     */
    public const SCOPES = [
        'institution' => 'General institution alumni (all years)',
        'class' => 'Class / Set alumni (specific year)',
        'department' => 'Department alumni',
        'faculty' => 'Faculty alumni',
        'association' => 'Association alumni',
        'other' => 'Other alumni group',
    ];

    public const SCOPE_SHORT = [
        'institution' => 'Institution-wide',
        'class' => 'Class / Set',
        'department' => 'Department',
        'faculty' => 'Faculty',
        'association' => 'Association',
        'other' => 'Other',
    ];

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM `alumni_groups` WHERE id = ? LIMIT 1', [$id]);
    }

    /** @return array<string, mixed>|null */
    public static function findBySlug(string $slug): ?array
    {
        return Database::fetch('SELECT * FROM `alumni_groups` WHERE slug = ? LIMIT 1', [$slug]);
    }

    /** @param array<string, mixed> $data */
    public static function create(array $data): int
    {
        return Database::insert('alumni_groups', [
            'name' => $data['name'],
            'slug' => self::uniqueSlug((string) $data['name']),
            'type' => $data['type'],
            'scope' => $data['scope'] ?? 'class',
            'institution_id' => $data['institution_id'] ?? null,
            'institution' => $data['institution'] ?? null,
            'faculty_id' => $data['faculty_id'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'faculty' => $data['faculty'] ?? null,
            'department' => $data['department'] ?? null,
            'graduation_year' => $data['graduation_year'] ?? null,
            'country' => $data['country'] ?? null,
            'currency' => $data['currency'] ?? config('payments.currency', 'NGN'),
            'description' => $data['description'] ?? null,
            'visibility' => $data['visibility'] ?? 'public',
            'join_policy' => $data['join_policy'] ?? 'approval',
            'verification_status' => $data['verification_status'] ?? 'verified',
            'specificity' => self::specificityFor(
                (string) ($data['scope'] ?? 'class'),
                $data['faculty'] ?? null,
                $data['department'] ?? null,
                $data['graduation_year'] ?? null
            ),
            'status' => 'active',
            'created_by' => $data['created_by'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function specificityFor(string $scope, ?string $faculty, ?string $department, mixed $year): int
    {
        $base = match ($scope) {
            'department' => 55,
            'class' => 45,
            'faculty' => 40,
            'institution' => 20,
            'association' => 10,
            default => 0,
        };

        if ($scope === 'class') {
            if (!empty($department)) {
                $base += 18;
            } elseif (!empty($faculty)) {
                $base += 12;
            }

            if (!empty($year)) {
                $base += 5;
            }
        }

        return $base;
    }

    public static function uniqueSlug(string $name): string
    {
        $base = slugify($name);
        $slug = $base;
        $counter = 1;

        while (Database::value('SELECT COUNT(*) FROM `alumni_groups` WHERE slug = ?', [$slug], 0) > 0) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /** @return array<int, array<string, mixed>> */
    public static function forUser(int $userId, ?string $status = null): array
    {
        $sql = 'SELECT g.*, m.role, m.position_title, m.status AS membership_status, m.membership_no,
                       (SELECT COUNT(*) FROM memberships mm WHERE mm.group_id = g.id AND mm.status = "active") AS member_count
                FROM `alumni_groups` g
                INNER JOIN memberships m ON m.group_id = g.id
                WHERE m.user_id = ? AND g.status = "active"';
        $params = [$userId];

        if ($status !== null) {
            $sql .= ' AND m.status = ?';
            $params[] = $status;
        }

        $sql .= ' ORDER BY g.name';

        return Database::fetchAll($sql, $params);
    }

    /** @return array<int, array<string, mixed>> */
    public static function explore(string $search = '', ?string $type = null, ?string $scope = null, int $limit = 24): array
    {
        $sql = 'SELECT g.*,
                    (SELECT COUNT(*) FROM memberships m WHERE m.group_id = g.id AND m.status = "active") AS member_count
                FROM `alumni_groups` g
                WHERE g.status = "active" AND g.visibility = "public" AND g.verification_status = "verified"';
        $params = [];

        if ($search !== '') {
            $sql .= ' AND (g.name LIKE ? OR g.institution LIKE ? OR g.faculty LIKE ? OR g.department LIKE ?)';
            $like = '%' . $search . '%';
            array_push($params, $like, $like, $like, $like);
        }

        if ($type !== null && isset(self::TYPES[$type])) {
            $sql .= ' AND g.type = ?';
            $params[] = $type;
        }

        if ($scope !== null && isset(self::SCOPES[$scope])) {
            $sql .= ' AND g.scope = ?';
            $params[] = $scope;
        }

        $sql .= ' ORDER BY member_count DESC, g.created_at DESC LIMIT ' . $limit;

        return Database::fetchAll($sql, $params);
    }

    /** @return array<string, int|float> */
    public static function stats(int $groupId): array
    {
        return [
            'members' => (int) Database::value(
                'SELECT COUNT(*) FROM memberships WHERE group_id = ? AND status = "active"',
                [$groupId],
                0
            ),
            'pending' => (int) Database::value(
                'SELECT COUNT(*) FROM memberships WHERE group_id = ? AND status = "pending"',
                [$groupId],
                0
            ),
            'events' => (int) Database::value(
                'SELECT COUNT(*) FROM events WHERE group_id = ? AND starts_at >= NOW() AND status = "published"',
                [$groupId],
                0
            ),
            'announcements' => (int) Database::value(
                'SELECT COUNT(*) FROM announcements WHERE group_id = ?',
                [$groupId],
                0
            ),
        ];
    }

    public static function treasury(int $groupId): array
    {
        $dues = (float) Database::value(
            'SELECT COALESCE(SUM(amount), 0) FROM payments WHERE group_id = ? AND purpose = "dues" AND status = "success"',
            [$groupId],
            0
        );

        $welfare = (float) Database::value(
            'SELECT COALESCE(SUM(wc.amount), 0) FROM welfare_contributions wc
             INNER JOIN welfare_cases w ON w.id = wc.welfare_case_id
             WHERE w.group_id = ?',
            [$groupId],
            0
        );

        $events = (float) Database::value(
            'SELECT COALESCE(SUM(amount), 0) FROM payments WHERE group_id = ? AND purpose = "event" AND status = "success"',
            [$groupId],
            0
        );

        $targets = (float) Database::value(
            'SELECT COALESCE(SUM(target_amount), 0) FROM welfare_cases WHERE group_id = ? AND status = "open"',
            [$groupId],
            0
        );

        return [
            'dues' => $dues,
            'welfare' => $welfare,
            'events' => $events,
            'total' => $dues + $welfare + $events,
            'welfare_targets' => $targets,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function paginate(int $page = 1, int $perPage = 20, string $search = ''): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $sql = 'SELECT g.*, (SELECT COUNT(*) FROM memberships m WHERE m.group_id = g.id AND m.status = "active") AS member_count FROM `alumni_groups` g';
        $params = [];

        if ($search !== '') {
            $sql .= ' WHERE g.name LIKE ? OR g.institution LIKE ?';
            $params = ['%' . $search . '%', '%' . $search . '%'];
        }

        $sql .= ' ORDER BY g.created_at DESC LIMIT ' . $perPage . ' OFFSET ' . $offset;

        return Database::fetchAll($sql, $params);
    }

    public static function count(string $search = ''): int
    {
        if ($search === '') {
            return (int) Database::value('SELECT COUNT(*) FROM `alumni_groups`', [], 0);
        }

        return (int) Database::value(
            'SELECT COUNT(*) FROM `alumni_groups` WHERE name LIKE ? OR institution LIKE ?',
            ['%' . $search . '%', '%' . $search . '%'],
            0
        );
    }

    public static function typeLabel(string $type): string
    {
        return self::TYPES[$type] ?? ucfirst($type);
    }

    /**
     * The group's own bank account, falling back to the platform default.
     *
     * @param array<string, mixed>|null $group
     * @return array{bank_name: string, account_number: string, account_holder: string}
     */
    public static function bankDetails(?array $group): array
    {
        $fallback = (array) config('payments.manual', []);

        return [
            'bank_name' => trim((string) ($group['bank_name'] ?? '')) ?: (string) ($fallback['bank_name'] ?? ''),
            'account_number' => trim((string) ($group['bank_account_number'] ?? '')) ?: (string) ($fallback['account_number'] ?? ''),
            'account_holder' => trim((string) ($group['bank_account_holder'] ?? '')) ?: (string) ($fallback['account_holder'] ?? ''),
        ];
    }

    public static function scopeLabel(?string $scope): string
    {
        return self::SCOPE_SHORT[$scope ?? 'class'] ?? 'Alumni group';
    }

    /**
     * Human-readable affiliation line, e.g. "Class of 2010" or
     * "Computer Science · Faculty of Science".
     */
    public static function descriptor(array $group): string
    {
        $scope = $group['scope'] ?? 'class';

        return match ($scope) {
            'institution' => 'All graduating years',
            'class' => !empty($group['graduation_year']) ? 'Class of ' . $group['graduation_year'] : 'Class / Set',
            'department' => trim(
                (string) ($group['department'] ?? '')
                . (!empty($group['faculty']) ? ' · ' . $group['faculty'] : '')
            ) ?: 'Department',
            'faculty' => (string) ($group['faculty'] ?? '') ?: 'Faculty',
            'association' => 'Alumni association',
            default => (string) ($group['institution'] ?? '') ?: 'Alumni group',
        };
    }

    /** @return array<int, string> */
    public static function currencies(): array
    {
        return ['NGN' => 'Nigerian Naira (₦)', 'USD' => 'US Dollar ($)', 'GBP' => 'British Pound (£)', 'EUR' => 'Euro (€)', 'GHS' => 'Ghanaian Cedi (GH₵)', 'KES' => 'Kenyan Shilling (KSh)', 'ZAR' => 'South African Rand (R)', 'CAD' => 'Canadian Dollar (C$)', 'AUD' => 'Australian Dollar (A$)'];
    }
}
