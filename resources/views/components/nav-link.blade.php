@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-bass-red text-sm font-semibold leading-5 text-bass-red focus:outline-none focus:ring-2 focus:ring-bass-red focus:ring-offset-2 transition-colors duration-150'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-600 hover:text-bass-red hover:border-bass-red-soft focus:outline-none focus:ring-2 focus:ring-bass-red focus:ring-offset-2 transition-colors duration-150';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
