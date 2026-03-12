<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-4">
            <div>
                <div class="flex items-center space-x-3 mb-2">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="font-bold text-2xl text-gray-900 leading-tight">Data Peserta</h2>
                        <p class="text-blue-600 font-medium text-sm">Kelola dan lihat informasi peserta</p>
                    </div>
                </div>
            </div>
            <a href="{{ route('admin.participants.analytics') }}" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-500 to-pink-600 text-white rounded-xl font-medium text-sm hover:from-purple-600 hover:to-pink-700 shadow-lg hover:shadow-xl transition-all duration-200">
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
                                <svg class="w-4 h-4 inline mr-1 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                Cari Peserta
                            </label>
                            <x-text-input type="text" name="search" id="search" value="{{ request('search') }}" class="w-full" placeholder="Nama, email, institusi, atau pekerjaan..." />
                        </div>

                        <!-- Gender Filter -->
                        <div>
                            <label for="gender" class="block text-sm font-semibold text-gray-700 mb-2">
                                <svg class="w-4 h-4 inline mr-1 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                </svg>
                                Gender
                            </label>
                            <select name="gender" id="gender" class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-xl">
                                <option value="">Semua</option>
                                <option value="male" @selected(request('gender') == 'male')>Laki-laki</option>
                                <option value="female" @selected(request('gender') == 'female')>Perempuan</option>
                            </select>
                        </div>

                        <!-- Institution Filter -->
                        <div>
                            <label for="institution" class="block text-sm font-semibold text-gray-700 mb-2">
                                <svg class="w-4 h-4 inline mr-1 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                Institusi
                            </label>
                            <select name="institution" id="institution" class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-xl">
                                <option value="">Semua</option>
                                @foreach($institutions as $inst)
                                    <option value="{{ $inst }}" @selected(request('institution') == $inst)>{{ $inst }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="registration_program" class="block text-sm font-semibold text-gray-700 mb-2">Program</label>
                            <select name="registration_program" id="registration_program" class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-xl">
                                <option value="">Semua</option>
                                <option value="regular" @selected(request('registration_program') == 'regular')>Reguler BASS</option>
                                <option value="avpn_ai" @selected(request('registration_program') == 'avpn_ai')>Literasi AI (AVPN)</option>
                            </select>
                        </div>

                        <div>
                            <label for="avpn_status" class="block text-sm font-semibold text-gray-700 mb-2">Status AVPN</label>
                            <select name="avpn_status" id="avpn_status" class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-xl">
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
                        <button type="submit" class="px-6 py-2 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-xl font-medium text-sm hover:from-blue-600 hover:to-indigo-700 shadow-lg hover:shadow-xl transition-all duration-200">
                            Terapkan Filter
                        </button>
                    </div>
                </form>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-blue-100 rounded-lg p-3">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                    class="w-full sm:w-64 border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-xl text-sm"
                                >
                                <button
                                    type="submit"
                                    formaction="{{ route('admin.participants.avpn.batch-approve') }}"
                                    class="px-4 py-2 bg-green-600 text-white rounded-xl text-sm font-medium hover:bg-green-700 transition-colors"
                                >
                                    Batch Approve AVPN
                                </button>
                                <button
                                    type="submit"
                                    formaction="{{ route('admin.participants.avpn.batch-reject') }}"
                                    class="px-4 py-2 bg-red-600 text-white rounded-xl text-sm font-medium hover:bg-red-700 transition-colors"
                                >
                                    Batch Reject AVPN
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.participants.avpn.sync-legacy') }}">
                                @csrf
                                <button
                                    type="submit"
                                    onclick="return confirm('Sinkronisasi ini akan menandai user lama yang punya riwayat kelas AVPN menjadi user AVPN approved. Lanjutkan?')"
                                    class="px-4 py-2 bg-amber-600 text-white rounded-xl text-sm font-medium hover:bg-amber-700 transition-colors"
                                >
                                    Sinkronisasi User Lama AVPN
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    <input type="checkbox" id="select-all-pending" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
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
                                                class="batch-pending-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500"
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
                                                <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-400 to-indigo-600 flex items-center justify-center text-white font-semibold">
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
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $participant->gender == 'male' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800' }}">
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
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">Literasi AI (AVPN)</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Reguler BASS</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        @php
                                            $statusClass = match($participant->avpn_verification_status) {
                                                'approved' => 'bg-green-100 text-green-800',
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'rejected' => 'bg-red-100 text-red-800',
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
                                        <a href="{{ route('admin.participants.show', $participant) }}" class="inline-flex items-center px-3 py-1.5 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                            Detail
                                        </a>
                                        @if($participant->avpn_verification_status === 'pending')
                                            <div class="mt-2 flex items-center justify-center gap-2">
                                                <form method="POST" action="{{ route('admin.participants.avpn.approve', $participant) }}">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center px-2.5 py-1 text-xs bg-green-100 text-green-700 rounded hover:bg-green-200">
                                                        Approve
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.participants.avpn.reject', $participant) }}">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center px-2.5 py-1 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200">
                                                        Reject
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                        <div class="mt-2 flex items-center justify-center gap-2">
                                            <form method="POST" action="{{ route('admin.participants.access.force', $participant) }}">
                                                @csrf
                                                <input type="hidden" name="access_mode" value="avpn_allowed">
                                                <button type="submit" class="inline-flex items-center px-2.5 py-1 text-xs bg-indigo-100 text-indigo-700 rounded hover:bg-indigo-200">
                                                    Paksa Aktifkan AVPN
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.participants.access.force', $participant) }}">
                                                @csrf
                                                <input type="hidden" name="access_mode" value="avpn_blocked">
                                                <input type="hidden" name="reason" value="Akses AVPN dihentikan secara paksa oleh admin.">
                                                <button
                                                    type="submit"
                                                    onclick="return confirm('Yakin ingin menghentikan akses AVPN peserta ini?')"
                                                    class="inline-flex items-center px-2.5 py-1 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200"
                                                >
                                                    Stop AVPN
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.participants.access.force', $participant) }}">
                                                @csrf
                                                <input type="hidden" name="access_mode" value="regular_only">
                                                <button type="submit" class="inline-flex items-center px-2.5 py-1 text-xs bg-gray-100 text-gray-700 rounded hover:bg-gray-200">
                                                    Paksa Set Reguler
                                                </button>
                                            </form>
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
