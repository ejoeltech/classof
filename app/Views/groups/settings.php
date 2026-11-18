<?php
$title = 'Group settings';
require BASE_PATH . '/app/Views/partials/group-header.php';
$isOwner = $membership['role'] === 'owner';
?>
<div class="page-head">
    <div>
        <h1>Group settings</h1>
        <p>Update the affiliation this group represents and how people join.</p>
    </div>
    <a href="<?= url('groups/' . $group['slug']) ?>" class="btn btn-ghost">Back to group</a>
</div>

<div class="split-main">
    <div class="stack">
        <form method="post" action="<?= url('groups/' . $group['slug'] . '/settings') ?>" enctype="multipart/form-data" class="card" data-submit-once>
            <?= csrf_field() ?>
            <div class="card-header">
                <div>
                    <h2>Group profile &amp; scope</h2>
                    <p class="sub">Visible to everyone in the group</p>
                </div>
            </div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="field span-2">
                        <label for="name">Group name</label>
                        <input type="text" id="name" name="name" value="<?= e(old('name', $group['name'])) ?>" required
                               data-group-name
                               data-name-placeholder-institution="e.g. University of Lagos Alumni Association"
                               data-name-placeholder-class="e.g. King's College, Lagos — Class of 2010"
                               data-name-placeholder-department="e.g. Department of Computer Science, UNILAG — Alumni"
                               data-name-placeholder-faculty="e.g. Faculty of Science, UNILAG — Alumni"
                               data-name-placeholder-association="e.g. King's College Lagos Old Boys Association"
                               data-name-placeholder-other="e.g. Alumni group name"
                               class="<?= isset($errors['name']) ? 'is-invalid' : '' ?>">
                        <?php if (isset($errors['name'])): ?><span class="error-text"><?= e($errors['name']) ?></span><?php endif; ?>
                    </div>

                    <div class="field span-2">
                        <label for="scope">What does this group represent?</label>
                        <select id="scope" name="scope" class="select" data-scope-select>
                            <?php foreach ($scopes as $key => $label): ?>
                                <option value="<?= e($key) ?>" <?= old('scope', $group['scope']) === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="hint">Changing scope updates how the group is labelled and filtered — membership records are unaffected.</span>
                    </div>

                    <div class="field">
                        <label for="type">Institution type</label>
                        <select id="type" name="type" class="select">
                            <?php foreach ($types as $key => $label): ?>
                                <option value="<?= e($key) ?>" <?= old('type', $group['type']) === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field">
                        <label for="institution_id">Institution</label>
                        <select id="institution_id" name="institution_id" class="select <?= isset($errors['institution_id']) ? 'is-invalid' : '' ?>" required data-institution-select data-faculty-url="<?= url('catalogue/faculties') ?>">
                            <?php if (empty($group['institution_id'])): ?>
                                <option value="">Choose institution…</option>
                            <?php endif; ?>
                            <?php foreach ($institutions as $institution): ?>
                                <option value="<?= (int) $institution['id'] ?>" <?= (int) old('institution_id', $group['institution_id']) === (int) $institution['id'] ? 'selected' : '' ?>>
                                    <?= e($institution['name']) ?> — <?= e(App\Models\Institution::label($institution)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="hint">Groups are tied to a verified catalogue institution. Requesting a new institution is handled from your profile.</span>
                        <?php if (isset($errors['institution_id'])): ?><span class="error-text"><?= e($errors['institution_id']) ?></span><?php endif; ?>
                    </div>

                    <div class="field" data-scope-for="class department faculty">
                        <label for="faculty_id">Faculty</label>
                        <select id="faculty_id" name="faculty_id" class="select" data-faculty-select data-department-url="<?= url('catalogue/departments') ?>" data-placeholder="No faculty selected" data-selected="<?= e(old('faculty_id', $group['faculty_id'])) ?>">
                            <option value="">No faculty selected</option>
                        </select>
                        <?php if (isset($errors['faculty_id'])): ?><span class="error-text"><?= e($errors['faculty_id']) ?></span><?php endif; ?>
                    </div>

                    <div class="field" data-scope-for="class department">
                        <label for="department_id">Department</label>
                        <select id="department_id" name="department_id" class="select" data-department-select data-placeholder="No department selected" data-selected="<?= e(old('department_id', $group['department_id'])) ?>">
                            <option value="">No department selected</option>
                        </select>
                        <?php if (isset($errors['department_id'])): ?><span class="error-text"><?= e($errors['department_id']) ?></span><?php endif; ?>
                    </div>

                    <div class="field" data-scope-for="class">
                        <label for="graduation_year">Graduation year</label>
                        <input type="number" id="graduation_year" name="graduation_year" value="<?= e(old('graduation_year', $group['graduation_year'])) ?>" min="1900" max="<?= date('Y') + 10 ?>" data-required-scope="class">
                        <?php if (isset($errors['graduation_year'])): ?><span class="error-text"><?= e($errors['graduation_year']) ?></span><?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="country">Country</label>
                        <input type="text" id="country" name="country" value="<?= e(old('country', $group['country'])) ?>">
                    </div>

                    <div class="field">
                        <label for="currency">Currency</label>
                        <select id="currency" name="currency" class="select">
                            <?php foreach ($currencies as $code => $label): ?>
                                <option value="<?= e($code) ?>" <?= old('currency', $group['currency']) === $code ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="hint">Used for dues, welfare and events.</span>
                    </div>

                    <div class="field">
                        <label for="logo">Group logo</label>
                        <input type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp,image/gif">
                        <?php if (!empty($group['logo'])): ?><span class="hint">Uploading a new logo replaces the current one.</span><?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="visibility">Visibility</label>
                        <select id="visibility" name="visibility" class="select">
                            <option value="public" <?= old('visibility', $group['visibility']) === 'public' ? 'selected' : '' ?>>Public — discoverable</option>
                            <option value="private" <?= old('visibility', $group['visibility']) === 'private' ? 'selected' : '' ?>>Private — hidden</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="join_policy">Joining</label>
                        <select id="join_policy" name="join_policy" class="select">
                            <option value="approval" <?= old('join_policy', $group['join_policy']) === 'approval' ? 'selected' : '' ?>>Proof required, admin approval</option>
                            <option value="invite" <?= old('join_policy', $group['join_policy']) === 'invite' ? 'selected' : '' ?>>Invitation only</option>
                        </select>
                    </div>

                    <div class="field span-2">
                        <label for="description">About the group</label>
                        <textarea id="description" name="description" class="textarea"><?= e(old('description', $group['description'])) ?></textarea>
                    </div>

                    <div class="divider span-2"></div>
                    <div class="span-2">
                        <p class="tiny muted" style="text-transform:uppercase;letter-spacing:.08em;font-weight:700">Group bank account</p>
                        <p class="hint" style="margin-top:4px">Optional. When set, manual payment instructions show this account instead of the platform-wide account in <code>.env</code>. Provide all three fields together.</p>
                    </div>
                    <div class="field">
                        <label for="bank_name">Bank name</label>
                        <input type="text" id="bank_name" name="bank_name" maxlength="120" value="<?= e(old('bank_name', $group['bank_name'] ?? '')) ?>" placeholder="e.g. First Bank of Nigeria">
                        <?php if (isset($errors['bank_name'])): ?><span class="error-text"><?= e($errors['bank_name']) ?></span><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="bank_account_number">Account number</label>
                        <input type="text" id="bank_account_number" name="bank_account_number" maxlength="40" value="<?= e(old('bank_account_number', $group['bank_account_number'] ?? '')) ?>" placeholder="0123456789">
                        <?php if (isset($errors['bank_account_number'])): ?><span class="error-text"><?= e($errors['bank_account_number']) ?></span><?php endif; ?>
                    </div>
                    <div class="field span-2">
                        <label for="bank_account_holder">Account name</label>
                        <input type="text" id="bank_account_holder" name="bank_account_holder" maxlength="160" value="<?= e(old('bank_account_holder', $group['bank_account_holder'] ?? '')) ?>" placeholder="e.g. King's College Class of 2010">
                        <?php if (isset($errors['bank_account_holder'])): ?><span class="error-text"><?= e($errors['bank_account_holder']) ?></span><?php endif; ?>
                    </div>
                </div>

                <div class="callout info mt-2">
                    <?= icon('shield', 17) ?>
                    <div>Self-service join requests always require proof and admin approval. Invitation links are pre-approved by the person who sent them.</div>
                </div>
            </div>
            <div class="card-footer between">
                <span class="muted small">Changes are recorded in the audit trail.</span>
                <button type="submit" class="btn btn-primary">Save changes</button>
            </div>
        </form>

        <?php $groupSite = App\Models\GroupSite::forGroup($group); ?>
        <div class="card">
            <div class="card-header">
                <div>
                    <h2>Group website</h2>
                    <p class="sub">A simple public landing page with news, events and leadership</p>
                </div>
                <span class="badge badge-<?= $groupSite['status'] === 'published' ? 'success' : 'neutral' ?>"><?= e(ucfirst($groupSite['status'])) ?></span>
            </div>
            <div class="card-body">
                <p class="muted small" style="margin-top:0">Public address: <code><?= e(group_site_url((string) $groupSite['subdomain'])) ?></code></p>
                <div class="flex flex-wrap" style="gap:8px">
                    <a href="<?= url('groups/' . $group['slug'] . '/site') ?>" class="btn btn-primary btn-sm"><?= icon('edit', 14) ?> Edit website</a>
                    <a href="<?= e(group_site_url((string) $groupSite['subdomain'])) ?>" class="btn btn-outline btn-sm" target="_blank" rel="noopener">Open site</a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h2>Pending invitations</h2>
                    <p class="sub">Links expire after 14 days</p>
                </div>
                <a href="<?= url('groups/' . $group['slug'] . '/members/invite') ?>" class="btn btn-outline btn-sm"><?= icon('plus', 14) ?> Invite</a>
            </div>
            <div class="card-body">
                <?php if ($invites === []): ?>
                    <p class="muted small">No pending invitations. Invite classmates from the members page.</p>
                <?php else: ?>
                    <div class="list">
                        <?php foreach ($invites as $invite): ?>
                            <?php $link = absolute_url('invite/' . $invite['token']); ?>
                            <div class="list-item" style="align-items:center">
                                <span class="stat-icon" style="width:34px;height:34px;border-radius:9px"><?= icon('mail', 16) ?></span>
                                <div class="list-item-body">
                                    <div class="list-item-title" style="font-size:13.5px"><?= e($invite['email']) ?></div>
                                    <div class="list-item-meta"><?= e(role_label($invite['role'])) ?> · expires <?= e(short_date($invite['expires_at'])) ?></div>
                                </div>
                                <button type="button" class="btn btn-outline btn-sm" data-copy="<?= e($link) ?>">Copy link</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <section class="card">
            <div class="card-header">
                <div>
                    <h2>WhatsApp channels</h2>
                    <p class="sub">Invite links only — Classof cannot read or post inside WhatsApp groups</p>
                </div>
                <span class="badge badge-neutral"><?= count(array_filter($channels, static fn (array $c): bool => $c['revoked_at'] === null)) ?></span>
            </div>
            <div class="card-body">
                <?php $activeChannels = array_values(array_filter($channels, static fn (array $c): bool => $c['revoked_at'] === null)); ?>
                <?php if ($activeChannels === []): ?>
                    <p class="muted small">No WhatsApp invite links stored yet.</p>
                <?php else: ?>
                    <div class="list">
                        <?php foreach ($activeChannels as $channel): ?>
                            <div class="list-item" style="align-items:flex-start">
                                <span class="stat-icon" style="width:34px;height:34px;border-radius:9px;background:var(--forest-50);color:var(--forest-700)"><?= icon('link', 16) ?></span>
                                <div class="list-item-body">
                                    <div class="list-item-title" style="font-size:13.5px">
                                        <?= e($channel['label']) ?>
                                        <?php if ((int) $channel['is_primary'] === 1): ?><span class="badge badge-gold">Primary</span><?php endif; ?>
                                    </div>
                                    <div class="list-item-meta">
                                        <?= $channel['visible_to'] === 'manager' ? 'Managers only' : 'All active members' ?>
                                        <span class="dot-sep">·</span>
                                        <span>verified <?= e(time_ago($channel['last_verified_at'])) ?></span>
                                    </div>
                                    <div class="copy-row">
                                        <input type="text" value="<?= e($channel['invite_url']) ?>" readonly data-select>
                                        <button type="button" class="btn btn-outline btn-sm" data-copy="<?= e($channel['invite_url']) ?>">Copy</button>
                                    </div>
                                </div>
                                <form method="post" action="<?= url('groups/' . $group['slug'] . '/channels/' . $channel['id'] . '/revoke') ?>" data-confirm="Revoke this WhatsApp link? It will disappear from the group.">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-ghost btn-sm">Revoke</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="divider"></div>
                <form method="post" action="<?= url('groups/' . $group['slug'] . '/channels') ?>" class="stack-sm" data-submit-once>
                    <?= csrf_field() ?>
                    <div class="form-grid">
                        <div class="field">
                            <label for="channel_label">Label</label>
                            <input type="text" id="channel_label" name="label" required maxlength="80" placeholder="e.g. Main group">
                        </div>
                        <div class="field">
                            <label for="channel_visible">Who can see it</label>
                            <select id="channel_visible" name="visible_to" class="select">
                                <option value="members">All active members</option>
                                <option value="manager">Managers only</option>
                            </select>
                        </div>
                        <div class="field span-2">
                            <label for="channel_url">WhatsApp invite link</label>
                            <input type="url" id="channel_url" name="invite_url" required maxlength="255" placeholder="https://chat.whatsapp.com/…">
                            <span class="hint">Only https chat.whatsapp.com or wa.me links are accepted.</span>
                        </div>
                    </div>
                    <label class="checkbox"><input type="checkbox" name="is_primary" value="1"><span>Make this the primary group</span></label>
                    <button type="submit" class="btn btn-outline btn-sm">Add WhatsApp channel</button>
                </form>
            </div>
        </section>
    </div>

    <div class="stack">
        <section class="card">
            <div class="card-header"><h2>Group at a glance</h2></div>
            <div class="card-body">
                <dl class="kv">
                    <dt>Scope</dt><dd><span class="badge badge-neutral"><?= e(App\Models\Group::scopeLabel($group['scope'])) ?></span></dd>
                    <dt>Affiliation</dt><dd><?= e(App\Models\Group::descriptor($group)) ?></dd>
                    <dt>Members</dt><dd><?= (int) $stats['members'] ?></dd>
                    <dt>Pending requests</dt><dd><?= (int) $stats['pending'] ?></dd>
                    <dt>Announcements</dt><dd><?= (int) $stats['announcements'] ?></dd>
                    <dt>Your role</dt><dd><span class="badge badge-gold"><?= e(role_label($membership['role'])) ?></span></dd>
                    <dt>Created</dt><dd><?= e(short_date($group['created_at'])) ?></dd>
                </dl>
                <div class="divider"></div>
                <p class="muted small">Need to change who is in charge? Promote another member to admin first, then they can help manage the group.</p>
                <a href="<?= url('groups/' . $group['slug'] . '/members') ?>" class="btn btn-outline btn-block">Manage members</a>
            </div>
        </section>

        <section class="card danger-zone">
            <h3>Leave this group</h3>
            <?php if ($isOwner): ?>
                <p class="small muted mt-1">As the owner you cannot leave the group. Ownership transfer is handled by the platform administrator — contact support if you need to hand over.</p>
            <?php else: ?>
                <p class="small muted mt-1">You will lose access to this group's directory, dues, welfare and elections. You can submit a new proof-backed join request later.</p>
                <form method="post" action="<?= url('groups/' . $group['slug'] . '/leave') ?>" data-confirm="Leave <?= e($group['name']) ?>? You will lose access until you rejoin.">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger-ghost btn-sm"><?= icon('logout', 15) ?> Leave group</button>
                </form>
            <?php endif; ?>
        </section>
    </div>
</div>
