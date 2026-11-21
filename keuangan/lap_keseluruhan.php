<?php
require '../config/config.php';
include 'templates/header.php';

// --- Konstanta Data Real ---
$list_objek = [
    'Panen',
    'Penunasan',
    'Racun piringan',
    'Perawatan',
    'Muat TBS ke truk',
    'Muat TBS ke jonder'
];

// Tipe tabel berdasarkan objek kerja
function getTableType($objek)
{
    if ($objek === 'Panen') return 'T3';
    if (in_array($objek, ['Penunasan', 'Racun piringan', 'Perawatan'])) return 'T2';
    if (in_array($objek, ['Muat TBS ke truk', 'Muat TBS ke jonder'])) return 'T5';
    return 'T2';
}

// Label tipe tabel
$label_tipe = [
    'T1' => 'Langsir',
    'T2' => 'Pemeliharaan',
    'T3' => 'Panen',
    'T4' => 'Kutip Brondolan',
    'T5' => 'Muat TBS'
];

// Nama bulan
$nama_bulan = [
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

// --- Filter ---
$bulan     = isset($_GET['bulan'])      ? str_pad($_GET['bulan'], 2, '0', STR_PAD_LEFT) : date('m');
$tahun     = isset($_GET['tahun'])      ? (int)$_GET['tahun']  : (int)date('Y');
$objek     = isset($_GET['objek'])      ? $_GET['objek']        : 'Panen';
$afdeling  = isset($_GET['afdeling'])   ? trim($_GET['afdeling']) : '';
$cari      = isset($_GET['cari'])       ? trim($_GET['cari'])   : '';
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

if (!in_array($objek, $list_objek, true)) $objek = 'Panen';
$tipe = getTableType($objek);

$objek_safe   = mysqli_real_escape_string($conn, $objek);
$bulan_int    = (int)$bulan;

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
                $pesan_wa = "🔔 *STATUS LAPORAN KESELURUHAN* 🔔\n\nHalo *{$row_lp['nama_kerani']}*,\nLaporan Keseluruhan Objek Kerja untuk *{$row_lp['afdeling']}* periode *" . ($nama_bulan[$row_lp['bulan']] ?? $row_lp['bulan']) . " {$row_lp['tahun']}* telah *{$status_msg}* oleh Bagian Keuangan.\n";
                if (!empty($cat_k)) $pesan_wa .= "💬 Catatan: _{$cat_k}_\n";
                $pesan_wa .= "\n_Sistem Informasi Karyawan PT DJL_";
                sendWA($row_lp['no_hp'], $pesan_wa);
            }
        }
        swalRedirect("Status laporan berhasil diperbarui!", "lap_keseluruhan.php?bulan=$bulan&tahun=$tahun&afdeling=" . urlencode($afdeling) . "&objek=" . urlencode($objek) . "&jabatan=" . urlencode($jabatan), 'success');
    }
}

$info_pengajuan = null;
$status_per_afdeling = [];
$afdeling_diajukan = [];
$bisa_tampil = false;

if (!empty($afdeling)) {
    $info_pengajuan = getLaporanPengajuan($conn, $afdeling, $bulan, $tahun, 'keseluruhan');
    if ($info_pengajuan && in_array($info_pengajuan['status'], ['menunggu_validasi', 'disetujui'])) {
        $bisa_tampil = true;
    } else {
        $bisa_tampil = false;
    }
} else {
    $q_all_st = mysqli_query($conn, "SELECT lp.*, u.name as nama_kerani FROM laporan_pengajuan lp LEFT JOIN users u ON lp.kerani_id = u.id WHERE lp.bulan='$bulan' AND lp.tahun=$tahun AND lp.jenis_laporan='keseluruhan'");
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


// --- LOGIKA TANDA TANGAN BERDASARKAN FILTER AFDELING ---
if (!empty($afdeling)) {
    $penandatangan = getReportSignatories($conn, $afdeling);
    $nama_pengawas = $penandatangan["pengawas"];
    $nama_kerani = $penandatangan["kerani"];
    $teks_afdeling = $penandatangan["label_afdeling"] ?: "Afdeling";
} else {
    // Jika filter Semua Afdeling, tampilkan garis titik-titik untuk diisi manual jika dicetak
    $teks_afdeling = "Semua Afdeling";
    $nama_kerani = "( ................................... )";
    $nama_pengawas = "( ................................... )";
}
// -------------------------------------------------------


// Ambil daftar afdeling
$q_afd = mysqli_query($conn, "SELECT DISTINCT afdeling FROM users WHERE role IN ('karyawan', 'kerani', 'mandor', 'pengawas') AND afdeling != '' ORDER BY afdeling ASC");
$list_afdeling = [];
while ($a = mysqli_fetch_assoc($q_afd)) $list_afdeling[] = $a['afdeling'];

// --- Ambil personel sesuai jabatan (keuangan bisa lihat data HANYA jika laporan sudah diserahkan) ---
$list_karyawan = [];
if ($bisa_tampil) {
    $jabatan_safe = mysqli_real_escape_string($conn, $jabatan);
    $where_karyawan = "(role='$jabatan_safe' OR jabatan='$jabatan_safe')";
    if (!empty($afdeling)) {
        $afd_safe = mysqli_real_escape_string($conn, $afdeling);
        $where_karyawan .= " AND afdeling='$afd_safe'";
    } else {
        $afd_in = array_map(function($a) use ($conn) { return "'" . mysqli_real_escape_string($conn, $a) . "'"; }, $afdeling_diajukan);
        $where_karyawan .= " AND afdeling IN (" . implode(',', $afd_in) . ")";
    }
    if (!empty($cari)) {
        $cari_safe = mysqli_real_escape_string($conn, $cari);
        $where_karyawan .= " AND (name LIKE '%$cari_safe%' OR nik LIKE '%$cari_safe%')";
    }
    $q_users = mysqli_query($conn, "SELECT id, nik, name, afdeling, role, jabatan FROM users WHERE $where_karyawan ORDER BY afdeling ASC, name ASC");
    if ($q_users) {
        while ($u = mysqli_fetch_assoc($q_users)) $list_karyawan[] = $u;
    }
}

$jumlah_hari = (int)date('t', mktime(0, 0, 0, $bulan_int, 1, $tahun));
$periode_label = "01 - {$jumlah_hari} " . $nama_bulan[$bulan] . " {$tahun}";

// --- Pagination ---
$per_page = 10;
$page     = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$total_karyawan = count($list_karyawan);
$total_pages    = max(1, ceil($total_karyawan / $per_page));
$page           = min($page, $total_pages);
$offset         = ($page - 1) * $per_page;
$list_karyawan_page = array_slice($list_karyawan, $offset, $per_page);
?>
<style>
    @media print {
        @page {
            size: landscape;
            margin: 10mm;
        }

        body * {
            visibility: hidden;
        }

        .main-content {
            margin-left: 0 !important;
        }

        .sidebar,
        .topbar,
        .no-print {
            display: none !important;
        }

        .print-area,
        .print-area * {
            visibility: visible;
        }

        .print-area {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }

        .lk-card {
            border: none !important;
            box-shadow: none !important;
            margin: 0 !important;
        }

        .lk-header-main {
            border-bottom: 2px solid #000 !important;
            padding: 10px 0 !important;
        }

        .lk-table-wrap {
            overflow: visible !important;
            width: 100% !important;
        }

        .lk-table {
            font-size: 10px !important;
            width: 100% !important;
            page-break-inside: auto;
        }

        .lk-table tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        .lk-table th,
        .lk-table td {
            padding: 4px 6px !important;
        }
    }

    .lk-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .lk-filter-group {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
    }

    .lk-select,
    .lk-input {
        padding: 9px 14px;
        border: 1.5px solid #cbd5e1;
        border-radius: 9px;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-main);
        background: white;
        outline: none;
        cursor: pointer;
        font-family: inherit;
        transition: border-color .2s;
    }

    .lk-select:focus,
    .lk-input:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, .12);
    }

    .lk-input {
        min-width: 180px;
        cursor: text;
    }

    .btn-filter-go {
        padding: 9px 18px;
        background: var(--accent);
        color: white;
        border: none;
        border-radius: 9px;
        font-weight: 700;
        font-size: 13px;
        cursor: pointer;
        transition: background .2s;
    }

    .btn-filter-go:hover {
        background: #2563eb;
    }

    .btn-print-lk {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 9px 18px;
        background: white;
        color: var(--text-main);
        border: 1.5px solid #cbd5e1;
        border-radius: 9px;
        font-weight: 700;
        font-size: 13px;
        cursor: pointer;
        transition: all .2s;
    }

    .btn-print-lk:hover {
        background: #f8fafc;
        border-color: #94a3b8;
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

    .t1 {
        background: #fef3c7;
        color: #d97706;
    }

    .t2 {
        background: #f0fdf4;
        color: #16a34a;
    }

    .t3 {
        background: #fee2e2;
        color: #dc2626;
    }

    .t4 {
        background: #eff6ff;
        color: #3b82f6;
    }

    .t5 {
        background: #f5f3ff;
        color: #7c3aed;
    }

    /* Card */
    .lk-card {
        background: white;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 15px rgba(0, 0, 0, .02);
        overflow: hidden;
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
        color: var(--text-main);
        margin: 0;
    }

    .lk-subtitle {
        font-size: 13px;
        color: var(--text-muted);
        margin-top: 4px;
    }

    /* Tabel */
    .lk-table-wrap {
        width: 100%;
        overflow-x: auto;
    }

    .lk-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
        white-space: nowrap;
    }

    /* Header */
    .lk-table thead th {
        padding: 16px 20px;
        background: #064e3b;
        color: rgba(255, 255, 255, 0.90);
        font-size: 13px;
        font-weight: 600;
        text-align: center;
        border: none;
        letter-spacing: 0.2px;
    }

    .lk-table thead th.th-left {
        text-align: left;
    }

    /* Body rows */
    .lk-table tbody tr {
        border-bottom: 1px solid #f0f4f8;
        transition: background 0.12s;
    }

    .lk-table tbody tr:nth-child(even) {
        background: #f6fdf9;
    }

    .lk-table tbody tr:hover {
        background: #eef4fb !important;
        cursor: pointer;
    }

    .lk-table tbody td {
        padding: 18px 20px;
        border: none;
        vertical-align: middle;
        color: #334155;
        font-size: 14px;
    }

    /* Cell helpers */
    .td-no {
        color: #94a3b8;
        font-weight: 600;
        text-align: center;
        width: 50px;
    }

    .td-name {
        font-weight: 500;
        color: #334155;
    }

    .td-name a {
        color: #3b82f6;
        text-decoration: none;
        font-weight: 600;
    }

    .td-name a:hover {
        text-decoration: underline;
        color: #2563eb;
    }

    .td-center {
        text-align: center;
    }

    .td-num {
        text-align: center;
        font-weight: 700;
        font-size: 14px;
        color: #22c55e;
    }

    .td-num-plain {
        text-align: center;
        font-weight: 600;
        color: #334155;
    }

    .td-empty {
        color: #cbd5e1;
        text-align: center;
        font-style: italic;
    }

    .num-alpha-pos {
        color: #ef4444;
        font-weight: 700;
    }

    .num-alpha-zero {
        color: #22c55e;
        font-weight: 700;
    }

    .num-hadir {
        color: #334155;
        font-weight: 700;
    }

    /* tfoot */
    .lk-table tfoot td {
        padding: 14px 20px;
        background: #f8fafc;
        font-weight: 700;
        font-size: 13px;
        color: #475569;
        border-top: 2px solid #e2e8f0;
        text-align: center;
    }

    .lk-table tfoot td.td-left {
        text-align: left;
    }

    /* Tombol Aksi */
    .btn-detail-aksi {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border-radius: 7px;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        color: #64748b;
        cursor: pointer;
        text-decoration: none;
        transition: all .2s ease;
    }

    .btn-detail-aksi:hover {
        background: #e0e7ff;
        border-color: #a5b4fc;
        color: #4258ff;
        transform: scale(1.08);
    }

    /* Pagination */
    .lk-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        background: #fff;
        border-top: 1px solid #e2e8f0;
        font-size: 13px;
        color: #64748b;
    }

    .pagination-btns {
        display: flex;
        gap: 6px;
    }

    .pg-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border-radius: 7px;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #475569;
        font-weight: 600;
        font-size: 13px;
        cursor: pointer;
        text-decoration: none;
        transition: all .15s;
    }

    .pg-btn:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
    }

    .pg-btn.active {
        background: #064e3b;
        border-color: #064e3b;
        color: white;
    }

    .pg-btn.disabled {
        opacity: 0.35;
        pointer-events: none;
    }
</style>

<!-- Toolbar Filter -->
<div class="lk-toolbar no-print">
    <form method="GET" id="filterForm" class="lk-filter-group" onchange="this.submit()">
        <!-- Filter Afdeling -->
        <select name="afdeling" class="lk-select">
            <option value="">Semua Afdeling</option>
            <?php foreach ($list_afdeling as $afd): ?>
                <option value="<?= htmlspecialchars($afd) ?>" <?= $afdeling == $afd ? 'selected' : '' ?>><?= htmlspecialchars($afd) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="jabatan" class="lk-select" aria-label="Filter jabatan">
            <?php foreach ($daftar_jabatan as $nilai_jabatan => $label_jabatan): ?>
                <option value="<?= $nilai_jabatan ?>" <?= $jabatan === $nilai_jabatan ? 'selected' : '' ?>><?= $label_jabatan ?></option>
            <?php endforeach; ?>
        </select>
        <select name="bulan" class="lk-select">
            <?php foreach ($nama_bulan as $num => $nm): ?>
                <option value="<?= $num ?>" <?= $bulan == $num ? 'selected' : '' ?>><?= $nm ?></option>
            <?php endforeach; ?>
        </select>
        <select name="tahun" class="lk-select">
            <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                <option value="<?= $y ?>" <?= $tahun == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
        <select name="objek" class="lk-select" style="min-width:200px;">
            <?php foreach ($list_objek as $obj): ?>
                <option value="<?= htmlspecialchars($obj) ?>" <?= $objek == $obj ? 'selected' : '' ?>><?= htmlspecialchars($obj) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="lk-input-wrap" style="position:relative; display:inline-flex; align-items:center;">
            <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:12px; color:#94a3b8; font-size:13px; pointer-events:none;"></i>
            <input type="text" name="cari" class="lk-input" placeholder="Cari nama / NIK..." value="<?= htmlspecialchars($cari) ?>" style="padding-left:34px;">
        </div>
        <button type="submit" class="btn-filter-go" style="display: none;">Tampilkan</button>
    </form>
    <?php if ($bisa_tampil): ?>
        <button class="btn-print-lk" onclick="cetakLaporan()">
            <i class="fa-solid fa-print"></i>
            Cetak PDF
        </button>
    <?php else: ?>
        <button class="btn-print-lk" type="button" onclick="alert('Laporan belum dikirim oleh Kerani untuk periode ini.');" style="opacity:0.5; cursor:not-allowed;" title="Laporan belum diserahkan oleh Kerani">
            <i class="fa-solid fa-lock"></i>
            Cetak PDF (Terkunci)
        </button>
    <?php endif; ?>
</div>

<?php if (empty($afdeling)): ?>
    <!-- Status Rekap Seluruh Afdeling -->
    <div class="no-print" style="background: white; border-radius: 14px; padding: 18px 22px; border: 1px solid #e5e7eb; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
        <div style="font-size: 13px; font-weight: 700; color: #374151; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between;">
            <span><i class="fa-solid fa-list-check" style="color: #10b981;"></i> Status Pengajuan Laporan Keseluruhan Seluruh Afdeling (<?= $nama_bulan[$bulan] ?> <?= $tahun ?>):</span>
            <a href="validasi_laporan.php?bulan=<?= $bulan ?>&tahun=<?= $tahun ?>" style="font-size: 12px; color: #2563eb; text-decoration: none; font-weight: 600;">Buka Menu Validasi &rarr;</a>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <?php foreach ($list_afdeling as $afd_item): 
                $st_item = $status_per_afdeling[$afd_item] ?? null;
                $st_code = $st_item ? $st_item['status'] : 'draft';
            ?>
                <a href="lap_keseluruhan.php?bulan=<?= $bulan ?>&tahun=<?= $tahun ?>&afdeling=<?= urlencode($afd_item) ?>&objek=<?= urlencode($objek) ?>&jabatan=<?= urlencode($jabatan) ?>" style="text-decoration: none; display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; transition: all 0.15s;
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
                        Kerani Afdeling <?= htmlspecialchars($afdeling) ?> belum menyelesaikan proses cross-check hasil kerja dan belum mengirimkan laporan resmi periode <?= $nama_bulan[$bulan] ?> <?= $tahun ?>. Data di bawah masih berupa data berjalan dan <u>belum resmi</u> untuk diproses.
                    <?php elseif ($info_pengajuan['status'] === 'menunggu_validasi'): ?>
                        Dikirim pada: <strong><?= date('d/m/Y H:i', strtotime($info_pengajuan['tanggal_kirim'])) ?></strong>.
                        <?php if (!empty($info_pengajuan['catatan_kerani'])): ?>
                            | Catatan Kerani: <em>"<?= htmlspecialchars($info_pengajuan['catatan_kerani']) ?>"</em>
                        <?php endif; ?>
                        <br>Silakan periksa rekap kinerja di bawah ini, lalu pilih <strong>Setujui & Validasi</strong> atau <strong>Minta Revisi</strong>.
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
        <p class="lk-title">Laporan Absensi dan Hasil Kinerja <?= htmlspecialchars($daftar_jabatan[$jabatan]) ?> (<?= htmlspecialchars($objek) ?>)</p>
        <p class="lk-subtitle">
            Periode: <?= $periode_label ?> &nbsp;|&nbsp;
            Afdeling: <?= htmlspecialchars($afdeling ?: 'Semua') ?> &nbsp;|&nbsp;
            Jabatan: <?= htmlspecialchars($daftar_jabatan[$jabatan]) ?> &nbsp;|&nbsp;
            <span class="tipe-badge <?= strtolower($tipe) ?>"><?= $label_tipe[$tipe] ?></span>
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
                        Laporan kinerja dan hasil kerja Afdeling <strong><?= htmlspecialchars($afdeling) ?></strong> periode <strong><?= $nama_bulan[$bulan] ?> <?= $tahun ?></strong> sedang dalam proses revisi oleh Kerani.
                    <?php else: ?>
                        Data kinerja dan hasil kerja Afdeling <strong><?= htmlspecialchars($afdeling) ?></strong> periode <strong><?= $nama_bulan[$bulan] ?> <?= $tahun ?></strong> belum dapat ditampilkan karena Kerani belum menyerahkan laporan resmi untuk divalidasi.
                    <?php endif; ?>
                <?php else: ?>
                    Belum ada Kerani afdeling yang mengirimkan laporan keseluruhan resmi untuk periode <strong><?= $nama_bulan[$bulan] ?> <?= $tahun ?></strong>. Rekapitulasi akan muncul secara otomatis setelah laporan diajukan oleh masing-masing Kerani.
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

        <div class="lk-table-wrap">
        <table class="lk-table">
            <thead>
                <tr>
                    <?php
                    $label_hasil = [
                        'T1' => 'Total Hasil Langsir (Kg)',
                        'T2' => 'Hari Kerja Objek Ini',
                        'T3' => 'Total Hasil Panen (Kg)',
                        'T4' => 'Total Kutip Brondolan (Kg)',
                        'T5' => 'Total Muat TBS (Kg)',
                    ];
                    $has_prestasi = in_array($tipe, ['T1', 'T4']);
                    $colspan_empty = $has_prestasi ? 8 : 7;
                    ?>
                    <th class="td-no">No</th>
                    <th class="th-left" style="min-width:200px;">Nama Personel</th>
                    <th class="th-left">Afdeling</th>
                    <th>Total Kehadiran (Hari)</th>
                    <th>Total Alpa</th>
                    <th><?= $label_hasil[$tipe] ?? 'Total Hasil' ?></th>
                    <?php if ($has_prestasi): ?>
                        <th>Total Prestasi</th>
                    <?php endif; ?>
                    <th style="width:50px;"></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no_global = $offset + 1;
                $total_hadir_global  = 0;
                $total_alpha_global  = 0;
                $total_hasil_global  = 0;
                $total_prestasi_global = 0;

                // Hitung total semua halaman
                foreach ($list_karyawan as $_u) {
                    $_uid = $_u['id'];
                    $_q = mysqli_query($conn, "SELECT status_kehadiran FROM absensis WHERE user_id=$_uid AND MONTH(tanggal)=$bulan_int AND YEAR(tanggal)=$tahun");
                    while ($_a = mysqli_fetch_assoc($_q)) {
                        $_s = strtolower($_a['status_kehadiran']);
                        if (in_array($_s, ['hadir', 'tepat_waktu'])) $total_hadir_global++;
                        if (in_array($_s, ['alpha', 'alpa', 'alfa', 'terlambat'])) $total_alpha_global++;
                    }
                    $_qlb = mysqli_query($conn, "SELECT SUM(hasil_langsir_kg) as sl, SUM(hasil_ton) as st, SUM(hasil_kg) as sk, SUM(prestasi_kg) as sp FROM logbook_kinerja WHERE user_id=$_uid AND objek_kerja='$objek_safe' AND MONTH(tanggal)=$bulan_int AND YEAR(tanggal)=$tahun");
                    $_lb = $_qlb ? mysqli_fetch_assoc($_qlb) : null;
                    if ($_lb) {
                        if ($tipe === 'T1') {
                            $total_hasil_global += (float)($_lb['sl'] ?? 0);
                            $total_prestasi_global += (float)($_lb['sp'] ?? 0);
                        } elseif ($tipe === 'T3') $total_hasil_global += (float)($_lb['st'] ?? 0);
                        elseif (in_array($tipe, ['T4', 'T5'])) {
                            $total_hasil_global += (float)($_lb['sk'] ?? 0);
                            $total_prestasi_global += (float)($_lb['sp'] ?? 0);
                        }
                    }
                }

                foreach ($list_karyawan_page as $user):
                    $uid = $user['id'];

                    $q_abs = mysqli_query($conn, "SELECT status_kehadiran FROM absensis WHERE user_id=$uid AND MONTH(tanggal)=$bulan_int AND YEAR(tanggal)=$tahun");
                    $hadir_count = 0;
                    $alpha_count = 0;
                    while ($abs = mysqli_fetch_assoc($q_abs)) {
                        $s = strtolower($abs['status_kehadiran']);
                        if (in_array($s, ['hadir', 'tepat_waktu'])) $hadir_count++;
                        if (in_array($s, ['alpha', 'alpa', 'alfa', 'terlambat'])) $alpha_count++;
                    }

                    $q_lb = mysqli_query($conn, "SELECT COUNT(*) as hk_objek, SUM(hasil_langsir_kg) as sum_langsir, SUM(prestasi_kg) as sum_prestasi, SUM(hasil_kg) as sum_hasil_kg, SUM(hasil_ton) as sum_hasil_tbs_kg FROM logbook_kinerja WHERE user_id=$uid AND objek_kerja='$objek_safe' AND MONTH(tanggal)=$bulan_int AND YEAR(tanggal)=$tahun");
                    $lb = $q_lb ? mysqli_fetch_assoc($q_lb) : null;
                    $has_data = $lb && $lb['hk_objek'] > 0;

                    if ($tipe === 'T1') {
                        $nilai_hasil = $has_data ? (float)($lb['sum_langsir'] ?? 0) : null;
                        $nilai_prestasi = $has_data ? (float)($lb['sum_prestasi'] ?? 0) : null;
                    } elseif ($tipe === 'T2') {
                        $nilai_hasil = $has_data ? (int)($lb['hk_objek'] ?? 0) : null;
                        $nilai_prestasi = null;
                    } elseif ($tipe === 'T3') {
                        $nilai_hasil = $has_data ? (float)($lb['sum_hasil_tbs_kg'] ?? 0) : null;
                        $nilai_prestasi = null;
                    } elseif ($tipe === 'T4') {
                        $nilai_hasil = $has_data ? (float)($lb['sum_hasil_kg'] ?? 0) : null;
                        $nilai_prestasi = $has_data ? (float)($lb['sum_prestasi'] ?? 0) : null;
                    } elseif ($tipe === 'T5') {
                        $nilai_hasil = $has_data ? (float)($lb['sum_hasil_kg'] ?? 0) : null;
                        $nilai_prestasi = null;
                    } else {
                        $nilai_hasil = null;
                        $nilai_prestasi = null;
                    }

                    $row_class = ($hadir_count == 0 && $alpha_count > 0) ? 'absent-row' : '';
                ?>
                    <tr class="<?= $row_class ?>">
                        <td class="td-no"><?= $no_global++ ?></td>
                        <td class="td-name">
                            <a href="laporan_individu.php?user_id=<?= $uid ?>&bulan=<?= $bulan ?>&tahun=<?= $tahun ?>&objek=<?= urlencode($objek) ?>">
                                <?= htmlspecialchars($user['name']) ?>
                            </a>
                        </td>
                        <td class="td-center" style="font-size:12px; color:#64748b;">
                            <?= htmlspecialchars($user['afdeling'] ?? '-') ?>
                        </td>
                        <td class="td-center">
                            <span class="num-hadir"><?= $hadir_count ?></span>
                        </td>
                        <td class="td-center">
                            <?php if ($alpha_count > 0): ?>
                                <span class="num-alpha-pos"><?= $alpha_count ?></span>
                            <?php else: ?>
                                <span class="num-alpha-zero">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="td-center">
                            <?php if ($nilai_hasil !== null): ?>
                                <span class="td-num"><?= $tipe === 'T2' ? $nilai_hasil . ' Hari' : number_format($nilai_hasil, 2) ?></span>
                            <?php else: ?>
                                <span class="td-empty">—</span>
                            <?php endif; ?>
                        </td>
                        <?php if ($has_prestasi): ?>
                            <td class="td-center td-num-plain">
                                <?= $nilai_prestasi !== null ? number_format($nilai_prestasi, 2) : '<span class="td-empty">—</span>' ?>
                            </td>
                        <?php endif; ?>
                        <td class="td-center">
                            <a href="laporan_individu.php?user_id=<?= $uid ?>&bulan=<?= $bulan ?>&tahun=<?= $tahun ?>&objek=<?= urlencode($objek) ?>" class="btn-detail-aksi" title="Lihat rincian harian <?= htmlspecialchars($user['name']) ?>" aria-label="Lihat rincian <?= htmlspecialchars($user['name']) ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="7" height="7"></rect>
                                    <rect x="14" y="3" width="7" height="7"></rect>
                                    <rect x="14" y="14" width="7" height="7"></rect>
                                    <rect x="3" y="14" width="7" height="7"></rect>
                                </svg>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($list_karyawan)): ?>
                    <tr>
                        <?php for($i=0; $i< $colspan_empty ; $i++): ?><td class="td-empty">-</td><?php endfor; ?>
                    </tr>
                <?php endif; ?>
            </tbody>
            <?php if (!empty($list_karyawan)): ?>
                <tfoot>
                    <tr>
                        <td colspan="3" class="td-left" style="font-weight:800;">TOTAL KESELURUHAN</td>
                        <td><?= $total_hadir_global ?></td>
                        <td><?= $total_alpha_global ?></td>
                        <td><?= $tipe === 'T2' ? '-' : number_format($total_hasil_global, 2) ?></td>
                        <?php if ($has_prestasi): ?><td><?= number_format($total_prestasi_global, 2) ?></td><?php endif; ?>
                        <td></td>
                    </tr>
                </tfoot>
            <?php endif; ?>
        </table>
    </div>

    <!-- Pagination -->
    <div class="lk-pagination">
        <span>Menampilkan <strong><?= $offset + 1 ?> - <?= min($offset + $per_page, $total_karyawan) ?></strong> dari <strong><?= $total_karyawan ?></strong> karyawan</span>
        <?php
        $q_params = $_GET;
        $q_params['bulan']  = $bulan;
        $q_params['tahun']  = $tahun;
        $q_params['objek']  = $objek;
        if (isset($q_params['page'])) unset($q_params['page']);
        $base_url = 'lap_keseluruhan.php?' . http_build_query($q_params) . '&page=';
        ?>
        <div class="pagination-btns">
            <a class="pg-btn <?= $page <= 1 ? 'disabled' : '' ?>" href="<?= $base_url . max(1, $page - 1) ?>"><i class="fa-solid fa-chevron-left" style="font-size:11px;"></i></a>
            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                <a class="pg-btn <?= $p == $page ? 'active' : '' ?>" href="<?= $base_url . $p ?>"><?= $p ?></a>
            <?php endfor; ?>
            <a class="pg-btn <?= $page >= $total_pages ? 'disabled' : '' ?>" href="<?= $base_url . min($total_pages, $page + 1) ?>"><i class="fa-solid fa-chevron-right" style="font-size:11px;"></i></a>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
    function cetakLaporan() {
        const tableHTML = document.querySelector('.lk-table-wrap').innerHTML;
        const win = window.open('', '_blank', 'width=1100,height=800');
        win.document.write(`<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Cetak Laporan Keseluruhan</title>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: 'Times New Roman', Times, serif; color: #000; padding: 10mm; }
  .kop { display:flex; align-items:center; border-bottom:3px solid #000; padding-bottom:10px; margin-bottom:15px; }
  .kop img { width:70px; margin-right:15px; }
  .kop-text { flex:1; text-align:center; }
  .kop-text h1 { font-size:18pt; font-weight:bold; text-transform:uppercase; margin:0; }
  .kop-text p { font-size:11pt; margin:3px 0 0 0; }
  .info-laporan { text-align: center; margin-bottom: 20px; }
  .info-laporan h2 { font-size: 14pt; margin-bottom: 5px; text-decoration: underline; text-transform: uppercase; }
  .info-laporan p { font-size: 11pt; }
  table { width:100%; border-collapse:collapse; margin-bottom:20px; font-size: 10pt; }
  th, td { border:1px solid #000; padding:6px 8px; }
  th { background:#f0f0f0 !important; font-weight:bold; text-align:center; text-transform:uppercase; }
  .td-date, .td-no, .td-center { text-align: center; }
  .td-num { text-align: right; }
  .footer-ttd { display:flex; justify-content:space-between; margin-top:40px; text-align:center; font-size:11pt; }
  .ttd-col { flex:1; }
  .ttd-col p { margin-bottom:60px; }
  .ttd-line { border-top:1px solid #000; padding-top:5px; font-weight:bold; display:inline-block; min-width:150px; }
  @page { size: A4 landscape; margin: 10mm; }
</style>
</head>
<body>
  <div class="kop">
    <img src="../assets/img/logo.png" onerror="this.style.display='none'" alt="">
    <div class="kop-text">
      <h1>PT Damai Jaya Lestari</h1>
      <p>Perkebunan Kelapa Sawit &amp; Pabrik Minyak Kelapa Sawit</p>
    </div>
  </div>
  <div class="info-laporan">
    <h2>LAPORAN ABSENSI DAN HASIL KINERJA <?= htmlspecialchars(strtoupper($daftar_jabatan[$jabatan])) ?> (<?= htmlspecialchars($objek) ?>)</h2>
    <p>Periode: <?= $periode_label ?> &nbsp;|&nbsp; Afdeling: <?= htmlspecialchars($afdeling ?: 'Semua') ?> &nbsp;|&nbsp; Jabatan: <?= htmlspecialchars($daftar_jabatan[$jabatan]) ?></p>
  </div>
  ${tableHTML}
  <div class="footer-ttd">
    <div class="ttd-col">
      <p>Diketahui oleh,</p>
      <div class="ttd-line"><?= htmlspecialchars($nama_pengawas) ?></div>
      <div style="font-weight:bold; margin-top:4px;">Pengawas <?= $teks_afdeling ?></div>
    </div>
    <div class="ttd-col">
      <p>Disusun oleh,</p>
      <div class="ttd-line"><?= htmlspecialchars($nama_kerani) ?></div>
      <div style="font-weight:bold; margin-top:4px;">Kerani <?= $teks_afdeling ?></div>
    </div>
  </div>
</body>
</html>`);
        win.document.close();
        win.focus();
        setTimeout(() => {
            win.print();
        }, 500);
    }
</script>

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
        title.innerText = 'Validasi & Setujui Laporan Keseluruhan';
        title.style.color = '#059669';
        desc.innerText = 'Apakah Anda yakin ingin menyetujui dan memvalidasi laporan keseluruhan objek kerja ini untuk dasar proses penggajian?';
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