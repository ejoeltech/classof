<?php
$title = 'Open a welfare case';
require BASE_PATH . '/app/Views/partials/group-header.php';
?>
<div class="page-head">
    <div>
        <h1>Open a welfare case</h1>
        <p>Describe the need clearly so classmates know how their support is used.</p>
    </div>
    <a href="<?= url('groups/' . $group['slug'] . '/welfare') ?>" class="btn btn-ghost">Cancel</a>
</div>

<form method="post" action="<?= url('groups/' . $group['slug'] . '/welfare') ?>" class="card" data-submit-once>
    <?= csrf_field() ?>
    <div class="card-body">
        <div class="form-grid">
            <div class="field span-2">
                <label for="title">Case title</label>
                <input type="text" id="title" name="title" required placeholder="e.g. Medical support for Kelechi Eze" value="<?= e(old('title')) ?>" class="<?= isset($errors['title']) ? 'is-invalid' : '' ?>">
                <?php if (isset($errors['title'])): ?><span class="error-text"><?= e($errors['title']) ?></span><?php endif; ?>
            </div>

            <div class="field">
                <label for="category">Category</label>
                <select id="category" name="category" class="select">
                    <?php foreach ($categories as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= old('category') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($isManager && $members !== []): ?>
                <div class="field">
                    <label for="beneficiary_id">Beneficiary</label>
                    <select id="beneficiary_id" name="beneficiary_id" class="select">
                        <?php foreach ($members as $member): ?>
                            <option value="<?= (int) $member['user_id'] ?>" <?= (int) old('beneficiary_id') === (int) $member['user_id'] ? 'selected' : '' ?>>
                                <?= e($member['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="hint">Who is this case for?</span>
                </div>
            <?php endif; ?>

            <div class="field">
                <label for="target_amount">Target amount (<?= e($group['currency']) ?>) <span class="hint">(optional)</span></label>
                <input type="number" id="target_amount" name="target_amount" min="1" step="0.01" placeholder="e.g. 500000" value="<?= e(old('target_amount')) ?>">
            </div>

            <?php if (!$isManager): ?>
                <div class="field span-2">
                    <div class="callout info">
                        <?= icon('info', 17) ?>
                        <div>You are opening this case for yourself. A group admin will review and may adjust it.</div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="field span-2">
                <label for="description">Description</label>
                <textarea id="description" name="description" class="textarea" required style="min-height:150px" placeholder="Explain the situation, what has been verified, and what the funds will cover…"><?= e(old('description')) ?></textarea>
                <?php if (isset($errors['description'])): ?><span class="error-text"><?= e($errors['description']) ?></span><?php endif; ?>
            </div>

            <div class="span-2">
                <label class="checkbox">
                    <input type="checkbox" name="is_anonymous" value="1">
                    <span>Hide the beneficiary's name from members (admins still see it)</span>
                </label>
            </div>
        </div>
    </div>
    <div class="card-footer between">
        <span class="muted small">Members will be notified on the welfare page.</span>
        <button type="submit" class="btn btn-primary">Open case</button>
    </div>
</form>
