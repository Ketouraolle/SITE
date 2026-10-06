<?php

namespace App\Livewire\Auth;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Password;
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
        $this->validate([
            'email' => ['required', 'email'],
        ]);

        $this->status = null;

        $status = Password::sendResetLink(['email' => $this->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [trans($status)],
            ]);
        }

        $this->status = __($status);
    }

    public function render(): View
    {
        return view('livewire.auth.forgot-password');
    }
}
