<?php $title = 'Admin console'; ?>
<div class="admin-hero">
    <span class="badge badge-gold"><?= icon('shield', 12) ?> Platform administrator</span>
    <h1>Platform overview</h1>
    <p>Monitor groups, members, money and election activity across Classof.</p>
</div>

<div class="stat-grid mb-3">
    <div class="stat-card">
        <span class="stat-icon"><?= icon('users', 20) ?></span>
        <div class="stat-meta">
            <span class="stat-label">Users</span>
            <span class="stat-value"><?= number_format($stats['users']) ?></span>
            <span class="stat-hint"><?= number_format($stats['new_users']) ?> new in 30 days</span>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon info"><?= icon('grid', 20) ?></span>
        <div class="stat-meta">
            <span class="stat-label">Groups</span>
            <span class="stat-value"><?= number_format($stats['groups']) ?></span>
            <span class="stat-hint"><?= number_format($stats['active_groups']) ?> active</span>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon gold"><?= icon('wallet', 20) ?></span>
        <div class="stat-meta">
            <span class="stat-label">Collected</span>
            <span class="stat-value"><?= money($stats['collected']) ?></span>
            <span class="stat-hint">Successful payments</span>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon warn"><?= icon('vote', 20) ?></span>
        <div class="stat-meta">
            <span class="stat-label">Elections</span>
            <span class="stat-value"><?= number_format($stats['elections']) ?></span>
            <span class="stat-hint"><?= number_format($stats['votes']) ?> votes cast</span>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon warn"><?= icon('shield', 20) ?></span>
        <div class="stat-meta">
            <span class="stat-label">Institution queue</span>
            <span class="stat-value"><?= number_format($stats['pending_institutions']) ?></span>
            <span class="stat-hint"><a href="<?= url('admin/institutions') ?>">Review institutions</a></span>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon info"><?= icon('grid', 20) ?></span>
        <div class="stat-meta">
            <span class="stat-label">Group requests</span>
            <span class="stat-value"><?= number_format($stats['pending_group_requests']) ?></span>
            <span class="stat-hint"><a href="<?= url('admin/group-requests') ?>">Review requests</a></span>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon warn"><?= icon('users', 20) ?></span>
        <div class="stat-meta">
            <span class="stat-label">Education queue</span>
            <span class="stat-value"><?= number_format($stats['pending_education']) ?></span>
            <span class="stat-hint"><a href="<?= url('admin/education') ?>">Review records</a></span>
        </div>
    </div>
</div>

<div class="split-main">
    <div class="stack">
        <section class="card">
            <div class="card-header">
                <h2>Recent groups</h2>
                <a href="<?= url('admin/groups') ?>" class="card-link">All groups <?= icon('arrow-right', 13) ?></a>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Group</th><th>Members</th><th>Status</th><th>Created</th></tr></thead>
                    <tbody>
                    <?php if ($recentGroups === []): ?>
                        <tr><td colspan="4" class="empty-row">No groups yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($recentGroups as $group): ?>
                        <tr>
                            <td>
                                <div class="table-person">
                                    <span class="group-dot" style="--dot: <?= e($group['cover_color'] ?: '#0b6e4f') ?>"><?= e(mb_substr((string) $group['name'], 0, 1)) ?></span>
                                    <div class="table-person-meta">
                                        <strong><?= e($group['name']) ?></strong>
                                        <span><?= e(App\Models\Group::typeLabel($group['type'])) ?> · <?= e(App\Models\Group::scopeLabel($group['scope'] ?? 'class')) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td><?= (int) $group['member_count'] ?></td>
                            <td><span class="badge badge-<?= $group['status'] === 'active' ? 'success' : 'neutral' ?>"><?= e(ucfirst($group['status'])) ?></span></td>
                            <td class="small muted nowrap"><?= e(short_date($group['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <h2>Newest members</h2>
                <a href="<?= url('admin/users') ?>" class="card-link">All users <?= icon('arrow-right', 13) ?></a>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Member</th><th>Role</th><th>Status</th><th>Joined</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentUsers as $account): ?>
                        <tr>
                            <td>
                                <div class="table-person">
                                    <?= avatar($account['name'], $account['avatar'] ?? null, 32) ?>
                                    <div class="table-person-meta">
                                        <strong><?= e($account['name']) ?></strong>
                                        <span><?= e($account['email']) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="small"><?= ($account['platform_role'] ?? 'user') === 'admin' ? 'Admin' : 'Member' ?></td>
                            <td><span class="badge badge-<?= $account['status'] === 'active' ? 'success' : 'danger' ?>"><?= e(ucfirst($account['status'])) ?></span></td>
                            <td class="small muted nowrap"><?= e(short_date($account['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="stack">
        <section class="card">
            <div class="card-header"><h2>Review queues</h2></div>
            <div class="card-body">
                <div class="stack-sm">
                    <a href="<?= url('admin/institutions') ?>" class="btn btn-outline btn-block">
                        Institutions <span class="badge badge-warn"><?= (int) $stats['pending_institutions'] ?></span>
                    </a>
                    <a href="<?= url('admin/group-requests') ?>" class="btn btn-outline btn-block">
                        Group requests <span class="badge badge-warn"><?= (int) $stats['pending_group_requests'] ?></span>
                    </a>
                    <a href="<?= url('admin/education') ?>" class="btn btn-outline btn-block">
                        Education records <span class="badge badge-warn"><?= (int) $stats['pending_education'] ?></span>
                    </a>
                    <a href="<?= url('admin/settings') ?>" class="btn btn-ghost btn-block">Platform settings</a>
                    <a href="<?= url('admin/whatsapp') ?>" class="btn btn-outline btn-block">WhatsApp outbox</a>
                </div>
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2>Platform activity</h2></div>
            <div class="card-body">
                <?php if ($activity === []): ?>
                    <p class="muted small">No activity recorded yet.</p>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($activity as $entry): ?>
                            <div class="timeline-item">
                                <span class="timeline-dot"></span>
                                <div class="timeline-text"><?= e($entry['description']) ?></div>
                                <div class="timeline-time"><?= e($entry['user_name'] ?? 'System') ?> · <?= e(time_ago($entry['created_at'])) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2>Platform totals</h2></div>
            <div class="card-body">
                <dl class="kv">
                    <dt>Active memberships</dt><dd><?= number_format($stats['members']) ?></dd>
                    <dt>Welfare mobilised</dt><dd><?= money($stats['welfare']) ?></dd>
                    <dt>Votes cast</dt><dd><?= number_format($stats['votes']) ?></dd>
                </dl>
                <div class="divider"></div>
                <div class="flex" style="gap:8px">
                    <a href="<?= url('admin/groups') ?>" class="btn btn-outline btn-sm">Groups</a>
                    <a href="<?= url('admin/users') ?>" class="btn btn-outline btn-sm">Users</a>
                </div>
            </div>
        </section>
    </div>
</div>
