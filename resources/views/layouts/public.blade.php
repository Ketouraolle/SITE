<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    @include('partials.head')
</head>
<body class="site-public min-h-full bg-slate-50 font-sans text-slate-900 antialiased">
    @php
        $navLinks = [
            ['label' => 'Accueil', 'href' => route('home'), 'icon' => 'home', 'active' => request()->routeIs('home')],
            ['label' => 'Catalogue', 'href' => route('catalog.index'), 'icon' => 'boxes', 'active' => request()->routeIs('catalog.*')],
            ['label' => 'Packs', 'href' => route('packs.index'), 'icon' => 'layers', 'active' => request()->routeIs('packs.*')],
            ['label' => 'Mon pack', 'href' => route('custom-pack.create'), 'icon' => 'sparkles', 'active' => request()->routeIs('custom-pack.*')],
        ];
    @endphp

    <x-flash />

    <div x-data="{ menuOpen: false, searchOpen: false }" x-effect="document.body.style.overflow = menuOpen ? 'hidden' : ''" class="flex min-h-dvh flex-col">
        {{-- Bandeau de contact --}}
        <div class="hidden bg-navy-950 text-slate-300 sm:block">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-2 text-xs sm:px-6 lg:px-8">
                <p class="flex items-center gap-1.5">
                    <x-icon name="pin" :size="14" class="text-cyan-accent" />
                    <span>{{ $shop['address'] ?: 'Livraison en ville' }}</span>
                </p>
                <div class="flex items-center gap-5">
                    @if ($shop['phone'])
                        <a href="tel:{{ preg_replace('/\s/', '', $shop['phone']) }}" class="flex items-center gap-1.5 hover:text-white">
                            <x-icon name="phone" :size="14" class="text-cyan-accent" />
                            <span>{{ $shop['phone'] }}</span>
                        </a>
                    @endif
                    @if ($shop['email'])
                        <a href="mailto:{{ $shop['email'] }}" class="hidden items-center gap-1.5 hover:text-white md:flex">
                            <x-icon name="mail" :size="14" class="text-cyan-accent" />
                            <span>{{ $shop['email'] }}</span>
                        </a>
                    @endif
                    <a href="{{ route('login') }}" class="flex items-center gap-1.5 hover:text-white">
                        <x-icon name="user" :size="14" class="text-cyan-accent" />
                        <span>Espace admin</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- En-tete --}}
        <header class="sticky top-0 z-40 bg-white/95 shadow-sm shadow-slate-900/5 backdrop-blur">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-18 items-center gap-3 py-3">
                    <button type="button" class="-ml-2 grid h-10 w-10 place-items-center rounded-lg text-slate-600 transition hover:bg-slate-100 lg:hidden" aria-label="Ouvrir le menu" x-on:click="menuOpen = true">
                        <x-icon name="menu" :size="22" />
                    </button>

                    <a href="{{ route('home') }}" wire:navigate class="group flex min-w-0 items-center gap-2.5">
                        <img src="/images/logo.jpeg" alt="{{ $shop['name'] }}" class="h-10 w-10 shrink-0 rounded-xl sm:h-12 sm:w-12 object-contain ring-1 ring-slate-200 transition duration-300 group-hover:scale-105" />
                        <span class="flex min-w-0 flex-col leading-tight">
                            <span class="truncate text-base font-extrabold tracking-tight text-navy-900 sm:text-xl">{{ $shop['name'] }}</span>
                            <span class="hidden font-script text-lg leading-none text-brand-600 sm:block">by the Martines &amp; Angèle</span>
                        </span>
                    </a>

                    <nav class="ml-8 hidden items-center gap-1 lg:flex">
                        @foreach ($navLinks as $link)
                            <a href="{{ $link['href'] }}" wire:navigate
                                class="relative rounded-full px-4 py-2 text-sm font-semibold transition {{ $link['active'] ? 'bg-brand-50 text-brand-700' : 'text-slate-700 hover:bg-slate-100 hover:text-brand-700' }}">
                                {{ $link['label'] }}
                            </a>
                        @endforeach
                    </nav>

                    <div class="ml-auto flex shrink-0 items-center gap-1 sm:gap-1.5">
                        <form action="{{ route('catalog.index') }}" method="get" class="hidden xl:block">
                            <div class="relative w-64">
                                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"><x-icon name="search" :size="18" /></span>
                                <input type="search" name="q" value="{{ request()->routeIs('catalog.index') ? request('q') : '' }}" placeholder="Rechercher un produit…" aria-label="Rechercher un produit"
                                    class="w-full rounded-full border-0 bg-slate-100 py-2.5 pl-10 pr-4 text-sm text-slate-900 ring-1 ring-inset ring-transparent placeholder:text-slate-400 focus:bg-white focus:ring-brand-600" />
                            </div>
                        </form>

                        <button type="button" class="grid h-10 w-10 place-items-center rounded-full text-slate-600 transition hover:bg-slate-100 xl:hidden" aria-label="Rechercher" x-on:click="searchOpen = ! searchOpen">
                            <x-icon name="search" :size="20" />
                        </button>

                        <a href="{{ route('cart.index') }}" wire:navigate class="relative grid h-10 w-10 place-items-center rounded-full text-slate-700 transition hover:bg-slate-100" aria-label="Voir le panier">
                            <x-icon name="cart" :size="21" />
                            <livewire:cart-badge />
                        </a>

                        @if ($shop['whatsapp_url'])
                            <x-btn :href="$shop['whatsapp_url']" external variant="accent" size="sm" class="ml-1 hidden md:inline-flex">
                                <x-icon name="whatsapp" :size="16" />
                                Commander
                            </x-btn>
                        @endif
                    </div>
                </div>

                {{-- Recherche depliable --}}
                <form action="{{ route('catalog.index') }}" method="get" x-show="searchOpen" x-cloak class="pb-3 xl:hidden">
                    <div class="relative">
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"><x-icon name="search" :size="18" /></span>
                        <input type="search" name="q" placeholder="Rechercher un produit…" aria-label="Rechercher un produit"
                            class="w-full rounded-full border-0 bg-slate-100 py-2.5 pl-10 pr-4 text-sm ring-1 ring-inset ring-transparent focus:bg-white focus:ring-brand-600" />
                    </div>
                </form>
            </div>

            {{-- Categories --}}
            @if ($navCategories->isNotEmpty())
                <div class="hidden bg-gradient-to-r from-brand-800 via-brand-700 to-brand-600 lg:block">
                    <div class="mx-auto flex max-w-7xl items-center gap-1 overflow-x-auto px-4 py-1.5 no-scrollbar sm:px-6 lg:px-8">
                        <span class="mr-2 flex shrink-0 items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-white/70">
                            <x-icon name="tag" :size="14" /> Categories
                        </span>
                        @foreach ($navCategories as $category)
                            <a href="{{ route('catalog.index', ['category' => $category->slug]) }}" wire:navigate
                                class="whitespace-nowrap rounded-full px-3 py-1 text-xs font-semibold text-white/90 transition hover:bg-white/15 hover:text-white">
                                {{ $category->name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </header>

        {{-- Menu mobile --}}
        <div x-show="menuOpen" x-cloak x-transition.opacity class="fixed inset-0 z-50 bg-navy-950/60 lg:hidden" x-on:click="menuOpen = false"></div>
        <aside x-show="menuOpen" x-cloak
            x-transition:enter="transition-transform duration-200 ease-out" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition-transform duration-150 ease-in" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
            class="fixed inset-y-0 left-0 z-50 flex w-[85%] max-w-xs flex-col bg-white shadow-xl lg:hidden">
            <div class="flex h-18 items-center justify-between bg-hero px-4 py-3">
                <span class="flex items-center gap-2">
                    <img src="/images/logo.jpeg" alt="{{ $shop['name'] }}" class="h-10 w-10 rounded-lg bg-white object-contain" />
                    <span class="text-base font-extrabold text-white">{{ $shop['name'] }}</span>
                </span>
                <button type="button" class="grid h-9 w-9 place-items-center rounded-lg text-white/80 hover:bg-white/10" aria-label="Fermer le menu" x-on:click="menuOpen = false">
                    <x-icon name="close" :size="20" />
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto p-3">
                @foreach ($navLinks as $link)
                    <a href="{{ $link['href'] }}" wire:navigate x-on:click="menuOpen = false"
                        class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ $link['active'] ? 'bg-brand-50 text-brand-700' : 'text-slate-700 hover:bg-slate-100' }}">
                        <x-icon :name="$link['icon']" :size="18" />
                        {{ $link['label'] }}
                    </a>
                @endforeach

                @if ($navCategories->isNotEmpty())
                    <p class="mt-5 px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">Categories</p>
                    @foreach ($navCategories as $category)
                        <a href="{{ route('catalog.index', ['category' => $category->slug]) }}" wire:navigate x-on:click="menuOpen = false"
                            class="block rounded-xl px-3 py-2 text-sm text-slate-600 transition hover:bg-slate-100">
                            {{ $category->name }}
                        </a>
                    @endforeach
                @endif
            </nav>

            @if ($shop['whatsapp_url'])
                <div class="border-t border-slate-200 p-3">
                    <x-btn :href="$shop['whatsapp_url']" external variant="whatsapp" block size="sm">
                        <x-icon name="whatsapp" :size="16" />
                        Nous ecrire
                    </x-btn>
                </div>
            @endif
        </aside>

        <main class="flex-1 pb-20 lg:pb-0">
            {{ $slot }}
        </main>

        {{-- Engagements --}}
        <section class="mt-16 border-y border-slate-200 bg-white">
            <div class="mx-auto grid max-w-7xl grid-cols-2 gap-6 px-4 py-8 sm:px-6 lg:grid-cols-4 lg:px-8">
                @foreach ([
                    ['icon' => 'truck', 'title' => 'Livraison rapide', 'text' => 'Partout en ville'],
                    ['icon' => 'shield', 'title' => 'Paiement a la livraison', 'text' => 'Aucun compte requis'],
                    ['icon' => 'whatsapp', 'title' => 'Suivi sur WhatsApp', 'text' => 'Une vraie personne vous repond'],
                    ['icon' => 'star', 'title' => 'Produits selectionnes', 'text' => 'Qualite verifiee'],
                ] as $promise)
                    <div class="flex items-center gap-3">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-brand-600 to-cyan-accent text-white shadow-sm shadow-brand-700/30">
                            <x-icon :name="$promise['icon']" :size="20" />
                        </span>
                        <span>
                            <span class="block text-sm font-bold text-slate-900">{{ $promise['title'] }}</span>
                            <span class="block text-xs text-slate-500">{{ $promise['text'] }}</span>
                        </span>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Pied de page --}}
        <footer class="bg-navy-950 pb-20 text-slate-300 lg:pb-0">
            <div class="h-1 bg-gradient-to-r from-brand-600 via-cyan-accent to-accent-500"></div>
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <div class="flex items-center gap-3">
                            <img src="/images/logo.jpeg" alt="{{ $shop['name'] }}" class="h-14 w-14 rounded-xl bg-white object-contain" />
                            <span class="text-lg font-extrabold text-white">{{ $shop['name'] }}</span>
                        </div>
                        <p class="mt-4 text-sm leading-relaxed text-slate-400">
                            Commandez en quelques clics, sans creer de compte. Nous finalisons ensemble sur WhatsApp.
                        </p>
                    </div>

                    <div>
                        <h2 class="text-xl text-brand-200">Boutique</h2>
                        <ul class="mt-4 space-y-2.5 text-sm">
                            @foreach ($navLinks as $link)
                                <li><a href="{{ $link['href'] }}" wire:navigate class="text-slate-400 transition hover:text-cyan-accent">{{ $link['label'] }}</a></li>
                            @endforeach
                            <li><a href="{{ route('cart.index') }}" wire:navigate class="text-slate-400 transition hover:text-cyan-accent">Panier</a></li>
                        </ul>
                    </div>

                    @if ($navCategories->isNotEmpty())
                        <div>
                            <h2 class="text-xl text-brand-200">Categories</h2>
                            <ul class="mt-4 space-y-2.5 text-sm">
                                @foreach ($navCategories->take(6) as $category)
                                    <li>
                                        <a href="{{ route('catalog.index', ['category' => $category->slug]) }}" wire:navigate class="text-slate-400 transition hover:text-cyan-accent">{{ $category->name }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div>
                        <h2 class="text-xl text-brand-200">Nous contacter</h2>
                        <ul class="mt-4 space-y-3 text-sm text-slate-400">
                            @if ($shop['phone'])
                                <li class="flex items-center gap-2">
                                    <x-icon name="phone" :size="16" class="text-cyan-accent" />
                                    <a href="tel:{{ preg_replace('/\s/', '', $shop['phone']) }}" class="transition hover:text-white">{{ $shop['phone'] }}</a>
                                </li>
                            @endif
                            @if ($shop['email'])
                                <li class="flex items-center gap-2">
                                    <x-icon name="mail" :size="16" class="text-cyan-accent" />
                                    <a href="mailto:{{ $shop['email'] }}" class="transition hover:text-white">{{ $shop['email'] }}</a>
                                </li>
                            @endif
                            @if ($shop['address'])
                                <li class="flex items-start gap-2">
                                    <x-icon name="pin" :size="16" class="mt-0.5 text-cyan-accent" />
                                    <span>{{ $shop['address'] }}</span>
                                </li>
                            @endif
                        </ul>

                        @if ($shop['whatsapp_url'])
                            <x-btn :href="$shop['whatsapp_url']" external variant="whatsapp" size="sm" class="mt-5">
                                <x-icon name="whatsapp" :size="16" />
                                WhatsApp
                            </x-btn>
                        @endif
                    </div>
                </div>

                <div class="mt-10 flex flex-col gap-2 border-t border-white/10 pt-6 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                    <p>© {{ now()->year }} {{ $shop['name'] }}. Tous droits reserves.</p>
                    <a href="{{ route('login') }}" class="transition hover:text-white">Espace administrateur</a>
                </div>
            </div>
        </footer>

        {{-- Barre de navigation mobile --}}
        <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur lg:hidden">
            <div class="grid grid-cols-5">
                @foreach ($navLinks as $link)
                    <a href="{{ $link['href'] }}" wire:navigate class="flex flex-col items-center gap-0.5 py-2.5 text-[11px] font-semibold transition {{ $link['active'] ? 'text-brand-700' : 'text-slate-500' }}">
                        <x-icon :name="$link['icon']" :size="20" />
                        {{ $link['label'] }}
                    </a>
                @endforeach
                <a href="{{ route('cart.index') }}" wire:navigate class="flex flex-col items-center gap-0.5 py-2.5 text-[11px] font-semibold transition {{ request()->routeIs('cart.*') ? 'text-brand-700' : 'text-slate-500' }}">
                    <span class="relative">
                        <x-icon name="cart" :size="20" />
                        <livewire:cart-badge variant="tab" />
                    </span>
                    Panier
                </a>
            </div>
        </nav>
    </div>

    @livewireScripts
</body>
</html>
