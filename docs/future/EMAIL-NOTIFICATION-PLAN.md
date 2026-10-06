# Pengembangan Email Notification LMS BASS

> Status: **email pembayaran sudah diimplementasikan**. Sertifikat, AVPN, dan fase lanjutan masih rencana.
> Prinsip: email dipakai untuk transaksi dan milestone penting dengan frekuensi rendah. Percakapan tetap memakai notifikasi in-app.

## 1. Kondisi saat ini

| Kanal | Penggunaan |
|-------|------------|
| Email | OTP, reset password, dan status pembayaran |
| Database (in-app) | Chat, diskusi, penilaian, export selesai, materi baru, dan status refund |

Email pembayaran dikirim kepada pemilik pesanan ketika:

1. Midtrans mengonfirmasi pembayaran dan akses langsung dibuka (`paid`).
2. Midtrans mengonfirmasi pembayaran tetapi akses menunggu verifikasi admin (`awaiting_verification`).
3. Admin menyetujui verifikasi dan akses dibuka (`approved`).
4. Admin menolak verifikasi (`rejected`), termasuk alasan penolakan.

Setiap email memuat kode pesanan, produk, nominal, dan tautan ke halaman pesanan. Email selain penolakan juga memuat tautan unduh invoice. Tautan tersebut memakai route yang sudah ada; pengguna harus login sebagai pemilik pesanan atau super-admin untuk mengunduh invoice. Tidak ada lampiran PDF atau notifikasi pembayaran in-app pada tahap ini.

Implementasi memakai `PaymentStatusNotification` (`ShouldQueue`, kanal `mail` saja) dengan isi peristiwa yang disimpan saat transisi status. `OrderService` mengantrekan email hanya setelah transisi berhasil, dan job ditandai `afterCommit` agar worker tidak membaca transaksi yang belum selesai. Guard status yang sudah ada mencegah email ganda akibat webhook atau tindakan admin berulang. Email tidak dikirim untuk order pending, gagal, kedaluwarsa, atau dibatalkan.

Queue proyek menggunakan `QUEUE_CONNECTION=database` pada `.env.example`; worker harus berjalan pada lingkungan yang mengirim email. OTP tetap memakai pengiriman langsung karena kode verifikasinya sensitif waktu. Pengiriman email yang gagal diproses melalui mekanisme failed jobs Laravel tanpa mengubah status pembayaran.

## 2. Pengembangan berikutnya

### Sertifikat terbit

- Kirim email setelah record dan PDF sertifikat selesai dibuat; sertakan tautan sertifikat dan kode verifikasi publik.
- Audit seluruh jalur pembuatan: `CertificateController`, `ContentController`, `ProgressController`, API mobile, serta alur bulk/force complete yang memanggil jalur tersebut. Satukan pemicu agar penerbitan yang sama tidak mengirim email berulang.
- Gunakan notifikasi queued dan tautan yang tetap memeriksa hak akses pengguna.

### Status AVPN

- Kirim hasil approved/rejected, alasan bila ada, dan langkah berikutnya setelah perubahan status berhasil.
- Cakup aksi satu peserta dan batch di `Admin/ParticipantController`; hanya peserta yang benar-benar berpindah dari `pending` menerima email.

### Fase lanjutan

- Hasil penilaian essay, studi kasus, dan dokumen: email hanya kepada peserta untuk hasil akhir, bukan setiap submission ke instruktur.
- Export peserta selesai: gunakan lampiran atau tautan unduh yang aman karena file export berada pada disk privat.
- Pengingat jadwal: tambahkan scheduler dan aturan deduplikasi untuk H-1/H-0.
- Pertimbangkan preferensi email pengguna sebelum menambah kategori non-transaksional. Tentukan dahulu apakah email transaksional dapat dimatikan.

Diskusi baru, balasan diskusi, hasil quiz langsung, dan setiap unggahan materi tetap cukup melalui aplikasi karena frekuensi atau konteksnya.

## 3. Pengujian dan operasi

- Tes pembayaran mencakup course dan bundle, pembayaran otomatis, menunggu verifikasi, persetujuan, penolakan beserta alasan, webhook berulang, serta nominal tidak valid.
- Tes fase berikutnya perlu mencakup jalur satuan dan batch, penerima yang tepat, transaksi gagal, hak akses tautan, serta tidak adanya email ganda.
- Pada lingkungan lokal, gunakan `MAIL_MAILER=log` atau mailer pengujian. Pada produksi, periksa worker queue dan failed jobs sebelum mengaktifkan pengiriman email baru.
