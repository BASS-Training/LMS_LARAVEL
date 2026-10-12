@extends('layouts.app')

@section('title', 'Penjualan ' . $course->title)

@section('content')
<div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
    <a href="{{ route('admin.course-commerce.index') }}" class="text-sm font-semibold text-gray-500 hover:text-navy">&larr; Kembali ke penjualan course</a>
    <div class="mt-4">
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-bass-red">Course Regular</p>
        <h1 class="mt-1 text-2xl font-bold text-gray-900">{{ $course->title }}</h1>
        <p class="mt-1 text-sm text-gray-500">Pengaturan ini hanya memengaruhi katalog dan transaksi, bukan materi atau progress pembelajaran.</p>
    </div>

    @if ($errors->any())<div class="mt-6 rounded-xl border border-error/30 bg-error-soft px-4 py-3 text-sm text-error">{{ $errors->first() }}</div>@endif

    <form method="POST" action="{{ route('admin.course-commerce.update', $course) }}" class="mt-7 space-y-8 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')

        @php
            $shopVisibility = old('visibility', $course->visibility ?? 'private');
            $shopPrice = old('price', $course->price);
            $shopShortDesc = old('short_description', $course->short_description);
            $shopRequiresVerif = (bool) old('requires_payment_verification', $course->requires_payment_verification);
        @endphp
        <section x-data="{ inCatalog: @js($shopVisibility === 'catalog') }">
            <h2 class="text-lg font-semibold text-gray-900">Status dan Harga</h2>
            <div class="mt-4 space-y-5 rounded-xl border border-gray-200 p-5">
                <label class="flex items-start gap-3"><input type="hidden" name="visibility" value="private"><input type="checkbox" name="visibility" value="catalog" x-model="inCatalog" @checked($shopVisibility === 'catalog') class="mt-1 rounded border-gray-300 text-bass-red focus:ring-bass-red"><span><strong class="block text-gray-900">Aktifkan di katalog publik</strong><span class="text-sm text-gray-500">Course harus berstatus akademik Published dan sales profile berstatus Published agar tampil.</span></span></label>
                <div x-show="inCatalog" x-collapse class="grid gap-5 md:grid-cols-2">
                    <div><label class="block text-sm font-semibold text-gray-700">Harga (Rp)</label><input type="number" min="0" step="1000" name="price" value="{{ $shopPrice }}" class="mt-1.5 w-full rounded-lg border-gray-300 focus:border-bass-red focus:ring-bass-red"><p class="mt-1 text-xs text-gray-500">Isi 0 untuk course gratis.</p>@error('price')<p class="mt-1 text-sm text-error">{{ $message }}</p>@enderror</div>
                    <div><label class="block text-sm font-semibold text-gray-700">Deskripsi kartu katalog</label><input type="text" maxlength="255" name="short_description" value="{{ $shopShortDesc }}" class="mt-1.5 w-full rounded-lg border-gray-300 focus:border-bass-red focus:ring-bass-red">@error('short_description')<p class="mt-1 text-sm text-error">{{ $message }}</p>@enderror</div>
                    <label class="flex items-start gap-3 md:col-span-2"><input type="hidden" name="requires_payment_verification" value="0"><input type="checkbox" name="requires_payment_verification" value="1" @checked($shopRequiresVerif) class="mt-1 rounded border-gray-300 text-bass-red focus:ring-bass-red"><span><strong class="block text-sm text-gray-900">Butuh verifikasi pembayaran manual</strong><span class="text-xs text-gray-500">Akses ditahan sampai pembayaran disetujui admin.</span></span></label>
                </div>
            </div>
        </section>

        @php
            $selectedPreviewIds = collect(old('preview_content_ids', $course->previews->pluck('content_id')->all()))
                ->map(fn ($id) => (int) $id);
        @endphp
        <section>
            <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-end">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Preview Materi</h2>
                    <p class="mt-1 text-sm text-gray-500">Pilih materi teks, video, atau gambar yang dapat dibuka calon peserta tanpa membuat progress.</p>
                </div>
                <span class="text-xs font-semibold text-gray-400">Urutan mengikuti kurikulum</span>
            </div>
            <div class="mt-4 space-y-4 rounded-xl border border-gray-200 p-5">
                @forelse ($course->lessons->filter(fn ($lesson) => $lesson->contents->isNotEmpty()) as $lesson)
                    <fieldset>
                        <legend class="text-sm font-bold text-navy">{{ $lesson->title }}</legend>
                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                            @foreach ($lesson->contents as $content)
                                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-3 hover:border-bass-red/50 hover:bg-bass-red-soft/30">
                                    <input type="checkbox" name="preview_content_ids[]" value="{{ $content->id }}" @checked($selectedPreviewIds->contains($content->id)) class="mt-0.5 rounded border-gray-300 text-bass-red focus:ring-bass-red">
                                    <span class="min-w-0"><strong class="block truncate text-sm text-gray-900">{{ $content->title }}</strong><span class="text-xs capitalize text-gray-500">{{ $content->type }}</span></span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @empty
                    <p class="text-sm text-gray-500">Belum ada materi teks, video, atau gambar yang dapat dijadikan preview.</p>
                @endforelse
                @error('preview_content_ids')<p class="text-sm text-error">{{ $message }}</p>@enderror
                @error('preview_content_ids.*')<p class="text-sm text-error">{{ $message }}</p>@enderror
            </div>
        </section>

        @include('admin.course-commerce.partials.sales-profile-fields')

        <div class="flex justify-end gap-3"><a href="{{ route('admin.course-commerce.index') }}" class="rounded-lg border border-gray-300 px-5 py-2.5 font-semibold text-gray-700">Batal</a><button class="rounded-lg bg-bass-red px-5 py-2.5 font-semibold text-white hover:bg-bass-red-hover">Simpan Penjualan</button></div>
    </form>
</div>
@endsection
