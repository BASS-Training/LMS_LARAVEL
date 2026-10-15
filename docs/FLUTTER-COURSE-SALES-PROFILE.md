# Sales Profile Course di Flutter

Sales profile merupakan bagian **Detail Course di Jelajahi/Katalog**. Data yang
diatur admin website sudah tersedia pada API; tidak perlu endpoint sales baru.

## Kontrak API

```http
GET /api/mobile/catalog/{courseId}
Accept: application/json
```

Publik. Tambahkan `Authorization: Bearer <token>` hanya ketika token tersedia dan
valid. Gunakan ID dari katalog, bukan slug. Token tidak valid menghasilkan `401`;
course di luar katalog atau sales profile draft/hidden menghasilkan `404`.

Objek sales profile ada pada `response.data['data']['salesProfile']`:

```json
{
  "slug": "dasar-bermain-bass",
  "headline": "Kuasai teknik dasar bass",
  "targetAudience": "Pemain bass pemula",
  "learningBenefits": "Memahami ritme\nMemainkan lagu pertama",
  "requirements": "Memiliki alat musik bass",
  "level": "beginner",
  "estimatedDurationMinutes": 480,
  "language": "Bahasa Indonesia",
  "promoVideoUrl": "https://example.com/promo",
  "faq": [{ "question": "Cocok untuk pemula?", "answer": "Ya." }]
}
```

`salesProfile` dapat `null`, field dapat kosong/null, dan `faq` default `[]`.
Daftar katalog hanya menggunakan headline sebagai `shortDescription`; ambil
sales profile lengkap melalui endpoint detail.

## Model Dart (Dart 3)

```dart
class CourseSalesFaq {
  const CourseSalesFaq(this.question, this.answer);
  final String question;
  final String answer;

  factory CourseSalesFaq.fromJson(Map<String, dynamic> json) => CourseSalesFaq(
        json['question'] as String? ?? '',
        json['answer'] as String? ?? '',
      );
}

class CourseSalesProfile {
  CourseSalesProfile.fromJson(Map<String, dynamic> json)
      : slug = json['slug'] as String?,
        headline = json['headline'] as String?,
        targetAudience = json['targetAudience'] as String?,
        learningBenefits = json['learningBenefits'] as String?,
        requirements = json['requirements'] as String?,
        level = json['level'] as String?,
        estimatedDurationMinutes =
            (json['estimatedDurationMinutes'] as num?)?.toInt(),
        language = json['language'] as String?,
        promoVideoUrl = json['promoVideoUrl'] as String?,
        faq = (json['faq'] as List? ?? const [])
            .map((item) => CourseSalesFaq.fromJson(
                  Map<String, dynamic>.from(item as Map),
                ))
            .where((item) => item.question.trim().isNotEmpty &&
                item.answer.trim().isNotEmpty)
            .toList();

  final String? slug, headline, targetAudience, learningBenefits;
  final String? requirements, level, language, promoVideoUrl;
  final int? estimatedDurationMinutes;
  final List<CourseSalesFaq> faq;

  String? get levelLabel => switch (level) {
        'beginner' => 'Pemula',
        'intermediate' => 'Menengah',
        'advanced' => 'Mahir',
        'all_levels' => 'Semua Level',
        _ => level,
      };

  String? get durationLabel {
    final minutes = estimatedDurationMinutes;
    if (minutes == null || minutes <= 0) return null;
    return [
      if (minutes ~/ 60 > 0) '${minutes ~/ 60} jam',
      if (minutes % 60 > 0) '${minutes % 60} menit',
    ].join(' ');
  }
}
```

Tambahkan `CourseSalesProfile? salesProfile` pada model detail yang sudah ada.
Di factory model tersebut, `json` adalah objek `data`, bukan envelope:

```dart
salesProfile: json['salesProfile'] == null
    ? null
    : CourseSalesProfile.fromJson(
        Map<String, dynamic>.from(json['salesProfile'] as Map),
      ),
```

## Request Dio

Gunakan API client terpusat aplikasi. Contoh berikut membutuhkan package `dio`:

```dart
import 'package:dio/dio.dart';

Future<Map<String, dynamic>> fetchCatalogDetail(
  Dio dio, String courseId, String? token,
) async {
  final response = await dio.get<Map<String, dynamic>>(
    'mobile/catalog/${Uri.encodeComponent(courseId)}',
    options: Options(headers: {
      'Accept': 'application/json',
      if (token != null && token.trim().isNotEmpty)
        'Authorization': 'Bearer ${token.trim()}',
    }),
  );
  return Map<String, dynamic>.from(response.data!['data'] as Map);
}

final dio = Dio(BaseOptions(baseUrl: 'https://domain-anda.com/api/'));
```

Panggil dari repository/provider/BLoC atau simpan Future pada `initState`, bukan
membuat request baru setiap build. Pastikan interceptor tidak mengirim token
lama pada request tamu.

## Contoh widget bagian sales profile

Import model sesuai lokasi file aplikasi:

```dart
import 'package:flutter/material.dart';

class SalesProfileSection extends StatelessWidget {
  const SalesProfileSection({super.key, required this.profile});
  final CourseSalesProfile? profile;

  Widget section(String title, String? text) {
    if (text == null || text.trim().isEmpty) return const SizedBox.shrink();
    return Padding(
      padding: const EdgeInsets.only(bottom: 20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title, style: const TextStyle(
            fontSize: 18, fontWeight: FontWeight.bold,
          )),
          const SizedBox(height: 8),
          Text(text.trim()),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final p = profile;
    if (p == null) return const SizedBox.shrink();
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Wrap(
          spacing: 8,
          runSpacing: 8,
          children: [p.levelLabel, p.durationLabel, p.language]
              .whereType<String>()
              .where((text) => text.trim().isNotEmpty)
              .map((text) => Chip(label: Text(text)))
              .toList(),
        ),
        section('Manfaat yang Anda dapatkan', p.learningBenefits),
        section('Cocok untuk siapa?', p.targetAudience),
        section('Persyaratan', p.requirements),
        if (p.faq.isNotEmpty) ...[
          const Text('Pertanyaan yang sering diajukan'),
          ...p.faq.map((item) => ExpansionTile(
                title: Text(item.question),
                childrenPadding: const EdgeInsets.all(16),
                children: [Text(item.answer)],
              )),
        ],
      ],
    );
  }
}
```

Letakkan widget pada body detail yang bisa di-scroll. Di header, gunakan
headline bila terisi, lalu fallback ke `shortDescription`; jangan tampilkan
keduanya sebagai teks identik.

Untuk tombol video, tambahkan package `url_launcher`. Parse `promoVideoUrl`
dengan `Uri.tryParse`, tampilkan tombol hanya untuk URL HTTP/HTTPS dengan host
valid, lalu gunakan `launchUrl(uri, mode: LaunchMode.externalApplication)`.
Tangani hasil `false` maupun exception dengan pesan gagal membuka video; cek
`context.mounted` sebelum menampilkan SnackBar setelah operasi async.

## Susunan dan alur halaman

1. Thumbnail, judul, headline.
2. Level, durasi, bahasa, dan tombol video bila tersedia.
3. Manfaat, deskripsi, target peserta, persyaratan.
4. Profil instruktur (`instructors[]`: `name`, `avatarUrl`, `bioHtml`).
5. Kurikulum (`sections[]`) dan materi preview terpilih.
6. FAQ.
7. Tombol sesuai status enrollment.

Teks sales profile adalah **plain text multiline**; gunakan `Text`. `description`
dan `instructors[].bioHtml` dapat mengandung HTML dan memerlukan renderer HTML
sesuai pola aplikasi. `objectives`/`objectivesHtml` belum tersedia dalam endpoint;
jangan mengasumsikannya sama dengan `learningBenefits`.

- Ikuti `meta.showPrice` dan `priceLabel` untuk tampilan harga.
- Sudah enrolled: Lanjutkan Belajar menuju Kursus Saya.
- Gratis, belum enrolled: arahkan tamu ke login; pengguna login dapat memanggil
  `POST /api/mobile/catalog/{courseId}/daftar-gratis` lalu refresh status.
- Berbayar, belum enrolled: gunakan alur kode akses yang tersedia. API mobile
  tidak menyediakan checkout course berbayar.
- Jangan menambahkan course yang baru dilihat ke cache Kursus Saya.
  Sumber Kursus Saya adalah `/api/mobile/courses`.
- Course privat yang tersedia untuk pembelajaran tidak harus memiliki detail
  katalog; tangani `404` tanpa memblokir akses belajar yang sah.

## Loading, error, dan verifikasi

Loading: tampilkan skeleton/indikator. `401`: alur sesi berakhir/login. `404`:
“Course tidak tersedia”. `429`: ikuti `retry_after`/`Retry-After`. Gangguan jaringan
atau `5xx`: tombol Coba Lagi. Refresh detail untuk membaca perubahan dari admin.

- [ ] Tamu dan pengguna login dapat membuka detail.
- [ ] Sales profile null atau field kosong tidak menyebabkan crash/heading kosong.
- [ ] Tidak ada headline ganda; newline dan karakter khusus tampil benar.
- [ ] Durasi 45, 480, 510 menjadi 45 menit, 8 jam, 8 jam 30 menit.
- [ ] FAQ panjang bisa diperluas dan halaman tetap bisa di-scroll.
- [ ] URL video kosong/tidak valid disembunyikan; launcher gagal ditangani.
- [ ] Course hidden/draft tidak ditampilkan sebagai data terbaru setelah `404`.
- [ ] Membuka detail tidak otomatis menambahkan course ke Kursus Saya.

Contoh ini perlu disesuaikan dan diuji pada proyek Flutter. Referensi endpoint:
[MOBILE-CATALOG-API.md](MOBILE-CATALOG-API.md).
