<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Department;
use App\Models\Faculty;
use App\Models\Institution;

/**
 * Unit scaffolding for Release 2.
 *
 * Faculties and departments are generated from a common Nigerian institutional
 * template the first time an institution is used. The template is deliberately
 * marked as such in the source column; it is a scaffold for matching, not a
 * claim of per-institution accreditation.
 */
final class UnitCatalogueService
{
    /** @var array<string, array<int, array{0: string, 1: array<int, string>}>> */
    private const TEMPLATES = [
        'university' => [
            ['Faculty of Science', ['Computer Science', 'Mathematics', 'Physics', 'Chemistry', 'Biological Sciences', 'Microbiology', 'Biochemistry', 'Statistics']],
            ['Faculty of Engineering', ['Electrical/Electronic Engineering', 'Mechanical Engineering', 'Civil Engineering', 'Chemical Engineering', 'Computer Engineering', 'Petroleum Engineering']],
            ['Faculty of Arts', ['English', 'History and International Studies', 'Linguistics', 'Philosophy']],
            ['Faculty of Social Sciences', ['Economics', 'Political Science', 'Sociology', 'Psychology', 'Mass Communication']],
            ['Faculty of Management Sciences', ['Accounting', 'Business Administration', 'Banking and Finance', 'Marketing']],
            ['Faculty of Law', ['Law']],
            ['Faculty of Medicine', ['Medicine and Surgery', 'Nursing', 'Pharmacy']],
            ['Faculty of Education', ['Education', 'Guidance and Counselling']],
            ['Faculty of Environmental Sciences', ['Architecture', 'Building', 'Quantity Surveying', 'Urban and Regional Planning']],
        ],
        'polytechnic' => [
            ['School of Engineering', ['Electrical/Electronic Engineering', 'Mechanical Engineering', 'Civil Engineering', 'Computer Engineering']],
            ['School of Science', ['Computer Science', 'Mathematics', 'Statistics', 'Science Laboratory Technology']],
            ['School of Business and Management', ['Accountancy', 'Business Administration', 'Marketing', 'Public Administration']],
            ['School of Environmental Studies', ['Architecture', 'Building Technology', 'Quantity Surveying', 'Urban and Regional Planning']],
        ],
        'college' => [
            ['School of Education', ['Education', 'Guidance and Counselling', 'Primary Education']],
            ['School of Arts and Social Sciences', ['English', 'History and International Studies', 'Social Studies']],
            ['School of Science', ['Computer Science', 'Mathematics', 'Integrated Science']],
        ],
    ];

    public static function ensureForInstitution(int $institutionId): void
    {
        $institution = Institution::find($institutionId);

        if ($institution === null || $institution['status'] !== 'verified') {
            return;
        }

        if (Faculty::forInstitution($institutionId) !== []) {
            return;
        }

        $type = (string) $institution['type'];
        $template = self::TEMPLATES[$type] ?? null;

        if ($template === null) {
            return;
        }

        foreach ($template as [$facultyName, $departments]) {
            $facultyId = Faculty::create($institutionId, $facultyName, 'common template');

            foreach ($departments as $departmentName) {
                Department::create($facultyId, $departmentName, 'common template');
            }
        }
    }
}
