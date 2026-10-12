<?php
/**
 * Cron Job Script: Otomatisasi Status Alpha Absensi PT Damai Jaya Lestari
 * 
 * Script ini dapat dijalankan secara terjadwal:
 * 1. cPanel Cron Job: /usr/local/bin/php /home/user/public_html/cron_auto_alpha.php
 * 2. CLI / Terminal: php cron_auto_alpha.php
 * 3. Browser / Webhook URL: https://domain.com/cron_auto_alpha.php
 * 
 * Aturan:
 * - Hari Minggu otomatis LIBUR (tidak di-set alpha).
 * - Menangani tanggal-tanggal yang terlewat (backfill).
 * - Hanya meng-alpha karyawan/mandor/pengawas/kerani yang belum absen & tidak ada izin.
 */

require_once __DIR__ . '/config/config.php';

header('Content-Type: text/plain; charset=utf-8');

$timestamp = date('Y-m-d H:i:s');
echo "[$timestamp] Memulai proses pengecekan Auto-Alpha...\n";

// Jalankan auto-alpha untuk bulan berjalan
checkAndSetAlpha($conn);

$end_timestamp = date('Y-m-d H:i:s');
echo "[$end_timestamp] Proses Auto-Alpha selesai dengan sukses.\n";
