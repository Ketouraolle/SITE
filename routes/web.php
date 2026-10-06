<?php

use App\Livewire\Admin;
use App\Livewire\Shop;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Site public
|--------------------------------------------------------------------------
|
| Aucune authentification : le client commande sans compte, son panier vit
| dans la session. Chaque page est un composant Livewire plein ecran ; les
| actions (ajout au panier, commande...) sont des methodes de ces composants.
|
*/

Route::livewire('/', Shop\Home::class)->name('home');

Route::livewire('/catalogue', Shop\Catalog::class)->name('catalog.index');
Route::livewire('/produit/{product}', Shop\ProductShow::class)->name('catalog.show');

Route::livewire('/packs', Shop\PackIndex::class)->name('packs.index');
Route::livewire('/packs/{pack}', Shop\PackShow::class)->name('packs.show');

Route::livewire('/periodes/{occasion}', Shop\OccasionShow::class)->name('occasions.show');

Route::livewire('/mon-pack', Shop\CustomPack::class)->name('custom-pack.create');

Route::livewire('/panier', Shop\Cart::class)->name('cart.index');

Route::livewire('/commande', Shop\Checkout::class)->name('checkout.create');
Route::livewire('/commande/{order}/{token}', Shop\Confirmation::class)->name('checkout.confirmation');

/*
|--------------------------------------------------------------------------
| Dashboard administrateur
|--------------------------------------------------------------------------
|
| Reserve aux comptes de la table users. Il n'existe pas d'inscription
| publique : les comptes sont crees par seeder ou en console.
|
*/

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::livewire('/', Admin\Dashboard::class)->name('dashboard');
    Route::livewire('statistiques', Admin\Statistics::class)->name('statistics');

    Route::livewire('commandes', Admin\Orders\Index::class)->name('orders.index');
    Route::livewire('commandes/{order}', Admin\Orders\Show::class)->name('orders.show');

    Route::livewire('produits', Admin\Products\Index::class)->name('products.index');
    Route::livewire('produits/nouveau', Admin\Products\Form::class)->name('products.create');
    Route::livewire('produits/{product:id}/modifier', Admin\Products\Form::class)->name('products.edit');

    Route::livewire('categories', Admin\Categories\Index::class)->name('categories.index');

    Route::livewire('packs', Admin\Packs\Index::class)->name('packs.index');
    Route::livewire('packs/nouveau', Admin\Packs\Form::class)->name('packs.create');
    Route::livewire('packs/{pack:id}/modifier', Admin\Packs\Form::class)->name('packs.edit');

    Route::livewire('periodes', Admin\Occasions\Index::class)->name('occasions.index');

    Route::livewire('offres', Admin\Offers\Index::class)->name('offers.index');
    Route::livewire('offres/nouvelle', Admin\Offers\Form::class)->name('offers.create');
    Route::livewire('offres/{offer}/modifier', Admin\Offers\Form::class)->name('offers.edit');

    Route::livewire('stocks', Admin\Stock\Index::class)->name('stock.index');

    Route::livewire('profil', Admin\Profile::class)->name('profile.edit');
});

require __DIR__.'/auth.php';
