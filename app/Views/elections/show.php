<?php
$title = $election['title'];
require BASE_PATH . '/app/Views/partials/group-header.php';
$totalPositions = count($positions);
$votedCount = count($votedPositions);
$allVoted = $totalPositions > 0 && $votedCount >= $totalPositions;
$tone = match ($election['status']) {
    'open' => 'success',
    'closed' => 'warn',
    'published' => 'info',
    default => 'neutral',
};
$starts = strtotime((string) $election['starts_at']);
$ends = strtotime((string) $election['ends_at']);
?>
<div class="page-head">
    <div>
        <a href="<?= url('groups/' . $group['slug'] . '/elections') ?>" class="tiny muted" style="display:inline-flex;align-items:center;gap:4px;margin-bottom:6px"><?= icon('chevron-right', 13) ?> All elections</a>
        <h1><?= e($election['title']) ?></h1>
        <p>
            <span class="badge badge-<?= $tone ?>"><?= e(App\Models\Election::STATUSES[$election['status']] ?? $election['status']) ?></span>
            <?php if ($live): ?><span class="badge badge-success badge-dot">Live now</span><?php endif; ?>
        </p>
    </div>
    <?php if ($isManager): ?>
        <div class="page-head-actions">
            <a href="<?= url('groups/' . $group['slug'] . '/elections/' . $election['id'] . '/results') ?>" class="btn btn-outline btn-sm">Results</a>
            <a href="<?= url('groups/' . $group['slug'] . '/elections/' . $election['id'] . '/manage') ?>" class="btn btn-primary btn-sm"><?= icon('settings', 15) ?> Manage</a>
        </div>
    <?php endif; ?>
</div>

<div class="split-main">
    <div class="stack">
        <?php if (!empty($election['description'])): ?>
            <div class="callout info">
                <?= icon('info', 17) ?>
                <div><?= e($election['description']) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($election['status'] === 'draft'): ?>
            <div class="card"><div class="empty-state" style="padding:32px">
                <span class="empty-icon"><?= icon('edit', 20) ?></span>
                <h3>This election is still a draft</h3>
                <p>Positions and candidates are being set up. Voting will open once the exco publishes it.</p>
                <?php if ($isManager): ?>
                    <a href="<?= url('groups/' . $group['slug'] . '/elections/' . $election['id'] . '/manage') ?>" class="btn btn-primary btn-sm mt-2">Set up election</a>
                <?php endif; ?>
            </div></div>
        <?php elseif ($election['status'] === 'open' && !$live): ?>
            <div class="callout <?= time() < $starts ? 'info' : '' ?>">
                <?= icon('clock', 17) ?>
                <div>
                    <?php if (time() < $starts): ?>
                        Voting opens on <strong><?= e(date_time($election['starts_at'])) ?></strong>. You can review the candidates below in the meantime.
                    <?php else: ?>
                        The voting window closed on <strong><?= e(date_time($election['ends_at'])) ?></strong>. Results will be published by the exco.
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($live && $allVoted): ?>
            <div class="card">
                <div class="card-body" style="text-align:center;padding:34px 24px">
                    <span class="empty-icon" style="margin:0 auto 12px;background:var(--gold-soft);color:var(--gold-700)"><?= icon('check', 22) ?></span>
                    <h2 style="font-size:18px;margin-bottom:6px">Your ballot is complete</h2>
                    <p class="muted small">You have voted for <?= $votedCount ?> of <?= $totalPositions ?> positions. Your choices are secret and cannot be changed.</p>
                    <?php if ($election['status'] === 'published'): ?>
                        <a href="<?= url('groups/' . $group['slug'] . '/elections/' . $election['id'] . '/results') ?>" class="btn btn-outline btn-sm mt-2">View published results</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($live && !$allVoted): ?>
            <form method="post" action="<?= url('groups/' . $group['slug'] . '/elections/' . $election['id'] . '/vote') ?>" data-submit-once>
                <?= csrf_field() ?>

                <?php foreach ($positions as $position): ?>
                    <?php $positionId = (int) $position['id']; ?>
                    <div class="ballot-position">
                        <div class="ballot-head">
                            <div>
                                <h3><?= e($position['title']) ?></h3>
                                <?php if (!empty($position['description'])): ?>
                                    <p class="muted tiny" style="margin:2px 0 0"><?= e($position['description']) ?></p>
                                <?php endif; ?>
                            </div>
                            <?php if ($position['i_voted']): ?>
                                <span class="badge badge-success"><?= icon('check', 12) ?> Vote recorded</span>
                            <?php else: ?>
                                <span class="badge badge-neutral"><?= count($position['candidates']) ?> <?= count($position['candidates']) === 1 ? 'candidate' : 'candidates' ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="ballot-body">
                            <?php if ($position['candidates'] === []): ?>
                                <p class="muted small">No candidates have been approved for this position.</p>
                            <?php else: ?>
                                <?php foreach ($position['candidates'] as $candidate): ?>
                                    <label class="choice">
                                        <input type="radio"
                                               name="candidate_<?= $positionId ?>"
                                               value="<?= (int) $candidate['id'] ?>"
                                               <?= $position['i_voted'] ? 'disabled' : '' ?>>
                                        <?= avatar($candidate['name'], $candidate['avatar'] ?? null, 40) ?>
                                        <span class="choice-main">
                                            <span class="choice-name">
                                                <?= e($candidate['name']) ?>
                                                <?php if ((int) $candidate['user_id'] === (int) user()['id']): ?>
                                                    <span class="badge badge-gold">You</span>
                                                <?php endif; ?>
                                            </span>
                                            <?php if (!empty($candidate['occupation'])): ?>
                                                <span class="choice-role"><?= e($candidate['occupation']) ?><?= !empty($candidate['location']) ? ' · ' . e($candidate['location']) : '' ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($candidate['manifesto'])): ?>
                                                <span class="choice-manifesto"><?= e($candidate['manifesto']) ?></span>
                                            <?php endif; ?>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                                <?php if ($position['i_voted']): ?>
                                    <p class="choice-voted"><?= icon('lock', 13) ?> Your vote for this position is locked and secret.</p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if ($positions !== []): ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="between">
                                <p class="muted small mb-0">Select one candidate per position, then submit. You can abstain from a position by leaving it blank.</p>
                                <button type="submit" class="btn btn-primary btn-lg">Submit ballot</button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </form>
        <?php else: ?>
            <?php if ((!$live && $election['status'] !== 'open') || ($live && $allVoted)): ?>
                <div class="stack">
                    <?php foreach ($positions as $position): ?>
                        <div class="card">
                            <div class="card-header">
                                <h2><?= e($position['title']) ?></h2>
                                <span class="badge badge-neutral"><?= count($position['candidates']) ?> candidates</span>
                            </div>
                            <div class="card-body">
                                <?php if ($position['candidates'] === []): ?>
                                    <p class="muted small">No candidates.</p>
                                <?php else: ?>
                                    <div class="stack-sm">
                                        <?php foreach ($position['candidates'] as $candidate): ?>
                                            <div class="choice" style="cursor:default">
                                                <?= avatar($candidate['name'], $candidate['avatar'] ?? null, 36) ?>
                                                <div class="choice-main">
                                                    <div class="choice-name"><?= e($candidate['name']) ?></div>
                                                    <?php if (!empty($candidate['manifesto'])): ?>
                                                        <div class="choice-manifesto"><?= e(str_limit($candidate['manifesto'], 160)) ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($results !== []): ?>
            <section class="card">
                <div class="card-header">
                    <h2><?= $election['status'] === 'published' ? 'Published results' : 'Results preview' ?></h2>
                    <a href="<?= url('groups/' . $group['slug'] . '/elections/' . $election['id'] . '/results') ?>" class="card-link">Full results <?= icon('arrow-right', 13) ?></a>
                </div>
                <div class="card-body">
                    <div class="stack">
                        <?php foreach ($results as $result): ?>
                            <div>
                                <div class="between mb-1">
                                    <strong style="font-size:13.5px"><?= e($result['position']['title']) ?></strong>
                                    <span class="tiny muted"><?= (int) $result['total_votes'] ?> votes</span>
                                </div>
                                <?php foreach ($result['candidates'] as $candidate): ?>
                                    <?php if (!$candidate['is_winner']) { continue; } ?>
                                    <div class="flex" style="gap:8px;padding:6px 0">
                                        <?= icon('crown', 15) ?>
                                        <span style="font-weight:600;font-size:13.5px"><?= e($candidate['name']) ?></span>
                                        <span class="badge badge-gold"><?= (int) $candidate['vote_count'] ?> votes</span>
                                    </div>
                                <?php endforeach; ?>
                                <?php if ($result['total_votes'] === 0): ?>
                                    <p class="muted tiny">No votes cast.</p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </div>

    <div class="stack">
        <section class="card">
            <div class="card-header"><h2>Voting details</h2></div>
            <div class="card-body">
                <dl class="kv">
                    <dt>Opens</dt><dd><?= e(date_time($election['starts_at'])) ?></dd>
                    <dt>Closes</dt><dd><?= e(date_time($election['ends_at'])) ?></dd>
                    <dt>Positions</dt><dd><?= $totalPositions ?></dd>
                    <dt>Your votes</dt><dd><?= $votedCount ?> of <?= $totalPositions ?></dd>
                    <dt>Status</dt><dd><span class="badge badge-<?= $tone ?>"><?= e(App\Models\Election::STATUSES[$election['status']] ?? $election['status']) ?></span></dd>
                </dl>
                <div class="divider"></div>
                <div class="between" style="font-size:12.5px;color:var(--muted);margin-bottom:5px">
                    <span>Turnout</span>
                    <span><?= (int) $turnout['voters'] ?> / <?= (int) $turnout['eligible'] ?></span>
                </div>
                <div class="progress"><span style="width:<?= (int) $turnout['percent'] ?>%"></span></div>
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2>How voting works</h2></div>
            <div class="card-body">
                <ul class="stack-sm" style="list-style:none;padding:0;font-size:13px;color:var(--text)">
                    <li class="flex" style="align-items:flex-start"><?= icon('lock', 16) ?><span>Your ballot is secret. No member can see who you voted for.</span></li>
                    <li class="flex" style="align-items:flex-start"><?= icon('check', 16) ?><span>One vote per position. Once submitted, a position is locked.</span></li>
                    <li class="flex" style="align-items:flex-start"><?= icon('users', 16) ?><span>Only active members of this group are eligible to vote.</span></li>
                    <li class="flex" style="align-items:flex-start"><?= icon('clock', 16) ?><span>Voting closes automatically at <?= e(date('M j, g:i A', $ends)) ?>.</span></li>
                </ul>
            </div>
        </section>
    </div>
</div>
