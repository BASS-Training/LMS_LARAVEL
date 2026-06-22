{{--
    Builder untuk konten tipe "case_study".
    Menghasilkan template JSON (bab/subbab + tabel ber-merge) yang dikirim via
    hidden input `case_study_template`. Dipakai di dalam <form> contentFormManager.
--}}
<div x-data="caseStudyBuilder({
        initial: @js($content->type === 'case_study' ? $content->case_study_template : ['version' => 1, 'sections' => []]),
        allowDownload: @js((bool)($content->allow_answer_download ?? false)),
        reviewMode: @js($content->exists && $content->type === 'case_study' ? (!($content->requires_review ?? true) ? 'no_review' : (!($content->scoring_enabled ?? true) ? 'feedback_only' : 'scoring')) : 'scoring'),
     })"
     class="bg-gradient-to-r from-amber-50 to-orange-50 rounded-xl p-6 border border-amber-100 space-y-6">

    {{-- hidden payloads --}}
    <input type="hidden" name="case_study_template" :value="templateJson()">
    <input type="hidden" name="allow_answer_download" :value="allowDownload ? 1 : 0">
    <input type="hidden" name="grading_mode" value="overall">
    <input type="hidden" name="review_mode" :value="reviewMode">

    <div class="flex items-start justify-between flex-wrap gap-3">
        <div>
            <h3 class="text-lg font-bold text-gray-900">📋 Penyusun Template Studi Kasus</h3>
            <p class="text-sm text-gray-600">Susun Bab, Subbab, dan Tabel. Peserta akan mengisi sesuai template ini.</p>
        </div>
        <button type="button" @click="addSection(1)"
                class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold rounded-lg shadow">
            + Tambah Bab
        </button>
    </div>

    {{-- Pengaturan penilaian & download --}}
    <div class="bg-white rounded-lg border border-amber-200 p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <h4 class="text-sm font-semibold text-gray-800 mb-2">Model Penilaian</h4>
            <div class="space-y-2 text-sm">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" value="scoring" x-model="reviewMode" class="text-amber-600 focus:ring-amber-500">
                    <span>Skor + Feedback (dinilai instruktur)</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" value="feedback_only" x-model="reviewMode" class="text-amber-600 focus:ring-amber-500">
                    <span>Hanya Feedback (tanpa skor)</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" value="no_review" x-model="reviewMode" class="text-amber-600 focus:ring-amber-500">
                    <span>Tanpa review (cukup dikumpulkan)</span>
                </label>
            </div>
        </div>
        <div>
            <h4 class="text-sm font-semibold text-gray-800 mb-2">Unduh Jawaban</h4>
            <label class="flex items-center gap-2 cursor-pointer text-sm">
                <input type="checkbox" x-model="allowDownload" class="rounded text-amber-600 focus:ring-amber-500">
                <span>Izinkan peserta mengunduh jawaban menjadi PDF</span>
            </label>
            <p class="text-xs text-gray-500 mt-1">Jika aktif, peserta yang sudah mengumpulkan dapat mengunduh hasilnya.</p>
        </div>
    </div>

    {{-- Daftar Section --}}
    <div class="space-y-4">
        <template x-if="template.sections.length === 0">
            <div class="text-center text-sm text-gray-500 bg-white border border-dashed border-amber-300 rounded-lg py-8">
                Belum ada bab. Klik "Tambah Bab" untuk mulai menyusun template.
            </div>
        </template>

        <template x-for="(section, sIdx) in template.sections" :key="section.id">
            <div class="bg-white rounded-lg border shadow-sm"
                 :class="section.level === 1 ? 'border-amber-300' : 'border-gray-200 ml-6'">
                <div class="flex items-center gap-2 px-4 py-3 border-b"
                     :class="section.level === 1 ? 'bg-amber-100/60' : 'bg-gray-50'">
                    <span class="text-xs font-bold px-2 py-1 rounded"
                          :class="section.level === 1 ? 'bg-amber-600 text-white' : 'bg-gray-400 text-white'"
                          x-text="section.level === 1 ? 'BAB' : 'SUBBAB'"></span>
                    <input type="text" x-model="section.title"
                           class="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm font-semibold focus:border-amber-500 focus:ring-2 focus:ring-amber-100"
                           :placeholder="section.level === 1 ? 'Judul Bab (mis. Company Profile)' : 'Judul Subbab (mis. Visi Perusahaan)'">
                    <div class="flex items-center gap-1">
                        <button type="button" @click="moveSection(sIdx, -1)" title="Naik" class="p-1.5 text-gray-500 hover:bg-gray-100 rounded">▲</button>
                        <button type="button" @click="moveSection(sIdx, 1)" title="Turun" class="p-1.5 text-gray-500 hover:bg-gray-100 rounded">▼</button>
                        <button type="button" @click="removeSection(sIdx)" title="Hapus" class="p-1.5 text-red-500 hover:bg-red-50 rounded">🗑</button>
                    </div>
                </div>

                <div class="p-4 space-y-3">
                    {{-- Instruksi opsional --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Instruksi / petunjuk (opsional)</label>
                        <textarea x-model="section.instruction" rows="2"
                                  class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-100"
                                  placeholder="Petunjuk pengisian untuk peserta..."></textarea>
                    </div>

                    {{-- Blocks --}}
                    <template x-for="(block, bIdx) in section.blocks" :key="block.id">
                        <div class="border border-gray-200 rounded-lg p-3 bg-gray-50/50">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-semibold text-gray-500"
                                      x-text="block.kind === 'text' ? '📝 Blok Teks (diisi peserta)' : '🔲 Blok Tabel'"></span>
                                <div class="flex items-center gap-1">
                                    <button type="button" @click="moveBlock(section, bIdx, -1)" class="p-1 text-gray-400 hover:bg-gray-100 rounded text-xs">▲</button>
                                    <button type="button" @click="moveBlock(section, bIdx, 1)" class="p-1 text-gray-400 hover:bg-gray-100 rounded text-xs">▼</button>
                                    <button type="button" @click="removeBlock(section, bIdx)" class="p-1 text-red-500 hover:bg-red-50 rounded text-xs">🗑</button>
                                </div>
                            </div>

                            {{-- Text block: peserta mengisi rich text saat attempt; di sini hanya placeholder info --}}
                            <template x-if="block.kind === 'text'">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Label / pertanyaan (opsional)</label>
                                    <input type="text" x-model="block.label"
                                           class="w-full px-3 py-2 border border-gray-200 rounded text-sm"
                                           placeholder="mis. Tuliskan sejarah perusahaan...">
                                    <p class="text-xs text-gray-400 mt-1">Peserta akan mengisi area teks (rich text) di bawah label ini.</p>
                                </div>
                            </template>

                            {{-- Table block --}}
                            <template x-if="block.kind === 'table'">
                                <div class="space-y-2">
                                    <div class="flex flex-wrap items-center gap-2 text-xs">
                                        <button type="button" @click="addRow(block.table)" class="px-2 py-1 bg-gray-200 hover:bg-gray-300 rounded">+ Baris</button>
                                        <button type="button" @click="removeRow(block.table)" class="px-2 py-1 bg-gray-200 hover:bg-gray-300 rounded">− Baris</button>
                                        <button type="button" @click="addCol(block.table)" class="px-2 py-1 bg-gray-200 hover:bg-gray-300 rounded">+ Kolom</button>
                                        <button type="button" @click="removeCol(block.table)" class="px-2 py-1 bg-gray-200 hover:bg-gray-300 rounded">− Kolom</button>
                                        <span class="mx-1 text-gray-300">|</span>
                                        <button type="button" @click="mergeSelected(block.table)" class="px-2 py-1 bg-amber-200 hover:bg-amber-300 rounded">Gabung sel</button>
                                        <button type="button" @click="unmergeSelected(block.table)" class="px-2 py-1 bg-amber-200 hover:bg-amber-300 rounded">Pisah</button>
                                        <span class="text-gray-400" x-text="selectionInfo(block.table)"></span>
                                    </div>

                                    {{-- Property panel untuk sel terpilih --}}
                                    <div x-show="hasSelection(block.table)" class="flex flex-wrap items-center gap-2 bg-white border rounded p-2 text-xs">
                                        <span class="font-semibold text-gray-600">Sel terpilih:</span>
                                        <select @change="applyToSelection(block.table, 'role', $event.target.value)" class="border rounded px-1 py-0.5">
                                            <option value="">Peran…</option>
                                            <option value="label">Label (tetap)</option>
                                            <option value="input">Input (diisi peserta)</option>
                                        </select>
                                        <label class="flex items-center gap-1">Warna
                                            <input type="color" @input="applyToSelection(block.table, 'bg', $event.target.value)" class="w-6 h-6 p-0 border rounded">
                                        </label>
                                        <select @change="applyToSelection(block.table, 'align', $event.target.value)" class="border rounded px-1 py-0.5">
                                            <option value="">Rata…</option>
                                            <option value="left">Kiri</option>
                                            <option value="center">Tengah</option>
                                            <option value="right">Kanan</option>
                                        </select>
                                        <button type="button" @click="applyToSelection(block.table, 'bold', 'toggle')" class="px-2 py-0.5 border rounded font-bold">B</button>
                                        <template x-if="singleSelected(block.table)">
                                            <input type="text" :value="firstSelectedText(block.table)"
                                                   @input="setSelectedText(block.table, $event.target.value)"
                                                   class="border rounded px-2 py-0.5 flex-1 min-w-[160px]"
                                                   placeholder="Teks label / placeholder input">
                                        </template>
                                        <button type="button" @click="clearSelection()" class="text-gray-400 hover:text-gray-600">tutup</button>
                                    </div>

                                    {{-- Grid tabel --}}
                                    <div class="overflow-x-auto">
                                        <table class="border-collapse">
                                            <tbody>
                                                <template x-for="(row, r) in block.table.cells" :key="r">
                                                    <tr>
                                                        <template x-for="(cell, c) in row" :key="c">
                                                            <td x-show="!cell.covered"
                                                                :colspan="cell.colSpan" :rowspan="cell.rowSpan"
                                                                @click="toggleCell(block.table, r, c)"
                                                                class="border border-gray-400 align-top p-1 cursor-pointer min-w-[90px] h-12 text-xs relative"
                                                                :style="`background:${cell.bg||'#ffffff'};text-align:${cell.align||'left'}`"
                                                                :class="isSelected(block.table, r, c) ? 'outline outline-2 outline-amber-500' : ''">
                                                                <span class="absolute top-0 left-0 text-[9px] px-1 rounded-br"
                                                                      :class="cell.role === 'input' ? 'bg-blue-100 text-blue-700' : 'bg-gray-200 text-gray-600'"
                                                                      x-text="cell.role === 'input' ? 'INPUT' : 'LABEL'"></span>
                                                                <span class="block pt-3" :class="cell.bold ? 'font-bold' : ''"
                                                                      x-text="cell.role === 'input' ? (cell.text ? '['+cell.text+']' : '(diisi peserta)') : (cell.text || '—')"
                                                                      :style="cell.role === 'input' ? 'color:#6b7280' : ''"></span>
                                                            </td>
                                                        </template>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    {{-- Tombol tambah block / subbab --}}
                    <div class="flex flex-wrap items-center gap-2 pt-1">
                        <button type="button" @click="addTextBlock(section)" class="px-3 py-1.5 text-xs bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg font-medium">+ Blok Teks</button>
                        <button type="button" @click="addTableBlock(section)" class="px-3 py-1.5 text-xs bg-green-100 hover:bg-green-200 text-green-700 rounded-lg font-medium">+ Blok Tabel</button>
                        <template x-if="section.level === 1">
                            <button type="button" @click="addSubsection(sIdx)" class="px-3 py-1.5 text-xs bg-amber-100 hover:bg-amber-200 text-amber-700 rounded-lg font-medium">+ Subbab</button>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>

@push('scripts')
<script>
    function caseStudyBuilder(cfg) {
        return {
            template: cfg.initial && Array.isArray(cfg.initial.sections)
                ? cfg.initial
                : { version: 1, sections: [] },
            allowDownload: !!cfg.allowDownload,
            reviewMode: cfg.reviewMode || 'scoring',
            // selection state (per active table)
            activeTableRef: null,
            selected: [], // array of "r-c"

            uid() {
                return 'id_' + Math.random().toString(36).slice(2, 10);
            },

            // ---- Sections ----
            addSection(level) {
                this.template.sections.push({
                    id: this.uid(), level: level, title: '', instruction: '', blocks: []
                });
            },
            addSubsection(parentIdx) {
                // sisipkan subbab tepat setelah bab induk (dan subbab-subbab yang sudah ada)
                let insertAt = parentIdx + 1;
                while (insertAt < this.template.sections.length && this.template.sections[insertAt].level === 2) {
                    insertAt++;
                }
                this.template.sections.splice(insertAt, 0, {
                    id: this.uid(), level: 2, title: '', instruction: '', blocks: []
                });
            },
            removeSection(idx) {
                if (!confirm('Hapus bagian ini beserta isinya?')) return;
                this.template.sections.splice(idx, 1);
            },
            moveSection(idx, dir) {
                const target = idx + dir;
                if (target < 0 || target >= this.template.sections.length) return;
                const arr = this.template.sections;
                [arr[idx], arr[target]] = [arr[target], arr[idx]];
            },

            // ---- Blocks ----
            addTextBlock(section) {
                section.blocks.push({ id: this.uid(), kind: 'text', label: '' });
            },
            addTableBlock(section) {
                section.blocks.push({ id: this.uid(), kind: 'table', table: this.newTable(2, 2) });
            },
            removeBlock(section, bIdx) {
                section.blocks.splice(bIdx, 1);
            },
            moveBlock(section, bIdx, dir) {
                const target = bIdx + dir;
                if (target < 0 || target >= section.blocks.length) return;
                [section.blocks[bIdx], section.blocks[target]] = [section.blocks[target], section.blocks[bIdx]];
            },

            // ---- Tables ----
            newCell(role) {
                return { rowSpan: 1, colSpan: 1, covered: false, role: role || 'input', text: '', bg: '#ffffff', align: 'left', bold: false };
            },
            newTable(rows, cols) {
                const cells = [];
                for (let r = 0; r < rows; r++) {
                    const row = [];
                    for (let c = 0; c < cols; c++) {
                        // baris pertama default sebagai label/header
                        row.push(this.newCell(r === 0 ? 'label' : 'input'));
                    }
                    cells.push(row);
                }
                return { cells: cells };
            },
            rowsOf(t) { return t.cells.length; },
            colsOf(t) { return t.cells[0] ? t.cells[0].length : 0; },
            addRow(t) {
                const cols = this.colsOf(t);
                const row = [];
                for (let c = 0; c < cols; c++) row.push(this.newCell('input'));
                t.cells.push(row);
                this.normalize(t);
            },
            removeRow(t) {
                if (this.rowsOf(t) <= 1) return;
                t.cells.pop();
                this.normalize(t);
                this.clearSelection();
            },
            addCol(t) {
                t.cells.forEach((row, r) => row.push(this.newCell(r === 0 ? 'label' : 'input')));
                this.normalize(t);
            },
            removeCol(t) {
                if (this.colsOf(t) <= 1) return;
                t.cells.forEach(row => row.pop());
                this.normalize(t);
                this.clearSelection();
            },

            // Recompute "covered" flags from spans, clamp spans to bounds.
            normalize(t) {
                const rows = this.rowsOf(t), cols = this.colsOf(t);
                t.cells.forEach(row => row.forEach(cell => { cell.covered = false; }));
                for (let r = 0; r < rows; r++) {
                    for (let c = 0; c < cols; c++) {
                        const cell = t.cells[r][c];
                        if (cell.covered) continue;
                        cell.rowSpan = Math.max(1, Math.min(cell.rowSpan || 1, rows - r));
                        cell.colSpan = Math.max(1, Math.min(cell.colSpan || 1, cols - c));
                        for (let rr = r; rr < r + cell.rowSpan; rr++) {
                            for (let cc = c; cc < c + cell.colSpan; cc++) {
                                if (rr === r && cc === c) continue;
                                t.cells[rr][cc].covered = true;
                                t.cells[rr][cc].rowSpan = 1;
                                t.cells[rr][cc].colSpan = 1;
                            }
                        }
                    }
                }
            },

            // ---- Selection ----
            keyOf(r, c) { return r + '-' + c; },
            isActive(t) { return this.activeTableRef === t; },
            toggleCell(t, r, c) {
                if (this.activeTableRef !== t) { this.activeTableRef = t; this.selected = []; }
                const k = this.keyOf(r, c);
                const i = this.selected.indexOf(k);
                if (i >= 0) this.selected.splice(i, 1); else this.selected.push(k);
            },
            isSelected(t, r, c) { return this.isActive(t) && this.selected.includes(this.keyOf(r, c)); },
            hasSelection(t) { return this.isActive(t) && this.selected.length > 0; },
            singleSelected(t) { return this.isActive(t) && this.selected.length === 1; },
            clearSelection() { this.selected = []; this.activeTableRef = null; },
            selectionInfo(t) {
                if (!this.isActive(t) || this.selected.length === 0) return '';
                return this.selected.length + ' sel dipilih';
            },
            selectedCoords() {
                return this.selected.map(k => k.split('-').map(Number));
            },
            firstSelectedCell(t) {
                if (!this.singleSelected(t)) return null;
                const [r, c] = this.selected[0].split('-').map(Number);
                return t.cells[r][c];
            },
            firstSelectedText(t) {
                const cell = this.firstSelectedCell(t);
                return cell ? (cell.text || '') : '';
            },
            setSelectedText(t, val) {
                const cell = this.firstSelectedCell(t);
                if (cell) cell.text = val;
            },
            applyToSelection(t, prop, val) {
                if (!this.hasSelection(t) || val === '') return;
                this.selectedCoords().forEach(([r, c]) => {
                    const cell = t.cells[r][c];
                    if (prop === 'bold') cell.bold = !cell.bold;
                    else cell[prop] = val;
                });
            },

            mergeSelected(t) {
                if (!this.hasSelection(t) || this.selected.length < 2) {
                    alert('Pilih minimal 2 sel yang membentuk persegi panjang.');
                    return;
                }
                const coords = this.selectedCoords();
                let minR = Infinity, maxR = -1, minC = Infinity, maxC = -1;
                coords.forEach(([r, c]) => {
                    minR = Math.min(minR, r); maxR = Math.max(maxR, r);
                    minC = Math.min(minC, c); maxC = Math.max(maxC, c);
                });
                const expected = (maxR - minR + 1) * (maxC - minC + 1);
                if (coords.length !== expected) {
                    alert('Sel yang dipilih harus membentuk persegi panjang penuh.');
                    return;
                }
                const anchor = t.cells[minR][minC];
                anchor.rowSpan = maxR - minR + 1;
                anchor.colSpan = maxC - minC + 1;
                this.normalize(t);
                this.clearSelection();
            },
            unmergeSelected(t) {
                if (!this.hasSelection(t)) return;
                this.selectedCoords().forEach(([r, c]) => {
                    const cell = t.cells[r][c];
                    if (!cell.covered) { cell.rowSpan = 1; cell.colSpan = 1; }
                });
                this.normalize(t);
                this.clearSelection();
            },

            // ---- Output ----
            templateJson() {
                return JSON.stringify(this.template);
            },
        };
    }
</script>
@endpush
