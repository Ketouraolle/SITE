<div>
    <x-admin.page-header :title="$editing ? 'Modifier l\'offre' : 'Nouvelle offre'"
        :subtitle="$editing ? $offer->name : 'Une remise sur des produits et packs, le temps d\'une periode'">
        <x-btn :href="route('admin.offers.index')" variant="secondary" size="sm">
            <x-icon name="arrow-left" :size="16" />
            Retour
        </x-btn>
    </x-admin.page-header>

    <form wire:submit="save" class="grid gap-4 lg:grid-cols-3">
        {{-- Articles concernes --}}
        <div class="space-y-4 lg:col-span-2">
            <section class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:p-5">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Produits concernes</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Le prix remise est affiche a cote de chaque produit coche.</p>
                    </div>
                    <div class="relative w-full sm:w-64">
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"><x-icon name="search" :size="16" /></span>
                        <x-input type="search" wire:model.live.debounce.300ms="search" placeholder="Rechercher un produit…" class="pl-9" aria-label="Rechercher un produit" />
                    </div>
                </div>

                <x-input-error for="product_ids" class="mt-2" />

                @if ($productsByCategory->isNotEmpty())
                    <div class="mt-4 space-y-5">
                        @foreach ($productsByCategory as $category => $items)
                            <div wire:key="category-{{ md5($category) }}">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-xs font-bold uppercase tracking-[0.15em] text-brand-600">{{ $category }}</h3>
                                    <div class="flex gap-3 text-xs">
                                        <button type="button" wire:click="toggleCategory(@js($category), true)" class="font-semibold text-brand-700 hover:underline">Tout</button>
                                        <button type="button" wire:click="toggleCategory(@js($category), false)" class="text-slate-500 hover:underline">Aucun</button>
                                    </div>
                                </div>

                                <ul class="mt-2 grid gap-2 sm:grid-cols-2">
                                    @foreach ($items as $product)
                                        <li wire:key="product-{{ $product['id'] }}">
                                            <label class="group flex cursor-pointer items-center gap-3 rounded-xl p-2.5 ring-1 ring-slate-200 transition hover:bg-slate-50 has-[:checked]:bg-brand-50 has-[:checked]:ring-brand-300">
                                                <x-checkbox wire:model="product_ids" value="{{ $product['id'] }}" />
                                                <span class="min-w-0 flex-1">
                                                    <span class="block truncate text-sm font-semibold text-slate-900">{{ $product['name'] }}</span>
                                                    <span class="text-xs text-slate-500">
                                                        <span class="group-has-[:checked]:line-through">{{ $product['price_label'] }}</span>
                                                        <span class="ml-1 hidden font-bold text-accent-600 group-has-[:checked]:inline">{{ $discounted($product['price']) }}</span>
                                                        @unless ($product['is_published'])
                                                            <span class="ml-1">· brouillon</span>
                                                        @endunless
                                                    </span>
                                                </span>
                                            </label>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="mt-4 text-sm text-slate-500">Aucun produit ne correspond.</p>
                @endif
            </section>

            <section class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:p-5">
                <h2 class="text-sm font-bold text-slate-900">Packs concernes</h2>
                <p class="mt-0.5 text-xs text-slate-500">La remise s'applique au prix du pack.</p>

                @if ($packs->isNotEmpty())
                    <ul class="mt-4 grid gap-2 sm:grid-cols-2">
                        @foreach ($packs as $pack)
                            <li wire:key="pack-{{ $pack['id'] }}">
                                <label class="group flex cursor-pointer items-center gap-3 rounded-xl p-2.5 ring-1 ring-slate-200 transition hover:bg-slate-50 has-[:checked]:bg-brand-50 has-[:checked]:ring-brand-300">
                                    <x-checkbox wire:model="pack_ids" value="{{ $pack['id'] }}" />
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-semibold text-slate-900">{{ $pack['name'] }}</span>
                                        <span class="text-xs text-slate-500">
                                            <span class="group-has-[:checked]:line-through">{{ $pack['price_label'] }}</span>
                                            <span class="ml-1 hidden font-bold text-accent-600 group-has-[:checked]:inline">{{ $discounted($pack['price']) }}</span>
                                            @unless ($pack['is_published'])
                                                <span class="ml-1">· brouillon</span>
                                            @endunless
                                        </span>
                                    </span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-4 text-sm text-slate-500">
                        Aucun pack.
                        <a href="{{ route('admin.packs.create') }}" wire:navigate class="font-semibold text-brand-700 hover:underline">Creer un pack</a>
                    </p>
                @endif
            </section>
        </div>

        {{-- Parametres --}}
        <div class="space-y-4">
            <section class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:p-5">
                <h2 class="text-sm font-bold text-slate-900">Offre</h2>

                <div class="mt-4 space-y-4">
                    <div>
                        <x-label for="offer-name">Nom de l'offre <span class="text-red-500">*</span></x-label>
                        <x-input id="offer-name" wire:model="name" placeholder="Ex. Love Week, Magie de Noel" :invalid="$errors->has('name')" class="mt-1.5" required />
                        <x-input-error for="name" />
                    </div>

                    <div>
                        <x-label for="offer-percent">Remise <span class="text-red-500">*</span> <span class="font-normal text-slate-400">(en %)</span></x-label>
                        <x-input id="offer-percent" type="number" min="1" max="90" wire:model.live.debounce.400ms="discount_percent" :invalid="$errors->has('discount_percent')" class="mt-1.5" required />
                        <x-input-error for="discount_percent" />
                    </div>

                    <div>
                        <x-label for="offer-description">Description <span class="font-normal text-slate-400">(optionnel)</span></x-label>
                        <x-textarea id="offer-description" wire:model="description" :rows="2" :invalid="$errors->has('description')" class="mt-1.5" />
                        <x-input-error for="description" />
                    </div>
                </div>
            </section>

            <section class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:p-5">
                <h2 class="text-sm font-bold text-slate-900">Quand ?</h2>

                <div class="mt-4 space-y-4">
                    <div>
                        <x-label for="offer-occasion">Periode</x-label>
                        <x-select id="offer-occasion" wire:model.live="occasion_id" :invalid="$errors->has('occasion_id')" class="mt-1.5">
                            <option value="">Dates personnalisees</option>
                            @foreach ($occasions as $occasion)
                                <option value="{{ $occasion['id'] }}">{{ $occasion['name'] }} ({{ $statusLabels[$occasion['status']] ?? $occasion['status'] }})</option>
                            @endforeach
                        </x-select>
                        <x-input-error for="occasion_id" />
                        @if ($selectedOccasion)
                            <p class="mt-1.5 text-xs text-slate-500">
                                {{ $selectedOccasion['period_label'] }}.
                                <a href="{{ route('admin.occasions.index') }}" wire:navigate class="font-semibold text-brand-700 hover:underline">Modifier les dates</a>
                            </p>
                        @endif
                    </div>

                    @unless ($selectedOccasion)
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <x-label for="offer-starts">Debut <span class="font-normal text-slate-400">(optionnel)</span></x-label>
                                <x-input id="offer-starts" type="date" wire:model="starts_on" :invalid="$errors->has('starts_on')" class="mt-1.5" />
                                <x-input-error for="starts_on" />
                            </div>
                            <div>
                                <x-label for="offer-ends">Fin <span class="font-normal text-slate-400">(incluse)</span></x-label>
                                <x-input id="offer-ends" type="date" wire:model="ends_on" :invalid="$errors->has('ends_on')" class="mt-1.5" />
                                <x-input-error for="ends_on" />
                            </div>
                        </div>
                    @endunless

                    <x-toggle wire:model="is_active" label="Offre active" description="Desactivee, la remise n'est plus appliquee." />
                </div>
            </section>

            <div class="rounded-2xl bg-hero p-4 text-white shadow-lg shadow-brand-900/20 sm:p-5">
                <p class="flex flex-wrap items-center gap-2 text-sm text-brand-100">
                    <span class="rounded-full bg-accent-500 px-2.5 py-1 text-xs font-extrabold text-white">-{{ $percent }} %</span>
                    sur <strong class="text-white" x-text="$wire.product_ids.length + $wire.pack_ids.length">{{ count($product_ids) + count($pack_ids) }}</strong> article(s)
                </p>
                <x-btn type="submit" variant="accent" class="mt-4" block wire:loading.attr="disabled" wire:target="save">
                    <x-icon name="save" :size="16" />
                    <span wire:loading.remove wire:target="save">{{ $editing ? 'Enregistrer' : "Creer l'offre" }}</span>
                    <span wire:loading wire:target="save">Enregistrement…</span>
                </x-btn>
            </div>
        </div>
    </form>
</div>
