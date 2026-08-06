# Peta Source Code SmashCourt

Dokumen ini adalah titik awal untuk memahami proyek saat presentasi atau ujian.
Kode dibagi berdasarkan tanggung jawab agar tampilan, aturan bisnis, dan akses data
tidak tercampur.

## Alur utama booking

1. Pengunjung membuka halaman lapangan di `app/Http/Controllers/PublicPage/`.
2. `BookingController` menerima formulir booking dan memanggil `BookingService`.
3. `BookingService` memvalidasi jadwal, mengunci data lapangan, lalu membuat booking
   berstatus `pending` selama 10 menit.
4. `PaymentService` membuat transaksi Snap Midtrans.
5. Midtrans memanggil route `/webhooks/midtrans` setelah pembayaran berubah.
6. `PaymentService` memverifikasi webhook, memperbarui payment dan booking menjadi
   sukses/lunas, lalu membuat invoice dan menjadwalkan WhatsApp.
7. `BookingPaymentWhatsAppNotificationService` memastikan satu payment hanya mengirim
   satu pesan WhatsApp, walaupun webhook dikirim ulang.

## Folder inti

| Folder | Fungsi |
| --- | --- |
| `routes/` | Daftar URL dan middleware akses. `web.php` untuk publik, `owner.php` untuk owner, `admin.php` untuk admin. |
| `app/Http/Controllers/` | Menerima request, memanggil service/action, lalu mengembalikan halaman atau JSON. |
| `app/Services/Booking/` | Aturan bisnis booking, slot, expired pending, dan perubahan status booking. |
| `app/Services/Payments/` | Integrasi Midtrans, validasi webhook, dan sinkronisasi pembayaran. |
| `app/Services/Notifications/` | Integrasi FlowKirim dan pesan WhatsApp setelah pembayaran berhasil. |
| `app/Services/Invoices/` | Membuat PDF invoice dan menyimpannya di storage lokal. |
| `app/Services/Dashboard/` | Query statistik dashboard owner. |
| `app/Actions/` | Operasi perubahan data yang spesifik, misalnya tambah/edit/hapus lapangan. |
| `app/Models/` | Representasi tabel database dan relasi Eloquent. |
| `app/Policies/` | Aturan otorisasi: siapa boleh melihat atau mengubah data. |
| `app/Http/Requests/` | Validasi input sebelum masuk controller/service. |
| `resources/views/` | Tampilan Blade. Folder `public`, `owner`, dan `admin` mengikuti jenis pengguna. |
| `database/migrations/` | Riwayat perubahan struktur database. Jalankan melalui `php artisan migrate`. |

## Status penting

### Booking

- `pending`: dibuat, masih menunggu pembayaran.
- `paid`: pembayaran berhasil dan jadwal siap dimainkan.
- `finished`: permainan telah selesai; tetap dianggap **lunas**.
- `expired`: pembayaran tidak selesai dalam batas waktu.
- `cancelled`: dibatalkan oleh customer/owner sesuai aturan.

### Payment

- `pending`: transaksi Midtrans belum berhasil.
- `success`: pembayaran berhasil.
- `failed`: pembayaran gagal.

> Pada statistik, istilah **lunas** berarti booking berstatus `paid` atau `finished`.

## Hal yang perlu dijaga saat deployment

- File `.env` berbeda antara lokal dan VPS; jangan pernah push `.env` ke GitHub.
- Setelah mengubah `.env` di VPS, jalankan `config:clear` lalu `config:cache` di container.
- Setelah menarik perubahan yang memiliki migration, jalankan `php artisan migrate --force` di container.
- Jika tampilan Blade berubah, jalankan `php artisan view:clear`.
