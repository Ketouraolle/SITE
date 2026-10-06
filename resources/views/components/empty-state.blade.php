@props(['icon' => 'box', 'title', 'description' => null])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center']) }}>
    <div class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-brand-50 text-brand-600">
        <x-icon :name="$icon" :size="24" />
    </div>
    <h3 class="mt-4 text-base font-bold text-slate-900">{{ $title }}</h3>
    @if ($description)
        <p class="mx-auto mt-1.5 max-w-sm text-sm text-slate-600">{{ $description }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-5 flex flex-wrap justify-center gap-2">{{ $slot }}</div>
    @endif
</div>
