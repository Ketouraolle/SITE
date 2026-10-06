<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Occasions\Index as OccasionIndex;
use App\Livewire\Admin\Offers\Form as OfferForm;
use App\Livewire\Admin\Offers\Index as OfferIndex;
use App\Models\Occasion;
use App\Models\Offer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PromotionManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
    }

    /* --------------------------------------------------------------------- */
    /* Periodes */
    /* --------------------------------------------------------------------- */

    public function test_une_periode_peut_etre_creee(): void
    {
        Livewire::actingAs($this->admin)
            ->test(OccasionIndex::class)
            ->call('create')
            ->set('name', 'Saint-Valentin')
            ->set('starts_on', '2027-02-01')
            ->set('ends_on', '2027-02-14')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showForm', false);

        $occasion = Occasion::firstOrFail();

        $this->assertSame('Saint-Valentin', $occasion->name);
        $this->assertSame('saint-valentin', $occasion->slug);
        $this->assertSame('2027-02-14', $occasion->ends_on->toDateString());
    }

    public function test_la_fin_d_une_periode_doit_suivre_son_debut(): void
    {
        Livewire::actingAs($this->admin)
            ->test(OccasionIndex::class)
            ->set('name', 'Noel')
            ->set('starts_on', '2027-12-25')
            ->set('ends_on', '2027-12-01')
            ->call('save')
            ->assertHasErrors(['ends_on']);

        $this->assertDatabaseCount('occasions', 0);
    }

    public function test_une_periode_peut_etre_modifiee_puis_supprimee_sans_perdre_ses_produits(): void
    {
        $occasion = Occasion::factory()->create(['name' => 'Noel']);
        $product = Product::factory()->create(['occasion_id' => $occasion->id]);

        $component = Livewire::actingAs($this->admin)
            ->test(OccasionIndex::class)
            ->call('edit', $occasion->id)
            ->set('name', 'Noel 2027')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Noel 2027', $occasion->fresh()->name);

        $component->call('delete', $occasion->id);

        $this->assertModelMissing($occasion);
        $this->assertNull($product->fresh()->occasion_id);
    }

    /* --------------------------------------------------------------------- */
    /* Offres */
    /* --------------------------------------------------------------------- */

    public function test_une_offre_peut_etre_creee_avec_ses_produits(): void
    {
        $product = Product::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(OfferForm::class)
            ->set('name', 'Soldes de rentree')
            ->set('discount_percent', 20)
            ->set('product_ids', [$product->id])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.offers.index'));

        $offer = Offer::firstOrFail();

        $this->assertSame(20, (int) $offer->discount_percent);
        $this->assertSame([$product->id], $offer->products()->pluck('products.id')->all());
    }

    public function test_une_offre_exige_au_moins_un_produit_ou_un_pack(): void
    {
        Livewire::actingAs($this->admin)
            ->test(OfferForm::class)
            ->set('name', 'Offre vide')
            ->set('discount_percent', 10)
            ->call('save')
            ->assertHasErrors(['product_ids']);

        $this->assertDatabaseCount('offers', 0);
    }

    public function test_la_remise_d_une_offre_est_plafonnee(): void
    {
        $product = Product::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(OfferForm::class)
            ->set('name', 'Trop belle')
            ->set('discount_percent', 95)
            ->set('product_ids', [$product->id])
            ->call('save')
            ->assertHasErrors(['discount_percent' => 'max']);
    }

    public function test_une_offre_peut_etre_desactivee_puis_supprimee(): void
    {
        $offer = Offer::factory()->create(['is_active' => true]);

        $component = Livewire::actingAs($this->admin)
            ->test(OfferIndex::class)
            ->call('toggleActive', $offer->id);

        $this->assertFalse($offer->fresh()->is_active);

        $component->call('delete', $offer->id);

        $this->assertModelMissing($offer);
    }
}
