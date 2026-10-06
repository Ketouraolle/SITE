@props(['occasions' => [], 'selected' => null, 'noun' => 'produit', 'for' => 'occasion_id'])

{{--
    Choix d'une periode (Noel, Saint-Valentin...) pour un produit ou un pack :
    <x-admin.occasion-select wire:model.live="occasion_id" :occasions="$occasions" :selected="$occasion_id" noun="pack" />
    $occasions : liste issue de Presenters::occasionTag().
--}}
@php
    $statusLabels = [
        'running' => 'en cours',
        'upcoming' => 'a venir',
        'ended' => 'terminee',
        'inactive' => 'desactivee',
    ];
    $current = collect($occasions)->firstWhere('id', (int) $selected);
@endphp

<div>
    <x-label :for="$for" value="Periode" />
    <p class="text-xs text-slate-500">Noel, Saint-Valentin, Aid...</p>
    <x-select :id="$for" class="mt-1.5" :invalid="$errors->has($for)" {{ $attributes }}>
        <option value="">Toute l'annee</option>
        @foreach ($occasions as $occasion)
            <option value="{{ $occasion['id'] }}">{{ $occasion['name'] }} ({{ $statusLabels[$occasion['status']] ?? $occasion['status'] }})</option>
        @endforeach
    </x-select>
    <x-input-error :for="$for" />

    @if ($current)
        <p class="mt-1.5 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">
            Ce {{ $noun }} ne sera visible et commandable que pendant « {{ $current['name'] }} » :
            {{ mb_strtolower($current['period_label']) }}.
        </p>
    @elseif (count($occasions) === 0)
        <p class="mt-1.5 text-xs text-slate-500">
            Aucune periode.
            <a href="{{ route('admin.occasions.index') }}" wire:navigate class="font-semibold text-brand-700 hover:underline">Creer une periode</a>
        </p>
    @endif
</div>
