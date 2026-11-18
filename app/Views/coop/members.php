<?php
$title = 'Co-op members';
$membership = $groupMembership ?? null;
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
        <h1>Co-op members</h1>
        <p>Shareholders and savers in <?= e($coop['name']) ?>.</p>
    </div>
    <div class="page-head-actions">
        <a href="<?= url('groups/' . $group['slug'] . '/coop/transactions') ?>" class="btn btn-outline"><?= icon('wallet', 15) ?> Ledger</a>
        <?php if ($isOfficer): ?>
            <a href="<?= url('groups/' . $group['slug'] . '/coop/settings') ?>" class="btn btn-ghost">Settings</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($isOfficer && $pendingCount > 0): ?>
    <div class="alert alert-warn">
        <span class="alert-icon"><?= icon('users', 18) ?></span>
        <div class="alert-body"><strong><?= $pendingCount ?></strong> co-op request(s) awaiting approval.</div>
        <a href="<?= url('groups/' . $group['slug'] . '/coop/members?status=pending') ?>" class="btn btn-sm btn-outline">Show pending</a>
    </div>
<?php endif; ?>

<form method="get" action="<?= url('groups/' . $group['slug'] . '/coop/members') ?>" class="filter-bar">
    <div class="search-field">
        <?= icon('search', 17) ?>
        <input type="search" name="q" value="<?= e($filters['search']) ?>" placeholder="Search name, email or member number…">
    </div>
    <select name="status" class="select">
        <option value="">All statuses</option>
        <?php foreach (['active' => 'Active', 'pending' => 'Pending', 'suspended' => 'Suspended', 'rejected' => 'Rejected', 'exited' => 'Exited'] as $key => $label): ?>
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
</form>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr><th>Member</th><th>Role</th><th>Savings</th><th>Shares</th><th>Status</th><?php if ($isOfficer): ?><th class="text-right">Manage</th><?php endif; ?></tr>
            </thead>
            <tbody>
            <?php if ($members === []): ?>
                <tr><td colspan="<?= $isOfficer ? 6 : 5 ?>" class="empty-row">No co-op members match these filters.</td></tr>
            <?php endif; ?>
            <?php foreach ($members as $member): ?>
                <?php $isSelf = (int) $member['user_id'] === (int) user()['id']; ?>
                <tr>
                    <td>
                        <div class="table-person">
                            <?= avatar($member['name'], $member['avatar'] ?? null, 36) ?>
                            <div class="table-person-meta">
                                <strong><?= e($member['name']) ?><?= $isSelf ? ' (you)' : '' ?></strong>
                                <span><?= e($member['member_no'] ?: $member['email']) ?></span>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge badge-<?= $member['role'] === 'coordinator' ? 'gold' : ($member['role'] === 'member' ? 'neutral' : 'info') ?>"><?= e(App\Models\Cooperative::roleLabel($member['role'])) ?></span></td>
                    <td class="strong"><?= money($member['savings_balance'] ?? 0, $group['currency']) ?></td>
                    <td><?= e((float) $member['shares']) ?> units</td>
                    <td><span class="badge badge-<?= $statusTone($member['status']) ?>"><?= e(status_label($member['status'])) ?></span></td>
                    <?php if ($isOfficer): ?>
                        <td>
                            <?php if ($member['status'] === 'pending'): ?>
                                <div class="table-actions">
                                    <form method="post" action="<?= url('groups/' . $group['slug'] . '/coop/members/' . $member['id'] . '/approve') ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-primary btn-sm">Approve</button>
                                    </form>
                                    <form method="post" action="<?= url('groups/' . $group['slug'] . '/coop/members/' . $member['id'] . '/reject') ?>" data-confirm="Reject this co-op request?">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-outline btn-sm">Reject</button>
                                    </form>
                                </div>
                            <?php elseif ($member['status'] === 'active'): ?>
                                <div class="table-actions">
                                    <form method="post" action="<?= url('groups/' . $group['slug'] . '/coop/members/' . $member['id'] . '/role') ?>" class="role-form">
                                        <?= csrf_field() ?>
                                        <select name="role" class="select" aria-label="Co-op role for <?= e($member['name']) ?>">
                                            <?php foreach ($roles as $key => $label): ?>
                                                <option value="<?= e($key) ?>" <?= $member['role'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="icon-btn" title="Save role" aria-label="Save role"><?= icon('check', 16) ?></button>
                                    </form>
                                    <?php if (!$isSelf): ?>
                                        <form method="post" action="<?= url('groups/' . $group['slug'] . '/coop/members/' . $member['id'] . '/remove') ?>" data-confirm="Mark this member as exited? Their financial history is kept.">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="icon-btn" title="Remove member" aria-label="Remove member" style="color:var(--danger)"><?= icon('trash', 15) ?></button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <span class="tiny muted">—</span>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
