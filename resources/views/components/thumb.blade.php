@props(['src' => null, 'alt' => '', 'label' => null, 'ratio' => 'square', 'rounded' => 'rounded-xl'])

@php
    $ratioClass = ['square' => 'aspect-square', 'wide' => 'aspect-[4/3]', 'banner' => 'aspect-[16/9]'][$ratio] ?? 'aspect-square';
    $initials = collect(preg_split('/\s+/', (string) $label, -1, PREG_SPLIT_NO_EMPTY))
        ->take(2)
        ->map(fn (string $word) => mb_strtoupper(mb_substr($word, 0, 1)))
        ->implode('');
@endphp

<div {{ $attributes->merge(['class' => "relative w-full overflow-hidden bg-slate-100 {$ratioClass} {$rounded}"]) }}>
    @if ($src)
        <img src="{{ $src }}" alt="{{ $alt }}" loading="lazy" class="h-full w-full object-cover transition-transform duration-700 ease-out group-hover/thumb:scale-110" />
    @else
        <div class="grid h-full w-full place-items-center bg-gradient-to-br from-brand-50 to-slate-200 text-brand-300">
            @if ($initials !== '')
                <span class="text-xl font-extrabold tracking-tight">{{ $initials }}</span>
            @else
                <x-icon name="image" :size="28" />
            @endif
        </div>
    @endif
</div>
