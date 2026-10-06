# Logika Bisnis

## Domain Utama

### 1. Manajemen Kursus

#### Hierarki Kursus
```
Course → Lesson → Content
       → CourseClass (batch/period)
```

- **Course**: Unit pembelajaran utama
- **Lesson**: Urutan topik dalam kursus, mendukung prerequisite
- **Content**: Material pembelajaran (7 tipe: text, video, quiz, essay, document, image, zoom)
- **CourseClass**: Batch/periode kelas dengan enrollment terpisah

#### Status Kursus
- **Draft** → **Published** → **Archived**
- Perubahan status mempengaruhi visibilitas dan aksesibilitas

#### Duplikasi
Kursus, lesson, konten, kuis, dan pertanyaan bisa diduplikasi menggunakan trait `Duplicateable`:
- Menggandakan data terkait (relasi)
- TIDAK menggandakan user associations atau completion records
- Reset token fields untuk kursus duplikat
- Menggunakan database transaction untuk atomicity

### 2. Sistem Enroll

#### Token-Based Enrollment
```
Course ─── enrollment_token ─── token_enabled ─── token_expires_at ─── token_type
CourseClass ─── enrollment_token ─── token_enabled ─── token_expires_at ─── token_type
```

Dua level enrollment:
1. **Course-level**: Enroll ke kursus secara keseluruhan
2. **Class-level**: Enroll ke batch/periode tertentu

#### Enrollment Code
- Kode one-time untuk enroll
- Bisa dibatasi per email
- Memiliki expiry time

#### Free Enrollment
- POST ke `/katalog/{course}/daftar-gratis`
- Langsung tanpa pembayaran

### 3. Sistem Pembayaran

#### Alur Pembayaran
```
Pilih Kursus → Checkout → Pilih Metode Bayar
                              ↓
                    Midtrans Snap Popup
                              ↓
                    ┌─────────┴─────────┐
                    │   Success         │   Pending/Paid
                    │   Auto-enroll     │   → Manual Verification
                    │                   │   → Admin Approve/Reject
                    └───────────────────┘
```

#### Komponen Biaya
- **Harga kursus**: Ditentukan oleh admin
- **Service fee**: Dihitung oleh `ServiceFee` service
  - Per-metode atau flat-rate
  - Dengan rounding dan caps
  - Ditampilkan transparan di Snap popup

#### Verifikasi Pembayaran
- **Auto-confirm**: Pembayaran成功via Midtrans
- **Manual**: Admin review bukti transfer
  - Approve → auto-enroll
  - Reject → beri alasan

### 4. Sistem Penilaian

#### Kuis (Auto-graded)
```
Quiz → Question → Option (is_correct)
  ↓
QuizAttempt → QuestionAnswer → bandingkan dengan Option.is_correct
```

- Percobaan kuis tercatat
- Jawaban dibandingkan otomatis
- Skor dihitung per pertanyaan
- Leaderboard tersedia

#### Essay (Manual grading)
```
EssayQuestion → Content
  ↓
EssaySubmission → EssayAnswer (per question)
  ↓
Instructor grades → rubric support → question-level + submission-level
```

- Dukungan multiple essay questions per konten
- Autosave draft
- Grade oleh instructor dengan rubric

#### Case Study & Document Submission
- Upload file → Instructor review & grade
- Download jawaban oleh instructor

### 5. Sistem Kehadiran (Attendance)

#### Fitur
- **Mark attendance**: Manual per konten
- **Bulk mark**: Beberapa user sekaligus
- **Attendance requirements**: `min_attendance_minutes` pada konten
- **Export**: Laporan kehadiran ke Excel/CSV
- **Reports**: Statistik kehadiran

### 6. Sistem Sertifikat

#### Template
3 tier:
- **Basic**: Template sederhana
- **Enhanced**: Dengan fitur lebih
- **Advanced**: Template lengkap

#### Coordinate System
Template memiliki field x/y untuk penempatan dinamis:
- Nama peserta
- Tanggal
- Judul
- Tanda tangan
- Background image

#### Issue Flow
```
Course Completed → Generate Certificate → PDF Storage
                                              ↓
                                    Unique verification code
                                              ↓
                              Public: /certificates/verify/{code}
```

### 7. Sistem Komunikasi

#### Chat (Real-time)
```
Chat → CourseClass (terkait kelas)
  ↓
Chat → Participants (pivot: chat_participants)
  ↓
Message → User (sender)
```

- Real-time via Laravel Reverb
- Channel auth: `chat.{chatId}`
- Events: `MessageSent`, `UserTyping`
- Notification: `ChatMessageNotification`

#### Discussion Forum
```
Content → Discussion → DiscussionReply
```

- Per konten
- Notifikasi mobile saat reply baru

#### Announcement
- Target: semua user atau per role
- Read tracking via pivot
- Notifikasi mobile

### 8. Sistem Notifikasi

#### Channel
| Channel | Penggunaan |
|---------|------------|
| Database | Mobile push notifications |
| Mail | OTP, password reset |

#### Tipe Notifikasi
| Notifikasi | Trigger |
|------------|---------|
| `MobileNotification` | Discussion reply, grade, new content, new submission |
| `ChatMessageNotification` | Chat message baru |
| `ExportCompletedNotification` | Export selesai |
| `ChatCreatedNotification` | Chat baru dibuat |
| `CustomResetPasswordNotification` | Password reset email |

### 9. Content Scheduling

Konten memiliki field penjadwalan:
- `is_scheduled`: Aktifkan penjadwalan
- `scheduled_start`: Waktu mulai tampil
- `scheduled_end`: Waktu berakhir tampil
- `timezone_offset`: Offset timezone

### 10. Grading & Gradebook

#### Komponen
- **Gradebook**: Tampilan semua nilai peserta
- **Instructor Analytics**: Performa instructor
- **Auto-grade**: Penilaian otomatis selesai

#### Grading Modes
- **Auto**: Kuis (otomatis)
- **Manual**: Essay, case study, document (oleh instructor)
- **Hybrid**: Kombinasi keduanya

### 11. Gamification

#### Fitur
- **Game Scores**: Skor dari mini-games
- **Achievement Tiers**: Level pencapaian
- **Leaderboard**: Peringkat berdasarkan skor

### 12. Personal Agenda

- User bisa membuat agenda pribadi
- Terkait dengan Zoom sessions
- Reminder untuk jadwal

## Aturan Bisnis Penting

### Enroll
1. User harus terverifikasi email (kecuali lama)
2. Token enrollment memiliki expiry
3. Enrollment code bersifat one-time
4. Free enrollment tidak perlu pembayaran

### Pembayaran
1. Harga + service fee = total yang dibayar
2. Service fee dihitung otomatis
3. Pembayaran pending memerlukan verifikasi manual
4. Auto-enroll setelah pembayaran成功

### Penilaian
1. Kuis di-auto-grade, essay di-manual-grade
2. Grading memerlukan `scoring_enabled` pada konten
3. `requires_review` untuk konten yang perlu review
4. `grading_mode` menentukan tipe penilaian

### Akses
1. Permission diperiksa di route level
2. Super-admin bypass semua izin
3. Instructor hanya akses kursus sendiri
4. Participant harus enroll untuk mengakses konten

### Sertifikat
1. Kursus harus selesai 100% untuk issue sertifikat
2. Setiap sertifikat punya kode verifikasi unik
3. Verifikasi publik tidak memerlukan login
4. Template bisa diduplikasi
