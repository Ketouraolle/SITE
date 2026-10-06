<?php

namespace App\Livewire\Admin\Packs;

use App\Http\Requests\Admin\PackRequest;
use App\Models\Occasion;
use App\Models\Pack;
use App\Models\Product;
use App\Support\Money;
use App\Support\Presenters;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Creation et modification d'un pack : informations, image, composition et
 * prix (fixe ou somme des produits).
 */
#[Layout('layouts.admin')]
class Form extends Component
{
    use WithFileUploads;

    #[Locked]
    public ?Pack $pack = null;

    public string $name = '';

    public ?string $slug = '';

    public ?string $description = '';

    public ?string $occasion_id = '';

    public ?string $price = '';

    public string|int|null $position = 0;

    public bool $is_published = false;

    public bool $is_featured = false;

    public ?TemporaryUploadedFile $image = null;

    public bool $remove_image = false;

    /**
     * Composition du pack.
     *
     * @var array<int, array{product_id: int, quantity: int}>
     */
    public array $items = [];

    public string $search = '';

    public function mount(?Pack $pack = null): void
    {
        if (! $pack?->exists) {
            return;
        }

        $pack->load('items');

        $this->pack = $pack;

        $this->fill([
            'name' => $pack->name,
            'slug' => $pack->slug,
            'description' => $pack->description ?? '',
            'occasion_id' => (string) ($pack->occasion_id ?? ''),
            'price' => $pack->price !== null ? (string) (float) $pack->price : '',
            'position' => $pack->position,
            'is_published' => $pack->is_published,
            'is_featured' => $pack->is_featured,
            'items' => $pack->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
            ])->values()->all(),
        ]);
    }

    public function addProduct(int $productId): void
    {
        if (in_array($productId, array_column($this->items, 'product_id'), true)
            || ! $this->selectableProducts->has($productId)) {
            return;
        }

        $this->items[] = ['product_id' => $productId, 'quantity' => 1];
        $this->search = '';
        $this->resetValidation('items');
    }

    public function removeItem(int $productId): void
    {
        $this->items = array_values(array_filter(
            $this->items,
            fn (array $item) => (int) $item['product_id'] !== $productId,
        ));
    }

    public function updatedImage(): void
    {
        $this->remove_image = false;
    }

    public function clearImage(): void
    {
        $this->reset('image');
        $this->resetValidation('image');
    }

    public function save(): void
    {
        $request = $this->packRequest();

        $validated = $this->validate($request->rules(), $request->messages());

        $data = collect($validated)
            ->except(['image', 'remove_image', 'items'])
            ->map(fn (mixed $value) => $value === '' ? null : $value);

        if (blank($this->slug)) {
            $data->forget('slug');
        }

        $data->put('position', (int) ($validated['position'] ?? 0));

        $items = array_map(fn (array $item) => [
            'product_id' => (int) $item['product_id'],
            'quantity' => (int) $item['quantity'],
        ], $validated['items']);

        $editing = $this->pack !== null;

        $pack = DB::transaction(function () use ($data, $items) {
            $pack = $this->pack ?? new Pack;
            $pack->fill($data->all())->save();

            $this->syncImage($pack);

            $pack->items()->delete();
            $pack->items()->createMany($items);

            return $pack;
        });

        session()->flash('success', $editing
            ? "Le pack \"{$pack->name}\" a ete mis a jour."
            : "Le pack \"{$pack->name}\" a ete cree.");

        $this->redirectRoute('admin.packs.index', navigate: true);
    }

    public function destroy(): void
    {
        abort_unless($this->pack !== null, 404);

        $this->pack->delete();

        session()->flash('success', "Le pack \"{$this->pack->name}\" a ete supprime.");

        $this->redirectRoute('admin.packs.index', navigate: true);
    }

    /**
     * Produits proposes dans le constructeur de pack, indexes par id.
     *
     * @return Collection<int, array{id: int, name: string, price: float, price_label: string, stock: int, stock_label: string, stock_color: string}>
     */
    #[Computed]
    public function selectableProducts(): Collection
    {
        return Product::published()
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'stock', 'is_available'])
            ->mapWithKeys(fn (Product $product) => [$product->id => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => (float) $product->price,
                'price_label' => Money::format($product->price),
                'stock' => $product->stock,
                'stock_label' => $product->stockState()->label(),
                'stock_color' => $product->stockState()->color(),
            ]]);
    }

    public function render(): View
    {
        $products = $this->selectableProducts;
        $selectedIds = array_map('intval', array_column($this->items, 'product_id'));
        $term = mb_strtolower(trim($this->search));

        $candidates = $products
            ->reject(fn (array $product) => in_array($product['id'], $selectedIds, true))
            ->filter(fn (array $product) => $term === '' || str_contains(mb_strtolower($product['name']), $term))
            ->take(50);

        $componentsTotal = collect($this->items)->sum(
            fn (array $item) => ($products[(int) $item['product_id']]['price'] ?? 0) * max(0, (int) $item['quantity'])
        );

        $effectivePrice = is_numeric($this->price) ? (float) $this->price : $componentsTotal;

        // Le produit le plus contraint fixe le nombre de packs servables.
        $servable = $this->items === [] ? 0 : (int) collect($this->items)->map(function (array $item) use ($products) {
            $stock = $products[(int) $item['product_id']]['stock'] ?? 0;

            return $stock <= 0 ? 0 : intdiv($stock, max(1, (int) $item['quantity']));
        })->min();

        return view('livewire.admin.packs.form', [
            'products' => $products,
            'candidates' => $candidates,
            'occasions' => Occasion::ordered()->get()->map(fn (Occasion $o) => Presenters::occasionTag($o))->all(),
            'componentsTotal' => $componentsTotal,
            'effectivePrice' => $effectivePrice,
            'savings' => max(0, $componentsTotal - $effectivePrice),
            'servable' => $servable,
        ])->title($this->pack ? "Modifier {$this->pack->name}" : 'Nouveau pack');
    }

    /**
     * Form request du back-office, avec le pack courant comme parametre de
     * route pour que la regle d'unicite du slug l'ignore.
     */
    private function packRequest(): PackRequest
    {
        $route = new Route(['POST'], '', []);
        $route->parameters = ['pack' => $this->pack];

        $request = new PackRequest;
        $request->setRouteResolver(fn () => $route);

        return $request;
    }

    private function syncImage(Pack $pack): void
    {
        if ($this->remove_image && $pack->image_path) {
            Storage::disk('public')->delete($pack->image_path);
            $pack->update(['image_path' => null]);
        }

        if ($this->image) {
            if ($pack->image_path) {
                Storage::disk('public')->delete($pack->image_path);
            }

            $pack->update(['image_path' => $this->image->store('packs', 'public')]);
        }
    }
}
