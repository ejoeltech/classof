<?php
$title = 'Elections';
require BASE_PATH . '/app/Views/partials/group-header.php';
$tone = static fn (string $status): string => match ($status) {
    'open' => 'success',
    'closed' => 'warn',
    'published' => 'info',
    default => 'neutral',
};
?>
<div class="page-head">
    <div>
        <h1>Elections</h1>
        <p>Transparent executive committee elections, one member one vote.</p>
    </div>
    <?php if ($isManager): ?>
        <button type="button" class="btn btn-primary" data-modal-open="election-modal"><?= icon('plus', 16) ?> New election</button>
    <?php endif; ?>
</div>

<?php if ($elections === []): ?>
    <div class="card">
        <div class="empty-state">
            <span class="empty-icon"><?= icon('vote', 22) ?></span>
            <h3>No elections yet</h3>
            <p><?= $isManager ? 'Create an election, add positions and candidates, then open voting.' : 'When the exco calls an election, it will appear here.' ?></p>
            <?php if ($isManager): ?>
                <button type="button" class="btn btn-primary btn-sm mt-2" data-modal-open="election-modal">Create an election</button>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="stack">
        <?php foreach ($elections as $election): ?>
            <?php
            $live = App\Models\Election::isLive($election);
            $totalPositions = (int) $election['position_count'];
            $myVotes = (int) $election['my_votes'];
            ?>
            <article class="card">
                <div class="card-body">
                    <div class="between" style="align-items:flex-start;flex-wrap:wrap;gap:14px">
                        <div style="max-width:640px">
                            <div class="flex" style="gap:8px;flex-wrap:wrap;margin-bottom:4px">
                                <h2 style="font-size:17px"><a href="<?= url('groups/' . $group['slug'] . '/elections/' . $election['id']) ?>" style="color:inherit"><?= e($election['title']) ?></a></h2>
                                <span class="badge badge-<?= $tone($election['status']) ?>"><?= e(App\Models\Election::STATUSES[$election['status']] ?? $election['status']) ?></span>
                                <?php if ($live): ?><span class="badge badge-success badge-dot">Live now</span><?php endif; ?>
                            </div>
                            <?php if (!empty($election['description'])): ?>
                                <p class="muted small"><?= e(str_limit($election['description'], 160)) ?></p>
                            <?php endif; ?>
                            <div class="meta-row">
                                <span><?= icon('clock', 15) ?> Opens <?= e(date_time($election['starts_at'])) ?></span>
                                <span class="dot-sep">·</span>
                                <span>Closes <?= e(date_time($election['ends_at'])) ?></span>
                            </div>
                            <div class="pill-row mt-2">
                                <span class="badge badge-neutral"><?= $totalPositions ?> <?= $totalPositions === 1 ? 'position' : 'positions' ?></span>
                                <span class="badge badge-neutral"><?= (int) $election['vote_count'] ?> votes cast</span>
                                <span class="badge badge-neutral"><?= (int) round($election['turnout']['percent']) ?>% turnout</span>
                                <?php if ($myVotes > 0): ?>
                                    <span class="badge badge-success"><?= icon('check', 12) ?> You voted <?= $myVotes ?>/<?= $totalPositions ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="stack-sm" style="min-width:170px">
                            <?php if ($live && $myVotes < $totalPositions): ?>
                                <a href="<?= url('groups/' . $group['slug'] . '/elections/' . $election['id']) ?>" class="btn btn-primary btn-sm">Cast your vote <?= icon('arrow-right', 14) ?></a>
                            <?php elseif ($election['status'] === 'published'): ?>
                                <a href="<?= url('groups/' . $group['slug'] . '/elections/' . $election['id'] . '/results') ?>" class="btn btn-outline btn-sm">View results</a>
                            <?php else: ?>
                                <a href="<?= url('groups/' . $group['slug'] . '/elections/' . $election['id']) ?>" class="btn btn-outline btn-sm">View election</a>
                            <?php endif; ?>
                            <?php if ($isManager): ?>
                                <a href="<?= url('groups/' . $group['slug'] . '/elections/' . $election['id'] . '/manage') ?>" class="btn btn-ghost btn-sm">Manage</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mt-2">
                        <div class="between tiny muted" style="margin-bottom:5px">
                            <span>Turnout</span>
                            <span><?= (int) $election['turnout']['voters'] ?> of <?= (int) $election['turnout']['eligible'] ?> members voted</span>
                        </div>
                        <div class="progress"><span style="width:<?= (int) $election['turnout']['percent'] ?>%"></span></div>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($isManager): ?>
    <div class="modal" id="election-modal" role="dialog" aria-modal="true" aria-labelledby="election-modal-title">
        <div class="modal-backdrop" data-modal-close></div>
        <div class="modal-panel">
            <form method="post" action="<?= url('groups/' . $group['slug'] . '/elections') ?>" data-submit-once>
                <?= csrf_field() ?>
                <div class="modal-head">
                    <h2 id="election-modal-title">New election</h2>
                    <button type="button" class="icon-btn" data-modal-close aria-label="Close"><?= icon('x', 18) ?></button>
                </div>
                <div class="modal-body">
                    <div class="stack-sm">
                        <div class="field">
                            <label for="election-title">Title</label>
                            <input type="text" id="election-title" name="title" required placeholder="e.g. 2026 Executive Committee Election" value="<?= e(old('title')) ?>">
                        </div>
                        <div class="field">
                            <label for="description">Description <span class="hint">(optional)</span></label>
                            <textarea id="description" name="description" class="textarea" style="min-height:80px" placeholder="What is this election for?"><?= e(old('description')) ?></textarea>
                        </div>
                        <div class="form-grid">
                            <div class="field">
                                <label for="starts_at">Voting opens</label>
                                <input type="datetime-local" id="starts_at" name="starts_at" required value="<?= e(old('starts_at')) ?>">
                            </div>
                            <div class="field">
                                <label for="ends_at">Voting closes</label>
                                <input type="datetime-local" id="ends_at" name="ends_at" required value="<?= e(old('ends_at')) ?>">
                            </div>
                        </div>
                        <div class="callout info">
                            <?= icon('info', 17) ?>
                            <div>The election is created as a draft. Add positions and candidates, then open voting.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-foot">
                    <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
                    <button type="submit" class="btn btn-primary">Create election</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
