{{--
    Builder untuk konten tipe "feedback" (form survei ala Google Form, tanpa nilai).
    Menghasilkan input bernama questions[i][...] + is_anonymous yang dikirim via
    native form submit. Dipakai di dalam <form> contentFormManager.
--}}
@php
    $feedbackInit = [];
    if ($content->exists && $content->type === 'feedback') {
        foreach ($content->feedbackQuestions as $q) {
            $cfg = $q->config ?? [];
            $feedbackInit[] = [
                'id' => $q->id,
                'type' => $q->type,
                'question' => $q->question,
                'help_text' => $q->help_text ?? '',
                'is_required' => (bool) $q->is_required,
                'scale_max' => $cfg['max'] ?? 5,
                'min_label' => $cfg['min_label'] ?? '',
                'max_label' => $cfg['max_label'] ?? '',
                'options' => array_map(fn ($o) => $o['label'] ?? '', $cfg['options'] ?? []),
            ];
        }
    }
@endphp

<div x-data="feedbackBuilder({
        initial: @js($feedbackInit),
        anonymous: @js((bool) ($content->is_anonymous ?? false)),
     })"
     class="bg-gradient-to-r from-sky-50 to-indigo-50 rounded-xl p-6 border border-sky-100 space-y-5">

    <div class="flex items-start justify-between flex-wrap gap-3">
        <div>
            <h3 class="text-lg font-bold text-gray-900">💬 Penyusun Form Feedback</h3>
            <p class="text-sm text-gray-600">Susun pertanyaan survei. Tidak ada penilaian — peserta hanya memberi tanggapan.</p>
        </div>
        <button type="button" @click="addQuestion()"
                class="inline-flex items-center px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold rounded-lg shadow">
            + Tambah Pertanyaan
        </button>
    </div>

    {{-- Pengaturan form --}}
    <div class="bg-white rounded-lg border border-sky-200 p-4">
        <label class="flex items-center gap-2 cursor-pointer text-sm">
            {{-- native checkbox: absen saat tidak dicentang → boolean() false --}}
            <input type="checkbox" name="is_anonymous" value="1" x-model="anonymous"
                   class="rounded text-sky-600 focus:ring-sky-500">
            <span>Jawaban anonim (instruktur hanya melihat hasil agregat, tidak tahu siapa menjawab)</span>
        </label>
    </div>

    {{-- Daftar pertanyaan --}}
    <div class="space-y-4">
        <template x-if="questions.length === 0">
            <div class="text-center text-sm text-gray-500 bg-white border border-dashed border-sky-300 rounded-lg py-8">
                Belum ada pertanyaan. Klik "Tambah Pertanyaan" untuk mulai.
            </div>
        </template>

        <template x-for="(q, qIdx) in questions" :key="qIdx">
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 space-y-3">
                {{-- id tersembunyi (untuk update pertanyaan yang sudah ada) --}}
                <template x-if="q.id">
                    <input type="hidden" :name="'questions['+qIdx+'][id]'" :value="q.id">
                </template>

                <div class="flex items-start gap-3">
                    <span class="mt-2 text-xs font-bold text-sky-600" x-text="(qIdx+1)+'.'"></span>
                    <div class="flex-1 space-y-2">
                        <input type="text" :name="'questions['+qIdx+'][question]'" x-model="q.question"
                               class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm font-semibold focus:border-sky-500 focus:ring-2 focus:ring-sky-100"
                               placeholder="Tulis pertanyaan (mis. Seberapa puas Anda dengan pelatihan ini?)">
                        <input type="text" :name="'questions['+qIdx+'][help_text]'" x-model="q.help_text"
                               class="w-full px-3 py-1.5 border border-gray-100 rounded-lg text-xs text-gray-600 focus:border-sky-400"
                               placeholder="Teks bantuan / penjelasan (opsional)">
                    </div>
                    <select :name="'questions['+qIdx+'][type]'" x-model="q.type"
                            class="px-2 py-2 border border-gray-200 rounded-lg text-sm focus:border-sky-500">
                        <option value="rating">⭐ Rating</option>
                        <option value="single_choice">🔘 Pilihan tunggal</option>
                        <option value="multi_choice">☑️ Pilihan ganda</option>
                        <option value="text">📝 Teks bebas</option>
                    </select>
                </div>

                {{-- Konfigurasi: RATING --}}
                <template x-if="q.type === 'rating'">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pl-6">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Skala maksimum</label>
                            <input type="number" min="2" max="10" :name="'questions['+qIdx+'][scale_max]'" x-model.number="q.scale_max"
                                   class="w-full px-3 py-2 border border-gray-200 rounded text-sm">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Label nilai terendah (opsional)</label>
                            <input type="text" :name="'questions['+qIdx+'][min_label]'" x-model="q.min_label"
                                   class="w-full px-3 py-2 border border-gray-200 rounded text-sm" placeholder="mis. Sangat tidak puas">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Label nilai tertinggi (opsional)</label>
                            <input type="text" :name="'questions['+qIdx+'][max_label]'" x-model="q.max_label"
                                   class="w-full px-3 py-2 border border-gray-200 rounded text-sm" placeholder="mis. Sangat puas">
                        </div>
                        <div class="sm:col-span-3 flex items-center gap-1 text-amber-400">
                            <template x-for="n in (parseInt(q.scale_max) || 5)" :key="n">
                                <span class="text-xl">★</span>
                            </template>
                            <span class="ml-2 text-xs text-gray-400">(pratinjau)</span>
                        </div>
                    </div>
                </template>

                {{-- Konfigurasi: PILIHAN --}}
                <template x-if="q.type === 'single_choice' || q.type === 'multi_choice'">
                    <div class="pl-6 space-y-2">
                        <template x-for="(opt, oIdx) in q.options" :key="oIdx">
                            <div class="flex items-center gap-2">
                                <span class="text-gray-400" x-text="q.type === 'single_choice' ? '○' : '☐'"></span>
                                <input type="text" :name="'questions['+qIdx+'][options][]'" x-model="q.options[oIdx]"
                                       class="flex-1 px-3 py-1.5 border border-gray-200 rounded text-sm"
                                       :placeholder="'Opsi ' + (oIdx+1)">
                                <button type="button" @click="removeOption(q, oIdx)"
                                        class="p-1 text-red-400 hover:bg-red-50 rounded" title="Hapus opsi">✕</button>
                            </div>
                        </template>
                        <button type="button" @click="addOption(q)"
                                class="text-xs text-sky-600 hover:text-sky-800 font-medium">+ Tambah opsi</button>
                    </div>
                </template>

                {{-- Konfigurasi: TEKS --}}
                <template x-if="q.type === 'text'">
                    <p class="pl-6 text-xs text-gray-400">Peserta akan mengisi jawaban teks bebas.</p>
                </template>

                {{-- Baris aksi --}}
                <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                    <label class="flex items-center gap-2 text-xs text-gray-600 cursor-pointer">
                        <input type="checkbox" value="1" :name="'questions['+qIdx+'][is_required]'" x-model="q.is_required"
                               class="rounded text-sky-600 focus:ring-sky-500">
                        Wajib dijawab
                    </label>
                    <div class="flex items-center gap-1">
                        <button type="button" @click="moveQuestion(qIdx, -1)" class="p-1.5 text-gray-400 hover:bg-gray-100 rounded" title="Naik">▲</button>
                        <button type="button" @click="moveQuestion(qIdx, 1)" class="p-1.5 text-gray-400 hover:bg-gray-100 rounded" title="Turun">▼</button>
                        <button type="button" @click="removeQuestion(qIdx)" class="p-1.5 text-red-500 hover:bg-red-50 rounded" title="Hapus">🗑</button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>

@push('scripts')
<script>
    function feedbackBuilder(cfg) {
        return {
            anonymous: !!cfg.anonymous,
            questions: Array.isArray(cfg.initial) ? cfg.initial.map(q => ({
                id: q.id ?? null,
                type: q.type || 'rating',
                question: q.question || '',
                help_text: q.help_text || '',
                is_required: !!q.is_required,
                scale_max: q.scale_max || 5,
                min_label: q.min_label || '',
                max_label: q.max_label || '',
                options: Array.isArray(q.options) && q.options.length ? [...q.options] : ['', ''],
            })) : [],

            addQuestion() {
                this.questions.push({
                    id: null, type: 'rating', question: '', help_text: '',
                    is_required: false, scale_max: 5, min_label: '', max_label: '',
                    options: ['', ''],
                });
            },
            removeQuestion(idx) {
                if (!confirm('Hapus pertanyaan ini?')) return;
                this.questions.splice(idx, 1);
            },
            moveQuestion(idx, dir) {
                const target = idx + dir;
                if (target < 0 || target >= this.questions.length) return;
                const arr = this.questions;
                [arr[idx], arr[target]] = [arr[target], arr[idx]];
            },
            addOption(q) {
                if (!Array.isArray(q.options)) q.options = [];
                q.options.push('');
            },
            removeOption(q, oIdx) {
                if (q.options.length <= 1) return;
                q.options.splice(oIdx, 1);
            },
        };
    }
</script>
@endpush
