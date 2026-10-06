{{--
    Form pengisian konten tipe "feedback" untuk peserta (tanpa penilaian).
    Native POST ke feedback.store dengan answers[questionId].
--}}
@php
    $fbUser = auth()->user();
    $fbQuestions = $content->feedbackQuestions;
    $fbSubmission = $content->feedbackSubmissions()
        ->where('user_id', $fbUser->id)
        ->with('answers')
        ->first();
    $fbAnswers = [];
    if ($fbSubmission) {
        foreach ($fbSubmission->answers as $a) {
            $fbAnswers[$a->question_id] = $a;
        }
    }
    $fbAlreadySubmitted = $fbSubmission && $fbSubmission->status === 'submitted';
    $fbCanManage = $fbUser->can('manage own courses') || $fbUser->can('update contents');
@endphp

<div class="space-y-6">
    @if($content->body)
        <div class="prose max-w-none text-gray-700">{!! $content->body !!}</div>
    @endif

    {{-- Aksi instruktur: lihat ringkasan hasil --}}
    @if($fbCanManage)
        <div class="flex items-center justify-between bg-navy/5 border border-navy/10 rounded-xl px-4 py-3">
            <div class="text-sm text-gray-700">
                <span class="font-semibold">Mode pengelola.</span>
                Anda bisa melihat ringkasan tanggapan peserta.
            </div>
            <a href="{{ route('feedback.results', $content) }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-navy hover:bg-navy-light text-white text-sm font-semibold rounded-lg">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h10l4 4v12a2 2 0 01-2 2z"/>
                </svg>
                Lihat Hasil
            </a>
        </div>
    @endif

    @if($fbQuestions->isEmpty())
        <div class="text-center text-gray-500 bg-gray-50 border border-dashed rounded-xl py-10">
            Form feedback ini belum memiliki pertanyaan.
        </div>
    @else
        @if($fbAlreadySubmitted)
            <div class="flex items-center gap-2 bg-success-soft border border-success/40 text-success rounded-xl px-4 py-3 text-sm">
                <span class="text-lg">✓</span>
                <span>Anda sudah mengirim tanggapan. Anda masih bisa mengubahnya selama form terbuka.</span>
            </div>
        @endif

        @if($content->is_anonymous)
            <p class="text-xs text-gray-500 flex items-center gap-1">
                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                Tanggapan bersifat anonim - instruktur hanya melihat hasil agregat.
            </p>
        @endif

        <form action="{{ route('feedback.store', $content) }}" method="POST" class="space-y-5">
            @csrf

            @foreach($fbQuestions as $idx => $q)
                @php
                    $ans = $fbAnswers[$q->id] ?? null;
                    $cfg = $q->config ?? [];
                @endphp
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <div class="flex items-start gap-2 mb-3">
                        <span class="text-sm font-bold text-bass-red">{{ $idx + 1 }}.</span>
                        <div>
                            <p class="font-semibold text-gray-900">
                                {{ $q->question }}
                                @if($q->is_required)<span class="text-bass-red">*</span>@endif
                            </p>
                            @if($q->help_text)
                                <p class="text-xs text-gray-500 mt-0.5">{{ $q->help_text }}</p>
                            @endif
                        </div>
                    </div>

                    {{-- RATING --}}
                    @if($q->type === 'rating')
                        @php $max = (int)($cfg['max'] ?? 5); $cur = $ans->rating_value ?? 0; @endphp
                        <div x-data="{ val: {{ $cur }} }" class="pl-6">
                            <input type="hidden" name="answers[{{ $q->id }}]" :value="val || ''">
                            <div class="flex items-center gap-1">
                                @for($i = 1; $i <= $max; $i++)
                                    <button type="button" @click="val = {{ $i }}"
                                            class="text-3xl leading-none transition-transform hover:scale-110 focus:outline-none"
                                            :class="val >= {{ $i }} ? 'text-bass-gold' : 'text-gray-300'">★</button>
                                @endfor
                                <span class="ml-3 text-sm text-gray-500" x-text="val ? (val + ' / {{ $max }}') : 'Belum dipilih'"></span>
                            </div>
                            @if(($cfg['min_label'] ?? '') || ($cfg['max_label'] ?? ''))
                                <div class="flex justify-between text-xs text-gray-400 mt-1" style="max-width: {{ $max * 2.5 }}rem">
                                    <span>{{ $cfg['min_label'] ?? '' }}</span>
                                    <span>{{ $cfg['max_label'] ?? '' }}</span>
                                </div>
                            @endif
                        </div>

                    {{-- PILIHAN TUNGGAL --}}
                    @elseif($q->type === 'single_choice')
                        @php $cur = $ans->choice_value[0] ?? null; @endphp
                        <div class="pl-6 space-y-2">
                            @foreach(($cfg['options'] ?? []) as $opt)
                                <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-700">
                                    <input type="radio" name="answers[{{ $q->id }}]" value="{{ $opt['id'] }}"
                                           @checked($cur === $opt['id'])
                                            class="text-bass-red focus:ring-bass-red">
                                    <span>{{ $opt['label'] }}</span>
                                </label>
                            @endforeach
                        </div>

                    {{-- PILIHAN GANDA --}}
                    @elseif($q->type === 'multi_choice')
                        @php $curArr = $ans->choice_value ?? []; @endphp
                        <div class="pl-6 space-y-2">
                            @foreach(($cfg['options'] ?? []) as $opt)
                                <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-700">
                                    <input type="checkbox" name="answers[{{ $q->id }}][]" value="{{ $opt['id'] }}"
                                           @checked(in_array($opt['id'], $curArr))
                                            class="rounded text-bass-red focus:ring-bass-red">
                                    <span>{{ $opt['label'] }}</span>
                                </label>
                            @endforeach
                        </div>

                    {{-- TEKS BEBAS --}}
                    @else
                        <div class="pl-6">
                            <textarea name="answers[{{ $q->id }}]" rows="3"
                                      class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:border-bass-red focus:ring-2 focus:ring-bass-red/50"
                                      placeholder="Tulis jawaban Anda...">{{ $ans->text_value ?? '' }}</textarea>
                        </div>
                    @endif
                </div>
            @endforeach

            <div class="flex justify-end">
                <button type="submit"
                        class="inline-flex items-center px-8 py-3 bg-bass-red hover:bg-bass-red-hover text-white font-semibold rounded-xl shadow-lg">
                    {{ $fbAlreadySubmitted ? 'Perbarui Tanggapan' : 'Kirim Tanggapan' }}
                </button>
            </div>
        </form>
    @endif
</div>
