<?php
$title = 'Members';
require BASE_PATH . '/app/Views/partials/group-header.php';
$statusTone = static fn (string $s): string => match ($s) {
    'active' => 'success',
    'pending' => 'warn',
    'suspended', 'rejected' => 'danger',
    default => 'neutral',
};
?>
<div class="page-head">
    <div>
        <h1>Members</h1>
        <p>The people who make up <?= e($group['name']) ?>.</p>
    </div>
    <div class="page-head-actions">
        <?php if ($isManager): ?>
            <a href="<?= url('groups/' . $group['slug'] . '/members/invite') ?>" class="btn btn-primary"><?= icon('plus', 16) ?> Invite classmates</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($isManager && $pendingCount > 0): ?>
    <div class="alert alert-warn">
        <span class="alert-icon"><?= icon('users', 18) ?></span>
        <div class="alert-body"><strong><?= $pendingCount ?></strong> membership <?= $pendingCount === 1 ? 'request is' : 'requests are' ?> waiting for review.</div>
        <a href="<?= url('groups/' . $group['slug'] . '/members?status=pending') ?>" class="btn btn-sm btn-outline">Show pending</a>
    </div>
<?php endif; ?>

<form method="get" action="<?= url('groups/' . $group['slug'] . '/members') ?>" class="filter-bar">
    <div class="search-field">
        <?= icon('search', 17) ?>
        <input type="search" name="q" value="<?= e($filters['search']) ?>" placeholder="Search by name, email or occupation…">
    </div>
    <select name="status" class="select">
        <option value="">All statuses</option>
        <?php foreach (['active' => 'Active', 'pending' => 'Pending', 'suspended' => 'Suspended', 'rejected' => 'Rejected'] as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= $filters['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="role" class="select">
        <option value="">All roles</option>
        <?php foreach ($roles as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= $filters['role'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-outline">Filter</button>
    <?php if ($filters['search'] !== '' || $filters['status'] !== '' || $filters['role'] !== ''): ?>
        <a href="<?= url('groups/' . $group['slug'] . '/members') ?>" class="btn btn-ghost">Clear</a>
    <?php endif; ?>
</form>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Member</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Proof</th>
                    <th>Joined / requested</th>
                    <?php if ($isManager): ?><th class="text-right">Manage</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php if ($members === []): ?>
                <tr><td colspan="<?= $isManager ? 6 : 5 ?>" class="empty-row">No members match these filters.</td></tr>
            <?php endif; ?>

            <?php foreach ($members as $member): ?>
                <?php
                $isSelf = (int) $member['user_id'] === (int) user()['id'];
                $proofState = App\Models\Membership::proofState($member);
                $proofTone = ['verified' => 'success', 'pending' => 'warn', 'rejected' => 'danger', 'none' => 'neutral'][$proofState] ?? 'neutral';
                $proofLabel = ['verified' => 'Verified', 'pending' => 'Submitted', 'rejected' => 'Rejected', 'none' => 'No proof'][$proofState] ?? 'No proof';
                ?>
                <tr>
                    <td>
                        <div class="table-person">
                            <?= avatar($member['name'], $member['avatar'] ?? null, 38) ?>
                            <div class="table-person-meta">
                                <strong><a href="<?= url('groups/' . $group['slug'] . '/members/' . $member['id']) ?>" style="color:inherit"><?= e($member['name']) ?></a><?= $isSelf ? ' (you)' : '' ?></strong>
                                <span><?= e($member['occupation'] ?: $member['email']) ?></span>
                                <?php if (!empty($member['location'])): ?>
                                    <span><?= e($member['location']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="flex" style="gap:6px;flex-wrap:wrap">
                            <span class="badge badge-<?= $member['role'] === 'owner' ? 'gold' : ($member['role'] === 'admin' ? 'info' : ($member['role'] === 'exco' ? 'success' : 'neutral')) ?>">
                                <?= e(role_label($member['role'])) ?>
                            </span>
                            <?php if (!empty($member['position_title'])): ?>
                                <span class="tiny muted"><?= e($member['position_title']) ?></span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td><span class="badge badge-<?= $statusTone($member['status']) ?>"><?= e(status_label($member['status'])) ?></span></td>
                    <td>
                        <?php if ($isManager || $isSelf): ?>
                            <span class="badge badge-<?= $proofTone ?>"><?= e($proofLabel) ?></span>
                        <?php else: ?>
                            <span class="tiny muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="muted small nowrap">
                        <?php if ($member['status'] === 'pending'): ?>
                            <?= e(time_ago($member['proof_submitted_at'] ?? $member['created_at'])) ?>
                        <?php else: ?>
                            <?= e(short_date($member['joined_at'])) ?>
                        <?php endif; ?>
                    </td>

                    <?php if ($isManager): ?>
                        <td>
                            <?php if ($member['status'] === 'pending'): ?>
                                <div class="table-actions">
                                    <a href="<?= url('groups/' . $group['slug'] . '/members/' . $member['id']) ?>" class="btn btn-primary btn-sm">
                                        <?= icon('shield', 14) ?> Review proof
                                    </a>
                                </div>
                            <?php elseif ($member['role'] === 'owner' || $isSelf): ?>
                                <div class="table-actions">
                                    <a href="<?= url('groups/' . $group['slug'] . '/members/' . $member['id']) ?>" class="btn btn-ghost btn-sm">View</a>
                                </div>
                            <?php else: ?>
                                <?php $canManageRow = $isOwner || $member['role'] !== 'admin'; ?>
                                <?php if ($canManageRow): ?>
                                    <div class="table-actions">
                                        <form method="post" action="<?= url('groups/' . $group['slug'] . '/members/' . $member['id'] . '/role') ?>" class="role-form">
                                            <?= csrf_field() ?>
                                            <select name="role" class="select" aria-label="Role for <?= e($member['name']) ?>">
                                                <?php foreach (['admin' => 'Admin', 'exco' => 'Exco', 'member' => 'Member'] as $key => $label): ?>
                                                    <?php if ($key === 'admin' && !$isOwner) { continue; } ?>
                                                    <option value="<?= e($key) ?>" <?= $member['role'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="text" name="position_title" value="<?= e($member['position_title']) ?>" placeholder="Title" style="width:104px" aria-label="Position title">
                                            <button type="submit" class="icon-btn" title="Save role" aria-label="Save role"><?= icon('check', 16) ?></button>
                                        </form>
                                        <form method="post" action="<?= url('groups/' . $group['slug'] . '/members/' . $member['id'] . '/remove') ?>" data-confirm="Remove <?= e($member['name']) ?> from the group?">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="icon-btn" title="Remove member" aria-label="Remove member" style="color:var(--danger)"><?= icon('trash', 15) ?></button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span class="tiny muted">Admin</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($isManager && $invites !== []): ?>
    <div class="card mt-3">
        <div class="card-header">
            <h2>Pending invitations</h2>
            <a href="<?= url('groups/' . $group['slug'] . '/members/invite') ?>" class="card-link">Manage invites</a>
        </div>
        <div class="card-body">
            <div class="list">
                <?php foreach (array_slice($invites, 0, 4) as $invite): ?>
                    <div class="list-item" style="align-items:center">
                        <span class="stat-icon" style="width:34px;height:34px;border-radius:9px"><?= icon('mail', 16) ?></span>
                        <div class="list-item-body">
                            <div class="list-item-title" style="font-size:13.5px"><?= e($invite['email']) ?></div>
                            <div class="list-item-meta"><?= e(role_label($invite['role'])) ?> · expires <?= e(short_date($invite['expires_at'])) ?></div>
                        </div>
                        <button type="button" class="btn btn-outline btn-sm" data-copy="<?= e(absolute_url('invite/' . $invite['token'])) ?>">Copy link</button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>
