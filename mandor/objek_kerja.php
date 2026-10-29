<?php
require '../config/config.php';

// Cek autentikasi Mandor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mandor') {
    if (isset($_REQUEST['ajax_komentar'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'msg' => 'Akses ditolak.']);
        exit;
    }
    header("Location: ../index.php");
    exit;
}

$mandor_id = $_SESSION['user_id'];
$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : (isset($_POST['tanggal']) ? $_POST['tanggal'] : date('Y-m-d'));
$afdeling_mandor = isset($_SESSION['afdeling']) ? mysqli_real_escape_string($conn, $_SESSION['afdeling']) : '';
$tgl_safe = mysqli_real_escape_string($conn, $tanggal);

// --- AJAX KOMENTAR HANDLER ---
if (isset($_REQUEST['ajax_komentar'])) {
    header('Content-Type: application/json');
    $action = $_REQUEST['action'] ?? '';

    // Ambil daftar komentar untuk tanggal ini
    if ($action === 'get_komentar') {
        $q_komentar = mysqli_query($conn, "
            SELECT k.id, k.komentar, k.dibaca, k.created_at,
                   u.name as nama_karyawan, u.nik, u.role as role_pengirim,
                   lk.objek_kerja, lk.blok, lk.id as logbook_id
            FROM komentar_objek_kerja k
            JOIN logbook_kinerja lk ON k.logbook_id = lk.id
            JOIN users u ON k.user_id = u.id
            WHERE lk.tanggal = '$tgl_safe' 
              AND (lk.mandor_id = $mandor_id" . (!empty($afdeling_mandor) ? " OR u.afdeling = '$afdeling_mandor'" : "") . ")
            ORDER BY k.created_at DESC
        ");
        $data = [];
        if ($q_komentar) {
            while ($row = mysqli_fetch_assoc($q_komentar)) {
                $data[] = $row;
            }
        }
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    // Hitung unread & total
    if ($action === 'count_unread') {
        $q_count = mysqli_query($conn, "
            SELECT COUNT(*) as total, SUM(CASE WHEN k.dibaca = 0 THEN 1 ELSE 0 END) as unread
            FROM komentar_objek_kerja k
            JOIN logbook_kinerja lk ON k.logbook_id = lk.id
            JOIN users u ON k.user_id = u.id
            WHERE lk.tanggal = '$tgl_safe' 
              AND (lk.mandor_id = $mandor_id" . (!empty($afdeling_mandor) ? " OR u.afdeling = '$afdeling_mandor'" : "") . ")
        ");
        $c = mysqli_fetch_assoc($q_count);
        echo json_encode([
            'success' => true,
            'total' => (int)($c['total'] ?? 0),
            'unread' => (int)($c['unread'] ?? 0)
        ]);
        exit;
    }

    // Tandai dibaca
    if ($action === 'baca_komentar') {
        $komentar_id = (int)($_POST['komentar_id'] ?? 0);
        if ($komentar_id > 0) {
            mysqli_query($conn, "UPDATE komentar_objek_kerja SET dibaca = 1 WHERE id = $komentar_id");
        } else {
            mysqli_query($conn, "
                UPDATE komentar_objek_kerja k
                JOIN logbook_kinerja lk ON k.logbook_id = lk.id
                JOIN users u ON k.user_id = u.id
                SET k.dibaca = 1
                WHERE lk.tanggal = '$tgl_safe' 
                  AND (lk.mandor_id = $mandor_id" . (!empty($afdeling_mandor) ? " OR u.afdeling = '$afdeling_mandor'" : "") . ")
            ");
        }
        echo json_encode(['success' => true]);
        exit;
    }

    // Kirim komentar/catatan dari Mandor ke Kerani
    if ($action === 'kirim_komentar_mandor') {
        $logbook_id = (int)($_POST['logbook_id'] ?? 0);
        $komentar = trim(mysqli_real_escape_string($conn, $_POST['komentar'] ?? ''));
        if ($logbook_id > 0 && !empty($komentar)) {
            mysqli_query($conn, "INSERT INTO komentar_objek_kerja (logbook_id, user_id, komentar, dibaca) VALUES ($logbook_id, $mandor_id, '$komentar', 0)");
            
            // Kirim notifikasi WA ke Kerani afdeling
            $q_kerani = mysqli_query($conn, "SELECT no_hp, name FROM users WHERE role='kerani'" .
                (!empty($afdeling_mandor) ? " AND afdeling='$afdeling_mandor'" : "") . " LIMIT 1");
            if ($q_kerani && ($kerani = mysqli_fetch_assoc($q_kerani))) {
                if (!empty($kerani['no_hp'])) {
                    $nama_mandor = $_SESSION['nama'] ?? 'Mandor';
                    $pesan = "Catatan dari Mandor ({$nama_mandor})
";
                    $pesan .= "Tanggal: " . date('d/m/Y', strtotime($tanggal)) . "

";
                    $pesan .= "*Pesan:*
_{$komentar}_

";
                    $pesan .= "_Sistem PT DJL_";
                    sendWA($kerani['no_hp'], $pesan);
                }
            }
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'msg' => 'Tugas dan catatan harus diisi.']);
        }
        exit;
    }

    echo json_encode(['success' => false, 'msg' => 'Aksi tidak valid.']);
    exit;
}

include 'templates/header.php';

// Hitung komentar awal untuk badge
$q_km_init = mysqli_query($conn, "
    SELECT COUNT(*) as total, SUM(CASE WHEN k.dibaca = 0 THEN 1 ELSE 0 END) as unread
    FROM komentar_objek_kerja k
    JOIN logbook_kinerja lk ON k.logbook_id = lk.id
    JOIN users u ON k.user_id = u.id
    WHERE lk.tanggal = '$tgl_safe' 
      AND (lk.mandor_id = $mandor_id" . (!empty($afdeling_mandor) ? " OR u.afdeling = '$afdeling_mandor'" : "") . ")
");
$km_init = mysqli_fetch_assoc($q_km_init);
$km_total = (int)($km_init['total'] ?? 0);
$km_unread = (int)($km_init['unread'] ?? 0);

// 1. Handle Simpan Data & Verifikasi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_semua'])) {

    foreach ($_POST as $key => $val) {
        if (strpos($key, 'status_') === 0) {
            $id = (int) str_replace('status_', '', $key);
            $status = mysqli_real_escape_string($conn, $val);

            $update_sql = "UPDATE logbook_kinerja SET status='$status'";

            // Format 1: Langsir / Membabat
            if (isset($_POST["hasil_ton_$id"])) {
                $hasil_ton = (float)$_POST["hasil_ton_$id"];
                $update_sql .= ", hasil_ton=$hasil_ton";
            }
            if (isset($_POST["hasil_kg_$id"])) {
                $hasil_kg = (float)$_POST["hasil_kg_$id"];
                $update_sql .= ", hasil_kg=$hasil_kg";
            }

            // Format 3: Potong Buah / Panen
            if (isset($_POST["tbs_$id"])) {
                $update_sql .= ", tbs=" . (int)$_POST["tbs_$id"];
            }
            if (isset($_POST["kosong_$id"])) {
                $update_sql .= ", tandan_kosong=" . (int)$_POST["kosong_$id"];
            }
            if (isset($_POST["brondol_$id"])) {
                $update_sql .= ", tandan_brondol=" . (int)$_POST["brondol_$id"];
            }
            if (isset($_POST["total_$id"])) {
                $update_sql .= ", total_tandan=" . (int)$_POST["total_$id"];
            }

            // Format 4: Muat TBS
            if (isset($_POST["hasil_langsir_$id"])) {
                $update_sql .= ", hasil_langsir_kg=" . (float)$_POST["hasil_langsir_$id"];
            }

            $update_sql .= " WHERE id=$id AND mandor_id=$mandor_id";
            mysqli_query($conn, $update_sql);
        }
    }
    swalRedirect('Data objek kerja berhasil disimpan dan diverifikasi!', "objek_kerja.php?tanggal=$tgl_safe", 'success');
    exit;
}

// 2. Ambil Data dari logbook_kinerja
if (!empty($afdeling_mandor)) {
    $query_logbook = mysqli_query($conn, "
        SELECT lk.*, u.nik, u.name as karyawan_name
        FROM logbook_kinerja lk
        JOIN users u ON lk.user_id = u.id
        WHERE lk.tanggal = '$tgl_safe' AND u.afdeling = '$afdeling_mandor' AND lk.mandor_id = $mandor_id
        ORDER BY u.name ASC
    ");
} else {
    $query_logbook = mysqli_query($conn, "
        SELECT lk.*, u.nik, u.name as karyawan_name
        FROM logbook_kinerja lk
        JOIN users u ON lk.user_id = u.id
        WHERE lk.tanggal = '$tgl_safe' AND lk.mandor_id = $mandor_id
        ORDER BY u.name ASC
    ");
}

$all_tasks = [];
if ($query_logbook) {
    while ($row = mysqli_fetch_assoc($query_logbook)) {
        $all_tasks[] = $row;
    }
}

// 2b. Jika logbook kosong, ambil rencana dari pengawas supaya mandor tetap bisa lihat tugasnya
$rencana_pengawas = [];
if (count($all_tasks) == 0) {
    $q_rencana = mysqli_query($conn, "
        SELECT r.*, p.name as nama_pengawas
        FROM rencana_kerja_pengawas r
        LEFT JOIN users p ON r.pengawas_id = p.id
        WHERE r.tanggal = '$tgl_safe' AND r.mandor_id = $mandor_id
        ORDER BY r.id ASC
    ");
    if ($q_rencana) {
        while ($rw = mysqli_fetch_assoc($q_rencana)) {
            $rencana_pengawas[] = $rw;
        }
    }
}

// 3. Grouping Berdasarkan Objek Kerja
$format1 = []; // Langsir, Membabat gawangan
$format2 = []; // Perawatan lain
$format3 = []; // Potong buah
$format4 = []; // Muat TBS
$format5 = []; // Jaga

foreach ($all_tasks as $t) {
    $ok = strtolower($t['objek_kerja']);
    if ($t['kategori_task'] == 'langsir' || strpos($ok, 'membabat') !== false || strpos($ok, 'langsir') !== false) {
        $format1[] = $t;
    } elseif ($t['kategori_task'] == 'potong_buah' || strpos($ok, 'potong') !== false || strpos($ok, 'panen') !== false) {
        $format3[] = $t;
    } elseif ($t['kategori_task'] == 'muat_tbs' || strpos($ok, 'muat') !== false) {
        $format4[] = $t;
    } elseif ($t['kategori_task'] == 'jaga' || strpos($ok, 'jaga') !== false) {
        $format5[] = $t;
    } else {
        $format2[] = $t; // fallback
    }
}
?>

<style>
    .btn-komentar-mandor {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-weight: 700;
        cursor: pointer;
        transition: all .2s;
    }
    .btn-komentar-mandor:hover {
        background: #f1f5f9 !important;
        border-color: #cbd5e1;
    }
    .badge-km {
        position: absolute;
        top: -6px;
        right: -6px;
        border-radius: 50%;
        min-width: 20px;
        height: 20px;
        padding: 0 4px;
        font-size: 11px;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid white;
        box-shadow: 0 2px 5px rgba(0,0,0,0.15);
    }
    .badge-unread {
        background: #ef4444;
        color: white;
    }
    .badge-read {
        background: #94a3b8;
        color: white;
    }
    .spin-icon {
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    .card-container {
        background: #ffffff;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(54, 72, 217, 0.05);
        border: 1px solid #f1f5f9;
        margin-bottom: 24px;
    }

    .form-input {
        width: 100%;
        padding: 12px 14px;
        border-radius: 10px;
        border: 1.5px solid #e2e8f0;
        background-color: #f8fafc;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-dark);
        font-family: inherit;
        outline: none;
        transition: all 0.2s;
        box-sizing: border-box;
    }

    .form-input:focus {
        border-color: var(--primary-start);
        box-shadow: 0 0 0 4px rgba(66, 88, 255, 0.1);
        background: white;
    }

    .table-responsive {
        width: 100%;
        overflow-x: auto;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        -webkit-overflow-scrolling: touch;
        background: white;
        margin-bottom: 20px;
    }

    .table-logbook {
        border-collapse: collapse;
        white-space: nowrap;
        font-size: 13px;
        width: 100%;
    }

    .table-logbook th,
    .table-logbook td {
        padding: 14px 10px;
        border: 1px solid #e2e8f0;
        vertical-align: middle;
        text-align: center;
    }

    .table-logbook th {
        background-color: var(--primary-light);
        color: var(--primary-end);
        font-weight: 800;
        font-size: 11px;
        text-transform: uppercase;
    }

    .input-mini {
        width: 70px;
        padding: 10px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        text-align: center;
        font-size: 13px;
        font-weight: 700;
        background: #fff;
        color: var(--primary-end);
    }

    .input-mini:focus {
        border-color: var(--primary-start);
        outline: none;
    }

    .select-status {
        padding: 10px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 700;
        background: #fff;
        color: #1e293b;
        cursor: pointer;
    }

    .table-title {
        font-size: 16px;
        font-weight: 800;
        color: var(--text-dark);
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .btn-submit {
        width: 100%;
        background: linear-gradient(135deg, var(--primary-start) 0%, var(--primary-end) 100%);
        color: white;
        border: none;
        padding: 16px;
        border-radius: 14px;
        font-size: 15px;
        font-weight: 800;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        transition: all 0.2s;
        box-shadow: 0 4px 15px rgba(66, 88, 255, 0.25);
    }

    .btn-submit:active {
        transform: scale(0.98);
        box-shadow: none;
    }

    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: var(--text-muted);
        text-decoration: none;
        font-weight: 700;
        font-size: 13px;
        margin-bottom: 16px;
        background: white;
        padding: 8px 14px;
        border-radius: 20px;
        border: 1.5px solid #e2e8f0;
    }

    .readonly-text {
        font-weight: 600;
        color: #64748b;
    }
</style>

<div class="animate-up">
    <a href="index.php" class="btn-back">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        Kembali
    </a>

    <h2 class="page-title" style="margin: 0 0 20px 0; font-size: 20px;">Objek Kerja & Verifikasi</h2>

    <form id="filterForm" method="GET" style="display:flex; gap:10px; align-items:flex-end; margin-bottom: 24px;">
        <div style="flex:1;">
            <label style="font-size: 12px; font-weight: 700; color: #64748b; margin-bottom:6px; display:block;">Pilih tgl, bulan, thn</label>
            <input type="date" name="tanggal" class="form-input" value="<?= $tanggal ?>" onchange="document.getElementById('filterForm').submit()">
        </div>
        <div>
            <button type="button" class="btn-back btn-komentar-mandor" style="margin-bottom:0; height:44px; border-radius:10px; background:#f8fafc;" onclick="bukaModalKomentar()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                </svg>
                Komentar
                <span class="badge-km <?= $km_unread > 0 ? 'badge-unread' : 'badge-read' ?>" id="badgeKomentarMandor" style="<?= ($km_unread > 0 || $km_total > 0) ? '' : 'display:none;' ?>">
                    <?= $km_unread > 0 ? $km_unread : ($km_total > 0 ? $km_total : '') ?>
                </span>
            </button>
        </div>
    </form>

    <p style="font-size: 11px; color: var(--text-muted); font-style: italic; margin-bottom: 16px;">
        * Diisi mandor setelah karyawan pulang bekerja lalu melapor hasil kerjanya.<br>
        * Blok, dan Luas Ha adalah data bawaan (readonly).
    </p>

    <form method="POST">

        <?php if (count($all_tasks) > 0): ?>

            <!-- FORMAT 1: Langsir manual -->
            <?php if (count($format1) > 0): ?>
                <div class="card-container">
                    <h3 class="table-title"><span style="color:var(--primary-start)">■</span> Langsir Manual</h3>
                    <div class="table-responsive">
                        <table class="table-logbook">
                            <thead>
                                <tr>
                                    <th rowspan="2">NO</th>
                                    <th rowspan="2">NIK</th>
                                    <th rowspan="2">Nama Karyawan</th>
                                    <th colspan="2">Hasil (Diisi Mandor)</th>
                                    <!-- <th colspan="2">Prestasi (Readonly)</th> -->
                                    <th rowspan="2">Blok</th>
                                    <th rowspan="2">Luas Ha</th>
                                    <th rowspan="2">Verifikasi</th>
                                </tr>
                                <tr>
                                    <th>Tandan / Ton</th>
                                    <th>Kg</th>
                                    <!-- <th>Tandan / Ton</th>
                                    <th>Kg</th> -->
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1;
                                foreach ($format1 as $t): $id = $t['id']; ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td class="readonly-text"><?= htmlspecialchars($t['nik']) ?></td>
                                        <td style="text-align:left; font-weight:700; color:var(--text-dark);"><?= htmlspecialchars($t['karyawan_name']) ?></td>
                                        <td><input type="number" step="0.01" name="hasil_ton_<?= $id ?>" class="input-mini" value="<?= $t['hasil_ton'] ?>"></td>
                                        <td><input type="number" step="0.01" name="hasil_kg_<?= $id ?>" class="input-mini" value="<?= $t['hasil_kg'] ?>"></td>
                                        <!-- <td class="readonly-text"><?= htmlspecialchars($t['prestasi_ton']) ?></td>
                                        <td class="readonly-text"><?= htmlspecialchars($t['prestasi_kg']) ?></td> -->
                                        <td class="readonly-text"><?= htmlspecialchars($t['blok'] ?? '-') ?></td>
                                        <td class="readonly-text"><?= htmlspecialchars($t['luas_ha'] ?? '-') ?></td>
                                        <td>
                                            <select name="status_<?= $id ?>" class="select-status">
                                                <option value="ditinjau" <?= $t['status'] == 'ditinjau' ? 'selected' : '' ?>>Ditinjau</option>
                                                <option value="diterima" <?= $t['status'] == 'diterima' ? 'selected' : '' ?>>Diterima</option>
                                                <option value="ditolak" <?= $t['status'] == 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- FORMAT 3: Potong Buah / Panen -->
            <?php if (count($format3) > 0): ?>
                <div class="card-container">
                    <h3 class="table-title"><span style="color:var(--primary-start)">■</span> Potong Buah / Panen</h3>
                    <div class="table-responsive">
                        <table class="table-logbook">
                            <thead>
                                <tr>
                                    <th rowspan="2">NO</th>
                                    <th rowspan="2">NIK</th>
                                    <th rowspan="2">Nama Karyawan</th>
                                    <th colspan="4">Hasil Janjangan (Diisi Mandor)</th>
                                    <th rowspan="2">Blok</th>
                                    <th rowspan="2">Luas Ha</th>
                                    <th rowspan="2">Verifikasi</th>
                                </tr>
                                <tr>
                                    <th>TBS</th>
                                    <th>TS</th>
                                    <th>TBB</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1;
                                foreach ($format3 as $t): $id = $t['id']; ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td class="readonly-text"><?= htmlspecialchars($t['nik']) ?></td>
                                        <td style="text-align:left; font-weight:700; color:var(--text-dark);"><?= htmlspecialchars($t['karyawan_name']) ?></td>
                                        <td><input type="number" name="tbs_<?= $id ?>" class="input-mini" value="<?= $t['tbs'] ?>"></td>
                                        <td><input type="number" name="kosong_<?= $id ?>" class="input-mini" value="<?= $t['tandan_kosong'] ?>"></td>
                                        <td><input type="number" name="brondol_<?= $id ?>" class="input-mini" value="<?= $t['tandan_brondol'] ?>"></td>
                                        <td><input type="number" name="total_<?= $id ?>" class="input-mini" value="<?= $t['total_tandan'] ?>"></td>
                                        <td class="readonly-text"><?= htmlspecialchars($t['blok'] ?? '-') ?></td>
                                        <td class="readonly-text"><?= htmlspecialchars($t['luas_ha'] ?? '-') ?></td>
                                        <td>
                                            <select name="status_<?= $id ?>" class="select-status">
                                                <option value="ditinjau" <?= $t['status'] == 'ditinjau' ? 'selected' : '' ?>>Ditinjau</option>
                                                <option value="diterima" <?= $t['status'] == 'diterima' ? 'selected' : '' ?>>Diterima</option>
                                                <option value="ditolak" <?= $t['status'] == 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- FORMAT 2: Perawatan Umum -->
            <?php if (count($format2) > 0): ?>
                <div class="card-container">
                    <h3 class="table-title"><span style="color:var(--primary-start)">■</span> Perawatan Umum</h3>
                    <div class="table-responsive">
                        <table class="table-logbook">
                            <thead>
                                <tr>
                                    <th>NO</th>
                                    <th>NIK</th>
                                    <th>Nama Karyawan</th>
                                    <th>Objek Kerja</th>
                                    <th>Blok</th>
                                    <th>Luas Ha</th>
                                    <th>Verifikasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1;
                                foreach ($format2 as $t): $id = $t['id']; ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td class="readonly-text"><?= htmlspecialchars($t['nik']) ?></td>
                                        <td style="text-align:left; font-weight:700; color:var(--text-dark);"><?= htmlspecialchars($t['karyawan_name']) ?></td>
                                        <td class="readonly-text"><?= htmlspecialchars($t['objek_kerja'] ?? '-') ?></td>
                                        <td class="readonly-text"><?= htmlspecialchars($t['blok'] ?? '-') ?></td>
                                        <td class="readonly-text"><?= htmlspecialchars($t['luas_ha'] ?? '-') ?></td>
                                        <td>
                                            <select name="status_<?= $id ?>" class="select-status">
                                                <option value="ditinjau" <?= $t['status'] == 'ditinjau' ? 'selected' : '' ?>>Ditinjau</option>
                                                <option value="diterima" <?= $t['status'] == 'diterima' ? 'selected' : '' ?>>Diterima</option>
                                                <option value="ditolak" <?= $t['status'] == 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- FORMAT 4 & 5... (Disembunyikan jika kosong untuk kerapian) -->

            <button type="submit" name="simpan_semua" class="btn-submit">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                Simpan & Verifikasi Data
            </button>

        <?php else: ?>
            <?php if (count($rencana_pengawas) > 0): ?>
                <!-- Rencana dari Pengawas (belum ada karyawan yang diassign kerani) -->
                <div class="card-container">
                    <h3 class="table-title">
                        <span style="color:var(--primary-start)">■</span> Rencana Tugas dari Pengawas
                    </h3>
                    <p style="font-size:12px; color:#f59e0b; background:#fffbeb; border:1px solid #fcd34d; border-radius:8px; padding:10px 14px; margin-bottom:14px; font-weight:600;">
                        ⏳ Kerani belum mengassign karyawan untuk tanggal ini. Data di bawah adalah rencana tugas dari pengawas.
                    </p>
                    <div class="table-responsive">
                        <table class="table-logbook">
                            <thead>
                                <tr>
                                    <th>NO</th>
                                    <th>Objek Kerja</th>
                                    <th>Blok</th>
                                    <th>Luas Ha</th>
                                    <th>Tenaga L</th>
                                    <th>Tenaga W</th>
                                    <th>Dari Pengawas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach ($rencana_pengawas as $rp): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td style="text-align:left; font-weight:700; color:var(--text-dark);"><?= htmlspecialchars($rp['objek_kerja']) ?></td>
                                        <td class="readonly-text"><?= htmlspecialchars($rp['blok'] ?? '-') ?></td>
                                        <td class="readonly-text"><?= htmlspecialchars($rp['luas_ha'] ?? '-') ?></td>
                                        <td><span style="background:#eff6ff;color:#1d4ed8;padding:3px 10px;border-radius:6px;font-weight:700;"><?= (int)$rp['tenaga_l'] ?></span></td>
                                        <td><span style="background:#fdf2f8;color:#be185d;padding:3px 10px;border-radius:6px;font-weight:700;"><?= (int)$rp['tenaga_w'] ?></span></td>
                                        <td class="readonly-text"><?= htmlspecialchars($rp['nama_pengawas'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="card-container" style="text-align:center; padding:40px;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5" style="margin-bottom:12px;">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    </svg>
                    <p style="color:#94a3b8; font-size:14px; font-weight:600;">Belum ada objek kerja untuk tanggal ini.</p>
                    <p style="color:#cbd5e1; font-size:12px;">Pengawas belum membuat rencana kerja untuk tanggal <?= date('d F Y', strtotime($tanggal)) ?></p>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </form>
</div>

<!-- Modal Komentar Mandor -->
<div id="modalKomentarMandor" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(15,23,42,.6); align-items:center; justify-content:center; backdrop-filter:blur(4px);">
    <div style="background:white; border-radius:16px; width:92%; max-width:580px; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25); overflow:hidden; max-height:88vh; display:flex; flex-direction:column;">
        
        <!-- Header Modal -->
        <div style="padding:16px 20px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; background:#f8fafc;">
            <div>
                <h4 style="margin:0; font-size:16px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5">
                        <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                    </svg>
                    Komentar Objek Kerja
                </h4>
                <p style="margin:4px 0 0 0; font-size:12px; color:#64748b;">Tanggal: <strong><?= date('d F Y', strtotime($tanggal)) ?></strong></p>
            </div>
            <button type="button" onclick="tutupModalKomentar()" style="background:none; border:none; font-size:24px; cursor:pointer; color:#64748b; line-height:1; padding:0 4px;">&times;</button>
        </div>

        <!-- Body Modal: Daftar Komentar -->
        <div id="komentar-modal-body" style="padding:16px 20px; overflow-y:auto; flex:1; max-height:55vh;">
            <div style="text-align:center; padding:30px; color:#94a3b8;">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="spin-icon" style="margin:0 auto 10px; display:block;">
                    <circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle>
                    <path d="M12 2a10 10 0 0 1 10 10" stroke-opacity="0.8"></path>
                </svg>
                Memuat komentar...
            </div>
        </div>

        <!-- Form Tambah Catatan Mandor -->
        <?php if (!empty($all_tasks)): ?>
        <div style="border-top:1px solid #e2e8f0; padding:12px 20px; background:#fafafa;">
            <div style="font-size:12px; font-weight:800; color:#064e3b; margin-bottom:8px;">
                ✍️ Tambah Catatan / Laporan ke Kerani:
            </div>
            <div style="display:flex; flex-direction:column; gap:8px;">
                <select id="pilihLogbookKomentar" class="form-input" style="font-size:12px; padding:8px 10px; background:white;">
                    <option value="">-- Pilih Karyawan / Tugas Terkait --</option>
                    <?php foreach ($all_tasks as $t): ?>
                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['karyawan_name']) ?> &mdash; <?= htmlspecialchars($t['objek_kerja']) ?> (Blok: <?= htmlspecialchars($t['blok'] ?? '-') ?>)</option>
                    <?php endforeach; ?>
                </select>
                <div style="display:flex; gap:8px;">
                    <input type="text" id="inputTeksKomentar" class="form-input" placeholder="Tulis catatan kendala/laporan..." style="font-size:12px; flex:1; background:white;">
                    <button type="button" class="btn-filter" style="padding:0 16px; font-size:12px; white-space:nowrap;" onclick="kirimKomentarMandor()">Kirim</button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Footer Modal -->
        <div style="padding:12px 20px; border-top:1px solid #e2e8f0; background:#f8fafc; display:flex; justify-content:space-between; align-items:center;">
            <button type="button" id="btnTandaiSemuaDibaca" class="btn-back" style="margin:0; font-size:12px; height:36px; padding:0 12px; display:none;" onclick="tandaiSemuaDibaca()">
                ✓ Tandai Semua Dibaca
            </button>
            <div style="margin-left:auto;">
                <button type="button" class="btn-filter" style="padding:8px 18px; border-radius:8px; font-size:13px;" onclick="tutupModalKomentar()">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
function bukaModalKomentar() {
    const modal = document.getElementById('modalKomentarMandor');
    modal.style.display = 'flex';
    muatKomentar();
}

function tutupModalKomentar() {
    const modal = document.getElementById('modalKomentarMandor');
    modal.style.display = 'none';
}

// Tutup modal jika klik di area backdrop luar
window.addEventListener('click', function(e) {
    const modal = document.getElementById('modalKomentarMandor');
    if (e.target === modal) {
        modal.style.display = 'none';
    }
});

function muatKomentar() {
    const body = document.getElementById('komentar-modal-body');
    const btnSemua = document.getElementById('btnTandaiSemuaDibaca');
    
    fetch('objek_kerja.php?ajax_komentar=1&action=get_komentar&tanggal=<?= urlencode($tanggal) ?>')
        .then(res => res.json())
        .then(res => {
            if (!res.success || !res.data || res.data.length === 0) {
                body.innerHTML = `
                    <div style="text-align:center; padding:36px 20px; color:#94a3b8;">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5" style="margin:0 auto 12px; display:block;">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            <line x1="9" y1="10" x2="15" y2="10"></line>
                        </svg>
                        <p style="font-size:14px; font-weight:700; color:#475569; margin-bottom:4px;">Belum Ada Komentar</p>
                        <p style="font-size:12px; color:#94a3b8; margin:0;">Tidak ada komentar atau komplain dari karyawan pada objek kerja tanggal ini.</p>
                    </div>
                `;
                btnSemua.style.display = 'none';
                return;
            }

            let hasUnread = false;
            let html = '<div style="display:flex; flex-direction:column; gap:12px;">';
            
            res.data.forEach(item => {
                const isUnread = parseInt(item.dibaca) === 0;
                if (isUnread) hasUnread = true;
                
                const isMandor = item.role_pengirim === 'mandor';
                const badgeStatus = isUnread 
                    ? '<span style="background:#fee2e2; color:#dc2626; font-size:10px; font-weight:800; padding:2px 8px; border-radius:12px; border:1px solid #fecaca;">Baru</span>'
                    : '<span style="background:#f1f5f9; color:#64748b; font-size:10px; font-weight:700; padding:2px 8px; border-radius:12px;">Dibaca</span>';
                
                html += `
                    <div style="background:${isUnread ? '#fffbeb' : '#ffffff'}; border:1px solid ${isUnread ? '#fde68a' : '#e2e8f0'}; border-left:4px solid ${isUnread ? '#f59e0b' : (isMandor ? '#3b82f6' : '#10b981')}; border-radius:10px; padding:12px 14px; transition:all 0.2s;">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:6px;">
                            <div>
                                <span style="font-weight:800; color:#0f172a; font-size:13px;">${escapeHtml(item.nama_karyawan)}</span>
                                ${isMandor ? '<span style="background:#dbeafe; color:#1e40af; font-size:10px; font-weight:700; padding:1px 6px; border-radius:4px; margin-left:6px;">Mandor</span>' : `<span style="font-size:11px; color:#64748b; margin-left:6px;">(NIK: ${escapeHtml(item.nik || '-')})</span>`}
                            </div>
                            ${badgeStatus}
                        </div>
                        <div style="font-size:11px; color:#065f46; font-weight:700; margin-bottom:8px; display:flex; gap:8px; flex-wrap:wrap;">
                            <span style="background:#d1fae5; padding:2px 6px; border-radius:4px;">📋 ${escapeHtml(item.objek_kerja || 'Tugas')}</span>
                            ${item.blok ? `<span style="background:#e0f2fe; color:#0369a1; padding:2px 6px; border-radius:4px;">📍 Blok: ${escapeHtml(item.blok)}</span>` : ''}
                        </div>
                        <div style="font-size:13px; color:#1e293b; line-height:1.5; background:rgba(255,255,255,0.7); padding:8px 10px; border-radius:6px; border:1px solid #f1f5f9; margin-bottom:8px; white-space:pre-wrap;">${escapeHtml(item.komentar)}</div>
                        <div style="display:flex; justify-content:space-between; align-items:center; font-size:11px; color:#94a3b8;">
                            <span>🕒 ${formatTanggalWaktu(item.created_at)}</span>
                            ${isUnread ? `
                                <button type="button" onclick="tandaiDibaca(${item.id})" style="background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; font-size:11px; font-weight:700; padding:3px 8px; border-radius:6px; cursor:pointer;">
                                    Tandai Dibaca
                                </button>
                            ` : ''}
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            body.innerHTML = html;
            btnSemua.style.display = hasUnread ? 'inline-flex' : 'none';
        })
        .catch(err => {
            body.innerHTML = '<div style="text-align:center; padding:30px; color:#ef4444;">Gagal memuat komentar. Silakan coba lagi.</div>';
        });
}

function tandaiDibaca(komentarId) {
    const formData = new FormData();
    formData.append('ajax_komentar', '1');
    formData.append('action', 'baca_komentar');
    formData.append('komentar_id', komentarId);
    formData.append('tanggal', '<?= $tanggal ?>');
    
    fetch('objek_kerja.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                muatKomentar();
                updateBadge();
            }
        });
}

function tandaiSemuaDibaca() {
    const formData = new FormData();
    formData.append('ajax_komentar', '1');
    formData.append('action', 'baca_komentar');
    formData.append('tanggal', '<?= $tanggal ?>');
    
    fetch('objek_kerja.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                muatKomentar();
                updateBadge();
            }
        });
}

function kirimKomentarMandor() {
    const sel = document.getElementById('pilihLogbookKomentar');
    const input = document.getElementById('inputTeksKomentar');
    
    if (!sel || !sel.value) {
        alert('Silakan pilih tugas/karyawan terkait terlebih dahulu.');
        return;
    }
    if (!input || !input.value.trim()) {
        alert('Tuliskan catatan terlebih dahulu.');
        return;
    }

    const formData = new FormData();
    formData.append('ajax_komentar', '1');
    formData.append('action', 'kirim_komentar_mandor');
    formData.append('logbook_id', sel.value);
    formData.append('komentar', input.value.trim());
    formData.append('tanggal', '<?= $tanggal ?>');

    fetch('objek_kerja.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                input.value = '';
                muatKomentar();
                updateBadge();
            } else {
                alert(res.msg || 'Gagal mengirim catatan.');
            }
        })
        .catch(err => {
            alert('Terjadi kesalahan saat mengirim catatan.');
        });
}

function updateBadge() {
    fetch('objek_kerja.php?ajax_komentar=1&action=count_unread&tanggal=<?= urlencode($tanggal) ?>')
        .then(r => r.json())
        .then(res => {
            const badge = document.getElementById('badgeKomentarMandor');
            if (badge) {
                if (res.unread > 0) {
                    badge.textContent = res.unread;
                    badge.className = 'badge-km badge-unread';
                    badge.style.display = 'flex';
                } else if (res.total > 0) {
                    badge.textContent = res.total;
                    badge.className = 'badge-km badge-read';
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
            }
        });
}

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

function formatTanggalWaktu(datetimeStr) {
    if (!datetimeStr) return '';
    const d = new Date(datetimeStr);
    return d.toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) + ' WIB';
}
</script>

</div>

<?php include 'templates/footer.php'; ?>