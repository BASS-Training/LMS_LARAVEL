@php
    $selectedCategories = collect(old('category_ids', isset($course) ? $course->categories->pluck('id')->all() : []))->map(fn ($id) => (int) $id);
    $selectedTags = collect(old('tag_ids', isset($course) ? $course->tags->pluck('id')->all() : []))->map(fn ($id) => (int) $id);
@endphp

<input type="hidden" name="taxonomy_present" value="1">

<div class="border-b border-gray-200 pb-8">
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-900">Kategori dan Tag</h3>
        <p class="mt-1 text-sm text-gray-500">Bantu peserta menemukan course ini di katalog.</p>
    </div>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
        <fieldset>
            <legend class="mb-3 text-sm font-semibold text-gray-700">Kategori</legend>
            <div class="max-h-64 space-y-2 overflow-y-auto rounded-xl border border-gray-200 p-4">
                @forelse ($categories as $category)
                    <label class="flex items-center gap-3 rounded-lg px-2 py-2 hover:bg-gray-50">
                        <input type="checkbox" name="category_ids[]" value="{{ $category->id }}"
                               @checked($selectedCategories->contains($category->id))
                               class="rounded border-gray-300 text-bass-red focus:ring-bass-red">
                        <span class="text-sm text-gray-700">{{ $category->name }}</span>
                        @if (! $category->is_active)<span class="ml-auto text-xs font-medium text-warning">Nonaktif</span>@endif
                    </label>
                @empty
                    <p class="text-sm text-gray-500">Belum ada kategori aktif.</p>
                @endforelse
            </div>
            @error('category_ids.*') <p class="mt-2 text-sm text-error">{{ $message }}</p> @enderror
        </fieldset>

        <fieldset>
            <legend class="mb-3 text-sm font-semibold text-gray-700">Tag</legend>
            <div class="max-h-64 space-y-2 overflow-y-auto rounded-xl border border-gray-200 p-4">
                @forelse ($tags as $tag)
                    <label class="flex items-center gap-3 rounded-lg px-2 py-2 hover:bg-gray-50">
                        <input type="checkbox" name="tag_ids[]" value="{{ $tag->id }}"
                               @checked($selectedTags->contains($tag->id))
                               class="rounded border-gray-300 text-bass-red focus:ring-bass-red">
                        <span class="text-sm text-gray-700">{{ $tag->name }}</span>
                        @if (! $tag->is_active)<span class="ml-auto text-xs font-medium text-warning">Nonaktif</span>@endif
                    </label>
                @empty
                    <p class="text-sm text-gray-500">Belum ada tag aktif.</p>
                @endforelse
            </div>
            @error('tag_ids.*') <p class="mt-2 text-sm text-error">{{ $message }}</p> @enderror
        </fieldset>
    </div>
</div>
