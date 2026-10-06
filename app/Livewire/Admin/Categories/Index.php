<?php

namespace App\Livewire\Admin\Categories;

use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Categories du catalogue : liste, creation et modification dans une
 * fenetre modale, suppression.
 */
#[Layout('layouts.admin')]
#[Title('Categories')]
class Index extends Component
{
    public bool $showForm = false;

    #[Locked]
    public ?int $editingId = null;

    public string $name = '';

    public ?string $slug = '';

    public ?string $description = '';

    public string|int|null $position = 0;

    public bool $is_active = true;

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $categoryId): void
    {
        $category = Category::findOrFail($categoryId);

        $this->resetValidation();
        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->slug = $category->slug;
        $this->description = $category->description ?? '';
        $this->position = $category->position;
        $this->is_active = $category->is_active;
        $this->showForm = true;
    }

    public function save(): void
    {
        $category = $this->editingId ? Category::findOrFail($this->editingId) : null;

        $data = $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'slug' => [
                'nullable', 'string', 'max:140', 'alpha_dash',
                Rule::unique('categories', 'slug')->ignore($category?->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'position' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
        ], [
            'name.required' => 'Le nom de la categorie est obligatoire.',
        ]);

        $data['position'] = blank($data['position'] ?? null) ? 0 : (int) $data['position'];
        $data['description'] = blank($data['description'] ?? null) ? null : $data['description'];

        if (blank($this->slug)) {
            unset($data['slug']);
        }

        if ($category) {
            $category->update($data);
            $message = "La categorie \"{$category->name}\" a ete mise a jour.";
        } else {
            $category = Category::create($data);
            $message = "La categorie \"{$category->name}\" a ete creee.";
        }

        $this->showForm = false;
        $this->resetForm();

        $this->dispatch('toast', type: 'success', message: $message);
    }

    /**
     * La contrainte est nullOnDelete : les produits sont conserves et se
     * retrouvent simplement sans categorie.
     */
    public function destroy(int $categoryId): void
    {
        $category = Category::findOrFail($categoryId);
        $name = $category->name;
        $category->delete();

        $this->dispatch('toast', type: 'success', message: "La categorie \"{$name}\" a ete supprimee.");
    }

    public function render(): View
    {
        return view('livewire.admin.categories.index', [
            'categories' => Category::ordered()
                ->withCount([
                    'products',
                    'products as published_products_count' => fn ($q) => $q->where('is_published', true),
                ])
                ->get(),
        ]);
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'slug', 'description', 'position', 'is_active']);
        $this->resetValidation();
    }
}
