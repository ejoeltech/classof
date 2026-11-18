<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\AuditLog;
use App\Models\CoopMember;
use App\Models\Cooperative;
use App\Models\Group;
use App\Models\Membership;

abstract class Controller
{
    /** @return array<string, mixed> */
    protected function findGroup(string $slug): array
    {
        $group = Group::findBySlug($slug);

        if ($group === null) {
            Response::abort(404, 'That alumni group does not exist.');
        }

        if (($group['status'] ?? 'active') !== 'active' && !Auth::isPlatformAdmin()) {
            Response::abort(404, 'That alumni group has been archived.');
        }

        return $group;
    }

    /**
     * Resolve the group and cooperative, plus the viewer's memberships.
     *
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null, 2: array<string, mixed>|null, 3: array<string, mixed>|null}
     */
    protected function coopContext(string $slug): array
    {
        $group = $this->findGroup($slug);
        $coop = Cooperative::forGroup((int) $group['id']);
        $groupMembership = Guard::membership((int) $group['id']);
        $coopMember = $coop !== null ? CoopMember::forUser((int) $coop['id'], (int) Auth::id()) : null;

        return [$group, $coop, $groupMembership, $coopMember];
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>, 2: array<string, mixed>|null, 3: array<string, mixed>|null}
     */
    protected function requireCoop(string $slug): array
    {
        [$group, $coop, $groupMembership, $coopMember] = $this->coopContext($slug);

        if ($coop === null) {
            Response::abort(404, 'This group does not have a cooperative yet.');
        }

        return [$group, $coop, $groupMembership, $coopMember];
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>, 2: array<string, mixed>|null, 3: array<string, mixed>|null}
     */
    protected function requireCoopOfficer(string $slug): array
    {
        [$group, $coop, $groupMembership, $coopMember] = $this->requireCoop($slug);

        if (!Cooperative::canManage($groupMembership, $coopMember)) {
            Response::abort(403, 'You are not a co-op officer or group admin.');
        }

        return [$group, $coop, $groupMembership, $coopMember];
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>, 2: array<string, mixed>|null, 3: array<string, mixed>}
     */
    protected function requireCoopMember(string $slug): array
    {
        [$group, $coop, $groupMembership, $coopMember] = $this->requireCoop($slug);

        if ($coopMember === null || $coopMember['status'] !== 'active') {
            Response::abort(403, 'You are not an active member of this cooperative.');
        }

        return [$group, $coop, $groupMembership, $coopMember];
    }

    /**
     * Resolve the group and require an active membership.
     *
     * @param array<int, string> $roles
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    protected function groupContext(string $slug, array $roles = []): array
    {
        $group = $this->findGroup($slug);
        $membership = Guard::requireMember((int) $group['id'], $roles);

        return [$group, $membership];
    }

    /** @param array<string, mixed> $data */
    protected function view(string $view, array $data = [], ?string $layout = 'layouts/app'): void
    {
        View::render($view, $data, $layout);
    }

    protected function redirect(string $path): never
    {
        Response::redirect($path);
    }

    protected function back(): never
    {
        Response::back();
    }

    /** @return array<string, mixed> */
    protected function currentUser(): array
    {
        $user = Auth::user();

        if ($user === null) {
            Response::redirect('login');
        }

        return $user;
    }

    /**
     * Validate the request, or flash the errors and redirect back.
     *
     * @param array<string, string> $rules
     * @return array<string, mixed>
     */
    protected function validate(array $rules): array
    {
        $data = Request::all();
        $errors = Validator::validate($data, $rules);

        if ($errors !== []) {
            Session::flash('errors', $errors);
            Session::flash('old', $data);
            Session::flash('error', 'Please correct the highlighted fields.');
            Response::back();
        }

        $validated = [];
        foreach (array_keys($rules) as $field) {
            $validated[$field] = $data[$field] ?? null;
        }

        return $validated;
    }

    /** @param array<string, string> $errors */
    protected function fail(array $errors, string $message = 'Please correct the highlighted fields.'): never
    {
        Session::flash('errors', $errors);
        Session::flash('old', Request::all());
        Session::flash('error', $message);
        Response::back();
    }

    protected function flashSuccess(string $message): void
    {
        Session::flash('status', $message);
    }

    protected function flashError(string $message): void
    {
        Session::flash('error', $message);
    }

    protected function audit(
        ?int $groupId,
        string $action,
        string $description,
        ?string $targetType = null,
        ?int $targetId = null
    ): void {
        AuditLog::record($groupId, Auth::id(), $action, $description, $targetType, $targetId);
    }

    /** @return array<string, mixed>|null */
    protected function membership(int $groupId): ?array
    {
        return Membership::forUser($groupId, (int) $this->currentUser()['id']);
    }

    /** @param array<int, string> $roles */
    protected function requireRole(int $groupId, array $roles = []): array
    {
        return Guard::requireMember($groupId, $roles);
    }
}
