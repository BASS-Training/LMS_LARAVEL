<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $content->title }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 12px; color: #1f2937; line-height: 1.5; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 15px; margin: 18px 0 6px; border-bottom: 1px solid #d1d5db; padding-bottom: 3px; }
        h3 { font-size: 13px; margin: 12px 0 5px; }
        .meta { color: #6b7280; font-size: 11px; margin-bottom: 16px; }
        .instruction { color: #6b7280; font-size: 11px; margin: 4px 0 8px; font-style: italic; }
        .answer { border: 1px solid #e5e7eb; background: #f9fafb; padding: 6px 8px; border-radius: 4px; }
        .subsection { margin-left: 12px; padding-left: 10px; border-left: 2px solid #fcd34d; }
        /* Tabel selalu muat di lebar halaman A4 portrait; kolom dibagi rata
           dan teks dibungkus/dipecah agar rapi (tidak terpotong keluar halaman). */
        table { border-collapse: collapse; width: 100%; margin: 6px 0; table-layout: fixed; }
        /* Tabel pendek dipindah utuh ke halaman berikutnya bila tak muat
           (mencegah header ber-merge terbelah). Tabel sangat panjang mulai di
           halaman baru lalu header diulang via thead. */
        .cs-table-wrap { page-break-inside: avoid; }
        thead { display: table-header-group; page-break-inside: avoid; }
        tr { page-break-inside: avoid; }
        td {
            border: 1px solid #9ca3af;
            padding: 3px 4px;
            font-size: 10px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
            word-break: break-word;
        }
        .empty { color: #9ca3af; }
    </style>
</head>
<body>
    <h1>{{ $content->title }}</h1>
    <div class="meta">
        Peserta: {{ $participant->name ?? '-' }}
        @if(($submission->status ?? '') === 'graded' && $content->scoring_enabled && !is_null($submission->score))
            &nbsp;|&nbsp; Nilai: {{ $submission->score }}
        @endif
        @if($submission->submitted_at)
            &nbsp;|&nbsp; Dikumpulkan: {{ $submission->submitted_at->format('d M Y H:i') }}
        @endif
    </div>

    @php
        $answerFor = function ($sid, $bid, $rc = null) use ($answers) {
            $blockAns = $answers[$sid][$bid] ?? null;
            if ($rc === null) return is_string($blockAns) ? $blockAns : '';
            return is_array($blockAns) ? ($blockAns[$rc] ?? '') : '';
        };
    @endphp

    @foreach(($template['sections'] ?? []) as $section)
        @php $sid = $section['id']; $level = $section['level'] ?? 1; @endphp
        <div class="{{ $level === 2 ? 'subsection' : '' }}">
            @if($level === 1)
                <h2>{{ $section['title'] ?: 'Bab' }}</h2>
            @else
                <h3>{{ $section['title'] ?: 'Subbab' }}</h3>
            @endif

            @if(!empty($section['instruction']))
                <div class="instruction">{!! nl2br(e($section['instruction'])) !!}</div>
            @endif

            @foreach(($section['blocks'] ?? []) as $block)
                @php $bid = $block['id']; @endphp
                @if(($block['kind'] ?? '') === 'text')
                    @if(!empty($block['label']))
                        <div style="font-weight:bold;margin-top:6px;">{{ $block['label'] }}</div>
                    @endif
                    <div class="answer">
                        {!! $answerFor($sid, $bid) ?: '<span class="empty">(kosong)</span>' !!}
                    </div>
                @elseif(($block['kind'] ?? '') === 'table')
                    @php
                        $table = $block['table'] ?? ['cells' => []];
                        $cells = $table['cells'] ?? [];
                        // Tentukan jumlah baris header (masuk <thead> agar diulang tiap
                        // halaman & tidak terbelah). Header dianggap ada bila baris pertama
                        // memuat sel 'label'. Tingginya = MAX(rentang rowSpan baris pertama,
                        // jumlah baris awal yang semuanya 'label') — agar header dua-baris
                        // ber-merge (mis. "Pelatihan diperlukan" → Ya/Tidak) tetap utuh.
                        $headerRows = 0;
                        $rowCount = count($cells);
                        if ($rowCount > 0) {
                            $row0HasLabel = false; $maxRowSpan0 = 1;
                            foreach ($cells[0] as $cell) {
                                if (!empty($cell['covered'])) continue;
                                if (($cell['role'] ?? 'input') === 'label') $row0HasLabel = true;
                                $maxRowSpan0 = max($maxRowSpan0, (int) ($cell['rowSpan'] ?? 1));
                            }
                            if ($row0HasLabel) {
                                $byLabel = 0;
                                foreach ($cells as $row) {
                                    $allLabel = true; $hasVisible = false;
                                    foreach ($row as $cell) {
                                        if (!empty($cell['covered'])) continue;
                                        $hasVisible = true;
                                        if (($cell['role'] ?? 'input') !== 'label') { $allLabel = false; break; }
                                    }
                                    if ($hasVisible && $allLabel) { $byLabel++; } else { break; }
                                }
                                $headerRows = min(max($maxRowSpan0, $byLabel), $rowCount);
                            }
                        }
                    @endphp
                    <div class="cs-table-wrap">
                    <table>
                        @if($headerRows > 0)
                            <thead>
                                @foreach(array_slice($cells, 0, $headerRows, true) as $r => $row)
                                    <tr>
                                        @foreach($row as $c => $cell)
                                            @continue(!empty($cell['covered']))
                                            <td colspan="{{ $cell['colSpan'] ?? 1 }}" rowspan="{{ $cell['rowSpan'] ?? 1 }}"
                                                style="background: {{ $cell['bg'] ?? '#ffffff' }}; text-align: {{ $cell['align'] ?? 'left' }};{{ !empty($cell['bold']) ? 'font-weight:bold;' : '' }}">
                                                {{ $cell['text'] ?? '' }}
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </thead>
                        @endif
                        <tbody>
                            @foreach(array_slice($cells, $headerRows, null, true) as $r => $row)
                                <tr>
                                    @foreach($row as $c => $cell)
                                        @continue(!empty($cell['covered']))
                                        @php
                                            $rc = $r.'-'.$c;
                                            $isInput = ($cell['role'] ?? 'input') === 'input';
                                            $val = $isInput ? $answerFor($sid, $bid, $rc) : ($cell['text'] ?? '');
                                        @endphp
                                        <td colspan="{{ $cell['colSpan'] ?? 1 }}" rowspan="{{ $cell['rowSpan'] ?? 1 }}"
                                            style="background: {{ $cell['bg'] ?? '#ffffff' }}; text-align: {{ $cell['align'] ?? 'left' }};{{ !empty($cell['bold']) ? 'font-weight:bold;' : '' }}">
                                            {{ $val !== '' ? $val : '' }}
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
    @endforeach
</body>
</html>
