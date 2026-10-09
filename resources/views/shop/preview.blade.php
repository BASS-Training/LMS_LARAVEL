@extends('layouts.public')

@section('title', 'Preview ' . $content->title)

@section('content')
<div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
    <nav class="text-xs font-semibold text-slate-500">
        <a href="{{ route('shop.index') }}" class="hover:text-bass-red">Katalog</a>
        <span class="mx-1">/</span>
        <a href="{{ route('shop.show', $course) }}" class="hover:text-bass-red">{{ $course->title }}</a>
        <span class="mx-1">/</span>
        Preview
    </nav>

    <header class="mt-6 rounded-2xl border-2 border-navy bg-navy p-6 text-white shadow-[7px_7px_0_#F6C945] sm:p-8">
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-bass-gold">Preview Materi &middot; {{ $content->lesson->title }}</p>
        <h1 class="mt-3 text-3xl font-extrabold [font-family:Fraunces,serif] sm:text-4xl">{{ $content->title }}</h1>
        <p class="mt-3 text-sm text-white/70">Aktivitas pada halaman preview tidak dicatat sebagai progress pembelajaran.</p>
    </header>

    <article class="mt-8 overflow-hidden rounded-2xl border-2 border-navy bg-[#fffdf7] p-6 shadow-[7px_7px_0_#DA1E1E] sm:p-8">
        @if ($content->description)
            <div class="prose prose-sm mb-7 max-w-none border-b border-dashed border-navy/20 pb-6 text-slate-700">{!! $content->description !!}</div>
        @endif

        @if ($content->type === 'text')
            <div class="prose max-w-none text-slate-800">{!! $content->body !!}</div>
        @elseif ($content->type === 'video')
            @if ($content->youtube_embed_url)
                <div class="aspect-video overflow-hidden rounded-xl border-2 border-navy bg-black">
                    <iframe class="h-full w-full" src="{{ $content->youtube_embed_url }}" title="{{ $content->title }}" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>
            @else
                <a href="{{ $content->body }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-12 items-center justify-center rounded-xl border-2 border-navy bg-bass-red px-5 font-bold text-white">Buka Video</a>
            @endif
        @elseif ($content->type === 'image')
            @php
                $imagePaths = $content->images->pluck('file_path');
                if ($imagePaths->isEmpty() && $content->file_path) {
                    $imagePaths = collect([$content->file_path]);
                }
            @endphp
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($imagePaths as $path)
                    <img src="{{ Storage::url($path) }}" alt="{{ $content->title }}" class="h-auto w-full rounded-xl border-2 border-navy bg-white object-contain">
                @endforeach
            </div>
        @endif
    </article>

    <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-between">
        <a href="{{ route('shop.show', $course) }}#kurikulum" class="inline-flex min-h-12 items-center justify-center rounded-xl border-2 border-navy px-5 font-bold text-navy hover:bg-white">&larr; Kembali ke course</a>
        <a href="{{ route('shop.show', $course) }}" class="inline-flex min-h-12 items-center justify-center rounded-xl border-2 border-navy bg-bass-red px-5 font-bold text-white shadow-[4px_4px_0_#17243A]">Lihat Detail Course</a>
    </div>
</div>
@endsection
