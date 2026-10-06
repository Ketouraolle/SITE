@props(['series'])

{{-- Chiffre d'affaires quotidien : <x-admin.revenue-chart :series="$series" /> (date, label, revenue, revenue_label, orders) --}}
@php
    $points = collect($series)->values();
    $count = max(1, $points->count());
    $max = max(1, (float) $points->max('revenue'));
    $total = (float) $points->sum('revenue');
    $totalOrders = (int) $points->sum('orders');
    $peakIndex = $points->search(fn ($point) => $point['revenue'] === $points->max('revenue'));
    $ticks = [$max, $max / 2, 0];
    $slot = 10;
    $barWidth = 6.4;
    $heightFor = fn (float $revenue) => $revenue <= 0 ? 0 : max(2, $revenue / $max * 100);
@endphp

<section x-data="{ hovered: null, showTable: false }" {{ $attributes->merge(['class' => 'rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:p-5']) }}>
    <header class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-sm font-bold text-slate-900">Chiffre d'affaires des 14 derniers jours</h2>
            <p class="mt-0.5 text-xs text-slate-500">
                <span class="font-semibold text-brand-700">{{ \App\Support\Money::format($total) }}</span>
                · {{ $totalOrders }} commande(s), annulations exclues
            </p>
        </div>

        <button type="button" class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-200 transition hover:bg-slate-50"
            x-on:click="showTable = ! showTable" x-bind:aria-pressed="showTable">
            <span x-show="! showTable" class="inline-flex items-center gap-1.5"><x-icon name="boxes" :size="14" /> Voir le tableau</span>
            <span x-show="showTable" x-cloak class="inline-flex items-center gap-1.5"><x-icon name="chart" :size="14" /> Voir le graphique</span>
        </button>
    </header>

    {{-- Graphique --}}
    <div x-show="! showTable" class="mt-6">
        <div class="flex gap-3">
            <div class="flex h-44 w-16 shrink-0 flex-col justify-between text-right">
                @foreach ($ticks as $tick)
                    <span class="text-[10px] leading-none tabular-nums text-slate-400">{{ \App\Support\Money::format($tick, false) }}</span>
                @endforeach
            </div>

            <div class="relative h-44 min-w-0 flex-1">
                <div class="pointer-events-none absolute inset-0 flex flex-col justify-between">
                    @foreach ($ticks as $tick)
                        <span class="h-px w-full {{ $loop->last ? 'bg-slate-200' : 'border-t border-dashed border-slate-100' }}"></span>
                    @endforeach
                </div>

                <svg viewBox="0 0 {{ $count * $slot }} 100" preserveAspectRatio="none" class="absolute inset-0 h-full w-full overflow-visible" aria-hidden="true">
                    <defs>
                        <linearGradient id="revenue-bar" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0" style="stop-color: var(--color-cyan-accent, #d9a441)" />
                            <stop offset="1" style="stop-color: var(--color-brand-600, #c2577a)" />
                        </linearGradient>
                        <linearGradient id="revenue-bar-active" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0" style="stop-color: var(--color-brand-500, #d9779a)" />
                            <stop offset="1" style="stop-color: var(--color-brand-800, #8e3a57)" />
                        </linearGradient>
                    </defs>
                    @foreach ($points as $index => $point)
                        @php($height = $heightFor((float) $point['revenue']))
                        @if ($height > 0)
                            <rect x="{{ $index * $slot + ($slot - $barWidth) / 2 }}" y="{{ 100 - $height }}" width="{{ $barWidth }}" height="{{ $height }}" rx="1"
                                x-bind:fill="hovered === {{ $index }} ? 'url(#revenue-bar-active)' : 'url(#revenue-bar)'" fill="url(#revenue-bar)" />
                        @else
                            <rect x="{{ $index * $slot + ($slot - $barWidth) / 2 }}" y="99" width="{{ $barWidth }}" height="1" class="fill-slate-200" />
                        @endif
                    @endforeach
                </svg>

                {{-- Zones de survol, etiquette du pic et infobulles --}}
                <div class="absolute inset-0 flex">
                    @foreach ($points as $index => $point)
                        @php($height = $heightFor((float) $point['revenue']))
                        <div class="relative h-full flex-1 outline-none" tabindex="0"
                            aria-label="{{ $point['label'] }} : {{ $point['revenue_label'] }}, {{ $point['orders'] }} commande(s)"
                            x-on:mouseenter="hovered = {{ $index }}" x-on:mouseleave="hovered = null"
                            x-on:focus="hovered = {{ $index }}" x-on:blur="hovered = null">
                            @if ($index === $peakIndex && $point['revenue'] > 0)
                                <span x-show="hovered === null" class="pointer-events-none absolute left-1/2 -translate-x-1/2 whitespace-nowrap text-[10px] font-bold tabular-nums text-brand-700"
                                    style="bottom: calc({{ $height }}% + 4px)">
                                    {{ \App\Support\Money::format($point['revenue'], false) }}
                                </span>
                            @endif
                            <div x-show="hovered === {{ $index }}" x-cloak
                                class="pointer-events-none absolute left-1/2 z-10 w-max -translate-x-1/2 rounded-lg bg-navy-900 px-2.5 py-1.5 text-left shadow-lg"
                                style="bottom: calc({{ $height }}% + 8px)">
                                <p class="text-[10px] font-medium text-slate-400">{{ $point['label'] }}</p>
                                <p class="text-xs font-bold tabular-nums text-white">{{ $point['revenue_label'] }}</p>
                                <p class="text-[10px] text-cyan-accent">{{ $point['orders'] }} commande(s)</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="mt-2 flex pl-[4.75rem]">
            @foreach ($points as $index => $point)
                <span class="flex-1 text-center text-[10px] tabular-nums text-slate-400">{{ $index % 2 === 0 ? $point['label'] : '' }}</span>
            @endforeach
        </div>
    </div>

    {{-- Tableau equivalent --}}
    <div x-show="showTable" x-cloak class="mt-4 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 text-left text-xs text-slate-500">
                    <th scope="col" class="py-2 pr-3 font-semibold">Jour</th>
                    <th scope="col" class="py-2 pr-3 text-right font-semibold">Chiffre d'affaires</th>
                    <th scope="col" class="py-2 text-right font-semibold">Commandes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($points as $point)
                    <tr>
                        <td class="py-1.5 pr-3 text-slate-700">{{ $point['label'] }}</td>
                        <td class="py-1.5 pr-3 text-right font-semibold tabular-nums text-slate-900">{{ $point['revenue_label'] }}</td>
                        <td class="py-1.5 text-right tabular-nums text-slate-700">{{ $point['orders'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
