<?php

namespace App\Support;

class CustomerServiceContent
{
    public static function data(): array
    {
        return [
            'title' => 'Customer Service',
            'subtitle' => 'Kami siap membantu Anda belajar dengan nyaman.',
            'description' => 'Hubungi tim BASS Learning and Development Institute untuk bantuan akun, akses kursus, pembelajaran, pembayaran, dan sertifikat. Pilih saluran yang paling sesuai untuk Anda.',
            'contacts' => [
                [
                    'type' => 'whatsapp',
                    'label' => 'WhatsApp',
                    'eyebrow' => 'Chat langsung',
                    'description' => 'Sampaikan kendala atau pertanyaan Anda melalui WhatsApp resmi BASS.',
                    'value' => '+62 821-1279-8728',
                    'url' => 'https://wa.me/6282112798728?text='.rawurlencode('Halo tim BASS, saya membutuhkan bantuan terkait layanan BASS.'),
                    'action_label' => 'Hubungi via WhatsApp',
                ],
                [
                    'type' => 'email',
                    'label' => 'Email',
                    'eyebrow' => 'Pertanyaan dan dokumen',
                    'description' => 'Kirim penjelasan lengkap dan lampiran yang relevan agar tim kami dapat meninjau kendala Anda.',
                    'value' => 'admin@basstrainingacademy.com',
                    'url' => 'mailto:admin@basstrainingacademy.com?subject='.rawurlencode('Permintaan Bantuan BASS'),
                    'action_label' => 'Kirim Email',
                ],
            ],
            'service_hours' => [
                'label' => 'Senin–Jumat, 09.00–17.00 WIB',
                'timezone' => 'Asia/Jakarta',
                'note' => 'Anda tetap dapat mengirim pesan di luar jam layanan. Pesan akan ditinjau pada jam operasional berikutnya.',
            ],
            'request_guidance' => [
                'title' => 'Agar kami dapat membantu lebih cepat',
                'description' => 'Sertakan informasi berikut saat menghubungi tim bantuan:',
                'items' => [
                    'Nama dan email yang digunakan pada akun BASS.',
                    'Nama kursus atau kelas yang terkait, jika ada.',
                    'Penjelasan kendala, waktu kejadian, dan langkah yang sudah dicoba.',
                    'Nomor pesanan atau referensi transaksi untuk kendala pembayaran.',
                    'Tangkapan layar atau bukti pendukung yang relevan, jika diperlukan.',
                ],
                'warning' => 'Jangan mengirim kata sandi, kode OTP, PIN, atau data kartu pembayaran. Tutupi informasi sensitif yang tidak diperlukan pada lampiran.',
            ],
            'faq' => [
                [
                    'question' => 'Bagaimana jika saya lupa kata sandi?',
                    'answer' => 'Gunakan fitur Lupa Kata Sandi pada halaman masuk, lalu ikuti petunjuk verifikasi melalui email akun Anda. Periksa juga folder spam jika email belum diterima. Jika masih terkendala, hubungi tim bantuan.',
                    'link' => ['label' => 'Buka Lupa Kata Sandi', 'url' => route('password.request')],
                ],
                [
                    'question' => 'Mengapa kursus atau materi belum dapat diakses?',
                    'answer' => 'Pastikan Anda masuk dengan akun yang terdaftar pada kursus atau kelas tersebut. Periksa status pendaftaran, pembayaran, verifikasi yang diminta, dan jadwal materi. Jika akses masih bermasalah, kirim nama kursus serta tangkapan layar kendala kepada tim bantuan.',
                ],
                [
                    'question' => 'Pembayaran sudah dilakukan, tetapi status belum berubah. Apa yang harus saya lakukan?',
                    'answer' => 'Periksa status pada halaman pesanan Anda dan pastikan instruksi pembayaran sudah diikuti. Siapkan nomor pesanan, waktu pembayaran, serta bukti pembayaran bila diperlukan, lalu hubungi tim bantuan untuk pengecekan sebelum melakukan pembayaran ulang.',
                ],
                [
                    'question' => 'Bagaimana mendapatkan atau memperbaiki sertifikat?',
                    'answer' => 'Penerbitan sertifikat mengikuti ketentuan kursus atau program yang Anda ikuti. Pastikan persyaratan pembelajaran telah terpenuhi. Jika sertifikat belum tersedia atau datanya tidak sesuai, hubungi tim bantuan dengan nama kursus dan penjelasan yang diperlukan.',
                ],
                [
                    'question' => 'Bagaimana cara mengajukan pengembalian dana?',
                    'answer' => 'Pengajuan mengikuti kebijakan refund yang berlaku untuk pesanan Anda. Baca ketentuannya terlebih dahulu, lalu gunakan formulir pengajuan pada pesanan jika tersedia atau hubungi tim bantuan dengan nomor transaksi dan alasan pengajuan.',
                    'link' => ['label' => 'Baca Kebijakan Refund', 'url' => route('shop.refund-policy')],
                ],
                [
                    'question' => 'Bagaimana cara menghapus akun atau meminta bantuan terkait data pribadi?',
                    'answer' => 'Gunakan bagian Hapus Akun pada halaman profil dan konfirmasikan kata sandi Anda. Anda juga dapat menghubungi admin melalui email dengan subjek “Permintaan Penghapusan Akun”. Sertakan email akun tanpa mengirim kata sandi. Permintaan melalui admin memerlukan verifikasi kepemilikan akun.',
                    'link' => ['label' => 'Baca Kebijakan Privasi', 'url' => route('privacy-policy')],
                ],
            ],
            'links' => [
                ['label' => 'Kebijakan Refund', 'url' => route('shop.refund-policy')],
                ['label' => 'Kebijakan Privasi', 'url' => route('privacy-policy')],
            ],
        ];
    }
}
