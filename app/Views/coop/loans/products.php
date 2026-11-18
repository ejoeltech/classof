<?php
$title = 'Co-op loan products';
$membership = $groupMembership ?? null;
require BASE_PATH . '/app/Views/partials/group-header.php';
?>
<div class="page-head">
    <div>
        <h1>Loan products</h1>
        <p>Define the loan windows members can apply for.</p>
    </div>
    <a href="<?= url('groups/' . $group['slug'] . '/coop/loans') ?>" class="btn btn-ghost">Back to loans</a>
</div>

<div class="split-main">
    <div class="stack">
        <div class="card">
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Product</th><th>Rate</th><th>Duration</th><th>Guarantors</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
                    <tbody>
                    <?php if ($products === []): ?>
                        <tr><td colspan="6" class="empty-row">No loan products yet. Create one on the right.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <div class="table-person-meta">
                                    <strong><?= e($product['name']) ?></strong>
                                    <span><?= e($product['description'] ?: '—') ?></span>
                                    <span class="tiny muted">
                                        <?= money($product['min_amount'], $group['currency']) ?> –
                                        <?= $product['max_amount'] !== null ? money($product['max_amount'], $group['currency']) : 'no cap' ?>
                                        <?= $product['max_multiple'] !== null ? ' · ' . e($product['max_multiple']) . '× savings' : '' ?>
                                    </span>
                                </div>
                            </td>
                            <td><?= e($product['interest_rate']) ?>%</td>
                            <td><?= (int) $product['duration_months'] ?> mo</td>
                            <td><?= (int) $product['guarantors_required'] ?></td>
                            <td><span class="badge badge-<?= $product['status'] === 'active' ? 'success' : 'neutral' ?>"><?= e(ucfirst($product['status'])) ?></span></td>
                            <td>
                                <div class="table-actions">
                                    <form method="post" action="<?= url('groups/' . $group['slug'] . '/coop/products/' . $product['id'] . '/toggle') ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-ghost btn-sm"><?= $product['status'] === 'active' ? 'Deactivate' : 'Activate' ?></button>
                                    </form>
                                    <form method="post" action="<?= url('groups/' . $group['slug'] . '/coop/products/' . $product['id'] . '/delete') ?>" data-confirm="Delete this loan product?">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-danger-ghost btn-sm">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="stack">
        <section class="card">
            <div class="card-header"><h2>New loan product</h2></div>
            <div class="card-body">
                <form method="post" action="<?= url('groups/' . $group['slug'] . '/coop/products') ?>" class="stack-sm" data-submit-once>
                    <?= csrf_field() ?>
                    <div class="field">
                        <label for="name">Product name</label>
                        <input type="text" id="name" name="name" required placeholder="e.g. Emergency loan">
                    </div>
                    <div class="field">
                        <label for="description">Description</label>
                        <input type="text" id="description" name="description" maxlength="255" placeholder="Short description">
                    </div>
                    <div class="form-grid">
                        <div class="field">
                            <label for="interest_rate">Interest % p.a.</label>
                            <input type="number" id="interest_rate" name="interest_rate" min="0" step="0.01" required value="10">
                        </div>
                        <div class="field">
                            <label for="duration_months">Duration (months)</label>
                            <input type="number" id="duration_months" name="duration_months" min="1" required value="12">
                        </div>
                        <div class="field">
                            <label for="min_amount">Minimum amount</label>
                            <input type="number" id="min_amount" name="min_amount" min="0" step="0.01" value="0">
                        </div>
                        <div class="field">
                            <label for="max_amount">Maximum amount</label>
                            <input type="number" id="max_amount" name="max_amount" min="0" step="0.01" placeholder="No cap">
                        </div>
                        <div class="field">
                            <label for="max_multiple">Max multiple of savings</label>
                            <input type="number" id="max_multiple" name="max_multiple" min="0" step="0.1" placeholder="Inherit co-op default">
                        </div>
                        <div class="field">
                            <label for="guarantors_required">Guarantors required</label>
                            <input type="number" id="guarantors_required" name="guarantors_required" min="0" max="10" required value="2">
                        </div>
                    </div>
                    <div class="field">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Create product</button>
                </form>
            </div>
        </section>
    </div>
</div>
