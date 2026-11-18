<?php
$title = 'Users · Admin';
$totalPages = max(1, (int) ceil($total / $perPage));
?>
<div class="page-head">
    <div>
        <h1>Users</h1>
        <p><?= number_format($total) ?> registered accounts.</p>
    </div>
    <a href="<?= url('admin') ?>" class="btn btn-ghost">Back to console</a>
</div>

<form method="get" action="<?= url('admin/users') ?>" class="filter-bar">
    <div class="search-field">
        <?= icon('search', 17) ?>
        <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search by name or email…">
    </div>
    <button type="submit" class="btn btn-outline">Search</button>
    <?php if ($search !== ''): ?>
        <a href="<?= url('admin/users') ?>" class="btn btn-ghost">Clear</a>
    <?php endif; ?>
</form>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr><th>Member</th><th>Platform role</th><th>Status</th><th>Groups</th><th>Joined</th><th class="text-right">Actions</th></tr>
            </thead>
            <tbody>
            <?php if ($users === []): ?>
                <tr><td colspan="6" class="empty-row">No users found.</td></tr>
            <?php endif; ?>
            <?php foreach ($users as $account): ?>
                <?php
                $groupCount = (int) \App\Core\Database::value(
                    'SELECT COUNT(*) FROM memberships WHERE user_id = ? AND status = "active"',
                    [(int) $account['id']],
                    0
                );
                $isSelf = (int) $account['id'] === (int) user()['id'];
                ?>
                <tr>
                    <td>
                        <div class="table-person">
                            <?= avatar($account['name'], $account['avatar'] ?? null, 34) ?>
                            <div class="table-person-meta">
                                <strong><?= e($account['name']) ?><?= $isSelf ? ' (you)' : '' ?></strong>
                                <span><?= e($account['email']) ?></span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-<?= ($account['platform_role'] ?? 'user') === 'admin' ? 'gold' : 'neutral' ?>">
                            <?= ($account['platform_role'] ?? 'user') === 'admin' ? 'Admin' : 'Member' ?>
                        </span>
                    </td>
                    <td><span class="badge badge-<?= $account['status'] === 'active' ? 'success' : 'danger' ?>"><?= e(ucfirst($account['status'])) ?></span></td>
                    <td><?= $groupCount ?></td>
                    <td class="small muted nowrap"><?= e(short_date($account['created_at'])) ?></td>
                    <td>
                        <div class="table-actions">
                            <?php if (!$isSelf): ?>
                                <form method="post" action="<?= url('admin/users/' . $account['id'] . '/status') ?>" data-confirm="<?= $account['status'] === 'active' ? 'Suspend this account? They will be signed out and unable to log in.' : 'Reactivate this account?' ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-<?= $account['status'] === 'active' ? 'ghost' : 'primary' ?> btn-sm">
                                        <?= $account['status'] === 'active' ? 'Suspend' : 'Activate' ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="tiny muted">—</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
    <div class="between mt-3">
        <span class="muted small">Page <?= $page ?> of <?= $totalPages ?></span>
        <div class="flex">
            <?php if ($page > 1): ?>
                <a href="<?= url('admin/users?page=' . ($page - 1) . ($search !== '' ? '&q=' . urlencode($search) : '')) ?>" class="btn btn-outline btn-sm">Previous</a>
            <?php endif; ?>
            <?php if ($page < $totalPages): ?>
                <a href="<?= url('admin/users?page=' . ($page + 1) . ($search !== '' ? '&q=' . urlencode($search) : '')) ?>" class="btn btn-outline btn-sm">Next</a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
