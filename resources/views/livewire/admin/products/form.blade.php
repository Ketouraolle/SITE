<div>
    <x-admin.page-header :title="$product ? 'Modifier le produit' : 'Nouveau produit'" :subtitle="$product ? $product->name : 'Creez une fiche produit'">
        <x-btn variant="secondary" size="sm" :href="route('admin.products.index')">
            <x-icon name="arrow-left" :size="16" />
            Retour
        </x-btn>
    </x-admin.page-header>

    <form wire:submit="save" class="grid gap-4 lg:grid-cols-3 lg:gap-6">
        {{-- Colonne principale --}}
        <div class="space-y-4 lg:col-span-2 lg:space-y-6">
            <section class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:p-6">
                <h2 class="flex items-center gap-2 text-base font-extrabold tracking-tight text-slate-900">
                    <span class="grid h-8 w-8 place-items-center rounded-lg bg-brand-50 text-brand-700"><x-icon name="box" :size="16" /></span>
                    Informations
                </h2>

                <div class="mt-5 space-y-4">
                    <div>
                        <x-label for="name">Nom du produit <span class="text-red-500">*</span></x-label>
                        <x-input id="name" wire:model="name" class="mt-1.5" :invalid="$errors->has('name')" required />
                        <x-input-error for="name" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-label for="sku">Reference (SKU) <span class="font-normal text-slate-400">optionnel</span></x-label>
                            <x-input id="sku" wire:model="sku" class="mt-1.5" :invalid="$errors->has('sku')" />
                            <x-input-error for="sku" />
                        </div>

                        <div>
                            <x-label for="category_id" value="Categorie" />
                            <x-select id="category_id" wire:model="category_id" class="mt-1.5" :invalid="$errors->has('category_id')">
                                <option value="">Sans categorie</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </x-select>
                            <x-input-error for="category_id" />
                        </div>
                    </div>

                    <x-admin.occasion-select wire:model.live="occasion_id" :occasions="$occasions" :selected="$occasion_id" />

                    <div>
                        <x-label for="short_description">Resume <span class="font-normal text-slate-400">affiche sur les cartes produit</span></x-label>
                        <x-input id="short_description" wire:model="short_description" maxlength="255" class="mt-1.5" :invalid="$errors->has('short_description')" />
                        <x-input-error for="short_description" />
                    </div>

                    <div>
                        <x-label for="description">Description complete <span class="font-normal text-slate-400">optionnel</span></x-label>
                        <x-textarea id="description" wire:model="description" :rows="6" class="mt-1.5" :invalid="$errors->has('description')" />
                        <x-input-error for="description" />
                    </div>

                    <div>
                        <x-label for="slug">Slug (URL) <span class="font-normal text-slate-400">genere automatiquement si vide</span></x-label>
                        <x-input id="slug" wire:model="slug" class="mt-1.5" :invalid="$errors->has('slug')" />
                        <x-input-error for="slug" />
                    </div>
                </div>
            </section>

            {{-- Prix et stock --}}
            <section class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:p-6">
                <h2 class="flex items-center gap-2 text-base font-extrabold tracking-tight text-slate-900">
                    <span class="grid h-8 w-8 place-items-center rounded-lg bg-brand-50 text-brand-700"><x-icon name="tag" :size="16" /></span>
                    Prix et stock
                </h2>

                <div class="mt-5 grid gap-4 sm:grid-cols-3">
                    <div>
                        <x-label for="price">Prix de vente <span class="text-red-500">*</span></x-label>
                        <x-input id="price" type="number" min="0" step="0.01" wire:model.live.blur="price" class="mt-1.5" :invalid="$errors->has('price')" required />
                        <x-input-error for="price" />
                        @if (is_numeric($price))
                            <p class="mt-1 text-xs text-slate-500">{{ \App\Support\Money::format($price) }}</p>
                        @endif
                    </div>

                    <div>
                        <x-label for="compare_at_price">Prix barre <span class="font-normal text-slate-400">optionnel</span></x-label>
                        <x-input id="compare_at_price" type="number" min="0" step="0.01" wire:model="compare_at_price" class="mt-1.5" :invalid="$errors->has('compare_at_price')" />
                        <x-input-error for="compare_at_price" />
                    </div>

                    <div>
                        <x-label for="stock">Stock <span class="text-red-500">*</span></x-label>
                        <x-input id="stock" type="number" min="0" step="1" wire:model.live.blur="stock" class="mt-1.5" :invalid="$errors->has('stock')" required />
                        <x-input-error for="stock" />
                    </div>
                </div>
            </section>

            {{-- Images --}}
            <section class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:p-6">
                <h2 class="flex items-center gap-2 text-base font-extrabold tracking-tight text-slate-900">
                    <span class="grid h-8 w-8 place-items-center rounded-lg bg-brand-50 text-brand-700"><x-icon name="image" :size="16" /></span>
                    Images
                </h2>
                <p class="mt-1 text-xs text-slate-500">JPG, PNG ou WebP, 4 Mo maximum par fichier. La premiere image sert de visuel principal.</p>

                @if ($product && $product->images->isNotEmpty())
                    <ul class="mt-4 grid grid-cols-3 gap-3 sm:grid-cols-4">
                        @foreach ($product->images as $image)
                            <li wire:key="image-{{ $image->id }}" class="relative">
                                <x-thumb :src="$image->url" :alt="$image->alt ?: $product->name" class="{{ $image->is_primary ? 'ring-2 ring-brand-600 ring-offset-2' : '' }}" />

                                @if ($image->is_primary)
                                    <span class="absolute left-1.5 top-1.5 rounded-md bg-brand-700 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">Principale</span>
                                @endif

                                <div class="absolute inset-x-1.5 bottom-1.5 flex items-center justify-between gap-1">
                                    @if (! $image->is_primary)
                                        <button type="button" wire:click="setPrimaryImage({{ $image->id }})"
                                            class="rounded-md bg-white/95 px-1.5 py-1 text-[10px] font-bold text-slate-700 shadow transition hover:bg-white hover:text-brand-700">
                                            Principale
                                        </button>
                                    @else
                                        <span></span>
                                    @endif

                                    <button type="button" wire:click="deleteImage({{ $image->id }})"
                                        wire:confirm="Supprimer cette image ? Le fichier sera definitivement supprime."
                                        class="grid h-7 w-7 place-items-center rounded-md bg-white/95 text-slate-500 shadow transition hover:bg-red-50 hover:text-red-600" aria-label="Supprimer l'image">
                                        <x-icon name="trash" :size="14" />
                                    </button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <div class="mt-4">
                    <label class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center transition hover:border-brand-400 hover:bg-brand-50/40">
                        <span class="grid h-11 w-11 place-items-center rounded-xl bg-gradient-to-br from-brand-600 to-cyan-accent text-white">
                            <x-icon name="image" :size="20" />
                        </span>
                        <span class="text-sm font-semibold text-slate-700">Ajouter des images</span>
                        <span class="text-xs text-slate-500">Cliquez pour choisir un ou plusieurs fichiers</span>
                        <input type="file" multiple accept="image/jpeg,image/png,image/webp" wire:model="images" class="sr-only" />
                    </label>

                    <div wire:loading wire:target="images" class="mt-2 text-xs font-semibold text-brand-700">Televersement en cours…</div>

                    <x-input-error for="images" />
                    @foreach ($errors->get('images.*') as $messages)
                        <x-input-error :message="$messages[0]" />
                    @endforeach

                    @if ($images !== [])
                        <ul class="mt-3 grid grid-cols-3 gap-3 sm:grid-cols-4">
                            @foreach ($images as $index => $upload)
                                <li wire:key="upload-{{ $index }}" class="relative">
                                    @php
                                        $previewUrl = method_exists($upload, 'isPreviewable') && $upload->isPreviewable() ? $upload->temporaryUrl() : null;
                                    @endphp
                                    <x-thumb :src="$previewUrl" :alt="$upload->getClientOriginalName()" :label="$upload->getClientOriginalName()" />
                                    <button type="button" wire:click="removeUpload({{ $index }})" aria-label="Retirer"
                                        class="absolute right-1.5 top-1.5 grid h-6 w-6 place-items-center rounded-full bg-white/95 text-slate-500 shadow hover:text-red-600">
                                        <x-icon name="close" :size="12" />
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                        <button type="button" wire:click="clearUploads" class="mt-2 text-xs font-semibold text-slate-500 hover:text-slate-800">Retirer la selection</button>
                    @endif
                </div>
            </section>
        </div>

        {{-- Colonne laterale --}}
        <div>
            <section class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:p-6 lg:sticky lg:top-24">
                <h2 class="text-base font-extrabold tracking-tight text-slate-900">Publication</h2>

                <div class="mt-5 space-y-4">
                    <x-toggle wire:model.live="is_published" label="Publie sur le site" description="Un produit non publie reste invisible pour les clients." />
                    <x-toggle wire:model.live="is_available" label="Disponible a la vente" description="Desactivez pour retirer temporairement de la vente sans toucher au stock." />
                    <x-toggle wire:model="is_featured" label="Mettre en avant" description="Apparait dans la selection de la page d'accueil." />
                </div>

                <div class="mt-5 rounded-xl bg-slate-50 p-3 ring-1 ring-slate-100">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Vu par le client</p>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        <x-badge :color="$previewState->color()" dot>{{ $previewState->label() }}</x-badge>
                        <x-badge :color="$is_published ? 'brand' : 'slate'">{{ $is_published ? 'Visible' : 'Masque' }}</x-badge>
                    </div>
                </div>

                <div class="mt-5 space-y-2">
                    <x-btn type="submit" variant="accent" block wire:loading.attr="disabled" wire:target="save, images">
                        <x-icon name="save" :size="16" />
                        <span wire:loading.remove wire:target="save">Enregistrer</span>
                        <span wire:loading wire:target="save">Enregistrement…</span>
                    </x-btn>

                    <a href="{{ route('admin.products.index') }}" wire:navigate class="block py-1 text-center text-sm font-semibold text-slate-500 hover:text-slate-800">Annuler</a>
                </div>

                @if ($product)
                    <div class="mt-5 border-t border-slate-200 pt-4">
                        <x-btn variant="danger-soft" size="sm" wire:click="destroy"
                            wire:confirm="Supprimer « {{ $product->name }} » ? La fiche sera retiree du catalogue. Les commandes passees restent intactes.">
                            <x-icon name="trash" :size="15" />
                            Supprimer ce produit
                        </x-btn>
                    </div>
                @endif
            </section>
        </div>
    </form>
</div>
