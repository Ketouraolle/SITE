<div>
    @include('livewire.shop.partials.checkout-hero', [
        'step' => 3,
        'eyebrow' => 'Merci pour votre confiance',
        'title' => 'Commande enregistree',
        'subtitle' => 'Votre commande '.$details['number'].' vient d\'apparaitre chez le vendeur.',
    ])

    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
        {{-- Confirmation --}}
        <div class="rounded-2xl bg-white p-6 text-center shadow-sm ring-1 ring-slate-200 sm:p-8">
            <span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-emerald-50 text-emerald-600 ring-8 ring-emerald-50/60">
                <x-icon name="check-circle" :size="34" />
            </span>
            <p class="mt-5 text-sm text-slate-600 sm:text-base">
                Merci {{ $details['customer_name'] }} ! Votre commande porte le numero
                <span class="font-extrabold text-navy-900">{{ $details['number'] }}</span>
                et vient d'apparaitre chez le vendeur.
            </p>
            <p class="mt-3 inline-flex items-center gap-2 rounded-full bg-brand-50 px-4 py-1.5 text-sm font-bold text-brand-700">
                Total : {{ $details['total_label'] }}
            </p>
        </div>

        {{-- Redirection WhatsApp, une seule fois par commande : revenir sur la
             page avec le bouton "retour" ne relance pas l'ouverture. --}}
        @if ($whatsappUrl)
            <div
                x-data="{
                    url: @js($whatsappUrl),
                    storageKey: @js('whatsapp-sent:'.$details['number']),
                    message: @js($details['whatsapp_message'] ?? ''),
                    countdown: 3,
                    redirecting: false,
                    copied: false,
                    timer: null,
                    alreadyRedirected() {
                        try { return sessionStorage.getItem(this.storageKey) === '1'; } catch (e) { return false; }
                    },
                    markRedirected() {
                        try { sessionStorage.setItem(this.storageKey, '1'); } catch (e) {}
                    },
                    open() {
                        clearInterval(this.timer);
                        this.redirecting = false;
                        this.markRedirected();
                        window.location.href = this.url;
                    },
                    cancel() {
                        clearInterval(this.timer);
                        this.redirecting = false;
                        this.markRedirected();
                    },
                    async copy() {
                        try {
                            await navigator.clipboard.writeText(this.message);
                            this.copied = true;
                            setTimeout(() => this.copied = false, 2000);
                        } catch (e) {
                            this.copied = false;
                        }
                    },
                    init() {
                        if (this.alreadyRedirected()) return;
                        this.redirecting = true;
                        this.timer = setInterval(() => {
                            this.countdown -= 1;
                            if (this.countdown <= 0) { this.open(); }
                        }, 1000);
                    },
                    destroy() {
                        clearInterval(this.timer);
                    },
                }"
                class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200"
            >
                <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:p-6">
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-[#25D366]/10 text-[#25D366]">
                        <x-icon name="whatsapp" :size="26" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <h2 class="text-base font-extrabold tracking-tight text-slate-900">Derniere etape : envoyez votre commande</h2>
                        <p class="mt-1 text-sm text-slate-600">
                            <span x-show="redirecting">Ouverture de WhatsApp dans <span x-text="countdown"></span> seconde(s)…</span>
                            <span x-show="! redirecting">Le message est deja redige, il ne vous reste qu'a l'envoyer.</span>
                        </p>
                    </div>

                    <div class="flex shrink-0 flex-col gap-2 sm:flex-row sm:items-center">
                        <a :href="url" x-on:click.prevent="open()" href="{{ $whatsappUrl }}"
                            class="inline-flex items-center justify-center gap-2 rounded-full bg-[#25D366] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#1fb757]">
                            <x-icon name="whatsapp" :size="17" />
                            Ouvrir WhatsApp
                        </a>
                        <x-btn variant="ghost" size="sm" x-show="redirecting" x-on:click="cancel()">Plus tard</x-btn>
                    </div>
                </div>

                @if (! empty($details['whatsapp_message']))
                    <div class="border-t border-slate-100 bg-slate-50 p-5 sm:p-6">
                        <div class="flex items-start justify-between gap-3">
                            <h3 class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500">Message prerempli</h3>
                            <button type="button" class="inline-flex shrink-0 items-center gap-1.5 text-xs font-semibold text-brand-700 hover:text-brand-900" x-on:click="copy()">
                                <span x-show="! copied" class="inline-flex items-center gap-1.5"><x-icon name="clipboard" :size="14" /> Copier</span>
                                <span x-show="copied" x-cloak class="inline-flex items-center gap-1.5"><x-icon name="check" :size="14" /> Copie !</span>
                            </button>
                        </div>
                        <pre class="mt-2 whitespace-pre-wrap font-sans text-sm leading-relaxed text-slate-700">{{ $details['whatsapp_message'] }}</pre>
                    </div>
                @endif
            </div>
        @else
            <div class="mt-6 flex items-start gap-3 rounded-2xl bg-amber-50 p-5 ring-1 ring-inset ring-amber-200">
                <x-icon name="alert" :size="20" class="mt-0.5 shrink-0 text-amber-700" />
                <div>
                    <h2 class="text-sm font-bold text-amber-900">WhatsApp non configure</h2>
                    <p class="mt-1 text-sm text-amber-800">
                        Votre commande est bien enregistree. Le vendeur vous recontactera
                        @if ($shopPhone) au {{ $shopPhone }} @endif
                        au numero que vous avez indique.
                    </p>
                </div>
            </div>
        @endif

        {{-- Detail --}}
        <section class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <header class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-4 sm:px-6">
                <h2 class="text-base font-extrabold tracking-tight text-slate-900">Detail de la commande</h2>
                <x-badge :color="$details['status_color'] ?? 'slate'" dot>{{ $details['status_label'] }}</x-badge>
            </header>

            <ul class="divide-y divide-slate-100">
                @foreach ($details['items'] as $item)
                    <li wire:key="item-{{ $item['id'] }}" class="px-5 py-4 sm:px-6">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-slate-900">{{ $item['name'] }}</p>
                                <p class="text-xs text-slate-500">{{ $item['quantity'] }} × {{ $item['unit_price_label'] }}</p>
                                @if ($item['components'] !== [])
                                    <ul class="mt-2 flex flex-wrap gap-1.5">
                                        @foreach ($item['components'] as $component)
                                            <li class="rounded-full bg-brand-50 px-2.5 py-0.5 text-[11px] font-medium text-brand-800">{{ $component['quantity'] }}× {{ $component['name'] }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                            <p class="shrink-0 text-sm font-extrabold text-slate-900">{{ $item['line_total_label'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="flex items-baseline justify-between border-t border-slate-200 bg-slate-50/60 px-5 py-4 sm:px-6">
                <span class="text-base font-bold text-slate-900">Total</span>
                <span class="text-2xl font-extrabold tracking-tight text-brand-700">{{ $details['total_label'] }}</span>
            </div>
        </section>

        {{-- Livraison --}}
        <section class="mt-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6">
            <div class="flex items-center gap-3">
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-gradient-to-br from-brand-600 to-cyan-accent text-white">
                    <x-icon name="truck" :size="20" />
                </span>
                <h2 class="text-base font-extrabold tracking-tight text-slate-900">Livraison</h2>
            </div>
            <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-slate-400">Nom</dt>
                    <dd class="mt-0.5 font-semibold text-slate-900">{{ $details['customer_name'] }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-slate-400">Telephone</dt>
                    <dd class="mt-0.5 font-semibold text-slate-900">{{ $details['customer_phone'] }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold uppercase tracking-wider text-slate-400">Adresse</dt>
                    <dd class="mt-0.5 font-semibold text-slate-900">{{ $details['customer_address'] }}</dd>
                </div>
                @if ($details['customer_note'])
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-400">Precisions</dt>
                        <dd class="mt-0.5 text-slate-700">{{ $details['customer_note'] }}</dd>
                    </div>
                @endif
            </dl>
        </section>

        <p class="mt-5 flex items-start gap-2 rounded-xl bg-brand-50 p-4 text-xs text-brand-900">
            <x-icon name="lock" :size="15" class="mt-px shrink-0 text-brand-600" />
            Conservez ce lien : c'est le seul moyen de retrouver le detail de la commande {{ $details['number'] }}, aucun compte n'ayant ete cree.
        </p>

        <div class="mt-6 flex flex-wrap justify-center gap-2">
            <x-btn :href="route('catalog.index')" variant="primary">Continuer mes achats</x-btn>
            <x-btn :href="route('home')" variant="ghost">Retour a l'accueil</x-btn>
        </div>
    </div>
</div>
