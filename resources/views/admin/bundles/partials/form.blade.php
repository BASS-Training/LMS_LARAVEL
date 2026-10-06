@php
    $initialCourses = $selectedCourses->map(fn ($course) => [
        'id' => (int) $course->id,
        'title' => $course->title,
        'price' => (int) $course->price,
        'priceLabel' => 'Rp '.number_format($course->price, 0, ',', '.'),
    ])->values();
@endphp
<div class="space-y-6">
    <div class="grid gap-5 sm:grid-cols-2">
        <div class="sm:col-span-2"><label class="block text-sm font-semibold text-gray-700">Judul</label><input name="title" value="{{ old('title', $bundle->title) }}" required class="mt-1.5 w-full rounded-lg border-gray-300 focus:border-bass-red focus:ring-bass-red">@error('title')<p class="mt-1 text-sm text-error">{{ $message }}</p>@enderror</div>
        <div><label class="block text-sm font-semibold text-gray-700">Slug</label><input name="slug" value="{{ old('slug', $bundle->slug) }}" class="mt-1.5 w-full rounded-lg border-gray-300 focus:border-bass-red focus:ring-bass-red" placeholder="otomatis-dari-judul">@error('slug')<p class="mt-1 text-sm text-error">{{ $message }}</p>@enderror</div>
        <div><label class="block text-sm font-semibold text-gray-700">Harga Bundle</label><input type="number" min="1" name="price" value="{{ old('price', $bundle->price) }}" required class="mt-1.5 w-full rounded-lg border-gray-300 focus:border-bass-red focus:ring-bass-red">@error('price')<p class="mt-1 text-sm text-error">{{ $message }}</p>@enderror</div>
        <div class="sm:col-span-2"><label class="block text-sm font-semibold text-gray-700">Deskripsi</label><textarea name="description" rows="4" class="mt-1.5 w-full rounded-lg border-gray-300 focus:border-bass-red focus:ring-bass-red">{{ old('description', $bundle->description) }}</textarea>@error('description')<p class="mt-1 text-sm text-error">{{ $message }}</p>@enderror</div>
    </div>

    <fieldset
        x-data="bundleCoursePicker()"
        x-init="loadCourses()"
        class="space-y-3"
    >
        <div>
            <legend class="text-sm font-semibold text-gray-700">Pilih Course</legend>
            <p class="mt-1 text-xs text-gray-500">Cari dan pilih minimal dua course. Urutan di panel course terpilih menjadi urutan bundle.</p>
            @error('course_ids')<p class="mt-2 text-sm text-error">{{ $message }}</p>@enderror
            @error('course_ids.*')<p class="mt-2 text-sm text-error">{{ $message }}</p>@enderror
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white" aria-labelledby="available-course-heading">
                <div class="border-b border-gray-200 bg-gray-50 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <h3 id="available-course-heading" class="text-sm font-semibold text-gray-900">Cari Course</h3>
                        <span class="text-xs text-gray-500" x-text="meta.total + ' ditemukan'"></span>
                    </div>
                    <label class="relative mt-3 block">
                        <span class="sr-only">Cari berdasarkan judul course</span>
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m2.1-5.4a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z" /></svg>
                        <input
                            type="search"
                            x-model="query"
                            @input.debounce.350ms="loadCourses(1)"
                            class="w-full rounded-lg border-gray-300 py-2.5 pl-9 pr-3 text-sm focus:border-bass-red focus:ring-bass-red"
                            placeholder="Ketik judul course..."
                        >
                    </label>
                </div>

                <div class="relative min-h-72">
                    <div x-show="loading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/80" aria-live="polite">
                        <span class="text-sm font-medium text-gray-500">Memuat course...</span>
                    </div>
                    <div x-show="errorMessage" x-cloak class="m-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700" x-text="errorMessage"></div>
                    <div x-show="!loading && courses.length === 0 && !errorMessage" x-cloak class="flex min-h-72 items-center justify-center p-6 text-center text-sm text-gray-500">
                        Tidak ada course yang cocok.
                    </div>
                    <ul x-show="courses.length > 0" class="divide-y divide-gray-100">
                        <template x-for="course in courses" :key="course.id">
                            <li class="flex items-center justify-between gap-3 p-4">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-gray-900" x-text="course.title"></p>
                                    <p class="mt-0.5 text-xs text-gray-500" x-text="course.priceLabel"></p>
                                </div>
                                <button
                                    type="button"
                                    @click="addCourse(course)"
                                    :disabled="isSelected(course.id)"
                                    class="shrink-0 rounded-lg border px-3 py-1.5 text-xs font-semibold transition disabled:cursor-not-allowed disabled:border-gray-200 disabled:bg-gray-100 disabled:text-gray-400"
                                    :class="isSelected(course.id) ? '' : 'border-bass-red text-bass-red hover:bg-red-50'"
                                    x-text="isSelected(course.id) ? 'Terpilih' : 'Tambah'"
                                ></button>
                            </li>
                        </template>
                    </ul>
                </div>

                <div x-show="meta.lastPage > 1" x-cloak class="flex items-center justify-between border-t border-gray-200 bg-gray-50 px-4 py-3">
                    <button type="button" @click="loadCourses(meta.currentPage - 1)" :disabled="loading || meta.currentPage === 1" class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 disabled:opacity-40">Sebelumnya</button>
                    <span class="text-xs text-gray-500" x-text="'Halaman ' + meta.currentPage + ' dari ' + meta.lastPage"></span>
                    <button type="button" @click="loadCourses(meta.currentPage + 1)" :disabled="loading || meta.currentPage === meta.lastPage" class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 disabled:opacity-40">Berikutnya</button>
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white" aria-labelledby="selected-course-heading">
                <div class="flex items-center justify-between gap-3 border-b border-gray-200 bg-gray-50 p-4">
                    <div>
                        <h3 id="selected-course-heading" class="text-sm font-semibold text-gray-900">Course Terpilih</h3>
                        <p class="mt-0.5 text-xs text-gray-500" x-text="selected.length + ' course · Total ' + formatPrice(totalPrice)"></p>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="selected.length >= 2 ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'" x-text="selected.length >= 2 ? 'Siap' : 'Minimal 2'"></span>
                </div>

                <div x-show="selected.length === 0" x-cloak class="flex min-h-72 items-center justify-center p-6 text-center">
                    <div>
                        <p class="text-sm font-semibold text-gray-700">Belum ada course dipilih</p>
                        <p class="mt-1 text-xs text-gray-500">Gunakan pencarian di sebelah kiri untuk menambahkan course.</p>
                    </div>
                </div>
                <ol x-show="selected.length > 0" x-cloak class="max-h-[32rem] divide-y divide-gray-100 overflow-y-auto">
                    <template x-for="(course, index) in selected" :key="course.id">
                        <li class="flex items-center gap-3 p-4">
                            <input type="hidden" name="course_ids[]" :value="course.id">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-bold text-gray-600" x-text="index + 1"></span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-gray-900" x-text="course.title"></p>
                                <p class="mt-0.5 text-xs text-gray-500" x-text="course.priceLabel"></p>
                            </div>
                            <div class="flex shrink-0 items-center gap-1">
                                <button type="button" @click="moveCourse(index, -1)" :disabled="index === 0" class="rounded-md p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 disabled:cursor-not-allowed disabled:opacity-30" aria-label="Naikkan urutan course">↑</button>
                                <button type="button" @click="moveCourse(index, 1)" :disabled="index === selected.length - 1" class="rounded-md p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 disabled:cursor-not-allowed disabled:opacity-30" aria-label="Turunkan urutan course">↓</button>
                                <button type="button" @click="removeCourse(index)" class="rounded-md p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600" aria-label="Hapus course dari bundle">✕</button>
                            </div>
                        </li>
                    </template>
                </ol>
            </section>
        </div>
    </fieldset>

    <div class="grid gap-3 sm:grid-cols-2"><input type="hidden" name="is_active" value="0"><label class="flex items-center gap-3 rounded-xl border border-gray-200 p-4"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $bundle->is_active)) class="rounded border-gray-300 text-bass-red focus:ring-bass-red"><span><strong class="block text-sm text-gray-900">Aktif di katalog</strong><span class="text-xs text-gray-500">Bundle dapat dilihat dan dibeli.</span></span></label><input type="hidden" name="requires_payment_verification" value="0"><label class="flex items-center gap-3 rounded-xl border border-gray-200 p-4"><input type="checkbox" name="requires_payment_verification" value="1" @checked(old('requires_payment_verification', $bundle->requires_payment_verification)) class="rounded border-gray-300 text-bass-red focus:ring-bass-red"><span><strong class="block text-sm text-gray-900">Verifikasi manual</strong><span class="text-xs text-gray-500">Akses menunggu persetujuan admin setelah bayar.</span></span></label></div>

    <div class="flex justify-end gap-3"><a href="{{ route('admin.bundles.index') }}" class="rounded-lg border border-gray-300 px-5 py-2.5 font-semibold text-gray-700 hover:bg-gray-50">Batal</a><button class="rounded-lg bg-bass-red px-5 py-2.5 font-semibold text-white hover:bg-bass-red-hover">Simpan Bundle</button></div>
</div>

@push('scripts')
    <script>
        function bundleCoursePicker() {
            return {
                endpoint: @js(route('admin.bundles.course-options')),
                selected: @js($initialCourses),
                courses: [],
                query: '',
                loading: false,
                errorMessage: '',
                requestNumber: 0,
                meta: { currentPage: 1, lastPage: 1, total: 0 },

                get totalPrice() {
                    return this.selected.reduce((total, course) => total + Number(course.price), 0);
                },

                async loadCourses(page = 1) {
                    const requestNumber = ++this.requestNumber;
                    this.loading = true;
                    this.errorMessage = '';

                    try {
                        const url = new URL(this.endpoint, window.location.origin);
                        url.searchParams.set('page', page);
                        if (this.query.trim()) url.searchParams.set('q', this.query.trim());

                        const response = await fetch(url, { headers: { Accept: 'application/json' } });
                        if (!response.ok) throw new Error('Course gagal dimuat.');

                        const result = await response.json();
                        if (requestNumber !== this.requestNumber) return;
                        this.courses = result.data;
                        this.meta = result.meta;
                    } catch (error) {
                        if (requestNumber !== this.requestNumber) return;
                        this.courses = [];
                        this.meta = { currentPage: 1, lastPage: 1, total: 0 };
                        this.errorMessage = 'Course gagal dimuat. Silakan coba lagi.';
                    } finally {
                        if (requestNumber === this.requestNumber) this.loading = false;
                    }
                },

                isSelected(id) {
                    return this.selected.some(course => Number(course.id) === Number(id));
                },

                addCourse(course) {
                    if (!this.isSelected(course.id)) this.selected.push({ ...course });
                },

                removeCourse(index) {
                    this.selected.splice(index, 1);
                },

                moveCourse(index, direction) {
                    const target = index + direction;
                    if (target < 0 || target >= this.selected.length) return;
                    const [course] = this.selected.splice(index, 1);
                    this.selected.splice(target, 0, course);
                },

                formatPrice(value) {
                    return new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        maximumFractionDigits: 0,
                    }).format(value);
                },
            };
        }
    </script>
@endpush
