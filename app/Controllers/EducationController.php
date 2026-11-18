<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\Department;
use App\Models\EducationRecord;
use App\Models\Faculty;
use App\Models\Institution;
use App\Models\Programme;

final class EducationController extends Controller
{
    public function index(): void
    {
        $user = $this->currentUser();

        $this->view('profile/education', [
            'user' => $user,
            'records' => EducationRecord::forUser((int) $user['id']),
            'institutions' => Institution::selectable((int) $user['id']),
            'programmes' => Programme::all(),
            'pendingInstitutions' => Database::fetchAll(
                'SELECT * FROM institutions WHERE created_by = ? AND status = "pending" ORDER BY created_at DESC',
                [(int) $user['id']]
            ),
        ]);
    }

    public function store(): void
    {
        $user = $this->currentUser();
        $onboarding = Request::boolean('onboarding');

        $data = $this->validate([
            'institution_id' => 'required|integer',
            'programme' => 'max:160',
            'faculty_id' => 'integer',
            'department_id' => 'integer',
            'graduation_year' => 'required|integer',
        ]);

        $institution = Institution::find((int) $data['institution_id']);

        if ($institution === null) {
            $this->fail(['institution_id' => 'Choose an institution from the list.']);
        }

        $isOwnPending = $institution['status'] === 'pending' && (int) $institution['created_by'] === (int) $user['id'];

        if ($institution['status'] !== 'verified' && !$isOwnPending) {
            $this->fail(['institution_id' => 'That institution is not available yet. Request it, then select it once approved.']);
        }

        $year = (int) $data['graduation_year'];

        if ($year < 1900 || $year > (int) date('Y') + 10) {
            $this->fail(['graduation_year' => 'Enter a realistic graduation year.']);
        }

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

        $programmeName = trim((string) ($data['programme'] ?? ''));
        $programmeId = null;

        if ($programmeName !== '') {
            $programme = Programme::findByKey($programmeName);

            if ($programme !== null) {
                $programmeId = (int) $programme['id'];
                $programmeName = $programme['name'];
            }
        }

        $duplicate = (int) Database::value(
            'SELECT COUNT(*) FROM education_records
             WHERE user_id = ? AND institution_id = ? AND graduation_year = ?
               AND (faculty_id <=> ?) AND (department_id <=> ?) AND COALESCE(programme, "") = ?',
            [(int) $user['id'], (int) $institution['id'], $year, $facultyId, $departmentId, $programmeName],
            0
        );

        if ($duplicate > 0) {
            $this->flashError('That education record is already on your profile.');
            $this->redirect('profile/education');
        }

        $id = EducationRecord::create([
            'user_id' => (int) $user['id'],
            'institution_id' => (int) $institution['id'],
            'programme' => $programmeName !== '' ? $programmeName : null,
            'programme_id' => $programmeId,
            'faculty' => $facultyName,
            'department' => $departmentName,
            'faculty_id' => $facultyId,
            'department_id' => $departmentId,
            'graduation_year' => $year,
            'source' => $onboarding ? 'signup' : 'profile',
        ]);

        $this->audit(null, 'education.added', 'Added an education record at ' . $institution['name'], 'education_record', $id);

        if ($onboarding) {
            $this->flashSuccess('Your education is on file. Here are groups that match it.');
            $this->redirect('dashboard');
        }

        $this->flashSuccess('Education record added.');
        $this->redirect('profile/education');
    }

    public function destroy(string $id): void
    {
        $user = $this->currentUser();
        $record = EducationRecord::find((int) $id);

        if ($record === null || (int) $record['user_id'] !== (int) $user['id']) {
            \App\Core\Response::abort(404, 'That education record could not be found.');
        }

        EducationRecord::delete((int) $record['id']);
        $this->audit(null, 'education.removed', 'Removed an education record', 'education_record', (int) $record['id']);
        $this->flashSuccess('Education record removed.');
        $this->redirect('profile/education');
    }

    public function requestInstitutionForm(): void
    {
        $user = $this->currentUser();

        $this->view('profile/institution-request', [
            'user' => $user,
            'types' => Institution::TYPES,
            'ownerships' => Institution::OWNERSHIPS,
            'pending' => Database::fetchAll(
                'SELECT * FROM institutions WHERE created_by = ? AND status IN ("pending", "rejected") ORDER BY created_at DESC',
                [(int) $user['id']]
            ),
        ]);
    }

    public function storeInstitutionRequest(): void
    {
        $user = $this->currentUser();

        $data = $this->validate([
            'name' => 'required|min:3|max:200',
            'type' => 'required|in:primary,secondary,university,polytechnic,college,vocational,other',
            'ownership' => 'required|in:federal,state,private,other',
            'state' => 'max:80',
        ]);

        $recent = (int) Database::value(
            'SELECT COUNT(*) FROM institutions WHERE created_by = ? AND created_at > ?',
            [(int) $user['id'], date('Y-m-d H:i:s', time() - 86400)],
            0
        );

        if ($recent >= 3) {
            $this->flashError('You can submit at most three institution requests per day.');
            $this->redirect('profile/institutions/request');
        }

        $existing = Institution::findByKey((string) $data['name']);

        if ($existing !== null) {
            $this->flashError('That institution is already in the catalogue. Search for it when adding your education.');
            $this->redirect('profile/education');
        }

        $id = Institution::create([
            'name' => $data['name'],
            'type' => $data['type'],
            'ownership' => $data['ownership'],
            'state' => $data['state'] ?: null,
            'status' => 'pending',
            'source' => 'user submission',
            'created_by' => (int) $user['id'],
        ]);

        $this->audit(null, 'institution.requested', 'Requested institution "' . $data['name'] . '"', 'institution', $id);
        $this->flashSuccess('Institution submitted. A platform admin will review it before it can be used for group matching.');
        $this->redirect('profile/education');
    }
}
