@php
    $user = auth()->user();
    // Instruktur/admin: tampilkan pintu ke halaman penilaian, bukan form unggah.
    $isManager = $user && ($user->can('manage own courses') || $user->can('update contents'));

    // Semua attempt milik peserta yang login untuk konten ini (urut naik).
    $mySubs = $content->documentSubmissions()
        ->where('user_id', auth()->id())
        ->orderBy('attempt')
        ->get();
    $latest = $mySubs->last();

    $isDraft   = $latest && $latest->status === 'draft';
    $isWaiting = $latest && $latest->status === 'submitted';
    $isPassed  = $latest && $latest->status === 'passed';
    $isFailed  = $latest && $latest->status === 'failed';
    $hasFile   = $latest && $latest->file_path;

    // Boleh unggah bila belum ada attempt, attempt draft, atau attempt terakhir gagal.
    $canUpload = !$latest || $isDraft || $isFailed;
    // Nomor percobaan yang akan diunggah bila memulai attempt baru.
    $nextAttempt = $isFailed ? ($latest->attempt + 1) : ($latest->attempt ?? 1);

    $allowedTypes = $content->submission_allowed_types ?: 'pdf,doc,docx,ppt,pptx,xls,xlsx,txt,jpg,jpeg,png,zip,rar';
    $maxMb = $content->submission_max_size_mb ?: 20;
    $acceptAttr = collect(explode(',', $allowedTypes))->map(fn($e) => '.' . trim($e))->implode(',');

    $fmtSize = function ($bytes) {
        if (!$bytes) return '';
        $units = ['B','KB','MB','GB'];
        $i = 0; $b = $bytes;
        while ($b >= 1024 && $i < count($units) - 1) { $b /= 1024; $i++; }
        return round($b, $b < 10 && $i > 0 ? 1 : 0) . ' ' . $units[$i];
    };
@endphp

<div class="max-w-4xl mx-auto px-6 lg:px-8 pb-6">
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="bg-gradient-to-r from-indigo-500 to-blue-600 p-6 text-white">
            <h3 class="text-xl font-bold flex items-center">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Pengumpulan Tugas
            </h3>
            <p class="text-indigo-100 mt-1">Unggah dokumen tugas Anda, lalu kumpulkan untuk dinilai.</p>
        </div>

        @if($isManager)
            {{-- Tampilan instruktur/admin: ringkas + tombol ke halaman penilaian --}}
            @php $totalSubs = $content->documentSubmissions()->where('status', '!=', 'draft')->distinct('user_id')->count('user_id'); @endphp
            <div class="p-6 lg:p-8 flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <p class="text-sm text-gray-600">Peserta mengumpulkan dokumen tugas di sini.</p>
                    <p class="text-lg font-semibold text-gray-900 mt-1">{{ $totalSubs }} peserta telah mengumpulkan</p>
                </div>
                <a href="{{ route('document-submissions.index', $content) }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 shadow">
                    📥 Lihat & Nilai Pengumpulan
                </a>
            </div>
        @else
        <div class="p-6 lg:p-8 space-y-5">
            {{-- Flash --}}
            @if(session('success'))
                <div class="rounded-xl bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">{{ session('error') }}</div>
            @endif
            @error('file')
                <div class="rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">{{ $message }}</div>
            @enderror

            {{-- Instruksi tugas --}}
            @if($content->submission_instructions)
                <div class="rounded-xl bg-blue-50 border border-blue-100 p-4">
                    <p class="text-xs font-semibold text-blue-700 uppercase tracking-wide mb-1">Instruksi</p>
                    <p class="text-sm text-gray-700 whitespace-pre-line">{{ $content->submission_instructions }}</p>
                </div>
            @endif

            {{-- Status banner --}}
            @if($isWaiting)
                <div class="flex items-start gap-3 rounded-xl bg-amber-50 border border-amber-200 p-4">
                    <span class="text-2xl">⏳</span>
                    <div>
                        <p class="font-semibold text-amber-800">Menunggu penilaian</p>
                        <p class="text-sm text-amber-700">Tugas Anda (percobaan ke-{{ $latest->attempt }}) sudah dikumpulkan dan sedang menunggu penilaian instruktur.</p>
                    </div>
                </div>
            @elseif($isPassed)
                <div class="flex items-start gap-3 rounded-xl bg-green-50 border border-green-200 p-4">
                    <span class="text-2xl">✅</span>
                    <div>
                        <p class="font-semibold text-green-800">Lulus @if($latest->score !== null && $content->isScoringEnabled())<span class="font-normal">— Nilai: {{ $latest->score }}</span>@endif</p>
                        <p class="text-sm text-green-700">Selamat! Tugas Anda telah dinilai lulus.</p>
                        @if($latest->feedback)<p class="text-sm text-green-800 mt-2"><span class="font-semibold">Feedback:</span> {{ $latest->feedback }}</p>@endif
                    </div>
                </div>
            @elseif($isFailed)
                <div class="flex items-start gap-3 rounded-xl bg-red-50 border border-red-200 p-4">
                    <span class="text-2xl">🔁</span>
                    <div>
                        <p class="font-semibold text-red-800">Belum lulus @if($latest->score !== null && $content->isScoringEnabled())<span class="font-normal">— Nilai: {{ $latest->score }}</span>@endif</p>
                        <p class="text-sm text-red-700">Percobaan ke-{{ $latest->attempt }} belum lulus. Silakan perbaiki dan unggah percobaan berikutnya.</p>
                        @if($latest->feedback)<p class="text-sm text-red-800 mt-2"><span class="font-semibold">Catatan revisi:</span> {{ $latest->feedback }}</p>@endif
                    </div>
                </div>
            @endif

            {{-- File pada attempt draft aktif (sebelum dikumpulkan) --}}
            @if($isDraft && $hasFile)
                <div class="flex items-center justify-between gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 flex-shrink-0 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="font-medium text-gray-900 truncate" title="{{ $latest->original_name }}">{{ $latest->original_name }}</p>
                            <p class="text-xs text-gray-500">{{ $fmtSize($latest->file_size) }} • Belum dikumpulkan</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <a href="{{ route('document-submissions.download', [$content, $latest]) }}" class="text-sm text-indigo-600 hover:underline">Unduh</a>
                        <form method="POST" action="{{ route('document-submissions.remove-file', $content) }}" onsubmit="return confirm('Hapus file ini?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-sm text-red-600 hover:underline">Hapus</button>
                        </form>
                    </div>
                </div>
            @endif

            {{-- Area unggah (draft baru / ganti / attempt berikutnya) --}}
            @if($canUpload)
                <form method="POST" action="{{ route('document-submissions.upload', $content) }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <label class="block">
                        <span class="block text-sm font-semibold text-gray-700 mb-1">
                            @if($isFailed)
                                Unggah Percobaan ke-{{ $nextAttempt }}
                            @elseif($hasFile)
                                Ganti File
                            @else
                                Unggah File Tugas
                            @endif
                        </span>
                        <input type="file" name="file" required accept="{{ $acceptAttr }}"
                               class="block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-600 file:text-white hover:file:bg-indigo-700 cursor-pointer">
                    </label>
                    <p class="text-xs text-gray-500">Tipe: {{ strtoupper(str_replace(',', ', ', $allowedTypes)) }} • Maks {{ $maxMb }} MB</p>
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        {{ $hasFile && $isDraft ? 'Ganti File' : 'Unggah' }}
                    </button>
                </form>
            @endif

            {{-- Tombol Kumpulkan (kunci) --}}
            @if($isDraft && $hasFile)
                <form method="POST" action="{{ route('document-submissions.submit', $content) }}"
                      onsubmit="return confirm('Kumpulkan tugas ini? Setelah dikumpulkan tidak bisa diubah sampai dinilai.')">
                    @csrf
                    <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-green-600 text-white font-semibold hover:bg-green-700 shadow-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Kumpulkan Tugas
                    </button>
                    <p class="text-center text-xs text-gray-500 mt-2">🔒 Setelah dikumpulkan, tugas terkunci hingga dinilai instruktur.</p>
                </form>
            @endif

            {{-- Riwayat percobaan --}}
            @php $history = $mySubs->filter(fn($s) => $s->status !== 'draft'); @endphp
            @if($history->count())
                <div x-data="{ open: false }" class="pt-2 border-t border-gray-100">
                    <button type="button" @click="open = !open" class="flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-gray-900">
                        <svg class="w-4 h-4 transition-transform" :class="open && 'rotate-90'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        Riwayat Pengumpulan ({{ $history->count() }})
                    </button>
                    <div x-show="open" x-cloak class="mt-3 space-y-2">
                        @foreach($history->sortByDesc('attempt') as $sub)
                            @php
                                $badge = match($sub->status) {
                                    'passed' => ['Lulus', 'bg-green-100 text-green-700'],
                                    'failed' => ['Belum lulus', 'bg-red-100 text-red-700'],
                                    default  => ['Menunggu', 'bg-amber-100 text-amber-700'],
                                };
                            @endphp
                            <div class="rounded-lg border border-gray-200 p-3">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="text-xs font-semibold text-gray-500">#{{ $sub->attempt }}</span>
                                        <span class="truncate text-sm text-gray-800" title="{{ $sub->original_name }}">{{ $sub->original_name ?: '—' }}</span>
                                    </div>
                                    <div class="flex items-center gap-3 flex-shrink-0">
                                        <span class="text-[11px] px-2 py-0.5 rounded-full {{ $badge[1] }}">{{ $badge[0] }}@if($sub->score !== null && $content->isScoringEnabled()) · {{ $sub->score }}@endif</span>
                                        @if($sub->file_path)
                                            <a href="{{ route('document-submissions.download', [$content, $sub]) }}" class="text-xs text-indigo-600 hover:underline">Unduh</a>
                                        @endif
                                    </div>
                                </div>
                                @if($sub->feedback)
                                    <p class="text-xs text-gray-600 mt-2"><span class="font-semibold">Feedback:</span> {{ $sub->feedback }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
        @endif
    </div>
</div>
