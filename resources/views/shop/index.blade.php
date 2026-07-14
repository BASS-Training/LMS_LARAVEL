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
    <form method="GET" action="{{ route('shop.index') }}" class="mb-6 flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="search" name="q" value="{{ $search }}" placeholder="Cari kursus…"
                   class="w-full pl-10 pr-4 py-2.5 rounded-lg border-gray-300 focus:border-bass-red focus:ring-bass-red text-sm">
        </div>

        <div class="flex gap-2">
            @foreach (['' => 'Semua', 'free' => 'Gratis', 'paid' => 'Berbayar'] as $value => $label)
                <button type="submit" name="harga" value="{{ $value }}"
                        class="px-4 py-2.5 rounded-lg text-sm font-medium border transition-colors
                               {{ $priceFilter === ($value ?: null)
                                   ? 'bg-bass-red text-white border-bass-red'
                                   : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </form>

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
                @php $owned = in_array($course->id, $enrolledIds, true); @endphp

                <a href="{{ route('shop.show', $course) }}"
                   class="group flex flex-col bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg hover:border-gray-300 transition-all">

                    {{-- Thumbnail --}}
                    <div class="relative aspect-video bg-gray-100 overflow-hidden">
                        @if ($course->thumbnail)
                            <img src="{{ asset('storage/' . $course->thumbnail) }}" alt="{{ $course->title }}"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                 loading="lazy">
                        @else
                            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
                                <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                            </div>
                        @endif

                        @if ($owned)
                            <span class="absolute top-2 left-2 px-2 py-1 rounded-md bg-emerald-600 text-white text-xs font-semibold shadow">
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

                        <p class="mt-2 text-xs text-gray-400">
                            {{ $course->instructors->pluck('name')->join(', ') ?: 'Instruktur BASS' }}
                        </p>

                        <div class="mt-auto pt-3 flex items-center justify-between">
                            <span class="text-xs text-gray-500">{{ $course->lessons_count }} pelajaran</span>
                            <span class="font-bold {{ $course->isFree() ? 'text-emerald-600' : 'text-gray-900' }}">
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
