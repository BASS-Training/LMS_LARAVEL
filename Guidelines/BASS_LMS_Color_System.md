# BASS LMS — Color System Guidelines

Dokumen ini menjadi acuan warna untuk pengembangan **LMS BASS** agar seluruh tampilan konsisten, profesional, dan selaras dengan identitas visual utama BASS.

---

## 1. Brand Color

### Primary — BASS Red
- **Hex:** `#DA1E1E`
- **Penggunaan:** CTA utama, tombol utama, menu aktif, progress bar, link penting, icon utama, highlight brand.
- **Catatan:** Jangan digunakan terlalu dominan pada area besar agar tampilan tetap nyaman dilihat.

### Primary Hover
- **Hex:** `#B91818`
- **Penggunaan:** Hover tombol utama, active state, pressed state.

### Primary Soft
- **Hex:** `#FCEAEA`
- **Penggunaan:** Background menu aktif, badge ringan, selected state, highlight area.
- **Tujuan:** Memberikan nuansa brand tanpa membuat tampilan terlalu merah.

---

## 2. Secondary Color

### Deep Navy
- **Hex:** `#17243A`
- **Penggunaan:** Heading, sidebar, navigasi utama, section title, elemen corporate.
- **Karakter:** Profesional, kuat, dan menjadi penyeimbang BASS Red.

### Secondary Navy
- **Hex:** `#334155`
- **Penggunaan:** Body text penting, icon sekunder, subtitle, secondary navigation.

---

## 3. Neutral Colors

### Background
- **Hex:** `#F7F8FA`
- **Penggunaan:** Background utama dashboard dan halaman LMS.

### Surface / Card
- **Hex:** `#FFFFFF`
- **Penggunaan:** Card, modal, form, table container, content section.

### Border
- **Hex:** `#E5E7EB`
- **Penggunaan:** Border card, input, divider, table.

### Text Primary
- **Hex:** `#1E293B`
- **Penggunaan:** Judul, teks utama, label penting.

### Text Secondary
- **Hex:** `#64748B`
- **Penggunaan:** Subtitle, metadata, placeholder, teks informasi sekunder.

### Muted / Disabled
- **Hex:** `#94A3B8`
- **Penggunaan:** Disabled text, disabled icon, inactive element.

---

## 4. Accent Color

### Yellow Accent
- **Hex:** `#F6C945`
- **Penggunaan:** Highlight kecil, penanda visual, underline, aksen tertentu.
- **Catatan:** Gunakan terbatas. Bukan warna utama tombol atau navigasi.

---

# 5. Semantic Colors

Warna semantic tetap boleh berbeda dari warna brand karena memiliki fungsi komunikasi yang jelas.

## Success — Green
- **Main:** `#168A50`
- **Soft Background:** `#EAF7F0`
- **Penggunaan:** Status selesai, berhasil, approved, completion, success notification.

Contoh:
- Selesai
- Sertifikat tersedia
- Pembayaran berhasil
- Quiz passed

---

## Warning — Yellow / Amber
- **Main:** `#D97706`
- **Soft Background:** `#FFF7E6`
- **Penggunaan:** Warning, deadline mendekati, perhatian, proses belum selesai.

Contoh:
- Deadline 2 hari lagi
- Belum submit tugas
- Data perlu dilengkapi

---

## Error — Red, tetapi berbeda dari BASS Red
- **Main:** `#B91C1C`
- **Dark:** `#991B1B`
- **Soft Background:** `#FEECEC`
- **Penggunaan:** Error, gagal, invalid input, destructive action.

> **Penting:**
> `#DA1E1E` adalah **brand red**, sedangkan `#B91C1C` digunakan sebagai **error red**.
> Jangan menyamakan keduanya agar user bisa membedakan elemen brand dan kondisi error.

Contoh:
- Login gagal
- Form tidak valid
- Hapus data
- Upload gagal

---

## Info — Gray atau Light BASS Red

Untuk status informasi, gunakan salah satu dari dua pendekatan berikut.

### Option A — Neutral Gray
- **Main:** `#64748B`
- **Soft Background:** `#F1F5F9`
- **Penggunaan:** Informasi umum, helper text, info non-kritis.

### Option B — Light BASS Red
- **Main/Text:** `#DA1E1E`
- **Soft Background:** `#FCEAEA`
- **Border:** `#F5B7B7`
- **Penggunaan:** Informasi yang ingin tetap terasa sebagai bagian dari identitas BASS.

### Rekomendasi
Gunakan **Gray** untuk informasi umum dan **Light BASS Red** untuk informasi yang memiliki hubungan langsung dengan action, course, atau brand.

---

# 6. Color Usage Ratio

Agar LMS tidak terlihat terlalu ramai, gunakan komposisi warna berikut sebagai acuan:

- **70%** Neutral — White, Off White, Gray
- **20%** Navy
- **10%** BASS Red dan Accent

Semantic colors seperti green, amber, dan error red hanya muncul ketika konteksnya membutuhkan.

---

# 7. Recommended UI Mapping

| Elemen UI | Warna |
|---|---|
| Main Background | `#F7F8FA` |
| Card | `#FFFFFF` |
| Sidebar | `#17243A` |
| Main Heading | `#17243A` |
| Body Text | `#334155` |
| Secondary Text | `#64748B` |
| Primary Button | `#DA1E1E` |
| Primary Button Hover | `#B91818` |
| Active Menu Background | `#FCEAEA` |
| Active Menu Text/Icon | `#DA1E1E` |
| Progress Bar | `#DA1E1E` |
| Border | `#E5E7EB` |
| Success | `#168A50` |
| Warning | `#D97706` |
| Error | `#B91C1C` |
| Info Neutral | `#64748B` |
| Info Brand | `#FCEAEA` + `#DA1E1E` |

---

# 8. CSS Variables

```css
:root {
  /* Brand */
  --bass-red: #DA1E1E;
  --bass-red-hover: #B91818;
  --bass-red-soft: #FCEAEA;

  /* Secondary */
  --navy: #17243A;
  --navy-light: #334155;

  /* Neutral */
  --background: #F7F8FA;
  --surface: #FFFFFF;
  --border: #E5E7EB;
  --text-primary: #1E293B;
  --text-secondary: #64748B;
  --text-muted: #94A3B8;

  /* Accent */
  --accent-yellow: #F6C945;

  /* Semantic */
  --success: #168A50;
  --success-soft: #EAF7F0;

  --warning: #D97706;
  --warning-soft: #FFF7E6;

  --error: #B91C1C;
  --error-dark: #991B1B;
  --error-soft: #FEECEC;

  --info: #64748B;
  --info-soft: #F1F5F9;

  --info-brand: #DA1E1E;
  --info-brand-soft: #FCEAEA;
  --info-brand-border: #F5B7B7;
}
```

---

# 9. Rules for AI / Frontend Generation

Saat AI membuat atau mengubah tampilan LMS BASS, gunakan aturan berikut:

1. Gunakan `#DA1E1E` sebagai **warna brand utama**.
2. Gunakan `#17243A` sebagai **warna sekunder utama**.
3. Gunakan background dominan putih atau `#F7F8FA`.
4. Jangan membuat setiap card atau menu memiliki warna berbeda.
5. Warna kategori course tidak boleh mendominasi UI.
6. BASS Red digunakan terutama untuk CTA, active state, progress, dan highlight.
7. Success harus tetap **green**.
8. Warning harus tetap **yellow/amber**.
9. Error harus tetap **red**, tetapi menggunakan shade berbeda dari BASS Red.
10. Info menggunakan **gray** atau **light BASS red**.
11. Yellow accent hanya digunakan sebagai aksen kecil.
12. Hindari gradient berlebihan.
13. Hindari warna neon atau saturated colors yang tidak termasuk design system.
14. Utamakan tampilan clean, corporate, modern, dan professional.
15. Jika ragu memilih warna, prioritaskan **neutral + navy + BASS red**.

---

# 10. AI Design Prompt Reference

Gunakan instruksi berikut ketika meminta AI membuat tampilan:

> Design the LMS interface using the official BASS color system.
> Use BASS Red `#DA1E1E` as the primary brand color, Deep Navy `#17243A` as the secondary color, and neutral white/off-white backgrounds.
> Keep the interface clean, corporate, modern, and professional.
> Avoid colorful cards and unnecessary decorative colors.
> Use semantic colors only for their intended functions: green for success, amber/yellow for warning, a darker red `#B91C1C` for errors, and gray or light BASS red for informational states.
> Use BASS red mainly for primary actions, active navigation, progress indicators, and key highlights.
> Maintain strong visual consistency throughout the LMS.

---

## Final Color Palette

```text
BASS Red       #DA1E1E
BASS Red Hover #B91818
BASS Red Soft  #FCEAEA

Deep Navy      #17243A
Navy Light     #334155

Background     #F7F8FA
Surface        #FFFFFF
Border         #E5E7EB

Text Primary   #1E293B
Text Secondary #64748B
Text Muted     #94A3B8

Accent Yellow  #F6C945

Success        #168A50
Warning        #D97706
Error          #B91C1C
Info Gray      #64748B
Info Brand     #FCEAEA / #DA1E1E
```
