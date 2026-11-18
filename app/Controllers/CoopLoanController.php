<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\CoopLoan;
use App\Models\CoopLoanGuarantor;
use App\Models\CoopLoanInstallment;
use App\Models\CoopLoanProduct;
use App\Models\CoopMember;
use App\Models\CoopTransaction;
use App\Models\Cooperative;
use App\Services\CoopService;

final class CoopLoanController extends Controller
{
    public function index(string $slug): void
    {
        [$group, $coop, $groupMembership, $coopMember] = $this->requireCoop($slug);
        $isOfficer = Cooperative::canManage($groupMembership, $coopMember);
        $isMember = $coopMember !== null && $coopMember['status'] === 'active';

        if (!$isOfficer && !$isMember) {
            $this->flashError('Only co-op members can view the loan book.');
            $this->redirect('groups/' . $slug . '/coop');
        }

        $filters = [
            'status' => (string) Request::input('status', ''),
            'search' => trim((string) Request::input('q', '')),
        ];

        $loans = $isOfficer
            ? CoopLoan::forCoop((int) $coop['id'], array_filter($filters))
            : CoopLoan::forMember((int) $coopMember['id']);

        $activeLoan = $isMember ? CoopLoan::activeForMember((int) $coopMember['id']) : null;
        $savings = $isMember ? CoopMember::savingsBalance((int) $coop['id'], (int) $coopMember['id']) : 0;

        $this->view('coop/loans/index', [
            'group' => $group,
            'coop' => $coop,
            'groupMembership' => $groupMembership,
            'coopMember' => $coopMember,
            'isOfficer' => $isOfficer,
            'isMember' => $isMember,
            'loans' => $loans,
            'filters' => $filters,
            'products' => CoopLoanProduct::active((int) $coop['id']),
            'members' => $isMember ? array_values(array_filter(
                CoopMember::active((int) $coop['id']),
                static fn (array $m): bool => (int) $m['id'] !== (int) $coopMember['id']
            )) : [],
            'activeLoan' => $activeLoan,
            'savings' => $savings,
            'loanLimit' => $savings * (float) $coop['max_loan_multiple'],
            'statuses' => CoopLoan::STATUSES,
        ]);
    }

    public function store(string $slug): void
    {
        [$group, $coop, , $coopMember] = $this->requireCoopMember($slug);

        $data = $this->validate([
            'principal' => 'required|numeric|gt:0',
            'product_id' => 'integer',
            'duration_months' => 'integer|gte:1',
            'purpose' => 'max:255',
        ]);

        $result = CoopService::applyForLoan($coop, $coopMember, [
            'principal' => $data['principal'],
            'product_id' => $data['product_id'],
            'duration_months' => $data['duration_months'],
            'purpose' => $data['purpose'],
            'guarantors' => (array) Request::input('guarantors', []),
        ]);

        if (!$result['ok']) {
            $this->flashError($result['error'] ?? 'The loan could not be submitted.');
            $this->redirect('groups/' . $slug . '/coop/loans');
        }

        $this->audit((int) $group['id'], 'coop.loan_applied', 'Applied for a co-op loan', 'coop_loan', (int) $result['loan_id']);
        $this->flashSuccess('Loan application submitted. Your guarantors and a loan officer will be notified.');
        $this->redirect('groups/' . $slug . '/coop/loans/' . $result['loan_id']);
    }

    public function show(string $slug, string $id): void
    {
        [$group, $coop, $groupMembership, $coopMember] = $this->requireCoop($slug);
        $loan = $this->findLoan((int) $coop['id'], (int) $id);
        $isOfficer = Cooperative::canManage($groupMembership, $coopMember);
        $isOwner = $coopMember !== null && (int) $loan['member_id'] === (int) $coopMember['id'];
        $guarantors = CoopLoanGuarantor::forLoan((int) $loan['id']);
        $myGuarantee = null;

        if ($coopMember !== null) {
            foreach ($guarantors as $guarantor) {
                if ((int) $guarantor['member_id'] === (int) $coopMember['id']) {
                    $myGuarantee = $guarantor;
                    break;
                }
            }
        }

        if (!$isOfficer && !$isOwner && $myGuarantee === null) {
            Response::abort(403, 'You cannot view this loan.');
        }

        $this->view('coop/loans/show', [
            'group' => $group,
            'coop' => $coop,
            'groupMembership' => $groupMembership,
            'coopMember' => $coopMember,
            'isOfficer' => $isOfficer,
            'isOwner' => $isOwner,
            'loan' => $loan,
            'guarantors' => $guarantors,
            'myGuarantee' => $myGuarantee,
            'installments' => CoopLoanInstallment::forLoan((int) $loan['id']),
            'outstanding' => CoopLoan::outstanding($loan),
            'repaid' => CoopLoan::totalRepaid((int) $loan['id']),
            'progress' => CoopLoan::progress($loan),
            'overdue' => CoopLoanInstallment::overdueCount((int) $loan['id']),
            'transactions' => \App\Core\Database::fetchAll(
                'SELECT * FROM coop_transactions WHERE loan_id = ? ORDER BY occurred_at DESC, id DESC',
                [(int) $loan['id']]
            ),
        ]);
    }

    public function guarantee(string $slug, string $id): void
    {
        [$group, $coop, , $coopMember] = $this->requireCoopMember($slug);
        $loan = $this->findLoan((int) $coop['id'], (int) $id);

        $guarantor = \App\Core\Database::fetch(
            'SELECT * FROM coop_loan_guarantors WHERE loan_id = ? AND member_id = ? LIMIT 1',
            [(int) $loan['id'], (int) $coopMember['id']]
        );

        if ($guarantor === null || $guarantor['status'] !== 'pending') {
            $this->flashError('You do not have a pending guarantor request for this loan.');
            $this->redirect('groups/' . $slug . '/coop/loans/' . $loan['id']);
        }

        $action = Request::string('action') === 'decline' ? 'declined' : 'accepted';
        CoopLoanGuarantor::respond((int) $guarantor['id'], $action);
        $this->audit((int) $group['id'], 'coop.guarantee_' . $action, ucfirst($action) . ' a loan guarantee request', 'coop_loan', (int) $loan['id']);

        $this->flashSuccess($action === 'accepted'
            ? 'You have accepted to guarantee this loan.'
            : 'You have declined the guarantee request.');
        $this->redirect('groups/' . $slug . '/coop/loans/' . $loan['id']);
    }

    public function approve(string $slug, string $id): void
    {
        [$group, $coop] = $this->requireCoopOfficer($slug);
        $loan = $this->findLoan((int) $coop['id'], (int) $id);

        $result = CoopService::approveLoan($loan, (int) user()['id']);

        if (!$result['ok']) {
            $this->flashError($result['error'] ?? 'The loan could not be approved.');
            $this->redirect('groups/' . $slug . '/coop/loans/' . $loan['id']);
        }

        $this->audit((int) $group['id'], 'coop.loan_approved', 'Approved a co-op loan', 'coop_loan', (int) $loan['id']);
        $this->flashSuccess('Loan approved. Record the disbursement when the funds are released.');
        $this->redirect('groups/' . $slug . '/coop/loans/' . $loan['id']);
    }

    public function reject(string $slug, string $id): void
    {
        [$group, $coop] = $this->requireCoopOfficer($slug);
        $loan = $this->findLoan((int) $coop['id'], (int) $id);
        $reason = Request::string('rejection_reason');

        CoopService::rejectLoan($loan, (int) user()['id'], $reason !== '' ? mb_substr($reason, 0, 255) : null);
        $this->audit((int) $group['id'], 'coop.loan_rejected', 'Rejected a co-op loan', 'coop_loan', (int) $loan['id']);
        $this->flashSuccess('Loan application rejected.');
        $this->redirect('groups/' . $slug . '/coop/loans/' . $loan['id']);
    }

    public function disburse(string $slug, string $id): void
    {
        [$group, $coop] = $this->requireCoopOfficer($slug);
        $loan = $this->findLoan((int) $coop['id'], (int) $id);

        $data = $this->validate([
            'method' => 'required|in:cash,bank',
            'note' => 'max:255',
        ]);

        $result = CoopService::disburseLoan($loan, (int) user()['id'], (string) $data['method'], $data['note'] ?: null);

        if (!$result['ok']) {
            $this->flashError($result['error'] ?? 'The loan could not be disbursed.');
            $this->redirect('groups/' . $slug . '/coop/loans/' . $loan['id']);
        }

        $this->audit((int) $group['id'], 'coop.loan_disbursed', 'Disbursed a co-op loan', 'coop_loan', (int) $loan['id']);
        $this->flashSuccess('Loan disbursed and repayment schedule generated.');
        $this->redirect('groups/' . $slug . '/coop/loans/' . $loan['id']);
    }

    public function recordRepayment(string $slug, string $id): void
    {
        [$group, $coop] = $this->requireCoopOfficer($slug);
        $loan = $this->findLoan((int) $coop['id'], (int) $id);

        if (!in_array($loan['status'], ['disbursed', 'repaying'], true)) {
            $this->flashError('Repayments can only be recorded for a disbursed loan.');
            $this->redirect('groups/' . $slug . '/coop/loans/' . $loan['id']);
        }

        $data = $this->validate([
            'amount' => 'required|numeric|gt:0',
            'method' => 'required|in:cash,bank',
            'note' => 'max:255',
        ]);

        $applied = CoopService::recordRepayment(
            $loan,
            (float) $data['amount'],
            (string) $data['method'],
            (int) user()['id'],
            null,
            $data['note'] ?: 'Manual repayment'
        );

        $this->audit((int) $group['id'], 'coop.loan_repayment', 'Recorded a loan repayment of ' . $applied, 'coop_loan', (int) $loan['id']);
        $this->flashSuccess('Repayment of ' . money($applied, $group['currency']) . ' recorded.');
        $this->redirect('groups/' . $slug . '/coop/loans/' . $loan['id']);
    }

    public function products(string $slug): void
    {
        [$group, $coop, $groupMembership] = $this->requireCoopOfficer($slug);

        $this->view('coop/loans/products', [
            'group' => $group,
            'coop' => $coop,
            'groupMembership' => $groupMembership,
            'products' => CoopLoanProduct::forCoop((int) $coop['id']),
        ]);
    }

    public function storeProduct(string $slug): void
    {
        [$group, $coop] = $this->requireCoopOfficer($slug);

        $data = $this->validate([
            'name' => 'required|min:2|max:120',
            'description' => 'max:255',
            'interest_rate' => 'required|numeric|gte:0',
            'duration_months' => 'required|integer|gte:1',
            'min_amount' => 'numeric|gte:0',
            'max_amount' => 'numeric|gte:0',
            'max_multiple' => 'numeric|gte:0',
            'guarantors_required' => 'required|integer|gte:0',
            'status' => 'required|in:active,inactive',
        ]);

        $id = CoopLoanProduct::create([
            'cooperative_id' => (int) $coop['id'],
            'name' => $data['name'],
            'description' => $data['description'] ?: null,
            'interest_rate' => (float) $data['interest_rate'],
            'duration_months' => (int) $data['duration_months'],
            'min_amount' => (float) ($data['min_amount'] ?: 0),
            'max_amount' => ($data['max_amount'] ?? '') !== '' ? (float) $data['max_amount'] : null,
            'max_multiple' => ($data['max_multiple'] ?? '') !== '' ? (float) $data['max_multiple'] : null,
            'guarantors_required' => (int) $data['guarantors_required'],
            'status' => $data['status'],
        ]);

        $this->audit((int) $group['id'], 'coop.loan_product_created', 'Created loan product ' . $data['name'], 'coop_loan_product', $id);
        $this->flashSuccess('Loan product created.');
        $this->redirect('groups/' . $slug . '/coop/products');
    }

    public function toggleProduct(string $slug, string $id): void
    {
        [$group, $coop] = $this->requireCoopOfficer($slug);
        $product = $this->findProduct((int) $coop['id'], (int) $id);

        CoopLoanProduct::setStatus((int) $product['id'], $product['status'] === 'active' ? 'inactive' : 'active');
        $this->flashSuccess('Loan product updated.');
        $this->redirect('groups/' . $slug . '/coop/products');
    }

    public function deleteProduct(string $slug, string $id): void
    {
        [$group, $coop] = $this->requireCoopOfficer($slug);
        $product = $this->findProduct((int) $coop['id'], (int) $id);

        $used = (int) \App\Core\Database::value('SELECT COUNT(*) FROM coop_loans WHERE product_id = ?', [(int) $product['id']], 0);

        if ($used > 0) {
            CoopLoanProduct::setStatus((int) $product['id'], 'inactive');
            $this->flashSuccess('This product has loans attached, so it was deactivated instead of deleted.');
            $this->redirect('groups/' . $slug . '/coop/products');
        }

        CoopLoanProduct::delete((int) $product['id']);
        $this->audit((int) $group['id'], 'coop.loan_product_deleted', 'Deleted loan product ' . $product['name'], 'coop_loan_product', (int) $product['id']);
        $this->flashSuccess('Loan product deleted.');
        $this->redirect('groups/' . $slug . '/coop/products');
    }

    /** @return array<string, mixed> */
    private function findLoan(int $cooperativeId, int $loanId): array
    {
        $loan = CoopLoan::find($loanId);

        if ($loan === null || (int) $loan['cooperative_id'] !== $cooperativeId) {
            Response::abort(404, 'That co-op loan could not be found.');
        }

        return $loan;
    }

    /** @return array<string, mixed> */
    private function findProduct(int $cooperativeId, int $productId): array
    {
        $product = CoopLoanProduct::find($productId);

        if ($product === null || (int) $product['cooperative_id'] !== $cooperativeId) {
            Response::abort(404, 'That loan product could not be found.');
        }

        return $product;
    }
}
