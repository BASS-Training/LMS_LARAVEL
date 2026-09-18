{{--
    Pengaturan etalase (katalog publik).
    Dipakai bersama oleh courses/create & courses/edit.
    $course opsional (tidak ada saat create).
--}}
@php
    $shopVisibility = old('visibility', $course->visibility ?? 'private');
    $shopPrice = old('price', $course->price ?? null);
    $shopShortDesc = old('short_description', $course->short_description ?? '');
    $shopRequiresVerif = (bool) old('requires_payment_verification', $course->requires_payment_verification ?? false);
@endphp

<div class="group mt-4" x-data="{ inCatalog: '{{ $shopVisibility }}' === 'catalog' }">
    <label class="flex items-center text-sm font-semibold text-gray-700 mb-2">
        <svg class="w-4 h-4 mr-2 text-bass-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
        </svg>
        Katalog & Penjualan Kursus
    </label>

    <div class="rounded-lg border border-gray-300 p-4 space-y-4">

        {{-- Toggle: private vs catalog --}}
        <label class="flex items-start gap-3 cursor-pointer">
            <input type="hidden" name="visibility" value="private">
            <input type="checkbox" name="visibility" value="catalog"
                   x-model="inCatalog"
                   @checked($shopVisibility === 'catalog')
                   class="mt-0.5 w-5 h-5 rounded border-gray-300 text-bass-red focus:ring-bass-red">
            <span>
                <span class="block text-md font-bold text-gray-800">Tampilkan dan jual di katalog publik</span>
                <span class="block text-xs text-gray-500 mt-0.5">
                    Aktifkan agar kursus dapat ditemukan oleh siapa pun, termasuk pengunjung yang belum memiliki akun.
                    Peserta dapat mendaftar ke kursus gratis atau membeli kursus berbayar melalui sistem pembayaran.
                    Jika dinonaktifkan, akses kursus hanya tersedia melalui token atau kode enrollment.
                </span>
            </span>
        </label>

        <div x-show="inCatalog" x-collapse style="display:none" class="space-y-4 pt-1">

            <div class="rounded-md bg-amber-50 border border-amber-200 px-3 py-2 text-xs text-amber-800">
                Kursus hanya muncul di katalog publik jika <strong>Status Publikasi</strong> juga diatur ke
                <strong>Published</strong>. Kursus berstatus Draft tidak dapat dilihat atau dibeli oleh publik.
            </div>

            {{-- Harga --}}
            <div>
                <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Harga (Rp)</label>
                <input type="number" name="price" id="price" min="0" step="1000"
                       value="{{ $shopPrice }}"
                       placeholder="0 = gratis"
                       class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-bass-red focus:border-transparent">
                <p class="text-xs text-gray-500 mt-1">
                    Isi <strong>0</strong> untuk kursus gratis. Jika harga lebih dari 0, peserta harus menyelesaikan
                    pembayaran sebelum memperoleh akses kursus.
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
                       class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-bass-red focus:border-transparent">
                @error('short_description')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Mode verifikasi pembayaran --}}
            <div class="rounded-md border border-gray-200 bg-gray-50 p-3">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="hidden" name="requires_payment_verification" value="0">
                    <input type="checkbox" name="requires_payment_verification" value="1"
                           @checked($shopRequiresVerif)
                           class="mt-0.5 w-5 h-5 rounded border-gray-300 text-bass-red focus:ring-bass-red">
                    <span>
                        <span class="flex items-center gap-1.5 text-sm font-medium text-gray-800">
                            <svg class="w-4 h-4 text-bass-red flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            Butuh verifikasi manual sebelum akses
                        </span>
                        <span class="block text-xs text-gray-500 mt-0.5">
                            Aktifkan jika pembayaran perlu diperiksa oleh tim sebelum akses diberikan. Pesanan yang
                            sudah dibayar akan masuk ke antrean <strong>Verifikasi Pembayaran</strong> dan harus
                            disetujui super-admin. Jika dinonaktifkan, akses terbuka <strong>otomatis</strong> setelah
                            sistem pembayaran mengonfirmasi transaksi berhasil.
                        </span>
                    </span>
                </label>
            </div>
        </div>
    </div>
</div>
