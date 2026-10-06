@props(['label' => null, 'description' => null])

{{-- Interrupteur lie a une propriete Livewire : <x-toggle wire:model="is_active" label="Active" /> --}}
<label class="flex cursor-pointer items-start justify-between gap-4">
    @if ($label || $description)
        <span class="min-w-0">
            @if ($label)
                <span class="block text-sm font-semibold text-slate-800">{{ $label }}</span>
            @endif
            @if ($description)
                <span class="mt-0.5 block text-xs text-slate-500">{{ $description }}</span>
            @endif
        </span>
    @endif
    <span class="relative inline-flex shrink-0">
        <input type="checkbox" class="peer sr-only" {{ $attributes }} />
        <span class="h-6 w-11 rounded-full bg-slate-300 transition peer-checked:bg-brand-600 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-600 peer-focus-visible:ring-offset-2"></span>
        <span class="pointer-events-none absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
    </span>
</label>
