<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Buat Template Sertifikat Baru - Editor Lanjutan') }}
            </h2>
            <div class="flex space-x-2">
                <button id="preview-btn" class="px-4 py-2 bg-bass-red text-white rounded-xl hover:bg-bass-red-hover transition">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>Pratinjau
                </button>
                <button id="save-btn" class="px-4 py-2 bg-success text-white rounded-xl hover:bg-success transition">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>Simpan Template
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-full mx-auto px-4">
            <div id="certificate-editor" class="flex flex-col lg:flex-row gap-6 h-screen">

                <!-- Left Sidebar: Tools & Properties -->
                <div class="lg:w-80 bg-white rounded-lg shadow-lg overflow-hidden flex flex-col">
                    <!-- Tabs -->
                    <div class="flex border-b">
                        <button class="tab-btn active flex-1 px-3 py-3 text-sm font-medium" data-tab="tools">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.42 15.17l-5.1-5.1m0 0L3.34 8.09a2 2 0 010-2.83l.59-.59a2 2 0 012.83 0l3.13 3.13m0 0l5.1 5.1m-5.1-5.1l5.1-5.1m0 0L19.59 3.34a2 2 0 012.83 0l.59.59a2 2 0 010 2.83l-5.1 5.1m-5.1 5.1l5.1 5.1m-5.1-5.1l-5.1 5.1"/></svg>Peralatan
                        </button>
                        <button class="tab-btn flex-1 px-3 py-3 text-sm font-medium" data-tab="properties">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>Properti
                        </button>
                        <button class="tab-btn flex-1 px-3 py-3 text-sm font-medium" data-tab="pages">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>Halaman
                        </button>
                        <button class="tab-btn flex-1 px-3 py-3 text-sm font-medium" data-tab="templates">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>Template
                        </button>
                    </div>

                    <!-- Tools Tab -->
                    <div id="tools-tab" class="tab-content flex-1 overflow-y-auto p-4">
                        <div class="space-y-4">
                            <!-- Template Info -->
                            <div class="bg-gray-50 p-3 rounded">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Template Name</label>
                                <input type="text" id="template-name" placeholder="Masukkan nama template..."
                                       class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-bass-red">
                            </div>

                            <!-- Quick Start Templates -->
                            <div>
                                <h3 class="text-sm font-semibold text-gray-700 mb-3">Template Awal Cepat</h3>
                                <div class="space-y-2">
                                    <button class="template-preset w-full text-left p-3 bg-bass-red/5 hover:bg-bass-red/10 rounded border-2 border-bass-red/20 hover:border-bass-red transition"
                                            data-template="classic">
                                        <div class="flex items-center">
                                            <svg class="w-5 h-5 text-bass-red mr-3 text-xl" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                                            <div>
                                                <div class="font-medium text-bass-red">Sertifikat Klasik</div>
                                                <div class="text-xs text-bass-red">Desain tradisional dengan border elegan</div>
                                            </div>
                                        </div>
                                    </button>

                                    <button class="template-preset w-full text-left p-3 bg-success-soft hover:bg-success-soft rounded border-2 border-success/40 hover:border-green-400 transition"
                                            data-template="modern">
                                        <div class="flex items-center">
                                            <svg class="w-5 h-5 text-success mr-3 text-xl" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M18.75 4.236c.982.143 1.954.317 2.916.52A6.003 6.003 0 0016.27 9.728M18.75 4.236V4.5c0 2.108-.966 3.99-2.48 5.228m0 0a6.015 6.015 0 01-1.77.685m0 0c-1.357 0-2.652-.496-3.618-1.383"/></svg>
                                            <div>
                                                <div class="font-medium text-success">Desain Modern</div>
                                                <div class="text-xs text-success">Tata letak bersih dan kontemporer</div>
                                            </div>
                                        </div>
                                    </button>

                                    <button class="template-preset w-full text-left p-3 bg-navy/5 hover:bg-navy/10 rounded border-2 border-navy/20 hover:border-navy transition"
                                            data-template="elegant">
                                        <div class="flex items-center">
                                            <svg class="w-5 h-5 text-navy mr-3 text-xl" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>
                                            <div>
                                                <div class="font-medium text-navy">Gaya Elegan</div>
                                                <div class="text-xs text-navy">Canggih dan profesional</div>
                                            </div>
                                        </div>
                                    </button>

                                    <button class="template-preset w-full text-left p-3 bg-gray-50 hover:bg-gray-100 rounded border-2 border-gray-200 hover:border-gray-400 transition"
                                            data-template="blank">
                                        <div class="flex items-center">
                                            <svg class="w-5 h-5 text-gray-500 mr-3 text-xl" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                            <div>
                                                <div class="font-medium text-gray-700">Template Kosong</div>
                                                <div class="text-xs text-gray-600">Mulai dari awal</div>
                                            </div>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <!-- Element Tools -->
                            <div>
                                <h3 class="text-sm font-semibold text-gray-700 mb-3">Tambah Elemen</h3>
                                <div class="grid grid-cols-2 gap-2">
                                    <button class="tool-btn" data-tool="text" data-content="Sample Text">
                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16"/></svg>
                                        <span>Teks</span>
                                    </button>
                                    <button class="tool-btn" data-tool="text" data-content="@{{name}}">
                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        <span>Nama</span>
                                    </button>
                                    <button class="tool-btn" data-tool="text" data-content="@{{course_title}}">
                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                                        <span>Kursus</span>
                                    </button>
                                    <button class="tool-btn" data-tool="text" data-content="@{{completion_date}}">
                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <span>Tanggal</span>
                                    </button>
                                    <button class="tool-btn" data-tool="text" data-content="@{{training_date_range}}">
                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <span>Pelatihan</span>
                                    </button>
                                    <button class="tool-btn" data-tool="text" data-content="@{{instructor_name}}">
                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6"/></svg>
                                        <span>Instruktur</span>
                                    </button>
                                    <button class="tool-btn" data-tool="image">
                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <span>Gambar</span>
                                    </button>
                                    <button class="tool-btn" data-tool="line">
                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                                        <span>Garis</span>
                                    </button>
                                    <button class="tool-btn" data-tool="shape">
                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                                        <span>Bentuk</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Background Controls -->
                            <div>
                                <h3 class="text-sm font-semibold text-gray-700 mb-3">Latar Belakang</h3>
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Unggah Latar Belakang</label>
                                        <div class="relative">
                                            <input type="file" id="background-upload" accept="image/*" class="hidden">
                                            <button type="button" onclick="document.getElementById('background-upload').click()"
                                                    class="w-full p-3 border-2 border-dashed border-gray-300 rounded text-center hover:border-bass-red transition">
                                                <svg class="w-5 h-5 inline text-gray-400 text-lg mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 15a4.5 4.5 0 004.5 4.5H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15a2.25 2.25 0 012.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/></svg>
                                                <div class="text-xs text-gray-500">Klik untuk mengunggah gambar</div>
                                            </button>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Warna Latar Belakang</label>
                                        <input type="color" id="background-color" value="#ffffff" class="w-full h-10 border rounded cursor-pointer">
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Ukuran Latar Belakang</label>
                                        <select id="background-size" class="w-full px-2 py-1 border rounded text-sm">
                                            <option value="cover">Cover</option>
                                            <option value="contain">Contain</option>
                                            <option value="stretch">Stretch</option>
                                            <option value="repeat">Repeat</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Properties Tab -->
                    <div id="properties-tab" class="tab-content flex-1 overflow-y-auto p-4 hidden">
                        <div id="element-properties" class="space-y-4">
                            <div class="text-center text-gray-500 py-8">
                                <svg class="w-8 h-8 inline text-3xl mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.98-.582M14.083 8.21l.778 2.897M3.658 11.474l-.777 2.898M5.136 16.035l2.98-.582M14.083 15.79l-.778 2.897"/></svg>
                                <p>Pilih elemen untuk mengedit properti</p>
                            </div>
                        </div>
                    </div>

                    <!-- Pages Tab -->
                    <div id="pages-tab" class="tab-content flex-1 overflow-y-auto p-4 hidden">
                        <div class="space-y-4">
                            <button id="add-page-btn" class="w-full p-3 border-2 border-dashed border-gray-300 rounded text-center hover:border-bass-red transition">
                                <svg class="w-5 h-5 inline text-gray-400 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <div class="text-sm text-gray-500">Tambah Halaman Baru</div>
                            </button>
                            <div id="pages-list" class="space-y-2">
                                <!-- Pages will be populated by JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Canvas Area -->
                <div class="flex-1 bg-gray-100 rounded-lg overflow-hidden flex flex-col">
                    <!-- Canvas Toolbar -->
                    <div class="bg-white border-b px-4 py-3 flex justify-between items-center">
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center space-x-2">
                                <button id="zoom-out" class="p-2 hover:bg-gray-100 rounded">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM13.5 10.5h-6"/></svg>
                                </button>
                                <span id="zoom-level" class="text-sm font-medium">100%</span>
                                <button id="zoom-in" class="p-2 hover:bg-gray-100 rounded">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM10.5 7.5v6m3-3h-6"/></svg>
                                </button>
                                <button id="zoom-fit" class="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 rounded">
                                    Paskan ke Layar
                                </button>
                            </div>

                            <div class="flex items-center space-x-2">
                                <button id="grid-toggle" class="p-2 hover:bg-gray-100 rounded" title="Aktifkan/Nonaktifkan Grid">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                                </button>
                                <button id="snap-toggle" class="p-2 hover:bg-gray-100 rounded active" title="Aktifkan/Nonaktifkan Snapping">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5"/></svg>
                                </button>
                                <button id="ruler-toggle" class="p-2 hover:bg-gray-100 rounded" title="Aktifkan/Nonaktifkan Penggaris">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15"/></svg>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center space-x-2">
                            <select id="page-selector" class="px-3 py-1 border rounded text-sm">
                                <option value="0">Halaman 1</option>
                            </select>
                            <button id="undo-btn" class="p-2 hover:bg-gray-100 rounded" title="Urungkan" disabled>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/></svg>
                            </button>
                            <button id="redo-btn" class="p-2 hover:bg-gray-100 rounded" title="Ulangi" disabled>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l6-6m0 0l-6-6m6 6H9a6 6 0 000 12h3"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Canvas Container -->
                    <div class="flex-1 p-6 overflow-auto" id="canvas-container">
                        <div id="canvas-wrapper" class="mx-auto">
                            <div id="certificate-canvas" class="relative bg-white shadow-lg mx-auto"
                                 style="width: 794px; height: 1123px; transform-origin: top center;">
                                <!-- Canvas content will be rendered here -->
                                <div id="grid-overlay" class="absolute inset-0 pointer-events-none opacity-20 hidden">
                                    <!-- Grid lines will be generated by JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Sidebar: Layers & History -->
                <div class="lg:w-64 bg-white rounded-lg shadow-lg overflow-hidden flex flex-col">
                    <div class="flex border-b">
                        <button class="tab-btn active flex-1 px-4 py-3 text-sm font-medium" data-tab="layers">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>Lapisan
                        </button>
                        <button class="tab-btn flex-1 px-4 py-3 text-sm font-medium" data-tab="history">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Riwayat
                        </button>
                    </div>

                    <!-- Layers Tab -->
                    <div id="layers-tab" class="tab-content flex-1 overflow-y-auto p-4">
                        <div id="layers-list" class="space-y-1">
                            <div class="text-center text-gray-500 py-4">
                                <svg class="w-6 h-6 inline text-2xl mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>
                                <p class="text-sm">Belum ada elemen</p>
                            </div>
                        </div>
                    </div>

                    <!-- History Tab -->
                    <div id="history-tab" class="tab-content flex-1 overflow-y-auto p-4 hidden">
                        <div id="history-list" class="space-y-1">
                            <div class="text-center text-gray-500 py-4">
                                <svg class="w-6 h-6 inline text-2xl mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <p class="text-sm">Riwayat akan muncul di sini</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <div id="preview-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-6xl w-full max-h-full overflow-auto">
                <div class="p-4 border-b flex justify-between items-center">
                    <h3 class="text-lg font-semibold">Pratinjau Sertifikat</h3>
                    <button id="close-preview" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-5 h-5 inline text-xl" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-6">
                    <div id="preview-content" class="mx-auto" style="max-width: 794px;">
                        <!-- Preview will be rendered here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Template Selection Modal -->
    <div id="template-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-4xl w-full">
                <div class="p-6 border-b">
                    <h3 class="text-xl font-semibold">Pilih Template Awal</h3>
                    <p class="text-gray-600 mt-1">Pilih template untuk memulai, atau buat dari awal</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Template Options -->
                        <div class="template-option border-2 border-gray-200 rounded-lg p-4 cursor-pointer hover:border-bass-red transition" data-template="classic">
                            <div class="aspect-w-16 aspect-h-9 bg-bass-red/5 rounded mb-3 flex items-center justify-center">
                                <svg class="w-10 h-10 text-bass-red" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                            </div>
                            <h4 class="font-semibold text-gray-800">Sertifikat Klasik</h4>
                            <p class="text-sm text-gray-600">Desain tradisional dengan border elegan dan tata letak formal</p>
                        </div>

                        <div class="template-option border-2 border-gray-200 rounded-lg p-4 cursor-pointer hover:border-green-400 transition" data-template="modern">
                            <div class="aspect-w-16 aspect-h-9 bg-success-soft rounded mb-3 flex items-center justify-center">
                                <svg class="w-10 h-10 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M18.75 4.236c.982.143 1.954.317 2.916.52A6.003 6.003 0 0016.27 9.728M18.75 4.236V4.5c0 2.108-.966 3.99-2.48 5.228m0 0a6.015 6.015 0 01-1.77.685m0 0c-1.357 0-2.652-.496-3.618-1.383"/></svg>
                            </div>
                            <h4 class="font-semibold text-gray-800">Desain Modern</h4>
                            <p class="text-sm text-gray-600">Tata letak bersih dan kontemporer dengan elemen minimal</p>
                        </div>

                        <div class="template-option border-2 border-gray-200 rounded-lg p-4 cursor-pointer hover:border-navy transition" data-template="elegant">
                            <div class="aspect-w-16 aspect-h-9 bg-navy/5 rounded mb-3 flex items-center justify-center">
                                <svg class="w-10 h-10 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>
                            </div>
                            <h4 class="font-semibold text-gray-800">Gaya Elegan</h4>
                            <p class="text-sm text-gray-600">Canggih dan profesional dengan tipografi yang halus</p>
                        </div>

                        <div class="template-option border-2 border-gray-200 rounded-lg p-4 cursor-pointer hover:border-gray-400 transition" data-template="blank">
                            <div class="aspect-w-16 aspect-h-9 bg-gray-50 rounded mb-3 flex items-center justify-center">
                                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            </div>
                            <h4 class="font-semibold text-gray-800">Template Kosong</h4>
                            <p class="text-sm text-gray-600">Mulai dari awal dengan kanvas kosong</p>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <a href="{{ route('admin.certificate-templates.index') }}" class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50">
                            Batal
                        </a>
                        <button id="start-editing" class="px-6 py-2 bg-bass-red text-white rounded-xl hover:bg-bass-red-hover transition" disabled>
                            Mulai Mengedit
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form id="save-form" method="POST" action="{{ route('admin.certificate-templates.store') }}" class="hidden">
        @csrf
        <input type="hidden" name="name" id="save-name">
        <input type="hidden" name="layout_data" id="save-layout-data">
        <input type="file" name="backgrounds[]" id="save-backgrounds" multiple style="display: none;">
    </form>

    @push('styles')
    <link rel="stylesheet" href="{{ asset('css/certificate-templates.css') }}">
    <style>
        .tab-btn.active {
            background-color: #f3f4f6;
            border-bottom: 2px solid #B91818;
            color: #B91818;
        }

        .template-option.selected {
            border-color: #B91818;
            background-color: #fef2f2;
        }

        .canvas-element {
            position: absolute;
            cursor: move;
            user-select: none;
        }

        .canvas-element.selected {
            outline: 2px solid #B91818;
            outline-offset: 2px;
        }

        .canvas-element .resize-handle {
            position: absolute;
            width: 8px;
            height: 8px;
            background: #B91818;
            border: 1px solid white;
            border-radius: 50%;
        }

        .resize-handle.nw { top: -4px; left: -4px; cursor: nw-resize; }
        .resize-handle.ne { top: -4px; right: -4px; cursor: ne-resize; }
        .resize-handle.sw { bottom: -4px; left: -4px; cursor: sw-resize; }
        .resize-handle.se { bottom: -4px; right: -4px; cursor: se-resize; }

        .layer-item {
            @apply flex items-center justify-between p-2 hover:bg-gray-50 rounded cursor-pointer;
        }

        .layer-item.active {
            @apply bg-bass-red/5 border-l-4 border-bass-red;
        }

        #grid-overlay {
            background-image:
                linear-gradient(rgba(0,0,0,0.1) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0,0,0,0.1) 1px, transparent 1px);
            background-size: 20px 20px;
        }
    </style>
    @endpush

    @push('scripts')
    <script src="{{ asset('js/advanced-certificate-editor.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let selectedTemplate = null;

            // Template selection handling
            document.querySelectorAll('.template-option').forEach(option => {
                option.addEventListener('click', function() {
                    // Remove selection from all options
                    document.querySelectorAll('.template-option').forEach(opt => {
                        opt.classList.remove('selected');
                    });

                    // Add selection to clicked option
                    this.classList.add('selected');
                    selectedTemplate = this.dataset.template;

                    // Enable start button
                    document.getElementById('start-editing').disabled = false;
                });
            });

            // Start editing button
            document.getElementById('start-editing').addEventListener('click', function() {
                if (selectedTemplate) {
                    // Hide template selection modal
                    document.getElementById('template-modal').classList.add('hidden');

                    // Initialize editor with selected template
                    if (window.AdvancedCertificateEditor) {
                        const templateData = getTemplateData(selectedTemplate);
                        window.editor = new window.AdvancedCertificateEditor();
                        window.editor.init(templateData);
                    }
                }
            });

            // Template presets in sidebar
            document.querySelectorAll('.template-preset').forEach(preset => {
                preset.addEventListener('click', function() {
                    const template = this.dataset.template;
                    if (window.editor && template) {
                        const templateData = getTemplateData(template);
                        window.editor.loadTemplate(templateData);
                    }
                });
            });

            function getTemplateData(templateType) {
                const templates = {
                    blank: [{
                        id: Date.now(),
                        name: 'Page 1',
                        width: 794,
                        height: 1123,
                        backgroundColor: '#ffffff',
                        backgroundSize: 'cover',
                        elements: []
                    }],
                    classic: [{
                        id: Date.now(),
                        name: 'Page 1',
                        width: 794,
                        height: 1123,
                        backgroundColor: '#f8f9fa',
                        backgroundSize: 'cover',
                        elements: [
                            {
                                id: 1,
                                type: 'text',
                                content: 'CERTIFICATE OF COMPLETION',
                                x: 197,
                                y: 150,
                                width: 400,
                                height: 50,
                                fontSize: 28,
                                fontFamily: 'Times New Roman',
                                color: '#2c3e50',
                                isBold: true,
                                textAlign: 'center',
                                zIndex: 1
                            },
                            {
                                id: 2,
                                type: 'text',
                                content: 'This is to certify that',
                                x: 247,
                                y: 250,
                                width: 300,
                                height: 30,
                                fontSize: 18,
                                fontFamily: 'Times New Roman',
                                color: '#5a6c7d',
                                textAlign: 'center',
                                zIndex: 2
                            },
                            {
                                id: 3,
                                type: 'text',
                                content: '@{{name}}',
                                x: 197,
                                y: 320,
                                width: 400,
                                height: 60,
                                fontSize: 36,
                                fontFamily: 'Times New Roman',
                                color: '#2c3e50',
                                isBold: true,
                                textAlign: 'center',
                                zIndex: 3
                            },
                            {
                                id: 4,
                                type: 'text',
                                content: 'has successfully completed the course',
                                x: 197,
                                y: 420,
                                width: 400,
                                height: 30,
                                fontSize: 18,
                                fontFamily: 'Times New Roman',
                                color: '#5a6c7d',
                                textAlign: 'center',
                                zIndex: 4
                            },
                            {
                                id: 5,
                                type: 'text',
                                content: '@{{course_title}}',
                                x: 197,
                                y: 480,
                                width: 400,
                                height: 40,
                                fontSize: 24,
                                fontFamily: 'Times New Roman',
                                color: '#2c3e50',
                                isBold: true,
                                textAlign: 'center',
                                zIndex: 5
                            },
                            {
                                id: 6,
                                type: 'text',
                                content: 'Date: @{{completion_date}}',
                                x: 100,
                                y: 650,
                                width: 200,
                                height: 30,
                                fontSize: 16,
                                fontFamily: 'Times New Roman',
                                color: '#5a6c7d',
                                textAlign: 'left',
                                zIndex: 6
                            },
                            {
                                id: 7,
                                type: 'text',
                                content: 'Instructor: @{{instructor_name}}',
                                x: 494,
                                y: 650,
                                width: 200,
                                height: 30,
                                fontSize: 16,
                                fontFamily: 'Times New Roman',
                                color: '#5a6c7d',
                                textAlign: 'right',
                                zIndex: 7
                            }
                        ]
                    }],
                    modern: [{
                        id: Date.now(),
                        name: 'Page 1',
                        width: 794,
                        height: 1123,
                        backgroundColor: '#ffffff',
                        backgroundSize: 'cover',
                        elements: [
                            {
                                id: 1,
                                type: 'text',
                                content: 'CERTIFICATE',
                                x: 197,
                                y: 200,
                                width: 400,
                                height: 60,
                                fontSize: 48,
                                fontFamily: 'Helvetica',
                                color: '#3498db',
                                isBold: true,
                                textAlign: 'center',
                                zIndex: 1
                            },
                            {
                                id: 2,
                                type: 'text',
                                content: '@{{name}}',
                                x: 197,
                                y: 350,
                                width: 400,
                                height: 50,
                                fontSize: 32,
                                fontFamily: 'Helvetica',
                                color: '#2c3e50',
                                textAlign: 'center',
                                zIndex: 2
                            },
                            {
                                id: 3,
                                type: 'text',
                                content: 'Successfully completed',
                                x: 197,
                                y: 450,
                                width: 400,
                                height: 30,
                                fontSize: 20,
                                fontFamily: 'Helvetica',
                                color: '#7f8c8d',
                                textAlign: 'center',
                                zIndex: 3
                            },
                            {
                                id: 4,
                                type: 'text',
                                content: '@{{course_title}}',
                                x: 197,
                                y: 520,
                                width: 400,
                                height: 40,
                                fontSize: 28,
                                fontFamily: 'Helvetica',
                                color: '#2c3e50',
                                isBold: true,
                                textAlign: 'center',
                                zIndex: 4
                            },
                            {
                                id: 5,
                                type: 'text',
                                content: '@{{completion_date}}',
                                x: 197,
                                y: 650,
                                width: 400,
                                height: 30,
                                fontSize: 18,
                                fontFamily: 'Helvetica',
                                color: '#95a5a6',
                                textAlign: 'center',
                                zIndex: 5
                            }
                        ]
                    }],
                    elegant: [{
                        id: Date.now(),
                        name: 'Page 1',
                        width: 794,
                        height: 1123,
                        backgroundColor: '#fdfbf7',
                        backgroundSize: 'cover',
                        elements: [
                            {
                                id: 1,
                                type: 'text',
                                content: 'Certificate of Achievement',
                                x: 197,
                                y: 180,
                                width: 400,
                                height: 50,
                                fontSize: 32,
                                fontFamily: 'Georgia',
                                color: '#8b4513',
                                isBold: true,
                                textAlign: 'center',
                                zIndex: 1
                            },
                            {
                                id: 2,
                                type: 'text',
                                content: 'This certifies that',
                                x: 247,
                                y: 280,
                                width: 300,
                                height: 30,
                                fontSize: 20,
                                fontFamily: 'Georgia',
                                color: '#8b4513',
                                isItalic: true,
                                textAlign: 'center',
                                zIndex: 2
                            },
                            {
                                id: 3,
                                type: 'text',
                                content: '@{{name}}',
                                x: 197,
                                y: 350,
                                width: 400,
                                height: 60,
                                fontSize: 40,
                                fontFamily: 'Georgia',
                                color: '#2c3e50',
                                isBold: true,
                                textAlign: 'center',
                                zIndex: 3
                            },
                            {
                                id: 4,
                                type: 'text',
                                content: 'has demonstrated excellence in',
                                x: 197,
                                y: 450,
                                width: 400,
                                height: 30,
                                fontSize: 18,
                                fontFamily: 'Georgia',
                                color: '#8b4513',
                                isItalic: true,
                                textAlign: 'center',
                                zIndex: 4
                            },
                            {
                                id: 5,
                                type: 'text',
                                content: '@{{course_title}}',
                                x: 197,
                                y: 520,
                                width: 400,
                                height: 40,
                                fontSize: 26,
                                fontFamily: 'Georgia',
                                color: '#2c3e50',
                                isBold: true,
                                textAlign: 'center',
                                zIndex: 5
                            },
                            {
                                id: 6,
                                type: 'text',
                                content: 'Awarded this @{{completion_date}}',
                                x: 197,
                                y: 650,
                                width: 400,
                                height: 30,
                                fontSize: 16,
                                fontFamily: 'Georgia',
                                color: '#8b4513',
                                textAlign: 'center',
                                zIndex: 6
                            },
                            {
                                id: 7,
                                type: 'text',
                                content: '@{{instructor_name}}',
                                x: 497,
                                y: 750,
                                width: 200,
                                height: 30,
                                fontSize: 18,
                                fontFamily: 'Georgia',
                                color: '#2c3e50',
                                textAlign: 'center',
                                zIndex: 7
                            },
                            {
                                id: 8,
                                type: 'text',
                                content: 'Instructor',
                                x: 497,
                                y: 780,
                                width: 200,
                                height: 20,
                                fontSize: 14,
                                fontFamily: 'Georgia',
                                color: '#8b4513',
                                textAlign: 'center',
                                zIndex: 8
                            }
                        ]
                    }]
                };

                return templates[templateType] || templates.blank;
            }
        });
    </script>
    @endpush
</x-app-layout>
