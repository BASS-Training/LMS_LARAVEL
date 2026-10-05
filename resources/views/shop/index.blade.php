@extends('layouts.app')

@section('title', 'Katalog Kursus')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Hero --}}
    <div class="mb-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Katalog Kursus</h1>
        <p class="mt-1 text-sm text-gray-500">
            Jelajahi kursus yang tersedia. Pilih, daftar, dan mulai belajar hari ini.
        </p>
    </div>

    {{-- Filter --}}
    <form method="GET" action="{{ route('shop.index') }}" class="mb-6 grid grid-cols-1 gap-3 lg:grid-cols-12">
        <div class="relative lg:col-span-4">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="search" name="q" value="{{ $search }}" placeholder="Cari kursus…"
                   class="w-full pl-10 pr-4 py-2.5 rounded-lg border-gray-300 focus:border-bass-red focus:ring-bass-red text-sm">
        </div>

        <select name="category" class="rounded-lg border-gray-300 text-sm focus:border-bass-red focus:ring-bass-red lg:col-span-2">
            <option value="">Semua kategori</option>
            @foreach ($categories as $category)
                <option value="{{ $category->slug }}" @selected($categoryFilter === $category->slug)>{{ $category->name }}</option>
            @endforeach
        </select>

        <select name="tag" class="rounded-lg border-gray-300 text-sm focus:border-bass-red focus:ring-bass-red lg:col-span-2">
            <option value="">Semua tag</option>
            @foreach ($tags as $tag)
                <option value="{{ $tag->slug }}" @selected($tagFilter === $tag->slug)>{{ $tag->name }}</option>
            @endforeach
        </select>

        <div class="flex gap-2 lg:col-span-4">
            @foreach (['' => 'Semua', 'free' => 'Gratis', 'paid' => 'Berbayar'] as $value => $label)
                <button type="submit" name="harga" value="{{ $value }}"
                        class="flex-1 px-3 py-2.5 rounded-lg text-sm font-medium border transition-colors
                               {{ $priceFilter === ($value ?: null)
                                   ? 'bg-bass-red text-white border-bass-red'
                                   : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </form>

    @if ($bundles->isNotEmpty())
        <section id="paket-kursus" class="mb-10 scroll-mt-24">
            <div class="mb-4 flex items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-bass-red">Lebih hemat</p>
                    <h2 class="mt-1 text-xl font-bold text-gray-900">Paket Kursus</h2>
                </div>
                <p class="hidden text-sm text-gray-500 sm:block">Beberapa kursus dalam satu transaksi</p>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($bundles as $bundle)
                    <a href="{{ route('bundles.show', $bundle) }}" class="group rounded-2xl border border-navy/15 bg-navy p-5 text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <span class="rounded-full bg-white/10 px-2.5 py-1 text-xs font-semibold">{{ $bundle->courses->count() }} kursus</span>
                                <h3 class="mt-4 text-xl font-bold group-hover:text-red-100">{{ $bundle->title }}</h3>
                            </div>
                            <span class="text-2xl text-white/60" aria-hidden="true">&rarr;</span>
                        </div>
                        <div class="mt-6 flex items-end justify-between gap-4 border-t border-white/10 pt-4">
                            <div>
                                <p class="text-xs text-white/60 line-through">{{ $bundle->original_price_label }}</p>
                                <p class="text-lg font-bold">{{ $bundle->price_label }}</p>
                            </div>
                            @if ($bundle->savings() > 0)
                                <span class="rounded-lg bg-white px-3 py-1.5 text-xs font-bold text-navy">Hemat {{ $bundle->savings_label }}</span>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if ($learningPaths->isNotEmpty())
        <section id="jalur-belajar" class="mb-10 scroll-mt-24">
            <div class="mb-4"><p class="text-xs font-bold uppercase tracking-[0.2em] text-bass-red">Belajar terarah</p><h2 class="mt-1 text-xl font-bold text-gray-900">Jalur Belajar</h2><p class="mt-1 text-sm text-gray-500">Ikuti urutan course yang disarankan. Pendaftaran setiap course tetap terpisah.</p></div>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($learningPaths as $learningPath)
                    <a href="{{ route('learning-paths.show', $learningPath) }}" class="group rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-bass-red/30 hover:shadow-lg"><div class="flex items-center justify-between"><span class="rounded-full bg-bass-red-soft px-2.5 py-1 text-xs font-bold text-bass-red">{{ $learningPath->courses->count() }} langkah</span><span class="text-xl text-gray-300 group-hover:text-bass-red">&rarr;</span></div><h3 class="mt-4 text-lg font-bold text-navy group-hover:text-bass-red">{{ $learningPath->title }}</h3>@if ($learningPath->short_description)<p class="mt-2 line-clamp-2 text-sm text-gray-500">{{ $learningPath->short_description }}</p>@endif<p class="mt-4 line-clamp-1 text-xs font-medium text-gray-400">{{ $learningPath->courses->pluck('title')->join(' · ') }}</p></a>
                @endforeach
            </div>
        </section>
    @endif

    @if ($courses->isEmpty())
        <div class="text-center py-20 bg-white rounded-xl border border-gray-200">
            <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
            <p class="mt-4 text-sm font-medium text-gray-900">Belum ada kursus di katalog</p>
            <p class="mt-1 text-sm text-gray-500">
                {{ $search ? 'Coba kata kunci lain.' : 'Kursus yang dijual akan muncul di sini.' }}
            </p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
            @foreach ($courses as $course)
                @php
                    $owned = in_array($course->id, $enrolledIds, true);
                    $managed = in_array($course->id, $managedIds, true);
                @endphp

                <a href="{{ route('shop.show', $course) }}"
                   class="group flex flex-col bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg hover:border-gray-300 transition-all">

                    {{-- Thumbnail --}}
                    <div class="relative aspect-video bg-gray-100 overflow-hidden">
                        @if ($course->thumbnail)
                            <img src="{{ asset('storage/' . $course->thumbnail) }}" alt="{{ $course->title }}"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                 loading="lazy">
                        @else
                            <div class="w-full h-full flex items-center justify-center bg-gray-200">
                                <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                            </div>
                        @endif

                        @if ($managed)
                            <span class="absolute top-2 left-2 px-2 py-1 rounded-md bg-navy text-white text-xs font-semibold shadow">
                                Dikelola
                            </span>
                        @elseif ($owned)
                            <span class="absolute top-2 left-2 px-2 py-1 rounded-md bg-success text-white text-xs font-semibold shadow">
                                Sudah dimiliki
                            </span>
                        @endif
                    </div>

                    {{-- Body --}}
                    <div class="flex-1 flex flex-col p-4">
                        <h2 class="font-semibold text-gray-900 leading-snug line-clamp-2 group-hover:text-bass-red transition-colors">
                            {{ $course->title }}
                        </h2>

                        @if ($course->short_description)
                            <p class="mt-1.5 text-sm text-gray-500 line-clamp-2">{{ $course->short_description }}</p>
                        @endif

                        @if ($course->categories->isNotEmpty() || $course->tags->isNotEmpty())
                            <div class="mt-3 flex flex-wrap gap-1.5">
                                @foreach ($course->categories->take(1) as $category)
                                    <span class="rounded-full bg-navy/10 px-2 py-0.5 text-xs font-medium text-navy">{{ $category->name }}</span>
                                @endforeach
                                @foreach ($course->tags->take(2) as $tag)
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">#{{ $tag->name }}</span>
                                @endforeach
                            </div>
                        @endif

                        <p class="mt-2 text-xs text-gray-400">
                            {{ $course->instructors->pluck('name')->join(', ') ?: 'Instruktur BASS' }}
                        </p>

                        <div class="mt-auto pt-3 flex items-center justify-between">
                            <span class="text-xs text-gray-500">{{ $course->lessons_count }} pelajaran</span>
                            <span class="font-bold {{ $course->isFree() ? 'text-success' : 'text-navy' }}">
                                {{ $course->price_label }}
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $courses->links() }}
        </div>
    @endif
</div>
@endsection
