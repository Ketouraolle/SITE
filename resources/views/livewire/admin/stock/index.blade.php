<div
    x-data="{
        threshold: {{ $threshold }},
        original: {},
        draft: {},
        register(id, stock, available) {
            this.original[id] = { stock: stock, is_available: available };
            this.draft[id] = { stock: stock, is_available: available };
        },
        isDirty(id) {
            const edit = this.draft[id];
            const base = this.original[id];
            if (! edit || ! base) return false;
            return Number(edit.stock) !== base.stock || edit.is_available !== base.is_available;
        },
        get dirtyIds() {
            return Object.keys(this.draft).filter((id) => this.isDirty(id));
        },
        adjust(id, delta) {
            const current = Number(this.draft[id].stock) || 0;
            this.draft[id].stock = Math.max(0, current + delta);
        },
        reset() {
            this.draft = JSON.parse(JSON.stringify(this.original));
        },
        payload(id) {
            return { id: Number(id), stock: Math.max(0, parseInt(this.draft[id].stock, 10) || 0), is_available: this.draft[id].is_available };
        },
        save() {
            const updates = this.dirtyIds.map((id) => this.payload(id));
            if (! updates.length) return;
            this.$wire.bulkUpdate(updates).then(() => updates.forEach((row) => { this.original[row.id] = { stock: row.stock, is_available: row.is_available }; }));
        },
        saveRow(id) {
            const row = this.payload(id);
            this.$wire.updateStock(row.id, { stock: row.stock, is_available: row.is_available }).then(() => { this.original[row.id] = { stock: row.stock, is_available: row.is_available }; });
        },
        preview(id) {
            const edit = this.draft[id];
            if (! edit) return { label: '—', classes: 'bg-slate-100 text-slate-600 ring-slate-500/20' };
            if (! edit.is_available) return { label: 'Indisponible', classes: 'bg-slate-100 text-slate-600 ring-slate-500/20' };
            if (Number(edit.stock) <= 0) return { label: 'En rupture', classes: 'bg-red-50 text-red-700 ring-red-600/20' };
            if (Number(edit.stock) <= this.threshold) return { label: 'Bientot epuise', classes: 'bg-amber-50 text-amber-800 ring-amber-600/20' };
            return { label: 'En stock', classes: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' };
        },
    }"
    class="pb-20"
>
    <x-admin.page-header title="Gestion des stocks" subtitle="Ajustez les quantites et la disponibilite">
        <x-btn variant="accent" size="sm" x-on:click="save()" x-bind:disabled="! dirtyIds.length" wire:loading.attr="disabled" wire:target="bulkUpdate">
            <x-icon name="save" :size="16" />
            <span x-text="dirtyIds.length ? `Enregistrer (${dirtyIds.length})` : 'Enregistrer'">Enregistrer</span>
        </x-btn>
    </x-admin.page-header>

    {{-- Synthese --}}
    <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        <x-stat-card label="En rupture" :value="$summary['out_of_stock']" hint="Stock a 0" icon="alert" tone="rose" />
        <x-stat-card label="Stock faible" :value="$summary['low']" hint="Seuil : {{ $threshold }}" icon="boxes" tone="amber" />
        <x-stat-card label="Indisponibles" :value="$summary['unavailable']" hint="Retires de la vente" icon="eye-off" tone="slate" />
        <x-stat-card label="Unites en stock" :value="number_format($summary['units'], 0, ',', ' ')" hint="Tous produits confondus" icon="box" tone="brand" />
    </div>

    {{-- Filtres --}}
    <div class="mt-4 flex flex-col gap-3 rounded-2xl bg-white p-3 shadow-sm ring-1 ring-slate-200 sm:flex-row sm:items-center sm:p-4">
        <div class="relative flex-1">
            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                <x-icon name="search" :size="16" />
            </span>
            <x-input type="search" wire:model.live.debounce.350ms="q" placeholder="Rechercher un produit…" class="pl-9" aria-label="Rechercher un produit" />
        </div>

        <x-select wire:model.live="category" class="sm:w-56" aria-label="Filtrer par categorie">
            <option value="">Toutes les categories</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
            @endforeach
        </x-select>

        <x-select wire:model.live="state" class="sm:w-48" aria-label="Filtrer par etat">
            @foreach ($states as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </x-select>
    </div>

    {{-- Lignes --}}
    @if ($products->isNotEmpty())
        <div class="mt-4 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200" wire:loading.class="opacity-60" wire:target="q, category, state, gotoPage, nextPage, previousPage">
            <div class="hidden grid-cols-[minmax(0,1fr)_200px_130px_150px_44px] items-center gap-4 border-b border-slate-200 bg-slate-50 px-4 py-3 text-xs font-bold uppercase tracking-wide text-slate-500 lg:grid">
                <span>Produit</span>
                <span class="text-center">Stock</span>
                <span>En vente</span>
                <span>Etat client</span>
                <span class="sr-only">Action</span>
            </div>

            <ul class="divide-y divide-slate-100">
                @foreach ($products as $product)
                    @php($id = $product['id'])
                    <li
                        wire:key="stock-{{ $id }}-{{ $product['stock'] }}-{{ (int) $product['is_available'] }}"
                        x-init="register({{ $id }}, {{ $product['stock'] }}, {{ $product['is_available'] ? 'true' : 'false' }})"
                        class="flex flex-wrap items-center gap-3 p-3 transition sm:p-4 lg:grid lg:grid-cols-[minmax(0,1fr)_200px_130px_150px_44px] lg:gap-4"
                        x-bind:class="isDirty({{ $id }}) ? 'bg-amber-50/60' : ''"
                    >
                        <div class="flex min-w-0 flex-1 basis-56 items-center gap-3">
                            <div class="w-12 shrink-0">
                                <x-thumb :src="$product['image']" :alt="$product['name']" :label="$product['name']" rounded="rounded-lg" />
                            </div>
                            <div class="min-w-0">
                                <a href="{{ route('admin.products.edit', $id) }}" wire:navigate class="block truncate text-sm font-bold text-slate-900 hover:text-brand-700">{{ $product['name'] }}</a>
                                <p class="truncate text-xs text-slate-500">{{ $product['category']['name'] ?? 'Sans categorie' }} · {{ $product['price_label'] }}</p>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center justify-center gap-1.5">
                            <button type="button" x-on:click="adjust({{ $id }}, -1)" aria-label="Retirer une unite"
                                class="grid h-9 w-9 place-items-center rounded-lg text-slate-500 ring-1 ring-inset ring-slate-300 transition hover:bg-slate-50">
                                <x-icon name="minus" :size="15" />
                            </button>
                            <input type="number" min="0" x-model="draft[{{ $id }}].stock" aria-label="Stock de {{ $product['name'] }}"
                                class="w-20 rounded-lg border-0 py-2 text-center text-sm font-bold tabular-nums ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600" />
                            <button type="button" x-on:click="adjust({{ $id }}, 1)" aria-label="Ajouter une unite"
                                class="grid h-9 w-9 place-items-center rounded-lg text-slate-500 ring-1 ring-inset ring-slate-300 transition hover:bg-slate-50">
                                <x-icon name="plus" :size="15" />
                            </button>
                        </div>

                        <label class="flex shrink-0 cursor-pointer items-center gap-2">
                            <span class="relative inline-flex shrink-0">
                                <input type="checkbox" class="peer sr-only" x-model="draft[{{ $id }}].is_available" aria-label="{{ $product['name'] }} en vente" />
                                <span class="h-6 w-11 rounded-full bg-slate-300 transition peer-checked:bg-emerald-500 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-600 peer-focus-visible:ring-offset-2"></span>
                                <span class="pointer-events-none absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                            </span>
                            <span class="text-sm font-semibold text-slate-700 lg:sr-only">En vente</span>
                        </label>

                        <div class="shrink-0">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset" x-bind:class="preview({{ $id }}).classes">
                                <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                <span x-text="preview({{ $id }}).label">{{ $product['stock_label'] }}</span>
                            </span>
                        </div>

                        <div class="ml-auto shrink-0 lg:ml-0">
                            <button type="button" x-show="isDirty({{ $id }})" x-cloak x-on:click="saveRow({{ $id }})" title="Enregistrer cette ligne" aria-label="Enregistrer cette ligne"
                                class="grid h-9 w-9 place-items-center rounded-full bg-brand-700 text-white shadow-sm transition hover:bg-brand-800">
                                <x-icon name="check" :size="16" />
                            </button>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="mt-6">
            {{ $products->links() }}
        </div>
    @else
        <x-empty-state class="mt-4" icon="boxes" title="Aucun produit" description="Aucun produit ne correspond a ces criteres." />
    @endif

    {{-- Barre d'enregistrement --}}
    <div x-show="dirtyIds.length" x-cloak
        x-transition:enter="transition-transform duration-200 ease-out" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
        x-transition:leave="transition-transform duration-150 ease-in" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
        class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white/95 p-3 shadow-[0_-8px_24px_-12px_rgba(15,23,42,0.25)] backdrop-blur lg:left-64">
        <div class="mx-auto flex max-w-3xl items-center justify-between gap-3">
            <p class="text-sm text-slate-600">
                <span class="font-extrabold text-slate-900" x-text="dirtyIds.length"></span>
                modification(s) non enregistree(s)
            </p>
            <div class="flex gap-2">
                <x-btn variant="secondary" size="sm" x-on:click="reset()">Annuler</x-btn>
                <x-btn variant="accent" size="sm" x-on:click="save()" wire:loading.attr="disabled" wire:target="bulkUpdate">
                    <x-icon name="save" :size="15" />
                    Enregistrer
                </x-btn>
            </div>
        </div>
    </div>
</div>
