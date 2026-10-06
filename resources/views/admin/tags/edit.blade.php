<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">Edit Tag</h2></x-slot>
    <div class="py-8"><div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('admin.tags.update', $tag) }}" class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">@csrf @method('PUT')
            @include('admin.tags.partials.form')
            <div class="mt-8 flex justify-end gap-3"><a href="{{ route('admin.tags.index') }}" class="rounded-lg px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100">Batal</a><x-primary-button>Perbarui</x-primary-button></div>
        </form>
    </div></div>
</x-app-layout>
