<?php
require __DIR__ . '/../config/config.php';

// Pastikan kolom keterangan sudah ada di tabel absensis
$check_col = mysqli_query($conn, "SHOW COLUMNS FROM absensis LIKE 'keterangan'");
if ($check_col && mysqli_num_rows($check_col) == 0) {
    mysqli_query($conn, "ALTER TABLE absensis ADD COLUMN keterangan VARCHAR(255) NULL AFTER status_kehadiran");
}

// ==========================================
// HANDLER: KOREKSI ABSENSI OLEH KERANI
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'koreksi_absensi') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Validasi session role kerani
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'kerani') {
        echo json_encode(['success' => false, 'message' => 'Akses ditolak: Hanya Kerani yang dapat mengoreksi absensi.']);
        exit;
    }

    $target_user_id = (int)($_POST['user_id'] ?? 0);
    $target_tanggal = trim($_POST['tanggal'] ?? '');
    $target_status  = strtolower(trim($_POST['status'] ?? ''));
    $target_ket     = trim($_POST['keterangan'] ?? '');
    $target_masuk   = trim($_POST['waktu_masuk'] ?? '');
    $target_pulang  = trim($_POST['waktu_pulang'] ?? '');

    if ($target_user_id <= 0 || empty($target_tanggal)) {
        echo json_encode(['success' => false, 'message' => 'ID Karyawan atau Tanggal tidak valid.']);
        exit;
    }

    // Ambil data user target
    $q_u = mysqli_query($conn, "SELECT id, name, nik FROM users WHERE id = $target_user_id");
    if (!$q_u || mysqli_num_rows($q_u) === 0) {
        echo json_encode(['success' => false, 'message' => 'Karyawan tidak ditemukan di database.']);
        exit;
    }
    $u_data = mysqli_fetch_assoc($q_u);

    $tgl_safe = mysqli_real_escape_string($conn, $target_tanggal);
    $ket_safe = mysqli_real_escape_string($conn, $target_ket);

    // Opsi 1: Kosongkan / Reset Absensi
    if ($target_status === 'hapus' || $target_status === 'kosong' || $target_status === '') {
        $del = mysqli_query($conn, "DELETE FROM absensis WHERE user_id = $target_user_id AND tanggal = '$tgl_safe'");
        if ($del) {
            echo json_encode([
                'success' => true,
                'message' => "Data absensi {$u_data['name']} pada tanggal {$target_tanggal} berhasil dikosongkan."
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal mengosongkan absensi: ' . mysqli_error($conn)]);
        }
        exit;
    }

    // Opsi 2: Update / Insert Status Baru
    $valid_status = ['hadir', 'tepat_waktu', 'terlambat', 'alpha', 'izin', 'sakit', 'cuti'];
    if (!in_array($target_status, $valid_status)) {
        echo json_encode(['success' => false, 'message' => 'Status kehadiran tidak valid.']);
        exit;
    }

    // Format jam masuk & pulang
    $masuk_sql = "NULL";
    $pulang_sql = "NULL";
    if (in_array($target_status, ['hadir', 'tepat_waktu', 'terlambat'])) {
        $jam_m = !empty($target_masuk) ? $target_masuk : '07:00:00';
        $masuk_sql = "'" . mysqli_real_escape_string($conn, $jam_m) . "'";
        $jam_p = !empty($target_pulang) ? $target_pulang : '15:00:00';
        $pulang_sql = "'" . mysqli_real_escape_string($conn, $jam_p) . "'";
    }

    // Cek apakah data absensi sudah ada
    $cek_abs = mysqli_query($conn, "SELECT id FROM absensis WHERE user_id = $target_user_id AND tanggal = '$tgl_safe'");
    if (mysqli_num_rows($cek_abs) > 0) {
        $sql = "UPDATE absensis SET 
                    status_kehadiran = '$target_status',
                    waktu_masuk = $masuk_sql,
                    waktu_pulang = $pulang_sql,
                    keterangan = '$ket_safe'
                WHERE user_id = $target_user_id AND tanggal = '$tgl_safe'";
        $res = mysqli_query($conn, $sql);
    } else {
        $sql = "INSERT INTO absensis (user_id, tanggal, waktu_masuk, waktu_pulang, status_kehadiran, keterangan)
                VALUES ($target_user_id, '$tgl_safe', $masuk_sql, $pulang_sql, '$target_status', '$ket_safe')";
        $res = mysqli_query($conn, $sql);
    }

    if ($res) {
        $status_label = strtoupper($target_status);
        echo json_encode([
            'success' => true,
            'message' => "Absensi {$u_data['name']} pada {$target_tanggal} berhasil diperbarui menjadi {$status_label}."
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
    exit;
}

include __DIR__ . '/templates/header.php';
?>
<style type="text/css" media="print">
    @page {
        size: landscape;
    }
</style>
<?php

$role_filter = isset($_GET['role']) ? $_GET['role'] : 'karyawan';
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');

// Sinkronisasi status Alpha otomatis untuk periode bulan yang dilihat
checkAndSetAlpha($conn, $bulan, $tahun);

$jumlah_hari = date('t', mktime(0, 0, 0, $bulan, 1, $tahun));
$nama_bulan = array(
    '01' => 'Januari',
    '02' => 'Februari',
    '03' => 'Maret',
    '04' => 'April',
    '05' => 'Mei',
    '06' => 'Juni',
    '07' => 'Juli',
    '08' => 'Agustus',
    '09' => 'September',
    '10' => 'Oktober',
    '11' => 'November',
    '12' => 'Desember'
);

$penandatangan = getReportSignatories($conn, $_SESSION['afdeling'] ?? '');
$afdeling_kerani = $_SESSION['afdeling'] ?? '';
$nama_kerani_tampil = $penandatangan['kerani'] ?: ($_SESSION['nama'] ?? '');
$nama_mandor_tampil = '';
if (!empty($afdeling_kerani)) {
    $num_afd = preg_replace('/[^0-9]/', '', $afdeling_kerani);
    $afd_safe = mysqli_real_escape_string($conn, $afdeling_kerani);
    if ($num_afd !== '') {
        $where_mandor = "(afdeling = '$afd_safe' OR afdeling = 'Afd $num_afd' OR afdeling = 'Afdeling $num_afd' OR afdeling = '$num_afd' OR afdeling LIKE '%$num_afd%')";
    } else {
        $where_mandor = "(afdeling = '$afd_safe' OR afdeling LIKE '%$afd_safe%')";
    }
    $q_mandor = mysqli_query($conn, "SELECT name FROM users WHERE (role='mandor' OR jabatan='mandor' OR jabatan LIKE '%mandor%') AND $where_mandor ORDER BY name ASC");
    $arr_mandor = [];
    if ($q_mandor) {
        while ($m = mysqli_fetch_assoc($q_mandor)) {
            $arr_mandor[] = $m['name'];
        }
    }
    if (!empty($arr_mandor)) {
        $nama_mandor_tampil = implode(', ', $arr_mandor);
    }
}
?>

<style>
    /* Dokumen dengan kop/logo hanya ditampilkan pada hasil cetak. */
    #official-print-doc { display: none; }

    @media print {
        #official-print-doc { display: block !important; }
        .header-actions, .koreksi-hint-banner, .modal-koreksi, .btn-koreksi { display: none !important; }
    }

    .header-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 12px;
    }

    .filter-group {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .filter-select {
        padding: 10px 16px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        color: var(--text-main);
        background: white;
        outline: none;
        cursor: pointer;
        transition: border-color 0.2s;
    }

    .filter-select:focus {
        border-color: var(--accent);
    }

    .action-buttons {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .btn-koreksi {
        background: #2563eb;
        color: white;
        border: none;
        padding: 10px 18px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
        box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25);
    }

    .btn-koreksi:hover {
        background: #1d4ed8;
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(37, 99, 235, 0.35);
    }

    .btn-print {
        background: white;
        color: var(--text-main);
        border: 1px solid #cbd5e1;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }

    .btn-print:hover {
        background: #f8fafc;
        border-color: #94a3b8;
    }

    /* Hint banner untuk kerani */
    .koreksi-hint-banner {
        display: flex;
        align-items: center;
        gap: 12px;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        padding: 12px 18px;
        border-radius: 10px;
        margin-bottom: 20px;
        color: #166534;
        font-size: 13.5px;
    }

    .koreksi-hint-banner .hint-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #22c55e;
        color: white;
        font-size: 14px;
        flex-shrink: 0;
    }

    .card {
        background: white;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
        overflow: hidden;
    }

    .card-header {
        padding: 20px 24px;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
        text-align: center;
    }

    .card-title {
        font-size: 18px;
        font-weight: 700;
        color: var(--text-main);
        margin: 0;
    }

    .lk-subtitle {
        font-size: 13px;
        color: #64748b;
        margin-top: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-wrap: wrap;
        gap: 6px;
        line-height: 1.6;
    }

    .lk-subtitle .sep {
        color: #cbd5e1;
        font-weight: 400;
    }

    .lk-subtitle strong {
        color: #1f2937;
    }

    .tipe-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .5px;
    }

    .badge-karyawan {
        background: #fee2e2;
        color: #dc2626;
    }

    .badge-mandor {
        background: #fef3c7;
        color: #d97706;
    }

    .badge-kerani {
        background: #eff6ff;
        color: #2563eb;
    }

    .badge-pengawas {
        background: #f5f3ff;
        color: #7c3aed;
    }

    .table-container {
        width: 100%;
        overflow-x: auto;
    }

    .table-data {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        white-space: nowrap;
    }

    .table-data th,
    .table-data td {
        padding: 10px 8px;
        text-align: center;
        border: 1px solid #e2e8f0;
    }

    .table-data th {
        background: white;
        color: var(--text-muted);
        font-weight: 700;
        text-transform: uppercase;
        font-size: 11px;
    }

    .table-data tbody tr:hover {
        background: #f8fafc;
    }

    .text-left {
        text-align: left !important;
    }

    .status-badge {
        display: inline-block;
        width: 20px;
        height: 20px;
        line-height: 20px;
        border-radius: 4px;
        font-weight: bold;
        font-size: 10px;
        user-select: none;
    }

    .status-h {
        background-color: #dcfce7;
        color: #166534;
    }

    .status-a {
        background-color: #fee2e2;
        color: #991b1b;
    }

    .status-i {
        background-color: #e0f2fe;
        color: #075985;
    }

    .status-s {
        background-color: #ede9fe;
        color: #5b21b6;
    }

    .status-c {
        background-color: #ffedd5;
        color: #9a3412;
    }

    .status-t {
        background-color: #fef9c3;
        color: #854d0e;
    }

    /* Interaktivitas Sel Absensi */
    .cell-absen {
        cursor: pointer;
        position: relative;
        transition: background-color 0.15s ease, outline 0.15s ease;
    }

    .cell-absen:hover {
        background-color: #dbeafe !important;
        outline: 2px solid #2563eb;
        outline-offset: -2px;
        z-index: 2;
    }

    .dot-koreksi {
        position: absolute;
        top: 2px;
        right: 2px;
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background-color: #2563eb;
        box-shadow: 0 0 0 1px #ffffff;
    }

    /* MODAL KOREKSI ABSENSI */
    .modal-backdrop {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(4px);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .modal-backdrop.active {
        display: flex;
    }

    .modal-box {
        background: white;
        border-radius: 16px;
        width: 100%;
        max-width: 520px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        overflow: hidden;
        animation: modalScaleIn 0.2s ease-out forwards;
    }

    @keyframes modalScaleIn {
        from {
            opacity: 0;
            transform: scale(0.95);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    .modal-header {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 16px 22px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .modal-title {
        font-size: 16px;
        font-weight: 700;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .modal-close-btn {
        background: transparent;
        border: none;
        color: #94a3b8;
        font-size: 20px;
        cursor: pointer;
        padding: 4px;
        line-height: 1;
        transition: color 0.15s;
    }

    .modal-close-btn:hover {
        color: #0f172a;
    }

    .modal-body {
        padding: 22px;
        max-height: 80vh;
        overflow-y: auto;
    }

    .info-card-karyawan {
        background: #f1f5f9;
        border-radius: 10px;
        padding: 12px 16px;
        margin-bottom: 18px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .form-group {
        margin-bottom: 16px;
    }

    .form-label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #334155;
        margin-bottom: 6px;
    }

    .form-control {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 14px;
        color: #0f172a;
        outline: none;
        background: white;
        transition: border-color 0.2s;
    }

    .form-control:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    /* Pilihan Status Kehadiran (Tile Grid) */
    .status-tile-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
        margin-top: 6px;
    }

    .status-tile {
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 8px;
        text-align: center;
        cursor: pointer;
        transition: all 0.15s ease;
        background: white;
    }

    .status-tile:hover {
        border-color: #94a3b8;
        background: #f8fafc;
    }

    .status-tile.active {
        border-color: #2563eb;
        background: #eff6ff;
        box-shadow: 0 2px 6px rgba(37, 99, 235, 0.15);
    }

    .status-tile .tile-code {
        font-size: 16px;
        font-weight: 800;
        display: block;
        margin-bottom: 2px;
    }

    .status-tile .tile-label {
        font-size: 11px;
        font-weight: 600;
        color: #64748b;
    }

    .tile-h .tile-code { color: #16a34a; }
    .tile-i .tile-code { color: #0284c7; }
    .tile-s .tile-code { color: #7c3aed; }
    .tile-c .tile-code { color: #ea580c; }
    .tile-a .tile-code { color: #dc2626; }
    .tile-k .tile-code { color: #64748b; }

    /* Jam Masuk/Pulang Row */
    .jam-inputs-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-top: 14px;
        background: #f8fafc;
        padding: 12px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
    }

    /* Quick Chips Alasan */
    .quick-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 8px;
    }

    .chip-btn {
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        color: #475569;
        border-radius: 16px;
        padding: 4px 10px;
        font-size: 11.5px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.15s;
    }

    .chip-btn:hover {
        background: #e2e8f0;
        color: #0f172a;
        border-color: #94a3b8;
    }

    .modal-footer {
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        padding: 14px 22px;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .btn-cancel {
        background: white;
        border: 1px solid #cbd5e1;
        color: #64748b;
        padding: 9px 18px;
        border-radius: 8px;
        font-size: 13.5px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s;
    }

    .btn-cancel:hover {
        background: #f1f5f9;
        color: #334155;
    }

    .btn-save-koreksi {
        background: #2563eb;
        border: none;
        color: white;
        padding: 9px 20px;
        border-radius: 8px;
        font-size: 13.5px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-save-koreksi:hover {
        background: #1d4ed8;
    }
</style>

<!-- Hint Banner Panduan Koreksi -->
<div class="koreksi-hint-banner">
    <div class="hint-icon">
        <i class="fa-solid fa-pen-to-square"></i>
    </div>
    <div style="flex: 1;">
        <strong>Pengelolaan & Koreksi Absensi Kerani:</strong> Klik langsung pada sel tanggal karyawan di tabel untuk mengoreksi kehadiran (misal mesin error, lupa scan QR, dll), atau gunakan tombol <strong>Koreksi Absensi</strong> di atas.
    </div>
</div>

<div class="header-actions">
    <form method="GET" id="filterForm" class="filter-group">
        <select name="role" class="filter-select" onchange="document.getElementById('filterForm').submit()">
            <option value="kerani" <?= $role_filter == 'kerani' ? 'selected' : '' ?>>Kerani</option>
            <option value="karyawan" <?= $role_filter == 'karyawan' ? 'selected' : '' ?>>Karyawan</option>
            <option value="mandor" <?= $role_filter == 'mandor' ? 'selected' : '' ?>>Mandor</option>
            <option value="pengawas" <?= $role_filter == 'pengawas' ? 'selected' : '' ?>>Pengawas</option>
        </select>

        <select name="bulan" class="filter-select" onchange="document.getElementById('filterForm').submit()">
            <?php foreach ($nama_bulan as $num => $name): ?>
                <option value="<?= $num ?>" <?= $bulan == $num ? 'selected' : '' ?>><?= $name ?></option>
            <?php endforeach; ?>
        </select>

        <select name="tahun" class="filter-select" onchange="document.getElementById('filterForm').submit()">
            <?php for ($y = date('Y') - 2; $y <= date('Y'); $y++): ?>
                <option value="<?= $y ?>" <?= $tahun == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </form>

    <div class="action-buttons">
        <button type="button" class="btn-koreksi" onclick="bukaModalKoreksiManual()">
            <i class="fa-solid fa-pen-to-square"></i>
            Koreksi Absensi
        </button>

        <button class="btn-print" onclick="window.print()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                <rect x="6" y="14" width="12" height="8"></rect>
            </svg>
            Cetak PDF
        </button>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Laporan Absensi <?= ucfirst($role_filter) ?><?= !empty($_SESSION['afdeling']) ? ' (' . htmlspecialchars($_SESSION['afdeling']) . ')' : '' ?></h3>
        <p class="lk-subtitle">
            <span>Periode: <strong>01 &ndash; <?= $jumlah_hari ?> <?= $nama_bulan[$bulan] ?> <?= $tahun ?></strong></span>
            <span class="sep">|</span>
            <span>Afdeling: <strong><?= htmlspecialchars($_SESSION['afdeling'] ?? '-') ?></strong></span>
            <span class="sep">|</span>
            <span>Jabatan: <strong><?= ucfirst($role_filter) ?></strong></span>
            <?php if (!empty($nama_kerani_tampil) && $nama_kerani_tampil !== '( ................................... )'): ?>
                <span class="sep">|</span>
                <span>Kerani: <strong><?= htmlspecialchars($nama_kerani_tampil) ?></strong></span>
            <?php endif; ?>
            <?php if (!empty($nama_mandor_tampil) && $role_filter === 'karyawan'): ?>
                <span class="sep">|</span>
                <span>Mandor: <strong><?= htmlspecialchars($nama_mandor_tampil) ?></strong></span>
            <?php endif; ?>
            <span class="sep">|</span>
            <span class="tipe-badge badge-<?= $role_filter ?>"><?= ucfirst($role_filter) ?></span>
        </p>
    </div>

    <div class="table-container">
        <table class="table-data">
            <thead>
                <tr>
                    <th rowspan="2" width="40">NO</th>
                    <th rowspan="2" class="text-left">NIK</th>
                    <th rowspan="2" class="text-left" width="200">NAMA LENGKAP</th>
                    <th colspan="<?= $jumlah_hari ?>">TGL</th>
                    <th rowspan="2" width="30" title="Hadir">H</th>
                    <th rowspan="2" width="30" title="Izin">I</th>
                    <th rowspan="2" width="30" title="Alpha">A</th>
                    <th rowspan="2" width="30" title="Sakit">S</th>
                    <th rowspan="2" width="30" title="Cuti">C</th>
                    <th rowspan="2">TOTAL<br>HADIR</th>
                </tr>
                <tr>
                    <?php for ($i = 1; $i <= $jumlah_hari; $i++): ?>
                        <th style="min-width: 25px; padding: 8px 4px;"><?= $i ?></th>
                    <?php endfor; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $role_safe = mysqli_real_escape_string($conn, $role_filter);
                $afdeling_kerani = isset($_SESSION['afdeling']) ? mysqli_real_escape_string($conn, $_SESSION['afdeling']) : '';

                if (!empty($afdeling_kerani)) {
                    $query = mysqli_query($conn, "SELECT id, nik, name, role, jabatan FROM users WHERE role='$role_safe' AND afdeling='$afdeling_kerani' ORDER BY name ASC");
                } else {
                    $query = mysqli_query($conn, "SELECT id, nik, name, role, jabatan FROM users WHERE role='$role_safe' ORDER BY name ASC");
                }

                $all_users_data = [];
                while ($user = mysqli_fetch_assoc($query)) {
                    $all_users_data[] = $user;
                }

                // ==========================================
                // PRELOAD DATA ABSENSI BULAN INI (1 QUERY)
                // ==========================================
                $start_month_date = sprintf("%04d-%02d-01", $tahun, $bulan);
                $end_month_date = sprintf("%04d-%02d-%02d", $tahun, $bulan, $jumlah_hari);

                $absen_map = [];
                $q_abs = mysqli_query($conn, "SELECT user_id, tanggal, waktu_masuk, waktu_pulang, status_kehadiran, keterangan 
                                              FROM absensis 
                                              WHERE tanggal BETWEEN '$start_month_date' AND '$end_month_date'");
                if ($q_abs) {
                    while ($row_abs = mysqli_fetch_assoc($q_abs)) {
                        $absen_map[$row_abs['user_id']][$row_abs['tanggal']] = $row_abs;
                    }
                }

                $no = 1;
                foreach ($all_users_data as $user):
                ?>
                    <tr>
                        <td style="color: var(--text-muted); font-weight: 600;"><?= $no++ ?></td>
                        <td class="text-left" style="font-weight: 700;"><?= htmlspecialchars($user['nik']) ?></td>
                        <td class="text-left"><?= htmlspecialchars($user['name']) ?></td>

                        <?php
                        $t_hadir = 0;
                        $t_izin = 0;
                        $t_sakit = 0;
                        $t_cuti = 0;
                        $t_alpha = 0;
                        $user_absen_data = []; // store for print
                        for ($i = 1; $i <= $jumlah_hari; $i++):
                            $tgl_str = sprintf("%04d-%02d-%02d", $tahun, $bulan, $i);
                            $rec = $absen_map[$user['id']][$tgl_str] ?? null;

                            if ($rec && !empty($rec['status_kehadiran'])) {
                                $status = strtolower($rec['status_kehadiran']);
                                $w_masuk = $rec['waktu_masuk'] ?? '';
                                $w_pulang = $rec['waktu_pulang'] ?? '';
                                $ket = $rec['keterangan'] ?? '';

                                $user_absen_data[$i] = $status;
                                $info = getStatusInfo($status);
                                switch($info['kode']) {
                                    case 'H': $t_hadir++; break;
                                    case 'I': $t_izin++; break;
                                    case 'S': $t_sakit++; break;
                                    case 'C': $t_cuti++; break;
                                    case 'A': $t_alpha++; break;
                                }

                                $has_note = !empty($ket);
                                $dot_html = $has_note ? '<span class="dot-koreksi" title="Catatan: ' . htmlspecialchars($ket) . '"></span>' : '';
                                $cell_title = "Klik untuk koreksi (" . htmlspecialchars($user['name']) . " - Tgl " . $i . ($has_note ? ' | Ket: ' . htmlspecialchars($ket) : '') . ")";

                                echo '<td class="cell-absen" title="' . $cell_title . '" onclick="bukaModalKoreksi(' . $user['id'] . ', \'' . htmlspecialchars(addslashes($user['name'])) . '\', \'' . htmlspecialchars(addslashes($user['nik'])) . '\', \'' . $tgl_str . '\', \'' . $status . '\', \'' . $w_masuk . '\', \'' . $w_pulang . '\', \'' . htmlspecialchars(addslashes($ket)) . '\')">';
                                echo getStatusBadge($status) . $dot_html;
                                echo '</td>';
                            } else {
                                $user_absen_data[$i] = null;
                                $cell_title = "Klik untuk isi absensi (" . htmlspecialchars($user['name']) . " - Tgl " . $i . ")";
                                echo '<td class="cell-absen" title="' . $cell_title . '" onclick="bukaModalKoreksi(' . $user['id'] . ', \'' . htmlspecialchars(addslashes($user['name'])) . '\', \'' . htmlspecialchars(addslashes($user['nik'])) . '\', \'' . $tgl_str . '\', \'\', \'\', \'\', \'\')">';
                                echo '<span style="color: #cbd5e1;">-</span>';
                                echo '</td>';
                            }
                        ?>
                        <?php endfor; ?>
                        
                        <?php
                        // Simpan total ke dalam array untuk digunakan di cetak dokumen resmi
                        $user['t_hadir'] = $t_hadir;
                        $user['t_izin'] = $t_izin;
                        $user['t_alpha'] = $t_alpha;
                        $user['t_sakit'] = $t_sakit;
                        $user['t_cuti'] = $t_cuti;
                        $user['absen_data'] = $user_absen_data;
                        // Replace array element with modified user array
                        $all_users_data[$no-2] = $user; 
                        ?>

                        <td style="font-weight: 700; color: #166534;"><?= $t_hadir ?></td>
                        <td style="font-weight: 700; color: #075985;"><?= $t_izin ?></td>
                        <td style="font-weight: 700; color: #991b1b;"><?= $t_alpha ?></td>
                        <td style="font-weight: 700; color: #5b21b6;"><?= $t_sakit ?></td>
                        <td style="font-weight: 700; color: #9a3412;"><?= $t_cuti ?></td>
                        <td style="font-weight: 800; color: #0f172a;"><?= $t_hadir ?>/<?= $jumlah_hari ?></td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($all_users_data)): ?>
                    <tr>
                        <td colspan="<?= $jumlah_hari + 9 ?>" style="padding: 30px; color: #94a3b8; font-style: italic;">
                            Belum ada data karyawan untuk divisi/afdeling ini.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL KOREKSI ABSENSI                      -->
<!-- ========================================== -->
<div class="modal-backdrop" id="modalKoreksiAbsensi">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">
                <i class="fa-solid fa-pen-to-square" style="color: #2563eb;"></i>
                <span>Koreksi Data Absensi</span>
            </div>
            <button type="button" class="modal-close-btn" onclick="tutupModalKoreksi()">&times;</button>
        </div>

        <form id="formKoreksiAbsensi" onsubmit="simpanKoreksi(event)">
            <input type="hidden" name="action" value="koreksi_absensi">
            
            <div class="modal-body">
                <!-- Info Karyawan Terpilih (Mode Klik Tabel) -->
                <div class="info-card-karyawan" id="karyawan_info_box">
                    <div>
                        <div id="modal_karyawan_nama" style="font-weight: 700; font-size: 14.5px; color: #0f172a;">-</div>
                        <div id="modal_karyawan_nik" style="font-size: 12px; color: #64748b; margin-top: 2px;">NIK: -</div>
                    </div>
                    <span class="tipe-badge badge-<?= $role_filter ?>"><?= ucfirst($role_filter) ?></span>
                </div>

                <!-- Dropdown Pilih Karyawan (Mode Tombol Manual) -->
                <div class="form-group" id="karyawan_select_group" style="display: none;">
                    <label class="form-label">Pilih Karyawan</label>
                    <select id="modal_user_select" class="form-control">
                        <option value="">-- Pilih Karyawan --</option>
                        <?php foreach ($all_users_data as $u_item): ?>
                            <option value="<?= $u_item['id'] ?>" data-name="<?= htmlspecialchars($u_item['name']) ?>" data-nik="<?= htmlspecialchars($u_item['nik']) ?>">
                                <?= htmlspecialchars($u_item['nik']) ?> - <?= htmlspecialchars($u_item['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <input type="hidden" name="user_id" id="modal_user_id">

                <!-- Tanggal Absensi -->
                <div class="form-group">
                    <label class="form-label">Tanggal Absensi</label>
                    <input type="date" name="tanggal" id="modal_tanggal" class="form-control" required>
                </div>

                <!-- Status Kehadiran Tiles -->
                <div class="form-group">
                    <label class="form-label">Status Kehadiran</label>
                    <input type="hidden" name="status" id="modal_status_val" value="hadir">
                    <div class="status-tile-grid">
                        <div class="status-tile tile-h active" onclick="pilihStatusTile('hadir')">
                            <span class="tile-code">H</span>
                            <span class="tile-label">Hadir</span>
                        </div>
                        <div class="status-tile tile-i" onclick="pilihStatusTile('izin')">
                            <span class="tile-code">I</span>
                            <span class="tile-label">Izin</span>
                        </div>
                        <div class="status-tile tile-s" onclick="pilihStatusTile('sakit')">
                            <span class="tile-code">S</span>
                            <span class="tile-label">Sakit</span>
                        </div>
                        <div class="status-tile tile-c" onclick="pilihStatusTile('cuti')">
                            <span class="tile-code">C</span>
                            <span class="tile-label">Cuti</span>
                        </div>
                        <div class="status-tile tile-a" onclick="pilihStatusTile('alpha')">
                            <span class="tile-code">A</span>
                            <span class="tile-label">Alpha</span>
                        </div>
                        <div class="status-tile tile-k" onclick="pilihStatusTile('hapus')">
                            <span class="tile-code">-</span>
                            <span class="tile-label">Kosongkan</span>
                        </div>
                    </div>
                </div>

                <!-- Jam Masuk & Pulang (Hanya jika Hadir) -->
                <div class="jam-inputs-row" id="jam_row">
                    <div>
                        <label class="form-label" style="font-size: 12px;">Jam Masuk</label>
                        <input type="time" name="waktu_masuk" id="modal_waktu_masuk" class="form-control" value="07:00">
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 12px;">Jam Pulang</label>
                        <input type="time" name="waktu_pulang" id="modal_waktu_pulang" class="form-control" value="15:00">
                    </div>
                </div>

                <!-- Keterangan / Alasan Koreksi -->
                <div class="form-group" style="margin-top: 14px;">
                    <label class="form-label">Keterangan / Alasan Koreksi</label>
                    <textarea name="keterangan" id="modal_keterangan" class="form-control" rows="2" placeholder="Contoh: Lupa scan QR kartu, alat scanner sedang error, izin lisan ke mandor..."></textarea>
                    
                    <div class="quick-chips">
                        <button type="button" class="chip-btn" onclick="tambahKeteranganChip('Lupa Scan QR')">+ Lupa Scan QR</button>
                        <button type="button" class="chip-btn" onclick="tambahKeteranganChip('Kendala Mesin Scanner')">+ Kendala Scanner</button>
                        <button type="button" class="chip-btn" onclick="tambahKeteranganChip('Izin Lisan ke Mandor')">+ Izin Lisan Mandor</button>
                        <button type="button" class="chip-btn" onclick="tambahKeteranganChip('Koreksi Kesalahan Input')">+ Koreksi Input</button>
                        <button type="button" class="chip-btn" onclick="tambahKeteranganChip('Dinas Lapangan')">+ Dinas Lapangan</button>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="tutupModalKoreksi()">Batal</button>
                <button type="submit" class="btn-save-koreksi" id="btnSubmitKoreksi">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Koreksi
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========== DOKUMEN CETAK RESMI ========== -->
<div id="official-print-doc">
    <!-- Kop Surat -->
    <div class="doc-header">
        <img src="<?= BASE_URL ?>assets/img/logo.png" alt="" class="doc-header-logo" onerror="this.style.display='none'">
        <div class="doc-header-text">
            <h1>PT Damai Jaya Lestari</h1>
            <h2>Laporan Rekapitulasi Absensi Karyawan</h2>
            <p>Jl. Perkebunan No. 1 &nbsp;|&nbsp; Telp: (021) 000-0000 &nbsp;|&nbsp; Email: admin@djl.co.id</p>
        </div>
    </div>

    <!-- Judul & Metadata -->
    <div class="doc-title">
        <h3>Laporan Absensi &mdash; <?= $nama_bulan[$bulan] ?> <?= $tahun ?></h3>
    </div>
    <div class="doc-meta">
        <div><strong>Jabatan&nbsp;&nbsp;:</strong> <?= !empty($role_filter) ? ucfirst(htmlspecialchars($role_filter)) : 'Semua Jabatan' ?></div>
        <div><strong>Jumlah Hari:</strong> <?= $jumlah_hari ?> hari</div>
        <div><strong>Dicetak&nbsp;&nbsp;:</strong> <?= date('d F Y, H:i') ?></div>
    </div>

    <!-- Keterangan Kode -->
    <p style="font-size:9pt; margin-bottom:10px;">
        <strong>Keterangan:</strong>
        H = Hadir &nbsp;|&nbsp; A = Alpha/Tidak Hadir &nbsp;|&nbsp; I = Izin &nbsp;|&nbsp; S = Sakit &nbsp;|&nbsp; C = Cuti &nbsp;|&nbsp; (kosong) = Belum tercatat
    </p>

    <!-- Tabel Absensi -->
    <table class="doc-table" style="font-size:8.5pt;">
        <thead>
            <tr>
                <th rowspan="2" style="width:25px;">No</th>
                <th rowspan="2" style="width:80px; text-align:left;">NIK</th>
                <th rowspan="2" style="min-width:120px; text-align:left;">Nama Karyawan</th>
                <th colspan="<?= $jumlah_hari ?>">Tanggal</th>
                <th rowspan="2" style="width:20px;">H</th>
                <th rowspan="2" style="width:20px;">I</th>
                <th rowspan="2" style="width:20px;">A</th>
                <th rowspan="2" style="width:20px;">S</th>
                <th rowspan="2" style="width:20px;">C</th>
                <th rowspan="2" style="width:40px;">Total Hadir</th>
            </tr>
            <tr>
                <?php for ($i = 1; $i <= $jumlah_hari; $i++): ?>
                    <th style="width:18px; font-size:8pt;"><?= $i ?></th>
                <?php endfor; ?>
            </tr>
        </thead>
        <tbody>
            <?php
            $pno = 1;
            foreach ($all_users_data as $user):
            ?>
                <tr>
                    <td class="text-center"><?= $pno++ ?></td>
                    <td><?= htmlspecialchars($user['nik']) ?></td>
                    <td><?= htmlspecialchars($user['name']) ?></td>
                    <?php
                    for ($i = 1; $i <= $jumlah_hari; $i++):
                        $st = $user['absen_data'][$i] ?? null;
                        $k = '';
                        $dc = '';
                        if (in_array($st, ['hadir', 'tepat_waktu'])) {
                            $k = 'H';
                            $dc = 'doc-status-H';
                        } elseif (in_array($st, ['alpha', 'alpa', 'alfa', 'terlambat'])) {
                            $k = 'A';
                            $dc = 'doc-status-A';
                        } elseif ($st == 'izin') {
                            $k = 'I';
                            $dc = 'doc-status-I';
                        } elseif ($st == 'sakit') {
                            $k = 'S';
                            $dc = 'doc-status-S';
                        } elseif ($st == 'cuti') {
                            $k = 'C';
                            $dc = 'doc-status-C';
                        }
                    ?>
                        <td class="text-center <?= $dc ?>" style="font-size:8pt; font-weight:bold;"><?= $k ?></td>
                    <?php endfor; ?>
                    <td class="text-center" style="font-weight:bold;"><?= $user['t_hadir'] ?></td>
                    <td class="text-center" style="font-weight:bold;"><?= $user['t_izin'] ?></td>
                    <td class="text-center" style="font-weight:bold;"><?= $user['t_alpha'] ?></td>
                    <td class="text-center" style="font-weight:bold;"><?= $user['t_sakit'] ?></td>
                    <td class="text-center" style="font-weight:bold;"><?= $user['t_cuti'] ?></td>
                    <td class="text-center" style="font-weight:bold;"><?= $user['t_hadir'] ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($all_users_data)): ?>
                <tr>
                    <td colspan="<?= $jumlah_hari + 9 ?>" style="padding: 20px; text-align: center; color: #94a3b8;">Tidak ada data</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Tanda Tangan -->
    <div class="doc-signature">
        <div class="doc-signature-col">
            <p>Diketahui oleh,</p>
            <span class="sig-name"><?= htmlspecialchars($penandatangan['pengawas']) ?></span>
            <div style="font-weight:bold;">Pengawas Afdeling <?= htmlspecialchars($penandatangan['afdeling']) ?></div>
        </div>
        <div class="doc-signature-col">
            <p>Disusun oleh,</p>
            <span class="sig-name"><?= htmlspecialchars($penandatangan['kerani']) ?></span>
            <div style="font-weight:bold;">Kerani Afdeling <?= htmlspecialchars($penandatangan['afdeling']) ?></div>
        </div>
    </div>

    <div class="doc-footer">
        Dokumen ini dicetak secara otomatis oleh Sistem Informasi PT Damai Jaya Lestari pada <?= date('d F Y \p\u\k\u\l H:i') ?> WIB.
    </div>
</div>

<!-- ========================================== -->
<!-- JAVASCRIPT LOGIC KOREKSI ABSENSI           -->
<!-- ========================================== -->
<script>
const modalBackdrop = document.getElementById('modalKoreksiAbsensi');
const modalUserId = document.getElementById('modal_user_id');
const modalTanggal = document.getElementById('modal_tanggal');
const modalStatusVal = document.getElementById('modal_status_val');
const modalWaktuMasuk = document.getElementById('modal_waktu_masuk');
const modalWaktuPulang = document.getElementById('modal_waktu_pulang');
const modalKeterangan = document.getElementById('modal_keterangan');
const jamRow = document.getElementById('jam_row');

const karyawanInfoBox = document.getElementById('karyawan_info_box');
const karyawanNamaEl = document.getElementById('modal_karyawan_nama');
const karyawanNikEl = document.getElementById('modal_karyawan_nik');
const karyawanSelectGroup = document.getElementById('karyawan_select_group');
const modalUserSelect = document.getElementById('modal_user_select');

// Buka modal dari klik sel pada tabel
function bukaModalKoreksi(userId, nama, nik, tanggal, currentStatus, waktuMasuk, waktuPulang, keterangan) {
    karyawanInfoBox.style.display = 'flex';
    karyawanSelectGroup.style.display = 'none';

    modalUserId.value = userId;
    karyawanNamaEl.textContent = nama;
    karyawanNikEl.textContent = 'NIK: ' + nik;

    modalTanggal.value = tanggal;
    modalTanggal.readOnly = true;

    // Normalisasi status
    let st = (currentStatus || 'hadir').toLowerCase();
    if (st === 'tepat_waktu' || st === 'terlambat') st = 'hadir';
    pilihStatusTile(st);

    modalWaktuMasuk.value = waktuMasuk ? waktuMasuk.substring(0, 5) : '07:00';
    modalWaktuPulang.value = waktuPulang ? waktuPulang.substring(0, 5) : '15:00';
    modalKeterangan.value = keterangan || '';

    modalBackdrop.classList.add('active');
}

// Buka modal manual dari tombol Toolbar
function bukaModalKoreksiManual() {
    karyawanInfoBox.style.display = 'none';
    karyawanSelectGroup.style.display = 'block';

    modalUserId.value = '';
    modalUserSelect.value = '';

    // Set tanggal default ke hari ini atau tgl 1 bulan filter
    const nowBulan = '<?= sprintf("%04d-%02d-01", $tahun, $bulan) ?>';
    modalTanggal.value = nowBulan;
    modalTanggal.readOnly = false;

    pilihStatusTile('hadir');
    modalWaktuMasuk.value = '07:00';
    modalWaktuPulang.value = '15:00';
    modalKeterangan.value = '';

    modalBackdrop.classList.add('active');
}

// Handler perubahan select user di mode manual
modalUserSelect.addEventListener('change', function() {
    modalUserId.value = this.value;
});

// Tutup Modal
function tutupModalKoreksi() {
    modalBackdrop.classList.remove('active');
}

// Klik di luar modal box untuk menutup
modalBackdrop.addEventListener('click', function(e) {
    if (e.target === modalBackdrop) {
        tutupModalKoreksi();
    }
});

// Pilih Status Tile
function pilihStatusTile(status) {
    modalStatusVal.value = status;

    const tiles = document.querySelectorAll('.status-tile');
    tiles.forEach(tile => tile.classList.remove('active'));

    const activeTile = document.querySelector('.status-tile.tile-' + (status === 'hapus' ? 'k' : status.charAt(0)));
    if (activeTile) {
        activeTile.classList.add('active');
    }

    // Tampilkan field jam hanya jika status Hadir
    if (status === 'hadir' || status === 'tepat_waktu' || status === 'terlambat') {
        jamRow.style.display = 'grid';
    } else {
        jamRow.style.display = 'none';
    }
}

// Tambah Alasan dari Quick Chips
function tambahKeteranganChip(text) {
    const curVal = modalKeterangan.value.trim();
    if (curVal === '') {
        modalKeterangan.value = text;
    } else if (!curVal.includes(text)) {
        modalKeterangan.value = curVal + ', ' + text;
    }
    modalKeterangan.focus();
}

// Simpan Form Koreksi (AJAX)
async function simpanKoreksi(event) {
    event.preventDefault();

    if (!modalUserId.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Perhatian',
            text: 'Silakan pilih karyawan terlebih dahulu!',
            confirmButtonColor: '#2563eb'
        });
        return;
    }

    if (!modalTanggal.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Perhatian',
            text: 'Silakan pilih tanggal absensi!',
            confirmButtonColor: '#2563eb'
        });
        return;
    }

    const btn = document.getElementById('btnSubmitKoreksi');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...';

    const formData = new FormData(document.getElementById('formKoreksiAbsensi'));

    try {
        const response = await fetch('laporan_absensi.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            tutupModalKoreksi();
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: data.message,
                timer: 1400,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: data.message || 'Terjadi kesalahan sistem.',
                confirmButtonColor: '#2563eb'
            });
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    } catch (err) {
        console.error(err);
        Swal.fire({
            icon: 'error',
            title: 'Kesalahan Jaringan',
            text: 'Gagal menghubungi server.',
            confirmButtonColor: '#2563eb'
        });
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}
</script>

<?php include 'templates/footer.php'; ?>
