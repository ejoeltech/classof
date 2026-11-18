<?php
$title = $target['name'];
require BASE_PATH . '/app/Views/partials/group-header.php';

$proofState = App\Models\Membership::proofState($target);
$proofTone = ['verified' => 'success', 'pending' => 'warn', 'rejected' => 'danger', 'none' => 'neutral'][$proofState] ?? 'neutral';
$proofLabel = ['verified' => 'Proof verified', 'pending' => 'Awaiting review', 'rejected' => 'Proof rejected', 'none' => 'No proof on file'][$proofState] ?? 'No proof';
$isActive = $target['status'] === 'active';
$backUrl = ($membership['status'] ?? '') === 'active' ? 'groups/' . $group['slug'] . '/members' : 'dashboard';
$backLabel = ($membership['status'] ?? '') === 'active' ? 'All members' : 'Dashboard';
$file = (string) ($target['proof_file'] ?? '');
$isImage = $file !== '' && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true);
?>
<div class="page-head">
    <div>
        <a href="<?= url($backUrl) ?>" class="tiny muted" style="display:inline-flex;align-items:center;gap:4px;margin-bottom:6px"><?= icon('chevron-right', 13) ?> <?= e($backLabel) ?></a>
        <h1><?= e($target['name']) ?></h1>
        <p><?= e($target['position_title'] ?: role_label($target['role'])) ?> · <?= e(App\Models\Group::descriptor($group)) ?></p>
    </div>
    <span class="badge badge-<?= $isActive ? 'success' : ($target['status'] === 'pending' ? 'warn' : ($target['status'] === 'rejected' ? 'danger' : 'neutral')) ?>">
        <?= e(status_label($target['status'])) ?>
    </span>
</div>

<div class="split-main">
    <div class="stack">
        <section class="card">
            <div class="card-body">
                <div class="flex" style="gap:16px;align-items:flex-start">
                    <?= avatar($target['name'], $target['avatar'] ?? null, 72) ?>
                    <div style="flex:1;min-width:0">
                        <h2 style="font-size:20px"><?= e($target['name']) ?><?= $isSelf ? ' (you)' : '' ?></h2>
                        <div class="meta-row mt-1">
                            <?php if (!empty($target['occupation'])): ?><span><?= e($target['occupation']) ?></span><span class="dot-sep">·</span><?php endif; ?>
                            <?php if (!empty($target['location'])): ?><span><?= icon('location', 14) ?> <?= e($target['location']) ?></span><span class="dot-sep">·</span><?php endif; ?>
                            <span><?= icon('mail', 14) ?> <?= e($target['email']) ?></span>
                        </div>
                        <?php if (!empty($target['phone'])): ?>
                            <div class="tiny muted mt-1"><?= icon('user', 13) ?> <?= e($target['phone']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($target['bio'])): ?>
                            <p class="prose mt-2"><?= nl2br(e($target['bio'])) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="divider"></div>
                <dl class="kv">
                    <dt>Role</dt><dd><span class="badge badge-neutral"><?= e(role_label($target['role'])) ?></span><?= !empty($target['position_title']) ? ' ' . e($target['position_title']) : '' ?></dd>
                    <dt>Membership no.</dt><dd><?= e($target['membership_no'] ?: '—') ?></dd>
                    <dt>Requested</dt><dd><?= e(date_time($target['proof_submitted_at'] ?? $target['created_at'])) ?></dd>
                    <dt>Joined</dt><dd><?= e($target['joined_at'] ? date_time($target['joined_at']) : '—') ?></dd>
                    <dt>Reviewed by</dt><dd><?= e($target['reviewer_name'] ?: '—') ?><?= !empty($target['reviewed_at']) ? ' · ' . e(short_date($target['reviewed_at'])) : '' ?></dd>
                </dl>
            </div>
        </section>

        <?php if ($canViewProof && (!empty($target['claim_match']) || $claimedEducation !== null || (int) $target['claim_flagged'] === 1)): ?>
            <section class="card">
                <div class="card-header">
                    <div><h2>Education claim</h2><p class="sub">Snapshot taken when the request was submitted</p></div>
                    <?php if ((int) $target['claim_flagged'] === 1): ?>
                        <span class="badge badge-warn">Unconfirmed claim</span>
                    <?php else: ?>
                        <span class="badge badge-success">Education confirmed</span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if ((int) $target['claim_flagged'] === 1): ?>
                        <div class="callout mb-2" style="background:var(--warn-50);border-color:#fde7bf;color:#7a3a06">
                            <?= icon('alert', 17) ?>
                            <div>This claim comes from an education record a platform admin has not confirmed. Review the proof carefully.</div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($target['claim_match'])): ?>
                        <p class="small mb-2"><strong>Matched:</strong> <?= e($target['claim_match']) ?></p>
                    <?php endif; ?>

                    <?php if ($claimedEducation !== null): ?>
                        <dl class="kv">
                            <dt>Institution</dt><dd><?= e($claimedEducation['institution_name'] ?: 'Unknown') ?></dd>
                            <dt>Programme</dt><dd><?= e($claimedEducation['programme'] ?: '—') ?></dd>
                            <dt>Faculty</dt><dd><?= e($claimedEducation['faculty'] ?: '—') ?></dd>
                            <dt>Department</dt><dd><?= e($claimedEducation['department'] ?: '—') ?></dd>
                            <dt>Year</dt><dd><?= e($claimedEducation['graduation_year'] ?: '—') ?></dd>
                            <dt>Record status</dt><dd><span class="badge badge-<?= $claimedEducation['status'] === 'verified' ? 'success' : 'warn' ?>"><?= e(ucfirst($claimedEducation['status'])) ?></span></dd>
                        </dl>
                    <?php else: ?>
                        <p class="muted small">No education record was attached to this request.</p>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($canViewProof): ?>
            <section class="card">
                <div class="card-header">
                    <div>
                        <h2>Proof of affiliation</h2>
                        <p class="sub">Only you and group admins can see this</p>
                    </div>
                    <span class="badge badge-<?= $proofTone ?>"><?= e($proofLabel) ?></span>
                </div>
                <div class="card-body">
                    <?php if ($target['proof_type'] === null): ?>
                        <p class="muted small">No proof is recorded for this membership. Existing members invited before proof tracking may not have a document on file.</p>
                    <?php else: ?>
                        <dl class="kv">
                            <dt>Evidence type</dt><dd><?= e(App\Models\Membership::PROOF_TYPES[$target['proof_type']] ?? ucfirst($target['proof_type'])) ?></dd>
                            <dt>Reference</dt><dd><?= e($target['proof_reference'] ?: '—') ?></dd>
                            <dt>Submitted</dt><dd><?= e($target['proof_submitted_at'] ? date_time($target['proof_submitted_at']) : '—') ?></dd>
                        </dl>

                        <?php if (!empty($target['proof_note'])): ?>
                            <div class="callout mt-2">
                                <?= icon('info', 17) ?>
                                <div><?= nl2br(e($target['proof_note'])) ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if ($file !== ''): ?>
                            <div class="divider"></div>
                            <?php if ($isImage): ?>
                                <a href="<?= url('proofs/' . $file) ?>" target="_blank" rel="noopener">
                                    <img src="<?= url('proofs/' . $file) ?>" alt="Proof document" style="max-width:100%;border-radius:12px;border:1px solid var(--line)">
                                </a>
                            <?php endif; ?>
                            <a href="<?= url('proofs/' . $file) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm mt-2">
                                <?= icon('download', 15) ?> Open document
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($proofState === 'rejected'): ?>
                        <div class="callout mt-2" style="background:var(--danger-50);border-color:#fbd5d1;color:#7a1a12">
                            <?= icon('alert', 17) ?>
                            <div>
                                <strong>This request was rejected.</strong>
                                <?php if (!empty($target['rejection_reason'])): ?>
                                    <div>Reason: <?= e($target['rejection_reason']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($isSelf): ?>
                            <a href="<?= url('join/' . $group['slug']) ?>" class="btn btn-primary btn-block mt-2">Submit new proof</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </section>
        <?php else: ?>
            <div class="callout info">
                <?= icon('lock', 17) ?>
                <div>Proof documents are private. They are visible only to <?= e($target['name']) ?> and the group admins.</div>
            </div>
        <?php endif; ?>
    </div>

    <div class="stack">
        <?php if ($isManager && $target['status'] === 'pending'): ?>
            <section class="card">
                <div class="card-header"><h2>Review request</h2></div>
                <div class="card-body">
                    <p class="muted small">Check the evidence above before admitting this member. Approval activates their access to the directory, dues, welfare and elections.</p>
                    <form method="post" action="<?= url('groups/' . $group['slug'] . '/members/' . $target['id'] . '/approve') ?>" class="mb-2" data-submit-once>
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-primary btn-block"><?= icon('check', 16) ?> Approve membership</button>
                    </form>
                    <form method="post" action="<?= url('groups/' . $group['slug'] . '/members/' . $target['id'] . '/reject') ?>" class="stack-sm" data-submit-once>
                        <?= csrf_field() ?>
                        <div class="field">
                            <label for="rejection_reason">Rejection reason <span class="hint">(optional)</span></label>
                            <input type="text" id="rejection_reason" name="rejection_reason" maxlength="255" placeholder="e.g. Name not found in the 2010 register">
                        </div>
                        <button type="submit" class="btn btn-danger-ghost btn-block"><?= icon('x', 16) ?> Reject request</button>
                    </form>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($isManager && $target['status'] === 'active' && $target['role'] !== 'owner' && !$isSelf): ?>
            <section class="card">
                <div class="card-header"><h2>Manage membership</h2></div>
                <div class="card-body">
                    <?php if ($isOwner || $target['role'] !== 'admin'): ?>
                        <form method="post" action="<?= url('groups/' . $group['slug'] . '/members/' . $target['id'] . '/role') ?>" class="stack-sm">
                            <?= csrf_field() ?>
                            <div class="field">
                                <label for="role">Role</label>
                                <select id="role" name="role" class="select">
                                    <?php foreach (['admin' => 'Admin', 'exco' => 'Exco', 'member' => 'Member'] as $key => $label): ?>
                                        <?php if ($key === 'admin' && !$isOwner) { continue; } ?>
                                        <option value="<?= e($key) ?>" <?= $target['role'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field">
                                <label for="position_title">Position title <span class="hint">(optional)</span></label>
                                <input type="text" id="position_title" name="position_title" maxlength="80" value="<?= e($target['position_title']) ?>" placeholder="e.g. Treasurer">
                            </div>
                            <button type="submit" class="btn btn-outline btn-block">Save role</button>
                        </form>
                    <?php endif; ?>

                    <div class="divider"></div>
                    <form method="post" action="<?= url('groups/' . $group['slug'] . '/members/' . $target['id'] . '/remove') ?>" data-confirm="Remove <?= e($target['name']) ?> from the group?">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-danger-ghost btn-sm btn-block"><?= icon('trash', 15) ?> Remove from group</button>
                    </form>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($isSelf && $target['status'] === 'pending'): ?>
            <div class="callout info">
                <?= icon('clock', 17) ?>
                <div>Your request is awaiting review. A group admin will check your proof and approve or reject it.</div>
            </div>
        <?php elseif ($isSelf && $target['status'] === 'active'): ?>
            <div class="callout">
                <?= icon('check', 17) ?>
                <div>Your membership is verified. You can update your profile from the profile page.</div>
            </div>
        <?php endif; ?>

        <section class="card">
            <div class="card-header"><h2>Group</h2></div>
            <div class="card-body">
                <div class="flex" style="gap:10px">
                    <span class="group-dot" style="--dot: <?= e($group['cover_color'] ?: '#0b6e4f') ?>;width:38px;height:38px;font-size:14px;border-radius:10px"><?= e(mb_substr((string) $group['name'], 0, 1)) ?></span>
                    <div>
                        <div style="font-weight:600;font-size:14px"><?= e($group['name']) ?></div>
                        <div class="tiny muted"><?= e(App\Models\Group::scopeLabel($group['scope'] ?? 'class')) ?> · <?= e(App\Models\Group::descriptor($group)) ?></div>
                    </div>
                </div>
                <a href="<?= url('groups/' . $group['slug']) ?>" class="btn btn-outline btn-block mt-2">Open group</a>
            </div>
        </section>
    </div>
</div>
