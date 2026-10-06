<?php

namespace App\Livewire\Admin\Occasions;

use App\Models\Occasion;
use App\Support\Presenters;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Periodes commerciales : Noel, Saint-Valentin, Aid...
 */
#[Layout('layouts.admin')]
#[Title('Periodes')]
class Index extends Component
{
    public bool $showForm = false;

    #[Locked]
    public ?int $editingId = null;

    public string $name = '';

    public string $slug = '';

    public string $description = '';

    public string $starts_on = '';

    public string $ends_on = '';

    public int|string|null $position = 0;

    public bool $is_active = true;

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $occasionId): void
    {
        $occasion = Occasion::findOrFail($occasionId);

        $this->resetForm();
        $this->editingId = $occasion->id;
        $this->name = $occasion->name;
        $this->slug = (string) $occasion->slug;
        $this->description = (string) $occasion->description;
        $this->starts_on = (string) $occasion->starts_on?->toDateString();
        $this->ends_on = (string) $occasion->ends_on?->toDateString();
        $this->position = $occasion->position;
        $this->is_active = (bool) $occasion->is_active;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $this->resetErrorBag();

        $occasion = $this->editingId ? Occasion::findOrFail($this->editingId) : null;

        // Les champs vides du formulaire valent null, comme dans une requete HTTP.
        $input = array_map(
            fn (mixed $value) => is_string($value) && trim($value) === '' ? null : $value,
            $this->only(['name', 'slug', 'description', 'starts_on', 'ends_on', 'position', 'is_active']),
        );

        $data = Validator::make($input, [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'slug' => [
                'nullable', 'string', 'max:140', 'alpha_dash',
                Rule::unique('occasions', 'slug')->ignore($occasion?->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'position' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
        ], [
            'name.required' => 'Le nom de la periode est obligatoire.',
            'ends_on.after_or_equal' => 'La date de fin doit suivre la date de debut.',
        ])->validate();

        $attributes = [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'starts_on' => $data['starts_on'] ?? null,
            'ends_on' => $data['ends_on'] ?? null,
            'position' => (int) ($data['position'] ?? 0),
            'is_active' => (bool) $data['is_active'],
        ];

        if (filled($data['slug'] ?? null)) {
            $attributes['slug'] = $data['slug'];
        }

        if ($occasion) {
            $occasion->update($attributes);
            $message = "La periode \"{$occasion->name}\" a ete mise a jour.";
        } else {
            $occasion = Occasion::create($attributes);
            $message = "La periode \"{$occasion->name}\" a ete creee.";
        }

        $this->closeForm();
        $this->dispatch('toast', type: 'success', message: $message);
    }

    /**
     * nullOnDelete : produits, packs et offres sont conserves et
     * redeviennent simplement « toute l'annee ».
     */
    public function delete(int $occasionId): void
    {
        $occasion = Occasion::findOrFail($occasionId);
        $name = $occasion->name;
        $occasion->delete();

        $this->dispatch('toast', type: 'success', message: "La periode \"{$name}\" a ete supprimee.");
    }

    public function render(): View
    {
        return view('livewire.admin.occasions.index', [
            'occasions' => Occasion::ordered()
                ->withCount(['products', 'packs', 'offers'])
                ->get()
                ->map(fn (Occasion $occasion) => Presenters::occasionAdmin($occasion)),
            'statuses' => [
                'running' => ['label' => 'En cours', 'color' => 'emerald'],
                'upcoming' => ['label' => 'A venir', 'color' => 'amber'],
                'ended' => ['label' => 'Terminee', 'color' => 'slate'],
                'inactive' => ['label' => 'Desactivee', 'color' => 'slate'],
            ],
        ]);
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'slug', 'description', 'starts_on', 'ends_on', 'position', 'is_active']);
        $this->resetValidation();
    }
}
