<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Payment;
use App\Models\User;
use App\Models\WelfareContribution;
use App\Services\Uploader;

final class ProfileController extends Controller
{
    public function edit(): void
    {
        $user = $this->currentUser();

        $memberships = Database::fetchAll(
            'SELECT m.*, g.name AS group_name, g.slug AS group_slug, g.type AS group_type, g.scope AS group_scope
             FROM memberships m
             INNER JOIN `alumni_groups` g ON g.id = m.group_id
             WHERE m.user_id = ? AND g.status = "active"
             ORDER BY m.status, g.name',
            [(int) $user['id']]
        );

        $this->view('profile/edit', [
            'user' => $user,
            'memberships' => $memberships,
            'payments' => Payment::forUser((int) $user['id'], 50),
            'contributed' => WelfareContribution::totalForUser((int) $user['id']),
            'purposes' => Payment::PURPOSES,
        ]);
    }

    public function update(): void
    {
        $user = $this->currentUser();

        $data = $this->validate([
            'name' => 'required|min:2|max:120',
            'phone' => 'max:40',
            'occupation' => 'max:120',
            'location' => 'max:120',
            'bio' => 'max:1000',
        ]);

        $update = [
            'name' => $data['name'],
            'phone' => $data['phone'] ?: null,
            'occupation' => $data['occupation'] ?: null,
            'location' => $data['location'] ?: null,
            'bio' => $data['bio'] ?: null,
        ];

        $avatar = \App\Core\Request::file('avatar');

        if ($avatar !== null) {
            try {
                $update['avatar'] = Uploader::image($avatar, 'avatar');
                Uploader::delete($user['avatar'] ?? null);
            } catch (\RuntimeException $e) {
                $this->flashError($e->getMessage());
                $this->redirect('profile');
            }
        }

        User::update($update, ['id' => (int) $user['id']]);

        $this->flashSuccess('Your profile has been updated.');
        $this->redirect('profile');
    }

    public function updateWhatsapp(): void
    {
        $user = $this->currentUser();
        $phone = preg_replace('/[^0-9+]/', '', trim((string) \App\Core\Request::input('whatsapp_phone', ''))) ?? '';
        $optIn = \App\Core\Request::boolean('whatsapp_opt_in');

        if ($optIn && !preg_match('/^\+[1-9][0-9]{7,14}$/', $phone)) {
            $this->fail(['whatsapp_phone' => 'Use an international phone number such as +2348012345678.']);
        }

        User::update([
            'phone_e164' => $phone !== '' ? $phone : null,
            'whatsapp_opt_in_at' => $optIn ? date('Y-m-d H:i:s') : null,
            'whatsapp_opt_out_at' => $optIn ? null : date('Y-m-d H:i:s'),
        ], ['id' => (int) $user['id']]);

        $this->audit(null, 'profile.whatsapp_updated', $optIn ? 'Opted in to WhatsApp updates' : 'Opted out of WhatsApp updates', 'user', (int) $user['id']);
        $this->flashSuccess($optIn ? 'WhatsApp updates enabled.' : 'WhatsApp updates disabled.');
        $this->redirect('profile');
    }

    public function password(): void
    {        $user = $this->currentUser();

        $data = $this->validate([
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        if (!password_verify((string) $data['current_password'], (string) $user['password_hash'])) {
            $this->fail(['current_password' => 'Your current password is incorrect.']);
        }

        User::updatePassword((int) $user['id'], (string) $data['password']);

        $this->flashSuccess('Your password has been changed.');
        $this->redirect('profile');
    }
}
