@extends('layouts.app')

@section('title', $bundle->title)

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <a href="{{ route('shop.index') }}" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-bass-red">
        <span aria-hidden="true">&larr;</span> Kembali ke katalog
    </a>

    @if ($errors->has('shop'))
        <div class="mt-5 rounded-lg border border-error/40 bg-error-soft px-4 py-3 text-sm text-error">{{ $errors->first('shop') }}</div>
    @endif

    <div class="mt-6 grid gap-8 lg:grid-cols-[1fr_360px]">
        <main>
            <div class="rounded-2xl bg-navy p-6 text-white sm:p-8">
                <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-semibold tracking-wide">PAKET KURSUS</span>
                <h1 class="mt-4 text-3xl font-bold sm:text-4xl">{{ $bundle->title }}</h1>
                @if ($bundle->description)
                    <p class="mt-4 max-w-3xl text-sm leading-7 text-white/80">{{ $bundle->description }}</p>
                @endif
                <p class="mt-5 text-sm text-white/70">{{ $bundle->courses->count() }} kursus dalam satu pembelian</p>
            </div>

            <section class="mt-8">
                <h2 class="text-xl font-bold text-gray-900">Isi paket</h2>
                <div class="mt-4 space-y-3">
                    @foreach ($bundle->courses as $index => $course)
                        @php($owned = in_array($course->id, $pricing['owned_ids'], true))
                        <article class="flex gap-4 rounded-xl border border-gray-200 bg-white p-4">
                            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-navy/10 font-bold text-navy">{{ $index + 1 }}</div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <h3 class="font-semibold text-gray-900">{{ $course->title }}</h3>
                                    @if ($owned)
                                        <span class="rounded-full bg-success-soft px-2.5 py-1 text-xs font-semibold text-success">Sudah dimiliki</span>
                                    @endif
                                </div>
                                <p class="mt-1 text-sm text-gray-500">{{ $course->instructors->pluck('name')->join(', ') ?: 'Instruktur BASS' }}</p>
                                <p class="mt-2 text-sm font-semibold text-navy">{{ $course->price_label }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        </main>

        <aside>
            <div class="sticky top-24 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <p class="text-sm text-gray-500">Jika dibeli terpisah</p>
                <p class="mt-1 text-lg text-gray-400 line-through">{{ $bundle->original_price_label }}</p>
                <p class="mt-4 text-sm font-semibold text-gray-700">Harga paket</p>
                <p class="mt-1 text-3xl font-bold text-bass-red">{{ $bundle->price_label }}</p>
                @if ($bundle->savings() > 0)
                    <p class="mt-2 text-sm font-semibold text-success">Hemat {{ $bundle->savings_label }}</p>
                @endif

                @auth
                    @if ($pricing['bundle_discount'] > 0)
                        <div class="mt-5 rounded-xl bg-success-soft p-4 text-sm">
                            <div class="flex justify-between gap-3 text-success"><span>Potongan kepemilikan</span><strong>-Rp {{ number_format($pricing['bundle_discount'], 0, ',', '.') }}</strong></div>
                            <div class="mt-2 flex justify-between gap-3 border-t border-success/20 pt-2 text-gray-900"><span>Harga Anda</span><strong>Rp {{ number_format($pricing['payable_base'], 0, ',', '.') }}</strong></div>
                        </div>
                    @endif
                @endauth

                @guest
                    <a href="{{ route('login') }}" class="mt-6 flex min-h-[48px] items-center justify-center rounded-lg bg-bass-red px-5 font-semibold text-white hover:bg-bass-red-hover">Masuk untuk Membeli</a>
                @else
                    @if ($pricing['payable_base'] > 0)
                        <a href="{{ route('checkout.bundle.choose', $bundle) }}" class="mt-6 flex min-h-[48px] items-center justify-center rounded-lg bg-bass-red px-5 font-semibold text-white hover:bg-bass-red-hover">Beli Paket</a>
                    @else
                        <button disabled class="mt-6 flex min-h-[48px] w-full items-center justify-center rounded-lg bg-gray-200 px-5 font-semibold text-gray-500">Tidak Ada Tagihan</button>
                    @endif
                @endguest

                <p class="mt-4 text-center text-xs text-gray-400">Biaya layanan {{ $methodsEnabled ? 'mulai dari ' : '' }}Rp {{ number_format($breakdown['fee'], 0, ',', '.') }}</p>
            </div>
        </aside>
    </div>
</div>
@endsection
