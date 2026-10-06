<div>
    <x-admin.page-header title="Mon compte" subtitle="Informations de connexion de l'administrateur" />

    <div class="grid gap-4 lg:grid-cols-2">
        {{-- Informations --}}
        <form wire:submit="updateProfile" class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:p-6">
            <div class="flex items-center gap-3">
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-gradient-to-br from-brand-600 to-cyan-accent text-white">
                    <x-icon name="user" :size="20" />
                </span>
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Informations</h2>
                    <p class="text-xs text-slate-500">Nom affiche et adresse de connexion.</p>
                </div>
            </div>

            <div class="mt-5 space-y-4">
                <div>
                    <x-label for="profile-name">Nom <span class="text-red-500">*</span></x-label>
                    <x-input id="profile-name" wire:model="name" autocomplete="name" :invalid="$errors->has('name')" class="mt-1.5" required />
                    <x-input-error for="name" />
                </div>

                <div>
                    <x-label for="profile-email">Adresse e-mail <span class="text-red-500">*</span></x-label>
                    <x-input id="profile-email" type="email" wire:model="email" autocomplete="username" :invalid="$errors->has('email')" class="mt-1.5" required />
                    <x-input-error for="email" />
                </div>
            </div>

            <x-btn type="submit" size="sm" class="mt-5" wire:loading.attr="disabled" wire:target="updateProfile">
                <x-icon name="save" :size="15" />
                <span wire:loading.remove wire:target="updateProfile">Enregistrer</span>
                <span wire:loading wire:target="updateProfile">Enregistrement…</span>
            </x-btn>
        </form>

        {{-- Mot de passe --}}
        <form wire:submit="updatePassword" class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:p-6">
            <div class="flex items-center gap-3">
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-gradient-to-br from-brand-600 to-cyan-accent text-white">
                    <x-icon name="lock" :size="20" />
                </span>
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Mot de passe</h2>
                    <p class="text-xs text-slate-500">Au moins 8 caracteres. Utilisez un mot de passe unique.</p>
                </div>
            </div>

            <div class="mt-5 space-y-4">
                <div>
                    <x-label for="current-password">Mot de passe actuel <span class="text-red-500">*</span></x-label>
                    <x-input id="current-password" type="password" wire:model="current_password" autocomplete="current-password" :invalid="$errors->has('current_password')" class="mt-1.5" required />
                    <x-input-error for="current_password" />
                </div>

                <div>
                    <x-label for="new-password">Nouveau mot de passe <span class="text-red-500">*</span></x-label>
                    <x-input id="new-password" type="password" wire:model="password" autocomplete="new-password" :invalid="$errors->has('password')" class="mt-1.5" required />
                    <x-input-error for="password" />
                </div>

                <div>
                    <x-label for="new-password-confirmation">Confirmer le nouveau mot de passe <span class="text-red-500">*</span></x-label>
                    <x-input id="new-password-confirmation" type="password" wire:model="password_confirmation" autocomplete="new-password" :invalid="$errors->has('password_confirmation')" class="mt-1.5" required />
                    <x-input-error for="password_confirmation" />
                </div>
            </div>

            <x-btn type="submit" size="sm" class="mt-5" wire:loading.attr="disabled" wire:target="updatePassword">
                <x-icon name="save" :size="15" />
                <span wire:loading.remove wire:target="updatePassword">Changer le mot de passe</span>
                <span wire:loading wire:target="updatePassword">Enregistrement…</span>
            </x-btn>
        </form>
    </div>

    <section class="mt-4 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:p-6">
        <div class="flex items-center gap-3">
            <span class="grid h-11 w-11 place-items-center rounded-xl bg-navy-900 text-cyan-accent">
                <x-icon name="shield" :size="20" />
            </span>
            <h2 class="text-sm font-bold text-slate-900">Comptes administrateurs</h2>
        </div>
        <p class="mt-3 text-sm text-slate-600">
            Il n'existe pas d'inscription publique : la table <code class="rounded bg-slate-100 px-1 py-0.5 text-xs">users</code>
            est reservee a l'administration. Pour ajouter un administrateur, executez en console :
        </p>
        <pre class="mt-3 overflow-x-auto rounded-xl bg-navy-950 p-3.5 text-xs text-cyan-accent">php artisan shop:create-admin</pre>
    </section>
</div>
