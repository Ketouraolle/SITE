<?php

namespace App\Providers;

use App\Models\Category;
use App\Services\Promotions;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Promotions::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Coordonnees de la boutique, partagees par tous les layouts.
        View::composer('layouts.*', function (\Illuminate\View\View $view): void {
            $view->with('shop', [
                'name' => config('shop.name'),
                'currency' => config('shop.currency'),
                'phone' => config('shop.phone'),
                'email' => config('shop.email'),
                'address' => config('shop.address'),
                'whatsapp_url' => app(WhatsAppService::class)->contactUrl(
                    'Bonjour, j\'ai une question sur vos produits.'
                ),
            ]);
        });

        View::composer('layouts.public', function (\Illuminate\View\View $view): void {
            $view->with('navCategories', Category::active()->ordered()->get(['id', 'name', 'slug']));
        });
    }
}
