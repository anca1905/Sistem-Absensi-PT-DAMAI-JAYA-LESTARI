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

// ------------------------------------------------------------------
// Cek Waktu Sesi Scan Pengawas (Max 2 Jam)
// ------------------------------------------------------------------
$session_file = '../config/scan_session.json';
if (file_exists($session_file)) {
    $session_data = json_decode(file_get_contents($session_file), true);
    $today = date('Y-m-d');

    if (isset($session_data['date']) && $session_data['date'] === $today) {
        $elapsed = time() - $session_data['start_timestamp'];
        if ($elapsed >= (2 * 60 * 60)) {
            // Sesi habis
            echo json_encode(['status' => 'error', 'message' => 'Waktu scan absensi sudah habis!']);
            exit;
        }
    }
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

if (!$dataAbsen) {
    // -- ABSEN MASUK --
    $status = 'hadir';
    $insert = mysqli_query($conn, "INSERT INTO absensis (user_id, tanggal, waktu_masuk, status_kehadiran) VALUES ('$userId', '$today', '$now', '$status')");

    if ($insert) {
        echo json_encode([
            'status' => 'success',
            'type' => 'MASUK',
            'nama' => $user['name'],
            'waktu' => $now,
            'ket' => 'Hadir'
        ]);
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
