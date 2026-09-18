<?php
/**
 * kerani/komentar_handler.php
 * AJAX handler: tandai dibaca, hapus komentar, ambil list komentar
 */
require '../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'kerani') {
    echo json_encode(['success' => false, 'msg' => 'Akses ditolak.']);
    exit;
}

header('Content-Type: application/json');
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// === Tandai semua komentar logbook tertentu sudah dibaca ===
if ($action === 'baca') {
    $logbook_id = (int)($_POST['logbook_id'] ?? 0);
    if ($logbook_id) {
        mysqli_query($conn, "UPDATE komentar_objek_kerja SET dibaca=1 WHERE logbook_id=$logbook_id");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'ID tidak valid.']);
    }
    exit;
}

// === Hapus komentar tertentu ===
if ($action === 'hapus') {
    $komentar_id = (int)($_POST['komentar_id'] ?? 0);
    if ($komentar_id) {
        mysqli_query($conn, "DELETE FROM komentar_objek_kerja WHERE id=$komentar_id");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'ID tidak valid.']);
    }
    exit;
}

// === Ambil jumlah komentar belum dibaca (untuk badge notif) ===
if ($action === 'count_unread') {
    $tanggal = mysqli_real_escape_string($conn, $_GET['tanggal'] ?? date('Y-m-d'));
    $afdeling = isset($_SESSION['afdeling']) ? mysqli_real_escape_string($conn, $_SESSION['afdeling']) : '';
    
    $q = mysqli_query($conn, "
        SELECT COUNT(*) AS total
        FROM komentar_objek_kerja k
        JOIN logbook_kinerja lk ON k.logbook_id = lk.id
        JOIN users u ON lk.user_id = u.id
        WHERE k.dibaca = 0
        AND lk.tanggal = '$tanggal'
        " . (!empty($afdeling) ? "AND u.afdeling = '$afdeling'" : "") . "
    ");
    $row = mysqli_fetch_assoc($q);
    echo json_encode(['success' => true, 'count' => (int)$row['total']]);
    exit;
}

// === Ambil daftar komentar per logbook ===
if ($action === 'list') {
    $logbook_id = (int)($_GET['logbook_id'] ?? 0);
    if (!$logbook_id) {
        echo json_encode(['success' => false, 'msg' => 'ID tidak valid.']);
        exit;
    }
    $q = mysqli_query($conn, "
        SELECT k.id, k.komentar, k.dibaca, k.created_at, u.name AS nama_karyawan
        FROM komentar_objek_kerja k
        JOIN users u ON k.user_id = u.id
        WHERE k.logbook_id = $logbook_id
        ORDER BY k.created_at DESC
    ");
    $list = [];
    while ($r = mysqli_fetch_assoc($q)) $list[] = $r;
    
    // Tandai sebagai dibaca
    mysqli_query($conn, "UPDATE komentar_objek_kerja SET dibaca=1 WHERE logbook_id=$logbook_id");
    
    echo json_encode(['success' => true, 'data' => $list]);
    exit;
}

echo json_encode(['success' => false, 'msg' => 'Aksi tidak dikenali.']);
