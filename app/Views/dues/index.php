<?php
$title = 'Dues';
require BASE_PATH . '/app/Views/partials/group-header.php';
?>
<div class="page-head">
    <div>
        <h1>Dues</h1>
        <p>Annual and special levies that keep the association running.</p>
    </div>
    <?php if ($isManager): ?>
        <button type="button" class="btn btn-primary" data-modal-open="dues-modal"><?= icon('plus', 16) ?> New dues cycle</button>
    <?php endif; ?>
</div>

<?php if ($cycles === []): ?>
    <div class="card">
        <div class="empty-state">
            <span class="empty-icon"><?= icon('wallet', 22) ?></span>
            <h3>No dues cycles yet</h3>
            <p><?= $isManager ? 'Create a dues cycle so members know what is due and when.' : 'The exco has not created a dues cycle yet.' ?></p>
            <?php if ($isManager): ?>
                <button type="button" class="btn btn-primary btn-sm mt-2" data-modal-open="dues-modal">Create dues cycle</button>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="stack">
        <?php foreach ($cycles as $cycle): ?>
            <?php
            $paidMembers = (int) $cycle['paid_members'];
            $activeMembers = max(1, (int) $cycle['active_members']);
            $pct = percent($paidMembers, $activeMembers);
            $paid = $cycle['my_payment'] !== null;
            $pending = !empty($cycle['my_pending']);
            $overdue = $cycle['status'] === 'open' && strtotime((string) $cycle['due_date']) < time();
            ?>
            <article class="card">
                <div class="card-body">
                    <div class="between" style="align-items:flex-start;flex-wrap:wrap;gap:14px">
                        <div>
                            <div class="flex" style="gap:8px;flex-wrap:wrap">
                                <h2 style="font-size:17px"><a href="<?= url('groups/' . $group['slug'] . '/dues/' . $cycle['id']) ?>" style="color:inherit"><?= e($cycle['title']) ?></a></h2>
                                <span class="badge badge-<?= $cycle['status'] === 'open' ? 'success' : 'neutral' ?>"><?= $cycle['status'] === 'open' ? 'Open' : 'Closed' ?></span>
                                <?php if ($paid): ?>
                                    <span class="badge badge-success"><?= icon('check', 12) ?> You paid</span>
                                <?php elseif ($pending): ?>
                                    <span class="badge badge-warn"><?= icon('clock', 12) ?> Awaiting confirmation</span>
                                <?php elseif ($cycle['status'] === 'open'): ?>
                                    <span class="badge badge-<?= $overdue ? 'danger' : 'warn' ?>"><?= $overdue ? 'Overdue' : 'Unpaid' ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="meta-row mt-1">
                                <span><?= icon('clock', 15) ?> Due <?= e(short_date($cycle['due_date'])) ?></span>
                                <?php if (!empty($cycle['description'])): ?>
                                    <span class="dot-sep">·</span><span><?= e(str_limit($cycle['description'], 120)) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="stat-value"><?= money($cycle['amount'], $cycle['currency']) ?></div>
                            <a href="<?= url('groups/' . $group['slug'] . '/dues/' . $cycle['id']) ?>" class="card-link"><?= $paid ? 'View receipt' : ($pending ? 'Check payment' : ($cycle['status'] === 'open' ? 'Pay now' : 'View details')) ?> <?= icon('arrow-right', 13) ?></a>
                        </div>
                    </div>

                    <div class="mt-2">
                        <div class="between" style="font-size:12px;color:var(--muted);margin-bottom:5px">
                            <span><?= $paidMembers ?> of <?= (int) $cycle['active_members'] ?> members paid</span>
                            <?php if ($isManager): ?>
                                <strong style="color:var(--forest-700)"><?= money($cycle['collected'], $cycle['currency']) ?> collected</strong>
                            <?php else: ?>
                                <span><?= (int) round($pct) ?>%</span>
                            <?php endif; ?>
                        </div>
                        <div class="progress"><span style="width:<?= (int) $pct ?>%"></span></div>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($isManager): ?>
    <div class="modal" id="dues-modal" role="dialog" aria-modal="true" aria-labelledby="dues-modal-title">
        <div class="modal-backdrop" data-modal-close></div>
        <div class="modal-panel">
            <form method="post" action="<?= url('groups/' . $group['slug'] . '/dues') ?>" data-submit-once>
                <?= csrf_field() ?>
                <div class="modal-head">
                    <h2 id="dues-modal-title">New dues cycle</h2>
                    <button type="button" class="icon-btn" data-modal-close aria-label="Close"><?= icon('x', 18) ?></button>
                </div>
                <div class="modal-body">
                    <div class="stack-sm">
                        <div class="field">
                            <label for="dues-title">Title</label>
                            <input type="text" id="dues-title" name="title" required placeholder="e.g. 2027 Annual Dues" value="<?= e(old('title')) ?>">
                        </div>
                        <div class="form-grid">
                            <div class="field">
                                <label for="amount">Amount per member (<?= e($group['currency']) ?>)</label>
                                <input type="number" id="amount" name="amount" min="1" step="0.01" required placeholder="25000" value="<?= e(old('amount')) ?>">
                            </div>
                            <div class="field">
                                <label for="due_date">Due date</label>
                                <input type="date" id="due_date" name="due_date" required value="<?= e(old('due_date')) ?>">
                            </div>
                        </div>
                        <div class="field">
                            <label for="dues-description">Description <span class="hint">(optional)</span></label>
                            <textarea id="dues-description" name="description" class="textarea" style="min-height:80px" placeholder="What does this levy cover?"><?= e(old('description')) ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-foot">
                    <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
                    <button type="submit" class="btn btn-primary">Create cycle</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
