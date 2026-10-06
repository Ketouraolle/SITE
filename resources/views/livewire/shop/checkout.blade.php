<div>
    @include('livewire.shop.partials.checkout-hero', [
        'step' => 2,
        'title' => 'Finaliser ma commande',
        'subtitle' => 'Aucun compte n\'est necessaire. Renseignez simplement vos coordonnees de livraison.',
    ])

    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        <a href="{{ route('cart.index') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-600 transition hover:text-brand-700">
            <x-icon name="arrow-left" :size="16" />
            Retour au panier
        </a>

        @error('cart')
            <div class="mt-5 rounded-2xl bg-red-50 p-4 ring-1 ring-inset ring-red-200 sm:p-5">
                <p class="flex items-center gap-2 text-sm font-bold text-red-800">
                    <x-icon name="alert" :size="17" />
                    Votre commande n'a pas pu etre enregistree
                </p>
                <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-red-700">
                    @foreach ($errors->get('cart') as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
                <x-btn :href="route('cart.index')" variant="secondary" size="sm" class="mt-3">Corriger mon panier</x-btn>
            </div>
        @enderror

        <form wire:submit="place" class="mt-6 grid gap-6 lg:grid-cols-5 lg:gap-8">
            {{-- Coordonnees --}}
            <div class="lg:col-span-3">
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-7">
                    <div class="flex items-center gap-3">
                        <span class="grid h-11 w-11 place-items-center rounded-xl bg-gradient-to-br from-brand-600 to-cyan-accent text-white shadow-sm shadow-brand-700/30">
                            <x-icon name="user" :size="20" />
                        </span>
                        <div>
                            <h2 class="text-lg font-extrabold tracking-tight text-slate-900">Vos coordonnees</h2>
                            <p class="text-xs text-slate-500">Utilisees uniquement pour la livraison de votre commande.</p>
                        </div>
                    </div>

                    <div class="mt-6 space-y-5">
                        <div>
                            <x-label for="customer_name">Nom complet <span class="text-red-500">*</span></x-label>
                            <x-input id="customer_name" wire:model="customer_name" autocomplete="name" placeholder="Ex. Jean Mbarga" :invalid="$errors->has('customer_name')" class="mt-1.5" required />
                            <x-input-error for="customer_name" />
                        </div>

                        <div>
                            <x-label for="customer_phone">Telephone (WhatsApp) <span class="text-red-500">*</span></x-label>
                            <x-input id="customer_phone" type="tel" inputmode="tel" wire:model="customer_phone" autocomplete="tel" placeholder="Ex. +237 6 99 00 00 00" :invalid="$errors->has('customer_phone')" class="mt-1.5" required />
                            <x-input-error for="customer_phone" />
                            <p class="mt-1.5 text-xs text-slate-500">C'est sur ce numero que nous vous rappellerons pour la livraison.</p>
                        </div>

                        <div>
                            <x-label for="customer_address">Adresse de livraison <span class="text-red-500">*</span></x-label>
                            <x-input id="customer_address" wire:model="customer_address" autocomplete="street-address" placeholder="Quartier, ville, point de repere" :invalid="$errors->has('customer_address')" class="mt-1.5" required />
                            <x-input-error for="customer_address" />
                        </div>

                        <div>
                            <x-label for="customer_note">Precisions <span class="font-normal text-slate-400">(optionnel)</span></x-label>
                            <x-textarea id="customer_note" wire:model="customer_note" :rows="3" placeholder="Horaire de livraison souhaite, point de repere…" :invalid="$errors->has('customer_note')" class="mt-1.5" />
                            <x-input-error for="customer_note" />
                        </div>
                    </div>
                </div>

                @if ($whatsappConfigured)
                    <div class="mt-4 flex items-start gap-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:p-5">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-[#25D366]/10 text-[#25D366]">
                            <x-icon name="whatsapp" :size="20" />
                        </span>
                        <p class="text-sm leading-relaxed text-slate-600">
                            Apres validation, vous serez redirige vers WhatsApp avec un message contenant votre numero de commande,
                            les produits et le total. Il ne reste qu'a l'envoyer.
                        </p>
                    </div>
                @else
                    <div class="mt-4 flex items-start gap-3 rounded-2xl bg-amber-50 p-4 ring-1 ring-inset ring-amber-200 sm:p-5">
                        <x-icon name="alert" :size="18" class="mt-0.5 shrink-0 text-amber-700" />
                        <p class="text-sm text-amber-800">
                            Le numero WhatsApp de la boutique n'est pas encore configure. Votre commande sera bien enregistree
                            et le vendeur vous recontactera.
                        </p>
                    </div>
                @endif
            </div>

            {{-- Recapitulatif --}}
            <aside class="lg:col-span-2">
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 lg:sticky lg:top-28">
                    <div class="h-1 bg-gradient-to-r from-brand-600 via-cyan-accent to-accent-500"></div>
                    <div class="p-5 sm:p-6">
                        <h2 class="text-lg font-extrabold tracking-tight text-slate-900">Votre commande</h2>

                        <ul class="mt-4 divide-y divide-slate-100">
                            @foreach ($cart['lines'] as $line)
                                <li wire:key="summary-{{ $line['key'] }}" class="flex items-start justify-between gap-3 py-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ $line['name'] }}</p>
                                        <p class="text-xs text-slate-500">{{ $line['quantity'] }} × {{ $line['unit_price_label'] }}</p>
                                    </div>
                                    <p class="shrink-0 text-sm font-bold text-slate-900">{{ $line['line_total_label'] }}</p>
                                </li>
                            @endforeach
                        </ul>

                        <div class="mt-3 flex items-baseline justify-between border-t border-dashed border-slate-200 pt-4">
                            <span class="text-base font-bold text-slate-900">Total</span>
                            <span class="text-2xl font-extrabold tracking-tight text-brand-700">{{ $cart['total_label'] }}</span>
                        </div>

                        <x-btn type="submit" variant="accent" block size="lg" class="mt-5" wire:loading.attr="disabled" wire:target="place">
                            <x-icon name="check" :size="18" />
                            <span wire:loading.remove wire:target="place">Valider ma commande</span>
                            <span wire:loading wire:target="place">Enregistrement…</span>
                        </x-btn>

                        <p class="mt-3 flex items-center justify-center gap-1.5 text-center text-xs text-slate-500">
                            <x-icon name="shield" :size="14" class="text-brand-600" />
                            Paiement a la livraison. Pas de paiement en ligne.
                        </p>
                    </div>
                </div>
            </aside>
        </form>
    </div>
</div>
