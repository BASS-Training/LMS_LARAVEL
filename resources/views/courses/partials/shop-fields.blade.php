{{--
    Pengaturan etalase (katalog publik).
    Dipakai bersama oleh courses/create & courses/edit.
    $course opsional (tidak ada saat create).
--}}
@php
    $shopVisibility = old('visibility', $course->visibility ?? 'private');
    $shopPrice = old('price', $course->price ?? null);
    $shopShortDesc = old('short_description', $course->short_description ?? '');
@endphp

<div class="group mt-4" x-data="{ inCatalog: '{{ $shopVisibility }}' === 'catalog' }">
    <label class="flex items-center text-sm font-semibold text-gray-700 mb-2">
        <svg class="w-4 h-4 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
        </svg>
        Etalase Kursus
    </label>

    <div class="rounded-lg border border-gray-300 p-4 space-y-4">

        {{-- Toggle: private vs catalog --}}
        <label class="flex items-start gap-3 cursor-pointer">
            <input type="hidden" name="visibility" value="private">
            <input type="checkbox" name="visibility" value="catalog"
                   x-model="inCatalog"
                   @checked($shopVisibility === 'catalog')
                   class="mt-0.5 w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
            <span>
                <span class="block text-sm font-medium text-gray-800">🛒 Tampilkan di katalog publik</span>
                <span class="block text-xs text-gray-500 mt-0.5">
                    Kursus bisa dilihat siapa pun (termasuk yang belum punya akun) dan bisa didaftar/dibeli langsung.
                    Jika dimatikan, kursus tetap hanya bisa diakses lewat token/kode enrollment seperti biasa.
                </span>
            </span>
        </label>

        <div x-show="inCatalog" x-collapse style="display:none" class="space-y-4 pt-1">

            <div class="rounded-md bg-amber-50 border border-amber-200 px-3 py-2 text-xs text-amber-800">
                Kursus baru muncul di katalog jika <strong>Status Publikasi</strong> di atas juga di-set
                <strong>Published</strong>. Draft tidak akan pernah tampil.
            </div>

            {{-- Harga --}}
            <div>
                <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Harga (Rp)</label>
                <input type="number" name="price" id="price" min="0" step="1000"
                       value="{{ $shopPrice }}"
                       placeholder="0 = gratis"
                       class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <p class="text-xs text-gray-500 mt-1">
                    Kosongkan atau isi <strong>0</strong> untuk kursus gratis — peserta bisa langsung daftar tanpa bayar.
                </p>
                @error('price')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Deskripsi singkat --}}
            <div>
                <label for="short_description" class="block text-sm font-medium text-gray-700 mb-1">
                    Deskripsi Singkat
                </label>
                <input type="text" name="short_description" id="short_description" maxlength="255"
                       value="{{ $shopShortDesc }}"
                       placeholder="Satu kalimat yang muncul di kartu katalog"
                       class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                @error('short_description')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>
</div>
