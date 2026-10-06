<div>
    <x-admin.page-header :title="$pack ? 'Modifier le pack' : 'Nouveau pack'" :subtitle="$pack ? $pack->name : 'Regroupez des produits a prix unique'">
        <x-btn variant="secondary" size="sm" :href="route('admin.packs.index')">
            <x-icon name="arrow-left" :size="16" />
            Retour
        </x-btn>
    </x-admin.page-header>

    <form wire:submit="save" class="grid gap-4 lg:grid-cols-3 lg:gap-6">
        <div class="space-y-4 lg:col-span-2 lg:space-y-6">
            {{-- Informations --}}
            <section class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:p-6">
                <h2 class="flex items-center gap-2 text-base font-extrabold tracking-tight text-slate-900">
                    <span class="grid h-8 w-8 place-items-center rounded-lg bg-brand-50 text-brand-700"><x-icon name="layers" :size="16" /></span>
                    Informations
                </h2>

                <div class="mt-5 space-y-4">
                    <div>
                        <x-label for="name">Nom du pack <span class="text-red-500">*</span></x-label>
                        <x-input id="name" wire:model="name" class="mt-1.5" :invalid="$errors->has('name')" required />
                        <x-input-error for="name" />
                    </div>

                    <div>
                        <x-label for="description">Description <span class="font-normal text-slate-400">optionnel</span></x-label>
                        <x-textarea id="description" wire:model="description" :rows="4" class="mt-1.5" :invalid="$errors->has('description')" />
                        <x-input-error for="description" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-label for="slug">Slug (URL) <span class="font-normal text-slate-400">genere si vide</span></x-label>
                            <x-input id="slug" wire:model="slug" class="mt-1.5" :invalid="$errors->has('slug')" />
                            <x-input-error for="slug" />
                        </div>
                        <div>
                            <x-label for="position">Position <span class="font-normal text-slate-400">ordre d'affichage</span></x-label>
                            <x-input id="position" type="number" min="0" wire:model="position" class="mt-1.5" :invalid="$errors->has('position')" />
                            <x-input-error for="position" />
                        </div>
                    </div>

                    <x-admin.occasion-select wire:model.live="occasion_id" :occasions="$occasions" :selected="$occasion_id" noun="pack" />

                    <div>
                        <x-label value="Image du pack" />
                        <div class="mt-1.5 flex items-start gap-4">
                            @php
                                $currentImage = $image?->isPreviewable() ? $image->temporaryUrl() : (! $remove_image ? $pack?->image_url : null);
                            @endphp
                            @if ($currentImage)
                                <div class="w-28 shrink-0">
                                    <x-thumb :src="$currentImage" :alt="$name" ratio="wide" />
                                    @if ($image)
                                        <button type="button" wire:click="clearImage" class="mt-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800">Annuler</button>
                                    @else
                                        <button type="button" wire:click="$set('remove_image', true)" class="mt-1.5 text-xs font-semibold text-red-600 hover:text-red-800">Retirer l'image</button>
                                    @endif
                                </div>
                            @endif

                            <div class="min-w-0 flex-1">
                                <input type="file" accept="image/jpeg,image/png,image/webp" wire:model="image"
                                    class="block w-full cursor-pointer rounded-lg text-sm text-slate-600 file:mr-3 file:cursor-pointer file:rounded-full file:border-0 file:bg-brand-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100" />
                                <div wire:loading wire:target="image" class="mt-1.5 text-xs font-semibold text-brand-700">Televersement en cours…</div>
                                <x-input-error for="image" />
                                @if ($remove_image)
                                    <p class="mt-1.5 text-xs text-red-600">
                                        L'image actuelle sera supprimee a l'enregistrement.
                                        <button type="button" wire:click="$set('remove_image', false)" class="font-semibold underline">Annuler</button>
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Composition --}}
            <section class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:p-6">
                <h2 class="flex items-center gap-2 text-base font-extrabold tracking-tight text-slate-900">
                    <span class="grid h-8 w-8 place-items-center rounded-lg bg-brand-50 text-brand-700"><x-icon name="boxes" :size="16" /></span>
                    Composition du pack
                </h2>
                <p class="mt-1 text-xs text-slate-500">Seuls les produits publies peuvent entrer dans un pack.</p>

                <x-input-error for="items" class="mt-2" />
                @foreach ($errors->get('items.*') as $messages)
                    <x-input-error :message="$messages[0]" />
                @endforeach

                @if ($items !== [])
                    <ul class="mt-4 divide-y divide-slate-100 rounded-xl ring-1 ring-slate-200">
                        @foreach ($items as $index => $item)
                            @php($product = $products[(int) $item['product_id']] ?? null)
                            <li wire:key="item-{{ $item['product_id'] }}" class="flex flex-wrap items-center gap-3 px-3 py-2.5 sm:px-4">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $product['name'] ?? 'Produit supprime' }}</p>
                                    <p class="text-xs text-slate-500">{{ $product['price_label'] ?? '—' }} · stock {{ $product['stock'] ?? 0 }}</p>
                                </div>

                                <x-quantity-stepper wire:model.live="items.{{ $index }}.quantity" :min="1" :max="100" size="sm" />

                                <button type="button" wire:click="removeItem({{ (int) $item['product_id'] }})" aria-label="Retirer du pack"
                                    class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 transition hover:bg-red-50 hover:text-red-600">
                                    <x-icon name="close" :size="16" />
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-4 rounded-xl bg-slate-50 p-4 text-sm text-slate-500">Aucun produit dans ce pack pour l'instant.</p>
                @endif

                <div class="mt-5 border-t border-slate-200 pt-4">
                    <x-label for="pack-search" value="Ajouter un produit" />
                    <div class="relative mt-1.5">
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                            <x-icon name="search" :size="16" />
                        </span>
                        <x-input id="pack-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Rechercher un produit publie…" class="pl-9" />
                    </div>

                    <ul class="mt-2 max-h-64 divide-y divide-slate-100 overflow-y-auto rounded-xl ring-1 ring-slate-200">
                        @forelse ($candidates as $candidate)
                            <li wire:key="candidate-{{ $candidate['id'] }}" class="flex items-center gap-3 px-3 py-2">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm text-slate-800">{{ $candidate['name'] }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ $candidate['price_label'] }} ·
                                        <span class="{{ $candidate['stock'] <= 0 ? 'text-red-600' : '' }}">stock {{ $candidate['stock'] }}</span>
                                    </p>
                                </div>
                                <button type="button" wire:click="addProduct({{ $candidate['id'] }})" aria-label="Ajouter {{ $candidate['name'] }}"
                                    class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-brand-700 text-white transition hover:bg-brand-800">
                                    <x-icon name="plus" :size="14" />
                                </button>
                            </li>
                        @empty
                            <li class="px-3 py-4 text-sm text-slate-500">Aucun produit disponible a ajouter.</li>
                        @endforelse
                    </ul>
                </div>
            </section>
        </div>

        {{-- Colonne laterale --}}
        <div>
            <section class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:p-6 lg:sticky lg:top-24">
                <h2 class="text-base font-extrabold tracking-tight text-slate-900">Prix et publication</h2>

                <div class="mt-5 space-y-4">
                    <div>
                        <x-label for="price">Prix du pack <span class="font-normal text-slate-400">vide = somme des produits</span></x-label>
                        <x-input id="price" type="number" min="0" step="0.01" wire:model.live.blur="price" placeholder="Laisser vide" class="mt-1.5" :invalid="$errors->has('price')" />
                        <x-input-error for="price" />
                    </div>

                    <dl class="space-y-1.5 rounded-xl bg-slate-50 p-3 text-sm ring-1 ring-slate-100">
                        <div class="flex justify-between text-slate-600">
                            <dt>Valeur des produits</dt>
                            <dd class="font-semibold tabular-nums text-slate-900">{{ \App\Support\Money::format($componentsTotal) }}</dd>
                        </div>
                        <div class="flex justify-between text-slate-600">
                            <dt>Prix facture</dt>
                            <dd class="font-extrabold tabular-nums text-brand-700">{{ \App\Support\Money::format($effectivePrice) }}</dd>
                        </div>
                        @if ($savings > 0)
                            <div class="flex justify-between text-emerald-700">
                                <dt>Economie client</dt>
                                <dd class="font-semibold tabular-nums">{{ \App\Support\Money::format($savings) }}</dd>
                            </div>
                        @endif
                        <div class="flex items-center justify-between border-t border-slate-200 pt-1.5 text-slate-600">
                            <dt>Packs servables</dt>
                            <dd><x-badge :color="$servable > 0 ? 'emerald' : 'rose'">{{ $servable }}</x-badge></dd>
                        </div>
                    </dl>

                    <x-toggle wire:model="is_published" label="Publie sur le site" description="Un pack non publie n'apparait pas dans la boutique." />
                    <x-toggle wire:model="is_featured" label="Mettre en avant" description="Affiche sur la page d'accueil." />
                </div>

                <div class="mt-5 space-y-2">
                    <x-btn type="submit" variant="accent" block :disabled="$items === []" wire:loading.attr="disabled" wire:target="save, image">
                        <x-icon name="save" :size="16" />
                        <span wire:loading.remove wire:target="save">Enregistrer</span>
                        <span wire:loading wire:target="save">Enregistrement…</span>
                    </x-btn>
                    <a href="{{ route('admin.packs.index') }}" wire:navigate class="block py-1 text-center text-sm font-semibold text-slate-500 hover:text-slate-800">Annuler</a>
                </div>

                @if ($pack)
                    <div class="mt-5 border-t border-slate-200 pt-4">
                        <x-btn variant="danger-soft" size="sm" wire:click="destroy"
                            wire:confirm="Supprimer « {{ $pack->name }} » ? Le pack sera retire du site. Les commandes passees restent intactes.">
                            <x-icon name="trash" :size="15" />
                            Supprimer ce pack
                        </x-btn>
                    </div>
                @endif
            </section>
        </div>
    </form>
</div>
