<?php
$title = 'Groups · Admin';
$totalPages = max(1, (int) ceil($total / $perPage));
?>
<div class="page-head">
    <div>
        <h1>Groups</h1>
        <p><?= number_format($total) ?> alumni groups on the platform.</p>
    </div>
    <a href="<?= url('admin') ?>" class="btn btn-ghost">Back to console</a>
</div>

<form method="get" action="<?= url('admin/groups') ?>" class="filter-bar">
    <div class="search-field">
        <?= icon('search', 17) ?>
        <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search by group or institution…">
    </div>
    <button type="submit" class="btn btn-outline">Search</button>
    <?php if ($search !== ''): ?>
        <a href="<?= url('admin/groups') ?>" class="btn btn-ghost">Clear</a>
    <?php endif; ?>
</form>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr><th>Group</th><th>Affiliation</th><th>Members</th><th>Joining</th><th>Status</th><th>Created</th><th class="text-right">Actions</th></tr>
            </thead>
            <tbody>
            <?php if ($groups === []): ?>
                <tr><td colspan="7" class="empty-row">No groups found.</td></tr>
            <?php endif; ?>
            <?php foreach ($groups as $group): ?>
                <tr>
                    <td>
                        <div class="table-person">
                            <span class="group-dot" style="--dot: <?= e($group['cover_color'] ?: '#0b6e4f') ?>"><?= e(mb_substr((string) $group['name'], 0, 1)) ?></span>
                            <div class="table-person-meta">
                                <strong><?= e($group['name']) ?></strong>
                                <span><?= e($group['institution'] ?: $group['slug']) ?></span>
                            </div>
                        </div>
                    </td>
                    <td class="small">
                        <div style="font-weight:600"><?= e(App\Models\Group::scopeLabel($group['scope'] ?? 'class')) ?></div>
                        <span class="tiny muted"><?= e(App\Models\Group::typeLabel($group['type'])) ?> · <?= e(App\Models\Group::descriptor($group)) ?></span>
                    </td>
                    <td><?= (int) $group['member_count'] ?></td>
                    <td class="small"><?= $group['join_policy'] === 'invite' ? 'Invite only' : 'Proof + approval' ?></td>
                    <td><span class="badge badge-<?= $group['status'] === 'active' ? 'success' : 'neutral' ?>"><?= e(ucfirst($group['status'])) ?></span></td>
                    <td class="small muted nowrap"><?= e(short_date($group['created_at'])) ?></td>
                    <td>
                        <div class="table-actions">
                            <form method="post" action="<?= url('admin/groups/' . $group['id'] . '/status') ?>" data-confirm="<?= $group['status'] === 'active' ? 'Archive this group? Members will lose access.' : 'Restore this group?' ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-<?= $group['status'] === 'active' ? 'ghost' : 'primary' ?> btn-sm">
                                    <?= $group['status'] === 'active' ? 'Archive' : 'Restore' ?>
                                </button>
                            </form>
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
                <a href="<?= url('admin/groups?page=' . ($page - 1) . ($search !== '' ? '&q=' . urlencode($search) : '')) ?>" class="btn btn-outline btn-sm">Previous</a>
            <?php endif; ?>
            <?php if ($page < $totalPages): ?>
                <a href="<?= url('admin/groups?page=' . ($page + 1) . ($search !== '' ? '&q=' . urlencode($search) : '')) ?>" class="btn btn-outline btn-sm">Next</a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
