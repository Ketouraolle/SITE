@props(['label', 'color' => 'slate'])

<x-badge :color="$color" dot {{ $attributes }}>{{ $label }}</x-badge>
