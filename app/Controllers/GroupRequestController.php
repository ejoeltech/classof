<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Group;
use App\Models\GroupRequest;
use App\Models\Institution;

final class GroupRequestController extends Controller
{
    public function index(): void
    {
        $user = $this->currentUser();

        $this->view('profile/group-requests', [
            'user' => $user,
            'requests' => GroupRequest::forUser((int) $user['id']),
            'institutions' => Institution::verified(),
            'types' => Group::TYPES,
            'scopes' => Group::SCOPES,
        ]);
    }

    public function store(): void
    {
        $user = $this->currentUser();

        $data = $this->validate([
            'institution_id' => 'required|integer',
            'name' => 'required|min:3|max:160',
            'scope' => 'required|in:institution,class,department,faculty,association,other',
            'type' => 'required|in:primary,secondary,university,polytechnic,college,vocational,other',
            'faculty_id' => 'integer',
            'department_id' => 'integer',
            'graduation_year' => 'integer',
            'description' => 'max:3000',
        ]);

        $institution = Institution::find((int) $data['institution_id']);

        if ($institution === null || $institution['status'] !== 'verified') {
            $this->fail(['institution_id' => 'Choose a verified institution from the catalogue.']);
        }

        $scope = (string) $data['scope'];
        $year = (string) ($data['graduation_year'] ?? '');
        $facultyId = (int) ($data['faculty_id'] ?? 0) ?: null;
        $departmentId = (int) ($data['department_id'] ?? 0) ?: null;
        $facultyName = null;
        $departmentName = null;

        if ($facultyId !== null) {
            $faculty = Faculty::find($facultyId);

            if ($faculty === null || (int) $faculty['institution_id'] !== (int) $institution['id']) {
                $this->fail(['faculty_id' => 'Choose a faculty that belongs to the selected institution.']);
            }

            $facultyName = $faculty['name'];
        }

        if ($departmentId !== null) {
            $department = Department::find($departmentId);

            if ($department === null || (int) $department['institution_id'] !== (int) $institution['id']) {
                $this->fail(['department_id' => 'Choose a department that belongs to the selected institution.']);
            }

            if ($facultyId !== null && (int) $department['faculty_id'] !== $facultyId) {
                $this->fail(['department_id' => 'That department does not belong to the selected faculty.']);
            }

            $departmentName = $department['name'];

            if ($facultyId === null) {
                $facultyId = (int) $department['faculty_id'];
                $facultyName = $department['faculty_name'];
            }
        }

        if ($scope === 'class' && $year === '') {
            $this->fail(['graduation_year' => 'A class / set request needs a graduation year.']);
        }

        if ($year !== '' && ((int) $year < 1900 || (int) $year > (int) date('Y') + 10)) {
            $this->fail(['graduation_year' => 'Enter a realistic graduation year.']);
        }

        if ($scope === 'faculty' && $facultyId === null) {
            $this->fail(['faculty_id' => 'A faculty request needs the faculty.']);
        }

        if ($scope === 'department' && $departmentId === null) {
            $this->fail(['department_id' => 'A department request needs the department.']);
        }

        $graduationYear = $year !== '' ? (int) $year : null;

        $existing = Database::value(
            'SELECT COUNT(*) FROM `alumni_groups`
             WHERE institution_id = ? AND scope = ? AND (faculty_id <=> ?) AND (department_id <=> ?) AND (graduation_year <=> ?)',
            [(int) $institution['id'], $scope, $facultyId, $departmentId, $graduationYear],
            0
        );

        if ((int) $existing > 0) {
            $this->flashError('A matching group already exists. Use Explore to request to join it.');
            $this->redirect('explore?for=you');
        }

        if (GroupRequest::duplicateExists((int) $institution['id'], $scope, $graduationYear, $facultyId, $departmentId)) {
            $this->flashError('You already have a matching group request awaiting review.');
            $this->redirect('profile/group-requests');
        }

        $id = GroupRequest::create([
            'user_id' => (int) $user['id'],
            'institution_id' => (int) $institution['id'],
            'name' => $data['name'],
            'scope' => $scope,
            'type' => $data['type'],
            'faculty' => $facultyName,
            'department' => $departmentName,
            'faculty_id' => $facultyId,
            'department_id' => $departmentId,
            'graduation_year' => $graduationYear,
            'description' => $data['description'] ?: null,
        ]);

        $this->audit(null, 'group.requested', 'Requested group "' . $data['name'] . '"', 'group_request', $id);
        $this->flashSuccess('Group request submitted. A platform admin will verify the affiliation before it appears in Explore.');
        $this->redirect('profile/group-requests');
    }

    public function redirectCreate(): void
    {
        $this->flashSuccess('New groups are requested and verified. Use the form on this page, or request to join an existing group.');
        $this->redirect('profile/group-requests');
    }
}
