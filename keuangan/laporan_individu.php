<?php
require '../config/config.php';
include 'templates/header.php';

$nama_bulan = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];
$list_objek = ['Panen', 'Penunasan', 'Racun piringan', 'Perawatan', 'Muat TBS ke truk', 'Muat TBS ke jonder'];

$bulan = isset($_GET['bulan']) ? str_pad((int) $_GET['bulan'], 2, '0', STR_PAD_LEFT) : date('m');
$tahun = isset($_GET['tahun']) ? (int) $_GET['tahun'] : (int) date('Y');
$objek = isset($_GET['objek']) ? trim($_GET['objek']) : 'Panen';
$user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
if (!in_array($objek, $list_objek, true)) $objek = 'Panen';

$q_user = mysqli_query($conn, "SELECT id, nik, name, afdeling FROM users WHERE id=$user_id AND (role IN ('karyawan', 'kerani', 'mandor', 'pengawas') OR jabatan IN ('karyawan', 'kerani', 'mandor', 'pengawas')) LIMIT 1");
$personel = $q_user ? mysqli_fetch_assoc($q_user) : null;
if (!$personel) {
    header('Location: lap_keseluruhan.php');
    exit;
}

$jumlah_hari = (int) date('t', mktime(0, 0, 0, (int) $bulan, 1, $tahun));
$objek_safe = mysqli_real_escape_string($conn, $objek);
$periode_label = '01 - ' . $jumlah_hari . ' ' . $nama_bulan[$bulan] . ' ' . $tahun;

function statusObjekKerja(?string $status): array
{
    $status = strtolower(trim((string) $status));
    if ($status === 'diterima' || $status === 'selesai') return ['Diterima', 'status-diterima'];
    if ($status === 'ditolak') return ['Ditolak', 'status-ditolak'];
    if ($status === 'pending' || $status === 'ditinjau') return ['Ditinjau', 'status-ditinjau'];
    return ['Belum ada objek', 'status-kosong'];
}

function statusKehadiran(?string $status): array
{
    $status = strtolower(trim((string) $status));
    if (in_array($status, ['hadir', 'tepat_waktu', 'terlambat'], true)) return ['Hadir', 'hadir'];
    if ($status === 'izin') return ['Izin', 'izin'];
    if ($status === 'sakit') return ['Sakit', 'sakit'];
    if ($status === 'cuti') return ['Cuti', 'cuti'];
    if (in_array($status, ['alpha', 'alpa', 'alfa'], true)) return ['Alpha', 'alpha'];
    return ['—', 'kosong'];
}
?>

<style>
    .detail-toolbar { display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap; align-items:center; margin-bottom:20px; }
    .detail-filter { display:flex; gap:9px; flex-wrap:wrap; align-items:center; }
    .detail-select, .detail-btn { min-height:40px; padding:9px 13px; border:1px solid #cbd5e1; border-radius:9px; background:#fff; color:#334155; font:600 13px inherit; }
    .detail-btn { cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:7px; }
    .detail-btn:hover { border-color:#10b981; color:#047857; }
    .detail-card { overflow:hidden; background:#fff; border:1px solid #e2e8f0; border-radius:14px; box-shadow:0 4px 15px rgba(0,0,0,.03); }
    .detail-heading { padding:22px 24px; text-align:center; background:#f8fafc; border-bottom:1px solid #e2e8f0; }
    .detail-heading h2 { margin:0; color:#064e3b; font-size:18px; }
    .detail-heading p { margin:6px 0 0; color:#64748b; font-size:13px; }
    .detail-table-wrap { overflow-x:auto; }
    .detail-table { width:100%; border-collapse:collapse; min-width:860px; font-size:13px; }
    .detail-table th { padding:13px 12px; background:#065f46; color:#fff; text-align:center; font-size:11px; letter-spacing:.2px; }
    .detail-table td { padding:12px; border-bottom:1px solid #eef2f7; color:#334155; vertical-align:middle; }
    .detail-table tbody tr:nth-child(even) { background:#f6fdf9; }
    .detail-table tbody tr:hover { background:#eefbf5; }
    .text-center { text-align:center; }
    .status-badge, .attendance-badge { display:inline-flex; align-items:center; justify-content:center; min-width:72px; padding:5px 9px; border-radius:999px; font-weight:800; font-size:11px; }
    .status-diterima { background:#dcfce7; color:#166534; }
    .status-ditolak { background:#fee2e2; color:#991b1b; }
    .status-ditinjau { background:#fef3c7; color:#92400e; }
    .status-kosong, .kosong { background:#f1f5f9; color:#94a3b8; }
    .hadir { background:#dcfce7; color:#166534; }
    .izin { background:#dbeafe; color:#1d4ed8; }
    .sakit { background:#ede9fe; color:#6d28d9; }
    .cuti { background:#ffedd5; color:#c2410c; }
    .alpha { background:#fee2e2; color:#b91c1c; }
    .detail-footer { padding:14px 20px; color:#64748b; font-size:12px; background:#f8fafc; }
    @media print { .sidebar,.topbar,.detail-toolbar { display:none !important; } .main-content{margin-left:0!important;} .content-container{padding:0!important;} .detail-card{border:none;box-shadow:none;} }
</style>

<div class="detail-toolbar no-print">
    <form method="GET" class="detail-filter" onchange="this.submit()">
        <input type="hidden" name="user_id" value="<?= $personel['id'] ?>">
        <select name="bulan" class="detail-select">
            <?php foreach ($nama_bulan as $nomor => $nama): ?>
                <option value="<?= $nomor ?>" <?= $bulan === $nomor ? 'selected' : '' ?>><?= $nama ?></option>
            <?php endforeach; ?>
        </select>
        <select name="tahun" class="detail-select">
            <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                <option value="<?= $y ?>" <?= $tahun === $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
        <select name="objek" class="detail-select">
            <?php foreach ($list_objek as $item): ?>
                <option value="<?= htmlspecialchars($item) ?>" <?= $objek === $item ? 'selected' : '' ?>><?= htmlspecialchars($item) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
    <div style="display:flex;gap:9px;">
        <a href="lap_keseluruhan.php?bulan=<?= $bulan ?>&tahun=<?= $tahun ?>&objek=<?= urlencode($objek) ?>" class="detail-btn">← Kembali</a>
        <button type="button" class="detail-btn" onclick="window.print()">▣ Cetak PDF</button>
    </div>
</div>

<div class="detail-card">
    <div class="detail-heading">
        <h2>Rincian Harian: <?= htmlspecialchars($personel['name']) ?></h2>
        <p>NIK: <?= htmlspecialchars($personel['nik']) ?> &nbsp;|&nbsp; Afdeling: <?= htmlspecialchars($personel['afdeling'] ?: '-') ?></p>
        <p>Objek: <?= htmlspecialchars($objek) ?> &nbsp;|&nbsp; Periode: <?= $periode_label ?></p>
    </div>
    <div class="detail-table-wrap">
        <table class="detail-table">
            <thead>
                <tr>
                    <th>TANGGAL</th>
                    <th>KEHADIRAN</th>
                    <th>NAMA MANDOR</th>
                    <th>BLOK</th>
                    <th>LUAS (HA)</th>
                    <th>HASIL KERJA</th>
                    <th>STATUS OBJEK</th>
                </tr>
            </thead>
            <tbody>
                <?php for ($hari = 1; $hari <= $jumlah_hari; $hari++):
                    $tanggal = sprintf('%04d-%02d-%02d', $tahun, (int) $bulan, $hari);
                    $q_absen = mysqli_query($conn, "SELECT status_kehadiran FROM absensis WHERE user_id={$personel['id']} AND tanggal='$tanggal' LIMIT 1");
                    $absen = $q_absen ? mysqli_fetch_assoc($q_absen) : null;
                    [$label_absen, $kelas_absen] = statusKehadiran($absen['status_kehadiran'] ?? null);
                    $q_logbook = mysqli_query($conn, "SELECT lk.*, m.name AS nama_mandor FROM logbook_kinerja lk LEFT JOIN users m ON m.id=lk.mandor_id WHERE lk.user_id={$personel['id']} AND lk.objek_kerja='$objek_safe' AND lk.tanggal='$tanggal' LIMIT 1");
                    $logbook = $q_logbook ? mysqli_fetch_assoc($q_logbook) : null;
                    [$label_status, $kelas_status] = statusObjekKerja($logbook['status'] ?? null);
                    $hasil = '—';
                    if ($logbook) {
                        if ($objek === 'Panen') $hasil = number_format((float) ($logbook['total_tandan'] ?? 0), 0) . ' tandan';
                        elseif (!empty($logbook['hasil_langsir_kg'])) $hasil = number_format((float) $logbook['hasil_langsir_kg'], 2) . ' kg';
                        elseif (!empty($logbook['hasil_kg'])) $hasil = number_format((float) $logbook['hasil_kg'], 2) . ' kg';
                        elseif (!empty($logbook['jumlah_jam_kerja'])) $hasil = number_format((float) $logbook['jumlah_jam_kerja'], 1) . ' jam';
                    }
                ?>
                    <tr>
                        <td class="text-center"><strong><?= str_pad($hari, 2, '0', STR_PAD_LEFT) ?> <?= $nama_bulan[$bulan] ?></strong></td>
                        <td class="text-center"><span class="attendance-badge <?= $kelas_absen ?>"><?= $label_absen ?></span></td>
                        <td><?= htmlspecialchars($logbook['nama_mandor'] ?? '—') ?></td>
                        <td class="text-center"><?= htmlspecialchars($logbook['blok'] ?? '—') ?></td>
                        <td class="text-center"><?= $logbook && $logbook['luas_ha'] !== null ? htmlspecialchars($logbook['luas_ha']) : '—' ?></td>
                        <td class="text-center"><strong><?= $hasil ?></strong></td>
                        <td class="text-center"><span class="status-badge <?= $kelas_status ?>"><?= $label_status ?></span></td>
                    </tr>
                <?php endfor; ?>
            </tbody>
        </table>
    </div>
    <div class="detail-footer">Status objek kerja: <strong>Diterima</strong> berarti telah diverifikasi, <strong>Ditinjau</strong> masih menunggu pemeriksaan, dan <strong>Ditolak</strong> perlu tindak lanjut.</div>
</div>

<?php include 'templates/footer.php'; ?>
