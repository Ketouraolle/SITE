@props(['invalid' => false])

<input {{ $attributes->merge([
    'type' => 'text',
    'class' => 'block w-full rounded-lg border-0 py-2.5 text-slate-900 shadow-sm ring-1 ring-inset placeholder:text-slate-400 focus:ring-2 focus:ring-inset sm:text-sm '
        .($invalid ? 'ring-red-400 focus:ring-red-500' : 'ring-slate-300 focus:ring-brand-600'),
]) }} @if ($invalid) aria-invalid="true" @endif />
