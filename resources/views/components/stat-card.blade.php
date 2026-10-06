@props(['label', 'value', 'hint' => null, 'icon' => 'chart', 'tone' => 'slate', 'href' => null])

@php
    $tones = [
        'slate' => 'bg-slate-100 text-slate-600',
        'brand' => 'bg-brand-50 text-brand-700',
        'amber' => 'bg-amber-50 text-amber-700',
        'rose' => 'bg-red-50 text-red-700',
        'emerald' => 'bg-emerald-50 text-emerald-700',
        'accent' => 'bg-accent-50 text-accent-600',
    ];
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" wire:navigate @endif
    {{ $attributes->merge(['class' => 'block rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:p-5'.($href ? ' transition hover:-translate-y-0.5 hover:shadow-md hover:ring-brand-200' : '')]) }}>
    <div class="flex items-start justify-between gap-3">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl {{ $tones[$tone] ?? $tones['slate'] }}">
            <x-icon :name="$icon" :size="18" />
        </span>
    </div>
    <p class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900">{{ $value }}</p>
    @if ($hint)
        <p class="mt-0.5 text-xs text-slate-500">{{ $hint }}</p>
    @endif
</{{ $tag }}>
