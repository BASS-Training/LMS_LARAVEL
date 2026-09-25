# Mobile Catalog API

Dokumen ini menjelaskan cara aplikasi mobile mengonsumsi API catalog kursus.
Scope dokumen hanya mencakup daftar catalog, preview detail catalog, dan
pendaftaran course gratis dari catalog.

## Ringkasan Endpoint

| Method | Endpoint | Fungsi |
|---|---|---|
| `GET` | `/api/mobile/catalog` | Mengambil daftar course catalog |
| `GET` | `/api/mobile/catalog/{courseId}` | Mengambil preview course catalog |
| `POST` | `/api/mobile/catalog/{courseId}/daftar-gratis` | Mendaftar ke course gratis |

Semua endpoint memerlukan bearer token:

```http
Authorization: Bearer <token>
Accept: application/json
```

Token diperoleh melalui proses autentikasi mobile. Detail autentikasi berada di
luar scope dokumen ini.

## 1. Daftar Catalog

```http
GET /api/mobile/catalog
```

### Query Parameter

| Parameter | Tipe | Wajib | Aturan |
|---|---|---|---|
| `q` | string | Tidak | Pencarian judul/deskripsi, minimal 2 dan maksimal 100 karakter |
| `harga` | string | Tidak | Nilai yang diperbolehkan: `free` atau `paid` |
| `page` | integer | Tidak | Nomor halaman, minimal `1`, default `1` |
| `perPage` | integer | Tidak | Jumlah item, antara `1` sampai `50`, default `20` |

Contoh:

```http
GET /api/mobile/catalog?q=bass&harga=free&page=1&perPage=20
Authorization: Bearer <token>
Accept: application/json
```

### Respons Berhasil

```json
{
  "status": "success",
  "message": "Berhasil mengambil katalog kursus",
  "data": [
    {
      "id": "12",
      "title": "Dasar Bass Elektrik",
      "shortDescription": "Pelajari teknik dasar bermain bass.",
      "instructor": "Budi Santoso",
      "thumbnailUrl": "https://example.com/storage/courses/bass.jpg",
      "lessonsCount": 6,
      "isFree": true,
      "isPaid": false,
      "price": null,
      "priceLabel": "Gratis",
      "isEnrolled": false
    }
  ],
  "meta": {
    "showPrice": false,
    "pagination": {
      "currentPage": 1,
      "lastPage": 3,
      "perPage": 20,
      "total": 54,
      "from": 1,
      "to": 20,
      "hasMorePages": true,
      "nextPageUrl": "https://example.com/api/mobile/catalog?page=2&perPage=20",
      "previousPageUrl": null
    }
  }
}
```

### Aturan Tampilan Harga

- Gunakan `isFree` dan `isPaid` untuk menentukan jenis course.
- Tampilkan nominal `price` hanya jika `meta.showPrice` bernilai `true`.
- Jika `meta.showPrice` bernilai `false`, `price` akan bernilai `null`.
- Gunakan `priceLabel` sebagai label siap tampil: `Gratis`, `Berbayar`, atau
  nominal Rupiah ketika harga diizinkan server.
- Catalog mobile tidak menyediakan tombol atau tautan pembelian course berbayar.

### Pagination di Mobile

1. Request pertama menggunakan `page=1`.
2. Tambahkan hasil halaman berikutnya ke daftar ketika
   `meta.pagination.hasMorePages` bernilai `true`.
3. Gunakan `currentPage + 1` atau URL dari `nextPageUrl` untuk request berikutnya.
4. Saat filter atau pencarian berubah, kosongkan daftar dan mulai lagi dari
   `page=1`.
5. Cegah request halaman berikutnya jika proses loading sebelumnya belum selesai.

## 2. Preview Detail Catalog

```http
GET /api/mobile/catalog/{courseId}
Authorization: Bearer <token>
Accept: application/json
```

Contoh:

```http
GET /api/mobile/catalog/12
```

### Respons Berhasil

```json
{
  "status": "success",
  "message": "Berhasil mengambil detail katalog",
  "data": {
    "id": "12",
    "title": "Dasar Bass Elektrik",
    "shortDescription": "Pelajari teknik dasar bermain bass.",
    "instructor": "Budi Santoso",
    "thumbnailUrl": "https://example.com/storage/courses/bass.jpg",
    "lessonsCount": 2,
    "isFree": true,
    "isPaid": false,
    "price": null,
    "priceLabel": "Gratis",
    "isEnrolled": false,
    "description": "<p>Deskripsi lengkap course.</p>",
    "totalContents": 3,
    "sections": [
      {
        "id": "21",
        "sectionNumber": 1,
        "title": "Pengenalan Bass",
        "lessons": [
          {
            "id": "101",
            "title": "Mengenal Bagian Bass",
            "type": "video"
          },
          {
            "id": "102",
            "title": "Posisi Bermain",
            "type": "text"
          }
        ]
      }
    ]
  },
  "meta": {
    "showPrice": false
  }
}
```

Detail catalog hanya berisi preview kurikulum. Field `lessons` di dalam setiap
section hanya berisi ID, judul, dan tipe konten. Body materi, file, soal, dan
media pembelajaran tidak dikirim melalui endpoint catalog.

Field `description` dapat mengandung HTML. Mobile harus menampilkannya dengan
renderer HTML yang aman atau mengubahnya menjadi plain text.

## 3. Daftar Course Gratis

Endpoint ini hanya digunakan ketika `isFree` bernilai `true`.

```http
POST /api/mobile/catalog/{courseId}/daftar-gratis
Authorization: Bearer <token>
Accept: application/json
```

Request tidak memerlukan body.

### Berhasil Terdaftar

```json
{
  "status": "success",
  "message": "Berhasil bergabung dengan kursus: Dasar Bass Elektrik",
  "data": {
    "courseId": "12",
    "isEnrolled": true
  }
}
```

HTTP status: `200 OK`.

Jika pengguna sudah terdaftar, API tetap mengembalikan `200 OK`:

```json
{
  "status": "success",
  "message": "Anda sudah terdaftar di kursus ini.",
  "data": {
    "courseId": "12",
    "isEnrolled": true
  }
}
```

Setelah berhasil, mobile harus memperbarui `isEnrolled` course terkait menjadi
`true` atau memuat ulang catalog.

### Course Berbayar

```json
{
  "status": "error",
  "message": "Kursus ini tidak dapat diikuti langsung dari aplikasi."
}
```

HTTP status: `422 Unprocessable Content`.

Jangan panggil endpoint pendaftaran gratis jika `isPaid` bernilai `true`.

## Penanganan Error

### Token Tidak Valid atau Tidak Ada

```json
{
  "status": "error",
  "message": "Unauthenticated."
}
```

HTTP status: `401 Unauthorized`. Mobile harus mengarahkan pengguna ke proses
autentikasi ulang.

### Parameter Daftar Tidak Valid

```json
{
  "status": "error",
  "message": "Parameter katalog tidak valid.",
  "errors": {
    "q": [
      "The q field must be at least 2 characters."
    ],
    "perPage": [
      "The per page field must not be greater than 50."
    ]
  }
}
```

HTTP status: `422 Unprocessable Content`. Jangan bergantung pada teks detail di
dalam `errors`; gunakan nama field sebagai acuan validasi UI.

### Course Tidak Tersedia

HTTP status: `404 Not Found` dapat berarti ID tidak ditemukan, course bukan
bagian dari catalog, belum dipublikasikan, atau pengguna tidak memiliki akses
ke program tersebut. Mobile cukup menampilkan bahwa course sudah tidak tersedia
dan tidak perlu membedakan penyebabnya.

## Alur Konsumsi yang Disarankan

1. Buka layar catalog dan request halaman pertama.
2. Render setiap item dari `data` sebagai kartu course.
3. Gunakan debounce sebelum mengirim pencarian dan jangan kirim `q` sebelum
   panjangnya mencapai 2 karakter.
4. Muat halaman berikutnya berdasarkan `meta.pagination.hasMorePages`.
5. Ketika kartu dipilih, request preview detail menggunakan ID course.
6. Jika course gratis dan belum terdaftar, tampilkan aksi daftar gratis.
7. Setelah pendaftaran berhasil, ubah status lokal menjadi terdaftar atau refresh
   halaman catalog.

## Catatan Tipe Data

- Semua ID dikirim sebagai string. Model mobile sebaiknya menggunakan `String`.
- `thumbnailUrl`, `price`, `from`, `to`, `nextPageUrl`, dan `previousPageUrl`
  dapat bernilai `null`.
- `data` selalu berupa array, termasuk ketika catalog kosong.
- `sections` dan `lessons` selalu berupa array.
- Mobile harus mengabaikan field JSON yang belum dikenal agar penambahan field
  di masa depan tidak merusak parsing.
