<?php
$title = 'Cooperative';
$membership = $groupMembership ?? null;
require BASE_PATH . '/app/Views/partials/group-header.php';

$isMember = $isMember ?? false;
$summary = $summary ?? [];
$coopMember = $coopMember ?? null;
$canManage = $canManage ?? false;
?>
<?php if ($coop === null): ?>
    <?php if ($canManage): ?>
        <div class="page-head">
            <div>
                <h1>Set up a cooperative</h1>
                <p>Give your alumni group a member-owned savings and loan society.</p>
            </div>
        </div>

        <form method="post" action="<?= url('groups/' . $group['slug'] . '/coop/enable') ?>" class="card" data-submit-once>
            <?= csrf_field() ?>
            <div class="card-body">
                <div class="callout info mb-3">
                    <?= icon('shield', 17) ?>
                    <div>A cooperative is optional. Once enabled, group members can enrol separately, buy shares, save, borrow and repay — with a full audit trail.</div>
                </div>

                <div class="form-grid">
                    <div class="field span-2">
                        <label for="coop-name">Cooperative name</label>
                        <input type="text" id="coop-name" name="name" required value="<?= e(old('name', $group['name'] . ' Cooperative Society')) ?>">
                    </div>
                    <div class="field">
                        <label for="registration_no">Registration number <span class="hint">(optional)</span></label>
                        <input type="text" id="registration_no" name="registration_no" value="<?= e(old('registration_no')) ?>" placeholder="e.g. COOP/2010/001">
                    </div>
                    <div class="field">
                        <label for="share_value">Share value (<?= e($group['currency']) ?>)</label>
                        <input type="number" id="share_value" name="share_value" min="1" step="0.01" required value="<?= e(old('share_value', '1000')) ?>">
                        <span class="hint">Price of one share unit.</span>
                    </div>
                    <div class="field">
                        <label for="monthly_savings">Recommended monthly savings</label>
                        <input type="number" id="monthly_savings" name="monthly_savings" min="0" step="0.01" required value="<?= e(old('monthly_savings', '5000')) ?>">
                    </div>
                    <div class="field">
                        <label for="loan_interest_rate">Loan interest rate (% per annum)</label>
                        <input type="number" id="loan_interest_rate" name="loan_interest_rate" min="0" step="0.01" required value="<?= e(old('loan_interest_rate', '10')) ?>">
                    </div>
                    <div class="field">
                        <label for="loan_duration_months">Default loan duration (months)</label>
                        <input type="number" id="loan_duration_months" name="loan_duration_months" min="1" required value="<?= e(old('loan_duration_months', '12')) ?>">
                    </div>
                    <div class="field">
                        <label for="max_loan_multiple">Maximum loan multiple of savings</label>
                        <input type="number" id="max_loan_multiple" name="max_loan_multiple" min="0.1" step="0.1" required value="<?= e(old('max_loan_multiple', '3')) ?>">
                        <span class="hint">e.g. 3 means a member with ₦60,000 savings can borrow up to ₦180,000.</span>
                    </div>
                    <div class="field">
                        <label for="guarantors_required">Guarantors required</label>
                        <input type="number" id="guarantors_required" name="guarantors_required" min="0" max="10" required value="<?= e(old('guarantors_required', '2')) ?>">
                    </div>
                    <div class="field">
                        <label for="fine_absent">Absence fine</label>
                        <input type="number" id="fine_absent" name="fine_absent" min="0" step="0.01" required value="<?= e(old('fine_absent', '500')) ?>">
                    </div>
                    <div class="field">
                        <label for="meeting_frequency">Meeting frequency</label>
                        <input type="text" id="meeting_frequency" name="meeting_frequency" value="<?= e(old('meeting_frequency', 'Monthly')) ?>" placeholder="e.g. Monthly">
                    </div>
                </div>
            </div>
            <div class="card-footer between">
                <span class="muted small">You become the first coordinator.</span>
                <button type="submit" class="btn btn-primary">Create cooperative</button>
            </div>
        </form>
    <?php else: ?>
        <div class="card"><div class="empty-state">
            <span class="empty-icon"><?= icon('card', 22) ?></span>
            <h3>No cooperative yet</h3>
            <p>This group has not enabled a cooperative. Ask a group admin to set one up.</p>
            <a href="<?= url('groups/' . $group['slug']) ?>" class="btn btn-outline btn-sm mt-2">Back to group</a>
        </div></div>
    <?php endif; ?>
<?php elseif (!$isMember && !$canManage): ?>
    <?php
    $pending = $coopMember !== null && $coopMember['status'] === 'pending';
    $rejected = $coopMember !== null && $coopMember['status'] === 'rejected';
    $suspended = $coopMember !== null && $coopMember['status'] === 'suspended';
    ?>
    <div style="max-width:720px;margin:0 auto">
        <?php if ($pending): ?>
            <div class="alert alert-warn"><span class="alert-icon"><?= icon('clock', 18) ?></span><div class="alert-body">Your co-op membership request is awaiting approval from a co-op officer.</div></div>
        <?php elseif ($rejected): ?>
            <div class="alert alert-error"><span class="alert-icon"><?= icon('alert', 18) ?></span><div class="alert-body"><strong>Your co-op request was rejected.</strong><?php if (!empty($coopMember['rejection_reason'])): ?> Reason: <?= e($coopMember['rejection_reason']) ?><?php endif; ?></div></div>
        <?php elseif ($suspended): ?>
            <div class="alert alert-error"><span class="alert-icon"><?= icon('lock', 18) ?></span><div class="alert-body">Your co-op membership is suspended. Contact a co-op officer.</div></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <span class="badge badge-gold mb-2">Cooperative</span>
                <h1 style="font-size:24px;margin-bottom:4px"><?= e($coop['name']) ?></h1>
                <p class="muted small"><?= e($group['name']) ?><?= !empty($coop['registration_no']) ? ' · ' . e($coop['registration_no']) : '' ?></p>

                <div class="stat-grid mt-3" style="grid-template-columns:repeat(2,1fr)">
                    <div class="stat-card"><span class="stat-icon"><?= icon('users', 19) ?></span><div class="stat-meta"><span class="stat-label">Members</span><span class="stat-value"><?= (int) ($summary['members'] ?? 0) ?></span></div></div>
                    <div class="stat-card"><span class="stat-icon gold"><?= icon('card', 19) ?></span><div class="stat-meta"><span class="stat-label">Share value</span><span class="stat-value"><?= money($coop['share_value'], $group['currency']) ?></span></div></div>
                </div>

                <div class="divider"></div>
                <h3 style="font-size:15px;margin-bottom:8px">How this co-op works</h3>
                <ul class="stack-sm" style="font-size:13.5px;color:var(--text);padding-left:18px">
                    <li>Shares cost <strong><?= money($coop['share_value'], $group['currency']) ?></strong> per unit.</li>
                    <li>Members can save from <strong><?= money($coop['monthly_savings'], $group['currency']) ?></strong> monthly.</li>
                    <li>Loans are capped at <strong><?= e($coop['max_loan_multiple']) ?>×</strong> your savings, at <?= e($coop['loan_interest_rate']) ?>% p.a. over <?= (int) $coop['loan_duration_months'] ?> months by default.</li>
                    <li>Loans need <strong><?= (int) $coop['guarantors_required'] ?></strong> guarantor(s) from the co-op.</li>
                </ul>

                <?php if (!$suspended): ?>
                    <form method="post" action="<?= url('groups/' . $group['slug'] . '/coop/join') ?>" class="mt-3" data-submit-once>
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-primary btn-block">
                            <?= $rejected ? 'Submit a new co-op request' : 'Request to join the cooperative' ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="page-head">
        <div>
            <h1><?= e($coop['name']) ?></h1>
            <p><?= e($group['name']) ?><?= !empty($coop['registration_no']) ? ' · ' . e($coop['registration_no']) : '' ?></p>
        </div>
        <div class="page-head-actions">
            <?php if ($isMember): ?>
                <a href="<?= url('groups/' . $group['slug'] . '/coop/me') ?>" class="btn btn-primary"><?= icon('user', 16) ?> My statement</a>
            <?php endif; ?>
            <?php if ($canManage): ?>
                <a href="<?= url('groups/' . $group['slug'] . '/coop/settings') ?>" class="btn btn-outline"><?= icon('settings', 15) ?> Settings</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($canManage && ($summary['pending_members'] ?? 0) > 0): ?>
        <div class="alert alert-warn">
            <span class="alert-icon"><?= icon('users', 18) ?></span>
            <div class="alert-body"><strong><?= (int) $summary['pending_members'] ?></strong> co-op membership request(s) awaiting review.</div>
            <a href="<?= url('groups/' . $group['slug'] . '/coop/members?status=pending') ?>" class="btn btn-sm btn-outline">Review</a>
        </div>
    <?php endif; ?>

    <?php if (!empty($guarantees)): ?>
        <div class="alert alert-info">
            <span class="alert-icon"><?= icon('shield', 18) ?></span>
            <div class="alert-body">You have <?= count($guarantees) ?> pending loan guarantee request(s).</div>
            <a href="<?= url('groups/' . $group['slug'] . '/coop/loans') ?>" class="btn btn-sm btn-outline">Review</a>
        </div>
    <?php endif; ?>

    <div class="stat-grid mb-3">
        <div class="stat-card"><span class="stat-icon"><?= icon('users', 20) ?></span><div class="stat-meta"><span class="stat-label">Members</span><span class="stat-value"><?= (int) ($summary['members'] ?? 0) ?></span><span class="stat-hint"><?= (int) ($summary['pending_members'] ?? 0) ?> pending</span></div></div>
        <div class="stat-card"><span class="stat-icon gold"><?= icon('wallet', 20) ?></span><div class="stat-meta"><span class="stat-label">Savings pool</span><span class="stat-value"><?= money($summary['savings'] ?? 0, $group['currency']) ?></span><span class="stat-hint">Member deposits</span></div></div>
        <div class="stat-card"><span class="stat-icon info"><?= icon('card', 20) ?></span><div class="stat-meta"><span class="stat-label">Share capital</span><span class="stat-value"><?= money($summary['shares'] ?? 0, $group['currency']) ?></span><span class="stat-hint">Paid-up shares</span></div></div>
        <div class="stat-card"><span class="stat-icon warn"><?= icon('chart', 20) ?></span><div class="stat-meta"><span class="stat-label">Loans outstanding</span><span class="stat-value"><?= money($summary['loans_outstanding'] ?? 0, $group['currency']) ?></span><span class="stat-hint"><?= (int) ($summary['active_loans'] ?? 0) ?> active</span></div></div>
    </div>

    <div class="split-main">
        <div class="stack">
            <?php if ($isMember): ?>
                <section class="card">
                    <div class="card-header">
                        <div><h2>My co-op position</h2><p class="sub"><?= e($coopMember['member_no'] ?? '') ?> · <?= e(App\Models\Cooperative::roleLabel($coopMember['role'])) ?></p></div>
                        <a href="<?= url('groups/' . $group['slug'] . '/coop/me') ?>" class="card-link">Statement <?= icon('arrow-right', 13) ?></a>
                    </div>
                    <div class="card-body">
                        <div class="grid-2">
                            <div class="stat-card" style="box-shadow:none"><span class="stat-icon"><?= icon('wallet', 19) ?></span><div class="stat-meta"><span class="stat-label">My savings</span><span class="stat-value"><?= money($savingsBalance ?? 0, $group['currency']) ?></span></div></div>
                            <div class="stat-card" style="box-shadow:none"><span class="stat-icon gold"><?= icon('card', 19) ?></span><div class="stat-meta"><span class="stat-label">My shares</span><span class="stat-value"><?= money($shareCapital ?? 0, $group['currency']) ?></span><span class="stat-hint"><?= e((float) ($coopMember['shares'] ?? 0)) ?> units</span></div></div>
                            <div class="stat-card" style="box-shadow:none"><span class="stat-icon warn"><?= icon('alert', 19) ?></span><div class="stat-meta"><span class="stat-label">Fines due</span><span class="stat-value"><?= money($finesUnpaid ?? 0, $group['currency']) ?></span></div></div>
                            <div class="stat-card" style="box-shadow:none"><span class="stat-icon info"><?= icon('chart', 19) ?></span><div class="stat-meta"><span class="stat-label">Active loan</span><span class="stat-value"><?= $activeLoan !== null ? money(App\Models\CoopLoan::outstanding($activeLoan), $group['currency']) : '—' ?></span><span class="stat-hint"><?= $activeLoan !== null ? e(App\Models\CoopLoan::statusLabel($activeLoan['status'])) : 'No active loan' ?></span></div></div>
                        </div>

                        <div class="flex flex-wrap mt-3">
                            <a href="<?= url('groups/' . $group['slug'] . '/coop/me') ?>" class="btn btn-primary btn-sm"><?= icon('plus', 14) ?> Save / buy shares</a>
                            <a href="<?= url('groups/' . $group['slug'] . '/coop/loans') ?>" class="btn btn-outline btn-sm">Apply for a loan</a>
                            <a href="<?= url('groups/' . $group['slug'] . '/coop/fines') ?>" class="btn btn-ghost btn-sm">My fines</a>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <section class="card">
                <div class="card-header">
                    <h2>Recent co-op activity</h2>
                    <?php if ($canManage): ?>
                        <a href="<?= url('groups/' . $group['slug'] . '/coop/transactions') ?>" class="card-link">Full ledger <?= icon('arrow-right', 13) ?></a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if ($transactions === []): ?>
                        <p class="muted small">No co-op transactions yet.</p>
                    <?php else: ?>
                        <div class="ledger">
                            <?php foreach ($transactions as $transaction): ?>
                                <div class="ledger-row">
                                    <span class="stat-icon" style="width:32px;height:32px;border-radius:9px"><?= icon($transaction['direction'] === 'in' ? 'download' : 'arrow-right', 15) ?></span>
                                    <div style="flex:1;min-width:0">
                                        <div style="font-size:13.5px;font-weight:600;color:var(--ink)"><?= e(App\Models\CoopTransaction::typeLabel($transaction['type'])) ?></div>
                                        <div class="ledger-note"><?= e($transaction['member_name'] ?? 'Co-op') ?> · <?= e(time_ago($transaction['occurred_at'])) ?></div>
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
            <?php if ($nextMeeting !== null): ?>
                <section class="card">
                    <div class="card-header"><h2>Next meeting</h2><span class="badge badge-info">Scheduled</span></div>
                    <div class="card-body">
                        <div class="list-item-title"><?= e($nextMeeting['title']) ?></div>
                        <div class="meta-row mt-1"><span><?= icon('calendar', 14) ?> <?= e(date_time($nextMeeting['scheduled_at'])) ?></span></div>
                        <?php if (!empty($nextMeeting['location'])): ?><div class="meta-row mt-1"><span><?= icon('location', 14) ?> <?= e($nextMeeting['location']) ?></span></div><?php endif; ?>
                        <a href="<?= url('groups/' . $group['slug'] . '/coop/meetings/' . $nextMeeting['id']) ?>" class="btn btn-outline btn-sm btn-block mt-2">View meeting</a>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($canManage): ?>
                <section class="card">
                    <div class="card-header"><h2>Manage</h2></div>
                    <div class="card-body">
                        <div class="stack-sm">
                            <a href="<?= url('groups/' . $group['slug'] . '/coop/members') ?>" class="btn btn-outline btn-block">Members</a>
                            <a href="<?= url('groups/' . $group['slug'] . '/coop/loans') ?>" class="btn btn-outline btn-block">Loan book</a>
                            <a href="<?= url('groups/' . $group['slug'] . '/coop/products') ?>" class="btn btn-outline btn-block">Loan products</a>
                            <a href="<?= url('groups/' . $group['slug'] . '/coop/transactions') ?>" class="btn btn-outline btn-block">Ledger &amp; manual entries</a>
                            <a href="<?= url('groups/' . $group['slug'] . '/coop/fines') ?>" class="btn btn-outline btn-block">Fines</a>
                            <a href="<?= url('groups/' . $group['slug'] . '/coop/meetings') ?>" class="btn btn-outline btn-block">Meetings</a>
                            <a href="<?= url('groups/' . $group['slug'] . '/coop/dividends') ?>" class="btn btn-outline btn-block">Dividends</a>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <section class="card">
                <div class="card-header"><h2>Co-op rules</h2></div>
                <div class="card-body">
                    <dl class="kv">
                        <dt>Share value</dt><dd><?= money($coop['share_value'], $group['currency']) ?></dd>
                        <dt>Monthly savings</dt><dd><?= money($coop['monthly_savings'], $group['currency']) ?></dd>
                        <dt>Loan interest</dt><dd><?= e($coop['loan_interest_rate']) ?>% p.a. (flat)</dd>
                        <dt>Loan duration</dt><dd><?= (int) $coop['loan_duration_months'] ?> months</dd>
                        <dt>Loan multiple</dt><dd><?= e($coop['max_loan_multiple']) ?>× savings</dd>
                        <dt>Guarantors</dt><dd><?= (int) $coop['guarantors_required'] ?></dd>
                        <dt>Absence fine</dt><dd><?= money($coop['fine_absent'], $group['currency']) ?></dd>
                    </dl>
                </div>
            </section>
        </div>
    </div>
<?php endif; ?>
