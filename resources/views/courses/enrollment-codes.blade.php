<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('courses.show', $course) }}"
                   class="inline-flex items-center text-indigo-600 hover:text-indigo-800 text-sm font-medium mb-2 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Kembali ke Course
                </a>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                    Kode Pendaftaran Pribadi
                </h2>
                <p class="text-sm text-gray-600 mt-1">{{ $course->title }}</p>
            </div>
            <a href="{{ route('courses.tokens', $course) }}"
               class="hidden md:inline-flex items-center px-3 py-2 text-sm font-medium text-purple-700 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                </svg>
                Token Bersama (lama)
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Penjelasan singkat --}}
            <div class="mb-6 bg-blue-50 border-l-4 border-blue-400 p-4 rounded-lg">
                <p class="text-sm text-blue-800">
                    Kode di sini bersifat <strong>sekali-pakai</strong> (1 kode = 1 peserta). Opsional bisa
                    di-<strong>bind ke email</strong> pembeli agar hanya email itu yang dapat memakainya.
                    Berbeda dari token bersama lama yang bisa dipakai berkali-kali.
                </p>
            </div>

            {{-- Flash --}}
            @if (session('success'))
                <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                    @foreach ($errors->all() as $error)
                        <p class="text-sm font-medium text-red-800">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            {{-- Ringkasan --}}
            @php
                $total = $codes->count();
                $available = $codes->where('status', \App\Models\EnrollmentCode::STATUS_AVAILABLE)->count();
                $redeemed = $codes->where('status', \App\Models\EnrollmentCode::STATUS_REDEEMED)->count();
                $revoked = $codes->where('status', \App\Models\EnrollmentCode::STATUS_REVOKED)->count();
            @endphp
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-xl shadow p-4">
                    <p class="text-xs text-gray-500">Total</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $total }}</p>
                </div>
                <div class="bg-white rounded-xl shadow p-4">
                    <p class="text-xs text-gray-500">Tersedia</p>
                    <p class="text-2xl font-bold text-green-600">{{ $available }}</p>
                </div>
                <div class="bg-white rounded-xl shadow p-4">
                    <p class="text-xs text-gray-500">Sudah dipakai</p>
                    <p class="text-2xl font-bold text-blue-600">{{ $redeemed }}</p>
                </div>
                <div class="bg-white rounded-xl shadow p-4">
                    <p class="text-xs text-gray-500">Dibatalkan</p>
                    <p class="text-2xl font-bold text-red-500">{{ $revoked }}</p>
                </div>
            </div>

            {{-- Form generate --}}
            <div class="bg-white overflow-hidden shadow-lg sm:rounded-xl mb-6">
                <div class="bg-gradient-to-r from-emerald-500 to-teal-600 px-6 py-4">
                    <h3 class="text-lg font-semibold text-white flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Generate Kode
                    </h3>
                </div>
                <form action="{{ route('courses.enrollment-codes.store', $course) }}" method="POST" class="p-6">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Berlaku untuk</label>
                            <select name="target" class="w-full border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500">
                                <option value="course">Course (umum): {{ $course->title }}</option>
                                @foreach ($course->classes as $class)
                                    <option value="class:{{ $class->id }}">Kelas: {{ $class->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah kode</label>
                            <input type="number" name="count" value="1" min="1" max="500"
                                   class="w-full border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Bind ke email <span class="text-gray-400 font-normal">(opsional)</span>
                            </label>
                            <input type="email" name="issued_to_email" placeholder="email pembeli, kosongkan jika tanpa bind"
                                   class="w-full border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500">
                            <p class="text-xs text-gray-500 mt-1">Jika diisi: hanya email ini yang bisa memakai kode. Disarankan saat 1 kode untuk 1 pembeli.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Kadaluarsa <span class="text-gray-400 font-normal">(opsional)</span>
                            </label>
                            <input type="date" name="expires_at"
                                   class="w-full border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Prefix <span class="text-gray-400 font-normal">(opsional)</span>
                            </label>
                            <input type="text" name="prefix" placeholder="mis. BASS" maxlength="10"
                                   class="w-full border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 uppercase">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Panjang kode acak</label>
                            <select name="length" class="w-full border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500">
                                <option value="8">8 karakter</option>
                                <option value="10" selected>10 karakter</option>
                                <option value="12">12 karakter</option>
                                <option value="16">16 karakter</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="px-5 py-2.5 bg-emerald-600 text-white font-medium rounded-lg hover:bg-emerald-700 transition-colors shadow-sm">
                            Generate Kode
                        </button>
                    </div>
                </form>
            </div>

            {{-- Daftar kode --}}
            <div class="bg-white overflow-hidden shadow-lg sm:rounded-xl"
                 x-data="{
                    availableCodes: {{ \Illuminate\Support\Js::from($codes->where('status', \App\Models\EnrollmentCode::STATUS_AVAILABLE)->pluck('code')->values()) }}
                 }">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Daftar Kode</h3>
                    <button type="button"
                            x-show="availableCodes.length > 0"
                            @click="navigator.clipboard.writeText(availableCodes.join('\n'))"
                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-emerald-700 bg-emerald-50 rounded-lg hover:bg-emerald-100 transition-colors">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        Salin semua kode tersedia
                    </button>
                </div>

                @if ($codes->isEmpty())
                    <div class="text-center py-12">
                        <p class="text-gray-500">Belum ada kode. Generate di atas untuk membuat.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kode</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Berlaku untuk</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Bind email</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Dipakai oleh</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kadaluarsa</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($codes as $code)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3">
                                            <div class="flex items-center space-x-2">
                                                <code class="font-mono text-sm font-bold text-gray-900">{{ $code->code }}</code>
                                                <button type="button"
                                                        x-data
                                                        @click="navigator.clipboard.writeText('{{ $code->code }}')"
                                                        class="text-gray-400 hover:text-emerald-600" title="Salin">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            @if ($code->course_class_id)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-indigo-50 text-indigo-700">
                                                    Kelas: {{ $code->courseClass->name ?? '-' }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-purple-50 text-purple-700">
                                                    Course (umum)
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            {{ $code->issued_to_email ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($code->status === \App\Models\EnrollmentCode::STATUS_AVAILABLE)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Tersedia</span>
                                                @if ($code->expires_at && $code->expires_at->isPast())
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 ml-1">Kadaluarsa</span>
                                                @endif
                                            @elseif ($code->status === \App\Models\EnrollmentCode::STATUS_REDEEMED)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Sudah dipakai</span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Dibatalkan</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            @if ($code->redeemer)
                                                <div class="font-medium">{{ $code->redeemer->name }}</div>
                                                <div class="text-xs text-gray-400">{{ optional($code->redeemed_at)->format('d M Y H:i') }}</div>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            {{ optional($code->expires_at)->format('d M Y') ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            @if ($code->status !== \App\Models\EnrollmentCode::STATUS_REDEEMED)
                                                <div class="inline-flex items-center gap-2">
                                                    @if ($code->status === \App\Models\EnrollmentCode::STATUS_AVAILABLE)
                                                        <form action="{{ route('courses.enrollment-codes.revoke', [$course, $code]) }}" method="POST"
                                                              onsubmit="return confirm('Batalkan kode ini? Setelah dibatalkan tidak bisa dipakai.')">
                                                            @csrf
                                                            <button type="submit" class="text-xs font-medium text-amber-700 hover:text-amber-900">Batalkan</button>
                                                        </form>
                                                    @endif
                                                    <form action="{{ route('courses.enrollment-codes.destroy', [$course, $code]) }}" method="POST"
                                                          onsubmit="return confirm('Hapus kode ini permanen?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-xs font-medium text-red-600 hover:text-red-800">Hapus</button>
                                                    </form>
                                                </div>
                                            @else
                                                <span class="text-xs text-gray-400">—</span>
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
</x-app-layout>
