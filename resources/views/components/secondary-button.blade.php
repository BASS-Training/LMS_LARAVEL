<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex min-h-[44px] items-center justify-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-navy focus:ring-offset-2 disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed transition-colors duration-150']) }}>
    {{ $slot }}
</button>
