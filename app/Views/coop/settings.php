<?php
$title = 'Co-op settings';
$membership = $groupMembership ?? null;
require BASE_PATH . '/app/Views/partials/group-header.php';
?>
<div class="page-head">
    <div>
        <h1>Co-op settings</h1>
        <p>Rules that apply to shares, savings, loans, fines and dividends.</p>
    </div>
    <a href="<?= url('groups/' . $group['slug'] . '/coop') ?>" class="btn btn-ghost">Back to co-op</a>
</div>

<form method="post" action="<?= url('groups/' . $group['slug'] . '/coop/settings') ?>" class="card" data-submit-once>
    <?= csrf_field() ?>
    <div class="card-body">
        <div class="form-grid">
            <div class="field span-2">
                <label for="name">Cooperative name</label>
                <input type="text" id="name" name="name" required value="<?= e(old('name', $coop['name'])) ?>">
            </div>
            <div class="field">
                <label for="registration_no">Registration number</label>
                <input type="text" id="registration_no" name="registration_no" value="<?= e(old('registration_no', $coop['registration_no'])) ?>">
            </div>
            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status" class="select">
                    <option value="active" <?= old('status', $coop['status']) === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="suspended" <?= old('status', $coop['status']) === 'suspended' ? 'selected' : '' ?>>Suspended — no new activity</option>
                </select>
            </div>
            <div class="field">
                <label for="share_value">Share value (<?= e($group['currency']) ?>)</label>
                <input type="number" id="share_value" name="share_value" min="1" step="0.01" required value="<?= e(old('share_value', $coop['share_value'])) ?>">
            </div>
            <div class="field">
                <label for="monthly_savings">Recommended monthly savings</label>
                <input type="number" id="monthly_savings" name="monthly_savings" min="0" step="0.01" required value="<?= e(old('monthly_savings', $coop['monthly_savings'])) ?>">
            </div>
            <div class="field">
                <label for="loan_interest_rate">Loan interest rate (% p.a.)</label>
                <input type="number" id="loan_interest_rate" name="loan_interest_rate" min="0" step="0.01" required value="<?= e(old('loan_interest_rate', $coop['loan_interest_rate'])) ?>">
            </div>
            <div class="field">
                <label for="loan_duration_months">Default duration (months)</label>
                <input type="number" id="loan_duration_months" name="loan_duration_months" min="1" required value="<?= e(old('loan_duration_months', $coop['loan_duration_months'])) ?>">
            </div>
            <div class="field">
                <label for="max_loan_multiple">Maximum loan multiple of savings</label>
                <input type="number" id="max_loan_multiple" name="max_loan_multiple" min="0.1" step="0.1" required value="<?= e(old('max_loan_multiple', $coop['max_loan_multiple'])) ?>">
            </div>
            <div class="field">
                <label for="guarantors_required">Guarantors required</label>
                <input type="number" id="guarantors_required" name="guarantors_required" min="0" max="10" required value="<?= e(old('guarantors_required', $coop['guarantors_required'])) ?>">
            </div>
            <div class="field">
                <label for="fine_absent">Absence fine</label>
                <input type="number" id="fine_absent" name="fine_absent" min="0" step="0.01" required value="<?= e(old('fine_absent', $coop['fine_absent'])) ?>">
            </div>
            <div class="field">
                <label for="meeting_frequency">Meeting frequency</label>
                <input type="text" id="meeting_frequency" name="meeting_frequency" value="<?= e(old('meeting_frequency', $coop['meeting_frequency'])) ?>">
            </div>
        </div>

        <div class="callout mt-2">
            <?= icon('info', 17) ?>
            <div>Changing the share value does not reprice existing shares. It affects future share purchases and dividend calculations based on current values.</div>
        </div>
    </div>
    <div class="card-footer between">
        <span class="muted small">Only co-op officers and group admins can change these rules.</span>
        <button type="submit" class="btn btn-primary">Save co-op settings</button>
    </div>
</form>
