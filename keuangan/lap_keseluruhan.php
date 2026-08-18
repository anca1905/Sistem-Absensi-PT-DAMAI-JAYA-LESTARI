<?php
require '../config/config.php';
include 'templates/header.php';

// ===== FILTER =====
$bulan     = isset($_GET['bulan'])    ? str_pad($_GET['bulan'], 2, '0', STR_PAD_LEFT) : date('m');
$tahun     = isset($_GET['tahun'])    ? (int)$_GET['tahun']   : (int)date('Y');
$afdeling  = isset($_GET['afdeling']) ? trim($_GET['afdeling']) : '';
$cari      = isset($_GET['cari'])     ? trim($_GET['cari'])   : '';
$bulan_int = (int)$bulan;

$nama_bulan = [
    '01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April',
    '05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus',
    '09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'
];

// Ambil daftar afdeling
$q_afd = mysqli_query($conn, "SELECT DISTINCT afdeling FROM users WHERE role='karyawan' AND afdeling != '' ORDER BY afdeling ASC");
$list_afdeling = [];
while ($a = mysqli_fetch_assoc($q_afd)) $list_afdeling[] = $a['afdeling'];

// ===== QUERY DATA =====
$where = "lk.tanggal BETWEEN '{$tahun}-{$bulan}-01' AND '{$tahun}-{$bulan}-31'";
if (!empty($afdeling)) {
    $afd_safe = mysqli_real_escape_string($conn, $afdeling);
    $where .= " AND u.afdeling = '$afd_safe'";
}
if (!empty($cari)) {
    $cari_safe = mysqli_real_escape_string($conn, $cari);
    $where .= " AND (u.name LIKE '%$cari_safe%' OR u.nik LIKE '%$cari_safe%')";
}

$q_data = mysqli_query($conn, "
    SELECT lk.*, u.name as karyawan_name, u.nik, u.afdeling as karyawan_afdeling, 
           m.name as mandor_name
    FROM logbook_kinerja lk
    JOIN users u ON lk.user_id = u.id
    LEFT JOIN users m ON lk.mandor_id = m.id
    WHERE $where
    ORDER BY u.afdeling ASC, lk.tanggal DESC, u.name ASC
");

$all_data = [];
while ($r = mysqli_fetch_assoc($q_data)) $all_data[] = $r;

// Total ringkas
$total_rows     = count($all_data);
$total_diterima = count(array_filter($all_data, fn($r) => $r['status'] == 'diterima'));
$total_ditinjau = count(array_filter($all_data, fn($r) => $r['status'] == 'ditinjau'));
$total_ditolak  = count(array_filter($all_data, fn($r) => $r['status'] == 'ditolak'));
?>

<style>
    @media print {
        @page { size: landscape; margin: 8mm; }
        body * { visibility: hidden; }
        .main-content { margin-left: 0 !important; }
        .sidebar, .topbar, .no-print { display: none !important; }
        .print-area, .print-area * { visibility: visible; }
        .print-area { position: absolute; left: 0; top: 0; width: 100%; }
    }

    .toolbar { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 20px; }
    .filter-select, .filter-input {
        padding: 9px 14px; border: 1.5px solid #d1d5db; border-radius: 10px;
        font-size: 13px; font-weight: 600; color: #1f2937; background: white;
        outline: none; font-family: inherit; transition: border-color 0.2s;
    }
    .filter-select:focus, .filter-input:focus { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,.12); }

    .btn-filter {
        display: inline-flex; align-items: center; gap: 6px;
        background: #10b981; color: white; border: none;
        padding: 9px 18px; border-radius: 10px;
        font-size: 13px; font-weight: 700; cursor: pointer;
        transition: all 0.2s; font-family: inherit;
    }
    .btn-filter:hover { background: #059669; }

    .btn-print {
        display: inline-flex; align-items: center; gap: 6px;
        background: white; color: #374151; border: 1.5px solid #d1d5db;
        padding: 9px 18px; border-radius: 10px;
        font-size: 13px; font-weight: 700; cursor: pointer;
        transition: all 0.2s; font-family: inherit; margin-left: auto;
    }
    .btn-print:hover { background: #f9fafb; border-color: #10b981; color: #059669; }

    /* Summary mini-stat */
    .mini-stats { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
    .mini-stat { background: white; border-radius: 12px; padding: 14px 18px; border: 1px solid #e5e7eb; display:flex; flex-direction:column; min-width: 120px; }
    .mini-stat-val { font-size: 22px; font-weight: 800; }
    .mini-stat-lbl { font-size: 11px; color: #6b7280; font-weight: 600; margin-top: 2px; }
    .v-green  { color: #065f46; }
    .v-amber  { color: #92400e; }
    .v-blue   { color: #1e40af; }
    .v-red    { color: #991b1b; }

    /* Table */
    .table-wrap {
        overflow-x: auto; border-radius: 14px;
        border: 1px solid #e5e7eb; background: white;
        box-shadow: 0 2px 12px rgba(0,0,0,0.04);
    }
    .table-keu {
        border-collapse: collapse; white-space: nowrap;
        font-size: 12px; width: 100%;
    }
    .table-keu th {
        background: #064e3b; color: #a7f3d0;
        font-weight: 700; font-size: 10px; text-transform: uppercase;
        padding: 12px 10px; border: 1px solid #065f46;
        text-align: center; letter-spacing: 0.5px;
    }
    .table-keu td {
        padding: 11px 10px; border: 1px solid #f3f4f6;
        text-align: center; color: #374151;
    }
    .table-keu tbody tr:hover td { background: #f0fdf4; }
    .table-keu .td-left { text-align: left; }
    .table-keu .td-name { font-weight: 700; color: #064e3b; }

    .status-pill {
        display: inline-block; padding: 3px 10px;
        border-radius: 20px; font-size: 10px; font-weight: 700;
    }
    .sp-diterima  { background: #d1fae5; color: #065f46; }
    .sp-ditinjau  { background: #fef3c7; color: #92400e; }
    .sp-ditolak   { background: #fee2e2; color: #991b1b; }

    .afd-row td {
        background: #ecfdf5; font-weight: 700; font-size: 11px;
        color: #064e3b; border-top: 2px solid #a7f3d0;
        text-align: left; padding-left: 16px;
    }

    .empty-state { text-align:center; padding:48px; color:#9ca3af; }
</style>

<!-- Toolbar -->
<div class="toolbar no-print">
    <form method="GET" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
        <!-- Filter Afdeling -->
        <select name="afdeling" class="filter-select">
            <option value="">Pilih Afdeling</option>
            <?php foreach($list_afdeling as $afd): ?>
                <option value="<?= htmlspecialchars($afd) ?>" <?= $afdeling == $afd ? 'selected' : '' ?>><?= htmlspecialchars($afd) ?></option>
            <?php endforeach; ?>
        </select>

        <!-- Filter Bulan -->
        <select name="bulan" class="filter-select">
            <?php foreach($nama_bulan as $num => $name): ?>
                <option value="<?= $num ?>" <?= $bulan == $num ? 'selected' : '' ?>><?= $name ?></option>
            <?php endforeach; ?>
        </select>

        <!-- Filter Tahun -->
        <select name="tahun" class="filter-select">
            <?php for($y = date('Y') - 2; $y <= date('Y'); $y++): ?>
                <option value="<?= $y ?>" <?= $tahun == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>

        <!-- Cari Nama -->
        <input type="text" name="cari" class="filter-input" placeholder="Cari nama / NIK..." value="<?= htmlspecialchars($cari) ?>" style="min-width:180px;">

        <button type="submit" class="btn-filter">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            Tampilkan
        </button>
    </form>
    <button class="btn-print" onclick="window.print()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
        Cetak PDF
    </button>
</div>

<!-- Mini Stats -->
<div class="mini-stats">
    <div class="mini-stat"><span class="mini-stat-val v-green"><?= $total_rows ?></span><span class="mini-stat-lbl">Total Entri</span></div>
    <div class="mini-stat"><span class="mini-stat-val v-blue"><?= $total_diterima ?></span><span class="mini-stat-lbl">Diterima</span></div>
    <div class="mini-stat"><span class="mini-stat-val v-amber"><?= $total_ditinjau ?></span><span class="mini-stat-lbl">Ditinjau</span></div>
    <div class="mini-stat"><span class="mini-stat-val v-red"><?= $total_ditolak ?></span><span class="mini-stat-lbl">Ditolak</span></div>
</div>

<!-- Table -->
<div class="print-area">
    <div style="display:none; text-align:center; margin-bottom:14px;" class="print-kop">
        <strong style="font-size:15px;">LAPORAN KESELURUHAN KINERJA KARYAWAN</strong><br>
        <span style="font-size:12px;">Periode: <?= $nama_bulan[$bulan] . ' ' . $tahun ?> | PT Damai Jaya Lestari</span>
        <hr style="margin: 8px 0;">
    </div>

    <?php if ($total_rows > 0): ?>
    <div class="table-wrap">
        <table class="table-keu">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>NIK</th>
                    <th style="text-align:left; min-width:160px;">Nama Karyawan</th>
                    <th>Afdeling</th>
                    <th>Mandor</th>
                    <th style="min-width:130px;">Objek Kerja</th>
                    <th>Blok</th>
                    <th>Luas (Ha)</th>
                    <th>Jam Kerja</th>
                    <th>Hasil Ton</th>
                    <th>Hasil Kg</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php 
            $no = 1;
            $cur_afd = null;
            foreach ($all_data as $r):
                if ($cur_afd !== ($r['karyawan_afdeling'] ?? '')) {
                    $cur_afd = $r['karyawan_afdeling'] ?? '';
                    echo "<tr class='afd-row'><td colspan='13'>📍 Afdeling: " . htmlspecialchars($cur_afd ?: '-') . "</td></tr>";
                }
                $s = strtolower($r['status'] ?? 'ditinjau');
                $sp = 'sp-ditinjau';
                if ($s == 'diterima') $sp = 'sp-diterima';
                if ($s == 'ditolak')  $sp = 'sp-ditolak';
            ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= date('d/m/Y', strtotime($r['tanggal'])) ?></td>
                <td class="td-left" style="font-size:11px; color:#6b7280;"><?= htmlspecialchars($r['nik']) ?></td>
                <td class="td-left td-name"><?= htmlspecialchars($r['karyawan_name']) ?></td>
                <td><?= htmlspecialchars($r['karyawan_afdeling'] ?? '-') ?></td>
                <td><?= htmlspecialchars($r['mandor_name'] ?? '-') ?></td>
                <td class="td-left"><?= htmlspecialchars($r['objek_kerja'] ?? '-') ?></td>
                <td><?= htmlspecialchars($r['blok'] ?? '-') ?></td>
                <td><?= htmlspecialchars($r['luas_ha'] ?? '-') ?></td>
                <td><?= $r['jumlah_jam_kerja'] ?? '-' ?></td>
                <td><?= $r['hasil_ton'] ?? '-' ?></td>
                <td><?= $r['hasil_kg'] ?? '-' ?></td>
                <td><span class="status-pill <?= $sp ?>"><?= ucfirst($s) ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="card empty-state">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5" style="margin:0 auto 14px;display:block;"><rect x="3" y="3" width="18" height="18" rx="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="3" y1="15" x2="21" y2="15"></line><line x1="9" y1="9" x2="9" y2="21"></line></svg>
        Belum ada data laporan kinerja untuk periode dan filter yang dipilih.<br>
        <small>Data ini diambil dari laporan yang sudah dibuat oleh Kerani.</small>
    </div>
    <?php endif; ?>
</div>

<?php include 'templates/footer.php'; ?>
