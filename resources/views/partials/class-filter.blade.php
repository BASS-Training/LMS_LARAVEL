{{-- Filter kelas (form GET biasa). Variabel: $classOptions (Collection<CourseClass>), $selectedClassId (?int) --}}
@if(isset($classOptions) && $classOptions->count() > 0)
    <form method="GET" action="{{ url()->current() }}" class="mb-4">
        {{-- Pertahankan parameter lain (mis. search), kecuali class_id & page --}}
        @foreach(collect(request()->query())->except(['class_id', 'page']) as $filterKey => $filterValue)
            @if(is_scalar($filterValue) && $filterValue !== '')
                <input type="hidden" name="{{ $filterKey }}" value="{{ $filterValue }}">
            @endif
        @endforeach

        <label for="class-filter" class="block text-sm font-semibold text-gray-700 mb-2">Filter Kelas</label>
        <div class="flex flex-col sm:flex-row gap-2">
            <select id="class-filter" name="class_id" onchange="this.form.submit()"
                    class="block w-full sm:w-auto sm:min-w-[18rem] py-2.5 text-sm rounded-lg border-gray-300 shadow-sm focus:border-bass-red focus:ring-bass-red">
                <option value="">Semua Kelas</option>
                @foreach($classOptions as $classOption)
                    <option value="{{ $classOption->id }}" @selected(($selectedClassId ?? null) == $classOption->id)>{{ $classOption->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2.5 bg-navy hover:bg-navy-light text-white text-sm font-medium rounded-lg shadow transition">Terapkan</button>
        </div>
        @if(!empty($selectedClassId))
            <p class="mt-1 text-xs text-gray-500">Menampilkan hanya peserta kelas terpilih.</p>
        @endif
    </form>
@endif
