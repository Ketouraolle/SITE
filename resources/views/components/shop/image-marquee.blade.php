@props(['items', 'reverse' => false])

@php
    $items = collect($items)->values();
    // La liste est doublee : l'animation glisse de -50 % puis reboucle sans saut.
    $loop = $items->concat($items);
@endphp

<div {{ $attributes->merge(['class' => 'group/marquee relative overflow-hidden']) }}>
    <div class="flex w-max animate-marquee gap-4 group-hover/marquee:[animation-play-state:paused] {{ $reverse ? '[animation-direction:reverse]' : '' }}">
        @foreach ($loop as $index => $item)
            <a
                href="{{ route('catalog.show', $item['slug']) }}"
                wire:navigate
                class="group/item relative block h-56 w-44 shrink-0 overflow-hidden rounded-2xl bg-brand-100 shadow-sm ring-1 ring-slate-200 sm:h-72 sm:w-56"
                @if ($index >= $items->count()) aria-hidden="true" tabindex="-1" @endif
            >
                <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" loading="lazy" class="h-full w-full object-cover transition duration-700 ease-out group-hover/item:scale-110" />
                <span class="absolute inset-0 flex items-end bg-gradient-to-t from-navy-950/80 via-navy-950/10 to-transparent p-4 opacity-0 transition duration-500 group-hover/item:opacity-100">
                    <span class="translate-y-3 text-sm font-semibold text-white transition duration-500 group-hover/item:translate-y-0">{{ $item['name'] }}</span>
                </span>
            </a>
        @endforeach
    </div>

    {{-- Fondu sur les bords. --}}
    <div class="pointer-events-none absolute inset-y-0 left-0 w-16 bg-gradient-to-r from-slate-50 to-transparent sm:w-32"></div>
    <div class="pointer-events-none absolute inset-y-0 right-0 w-16 bg-gradient-to-l from-slate-50 to-transparent sm:w-32"></div>
</div>
