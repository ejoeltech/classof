<?php
$title = 'Welfare';
require BASE_PATH . '/app/Views/partials/group-header.php';
$tabs = [
    '' => 'All cases',
    'open' => 'Open',
    'funded' => 'Fully funded',
    'closed' => 'Closed',
];
?>
<div class="page-head">
    <div>
        <h1>Welfare</h1>
        <p>Support for classmates who need a helping hand, with full transparency.</p>
    </div>
    <a href="<?= url('groups/' . $group['slug'] . '/welfare/create') ?>" class="btn btn-primary"><?= icon('plus', 16) ?> Open a case</a>
</div>

<div class="stat-grid cols-3 mb-3">
    <div class="stat-card">
        <span class="stat-icon gold"><?= icon('heart', 20) ?></span>
        <div class="stat-meta">
            <span class="stat-label">Total raised</span>
            <span class="stat-value"><?= money($raised, $group['currency']) ?></span>
            <span class="stat-hint">Across shown cases</span>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon"><?= icon('users', 20) ?></span>
        <div class="stat-meta">
            <span class="stat-label">Cases shown</span>
            <span class="stat-value"><?= count($cases) ?></span>
            <span class="stat-hint">Verified by the exco</span>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon info"><?= icon('shield', 20) ?></span>
        <div class="stat-meta">
            <span class="stat-label">Ledger</span>
            <span class="stat-value" style="font-size:16px">Open to members</span>
            <span class="stat-hint">Every contribution recorded</span>
        </div>
    </div>
</div>

<div class="filter-bar">
    <?php foreach ($tabs as $key => $label): ?>
        <a href="<?= url('groups/' . $group['slug'] . '/welfare' . ($key !== '' ? '?status=' . $key : '')) ?>"
           class="btn btn-sm <?= $status === $key ? 'btn-primary' : 'btn-outline' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</div>

<?php if ($cases === []): ?>
    <div class="card">
        <div class="empty-state">
            <span class="empty-icon"><?= icon('heart', 22) ?></span>
            <h3>No welfare cases here</h3>
            <p>When a classmate needs support — bereavement, medical, education — open a case so members can contribute.</p>
            <a href="<?= url('groups/' . $group['slug'] . '/welfare/create') ?>" class="btn btn-primary btn-sm mt-2">Open a case</a>
        </div>
    </div>
<?php else: ?>
    <div class="grid-3">
        <?php foreach ($cases as $case): ?>
            <?php
            $target = (float) ($case['target_amount'] ?? 0);
            $caseRaised = (float) $case['raised'];
            $pct = $target > 0 ? percent($caseRaised, $target) : null;
            $tone = match ($case['status']) {
                'open' => 'success',
                'funded' => 'info',
                default => 'neutral',
            };
            ?>
            <article class="card welfare-card">
                <div class="card-body">
                    <div class="welfare-head">
                        <span class="welfare-cat"><?= icon('heart', 18) ?></span>
                        <div style="flex:1;min-width:0">
                            <div class="list-item-title"><a href="<?= url('groups/' . $group['slug'] . '/welfare/' . $case['id']) ?>"><?= e($case['title']) ?></a></div>
                            <div class="list-item-meta">
                                <?= e(App\Models\WelfareCase::categoryLabel($case['category'])) ?>
                                <span class="dot-sep">·</span>
                                <span><?= e(((int) $case['is_anonymous'] === 1 && !$isManager) ? 'A classmate' : $case['beneficiary_name']) ?></span>
                            </div>
                        </div>
                        <span class="badge badge-<?= $tone ?>"><?= e(ucfirst($case['status'])) ?></span>
                    </div>

                    <p class="list-item-text"><?= e(str_limit($case['description'], 130)) ?></p>

                    <div class="welfare-amounts">
                        <div>
                            <div class="welfare-raised"><?= money($caseRaised, $case['currency']) ?></div>
                            <div class="welfare-target"><?= $target > 0 ? 'of ' . money($target, $case['currency']) : 'No target set' ?></div>
                        </div>
                        <div class="text-right tiny muted"><?= (int) $case['contributors'] ?> <?= (int) $case['contributors'] === 1 ? 'contributor' : 'contributors' ?></div>
                    </div>

                    <?php if ($pct !== null): ?>
                        <div class="progress gold"><span style="width:<?= (int) $pct ?>%"></span></div>
                    <?php endif; ?>

                    <a href="<?= url('groups/' . $group['slug'] . '/welfare/' . $case['id']) ?>" class="btn btn-outline btn-sm btn-block"><?= $case['status'] === 'open' ? 'Contribute' : 'View case' ?></a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
