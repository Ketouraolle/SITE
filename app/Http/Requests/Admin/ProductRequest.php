<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'name' => ['required', 'string', 'min:2', 'max:160'],
            'slug' => [
                'nullable', 'string', 'max:180', 'alpha_dash',
                Rule::unique('products', 'slug')->ignore($productId),
            ],
            'sku' => [
                'nullable', 'string', 'max:60',
                Rule::unique('products', 'sku')->ignore($productId),
            ],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'occasion_id' => ['nullable', 'integer', 'exists:occasions,id'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0', 'max:999999999', 'gt:price'],
            'stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'is_available' => ['boolean'],
            'is_published' => ['boolean'],
            'is_featured' => ['boolean'],
            'images' => ['nullable', 'array', 'max:6'],
            'images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du produit est obligatoire.',
            'price.required' => 'Le prix est obligatoire.',
            'compare_at_price.gt' => 'Le prix barre doit etre superieur au prix de vente.',
            'stock.required' => 'Indiquez le stock disponible (0 si rupture).',
            'images.*.image' => 'Chaque fichier doit etre une image (jpg, png ou webp).',
            'images.*.max' => 'Chaque image doit peser moins de 4 Mo.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_available' => $this->boolean('is_available'),
            'is_published' => $this->boolean('is_published'),
            'is_featured' => $this->boolean('is_featured'),
        ]);
    }

    /**
     * Champs du modele, sans les fichiers.
     *
     * Le slug n'est transmis que s'il a ete saisi : a la creation le modele le
     * derive du nom, et a la modification un champ vide laisse l'URL
     * existante intacte.
     *
     * @return array<string, mixed>
     */
    public function productData(): array
    {
        $data = collect($this->validated())->except('images');

        if (! $this->filled('slug')) {
            $data->forget('slug');
        }

        return $data->all();
    }
}
