<div>
    <div class="text-center">
        <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-gradient-to-br from-brand-600 to-cyan-accent text-white shadow-lg shadow-brand-900/20">
            <x-icon name="shield" :size="22" />
        </span>
        <p class="mt-4 text-xs font-bold uppercase tracking-[0.2em] text-brand-600">Espace administration</p>
        <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">Nouveau mot de passe</h1>
        <p class="mt-1 text-sm text-slate-500">Choisissez un nouveau mot de passe.</p>
    </div>

    <form wire:submit="resetPassword" class="mt-6 space-y-4">
        <div>
            <x-label for="reset-email">Adresse e-mail</x-label>
            <div class="relative mt-1.5">
                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"><x-icon name="mail" :size="16" /></span>
                <x-input id="reset-email" type="email" wire:model="email" autocomplete="username" class="pl-9" :invalid="$errors->has('email')" required />
            </div>
            <x-input-error for="email" />
        </div>

        <div>
            <x-label for="reset-password">Nouveau mot de passe</x-label>
            <div class="relative mt-1.5">
                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"><x-icon name="lock" :size="16" /></span>
                <x-input id="reset-password" type="password" wire:model="password" autocomplete="new-password" class="pl-9" :invalid="$errors->has('password')" required autofocus />
            </div>
            <x-input-error for="password" />
        </div>

        <div>
            <x-label for="reset-password-confirmation">Confirmer le mot de passe</x-label>
            <div class="relative mt-1.5">
                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"><x-icon name="lock" :size="16" /></span>
                <x-input id="reset-password-confirmation" type="password" wire:model="password_confirmation" autocomplete="new-password" class="pl-9" :invalid="$errors->has('password_confirmation')" required />
            </div>
            <x-input-error for="password_confirmation" />
        </div>

        <x-btn type="submit" block size="lg" wire:loading.attr="disabled" wire:target="resetPassword">
            <span wire:loading.remove wire:target="resetPassword">Reinitialiser le mot de passe</span>
            <span wire:loading wire:target="resetPassword">Enregistrement…</span>
        </x-btn>
    </form>
</div>
