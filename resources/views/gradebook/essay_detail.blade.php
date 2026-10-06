<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Penilaian Esai: {{ $submission->content->title }}
            </h2>
            <div class="flex flex-wrap items-center gap-3">
                {{-- Status badge untuk scoring --}}
                @php
                    $scoringEnabled = isset($scoringEnabled) ? $scoringEnabled : ($submission->content->scoring_enabled ?? true);
                    $gradingMode = isset($gradingMode) ? $gradingMode : ($submission->content->grading_mode ?? 'individual');
                @endphp
                
                @if($scoringEnabled)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success-soft text-success">
                        Dengan Penilaian
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                        Tanpa Penilaian
                    </span>
                @endif
                
                {{-- Grading Mode Badge --}}
                @if($gradingMode === 'overall')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-bass-red-soft text-bass-red">
                        Penilaian Keseluruhan
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-warning-soft text-warning">
                        Per Pertanyaan
                    </span>
                @endif
                
                <a href="{{ route('courses.gradebook', $submission->content->lesson->course) }}" 
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 hover:shadow-sm transition-all">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali ke Buku Nilai
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            @php
                $totalQuestions = $submission->content->essayQuestions()->count();
                
                // Calculate graded answers based on mode
                if ($gradingMode === 'overall') {
                    if ($scoringEnabled) {
                        $firstAnswer = $submission->answers()->first();
                        $gradedAnswers = ($firstAnswer && $firstAnswer->score !== null) ? $totalQuestions : 0;
                    } else {
                        $firstAnswer = $submission->answers()->first();
                        $gradedAnswers = ($firstAnswer && !empty($firstAnswer->feedback)) ? $totalQuestions : 0;
                    }
                } else {
                    if ($scoringEnabled) {
                        $gradedAnswers = $submission->answers()->whereNotNull('score')->count();
                    } else {
                        $gradedAnswers = $submission->answers()->whereNotNull('feedback')->count();
                    }
                }
            @endphp

            {{-- Progress Info --}}
            @if($totalQuestions > 0)
                <div class="bg-white overflow-hidden rounded-2xl border border-gray-200 shadow-sm mb-6">
                    <div class="p-4">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-1">Progres Penilaian</h3>
                                <p class="text-sm text-gray-600">
                                    Mode: {{ $gradingMode === 'overall' ? 'Penilaian Keseluruhan' : 'Penilaian Per Pertanyaan' }}
                                </p>
                                @if($gradingMode === 'overall')
                                    <p class="text-xs text-bass-red mt-1">
                                        @if($scoringEnabled)
                                            Satu nilai berlaku untuk seluruh {{ $totalQuestions }} pertanyaan
                                        @else
                                            Satu feedback berlaku untuk seluruh {{ $totalQuestions }} pertanyaan
                                        @endif
                                    </p>
                                @else
                                    <p class="text-xs text-warning mt-1">
                                        @if($scoringEnabled)
                                            Setiap pertanyaan mendapatkan nilai tersendiri
                                        @else
                                            Setiap pertanyaan mendapatkan feedback tersendiri
                                        @endif
                                    </p>
                                @endif
                            </div>
                            <div class="text-right">
                                @php
                                    $percentage = $totalQuestions > 0 ? round(($gradedAnswers / $totalQuestions) * 100) : 0;
                                @endphp
                                <div class="text-2xl font-bold text-gray-900">{{ $gradedAnswers }}/{{ $totalQuestions }}</div>
                                <div class="text-sm text-gray-600">
                                    @if($gradingMode === 'overall')
                                        {{ $gradedAnswers > 0 ? 'Semua Pertanyaan Dinilai' : 'Pertanyaan Dinilai' }}
                                    @else
                                        Pertanyaan Dinilai
                                    @endif
                                </div>
                                <div class="mt-2">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $percentage >= 100 ? 'bg-success-soft text-success' : 'bg-warning-soft text-warning' }}">
                                        {{ $percentage }}% Selesai
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="grade-progress-bar bg-bass-red h-2 rounded-full transition-all duration-500"
                                     style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- KONDISI INI YANG DIPERBAIKI: Overall mode berlaku untuk scoring dan non-scoring --}}
            @if($gradingMode === 'overall')
                {{-- OVERALL GRADING MODE (dengan atau tanpa scoring) --}}
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {{-- Left: Questions & Answers (Read-only) --}}
                    <div class="lg:col-span-2 space-y-4">
                        <div class="bg-white overflow-hidden rounded-2xl border border-gray-200 shadow-sm">
                            <div class="p-4 border-b border-bass-red/30 bg-bass-red-soft">
                                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-bass-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Pertanyaan dan Jawaban Esai
                                </h3>
                                <p class="text-sm text-gray-700 mt-1">
                                    @if($scoringEnabled)
                                        Tinjau semua pertanyaan dan jawaban sebelum memberikan nilai keseluruhan
                                    @else
                                        Tinjau semua pertanyaan dan jawaban sebelum memberikan feedback keseluruhan
                                    @endif
                                </p>
                            </div>
                            
                            <div class="p-4 space-y-6 max-h-[70vh] overflow-y-auto">
                                @if($totalQuestions > 0)
                                    @foreach($submission->content->essayQuestions()->orderBy('order')->get() as $index => $question)
                                        @php
                                            $answer = $submission->answers()->where('question_id', $question->id)->first();
                                        @endphp
                                        
                                        <div class="p-4 border border-gray-200 rounded-lg {{ $index > 0 ? 'mt-6' : '' }}">
                                            <div class="flex items-center justify-between mb-3">
                                                <h4 class="font-semibold text-gray-900">Pertanyaan {{ $index + 1 }}</h4>
                                                <span class="text-xs text-gray-500 px-2 py-1 bg-gray-100 rounded">
                                                    @if($scoringEnabled)
                                                        Maksimal: {{ $question->max_score }} poin
                                                    @else
                                                        Feedback Diperlukan
                                                    @endif
                                                </span>
                                            </div>
                                            
                                            <div class="p-3 bg-bass-red-soft rounded-lg border-l-4 border-bass-red mb-3">
                                                <p class="text-gray-900">{!! nl2br(e($question->question)) !!}</p>
                                            </div>
                                            
                                            <div class="p-3 bg-gray-50 rounded-lg border border-gray-200 min-h-[80px]">
                                                @if($answer && $answer->answer)
                                                    <div class="prose prose-sm max-w-none text-gray-800">{!! nl2br(e($answer->answer)) !!}</div>
                                                @else
                                                    <p class="text-gray-500 italic">Tidak ada jawaban untuk pertanyaan ini.</p>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    @php $answer = $submission->answers()->first(); @endphp
                                    <div class="p-4">
                                        <h4 class="font-semibold text-gray-900 mb-3">Jawaban Esai</h4>
                                        <div class="p-4 bg-gray-50 rounded-lg border border-gray-200 min-h-[200px]">
                                            @if($answer && $answer->answer)
                                                <div class="prose max-w-none text-gray-800">{!! nl2br(e($answer->answer)) !!}</div>
                                            @else
                                                <p class="text-gray-500 italic">Tidak ada jawaban.</p>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Right: Overall Assessment Form --}}
                    <div class="lg:sticky lg:top-6">
                        <form action="{{ 
                            $scoringEnabled 
                                ? route('gradebook.store-overall-grade', $submission)
                                : route('gradebook.store-overall-feedback', $submission)
                        }}" method="POST">
                            @csrf

                            <div class="bg-white overflow-hidden rounded-2xl border border-gray-200 shadow-sm">
                                <div class="p-4 border-b border-bass-red/30 bg-bass-red-soft">
                                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-bass-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                        </svg>
                                        Penilaian Keseluruhan
                                    </h3>
                                    <p class="text-sm text-gray-700 mt-1">
                                        @if($scoringEnabled)
                                            Berikan satu nilai untuk seluruh esai ({{ $totalQuestions }} pertanyaan)
                                        @else
                                            Berikan feedback untuk seluruh esai ({{ $totalQuestions }} pertanyaan)
                                        @endif
                                    </p>
                                </div>

                                <div class="p-4 space-y-4">
                                    @php
                                        $totalMaxScore = $submission->content->essayQuestions()->sum('max_score') ?: 100;
                                        $firstAnswer = $submission->answers()->first();
                                        $overallScore = $firstAnswer ? $firstAnswer->score : null;
                                        $overallFeedback = $firstAnswer ? $firstAnswer->feedback : '';
                                    @endphp

                                    @if($scoringEnabled)
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Nilai Keseluruhan</label>
                                            <div class="relative">
                                                <input type="number" name="overall_score" min="0" max="{{ $totalMaxScore }}"
                                                    value="{{ old('overall_score', $overallScore) }}"
                                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-bass-red focus:border-bass-red text-lg font-semibold"
                                                    placeholder="Masukkan nilai total" />
                                                <div class="absolute right-3 top-3 text-gray-500">/ {{ $totalMaxScore }}</div>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-1">Nilai ini berlaku untuk seluruh {{ $totalQuestions }} pertanyaan</p>
                                        </div>
                                    @endif

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            @if($scoringEnabled)
                                                Feedback Keseluruhan
                                            @else
                                                Feedback untuk Seluruh Esai
                                            @endif
                                        </label>
                                        <textarea name="overall_feedback" rows="8"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-bass-red focus:border-bass-red resize-none"
                                                placeholder="Berikan feedback menyeluruh untuk esai ini...">{{ old('overall_feedback', $overallFeedback) }}</textarea>
                                        <p class="text-xs text-gray-500 mt-1">Feedback ini berlaku untuk seluruh pengumpulan</p>
                                    </div>

                                    {{-- Quick Feedback Templates --}}
                                    <div class="border-t pt-4">
                                        <h4 class="text-sm font-medium text-gray-700 mb-2">Template Feedback Cepat:</h4>
                                        <div class="grid grid-cols-1 gap-2">
                                            <button type="button" onclick="setFeedback('Hasil sangat baik! Struktur jelas, argumen kuat, dan seluruh pertanyaan dibahas secara menyeluruh.')"
                                                    class="p-2 text-left bg-success-soft text-success text-xs rounded hover:bg-success transition-colors">
                                                Sangat Baik
                                            </button>
                                            <button type="button" onclick="setFeedback('Secara keseluruhan sudah baik. Sebagian besar pertanyaan dijawab dengan baik, tetapi beberapa bagian memerlukan detail dan contoh tambahan.')"
                                                    class="p-2 text-left bg-bass-red-soft text-bass-red text-xs rounded hover:bg-bass-red hover:text-white transition-colors">
                                                Baik
                                            </button>
                                            <button type="button" onclick="setFeedback('Perlu diperbaiki. Tinjau kembali setiap pertanyaan dan berikan jawaban yang lebih menyeluruh.')"
                                                    class="p-2 text-left bg-warning-soft text-warning text-xs rounded hover:bg-warning hover:text-white transition-colors">
                                                Perlu Diperbaiki
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Current Status --}}
                                    @if($overallScore !== null || $overallFeedback)
                                        <div class="border-t pt-4">
                                            <h4 class="text-sm font-medium text-gray-700 mb-2">Penilaian Saat Ini:</h4>
                                            @if($overallScore !== null)
                                                <p class="text-sm text-success">Nilai: <span class="font-bold">{{ $overallScore }}</span>/{{ $totalMaxScore }}</p>
                                            @endif
                                            @if($overallFeedback)
                                                <p class="text-sm text-gray-600 mt-1">{{ Str::limit($overallFeedback, 100) }}</p>
                                            @endif
                                        </div>
                                    @endif

                                    <button type="submit" class="w-full px-4 py-3 bg-bass-red hover:bg-bass-red-hover text-white font-medium rounded-lg transition-colors flex items-center justify-center space-x-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3-3m0 0l-3 3m3-3v12"></path>
                                        </svg>
                                        <span>
                                            @if($scoringEnabled)
                                                Simpan Nilai Keseluruhan
                                            @else
                                                Simpan Feedback Keseluruhan
                                            @endif
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            @else
                {{-- INDIVIDUAL GRADING MODE --}}
                <form action="{{ route('gradebook.store-multi-grade', $submission) }}" method="POST">
                    @csrf
                    
                    <div class="space-y-6">
                        @if($totalQuestions > 0)
                            @foreach($submission->content->essayQuestions()->orderBy('order')->get() as $index => $question)
                                @php
                                    $answer = $submission->answers()->where('question_id', $question->id)->first();
                                @endphp
                                
                                <div class="bg-white overflow-hidden rounded-2xl border border-gray-200 shadow-sm">
                                    <div class="p-6">
                                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                                            <h4 class="text-lg font-semibold text-gray-900 flex items-center">
                                                <span class="bg-bass-red-soft text-bass-red text-sm font-medium px-2.5 py-0.5 rounded-full mr-3">
                                                    {{ $index + 1 }}
                                                </span>
                                                Pertanyaan {{ $index + 1 }} dari {{ $totalQuestions }}
                                            </h4>
                                            @if($answer && (($scoringEnabled && $answer->score !== null) || (!$scoringEnabled && $answer->feedback)))
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-success-soft text-success">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                    @if($scoringEnabled)
                                                        Dinilai: {{ $answer->score }}/{{ $question->max_score }}
                                                    @else
                                                        Feedback Diberikan
                                                    @endif
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-warning-soft text-warning">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    Menunggu Penilaian
                                                </span>
                                            @endif
                                        </div>
                                        
                                        {{-- Question Text --}}
                                        <div class="mb-6 p-4 bg-bass-red-soft border-l-4 border-bass-red rounded-r-lg">
                                            <h5 class="font-medium text-gray-900 mb-2">Pertanyaan:</h5>
                                            <div class="text-gray-800">{!! nl2br(e($question->question)) !!}</div>
                                        </div>
                                        
                                        {{-- Student Answer --}}
                                        <div class="mb-6">
                                            <h5 class="font-medium text-gray-900 mb-3">Jawaban Peserta:</h5>
                                            <div class="p-4 bg-gray-50 border border-gray-300 rounded-lg min-h-[120px]">
                                                @if($answer && $answer->answer)
                                                    <div class="prose max-w-none">{!! nl2br(e($answer->answer)) !!}</div>
                                                @else
                                                    <p class="text-gray-500 italic">Tidak ada jawaban untuk pertanyaan ini.</p>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        @if($answer)
                                            {{-- Individual Grading Section --}}
                                            <div class="border-t pt-6">
                                                <h5 class="font-medium text-gray-900 mb-4 flex items-center">
                                                    <svg class="w-5 h-5 mr-2 text-bass-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                    Penilaian Pertanyaan {{ $index + 1 }}
                                                </h5>
                                                
                                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                                    @if($scoringEnabled)
                                                        <div>
                                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                                Nilai (0 - {{ $question->max_score }})
                                                            </label>
                                                            <div class="relative">
                                                                <input type="number" 
                                                                       name="scores[{{ $answer->id }}]" 
                                                                       min="0" 
                                                                       max="{{ $question->max_score }}" 
                                                                       value="{{ $answer->score }}"
                                                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-bass-red focus:border-bass-red text-lg"
                                                                       placeholder="Masukkan nilai">
                                                                <div class="absolute right-3 top-3 text-gray-500 text-sm">
                                                                    / {{ $question->max_score }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                    
                                                    <div class="{{ $scoringEnabled ? '' : 'lg:col-span-2' }}">
                                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                                            Feedback @if(!$scoringEnabled)(Wajib)@else(Opsional)@endif
                                                        </label>
                                                        <textarea name="feedback[{{ $answer->id }}]" 
                                                                  rows="4"
                                                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-bass-red focus:border-bass-red"
                                                                  placeholder="Berikan feedback khusus untuk jawaban ini..."
                                                                  @if(!$scoringEnabled) required @endif>{{ $answer->feedback }}</textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        @else
                            {{-- Fallback for legacy essays --}}
                            <div class="bg-white overflow-hidden rounded-2xl border border-gray-200 shadow-sm">
                                <div class="p-6">
                                    <h4 class="text-lg font-semibold text-gray-900 mb-4">Jawaban Esai</h4>
                                    
                                    @php $answer = $submission->answers()->first(); @endphp
                                    
                                    <div class="mb-6 p-4 bg-gray-50 border border-gray-300 rounded-lg min-h-[200px]">
                                        @if($answer && $answer->answer)
                                            {!! nl2br(e($answer->answer)) !!}
                                        @else
                                            <p class="text-gray-500 italic">Tidak ada jawaban.</p>
                                        @endif
                                    </div>
                                    
                                    @if($answer)
                                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                            @if($scoringEnabled)
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nilai</label>
                                                    <input type="number" name="scores[{{ $answer->id }}]" min="0" max="100" 
                                                           value="{{ $answer->score }}" 
                                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-bass-red focus:border-bass-red">
                                                </div>
                                            @endif
                                            <div class="{{ $scoringEnabled ? '' : 'lg:col-span-2' }}">
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Feedback</label>
                                                <textarea name="feedback[{{ $answer->id }}]" rows="4" 
                                                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-bass-red focus:border-bass-red">{{ $answer->feedback }}</textarea>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                        
                        {{-- Submit Button --}}
                        <div class="bg-white overflow-hidden rounded-2xl border border-gray-200 shadow-sm">
                            <div class="p-6">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div class="text-sm text-gray-600">
                                        @if($gradedAnswers >= $totalQuestions && $totalQuestions > 0)
                                            <span class="text-success font-medium flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                Semua pertanyaan telah dinilai
                                            </span>
                                        @else
                                            <span class="text-warning font-medium flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                {{ $totalQuestions - $gradedAnswers }} pertanyaan masih perlu dinilai
                                            </span>
                                        @endif
                                    </div>
                                    
                                    <button type="submit"
                                            class="w-full sm:w-auto px-6 py-3 bg-bass-red hover:bg-bass-red-hover text-white font-medium rounded-lg transition-colors flex items-center justify-center space-x-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3-3m0 0l-3 3m3-3v12"></path>
                                        </svg>
                                        <span>
                                            @if($scoringEnabled)
                                                Simpan Nilai Per Pertanyaan
                                            @else
                                                Simpan Feedback Per Pertanyaan
                                            @endif
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>

    {{-- JavaScript --}}
    <script>
        // For Overall Grading Mode
        function setFeedback(feedbackText) {
            const textarea = document.querySelector('textarea[name="overall_feedback"]');
            if (textarea) {
                textarea.value = feedbackText;
                textarea.focus();
            }
        }

        // For Individual Grading Mode  
        function addFeedback(answerId, feedbackText) {
            const textarea = document.querySelector(`textarea[name="feedback[${answerId}]"]`);
            if (textarea) {
                const currentValue = textarea.value.trim();
                if (currentValue) {
                    textarea.value = currentValue + ' ' + feedbackText;
                } else {
                    textarea.value = feedbackText;
                }
                textarea.focus();
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const gradingMode = '{{ $gradingMode }}';
            const scoringEnabled = {{ $scoringEnabled ? 'true' : 'false' }};
            
            // Add visual indicators for changed inputs
            const inputs = document.querySelectorAll('input[type="number"], textarea');
            inputs.forEach(input => {
                const originalValue = input.value;
                input.addEventListener('input', function() {
                    if (this.value !== originalValue) {
                        this.style.borderColor = '#f59e0b';
                        this.style.boxShadow = '0 0 0 1px #f59e0b';
                    } else {
                        this.style.borderColor = '';
                        this.style.boxShadow = '';
                    }
                });
            });

            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.key === 's') {
                    e.preventDefault();
                    const submitButton = document.querySelector('button[type="submit"]');
                    if (submitButton) {
                        submitButton.click();
                    }
                }
            });
        });
    </script>

    {{-- Custom CSS --}}
    <style>
        /* Smooth transitions */
        .transition-all {
            transition: all 0.2s ease-in-out;
        }
        
        /* Better focus states */
        input:focus, textarea:focus {
            box-shadow: 0 0 0 3px rgba(218, 30, 30, 0.1);
            border-color: #DA1E1E;
        }
        
        /* Sticky positioning */
        @media (min-width: 1024px) {
            .lg\:sticky {
                position: sticky;
                top: 1.5rem;
            }
        }
        
        /* Custom scrollbar */
        .overflow-y-auto::-webkit-scrollbar {
            width: 6px;
        }
        
        .overflow-y-auto::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 3px;
        }
        
        .overflow-y-auto::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        
        .overflow-y-auto::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        /* Progress bar animation */
        .grade-progress-bar {
            animation: progressFill 1s ease-out;
        }
        
        @keyframes progressFill {
            from { width: 0%; }
        }
        
        /* Print styles */
        @media print {
            .no-print { display: none !important; }
            .grid { display: block !important; }
            .lg\:grid-cols-2 { grid-template-columns: none !important; }
        }

        /* Highlight unsaved changes */
        .changed-input {
            border-color: #f59e0b !important;
            box-shadow: 0 0 0 1px #f59e0b !important;
        }
    </style>
</x-app-layout>
