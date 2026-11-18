<?php
$title = 'Co-op ledger';
$membership = $groupMembership ?? null;
require BASE_PATH . '/app/Views/partials/group-header.php';
?>
<div class="page-head">
    <div>
        <h1>Co-op ledger</h1>
        <p>Every share purchase, saving, loan movement, fine and dividend.</p>
    </div>
    <div class="page-head-actions">
        <a href="<?= url('groups/' . $group['slug'] . '/coop/transactions/export') ?>" class="btn btn-outline"><?= icon('download', 15) ?> Export CSV</a>
        <a href="<?= url('groups/' . $group['slug'] . '/coop/members') ?>" class="btn btn-ghost">Members</a>
    </div>
</div>

<div class="stat-grid mb-3">
    <div class="stat-card"><span class="stat-icon"><?= icon('wallet', 20) ?></span><div class="stat-meta"><span class="stat-label">Cash position</span><span class="stat-value"><?= money($summary['cash'] ?? 0, $group['currency']) ?></span><span class="stat-hint">All money in and out</span></div></div>
    <div class="stat-card"><span class="stat-icon gold"><?= icon('card', 20) ?></span><div class="stat-meta"><span class="stat-label">Savings pool</span><span class="stat-value"><?= money($summary['savings'] ?? 0, $group['currency']) ?></span></div></div>
    <div class="stat-card"><span class="stat-icon info"><?= icon('chart', 20) ?></span><div class="stat-meta"><span class="stat-label">Loans out</span><span class="stat-value"><?= money($summary['loans_outstanding'] ?? 0, $group['currency']) ?></span></div></div>
    <div class="stat-card"><span class="stat-icon warn"><?= icon('alert', 20) ?></span><div class="stat-meta"><span class="stat-label">Unpaid fines</span><span class="stat-value"><?= money($summary['fines_unpaid'] ?? 0, $group['currency']) ?></span></div></div>
</div>

<div class="split-main">
    <div class="stack">
        <form method="get" action="<?= url('groups/' . $group['slug'] . '/coop/transactions') ?>" class="filter-bar">
            <div class="search-field">
                <?= icon('search', 17) ?>
                <input type="search" name="q" value="<?= e($filters['search']) ?>" placeholder="Search reference, note or member…">
            </div>
            <select name="type" class="select">
                <option value="">All types</option>
                <?php foreach ($types as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= $filters['type'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="member_id" class="select">
                <option value="">All members</option>
                <?php foreach ($members as $member): ?>
                    <option value="<?= (int) $member['id'] ?>" <?= (int) $filters['member_id'] === (int) $member['id'] ? 'selected' : '' ?>><?= e($member['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-outline">Filter</button>
        </form>

        <?php if (!empty($pendingPayments)): ?>
            <section class="card" style="border-color:#fde7bf;background:#fffdf7">
                <div class="card-header">
                    <div><h2>Pending co-op payments</h2><p class="sub">Confirm after the funds land</p></div>
                    <span class="badge badge-warn"><?= count($pendingPayments) ?></span>
                </div>
                <div class="card-body">
                    <div class="list">
                        <?php foreach ($pendingPayments as $pending): ?>
                            <div class="list-item" style="align-items:center">
                                <?= avatar($pending['member_name'] ?? 'Member', $pending['avatar'] ?? null, 32) ?>
                                <div class="list-item-body">
                                    <div class="list-item-title" style="font-size:13.5px"><?= e($pending['member_name'] ?? 'Member') ?> — <?= money($pending['amount'], $pending['currency']) ?></div>
                                    <div class="list-item-meta"><?= e(App\Models\Payment::PURPOSES[$pending['purpose']] ?? $pending['purpose']) ?> · <?= e($pending['reference']) ?></div>
                                </div>
                                <div class="flex">
                                    <form method="post" action="<?= url('payments/' . $pending['id'] . '/confirm') ?>" data-confirm="Confirm this co-op payment as received?">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-primary btn-sm">Confirm</button>
                                    </form>
                                    <form method="post" action="<?= url('payments/' . $pending['id'] . '/reject') ?>" data-confirm="Mark this payment as failed?">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-ghost btn-sm">Reject</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <div class="card">
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Date</th><th>Member</th><th>Type</th><th>Amount</th><th>Method</th><th>Reference</th></tr></thead>
                    <tbody>
                    <?php if ($transactions === []): ?>
                        <tr><td colspan="6" class="empty-row">No transactions match these filters.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td class="small muted nowrap"><?= e(short_date($transaction['occurred_at'])) ?></td>
                            <td>
                                <div class="table-person">
                                    <?= avatar($transaction['member_name'] ?? 'Co-op', $transaction['avatar'] ?? null, 30) ?>
                                    <div class="table-person-meta"><strong><?= e($transaction['member_name'] ?? 'Co-op') ?></strong></div>
                                </div>
                            </td>
                            <td><span class="badge badge-neutral"><?= e(App\Models\CoopTransaction::typeLabel($transaction['type'])) ?></span></td>
                            <td class="strong nowrap" style="color:<?= $transaction['direction'] === 'in' ? 'var(--forest-700)' : 'var(--danger)' ?>">
                                <?= $transaction['direction'] === 'in' ? '+' : '−' ?><?= money($transaction['amount'], $group['currency']) ?>
                            </td>
                            <td class="small"><?= e(ucfirst($transaction['method'])) ?></td>
                            <td class="tiny muted" style="font-family:ui-monospace,monospace"><?= e($transaction['reference']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="stack">
        <section class="card">
            <div class="card-header"><h2>Record a transaction</h2></div>
            <div class="card-body">
                <form method="post" action="<?= url('groups/' . $group['slug'] . '/coop/transactions') ?>" class="stack-sm" data-submit-once>
                    <?= csrf_field() ?>
                    <div class="field">
                        <label for="member_id">Member</label>
                        <select id="member_id" name="member_id" class="select" required>
                            <option value="">Choose member…</option>
                            <?php foreach ($members as $member): ?>
                                <option value="<?= (int) $member['id'] ?>"><?= e($member['name']) ?> (<?= e($member['member_no']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="type">Transaction type</label>
                        <select id="type" name="type" class="select" required>
                            <option value="savings_deposit">Savings deposit</option>
                            <option value="savings_withdrawal">Savings withdrawal</option>
                            <option value="share_purchase">Share purchase</option>
                            <option value="adjustment">Adjustment</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="amount">Amount (<?= e($group['currency']) ?>)</label>
                        <input type="number" id="amount" name="amount" min="0.01" step="0.01" required>
                    </div>
                    <div class="field">
                        <label for="method">Method</label>
                        <select id="method" name="method" class="select">
                            <option value="cash">Cash</option>
                            <option value="bank">Bank transfer</option>
                            <option value="gateway">Gateway</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="direction">Direction <span class="hint">(adjustments only)</span></label>
                        <select id="direction" name="direction" class="select">
                            <option value="in">Money in</option>
                            <option value="out">Money out</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="note">Note</label>
                        <input type="text" id="note" name="note" maxlength="255" placeholder="e.g. January savings">
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Record transaction</button>
                </form>
                <div class="callout info mt-2">
                    <?= icon('info', 17) ?>
                    <div>Use this only for cash or bank movements. Gateway payments are recorded automatically when confirmed.</div>
                </div>
            </div>
        </section>
    </div>
</div>
