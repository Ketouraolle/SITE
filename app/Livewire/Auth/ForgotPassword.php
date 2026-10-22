<?php

namespace App\Livewire\Auth;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Demande d'un lien de reinitialisation du mot de passe.
 */
#[Layout('layouts.auth')]
#[Title('Mot de passe oublie')]
class ForgotPassword extends Component
{
    public string $email = '';

    public ?string $status = null;

    /**
     * @throws ValidationException
     */
    public function sendResetLink(): void
    {
        $this->status = null;
        $this->email = trim($this->email);

        $this->validate([
            'email' => ['required', 'email', 'max:254'],
        ]);

        $key = 'password-reset:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Trop de tentatives. Reessayez dans '
                    .RateLimiter::availableIn($key).' seconde(s).',
            ]);
        }

        RateLimiter::hit($key, 60);

        Password::sendResetLink(['email' => $this->email]);

        $this->status = 'Si un compte correspond a cette adresse, un lien de reinitialisation vous sera envoye.';
    }

    public function render(): View
    {
        return view('livewire.auth.forgot-password');
    }
}
