<div>
    <x-shop.page-hero
        eyebrow="Prix groupes"
        title="Nos packs"
        subtitle="Des ensembles prepares par la boutique, a prix groupe. Vous pouvez aussi composer le votre produit par produit."
        :breadcrumbs="[['label' => 'Packs']]"
    >
        <div class="mt-6 flex flex-wrap gap-3">
            <x-btn :href="route('custom-pack.create')" variant="accent">
                <x-icon name="sparkles" :size="16" />
                Composer mon pack
            </x-btn>
            <x-btn :href="route('catalog.index')" variant="outline-light">
                Voir le catalogue
                <x-icon name="arrow-right" :size="16" />
            </x-btn>
        </div>
    </x-shop.page-hero>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @if ($packs->isNotEmpty())
            <p class="text-sm text-slate-600"><span class="font-extrabold text-slate-900">{{ $packs->count() }}</span> pack(s) disponible(s) a la commande.</p>

            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($packs as $pack)
                    <x-shop.pack-card :pack="$pack" wire:key="pack-{{ $pack['id'] }}" />
                @endforeach
            </div>
        @else
            <x-empty-state icon="layers" title="Aucun pack pour le moment" description="La boutique n'a pas encore publie de pack. Composez le votre a partir du catalogue.">
                <x-btn :href="route('custom-pack.create')" size="sm">Composer mon pack</x-btn>
            </x-empty-state>
        @endif
    </div>
</div>
