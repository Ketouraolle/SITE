<div>
    <x-admin.page-header title="Offres" :subtitle="$offers->count().' offre(s) promotionnelle(s)'">
        <x-btn :href="route('admin.offers.create')" size="sm">
            <x-icon name="plus" :size="16" />
            Nouvelle offre
        </x-btn>
    </x-admin.page-header>

    @if ($offers->isNotEmpty())
        <ul class="grid gap-4 lg:grid-cols-2">
            @foreach ($offers as $offer)
                @php
                    $status = $statuses[$offer['status']] ?? $statuses['inactive'];
                    $names = collect([...$offer['products'], ...$offer['packs']])->pluck('name');
                    $targets = $names->count() > 3
                        ? $names->take(3)->implode(', ').' + '.($names->count() - 3).' autre(s)'
                        : $names->implode(', ');
                @endphp
                <li wire:key="offer-{{ $offer['id'] }}" class="flex flex-col rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 transition hover:shadow-md hover:ring-brand-200 sm:p-5">
                    <div class="flex flex-1 items-start gap-3">
                        <span @class([
                            'grid h-14 w-14 shrink-0 place-items-center rounded-2xl text-sm font-extrabold',
                            'bg-gradient-to-br from-accent-500 to-accent-600 text-white shadow-sm shadow-accent-700/25' => $offer['status'] === 'running',
                            'bg-accent-50 text-accent-700' => $offer['status'] !== 'running',
                        ])>
                            {{ $offer['badge'] }}
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-sm font-bold text-slate-900">{{ $offer['name'] }}</h2>
                                <x-badge :color="$status['color']" dot>{{ $status['label'] }}</x-badge>
                                @if ($offer['occasion'])
                                    <x-badge color="indigo">{{ $offer['occasion']['name'] }}</x-badge>
                                @endif
                            </div>
                            <p class="mt-0.5 flex items-center gap-1 text-xs text-slate-500">
                                <x-icon name="clock" :size="12" />
                                {{ $offer['period_label'] }}
                            </p>
                            <p class="mt-1.5 line-clamp-2 text-xs text-slate-600">
                                <span class="font-bold">{{ count($offer['products']) + count($offer['packs']) }} article(s) :</span>
                                {{ $targets }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-end gap-1 border-t border-slate-100 pt-3">
                        <button type="button" wire:click="toggleActive({{ $offer['id'] }})"
                            class="inline-flex h-8 items-center gap-1.5 rounded-full px-3 text-xs font-semibold text-slate-600 transition hover:bg-slate-100">
                            <x-icon :name="$offer['is_active'] ? 'eye-off' : 'eye'" :size="14" />
                            {{ $offer['is_active'] ? 'Desactiver' : 'Activer' }}
                        </button>

                        <a href="{{ route('admin.offers.edit', $offer['id']) }}" wire:navigate
                            class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                            title="Modifier" aria-label="Modifier">
                            <x-icon name="edit" :size="16" />
                        </a>

                        <button type="button" wire:click="delete({{ $offer['id'] }})"
                            wire:confirm="Supprimer « {{ $offer['name'] }} » ? Les prix des articles concernes reviennent a la normale. Les commandes passees ne changent pas."
                            class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 transition hover:bg-red-50 hover:text-red-600"
                            title="Supprimer" aria-label="Supprimer">
                            <x-icon name="trash" :size="16" />
                        </button>
                    </div>
                </li>
            @endforeach
        </ul>
    @else
        <x-empty-state icon="star" title="Aucune offre"
            description="Creez une remise pour Noel, la Saint-Valentin ou l'Aid sur une selection de produits et de packs.">
            <x-btn :href="route('admin.offers.create')" size="sm">
                <x-icon name="plus" :size="16" />
                Nouvelle offre
            </x-btn>
        </x-empty-state>
    @endif
</div>
