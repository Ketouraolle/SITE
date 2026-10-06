@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'external' => false,
    'type' => 'button',
    'block' => false,
    'disabled' => false,
])

@php
    $variants = [
        'primary' => 'bg-brand-700 text-white shadow-sm shadow-brand-900/20 hover:bg-brand-800 focus-visible:outline-brand-700',
        'accent' => 'bg-accent-500 text-white shadow-sm shadow-accent-700/25 hover:bg-accent-600 focus-visible:outline-accent-500',
        'secondary' => 'bg-white text-slate-700 ring-1 ring-inset ring-slate-300 shadow-sm hover:bg-slate-50 focus-visible:outline-slate-400',
        'outline-light' => 'text-white ring-1 ring-inset ring-white/40 hover:bg-white/10 focus-visible:outline-white',
        'ghost' => 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus-visible:outline-slate-400',
        'danger' => 'bg-red-600 text-white shadow-sm hover:bg-red-700 focus-visible:outline-red-600',
        'danger-soft' => 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-200 hover:bg-red-100 focus-visible:outline-red-500',
        'whatsapp' => 'bg-[#25D366] text-white shadow-sm hover:bg-[#1fb757] focus-visible:outline-[#25D366]',
        'dark' => 'bg-navy-900 text-white shadow-sm hover:bg-navy-800 focus-visible:outline-navy-900',
    ];

    $sizes = [
        'xs' => 'px-2.5 py-1.5 text-xs gap-1',
        'sm' => 'px-3.5 py-2 text-sm gap-1.5',
        'md' => 'px-5 py-2.5 text-sm gap-2',
        'lg' => 'px-6 py-3.5 text-base gap-2',
    ];

    $classes = implode(' ', [
        'inline-flex items-center justify-center rounded-full font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-2 disabled:pointer-events-none disabled:opacity-50',
        $variants[$variant] ?? $variants['primary'],
        $sizes[$size] ?? $sizes['md'],
        $block ? 'w-full' : '',
        $disabled ? 'pointer-events-none opacity-50' : '',
    ]);
@endphp

@if ($href)
    <a
        href="{{ $href }}"
        @if ($external) target="_blank" rel="noopener" @else wire:navigate @endif
        @if ($disabled) aria-disabled="true" @endif
        {{ $attributes->merge(['class' => $classes]) }}
    >{{ $slot }}</a>
@else
    <button type="{{ $type }}" @disabled($disabled) {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
