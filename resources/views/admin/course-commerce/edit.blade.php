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

        @include('admin.course-commerce.partials.sales-profile-fields')

        <div class="flex justify-end gap-3"><a href="{{ route('admin.course-commerce.index') }}" class="rounded-lg border border-gray-300 px-5 py-2.5 font-semibold text-gray-700">Batal</a><button class="rounded-lg bg-bass-red px-5 py-2.5 font-semibold text-white hover:bg-bass-red-hover">Simpan Penjualan</button></div>
    </form>
</div>
@endsection
