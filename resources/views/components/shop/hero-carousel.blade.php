@props(['slides', 'interval' => 5000])

@php
    $slides = collect($slides)->values();
@endphp

{{-- Carrousel en fondu : defilement automatique, pause au survol, fleches et puces. --}}
<div
    x-data="{
        current: 0,
        total: {{ $slides->count() }},
        timer: null,
        go(index) { this.current = (index + this.total) % this.total },
        start() {
            this.stop();
            if (this.total > 1) {
                this.timer = setInterval(() => this.go(this.current + 1), {{ (int) $interval }});
            }
        },
        stop() { clearInterval(this.timer); this.timer = null },
        select(index) { this.go(index); this.start() },
    }"
    x-init="start()"
    x-on:mouseenter="stop()"
    x-on:mouseleave="start()"
    x-on:livewire:navigating.window="stop()"
    {{ $attributes->merge(['class' => 'group/hero relative h-full w-full overflow-hidden bg-navy-800']) }}
>
    @foreach ($slides as $index => $slide)
        <a
            href="{{ route('catalog.show', $slide['slug']) }}"
            wire:navigate
            class="absolute inset-0"
            x-show="current === {{ $index }}"
            x-transition:enter="transition-opacity duration-1000 ease-out"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity duration-1000 ease-in"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @if ($index > 0) style="display: none" @endif
        >
            <img
                src="{{ $slide['image'] }}"
                alt="{{ $slide['name'] }}"
                loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                class="h-full w-full object-cover transition-transform duration-[6000ms] ease-out"
                x-bind:class="current === {{ $index }} ? 'scale-110' : 'scale-100'"
            />
            <span class="absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-navy-950/80 to-transparent"></span>
            <span class="absolute inset-x-0 bottom-0 p-5 pb-12 sm:p-7 sm:pb-14">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-cyan-accent/20 px-2.5 py-1 text-[11px] font-bold uppercase tracking-[0.18em] text-brand-100 ring-1 ring-inset ring-cyan-accent/40">
                    <x-icon name="heart" :size="12" filled />
                    Coup de coeur
                </span>
                <span class="mt-2 block text-xl font-extrabold tracking-tight text-white sm:text-2xl">{{ $slide['name'] }}</span>
            </span>
        </a>
    @endforeach

    @if ($slides->count() > 1)
        <button
            type="button"
            class="absolute left-3 top-1/2 z-20 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full bg-white/85 text-navy-900 shadow backdrop-blur transition hover:bg-white lg:opacity-0 lg:group-hover/hero:opacity-100"
            aria-label="Image precedente"
            x-on:click.prevent="select(current - 1)"
        >
            <x-icon name="chevron-left" :size="20" />
        </button>
        <button
            type="button"
            class="absolute right-3 top-1/2 z-20 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full bg-white/85 text-navy-900 shadow backdrop-blur transition hover:bg-white lg:opacity-0 lg:group-hover/hero:opacity-100"
            aria-label="Image suivante"
            x-on:click.prevent="select(current + 1)"
        >
            <x-icon name="chevron-right" :size="20" />
        </button>

        <div class="absolute inset-x-0 bottom-4 z-20 flex justify-center gap-1.5">
            @foreach ($slides as $index => $slide)
                <button
                    type="button"
                    class="h-1.5 rounded-full transition-all duration-500"
                    x-bind:class="current === {{ $index }} ? 'w-7 bg-cyan-accent' : 'w-1.5 bg-white/60 hover:bg-white'"
                    aria-label="Voir {{ $slide['name'] }}"
                    x-on:click.prevent="select({{ $index }})"
                ></button>
            @endforeach
        </div>
    @endif
</div>
