<div>
    <x-admin.page-header title="Produits" subtitle="{{ $products->total() }} fiche(s) produit">
        <x-btn variant="accent" size="sm" :href="route('admin.products.create')">
            <x-icon name="plus" :size="16" />
            Nouveau produit
        </x-btn>
    </x-admin.page-header>

    {{-- Filtres --}}
    <div class="flex flex-col gap-3 rounded-2xl bg-white p-3 shadow-sm ring-1 ring-slate-200 sm:flex-row sm:items-center sm:p-4">
        <div class="relative flex-1">
            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                <x-icon name="search" :size="16" />
            </span>
            <x-input type="search" wire:model.live.debounce.350ms="q" placeholder="Nom, reference, description…" class="pl-9" aria-label="Rechercher un produit" />
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

    @if ($products->isNotEmpty())
        <div class="mt-4" wire:loading.class="opacity-60" wire:target="q, category, state, gotoPage, nextPage, previousPage">
            {{-- Tableau (desktop) --}}
            <div class="hidden overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 lg:block">
                <table class="w-full">
                    <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="px-4 py-3">Produit</th>
                            <th scope="col" class="px-4 py-3">Categorie</th>
                            <th scope="col" class="px-4 py-3 text-right">Prix</th>
                            <th scope="col" class="px-4 py-3 text-right">Stock</th>
                            <th scope="col" class="px-4 py-3">Etat</th>
                            <th scope="col" class="px-4 py-3 text-center">Publie</th>
                            <th scope="col" class="px-4 py-3 text-center">En vente</th>
                            <th scope="col" class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($products as $product)
                            <tr wire:key="product-row-{{ $product['id'] }}" class="transition hover:bg-brand-50/40">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-11 shrink-0">
                                            <x-thumb :src="$product['image']" :alt="$product['name']" :label="$product['name']" rounded="rounded-lg" />
                                        </div>
                                        <div class="min-w-0">
                                            <a href="{{ route('admin.products.edit', $product['id']) }}" wire:navigate class="block max-w-xs truncate text-sm font-bold text-slate-900 hover:text-brand-700">
                                                {{ $product['name'] }}
                                            </a>
                                            @if ($product['sku'])
                                                <p class="text-xs text-slate-400">{{ $product['sku'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $product['category']['name'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-right text-sm font-bold tabular-nums text-slate-900">{{ $product['price_label'] }}</td>
                                <td class="px-4 py-3 text-right text-sm tabular-nums text-slate-700">{{ $product['stock'] }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1.5">
                                        <x-badge :color="$product['stock_color']" dot>{{ $product['stock_label'] }}</x-badge>
                                        @if ($product['occasion'])
                                            <x-badge :color="$product['occasion']['status'] === 'running' ? 'indigo' : 'slate'">{{ $product['occasion']['name'] }}</x-badge>
                                        @endif
                                        @if ($product['offer'])
                                            <x-badge color="accent">{{ $product['offer']['badge'] }} · {{ $product['sale_price_label'] }}</x-badge>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button type="button" wire:click="togglePublish({{ $product['id'] }})" role="switch" aria-checked="{{ $product['is_published'] ? 'true' : 'false' }}"
                                        title="{{ $product['is_published'] ? 'Depublier' : 'Publier' }}" aria-label="{{ $product['is_published'] ? 'Depublier' : 'Publier' }} {{ $product['name'] }}"
                                        class="relative inline-flex h-6 w-11 shrink-0 rounded-full transition {{ $product['is_published'] ? 'bg-brand-600' : 'bg-slate-300' }}">
                                        <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition {{ $product['is_published'] ? 'translate-x-5' : '' }}"></span>
                                    </button>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button type="button" wire:click="toggleAvailability({{ $product['id'] }})" role="switch" aria-checked="{{ $product['is_available'] ? 'true' : 'false' }}"
                                        title="{{ $product['is_available'] ? 'Marquer indisponible' : 'Remettre en vente' }}" aria-label="{{ $product['is_available'] ? 'Marquer indisponible' : 'Remettre en vente' }} {{ $product['name'] }}"
                                        class="relative inline-flex h-6 w-11 shrink-0 rounded-full transition {{ $product['is_available'] ? 'bg-emerald-500' : 'bg-slate-300' }}">
                                        <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition {{ $product['is_available'] ? 'translate-x-5' : '' }}"></span>
                                    </button>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('admin.products.edit', $product['id']) }}" wire:navigate title="Modifier" aria-label="Modifier"
                                            class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 transition hover:bg-brand-50 hover:text-brand-700">
                                            <x-icon name="edit" :size="16" />
                                        </a>
                                        <button type="button" title="Supprimer" aria-label="Supprimer"
                                            wire:click="destroy({{ $product['id'] }})"
                                            wire:confirm="Supprimer « {{ $product['name'] }} » ? La fiche sera retiree du catalogue. Les commandes passees restent intactes."
                                            class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 transition hover:bg-red-50 hover:text-red-600">
                                            <x-icon name="trash" :size="16" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Cartes (mobile) --}}
            <ul class="space-y-3 lg:hidden">
                @foreach ($products as $product)
                    <li wire:key="product-card-{{ $product['id'] }}" class="rounded-2xl bg-white p-3 shadow-sm ring-1 ring-slate-200">
                        <div class="flex gap-3">
                            <div class="w-16 shrink-0">
                                <x-thumb :src="$product['image']" :alt="$product['name']" :label="$product['name']" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('admin.products.edit', $product['id']) }}" wire:navigate class="block truncate text-sm font-bold text-slate-900">{{ $product['name'] }}</a>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $product['category']['name'] ?? 'Sans categorie' }}</p>
                                <p class="mt-1 text-sm font-bold text-slate-900">
                                    {{ $product['price_label'] }}
                                    <span class="ml-1 text-xs font-normal text-slate-500">· stock {{ $product['stock'] }}</span>
                                </p>
                                <div class="mt-1.5 flex flex-wrap gap-1.5">
                                    <x-badge :color="$product['stock_color']" dot>{{ $product['stock_label'] }}</x-badge>
                                    <x-badge :color="$product['is_published'] ? 'brand' : 'slate'">{{ $product['is_published'] ? 'Publie' : 'Brouillon' }}</x-badge>
                                    @if ($product['occasion'])
                                        <x-badge :color="$product['occasion']['status'] === 'running' ? 'indigo' : 'slate'">{{ $product['occasion']['name'] }}</x-badge>
                                    @endif
                                    @if ($product['offer'])
                                        <x-badge color="accent">{{ $product['offer']['badge'] }}</x-badge>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-2 border-t border-slate-100 pt-3">
                            <x-btn variant="secondary" size="xs" wire:click="togglePublish({{ $product['id'] }})">
                                <x-icon :name="$product['is_published'] ? 'eye-off' : 'eye'" :size="14" />
                                {{ $product['is_published'] ? 'Depublier' : 'Publier' }}
                            </x-btn>
                            <x-btn variant="secondary" size="xs" wire:click="toggleAvailability({{ $product['id'] }})">
                                <x-icon :name="$product['is_available'] ? 'close' : 'check'" :size="14" />
                                {{ $product['is_available'] ? 'Indisponible' : 'Disponible' }}
                            </x-btn>
                            <x-btn variant="secondary" size="xs" :href="route('admin.products.edit', $product['id'])">
                                <x-icon name="edit" :size="14" />
                                Modifier
                            </x-btn>
                            <x-btn variant="danger-soft" size="xs" class="ml-auto" aria-label="Supprimer"
                                wire:click="destroy({{ $product['id'] }})"
                                wire:confirm="Supprimer « {{ $product['name'] }} » ? La fiche sera retiree du catalogue.">
                                <x-icon name="trash" :size="14" />
                            </x-btn>
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="mt-6">
                {{ $products->links() }}
            </div>
        </div>
    @else
        <x-empty-state class="mt-4" icon="box" title="Aucun produit"
            :description="$q !== '' || $category !== '' || $state !== 'all' ? 'Aucun produit ne correspond a ces criteres.' : 'Creez votre premiere fiche produit pour commencer a vendre.'">
            <x-btn variant="accent" size="sm" :href="route('admin.products.create')">
                <x-icon name="plus" :size="16" />
                Nouveau produit
            </x-btn>
        </x-empty-state>
    @endif
</div>
