@extends('layouts.public')

@section('title', $policy['title'])
@section('meta_description', $policy['description'])
@section('page_language', $language)

@section('content')
<article class="mx-auto max-w-4xl px-4 py-12 text-navy sm:px-6">
    <nav aria-label="{{ $policy['language_label'] }}" class="mb-8 flex flex-wrap gap-3">
        @foreach (['id' => 'Indonesia', 'en' => 'English'] as $code => $label)
            <a href="{{ route('privacy-policy', ['lang' => $code]) }}" lang="{{ $code }}" hreflang="{{ $code }}"
               @if ($language === $code) aria-current="page" @endif
               class="inline-flex min-h-11 items-center rounded-lg border-2 border-navy px-4 text-sm font-bold {{ $language === $code ? 'bg-navy text-white' : 'bg-white hover:text-bass-red' }}">{{ $label }}</a>
        @endforeach
    </nav>

    <p class="text-xs font-bold uppercase tracking-[0.18em] text-bass-red">{{ $policy['eyebrow'] }}</p>
    <h1 class="mt-2 text-3xl font-extrabold sm:text-4xl">{{ $policy['title'] }}</h1>
    <p class="mt-3 text-sm text-slate-500">{{ $policy['updated'] }} · BASS Learning and Development Institute</p>
    <p class="mt-6 leading-7 text-slate-700">{{ $policy['intro'] }}</p>

    <nav aria-label="{{ $policy['contents'] }}" class="mt-8 rounded-2xl border-2 border-navy bg-white p-6 sm:p-8">
        <h2 class="text-xl font-bold">{{ $policy['contents'] }}</h2>
        <ul class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
            @foreach ($policy['sections'] as $id => $section)
                <li><a href="#{{ $id }}" class="font-semibold text-bass-red hover:underline">{{ $section['title'] }}</a></li>
            @endforeach
        </ul>
    </nav>

    @foreach ($policy['sections'] as $id => $section)
        <section id="{{ $id }}" class="mt-10 scroll-mt-24">
            <h2 class="text-2xl font-extrabold">{{ $section['title'] }}</h2>
            @foreach ($section['paragraphs'] ?? [] as $paragraph)
                <p class="mt-4 leading-7 text-slate-700">{{ $paragraph }}</p>
            @endforeach
            @if (!empty($section['items']))
                <ul class="mt-4 list-disc space-y-3 pl-5 leading-7 text-slate-700">
                    @foreach ($section['items'] as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endforeach

    <section class="mt-10 rounded-2xl bg-navy p-6 text-white sm:p-8">
        <h2 class="text-2xl font-extrabold">{{ $policy['contact_title'] }}</h2>
        <p class="mt-4 leading-7 text-white/80">{{ $policy['contact_text'] }}</p>
        <a href="mailto:admin@basstrainingacademy.com" class="mt-5 inline-flex min-h-11 items-center break-all rounded-lg bg-white px-4 py-2 font-semibold text-navy">admin@basstrainingacademy.com</a>
    </section>

    <a href="{{ route('welcome') }}" class="mt-8 inline-block font-semibold text-bass-red hover:underline">{{ $policy['back'] }}</a>
</article>
@endsection
