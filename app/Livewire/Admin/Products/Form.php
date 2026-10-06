<?php

namespace App\Livewire\Admin\Products;

use App\Enums\StockState;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Category;
use App\Models\Occasion;
use App\Models\Product;
use App\Support\Presenters;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Route;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Creation et modification d'une fiche produit, images comprises.
 */
#[Layout('layouts.admin')]
class Form extends Component
{
    use WithFileUploads;

    #[Locked]
    public ?Product $product = null;

    public string $name = '';

    public ?string $slug = '';

    public ?string $sku = '';

    public ?string $category_id = '';

    public ?string $occasion_id = '';

    public ?string $short_description = '';

    public ?string $description = '';

    public ?string $price = '';

    public ?string $compare_at_price = '';

    public ?string $stock = '0';

    public bool $is_available = true;

    public bool $is_published = false;

    public bool $is_featured = false;

    /**
     * Nouveaux fichiers a televerser.
     *
     * @var array<int, TemporaryUploadedFile>
     */
    public array $images = [];

    public function mount(?Product $product = null): void
    {
        if (! $product?->exists) {
            return;
        }

        $this->product = $product;

        $this->fill([
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku ?? '',
            'category_id' => (string) ($product->category_id ?? ''),
            'occasion_id' => (string) ($product->occasion_id ?? ''),
            'short_description' => $product->short_description ?? '',
            'description' => $product->description ?? '',
            'price' => (string) (float) $product->price,
            'compare_at_price' => $product->compare_at_price !== null ? (string) (float) $product->compare_at_price : '',
            'stock' => (string) $product->stock,
            'is_available' => $product->is_available,
            'is_published' => $product->is_published,
            'is_featured' => $product->is_featured,
        ]);
    }

    public function save(): void
    {
        $request = $this->productRequest();

        $data = collect($this->validate($request->rules(), $request->messages()))
            ->except('images')
            ->map(fn (mixed $value) => $value === '' ? null : $value);

        // Le slug n'est transmis que s'il a ete saisi : a la creation le modele
        // le derive du nom, et a la modification un champ vide laisse l'URL
        // existante intacte.
        if (blank($this->slug)) {
            $data->forget('slug');
        }

        $editing = $this->product !== null;

        if ($editing) {
            $this->product->update($data->all());
            $product = $this->product;
        } else {
            $product = Product::create($data->all());
        }

        $this->storeImages($product);

        session()->flash('success', $editing
            ? "Le produit \"{$product->name}\" a ete mis a jour."
            : "Le produit \"{$product->name}\" a ete cree.");

        $this->redirectRoute('admin.products.index', navigate: true);
    }

    public function destroy(): void
    {
        abort_unless($this->product !== null, 404);

        $this->product->delete();

        session()->flash('success', "Le produit \"{$this->product->name}\" a ete supprime.");

        $this->redirectRoute('admin.products.index', navigate: true);
    }

    /**
     * Supprime une image existante ; le fichier est efface par le modele.
     */
    public function deleteImage(int $imageId): void
    {
        abort_unless($this->product !== null, 404);

        $this->product->images()->findOrFail($imageId)->delete();

        $this->dispatch('toast', type: 'success', message: 'Image supprimee.');
    }

    public function setPrimaryImage(int $imageId): void
    {
        abort_unless($this->product !== null, 404);

        $image = $this->product->images()->findOrFail($imageId);

        $this->product->images()->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);

        $this->dispatch('toast', type: 'success', message: 'Image principale mise a jour.');
    }

    public function removeUpload(int $index): void
    {
        unset($this->images[$index]);

        $this->images = array_values($this->images);
    }

    public function clearUploads(): void
    {
        $this->reset('images');
        $this->resetValidation(['images', 'images.*']);
    }

    public function render(): View
    {
        $this->product?->load('images');

        return view('livewire.admin.products.form', [
            'categories' => Category::ordered()->get(['id', 'name']),
            'occasions' => Occasion::ordered()->get()->map(fn (Occasion $o) => Presenters::occasionTag($o))->all(),
            'previewState' => StockState::forProduct($this->is_available, (int) $this->stock),
        ])->title($this->product ? "Modifier {$this->product->name}" : 'Nouveau produit');
    }

    /**
     * Form request du back-office, avec le produit courant comme parametre de
     * route pour que les regles d'unicite l'ignorent.
     */
    private function productRequest(): ProductRequest
    {
        $route = new Route(['POST'], '', []);
        $route->parameters = ['product' => $this->product];

        $request = new ProductRequest;
        $request->setRouteResolver(fn () => $route);

        return $request;
    }

    private function storeImages(Product $product): void
    {
        if ($this->images === []) {
            return;
        }

        $position = (int) $product->images()->max('position');
        $hasPrimary = $product->images()->where('is_primary', true)->exists();

        foreach ($this->images as $file) {
            $product->images()->create([
                'path' => $file->store('products', 'public'),
                'alt' => $product->name,
                'position' => ++$position,
                'is_primary' => ! $hasPrimary,
            ]);

            $hasPrimary = true;
        }

        $this->reset('images');
    }
}
