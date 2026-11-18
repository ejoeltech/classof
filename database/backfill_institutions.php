<?php

declare(strict_types=1);

/**
 * Backfill catalogue links for existing groups (CLI only).
 *
 *   php database/backfill_institutions.php
 *
 * Safe to run repeatedly. It imports/updates the curated catalogue, maps each
 * group's free-text institution to a catalogue row by normalised name, and
 * recomputes specificity. Unmatched groups are reported and left untouched.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found.');
}

define('BASE_PATH', dirname(__DIR__));
define('BASE_URL_PATH', '');
define('APP_START', microtime(true));

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }

    $file = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require BASE_PATH . '/app/Core/helpers.php';

$config = require BASE_PATH . '/config/config.php';
App\Core\Config::init($config);
date_default_timezone_set('Africa/Lagos');

use App\Core\Database;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Group;
use App\Models\Institution;
use App\Models\PlatformSetting;
use App\Models\Programme;
use App\Services\UnitCatalogueService;

$catalogue = require BASE_PATH . '/database/data/institutions_ng.php';
$ids = [];

foreach ($catalogue['institutions'] as $row) {
    $id = Institution::create([
        'name' => $row['name'],
        'type' => $row['type'],
        'ownership' => $row['ownership'],
        'state' => $row['state'] ?? null,
        'status' => 'verified',
        'source' => $catalogue['source'],
        'source_url' => $catalogue['source_url'],
        'dataset_version' => $catalogue['version'],
        'aliases' => $row['aliases'] ?? [],
    ]);

    $ids[Institution::normalizeKey($row['name'])] = $id;
}

$programmeCatalogue = require BASE_PATH . '/database/data/programmes_ng.php';

foreach ($programmeCatalogue['programmes'] as [$programmeName, $field]) {
    Programme::create($programmeName, $field, null, $programmeCatalogue['source']);
}

PlatformSetting::seedDefaults();

$groups = Database::fetchAll('SELECT * FROM `alumni_groups` ORDER BY id');
$matched = 0;
$unmatched = [];

foreach ($groups as $group) {
    $key = Institution::normalizeKey((string) ($group['institution'] ?? ''));
    $institutionId = $ids[$key] ?? null;

    if ($institutionId === null) {
        $unmatched[] = '#' . $group['id'] . ' ' . ($group['name'] ?? '') . ' — "' . ($group['institution'] ?? '') . '"';
        continue;
    }

    UnitCatalogueService::ensureForInstitution($institutionId);

    $facultyId = null;
    $departmentId = null;

    if (!empty($group['faculty'])) {
        $faculty = Faculty::findForInstitution($institutionId, (string) $group['faculty']);

        if ($faculty !== null) {
            $facultyId = (int) $faculty['id'];

            if (!empty($group['department'])) {
                $department = Department::findForFaculty($facultyId, (string) $group['department']);

                if ($department !== null) {
                    $departmentId = (int) $department['id'];
                }
            }
        }
    }

    Database::update('alumni_groups', [
        'institution_id' => $institutionId,
        'faculty_id' => $facultyId,
        'department_id' => $departmentId,
        'specificity' => Group::specificityFor(
            (string) ($group['scope'] ?? 'class'),
            $group['faculty'] ?? null,
            $group['department'] ?? null,
            $group['graduation_year'] ?? null
        ),
    ], ['id' => (int) $group['id']]);

    $matched++;
}

fwrite(STDOUT, "Institution catalogue: " . count($ids) . " rows available.\n");
fwrite(STDOUT, "Groups matched: {$matched}\n");

$records = Database::fetchAll(
    'SELECT * FROM education_records WHERE institution_id IS NOT NULL AND faculty_id IS NULL AND faculty IS NOT NULL'
);
$recordsMapped = 0;

foreach ($records as $record) {
    $faculty = Faculty::findForInstitution((int) $record['institution_id'], (string) $record['faculty']);

    if ($faculty === null) {
        continue;
    }

    $departmentId = null;

    if (!empty($record['department'])) {
        $department = Department::findForFaculty((int) $faculty['id'], (string) $record['department']);

        if ($department !== null) {
            $departmentId = (int) $department['id'];
        }
    }

    Database::update('education_records', [
        'faculty_id' => (int) $faculty['id'],
        'department_id' => $departmentId,
    ], ['id' => (int) $record['id']]);

    $recordsMapped++;
}

fwrite(STDOUT, "Education records unit-mapped: {$recordsMapped}\n");

if ($unmatched !== []) {
    fwrite(STDOUT, "Unmatched groups (" . count($unmatched) . "):\n");

    foreach ($unmatched as $line) {
        fwrite(STDOUT, '  ' . $line . "\n");
    }
} else {
    fwrite(STDOUT, "Every group is linked to a catalogue institution.\n");
}
