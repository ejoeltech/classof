<?php
$title = 'Election results';
require BASE_PATH . '/app/Views/partials/group-header.php';
$published = $election['status'] === 'published';
?>
<div class="page-head">
    <div>
        <a href="<?= url('groups/' . $group['slug'] . '/elections/' . $election['id']) ?>" class="tiny muted" style="display:inline-flex;align-items:center;gap:4px;margin-bottom:6px"><?= icon('chevron-right', 13) ?> Back to election</a>
        <h1>Results</h1>
        <p><?= e($election['title']) ?></p>
    </div>
    <span class="badge badge-<?= $published ? 'info' : 'warn' ?>"><?= $published ? 'Published' : 'Preview' ?></span>
</div>

<?php if (!$published): ?>
    <div class="callout mb-3">
        <?= icon('info', 17) ?>
        <div>This is an internal preview. Results have not been published to members yet. Publish them from the manage page once you are satisfied.</div>
    </div>
<?php endif; ?>

<section class="card mb-3">
    <div class="card-body">
        <div class="between" style="gap:24px;flex-wrap:wrap">
            <div class="turnout" style="flex:1;min-width:260px">
                <div>
                    <div class="turnout-value"><?= (int) round($turnout['percent']) ?>%</div>
                    <div class="turnout-label">Turnout</div>
                </div>
                <div style="flex:1">
                    <div class="between tiny muted" style="margin-bottom:5px">
                        <span><?= (int) $turnout['voters'] ?> members voted</span>
                        <span><?= (int) $turnout['eligible'] ?> eligible</span>
                    </div>
                    <div class="progress"><span style="width:<?= (int) $turnout['percent'] ?>%"></span></div>
                </div>
            </div>
            <div>
                <p class="tiny muted mb-0">Voting period</p>
                <p class="small mb-0"><?= e(short_date($election['starts_at'])) ?> – <?= e(short_date($election['ends_at'])) ?></p>
            </div>
        </div>
    </div>
</section>

<?php if ($results === []): ?>
    <div class="card"><div class="empty-state">
        <span class="empty-icon"><?= icon('vote', 22) ?></span>
        <h3>Nothing to report yet</h3>
        <p>No positions or votes have been recorded for this election.</p>
    </div></div>
<?php else: ?>
    <div class="stack">
        <?php foreach ($results as $result): ?>
            <?php
            $position = $result['position'];
            $candidates = $result['candidates'];
            $seats = max(1, (int) $position['seats']);
            $winnerCount = (int) ($result['winner_count'] ?? count(array_filter($candidates, static fn (array $c): bool => $c['is_winner'])));
            ?>
            <section class="card">
                <div class="card-header">
                    <div>
                        <h2><?= e($position['title']) ?></h2>
                        <p class="sub"><?= (int) $result['total_votes'] ?> total <?= (int) $result['total_votes'] === 1 ? 'vote' : 'votes' ?> · <?= $seats ?> <?= $seats === 1 ? 'seat' : 'seats' ?></p>
                    </div>
                    <?php if ($winnerCount > $seats): ?>
                        <span class="badge badge-warn">Tie — run-off advised</span>
                    <?php elseif ($winnerCount > 0 && $seats === 1): ?>
                        <span class="badge badge-gold"><?= icon('crown', 12) ?> Winner declared</span>
                    <?php elseif ($winnerCount > 0): ?>
                        <span class="badge badge-gold"><?= $winnerCount ?> of <?= $seats ?> seats filled</span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if ($candidates === []): ?>
                        <p class="muted small">No candidates contested this position.</p>
                    <?php else: ?>
                        <?php foreach ($candidates as $candidate): ?>
                            <div class="result-row <?= $candidate['is_winner'] ? 'is-winner' : '' ?>">
                                <div class="result-top">
                                    <div class="result-name">
                                        <?= avatar($candidate['name'], $candidate['avatar'] ?? null, 32) ?>
                                        <span><?= e($candidate['name']) ?></span>
                                        <?php if ($candidate['is_winner']): ?>
                                            <span class="badge badge-gold"><?= icon('crown', 11) ?> Winner</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="result-count"><?= (int) $candidate['vote_count'] ?> votes · <?= (int) round($candidate['percent']) ?>%</div>
                                </div>
                                <div class="progress <?= $candidate['is_winner'] ? 'gold' : '' ?>"><span style="width:<?= (int) $candidate['percent'] ?>%"></span></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>

    <div class="card mt-3">
        <div class="card-body">
            <div class="callout info">
                <?= icon('shield', 17) ?>
                <div>
                    Results are calculated directly from recorded ballots. Each member could vote once per position, and individual ballots are never exposed — only totals.
                    <?php if ($published): ?> These results are final and visible to all active members.<?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
