<?php
$title = 'Co-op dividends';
$membership = $groupMembership ?? null;
require BASE_PATH . '/app/Views/partials/group-header.php';
?>
<div class="page-head">
    <div>
        <h1>Dividends</h1>
        <p>Share surplus with members pro-rata to shares or savings.</p>
    </div>
    <?php if ($isOfficer): ?>
        <button type="button" class="btn btn-primary" data-modal-open="dividend-modal"><?= icon('plus', 16) ?> Declare dividend</button>
    <?php endif; ?>
</div>

<?php if ($dividends === []): ?>
    <div class="card"><div class="empty-state">
        <span class="empty-icon"><?= icon('chart', 22) ?></span>
        <h3>No dividends declared</h3>
        <p><?= $isOfficer ? 'Declare a surplus distribution when the co-op has funds to share.' : 'Dividends declared by the officers will appear here.' ?></p>
    </div></div>
<?php else: ?>
    <div class="stack">
        <?php foreach ($dividends as $dividend): ?>
            <article class="card">
                <div class="card-body">
                    <div class="between" style="align-items:flex-start;flex-wrap:wrap;gap:12px">
                        <div>
                            <div class="flex" style="gap:8px;flex-wrap:wrap">
                                <h2 style="font-size:17px"><a href="<?= url('groups/' . $group['slug'] . '/coop/dividends/' . $dividend['id']) ?>" style="color:inherit"><?= e($dividend['title']) ?></a></h2>
                                <span class="badge badge-<?= $dividend['status'] === 'distributed' ? 'success' : 'warn' ?>"><?= e(ucfirst($dividend['status'])) ?></span>
                            </div>
                            <div class="meta-row mt-1">
                                <span><?= e($dividend['period'] ?: 'No period set') ?></span>
                                <span class="dot-sep">·</span>
                                <span>Based on <?= e($dividend['basis']) ?></span>
                                <span class="dot-sep">·</span>
                                <span><?= (int) $dividend['allocation_count'] ?> allocations</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="stat-value"><?= money($dividend['total_amount'], $group['currency']) ?></div>
                            <a href="<?= url('groups/' . $group['slug'] . '/coop/dividends/' . $dividend['id']) ?>" class="card-link">Open <?= icon('arrow-right', 13) ?></a>
                        </div>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($isOfficer): ?>
    <div class="modal" id="dividend-modal" role="dialog" aria-modal="true">
        <div class="modal-backdrop" data-modal-close></div>
        <div class="modal-panel">
            <form method="post" action="<?= url('groups/' . $group['slug'] . '/coop/dividends') ?>" data-submit-once>
                <?= csrf_field() ?>
                <div class="modal-head"><h2>Declare a dividend</h2><button type="button" class="icon-btn" data-modal-close><?= icon('x', 18) ?></button></div>
                <div class="modal-body">
                    <div class="stack-sm">
                        <div class="field">
                            <label for="title">Title</label>
                            <input type="text" id="title" name="title" required placeholder="e.g. 2025 Surplus Distribution">
                        </div>
                        <div class="form-grid">
                            <div class="field">
                                <label for="period">Period</label>
                                <input type="text" id="period" name="period" placeholder="e.g. 2025">
                            </div>
                            <div class="field">
                                <label for="total_amount">Total amount</label>
                                <input type="number" id="total_amount" name="total_amount" min="0.01" step="0.01" required value="0">
                            </div>
                        </div>
                        <div class="field">
                            <label for="basis">Allocate by</label>
                            <select id="basis" name="basis" class="select">
                                <option value="shares">Share capital</option>
                                <option value="savings">Savings balance</option>
                            </select>
                        </div>
                        <div class="callout info">
                            <?= icon('info', 17) ?>
                            <div>Allocations are calculated from current balances and can be reviewed before distribution.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-foot">
                    <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
                    <button type="submit" class="btn btn-primary">Declare dividend</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
