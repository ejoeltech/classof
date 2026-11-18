<?php
$title = 'Education records';
$statusTone = static fn (string $s): string => match ($s) {
    'verified' => 'success',
    'pending', 'unverified' => 'warn',
    'rejected' => 'danger',
    default => 'neutral',
};
$statusLabel = static fn (string $s): string => match ($s) {
    'verified' => 'Confirmed',
    'unverified' => 'Not confirmed',
    default => ucfirst($s),
};
?>
<div class="page-head">
    <div>
        <h1>Education records</h1>
        <p>These records drive group suggestions. They never replace proof and admin approval.</p>
    </div>
    <div class="page-head-actions">
        <a href="<?= url('profile/institutions/request') ?>" class="btn btn-outline"><?= icon('plus', 15) ?> Request an institution</a>
        <a href="<?= url('profile') ?>" class="btn btn-ghost">Back to profile</a>
    </div>
</div>

<div class="split-main">
    <div class="stack">
        <section class="card">
            <div class="card-header"><h2>Add an education record</h2></div>
            <div class="card-body">
                <form method="post" action="<?= url('profile/education') ?>" class="form-grid" data-submit-once>
                    <?= csrf_field() ?>
                    <div class="field span-2">
                        <label for="institution_id">Institution</label>
                        <select id="institution_id" name="institution_id" class="select <?= isset($errors['institution_id']) ? 'is-invalid' : '' ?>" required data-institution-select data-faculty-url="<?= url('catalogue/faculties') ?>">
                            <option value="">Choose institution…</option>
                            <?php
                            $verified = array_values(array_filter($institutions, static fn (array $i): bool => $i['status'] === 'verified'));
                            $ownPending = array_values(array_filter($institutions, static fn (array $i): bool => $i['status'] !== 'verified'));
                            ?>
                            <?php if ($verified !== []): ?>
                                <optgroup label="Verified institutions">
                                    <?php foreach ($verified as $institution): ?>
                                        <option value="<?= (int) $institution['id'] ?>" <?= (int) old('institution_id') === (int) $institution['id'] ? 'selected' : '' ?>>
                                            <?= e($institution['name']) ?> — <?= e(App\Models\Institution::label($institution)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
                            <?php if ($ownPending !== []): ?>
                                <optgroup label="Your pending submissions">
                                    <?php foreach ($ownPending as $institution): ?>
                                        <option value="<?= (int) $institution['id'] ?>" <?= (int) old('institution_id') === (int) $institution['id'] ? 'selected' : '' ?>>
                                            <?= e($institution['name']) ?> — pending review
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
                        </select>
                        <?php if (isset($errors['institution_id'])): ?><span class="error-text"><?= e($errors['institution_id']) ?></span><?php endif; ?>
                        <span class="hint">Can't find it? <a href="<?= url('profile/institutions/request') ?>">Request the institution</a>.</span>
                    </div>

                    <div class="field">
                        <label for="programme">Programme / course</label>
                        <input type="text" id="programme" name="programme" value="<?= e(old('programme')) ?>" maxlength="160" list="programme-list" autocomplete="off" placeholder="e.g. Electrical/Electronic Engineering">
                    </div>
                    <div class="field">
                        <label for="graduation_year">Year you graduated / left</label>
                        <input type="number" id="graduation_year" name="graduation_year" value="<?= e(old('graduation_year')) ?>" min="1900" max="<?= date('Y') + 10 ?>" required class="<?= isset($errors['graduation_year']) ? 'is-invalid' : '' ?>">
                        <?php if (isset($errors['graduation_year'])): ?><span class="error-text"><?= e($errors['graduation_year']) ?></span><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="faculty_id">Faculty <span class="hint">(optional)</span></label>
                        <select id="faculty_id" name="faculty_id" class="select" data-faculty-select data-department-url="<?= url('catalogue/departments') ?>" data-placeholder="No faculty selected" data-selected="<?= e(old('faculty_id')) ?>">
                            <option value="">No faculty selected</option>
                        </select>
                        <?php if (isset($errors['faculty_id'])): ?><span class="error-text"><?= e($errors['faculty_id']) ?></span><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="department_id">Department <span class="hint">(optional)</span></label>
                        <select id="department_id" name="department_id" class="select" data-department-select data-placeholder="No department selected" data-selected="<?= e(old('department_id')) ?>">
                            <option value="">No department selected</option>
                        </select>
                        <?php if (isset($errors['department_id'])): ?><span class="error-text"><?= e($errors['department_id']) ?></span><?php endif; ?>
                    </div>

                    <datalist id="programme-list">
                        <?php foreach ($programmes as $programme): ?>
                            <option value="<?= e($programme['name']) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>

                    <div class="span-2 form-actions">
                        <button type="submit" class="btn btn-primary">Add record</button>
                    </div>
                </form>
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2>My records</h2><span class="badge badge-neutral"><?= count($records) ?></span></div>
            <div class="card-body">
                <?php if ($records === []): ?>
                    <p class="muted small">No education records yet. Add one above to see matched groups.</p>
                <?php else: ?>
                    <div class="list">
                        <?php foreach ($records as $record): ?>
                            <div class="list-item" style="align-items:flex-start">
                                <span class="stat-icon" style="width:36px;height:36px;border-radius:10px"><?= icon('grid', 17) ?></span>
                                <div class="list-item-body">
                                    <div class="list-item-title">
                                        <?= e($record['institution_name'] ?: 'Unknown institution') ?>
                                        <span class="badge badge-<?= $statusTone($record['status']) ?>"><?= e($statusLabel($record['status'])) ?></span>
                                    </div>
                                    <div class="list-item-meta">
                                        <?= e($record['programme'] ?: 'Programme not stated') ?>
                                        <span class="dot-sep">·</span>
                                        <span><?= e($record['graduation_year'] ?: 'Year not stated') ?></span>
                                        <?php if (!empty($record['faculty'])): ?><span class="dot-sep">·</span><span><?= e($record['faculty']) ?></span><?php endif; ?>
                                        <?php if (!empty($record['department'])): ?><span class="dot-sep">·</span><span><?= e($record['department']) ?></span><?php endif; ?>
                                    </div>
                                </div>
                                <form method="post" action="<?= url('profile/education/' . $record['id'] . '/delete') ?>" data-confirm="Remove this education record?">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="icon-btn" title="Remove record" style="color:var(--danger)"><?= icon('trash', 15) ?></button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <div class="stack">
        <section class="card">
            <div class="card-header"><h2>What matches in this release</h2></div>
            <div class="card-body">
                <p class="muted small">Release 1 matches verified institution-wide, class/set (year) and institution-level association groups.</p>
                <div class="callout info">
                    <?= icon('info', 17) ?>
                    <div>Faculty, department and combined unit groups are not matched yet. They will appear once the unit catalogue ships. Joining still requires proof and admin approval.</div>
                </div>
                <?php if ($pendingInstitutions !== []): ?>
                    <div class="divider"></div>
                    <p class="tiny muted" style="text-transform:uppercase;letter-spacing:.08em;font-weight:700">Pending institutions</p>
                    <?php foreach ($pendingInstitutions as $pending): ?>
                        <div class="flex" style="justify-content:space-between;padding:6px 0">
                            <span class="small"><?= e($pending['name']) ?></span>
                            <span class="badge badge-warn">Pending</span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>
