<?php
require '../config/config.php'; // Sesuaikan path config
header('Content-Type: application/json');

// Ambil data JSON dari fetch Javascript
$input = json_decode(file_get_contents('php://input'), true);
$nik = isset($input['nik']) ? mysqli_real_escape_string($conn, $input['nik']) : '';

if (empty($nik)) {
    echo json_encode(['status' => 'error', 'message' => 'QR Code tidak terbaca!']);
    exit;
}

// 1. Cari Karyawan
$queryUser = mysqli_query($conn, "SELECT * FROM users WHERE nik = '$nik' AND role = 'karyawan'");
$user = mysqli_fetch_assoc($queryUser);

if (!$user) {
    echo json_encode(['status' => 'error', 'message' => 'NIK tidak dikenali!']);
    exit;
}

// 2. Cek Absensi Hari Ini
$userId = $user['id'];
$today = date('Y-m-d');
$now = date('H:i:s');

// Ambil jam masuk dari settings
$setting = mysqli_fetch_assoc(mysqli_query($conn, "SELECT jam_masuk FROM settings LIMIT 1"));
$jamMasuk = $setting['jam_masuk'];

// Cek apakah sudah absen masuk
$cekAbsen = mysqli_query($conn, "SELECT * FROM absensis WHERE user_id = '$userId' AND tanggal = '$today'");
$dataAbsen = mysqli_fetch_assoc($cekAbsen);

// Jalankan pengecekan auto-alpha hari ini
checkAndSetAlpha($conn);

if (!$dataAbsen) {
    // -- ABSEN MASUK --
    if ($now >= '06:00:00' && $now <= '07:00:00') {
        $status = ($now > $jamMasuk) ? 'terlambat' : 'tepat_waktu';
        $insert = mysqli_query($conn, "INSERT INTO absensis (user_id, tanggal, waktu_masuk, status_kehadiran) VALUES ('$userId', '$today', '$now', '$status')");

        if ($insert) {
            echo json_encode([
                'status' => 'success',
                'type' => 'MASUK',
                'nama' => $user['name'],
                'waktu' => $now,
                'ket' => ($status == 'terlambat') ? 'Terlambat' : 'Tepat Waktu'
            ]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Batas waktu absen masuk adalah jam 06:00 - 07:00!']);
    }
} else {
    // -- ABSEN PULANG --
    if ($dataAbsen['waktu_pulang'] == null) {
        $update = mysqli_query($conn, "UPDATE absensis SET waktu_pulang = '$now' WHERE id = '" . $dataAbsen['id'] . "'");
        if ($update) {
            echo json_encode([
                'status' => 'success',
                'type' => 'PULANG',
                'nama' => $user['name'],
                'waktu' => $now,
                'ket' => 'Hati-hati di jalan'
            ]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Sudah absen pulang hari ini.']);
    }
}
