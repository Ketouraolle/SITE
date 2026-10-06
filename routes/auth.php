<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Livewire\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentification administrateur
|--------------------------------------------------------------------------
|
| Il n'y a volontairement aucune route d'inscription : la table users est
| reservee aux administrateurs, crees par seeder ou via la console
| (php artisan shop:create-admin).
|
*/

Route::middleware('guest')->group(function () {
    Route::livewire('login', Auth\Login::class)->name('login');
    Route::livewire('forgot-password', Auth\ForgotPassword::class)->name('password.request');
    Route::livewire('reset-password/{token}', Auth\ResetPassword::class)->name('password.reset');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
