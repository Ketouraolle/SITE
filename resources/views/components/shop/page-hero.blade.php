@props(['title', 'subtitle' => null, 'eyebrow' => null, 'breadcrumbs' => []])

{{-- Bandeau de page public : degrade marine -> bleu, fil d'Ariane, titre blanc. --}}
<section {{ $attributes->merge(['class' => 'relative overflow-hidden bg-hero text-white']) }}>
    <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full border border-white/10"></div>
    <div class="pointer-events-none absolute -right-10 -top-10 h-44 w-44 rounded-full border border-white/10"></div>

    <div class="relative mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
        @if (count($breadcrumbs))
            <nav class="flex flex-wrap items-center gap-1.5 text-xs text-brand-100/80" aria-label="Fil d'Ariane">
                <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-1 transition hover:text-white">
                    <x-icon name="home" :size="13" />
                    Accueil
                </a>
                @foreach ($breadcrumbs as $crumb)
                    <x-icon name="chevron-right" :size="12" class="text-brand-200/60" />
                    @if ($crumb['href'] ?? null)
                        <a href="{{ $crumb['href'] }}" wire:navigate class="transition hover:text-white">{{ $crumb['label'] }}</a>
                    @else
                        <span class="truncate font-semibold text-white">{{ $crumb['label'] }}</span>
                    @endif
                @endforeach
            </nav>
        @endif

        @if ($eyebrow)
            <p class="mt-5 text-xs font-bold uppercase tracking-[0.2em] text-cyan-accent">{{ $eyebrow }}</p>
        @endif

        <h1 class="{{ $eyebrow ? 'mt-2' : 'mt-4' }} max-w-3xl text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-5xl">{{ $title }}</h1>

        @if ($subtitle)
            <p class="mt-3 max-w-2xl text-sm text-brand-100 sm:text-base">{{ $subtitle }}</p>
        @endif

        {{ $slot }}
    </div>
</section>
