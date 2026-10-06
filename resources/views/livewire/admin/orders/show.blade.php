<div>
    @php
        $typeLabels = ['product' => 'Produit', 'pack' => 'Pack', 'custom_pack' => 'Pack client'];
        $dotColors = ['amber' => 'bg-amber-500', 'blue' => 'bg-fuchsia-500', 'indigo' => 'bg-violet-500', 'emerald' => 'bg-emerald-500', 'rose' => 'bg-red-500'];
    @endphp

    <x-admin.page-header :title="'Commande '.$detail['number']" :subtitle="'Passee le '.$detail['created_at_label']">
        <x-btn :href="route('admin.orders.index')" variant="secondary" size="sm">
            <x-icon name="arrow-left" :size="16" />
            Retour
        </x-btn>
    </x-admin.page-header>

    <div class="grid gap-4 lg:grid-cols-3">
        {{-- Articles --}}
        <div class="space-y-4 lg:col-span-2">
            <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <header class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-4 py-3.5 sm:px-5">
                    <h2 class="text-sm font-bold text-slate-900">
                        Contenu de la commande
                        <span class="ml-1 font-normal text-slate-400">({{ $detail['items_count'] }} article(s))</span>
                    </h2>
                    <x-badge :color="$detail['status_color']" dot>{{ $detail['status_label'] }}</x-badge>
                </header>

                <ul class="divide-y divide-slate-100">
                    @foreach ($detail['items'] as $item)
                        <li wire:key="item-{{ $item['id'] }}" class="px-4 py-3.5 sm:px-5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-bold text-slate-900">{{ $item['name'] }}</span>
                                        @if ($item['type'] !== 'product')
                                            <x-badge color="brand">{{ $typeLabels[$item['type']] ?? $item['type'] }}</x-badge>
                                        @endif
                                    </p>
                                    <p class="mt-0.5 text-xs tabular-nums text-slate-500">{{ $item['quantity'] }} × {{ $item['unit_price_label'] }}</p>

                                    @if (count($item['components']))
                                        <ul class="mt-2 flex flex-wrap gap-1.5">
                                            @foreach ($item['components'] as $component)
                                                <li class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] text-slate-600">{{ $component['quantity'] }}× {{ $component['name'] }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>

                                <p class="shrink-0 text-sm font-extrabold tabular-nums text-slate-900">{{ $item['line_total_label'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <div class="flex items-baseline justify-between border-t border-slate-200 bg-slate-50/60 px-4 py-4 sm:px-5">
                    <span class="text-sm font-bold text-slate-900">Total</span>
                    <span class="text-xl font-extrabold tabular-nums text-brand-700">{{ $detail['total_label'] }}</span>
                </div>
            </section>

            {{-- Message WhatsApp --}}
            @if ($detail['whatsapp_message'])
                <section class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:p-5"
                    x-data="{
                        copied: false,
                        async copy() {
                            try {
                                await navigator.clipboard.writeText(this.$refs.message.innerText);
                                this.copied = true;
                                setTimeout(() => (this.copied = false), 2000);
                            } catch (error) {
                                this.copied = false;
                            }
                        },
                    }">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-sm font-bold text-slate-900">Message genere pour le client</h2>
                        <button type="button" class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold text-brand-700 transition hover:bg-brand-50" x-on:click="copy()">
                            <span x-show="! copied" class="inline-flex items-center gap-1.5"><x-icon name="clipboard" :size="14" /> Copier</span>
                            <span x-show="copied" x-cloak class="inline-flex items-center gap-1.5"><x-icon name="check" :size="14" /> Copie !</span>
                        </button>
                    </div>

                    <pre x-ref="message" class="mt-3 whitespace-pre-wrap rounded-xl bg-slate-50 p-3.5 font-sans text-sm leading-relaxed text-slate-700">{{ $detail['whatsapp_message'] }}</pre>
                </section>
            @endif
        </div>

        {{-- Colonne laterale --}}
        <div class="space-y-4">
            {{-- Statut --}}
            <section class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:p-5">
                <h2 class="text-sm font-bold text-slate-900">Statut de la commande</h2>

                <div class="mt-3 space-y-1.5">
                    @foreach ($statuses as $status)
                        @php($current = $detail['status'] === $status['value'])
                        <button type="button" wire:key="status-{{ $status['value'] }}" wire:click="updateStatus('{{ $status['value'] }}')" wire:loading.attr="disabled" wire:target="updateStatus"
                            @class([
                                'flex w-full items-center justify-between gap-2 rounded-xl px-3.5 py-2.5 text-left text-sm font-semibold transition disabled:opacity-60',
                                'bg-gradient-to-r from-brand-700 to-brand-600 text-white shadow-sm shadow-brand-900/20' => $current,
                                'bg-slate-50 text-slate-700 ring-1 ring-inset ring-slate-200 hover:bg-brand-50 hover:ring-brand-200' => ! $current,
                            ])>
                            <span class="flex items-center gap-2">
                                @unless ($current)
                                    <span class="h-2 w-2 rounded-full {{ $dotColors[$status['color']] ?? 'bg-slate-400' }}"></span>
                                @endunless
                                {{ $status['label'] }}
                            </span>
                            @if ($current)
                                <x-icon name="check" :size="16" />
                            @endif
                        </button>
                    @endforeach
                </div>

                <x-input-error for="status" />

                <p class="mt-3 text-xs text-slate-500">
                    Passer une commande en « Annulee » remet automatiquement les articles en stock. La repasser dans un autre statut les reprend.
                </p>
            </section>

            {{-- Client --}}
            <section class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:p-5">
                <div class="flex items-center gap-3">
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-gradient-to-br from-brand-600 to-cyan-accent text-white">
                        <x-icon name="user" :size="20" />
                    </span>
                    <h2 class="text-sm font-bold text-slate-900">Client</h2>
                </div>

                <dl class="mt-4 space-y-3 text-sm">
                    <div>
                        <dt class="text-xs font-semibold text-slate-500">Nom</dt>
                        <dd class="font-semibold text-slate-900">{{ $detail['customer_name'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-slate-500">Telephone</dt>
                        <dd class="font-semibold tabular-nums text-slate-900">
                            <a href="tel:{{ preg_replace('/\s/', '', $detail['customer_phone']) }}" class="inline-flex items-center gap-1.5 hover:text-brand-700">
                                <x-icon name="phone" :size="14" class="text-slate-400" />
                                {{ $detail['customer_phone'] }}
                            </a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-slate-500">Adresse de livraison</dt>
                        <dd class="text-slate-900">{{ $detail['customer_address'] }}</dd>
                    </div>
                    @if ($detail['customer_note'])
                        <div>
                            <dt class="text-xs font-semibold text-slate-500">Precisions du client</dt>
                            <dd class="text-slate-700">{{ $detail['customer_note'] }}</dd>
                        </div>
                    @endif
                    @if ($detail['confirmed_at_label'])
                        <div>
                            <dt class="text-xs font-semibold text-slate-500">Confirmee le</dt>
                            <dd class="text-slate-700">{{ $detail['confirmed_at_label'] }}</dd>
                        </div>
                    @endif
                </dl>

                <div class="mt-4 space-y-2">
                    @if ($customerWhatsappUrl)
                        <x-btn :href="$customerWhatsappUrl" external variant="whatsapp" size="sm" block>
                            <x-icon name="whatsapp" :size="16" />
                            Ecrire au client
                        </x-btn>
                    @endif
                    @if ($whatsappUrl)
                        <x-btn :href="$whatsappUrl" external variant="secondary" size="sm" block>
                            <x-icon name="whatsapp" :size="16" />
                            Renvoyer la commande
                        </x-btn>
                    @endif
                </div>
            </section>
        </div>
    </div>
</div>
