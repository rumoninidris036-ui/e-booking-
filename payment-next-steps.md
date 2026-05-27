# Payment Next Steps

Dokumen ini menjadi arahan pengembangan setelah core payment Midtrans sudah berjalan. Fokus berikutnya adalah membuat alur booking lebih cocok untuk customer publik: tidak wajib daftar akun, mendapat invoice, dan menerima notifikasi pembayaran/booking.

## Kondisi Sekarang

- Core payment Midtrans sudah ada: Snap redirect, webhook, status `pending/success/failed`, retry payment, dan validasi nominal/order.
- Booking masih berbasis user login untuk customer.
- Admin dan owner sudah memakai role login.
- Halaman payment sudah bisa dipakai untuk sandbox lokal, termasuk fallback return dari Midtrans.

## Arah Flow Baru

Customer publik tidak perlu membuat akun. Akun hanya wajib untuk:

- Admin: mengelola sistem, owner, lapangan, booking, dan transaksi.
- Owner: mengelola lapangan, jadwal, booking masuk, dan pendapatan.

Customer cukup mengisi data saat booking:

- Nama customer.
- Nomor WhatsApp/Telegram.
- Email opsional.
- Lapangan, tanggal, dan jam.

Setelah payment sukses:

- Booking berubah menjadi `paid`.
- Invoice PDF dibuat.
- Link invoice/booking detail diberikan ke customer.
- Notifikasi Telegram dikirim ke customer atau channel operasional.

## Sprint A: Guest Booking

Tujuan: customer bisa booking tanpa login.

Task:

- Ubah schema booking agar menyimpan snapshot customer:
  - `customer_name`
  - `customer_phone`
  - `customer_email`
  - `customer_telegram_chat_id` nullable
- Jadikan `bookings.user_id` nullable, atau tetap isi jika customer login.
- Buat request validasi guest booking.
- Update booking service agar menerima customer snapshot.
- Pastikan anti double booking tetap memakai mekanisme yang sama.
- Buat signed URL untuk melihat booking tanpa login.

Output:

- Guest bisa booking dan lanjut bayar.
- Customer tidak wajib register/login.
- Admin/owner tetap bisa melihat data customer dari booking.

## Sprint B: Invoice PDF

Tujuan: setiap payment sukses menghasilkan invoice yang bisa diunduh.

Task:

- Install library PDF Laravel, kandidat:
  - `barryvdh/laravel-dompdf`
- Buat tabel/kolom invoice:
  - `invoice_number`
  - `invoice_pdf_path`
  - `invoice_generated_at`
- Buat `InvoiceService`.
- Buat view invoice PDF:
  - logo/nama aplikasi
  - booking code
  - invoice number
  - nama customer
  - lapangan
  - tanggal dan jam
  - status payment
  - nominal
- Generate invoice otomatis saat payment menjadi `success`.
- Buat endpoint download invoice:
  - via authenticated user/admin/owner
  - via signed URL untuk guest customer

Output:

- Invoice PDF otomatis dibuat setelah payment sukses.
- Customer bisa download invoice tanpa login melalui link aman.
- Owner/admin bisa melihat invoice dari data booking/payment.

## Sprint C: Telegram Notification

Tujuan: customer atau operasional mendapat notifikasi otomatis.

Task:

- Tambah konfigurasi `.env`:
  - `TELEGRAM_BOT_TOKEN`
  - `TELEGRAM_OPERATIONS_CHAT_ID`
- Buat `TelegramNotificationService`.
- Kirim notifikasi saat:
  - booking dibuat
  - payment sukses
  - payment gagal/expired
  - booking dibatalkan
- Format pesan payment sukses:
  - booking code
  - nama customer
  - lapangan
  - tanggal dan jam
  - nominal
  - link invoice
- Untuk customer pribadi, butuh strategi mendapatkan `chat_id`:
  - opsi awal: kirim ke channel/group operasional dulu
  - opsi lanjut: customer klik bot Telegram untuk menghubungkan chat ID

Output:

- Owner/admin operasional mendapat notifikasi booking/payment.
- Customer bisa menerima link invoice jika chat ID sudah tersedia.

## Sprint D: Security & Reliability

Tujuan: invoice dan notifikasi aman serta tidak dobel.

Task:

- Gunakan signed route untuk invoice guest.
- Jangan expose path storage langsung tanpa validasi akses.
- Pastikan invoice hanya dibuat sekali per payment sukses.
- Pastikan notifikasi Telegram idempotent:
  - simpan `telegram_notified_at`
  - atau gunakan table notification logs
- Tambah retry queue untuk Telegram jika request gagal.
- Masking data sensitif di log.

Output:

- Invoice tidak bocor ke user lain.
- Notifikasi tidak terkirim dobel.
- Jika Telegram gagal sementara, sistem bisa retry.

## Sprint E: Testing

Tujuan: flow baru aman sebelum masuk UI penuh.

Test minimum:

- Guest bisa membuat booking.
- Guest booking mencegah double booking.
- Payment sukses generate invoice.
- Invoice signed URL bisa dibuka customer.
- Invoice signed URL invalid/expired ditolak.
- Telegram notification dikirim saat payment sukses.
- Telegram notification tidak dobel ketika webhook dikirim ulang.
- Admin/owner tetap bisa melihat booking guest.

## Prioritas Implementasi

1. Guest booking data model.
2. Invoice PDF service dan download endpoint.
3. Generate invoice otomatis saat payment sukses.
4. Telegram notification untuk channel operasional.
5. Telegram customer notification setelah mekanisme chat ID siap.
6. UI polish untuk booking success dan invoice download.

## Catatan Penting

- Untuk production, payment status harus tetap mengandalkan webhook Midtrans dari URL publik.
- Fallback return dari Midtrans hanya untuk membantu sandbox/local development.
- Jangan menaruh token Telegram atau key payment di repository.
- Invoice harus memakai signed URL atau authorization, karena berisi data customer dan transaksi.
