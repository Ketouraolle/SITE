{{-- Actions d'une ligne de categorie (tableau et cartes). --}}
<div class="flex items-center justify-end gap-1">
    <a href="{{ route('admin.products.index', ['category' => $category->id]) }}" wire:navigate title="Voir les produits" aria-label="Voir les produits"
        class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 transition hover:bg-brand-50 hover:text-brand-700">
        <x-icon name="box" :size="16" />
    </a>
    <button type="button" wire:click="edit({{ $category->id }})" title="Modifier" aria-label="Modifier"
        class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 transition hover:bg-brand-50 hover:text-brand-700">
        <x-icon name="edit" :size="16" />
    </button>
    <button type="button" wire:click="destroy({{ $category->id }})" title="Supprimer" aria-label="Supprimer"
        wire:confirm="Supprimer « {{ $category->name }} » ? {{ $category->products_count ? $category->products_count.' produit(s) se retrouveront sans categorie. Ils ne seront pas supprimes.' : 'Cette categorie ne contient aucun produit.' }}"
        class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 transition hover:bg-red-50 hover:text-red-600">
        <x-icon name="trash" :size="16" />
    </button>
</div>
