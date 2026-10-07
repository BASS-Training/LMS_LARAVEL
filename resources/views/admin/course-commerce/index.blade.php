@extends('layouts.app')

@section('title', 'Penjualan Course')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div>
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-bass-red">Commerce</p>
        <h1 class="mt-1 text-2xl font-bold text-gray-900 sm:text-3xl">Penjualan Course</h1>
        <p class="mt-1 text-sm text-gray-500">Kelola katalog dan profil penjualan khusus course Regular. Course AVPN tidak dapat dijual.</p>
    </div>

    @if (session('success'))<div class="mt-6 rounded-xl border border-success/30 bg-success-soft px-4 py-3 text-sm text-success">{{ session('success') }}</div>@endif

    <section class="mt-7 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <form method="GET" class="grid gap-3 border-b border-gray-100 p-5 sm:grid-cols-[1fr_220px_auto]">
            <input name="q" value="{{ $search }}" class="rounded-lg border-gray-300 text-sm focus:border-navy focus:ring-navy" placeholder="Cari judul course Regular">
            <select name="status" class="rounded-lg border-gray-300 text-sm focus:border-navy focus:ring-navy">
                <option value="">Semua status</option>
                <option value="private" @selected($status === 'private')>Belum dijual</option>
                <option value="draft" @selected($status === 'draft')>Draft</option>
                <option value="published" @selected($status === 'published')>Published</option>
                <option value="hidden" @selected($status === 'hidden')>Hidden</option>
            </select>
            <button class="rounded-lg bg-navy px-5 py-2.5 text-sm font-semibold text-white">Filter</button>
        </form>

        <div class="divide-y divide-gray-100">
            @forelse ($courses as $course)
                @php($salesStatus = $course->visibility === 'catalog' ? ($course->salesProfile?->sales_status ?? 'draft') : 'private')
                <div class="flex flex-col gap-4 px-5 py-4 md:flex-row md:items-center">
                    <div class="min-w-0 flex-1">
                        <h2 class="font-semibold text-gray-900">{{ $course->title }}</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ $course->price_label }} &middot; {{ $course->salesProfile?->headline ?: 'Headline belum diisi' }}</p>
                    </div>
                    <span class="w-fit rounded-full px-3 py-1 text-xs font-semibold {{ $salesStatus === 'published' ? 'bg-success-soft text-success' : ($salesStatus === 'private' ? 'bg-gray-100 text-gray-600' : 'bg-warning-soft text-warning') }}">{{ ['private' => 'Belum dijual', 'draft' => 'Draft', 'published' => 'Published', 'hidden' => 'Hidden'][$salesStatus] }}</span>
                    <a href="{{ route('admin.course-commerce.edit', $course) }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:border-navy hover:text-navy">Kelola Penjualan</a>
                </div>
            @empty
                <div class="px-5 py-14 text-center text-sm text-gray-500">Course Regular tidak ditemukan.</div>
            @endforelse
        </div>

        @if ($courses->hasPages())<div class="border-t border-gray-100 px-5 py-4">{{ $courses->links() }}</div>@endif
    </section>
</div>
@endsection
