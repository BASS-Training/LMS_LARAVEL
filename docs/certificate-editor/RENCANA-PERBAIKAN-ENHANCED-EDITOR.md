# Rencana Perbaikan Canvas Sertifikat Enhanced Editor

Status: implementasi kode dan automated test selesai; validasi browser/visual PDF masih diperlukan.
Tanggal: 29 September 2026.

## Status implementasi 7 Oktober 2026

- Logika create/edit telah disatukan di `resources/js/enhanced-certificate-editor.js`.
- Halaman memakai ID stabil, metadata Enhanced versi 2, dan ukuran desain 1123 x 794.
- Drag/resize memakai delta layar yang dikonversi berdasarkan zoom, akumulasi gesture, snap posisi absolut, dan pembatas canvas bersama.
- Shortcut aman terhadap input/contenteditable dan listener dibersihkan saat komponen dihancurkan.
- Upload background dipetakan berdasarkan ID halaman lalu dikirim dengan indeks eksplisit; `null` dipertahankan sebagai penghapusan eksplisit.
- Controller memvalidasi kepemilikan path, menyimpan secara transaksional, dan baru membersihkan file lama setelah update berhasil.
- Preview dan renderer memakai dimensi, posisi background, serta kontrak box teks yang konsisten sambil mempertahankan template Advanced.
- Unit test JavaScript dan feature test persistence/background telah ditambahkan.
- Pengujian browser interaktif pada beberapa tingkat zoom dan perbandingan visual hasil Dompdf belum diotomatisasi.

## Tujuan

Menstabilkan enhanced editor yang digunakan untuk membuat dan mengedit template sertifikat. Prioritasnya adalah mencegah kehilangan elemen/latar, memperbaiki koordinat X/Y dan interaksi drag/resize, serta menyelaraskan canvas dengan preview dan hasil sertifikat.

Perubahan harus tetap dapat membaca template lama. Tidak mengganti editor dengan Fabric/Konva dan tidak mendesain ulang seluruh antarmuka.

## Acuan implementasi

- View utama: `resources/views/admin/certificate-templates/enhanced-create.blade.php` dan `enhanced-edit.blade.php`.
- Controller penyimpanan: `app/Http/Controllers/Admin/CertificateTemplateController.php`.
- Rendering: preview internal enhanced editor, `resources/views/admin/certificate-templates/preview.blade.php`, dan `resources/views/certificates/template-render.blade.php`.
- Editor memakai Alpine.js, InteractJS, dan elemen HTML dengan posisi absolut. Ukuran desain enhanced saat ini adalah **1123 × 794 px**, A4 landscape.
- `public/js/advanced-certificate-editor.js` merupakan implementasi editor advanced, bukan pusat perbaikan enhanced.

## Temuan dan tingkat verifikasi

| Masalah | Dampak | Bukti saat audit |
| --- | --- | --- |
| Shortcut Delete tidak memeriksa fokus input | Menghapus karakter dapat menghapus elemen terpilih | Direproduksi lewat simulasi handler JavaScript |
| Snap membulatkan delta setiap event | Drag pelan dapat tidak bergerak | Simulasi 20 event × 2 px menghasilkan offset 0 px |
| Drag/resize tidak mengonversi koordinat berdasarkan zoom | Gerakan dan ukuran tidak sesuai pointer pada zoom selain 100% | Terlihat dari perhitungan kode; perlu uji browser |
| Snap drag memakai offset, bukan posisi akhir | Elemen tidak menempel pada grid jika posisi awal bukan kelipatan grid | Terlihat dari perhitungan kode |
| Resize dan input properti belum membatasi ukuran/posisi secara konsisten | Elemen dapat keluar canvas; resize besar dapat melewati batas | Terlihat dari perhitungan kode |
| Duplikasi selalu menambah X/Y sebesar 20 | Salinan dekat tepi dapat keluar canvas | Terlihat dari perhitungan kode |
| Watcher perubahan data memanggil `unset()` dan memasang ulang InteractJS | Berpotensi memutus drag/resize yang sedang berlangsung | Risiko dari lifecycle kode; belum direproduksi di browser |
| Backend memulihkan background lama berdasarkan indeks | Latar yang dihapus muncul kembali; latar dapat tertukar setelah halaman dihapus | Terlihat dari alur update controller |
| Preview internal portrait, canvas landscape | Elemen terpotong atau tampil pada posisi yang tidak sesuai | Ukuran berbeda di kode |
| Format teks/latar tidak konsisten antar-renderer | Hasil preview/sertifikat berbeda dari desain | Perbedaan CSS dan penerapan properti di kode |
| Create memanggil `init()` otomatis dan lewat `x-init` | Dua halaman awal dan listener keyboard ganda | Simulasi dua pemanggilan menghasilkan dua halaman dan dua listener; perilaku otomatis dikonfirmasi dokumentasi Alpine |

Audit awal belum mencakup pengujian browser menyeluruh atau perbandingan visual PDF. Temuan berbasis simulasi tidak dianggap sebagai pengujian end-to-end.

## Rencana implementasi

### 1. Koordinat X/Y, ukuran, zoom, dan snap

- Gunakan posisi dan ukuran dalam **pixel desain**, bukan pixel layar. Zoom hanya mengubah tampilan, tidak mengubah data tersimpan.
- X/Y dihitung dari sudut kiri atas halaman: X ke kanan, Y ke bawah.
- Simpan posisi awal dan akumulasi gerakan mentah selama gesture; konversi delta layar dengan membaginya terhadap zoom yang berlaku.
- Untuk drag, hitung posisi absolut dari posisi awal + total delta desain. Snap posisi absolut ke grid 10 px, bukan membulatkan delta setiap event.
- Gerakan kecil tetap terakumulasi hingga melewati ambang snap; jangan menghilangkan sisa delta.
- Untuk resize, gunakan batas awal elemen dan akumulasi delta dalam koordinat desain. Hindari menggunakan `event.rect.width/height` layar sebagai ukuran desain langsung.
- Resize kiri/atas mengubah X/Y sambil mempertahankan tepi berlawanan. Resize kanan/bawah mempertahankan X/Y. Snap tepi yang sedang digerakkan sebelum menerapkan batas ukuran.
- Gunakan satu fungsi pembatas untuk gesture, input properti, dan duplikasi: ukuran minimal 20 px, maksimal ukuran halaman; `0 <= x <= pageWidth - width` dan `0 <= y <= pageHeight - height`.
- Batas canvas memiliki prioritas atas grid; elemen tetap boleh tepat pada tepi halaman meskipun koordinat tersebut bukan kelipatan 10.
- Input angka kosong atau tidak valid selama mengetik tidak langsung menimpa model dengan nilai rusak. Normalisasi saat perubahan dikonfirmasi/blur; sebelum simpan, semua nilai wajib finite.
- Duplikasi tetap memakai offset 20 px tetapi posisi hasil dibatasi agar seluruh salinan berada dalam halaman.
- Lindungi kompatibilitas layout lama: jangan mengubah posisi/ukuran template tersimpan hanya karena dibuka. Terapkan batas baru pada perubahan pengguna; nilai lama yang tidak valid dilaporkan sebelum penyimpanan.

### 2. Lifecycle interaksi dan shortcut

- Pisahkan logika editor yang sama pada create/edit menjadi satu modul JavaScript agar perbaikan tidak menyimpang antara kedua halaman.
- Inisialisasi komponen tepat satu kali. Hilangkan pemanggilan `init()` ganda pada create.
- Pasang InteractJS sekali pada komponen; perubahan X/Y, ukuran, warna, teks, atau selection tidak menjalankan `unset()` selama gesture.
- Identifikasi target melalui ID halaman/elemen yang stabil, bukan hanya indeks array yang dapat berubah.
- Bersihkan listener dan InteractJS ketika komponen dihancurkan. Gunakan `$nextTick` untuk kesiapan DOM, tanpa tumpukan timer pemasangan ulang.
- Batasi shortcut pada editor aktif. Abaikan Delete dan duplikasi ketika fokus ada di input, textarea, select, atau contenteditable; jangan memproses shortcut saat IME sedang composing.
- Reset selection ketika halaman diganti/ditambahkan/dihapus. Render panel properti hanya jika elemen terpilih valid; `x-show` saja tidak melindungi ekspresi yang membaca properti dari `null`.
- Nonaktifkan transisi posisi/ukuran selama drag/resize dan gunakan `touch-action: none` pada target interaksi.

### 3. Halaman dan penyimpanan background

- Berikan ID halaman stabil pada state enhanced. Saat membaca template lama tanpa ID, tambahkan ID tanpa mengubah urutan/isi desain.
- Simpan pending file background berdasarkan ID halaman, terpisah dari elemen input DOM. Menghapus halaman tidak memindahkan file ke halaman lain.
- Saat submit, bangun payload menggunakan urutan halaman terkini dan file dengan indeks eksplisit `backgrounds[index]`, bukan mengandalkan urutan input `backgrounds[]`.
- Background yang dipertahankan mengirim path yang benar untuk halaman tersebut; background yang dihapus mengirim `background_image_path: null`; file baru menggantikan path sesuai indeks payload terbaru.
- Backend membedakan path yang tidak dikirim dengan path `null`. Jangan memulihkan path lama berdasarkan indeks ketika enhanced secara eksplisit menghapus atau memindahkan halaman.
- Pertahankan kompatibilitas request editor lain: fallback lama hanya berlaku jika field path benar-benar tidak dikirim. Path existing yang diterima harus berasal dari template yang sedang diedit.
- Validasi gambar konsisten pada seluruh jalur upload: format sesuai backend dan batas 5 MB. Lepaskan object URL saat file diganti, halaman dihapus, atau editor ditutup.
- Hapus file lama yang tidak dipakai setelah update data berhasil; kegagalan penyimpanan tidak boleh menghilangkan background template sebelumnya.
- Siapkan payload dari state terbaru ketika submit dan pertahankan validasi form nama template. Cegah submit ganda dan submit ketika gesture masih aktif.

### 4. Preview dan hasil sertifikat

- Sertakan ukuran halaman enhanced `width: 1123` dan `height: 794` dalam payload. Template enhanced lama tanpa ukuran menggunakan default landscape saat dimuat editor.
- Preview internal memakai dimensi halaman yang sama dengan canvas, bukan portrait hardcoded.
- Terapkan `backgroundSize` dan `backgroundPosition` secara konsisten. Canvas edit sudah memiliki binding properti tersebut; preview internal dan renderer lainnya masih perlu diselaraskan.
- Samakan ukuran box teks, font, alignment horizontal/vertikal, line-height, padding, dan perlakuan baris baru. Untuk enhanced, gunakan tampilan canvas saat ini sebagai acuan: padding 8 px, line-height 1.4, alignment vertikal tengah, dan box-sizing border-box.
- Periksa dukungan CSS pada renderer PDF yang digunakan. Jika perlu pendekatan berbeda, pertahankan hasil posisi/box yang setara dan dokumentasikan selisih font yang tidak dapat dihilangkan.
- Preview menampilkan teks sebagai teks, dengan escaping; jangan menyisipkan `element.content` mentah sebagai HTML.
- Sediakan penanganan popup yang diblokir pada preview internal.
- Jangan memaksakan ukuran landscape enhanced pada template advanced yang memiliki dimensi eksplisit berbeda. Perubahan renderer bersama harus diuji dengan template lama/editor lain.

## Urutan pengerjaan

1. Reproduksi kasus utama pada browser dengan template percobaan; catat zoom, posisi awal, dan hasil aktual.
2. Perbaiki lifecycle, selection, shortcut, serta perhitungan X/Y dan resize pada create/edit.
3. Perbaiki identitas halaman, pemetaan file, dan semantics hapus background pada penyimpanan.
4. Selaraskan preview dan renderer sertifikat, lalu uji regresi template lama.

Tidak ada migrasi database yang direncanakan; metadata tambahan tetap berada dalam JSON `layout_data`.

## Pengujian dan kriteria penerimaan

### Unit dan feature

- Matematika drag: pada zoom 50%, delta layar 20 px menjadi delta desain 40 px; zoom 100% dan 200% juga diuji.
- Snap: beberapa delta kecil terakumulasi; posisi awal bukan kelipatan grid tetap menghasilkan posisi akhir yang sesuai grid kecuali terbatasi tepi.
- Resize: delapan arah, batas minimum/maksimum, dan tepi berlawanan tetap konsisten.
- Input properti dan duplikasi: nilai tidak valid tidak merusak state; perubahan valid tetap berada di dalam canvas.
- Backend dengan storage palsu: simpan/hapus/ganti background, hapus halaman pertama/tengah, upload hanya halaman kedua, dan pertahankan path halaman yang benar.
- Verifikasi halaman dengan path `null` tidak dipulihkan; request lama tanpa field path tetap kompatibel.
- Verifikasi ID, elemen, urutan halaman, dan ukuran desain bertahan setelah simpan lalu muat ulang.

### Browser dan visual

- Create membuka tepat satu halaman. Tidak ada error console ketika belum memilih elemen, setelah deselect, dan ketika berpindah halaman.
- Drag lambat/cepat dan resize pada zoom 50%, 100%, dan 200%, dengan snap aktif maupun nonaktif; pointer tetap mengikuti gesture tanpa berhenti akibat pemasangan ulang.
- Mengedit angka/teks lalu menekan Delete hanya mengubah input, bukan menghapus elemen sertifikat.
- Tambah, salin, hapus elemen; tambah/ganti/hapus halaman; pastikan selection selalu menunjuk elemen pada halaman aktif.
- Hapus background lalu simpan dan buka kembali; background tetap terhapus. Penghapusan halaman tidak menukar background lainnya.
- Bandingkan canvas, preview internal, preview tersimpan, dan PDF dengan teks di sudut/tengah, font berbeda, beberapa baris, serta cover/contain/stretch/original size.
- Uji minimal satu template lama tanpa ukuran/ID halaman dan satu template advanced berdimensi eksplisit.

Pekerjaan dinyatakan selesai setelah interaksi stabil, data bertahan dengan benar setelah muat ulang, dan perbedaan visual material antara desain dan hasil sertifikat sudah diselesaikan atau dijelaskan dengan bukti.

## Batas rencana

- Undo/redo, rotasi elemen, multi-selection, dan fitur desain baru tidak termasuk perbaikan ini.
- Template/layout produksi tidak diubah untuk pengujian; gunakan template percobaan atau salinan.
- Hanya dokumen ini yang dibuat pada tahap pencatatan. Implementasi dan pengujian lanjutan dilakukan pada tahap berikutnya.
