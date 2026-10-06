@extends('layouts.public')

@section('title', 'Mulai Kompeten')

@section('content')
<section class="mx-auto grid max-w-7xl items-center gap-12 px-4 py-14 sm:px-6 sm:py-20 lg:grid-cols-[1.25fr_.75fr] lg:px-8">
    <div>
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-bass-red">Pelatihan Profesional BASS</p>
        <h1 class="mt-4 max-w-4xl text-5xl font-extrabold leading-[0.98] tracking-[-0.045em] text-navy [font-family:Fraunces,serif] sm:text-7xl lg:text-[5.4rem]">Bersama kami, <em class="font-medium text-bass-red">mulai</em> kompeten.</h1>
        <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-600">Pilih course sesuai kebutuhan dan bangun kompetensi profesional melalui pengalaman belajar yang terukur.</p>
        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
            @if ($bundlesEnabled)
            <a href="{{ route('bundles.index') }}" class="inline-flex min-h-12 items-center justify-center rounded-xl border-2 border-navy bg-bass-red px-6 text-sm font-bold text-white shadow-[5px_5px_0_#17243A] transition hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[2px_2px_0_#17243A]">Lihat Bundle Hemat</a>
            @endif
            <a href="{{ route('shop.index') }}" class="inline-flex min-h-12 items-center justify-center rounded-xl border-2 border-navy bg-white px-6 text-sm font-bold shadow-[5px_5px_0_#17243A] transition hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[2px_2px_0_#17243A]">Jelajahi Course</a>
        </div>
        <form method="GET" action="{{ route('shop.index') }}" class="mt-8 flex max-w-xl gap-2">
            <label for="landing-search" class="sr-only">Cari course</label>
            <input id="landing-search" type="search" name="q" minlength="2" maxlength="100" placeholder="Cari course yang Anda butuhkan" class="min-h-12 min-w-0 flex-1 rounded-xl border-2 border-navy bg-white px-4 text-sm focus:border-bass-red focus:ring-bass-red">
            <button class="min-h-12 rounded-xl border-2 border-navy bg-navy px-5 text-sm font-bold text-white">Cari</button>
        </form>
    </div>
    <div class="relative mx-auto w-full max-w-md pb-6 pr-3 sm:pr-6">
        <div class="relative rotate-2 rounded-lg border-2 border-navy bg-[#fffdf7] px-6 py-8 shadow-[10px_10px_0_#F6C945] sm:px-8 sm:py-10">
            <div class="pointer-events-none absolute inset-3 rounded border border-dashed border-navy/25"></div>
            <p class="relative text-xs font-bold uppercase tracking-[0.2em] text-slate-500">Sertifikat Kompetensi</p>
            <p class="relative mt-8 text-sm text-slate-500">Diberikan kepada</p>
            <p class="relative mt-2 border-b-2 border-navy pb-2 text-2xl font-medium italic text-bass-red [font-family:Fraunces,serif]">Nama Anda di sini</p>
            <h2 class="relative mt-7 text-2xl font-extrabold leading-tight [font-family:Fraunces,serif]">Atas keberhasilan menyelesaikan program pelatihan.</h2>
            <div class="relative mt-10 flex items-end justify-between gap-5 text-xs text-slate-500"><span>BASS Academy</span><span class="border-t border-navy px-5 pt-1">Instruktur</span></div>
            <div class="absolute -bottom-7 -right-5 flex h-24 w-24 -rotate-12 items-center justify-center rounded-full border-4 border-double border-white bg-bass-red text-center text-xs font-extrabold leading-tight text-white outline outline-2 outline-navy">TERUJI<br>&amp;<br>KOMPETEN</div>
        </div>
    </div>
</section>

<section class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
    <div class="grid overflow-hidden rounded-2xl border-2 border-navy bg-[#fffdf7] {{ $bundlesEnabled && $learningPathsEnabled ? 'md:grid-cols-3' : (($bundlesEnabled || $learningPathsEnabled) ? 'md:grid-cols-2' : '') }}">
        <a href="{{ route('shop.index') }}" class="border-b-2 border-navy p-6 transition hover:bg-white md:border-b-0 {{ $bundlesEnabled || $learningPathsEnabled ? 'md:border-r-2' : '' }}"><p class="text-xs font-bold tracking-[0.16em] text-bass-red">COURSE</p><h2 class="mt-2 text-2xl font-extrabold [font-family:Fraunces,serif]">Pilih per course</h2><p class="mt-2 text-sm leading-6 text-slate-600">Ambil kompetensi tertentu sesuai kebutuhan Anda saat ini.</p></a>
        @if ($bundlesEnabled)
        <a href="{{ route('bundles.index') }}" class="border-b-2 border-navy p-6 transition hover:bg-white md:border-b-0 md:border-r-2"><p class="text-xs font-bold tracking-[0.16em] text-bass-red">2 · BUNDLE</p><h2 class="mt-2 text-2xl font-extrabold [font-family:Fraunces,serif]">Beberapa course, lebih hemat</h2><p class="mt-2 text-sm leading-6 text-slate-600">Satu transaksi untuk paket course pilihan dengan harga khusus.</p></a>
        @endif
        @if ($learningPathsEnabled)
        <a href="{{ route('learning-paths.index') }}" class="p-6 transition hover:bg-white"><p class="text-xs font-bold tracking-[0.16em] text-bass-red">3 · LEARNING PATH</p><h2 class="mt-2 text-2xl font-extrabold [font-family:Fraunces,serif]">Belajar berurutan</h2><p class="mt-2 text-sm leading-6 text-slate-600">Ikuti rekomendasi urutan course untuk mencapai tujuan belajar.</p></a>
        @endif
    </div>
</section>

@if ($bundles->isNotEmpty())
<section id="bundle" class="border-y-2 border-navy bg-navy py-16 text-white sm:py-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end"><div><p class="text-xs font-bold uppercase tracking-[0.2em] text-bass-gold">Paket belajar pilihan</p><h2 class="mt-3 max-w-3xl text-4xl font-extrabold leading-tight [font-family:Fraunces,serif] sm:text-5xl">Ambil beberapa course,<br>bayar lebih murah.</h2><p class="mt-4 max-w-xl text-white/65">Paket course pilihan dalam satu transaksi dengan harga yang lebih efisien.</p></div><a href="{{ route('bundles.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border-2 border-white bg-[#fffdf7] px-5 text-sm font-bold text-navy shadow-[4px_4px_0_#F6C945]">Semua Bundle &rarr;</a></div>
        <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($bundles as $index => $bundle)
                <article class="flex flex-col rounded-2xl border-2 border-white {{ $index === 0 ? 'bg-bass-gold shadow-[7px_7px_0_#DA1E1E]' : 'bg-[#fffdf7] shadow-[7px_7px_0_#F6C945]' }} p-6 text-navy">
                    <div class="flex items-center justify-between gap-3"><span class="rounded-full border border-navy bg-white px-3 py-1 text-xs font-bold">{{ $bundle->courses->count() }} course</span>@if ($bundle->savings() > 0)<span class="rounded-md bg-bass-red px-2.5 py-1 text-xs font-bold text-white">Hemat {{ $bundle->savings_label }}</span>@endif</div>
                    <h3 class="mt-5 text-2xl font-extrabold leading-tight [font-family:Fraunces,serif]">{{ $bundle->title }}</h3>
                    @if ($bundle->description)<p class="mt-2 line-clamp-2 text-sm leading-6 text-slate-600">{{ $bundle->description }}</p>@endif
                    <ul class="mt-5 space-y-2 border-l-2 border-bass-red pl-4 text-sm font-semibold">@foreach ($bundle->courses->take(3) as $course)<li>{{ $course->title }}</li>@endforeach</ul>
                    <div class="mt-auto flex items-end justify-between gap-3 pt-7"><div><p class="text-xs text-slate-500 line-through">{{ $bundle->original_price_label }}</p><p class="text-2xl font-extrabold [font-family:Fraunces,serif]">{{ $bundle->price_label }}</p></div><a href="{{ route('bundles.show', $bundle) }}" class="rounded-lg border-2 border-navy bg-bass-red px-4 py-2 text-sm font-bold text-white">Lihat Detail</a></div>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif

@if ($learningPaths->isNotEmpty())
<section id="learning-path" class="py-16 sm:py-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end"><div><p class="text-xs font-bold uppercase tracking-[0.2em] text-bass-red">Learning Path</p><h2 class="mt-3 text-4xl font-extrabold leading-tight [font-family:Fraunces,serif] sm:text-5xl">Belajar dengan arah<br>yang lebih jelas.</h2><p class="mt-4 max-w-xl text-slate-600">Rangkaian course dalam urutan yang disarankan tanpa mengunci pilihan belajar Anda.</p></div><a href="{{ route('learning-paths.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border-2 border-navy bg-white px-5 text-sm font-bold shadow-[4px_4px_0_#17243A]">Semua Learning Path &rarr;</a></div>
        <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">@foreach ($learningPaths as $learningPath)<a href="{{ route('learning-paths.show', $learningPath) }}" class="group flex flex-col rounded-2xl border-2 border-navy bg-[#fffdf7] p-6 shadow-[6px_6px_0_#17243A] transition hover:-translate-y-1 hover:shadow-[6px_10px_0_#DA1E1E]"><div class="flex items-center justify-between"><span class="rounded-full bg-navy px-3 py-1 text-xs font-bold text-white">{{ $learningPath->courses->count() }} langkah</span><span class="text-2xl text-bass-red">&rarr;</span></div><h3 class="mt-5 text-2xl font-extrabold leading-tight [font-family:Fraunces,serif]">{{ $learningPath->title }}</h3>@if ($learningPath->short_description)<p class="mt-2 line-clamp-3 text-sm leading-6 text-slate-600">{{ $learningPath->short_description }}</p>@endif<p class="mt-6 border-t border-dashed border-navy/20 pt-4 text-xs font-semibold text-slate-500">Mulai dari {{ $learningPath->courses->first()->title }}</p></a>@endforeach</div>
    </div>
</section>
@endif

<section id="program" class="border-y-2 border-navy bg-[#fffdf7] py-16 sm:py-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end"><div><p class="text-xs font-bold uppercase tracking-[0.2em] text-bass-red">Course terbaru</p><h2 class="mt-3 text-4xl font-extrabold [font-family:Fraunces,serif] sm:text-5xl">Mulai dari satu course.</h2><p class="mt-3 max-w-xl text-slate-600">Fokus pada keterampilan yang paling Anda butuhkan sekarang.</p></div><a href="{{ route('shop.index') }}" class="text-sm font-bold text-bass-red">Lihat semua course &rarr;</a></div>
        @if ($courses->isNotEmpty())
            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">@foreach ($courses as $course)<a href="{{ route('shop.show', $course) }}" class="group flex flex-col overflow-hidden rounded-xl border-2 border-navy bg-white transition hover:-translate-y-1 hover:shadow-[0_8px_0_-2px_#DA1E1E]"><div class="relative h-32 overflow-hidden bg-navy">@if ($course->thumbnail)<img src="{{ asset('storage/'.$course->thumbnail) }}" alt="{{ $course->title }}" loading="lazy" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">@else<div class="flex h-full items-end justify-between p-4 text-white"><span class="rounded bg-white px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-navy">Course BASS</span><strong class="text-7xl leading-none opacity-15 [font-family:Fraunces,serif]">B</strong></div>@endif</div><div class="flex flex-1 flex-col p-4"><p class="text-xs text-slate-500">{{ $course->instructors->pluck('name')->join(', ') ?: 'Instruktur BASS' }}</p><h3 class="mt-2 line-clamp-2 text-lg font-extrabold leading-tight [font-family:Fraunces,serif]">{{ $course->title }}</h3>@if ($course->short_description)<p class="mt-2 line-clamp-2 text-sm text-slate-500">{{ $course->short_description }}</p>@endif<div class="mt-auto flex items-end justify-between gap-3 border-t border-dashed border-navy/20 pt-4 {{ $course->short_description ? 'mt-5' : 'mt-8' }}"><span class="text-xs text-slate-500">{{ $course->lessons_count }} pelajaran</span><span class="text-lg font-extrabold [font-family:Fraunces,serif]">{{ $course->price_label }}</span></div></div></a>@endforeach</div>
        @else
            <div class="mt-10 rounded-2xl border-2 border-dashed border-navy bg-white px-6 py-14 text-center"><h3 class="text-xl font-extrabold [font-family:Fraunces,serif]">Program sedang dipersiapkan</h3><p class="mt-2 text-sm text-slate-500">Course terbaru akan segera tersedia di katalog.</p></div>
        @endif
    </div>
</section>

<section id="cara-belajar" class="py-16 sm:py-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"><p class="text-xs font-bold uppercase tracking-[0.2em] text-bass-red">Cara belajar</p><h2 class="mt-3 text-4xl font-extrabold [font-family:Fraunces,serif] sm:text-5xl">Empat langkah, tanpa ribet.</h2><p class="mt-3 max-w-xl text-slate-600">Dari memilih program hingga membuktikan kompetensi, seluruh proses tersedia dalam satu platform.</p><ol class="mt-10 grid overflow-hidden rounded-2xl border-2 border-navy bg-[#fffdf7] md:grid-cols-4">@foreach ([['Pilih course', 'Pilih pelatihan yang sesuai dengan kebutuhan kompetensi Anda.'], ['Selesaikan pendaftaran', 'Daftar gratis atau selesaikan pembayaran secara aman.'], ['Mulai belajar', 'Akses materi, asesmen, diskusi, dan feedback instruktur.'], ['Tuntaskan program', 'Selesaikan persyaratan dan dapatkan bukti kompetensi.']] as $index => [$title, $description])<li class="border-b-2 border-navy p-6 last:border-0 md:border-b-0 md:border-r-2"><span class="block text-5xl font-extrabold leading-none text-bass-red [font-family:Fraunces,serif]">{{ $index + 1 }}</span><h3 class="mt-3 text-xl font-extrabold [font-family:Fraunces,serif]">{{ $title }}</h3><p class="mt-2 text-sm leading-6 text-slate-600">{{ $description }}</p></li>@endforeach</ol></div>
</section>

<section id="aplikasi" class="overflow-hidden border-y-2 border-navy bg-bass-red text-white">
    <div class="mx-auto grid max-w-7xl items-center gap-10 px-4 py-16 sm:px-6 sm:py-20 lg:grid-cols-[1fr_.8fr] lg:px-8">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-bass-gold">BASS Academy di Android</p>
            <h2 class="mt-3 max-w-2xl text-4xl font-extrabold leading-tight [font-family:Fraunces,serif] sm:text-5xl">Belajar tetap jalan,<br>di mana pun Anda berada.</h2>
            <p class="mt-5 max-w-xl text-base leading-7 text-white/75">Akses course, lanjutkan materi, pantau progres, dan ikuti aktivitas belajar langsung dari ponsel Anda.</p>
            <div class="mt-7 flex flex-wrap gap-x-6 gap-y-3 text-sm font-semibold text-white/90">
                <span class="inline-flex items-center gap-2"><span class="flex h-5 w-5 items-center justify-center rounded-full bg-bass-gold text-xs text-navy">&#10003;</span>Akses materi</span>
                <span class="inline-flex items-center gap-2"><span class="flex h-5 w-5 items-center justify-center rounded-full bg-bass-gold text-xs text-navy">&#10003;</span>Pantau progres</span>
                <span class="inline-flex items-center gap-2"><span class="flex h-5 w-5 items-center justify-center rounded-full bg-bass-gold text-xs text-navy">&#10003;</span>Notifikasi belajar</span>
            </div>
            <a href="https://play.google.com/store/apps/details?id=com.basstraining.lms&amp;pcampaignid=web_share"
               target="_blank" rel="noopener noreferrer"
               aria-label="Download BASS Academy di Google Play"
               class="mt-8 inline-flex min-h-16 items-center gap-3 rounded-xl border-2 border-white bg-black px-5 text-white shadow-[5px_5px_0_#F6C945] transition hover:translate-x-0.5 hover:translate-y-0.5 hover:shadow-[2px_2px_0_#F6C945]">
                <svg class="h-9 w-9 shrink-0" viewBox="0 0 48 48" aria-hidden="true">
                    <path fill="#00d7fe" d="M7.2 5.6c-.5.7-.8 1.7-.8 2.9v31c0 1.2.3 2.2.8 2.9L25 24 7.2 5.6Z"/>
                    <path fill="#ffce00" d="m31 17.8-6-6.2L8.6 4.2c-.5-.2-1-.3-1.4-.1L25 24l6-6.2Z"/>
                    <path fill="#ff3a44" d="M7.2 43.9c.4.2.9.1 1.4-.1L25 36.4l6-6.2-6-6.2L7.2 43.9Z"/>
                    <path fill="#00f076" d="m40.3 21.9-9.3-4.1-6 6.2 6 6.2 9.3-4.1c1.8-.8 1.8-3.4 0-4.2Z"/>
                </svg>
                <span class="text-left"><span class="block text-[10px] font-medium uppercase leading-none tracking-[0.14em] text-white/75">Download di</span><span class="mt-1 block text-xl font-semibold leading-none">Google Play</span></span>
            </a>
        </div>

        <div class="relative mx-auto w-full max-w-sm px-8 pt-3 sm:px-12">
            <div class="absolute left-0 top-12 h-24 w-24 -rotate-12 rounded-full border-2 border-navy bg-bass-gold"></div>
            <div class="absolute bottom-8 right-1 h-16 w-16 rotate-12 border-2 border-navy bg-white"></div>
            <div class="relative mx-auto w-64 rotate-2 rounded-[2.5rem] border-[5px] border-navy bg-[#f7f3ea] p-3 shadow-[10px_10px_0_#17243A] sm:w-72">
                <div class="mx-auto mb-3 h-1.5 w-16 rounded-full bg-navy"></div>
                <div class="rounded-[1.8rem] bg-white p-4 text-navy">
                    <div class="flex items-center justify-between"><img src="{{ asset('images/logo.png') }}" alt="BASS Academy" class="h-8 w-auto"><span class="h-8 w-8 rounded-full bg-bass-gold"></span></div>
                    <p class="mt-6 text-xs font-semibold text-slate-500">Selamat datang kembali</p>
                    <p class="mt-1 text-xl font-extrabold [font-family:Fraunces,serif]">Lanjutkan belajar.</p>
                    <div class="mt-4 rounded-xl bg-navy p-4 text-white">
                        <div class="flex items-center justify-between text-[10px] font-bold uppercase tracking-wider text-white/60"><span>Course aktif</span><span>68%</span></div>
                        <p class="mt-3 text-sm font-bold">Kompetensi Profesional</p>
                        <div class="mt-4 h-2 overflow-hidden rounded-full bg-white/20"><div class="h-full w-2/3 rounded-full bg-bass-gold"></div></div>
                        <div class="mt-4 rounded-lg bg-bass-red px-3 py-2 text-center text-xs font-bold">Lanjutkan materi</div>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-3"><div class="rounded-xl border-2 border-navy/10 p-3"><span class="block text-lg font-extrabold">4</span><span class="text-[10px] text-slate-500">Course saya</span></div><div class="rounded-xl border-2 border-navy/10 p-3"><span class="block text-lg font-extrabold">2</span><span class="text-[10px] text-slate-500">Sertifikat</span></div></div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="kontak" class="border-y-2 border-navy bg-bass-gold py-16 sm:py-20">
    <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 md:grid-cols-2 lg:px-8"><div class="flex flex-col rounded-2xl bg-navy p-7 text-white sm:p-9"><p class="text-xs font-bold uppercase tracking-[0.2em] text-bass-gold">Kontak</p><h2 class="mt-3 text-4xl font-extrabold [font-family:Fraunces,serif]">Ada pertanyaan?<br>Hubungi kami.</h2><p class="mt-5 max-w-md text-sm leading-6 text-white/70">Tim BASS siap membantu memilih program yang sesuai dengan kebutuhan individu maupun perusahaan Anda.</p><div class="mt-8 space-y-2 text-sm text-white/80"><p>PT Bintang Anugrah Surya Semesta</p><p>Ruko U Town Avenue Bintaro Jaya A16, Tangerang Selatan</p><p>WhatsApp: +62 821-1279-8728</p></div><a href="https://wa.me/6282112798728" target="_blank" rel="noopener" class="mt-8 inline-flex min-h-12 w-fit items-center justify-center rounded-xl border-2 border-white bg-bass-red px-6 text-sm font-bold shadow-[4px_4px_0_#F6C945]">Chat WhatsApp</a></div><div x-data="{ name: '', message: '' }" class="rounded-2xl border-2 border-navy bg-[#fffdf7] p-7 shadow-[7px_7px_0_#17243A] sm:p-9"><h2 class="text-3xl font-extrabold [font-family:Fraunces,serif]">Kirim pesan</h2><p class="mt-2 text-sm text-slate-600">Pesan akan diteruskan melalui WhatsApp BASS.</p><label class="mt-6 block text-sm font-bold" for="contact-name">Nama Anda</label><input id="contact-name" x-model="name" class="mt-2 min-h-12 w-full rounded-xl border-2 border-navy bg-white focus:border-bass-red focus:ring-bass-red" placeholder="Nama lengkap"><label class="mt-4 block text-sm font-bold" for="contact-message">Pertanyaan atau kebutuhan</label><textarea id="contact-message" x-model="message" rows="4" class="mt-2 w-full rounded-xl border-2 border-navy bg-white focus:border-bass-red focus:ring-bass-red" placeholder="Ceritakan program yang Anda cari"></textarea><button type="button" @click="window.open('https://wa.me/6282112798728?text=' + encodeURIComponent('Halo BASS, saya ' + (name || 'calon peserta') + '.\n' + message), '_blank', 'noopener')" class="mt-5 inline-flex min-h-12 w-full items-center justify-center rounded-xl border-2 border-navy bg-bass-red px-6 text-sm font-bold text-white shadow-[4px_4px_0_#17243A]">Kirim via WhatsApp</button></div></div>
</section>
@endsection
