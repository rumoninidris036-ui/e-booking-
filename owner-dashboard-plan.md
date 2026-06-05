# Owner Dashboard Plan

Dokumen ini adalah kerangka awal untuk dashboard owner. Tujuannya agar layout, data, dan prioritas fitur bisa direvisi dulu sebelum masuk implementasi Blade/controller/service tambahan.

## Tujuan Dashboard Owner

Dashboard owner dipakai untuk memantau operasional lapangan milik owner secara cepat:

- Melihat performa booking.
- Melihat pendapatan dari payment sukses.
- Melihat slot/jadwal yang ramai.
- Melihat statistik setiap lapangan.
- Memantau booking terbaru dan status pembayaran.
- Mengambil keputusan operasional harian tanpa masuk ke menu detail satu per satu.

## Batasan Role

Dashboard owner hanya menampilkan data milik owner yang sedang login.

Data yang boleh muncul:

- Lapangan dengan `badminton_fields.owner_id = auth()->id()`.
- Booking yang terhubung ke lapangan milik owner.
- Payment yang terhubung ke booking lapangan milik owner.
- Customer snapshot dari booking guest atau user terdaftar.

Data yang tidak boleh muncul:

- Data lapangan owner lain.
- Booking owner lain.
- Payment owner lain.
- Secret key payment, snap response mentah, webhook payload mentah.

## Referensi Visual

Arah visual mengikuti screenshot yang diberikan:

- Sidebar kiri gelap.
- Content area putih/terang.
- Topbar dengan search, notification, dan profile owner.
- KPI cards di bagian atas.
- Chart utama booking/revenue.
- Panel statistik lapangan.
- Recent bookings table.
- Quick action button.

Catatan: meskipun referensi terlihat seperti admin dashboard, versi owner harus lebih fokus ke operasional venue/lapangan, bukan total platform.

## Struktur Layout

### Sidebar

Menu utama owner:

- Dashboard
- Lapangan Saya
- Jadwal
- Booking
- Transaksi
- Invoice
- Notifikasi
- Pengaturan

Menu opsional fase lanjut:

- Promo
- Review Customer
- Laporan Export

### Topbar

Elemen:

- Search global untuk booking code, nama customer, lapangan.
- Notification icon untuk booking/payment terbaru.
- Profile owner.
- Tombol quick action: `Tambah Lapangan` atau `Export Report`.

Search awal cukup frontend filter atau query sederhana, nanti bisa dibuat endpoint khusus.

### Header Page

Judul:

`Dashboard Owner`

Subtitle:

`Pantau booking, pendapatan, dan performa lapangan kamu secara real-time.`

Filter periode:

- Hari ini
- 7 hari
- Bulan ini
- Custom date range

Filter lapangan:

- Semua lapangan
- Per lapangan

## KPI Cards

KPI utama yang tampil di atas:

- Total Booking
- Booking Pending
- Booking Paid
- Total Revenue
- Active Fields

KPI tambahan jika ruang cukup:

- Cancelled Bookings
- Successful Transactions
- Occupancy Rate
- Average Revenue per Booking

Format card:

- Label kecil uppercase.
- Nilai besar.
- Trend kecil dibanding periode sebelumnya.
- Icon sederhana.

Contoh:

```text
TOTAL BOOKING
342
+12.4% bulan ini
```

```text
TOTAL REVENUE
Rp 48.200.000
+15.6% bulan ini
```

## Chart Section

### Booking & Revenue Trend

Chart utama menampilkan:

- Jumlah booking per hari.
- Revenue per hari.
- Periode default 7 hari terakhir.

Pilihan tampilan:

- Line chart untuk booking count.
- Area/line chart untuk revenue.
- Toggle `Bookings` dan `Revenue`.

Data minimal:

```json
{
    "date": "2026-05-29",
    "total_bookings": 12,
    "total_revenue": 1200000
}
```

### Peak Hours

Panel kecil untuk jam ramai:

- 08:00 - 09:00
- 17:00 - 18:00
- 19:00 - 20:00

Data berasal dari grouping booking berdasarkan `start_time` dan `end_time`.

## Field Statistics

Panel statistik lapangan menampilkan performa per lapangan:

- Nama lapangan.
- Status aktif/nonaktif.
- Total booking.
- Total revenue.
- Booking paid.
- Booking pending.
- Occupancy rate.

Format bisa berupa table atau bar list.

Kolom awal:

```text
Field | Status | Bookings | Revenue | Pending | Paid
```

Untuk visual seperti screenshot, bisa dibuat ranking:

```text
Popular Courts
Olympic Arena      145 bookings
Grand Central      112 bookings
Velocity X          96 bookings
```

## Recent Bookings

Table booking terbaru:

- Booking code.
- Customer.
- Lapangan.
- Tanggal.
- Jam.
- Status booking.
- Payment status.
- Amount.
- Action detail.

Customer harus mendukung dua sumber:

- Jika `user_id` ada: pakai `user.name`.
- Jika guest booking: pakai `customer_name`.

Status badge:

- `pending`: kuning/oranye.
- `paid`: hijau.
- `cancelled`: merah.
- `finished`: abu/biru.

Payment status badge:

- `pending`
- `success`
- `failed`

## Transaction Monitoring

Section transaksi owner:

- Total transaksi sukses.
- Total payment pending.
- Total payment failed.
- Recent transactions.
- Link download invoice jika tersedia.

Kolom:

```text
Order ID | Booking Code | Customer | Amount | Status | Paid At | Invoice
```

## Notification Panel

Panel notifikasi ringkas:

- Booking baru dibuat.
- Payment sukses.
- Payment gagal/expired.
- Booking dibatalkan.

Fase awal cukup ambil dari data booking/payment terbaru.

Fase lanjut bisa dibuat table notification logs agar tidak bergantung pada query booking langsung.

## Data Source Backend

Saat ini sudah ada endpoint:

```text
GET /owner/dashboard
```

Route name:

```text
owner.dashboard
```

Middleware:

```text
auth
role:owner
```

Data backend yang sudah ada:

- `summary.total_bookings`
- `summary.pending_bookings`
- `summary.paid_bookings`
- `summary.finished_bookings`
- `summary.cancelled_bookings`
- `summary.total_revenue`
- `summary.successful_transactions`
- `busy_schedules`
- `field_statistics`

Endpoint owner lain yang sudah ada:

```text
GET /owner/fields
GET /owner/fields/{badmintonField}
GET /owner/fields/{badmintonField}/schedule
GET /owner/bookings
GET /owner/bookings/{booking}
PATCH /owner/bookings/{booking}/status
```

## Backend Tambahan Yang Dibutuhkan

Untuk dashboard UI yang lebih lengkap, endpoint owner dashboard perlu diperluas atau dipecah.

Rekomendasi service:

```text
app/Services/Dashboard/OwnerDashboardService.php
```

Controller tetap tipis:

```text
app/Http/Controllers/Owner/DashboardController.php
```

Response yang disarankan:

```json
{
    "data": {
        "filters": {},
        "summary": {},
        "trends": [],
        "peak_hours": [],
        "field_statistics": [],
        "recent_bookings": [],
        "recent_transactions": [],
        "notifications": []
    }
}
```

Query/filter yang perlu didukung:

- `period=today|7_days|month|custom`
- `date_from=YYYY-MM-DD`
- `date_to=YYYY-MM-DD`
- `field_id=1`

## View Yang Akan Dibuat

File Blade kandidat:

```text
resources/views/owner/dashboard.blade.php
```

Jika layout reusable:

```text
resources/views/layouts/owner.blade.php
resources/views/owner/partials/sidebar.blade.php
resources/views/owner/partials/topbar.blade.php
```

Untuk chart:

- Fase awal: pakai SVG/CSS sederhana atau Chart.js CDN.
- Fase lanjut: pakai bundling Vite jika dashboard semakin kompleks.

## Security Notes

Hal penting:

- Semua query wajib scope ke `owner_id`.
- Jangan expose payment payload mentah.
- Jangan tampilkan server key Midtrans.
- Customer contact boleh tampil untuk owner, tapi jangan masuk ke log.
- Invoice download harus tetap melalui route authorized/token.
- Export report nanti harus tetap scope owner.

## MVP Dashboard Owner

Prioritas implementasi pertama:

1. Blade layout owner dashboard.
2. KPI cards.
3. Booking & revenue trend 7 hari.
4. Popular courts/field statistics.
5. Recent bookings.
6. Recent transactions dengan invoice link.

Yang ditunda:

- Real-time websocket.
- Export Excel/PDF report.
- Notification logs lengkap.
- Advanced search global.
- Occupancy rate detail per jam.

## Pertanyaan Untuk Direvisi

Sebelum implementasi, mohon tentukan:

- Dashboard owner ingin tema terang seperti screenshot, atau tetap mengikuti tema gelap SmashCourt?
- Owner perlu bisa export report dari MVP pertama atau belakangan?
- Chart cukup 7 hari terakhir dulu atau wajib custom date range dari awal?
- Recent bookings cukup 5 data atau perlu pagination di dashboard?
- Nama menu owner final apakah pakai Bahasa Indonesia penuh atau campuran English seperti template?

## Output Setelah Disetujui

Setelah dokumen ini direvisi dan disetujui, implementasi berikutnya:

- Buat/rapikan `OwnerDashboardService`.
- Update `Owner\DashboardController` agar bisa return Blade dan JSON.
- Buat layout dashboard owner.
- Buat komponen KPI, chart, field statistics, recent bookings, recent transactions.
- Tambah test untuk filter periode dan scope owner.

## Map & Location Overview

Dashboard owner mendukung integrasi map menggunakan OpenStreetMap (OSM) untuk membantu owner memantau dan mengelola lokasi venue/lapangan.

Fitur ini bersifat operasional dan visual overview, bukan tracking real-time user.

### Tujuan Fitur Map

Map digunakan untuk:

- Menampilkan lokasi seluruh lapangan milik owner.
- Membantu owner mengatur dan memvalidasi lokasi venue.
- Memberikan overview cabang/lapangan secara visual.
- Menampilkan statistik singkat lapangan langsung dari marker map.
- Mendukung pengembangan multi-cabang di masa depan.

### Owner Pin Location

Owner dapat menentukan lokasi lapangan menggunakan fitur pin map.

Flow dasar:

1. Owner membuka form tambah/edit lapangan.
2. Owner memilih lokasi pada map.
3. Sistem menyimpan latitude dan longitude.
4. Marker otomatis tampil pada dashboard owner.

Metode input lokasi:

- Klik langsung pada map.
- Drag marker.
- Search lokasi/alamat (fase lanjut).

### Data Lokasi Lapangan

Field tambahan pada tabel lapangan:

```text
latitude
longitude
address
```

Rekomendasi type database:

```php
latitude  -> decimal(10,7)
longitude -> decimal(10,7)
```

### Dashboard Map Overview

Dashboard owner memiliki panel map overview.

Isi panel:

- Marker semua lapangan milik owner.
- Status lapangan berdasarkan warna marker.
- Popup statistik singkat lapangan.

Contoh popup:

```text
Olympic Arena
12 booking hari ini
Revenue Rp 1.200.000
Status: Active
```

Warna marker:

```text
Hijau  -> Active
Kuning -> Maintenance
Merah  -> Nonactive
```

### Backend Response Tambahan

Tambahkan data berikut pada endpoint dashboard:

```json
{
    "map_fields": [
        {
            "id": 1,
            "name": "Olympic Arena",
            "latitude": -3.6954,
            "longitude": 128.1814,
            "status": "active",
            "total_bookings": 12,
            "total_revenue": 1200000
        }
    ]
}
```

### Integrasi Map

Map menggunakan:

- OpenStreetMap
- Leaflet.js

Fase awal:

- Static marker.
- Popup statistik sederhana.
- Single owner map overview.

Fase lanjut:

- Marker clustering.
- Heatmap booking.
- Radius coverage area.
- Route menuju venue.
- Multi-branch analytics.

### Security Notes untuk Map

- Owner hanya dapat melihat marker venue miliknya sendiri.
- Coordinate venue owner lain tidak boleh terekspos.
- Jangan expose API key sensitif.
- Lokasi customer tidak disimpan pada dashboard map owner kecuali fitur tertentu ditambahkan di masa depan.

### MVP Scope

Fitur map pada MVP:

- Pin lokasi lapangan.
- Simpan latitude & longitude.
- Marker lapangan owner.
- Popup informasi singkat lapangan.

Yang ditunda:

- Live tracking.
- Heatmap analytics.
- Route optimization.
- Geofencing.
- Real-time occupancy map.
