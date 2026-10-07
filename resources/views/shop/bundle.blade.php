@extends('layouts.public')

@section('title', $bundle->title)

@section('content')
<div class="min-h-screen bg-[#f7f3ea] text-navy">
    <div class="border-b-2 border-navy bg-navy pb-28 pt-10 text-white sm:pb-36 sm:pt-14">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <a href="{{ route('bundles.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-white/70 transition hover:text-bass-gold"><span aria-hidden="true">&larr;</span> Kembali ke daftar bundle</a>
            <div class="mt-8 max-w-3xl">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-bass-gold">Paket Course</p>
                <h1 class="mt-3 text-4xl font-extrabold leading-[1.05] tracking-tight [font-family:Fraunces,serif] sm:text-6xl">{{ $bundle->title }}</h1>
                @if ($bundle->description)
                    <p class="mt-5 max-w-2xl text-base leading-7 text-white/70 sm:text-lg">{{ $bundle->description }}</p>
                @endif
                <div class="mt-6 inline-flex rounded-full border border-white/40 px-4 py-1.5 text-sm font-semibold">{{ $bundle->courses->count() }} course dalam satu pembelian</div>
            </div>
        </div>
    </div>

    <div class="mx-auto -mt-20 grid max-w-6xl items-start gap-8 px-4 pb-16 sm:-mt-24 sm:px-6 lg:grid-cols-[minmax(0,1fr)_350px] lg:px-8">
        <main class="rounded-2xl border-2 border-navy bg-[#fffdf7] p-6 shadow-[7px_7px_0_#F6C945] sm:p-8">
            @if ($errors->has('shop'))
                <div class="mb-6 rounded-xl border-2 border-bass-red bg-red-50 px-4 py-3 text-sm font-semibold text-bass-red">{{ $errors->first('shop') }}</div>
            @endif

            <div class="flex flex-wrap items-end justify-between gap-4 border-b-2 border-dashed border-navy/20 pb-5">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-bass-red">Isi paket</p>
                    <h2 class="mt-2 text-3xl font-extrabold [font-family:Fraunces,serif]">Course dalam bundle</h2>
                </div>
                <p class="text-sm text-slate-500">Urutan paket {{ $bundle->courses->count() }} course</p>
            </div>

            <ol class="mt-7 ml-5 border-l-2 border-bass-red">
                @foreach ($bundle->courses as $index => $course)
                    @php($owned = in_array($course->id, $pricing['owned_ids'], true))
                    <li class="relative pb-7 pl-8 last:pb-0">
                        <span class="absolute -left-5 top-0 flex h-10 w-10 items-center justify-center rounded-full border-2 border-bass-red bg-[#fffdf7] text-sm font-extrabold text-bass-red">{{ $index + 1 }}</span>
                        <div class="rounded-xl border border-navy/20 bg-white p-4 sm:p-5">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3 class="text-xl font-extrabold leading-tight [font-family:Fraunces,serif]">{{ $course->title }}</h3>
                                    <p class="mt-2 text-sm text-slate-500">{{ $course->instructors->pluck('name')->join(', ') ?: 'Instruktur BASS' }}</p>
                                </div>
                                @if ($owned)
                                    <span class="shrink-0 rounded-full border border-emerald-700 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">Sudah dimiliki</span>
                                @endif
                            </div>
                            <p class="mt-4 border-t border-dashed border-navy/15 pt-3 text-sm font-bold text-navy">{{ $course->price_label }} jika dibeli terpisah</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </main>

        <aside class="lg:sticky lg:top-24">
            <div class="rounded-2xl border-2 border-navy bg-[#fffdf7] p-6 shadow-[7px_7px_0_#17243A]">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Jika dibeli terpisah</p>
                <p class="mt-2 text-lg text-slate-500 line-through">{{ $bundle->original_price_label }}</p>
                <p class="mt-5 text-xs font-bold uppercase tracking-[0.16em] text-bass-red">Harga paket</p>
                <p class="mt-2 text-4xl font-extrabold leading-none text-bass-red [font-family:Fraunces,serif]">{{ $bundle->price_label }}</p>
                @if ($bundle->savings() > 0)
                    <span class="mt-4 inline-flex rounded-md bg-bass-gold px-3 py-1.5 text-xs font-bold text-navy">Hemat {{ $bundle->savings_label }}</span>
                @endif

                @auth
                    @if ($pricing['bundle_discount'] > 0)
                        <div class="mt-6 rounded-xl border border-emerald-700/30 bg-emerald-50 p-4 text-sm">
                            <div class="flex justify-between gap-3 text-emerald-700"><span>Potongan kepemilikan</span><strong>-Rp {{ number_format($pricing['bundle_discount'], 0, ',', '.') }}</strong></div>
                            <div class="mt-3 flex justify-between gap-3 border-t border-emerald-700/20 pt-3 text-navy"><span>Harga Anda</span><strong>Rp {{ number_format($pricing['payable_base'], 0, ',', '.') }}</strong></div>
                        </div>
                    @endif
                @endauth

                @guest
                    <a href="{{ route('login') }}" class="mt-7 flex min-h-12 items-center justify-center rounded-xl border-2 border-navy bg-bass-red px-5 font-bold text-white shadow-[4px_4px_0_#F6C945] transition hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[2px_2px_0_#F6C945]">Masuk untuk Membeli</a>
                @else
                    @if ($pricing['payable_base'] > 0)
                        @php($refundSettings = \App\Models\RefundSetting::current())
                        <form method="GET" action="{{ route('checkout.bundle.choose', $bundle) }}" class="mt-7 space-y-3">
                            @include('shop.partials.refund-consent')
                            <button type="submit" class="flex min-h-12 w-full items-center justify-center rounded-xl border-2 border-navy bg-bass-red px-5 font-bold text-white shadow-[4px_4px_0_#F6C945] transition hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[2px_2px_0_#F6C945]">Beli Paket</button>
                        </form>
                    @else
                        <button disabled class="mt-7 flex min-h-12 w-full cursor-not-allowed items-center justify-center rounded-xl border-2 border-slate-300 bg-slate-200 px-5 font-bold text-slate-500">Tidak Ada Tagihan</button>
                    @endif
                @endguest

                <p class="mt-5 border-t border-dashed border-navy/20 pt-4 text-center text-xs leading-5 text-slate-500">Biaya layanan {{ $methodsEnabled ? 'mulai dari ' : '' }}Rp {{ number_format($breakdown['fee'], 0, ',', '.') }}</p>
            </div>
        </aside>
    </div>
</div>
@endsection
