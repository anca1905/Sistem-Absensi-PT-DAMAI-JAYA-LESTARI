<?php
require '../config/config.php';
include 'templates/header.php';

$bulan = isset($_GET['bulan']) ? str_pad($_GET['bulan'], 2, '0', STR_PAD_LEFT) : date('m');
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');

// Sinkronisasi status Alpha otomatis untuk periode bulan yang dilihat
checkAndSetAlpha($conn, $bulan, $tahun);

$cari  = isset($_GET['cari'])  ? trim($_GET['cari']) : '';
$afdeling = isset($_GET['afdeling']) ? trim($_GET['afdeling']) : '';
$daftar_jabatan = [
    'karyawan' => 'Karyawan',
    'kerani'   => 'Kerani',
    'mandor'   => 'Mandor',
    'pengawas' => 'Pengawas',
];
$jabatan = isset($_GET['jabatan']) ? strtolower(trim($_GET['jabatan'])) : 'karyawan';
if (!array_key_exists($jabatan, $daftar_jabatan)) {
    $jabatan = 'karyawan';
}

$jumlah_hari = date('t', mktime(0, 0, 0, (int)$bulan, 1, $tahun));
$nama_bulan  = [
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
];

// List afdeling untuk dropdown filter
$q_afdeling = mysqli_query($conn, "SELECT DISTINCT afdeling FROM users WHERE role IN ('karyawan', 'kerani', 'mandor', 'pengawas') AND afdeling IS NOT NULL AND afdeling != '' ORDER BY afdeling ASC");
$list_afdeling = [];
while ($afd = mysqli_fetch_assoc($q_afdeling)) {
    $list_afdeling[] = $afd['afdeling'];
}

// --- LOGIKA PENGAJUAN & VALIDASI OLEH KEUANGAN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_keu_val'])) {
    $act = $_POST['action_keu_val'];
    $id_p = (int)$_POST['id_pengajuan'];
    $cat_k = trim($_POST['catatan_keuangan'] ?? '');
    $cat_safe = mysqli_real_escape_string($conn, $cat_k);
    $uid_keu = (int)$_SESSION['user_id'];
    $now_dt = date('Y-m-d H:i:s');

    if ($id_p > 0) {
        $st_target = ($act === 'setujui') ? 'disetujui' : 'ditolak';
        mysqli_query($conn, "UPDATE laporan_pengajuan SET status='$st_target', keuangan_id=$uid_keu, tanggal_validasi='$now_dt', catatan_keuangan='$cat_safe' WHERE id=$id_p");
        
        // Notifikasi WA ke Kerani
        $q_lp = mysqli_query($conn, "SELECT lp.*, u.no_hp, u.name as nama_kerani FROM laporan_pengajuan lp JOIN users u ON lp.kerani_id = u.id WHERE lp.id=$id_p");
        if ($row_lp = mysqli_fetch_assoc($q_lp)) {
            if (!empty($row_lp['no_hp'])) {
                $status_msg = ($st_target === 'disetujui') ? 'DISETUJUI & DIVALIDASI ✅' : 'DIKEMBALIKAN UNTUK DIREVISI ⚠️';
                $pesan_wa = "🔔 *STATUS LAPORAN ABSENSI* 🔔\n\nHalo *{$row_lp['nama_kerani']}*,\nLaporan Absensi untuk *{$row_lp['afdeling']}* periode *" . ($nama_bulan[$row_lp['bulan']] ?? $row_lp['bulan']) . " {$row_lp['tahun']}* telah *{$status_msg}* oleh Bagian Keuangan.\n";
                if (!empty($cat_k)) $pesan_wa .= "💬 Catatan: _{$cat_k}_\n";
                $pesan_wa .= "\n_Sistem Informasi Karyawan PT DJL_";
                sendWA($row_lp['no_hp'], $pesan_wa);
            }
        }
        swalRedirect("Status laporan berhasil diperbarui!", "laporan_absensi.php?bulan=$bulan&tahun=$tahun&afdeling=" . urlencode($afdeling) . "&jabatan=" . urlencode($jabatan), 'success');
    }
}

$info_pengajuan = null;
$status_per_afdeling = [];
$afdeling_diajukan = [];
$bisa_tampil = false;

if (!empty($afdeling)) {
    $info_pengajuan = getLaporanPengajuan($conn, $afdeling, $bulan, $tahun, 'absensi');
    if ($info_pengajuan && in_array($info_pengajuan['status'], ['menunggu_validasi', 'disetujui'])) {
        $bisa_tampil = true;
    } else {
        $bisa_tampil = false;
    }
} else {
    $q_all_st = mysqli_query($conn, "SELECT lp.*, u.name as nama_kerani FROM laporan_pengajuan lp LEFT JOIN users u ON lp.kerani_id = u.id WHERE lp.bulan='$bulan' AND lp.tahun=$tahun AND lp.jenis_laporan='absensi'");
    if ($q_all_st) {
        while ($st_r = mysqli_fetch_assoc($q_all_st)) {
            $status_per_afdeling[$st_r['afdeling']] = $st_r;
            if (in_array($st_r['status'], ['menunggu_validasi', 'disetujui'])) {
                $afdeling_diajukan[] = $st_r['afdeling'];
            }
        }
    }
    if (count($afdeling_diajukan) > 0) {
        $bisa_tampil = true;
    } else {
        $bisa_tampil = false;
    }
}

// Data absensi HANYA ditampilkan jika laporan telah diserahkan oleh Kerani (status menunggu_validasi / disetujui)
$list_karyawan = [];
$data_absen = [];

if ($bisa_tampil) {
    $jabatan_safe = mysqli_real_escape_string($conn, $jabatan);
    $where = "(role='$jabatan_safe' OR jabatan='$jabatan_safe')";
    if (!empty($cari)) {
        $cari_safe = mysqli_real_escape_string($conn, $cari);
        $where .= " AND (name LIKE '%$cari_safe%' OR nik LIKE '%$cari_safe%')";
    }
    if (!empty($afdeling)) {
        $afdeling_safe = mysqli_real_escape_string($conn, $afdeling);
        $where .= " AND afdeling = '$afdeling_safe'";
    } else {
        $afd_in = array_map(function($a) use ($conn) { return "'" . mysqli_real_escape_string($conn, $a) . "'"; }, $afdeling_diajukan);
        $where .= " AND afdeling IN (" . implode(',', $afd_in) . ")";
    }

    $q_users = mysqli_query($conn, "SELECT id, nik, name, afdeling, role, jabatan FROM users WHERE $where ORDER BY afdeling ASC, name ASC");
    if ($q_users) {
        while ($u = mysqli_fetch_assoc($q_users)) {
            $list_karyawan[] = $u;
        }
    }

    $bulan_int = (int)$bulan;
    $q_absen = mysqli_query($conn, "
        SELECT user_id, DAY(tanggal) as hari, status_kehadiran
        FROM absensis
        WHERE MONTH(tanggal)=$bulan_int AND YEAR(tanggal)=$tahun
    ");
    if ($q_absen) {
        while ($a = mysqli_fetch_assoc($q_absen)) {
            $data_absen[$a['user_id']][$a['hari']] = $a['status_kehadiran'];
        }
    }
}


// --- LOGIKA TANDA TANGAN BERDASARKAN FILTER AFDELING ---
$nama_mandor_tampil = '';
$nama_kerani_tampil = '';
if (!empty($afdeling)) {
    $penandatangan = getReportSignatories($conn, $afdeling);
    $nama_pengawas = $penandatangan["pengawas"];
    $nama_kerani = $penandatangan["kerani"];
    $teks_afdeling = $penandatangan["label_afdeling"] ?: "Afdeling";
    if (!empty($nama_kerani) && $nama_kerani !== '( ................................... )') {
        $nama_kerani_tampil = $nama_kerani;
    }

    // Cari Mandor pada afdeling terkait
    $num_afd = preg_replace('/[^0-9]/', '', $afdeling);
    $afd_safe = mysqli_real_escape_string($conn, $afdeling);
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
} else {
    // Jika filter Semua Afdeling, tampilkan garis titik-titik untuk diisi manual jika dicetak
    $teks_afdeling = "Semua Afdeling";
    $nama_kerani = "( ................................... )";
    $nama_pengawas = "( ................................... )";
}
// -------------------------------------------------------

?>

<style>
    /* Print styles handled by print.css */

    /* Dokumen resmi hanya digunakan saat mencetak, bukan pada tampilan laporan. */
    #official-print-doc {
        display: none;
    }

    @media print {
        #official-print-doc {
            display: block !important;
        }
    }

    .toolbar {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }

    .filter-input,
    .filter-select {
        padding: 9px 14px;
        border: 1.5px solid #d1d5db;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
        color: #1f2937;
        background: white;
        outline: none;
        font-family: inherit;
        transition: border-color 0.2s;
    }

    .filter-input:focus,
    .filter-select:focus {
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, .12);
    }

    .btn-filter {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #10b981;
        color: white;
        border: none;
        padding: 9px 18px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
        font-family: inherit;
    }

    .btn-filter:hover {
        background: #059669;
    }

    .btn-print {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: white;
        color: #374151;
        border: 1.5px solid #d1d5db;
        padding: 9px 18px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
        font-family: inherit;
    }

    .btn-print:hover {
        background: #f9fafb;
        border-color: #10b981;
        color: #059669;
    }

    /* Card */
    .lk-card {
        background: white;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 15px rgba(0, 0, 0, .02);
        overflow: hidden;
        margin-bottom: 24px;
    }

    .lk-header-main {
        padding: 20px 24px;
        border-bottom: 1.5px solid #e2e8f0;
        background: #f8fafc;
        text-align: center;
    }

    .lk-title {
        font-size: 17px;
        font-weight: 800;
        color: #1f2937;
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

    /* Tipe badge */
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

    .lk-card .legend {
        margin: 0;
        border: none;
        border-bottom: 1px solid #e5e7eb;
        border-radius: 0;
        background: #ffffff;
        padding: 10px 16px;
    }

    .table-wrap {
        overflow-x: auto;
        border-radius: 14px;
        border: 1px solid #e5e7eb;
        background: white;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
    }

    .table-absen {
        border-collapse: collapse;
        white-space: nowrap;
        font-size: 12px;
        width: 100%;
    }

    .table-absen th {
        background: #064e3b;
        color: #a7f3d0;
        font-weight: 700;
        font-size: 10px;
        text-transform: uppercase;
        padding: 12px 10px;
        border: 1px solid #065f46;
        text-align: center;
        letter-spacing: 0.5px;
    }

    .table-absen td {
        padding: 10px 8px;
        border: 1px solid #f3f4f6;
        text-align: center;
        color: #374151;
    }

    .table-absen tbody tr:hover td {
        background: #f0fdf4;
    }

    .table-absen td.td-name {
        text-align: left;
        font-weight: 700;
        color: #064e3b;
        min-width: 150px;
    }

    .table-absen td.td-nik {
        font-size: 11px;
        color: #6b7280;
    }

    .badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        border-radius: 6px;
        font-weight: 700;
        font-size: 10px;
    }

    .b-h {
        background: #d1fae5;
        color: #065f46;
    }

    .b-t {
        background: #fef3c7;
        color: #92400e;
    }

    .b-a {
        background: #fee2e2;
        color: #991b1b;
    }

    .b-i {
        background: #dbeafe;
        color: #1e40af;
    }

    .b-s {
        background: #ede9fe;
        color: #5b21b6;
    }

    .b-c {
        background: #ffedd5;
        color: #9a3412;
    }

    .summary-total {
        font-weight: 800;
        color: #065f46;
    }

    .afdeling-row td {
        background: #f0fdf4;
        font-weight: 700;
        font-size: 11px;
        color: #065f46;
        border-top: 2px solid #d1fae5;
    }

    .legend {
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
        justify-content: center;
        font-size: 11px;
        color: #6b7280;
        margin-bottom: 16px;
        padding: 12px 16px;
        background: white;
        border-radius: 10px;
        border: 1px solid #e5e7eb;
        align-items: center;
    }

    .legend span {
        display: flex;
        align-items: center;
        gap: 5px;
    }
</style>

<!-- Toolbar -->
<div class="toolbar no-print">
    <form method="GET" id="filterForm" onchange="this.submit()" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
        <select name="bulan" class="filter-select">
            <?php foreach ($nama_bulan as $num => $name): ?>
                <option value="<?= $num ?>" <?= $bulan == $num ? 'selected' : '' ?>><?= $name ?></option>
            <?php endforeach; ?>
        </select>
        <select name="tahun" class="filter-select">
            <?php for ($y = date('Y') - 2; $y <= date('Y'); $y++): ?>
                <option value="<?= $y ?>" <?= $tahun == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
        <select name="afdeling" class="filter-select">
            <option value="">Semua Afdeling</option>
            <?php foreach ($list_afdeling as $afd): ?>
                <option value="<?= htmlspecialchars($afd) ?>" <?= $afdeling == $afd ? 'selected' : '' ?>>Afdeling <?= htmlspecialchars($afd) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="jabatan" class="filter-select" aria-label="Filter jabatan">
            <?php foreach ($daftar_jabatan as $nilai_jabatan => $label_jabatan): ?>
                <option value="<?= $nilai_jabatan ?>" <?= $jabatan === $nilai_jabatan ? 'selected' : '' ?>><?= $label_jabatan ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="cari" class="filter-input" placeholder="Cari nama / NIK..." value="<?= htmlspecialchars($cari) ?>" style="min-width:180px;">
        <button type="submit" class="btn-filter" style="display: none;">
            Tampilkan
        </button>
    </form>
    <?php if ($bisa_tampil): ?>
        <button class="btn-print" onclick="window.print()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                <rect x="6" y="14" width="12" height="8"></rect>
            </svg>
            Cetak PDF
        </button>
    <?php else: ?>
        <button class="btn-print" type="button" onclick="alert('Laporan belum dikirim oleh Kerani untuk periode ini.');" style="opacity:0.5; cursor:not-allowed;" title="Laporan belum diserahkan oleh Kerani">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                <rect x="6" y="14" width="12" height="8"></rect>
            </svg>
            Cetak PDF (Terkunci)
        </button>
    <?php endif; ?>
</div>

<?php if (empty($afdeling)): ?>
    <!-- Status Rekap Seluruh Afdeling -->
    <div class="no-print" style="background: white; border-radius: 14px; padding: 18px 22px; border: 1px solid #e5e7eb; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
        <div style="font-size: 13px; font-weight: 700; color: #374151; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between;">
            <span><i class="fa-solid fa-list-check" style="color: #10b981;"></i> Status Pengajuan Laporan Absensi Seluruh Afdeling (<?= $nama_bulan[$bulan] ?> <?= $tahun ?>):</span>
            <a href="validasi_laporan.php?bulan=<?= $bulan ?>&tahun=<?= $tahun ?>" style="font-size: 12px; color: #2563eb; text-decoration: none; font-weight: 600;">Buka Menu Validasi &rarr;</a>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <?php foreach ($list_afdeling as $afd_item): 
                $st_item = $status_per_afdeling[$afd_item] ?? null;
                $st_code = $st_item ? $st_item['status'] : 'draft';
            ?>
                <a href="laporan_absensi.php?bulan=<?= $bulan ?>&tahun=<?= $tahun ?>&afdeling=<?= urlencode($afd_item) ?>&jabatan=<?= urlencode($jabatan) ?>" style="text-decoration: none; display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; transition: all 0.15s;
                    <?php if ($st_code === 'disetujui'): ?>
                        background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0;
                    <?php elseif ($st_code === 'menunggu_validasi'): ?>
                        background: #fef3c7; color: #b45309; border: 1px solid #fde68a;
                    <?php elseif ($st_code === 'ditolak'): ?>
                        background: #fee2e2; color: #991b1b; border: 1px solid #fecaca;
                    <?php else: ?>
                        background: #f3f4f6; color: #6b7280; border: 1px solid #e5e7eb;
                    <?php endif; ?>
                ">
                    <span><?= htmlspecialchars($afd_item) ?>:</span>
                    <?php if ($st_code === 'disetujui'): ?>
                        <span>✅ Disetujui</span>
                    <?php elseif ($st_code === 'menunggu_validasi'): ?>
                        <span>⏳ Menunggu Validasi</span>
                    <?php elseif ($st_code === 'ditolak'): ?>
                        <span>❌ Perlu Revisi</span>
                    <?php else: ?>
                        <span>⚠️ Belum Dikirim</span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
<?php else: ?>
    <!-- Status Pengajuan Spesifik Afdeling -->
    <div class="no-print" style="margin-bottom: 24px; border-radius: 14px; padding: 18px 22px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;
        <?php if (!$info_pengajuan): ?>
            background: #fef2f2; border: 1.5px solid #fecaca; color: #991b1b;
        <?php elseif ($info_pengajuan['status'] === 'menunggu_validasi'): ?>
            background: #fffbeb; border: 1.5px solid #fde68a; color: #92400e;
        <?php elseif ($info_pengajuan['status'] === 'disetujui'): ?>
            background: #f0fdf4; border: 1.5px solid #bbf7d0; color: #166534;
        <?php elseif ($info_pengajuan['status'] === 'ditolak'): ?>
            background: #fee2e2; border: 1.5px solid #fca5a5; color: #991b1b;
        <?php endif; ?>
    ">
        <div style="display: flex; align-items: center; gap: 16px; flex: 1;">
            <div style="font-size: 28px;">
                <?php if (!$info_pengajuan): ?>
                    ⚠️
                <?php elseif ($info_pengajuan['status'] === 'menunggu_validasi'): ?>
                    ⏳
                <?php elseif ($info_pengajuan['status'] === 'disetujui'): ?>
                    ✅
                <?php elseif ($info_pengajuan['status'] === 'ditolak'): ?>
                    ❌
                <?php endif; ?>
            </div>
            <div>
                <div style="font-size: 15px; font-weight: 800;">
                    <?php if (!$info_pengajuan): ?>
                        Laporan Belum Dikirim oleh Kerani <?= htmlspecialchars($afdeling) ?>
                    <?php elseif ($info_pengajuan['status'] === 'menunggu_validasi'): ?>
                        Laporan Menunggu Validasi Keuangan (Dikirim oleh Kerani: <?= htmlspecialchars($info_pengajuan['nama_kerani'] ?: 'Kerani') ?>)
                    <?php elseif ($info_pengajuan['status'] === 'disetujui'): ?>
                        Laporan Resmi Telah Divalidasi oleh Keuangan (<?= htmlspecialchars($info_pengajuan['nama_keuangan'] ?: 'Keuangan') ?>)
                    <?php elseif ($info_pengajuan['status'] === 'ditolak'): ?>
                        Laporan Telah Dikembalikan ke Kerani untuk Direvisi
                    <?php endif; ?>
                </div>
                <div style="font-size: 12px; margin-top: 4px; line-height: 1.5; opacity: 0.95;">
                    <?php if (!$info_pengajuan): ?>
                        Kerani Afdeling <?= htmlspecialchars($afdeling) ?> belum menyelesaikan proses cross-check dan belum mengirimkan laporan resmi periode <?= $nama_bulan[$bulan] ?> <?= $tahun ?>. Data di bawah masih berupa data berjalan dan <u>belum sah</u> untuk diproses.
                    <?php elseif ($info_pengajuan['status'] === 'menunggu_validasi'): ?>
                        Dikirim pada: <strong><?= date('d/m/Y H:i', strtotime($info_pengajuan['tanggal_kirim'])) ?></strong>.
                        <?php if (!empty($info_pengajuan['catatan_kerani'])): ?>
                            | Catatan Kerani: <em>"<?= htmlspecialchars($info_pengajuan['catatan_kerani']) ?>"</em>
                        <?php endif; ?>
                        <br>Silakan periksa rekap absensi di bawah ini, lalu pilih <strong>Setujui & Validasi</strong> atau <strong>Minta Revisi</strong>.
                    <?php elseif ($info_pengajuan['status'] === 'disetujui'): ?>
                        Divalidasi pada: <strong><?= date('d/m/Y H:i', strtotime($info_pengajuan['tanggal_validasi'])) ?></strong>. Laporan ini sah sebagai dasar pencatatan keuangan dan penggajian.
                        <?php if (!empty($info_pengajuan['catatan_keuangan'])): ?>
                            | Catatan: <em>"<?= htmlspecialchars($info_pengajuan['catatan_keuangan']) ?>"</em>
                        <?php endif; ?>
                    <?php elseif ($info_pengajuan['status'] === 'ditolak'): ?>
                        Catatan Revisi Keuangan: <strong>"<?= htmlspecialchars($info_pengajuan['catatan_keuangan']) ?>"</strong> (<?= date('d/m/Y H:i', strtotime($info_pengajuan['tanggal_validasi'])) ?>). Menunggu Kerani melakukan koreksi dan mengirim ulang.
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($info_pengajuan && $info_pengajuan['status'] === 'menunggu_validasi'): ?>
            <div style="display: flex; gap: 8px; flex-shrink: 0;">
                <button type="button" onclick="bukaModalAksiKeu(<?= $info_pengajuan['id'] ?>, 'setujui')" style="background: #10b981; color: white; border: none; padding: 10px 16px; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 8px rgba(16,185,129,0.3);">
                    ✅ Setujui & Validasi
                </button>
                <button type="button" onclick="bukaModalAksiKeu(<?= $info_pengajuan['id'] ?>, 'tolak')" style="background: #ef4444; color: white; border: none; padding: 10px 16px; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 8px rgba(239,68,68,0.3);">
                    ↩️ Minta Revisi
                </button>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Card Laporan -->
<div class="lk-card print-area">
    <div class="lk-header-main">
        <p class="lk-title">Laporan Absensi <?= htmlspecialchars($daftar_jabatan[$jabatan]) ?><?= !empty($afdeling) ? ' (' . htmlspecialchars($afdeling) . ')' : '' ?></p>
        <p class="lk-subtitle">
            <span>Periode: <strong>01 &ndash; <?= $jumlah_hari ?> <?= $nama_bulan[$bulan] ?> <?= $tahun ?></strong></span>
            <span class="sep">|</span>
            <span>Afdeling: <strong><?= !empty($afdeling) ? htmlspecialchars($afdeling) : 'Semua Afdeling' ?></strong></span>
            <span class="sep">|</span>
            <span>Jabatan: <strong><?= htmlspecialchars($daftar_jabatan[$jabatan]) ?></strong></span>
            <?php if (!empty($nama_kerani_tampil)): ?>
                <span class="sep">|</span>
                <span>Kerani: <strong><?= htmlspecialchars($nama_kerani_tampil) ?></strong></span>
            <?php endif; ?>
            <?php if (!empty($nama_mandor_tampil) && $jabatan === 'karyawan'): ?>
                <span class="sep">|</span>
                <span>Mandor: <strong><?= htmlspecialchars($nama_mandor_tampil) ?></strong></span>
            <?php endif; ?>
            <?php if (!empty($cari)): ?>
                <span class="sep">|</span>
                <span>Pencarian: <strong>&ldquo;<?= htmlspecialchars($cari) ?>&rdquo;</strong></span>
            <?php endif; ?>
            <span class="sep">|</span>
            <span class="tipe-badge badge-<?= $jabatan ?>"><?= htmlspecialchars($daftar_jabatan[$jabatan]) ?></span>
        </p>
    </div>

    <?php if (!$bisa_tampil): ?>
        <div style="padding: 60px 24px; text-align: center; background: #ffffff;">
            <div style="font-size: 52px; line-height: 1; margin-bottom: 16px;">📭</div>
            <h3 style="font-size: 18px; font-weight: 800; color: #1e293b; margin-bottom: 8px;">
                <?= !empty($afdeling) ? 'Laporan Belum Dikirim oleh Kerani ' . htmlspecialchars($afdeling) : 'Belum Ada Laporan yang Dikirim oleh Kerani' ?>
            </h3>
            <p style="font-size: 14px; color: #64748b; max-width: 540px; margin: 0 auto 18px; line-height: 1.6;">
                <?php if (!empty($afdeling)): ?>
                    <?php if ($info_pengajuan && $info_pengajuan['status'] === 'ditolak'): ?>
                        Laporan absensi Afdeling <strong><?= htmlspecialchars($afdeling) ?></strong> periode <strong><?= $nama_bulan[$bulan] ?> <?= $tahun ?></strong> telah dikembalikan ke Kerani untuk revisi. Data akan muncul kembali setelah Kerani mengirimkan perbaikan.
                    <?php else: ?>
                        Data absensi Afdeling <strong><?= htmlspecialchars($afdeling) ?></strong> periode <strong><?= $nama_bulan[$bulan] ?> <?= $tahun ?></strong> belum dapat ditampilkan karena Kerani belum menyerahkan laporan resmi untuk divalidasi.
                    <?php endif; ?>
                <?php else: ?>
                    Belum ada Kerani afdeling yang mengirimkan laporan absensi resmi untuk periode <strong><?= $nama_bulan[$bulan] ?> <?= $tahun ?></strong>. Rekapitulasi absensi akan muncul secara otomatis setelah laporan diajukan oleh masing-masing Kerani.
                <?php endif; ?>
            </p>
            <div style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 999px; font-size: 12px; font-weight: 600; color: #64748b;">
                <i class="fa-solid fa-clock-rotate-left" style="color: #f59e0b;"></i>
                Status: Menunggu pengiriman laporan resmi dari Kerani
            </div>
        </div>
    <?php else: ?>
        <?php if (empty($afdeling) && count($afdeling_diajukan) < count($list_afdeling)): ?>
            <div class="no-print" style="margin: 14px 20px 0; padding: 10px 16px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; font-size: 12px; color: #1e40af; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-circle-info"></i>
                <span>Menampilkan data dari Afdeling yang sudah menyerahkan laporan: <strong><?= implode(', ', $afdeling_diajukan) ?></strong>. Afdeling lain belum menyerahkan laporan periode ini.</span>
            </div>
        <?php endif; ?>

        <!-- Legend -->
        <div class="legend">
            <strong style="color:#064e3b;">Keterangan:</strong>
            <span><span class="badge b-h">H</span> Hadir</span>
            <span><span class="badge b-a">A</span> Alpha</span>
            <span><span class="badge b-i">I</span> Izin</span>
            <span><span class="badge b-s">S</span> Sakit</span>
            <span><span class="badge b-c">C</span> Cuti</span>
            <span><span style="color:#94a3b8;">-</span> Tidak Ada Data</span>
        </div>

        <?php if (count($list_karyawan) > 0): ?>
            <div class="table-wrap" style="border:none; border-radius:0; box-shadow:none;">
                <table class="table-absen">
                    <thead>
                        <tr>
                            <th rowspan="2" style="width:30px;">No</th>
                            <th rowspan="2" style="min-width:70px;">NIK</th>
                            <th rowspan="2" style="min-width:160px; text-align:left;">Nama Personel</th>
                            <th rowspan="2">Afdeling</th>
                            <?php for ($i = 1; $i <= $jumlah_hari; $i++): ?>
                                <th style="min-width:26px;"><?= $i ?></th>
                            <?php endfor; ?>
                            <th rowspan="2">H</th>
                            <th rowspan="2">I</th>
                            <th rowspan="2">A</th>
                            <th rowspan="2">S</th>
                            <th rowspan="2">C</th>
                            <th rowspan="2">Total<br>Hadir</th>
                        </tr>
                        <tr>
                            <?php for ($i = 1; $i <= $jumlah_hari; $i++):
                                $dow = date('N', mktime(0, 0, 0, $bulan_int, $i, $tahun)); // 7 = Sunday
                            ?>
                                <th style="<?= $dow == 7 ? 'background:#0f3f2e;' : '' ?>"><?= ['', 'S', 'S', 'R', 'K', 'J', 'S', 'M'][$dow] ?></th>
                            <?php endfor; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        $cur_afdeling = null;
                        foreach ($list_karyawan as $kar):
                            if ($cur_afdeling !== $kar['afdeling']) {
                                $cur_afdeling = $kar['afdeling'];
                                echo "<tr class='afdeling-row'><td colspan='" . ($jumlah_hari + 10) . "'>📍 Afdeling: " . htmlspecialchars($cur_afdeling ?: '-') . "</td></tr>";
                            }
                            $absen = $data_absen[$kar['id']] ?? [];
                            $h = 0;
                            $a = 0;
                            $i_jin = 0;
                            $s_sakit = 0;
                            $c_cuti = 0;
                        ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td class="td-nik"><?= htmlspecialchars($kar['nik']) ?></td>
                                <td class="td-name"><?= htmlspecialchars($kar['name']) ?></td>
                                <td style="font-size:11px;"><?= htmlspecialchars($kar['afdeling'] ?? '-') ?></td>
                                <?php for ($i = 1; $i <= $jumlah_hari; $i++):
                                    $s = strtolower((string)($absen[$i] ?? null));
                                    if ($s == 'hadir' || $s == 'tepat_waktu') {
                                        $h++;
                                        echo '<td><span class="badge b-h">H</span></td>';
                                    } elseif (in_array($s, ['alpha', 'alpa', 'alfa', 'terlambat'])) {
                                        $a++;
                                        echo '<td><span class="badge b-a">A</span></td>';
                                    } elseif ($s == 'izin') {
                                        $i_jin++;
                                        echo '<td><span class="badge b-i">I</span></td>';
                                    } elseif ($s == 'sakit') {
                                        $s_sakit++;
                                        echo '<td><span class="badge b-s">S</span></td>';
                                    } elseif ($s == 'cuti') {
                                        $c_cuti++;
                                        echo '<td><span class="badge b-c">C</span></td>';
                                    } else {
                                        echo '<td><span style="color:#d1d5db;">-</span></td>';
                                    }
                                endfor; ?>
                                <td class="summary-total"><?= $h ?></td>
                                <td style="font-weight:700; color:#1e40af;"><?= $i_jin ?></td>
                                <td style="font-weight:700; color:#991b1b;"><?= $a ?></td>
                                <td style="font-weight:700; color:#5b21b6;"><?= $s_sakit ?></td>
                                <td style="font-weight:700; color:#9a3412;"><?= $c_cuti ?></td>
                                <td style="font-weight:800; color:#0f172a;"><?= $h ?> Hari</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div style="text-align:center; padding:48px; background:white; color:#9ca3af;">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5" style="margin:0 auto 12px;display:block;">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                </svg>
                Tidak ada data <?= strtolower($daftar_jabatan[$jabatan]) ?> yang ditemukan.
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- ========== DOKUMEN CETAK RESMI ========== -->
<div id="official-print-doc">
    <!-- Kop Surat -->
    <div class="doc-header">
        <img src="<?= BASE_URL ?>assets/img/logo.png" alt="" class="doc-header-logo" onerror="this.style.display='none'">
        <div class="doc-header-text">
            <h1>PT Damai Jaya Lestari</h1>
            <h2>Laporan Rekapitulasi Absensi <?= htmlspecialchars($daftar_jabatan[$jabatan]) ?></h2>
            <p>Jl. Perkebunan No. 1 &nbsp;|&nbsp; Telp: (021) 000-0000 &nbsp;|&nbsp; Email: admin@djl.co.id</p>
        </div>
    </div>

    <!-- Judul & Metadata -->
    <div class="doc-title">
        <h3>Laporan Absensi &mdash; <?= $nama_bulan[$bulan] ?> <?= $tahun ?></h3>
    </div>
    <div class="doc-meta">
        <div><strong>Periode&nbsp;&nbsp;&nbsp;&nbsp;:</strong> 01 &ndash; <?= $jumlah_hari ?> <?= $nama_bulan[$bulan] ?> <?= $tahun ?></div>
        <div><strong>Afdeling&nbsp;&nbsp;&nbsp;:</strong> <?= !empty($afdeling) ? htmlspecialchars($afdeling) : 'Semua Afdeling' ?></div>
        <div><strong>Jabatan&nbsp;&nbsp;&nbsp;&nbsp;:</strong> <?= htmlspecialchars($daftar_jabatan[$jabatan]) ?></div>
        <?php if (!empty($nama_kerani_tampil)): ?>
            <div><strong>Kerani&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</strong> <?= htmlspecialchars($nama_kerani_tampil) ?></div>
        <?php endif; ?>
        <?php if (!empty($nama_mandor_tampil) && $jabatan === 'karyawan'): ?>
            <div><strong>Mandor&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</strong> <?= htmlspecialchars($nama_mandor_tampil) ?></div>
        <?php endif; ?>
        <div><strong>Jumlah Hari:</strong> <?= $jumlah_hari ?> hari</div>
        <div><strong>Dicetak&nbsp;&nbsp;&nbsp;&nbsp;:</strong> <?= date('d F Y, H:i') ?></div>
    </div>

    <?php if (!$bisa_tampil): ?>
        <div style="text-align: center; padding: 60px 20px; border: 1.5px dashed #cbd5e1; border-radius: 8px; margin: 30px 0;">
            <h3 style="font-size: 14pt; color: #475569; margin-bottom: 8px;">LAPORAN BELUM DIKIRIM OLEH KERANI</h3>
            <p style="font-size: 10pt; color: #94a3b8; margin: 0;">Data absensi belum resmi diserahkan oleh Kerani untuk periode <?= $nama_bulan[$bulan] ?> <?= $tahun ?>.</p>
        </div>
    <?php else: ?>
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
                    <th rowspan="2" style="min-width:120px; text-align:left;">Nama Personel</th>
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
                foreach ($list_karyawan as $kar):
                ?>
                    <tr>
                        <td class="text-center"><?= $pno++ ?></td>
                        <td><?= htmlspecialchars($kar['nik']) ?></td>
                        <td><?= htmlspecialchars($kar['name']) ?></td>
                        <?php
                        $h = 0; $a = 0; $i_jin = 0; $s_sakit = 0; $c_cuti = 0;
                        $absen = $data_absen[$kar['id']] ?? [];
                        for ($i = 1; $i <= $jumlah_hari; $i++):
                            $st = strtolower((string)($absen[$i] ?? null));
                            $k = '';
                            $dc = '';
                            if (in_array($st, ['hadir', 'tepat_waktu'])) {
                                $k = 'H'; $dc = 'doc-status-H'; $h++;
                            } elseif (in_array($st, ['alpha', 'alpa', 'alfa', 'terlambat'])) {
                                $k = 'A'; $dc = 'doc-status-A'; $a++;
                            } elseif ($st == 'izin') {
                                $k = 'I'; $dc = 'doc-status-I'; $i_jin++;
                            } elseif ($st == 'sakit') {
                                $k = 'S'; $dc = 'doc-status-S'; $s_sakit++;
                            } elseif ($st == 'cuti') {
                                $k = 'C'; $dc = 'doc-status-C'; $c_cuti++;
                            }
                        ?>
                            <td class="text-center <?= $dc ?>" style="font-size:8pt; font-weight:bold;"><?= $k ?></td>
                        <?php endfor; ?>
                        <td class="text-center" style="font-weight:bold;"><?= $h ?></td>
                        <td class="text-center" style="font-weight:bold;"><?= $i_jin ?></td>
                        <td class="text-center" style="font-weight:bold;"><?= $a ?></td>
                        <td class="text-center" style="font-weight:bold;"><?= $s_sakit ?></td>
                        <td class="text-center" style="font-weight:bold;"><?= $c_cuti ?></td>
                        <td class="text-center" style="font-weight:bold;"><?= $h ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($list_karyawan)): ?>
                    <tr>
                        <?php for($i=0; $i< $jumlah_hari + 9 ; $i++): ?><td class="td-empty">-</td><?php endfor; ?>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Tanda Tangan -->
    <div class="doc-signature">
        <div class="doc-signature-col">
            <p>Diketahui oleh,</p>
            <span class="sig-name"><?= htmlspecialchars($nama_pengawas) ?></span>
            <div style="font-weight:bold;">Pengawas <?= $teks_afdeling ?></div>
        </div>
        <div class="doc-signature-col">
            <p>Disusun oleh,</p>
            <span class="sig-name"><?= htmlspecialchars($nama_kerani) ?></span>
            <div style="font-weight:bold;">Kerani <?= $teks_afdeling ?></div>
        </div>
    </div>

    <div class="doc-footer">
        Dokumen ini dicetak secara otomatis oleh Sistem Informasi PT Damai Jaya Lestari pada <?= date('d F Y \p\u\k\u\l H:i') ?> WIB.
    </div>
</div>

<!-- Modal Aksi Validasi Cepat Keuangan -->
<div id="modalAksiKeuWrap" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); backdrop-filter:blur(3px); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:white; border-radius:16px; width:90%; max-width:480px; padding:26px; box-shadow:0 20px 40px rgba(0,0,0,0.2);">
        <h3 id="modalAksiKeuTitle" style="font-size:18px; font-weight:800; margin-bottom:8px;">Validasi Laporan</h3>
        <p id="modalAksiKeuDesc" style="font-size:13px; color:#6b7280; line-height:1.5; margin-bottom:16px;"></p>
        <form method="POST">
            <input type="hidden" name="action_keu_val" id="inputAksiKeu">
            <input type="hidden" name="id_pengajuan" id="inputIdPengajuan">
            <div style="margin-bottom:16px;">
                <label id="labelCatatanKeu" style="display:block; font-size:12px; font-weight:700; color:#374151; margin-bottom:6px;">Catatan:</label>
                <textarea name="catatan_keuangan" id="txtCatatanKeu" style="width:100%; height:80px; border:1px solid #d1d5db; border-radius:8px; padding:10px; font-size:13px; outline:none; resize:vertical;" placeholder="Tuliskan catatan..."></textarea>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" onclick="tutupModalAksiKeu()" style="padding:9px 16px; background:#f3f4f6; color:#4b5563; border:none; border-radius:8px; font-weight:700; cursor:pointer;">Batal</button>
                <button type="submit" id="btnSubmitAksiKeu" style="padding:9px 18px; color:white; border:none; border-radius:8px; font-weight:700; cursor:pointer;"></button>
            </div>
        </form>
    </div>
</div>

<script>
function bukaModalAksiKeu(id, act) {
    document.getElementById('inputIdPengajuan').value = id;
    document.getElementById('inputAksiKeu').value = act;
    const title = document.getElementById('modalAksiKeuTitle');
    const desc = document.getElementById('modalAksiKeuDesc');
    const label = document.getElementById('labelCatatanKeu');
    const txt = document.getElementById('txtCatatanKeu');
    const btn = document.getElementById('btnSubmitAksiKeu');

    if (act === 'setujui') {
        title.innerText = 'Validasi & Setujui Laporan Absensi';
        title.style.color = '#059669';
        desc.innerText = 'Apakah Anda yakin ingin menyetujui dan memvalidasi laporan absensi ini untuk dasar proses penggajian?';
        label.innerText = 'Catatan Keuangan (Opsional):';
        txt.required = false;
        btn.style.background = '#10b981';
        btn.innerHTML = '✅ Setujui Laporan';
    } else {
        title.innerText = 'Kembalikan Laporan untuk Revisi';
        title.style.color = '#dc2626';
        desc.innerText = 'Kembalikan laporan ini ke Kerani untuk diperbaiki/dikoreksi.';
        label.innerHTML = 'Alasan / Catatan Revisi <span style="color:#ef4444;">*</span>:';
        txt.required = true;
        btn.style.background = '#ef4444';
        btn.innerHTML = '↩️ Kembalikan ke Kerani';
    }

    document.getElementById('modalAksiKeuWrap').style.display = 'flex';
}

function tutupModalAksiKeu() {
    document.getElementById('modalAksiKeuWrap').style.display = 'none';
}
</script>

<?php include 'templates/footer.php'; ?>