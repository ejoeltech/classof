<?php
$title = 'Invite classmates';
require BASE_PATH . '/app/Views/partials/group-header.php';
?>
<div class="page-head">
    <div>
        <h1>Invite classmates</h1>
        <p>Send an invitation link to a classmate's email address.</p>
    </div>
    <a href="<?= url('groups/' . $group['slug'] . '/members') ?>" class="btn btn-ghost">Back to members</a>
</div>

<div class="split-main">
    <div class="stack">
        <form method="post" action="<?= url('groups/' . $group['slug'] . '/members/invite') ?>" class="card" data-submit-once>
            <?= csrf_field() ?>
            <div class="card-header">
                <div>
                    <h2>New invitation</h2>
                    <p class="sub">The link can only be used by the invited email address</p>
                </div>
            </div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="field span-2">
                        <label for="email">Email address</label>
                        <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" required placeholder="classmate@example.com" class="<?= isset($errors['email']) ? 'is-invalid' : '' ?>">
                        <?php if (isset($errors['email'])): ?><span class="error-text"><?= e($errors['email']) ?></span><?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="role">Invite as</label>
                        <select id="role" name="role" class="select">
                            <option value="member" <?= old('role', 'member') === 'member' ? 'selected' : '' ?>>Member</option>
                            <option value="exco" <?= old('role') === 'exco' ? 'selected' : '' ?>>Exco / Committee</option>
                            <?php if ($isOwner): ?>
                                <option value="admin" <?= old('role') === 'admin' ? 'selected' : '' ?>>Admin</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="field span-2">
                        <label for="message">Personal message <span class="hint">(optional)</span></label>
                        <textarea id="message" name="message" class="textarea" style="min-height:90px" placeholder="Add a short note to the invitation email…"><?= e(old('message')) ?></textarea>
                    </div>
                </div>

                <div class="callout mt-2">
                    <?= icon('mail', 17) ?>
                    <div>Email delivery is logged to <code>storage/logs/mail.log</code> in local mode. The invitation link will also be shown so you can copy and share it directly.</div>
                </div>
            </div>
            <div class="card-footer between">
                <span class="muted small">Invitations expire after 14 days.</span>
                <button type="submit" class="btn btn-primary"><?= icon('plus', 15) ?> Create invitation</button>
            </div>
        </form>

        <section class="card">
            <div class="card-header">
                <h2>Pending invitations</h2>
                <span class="badge badge-neutral"><?= count($invites) ?></span>
            </div>
            <div class="card-body">
                <?php if ($invites === []): ?>
                    <p class="muted small">No pending invitations.</p>
                <?php else: ?>
                    <div class="list">
                        <?php foreach ($invites as $invite): ?>
                            <?php $link = absolute_url('invite/' . $invite['token']); ?>
                            <div class="list-item" style="align-items:center">
                                <span class="stat-icon" style="width:34px;height:34px;border-radius:9px"><?= icon('mail', 16) ?></span>
                                <div class="list-item-body">
                                    <div class="list-item-title" style="font-size:13.5px"><?= e($invite['email']) ?></div>
                                    <div class="list-item-meta">
                                        <?= e(role_label($invite['role'])) ?> · invited <?= e(time_ago($invite['created_at'])) ?> · expires <?= e(short_date($invite['expires_at'])) ?>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline btn-sm" data-copy="<?= e($link) ?>">Copy link</button>
                                <a href="<?= e(whatsapp_share_url(null, 'Join ' . $group['name'] . ' on Classof: ' . $link)) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm">
                                    <?= icon('message', 14) ?> WhatsApp
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <div class="stack">
        <section class="card">
            <div class="card-header"><h2>Who is already here?</h2></div>
            <div class="card-body">
                <p class="muted small mb-2"><?= count($members) ?> active members. Before inviting, check that your classmate is not already on the list.</p>
                <div class="list">
                    <?php foreach (array_slice($members, 0, 8) as $member): ?>
                        <div class="list-item" style="align-items:center;padding:8px 0">
                            <?= avatar($member['name'], $member['avatar'] ?? null, 30) ?>
                            <div class="list-item-body">
                                <div class="list-item-title" style="font-size:13px"><?= e($member['name']) ?></div>
                                <div class="list-item-meta"><?= e($member['email']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <div class="callout">
            <?= icon('info', 17) ?>
            <div>
                <strong>Approval policy:</strong> this group is set to
                <strong><?= $group['join_policy'] === 'invite' ? 'invitation only' : 'proof + admin approval' ?></strong>.
                Invited members join automatically as active.
            </div>
        </div>
    </div>
</div>
