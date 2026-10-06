<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    @include('partials.head')
</head>
<body class="min-h-full bg-slate-100 font-sans text-slate-900 antialiased">
    @php
        $user = auth()->user();
        $navigation = [
            ['label' => 'Tableau de bord', 'route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'icon' => 'chart'],
            ['label' => 'Commandes', 'route' => 'admin.orders.index', 'match' => 'admin.orders.*', 'icon' => 'clipboard'],
            ['label' => 'Statistiques', 'route' => 'admin.statistics', 'match' => 'admin.statistics', 'icon' => 'trending'],
            ['label' => 'Produits', 'route' => 'admin.products.index', 'match' => 'admin.products.*', 'icon' => 'box'],
            ['label' => 'Categories', 'route' => 'admin.categories.index', 'match' => 'admin.categories.*', 'icon' => 'tag'],
            ['label' => 'Packs', 'route' => 'admin.packs.index', 'match' => 'admin.packs.*', 'icon' => 'layers'],
            ['label' => 'Periodes', 'route' => 'admin.occasions.index', 'match' => 'admin.occasions.*', 'icon' => 'sparkles'],
            ['label' => 'Offres', 'route' => 'admin.offers.index', 'match' => 'admin.offers.*', 'icon' => 'star'],
            ['label' => 'Stocks', 'route' => 'admin.stock.index', 'match' => 'admin.stock.*', 'icon' => 'boxes'],
        ];
    @endphp

    <x-flash />

    <div x-data="{ sidebarOpen: false }" x-effect="document.body.style.overflow = sidebarOpen ? 'hidden' : ''">
        @foreach (['desktop', 'mobile'] as $mode)
            <aside
                @if ($mode === 'desktop')
                    class="fixed inset-y-0 left-0 hidden w-64 flex-col bg-admin-sidebar lg:flex"
                @else
                    x-show="sidebarOpen" x-cloak
                    x-transition:enter="transition-transform duration-200 ease-out" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
                    x-transition:leave="transition-transform duration-150 ease-in" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
                    class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-admin-sidebar lg:hidden"
                @endif
            >
                <div class="flex h-16 items-center gap-2.5 border-b border-white/60 px-5">
                    <img src="/images/logo.jpeg" alt="{{ $shop['name'] }}" class="h-10 w-10 shrink-0 rounded-xl bg-white object-contain" />
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-extrabold text-navy-900">{{ $shop['name'] }}</span>
                        <span class="block text-[11px] font-semibold uppercase tracking-wider text-brand-700">Administration</span>
                    </span>
                    @if ($mode === 'mobile')
                        <button type="button" class="grid h-9 w-9 place-items-center rounded-lg text-navy-800 hover:bg-white/60" aria-label="Fermer le menu" x-on:click="sidebarOpen = false">
                            <x-icon name="close" :size="20" />
                        </button>
                    @endif
                </div>

                <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                    @foreach ($navigation as $item)
                        @php($active = request()->routeIs($item['match']))
                        <a href="{{ route($item['route']) }}" wire:navigate @if ($mode === 'mobile') x-on:click="sidebarOpen = false" @endif
                            class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ $active ? 'bg-gradient-to-r from-brand-700 to-brand-600 text-white shadow-sm shadow-brand-900/40' : 'text-navy-800 hover:bg-white/60 hover:text-brand-800' }}">
                            <x-icon :name="$item['icon']" :size="18" />
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>

                <div class="border-t border-white/60 p-3">
                    <a href="{{ route('home') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-navy-800 transition hover:bg-white/60 hover:text-brand-800">
                        <x-icon name="eye" :size="18" />
                        Voir la boutique
                    </a>
                </div>
            </aside>
        @endforeach

        <div x-show="sidebarOpen" x-cloak x-transition.opacity class="fixed inset-0 z-40 bg-navy-950/60 lg:hidden" x-on:click="sidebarOpen = false"></div>

        <div class="lg:pl-64">
            <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
                <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
                    <button type="button" class="-ml-2 grid h-10 w-10 place-items-center rounded-lg text-slate-600 transition hover:bg-slate-100 lg:hidden" aria-label="Ouvrir le menu" x-on:click="sidebarOpen = true">
                        <x-icon name="menu" :size="22" />
                    </button>

                    <p class="min-w-0 flex-1 truncate text-sm font-semibold text-slate-500">
                        Bonjour, <span class="text-slate-900">{{ $user?->name }}</span>
                    </p>

                    <div x-data="{ open: false }" class="relative" x-on:click.outside="open = false">
                        <button type="button" class="flex items-center gap-2 rounded-full p-1 pr-2.5 text-sm transition hover:bg-slate-100" x-on:click="open = ! open">
                            <span class="grid h-8 w-8 place-items-center rounded-full bg-gradient-to-br from-brand-600 to-cyan-accent text-xs font-bold text-white">
                                {{ mb_strtoupper(mb_substr($user?->name ?? '?', 0, 2)) }}
                            </span>
                            <x-icon name="chevron-down" :size="14" class="text-slate-400" />
                        </button>

                        <div x-show="open" x-cloak x-transition.opacity class="absolute right-0 mt-2 w-56 overflow-hidden rounded-xl bg-white py-1 shadow-lg ring-1 ring-slate-200">
                            <div class="border-b border-slate-100 px-4 py-2.5">
                                <p class="truncate text-sm font-bold text-slate-900">{{ $user?->name }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $user?->email }}</p>
                            </div>
                            <a href="{{ route('admin.profile.edit') }}" wire:navigate class="flex items-center gap-2.5 px-4 py-2 text-sm text-slate-700 transition hover:bg-slate-50">
                                <x-icon name="settings" :size="16" class="text-slate-400" />
                                Mon compte
                            </a>
                            <form method="post" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="flex w-full items-center gap-2.5 px-4 py-2 text-left text-sm text-red-600 transition hover:bg-red-50">
                                    <x-icon name="logout" :size="16" />
                                    Se deconnecter
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main class="p-4 sm:p-6 lg:p-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
