@props(['color' => 'slate', 'dot' => false])

@php
    $colors = [
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'amber' => 'bg-amber-50 text-amber-800 ring-amber-600/20',
        'rose' => 'bg-red-50 text-red-700 ring-red-600/20',
        'slate' => 'bg-slate-100 text-slate-600 ring-slate-500/20',
        'blue' => 'bg-fuchsia-50 text-fuchsia-700 ring-fuchsia-600/20',
        'indigo' => 'bg-violet-50 text-violet-700 ring-violet-600/20',
        'brand' => 'bg-brand-50 text-brand-700 ring-brand-600/20',
        'accent' => 'bg-accent-50 text-accent-700 ring-accent-600/20',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset '.($colors[$color] ?? $colors['slate'])]) }}>
    @if ($dot)
        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
    @endif
    {{ $slot }}
</span>
