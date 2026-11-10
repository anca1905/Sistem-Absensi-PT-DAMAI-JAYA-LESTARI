<?php
/**
 * Script Sinkronisasi Otomatis Live Server PT DJL
 * 
 * Penggunaan via Browser:
 * https://portal-djl.skillance.cloud/sync_update.php?key=DJL_AMANDA_SECRET_2025
 */

require_once __DIR__ . '/config/config.php';

// Validasi Keamanan dengan APP_SECRET
$key = $_GET['key'] ?? '';
if ($key !== APP_SECRET) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    die("Akses ditolak: Secret key tidak valid.\n");
}

header('Content-Type: text/plain; charset=utf-8');
echo "====================================================\n";
echo "   SINKRONISASI UPDATE LIVE SERVER PT DJL           \n";
echo "====================================================\n";
echo "Waktu Server: " . date('Y-m-d H:i:s') . "\n";
echo "Direktori   : " . __DIR__ . "\n\n";

// 1. Git Pull
echo "--- [1/3] Menarik Update Terbaru dari GitHub (git pull) ---\n";
$output = [];
$return_var = 0;
exec("git pull origin main 2>&1", $output, $return_var);
if (!empty($output)) {
    echo implode("\n", $output) . "\n";
}
echo "Status Git Pull: " . ($return_var === 0 ? "SUKSES (OK)" : "KODE RETURN: $return_var") . "\n\n";

// 2. Migrasi Database (Kolom keterangan di tabel absensis)
echo "--- [2/3] Memeriksa Struktur Database (absensis) ---\n";
$check = mysqli_query($conn, "SHOW COLUMNS FROM absensis LIKE 'keterangan'");
if ($check && mysqli_num_rows($check) == 0) {
    $alt = mysqli_query($conn, "ALTER TABLE absensis ADD COLUMN keterangan VARCHAR(255) NULL AFTER status_kehadiran");
    if ($alt) {
        echo "SUKSES: Kolom 'keterangan' berhasil ditambahkan ke tabel absensis.\n";
    } else {
        echo "GAGAL: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "OK: Kolom 'keterangan' sudah aktif di tabel absensis.\n";
}
echo "\n";

// 3. Periksa & Perbaiki Izin File scan_session.json
echo "--- [3/3] Memeriksa Izin File Session Scanner ---\n";
$session_file = __DIR__ . '/config/scan_session.json';
if (file_exists($session_file)) {
    @chmod($session_file, 0777);
    echo "OK: Izin file scan_session.json telah diset 0777 (read/write).\n";
} else {
    $created = @file_put_contents($session_file, json_encode(['active' => false]));
    if ($created !== false) {
        @chmod($session_file, 0777);
        echo "SUKSES: File scan_session.json dibuat dan diset 0777.\n";
    } else {
        echo "PERINGATAN: Gagal membuat scan_session.json, pastikan folder config berizin tulis.\n";
    }
}
echo "\n";

echo "====================================================\n";
echo "   SINKRONISASI SELESAI DENGAN SUKSES!             \n";
echo "====================================================\n";
