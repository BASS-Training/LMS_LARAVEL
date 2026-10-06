<x-app-layout>
    <x-slot name="header">
        <div class="bg-navy -mx-4 -my-2 px-4 py-8 sm:px-6 lg:px-8 rounded-2xl shadow-lg">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <nav class="flex text-sm text-white/80 mb-2" aria-label="Breadcrumb">
                        <a href="{{ route('certificate-management.index') }}" class="hover:text-white">Manajemen Sertifikat</a>
                        <span class="mx-2">›</span>
                        <span class="text-white">{{ $course->title }}</span>
                    </nav>
                    <h2 class="text-white text-3xl font-bold leading-tight">
                        {{ $course->title }}
                    </h2>
                    <p class="text-white/80 mt-2">
                        {{ __('Kelola sertifikat untuk kursus ini') }}
                    </p>
                </div>
                <div class="flex space-x-4">
                    <a href="{{ route('certificate-management.index') }}" 
                       class="bg-white/20 hover:bg-white/30 text-white font-medium py-2 px-4 rounded-md transition duration-150 ease-in-out">
                        ← Kembali
                    </a>
                </div>
            </div>
        </div>
    </x-slot>
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        <!-- Course Information -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-navy">{{ $certificates->total() }}</div>
                        <div class="text-sm text-gray-500">Total Sertifikat</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-success">{{ $course->enrolledUsers->count() }}</div>
                        <div class="text-sm text-gray-500">Total Peserta</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-bass-red">
                            {{ $certificates->total() > 0 ? number_format(($certificates->total() / $course->enrolledUsers->count()) * 100, 1) : 0 }}%
                        </div>
                        <div class="text-sm text-gray-500">Tingkat Penyelesaian</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="p-6">
                <form method="GET" action="{{ route('certificate-management.by-course', $course) }}" class="flex flex-wrap gap-4">
                    <!-- Search by Name -->
                    <div class="flex-1 min-w-64">
                        <input type="text" name="search" value="{{ request('search') }}" 
                               placeholder="Cari nama peserta..." 
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-bass-red focus:ring-bass-red">
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="flex gap-2">
                        <button type="submit" 
                                class="bg-bass-red hover:bg-bass-red-hover text-white font-medium py-2 px-4 rounded-md transition duration-150 ease-in-out">
                            Cari
                        </button>
                        <a href="{{ route('certificate-management.by-course', $course) }}" 
                           class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-md transition duration-150 ease-in-out">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bulk Actions -->
        <div class="bg-white shadow rounded-lg mb-6" id="bulk-actions" style="display: none;"
             data-total="{{ $certificates->total() }}"
             data-course-id="{{ $course->id }}"
             data-search="{{ request('search') }}">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Aksi Massal</h3>
                    <span class="text-sm text-gray-600">
                        <span id="selected-count">0</span> sertifikat dipilih
                    </span>
                </div>
                <div class="flex gap-4">
                    <button onclick="bulkAction('delete')"
                            class="bg-neutral-900 hover:bg-black text-white font-medium py-2 px-4 rounded-md transition duration-150 ease-in-out">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        Hapus Terpilih
                    </button>
                    <button onclick="openBulkUpdateTemplateModal()" 
                            class="bg-navy hover:bg-navy-light text-white font-medium py-2 px-4 rounded-md transition duration-150 ease-in-out">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        Update Template
                    </button>
                </div>
            </div>
        </div>

        <!-- Bulk Update Progress Indicator -->
        <div id="bulk-update-progress" style="display: none;" class="mb-6">
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

        <!-- Certificates Table -->
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Daftar Sertifikat Peserta
                        @if(request('search'))
                            <span class="text-sm font-normal text-gray-500">
                                ({{ $certificates->total() }} hasil pencarian)
                            </span>
                        @else
                            <span class="text-sm font-normal text-gray-500">
                                ({{ $certificates->total() }} total)
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
                                    <div class="ml-4 flex items-center">
                                        <!-- Avatar/Initial -->
                                        <div class="flex-shrink-0 h-10 w-10 bg-navy rounded-full flex items-center justify-center">
                                            <span class="text-white font-semibold">
                                                {{ strtoupper(substr($certificate->user->name, 0, 1)) }}
                                            </span>
                                        </div>
                                        
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
                                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                                {{ $certificate->user->email }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                Diterbitkan: {{ $certificate->issued_at->format('d M Y H:i') }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                                                Kode: {{ $certificate->certificate_code }}
                                            </div>
                                            @if($certificate->certificateTemplate)
                                                <div class="text-sm text-gray-500">
                                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path></svg>
                                                    Template: {{ $certificate->certificateTemplate->name }}
                                                </div>
                                            @endif
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
                        <h3 class="text-lg font-medium text-gray-900 mb-2">
                            @if(request('search'))
                                Tidak ada sertifikat yang cocok dengan pencarian
                            @else
                                Belum ada sertifikat untuk kursus ini
                            @endif
                        </h3>
                        <p class="text-sm text-gray-500">
                            @if(request('search'))
                                Coba ubah kata kunci pencarian Anda.
                            @else
                                Sertifikat akan muncul di sini setelah peserta menyelesaikan kursus.
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
let totalCertificates = 0;
let bulkCourseId = '';
let bulkSearch = '';
let bulkActionsDiv = null;
let selectAllCheckbox = null;
let certificateCheckboxes = [];
let selectedCountSpan = null;
let bulkUpdateStartAt = null;

document.addEventListener('DOMContentLoaded', function() {
    selectAllCheckbox = document.getElementById('select-all');
    certificateCheckboxes = document.querySelectorAll('.certificate-checkbox');
    bulkActionsDiv = document.getElementById('bulk-actions');
    selectedCountSpan = document.getElementById('selected-count');

    if (bulkActionsDiv) {
        totalCertificates = Number(bulkActionsDiv.dataset.total || 0);
        bulkCourseId = bulkActionsDiv.dataset.courseId || '';
        bulkSearch = bulkActionsDiv.dataset.search || '';
    }

    // Handle select all
    selectAllCheckbox.addEventListener('change', function() {
        selectAllAcrossPages = this.checked;
        certificateCheckboxes.forEach(checkbox => checkbox.checked = this.checked);
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

        if (bulkActionsDiv) {
            bulkActionsDiv.style.display = hasSelection ? 'block' : 'none';
        }

        if (selectedCountSpan) {
            selectedCountSpan.textContent = selectedCount;
        }
    }
});

function getSelectedCertificateIds() {
    const checkedBoxes = document.querySelectorAll('.certificate-checkbox:checked');
    return Array.from(checkedBoxes).map(cb => cb.value);
}

function bulkAction(action, options = {}) {
    const certificateIds = getSelectedCertificateIds();
    const selectedCount = selectAllAcrossPages ? totalCertificates : certificateIds.length;

    if (!selectAllAcrossPages && certificateIds.length === 0) {
        alert('Pilih minimal satu sertifikat');
        return;
    }

    if (selectedCount === 0) {
        alert('Pilih minimal satu sertifikat');
        return;
    }

    const actionText = action === 'delete' ? 'menghapus' : 'memperbarui template';
    if (!confirm(`Apakah Anda yakin ingin ${actionText} ${selectedCount} sertifikat?`)) {
        return;
    }

    const payload = {
        action: action
    };

    if (selectAllAcrossPages) {
        payload.select_all = true;
        payload.course_id = bulkCourseId;
        if (bulkSearch) {
            payload.search = bulkSearch;
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
    const certificateIds = getSelectedCertificateIds();
    const selectedCount = selectAllAcrossPages ? totalCertificates : certificateIds.length;

    if (!selectAllAcrossPages && certificateIds.length === 0) {
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
