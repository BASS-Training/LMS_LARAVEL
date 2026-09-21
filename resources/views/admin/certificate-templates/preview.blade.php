<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Pratinjau Template') }}: {{ $certificateTemplate->name }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('admin.certificate-templates.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-xl font-medium text-sm text-gray-700 hover:bg-gray-50 transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali ke Template
                </a>
                <a href="{{ route('admin.certificate-templates.edit-advanced', $certificateTemplate) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-navy text-white rounded-xl font-medium text-sm hover:bg-navy/90 transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                    Edit Template
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                
                <!-- Control Panel -->
                <div class="border-b border-gray-200 bg-gray-50 p-4">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div class="flex items-center space-x-4">
                            <h3 class="text-lg font-semibold text-gray-700">Pengaturan Pratinjau</h3>
                        </div>
                        
                        <div class="flex items-center space-x-2">
                            <div class="flex items-center space-x-2">
                                <button id="zoom-out" class="p-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM10.5 7.5v6m3-3h-6"/></svg>
                                </button>
                                <span id="zoom-level" class="text-sm font-medium px-3 py-1 bg-white border border-gray-300 rounded-lg">100%</span>
                                <button id="zoom-in" class="p-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM10.5 7.5v6m3-3h-6"/></svg>
                                </button>
                                <button id="fit-screen" class="px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 text-sm transition">
                                    Pas Layar
                                </button>
                            </div>
                            
                            <button id="download-pdf" class="px-4 py-2 bg-bass-red text-white rounded-xl font-medium text-sm hover:bg-[#B91818] transition shadow-sm">
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                    Unduh PDF
                                </span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Sample Data Form -->
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-3 lg:grid-cols-7 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama</label>
                            <input type="text" id="sample-name" value="John Doe" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-bass-red focus:ring-bass-red">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kursus</label>
                            <input type="text" id="sample-course" value="Advanced Web Development" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-bass-red focus:ring-bass-red">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                            <input type="text" id="sample-date" value="{{ now()->format('F d, Y') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-bass-red focus:ring-bass-red">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Pelatihan</label>
                            <input type="text" id="sample-training-date" value="6 Maret 2026 - 8 Maret 2026" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-bass-red focus:ring-bass-red">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Instruktur</label>
                            <input type="text" id="sample-instructor" value="Dr. Jane Smith" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-bass-red focus:ring-bass-red">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nilai</label>
                            <input type="text" id="sample-grade" value="A+" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-bass-red focus:ring-bass-red">
                        </div>
                        <div class="flex items-end">
                            <button id="update-preview" class="w-full px-3 py-2 bg-bass-red text-white rounded-xl font-medium text-sm hover:bg-[#B91818] transition shadow-sm">
                                Perbarui Pratinjau
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Preview Area -->
                <div class="p-6 bg-gray-100">
                    @php
                        $rawLayoutData = $certificateTemplate->layout_data ?? [];
                        if (is_string($rawLayoutData)) {
                            $decodedLayout = json_decode($rawLayoutData, true);
                            $layoutPages = is_array($decodedLayout) ? $decodedLayout : [];
                        } elseif (is_array($rawLayoutData)) {
                            $layoutPages = $rawLayoutData;
                        } else {
                            $layoutPages = [];
                        }
                    @endphp
                    <div id="preview-container" class="mx-auto overflow-auto">
                        <div id="preview-content" class="mx-auto" style="transform-origin: top center;">
                            @forelse($layoutPages as $pageIndex => $page)
                                @php
                                    $pageWidth = $page['width'] ?? 794;
                                    $pageHeight = $page['height'] ?? 1123;
                                    $backgroundImagePath = $page['background_image_path'] ?? null;
                                    $backgroundSize = $page['backgroundSize'] ?? 'cover';
                                    $backgroundColor = $page['backgroundColor'] ?? '#ffffff';
                                @endphp
                                <div class="preview-page bg-white shadow-lg mx-auto mb-8 {{ $loop->last ? '' : 'page-break-after' }}" 
                                     style="width: {{ $pageWidth }}px; height: {{ $pageHeight }}px; 
                                            {{ !empty($backgroundImagePath) ? 'background-image: url(' . asset('storage/' . $backgroundImagePath) . '); background-size: ' . $backgroundSize . '; background-position: center; background-repeat: no-repeat;' : 'background-color: ' . $backgroundColor . ';' }}">
                                    
                                    @if(isset($page['elements']) && is_array($page['elements']))
                                        @foreach($page['elements'] as $element)
                                            @php
                                                $elementType = $element['type'] ?? 'text';
                                                $elementContent = $element['content'] ?? '';
                                                $elementX = $element['x'] ?? 0;
                                                $elementY = $element['y'] ?? 0;
                                                $elementWidth = $element['width'] ?? 0;
                                                $elementHeight = $element['height'] ?? 0;
                                                $elementRotation = $element['rotation'] ?? 0;
                                                $elementOpacity = $element['opacity'] ?? 1;
                                                $elementZIndex = $element['zIndex'] ?? 1;
                                                $elementTextAlign = $element['textAlign'] ?? 'left';
                                                $elementFontFamily = $element['fontFamily'] ?? 'Arial';
                                                $elementFontSize = $element['fontSize'] ?? 16;
                                                $elementColor = $element['color'] ?? '#000000';
                                                $elementIsBold = !empty($element['isBold']);
                                                $elementIsItalic = !empty($element['isItalic']);
                                                $elementIsUnderline = !empty($element['isUnderline']);
                                            @endphp
                                            @if($elementType === 'text')
                                                <div class="absolute" 
                                                     style="left: {{ $elementX }}px; 
                                                            top: {{ $elementY }}px; 
                                                            width: {{ $elementWidth }}px; 
                                                            height: {{ $elementHeight }}px; 
                                                            transform: rotate({{ $elementRotation }}deg); 
                                                            opacity: {{ $elementOpacity }}; 
                                                            z-index: {{ $elementZIndex }};">
                                                    <div style="width: 100%; 
                                                                height: 100%; 
                                                                display: flex; 
                                                                align-items: center; 
                                                                justify-content: {{ $elementTextAlign === 'center' ? 'center' : ($elementTextAlign === 'right' ? 'flex-end' : 'flex-start') }}; 
                                                                font-family: '{{ $elementFontFamily }}'; 
                                                                font-size: {{ $elementFontSize }}px; 
                                                                color: {{ $elementColor }}; 
                                                                font-weight: {{ $elementIsBold ? 'bold' : 'normal' }}; 
                                                                font-style: {{ $elementIsItalic ? 'italic' : 'normal' }}; 
                                                                text-decoration: {{ $elementIsUnderline ? 'underline' : 'none' }}; 
                                                                word-wrap: break-word; 
                                                                overflow: hidden; 
                                                                padding: 2px;">
                                                        <span class="template-variable" data-original="{{ $elementContent }}">{{ $elementContent }}</span>
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    @endif
                                </div>

                                @if(!$loop->last)
                                    <div class="text-center my-4 text-sm text-gray-500">Halaman {{ $pageIndex + 1 }}</div>
                                @endif
                            @empty
                                <div class="text-center text-gray-500 py-12">
                                    Tidak ada halaman untuk template ini.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Template Info -->
                <div class="border-t border-gray-200 bg-gray-50 p-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-600">
                        <div>
                            <strong>Nama Template:</strong> {{ $certificateTemplate->name }}
                        </div>
                        <div>
                            <strong>Halaman:</strong> {{ count($layoutPages) }}
                        </div>
                        <div>
                            <strong>Terakhir Diubah:</strong> {{ $certificateTemplate->updated_at->format('d M Y H:i') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
        .preview-page {
            position: relative;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        @media print {
            .page-break-after {
                page-break-after: always;
            }
        }
        
        .template-variable {
            transition: background-color 0.2s;
        }
        
        .template-variable:hover {
            background-color: rgba(218, 30, 30, 0.1);
            outline: 1px dashed #DA1E1E;
        }
    </style>
    @endpush

    @push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let currentZoom = 1;
            const previewContent = document.getElementById('preview-content');
            const zoomLevel = document.getElementById('zoom-level');

            const variableMappings = {
                '@{{name}}': 'sample-name',
                '@{{participant_name}}': 'sample-name',
                '@{{course}}': 'sample-course',
                '@{{course_title}}': 'sample-course', 
                '@{{completion_date}}': 'sample-date',
                '@{{date}}': 'sample-date',
                '@{{instructor_name}}': 'sample-instructor',
                '@{{instructor}}': 'sample-instructor',
                '@{{training_date_range}}': 'sample-training-date',
                '@{{training_date}}': 'sample-training-date',
                '@{{training_period}}': 'sample-training-date',
                '@{{grade}}': 'sample-grade',
                '@{{organization}}': 'sample-organization'
            };

            document.getElementById('zoom-in').addEventListener('click', function() {
                currentZoom = Math.min(3, currentZoom + 0.2);
                updateZoom();
            });

            document.getElementById('zoom-out').addEventListener('click', function() {
                currentZoom = Math.max(0.2, currentZoom - 0.2);
                updateZoom();
            });

            document.getElementById('fit-screen').addEventListener('click', function() {
                const container = document.getElementById('preview-container');
                const content = document.getElementById('preview-content');
                const containerWidth = container.clientWidth - 40;
                const firstPage = content.querySelector('.preview-page');
                if (!firstPage) return;
                const contentWidth = firstPage.offsetWidth;
                currentZoom = Math.min(1, containerWidth / contentWidth);
                updateZoom();
            });

            function updateZoom() {
                previewContent.style.transform = `scale(${currentZoom})`;
                zoomLevel.textContent = Math.round(currentZoom * 100) + '%';
            }

            document.getElementById('update-preview').addEventListener('click', function() {
                updatePreviewVariables();
            });

            Object.values(variableMappings).forEach(inputId => {
                const input = document.getElementById(inputId);
                if (input) {
                    input.addEventListener('input', debounce(updatePreviewVariables, 300));
                }
            });

            function updatePreviewVariables() {
                document.querySelectorAll('.template-variable').forEach(element => {
                    const original = element.dataset.original;
                    let newContent = original;
                    Object.entries(variableMappings).forEach(([variable, inputId]) => {
                        const input = document.getElementById(inputId);
                        if (input && newContent.includes(variable)) {
                            newContent = newContent.replace(new RegExp(escapeRegExp(variable), 'g'), input.value);
                        }
                    });
                    element.textContent = newContent;
                });
            }

            document.getElementById('download-pdf').addEventListener('click', async function() {
                this.disabled = true;
                this.innerHTML = '<svg class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>Memproses...';

                try {
                    const { jsPDF } = window.jspdf;
                    const pdf = new jsPDF({
                        orientation: 'landscape',
                        unit: 'px',
                        format: [794, 1123]
                    });

                    const pages = document.querySelectorAll('.preview-page');
                    for (let i = 0; i < pages.length; i++) {
                        if (i > 0) pdf.addPage();
                        const canvas = await html2canvas(pages[i], { scale: 2, useCORS: true, allowTaint: true });
                        const imgData = canvas.toDataURL('image/jpeg', 0.9);
                        pdf.addImage(imgData, 'JPEG', 0, 0, 794, 1123);
                    }
                    pdf.save(`{{ $certificateTemplate->name }}_preview.pdf`);
                } catch (error) {
                    console.error('Error generating PDF:', error);
                    alert('Gagal membuat PDF. Silakan coba lagi.');
                } finally {
                    this.disabled = false;
                    this.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg> Unduh PDF';
                }
            });

            function debounce(func, wait) {
                let timeout;
                return function executedFunction(...args) {
                    const later = () => { clearTimeout(timeout); func(...args); };
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                };
            }

            function escapeRegExp(string) {
                return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            }

            updatePreviewVariables();
            setTimeout(() => { document.getElementById('fit-screen').click(); }, 100);
        });
    </script>
    @endpush
</x-app-layout>
