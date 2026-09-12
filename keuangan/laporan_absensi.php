<?php
require '../config/config.php';
include 'templates/header.php';

$bulan = isset($_GET['bulan']) ? str_pad($_GET['bulan'], 2, '0', STR_PAD_LEFT) : date('m');
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
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

// Keuangan dapat melihat laporan untuk seluruh jabatan operasional.
$jabatan_safe = mysqli_real_escape_string($conn, $jabatan);
$where = "(role='$jabatan_safe' OR jabatan='$jabatan_safe')";
if (!empty($cari)) {
    $cari_safe = mysqli_real_escape_string($conn, $cari);
    $where .= " AND (name LIKE '%$cari_safe%' OR nik LIKE '%$cari_safe%')";
}
if (!empty($afdeling)) {
    $afdeling_safe = mysqli_real_escape_string($conn, $afdeling);
    $where .= " AND afdeling = '$afdeling_safe'";
}

$q_users = mysqli_query($conn, "SELECT id, nik, name, afdeling, role, jabatan FROM users WHERE $where ORDER BY afdeling ASC, name ASC");
$list_karyawan = [];
while ($u = mysqli_fetch_assoc($q_users)) $list_karyawan[] = $u;

// Pre-load semua absensi bulan ini sekaligus
$bulan_int = (int)$bulan;
$q_absen = mysqli_query($conn, "
    SELECT user_id, DAY(tanggal) as hari, status_kehadiran
    FROM absensis
    WHERE MONTH(tanggal)=$bulan_int AND YEAR(tanggal)=$tahun
");
$data_absen = [];
while ($a = mysqli_fetch_assoc($q_absen)) {
    $data_absen[$a['user_id']][$a['hari']] = $a['status_kehadiran'];
}


// --- LOGIKA TANDA TANGAN BERDASARKAN FILTER AFDELING ---
$nama_kerani = '-';
$nama_pengawas = '-';
$teks_afdeling = 'Semua Afdeling';

if (!empty($afdeling)) {
    $afd_esc = mysqli_real_escape_string($conn, $afdeling);
    $teks_afdeling = "Afd " . htmlspecialchars($afdeling);
    
    // Cari nama Pengawas di afdeling yang difilter
    $q_pengawas = mysqli_query($conn, "SELECT name FROM users WHERE (role = 'pengawas' OR jabatan = 'pengawas') AND afdeling = '$afd_esc' LIMIT 1");
    if ($q_pengawas && mysqli_num_rows($q_pengawas) > 0) {
        $pengawas = mysqli_fetch_assoc($q_pengawas);
        $nama_pengawas = $pengawas['name'];
    }

    // Cari nama Kerani di afdeling yang difilter
    $q_kerani = mysqli_query($conn, "SELECT name FROM users WHERE (role = 'kerani' OR jabatan = 'kerani') AND afdeling = '$afd_esc' LIMIT 1");
    if ($q_kerani && mysqli_num_rows($q_kerani) > 0) {
        $kerani = mysqli_fetch_assoc($q_kerani);
        $nama_kerani = $kerani['name'];
    }
} else {
    // Jika filter 'Semua Afdeling', tampilkan garis titik-titik untuk diisi manual jika dicetak
    $nama_kerani = '( ................................... )';
    $nama_pengawas = '( ................................... )';
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
    <button class="btn-print" onclick="window.print()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <polyline points="6 9 6 2 18 2 18 9"></polyline>
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
            <rect x="6" y="14" width="12" height="8"></rect>
        </svg>
        Cetak PDF
    </button>
</div>

<!-- Legend -->
<div class="legend">
    <strong style="color:#064e3b;">Keterangan:</strong>
    <span><span class="badge b-h">H</span> Hadir</span>
    <span><span class="badge b-t">T</span> Terlambat</span>
    <span><span class="badge b-a">A</span> Alpha</span>
    <span><span class="badge b-i">I</span> Izin</span>
    <span><span class="badge b-s">S</span> Sakit</span>
    <span><span class="badge b-c">C</span> Cuti</span>
    <span><span style="color:#94a3b8;">-</span> Tidak Ada Data</span>
</div>

<!-- Table -->
<div class="print-area">
    <div style="text-align:center; margin-bottom:16px; display:none;" class="print-kop">
        <strong style="font-size:15px;">REKAPAN ABSENSI KARYAWAN</strong><br>
        <span style="font-size:12px;">Periode: <?= $nama_bulan[$bulan] . ' ' . $tahun ?> | PT Damai Jaya Lestari</span>
        <hr style="margin:8px 0;">
    </div>

    <?php if (count($list_karyawan) > 0): ?>
        <div class="table-wrap">
            <table class="table-absen">
                <thead>
                    <tr>
                        <th rowspan="2" style="width:30px;">No</th>
                        <th rowspan="2" style="min-width:70px;">NIK</th>
                        <th rowspan="2" style="min-width:160px; text-align:left;">Nama Karyawan</th>
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
                                } elseif ($s == 'terlambat') {
                                    $h++;
                                    echo '<td><span class="badge b-t">T</span></td>';
                                } elseif (in_array($s, ['alpha', 'alpa', 'alfa'])) {
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
        <div style="text-align:center; padding:48px; background:white; border-radius:14px; border:1px solid #e5e7eb; color:#9ca3af;">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5" style="margin:0 auto 12px;display:block;">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
            </svg>
            Tidak ada data <?= strtolower($daftar_jabatan[$jabatan]) ?> yang ditemukan.
        </div>
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
        <div><strong>Afdeling&nbsp;:</strong> <?= !empty($afdeling) ? htmlspecialchars($afdeling) : 'Semua Afdeling' ?></div>
        <div><strong>Jabatan&nbsp;&nbsp;:</strong> <?= htmlspecialchars($daftar_jabatan[$jabatan]) ?></div>
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
                        if (in_array($st, ['hadir', 'tepat_waktu', 'terlambat'])) {
                            $k = 'H'; $dc = 'doc-status-H'; $h++;
                        } elseif (in_array($st, ['alpha', 'alpa', 'alfa'])) {
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
                    <td colspan="<?= $jumlah_hari + 9 ?>" class="text-center" style="padding:16px;">Tidak ada data.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

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

<?php include 'templates/footer.php'; ?>