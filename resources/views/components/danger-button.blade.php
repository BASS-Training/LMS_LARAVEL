<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex min-h-[44px] items-center justify-center gap-2 px-4 py-2 bg-error border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-error-dark active:bg-error-dark focus:outline-none focus:ring-2 focus:ring-error focus:ring-offset-2 disabled:bg-gray-300 disabled:text-gray-500 disabled:cursor-not-allowed transition-colors duration-150']) }}>
    {{ $slot }}
</button>
