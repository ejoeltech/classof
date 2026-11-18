<?php
$title = 'Group requests';
$statusTone = static fn (string $s): string => match ($s) {
    'approved' => 'success',
    'pending' => 'warn',
    'rejected' => 'danger',
    default => 'neutral',
};
?>
<div class="page-head">
    <div>
        <h1>Request a new group</h1>
        <p>New groups are verified before they appear. To join an existing group, use Explore.</p>
    </div>
    <div class="page-head-actions">
        <a href="<?= url('explore?for=you') ?>" class="btn btn-primary"><?= icon('search', 15) ?> Find my groups</a>
        <a href="<?= url('profile') ?>" class="btn btn-ghost">Back to profile</a>
    </div>
</div>

<div class="split-main">
    <div class="stack">
        <form method="post" action="<?= url('profile/group-requests') ?>" class="card" data-submit-once>
            <?= csrf_field() ?>
            <div class="card-body">
                <?php if ($institutions === []): ?>
                    <div class="callout info">
                        <?= icon('info', 17) ?>
                        <div>No verified institutions are available yet. <a href="<?= url('profile/institutions/request') ?>">Request your institution</a> first.</div>
                    </div>
                <?php else: ?>
                    <div class="form-grid">
                        <div class="field span-2">
                            <label for="institution_id">Institution</label>
                            <select id="institution_id" name="institution_id" class="select" required data-institution-select data-faculty-url="<?= url('catalogue/faculties') ?>">
                                <option value="">Choose verified institution…</option>
                                <?php foreach ($institutions as $institution): ?>
                                    <option value="<?= (int) $institution['id'] ?>" <?= (int) old('institution_id') === (int) $institution['id'] ? 'selected' : '' ?>>
                                        <?= e($institution['name']) ?> — <?= e(App\Models\Institution::label($institution)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="field span-2">
                            <label for="name">Group name</label>
                            <input type="text" id="name" name="name" value="<?= e(old('name')) ?>" required maxlength="160"
                                   data-group-name
                                   data-name-placeholder-institution="e.g. University of Benin Alumni Association"
                                   data-name-placeholder-class="e.g. University of Benin — Class of 2004"
                                   data-name-placeholder-department="e.g. Department of Electrical Engineering, UNIBEN — Alumni"
                                   data-name-placeholder-faculty="e.g. Faculty of Engineering, UNIBEN — Alumni"
                                   data-name-placeholder-association="e.g. UNIBEN Old Students Association"
                                   data-name-placeholder-other="e.g. Alumni group name"
                                   placeholder="e.g. University of Benin — Class of 2004">
                        </div>

                        <div class="field span-2">
                            <label for="scope">What does the group represent?</label>
                            <select id="scope" name="scope" class="select" data-scope-select>
                                <?php foreach ($scopes as $key => $label): ?>
                                    <option value="<?= e($key) ?>" <?= old('scope', 'class') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="field">
                            <label for="type">Institution type</label>
                            <select id="type" name="type" class="select">
                                <?php foreach ($types as $key => $label): ?>
                                    <option value="<?= e($key) ?>" <?= old('type', 'university') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="field" data-scope-for="class department faculty">
                            <label for="faculty_id">Faculty</label>
                            <select id="faculty_id" name="faculty_id" class="select" data-faculty-select data-department-url="<?= url('catalogue/departments') ?>" data-placeholder="No faculty selected" data-selected="<?= e(old('faculty_id')) ?>">
                                <option value="">No faculty selected</option>
                            </select>
                            <?php if (isset($errors['faculty_id'])): ?><span class="error-text"><?= e($errors['faculty_id']) ?></span><?php endif; ?>
                        </div>

                        <div class="field" data-scope-for="class department">
                            <label for="department_id">Department</label>
                            <select id="department_id" name="department_id" class="select" data-department-select data-placeholder="No department selected" data-selected="<?= e(old('department_id')) ?>">
                                <option value="">No department selected</option>
                            </select>
                            <?php if (isset($errors['department_id'])): ?><span class="error-text"><?= e($errors['department_id']) ?></span><?php endif; ?>
                        </div>

                        <div class="field" data-scope-for="class">
                            <label for="graduation_year">Graduation year</label>
                            <input type="number" id="graduation_year" name="graduation_year" value="<?= e(old('graduation_year')) ?>" min="1900" max="<?= date('Y') + 10 ?>" data-required-scope="class">
                        </div>

                        <div class="field span-2">
                            <label for="description">Why does this group need to exist?</label>
                            <textarea id="description" name="description" class="textarea" style="min-height:100px" placeholder="Explain the affiliation and why an existing group does not cover it."><?= e(old('description')) ?></textarea>
                        </div>
                    </div>

                    <div class="callout mt-2">
                        <?= icon('shield', 17) ?>
                        <div>A platform admin verifies the institution and the affiliations before publishing. Rejected requests keep a reason and do not reserve a group name.</div>
                    </div>

                    <div class="form-actions mt-2">
                        <button type="submit" class="btn btn-primary">Submit group request</button>
                    </div>
                <?php endif; ?>
            </div>
        </form>

        <section class="card">
            <div class="card-header"><h2>My requests</h2><span class="badge badge-neutral"><?= count($requests) ?></span></div>
            <div class="card-body">
                <?php if ($requests === []): ?>
                    <p class="muted small">No group requests submitted.</p>
                <?php else: ?>
                    <div class="list">
                        <?php foreach ($requests as $request): ?>
                            <div class="list-item">
                                <div class="list-item-body">
                                    <div class="list-item-title">
                                        <?= e($request['name']) ?>
                                        <span class="badge badge-<?= $statusTone($request['status']) ?>"><?= e(ucfirst($request['status'])) ?></span>
                                    </div>
                                    <div class="list-item-meta">
                                        <?= e($request['institution_name']) ?>
                                        <span class="dot-sep">·</span>
                                        <span><?= e(App\Models\Group::scopeLabel($request['scope'])) ?></span>
                                        <?php if (!empty($request['graduation_year'])): ?><span class="dot-sep">·</span><span><?= e($request['graduation_year']) ?></span><?php endif; ?>
                                    </div>
                                    <?php if ($request['status'] === 'rejected' && !empty($request['rejection_reason'])): ?>
                                        <div class="tiny" style="color:var(--danger);margin-top:4px">Reason: <?= e($request['rejection_reason']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <?php if ($request['status'] === 'approved' && !empty($request['created_group_slug'])): ?>
                                    <a href="<?= url('groups/' . $request['created_group_slug']) ?>" class="btn btn-outline btn-sm">Open group</a>
                                <?php else: ?>
                                    <span class="tiny muted"><?= e(time_ago($request['created_at'])) ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <div class="stack">
        <section class="card">
            <div class="card-header"><h2>How requests work</h2></div>
            <div class="card-body">
                <ol class="stack-sm" style="font-size:13.5px;padding-left:18px;color:var(--text)">
                    <li>Pick a verified institution.</li>
                    <li>Describe the exact affiliation and why no existing group covers it.</li>
                    <li>An admin checks for duplicates and verifies the claim.</li>
                    <li>If approved, you become the owner and the group becomes discoverable.</li>
                </ol>
            </div>
        </section>

        <div class="callout info">
            <?= icon('info', 17) ?>
            <div>Already know the group? <a href="<?= url('explore') ?>">Search Explore</a> and request to join. This form is only for groups that do not exist.</div>
        </div>
    </div>
</div>
