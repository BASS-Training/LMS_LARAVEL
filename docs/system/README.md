# Dokumentasi Sistem LMS

Dokumentasi ini menggambarkan kondisi implementasi repository saat ini. Sumber utama adalah migration, model Eloquent, route, middleware, service, job, event, observer, dan konfigurasi aplikasi.

## Dokumen

| Dokumen | Isi |
|---|---|
| [ERD.md](ERD.md) | Entity Relationship Diagram per domain, kamus tabel, dan catatan integritas data |
| [SYSTEM-DESIGN.md](SYSTEM-DESIGN.md) | Arsitektur, komponen, alur kritis, keamanan, deployment, dan risiko teknis |
| [COURSE-COMMERCE-ROADMAP.md](COURSE-COMMERCE-ROADMAP.md) | Rencana pengembangan penjualan course tanpa mengubah alur pembelajaran yang ada |

## Ringkasan Sistem

- Platform: Laravel 12, PHP 8.2+, Blade, Alpine.js, Tailwind CSS, Vite.
- Kanal: aplikasi web, mobile JSON API, halaman publik, dan WebSocket chat.
- Role utama: `super-admin`, `instructor`, `event-organizer`, dan `participant`.
- Hierarki pembelajaran: `Course -> Lesson -> Content`, dengan `CourseClass` sebagai batch/periode.
- Persistence: database relasional untuk data bisnis, session, cache, queue, dan notification.
- Integrasi: Midtrans, email, Laravel Reverb, PDF, serta ekspor spreadsheet.

## Cara Membaca Diagram

Diagram menggunakan sintaks Mermaid. GitHub dan banyak editor Markdown dapat merender diagram secara langsung. Jika renderer tidak tersedia, isi diagram tetap dapat dibaca sebagai definisi relasi tekstual.

## Batasan

- ERD merepresentasikan hasil akhir seluruh migration, bukan dump dari database production.
- Kolom audit `created_at` dan `updated_at` tidak selalu ditampilkan agar diagram tetap terbaca.
- Tabel framework seperti queue dan cache dicatat dalam katalog, tetapi tidak dimasukkan ke diagram domain bisnis.
- Relasi polymorphic Laravel ditampilkan sebagai relasi logis karena tidak memiliki foreign key langsung ke satu tabel.

Terakhir diperbarui berdasarkan source code: 1 Oktober 2026.
