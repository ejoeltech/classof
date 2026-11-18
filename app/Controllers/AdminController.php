<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\AuditLog;
use App\Models\Group;
use App\Models\GroupRequest;
use App\Models\GroupSite;
use App\Models\Institution;
use App\Models\Membership;
use App\Models\NotificationDelivery;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\WhatsAppService;

final class AdminController extends Controller
{
    public function index(): void
    {
        $stats = [
            'users' => (int) Database::value('SELECT COUNT(*) FROM users', [], 0),
            'groups' => (int) Database::value('SELECT COUNT(*) FROM `alumni_groups`', [], 0),
            'active_groups' => (int) Database::value('SELECT COUNT(*) FROM `alumni_groups` WHERE status = "active"', [], 0),
            'members' => (int) Database::value('SELECT COUNT(*) FROM memberships WHERE status = "active"', [], 0),
            'collected' => (float) Database::value('SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = "success"', [], 0),
            'welfare' => (float) Database::value('SELECT COALESCE(SUM(amount), 0) FROM welfare_contributions', [], 0),
            'elections' => (int) Database::value('SELECT COUNT(*) FROM elections', [], 0),
            'votes' => (int) Database::value('SELECT COUNT(*) FROM votes', [], 0),
            'new_users' => (int) Database::value('SELECT COUNT(*) FROM users WHERE created_at >= ?', [date('Y-m-d H:i:s', strtotime('-30 days'))], 0),
            'pending_institutions' => (int) Database::value('SELECT COUNT(*) FROM institutions WHERE status = "pending"', [], 0),
            'pending_group_requests' => (int) Database::value('SELECT COUNT(*) FROM group_requests WHERE status = "pending"', [], 0),
            'pending_education' => (int) Database::value('SELECT COUNT(*) FROM education_records WHERE status = "unverified"', [], 0),
        ];

        $this->view('admin/index', [
            'stats' => $stats,
            'recentGroups' => Database::fetchAll(
                'SELECT g.*, (SELECT COUNT(*) FROM memberships m WHERE m.group_id = g.id AND m.status = "active") AS member_count
                 FROM `alumni_groups` g ORDER BY g.created_at DESC LIMIT 6'
            ),
            'recentUsers' => Database::fetchAll('SELECT * FROM users ORDER BY created_at DESC LIMIT 6'),
            'activity' => AuditLog::recent(null, 12),
        ]);
    }

    public function whatsapp(): void
    {
        $this->view('admin/whatsapp', [
            'counts' => NotificationDelivery::counts(),
            'recent' => NotificationDelivery::recent(60),
            'driver' => WhatsAppService::driver(),
            'configured' => WhatsAppService::cloudConfigured(),
            'enabled' => PlatformSetting::bool('whatsapp.enabled'),
        ]);
    }

    public function retryMessage(string $id): void
    {
        $delivery = NotificationDelivery::find((int) $id);

        if ($delivery === null) {
            \App\Core\Response::abort(404, 'That delivery does not exist.');
        }

        NotificationDelivery::retry((int) $delivery['id']);
        AuditLog::record((int) ($delivery['group_id'] ?? 0) ?: null, (int) user()['id'], 'whatsapp.retry', 'Retried WhatsApp delivery #' . $delivery['id'], 'notification_delivery', (int) $delivery['id']);
        $this->flashSuccess('Delivery queued for retry.');
        $this->back();
    }

    public function cancelMessage(string $id): void
    {
        $delivery = NotificationDelivery::find((int) $id);

        if ($delivery === null) {
            \App\Core\Response::abort(404, 'That delivery does not exist.');
        }

        NotificationDelivery::cancel((int) $delivery['id']);
        $this->flashSuccess('Delivery cancelled.');
        $this->back();
    }

    public function settings(): void
    {        PlatformSetting::seedDefaults();

        $this->view('admin/settings', [
            'definitions' => PlatformSetting::defaults(),
            'settings' => PlatformSetting::all(),
        ]);
    }

    public function updateSettings(): void
    {
        $defaults = PlatformSetting::defaults();

        foreach ($defaults as $key => $definition) {
            $value = $definition['type'] === 'boolean'
                ? (Request::boolean(str_replace('.', '_', $key)) ? '1' : '0')
                : (string) Request::input(str_replace('.', '_', $key), $definition['value']);

            PlatformSetting::set($key, $value, $definition['type'], (int) user()['id']);
        }

        AuditLog::record(null, (int) user()['id'], 'settings.updated', 'Updated platform settings', 'platform_settings', null);
        $this->flashSuccess('Platform settings saved.');
        $this->redirect('admin/settings');
    }

    public function education(): void
    {
        $this->view('admin/education', [
            'pending' => Database::fetchAll(
                'SELECT e.*, u.name AS member_name, u.email AS member_email,
                        i.name AS institution_name, f.name AS faculty_name, d.name AS department_name
                 FROM education_records e
                 INNER JOIN users u ON u.id = e.user_id
                 LEFT JOIN institutions i ON i.id = e.institution_id
                 LEFT JOIN faculties f ON f.id = e.faculty_id
                 LEFT JOIN departments d ON d.id = e.department_id
                 WHERE e.status = "unverified"
                 ORDER BY e.created_at ASC'
            ),
            'reviewed' => Database::fetchAll(
                'SELECT e.*, u.name AS member_name, i.name AS institution_name,
                        r.name AS reviewer_name
                 FROM education_records e
                 INNER JOIN users u ON u.id = e.user_id
                 LEFT JOIN institutions i ON i.id = e.institution_id
                 LEFT JOIN users r ON r.id = e.verified_by
                 WHERE e.status IN ("verified", "rejected")
                 ORDER BY e.verified_at DESC
                 LIMIT 20'
            ),
        ]);
    }

    public function educationStatus(string $id): void
    {
        $record = Database::fetch(
            'SELECT e.*, u.name AS member_name FROM education_records e
             INNER JOIN users u ON u.id = e.user_id
             WHERE e.id = ? LIMIT 1',
            [(int) $id]
        );

        if ($record === null) {
            \App\Core\Response::abort(404, 'That education record does not exist.');
        }

        $action = Request::string('action');

        if ($action === 'approve') {
            Database::update('education_records', [
                'status' => 'verified',
                'verified_by' => (int) user()['id'],
                'verified_at' => date('Y-m-d H:i:s'),
                'rejection_reason' => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['id' => (int) $record['id']]);

            AuditLog::record(null, (int) user()['id'], 'education.verified', 'Confirmed ' . $record['member_name'] . "'s education record", 'education_record', (int) $record['id']);
            $this->flashSuccess('Education record confirmed.');
        } elseif ($action === 'reject') {
            $reason = Request::string('rejection_reason');

            Database::update('education_records', [
                'status' => 'rejected',
                'verified_by' => (int) user()['id'],
                'verified_at' => date('Y-m-d H:i:s'),
                'rejection_reason' => $reason !== '' ? mb_substr($reason, 0, 255) : null,
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['id' => (int) $record['id']]);

            AuditLog::record(null, (int) user()['id'], 'education.rejected', 'Rejected ' . $record['member_name'] . "'s education record", 'education_record', (int) $record['id']);
            $this->flashSuccess('Education record rejected.');
        } else {
            $this->flashError('Unknown education action.');
        }

        $this->back();
    }

    public function institutions(): void
    {        $this->view('admin/institutions', [
            'pending' => Institution::pending(),
            'reviewed' => Database::fetchAll(
                'SELECT i.*, u.name AS reviewer_name
                 FROM institutions i
                 LEFT JOIN users u ON u.id = i.reviewed_by
                 WHERE i.status <> "pending"
                 ORDER BY i.reviewed_at DESC
                 LIMIT 20'
            ),
        ]);
    }

    public function institutionStatus(string $id): void
    {
        $institution = Institution::find((int) $id);

        if ($institution === null) {
            \App\Core\Response::abort(404, 'That institution does not exist.');
        }

        $action = Request::string('action');

        if ($action === 'approve') {
            Institution::approve((int) $institution['id'], (int) user()['id']);
            AuditLog::record(null, (int) user()['id'], 'institution.approved', 'Approved institution "' . $institution['name'] . '"', 'institution', (int) $institution['id']);
            $this->flashSuccess($institution['name'] . ' is now verified and selectable.');
        } elseif ($action === 'reject') {
            $reason = Request::string('rejection_reason');
            Institution::reject((int) $institution['id'], (int) user()['id'], $reason !== '' ? mb_substr($reason, 0, 255) : null);
            AuditLog::record(null, (int) user()['id'], 'institution.rejected', 'Rejected institution "' . $institution['name'] . '"', 'institution', (int) $institution['id']);
            $this->flashSuccess('Institution request rejected.');
        } else {
            $this->flashError('Unknown institution action.');
        }

        $this->back();
    }

    public function groupRequests(): void
    {
        $this->view('admin/group-requests', [
            'pending' => GroupRequest::pending(),
            'reviewed' => Database::fetchAll(
                'SELECT r.*, i.name AS institution_name, u.name AS requester_name,
                        g.slug AS created_group_slug
                 FROM group_requests r
                 INNER JOIN institutions i ON i.id = r.institution_id
                 INNER JOIN users u ON u.id = r.user_id
                 LEFT JOIN `alumni_groups` g ON g.id = r.created_group_id
                 WHERE r.status <> "pending"
                 ORDER BY r.reviewed_at DESC
                 LIMIT 20'
            ),
        ]);
    }

    public function groupRequestStatus(string $id): void
    {
        $request = GroupRequest::find((int) $id);

        if ($request === null) {
            \App\Core\Response::abort(404, 'That group request does not exist.');
        }

        if ($request['status'] !== 'pending') {
            $this->flashError('That group request has already been reviewed.');
            $this->redirect('admin/group-requests');
        }

        $action = Request::string('action');

        if ($action === 'approve') {
            $institution = Institution::find((int) $request['institution_id']);

            if ($institution === null || $institution['status'] !== 'verified') {
                $this->flashError('Verify the institution before approving this group.');
                $this->redirect('admin/group-requests');
            }

            $group = null;

            Database::transaction(function () use ($request, $institution, &$group): void {
                $groupId = Group::create([
                    'name' => $request['name'],
                    'type' => $request['type'],
                    'scope' => $request['scope'],
                    'institution_id' => (int) $institution['id'],
                    'institution' => $institution['name'],
                    'faculty' => $request['faculty'],
                    'department' => $request['department'],
                    'faculty_id' => $request['faculty_id'] ?? null,
                    'department_id' => $request['department_id'] ?? null,
                    'graduation_year' => $request['graduation_year'],
                    'country' => $institution['country_code'] === 'NG' ? 'Nigeria' : $institution['country_code'],
                    'currency' => config('payments.currency', 'NGN'),
                    'description' => $request['description'],
                    'visibility' => 'public',
                    'join_policy' => 'approval',
                    'verification_status' => 'verified',
                    'created_by' => (int) $request['user_id'],
                ]);

                Membership::create([
                    'group_id' => $groupId,
                    'user_id' => (int) $request['user_id'],
                    'role' => 'owner',
                    'position_title' => 'Founder',
                    'status' => 'active',
                    'approved_by' => (int) user()['id'],
                    'reviewed_by' => (int) user()['id'],
                    'proof_type' => 'founder',
                    'proof_reference' => 'Approved group request #' . $request['id'],
                    'proof_note' => 'Created after platform verification of the group request.',
                ]);

                GroupRequest::approve((int) $request['id'], (int) user()['id'], $groupId);

                if ($groupRow = Group::find($groupId)) {
                    GroupSite::forGroup($groupRow);
                }

                $group = Group::find($groupId);
            });

            AuditLog::record(
                $group !== null ? (int) $group['id'] : null,
                (int) user()['id'],
                'group.request_approved',
                'Approved group request "' . $request['name'] . '"',
                'group_request',
                (int) $request['id']
            );

            $this->flashSuccess('Group approved and published. The requester is now the owner.');
            $this->redirect('admin/group-requests');
        }

        if ($action === 'reject') {
            $reason = Request::string('rejection_reason');
            GroupRequest::reject((int) $request['id'], (int) user()['id'], $reason !== '' ? mb_substr($reason, 0, 255) : null);
            AuditLog::record(null, (int) user()['id'], 'group.request_rejected', 'Rejected group request "' . $request['name'] . '"', 'group_request', (int) $request['id']);
            $this->flashSuccess('Group request rejected.');
            $this->redirect('admin/group-requests');
        }

        $this->flashError('Unknown group request action.');
        $this->redirect('admin/group-requests');
    }

    public function users(): void
    {        $search = trim((string) Request::input('q', ''));
        $page = max(1, Request::int('page', 1));
        $perPage = 20;

        $this->view('admin/users', [
            'users' => User::paginate($page, $perPage, $search),
            'total' => User::count($search),
            'page' => $page,
            'perPage' => $perPage,
            'search' => $search,
        ]);
    }

    public function userStatus(string $id): void
    {
        $target = User::find((int) $id);

        if ($target === null) {
            \App\Core\Response::abort(404, 'That user does not exist.');
        }

        if ((int) $target['id'] === (int) user()['id']) {
            $this->flashError('You cannot suspend your own account.');
            $this->back();
        }

        $status = $target['status'] === 'active' ? 'suspended' : 'active';
        User::update(['status' => $status], ['id' => (int) $target['id']]);

        AuditLog::record(null, (int) user()['id'], 'user.status_changed', ucfirst($status) . ' the account of ' . $target['name'], 'user', (int) $target['id']);

        $this->flashSuccess($target['name'] . ' is now ' . $status . '.');
        $this->back();
    }

    public function groups(): void
    {
        $search = trim((string) Request::input('q', ''));
        $page = max(1, Request::int('page', 1));
        $perPage = 20;

        $this->view('admin/groups', [
            'groups' => Group::paginate($page, $perPage, $search),
            'total' => Group::count($search),
            'page' => $page,
            'perPage' => $perPage,
            'search' => $search,
        ]);
    }

    public function groupStatus(string $id): void
    {
        $target = Group::find((int) $id);

        if ($target === null) {
            \App\Core\Response::abort(404, 'That group does not exist.');
        }

        $status = $target['status'] === 'active' ? 'archived' : 'active';
        Database::update('alumni_groups', [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => (int) $target['id']]);

        AuditLog::record((int) $target['id'], (int) user()['id'], 'group.status_changed', ucfirst($status) . ' the group "' . $target['name'] . '"', 'group', (int) $target['id']);

        $this->flashSuccess($target['name'] . ' is now ' . $status . '.');
        $this->back();
    }
}
