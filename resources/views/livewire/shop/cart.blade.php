@php
    $typeLabels = ['product' => 'Produit', 'pack' => 'Pack', 'custom_pack' => 'Pack personnalise'];
    $blocked = $cart['has_blocking_issue'];
@endphp

<div>
    @include('livewire.shop.partials.checkout-hero', [
        'step' => 1,
        'title' => 'Mon panier',
        'subtitle' => $cart['count'].' article(s) dans votre panier',
    ])

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @if ($cart['lines'] !== [])
            <div class="grid gap-6 lg:grid-cols-3 lg:gap-8">
                {{-- Lignes --}}
                <div class="lg:col-span-2">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h2 class="text-lg font-extrabold tracking-tight text-slate-900">Articles selectionnes</h2>
                        <x-btn variant="secondary" size="sm" wire:click="clear" wire:confirm="Tous les articles seront retires de votre panier. Vider le panier ?">
                            <x-icon name="trash" :size="15" />
                            Vider le panier
                        </x-btn>
                    </div>

                    <ul class="space-y-3">
                        @foreach ($cart['lines'] as $line)
                            <li wire:key="line-{{ $line['key'] }}" @class([
                                'overflow-hidden rounded-2xl bg-white shadow-sm ring-1 transition hover:shadow-lg hover:shadow-brand-900/5',
                                'ring-red-200' => $line['issue'],
                                'ring-slate-200' => ! $line['issue'],
                            ])>
                                <div class="flex gap-3 p-3 sm:gap-5 sm:p-5">
                                    <div class="w-20 shrink-0 sm:w-28">
                                        @if ($line['url'])
                                            <a href="{{ $line['url'] }}" wire:navigate class="group/thumb block">
                                                <x-thumb :src="$line['image']" :alt="$line['name']" :label="$line['name']" rounded="rounded-xl" />
                                            </a>
                                        @else
                                            <x-thumb :src="$line['image']" :alt="$line['name']" :label="$line['name']" rounded="rounded-xl" />
                                        @endif
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="min-w-0">
                                                <p class="text-[11px] font-bold uppercase tracking-[0.15em] text-brand-600">{{ $typeLabels[$line['type']] ?? '' }}</p>
                                                <h3 class="mt-0.5 text-sm font-bold text-slate-900 sm:text-base">
                                                    @if ($line['url'])
                                                        <a href="{{ $line['url'] }}" wire:navigate class="transition hover:text-brand-700">{{ $line['name'] }}</a>
                                                    @else
                                                        {{ $line['name'] }}
                                                    @endif
                                                </h3>
                                                <p class="mt-0.5 text-xs text-slate-500">{{ $line['unit_price_label'] }} l'unite</p>
                                            </div>

                                            <button type="button" class="-mr-1 -mt-1 grid h-9 w-9 shrink-0 place-items-center rounded-full text-slate-400 transition hover:bg-red-50 hover:text-red-600"
                                                aria-label="Retirer cet article" wire:click="remove(@js($line['key']))" wire:loading.attr="disabled">
                                                <x-icon name="trash" :size="17" />
                                            </button>
                                        </div>

                                        @if ($line['components'] !== [])
                                            <ul class="mt-2.5 flex flex-wrap gap-1.5">
                                                @foreach ($line['components'] as $component)
                                                    <li class="rounded-full bg-brand-50 px-2.5 py-0.5 text-[11px] font-medium text-brand-800">
                                                        {{ $component['quantity'] }}× {{ $component['name'] }}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif

                                        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                                            @php
                                                $lineMax = max($line['max_quantity'], $line['quantity']);
                                                $lineDisabled = $line['max_quantity'] === 0;
                                            @endphp
                                            {{-- La quantite part au serveur apres une courte pause, pas a chaque appui. --}}
                                            <div
                                                x-data="{
                                                    value: {{ (int) $line['quantity'] }},
                                                    max: {{ (int) $lineMax }},
                                                    timer: null,
                                                    set(next) {
                                                        const parsed = Number.parseInt(next, 10);
                                                        this.value = Number.isNaN(parsed) ? 1 : Math.min(this.max, Math.max(1, parsed));
                                                        clearTimeout(this.timer);
                                                        this.timer = setTimeout(() => $wire.updateQuantity(@js($line['key']), this.value), 400);
                                                    },
                                                }"
                                                class="inline-flex items-center rounded-full bg-white ring-1 ring-inset ring-slate-300"
                                            >
                                                <button type="button" class="grid h-8 w-8 place-items-center rounded-l-full text-slate-600 transition hover:bg-slate-100 disabled:opacity-40"
                                                    x-on:click="set(value - 1)" :disabled="{{ $lineDisabled ? 'true' : 'false' }} || value <= 1" aria-label="Diminuer la quantite">
                                                    <x-icon name="minus" :size="14" />
                                                </button>
                                                <input type="text" inputmode="numeric" class="h-8 w-10 border-0 bg-transparent p-0 text-center text-sm font-semibold tabular-nums text-slate-900 focus:ring-0"
                                                    :value="value" x-on:change="set($event.target.value); $event.target.value = value" @disabled($lineDisabled) aria-label="Quantite" />
                                                <button type="button" class="grid h-8 w-8 place-items-center rounded-r-full text-slate-600 transition hover:bg-slate-100 disabled:opacity-40"
                                                    x-on:click="set(value + 1)" :disabled="{{ $lineDisabled ? 'true' : 'false' }} || value >= max" aria-label="Augmenter la quantite">
                                                    <x-icon name="plus" :size="14" />
                                                </button>
                                            </div>

                                            <p class="text-lg font-extrabold tracking-tight text-navy-900">{{ $line['line_total_label'] }}</p>
                                        </div>
                                    </div>
                                </div>

                                @if ($line['issue'])
                                    <p class="flex items-center gap-2 border-t border-red-100 bg-red-50 px-4 py-2.5 text-xs font-semibold text-red-700 sm:px-5">
                                        <x-icon name="alert" :size="15" />
                                        {{ $line['issue'] }}
                                    </p>
                                @endif
                            </li>
                        @endforeach
                    </ul>

                    <a href="{{ route('catalog.index') }}" wire:navigate class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 hover:text-brand-900">
                        <x-icon name="arrow-left" :size="16" />
                        Continuer mes achats
                    </a>
                </div>

                {{-- Recapitulatif --}}
                <aside class="lg:col-span-1">
                    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 lg:sticky lg:top-28">
                        <div class="h-1 bg-gradient-to-r from-brand-600 via-cyan-accent to-accent-500"></div>
                        <div class="p-5 sm:p-6">
                            <h2 class="text-lg font-extrabold tracking-tight text-slate-900">Recapitulatif</h2>

                            <dl class="mt-4 space-y-3 text-sm">
                                <div class="flex justify-between text-slate-600">
                                    <dt>Articles</dt>
                                    <dd class="font-semibold text-slate-900">{{ $cart['count'] }}</dd>
                                </div>
                                <div class="flex justify-between text-slate-600">
                                    <dt>Sous-total</dt>
                                    <dd class="font-semibold text-slate-900">{{ $cart['total_label'] }}</dd>
                                </div>
                                <div class="flex justify-between text-slate-600">
                                    <dt>Livraison</dt>
                                    <dd class="text-xs font-medium text-brand-700">A convenir sur WhatsApp</dd>
                                </div>
                                <div class="flex items-baseline justify-between border-t border-dashed border-slate-200 pt-4">
                                    <dt class="text-base font-bold text-slate-900">Total</dt>
                                    <dd class="text-2xl font-extrabold tracking-tight text-brand-700">{{ $cart['total_label'] }}</dd>
                                </div>
                            </dl>

                            @if ($blocked)
                                <p class="mt-4 flex items-start gap-2 rounded-xl bg-red-50 p-3 text-xs font-semibold text-red-700">
                                    <x-icon name="alert" :size="15" class="mt-px shrink-0" />
                                    Corrigez les articles signales avant de valider votre commande.
                                </p>
                            @endif

                            <x-btn :href="$blocked ? null : route('checkout.create')" variant="accent" block size="lg" class="mt-5" :disabled="$blocked">
                                Valider ma commande
                                <x-icon name="arrow-right" :size="18" />
                            </x-btn>

                            <x-btn :href="route('catalog.index')" variant="ghost" block size="sm" class="mt-2">
                                Continuer mes achats
                            </x-btn>

                            <div class="mt-5 flex items-start gap-3 rounded-xl bg-slate-50 p-3.5">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-[#25D366]/10 text-[#25D366]">
                                    <x-icon name="whatsapp" :size="16" />
                                </span>
                                <p class="text-xs leading-relaxed text-slate-600">
                                    Apres validation, votre commande part preremplie sur le WhatsApp du vendeur. Aucun paiement en ligne.
                                </p>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        @else
            <x-empty-state icon="cart" title="Votre panier est vide"
                description="Parcourez le catalogue ou composez votre pack pour commencer votre commande." class="mx-auto max-w-2xl">
                <x-btn :href="route('catalog.index')" variant="accent" size="sm">Voir le catalogue</x-btn>
                <x-btn :href="route('custom-pack.create')" variant="secondary" size="sm">
                    <x-icon name="sparkles" :size="15" />
                    Composer mon pack
                </x-btn>
            </x-empty-state>
        @endif
    </div>
</div>
