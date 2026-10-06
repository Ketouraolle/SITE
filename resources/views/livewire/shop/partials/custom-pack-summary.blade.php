{{--
    Options et totaux du pack personnalise (recapitulatif desktop et panneau mobile).
    Variables : $context ("desktop"|"mobile") + celles de la vue custom-pack.
--}}
<div class="space-y-4">
    <div>
        <x-label for="pack-label-{{ $context }}">Nom du pack <span class="font-normal text-slate-400">(optionnel)</span></x-label>
        <x-input id="pack-label-{{ $context }}" wire:model.blur="label" placeholder="Ex. Pack de la semaine" maxlength="60" class="mt-1.5" :invalid="$errors->has('label')" />
        <x-input-error for="label" />
    </div>

    <div class="flex items-center justify-between gap-3">
        <span class="text-sm font-semibold text-slate-700">Nombre de packs</span>
        <x-quantity-stepper wire:model.live="quantity" :max="$maxPacks" size="sm" wire:key="pack-quantity-{{ $context }}-{{ $maxPacks }}" />
    </div>

    <dl class="space-y-2 text-sm">
        <div class="flex justify-between text-slate-600">
            <dt>Prix d'un pack</dt>
            <dd class="font-semibold text-slate-900">{{ $unitTotalLabel }}</dd>
        </div>
        <div class="flex items-baseline justify-between border-t border-dashed border-slate-200 pt-3">
            <dt class="text-base font-bold text-slate-900">Total</dt>
            <dd class="text-2xl font-extrabold tracking-tight text-brand-700">{{ $grandTotalLabel }}</dd>
        </div>
    </dl>

    @if (! $enoughProducts)
        <p class="flex items-start gap-2 rounded-xl bg-amber-50 p-3 text-xs font-medium text-amber-800">
            <x-icon name="alert" :size="14" class="mt-px shrink-0" />
            Ajoutez encore {{ $limits['min_products'] - $count }} produit(s) pour valider votre pack.
        </p>
    @elseif ($tooManyProducts)
        <p class="flex items-start gap-2 rounded-xl bg-red-50 p-3 text-xs font-medium text-red-700">
            <x-icon name="alert" :size="14" class="mt-px shrink-0" />
            Retirez {{ $count - $limits['max_products'] }} produit(s) : le maximum est de {{ $limits['max_products'] }}.
        </p>
    @endif

    <x-btn variant="accent" block size="lg" wire:click="addToCart" wire:loading.attr="disabled" wire:target="addToCart" :disabled="! $enoughProducts || $tooManyProducts">
        <x-icon name="cart" :size="17" />
        <span wire:loading.remove wire:target="addToCart">Ajouter au panier</span>
        <span wire:loading wire:target="addToCart">Ajout…</span>
    </x-btn>
</div>
