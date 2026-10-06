@extends('layouts.public')

@section('title', 'Bundle Course')

@section('content')
<div class="min-h-screen bg-[#f7f3ea] text-navy">
    <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
        <header class="max-w-3xl">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-bass-red">Bundle Course</p>
            <h1 class="mt-3 text-4xl font-extrabold leading-[1.05] tracking-tight [font-family:Fraunces,serif] sm:text-6xl">Beberapa course, <span class="font-medium italic text-bass-red">harga lebih hemat.</span></h1>
            <p class="mt-5 max-w-2xl text-base leading-7 text-slate-600 sm:text-lg">Pilih paket course dalam satu transaksi dengan harga khusus. Setiap panel menampilkan urutan course dan rincian harga paket.</p>
        </header>

        @if ($bundles->isEmpty())
            <div class="mt-12 rounded-2xl border-2 border-dashed border-navy bg-[#fffdf7] px-6 py-16 text-center">
                <h2 class="text-2xl font-extrabold [font-family:Fraunces,serif]">Bundle belum tersedia</h2>
                <p class="mt-2 text-sm text-slate-500">Paket course terbaru akan ditampilkan di halaman ini.</p>
            </div>
        @else
            <div class="mt-12 space-y-8">
                @foreach ($bundles as $bundle)
                    <article class="grid overflow-hidden rounded-2xl border-2 border-navy bg-[#fffdf7] shadow-[7px_7px_0_#17243A] md:grid-cols-[1.35fr_0.85fr]">
                        <div class="p-6 sm:p-8 md:border-r-2 md:border-dashed md:border-navy/25">
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="rounded-full border border-navy bg-white px-3 py-1 text-xs font-bold">{{ $bundle->courses->count() }} course</span>
                                @if ($bundle->savings() > 0)
                                    <span class="rounded-md bg-bass-gold px-3 py-1 text-xs font-bold text-navy">Hemat {{ $bundle->savings_label }}</span>
                                @endif
                            </div>
                            <h2 class="mt-5 text-3xl font-extrabold leading-tight [font-family:Fraunces,serif] sm:text-4xl">{{ $bundle->title }}</h2>
                            @if ($bundle->description)
                                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">{{ $bundle->description }}</p>
                            @endif

                            <ol class="mt-7 ml-2 border-l-2 border-bass-red">
                                @foreach ($bundle->courses as $index => $course)
                                    <li class="relative pb-5 pl-7 last:pb-0 before:absolute before:-left-[9px] before:top-1 before:h-4 before:w-4 before:rounded-full before:border-[3px] before:border-bass-red before:bg-[#fffdf7]">
                                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-bass-red">Course {{ $index + 1 }}</p>
                                        <p class="mt-1 font-semibold leading-snug">{{ $course->title }}</p>
                                    </li>
                                @endforeach
                            </ol>
                        </div>

                        <div class="flex flex-col justify-center bg-white/55 p-6 sm:p-8">
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Harga paket</p>
                            <p class="mt-3 text-sm text-slate-500 line-through">{{ $bundle->original_price_label }}</p>
                            <p class="mt-1 text-4xl font-extrabold leading-none text-bass-red [font-family:Fraunces,serif]">{{ $bundle->price_label }}</p>
                            @if ($bundle->savings() > 0)
                                <p class="mt-3 text-sm font-bold text-navy">Anda hemat {{ $bundle->savings_label }}</p>
                            @endif
                            <a href="{{ route('bundles.show', $bundle) }}" class="mt-8 inline-flex min-h-12 items-center justify-center rounded-xl border-2 border-navy bg-bass-red px-5 text-sm font-bold text-white shadow-[4px_4px_0_#F6C945] transition hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[2px_2px_0_#F6C945]">Lihat Detail Bundle</a>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-10">{{ $bundles->links() }}</div>
        @endif
    </div>
</div>
@endsection
