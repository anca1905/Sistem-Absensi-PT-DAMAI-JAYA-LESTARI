<?php
require_once __DIR__ . '/config/config.php';

$php_time = date('Y-m-d H:i:s');
$php_timezone = date_default_timezone_get();

$db_time = 'Database Offline';
$global_tz = '-';
$session_tz = '-';
$jam_masuk = '08:00:00';
$toleransi = 15;

if (isset($conn) && $conn) {
    $q_mysql = mysqli_query($conn, "SELECT NOW() as db_time, @@global.time_zone as global_tz, @@session.time_zone as session_tz");
    if ($q_mysql && $db_data = mysqli_fetch_assoc($q_mysql)) {
        $db_time = $db_data['db_time'];
        $global_tz = $db_data['global_tz'];
        $session_tz = $db_data['session_tz'];
    }

    $q_setting = mysqli_query($conn, "SELECT jam_masuk, jam_pulang, toleransi_menit FROM settings LIMIT 1");
    if ($q_setting && $setting = mysqli_fetch_assoc($q_setting)) {
        $jam_masuk = $setting['jam_masuk'] ?? '08:00:00';
        $toleransi = $setting['toleransi_menit'] ?? 0;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Waktu Server & Database</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f8fafc;
            padding: 30px;
            color: #1e293b;
        }
        .card {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }
        h2 {
            margin-top: 0;
            color: #0f172a;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 12px;
        }
        .row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .label {
            font-weight: 600;
            color: #64748b;
        }
        .val {
            font-weight: 700;
            color: #0f172a;
        }
        .highlight {
            color: #2563eb;
        }
        .note {
            margin-top: 20px;
            padding: 12px;
            background: #f0fdf4;
            border-left: 4px solid #16a34a;
            font-size: 13px;
            color: #166534;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="card">
        <h2>⏱️ Diagnosa Waktu Server & Aplikasi</h2>
        <div class="row">
            <span class="label">Waktu PHP Sekarang:</span>
            <span class="val highlight"><?= $php_time ?></span>
        </div>
        <div class="row">
            <span class="label">Timezone PHP:</span>
            <span class="val"><?= $php_timezone ?></span>
        </div>
        <div class="row">
            <span class="label">Waktu Database (MySQL):</span>
            <span class="val highlight"><?= $db_time ?></span>
        </div>
        <div class="row">
            <span class="label">Timezone Database:</span>
            <span class="val"><?= $session_tz ?> (Global: <?= $global_tz ?>)</span>
        </div>
        <div class="row">
            <span class="label">Jam Masuk (Ketentuan):</span>
            <span class="val" style="color: #dc2626;"><?= $jam_masuk ?></span>
        </div>
        <div class="row">
            <span class="label">Toleransi Keterlambatan:</span>
            <span class="val"><?= $toleransi ?> Menit</span>
        </div>

        <div class="note">
            💡 <strong>Panduan:</strong> Pastikan <strong>Waktu PHP Sekarang</strong> sama persis dengan jam di HP / jam dinding lokal Anda (WITA / UTC+8). Jika berbeda, periksa pengaturan jam dan timezone di aaPanel.
        </div>
    </div>
</body>
</html>
