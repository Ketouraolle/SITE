<div>
    <div class="text-center">
        <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-gradient-to-br from-brand-600 to-cyan-accent text-white shadow-lg shadow-brand-900/20">
            <x-icon name="lock" :size="22" />
        </span>
        <p class="mt-4 text-xs font-bold uppercase tracking-[0.2em] text-brand-600">Espace administration</p>
        <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">Connexion</h1>
        <p class="mt-1 text-sm text-slate-500">Accedez au tableau de bord de la boutique.</p>
    </div>

    @if ($status)
        <div class="mt-5 flex items-start gap-2 rounded-xl bg-emerald-50 p-3 text-sm font-medium text-emerald-700 ring-1 ring-emerald-200">
            <x-icon name="check-circle" :size="18" class="shrink-0" />
            {{ $status }}
        </div>
    @endif

    <form wire:submit="login" class="mt-6 space-y-4">
        <div>
            <x-label for="login-email">Adresse e-mail</x-label>
            <div class="relative mt-1.5">
                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"><x-icon name="mail" :size="16" /></span>
                <x-input id="login-email" type="email" wire:model="email" autocomplete="username" placeholder="vous@exemple.com" class="pl-9" :invalid="$errors->has('email')" required autofocus />
            </div>
            <x-input-error for="email" />
        </div>

        <div x-data="{ visible: false }">
            <x-label for="login-password">Mot de passe</x-label>
            <div class="relative mt-1.5">
                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"><x-icon name="lock" :size="16" /></span>
                <x-input id="login-password" type="password" x-bind:type="visible ? 'text' : 'password'" wire:model="password" autocomplete="current-password" class="pl-9 pr-10" :invalid="$errors->has('password')" required />
                <button type="button" class="absolute right-2 top-1/2 grid h-7 w-7 -translate-y-1/2 place-items-center rounded-md text-slate-400 hover:text-slate-600"
                    x-on:click="visible = ! visible" x-bind:aria-label="visible ? 'Masquer le mot de passe' : 'Afficher le mot de passe'">
                    <span x-show="! visible"><x-icon name="eye" :size="16" /></span>
                    <span x-show="visible" x-cloak><x-icon name="eye-off" :size="16" /></span>
                </button>
            </div>
            <x-input-error for="password" />
        </div>

        <div class="flex items-center justify-between gap-3">
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <x-checkbox wire:model="remember" />
                Se souvenir de moi
            </label>

            @if ($canResetPassword)
                <a href="{{ route('password.request') }}" wire:navigate class="text-sm font-semibold text-brand-700 hover:text-brand-900">Mot de passe oublie ?</a>
            @endif
        </div>

        <x-btn type="submit" block size="lg" wire:loading.attr="disabled" wire:target="login">
            <span wire:loading.remove wire:target="login" class="inline-flex items-center gap-2">Se connecter <x-icon name="arrow-right" :size="18" /></span>
            <span wire:loading wire:target="login">Connexion…</span>
        </x-btn>
    </form>
</div>
