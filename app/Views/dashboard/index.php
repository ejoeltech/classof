<?php
$title = 'Dashboard';
$firstName = explode(' ', (string) (user()['name'] ?? 'there'))[0];
$hour = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$me = user();
?>
<div class="page-head">
    <div>
        <h1><?= e($greeting) ?>, <?= e($firstName) ?>.</h1>
        <p>Here is what is happening across your alumni groups.</p>
    </div>
    <div class="page-head-actions">
        <a href="<?= url('explore?for=you') ?>" class="btn btn-primary"><?= icon('search', 16) ?> Find my groups</a>
        <?php if (($educationCount ?? 0) === 0): ?>
            <a href="<?= url('profile/education') ?>" class="btn btn-outline">Add education</a>
        <?php endif; ?>
    </div>
</div>

<?php if (($newMatchCount ?? 0) > 0): ?>
    <div class="alert alert-info">
        <span class="alert-icon"><?= icon('star', 18) ?></span>
        <div class="alert-body">
            <strong><?= (int) $newMatchCount ?> new <?= (int) $newMatchCount === 1 ? 'group matches' : 'groups match' ?> your education</strong>
            since your last visit:
            <?= e(implode(', ', array_map(static fn (array $g): string => $g['name'], $newMatches))) ?>
            <?= (int) $newMatchCount > count($newMatches) ? ' and more' : '' ?>.
        </div>
        <a href="<?= url('explore?for=you') ?>" class="btn btn-sm btn-outline">See matches</a>
    </div>
<?php endif; ?>

<?php if (!empty($siteEditorGroups)): ?>
    <div class="card mb-3">
        <div class="card-header">
            <div>
                <h2>Websites you manage</h2>
                <p class="sub">You are the assigned editor for these group pages.</p>
            </div>
            <span class="badge badge-neutral"><?= count($siteEditorGroups) ?></span>
        </div>
        <div class="card-body">
            <div class="list">
                <?php foreach ($siteEditorGroups as $editorSite): ?>
                    <div class="list-item" style="align-items:center">
                        <span class="stat-icon" style="width:36px;height:36px;border-radius:9px"><?= icon('link', 16) ?></span>
                        <div class="list-item-body">
                            <div class="list-item-title" style="font-size:13.5px"><?= e($editorSite['group_name']) ?></div>
                            <div class="list-item-meta"><?= e($editorSite['subdomain']) ?> · <?= e(ucfirst($editorSite['status'])) ?></div>
                        </div>
                        <div class="flex" style="gap:6px">
                            <a href="<?= e(group_site_url((string) $editorSite['subdomain'])) ?>" class="btn btn-ghost btn-sm" target="_blank" rel="noopener">View</a>
                            <a href="<?= url('groups/' . $editorSite['group_slug'] . '/site') ?>" class="btn btn-outline btn-sm">Edit</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (($educationCount ?? 0) === 0): ?>
    <div class="card mb-3">
        <div class="card-body between" style="flex-wrap:wrap;gap:16px">
            <div class="flex" style="align-items:flex-start">
                <span class="stat-icon"><?= icon('grid', 20) ?></span>
                <div>
                    <strong style="display:block;font-size:15px">Add your education to see matching groups</strong>
                    <span class="muted small">Institution, programme and graduation year let Classof suggest your institution, class and association groups.</span>
                </div>
            </div>
            <a href="<?= url('profile/education') ?>" class="btn btn-primary btn-sm">Add education</a>
        </div>
    </div>
<?php elseif (($suggestions ?? []) !== []): ?>
    <section class="card mb-3">
        <div class="card-header">
            <div><h2>Groups that match your education</h2><p class="sub"><?= (int) $matchCount ?> matched <?= (int) $matchCount === 1 ? 'group' : 'groups' ?> not yet joined</p></div>
            <a href="<?= url('explore?for=you') ?>" class="card-link">See all <?= icon('arrow-right', 13) ?></a>
        </div>
        <div class="card-body">
            <div class="list">
                <?php foreach ($suggestions as $suggestion): ?>
                    <div class="list-item" style="align-items:center">
                        <span class="group-dot" style="--dot: <?= e($suggestion['cover_color'] ?: '#0b6e4f') ?>;width:36px;height:36px;border-radius:10px;font-size:14px"><?= e(mb_substr((string) $suggestion['name'], 0, 1)) ?></span>
                        <div class="list-item-body">
                            <div class="list-item-title">
                                <a href="<?= url('join/' . $suggestion['slug']) ?>"><?= e($suggestion['name']) ?></a>
                            </div>
                            <div class="list-item-meta">
                                <span class="badge badge-success">Matched</span>
                                <span><?= e($suggestion['match_reason']) ?></span>
                                <span class="dot-sep">·</span>
                                <span><?= (int) $suggestion['member_count'] ?> members</span>
                            </div>
                        </div>
                        <a href="<?= url('join/' . $suggestion['slug']) ?>" class="btn btn-outline btn-sm">Request to join</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if ($pendingGroups !== []): ?>
    <div class="alert alert-warn">
        <span class="alert-icon"><?= icon('clock', 18) ?></span>
        <div class="alert-body">
            Your request to join
            <?php foreach ($pendingGroups as $index => $pending): ?>
                <?= $index > 0 ? ', ' : '' ?><strong><?= e($pending['name']) ?></strong>
            <?php endforeach; ?>
            is awaiting approval from a group admin.
        </div>
    </div>
<?php endif; ?>

<?php if ($groups === []): ?>
    <div class="card mb-3">
        <div class="card-body" style="padding:36px 28px">
            <div class="between" style="align-items:flex-start;flex-wrap:wrap;gap:24px">
                <div style="max-width:560px">
                    <span class="empty-icon" style="margin-bottom:14px"><?= icon('users', 22) ?></span>
                    <h2 style="margin-bottom:6px">Welcome to Classof, <?= e($firstName) ?>.</h2>
                    <p class="muted">You are not part of an alumni group yet. Add your education, then request to join the groups that match.</p>
                    <div class="flex flex-wrap mt-2">
                        <?php if (($educationCount ?? 0) === 0): ?>
                            <a href="<?= url('profile/education') ?>" class="btn btn-primary"><?= icon('grid', 16) ?> Add education</a>
                        <?php endif; ?>
                        <a href="<?= url('explore?for=you') ?>" class="btn btn-outline">Find my groups</a>
                    </div>
                </div>
                <div class="callout info" style="max-width:320px">
                    <?= icon('info', 18) ?>
                    <div>
                        <strong>Tip:</strong> if your class already exists, request to join instead of creating a duplicate. An admin will approve you.
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="stat-grid mb-3">
        <div class="stat-card">
            <span class="stat-icon"><?= icon('users', 20) ?></span>
            <div class="stat-meta">
                <span class="stat-label">My groups</span>
                <span class="stat-value"><?= count($groups) ?></span>
                <span class="stat-hint">Active memberships</span>
            </div>
        </div>
        <div class="stat-card">
            <span class="stat-icon gold"><?= icon('heart', 20) ?></span>
            <div class="stat-meta">
                <span class="stat-label">Welfare given</span>
                <span class="stat-value"><?= money($contributed) ?></span>
                <span class="stat-hint">Your total contributions</span>
            </div>
        </div>
        <div class="stat-card">
            <span class="stat-icon info"><?= icon('calendar', 20) ?></span>
            <div class="stat-meta">
                <span class="stat-label">Upcoming events</span>
                <span class="stat-value"><?= count($events) ?></span>
                <span class="stat-hint">Across your groups</span>
            </div>
        </div>
        <div class="stat-card">
            <span class="stat-icon warn"><?= icon('vote', 20) ?></span>
            <div class="stat-meta">
                <span class="stat-label">Elections open</span>
                <span class="stat-value"><?= count($elections) ?></span>
                <span class="stat-hint">Your vote is waiting</span>
            </div>
        </div>
    </div>

    <div class="split-main">
        <div class="stack">
            <section class="card">
                <div class="card-header">
                    <h2>My groups</h2>
                    <a href="<?= url('explore') ?>" class="card-link">Explore <?= icon('arrow-right', 14) ?></a>
                </div>
                <div class="table-wrap">
                    <table class="table">
                        <tbody>
                        <?php foreach ($groups as $group): ?>
                            <tr>
                                <td>
                                    <div class="table-person">
                                        <span class="group-dot" style="--dot: <?= e($group['cover_color'] ?: '#0b6e4f') ?>;width:36px;height:36px;font-size:14px;border-radius:10px"><?= e(mb_substr((string) $group['name'], 0, 1)) ?></span>
                                        <div class="table-person-meta">
                                            <strong><a href="<?= url('groups/' . $group['slug']) ?>"><?= e($group['name']) ?></a></strong>
                                            <span><?= (int) $group['member_count'] ?> members · <?= e(App\Models\Group::scopeLabel($group['scope'] ?? 'class')) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-right">
                                    <span class="badge badge-<?= in_array($group['role'], ['owner', 'admin'], true) ? 'gold' : ($group['role'] === 'exco' ? 'info' : 'neutral') ?>"><?= e(role_label($group['role'])) ?></span>
                                </td>
                                <td class="text-right" style="width:1%">
                                    <a href="<?= url('groups/' . $group['slug']) ?>" class="btn btn-ghost btn-sm"><?= icon('chevron-right', 16) ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card">
                <div class="card-header">
                    <div>
                        <h2>Upcoming events</h2>
                        <p class="sub">Reunions, webinars and homecomings</p>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($events === []): ?>
                        <div class="empty-state" style="padding:24px 12px">
                            <span class="empty-icon"><?= icon('calendar', 20) ?></span>
                            <h3>No upcoming events</h3>
                            <p>When a group schedules something, it will show up here.</p>
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
                                            <a href="<?= url('groups/' . $event['group_slug'] . '/events/' . $event['id']) ?>"><?= e($event['title']) ?></a>
                                        </div>
                                        <div class="list-item-meta">
                                            <span><?= e($event['group_name']) ?></span>
                                            <span class="dot-sep">·</span>
                                            <span><?= date('g:i A', $ts) ?></span>
                                            <?php if (!empty($event['location'])): ?>
                                                <span class="dot-sep">·</span><span><?= e($event['location']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if ($event['my_rsvp'] === 'going'): ?>
                                        <span class="badge badge-success">Going</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="card">
                <div class="card-header">
                    <h2>Latest announcements</h2>
                </div>
                <div class="card-body">
                    <?php if ($announcements === []): ?>
                        <p class="muted small">No announcements yet.</p>
                    <?php else: ?>
                        <div class="list">
                            <?php foreach ($announcements as $announcement): ?>
                                <div class="list-item">
                                    <span class="stat-icon" style="width:34px;height:34px;border-radius:9px"><?= icon('megaphone', 16) ?></span>
                                    <div class="list-item-body">
                                        <div class="list-item-title">
                                            <a href="<?= url('groups/' . $announcement['group_slug'] . '/announcements') ?>"><?= e($announcement['title']) ?></a>
                                            <?php if ((int) $announcement['pinned'] === 1): ?>
                                                <span class="badge badge-gold">Pinned</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="list-item-text"><?= e(str_limit($announcement['body'], 120)) ?></div>
                                        <div class="list-item-meta mt-1">
                                            <span><?= e($announcement['group_name']) ?></span>
                                            <span class="dot-sep">·</span>
                                            <span><?= e(time_ago($announcement['created_at'])) ?></span>
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
                    <h2>Dues outstanding</h2>
                    <?php if ($soonestDue !== null): ?>
                        <span class="badge badge-warn">Due <?= e(short_date($soonestDue)) ?></span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if ($outstanding === []): ?>
                        <div class="callout">
                            <?= icon('check', 17) ?>
                            <div>You are up to date on dues across all your groups. Well done.</div>
                        </div>
                    <?php else: ?>
                        <div class="list">
                            <?php foreach ($outstanding as $item): ?>
                                <div class="list-item" style="align-items:center">
                                    <div class="list-item-body">
                                        <div class="list-item-title"><?= e($item['cycle']['title']) ?></div>
                                        <div class="list-item-meta"><?= e($item['group']['name']) ?> · due <?= e(short_date($item['cycle']['due_date'])) ?></div>
                                    </div>
                                    <div class="text-right">
                                        <div class="strong"><?= money($item['cycle']['amount'], $item['group']['currency']) ?></div>
                                        <a class="card-link" href="<?= url('groups/' . $item['group']['slug'] . '/dues/' . $item['cycle']['id']) ?>">Pay <?= icon('arrow-right', 13) ?></a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <?php if (!empty($coops)): ?>
                <section class="card">
                    <div class="card-header">
                        <h2>Cooperatives</h2>
                        <span class="badge badge-neutral"><?= count($coops) ?></span>
                    </div>
                    <div class="card-body">
                        <div class="list">
                            <?php foreach ($coops as $entry): ?>
                                <div class="list-item">
                                    <span class="stat-icon gold" style="width:36px;height:36px;border-radius:10px"><?= icon('card', 17) ?></span>
                                    <div class="list-item-body">
                                        <div class="list-item-title">
                                            <a href="<?= url('groups/' . $entry['group']['slug'] . '/coop') ?>"><?= e($entry['coop']['name']) ?></a>
                                        </div>
                                        <div class="list-item-meta"><?= e($entry['group']['name']) ?></div>
                                        <?php if ($entry['member']['status'] === 'pending'): ?>
                                            <span class="badge badge-warn mt-1">Awaiting approval</span>
                                        <?php else: ?>
                                            <div class="list-item-meta mt-1">
                                                <span>Savings <strong><?= money($entry['savings'], $entry['group']['currency']) ?></strong></span>
                                                <span class="dot-sep">·</span>
                                                <span>Shares <strong><?= money($entry['shares'], $entry['group']['currency']) ?></strong></span>
                                            </div>
                                            <?php if ($entry['loan'] !== null): ?>
                                                <div class="tiny muted">Loan <?= money(App\Models\CoopLoan::outstanding($entry['loan']), $entry['group']['currency']) ?> outstanding</div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (!empty($classmates)): ?>
                <section class="card">
                    <div class="card-header">
                        <h2>Classmates</h2>
                        <a href="<?= url('classmates') ?>" class="card-link">See all <?= icon('arrow-right', 13) ?></a>
                    </div>
                    <div class="card-body">
                        <div class="list">
                            <?php foreach ($classmates as $classmate): ?>
                                <div class="list-item" style="align-items:center">
                                    <?= avatar($classmate['name'], $classmate['avatar'] ?? null, 34) ?>
                                    <div class="list-item-body">
                                        <div class="list-item-title" style="font-size:13.5px"><?= e($classmate['name']) ?></div>
                                        <div class="list-item-meta">
                                            <?= e($classmate['institution_name']) ?> · Class of <?= e($classmate['graduation_year']) ?>
                                            <?php if (!empty($classmate['department'])): ?><span class="dot-sep">·</span><span><?= e($classmate['department']) ?></span><?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($elections !== []): ?>
                <section class="card">
                    <div class="card-header">
                        <h2>Elections</h2>
                        <span class="badge badge-success badge-dot">Live</span>
                    </div>
                    <div class="card-body">
                        <div class="list">
                            <?php foreach ($elections as $election): ?>
                                <div class="list-item">
                                    <span class="stat-icon warn" style="width:36px;height:36px;border-radius:10px"><?= icon('vote', 17) ?></span>
                                    <div class="list-item-body">
                                        <div class="list-item-title">
                                            <a href="<?= url('groups/' . $election['group_slug'] . '/elections/' . $election['id']) ?>"><?= e($election['title']) ?></a>
                                        </div>
                                        <div class="list-item-meta">
                                            <?= e($election['group_name']) ?> · <?= (int) $election['position_count'] ?> positions
                                        </div>
                                        <div class="list-item-meta">
                                            <span>Closes <?= e(short_date($election['ends_at'])) ?></span>
                                            <?php if ((int) $election['my_positions'] > 0): ?>
                                                <span class="badge badge-success">You voted</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <section class="card">
                <div class="card-header">
                    <h2>Welfare cases</h2>
                    <a href="<?= url('explore') ?>" class="card-link"><?= icon('heart', 14) ?></a>
                </div>
                <div class="card-body">
                    <?php if ($welfare === []): ?>
                        <p class="muted small">No open welfare cases in your groups.</p>
                    <?php else: ?>
                        <div class="list">
                            <?php foreach ($welfare as $case): ?>
                                <?php $target = (float) ($case['target_amount'] ?? 0); $raised = (float) $case['raised']; $pct = $target > 0 ? percent($raised, $target) : null; ?>
                                <div class="list-item">
                                    <div class="list-item-body">
                                        <div class="list-item-title">
                                            <a href="<?= url('groups/' . $case['group_slug'] . '/welfare/' . $case['id']) ?>"><?= e($case['title']) ?></a>
                                        </div>
                                        <div class="list-item-meta">
                                            <?= e($case['group_name']) ?> · <?= e(App\Models\WelfareCase::categoryLabel($case['category'])) ?>
                                        </div>
                                        <div class="mt-1">
                                            <div class="between" style="font-size:11.5px;color:var(--muted);margin-bottom:4px">
                                                <span><?= money($raised, $case['currency']) ?> raised</span>
                                                <span><?= $target > 0 ? money($target, $case['currency']) : 'No target' ?></span>
                                            </div>
                                            <?php if ($pct !== null): ?>
                                                <div class="progress thin"><span style="width:<?= (int) $pct ?>%"></span></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
<?php endif; ?>
