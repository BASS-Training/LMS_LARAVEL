@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'min-h-[44px] border-gray-300 focus:border-bass-red focus:ring-bass-red rounded-lg shadow-sm disabled:bg-gray-100 disabled:text-gray-500 disabled:cursor-not-allowed']) }}>
