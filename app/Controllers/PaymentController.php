<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Guard;
use App\Core\Request;
use App\Core\Response;
use App\Models\CoopFine;
use App\Models\CoopLoan;
use App\Models\CoopMember;
use App\Models\Cooperative;
use App\Models\DuesCycle;
use App\Models\Event;
use App\Models\Group;
use App\Models\Payment;
use App\Models\WelfareCase;
use App\Services\Payments\PaymentManager;
use App\Services\Payments\PaymentService;
use App\Services\Payments\PaystackGateway;
use App\Services\Payments\StripeGateway;

final class PaymentController extends Controller
{
    public function initiate(): void
    {
        $user = $this->currentUser();
        $purpose = Request::string('purpose');
        $groupId = Request::int('group_id');

        if (!array_key_exists($purpose, Payment::PURPOSES)) {
            $this->flashError('Choose a valid payment type.');
            $this->back();
        }

        $group = Group::find($groupId);

        if ($group === null) {
            Response::abort(404, 'That group does not exist.');
        }

        Guard::requireMember($groupId);

        $amount = 0.0;
        $related = [
            'dues_cycle_id' => null,
            'welfare_case_id' => null,
            'event_id' => null,
            'coop_loan_id' => null,
            'coop_fine_id' => null,
        ];
        $meta = [];

        if ($purpose === 'dues') {
            $cycle = DuesCycle::find(Request::int('dues_cycle_id'));

            if ($cycle === null || (int) $cycle['group_id'] !== $groupId) {
                $this->flashError('That dues cycle could not be found.');
                $this->back();
            }

            if ($cycle['status'] !== 'open') {
                $this->flashError('That dues cycle is closed.');
                $this->back();
            }

            if (DuesCycle::myPayment((int) $cycle['id'], (int) $user['id']) !== null) {
                $this->flashSuccess('You have already paid these dues.');
                $this->redirect('groups/' . $group['slug'] . '/dues/' . $cycle['id']);
            }

            $pending = \App\Core\Database::fetch(
                'SELECT * FROM payments WHERE dues_cycle_id = ? AND user_id = ? AND purpose = "dues" AND status = "pending" ORDER BY id DESC LIMIT 1',
                [(int) $cycle['id'], (int) $user['id']]
            );

            if ($pending !== null) {
                $this->flashSuccess('You already have a payment awaiting confirmation. Use the reference below or contact an admin.');
                $this->redirect('payments/' . $pending['id'] . '/instructions');
            }

            $amount = (float) $cycle['amount'];
            $related['dues_cycle_id'] = (int) $cycle['id'];
            $meta['title'] = $cycle['title'];
        } elseif ($purpose === 'welfare') {
            $case = WelfareCase::find(Request::int('welfare_case_id'));

            if ($case === null || (int) $case['group_id'] !== $groupId) {
                $this->flashError('That welfare case could not be found.');
                $this->back();
            }

            if ($case['status'] !== 'open') {
                $this->flashError('This welfare case is no longer accepting contributions.');
                $this->back();
            }

            $amount = Request::float('amount');

            if ($amount < 1) {
                $this->flashError('Enter a contribution amount of at least 1.');
                $this->back();
            }

            $related['welfare_case_id'] = (int) $case['id'];
            $meta['title'] = $case['title'];
            $meta['note'] = mb_substr(Request::string('note'), 0, 255);
        } elseif ($purpose === 'event') {
            $event = Event::find(Request::int('event_id'));

            if ($event === null || (int) $event['group_id'] !== $groupId) {
                $this->flashError('That event could not be found.');
                $this->back();
            }

            if (Payment::successFor('event', (int) $event['id'], (int) $user['id']) !== null) {
                $this->flashSuccess('You have already paid for this event.');
                $this->redirect('groups/' . $group['slug'] . '/events/' . $event['id']);
            }

            $amount = (float) $event['fee'];

            if ($amount <= 0) {
                $this->flashError('This event does not require payment.');
                $this->redirect('groups/' . $group['slug'] . '/events/' . $event['id']);
            }

            $myRsvp = Event::rsvpFor((int) $event['id'], (int) $user['id']);

            if (!empty($event['capacity'])
                && ($myRsvp['status'] ?? '') !== 'going'
                && Event::rsvpCounts((int) $event['id'])['going'] >= (int) $event['capacity']) {
                $this->flashError('This event has reached its capacity.');
                $this->redirect('groups/' . $group['slug'] . '/events/' . $event['id']);
            }

            $related['event_id'] = (int) $event['id'];
            $meta['title'] = $event['title'];
        } elseif (str_starts_with($purpose, 'coop_')) {
            $coop = Cooperative::forGroup($groupId);

            if ($coop === null || $coop['status'] !== 'active') {
                $this->flashError('The cooperative is not available for this group.');
                $this->back();
            }

            $coopMember = CoopMember::forUser((int) $coop['id'], (int) $user['id']);

            if ($coopMember === null || $coopMember['status'] !== 'active') {
                $this->flashError('You are not an active member of this cooperative.');
                $this->redirect('groups/' . $group['slug'] . '/coop');
            }

            if ($purpose === 'coop_savings') {
                $amount = Request::float('amount');

                if ($amount < 1) {
                    $this->flashError('Enter a savings amount of at least 1.');
                    $this->back();
                }

                $meta['title'] = 'Savings deposit';
            } elseif ($purpose === 'coop_shares') {
                $amount = Request::float('amount');
                $shareValue = (float) $coop['share_value'];

                if ($amount < $shareValue) {
                    $this->flashError('The minimum share purchase is ' . money($shareValue, $group['currency']) . '.');
                    $this->back();
                }

                $units = $amount / $shareValue;
                $nearest = round($units);

                if ($shareValue > 0 && abs($units - $nearest) > 0.01) {
                    $this->flashError('Share purchases must be a multiple of ' . money($shareValue, $group['currency']) . '.');
                    $this->back();
                }

                $meta['title'] = 'Share purchase (' . (int) $nearest . ' shares)';
            } elseif ($purpose === 'coop_loan') {
                $loan = CoopLoan::find(Request::int('coop_loan_id'));

                if ($loan === null || (int) $loan['member_id'] !== (int) $coopMember['id']) {
                    $this->flashError('That loan could not be found.');
                    $this->back();
                }

                if (!in_array($loan['status'], ['disbursed', 'repaying'], true)) {
                    $this->flashError('This loan is not open for repayment.');
                    $this->redirect('groups/' . $group['slug'] . '/coop/loans/' . $loan['id']);
                }

                $outstanding = CoopLoan::outstanding($loan);
                $amount = Request::float('amount');

                if ($amount < 1) {
                    $amount = $outstanding;
                }

                if ($amount > $outstanding) {
                    $amount = $outstanding;
                }

                if ($amount <= 0) {
                    $this->flashSuccess('This loan is fully repaid.');
                    $this->redirect('groups/' . $group['slug'] . '/coop/loans/' . $loan['id']);
                }

                $pending = \App\Core\Database::fetch(
                    'SELECT * FROM payments WHERE coop_loan_id = ? AND user_id = ? AND status = "pending" ORDER BY id DESC LIMIT 1',
                    [(int) $loan['id'], (int) $user['id']]
                );

                if ($pending !== null) {
                    $this->flashSuccess('You already have a repayment awaiting confirmation.');
                    $this->redirect('payments/' . $pending['id'] . '/instructions');
                }

                $related['coop_loan_id'] = (int) $loan['id'];
                $meta['title'] = 'Loan repayment';
            } elseif ($purpose === 'coop_fine') {
                $fine = CoopFine::find(Request::int('coop_fine_id'));

                if ($fine === null || (int) $fine['member_id'] !== (int) $coopMember['id']) {
                    $this->flashError('That fine could not be found.');
                    $this->back();
                }

                if ($fine['status'] !== 'unpaid') {
                    $this->flashSuccess('That fine is already settled.');
                    $this->redirect('groups/' . $group['slug'] . '/coop/fines');
                }

                $amount = (float) $fine['amount'];
                $related['coop_fine_id'] = (int) $fine['id'];
                $meta['title'] = 'Fine: ' . $fine['reason'];
            } else {
                $this->flashError('Unsupported co-op payment.');
                $this->back();
            }
        } else {
            $amount = Request::float('amount');

            if ($amount < 1) {
                $this->flashError('Enter a valid amount.');
                $this->back();
            }
        }

        $gateway = PaymentManager::current();

        $payment = Payment::create([
            'group_id' => $groupId,
            'user_id' => (int) $user['id'],
            'purpose' => $purpose,
            'dues_cycle_id' => $related['dues_cycle_id'],
            'welfare_case_id' => $related['welfare_case_id'],
            'event_id' => $related['event_id'],
            'coop_loan_id' => $related['coop_loan_id'],
            'coop_fine_id' => $related['coop_fine_id'],
            'gateway' => $gateway->slug(),
            'amount' => round($amount, 2),
            'currency' => $group['currency'],
            'payer_name' => $user['name'],
            'payer_email' => $user['email'],
            'meta' => $meta,
        ]);

        $callbackUrl = absolute_url('payments/' . $payment['id'] . '/callback');

        try {
            $result = $gateway->initialize($payment, $callbackUrl);
        } catch (\RuntimeException $e) {
            Payment::markFailed((int) $payment['id'], ['error' => $e->getMessage()]);
            $this->flashError($e->getMessage());
            $this->back();
        }

        if (!empty($result['redirect'])) {
            Response::redirect((string) $result['redirect']);
        }

        $this->flashError('The payment gateway did not return a checkout page.');
        $this->back();
    }

    public function instructions(string $id): void
    {
        $payment = $this->findPayment((int) $id);
        $this->authorizePayment($payment);

        if ($payment['status'] === 'success') {
            $this->flashSuccess('This payment is already confirmed.');
            $this->redirect($this->destination($payment));
        }

        $this->view('payments/instructions', [
            'payment' => $payment,
            'bank' => Group::bankDetails(Group::find((int) $payment['group_id'])),
        ]);
    }

    public function claim(string $id): void
    {
        $payment = $this->findPayment((int) $id);

        if ((int) $payment['user_id'] !== (int) user()['id']) {
            Response::abort(403, 'You can only update your own payment.');
        }

        if ($payment['gateway'] !== 'manual') {
            $this->flashError('This payment is handled by the gateway and updates automatically.');
            $this->redirect('payments/' . $payment['id'] . '/instructions');
        }

        if ($payment['status'] !== 'pending') {
            $this->flashError('This payment is no longer awaiting confirmation.');
            $this->redirect('payments/' . $payment['id'] . '/instructions');
        }

        $note = trim((string) Request::input('claim_note', ''));

        if (mb_strlen($note) > 255) {
            $this->flashError('Keep the transfer note under 255 characters.');
            $this->redirect('payments/' . $payment['id'] . '/instructions');
        }

        Payment::mergeMeta((int) $payment['id'], [
            'member_claimed_at' => date('Y-m-d H:i:s'),
            'member_claim_note' => $note !== '' ? $note : null,
        ]);

        $this->audit(
            (int) $payment['group_id'],
            'payment.claimed',
            'Member marked transfer as sent for ' . $payment['reference'],
            'payment',
            (int) $payment['id']
        );

        $this->flashSuccess('Thanks — the group treasurer will see that you have sent the transfer.');
        $this->redirect('payments/' . $payment['id'] . '/instructions');
    }

    public function callback(string $id): void
    {
        $payment = $this->findPayment((int) $id);
        $this->authorizePayment($payment);

        if ($payment['status'] === 'success') {
            $this->flashSuccess('This payment is already confirmed.');
            $this->redirect($this->destination($payment));
        }

        $gateway = PaymentManager::resolve((string) $payment['gateway']);

        try {
            $result = $gateway->verify($payment);
        } catch (\RuntimeException $e) {
            $this->flashError($e->getMessage());
            $this->redirect('payments/' . $payment['id'] . '/instructions');
        }

        if ($result['status'] === 'success') {
            $completed = PaymentService::complete($payment, $result['meta'] ?? [], $result['paid_at'] ?? null);

            $this->flashSuccess($completed
                ? 'Payment confirmed. Thank you!'
                : 'This payment was already confirmed.');

            $this->redirect($this->destination($payment));
        }

        if ($result['status'] === 'pending') {
            if ($gateway->slug() === 'manual') {
                $this->flashSuccess('Your payment is recorded and awaiting confirmation.');
                $this->redirect('payments/' . $payment['id'] . '/instructions');
            }

            $this->flashError('Your payment has not been confirmed yet. If you were charged, it will be verified automatically.');
            $this->redirect('payments/' . $payment['id'] . '/instructions');
        }

        Payment::markFailed((int) $payment['id'], $result['meta'] ?? []);
        $this->flashError('The payment was not successful. No money has been taken.');
        $this->redirect('payments/' . $payment['id'] . '/instructions');
    }

    public function confirm(string $id): void
    {
        $payment = $this->findPayment((int) $id);
        $this->authorizePaymentReviewer($payment);

        if ($payment['status'] === 'success') {
            $this->flashSuccess('That payment is already confirmed.');
            $this->back();
        }

        PaymentService::complete($payment, ['confirmed_by_admin' => (int) user()['id']]);
        Payment::confirmManual((int) $payment['id'], (int) user()['id']);
        $this->audit((int) $payment['group_id'], 'payment.confirmed', 'Confirmed ' . $payment['reference'], 'payment', (int) $payment['id']);

        $this->flashSuccess('Payment confirmed and recorded in the treasury.');
        $this->back();
    }

    public function reject(string $id): void
    {
        $payment = $this->findPayment((int) $id);
        $this->authorizePaymentReviewer($payment);

        if ($payment['status'] === 'success') {
            $this->flashError('Confirmed payments cannot be rejected.');
            $this->back();
        }

        Payment::markFailed((int) $payment['id'], ['rejected_by_admin' => (int) user()['id']]);
        Payment::confirmManual((int) $payment['id'], (int) user()['id']);
        $this->audit((int) $payment['group_id'], 'payment.rejected', 'Rejected ' . $payment['reference'], 'payment', (int) $payment['id']);

        $this->flashSuccess('Payment marked as failed.');
        $this->back();
    }

    public function paystackWebhook(): void
    {
        $payload = (string) file_get_contents('php://input');
        $signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? null;

        if (!PaystackGateway::verifySignature($payload, $signature)) {
            Response::json(['status' => 'invalid signature'], 401);
        }

        $event = json_decode($payload, true);

        if (is_array($event) && ($event['event'] ?? '') === 'charge.success') {
            $reference = PaystackGateway::referenceFromEvent($event);

            if ($reference !== null) {
                $payment = Payment::findByReference($reference);

                if ($payment !== null && $payment['status'] !== 'success') {
                    $result = (new PaystackGateway())->verify($payment);

                    if ($result['status'] === 'success') {
                        PaymentService::complete($payment, $result['meta'] ?? [], $result['paid_at'] ?? null);
                    }
                }
            }
        }

        Response::json(['status' => 'ok']);
    }

    public function stripeWebhook(): void
    {
        $payload = (string) file_get_contents('php://input');
        $signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? null;

        if (!StripeGateway::verifySignature($payload, $signature)) {
            Response::json(['status' => 'invalid signature'], 401);
        }

        $event = json_decode($payload, true);

        if (is_array($event) && ($event['type'] ?? '') === 'checkout.session.completed') {
            $reference = StripeGateway::referenceFromEvent($event);

            if ($reference !== null) {
                $payment = Payment::findByReference($reference);

                if ($payment !== null && $payment['status'] !== 'success') {
                    PaymentService::complete($payment, ['gateway' => 'stripe']);
                }
            }
        }

        Response::json(['status' => 'ok']);
    }

    /** @return array<string, mixed> */
    private function findPayment(int $id): array
    {
        $payment = Payment::find($id);

        if ($payment === null) {
            Response::abort(404, 'That payment could not be found.');
        }

        return $payment;
    }

    /** @param array<string, mixed> $payment */
    private function authorizePayment(array $payment): void
    {
        if ((int) $payment['user_id'] === (int) user()['id']) {
            return;
        }

        Guard::requireManager((int) $payment['group_id']);
    }

    /** @param array<string, mixed> $payment */
    private function authorizePaymentReviewer(array $payment): void
    {
        $groupMembership = Guard::membership((int) $payment['group_id']);

        if (Guard::canManage($groupMembership)) {
            return;
        }

        if (str_starts_with((string) $payment['purpose'], 'coop_')) {
            $coop = Cooperative::forGroup((int) $payment['group_id']);
            $coopMember = $coop !== null
                ? CoopMember::forUser((int) $coop['id'], (int) user()['id'])
                : null;

            if (Cooperative::canManage($groupMembership, $coopMember)) {
                return;
            }
        }

        Response::abort(403, 'You are not allowed to review this payment.');
    }

    /** @param array<string, mixed> $payment */
    private function destination(array $payment): string
    {
        $slug = (string) ($payment['group_slug'] ?? '');

        return match ($payment['purpose']) {
            'dues' => 'groups/' . $slug . '/dues/' . $payment['dues_cycle_id'],
            'welfare' => 'groups/' . $slug . '/welfare/' . $payment['welfare_case_id'],
            'event' => 'groups/' . $slug . '/events/' . $payment['event_id'],
            'coop_loan' => 'groups/' . $slug . '/coop/loans/' . $payment['coop_loan_id'],
            'coop_fine' => 'groups/' . $slug . '/coop/fines',
            'coop_savings', 'coop_shares' => 'groups/' . $slug . '/coop/me',
            default => 'dashboard',
        };
    }
}
