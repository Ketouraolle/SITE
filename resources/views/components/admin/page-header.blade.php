@props(['title', 'subtitle' => null])

{{-- En-tete de page admin : <x-admin.page-header title="Produits" subtitle="..."> boutons </x-admin.page-header> --}}
<div {{ $attributes->merge(['class' => 'mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between']) }}>
    <div class="min-w-0">
        <h1 class="truncate text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-0.5 truncate text-sm text-slate-500">{{ $subtitle }}</p>
        @endif
    </div>
    @if ($slot->isNotEmpty())
        <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $slot }}</div>
    @endif
</div>
