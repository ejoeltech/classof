<?php
$title = $dividend['title'];
$membership = $groupMembership ?? null;
require BASE_PATH . '/app/Views/partials/group-header.php';
$allocated = array_sum(array_map(static fn (array $a): float => (float) $a['amount'], $allocations));
?>
<div class="page-head">
    <div>
        <a href="<?= url('groups/' . $group['slug'] . '/coop/dividends') ?>" class="tiny muted" style="display:inline-flex;align-items:center;gap:4px;margin-bottom:6px"><?= icon('chevron-right', 13) ?> All dividends</a>
        <h1><?= e($dividend['title']) ?></h1>
        <p><?= e($dividend['period'] ?: 'No period') ?> · based on <?= e($dividend['basis']) ?></p>
    </div>
    <div class="page-head-actions">
        <span class="badge badge-<?= $dividend['status'] === 'distributed' ? 'success' : 'warn' ?>"><?= e(ucfirst($dividend['status'])) ?></span>
        <?php if ($isOfficer && $dividend['status'] !== 'distributed'): ?>
            <form method="post" action="<?= url('groups/' . $group['slug'] . '/coop/dividends/' . $dividend['id'] . '/distribute') ?>" data-confirm="Distribute this dividend? Transactions will be recorded for every member.">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-primary">Distribute dividend</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="stat-grid mb-3" style="grid-template-columns:repeat(3,1fr)">
    <div class="stat-card"><span class="stat-icon gold"><?= icon('card', 20) ?></span><div class="stat-meta"><span class="stat-label">Total declared</span><span class="stat-value"><?= money($dividend['total_amount'], $group['currency']) ?></span></div></div>
    <div class="stat-card"><span class="stat-icon"><?= icon('users', 20) ?></span><div class="stat-meta"><span class="stat-label">Members</span><span class="stat-value"><?= count($allocations) ?></span></div></div>
    <div class="stat-card"><span class="stat-icon info"><?= icon('chart', 20) ?></span><div class="stat-meta"><span class="stat-label">Allocated</span><span class="stat-value"><?= money($allocated, $group['currency']) ?></span><span class="stat-hint"><?= $dividend['distributed_at'] ? 'Distributed ' . e(short_date($dividend['distributed_at'])) : 'Awaiting distribution' ?></span></div></div>
</div>

<div class="card">
    <div class="card-header"><h2>Allocations</h2><span class="badge badge-neutral"><?= count($allocations) ?></span></div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Member</th><th><?= e(ucfirst($dividend['basis'])) ?> basis</th><th>Dividend</th></tr></thead>
            <tbody>
            <?php if ($allocations === []): ?>
                <tr><td colspan="3" class="empty-row">No allocations.</td></tr>
            <?php endif; ?>
            <?php foreach ($allocations as $allocation): ?>
                <tr>
                    <td>
                        <div class="table-person">
                            <?= avatar($allocation['name'], $allocation['avatar'] ?? null, 30) ?>
                            <div class="table-person-meta"><strong><?= e($allocation['name']) ?></strong><span><?= e($allocation['member_no']) ?></span></div>
                        </div>
                    </td>
                    <td><?= $dividend['basis'] === 'shares' ? e((float) $allocation['basis_amount']) . ' units' : money($allocation['basis_amount'], $group['currency']) ?></td>
                    <td class="strong"><?= money($allocation['amount'], $group['currency']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
