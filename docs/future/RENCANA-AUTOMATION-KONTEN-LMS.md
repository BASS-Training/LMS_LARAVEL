# Rencana Automation Pengisian Konten LMS

Status: draft untuk diskusi, belum diimplementasikan.  
Tanggal: 29 September 2026.

## Tujuan dan keputusan yang sudah disepakati

Membuat tool Python yang mengisi materi siap pakai ke modul yang sudah ada di LMS melalui browser menggunakan Selenium.

- Proyek automation terpisah dari proyek Laravel ini, dengan dependensi dan virtual environment sendiri.
- Target awal adalah website LMS online.
- Pengguna login manual di browser sebelum automation melanjutkan.
- Daftar pekerjaan berasal dari Excel, dengan file materi disimpan dalam folder.
- Versi pertama mendukung teks, link video, dokumen, dan gambar.
- Tidak membuat course atau modul baru pada versi pertama.
- Tidak menyusun materi dengan AI atau memasukkan quiz dan essay pada versi pertama.
- Tahap kedua menambahkan pembuatan quiz dan essay pada tool Python yang sama. Penilaian jawaban essay peserta belum termasuk cakupan.

Dokumen perencanaan disimpan di repository LMS agar bisa merujuk implementasi form. Kode automation nantinya berada di proyek terpisah. Lokasi proyek tersebut belum ditentukan.

## Acuan Selenium pada LMS

Struktur data di aplikasi adalah **Course → Lesson/modul → Content**. Istilah modul pada dokumen ini merujuk pada `Lesson`.

Selenium mengoperasikan website melalui dua acuan:

1. **URL halaman** untuk membuka course atau form tambah konten.
2. **Selector HTML** untuk menemukan kolom, pilihan jenis, editor, input file, dan tombol Simpan.

Contoh URL relatif yang tersedia:

- `/courses/{course_id}`: halaman course beserta daftar modul dan kontennya.
- `/lessons/{lesson_id}/contents/create`: form tambah konten pada modul.

Alamat lengkap dibentuk dari URL dasar website yang dikonfigurasi. Selenium tidak menulis langsung ke database.

Hasil pemeriksaan kode saat menyusun rencana:

- `ContentController::create()` menampilkan `resources/views/contents/edit.blade.php`, termasuk untuk konten baru. Jangan memakai `contents/create.blade.php` sebagai satu-satunya acuan selector.
- Form aktif menggunakan `contentForm`, field `title`, `description`, dan pilihan `type`.
- Materi teks memakai Summernote dengan field `body_text`; link video memakai `body_video`.
- Upload tersedia melalui `file_upload`, `documents[]`, dan `images[]`. Selector akhir dipilih berdasarkan jenis dan perilaku form aktif.
- Halaman course menggunakan Alpine.js; automation perlu menunggu daftar selesai dirender dan membuka bagian modul yang diperlukan.
- Akses form mengikuti login, permission, dan otorisasi course yang berlaku di LMS.

Jika tampilan website online berbeda dari kode lokal, selector perlu diverifikasi pada website target sebelum eksekusi batch.

## Usulan format Excel

Satu baris menjadi satu konten. Format berikut merupakan usulan awal untuk dikonfirmasi sebelum implementasi.

| Kolom | Wajib | Isi |
| --- | --- | --- |
| `course_id` | Ya | ID course tujuan, untuk memeriksa keanggotaan modul |
| `lesson_id` | Ya | ID modul tujuan |
| `title` | Ya | Judul konten, maksimal 255 karakter |
| `description` | Tidak | Deskripsi singkat |
| `type` | Ya | `text`, `video`, `document`, atau `image` |
| `source` | Ya | Lokasi file materi atau URL video |

Contoh input; ID hanya ilustrasi dan harus diganti dengan ID website target:

| course_id | lesson_id | title | description | type | source |
| --- | --- | --- | --- | --- | --- |
| 10 | 123 | Pengenalan Bass | Materi pembuka | text | materi/pengenalan.html |
| 10 | 123 | Teknik Dasar | Video latihan | video | https://www.youtube.com/watch?v=VIDEO_ID |
| 10 | 123 | Panduan Latihan | Panduan pendamping | document | materi/panduan.pdf |
| 10 | 123 | Posisi Jari | Contoh posisi | image | materi/posisi-jari.jpg |

Default yang diusulkan:

- Lokasi file relatif terhadap folder Excel, bukan direktori tempat script dijalankan.
- Satu file per baris untuk dokumen dan gambar; multi-file per konten ditunda.
- Teks menerima `.txt` dan `.html`. Teks biasa dikonversi menjadi paragraf HTML dengan karakter khusus di-escape; HTML mempertahankan format materi.
- File Word/PDF yang ingin diunggah menjadi konten `document`, bukan otomatis diekstrak menjadi materi teks.
- Urutan baris menentukan urutan pembuatan. Konten ditambahkan ke akhir modul melalui urutan otomatis LMS.
- Konten wajib, pengumpulan tugas nonaktif, dan absensi nonaktif. Akses dokumen memakai default form.

## Tahap kedua: automation quiz dan essay

Keputusan diskusi: dukungan quiz dan essay dikerjakan setelah pengisian materi biasa pada tahap pertama. Keduanya tetap mengisi modul yang sudah ada, memakai login manual dan mekanisme laporan/pemulihan yang sama.

Excel utama tetap menjadi daftar konten. Pada tahap kedua, kolom `type` juga menerima `quiz` dan `essay`; kolom `source` menunjuk Excel soal terpisah.

| title | type | source |
| --- | --- | --- |
| Quiz Teknik Dasar | quiz | soal/quiz-teknik.xlsx |
| Evaluasi Pemahaman | essay | soal/essay-pemahaman.xlsx |

### Quiz: jumlah soal, pilihan, dan kunci jawaban

LMS sudah menyediakan import Excel quiz. Selenium memilih jenis Quiz, memilih metode Import, mengunggah Excel melalui `quiz_excel_file`, lalu menyimpan form.

- Satu baris pada Excel soal adalah satu pertanyaan. Sepuluh baris soal untuk quiz yang sama menghasilkan sepuluh pertanyaan.
- Jumlah pilihan tiap soal mengikuti kolom `option_1` sampai `option_10` yang diisi. Importer saat ini mendukung 2–10 pilihan untuk soal pilihan ganda.
- Isi pilihan secara berurutan tanpa kolom kosong di tengah. Importer mengumpulkan pilihan yang terisi lalu menghitung ulang nomornya.
- Kunci jawaban ditentukan pengguna pada kolom `correct_answer`; format yang disarankan adalah `option_1`, `option_2`, dan seterusnya.
- Target awal automation adalah satu jawaban benar per soal pilihan ganda. Python memvalidasi kunci menunjuk pilihan yang tersedia dan memeriksa hasilnya setelah import.
- Automation tidak menebak jawaban benar atau menyusun soal sendiri.

Contoh bagian pertanyaan pada Excel quiz:

| question_text | question_type | marks | option_1 | option_2 | option_3 | option_4 | correct_answer |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Berapa jumlah senar bass standar? | multiple_choice | 10 | 3 | 4 | 5 | 6 | option_2 |
| Apa fungsi metronom? | multiple_choice | 10 | Menjaga tempo | Mengatur nada | Memperbesar suara | | option_1 |

Soal pertama memiliki empat pilihan dengan jawaban benar **4**. Soal kedua memiliki tiga pilihan dengan jawaban benar **Menjaga tempo**. Tabel ini hanya contoh kolom pertanyaan, bukan template import lengkap.

File lengkap mengikuti template download LMS dan aturan `QuizImport`. Kolom pengaturan meliputi `quiz_title`, `quiz_description`, `passing_percentage`, `time_limit`, `show_answers_after_attempt`, dan `enable_leaderboard`. `quiz_title` dan `passing_percentage` wajib; batas lulus 0–100, sedangkan durasi kosong atau 1–1440 menit. Bobot `marks` merupakan bilangan bulat minimal 1. Pengaturan quiz harus konsisten untuk seluruh barisnya.

Default yang diusulkan: **satu file sumber untuk satu quiz**, dengan `quiz_title` yang sama di semua baris. Importer bisa membuat beberapa quiz berdasarkan judul, tetapi form konten hanya menghubungkan satu hasil import; automation harus menolak file berisi beberapa judul quiz.

Importer juga mendukung `true_false`. Dukungan jenis ini pada automation belum diputuskan; contoh dan cakupan awal tahap kedua berfokus pada pilihan ganda.

### Essay: daftar pertanyaan dan pengaturan penilaian

Form LMS menyediakan daftar pertanyaan essay dinamis. Selenium memilih jenis Essay, menambahkan pertanyaan satu per satu, mengisi teks serta nilai maksimal bila diperlukan, lalu menyimpan konten.

Usulan format Excel sumber: satu baris per pertanyaan dengan kolom `question_text` dan `max_score`. Format final beserta lokasi pengaturan essay masih perlu dikonfirmasi.

Mode yang sudah tersedia pada LMS:

- `scoring`: penilaian dengan skor; pertanyaan memiliki nilai maksimal.
- `feedback_only`: review berupa feedback tanpa skor.
- `no_review`: tanpa review.

LMS juga menyediakan mode penilaian per pertanyaan (`individual`) atau keseluruhan (`overall`). Automation akan mengisi pengaturan sesuai input yang ditetapkan sebelum implementasi, bukan otomatis menilai jawaban peserta.

### Validasi tambahan tahap kedua

- Periksa file soal tidak kosong, kolom wajib, jumlah pertanyaan, serta konsistensi pengaturan sebelum membuka proses simpan.
- Untuk quiz, periksa pilihan berurutan, jumlah 2–10, bobot, dan kunci jawaban. Simulasikan aturan pencocokan importer agar tepat satu pilihan ditandai benar; teks opsi yang menyerupai penanda kunci dapat menimbulkan kecocokan tambahan.
- Setelah menyimpan quiz, periksa quiz terhubung ke konten yang tepat, jumlah/isi soal, pilihan, kunci jawaban, bobot, serta pengaturannya.
- Setelah menyimpan essay, periksa jumlah/isi pertanyaan, nilai maksimal bila berlaku, dan mode penilaian/review.
- Uji quiz dengan jumlah pilihan berbeda antarsoal, kunci tidak valid, celah pilihan, dan beberapa judul quiz dalam satu file.
- Uji essay dengan beberapa pertanyaan dan mode review yang dipilih; uji juga pemulihan setelah proses terputus.
- Hentikan batch bila hasil import atau isi soal tidak sesuai; jangan menganggap judul konten yang muncul sebagai bukti seluruh soal berhasil dibuat.

## Usulan alur dan struktur tool

Nama proyek sementara: `lms-content-automation`.

Dependensi awal: Python, Selenium, `openpyxl` untuk Excel, dan pembaca `.env` untuk konfigurasi. Chrome menjadi browser awal. Versi Python dan dependensi dikunci saat implementasi setelah mengecek lingkungan pengguna.

Komponen tool:

- Pembaca dan validator Excel serta file materi.
- Konfigurasi URL dasar, timeout, dan lokasi laporan.
- Pengendali browser serta adapter form LMS dengan selector terpusat.
- Jurnal proses dan laporan hasil untuk melanjutkan pekerjaan yang terputus.
- Template Excel, contoh materi, dan petunjuk penggunaan.

Alur yang diusulkan:

1. Validasi seluruh Excel dan file sebelum menyimpan konten apa pun. Jika input tidak valid, tampilkan daftar kesalahan dan hentikan batch.
2. Buka Chrome pada website target dan tunggu pengguna menyelesaikan login manual. Password tidak disimpan oleh tool.
3. Buka course, pastikan modul tujuan berada di course tersebut, dan periksa konten yang sudah ada.
4. Buka form tambah konten, pilih jenisnya, lalu tunggu field terkait siap.
5. Isi judul, deskripsi, dan materi. Untuk Summernote, gunakan API editor melalui JavaScript Selenium agar isi editor dan field form tersinkronisasi.
6. Untuk file, kirim lokasi file ke input upload; tidak menggunakan dialog pemilih file sistem operasi.
7. Tekan Simpan melalui form yang tersedia.
8. Periksa hasil pada modul tujuan, catat ID konten, lalu lanjutkan ke baris berikutnya.

Gunakan explicit wait berdasarkan kondisi halaman, termasuk kesiapan Alpine.js dan Summernote. Keberhasilan tidak ditentukan hanya dari klik tombol atau perpindahan URL.

## Usulan penanganan gagal dan duplikasi

- Jurnal lokal mencatat identitas website, input, status tiap baris, dan ID konten yang berhasil dibuat. Materi yang berubah terdeteksi melalui sidik input/file.
- Laporan CSV berisi hasil tiap baris dan pesan kesalahan; screenshot disimpan saat gagal di halaman pengisian.
- Saat dilanjutkan, hasil yang sudah berhasil diperiksa melalui ID kontennya sebelum dilewati.
- Judul sama dalam modul yang sama dianggap konflik pada versi pertama; tool tidak menimpa atau menggandakan konten secara otomatis.
- Jika login habis, hentikan pengisian sementara dan tunggu login manual kembali.
- Kesalahan validasi LMS dicatat dan batch dihentikan agar pengguna dapat memperbaiki input sebelum melanjutkan.
- Jika Simpan mengalami timeout, periksa ulang daftar konten dan perubahan ID sebelum memutuskan hasilnya. Jangan mengulang submit secara buta.
- Jika hasil simpan tetap ambigu, hentikan batch untuk pemeriksaan manual. Jurnal lokal tidak memberikan jaminan transaksi atomik dengan LMS.

## Validasi dan kriteria berhasil

Uji pertama menggunakan course percobaan yang bisa dikelola oleh akun pengguna.

- Teks biasa dan HTML tersimpan dengan isi serta format yang sesuai.
- Link video, dokumen, dan gambar tersimpan dan dapat dibuka dari LMS.
- Konten berada pada course dan modul yang ditentukan, dengan urutan sesuai input.
- Input tidak valid, file hilang, salah course/modul, dan akses ditolak tidak menghasilkan konten baru.
- Interupsi setelah sebagian baris berhasil dapat dilanjutkan tanpa menggandakan hasil.
- Judul konflik, perubahan input, sesi habis, dan hasil submit ambigu dilaporkan dengan jelas.
- File dan ukuran divalidasi mengikuti aturan Laravel. Batas saat ini: dokumen maksimal 100 MB dan gambar maksimal 10 MB per file; batas server online dapat lebih kecil.

Penyimpanan konten saat ini memicu notifikasi materi baru kepada peserta course. Automation mengikuti perilaku tersebut; gunakan course percobaan untuk pengujian awal.

## Hal yang masih perlu ditentukan

- URL website target dan akun pengelola untuk login manual.
- Lokasi proyek Python terpisah dan lingkungan Python/Chrome yang digunakan.
- Course dan modul percobaan serta contoh materi nyata.
- Persetujuan format kolom Excel dan format materi teks yang diusulkan.
- Kesesuaian default konflik judul, penghentian batch saat gagal, dan satu file per konten.
- Template lengkap quiz tahap kedua, pengaturan batas lulus/durasi, serta kebutuhan soal benar/salah.
- Format final Excel essay dan cara menentukan mode review, mode penilaian, serta nilai maksimal.

Belum ada script automation, instalasi dependensi, atau eksekusi ke website yang dilakukan dalam tahap penyusunan dokumen ini.
