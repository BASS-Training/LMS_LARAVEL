@extends('layouts.app')

@section('title', $learningPath->title)

@section('content')
<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
    <a href="{{ route('shop.index') }}#jalur-belajar" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-bass-red">&larr; Kembali ke katalog</a>

    <header class="mt-5 overflow-hidden rounded-3xl bg-navy text-white shadow-xl">
        <div class="grid gap-8 px-6 py-9 sm:px-10 lg:grid-cols-[1fr_280px] lg:items-center lg:px-12 lg:py-12">
            <div>
                <p class="text-xs font-extrabold uppercase tracking-[0.2em] text-bass-gold">Jalur Belajar</p>
                <h1 class="mt-3 text-3xl font-extrabold leading-tight sm:text-4xl">{{ $learningPath->title }}</h1>
                @if ($learningPath->short_description)<p class="mt-4 max-w-2xl leading-7 text-white/70">{{ $learningPath->short_description }}</p>@endif
                <div class="mt-6 flex flex-wrap gap-3 text-sm font-semibold"><span class="rounded-full bg-white/10 px-3 py-1.5">{{ $learningPath->courses->count() }} course</span><span class="rounded-full bg-white/10 px-3 py-1.5">Gratis dan berbayar dapat digabung</span></div>
            </div>
            @if ($progress)
                <div class="rounded-2xl border border-white/15 bg-white/10 p-5">
                    <div class="flex items-end justify-between"><span class="text-sm text-white/65">Progress Anda</span><strong class="text-3xl">{{ number_format($progress['progressPercentage'], 0) }}%</strong></div>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-white/15"><div class="h-full rounded-full bg-bass-gold" style="width: {{ $progress['progressPercentage'] }}%"></div></div>
                    <p class="mt-3 text-xs text-white/60">{{ $progress['completedCourses'] }} dari {{ $progress['totalCourses'] }} course selesai</p>
                </div>
            @else
                <div class="rounded-2xl border border-white/15 bg-white/10 p-5 text-sm leading-6 text-white/70">Masuk untuk melihat progress pribadi dan rekomendasi course berikutnya.</div>
            @endif
        </div>
    </header>

    @if ($learningPath->description)
        <section class="mt-8 rounded-2xl border border-gray-200 bg-white p-6"><h2 class="text-lg font-bold text-gray-900">Tentang jalur ini</h2><div class="prose prose-sm mt-3 max-w-none text-gray-600">{!! nl2br(e($learningPath->description)) !!}</div></section>
    @endif

    <section class="mt-8">
        <div class="flex items-end justify-between gap-4"><div><p class="text-xs font-bold uppercase tracking-[0.18em] text-bass-red">Urutan yang disarankan</p><h2 class="mt-1 text-2xl font-bold text-gray-900">Langkah belajar Anda</h2></div><p class="hidden text-sm text-gray-500 sm:block">Urutan ini tidak mengunci course berikutnya</p></div>
        <ol class="mt-6 space-y-4">
            @foreach ($learningPath->courses as $index => $course)
                @php($courseProgress = $progress ? collect($progress['courses'])->first(fn ($item) => $item['course']->is($course)) : null)
                <li class="relative grid gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:grid-cols-[48px_1fr_auto] sm:items-center">
                    <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-navy text-lg font-extrabold text-white">{{ $index + 1 }}</span>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2"><h3 class="font-bold text-gray-900">{{ $course->title }}</h3>@if ($courseProgress)<span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $courseProgress['status'] === 'completed' ? 'bg-success-soft text-success' : ($courseProgress['status'] === 'in_progress' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600') }}">{{ $courseProgress['statusLabel'] }}</span>@endif</div>
                        <p class="mt-1 text-sm text-gray-500">{{ $course->instructors->pluck('name')->join(', ') ?: 'Instruktur BASS' }} &middot; {{ $course->price_label }}</p>
                        @if ($courseProgress)<div class="mt-3 h-1.5 max-w-md overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full bg-bass-red" style="width: {{ $courseProgress['progress'] }}%"></div></div>@endif
                    </div>
                    <a href="{{ $courseProgress && $courseProgress['enrolled'] ? route('courses.show', $course) : route('shop.show', $course) }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-gray-300 px-4 text-sm font-bold text-gray-700 hover:border-bass-red hover:text-bass-red">{{ $courseProgress && $courseProgress['enrolled'] ? 'Lanjutkan' : 'Lihat Course' }}</a>
                </li>
            @endforeach
        </ol>
    </section>

    @if ($progress && $progress['recommendedCourse'])
        <aside class="mt-8 flex flex-col gap-4 rounded-2xl border border-bass-red/20 bg-bass-red-soft p-6 sm:flex-row sm:items-center sm:justify-between"><div><p class="text-xs font-bold uppercase tracking-[0.16em] text-bass-red">Course berikutnya</p><p class="mt-1 text-lg font-bold text-navy">{{ $progress['recommendedCourse']->title }}</p></div><a href="{{ in_array($progress['recommendedCourse']->id, collect($progress['courses'])->where('enrolled', true)->pluck('course.id')->all(), true) ? route('courses.show', $progress['recommendedCourse']) : route('shop.show', $progress['recommendedCourse']) }}" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-bass-red px-5 text-sm font-bold text-white hover:bg-bass-red-hover">Lanjutkan Jalur</a></aside>
    @endif
</div>
@endsection
