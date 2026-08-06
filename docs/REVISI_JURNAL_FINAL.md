# Revisi Final Jurnal

Dokumen ini berisi teks pengganti yang harus dimasukkan ke jurnal agar naskah konsisten dengan implementasi sistem.

## Revisi abstrak

Penelitian ini bertujuan merancang dan membangun Sistem Informasi Rekomendasi dan E-Booking Lapangan Badminton Berbasis Web di Kota Ambon. Sistem dikembangkan menggunakan framework Laravel dengan arsitektur Model-View-Controller (MVC). Metode Content-Based Filtering dengan TF-IDF dan Cosine Similarity digunakan sebagai metode utama untuk menghasilkan rekomendasi berdasarkan karakteristik lapangan dan preferensi pengguna, serta dilengkapi fallback rule-based apabila hasil rekomendasi utama tidak tersedia. Sistem terintegrasi dengan Midtrans untuk pembayaran digital dan FlowKirim WhatsApp untuk konfirmasi pembayaran serta undangan rating setelah permainan selesai.

## Revisi alur booking

Pelanggan dapat mengakses website tanpa login, melihat informasi dan rekomendasi lapangan, memilih jadwal, lalu mengisi data customer. Sistem membuat booking dengan status `pending` dan menahan slot dalam batas waktu pembayaran. Pengguna kemudian diarahkan ke Midtrans. Setelah pembayaran berhasil dikonfirmasi melalui webhook, sistem memperbarui status payment menjadi `success` dan status booking menjadi `paid`, membuat invoice, serta mengirimkan konfirmasi WhatsApp. Booking yang telah digunakan ditandai owner sebagai `finished`. Pada tahap tersebut, sistem mengirimkan tautan rating khusus kepada pelanggan.

## Revisi ERD

Entity Relationship Diagram digunakan untuk menggambarkan hubungan antarentitas dalam Sistem Informasi Rekomendasi dan E-Booking Lapangan Badminton Berbasis Web di Kota Ambon. Entitas utama terdiri atas Users, Badminton Fields, Facilities, Badminton Field Facility, Badminton Field Gallery Images, Bookings, Payments, dan Ratings.

Users menyimpan data admin dan owner. Setiap owner dapat memiliki satu atau lebih Badminton Fields. Setiap lapangan dapat memiliki beberapa fasilitas melalui Badminton Field Facility dan beberapa gambar melalui Badminton Field Gallery Images. Sistem mendukung guest booking, sehingga data pelanggan seperti nama, nomor kontak, email, tanggal, waktu bermain, dan status booking disimpan pada Bookings. Jika pelanggan memiliki akun, booking dapat dihubungkan dengan Users melalui `user_id`.

Bookings memiliki hubungan one-to-many dengan Payments karena satu booking dapat memiliki satu atau lebih percobaan transaksi pembayaran. Hanya payment berstatus `success` yang digunakan untuk mengonfirmasi booking dan membuat invoice. Bookings memiliki hubungan one-to-one dengan Ratings karena satu booking hanya dapat mengirim satu rating. Rating hanya dapat dikirim setelah booking berstatus `finished`.

Jam buka, jam tutup, dan durasi slot disimpan pada Badminton Fields sehingga Schedules tidak dibuat sebagai entitas terpisah. Status notifikasi pembayaran disimpan pada Payments, sedangkan status undangan rating disimpan pada Bookings. Karena itu, Notifications tidak dibuat sebagai entitas utama terpisah pada ERD.

## Revisi implementasi pembayaran dan rating

Setelah pelanggan mengonfirmasi data booking, sistem membuat transaksi Snap Midtrans. Status pembayaran disinkronkan melalui webhook. Jika pembayaran berhasil, sistem memperbarui status payment menjadi `success`, mengubah booking menjadi `paid`, membuat invoice, dan mengirimkan konfirmasi WhatsApp berisi detail booking serta tautan invoice.

Fitur rating dan ulasan digunakan sebagai media evaluasi pengalaman pelanggan setelah menggunakan lapangan. Owner menandai booking sebagai `finished` setelah permainan selesai. Sistem kemudian mengirimkan tautan rating WhatsApp dengan signed URL kepada pelanggan. Form rating dan proses penyimpanan rating memvalidasi status `finished`, sehingga booking yang belum selesai tidak dapat mengirim ulasan. Setiap booking hanya dapat memberikan satu rating dan satu komentar.

## Revisi bagian 3.2 Implementasi Sistem Rekomendasi

Sistem rekomendasi menggunakan Content-Based Filtering dengan TF-IDF dan Cosine Similarity sebagai metode utama. Dokumen lapangan dibentuk dari nama, deskripsi, alamat, harga per jam, jam operasional, durasi slot, koordinat lokasi, dan fasilitas. Preferensi pengguna dibentuk dari kata kunci pencarian, lokasi, anggaran, fasilitas, serta tanggal dan waktu booking.

Apabila pengguna telah login, sistem membentuk profil preferensi berdasarkan riwayat booking, rating, dan ulasan pengguna. Bagi pengguna baru atau apabila proses TF-IDF tidak menghasilkan rekomendasi, sistem menggunakan fallback rule-based dengan mempertimbangkan ketersediaan slot, kesesuaian fasilitas, anggaran, lokasi, dan popularitas lapangan.

Term Frequency dihitung dengan persamaan:

`TF(t,d) = f(t,d) / Σf(t,d)`

Inverse Document Frequency yang digunakan menerapkan smoothing:

`IDF(t) = log((N + 1) / (DF(t) + 1)) + 1`

Bobot TF-IDF diperoleh melalui:

`W(t,d) = TF(t,d) × IDF(t)`

Kemiripan antara vektor preferensi pengguna `A` dan vektor dokumen lapangan `B` dihitung menggunakan:

`cos(θ) = (A · B) / (‖A‖ × ‖B‖)`

Nilai similarity berada pada rentang 0 hingga 1 karena seluruh bobot bernilai nonnegatif. Nilai tersebut dikalikan 100 untuk keperluan skor tampilan dan diurutkan dari nilai tertinggi ke terendah. Rating dan ulasan tidak menjadi token dokumen lapangan secara langsung, tetapi memengaruhi pembentukan profil pengguna dan bobot popularitas pada kondisi pengguna baru.

## Revisi pengujian dan kesimpulan

Pengujian rekomendasi sebaiknya dilaporkan sebagai pengujian kesesuaian skenario, bukan akurasi 100 persen, apabila belum menggunakan ground truth dan metrik seperti Precision@K atau Recall@K. Gunakan kalimat: “Seluruh rekomendasi pada skenario pengujian memenuhi kriteria yang telah ditetapkan. Hasil ini menunjukkan kesesuaian fungsional rekomendasi pada data uji yang tersedia, namun belum dapat digeneralisasi karena jumlah lapangan yang dievaluasi masih terbatas.”

Nilai 0,99 detik harus disebut sebagai rata-rata Largest Contentful Paint (LCP), bukan rata-rata waktu respons. Laporkan perangkat, browser, kondisi jaringan, jumlah pengulangan, dan tanggal pengujian.

Kesimpulan: Sistem mendukung pencarian lapangan, booking berbasis slot, pembayaran Midtrans, sinkronisasi status melalui webhook, invoice, notifikasi WhatsApp, serta rating setelah booking selesai. Metode Content-Based Filtering dengan TF-IDF dan Cosine Similarity digunakan sebagai metode utama rekomendasi dan dilengkapi fallback rule-based.

## Checklist

- Ganti semua `Whatsapp` menjadi `WhatsApp`.
- Perbaiki kata yang menyatu, misalnya `Content-BasedFiltering`, `paymentgateway`, dan `guestbooking`.
- Ganti `/home` pada tabel pengujian menjadi `/`.
- Hapus klaim notifikasi pengingat jadwal apabila scheduler belum dibuat.
- Tambahkan referensi ilmiah primer tentang Content-Based Filtering, TF-IDF, dan Cosine Similarity.
- Pastikan gambar ERD memperlihatkan relasi Bookings one-to-many Payments.
