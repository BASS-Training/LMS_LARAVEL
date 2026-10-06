@php
    $selectedCourses = collect(old('course_ids', $coupon->exists ? $coupon->courses->pluck('id')->all() : []))->map(fn ($id) => (int) $id)->all();
    $allCourses = (bool) old('applies_to_all_courses', $coupon->applies_to_all_courses ?? true);
    $discountType = old('discount_type', $coupon->discount_type ?? \App\Models\Coupon::TYPE_PERCENTAGE);
@endphp

<div x-data="{ allCourses: @js($allCourses), discountType: @js($discountType) }" class="space-y-6">
    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="code" class="block text-sm font-semibold text-gray-700">Kode kupon</label>
            <input id="code" name="code" value="{{ old('code', $coupon->code) }}" required maxlength="50"
                   class="mt-1.5 w-full rounded-xl border-gray-300 font-mono uppercase focus:border-navy focus:ring-navy" placeholder="HEMAT10">
            @error('code')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="discount_type" class="block text-sm font-semibold text-gray-700">Jenis diskon</label>
            <select id="discount_type" name="discount_type" x-model="discountType" class="mt-1.5 w-full rounded-xl border-gray-300 focus:border-navy focus:ring-navy">
                <option value="percentage">Persentase</option>
                <option value="fixed">Nominal rupiah</option>
            </select>
        </div>
        <div>
            <label for="discount_value" class="block text-sm font-semibold text-gray-700">Nilai diskon</label>
            <div class="relative mt-1.5">
                <input id="discount_value" name="discount_value" type="number" min="1" :max="discountType === 'percentage' ? 99 : null"
                       value="{{ old('discount_value', $coupon->discount_value) }}" required class="w-full rounded-xl border-gray-300 pr-12 focus:border-navy focus:ring-navy">
                <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-gray-400" x-text="discountType === 'percentage' ? '%' : 'Rp'"></span>
            </div>
            @error('discount_value')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="minimum_amount" class="block text-sm font-semibold text-gray-700">Minimum pembelian <span class="font-normal text-gray-400">(opsional)</span></label>
            <input id="minimum_amount" name="minimum_amount" type="number" min="1" value="{{ old('minimum_amount', $coupon->minimum_amount) }}" class="mt-1.5 w-full rounded-xl border-gray-300 focus:border-navy focus:ring-navy" placeholder="100000">
        </div>
        <div>
            <label for="usage_limit" class="block text-sm font-semibold text-gray-700">Kuota total <span class="font-normal text-gray-400">(kosong = tanpa batas)</span></label>
            <input id="usage_limit" name="usage_limit" type="number" min="1" value="{{ old('usage_limit', $coupon->usage_limit) }}" class="mt-1.5 w-full rounded-xl border-gray-300 focus:border-navy focus:ring-navy">
        </div>
        <div>
            <label for="per_user_limit" class="block text-sm font-semibold text-gray-700">Kuota per pengguna</label>
            <input id="per_user_limit" name="per_user_limit" type="number" min="1" value="{{ old('per_user_limit', $coupon->per_user_limit) }}" class="mt-1.5 w-full rounded-xl border-gray-300 focus:border-navy focus:ring-navy">
        </div>
        <div>
            <label for="starts_at" class="block text-sm font-semibold text-gray-700">Mulai berlaku <span class="font-normal text-gray-400">(opsional)</span></label>
            <input id="starts_at" name="starts_at" type="datetime-local" value="{{ old('starts_at', $coupon->starts_at?->format('Y-m-d\TH:i')) }}" class="mt-1.5 w-full rounded-xl border-gray-300 focus:border-navy focus:ring-navy">
        </div>
        <div>
            <label for="expires_at" class="block text-sm font-semibold text-gray-700">Berakhir <span class="font-normal text-gray-400">(opsional)</span></label>
            <input id="expires_at" name="expires_at" type="datetime-local" value="{{ old('expires_at', $coupon->expires_at?->format('Y-m-d\TH:i')) }}" class="mt-1.5 w-full rounded-xl border-gray-300 focus:border-navy focus:ring-navy">
            @error('expires_at')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 p-5">
        <label class="flex cursor-pointer items-start gap-3">
            <input type="checkbox" name="applies_to_all_courses" value="1" x-model="allCourses" class="mt-1 rounded border-gray-300 text-navy focus:ring-navy">
            <span><span class="block text-sm font-semibold text-gray-900">Berlaku untuk semua course berbayar</span><span class="mt-0.5 block text-xs text-gray-500">Matikan pilihan ini untuk membatasi kupon ke course tertentu.</span></span>
        </label>

        <div x-show="!allCourses" x-cloak class="mt-4 border-t border-gray-100 pt-4">
            <p class="mb-3 text-sm font-semibold text-gray-700">Pilih course</p>
            <div class="grid max-h-64 gap-2 overflow-y-auto sm:grid-cols-2">
                @foreach ($courses as $courseOption)
                    <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm hover:bg-gray-50">
                        <input type="checkbox" name="course_ids[]" value="{{ $courseOption->id }}" @checked(in_array($courseOption->id, $selectedCourses, true)) class="rounded border-gray-300 text-navy focus:ring-navy">
                        <span class="line-clamp-1">{{ $courseOption->title }}</span>
                    </label>
                @endforeach
            </div>
            @error('course_ids')<p class="mt-2 text-xs text-error">{{ $message }}</p>@enderror
        </div>
    </div>

    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 p-5">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $coupon->is_active ?? true)) class="mt-1 rounded border-gray-300 text-success focus:ring-success">
        <span><span class="block text-sm font-semibold text-gray-900">Kupon aktif</span><span class="mt-0.5 block text-xs text-gray-500">Kupon nonaktif tetap tersimpan tetapi tidak dapat digunakan.</span></span>
    </label>

    <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
        <a href="{{ route('admin.coupons.index') }}" class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-gray-300 px-5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Batal</a>
        <button class="inline-flex min-h-[44px] items-center justify-center rounded-xl bg-bass-red px-6 text-sm font-semibold text-white shadow-sm hover:bg-bass-red-hover">{{ $submitLabel }}</button>
    </div>
</div>
