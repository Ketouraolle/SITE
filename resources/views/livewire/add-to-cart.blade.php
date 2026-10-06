<div>
    <div class="flex items-stretch gap-2 {{ $block ? 'w-full' : '' }}">
        @if ($withQuantity && $orderable)
            <x-quantity-stepper wire:model="quantity" :max="max(1, $maxQuantity)" :size="$size === 'sm' ? 'sm' : 'md'" />
        @endif

        <x-btn
            :variant="$orderable ? 'accent' : 'secondary'"
            :size="$size"
            :block="$block || ! $withQuantity"
            :disabled="! $orderable"
            class="flex-1"
            wire:click="add"
            wire:loading.attr="disabled"
        >
            <x-icon name="cart" :size="16" />
            @if ($orderable)
                <span wire:loading.remove wire:target="add">{{ $label }}</span>
                <span wire:loading wire:target="add">Ajout…</span>
            @else
                <span>Indisponible</span>
            @endif
        </x-btn>
    </div>

    <x-input-error for="quantity" />
</div>
