@inject('adminSidebarData', 'App\Services\AdminSidebarData')
@php
    $pendingVerifications = $adminSidebarData->pendingVerifications();
    $pendingRefunds = $adminSidebarData->pendingRefunds();
@endphp

<div x-show="adminSidebarOpen" x-cloak x-transition.opacity @click="adminSidebarOpen = false" class="fixed inset-0 z-40 bg-navy/55 backdrop-blur-sm lg:hidden" aria-hidden="true"></div>

<aside data-admin-sidebar
       x-data="{ adminSidebarCollapsed: false }"
       x-show="adminSidebarOpen"
       x-cloak
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="-translate-x-full"
       x-transition:enter-end="translate-x-0"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="translate-x-0"
       x-transition:leave-end="-translate-x-full"
       @keydown.escape.window="adminSidebarOpen = false"
       class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-navy text-white shadow-2xl lg:hidden">
    <div class="flex min-h-28 items-center justify-between border-b border-white/10 bg-white px-4">
        <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center rounded-lg focus-visible:ring-2 focus-visible:ring-bass-red" aria-label="Dashboard admin">
            <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" class="max-h-20 w-auto">
        </a>
        <button type="button" @click="adminSidebarOpen = false" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-navy hover:bg-gray-100" aria-label="Tutup menu admin">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <nav class="min-h-0 flex-1 overflow-y-auto scrollbar-thin" aria-label="Navigasi admin" @click="adminSidebarOpen = false">
        @include('layouts.partials.admin-sidebar-links')
    </nav>
</aside>

<aside data-admin-sidebar
       class="admin-sidebar-desktop sticky top-0 hidden h-screen shrink-0 flex-col bg-navy text-white transition-[width] duration-200 lg:flex"
       :class="adminSidebarCollapsed ? 'w-20' : 'w-72'">
    <div class="relative flex items-center justify-center border-b border-gray-200 bg-white px-3 transition-[height] duration-200" :class="adminSidebarCollapsed ? 'h-20' : 'h-40'">
        <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center justify-center rounded-lg focus-visible:ring-2 focus-visible:ring-bass-red" aria-label="Dashboard admin">
            <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" class="w-auto transition-all" :class="adminSidebarCollapsed ? 'max-h-12 max-w-14' : 'max-h-28 max-w-64'">
        </a>
        <button type="button" @click="toggleAdminSidebar" class="absolute -right-3 top-1/2 inline-flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-full border border-gray-200 bg-white text-navy shadow-md hover:bg-gray-50" :aria-label="adminSidebarCollapsed ? 'Perluas sidebar admin' : 'Ciutkan sidebar admin'">
            <svg class="h-5 w-5 transition-transform" :class="adminSidebarCollapsed ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 18-6-6 6-6"/></svg>
        </button>
    </div>
    <nav class="min-h-0 flex-1 overflow-y-auto overflow-x-hidden scrollbar-thin" aria-label="Navigasi admin">
        @include('layouts.partials.admin-sidebar-links')
    </nav>
</aside>
