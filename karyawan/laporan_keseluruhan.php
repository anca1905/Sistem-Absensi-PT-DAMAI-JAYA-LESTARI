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
function getTableType($objek) {
    if ($objek === 'Panen') return 'T3';
    if (in_array($objek, ['Penunasan', 'Racun piringan', 'Perawatan'])) return 'T2';
    if (in_array($objek, ['Muat TBS ke truk', 'Muat TBS ke jonder'])) return 'T5';
    return 'T2';
}

// Label tipe tabel
$label_tipe = [
    'T1' => 'Langsir', 'T2' => 'Pemeliharaan',
    'T3' => 'Panen', 'T4' => 'Kutip Brondolan', 'T5' => 'Muat TBS'
];

// Nama bulan
$nama_bulan = [
    '01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April',
    '05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus',
    '09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'
];

// --- Filter ---
$bulan     = isset($_GET['bulan'])      ? str_pad($_GET['bulan'], 2, '0', STR_PAD_LEFT) : date('m');
$tahun     = isset($_GET['tahun'])      ? (int)$_GET['tahun']  : (int)date('Y');
$objek     = isset($_GET['objek'])      ? $_GET['objek']        : 'Langsir manual';

if (!in_array($objek, $list_objek)) $objek = 'Langsir manual';
$tipe = getTableType($objek);

$objek_safe   = mysqli_real_escape_string($conn, $objek);
$bulan_int    = (int)$bulan;
$uid          = $_SESSION['user_id'];
$nama_karyawan = htmlspecialchars($_SESSION['nama'] ?? 'Karyawan');

$jumlah_hari = cal_days_in_month(CAL_GREGORIAN, $bulan_int, $tahun);
$periode_label = "01 - {$jumlah_hari} " . $nama_bulan[$bulan] . " {$tahun}";
?>
<style>
    @media print {
        @page { size: landscape; margin: 10mm; }
        body * { visibility: hidden; }
        .main-content { margin-left: 0 !important; }
        .mobile-header, .no-print, .btn-back { display: none !important; }
        .print-area, .print-area * { visibility: visible; }
        .print-area { position: absolute; left: 0; top: 0; width: 100%; }
        .lk-card { border: none !important; box-shadow: none !important; margin:0 !important; }
        .lk-header-main { border-bottom: 2px solid #000 !important; padding: 10px 0 !important; }
        
        /* Mencegah tabel terpotong saat print */
        .lk-table-wrap { overflow: visible !important; width: 100% !important; }
        .lk-table { font-size: 10px !important; width: 100% !important; page-break-inside: auto; }
        .lk-table tr { page-break-inside: avoid; page-break-after: auto; }
        .lk-table th, .lk-table td { padding: 4px 6px !important; }
    }

    .lk-toolbar {
        display: flex; align-items: center; justify-content: space-between;
        gap: 12px; margin-bottom: 20px; flex-wrap: wrap;
    }
    .lk-filter-group { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
    .lk-select {
        padding: 9px 14px; border: 1.5px solid #cbd5e1; border-radius: 9px;
        font-size: 13px; font-weight: 600; color: var(--text-dark);
        background: white; outline: none; cursor: pointer; font-family: inherit;
        transition: border-color .2s;
    }
    .lk-select:focus { border-color: var(--primary-start); box-shadow: 0 0 0 3px rgba(59,130,246,.12); }
    .btn-filter-go {
        padding: 9px 18px; background: var(--primary-start); color: white;
        border: none; border-radius: 9px; font-weight: 700; font-size: 13px;
        cursor: pointer; transition: background .2s;
    }
    .btn-filter-go:hover { background: var(--primary-end); }
    .btn-print-lk {
        display: flex; align-items: center; gap: 8px;
        padding: 9px 18px; background: white; color: var(--text-dark);
        border: 1.5px solid #cbd5e1; border-radius: 9px; font-weight: 700;
        font-size: 13px; cursor: pointer; transition: all .2s;
    }
    .btn-print-lk:hover { background: #f8fafc; border-color: #94a3b8; }

    /* Tipe badge */
    .tipe-badge {
        display: inline-block; padding: 3px 10px; border-radius: 20px;
        font-size: 11px; font-weight: 700; letter-spacing: .5px;
    }
    .t1 { background:#fef3c7; color:#d97706; }
    .t2 { background:#f0fdf4; color:#16a34a; }
    .t3 { background:#fee2e2; color:#dc2626; }
    .t4 { background:#eff6ff; color:#3b82f6; }
    .t5 { background:#f5f3ff; color:#7c3aed; }

    /* Card */
    .lk-card {
        background: white; border-radius: 14px; border: 1px solid #e2e8f0;
        box-shadow: 0 4px 15px rgba(0,0,0,.02); overflow: hidden;
        margin-bottom: 30px;
    }
    .lk-header-main {
        padding: 20px 24px; border-bottom: 1.5px solid #e2e8f0;
        background: #f8fafc; text-align: center;
    }
    .lk-title { font-size: 17px; font-weight: 800; color: var(--text-dark); margin: 0; }
    .lk-subtitle { font-size: 13px; color: var(--text-muted); margin-top: 4px; }

    /* Tabel */
    .lk-table-wrap { width: 100%; overflow-x: auto; }
    .lk-table {
        width: 100%; border-collapse: collapse; font-size: 12.5px; white-space: nowrap;
    }
    .lk-table th, .lk-table td {
        padding: 11px 12px; border: 1px solid #e2e8f0; vertical-align: middle;
    }
    .lk-table thead tr:first-child th {
        background: #1e293b; color: white; font-weight: 700;
        text-transform: uppercase; font-size: 11px; letter-spacing: .5px;
    }
    .lk-table thead tr:nth-child(2) th {
        background: #334155; color: #cbd5e1; font-weight: 700;
        font-size: 10.5px; text-transform: uppercase;
    }
    .lk-table tbody tr:hover { background: #f8fafc; }
    .lk-table tbody tr.absent-row { background: #fff5f5; }
    .lk-table tbody tr.absent-row:hover { background: #fee2e2; }

    .th-o1 { background: #0f4c81 !important; }
    .th-o2 { background: #166534 !important; }

    .td-date { font-weight: 700; text-align: center; color: var(--text-dark); }
    .td-center { text-align: center; }
    .td-num { text-align: right; font-weight: 700; color: #1e293b; }
    .td-empty { color: #cbd5e1; text-align: center; font-style: italic; font-size: 11px; }

    /* Status badge kehadiran */
    .badge-hadir  { display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:20px;background:#dcfce7;color:#166534;font-weight:800;font-size:11px; }
    .badge-sakit  { padding:4px 10px;border-radius:20px;background:#ede9fe;color:#5b21b6;font-weight:800;font-size:11px;display:inline-block; }
    .badge-izin   { padding:4px 10px;border-radius:20px;background:#e0f2fe;color:#075985;font-weight:800;font-size:11px;display:inline-block; }
    .badge-alpha  { padding:4px 10px;border-radius:20px;background:#fee2e2;color:#991b1b;font-weight:800;font-size:11px;display:inline-block; }
    .badge-cuti   { padding:4px 10px;border-radius:20px;background:#ffedd5;color:#9a3412;font-weight:800;font-size:11px;display:inline-block; }
    .badge-none   { padding:4px 10px;border-radius:20px;background:#f1f5f9;color:#94a3b8;font-weight:700;font-size:11px;display:inline-block; }

    .lk-footer-info {
        padding: 14px 24px; border-top: 1px solid #e2e8f0;
        display: flex; justify-content: space-between; align-items: center;
        background: #f8fafc; font-size: 12px; color: var(--text-muted); font-weight: 600;
        flex-wrap: wrap; gap: 8px;
    }

    /* Summary row */
    .lk-table tfoot td {
        background: #1e293b; color: white; font-weight: 800; font-size: 12px; border-color: #334155;
    }

    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #64748b;
        text-decoration: none;
        font-weight: 700;
        font-size: 14px;
        margin-bottom: 20px;
        background: white;
        padding: 8px 16px;
        border-radius: 20px;
        border: 1px solid #e2e8f0;
    }
</style>

<div class="animate-up">
    <a href="index.php" class="btn-back">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        Kembali
    </a>
    
    <h1 class="page-title" style="text-align: left;">Laporan Keseluruhan</h1>

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
            <button type="submit" class="btn-filter-go" style="display: none;">Tampilkan</button>
        </form>
        <button class="btn-print-lk" onclick="cetakLaporan()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            Cetak PDF
        </button>
    </div>

    <!-- Card Laporan -->
    <div class="lk-card print-area">
        <div class="lk-header-main">
            <p class="lk-title">Laporan Absensi dan Hasil Kinerja</p>
            <p class="lk-subtitle">
                Objek: <?= htmlspecialchars($objek) ?> &nbsp;|&nbsp;
                Periode: <?= $periode_label ?> &nbsp;|&nbsp;
                <span class="tipe-badge <?= strtolower($tipe) ?>"><?= $label_tipe[$tipe] ?></span>
            </p>
        </div>

        <div class="lk-table-wrap">
            <table class="lk-table">
                <thead>
                    <!-- Baris 1: Group header -->
                    <tr>
                        <th rowspan="2" width="60">TANGGAL</th>
                        <!-- O1 -->
                        <th colspan="1" class="th-o1">KEHADIRAN</th>
                        <!-- berbeda setiap tipe -->
                        <?php if ($tipe === 'T1'): ?>
                            <th colspan="5" class="th-o2">HASIL KERJA (LANGSIR)</th>
                        <?php elseif ($tipe === 'T2'): ?>
                            <th colspan="3" class="th-o2">DATA KERJA</th>
                        <?php elseif ($tipe === 'T3'): ?>
                            <th colspan="6" class="th-o2">HASIL PANEN</th>
                        <?php elseif ($tipe === 'T4'): ?>
                            <th colspan="5" class="th-o2">HASIL KUTIP BRONDOLAN</th>
                        <?php elseif ($tipe === 'T5'): ?>
                            <th colspan="4" class="th-o2">HASIL MUAT TBS</th>
                        <?php endif; ?>
                    </tr>
                    <!-- Baris 2: Detail kolom -->
                    <tr>
                        <!-- O1 detail -->
                        <th class="th-o1">STATUS</th>
                        <!-- O2 detail -->
                        <?php if ($tipe === 'T1'): ?>
                            <th class="th-o2">NAMA MANDOR</th>
                            <th class="th-o2">HASIL LANGSIR (kg)</th>
                            <th class="th-o2">PRESTASI (kg)</th>
                            <th class="th-o2">BLOK</th>
                            <th class="th-o2">LUAS (Ha)</th>
                        <?php elseif ($tipe === 'T2'): ?>
                            <th class="th-o2">NAMA MANDOR</th>
                            <th class="th-o2">BLOK</th>
                            <th class="th-o2">LUAS (Ha)</th>
                        <?php elseif ($tipe === 'T3'): ?>
                            <th class="th-o2">NAMA MANDOR</th>
                            
                            <th class="th-o2">TS</th>
                            <th class="th-o2">TBS</th>
                            <th class="th-o2">TOTAL TANDAN</th>
                            <th class="th-o2">BLOK</th>
                            <th class="th-o2">LUAS (Ha)</th>
                        <?php elseif ($tipe === 'T4'): ?>
                            <th class="th-o2">NAMA MANDOR</th>
                            <th class="th-o2">HASIL (kg)</th>
                            <th class="th-o2">PRESTASI (kg)</th>
                            <th class="th-o2">BLOK</th>
                            <th class="th-o2">LUAS (Ha)</th>
                        <?php elseif ($tipe === 'T5'): ?>
                            <th class="th-o2">NAMA MANDOR</th>
                            <th class="th-o2">HASIL (kg)</th>
                            <th class="th-o2">BLOK</th>
                            <th class="th-o2">LUAS (Ha)</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php
                $total_hadir = 0;
                // Totals untuk footer
                $sum_langsir=0; $sum_prestasi=0; $sum_hasil=0; $sum_tbs_kg=0;
                $sum_ts=0; $sum_tbs=0; $sum_total_tandan=0;

                for ($d = 1; $d <= $jumlah_hari; $d++):
                    $tanggal_loop = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
                    
                    // --- O1: Kehadiran ---
                    $q_abs = mysqli_query($conn, "SELECT status_kehadiran FROM absensis WHERE user_id=$uid AND tanggal='$tanggal_loop' LIMIT 1");
                    $abs = $q_abs ? mysqli_fetch_assoc($q_abs) : null;
                    
                    if ($abs) {
                        $s = strtolower($abs['status_kehadiran']);
                        if ($s == 'hadir') {
                            $badge_class = 'badge-hadir'; $badge_text = 'Hadir';
                            $total_hadir++;
                        } elseif ($s == 'sakit') {
                            $badge_class = 'badge-sakit'; $badge_text = 'Sakit';
                        } elseif ($s == 'izin') {
                            $badge_class = 'badge-izin'; $badge_text = 'Izin';
                        } elseif ($s == 'cuti') {
                            $badge_class = 'badge-cuti'; $badge_text = 'Cuti';
                        } elseif ($s == 'alpha') {
                            $badge_class = 'badge-alpha'; $badge_text = 'Alpha';
                        } else {
                            $badge_class = 'badge-none'; $badge_text = '—';
                        }
                    } else {
                        $badge_class = 'badge-none'; $badge_text = '—';
                    }

                    // --- O2: Logbook kinerja untuk objek ini ---
                    $q_lb = mysqli_query($conn, "
                        SELECT lk.*, m.name AS nama_mandor
                        FROM logbook_kinerja lk
                        LEFT JOIN users m ON lk.mandor_id = m.id
                        WHERE lk.user_id = $uid
                          AND lk.objek_kerja = '$objek_safe'
                          AND lk.tanggal = '$tanggal_loop'
                        LIMIT 1
                    ");
                    $lb = $q_lb ? mysqli_fetch_assoc($q_lb) : null;
                    $has_data = $lb != null;

                    // Akumulasi totals
                    if ($has_data) {
                        $sum_langsir      += (float)($lb['hasil_langsir_kg'] ?? 0);
                        $sum_prestasi     += (float)($lb['prestasi_kg'] ?? 0);
                        $sum_hasil        += (float)($lb['hasil_kg'] ?? 0);
                        $sum_tbs_kg       += (float)($lb['hasil_ton'] ?? 0);
                        $sum_ts           += (int)($lb['tandan_kosong'] ?? 0);
                        $sum_tbs          += (int)($lb['tbs'] ?? 0);
                        $sum_total_tandan += (int)($lb['total_tandan'] ?? 0);
                    }

                    $row_class = ($badge_text == 'Alpha' || $badge_text == '—') ? 'absent-row' : '';
                ?>
                    <tr class="<?= $row_class ?>">
                        <td class="td-date"><?= str_pad($d, 2, '0', STR_PAD_LEFT) ?> <?= $nama_bulan[$bulan] ?></td>

                        <!-- O1 -->
                        <td class="td-center"><span class="<?= $badge_class ?>"><?= $badge_text ?></span></td>

                        <!-- Tipe T1: Langsir -->
                        <?php if ($tipe === 'T1'): ?>
                            <?php if ($has_data): ?>
                                <td><?= htmlspecialchars($lb['nama_mandor'] ?? '—') ?></td>
                                <td class="td-num"><?= number_format($lb['hasil_langsir_kg'] ?? 0, 2) ?></td>
                                <td class="td-num"><?= number_format($lb['prestasi_kg'] ?? 0, 2) ?></td>
                                <td class="td-center"><?= htmlspecialchars($lb['blok'] ?? '—') ?></td>
                                <td class="td-center"><?= htmlspecialchars($lb['luas_ha'] ?? '—') ?></td>
                            <?php else: ?>
                                <td class="td-empty" colspan="5">—</td>
                            <?php endif; ?>

                        <!-- Tipe T2: Perawatan -->
                        <?php elseif ($tipe === 'T2'): ?>
                            <?php if ($has_data): ?>
                                <td><?= htmlspecialchars($lb['nama_mandor'] ?? '—') ?></td>
                                <td class="td-center"><?= htmlspecialchars($lb['blok'] ?? '—') ?></td>
                                <td class="td-center"><?= htmlspecialchars($lb['luas_ha'] ?? '—') ?></td>
                            <?php else: ?>
                                <td class="td-empty" colspan="3">—</td>
                            <?php endif; ?>

                        <!-- Tipe T3: Panen / Potong Buah -->
                        <?php elseif ($tipe === 'T3'): ?>
                            <?php if ($has_data): ?>
                                <td><?= htmlspecialchars($lb['nama_mandor'] ?? '—') ?></td>
                                                                <td class="td-num"><?= number_format($lb['tandan_kosong'] ?? 0, 0) ?></td>
                                <td class="td-num"><?= number_format($lb['tbs'] ?? 0, 0) ?></td>
                                <td class="td-num"><?= number_format($lb['total_tandan'] ?? 0, 0) ?></td>
                                <td class="td-center"><?= htmlspecialchars($lb['blok'] ?? '—') ?></td>
                                <td class="td-center"><?= htmlspecialchars($lb['luas_ha'] ?? '—') ?></td>
                            <?php else: ?>
                                <td class="td-empty" colspan="6">—</td>
                            <?php endif; ?>

                        <!-- Tipe T4: Kutip Brondolan -->
                        <?php elseif ($tipe === 'T4'): ?>
                            <?php if ($has_data): ?>
                                <td><?= htmlspecialchars($lb['nama_mandor'] ?? '—') ?></td>
                                <td class="td-num"><?= number_format($lb['hasil_kg'] ?? 0, 2) ?></td>
                                <td class="td-num"><?= number_format($lb['prestasi_kg'] ?? 0, 2) ?></td>
                                <td class="td-center"><?= htmlspecialchars($lb['blok'] ?? '—') ?></td>
                                <td class="td-center"><?= htmlspecialchars($lb['luas_ha'] ?? '—') ?></td>
                            <?php else: ?>
                                <td class="td-empty" colspan="5">—</td>
                            <?php endif; ?>

                        <!-- Tipe T5: Muat TBS -->
                        <?php elseif ($tipe === 'T5'): ?>
                            <?php if ($has_data): ?>
                                <td><?= htmlspecialchars($lb['nama_mandor'] ?? '—') ?></td>
                                <td class="td-num"><?= number_format($lb['hasil_kg'] ?? 0, 0) ?></td>
                                <td class="td-center"><?= htmlspecialchars($lb['blok'] ?? '—') ?></td>
                                <td class="td-center"><?= htmlspecialchars($lb['luas_ha'] ?? '—') ?></td>
                            <?php else: ?>
                                <td class="td-empty" colspan="4">—</td>
                            <?php endif; ?>
                        <?php endif; ?>
                    </tr>
                <?php endfor; ?>
                </tbody>

                <!-- Footer / Total -->
                <tfoot>
                    <tr>
                        <td style="text-align:right;">TOTAL HADIR</td>
                        <td class="td-center"><?= $total_hadir ?> hr</td>
                        <?php if ($tipe === 'T1'): ?>
                            <td>—</td>
                            <td style="text-align:right;"><?= number_format($sum_langsir, 2) ?></td>
                            <td style="text-align:right;"><?= number_format($sum_prestasi, 2) ?></td>
                            <td>—</td><td>—</td>
                        <?php elseif ($tipe === 'T2'): ?>
                            <td>—</td><td>—</td><td>—</td>
                        <?php elseif ($tipe === 'T3'): ?>
                            <td>—</td>
                                                        <td style="text-align:right;"><?= number_format($sum_ts, 0) ?></td>
                            <td style="text-align:right;"><?= number_format($sum_tbs, 0) ?></td>
                            <td style="text-align:right;"><?= number_format($sum_total_tandan, 0) ?></td>
                            <td>—</td><td>—</td>
                        <?php elseif ($tipe === 'T4'): ?>
                            <td>—</td>
                            <td style="text-align:right;"><?= number_format($sum_hasil, 2) ?></td>
                            <td style="text-align:right;"><?= number_format($sum_prestasi, 2) ?></td>
                            <td>—</td><td>—</td>
                        <?php elseif ($tipe === 'T5'): ?>
                            <td>—</td>
                            <td style="text-align:right;"><?= number_format($sum_hasil, 0) ?></td>
                            <td>—</td><td>—</td>
                        <?php endif; ?>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="lk-footer-info">
            <span>Nama: <strong><?= $nama_karyawan ?></strong> &nbsp;|&nbsp; Total Hadir: <strong><?= $total_hadir ?></strong> hari</span>
            <span>Dicetak: <?= date('d/m/Y H:i') ?> &nbsp;|&nbsp; PT Damai Jaya Lestari</span>
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
  
  .info-laporan { text-align: center; margin-bottom: 15px; }
  .info-laporan h2 { font-size: 14pt; margin-bottom: 5px; text-decoration: underline; text-transform: uppercase; }
  .info-laporan p { font-size: 11pt; margin-bottom: 5px; }
  .info-karyawan { font-size: 11pt; font-weight: bold; margin-bottom: 15px; text-align: center; }
  
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
    <h2>LAPORAN ABSENSI DAN HASIL KINERJA</h2>
    <p>Objek: <?= htmlspecialchars($objek) ?> &nbsp;|&nbsp; Periode: <?= $periode_label ?></p>
    <div class="info-karyawan">Nama: <?= htmlspecialchars($nama_karyawan) ?></div>
  </div>

  ${tableHTML}
  
  <div class="footer-ttd">
    <div class="ttd-col">
      <p>Karyawan,</p>
      <div class="ttd-line"><?= htmlspecialchars($nama_karyawan) ?></div>
    </div>
    <div class="ttd-col">
      <p>Kerani Afdeling,</p>
      <div class="ttd-line">____________________</div>
    </div>
  </div>
</body>
</html>`);
    win.document.close();
    win.focus();
    setTimeout(() => { win.print(); }, 500);
}
</script>

<?php include 'templates/footer.php'; ?>
