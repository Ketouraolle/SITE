<div>
    <x-admin.page-header title="Commandes" :subtitle="$summary['count'].' commande(s) · '.$summary['total_label']" />

    {{-- Filtres --}}
    <div class="rounded-2xl bg-white p-3 shadow-sm ring-1 ring-slate-200 sm:p-4">
        <div class="relative">
            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                <x-icon name="search" :size="16" />
            </span>
            <x-input type="search" wire:model.live.debounce.350ms="q" placeholder="Numero, nom, telephone, adresse…" class="pl-9" aria-label="Rechercher une commande" />
        </div>

        <div class="mt-3 flex gap-1.5 overflow-x-auto pb-1 no-scrollbar">
            @foreach ([['value' => 'all', 'label' => 'Toutes'], ...$statuses] as $option)
                <button type="button" wire:click="setStatus('{{ $option['value'] }}')" wire:key="status-{{ $option['value'] }}"
                    @class([
                        'shrink-0 rounded-full px-3.5 py-1.5 text-xs font-bold transition',
                        'bg-gradient-to-r from-brand-700 to-brand-600 text-white shadow-sm' => $status === $option['value'],
                        'bg-slate-100 text-slate-600 hover:bg-slate-200' => $status !== $option['value'],
                    ])>
                    {{ $option['label'] }}
                </button>
            @endforeach
        </div>
    </div>

    <div wire:loading.class="opacity-60" class="transition-opacity">
        @if ($orders->isNotEmpty())
            <div class="mt-4">
                {{-- Tableau (desktop) --}}
                <div class="hidden overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 lg:block">
                    <table class="w-full">
                        <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th scope="col" class="px-4 py-3">Commande</th>
                                <th scope="col" class="px-4 py-3">Client</th>
                                <th scope="col" class="px-4 py-3">Adresse</th>
                                <th scope="col" class="px-4 py-3 text-right">Articles</th>
                                <th scope="col" class="px-4 py-3 text-right">Total</th>
                                <th scope="col" class="px-4 py-3">Statut</th>
                                <th scope="col" class="px-4 py-3"><span class="sr-only">Ouvrir</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($orders as $order)
                                <tr wire:key="row-{{ $order['id'] }}" class="group relative transition hover:bg-brand-50/40">
                                    <td class="px-4 py-3">
                                        <a href="{{ route('admin.orders.show', $order['number']) }}" wire:navigate class="text-sm font-bold tabular-nums text-slate-900 after:absolute after:inset-0 group-hover:text-brand-700">
                                            {{ $order['number'] }}
                                        </a>
                                        <p class="text-xs text-slate-500">{{ $order['created_at_label'] }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="text-sm font-medium text-slate-900">{{ $order['customer_name'] }}</p>
                                        <p class="text-xs tabular-nums text-slate-500">{{ $order['customer_phone'] }}</p>
                                    </td>
                                    <td class="max-w-48 px-4 py-3">
                                        <p class="truncate text-sm text-slate-600">{{ $order['customer_address'] }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm tabular-nums text-slate-700">{{ $order['items_count'] }}</td>
                                    <td class="px-4 py-3 text-right text-sm font-extrabold tabular-nums text-slate-900">{{ $order['total_label'] }}</td>
                                    <td class="px-4 py-3">
                                        <x-badge :color="$order['status_color']" dot>{{ $order['status_label'] }}</x-badge>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <x-icon name="chevron-right" :size="16" class="inline text-slate-300 group-hover:text-brand-600" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Cartes (mobile) --}}
                <ul class="space-y-3 lg:hidden">
                    @foreach ($orders as $order)
                        <li wire:key="card-{{ $order['id'] }}">
                            <a href="{{ route('admin.orders.show', $order['number']) }}" wire:navigate class="block rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 transition hover:ring-brand-200">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-sm font-extrabold tabular-nums text-slate-900">{{ $order['number'] }}</p>
                                        <p class="text-xs text-slate-500">{{ $order['created_at_label'] }}</p>
                                    </div>
                                    <x-badge :color="$order['status_color']" dot>{{ $order['status_label'] }}</x-badge>
                                </div>

                                <div class="mt-3 border-t border-slate-100 pt-3">
                                    <p class="text-sm font-medium text-slate-900">{{ $order['customer_name'] }}</p>
                                    <p class="text-xs tabular-nums text-slate-500">{{ $order['customer_phone'] }}</p>
                                    <p class="mt-0.5 line-clamp-1 text-xs text-slate-500">{{ $order['customer_address'] }}</p>
                                </div>

                                <div class="mt-3 flex items-baseline justify-between">
                                    <span class="text-xs text-slate-500">{{ $order['items_count'] }} article(s)</span>
                                    <span class="text-base font-extrabold tabular-nums text-slate-900">{{ $order['total_label'] }}</span>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-6">
                    {{ $orders->links() }}
                </div>
            </div>
        @else
            <div class="mt-4">
                <x-empty-state icon="clipboard" title="Aucune commande"
                    :description="$q !== '' || $status !== 'all'
                        ? 'Aucune commande ne correspond a ces criteres.'
                        : 'Les commandes validees par les clients apparaissent ici immediatement.'" />
            </div>
        @endif
    </div>
</div>
