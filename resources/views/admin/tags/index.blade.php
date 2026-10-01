<x-app-layout>
    <x-slot name="header"><div class="flex items-center justify-between gap-4"><div><h2 class="text-xl font-semibold text-gray-800">Tag Course</h2><p class="mt-1 text-sm text-gray-500">Karakteristik fleksibel untuk katalog course.</p></div><a href="{{ route('admin.tags.create') }}" class="shrink-0 rounded-lg bg-bass-red px-4 py-2 text-sm font-semibold text-white hover:bg-bass-red-hover">Tambah Tag</a></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"><div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Tag</th><th class="px-6 py-3 text-center text-xs font-semibold uppercase text-gray-500">Course</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Status</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Aksi</th></tr></thead>
            <tbody class="divide-y divide-gray-100">@forelse ($tags as $tag)<tr>
                <td class="px-6 py-4"><p class="font-medium text-gray-900">{{ $tag->name }}</p><p class="text-xs text-gray-500">{{ $tag->slug }}</p></td>
                <td class="px-6 py-4 text-center text-sm text-gray-600">{{ $tag->courses_count }}</td>
                <td class="px-6 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $tag->is_active ? 'bg-success-soft text-success' : 'bg-gray-100 text-gray-600' }}">{{ $tag->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                <td class="px-6 py-4"><div class="flex justify-end gap-2"><a href="{{ route('admin.tags.edit', $tag) }}" class="rounded-lg bg-warning-soft px-3 py-1.5 text-xs font-semibold text-warning">Edit</a><form method="POST" action="{{ route('admin.tags.destroy', $tag) }}" onsubmit="return confirm('Hapus tag ini? Course tidak akan ikut dihapus.')">@csrf @method('DELETE')<button class="rounded-lg bg-error-soft px-3 py-1.5 text-xs font-semibold text-error">Hapus</button></form></div></td>
            </tr>@empty<tr><td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500">Belum ada tag.</td></tr>@endforelse</tbody>
        </table></div></div><div class="mt-6">{{ $tags->links() }}</div>
    </div></div>
</x-app-layout>
