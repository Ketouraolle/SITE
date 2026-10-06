@php
    $itemErrors = collect($errors->getMessages())
        ->filter(fn ($messages, $key) => str_starts_with($key, 'items'))
        ->flatten()
        ->unique()
        ->values();
@endphp

<div x-data="{ panelOpen: false }">
    {{-- Bandeau --}}
    <section class="bg-hero text-white">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
            <nav class="flex items-center gap-1.5 text-xs font-semibold text-brand-100/80" aria-label="Fil d'Ariane">
                <a href="{{ route('home') }}" wire:navigate class="transition hover:text-white">Accueil</a>
                <x-icon name="chevron-right" :size="12" />
                <span class="text-white">Mon pack</span>
            </nav>
            <div class="mt-4 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-2xl">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-accent">Pack sur mesure</p>
                    <h1 class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">Composez votre pack</h1>
                    <p class="mt-3 text-sm text-brand-100 sm:text-base">
                        Selectionnez entre {{ $limits['min_products'] }} et {{ $limits['max_products'] }} produits disponibles,
                        ajustez les quantites, et ajoutez l'ensemble a votre panier en une seule ligne.
                    </p>
                </div>
                <ol class="grid grid-cols-3 gap-2 text-center text-xs font-semibold text-brand-100 sm:gap-3 lg:w-[26rem]">
                    @foreach ([['icon' => 'search', 'text' => 'Choisissez'], ['icon' => 'plus', 'text' => 'Ajustez'], ['icon' => 'cart', 'text' => 'Commandez']] as $tip)
                        <li class="rounded-2xl bg-white/10 px-2 py-3 ring-1 ring-inset ring-white/15 backdrop-blur">
                            <span class="mx-auto grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-brand-500 to-cyan-accent text-white">
                                <x-icon :name="$tip['icon']" :size="17" />
                            </span>
                            <span class="mt-2 block">{{ $loop->iteration }}. {{ $tip['text'] }}</span>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @if ($itemErrors->isNotEmpty())
            <div class="mb-6 rounded-2xl bg-red-50 p-4 ring-1 ring-inset ring-red-200">
                <ul class="space-y-1 text-sm font-medium text-red-700">
                    @foreach ($itemErrors as $message)
                        <li class="flex items-start gap-2">
                            <x-icon name="alert" :size="15" class="mt-0.5 shrink-0" />
                            {{ $message }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="flex gap-8">
            {{-- Produits --}}
            <div class="min-w-0 flex-1">
                <div class="flex flex-col gap-3 rounded-2xl bg-white p-3 shadow-sm ring-1 ring-slate-200 sm:flex-row">
                    <div class="relative flex-1">
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"><x-icon name="search" :size="16" /></span>
                        <input type="search" wire:model.live.debounce.350ms="q" placeholder="Rechercher parmi les produits disponibles…" aria-label="Rechercher un produit"
                            class="w-full rounded-full border-0 bg-slate-100 py-2.5 pl-9 pr-3 text-sm ring-1 ring-inset ring-transparent placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-brand-600" />
                    </div>
                    <select wire:model.live="category" aria-label="Filtrer par categorie"
                        class="rounded-full border-0 bg-slate-100 py-2.5 pl-4 pr-10 text-sm font-semibold text-slate-700 ring-1 ring-inset ring-transparent focus:bg-white focus:ring-2 focus:ring-brand-600">
                        <option value="">Toutes les categories</option>
                        @foreach ($categories as $item)
                            <option value="{{ $item->slug }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>

                @if ($products->isNotEmpty())
                    <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                        @foreach ($products as $product)
                            @php
                                $selectedIndex = $selectedIndexes[$product['id']] ?? null;
                                $isSelected = $selectedIndex !== null;
                            @endphp
                            <li wire:key="product-{{ $product['id'] }}" @class([
                                'flex items-center gap-3 rounded-2xl bg-white p-3 shadow-sm ring-1 transition sm:p-4',
                                'ring-2 ring-brand-500 bg-brand-50/40' => $isSelected,
                                'ring-slate-200 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-brand-900/10 hover:ring-brand-200' => ! $isSelected,
                            ])>
                                <div class="relative w-16 shrink-0 sm:w-20">
                                    <x-thumb :src="$product['image']" :alt="$product['name']" :label="$product['name']" rounded="rounded-xl" />
                                    @if ($isSelected)
                                        <span class="absolute -right-1.5 -top-1.5 grid h-6 w-6 place-items-center rounded-full bg-brand-600 text-white shadow ring-2 ring-white">
                                            <x-icon name="check" :size="13" />
                                        </span>
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-bold text-slate-900">{{ $product['name'] }}</p>
                                    <p class="mt-0.5 text-sm font-extrabold text-brand-700">{{ $product['price_label'] }}</p>
                                    <x-badge :color="$product['stock_color']" dot class="mt-1.5">{{ $product['stock_label'] }}</x-badge>
                                </div>

                                <div class="flex shrink-0 flex-col items-end gap-2">
                                    @if ($isSelected)
                                        <x-quantity-stepper wire:model.live="items.{{ $selectedIndex }}.quantity" :max="max(1, $product['max_quantity'])" size="sm"
                                            wire:key="stepper-{{ $product['id'] }}-{{ $selectedIndex }}" />
                                    @endif

                                    <button type="button" wire:click="toggle({{ $product['id'] }})" wire:loading.attr="disabled" @class([
                                        'inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-bold transition',
                                        'bg-red-50 text-red-700 hover:bg-red-100' => $isSelected,
                                        'bg-brand-700 text-white shadow-sm hover:bg-brand-800' => ! $isSelected,
                                    ])>
                                        <x-icon :name="$isSelected ? 'close' : 'plus'" :size="14" />
                                        {{ $isSelected ? 'Retirer' : 'Ajouter' }}
                                    </button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <x-empty-state icon="search" title="Aucun produit disponible ne correspond"
                        description="Modifiez votre recherche ou choisissez une autre categorie." class="mt-4" />
                @endif
            </div>

            {{-- Recapitulatif (desktop) --}}
            <aside class="hidden w-96 shrink-0 lg:block">
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 lg:sticky lg:top-28">
                    <div class="h-1 bg-gradient-to-r from-brand-600 via-cyan-accent to-accent-500"></div>
                    <div class="p-5">
                        <div class="flex items-center gap-3">
                            <span class="grid h-11 w-11 place-items-center rounded-xl bg-gradient-to-br from-brand-600 to-cyan-accent text-white shadow-sm shadow-brand-700/30">
                                <x-icon name="gift" :size="20" />
                            </span>
                            <h2 class="text-lg font-extrabold tracking-tight text-slate-900">
                                Mon pack <span class="ml-1 text-sm font-semibold text-slate-400">({{ $count }})</span>
                            </h2>
                        </div>

                        @if ($count)
                            <div class="mt-4 max-h-72 space-y-2 overflow-y-auto pr-1">
                                @foreach ($lines as $line)
                                    <div wire:key="summary-{{ $line['product']['id'] }}" class="flex items-start gap-2 rounded-xl bg-slate-50 p-2.5">
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-xs font-bold text-slate-900">{{ $line['product']['name'] }}</p>
                                            <p class="text-xs text-slate-500">{{ $line['quantity'] }} × {{ $line['product']['price_label'] }}</p>
                                        </div>
                                        <button type="button" class="shrink-0 rounded-full p-1 text-slate-400 transition hover:bg-red-50 hover:text-red-600"
                                            aria-label="Retirer du pack" wire:click="removeItem({{ $line['product']['id'] }})">
                                            <x-icon name="close" :size="14" />
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="mt-4 rounded-xl border border-dashed border-slate-300 p-4 text-center text-sm text-slate-500">
                                Aucun produit selectionne pour l'instant.
                            </p>
                        @endif

                        <div class="mt-5 border-t border-slate-200 pt-5">
                            @include('livewire.shop.partials.custom-pack-summary', ['context' => 'desktop'])
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    {{-- Recapitulatif (mobile) --}}
    @if ($count)
        <div class="fixed inset-x-0 bottom-[calc(3.75rem+env(safe-area-inset-bottom))] z-30 border-t border-slate-200 bg-white/95 shadow-[0_-8px_24px_-12px_rgba(15,23,42,0.25)] backdrop-blur lg:hidden">
            <button type="button" class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left" x-on:click="panelOpen = ! panelOpen">
                <span class="min-w-0">
                    <span class="block text-xs text-slate-500">{{ $count }} produit(s) · {{ $quantity }} pack(s)</span>
                    <span class="block text-lg font-extrabold tracking-tight text-navy-900">{{ $grandTotalLabel }}</span>
                </span>
                <span class="flex shrink-0 items-center gap-1.5 text-sm font-bold text-brand-700">
                    Details
                    <x-icon name="chevron-up-down" :size="16" />
                </span>
            </button>

            <div x-show="panelOpen" x-cloak class="max-h-[60vh] overflow-y-auto border-t border-slate-100 px-4 py-3">
                @foreach ($lines as $line)
                    <div wire:key="mobile-{{ $line['product']['id'] }}" class="flex items-center gap-2 py-2">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-bold text-slate-900">{{ $line['product']['name'] }}</p>
                            <p class="text-xs text-slate-500">{{ $line['product']['price_label'] }}</p>
                        </div>
                        <x-quantity-stepper wire:model.live="items.{{ $line['index'] }}.quantity" :max="max(1, $line['product']['max_quantity'])" size="sm"
                            wire:key="mobile-stepper-{{ $line['product']['id'] }}-{{ $line['index'] }}" />
                        <button type="button" class="rounded-full p-1 text-slate-400 hover:text-red-600" aria-label="Retirer du pack" wire:click="removeItem({{ $line['product']['id'] }})">
                            <x-icon name="close" :size="16" />
                        </button>
                    </div>
                @endforeach

                <div class="mt-3 border-t border-slate-200 pt-3">
                    @include('livewire.shop.partials.custom-pack-summary', ['context' => 'mobile'])
                </div>
            </div>

            <div x-show="! panelOpen" class="px-4 pb-3">
                @if (! $enoughProducts)
                    <p class="mb-2 text-xs font-medium text-amber-700">Ajoutez encore {{ $limits['min_products'] - $count }} produit(s).</p>
                @endif
                <x-btn variant="accent" block wire:click="addToCart" wire:loading.attr="disabled" wire:target="addToCart" :disabled="! $enoughProducts || $tooManyProducts">
                    <x-icon name="cart" :size="16" />
                    Ajouter au panier
                </x-btn>
            </div>
        </div>
    @endif
</div>
