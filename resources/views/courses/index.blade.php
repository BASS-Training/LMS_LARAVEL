<x-app-layout>
    <x-slot name="header">
        <div class="bg-navy -mx-4 -my-2 px-4 py-8 sm:px-6 lg:px-8 rounded-2xl shadow-lg">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="text-3xl font-bold text-white mb-2 flex items-center">
                        <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                        Manajemen Kursus
                    </h2>
                    <p class="text-white/80 text-lg">Kelola dan pantau semua kursus pembelajaran Anda</p>
                </div>
                @can('create', App\Models\Course::class)
                    <a href="{{ route('courses.create') }}" class="inline-flex items-center px-6 py-3 bg-bass-red text-white border border-transparent rounded-xl font-semibold text-sm uppercase tracking-wider hover:bg-bass-red-hover hover:scale-105 focus:outline-none focus:ring-4 focus:ring-bass-red/20 transition-all duration-200 shadow-lg hover:shadow-xl">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Tambah Kursus Baru
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Success Alert -->
            @if (session('success'))
                <div class="mb-8 bg-success-soft border-l-4 border-success rounded-r-xl shadow-md" role="alert">
                    <div class="flex items-center p-6">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-success" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-success">Berhasil!</h3>
                            <p class="text-success">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Error Alert -->
            @if (session('error'))
                <div class="mb-8 bg-error-soft border-l-4 border-error rounded-r-xl shadow-md" role="alert">
                    <div class="flex items-center p-6">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-error" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-error">Terjadi Kesalahan</h3>
                            <p class="text-error">{{ session('error') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Search Form -->
            <form method="GET" action="{{ route('courses.index') }}" class="mb-6">
                <div class="flex gap-3 items-center">
                    <div class="relative flex-1 max-w-md">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text"
                               name="q"
                               value="{{ $search }}"
                               placeholder="Cari judul atau deskripsi kursus..."
                               minlength="2"
                               maxlength="100"
                               class="block w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-bass-red focus:border-bass-red transition">
                    </div>
                    <button type="submit"
                            class="inline-flex items-center px-5 py-2.5 bg-bass-red text-white text-sm font-medium rounded-xl hover:bg-bass-red-hover focus:ring-4 focus:ring-bass-red/20 transition-all duration-200 shadow-md">
                        Cari
                    </button>
                    @if ($search)
                        <a href="{{ route('courses.index') }}"
                           class="inline-flex items-center px-4 py-2.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-200 transition-all duration-200">
                            Reset
                        </a>
                    @endif
                </div>
                @if ($search)
                    <p class="mt-2 text-sm text-gray-500">
                        Menampilkan hasil untuk: <span class="font-semibold text-gray-700">{{ $search }}</span>
                    </p>
                @endif
            </form>

            <!-- Main Content -->
            <div class="bg-white overflow-hidden shadow-2xl rounded-3xl border border-gray-100">
                <div class="p-8">
                    @if ($courses->isEmpty())
                        <!-- Empty State -->
                        <div class="text-center py-16">
                            <div class="mx-auto w-32 h-32 bg-gray-100 rounded-full flex items-center justify-center mb-8">
                                <svg class="w-16 h-16 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                            </div>
                            @if ($search)
                                <h3 class="text-2xl font-bold text-gray-900 mb-4">Tidak Ada Hasil</h3>
                                <p class="text-lg text-gray-500 mb-8 max-w-md mx-auto">Tidak ada kursus yang cocok dengan kata kunci "<strong>{{ $search }}</strong>".</p>
                                <a href="{{ route('courses.index') }}" class="inline-flex items-center px-8 py-4 bg-bass-red text-white font-semibold rounded-2xl hover:bg-bass-red-hover focus:ring-4 focus:ring-bass-red/20 transition-all duration-300 shadow-xl hover:shadow-2xl hover:scale-105">
                                    Lihat Semua Kursus
                                </a>
                            @else
                                <h3 class="text-2xl font-bold text-gray-900 mb-4">Belum Ada Kursus</h3>
                                <p class="text-lg text-gray-500 mb-8 max-w-md mx-auto">Mulai perjalanan pembelajaran dengan membuat kursus pertama Anda!</p>
                                @can('create', App\Models\Course::class)
                                    <a href="{{ route('courses.create') }}" class="inline-flex items-center px-8 py-4 bg-bass-red text-white font-semibold rounded-2xl hover:bg-bass-red-hover focus:ring-4 focus:ring-bass-red/20 transition-all duration-300 shadow-xl hover:shadow-2xl hover:scale-105">
                                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                        Buat Kursus Pertama
                                    </a>
                                @endcan
                            @endif
                        </div>
                    @else
                        <!-- Courses Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-3 gap-8">
                            @foreach ($courses as $course)
                                <div class="group bg-white rounded-2xl shadow-lg hover:shadow-2xl border border-gray-200 overflow-hidden transition-all duration-500 hover:scale-[1.02] hover:border-bass-red/30 flex flex-col">
                                    <!-- Course Image -->
                                    <div class="relative overflow-hidden">
                                        @if ($course->thumbnail)
                                            <img src="{{ asset('storage/' . $course->thumbnail) }}" 
                                                 alt="{{ $course->title }}" 
                                                 class="w-full h-56 object-cover group-hover:scale-110 transition-transform duration-700">
                                        @else
                                            <div class="w-full h-56 bg-gray-100 flex items-center justify-center">
                                                <div class="text-center">
                                                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                    <p class="text-gray-500 font-medium">Tidak Ada Gambar</p>
                                                </div>
                                            </div>
                                        @endif
                                        
                                        <!-- Status Badge -->
                                        <div class="absolute top-4 right-4">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider shadow-lg
                                                {{ $course->status === 'published' 
                                                    ? 'bg-success-soft text-success border border-success/20'
                                                    : 'bg-gray-100 text-gray-700 border border-gray-200' }}">
                                                @if($course->status === 'published')
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    Published
                                                @else
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    Draft
                                                @endif
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Course Content -->
                                    <div class="p-6 flex flex-col flex-1">
                                        <div class="mb-4">
                                            <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-bass-red transition-colors duration-300 line-clamp-2">
                                                {{ $course->title }}
                                            </h3>
                                            <p class="text-gray-600 text-sm leading-relaxed line-clamp-3 mb-4">
                                                {{ Str::limit($course->description, 120) }}
                                            </p>
                                        </div>

                                        <!-- Instructor Info -->
                                        <div class="flex items-center mb-6 p-3 bg-gray-50 rounded-xl">
                                            <div class="flex-shrink-0">
                                                <div class="w-10 h-10 bg-navy rounded-full flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="ml-3 flex-1 min-w-0">
                                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Instruktur</p>
                                                <p class="text-sm font-semibold text-gray-900 truncate">
                                                    {{ $course->instructors->pluck('name')->join(', ') ?: 'Belum ditentukan' }}
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div class="mt-auto flex flex-wrap items-center gap-2 pt-1">
                                            <!-- View Button -->
                                            <a href="{{ route('courses.show', $course) }}"
                                                class="inline-flex items-center gap-2 h-9 px-3 shrink-0 whitespace-nowrap bg-navy text-white text-sm font-medium rounded-lg border border-transparent hover:bg-navy-light focus:ring-4 focus:ring-bass-red/20 transition-all duration-200 shadow-md hover:shadow-lg">
                                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                                Lihat
                                            </a>

                                            <!-- Edit Button -->
                                            @can('update', $course)
                                                <a href="{{ route('courses.edit', $course) }}"
                                                    class="inline-flex items-center gap-2 h-9 px-3 shrink-0 whitespace-nowrap bg-warning text-white text-sm font-medium rounded-lg border border-transparent hover:bg-amber-600 focus:ring-4 focus:ring-warning/30 transition-all duration-200 shadow-md hover:shadow-lg">
                                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                    Edit
                                                </a>
                                            @endcan

                                            <!-- Duplicate Button -->
                                            @can('duplicate', App\Models\Course::class)
                                                <form action="{{ route('courses.duplicate', $course) }}" method="POST" class="contents" onsubmit="return confirm('Anda yakin ingin menduplikasi kursus ini?');">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center gap-2 h-9 px-3 shrink-0 whitespace-nowrap bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 focus:ring-4 focus:ring-gray-200 transition-all duration-200 shadow-md hover:shadow-lg">
                                                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                        </svg>
                                                        Duplikat
                                                    </button>
                                                </form>
                                            @endcan

                                            <!-- Delete Button -->
                                            @can('delete', $course)
                                                <form action="{{ route('courses.destroy', $course) }}" method="POST" class="contents" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kursus ini? Tindakan ini tidak dapat dibatalkan.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex items-center gap-2 h-9 px-3 shrink-0 whitespace-nowrap bg-bass-red text-white text-sm font-medium rounded-lg border border-transparent hover:bg-bass-red-hover focus:ring-4 focus:ring-bass-red/30 transition-all duration-200 shadow-md hover:shadow-lg">
                                                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                        Hapus
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        <div class="mt-12">
                            {{ $courses->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Custom Styles -->
    <style>
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .group:hover .group-hover\:scale-110 {
            transform: scale(1.1);
        }
    </style>
</x-app-layout>
