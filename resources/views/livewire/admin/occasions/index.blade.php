<div>
    <x-admin.page-header title="Periodes" subtitle="Noel, Saint-Valentin, Aid... : produits, packs et offres de saison">
        <x-btn size="sm" wire:click="create">
            <x-icon name="plus" :size="16" />
            Nouvelle periode
        </x-btn>
    </x-admin.page-header>

    <div class="mb-4 flex gap-3 rounded-2xl bg-brand-50 p-4 text-sm text-brand-900 ring-1 ring-brand-100">
        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-brand-600 to-cyan-accent text-white">
            <x-icon name="sparkles" :size="18" />
        </span>
        <div>
            <p class="font-bold">Comment ca marche ?</p>
            <ul class="mt-1 list-disc space-y-0.5 pl-5 text-xs text-brand-800">
                <li>Un <strong>produit</strong> ou un <strong>pack</strong> rattache a une periode n'est visible que pendant celle-ci.</li>
                <li>Une <strong>offre</strong> rattachee a une periode applique sa remise uniquement pendant ses dates.</li>
                <li>Pendant une periode, l'accueil affiche une vitrine dediee. Pensez a ajuster les dates de l'Aid chaque annee.</li>
            </ul>
        </div>
    </div>

    @if ($occasions->isNotEmpty())
        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <ul class="divide-y divide-slate-100">
                @foreach ($occasions as $occasion)
                    @php($status = $statuses[$occasion['status']] ?? $statuses['inactive'])
                    <li wire:key="occasion-{{ $occasion['id'] }}" class="flex flex-wrap items-center gap-3 p-4">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl {{ $occasion['status'] === 'running' ? 'bg-gradient-to-br from-brand-600 to-cyan-accent text-white' : 'bg-slate-100 text-slate-500' }}">
                            <x-icon name="sparkles" :size="18" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-sm font-bold text-slate-900">{{ $occasion['name'] }}</h2>
                                <x-badge :color="$status['color']" dot>{{ $status['label'] }}</x-badge>
                            </div>
                            <p class="mt-0.5 text-xs text-slate-500">
                                {{ $occasion['period_label'] }} · {{ $occasion['products_count'] }} produit(s),
                                {{ $occasion['packs_count'] }} pack(s), {{ $occasion['offers_count'] }} offre(s)
                            </p>
                            @if ($occasion['description'])
                                <p class="mt-1 line-clamp-1 text-xs text-slate-500">{{ $occasion['description'] }}</p>
                            @endif
                        </div>

                        <div class="flex shrink-0 items-center gap-1">
                            <a href="{{ route('admin.offers.create', ['periode' => $occasion['id']]) }}" wire:navigate
                                class="inline-flex h-8 items-center gap-1 rounded-full px-3 text-xs font-semibold text-brand-700 transition hover:bg-brand-50"
                                title="Creer une offre pour cette periode">
                                <x-icon name="plus" :size="14" />
                                Offre
                            </a>

                            @if ($occasion['status'] === 'running')
                                <a href="{{ route('occasions.show', $occasion['slug']) }}" target="_blank"
                                    class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                                    title="Voir la vitrine" aria-label="Voir la vitrine">
                                    <x-icon name="eye" :size="16" />
                                </a>
                            @endif

                            <button type="button" wire:click="edit({{ $occasion['id'] }})"
                                class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                                title="Modifier" aria-label="Modifier">
                                <x-icon name="edit" :size="16" />
                            </button>

                            <button type="button" wire:click="delete({{ $occasion['id'] }})"
                                wire:confirm="Supprimer « {{ $occasion['name'] }} » ? Les produits, packs et offres rattaches sont conserves et redeviennent disponibles toute l'annee."
                                class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 transition hover:bg-red-50 hover:text-red-600"
                                title="Supprimer" aria-label="Supprimer">
                                <x-icon name="trash" :size="16" />
                            </button>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @else
        <x-empty-state icon="sparkles" title="Aucune periode"
            description="Creez Noel, la Saint-Valentin ou l'Aid pour proposer des produits, packs et offres de saison.">
            <x-btn size="sm" wire:click="create">
                <x-icon name="plus" :size="16" />
                Nouvelle periode
            </x-btn>
        </x-empty-state>
    @endif

    {{-- Formulaire (fenetre modale) --}}
    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-end justify-center overflow-y-auto p-4 sm:items-center"
            x-data x-on:keydown.escape.window="$wire.closeForm()" role="dialog" aria-modal="true" aria-labelledby="occasion-form-title">
            <div class="fixed inset-0 bg-navy-950/60 backdrop-blur-sm" wire:click="closeForm"></div>

            <form wire:submit="save" class="relative max-h-[90dvh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-5 shadow-2xl sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <h2 id="occasion-form-title" class="text-lg font-extrabold tracking-tight text-slate-900">
                        {{ $editingId ? 'Modifier la periode' : 'Nouvelle periode' }}
                    </h2>
                    <button type="button" wire:click="closeForm" class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 hover:bg-slate-100" aria-label="Fermer">
                        <x-icon name="close" :size="18" />
                    </button>
                </div>

                <div class="mt-4 space-y-4">
                    <div>
                        <x-label for="occasion-name">Nom <span class="text-red-500">*</span></x-label>
                        <x-input id="occasion-name" wire:model="name" placeholder="Ex. Noel, Saint-Valentin, Aid el-Fitr" :invalid="$errors->has('name')" class="mt-1.5" required autofocus />
                        <x-input-error for="name" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-label for="occasion-starts">Debut <span class="font-normal text-slate-400">(optionnel)</span></x-label>
                            <x-input id="occasion-starts" type="date" wire:model="starts_on" :invalid="$errors->has('starts_on')" class="mt-1.5" />
                            <x-input-error for="starts_on" />
                        </div>
                        <div>
                            <x-label for="occasion-ends">Fin <span class="font-normal text-slate-400">(incluse)</span></x-label>
                            <x-input id="occasion-ends" type="date" wire:model="ends_on" :invalid="$errors->has('ends_on')" class="mt-1.5" />
                            <x-input-error for="ends_on" />
                        </div>
                    </div>

                    <div>
                        <x-label for="occasion-description">Description <span class="font-normal text-slate-400">(affichee sur la vitrine)</span></x-label>
                        <x-textarea id="occasion-description" wire:model="description" :rows="3" :invalid="$errors->has('description')" class="mt-1.5" />
                        <x-input-error for="description" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-label for="occasion-slug">Slug <span class="font-normal text-slate-400">(genere si vide)</span></x-label>
                            <x-input id="occasion-slug" wire:model="slug" :invalid="$errors->has('slug')" class="mt-1.5" />
                            <x-input-error for="slug" />
                        </div>
                        <div>
                            <x-label for="occasion-position">Position <span class="font-normal text-slate-400">(ordre d'affichage)</span></x-label>
                            <x-input id="occasion-position" type="number" min="0" wire:model="position" :invalid="$errors->has('position')" class="mt-1.5" />
                            <x-input-error for="position" />
                        </div>
                    </div>

                    <x-toggle wire:model="is_active" label="Periode active" description="Desactivee, ses produits et packs sont masques et ses offres suspendues." />
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <x-btn type="button" variant="secondary" size="sm" wire:click="closeForm">Annuler</x-btn>
                    <x-btn type="submit" size="sm" wire:loading.attr="disabled" wire:target="save">
                        <x-icon name="save" :size="15" />
                        <span wire:loading.remove wire:target="save">Enregistrer</span>
                        <span wire:loading wire:target="save">Enregistrement…</span>
                    </x-btn>
                </div>
            </form>
        </div>
    @endif
</div>
