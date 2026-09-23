<x-app-layout>
    <x-slot name="header">
        <!-- Compact Quiz Header -->
        <div class="relative bg-navy text-white -mx-6 -mt-6 mb-6 overflow-hidden">
            <div class="relative px-6 py-6">
                <div class="max-w-7xl mx-auto">
                    <div class="flex items-center justify-between">
                        <!-- Quiz Info -->
                        <div class="flex items-center space-x-4">
                            <div class="bg-white/20 backdrop-blur-sm p-3 rounded-xl border border-white/30 shadow-lg">
                                <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 3h6v4H9V3z" /></svg>
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold text-white">{{ $quiz->title }}</h1>
                                <p class="text-white/80 text-sm">
                                    @if ($quiz->lesson && $quiz->lesson->course)
                                        {{ $quiz->lesson->course->title }}
                                    @else
                                        Kursus Tidak Ditemukan
                                    @endif
                                </p>
                            </div>
                        </div>

                        <!-- Quiz Stats -->
                        <div class="flex items-center space-x-4">
                            <div class="bg-white/10 backdrop-blur-sm rounded-lg px-4 py-2 border border-white/20">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-white">{{ $quiz->questions->count() }}</div>
                                    <div class="text-xs text-white/70">Soal</div>
                                </div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-lg px-4 py-2 border border-white/20">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-white">{{ $quiz->passing_percentage }}%</div>
                                    <div class="text-xs text-white/70">Nilai Lulus</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-slot>

    <!-- Floating Timer (Pojok Kanan Atas) -->
    @if ($quiz->time_limit)
        <div id="floating-timer" class="fixed top-20 right-6 z-50 bg-navy rounded-2xl shadow-2xl p-4 border-2 border-white/20 transform hover:scale-105 transition-all duration-300">
            <div class="text-center">
                <div class="flex items-center justify-center space-x-2 mb-2">
                    <svg class="h-4 w-4 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m4-9l-1.5 1.5M9 2h6m-3 3a8 8 0 110 16 8 8 0 010-16z" /></svg>
                    <span class="text-xs font-semibold text-white">Sisa Waktu</span>
                </div>

                <!-- Circular Timer -->
                <div class="relative w-20 h-20 mx-auto mb-2">
                    <svg class="w-20 h-20 transform -rotate-90" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="42" stroke="rgba(255,255,255,0.2)" stroke-width="6" fill="none"/>
                        <circle id="timer-circle" cx="50" cy="50" r="42" stroke="#D97706" stroke-width="6" fill="none"
                                stroke-linecap="round" stroke-dasharray="264" stroke-dashoffset="0"
                                class="transition-all duration-1000"/>
                    </svg>

                    <div class="absolute inset-0 flex items-center justify-center">
                        <div id="time-left" class="text-xl font-bold font-mono text-white tracking-tight"></div>
                    </div>
                </div>

                <!-- Timer Status -->
                <div id="timer-status" class="text-xs font-medium text-white/80">
                    In Progress
                </div>
            </div>
        </div>
    @endif

    <div class="py-8 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Split Layout: Sidebar Kiri + Content Kanan -->
            <div class="flex gap-6">

                <!-- SIDEBAR KIRI - Daftar Nomor Soal (Sticky) -->
                <div class="w-80 flex-shrink-0">
                    <div class="sticky top-24">
                        <!-- Progress Card -->
                        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6 border border-gray-200">
                            <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                                 <svg class="h-5 w-5 text-navy mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 11l3 3L22 4M2 12l5 5M2 5l5 5" /></svg>
                                Progress Quiz
                            </h3>

                            <div class="mb-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span id="progress-text" class="text-sm font-semibold text-gray-700">0 / {{ $quiz->questions->count() }}</span>
                                    <span id="progress-percentage" class="text-sm font-bold text-bass-red">0%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-3">
                                    <div id="progress-bar" class="bg-bass-red h-3 rounded-full transition-all duration-500" style="width: 0%"></div>
                                </div>
                            </div>

                            <!-- Auto Save Indicator -->
                            <div id="save-indicator" class="flex items-center space-x-2 text-success text-sm opacity-0 transition-opacity duration-300 mb-4">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                <span class="font-medium">Auto-saved</span>
                            </div>

                            <!-- Current Question Info -->
                            <div class="mt-4 p-3 bg-bass-red-soft rounded-lg border border-bass-red">
                                <div class="text-sm text-bass-red">
                                    <span class="font-semibold">Soal Aktif:</span>
                                    <span id="current-question-display" class="ml-2 text-lg font-bold">1</span>
                                    <span class="text-gray-600">/ {{ $quiz->questions->count() }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Question Navigation -->
                        <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-200">
                            <h4 class="text-base font-bold text-gray-900 mb-4 flex items-center">
                                 <svg class="h-5 w-5 text-navy mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 6h11M9 12h11M9 18h11M4 6h.01M4 12h.01M4 18h.01" /></svg>
                                Navigasi Soal
                            </h4>

                            <!-- Grid 5 Kolom untuk Nomor Soal -->
                            <div class="grid grid-cols-5 gap-2">
                                @for($i = 1; $i <= $quiz->questions->count(); $i++)
                                    <button onclick="goToQuestion({{ $i - 1 }})"
                                            class="question-nav-btn w-full aspect-square rounded-lg border-2 border-gray-300 bg-white text-sm font-bold transition-all duration-200 hover:border-bass-red hover:bg-bass-red-soft hover:scale-105"
                                            data-question="{{ $i - 1 }}"
                                            title="Soal {{ $i }}">
                                        {{ $i }}
                                    </button>
                                @endfor
                            </div>

                            <!-- Legend -->
                            <div class="mt-6 pt-4 border-t border-gray-200 space-y-2 text-sm">
                                <div class="flex items-center space-x-2">
                                    <div class="w-6 h-6 rounded border-2 border-gray-300 bg-white"></div>
                                    <span class="text-gray-600">Belum dijawab</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="w-6 h-6 rounded border-2 border-success bg-success-soft"></div>
                                    <span class="text-gray-600">Sudah dijawab</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="w-6 h-6 rounded border-2 border-bass-red bg-bass-red-soft"></div>
                                    <span class="text-gray-600">Sedang dilihat</span>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button di Sidebar -->
                        <div class="mt-6">
                            <button type="button"
                                    id="sidebar-submit-btn"
                                    onclick="showSubmitConfirmation(event)"
                                    disabled
                                    class="w-full btn-submit bg-gray-400 text-white px-6 py-4 rounded-xl text-lg font-bold shadow-lg transform transition-all duration-300 cursor-not-allowed opacity-70">
                                <svg class="inline h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                                Kirim Jawaban
                            </button>
                        </div>
                    </div>
                </div>

                <!-- CONTENT KANAN - Soal Quiz (1 per 1) -->
                <div class="flex-1">
                    <form id="quiz-attempt-form" method="POST" action="{{ route('quizzes.submit_attempt', [$quiz, $attempt]) }}">
                        @csrf

                        <!-- Question Container - Hanya tampilkan 1 soal -->
                        <div id="question-container">
                            @foreach ($quiz->questions as $index => $question)
                                <div class="question-slide {{ $index === 0 ? 'active' : 'hidden' }}"
                                     data-question-index="{{ $index }}"
                                     id="question-slide-{{ $index }}">

                                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-200">
                                        <!-- Question Header -->
                                        <div class="bg-gray-50 px-8 py-5 border-b border-gray-200">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-4">
                                                    <div class="bg-bass-red text-white w-12 h-12 rounded-xl flex items-center justify-center font-bold text-lg shadow-lg">
                                                        {{ $index + 1 }}
                                                    </div>
                                                    <div>
                                                        <span class="text-lg font-semibold text-gray-800">
                                                            Soal {{ $index + 1 }} dari {{ $quiz->questions->count() }}
                                                        </span>
                                                        <div class="flex items-center space-x-3 mt-1">
                                                            <span class="text-sm text-gray-600">
                                                                @if($question->type === 'multiple_choice')
                                                                    Pilihan Ganda
                                                                @else
                                                                    Benar/Salah
                                                                @endif
                                                            </span>
                                                            <span class="text-sm text-bass-red font-medium">
                                                                 {{ $question->marks }} poin
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Question Status -->
                                                <div class="question-status flex items-center space-x-2">
                                                    <div class="status-indicator w-4 h-4 rounded-full border-2 border-gray-300 transition-all duration-300"></div>
                                                    <span class="status-text text-sm text-gray-500">Belum dijawab</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Question Content -->
                                        <div class="p-8 bg-white min-h-[400px]">
                                            <div class="mb-8">
                                                <div class="prose prose-lg max-w-none">
                                                    <p class="text-xl font-medium text-gray-900 leading-relaxed mb-6">
                                                        {{ $question->question_text }}
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Multiple Choice Options -->
                                            @if ($question->type === 'multiple_choice')
                                                <div class="space-y-4">
                                                    @foreach ($question->options as $optionIndex => $option)
                                                        <label class="option-item group block relative cursor-pointer">
                                                            <input type="radio"
                                                                   name="answers[{{ $index }}][option_id]"
                                                                   value="{{ $option->id }}"
                                                                   class="sr-only peer"
                                                                   onchange="updateProgress(); autoSave(); updateQuestionStatus({{ $index }});">

                                                            <div class="bg-white border-2 border-gray-200 rounded-xl p-6 transition-all duration-300 hover:border-bass-red hover:shadow-lg hover:bg-bass-red-soft peer-checked:border-bass-red peer-checked:bg-bass-red-soft peer-checked:shadow-lg">
                                                                <div class="flex items-center space-x-4">
                                                                    <!-- Custom Radio -->
                                                                    <div class="relative">
                                                                         <div class="w-6 h-6 rounded-full border-2 border-gray-300 bg-white transition-all duration-200 peer-checked:border-bass-red group-hover:border-bass-red"></div>
                                                                        <div class="absolute inset-0 flex items-center justify-center">
                                                                             <div class="w-3 h-3 rounded-full bg-bass-red scale-0 transition-transform duration-200 peer-checked:scale-100"></div>
                                                                        </div>
                                                                    </div>

                                                                    <!-- Option Letter -->
                                                                     <div class="bg-gray-100 text-gray-700 w-10 h-10 rounded-lg flex items-center justify-center font-bold text-lg transition-all duration-200 peer-checked:bg-bass-red peer-checked:text-white">
                                                                        {{ chr(65 + $optionIndex) }}
                                                                    </div>

                                                                    <!-- Option Text -->
                                                                    <div class="flex-1">
                                                                         <span class="text-lg font-medium text-gray-800 transition-colors duration-200 peer-checked:text-navy">
                                                                            {{ $option->option_text }}
                                                                        </span>
                                                                    </div>

                                                                    <!-- Selection Indicator -->
                                                                    <div class="opacity-0 transition-opacity duration-200 peer-checked:opacity-100">
                                                                         <svg class="h-6 w-6 text-bass-red" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </label>
                                                    @endforeach
                                                </div>
                                                <input type="hidden" name="answers[{{ $index }}][question_id]" value="{{ $question->id }}">

                                            <!-- True/False Options -->
                                            @elseif ($question->type === 'true_false')
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                    <!-- True Option -->
                                                    <label class="option-item group block relative cursor-pointer">
                                                        <input type="radio"
                                                               name="answers[{{ $index }}][answer_text]"
                                                               value="True"
                                                               class="sr-only peer"
                                                               onchange="updateProgress(); autoSave(); updateQuestionStatus({{ $index }});">

                                                        <div class="bg-white border-2 border-success-soft rounded-xl p-8 transition-all duration-300 hover:border-success hover:shadow-lg hover:bg-success-soft peer-checked:border-success peer-checked:bg-success-soft peer-checked:shadow-lg relative h-full">
                                                            <div class="absolute top-4 right-4 opacity-0 peer-checked:opacity-100 transition-all duration-300 transform scale-0 peer-checked:scale-100">
                                                                <div class="bg-success text-white rounded-full w-8 h-8 flex items-center justify-center shadow-lg">
                                                                     <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                                                </div>
                                                            </div>

                                                            <div class="text-center">
                                                                <div class="bg-success-soft w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 transition-all duration-200">
                                                                     <svg class="h-8 w-8 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                                                </div>
                                                                <h4 class="text-xl font-bold text-success mb-2">BENAR</h4>
                                                                <p class="text-success font-medium">True</p>
                                                            </div>
                                                        </div>
                                                    </label>

                                                    <!-- False Option -->
                                                    <label class="option-item group block relative cursor-pointer">
                                                        <input type="radio"
                                                               name="answers[{{ $index }}][answer_text]"
                                                               value="False"
                                                               class="sr-only peer"
                                                               onchange="updateProgress(); autoSave(); updateQuestionStatus({{ $index }});">

                                                        <div class="bg-white border-2 border-error-soft rounded-xl p-8 transition-all duration-300 hover:border-error hover:shadow-lg hover:bg-error-soft peer-checked:border-error peer-checked:bg-error-soft peer-checked:shadow-lg relative h-full">
                                                            <div class="absolute top-4 right-4 opacity-0 peer-checked:opacity-100 transition-all duration-300 transform scale-0 peer-checked:scale-100">
                                                                <div class="bg-error text-white rounded-full w-8 h-8 flex items-center justify-center shadow-lg">
                                                                     <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                                                </div>
                                                            </div>

                                                            <div class="text-center">
                                                                <div class="bg-error-soft w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 transition-all duration-200">
                                                                     <svg class="h-8 w-8 text-error" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                                                </div>
                                                                <h4 class="text-xl font-bold text-error mb-2">SALAH</h4>
                                                                <p class="text-error font-medium">False</p>
                                                            </div>
                                                        </div>
                                                    </label>
                                                </div>
                                                <input type="hidden" name="answers[{{ $index }}][question_id]" value="{{ $question->id }}">
                                            @endif
                                        </div>

                                        <!-- Navigation Buttons -->
                                        <div class="bg-gray-50 px-8 py-5 border-t border-gray-200">
                                            <div class="flex items-center justify-between">
                                                <button type="button"
                                                        onclick="previousQuestion()"
                                                        class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg transition-all duration-200 {{ $index === 0 ? 'invisible' : '' }}"
                                                        id="prev-btn-{{ $index }}">
                                                    <svg class="inline h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                                                    Sebelumnya
                                                </button>

                                                <div class="text-sm text-gray-600 font-medium">
                                                    Soal <span class="text-bass-red font-bold">{{ $index + 1 }}</span> dari {{ $quiz->questions->count() }}
                                                </div>

                                                <button type="button"
                                                        onclick="nextQuestion()"
                                                        class="px-6 py-3 bg-bass-red hover:bg-bass-red-hover text-white font-semibold rounded-lg transition-all duration-200 {{ $index === $quiz->questions->count() - 1 ? 'hidden' : '' }}"
                                                        id="next-btn-{{ $index }}">
                                                    Selanjutnya
                                                    <svg class="inline h-4 w-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                                </button>

                                                <button type="button"
                                                        onclick="showSubmitConfirmation(event)"
                                                        disabled
                                                        class="inline-submit-btn px-6 py-3 bg-gray-400 text-white font-semibold rounded-lg transition-all duration-200 cursor-not-allowed opacity-70 {{ $index !== $quiz->questions->count() - 1 ? 'hidden' : '' }}"
                                                        id="submit-btn-{{ $index }}">
                                                    <svg class="inline h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                                                    Kirim Jawaban
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            @endforeach
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="submit-modal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 transform transition-all duration-300 shadow-2xl">
            <div class="text-center">
                <div class="bg-warning-soft w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                     <svg class="h-10 w-10 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.74-3L13.74 4a2 2 0 00-3.48 0L3.33 16a2 2 0 001.74 3z" /></svg>
                </div>

                <h3 class="text-2xl font-bold text-gray-900 mb-3">Konfirmasi Pengiriman</h3>
                <p class="text-gray-600 mb-6 leading-relaxed">
                    Apakah Anda yakin ingin mengirim jawaban?
                    <br><strong>Setelah dikirim, Anda tidak dapat mengubah jawaban lagi.</strong>
                </p>

                <div class="bg-gray-50 rounded-xl p-4 mb-6">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">Soal Dijawab:</span>
                            <br><span id="modal-answered-count" class="font-bold text-bass-red">0 / {{ $quiz->questions->count() }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Sisa Waktu:</span>
                            <br><span id="modal-time-left" class="font-bold text-warning">--:--</span>
                        </div>
                    </div>
                </div>

                <div class="flex space-x-4">
                    <button type="button"
                            onclick="hideSubmitConfirmation()"
                            class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 px-6 rounded-xl font-semibold transition-colors duration-200">
                         Batalkan
                    </button>
                    <button type="button"
                            onclick="submitQuiz(event)"
                            class="flex-1 bg-bass-red hover:bg-bass-red-hover text-white py-3 px-6 rounded-xl font-semibold transition-colors duration-200">
                         Ya, Kirim
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom Styles -->
    <style>
        .question-slide {
            transition: all 0.3s ease-in-out;
        }

        .question-slide.hidden {
            display: none;
        }

        .question-slide.active {
            display: block;
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .option-item {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .option-item:hover .peer:not(:checked) ~ div {
            border-color: #DA1E1E !important;
            background-color: #FCEAEA !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(218, 30, 30, 0.15) !important;
        }

        .option-item .peer:checked ~ div {
            transform: translateY(-1px);
            border-width: 3px !important;
        }

        .option-item .peer:checked ~ div .bg-gray-100 {
            background: #DA1E1E !important;
            color: white !important;
            transform: scale(1.1);
        }

        /* Highlight active question in sidebar */
        .question-nav-btn.active {
            border-color: #DA1E1E !important;
            background: #FCEAEA !important;
            color: #DA1E1E;
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(218, 30, 30, 0.2);
        }

        .question-nav-btn.answered {
            border-color: #168A50 !important;
            background: #EAF7F0 !important;
            color: #168A50;
        }

        /* Floating timer responsive */
        @media (max-width: 1024px) {
            #floating-timer {
                top: 10px;
                right: 10px;
                padding: 12px;
            }
            #floating-timer .w-20 {
                width: 60px;
                height: 60px;
            }
        }
    </style>

    <!-- JavaScript -->
    <script>
        let currentQuestionIndex = 0;
        const totalQuestions = {{ $quiz->questions->count() }};

        @if ($quiz->time_limit && isset($timeRemaining) && $timeRemaining > 0)
            let timeLeft = Math.floor({{ $timeRemaining }});
            const totalTime = {{ $quiz->time_limit }} * 60;
            const circumference = 2 * Math.PI * 42;

            function updateTimerDisplay() {
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    showTimeUpAlert();
                    return;
                }

                const totalSeconds = Math.floor(timeLeft);
                const minutes = Math.floor(totalSeconds / 60);
                const seconds = totalSeconds % 60;

                const formattedTime = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                document.getElementById('time-left').textContent = formattedTime;

                const modalTimeEl = document.getElementById('modal-time-left');
                if (modalTimeEl) {
                    modalTimeEl.textContent = formattedTime;
                }

                const progress = (totalTime - totalSeconds) / totalTime;
                const offset = circumference * progress;
                const circle = document.getElementById('timer-circle');
                if (circle) {
                    circle.style.strokeDashoffset = offset;
                }

                updateTimerStatus(totalSeconds);

                timeLeft = Math.max(0, timeLeft - 1);
            }

            function updateTimerStatus(seconds) {
                const statusEl = document.getElementById('timer-status');
                const timerEl = document.getElementById('time-left');

                if (seconds <= 300) {
                    statusEl.textContent = 'Hampir Habis!';
                    timerEl.style.color = '#D97706';
                } else if (seconds <= 600) {
                    statusEl.textContent = 'Perhatikan Waktu';
                } else {
                    statusEl.textContent = 'In Progress';
                }
            }

            function showTimeUpAlert() {
                // Auto-submit directly without alert for better browser compatibility
                autoSubmitQuiz();
            }

            const timerInterval = setInterval(updateTimerDisplay, 1000);
            updateTimerDisplay();
        @endif

        const quizId = {{ $quiz->id }};
        const attemptId = {{ $attempt->id }};
        const saveProgressUrl = "{{ route('quizzes.save_progress', [$quiz, $attempt]) }}";
        const csrfToken = "{{ csrf_token() }}";

        function goToQuestion(index) {
            // Hide all questions
            document.querySelectorAll('.question-slide').forEach(slide => {
                slide.classList.remove('active');
                slide.classList.add('hidden');
            });

            // Show target question
            const targetSlide = document.getElementById(`question-slide-${index}`);
            if (targetSlide) {
                targetSlide.classList.remove('hidden');
                targetSlide.classList.add('active');
                currentQuestionIndex = index;

                // Update current question display
                document.getElementById('current-question-display').textContent = index + 1;

                // Update navigation button active state
                updateNavigationButtons();

                // Scroll to top
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }

        function nextQuestion() {
            if (currentQuestionIndex < totalQuestions - 1) {
                goToQuestion(currentQuestionIndex + 1);
            }
        }

        function previousQuestion() {
            if (currentQuestionIndex > 0) {
                goToQuestion(currentQuestionIndex - 1);
            }
        }

        function updateNavigationButtons() {
            // Update sidebar navigation buttons
            document.querySelectorAll('.question-nav-btn').forEach((btn, index) => {
                if (index === currentQuestionIndex) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
        }

        function updateProgress() {
            let answeredCount = 0;

            for (let i = 0; i < totalQuestions; i++) {
                const questionInputs = document.querySelectorAll(`input[name*="[${i}]"]`);
                let isAnswered = false;

                questionInputs.forEach(input => {
                    if (input.type === 'radio' && input.checked) {
                        isAnswered = true;
                    }
                });

                if (isAnswered) {
                    answeredCount++;
                    updateQuestionNavButton(i, true);
                } else {
                    updateQuestionNavButton(i, false);
                }
            }

            const progressPercent = (answeredCount / totalQuestions) * 100;

            const progressBar = document.getElementById('progress-bar');
            if (progressBar) {
                progressBar.style.width = progressPercent + '%';
            }

            const progressText = document.getElementById('progress-text');
            if (progressText) {
                progressText.textContent = `${answeredCount} / ${totalQuestions}`;
            }

            const progressPercentage = document.getElementById('progress-percentage');
            if (progressPercentage) {
                progressPercentage.textContent = Math.round(progressPercent) + '%';
            }

            const modalAnsweredCount = document.getElementById('modal-answered-count');
            if (modalAnsweredCount) {
                modalAnsweredCount.textContent = `${answeredCount} / ${totalQuestions}`;
            }

            // Enable/disable submit buttons based on whether all questions are answered
            const allAnswered = answeredCount === totalQuestions;

            const sidebarBtn = document.getElementById('sidebar-submit-btn');
            if (sidebarBtn) {
                sidebarBtn.disabled = !allAnswered;
                if (allAnswered) {
                    sidebarBtn.className = 'w-full btn-submit bg-bass-red hover:bg-bass-red-hover text-white px-6 py-4 rounded-xl text-lg font-bold shadow-lg transition-colors duration-200 cursor-pointer';
                } else {
                    sidebarBtn.className = 'w-full btn-submit bg-gray-400 text-white px-6 py-4 rounded-xl text-lg font-bold shadow-lg transform transition-all duration-300 cursor-not-allowed opacity-70';
                }
            }

            document.querySelectorAll('.inline-submit-btn').forEach(btn => {
                btn.disabled = !allAnswered;
                if (allAnswered) {
                    btn.classList.add('bg-bass-red', 'hover:bg-bass-red-hover', 'cursor-pointer');
                    btn.classList.remove('bg-gray-400', 'cursor-not-allowed', 'opacity-70');
                } else {
                    btn.classList.add('bg-gray-400', 'cursor-not-allowed', 'opacity-70');
                    btn.classList.remove('bg-bass-red', 'hover:bg-bass-red-hover', 'cursor-pointer');
                }
            });
        }

        function updateQuestionNavButton(index, isAnswered) {
            const navBtn = document.querySelector(`[data-question="${index}"]`);
            if (navBtn) {
                if (isAnswered) {
                    navBtn.classList.add('answered');
                } else {
                    navBtn.classList.remove('answered');
                }
            }
        }

        function updateQuestionStatus(questionIndex) {
            const questionSlide = document.querySelector(`[data-question-index="${questionIndex}"]`);
            if (!questionSlide) return;

            const statusIndicator = questionSlide.querySelector('.status-indicator');
            const statusText = questionSlide.querySelector('.status-text');
            const questionInputs = questionSlide.querySelectorAll('input[type="radio"]');
            let isAnswered = false;

            questionInputs.forEach(input => {
                if (input.checked) {
                    isAnswered = true;
                }
            });

            if (isAnswered) {
                statusIndicator.className = 'status-indicator w-4 h-4 rounded-full bg-success transition-all duration-300';
                statusText.textContent = 'Sudah dijawab';
                statusText.className = 'status-text text-sm text-success font-medium';
            } else {
                statusIndicator.className = 'status-indicator w-4 h-4 rounded-full border-2 border-gray-300 transition-all duration-300';
                statusText.textContent = 'Belum dijawab';
                statusText.className = 'status-text text-sm text-gray-500';
            }
        }

        function autoSave() {
            if (!quizId || !attemptId) {
                return;
            }

            const answers = getCurrentAnswers();
            localStorage.setItem(`quiz_backup_${quizId}`, JSON.stringify(answers));

            // Save to server so answers are available when backend auto-submit runs.
            const tokenElement = document.querySelector('meta[name="csrf-token"]');
            const token = tokenElement ? tokenElement.getAttribute('content') : csrfToken;
            if (token) {
                fetch(saveProgressUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ answers: answers })
                })
                .then(response => {
                    const contentType = response.headers.get('content-type') || '';
                    if (!contentType.includes('application/json')) {
                        return null;
                    }
                    return response.json();
                })
                .then(data => {
                    if (data && data.expired) {
                        autoSubmitQuiz();
                    }
                })
                .catch(() => {
                    // Silent fail to keep UX smooth; local backup still exists.
                });
            }

            const saveIndicator = document.getElementById('save-indicator');
            if (saveIndicator) {
                saveIndicator.style.opacity = '1';
                setTimeout(() => {
                    saveIndicator.style.opacity = '0';
                }, 2000);
            }
        }

        function getCurrentAnswers() {
            const formData = new FormData(document.getElementById('quiz-attempt-form'));
            const answers = [];

            for (let i = 0; i < totalQuestions; i++) {
                const questionId = formData.get(`answers[${i}][question_id]`);
                const optionId = formData.get(`answers[${i}][option_id]`);
                const answerText = formData.get(`answers[${i}][answer_text]`);

                if (questionId && (optionId || answerText)) {
                    answers.push({
                        question_id: questionId,
                        option_id: optionId,
                        answer_text: answerText
                    });
                }
            }

            return answers;
        }

        function restoreAnswers() {
            const saved = localStorage.getItem(`quiz_backup_${quizId}`);
            if (!saved) return;

            try {
                const answers = JSON.parse(saved);

                answers.forEach(answer => {
                    let input = null;

                    if (answer.option_id) {
                        input = document.querySelector(`input[value="${answer.option_id}"]`);
                    } else if (answer.answer_text) {
                        input = document.querySelector(`input[value="${answer.answer_text}"]`);
                    }

                    if (input) {
                        input.checked = true;

                        const questionSlide = input.closest('.question-slide');
                        if (questionSlide) {
                            const questionIndex = parseInt(questionSlide.dataset.questionIndex);
                            updateQuestionStatus(questionIndex);
                        }
                    }
                });

                updateProgress();
            } catch (error) {
                console.error('Error restoring answers:', error);
            }
        }

        function autoSubmitQuiz() {
            autoSave();
            document.getElementById('quiz-attempt-form').submit();
        }

        function showSubmitConfirmation(event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            updateProgress();

            const modal = document.getElementById('submit-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            const modalContent = modal.querySelector('div > div');
            modalContent.style.transform = 'scale(0.8)';
            modalContent.style.opacity = '0';

            setTimeout(() => {
                modalContent.style.transform = 'scale(1)';
                modalContent.style.opacity = '1';
            }, 50);
        }

        function hideSubmitConfirmation() {
            const modal = document.getElementById('submit-modal');
            const modalContent = modal.querySelector('div > div');

            modalContent.style.transform = 'scale(0.8)';
            modalContent.style.opacity = '0';

            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }, 200);
        }

        function submitQuiz(event) {
            localStorage.removeItem(`quiz_backup_${quizId}`);

            const submitBtn = event?.currentTarget || event?.target;
            if (submitBtn && submitBtn.tagName === 'BUTTON') {
                submitBtn.textContent = 'Mengirim Jawaban...';
                submitBtn.disabled = true;
            }

            hideSubmitConfirmation();
            document.getElementById('quiz-attempt-form').submit();
        }

        document.addEventListener('DOMContentLoaded', function() {
            restoreAnswers();
            updateProgress();
            updateNavigationButtons();

            for (let i = 0; i < totalQuestions; i++) {
                updateQuestionStatus(i);
            }

            setInterval(autoSave, 10000);
        });

        // Beforeunload warning removed for better browser compatibility
        // Answers are auto-saved every 10 seconds

        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowRight') {
                nextQuestion();
            } else if (e.key === 'ArrowLeft') {
                previousQuestion();
            }
        });
    </script>
</x-app-layout>
