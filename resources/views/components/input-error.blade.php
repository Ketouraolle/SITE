@props(['for' => null, 'message' => null])

@php
    $text = $message ?? ($for ? $errors->first($for) : null);
@endphp

@if ($text)
    <p {{ $attributes->merge(['class' => 'mt-1.5 text-sm text-red-600']) }}>{{ $text }}</p>
@endif
