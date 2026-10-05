<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Temukan program pelatihan profesional BASS Training Center untuk meningkatkan kompetensi dan kesiapan karier Anda.">
    <meta name="theme-color" content="#ffffff">
    <title>{{ config('app.name', 'BASS Academy') }} - Pelatihan Profesional untuk Karier Anda</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#f7f8fc] font-sans text-slate-900 antialiased">
    <div x-data="{ mobileOpen: false }" class="min-h-screen overflow-hidden">
        <header class="relative z-40 border-b border-slate-200/80 bg-white/95 backdrop-blur">
            <nav class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8" aria-label="Navigasi utama">
                <a href="{{ route('welcome') }}" class="flex items-center" aria-label="BASS Academy beranda">
                    <img src="{{ asset('images/logo.png') }}" alt="BASS Academy" class="h-11 w-auto">
                </a>

                <div class="hidden items-center gap-8 md:flex">
                    <a href="#program" class="text-sm font-semibold text-slate-700 transition hover:text-bass-red">Program</a>
                    <a href="#keunggulan" class="text-sm font-semibold text-slate-700 transition hover:text-bass-red">Keunggulan</a>
                    <a href="#cara-belajar" class="text-sm font-semibold text-slate-700 transition hover:text-bass-red">Cara Belajar</a>
                    <a href="{{ route('shop.index') }}" class="text-sm font-semibold text-slate-700 transition hover:text-bass-red">Katalog</a>
                </div>

                <div class="hidden items-center gap-3 sm:flex">
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-slate-700 transition hover:text-bass-red">Dashboard</a>
                        <a href="{{ route('shop.index') }}" class="inline-flex min-h-10 items-center rounded-lg bg-bass-red px-4 text-sm font-bold text-white transition hover:bg-bass-red-hover">Jelajahi Kursus</a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-semibold text-slate-700 transition hover:text-bass-red">Masuk</a>
                        <a href="{{ route('register') }}" class="inline-flex min-h-10 items-center rounded-lg bg-bass-red px-4 text-sm font-bold text-white transition hover:bg-bass-red-hover">Daftar</a>
                    @endauth
                </div>

                <button type="button" @click="mobileOpen = !mobileOpen" :aria-expanded="mobileOpen" aria-controls="landing-mobile-menu"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100 sm:hidden">
                    <span class="sr-only">Buka menu</span>
                    <svg x-show="!mobileOpen" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <svg x-show="mobileOpen" x-cloak class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </nav>

            <div id="landing-mobile-menu" x-show="mobileOpen" x-cloak @click.outside="mobileOpen = false"
                 class="border-t border-slate-200 bg-white px-4 py-4 sm:hidden">
                <div class="space-y-1">
                    <a href="#program" @click="mobileOpen = false" class="block rounded-lg px-3 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Program</a>
                    <a href="#keunggulan" @click="mobileOpen = false" class="block rounded-lg px-3 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Keunggulan</a>
                    <a href="#cara-belajar" @click="mobileOpen = false" class="block rounded-lg px-3 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cara Belajar</a>
                    <a href="{{ route('shop.index') }}" class="block rounded-lg px-3 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Katalog</a>
                </div>
                <div class="mt-3 grid grid-cols-2 gap-2 border-t border-slate-100 pt-4">
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 text-sm font-bold text-slate-700">Dashboard</a>
                        <a href="{{ route('shop.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-bass-red text-sm font-bold text-white">Katalog</a>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 text-sm font-bold text-slate-700">Masuk</a>
                        <a href="{{ route('register') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-bass-red text-sm font-bold text-white">Daftar</a>
                    @endauth
                </div>
            </div>
        </header>

        <div class="bg-bass-red px-4 py-2.5 text-center text-xs font-semibold text-white sm:text-sm">
            Pelatihan profesional, pembayaran aman, dan akses belajar langsung dalam satu platform.
        </div>

        <main>
            <section class="relative overflow-hidden bg-white">
                <div class="absolute inset-x-0 top-0 h-80 bg-gradient-to-b from-bass-red-soft/70 to-transparent" aria-hidden="true"></div>
                <div class="relative mx-auto grid max-w-7xl items-center gap-12 px-4 py-16 sm:px-6 sm:py-20 lg:grid-cols-[1.04fr_.96fr] lg:px-8 lg:py-24">
                    <div>
                        <span class="inline-flex items-center gap-2 rounded-full border border-bass-red/20 bg-white px-3 py-1.5 text-xs font-bold uppercase tracking-[0.16em] text-bass-red shadow-sm">
                            BASS Training Center
                        </span>
                        <h1 class="mt-6 max-w-3xl text-4xl font-extrabold leading-[1.08] tracking-tight text-navy sm:text-5xl lg:text-6xl">
                            Percepat karier melalui <span class="text-bass-red">pelatihan yang relevan.</span>
                        </h1>
                        <p class="mt-6 max-w-2xl text-base leading-7 text-slate-600 sm:text-lg">
                            Temukan program pelatihan profesional yang terstruktur, pelajari keterampilan praktis bersama instruktur, dan buktikan kompetensi Anda.
                        </p>

                        <form method="GET" action="{{ route('shop.index') }}" class="mt-8 flex max-w-2xl flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl shadow-slate-900/10 sm:flex-row">
                            <label for="landing-search" class="sr-only">Cari program pelatihan</label>
                            <div class="relative flex-1">
                                <svg class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
                                </svg>
                                <input id="landing-search" type="search" name="q" minlength="2" maxlength="100"
                                       placeholder="Cari pelatihan yang Anda butuhkan"
                                       class="min-h-12 w-full rounded-xl border-0 bg-slate-50 pl-12 pr-4 text-sm text-slate-900 placeholder:text-slate-400 focus:ring-2 focus:ring-bass-red">
                            </div>
                            <button type="submit" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-bass-red px-6 text-sm font-bold text-white transition hover:bg-bass-red-hover">
                                Temukan Kelas
                            </button>
                        </form>

                        <div class="mt-6 flex flex-wrap gap-x-6 gap-y-3 text-sm font-medium text-slate-600">
                            <span class="inline-flex items-center gap-2">
                                <svg class="h-5 w-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/></svg>
                                Belajar fleksibel
                            </span>
                            <span class="inline-flex items-center gap-2">
                                <svg class="h-5 w-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/></svg>
                                Materi terstruktur
                            </span>
                            <span class="inline-flex items-center gap-2">
                                <svg class="h-5 w-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/></svg>
                                Web dan mobile
                            </span>
                        </div>
                    </div>

                    <div class="relative mx-auto w-full max-w-xl lg:max-w-none" aria-label="Pelatihan dan persiapan sertifikasi BASS Academy">
                        <div class="absolute -left-8 top-10 h-40 w-40 rounded-full bg-bass-gold/25 blur-3xl" aria-hidden="true"></div>
                        <div class="absolute -right-8 bottom-0 h-56 w-56 rounded-full bg-bass-red/15 blur-3xl" aria-hidden="true"></div>
                        <div class="relative min-h-[430px] overflow-hidden rounded-[2rem] bg-navy shadow-2xl shadow-navy/25 sm:min-h-[520px]">
                            <img src="https://images.unsplash.com/photo-1524178232363-1fb2b075b655?auto=format&fit=crop&w=1200&q=85"
                                 alt="Suasana pelatihan profesional di dalam kelas"
                                 class="absolute inset-0 h-full w-full object-cover"
                                 loading="eager"
                                 fetchpriority="high">
                            <div class="absolute inset-0 bg-gradient-to-t from-navy via-navy/35 to-transparent" aria-hidden="true"></div>
                            <div class="absolute inset-x-0 bottom-0 p-5 sm:p-7">
                                <div class="rounded-2xl border border-white/15 bg-navy/90 p-5 shadow-xl backdrop-blur-md sm:p-6">
                                    <div class="flex items-start gap-4">
                                        <span class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-bass-red text-white shadow-lg shadow-black/20">
                                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 14.25c3.728 0 6.75-2.015 6.75-4.5S15.728 5.25 12 5.25 5.25 7.265 5.25 9.75s3.022 4.5 6.75 4.5Zm0 0v4.5m-3-1.5 3 1.5 3-1.5M3.75 9.75v6"/>
                                            </svg>
                                        </span>
                                        <div>
                                            <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-bass-gold">Pelatihan &amp; Persiapan Sertifikasi</p>
                                            <h2 class="mt-2 text-xl font-extrabold leading-tight text-white sm:text-2xl">Bangun kompetensi yang diakui industri.</h2>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            @if ($bundles->isNotEmpty())
                @php
                    $featuredBundle = $bundles->first();
                @endphp
                <section id="paket" class="scroll-mt-20 bg-[#111b2c] py-16 text-white sm:py-20">
                    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
                            <div>
                                <p class="text-xs font-extrabold uppercase tracking-[0.2em] text-bass-gold">Paket belajar pilihan</p>
                                <h2 class="mt-3 max-w-2xl text-3xl font-extrabold tracking-tight text-white sm:text-4xl">Lebih banyak keterampilan, harga lebih hemat.</h2>
                                <p class="mt-3 max-w-2xl text-sm leading-6 text-white/65 sm:text-base">Dapatkan beberapa course dalam satu paket terstruktur dan mulai perjalanan belajar Anda dengan biaya yang lebih efisien.</p>
                            </div>
                            <a href="{{ route('shop.index') }}#paket-kursus" class="inline-flex items-center gap-2 text-sm font-bold text-white transition hover:text-bass-gold">
                                Lihat semua paket
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"/></svg>
                            </a>
                        </div>

                        <div class="mt-10 grid gap-5 {{ $bundles->count() > 1 ? 'lg:grid-cols-[1.45fr_.75fr]' : '' }}">
                            <article class="overflow-hidden rounded-3xl bg-white text-slate-900 shadow-2xl shadow-black/20">
                                <div class="grid h-full md:grid-cols-[1.15fr_.85fr]">
                                    <div class="flex flex-col p-6 sm:p-8 lg:p-10">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded-full bg-bass-red px-3 py-1.5 text-[11px] font-extrabold uppercase tracking-[0.14em] text-white">Paket unggulan</span>
                                            <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-navy">{{ $featuredBundle->courses->count() }} course</span>
                                            @if ($featuredBundle->savings() > 0)
                                                <span class="rounded-full bg-bass-gold/20 px-3 py-1.5 text-xs font-extrabold text-amber-800">Hemat {{ $featuredBundle->savings_label }}</span>
                                            @endif
                                        </div>

                                        <h3 class="mt-6 text-2xl font-extrabold leading-tight text-navy sm:text-3xl">{{ $featuredBundle->title }}</h3>
                                        @if ($featuredBundle->description)
                                            <p class="mt-3 line-clamp-2 text-sm leading-6 text-slate-600">{{ $featuredBundle->description }}</p>
                                        @endif

                                        <ul class="mt-6 space-y-3" aria-label="Course dalam {{ $featuredBundle->title }}">
                                            @foreach ($featuredBundle->courses->take(3) as $course)
                                                <li class="flex items-start gap-3 text-sm font-semibold text-slate-700">
                                                    <span class="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-success-soft text-success">
                                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m5 13 4 4L19 7"/></svg>
                                                    </span>
                                                    <span class="line-clamp-1">{{ $course->title }}</span>
                                                </li>
                                            @endforeach
                                            @if ($featuredBundle->courses->count() > 3)
                                                <li class="pl-8 text-xs font-bold text-bass-red">+{{ $featuredBundle->courses->count() - 3 }} course lainnya</li>
                                            @endif
                                        </ul>

                                        <div class="mt-8 flex flex-col gap-5 border-t border-slate-200 pt-6 sm:flex-row sm:items-end sm:justify-between">
                                            <div>
                                                <p class="text-xs font-semibold text-slate-400">Harga normal <span class="line-through">{{ $featuredBundle->original_price_label }}</span></p>
                                                <p class="mt-1 text-3xl font-extrabold text-navy">{{ $featuredBundle->price_label }}</p>
                                            </div>
                                            <a href="{{ route('bundles.show', $featuredBundle) }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-bass-red px-6 text-sm font-extrabold text-white transition hover:bg-bass-red-hover focus:outline-none focus:ring-4 focus:ring-bass-red/20">
                                                Lihat Paket Hemat
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"/></svg>
                                            </a>
                                        </div>
                                    </div>

                                    <div class="relative min-h-64 overflow-hidden bg-slate-100 md:min-h-full">
                                        @if ($featuredBundle->courses->first()?->thumbnail)
                                            <img src="{{ asset('storage/'.$featuredBundle->courses->first()->thumbnail) }}" alt="{{ $featuredBundle->title }}" loading="lazy" class="absolute inset-0 h-full w-full object-cover transition duration-500 hover:scale-105">
                                        @else
                                            <div class="absolute inset-0 flex flex-col items-center justify-center bg-navy p-8 text-center">
                                                <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/10 text-bass-gold">
                                                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                                </span>
                                                <p class="mt-4 text-xs font-extrabold uppercase tracking-[0.18em] text-white/60">Belajar dalam satu paket</p>
                                            </div>
                                        @endif
                                        <div class="absolute bottom-4 left-4 right-4 rounded-2xl border border-white/20 bg-navy/90 p-4 shadow-xl backdrop-blur-sm">
                                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-bass-gold">Sekali transaksi</p>
                                            <p class="mt-1 text-sm font-bold text-white">Akses seluruh course di dalam paket</p>
                                        </div>
                                    </div>
                                </div>
                            </article>

                            @if ($bundles->count() > 1)
                                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-1">
                                    @foreach ($bundles->skip(1) as $bundle)
                                        <a href="{{ route('bundles.show', $bundle) }}" class="group flex min-h-52 flex-col justify-between overflow-hidden rounded-3xl border border-white/15 bg-white/10 p-6 transition hover:-translate-y-1 hover:border-white/30 hover:bg-white/[0.14] hover:shadow-xl">
                                            <div>
                                                <div class="flex items-start justify-between gap-4">
                                                    <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-bold text-white/80">{{ $bundle->courses->count() }} course</span>
                                                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-navy transition group-hover:bg-bass-gold" aria-hidden="true">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"/></svg>
                                                    </span>
                                                </div>
                                                <h3 class="mt-5 line-clamp-2 text-xl font-extrabold leading-tight text-white">{{ $bundle->title }}</h3>
                                                <p class="mt-2 line-clamp-1 text-sm text-white/55">{{ $bundle->courses->take(2)->pluck('title')->join(' + ') }}</p>
                                            </div>
                                            <div class="mt-6 flex items-end justify-between gap-4 border-t border-white/10 pt-4">
                                                <div>
                                                    <p class="text-xs text-white/45 line-through">{{ $bundle->original_price_label }}</p>
                                                    <p class="mt-0.5 text-xl font-extrabold text-white">{{ $bundle->price_label }}</p>
                                                </div>
                                                @if ($bundle->savings() > 0)
                                                    <span class="rounded-lg bg-bass-gold px-2.5 py-1.5 text-xs font-extrabold text-navy">Hemat {{ $bundle->savings_label }}</span>
                                                @endif
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </section>
            @endif

            @if ($learningPaths->isNotEmpty())
                <section id="jalur-belajar" class="border-y border-slate-200 bg-white py-16 sm:py-20">
                    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"><div class="max-w-2xl"><p class="text-xs font-extrabold uppercase tracking-[0.2em] text-bass-red">Belajar terarah</p><h2 class="mt-3 text-3xl font-extrabold tracking-tight text-navy sm:text-4xl">Jalur menuju kompetensi Anda</h2><p class="mt-3 text-slate-600">Rangkaian course dalam urutan yang disarankan, tanpa mengunci pilihan belajar Anda.</p></div><div class="mt-9 grid gap-5 md:grid-cols-3">@foreach ($learningPaths as $learningPath)<a href="{{ route('learning-paths.show', $learningPath) }}" class="group flex flex-col rounded-2xl border border-slate-200 bg-[#fafbfe] p-6 transition hover:-translate-y-1 hover:border-bass-red/30 hover:bg-white hover:shadow-card-hover"><div class="flex items-center justify-between"><span class="rounded-full bg-navy px-3 py-1 text-xs font-bold text-white">{{ $learningPath->courses->count() }} langkah</span><span class="text-xl text-slate-300 group-hover:text-bass-red">&rarr;</span></div><h3 class="mt-5 text-xl font-extrabold text-navy group-hover:text-bass-red">{{ $learningPath->title }}</h3>@if ($learningPath->short_description)<p class="mt-2 line-clamp-3 text-sm leading-6 text-slate-600">{{ $learningPath->short_description }}</p>@endif<div class="mt-6 border-t border-slate-200 pt-4 text-xs font-semibold text-slate-500">Mulai dari {{ $learningPath->courses->first()->title }}</div></a>@endforeach</div></div>
                </section>
            @endif

            <section id="program" class="scroll-mt-20 py-16 sm:py-20">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
                        <div>
                            <p class="text-xs font-extrabold uppercase tracking-[0.2em] text-bass-red">Pelatihan BASS</p>
                            <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-navy sm:text-4xl">Program terbaru untuk Anda</h2>
                            <p class="mt-3 max-w-2xl text-slate-600">Pilih program yang sesuai dengan kebutuhan pengembangan kompetensi dan karier Anda.</p>
                        </div>
                        <a href="{{ route('shop.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-bass-red hover:text-bass-red-hover">
                            Lihat semua program
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"/></svg>
                        </a>
                    </div>

                    @if ($courses->isNotEmpty())
                        <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach ($courses as $course)
                                <a href="{{ route('shop.show', $course) }}" class="group flex min-w-0 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card transition duration-200 hover:-translate-y-1 hover:border-slate-300 hover:shadow-card-hover">
                                    <div class="relative aspect-[16/10] overflow-hidden bg-slate-100">
                                        @if ($course->thumbnail)
                                            <img src="{{ asset('storage/' . $course->thumbnail) }}" alt="{{ $course->title }}" loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                        @else
                                            <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-navy via-navy-light to-bass-red">
                                                <svg class="h-12 w-12 text-white/75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                            </div>
                                        @endif
                                        <span class="absolute left-3 top-3 rounded-full bg-white/95 px-2.5 py-1 text-[11px] font-extrabold uppercase tracking-wide text-bass-red shadow-sm">Pelatihan</span>
                                    </div>
                                    <div class="flex flex-1 flex-col p-5">
                                        <p class="text-xs font-semibold text-slate-500">{{ $course->instructors->pluck('name')->join(', ') ?: 'Instruktur BASS' }}</p>
                                        <h3 class="mt-2 line-clamp-2 text-base font-bold leading-6 text-navy transition group-hover:text-bass-red">{{ $course->title }}</h3>
                                        @if ($course->short_description)
                                            <p class="mt-2 line-clamp-2 text-sm leading-5 text-slate-500">{{ $course->short_description }}</p>
                                        @endif
                                        <div class="mt-auto flex items-end justify-between gap-3 border-t border-slate-100 pt-4 {{ $course->short_description ? 'mt-5' : 'mt-8' }}">
                                            <span class="text-xs font-medium text-slate-500">{{ $course->lessons_count }} pelajaran</span>
                                            <span class="text-base font-extrabold text-navy">{{ $course->price_label }}</span>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="mt-10 rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center">
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-bass-red-soft text-bass-red">
                                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                            </div>
                            <h3 class="mt-4 text-lg font-bold text-navy">Program sedang dipersiapkan</h3>
                            <p class="mt-2 text-sm text-slate-500">Program pelatihan terbaru akan segera tersedia di katalog.</p>
                        </div>
                    @endif
                </div>
            </section>

            <section id="keunggulan" class="scroll-mt-20 bg-white py-16 sm:py-20">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-3xl text-center">
                        <p class="text-xs font-extrabold uppercase tracking-[0.2em] text-bass-red">Pengalaman belajar lengkap</p>
                        <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-navy sm:text-4xl">Lebih dari sekadar menonton materi</h2>
                        <p class="mt-4 text-slate-600">Satu platform untuk belajar, berlatih, mendapatkan umpan balik, dan memantau perkembangan Anda.</p>
                    </div>

                    <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        @php
                            $benefits = [
                                ['Materi terstruktur', 'Kurikulum tersusun dalam pelajaran dan materi yang mudah diikuti.', 'M4 19.5A2.5 2.5 0 0 1 6.5 17H20M4 14.5A2.5 2.5 0 0 1 6.5 12H20M4 9.5A2.5 2.5 0 0 1 6.5 7H20M4 4.5A2.5 2.5 0 0 1 6.5 2H20'],
                                ['Asesmen dan feedback', 'Uji pemahaman melalui kuis, tugas, dan penilaian dari instruktur.', 'M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
                                ['Belajar lintas perangkat', 'Akses pembelajaran melalui web maupun aplikasi mobile.', 'M9.75 17 9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2Z'],
                                ['Bukti kompetensi', 'Dapatkan sertifikat setelah memenuhi persyaratan program.', 'M16.5 18.75h-9m9 0a3.75 3.75 0 0 0 3.75-3.75V6a3.75 3.75 0 0 0-3.75-3.75h-9A3.75 3.75 0 0 0 3.75 6v9a3.75 3.75 0 0 0 3.75 3.75m9 0v2.625c0 .621-.504 1.125-1.125 1.125h-6.75A1.125 1.125 0 0 1 6.5 21.375V18.75'],
                            ];
                        @endphp
                        @foreach ($benefits as [$title, $description, $icon])
                            <article class="rounded-2xl border border-slate-200 bg-[#fafbfe] p-6 transition hover:border-bass-red/30 hover:bg-white hover:shadow-card-hover">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-bass-red-soft text-bass-red">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="{{ $icon }}"/></svg>
                                </div>
                                <h3 class="mt-5 text-lg font-bold text-navy">{{ $title }}</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $description }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>

            <section id="cara-belajar" class="scroll-mt-20 py-16 sm:py-20">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="overflow-hidden rounded-3xl bg-navy px-6 py-10 shadow-2xl shadow-navy/15 sm:px-10 lg:grid lg:grid-cols-[.8fr_1.2fr] lg:gap-14 lg:px-14 lg:py-14">
                        <div>
                            <p class="text-xs font-extrabold uppercase tracking-[0.2em] text-bass-gold">Mulai dengan mudah</p>
                            <h2 class="mt-4 text-3xl font-extrabold leading-tight text-white sm:text-4xl">Dari memilih program hingga membuktikan kompetensi.</h2>
                            <p class="mt-5 text-sm leading-6 text-white/65 sm:text-base">Proses belajar dirancang jelas agar Anda dapat fokus pada keterampilan yang ingin dikuasai.</p>
                            <a href="{{ route('shop.index') }}" class="mt-8 inline-flex min-h-12 items-center justify-center rounded-xl bg-bass-red px-6 text-sm font-bold text-white transition hover:bg-bass-red-hover">Jelajahi Katalog</a>
                        </div>

                        <ol class="mt-10 space-y-3 lg:mt-0">
                            @foreach ([
                                ['Pilih program', 'Temukan pelatihan yang sesuai dengan kebutuhan Anda.'],
                                ['Selesaikan pendaftaran', 'Pilih metode pembayaran dan selesaikan transaksi dengan aman.'],
                                ['Mulai belajar', 'Akses materi, asesmen, diskusi, dan umpan balik dalam satu platform.'],
                                ['Tuntaskan program', 'Penuhi seluruh persyaratan untuk memperoleh sertifikat.'],
                            ] as $index => [$title, $description])
                                <li class="flex gap-4 rounded-2xl border border-white/10 bg-white/10 p-4 sm:p-5">
                                    <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl {{ $index === 0 ? 'bg-bass-red text-white' : 'bg-white/10 text-bass-gold' }} text-sm font-extrabold">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                    <div>
                                        <h3 class="font-bold text-white">{{ $title }}</h3>
                                        <p class="mt-1 text-sm leading-5 text-white/60">{{ $description }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            </section>

            <section class="bg-white py-16 sm:py-20">
                <div class="mx-auto max-w-5xl px-4 text-center sm:px-6 lg:px-8">
                    <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-bass-red-soft text-bass-red">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 14.25c3.728 0 6.75-2.015 6.75-4.5S15.728 5.25 12 5.25 5.25 7.265 5.25 9.75s3.022 4.5 6.75 4.5Zm0 0v4.5m-3-1.5 3 1.5 3-1.5M3.75 9.75v6"/></svg>
                    </span>
                    <h2 class="mt-6 text-3xl font-extrabold tracking-tight text-navy sm:text-4xl">Siap meningkatkan kompetensi Anda?</h2>
                    <p class="mx-auto mt-4 max-w-2xl text-slate-600">Pilih program yang tepat, mulai belajar, dan bangun langkah berikutnya untuk karier Anda.</p>
                    <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                        <a href="{{ route('shop.index') }}" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-bass-red px-7 text-sm font-bold text-white transition hover:bg-bass-red-hover">Lihat Semua Program</a>
                        @guest
                            <a href="{{ route('register') }}" class="inline-flex min-h-12 items-center justify-center rounded-xl border border-slate-300 bg-white px-7 text-sm font-bold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50">Buat Akun</a>
                        @endguest
                    </div>
                </div>
            </section>
        </main>

        <footer class="bg-[#111b2c] text-white">
            <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 md:grid-cols-3 lg:px-8">
                <div>
                    <img src="{{ asset('images/logo.png') }}" alt="BASS Academy" class="h-12 w-auto rounded bg-white px-2">
                    <p class="mt-4 max-w-sm text-sm leading-6 text-white/60">Platform pelatihan profesional untuk membantu Anda mengembangkan kompetensi yang relevan.</p>
                </div>
                <div>
                    <h2 class="text-sm font-bold">Jelajahi</h2>
                    <div class="mt-4 space-y-3 text-sm text-white/60">
                        <a href="{{ route('shop.index') }}" class="block hover:text-white">Katalog Program</a>
                        <a href="#keunggulan" class="block hover:text-white">Keunggulan Platform</a>
                        <a href="#cara-belajar" class="block hover:text-white">Cara Belajar</a>
                    </div>
                </div>
                <div>
                    <h2 class="text-sm font-bold">Akun</h2>
                    <div class="mt-4 space-y-3 text-sm text-white/60">
                        @auth
                            <a href="{{ route('dashboard') }}" class="block hover:text-white">Dashboard</a>
                            <a href="{{ route('checkout.index') }}" class="block hover:text-white">Pesanan Saya</a>
                        @else
                            <a href="{{ route('login') }}" class="block hover:text-white">Masuk</a>
                            <a href="{{ route('register') }}" class="block hover:text-white">Daftar</a>
                        @endauth
                    </div>
                </div>
            </div>
            <div class="border-t border-white/10">
                <div class="mx-auto max-w-7xl px-4 py-5 text-center text-xs text-white/40 sm:px-6 lg:px-8">
                    &copy; {{ date('Y') }} {{ config('app.name', 'BASS Academy') }}. All rights reserved.
                </div>
            </div>
        </footer>
    </div>
</body>
</html>
