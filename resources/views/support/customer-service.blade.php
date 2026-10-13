@extends('layouts.public')

@section('title', 'Customer Service — Layanan Pelanggan')
@section('meta_description', 'Hubungi layanan pelanggan BASS melalui WhatsApp atau email untuk bantuan akun, akses kursus, pembayaran, sertifikat, dan pengembalian dana.')
@section('page_language', 'id')

@section('content')
<article class="mx-auto max-w-5xl px-4 py-12 text-navy sm:px-6">
    <header class="max-w-3xl">
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-bass-red">Pusat bantuan BASS</p>
        <h1 class="mt-2 text-3xl font-extrabold sm:text-4xl">Customer Service</h1>
        <p class="mt-3 text-lg font-semibold">Kami siap membantu Anda belajar dengan nyaman.</p>
        <p class="mt-4 leading-7 text-slate-600">Hubungi tim BASS Learning and Development Institute untuk bantuan akun, akses kursus, pembelajaran, pembayaran, dan sertifikat. Pilih saluran yang paling sesuai untuk Anda.</p>
    </header>

    <section aria-labelledby="contact-heading" class="mt-8">
        <h2 id="contact-heading" class="text-2xl font-extrabold">Hubungi tim kami</h2>
        <div class="mt-5 grid gap-5 md:grid-cols-2">
            <div class="flex flex-col rounded-2xl border-2 border-navy bg-white p-6 shadow-[5px_5px_0_#F6C945] sm:p-8">
                <span class="text-xs font-bold uppercase tracking-widest text-bass-red">Chat langsung</span>
                <h3 class="mt-3 text-xl font-extrabold">WhatsApp</h3>
                <p class="mt-3 leading-7 text-slate-600">Sampaikan kendala atau pertanyaan Anda melalui WhatsApp resmi BASS.</p>
                <p class="mt-4 font-semibold">+62 821-1279-8728</p>
                <a href="https://wa.me/6282112798728?text={{ rawurlencode('Halo tim BASS, saya membutuhkan bantuan terkait layanan website BASS.') }}" target="_blank" rel="noopener noreferrer" class="mt-6 inline-flex min-h-11 items-center justify-center rounded-lg bg-navy px-5 py-3 text-center font-bold text-white hover:bg-navy/90">Hubungi via WhatsApp<span class="sr-only"> (dibuka di tab baru)</span></a>
            </div>
            <div class="flex flex-col rounded-2xl border-2 border-navy bg-white p-6 shadow-[5px_5px_0_#DA1E1E] sm:p-8">
                <span class="text-xs font-bold uppercase tracking-widest text-bass-red">Pertanyaan dan dokumen</span>
                <h3 class="mt-3 text-xl font-extrabold">Email</h3>
                <p class="mt-3 leading-7 text-slate-600">Kirim penjelasan lengkap dan lampiran yang relevan agar tim kami dapat meninjau kendala Anda.</p>
                <p class="mt-4 break-all font-semibold">admin@basstrainingacademy.com</p>
                <a href="mailto:admin@basstrainingacademy.com?subject={{ rawurlencode('Permintaan Bantuan Website BASS') }}" class="mt-6 inline-flex min-h-11 items-center justify-center rounded-lg bg-bass-red px-5 py-3 text-center font-bold text-white hover:bg-bass-red/90">Kirim Email</a>
            </div>
        </div>
        <div class="mt-6 rounded-xl border border-navy/15 bg-white px-5 py-4">
            <p class="font-bold">Jam layanan: Senin–Jumat, 09.00–17.00 WIB</p>
            <p class="mt-2 text-sm leading-6 text-slate-600">Anda tetap dapat mengirim pesan di luar jam layanan. Pesan akan ditinjau pada jam operasional berikutnya.</p>
        </div>
    </section>

    <section aria-labelledby="request-heading" class="mt-10 rounded-2xl bg-navy p-6 text-white sm:p-8">
        <h2 id="request-heading" class="text-2xl font-extrabold">Agar kami dapat membantu lebih cepat</h2>
        <p class="mt-3 leading-7 text-white/80">Sertakan informasi berikut saat menghubungi tim bantuan:</p>
        <ul class="mt-4 list-disc space-y-2 pl-5 leading-7 text-white/90">
            <li>Nama dan email yang digunakan pada akun BASS.</li>
            <li>Nama kursus atau kelas yang terkait, jika ada.</li>
            <li>Penjelasan kendala, waktu kejadian, dan langkah yang sudah dicoba.</li>
            <li>Nomor pesanan atau referensi transaksi untuk kendala pembayaran.</li>
            <li>Tangkapan layar atau bukti pendukung yang relevan, jika diperlukan.</li>
        </ul>
        <p class="mt-5 rounded-lg border border-white/20 px-4 py-3 text-sm leading-6">Jangan mengirim kata sandi, kode OTP, PIN, atau data kartu pembayaran. Tutupi informasi sensitif yang tidak diperlukan pada lampiran.</p>
    </section>

    <section aria-labelledby="faq-heading" class="mt-10">
        <h2 id="faq-heading" class="text-2xl font-extrabold">Pertanyaan yang sering diajukan</h2>
        <p class="mt-3 leading-7 text-slate-600">Temukan langkah awal untuk kendala yang umum terjadi.</p>
        <div class="mt-5 space-y-3">
            <details class="group rounded-xl border border-navy/15 bg-white p-5">
                <summary class="cursor-pointer font-bold">Bagaimana jika saya lupa kata sandi?</summary>
                <p class="mt-4 leading-7 text-slate-700">Gunakan fitur Lupa Kata Sandi pada halaman masuk, lalu ikuti petunjuk verifikasi melalui email akun Anda. Periksa juga folder spam jika email belum diterima. Jika masih terkendala, hubungi tim bantuan.</p>
                @guest
                    <a href="{{ route('password.request') }}" class="mt-3 inline-block font-semibold text-bass-red hover:underline">Buka Lupa Kata Sandi</a>
                @endguest
            </details>
            <details class="group rounded-xl border border-navy/15 bg-white p-5">
                <summary class="cursor-pointer font-bold">Mengapa kursus atau materi belum dapat diakses?</summary>
                <p class="mt-4 leading-7 text-slate-700">Pastikan Anda masuk dengan akun yang terdaftar pada kursus atau kelas tersebut. Periksa status pendaftaran, pembayaran, verifikasi yang diminta, dan jadwal materi. Jika akses masih bermasalah, kirim nama kursus serta tangkapan layar kendala kepada tim bantuan.</p>
            </details>
            <details class="group rounded-xl border border-navy/15 bg-white p-5">
                <summary class="cursor-pointer font-bold">Pembayaran sudah dilakukan, tetapi status belum berubah. Apa yang harus saya lakukan?</summary>
                <p class="mt-4 leading-7 text-slate-700">Periksa status pada halaman pesanan Anda dan pastikan instruksi pembayaran sudah diikuti. Siapkan nomor pesanan, waktu pembayaran, serta bukti pembayaran bila diperlukan, lalu hubungi tim bantuan untuk pengecekan sebelum melakukan pembayaran ulang.</p>
            </details>
            <details class="group rounded-xl border border-navy/15 bg-white p-5">
                <summary class="cursor-pointer font-bold">Bagaimana mendapatkan atau memperbaiki sertifikat?</summary>
                <p class="mt-4 leading-7 text-slate-700">Penerbitan sertifikat mengikuti ketentuan kursus atau program yang Anda ikuti. Pastikan persyaratan pembelajaran telah terpenuhi. Jika sertifikat belum tersedia atau datanya tidak sesuai, hubungi tim bantuan dengan nama kursus dan penjelasan yang diperlukan.</p>
            </details>
            <details class="group rounded-xl border border-navy/15 bg-white p-5">
                <summary class="cursor-pointer font-bold">Bagaimana cara mengajukan pengembalian dana?</summary>
                <p class="mt-4 leading-7 text-slate-700">Pengajuan mengikuti kebijakan refund yang berlaku untuk pesanan Anda. Baca ketentuannya terlebih dahulu, lalu gunakan formulir pengajuan pada pesanan jika tersedia atau hubungi tim bantuan dengan nomor transaksi dan alasan pengajuan.</p>
                <a href="{{ route('shop.refund-policy') }}" class="mt-3 inline-block font-semibold text-bass-red hover:underline">Baca Kebijakan Refund</a>
            </details>
            <details class="group rounded-xl border border-navy/15 bg-white p-5">
                <summary class="cursor-pointer font-bold">Bagaimana cara menghapus akun atau meminta bantuan terkait data pribadi?</summary>
                <p class="mt-4 leading-7 text-slate-700">Gunakan bagian Hapus Akun pada halaman profil dan konfirmasikan kata sandi Anda. Anda juga dapat menghubungi admin melalui email dengan subjek “Permintaan Penghapusan Akun”. Sertakan email akun tanpa mengirim kata sandi. Permintaan melalui admin memerlukan verifikasi kepemilikan akun.</p>
                <a href="{{ route('privacy-policy') }}" class="mt-3 inline-block font-semibold text-bass-red hover:underline">Baca Kebijakan Privasi</a>
            </details>
        </div>
    </section>

    <nav aria-label="Tautan bantuan terkait" class="mt-10 flex flex-wrap gap-x-6 gap-y-3 border-t border-navy/15 pt-6 text-sm font-semibold">
        <a href="{{ route('shop.refund-policy') }}" class="text-bass-red hover:underline">Kebijakan Refund</a>
        <a href="{{ route('privacy-policy') }}" class="text-bass-red hover:underline">Kebijakan Privasi</a>
        <a href="{{ route('welcome') }}" class="hover:text-bass-red">Kembali ke beranda</a>
    </nav>
</article>
@endsection
