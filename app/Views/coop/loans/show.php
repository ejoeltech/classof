<?php
$title = 'Loan ' . money($loan['principal'], $group['currency']);
$membership = $groupMembership ?? null;
require BASE_PATH . '/app/Views/partials/group-header.php';
$acceptedGuarantors = count(array_filter($guarantors, static fn (array $g): bool => $g['status'] === 'accepted'));
$outstanding = $outstanding ?? 0;
?>
<div class="page-head">
    <div>
        <a href="<?= url('groups/' . $group['slug'] . '/coop/loans') ?>" class="tiny muted" style="display:inline-flex;align-items:center;gap:4px;margin-bottom:6px"><?= icon('chevron-right', 13) ?> All loans</a>
        <h1><?= e($loan['member_name']) ?> — <?= money($loan['principal'], $group['currency']) ?></h1>
        <p><?= e($loan['product_name'] ?: 'Standard terms') ?> · applied <?= e(short_date($loan['applied_at'])) ?></p>
    </div>
    <span class="badge badge-<?= App\Models\CoopLoan::statusTone($loan['status']) ?>"><?= e(App\Models\CoopLoan::statusLabel($loan['status'])) ?></span>
</div>

<?php if ($overdue > 0): ?>
    <div class="alert alert-error">
        <span class="alert-icon"><?= icon('alert', 18) ?></span>
        <div class="alert-body">This loan has <strong><?= $overdue ?></strong> overdue instalment(s).</div>
    </div>
<?php endif; ?>

<div class="stat-grid mb-3">
    <div class="stat-card"><span class="stat-icon"><?= icon('card', 20) ?></span><div class="stat-meta"><span class="stat-label">Principal</span><span class="stat-value"><?= money($loan['principal'], $group['currency']) ?></span></div></div>
    <div class="stat-card"><span class="stat-icon info"><?= icon('chart', 20) ?></span><div class="stat-meta"><span class="stat-label">Total repayable</span><span class="stat-value"><?= money($loan['total_repayable'], $group['currency']) ?></span><span class="stat-hint"><?= money($loan['monthly_repayment'], $group['currency']) ?> monthly</span></div></div>
    <div class="stat-card"><span class="stat-icon"><?= icon('wallet', 20) ?></span><div class="stat-meta"><span class="stat-label">Repaid</span><span class="stat-value"><?= money($repaid, $group['currency']) ?></span></div></div>
    <div class="stat-card"><span class="stat-icon warn"><?= icon('clock', 20) ?></span><div class="stat-meta"><span class="stat-label">Outstanding</span><span class="stat-value"><?= money($outstanding, $group['currency']) ?></span></div></div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="between" style="font-size:12.5px;color:var(--muted);margin-bottom:5px">
            <span>Repayment progress</span>
            <span><?= (int) round($progress) ?>%</span>
        </div>
        <div class="progress"><span style="width:<?= (int) $progress ?>%"></span></div>
    </div>
</div>

<div class="split-main">
    <div class="stack">
        <section class="card">
            <div class="card-header"><h2>Loan details</h2></div>
            <div class="card-body">
                <dl class="kv">
                    <dt>Borrower</dt><dd><?= e($loan['member_name']) ?> · <?= e($loan['member_no']) ?></dd>
                    <dt>Purpose</dt><dd><?= e($loan['purpose'] ?: '—') ?></dd>
                    <dt>Interest</dt><dd><?= e($loan['interest_rate']) ?>% p.a. flat · <?= money($loan['interest_amount'], $group['currency']) ?></dd>
                    <dt>Duration</dt><dd><?= (int) $loan['duration_months'] ?> months</dd>
                    <dt>Applied</dt><dd><?= e(date_time($loan['applied_at'])) ?></dd>
                    <dt>Approved</dt><dd><?= e($loan['approved_at'] ? date_time($loan['approved_at']) : '—') ?><?= !empty($loan['approver_name']) ? ' by ' . e($loan['approver_name']) : '' ?></dd>
                    <dt>Disbursed</dt><dd><?= e($loan['disbursed_at'] ? date_time($loan['disbursed_at']) : '—') ?></dd>
                    <dt>Due date</dt><dd><?= e($loan['due_date'] ? short_date($loan['due_date']) : '—') ?></dd>
                </dl>

                <?php if (!empty($loan['rejection_reason'])): ?>
                    <div class="callout mt-2" style="background:var(--danger-50);border-color:#fbd5d1;color:#7a1a12">
                        <?= icon('alert', 17) ?>
                        <div><strong>Rejected:</strong> <?= e($loan['rejection_reason']) ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2>Repayment schedule</h2><span class="badge badge-neutral"><?= count($installments) ?> instalments</span></div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>#</th><th>Due date</th><th>Amount</th><th>Paid</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php if ($installments === []): ?>
                        <tr><td colspan="5" class="empty-row">Schedule is generated when the loan is disbursed.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($installments as $installment): ?>
                        <?php $isOverdue = $installment['status'] !== 'paid' && $installment['due_date'] < date('Y-m-d'); ?>
                        <tr>
                            <td><?= (int) $installment['installment_no'] ?></td>
                            <td class="nowrap"><?= e(short_date($installment['due_date'])) ?></td>
                            <td class="strong"><?= money($installment['amount_due'], $group['currency']) ?></td>
                            <td><?= money($installment['amount_paid'], $group['currency']) ?></td>
                            <td>
                                <span class="badge badge-<?= $installment['status'] === 'paid' ? 'success' : ($isOverdue ? 'danger' : ($installment['status'] === 'partial' ? 'warn' : 'neutral')) ?>">
                                    <?= $isOverdue && $installment['status'] !== 'paid' ? 'Overdue' : e(ucfirst($installment['status'])) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2>Loan transactions</h2></div>
            <div class="card-body">
                <?php if ($transactions === []): ?>
                    <p class="muted small">No movements recorded.</p>
                <?php else: ?>
                    <div class="ledger">
                        <?php foreach ($transactions as $transaction): ?>
                            <div class="ledger-row">
                                <div style="flex:1">
                                    <div style="font-weight:600;font-size:13.5px"><?= e(App\Models\CoopTransaction::typeLabel($transaction['type'])) ?></div>
                                    <div class="ledger-note"><?= e(date_time($transaction['occurred_at'])) ?> · <?= e(ucfirst($transaction['method'])) ?></div>
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
    </div>

    <div class="stack">
        <section class="card">
            <div class="card-header"><h2>Guarantors</h2><span class="badge badge-<?= $acceptedGuarantors > 0 ? 'success' : 'neutral' ?>"><?= $acceptedGuarantors ?> accepted</span></div>
            <div class="card-body">
                <?php if ($guarantors === []): ?>
                    <p class="muted small">No guarantors recorded.</p>
                <?php else: ?>
                    <div class="list">
                        <?php foreach ($guarantors as $guarantor): ?>
                            <div class="list-item" style="align-items:center">
                                <?= avatar($guarantor['name'], $guarantor['avatar'] ?? null, 32) ?>
                                <div class="list-item-body">
                                    <div class="list-item-title" style="font-size:13.5px"><?= e($guarantor['name']) ?></div>
                                    <div class="list-item-meta"><?= e($guarantor['member_no']) ?><?= $guarantor['amount'] !== null ? ' · ' . money($guarantor['amount'], $group['currency']) : '' ?></div>
                                </div>
                                <span class="badge badge-<?= $guarantor['status'] === 'accepted' ? 'success' : ($guarantor['status'] === 'declined' ? 'danger' : 'warn') ?>"><?= e(ucfirst($guarantor['status'])) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($myGuarantee !== null && $myGuarantee['status'] === 'pending'): ?>
                    <div class="divider"></div>
                    <p class="small"><strong>Your decision is needed.</strong> If this borrower defaults, you may be called upon as guarantor.</p>
                    <div class="flex">
                        <form method="post" action="<?= url('groups/' . $group['slug'] . '/coop/loans/' . $loan['id'] . '/guarantee') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="accept">
                            <button type="submit" class="btn btn-primary btn-sm">Accept</button>
                        </form>
                        <form method="post" action="<?= url('groups/' . $group['slug'] . '/coop/loans/' . $loan['id'] . '/guarantee') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="decline">
                            <button type="submit" class="btn btn-outline btn-sm">Decline</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <?php if ($isOwner && in_array($loan['status'], ['disbursed', 'repaying'], true) && $outstanding > 0): ?>
            <section class="card">
                <div class="card-header"><h2>Make a repayment</h2></div>
                <div class="card-body">
                    <form method="post" action="<?= url('payments/initiate') ?>" data-submit-once>
                        <?= csrf_field() ?>
                        <input type="hidden" name="purpose" value="coop_loan">
                        <input type="hidden" name="group_id" value="<?= (int) $group['id'] ?>">
                        <input type="hidden" name="coop_loan_id" value="<?= (int) $loan['id'] ?>">
                        <div class="field">
                            <label for="repay-amount">Amount (<?= e($group['currency']) ?>)</label>
                            <input type="number" id="repay-amount" name="amount" min="1" max="<?= $outstanding ?>" step="0.01" value="<?= (float) $loan['monthly_repayment'] ?>" required>
                        </div>
                        <div class="amount-presets mt-1">
                            <button type="button" class="amount-preset" data-amount="<?= (float) $loan['monthly_repayment'] ?>" data-amount-target="#repay-amount">Monthly</button>
                            <button type="button" class="amount-preset" data-amount="<?= $outstanding ?>" data-amount-target="#repay-amount">Full balance</button>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block mt-2">Pay <?= money($loan['monthly_repayment'], $group['currency']) ?></button>
                    </form>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($isOfficer): ?>
            <?php if ($loan['status'] === 'pending'): ?>
                <section class="card">
                    <div class="card-header"><h2>Decision</h2></div>
                    <div class="card-body">
                        <p class="muted small"><?= $acceptedGuarantors ?> guarantor(s) accepted. The co-op requires guarantors to accept before approval.</p>
                        <form method="post" action="<?= url('groups/' . $group['slug'] . '/coop/loans/' . $loan['id'] . '/approve') ?>" class="mb-2" data-submit-once>
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-primary btn-block"><?= icon('check', 16) ?> Approve loan</button>
                        </form>
                        <form method="post" action="<?= url('groups/' . $group['slug'] . '/coop/loans/' . $loan['id'] . '/reject') ?>" class="stack-sm" data-submit-once>
                            <?= csrf_field() ?>
                            <div class="field">
                                <label for="rejection_reason">Rejection reason</label>
                                <input type="text" id="rejection_reason" name="rejection_reason" maxlength="255" placeholder="Optional note">
                            </div>
                            <button type="submit" class="btn btn-danger-ghost btn-block">Reject</button>
                        </form>
                    </div>
                </section>
            <?php elseif ($loan['status'] === 'approved'): ?>
                <section class="card">
                    <div class="card-header"><h2>Disburse</h2></div>
                    <div class="card-body">
                        <p class="muted small">Record how the funds were released. This generates the repayment schedule.</p>
                        <form method="post" action="<?= url('groups/' . $group['slug'] . '/coop/loans/' . $loan['id'] . '/disburse') ?>" class="stack-sm" data-submit-once>
                            <?= csrf_field() ?>
                            <div class="field">
                                <label for="method">Method</label>
                                <select id="method" name="method" class="select">
                                    <option value="bank">Bank transfer</option>
                                    <option value="cash">Cash</option>
                                </select>
                            </div>
                            <div class="field">
                                <label for="disburse-note">Note</label>
                                <input type="text" id="disburse-note" name="note" maxlength="255" placeholder="e.g. Transfer to GTB ****1234">
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Record disbursement</button>
                        </form>
                    </div>
                </section>
            <?php elseif (in_array($loan['status'], ['disbursed', 'repaying'], true)): ?>
                <section class="card">
                    <div class="card-header"><h2>Record repayment</h2></div>
                    <div class="card-body">
                        <form method="post" action="<?= url('groups/' . $group['slug'] . '/coop/loans/' . $loan['id'] . '/repayment') ?>" class="stack-sm" data-submit-once>
                            <?= csrf_field() ?>
                            <div class="field">
                                <label for="amount">Amount</label>
                                <input type="number" id="amount" name="amount" min="1" max="<?= $outstanding ?>" step="0.01" required>
                            </div>
                            <div class="field">
                                <label for="repay-method">Method</label>
                                <select id="repay-method" name="method" class="select">
                                    <option value="cash">Cash</option>
                                    <option value="bank">Bank transfer</option>
                                </select>
                            </div>
                            <div class="field">
                                <label for="repay-note">Note</label>
                                <input type="text" id="repay-note" name="note" maxlength="255">
                            </div>
                            <button type="submit" class="btn btn-outline btn-block">Record repayment</button>
                        </form>
                    </div>
                </section>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
