@php
    $item = $packData;
@endphp

<div>
    <x-shop.page-hero
        eyebrow="Pack pret a commander"
        :title="$item['name']"
        :subtitle="count($item['items']).' produit(s) reunis a prix groupe.'"
        :breadcrumbs="[['label' => 'Packs', 'href' => route('packs.index')], ['label' => $item['name']]]"
    />

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-5 lg:gap-10">
            <div class="lg:col-span-3">
                <div class="relative overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
                    <x-thumb :src="$item['image']" :alt="$item['name']" :label="$item['name']" ratio="banner" rounded="rounded-none" />
                    @if ($item['offer'])
                        <span class="absolute right-4 top-4 rounded-full bg-accent-500 px-3 py-1 text-xs font-bold text-white shadow" title="{{ $item['offer']['name'] }}">{{ $item['offer']['badge'] }}</span>
                    @endif
                </div>

                @if ($item['description'])
                    <p class="mt-6 text-sm leading-relaxed text-slate-600 sm:text-base">{{ $item['description'] }}</p>
                @endif

                <h2 class="mt-8 flex items-center gap-2 text-xs font-bold uppercase tracking-[0.2em] text-slate-900">
                    <x-icon name="layers" :size="16" class="text-brand-600" />
                    Contenu du pack ({{ count($item['items']) }} produit(s))
                </h2>

                <ul class="mt-4 divide-y divide-slate-100 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    @foreach ($item['items'] as $packItem)
                        <li wire:key="component-{{ $packItem['product_id'] }}" class="flex items-center gap-4 p-3 transition hover:bg-slate-50 sm:p-4">
                            <div class="w-14 shrink-0 sm:w-16">
                                <x-thumb :src="$packItem['image']" :alt="$packItem['name']" :label="$packItem['name']" rounded="rounded-xl" />
                            </div>

                            <div class="min-w-0 flex-1">
                                <a href="{{ route('catalog.show', $packItem['slug']) }}" wire:navigate class="block truncate text-sm font-bold text-slate-900 hover:text-brand-700">{{ $packItem['name'] }}</a>
                                <p class="text-xs text-slate-500">{{ $packItem['unit_price_label'] }} l'unite</p>
                            </div>

                            <span class="shrink-0 rounded-full bg-brand-50 px-3 py-1 text-sm font-extrabold text-brand-700">&times;{{ $packItem['quantity'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Achat --}}
            <div class="lg:col-span-2">
                <div class="overflow-hidden rounded-2xl bg-white shadow-lg shadow-navy-900/5 ring-1 ring-slate-200 lg:sticky lg:top-24">
                    <div class="h-1.5 bg-gradient-to-r from-brand-600 to-cyan-accent"></div>
                    <div class="p-6">
                        <x-shop.stock-badge :label="$item['stock_label']" :color="$item['stock_color']" />

                        <div class="mt-4 flex flex-wrap items-baseline gap-2">
                            <span class="text-4xl font-extrabold tracking-tight text-navy-900">{{ $item['price_label'] }}</span>
                            @if ($item['savings'] > 0)
                                <span class="text-sm text-slate-400 line-through">{{ $item['components_total_label'] }}</span>
                            @endif
                        </div>

                        @if ($item['savings_label'])
                            <p class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-1.5 text-sm font-semibold text-emerald-700">
                                <x-icon name="sparkles" :size="15" />
                                Vous economisez {{ $item['savings_label'] }}
                            </p>
                        @endif

                        <div class="mt-6">
                            <livewire:add-to-cart
                                kind="pack"
                                :item-id="$item['id']"
                                :max-quantity="$item['max_quantity']"
                                :orderable="$item['orderable']"
                                label="Ajouter le pack"
                                :block="true"
                                :key="'detail-k-'.$item['id']"
                            />
                        </div>

                        @if ($item['orderable'])
                            <p class="mt-3 text-xs text-slate-500">{{ $item['max_quantity'] }} pack(s) disponible(s) selon le stock des produits.</p>
                        @else
                            <p class="mt-3 text-xs text-slate-500">
                                Un ou plusieurs produits de ce pack sont indisponibles. Composez votre
                                propre pack a partir des produits en stock.
                            </p>
                        @endif

                        <x-btn :href="route('custom-pack.create')" variant="secondary" size="sm" :block="true" class="mt-4">
                            <x-icon name="sparkles" :size="16" />
                            Composer mon pack
                        </x-btn>

                        <ul class="mt-6 space-y-2.5 border-t border-slate-100 pt-5 text-sm text-slate-600">
                            <li class="flex items-center gap-2.5"><x-icon name="user" :size="16" class="text-brand-600" /> Sans compte a creer</li>
                            <li class="flex items-center gap-2.5"><x-icon name="whatsapp" :size="16" class="text-brand-600" /> Commande finalisee sur WhatsApp</li>
                            <li class="flex items-center gap-2.5"><x-icon name="shield" :size="16" class="text-brand-600" /> Paiement a la livraison</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        @if ($others->isNotEmpty())
            <section class="mt-16">
                <x-section-heading eyebrow="A decouvrir" title="Autres packs" />
                <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($others as $other)
                        <x-shop.pack-card :pack="$other" key-prefix="other-" wire:key="other-pack-{{ $other['id'] }}" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>
