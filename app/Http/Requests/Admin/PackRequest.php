<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PackRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $packId = $this->route('pack')?->id;

        return [
            'name' => ['required', 'string', 'min:2', 'max:160'],
            'slug' => [
                'nullable', 'string', 'max:180', 'alpha_dash',
                Rule::unique('packs', 'slug')->ignore($packId),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'occasion_id' => ['nullable', 'integer', 'exists:occasions,id'],
            // Laisser vide pour facturer la somme des produits du pack.
            'price' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'position' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_published' => ['boolean'],
            'is_featured' => ['boolean'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_image' => ['boolean'],
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du pack est obligatoire.',
            'items.required' => 'Un pack doit contenir au moins un produit.',
            'items.min' => 'Un pack doit contenir au moins un produit.',
            'items.*.product_id.distinct' => 'Chaque produit ne peut apparaitre qu\'une fois dans le pack.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_published' => $this->boolean('is_published'),
            'is_featured' => $this->boolean('is_featured'),
            'remove_image' => $this->boolean('remove_image'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function packData(): array
    {
        $data = collect($this->validated())->except(['image', 'remove_image', 'items']);

        if (! $this->filled('slug')) {
            $data->forget('slug');
        }

        return $data->put('position', $this->integer('position'))->all();
    }

    /**
     * @return array<int, array{product_id:int, quantity:int}>
     */
    public function packItems(): array
    {
        return array_map(fn (array $item) => [
            'product_id' => (int) $item['product_id'],
            'quantity' => (int) $item['quantity'],
        ], $this->validated()['items']);
    }
}
