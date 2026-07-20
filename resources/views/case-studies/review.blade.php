<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">
            Tinjau Jawaban — {{ $submission->user->name ?? 'Peserta' }}
        </h2>
    </x-slot>

    @php
        $answerFor = function ($sid, $bid, $rc = null) use ($answers) {
            $blockAns = $answers[$sid][$bid] ?? null;
            if ($rc === null) return is_string($blockAns) ? $blockAns : '';
            return is_array($blockAns) ? ($blockAns[$rc] ?? '') : '';
        };
    @endphp

    <div class="py-8 max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="bg-white shadow rounded-2xl p-6 lg:p-8">
            <h3 class="text-lg font-bold text-gray-900 mb-4">{{ $content->title }}</h3>

            @foreach(($template['sections'] ?? []) as $section)
                @php $sid = $section['id']; $level = $section['level'] ?? 1; @endphp
                <section class="{{ $level === 2 ? 'ml-4 pl-4 border-l-2 border-amber-200' : '' }} mb-6">
                    <h4 class="{{ $level === 1 ? 'text-base font-bold text-gray-900' : 'text-sm font-semibold text-gray-800' }}">
                        {{ $section['title'] ?: ($level === 1 ? 'Bab' : 'Subbab') }}
                    </h4>
                    @if(!empty($section['instruction']))
                        <p class="text-sm text-gray-500 mt-1" style="white-space: pre-line;">{{ $section['instruction'] }}</p>
                    @endif

                    <div class="space-y-3 mt-2">
                        @foreach(($section['blocks'] ?? []) as $block)
                            @php $bid = $block['id']; @endphp
                            @if(($block['kind'] ?? '') === 'text')
                                @if(!empty($block['label']))
                                    <p class="text-sm font-medium text-gray-700">{{ $block['label'] }}</p>
                                @endif
                                <div class="prose max-w-none border border-gray-200 rounded-lg p-3 bg-gray-50">
                                    {!! $answerFor($sid, $bid) ?: '<span class="text-gray-400 italic">(kosong)</span>' !!}
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
                                                            $isInput = ($cell['role'] ?? 'input') === 'input';
                                                            $val = $isInput ? $answerFor($sid, $bid, $rc) : ($cell['text'] ?? '');
                                                        @endphp
                                                        <td colspan="{{ $cell['colSpan'] ?? 1 }}" rowspan="{{ $cell['rowSpan'] ?? 1 }}"
                                                            class="border border-gray-400 p-2 text-sm align-top {{ !empty($cell['bold']) ? 'font-bold' : '' }}"
                                                            style="background: {{ $cell['bg'] ?? '#ffffff' }}; text-align: {{ $cell['align'] ?? 'left' }};">
                                                            {{ $val !== '' ? $val : ($isInput ? '—' : '') }}
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

        {{-- Form penilaian --}}
        <div class="bg-white shadow rounded-2xl p-6 lg:p-8">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Penilaian</h3>
            <form method="POST" action="{{ route('case-studies.grade', [$content, $submission]) }}" class="space-y-4">
                @csrf
                @if($content->scoring_enabled)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nilai (0–100)</label>
                        <input type="number" name="score" min="0" max="100" value="{{ old('score', $submission->score) }}"
                               class="w-40 px-3 py-2 border border-gray-300 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                    </div>
                @endif
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Feedback</label>
                    <textarea name="feedback" rows="4"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                              placeholder="Catatan untuk peserta...">{{ old('feedback', $submission->feedback) }}</textarea>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('case-studies.submissions', $content) }}" class="px-4 py-2 text-gray-600 hover:text-gray-800">Kembali</a>
                    <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg">
                        Simpan Penilaian
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
