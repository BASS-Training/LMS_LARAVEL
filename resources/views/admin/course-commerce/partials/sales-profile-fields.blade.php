@php
    $salesProfile = isset($course) ? $course->salesProfile : null;
    $salesStatusDefault = $salesProfile?->sales_status
        ?? (isset($course) && $course->visibility === 'catalog' ? 'published' : 'draft');
    $salesFaq = array_values(old('sales_profile.faq', $salesProfile?->faq ?? []));
@endphp

<div class="border-b border-gray-200 pb-8" x-data="{
    faq: @js($salesFaq),
    addFaq() { this.faq.push({ question: '', answer: '' }); },
    removeFaq(index) { this.faq.splice(index, 1); }
}">
    <div class="mb-6">
        <h3 class="flex items-center text-lg font-semibold text-gray-900">
            <svg class="mr-2 h-5 w-5 text-bass-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3v18m4-16v14m4-11v8M7 8v8M3 10v4"/>
            </svg>
            Sales Profile
        </h3>
        <p class="mt-1 text-sm text-gray-500">Informasi pemasaran yang ditampilkan pada halaman detail katalog.</p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div>
            <label for="sales_status" class="mb-2 block text-sm font-semibold text-gray-700">Status Sales Profile *</label>
            <select id="sales_status" name="sales_profile[sales_status]" required class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-bass-red">
                <option value="draft" @selected(old('sales_profile.sales_status', $salesStatusDefault) === 'draft')>Draft</option>
                <option value="published" @selected(old('sales_profile.sales_status', $salesStatusDefault) === 'published')>Published</option>
                <option value="hidden" @selected(old('sales_profile.sales_status', $salesStatusDefault) === 'hidden')>Hidden</option>
            </select>
            <p class="mt-1 text-xs text-gray-500">Hanya profile Published yang dapat muncul di katalog publik.</p>
            @error('sales_profile.sales_status')<p class="mt-1 text-sm text-error">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="sales_slug" class="mb-2 block text-sm font-semibold text-gray-700">Slug</label>
            <input id="sales_slug" type="text" name="sales_profile[slug]" maxlength="255" value="{{ old('sales_profile.slug', $salesProfile?->slug) }}" placeholder="Otomatis dari judul course" class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-bass-red">
            <p class="mt-1 text-xs text-gray-500">Gunakan huruf kecil, angka, dan tanda hubung.</p>
            @error('sales_profile.slug')<p class="mt-1 text-sm text-error">{{ $message }}</p>@enderror
        </div>

        <div class="lg:col-span-2">
            <label for="sales_headline" class="mb-2 block text-sm font-semibold text-gray-700">Headline</label>
            <input id="sales_headline" type="text" name="sales_profile[headline]" maxlength="255" value="{{ old('sales_profile.headline', $salesProfile?->headline) }}" placeholder="Contoh: Kuasai teknik dasar bass dalam 30 hari" class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-bass-red">
            @error('sales_profile.headline')<p class="mt-1 text-sm text-error">{{ $message }}</p>@enderror
        </div>

        @foreach ([
            'target_audience' => ['Target Peserta', 'Siapa yang paling cocok mengikuti course ini?'],
            'learning_benefits' => ['Manfaat Pembelajaran', 'Tuliskan manfaat utama, satu manfaat per baris.'],
            'requirements' => ['Persyaratan', 'Tuliskan pengetahuan, alat, atau syarat yang diperlukan.'],
        ] as $field => [$label, $placeholder])
            <div class="{{ $field === 'target_audience' ? 'lg:col-span-2' : '' }}">
                <label for="sales_{{ $field }}" class="mb-2 block text-sm font-semibold text-gray-700">{{ $label }}</label>
                <textarea id="sales_{{ $field }}" name="sales_profile[{{ $field }}]" rows="4" placeholder="{{ $placeholder }}" class="w-full resize-y rounded-lg border border-gray-300 px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-bass-red">{{ old('sales_profile.'.$field, $salesProfile?->{$field}) }}</textarea>
                @error('sales_profile.'.$field)<p class="mt-1 text-sm text-error">{{ $message }}</p>@enderror
            </div>
        @endforeach

        <div>
            <label for="sales_level" class="mb-2 block text-sm font-semibold text-gray-700">Level</label>
            <select id="sales_level" name="sales_profile[level]" class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-bass-red">
                <option value="">Pilih level</option>
                <option value="beginner" @selected(old('sales_profile.level', $salesProfile?->level) === 'beginner')>Pemula</option>
                <option value="intermediate" @selected(old('sales_profile.level', $salesProfile?->level) === 'intermediate')>Menengah</option>
                <option value="advanced" @selected(old('sales_profile.level', $salesProfile?->level) === 'advanced')>Mahir</option>
                <option value="all_levels" @selected(old('sales_profile.level', $salesProfile?->level) === 'all_levels')>Semua Level</option>
            </select>
            @error('sales_profile.level')<p class="mt-1 text-sm text-error">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="sales_language" class="mb-2 block text-sm font-semibold text-gray-700">Bahasa</label>
            <input id="sales_language" type="text" name="sales_profile[language]" maxlength="100" value="{{ old('sales_profile.language', $salesProfile?->language) }}" placeholder="Contoh: Bahasa Indonesia" class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-bass-red">
            @error('sales_profile.language')<p class="mt-1 text-sm text-error">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="sales_duration" class="mb-2 block text-sm font-semibold text-gray-700">Estimasi Durasi (menit)</label>
            <input id="sales_duration" type="number" name="sales_profile[estimated_duration_minutes]" min="1" max="1000000" value="{{ old('sales_profile.estimated_duration_minutes', $salesProfile?->estimated_duration_minutes) }}" placeholder="Contoh: 480" class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-bass-red">
            @error('sales_profile.estimated_duration_minutes')<p class="mt-1 text-sm text-error">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="sales_promo_video" class="mb-2 block text-sm font-semibold text-gray-700">URL Video Promosi</label>
            <input id="sales_promo_video" type="url" name="sales_profile[promo_video_url]" maxlength="2048" value="{{ old('sales_profile.promo_video_url', $salesProfile?->promo_video_url) }}" placeholder="https://..." class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-bass-red">
            @error('sales_profile.promo_video_url')<p class="mt-1 text-sm text-error">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="sales_seo_title" class="mb-2 block text-sm font-semibold text-gray-700">SEO Title</label>
            <input id="sales_seo_title" type="text" name="sales_profile[seo_title]" maxlength="255" value="{{ old('sales_profile.seo_title', $salesProfile?->seo_title) }}" class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-bass-red">
            @error('sales_profile.seo_title')<p class="mt-1 text-sm text-error">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="sales_seo_description" class="mb-2 block text-sm font-semibold text-gray-700">SEO Description</label>
            <textarea id="sales_seo_description" name="sales_profile[seo_description]" maxlength="500" rows="3" class="w-full resize-y rounded-lg border border-gray-300 px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-bass-red">{{ old('sales_profile.seo_description', $salesProfile?->seo_description) }}</textarea>
            @error('sales_profile.seo_description')<p class="mt-1 text-sm text-error">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="mt-8">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h4 class="font-semibold text-gray-900">FAQ</h4>
                <p class="text-xs text-gray-500">Tambahkan maksimal 20 pertanyaan yang sering diajukan.</p>
            </div>
            <button type="button" @click="addFaq()" class="rounded-lg bg-navy px-4 py-2 text-sm font-semibold text-white hover:bg-navy/90">Tambah FAQ</button>
        </div>

        <div class="mt-4 space-y-4">
            <template x-for="(item, index) in faq" :key="index">
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <div class="flex items-start justify-between gap-4">
                        <span class="text-sm font-semibold text-gray-700" x-text="`FAQ ${index + 1}`"></span>
                        <button type="button" @click="removeFaq(index)" class="text-sm font-semibold text-error hover:underline">Hapus</button>
                    </div>
                    <div class="mt-3 space-y-3">
                        <input type="text" :name="`sales_profile[faq][${index}][question]`" x-model="item.question" maxlength="255" placeholder="Pertanyaan" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-transparent focus:ring-2 focus:ring-bass-red">
                        <textarea :name="`sales_profile[faq][${index}][answer]`" x-model="item.answer" maxlength="2000" rows="3" placeholder="Jawaban" class="w-full resize-y rounded-lg border border-gray-300 px-4 py-2.5 focus:border-transparent focus:ring-2 focus:ring-bass-red"></textarea>
                    </div>
                </div>
            </template>
            <p x-show="faq.length === 0" class="rounded-lg border border-dashed border-gray-300 px-4 py-6 text-center text-sm text-gray-500">Belum ada FAQ.</p>
        </div>
        @error('sales_profile.faq')<p class="mt-1 text-sm text-error">{{ $message }}</p>@enderror
        @error('sales_profile.faq.*.question')<p class="mt-1 text-sm text-error">{{ $message }}</p>@enderror
        @error('sales_profile.faq.*.answer')<p class="mt-1 text-sm text-error">{{ $message }}</p>@enderror
    </div>
</div>
