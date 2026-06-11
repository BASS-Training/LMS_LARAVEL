<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('contents.show', $content) }}"
                   class="inline-flex items-center text-indigo-600 hover:text-indigo-800 text-sm font-medium mb-1">
                    ← Kembali ke konten
                </a>
                <h1 class="text-2xl font-bold text-gray-900">📊 Hasil Feedback</h1>
                <p class="text-sm text-gray-600">{{ $content->title }}</p>
            </div>
            <div class="text-right">
                <div class="text-3xl font-bold text-indigo-600">{{ $submissionsCount }}</div>
                <div class="text-xs text-gray-500">tanggapan</div>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

            @if($content->is_anonymous)
                <p class="text-xs text-gray-500 flex items-center gap-1">🔒 Form ini anonim — hanya hasil agregat yang ditampilkan.</p>
            @endif

            @if($submissionsCount === 0)
                <div class="text-center text-gray-500 bg-white border border-dashed rounded-xl py-12">
                    Belum ada tanggapan dari peserta.
                </div>
            @endif

            @foreach($summary as $idx => $entry)
                @php $q = $entry['question']; @endphp
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <p class="font-semibold text-gray-900">{{ $idx + 1 }}. {{ $q->question }}</p>
                        <span class="shrink-0 text-xs px-2 py-1 bg-gray-100 text-gray-500 rounded-full">{{ $entry['count'] }} jawaban</span>
                    </div>

                    {{-- RATING --}}
                    @if($entry['type'] === 'rating')
                        <div class="flex items-center gap-3 mb-4">
                            <div class="text-3xl font-bold text-amber-500">{{ $entry['avg'] ?? '–' }}</div>
                            <div class="text-amber-400 text-xl leading-none">
                                @php $avgRound = (int) round($entry['avg'] ?? 0); @endphp
                                @for($i = 1; $i <= $entry['max']; $i++)
                                    <span class="{{ $i <= $avgRound ? 'text-amber-400' : 'text-gray-300' }}">★</span>
                                @endfor
                            </div>
                            <span class="text-sm text-gray-500">rata-rata dari {{ $entry['max'] }}</span>
                        </div>
                        <div class="space-y-1.5">
                            @foreach($entry['dist'] as $score => $cnt)
                                @php $pct = $entry['count'] ? round($cnt / $entry['count'] * 100) : 0; @endphp
                                <div class="flex items-center gap-2 text-sm">
                                    <span class="w-10 text-gray-500">{{ $score }} ★</span>
                                    <div class="flex-1 bg-gray-100 rounded-full h-3 overflow-hidden">
                                        <div class="h-3 bg-amber-400 rounded-full" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <span class="w-16 text-right text-gray-500">{{ $cnt }} ({{ $pct }}%)</span>
                                </div>
                            @endforeach
                        </div>

                    {{-- PILIHAN (tunggal/ganda) --}}
                    @elseif($entry['type'] === 'single_choice' || $entry['type'] === 'multi_choice')
                        <div class="space-y-1.5">
                            @foreach($entry['tally'] as $opt)
                                @php $pct = $entry['count'] ? round($opt['count'] / $entry['count'] * 100) : 0; @endphp
                                <div class="flex items-center gap-2 text-sm">
                                    <span class="w-40 truncate text-gray-700" title="{{ $opt['label'] }}">{{ $opt['label'] }}</span>
                                    <div class="flex-1 bg-gray-100 rounded-full h-3 overflow-hidden">
                                        <div class="h-3 bg-sky-500 rounded-full" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <span class="w-16 text-right text-gray-500">{{ $opt['count'] }} ({{ $pct }}%)</span>
                                </div>
                            @endforeach
                        </div>
                        @if($entry['type'] === 'multi_choice')
                            <p class="text-xs text-gray-400 mt-2">Boleh memilih lebih dari satu, jadi total bisa melebihi 100%.</p>
                        @endif

                    {{-- TEKS --}}
                    @else
                        @if($entry['texts']->isEmpty())
                            <p class="text-sm text-gray-400 italic">Belum ada jawaban teks.</p>
                        @else
                            <div class="space-y-2 max-h-72 overflow-y-auto">
                                @foreach($entry['texts'] as $txt)
                                    <div class="bg-gray-50 border border-gray-100 rounded-lg px-3 py-2 text-sm text-gray-700">{{ $txt }}</div>
                                @endforeach
                            </div>
                        @endif
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
