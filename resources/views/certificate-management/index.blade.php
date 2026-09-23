<x-app-layout>
    <x-slot name="header">
        <div class="bg-navy -mx-4 -my-2 px-4 py-8 sm:px-6 lg:px-8 rounded-2xl shadow-lg">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="text-white text-3xl font-bold leading-tight">
                        {{ __('Manajemen Sertifikat') }}
                    </h2>
                    <p class="text-white/80 mt-2">
                        {{ __('Kelola semua sertifikat peserta berdasarkan kursus') }}
                    </p>
                </div>
            </div>
        </div>
    </x-slot>
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header Actions -->
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div class="flex space-x-4">
                     <a href="{{ route('certificate-management.analytics') }}"
                       class="bg-navy hover:bg-navy-light text-white font-medium py-2 px-4 rounded-md transition duration-150 ease-in-out">
                        Analytics
                    </a>
                </div>
            </div>
        </div>

        <!-- Analytics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-navy rounded-md flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Sertifikat</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ number_format($analytics['total_certificates']) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-success rounded-md flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Bulan Ini</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ number_format($analytics['certificates_this_month']) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-gray-600 rounded-md flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Kursus Aktif</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ number_format($analytics['courses_with_certificates']) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-bass-red rounded-md flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Terbaru</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ $analytics['recent_certificates']->count() }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters and Actions -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="p-6">
                <form method="GET" action="{{ route('certificate-management.index') }}" class="flex flex-wrap gap-4">
                    <!-- Search by Name -->
                    <div class="flex-1 min-w-64">
                        <input type="text" name="search" value="{{ request('search') }}" 
                               placeholder="Cari nama peserta..." 
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-bass-red focus:ring-bass-red">
                    </div>
                    
                    <!-- Filter by Course -->
                    <div class="flex-1 min-w-64">
                        <select name="course_id" 
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-bass-red focus:ring-bass-red">
                            <option value="">Semua Kursus</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>
                                    {{ $course->title }} ({{ $course->certificates_count }} sertifikat)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="flex gap-2">
                        <button type="submit" 
                                class="bg-bass-red hover:bg-bass-red-hover text-white font-medium py-2 px-4 rounded-md transition duration-150 ease-in-out">
                            Cari
                        </button>
                        <a href="{{ route('certificate-management.index') }}" 
                           class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-md transition duration-150 ease-in-out">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Course Quick Navigation -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Navigasi Cepat Berdasarkan Kursus</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    @foreach($courses->take(8) as $course)
                        <a href="{{ route('certificate-management.by-course', $course) }}" 
                           class="block p-4 bg-gray-50 hover:bg-gray-100 rounded-lg transition duration-150 ease-in-out">
                            <div class="font-medium text-gray-900 truncate">{{ $course->title }}</div>
                            <div class="text-sm text-gray-500">{{ $course->certificates_count }} sertifikat</div>
                        </a>
                    @endforeach
                </div>
                @if($courses->count() > 8)
                    <div class="mt-4 text-center">
                        <span class="text-sm text-gray-500">Dan {{ $courses->count() - 8 }} kursus lainnya...</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Bulk Actions -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    Aksi Massal
                </h3>
                <div class="flex flex-wrap gap-4">
                    <!-- Actions for selected certificates -->
                    <div id="selected-actions" style="display: none;" class="flex gap-4 items-center border-r pr-4">
                        <span class="text-sm text-gray-600">
                            <span id="selected-count">0</span> sertifikat dipilih
                        </span>
                        <button onclick="bulkAction('download')"
                                class="bg-navy hover:bg-navy-light text-white font-medium py-2 px-4 rounded-md transition duration-150 ease-in-out">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Download Terpilih
                        </button>
                        <button onclick="openBulkUpdateTemplateModal()"
                                class="bg-navy hover:bg-navy-light text-white font-medium py-2 px-4 rounded-md transition duration-150 ease-in-out">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            Update Template
                        </button>
                        <button onclick="bulkAction('delete')"
                                class="bg-neutral-900 hover:bg-black text-white font-medium py-2 px-4 rounded-md transition duration-150 ease-in-out">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            Hapus Terpilih
                        </button>
                    </div>

                    <!-- Actions for all certificates -->
                    <div class="flex gap-4">
                        <button onclick="downloadAllCertificates()"
                                class="bg-bass-red hover:bg-bass-red-hover text-white font-medium py-2 px-4 rounded-md transition duration-150 ease-in-out">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Download Semua ({{ $certificates->total() }} sertifikat)
                        </button>
                    </div>
                </div>

                <!-- Download Progress Indicator -->
                <div id="download-progress" style="display: none;" class="mt-4">
                    <div class="bg-info-soft border border-navy/20 rounded-md p-4">
                        <div class="flex items-center">
                            <svg class="animate-spin h-5 w-5 text-navy mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-navy font-medium" id="progress-text">Memproses download...</span>
                        </div>
                        <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                            <div id="progress-bar" class="bg-navy h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                        <p class="text-xs text-navy mt-2">
                            File akan otomatis terdownload setelah selesai. Harap jangan menutup halaman ini.
                        </p>
                    </div>
                </div>

                <!-- Bulk Update Progress Indicator -->
                <div id="bulk-update-progress" style="display: none;" class="mt-4">
                    <div class="bg-warning-soft border border-warning/40 rounded-md p-4">
                        <div class="flex items-center">
                            <svg class="animate-spin h-5 w-5 text-warning mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-warning font-medium" id="bulk-update-text">Memproses update template...</span>
                        </div>
                        <div class="mt-2 w-full bg-warning-soft rounded-full h-2">
                            <div id="bulk-update-bar" class="bg-warning h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                        <p class="text-xs text-warning mt-2">
                            Proses berjalan di background. Anda bisa menunggu di halaman ini.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Certificates Table -->
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Daftar Sertifikat
                        @if(request('course_id') || request('search'))
                            <span class="text-sm font-normal text-gray-500">
                                ({{ $certificates->total() }} hasil)
                            </span>
                        @endif
                    </h3>
                    <div class="flex items-center">
                        <input type="checkbox" id="select-all" class="rounded border-gray-300 text-bass-red shadow-sm focus:border-bass-red focus:ring focus:ring-bass-red/50 focus:ring-opacity-50">
                        <label for="select-all" class="ml-2 text-sm text-gray-600">
                            Pilih Semua
                            <span class="text-xs text-gray-400">(semua halaman)</span>
                        </label>
                    </div>
                </div>
            </div>
            
            @if($certificates->count() > 0)
                <ul class="divide-y divide-gray-200">
                    @foreach($certificates as $certificate)
                        <li class="px-6 py-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <input type="checkbox" name="certificate_ids[]" value="{{ $certificate->id }}" 
                                           class="certificate-checkbox rounded border-gray-300 text-bass-red shadow-sm focus:border-bass-red focus:ring focus:ring-bass-red/50 focus:ring-opacity-50">
                                    <div class="ml-4">
                                        <div class="flex items-center">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $certificate->user->name }}
                                            </div>
                                            <div class="ml-2 flex-shrink-0">
                                                @if($certificate->fileExists())
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success-soft text-success">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                        Tersedia
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-error-soft text-error">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                        File Hilang
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                                            {{ $certificate->course->title }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            {{ $certificate->issued_at->format('d M Y H:i') }} &bull;
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                                            {{ $certificate->certificate_code }}
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-2">
                                    @if($certificate->fileExists())
                                         <a href="{{ route('certificates.download', $certificate) }}"
                                            class="bg-success-soft hover:bg-success/20 text-success font-medium py-1 px-3 rounded text-sm transition duration-150 ease-in-out"
                                            title="Lihat Sertifikat">
                                            Download
                                        </a>
                                    @endif
                                    
                                     <a href="{{ route('certificates.verify', $certificate->certificate_code) }}"
                                        target="_blank"
                                        class="bg-info-soft hover:bg-gray-200 text-navy font-medium py-1 px-3 rounded text-sm transition duration-150 ease-in-out"
                                        title="Verifikasi Publik">
                                        Lihat
                                    </a>
                                    
                                     <button onclick="showUpdateTemplateModal({{ $certificate->id }}, '{{ $certificate->user->name }}', '{{ $certificate->certificateTemplate->name ?? 'Template Tidak Ada' }}')"
                                            class="bg-warning-soft hover:bg-warning/20 text-warning font-medium py-1 px-3 rounded text-sm transition duration-150 ease-in-out"
                                            title="Update Template">
                                        Update
                                    </button>
                                    
                                    <button onclick="deleteCertificate({{ $certificate->id }})" 
                                            class="bg-error-soft hover:bg-error/20 text-error font-medium py-1 px-3 rounded text-sm transition duration-150 ease-in-out"
                                            title="Hapus Sertifikat">Hapus
                                    </button>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
                
                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $certificates->withQueryString()->links() }}
                </div>
            @else
                <div class="px-6 py-12 text-center">
                        <div class="text-gray-500">
                            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Tidak ada sertifikat ditemukan</h3>
                        <p class="text-sm text-gray-500">
                            @if(request('search') || request('course_id'))
                                Coba ubah filter pencarian Anda.
                            @else
                                Belum ada sertifikat yang dibuat.
                            @endif
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Update Template Modal -->
<div id="updateTemplateModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900" id="modal-title">Update Template Sertifikat</h3>
                <button onclick="closeUpdateTemplateModal()" class="text-gray-400 hover:text-gray-600">
                    <span class="sr-only">Close</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <form id="updateTemplateForm" method="POST">
                @csrf
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Peserta</label>
                    <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded" id="participant-name"></p>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Template Saat Ini</label>
                    <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded" id="current-template"></p>
                </div>
                
                <div class="mb-4">
                    <label for="certificate_template_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Pilih Template Baru (Opsional)
                    </label>
                    <select name="certificate_template_id" id="certificate_template_id" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-bass-red focus:ring-bass-red">
                        <option value="">-- Gunakan template yang sama --</option>
                        @foreach($templates as $template)
                            <option value="{{ $template->id }}">{{ $template->name }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">
                        Kosongkan jika hanya ingin meregenerasi dengan template saat ini
                    </p>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeUpdateTemplateModal()" 
                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium py-2 px-4 rounded">
                        Batal
                    </button>
                    <button type="submit" 
                            class="bg-bass-red hover:bg-bass-red-hover text-white font-medium py-2 px-4 rounded">
                        Update Template
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Update Template Modal -->
<div id="bulkUpdateTemplateModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Update Template (Massal)</h3>
                <button onclick="closeBulkUpdateTemplateModal()" class="text-gray-400 hover:text-gray-600">
                    <span class="sr-only">Close</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Total Sertifikat</label>
                <p class="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded">
                    <span id="bulk-selected-count">0</span> sertifikat
                </p>
            </div>

            <div class="mb-4">
                <label for="bulk_certificate_template_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Pilih Template Baru (Opsional)
                </label>
                <select id="bulk_certificate_template_id"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-bass-red focus:ring-bass-red">
                    <option value="">-- Gunakan template yang sama --</option>
                    @foreach($templates as $template)
                        <option value="{{ $template->id }}">{{ $template->name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">
                    Kosongkan jika hanya ingin meregenerasi dengan template saat ini
                </p>
            </div>

            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeBulkUpdateTemplateModal()"
                        class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium py-2 px-4 rounded">
                    Batal
                </button>
                <button type="button" onclick="submitBulkUpdateTemplate()"
                        class="bg-bass-red hover:bg-bass-red-hover text-white font-medium py-2 px-4 rounded">
                    Update Template
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let selectAllAcrossPages = false;
const totalCertificates = {{ $certificates->total() }};
const filterCourseId = "{{ request('course_id') }}";
const filterSearch = "{{ request('search') }}";
let selectAllCheckbox = null;
let certificateCheckboxes = [];
let selectedActionsDiv = null;
let selectedCountSpan = null;
let bulkUpdateStartAt = null;

document.addEventListener('DOMContentLoaded', function() {
    selectAllCheckbox = document.getElementById('select-all');
    certificateCheckboxes = document.querySelectorAll('.certificate-checkbox');
    selectedActionsDiv = document.getElementById('selected-actions');
    selectedCountSpan = document.getElementById('selected-count');

    // Handle select all
    selectAllCheckbox.addEventListener('change', function() {
        selectAllAcrossPages = this.checked;
        certificateCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        toggleBulkActions();
    });

    // Handle individual checkbox changes
    certificateCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (selectAllAcrossPages && !this.checked) {
                selectAllAcrossPages = false;
                selectAllCheckbox.checked = false;
            }
            toggleBulkActions();
        });
    });

    function toggleBulkActions() {
        const checkedBoxes = document.querySelectorAll('.certificate-checkbox:checked');
        const hasSelection = selectAllAcrossPages || checkedBoxes.length > 0;
        const selectedCount = selectAllAcrossPages ? totalCertificates : checkedBoxes.length;

        if (selectedActionsDiv) {
            selectedActionsDiv.style.display = hasSelection ? 'flex' : 'none';
        }

        if (selectedCountSpan) {
            selectedCountSpan.textContent = selectedCount;
        }
    }
});

function bulkAction(action, options = {}) {
    const checkedBoxes = document.querySelectorAll('.certificate-checkbox:checked');
    const certificateIds = Array.from(checkedBoxes).map(cb => cb.value);

    const selectedCount = selectAllAcrossPages ? totalCertificates : certificateIds.length;

    if (!selectAllAcrossPages && certificateIds.length === 0) {
        alert('Pilih minimal satu sertifikat');
        return;
    }

    if (selectedCount === 0) {
        alert('Pilih minimal satu sertifikat');
        return;
    }

    const actionMessages = {
        'delete': 'menghapus',
        'update_template': 'memperbarui template',
        'download': 'mengunduh'
    };
    const actionText = actionMessages[action] || action;

    if (!confirm(`Apakah Anda yakin ingin ${actionText} ${selectedCount} sertifikat?`)) {
        return;
    }

    // For download action, use form submission to trigger file download
    if (action === 'download') {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("certificate-management.bulk-action") }}';

        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        form.appendChild(csrfToken);

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = action;
        form.appendChild(actionInput);

        if (selectAllAcrossPages) {
            const selectAllInput = document.createElement('input');
            selectAllInput.type = 'hidden';
            selectAllInput.name = 'select_all';
            selectAllInput.value = '1';
            form.appendChild(selectAllInput);

            if (filterCourseId) {
                const courseInput = document.createElement('input');
                courseInput.type = 'hidden';
                courseInput.name = 'course_id';
                courseInput.value = filterCourseId;
                form.appendChild(courseInput);
            }

            if (filterSearch) {
                const searchInput = document.createElement('input');
                searchInput.type = 'hidden';
                searchInput.name = 'search';
                searchInput.value = filterSearch;
                form.appendChild(searchInput);
            }
        } else {
            certificateIds.forEach(id => {
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'certificate_ids[]';
                idInput.value = id;
                form.appendChild(idInput);
            });
        }

        document.body.appendChild(form);
        form.submit();

        // Clear selection after download
        setTimeout(() => {
            checkedBoxes.forEach(cb => cb.checked = false);
            document.getElementById('select-all').checked = false;
            document.getElementById('bulk-actions').style.display = 'none';
        }, 1000);

        return;
    }

    // For other actions, use AJAX
    const payload = {
        action: action
    };

    if (selectAllAcrossPages) {
        payload.select_all = true;
        if (filterCourseId) {
            payload.course_id = filterCourseId;
        }
        if (filterSearch) {
            payload.search = filterSearch;
        }
    } else {
        payload.certificate_ids = certificateIds;
    }

    if (action === 'update_template' && options.templateId) {
        payload.certificate_template_id = options.templateId;
    }
    if (action === 'update_template') {
        payload.process_mode = 'client';
    }

    fetch('{{ route("certificate-management.bulk-action") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.queued && data.batch_id) {
                if (data.mode === 'client') {
                    startBulkUpdateClient(data.batch_id, data.total || selectedCount);
                } else {
                    startBulkUpdateProgress(data.batch_id, data.total || selectedCount);
                }
                return;
            }
            alert(data.message);
            location.reload();
        } else {
            alert('Terjadi kesalahan');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan');
    });
}

function showUpdateTemplateModal(certificateId, participantName, currentTemplate) {
    document.getElementById('participant-name').textContent = participantName;
    document.getElementById('current-template').textContent = currentTemplate;
    document.getElementById('updateTemplateForm').action = `/certificate-management/${certificateId}/update-template`;
    document.getElementById('certificate_template_id').value = '';
    document.getElementById('updateTemplateModal').classList.remove('hidden');
}

function closeUpdateTemplateModal() {
    document.getElementById('updateTemplateModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('updateTemplateModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeUpdateTemplateModal();
    }
});

function openBulkUpdateTemplateModal() {
    const checkedBoxes = document.querySelectorAll('.certificate-checkbox:checked');
    const selectedCount = selectAllAcrossPages ? totalCertificates : checkedBoxes.length;

    if (!selectAllAcrossPages && checkedBoxes.length === 0) {
        alert('Pilih minimal satu sertifikat');
        return;
    }

    if (selectedCount === 0) {
        alert('Pilih minimal satu sertifikat');
        return;
    }

    document.getElementById('bulk-selected-count').textContent = selectedCount;
    document.getElementById('bulk_certificate_template_id').value = '';
    document.getElementById('bulkUpdateTemplateModal').classList.remove('hidden');
}

function closeBulkUpdateTemplateModal() {
    document.getElementById('bulkUpdateTemplateModal').classList.add('hidden');
}

function submitBulkUpdateTemplate() {
    const templateId = document.getElementById('bulk_certificate_template_id').value;
    closeBulkUpdateTemplateModal();
    bulkAction('update_template', { templateId });
}

// Close bulk modal when clicking outside
document.getElementById('bulkUpdateTemplateModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeBulkUpdateTemplateModal();
    }
});

function deleteCertificate(certificateId) {
    if (!confirm('Apakah Anda yakin ingin menghapus sertifikat ini? File PDF juga akan dihapus.')) {
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/certificates/${certificateId}`;
    
    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = '{{ csrf_token() }}';
    form.appendChild(csrfToken);
    
    const methodInput = document.createElement('input');
    methodInput.type = 'hidden';
    methodInput.name = '_method';
    methodInput.value = 'DELETE';
    form.appendChild(methodInput);
    
    document.body.appendChild(form);
    form.submit();
}

// Download all certificates with current filters
function downloadAllCertificates() {
    const params = new URLSearchParams(window.location.search);
    const courseId = params.get('course_id') || '';
    const search = params.get('search') || '';

    const totalCount = {{ $certificates->total() }};

    if (totalCount === 0) {
        alert('Tidak ada sertifikat yang bisa didownload');
        return;
    }

    if (!confirm(`Anda akan mendownload ${totalCount} sertifikat. Proses ini mungkin memakan waktu. Lanjutkan?`)) {
        return;
    }

    // Show progress indicator
    document.getElementById('download-progress').style.display = 'block';
    document.getElementById('progress-text').textContent = 'Mempersiapkan download...';
    document.getElementById('progress-bar').style.width = '0%';

    // Start batch download process
    startBatchDownload(courseId, search, totalCount);
}

async function startBatchDownload(courseId, search, totalCount) {
    try {
        // Request server to prepare the download in batches
        const response = await fetch('{{ route("certificate-management.download-all") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                course_id: courseId,
                search: search
            })
        });

        const data = await response.json();

        if (data.success) {
            // Poll for download status
            pollDownloadStatus(data.batch_id, totalCount);
        } else {
            document.getElementById('download-progress').style.display = 'none';
            alert(data.message || 'Gagal memulai download');
        }
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('download-progress').style.display = 'none';
        alert('Terjadi kesalahan saat memulai download');
    }
}

async function pollDownloadStatus(batchId, totalCount) {
    const pollInterval = setInterval(async () => {
        try {
            const response = await fetch(`{{ url('certificate-management/download-status') }}/${batchId}`);
            const data = await response.json();

            if (data.status === 'processing') {
                const progress = Math.round((data.processed / totalCount) * 100);
                document.getElementById('progress-bar').style.width = progress + '%';
                document.getElementById('progress-text').textContent =
                    `Memproses ${data.processed} dari ${totalCount} sertifikat...`;
            } else if (data.status === 'completed') {
                clearInterval(pollInterval);
                document.getElementById('progress-bar').style.width = '100%';
                document.getElementById('progress-text').textContent = 'Download siap!';

                // Trigger download
                window.location.href = `{{ url('certificate-management/download-zip') }}/${batchId}`;

                // Hide progress after a delay
                setTimeout(() => {
                    document.getElementById('download-progress').style.display = 'none';
                    document.getElementById('progress-bar').style.width = '0%';
                }, 3000);
            } else if (data.status === 'failed') {
                clearInterval(pollInterval);
                document.getElementById('download-progress').style.display = 'none';
                alert('Download gagal: ' + (data.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Polling error:', error);
            clearInterval(pollInterval);
            document.getElementById('download-progress').style.display = 'none';
            alert('Terjadi kesalahan saat memantau progress download');
        }
    }, 2000); // Poll every 2 seconds
}

function startBulkUpdateProgress(batchId, totalCount) {
    const progressContainer = document.getElementById('bulk-update-progress');
    const progressText = document.getElementById('bulk-update-text');
    const progressBar = document.getElementById('bulk-update-bar');

    if (!progressContainer || !progressText || !progressBar) {
        alert('Proses update template berjalan di background. Silakan refresh halaman nanti.');
        return;
    }

    progressContainer.style.display = 'block';
    progressText.textContent = 'Memproses update template...';
    progressBar.style.width = '0%';
    bulkUpdateStartAt = null;

    pollBulkUpdateStatus(batchId, totalCount);
}

function startBulkUpdateClient(batchId, totalCount) {
    const progressContainer = document.getElementById('bulk-update-progress');
    const progressText = document.getElementById('bulk-update-text');
    const progressBar = document.getElementById('bulk-update-bar');

    if (!progressContainer || !progressText || !progressBar) {
        alert('Proses update template berjalan di tab ini. Silakan refresh halaman nanti.');
        return;
    }

    progressContainer.style.display = 'block';
    progressText.textContent = 'Memproses update template...';
    progressBar.style.width = '0%';
    bulkUpdateStartAt = null;

    processBulkUpdateChunk(batchId, totalCount);
}

async function pollBulkUpdateStatus(batchId, totalCount) {
    const pollInterval = setInterval(async () => {
        try {
            const response = await fetch(`{{ url('certificate-management/update-template-status') }}/${batchId}`);

            if (!response.ok) {
                throw new Error('Status tidak ditemukan');
            }

            const data = await response.json();
            const total = data.total || totalCount || 0;
            const processed = data.processed || 0;
            const progress = total ? Math.round((processed / total) * 100) : 0;
            const etaText = calculateEtaText(data, total, processed);

            if (data.status === 'queued') {
                document.getElementById('bulk-update-bar').style.width = '0%';
                document.getElementById('bulk-update-text').textContent =
                    (data.message || 'Menunggu proses di antrian...') + formatProgressSuffix(progress, etaText);
            } else if (data.status === 'processing') {
                document.getElementById('bulk-update-bar').style.width = progress + '%';
                document.getElementById('bulk-update-text').textContent =
                    `Memproses ${processed} dari ${total} sertifikat...` + formatProgressSuffix(progress, etaText);
            } else if (data.status === 'completed') {
                clearInterval(pollInterval);
                document.getElementById('bulk-update-bar').style.width = '100%';
                document.getElementById('bulk-update-text').textContent = data.message || 'Update selesai.';

                setTimeout(() => {
                    alert(data.message || 'Update template selesai.');
                    location.reload();
                }, 500);
            } else if (data.status === 'failed') {
                clearInterval(pollInterval);
                document.getElementById('bulk-update-progress').style.display = 'none';
                alert('Update gagal: ' + (data.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Polling error:', error);
            clearInterval(pollInterval);
            document.getElementById('bulk-update-progress').style.display = 'none';
            alert('Terjadi kesalahan saat memantau progress update template');
        }
    }, 2000);
}

async function processBulkUpdateChunk(batchId, totalCount) {
    try {
        const response = await fetch(`{{ url('certificate-management/update-template-chunk') }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ batch_id: batchId })
        });

        const data = await response.json();

        if (!response.ok || data.status === 'failed') {
            throw new Error(data.message || 'Update gagal');
        }

        const total = data.total || totalCount || 0;
        const processed = data.processed || 0;
        const progress = total ? Math.round((processed / total) * 100) : 0;
        const etaText = calculateEtaText(data, total, processed);

        if (data.status === 'queued') {
            document.getElementById('bulk-update-bar').style.width = '0%';
            document.getElementById('bulk-update-text').textContent =
                (data.message || 'Menunggu proses di antrian...') + formatProgressSuffix(progress, etaText);
        } else if (data.status === 'processing') {
            document.getElementById('bulk-update-bar').style.width = progress + '%';
            document.getElementById('bulk-update-text').textContent =
                `Memproses ${processed} dari ${total} sertifikat...` + formatProgressSuffix(progress, etaText);
        } else if (data.status === 'completed') {
            document.getElementById('bulk-update-bar').style.width = '100%';
            document.getElementById('bulk-update-text').textContent = data.message || 'Update selesai.';
            setTimeout(() => {
                alert(data.message || 'Update template selesai.');
                location.reload();
            }, 500);
            return;
        }

        setTimeout(() => processBulkUpdateChunk(batchId, totalCount), 300);
    } catch (error) {
        console.error('Chunk error:', error);
        document.getElementById('bulk-update-progress').style.display = 'none';
        alert('Terjadi kesalahan saat memproses update template: ' + error.message);
    }
}

function calculateEtaText(data, total, processed) {
    if (!total || processed === 0) {
        return 'Estimasi: menghitung...';
    }

    if (data.started_at) {
        const parsedStart = Date.parse(data.started_at);
        if (!Number.isNaN(parsedStart)) {
            bulkUpdateStartAt = parsedStart;
        }
    }

    if (!bulkUpdateStartAt) {
        bulkUpdateStartAt = Date.now();
        return 'Estimasi: menghitung...';
    }

    const elapsedSeconds = Math.max(1, (Date.now() - bulkUpdateStartAt) / 1000);
    const rate = processed / elapsedSeconds;

    if (rate <= 0) {
        return 'Estimasi: menghitung...';
    }

    const remaining = Math.max(0, total - processed);
    const etaSeconds = Math.round(remaining / rate);

    return `Estimasi: ${formatDuration(etaSeconds)}`;
}

function formatProgressSuffix(progress, etaText) {
    const percentText = Number.isFinite(progress) ? ` • ${progress}%` : '';
    return percentText ? `${percentText} • ${etaText}` : ` • ${etaText}`;
}

function formatDuration(totalSeconds) {
    if (!Number.isFinite(totalSeconds)) {
        return '-';
    }

    const seconds = Math.max(0, Math.floor(totalSeconds));
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;

    if (hours > 0) {
        return `${hours}j ${minutes}m`;
    }
    if (minutes > 0) {
        return `${minutes}m ${secs}d`;
    }
    return `${secs}d`;
}
</script>
</x-app-layout>
