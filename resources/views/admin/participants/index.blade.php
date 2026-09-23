<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-4">
            <div>
                <div class="flex items-center space-x-3 mb-2">
                    <div class="w-12 h-12 bg-navy rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="font-bold text-2xl text-gray-900 leading-tight">Data Peserta</h2>
                        <p class="text-navy font-medium text-sm">Kelola dan lihat informasi peserta</p>
                    </div>
                </div>
            </div>
            <a href="{{ route('admin.participants.analytics') }}" class="inline-flex items-center px-4 py-2 bg-navy text-white rounded-xl font-medium text-sm hover:bg-navy/90 shadow-sm transition-all duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                Lihat Analytics
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Filter Section -->
            <div class="mb-6 bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <form action="{{ route('admin.participants.index') }}" method="GET">
                    <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                        <!-- Search -->
                        <div class="md:col-span-2">
                            <label for="search" class="block text-sm font-semibold text-gray-700 mb-2">
                                <svg class="w-4 h-4 inline mr-1 text-bass-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                Cari Peserta
                            </label>
                            <x-text-input type="text" name="search" id="search" value="{{ request('search') }}" class="w-full" placeholder="Nama, email, institusi, atau pekerjaan..." />
                        </div>

                        <!-- Gender Filter -->
                        <div>
                            <label for="gender" class="block text-sm font-semibold text-gray-700 mb-2">
                                <svg class="w-4 h-4 inline mr-1 text-bass-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                </svg>
                                Gender
                            </label>
                            <select name="gender" id="gender" class="w-full border-gray-300 focus:border-bass-red focus:ring-bass-red rounded-xl">
                                <option value="">Semua</option>
                                <option value="male" @selected(request('gender') == 'male')>Laki-laki</option>
                                <option value="female" @selected(request('gender') == 'female')>Perempuan</option>
                            </select>
                        </div>

                        <!-- Institution Filter -->
                        <div>
                            <label for="institution" class="block text-sm font-semibold text-gray-700 mb-2">
                                <svg class="w-4 h-4 inline mr-1 text-bass-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                Institusi
                            </label>
                            <select name="institution" id="institution" class="w-full border-gray-300 focus:border-bass-red focus:ring-bass-red rounded-xl">
                                <option value="">Semua</option>
                                @foreach($institutions as $inst)
                                    <option value="{{ $inst }}" @selected(request('institution') == $inst)>{{ $inst }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="registration_program" class="block text-sm font-semibold text-gray-700 mb-2">Program</label>
                            <select name="registration_program" id="registration_program" class="w-full border-gray-300 focus:border-bass-red focus:ring-bass-red rounded-xl">
                                <option value="">Semua</option>
                                <option value="regular" @selected(request('registration_program') == 'regular')>Reguler BASS</option>
                                <option value="avpn_ai" @selected(request('registration_program') == 'avpn_ai')>Literasi AI (AVPN)</option>
                            </select>
                        </div>

                        <div>
                            <label for="avpn_status" class="block text-sm font-semibold text-gray-700 mb-2">Status AVPN</label>
                            <select name="avpn_status" id="avpn_status" class="w-full border-gray-300 focus:border-bass-red focus:ring-bass-red rounded-xl">
                                <option value="">Semua</option>
                                <option value="pending" @selected(request('avpn_status') == 'pending')>Pending</option>
                                <option value="approved" @selected(request('avpn_status') == 'approved')>Approved</option>
                                <option value="rejected" @selected(request('avpn_status') == 'rejected')>Rejected</option>
                                <option value="not_required" @selected(request('avpn_status') == 'not_required')>Not Required</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end space-x-2">
                        <a href="{{ route('admin.participants.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-xl font-medium text-sm hover:bg-gray-200 transition-colors">
                            Reset
                        </a>
                        <button type="submit" class="px-6 py-2 bg-bass-red text-white rounded-xl font-medium text-sm hover:bg-bass-red-hover shadow-sm transition-all duration-200">
                            Terapkan Filter
                        </button>
                    </div>
                </form>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-navy/10 rounded-lg p-3">
                            <svg class="w-6 h-6 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Peserta</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $participants->total() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Participants Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-gray-800">Aksi Batch AVPN (khusus status Pending)</p>
                            <p class="text-xs text-gray-500">Centang peserta pending untuk batch approve/reject, atau jalankan sinkronisasi user lama AVPN.</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <form id="avpnBatchForm" method="POST" class="flex flex-col sm:flex-row gap-2">
                                @csrf
                                <input
                                    type="text"
                                    name="reason"
                                    placeholder="Alasan reject batch (opsional)"
                                    class="w-full sm:w-64 border-gray-300 focus:border-bass-red focus:ring-bass-red rounded-xl text-sm"
                                >
                                <button
                                    type="submit"
                                    formaction="{{ route('admin.participants.avpn.batch-approve') }}"
                                    class="px-4 py-2 bg-success text-white rounded-xl text-sm font-medium hover:bg-success/90 transition-colors"
                                >
                                    Batch Approve AVPN
                                </button>
                                <button
                                    type="submit"
                                    formaction="{{ route('admin.participants.avpn.batch-reject') }}"
                                    class="px-4 py-2 bg-error text-white rounded-xl text-sm font-medium hover:bg-error-dark transition-colors"
                                >
                                    Batch Reject AVPN
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.participants.avpn.sync-legacy') }}">
                                @csrf
                                <button
                                    type="submit"
                                    onclick="return confirm('Sinkronisasi ini akan menandai user lama yang punya riwayat kelas AVPN menjadi user AVPN approved. Lanjutkan?')"
                                    class="px-4 py-2 bg-navy text-white rounded-xl text-sm font-medium hover:bg-navy-light transition-colors"
                                >
                                    Sinkronisasi User Lama AVPN
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    <input type="checkbox" id="select-all-pending" class="rounded border-gray-300 text-bass-red focus:ring-bass-red">
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Peserta</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Gender</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Tanggal Lahir</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Institusi</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Pekerjaan</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Program</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Status AVPN</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Terdaftar</th>
                                <th class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($participants as $participant)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-4 text-center">
                                        @if($participant->avpn_verification_status === 'pending')
                                            <input
                                                type="checkbox"
                                                name="participant_ids[]"
                                                value="{{ $participant->id }}"
                                                form="avpnBatchForm"
                                                class="batch-pending-checkbox rounded border-gray-300 text-bass-red focus:ring-bass-red"
                                            >
                                        @else
                                            <input
                                                type="checkbox"
                                                disabled
                                                class="rounded border-gray-200 text-gray-300 cursor-not-allowed"
                                                title="Batch action hanya untuk status pending"
                                            >
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-full bg-navy flex items-center justify-center text-white font-semibold">
                                                    {{ strtoupper(substr($participant->name, 0, 2)) }}
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-semibold text-gray-900">{{ $participant->name }}</div>
                                                <div class="text-sm text-gray-500">{{ $participant->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($participant->gender)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $participant->gender == 'male' ? 'bg-gray-100 text-gray-700' : 'bg-gray-200 text-gray-700' }}">
                                                {{ $participant->gender == 'male' ? 'Laki-laki' : 'Perempuan' }}
                                            </span>
                                        @else
                                            <span class="text-sm text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $participant->date_of_birth ? $participant->date_of_birth->format('d M Y') : '-' }}
                                        @if($participant->date_of_birth)
                                            <span class="text-xs text-gray-500">({{ floor($participant->date_of_birth->diffInYears(now())) }} th)</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        {{ $participant->institution_name ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        {{ $participant->occupation ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        @if($participant->registration_program === 'avpn_ai')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-navy/10 text-navy">Literasi AI (AVPN)</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Reguler BASS</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        @php
                                            $statusClass = match($participant->avpn_verification_status) {
                                                'approved' => 'bg-success-soft text-success',
                                                'pending' => 'bg-warning-soft text-warning',
                                                'rejected' => 'bg-error-soft text-error',
                                                default => 'bg-gray-100 text-gray-700'
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                                            {{ strtoupper($participant->avpn_verification_status ?? 'not_required') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $participant->created_at->format('d M Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <div x-data="{ open: false }" class="relative inline-block">
                                            <button @click="open = !open" @click.outside="open = false" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-navy bg-navy/5 rounded-lg hover:bg-navy/10 transition-colors">
                                                More
                                            </button>
                                            <div x-show="open" x-transition class="absolute right-0 mt-1 w-52 bg-white rounded-xl border border-gray-200 shadow-lg z-50 py-1">
                                                <a href="{{ route('admin.participants.show', $participant) }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                    Lihat Detail
                                                </a>
                                                @if($participant->avpn_verification_status === 'pending')
                                                    <div class="border-t border-gray-100 my-1"></div>
                                                    <form method="POST" action="{{ route('admin.participants.avpn.approve', $participant) }}">
                                                        @csrf
                                                        <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-success hover:bg-success-soft">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                            Approve AVPN
                                                        </button>
                                                    </form>
                                                    <form method="POST" action="{{ route('admin.participants.avpn.reject', $participant) }}">
                                                        @csrf
                                                        <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-error hover:bg-error-soft">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"/></svg>
                                                            Reject AVPN
                                                        </button>
                                                    </form>
                                                @endif
                                                <div class="border-t border-gray-100 my-1"></div>
                                                <form method="POST" action="{{ route('admin.participants.access.force', $participant) }}">
                                                    @csrf
                                                    <input type="hidden" name="access_mode" value="avpn_allowed">
                                                    <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-navy hover:bg-navy/5">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                        Aktifkan AVPN
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.participants.access.force', $participant) }}">
                                                    @csrf
                                                    <input type="hidden" name="access_mode" value="avpn_blocked">
                                                    <input type="hidden" name="reason" value="Akses AVPN dihentikan secara paksa oleh admin.">
                                                    <button type="submit" onclick="return confirm('Yakin ingin menghentikan akses AVPN peserta ini?')" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-error hover:bg-error-soft">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"/></svg>
                                                        Stop AVPN
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.participants.access.force', $participant) }}">
                                                    @csrf
                                                    <input type="hidden" name="access_mode" value="regular_only">
                                                    <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                                                        Set Reguler
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-6 py-12 text-center">
                                        <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                        </svg>
                                        <h3 class="text-lg font-medium text-gray-900 mb-1">Tidak ada peserta ditemukan</h3>
                                        <p class="text-sm text-gray-500">Coba ubah filter atau kata kunci pencarian.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($participants->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $participants->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selectAll = document.getElementById('select-all-pending');
            const checkboxes = document.querySelectorAll('.batch-pending-checkbox');
            const batchForm = document.getElementById('avpnBatchForm');

            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    checkboxes.forEach((checkbox) => {
                        checkbox.checked = selectAll.checked;
                    });
                });
            }

            if (batchForm) {
                batchForm.addEventListener('submit', function (event) {
                    const checked = document.querySelectorAll('.batch-pending-checkbox:checked');
                    if (checked.length === 0) {
                        event.preventDefault();
                        window.alert('Pilih minimal 1 peserta berstatus pending untuk aksi batch.');
                    }
                });
            }
        });
    </script>
    @endpush
</x-app-layout>
