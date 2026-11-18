<?php
$title = 'Manage election';
require BASE_PATH . '/app/Views/partials/group-header.php';
$status = $election['status'];
$canEdit = in_array($status, ['draft', 'open'], true);
$showTally = in_array($status, ['closed', 'published'], true);
$tone = match ($status) {
    'open' => 'success',
    'closed' => 'warn',
    'published' => 'info',
    default => 'neutral',
};
?>
<div class="page-head">
    <div>
        <a href="<?= url('groups/' . $group['slug'] . '/elections') ?>" class="tiny muted" style="display:inline-flex;align-items:center;gap:4px;margin-bottom:6px"><?= icon('chevron-right', 13) ?> All elections</a>
        <h1>Manage election</h1>
        <p><?= e($election['title']) ?></p>
    </div>
    <div class="page-head-actions">
        <a href="<?= url('groups/' . $group['slug'] . '/elections/' . $election['id']) ?>" class="btn btn-outline btn-sm">View ballot</a>
        <a href="<?= url('groups/' . $group['slug'] . '/elections/' . $election['id'] . '/results') ?>" class="btn btn-ghost btn-sm">Results</a>
    </div>
</div>

<section class="card mb-3">
    <div class="card-body">
        <div class="between" style="flex-wrap:wrap;gap:16px">
            <div>
                <div class="flex" style="gap:8px;flex-wrap:wrap">
                    <span class="badge badge-<?= $tone ?>"><?= e(App\Models\Election::STATUSES[$status] ?? $status) ?></span>
                    <span class="badge badge-neutral"><?= (int) $turnout['voters'] ?> members voted</span>
                    <span class="badge badge-neutral"><?= count($positions) ?> positions</span>
                </div>
                <div class="meta-row mt-1">
                    <span><?= icon('clock', 15) ?> Opens <?= e(date_time($election['starts_at'])) ?></span>
                    <span class="dot-sep">·</span>
                    <span>Closes <?= e(date_time($election['ends_at'])) ?></span>
                </div>
            </div>

            <div class="flex flex-wrap">
                <?php if ($status === 'draft'): ?>
                    <form method="post" action="<?= url('groups/' . $group['slug'] . '/elections/' . $election['id'] . '/status') ?>" data-confirm="Open voting now? Members will be able to cast ballots immediately.">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="open">
                        <button type="submit" class="btn btn-primary"><?= icon('vote', 15) ?> Open voting</button>
                    </form>
                <?php elseif ($status === 'open'): ?>
                    <form method="post" action="<?= url('groups/' . $group['slug'] . '/elections/' . $election['id'] . '/status') ?>" data-confirm="Close voting? No member will be able to vote after this.">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="close">
                        <button type="submit" class="btn btn-outline"><?= icon('lock', 15) ?> Close voting</button>
                    </form>
                <?php elseif ($status === 'closed'): ?>
                    <form method="post" action="<?= url('groups/' . $group['slug'] . '/elections/' . $election['id'] . '/status') ?>" data-confirm="Publish these results to all members? This cannot be undone.">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="publish">
                        <button type="submit" class="btn btn-primary"><?= icon('megaphone', 15) ?> Publish results</button>
                    </form>
                <?php else: ?>
                    <span class="badge badge-info">Results published</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php if (!$canEdit): ?>
    <div class="callout mb-3">
        <?= icon('lock', 17) ?>
        <div>Positions and candidates cannot be changed because voting has closed. You can still review the tally below.</div>
    </div>
<?php endif; ?>

<div class="stack">
    <?php if ($positions === []): ?>
        <div class="card"><div class="empty-state">
            <span class="empty-icon"><?= icon('vote', 22) ?></span>
            <h3>No positions yet</h3>
            <p>Add the positions you are electing — President, Secretary, Treasurer, and so on.</p>
        </div></div>
    <?php endif; ?>

    <?php foreach ($positions as $position): ?>
        <?php
        $existingUserIds = array_map(static fn (array $c): int => (int) $c['user_id'], $position['candidates']);
        $available = array_values(array_filter($members, static fn (array $m): bool => !in_array((int) $m['user_id'], $existingUserIds, true)));
        ?>
        <section class="card">
            <div class="card-header">
                <div>
                    <h2><?= e($position['title']) ?></h2>
                    <p class="sub"><?= (int) $position['seats'] ?> <?= (int) $position['seats'] === 1 ? 'seat' : 'seats' ?> · <?= count($position['candidates']) ?> candidates</p>
                </div>
                <?php if ($canEdit): ?>
                    <form method="post" action="<?= url('groups/' . $group['slug'] . '/elections/' . $election['id'] . '/positions/' . $position['id'] . '/delete') ?>" data-confirm="Remove this position and all its candidates?">
                        <?= csrf_field() ?>
                        <button type="submit" class="icon-btn" title="Delete position" aria-label="Delete position"><?= icon('trash', 16) ?></button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="card-body">
                <?php if ($position['candidates'] === []): ?>
                    <p class="muted small">No candidates yet for this position.</p>
                <?php else: ?>
                    <div class="stack-sm">
                        <?php foreach ($position['candidates'] as $candidate): ?>
                            <div class="ledger-row">
                                <?= avatar($candidate['name'], $candidate['avatar'] ?? null, 36) ?>
                                <div style="flex:1;min-width:0">
                                    <div style="font-weight:600;font-size:13.5px;color:var(--ink)">
                                        <?= e($candidate['name']) ?>
                                        <?php if ($candidate['status'] !== 'approved'): ?>
                                            <span class="badge badge-warn"><?= e(status_label($candidate['status'])) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ledger-note"><?= e(str_limit($candidate['manifesto'] ?: 'No manifesto provided.', 150)) ?></div>
                                </div>
                                <?php if ($showTally): ?>
                                    <span class="badge badge-neutral"><?= (int) $candidate['vote_count'] ?> votes</span>
                                <?php endif; ?>
                                <?php if ($canEdit): ?>
                                    <form method="post" action="<?= url('groups/' . $group['slug'] . '/elections/' . $election['id'] . '/candidates/' . $candidate['id'] . '/delete') ?>" data-confirm="Remove <?= e($candidate['name']) ?> as a candidate?">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="icon-btn" title="Remove candidate" aria-label="Remove candidate"><?= icon('x', 15) ?></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($canEdit && $available !== []): ?>
                    <div class="divider"></div>
                    <form method="post" action="<?= url('groups/' . $group['slug'] . '/elections/' . $election['id'] . '/positions/' . $position['id'] . '/candidates') ?>" class="form-grid" data-submit-once>
                        <?= csrf_field() ?>
                        <div class="field">
                            <label>Add a candidate</label>
                            <select name="user_id" class="select" required>
                                <option value="">Choose a member…</option>
                                <?php foreach ($available as $member): ?>
                                    <option value="<?= (int) $member['user_id'] ?>"><?= e($member['name']) ?><?= !empty($member['occupation']) ? ' — ' . e($member['occupation']) : '' ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label>Manifesto <span class="hint">(optional)</span></label>
                            <input type="text" name="manifesto" maxlength="3000" placeholder="One-line pitch…">
                        </div>
                        <div class="span-2 form-actions">
                            <button type="submit" class="btn btn-outline btn-sm"><?= icon('plus', 14) ?> Add candidate</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </section>
    <?php endforeach; ?>

    <?php if ($canEdit): ?>
        <section class="card">
            <div class="card-header"><h2>Add a position</h2></div>
            <div class="card-body">
                <form method="post" action="<?= url('groups/' . $group['slug'] . '/elections/' . $election['id'] . '/positions') ?>" class="form-grid" data-submit-once>
                    <?= csrf_field() ?>
                    <div class="field">
                        <label for="position-title">Position title</label>
                        <input type="text" id="position-title" name="title" required placeholder="e.g. Financial Secretary">
                    </div>
                    <div class="field">
                        <label for="seats">Seats</label>
                        <input type="number" id="seats" name="seats" min="1" max="10" value="1">
                    </div>
                    <div class="field span-2">
                        <label for="position-description">Description <span class="hint">(optional)</span></label>
                        <input type="text" id="position-description" name="description" maxlength="255" placeholder="What does this role do?">
                    </div>
                    <div class="span-2 form-actions">
                        <button type="submit" class="btn btn-primary btn-sm"><?= icon('plus', 15) ?> Add position</button>
                    </div>
                </form>
            </div>
        </section>
    <?php endif; ?>
</div>
