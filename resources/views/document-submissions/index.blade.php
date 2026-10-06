<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">
            Pengumpulan Dokumen — {{ $content->title }}
        </h2>
    </x-slot>

    <div class="py-8 max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
        @if(session('success'))
            <div class="p-4 bg-success-soft border border-success/40 text-success rounded-lg">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="p-4 bg-error-soft border border-error/40 text-error rounded-lg">{{ session('error') }}</div>
        @endif

        @php $byUser = $submissions->groupBy('user_id'); @endphp

        @forelse($byUser as $userId => $attempts)
            @php
                $attempts = $attempts->sortByDesc('attempt');
                $latest = $attempts->first();
                $participant = $latest->user;
                // Attempt yang bisa dinilai = attempt terbaru yang bukan draft.
                $gradable = $attempts->firstWhere(fn($s) => $s->status !== 'draft');
            @endphp
            <div class="bg-white shadow rounded-2xl overflow-hidden" x-data="{ open: false }">
                <div class="flex items-center justify-between gap-3 p-5">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-11 h-11 rounded-full bg-navy/10 text-navy flex items-center justify-center font-semibold flex-shrink-0">
                            {{ strtoupper(mb_substr($participant->name ?? '?', 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="font-semibold text-gray-900 truncate">{{ $participant->name ?? '-' }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ $participant->email ?? '' }} • {{ $attempts->count() }} percobaan</p>
                        </div>
                    </div>
                    @php
                        $badge = match($latest->status) {
                            'passed' => ['Lulus', 'bg-success-soft text-success'],
                            'failed' => ['Belum lulus', 'bg-error-soft text-error'],
                            'submitted' => ['Perlu dinilai', 'bg-warning-soft text-warning'],
                            default => ['Draft', 'bg-gray-100 text-gray-600'],
                        };
                    @endphp
                    <span class="px-3 py-1 rounded-full text-xs font-medium flex-shrink-0 {{ $badge[1] }}">{{ $badge[0] }}</span>
                </div>

                {{-- Daftar attempt --}}
                <div class="border-t border-gray-100 divide-y divide-gray-100">
                    @foreach($attempts as $sub)
                        @php
                            $b = match($sub->status) {
                                'passed' => ['Lulus', 'bg-success-soft text-success'],
                                'failed' => ['Belum lulus', 'bg-error-soft text-error'],
                                'submitted' => ['Menunggu', 'bg-warning-soft text-warning'],
                                default => ['Draft', 'bg-gray-100 text-gray-600'],
                            };
                        @endphp
                        <div class="px-5 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="text-xs font-semibold text-gray-400">#{{ $sub->attempt }}</span>
                                    <span class="truncate text-sm text-gray-800" title="{{ $sub->original_name }}">{{ $sub->original_name ?: '— belum ada file —' }}</span>
                                    <span class="text-[11px] px-2 py-0.5 rounded-full {{ $b[1] }}">{{ $b[0] }}@if($sub->score !== null && $content->isScoringEnabled()) · {{ $sub->score }}@endif</span>
                                </div>
                                <div class="flex items-center gap-3 flex-shrink-0 text-xs text-gray-500">
                                    <span>{{ $sub->submitted_at?->format('d M Y H:i') ?? '—' }}</span>
                                    @if($sub->file_path)
                                        <a href="{{ route('document-submissions.download', [$content, $sub]) }}"
                                           class="inline-flex items-center px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium">Unduh</a>
                                    @endif
                                </div>
                            </div>
                            @if($sub->feedback)
                                <p class="text-xs text-gray-500 mt-1.5 pl-6"><span class="font-semibold">Feedback:</span> {{ $sub->feedback }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Form penilaian untuk attempt terbaru yang sudah dikumpulkan --}}
                @if($gradable)
                    <div class="border-t border-gray-100 bg-gray-50 p-5">
                        <button type="button" @click="open = !open"
                                class="text-sm font-semibold text-navy hover:text-navy-light">
                            {{ $gradable->isGraded() ? 'Ubah Penilaian' : 'Nilai Percobaan #' . $gradable->attempt }}
                            <span x-show="!open">▾</span><span x-show="open" x-cloak>▴</span>
                        </button>
                        <form x-show="open" x-cloak method="POST"
                              action="{{ route('document-submissions.grade', [$content, $gradable]) }}"
                              class="mt-4 space-y-4">
                            @csrf
                            <div>
                                <span class="block text-sm font-semibold text-gray-700 mb-2">Hasil Penilaian (percobaan #{{ $gradable->attempt }})</span>
                                <div class="flex gap-3">
                                    <label class="flex-1 cursor-pointer">
                                        <input type="radio" name="result" value="passed" class="peer sr-only" {{ $gradable->status === 'passed' ? 'checked' : '' }} required>
                                        <div class="p-3 text-center rounded-xl border-2 border-gray-200 peer-checked:border-success peer-checked:bg-success-soft">
                                            <div class="text-xl">✅</div><div class="text-sm font-medium text-gray-800">Lulus</div>
                                        </div>
                                    </label>
                                    <label class="flex-1 cursor-pointer">
                                        <input type="radio" name="result" value="failed" class="peer sr-only" {{ $gradable->status === 'failed' ? 'checked' : '' }}>
                                        <div class="p-3 text-center rounded-xl border-2 border-gray-200 peer-checked:border-error peer-checked:bg-error-soft">
                                            <div class="text-xl">🔁</div><div class="text-sm font-medium text-gray-800">Belum lulus</div>
                                        </div>
                                    </label>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Bila "Belum lulus", peserta otomatis dapat mengunggah percobaan berikutnya.</p>
                            </div>

                            @if($content->isScoringEnabled())
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nilai (opsional, 0–100)</label>
                                    <input type="number" name="score" min="0" max="100" value="{{ $gradable->score }}"
                                           class="w-32 rounded-lg border-gray-300 text-sm focus:border-bass-red focus:ring-bass-red/50">
                                </div>
                            @endif

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Feedback / Catatan Revisi</label>
                                <textarea name="feedback" rows="3"
                                          class="w-full rounded-lg border-gray-300 text-sm focus:border-bass-red focus:ring-bass-red/50"
                                          placeholder="Catatan untuk peserta...">{{ $gradable->feedback }}</textarea>
                            </div>

                            <button type="submit" class="px-5 py-2.5 rounded-xl bg-bass-red text-white text-sm font-semibold hover:bg-bass-red-hover">
                                Simpan Penilaian
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        @empty
            <div class="bg-white shadow rounded-2xl p-10 text-center text-gray-400">Belum ada pengumpulan dari peserta.</div>
        @endforelse
    </div>
</x-app-layout>
