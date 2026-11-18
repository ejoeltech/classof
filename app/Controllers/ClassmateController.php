<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\ClassmateService;

final class ClassmateController extends Controller
{
    public function index(): void
    {
        $user = $this->currentUser();

        $this->view('classmates/index', [
            'user' => $user,
            'optedIn' => !empty($user['classmate_opt_in_at']),
            'classmatesEnabled' => PlatformSetting::bool('classmates.enabled'),
            'classmates' => ClassmateService::suggestions((int) $user['id']),
        ]);
    }

    public function toggle(): void
    {
        $user = $this->currentUser();
        $optIn = \App\Core\Request::boolean('classmate_opt_in');

        User::update([
            'classmate_opt_in_at' => $optIn ? date('Y-m-d H:i:s') : null,
        ], ['id' => (int) $user['id']]);

        $this->audit(null, 'profile.classmates_updated', $optIn ? 'Opted in to classmate discovery' : 'Opted out of classmate discovery', 'user', (int) $user['id']);
        $this->flashSuccess($optIn
            ? 'You can now appear to classmates with the same confirmed education record.'
            : 'You are hidden from classmate discovery.');
        $this->redirect('classmates');
    }
}
