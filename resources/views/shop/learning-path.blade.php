@extends('layouts.public')

@section('title', $learningPath->title)

@section('content')
<div class="min-h-screen bg-[#f7f3ea] text-navy">
    <div class="border-b-2 border-navy bg-navy pb-12 pt-10 text-white sm:pb-16 sm:pt-14">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <a href="{{ route('learning-paths.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-white/70 transition hover:text-bass-gold"><span aria-hidden="true">&larr;</span> Kembali ke daftar learning path</a>
            <div class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1fr)_320px] lg:items-end">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-bass-gold">Jalur Belajar</p>
                    <h1 class="mt-3 text-4xl font-extrabold leading-[1.05] tracking-tight [font-family:Fraunces,serif] sm:text-6xl">{{ $learningPath->title }}</h1>
                    @if ($learningPath->short_description)
                        <p class="mt-5 max-w-2xl text-base leading-7 text-white/70 sm:text-lg">{{ $learningPath->short_description }}</p>
                    @endif
                    <div class="mt-6 flex flex-wrap gap-3 text-sm font-semibold">
                        <span class="rounded-full border border-white/40 px-4 py-1.5">{{ $learningPath->courses->count() }} course</span>
                        <span class="rounded-full border border-white/40 px-4 py-1.5">Urutan belajar yang disarankan</span>
                    </div>
                </div>

                @if ($progress)
                    <div class="rounded-2xl border-2 border-white bg-[#fffdf7] p-5 text-navy shadow-[6px_6px_0_#F6C945]">
                        <div class="flex items-end justify-between gap-4">
                            <span class="text-sm font-semibold text-slate-500">Progress Anda</span>
                            <strong class="text-4xl leading-none [font-family:Fraunces,serif]">{{ number_format($progress['progressPercentage'], 0) }}%</strong>
                        </div>
                        <div class="mt-4 h-3 overflow-hidden rounded-full border border-navy bg-[#f7f3ea]"><div class="h-full bg-bass-red" style="width: {{ $progress['progressPercentage'] }}%"></div></div>
                        <p class="mt-3 text-xs text-slate-500">{{ $progress['completedCourses'] }} dari {{ $progress['totalCourses'] }} course selesai</p>
                    </div>
                @else
                    <div class="rounded-2xl border-2 border-white bg-[#fffdf7] p-5 text-sm leading-6 text-navy shadow-[6px_6px_0_#F6C945]">Masuk untuk melihat progress pribadi dan rekomendasi course berikutnya.</div>
                @endif
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
        @if ($learningPath->description)
            <section class="rounded-2xl border-2 border-navy bg-[#fffdf7] p-6 sm:p-8">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-bass-red">Tentang jalur ini</p>
                <h2 class="mt-2 text-3xl font-extrabold [font-family:Fraunces,serif]">Gambaran Learning Path</h2>
                <div class="prose prose-sm mt-4 max-w-none leading-7 text-slate-600">{!! nl2br(e($learningPath->description)) !!}</div>
            </section>
        @endif

        <section class="{{ $learningPath->description ? 'mt-12' : '' }}">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-bass-red">Urutan yang disarankan</p>
                    <h2 class="mt-2 text-3xl font-extrabold [font-family:Fraunces,serif] sm:text-4xl">Langkah belajar Anda</h2>
                </div>
                <p class="max-w-sm text-sm leading-6 text-slate-500">Urutan ini tidak mengunci course berikutnya. Setiap course tetap dipilih secara terpisah.</p>
            </div>

            <ol class="mt-8 ml-5 border-l-2 border-bass-red">
                @foreach ($learningPath->courses as $index => $course)
                    @php
                        $courseProgress = $progress ? collect($progress['courses'])->first(fn ($item) => $item['course']->is($course)) : null;
                        $statusClasses = match ($courseProgress['status'] ?? null) {
                            'completed' => 'border-emerald-700 bg-emerald-50 text-emerald-700',
                            'in_progress' => 'border-bass-gold bg-amber-50 text-amber-800',
                            'not_started' => 'border-navy bg-slate-100 text-navy',
                            default => 'border-slate-300 bg-white text-slate-500',
                        };
                    @endphp
                    <li class="relative pb-8 pl-8 last:pb-0">
                        <span class="absolute -left-5 top-0 flex h-10 w-10 items-center justify-center rounded-full border-2 border-bass-red bg-[#f7f3ea] text-sm font-extrabold text-bass-red">{{ $index + 1 }}</span>
                        <article class="rounded-2xl border-2 border-navy bg-[#fffdf7] p-5 shadow-[5px_5px_0_#17243A] sm:p-6">
                            <div class="grid gap-5 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <h3 class="text-xl font-extrabold leading-tight [font-family:Fraunces,serif] sm:text-2xl">{{ $course->title }}</h3>
                                        @if ($courseProgress)
                                            <span class="rounded-full border px-3 py-1 text-xs font-bold {{ $statusClasses }}">{{ $courseProgress['statusLabel'] }}</span>
                                        @endif
                                    </div>
                                    <p class="mt-2 text-sm text-slate-500">{{ $course->instructors->pluck('name')->join(', ') ?: 'Instruktur BASS' }} &middot; {{ $course->price_label }}</p>
                                    @if ($courseProgress)
                                        <div class="mt-4 max-w-md">
                                            <div class="mb-1 flex justify-between text-[11px] font-semibold text-slate-500"><span>Progress course</span><span>{{ number_format($courseProgress['progress'], 0) }}%</span></div>
                                            <div class="h-2 overflow-hidden rounded-full border border-navy/30 bg-[#f7f3ea]"><div class="h-full bg-bass-red" style="width: {{ $courseProgress['progress'] }}%"></div></div>
                                        </div>
                                    @endif
                                </div>
                                <a href="{{ $courseProgress && $courseProgress['enrolled'] ? route('courses.show', $course) : route('shop.show', $course) }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border-2 border-navy bg-white px-5 text-sm font-bold text-navy shadow-[3px_3px_0_#DA1E1E] transition hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[1px_1px_0_#DA1E1E]">{{ $courseProgress && $courseProgress['enrolled'] ? 'Lanjutkan' : 'Lihat Course' }}</a>
                            </div>
                        </article>
                    </li>
                @endforeach
            </ol>
        </section>

        @if ($progress && $progress['recommendedCourse'])
            <aside class="mt-12 flex flex-col gap-5 rounded-2xl border-2 border-navy bg-bass-gold p-6 shadow-[7px_7px_0_#17243A] sm:flex-row sm:items-center sm:justify-between sm:p-8">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-bass-red">Course berikutnya yang direkomendasikan</p>
                    <p class="mt-2 text-2xl font-extrabold leading-tight [font-family:Fraunces,serif]">{{ $progress['recommendedCourse']->title }}</p>
                </div>
                <a href="{{ in_array($progress['recommendedCourse']->id, collect($progress['courses'])->where('enrolled', true)->pluck('course.id')->all(), true) ? route('courses.show', $progress['recommendedCourse']) : route('shop.show', $progress['recommendedCourse']) }}" class="inline-flex min-h-12 shrink-0 items-center justify-center rounded-xl border-2 border-navy bg-bass-red px-6 text-sm font-bold text-white shadow-[4px_4px_0_#fffdf7] transition hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[2px_2px_0_#fffdf7]">Lanjutkan Jalur</a>
            </aside>
        @endif
    </div>
</div>
@endsection
