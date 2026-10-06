<div x-data="{ filtersOpen: false }">
    <x-shop.page-hero
        eyebrow="Boutique en ligne"
        title="Catalogue"
        :subtitle="$filters['q'] !== '' ? $products->total().' produit(s) pour « '.$filters['q'].' »' : $products->total().' produit(s) a decouvrir, livres chez vous.'"
        :breadcrumbs="[['label' => 'Catalogue']]"
    >
        {{-- Recherche rapide --}}
        <div class="mt-6 max-w-xl">
            <label for="catalog-search" class="sr-only">Rechercher</label>
            <div class="relative">
                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"><x-icon name="search" :size="20" /></span>
                <input
                    id="catalog-search"
                    type="search"
                    wire:model.live.debounce.350ms="q"
                    placeholder="Nom, reference…"
                    class="w-full rounded-full border-0 bg-white py-3.5 pl-12 pr-4 text-sm text-slate-900 shadow-lg shadow-navy-950/20 placeholder:text-slate-400 focus:ring-4 focus:ring-cyan-accent/40"
                />
                <span wire:loading.delay wire:target="q" class="absolute right-4 top-1/2 -translate-y-1/2 text-brand-600">
                    <x-icon name="refresh" :size="18" class="animate-spin" />
                </span>
            </div>
        </div>
    </x-shop.page-hero>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-slate-600">
                <span class="font-extrabold text-slate-900">{{ $products->total() }}</span> produit(s)
                @if ($filters['q'] !== '')
                    pour « {{ $filters['q'] }} »
                @endif
            </p>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 lg:hidden"
                    x-on:click="filtersOpen = ! filtersOpen"
                >
                    <x-icon name="filter" :size="16" />
                    Filtres
                    @if ($activeCount)
                        <span class="grid h-5 min-w-5 place-items-center rounded-full bg-brand-700 px-1 text-[11px] font-bold text-white">{{ $activeCount }}</span>
                    @endif
                </button>

                <label class="sr-only" for="sort">Trier</label>
                <x-select id="sort" wire:model.live="sort" class="!w-auto !rounded-full">
                    @foreach ($sortOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-select>
            </div>
        </div>

        <div class="mt-6 flex flex-col gap-8 lg:flex-row">
            {{-- Filtres --}}
            <aside class="hidden w-full shrink-0 lg:block lg:w-64" x-bind:class="filtersOpen && '!block'">
                <div class="space-y-6 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 lg:sticky lg:top-24">
                    @if ($categories->isNotEmpty())
                        <div>
                            <h2 class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.16em] text-slate-900">
                                <x-icon name="tag" :size="15" class="text-brand-600" />
                                Categorie
                            </h2>
                            <div class="mt-3 space-y-1">
                                <button
                                    type="button"
                                    wire:click="selectCategory('')"
                                    class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm transition {{ $filters['category'] === '' ? 'bg-brand-50 font-bold text-brand-800' : 'text-slate-600 hover:bg-slate-100' }}"
                                >
                                    Toutes
                                </button>
                                @foreach ($categories as $category)
                                    <button
                                        type="button"
                                        wire:key="filter-category-{{ $category->id }}"
                                        wire:click="selectCategory(@js($category->slug))"
                                        class="flex w-full items-center justify-between gap-2 rounded-lg px-3 py-2 text-left text-sm transition {{ $filters['category'] === $category->slug ? 'bg-brand-50 font-bold text-brand-800' : 'text-slate-600 hover:bg-slate-100' }}"
                                    >
                                        <span class="truncate">{{ $category->name }}</span>
                                        <span class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-500">{{ $category->products_count }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div>
                        <h2 class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.16em] text-slate-900">
                            <x-icon name="box" :size="15" class="text-brand-600" />
                            Disponibilite
                        </h2>
                        <div class="mt-3 space-y-2">
                            @foreach ($availabilityOptions as $value => $label)
                                <label class="flex cursor-pointer items-center gap-2.5 text-sm text-slate-600">
                                    <input type="radio" wire:model.live="availability" value="{{ $value }}" class="h-4 w-4 border-slate-300 text-brand-700 focus:ring-brand-600" />
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <h2 class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.16em] text-slate-900">
                            <x-icon name="chart" :size="15" class="text-brand-600" />
                            Prix
                        </h2>
                        <p class="mt-1 text-xs text-slate-500">
                            de {{ \App\Support\Money::format($priceRange['min']) }} a {{ \App\Support\Money::format($priceRange['max']) }}
                        </p>
                        <div class="mt-3 flex items-center gap-2">
                            <x-input type="number" min="0" placeholder="Min" wire:model.live.debounce.600ms="minPrice" aria-label="Prix minimum" />
                            <span class="text-slate-400">&ndash;</span>
                            <x-input type="number" min="0" placeholder="Max" wire:model.live.debounce.600ms="maxPrice" aria-label="Prix maximum" />
                        </div>
                    </div>

                    @if ($activeCount)
                        <button type="button" wire:click="resetFilters" class="flex w-full items-center justify-center gap-1.5 rounded-full py-2 text-sm font-semibold text-slate-500 ring-1 ring-inset ring-slate-200 transition hover:bg-slate-50 hover:text-slate-800">
                            <x-icon name="close" :size="14" />
                            Reinitialiser les filtres
                        </button>
                    @endif
                </div>
            </aside>

            {{-- Grille --}}
            <div class="relative min-w-0 flex-1">
                <div wire:loading.delay.class="opacity-50" class="transition-opacity">
                    @if ($products->count())
                        <div class="grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-3 xl:grid-cols-4">
                            @foreach ($products as $product)
                                <x-shop.product-card :product="$product" wire:key="catalog-product-{{ $product['id'] }}" />
                            @endforeach
                        </div>

                        <div class="mt-10">
                            {{ $products->links() }}
                        </div>
                    @else
                        <x-empty-state icon="search" title="Aucun produit ne correspond" description="Essayez d'elargir votre recherche ou de retirer un filtre.">
                            <x-btn variant="secondary" size="sm" wire:click="resetFilters">Reinitialiser</x-btn>
                        </x-empty-state>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
