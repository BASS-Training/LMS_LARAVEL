<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('meta_description', 'Pelatihan profesional BASS untuk meningkatkan kompetensi dan kesiapan karier Anda.')">
    <meta name="theme-color" content="#F7F3EA">
    <title>{{ config('app.name', 'BASS Academy') }}@hasSection('title') - @yield('title')@endif</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=fraunces:500,800|instrument-sans:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-[#f7f3ea] text-navy antialiased [font-family:'Instrument_Sans',sans-serif]">
    <div x-data="{ mobileOpen: false }" class="flex min-h-screen flex-col overflow-x-clip">
        <header class="sticky top-0 z-40 border-b-2 border-navy bg-[#f7f3ea]/95 backdrop-blur">
            <nav class="mx-auto flex h-16 w-full max-w-7xl items-center gap-5 px-4 sm:px-6 lg:px-8" aria-label="Navigasi utama">
                <a href="{{ route('welcome') }}" class="flex shrink-0 items-center" aria-label="BASS Academy beranda">
                    <img src="{{ asset('images/logo.png') }}" alt="BASS Academy" class="h-11 w-auto">
                </a>

                <div class="ml-auto hidden items-center gap-5 lg:flex">
                    <a href="{{ route('welcome') }}" class="text-sm font-semibold hover:text-bass-red {{ request()->routeIs('welcome') ? 'text-bass-red' : '' }}">Beranda</a>
                    <a href="{{ route('shop.index') }}" class="text-sm font-semibold hover:text-bass-red {{ request()->routeIs('shop.*') ? 'text-bass-red' : '' }}">Course</a>
                    <a href="{{ route('learning-paths.index') }}" class="text-sm font-semibold hover:text-bass-red {{ request()->routeIs('learning-paths.*') ? 'text-bass-red' : '' }}">Learning Path</a>
                    <a href="{{ route('bundles.index') }}" class="rounded-full px-4 py-1.5 text-sm font-bold {{ request()->routeIs('bundles.*') ? 'bg-bass-red text-white' : 'hover:text-bass-red' }}">Bundle</a>
                    <a href="{{ route('welcome') }}#cara-belajar" class="text-sm font-semibold hover:text-bass-red">Cara Belajar</a>
                    <a href="{{ route('welcome') }}#kontak" class="text-sm font-semibold hover:text-bass-red">Kontak</a>
                </div>

                <div class="ml-auto hidden items-center gap-3 sm:flex lg:ml-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-lg border-2 border-navy bg-white px-4 py-2 text-sm font-bold shadow-[3px_3px_0_#17243A] transition hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[1px_1px_0_#17243A]">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-bold hover:text-bass-red">Masuk</a>
                        <a href="{{ route('register') }}" class="rounded-lg border-2 border-navy bg-navy px-4 py-2 text-sm font-bold text-white shadow-[3px_3px_0_#DA1E1E] transition hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[1px_1px_0_#DA1E1E]">Daftar</a>
                    @endauth
                </div>

                <button type="button" @click="mobileOpen = !mobileOpen" :aria-expanded="mobileOpen" aria-controls="public-mobile-menu" class="ml-auto inline-flex h-11 w-11 items-center justify-center rounded-lg border-2 border-navy bg-white lg:hidden">
                    <span class="sr-only">Buka menu</span>
                    <svg x-show="!mobileOpen" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg x-show="mobileOpen" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </nav>

            <div id="public-mobile-menu" x-show="mobileOpen" x-cloak @click.outside="mobileOpen = false" class="border-t-2 border-navy bg-[#f7f3ea] px-4 py-4 lg:hidden">
                <div class="grid gap-1 text-sm font-semibold">
                    <a href="{{ route('welcome') }}" class="rounded-lg px-3 py-2.5 hover:bg-white">Beranda</a>
                    <a href="{{ route('shop.index') }}" class="rounded-lg px-3 py-2.5 hover:bg-white">Course</a>
                    <a href="{{ route('learning-paths.index') }}" class="rounded-lg px-3 py-2.5 hover:bg-white">Learning Path</a>
                    <a href="{{ route('bundles.index') }}" class="rounded-lg px-3 py-2.5 text-bass-red hover:bg-white">Bundle</a>
                    <a href="{{ route('welcome') }}#cara-belajar" class="rounded-lg px-3 py-2.5 hover:bg-white">Cara Belajar</a>
                    <a href="{{ route('welcome') }}#kontak" class="rounded-lg px-3 py-2.5 hover:bg-white">Kontak</a>
                </div>
                <div class="mt-3 grid grid-cols-2 gap-2 border-t border-navy/15 pt-4">
                    @auth
                        <a href="{{ route('dashboard') }}" class="col-span-2 inline-flex min-h-11 items-center justify-center rounded-lg bg-navy text-sm font-bold text-white">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border-2 border-navy bg-white text-sm font-bold">Masuk</a>
                        <a href="{{ route('register') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-bass-red text-sm font-bold text-white">Daftar</a>
                    @endauth
                </div>
            </div>
        </header>

        <main class="flex-1">
            @yield('content')
        </main>

        <footer class="border-t-2 border-navy bg-[#f7f3ea]">
            <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 md:grid-cols-[1.3fr_1fr_1fr] lg:px-8">
                <div><img src="{{ asset('images/logo.png') }}" alt="BASS Academy" class="h-12 w-auto"><p class="mt-4 max-w-sm text-sm leading-6 text-slate-600">Platform pelatihan profesional untuk membangun kompetensi yang relevan dan terukur.</p></div>
                <div><h2 class="font-bold">Jelajahi</h2><div class="mt-3 space-y-2 text-sm text-slate-600"><a href="{{ route('shop.index') }}" class="block hover:text-bass-red">Course</a><a href="{{ route('bundles.index') }}" class="block hover:text-bass-red">Bundle</a><a href="{{ route('learning-paths.index') }}" class="block hover:text-bass-red">Learning Path</a></div></div>
                <div><h2 class="font-bold">Akun</h2><div class="mt-3 space-y-2 text-sm text-slate-600">@auth<a href="{{ route('dashboard') }}" class="block hover:text-bass-red">Dashboard</a><a href="{{ route('checkout.index') }}" class="block hover:text-bass-red">Pesanan Saya</a>@else<a href="{{ route('login') }}" class="block hover:text-bass-red">Masuk</a><a href="{{ route('register') }}" class="block hover:text-bass-red">Daftar</a>@endauth</div></div>
            </div>
            <div class="border-t border-navy/20 px-4 py-5 text-center text-xs text-slate-500">&copy; {{ date('Y') }} PT Bintang Anugrah Surya Semesta · BASS Learning &amp; Development</div>
        </footer>
    </div>
    @stack('scripts')
</body>
</html>
