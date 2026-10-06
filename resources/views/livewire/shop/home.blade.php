@php
    $shopName = config('shop.name');

    $advantages = [
        ['icon' => 'user', 'title' => 'Sans compte', 'text' => '0 inscription : commandez en quelques clics.'],
        ['icon' => 'whatsapp', 'title' => 'Commande via WhatsApp', 'text' => 'Nous finalisons ensemble, en direct.'],
        ['icon' => 'shield', 'title' => 'Paiement a la livraison', 'text' => 'Vous payez a la reception de votre colis.'],
        ['icon' => 'truck', 'title' => 'Livraison en ville', 'text' => 'Vos articles livres directement chez vous.'],
    ];

    $steps = [
        ['title' => 'Choisissez vos produits', 'text' => 'Parcourez le catalogue, ajoutez au panier, ajustez les quantites.', 'icon' => 'boxes'],
        ['title' => 'Renseignez vos coordonnees', 'text' => 'Nom, telephone et adresse de livraison. Aucun compte requis.', 'icon' => 'user'],
        ['title' => 'Validez sur WhatsApp', 'text' => 'Votre commande part preremplie au vendeur, avec son numero unique.', 'icon' => 'whatsapp'],
    ];
@endphp

<div>
    {{-- Bandeau principal --}}
    <section class="relative overflow-hidden bg-hero text-white">
        <div class="pointer-events-none absolute -left-32 top-1/3 h-96 w-96 rounded-full border border-white/5"></div>
        <div class="pointer-events-none absolute -right-40 -top-40 h-[32rem] w-[32rem] rounded-full border border-white/10"></div>
        <div class="pointer-events-none absolute -right-20 -top-20 h-80 w-80 rounded-full border border-white/10"></div>

        <div class="relative mx-auto grid max-w-7xl items-center gap-12 px-4 pb-24 pt-12 sm:px-6 sm:pt-16 lg:grid-cols-2 lg:gap-16 lg:px-8 lg:pb-32 lg:pt-20">
            <div class="animate-fade-up text-center lg:text-left">
                <p class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-1.5 text-xs font-bold uppercase tracking-[0.2em] text-brand-100 ring-1 ring-inset ring-white/20 backdrop-blur">
                    <span class="h-2 w-2 animate-pulse rounded-full bg-cyan-accent"></span>
                    Bienvenue chez {{ $shopName }}
                </p>

                <p class="mt-5 font-script text-3xl leading-none text-brand-100 sm:text-4xl">by the Martines &amp; Angèle</p>

                <h1 class="mt-3 text-4xl font-extrabold leading-[1.08] tracking-tight sm:text-5xl lg:text-6xl">
                    Clean Era Temple,
                    <span class="text-gradient">livre chez vous</span>
                </h1>

                <p class="mx-auto mt-6 max-w-xl text-base text-brand-100 sm:text-lg lg:mx-0">
                    Bijoux, habits, pyjamas et beaute choisis avec amour, pour elle
                    comme pour lui. Ajoutez vos coups de coeur au panier : nous
                    finalisons la commande ensemble sur WhatsApp, sans compte a creer.
                </p>

                <div class="mt-8 flex flex-wrap justify-center gap-3 lg:justify-start">
                    <x-btn :href="route('catalog.index')" variant="accent" size="lg" class="px-7">
                        Voir le catalogue
                        <x-icon name="arrow-right" :size="18" />
                    </x-btn>
                    <x-btn :href="route('custom-pack.create')" variant="outline-light" size="lg" class="px-7">
                        <x-icon name="sparkles" :size="18" />
                        Composer mon pack
                    </x-btn>
                </div>

                {{-- Chiffres cles --}}
                <div class="mx-auto mt-10 grid max-w-lg grid-cols-3 gap-3 lg:mx-0">
                    <div class="rounded-2xl bg-white/5 px-3 py-4 ring-1 ring-inset ring-white/10 backdrop-blur">
                        <p class="text-2xl font-extrabold tracking-tight sm:text-3xl">{{ $stats['products'] }}</p>
                        <p class="mt-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-brand-200">Produits</p>
                    </div>
                    <div class="rounded-2xl bg-white/5 px-3 py-4 ring-1 ring-inset ring-white/10 backdrop-blur">
                        <p class="text-2xl font-extrabold tracking-tight sm:text-3xl">{{ $stats['categories'] }}</p>
                        <p class="mt-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-brand-200">Rayons</p>
                    </div>
                    <div class="rounded-2xl bg-white/5 px-3 py-4 ring-1 ring-inset ring-white/10 backdrop-blur">
                        <p class="text-2xl font-extrabold tracking-tight sm:text-3xl">{{ $stats['packs'] }}</p>
                        <p class="mt-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-brand-200">Packs</p>
                    </div>
                </div>
            </div>

            {{-- Carrousel des produits en photo --}}
            @if ($gallery->isNotEmpty())
                <div class="relative mx-auto w-full max-w-md animate-fade-up [animation-delay:150ms]">
                    <div class="absolute -inset-4 rotate-3 rounded-[2rem] bg-gradient-to-br from-cyan-accent/40 to-brand-500/30 blur-sm"></div>
                    <div class="relative aspect-[4/5] overflow-hidden rounded-[2rem] shadow-2xl shadow-navy-950/50 ring-1 ring-white/20">
                        <x-shop.hero-carousel :slides="$gallery->take(8)" />
                    </div>

                    <div class="absolute -bottom-6 -left-4 flex items-center gap-3 rounded-2xl bg-white p-3 pr-5 text-slate-900 shadow-xl sm:-left-8">
                        <img src="/images/logo.jpeg" alt="{{ $shopName }}" class="h-12 w-12 rounded-xl object-contain ring-1 ring-slate-200" />
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-brand-600">Paiement</p>
                            <p class="text-sm font-extrabold">A la livraison</p>
                        </div>
                    </div>

                    <div class="absolute -right-3 top-8 hidden items-center gap-2 rounded-2xl bg-white px-4 py-3 text-slate-900 shadow-xl sm:flex">
                        <span class="grid h-9 w-9 place-items-center rounded-xl bg-[#25D366] text-white"><x-icon name="whatsapp" :size="18" /></span>
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500">Commande</p>
                            <p class="text-sm font-extrabold">Via WhatsApp</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>

    {{-- Atouts --}}
    <section class="relative z-10 mx-auto -mt-14 max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid gap-px overflow-hidden rounded-2xl bg-slate-200 shadow-xl shadow-navy-900/10 ring-1 ring-slate-200 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($advantages as $advantage)
                <div class="flex items-start gap-4 bg-white p-5 sm:p-6">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-brand-600 to-cyan-accent text-white shadow-md shadow-brand-600/20">
                        <x-icon :name="$advantage['icon']" :size="20" />
                    </span>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">{{ $advantage['title'] }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $advantage['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Bandeau defilant --}}
    @if ($gallery->isNotEmpty())
        <section class="py-14 sm:py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <x-section-heading eyebrow="Nos coups de coeur" title="Laissez-vous inspirer" subtitle="Un apercu de nos produits du moment : cliquez sur une photo pour la decouvrir." center />
            </div>

            <div class="mt-10">
                <x-shop.image-marquee :items="$gallery" />
            </div>
        </section>
    @endif

    {{-- Rayons --}}
    @if ($categories->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <x-section-heading eyebrow="Nos univers" title="Nos rayons" subtitle="Trouvez rapidement ce que vous cherchez." />
                <a href="{{ route('catalog.index') }}" wire:navigate class="hidden shrink-0 items-center gap-1.5 text-sm font-bold text-brand-700 hover:text-brand-900 sm:inline-flex">
                    Tout le catalogue
                    <x-icon name="arrow-right" :size="16" />
                </a>
            </div>

            <div class="no-scrollbar mt-8 flex gap-4 overflow-x-auto pb-2 sm:grid sm:grid-cols-2 sm:overflow-visible lg:grid-cols-3">
                @foreach ($categories as $category)
                    <a
                        href="{{ route('catalog.index', ['category' => $category['slug']]) }}"
                        wire:navigate
                        wire:key="category-{{ $category['id'] }}"
                        class="group relative block aspect-[4/5] min-w-[14rem] overflow-hidden rounded-2xl bg-brand-100 shadow-sm ring-1 ring-slate-200 transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-brand-900/15 sm:aspect-[4/3] sm:min-w-0"
                    >
                        @if ($category['image'])
                            <img src="{{ $category['image'] }}" alt="{{ $category['name'] }}" loading="lazy" class="absolute inset-0 h-full w-full object-cover transition duration-700 ease-out group-hover:scale-110" />
                        @else
                            <span class="absolute inset-0 grid place-items-center bg-gradient-to-br from-brand-100 to-cyan-accent/30 text-brand-700">
                                <x-icon name="tag" :size="36" />
                            </span>
                        @endif

                        <span class="absolute inset-0 bg-gradient-to-t from-navy-950/85 via-navy-950/20 to-transparent transition duration-500 group-hover:from-brand-950/90"></span>

                        <span class="absolute inset-x-0 bottom-0 p-5 text-white">
                            <span class="block text-xl font-extrabold tracking-tight sm:text-2xl">{{ $category['name'] }}</span>
                            <span class="mt-2 flex items-center justify-between text-xs font-semibold uppercase tracking-[0.16em] text-brand-100">
                                {{ $category['products_count'] }} produit(s)
                                <span class="grid h-9 w-9 place-items-center rounded-full bg-white/15 backdrop-blur transition duration-500 group-hover:translate-x-1 group-hover:bg-accent-500 group-hover:text-white">
                                    <x-icon name="arrow-right" :size="16" />
                                </span>
                            </span>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Periodes en cours : Noel, Saint-Valentin, Aid... --}}
    @foreach ($occasions as $occasion)
        <section wire:key="occasion-{{ $occasion['id'] }}" class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="relative overflow-hidden bg-hero px-5 py-7 text-white sm:px-8">
                    <div class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full border border-white/10"></div>
                    <div class="relative flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p class="inline-flex items-center gap-1.5 rounded-full bg-accent-500 px-3 py-1 text-xs font-bold text-white shadow">
                                <x-icon name="sparkles" :size="14" />
                                {{ $occasion['name'] }}
                                @if ($occasion['best_offer'])
                                    <span>&middot; jusqu'a -{{ $occasion['best_offer'] }} %</span>
                                @endif
                            </p>
                            <h2 class="mt-3 text-xl font-extrabold tracking-tight sm:text-2xl">{{ $occasion['description'] ?: 'Special '.$occasion['name'] }}</h2>
                            <p class="mt-1 flex items-center gap-1.5 text-sm text-brand-100">
                                <x-icon name="clock" :size="15" />
                                {{ $occasion['period_label'] }}
                            </p>
                        </div>
                        <x-btn :href="route('occasions.show', $occasion['slug'])" variant="outline-light" size="sm">
                            Tout voir
                            <x-icon name="arrow-right" :size="16" />
                        </x-btn>
                    </div>
                </div>

                <div class="p-4 sm:p-6">
                    @if ($occasion['packs']->isNotEmpty())
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($occasion['packs'] as $pack)
                                <x-shop.pack-card :pack="$pack" :key-prefix="'occ-'.$occasion['id'].'-'" wire:key="occ-{{ $occasion['id'] }}-pack-{{ $pack['id'] }}" />
                            @endforeach
                        </div>
                    @endif

                    @if ($occasion['products']->isNotEmpty())
                        <div class="{{ $occasion['packs']->isNotEmpty() ? 'mt-5' : '' }} grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                            @foreach ($occasion['products'] as $product)
                                <x-shop.product-card :product="$product" :key-prefix="'occ-'.$occasion['id'].'-'" wire:key="occ-{{ $occasion['id'] }}-product-{{ $product['id'] }}" />
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endforeach

    {{-- Produits en vedette --}}
    @if ($featured->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <x-section-heading eyebrow="Selection du moment" title="Nos produits vedettes" subtitle="Les produits les plus demandes." />
                <a href="{{ route('catalog.index') }}" wire:navigate class="hidden shrink-0 items-center gap-1.5 text-sm font-bold text-brand-700 hover:text-brand-900 sm:inline-flex">
                    Tout voir
                    <x-icon name="arrow-right" :size="16" />
                </a>
            </div>

            <div class="mt-8 grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-4">
                @foreach ($featured as $product)
                    <x-shop.product-card :product="$product" key-prefix="featured-" wire:key="featured-{{ $product['id'] }}" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- Packs --}}
    @if ($packs->isNotEmpty())
        <section class="bg-gradient-to-b from-brand-50/70 to-slate-50 py-14">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <x-section-heading eyebrow="Prix groupes" title="Nos packs prets a commander" subtitle="Des ensembles de produits a prix groupe." />
                    <a href="{{ route('packs.index') }}" wire:navigate class="hidden shrink-0 items-center gap-1.5 text-sm font-bold text-brand-700 hover:text-brand-900 sm:inline-flex">
                        Tous les packs
                        <x-icon name="arrow-right" :size="16" />
                    </a>
                </div>

                <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($packs as $pack)
                        <x-shop.pack-card :pack="$pack" key-prefix="home-" wire:key="home-pack-{{ $pack['id'] }}" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Pack personnalise --}}
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="relative overflow-hidden rounded-3xl bg-hero px-6 py-10 text-white shadow-xl shadow-navy-900/20 sm:px-12 sm:py-14">
            <div class="pointer-events-none absolute -right-20 -top-20 h-72 w-72 rounded-full border border-white/10"></div>
            <div class="pointer-events-none absolute -right-6 -top-6 h-44 w-44 rounded-full border border-white/10"></div>
            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-xl">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-accent">Sur mesure</p>
                    <h2 class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">Composez votre propre pack</h2>
                    <p class="mt-3 text-sm text-brand-100 sm:text-base">
                        Choisissez librement vos produits et leurs quantites, nous calculons
                        le total au fur et a mesure. Entre
                        {{ $customPack['min_products'] ?? 2 }} et
                        {{ $customPack['max_products'] ?? 20 }} produits par pack.
                    </p>
                </div>
                <x-btn :href="route('custom-pack.create')" variant="accent" size="lg" class="shrink-0 px-7">
                    <x-icon name="sparkles" :size="18" />
                    Commencer
                </x-btn>
            </div>
        </div>
    </section>

    {{-- Nouveautes --}}
    @if ($latest->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <x-section-heading eyebrow="Nouveautes" title="Derniers arrivages" subtitle="Les derniers produits ajoutes a la boutique." />

            <div class="mt-8 grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-4">
                @foreach ($latest as $product)
                    <x-shop.product-card :product="$product" key-prefix="latest-" wire:key="latest-{{ $product['id'] }}" />
                @endforeach
            </div>

            <div class="mt-10 text-center">
                <x-btn :href="route('catalog.index')" variant="primary" size="lg">
                    Voir tout le catalogue
                    <x-icon name="arrow-right" :size="18" />
                </x-btn>
            </div>
        </section>
    @endif

    {{-- Comment commander --}}
    <section class="mx-auto max-w-7xl px-4 pb-16 pt-10 sm:px-6 lg:px-8">
        <x-section-heading eyebrow="Simple et rapide" title="Comment ca marche ?" center />

        <ol class="mt-10 grid gap-5 sm:grid-cols-3">
            @foreach ($steps as $step)
                <li class="relative rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-brand-900/10 hover:ring-brand-200">
                    <span class="absolute right-5 top-4 text-5xl font-extrabold tracking-tight text-slate-100">{{ $loop->iteration }}</span>
                    <span class="relative grid h-11 w-11 place-items-center rounded-xl bg-gradient-to-br from-brand-600 to-cyan-accent text-white">
                        <x-icon :name="$step['icon']" :size="20" />
                    </span>
                    <h3 class="relative mt-4 text-base font-extrabold text-slate-900">{{ $loop->iteration }}. {{ $step['title'] }}</h3>
                    <p class="relative mt-1 text-sm text-slate-600">{{ $step['text'] }}</p>
                </li>
            @endforeach
        </ol>
    </section>
</div>
