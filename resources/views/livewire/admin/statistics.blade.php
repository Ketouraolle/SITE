<div>
    @php
        $periodLabel = $periods->firstWhere('value', $period)['label'] ?? '';
        $maxUnits = max(1, (int) $topProducts->max('units'));
    @endphp

    <x-admin.page-header title="Statistiques" subtitle="Produits les plus commandes (annulations exclues)">
        <div class="flex gap-1 overflow-x-auto rounded-full bg-white p-1 shadow-sm ring-1 ring-slate-200 no-scrollbar" role="group" aria-label="Periode">
            @foreach ($periods as $option)
                <button type="button" wire:click="selectPeriod('{{ $option['value'] }}')" wire:key="period-{{ $option['value'] }}"
                    @class([
                        'shrink-0 rounded-full px-3.5 py-1.5 text-xs font-bold transition',
                        'bg-gradient-to-r from-brand-700 to-brand-600 text-white shadow-sm' => $period === $option['value'],
                        'text-slate-600 hover:bg-slate-100' => $period !== $option['value'],
                    ])
                    aria-pressed="{{ $period === $option['value'] ? 'true' : 'false' }}">
                    {{ $option['label'] }}
                </button>
            @endforeach
        </div>
    </x-admin.page-header>

    <div wire:loading.class="opacity-60" class="transition-opacity">
        {{-- Indicateurs --}}
        <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
            <x-stat-card label="Commandes" :value="$summary['orders']" :hint="'Periode : '.$periodLabel" icon="clipboard" tone="brand" />
            <x-stat-card label="Articles vendus" :value="$summary['units']" hint="Unites, packs inclus" icon="box" tone="emerald" />
            <x-stat-card label="Produits differents" :value="$summary['distinct_products']" icon="tag" tone="amber" />
            <x-stat-card label="Chiffre d'affaires" :value="$summary['revenue_label']" icon="chart" tone="accent" />
        </div>

        <div class="mt-4 grid gap-4 lg:grid-cols-3">
            {{-- Classement des produits --}}
            <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 lg:col-span-2">
                <header class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3.5 sm:px-5">
                    <h2 class="text-sm font-bold text-slate-900">Produits les plus commandes</h2>
                    <x-badge color="brand">{{ $periodLabel }}</x-badge>
                </header>

                @if ($topProducts->isNotEmpty())
                    <ol class="divide-y divide-slate-100">
                        @foreach ($topProducts as $index => $product)
                            @php($linkable = $product['product_id'] && $product['stock'] !== null)
                            <li wire:key="top-{{ $product['product_id'] ?? $product['name'] }}" class="flex items-center gap-3 px-4 py-3 sm:px-5">
                                <span @class([
                                    'grid h-8 w-8 shrink-0 place-items-center rounded-full text-xs font-extrabold tabular-nums',
                                    'bg-gradient-to-br from-brand-600 to-cyan-accent text-white shadow-sm' => $index < 3,
                                    'bg-slate-100 text-slate-500' => $index >= 3,
                                ])>{{ $index + 1 }}</span>

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-baseline justify-between gap-3">
                                        @if ($linkable)
                                            <a href="{{ route('admin.products.edit', $product['product_id']) }}" wire:navigate class="truncate text-sm font-bold text-slate-900 hover:text-brand-700">{{ $product['name'] }}</a>
                                        @else
                                            <span class="truncate text-sm font-bold text-slate-900">{{ $product['name'] }}</span>
                                        @endif
                                        <span class="shrink-0 text-sm font-extrabold tabular-nums text-slate-900">{{ $product['units'] }}</span>
                                    </div>

                                    <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100">
                                        <div class="h-full rounded-full bg-gradient-to-r from-brand-600 to-cyan-accent" style="width: {{ round($product['units'] / $maxUnits * 100, 1) }}%"></div>
                                    </div>

                                    <p class="mt-1 truncate text-xs text-slate-500">
                                        {{ $product['category'] ?? 'Sans categorie' }}
                                        · {{ $product['orders'] }} commande(s)
                                        @if ($product['units_in_packs'])
                                            · dont {{ $product['units_in_packs'] }} en pack
                                        @endif
                                        · {{ $product['revenue_label'] }}
                                        @if ($product['stock'] !== null)
                                            · stock {{ $product['stock'] }}
                                        @else
                                            · supprime
                                        @endif
                                    </p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <div class="p-5">
                        <x-empty-state icon="trending" title="Aucune vente sur la periode" description="Le classement apparait des que des commandes sont passees." />
                    </div>
                @endif
            </section>

            <div class="space-y-4">
                {{-- Categories --}}
                <section class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:p-5">
                    <h2 class="text-sm font-bold text-slate-900">Par categorie</h2>

                    @if ($topCategories->isNotEmpty())
                        <ul class="mt-4 space-y-3">
                            @foreach ($topCategories as $category)
                                <li wire:key="cat-{{ $category['name'] }}">
                                    <div class="flex items-baseline justify-between gap-3 text-sm">
                                        <span class="truncate text-slate-700">{{ $category['name'] }}</span>
                                        <span class="shrink-0 font-bold tabular-nums text-slate-900">{{ $category['units'] }} · {{ $category['share'] }}%</span>
                                    </div>
                                    <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-100">
                                        <div class="h-full rounded-full bg-brand-600" style="width: {{ $category['share'] }}%"></div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="mt-3 text-sm text-slate-500">Aucune donnee.</p>
                    @endif
                </section>

                {{-- Packs --}}
                <section class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:p-5">
                    <h2 class="text-sm font-bold text-slate-900">Packs les plus commandes</h2>

                    @if ($topPacks->isNotEmpty())
                        <ul class="mt-3 divide-y divide-slate-100">
                            @foreach ($topPacks as $pack)
                                <li wire:key="pack-{{ $loop->index }}" class="flex items-center justify-between gap-3 py-2 text-sm">
                                    <span class="min-w-0">
                                        <span class="block truncate font-medium text-slate-700">{{ $pack['name'] }}</span>
                                        <span class="text-xs text-slate-500">{{ $pack['orders'] }} commande(s) · {{ $pack['revenue_label'] }}</span>
                                    </span>
                                    <span class="shrink-0 font-extrabold tabular-nums text-slate-900">{{ $pack['units'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="mt-3 text-sm text-slate-500">Aucun pack vendu.</p>
                    @endif
                </section>

                {{-- Jamais commandes --}}
                <section class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:p-5">
                    <h2 class="text-sm font-bold text-slate-900">Sans commande sur la periode</h2>

                    @if ($neverOrdered->isNotEmpty())
                        <ul class="mt-3 divide-y divide-slate-100">
                            @foreach ($neverOrdered as $product)
                                <li wire:key="never-{{ $product['id'] }}" class="flex items-center justify-between gap-3 py-2 text-sm">
                                    <a href="{{ route('admin.products.edit', $product['id']) }}" wire:navigate class="min-w-0 truncate text-slate-700 hover:text-brand-700">{{ $product['name'] }}</a>
                                    <span class="shrink-0 text-xs tabular-nums text-slate-400">stock {{ $product['stock'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="mt-3 text-sm text-slate-500">Tous les produits publies ont ete commandes sur la periode.</p>
                    @endif
                </section>
            </div>
        </div>
    </div>
</div>
