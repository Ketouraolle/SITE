@php
    $item = $productData;
    $images = $item['images'];
    $firstImage = $images[0]['url'] ?? $item['image'];
    $breadcrumbs = [['label' => 'Catalogue', 'href' => route('catalog.index')]];
    if ($item['category']) {
        $breadcrumbs[] = ['label' => $item['category']['name'], 'href' => route('catalog.index', ['category' => $item['category']['slug']])];
    }
    $breadcrumbs[] = ['label' => $item['name']];
@endphp

<div>
    {{-- Fil d'Ariane --}}
    <div class="border-b border-slate-200 bg-white">
        <nav class="mx-auto flex max-w-7xl flex-wrap items-center gap-1.5 px-4 py-3 text-xs text-slate-500 sm:px-6 lg:px-8" aria-label="Fil d'Ariane">
            <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-1 hover:text-brand-700">
                <x-icon name="home" :size="13" />
                Accueil
            </a>
            @foreach ($breadcrumbs as $crumb)
                <x-icon name="chevron-right" :size="12" class="text-slate-300" />
                @if ($crumb['href'] ?? null)
                    <a href="{{ $crumb['href'] }}" wire:navigate class="hover:text-brand-700">{{ $crumb['label'] }}</a>
                @else
                    <span class="truncate font-semibold text-slate-700">{{ $crumb['label'] }}</span>
                @endif
            @endforeach
        </nav>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-2 lg:gap-12">
            {{-- Galerie --}}
            <div x-data="{ active: @js($firstImage) }">
                <div class="relative overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
                    @if ($firstImage)
                        <div class="aspect-square w-full bg-slate-100">
                            <img src="{{ $firstImage }}" x-bind:src="active" alt="{{ $item['name'] }}" class="h-full w-full object-cover" />
                        </div>
                    @else
                        <x-thumb :src="null" :alt="$item['name']" :label="$item['name']" rounded="rounded-none" />
                    @endif

                    @if ($item['offer'])
                        <span class="absolute left-4 top-4 rounded-full bg-accent-500 px-3 py-1 text-xs font-bold text-white shadow" title="{{ $item['offer']['name'] }}">{{ $item['offer']['badge'] }}</span>
                    @elseif ($item['compare_at_price'])
                        <span class="absolute left-4 top-4 rounded-full bg-accent-500 px-3 py-1 text-xs font-bold text-white shadow">Promo</span>
                    @endif
                </div>

                @if (count($images) > 1)
                    <div class="no-scrollbar mt-3 flex gap-2 overflow-x-auto pb-1">
                        @foreach ($images as $image)
                            <button
                                type="button"
                                wire:key="image-{{ $image['id'] }}"
                                class="w-20 shrink-0 overflow-hidden rounded-xl ring-2 transition"
                                x-bind:class="active === @js($image['url']) ? 'ring-brand-600' : 'ring-slate-200 hover:ring-slate-300'"
                                x-on:click="active = @js($image['url'])"
                                aria-label="Afficher la photo {{ $loop->iteration }}"
                            >
                                <x-thumb :src="$image['url']" :alt="$image['alt']" rounded="rounded-none" />
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Informations --}}
            <div>
                @if ($item['category'])
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand-600">{{ $item['category']['name'] }}</p>
                @endif

                <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">{{ $item['name'] }}</h1>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <x-shop.stock-badge :label="$item['stock_label']" :color="$item['stock_color']" />
                    @if ($item['sku'])
                        <span class="text-xs text-slate-500">Ref. {{ $item['sku'] }}</span>
                    @endif
                </div>

                <div class="mt-6 flex flex-wrap items-baseline gap-3">
                    <span class="text-4xl font-extrabold tracking-tight text-navy-900">{{ $item['price_label'] }}</span>
                    @if ($item['compare_at_price_label'])
                        <span class="text-base text-slate-400 line-through">{{ $item['compare_at_price_label'] }}</span>
                    @endif
                    @if ($item['compare_at_price'])
                        <span class="rounded-full bg-accent-50 px-2.5 py-0.5 text-xs font-bold text-accent-700 ring-1 ring-inset ring-accent-200">Promo</span>
                    @endif
                </div>

                @if ($item['offer'])
                    <p class="mt-2 inline-flex items-center gap-1.5 text-sm font-semibold text-accent-700">
                        <x-icon name="gift" :size="16" />
                        {{ $item['offer']['name'] }}
                        @if ($item['offer']['ends_on_label'])
                            <span class="font-normal text-slate-500">&middot; jusqu'au {{ $item['offer']['ends_on_label'] }}</span>
                        @endif
                    </p>
                @endif

                @if ($item['short_description'])
                    <p class="mt-5 text-sm leading-relaxed text-slate-700 sm:text-base">{{ $item['short_description'] }}</p>
                @endif

                {{-- Achat --}}
                <div class="mt-6 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6">
                    @if ($item['orderable'])
                        <p class="mb-3 text-sm text-slate-600">
                            <span class="font-bold text-slate-900">{{ $item['max_quantity'] }}</span>
                            exemplaire(s) disponible(s)
                        </p>
                        <livewire:add-to-cart
                            kind="product"
                            :item-id="$item['id']"
                            :max-quantity="$item['max_quantity']"
                            :orderable="$item['orderable']"
                            size="md"
                            :block="true"
                            :key="'detail-p-'.$item['id']"
                        />
                    @else
                        <div class="flex items-start gap-3">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-slate-100 text-slate-500">
                                <x-icon name="alert" :size="18" />
                            </span>
                            <div>
                                <p class="text-sm font-bold text-slate-900">{{ $item['stock_label'] }}</p>
                                <p class="mt-0.5 text-sm text-slate-600">
                                    Ce produit n'est pas commandable pour le moment. Ecrivez-nous
                                    pour etre prevenu de son retour.
                                </p>
                            </div>
                        </div>
                    @endif

                    <div class="mt-4 flex flex-wrap gap-2">
                        @if ($whatsappUrl)
                            <x-btn :href="$whatsappUrl" external variant="whatsapp" size="sm">
                                <x-icon name="whatsapp" :size="16" />
                                Poser une question
                            </x-btn>
                        @endif
                        <x-btn :href="route('custom-pack.create')" variant="secondary" size="sm">
                            <x-icon name="sparkles" :size="16" />
                            Ajouter a un pack
                        </x-btn>
                    </div>
                </div>

                {{-- Garanties --}}
                <ul class="mt-5 grid gap-3 sm:grid-cols-3">
                    @foreach ([['icon' => 'user', 'label' => 'Sans compte'], ['icon' => 'whatsapp', 'label' => 'Commande via WhatsApp'], ['icon' => 'shield', 'label' => 'Paiement a la livraison']] as $perk)
                        <li class="flex items-center gap-2.5 rounded-xl bg-brand-50/70 px-3 py-2.5 text-xs font-semibold text-brand-900">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-gradient-to-br from-brand-600 to-cyan-accent text-white"><x-icon :name="$perk['icon']" :size="15" /></span>
                            {{ $perk['label'] }}
                        </li>
                    @endforeach
                </ul>

                {{-- Description --}}
                @if ($item['description'])
                    <div class="mt-8">
                        <h2 class="text-xs font-bold uppercase tracking-[0.2em] text-slate-900">Description</h2>
                        <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $item['description'] }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Produits lies --}}
        @if ($related->isNotEmpty())
            <section class="mt-16">
                <x-section-heading eyebrow="Dans le meme esprit" title="Vous aimerez aussi" />
                <div class="mt-8 grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-4">
                    @foreach ($related as $relatedProduct)
                        <x-shop.product-card :product="$relatedProduct" key-prefix="related-" wire:key="related-{{ $relatedProduct['id'] }}" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>
