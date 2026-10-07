@extends('layouts.public')

@section('title', 'Katalog Course')

@section('content')
<div x-data="{ filtersOpen: false }" class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <header class="pb-8">
        <nav class="text-xs font-semibold text-slate-500"><a href="{{ route('welcome') }}" class="hover:text-bass-red">Beranda</a> <span class="mx-1">/</span> Katalog Course</nav>
        <p class="mt-8 text-xs font-bold uppercase tracking-[0.2em] text-bass-red">Katalog BASS</p>
        <h1 class="mt-3 text-4xl font-extrabold tracking-[-0.035em] [font-family:Fraunces,serif] sm:text-6xl">Semua <em class="font-medium text-bass-red">course</em></h1>
        <p class="mt-4 max-w-2xl text-lg text-slate-600">Pilih course sesuai kebutuhan dan mulai tingkatkan kompetensi Anda.</p>
    </header>

    <form method="GET" action="{{ route('shop.index') }}" class="grid gap-8 lg:grid-cols-[240px_1fr]">
        <aside :class="filtersOpen ? 'block' : 'hidden'" class="h-fit rounded-2xl border-2 border-navy bg-[#fffdf7] p-5 lg:sticky lg:top-24 lg:block">
            <div class="flex items-center justify-between border-b border-dashed border-navy/20 pb-4"><h2 class="font-extrabold [font-family:Fraunces,serif]">Filter Course</h2><a href="{{ route('shop.index') }}" class="text-xs font-bold text-bass-red">Reset</a></div>

            <fieldset class="border-b border-dashed border-navy/20 py-5">
                <legend class="text-xs font-bold uppercase tracking-[0.15em] text-bass-red">Harga</legend>
                <div class="mt-3 space-y-2 text-sm">
                    @foreach (['' => 'Semua harga', 'free' => 'Gratis', 'paid' => 'Berbayar'] as $value => $label)
                        <label class="flex cursor-pointer items-center gap-2"><input type="radio" name="harga" value="{{ $value }}" @checked($priceFilter === ($value ?: null)) class="border-navy text-bass-red focus:ring-bass-red"> {{ $label }}</label>
                    @endforeach
                </div>
            </fieldset>

            @if ($categories->isNotEmpty())
                <div class="border-b border-dashed border-navy/20 py-5">
                    <label for="catalog-category" class="text-xs font-bold uppercase tracking-[0.15em] text-bass-red">Kategori</label>
                    <select id="catalog-category" name="category" class="mt-3 min-h-11 w-full rounded-lg border-2 border-navy bg-white text-sm focus:border-bass-red focus:ring-bass-red"><option value="">Semua kategori</option>@foreach ($categories as $category)<option value="{{ $category->slug }}" @selected($categoryFilter === $category->slug)>{{ $category->name }}</option>@endforeach</select>
                </div>
            @endif

            @if ($tags->isNotEmpty())
                <div class="py-5">
                    <label for="catalog-tag" class="text-xs font-bold uppercase tracking-[0.15em] text-bass-red">Tag</label>
                    <select id="catalog-tag" name="tag" class="mt-3 min-h-11 w-full rounded-lg border-2 border-navy bg-white text-sm focus:border-bass-red focus:ring-bass-red"><option value="">Semua tag</option>@foreach ($tags as $tag)<option value="{{ $tag->slug }}" @selected($tagFilter === $tag->slug)>{{ $tag->name }}</option>@endforeach</select>
                </div>
            @endif

            <button class="mt-1 inline-flex min-h-11 w-full items-center justify-center rounded-lg border-2 border-navy bg-bass-red px-4 text-sm font-bold text-white shadow-[3px_3px_0_#17243A]">Terapkan Filter</button>
        </aside>

        <div class="min-w-0">
            <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center">
                <button type="button" @click="filtersOpen = !filtersOpen" class="inline-flex min-h-11 items-center justify-center rounded-full border-2 border-navy bg-[#fffdf7] px-4 text-sm font-bold lg:hidden">Filter</button>
                <p class="font-bold">{{ $courses->total() }} course ditemukan</p>
                <div class="flex flex-1 flex-col gap-2 sm:ml-auto sm:max-w-xl sm:flex-row">
                    <input type="search" name="q" value="{{ $search }}" placeholder="Cari course..." class="min-h-11 min-w-0 flex-1 rounded-full border-2 border-navy bg-[#fffdf7] px-4 text-sm focus:border-bass-red focus:ring-bass-red">
                    <select name="sort" class="min-h-11 rounded-full border-2 border-navy bg-[#fffdf7] px-4 text-sm focus:border-bass-red focus:ring-bass-red"><option value="latest" @selected($sort === 'latest')>Terbaru</option><option value="price_asc" @selected($sort === 'price_asc')>Harga terendah</option><option value="price_desc" @selected($sort === 'price_desc')>Harga tertinggi</option></select>
                    <button class="min-h-11 rounded-full bg-navy px-5 text-sm font-bold text-white">Cari</button>
                </div>
            </div>

            @if ($courses->isEmpty())
                <div class="rounded-2xl border-2 border-dashed border-navy bg-[#fffdf7] px-6 py-16 text-center"><h2 class="text-xl font-extrabold [font-family:Fraunces,serif]">Belum ada kursus di katalog</h2><p class="mt-2 text-sm text-slate-500">{{ $search ? 'Coba kata kunci atau filter lain.' : 'Kursus yang tersedia akan muncul di sini.' }}</p></div>
            @else
                <div class="space-y-4">
                    @foreach ($courses as $course)
                        @php
                            $owned = in_array($course->id, $enrolledIds, true);
                            $managed = in_array($course->id, $managedIds, true);
                        @endphp
                        <article class="group overflow-hidden rounded-xl border-2 border-navy bg-[#fffdf7] transition hover:translate-x-1 hover:shadow-[-6px_6px_0_#DA1E1E] sm:flex">
                            <a href="{{ route('shop.show', $course) }}" class="relative flex h-28 shrink-0 items-end overflow-hidden bg-navy p-3 text-white sm:h-auto sm:w-40">
                                @if ($course->thumbnail)<img src="{{ asset('storage/'.$course->thumbnail) }}" alt="{{ $course->title }}" class="absolute inset-0 h-full w-full object-cover" loading="lazy"><span class="absolute inset-0 bg-navy/45"></span>@endif
                                <span class="relative rounded bg-white px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-navy">Course</span>
                            </a>
                            <a href="{{ route('shop.show', $course) }}" class="min-w-0 flex-1 p-4 sm:p-5">
                                <div class="flex flex-wrap gap-2">@if ($managed)<span class="rounded-md bg-navy px-2 py-1 text-xs font-bold text-white">Dikelola</span>@elseif ($owned)<span class="rounded-md bg-success-soft px-2 py-1 text-xs font-bold text-success">Sudah dimiliki</span>@endif @foreach ($course->categories->take(1) as $category)<span class="rounded-md border border-navy/20 px-2 py-1 text-xs font-semibold">{{ $category->name }}</span>@endforeach</div>
                                <h2 class="mt-3 text-xl font-extrabold leading-tight group-hover:text-bass-red [font-family:Fraunces,serif]">{{ $course->title }}</h2>
                                @if ($course->salesProfile?->headline || $course->short_description)<p class="mt-2 line-clamp-2 text-sm leading-6 text-slate-600">{{ $course->salesProfile?->headline ?: $course->short_description }}</p>@endif
                                <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500"><span>{{ $course->instructors->pluck('name')->join(', ') ?: 'Instruktur BASS' }}</span><span>{{ $course->lessons_count }} pelajaran</span>@foreach ($course->tags->take(2) as $tag)<span>#{{ $tag->name }}</span>@endforeach</div>
                            </a>
                            <div class="flex items-center justify-between gap-4 border-t-2 border-dashed border-navy/20 p-4 sm:w-48 sm:flex-col sm:items-end sm:justify-center sm:border-l-2 sm:border-t-0">
                                <strong class="text-xl font-extrabold {{ $course->isFree() ? 'text-success' : 'text-navy' }} [font-family:Fraunces,serif]">{{ $course->price_label }}</strong>
                                <a href="{{ route('shop.show', $course) }}" class="rounded-lg bg-navy px-4 py-2 text-xs font-bold text-white">Lihat Detail</a>
                            </div>
                        </article>
                    @endforeach
                </div>
                <div class="mt-8">{{ $courses->links() }}</div>
            @endif
        </div>
    </form>
</div>
@endsection
