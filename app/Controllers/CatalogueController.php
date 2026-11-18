<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Department;
use App\Models\Faculty;
use App\Services\UnitCatalogueService;

final class CatalogueController extends Controller
{
    public function faculties(): void
    {
        $institutionId = Request::int('institution_id');

        if ($institutionId > 0) {
            UnitCatalogueService::ensureForInstitution($institutionId);
        }

        $rows = $institutionId > 0 ? Faculty::forInstitution($institutionId) : [];

        Response::json(array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'name' => $row['name'],
        ], $rows));
    }

    public function departments(): void
    {
        $facultyId = Request::int('faculty_id');
        $rows = $facultyId > 0 ? Department::forFaculty($facultyId) : [];

        Response::json(array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'name' => $row['name'],
        ], $rows));
    }
}
