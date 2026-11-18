<?php
$title = 'My profile';
$statusTone = static fn (string $s): string => match ($s) {
    'active' => 'success',
    'pending' => 'warn',
    'suspended', 'rejected' => 'danger',
    default => 'neutral',
};
$paymentTone = static fn (string $s): string => match ($s) {
    'success' => 'success',
    'pending' => 'warn',
    default => 'neutral',
};
?>
<div class="page-head">
    <div>
        <h1>My profile</h1>
        <p>Your details as they appear to your alumni groups.</p>
    </div>
    <div class="page-head-actions">
        <a href="<?= url('profile/education') ?>" class="btn btn-outline"><?= icon('grid', 15) ?> Education records</a>
        <a href="<?= url('classmates') ?>" class="btn btn-outline"><?= icon('users', 15) ?> Classmates</a>
        <a href="<?= url('profile/group-requests') ?>" class="btn btn-ghost">Group requests</a>
    </div>
</div>

<div class="split-main">
    <div class="stack">
        <form method="post" action="<?= url('profile') ?>" enctype="multipart/form-data" class="card" data-submit-once>
            <?= csrf_field() ?>
            <div class="card-header">
                <div>
                    <h2>Personal details</h2>
                    <p class="sub">Seen by members of your groups</p>
                </div>
            </div>
            <div class="card-body">
                <div class="flex mb-3" style="gap:16px">
                    <?= avatar($user['name'], $user['avatar'] ?? null, 72) ?>
                    <div class="field" style="flex:1">
                        <label for="avatar">Profile photo</label>
                        <input type="file" id="avatar" name="avatar" accept="image/png,image/jpeg,image/webp,image/gif">
                        <span class="hint">JPEG, PNG, WebP or GIF. Maximum 4MB.</span>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="field span-2">
                        <label for="name">Full name</label>
                        <input type="text" id="name" name="name" value="<?= e(old('name', $user['name'])) ?>" required class="<?= isset($errors['name']) ? 'is-invalid' : '' ?>">
                        <?php if (isset($errors['name'])): ?><span class="error-text"><?= e($errors['name']) ?></span><?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" value="<?= e(old('phone', $user['phone'])) ?>" placeholder="+234 800 000 0000">
                    </div>

                    <div class="field">
                        <label for="occupation">Occupation</label>
                        <input type="text" id="occupation" name="occupation" value="<?= e(old('occupation', $user['occupation'])) ?>" placeholder="e.g. Software Engineer">
                    </div>

                    <div class="field span-2">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" value="<?= e(old('location', $user['location'])) ?>" placeholder="e.g. Lagos, Nigeria">
                    </div>

                    <div class="field span-2">
                        <label for="bio">Short bio</label>
                        <textarea id="bio" name="bio" class="textarea" style="min-height:100px" placeholder="A line or two about you…"><?= e(old('bio', $user['bio'])) ?></textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer between">
                <span class="muted small">Member since <?= e(short_date($user['created_at'])) ?></span>
                <button type="submit" class="btn btn-primary">Save profile</button>
            </div>
        </form>

        <section class="card">
            <div class="card-header">
                <h2>My groups</h2>
                <span class="badge badge-neutral"><?= count($memberships) ?></span>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Group</th><th>Role</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php if ($memberships === []): ?>
                        <tr><td colspan="4" class="empty-row">You have not joined any groups yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($memberships as $membership): ?>
                        <tr>
                            <td>
                                <div class="table-person-meta">
                                    <strong><?= e($membership['group_name']) ?></strong>
                                    <span><?= e(App\Models\Group::typeLabel($membership['group_type'])) ?> · <?= e(App\Models\Group::scopeLabel($membership['group_scope'] ?? 'class')) ?></span>
                                </div>
                            </td>
                            <td><span class="badge badge-neutral"><?= e(role_label($membership['role'])) ?></span></td>
                            <td><span class="badge badge-<?= $statusTone($membership['status']) ?>"><?= e(status_label($membership['status'])) ?></span></td>
                            <td class="text-right">
                                <?php if ($membership['status'] === 'active'): ?>
                                    <a href="<?= url('groups/' . $membership['group_slug']) ?>" class="btn btn-ghost btn-sm">Open</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <h2>Payment history</h2>
                <span class="badge badge-neutral"><?= count($payments) ?></span>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Reference</th><th>Group</th><th>Purpose</th><th>Amount</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php if ($payments === []): ?>
                        <tr><td colspan="6" class="empty-row">No payments yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td class="tiny" style="font-family:ui-monospace,monospace"><?= e($payment['reference']) ?></td>
                            <td class="small"><?= e($payment['group_name']) ?></td>
                            <td><span class="badge badge-neutral"><?= e($purposes[$payment['purpose']] ?? $payment['purpose']) ?></span></td>
                            <td class="strong nowrap"><?= money($payment['amount'], $payment['currency']) ?></td>
                            <td><span class="badge badge-<?= $paymentTone($payment['status']) ?>"><?= e(ucfirst($payment['status'])) ?></span></td>
                            <td class="text-right"><a href="<?= url('payments/' . $payment['id'] . '/instructions') ?>" class="btn btn-ghost btn-sm">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="stack">
        <section class="card">
            <div class="card-header"><h2>Account</h2></div>
            <div class="card-body">
                <dl class="kv">
                    <dt>Email</dt><dd style="word-break:break-all"><?= e($user['email']) ?></dd>
                    <dt>Account type</dt><dd><?= ($user['platform_role'] ?? 'user') === 'admin' ? 'Platform admin' : 'Member' ?></dd>
                    <dt>Joined</dt><dd><?= e(short_date($user['created_at'])) ?></dd>
                    <dt>Last login</dt><dd><?= e($user['last_login_at'] ? time_ago($user['last_login_at']) : 'This is your first session') ?></dd>
                </dl>
                <div class="divider"></div>
                <p class="tiny muted" style="text-transform:uppercase;letter-spacing:.08em;font-weight:700">Welfare given</p>
                <div class="stat-value"><?= money($contributed) ?></div>
                <p class="muted small mb-0">Total across all groups</p>
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <h2>WhatsApp updates</h2>
                <?php if (!empty($user['whatsapp_opt_in_at']) && empty($user['whatsapp_opt_out_at'])): ?>
                    <span class="badge badge-success">On</span>
                <?php else: ?>
                    <span class="badge badge-neutral">Off</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form method="post" action="<?= url('profile/whatsapp') ?>" class="stack-sm" data-submit-once>
                    <?= csrf_field() ?>
                    <div class="field">
                        <label for="whatsapp_phone">WhatsApp number</label>
                        <input type="tel" id="whatsapp_phone" name="whatsapp_phone" value="<?= e($user['phone_e164'] ?? '') ?>" placeholder="+2348012345678">
                        <span class="hint">International format, including the + and country code.</span>
                    </div>
                    <label class="checkbox">
                        <input type="checkbox" name="whatsapp_opt_in" value="1" <?= !empty($user['whatsapp_opt_in_at']) && empty($user['whatsapp_opt_out_at']) ? 'checked' : '' ?>>
                        <span>Send me announcement and event reminders on WhatsApp</span>
                    </label>
                    <button type="submit" class="btn btn-outline btn-block">Save WhatsApp preference</button>
                </form>
                <p class="tiny muted mt-2 mb-0">Only group announcements and event reminders are queued. Group admins cannot read WhatsApp messages.</p>
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2>Change password</h2></div>
            <div class="card-body">
                <form method="post" action="<?= url('profile/password') ?>" class="stack-sm" data-submit-once>
                    <?= csrf_field() ?>
                    <div class="field">
                        <label for="current_password">Current password</label>
                        <input type="password" id="current_password" name="current_password" required autocomplete="current-password" class="<?= isset($errors['current_password']) ? 'is-invalid' : '' ?>">
                        <?php if (isset($errors['current_password'])): ?><span class="error-text"><?= e($errors['current_password']) ?></span><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="new_password">New password</label>
                        <input type="password" id="new_password" name="password" required autocomplete="new-password" minlength="8" class="<?= isset($errors['password']) ? 'is-invalid' : '' ?>">
                        <?php if (isset($errors['password'])): ?><span class="error-text"><?= e($errors['password']) ?></span><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="password_confirmation">Confirm new password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn btn-outline btn-block">Update password</button>
                </form>
            </div>
        </section>
    </div>
</div>
