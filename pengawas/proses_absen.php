<?php
ob_start();
require '../config/config.php';
ob_clean();
header('Content-Type: application/json; charset=utf-8');

// Ambil data JSON dari fetch Javascript
$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true);
$nik = isset($input['nik']) ? mysqli_real_escape_string($conn, trim($input['nik'])) : '';

if (empty($nik)) {
    echo json_encode(['status' => 'error', 'message' => 'QR Code tidak terbaca!']);
    exit;
}

$today = date('Y-m-d');
$now = date('H:i:s');

// ------------------------------------------------------------------
// Cek Waktu Sesi Scan Pengawas (Max 2 Jam) via Database
// ------------------------------------------------------------------
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS scan_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_date DATE NOT NULL UNIQUE,
    start_timestamp INT NOT NULL,
    start_time_formatted VARCHAR(10) NOT NULL
)");

$q_sess = mysqli_query($conn, "SELECT * FROM scan_sessions WHERE session_date = '$today' LIMIT 1");
if ($q_sess && mysqli_num_rows($q_sess) > 0) {
    $sess_row = mysqli_fetch_assoc($q_sess);
    $elapsed = time() - (int)$sess_row['start_timestamp'];
    if ($elapsed >= (2 * 60 * 60)) {
        echo json_encode(['status' => 'error', 'message' => 'Waktu sesi scan absensi hari ini sudah habis!']);
        exit;
    }
}

// 1. Cari Karyawan / Personel berdasarkan NIK
$queryUser = mysqli_query($conn, "SELECT * FROM users WHERE nik = '$nik' AND role IN ('karyawan', 'mandor', 'kerani', 'pengawas') LIMIT 1");
$user = mysqli_fetch_assoc($queryUser);

if (!$user) {
    echo json_encode(['status' => 'error', 'message' => 'NIK tidak terdaftar dalam sistem!']);
    exit;
}

$userId = $user['id'];

// 2. Cek Absensi Hari Ini
$cekAbsen = mysqli_query($conn, "SELECT * FROM absensis WHERE user_id = '$userId' AND tanggal = '$today' LIMIT 1");
$dataAbsen = mysqli_fetch_assoc($cekAbsen);

// Jika belum ada record absen, ATAU record ada tapi belum punya waktu_masuk (misal sempat tercatat alpha)
if (!$dataAbsen || empty($dataAbsen['waktu_masuk'])) {
    // -- ABSEN MASUK --
    $status = 'hadir';
    if ($dataAbsen) {
        $ok = mysqli_query($conn, "UPDATE absensis SET waktu_masuk = '$now', status_kehadiran = '$status' WHERE id = " . (int)$dataAbsen['id']);
    } else {
        $ok = mysqli_query($conn, "INSERT INTO absensis (user_id, tanggal, waktu_masuk, status_kehadiran) VALUES ('$userId', '$today', '$now', '$status')");
    }

    if ($ok) {
        echo json_encode([
            'status' => 'success',
            'type' => 'MASUK',
            'nama' => $user['name'],
            'waktu' => $now,
            'ket' => 'Hadir'
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan absensi masuk.']);
    }
} else {
    // Record sudah ada waktu_masuk
    // Cek apakah baru saja scan masuk (< 5 menit) untuk mencegah scan ganda tidak sengaja
    $time_masuk_ts = strtotime($today . ' ' . $dataAbsen['waktu_masuk']);
    $time_now_ts = strtotime($today . ' ' . $now);
    $diff_seconds = $time_now_ts - $time_masuk_ts;

    if ($diff_seconds < 300 && empty($dataAbsen['waktu_pulang'])) {
        echo json_encode([
            'status' => 'info',
            'type' => 'MASUK',
            'nama' => $user['name'],
            'waktu' => $dataAbsen['waktu_masuk'],
            'message' => 'Sudah absen masuk jam ' . $dataAbsen['waktu_masuk']
        ]);
        exit;
    }

    // -- ABSEN PULANG --
    if (empty($dataAbsen['waktu_pulang'])) {
        $update = mysqli_query($conn, "UPDATE absensis SET waktu_pulang = '$now' WHERE id = " . (int)$dataAbsen['id']);
        if ($update) {
            echo json_encode([
                'status' => 'success',
                'type' => 'PULANG',
                'nama' => $user['name'],
                'waktu' => $now,
                'ket' => 'Hati-hati di jalan'
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal mencatat jam pulang.']);
        }
    } else {
        echo json_encode([
            'status' => 'info',
            'type' => 'SELESAI',
            'nama' => $user['name'],
            'waktu' => $dataAbsen['waktu_pulang'],
            'message' => 'Sudah absen pulang hari ini (' . $dataAbsen['waktu_pulang'] . ').'
        ]);
    }
}
exit;
