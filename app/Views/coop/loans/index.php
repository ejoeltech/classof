<?php
$title = 'Co-op loans';
$membership = $groupMembership ?? null;
require BASE_PATH . '/app/Views/partials/group-header.php';
$outstanding = $activeLoan !== null ? App\Models\CoopLoan::outstanding($activeLoan) : 0;
?>
<div class="page-head">
    <div>
        <h1>Co-op loans</h1>
        <p>Apply, guarantee, approve and track repayments.</p>
    </div>
    <div class="page-head-actions">
        <?php if ($isOfficer): ?>
            <a href="<?= url('groups/' . $group['slug'] . '/coop/products') ?>" class="btn btn-outline"><?= icon('settings', 15) ?> Loan products</a>
            <a href="<?= url('groups/' . $group['slug'] . '/coop/transactions') ?>" class="btn btn-ghost">Ledger</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($isMember): ?>
    <div class="stat-grid mb-3" style="grid-template-columns:repeat(3,1fr)">
        <div class="stat-card"><span class="stat-icon"><?= icon('wallet', 20) ?></span><div class="stat-meta"><span class="stat-label">My savings</span><span class="stat-value"><?= money($savings, $group['currency']) ?></span></div></div>
        <div class="stat-card"><span class="stat-icon info"><?= icon('chart', 20) ?></span><div class="stat-meta"><span class="stat-label">My borrowing limit</span><span class="stat-value"><?= money($loanLimit, $group['currency']) ?></span><span class="stat-hint"><?= e($coop['max_loan_multiple']) ?>× savings</span></div></div>
        <div class="stat-card"><span class="stat-icon warn"><?= icon('card', 20) ?></span><div class="stat-meta"><span class="stat-label">Active loan</span><span class="stat-value"><?= money($outstanding, $group['currency']) ?></span><span class="stat-hint"><?= $activeLoan !== null ? e(App\Models\CoopLoan::statusLabel($activeLoan['status'])) : 'None' ?></span></div></div>
    </div>

    <?php if ($activeLoan !== null): ?>
        <div class="callout mb-3">
            <?= icon('info', 17) ?>
            <div>You already have a loan in progress. Repay it fully before applying for another. <a href="<?= url('groups/' . $group['slug'] . '/coop/loans/' . $activeLoan['id']) ?>">View loan</a>.</div>
        </div>
    <?php elseif ($products === []): ?>
        <div class="callout info mb-3"><?= icon('info', 17) ?><div>No loan products are available yet. Ask a co-op officer to create one.</div></div>
    <?php else: ?>
        <section class="card mb-3">
            <div class="card-header">
                <div><h2>Apply for a loan</h2><p class="sub">Flat interest, fixed monthly instalments</p></div>
                <span class="badge badge-neutral">Limit <?= money($loanLimit, $group['currency']) ?></span>
            </div>
            <div class="card-body">
                <form method="post" action="<?= url('groups/' . $group['slug'] . '/coop/loans') ?>" class="form-grid" data-submit-once>
                    <?= csrf_field() ?>
                    <div class="field">
                        <label for="product_id">Loan product</label>
                        <select id="product_id" name="product_id" class="select">
                            <option value="">Co-op standard terms</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?= (int) $product['id'] ?>">
                                    <?= e($product['name']) ?> — <?= e($product['interest_rate']) ?>% · <?= (int) $product['duration_months'] ?> months
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="principal">Amount (<?= e($group['currency']) ?>)</label>
                        <input type="number" id="principal" name="principal" min="1" step="0.01" max="<?= $loanLimit ?>" required placeholder="e.g. 50000">
                        <span class="hint">Maximum <?= money($loanLimit, $group['currency']) ?> based on your savings.</span>
                    </div>
                    <div class="field">
                        <label for="duration_months">Duration (months)</label>
                        <input type="number" id="duration_months" name="duration_months" min="1" value="<?= (int) $coop['loan_duration_months'] ?>">
                    </div>
                    <div class="field">
                        <label for="purpose">Purpose</label>
                        <input type="text" id="purpose" name="purpose" maxlength="255" placeholder="e.g. School fees, business stock">
                    </div>
                    <div class="field span-2">
                        <label>Guarantors <span class="hint">(<?= (int) $coop['guarantors_required'] ?> required)</span></label>
                        <?php if ($members === []): ?>
                            <p class="muted small">No other active co-op members are available to guarantee.</p>
                        <?php else: ?>
                            <div class="grid-2" style="gap:8px">
                                <?php foreach ($members as $member): ?>
                                    <label class="checkbox" style="border:1px solid var(--line);border-radius:10px;padding:9px 11px">
                                        <input type="checkbox" name="guarantors[]" value="<?= (int) $member['id'] ?>">
                                        <span><?= e($member['name']) ?><br><span class="tiny muted"><?= e($member['member_no']) ?></span></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="span-2 form-actions">
                        <button type="submit" class="btn btn-primary">Submit application</button>
                        <span class="muted small">Your guarantors must accept before a loan officer can approve.</span>
                    </div>
                </form>
            </div>
        </section>
    <?php endif; ?>
<?php endif; ?>

<?php if ($isOfficer): ?>
    <form method="get" action="<?= url('groups/' . $group['slug'] . '/coop/loans') ?>" class="filter-bar">
        <div class="search-field">
            <?= icon('search', 17) ?>
            <input type="search" name="q" value="<?= e($filters['search']) ?>" placeholder="Search borrower or purpose…">
        </div>
        <select name="status" class="select">
            <option value="">All statuses</option>
            <?php foreach ($statuses as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= $filters['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-outline">Filter</button>
    </form>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2><?= $isOfficer ? 'Loan book' : 'My loans' ?></h2>
        <span class="badge badge-neutral"><?= count($loans) ?></span>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Borrower</th><th>Principal</th><th>Outstanding</th><th>Status</th><th>Guarantors</th><th>Applied</th><th></th></tr></thead>
            <tbody>
            <?php if ($loans === []): ?>
                <tr><td colspan="7" class="empty-row">No loans yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($loans as $loan): ?>
                <?php $loanOutstanding = App\Models\CoopLoan::outstanding($loan); ?>
                <tr>
                    <td>
                        <div class="table-person">
                            <?= avatar($loan['member_name'], $loan['avatar'] ?? null, 30) ?>
                            <div class="table-person-meta">
                                <strong><?= e($loan['member_name']) ?></strong>
                                <span><?= e($loan['product_name'] ?: 'Standard terms') ?></span>
                            </div>
                        </div>
                    </td>
                    <td class="strong"><?= money($loan['principal'], $group['currency']) ?></td>
                    <td><?= money($loanOutstanding, $group['currency']) ?></td>
                    <td><span class="badge badge-<?= App\Models\CoopLoan::statusTone($loan['status']) ?>"><?= e(App\Models\CoopLoan::statusLabel($loan['status'])) ?></span></td>
                    <td class="small muted"><?= isset($loan['accepted_guarantors']) ? (int) $loan['accepted_guarantors'] . '/' . (int) $loan['guarantor_count'] : '—' ?></td>
                    <td class="small muted nowrap"><?= e(short_date($loan['applied_at'])) ?></td>
                    <td class="text-right"><a href="<?= url('groups/' . $group['slug'] . '/coop/loans/' . $loan['id']) ?>" class="btn btn-ghost btn-sm">View</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
