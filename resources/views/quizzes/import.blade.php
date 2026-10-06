<x-app-layout>
    <x-slot name="header">
        <div class="bg-navy text-white -mx-6 -mt-6 mb-6 px-6 py-8">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <div class="bg-white/20 p-3 rounded-lg">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6m3 6V7m3 10v-3m4 7H5a2 2 0 01-2-2V5a2 2 0 012-2h9l5 5v11a2 2 0 01-2 2z" /></svg>
                    </div>
                    <div>
                        <h2 class="text-3xl font-bold">Import Kuis dari Excel</h2>
                        <p class="text-white/80 mt-1">Upload file Excel untuk membuat kuis secara batch</p>
                    </div>
                </div>
                <a href="{{ route('quizzes.index') }}"
                   class="bg-white/20 text-white px-6 py-3 rounded-lg font-semibold hover:bg-white/30 transition-all duration-200 flex items-center space-x-2">
                     <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7 7-7m-7 7h18" /></svg>
                    <span>Kembali</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto">
            <!-- Alert Messages -->
            @if(session('success'))
                <div class="mb-6 bg-success-soft border-l-4 border-success text-success p-4 rounded-lg shadow-md" role="alert">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <div>
                            <p class="font-bold">Sukses!</p>
                            <p>{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 bg-error-soft border-l-4 border-error text-error p-4 rounded-lg shadow-md" role="alert">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.74-3L13.74 4a2 2 0 00-3.48 0L3.33 16a2 2 0 001.74 3z" /></svg>
                        <div>
                            <p class="font-bold">Error!</p>
                            <p>{{ session('error') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if(session('import_errors') && count(session('import_errors')) > 0)
                <div class="mb-6 bg-warning-soft border-l-4 border-warning text-warning p-4 rounded-lg shadow-md" role="alert">
                    <div class="flex items-start">
                        <svg class="h-5 w-5 mr-3 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.74-3L13.74 4a2 2 0 00-3.48 0L3.33 16a2 2 0 001.74 3z" /></svg>
                        <div class="flex-1">
                            <p class="font-bold mb-2">Peringatan Import:</p>
                            <ul class="list-disc list-inside space-y-1">
                                @foreach(session('import_errors') as $error)
                                    <li class="text-sm">{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Instructions Card -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                <div class="flex items-start space-x-4">
                    <div class="bg-info-soft p-3 rounded-lg text-navy">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-gray-800 mb-3">Panduan Import Kuis</h3>
                        <ul class="list-disc pl-5 space-y-2 text-gray-600 marker:text-bass-red">
                            <li>Download template Excel terlebih dahulu</li>
                            <li>Isi data quiz sesuai format yang ada</li>
                            <li>Satu quiz bisa memiliki banyak pertanyaan</li>
                            <li>Untuk pertanyaan yang satu quiz, gunakan judul quiz yang sama</li>
                            <li>Tipe pertanyaan: <code class="bg-gray-200 px-2 py-1 rounded">multiple_choice</code> atau <code class="bg-gray-200 px-2 py-1 rounded">true_false</code></li>
                            <li>Status: <code class="bg-gray-200 px-2 py-1 rounded">draft</code> atau <code class="bg-gray-200 px-2 py-1 rounded">published</code></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Download Template Card -->
            <div class="bg-navy rounded-xl shadow-lg p-6 mb-6 text-white">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="bg-white/20 p-4 rounded-lg">
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0l-4-4m4 4l4-4M5 19h14" /></svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold mb-1">Template Excel</h3>
                            <p class="text-white/80">Download template untuk memulai import kuis</p>
                        </div>
                    </div>
                    <a href="{{ route('quizzes.download-template') }}"
                       class="bg-white text-navy px-6 py-3 rounded-lg font-semibold shadow-lg hover:bg-gray-50 transition-colors duration-200 flex items-center space-x-2 focus:outline-none focus:ring-2 focus:ring-bass-red">
                         <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0l-4-4m4 4l4-4M5 19h14" /></svg>
                        <span>Download Template</span>
                    </a>
                </div>
            </div>

            <!-- Import Form Card -->
            <div class="bg-white rounded-xl shadow-lg p-8">
                <h3 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                    <svg class="h-6 w-6 text-bass-red mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16V4m0 0L8 8m4-4l4 4M5 20h14" /></svg>
                    Upload File Excel
                </h3>

                <form action="{{ route('quizzes.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <!-- Lesson Selection -->
                    <div class="mb-6">
                        <label for="lesson_id" class="block text-sm font-semibold text-gray-700 mb-2">
                            <svg class="inline h-4 w-4 mr-2 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 19.5A2.5 2.5 0 016.5 17H20V5H6.5A2.5 2.5 0 004 7.5v12z" /></svg>Pilih Lesson
                        </label>
                        <select name="lesson_id" id="lesson_id"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-bass-red focus:border-bass-red transition-all duration-200 @error('lesson_id') border-error @enderror"
                                required>
                            <option value="">-- Pilih Lesson --</option>
                            @foreach($lessons as $lesson)
                                <option value="{{ $lesson->id }}" {{ old('lesson_id') == $lesson->id ? 'selected' : '' }}>
                                    {{ $lesson->title }} - {{ $lesson->course->title }}
                                </option>
                            @endforeach
                        </select>
                        @error('lesson_id')
                            <p class="text-error text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- File Upload -->
                    <div class="mb-6">
                        <label for="file" class="block text-sm font-semibold text-gray-700 mb-2">
                            <svg class="inline h-4 w-4 mr-2 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01.9-7.9A5 5 0 0117.9 9H18a4 4 0 010 8h-1M12 12v9m0-9l-3 3m3-3l3 3" /></svg>File Excel
                        </label>
                        <div class="flex items-center justify-center w-full">
                            <label for="file" class="flex flex-col items-center justify-center w-full h-48 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-bass-red-soft hover:border-bass-red transition-all duration-200 @error('file') border-error @enderror">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <svg class="h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01.9-7.9A5 5 0 0117.9 9H18a4 4 0 010 8h-1M12 12v9m0-9l-3 3m3-3l3 3" /></svg>
                                    <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Klik untuk upload</span> atau drag and drop</p>
                                    <p class="text-xs text-gray-500">File Excel (XLSX, XLS, CSV) maksimal 2MB</p>
                                    <p class="text-xs text-gray-400 mt-2" id="file-name"></p>
                                </div>
                                <input id="file" name="file" type="file" class="hidden" accept=".xlsx,.xls,.csv" required onchange="updateFileName(this)" />
                            </label>
                        </div>
                        @error('file')
                            <p class="text-error text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                        <a href="{{ route('quizzes.index') }}"
                           class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 font-semibold hover:bg-gray-50 transition-all duration-200">
                             Batal
                        </a>
                        <button type="submit"
                                class="inline-flex items-center bg-bass-red hover:bg-bass-red-hover text-white px-8 py-3 rounded-lg font-semibold shadow-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-bass-red focus:ring-offset-2">
                             <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16V4m0 0L8 8m4-4l4 4M5 20h14" /></svg>Upload & Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function updateFileName(input) {
            const fileName = input.files[0]?.name;
            const fileNameDisplay = document.getElementById('file-name');
            if (fileName) {
                fileNameDisplay.textContent = 'File terpilih: ' + fileName;
                fileNameDisplay.classList.remove('text-gray-400');
                fileNameDisplay.classList.add('text-bass-red', 'font-semibold');
            }
        }
    </script>
</x-app-layout>
