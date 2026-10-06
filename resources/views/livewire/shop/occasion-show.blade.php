<div>
    <x-shop.page-hero
        :eyebrow="$occasionData['period_label']"
        :title="$occasionData['name']"
        :subtitle="$occasionData['description']"
        :breadcrumbs="[['label' => $occasionData['name']]]"
    >
        @if ($offers->isNotEmpty())
            <ul class="mt-6 flex flex-wrap gap-3">
                @foreach ($offers as $offer)
                    <li wire:key="offer-{{ $loop->index }}" class="flex items-start gap-3 rounded-2xl bg-white/95 px-4 py-3 text-sm text-slate-900 shadow-lg shadow-navy-950/20">
                        <span class="rounded-full bg-accent-500 px-2.5 py-1 text-xs font-extrabold text-white">{{ $offer['badge'] }}</span>
                        <span>
                            <span class="font-bold">{{ $offer['name'] }}</span>
                            @if ($offer['description'])
                                <span class="block text-xs text-slate-500">{{ $offer['description'] }}</span>
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-shop.page-hero>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @if ($packs->isNotEmpty())
            <section>
                <x-section-heading eyebrow="Offres groupees" title="Packs et coffrets" />
                <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($packs as $pack)
                        <x-shop.pack-card :pack="$pack" wire:key="occasion-pack-{{ $pack['id'] }}" />
                    @endforeach
                </div>
            </section>
        @endif

        @if ($products->isNotEmpty())
            <section class="{{ $packs->isNotEmpty() ? 'mt-14' : '' }}">
                <x-section-heading eyebrow="Selection de la periode" title="Produits" />
                <div class="mt-8 grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-4">
                    @foreach ($products as $product)
                        <x-shop.product-card :product="$product" wire:key="occasion-product-{{ $product['id'] }}" />
                    @endforeach
                </div>
            </section>
        @endif

        @if ($packs->isEmpty() && $products->isEmpty())
            <x-empty-state icon="sparkles" title="Bientot disponible" description="Les articles de cette periode arrivent tres vite. En attendant, decouvrez le catalogue.">
                <x-btn :href="route('catalog.index')" size="sm">Voir le catalogue</x-btn>
            </x-empty-state>
        @endif
    </div>
</div>
