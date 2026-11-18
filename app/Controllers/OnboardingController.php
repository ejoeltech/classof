<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\EducationRecord;
use App\Models\Institution;
use App\Models\Programme;

final class OnboardingController extends Controller
{
    public function show(): void
    {
        $user = $this->currentUser();

        if (EducationRecord::countForUser((int) $user['id']) > 0) {
            $this->redirect('dashboard');
        }

        $this->view('onboarding/education', [
            'user' => $user,
            'institutions' => Institution::selectable((int) $user['id']),
            'programmes' => Programme::all(),
        ]);
    }

    public function skip(): void
    {
        $this->flashSuccess('You can add your education later from your profile.');
        $this->redirect('dashboard');
    }
}
