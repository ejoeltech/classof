<?php $title = 'Request an institution'; ?>
<div class="page-head">
    <div>
        <h1>Request an institution</h1>
        <p>If your school is missing, submit it for review. An admin must verify it before it can anchor groups or suggestions.</p>
    </div>
    <a href="<?= url('profile/education') ?>" class="btn btn-ghost">Back to education</a>
</div>

<div class="split-main">
    <form method="post" action="<?= url('profile/institutions/request') ?>" class="card" data-submit-once>
        <?= csrf_field() ?>
        <div class="card-body">
            <div class="form-grid">
                <div class="field span-2">
                    <label for="name">Institution name</label>
                    <input type="text" id="name" name="name" value="<?= e(old('name')) ?>" required maxlength="200" placeholder="e.g. University of Benin">
                    <?php if (isset($errors['name'])): ?><span class="error-text"><?= e($errors['name']) ?></span><?php endif; ?>
                </div>
                <div class="field">
                    <label for="type">Institution type</label>
                    <select id="type" name="type" class="select">
                        <?php foreach ($types as $key => $label): ?>
                            <option value="<?= e($key) ?>" <?= old('type', 'university') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="ownership">Ownership</label>
                    <select id="ownership" name="ownership" class="select">
                        <?php foreach ($ownerships as $key => $label): ?>
                            <option value="<?= e($key) ?>" <?= old('ownership', 'private') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="state">State</label>
                    <input type="text" id="state" name="state" value="<?= e(old('state')) ?>" maxlength="80" placeholder="e.g. Edo">
                </div>
            </div>
            <div class="callout mt-2">
                <?= icon('info', 17) ?>
                <div>You can submit up to three institution requests per day. Approved institutions become selectable for everyone. Rejected requests stay visible here with no ability to edit.</div>
            </div>
        </div>
        <div class="card-footer between">
            <span class="muted small">Review is handled by a platform admin.</span>
            <button type="submit" class="btn btn-primary">Submit for review</button>
        </div>
    </form>

    <div class="stack">
        <section class="card">
            <div class="card-header"><h2>My submissions</h2><span class="badge badge-neutral"><?= count($pending) ?></span></div>
            <div class="card-body">
                <?php if ($pending === []): ?>
                    <p class="muted small">No institution requests submitted.</p>
                <?php else: ?>
                    <div class="list">
                        <?php foreach ($pending as $request): ?>
                            <div class="list-item">
                                <div class="list-item-body">
                                    <div class="list-item-title" style="font-size:13.5px"><?= e($request['name']) ?></div>
                                    <div class="list-item-meta"><?= e(App\Models\Institution::label($request)) ?> · <?= e(time_ago($request['created_at'])) ?></div>
                                </div>
                                <span class="badge badge-<?= $request['status'] === 'pending' ? 'warn' : 'danger' ?>"><?= e(ucfirst($request['status'])) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>
