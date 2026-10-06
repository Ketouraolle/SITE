<div>
    <x-admin.page-header title="Categories" subtitle="{{ $categories->count() }} categorie(s)">
        <x-btn variant="accent" size="sm" wire:click="create">
            <x-icon name="plus" :size="16" />
            Nouvelle categorie
        </x-btn>
    </x-admin.page-header>

    @if ($categories->isNotEmpty())
        {{-- Tableau (desktop) --}}
        <div class="hidden overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 md:block">
            <table class="w-full">
                <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" class="px-4 py-3">Categorie</th>
                        <th scope="col" class="px-4 py-3 text-center">Position</th>
                        <th scope="col" class="px-4 py-3 text-right">Produits publies</th>
                        <th scope="col" class="px-4 py-3">Statut</th>
                        <th scope="col" class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($categories as $category)
                        <tr wire:key="category-row-{{ $category->id }}" class="transition hover:bg-brand-50/40">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-brand-600 to-cyan-accent text-white">
                                        <x-icon name="tag" :size="18" />
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold text-slate-900">{{ $category->name }}</p>
                                        <p class="text-xs text-slate-400">/{{ $category->slug }}</p>
                                        @if ($category->description)
                                            <p class="mt-0.5 line-clamp-1 max-w-md text-xs text-slate-500">{{ $category->description }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center text-sm tabular-nums text-slate-600">{{ $category->position }}</td>
                            <td class="px-4 py-3 text-right text-sm tabular-nums text-slate-700">
                                <span class="font-bold text-slate-900">{{ $category->published_products_count }}</span> / {{ $category->products_count }}
                            </td>
                            <td class="px-4 py-3">
                                <x-badge :color="$category->is_active ? 'emerald' : 'slate'" dot>{{ $category->is_active ? 'Active' : 'Masquee' }}</x-badge>
                            </td>
                            <td class="px-4 py-3">
                                @include('livewire.admin.categories.partials.actions')
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Cartes (mobile) --}}
        <ul class="space-y-3 md:hidden">
            @foreach ($categories as $category)
                <li wire:key="category-card-{{ $category->id }}" class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-start gap-3">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-brand-600 to-cyan-accent text-white">
                            <x-icon name="tag" :size="18" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-bold text-slate-900">{{ $category->name }}</p>
                                <x-badge :color="$category->is_active ? 'emerald' : 'slate'" dot>{{ $category->is_active ? 'Active' : 'Masquee' }}</x-badge>
                            </div>
                            <p class="mt-0.5 text-xs text-slate-500">
                                /{{ $category->slug }} · position {{ $category->position }} ·
                                {{ $category->published_products_count }} produit(s) publie(s) sur {{ $category->products_count }}
                            </p>
                            @if ($category->description)
                                <p class="mt-1 line-clamp-2 text-xs text-slate-500">{{ $category->description }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="mt-3 border-t border-slate-100 pt-3">
                        @include('livewire.admin.categories.partials.actions')
                    </div>
                </li>
            @endforeach
        </ul>
    @else
        <x-empty-state icon="tag" title="Aucune categorie" description="Les categories permettent aux clients de filtrer le catalogue.">
            <x-btn variant="accent" size="sm" wire:click="create">
                <x-icon name="plus" :size="16" />
                Nouvelle categorie
            </x-btn>
        </x-empty-state>
    @endif

    {{-- Formulaire (fenetre modale) --}}
    <div x-data x-show="$wire.showForm" x-cloak x-on:keydown.escape.window="$wire.showForm = false"
        class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4" role="dialog" aria-modal="true">
        <div x-show="$wire.showForm" x-transition.opacity class="absolute inset-0 bg-navy-950/60" x-on:click="$wire.showForm = false"></div>

        <form wire:submit="save" x-show="$wire.showForm"
            x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-4 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
            class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-t-2xl bg-white p-5 shadow-xl sm:rounded-2xl sm:p-6">
            <div class="flex items-start justify-between gap-4">
                <h2 class="text-lg font-extrabold tracking-tight text-slate-900">{{ $editingId ? 'Modifier la categorie' : 'Nouvelle categorie' }}</h2>
                <button type="button" class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 hover:bg-slate-100" aria-label="Fermer" x-on:click="$wire.showForm = false">
                    <x-icon name="close" :size="18" />
                </button>
            </div>

            <div class="mt-5 space-y-4">
                <div>
                    <x-label for="category-name">Nom <span class="text-red-500">*</span></x-label>
                    <x-input id="category-name" wire:model="name" class="mt-1.5" :invalid="$errors->has('name')" required />
                    <x-input-error for="name" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-label for="category-slug">Slug <span class="font-normal text-slate-400">genere si vide</span></x-label>
                        <x-input id="category-slug" wire:model="slug" class="mt-1.5" :invalid="$errors->has('slug')" />
                        <x-input-error for="slug" />
                    </div>
                    <div>
                        <x-label for="category-position">Position <span class="font-normal text-slate-400">ordre d'affichage</span></x-label>
                        <x-input id="category-position" type="number" min="0" wire:model="position" class="mt-1.5" :invalid="$errors->has('position')" />
                        <x-input-error for="position" />
                    </div>
                </div>

                <div>
                    <x-label for="category-description">Description <span class="font-normal text-slate-400">optionnel</span></x-label>
                    <x-textarea id="category-description" wire:model="description" :rows="3" class="mt-1.5" :invalid="$errors->has('description')" />
                    <x-input-error for="description" />
                </div>

                <x-toggle wire:model="is_active" label="Categorie active" description="Une categorie masquee disparait des menus et des filtres." />
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <x-btn variant="secondary" size="sm" x-on:click="$wire.showForm = false">Annuler</x-btn>
                <x-btn type="submit" size="sm" wire:loading.attr="disabled" wire:target="save">
                    <x-icon name="save" :size="15" />
                    Enregistrer
                </x-btn>
            </div>
        </form>
    </div>
</div>
