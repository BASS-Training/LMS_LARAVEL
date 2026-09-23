<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Tinjauan Kuis: <span class="font-normal">{{ $attempt->quiz->title }}</span>
        </h2>
        <p class="text-sm text-gray-600 mt-1">
            Peserta: <span class="font-medium">{{ $attempt->user->name }}</span>
        </p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden rounded-2xl border border-gray-200 shadow-sm">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-6 pb-4 border-b">
                        <div>
                            <h3 class="text-2xl font-bold">Skor Akhir: {{ number_format($attempt->score) }}</h3>
                        </div>
                        <a href="javascript:void(0)" onclick="window.history.back()" class="text-sm text-bass-red hover:text-bass-red-hover">&larr; Kembali</a>
                    </div>

                    @foreach ($attempt->quiz->questions as $question)
                        <div class="mb-8">
                            <p class="font-semibold text-lg">{{ $loop->iteration }}. {{ $question->question_text }}</p>
                            
                            @php
                                // Cari jawaban user untuk pertanyaan ini
                                $userAnswer = $attempt->answers->where('question_id', $question->id)->first();
                            @endphp

                            <div class="space-y-3 mt-4">
                                @foreach ($question->options as $option)
                                    @php
                                        $isUserAnswer = $userAnswer && $userAnswer->option_id == $option->id;
                                        $isCorrect = $option->is_correct;
                                        
                                        $baseClass = 'p-4 rounded-lg border-2 flex items-center';
                                        $styleClass = '';

                                        if ($isUserAnswer && $isCorrect) {
                                            // Jawaban user, dan itu benar
                                            $styleClass = 'bg-success-soft border-success text-success';
                                        } elseif ($isUserAnswer && !$isCorrect) {
                                            // Jawaban user, tapi salah
                                            $styleClass = 'bg-error-soft border-error text-error';
                                        } elseif (!$isUserAnswer && $isCorrect) {
                                            // Bukan jawaban user, tapi ini kunci jawabannya
                                            $styleClass = 'bg-navy/5 border-navy text-navy';
                                        } else {
                                            // Opsi lain yang tidak dipilih
                                            $styleClass = 'bg-gray-50 border-gray-200';
                                        }
                                    @endphp
                                    <div class="{{ $baseClass }} {{ $styleClass }}">
                                        <span>{{ $option->option_text }}</span>
                                        @if ($isUserAnswer && $isCorrect) <span class="ml-auto font-bold text-sm">[Jawaban Anda Benar]</span> @endif
                                        @if ($isUserAnswer && !$isCorrect) <span class="ml-auto font-bold text-sm">[Jawaban Anda Salah]</span> @endif
                                        @if (!$isUserAnswer && $isCorrect) <span class="ml-auto font-bold text-sm">[Kunci Jawaban]</span> @endif
                                    </div>
                                @endforeach
                            </div>

                             @if(!$userAnswer)
                                <div class="mt-4 p-3 rounded-lg border-2 bg-warning-soft border-warning text-warning">
                                    <p class="font-semibold">Tidak Dijawab</p>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
