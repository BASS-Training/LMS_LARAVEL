<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Kelola Periode
                </h2>
                <p class="text-sm text-gray-600 mt-1">{{ $period->name }} &mdash; {{ $course->title }}</p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('course-periods.edit', [$course, $period]) }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    Edit
                </a>
                <a href="{{ route('course-periods.show', [$course, $period]) }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    Detail
                </a>
                <a href="{{ route('courses.show', $course) }}"
                   class="inline-flex items-center px-4 py-2 bg-navy hover:bg-navy-light text-white text-sm font-medium rounded-lg transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Kembali
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Period Info Header -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ $period->name }}</h1>
                        <p class="mt-1 text-sm text-gray-600">{{ $course->title }}</p>
                        <div class="mt-2 flex items-center space-x-4 text-sm text-gray-600">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($period->status === 'active') bg-success-soft text-success
                                @elseif($period->status === 'upcoming') bg-info-soft text-navy
                                @else bg-gray-100 text-gray-600 @endif">
                                {{ ucfirst($period->status) }}
                            </span>
                            @if($period->start_date && $period->end_date)
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    {{ $period->start_date->format('d M Y') }} - {{ $period->end_date->format('d M Y') }}
                                </span>
                            @else
                                <span class="italic text-gray-400">Tanggal belum ditentukan</span>
                            @endif
                            @if($period->max_participants)
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                    {{ $period->getParticipantCount() }} / {{ $period->max_participants }} peserta
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-navy rounded-lg p-4 text-white">
                    <div class="text-sm text-white/80">Instructor</div>
                    <div class="text-2xl font-bold">{{ $period->instructors->count() }}</div>
                </div>
                <div class="bg-success rounded-lg p-4 text-white">
                    <div class="text-sm text-white/80">Peserta</div>
                    <div class="text-2xl font-bold">{{ $period->participants->count() }}</div>
                </div>
                <div class="bg-bass-red rounded-lg p-4 text-white">
                    <div class="text-sm text-white/80">Slot Tersedia</div>
                    <div class="text-2xl font-bold">{{ $period->getAvailableSlots() == PHP_INT_MAX ? '∞' : $period->getAvailableSlots() }}</div>
                </div>
                <div class="bg-gray-600 rounded-lg p-4 text-white">
                    <div class="text-sm text-white/80">Durasi</div>
                    <div class="text-2xl font-bold">{{ $period->getDurationInDays() }} hari</div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Instructors Management -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            Instructor ({{ $period->instructors->count() }})
                        </h2>
                    </div>
                    <div class="p-6">
                        @if($availableInstructors->count() > 0)
                        <form action="{{ route('course-periods.add-instructor', [$course, $period]) }}" method="POST" class="mb-4">
                            @csrf
                            <div class="flex space-x-2">
                                <select name="user_id" class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-bass-red focus:ring-bass-red text-sm" required>
                                    <option value="">Pilih Instructor</option>
                                    @foreach($availableInstructors as $instructor)
                                        <option value="{{ $instructor->id }}">{{ $instructor->name }} ({{ $instructor->email }})</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="px-4 py-2 bg-bass-red hover:bg-bass-red-hover text-white rounded-lg text-sm font-medium transition">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    Tambah
                                </button>
                            </div>
                        </form>
                        @endif

                        <div class="space-y-2">
                            @forelse($period->instructors as $instructor)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-navy rounded-full flex items-center justify-center">
                                            <span class="text-white text-sm font-medium">{{ strtoupper(substr($instructor->name, 0, 1)) }}</span>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900">{{ $instructor->name }}</p>
                                            <p class="text-xs text-gray-500">{{ $instructor->email }}</p>
                                        </div>
                                    </div>
                                    <form action="{{ route('course-periods.remove-instructor', [$course, $period, $instructor]) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-error hover:text-error-dark p-1" onclick="return confirm('Yakin ingin menghapus instructor ini dari periode?')">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </form>
                                </div>
                            @empty
                                <p class="text-gray-500 text-center py-4">Belum ada instructor yang ditugaskan</p>
                            @endforelse
                        </div>

                        @if($availableInstructors->count() == 0 && $period->instructors->count() == 0)
                            <div class="text-center py-6">
                                <svg class="w-10 h-10 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <p class="text-sm text-gray-500">Tidak ada instructor yang tersedia.</p>
                                <p class="text-xs text-gray-400 mt-1">Pastikan course ini memiliki instructor terlebih dahulu.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Participants Management -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            Peserta ({{ $period->participants->count() }}{{ $period->max_participants ? '/' . $period->max_participants : '' }})
                        </h2>
                    </div>
                    <div class="p-6">
                        @if($availableParticipants->count() > 0 && $period->hasAvailableSlots())
                        <form action="{{ route('course-periods.add-participant', [$course, $period]) }}" method="POST" class="mb-4">
                            @csrf
                            <div class="space-y-3">
                                <input type="text" id="participant-search" placeholder="Cari peserta..."
                                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-bass-red focus:ring-bass-red text-sm">

                                <div class="border rounded-lg max-h-40 overflow-y-auto bg-gray-50">
                                    <div id="participant-list" class="p-2 space-y-1">
                                        @foreach($availableParticipants as $participant)
                                            <label class="participant-item flex items-center p-2 hover:bg-gray-100 rounded cursor-pointer"
                                                   data-name="{{ strtolower($participant->name) }}" data-email="{{ strtolower($participant->email) }}">
                                                <input type="checkbox" name="user_ids[]" value="{{ $participant->id }}"
                                                       class="rounded border-gray-300 text-bass-red focus:ring-bass-red mr-3">
                                                <div class="flex items-center">
                                                    <div class="w-6 h-6 bg-success rounded-full flex items-center justify-center mr-2">
                                                        <span class="text-white text-xs font-medium">{{ strtoupper(substr($participant->name, 0, 1)) }}</span>
                                                    </div>
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900">{{ $participant->name }}</p>
                                                        <p class="text-xs text-gray-500">{{ $participant->email }}</p>
                                                    </div>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                    <div id="no-results" class="p-4 text-center text-gray-500 text-sm hidden">
                                        Tidak ada peserta yang ditemukan
                                    </div>
                                </div>

                                <div class="flex items-center justify-between">
                                    <span id="selection-count" class="text-sm text-gray-600">Belum ada yang dipilih</span>
                                    <div class="flex space-x-2">
                                        <button type="button" id="select-all" class="px-3 py-1 text-xs bg-gray-100 text-gray-700 rounded hover:bg-gray-200">Pilih Semua</button>
                                        <button type="button" id="clear-all" class="px-3 py-1 text-xs bg-gray-100 text-gray-700 rounded hover:bg-gray-200">Hapus Pilihan</button>
                                        <button type="submit" class="px-4 py-2 bg-bass-red hover:bg-bass-red-hover text-white rounded-lg text-sm font-medium disabled:bg-gray-400 transition" disabled id="submit-btn">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                            Tambah Peserta
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                        @endif

                        @if(!$period->hasAvailableSlots() && $period->max_participants)
                        <div class="mb-4 p-3 bg-warning-soft border border-warning/30 rounded-lg">
                            <p class="text-sm text-gray-800 flex items-center">
                                <svg class="w-4 h-4 mr-1 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>
                                Periode sudah penuh ({{ $period->max_participants }} peserta)
                            </p>
                        </div>
                        @endif

                        @if($period->participants->count() > 0)
                        <div class="mb-4">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-sm font-medium text-gray-900">Peserta Terdaftar</h3>
                                <div class="flex space-x-2">
                                    <button type="button" id="select-all-participants" class="px-3 py-1 text-xs bg-gray-100 text-gray-700 rounded hover:bg-gray-200">Pilih Semua</button>
                                    <button type="button" id="bulk-remove-btn" class="px-3 py-1 text-xs bg-error-soft text-error rounded hover:bg-error-soft disabled:bg-gray-100 disabled:text-gray-400 transition" disabled>
                                        <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        Hapus
                                    </button>
                                </div>
                            </div>
                            <form id="bulk-remove-form" action="{{ route('course-periods.bulk-remove-participants', [$course, $period]) }}" method="POST" style="display: none;">
                                @csrf
                                @method('DELETE')
                            </form>
                        </div>
                        @endif

                        <div class="space-y-2 max-h-96 overflow-y-auto">
                            @forelse($period->participants as $participant)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg participant-row">
                                    <div class="flex items-center">
                                        <input type="checkbox" class="participant-checkbox rounded border-gray-300 text-bass-red focus:ring-bass-red mr-3"
                                               data-participant-id="{{ $participant->id }}">
                                        <div class="w-8 h-8 bg-success rounded-full flex items-center justify-center mr-3">
                                            <span class="text-white text-sm font-medium">{{ strtoupper(substr($participant->name, 0, 1)) }}</span>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ $participant->name }}</p>
                                            <p class="text-xs text-gray-500">{{ $participant->email }}</p>
                                        </div>
                                    </div>
                                    <form action="{{ route('course-periods.remove-participant', [$course, $period, $participant]) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-error hover:text-error-dark p-1" onclick="return confirm('Yakin ingin menghapus peserta ini dari periode?')">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </form>
                                </div>
                            @empty
                                <p class="text-gray-500 text-center py-4">Belum ada peserta yang terdaftar</p>
                            @endforelse
                        </div>

                        @if($availableParticipants->count() == 0 && $period->participants->count() == 0)
                            <div class="text-center py-6">
                                <svg class="w-10 h-10 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <p class="text-sm text-gray-500">Tidak ada peserta yang tersedia.</p>
                                <p class="text-xs text-gray-400 mt-1">Pastikan course ini memiliki peserta terlebih dahulu.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('participant-search');
        const participantItems = document.querySelectorAll('.participant-item');
        const noResults = document.getElementById('no-results');
        const participantList = document.getElementById('participant-list');

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                let hasResults = false;
                participantItems.forEach(function(item) {
                    const name = item.dataset.name;
                    const email = item.dataset.email;
                    if (name.includes(searchTerm) || email.includes(searchTerm)) {
                        item.style.display = 'flex';
                        hasResults = true;
                    } else {
                        item.style.display = 'none';
                    }
                });
                if (hasResults) {
                    participantList.style.display = 'block';
                    noResults.style.display = 'none';
                } else {
                    participantList.style.display = 'none';
                    noResults.style.display = 'block';
                }
            });
        }

        const checkboxes = document.querySelectorAll('input[name="user_ids[]"]');
        const selectionCount = document.getElementById('selection-count');
        const submitBtn = document.getElementById('submit-btn');
        const selectAllBtn = document.getElementById('select-all');
        const clearAllBtn = document.getElementById('clear-all');

        function updateSelectionCount() {
            const selectedCount = document.querySelectorAll('input[name="user_ids[]"]:checked').length;
            if (selectedCount === 0) {
                selectionCount.textContent = 'Belum ada yang dipilih';
                submitBtn.disabled = true;
            } else {
                selectionCount.textContent = `${selectedCount} peserta dipilih`;
                submitBtn.disabled = false;
            }
        }

        checkboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', updateSelectionCount);
        });

        if (selectAllBtn) {
            selectAllBtn.addEventListener('click', function() {
                Array.from(checkboxes).filter(cb => cb.closest('.participant-item').style.display !== 'none').forEach(function(checkbox) {
                    checkbox.checked = true;
                });
                updateSelectionCount();
            });
        }

        if (clearAllBtn) {
            clearAllBtn.addEventListener('click', function() {
                checkboxes.forEach(function(checkbox) { checkbox.checked = false; });
                updateSelectionCount();
            });
        }

        const participantCheckboxes = document.querySelectorAll('.participant-checkbox');
        const bulkRemoveBtn = document.getElementById('bulk-remove-btn');
        const selectAllParticipantsBtn = document.getElementById('select-all-participants');
        const bulkRemoveForm = document.getElementById('bulk-remove-form');

        function updateBulkRemoveBtn() {
            bulkRemoveBtn.disabled = document.querySelectorAll('.participant-checkbox:checked').length === 0;
        }

        participantCheckboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', updateBulkRemoveBtn);
        });

        if (selectAllParticipantsBtn) {
            selectAllParticipantsBtn.addEventListener('click', function() {
                participantCheckboxes.forEach(function(checkbox) { checkbox.checked = true; });
                updateBulkRemoveBtn();
            });
        }

        if (bulkRemoveBtn) {
            bulkRemoveBtn.addEventListener('click', function() {
                const selected = document.querySelectorAll('.participant-checkbox:checked');
                if (selected.length === 0) return;
                if (confirm(`Yakin ingin menghapus ${selected.length} peserta dari periode ini?`)) {
                    selected.forEach(function(checkbox) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'participant_ids[]';
                        input.value = checkbox.dataset.participantId;
                        bulkRemoveForm.appendChild(input);
                    });
                    bulkRemoveForm.submit();
                }
            });
        }
    });
    </script>
    @endpush
</x-app-layout>
