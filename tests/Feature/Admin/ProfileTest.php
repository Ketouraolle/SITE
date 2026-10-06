<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_profil_peut_etre_mis_a_jour(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('name', 'Admin Boutique')
            ->set('email', 'admin@boutique.test')
            ->call('updateProfile')
            ->assertHasNoErrors();

        $user->refresh();

        $this->assertSame('Admin Boutique', $user->name);
        $this->assertSame('admin@boutique.test', $user->email);
    }

    public function test_l_email_doit_rester_unique(): void
    {
        User::factory()->create(['email' => 'pris@boutique.test']);
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('email', 'pris@boutique.test')
            ->call('updateProfile')
            ->assertHasErrors(['email' => 'unique']);
    }
}
