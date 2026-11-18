<?php

declare(strict_types=1);

/**
 * Classof demo seeder.
 *
 * Run after importing database/schema.sql:
 *   php database/seed.php
 *
 * Demo accounts (password: password123)
 *   admin@classof.test  — platform administrator
 *   demo@classof.test   — group owner / member
 */

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
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\CoopDividend;
use App\Models\CoopFine;
use App\Models\CoopLoan;
use App\Models\CoopLoanGuarantor;
use App\Models\CoopLoanProduct;
use App\Models\CoopMeeting;
use App\Models\CoopMember;
use App\Models\Cooperative;
use App\Models\Department;
use App\Models\DuesCycle;
use App\Models\EducationRecord;
use App\Models\Election;
use App\Models\ElectionCandidate;
use App\Models\ElectionPosition;
use App\Models\Event;
use App\Models\Faculty;
use App\Models\Group;
use App\Models\GroupChannel;
use App\Models\GroupRequest;
use App\Models\GroupSite;
use App\Models\Institution;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\PlatformSetting;
use App\Models\Programme;
use App\Models\User;
use App\Models\Vote;
use App\Models\WelfareCase;
use App\Models\WelfareContribution;
use App\Services\CoopService;
use App\Services\UnitCatalogueService;

if (!in_array('--force', $argv, true) && (int) Database::value('SELECT COUNT(*) FROM users', [], 0) > 0) {
    fwrite(STDOUT, "Database already contains data. Re-import database/schema.sql first, or run with --force to append.\n");
    exit(0);
}

$password = 'password123';

$users = [
    'admin' => ['Amara Okafor', 'admin@classof.test', 'admin'],
    'demo' => ['Tunde Bakare', 'demo@classof.test', 'user'],
    'chioma' => ['Chioma Nwosu', 'chioma@classof.test', 'user'],
    'emeka' => ['Emeka Obi', 'emeka@classof.test', 'user'],
    'fatima' => ['Fatima Bello', 'fatima@classof.test', 'user'],
    'kelechi' => ['Kelechi Eze', 'kelechi@classof.test', 'user'],
    'ngozi' => ['Ngozi Adeyemi', 'ngozi@classof.test', 'user'],
    'seyi' => ['Seyi Ogunleye', 'seyi@classof.test', 'user'],
    'ibrahim' => ['Ibrahim Musa', 'ibrahim@classof.test', 'user'],
    'bisi' => ['Bisi Adeleke', 'bisi@classof.test', 'user'],
    'jude' => ['Jude Okonkwo', 'jude@classof.test', 'user'],
];

$ids = [];

foreach ($users as $key => [$name, $email, $role]) {
    $existing = User::findByEmail($email);

    if ($existing !== null) {
        $ids[$key] = (int) $existing['id'];
        continue;
    }

    $ids[$key] = User::create([
        'name' => $name,
        'email' => $email,
        'password' => $password,
        'platform_role' => $role,
    ]);
}

$occupations = [
    'chioma' => ['Chartered Accountant', 'Lagos, Nigeria'],
    'emeka' => ['Software Engineer', 'Abuja, Nigeria'],
    'fatima' => ['Medical Doctor', 'Kano, Nigeria'],
    'kelechi' => ['Teacher', 'Enugu, Nigeria'],
    'ngozi' => ['HR Consultant', 'Lagos, Nigeria'],
    'seyi' => ['Banker', 'Ibadan, Nigeria'],
    'bisi' => ['Architect', 'Lagos, Nigeria'],
    'jude' => ['Electrical Engineer', 'Benin City, Nigeria'],
];

foreach ($occupations as $key => [$occupation, $location]) {
    Database::update('users', ['occupation' => $occupation, 'location' => $location], ['id' => $ids[$key]]);
}

/* ------------------------------------------------------ Institution catalogue */
$catalogue = require BASE_PATH . '/database/data/institutions_ng.php';
$institutionIds = [];

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

    $institutionIds[Institution::normalizeKey($row['name'])] = $id;
}

$iid = static function (string $name) use ($institutionIds): ?int {
    return $institutionIds[Institution::normalizeKey($name)] ?? null;
};

$programmeCatalogue = require BASE_PATH . '/database/data/programmes_ng.php';

foreach ($programmeCatalogue['programmes'] as [$programmeName, $field]) {
    Programme::create($programmeName, $field, null, $programmeCatalogue['source']);
}

UnitCatalogueService::ensureForInstitution((int) $iid('University of Lagos'));
UnitCatalogueService::ensureForInstitution((int) $iid('University of Benin'));

$ulScience = Faculty::findForInstitution((int) $iid('University of Lagos'), 'Faculty of Science');
$ulComputerScience = $ulScience !== null ? Department::findForFaculty((int) $ulScience['id'], 'Computer Science') : null;
$ulEngineering = Faculty::findForInstitution((int) $iid('University of Lagos'), 'Faculty of Engineering');
$ubEngineering = Faculty::findForInstitution((int) $iid('University of Benin'), 'Faculty of Engineering');
$ubEee = $ubEngineering !== null ? Department::findForFaculty((int) $ubEngineering['id'], 'Electrical/Electronic Engineering') : null;

/* ---------------------------------------------------------------- Groups */
$kings = Group::create([
    'name' => "King's College, Lagos — Class of 2010",
    'type' => 'secondary',
    'scope' => 'class',
    'institution_id' => $iid("King's College, Lagos"),
    'institution' => "King's College, Lagos",
    'graduation_year' => 2010,
    'country' => 'Nigeria',
    'currency' => 'NGN',
    'description' => 'The official alumni association for the King\'s College Lagos graduating class of 2010. We keep the bond alive through reunions, mentorship and member welfare.',
    'join_policy' => 'approval',
    'created_by' => $ids['demo'],
]);

$unilag = Group::create([
    'name' => 'University of Lagos — Class of 2016',
    'type' => 'university',
    'scope' => 'class',
    'institution_id' => $iid('University of Lagos'),
    'institution' => 'University of Lagos',
    'graduation_year' => 2016,
    'country' => 'Nigeria',
    'currency' => 'NGN',
    'description' => 'Connecting graduates of the UNILAG class of 2016. Professional network, homecoming events and a strong welfare fund.',
    'join_policy' => 'approval',
    'created_by' => $ids['admin'],
]);

$greenfield = Group::create([
    'name' => 'Greenfield Primary School — Class of 2004',
    'type' => 'primary',
    'scope' => 'class',
    'institution_id' => $iid('Greenfield Primary School'),
    'institution' => 'Greenfield Primary School',
    'graduation_year' => 2004,
    'country' => 'Nigeria',
    'currency' => 'NGN',
    'description' => 'Greenfield Primary School Class of 2004 alumni community.',
    'join_policy' => 'invite',
    'visibility' => 'private',
    'created_by' => $ids['chioma'],
]);

$unilagAssoc = Group::create([
    'name' => 'University of Lagos Alumni Association',
    'type' => 'university',
    'scope' => 'institution',
    'institution_id' => $iid('University of Lagos'),
    'institution' => 'University of Lagos',
    'country' => 'Nigeria',
    'currency' => 'NGN',
    'description' => 'The general alumni body for every graduate of the University of Lagos, across all years, faculties and departments.',
    'join_policy' => 'approval',
    'created_by' => $ids['admin'],
]);

$science = Group::create([
    'name' => 'Faculty of Science, University of Lagos — Alumni',
    'type' => 'university',
    'scope' => 'faculty',
    'institution_id' => $iid('University of Lagos'),
    'institution' => 'University of Lagos',
    'faculty_id' => $ulScience['id'] ?? null,
    'faculty' => 'Faculty of Science',
    'country' => 'Nigeria',
    'currency' => 'NGN',
    'description' => 'Alumni of the UNILAG Faculty of Science — all departments and all graduating years.',
    'join_policy' => 'approval',
    'created_by' => $ids['emeka'],
]);

$computerScience = Group::create([
    'name' => 'Department of Computer Science, UNILAG — Alumni',
    'type' => 'university',
    'scope' => 'department',
    'institution_id' => $iid('University of Lagos'),
    'institution' => 'University of Lagos',
    'faculty_id' => $ulScience['id'] ?? null,
    'department_id' => $ulComputerScience['id'] ?? null,
    'faculty' => 'Faculty of Science',
    'department' => 'Department of Computer Science',
    'country' => 'Nigeria',
    'currency' => 'NGN',
    'description' => 'A home for graduates of the UNILAG Department of Computer Science — mentorship, jobs and research collaboration.',
    'join_policy' => 'approval',
    'created_by' => $ids['emeka'],
]);

$oldBoys = Group::create([
    'name' => "King's College Lagos Old Boys Association",
    'type' => 'secondary',
    'scope' => 'association',
    'institution_id' => $iid("King's College, Lagos"),
    'institution' => "King's College, Lagos",
    'country' => 'Nigeria',
    'currency' => 'NGN',
    'description' => 'The institution-wide old boys association for every King\'s College Lagos graduate, from every set.',
    'join_policy' => 'approval',
    'created_by' => $ids['chioma'],
]);

$unibenAssoc = Group::create([
    'name' => 'University of Benin Alumni Association',
    'type' => 'university',
    'scope' => 'institution',
    'institution_id' => $iid('University of Benin'),
    'institution' => 'University of Benin',
    'country' => 'Nigeria',
    'currency' => 'NGN',
    'description' => 'The institution-wide alumni body for every graduate of the University of Benin.',
    'join_policy' => 'approval',
    'created_by' => $ids['admin'],
]);

$unibenClass = Group::create([
    'name' => 'University of Benin — Class of 2004',
    'type' => 'university',
    'scope' => 'class',
    'institution_id' => $iid('University of Benin'),
    'institution' => 'University of Benin',
    'graduation_year' => 2004,
    'country' => 'Nigeria',
    'currency' => 'NGN',
    'description' => 'The 2004 graduating set of the University of Benin.',
    'join_policy' => 'approval',
    'created_by' => $ids['admin'],
]);

$unibenOld = Group::create([
    'name' => 'UNIBEN Old Students Association',
    'type' => 'university',
    'scope' => 'association',
    'institution_id' => $iid('University of Benin'),
    'institution' => 'University of Benin',
    'country' => 'Nigeria',
    'currency' => 'NGN',
    'description' => 'The old students association for University of Benin graduates across all sets.',
    'join_policy' => 'approval',
    'created_by' => $ids['admin'],
]);

$unibenFaculty = Group::create([
    'name' => 'Faculty of Engineering, UNIBEN — Alumni',
    'type' => 'university',
    'scope' => 'faculty',
    'institution_id' => $iid('University of Benin'),
    'institution' => 'University of Benin',
    'faculty_id' => $ubEngineering['id'] ?? null,
    'faculty' => 'Faculty of Engineering',
    'country' => 'Nigeria',
    'currency' => 'NGN',
    'description' => 'Alumni of the UNIBEN Faculty of Engineering.',
    'join_policy' => 'approval',
    'created_by' => $ids['admin'],
]);

$unibenDept = Group::create([
    'name' => 'Department of Electrical/Electronic Engineering, UNIBEN — Alumni',
    'type' => 'university',
    'scope' => 'department',
    'institution_id' => $iid('University of Benin'),
    'institution' => 'University of Benin',
    'faculty_id' => $ubEngineering['id'] ?? null,
    'department_id' => $ubEee['id'] ?? null,
    'faculty' => 'Faculty of Engineering',
    'department' => 'Electrical/Electronic Engineering',
    'country' => 'Nigeria',
    'currency' => 'NGN',
    'description' => 'Alumni of the UNIBEN Department of Electrical/Electronic Engineering.',
    'join_policy' => 'approval',
    'created_by' => $ids['admin'],
]);

$unibenCombined = Group::create([
    'name' => 'University of Benin — Class of 2004, Faculty of Engineering',
    'type' => 'university',
    'scope' => 'class',
    'institution_id' => $iid('University of Benin'),
    'institution' => 'University of Benin',
    'faculty_id' => $ubEngineering['id'] ?? null,
    'faculty' => 'Faculty of Engineering',
    'graduation_year' => 2004,
    'country' => 'Nigeria',
    'currency' => 'NGN',
    'description' => 'The 2004 Engineering set of the University of Benin. This combined-unit group is not matched in Release 1.',
    'join_policy' => 'approval',
    'created_by' => $ids['admin'],
]);

Database::update('alumni_groups', ['cover_color' => '#0B6E4F'], ['id' => $kings]);
Database::update('alumni_groups', ['cover_color' => '#1D4ED8'], ['id' => $unilag]);
Database::update('alumni_groups', ['cover_color' => '#B45309'], ['id' => $greenfield]);
Database::update('alumni_groups', ['cover_color' => '#7C3AED'], ['id' => $unilagAssoc]);
Database::update('alumni_groups', ['cover_color' => '#0E7490'], ['id' => $science]);
Database::update('alumni_groups', ['cover_color' => '#BE123C'], ['id' => $computerScience]);
Database::update('alumni_groups', ['cover_color' => '#065F46'], ['id' => $oldBoys]);
Database::update('alumni_groups', ['cover_color' => '#4338CA'], ['id' => $unibenAssoc]);
Database::update('alumni_groups', ['cover_color' => '#0891B2'], ['id' => $unibenClass]);
Database::update('alumni_groups', ['cover_color' => '#0D9488'], ['id' => $unibenOld]);
Database::update('alumni_groups', ['cover_color' => '#7C2D12'], ['id' => $unibenFaculty]);
Database::update('alumni_groups', ['cover_color' => '#9F1239'], ['id' => $unibenDept]);
Database::update('alumni_groups', ['cover_color' => '#6D28D9'], ['id' => $unibenCombined]);

/* ------------------------------------------------------- Group websites */
$siteSeeds = [
    $kings => [
        'subdomain' => 'kingscollege-2010',
        'headline' => "King's College, Lagos — Class of 2010",
        'tagline' => 'The alumni home of the 2010 set. Reunions, mentorship and a community that looks out for its own.',
        'contact_email' => 'classof2010@classof.test',
        'contact_phone' => '+234 801 000 0010',
        'contact_address' => 'King\'s College, Lagos',
    ],
    $unilag => [
        'subdomain' => 'unilag-2016',
        'tagline' => 'Connecting the University of Lagos Class of 2016 — professionally and personally.',
        'contact_email' => 'unilag2016@classof.test',
    ],
    $greenfield => [
        'subdomain' => 'greenfield-2004',
        'status' => 'draft',
        'headline' => 'Greenfield Primary School — Class of 2004',
    ],
    $unilagAssoc => [
        'subdomain' => 'unilag',
        'tagline' => 'One university, every generation. The alumni home for all University of Lagos graduates.',
    ],
    $science => [
        'subdomain' => 'unilag-science',
        'tagline' => 'Alumni of the UNILAG Faculty of Science, across every department and graduating year.',
    ],
    $computerScience => [
        'subdomain' => 'unilag-cs',
        'tagline' => 'A home for graduates of the UNILAG Department of Computer Science.',
    ],
    $oldBoys => [
        'subdomain' => 'kings-oldboys',
        'tagline' => 'The institution-wide old boys association for every King\'s College Lagos graduate.',
    ],
    $unibenAssoc => [
        'subdomain' => 'uniben',
        'tagline' => 'The alumni home for every University of Benin graduate.',
        'editor_user_id' => $ids['jude'],
    ],
    $unibenClass => [
        'subdomain' => 'uniben-2004',
        'tagline' => 'The 2004 graduating set of the University of Benin, keeping the connection alive.',
    ],
    $unibenOld => [
        'subdomain' => 'uniben-oldstudents',
        'tagline' => 'University of Benin old students, across all sets and faculties.',
    ],
    $unibenFaculty => [
        'subdomain' => 'uniben-engineering',
        'tagline' => 'Alumni of the UNIBEN Faculty of Engineering.',
    ],
    $unibenDept => [
        'subdomain' => 'uniben-eee',
        'tagline' => 'Alumni of the UNIBEN Department of Electrical/Electronic Engineering.',
    ],
    $unibenCombined => [
        'subdomain' => 'uniben-2004-engineering',
        'tagline' => 'The 2004 Engineering set of the University of Benin.',
    ],
];

foreach ([$kings, $unilag, $greenfield, $unilagAssoc, $science, $computerScience, $oldBoys, $unibenAssoc, $unibenClass, $unibenOld, $unibenFaculty, $unibenDept, $unibenCombined] as $groupId) {
    $groupRow = Group::find($groupId);

    if ($groupRow !== null) {
        GroupSite::createForGroup($groupRow, $siteSeeds[$groupId] ?? []);
    }
}


$groupOwners = [
    $kings => $ids['demo'],
    $unilag => $ids['admin'],
    $greenfield => $ids['chioma'],
    $unilagAssoc => $ids['admin'],
    $science => $ids['emeka'],
    $computerScience => $ids['emeka'],
    $oldBoys => $ids['chioma'],
    $unibenAssoc => $ids['admin'],
    $unibenClass => $ids['admin'],
    $unibenOld => $ids['admin'],
    $unibenFaculty => $ids['admin'],
    $unibenDept => $ids['admin'],
    $unibenCombined => $ids['admin'],
];

/* ----------------------------------------------------------- Memberships */
$memberships = [
    // King's College class of 2010
    [$kings, 'demo', 'owner', 'President', 'active', 'KC/2010/0001'],
    [$kings, 'admin', 'admin', 'Vice President', 'active', 'KC/2010/0002'],
    [$kings, 'chioma', 'exco', 'General Secretary', 'active', 'KC/2010/0031'],
    [$kings, 'emeka', 'member', null, 'active', 'KC/2010/0044'],
    [$kings, 'fatima', 'member', null, 'active', 'KC/2010/0052'],
    [$kings, 'kelechi', 'member', null, 'active', 'KC/2010/0063'],
    [$kings, 'ngozi', 'member', null, 'active', 'KC/2010/0071'],
    [$kings, 'seyi', 'member', null, 'active', 'KC/2010/0088'],
    [$kings, 'ibrahim', 'member', null, 'pending', 'KC/2010/0199'],

    // UNILAG class of 2016
    [$unilag, 'admin', 'owner', 'Chairman', 'active', 'UNILAG/2016/0001'],
    [$unilag, 'demo', 'member', null, 'active', 'UNILAG/2016/0102'],
    [$unilag, 'emeka', 'exco', 'Treasurer', 'active', 'UNILAG/2016/0155'],
    [$unilag, 'fatima', 'member', null, 'active', 'UNILAG/2016/0201'],
    [$unilag, 'bisi', 'member', null, 'active', 'UNILAG/2016/0240'],

    // Greenfield primary
    [$greenfield, 'chioma', 'owner', 'Coordinator', 'active', 'GPS/2004/001'],
    [$greenfield, 'ngozi', 'member', null, 'active', 'GPS/2004/012'],
    [$greenfield, 'demo', 'member', null, 'active', 'GPS/2004/033'],

    // Institution-wide alumni association
    [$unilagAssoc, 'admin', 'owner', 'President', 'active', 'UNILAG-ALUM/0001'],
    [$unilagAssoc, 'demo', 'member', null, 'active', 'UNILAG-ALUM/1024'],
    [$unilagAssoc, 'chioma', 'member', null, 'active', 'UNILAG-ALUM/1102'],
    [$unilagAssoc, 'emeka', 'member', null, 'active', 'UNILAG-ALUM/1240'],
    [$unilagAssoc, 'fatima', 'member', null, 'active', 'UNILAG-ALUM/1330'],

    // Faculty alumni
    [$science, 'emeka', 'owner', 'Coordinator', 'active', 'FOS/2016/0001'],
    [$science, 'demo', 'member', null, 'active', 'FOS/2016/0110'],
    [$science, 'fatima', 'member', null, 'active', 'FOS/2016/0220'],
    [$science, 'ngozi', 'member', null, 'active', 'FOS/2016/0330'],

    // Department alumni
    [$computerScience, 'emeka', 'owner', 'Coordinator', 'active', 'CS/2016/0001'],
    [$computerScience, 'demo', 'member', null, 'active', 'CS/2016/0102'],
    [$computerScience, 'ngozi', 'member', null, 'pending', 'CS/2016/0450'],

    // Institution-wide old boys association
    [$oldBoys, 'chioma', 'owner', 'President', 'active', 'OBA/0001'],
    [$oldBoys, 'demo', 'member', null, 'active', 'OBA/0142'],
    [$oldBoys, 'seyi', 'member', null, 'active', 'OBA/0208'],
    [$oldBoys, 'emeka', 'member', null, 'active', 'OBA/0311'],

    // University of Benin demo groups (Jude is eligible, not yet a member)
    [$unibenAssoc, 'admin', 'owner', 'President', 'active', 'UNIBEN-ALUM/0001'],
    [$unibenClass, 'admin', 'owner', 'Coordinator', 'active', 'UNIBEN/2004/0001'],
    [$unibenOld, 'admin', 'owner', 'President', 'active', 'UNIBEN-OBA/0001'],
    [$unibenFaculty, 'admin', 'owner', 'Coordinator', 'active', 'UNIBEN-ENG/0001'],
    [$unibenDept, 'admin', 'owner', 'Coordinator', 'active', 'UNIBEN-EEE/0001'],
    [$unibenCombined, 'admin', 'owner', 'Coordinator', 'active', 'UNIBEN-ENG2004/0001'],
];

$membershipIds = [];

foreach ($memberships as [$groupId, $userKey, $role, $title, $status, $proofRef]) {
    $isOwner = $role === 'owner';
    $proofType = $isOwner ? 'founder' : ($status === 'pending' ? 'student_id' : 'alumni_id');
    $reviewer = $status === 'active' ? $groupOwners[$groupId] : null;

    $id = Membership::create([
        'group_id' => $groupId,
        'user_id' => $ids[$userKey],
        'role' => $role,
        'position_title' => $title,
        'status' => $status,
        'approved_by' => $reviewer,
        'reviewed_by' => $reviewer,
        'reviewed_at' => $status === 'active' ? date('Y-m-d H:i:s', strtotime('-8 months')) : null,
        'proof_type' => $proofType,
        'proof_reference' => $proofRef,
        'proof_note' => $isOwner
            ? 'Founded this group on Classof.'
            : ($status === 'pending'
                ? 'Submitted for review. Please verify against the alumni register.'
                : 'Checked against the alumni register.'),
    ]);

    $membershipIds[$groupId . '-' . $userKey] = $id;

    if ($status === 'active') {
        Database::update('memberships', [
            'joined_at' => date('Y-m-d H:i:s', strtotime('-8 months')),
        ], ['id' => $id]);
    }
}

/* ---------------------------------------------------------- Cooperative */
$coopId = Cooperative::create([
    'group_id' => $kings,
    'name' => "King's College Class of 2010 Cooperative Society",
    'registration_no' => 'KC-COOP/2010/001',
    'share_value' => 1000,
    'monthly_savings' => 5000,
    'loan_interest_rate' => 12,
    'loan_duration_months' => 12,
    'max_loan_multiple' => 3,
    'guarantors_required' => 2,
    'fine_absent' => 500,
    'meeting_frequency' => 'Monthly',
    'created_by' => $ids['demo'],
]);

$coop = Cooperative::find($coopId);
$coopRoles = [
    'demo' => 'coordinator',
    'chioma' => 'treasurer',
    'seyi' => 'loan_officer',
    'admin' => 'secretary',
];
$coopShares = ['demo' => 20, 'admin' => 15, 'chioma' => 12, 'emeka' => 8, 'fatima' => 5, 'kelechi' => 4, 'ngozi' => 6, 'seyi' => 7];
$coopSavings = ['demo' => 60000, 'admin' => 45000, 'chioma' => 36000, 'emeka' => 24000, 'fatima' => 15000, 'kelechi' => 12000, 'ngozi' => 18000, 'seyi' => 21000];
$coopMemberIds = [];

foreach ($coopShares as $key => $shares) {
    $memberId = CoopMember::create([
        'cooperative_id' => $coopId,
        'user_id' => $ids[$key],
        'role' => $coopRoles[$key] ?? 'member',
        'status' => 'active',
        'shares' => 0,
        'approved_by' => $ids['demo'],
        'joined_at' => date('Y-m-d H:i:s', strtotime('-8 months')),
    ]);

    $coopMemberIds[$key] = $memberId;

    CoopService::purchaseShares(
        $coopId,
        $memberId,
        $shares * (float) $coop['share_value'],
        (float) $coop['share_value'],
        'system',
        null,
        'Initial share purchase',
        null,
        date('Y-m-d H:i:s', strtotime('-8 months'))
    );

    if (!empty($coopSavings[$key])) {
        CoopService::recordSavings(
            $coopId,
            $memberId,
            (float) $coopSavings[$key],
            'in',
            'system',
            null,
            'Opening savings balance',
            null,
            date('Y-m-d H:i:s', strtotime('-8 months'))
        );
    }
}

$emergencyProduct = CoopLoanProduct::create([
    'cooperative_id' => $coopId,
    'name' => 'Emergency loan',
    'description' => 'Short-term support for urgent needs.',
    'interest_rate' => 12,
    'duration_months' => 6,
    'min_amount' => 5000,
    'max_amount' => 200000,
    'max_multiple' => 2,
    'guarantors_required' => 2,
    'status' => 'active',
]);

$developmentProduct = CoopLoanProduct::create([
    'cooperative_id' => $coopId,
    'name' => 'Development loan',
    'description' => 'Larger loan for business or education over 12 months.',
    'interest_rate' => 15,
    'duration_months' => 12,
    'min_amount' => 20000,
    'max_amount' => 500000,
    'max_multiple' => 3,
    'guarantors_required' => 2,
    'status' => 'active',
]);

$emekaTerms = CoopLoan::calculateTerms(50000, 12, 6);
$emekaLoanId = CoopLoan::create([
    'cooperative_id' => $coopId,
    'member_id' => $coopMemberIds['emeka'],
    'product_id' => $emergencyProduct,
    'principal' => 50000,
    'interest_rate' => 12,
    'duration_months' => 6,
    'interest_amount' => $emekaTerms['interest'],
    'total_repayable' => $emekaTerms['total'],
    'monthly_repayment' => $emekaTerms['monthly'],
    'purpose' => 'Business stock',
]);

foreach (['demo', 'chioma'] as $guarantorKey) {
    $guarantorId = CoopLoanGuarantor::create($emekaLoanId, $coopMemberIds[$guarantorKey], 25000);
    CoopLoanGuarantor::respond($guarantorId, 'accepted');
}

CoopService::approveLoan(CoopLoan::find($emekaLoanId), $ids['demo']);
CoopService::disburseLoan(CoopLoan::find($emekaLoanId), $ids['demo'], 'bank', 'Transfer to Emeka');
CoopService::recordRepayment(CoopLoan::find($emekaLoanId), 18000, 'bank', $ids['chioma'], null, 'First two instalments');

$fatimaTerms = CoopLoan::calculateTerms(30000, 15, 12);
$fatimaLoanId = CoopLoan::create([
    'cooperative_id' => $coopId,
    'member_id' => $coopMemberIds['fatima'],
    'product_id' => $developmentProduct,
    'principal' => 30000,
    'interest_rate' => 15,
    'duration_months' => 12,
    'interest_amount' => $fatimaTerms['interest'],
    'total_repayable' => $fatimaTerms['total'],
    'monthly_repayment' => $fatimaTerms['monthly'],
    'purpose' => 'Clinic equipment',
]);

CoopLoanGuarantor::create($fatimaLoanId, $coopMemberIds['demo'], 15000);
CoopLoanGuarantor::create($fatimaLoanId, $coopMemberIds['seyi'], 15000);

$pastMeeting = CoopMeeting::create([
    'cooperative_id' => $coopId,
    'title' => 'Q4 2026 General Meeting',
    'agenda' => "1. Review savings and loan book\n2. Approve the 2026 dividend\n3. Elections and welfare update",
    'location' => "King's College assembly hall",
    'scheduled_at' => date('Y-m-d H:i:s', strtotime('-1 month 10:00')),
    'created_by' => $ids['demo'],
]);

foreach (['demo' => 'present', 'admin' => 'present', 'chioma' => 'present', 'emeka' => 'absent', 'fatima' => 'apology', 'kelechi' => 'absent', 'ngozi' => 'present', 'seyi' => 'present'] as $key => $status) {
    CoopMeeting::setAttendance($pastMeeting, $coopMemberIds[$key], $status, null);
}

CoopMeeting::complete($pastMeeting, "The co-op reviewed a savings pool of over ₦230,000 and approved the 2026 surplus distribution. Loan recovery remains healthy. The next meeting will review the new development loan product.");

CoopMeeting::create([
    'cooperative_id' => $coopId,
    'title' => 'Q1 2027 General Meeting',
    'agenda' => "1. Loan recovery report\n2. New member induction\n3. Any other business",
    'location' => 'Zoom',
    'scheduled_at' => date('Y-m-d H:i:s', strtotime('+1 month 18:00')),
    'created_by' => $ids['demo'],
]);

$kelechiFine = CoopFine::create([
    'cooperative_id' => $coopId,
    'member_id' => $coopMemberIds['kelechi'],
    'meeting_id' => $pastMeeting,
    'reason' => 'Absence from Q4 2026 General Meeting',
    'amount' => 500,
    'issued_by' => $ids['chioma'],
]);

$emekaFine = CoopFine::create([
    'cooperative_id' => $coopId,
    'member_id' => $coopMemberIds['emeka'],
    'meeting_id' => $pastMeeting,
    'reason' => 'Absence from Q4 2026 General Meeting',
    'amount' => 500,
    'issued_by' => $ids['chioma'],
]);

CoopService::payFine(CoopFine::find($emekaFine), 'cash', $ids['chioma']);

$dividendResult = CoopService::declareDividend($coop, [
    'title' => '2026 Surplus Distribution',
    'period' => '2026',
    'basis' => 'shares',
    'total_amount' => 120000,
], $ids['chioma']);

if (!empty($dividendResult['ok'])) {
    CoopService::distributeDividend(CoopDividend::find((int) $dividendResult['dividend_id']), $ids['chioma']);
}

AuditLog::record($kings, $ids['demo'], 'coop.enabled', 'Enabled the cooperative society', 'cooperative', $coopId);

PlatformSetting::seedDefaults();

GroupChannel::create([
    'group_id' => $kings,
    'label' => 'Main class group',
    'invite_url' => 'https://chat.whatsapp.com/ClassofDemoMainGroup',
    'visible_to' => 'members',
    'is_primary' => true,
    'created_by' => $ids['demo'],
]);

GroupChannel::create([
    'group_id' => $kings,
    'label' => 'Exco only',
    'invite_url' => 'https://chat.whatsapp.com/ClassofDemoExcoGroup',
    'visible_to' => 'manager',
    'is_primary' => false,
    'created_by' => $ids['demo'],
]);

/* ------------------------------------------------------ Education records */
$educationRecords = [
    'demo' => [
        ['University of Lagos', 2016, 'Computer Science', 'Faculty of Science', 'Computer Science'],
        ["King's College, Lagos", 2010, null, null, null],
    ],
    'admin' => [
        ['University of Lagos', 2016, 'Computer Science', 'Faculty of Science', 'Computer Science'],
    ],
    'chioma' => [
        ["King's College, Lagos", 2010, null, null, null],
        ['University of Lagos', 2016, 'Accounting', 'Faculty of Management Sciences', 'Accounting'],
    ],
    'emeka' => [
        ['University of Lagos', 2016, 'Computer Science', 'Faculty of Science', 'Computer Science'],
    ],
    'fatima' => [
        ['University of Lagos', 2016, 'Medicine and Surgery', 'Faculty of Medicine', 'Medicine and Surgery'],
    ],
    'kelechi' => [
        ["King's College, Lagos", 2010, null, null, null],
    ],
    'ngozi' => [
        ["King's College, Lagos", 2010, null, null, null],
        ['Greenfield Primary School', 2004, null, null, null],
    ],
    'seyi' => [
        ["King's College, Lagos", 2010, null, null, null],
    ],
    'ibrahim' => [
        ["King's College, Lagos", 2010, null, null, null],
    ],
    'bisi' => [
        ['University of Lagos', 2016, 'Architecture', 'Faculty of Environmental Sciences', 'Architecture'],
    ],
    'jude' => [
        ['University of Benin', 2004, 'Electrical/Electronic Engineering', 'Faculty of Engineering', 'Electrical/Electronic Engineering'],
    ],
];

foreach ($educationRecords as $userKey => $records) {
    foreach ($records as [$institutionName, $year, $programme, $faculty, $department]) {
        $institutionId = $iid($institutionName);

        if ($institutionId === null) {
            continue;
        }

        UnitCatalogueService::ensureForInstitution($institutionId);

        $facultyId = null;
        $departmentId = null;

        if ($faculty !== null) {
            $facultyRow = Faculty::findForInstitution($institutionId, $faculty);

            if ($facultyRow !== null) {
                $facultyId = (int) $facultyRow['id'];

                if ($department !== null) {
                    $departmentRow = Department::findForFaculty($facultyId, $department);

                    if ($departmentRow !== null) {
                        $departmentId = (int) $departmentRow['id'];
                    }
                }
            }
        }

        $programmeRow = $programme !== null ? Programme::findByKey($programme) : null;

        EducationRecord::create([
            'user_id' => $ids[$userKey],
            'institution_id' => $institutionId,
            'programme' => $programme,
            'programme_id' => $programmeRow['id'] ?? null,
            'faculty' => $faculty,
            'department' => $department,
            'faculty_id' => $facultyId,
            'department_id' => $departmentId,
            'graduation_year' => $year,
            'source' => 'import',
        ]);
    }
}

/* ------------------------------------------- Verified classmate demo records */
$unilagInstitutionId = (int) $iid('University of Lagos');
$verifiedClassmates = ['demo', 'chioma', 'emeka', 'fatima', 'bisi'];

foreach ($verifiedClassmates as $key) {
    Database::run(
        'UPDATE education_records SET status = "verified", verified_by = ?, verified_at = ?
         WHERE user_id = ? AND institution_id = ? AND graduation_year = 2016',
        [$ids['admin'], date('Y-m-d H:i:s'), $ids[$key], $unilagInstitutionId]
    );
}

$contactOptIns = [
    'demo' => ['+2348010000001', true, true],
    'chioma' => ['+2348010000002', true, true],
    'emeka' => ['+2348010000003', true, true],
    'fatima' => ['+2348010000004', true, true],
    'bisi' => ['+2348010000005', true, true],
    'jude' => ['+2348010000006', true, true],
];

foreach ($contactOptIns as $key => [$phone, $whatsappOptIn, $classmateOptIn]) {
    Database::update('users', [
        'phone_e164' => $phone,
        'whatsapp_opt_in_at' => $whatsappOptIn ? date('Y-m-d H:i:s') : null,
        'whatsapp_opt_out_at' => null,
        'classmate_opt_in_at' => $classmateOptIn ? date('Y-m-d H:i:s') : null,
    ], ['id' => $ids[$key]]);
}

/* --------------------------------------------- Pending catalogue/group work */
$pendingInstitution = Institution::create([
    'name' => 'Lagos City College of Technology',
    'type' => 'polytechnic',
    'ownership' => 'private',
    'state' => 'Lagos',
    'status' => 'pending',
    'source' => 'user submission',
    'created_by' => $ids['demo'],
]);

$facultyRequest = GroupRequest::create([
    'user_id' => $ids['bisi'],
    'institution_id' => (int) $iid('University of Lagos'),
    'name' => 'Faculty of Engineering, University of Lagos — Alumni',
    'scope' => 'faculty',
    'type' => 'university',
    'faculty' => 'Faculty of Engineering',
    'faculty_id' => $ulEngineering['id'] ?? null,
    'description' => 'A faculty-level alumni group for UNILAG Engineering graduates, separate from the class of 2016 group.',
]);

AuditLog::record(null, $ids['demo'], 'institution.requested', 'Requested institution "Lagos City College of Technology"', 'institution', $pendingInstitution);
AuditLog::record(null, $ids['bisi'], 'group.requested', 'Requested group "Faculty of Engineering, University of Lagos — Alumni"', 'group_request', $facultyRequest);

/* --------------------------------------------------------- Announcements */
Announcement::create([
    'group_id' => $kings,
    'user_id' => $ids['demo'],
    'title' => '2026 Annual General Meeting — save the date',
    'body' => "Dear classmates,\n\nOur AGM holds on the last Saturday of March 2026 at the King's College assembly hall. We will review the welfare fund, elect a new exco, and unveil the class endowment project.\n\nPlease RSVP through the Events page so we can plan seating and refreshments.\n\nWarm regards,\nTunde",
    'pinned' => true,
]);

Announcement::create([
    'group_id' => $kings,
    'user_id' => $ids['chioma'],
    'title' => 'Welfare case: support for Kelechi Eze',
    'body' => 'Kelechi has been scheduled for surgery next month. The welfare committee has opened a case and is appealing to every member to contribute what they can. Every naira counts.',
    'pinned' => false,
]);

Announcement::create([
    'group_id' => $unilag,
    'user_id' => $ids['admin'],
    'title' => 'Mentorship programme — call for volunteers',
    'body' => 'We are pairing final-year students with alumni mentors. If you can commit two hours a month, add your name to the mentorship list at the next homecoming.',
    'pinned' => false,
]);

Announcement::create([
    'group_id' => $unilagAssoc,
    'user_id' => $ids['admin'],
    'title' => 'One association, every UNILAG graduate',
    'body' => "This is the institution-wide alumni body. Whether you graduated in 1985 or 2025, you belong here. Class, faculty and department groups remain separate so you can keep both the broad network and your closest circle.",
    'pinned' => true,
]);

Announcement::create([
    'group_id' => $computerScience,
    'user_id' => $ids['emeka'],
    'title' => 'CS alumni mentorship pairing',
    'body' => 'We are matching final-year Computer Science students with alumni in engineering, data, product and research. Reply with your area of expertise to join the mentor pool.',
    'pinned' => false,
]);

Announcement::create([
    'group_id' => $oldBoys,
    'user_id' => $ids['chioma'],
    'title' => 'Old Boys Association — membership drive',
    'body' => 'Every set is welcome. Submit your proof of attendance and a group admin will verify you against the school register.',
    'pinned' => false,
]);

/* ---------------------------------------------------------------- Events */
$reunion = Event::create([
    'group_id' => $kings,
    'title' => 'Class of 2010 Annual Reunion',
    'description' => "A full day of reconnection, dinner and the class photograph. Guests are welcome.\n\nDress code: smart casual with a touch of gold.",
    'location' => "King's College Assembly Hall, Lagos",
    'starts_at' => date('Y-m-d H:i:s', strtotime('+30 days 10:00')),
    'ends_at' => date('Y-m-d H:i:s', strtotime('+30 days 18:00')),
    'capacity' => 150,
    'fee' => 5000,
    'created_by' => $ids['demo'],
]);

Event::create([
    'group_id' => $kings,
    'title' => 'Career Mentorship Webinar',
    'description' => 'Alumni share career paths with current students. Speakers from law, medicine, technology and finance.',
    'location' => 'Zoom',
    'starts_at' => date('Y-m-d H:i:s', strtotime('+10 days 18:00')),
    'ends_at' => date('Y-m-d H:i:s', strtotime('+10 days 19:30')),
    'capacity' => 300,
    'fee' => 0,
    'created_by' => $ids['chioma'],
]);

$homecoming = Event::create([
    'group_id' => $unilag,
    'title' => 'UNILAG Class of 2016 Homecoming',
    'description' => 'Campus tour, networking dinner and the annual class photograph.',
    'location' => 'University of Lagos Main Campus',
    'starts_at' => date('Y-m-d H:i:s', strtotime('+45 days 12:00')),
    'ends_at' => date('Y-m-d H:i:s', strtotime('+45 days 20:00')),
    'capacity' => 200,
    'fee' => 0,
    'created_by' => $ids['admin'],
]);

Event::setRsvp($reunion, $ids['demo'], 'going', 1);
Event::setRsvp($reunion, $ids['chioma'], 'going', 0);
Event::setRsvp($reunion, $ids['emeka'], 'maybe', 0);
Event::setRsvp($reunion, $ids['fatima'], 'going', 2);
Event::setRsvp($homecoming, $ids['demo'], 'going', 0);

/* ------------------------------------------------------------------ Dues */
$dues = DuesCycle::create([
    'group_id' => $kings,
    'title' => '2026 Annual Dues',
    'description' => 'Covers the welfare fund, AGM logistics and the class endowment contribution.',
    'amount' => 25000,
    'currency' => 'NGN',
    'due_date' => date('Y-m-d', strtotime('+60 days')),
    'created_by' => $ids['demo'],
]);

$duesPayments = [];

foreach (['demo', 'chioma', 'emeka'] as $index => $key) {
    $payment = Payment::create([
        'group_id' => $kings,
        'user_id' => $ids[$key],
        'purpose' => 'dues',
        'dues_cycle_id' => $dues,
        'gateway' => 'manual',
        'amount' => 25000,
        'currency' => 'NGN',
        'payer_name' => $users[$key][0],
        'payer_email' => $users[$key][1],
    ]);

    Payment::markSuccess((int) $payment['id'], ['confirmed_by_admin' => true], date('Y-m-d H:i:s', strtotime('-' . (5 + $index) . ' days')));
    Payment::confirmManual((int) $payment['id'], $ids['admin']);
    $duesPayments[$key] = (int) $payment['id'];
}

$pendingPayment = Payment::create([
    'group_id' => $kings,
    'user_id' => $ids['fatima'],
    'purpose' => 'dues',
    'dues_cycle_id' => $dues,
    'gateway' => 'manual',
    'amount' => 25000,
    'currency' => 'NGN',
    'payer_name' => $users['fatima'][0],
    'payer_email' => $users['fatima'][1],
]);

/* --------------------------------------------------------------- Welfare */
$case = WelfareCase::create([
    'group_id' => $kings,
    'user_id' => $ids['kelechi'],
    'title' => 'Medical support for Kelechi Eze',
    'category' => 'medical',
    'description' => 'Kelechi requires a scheduled surgical procedure. The welfare committee has verified the hospital estimate and is raising funds to cover the balance not covered by his HMO.',
    'target_amount' => 500000,
    'currency' => 'NGN',
    'created_by' => $ids['demo'],
]);

$contributions = [
    ['demo', 50000, 'Wishing you a quick recovery.'],
    ['chioma', 25000, null],
    ['emeka', 10000, null],
    ['fatima', 15000, 'Get well soon.'],
    ['ngozi', 5000, null],
];

foreach ($contributions as [$key, $amount, $note]) {
    $payment = Payment::create([
        'group_id' => $kings,
        'user_id' => $ids[$key],
        'purpose' => 'welfare',
        'welfare_case_id' => $case,
        'gateway' => 'manual',
        'amount' => $amount,
        'currency' => 'NGN',
        'payer_name' => $users[$key][0],
        'payer_email' => $users[$key][1],
    ]);

    Payment::markSuccess((int) $payment['id'], [], date('Y-m-d H:i:s', strtotime('-' . random_int(1, 20) . ' days')));

    WelfareContribution::create([
        'welfare_case_id' => $case,
        'user_id' => $ids[$key],
        'payment_id' => (int) $payment['id'],
        'amount' => $amount,
        'note' => $note,
    ]);
}

/* ------------------------------------------------------------- Elections */
$election = Election::create([
    'group_id' => $kings,
    'title' => '2026 Executive Committee Election',
    'description' => 'Vote for the classmates you want to lead the association for the next two years. One vote per position. Voting is secret and ends at midnight.',
    'starts_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
    'ends_at' => date('Y-m-d H:i:s', strtotime('+7 days')),
    'created_by' => $ids['demo'],
]);

Election::setStatus($election, 'open');

$president = ElectionPosition::create($election, 'President', 'Leads the association and represents members.', 1, 1);
$secretary = ElectionPosition::create($election, 'General Secretary', 'Keeps records, minutes and member communication.', 1, 2);
$treasurer = ElectionPosition::create($election, 'Treasurer', 'Manages dues, welfare funds and financial reporting.', 1, 3);

$candidates = [
    [$president, 'chioma', 'I will build a transparent welfare fund and a members-first calendar.'],
    [$president, 'emeka', 'A digital-first association with quarterly reports and stronger mentorship.'],
    [$secretary, 'fatima', 'Clear minutes, timely notices and a searchable member directory.'],
    [$secretary, 'ngozi', 'Better communication across our diaspora members.'],
    [$treasurer, 'seyi', 'Monthly statements and a fully auditable welfare ledger.'],
];

$candidateIds = [];
foreach ($candidates as [$positionId, $key, $manifesto]) {
    $candidateIds[$key] = ElectionCandidate::create($positionId, $ids[$key], $manifesto);
}

$voters = ['demo', 'admin', 'chioma', 'emeka', 'fatima', 'kelechi', 'ngozi', 'seyi'];

foreach ($voters as $index => $voter) {
    Vote::cast($election, $president, $index % 2 === 0 ? $candidateIds['chioma'] : $candidateIds['emeka'], $ids[$voter]);
    Vote::cast($election, $secretary, $index % 2 === 0 ? $candidateIds['fatima'] : $candidateIds['ngozi'], $ids[$voter]);

    if ($index < 6) {
        Vote::cast($election, $treasurer, $candidateIds['seyi'], $ids[$voter]);
    }
}

$pastElection = Election::create([
    'group_id' => $kings,
    'title' => '2024 Executive Committee Election',
    'description' => 'The previous executive committee election.',
    'starts_at' => date('Y-m-d H:i:s', strtotime('-2 years')),
    'ends_at' => date('Y-m-d H:i:s', strtotime('-2 years +7 days')),
    'created_by' => $ids['demo'],
]);

Election::setStatus($pastElection, 'published');
$pastChair = ElectionPosition::create($pastElection, 'Chairman', null, 1, 1);
$pastCandidate = ElectionCandidate::create($pastChair, $ids['demo'], 'Served as chairman from 2024 to 2026.');

foreach ($voters as $voter) {
    Vote::cast($pastElection, $pastChair, $pastCandidate, $ids[$voter]);
}

/* ------------------------------------------------------------ Audit logs */
AuditLog::record($kings, $ids['demo'], 'group.created', 'Created the alumni group', 'group', $kings);
AuditLog::record($kings, $ids['admin'], 'member.approved', 'Approved a membership request', 'membership', $membershipIds[$kings . '-chioma']);
AuditLog::record($kings, $ids['chioma'], 'announcement.created', 'Published a welfare announcement', 'announcement', null);
AuditLog::record($unilag, $ids['admin'], 'event.created', 'Created the homecoming event', 'event', $homecoming);

fwrite(STDOUT, "Classof seeded successfully.\n\n");
fwrite(STDOUT, "Sign in with:\n  admin@classof.test / password123  (platform admin)\n  demo@classof.test  / password123  (owner of King's College group)\n\n");
fwrite(STDOUT, "Open: http://localhost/classof/\n");
