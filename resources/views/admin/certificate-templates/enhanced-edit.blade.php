<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit Template Sertifikat') }}: {{ $certificateTemplate->name }} - Editor Lanjutan
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-full mx-auto px-4">
            <div x-data="enhancedCertificateEditor({ initialPages: window.enhancedCertificateInitialPages, storageBaseUrl: window.enhancedCertificateStorageUrl, fontStylesheetUrl: window.enhancedCertificateFontStylesheetUrl })">
                <form id="template-form" @submit.prevent="submitForm" method="POST" action="{{ route('admin.certificate-templates.update', $certificateTemplate) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <!-- Top Toolbar -->
                    <div class="bg-white shadow-sm rounded-2xl border border-gray-200 mb-6 p-4">
                        <div class="flex flex-col md:flex-row justify-between md:items-center gap-4">
                            <div class="flex items-center space-x-4">
                                <div>
                                    <x-input-label for="name" :value="__('Template Name')" />
                                    <x-text-input id="name" type="text" name="name" :value="old('name', $certificateTemplate->name)" required class="mt-1 w-64" />
                                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                                </div>

                                <!-- Zoom Controls -->
                                <div class="flex items-center space-x-2">
                                    <button type="button" @click="zoomOut" class="p-2 bg-gray-100 hover:bg-gray-200 rounded">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                        </svg>
                                    </button>
                                    <span x-text="Math.round(zoom * 100) + '%'" class="text-sm font-medium w-12 text-center"></span>
                                    <button type="button" @click="zoomIn" class="p-2 bg-gray-100 hover:bg-gray-200 rounded">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                    </button>
                                    <button type="button" @click="resetZoom" class="p-2 bg-gray-100 hover:bg-gray-200 rounded text-xs">Reset</button>
                                </div>
                            </div>

                            <div class="flex items-center space-x-4">
                                <!-- Grid Toggle -->
                                <button type="button" @click="showGrid = !showGrid" class="p-2 rounded" :class="showGrid ? 'bg-bass-red/10 text-bass-red' : 'bg-gray-100'">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                    </svg>
                                </button>

                                <!-- Snap to Grid Toggle -->
                                <button type="button" @click="snapToGrid = !snapToGrid" class="p-2 rounded" :class="snapToGrid ? 'bg-bass-red/10 text-bass-red' : 'bg-gray-100'">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                    </svg>
                                </button>

                                <!-- Preview Button -->
                                <button type="button" @click="openPreview" aria-label="Pratinjau template" title="Pratinjau template" class="inline-flex items-center gap-2 rounded bg-success-soft p-2 text-success hover:bg-green-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    <span class="hidden sm:inline">Pratinjau</span>
                                </button>

                                <div class="border-l border-gray-200 pl-4 flex space-x-2">
                                    <a href="{{ route('admin.certificate-templates.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900 border border-gray-300 rounded-md">Batal</a>
                                    <button type="submit" class="px-4 py-2 text-sm text-white bg-bass-red hover:bg-bass-red-hover rounded-md">Perbarui Template</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Properties Panel (moved above canvas) -->
                    <div class="mb-6 h-72 overflow-hidden rounded-2xl border border-gray-200 bg-white p-4 shadow-sm md:h-64 lg:h-52">
                        <h3 class="font-semibold text-gray-900 mb-3">Properti Elemen</h3>

                        <div x-show="!selectedElement" class="flex h-[calc(100%-2rem)] items-center justify-center text-sm text-gray-400">
                            Pilih elemen pada canvas untuk mengubah propertinya.
                        </div>

                        <div x-show="selectedElement" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                            <!-- Position -->
                            <div>
                                <label class="text-xs font-medium text-gray-600">X Position</label>
                                <input type="number" x-model.number="selectedElement.x" @change="normalizeSelectedRect" min="0" class="w-full text-sm border border-gray-300 rounded px-2 py-1">
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-600">Y Position</label>
                                <input type="number" x-model.number="selectedElement.y" @change="normalizeSelectedRect" min="0" class="w-full text-sm border border-gray-300 rounded px-2 py-1">
                            </div>

                            <!-- Size -->
                            <div>
                                <label class="text-xs font-medium text-gray-600">Width</label>
                                <input type="number" x-model.number="selectedElement.width" @change="normalizeSelectedRect" min="20" class="w-full text-sm border border-gray-300 rounded px-2 py-1">
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-600">Height</label>
                                <input type="number" x-model.number="selectedElement.height" @change="normalizeSelectedRect" min="20" class="w-full text-sm border border-gray-300 rounded px-2 py-1">
                            </div>

                            <!-- Typography -->
                            <div>
                                <label class="text-xs font-medium text-gray-600">Font Size</label>
                                <div class="flex items-center space-x-2">
                                    <input type="range" x-model.number="selectedElement.fontSize" min="8" max="100" class="flex-1">
                                    <span class="text-xs text-gray-500 w-12" x-text="selectedElement.fontSize + 'px'"></span>
                                </div>
                            </div>

                            <div>
                                <label class="text-xs font-medium text-gray-600">Font Family</label>
                                <select x-model="selectedElement.fontFamily" class="w-full text-sm border border-gray-300 rounded px-2 py-1">
                                    @foreach(config('certificate.editor_fonts') as $fontFamily => $fontLabel)
                                        <option value="{{ $fontFamily }}" style="font-family: '{{ $fontFamily }}'">{{ $fontLabel }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="text-xs font-medium text-gray-600">Text Color</label>
                                <input type="color" x-model="selectedElement.color" class="w-full h-8 border border-gray-300 rounded">
                            </div>

                            <!-- Text Style -->
                            <div>
                                <label class="text-xs font-medium text-gray-600 mb-1 block">Text Style</label>
                                <div class="flex space-x-2">
                                    <label class="flex items-center">
                                        <input type="checkbox" x-model="selectedElement.isBold" class="mr-1">
                                        <span class="text-xs">B</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" x-model="selectedElement.isItalic" class="mr-1">
                                        <span class="text-xs">I</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" x-model="selectedElement.isUnderline" class="mr-1">
                                        <span class="text-xs">U</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Text Alignment -->
                            <div>
                                <label class="text-xs font-medium text-gray-600 mb-1 block">Text Alignment</label>
                                <div class="grid grid-cols-3 gap-1">
                                    <button type="button"
                                            @click="selectedElement.textAlign = 'left'"
                                            class="flex items-center justify-center p-1 border rounded text-xs transition-all duration-200"
                                             :class="selectedElement.textAlign === 'left' || !selectedElement.textAlign ? 'bg-bass-red/10 border-bass-red/20 text-bass-red' : 'border-gray-300 hover:border-gray-400'">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16"></path>
                                        </svg>
                                    </button>
                                    <button type="button"
                                            @click="selectedElement.textAlign = 'center'"
                                            class="flex items-center justify-center p-1 border rounded text-xs transition-all duration-200"
                                             :class="selectedElement.textAlign === 'center' ? 'bg-bass-red/10 border-bass-red/20 text-bass-red' : 'border-gray-300 hover:border-gray-400'">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M8 12h8M6 18h12"></path>
                                        </svg>
                                    </button>
                                    <button type="button"
                                            @click="selectedElement.textAlign = 'right'"
                                            class="flex items-center justify-center p-1 border rounded text-xs transition-all duration-200"
                                             :class="selectedElement.textAlign === 'right' ? 'bg-bass-red/10 border-bass-red/20 text-bass-red' : 'border-gray-300 hover:border-gray-400'">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M12 12h8M4 18h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Layer Controls -->
                            <div>
                                <label class="text-xs font-medium text-gray-600 mb-1 block">Lapisan</label>
                                <div class="grid grid-cols-2 gap-1">
                                    <button type="button" @click="bringToFront" class="p-1 text-xs bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded">Depan</button>
                                    <button type="button" @click="sendToBack" class="p-1 text-xs bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded">Belakang</button>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div>
                                <label class="text-xs font-medium text-gray-600 mb-1 block">Aksi</label>
                                <div class="grid grid-cols-2 gap-1">
                                    <button type="button" @click="duplicateElement" class="p-1 text-xs bg-bass-red text-white rounded hover:bg-bass-red-hover">Salin</button>
                                    <button type="button" @click="removeElement" class="p-1 text-xs bg-bass-red text-white rounded hover:bg-bass-red-hover">Hapus</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Background Controls -->
                    <div class="bg-white shadow-sm rounded-2xl border border-gray-200 p-4 mb-6">
                        <h3 class="font-semibold text-gray-900 mb-3">Pengaturan Latar Belakang - Halaman <span x-text="activePageIndex + 1"></span></h3>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Current Background -->
                            <div>
                                <label class="text-xs font-medium text-gray-600 mb-1 block">Latar Belakang Saat Ini</label>
                                <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center">
                                    <template x-if="pages[activePageIndex]?.backgroundUrl">
                                        <div>
                                            <img :src="pages[activePageIndex].backgroundUrl" class="w-full h-24 object-cover rounded mb-2">
                                            <button type="button" @click="removeBackground()" class="text-xs text-error hover:text-error">Hapus Latar Belakang</button>
                                        </div>
                                    </template>
                                    <template x-if="!pages[activePageIndex]?.backgroundUrl">
                                        <div class="text-gray-500">
                                            <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            <p class="text-xs">No background</p>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- Upload New Background -->
                            <div>
                                <label class="text-xs font-medium text-gray-600 mb-1 block">Unggah Latar Baru</label>
                                <label :for="'bg_upload_' + activePageIndex" class="cursor-pointer border-2 border-dashed border-gray-300 rounded-lg p-4 hover:border-gray-400 block text-center">
                                    <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                    <p class="text-xs text-gray-600">Klik untuk mengunggah</p>
                                    <p class="text-xs text-gray-400 mt-1">PNG, JPG, GIF (Max 5MB)</p>
                                </label>
                                <input :id="'bg_upload_' + activePageIndex" type="file" @change="changeBackground($event)" accept=".jpg,.jpeg,.png,.gif,.webp" class="hidden">
                            </div>

                            <!-- Background Settings -->
                            <div x-show="pages[activePageIndex]?.backgroundUrl">
                                <label class="text-xs font-medium text-gray-600 mb-1 block">Ukuran Latar Belakang</label>
                                <select x-model="pages[activePageIndex].backgroundSize" class="w-full text-sm border border-gray-300 rounded px-2 py-1 mb-2">
                                    <option value="cover">Cover (Fill)</option>
                                    <option value="contain">Contain (Fit)</option>
                                    <option value="100% 100%">Stretch</option>
                                    <option value="auto">Original Size</option>
                                </select>

                                <label class="text-xs font-medium text-gray-600 mb-1 block">Posisi Latar Belakang</label>
                                <select x-model="pages[activePageIndex].backgroundPosition" class="w-full text-sm border border-gray-300 rounded px-2 py-1">
                                    <option value="center">Center</option>
                                    <option value="top">Top</option>
                                    <option value="bottom">Bottom</option>
                                    <option value="left">Left</option>
                                    <option value="right">Right</option>
                                    <option value="top left">Top Left</option>
                                    <option value="top right">Top Right</option>
                                    <option value="bottom left">Bottom Left</option>
                                    <option value="bottom right">Bottom Right</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-6 xl:flex-row">
                        <!-- Left Sidebar -->
                        <div class="w-full space-y-6 xl:w-80 xl:shrink-0">
                            <!-- Page Navigation -->
                            <div class="bg-white shadow-sm rounded-2xl border border-gray-200 p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="font-semibold text-gray-900">Halaman</h3>
                                    <button type="button" @click="addPage" class="text-xs px-3 py-1 bg-bass-red text-white rounded-md hover:bg-bass-red-hover">+ Tambah</button>
                                </div>
                                <div class="space-y-2">
                                    <template x-for="(page, index) in pages" :key="page.id">
                                        <div class="flex items-center justify-between p-2 rounded border" :class="activePageIndex === index ? 'border-bass-red bg-bass-red/5' : 'border-gray-200'">
                                            <button type="button" @click="setActivePage(index)" class="flex items-center space-x-2 flex-1">
                                                <div class="w-8 h-6 bg-gray-200 rounded border flex items-center justify-center">
                                                    <span x-text="index + 1" class="text-xs"></span>
                                                </div>
                                                <span x-text="'Halaman ' + (index + 1)" class="text-sm"></span>
                                            </button>
                                            <button type="button" x-show="pages.length > 1" @click="removePage(index)" class="text-error hover:text-error">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- Elements Toolbox -->
                            <div class="bg-white shadow-sm rounded-2xl border border-gray-200 p-4">
                                <h3 class="font-semibold text-gray-900 mb-3">Elemen</h3>

                                <!-- Text Elements -->
                                <div class="mb-4">
                                    <h4 class="text-sm font-medium text-gray-700 mb-2">Teks Dinamis</h4>
                                    <div class="grid grid-cols-1 gap-2">
                                        <button type="button" @click="addElement('@{{name}}')" class="text-left p-2 text-sm bg-bass-red/5 hover:bg-bass-red/10 border border-bass-red/20 rounded">Nama Peserta</button>
                                        <button type="button" @click="addElement('@{{course}}')" class="text-left p-2 text-sm bg-bass-red/5 hover:bg-bass-red/10 border border-bass-red/20 rounded">Nama Kursus</button>
                                        <button type="button" @click="addElement('@{{date}}')" class="text-left p-2 text-sm bg-bass-red/5 hover:bg-bass-red/10 border border-bass-red/20 rounded">Tanggal Selesai</button>
                                        <button type="button" @click="addElement('@{{training_date_range}}')" class="text-left p-2 text-sm bg-bass-red/5 hover:bg-bass-red/10 border border-bass-red/20 rounded">Rentang Tanggal Pelatihan</button>
                                        <button type="button" @click="addElement('@{{score}}')" class="text-left p-2 text-sm bg-bass-red/5 hover:bg-bass-red/10 border border-bass-red/20 rounded">Nilai Akhir</button>
                                        <button type="button" @click="addElement('@{{certificate_code}}')" class="text-left p-2 text-sm bg-bass-red/5 hover:bg-bass-red/10 border border-bass-red/20 rounded">Kode Sertifikat</button>
                                        <button type="button" @click="addElement('@{{course_summary}}')" class="text-left p-2 text-sm bg-bass-red/5 hover:bg-bass-red/10 border border-bass-red/20 rounded">Rangkuman Materi</button>
                                    </div>
                                </div>

                                <!-- Static Text -->
                                <div class="mb-4">
                                    <h4 class="text-sm font-medium text-gray-700 mb-2">Teks Statis</h4>
                                    <div class="flex">
                                        <input type="text" x-model="customText" placeholder="Masukkan teks kustom..." class="flex-1 text-sm border border-gray-300 rounded-l px-2 py-1">
                                        <button type="button" @click="addCustomText" class="px-3 py-1 bg-success text-white text-sm rounded-r">Tambah</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Main Canvas Area -->
                        <div class="min-w-0 flex-1">
                            <div class="bg-white rounded-lg shadow-sm p-6" :style="{ minHeight: 'calc(100vh - 200px)' }">

                                <!-- Canvas Container -->
                                <div x-ref="canvasViewport" class="relative overflow-hidden border border-gray-300 rounded-lg landscape-canvas p-4"
                                     :style="{ height: (794 * zoom + 32) + 'px', minWidth: '100%' }">
                                    <div class="relative mx-auto bg-gray-100 canvas-wrapper"
                                         :style="{
                                             width: (1123 * zoom) + 'px',
                                             height: (794 * zoom) + 'px'
                                          }">
                                        <div x-ref="canvasContainer"
                                             class="absolute left-0 top-0 bg-white"
                                             :style="{ width: '1123px', height: '794px', transform: `scale(${zoom})`, transformOrigin: 'top left' }"
                                         @click="deselectElement">

                                        <!-- Grid overlay -->
                                        <div x-show="showGrid"
                                             class="absolute inset-0 pointer-events-none"
                                             :style="{
                                                 backgroundImage: `
                                                     linear-gradient(to right, rgba(0,0,0,0.1) 1px, transparent 1px),
                                                     linear-gradient(to bottom, rgba(0,0,0,0.1) 1px, transparent 1px)
                                                 `,
                                                 backgroundSize: '20px 20px'
                                             }"></div>

                                        <!-- Background Image -->
                                        <template x-if="pages[activePageIndex] && pages[activePageIndex].backgroundUrl">
                                            <img :src="pages[activePageIndex].backgroundUrl"
                                                 class="absolute inset-0 w-full h-full pointer-events-none"
                                                 :style="{
                                                     objectFit: pages[activePageIndex].backgroundSize === 'cover' ? 'cover' :
                                                               pages[activePageIndex].backgroundSize === 'contain' ? 'contain' :
                                                               pages[activePageIndex].backgroundSize === '100% 100%' ? 'fill' : 'none',
                                                     objectPosition: pages[activePageIndex].backgroundPosition || 'center'
                                                 }">
                                        </template>

                                        <!-- Background Upload Area -->
                                        <div x-show="!pages[activePageIndex]?.backgroundUrl"
                                             class="absolute inset-0 flex items-center justify-center">
                                             <label :for="'background_image_' + pages[activePageIndex]?.id"
                                                   class="cursor-pointer bg-white p-8 rounded-lg shadow-lg border-2 border-dashed border-gray-300 hover:border-gray-400 text-center">
                                                <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                                </svg>
                                                <h4 class="text-lg font-semibold text-gray-700 mb-2">Unggah Latar Belakang</h4>
                                                <p class="text-gray-500">Klik untuk mengganti latar belakang Halaman <span x-text="activePageIndex + 1"></span></p>
                                                <p class="text-sm text-gray-400 mt-2">Disarankan: 1123×794px (A4 Landscape)</p>
                                            </label>
                                        </div>

                                        <!-- Elements -->
                                        <template x-for="(element, elementIndex) in (pages[activePageIndex]?.elements || [])" :key="element.id">
                                             <div class="enhanced-editor-element resizable-draggable absolute cursor-move select-none element-container"
                                                  :data-page-id="pages[activePageIndex].id"
                                                  :data-element-id="element.id"
                                                  @click.stop="selectElement(element.id)"
                                                 :class="{
                                                      'element-selected': selectedElementId === element.id,
                                                      'element-hover': selectedElementId !== element.id,
                                                     'text-align-left': element.textAlign === 'left' || !element.textAlign,
                                                     'text-align-center': element.textAlign === 'center',
                                                     'text-align-right': element.textAlign === 'right'
                                                 }"
                                                 :style="{
                                                     left: element.x + 'px',
                                                     top: element.y + 'px',
                                                     width: element.width + 'px',
                                                     height: element.height + 'px',
                                                     zIndex: element.zIndex || 10
                                                 }">

                                                <!-- Text Content -->
                                                <div x-text="element.content"
                                                     class="w-full h-full overflow-hidden p-2 element-text"
                                                     :style="{
                                                         fontSize: element.fontSize + 'px',
                                                         color: element.color,
                                                         fontFamily: element.fontFamily || 'Arial',
                                                         fontWeight: element.isBold ? 'bold' : 'normal',
                                                         fontStyle: element.isItalic ? 'italic' : 'normal',
                                                         textDecoration: element.isUnderline ? 'underline' : 'none',
                                                         textAlign: element.textAlign || 'left',
                                                         lineHeight: '1.4',
                                                         display: 'flex',
                                                         alignItems: 'center'
                                                     }"></div>

                                                <!-- Resize Handles -->
                                                 <template x-if="selectedElementId === element.id">
                                                    <div class="resize-handles">
                                                        <!-- Corner handles -->
                                                        <div class="resize-handle resize-handle-nw"></div>
                                                        <div class="resize-handle resize-handle-ne"></div>
                                                        <div class="resize-handle resize-handle-sw"></div>
                                                        <div class="resize-handle resize-handle-se"></div>

                                                        <!-- Edge handles -->
                                                        <div class="resize-handle resize-handle-n"></div>
                                                        <div class="resize-handle resize-handle-s"></div>
                                                        <div class="resize-handle resize-handle-w"></div>
                                                        <div class="resize-handle resize-handle-e"></div>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                        </div>
                                    </div>
                                </div>

                                <!-- Status Bar -->
                                <div class="mt-4 flex justify-between items-center text-sm text-gray-600">
                                    <div class="flex items-center space-x-4">
                                        <span>Halaman <span x-text="activePageIndex + 1"></span> dari <span x-text="pages.length"></span></span>
                                        <span x-show="selectedElement">Dipilih: <span x-text="selectedElement.content"></span></span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span x-text="Math.round(zoom * 100) + '% zoom'"></span>
                                        <span x-show="snapToGrid" class="text-success">• Snapping ke Grid</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Hidden inputs for form submission -->
                    <input type="hidden" name="layout_data" :value="JSON.stringify(getSanitizedPages())">
                    <div class="hidden">
                        <template x-for="page in pages" :key="page.id">
                            <input :id="'background_image_' + page.id" @change="handleBackgroundUpload($event, page.id)" type="file" accept=".jpg,.jpeg,.png,.gif,.webp">
                        </template>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="{{ config('certificate.google_fonts_url') }}" rel="stylesheet">
    @endpush

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>
    <script>
        window.enhancedCertificateInitialPages = @json($certificateTemplate->layout_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
        window.enhancedCertificateStorageUrl = @json(Storage::url(''));
        window.enhancedCertificateFontStylesheetUrl = @json(config('certificate.google_fonts_url'));
    </script>

    <style>
    /* Element Container Styling */
    .element-container {
        border: 1px solid transparent;
        border-radius: 2px;
        touch-action: none;
    }

    .element-hover:hover {
        border-color: #94a3b8;
        background-color: rgba(148, 163, 184, 0.05);
    }

    .element-selected {
        border-color: #B91818 !important;
        background-color: rgba(185, 24, 24, 0.05) !important;
        box-shadow: 0 0 0 1px #B91818;
    }

    .element-text {
        pointer-events: none;
        word-break: break-word;
    }

    /* Make sure the element container is draggable but text is not */
    .element-container {
        pointer-events: auto;
    }

    .element-container .element-text {
        pointer-events: none;
        user-select: none;
    }

    /* Resize Handles */
    .resize-handles {
        position: absolute;
        top: -4px;
        left: -4px;
        right: -4px;
        bottom: -4px;
        pointer-events: none;
        z-index: 1001;
    }

    .resize-handle {
        position: absolute;
        background: #B91818;
        border: 2px solid white;
        border-radius: 3px;
        pointer-events: auto;
        z-index: 1002;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        transition: all 0.1s ease;
    }

    .resize-handle:hover {
        background: #9A1414;
        transform: scale(1.1);
    }

    /* Corner handles */
    .resize-handle-nw {
        top: -6px;
        left: -6px;
        width: 12px;
        height: 12px;
        cursor: nw-resize;
    }

    .resize-handle-ne {
        top: -6px;
        right: -6px;
        width: 12px;
        height: 12px;
        cursor: ne-resize;
    }

    .resize-handle-sw {
        bottom: -6px;
        left: -6px;
        width: 12px;
        height: 12px;
        cursor: sw-resize;
    }

    .resize-handle-se {
        bottom: -6px;
        right: -6px;
        width: 12px;
        height: 12px;
        cursor: se-resize;
    }

    /* Edge handles */
    .resize-handle-n {
        top: -6px;
        left: 50%;
        transform: translateX(-50%);
        width: 12px;
        height: 12px;
        cursor: n-resize;
    }

    .resize-handle-s {
        bottom: -6px;
        left: 50%;
        transform: translateX(-50%);
        width: 12px;
        height: 12px;
        cursor: s-resize;
    }

    .resize-handle-w {
        top: 50%;
        left: -6px;
        transform: translateY(-50%);
        width: 12px;
        height: 12px;
        cursor: w-resize;
    }

    .resize-handle-e {
        top: 50%;
        right: -6px;
        transform: translateY(-50%);
        width: 12px;
        height: 12px;
        cursor: e-resize;
    }

    /* Text alignment visual helpers */
    .text-align-left .element-text {
        justify-content: flex-start;
        text-align: left;
    }

    .text-align-center .element-text {
        justify-content: center;
        text-align: center;
    }

    .text-align-right .element-text {
        justify-content: flex-end;
        text-align: right;
    }

    /* Custom scrollbar */
    .overflow-auto::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    .overflow-auto::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    .overflow-auto::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 4px;
    }

    .overflow-auto::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }

    /* Canvas styling for landscape */
    .landscape-canvas {
        min-width: 100%;
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    }

    /* Canvas wrapper styling */
    .canvas-wrapper {
        overflow: visible;
    }
    </style>
    @endpush
</x-app-layout>
