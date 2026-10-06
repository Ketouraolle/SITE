<div>
    <x-admin.page-header title="Tableau de bord" subtitle="Vue d'ensemble de la boutique">
        <x-btn :href="route('admin.statistics')" variant="secondary" size="sm">
            <x-icon name="trending" :size="16" />
            Statistiques
        </x-btn>
        <x-btn :href="route('admin.products.create')" size="sm">
            <x-icon name="plus" :size="16" />
            Nouveau produit
        </x-btn>
    </x-admin.page-header>

    {{-- Bandeau de synthese --}}
    <section class="relative overflow-hidden rounded-2xl bg-hero p-5 text-white shadow-lg shadow-brand-900/20 sm:p-6">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-accent">Chiffre d'affaires</p>
                <p class="mt-1 text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $stats['revenue_label'] }}</p>
                <p class="mt-1 text-sm text-brand-100">Toutes commandes confondues, annulations exclues</p>
            </div>
            <div class="grid grid-cols-3 gap-2 sm:gap-3">
                @foreach ([
                    ['label' => 'A traiter', 'value' => $stats['orders_pending']],
                    ['label' => "Aujourd'hui", 'value' => $stats['orders_today']],
                    ['label' => 'Au total', 'value' => $stats['orders_total']],
                ] as $figure)
                    <div class="rounded-xl bg-white/10 px-3 py-2.5 text-center ring-1 ring-inset ring-white/15 backdrop-blur sm:px-4">
                        <p class="text-xl font-extrabold tabular-nums">{{ $figure['value'] }}</p>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-brand-100">{{ $figure['label'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Indicateurs --}}
    <div class="mt-4 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        <x-stat-card label="Commandes a traiter" :value="$stats['orders_pending']" :hint="$stats['orders_total'].' au total'" icon="clipboard" tone="amber"
            :href="route('admin.orders.index', ['status' => 'pending'])" />
        <x-stat-card label="Commandes aujourd'hui" :value="$stats['orders_today']" hint="Depuis minuit" icon="sparkles" tone="emerald"
            :href="route('admin.orders.index')" />
        <x-stat-card label="Produits publies" :value="$stats['products_published']" :hint="$stats['products_total'].' fiches au total'" icon="box" tone="brand"
            :href="route('admin.products.index', ['state' => 'published'])" />
        <x-stat-card label="Packs publies" :value="$stats['packs_published']" :hint="$stats['packs_total'].' au total'" icon="layers" tone="accent"
            :href="route('admin.packs.index')" />
    </div>

    {{-- Alertes stock --}}
    <div class="mt-3 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        <x-stat-card label="En rupture" :value="$stats['products_out_of_stock']" hint="Publies, stock a 0" icon="alert" tone="rose"
            :href="route('admin.stock.index', ['state' => 'out_of_stock'])" />
        <x-stat-card label="Indisponibles" :value="$stats['products_unavailable']" hint="Retires de la vente" icon="eye-off" tone="slate"
            :href="route('admin.stock.index', ['state' => 'unavailable'])" />
        <x-stat-card label="Stock a surveiller" :value="$lowStock->count()" :hint="'Seuil : '.$stats['low_stock_threshold'].' unite(s)'" icon="boxes" tone="amber"
            :href="route('admin.stock.index')" />
        <x-stat-card label="Chiffre d'affaires" :value="$stats['revenue_label']" hint="Annulations exclues" icon="chart" tone="brand" />
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-3">
        <x-admin.revenue-chart :series="$series" class="lg:col-span-2" />

        {{-- Repartition par statut --}}
        <section class="flex flex-col rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:p-5">
            <h2 class="text-sm font-bold text-slate-900">Commandes par statut</h2>

            @php($statusTotal = max(1, $statusBreakdown->sum('count')))
            <ul class="mt-4 flex-1 space-y-3">
                @foreach ($statusBreakdown as $status)
                    <li>
                        <a href="{{ route('admin.orders.index', ['status' => $status['value']]) }}" wire:navigate class="group block">
                            <div class="flex items-center justify-between gap-3">
                                <x-badge :color="$status['color']" dot>{{ $status['label'] }}</x-badge>
                                <span class="text-sm font-extrabold tabular-nums text-slate-900 group-hover:text-brand-700">{{ $status['count'] }}</span>
                            </div>
                            <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-gradient-to-r from-brand-600 to-cyan-accent" style="width: {{ round($status['count'] / $statusTotal * 100, 1) }}%"></div>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>

            <x-btn :href="route('admin.orders.index')" variant="secondary" size="sm" block class="mt-5">
                Toutes les commandes
            </x-btn>
        </section>
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-5">
        {{-- Dernieres commandes --}}
        <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 lg:col-span-3">
            <header class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3.5 sm:px-5">
                <h2 class="text-sm font-bold text-slate-900">Dernieres commandes</h2>
                <a href="{{ route('admin.orders.index') }}" wire:navigate class="inline-flex items-center gap-1 text-xs font-semibold text-brand-700 hover:text-brand-900">
                    Tout voir <x-icon name="arrow-right" :size="14" />
                </a>
            </header>

            @if ($recentOrders->isNotEmpty())
                <ul class="divide-y divide-slate-100">
                    @foreach ($recentOrders as $order)
                        <li wire:key="recent-{{ $order['id'] }}">
                            <a href="{{ route('admin.orders.show', $order['number']) }}" wire:navigate class="flex items-center gap-3 px-4 py-3 transition hover:bg-brand-50/40 sm:px-5">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700">
                                    <x-icon name="clipboard" :size="16" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="flex flex-wrap items-center gap-2 text-sm font-bold text-slate-900">
                                        <span class="tabular-nums">{{ $order['number'] }}</span>
                                        <x-badge :color="$order['status_color']">{{ $order['status_label'] }}</x-badge>
                                    </p>
                                    <p class="mt-0.5 truncate text-xs text-slate-500">{{ $order['customer_name'] }} · {{ $order['created_at_label'] }}</p>
                                </div>
                                <p class="shrink-0 text-sm font-extrabold tabular-nums text-slate-900">{{ $order['total_label'] }}</p>
                                <x-icon name="chevron-right" :size="16" class="shrink-0 text-slate-300" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="p-5">
                    <x-empty-state icon="clipboard" title="Aucune commande" description="Les commandes validees par les clients apparaissent ici en temps reel." />
                </div>
            @endif
        </section>

        {{-- Stock faible --}}
        <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 lg:col-span-2">
            <header class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3.5 sm:px-5">
                <h2 class="text-sm font-bold text-slate-900">Stock a surveiller</h2>
                <a href="{{ route('admin.stock.index') }}" wire:navigate class="inline-flex items-center gap-1 text-xs font-semibold text-brand-700 hover:text-brand-900">
                    Gerer <x-icon name="arrow-right" :size="14" />
                </a>
            </header>

            @if ($lowStock->isNotEmpty())
                <ul class="divide-y divide-slate-100">
                    @foreach ($lowStock as $product)
                        <li wire:key="low-{{ $product['id'] }}" class="flex items-center gap-3 px-4 py-2.5 sm:px-5">
                            <p class="min-w-0 flex-1 truncate text-sm font-medium text-slate-700">{{ $product['name'] }}</p>
                            <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-bold tabular-nums {{ $product['stock'] <= 0 ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-800' }}">
                                {{ $product['stock'] }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="p-5">
                    <x-empty-state icon="check-circle" title="Tous les stocks sont bons" description="Aucun produit publie sous le seuil d'alerte." />
                </div>
            @endif
        </section>
    </div>
</div>
