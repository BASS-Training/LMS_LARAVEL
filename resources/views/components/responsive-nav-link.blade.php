@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block min-h-[44px] w-full ps-3 pe-4 py-2 border-l-4 border-bass-red text-start text-base font-semibold text-bass-red bg-bass-red-soft focus:outline-none focus:ring-2 focus:ring-inset focus:ring-bass-red transition-colors duration-150'
            : 'block min-h-[44px] w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-gray-600 hover:text-bass-red hover:bg-bass-red-soft hover:border-bass-red-soft focus:outline-none focus:ring-2 focus:ring-inset focus:ring-bass-red transition-colors duration-150';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
