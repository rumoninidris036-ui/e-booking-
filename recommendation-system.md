# Recommendation System

Dokumentasi module rekomendasi lapangan badminton.

## Tujuan

- Kasih rekomendasi lapangan paling relevan.
- Pakai histori booking user jika ada.
- Tetap kasih hasil untuk user baru.
- Pertimbangkan filter pencarian dari user.

## Entry Point

- `App\Services\Recommendations\FieldRecommendationService`

Service ini jadi pintu masuk utama untuk ambil rekomendasi.

## Scope Module

Folder `app/Services/Recommendations/` berisi service yang ngurus:

- normalisasi filter rekomendasi
- tokenisasi data lapangan
- pembentukan profile user
- perhitungan TF-IDF
- scoring dan ranking hasil
- fallback rule-based

## Flow Utama

1. Request masuk dengan filter rekomendasi.
2. Filter dinormalisasi ke `FieldRecommendationCriteria`.
3. `RecommendationService` pilih algoritma aktif dari config.
4. Mode aktif:
    - `tfidf` → pakai TF-IDF scoring.
    - `legacy` / `rule-based` / `rule_based` → pakai rule-based fallback.
5. Jika TF-IDF gagal atau hasil kosong, sistem fallback ke rule-based.
6. Jika user kirim kata kunci pencarian, query itu ikut dipakai ke profile vector TF-IDF.

## Class yang Dipakai

### `FieldRecommendationCriteria`

Normalisasi input filter.

Field yang diterima:

- `limit`
- `searchQuery` / payload `q`
- `date`
- `start_time`
- `end_time`
- `budget`
- `latitude`
- `longitude`
- `facility_slugs`
- `exclude_field_ids`

Aturan:

- `limit` dibatasi 1 sampai 12.
- `date` dipotong ke format tanggal.
- `start_time` dan `end_time` dipotong ke format jam.
- `searchQuery` di-trim, lalu kosong berubah jadi `null`.
- list fasilitas dan exclude ID dibersihkan dari nilai kosong.
- nilai `limit` dibatasi 1 sampai 12.

### `searchQuery`

Query teks ini dipakai buat:

- cari kata kunci user di mode TF-IDF
- bantu rekomendasi saat user cari nama lokasi, fasilitas, atau tema lapangan
- tetap aman kalau kosong atau tidak dikirim

### `FieldRecommendationService`

Wrapper tipis untuk controller atau route.

Peran:

- terima `FieldRecommendationCriteria`
- delegasi ke `RecommendationService`
- jaga controller tetap tipis

### `RecommendationService`

Orchestrator algoritma.

Peran:

- baca config `services.recommendations.algorithm`
- pilih engine `tfidf` atau `rule-based`
- tangani fallback kalau engine utama error atau hasil kosong

### `DocumentBuilderService`

Ubah data lapangan jadi token dokumen.

Sumber token:

- nama lapangan
- deskripsi
- alamat
- harga per jam
- jam buka dan tutup
- durasi slot
- koordinat
- fasilitas

Output utama:

- `text` gabungan semua token
- `tokens` list token hasil normalisasi
- `term_counts` frekuensi token
- `features` token per sumber data

### `UserProfileService`

Bangun profile user dari:

- histori booking sukses
- rating dan komentar
- fallback ke lapangan populer kalau user baru

Output profile:

- `tokens` untuk query user
- `vector` TF-IDF profile
- `source` asal profile: `history` atau `fallback`
- `reasons` alasan kenapa profile terbentuk

### `TfidfRecommendationService`

Engine utama scoring.

Langkah:

- ambil field aktif
- eager load `facilities` dan `owner`
- hitung rata-rata rating
- hitung booking 30 hari terakhir
- filter field yang di-exclude
- filter slot jika user kirim jadwal
- bangun dokumen lapangan
- hitung IDF
- bangun profile user
- bentuk query vector dari `searchQuery` kalau ada, atau dari profile user kalau tidak ada query
- hitung cosine similarity
- urutkan score tertinggi
- ambil top result sesuai `limit`

### `TFIDFService`

Utility matematis untuk TF-IDF.

Fungsi:

- `termFrequency()` hitung frekuensi token per dokumen
- `documentFrequency()` hitung jumlah dokumen yang punya token
- `inverseDocumentFrequency()` hitung bobot IDF
- `tfIdf()` bentuk vector TF-IDF
- `magnitude()` hitung panjang vector
- `alignVector()` samakan vocabulary antar vector

### `CosineSimilarityService`

Hitung kemiripan dua vector.

Dipakai untuk:

- bandingkan profile user dengan vector lapangan
- hasilkan skor rekomendasi final

### `FieldRecommendationScorer`

Scoring rule-based.

Peran:

- nilai slot booking
- nilai preferensi fasilitas
- nilai budget
- nilai popularitas booking terakhir
- nilai kedekatan lokasi

### `RuleBasedRecommendationService`

Fallback recommendation engine.

Peran:

- ambil semua field aktif
- hitung statistik dasar
- panggil scorer per field
- urutkan hasil dari score tertinggi
- batasi hasil sesuai `limit`

## Filter yang Didukung

- kata kunci pencarian `q`
- tanggal booking
- jam mulai dan jam selesai
- budget
- lokasi latitude dan longitude
- preferensi fasilitas
- exclude field ID

## Output

Format rekomendasi:

```php
[
    'field' => BadmintonField,
    'score' => float,
    'reasons' => list<string>,
]
```

## Alasan Rekomendasi

`reasons` bisa berisi:

- cocok dengan preferensi kata kunci
- disesuaikan dari histori booking
- diprioritaskan dari lapangan populer
- fasilitas yang sesuai
- lokasi lapangan
- budget dipertimbangkan
- slot waktu tersedia

## Perilaku Fallback

- User baru tetap dapat rekomendasi.
- Data kosong pada fasilitas, deskripsi, rating, atau lokasi tidak bikin error.
- Kalau TF-IDF tidak menghasilkan hasil, sistem kembali ke rule-based.
- Kalau filter terlalu sempit dan hasil kosong, response tetap aman dan kosong.

## Konfigurasi

Algorithm dipilih lewat config:

```php
config('services.recommendations.algorithm')
```

Nilai yang umum dipakai:

- `tfidf`
- `legacy`
- `rule-based`
- `rule_based`

## Catatan Implementasi

- Tokenisasi selalu lewat `DocumentBuilderService`.
- Service ini cocok dipanggil dari controller publik.
- Logic scoring tidak ditaruh di controller.
- Struktur ini aman buat dikembangkan ke algoritma baru nanti.
- Semua service di folder ini fokus ke 1 tugas utama per class.
- Endpoint public yang umum dipakai:
    - `GET /fields/recommendations`
    - payload `q`, `limit`, `date`, `start_time`, `end_time`, `budget`, `latitude`, `longitude`, `facility_slugs`, `exclude_field_ids`
