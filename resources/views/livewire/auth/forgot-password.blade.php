<div>
    <div class="text-center">
        <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-gradient-to-br from-brand-600 to-cyan-accent text-white shadow-lg shadow-brand-900/20">
            <x-icon name="mail" :size="22" />
        </span>
        <p class="mt-4 text-xs font-bold uppercase tracking-[0.2em] text-brand-600">Espace administration</p>
        <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">Mot de passe oublie</h1>
        <p class="mt-1 text-sm text-slate-500">Indiquez votre adresse e-mail : nous vous enverrons un lien de reinitialisation.</p>
    </div>

    @if ($status)
        <div class="mt-5 flex items-start gap-2 rounded-xl bg-emerald-50 p-3 text-sm font-medium text-emerald-700 ring-1 ring-emerald-200">
            <x-icon name="check-circle" :size="18" class="shrink-0" />
            {{ $status }}
        </div>
    @endif

    <form wire:submit="sendResetLink" class="mt-6 space-y-4">
        <div>
            <x-label for="forgot-email">Adresse e-mail</x-label>
            <div class="relative mt-1.5">
                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"><x-icon name="mail" :size="16" /></span>
                <x-input id="forgot-email" type="email" wire:model="email" autocomplete="username" placeholder="vous@exemple.com" class="pl-9" :invalid="$errors->has('email')" required autofocus />
            </div>
            <x-input-error for="email" />
        </div>

        <x-btn type="submit" block size="lg" wire:loading.attr="disabled" wire:target="sendResetLink">
            <span wire:loading.remove wire:target="sendResetLink">Envoyer le lien</span>
            <span wire:loading wire:target="sendResetLink">Envoi…</span>
        </x-btn>

        <a href="{{ route('login') }}" wire:navigate class="flex items-center justify-center gap-1.5 text-sm font-semibold text-slate-600 hover:text-slate-900">
            <x-icon name="arrow-left" :size="16" />
            Retour a la connexion
        </a>
    </form>
</div>
