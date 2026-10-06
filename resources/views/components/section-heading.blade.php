@props(['eyebrow' => null, 'title', 'subtitle' => null, 'center' => false])

{{-- Titre de section : accroche calligraphiee, titre serif, filet rose et or. --}}
<div {{ $attributes->merge(['class' => $center ? 'mx-auto max-w-2xl text-center' : '']) }}>
    @if ($eyebrow)
        <p class="font-script text-2xl leading-none text-brand-600 sm:text-3xl">{{ $eyebrow }}</p>
    @endif
    <h2 class="mt-1 text-3xl text-slate-900 sm:text-4xl">{{ $title }}</h2>
    <span class="mt-3 block h-1 w-14 rounded-full bg-gradient-to-r from-brand-600 to-cyan-accent {{ $center ? 'mx-auto' : '' }}"></span>
    @if ($subtitle)
        <p class="mt-3 text-sm text-slate-600 sm:text-base">{{ $subtitle }}</p>
    @endif
</div>
