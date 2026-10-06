@props(['product', 'keyPrefix' => ''])

{{-- Carte produit : grilles du catalogue, accueil, produits lies. --}}
<article {{ $attributes->merge(['class' => 'group flex h-full flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-brand-900/10 hover:ring-brand-200']) }}>
    <a href="{{ route('catalog.show', $product['slug']) }}" wire:navigate class="group/thumb relative block overflow-hidden">
        <x-thumb :src="$product['image']" :alt="$product['name']" :label="$product['name']" rounded="rounded-none" />

        @if ($product['offer'])
            <span class="absolute left-2.5 top-2.5 rounded-full bg-accent-500 px-2.5 py-0.5 text-[11px] font-bold text-white shadow" title="{{ $product['offer']['name'] }}">
                {{ $product['offer']['badge'] }}
            </span>
        @elseif ($product['compare_at_price'])
            <span class="absolute left-2.5 top-2.5 rounded-full bg-accent-500 px-2.5 py-0.5 text-[11px] font-bold text-white shadow">Promo</span>
        @endif

        @if ($product['is_featured'] ?? false)
            <span class="absolute right-2.5 top-2.5 grid h-7 w-7 place-items-center rounded-full bg-white/90 text-brand-600 shadow backdrop-blur" title="Produit vedette">
                <x-icon name="star" :size="14" filled />
            </span>
        @endif

        @if ($product['orderable'])
            <span class="absolute inset-x-3 bottom-3 flex translate-y-3 items-center justify-center gap-1.5 rounded-full bg-navy-900/85 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-white opacity-0 shadow backdrop-blur transition duration-300 group-hover/thumb:translate-y-0 group-hover/thumb:opacity-100">
                <x-icon name="eye" :size="14" />
                Decouvrir
            </span>
        @else
            {{-- Un produit non commandable reste visible, mais signale sans ambiguite. --}}
            <div class="absolute inset-0 grid place-items-center bg-white/70 backdrop-blur-[1px]">
                <span class="rounded-full bg-navy-900/85 px-3 py-1 text-xs font-bold text-white">{{ $product['stock_label'] }}</span>
            </div>
        @endif
    </a>

    <div class="flex flex-1 flex-col p-3.5 sm:p-4">
        @if ($product['category'])
            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-brand-600">{{ $product['category']['name'] }}</p>
        @endif

        <h3 class="mt-1 text-sm font-bold leading-snug text-slate-900 sm:text-base">
            <a href="{{ route('catalog.show', $product['slug']) }}" wire:navigate class="transition hover:text-brand-700">{{ $product['name'] }}</a>
        </h3>

        @if ($product['short_description'])
            <p class="mt-1 line-clamp-2 text-xs text-slate-500">{{ $product['short_description'] }}</p>
        @endif

        <div class="mt-3 flex flex-wrap items-baseline gap-x-2 gap-y-1">
            <span class="text-base font-extrabold tracking-tight text-navy-900 sm:text-lg">{{ $product['price_label'] }}</span>
            @if ($product['compare_at_price_label'])
                <span class="text-xs text-slate-400 line-through">{{ $product['compare_at_price_label'] }}</span>
            @endif
        </div>

        @if ($product['offer'])
            <p class="mt-1 text-[11px] font-semibold text-accent-700">{{ $product['offer']['name'] }}</p>
        @endif

        <div class="mt-2">
            <x-shop.stock-badge :label="$product['stock_label']" :color="$product['stock_color']" />
        </div>

        <div class="mt-auto pt-3.5">
            <livewire:add-to-cart
                kind="product"
                :item-id="$product['id']"
                :max-quantity="$product['max_quantity']"
                :orderable="$product['orderable']"
                :with-quantity="false"
                size="sm"
                :block="true"
                :key="$keyPrefix.'p-'.$product['id']"
            />
        </div>
    </div>
</article>
