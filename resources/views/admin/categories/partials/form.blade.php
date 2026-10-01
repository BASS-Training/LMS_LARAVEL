@php($category = $category ?? new \App\Models\Category(['is_active' => true, 'sort_order' => 0]))

<div class="space-y-6">
    <div>
        <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Nama kategori *</label>
        <input id="name" name="name" type="text" required maxlength="255"
               value="{{ old('name', $category->name) }}"
               class="w-full rounded-lg border-gray-300 focus:border-bass-red focus:ring-bass-red">
        @error('name') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <label for="parent_id" class="block text-sm font-semibold text-gray-700 mb-2">Kategori induk</label>
            <select id="parent_id" name="parent_id" class="w-full rounded-lg border-gray-300 focus:border-bass-red focus:ring-bass-red">
                <option value="">Tanpa induk</option>
                @foreach ($parents as $parent)
                    <option value="{{ $parent->id }}" @selected((string) old('parent_id', $category->parent_id) === (string) $parent->id)>
                        {{ $parent->name }}
                    </option>
                @endforeach
            </select>
            @error('parent_id') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="sort_order" class="block text-sm font-semibold text-gray-700 mb-2">Urutan</label>
            <input id="sort_order" name="sort_order" type="number" min="0" required
                   value="{{ old('sort_order', $category->sort_order ?? 0) }}"
                   class="w-full rounded-lg border-gray-300 focus:border-bass-red focus:ring-bass-red">
            @error('sort_order') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">Deskripsi</label>
        <textarea id="description" name="description" rows="4"
                  class="w-full rounded-lg border-gray-300 focus:border-bass-red focus:ring-bass-red">{{ old('description', $category->description) }}</textarea>
        @error('description') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
    </div>

    <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-4">
        <input type="hidden" name="is_active" value="0">
        <input name="is_active" type="checkbox" value="1"
               @checked(old('is_active', $category->exists ? $category->is_active : true))
               class="mt-0.5 rounded border-gray-300 text-bass-red focus:ring-bass-red">
        <span>
            <span class="block text-sm font-semibold text-gray-800">Aktif</span>
            <span class="block text-xs text-gray-500">Kategori aktif dapat dipilih dan ditampilkan di katalog.</span>
        </span>
    </label>
</div>
