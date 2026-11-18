<?php
$title = 'Co-op fines';
$membership = $groupMembership ?? null;
require BASE_PATH . '/app/Views/partials/group-header.php';
$statusTone = static fn (string $s): string => match ($s) {
    'paid' => 'success',
    'unpaid' => 'warn',
    'waived' => 'neutral',
    default => 'neutral',
};
?>
<div class="page-head">
    <div>
        <h1>Co-op fines</h1>
        <p>Attendance and conduct fines, with a full payment trail.</p>
    </div>
    <a href="<?= url('groups/' . $group['slug'] . '/coop') ?>" class="btn btn-ghost">Back to co-op</a>
</div>

<div class="stat-grid mb-3" style="grid-template-columns:repeat(3,1fr)">
    <div class="stat-card"><span class="stat-icon warn"><?= icon('alert', 20) ?></span><div class="stat-meta"><span class="stat-label">Unpaid fines</span><span class="stat-value"><?= money($unpaidTotal, $group['currency']) ?></span></div></div>
    <div class="stat-card"><span class="stat-icon"><?= icon('card', 20) ?></span><div class="stat-meta"><span class="stat-label">Fines listed</span><span class="stat-value"><?= count($fines) ?></span></div></div>
    <div class="stat-card"><span class="stat-icon info"><?= icon('users', 20) ?></span><div class="stat-meta"><span class="stat-label">Absence fine</span><span class="stat-value"><?= money($coop['fine_absent'], $group['currency']) ?></span><span class="stat-hint">Per meeting</span></div></div>
</div>

<div class="split-main">
    <div class="stack">
        <?php if ($isOfficer): ?>
            <form method="get" action="<?= url('groups/' . $group['slug'] . '/coop/fines') ?>" class="filter-bar">
                <select name="status" class="select">
                    <option value="">All statuses</option>
                    <?php foreach (['unpaid' => 'Unpaid', 'paid' => 'Paid', 'waived' => 'Waived'] as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= $filters['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-outline">Filter</button>
            </form>
        <?php endif; ?>

        <div class="card">
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><?php if ($isOfficer): ?><th>Member</th><?php endif; ?><th>Reason</th><th>Amount</th><th>Status</th><th>Issued</th><th class="text-right">Action</th></tr></thead>
                    <tbody>
                    <?php if ($fines === []): ?>
                        <tr><td colspan="<?= $isOfficer ? 6 : 5 ?>" class="empty-row">No fines recorded.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($fines as $fine): ?>
                        <tr>
                            <?php if ($isOfficer): ?>
                                <td>
                                    <div class="table-person">
                                        <?= avatar($fine['member_name'], $fine['avatar'] ?? null, 30) ?>
                                        <div class="table-person-meta"><strong><?= e($fine['member_name']) ?></strong><span><?= e($fine['member_no'] ?? '') ?></span></div>
                                    </div>
                                </td>
                            <?php endif; ?>
                            <td>
                                <div style="font-weight:600;font-size:13.5px"><?= e($fine['reason']) ?></div>
                                <?php if (!empty($fine['meeting_title'])): ?><span class="tiny muted"><?= e($fine['meeting_title']) ?></span><?php endif; ?>
                            </td>
                            <td class="strong"><?= money($fine['amount'], $group['currency']) ?></td>
                            <td><span class="badge badge-<?= $statusTone($fine['status']) ?>"><?= e(ucfirst($fine['status'])) ?></span></td>
                            <td class="small muted nowrap"><?= e(short_date($fine['issued_at'])) ?></td>
                            <td>
                                <div class="table-actions">
                                    <?php if ($fine['status'] === 'unpaid'): ?>
                                        <?php if ($isOfficer): ?>
                                            <form method="post" action="<?= url('groups/' . $group['slug'] . '/coop/fines/' . $fine['id'] . '/paid') ?>" data-confirm="Mark this fine as paid in cash?">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-primary btn-sm">Mark paid</button>
                                            </form>
                                            <form method="post" action="<?= url('groups/' . $group['slug'] . '/coop/fines/' . $fine['id'] . '/waive') ?>" data-confirm="Waive this fine?">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-ghost btn-sm">Waive</button>
                                            </form>
                                        <?php elseif ((int) $fine['member_id'] === (int) $coopMember['id']): ?>
                                            <form method="post" action="<?= url('payments/initiate') ?>" data-submit-once>
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="purpose" value="coop_fine">
                                                <input type="hidden" name="group_id" value="<?= (int) $group['id'] ?>">
                                                <input type="hidden" name="coop_fine_id" value="<?= (int) $fine['id'] ?>">
                                                <button type="submit" class="btn btn-primary btn-sm">Pay fine</button>
                                            </form>
                                        <?php endif; ?>
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
    </div>

    <?php if ($isOfficer): ?>
        <div class="stack">
            <section class="card">
                <div class="card-header"><h2>Issue a fine</h2></div>
                <div class="card-body">
                    <form method="post" action="<?= url('groups/' . $group['slug'] . '/coop/fines') ?>" class="stack-sm" data-submit-once>
                        <?= csrf_field() ?>
                        <div class="field">
                            <label for="member_id">Member</label>
                            <select id="member_id" name="member_id" class="select" required>
                                <option value="">Choose member…</option>
                                <?php foreach ($members as $member): ?>
                                    <option value="<?= (int) $member['id'] ?>"><?= e($member['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="reason">Reason</label>
                            <input type="text" id="reason" name="reason" required maxlength="255" placeholder="e.g. Late to meeting">
                        </div>
                        <div class="field">
                            <label for="amount">Amount</label>
                            <input type="number" id="amount" name="amount" min="0.01" step="0.01" required value="<?= e($coop['fine_absent']) ?>">
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">Issue fine</button>
                    </form>
                </div>
            </section>
        </div>
    <?php endif; ?>
</div>
