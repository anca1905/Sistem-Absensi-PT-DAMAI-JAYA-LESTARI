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

$list_blok = [
    'H.39' => '8.66',
    'H.40' => '0.91',
    'I.39' => '29.26',
    'I.40' => '26.18',
    'J.39' => '31.01',
    'J.40' => '27.05',
    'K.39' => '20.98',
    'K.40' => '28.52',
    'L.39' => '31.17',
    'L.40' => '17.74'
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
$objek     = isset($_GET['objek'])      ? $_GET['objek']        : 'Langsir manual';
$cari      = isset($_GET['cari'])       ? trim($_GET['cari'])   : '';

if (!in_array($objek, $list_objek)) $objek = 'Langsir manual';
$tipe = getTableType($objek);

$objek_safe   = mysqli_real_escape_string($conn, $objek);
$bulan_int    = (int)$bulan;
$afdeling_kerani = isset($_SESSION['afdeling']) ? mysqli_real_escape_string($conn, $_SESSION['afdeling']) : '';

// --- Ambil Semua Karyawan ---
$where_karyawan = "role='karyawan'";
if (!empty($afdeling_kerani)) $where_karyawan .= " AND afdeling='$afdeling_kerani'";
if (!empty($cari)) {
    $cari_safe = mysqli_real_escape_string($conn, $cari);
    $where_karyawan .= " AND (name LIKE '%$cari_safe%' OR nik LIKE '%$cari_safe%')";
}
$q_users = mysqli_query($conn, "SELECT id, nik, name FROM users WHERE $where_karyawan ORDER BY name ASC");
$list_karyawan = [];
while ($u = mysqli_fetch_assoc($q_users)) $list_karyawan[] = $u;

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

        /* Mencegah tabel terpotong saat print */
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
        background: #1a3a5c;
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

    /* Angka alpha: merah jika >0, hijau jika 0 */
    .num-alpha-pos {
        color: #ef4444;
        font-weight: 700;
    }

    .num-alpha-zero {
        color: #22c55e;
        font-weight: 700;
    }

    /* Angka kehadiran */
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
        background: #1a3a5c;
        border-color: #1a3a5c;
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
    <button class="btn-print-lk" onclick="cetakLaporan()">
        <i class="fa-solid fa-print"></i>
        Cetak PDF
    </button>
</div>

<!-- Card Laporan -->
<div class="lk-card print-area">
    <div class="lk-header-main">
        <p class="lk-title">Laporan Absensi dan Hasil Kinerja (<?= htmlspecialchars($objek) ?>)</p>
        <p class="lk-subtitle">
            Periode: <?= $periode_label ?> &nbsp;|&nbsp;
            Afdeling: <?= htmlspecialchars($afdeling_kerani ?: 'Semua') ?> &nbsp;|&nbsp;
            <span class="tipe-badge <?= strtolower($tipe) ?>"><?= $label_tipe[$tipe] ?></span>
        </p>
    </div>

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
                    $has_prestasi = false; // in_array($tipe, ['T1','T4']);
                    $colspan_empty = $has_prestasi ? 7 : 6;
                    ?>
                    <th class="td-no">No</th>
                    <th class="th-left" style="min-width:200px;">Nama Karyawan</th>
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

                // Hitung total semua halaman (loop semua karyawan)
                foreach ($list_karyawan as $_u) {
                    $_uid = $_u['id'];
                    $_q = mysqli_query($conn, "SELECT status_kehadiran FROM absensis WHERE user_id=$_uid AND MONTH(tanggal)=$bulan_int AND YEAR(tanggal)=$tahun");
                    while ($_a = mysqli_fetch_assoc($_q)) {
                        $_s = strtolower($_a['status_kehadiran']);
                        if (!in_array($_s, ['alpha', 'izin', 'sakit', 'cuti'])) $total_hadir_global++;
                        if ($_s === 'alpha') $total_alpha_global++;
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

                    // Kehadiran & Alpha
                    $q_abs = mysqli_query($conn, "SELECT status_kehadiran FROM absensis WHERE user_id=$uid AND MONTH(tanggal)=$bulan_int AND YEAR(tanggal)=$tahun");
                    $hadir_count = 0;
                    $alpha_count = 0;
                    while ($abs = mysqli_fetch_assoc($q_abs)) {
                        $s = strtolower($abs['status_kehadiran']);
                        if (!in_array($s, ['alpha', 'izin', 'sakit', 'cuti'])) $hadir_count++;
                        if ($s === 'alpha') $alpha_count++;
                    }

                    // Logbook
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
                            <a href="laporan_individu.php?user_id=<?= $uid ?>&bulan=<?= $bulan ?>&tahun=<?= $tahun ?>&objek=<?= urlencode($objek) ?>" class="btn-detail-aksi" title="Detail <?= htmlspecialchars($user['name']) ?>">
                                <i class="fa-solid fa-table-cells" style="font-size:14px;"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($list_karyawan)): ?>
                    <tr>
                        <td colspan="<?= $colspan_empty ?>" style="text-align:center;padding:50px;color:#94a3b8;font-size:14px;">Belum ada data karyawan untuk filter ini.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
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
        $base_url = 'laporan_keseluruhan.php?' . http_build_query($q_params) . '&page=';
        ?>
        <div class="pagination-btns">
            <a class="pg-btn <?= $page <= 1 ? 'disabled' : '' ?>" href="<?= $base_url . max(1, $page - 1) ?>"><i class="fa-solid fa-chevron-left" style="font-size:11px;"></i></a>
            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                <a class="pg-btn <?= $p == $page ? 'active' : '' ?>" href="<?= $base_url . $p ?>"><?= $p ?></a>
            <?php endfor; ?>
            <a class="pg-btn <?= $page >= $total_pages ? 'disabled' : '' ?>" href="<?= $base_url . min($total_pages, $page + 1) ?>"><i class="fa-solid fa-chevron-right" style="font-size:11px;"></i></a>
        </div>
    </div>
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
  
  /* Reset badge styling for print to just text */
  span[class^="badge-"] { font-weight: bold; color: #000 !important; background: transparent !important; padding: 0 !important; }
  .absent-row td { color: #555; }
  
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
      <p>Perkebunan Kelapa Sawit & Pabrik Minyak Kelapa Sawit</p>
    </div>
  </div>
  
  <div class="info-laporan">
    <h2>LAPORAN ABSENSI DAN HASIL KINERJA (<?= htmlspecialchars($objek) ?>)</h2>
    <p>Periode: <?= $periode_label ?> &nbsp;|&nbsp; Afdeling: <?= htmlspecialchars($afdeling_kerani ?: 'Semua') ?></p>
  </div>

  ${tableHTML}
  
  <div class="footer-ttd">
    <div class="ttd-col">
      <p>Dibuat Oleh,</p>
      <div class="ttd-line">Kerani Afdeling</div>
    </div>
    <div class="ttd-col">
      <p>Disetujui Oleh,</p>
      <div class="ttd-line">Askep / Manajer</div>
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

<?php include 'templates/footer.php'; ?>