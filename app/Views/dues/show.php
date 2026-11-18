<?php
$title = $cycle['title'];
require BASE_PATH . '/app/Views/partials/group-header.php';
$paidMembers = (int) $cycle['paid_members'];
$activeMembers = max(1, (int) $cycle['active_members']);
$pct = percent($paidMembers, $activeMembers);
$paid = $myPayment !== null;
$gatewayLabel = App\Services\Payments\PaymentManager::current()->label();
?>
<div class="page-head">
    <div>
        <a href="<?= url('groups/' . $group['slug'] . '/dues') ?>" class="tiny muted" style="display:inline-flex;align-items:center;gap:4px;margin-bottom:6px"><?= icon('chevron-right', 13) ?> All dues</a>
        <h1><?= e($cycle['title']) ?></h1>
        <p>Due <?= e(short_date($cycle['due_date'])) ?> · <?= e($group['name']) ?></p>
    </div>
    <?php if ($isManager && $cycle['status'] === 'open'): ?>
        <form method="post" action="<?= url('groups/' . $group['slug'] . '/dues/' . $cycle['id'] . '/close') ?>" data-confirm="Close this dues cycle? Members will no longer be able to pay.">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline btn-sm">Close cycle</button>
        </form>
    <?php endif; ?>
</div>

<div class="split-main">
    <div class="stack">
        <section class="card">
            <div class="card-body">
                <div class="between" style="align-items:flex-start">
                    <div>
                        <p class="muted tiny" style="text-transform:uppercase;letter-spacing:.08em;font-weight:700;margin-bottom:4px">Amount due</p>
                        <div style="font-size:38px;font-weight:700;letter-spacing:-.03em;color:var(--ink);line-height:1.1"><?= money($cycle['amount'], $cycle['currency']) ?></div>
                        <p class="muted small mt-1"><?= e($cycle['description'] ?: 'No description provided.') ?></p>
                    </div>
                    <span class="badge badge-<?= $cycle['status'] === 'open' ? 'success' : 'neutral' ?>"><?= $cycle['status'] === 'open' ? 'Open' : 'Closed' ?></span>
                </div>

                <div class="divider"></div>

                <div class="between" style="font-size:12.5px;color:var(--muted);margin-bottom:6px">
                    <span><?= $paidMembers ?> of <?= (int) $cycle['active_members'] ?> members paid</span>
                    <strong style="color:var(--forest-700)"><?= money($cycle['collected'], $cycle['currency']) ?> collected</strong>
                </div>
                <div class="progress"><span style="width:<?= (int) $pct ?>%"></span></div>
            </div>
        </section>

        <?php if ($isManager): ?>
            <section class="card">
                <div class="card-header">
                    <h2>Payment ledger</h2>
                    <span class="badge badge-neutral"><?= count($payers) ?> paid</span>
                </div>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Member</th><th>Amount</th><th>Reference</th><th>Paid</th></tr></thead>
                        <tbody>
                        <?php if ($payers === []): ?>
                            <tr><td colspan="4" class="empty-row">No payments recorded yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($payers as $payment): ?>
                            <tr>
                                <td>
                                    <div class="table-person">
                                        <?= avatar($payment['name'], $payment['avatar'] ?? null, 30) ?>
                                        <div class="table-person-meta">
                                            <strong><?= e($payment['name']) ?></strong>
                                            <span><?= e($payment['email']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="strong"><?= money($payment['amount'], $payment['currency']) ?></td>
                                <td class="tiny muted"><?= e($payment['reference']) ?></td>
                                <td class="small muted nowrap"><?= e(short_date($payment['paid_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card">
                <div class="card-header">
                    <h2>Yet to pay</h2>
                    <span class="badge badge-warn"><?= count($defaulters) ?> outstanding</span>
                </div>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Member</th><th>Contact</th><th class="text-right">Chase</th></tr></thead>
                        <tbody>
                        <?php if ($defaulters === []): ?>
                            <tr><td colspan="3" class="empty-row">Everyone has paid. 🎉</td></tr>
                        <?php endif; ?>
                        <?php foreach ($defaulters as $member): ?>
                            <?php
                            $phone = $member['phone_e164'] ?: ($member['phone'] ?? null);
                            $reminder = 'Hello ' . $member['name'] . ', this is a friendly reminder from ' . $group['name']
                                . '. ' . $cycle['title'] . ' of ' . money($cycle['amount'], $group['currency'])
                                . ' was due on ' . short_date($cycle['due_date']) . '. '
                                . 'Open Classof to get your payment reference and pay: '
                                . absolute_url('groups/' . $group['slug'] . '/dues/' . $cycle['id'])
                                . '. Please disregard this message if you have already paid.';
                            ?>
                            <tr>
                                <td>
                                    <div class="table-person">
                                        <?= avatar($member['name'], $member['avatar'] ?? null, 30) ?>
                                        <div class="table-person-meta"><strong><?= e($member['name']) ?></strong></div>
                                    </div>
                                </td>
                                <td class="small muted">
                                    <?= e($member['email']) ?>
                                    <?php if (!empty($phone)): ?><div><?= e($phone) ?></div><?php endif; ?>
                                </td>
                                <td class="text-right">
                                    <a href="<?= e(whatsapp_share_url($phone, $reminder)) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm">
                                        <?= icon('message', 14) ?> WhatsApp
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    </div>

    <div class="stack">
        <section class="card">
            <div class="card-header"><h2>Your payment</h2></div>
            <div class="card-body">
                <?php if ($paid): ?>
                    <div class="status-line success mb-2"><?= icon('check', 16) ?> Paid in full</div>
                    <dl class="kv">
                        <dt>Amount</dt><dd><?= money($myPayment['amount'], $myPayment['currency']) ?></dd>
                        <dt>Reference</dt><dd class="reference-box" style="font-size:12px"><?= e($myPayment['reference']) ?></dd>
                        <dt>Paid on</dt><dd><?= e(short_date($myPayment['paid_at'])) ?></dd>
                        <dt>Method</dt><dd><?= e(ucfirst($myPayment['gateway'])) ?></dd>
                    </dl>
                    <a href="<?= url('payments/' . $myPayment['id'] . '/instructions') ?>" class="btn btn-outline btn-block mt-2">View receipt</a>
                <?php elseif (!empty($pendingPayment)): ?>
                    <div class="status-line pending mb-2"><?= icon('clock', 16) ?> Awaiting confirmation</div>
                    <dl class="kv">
                        <dt>Amount</dt><dd><?= money($pendingPayment['amount'], $pendingPayment['currency']) ?></dd>
                        <dt>Reference</dt><dd class="reference-box" style="font-size:12px"><?= e($pendingPayment['reference']) ?></dd>
                        <dt>Started</dt><dd><?= e(short_date($pendingPayment['created_at'])) ?></dd>
                    </dl>
                    <a href="<?= url('payments/' . $pendingPayment['id'] . '/instructions') ?>" class="btn btn-outline btn-block mt-2">View payment instructions</a>
                    <p class="tiny muted mt-1 mb-0">If you have already sent the money, a group admin will confirm it shortly.</p>
                <?php elseif ($cycle['status'] !== 'open'): ?>
                    <p class="muted small">This dues cycle is closed. Contact a group admin if you believe you have paid.</p>
                <?php else: ?>
                    <p class="muted small">You have not paid this dues cycle yet.</p>
                    <div class="callout mt-2">
                        <?= icon('card', 17) ?>
                        <div>Checkout via <strong><?= e($gatewayLabel) ?></strong>. The amount is fixed at <?= money($cycle['amount'], $cycle['currency']) ?>.</div>
                    </div>
                    <form method="post" action="<?= url('payments/initiate') ?>" class="mt-2" data-submit-once>
                        <?= csrf_field() ?>
                        <input type="hidden" name="purpose" value="dues">
                        <input type="hidden" name="group_id" value="<?= (int) $group['id'] ?>">
                        <input type="hidden" name="dues_cycle_id" value="<?= (int) $cycle['id'] ?>">
                        <button type="submit" class="btn btn-primary btn-lg btn-block">Pay <?= money($cycle['amount'], $cycle['currency']) ?></button>
                    </form>
                <?php endif; ?>
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2>How dues are used</h2></div>
            <div class="card-body">
                <p class="muted small">Dues fund the welfare reserve, AGM logistics and group projects. Every successful payment is recorded in the treasury ledger with a unique reference.</p>
                <div class="callout info">
                    <?= icon('shield', 17) ?>
                    <div>Only group admins see the full ledger. Members always see their own receipts.</div>
                </div>
            </div>
        </section>
    </div>
</div>
