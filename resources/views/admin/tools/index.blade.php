<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Admin Tools') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                    <h3 class="text-lg font-semibold mb-2">Permission Cache</h3>
                    <p class="text-sm text-gray-600 mb-4">Refresh cache permission agar perubahan role/permission langsung berlaku.</p>
                    <form action="{{ route('admin.tools.permissions.refresh') }}" method="POST" onsubmit="return confirm('Refresh permission cache sekarang?');">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-bass-red text-white rounded-lg hover:bg-bass-red-hover transition-colors">Refresh Permission Cache</button>
                    </form>
                </div>

                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                    <h3 class="text-lg font-semibold mb-2">Export Role Matrix</h3>
                    <p class="text-sm text-gray-600 mb-4">Export mapping role → permissions (JSON) untuk audit atau backup.</p>
                    <a href="{{ route('admin.tools.roles.export') }}" class="px-4 py-2 bg-gray-100 text-gray-800 rounded-lg hover:bg-gray-200 transition-colors">Download Role Matrix</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
