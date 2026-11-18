<?php $title = 'Add your education'; ?>
<div style="max-width:720px;margin:0 auto">
    <div class="page-head">
        <div>
            <p class="eyebrow" style="color:var(--forest-700)">One more step</p>
            <h1>Find your alumni groups</h1>
            <p>Add where you schooled. Classof will show the institution, class, faculty, department and association groups that match.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="callout info mb-3">
                <?= icon('shield', 17) ?>
                <div>Your education record only drives suggestions. Every join request still needs proof and approval from a group admin.</div>
            </div>

            <?php if ($institutions === []): ?>
                <div class="empty-state">
                    <span class="empty-icon"><?= icon('search', 21) ?></span>
                    <h3>No institutions available yet</h3>
                    <p>Request your institution, then come back to add your record.</p>
                    <a href="<?= url('profile/institutions/request') ?>" class="btn btn-primary btn-sm mt-2">Request an institution</a>
                </div>
            <?php else: ?>
                <form method="post" action="<?= url('profile/education') ?>" class="form-grid" data-submit-once>
                    <?= csrf_field() ?>
                    <input type="hidden" name="onboarding" value="1">

                    <div class="field span-2">
                        <label for="institution_id">Institution</label>
                        <select id="institution_id" name="institution_id" class="select" required data-institution-select data-faculty-url="<?= url('catalogue/faculties') ?>">
                            <option value="">Choose your institution…</option>
                            <?php foreach ($institutions as $institution): ?>
                                <option value="<?= (int) $institution['id'] ?>" <?= (int) old('institution_id') === (int) $institution['id'] ? 'selected' : '' ?>>
                                    <?= e($institution['name']) ?> — <?= e(App\Models\Institution::label($institution)) ?><?= $institution['status'] !== 'verified' ? ' (pending review)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="hint">Not listed? <a href="<?= url('profile/institutions/request') ?>">Request your institution</a>.</span>
                    </div>

                    <div class="field">
                        <label for="programme">Programme / course</label>
                        <input type="text" id="programme" name="programme" value="<?= e(old('programme')) ?>" maxlength="160" list="programme-list" autocomplete="off" placeholder="e.g. Electrical/Electronic Engineering">
                    </div>
                    <div class="field">
                        <label for="graduation_year">Year you graduated / left</label>
                        <input type="number" id="graduation_year" name="graduation_year" value="<?= e(old('graduation_year')) ?>" min="1900" max="<?= date('Y') + 10 ?>" required>
                    </div>
                    <div class="field">
                        <label for="faculty_id">Faculty <span class="hint">(optional)</span></label>
                        <select id="faculty_id" name="faculty_id" class="select" data-faculty-select data-department-url="<?= url('catalogue/departments') ?>" data-placeholder="No faculty selected" data-selected="<?= e(old('faculty_id')) ?>">
                            <option value="">No faculty selected</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="department_id">Department <span class="hint">(optional)</span></label>
                        <select id="department_id" name="department_id" class="select" data-department-select data-placeholder="No department selected" data-selected="<?= e(old('department_id')) ?>">
                            <option value="">No department selected</option>
                        </select>
                    </div>

                    <datalist id="programme-list">
                        <?php foreach ($programmes as $programme): ?>
                            <option value="<?= e($programme['name']) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>

                    <div class="span-2 form-actions">
                        <button type="submit" class="btn btn-primary">Add education &amp; find groups</button>
                        <span class="muted small">You can add more institutions later from your profile.</span>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        <div class="card-footer between">
            <span class="muted small">Matching is advisory. Admin approval still controls access.</span>
            <form method="post" action="<?= url('onboarding/skip') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-ghost btn-sm">Skip for now</button>
            </form>
        </div>
    </div>
</div>
