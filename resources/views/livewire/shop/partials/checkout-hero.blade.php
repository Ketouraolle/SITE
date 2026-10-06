{{--
    Bandeau du tunnel d'achat avec les etapes Panier -> Coordonnees -> Confirmation.
    Variables : $step (1|2|3), $title, $subtitle (optionnel), $eyebrow (optionnel).
--}}
@php
    $steps = [
        1 => ['label' => 'Panier', 'icon' => 'cart'],
        2 => ['label' => 'Coordonnees', 'icon' => 'user'],
        3 => ['label' => 'Confirmation', 'icon' => 'check-circle'],
    ];
@endphp

<section class="bg-hero text-white">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-12 lg:px-8">
        <div class="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-2xl">
                <nav class="flex items-center gap-1.5 text-xs font-semibold text-brand-100/80" aria-label="Fil d'Ariane">
                    <a href="{{ route('home') }}" wire:navigate class="transition hover:text-white">Accueil</a>
                    <x-icon name="chevron-right" :size="12" />
                    <span class="text-white">{{ $steps[$step]['label'] }}</span>
                </nav>
                <p class="mt-4 text-xs font-bold uppercase tracking-[0.2em] text-cyan-accent">{{ $eyebrow ?? 'Votre commande' }}</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $title }}</h1>
                @if (! empty($subtitle))
                    <p class="mt-3 text-sm text-brand-100 sm:text-base">{{ $subtitle }}</p>
                @endif
            </div>

            <ol class="flex items-center gap-2 sm:gap-3" aria-label="Etapes de la commande">
                @foreach ($steps as $number => $item)
                    @php
                        $done = $number < $step;
                        $current = $number === $step;
                    @endphp
                    <li class="flex items-center gap-2 sm:gap-3" @if ($current) aria-current="step" @endif>
                        <span class="flex items-center gap-2">
                            <span @class([
                                'grid h-9 w-9 shrink-0 place-items-center rounded-full text-sm font-extrabold transition',
                                'bg-cyan-accent text-navy-950 shadow-lg shadow-brand-500/30' => $done,
                                'bg-accent-500 text-white shadow-lg shadow-accent-700/40 ring-4 ring-white/15' => $current,
                                'bg-white/10 text-white/60 ring-1 ring-inset ring-white/25' => ! $done && ! $current,
                            ])>
                                @if ($done)
                                    <x-icon name="check" :size="16" />
                                @else
                                    {{ $number }}
                                @endif
                            </span>
                            <span @class([
                                'hidden text-sm font-bold sm:inline',
                                'text-white' => $done || $current,
                                'text-white/60' => ! $done && ! $current,
                            ])>{{ $item['label'] }}</span>
                        </span>
                        @if (! $loop->last)
                            <span @class([
                                'h-0.5 w-6 rounded-full sm:w-10',
                                'bg-cyan-accent' => $done,
                                'bg-white/25' => ! $done,
                            ])></span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </div>
    </div>
</section>
