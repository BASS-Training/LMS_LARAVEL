<x-app-layout>
    <x-slot name="header">
        <div class="bg-navy text-white -mx-6 -mt-6 mb-6 px-6 py-8">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <div class="bg-white/20 p-3 rounded-lg">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-6 4h6m-6 4h6m-6 4h3M9 3h6v4H9V3z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-3xl font-bold">Manajemen Kuis</h2>
                        <p class="text-white/80 mt-1">Kelola dan monitor semua kuis Anda</p>
                    </div>
                </div>
                @can('manage-courses')
                    <a href="{{ route('quizzes.import-form') }}"
                       class="bg-bass-red text-white px-6 py-3 rounded-lg font-semibold shadow-lg hover:bg-bass-red-hover transition-colors duration-200 flex items-center space-x-2 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-navy">
                         <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                         </svg>
                        <span>Import Kuis</span>
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <!-- Custom Styles -->
    <style>
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .btn-action {
            transition: all 0.2s ease;
        }
        .btn-action:hover {
            transform: scale(1.05);
        }
    </style>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Flash Messages -->
            @if (session('success'))
                <div class="bg-success-soft border-l-4 border-success p-4 mb-6 rounded-r-lg">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-success mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <p class="font-medium text-success">Berhasil!</p>
                            <p class="text-success">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="bg-error-soft border-l-4 border-error p-4 mb-6 rounded-r-lg">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-error mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.74-3L13.74 4a2 2 0 00-3.48 0L3.33 16a2 2 0 001.74 3z" />
                        </svg>
                        <div>
                            <p class="font-medium text-error">Error!</p>
                            <p class="text-error">{{ session('error') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Stats Overview -->
            @if (!$quizzes->isEmpty())
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total Kuis -->
                    <div class="bg-white rounded-xl shadow-lg p-6 card-hover">
                        <div class="flex items-center">
                            <div class="bg-navy p-3 rounded-lg text-white">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 3h6v4H9V3z" /></svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Kuis</p>
                                <p class="text-2xl font-bold text-gray-900">{{ $quizzes->count() }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Kuis Published -->
                    <div class="bg-white rounded-xl shadow-lg p-6 card-hover">
                        <div class="flex items-center">
                            <div class="bg-success p-3 rounded-lg text-white">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0c-1.5 4-4.5 6-9 6s-7.5-2-9-6c1.5-4 4.5-6 9-6s7.5 2 9 6z" /></svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Published</p>
                                <p class="text-2xl font-bold text-gray-900">{{ $quizzes->where('status', 'published')->count() }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Kuis Draft -->
                    <div class="bg-white rounded-xl shadow-lg p-6 card-hover">
                        <div class="flex items-center">
                            <div class="bg-warning p-3 rounded-lg text-white">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.5-8.5a2.12 2.12 0 013 3L12 14l-4 1 1-4 6.5-6.5z" /></svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Draft</p>
                                <p class="text-2xl font-bold text-gray-900">{{ $quizzes->where('status', 'draft')->count() }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Total Soal -->
                    <div class="bg-white rounded-xl shadow-lg p-6 card-hover">
                        <div class="flex items-center">
                            <div class="bg-bass-red p-3 rounded-lg text-white">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.23 9a4 4 0 117.54 2c-.75 1-2.77 1.5-2.77 3m-1 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Soal</p>
                                <p class="text-2xl font-bold text-gray-900">{{ $quizzes->sum(function($quiz) { return $quiz->questions->count(); }) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Quiz Cards -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                @if ($quizzes->isEmpty())
                    <!-- Empty State -->
                    <div class="text-center py-16">
                        <div class="bg-gray-100 rounded-full w-24 h-24 flex items-center justify-center mx-auto mb-6">
                            <svg class="h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 3h6v4H9V3z" /></svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">Belum ada kuis</h3>
                        <p class="text-gray-500 mb-6">Mulai buat kuis pertama Anda untuk mengukur pemahaman peserta</p>
                        @can('manage-courses')
                            <a href="{{ route('quizzes.import-form') }}"
                               class="inline-flex items-center px-6 py-3 bg-bass-red text-white font-semibold rounded-lg hover:bg-bass-red-hover transition-colors duration-200">
                                 <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                Import Kuis Pertama
                            </a>
                        @endcan
                    </div>
                @else
                    <!-- Quiz Header -->
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900">Daftar Kuis</h3>
                            <div class="text-sm text-gray-500">
                                {{ $quizzes->count() }} kuis ditemukan
                            </div>
                        </div>
                    </div>

                    <!-- Quiz Grid -->
                    <div class="p-6">
                        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                            @foreach ($quizzes as $quiz)
                                <div class="bg-white border border-gray-200 rounded-xl shadow-sm card-hover overflow-hidden">
                                    <!-- Quiz Header -->
                                    <div class="p-6 pb-4">
                                        <div class="flex items-start justify-between mb-4">
                                            <div class="flex-1">
                                                <h4 class="text-lg font-semibold text-gray-900 mb-2 line-clamp-2">
                                                    {{ $quiz->title }}
                                                </h4>
                                                <p class="text-sm text-gray-600 mb-3">
                                                     <svg class="inline h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m7-10a4 4 0 100-8 4 4 0 000 8z" /></svg>
                                                    Oleh: {{ $quiz->instructor->name }}
                                                </p>
                                                @if($quiz->lesson && $quiz->lesson->course)
                                                    <p class="text-sm text-gray-500">
                                                         <svg class="inline h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 19.5A2.5 2.5 0 016.5 17H20V5H6.5A2.5 2.5 0 004 7.5v12zM4 19.5A2.5 2.5 0 006.5 22H20v-5" /></svg>
                                                        {{ $quiz->lesson->course->title }}
                                                    </p>
                                                @endif
                                            </div>
                                             <span class="ml-3 px-3 py-1 text-xs font-semibold rounded-full {{ $quiz->status === 'published' ? 'bg-success-soft text-success' : 'bg-warning-soft text-warning' }}">
                                                {{ $quiz->status === 'published' ? 'Published' : 'Draft' }}
                                            </span>
                                        </div>

                                        <!-- Quiz Stats -->
                                        <div class="grid grid-cols-2 gap-4 mb-4">
                                             <div class="bg-info-soft p-3 rounded-lg text-center">
                                                 <div class="text-lg font-bold text-navy">{{ $quiz->questions->count() }}</div>
                                                 <div class="text-xs text-info">Soal</div>
                                            </div>
                                             <div class="bg-gray-100 p-3 rounded-lg text-center">
                                                 <div class="text-lg font-bold text-navy">{{ $quiz->total_marks }}</div>
                                                 <div class="text-xs text-gray-600">Total Nilai</div>
                                            </div>
                                        </div>

                                        <!-- Additional Info -->
                                        <div class="space-y-2 text-sm text-gray-600">
                                            <div class="flex items-center justify-between">
                                                <span class="flex items-center">
                                                     <svg class="h-4 w-4 mr-2 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8a4 4 0 100 8 4 4 0 000-8zm0-5v2m0 14v2m9-9h-2M5 12H3" /></svg>
                                                    Passing Grade
                                                </span>
                                                <span class="font-medium">{{ $quiz->pass_marks }} poin</span>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span class="flex items-center">
                                                     <svg class="h-4 w-4 mr-2 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                    Batas Waktu
                                                </span>
                                                <span class="font-medium">
                                                    {{ $quiz->time_limit ? $quiz->time_limit . ' menit' : 'Tidak ada' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="bg-gray-50 px-6 py-4">
                                        <div class="flex items-center justify-between space-x-2">
                                            <a href="{{ route('quizzes.show', $quiz) }}" 
                                               class="flex-1 bg-bass-red text-white text-center py-2 px-4 rounded-lg font-medium hover:bg-bass-red-hover btn-action">
                                                 Lihat
                                            </a>
                                            
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Quick Actions -->
            @if (!$quizzes->isEmpty())
                <div class="mt-8 bg-gray-50 border border-gray-200 rounded-xl p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Aksi Cepat</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @can('manage-courses')
                            <a href="{{ route('quizzes.import-form') }}"
                               class="flex items-center p-4 bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200">
                                 <div class="bg-bass-red-soft p-3 rounded-lg mr-4 text-bass-red">
                                     <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-900">Import Kuis</h4>
                                    <p class="text-sm text-gray-500">Tambah kuis dari template Excel</p>
                                </div>
                            </a>
                        @endcan
                        
                        <a href="{{ route('courses.index') }}" 
                           class="flex items-center p-4 bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200">
                             <div class="bg-info-soft p-3 rounded-lg mr-4 text-navy">
                                 <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 19.5A2.5 2.5 0 016.5 17H20V5H6.5A2.5 2.5 0 004 7.5v12zM4 19.5A2.5 2.5 0 006.5 22H20v-5" /></svg>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Kelola Kursus</h4>
                                <p class="text-sm text-gray-500">Atur kursus dan materi</p>
                            </div>
                        </a>
                        
                        <a href="#" onclick="window.print()" 
                           class="flex items-center p-4 bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200">
                             <div class="bg-gray-100 p-3 rounded-lg mr-4 text-gray-600">
                                 <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0l-4-4m4 4l4-4M5 19h14" /></svg>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Export Data</h4>
                                <p class="text-sm text-gray-500">Unduh laporan kuis</p>
                            </div>
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>

</x-app-layout>
