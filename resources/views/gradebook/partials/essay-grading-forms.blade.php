@if($submission->content->grading_mode === 'individual' && $submission->content->scoring_enabled)
    <div class="bg-gray-50 rounded-2xl p-4 sm:p-6 border border-gray-200">
        <h4 class="font-bold text-navy mb-4">Penilaian Per Pertanyaan</h4>
        
        <form action="{{ route('gradebook.store-multi-grade', $submission) }}" method="POST">
            @csrf
            @foreach($submission->answers as $index => $answer)
                <div class="mb-6 p-4 border border-gray-200 rounded-xl bg-white shadow-sm">
                    <h5 class="font-medium text-gray-800 mb-2">Pertanyaan {{ $index + 1 }}</h5>
                    
                    @if($answer->question)
                        <div class="text-gray-700 mb-3">{!! $answer->question->question !!}</div>
                    @endif
                    
                    <div class="bg-gray-50 p-3 rounded mb-3">
                        <strong>Jawaban:</strong> {!! app(\App\Services\ParticipantRichTextSanitizer::class)->sanitize($answer->answer) !!}
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nilai</label>
                            <input type="number" 
                                   name="scores[{{ $answer->id }}]" 
                                   value="{{ $answer->score }}"
                                   class="w-full border-gray-300 rounded-lg focus:border-bass-red focus:ring-bass-red"
                                   min="0" max="100">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Feedback</label>
                            <textarea name="feedback[{{ $answer->id }}]" 
                                      rows="3"
                                      class="w-full border-gray-300 rounded-lg focus:border-bass-red focus:ring-bass-red">{{ $answer->feedback }}</textarea>
                        </div>
                    </div>
                </div>
            @endforeach
            
            <button type="submit" class="w-full sm:w-auto px-6 py-3 bg-bass-red text-white font-semibold rounded-lg hover:bg-bass-red-hover transition-colors">
                Simpan Nilai Per Pertanyaan
            </button>
        </form>
    </div>

{{-- Individual Grading tanpa Scoring --}}
@elseif($submission->content->grading_mode === 'individual' && !$submission->content->scoring_enabled)
    <div class="bg-gray-50 rounded-2xl p-4 sm:p-6 border border-gray-200">
        <h4 class="font-bold text-navy mb-4">Feedback Per Pertanyaan</h4>
        
        <form action="{{ route('gradebook.store-multi-grade', $submission) }}" method="POST">
            @csrf
            @foreach($submission->answers as $index => $answer)
                <div class="mb-6 p-4 border border-gray-200 rounded-xl bg-white shadow-sm">
                    <h5 class="font-medium text-gray-800 mb-2">Pertanyaan {{ $index + 1 }}</h5>
                    
                    @if($answer->question)
                        <div class="text-gray-700 mb-3">{!! $answer->question->question !!}</div>
                    @endif
                    
                    <div class="bg-gray-50 p-3 rounded mb-3">
                        <strong>Jawaban:</strong> {!! app(\App\Services\ParticipantRichTextSanitizer::class)->sanitize($answer->answer) !!}
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Feedback</label>
                        <textarea name="feedback[{{ $answer->id }}]" 
                                  rows="4"
                                  class="w-full border-gray-300 rounded-lg focus:border-bass-red focus:ring-bass-red">{{ $answer->feedback }}</textarea>
                    </div>
                </div>
            @endforeach
            
            <button type="submit" class="w-full sm:w-auto px-6 py-3 bg-bass-red text-white font-semibold rounded-lg hover:bg-bass-red-hover transition-colors">
                Simpan Feedback Per Pertanyaan
            </button>
        </form>
    </div>

{{-- Overall Grading dengan Scoring --}}
@elseif($submission->content->grading_mode === 'overall' && $submission->content->scoring_enabled)
    <div class="bg-gray-50 rounded-2xl p-4 sm:p-6 border border-gray-200">
        <h4 class="font-bold text-navy mb-4">Penilaian Keseluruhan</h4>
        
        {{-- Tampilkan semua soal dan jawaban --}}
        <div class="mb-6">
            @foreach($submission->answers as $index => $answer)
                <div class="mb-4 p-4 border border-gray-200 rounded-xl bg-white shadow-sm">
                    @if($answer->question)
                        <h5 class="font-medium text-gray-800 mb-2">Pertanyaan {{ $index + 1 }}: {{ $answer->question->question }}</h5>
                    @endif
                    <div class="bg-gray-50 p-3 rounded">
                        <strong>Jawaban:</strong> {!! app(\App\Services\ParticipantRichTextSanitizer::class)->sanitize($answer->answer) !!}
                    </div>
                </div>
            @endforeach
        </div>
        
        <form action="{{ route('gradebook.store-overall-grade', $submission) }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nilai Keseluruhan (0-100)</label>
                    <input type="number" 
                           name="overall_score" 
                           value="{{ $submission->answers->first()->score ?? '' }}"
                           class="w-full border-gray-300 rounded-lg text-center text-2xl font-bold focus:border-bass-red focus:ring-bass-red"
                           min="0" max="100" required>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Feedback Keseluruhan</label>
                    <textarea name="overall_feedback" 
                              rows="4"
                              class="w-full border-gray-300 rounded-lg focus:border-bass-red focus:ring-bass-red">{{ $submission->answers->first()->feedback ?? '' }}</textarea>
                </div>
            </div>
            
            <button type="submit" class="w-full sm:w-auto mt-4 px-6 py-3 bg-bass-red text-white font-semibold rounded-lg hover:bg-bass-red-hover transition-colors">
                Simpan Nilai Keseluruhan
            </button>
        </form>
    </div>

{{-- Overall Grading tanpa Scoring --}}
@else
    <div class="bg-gray-50 rounded-2xl p-4 sm:p-6 border border-gray-200">
        <h4 class="font-bold text-navy mb-4">Feedback Keseluruhan</h4>
        
        {{-- Tampilkan semua soal dan jawaban --}}
        <div class="mb-6">
            @foreach($submission->answers as $index => $answer)
                <div class="mb-4 p-4 border border-gray-200 rounded-xl bg-white shadow-sm">
                    @if($answer->question)
                        <h5 class="font-medium text-gray-800 mb-2">Pertanyaan {{ $index + 1 }}: {{ $answer->question->question }}</h5>
                    @endif
                    <div class="bg-gray-50 p-3 rounded">
                        <strong>Jawaban:</strong> {!! app(\App\Services\ParticipantRichTextSanitizer::class)->sanitize($answer->answer) !!}
                    </div>
                </div>
            @endforeach
        </div>
        
        <form action="{{ route('gradebook.storeEssayFeedbackOnly', $submission) }}" method="POST">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Feedback untuk Keseluruhan Esai</label>
                <textarea name="feedback" 
                          rows="6"
                          class="w-full border-gray-300 rounded-lg focus:border-bass-red focus:ring-bass-red"
                          placeholder="Berikan feedback konstruktif untuk keseluruhan esai..."
                          required>{{ $submission->answers->first()->feedback ?? '' }}</textarea>
            </div>
            
            <button type="submit" class="w-full sm:w-auto mt-4 px-6 py-3 bg-bass-red text-white font-semibold rounded-lg hover:bg-bass-red-hover transition-colors">
                Simpan Feedback Keseluruhan
            </button>
        </form>
    </div>
@endif
