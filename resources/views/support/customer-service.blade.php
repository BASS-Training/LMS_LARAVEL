@extends('layouts.public')

@section('title', 'Customer Service — Layanan Pelanggan')
@section('meta_description', 'Hubungi layanan pelanggan BASS melalui WhatsApp atau email untuk bantuan akun, akses kursus, pembayaran, sertifikat, dan pengembalian dana.')
@section('page_language', 'id')

@section('content')
@php($support = \App\Support\CustomerServiceContent::data())
<article class="mx-auto max-w-5xl px-4 py-12 text-navy sm:px-6">
    <header class="max-w-3xl">
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-bass-red">Pusat bantuan BASS</p>
        <h1 class="mt-2 text-3xl font-extrabold sm:text-4xl">{{ $support['title'] }}</h1>
        <p class="mt-3 text-lg font-semibold">{{ $support['subtitle'] }}</p>
        <p class="mt-4 leading-7 text-slate-600">{{ $support['description'] }}</p>
    </header>

    <section aria-labelledby="contact-heading" class="mt-8">
        <h2 id="contact-heading" class="text-2xl font-extrabold">Hubungi tim kami</h2>
        <div class="mt-5 grid gap-5 md:grid-cols-2">
            @foreach ($support['contacts'] as $contact)
                <div @class(['flex flex-col rounded-2xl border-2 border-navy bg-white p-6 sm:p-8', 'shadow-[5px_5px_0_#F6C945]' => $contact['type'] === 'whatsapp', 'shadow-[5px_5px_0_#DA1E1E]' => $contact['type'] === 'email'])>
                    <span class="text-xs font-bold uppercase tracking-widest text-bass-red">{{ $contact['eyebrow'] }}</span>
                    <h3 class="mt-3 text-xl font-extrabold">{{ $contact['label'] }}</h3>
                    <p class="mt-3 leading-7 text-slate-600">{{ $contact['description'] }}</p>
                    <p class="mt-4 break-all font-semibold">{{ $contact['value'] }}</p>
                    <a href="{{ $contact['url'] }}" @if ($contact['type'] === 'whatsapp') target="_blank" rel="noopener noreferrer" @endif @class(['mt-6 inline-flex min-h-11 items-center justify-center rounded-lg px-5 py-3 text-center font-bold text-white', 'bg-navy hover:bg-navy/90' => $contact['type'] === 'whatsapp', 'bg-bass-red hover:bg-bass-red/90' => $contact['type'] === 'email'])>{{ $contact['action_label'] }}@if ($contact['type'] === 'whatsapp')<span class="sr-only"> (dibuka di tab baru)</span>@endif</a>
                </div>
            @endforeach
        </div>
        <div class="mt-6 rounded-xl border border-navy/15 bg-white px-5 py-4">
            <p class="font-bold">Jam layanan: {{ $support['service_hours']['label'] }}</p>
            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $support['service_hours']['note'] }}</p>
        </div>
    </section>

    <section aria-labelledby="request-heading" class="mt-10 rounded-2xl bg-navy p-6 text-white sm:p-8">
        <h2 id="request-heading" class="text-2xl font-extrabold">{{ $support['request_guidance']['title'] }}</h2>
        <p class="mt-3 leading-7 text-white/80">{{ $support['request_guidance']['description'] }}</p>
        <ul class="mt-4 list-disc space-y-2 pl-5 leading-7 text-white/90">
            @foreach ($support['request_guidance']['items'] as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
        <p class="mt-5 rounded-lg border border-white/20 px-4 py-3 text-sm leading-6">{{ $support['request_guidance']['warning'] }}</p>
    </section>

    <section aria-labelledby="faq-heading" class="mt-10">
        <h2 id="faq-heading" class="text-2xl font-extrabold">Pertanyaan yang sering diajukan</h2>
        <p class="mt-3 leading-7 text-slate-600">Temukan langkah awal untuk kendala yang umum terjadi.</p>
        <div class="mt-5 space-y-3">
            @foreach ($support['faq'] as $faq)
                <details class="group rounded-xl border border-navy/15 bg-white p-5">
                    <summary class="cursor-pointer font-bold">{{ $faq['question'] }}</summary>
                    <p class="mt-4 leading-7 text-slate-700">{{ $faq['answer'] }}</p>
                    @if (isset($faq['link']) && (! $loop->first || auth()->guest()))
                        <a href="{{ $faq['link']['url'] }}" class="mt-3 inline-block font-semibold text-bass-red hover:underline">{{ $faq['link']['label'] }}</a>
                    @endif
                </details>
            @endforeach
        </div>
    </section>

    <nav aria-label="Tautan bantuan terkait" class="mt-10 flex flex-wrap gap-x-6 gap-y-3 border-t border-navy/15 pt-6 text-sm font-semibold">
        @foreach ($support['links'] as $link)
            <a href="{{ $link['url'] }}" class="text-bass-red hover:underline">{{ $link['label'] }}</a>
        @endforeach
        <a href="{{ route('welcome') }}" class="hover:text-bass-red">Kembali ke beranda</a>
    </nav>
</article>
@endsection
