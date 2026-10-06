@php
    $sectionTitle = trim($__env->yieldContent('title'));
    $adminPageTitle = $sectionTitle !== '' ? $sectionTitle : match (true) {
        request()->routeIs('dashboard') => 'Dashboard Admin',
        request()->routeIs('courses.*') => 'Kelola Kursus',
        request()->routeIs('admin.users.*') => 'Manajemen Pengguna',
        request()->routeIs('admin.roles.*') => 'Manajemen Peran',
        request()->routeIs('admin.participants.*') => 'Analitik Peserta',
        request()->routeIs('admin.announcements.*') => 'Manajemen Pengumuman',
        request()->routeIs('admin.certificate-templates.*') => 'Template Sertifikat',
        request()->routeIs('certificate-management.*') => 'Manajemen Sertifikat',
        request()->routeIs('admin.auto-grade.*') => 'Penilaian Otomatis',
        request()->routeIs('admin.force-complete.*') => 'Force Complete',
        request()->routeIs('file-control.*') => 'File Manager',
        request()->routeIs('activity-logs.*') => 'Log Aktivitas',
        default => 'Panel Admin',
    };
@endphp

<header class="admin-topbar sticky top-0 z-30 flex h-16 items-stretch border-b border-gray-200 bg-gray-100 shadow-sm">
    <div class="flex min-w-0 flex-1 items-center gap-3 px-4 sm:px-6">
        <button type="button" @click="adminSidebarOpen = true" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg text-navy hover:bg-white lg:hidden" aria-label="Buka menu admin">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h7"/></svg>
        </button>
        <h1 class="truncate text-lg font-bold text-navy sm:text-2xl">{{ $adminPageTitle }}</h1>
    </div>

    <div x-data="{ profileOpen: false }" class="relative flex shrink-0">
        <button type="button" @click="profileOpen = !profileOpen" @keydown.escape.window="profileOpen = false" :aria-expanded="profileOpen" class="flex min-w-[76px] items-center justify-center gap-3 bg-[#656263] px-4 text-white transition hover:bg-[#555253] sm:min-w-[190px] sm:justify-between sm:px-6">
            <span class="flex items-center gap-3 min-w-0">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/15 text-sm font-bold">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                <span class="hidden max-w-[120px] truncate text-sm font-semibold sm:block">{{ Auth::user()->name }}</span>
            </span>
            <svg class="hidden h-4 w-4 transition-transform sm:block" :class="profileOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/></svg>
        </button>

        <div x-show="profileOpen" x-cloak x-transition @click.outside="profileOpen = false" class="absolute right-2 top-[calc(100%+8px)] z-50 w-56 overflow-hidden rounded-xl bg-white shadow-xl ring-1 ring-black/5">
            <div class="border-b border-gray-100 px-4 py-3"><p class="truncate text-sm font-semibold text-gray-900">{{ Auth::user()->name }}</p><p class="truncate text-xs text-gray-500">{{ Auth::user()->email }}</p></div>
            <a href="{{ route('profile.edit') }}" class="dropdown-item-custom">Profil Saya</a>
            <a href="{{ route('checkout.index') }}" class="dropdown-item-custom">Pesanan Saya</a>
            <form method="POST" action="{{ route('logout') }}" class="border-t border-gray-100">@csrf<button type="submit" class="dropdown-item-custom w-full text-left text-error">Keluar</button></form>
        </div>
    </div>
</header>
