<?php
require '../config/config.php';
include 'templates/header.php';

// --- Konstanta Data Real ---
$list_objek = [
    'Langsir manual',
    'Membabat gawangan',
    'Semprot pingan',
    'Rawat jalan',
    'Kotrek anyangan',
    'Panen',
    'Potong buah',
    'Kutip brondolan',
    'Muat TBS ke truk',
    'Muat TBS ke jondol'
];

$list_blok = [
    'H.39' => '8.66',  'H.40' => '0.91',
    'I.39' => '29.26', 'I.40' => '26.18',
    'J.39' => '31.01', 'J.40' => '27.05',
    'K.39' => '20.98', 'K.40' => '28.52',
    'L.39' => '31.17', 'L.40' => '17.74'
];

// Tipe tabel berdasarkan objek kerja
function getTableType($objek) {
    if ($objek === 'Langsir manual') return 'T1';
    if (in_array($objek, ['Membabat gawangan','Semprot pingan','Rawat jalan','Kotrek anyangan'])) return 'T2';
    if (in_array($objek, ['Panen','Potong buah'])) return 'T3';
    if ($objek === 'Kutip brondolan') return 'T4';
    if (in_array($objek, ['Muat TBS ke truk','Muat TBS ke jondol'])) return 'T5';
    return 'T2';
}

// Label tipe tabel
$label_tipe = [
    'T1' => 'Langsir', 'T2' => 'Perawatan',
    'T3' => 'Panen/Potong Buah', 'T4' => 'Kutip Brondolan', 'T5' => 'Muat TBS'
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

$jumlah_hari = cal_days_in_month(CAL_GREGORIAN, $bulan_int, $tahun);
$periode_label = "01 - {$jumlah_hari} " . $nama_bulan[$bulan] . " {$tahun}";
?>
<style>
    @media print {
        @page { size: landscape; margin: 10mm; }
        body * { visibility: hidden; }
        .main-content { margin-left: 0 !important; }
        .sidebar, .topbar, .no-print { display: none !important; }
        .print-area, .print-area * { visibility: visible; }
        .print-area { position: absolute; left: 0; top: 0; width: 100%; }
        .lk-card { border: none !important; box-shadow: none !important; }
        .lk-header-main { border-bottom: 2px solid #000 !important; }
    }

    .lk-toolbar {
        display: flex; align-items: center; justify-content: space-between;
        gap: 12px; margin-bottom: 20px; flex-wrap: wrap;
    }
    .lk-filter-group { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
    .lk-select, .lk-input {
        padding: 9px 14px; border: 1.5px solid #cbd5e1; border-radius: 9px;
        font-size: 13px; font-weight: 600; color: var(--text-main);
        background: white; outline: none; cursor: pointer; font-family: inherit;
        transition: border-color .2s;
    }
    .lk-select:focus, .lk-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(59,130,246,.12); }
    .lk-input { min-width: 180px; cursor: text; }
    .btn-filter-go {
        padding: 9px 18px; background: var(--accent); color: white;
        border: none; border-radius: 9px; font-weight: 700; font-size: 13px;
        cursor: pointer; transition: background .2s;
    }
    .btn-filter-go:hover { background: #2563eb; }
    .btn-print-lk {
        display: flex; align-items: center; gap: 8px;
        padding: 9px 18px; background: white; color: var(--text-main);
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
    }
    .lk-header-main {
        padding: 20px 24px; border-bottom: 1.5px solid #e2e8f0;
        background: #f8fafc; text-align: center;
    }
    .lk-title { font-size: 17px; font-weight: 800; color: var(--text-main); margin: 0; }
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

    .td-no { color: var(--text-muted); font-weight: 700; text-align: center; }
    .td-nik { font-weight: 800; color: var(--text-main); }
    .td-name { font-weight: 600; min-width: 160px; }
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
    }

    /* Summary row */
    .lk-table tfoot td {
        background: #1e293b; color: white; font-weight: 800; font-size: 12px; border-color: #334155;
    }
</style>

<!-- Toolbar Filter -->
<div class="lk-toolbar no-print">
    <form method="GET" id="filterForm" class="lk-filter-group">
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
        <input type="text" name="cari" class="lk-input" placeholder="🔍 Cari nama / NIK..." value="<?= htmlspecialchars($cari) ?>">
        <button type="submit" class="btn-filter-go">Tampilkan</button>
    </form>
    <button class="btn-print-lk" onclick="window.print()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
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
                <!-- Baris 1: Group header -->
                <tr>
                    <th rowspan="2" width="40">NO</th>
                    <th rowspan="2" width="80">NIK</th>
                    <th rowspan="2" style="min-width:160px; text-align:left;">NAMA KARYAWAN</th>
                    <!-- O1 -->
                    <th colspan="2" class="th-o1">O1 — KEHADIRAN</th>
                    <!-- O2 — berbeda setiap tipe -->
                    <?php if ($tipe === 'T1'): ?>
                        <th colspan="5" class="th-o2">O2 — HASIL KERJA (LANGSIR)</th>
                    <?php elseif ($tipe === 'T2'): ?>
                        <th colspan="3" class="th-o2">O2 — DATA KERJA</th>
                    <?php elseif ($tipe === 'T3'): ?>
                        <th colspan="7" class="th-o2">O2 — HASIL PANEN</th>
                    <?php elseif ($tipe === 'T4'): ?>
                        <th colspan="5" class="th-o2">O2 — HASIL KUTIP BRONDOLAN</th>
                    <?php elseif ($tipe === 'T5'): ?>
                        <th colspan="4" class="th-o2">O2 — HASIL MUAT TBS</th>
                    <?php endif; ?>
                </tr>
                <!-- Baris 2: Detail kolom -->
                <tr>
                    <!-- O1 detail -->
                    <th class="th-o1">STATUS</th>
                    <th class="th-o1">∑ HADIR</th>
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
                        <th class="th-o2">HASIL TBS (kg)</th>
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
            $no = 1;
            $total_hadir_global = 0;
            // Totals untuk footer
            $sum_langsir=0; $sum_prestasi=0; $sum_hasil=0; $sum_tbs_kg=0;
            $sum_ts=0; $sum_tbs=0; $sum_total_tandan=0;

            foreach ($list_karyawan as $user):
                $uid = $user['id'];

                // --- O1: Kehadiran bulan ini ---
                $q_abs = mysqli_query($conn, "SELECT status_kehadiran FROM absensis WHERE user_id=$uid AND MONTH(tanggal)=$bulan_int AND YEAR(tanggal)=$tahun");
                $hadir_count = 0;
                $status_utama = null;
                $status_freq = [];
                while ($abs = mysqli_fetch_assoc($q_abs)) {
                    $s = strtolower($abs['status_kehadiran']);
                    if (!in_array($s, ['alpha','izin','sakit','cuti'])) {
                        $hadir_count++;
                    }
                    $status_freq[$s] = ($status_freq[$s] ?? 0) + 1;
                }
                if (!empty($status_freq)) {
                    arsort($status_freq);
                    $status_utama = array_key_first($status_freq);
                }

                // Tentukan badge status
                if ($hadir_count > 0) {
                    $badge_class = 'badge-hadir';
                    $badge_text  = 'Hadir';
                } elseif ($status_utama == 'sakit') {
                    $badge_class = 'badge-sakit'; $badge_text = 'Sakit';
                } elseif ($status_utama == 'izin') {
                    $badge_class = 'badge-izin'; $badge_text = 'Izin';
                } elseif ($status_utama == 'cuti') {
                    $badge_class = 'badge-cuti'; $badge_text = 'Cuti';
                } elseif ($status_utama == 'alpha') {
                    $badge_class = 'badge-alpha'; $badge_text = 'Alpha';
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
                      AND MONTH(lk.tanggal) = $bulan_int
                      AND YEAR(lk.tanggal)  = $tahun
                    LIMIT 1
                ");
                $lb = $q_lb ? mysqli_fetch_assoc($q_lb) : null;
                $has_data = $lb && $hadir_count > 0;

                $total_hadir_global += $hadir_count;

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

                $row_class = ($hadir_count == 0) ? 'absent-row' : '';
            ?>
                <tr class="<?= $row_class ?>">
                    <td class="td-no"><?= $no++ ?></td>
                    <td class="td-nik"><?= htmlspecialchars($user['nik']) ?></td>
                    <td class="td-name" style="text-align:left;"><?= htmlspecialchars($user['name']) ?></td>

                    <!-- O1 -->
                    <td class="td-center"><span class="<?= $badge_class ?>"><?= $badge_text ?></span></td>
                    <td class="td-center" style="font-weight:800;"><?= $hadir_count > 0 ? $hadir_count . ' hr' : '<span style="color:#cbd5e1;">0</span>' ?></td>

                    <!-- O2 — Tipe T1: Langsir -->
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

                    <!-- O2 — Tipe T2: Perawatan -->
                    <?php elseif ($tipe === 'T2'): ?>
                        <?php if ($has_data): ?>
                            <td><?= htmlspecialchars($lb['nama_mandor'] ?? '—') ?></td>
                            <td class="td-center"><?= htmlspecialchars($lb['blok'] ?? '—') ?></td>
                            <td class="td-center"><?= htmlspecialchars($lb['luas_ha'] ?? '—') ?></td>
                        <?php else: ?>
                            <td class="td-empty" colspan="3">—</td>
                        <?php endif; ?>

                    <!-- O2 — Tipe T3: Panen / Potong Buah -->
                    <?php elseif ($tipe === 'T3'): ?>
                        <?php if ($has_data): ?>
                            <td><?= htmlspecialchars($lb['nama_mandor'] ?? '—') ?></td>
                            <td class="td-num"><?= number_format($lb['hasil_ton'] ?? 0, 0) ?></td>
                            <td class="td-num"><?= number_format($lb['tandan_kosong'] ?? 0, 0) ?></td>
                            <td class="td-num"><?= number_format($lb['tbs'] ?? 0, 0) ?></td>
                            <td class="td-num"><?= number_format($lb['total_tandan'] ?? 0, 0) ?></td>
                            <td class="td-center"><?= htmlspecialchars($lb['blok'] ?? '—') ?></td>
                            <td class="td-center"><?= htmlspecialchars($lb['luas_ha'] ?? '—') ?></td>
                        <?php else: ?>
                            <td class="td-empty" colspan="7">—</td>
                        <?php endif; ?>

                    <!-- O2 — Tipe T4: Kutip Brondolan -->
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

                    <!-- O2 — Tipe T5: Muat TBS -->
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
            <?php endforeach; ?>

            <?php if (empty($list_karyawan)): ?>
                <tr>
                    <td colspan="20" style="text-align:center;padding:40px;color:var(--text-muted);font-size:14px;">
                        Belum ada data karyawan untuk filter ini.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>

            <!-- Footer / Total -->
            <?php if (!empty($list_karyawan)): ?>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align:right;">TOTAL</td>
                    <td class="td-center"><?= $total_hadir_global ?> hr</td>
                    <?php if ($tipe === 'T1'): ?>
                        <td>—</td>
                        <td style="text-align:right;"><?= number_format($sum_langsir, 2) ?></td>
                        <td style="text-align:right;"><?= number_format($sum_prestasi, 2) ?></td>
                        <td>—</td><td>—</td>
                    <?php elseif ($tipe === 'T2'): ?>
                        <td>—</td><td>—</td><td>—</td>
                    <?php elseif ($tipe === 'T3'): ?>
                        <td>—</td>
                        <td style="text-align:right;"><?= number_format($sum_tbs_kg, 0) ?></td>
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
            <?php endif; ?>
        </table>
    </div>

    <div class="lk-footer-info">
        <span>Total Karyawan: <strong><?= count($list_karyawan) ?></strong> orang &nbsp;|&nbsp; Total Hadir: <strong><?= $total_hadir_global ?></strong> hari-orang</span>
        <span>Dicetak: <?= date('d/m/Y H:i') ?> &nbsp;|&nbsp; PT Damai Jaya Lestari</span>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
