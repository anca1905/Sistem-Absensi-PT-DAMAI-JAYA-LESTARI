<?php
require '../config/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['status' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? ($_POST['action'] ?? 'status');

if ($action === 'status') {
    $token = getFonnteToken();
    if (empty($token)) {
        echo json_encode([
            'status' => false,
            'configured' => false,
            'reason' => 'Token Fonnte belum diatur. Silakan masukkan Token Fonnte Anda di bawah.',
            'token' => ''
        ]);
        exit;
    }

    $raw = checkFonnteStatus($token);
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        echo json_encode([
            'status' => false,
            'configured' => true,
            'reason' => 'Gagal menghubungi server Fonnte (Respons tidak valid).',
            'raw' => $raw,
            'token' => $token
        ]);
        exit;
    }

    $data['configured'] = true;
    $data['token'] = $token;
    echo json_encode($data);
    exit;
}

if ($action === 'save_token') {
    $token = trim($_POST['token'] ?? '');
    if (empty($token)) {
        echo json_encode(['status' => false, 'message' => 'Token tidak boleh kosong!']);
        exit;
    }

    $saved = saveFonnteToken($token);
    if (!$saved) {
        echo json_encode(['status' => false, 'message' => 'Gagal menyimpan token ke database!']);
        exit;
    }

    // Verifikasi token langsung ke Fonnte API
    $test_res = checkFonnteStatus($token);
    $test_data = json_decode($test_res, true);

    echo json_encode([
        'status' => true,
        'message' => 'Token Fonnte berhasil disimpan!',
        'fonnte_response' => $test_data
    ]);
    exit;
}

if ($action === 'qr') {
    $raw = getFonnteQR();
    echo $raw ?: json_encode(['status' => false, 'message' => 'Gagal mengambil QR dari Fonnte']);
    exit;
}

if ($action === 'disconnect') {
    $raw = disconnectFonnte();
    echo $raw ?: json_encode(['status' => false, 'message' => 'Gagal memutus koneksi di Fonnte']);
    exit;
}

if ($action === 'test_send') {
    $target = trim($_POST['target'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($target) || empty($message)) {
        echo json_encode(['status' => false, 'message' => 'Nomor tujuan dan pesan wajib diisi!']);
        exit;
    }

    $raw = sendWA($target, $message);
    $data = json_decode($raw, true);

    if (is_array($data) && isset($data['status']) && $data['status'] === true) {
        echo json_encode(['status' => true, 'message' => 'Pesan uji coba berhasil dikirim via Fonnte!', 'response' => $data]);
    } else {
        $reason = is_array($data) ? ($data['reason'] ?? ($data['message'] ?? 'Gagal mengirim pesan')) : 'Gagal menghubungi gateway Fonnte';
        echo json_encode(['status' => false, 'message' => 'Gagal mengirim: ' . $reason, 'response' => $data]);
    }
    exit;
}

echo json_encode(['status' => false, 'message' => 'Action tidak dikenali']);
exit;
