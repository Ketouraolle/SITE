@props(['min' => 1, 'max' => 99, 'size' => 'md', 'disabled' => false])

{{--
    Selecteur de quantite borne, lie a une propriete Livewire via wire:model :
    <x-quantity-stepper wire:model="quantity" :max="10" />
    Pour enregistrer a chaque clic, utiliser wire:model.live.
--}}
@php
    $dims = $size === 'sm'
        ? ['button' => 'h-8 w-8', 'input' => 'h-8 w-10 text-sm', 'icon' => 14]
        : ['button' => 'h-10 w-10', 'input' => 'h-10 w-12 text-sm', 'icon' => 16];
    $model = $attributes->wire('model');
@endphp

<div
    x-data="{
        value: $wire.entangle(@js($model->value()), @js($model->hasModifier('live'))),
        min: {{ (int) $min }},
        max: {{ (int) $max }},
        clamp(next) {
            const parsed = Number.parseInt(next, 10);
            this.value = Number.isNaN(parsed) ? this.min : Math.min(this.max, Math.max(this.min, parsed));
        },
    }"
    {{ $attributes->whereDoesntStartWith('wire:model')->merge(['class' => 'inline-flex items-center rounded-full bg-white ring-1 ring-inset ring-slate-300']) }}
>
    <button type="button" class="{{ $dims['button'] }} grid place-items-center rounded-l-full text-slate-600 transition hover:bg-slate-100 disabled:opacity-40"
        x-on:click="clamp(value - 1)" :disabled="{{ $disabled ? 'true' : 'false' }} || value <= min" aria-label="Diminuer la quantite">
        <x-icon name="minus" :size="$dims['icon']" />
    </button>
    <input type="text" inputmode="numeric" class="{{ $dims['input'] }} border-0 bg-transparent p-0 text-center font-semibold tabular-nums text-slate-900 focus:ring-0"
        :value="value" x-on:change="clamp($event.target.value); $event.target.value = value" @disabled($disabled) aria-label="Quantite" />
    <button type="button" class="{{ $dims['button'] }} grid place-items-center rounded-r-full text-slate-600 transition hover:bg-slate-100 disabled:opacity-40"
        x-on:click="clamp(value + 1)" :disabled="{{ $disabled ? 'true' : 'false' }} || value >= max" aria-label="Augmenter la quantite">
        <x-icon name="plus" :size="$dims['icon']" />
    </button>
</div>
