<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-6">
            <div class="flex items-center space-x-4">
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                        {{ $course->title }}
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">Detail dan manajemen kursus</p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3" x-data="{ openActions: false }">
                <a href="javascript:void(0)" onclick="window.history.back()" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-xl font-medium text-sm text-gray-700 hover:bg-gray-50 hover:shadow-lg transition-all duration-200 shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Kembali
                </a>

                @can('update', $course)
                    <a href="{{ route('courses.edit', $course) }}" class="inline-flex items-center px-4 py-2 bg-warning text-white rounded-xl font-medium text-sm hover:bg-amber-700 shadow-lg hover:shadow-xl transition-all duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Edit Kursus
                    </a>
                @endcan

                {{-- Dropdown: Aksi Lainnya --}}
                @canany(['view', 'grade quizzes', 'view progress reports', 'viewProgress', 'update'], $course)
                <div class="relative" @click.away="openActions = false">
                    <button @click="openActions = !openActions"
                            class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-xl font-medium text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-800 shadow-sm transition-all duration-200"
                            :class="{ 'ring-2 ring-bass-red/20 border-bass-red': openActions }">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                        </svg>
                    </button>

                    <div x-show="openActions" x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-xl border border-gray-200 py-2 z-50">

                        {{-- Section: Pembelajaran --}}
                        @canany(['view', 'grade quizzes', 'view progress reports'], $course)
                        <div class="px-3 py-1.5">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Pembelajaran</p>
                        </div>
                        @endcanany
                        @can('view', $course)
                            <a href="{{ route('courses.discussions.index', $course) }}" class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-red-50 hover:text-bass-red transition-colors">
                                <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                </svg>
                                Diskusi
                            </a>
                        @endcan
                        @can('grade quizzes')
                            <a href="{{ route('courses.gradebook', $course) }}" class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-red-50 hover:text-bass-red transition-colors">
                                <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                </svg>
                                Penilaian Essay
                            </a>
                        @endcan
                        @can('view progress reports')
                            <a href="{{ route('courses.scores', $course) }}" class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-red-50 hover:text-bass-red transition-colors">
                                <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Nilai Quiz
                            </a>
                        @endcan

                        <div class="border-t border-gray-100 my-1"></div>

                        {{-- Section: Monitoring --}}
                        @can('view progress reports')
                        <div class="px-3 py-1.5">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Monitoring</p>
                        </div>
                            <a href="{{ route('courses.progress', $course) }}" class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-red-50 hover:text-bass-red transition-colors">
                                <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                Lihat Progres
                            </a>
                            <a href="{{ route('attendance.course-report', $course) }}" class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-red-50 hover:text-bass-red transition-colors">
                                <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                </svg>
                                Attendance
                            </a>
                        @endcan

                        {{-- Section: Pengaturan --}}
                        @can('update', $course)
                        <div class="border-t border-gray-100 my-1"></div>
                        <div class="px-3 py-1.5">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Pengaturan</p>
                        </div>
                            <a href="{{ route('courses.tokens', $course) }}" class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-red-50 hover:text-bass-red transition-colors">
                                <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                </svg>
                                Token Kelas
                            </a>
                        @endcan
                    </div>
                </div>
                @endcanany
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-8 p-4 bg-success-soft border border-success/30 rounded-xl shadow-sm" role="alert">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-success">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <div x-data="{ currentTab: 'lessons' }" class="bg-white rounded-2xl shadow-xl overflow-hidden">
                <!-- Enhanced Tab Navigation -->
                <div class="bg-gray-50 border-b border-gray-200">
                    <nav class="flex space-x-8 px-6" aria-label="Tabs">
                        <button @click="currentTab = 'lessons'"
                                :class="{'border-bass-red text-bass-red bg-red-50': currentTab === 'lessons', 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50': currentTab !== 'lessons'}"
                                class="whitespace-nowrap py-4 px-4 border-b-2 font-semibold text-sm rounded-t-lg transition-all duration-200">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                                <span>Pelajaran & Konten</span>
                            </div>
                        </button>
                        {{-- 🆕 NEW: Periods & Chat Tab --}}
                        <button @click="currentTab = 'periods'"
                                :class="{'border-bass-red text-bass-red bg-red-50': currentTab === 'periods', 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50': currentTab !== 'periods'}"
                                class="whitespace-nowrap py-4 px-4 border-b-2 font-semibold text-sm rounded-t-lg transition-all duration-200">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <span>Kelas & Chat</span>
                            </div>
                        </button>

                        @can('update', $course)
                            <button @click="currentTab = 'managers'"
                                    :class="{'border-bass-red text-bass-red bg-red-50': currentTab === 'managers', 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50': currentTab !== 'managers'}"
                                    class="whitespace-nowrap py-4 px-4 border-b-2 font-semibold text-sm rounded-t-lg transition-all duration-200">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    <span>Instruktur</span>
                                </div>
                            </button>
                            <button @click="currentTab = 'event_organizers'"
                                    :class="{'border-bass-red text-bass-red bg-red-50': currentTab === 'event_organizers', 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50': currentTab !== 'event_organizers'}"
                                    class="whitespace-nowrap py-4 px-4 border-b-2 font-semibold text-sm rounded-t-lg transition-all duration-200">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                    <span>Event Organizer</span>
                                </div>
                            </button>
                        @endcan
                        @can('manageParticipants', $course)
                            <button @click="currentTab = 'participants'"
                                    :class="{'border-bass-red text-bass-red bg-red-50': currentTab === 'participants', 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50': currentTab !== 'participants'}"
                                    class="whitespace-nowrap py-4 px-4 border-b-2 font-semibold text-sm rounded-t-lg transition-all duration-200">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                    <span>Peserta Kursus</span>
                                </div>
                            </button>
                        @endcan
                    </nav>
                </div>

                <!-- Lessons Tab -->
                <div x-show="currentTab === 'lessons'" class="p-8">
                    <div
                        x-data="{
                            lessons: {{ Js::from($course->lessons->sortBy('order')->values()) }},
                            activeAccordion: null,
                            moveUp(index) {
                                if (index === 0) return;
                                [this.lessons[index - 1], this.lessons[index]] = [this.lessons[index], this.lessons[index - 1]];
                                this.updateLessonOrderOnServer();
                            },
                            moveDown(index) {
                                if (index === this.lessons.length - 1) return;
                                [this.lessons[index], this.lessons[index + 1]] = [this.lessons[index + 1], this.lessons[index]];
                                this.updateLessonOrderOnServer();
                            },
                            updateLessonOrderOnServer() {
                                const orderedIds = this.lessons.map(lesson => lesson.id);
                                fetch('{{ route('lessons.update_order') }}', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                    body: JSON.stringify({ lessons: orderedIds })
                                });
                            },
                            moveContentUp(lessonIndex, contentIndex) {
                                if (contentIndex === 0) return;
                                let contents = this.lessons[lessonIndex].contents;
                                [contents[contentIndex - 1], contents[contentIndex]] = [contents[contentIndex], contents[contentIndex - 1]];
                                this.updateContentOrderOnServer(lessonIndex);
                            },
                            moveContentDown(lessonIndex, contentIndex) {
                                let contents = this.lessons[lessonIndex].contents;
                                if (contentIndex === contents.length - 1) return;
                                [contents[contentIndex], contents[contentIndex + 1]] = [contents[contentIndex + 1], contents[contentIndex]];
                                this.updateContentOrderOnServer(lessonIndex);
                            },
                            updateContentOrderOnServer(lessonIndex) {
                                const orderedContentIds = this.lessons[lessonIndex].contents.map(content => content.id);
                                fetch('{{ route('contents.update_order') }}', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                    body: JSON.stringify({ contents: orderedContentIds })
                                });
                            }
                        }">

                        <div class="flex justify-between items-center mb-8">
                            <div>
                                <h3 class="text-2xl font-bold text-gray-900">Daftar Pelajaran</h3>
                                <p class="text-gray-600 mt-1">Kelola urutan dan konten pelajaran</p>
                            </div>
                            @can('update', $course)
                                <a href="{{ route('courses.lessons.create', $course) }}" class="inline-flex items-center px-6 py-3 bg-bass-red text-white rounded-xl font-semibold text-sm hover:bg-[#B91818] shadow-lg hover:shadow-xl transition-all duration-200">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Tambah Pelajaran
                                </a>
                            @endcan
                        </div>

                        <div x-show="lessons.length === 0" class="text-center py-16">
                            <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                            </div>
                            <h4 class="text-xl font-semibold text-gray-900 mb-2">Belum Ada Pelajaran</h4>
                            <p class="text-gray-500">Tambahkan pelajaran pertama untuk memulai kursus ini.</p>
                        </div>

                        <div class="space-y-6">
                            <template x-for="(lesson, index) in lessons" :key="lesson.id">
                                <div class="bg-white rounded-2xl shadow-lg border border-gray-200 hover:shadow-xl transition-all duration-300"
                                    :class="{ 'opacity-50 pointer-events-none': !isLessonUnlocked(lesson, index) }">

                                    <div class="p-6 flex justify-between items-center">
                                        <div class="flex items-center flex-grow">
                                            @can('update', $course)
                                                <div class="flex flex-col mr-4 space-y-1">
                                                    <button @click="moveUp(index)" :disabled="index === 0"
                                                            :class="{'opacity-25 cursor-not-allowed': index === 0}"
                                                            class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                                        </svg>
                                                    </button>
                                                    <button @click="moveDown(index)" :disabled="index === lessons.length - 1"
                                                            :class="{'opacity-25 cursor-not-allowed': index === lessons.length - 1}"
                                                            class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            @endcan
                                            <div class="flex items-center space-x-4">
                                                <div class="w-12 h-12 bg-navy rounded-xl flex items-center justify-center shadow-lg">
                                                    <span class="font-bold text-white text-lg" x-text="index + 1"></span>
                                                </div>
                                                <div>
                                                    <template x-if="!isLessonUnlocked(lesson, index)">
                                                        <div class="flex items-center space-x-2 mb-1">
                                                            <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd"></path>
                                                            </svg>
                                                            <span class="text-xs text-amber-600 font-medium">Terkunci</span>
                                                        </div>
                                                    </template>
                                                    <h4 class="text-xl font-bold text-gray-900" x-text="lesson.title"></h4>
                                                    <p class="text-gray-600 text-sm mt-1" x-text="lesson.description || 'Tidak ada deskripsi.'"></p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2 flex-shrink-0">
                                            @can('update', $course)
                                                <!-- Action Buttons -->
                                                <div class="flex items-center space-x-2">
                                                    <form :action="`/courses/{{$course->id}}/lessons/${lesson.id}/duplicate`" method="POST" onsubmit="return confirm('Yakin ingin duplikasi pelajaran ini?');">
                                                        @csrf
                                                        <button type="submit" class="inline-flex items-center px-3 py-2 bg-info text-white text-sm font-medium rounded-lg hover:bg-navy/20 transition-colors">
                                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                            </svg>
                                                            Duplikat
                                                        </button>
                                                    </form>

                                                    <a :href="`/courses/{{$course->id}}/lessons/${lesson.id}/edit`" class="inline-flex items-center px-3 py-2 bg-warning/10 text-warning text-sm font-medium rounded-lg hover:bg-warning/20 transition-colors">
                                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                        </svg>
                                                        Edit
                                                    </a>

                                                    <a :href="`/lessons/${lesson.id}/contents/create`" class="inline-flex items-center px-3 py-2 bg-info-soft text-navy text-sm font-medium rounded-lg hover:bg-navy/10 transition-colors">
                                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                                        </svg>
                                                        Tambah Konten
                                                    </a>

                                                    <form :action="`/courses/{{$course->id}}/lessons/${lesson.id}`" method="POST" onsubmit="return confirm('Yakin ingin menghapus pelajaran ini?');">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="inline-flex items-center px-3 py-2 bg-red-100 text-red-700 text-sm font-medium rounded-lg hover:bg-red-200 transition-colors">
                                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                            </svg>
                                                            Hapus
                                                        </button>
                                                    </form>
                                                </div>
                                            @endcan

                                            <!-- Expand Button -->
                                            <button @click="activeAccordion = (activeAccordion === lesson.id) ? null : lesson.id"
                                                    class="p-2 rounded-full hover:bg-gray-100 transition-colors">
                                                <svg class="w-5 h-5 text-gray-600 transition-transform" :class="{'rotate-180': activeAccordion === lesson.id}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Content List -->
                                    <div x-show="activeAccordion === lesson.id" x-collapse.duration.300ms class="border-t border-gray-200 bg-gray-50">
                                        <div class="p-6">
                                            <div class="flex items-center justify-between mb-4">
                                                <h5 class="text-lg font-semibold text-gray-800">Daftar Konten</h5>
                                                <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm font-medium" x-text="`${lesson.contents.length} konten`"></span>
                                            </div>

                                            <div class="space-y-3">
                                                <template x-for="(content, contentIndex) in lesson.contents" :key="content.id">
                                                    <div class="flex items-center justify-between p-4 bg-white rounded-xl border border-gray-200 hover:shadow-md transition-all duration-200">
                                                        <div class="flex items-center space-x-4">
                                                            @can('update', $course)
                                                            <div class="flex flex-col space-y-1">
                                                                <button @click="moveContentUp(index, contentIndex)" :disabled="contentIndex === 0"
                                                                        :class="{'opacity-25 cursor-not-allowed': contentIndex === 0}"
                                                                        class="p-1 hover:bg-gray-100 rounded">
                                                                    <svg class="w-3 h-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                                                    </svg>
                                                                </button>
                                                                <button @click="moveContentDown(index, contentIndex)" :disabled="contentIndex === lesson.contents.length - 1"
                                                                        :class="{'opacity-25 cursor-not-allowed': contentIndex === lesson.contents.length - 1}"
                                                                        class="p-1 hover:bg-gray-100 rounded">
                                                                    <svg class="w-3 h-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                                    </svg>
                                                                </button>
                                                            </div>
                                                            @endcan

                                                            <!-- Content Type Icon -->
                                                            <div class="w-10 h-10 rounded-lg flex items-center justify-center"
                                                                 :class="{
                                                                    'bg-info-soft': content.type === 'text',
                                                                    'bg-error-soft': content.type === 'video',
                                                                    'bg-success-soft': content.type === 'quiz',
                                                                    'bg-gray-100': !['text', 'video', 'quiz'].includes(content.type)
                                                                 }">
                                                                <svg class="w-5 h-5"
                                                                     :class="{
                                                                        'text-navy': content.type === 'text',
                                                                        'text-error': content.type === 'video',
                                                                        'text-success': content.type === 'quiz',
                                                                        'text-gray-600': !['text', 'video', 'quiz'].includes(content.type)
                                                                     }"
                                                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path x-show="content.type === 'text'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                                    <path x-show="content.type === 'video'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                                    <path x-show="content.type === 'quiz'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                                    <path x-show="!['text', 'video', 'quiz'].includes(content.type)" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                                                </svg>
                                                            </div>

                                                            <div>
                                                                <a :href="`/contents/${content.id}`" class="text-lg font-medium text-bass-red hover:text-[#B91818] hover:underline transition-colors" x-text="content.title"></a>
                                                                <div class="flex items-center space-x-2 mt-1">
                                                                    <span class="px-2 py-1 text-xs font-medium rounded-full"
                                                                          :class="{
                                                                        'bg-info-soft text-navy': content.type === 'text',
                                                                        'bg-error-soft text-error': content.type === 'video',
                                                                        'bg-success-soft text-success': content.type === 'quiz',
                                                                            'bg-gray-100 text-gray-700': !['text', 'video', 'quiz'].includes(content.type)
                                                                          }"
                                                                          x-text="content.type.charAt(0).toUpperCase() + content.type.slice(1)"></span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        @can('update', $course)
                                                        <div class="flex items-center space-x-2">
                                                            <form :action="`/lessons/${lesson.id}/contents/${content.id}/duplicate`" method="POST" onsubmit="return confirm('Yakin ingin duplikasi konten ini?');">
                                                                @csrf
                                                                <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-info-soft text-navy text-xs font-medium rounded-lg hover:bg-navy/10 transition-colors">
                                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                                    </svg>
                                                                    Duplikat
                                                                </button>
                                                            </form>

                                                            <a :href="`/lessons/${lesson.id}/contents/${content.id}/edit`" class="inline-flex items-center px-3 py-1.5 bg-warning/10 text-warning text-xs font-medium rounded-lg hover:bg-warning/20 transition-colors">
                                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                                </svg>
                                                                Edit
                                                            </a>

                                                            <form :action="`/lessons/${lesson.id}/contents/${content.id}`" method="POST" onsubmit="return confirm('Yakin ingin menghapus konten ini?');">
                                                                @csrf @method('DELETE')
                                                                <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-red-50 text-red-700 text-xs font-medium rounded-lg hover:bg-red-100 transition-colors">
                                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                                    </svg>
                                                                    Hapus
                                                                </button>
                                                            </form>
                                                        </div>
                                                        @endcan
                                                    </div>
                                                </template>

                                                <div x-show="lesson.contents.length === 0" class="text-center py-12">
                                                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                        </svg>
                                                    </div>
                                                    <p class="text-gray-500 text-sm">Belum ada konten untuk pelajaran ini.</p>
                                                    @can('update', $course)
                                                        <a :href="`/lessons/${lesson.id}/contents/create`" class="inline-flex items-center mt-3 px-4 py-2 bg-red-50 text-bass-red text-sm font-medium rounded-lg hover:bg-red-100 transition-colors">
                                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                                            </svg>
                                                            Tambah Konten Pertama
                                                        </a>
                                                    @endcan
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- 🆕 NEW: Periods & Chat Tab --}}
                <div x-show="currentTab === 'periods'" x-cloak class="p-8" 
                     x-data="periodManager({{ $course->id }}, @js($course->periods->toArray() ?? []))">
                    <div class="mb-8">
                        <h3 class="text-2xl font-bold text-gray-900">Kelas & Komunikasi Kursus</h3>
                        <p class="text-gray-600 mt-1">Kelola kelas kursus dan akses chat realtime</p>
                    </div>

                    @if($course->periods && $course->periods->count() > 0)
                        <!-- Search and Bulk Actions -->
                        <div class="mb-6 bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                                <!-- Search -->
                                <div class="flex-1 max-w-md">
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                            </svg>
                                        </div>
                                        <input x-model="searchTerm" type="text" 
                                               class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-bass-red focus:border-transparent"
                                               placeholder="Cari kelas berdasarkan nama atau deskripsi...">
                                    </div>
                                </div>

                                <!-- Bulk Actions -->
                                @can('update', $course)
                                <div class="flex items-center space-x-3">
                                    <label class="flex items-center">
                                        <input type="checkbox" x-model="selectAll" @change="toggleSelectAll()" 
                                               class="rounded border-gray-300 text-bass-red shadow-sm focus:border-bass-red focus:ring focus:ring-bass-red/20 focus:ring-opacity-50">
                                        <span class="ml-2 text-sm text-gray-600">Pilih Semua</span>
                                    </label>
                                    
                                    <button @click="deleteSelected()" x-show="selectedPeriods.length > 0"
                                            class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 shadow-md transition-all duration-200">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                        <span x-text="`Hapus (${selectedPeriods.length})`"></span>
                                    </button>
                                </div>
                                @endcan
                            </div>
                        </div>

                        <!-- Status Summary -->
                        <div class="mb-6 flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-3 bg-success rounded-full"></div>
                                    <span class="text-sm text-gray-600">Kelas Aktif: {{ $course->periods->where('status', 'active')->count() }}</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-3 bg-navy rounded-full"></div>
                                    <span class="text-sm text-gray-600">Mendatang: {{ $course->periods->where('status', 'upcoming')->count() }}</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-3 bg-gray-400 rounded-full"></div>
                                    <span class="text-sm text-gray-600">Selesai: {{ $course->periods->where('status', 'completed')->count() }}</span>
                                </div>
                            </div>

                            <div class="flex space-x-2">
                                @can('update', $course)
                                    <a href="{{ route('course-periods.create', $course) }}"
                                        class="inline-flex items-center px-4 py-2 bg-bass-red text-white text-sm font-medium rounded-lg hover:bg-[#B91818] shadow-md transition-all duration-200">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                        Tambah Kelas
                                    </a>
                                @endcan

                                @if($course->hasActivePeriod())
                                    <a href="{{ route('chat.index') }}?course={{ $course->id }}"
                                       class="inline-flex items-center px-4 py-2 bg-navy text-white text-sm font-medium rounded-lg hover:bg-navy-light shadow-md transition-all duration-200">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                        </svg>
                                        Buka Chat
                                    </a>
                                @endif
                            </div>
                        </div>

                        <!-- Period Cards with Search Filtering -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <template x-for="period in filteredPeriods" :key="period.id">
                                <div class="bg-white rounded-xl border border-gray-200 hover:shadow-lg transition-all duration-300 p-5"
                                     :class="period.status === 'active' ? 'ring-2 ring-success/30 border-success/20' : ''">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center space-x-3">
                                            @can('update', $course)
                                                <input type="checkbox" :value="period.id" x-model="selectedPeriods"
                                                       class="rounded border-gray-300 text-bass-red shadow-sm focus:border-bass-red focus:ring focus:ring-bass-red/20 focus:ring-opacity-50">
                                            @endcan
                                            <h4 class="text-lg font-bold text-gray-900" x-text="period.name"></h4>
                                        </div>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                              :class="{
                                                  'bg-success-soft text-success': period.status === 'active',
                                                  'bg-info-soft text-navy': period.status === 'upcoming',
                                                  'bg-gray-100 text-gray-800': period.status === 'completed',
                                                  'bg-red-100 text-red-800': period.status === 'cancelled'
                                              }"
                                              x-text="period.status === 'active' ? 'Aktif' : period.status === 'upcoming' ? 'Akan Datang' : period.status === 'completed' ? 'Selesai' : 'Dibatalkan'">
                                        </span>
                                    </div>

                                    <div class="space-y-3 mb-6">
                                        <div class="flex items-center text-sm text-gray-600">
                                            <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            <div>
                                                <div class="font-medium" x-text="`${new Date(period.start_date).toLocaleDateString('id-ID', {day: 'numeric', month: 'short', year: 'numeric'})} - ${new Date(period.end_date).toLocaleDateString('id-ID', {day: 'numeric', month: 'short', year: 'numeric'})}`"></div>
                                                <div class="text-xs text-gray-500" x-text="`${Math.ceil((new Date(period.end_date) - new Date(period.start_date)) / (1000 * 60 * 60 * 24))} hari`"></div>
                                            </div>
                                        </div>

                                        <template x-if="period.status === 'active'">
                                            <div class="flex items-center text-sm text-success">
                                                <svg class="w-4 h-4 mr-3 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <span class="font-medium" x-text="`${Math.max(0, Math.ceil((new Date(period.end_date) - new Date()) / (1000 * 60 * 60 * 24)))} hari tersisa`"></span>
                                            </div>
                                        </template>

                                        <template x-if="period.description">
                                            <div class="text-sm text-gray-600 bg-gray-50 p-3 rounded-lg" x-text="period.description.substring(0, 100) + (period.description.length > 100 ? '...' : '')"></div>
                                        </template>
                                    </div>

                                    <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                                        <div>
                                            <template x-if="period.status === 'active'">
                                                <a :href="`{{ route('chat.index') }}?period=${period.id}`"
                                                   class="inline-flex items-center text-sm font-medium text-success hover:text-success-dark transition-colors">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                                    </svg>
                                                    Masuk Chat
                                                </a>
                                            </template>
                                            <template x-if="period.status === 'upcoming'">
                                                <span class="inline-flex items-center text-sm text-navy">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    Belum dimulai
                                                </span>
                                            </template>
                                            <template x-if="period.status === 'completed' || period.status === 'cancelled'">
                                                <span class="inline-flex items-center text-sm text-gray-500">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    Selesai
                                                </span>
                                            </template>
                                        </div>

                                        @can('update', $course)
                                            <div class="flex items-center space-x-2">
                                                <a :href="`{{ url('courses/' . $course->id . '/periods') }}/${period.id}/manage`"
                                                   class="text-xs text-navy hover:text-navy-light font-medium">Kelola</a>
                                                <a :href="`{{ url('courses/' . $course->id . '/periods') }}/${period.id}/edit`"
                                                   class="text-xs text-navy hover:text-navy-light font-medium">Edit</a>
                                                <button @click="deletePeriod(period.id)" class="text-xs text-red-600 hover:text-red-800 font-medium">Hapus</button>
                                            </div>
                                        @endcan
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- No results message -->
                        <div x-show="filteredPeriods.length === 0 && searchTerm !== ''" class="text-center py-8">
                            <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <h4 class="text-lg font-semibold text-gray-900 mb-2">Tidak ada kelas yang ditemukan</h4>
                            <p class="text-gray-600" x-text="'Tidak ada kelas yang cocok dengan \'' + searchTerm + '\''"></p>
                        </div>

                        @if($course->periods->where('status', 'active')->count() === 0)
                            <div class="mt-8 p-6 bg-warning-soft border border-warning/30 rounded-xl">
                                <div class="flex items-center">
                                    <svg class="w-6 h-6 text-warning mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L3.232 19.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                    </svg>
                                    <div>
                                        <h4 class="text-lg font-medium text-gray-800">Tidak ada kelas aktif</h4>
                                        <p class="text-sm text-gray-600 mt-1">Chat tidak tersedia saat ini. Tambahkan kelas baru atau aktifkan kelas yang ada.</p>
                                    </div>
                                </div>
                            </div>
                        @endif

                    @else
                        {{-- No Periods State --}}
                        <div class="text-center py-16">
                            <div class="bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-6" style="width: 6rem; height: 6rem;">
                                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <h4 class="text-2xl font-bold text-gray-900 mb-3">Belum Ada Kelas Kursus</h4>
                            <p class="text-gray-600 text-lg mb-8 max-w-md mx-auto">Buat kelas kursus untuk mengaktifkan fitur chat dan mengelola timeline pembelajaran.</p>
                            @can('update', $course)
                                <a href="{{ route('course-periods.create', ['course' => $course->id]) }}"
                                    class="inline-flex items-center px-6 py-3 bg-bass-red text-white font-semibold rounded-xl hover:bg-[#B91818] shadow-lg hover:shadow-xl transition-all duration-200">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Buat Kelas Pertama
                                </a>
                            @endcan
                        </div>
                    @endif
                </div>

                @can('update', $course)
                    <!-- Managers Tab -->
                    <div x-show="currentTab === 'managers'" x-cloak class="p-8">
                        <div class="mb-8">
                            <h3 class="text-2xl font-bold text-gray-900">Manajemen Instruktur</h3>
                            <p class="text-gray-600 mt-1">Kelola instruktur yang ditugaskan untuk kursus ini</p>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <!-- Current Instructors -->
                            <div class="bg-red-50 rounded-2xl p-6 border border-red-200">
                                <div class="flex items-center mb-6">
                                    <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center mr-3">
                                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-lg font-bold text-red-900">Instruktur Ditugaskan</h4>
                                        <p class="text-sm text-red-700">{{ $course->instructors->count() }} instruktur aktif</p>
                                    </div>
                                </div>

                                <form action="{{ route('courses.removeInstructor', $course) }}" method="POST" onsubmit="return confirm('Anda yakin ingin menghapus instruktur terpilih?');">
                                    @csrf @method('DELETE')
                                    <div class="space-y-3 mb-6 max-h-80 overflow-y-auto">
                                        @forelse($course->instructors as $instructor)
                                            <div class="flex items-center p-3 bg-white rounded-xl border border-red-200 hover:bg-red-50 transition-colors">
                                                <input type="checkbox" name="user_ids[]" value="{{ $instructor->id }}" id="instructor-{{$instructor->id}}" class="mr-3 rounded border-red-300 text-red-600 focus:ring-red-500">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-8 h-8 bg-red-600 rounded-full flex items-center justify-center">
                                                        <span class="text-white text-sm font-semibold">{{ strtoupper(substr($instructor->name, 0, 1)) }}</span>
                                                    </div>
                                                    <label for="instructor-{{$instructor->id}}" class="font-medium text-gray-900 cursor-pointer">{{ $instructor->name }}</label>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="text-center py-8">
                                                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                                    <svg class="w-8 h-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                    </svg>
                                                </div>
                                                <p class="text-red-600 font-medium">Belum ada instruktur ditugaskan</p>
                                            </div>
                                        @endforelse
                                    </div>
                                    @if($course->instructors->isNotEmpty())
                                        <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-3 bg-red-600 text-white font-semibold rounded-xl hover:bg-red-700 shadow-lg hover:shadow-xl transition-all duration-200">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            Hapus Instruktur Terpilih
                                        </button>
                                    @endif
                                </form>
                            </div>

                            <!-- Available Instructors -->
                            <div class="bg-info-soft rounded-2xl p-6 border border-navy/20">
                                <div class="flex items-center mb-6">
                                    <div class="w-10 h-10 bg-navy rounded-xl flex items-center justify-center mr-3">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-lg font-bold text-navy">Tambahkan Instruktur</h4>
                                        <p class="text-sm text-navy/70">{{ $availableInstructors->count() }} instruktur tersedia</p>
                                    </div>
                                </div>

                                <form action="{{ route('courses.addInstructor', $course) }}" method="POST">
                                    @csrf
                                    <div class="space-y-3 mb-6 max-h-80 overflow-y-auto">
                                        @forelse($availableInstructors as $instructor)
                                            <div class="flex items-center p-3 bg-white rounded-xl border border-navy/20 hover:bg-navy/5 transition-colors">
                                                <input type="checkbox" name="user_ids[]" value="{{ $instructor->id }}" id="avail-instructor-{{$instructor->id}}" class="mr-3 rounded border-gray-300 text-bass-red focus:ring-bass-red">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-8 h-8 bg-navy rounded-full flex items-center justify-center">
                                                        <span class="text-white text-sm font-semibold">{{ strtoupper(substr($instructor->name, 0, 1)) }}</span>
                                                    </div>
                                                    <label for="avail-instructor-{{$instructor->id}}" class="font-medium text-gray-900 cursor-pointer">{{ $instructor->name }}</label>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="text-center py-8">
                                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                </div>
                                                <p class="text-gray-600 font-medium">Semua instruktur sudah ditugaskan</p>
                                            </div>
                                        @endforelse
                                    </div>
                                    {{-- All pagination removed - now using Collection directly --}}
                                    @if($availableInstructors->count() > 0)
                                        <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-3 bg-bass-red text-white font-semibold rounded-xl hover:bg-bass-red-hover shadow-lg hover:shadow-xl transition-all duration-200">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                            </svg>
                                            Tambahkan Instruktur
                                        </button>
                                    @endif
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Event Organizers Tab -->
                    <div x-show="currentTab === 'event_organizers'" x-cloak class="p-8">
                        <div class="mb-8">
                            <h3 class="text-2xl font-bold text-gray-900">Manajemen Event Organizer</h3>
                            <p class="text-gray-600 mt-1">Kelola event organizer yang ditugaskan untuk kursus ini</p>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <!-- Current Event Organizers -->
                            <div class="bg-gray-50 rounded-2xl p-6 border border-gray-200">
                                <div class="flex items-center mb-6">
                                    <div class="w-10 h-10 bg-navy rounded-xl flex items-center justify-center mr-3">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-lg font-bold text-gray-900">EO Ditugaskan</h4>
                                        <p class="text-sm text-gray-600">{{ $course->eventOrganizers->count() }} EO aktif</p>
                                    </div>
                                </div>

                                <form action="{{ route('courses.removeEo', $course) }}" method="POST" onsubmit="return confirm('Anda yakin ingin menghapus EO terpilih?');">
                                    @csrf @method('DELETE')
                                    <div class="space-y-3 mb-6 max-h-80 overflow-y-auto">
                                        @forelse($course->eventOrganizers as $organizer)
                                            <div class="flex items-center p-3 bg-white rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors">
                                                <input type="checkbox" name="user_ids[]" value="{{ $organizer->id }}" id="organizer-{{$organizer->id}}" class="mr-3 rounded border-gray-300 text-bass-red focus:ring-bass-red">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-8 h-8 bg-navy rounded-full flex items-center justify-center">
                                                        <span class="text-white text-sm font-semibold">{{ strtoupper(substr($organizer->name, 0, 1)) }}</span>
                                                    </div>
                                                    <label for="organizer-{{$organizer->id}}" class="font-medium text-gray-900 cursor-pointer">{{ $organizer->name }}</label>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="text-center py-8">
                                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                                     <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                                    </svg>
                                                </div>
                                                <p class="text-gray-600 font-medium">Belum ada EO ditugaskan</p>
                                            </div>
                                        @endforelse
                                    </div>
                                    @if($course->eventOrganizers->isNotEmpty())
                                        <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-3 bg-navy text-white font-semibold rounded-xl hover:bg-navy/90 shadow-lg hover:shadow-xl transition-all duration-200">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            Hapus EO Terpilih
                                        </button>
                                    @endif
                                </form>
                            </div>

                            <!-- Available Event Organizers -->
                            <div class="bg-gray-50 rounded-2xl p-6 border border-gray-200">
                                <div class="flex items-center mb-6">
                                    <div class="w-10 h-10 bg-gray-200 rounded-xl flex items-center justify-center mr-3">
                                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-lg font-bold text-gray-900">Tambahkan EO</h4>
                                        <p class="text-sm text-gray-600">{{ $availableOrganizers->count() }} EO tersedia</p>
                                    </div>
                                </div>

                                <form action="{{ route('courses.addEo', $course) }}" method="POST">
                                    @csrf
                                    <div class="space-y-3 mb-6 max-h-80 overflow-y-auto">
                                        @forelse($availableOrganizers as $organizer)
                                            <div class="flex items-center p-3 bg-white rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors">
                                                <input type="checkbox" name="user_ids[]" value="{{ $organizer->id }}" id="avail-organizer-{{$organizer->id}}" class="mr-3 rounded border-gray-300 text-bass-red focus:ring-bass-red">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-8 h-8 bg-navy rounded-full flex items-center justify-center">
                                                        <span class="text-white text-sm font-semibold">{{ strtoupper(substr($organizer->name, 0, 1)) }}</span>
                                                    </div>
                                                    <label for="avail-organizer-{{$organizer->id}}" class="font-medium text-gray-900 cursor-pointer">{{ $organizer->name }}</label>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="text-center py-8">
                                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                </div>
                                                <p class="text-gray-600 font-medium">Semua EO sudah ditugaskan</p>
                                            </div>
                                        @endforelse
                                    </div>
                                    {{-- All pagination removed - now using Collection directly --}}
                                    @if($availableOrganizers->count() > 0)
                                        <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-3 bg-navy text-white font-semibold rounded-xl hover:bg-navy/90 shadow-lg hover:shadow-xl transition-all duration-200">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                            </svg>
                                            Tambahkan EO
                                        </button>
                                    @endif
                                </form>
                            </div>
                        </div>
                    </div>

                @endcan

                @can('manageParticipants', $course)
                    <!-- Participants Tab -->
                    <div x-show="currentTab === 'participants'" x-cloak class="p-8"
                         x-data="{
                            selectedEnrollUsers: [],
                            selectedUnenrollUsers: [],
                            searchTermEnroll: '',
                            searchTermUnenroll: '',
                            unEnrolledParticipantsData: [],
                            enrolledParticipantsData: [],
                            enrolledMeta: { total: 0, current_page: 1, last_page: 1, from: null, to: null },
                            availableMeta: { total: 0, current_page: 1, last_page: 1, from: null, to: null },
                            enrolledLoading: false,
                            availableLoading: false,
                            participantsLoaded: false,
                            searchTimers: {},
                            searchUrl: '{{ route('courses.participants.search', $course) }}',
                            initParticipants() {
                                this.$watch('currentTab', value => {
                                    if (value === 'participants' && !this.participantsLoaded) {
                                        this.participantsLoaded = true;
                                        this.loadParticipants('enrolled');
                                        this.loadParticipants('available');
                                    }
                                });
                            },
                            loadParticipants(type, page = 1) {
                                const isEnrolled = type === 'enrolled';
                                const params = new URLSearchParams({
                                    type,
                                    page,
                                    per_page: 25,
                                    q: isEnrolled ? this.searchTermUnenroll : this.searchTermEnroll
                                });

                                if (isEnrolled) {
                                    this.enrolledLoading = true;
                                } else {
                                    this.availableLoading = true;
                                }

                                fetch(`${this.searchUrl}?${params.toString()}`, {
                                    headers: { 'Accept': 'application/json' }
                                })
                                    .then(response => response.json())
                                    .then(payload => {
                                        if (isEnrolled) {
                                            this.enrolledParticipantsData = payload.data;
                                            this.enrolledMeta = payload.meta;
                                        } else {
                                            this.unEnrolledParticipantsData = payload.data;
                                            this.availableMeta = payload.meta;
                                        }
                                    })
                                    .finally(() => {
                                        if (isEnrolled) {
                                            this.enrolledLoading = false;
                                        } else {
                                            this.availableLoading = false;
                                        }
                                    });
                            },
                            debouncedLoad(type) {
                                clearTimeout(this.searchTimers[type]);
                                this.searchTimers[type] = setTimeout(() => this.loadParticipants(type), 350);
                            }
                        }"
                         x-init="initParticipants()">
                        <div class="mb-8">
                            <h3 class="text-2xl font-bold text-gray-900">Manajemen Peserta Kursus</h3>
                            <p class="text-gray-600 mt-1">Kelola pendaftaran dan akses peserta kursus</p>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <!-- Enrolled Participants -->
                            <div class="bg-warning-soft rounded-2xl p-6 border border-warning/20">
                                <div class="flex items-center mb-6">
                                    <div class="w-10 h-10 bg-warning/10 rounded-xl flex items-center justify-center mr-3">
                                        <svg class="w-5 h-5 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-lg font-bold text-gray-900">Peserta Terdaftar</h4>
                                        <p class="text-sm text-gray-600" x-text="`${enrolledMeta.total} peserta aktif`"></p>
                                    </div>
                                </div>

                                    <form id="unenroll-form" method="POST" action="{{ route('courses.unenroll_mass', $course) }}" onsubmit="return confirm('Anda yakin ingin mencabut akses peserta terpilih?');">
                                        @csrf @method('DELETE')
                                        <template x-for="userId in selectedUnenrollUsers" :key="`unenroll-${userId}`">
                                            <input type="hidden" name="user_ids[]" :value="userId">
                                        </template>

                                        <!-- Search Input -->
                                        <div class="mb-4">
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                                    </svg>
                                                </div>
                                                <input type="text" x-model="searchTermUnenroll" @input="debouncedLoad('enrolled')" placeholder="Cari peserta terdaftar..." class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-xl leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-bass-red focus:border-bass-red">
                                            </div>
                                        </div>

                                        <!-- Participants List -->
                                        <div class="space-y-3 mb-6 max-h-80 overflow-y-auto">
                                            <div x-show="enrolledLoading" class="text-center py-8">
                                                <p class="text-gray-500 font-medium">Memuat peserta...</p>
                                            </div>

                                            <template x-for="participant in enrolledParticipantsData" :key="participant.id">
                                                <div class="flex items-center p-3 bg-white rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors">
                                                    <input type="checkbox" :value="participant.id" x-model="selectedUnenrollUsers" class="mr-3 rounded border-gray-300 text-bass-red focus:ring-bass-red">
                                                    <div class="flex items-center space-x-3">
                                                        <div class="w-8 h-8 bg-navy rounded-full flex items-center justify-center">
                                                            <span class="text-white text-sm font-semibold" x-text="participant.name.charAt(0).toUpperCase()"></span>
                                                        </div>
                                                        <div>
                                                            <p class="font-medium text-gray-900" x-text="participant.name"></p>
                                                            <p class="text-sm text-gray-600" x-text="participant.email"></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>

                                            <template x-if="!enrolledLoading && enrolledParticipantsData.length === 0">
                                                <div class="text-center py-8">
                                                    <p class="text-gray-500 font-medium">Belum ada peserta terdaftar atau tidak ada hasil pencarian</p>
                                                </div>
                                            </template>
                                        </div>

                                        <div class="flex items-center justify-between gap-3 mb-6 text-sm text-gray-600">
                                            <span x-text="enrolledMeta.total > 0 ? `Menampilkan ${enrolledMeta.from}-${enrolledMeta.to} dari ${enrolledMeta.total}` : 'Tidak ada data'"></span>
                                            <div class="flex gap-2">
                                            <button type="button" @click="loadParticipants('enrolled', enrolledMeta.current_page - 1)" :disabled="enrolledMeta.current_page <= 1 || enrolledLoading" class="px-3 py-1.5 bg-white border border-gray-300 rounded-lg disabled:opacity-50">Prev</button>
                                            <button type="button" @click="loadParticipants('enrolled', enrolledMeta.current_page + 1)" :disabled="enrolledMeta.current_page >= enrolledMeta.last_page || enrolledLoading" class="px-3 py-1.5 bg-white border border-gray-300 rounded-lg disabled:opacity-50">Next</button>
                                            </div>
                                        </div>

                                        <button type="submit" x-bind:disabled="selectedUnenrollUsers.length === 0"
                                                :class="selectedUnenrollUsers.length === 0 ? 'opacity-50 cursor-not-allowed' : ''"
                                                class="w-full inline-flex justify-center items-center px-4 py-3 bg-bass-red text-white font-semibold rounded-xl hover:bg-bass-red-hover shadow-lg hover:shadow-xl transition-all duration-200">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            Cabut Akses Terpilih
                                        </button>
                                    </form>
                            </div>

                            <!-- Available Participants -->
                            <div class="bg-info-soft rounded-2xl p-6 border border-navy/20">
                                <div class="flex items-center mb-6">
                                    <div class="w-10 h-10 bg-navy rounded-xl flex items-center justify-center mr-3">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-lg font-bold text-navy">Daftarkan Peserta</h4>
                                        <p class="text-sm text-navy/70" x-text="`${availableMeta.total} calon peserta tersedia`"></p>
                                    </div>
                                </div>

                                <form id="enroll-form" method="POST" action="{{ route('courses.enroll', $course) }}">
                                    @csrf
                                    <template x-for="userId in selectedEnrollUsers" :key="`enroll-${userId}`">
                                        <input type="hidden" name="user_ids[]" :value="userId">
                                    </template>

                                    <!-- Search Input -->
                                    <div class="mb-4">
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                                </svg>
                                            </div>
                                            <input type="text" x-model="searchTermEnroll" @input="debouncedLoad('available')" placeholder="Cari calon peserta..." class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-xl leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-bass-red focus:border-bass-red">
                                        </div>
                                    </div>

                                    <!-- Available Users List -->
                                    <div class="space-y-3 mb-6 max-h-80 overflow-y-auto">
                                        <div x-show="availableLoading" class="text-center py-8">
                                            <p class="text-navy font-medium">Memuat calon peserta...</p>
                                        </div>

                                        <template x-for="user in unEnrolledParticipantsData" :key="user.id">
                                            <div class="flex items-center p-3 bg-white rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors">
                                                <input type="checkbox" :value="user.id" x-model="selectedEnrollUsers" class="mr-3 rounded border-gray-300 text-bass-red focus:ring-bass-red">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-8 h-8 bg-navy rounded-full flex items-center justify-center">
                                                        <span class="text-white text-sm font-semibold" x-text="user.name.charAt(0).toUpperCase()"></span>
                                                    </div>
                                                    <div>
                                                        <p class="font-medium text-gray-900" x-text="user.name"></p>
                                                        <p class="text-sm text-gray-600" x-text="user.email"></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>

                                        <template x-if="!availableLoading && unEnrolledParticipantsData.length === 0">
                                            <div class="text-center py-8">
                                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                </div>
                                                <p class="text-gray-600 font-medium">Semua pengguna sudah terdaftar atau tidak ada hasil</p>
                                            </div>
                                        </template>
                                    </div>

                                    <div class="flex items-center justify-between gap-3 mb-6 text-sm text-gray-600">
                                        <span x-text="availableMeta.total > 0 ? `Menampilkan ${availableMeta.from}-${availableMeta.to} dari ${availableMeta.total}` : 'Tidak ada data'"></span>
                                        <div class="flex gap-2">
                                            <button type="button" @click="loadParticipants('available', availableMeta.current_page - 1)" :disabled="availableMeta.current_page <= 1 || availableLoading" class="px-3 py-1.5 bg-white border border-gray-300 rounded-lg disabled:opacity-50">Prev</button>
                                            <button type="button" @click="loadParticipants('available', availableMeta.current_page + 1)" :disabled="availableMeta.current_page >= availableMeta.last_page || availableLoading" class="px-3 py-1.5 bg-white border border-gray-300 rounded-lg disabled:opacity-50">Next</button>
                                        </div>
                                    </div>

                                    <button type="submit" x-bind:disabled="selectedEnrollUsers.length === 0"
                                            :class="selectedEnrollUsers.length === 0 ? 'opacity-50 cursor-not-allowed' : ''"
                                            class="w-full inline-flex justify-center items-center px-4 py-3 bg-bass-red text-white font-semibold rounded-xl hover:bg-bass-red-hover shadow-lg hover:shadow-xl transition-all duration-200">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                        </svg>
                                        Daftarkan Terpilih
                                    </button>
                                </form>
                            </div>
                        </div>

                        {{-- Riwayat Export (collapsible) --}}
                        <div x-data="{ showExportHistory: false }" class="mt-8">
                            <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                                <div class="flex items-center justify-between px-5 py-4">
                                    <button @click="showExportHistory = !showExportHistory"
                                            class="flex items-center gap-3 text-left hover:opacity-70 transition-opacity">
                                        <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0" :class="{ '!bg-bass-red/10': showExportHistory }">
                                            <svg class="w-4 h-4 text-gray-500" :class="{ '!text-bass-red': showExportHistory }" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                        </div>
                                        <span class="font-semibold text-sm text-gray-800">Riwayat Export</span>
                                        @if($exportHistories->isNotEmpty())
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">{{ $exportHistories->count() }} file</span>
                                        @endif
                                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': showExportHistory }" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </button>
                                    <button @click="$dispatch('open-export-modal')"
                                            class="inline-flex items-center px-3 py-1.5 bg-bass-red text-white text-xs font-semibold rounded-lg hover:bg-[#B91818] transition-colors shadow-sm">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        Export Baru
                                    </button>
                                </div>

                                <div x-show="showExportHistory" x-cloak
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 -translate-y-2"
                                     x-transition:enter-end="opacity-100 translate-y-0"
                                     x-transition:leave="transition ease-in duration-100"
                                     x-transition:leave-start="opacity-100"
                                     x-transition:leave-end="opacity-0">
                                    <div class="border-t border-gray-100"></div>
                                    @if($exportHistories->isEmpty())
                                        <div class="text-center py-10 px-5">
                                            <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                            </div>
                                            <p class="text-sm font-medium text-gray-600">Belum ada riwayat export</p>
                                            <p class="text-xs text-gray-400 mt-1">File Excel akan muncul di sini setelah Anda melakukan export peserta</p>
                                        </div>
                                    @else
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-gray-100">
                                                <thead class="bg-gray-50/50">
                                                    <tr>
                                                        <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Waktu</th>
                                                        <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Filter</th>
                                                        <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Kelas</th>
                                                        <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                                        <th class="px-5 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-100">
                                                    @foreach($exportHistories as $history)
                                                        <tr class="hover:bg-gray-50/50 transition-colors">
                                                            <td class="px-5 py-3 text-sm text-gray-700 whitespace-nowrap">
                                                                {{ $history->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}
                                                            </td>
                                                            <td class="px-5 py-3 text-sm text-gray-600">
                                                                {{ $history->filter === 'all' ? 'Semua Peserta' : 'Per Kelas' }}
                                                            </td>
                                                            <td class="px-5 py-3 text-sm text-gray-600">
                                                                {{ $history->courseClass?->name ?? '-' }}
                                                            </td>
                                                            <td class="px-5 py-3">
                                                                @if($history->isProcessing())
                                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-700 ring-1 ring-amber-200">
                                                                        <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                                                        </svg>
                                                                        Diproses
                                                                    </span>
                                                                @elseif($history->isDone())
                                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-success-soft text-success ring-1 ring-success/20">
                                                                        Selesai
                                                                    </span>
                                                                @else
                                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-700 ring-1 ring-red-200"
                                                                          title="{{ $history->error_message }}">
                                                                        Gagal
                                                                    </span>
                                                                @endif
                                                            </td>
                                                            <td class="px-5 py-3 text-right">
                                                                @if($history->isDone() && $history->fileExists())
                                                                    <a href="{{ route('exports.download', $history) }}"
                                                                       class="inline-flex items-center px-3 py-1.5 bg-navy text-white text-xs font-medium rounded-lg hover:bg-navy/90 transition-colors">
                                                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                                                        </svg>
                                                                        Download
                                                                    </a>
                                                                @else
                                                                    <span class="text-gray-300 text-xs">-</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endcan
            </div>
        </div>
    </div>

    {{-- ======================================================================
         EXPORT PARTICIPANTS MODAL
         ====================================================================== --}}
    @can('viewProgress', $course)
    <div
        x-data="{
            open: false,
            filter: '{{ auth()->user()->can('manage all courses') || auth()->user()->can('view progress reports') ? 'all' : 'class' }}',
            classId: '',
            count: 0,
            loading: false,
            init() {
                this.$watch('filter', () => this.fetchCount());
                this.$watch('classId', () => { if (this.filter === 'class') this.fetchCount(); });
                this.fetchCount();
            },
            async fetchCount() {
                this.loading = true;
                try {
                    const base = '{{ route('courses.participants.count', $course) }}';
                    const params = new URLSearchParams({ filter: this.filter });
                    if (this.filter === 'class' && this.classId) params.append('class_id', this.classId);
                    const res = await fetch(`${base}?${params}`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await res.json();
                    this.count = data.count;
                } catch(e) { this.count = '?'; }
                finally { this.loading = false; }
            }
        }"
        @open-export-modal.window="open = true; fetchCount()">

        <!-- Backdrop -->
        <div x-show="open" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="open = false"
             class="fixed inset-0 bg-black/50 z-40">
        </div>

        <!-- Modal Panel -->
        <div x-show="open" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" @click.stop>
                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 bg-navy rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Export Peserta</h3>
                    </div>
                    <button @click="open = false" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Body -->
                <form method="POST" action="{{ route('courses.export.participants', $course) }}" class="px-6 py-5 space-y-4">
                    @csrf
                    <input type="hidden" name="filter" x-bind:value="filter">
                    <input type="hidden" name="class_id" x-bind:value="classId">

                    <!-- Filter Select -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Filter Peserta</label>
                        <select x-model="filter"
                                class="w-full rounded-xl border-gray-300 shadow-sm focus:border-bass-red focus:ring-bass-red text-sm py-2.5">
                            @if(auth()->user()->can('manage all courses') || auth()->user()->can('view progress reports'))
                                <option value="all">Semua Peserta Kursus</option>
                            @endif
                            <option value="class">Per Kelas</option>
                        </select>
                    </div>

                    <!-- Class Select -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            Kelas
                            <span x-show="filter === 'all'" class="font-normal text-gray-400">(tidak diperlukan untuk semua)</span>
                        </label>
                        <select x-model="classId"
                                :disabled="filter === 'all'"
                                :class="filter === 'all' ? 'opacity-50 cursor-not-allowed bg-gray-50' : 'bg-white'"
                                class="w-full rounded-xl border-gray-300 shadow-sm focus:border-bass-red focus:ring-bass-red text-sm py-2.5">
                            <option value="">— Pilih Kelas —</option>
                            @foreach($course->periods as $period)
                                <option value="{{ $period->id }}">{{ $period->name }}</option>
                            @endforeach
                        </select>
                        <p x-show="filter === 'class' && !classId" class="mt-1 text-xs text-amber-600">
                            Pilih kelas terlebih dahulu
                        </p>
                    </div>

                    <!-- Participant Count Badge -->
                    <div class="flex items-center gap-2 p-3 bg-gray-50 border border-gray-200 rounded-xl">
                        <svg class="w-4 h-4 text-navy flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <span class="text-sm text-gray-700">
                            Total:
                            <span x-show="loading" class="inline-block w-10 h-4 bg-gray-200 rounded animate-pulse"></span>
                            <strong x-show="!loading" x-text="count" class="font-bold"></strong>
                            <span x-show="!loading"> peserta</span>
                        </span>
                    </div>

                    <!-- Info -->
                    <div class="flex gap-2 p-3 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-600">
                        <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Export akan diproses di latar belakang. Anda akan mendapat notifikasi ketika file siap diunduh.</span>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-3 pt-1">
                        <button type="button" @click="open = false"
                                class="flex-1 px-4 py-2.5 bg-gray-100 text-gray-700 font-medium text-sm rounded-xl hover:bg-gray-200 transition-colors">
                            Batal
                        </button>
                        <button type="submit"
                                :disabled="filter === 'class' && !classId"
                                :class="(filter === 'class' && !classId) ? 'opacity-50 cursor-not-allowed' : 'hover:bg-[#B91818]'"
                                class="flex-1 inline-flex justify-center items-center gap-2 px-4 py-2.5 bg-bass-red text-white font-semibold text-sm rounded-xl transition-colors shadow">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            Export Excel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endcan

    <script>
        function periodManager(courseId, initialPeriods) {
            return {
                selectedPeriods: [],
                searchTerm: '',
                selectAll: false,
                periods: initialPeriods || [],
                
                get filteredPeriods() {
                    if (!this.searchTerm) return this.periods;
                    return this.periods.filter(period => 
                        period.name.toLowerCase().includes(this.searchTerm.toLowerCase()) ||
                        (period.description && period.description.toLowerCase().includes(this.searchTerm.toLowerCase()))
                    );
                },
                
                toggleSelectAll() {
                    if (this.selectAll) {
                        this.selectedPeriods = this.filteredPeriods.map(p => p.id);
                    } else {
                        this.selectedPeriods = [];
                    }
                },
                
                deleteSelected() {
                    if (this.selectedPeriods.length === 0) {
                        alert('Pilih kelas yang ingin dihapus');
                        return;
                    }
                    
                    if (confirm(`Yakin ingin menghapus ${this.selectedPeriods.length} kelas yang dipilih?`)) {
                        // Create forms and submit them
                        this.selectedPeriods.forEach(periodId => {
                            this.deletePeriod(periodId);
                        });
                    }
                },
                
                deletePeriod(periodId) {
                    if (confirm('Yakin ingin menghapus kelas ini?')) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = `/courses/${courseId}/periods/${periodId}`;
                        
                        // Add CSRF token
                        const tokenInput = document.createElement('input');
                        tokenInput.type = 'hidden';
                        tokenInput.name = '_token';
                        tokenInput.value = '{{ csrf_token() }}';
                        form.appendChild(tokenInput);
                        
                        // Add method override
                        const methodInput = document.createElement('input');
                        methodInput.type = 'hidden';
                        methodInput.name = '_method';
                        methodInput.value = 'DELETE';
                        form.appendChild(methodInput);
                        
                        document.body.appendChild(form);
                        form.submit();
                    }
                }
            }
        }

        function isLessonUnlocked(lesson, index) {
            // Simple unlock logic - you can modify this based on your requirements
            // For now, lessons are unlocked sequentially
            return true; // or implement your unlock logic here
        }
    </script>
</x-app-layout>
