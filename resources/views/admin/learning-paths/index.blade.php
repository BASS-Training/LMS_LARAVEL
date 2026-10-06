@extends('layouts.app')

@section('title', 'Manajemen Skema')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div><h1 class="text-2xl font-bold text-gray-900">Manajemen Skema</h1><p class="mt-1 text-sm text-gray-500">Kelola urutan rekomendasi course tanpa mengubah enrollment atau pembayaran.</p></div>
        <a href="{{ route('admin.learning-paths.create') }}" class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-bass-red px-5 font-semibold text-white hover:bg-bass-red-hover">Buat Skema</a>
    </div>
    @if (session('success'))<div class="mt-5 rounded-lg border border-success/30 bg-success-soft px-4 py-3 text-sm text-success">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="mt-5 rounded-lg border border-error/30 bg-error-soft px-4 py-3 text-sm text-error">{{ $errors->first() }}</div>@endif
    <div class="mt-6 flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2"><h2 class="font-semibold text-gray-900">Ketersediaan Skema</h2><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $featureSettings->learning_paths_enabled ? 'bg-success-soft text-success' : 'bg-gray-100 text-gray-600' }}">{{ $featureSettings->learning_paths_enabled ? 'Aktif' : 'Disembunyikan' }}</span></div>
            <p class="mt-1 text-sm text-gray-500">Saat disembunyikan, seluruh menu dan halaman Skema tidak terlihat oleh pengguna. Pengelolaan admin tetap terbuka.</p>
        </div>
        <form method="POST" action="{{ route('admin.learning-paths.availability.update') }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="enabled" value="{{ $featureSettings->learning_paths_enabled ? 0 : 1 }}">
            <button class="inline-flex min-h-[42px] items-center justify-center rounded-lg px-5 text-sm font-semibold {{ $featureSettings->learning_paths_enabled ? 'border border-error/30 text-error hover:bg-error-soft' : 'bg-success text-white hover:opacity-90' }}" onclick="return confirm('{{ $featureSettings->learning_paths_enabled ? 'Sembunyikan Skema dari pengguna?' : 'Tampilkan kembali Skema kepada pengguna?' }}')">{{ $featureSettings->learning_paths_enabled ? 'Sembunyikan Skema' : 'Aktifkan Skema' }}</button>
        </form>
    </div>
    <form method="GET" class="mt-6 flex gap-2"><input type="search" name="search" value="{{ $search }}" placeholder="Cari judul atau slug..." class="w-full max-w-md rounded-lg border-gray-300 text-sm focus:border-bass-red focus:ring-bass-red"><button class="rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cari</button></form>
    <div class="mt-5 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"><div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500"><tr><th class="px-5 py-3">Skema</th><th class="px-5 py-3">Isi</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Aksi</th></tr></thead>
        <tbody class="divide-y divide-gray-100">@forelse ($learningPaths as $learningPath)<tr><td class="px-5 py-4"><p class="font-semibold text-gray-900">{{ $learningPath->title }}</p><p class="text-xs text-gray-500">{{ $learningPath->slug }}</p></td><td class="px-5 py-4 text-gray-600">{{ $learningPath->courses_count }} course</td><td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $learningPath->is_active ? 'bg-success-soft text-success' : 'bg-gray-100 text-gray-600' }}">{{ $learningPath->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td class="px-5 py-4"><div class="flex justify-end gap-2"><a href="{{ route('admin.learning-paths.edit', $learningPath) }}" class="rounded-lg border border-gray-300 px-3 py-2 font-semibold text-gray-700 hover:bg-gray-50">Edit</a><form method="POST" action="{{ route('admin.learning-paths.destroy', $learningPath) }}" onsubmit="return confirm('Hapus Skema ini?')">@csrf @method('DELETE')<button class="rounded-lg border border-error/30 px-3 py-2 font-semibold text-error hover:bg-error-soft">Hapus</button></form></div></td></tr>@empty<tr><td colspan="4" class="px-5 py-12 text-center text-gray-500">Belum ada Skema.</td></tr>@endforelse</tbody>
    </table></div></div>
    <div class="mt-6">{{ $learningPaths->links() }}</div>
</div>
@endsection
