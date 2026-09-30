# Mobile Web Session Handoff

Dokumen ini menjelaskan integrasi tombol **Website** pada aplikasi mobile.
Tombol tersebut membuka halaman detail katalog kursus di browser dalam keadaan
pengguna sudah login dengan akun mobile yang sama.

## Ringkasan Alur

1. Pengguna menekan tombol **Website** pada detail katalog di aplikasi mobile.
2. Mobile meminta URL sekali pakai melalui `POST /api/mobile/web-session`.
3. Server mengembalikan `data.url` yang berlaku selama 120 detik.
4. Mobile langsung membuka URL tersebut di browser.
5. Server membuat session web, lalu mengarahkan browser ke `/katalog/{course}`.

Mobile tidak perlu mengirim API token ke browser. API token hanya digunakan
saat memanggil endpoint dari aplikasi mobile.

## Endpoint

```http
POST /api/mobile/web-session
Authorization: Bearer <api_token>
Accept: application/json
Content-Type: application/json
```

### Request Body

```json
{
  "course_id": 123
}
```

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `course_id` | integer | Ya | ID kursus yang sedang dilihat pada katalog mobile |

Kursus harus tersedia di katalog. Mobile tidak mengirim URL tujuan karena URL
detail kursus dibentuk oleh server.

## Respons Berhasil

Status HTTP: `200 OK`

```json
{
  "status": "success",
  "message": "Berhasil membuat tautan website.",
  "data": {
    "url": "https://example.com/auth/handoff/TOKEN_SEKALI_PAKAI",
    "expires_in": 120
  }
}
```

| Field | Keterangan |
|---|---|
| `data.url` | URL yang harus langsung dibuka di browser |
| `data.expires_in` | Masa berlaku URL dalam detik |

URL bersifat sekali pakai. Setelah berhasil digunakan, URL yang sama tidak dapat
digunakan lagi.

## Implementasi Mobile

Pseudocode:

```text
onWebsiteButtonPressed(courseId):
    response = POST /api/mobile/web-session
        Authorization: Bearer apiToken
        JSON: { course_id: courseId }

    if response successful:
        openExternalBrowser(response.data.url)
    else:
        showError(response.message)
```

### Aturan Penting

- Buat URL hanya saat pengguna menekan tombol **Website**.
- Buka `data.url` segera setelah respons diterima.
- Jangan menyimpan URL untuk digunakan kembali.
- Jangan menambahkan `api_token` ke URL.
- Jangan mengubah atau mengambil token dari bagian `/auth/handoff/{token}`.
- Jangan mengikuti URL handoff memakai HTTP client aplikasi. URL tersebut harus
  dibuka sebagai navigasi browser agar cookie session web tersimpan di browser.
- Nonaktifkan tombol atau cegah request ganda selama proses pembuatan URL.

### Browser yang Direkomendasikan

Gunakan browser sistem atau browser tab yang berbagi cookie dengan browser
utama:

- Android: browser eksternal atau Chrome Custom Tabs.
- iOS: browser eksternal atau `SFSafariViewController`.
- Flutter: `url_launcher` dengan `LaunchMode.externalApplication`.

WebView terpisah tidak direkomendasikan karena biasanya memiliki penyimpanan
cookie sendiri. Jika WebView digunakan, session hanya tersedia di WebView
tersebut dan belum tentu tersedia saat pengguna membuka browser biasa.

## Penanganan Error

| Status | Kondisi | Tindakan Mobile |
|---|---|---|
| `401` | API token tidak ada, salah, atau tidak berlaku | Minta pengguna login ulang |
| `422` | `course_id` kosong atau bukan integer | Jangan buka browser; tampilkan error validasi |
| `422` | Kursus tidak ditemukan atau tidak tersedia di katalog | Tampilkan pesan dari server |
| `429` | Terlalu banyak request | Tampilkan pesan dan tunggu sesuai `retry_after` |
| `5xx` | Gangguan server | Tampilkan pesan gagal dan tombol coba lagi |

Contoh kursus tidak tersedia:

```json
{
  "status": "error",
  "message": "Kursus tidak ditemukan."
}
```

Contoh rate limit:

```json
{
  "status": "error",
  "message": "Terlalu banyak permintaan. Silakan coba lagi.",
  "retry_after": 30
}
```

Batas khusus pembuatan URL adalah 10 request per menit untuk setiap pengguna
dan 30 request per menit untuk setiap alamat IP.

## Perilaku di Browser

- Browser membuka `/auth/handoff/{token}` lalu menerima redirect ke halaman
  detail `/katalog/{course}`.
- Session browser yang lama akan diganti dengan akun pengguna mobile.
- Jika email belum diverifikasi, pengguna diarahkan ke verifikasi OTP. Setelah
  berhasil, pengguna kembali ke detail kursus yang sama.
- Jika token sudah digunakan, kedaluwarsa, atau tidak valid, pengguna diarahkan
  ke daftar katalog dengan pesan error.

## Keamanan

- API token mobile tidak pernah dikirim melalui URL atau disimpan di browser.
- Token handoff berupa token acak dan hanya hash SHA-256 yang disimpan server.
- Token handoff berlaku selama 120 detik dan hanya dapat dipakai sekali.
- URL tujuan dibentuk server dari `course_id`, sehingga mobile tidak dapat
  menentukan URL redirect bebas.

## Checklist Mobile

- [ ] Tombol diberi label generik **Website**.
- [ ] Endpoint dipanggil memakai bearer token mobile yang sudah ada.
- [ ] `course_id` berasal dari data detail katalog.
- [ ] Loading ditampilkan dan tombol dicegah dari klik berulang.
- [ ] `data.url` langsung dibuka di browser eksternal/browser tab.
- [ ] Error `401`, `422`, `429`, dan `5xx` ditangani.
- [ ] URL handoff tidak disimpan dan tidak dipakai ulang.

## Checklist Deployment Backend

- [ ] `APP_URL` menggunakan domain production yang benar dan memakai HTTPS.
- [ ] Jalankan `php artisan optimize:clear`.
- [ ] Jalankan `php artisan optimize`.
- [ ] Pastikan cache Laravel tersedia karena token handoff disimpan di cache.
- [ ] Uji endpoint menggunakan bearer token akun mobile production/staging.
- [ ] Uji redirect hingga halaman detail katalog dan verifikasi session login.

Tidak ada migration database untuk fitur ini.
