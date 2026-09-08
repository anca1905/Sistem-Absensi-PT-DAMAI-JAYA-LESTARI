# Daftar Lampiran Program Aplikasi (Sistem Absensi PT Damai Jaya Lestari)

Berikut adalah struktur dan daftar modul (file program) yang membangun **Sistem Absensi PT Damai Jaya Lestari** dan dapat disertakan sebagai lampiran (listing program) pada laporan skripsi:

## 1. Konfigurasi dan Helper (`/config`)
File-file pada folder ini menangani koneksi ke database MySQL serta berisi fungsi-fungsi umum yang digunakan berulang kali (helper).
- `config.php`: Konfigurasi koneksi ke database.
- `functions.php`: Kumpulan fungsi untuk formatting, query, atau pengambilan data khusus (helper utama).
- `alert_helper.php`: Fungsi untuk menampilkan notifikasi/alert flash session.
- `wa_helper.php`: Fungsi untuk interaksi HTTP request ke WhatsApp Bot (API).

## 2. Halaman Utama dan Autentikasi (Root Directory)
- `index.php`: Halaman login dan proses autentikasi (pengecekan level role user: admin, pimpinan, pengawas, mandor, kerani, keuangan, karyawan).
- `logout.php`: Proses mengakhiri session user dan kembali ke halaman login.

## 3. Modul Administrator (`/admin`)
Merupakan hak akses tertinggi yang mengelola semua master data dan fitur krusial.
- `index.php`: Dashboard admin.
- `afdeling.php`: Kelola data afdeling/lokasi kerja.
- `personil.php`: Kelola master data personil/karyawan.
- `penggajian.php`: Modul pengelolaan penggajian karyawan.
- `cetak_kartu.php`: Fitur untuk mencetak kartu ID (QR Code) karyawan.
- `cetak_penggajian.php`: Fitur cetak laporan slip/penggajian.
- `scan_pengawas.php`: Modul integrasi scan QR Code presensi.
- `whatsapp.php` & `start_wa_server.php`: Pengelolaan integrasi bot WhatsApp.

## 4. Modul Pimpinan (`/pimpinan`)
Digunakan oleh pimpinan untuk monitoring dan melihat laporan.
- `index.php`: Dashboard pimpinan dengan ringkasan informasi.
- `karyawan.php`: Melihat daftar karyawan aktif.
- `/report`: Direktori khusus cetak laporan absensi/kinerja.

## 5. Modul Pengawas (`/pengawas`)
Role yang bertugas mengawasi presensi harian dan operasional lapangan.
- `index.php`: Dashboard pengawas.
- `buat_objek_kerja.php` & `form_harian.php`: Pembagian objek kerja dan form pelaporan lapangan harian.
- `scan_pengawas.php` & `proses_absen.php`: Fitur pemindaian (scanner) QR Code dari karyawan untuk direkap langsung sebagai presensi.
- `validasi_izin.php`: Memvalidasi atau menolak pengajuan izin karyawan.
- `absen_mandiri.php` & `rekapan_absensi.php`: Modul pencatatan absensi.
- Laporan lapangan: `laporan_kinerja.php`, `laporan_mingguan.php`, `laporan_keseluruhan.php`.
- `cetak_kartu.php`: Cetak kartu QR mandiri/bawahan.

## 6. Modul Kerani (`/kerani`)
Bagian administrasi afdeling/lapangan untuk mengelola absensi dan izin.
- `index.php`: Dashboard kerani.
- `data_personil.php`: Melihat data personil yang ditugaskan.
- `scanner.php`: Interface alternatif untuk pemindaian QR absensi oleh kerani.
- `objek_kerja.php`: Input pencapaian objek kerja.
- `validasi_izin.php`: Validasi perizinan.
- Modul Laporan: `laporan_absensi.php`, `laporan_individu.php`, `laporan_keseluruhan.php`, `laporan_kinerja.php`, `laporan_kinerja_detail.php`.

## 7. Modul Mandor (`/mandor`)
Digunakan oleh mandor yang mengawasi kelompok karyawan/buruh lapangan.
- `index.php`: Dashboard ringkasan absensi tim.
- `objek_kerja.php`: Pemantauan atau input target objek kerja lapangan.
- `absen_mandiri.php` & `rekapan_absensi.php`: Presensi mandiri mandor.
- `form_izin.php`: Pengajuan perizinan.
- `slip_gaji.php`: Melihat/cetak slip gaji.

## 8. Modul Karyawan (`/karyawan`)
Role terbawah (pekerja/buruh biasa).
- `index.php`: Dashboard karyawan (menampilkan QR Code user sendiri).
- `absen_mandiri.php`: Melakukan presensi absensi mandiri.
- `form_izin.php`: Pengajuan surat izin (sakit, cuti, dll).
- `logbook.php` & `objek_kerja.php`: Mencatat tugas atau jurnal (logbook) yang dilakukan harian.
- `rekapan_absensi.php`: Melihat history masuk/izin/alpha.
- `slip_gaji.php`: Menampilkan total gaji/slip elektronik.

## 9. Modul Keuangan (`/keuangan`)
Digunakan oleh bagian finansial untuk merekapitulasi penggajian.
- `index.php`: Dashboard keuangan.
- `lap_keseluruhan.php` & `laporan_absensi.php`: Sinkronisasi jumlah absensi dengan penggajian yang akan dibayarkan.

## 10. API dan Layanan Node.js Bot (`/wa-bot`)
Direktori ini memuat service terpisah (menggunakan Node.js) yang bertanggung jawab sebagai WhatsApp API Gateway.
- `package.json`: Konfigurasi dependensi project (library `whatsapp-web.js`).
- `server.js`: Source code utama (Express JS API) yang me-listen request dari PHP dan mengirimkan pesan WhatsApp secara otomatis (notifikasi masuk, slip gaji, izin disetujui, dll).

---

> **Tips Untuk Skripsi:** 
> Anda tidak perlu mencetak semua file pada lampiran skripsi. Cukup lampirkan file krusial yang menunjukkan alur logika dan algoritma utama, seperti: `config/config.php`, `index.php` (login), `pengawas/scan_pengawas.php` (algoritma QR Code scanner), `kerani/validasi_izin.php`, dan file layanan integrasi WhatsApp `wa-bot/server.js`.
