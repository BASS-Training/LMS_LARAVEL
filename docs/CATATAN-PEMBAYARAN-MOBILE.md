# Catatan: Kebijakan Pembayaran Mobile vs Web

## Google Play Anti-Steering Policy

Google Play melarang aplikasi mengarahkan pengguna ke pembayaran di luar Play Billing untuk **digital goods**.

### Yang dilarang:
- ❌ Tombol "Beli" di app yang redirect ke Midtrans
- ❌ WebView ke payment gateway
- ❌ Sebutkan "bayar di web" atau link ke checkout di dalam app
- ❌ Link ke halaman pembayaran eksternal

### Yang diizinkan:
- ✅ User **buka browser sendiri** (inisiatif user, bukan dari app)
- ✅ Admin beri **enrollment code** (bukan flow pembelian)
- ✅ Kursus **gratis** (tidak ada transaksi)
- ✅ Fisik/bukan digital (tiket, merchandise)

## Implikasi untuk Sistem Ini

| Aspek | Web | Mobile |
|-------|-----|--------|
| Payment gateway | Midtrans Snap + Core API | **Tidak ada** |
| Checkout flow | Lengkap (pilih metode, bayar, invoice) | **Tidak ada** |
| Kursus berbayar | Beli langsung | Preview only, beli di web |
| Kursus gratis | Enroll | Enroll langsung |
| Enrollment | Token, code, atau beli | Token atau code saja |
| Order history | Ya | **Tidak ada** |

## Alur yang Benar

### Kursus Gratis
```
Mobile App → Klik "Daftar Gratis" → Langsung enroll → Akses kursus
```

### Kursus Berbayar (dari Mobile)
```
Mobile App → Preview kursus (tanpa tombol beli)
                  │
                  ▼
        User buka browser sendiri
                  │
                  ▼
        Buka /katalog/{course}/beli
                  │
                  ▼
        Bayar via Midtrans Snap
                  │
                  ▼
        Akses muncul di app (shared DB)
```

### Kursus Berbayar (Enrollment Code)
```
Admin/Instructor → Generate enrollment code → Beri ke participant
                                                      │
                                                      ▼
Mobile App → /api/mobile/enroll (kode) → Akses kursus
```

## Kenapa Tidak Pakai Google Play Billing?

| Aspek | Google Play Billing | Midtrans (Web) |
|-------|--------------------|-|
| Potongan | 15-30% | ~2-3% |
| Integrasi | Kompleks (subscription, refund handling) | Simpel (Snap popup) |
| Refund | Ikut kebijakan Google | Kontrol sendiri |
| Target user | Consumer massal | Institusi/pelatihan |
| Verifikasi | Ribuk (server-to-server) | Simpel (webhook) |

**Kesimpulan**: Untuk app institusi/pelatihan, lebih praktis tanpa in-app purchase.

## Referensi

- [Google Play Developer Policy - Anti-Steering](https://support.google.com/googleplay/android-developer/answer/9858737)
- [Google Play Billing Overview](https://developer.android.com/google/play/billing)
- Kode terkait: `routes/api.php` (baris 61-65), `app/Http/Controllers/Api/ShopApiController.php`
