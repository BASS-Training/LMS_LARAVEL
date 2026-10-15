# Kontak profil dan enkripsi nomor HP

Nomor HP (`phone`) dan alamat (`address`) opsional pada profil web dan
`POST /api/mobile/profile`. Nomor HP menggunakan AES-256-GCM; alamat tidak
dienkripsi. Field yang tidak dikirim tidak mengubah nilai lama. Kirim `null`
atau string kosong untuk menghapus nilainya.

## Konfigurasi deployment

1. Buat dan simpan kunci acak 32 byte di `.env`, jalankan:

   ```sh
   php artisan sensitive-data:key
   ```

2. Jika memakai secret manager, gunakan `php artisan sensitive-data:key --show`
   dan simpan hasilnya sebagai `SENSITIVE_DATA_KEY`.
   Jangan commit kunci. Semua server aplikasi dan worker menggunakan kunci yang
   sama. Simpan cadangan kunci bersama prosedur pemulihan database; kehilangan
   kunci menyebabkan nomor HP tidak bisa didekripsi.

3. Jalankan `php artisan migrate`, lalu `php artisan config:cache` dan
   `php artisan queue:restart` jika menggunakan queue worker.

Kunci ini terpisah dari `APP_KEY`. Service menolak kunci kosong atau kunci
dengan panjang yang salah; tidak ada fallback penyimpanan plaintext.

## Format dan rotasi

`SensitiveDataEncryptionService` menyimpan `v1:` diikuti payload Laravel
Encrypter AES-256-GCM (IV acak, ciphertext, authentication tag). Cast
`EncryptedPhone` mengenkripsi saat assignment dan mendekripsi saat dibaca.
Ciphertext rusak atau format yang tidak dikenal menghasilkan `DecryptException`.

Untuk rotasi, pindahkan kunci lama ke `SENSITIVE_DATA_PREVIOUS_KEYS` (dipisahkan
koma), lalu isi `SENSITIVE_DATA_KEY` dengan kunci baru. Nomor HP yang baru
disimpan memakai kunci baru; nilai lama masih dibaca memakai kunci sebelumnya.
Jangan hapus kunci lama sebelum seluruh data yang memakainya dienkripsi ulang,
termasuk kebutuhan pemulihan backup.

Nomor HP disembunyikan dari serialisasi model umum dan secara eksplisit
ditampilkan pada payload profil/auth pengguna mobile. Activity-log middleware
menghapus field `phone` dari input dan snapshot model sebelum job diantrikan.
Nomor HP juga tidak disalin ke old input sesi saat validasi gagal.
Gunakan HTTPS untuk akses web/API; enkripsi ini melindungi penyimpanan database.
