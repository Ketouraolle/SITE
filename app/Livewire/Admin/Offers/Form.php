<?php

namespace App\Livewire\Admin\Offers;

use App\Models\Occasion;
use App\Models\Offer;
use App\Models\Pack;
use App\Models\Product;
use App\Support\Money;
use App\Support\Presenters;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Creation et modification d'une offre promotionnelle.
 */
#[Layout('layouts.admin')]
class Form extends Component
{
    #[Locked]
    public ?Offer $offer = null;

    public string $name = '';

    public string $description = '';

    public int|string|null $discount_percent = 10;

    public int|string|null $occasion_id = '';

    public string $starts_on = '';

    public string $ends_on = '';

    public bool $is_active = true;

    /** @var array<int, int|string> */
    public array $product_ids = [];

    /** @var array<int, int|string> */
    public array $pack_ids = [];

    public string $search = '';

    public function mount(?Offer $offer = null): void
    {
        if ($offer?->exists) {
            $offer->load(['products:id,name', 'packs:id,name']);

            $this->offer = $offer;
            $this->name = $offer->name;
            $this->description = (string) $offer->description;
            $this->discount_percent = $offer->discount_percent;
            $this->occasion_id = $offer->occasion_id ?? '';
            $this->starts_on = (string) $offer->starts_on?->toDateString();
            $this->ends_on = (string) $offer->ends_on?->toDateString();
            $this->is_active = (bool) $offer->is_active;
            $this->product_ids = $offer->products->pluck('id')->all();
            $this->pack_ids = $offer->packs->pluck('id')->all();

            return;
        }

        $this->offer = null;
        $this->occasion_id = request()->integer('periode') ?: '';
    }

    /**
     * Coche ou decoche tous les produits (visibles) d'une categorie.
     */
    public function toggleCategory(string $category, bool $select): void
    {
        $ids = $this->filteredProducts()
            ->filter(fn (array $product) => ($product['category'] ?? 'Sans categorie') === $category)
            ->pluck('id')
            ->all();

        $current = array_map('intval', $this->product_ids);

        $this->product_ids = $select
            ? array_values(array_unique([...$current, ...$ids]))
            : array_values(array_diff($current, $ids));
    }

    public function save(): void
    {
        $this->resetErrorBag();

        $input = array_map(
            fn (mixed $value) => is_string($value) && trim($value) === '' ? null : $value,
            $this->only(['name', 'description', 'discount_percent', 'occasion_id', 'starts_on', 'ends_on', 'is_active', 'product_ids', 'pack_ids']),
        );

        $data = Validator::make($input, [
            'name' => ['required', 'string', 'min:2', 'max:160'],
            'description' => ['nullable', 'string', 'max:1000'],
            'discount_percent' => ['required', 'integer', 'min:1', 'max:90'],
            'occasion_id' => ['nullable', 'integer', 'exists:occasions,id'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'is_active' => ['boolean'],
            'product_ids' => ['nullable', 'array', 'max:500'],
            'product_ids.*' => ['integer', 'distinct', 'exists:products,id'],
            'pack_ids' => ['nullable', 'array', 'max:200'],
            'pack_ids.*' => ['integer', 'distinct', 'exists:packs,id'],
        ], [
            'name.required' => "Le nom de l'offre est obligatoire.",
            'discount_percent.required' => 'Indiquez le pourcentage de remise.',
            'discount_percent.max' => 'La remise ne peut pas depasser :max %.',
            'ends_on.after_or_equal' => 'La date de fin doit suivre la date de debut.',
        ])->validate();

        $productIds = array_map('intval', $data['product_ids'] ?? []);
        $packIds = array_map('intval', $data['pack_ids'] ?? []);

        if ($productIds === [] && $packIds === []) {
            throw ValidationException::withMessages([
                'product_ids' => "Selectionnez au moins un produit ou un pack concerne par l'offre.",
            ]);
        }

        $attributes = [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'discount_percent' => (int) $data['discount_percent'],
            'occasion_id' => $data['occasion_id'] ?? null,
            // Rattachee a une periode, l'offre en suit les dates.
            'starts_on' => empty($data['occasion_id']) ? ($data['starts_on'] ?? null) : null,
            'ends_on' => empty($data['occasion_id']) ? ($data['ends_on'] ?? null) : null,
            'is_active' => (bool) $data['is_active'],
        ];

        $editing = $this->offer !== null;

        $offer = DB::transaction(function () use ($attributes, $productIds, $packIds) {
            $offer = $this->offer ?? new Offer;
            $offer->fill($attributes)->save();
            $offer->syncTargets($productIds, $packIds);

            return $offer;
        });

        session()->flash('success', $editing
            ? "L'offre \"{$offer->name}\" a ete mise a jour."
            : "L'offre \"{$offer->name}\" a ete creee.");

        $this->redirectRoute('admin.offers.index', navigate: true);
    }

    /**
     * Produits proposes, avec leur categorie, pour la selection.
     *
     * @return Collection<int, array{id: int, name: string, price: float, price_label: string, category: string|null, is_published: bool}>
     */
    #[Computed]
    public function products(): Collection
    {
        return Product::with('category:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'category_id', 'is_published'])
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => (float) $product->price,
                'price_label' => Money::format($product->price),
                'category' => $product->category?->name,
                'is_published' => (bool) $product->is_published,
            ]);
    }

    public function render(): View
    {
        $occasions = Occasion::ordered()->get()
            ->map(fn (Occasion $occasion) => Presenters::occasionTag($occasion));

        $percent = min(90, max(0, (int) $this->discount_percent));

        return view('livewire.admin.offers.form', [
            'editing' => $this->offer !== null,
            'occasions' => $occasions,
            'selectedOccasion' => $occasions->firstWhere('id', (int) $this->occasion_id),
            'productsByCategory' => $this->filteredProducts()->groupBy(fn (array $product) => $product['category'] ?? 'Sans categorie'),
            'packs' => Pack::with('items.product')
                ->ordered()
                ->get()
                ->map(fn (Pack $pack) => [
                    'id' => $pack->id,
                    'name' => $pack->name,
                    'price' => $pack->basePrice(),
                    'price_label' => Money::format($pack->basePrice()),
                    'is_published' => (bool) $pack->is_published,
                ]),
            'percent' => $percent,
            'discounted' => fn (float $price) => Money::format(round($price * (100 - $percent) / 100)),
            'statusLabels' => [
                'running' => 'en cours',
                'upcoming' => 'a venir',
                'ended' => 'terminee',
                'inactive' => 'desactivee',
            ],
        ])->title($this->offer ? 'Modifier '.$this->offer->name : 'Nouvelle offre');
    }

    /**
     * @return Collection<int, array{id: int, name: string, price: float, price_label: string, category: string|null, is_published: bool}>
     */
    private function filteredProducts(): Collection
    {
        $term = mb_strtolower(trim($this->search));

        if ($term === '') {
            return $this->products;
        }

        return $this->products
            ->filter(fn (array $product) => str_contains(mb_strtolower($product['name'].' '.($product['category'] ?? '')), $term))
            ->values();
    }
}
