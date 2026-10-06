@extends('layouts.public')

@section('title', 'Kebijakan Refund')

@section('content')
<main class="mx-auto max-w-4xl px-4 py-12 text-navy sm:px-6">
    <p class="text-xs font-bold uppercase tracking-[0.18em] text-bass-red">Pembayaran kursus dan bundle</p>
    <h1 class="mt-2 text-3xl font-extrabold sm:text-4xl">Kebijakan Pengembalian Dana (Refund)</h1>
    <p class="mt-2 text-sm text-slate-500">Berlaku sejak 6 Oktober 2026 · BASS Learning and Development Institute</p>
    <p class="mt-4 max-w-3xl leading-7 text-slate-600">Kebijakan ini berlaku untuk pembelian course dan bundle berbayar. Dengan mencentang persetujuan dan menekan tombol “Lanjutkan Pembayaran”, pembeli menyatakan telah membaca dan memahami ketentuan yang berlaku. Pembayaran berhasil adalah transaksi yang telah dikonfirmasi lunas oleh penyedia pembayaran.</p>

    <div class="mt-8 rounded-2xl border-2 border-navy bg-[#fffdf7] p-6 shadow-[5px_5px_0_#F6C945] sm:p-8">
        <span class="inline-flex rounded-full bg-bass-gold px-3 py-1 text-xs font-bold text-navy">Berlaku untuk pembelian baru</span>
        @if ($settings->policy_mode === 'company_issue')
            <h2 class="mt-4 text-2xl font-extrabold">Non-refundable, kecuali kendala dari pihak kami</h2>
            <blockquote class="mt-4 border-l-4 border-bass-red bg-white px-4 py-3 font-semibold leading-7">{{ $settings->consentText() }}</blockquote>
            <p class="mt-4 leading-7 text-slate-700">Refund hanya dapat disetujui jika penyebabnya berasal dari pihak BASS Learning and Development Institute. Contoh kondisi yang dapat diajukan:</p>
            <ul class="mt-3 list-disc space-y-2 pl-5 leading-7 text-slate-700">
                <li>Course dibatalkan atau tidak jadi diselenggarakan oleh kami.</li>
                <li>Akses course tidak diberikan dalam 3 × 24 jam setelah pembayaran berhasil dan tidak dapat diperbaiki oleh tim kami.</li>
                <li>Terjadi penagihan ganda untuk course yang sama.</li>
                <li>Nominal yang ditagihkan berbeda dari harga saat checkout akibat kesalahan sistem.</li>
                <li>Materi tidak dapat diakses karena kendala teknis dari pihak kami yang tidak dapat diselesaikan dalam 7 hari kerja sejak dilaporkan.</li>
            </ul>
            <p class="mt-5 font-semibold text-navy">Kondisi berikut tidak memenuhi syarat:</p>
            <ul class="mt-2 list-disc space-y-2 pl-5 leading-7 text-slate-700">
                <li>Berubah pikiran, tidak lagi membutuhkan course, atau tidak memiliki waktu untuk mengikutinya.</li>
                <li>Salah memilih course atau metode pembayaran.</li>
                <li>Kendala pada perangkat, koneksi internet, atau akun pembeli yang tidak aktif.</li>
                <li>Akun dinonaktifkan karena melanggar syarat dan ketentuan.</li>
                <li>Pengajuan melewati batas waktu, kecuali pengecualian pembatalan course yang dijelaskan di bawah.</li>
            </ul>
            <p class="mt-5 leading-7 text-slate-700">Pengajuan dilakukan paling lambat <strong>7 hari kalender sejak pembayaran berhasil</strong>. Untuk course yang dibatalkan oleh kami, batas dihitung sejak kendala pembatalan diketahui. Progres pembelajaran tidak membatasi laporan dalam aturan ini; sertifikat yang sudah diterbitkan tetap menghalangi refund.</p>
        @else
            <h2 class="mt-4 text-2xl font-extrabold">Refund berbatas hari</h2>
            <p class="mt-4 leading-7 text-slate-700">Refund dapat diajukan paling lambat {{ $settings->request_window_days }} hari setelah akses pembelajaran diberikan, selama progres kursus tidak melebihi {{ $settings->max_progress_percentage }}% dan sertifikat belum diterbitkan. Pengajuan yang memenuhi batas tersebut tetap ditinjau oleh tim kami dan tidak otomatis disetujui.</p>
        @endif
    </div>

    <section class="mt-10">
        <h2 class="text-2xl font-extrabold">Aturan yang berlaku untuk pesanan Anda</h2>
        <p class="mt-3 leading-7 text-slate-700">Setiap pesanan mengikuti satu aturan refund yang berlaku ketika pesanan dibuat. Aturan tersebut dicatat pada pesanan. Jika kebijakan untuk pembelian baru berubah, aturan pada pesanan lama tetap berlaku. Pesanan yang dibuat sebelum sistem mencatat pilihan aturan mengikuti ketentuan refund berbatas hari sebelumnya.</p>
    </section>

    <section class="mt-10 rounded-2xl border border-navy/15 bg-white p-6 sm:p-8">
        <h2 class="text-2xl font-extrabold">Cara mengajukan refund</h2>
        <ol class="mt-4 list-decimal space-y-3 pl-5 leading-7 text-slate-700">
            <li>Masuk ke akun yang digunakan untuk membeli, buka <strong>Riwayat Pembelian</strong>, lalu pilih pesanan terkait dan gunakan formulir pengajuan yang tersedia. Pengajuan juga dapat dikirim melalui email atau WhatsApp resmi di bawah.</li>
            <li>Sertakan nama lengkap, email akun, nomor transaksi, nama course atau bundle, metode pembayaran, alasan, dan bukti pendukung. Untuk transfer bank, sertakan pula nama bank, nomor rekening, dan nama pemilik rekening yang sesuai dengan nama pembeli.</li>
            <li>Tim BASS Learning and Development Institute memverifikasi data dan memberikan keputusan paling lambat <strong>3 hari kerja sejak data lengkap diterima</strong>. Pengajuan tidak otomatis disetujui.</li>
            <li>Jika disetujui, pengembalian dana diproses melalui penyedia pembayaran. Waktu dana diterima mengikuti metode pembayaran dan proses penyedia pembayaran.</li>
        </ol>
        <p class="mt-5 leading-7 text-slate-700">Untuk bundle, pengajuan berlaku pada seluruh pesanan bundle, bukan per course. Setelah pengembalian dana dikonfirmasi selesai, akses yang diberikan oleh pesanan tersebut dicabut. Akses yang diperoleh secara terpisah tetap mengikuti sumber aksesnya sendiri.</p>
        <p class="mt-3 leading-7 text-slate-700">Pengajuan melalui halaman pesanan hanya tersedia untuk pembayaran yang sudah lunas. Pembayaran yang masih dalam verifikasi ditangani melalui proses verifikasi tersendiri, termasuk pengembalian dana jika pembayaran ditolak.</p>
    </section>

    <section class="mt-10 rounded-2xl border border-navy/15 bg-white p-6 sm:p-8">
        <h2 class="text-2xl font-extrabold">Nominal yang dikembalikan</h2>
        <p class="mt-4 leading-7 text-slate-700">Untuk pengajuan yang disetujui karena kendala dari pihak kami, refund penuh mencakup 100% nilai pesanan beserta biaya layanan yang tercatat. Pada penagihan ganda, yang dikembalikan adalah transaksi kelebihannya. Selisih harga akibat kesalahan sistem dapat dikembalikan sebagian; kasus refund sebagian ditangani <strong>secara manual oleh tim kami</strong> melalui kontak di bawah, karena formulir refund pada halaman pesanan memproses refund penuh.</p>
        <p class="mt-3 leading-7 text-slate-700">Dana tidak dialihkan ke pihak lain atau diganti dalam bentuk tunai. Jika sesuai, pembeli dapat membicarakan pilihan pengalihan ke course lain dengan nilai setara bersama tim kami.</p>
    </section>

    <section class="mt-10 rounded-2xl bg-navy p-6 text-white sm:p-8">
        <h2 class="text-2xl font-extrabold">Kontak pengajuan dan bantuan</h2>
        <p class="mt-3 leading-7 text-white/80">Gunakan subjek <strong>Pengajuan Refund – [Nomor Transaksi]</strong> dan sertakan data serta bukti pendukung. Untuk pembatalan course yang diketahui setelah batas pengajuan online, hubungi tim kami melalui kontak ini.</p>
        <div class="mt-5 flex flex-wrap gap-3">
            <a href="mailto:admin@basstrainingacademy.com?subject=Pengajuan%20Refund" class="inline-flex min-h-11 items-center rounded-lg bg-white px-4 font-semibold text-navy">admin@basstrainingacademy.com</a>
            <a href="https://wa.me/6282112798728" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center rounded-lg border border-white px-4 font-semibold text-white">WhatsApp +62 821-1279-8728</a>
        </div>
        <p class="mt-4 text-sm text-white/70">Jam layanan: Senin–Jumat, 09.00–17.00 WIB.</p>
    </section>

    <p class="mt-8 leading-7 text-slate-600">BASS Learning and Development Institute dapat menolak pengajuan yang terindikasi penyalahgunaan atau kecurangan. Keputusan atas pengajuan refund bersifat final. Perubahan kebijakan berlaku untuk transaksi setelah tanggal pembaruan; ketentuan yang disetujui saat pesanan dibuat tetap berlaku untuk pesanan tersebut.</p>

    <a href="{{ route('shop.index') }}" class="mt-8 inline-block font-semibold text-bass-red hover:underline">Kembali ke katalog</a>
</main>
@endsection
