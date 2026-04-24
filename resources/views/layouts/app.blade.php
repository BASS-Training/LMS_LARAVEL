<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#ffffff">

    <link rel="icon" href="{{ asset('images/favicon.ico') }}" type="image/x-icon">

    <title>{{ config('app.name', 'LMS App') }}@hasSection('title') &ndash; @yield('title')@endif</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">

    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-50 text-gray-900">

{{-- ══════════════════════════════════════════════════════
     ROOT WRAPPER — provides Alpine scope for mobile menu
     ══════════════════════════════════════════════════════ --}}
<div x-data="{ mobileOpen: false }" class="min-h-screen flex flex-col">

    {{-- ══════════════ STICKY NAVBAR ══════════════ --}}
    <nav class="sticky top-0 z-40 bg-white border-b border-gray-200 shadow-sm"
         :class="{ 'shadow-md': mobileOpen }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">

                {{-- ── LEFT: Logo + desktop links ── --}}
                <div class="flex items-center min-w-0">
                    {{-- Logo --}}
                    <a href="{{ route('dashboard') }}" class="flex-shrink-0 mr-6 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-bass-red rounded-md" aria-label="Kembali ke Dashboard">
                        <img src="{{ asset('images/logo.png') }}"
                             alt="{{ config('app.name') }} Logo"
                             class="h-12 w-auto"
                             loading="eager">
                    </a>

                    {{-- Desktop nav links — hidden on mobile --}}
                    <div class="hidden md:flex md:items-center md:gap-1">
                        @auth
                        <a href="{{ route('dashboard') }}"
                           class="nav-link-custom {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            Dashboard
                        </a>
                        @endauth

                        @can('view courses')
                        <a href="{{ route('courses.index') }}"
                           class="nav-link-custom {{ request()->routeIs('courses.*') ? 'active' : '' }}">
                            Kelola Kursus
                        </a>
                        @endcan

                        @can('view progress reports')
                        <a href="{{ route('eo.courses.index') }}"
                           class="nav-link-custom {{ request()->routeIs('eo.*') ? 'active' : '' }}">
                            Pemantauan
                        </a>
                        <a href="{{ route('instructor-analytics.index') }}"
                           class="nav-link-custom {{ request()->routeIs('instructor-analytics.*') ? 'active' : '' }}">
                            Analytics
                        </a>
                        @endcan

                        {{-- Admin dropdown --}}
                        @canany(['manage users','manage roles','view certificate templates','view activity logs','view certificate analytics','view certificate management'])
                        <div x-data="{ adminOpen: false }" class="relative">
                            <button @click="adminOpen = !adminOpen"
                                    @keydown.escape.window="adminOpen = false"
                                    :aria-expanded="adminOpen"
                                    aria-haspopup="true"
                                    class="dropdown-trigger-custom">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                                </svg>
                                <span>Admin</span>
                                <svg class="w-3.5 h-3.5 transition-transform duration-200"
                                     :class="{ 'rotate-180': adminOpen }"
                                     fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </button>

                            <div x-show="adminOpen"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-100"
                                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                 x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                                 @click.outside="adminOpen = false"
                                 class="absolute left-0 mt-2 w-64 rounded-xl bg-white shadow-xl ring-1 ring-black/5 divide-y divide-gray-100 z-50"
                                 style="display:none"
                                 role="menu">
                                <div class="py-1" role="none">
                                    <a href="{{ route('admin.users.index') }}"    class="dropdown-item-custom" role="menuitem">Manajemen Pengguna</a>
                                    <a href="{{ route('admin.roles.index') }}"    class="dropdown-item-custom" role="menuitem">Manajemen Peran</a>
                                    <a href="{{ route('admin.announcements.index') }}" class="dropdown-item-custom" role="menuitem">Manajemen Pengumuman</a>
                                    <a href="{{ route('admin.participants.index') }}" class="dropdown-item-custom" role="menuitem">Analitik Peserta</a>
                                </div>
                                <div class="py-1" role="none">
                                    <a href="{{ route('admin.certificate-templates.index') }}" class="dropdown-item-custom" role="menuitem">Certificate Template</a>
                                    <a href="{{ route('certificate-management.index') }}"      class="dropdown-item-custom" role="menuitem">Manajemen Sertifikat</a>
                                </div>
                                <div class="py-1" role="none">
                                    <a href="{{ route('admin.auto-grade.index') }}"    class="dropdown-item-custom" role="menuitem">Penilaian Otomatis</a>
                                    <a href="{{ route('admin.force-complete.index') }}" class="dropdown-item-custom" role="menuitem">Force Complete Konten</a>
                                </div>
                                <div class="py-1" role="none">
                                    <a href="{{ route('file-control.index') }}"    class="dropdown-item-custom" role="menuitem">File Manager</a>
                                    <a href="{{ route('activity-logs.index') }}"   class="dropdown-item-custom" role="menuitem">Log Aktivitas</a>
                                </div>
                            </div>
                        </div>
                        @endcanany
                    </div>
                </div>

                {{-- ── RIGHT: User controls + hamburger ── --}}
                <div class="flex items-center gap-2">

                    @auth
                    {{-- User dropdown — desktop --}}
                    <div x-data="{ userOpen: false }" class="relative hidden sm:block">
                        <button @click="userOpen = !userOpen"
                                @keydown.escape.window="userOpen = false"
                                :aria-expanded="userOpen"
                                class="flex items-center gap-2 min-h-[44px] px-3 py-1.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-bass-red">
                            {{-- Avatar initials --}}
                            <span class="w-8 h-8 rounded-full bg-gradient-to-br from-bass-red to-red-700 flex items-center justify-center text-white text-xs font-bold flex-shrink-0" aria-hidden="true">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </span>
                            <span class="hidden lg:block max-w-[120px] truncate">{{ Auth::user()->name }}</span>
                            <svg class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200 flex-shrink-0"
                                 :class="{ 'rotate-180': userOpen }"
                                 fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>

                        <div x-show="userOpen"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                             x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                             @click.outside="userOpen = false"
                             class="absolute right-0 mt-2 w-52 rounded-xl bg-white shadow-xl ring-1 ring-black/5 z-50"
                             style="display:none"
                             role="menu">
                            <div class="px-4 py-3 border-b border-gray-100">
                                <p class="text-xs text-gray-500">Masuk sebagai</p>
                                <p class="text-sm font-semibold text-gray-900 truncate">{{ Auth::user()->name }}</p>
                                <p class="text-xs text-gray-500 truncate">{{ Auth::user()->email }}</p>
                            </div>
                            <div class="py-1" role="none">
                                <a href="{{ route('profile.edit') }}"
                                   class="dropdown-item-custom flex items-center gap-2" role="menuitem">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    Profil Saya
                                </a>
                            </div>
                            <div class="py-1 border-t border-gray-100" role="none">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                            class="dropdown-item-custom w-full text-left flex items-center gap-2 text-red-600 hover:bg-red-50 hover:text-red-700"
                                            role="menuitem">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                        Keluar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="hidden sm:flex items-center gap-3">
                        <a href="{{ route('login') }}"    class="text-sm font-medium text-gray-700 hover:text-bass-red transition-colors">Masuk</a>
                        <a href="{{ route('register') }}" class="btn-primary text-sm py-2 px-4 min-h-[36px]">Daftar</a>
                    </div>
                    @endauth

                    {{-- Hamburger — visible on mobile --}}
                    <button @click="mobileOpen = !mobileOpen"
                            :aria-expanded="mobileOpen"
                            aria-controls="mobile-menu"
                            aria-label="Buka menu navigasi"
                            class="md:hidden inline-flex items-center justify-center w-11 h-11 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-bass-red">
                        {{-- Hamburger icon --}}
                        <svg x-show="!mobileOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        {{-- Close icon --}}
                        <svg x-show="mobileOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true" style="display:none">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- ══════════ MOBILE MENU PANEL ══════════ --}}
        <div id="mobile-menu"
             x-show="mobileOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="md:hidden border-t border-gray-200 bg-white overflow-y-auto max-h-[calc(100vh-4rem)] scrollbar-thin pb-safe"
             style="display:none"
             @click="mobileOpen = false">

            <div class="py-2 space-y-0.5">
                @auth
                <a href="{{ route('dashboard') }}"
                   class="responsive-nav-link-custom {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 21V12M16 21v-8"/></svg>
                    Dashboard
                </a>
                @endauth

                @can('view courses')
                <a href="{{ route('courses.index') }}"
                   class="responsive-nav-link-custom {{ request()->routeIs('courses.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    Kelola Kursus
                </a>
                @endcan

                @can('view progress reports')
                <a href="{{ route('eo.courses.index') }}"
                   class="responsive-nav-link-custom {{ request()->routeIs('eo.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    Pemantauan Kursus
                </a>
                <a href="{{ route('instructor-analytics.index') }}"
                   class="responsive-nav-link-custom {{ request()->routeIs('instructor-analytics.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Analytics Instruktur
                </a>
                @endcan

                @canany(['manage users','manage roles','view certificate templates','view activity logs','view certificate analytics','view certificate management'])
                <div class="pt-2 mt-1 border-t border-gray-100">
                    <p class="px-4 py-1.5 text-xs font-semibold text-gray-400 uppercase tracking-wider">Admin</p>
                    <a href="{{ route('admin.users.index') }}"    class="responsive-nav-link-custom {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">Manajemen Pengguna</a>
                    <a href="{{ route('admin.roles.index') }}"    class="responsive-nav-link-custom {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">Manajemen Peran</a>
                    <a href="{{ route('admin.announcements.index') }}" class="responsive-nav-link-custom">Manajemen Pengumuman</a>
                    <a href="{{ route('admin.participants.index') }}" class="responsive-nav-link-custom">Analitik Peserta</a>
                    <a href="{{ route('admin.certificate-templates.index') }}" class="responsive-nav-link-custom">Certificate Template</a>
                    <a href="{{ route('certificate-management.index') }}"  class="responsive-nav-link-custom">Manajemen Sertifikat</a>
                    <a href="{{ route('admin.auto-grade.index') }}"   class="responsive-nav-link-custom">Penilaian Otomatis</a>
                    <a href="{{ route('admin.force-complete.index') }}" class="responsive-nav-link-custom">Force Complete Konten</a>
                    <a href="{{ route('file-control.index') }}"   class="responsive-nav-link-custom">File Manager</a>
                    <a href="{{ route('activity-logs.index') }}"  class="responsive-nav-link-custom">Log Aktivitas</a>
                </div>
                @endcanany
            </div>

            {{-- Mobile user footer --}}
            @auth
            <div class="border-t border-gray-200 py-3 px-4 space-y-2">
                <div class="flex items-center gap-3">
                    <span class="w-9 h-9 rounded-full bg-gradient-to-br from-bass-red to-red-700 flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-900 truncate">{{ Auth::user()->name }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ Auth::user()->email }}</p>
                    </div>
                </div>
                <div class="flex gap-2 pt-1">
                    <a href="{{ route('profile.edit') }}"
                       class="flex-1 text-center min-h-[40px] flex items-center justify-center text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                        Profil
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="flex-1">
                        @csrf
                        <button type="submit"
                                class="w-full min-h-[40px] text-sm font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors">
                            Keluar
                        </button>
                    </form>
                </div>
            </div>
            @else
            <div class="border-t border-gray-200 py-3 px-4 flex gap-2">
                <a href="{{ route('login') }}"    class="flex-1 text-center min-h-[44px] flex items-center justify-center text-sm font-medium text-gray-700 border border-gray-300 rounded-lg">Masuk</a>
                <a href="{{ route('register') }}" class="flex-1 text-center min-h-[44px] flex items-center justify-center text-sm font-medium text-white bg-bass-red rounded-lg">Daftar</a>
            </div>
            @endauth
        </div>
    </nav>

    {{-- ══════════════ PAGE HEADER ══════════════ --}}
    @if(isset($header))
    <header class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-5">
            {{ $header }}
        </div>
    </header>
    @endif

    {{-- ══════════════ MAIN CONTENT ══════════════ --}}
    <main class="flex-1 min-w-0">
        @hasSection('content')
            @yield('content')
        @else
            {{ $slot }}
        @endif
    </main>

    {{-- ══════════════ FOOTER ══════════════ --}}
    <footer class="border-t border-gray-200 bg-white mt-auto py-4 pb-safe">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <p class="text-center text-xs text-gray-400">
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </p>
        </div>
    </footer>
</div>

<script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>

@stack('scripts')
</body>
</html>
