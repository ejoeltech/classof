<?php
$title = $case['title'];
require BASE_PATH . '/app/Views/partials/group-header.php';
$gatewayLabel = App\Services\Payments\PaymentManager::current()->label();
$beneficiaryName = ((int) $case['is_anonymous'] === 1 && !$isManager) ? 'A classmate' : $case['beneficiary_name'];
$statusTone = match ($case['status']) {
    'open' => 'success',
    'funded' => 'info',
    default => 'neutral',
};
?>
<div class="page-head">
    <div>
        <a href="<?= url('groups/' . $group['slug'] . '/welfare') ?>" class="tiny muted" style="display:inline-flex;align-items:center;gap:4px;margin-bottom:6px"><?= icon('chevron-right', 13) ?> All welfare cases</a>
        <h1><?= e($case['title']) ?></h1>
        <p>Opened <?= e(short_date($case['created_at'])) ?> by <?= e($beneficiaryName) ?></p>
    </div>
    <?php if ($isManager && $case['status'] !== 'closed'): ?>
        <form method="post" action="<?= url('groups/' . $group['slug'] . '/welfare/' . $case['id'] . '/close') ?>" data-confirm="Close this welfare case? Contributions will stop.">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline btn-sm">Close case</button>
        </form>
    <?php endif; ?>
</div>

<div class="split-main">
    <div class="stack">
        <section class="card">
            <div class="card-body">
                <div class="between" style="align-items:flex-start;flex-wrap:wrap;gap:14px">
                    <div class="flex" style="align-items:flex-start">
                        <span class="welfare-cat" style="width:46px;height:46px;border-radius:13px"><?= icon('heart', 21) ?></span>
                        <div>
                            <div class="pill-row mb-1">
                                <span class="badge badge-neutral"><?= e(App\Models\WelfareCase::categoryLabel($case['category'])) ?></span>
                                <span class="badge badge-<?= $statusTone ?>"><?= e(ucfirst($case['status'])) ?></span>
                            </div>
                            <div class="flex" style="gap:8px">
                                <?= avatar($beneficiaryName, (int) $case['is_anonymous'] === 1 && !$isManager ? null : ($case['avatar'] ?? null), 28) ?>
                                <span class="small"><strong><?= e($beneficiaryName) ?></strong> · beneficiary</span>
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="welfare-raised" style="font-size:28px"><?= money($raised, $case['currency']) ?></div>
                        <div class="welfare-target"><?= $target > 0 ? 'of ' . money($target, $case['currency']) . ' target' : 'Raised so far' ?></div>
                    </div>
                </div>

                <?php if ($progress !== null): ?>
                    <div class="mt-2">
                        <div class="between tiny muted" style="margin-bottom:5px">
                            <span><?= (int) round($progress) ?>% funded</span>
                            <span><?= $target > $raised ? money($target - $raised, $case['currency']) . ' to go' : 'Target reached' ?></span>
                        </div>
                        <div class="progress gold"><span style="width:<?= (int) $progress ?>%"></span></div>
                    </div>
                <?php endif; ?>

                <div class="prose mt-3" style="border-top:1px solid var(--line);padding-top:18px">
                    <?= nl2br(e($case['description'])) ?>
                </div>

                <?php if ($case['status'] === 'funded'): ?>
                    <div class="callout mt-2">
                        <?= icon('check', 17) ?>
                        <div><strong>Fully funded.</strong> Thank you to everyone who contributed. The exco will coordinate disbursement.</div>
                    </div>
                <?php elseif ($case['status'] === 'closed'): ?>
                    <div class="callout info mt-2">
                        <?= icon('lock', 17) ?>
                        <div>This case is closed and no longer accepting contributions.</div>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <h2>Contribution ledger</h2>
                <span class="badge badge-neutral"><?= count($contributions) ?> entries</span>
            </div>
            <div class="card-body">
                <?php if ($contributions === []): ?>
                    <p class="muted small">No contributions yet. Be the first to give.</p>
                <?php else: ?>
                    <div class="ledger">
                        <?php foreach ($contributions as $contribution): ?>
                            <div class="ledger-row">
                                <?= avatar($contribution['name'], $contribution['avatar'] ?? null, 32) ?>
                                <div style="flex:1;min-width:0">
                                    <div style="font-size:13.5px;font-weight:600;color:var(--ink)"><?= e($contribution['name']) ?></div>
                                    <div class="ledger-note"><?= e(time_ago($contribution['created_at'])) ?><?= !empty($contribution['note']) ? ' · “' . e($contribution['note']) . '”' : '' ?></div>
                                </div>
                                <div class="ledger-amount"><?= money($contribution['amount'], $case['currency']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <div class="stack">
        <?php if ($case['status'] === 'open'): ?>
            <section class="card">
                <div class="card-header"><h2>Make a contribution</h2></div>
                <div class="card-body">
                    <?php if ($myContribution > 0): ?>
                        <div class="status-line success mb-2">
                            <?= icon('check', 16) ?> You have given <?= money($myContribution, $case['currency']) ?>
                        </div>
                    <?php endif; ?>
                    <form method="post" action="<?= url('payments/initiate') ?>" data-submit-once>
                        <?= csrf_field() ?>
                        <input type="hidden" name="purpose" value="welfare">
                        <input type="hidden" name="group_id" value="<?= (int) $group['id'] ?>">
                        <input type="hidden" name="welfare_case_id" value="<?= (int) $case['id'] ?>">

                        <div class="field">
                            <label for="contribution-amount">Amount (<?= e($group['currency']) ?>)</label>
                            <input type="number" id="contribution-amount" name="amount" min="1" step="0.01" required placeholder="Enter amount" value="<?= e(old('amount')) ?>">
                        </div>

                        <div class="amount-presets mt-2">
                            <?php foreach ([1000, 2500, 5000, 10000] as $preset): ?>
                                <button type="button" class="amount-preset" data-amount="<?= $preset ?>" data-amount-target="#contribution-amount">
                                    <?= money($preset, $group['currency']) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>

                        <div class="field mt-2">
                            <label for="note">Message <span class="hint">(optional)</span></label>
                            <input type="text" id="note" name="note" maxlength="255" placeholder="A word of encouragement…">
                        </div>

                        <button type="submit" class="btn btn-gold btn-lg btn-block mt-2">Contribute</button>
                        <p class="tiny muted text-center mt-1" style="margin-bottom:0">Secure checkout via <?= e($gatewayLabel) ?></p>
                    </form>
                </div>
            </section>
        <?php endif; ?>

        <section class="card">
            <div class="card-header"><h2>Case summary</h2></div>
            <div class="card-body">
                <dl class="kv">
                    <dt>Beneficiary</dt><dd><?= e($beneficiaryName) ?></dd>
                    <dt>Category</dt><dd><?= e(App\Models\WelfareCase::categoryLabel($case['category'])) ?></dd>
                    <dt>Raised</dt><dd><?= money($raised, $case['currency']) ?></dd>
                    <dt>Contributors</dt><dd><?= count($contributions) ?></dd>
                    <dt>Status</dt><dd><span class="badge badge-<?= $statusTone ?>"><?= e(ucfirst($case['status'])) ?></span></dd>
                </dl>
                <div class="divider"></div>
                <div class="callout info">
                    <?= icon('shield', 17) ?>
                    <div>Contributions are recorded against your name in the ledger above. Payment references are kept in the treasury.</div>
                </div>
            </div>
        </section>
    </div>
</div>
