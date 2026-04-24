{{-- resources/views/dashboard/admin.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <h2 class="text-lg sm:text-xl font-semibold text-gray-800 leading-tight">
                Dashboard Admin
            </h2>
            <div class="flex items-center gap-3 text-sm text-gray-500">
                <span>{{ now()->translatedFormat('l, d F Y') }}</span>
                <span class="flex items-center gap-1.5">
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    Online
                </span>
            </div>
        </div>
    </x-slot>

    <div class="section-gap">
        <div class="page-container space-y-6">

            {{-- ── Hero Banner ── --}}
            <div class="hero-banner bg-gradient-to-r from-indigo-600 via-indigo-700 to-purple-700 shadow-md">
                <div class="absolute inset-0 bg-black/10 rounded-xl sm:rounded-2xl pointer-events-none"></div>
                <div class="relative flex items-center justify-between gap-4">
                    <div>
                        <h3 class="text-xl sm:text-2xl font-bold mb-1">
                            Selamat datang, {{ auth()->user()->name }}!
                        </h3>
                        <p class="text-indigo-100 text-sm sm:text-base">
                            Kelola platform pembelajaran dengan mudah dan efisien.
                        </p>
                    </div>
                    <div class="hidden sm:flex w-16 h-16 lg:w-20 lg:h-20 bg-white/20 rounded-2xl items-center justify-center flex-shrink-0">
                        <svg class="w-8 h-8 lg:w-10 lg:h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- ── Announcements ── --}}
            @if($announcements && $announcements->count() > 0)
            <div class="bg-white rounded-xl shadow-card border-l-4 border-blue-500 overflow-hidden">
                <div class="px-5 py-3.5 border-b border-gray-100 flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                    </svg>
                    <h3 class="text-sm font-semibold text-gray-900">Pengumuman Terbaru</h3>
                </div>
                <div class="p-4 sm:p-5 space-y-3">
                    @foreach($announcements->take(2) as $ann)
                    <div class="flex gap-3 p-3 rounded-lg border border-{{ $ann->level_color }}-200 bg-{{ $ann->level_color }}-50">
                        <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-{{ $ann->level_color }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            @if($ann->level === 'info')    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            @elseif($ann->level === 'success') <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            @elseif($ann->level === 'warning') <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            @else <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            @endif
                        </svg>
                        <div class="min-w-0">
                            <h4 class="text-sm font-semibold text-{{ $ann->level_color }}-800">{{ $ann->title }}</h4>
                            <p class="text-sm text-{{ $ann->level_color }}-700 mt-0.5 line-clamp-2">{{ Str::limit($ann->content, 120) }}</p>
                            <p class="text-xs text-{{ $ann->level_color }}-600 mt-1">{{ $ann->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- ── KPI Cards — 1 col → 2 col → 4 col ── --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-5">

                {{-- Users --}}
                <div class="kpi-card border-l-4 border-indigo-500 animate-fade-in-up" style="animation-delay:0ms">
                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 bg-indigo-50 rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Pengguna</p>
                            <p class="text-2xl font-bold text-gray-900 tabular-nums">{{ number_format($stats['users']['total']) }}</p>
                            <div class="flex flex-wrap gap-x-3 gap-y-1 mt-1.5 text-xs text-gray-500">
                                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-blue-400"></span>{{ $stats['users']['participants'] }} Peserta</span>
                                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-green-400"></span>{{ $stats['users']['instructors'] }} Instruktur</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Courses --}}
                <div class="kpi-card border-l-4 border-emerald-500 animate-fade-in-up" style="animation-delay:75ms">
                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 bg-emerald-50 rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Kursus</p>
                            <p class="text-2xl font-bold text-gray-900 tabular-nums">{{ number_format($stats['courses']['total']) }}</p>
                            <div class="flex flex-wrap gap-x-3 gap-y-1 mt-1.5 text-xs text-gray-500">
                                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-400"></span>{{ $stats['courses']['published'] }} Published</span>
                                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-400"></span>{{ $stats['courses']['draft'] }} Draft</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Quizzes --}}
                <div class="kpi-card border-l-4 border-purple-500 animate-fade-in-up" style="animation-delay:150ms">
                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 bg-purple-50 rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Kuis</p>
                            <p class="text-2xl font-bold text-gray-900 tabular-nums">{{ number_format($stats['quizzes']['total']) }}</p>
                            <p class="mt-1.5 text-xs text-gray-500">{{ $stats['quizzes']['completed'] }}/{{ $stats['quizzes']['attempts'] }} percobaan selesai</p>
                        </div>
                    </div>
                </div>

                {{-- Announcements --}}
                <div class="kpi-card border-l-4 border-orange-500 animate-fade-in-up" style="animation-delay:225ms">
                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 bg-orange-50 rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Pengumuman</p>
                            <p class="text-2xl font-bold text-gray-900 tabular-nums">{{ number_format($announcements->count()) }}</p>
                            <p class="mt-1.5 text-xs text-gray-500">{{ $stats['announcements']['active'] ?? 0 }} aktif dari {{ $stats['announcements']['total'] ?? 0 }} total</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Main Content Grid: 2/3 + 1/3 ── --}}
            <div class="dashboard-grid">

                {{-- Left: Recent Activities --}}
                <div class="dashboard-main">
                    <div class="bg-white rounded-xl shadow-card overflow-hidden">
                        <div class="px-5 py-4 border-b border-gray-100">
                            <h3 class="text-base font-semibold text-gray-900">Aktivitas Terbaru</h3>
                        </div>
                        <div class="p-4 sm:p-5 space-y-6">

                            {{-- Recent Courses --}}
                            <div>
                                <h4 class="text-sm font-semibold text-gray-700 mb-3">Kursus Terbaru</h4>
                                <div class="space-y-2">
                                    @forelse($stats['recent_activities']['courses'] as $course)
                                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                        <div class="w-10 h-10 bg-gradient-to-br from-blue-100 to-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate">{{ $course->title }}</p>
                                            <p class="text-xs text-gray-500">oleh {{ $course->instructors->pluck('name')->join(', ') }} &middot; {{ $course->created_at->diffForHumans() }}</p>
                                        </div>
                                        <span class="badge flex-shrink-0 {{ $course->status === 'published' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                            {{ ucfirst($course->status) }}
                                        </span>
                                    </div>
                                    @empty
                                    <div class="text-center py-8 text-gray-400">
                                        <svg class="w-10 h-10 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                        <p class="text-sm">Belum ada kursus</p>
                                    </div>
                                    @endforelse
                                </div>
                            </div>

                            {{-- Recent Users --}}
                            <div>
                                <h4 class="text-sm font-semibold text-gray-700 mb-3">Pengguna Terbaru</h4>
                                <div class="space-y-2">
                                    @forelse($stats['recent_activities']['users'] as $user)
                                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                        <div class="w-9 h-9 bg-gradient-to-br from-emerald-100 to-blue-100 rounded-full flex items-center justify-center flex-shrink-0 text-emerald-700 text-xs font-bold">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate">{{ $user->name }}</p>
                                            <p class="text-xs text-gray-500 truncate">{{ $user->email }}</p>
                                        </div>
                                        <div class="text-right flex-shrink-0">
                                            <p class="text-xs text-gray-400">{{ $user->created_at->diffForHumans() }}</p>
                                            <span class="badge bg-blue-100 text-blue-700 mt-0.5">{{ $user->primary_role }}</span>
                                        </div>
                                    </div>
                                    @empty
                                    <div class="text-center py-8 text-gray-400">
                                        <svg class="w-10 h-10 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197"/></svg>
                                        <p class="text-sm">Belum ada pengguna baru</p>
                                    </div>
                                    @endforelse
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                {{-- Right: Sidebar --}}
                <div class="dashboard-aside">

                    {{-- Quick Actions --}}
                    <div class="bg-white rounded-xl shadow-card overflow-hidden">
                        <div class="px-5 py-4 border-b border-gray-100">
                            <h3 class="text-base font-semibold text-gray-900">Aksi Cepat</h3>
                        </div>
                        <div class="p-4 space-y-2">
                            @foreach([
                                ['route' => 'admin.users.index', 'label' => 'Kelola Pengguna',    'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0'],
                                ['route' => 'courses.index',    'label' => 'Kelola Kursus',       'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
                                ['route' => 'admin.announcements.index', 'label' => 'Pengumuman', 'icon' => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z'],
                                ['route' => 'activity-logs.index', 'label' => 'Log Aktivitas',   'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                            ] as $action)
                            <a href="{{ route($action['route']) }}"
                               class="flex items-center gap-3 w-full min-h-[44px] px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 bg-gray-50 hover:bg-indigo-50 hover:text-indigo-700 transition-colors group">
                                <svg class="w-4 h-4 text-gray-400 group-hover:text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $action['icon'] }}"/>
                                </svg>
                                <span class="flex-1">{{ $action['label'] }}</span>
                                <svg class="w-3.5 h-3.5 text-gray-300 group-hover:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                            @endforeach
                        </div>
                    </div>

                    {{-- System Statistics --}}
                    <div class="bg-white rounded-xl shadow-card overflow-hidden">
                        <div class="px-5 py-4 border-b border-gray-100">
                            <h3 class="text-base font-semibold text-gray-900">Statistik Sistem</h3>
                        </div>
                        <div class="p-4 divide-y divide-gray-50">
                            @foreach([
                                ['label' => 'Total Peserta',     'value' => $stats['users']['participants']],
                                ['label' => 'Total Instruktur',  'value' => $stats['users']['instructors']],
                                ['label' => 'Kursus Published',  'value' => $stats['courses']['published']],
                                ['label' => 'Diskusi Aktif',     'value' => $stats['discussions']['total']],
                            ] as $stat)
                            <div class="flex items-center justify-between py-2.5">
                                <span class="text-sm text-gray-600">{{ $stat['label'] }}</span>
                                <span class="text-sm font-semibold text-gray-900 tabular-nums">{{ number_format($stat['value']) }}</span>
                            </div>
                            @endforeach
                            <div class="pt-3 flex items-center justify-between">
                                <span class="text-sm font-semibold text-gray-900">Total Aktivitas</span>
                                <span class="text-lg font-bold text-indigo-600 tabular-nums">
                                    {{ number_format($stats['quizzes']['attempts'] + $stats['essays']['submissions'] + $stats['discussions']['total']) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- System Status --}}
                    <div class="bg-white rounded-xl shadow-card p-4 sm:p-5">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-900">Platform LMS</span>
                            <span class="badge bg-emerald-100 text-emerald-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                Online
                            </span>
                        </div>
                        <div class="mt-2 text-xs text-gray-400 space-y-0.5">
                            <p>Diperbarui: {{ now()->format('H:i') }}</p>
                            <p>Lingkungan: {{ ucfirst(config('app.env')) }}</p>
                        </div>
                    </div>

                </div>
            </div>

        </div>{{-- /page-container --}}
    </div>{{-- /section-gap --}}

</x-app-layout>
