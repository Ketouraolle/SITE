<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\ResetPassword as ResetPasswordPage;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertSeeLivewire(ForgotPassword::class);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        Livewire::test(ForgotPassword::class)
            ->set('email', $user->email)
            ->call('sendResetLink')
            ->assertHasNoErrors()
            ->assertSet(
                'status',
                'Si un compte correspond a cette adresse, un lien de reinitialisation vous sera envoye.',
            );

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        Livewire::test(ForgotPassword::class)
            ->set('email', $user->email)
            ->call('sendResetLink');

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $this->get('/reset-password/'.$notification->token.'?email='.urlencode($user->email))
                ->assertOk()
                ->assertSeeLivewire(ResetPasswordPage::class);

            Livewire::withQueryParams(['email' => $user->email])
                ->test(ResetPasswordPage::class, ['token' => $notification->token])
                ->assertSet('email', $user->email);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        Livewire::test(ForgotPassword::class)
            ->set('email', $user->email)
            ->call('sendResetLink');

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            Livewire::test(ResetPasswordPage::class, ['token' => $notification->token])
                ->set('email', $user->email)
                ->set('password', 'new-password')
                ->set('password_confirmation', 'new-password')
                ->call('resetPassword')
                ->assertHasNoErrors()
                ->assertRedirect(route('login'));

            $this->assertTrue(Hash::check('new-password', $user->refresh()->password));

            return true;
        });
    }

    public function test_unknown_email_receives_the_same_response(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $key = 'password-reset:127.0.0.1';

        RateLimiter::clear($key);

        try {
            $this->withServerVariables([
                'REMOTE_ADDR' => '127.0.0.1',
            ]);

            $component = Livewire::test(ForgotPassword::class);

            $component
                ->set('email', $user->email)
                ->call('sendResetLink')
                ->assertHasNoErrors();

            $knownStatus = $component->get('status');

            Notification::assertSentTo($user, ResetPassword::class);
            Notification::fake();

            $component
                ->set('email', 'unknown@example.com')
                ->call('sendResetLink')
                ->assertHasNoErrors()
                ->assertSet('status', $knownStatus);

            Notification::assertNothingSent();
        } finally {
            RateLimiter::clear($key);
        }
    }

    public function test_reset_requests_are_limited_per_ip_and_resume_after_one_minute(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $key = 'password-reset:127.0.0.1';

        RateLimiter::clear($key);
        $this->freezeTime();

        try {
            $this->withServerVariables([
                'REMOTE_ADDR' => '127.0.0.1',
            ]);

            $component = Livewire::test(ForgotPassword::class);

            for ($attempt = 0; $attempt < 5; $attempt++) {
                $component
                    ->set('email', "unknown{$attempt}@example.com")
                    ->call('sendResetLink')
                    ->assertHasNoErrors();
            }

            $component
                ->set('email', $user->email)
                ->call('sendResetLink')
                ->assertHasErrors('email')
                ->assertSet('status', null);

            Notification::assertNothingSent();

            $this->travel(61)->seconds();

            $component
                ->call('sendResetLink')
                ->assertHasNoErrors();

            Notification::assertSentTo($user, ResetPassword::class);
        } finally {
            RateLimiter::clear($key);
            $this->travelBack();
        }
    }
}
