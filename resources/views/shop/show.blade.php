@extends('layouts.app')

@section('title', $course->title)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <a href="{{ route('shop.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-bass-red mb-5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Kembali ke katalog
    </a>

    @if ($errors->has('shop'))
        <div class="mb-5 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
            {{ $errors->first('shop') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        {{-- ─────────── KIRI: Detail ─────────── --}}
        <div class="lg:col-span-2 space-y-6">

            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ $course->title }}</h1>

                @if ($course->short_description)
                    <p class="mt-2 text-gray-600">{{ $course->short_description }}</p>
                @endif

                <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-gray-500">
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        {{ $course->instructors->pluck('name')->join(', ') ?: 'Instruktur BASS' }}
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                        {{ $course->lessons->count() }} pelajaran &middot; {{ $totalContents }} materi
                    </span>
                </div>
            </div>

            @if ($course->description)
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h2 class="font-semibold text-gray-900 mb-3">Tentang kursus ini</h2>
                    <div class="prose prose-sm max-w-none text-gray-700">
                        {!! $course->description !!}
                    </div>
                </div>
            @endif

            @if ($course->objectives)
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h2 class="font-semibold text-gray-900 mb-3">Yang akan Anda pelajari</h2>
                    <div class="prose prose-sm max-w-none text-gray-700">
                        {!! $course->objectives !!}
                    </div>
                </div>
            @endif

            {{-- Kurikulum: judul saja, isi digembok --}}
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-gray-900">Kurikulum</h2>
                    @unless ($isEnrolled)
                        <p class="mt-0.5 text-xs text-gray-500">Materi terbuka setelah Anda terdaftar.</p>
                    @endunless
                </div>

                <div class="divide-y divide-gray-100">
                    @forelse ($course->lessons as $i => $lesson)
                        <div x-data="{ open: {{ $i === 0 ? 'true' : 'false' }} }">
                            <button type="button" @click="open = !open"
                                    class="w-full flex items-center justify-between gap-3 px-6 py-4 text-left hover:bg-gray-50 transition-colors">
                                <span class="flex items-center gap-3 min-w-0">
                                    <span class="flex-shrink-0 w-7 h-7 rounded-full bg-gray-100 text-gray-600 text-xs font-semibold flex items-center justify-center">
                                        {{ $i + 1 }}
                                    </span>
                                    <span class="font-medium text-gray-900 truncate">{{ $lesson->title }}</span>
                                </span>
                                <span class="flex items-center gap-2 flex-shrink-0">
                                    <span class="text-xs text-gray-400">{{ $lesson->contents->count() }} materi</span>
                                    <svg class="w-4 h-4 text-gray-400 transition-transform" :class="{ 'rotate-180': open }"
                                         fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                </span>
                            </button>

                            <div x-show="open" x-collapse style="display:none" class="pb-2">
                                @foreach ($lesson->contents as $content)
                                    <div class="flex items-center gap-3 pl-16 pr-6 py-2 text-sm">
                                        <svg class="w-4 h-4 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                        <span class="text-gray-600 truncate">{{ $content->title }}</span>
                                        <span class="ml-auto text-xs text-gray-400 capitalize flex-shrink-0">{{ $content->type }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="px-6 py-8 text-center text-sm text-gray-500">Kurikulum belum tersedia.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ─────────── KANAN: Kartu beli (sticky) ─────────── --}}
        <div class="lg:col-span-1">
            <div class="lg:sticky lg:top-24 bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">

                <div class="aspect-video bg-gray-100">
                    @if ($course->thumbnail)
                        <img src="{{ asset('storage/' . $course->thumbnail) }}" alt="{{ $course->title }}"
                             class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
                            <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                    @endif
                </div>

                <div class="p-6 space-y-4">
                    <p class="text-3xl font-bold {{ $course->isFree() ? 'text-emerald-600' : 'text-gray-900' }}">
                        {{ $course->price_label }}
                    </p>

                    @if (! Auth::check())
                        <a href="{{ route('login') }}"
                           class="w-full inline-flex items-center justify-center min-h-[48px] rounded-lg bg-bass-red text-white font-semibold hover:bg-red-800 transition-colors">
                            Masuk untuk {{ $course->isFree() ? 'mendaftar' : 'membeli' }}
                        </a>
                        <p class="text-center text-xs text-gray-500">
                            Belum punya akun?
                            <a href="{{ route('register') }}" class="text-bass-red font-medium hover:underline">Daftar gratis</a>
                        </p>

                    @elseif ($isEnrolled)
                        <a href="{{ route('courses.show', $course) }}"
                           class="w-full inline-flex items-center justify-center gap-2 min-h-[48px] rounded-lg bg-emerald-600 text-white font-semibold hover:bg-emerald-700 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            Lanjutkan Belajar
                        </a>
                        <p class="text-center text-xs text-gray-500">Anda sudah terdaftar di kursus ini.</p>

                    @elseif ($course->isFree())
                        <form method="POST" action="{{ route('shop.enroll-free', $course) }}">
                            @csrf
                            <button type="submit"
                                    class="w-full inline-flex items-center justify-center min-h-[48px] rounded-lg bg-bass-red text-white font-semibold hover:bg-red-800 transition-colors">
                                Daftar Gratis
                            </button>
                        </form>
                        <p class="text-center text-xs text-gray-500">Langsung bisa diakses setelah mendaftar.</p>

                    @else
                        {{-- Fase 2: diganti tombol checkout Midtrans --}}
                        <button type="button" disabled
                                class="w-full inline-flex items-center justify-center min-h-[48px] rounded-lg bg-gray-200 text-gray-500 font-semibold cursor-not-allowed">
                            Beli Sekarang
                        </button>
                        <p class="text-center text-xs text-gray-500">Pembayaran online segera hadir.</p>
                    @endif

                    <div class="pt-4 border-t border-gray-100 space-y-2 text-sm text-gray-600">
                        <p class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Akses selamanya
                        </p>
                        <p class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Bisa dibuka di aplikasi mobile
                        </p>
                        <p class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Sertifikat setelah lulus
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
