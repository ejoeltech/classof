<?php
$title = 'My co-op statement';
$membership = $groupMembership ?? null;
require BASE_PATH . '/app/Views/partials/group-header.php';
$outstandingLoan = $activeLoan !== null ? App\Models\CoopLoan::outstanding($activeLoan) : 0;
$unpaidFines = array_values(array_filter($fines, static fn (array $f): bool => $f['status'] === 'unpaid'));
?>
<div class="page-head">
    <div>
        <h1>My co-op statement</h1>
        <p><?= e($coopMember['member_no'] ?? '') ?> · <?= e(App\Models\Cooperative::roleLabel($coopMember['role'])) ?> · joined <?= e(short_date($coopMember['joined_at'])) ?></p>
    </div>
    <div class="page-head-actions">
        <a href="<?= url('groups/' . $group['slug'] . '/coop/me/export') ?>" class="btn btn-outline"><?= icon('download', 15) ?> Export statement</a>
        <a href="<?= url('groups/' . $group['slug'] . '/coop') ?>" class="btn btn-ghost">Back to co-op</a>
    </div>
</div>

<div class="stat-grid mb-3">
    <div class="stat-card"><span class="stat-icon"><?= icon('wallet', 20) ?></span><div class="stat-meta"><span class="stat-label">Savings balance</span><span class="stat-value"><?= money($savings, $group['currency']) ?></span><span class="stat-hint">Withdrawable</span></div></div>
    <div class="stat-card"><span class="stat-icon gold"><?= icon('card', 20) ?></span><div class="stat-meta"><span class="stat-label">Share capital</span><span class="stat-value"><?= money($shareCapital, $group['currency']) ?></span><span class="stat-hint"><?= e((float) $coopMember['shares']) ?> units</span></div></div>
    <div class="stat-card"><span class="stat-icon info"><?= icon('chart', 20) ?></span><div class="stat-meta"><span class="stat-label">Loan outstanding</span><span class="stat-value"><?= money($outstandingLoan, $group['currency']) ?></span><span class="stat-hint"><?= $activeLoan !== null ? e(App\Models\CoopLoan::statusLabel($activeLoan['status'])) : 'No active loan' ?></span></div></div>
    <div class="stat-card"><span class="stat-icon warn"><?= icon('alert', 20) ?></span><div class="stat-meta"><span class="stat-label">Unpaid fines</span><span class="stat-value"><?= money(array_sum(array_map(static fn (array $f): float => (float) $f['amount'], $unpaidFines)), $group['currency']) ?></span><span class="stat-hint"><?= count($unpaidFines) ?> fine(s)</span></div></div>
</div>

<?php if (!empty($guarantees)): ?>
    <div class="card mb-3">
        <div class="card-header"><h2>Guarantee requests</h2><span class="badge badge-warn"><?= count($guarantees) ?> pending</span></div>
        <div class="card-body">
            <div class="list">
                <?php foreach ($guarantees as $request): ?>
                    <div class="list-item" style="align-items:center">
                        <?= avatar($request['borrower_name'], $request['avatar'] ?? null, 34) ?>
                        <div class="list-item-body">
                            <div class="list-item-title" style="font-size:13.5px"><?= e($request['borrower_name']) ?> needs a guarantor</div>
                            <div class="list-item-meta">Loan of <?= money($request['principal'], $group['currency']) ?> · <?= e(App\Models\CoopLoan::statusLabel($request['loan_status'])) ?></div>
                        </div>
                        <a href="<?= url('groups/' . $request['group_slug'] . '/coop/loans/' . $request['loan_id']) ?>" class="btn btn-outline btn-sm">Review</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="split-main">
    <div class="stack">
        <section class="card">
            <div class="card-header"><h2>Save &amp; buy shares</h2></div>
            <div class="card-body">
                <div class="grid-2">
                    <form method="post" action="<?= url('payments/initiate') ?>" data-submit-once>
                        <?= csrf_field() ?>
                        <input type="hidden" name="purpose" value="coop_savings">
                        <input type="hidden" name="group_id" value="<?= (int) $group['id'] ?>">
                        <div class="field">
                            <label for="savings-amount">Savings deposit</label>
                            <input type="number" id="savings-amount" name="amount" min="1" step="0.01" required placeholder="Amount">
                        </div>
                        <div class="amount-presets mt-1">
                            <?php foreach ([1000, 5000, 10000, 20000] as $preset): ?>
                                <button type="button" class="amount-preset" data-amount="<?= $preset ?>" data-amount-target="#savings-amount"><?= money($preset, $group['currency']) ?></button>
                            <?php endforeach; ?>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block mt-2">Deposit savings</button>
                    </form>

                    <form method="post" action="<?= url('payments/initiate') ?>" data-submit-once>
                        <?= csrf_field() ?>
                        <input type="hidden" name="purpose" value="coop_shares">
                        <input type="hidden" name="group_id" value="<?= (int) $group['id'] ?>">
                        <div class="field">
                            <label for="shares-amount">Buy shares</label>
                            <input type="number" id="shares-amount" name="amount" min="<?= (float) $coop['share_value'] ?>" step="<?= (float) $coop['share_value'] ?>" required placeholder="Amount">
                            <span class="hint"><?= money($coop['share_value'], $group['currency']) ?> per share unit.</span>
                        </div>
                        <button type="submit" class="btn btn-gold btn-block mt-2">Buy shares</button>
                    </form>
                </div>
                <p class="tiny muted mt-2 mb-0">Checkout is handled by <?= e(App\Services\Payments\PaymentManager::current()->label()) ?>. Deposits and shares appear after payment confirmation.</p>
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2>My transactions</h2><span class="badge badge-neutral"><?= count($transactions) ?></span></div>
            <div class="card-body">
                <?php if ($transactions === []): ?>
                    <p class="muted small">No transactions yet.</p>
                <?php else: ?>
                    <div class="ledger">
                        <?php foreach ($transactions as $transaction): ?>
                            <div class="ledger-row">
                                <div style="flex:1;min-width:0">
                                    <div style="font-size:13.5px;font-weight:600"><?= e(App\Models\CoopTransaction::typeLabel($transaction['type'])) ?></div>
                                    <div class="ledger-note"><?= e(date_time($transaction['occurred_at'])) ?> · <?= e(ucfirst($transaction['method'])) ?><?= !empty($transaction['note']) ? ' · ' . e($transaction['note']) : '' ?></div>
                                </div>
                                <div class="ledger-amount" style="color:<?= $transaction['direction'] === 'in' ? 'var(--forest-700)' : 'var(--danger)' ?>">
                                    <?= $transaction['direction'] === 'in' ? '+' : '−' ?><?= money($transaction['amount'], $group['currency']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2>My loans</h2><a href="<?= url('groups/' . $group['slug'] . '/coop/loans') ?>" class="card-link">Loan centre <?= icon('arrow-right', 13) ?></a></div>
            <div class="card-body">
                <?php if ($loans === []): ?>
                    <p class="muted small">No loan applications yet.</p>
                <?php else: ?>
                    <div class="list">
                        <?php foreach ($loans as $loan): ?>
                            <?php $repaid = (float) ($loan['repaid'] ?? 0); $pct = App\Models\CoopLoan::progress($loan); ?>
                            <div class="list-item">
                                <div class="list-item-body">
                                    <div class="list-item-title">
                                        <a href="<?= url('groups/' . $group['slug'] . '/coop/loans/' . $loan['id']) ?>"><?= money($loan['principal'], $group['currency']) ?> loan</a>
                                        <span class="badge badge-<?= App\Models\CoopLoan::statusTone($loan['status']) ?>"><?= e(App\Models\CoopLoan::statusLabel($loan['status'])) ?></span>
                                    </div>
                                    <div class="list-item-meta"><?= e(date('M j, Y', strtotime($loan['applied_at']))) ?> · <?= money($repaid, $group['currency']) ?> repaid of <?= money($loan['total_repayable'], $group['currency']) ?></div>
                                    <div class="progress thin mt-1"><span style="width:<?= (int) $pct ?>%"></span></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <div class="stack">
        <section class="card">
            <div class="card-header"><h2>Fines</h2><span class="badge badge-<?= $unpaidFines === [] ? 'success' : 'warn' ?>"><?= count($unpaidFines) ?> unpaid</span></div>
            <div class="card-body">
                <?php if ($fines === []): ?>
                    <p class="muted small">No fines on record.</p>
                <?php else: ?>
                    <div class="list">
                        <?php foreach ($fines as $fine): ?>
                            <div class="list-item" style="align-items:center">
                                <div class="list-item-body">
                                    <div class="list-item-title" style="font-size:13.5px"><?= e($fine['reason']) ?></div>
                                    <div class="list-item-meta"><?= money($fine['amount'], $group['currency']) ?> · <?= e(ucfirst($fine['status'])) ?></div>
                                </div>
                                <?php if ($fine['status'] === 'unpaid'): ?>
                                    <form method="post" action="<?= url('payments/initiate') ?>" data-submit-once>
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="purpose" value="coop_fine">
                                        <input type="hidden" name="group_id" value="<?= (int) $group['id'] ?>">
                                        <input type="hidden" name="coop_fine_id" value="<?= (int) $fine['id'] ?>">
                                        <button type="submit" class="btn btn-outline btn-sm">Pay</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2>Dividends received</h2></div>
            <div class="card-body">
                <?php if ($dividends === []): ?>
                    <p class="muted small">No dividends yet.</p>
                <?php else: ?>
                    <div class="list">
                        <?php foreach ($dividends as $dividend): ?>
                            <div class="list-item">
                                <div class="list-item-body">
                                    <div class="list-item-title" style="font-size:13.5px"><?= e($dividend['title']) ?></div>
                                    <div class="list-item-meta"><?= e($dividend['period'] ?: short_date($dividend['declared_at'])) ?></div>
                                </div>
                                <div class="ledger-amount"><?= money($dividend['amount'], $group['currency']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>
