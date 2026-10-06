@php($tag = $tag ?? new \App\Models\Tag(['is_active' => true]))

<div class="space-y-6">
    <div>
        <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Nama tag *</label>
        <input id="name" name="name" type="text" required maxlength="255" value="{{ old('name', $tag->name) }}"
               class="w-full rounded-lg border-gray-300 focus:border-bass-red focus:ring-bass-red">
        @error('name') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
    </div>
    <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-4">
        <input type="hidden" name="is_active" value="0">
        <input name="is_active" type="checkbox" value="1" @checked(old('is_active', $tag->exists ? $tag->is_active : true)) class="mt-0.5 rounded border-gray-300 text-bass-red focus:ring-bass-red">
        <span><span class="block text-sm font-semibold text-gray-800">Aktif</span><span class="block text-xs text-gray-500">Tag aktif dapat dipilih dan ditampilkan di katalog.</span></span>
    </label>
</div>
