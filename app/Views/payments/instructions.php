<?php
$title = 'Payment ' . $payment['reference'];
$destination = match ($payment['purpose']) {
    'dues' => 'groups/' . $payment['group_slug'] . '/dues/' . $payment['dues_cycle_id'],
    'welfare' => 'groups/' . $payment['group_slug'] . '/welfare/' . $payment['welfare_case_id'],
    'event' => 'groups/' . $payment['group_slug'] . '/events/' . $payment['event_id'],
    default => 'dashboard',
};
$isManual = $payment['gateway'] === 'manual';
$meta = [];

if (!empty($payment['meta'])) {
    $decodedMeta = json_decode((string) $payment['meta'], true);
    $meta = is_array($decodedMeta) ? $decodedMeta : [];
}

$claimedAt = $meta['member_claimed_at'] ?? null;
$claimNote = $meta['member_claim_note'] ?? null;
$shareText = ($payment['group_name'] ?? 'Group') . ' payment of ' . money($payment['amount'], $payment['currency'])
    . "\nBank: " . ($bank['bank_name'] ?? '')
    . "\nAccount: " . ($bank['account_number'] ?? '')
    . "\nName: " . ($bank['account_holder'] ?? '')
    . "\nReference: " . $payment['reference']
    . "\nAmount: " . money($payment['amount'], $payment['currency']);
$statusTone = match ($payment['status']) {
    'success' => 'success',
    'pending' => 'warn',
    'failed', 'refunded' => 'danger',
    default => 'neutral',
};
?>
<div class="page-head">
    <div>
        <a href="<?= url($destination) ?>" class="tiny muted" style="display:inline-flex;align-items:center;gap:4px;margin-bottom:6px"><?= icon('chevron-right', 13) ?> Back</a>
        <h1>Payment instructions</h1>
        <p>Reference <span class="reference-box"><?= e($payment['reference']) ?></span></p>
    </div>
    <span class="badge badge-<?= $statusTone ?>"><?= e(ucfirst($payment['status'])) ?></span>
</div>

<div class="split-main">
    <div class="stack">
        <section class="payment-summary">
            <p class="eyebrow" style="color:var(--gold)">Amount due</p>
            <h2 style="font-size:32px"><?= money($payment['amount'], $payment['currency']) ?></h2>
            <p class="mt-1"><?= e(App\Models\Payment::PURPOSES[$payment['purpose']] ?? $payment['purpose']) ?> · <?= e($payment['group_name']) ?></p>
            <div class="mt-3" style="border-top:1px solid rgba(255,255,255,.16);padding-top:16px">
                <div class="between" style="font-size:13px;color:#cfe3d8">
                    <span>Reference</span>
                    <span class="reference-box" style="font-size:12px"><?= e($payment['reference']) ?></span>
                </div>
                <div class="between mt-1" style="font-size:13px;color:#cfe3d8">
                    <span>Method</span>
                    <span><?= e(ucfirst($payment['gateway'])) ?></span>
                </div>
                <div class="between mt-1" style="font-size:13px;color:#cfe3d8">
                    <span>Initiated</span>
                    <span><?= e(date_time($payment['created_at'])) ?></span>
                </div>
            </div>
        </section>

        <?php if ($payment['status'] === 'success'): ?>
            <div class="status-line success"><?= icon('check', 17) ?> Payment confirmed on <?= e(date_time($payment['paid_at'])) ?></div>
        <?php elseif ($payment['status'] === 'failed'): ?>
            <div class="status-line failed"><?= icon('alert', 17) ?> This payment attempt failed. No money has been taken.</div>
        <?php else: ?>
            <div class="status-line pending"><?= icon('clock', 17) ?> Payment is pending confirmation.</div>
        <?php endif; ?>
    </div>

    <div class="stack">
        <?php if ($isManual && $payment['status'] === 'pending'): ?>
            <section class="card">
                <div class="card-header"><h2>Bank transfer details</h2></div>
                <div class="card-body">
                    <div class="bank-box">
                        <div class="bank-row"><span>Bank</span><strong><?= e($bank['bank_name'] ?? '') ?></strong></div>
                        <div class="bank-row"><span>Account name</span><strong><?= e($bank['account_holder'] ?? '') ?></strong></div>
                        <div class="bank-row">
                            <span>Account number</span>
                            <span class="flex" style="gap:6px">
                                <strong><?= e($bank['account_number'] ?? '') ?></strong>
                                <button type="button" class="btn btn-outline btn-sm" data-copy="<?= e($bank['account_number'] ?? '') ?>">Copy</button>
                            </span>
                        </div>
                        <div class="bank-row"><span>Reference</span><strong class="reference-box" style="font-size:12px"><?= e($payment['reference']) ?></strong></div>
                    </div>

                    <ol class="stack-sm mt-2" style="font-size:13px;color:var(--text);padding-left:18px">
                        <li>Transfer <strong><?= money($payment['amount'], $payment['currency']) ?></strong> to the account above.</li>
                        <li>Use <strong><?= e($payment['reference']) ?></strong> as the transfer narration.</li>
                        <li>A group admin will confirm your payment. Your record updates automatically.</li>
                    </ol>

                    <div class="copy-row">
                        <input type="text" value="<?= e($payment['reference']) ?>" readonly data-select>
                        <button type="button" class="btn btn-outline btn-sm" data-copy="<?= e($payment['reference']) ?>">Copy ref</button>
                    </div>

                    <div class="callout mt-2">
                        <?= icon('info', 17) ?>
                        <div>Keep your bank receipt. If confirmation takes longer than 24 hours, send it to a group admin with this reference.</div>
                    </div>

                    <div class="divider"></div>

                    <?php if ($claimedAt !== null): ?>
                        <div class="status-line pending" style="margin-bottom:10px">
                            <?= icon('check', 16) ?> You marked this transfer as sent on <?= e(date_time($claimedAt)) ?>.
                        </div>
                        <?php if (!empty($claimNote)): ?>
                            <p class="small muted">Your note: “<?= e($claimNote) ?>”</p>
                        <?php endif; ?>
                        <p class="tiny muted">The group treasurer can now see this in the treasury. Confirmation still happens after the funds are checked.</p>
                    <?php else: ?>
                        <form method="post" action="<?= url('payments/' . $payment['id'] . '/claim') ?>" class="stack-sm" data-submit-once>
                            <?= csrf_field() ?>
                            <div class="field">
                                <label for="claim_note">Already sent the transfer?</label>
                                <input type="text" id="claim_note" name="claim_note" maxlength="255" placeholder="Optional note, e.g. sent from GTBank at 10:15">
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">I have sent this transfer</button>
                        </form>
                    <?php endif; ?>

                    <a href="<?= e(whatsapp_share_url(null, $shareText)) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-block mt-2">
                        <?= icon('message', 15) ?> Send transfer details on WhatsApp
                    </a>
                </div>
            </section>
        <?php elseif ($payment['status'] === 'pending'): ?>
            <section class="card">
                <div class="card-header"><h2>Finish your payment</h2></div>
                <div class="card-body">
                    <p class="muted small">Your payment with <?= e(ucfirst($payment['gateway'])) ?> has not been confirmed yet. If you completed checkout, you can check the status now.</p>
                    <a href="<?= url('payments/' . $payment['id'] . '/callback') ?>" class="btn btn-primary btn-block"><?= icon('check', 16) ?> Check payment status</a>
                    <a href="<?= url($destination) ?>" class="btn btn-ghost btn-block mt-1">Return without paying</a>
                </div>
            </section>
        <?php elseif ($payment['status'] === 'failed'): ?>
            <section class="card">
                <div class="card-header"><h2>Try again</h2></div>
                <div class="card-body">
                    <p class="muted small">That attempt did not go through. You can start a fresh payment from the group page.</p>
                    <a href="<?= url($destination) ?>" class="btn btn-primary btn-block">Return to <?= e(ucfirst($payment['purpose'])) ?></a>
                </div>
            </section>
        <?php else: ?>
            <section class="card">
                <div class="card-header"><h2>Receipt</h2></div>
                <div class="card-body">
                    <dl class="kv">
                        <dt>Reference</dt><dd><?= e($payment['reference']) ?></dd>
                        <dt>Amount</dt><dd><?= money($payment['amount'], $payment['currency']) ?></dd>
                        <dt>Paid on</dt><dd><?= e(date_time($payment['paid_at'])) ?></dd>
                        <dt>Payer</dt><dd><?= e($payment['payer_name'] ?? $payment['user_name'] ?? '') ?></dd>
                        <dt>Status</dt><dd><span class="badge badge-success">Confirmed</span></dd>
                    </dl>
                    <a href="<?= url($destination) ?>" class="btn btn-outline btn-block mt-2">Return to group</a>
                </div>
            </section>
        <?php endif; ?>
    </div>
</div>
