<?php
$title = $group['name'];
require BASE_PATH . '/app/Views/partials/group-header.php';

$openWelfare = $welfare ?? [];
$openElections = array_values(array_filter($elections ?? [], static fn (array $e): bool => $e['status'] === 'open'));
$canPost = in_array($membership['role'], ['owner', 'admin', 'exco'], true);
?>
<?php if ($isManager && (int) $stats['pending'] > 0): ?>
    <div class="alert alert-warn">
        <span class="alert-icon"><?= icon('users', 18) ?></span>
        <div class="alert-body">
            <strong><?= (int) $stats['pending'] ?> membership <?= (int) $stats['pending'] === 1 ? 'request is' : 'requests are' ?> waiting</strong> for your approval.
        </div>
        <a href="<?= url('groups/' . $group['slug'] . '/members?status=pending') ?>" class="btn btn-sm btn-outline">Review</a>
    </div>
<?php endif; ?>

<div class="stat-grid mb-3">
    <div class="stat-card">
        <span class="stat-icon"><?= icon('users', 20) ?></span>
        <div class="stat-meta">
            <span class="stat-label">Members</span>
            <span class="stat-value"><?= (int) $stats['members'] ?></span>
            <span class="stat-hint"><?= $isManager ? (int) $stats['pending'] . ' pending' : 'Active classmates' ?></span>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon info"><?= icon('calendar', 20) ?></span>
        <div class="stat-meta">
            <span class="stat-label">Upcoming events</span>
            <span class="stat-value"><?= (int) $stats['events'] ?></span>
            <span class="stat-hint"><a href="<?= url('groups/' . $group['slug'] . '/events') ?>">View calendar</a></span>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon gold"><?= icon('heart', 20) ?></span>
        <div class="stat-meta">
            <span class="stat-label">Open welfare</span>
            <span class="stat-value"><?= count($openWelfare) ?></span>
            <span class="stat-hint"><?= count($openWelfare) === 1 ? 'Case accepting support' : 'Cases accepting support' ?></span>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon warn"><?= icon('wallet', 20) ?></span>
        <div class="stat-meta">
            <?php if ($isManager): ?>
                <span class="stat-label">Treasury collected</span>
                <span class="stat-value"><?= money((float) ($treasury['total'] ?? 0), $group['currency']) ?></span>
                <span class="stat-hint"><a href="<?= url('groups/' . $group['slug'] . '/treasury') ?>">Open treasury</a></span>
            <?php else: ?>
                <span class="stat-label">My dues</span>
                <span class="stat-value" style="font-size:18px"><?= $myDues !== null ? 'Paid' : ($openDue !== null ? money($openDue['amount'], $group['currency']) : 'Clear') ?></span>
                <span class="stat-hint"><?= $openDue !== null ? e($openDue['title']) : 'No open cycle' ?></span>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="split-main">
    <div class="stack">
        <section class="card">
            <div class="card-header">
                <div>
                    <h2>Announcements</h2>
                    <p class="sub">News from the exco</p>
                </div>
                <div class="flex">
                    <?php if ($canPost): ?>
                        <a href="<?= url('groups/' . $group['slug'] . '/announcements') ?>" class="btn btn-ghost btn-sm"><?= icon('plus', 15) ?> Post</a>
                    <?php endif; ?>
                    <a href="<?= url('groups/' . $group['slug'] . '/announcements') ?>" class="card-link">All <?= icon('arrow-right', 13) ?></a>
                </div>
            </div>
            <div class="card-body">
                <?php if ($announcements === []): ?>
                    <div class="empty-state" style="padding:22px 10px">
                        <span class="empty-icon"><?= icon('megaphone', 20) ?></span>
                        <h3>No announcements yet</h3>
                        <p><?= $canPost ? 'Post the first update for your classmates.' : 'Group admins will post updates here.' ?></p>
                    </div>
                <?php else: ?>
                    <div class="list">
                        <?php foreach ($announcements as $announcement): ?>
                            <article class="list-item">
                                <div class="list-item-body">
                                    <div class="list-item-title">
                                        <?= e($announcement['title']) ?>
                                        <?php if ((int) $announcement['pinned'] === 1): ?>
                                            <span class="badge badge-gold">Pinned</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="list-item-text"><?= e(str_limit($announcement['body'], 180)) ?></div>
                                    <div class="list-item-meta mt-1">
                                        <?= avatar($announcement['author_name'], $announcement['avatar'], 22) ?>
                                        <span><?= e($announcement['author_name']) ?></span>
                                        <span class="dot-sep">·</span>
                                        <span><?= e(time_ago($announcement['created_at'])) ?></span>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <div>
                    <h2>Upcoming events</h2>
                    <p class="sub">Save your spot</p>
                </div>
                <a href="<?= url('groups/' . $group['slug'] . '/events') ?>" class="card-link">All events <?= icon('arrow-right', 13) ?></a>
            </div>
            <div class="card-body">
                <?php if ($events === []): ?>
                    <div class="empty-state" style="padding:22px 10px">
                        <span class="empty-icon"><?= icon('calendar', 20) ?></span>
                        <h3>No upcoming events</h3>
                        <p>Events created by the exco will appear here.</p>
                    </div>
                <?php else: ?>
                    <div class="list">
                        <?php foreach ($events as $event): ?>
                            <?php $ts = strtotime((string) $event['starts_at']); ?>
                            <div class="list-item">
                                <div class="date-chip">
                                    <span><?= date('M', $ts) ?></span>
                                    <strong><?= date('j', $ts) ?></strong>
                                </div>
                                <div class="list-item-body">
                                    <div class="list-item-title">
                                        <a href="<?= url('groups/' . $group['slug'] . '/events/' . $event['id']) ?>"><?= e($event['title']) ?></a>
                                    </div>
                                    <div class="list-item-meta">
                                        <span><?= date('g:i A', $ts) ?></span>
                                        <?php if (!empty($event['location'])): ?>
                                            <span class="dot-sep">·</span><span><?= e($event['location']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <span class="badge badge-neutral"><?= (int) $event['going_count'] ?> going</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <div>
                    <h2>Welfare</h2>
                    <p class="sub">Supporting our own</p>
                </div>
                <a href="<?= url('groups/' . $group['slug'] . '/welfare') ?>" class="card-link">All cases <?= icon('arrow-right', 13) ?></a>
            </div>
            <div class="card-body">
                <?php if ($openWelfare === []): ?>
                    <div class="callout">
                        <?= icon('heart', 17) ?>
                        <div>No open welfare cases right now. When a classmate needs support, it will be posted here.</div>
                    </div>
                <?php else: ?>
                    <div class="list">
                        <?php foreach (array_slice($openWelfare, 0, 3) as $case): ?>
                            <?php
                            $target = (float) ($case['target_amount'] ?? 0);
                            $raised = (float) $case['raised'];
                            $pct = $target > 0 ? percent($raised, $target) : 0;
                            ?>
                            <div class="list-item">
                                <span class="welfare-cat"><?= icon('heart', 17) ?></span>
                                <div class="list-item-body">
                                    <div class="list-item-title">
                                        <a href="<?= url('groups/' . $group['slug'] . '/welfare/' . $case['id']) ?>"><?= e($case['title']) ?></a>
                                    </div>
                                    <div class="list-item-meta"><?= e(((int) $case['is_anonymous'] === 1 && !$isManager) ? 'A classmate' : $case['beneficiary_name']) ?> · <?= e(App\Models\WelfareCase::categoryLabel($case['category'])) ?></div>
                                    <div class="mt-1">
                                        <div class="between" style="font-size:11.5px;color:var(--muted);margin-bottom:4px">
                                            <span><strong style="color:var(--forest-700)"><?= money($raised, $case['currency']) ?></strong> raised</span>
                                            <span><?= $target > 0 ? money($target, $case['currency']) : '' ?></span>
                                        </div>
                                        <?php if ($target > 0): ?><div class="progress thin"><span style="width:<?= (int) $pct ?>%"></span></div><?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <div class="stack">
        <section class="card">
            <div class="card-header">
                <h2>Dues</h2>
                <a href="<?= url('groups/' . $group['slug'] . '/dues') ?>" class="card-link">History <?= icon('arrow-right', 13) ?></a>
            </div>
            <div class="card-body">
                <?php if ($openDue === null): ?>
                    <p class="muted small">No open dues cycle. <?= $isManager ? 'Create one when the committee is ready.' : '' ?></p>
                    <?php if ($isManager): ?>
                        <a href="<?= url('groups/' . $group['slug'] . '/dues') ?>" class="btn btn-outline btn-sm mt-1"><?= icon('plus', 14) ?> New dues cycle</a>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="between" style="align-items:flex-start">
                        <div>
                            <p class="muted tiny" style="margin-bottom:2px"><?= e($openDue['title']) ?></p>
                            <div class="stat-value"><?= money($openDue['amount'], $openDue['currency']) ?></div>
                            <p class="muted tiny mb-0">Due <?= e(short_date($openDue['due_date'])) ?></p>
                        </div>
                        <?php if ($myDues !== null): ?>
                            <span class="badge badge-success"><?= icon('check', 13) ?> Paid</span>
                        <?php else: ?>
                            <span class="badge badge-warn">Unpaid</span>
                        <?php endif; ?>
                    </div>
                    <div class="mt-2">
                        <?php if ($myDues === null): ?>
                            <a href="<?= url('groups/' . $group['slug'] . '/dues/' . $openDue['id']) ?>" class="btn btn-primary btn-block">Pay dues</a>
                        <?php else: ?>
                            <a href="<?= url('groups/' . $group['slug'] . '/dues/' . $openDue['id']) ?>" class="btn btn-outline btn-block">View receipt</a>
                        <?php endif; ?>
                    </div>
                    <?php if ($isManager): ?>
                        <div class="between mt-2" style="font-size:12px;color:var(--muted)">
                            <span><?= (int) $openDue['paid_members'] ?> of <?= (int) $openDue['active_members'] ?> paid</span>
                            <strong style="color:var(--forest-700)"><?= money($openDue['collected'], $openDue['currency']) ?></strong>
                        </div>
                        <div class="progress thin mt-1"><span style="width:<?= (int) percent($openDue['paid_members'], $openDue['active_members']) ?>%"></span></div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <h2>Elections</h2>
                <a href="<?= url('groups/' . $group['slug'] . '/elections') ?>" class="card-link">All <?= icon('arrow-right', 13) ?></a>
            </div>
            <div class="card-body">
                <?php if ($elections === []): ?>
                    <p class="muted small">No elections have been created yet.</p>
                    <?php if ($isManager): ?>
                        <a href="<?= url('groups/' . $group['slug'] . '/elections') ?>" class="btn btn-outline btn-sm mt-1"><?= icon('vote', 14) ?> Set up an election</a>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="list">
                        <?php foreach (array_slice($elections, 0, 3) as $election): ?>
                            <div class="list-item" style="align-items:center">
                                <div class="list-item-body">
                                    <div class="list-item-title">
                                        <a href="<?= url('groups/' . $group['slug'] . '/elections/' . $election['id']) ?>"><?= e($election['title']) ?></a>
                                    </div>
                                    <div class="list-item-meta">
                                        <?= (int) $election['position_count'] ?> positions · <?= (int) $election['vote_count'] ?> votes
                                    </div>
                                </div>
                                <?php
                                $tone = match ($election['status']) {
                                    'open' => 'success',
                                    'closed' => 'warn',
                                    'published' => 'info',
                                    default => 'neutral',
                                };
                                ?>
                                <span class="badge badge-<?= $tone ?>"><?= e(App\Models\Election::STATUSES[$election['status']] ?? $election['status']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <?php if (!empty($channels)): ?>
            <section class="card">
                <div class="card-header">
                    <h2>WhatsApp</h2>
                    <span class="badge badge-neutral"><?= count($channels) ?></span>
                </div>
                <div class="card-body">
                    <div class="list">
                        <?php foreach ($channels as $channel): ?>
                            <div class="list-item" style="align-items:center">
                                <span class="stat-icon" style="width:34px;height:34px;border-radius:9px;background:var(--forest-50);color:var(--forest-700)"><?= icon('link', 16) ?></span>
                                <div class="list-item-body">
                                    <div class="list-item-title" style="font-size:13.5px">
                                        <?= e($channel['label']) ?>
                                        <?php if ((int) $channel['is_primary'] === 1): ?><span class="badge badge-gold">Primary</span><?php endif; ?>
                                    </div>
                                    <div class="list-item-meta"><?= $channel['visible_to'] === 'manager' ? 'Managers only' : 'Members' ?> · verified <?= e(time_ago($channel['last_verified_at'])) ?></div>
                                </div>
                                <a href="<?= e($channel['invite_url']) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm">Join WhatsApp</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="callout mt-2">
                        <?= icon('info', 17) ?>
                        <div>Anyone with this link can join the WhatsApp group. It does not add them to Classof.</div>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <section class="card">
            <div class="card-header">
                <h2>Leadership</h2>
                <a href="<?= url('groups/' . $group['slug'] . '/members') ?>" class="card-link">Members <?= icon('arrow-right', 13) ?></a>
            </div>
            <div class="card-body">
                <div class="list">
                    <?php foreach (array_slice($exco, 0, 5) as $leader): ?>
                        <div class="list-item" style="align-items:center;padding:9px 0">
                            <?= avatar($leader['name'], $leader['avatar'] ?? null, 34) ?>
                            <div class="list-item-body">
                                <div class="list-item-title" style="font-size:13.5px"><?= e($leader['name']) ?></div>
                                <div class="list-item-meta"><?= e($leader['position_title'] ?: role_label($leader['role'])) ?></div>
                            </div>
                            <?php if ($leader['role'] === 'owner'): ?>
                                <?= icon('crown', 15) ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <?php if ($isManager && $recentActivity !== []): ?>
            <section class="card">
                <div class="card-header">
                    <h2>Recent activity</h2>
                    <span class="badge badge-neutral">Audit trail</span>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <?php foreach ($recentActivity as $activity): ?>
                            <div class="timeline-item">
                                <span class="timeline-dot"></span>
                                <div class="timeline-text"><?= e($activity['description']) ?></div>
                                <div class="timeline-time"><?= e($activity['user_name'] ?? 'System') ?> · <?= e(time_ago($activity['created_at'])) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </div>
</div>
