<div class="space-y-6 px-3 py-5">
    <section x-data="{ open: true }">
        <button type="button" x-show="!adminSidebarCollapsed" @click="open = !open" class="mb-2 flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-xs font-bold uppercase tracking-[0.16em] text-slate-400 hover:bg-white/10 hover:text-white"><span>Utama</span><svg class="h-4 w-4 transition" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/></svg></button>
        <div x-show="adminSidebarCollapsed || open" x-collapse class="space-y-1" :class="adminSidebarCollapsed ? '' : 'pl-3'">
        @include('layouts.partials.admin-sidebar-link', [
            'href' => route('dashboard'),
            'label' => 'Dashboard',
            'active' => request()->routeIs('dashboard'),
            'badge' => 0,
            'icon' => 'M3 10.5 12 3l9 7.5M5 9.5V21h5v-6h4v6h5V9.5',
        ])
        </div>
    </section>

    <section x-data="{ open: true }">
        <button type="button" x-show="!adminSidebarCollapsed" @click="open = !open" class="mb-2 flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-xs font-bold uppercase tracking-[0.16em] text-slate-400 hover:bg-white/10 hover:text-white"><span>Landing Page</span><svg class="h-4 w-4 transition" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/></svg></button>
        <div x-show="adminSidebarCollapsed || open" x-collapse class="space-y-1" :class="adminSidebarCollapsed ? '' : 'pl-3'">
            @include('layouts.partials.admin-sidebar-link', [
                'href' => route('shop.index'),
                'label' => 'Katalog Course',
                'active' => request()->routeIs('shop.*'),
                'badge' => 0,
                'icon' => 'M16 11V7a4 4 0 0 0-8 0v4M5 9h14l1 12H4L5 9Z',
            ])
        </div>
    </section>

    @canany(['view courses', 'view progress reports', 'view instructor analytics', 'manage course taxonomy', 'manage bundles', 'manage learning paths'])
    <section x-data="{ open: true }">
        <button type="button" x-show="!adminSidebarCollapsed" @click="open = !open" class="mb-2 flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-xs font-bold uppercase tracking-[0.16em] text-slate-400 hover:bg-white/10 hover:text-white"><span>Program</span><svg class="h-4 w-4 transition" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/></svg></button>
        <div x-show="adminSidebarCollapsed || open" x-collapse class="space-y-1" :class="adminSidebarCollapsed ? '' : 'pl-3'">
            @can('view courses')
                @include('layouts.partials.admin-sidebar-link', ['href' => route('courses.index'), 'label' => 'Kelola Kursus', 'active' => request()->routeIs('courses.*'), 'badge' => 0, 'icon' => 'M4 5.5A2.5 2.5 0 0 1 6.5 3H11v16H6.5A2.5 2.5 0 0 0 4 21.5v-16Zm16 0A2.5 2.5 0 0 0 17.5 3H13v16h4.5a2.5 2.5 0 0 1 2.5 2.5v-16Z'])
            @endcan
            @can('view progress reports')
                @include('layouts.partials.admin-sidebar-link', ['href' => route('eo.courses.index'), 'label' => 'Pemantauan Kursus', 'active' => request()->routeIs('eo.*'), 'badge' => 0, 'icon' => 'M4 19V9m5 10V5m5 14v-7m5 7V3'])
            @endcan
            @canany(['view instructor analytics', 'view progress reports'])
                @include('layouts.partials.admin-sidebar-link', ['href' => route('instructor-analytics.index'), 'label' => 'Analytics Instruktur', 'active' => request()->routeIs('instructor-analytics.*'), 'badge' => 0, 'icon' => 'M3 3v18h18M7 16l4-5 3 2 5-7'])
            @endcanany
            @can('manage course taxonomy')
                @include('layouts.partials.admin-sidebar-link', ['href' => route('admin.categories.index'), 'label' => 'Kategori Course', 'active' => request()->routeIs('admin.categories.*'), 'badge' => 0, 'icon' => 'M4 4h6v6H4V4Zm10 0h6v6h-6V4ZM4 14h6v6H4v-6Zm10 0h6v6h-6v-6Z'])
                @include('layouts.partials.admin-sidebar-link', ['href' => route('admin.tags.index'), 'label' => 'Tag Course', 'active' => request()->routeIs('admin.tags.*'), 'badge' => 0, 'icon' => 'M20 13 13 20l-9-9V4h7l9 9ZM8.5 8.5h.01'])
            @endcan
            @can('manage bundles')
                @include('layouts.partials.admin-sidebar-link', ['href' => route('admin.bundles.index'), 'label' => 'Manajemen Bundle', 'active' => request()->routeIs('admin.bundles.*'), 'badge' => 0, 'icon' => 'M21 8 12 3 3 8l9 5 9-5ZM3 12l9 5 9-5M3 16l9 5 9-5'])
            @endcan
            @can('manage learning paths')
                @include('layouts.partials.admin-sidebar-link', ['href' => route('admin.learning-paths.index'), 'label' => 'Learning Path', 'active' => request()->routeIs('admin.learning-paths.*'), 'badge' => 0, 'icon' => 'M5 19V8m0 0 4 4M5 8l4-4m10 1v11m0 0-4-4m4 4-4 4M9 12h6'])
            @endcan
        </div>
    </section>
    @endcanany

    @canany(['manage users', 'manage roles', 'view announcements'])
    <section x-data="{ open: true }">
        <button type="button" x-show="!adminSidebarCollapsed" @click="open = !open" class="mb-2 flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-xs font-bold uppercase tracking-[0.16em] text-slate-400 hover:bg-white/10 hover:text-white"><span>Pengguna</span><svg class="h-4 w-4 transition" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/></svg></button>
        <div x-show="adminSidebarCollapsed || open" x-collapse class="space-y-1" :class="adminSidebarCollapsed ? '' : 'pl-3'">
            @can('manage users')
                @include('layouts.partials.admin-sidebar-link', ['href' => route('admin.users.index'), 'label' => 'Manajemen Pengguna', 'active' => request()->routeIs('admin.users.*'), 'badge' => 0, 'icon' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm13 10v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75'])
                @include('layouts.partials.admin-sidebar-link', ['href' => route('admin.participants.index'), 'label' => 'Analitik Peserta', 'active' => request()->routeIs('admin.participants.*'), 'badge' => 0, 'icon' => 'M4 19V9m5 10V5m5 14v-7m5 7V3'])
            @endcan
            @can('manage roles')
                @include('layouts.partials.admin-sidebar-link', ['href' => route('admin.roles.index'), 'label' => 'Manajemen Peran', 'active' => request()->routeIs('admin.roles.*'), 'badge' => 0, 'icon' => 'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Zm-3-10 2 2 4-4'])
            @endcan
            @can('view announcements')
                @include('layouts.partials.admin-sidebar-link', ['href' => route('admin.announcements.index'), 'label' => 'Pengumuman', 'active' => request()->routeIs('admin.announcements.*'), 'badge' => 0, 'icon' => 'M3 11v2h4l9 4V7l-9 4H3Zm4 2 2 7h3l-2-6'])
            @endcan
        </div>
    </section>
    @endcanany

    @canany(['view certificate templates', 'view certificate management', 'grade essays', 'grade quizzes', 'admin-only'])
    <section x-data="{ open: true }">
        <button type="button" x-show="!adminSidebarCollapsed" @click="open = !open" class="mb-2 flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-xs font-bold uppercase tracking-[0.16em] text-slate-400 hover:bg-white/10 hover:text-white"><span>Evaluasi</span><svg class="h-4 w-4 transition" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/></svg></button>
        <div x-show="adminSidebarCollapsed || open" x-collapse class="space-y-1" :class="adminSidebarCollapsed ? '' : 'pl-3'">
            @can('view certificate templates')
                @include('layouts.partials.admin-sidebar-link', ['href' => route('admin.certificate-templates.index'), 'label' => 'Template Sertifikat', 'active' => request()->routeIs('admin.certificate-templates.*'), 'badge' => 0, 'icon' => 'M6 3h12v18l-6-3-6 3V3Zm3 5h6M9 12h6'])
            @endcan
            @can('view certificate management')
                @include('layouts.partials.admin-sidebar-link', ['href' => route('certificate-management.index'), 'label' => 'Manajemen Sertifikat', 'active' => request()->routeIs('certificate-management.*'), 'badge' => 0, 'icon' => 'M12 15a6 6 0 1 0 0-12 6 6 0 0 0 0 12Zm-3 0-1 7 4-2 4 2-1-7'])
            @endcan
            @canany(['grade essays', 'grade quizzes'])
                @include('layouts.partials.admin-sidebar-link', ['href' => route('admin.auto-grade.index'), 'label' => 'Penilaian Otomatis', 'active' => request()->routeIs('admin.auto-grade.*'), 'badge' => 0, 'icon' => 'm4 13 4 4L20 5M4 5h7M4 9h4M14 17h6'])
            @endcanany
            @include('layouts.partials.admin-sidebar-link', ['href' => route('admin.force-complete.index'), 'label' => 'Force Complete', 'active' => request()->routeIs('admin.force-complete.*'), 'badge' => 0, 'icon' => 'M22 11.1V12a10 10 0 1 1-5.9-9.1M22 4 12 14l-3-3'])
        </div>
    </section>
    @endcanany

    @canany(['manage coupons', 'super-admin-only'])
    <section x-data="{ open: true }">
        <button type="button" x-show="!adminSidebarCollapsed" @click="open = !open" class="mb-2 flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-xs font-bold uppercase tracking-[0.16em] text-slate-400 hover:bg-white/10 hover:text-white"><span>Transaksi</span><svg class="h-4 w-4 transition" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/></svg></button>
        <div x-show="adminSidebarCollapsed || open" x-collapse class="space-y-1" :class="adminSidebarCollapsed ? '' : 'pl-3'">
            @can('manage coupons')
                @include('layouts.partials.admin-sidebar-link', ['href' => route('admin.coupons.index'), 'label' => 'Manajemen Kupon', 'active' => request()->routeIs('admin.coupons.*'), 'badge' => 0, 'icon' => 'M3 9a3 3 0 0 0 0 6v4h18v-4a3 3 0 0 0 0-6V9a3 3 0 0 0 0 6V5H3v4Zm9-1v2m0 4v2'])
            @endcan
            @can('super-admin-only')
                @include('layouts.partials.admin-sidebar-link', ['href' => route('admin.payment-verifications.index'), 'label' => 'Verifikasi Pembayaran', 'active' => request()->routeIs('admin.payment-verifications.*'), 'badge' => $pendingVerifications, 'icon' => 'M3 7h18v12H3V7Zm0 4h18M7 15h4'])
                @include('layouts.partials.admin-sidebar-link', ['href' => route('admin.refunds.index'), 'label' => 'Manajemen Refund', 'active' => request()->routeIs('admin.refunds.*'), 'badge' => $pendingRefunds, 'icon' => 'M3 12a9 9 0 1 0 3-6.7L3 8m0-5v5h5M8 12h8m-3-3 3 3-3 3'])
            @endcan
        </div>
    </section>
    @endcanany

    @canany(['view files', 'view activity logs', 'manage users', 'manage roles'])
    <section x-data="{ open: true }">
        <button type="button" x-show="!adminSidebarCollapsed" @click="open = !open" class="mb-2 flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-xs font-bold uppercase tracking-[0.16em] text-slate-400 hover:bg-white/10 hover:text-white"><span>Sistem</span><svg class="h-4 w-4 transition" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/></svg></button>
        <div x-show="adminSidebarCollapsed || open" x-collapse class="space-y-1" :class="adminSidebarCollapsed ? '' : 'pl-3'">
            @can('view files')
                @include('layouts.partials.admin-sidebar-link', ['href' => route('file-control.index'), 'label' => 'File Manager', 'active' => request()->routeIs('file-control.*'), 'badge' => 0, 'icon' => 'M3 6h7l2 2h9v11H3V6Z'])
            @endcan
            @can('view activity logs')
                @include('layouts.partials.admin-sidebar-link', ['href' => route('activity-logs.index'), 'label' => 'Log Aktivitas', 'active' => request()->routeIs('activity-logs.*'), 'badge' => 0, 'icon' => 'M12 8v4l3 2M3.05 11A9 9 0 1 0 5 5.7L3 8m0-5v5h5'])
            @endcan
            @canany(['manage users', 'manage roles'])
                @include('layouts.partials.admin-sidebar-link', ['href' => route('admin.tools.index'), 'label' => 'Tools Admin', 'active' => request()->routeIs('admin.tools.*'), 'badge' => 0, 'icon' => 'M14.7 6.3a4 4 0 0 0-5 5L3 18l3 3 6.7-6.7a4 4 0 0 0 5-5l-3 3-3-3 3-3Z'])
            @endcanany
        </div>
    </section>
    @endcanany
</div>
