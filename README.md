# SmashCourt - E-Booking Lapangan Badminton

SmashCourt adalah aplikasi web untuk pencarian, pemesanan, pembayaran, dan pengelolaan lapangan badminton. Aplikasi ini mendukung tiga alur utama: customer mencari dan booking lapangan, owner mengelola lapangan beserta jadwal dan booking, serta admin memantau seluruh aktivitas platform.

## Fitur Utama

### Halaman Publik dan Customer

- Melihat daftar lapangan badminton aktif.
- Melihat detail lapangan, fasilitas, harga, alamat, owner, dan lokasi berbasis peta.
- Melihat jadwal ketersediaan slot lapangan.
- Membuat booking berdasarkan tanggal dan slot waktu.
- Melakukan pembayaran booking melalui Midtrans Snap.
- Melihat status pembayaran dan halaman return pembayaran.
- Mengunduh invoice pembayaran.
- Melihat daftar booking customer setelah login.
- Membatalkan booking sesuai aturan yang tersedia.
- Mengelola profil akun.

### Owner Venue

- Dashboard owner berisi ringkasan booking, pending booking, paid booking, revenue, dan lapangan aktif.
- Grafik tren booking dan revenue.
- Statistik performa lapangan.
- Daftar booking terbaru dan transaksi terbaru.
- Notifikasi operasional booking.
- Manajemen lapangan:
  - tambah lapangan;
  - edit nama, deskripsi, alamat, harga, status aktif, cover image;
  - edit jam buka, jam tutup, dan durasi slot;
  - pilih fasilitas;
  - pin koordinat lapangan di peta OpenStreetMap/Leaflet;
  - hapus lapangan.
- Manajemen jadwal lapangan:
  - pilih lapangan dan tanggal;
  - melihat slot tersedia, slot terisi, pending, dan paid.
- Manajemen booking:
  - melihat daftar booking masuk;
  - filter booking berdasarkan status, lapangan, tanggal, dan pencarian;
  - update status booking.
- Top bar profil dengan menu Profil dan Log Out.

### Admin

- Dashboard admin untuk memantau platform secara keseluruhan.
- Ringkasan total user, owner, lapangan, booking, transaksi, dan revenue.
- Monitoring owner terbaru dan top owner.
- Monitoring lapangan terbaru.
- Monitoring booking terbaru.
- Manajemen user owner:
  - melihat daftar owner terdaftar;
  - melihat tanggal daftar, jumlah lapangan, booking, dan revenue per owner;
  - filter dan sortir owner.
- Manajemen semua lapangan owner:
  - melihat semua lapangan dari semua owner;
  - mengetahui lapangan tersebut milik owner siapa;
  - melihat nama dan email owner di setiap lapangan;
  - mengedit data lapangan owner.
- Top bar profil dengan menu Profil dan Log Out.

## Teknologi yang Digunakan

### Backend

- PHP 8.2+
- Laravel 12
- Laravel Breeze untuk autentikasi dasar
- Laravel Eloquent ORM
- Laravel Form Request untuk validasi
- Laravel Policy untuk authorization
- Laravel Resource untuk response data tertentu
- Laravel Migrations dan Seeders
- PHPUnit untuk automated testing

### Frontend

- Blade template engine
- Tailwind CSS
- Tailwind Forms plugin
- Alpine.js untuk interaksi ringan seperti dropdown
- Vite untuk asset bundling
- Chart.js untuk visualisasi dashboard owner
- Leaflet.js dan OpenStreetMap untuk peta dan marker lapangan

### Payment dan Dokumen

- Midtrans Snap untuk pembayaran online
- Midtrans webhook untuk sinkronisasi status pembayaran
- barryvdh/laravel-dompdf untuk generate invoice PDF

### Authorization dan Role

- spatie/laravel-permission
- Role utama:
  - `admin`
  - `owner`
  - `customer`

### Infrastruktur Development

- Docker Compose
- Nginx
- PHP-FPM container
- MySQL 8.4
- phpMyAdmin

## Struktur Modul Penting

```text
app/
  Actions/
    Auth/                 # aksi register user
    Field/                # create, update, delete lapangan
  Contracts/Payments/     # kontrak gateway pembayaran
  Http/
    Controllers/
      Admin/              # dashboard, users, semua lapangan
      Auth/               # login, register, reset password
      Owner/              # dashboard, lapangan, booking, jadwal
      PublicPage/         # halaman publik, booking, payment
      Webhooks/           # webhook Midtrans
    Requests/             # validasi form/request
    Resources/            # resource API
  Models/                 # User, BadmintonField, Booking, Payment, Facility
  Policies/               # authorization booking dan lapangan
  Services/
    Booking/              # service jadwal slot lapangan
    Invoices/             # service invoice
    Payments/             # service pembayaran Midtrans

routes/
  web.php                 # halaman publik, booking, payment, profile
  auth.php                # route auth Breeze
  owner.php               # route panel owner
  admin.php               # route panel admin

resources/views/
  public/                 # halaman lapangan publik dan booking
  owner/                  # panel owner
  admin/                  # panel admin
  layouts/                # sidebar dan topbar shared
  payments/               # halaman pembayaran
  invoices/               # template invoice

database/
  migrations/             # struktur database
  seeders/                # data awal role, fasilitas, user, lapangan

tests/
  Feature/                # pengujian alur fitur utama
```

## Model Data Utama

- `User`: akun pengguna aplikasi, bisa memiliki role admin, owner, atau customer.
- `BadmintonField`: data lapangan milik owner, termasuk harga, alamat, koordinat, cover image, status aktif, dan pengaturan jadwal.
- `Facility`: fasilitas lapangan, terhubung many-to-many dengan lapangan.
- `Booking`: data pemesanan slot lapangan.
- `Payment`: data pembayaran booking, Snap token, status Midtrans, dan data invoice.

## Alur Booking dan Payment

1. Customer memilih lapangan dari halaman publik.
2. Customer membuka halaman booking lapangan.
3. Sistem menampilkan slot jadwal berdasarkan tanggal, jam buka, jam tutup, durasi slot, dan booking yang sudah ada.
4. Customer membuat booking.
5. Booking dibuat dengan status pending.
6. Customer membuat pembayaran Midtrans Snap.
7. Sistem menyimpan payment pending beserta Snap token/redirect URL.
8. Midtrans mengirim webhook status transaksi.
9. Sistem memverifikasi notifikasi, memperbarui status payment, dan memperbarui status booking.
10. Jika payment sukses, invoice dapat dibuat dan diunduh.

## Panel Owner

Route owner berada di prefix:

```text
/owner
```

Route penting:

- `/owner/dashboard`
- `/owner/fields`
- `/owner/schedules`
- `/owner/bookings`

Owner hanya dapat mengakses dan mengubah data lapangan/booking miliknya sendiri.

## Panel Admin

Route admin berada di prefix:

```text
/admin
```

Route penting:

- `/admin/dashboard`
- `/admin/users`
- `/admin/fields`

Admin dapat memantau data seluruh platform dan mengedit lapangan milik owner. Pada halaman semua lapangan, admin bisa melihat informasi pemilik lapangan agar jelas lapangan tersebut milik owner siapa.

## Menjalankan Aplikasi dengan Docker

Jalankan service:

```bash
docker compose up -d --build
```

Install dependency PHP:

```bash
docker compose exec app composer install
```

Install dependency frontend dari host yang memiliki Node.js:

```bash
npm install
```

Siapkan environment:

```bash
cp .env.example .env
docker compose exec app php artisan key:generate
```

Jalankan migrasi dan seeder:

```bash
docker compose exec app php artisan migrate --seed
```

Buat storage link:

```bash
docker compose exec app php artisan storage:link
```

Build asset:

```bash
npm run build
```

Akses aplikasi:

```text
http://localhost:18000
```

phpMyAdmin:

```text
http://localhost:18081
```

## Menjalankan Aplikasi Tanpa Docker

Install dependency:

```bash
composer install
npm install
```

Siapkan environment:

```bash
cp .env.example .env
php artisan key:generate
```

Jalankan migrasi dan seeder:

```bash
php artisan migrate --seed
php artisan storage:link
```

Jalankan server development:

```bash
composer run dev
```

Atau jalankan Laravel dan Vite terpisah:

```bash
php artisan serve
npm run dev
```

## Konfigurasi Environment Penting

Sesuaikan `.env` untuk database:

```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=ebooking_court
DB_USERNAME=ebooking_user
DB_PASSWORD=secret
```

Sesuaikan konfigurasi Midtrans:

```env
MIDTRANS_SERVER_KEY=
MIDTRANS_CLIENT_KEY=
MIDTRANS_IS_PRODUCTION=false
```

Webhook Midtrans diarahkan ke:

```text
/webhooks/midtrans
```

## Testing

Jalankan seluruh test:

```bash
docker compose exec app php artisan test
```

Jalankan test tertentu:

```bash
docker compose exec app php artisan test --filter=DashboardTest
```

Area yang sudah memiliki feature test meliputi:

- autentikasi dan registrasi;
- profile;
- dashboard owner dan admin;
- manajemen lapangan owner;
- manajemen jadwal owner;
- manajemen booking owner;
- lifecycle booking customer;
- integrasi payment Midtrans dengan fake gateway.

## Catatan Pengembangan

- User yang register dari halaman publik otomatis diarahkan sebagai owner sesuai implementasi saat ini.
- Data role, fasilitas, user awal, dan lapangan awal disediakan oleh seeder.
- Halaman custom dashboard menggunakan Blade dengan Tailwind CDN pada beberapa view, sementara asset utama tetap dikelola oleh Vite.
- Untuk development lokal Midtrans, aplikasi menyediakan fallback return browser pada environment local/testing karena webhook tidak selalu bisa menjangkau localhost.
