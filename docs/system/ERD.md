# Entity Relationship Diagram

## Notasi

| Simbol | Arti |
|---|---|
| `||` | Tepat satu |
| `o|` | Nol atau satu |
| `o{` | Nol atau banyak |
| `PK` | Primary key |
| `FK` | Foreign key |
| `UK` | Unique key |

ERD dibagi per domain agar relasi dapat dibaca tanpa menghasilkan satu diagram yang terlalu besar.

## 1. Identity dan RBAC

```mermaid
erDiagram
    USERS {
        bigint id PK
        string name
        string email UK
        string role
        string registration_program
        string avpn_verification_status
        bigint avpn_verified_by FK
        timestamp email_verified_at
        string api_token UK
    }
    ROLES {
        bigint id PK
        string name
        string guard_name
    }
    PERMISSIONS {
        bigint id PK
        string name
        string guard_name
    }
    MODEL_HAS_ROLES {
        bigint role_id PK,FK
        string model_type PK
        bigint model_id PK
    }
    MODEL_HAS_PERMISSIONS {
        bigint permission_id PK,FK
        string model_type PK
        bigint model_id PK
    }
    ROLE_HAS_PERMISSIONS {
        bigint role_id PK,FK
        bigint permission_id PK,FK
    }
    EMAIL_OTPS {
        bigint id PK
        string email
        string purpose
        string code
        timestamp expires_at
        timestamp consumed_at
    }
    SESSIONS {
        string id PK
        bigint user_id
        integer last_activity
    }
    NOTIFICATIONS {
        uuid id PK
        string type
        string notifiable_type
        bigint notifiable_id
        timestamp read_at
    }

    USERS o|--o{ USERS : verifies_AVPN
    ROLES ||--o{ MODEL_HAS_ROLES : assigned
    PERMISSIONS ||--o{ MODEL_HAS_PERMISSIONS : assigned
    ROLES ||--o{ ROLE_HAS_PERMISSIONS : grants
    PERMISSIONS ||--o{ ROLE_HAS_PERMISSIONS : included
    USERS ||--o{ MODEL_HAS_ROLES : logical_polymorphic
    USERS ||--o{ MODEL_HAS_PERMISSIONS : logical_polymorphic
    USERS ||--o{ SESSIONS : logical_user_id
    USERS ||--o{ NOTIFICATIONS : logical_notifiable
```

Catatan:

- `model_has_roles`, `model_has_permissions`, dan `notifications` bersifat polymorphic.
- `sessions.user_id` memiliki index tetapi bukan foreign key.
- `email_otps` dan `password_reset_tokens` berelasi ke pengguna melalui email, bukan foreign key.
- Field `users.role` adalah field legacy; otorisasi utama menggunakan Spatie Permission.

## 2. Kursus, Kelas, dan Enrollment

```mermaid
erDiagram
    USERS {
        bigint id PK
        string email UK
    }
    CERTIFICATE_TEMPLATES {
        bigint id PK
        string name
    }
    COURSES {
        bigint id PK
        bigint certificate_template_id FK
        string title
        string status
        string visibility
        bigint price
        string enrollment_token UK
        boolean token_enabled
        string program_type
    }
    CATEGORIES {
        bigint id PK
        bigint parent_id FK
        string name
        string slug UK
        boolean is_active
        integer sort_order
    }
    TAGS {
        bigint id PK
        string name
        string slug UK
        boolean is_active
    }
    CATEGORY_COURSE {
        bigint category_id FK
        bigint course_id FK
    }
    COURSE_TAG {
        bigint course_id FK
        bigint tag_id FK
    }
    LESSONS {
        bigint id PK
        bigint course_id FK
        bigint prerequisite_id FK
        string title
        integer order
        boolean is_optional
    }
    CONTENTS {
        bigint id PK
        bigint lesson_id FK
        bigint quiz_id FK
        string title
        string type
        integer order
        boolean is_optional
        boolean is_scheduled
        boolean attendance_required
        boolean scoring_enabled
    }
    COURSE_CLASSES {
        bigint id PK
        bigint course_id FK
        string name
        string class_code UK
        string status
        string enrollment_token UK
        integer max_participants
    }
    COURSE_USER {
        bigint id PK
        bigint course_id FK
        bigint user_id FK
        timestamp completed_at
    }
    COURSE_INSTRUCTOR {
        bigint id PK
        bigint course_id FK
        bigint user_id FK
    }
    COURSE_EVENT_ORGANIZER {
        bigint id PK
        bigint course_id FK
        bigint user_id FK
        timestamp assigned_at
    }
    COURSE_CLASS_USER {
        bigint id PK
        bigint course_class_id FK
        bigint user_id FK
    }
    COURSE_CLASS_INSTRUCTOR {
        bigint id PK
        bigint course_class_id FK
        bigint user_id FK
    }
    LESSON_USER {
        bigint id PK
        bigint lesson_id FK
        bigint user_id FK
        boolean completed
        timestamp completed_at
    }
    CONTENT_USER {
        bigint id PK
        bigint content_id FK
        bigint user_id FK
        boolean completed
        timestamp completed_at
    }
    SAVED_COURSES {
        bigint id PK
        bigint course_id FK
        bigint user_id FK
    }
    ENROLLMENT_CODES {
        bigint id PK
        string code UK
        bigint course_id FK
        bigint course_class_id FK
        bigint redeemed_by FK
        bigint created_by FK
        string status
        timestamp expires_at
    }

    CERTIFICATE_TEMPLATES o|--o{ COURSES : default_template
    COURSES ||--o{ LESSONS : contains
    LESSONS o|--o{ LESSONS : prerequisite
    LESSONS ||--o{ CONTENTS : contains
    COURSES ||--o{ COURSE_CLASSES : has_batches
    CATEGORIES o|--o{ CATEGORIES : parent
    CATEGORIES ||--o{ CATEGORY_COURSE : classifies
    COURSES ||--o{ CATEGORY_COURSE : categorized
    TAGS ||--o{ COURSE_TAG : labels
    COURSES ||--o{ COURSE_TAG : tagged

    COURSES ||--o{ COURSE_USER : enrollment
    USERS ||--o{ COURSE_USER : enrolls
    COURSES ||--o{ COURSE_INSTRUCTOR : teaching_assignment
    USERS ||--o{ COURSE_INSTRUCTOR : teaches
    COURSES ||--o{ COURSE_EVENT_ORGANIZER : organizer_assignment
    USERS ||--o{ COURSE_EVENT_ORGANIZER : organizes
    COURSE_CLASSES ||--o{ COURSE_CLASS_USER : class_enrollment
    USERS ||--o{ COURSE_CLASS_USER : joins
    COURSE_CLASSES ||--o{ COURSE_CLASS_INSTRUCTOR : class_assignment
    USERS ||--o{ COURSE_CLASS_INSTRUCTOR : instructs

    LESSONS ||--o{ LESSON_USER : progress
    USERS ||--o{ LESSON_USER : completes
    CONTENTS ||--o{ CONTENT_USER : progress
    USERS ||--o{ CONTENT_USER : completes
    COURSES ||--o{ SAVED_COURSES : bookmarked
    USERS ||--o{ SAVED_COURSES : saves

    COURSES o|--o{ ENROLLMENT_CODES : target_course
    COURSE_CLASSES o|--o{ ENROLLMENT_CODES : target_class
    USERS o|--o{ ENROLLMENT_CODES : redeems
    USERS o|--o{ ENROLLMENT_CODES : creates
```

Constraint unik penting:

- `course_user(course_id, user_id)`
- `course_instructor(course_id, user_id)`
- `course_event_organizer(course_id, user_id)`
- `course_class_user(course_class_id, user_id)`
- `course_class_instructor(course_class_id, user_id)`
- `lesson_user(lesson_id, user_id)`
- `content_user(content_id, user_id)`
- `saved_courses(user_id, course_id)`
- `categories.slug`
- `tags.slug`
- `category_course(category_id, course_id)`
- `course_tag(course_id, tag_id)`

Saat category induk dihapus, `categories.parent_id` pada anak menjadi `null`. Penghapusan category atau tag hanya menghapus relasi pivot dan tidak menghapus course. Taxonomy nonaktif tetap dapat tersimpan pada course, tetapi tidak ditampilkan atau diterima sebagai filter katalog publik.

## 3. Asesmen

```mermaid
erDiagram
    USERS {
        bigint id PK
    }
    LESSONS {
        bigint id PK
        bigint course_id FK
    }
    CONTENTS {
        bigint id PK
        bigint lesson_id FK
        bigint quiz_id FK
        string type
        string grading_mode
        boolean requires_review
    }
    QUIZZES {
        bigint id PK
        bigint lesson_id FK
        bigint user_id FK
        string title
        integer passing_percentage
        integer time_limit
        string status
    }
    QUESTIONS {
        bigint id PK
        bigint quiz_id FK
        text question_text
        string type
        integer marks
    }
    OPTIONS {
        bigint id PK
        bigint question_id FK
        text option_text
        boolean is_correct
    }
    QUIZ_ATTEMPTS {
        bigint id PK
        bigint quiz_id FK
        bigint user_id FK
        decimal score
        boolean passed
        timestamp started_at
        timestamp completed_at
    }
    QUESTION_ANSWERS {
        bigint id PK
        bigint quiz_attempt_id FK
        bigint user_id FK
        bigint question_id FK
        bigint option_id FK
    }
    ESSAY_QUESTIONS {
        bigint id PK
        bigint content_id FK
        text question
        integer max_score
        boolean is_active
        timestamp deleted_at
    }
    ESSAY_SUBMISSIONS {
        bigint id PK
        bigint content_id FK
        bigint user_id FK
        string status
        timestamp graded_at
    }
    ESSAY_ANSWERS {
        bigint id PK
        bigint submission_id FK
        bigint question_id FK
        text answer
        decimal score
        text feedback
    }
    CASE_STUDY_SUBMISSIONS {
        bigint id PK
        bigint content_id FK
        bigint user_id FK
        bigint graded_by FK
        json answers
        string status
        decimal score
    }
    DOCUMENT_SUBMISSIONS {
        bigint id PK
        bigint content_id FK
        bigint user_id FK
        bigint graded_by FK
        integer attempt
        string file_path
        string status
        decimal score
    }
    FEEDBACK_QUESTIONS {
        bigint id PK
        bigint content_id FK
        string type
        text question
        boolean is_required
        json config
    }
    FEEDBACK_SUBMISSIONS {
        bigint id PK
        bigint content_id FK
        bigint user_id FK
        string status
        timestamp submitted_at
    }
    FEEDBACK_ANSWERS {
        bigint id PK
        bigint submission_id FK
        bigint question_id FK
        integer rating_value
        text text_value
        json choice_value
    }
    FEEDBACK {
        bigint id PK
        bigint course_id FK
        bigint user_id FK
        bigint instructor_id FK
        text feedback
    }
    COURSES {
        bigint id PK
    }

    LESSONS o|--o{ QUIZZES : owns
    USERS ||--o{ QUIZZES : creates
    QUIZZES ||--o{ QUESTIONS : contains
    QUESTIONS ||--o{ OPTIONS : offers
    QUIZZES ||--o{ QUIZ_ATTEMPTS : attempted
    USERS ||--o{ QUIZ_ATTEMPTS : performs
    QUIZ_ATTEMPTS ||--o{ QUESTION_ANSWERS : records
    USERS ||--o{ QUESTION_ANSWERS : answers
    QUESTIONS ||--o{ QUESTION_ANSWERS : answered_question
    OPTIONS ||--o{ QUESTION_ANSWERS : selected_option
    QUIZZES o|--o{ CONTENTS : linked_by_quiz_id

    CONTENTS ||--o{ ESSAY_QUESTIONS : defines
    CONTENTS ||--o{ ESSAY_SUBMISSIONS : receives
    USERS ||--o{ ESSAY_SUBMISSIONS : submits
    ESSAY_SUBMISSIONS ||--o{ ESSAY_ANSWERS : contains
    ESSAY_QUESTIONS o|--o{ ESSAY_ANSWERS : answered

    CONTENTS ||--o{ CASE_STUDY_SUBMISSIONS : receives
    USERS ||--o{ CASE_STUDY_SUBMISSIONS : submits
    USERS o|--o{ CASE_STUDY_SUBMISSIONS : grades
    CONTENTS ||--o{ DOCUMENT_SUBMISSIONS : receives
    USERS ||--o{ DOCUMENT_SUBMISSIONS : submits
    USERS o|--o{ DOCUMENT_SUBMISSIONS : grades

    CONTENTS ||--o{ FEEDBACK_QUESTIONS : defines
    CONTENTS ||--o{ FEEDBACK_SUBMISSIONS : receives
    USERS ||--o{ FEEDBACK_SUBMISSIONS : submits
    FEEDBACK_SUBMISSIONS ||--o{ FEEDBACK_ANSWERS : contains
    FEEDBACK_QUESTIONS o|--o{ FEEDBACK_ANSWERS : answered

    COURSES ||--o{ FEEDBACK : receives
    USERS ||--o{ FEEDBACK : participant
    USERS ||--o{ FEEDBACK : instructor
```

## 4. Konten, Kehadiran, dan Diskusi

```mermaid
erDiagram
    USERS {
        bigint id PK
    }
    COURSES {
        bigint id PK
    }
    CONTENTS {
        bigint id PK
    }
    CONTENT_IMAGES {
        bigint id PK
        bigint content_id FK
        string file_path
        integer order
    }
    CONTENT_DOCUMENTS {
        bigint id PK
        bigint content_id FK
        string file_path
        string original_name
        integer order
    }
    ATTENDANCES {
        bigint id PK
        bigint user_id FK
        bigint content_id FK
        bigint course_id FK
        timestamp joined_at
        timestamp left_at
        integer duration_minutes
        string status
    }
    DISCUSSIONS {
        bigint id PK
        bigint user_id FK
        bigint content_id FK
        string title
        text body
    }
    DISCUSSION_REPLIES {
        bigint id PK
        bigint user_id FK
        bigint discussion_id FK
        text body
    }

    CONTENTS ||--o{ CONTENT_IMAGES : has
    CONTENTS ||--o{ CONTENT_DOCUMENTS : has
    USERS ||--o{ ATTENDANCES : attends
    CONTENTS ||--o{ ATTENDANCES : tracked_for
    COURSES ||--o{ ATTENDANCES : belongs_to
    CONTENTS ||--o{ DISCUSSIONS : hosts
    USERS ||--o{ DISCUSSIONS : creates
    DISCUSSIONS ||--o{ DISCUSSION_REPLIES : has
    USERS ||--o{ DISCUSSION_REPLIES : writes
```

`attendances` unik per `(user_id, content_id)`. `course_id` disimpan secara denormalisasi untuk reporting dan harus konsisten dengan jalur `content -> lesson -> course` pada level aplikasi.

## 5. Komunikasi

```mermaid
erDiagram
    USERS {
        bigint id PK
    }
    COURSE_CLASSES {
        bigint id PK
    }
    CHATS {
        bigint id PK
        bigint course_class_id FK
        bigint created_by FK
        string name
        string type
        boolean is_active
        timestamp last_message_at
    }
    CHAT_PARTICIPANTS {
        bigint id PK
        bigint chat_id FK
        bigint user_id FK
        timestamp joined_at
        timestamp last_read_at
        string status
        boolean notifications_enabled
    }
    MESSAGES {
        bigint id PK
        bigint chat_id FK
        bigint user_id FK
        text content
        string type
        json metadata
        boolean is_edited
    }
    ANNOUNCEMENTS {
        bigint id PK
        bigint user_id FK
        string title
        text content
        string level
        json target_roles
        timestamp published_at
        timestamp expires_at
    }
    ANNOUNCEMENT_READS {
        bigint id PK
        bigint announcement_id FK
        bigint user_id FK
        timestamp read_at
    }

    COURSE_CLASSES o|--o{ CHATS : scopes
    USERS ||--o{ CHATS : creates
    CHATS ||--o{ CHAT_PARTICIPANTS : includes
    USERS ||--o{ CHAT_PARTICIPANTS : joins
    CHATS ||--o{ MESSAGES : contains
    USERS ||--o{ MESSAGES : sends
    USERS ||--o{ ANNOUNCEMENTS : publishes
    ANNOUNCEMENTS ||--o{ ANNOUNCEMENT_READS : tracked_by
    USERS ||--o{ ANNOUNCEMENT_READS : reads
```

## 6. Sertifikat, Pembayaran, dan Reporting

```mermaid
erDiagram
    USERS {
        bigint id PK
    }
    COURSES {
        bigint id PK
        bigint certificate_template_id FK
    }
    COURSE_CLASSES {
        bigint id PK
    }
    CERTIFICATE_TEMPLATES {
        bigint id PK
        string name
        json layout_data
    }
    CERTIFICATES {
        bigint id PK
        bigint user_id FK
        bigint course_id FK
        bigint certificate_template_id FK
        string certificate_code UK
        string path
        timestamp issued_at
    }
    ORDERS {
        bigint id PK
        bigint user_id FK
        bigint course_id FK
        bigint verified_by FK
        string order_code UK
        string invoice_number UK
        bigint amount
        string status
        string payment_method_key
        string transaction_id
        timestamp paid_at
    }
    EXPORT_HISTORIES {
        bigint id PK
        bigint user_id FK
        bigint course_id FK
        bigint course_class_id FK
        string filter
        string file_path
        string status
    }
    ACTIVITY_LOGS {
        bigint id PK
        bigint user_id FK
        string action
        string description
        json metadata
        string status
    }

    CERTIFICATE_TEMPLATES o|--o{ COURSES : default_for
    USERS ||--o{ CERTIFICATES : receives
    COURSES ||--o{ CERTIFICATES : awards
    CERTIFICATE_TEMPLATES ||--o{ CERTIFICATES : renders
    USERS ||--o{ ORDERS : places
    COURSES ||--o{ ORDERS : purchased_for
    USERS o|--o{ ORDERS : verifies
    USERS ||--o{ EXPORT_HISTORIES : requests
    COURSES ||--o{ EXPORT_HISTORIES : exported_course
    COURSE_CLASSES o|--o{ EXPORT_HISTORIES : filtered_class
    USERS o|--o{ ACTIVITY_LOGS : performs
```

## 7. Fitur Personal Mobile

```mermaid
erDiagram
    USERS {
        bigint id PK
    }
    PERSONAL_AGENDA_ITEMS {
        bigint id PK
        bigint user_id FK
        string title
        text note
        date event_date
        integer hour
        integer minute
    }
    USER_GAME_SCORES {
        bigint id PK
        bigint user_id FK
        string game_id
        integer best_score
        integer plays
        timestamp last_played_at
    }
    USER_ACHIEVEMENT_TIERS {
        bigint id PK
        bigint user_id FK
        string achievement_id
        integer tier
    }

    USERS ||--o{ PERSONAL_AGENDA_ITEMS : owns
    USERS ||--o{ USER_GAME_SCORES : records
    USERS ||--o{ USER_ACHIEVEMENT_TIERS : unlocks
```

`game_id` dan `achievement_id` menunjuk registry di aplikasi mobile, bukan tabel master di database.

## Katalog Tabel

| Domain | Tabel |
|---|---|
| Identity/Auth | `users`, `password_reset_tokens`, `sessions`, `email_otps` |
| RBAC | `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` |
| Course | `courses`, `lessons`, `contents`, `course_classes` |
| Course taxonomy | `categories`, `tags`, `category_course`, `course_tag` |
| Membership | `course_user`, `course_instructor`, `course_event_organizer`, `course_class_user`, `course_class_instructor`, `saved_courses` |
| Progress | `lesson_user`, `content_user`, `attendances` |
| Enrollment | `enrollment_codes` |
| Quiz | `quizzes`, `questions`, `options`, `quiz_attempts`, `question_answers` |
| Essay | `essay_questions`, `essay_submissions`, `essay_answers` |
| Submission | `case_study_submissions`, `document_submissions` |
| Survey | `feedback_questions`, `feedback_submissions`, `feedback_answers`, `feedback` |
| Content assets | `content_images`, `content_documents` |
| Discussion | `discussions`, `discussion_replies` |
| Chat | `chats`, `chat_participants`, `messages` |
| Announcement | `announcements`, `announcement_reads`, `notifications` |
| Certificate | `certificate_templates`, `certificates` |
| Commerce | `orders` |
| Reporting/Audit | `export_histories`, `activity_logs` |
| Personal | `personal_agenda_items`, `user_game_scores`, `user_achievement_tiers` |
| Infrastructure | `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs` |

## Aturan Integritas di Level Aplikasi

Aturan berikut belum seluruhnya dipaksa oleh constraint database:

1. `enrollment_codes` seharusnya menunjuk tepat satu target: course atau class.
2. `contents.quiz_id` seharusnya menunjuk quiz pada lesson yang sesuai.
3. `question_answers` harus konsisten antara attempt, user, quiz, question, dan option.
4. `attendances.course_id` harus sama dengan course dari content terkait.
5. Satu submission seharusnya hanya memiliki satu jawaban per pertanyaan.
6. Tanggal akhir class, course, dan jadwal content tidak boleh lebih awal dari tanggal mulai.
7. Sertifikat diterbitkan hanya jika syarat kelulusan dan review konten wajib terpenuhi.
8. Enrollment berbayar hanya diberikan setelah status pembayaran valid atau verifikasi admin selesai.
9. Hierarki category tidak boleh menjadikan category sebagai induk dirinya sendiri atau salah satu turunannya.

## Ketidaksesuaian Model yang Perlu Diperhatikan

Temuan ini didokumentasikan agar ERD fisik tidak disalahartikan sebagai perilaku model yang selalu benar:

- `User::completedLessons()` menggunakan pivot `status`, sedangkan tabel `lesson_user` memiliki kolom boolean `completed`.
- `Course::quizzes()` mengasumsikan `quizzes.course_id`, tetapi schema final menggunakan `quizzes.lesson_id`.
- `Quiz::content()` adalah `hasOne`, sedangkan `contents.quiz_id` tidak unik sehingga database mengizinkan banyak content untuk satu quiz.
- `essay_questions.deleted_at` tersedia, tetapi model `EssayQuestion` tidak memakai trait `SoftDeletes`.
- `User::$fillable` memuat `phone` dan `monthly_income`, tetapi kolom tersebut tidak ditemukan pada migration repository.
