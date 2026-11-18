<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Models\PasswordReset;
use App\Models\User;
use App\Services\Mailer;

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        $this->view('auth/login', [], 'layouts/auth');
    }

    public function login(): void
    {
        $data = $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $email = strtolower((string) $data['email']);

        if ($this->tooManyAttempts($email)) {
            $this->flashError('Too many failed attempts. Please wait 15 minutes and try again.');
            Session::flash('old', ['email' => $email]);
            $this->redirect('login');
        }

        if (!Auth::attempt($email, (string) $data['password'], Request::boolean('remember'))) {
            $this->recordAttempt($email);
            $this->flashError('Those credentials do not match our records.');
            Session::flash('old', ['email' => $email]);
            $this->redirect('login');
        }

        $this->clearAttempts($email);

        $intended = (string) Session::pull('intended_url', '/dashboard');
        $this->flashSuccess('Welcome back.');

        $this->redirect($intended !== '' ? $intended : 'dashboard');
    }

    public function showRegister(): void
    {
        $this->view('auth/register', [], 'layouts/auth');
    }

    public function register(): void
    {
        $data = $this->validate([
            'name' => 'required|min:2|max:120',
            'email' => 'required|email|max:190|unique:users,email',
            'phone' => 'max:40',
            'password' => 'required|min:8|confirmed',
        ]);

        $id = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => $data['password'],
        ]);

        $user = User::find($id);

        if ($user === null) {
            $this->flashError('Your account could not be created. Please try again.');
            $this->redirect('register');
        }

        Auth::login($user);
        $this->flashSuccess('Your Classof account is ready. Add your education so we can find your alumni groups.');

        $this->redirect('onboarding/education');
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('login');
    }

    public function showForgot(): void
    {
        $this->view('auth/forgot', [], 'layouts/auth');
    }

    public function sendReset(): void
    {
        $data = $this->validate(['email' => 'required|email']);

        $user = User::findByEmail((string) $data['email']);

        if ($user !== null) {
            $token = PasswordReset::create((string) $user['email']);
            $link = absolute_url('reset-password/' . $token);

            Mailer::resetLink((string) $user['email'], $link);

            if (config('app.env') === 'local') {
                Session::flash('dev_link', $link);
            }
        }

        $this->flashSuccess('If an account exists for that email, a reset link has been sent.');
        $this->redirect('forgot-password');
    }

    public function showReset(string $token): void
    {
        $reset = PasswordReset::findValid($token);

        if ($reset === null) {
            $this->flashError('That reset link is invalid or has expired. Request a new one.');
            $this->redirect('forgot-password');
        }

        $this->view('auth/reset', [
            'token' => $token,
            'email' => $reset['email'],
        ], 'layouts/auth');
    }

    public function resetPassword(): void
    {
        $data = $this->validate([
            'token' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        $reset = PasswordReset::findValid((string) $data['token']);

        if ($reset === null) {
            $this->flashError('That reset link is invalid or has expired. Request a new one.');
            $this->redirect('forgot-password');
        }

        $user = User::findByEmail((string) $reset['email']);

        if ($user === null) {
            $this->flashError('We could not find that account.');
            $this->redirect('forgot-password');
        }

        User::updatePassword((int) $user['id'], (string) $data['password']);
        PasswordReset::consume((string) $reset['email']);

        $this->flashSuccess('Your password has been reset. Sign in with your new password.');
        $this->redirect('login');
    }

    private function tooManyAttempts(string $email): bool
    {
        return (int) Database::value(
            'SELECT COUNT(*) FROM login_attempts WHERE email = ? AND ip = ? AND attempted_at > ?',
            [$email, Request::ip(), date('Y-m-d H:i:s', time() - 900)],
            0
        ) >= 5;
    }

    private function recordAttempt(string $email): void
    {
        Database::insert('login_attempts', [
            'email' => $email,
            'ip' => Request::ip(),
            'attempted_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function clearAttempts(string $email): void
    {
        Database::run(
            'DELETE FROM login_attempts WHERE email = ? AND ip = ?',
            [$email, Request::ip()]
        );
    }
}
