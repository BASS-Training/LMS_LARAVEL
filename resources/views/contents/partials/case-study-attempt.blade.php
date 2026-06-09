{{--
    Tampilan peserta untuk konten tipe "case_study".
    Merender template (bab/subbab + tabel ber-merge) dan menyediakan input.
    Variabel: $content
--}}
@php
    $user = auth()->user();
    $template = $content->case_study_template;
    $sections = $template['sections'] ?? [];

    $csSubmission = $content->caseStudySubmissions()->where('user_id', $user->id)->first();
    $csAnswers = $csSubmission?->answers ?? [];
    $isSubmitted = $csSubmission && in_array($csSubmission->status, ['submitted', 'graded']);
    $isManager = $user->can('manage own courses') || $user->can('update contents');
    // Peserta yang sudah submit -> tampilan read-only; instruktur selalu read-only.
    $readOnly = $isSubmitted || $isManager;

    // Helper ambil jawaban
    $answerFor = function ($sid, $bid, $rc = null) use ($csAnswers) {
        $blockAns = $csAnswers[$sid][$bid] ?? null;
        if ($rc === null) return is_string($blockAns) ? $blockAns : '';
        return is_array($blockAns) ? ($blockAns[$rc] ?? '') : '';
    };
@endphp

<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 lg:p-8">
    <div class="flex items-start justify-between flex-wrap gap-3 mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-900">📋 {{ $content->title }}</h2>
            @if($content->description)
                <p class="text-gray-600 mt-1">{{ $content->description }}</p>
            @endif
        </div>
        <div class="flex items-center gap-2">
            @if($isManager)
                <a href="{{ route('case-studies.submissions', $content) }}"
                   class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold rounded-lg shadow">
                    📥 Lihat Pengumpulan
                </a>
            @endif
            @if($isSubmitted)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-700">
                    ✓ {{ $csSubmission->status === 'graded' ? 'Sudah dinilai' : 'Sudah dikumpulkan' }}
                </span>
            @endif
            @if($isSubmitted && $content->allow_answer_download)
                <a href="{{ route('case-studies.download', $content) }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg shadow">
                    ⬇ Unduh PDF
                </a>
            @endif
        </div>
    </div>

    @if($csSubmission && $csSubmission->status === 'graded')
        <div class="mb-6 p-4 rounded-xl bg-blue-50 border border-blue-200">
            @if($content->scoring_enabled && !is_null($csSubmission->score))
                <p class="text-sm font-semibold text-blue-900">Nilai: {{ $csSubmission->score }}</p>
            @endif
            @if($csSubmission->feedback)
                <p class="text-sm text-blue-800 mt-1"><span class="font-semibold">Feedback:</span> {{ $csSubmission->feedback }}</p>
            @endif
        </div>
    @endif

    @if(empty($sections))
        <p class="text-gray-500 italic">Template studi kasus belum disusun.</p>
    @else
        <form id="caseStudyForm" method="POST" action="{{ route('case-studies.store', $content) }}">
            @csrf
            <div class="space-y-8">
                @foreach($sections as $section)
                    @php $sid = $section['id']; $level = $section['level'] ?? 1; @endphp
                    <section class="{{ $level === 2 ? 'ml-4 pl-4 border-l-2 border-amber-200' : '' }}">
                        <h3 class="{{ $level === 1 ? 'text-lg font-bold text-gray-900' : 'text-base font-semibold text-gray-800' }}">
                            {{ $section['title'] ?: ($level === 1 ? 'Bab' : 'Subbab') }}
                        </h3>
                        @if(!empty($section['instruction']))
                            <p class="text-sm text-gray-500 mt-1 mb-3">{{ $section['instruction'] }}</p>
                        @endif

                        <div class="space-y-4 mt-3">
                            @foreach(($section['blocks'] ?? []) as $block)
                                @php $bid = $block['id']; @endphp

                                @if(($block['kind'] ?? '') === 'text')
                                    <div>
                                        @if(!empty($block['label']))
                                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $block['label'] }}</label>
                                        @endif
                                        @php $editorId = 'cs_'.preg_replace('/[^a-zA-Z0-9]/', '', $sid.$bid); @endphp
                                        @if($readOnly)
                                            <div class="prose max-w-none border border-gray-200 rounded-lg p-3 bg-gray-50">
                                                {!! $answerFor($sid, $bid) ?: '<span class="text-gray-400 italic">(kosong)</span>' !!}
                                            </div>
                                        @else
                                            <x-forms.summernote-editor
                                                :id="$editorId"
                                                :name="'answers['.$sid.']['.$bid.']'"
                                                :value="$answerFor($sid, $bid)" />
                                        @endif
                                    </div>

                                @elseif(($block['kind'] ?? '') === 'table')
                                    @php $table = $block['table'] ?? ['cells' => []]; @endphp
                                    <div class="overflow-x-auto">
                                        <table class="border-collapse w-full">
                                            <tbody>
                                                @foreach(($table['cells'] ?? []) as $r => $row)
                                                    <tr>
                                                        @foreach($row as $c => $cell)
                                                            @continue(!empty($cell['covered']))
                                                            @php
                                                                $rc = $r.'-'.$c;
                                                                $bg = $cell['bg'] ?? '#ffffff';
                                                                $align = $cell['align'] ?? 'left';
                                                                $bold = !empty($cell['bold']);
                                                                $isInput = ($cell['role'] ?? 'input') === 'input';
                                                            @endphp
                                                            <td colspan="{{ $cell['colSpan'] ?? 1 }}" rowspan="{{ $cell['rowSpan'] ?? 1 }}"
                                                                class="border border-gray-400 p-2 align-top {{ $bold ? 'font-bold' : '' }}"
                                                                style="background: {{ $bg }}; text-align: {{ $align }};">
                                                                @if($isInput)
                                                                    @if($readOnly)
                                                                        <span class="text-sm text-gray-800">{{ $answerFor($sid, $bid, $rc) ?: '—' }}</span>
                                                                    @else
                                                                        <input type="text"
                                                                               name="answers[{{ $sid }}][{{ $bid }}][{{ $rc }}]"
                                                                               value="{{ $answerFor($sid, $bid, $rc) }}"
                                                                               placeholder="{{ $cell['text'] ?? '' }}"
                                                                               class="w-full px-2 py-1 border border-gray-200 rounded text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-200">
                                                                    @endif
                                                                @else
                                                                    <span class="text-sm text-gray-800">{{ $cell['text'] ?? '' }}</span>
                                                                @endif
                                                            </td>
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>

            @unless($readOnly)
                <div class="mt-8 flex items-center justify-between border-t border-gray-200 pt-6">
                    <span id="cs-autosave-status" class="text-xs text-gray-400"></span>
                    <button type="submit"
                            class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-xl hover:from-indigo-700 hover:to-purple-700 shadow-lg">
                        Kumpulkan Jawaban
                    </button>
                </div>
            @endunless
        </form>
    @endif
</div>

@unless($readOnly)
@push('scripts')
<script>
    (function () {
        const form = document.getElementById('caseStudyForm');
        if (!form) return;
        const statusEl = document.getElementById('cs-autosave-status');
        const autosaveUrl = "{{ route('case-studies.autosave', $content) }}";
        const csrf = "{{ csrf_token() }}";
        let timer = null;

        function autosave() {
            // Sinkronkan editor Summernote ke textarea sebelum serialize.
            if (window.jQuery && jQuery.fn.summernote) {
                jQuery('.summernote').each(function () {
                    const el = jQuery(this);
                    if (el.next('.note-editor').length) {
                        el.val(el.summernote('code'));
                    }
                });
            }
            const fd = new FormData(form);
            fd.append('_token', csrf);
            if (statusEl) statusEl.textContent = 'Menyimpan…';
            fetch(autosaveUrl, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: fd,
            }).then(r => r.json()).then(() => {
                if (statusEl) statusEl.textContent = 'Tersimpan otomatis ✓';
            }).catch(() => {
                if (statusEl) statusEl.textContent = 'Gagal menyimpan draft';
            });
        }

        function scheduleAutosave() {
            clearTimeout(timer);
            timer = setTimeout(autosave, 1500);
        }

        form.addEventListener('input', scheduleAutosave);
        // Summernote tidak memicu 'input' pada form; pakai event delegasi sederhana.
        document.addEventListener('summernote.change', scheduleAutosave);
    })();
</script>
@endpush
@endunless
