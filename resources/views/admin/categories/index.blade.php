<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div><h2 class="text-xl font-semibold text-gray-800">Kategori Course</h2><p class="mt-1 text-sm text-gray-500">Navigasi terstruktur untuk katalog course.</p></div>
            <a href="{{ route('admin.categories.create') }}" class="shrink-0 rounded-lg bg-bass-red px-4 py-2 text-sm font-semibold text-white hover:bg-bass-red-hover">Tambah Kategori</a>
        </div>
    </x-slot>
    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50"><tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Kategori</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Induk</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold uppercase text-gray-500">Urutan</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold uppercase text-gray-500">Course</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Aksi</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-100">
                        @forelse ($categories as $category)
                            <tr>
                                <td class="px-6 py-4"><p class="font-medium text-gray-900">{{ $category->name }}</p><p class="text-xs text-gray-500">{{ $category->slug }}</p></td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $category->parent?->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-center text-sm text-gray-600">{{ $category->sort_order }}</td>
                                <td class="px-6 py-4 text-center text-sm text-gray-600">{{ $category->courses_count }}</td>
                                <td class="px-6 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $category->is_active ? 'bg-success-soft text-success' : 'bg-gray-100 text-gray-600' }}">{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                                <td class="px-6 py-4"><div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.categories.edit', $category) }}" class="rounded-lg bg-warning-soft px-3 py-1.5 text-xs font-semibold text-warning">Edit</a>
                                    <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Hapus kategori ini? Course tidak akan ikut dihapus.')">@csrf @method('DELETE')<button class="rounded-lg bg-error-soft px-3 py-1.5 text-xs font-semibold text-error">Hapus</button></form>
                                </div></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">Belum ada kategori.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-6">{{ $categories->links() }}</div>
        </div>
    </div>
</x-app-layout>
