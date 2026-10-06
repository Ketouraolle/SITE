@props(['pack', 'keyPrefix' => ''])

{{-- Carte d'un pack pret a commander. --}}
<article {{ $attributes->merge(['class' => 'group flex h-full flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-brand-900/10 hover:ring-brand-200']) }}>
    <a href="{{ route('packs.show', $pack['slug']) }}" wire:navigate class="group/thumb relative block overflow-hidden">
        <x-thumb :src="$pack['image']" :alt="$pack['name']" :label="$pack['name']" ratio="wide" rounded="rounded-none" />

        <span class="pointer-events-none absolute inset-0 bg-gradient-to-t from-navy-950/40 via-transparent to-transparent"></span>

        @if ($pack['offer'])
            <span class="absolute right-2.5 top-2.5 rounded-full bg-accent-500 px-2.5 py-1 text-[11px] font-bold text-white shadow" title="{{ $pack['offer']['name'] }}">
                {{ $pack['offer']['badge'] }}
            </span>
        @endif

        @if ($pack['savings_label'])
            <span class="absolute left-2.5 top-2.5 rounded-full bg-brand-700 px-2.5 py-1 text-[11px] font-bold text-white shadow">
                &minus; {{ $pack['savings_label'] }}
            </span>
        @endif

        <span class="absolute bottom-2.5 left-2.5 inline-flex items-center gap-1.5 rounded-full bg-white/90 px-2.5 py-1 text-[11px] font-bold uppercase tracking-[0.12em] text-brand-700 shadow backdrop-blur">
            <x-icon name="layers" :size="13" />
            Pack
        </span>
    </a>

    <div class="flex flex-1 flex-col p-4 sm:p-5">
        <h3 class="text-base font-bold text-slate-900 sm:text-lg">
            <a href="{{ route('packs.show', $pack['slug']) }}" wire:navigate class="transition hover:text-brand-700">{{ $pack['name'] }}</a>
        </h3>

        @if ($pack['description'])
            <p class="mt-1 line-clamp-2 text-sm text-slate-500">{{ $pack['description'] }}</p>
        @endif

        @if (count($pack['items']))
            <ul class="mt-3 space-y-1.5">
                @foreach (array_slice($pack['items'], 0, 4) as $item)
                    <li class="flex items-center gap-2 text-xs text-slate-600">
                        <span class="grid h-4 w-4 shrink-0 place-items-center rounded-full bg-brand-50 text-brand-600"><x-icon name="check" :size="10" /></span>
                        <span class="font-bold text-slate-900">{{ $item['quantity'] }}&times;</span>
                        <span class="truncate">{{ $item['name'] }}</span>
                    </li>
                @endforeach
                @if (count($pack['items']) > 4)
                    <li class="pl-6 text-xs text-slate-400">+ {{ count($pack['items']) - 4 }} autre(s) produit(s)</li>
                @endif
            </ul>
        @endif

        <div class="mt-4 flex flex-wrap items-baseline gap-x-2">
            <span class="text-lg font-extrabold tracking-tight text-navy-900">{{ $pack['price_label'] }}</span>
            @if ($pack['savings'] > 0)
                <span class="text-xs text-slate-400 line-through">{{ $pack['components_total_label'] }}</span>
            @endif
        </div>

        <div class="mt-2">
            <x-shop.stock-badge :label="$pack['stock_label']" :color="$pack['stock_color']" />
        </div>

        <div class="mt-auto pt-4">
            <livewire:add-to-cart
                kind="pack"
                :item-id="$pack['id']"
                :max-quantity="$pack['max_quantity']"
                :orderable="$pack['orderable']"
                :with-quantity="false"
                label="Ajouter le pack"
                size="sm"
                :block="true"
                :key="$keyPrefix.'k-'.$pack['id']"
            />
        </div>
    </div>
</article>
