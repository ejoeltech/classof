<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\EducationRecord;
use App\Models\PlatformSetting;

final class EligibilityService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function matchesForUser(int $userId, ?int $limit = null): array
    {
        $records = EducationRecord::withVerifiedInstitution($userId);

        if (!PlatformSetting::bool('education.suggest_from_unverified')) {
            $records = array_values(array_filter(
                $records,
                static fn (array $record): bool => $record['status'] === 'verified'
            ));
        }

        if ($records === []) {
            return [];
        }

        $institutionIds = array_values(array_unique(array_map(
            static fn (array $record): int => (int) $record['institution_id'],
            $records
        )));

        $placeholders = implode(', ', array_fill(0, count($institutionIds), '?'));
        $sql = 'SELECT g.*,
                    (SELECT COUNT(*) FROM memberships m2 WHERE m2.group_id = g.id AND m2.status = "active") AS member_count
                FROM `alumni_groups` g
                WHERE g.institution_id IN (' . $placeholders . ')
                  AND g.status = "active"
                  AND g.verification_status = "verified"
                  AND g.visibility = "public"
                  AND g.join_policy = "approval"
                  AND g.scope IN ("institution", "class", "association", "faculty", "department")
                  AND NOT EXISTS (
                      SELECT 1 FROM memberships m
                      WHERE m.group_id = g.id AND m.user_id = ?
                  )
                ORDER BY g.specificity DESC, g.created_at ASC';

        $params = $institutionIds;
        $params[] = $userId;
        $groups = Database::fetchAll($sql, $params);

        $byRecord = [];
        foreach ($records as $record) {
            $byRecord[(int) $record['institution_id']][] = $record;
        }

        $matches = [];

        foreach ($groups as $group) {
            $candidates = $byRecord[(int) $group['institution_id']] ?? [];
            $best = null;

            foreach ($candidates as $record) {
                if (!self::recordMatchesGroup($record, $group)) {
                    continue;
                }

                $weight = (int) ($group['specificity'] ?: self::specificity($group));

                if ($best === null || $weight > $best['weight']) {
                    $best = [
                        'weight' => $weight,
                        'record' => $record,
                    ];
                }
            }

            if ($best === null) {
                continue;
            }

            $group['match_weight'] = $best['weight'];
            $group['match_type'] = (string) $group['scope'];
            $group['match_reason'] = self::reason($group, $best['record']);
            $group['education_record_id'] = (int) $best['record']['id'];
            $group['matched_programme'] = $best['record']['programme'] ?? null;
            $matches[] = $group;
        }

        usort($matches, static function (array $a, array $b): int {
            return ((int) $b['match_weight'] <=> (int) $a['match_weight'])
                ?: ((int) $b['member_count'] <=> (int) $a['member_count'])
                ?: strcmp((string) $a['name'], (string) $b['name']);
        });

        if ($limit !== null) {
            $matches = array_slice($matches, 0, max(0, $limit));
        }

        return $matches;
    }

    public static function countForUser(int $userId): int
    {
        return count(self::matchesForUser($userId));
    }

    /**
     * Best education record that matches a group, regardless of suggestion
     * settings. Used by join policy gates and claim snapshots.
     *
     * @return array<string, mixed>|null
     */
    public static function bestMatchForGroup(int $userId, array $group): ?array
    {
        $records = EducationRecord::withVerifiedInstitution($userId);
        $best = null;

        foreach ($records as $record) {
            if ((int) $record['institution_id'] !== (int) $group['institution_id']) {
                continue;
            }

            if (!self::recordMatchesGroup($record, $group)) {
                continue;
            }

            $weight = (int) ($group['specificity'] ?: self::specificity($group));

            if ($best === null || $weight > (int) $best['match_weight']) {
                $record['match_weight'] = $weight;
                $record['match_reason'] = self::reason($group, $record);
                $best = $record;
            }
        }

        return $best;
    }

    /**
     * @param array<string, mixed> $record
     * @param array<string, mixed> $group
     */
    public static function recordMatchesGroup(array $record, array $group): bool
    {
        if (empty($record['institution_id']) || (int) $record['institution_id'] !== (int) $group['institution_id']) {
            return false;
        }

        $scope = (string) $group['scope'];
        $facultyId = !empty($group['faculty_id']) ? (int) $group['faculty_id'] : null;
        $departmentId = !empty($group['department_id']) ? (int) $group['department_id'] : null;

        // Legacy groups carrying free text but no catalogue unit IDs cannot be
        // matched safely.
        if (($scope === 'faculty' || !empty($group['faculty'])) && $facultyId === null) {
            return false;
        }

        if (($scope === 'department' || !empty($group['department'])) && $departmentId === null) {
            return false;
        }

        if ($scope === 'institution' || $scope === 'association') {
            return true;
        }

        if ($scope === 'class') {
            if (empty($record['graduation_year']) || (int) $record['graduation_year'] !== (int) $group['graduation_year']) {
                return false;
            }
        } elseif ($scope === 'faculty') {
            if (empty($record['faculty_id']) || (int) $record['faculty_id'] !== $facultyId) {
                return false;
            }
        } elseif ($scope === 'department') {
            if (empty($record['department_id']) || (int) $record['department_id'] !== $departmentId) {
                return false;
            }
        } elseif ($scope !== 'class') {
            return false;
        }

        if ($facultyId !== null && (empty($record['faculty_id']) || (int) $record['faculty_id'] !== $facultyId)) {
            return false;
        }

        if ($departmentId !== null && (empty($record['department_id']) || (int) $record['department_id'] !== $departmentId)) {
            return false;
        }

        return true;
    }

    private static function reason(array $group, array $record): string
    {
        $reason = (string) ($record['institution_name'] ?? 'Institution');

        if ((string) $group['scope'] === 'class' && !empty($group['graduation_year'])) {
            $reason .= ' · Class of ' . $group['graduation_year'];
        }

        if (!empty($group['faculty'])) {
            $reason .= ' · ' . $group['faculty'];
        }

        if (!empty($group['department'])) {
            $reason .= ' · ' . $group['department'];
        }

        if ((string) $group['scope'] === 'association') {
            $reason .= ' · association';
        }

        if ((string) $group['scope'] === 'institution') {
            $reason .= ' · all years';
        }

        return $reason;
    }

    private static function specificity(array $group): int
    {
        return \App\Models\Group::specificityFor(
            (string) $group['scope'],
            $group['faculty'] ?? null,
            $group['department'] ?? null,
            $group['graduation_year'] ?? null
        );
    }
}
