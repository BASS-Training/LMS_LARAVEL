# API Mobile: Privasi, Customer Service, dan Hapus Akun

Kirim `Accept: application/json`. Base path: `/api/mobile`.

## Kebijakan privasi

`GET /privacy-policy?lang=id` — publik, tanpa token.

Bahasa: `id` (default) atau `en`. Nilai lain menggunakan Indonesia.
Respons `200` memiliki format:

```json
{
  "status": "success",
  "data": {
    "language": "id",
    "url": "https://example.com/kebijakan-privasi?lang=id",
    "policy": {
      "title": "Kebijakan Privasi",
      "updated": "Terakhir diperbarui: 13 Oktober 2026",
      "intro": "...",
      "sections": {
        "scope": { "title": "1. Pengelola dan cakupan", "paragraphs": ["..."] }
      }
    },
    "contact_email": "admin@basstrainingacademy.com"
  }
}
```

`policy` berisi seluruh terjemahan yang digunakan website, bukan hanya field
contoh di atas. Render `sections` berurutan; bagian dapat memiliki `paragraphs`,
`items`, atau keduanya. Konten berupa plain text dan saat ini menjelaskan website BASS.

## Customer Service

`GET /customer-service` — publik, tanpa token.

Respons `200`: `{ "status": "success", "data": { ... } }`.

| Field data | Isi |
| --- | --- |
| `title`, `subtitle`, `description` | Pengantar |
| `contacts[]` | `type`, `label`, `eyebrow`, `description`, `value`, `url`, `action_label` |
| `service_hours` | `label`, `timezone` (Asia/Jakarta), `note` |
| `request_guidance` | `title`, `description`, `items[]`, `warning` |
| `faq[]` | `question`, `answer`, opsional `link` (`label`, `url`) |
| `links[]` | `label`, `url` |

Buka URL kontak menggunakan launcher perangkat. Tautan FAQ adalah URL website;
mobile dapat memetakan tautan privasi/lupa kata sandi ke layar native.
Sumber konten bersama website: `App\Support\CustomerServiceContent`.

## Self-delete akun

`DELETE /profile` — wajib `Authorization: Bearer <token>`.
Kirim `Content-Type: application/json`:

```json
{ "password": "kata-sandi-saat-ini" }
```

Hanya akun pemilik token yang dihapus, tanpa parameter ID sasaran. Kata sandi
diperiksa terhadap akun tersebut. Penghapusan permanen mengikuti cascade dan
null-on-delete database seperti fitur web. Sesi web dan token reset password
dibersihkan dalam transaksi yang sama. Token mobile lama tidak lagi valid.

Respons `200`:

```json
{ "status": "success", "message": "Akun Anda berhasil dihapus." }
```

- `401`: token kosong/tidak valid.
- `422`: kata sandi kosong/salah, lihat `errors.password`.
- `429`: maksimal 5 percobaan per menit per token, selain limiter API umum.

Flutter: tampilkan konfirmasi dan input kata sandi. Setelah `200`, hapus token
serta data akun lokal, lalu kembali ke login. Pada error jangan menampilkan
penghapusan sebagai berhasil.
