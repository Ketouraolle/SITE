<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    @include('partials.head')
</head>
<body class="min-h-full bg-hero font-sans text-slate-900 antialiased">
    <x-flash />

    <div class="flex min-h-dvh flex-col items-center justify-center px-4 py-10">
        <a href="{{ route('home') }}" class="flex flex-col items-center gap-3">
            <img src="/images/logo.jpeg" alt="{{ $shop['name'] }}" class="h-20 w-20 rounded-2xl bg-white object-contain shadow-lg shadow-navy-950/40" />
            <span class="text-xl font-extrabold tracking-tight text-white">{{ $shop['name'] }}</span>
        </a>

        <div class="mt-8 w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl shadow-navy-950/40 sm:p-8">
            {{ $slot }}
        </div>

        <a href="{{ route('home') }}" class="mt-6 flex items-center gap-1.5 text-sm font-semibold text-white/80 transition hover:text-white">
            <x-icon name="arrow-left" :size="16" />
            Retour a la boutique
        </a>
    </div>

    @livewireScripts
</body>
</html>
