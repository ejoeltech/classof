<?php
$title = 'Join ' . $group['name'];
$joinProofTypes = array_intersect_key($proofTypes, array_flip(App\Models\Membership::JOIN_PROOF_TYPES));
?>
<div style="max-width:640px;margin:24px auto">
    <div class="card">
        <div class="public-group-cover" style="--cover: <?= e($group['cover_color'] ?: '#0b6e4f') ?>;height:100px;border-radius:16px 16px 0 0"></div>
        <div class="card-body" style="padding:0 28px 28px">
            <div class="public-group-logo" style="width:70px;height:70px;font-size:30px;margin-top:-35px"><?= e(mb_substr((string) $group['name'], 0, 1)) ?></div>
            <h1 style="font-size:22px;margin:14px 0 4px"><?= e($group['name']) ?></h1>
            <p class="muted small">
                <?= e(App\Models\Group::scopeLabel($group['scope'] ?? 'class')) ?>
                · <?= e(App\Models\Group::descriptor($group)) ?>
                · <?= (int) $memberCount ?> members
            </p>

            <?php if (!empty($group['description'])): ?>
                <p class="prose mt-2"><?= e(str_limit($group['description'], 400)) ?></p>
            <?php endif; ?>

            <?php if ($membership !== null && $membership['status'] === 'rejected'): ?>
                <div class="alert alert-error mt-2">
                    <span class="alert-icon"><?= icon('alert', 18) ?></span>
                    <div class="alert-body">
                        <strong>Your previous request was rejected.</strong>
                        <?php if (!empty($membership['rejection_reason'])): ?>
                            <div class="small">Reason: <?= e($membership['rejection_reason']) ?></div>
                        <?php endif; ?>
                        <div class="small">You can submit fresh proof below.</div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="callout info mt-2">
                <?= icon('shield', 17) ?>
                <div>
                    <strong>Proof of affiliation is required.</strong>
                    Every join request is reviewed by a group admin. Submit an ID, certificate, transcript, admission letter or similar evidence.
                </div>
            </div>

            <form method="post" action="<?= url('join/' . $group['slug']) ?>" enctype="multipart/form-data" class="mt-2" data-submit-once>
                <?= csrf_field() ?>

                <div class="stack-sm">
                    <div class="field">
                        <label for="proof_type">Proof type</label>
                        <select id="proof_type" name="proof_type" class="select <?= isset($errors['proof_type']) ? 'is-invalid' : '' ?>" required>
                            <option value="">Choose evidence…</option>
                            <?php foreach ($joinProofTypes as $key => $label): ?>
                                <option value="<?= e($key) ?>" <?= old('proof_type') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['proof_type'])): ?><span class="error-text"><?= e($errors['proof_type']) ?></span><?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="proof_reference">Reference / identifier</label>
                        <input type="text" id="proof_reference" name="proof_reference" required value="<?= e(old('proof_reference')) ?>" placeholder="e.g. KC/2010/0142, matric number, certificate number, or year of graduation" class="<?= isset($errors['proof_reference']) ? 'is-invalid' : '' ?>">
                        <span class="hint">This helps the admin verify you against school records.</span>
                        <?php if (isset($errors['proof_reference'])): ?><span class="error-text"><?= e($errors['proof_reference']) ?></span><?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="proof_file">Supporting document <span class="hint">(optional)</span></label>
                        <input type="file" id="proof_file" name="proof_file" accept="image/jpeg,image/png,image/webp,application/pdf">
                        <span class="hint">JPG, PNG, WebP or PDF. Maximum 5MB. Only you and group admins can view it.</span>
                    </div>

                    <div class="field">
                        <label for="proof_note">Anything else the reviewer should know <span class="hint">(optional)</span></label>
                        <textarea id="proof_note" name="proof_note" class="textarea" style="min-height:80px" placeholder="e.g. I was in Blue House and graduated in 2010…"><?= e(old('proof_note')) ?></textarea>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg btn-block mt-2">
                    <?= icon('shield', 17) ?> Submit request for approval
                </button>
            </form>
        </div>
    </div>
</div>
