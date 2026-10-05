@extends('layouts.public')

@section('title', 'Learning Path')

@section('content')
<div class="min-h-screen bg-[#f7f3ea] text-navy">
    <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
        <header class="max-w-3xl">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-bass-red">Learning Path</p>
            <h1 class="mt-3 text-4xl font-extrabold leading-[1.05] tracking-tight [font-family:Fraunces,serif] sm:text-6xl">Belajar dengan arah <span class="font-medium italic text-bass-red">yang lebih jelas.</span></h1>
            <p class="mt-5 max-w-2xl text-base leading-7 text-slate-600 sm:text-lg">Learning Path adalah panduan urutan course yang disarankan. Anda tetap memilih dan mengambil setiap course secara terpisah sesuai kebutuhan.</p>
        </header>

        @if ($learningPaths->isEmpty())
            <div class="mt-12 rounded-2xl border-2 border-dashed border-navy bg-[#fffdf7] px-6 py-16 text-center">
                <h2 class="text-2xl font-extrabold [font-family:Fraunces,serif]">Learning Path belum tersedia</h2>
                <p class="mt-2 text-sm text-slate-500">Jalur belajar terbaru akan ditampilkan di halaman ini.</p>
            </div>
        @else
            <div class="mt-12 space-y-8">
                @foreach ($learningPaths as $learningPath)
                    <article class="grid overflow-hidden rounded-2xl border-2 border-navy bg-[#fffdf7] shadow-[7px_7px_0_#17243A] md:grid-cols-[1.35fr_0.85fr]">
                        <div class="p-6 sm:p-8 md:border-r-2 md:border-dashed md:border-navy/25">
                            <span class="inline-flex rounded-full bg-navy px-3 py-1 text-xs font-bold text-white">{{ $learningPath->courses->count() }} langkah</span>
                            <h2 class="mt-5 text-3xl font-extrabold leading-tight [font-family:Fraunces,serif] sm:text-4xl">{{ $learningPath->title }}</h2>
                            @if ($learningPath->short_description)
                                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">{{ $learningPath->short_description }}</p>
                            @endif

                            <ol class="mt-7 ml-2 border-l-2 border-bass-red">
                                @foreach ($learningPath->courses as $index => $course)
                                    <li class="relative pb-5 pl-7 last:pb-0 before:absolute before:-left-[9px] before:top-1 before:h-4 before:w-4 before:rounded-full before:border-[3px] before:border-bass-red before:bg-[#fffdf7]">
                                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-bass-red">Langkah {{ $index + 1 }}</p>
                                        <p class="mt-1 font-semibold leading-snug">{{ $course->title }}</p>
                                    </li>
                                @endforeach
                            </ol>
                        </div>

                        <div class="flex flex-col justify-center bg-white/55 p-6 sm:p-8">
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-bass-red">Panduan belajar</p>
                            <p class="mt-3 text-4xl font-extrabold leading-none [font-family:Fraunces,serif]">{{ $learningPath->courses->count() }} langkah</p>
                            <p class="mt-4 text-sm leading-6 text-slate-600">Lihat urutan rekomendasi, status belajar pribadi, dan course yang disarankan berikutnya. Pendaftaran setiap course tetap dilakukan secara terpisah.</p>
                            <a href="{{ route('learning-paths.show', $learningPath) }}" class="mt-8 inline-flex min-h-12 items-center justify-center rounded-xl border-2 border-navy bg-[#fffdf7] px-5 text-sm font-bold text-navy shadow-[4px_4px_0_#DA1E1E] transition hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[2px_2px_0_#DA1E1E]">Lihat Learning Path</a>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-10">{{ $learningPaths->links() }}</div>
        @endif
    </div>
</div>
@endsection
