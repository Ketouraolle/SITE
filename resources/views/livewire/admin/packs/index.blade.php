<div>
    <x-admin.page-header title="Packs" subtitle="{{ $packs->count() }} pack(s) predefini(s)">
        <x-btn variant="accent" size="sm" :href="route('admin.packs.create')">
            <x-icon name="plus" :size="16" />
            Nouveau pack
        </x-btn>
    </x-admin.page-header>

    @if ($packs->isNotEmpty())
        <ul class="grid gap-4 lg:grid-cols-2">
            @foreach ($packs as $pack)
                <li wire:key="pack-{{ $pack['id'] }}" class="flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 transition hover:shadow-md hover:ring-brand-200">
                    <div class="flex gap-4 p-4 sm:p-5">
                        <div class="w-20 shrink-0 sm:w-24">
                            <x-thumb :src="$pack['image']" :alt="$pack['name']" :label="$pack['name']" />
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('admin.packs.edit', $pack['id']) }}" wire:navigate class="text-base font-extrabold tracking-tight text-slate-900 hover:text-brand-700">{{ $pack['name'] }}</a>
                                <x-badge :color="$pack['is_published'] ? 'brand' : 'slate'">{{ $pack['is_published'] ? 'Publie' : 'Brouillon' }}</x-badge>
                                @if ($pack['is_featured'])
                                    <x-badge color="amber">Vedette</x-badge>
                                @endif
                                @if ($pack['occasion'])
                                    <x-badge :color="$pack['occasion']['status'] === 'running' ? 'indigo' : 'slate'">
                                        {{ $pack['occasion']['name'] }}{{ $pack['occasion']['status'] === 'running' ? '' : ' · hors periode' }}
                                    </x-badge>
                                @endif
                                @if ($pack['offer'])
                                    <x-badge color="accent">{{ $pack['offer']['badge'] }}</x-badge>
                                @endif
                            </div>

                            <p class="mt-1.5 text-lg font-extrabold tracking-tight text-brand-700">
                                {{ $pack['effective_price_label'] }}
                                @if ($pack['price'] === null)
                                    <span class="text-xs font-medium text-slate-500">(somme des produits)</span>
                                @endif
                            </p>

                            <p class="mt-0.5 text-xs text-slate-500">
                                Valeur produits : {{ $pack['components_total_label'] }} ·
                                <span class="font-semibold {{ $pack['available_quantity'] > 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ $pack['available_quantity'] }} pack(s) servable(s)</span>
                            </p>

                            <ul class="mt-2.5 flex flex-wrap gap-1.5">
                                @foreach ($pack['items'] as $item)
                                    <li class="rounded-md px-2 py-0.5 text-[11px] font-medium {{ $item['stock'] <= 0 ? 'bg-red-50 text-red-700' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $item['quantity'] }}× {{ $item['name'] }}
                                        <span class="text-slate-400">(stock {{ $item['stock'] }})</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    <div class="mt-auto flex flex-wrap items-center gap-2 border-t border-slate-100 bg-slate-50/60 px-4 py-3">
                        <x-btn variant="secondary" size="xs" wire:click="togglePublish({{ $pack['id'] }})">
                            <x-icon :name="$pack['is_published'] ? 'eye-off' : 'eye'" :size="14" />
                            {{ $pack['is_published'] ? 'Depublier' : 'Publier' }}
                        </x-btn>
                        <x-btn variant="secondary" size="xs" :href="route('admin.packs.edit', $pack['id'])">
                            <x-icon name="edit" :size="14" />
                            Modifier
                        </x-btn>
                        @if ($pack['is_published'])
                            <a href="{{ route('packs.show', $pack['slug']) }}" target="_blank" rel="noopener" class="text-xs font-semibold text-brand-700 hover:text-brand-900">Voir sur le site</a>
                        @endif
                        <x-btn variant="danger-soft" size="xs" class="ml-auto" aria-label="Supprimer"
                            wire:click="destroy({{ $pack['id'] }})"
                            wire:confirm="Supprimer « {{ $pack['name'] }} » ? Le pack sera retire du site. Les commandes passees restent intactes.">
                            <x-icon name="trash" :size="14" />
                        </x-btn>
                    </div>
                </li>
            @endforeach
        </ul>
    @else
        <x-empty-state icon="layers" title="Aucun pack" description="Un pack regroupe plusieurs produits a un prix unique. Les clients peuvent aussi composer le leur.">
            <x-btn variant="accent" size="sm" :href="route('admin.packs.create')">
                <x-icon name="plus" :size="16" />
                Nouveau pack
            </x-btn>
        </x-empty-state>
    @endif
</div>
