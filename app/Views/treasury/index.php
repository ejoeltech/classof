<?php
$title = 'Treasury';
require BASE_PATH . '/app/Views/partials/group-header.php';
$statusTone = static fn (string $s): string => match ($s) {
    'success' => 'success',
    'pending' => 'warn',
    'failed', 'refunded' => 'danger',
    default => 'neutral',
};
?>
<div class="page-head">
    <div>
        <h1>Treasury</h1>
        <p>Every naira that enters the group, in one auditable ledger.</p>
    </div>
    <div class="page-head-actions">
        <a href="<?= url('groups/' . $group['slug'] . '/treasury/export') ?>" class="btn btn-outline"><?= icon('download', 16) ?> Export CSV</a>
    </div>
</div>

<div class="stat-grid mb-3">
    <div class="stat-card">
        <span class="stat-icon"><?= icon('wallet', 20) ?></span>
        <div class="stat-meta">
            <span class="stat-label">Total collected</span>
            <span class="stat-value"><?= money($summary['collected'], $group['currency']) ?></span>
            <span class="stat-hint">All successful payments</span>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon info"><?= icon('card', 20) ?></span>
        <div class="stat-meta">
            <span class="stat-label">Dues</span>
            <span class="stat-value"><?= money($summary['dues'], $group['currency']) ?></span>
            <span class="stat-hint">Membership levies</span>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon gold"><?= icon('heart', 20) ?></span>
        <div class="stat-meta">
            <span class="stat-label">Welfare</span>
            <span class="stat-value"><?= money($treasury['welfare'], $group['currency']) ?></span>
            <span class="stat-hint">Contributions received</span>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon warn"><?= icon('clock', 20) ?></span>
        <div class="stat-meta">
            <span class="stat-label">Pending</span>
            <span class="stat-value"><?= money($summary['pending'], $group['currency']) ?></span>
            <span class="stat-hint"><?= (int) $pendingCount ?> awaiting confirmation</span>
        </div>
    </div>
    <?php if (($summary['coop'] ?? 0) > 0): ?>
        <div class="stat-card">
            <span class="stat-icon info"><?= icon('card', 20) ?></span>
            <div class="stat-meta">
                <span class="stat-label">Co-op (via gateway)</span>
                <span class="stat-value"><?= money($summary['coop'], $group['currency']) ?></span>
                <span class="stat-hint">Recorded in the co-op ledger</span>
            </div>
        </div>
    <?php endif; ?>
</div>

<form method="get" action="<?= url('groups/' . $group['slug'] . '/treasury') ?>" class="filter-bar">
    <div class="search-field">
        <?= icon('search', 17) ?>
        <input type="search" name="q" value="<?= e($filters['search']) ?>" placeholder="Search reference or member…">
    </div>
    <select name="purpose" class="select">
        <option value="">All purposes</option>
        <?php foreach ($purposes as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= $filters['purpose'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="status" class="select">
        <option value="">All statuses</option>
        <?php foreach (['pending' => 'Pending', 'success' => 'Successful', 'failed' => 'Failed', 'refunded' => 'Refunded'] as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= $filters['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-outline">Filter</button>
</form>

<section class="card mb-3">
    <div class="card-header">
        <div>
            <h2>Record cash received</h2>
            <p class="sub">For cash at meetings, or a transfer confirmed outside Classof.</p>
        </div>
        <span class="badge badge-neutral">Owner / admin</span>
    </div>
    <div class="card-body">
        <form method="post" action="<?= url('groups/' . $group['slug'] . '/treasury/record') ?>" class="form-grid" data-submit-once>
            <?= csrf_field() ?>
            <div class="field">
                <label for="cash_member">Member</label>
                <select id="cash_member" name="member_id" class="select" required>
                    <option value="">Choose member…</option>
                    <?php foreach ($members as $member): ?>
                        <option value="<?= (int) $member['user_id'] ?>" <?= (string) old('member_id') === (string) $member['user_id'] ? 'selected' : '' ?>>
                            <?= e($member['name']) ?><?= !empty($member['email']) ? ' — ' . e($member['email']) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['member_id'])): ?><span class="error-text"><?= e($errors['member_id']) ?></span><?php endif; ?>
            </div>
            <div class="field">
                <label for="cash_purpose">Purpose</label>
                <select id="cash_purpose" name="purpose" class="select" required>
                    <option value="dues" <?= old('purpose', 'dues') === 'dues' ? 'selected' : '' ?>>Dues</option>
                    <option value="donation" <?= old('purpose') === 'donation' ? 'selected' : '' ?>>Donation / other</option>
                </select>
                <?php if (isset($errors['purpose'])): ?><span class="error-text"><?= e($errors['purpose']) ?></span><?php endif; ?>
            </div>
            <div class="field">
                <label for="cash_dues">Dues cycle (for dues)</label>
                <select id="cash_dues" name="dues_cycle_id" class="select">
                    <option value="">Choose open cycle…</option>
                    <?php foreach ($openDues as $cycle): ?>
                        <option value="<?= (int) $cycle['id'] ?>" <?= (string) old('dues_cycle_id') === (string) $cycle['id'] ? 'selected' : '' ?>>
                            <?= e($cycle['title']) ?> — <?= money($cycle['amount'], $group['currency']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($openDues === []): ?><span class="hint">No open dues cycle. Create one from Dues first.</span><?php endif; ?>
                <?php if (isset($errors['dues_cycle_id'])): ?><span class="error-text"><?= e($errors['dues_cycle_id']) ?></span><?php endif; ?>
            </div>
            <div class="field">
                <label for="cash_amount">Amount (for donation)</label>
                <input type="number" id="cash_amount" name="amount" min="1" step="0.01" value="<?= e(old('amount')) ?>" placeholder="0.00">
                <?php if (isset($errors['amount'])): ?><span class="error-text"><?= e($errors['amount']) ?></span><?php endif; ?>
            </div>
            <div class="field span-2">
                <label for="cash_note">Note / description</label>
                <input type="text" id="cash_note" name="note" maxlength="255" value="<?= e(old('note')) ?>" placeholder="Optional. Used as the donation title if provided.">
                <?php if (isset($errors['note'])): ?><span class="error-text"><?= e($errors['note']) ?></span><?php endif; ?>
            </div>
            <div class="span-2">
                <button type="submit" class="btn btn-primary">Record cash payment</button>
                <span class="hint" style="margin-left:10px">Cash entries are marked successful immediately and tied to your account in the audit trail.</span>
            </div>
        </form>
    </div>
</section>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Member</th>
                    <th>Purpose</th>
                    <th>Amount</th>
                    <th>Gateway</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($payments === []): ?>
                <tr><td colspan="8" class="empty-row">No payments match these filters.</td></tr>
            <?php endif; ?>
            <?php foreach ($payments as $payment): ?>
                <?php
                $paymentMeta = [];
                if (!empty($payment['meta'])) {
                    $decodedMeta = json_decode((string) $payment['meta'], true);
                    $paymentMeta = is_array($decodedMeta) ? $decodedMeta : [];
                }
                $claimedAt = $paymentMeta['member_claimed_at'] ?? null;
                ?>
                <tr>
                    <td class="tiny" style="font-family:ui-monospace,monospace">
                        <?= e($payment['reference']) ?>
                        <?php if ($claimedAt !== null): ?>
                            <div class="mt-1"><span class="badge badge-info">Member says paid</span></div>
                            <div class="tiny muted"><?= e(time_ago($claimedAt)) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="table-person">
                            <?= avatar($payment['user_name'] ?? 'Unknown', $payment['avatar'] ?? null, 30) ?>
                            <div class="table-person-meta">
                                <strong><?= e($payment['user_name'] ?? 'Unknown') ?></strong>
                                <span><?= e($payment['user_email'] ?? '') ?></span>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge badge-neutral"><?= e($purposes[$payment['purpose']] ?? $payment['purpose']) ?></span></td>
                    <td class="strong nowrap"><?= money($payment['amount'], $payment['currency']) ?></td>
                    <td class="small muted"><?= e(ucfirst($payment['gateway'])) ?></td>
                    <td><span class="badge badge-<?= $statusTone($payment['status']) ?>"><?= e(ucfirst($payment['status'])) ?></span></td>
                    <td class="small muted nowrap"><?= e(short_date($payment['created_at'])) ?></td>
                    <td>
                        <div class="table-actions">
                            <?php if ($payment['status'] === 'pending'): ?>
                                <form method="post" action="<?= url('payments/' . $payment['id'] . '/confirm') ?>" data-confirm="Confirm this payment as received?">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-primary btn-sm">Confirm</button>
                                </form>
                                <form method="post" action="<?= url('payments/' . $payment['id'] . '/reject') ?>" data-confirm="Mark this payment as failed?">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-ghost btn-sm">Reject</button>
                                </form>
                            <?php else: ?>
                                <a href="<?= url('payments/' . $payment['id'] . '/instructions') ?>" class="btn btn-ghost btn-sm">View</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="callout info mt-3">
    <?= icon('shield', 17) ?>
    <div>Confirm a payment only after the funds have landed in the group account. Every confirmation is tied to your admin account in the audit trail.</div>
</div>
