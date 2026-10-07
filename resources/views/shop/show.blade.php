@extends('layouts.public')

@section('title', $course->salesProfile?->seo_title ?: $course->title)
@section('meta_description', $course->salesProfile?->seo_description ?: ($course->salesProfile?->headline ?: ($course->short_description ?: 'Pelatihan profesional BASS untuk meningkatkan kompetensi dan kesiapan karier Anda.')))

@section('content')
<header class="relative overflow-hidden bg-navy pb-32 pt-10 text-white">
    <div class="pointer-events-none absolute -right-8 -top-20 text-[20rem] font-extrabold leading-none text-white/[0.04] [font-family:Fraunces,serif]">B</div>
    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <nav class="text-xs font-semibold text-white/60"><a href="{{ route('welcome') }}" class="hover:text-white">Beranda</a> <span class="mx-1">/</span> <a href="{{ route('shop.index') }}" class="hover:text-white">Katalog</a> <span class="mx-1">/</span> {{ $course->title }}</nav>
        <p class="mt-8 text-xs font-bold uppercase tracking-[0.2em] text-bass-gold">Course BASS</p>
        <h1 class="mt-3 max-w-4xl text-4xl font-extrabold leading-[1.03] tracking-[-0.035em] [font-family:Fraunces,serif] sm:text-6xl">{{ $course->title }}</h1>
        @if ($course->salesProfile?->headline || $course->short_description)<p class="mt-5 max-w-3xl text-lg leading-8 text-white/75">{{ $course->salesProfile?->headline ?: $course->short_description }}</p>@endif
        <div class="mt-6 flex flex-wrap gap-2 text-xs font-semibold">
            <span class="rounded-full border-2 border-bass-gold bg-bass-gold px-3 py-1.5 text-navy">{{ $course->price_label }}</span>
            <span class="rounded-full border border-white/60 px-3 py-1.5">{{ $course->lessons->count() }} pelajaran</span>
            <span class="rounded-full border border-white/60 px-3 py-1.5">{{ $totalContents }} materi</span>
            <span class="rounded-full border border-white/60 px-3 py-1.5">{{ $course->instructors->pluck('name')->join(', ') ?: 'Instruktur BASS' }}</span>
        </div>
    </div>
</header>

<div class="relative mx-auto -mt-24 grid max-w-7xl items-start gap-10 px-4 pb-16 sm:px-6 lg:grid-cols-[1fr_350px] lg:px-8">
    <main class="order-2 space-y-9 pt-4 lg:order-1 lg:pt-28">
        @if ($errors->has('shop'))<div class="rounded-xl border-2 border-error bg-error-soft px-4 py-3 text-sm font-semibold text-error" role="alert">{{ $errors->first('shop') }}</div>@endif

        @if ($course->objectives)
            <section><h2 class="text-3xl font-extrabold [font-family:Fraunces,serif]">Yang akan Anda pelajari</h2><div class="prose prose-sm mt-4 max-w-none text-slate-700 prose-li:marker:text-bass-red">{!! $course->objectives !!}</div></section>
        @endif

        @if ($course->salesProfile?->learning_benefits)
            <section><h2 class="text-3xl font-extrabold [font-family:Fraunces,serif]">Manfaat yang Anda dapatkan</h2><div class="mt-4 whitespace-pre-line leading-7 text-slate-700">{{ $course->salesProfile->learning_benefits }}</div></section>
        @endif

        @if ($course->description)
            <section><h2 class="text-3xl font-extrabold [font-family:Fraunces,serif]">Tentang course ini</h2><div class="prose prose-sm mt-4 max-w-none text-slate-700">{!! $course->description !!}</div></section>
        @endif

        @if ($course->salesProfile && ($course->salesProfile->target_audience || $course->salesProfile->requirements))
            <section class="grid gap-5 sm:grid-cols-2">
                @if ($course->salesProfile->target_audience)<div class="rounded-2xl border-2 border-navy bg-[#fffdf7] p-6"><h2 class="text-xl font-extrabold [font-family:Fraunces,serif]">Cocok untuk siapa?</h2><p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $course->salesProfile->target_audience }}</p></div>@endif
                @if ($course->salesProfile->requirements)<div class="rounded-2xl border-2 border-navy bg-[#fffdf7] p-6"><h2 class="text-xl font-extrabold [font-family:Fraunces,serif]">Persyaratan</h2><p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $course->salesProfile->requirements }}</p></div>@endif
            </section>
        @endif

        @if ($course->instructors->isNotEmpty())
            <section>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-bass-red">Kenali Pengajar Anda</p>
                <h2 class="mt-2 text-3xl font-extrabold [font-family:Fraunces,serif]">Instruktur</h2>
                <div class="mt-5 grid gap-5 {{ $course->instructors->count() > 1 ? 'md:grid-cols-2' : '' }}">
                    @foreach ($course->instructors as $instructor)
                        <article class="flex flex-col gap-5 rounded-2xl border-2 border-navy bg-[#fffdf7] p-6 shadow-[5px_5px_0_#F6C945] sm:flex-row">
                            <div class="shrink-0">
                                @if ($instructor->avatar)
                                    <img src="{{ asset('storage/'.$instructor->avatar) }}" alt="Foto {{ $instructor->name }}" class="h-28 w-28 rounded-2xl border-2 border-navy object-cover">
                                @else
                                    <span class="flex h-28 w-28 items-center justify-center rounded-2xl border-2 border-navy bg-navy text-3xl font-extrabold text-bass-gold [font-family:Fraunces,serif]">{{ Str::upper(Str::substr($instructor->name, 0, 2)) }}</span>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-2xl font-extrabold text-navy [font-family:Fraunces,serif]">{{ $instructor->name }}</h3>
                                @if ($instructor->instructor_bio)
                                    <div class="prose prose-sm mt-3 max-w-none text-slate-700 prose-a:font-semibold prose-a:text-bass-red">{!! $instructor->instructor_bio !!}</div>
                                @else
                                    <p class="mt-3 text-sm leading-6 text-slate-500">Instruktur BASS yang akan mendampingi proses belajar Anda.</p>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        @if (!empty($course->salesProfile?->faq))
            <section><h2 class="text-3xl font-extrabold [font-family:Fraunces,serif]">Pertanyaan yang sering diajukan</h2><div class="mt-5 space-y-3">@foreach ($course->salesProfile->faq as $faq)<details class="rounded-xl border-2 border-navy bg-[#fffdf7] px-5"><summary class="cursor-pointer py-4 font-bold">{{ $faq['question'] }}</summary><p class="border-t border-dashed border-navy/20 py-4 text-sm leading-6 text-slate-700">{{ $faq['answer'] }}</p></details>@endforeach</div></section>
        @endif

        @if ($learningPaths->isNotEmpty())
            <section class="rounded-2xl border-2 border-navy bg-navy p-6 text-white shadow-[6px_6px_0_#F6C945]">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-bass-gold">Bagian dari Learning Path</p>
                <div class="mt-4 space-y-3">@foreach ($learningPaths as $learningPath)@php($position = $learningPath->courses->search(fn ($pathCourse) => $pathCourse->is($course)) + 1)<a href="{{ route('learning-paths.show', $learningPath) }}" class="flex items-center justify-between gap-4 rounded-xl border border-white/20 bg-white/10 p-4 hover:bg-white/15"><div><h3 class="font-bold">{{ $learningPath->title }}</h3><p class="mt-1 text-xs text-white/60">Langkah {{ $position }} dari {{ $learningPath->courses->count() }}</p></div><span class="text-bass-gold">&rarr;</span></a>@endforeach</div>
            </section>
        @endif

        <section>
            <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-end"><h2 class="text-3xl font-extrabold [font-family:Fraunces,serif]">Kurikulum</h2>@unless ($isEnrolled)<p class="text-xs text-slate-500">Isi materi terbuka setelah Anda terdaftar.</p>@endunless</div>
            <div class="mt-5 space-y-3">
                @forelse ($course->lessons as $i => $lesson)
                    <details class="group rounded-xl border-2 border-navy bg-[#fffdf7] px-5" @if ($i === 0) open @endif>
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 py-4"><span class="flex min-w-0 items-center gap-3"><span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-2 border-navy text-xs font-bold">{{ $i + 1 }}</span><strong class="truncate text-lg [font-family:Fraunces,serif]">{{ $lesson->title }}</strong></span><span class="text-xs text-slate-500">{{ $lesson->contents->count() }} materi</span></summary>
                        <div class="border-t border-dashed border-navy/20 pb-3 pt-2">@foreach ($lesson->contents as $content)<div class="flex items-center gap-3 py-2 pl-11 text-sm text-slate-600"><span class="h-2 w-2 shrink-0 rounded-full bg-bass-red"></span><span class="truncate">{{ $content->title }}</span><span class="ml-auto shrink-0 text-xs capitalize text-slate-400">{{ $content->type }}</span></div>@endforeach</div>
                    </details>
                @empty
                    <div class="rounded-xl border-2 border-dashed border-navy bg-[#fffdf7] px-6 py-10 text-center text-sm text-slate-500">Kurikulum belum tersedia.</div>
                @endforelse
            </div>
        </section>
    </main>

    <aside class="order-1 lg:order-2">
        <div class="sticky top-24 overflow-hidden rounded-2xl border-2 border-navy bg-[#fffdf7] shadow-[8px_8px_0_#F6C945]">
            <div class="aspect-video bg-navy">@if ($course->thumbnail)<img src="{{ asset('storage/'.$course->thumbnail) }}" alt="{{ $course->title }}" class="h-full w-full object-cover">@else<div class="flex h-full items-center justify-center text-7xl font-extrabold text-white/20 [font-family:Fraunces,serif]">BASS</div>@endif</div>
            <div class="space-y-4 p-6">
                @if ($breakdown && $breakdown['fee'] > 0 && ($methodsEnabled ?? false))
                    <p class="text-4xl font-extrabold [font-family:Fraunces,serif]">Rp {{ number_format($breakdown['base'], 0, ',', '.') }}</p>
                @elseif ($breakdown && $breakdown['fee'] > 0)
                    <div><p class="text-4xl font-extrabold [font-family:Fraunces,serif]">Rp {{ number_format($breakdown['total'], 0, ',', '.') }}</p><p class="mt-1 text-xs text-slate-500">Sudah termasuk {{ strtolower($feeLabel) }}</p><dl class="mt-4 space-y-2 border-t border-dashed border-navy/20 pt-3 text-sm"><div class="flex justify-between"><dt>Harga course</dt><dd>Rp {{ number_format($breakdown['base'], 0, ',', '.') }}</dd></div><div class="flex justify-between"><dt>{{ $feeLabel }}</dt><dd>Rp {{ number_format($breakdown['fee'], 0, ',', '.') }}</dd></div></dl></div>
                @else
                    <p class="text-4xl font-extrabold {{ $course->isFree() ? 'text-success' : '' }} [font-family:Fraunces,serif]">{{ $course->price_label }}</p>
                @endif

                @guest
                    <a href="{{ route('login') }}" class="inline-flex min-h-12 w-full items-center justify-center rounded-xl border-2 border-navy bg-bass-red px-5 font-bold text-white shadow-[4px_4px_0_#17243A]">Masuk untuk {{ $course->isFree() ? 'Mendaftar' : 'Membeli' }}</a><p class="text-center text-xs text-slate-500">Belum punya akun? <a href="{{ route('register') }}" class="font-bold text-bass-red">Daftar gratis</a></p>
                @elseif ($isEnrolled)
                    <a href="{{ route('courses.show', $course) }}" class="inline-flex min-h-12 w-full items-center justify-center rounded-xl border-2 border-navy bg-bass-red px-5 font-bold text-white shadow-[4px_4px_0_#17243A]">Lanjutkan Belajar</a><p class="text-center text-xs text-slate-500">Anda sudah terdaftar di course ini.</p>
                @elseif ($isManager)
                    <a href="{{ route('courses.show', $course) }}" class="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-navy px-5 font-bold text-white">Kelola Course</a><p class="text-center text-xs text-slate-500">Pengelola dapat membuka seluruh materi tanpa membeli.</p>
                @elseif ($course->isFree())
                    <form method="POST" action="{{ route('shop.enroll-free', $course) }}">@csrf<button class="inline-flex min-h-12 w-full items-center justify-center rounded-xl border-2 border-navy bg-bass-red px-5 font-bold text-white shadow-[4px_4px_0_#17243A]">Daftar Gratis</button></form><p class="text-center text-xs text-slate-500">Langsung bisa diakses setelah mendaftar.</p>
                @else
                    @php($refundSettings = \App\Models\RefundSetting::current())
                    <form method="GET" action="{{ route('checkout.choose', $course) }}" class="space-y-3">
                        @include('shop.partials.refund-consent')
                        <button type="submit" class="inline-flex min-h-12 w-full items-center justify-center rounded-xl border-2 border-navy bg-bass-red px-5 font-bold text-white shadow-[4px_4px_0_#17243A]">Beli Sekarang</button>
                    </form>
                    <p class="text-center text-xs text-slate-500">Pilih QRIS, e-wallet, transfer bank, atau kartu.</p>
                @endguest

                @if ($course->salesProfile?->promo_video_url)<a href="{{ $course->salesProfile->promo_video_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex w-full items-center justify-center rounded-xl border-2 border-navy px-4 py-3 text-sm font-bold hover:bg-white">Tonton Video Promosi</a>@endif
                <ul class="border-t border-dashed border-navy/20 pt-3 text-sm">
                    @if ($course->salesProfile?->level)<li class="py-1.5">Level: {{ ['beginner' => 'Pemula', 'intermediate' => 'Menengah', 'advanced' => 'Mahir', 'all_levels' => 'Semua Level'][$course->salesProfile->level] ?? $course->salesProfile->level }}</li>@endif
                    @if ($course->salesProfile?->language)<li class="py-1.5">Bahasa: {{ $course->salesProfile->language }}</li>@endif
                    @if ($course->salesProfile?->estimated_duration_minutes)<li class="py-1.5">Estimasi durasi: {{ intdiv($course->salesProfile->estimated_duration_minutes, 60) > 0 ? intdiv($course->salesProfile->estimated_duration_minutes, 60).' jam ' : '' }}{{ $course->salesProfile->estimated_duration_minutes % 60 ? ($course->salesProfile->estimated_duration_minutes % 60).' menit' : '' }}</li>@endif
                    <li class="py-1.5">Akses pembelajaran setelah terdaftar</li><li class="py-1.5">Dapat dibuka melalui web dan mobile</li><li class="py-1.5">Sertifikat sesuai persyaratan course</li>
                </ul>
            </div>
        </div>
    </aside>
</div>
@endsection
