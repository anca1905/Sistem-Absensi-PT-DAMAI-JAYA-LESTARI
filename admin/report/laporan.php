<?php
require '../../config/config.php';
include '../templates/header.php';

// @page landscape untuk halaman ini saja
echo '<style>@media print { @page { size: landscape; margin: 12mm; } }</style>';

// Filter parameter
$bulan    = isset($_GET['bulan'])    ? $_GET['bulan']    : date('m');
$tahun    = isset($_GET['tahun'])    ? $_GET['tahun']    : date('Y');
$jabatan  = isset($_GET['jabatan'])  ? $_GET['jabatan']  : '';
$afdeling = isset($_GET['afdeling']) ? $_GET['afdeling'] : '';

$jumlah_hari = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);

$nama_bulan = [
    '01'=>'Januari','02'=>'Februari','03'=>'Maret',
    '04'=>'April',  '05'=>'Mei',     '06'=>'Juni',
    '07'=>'Juli',   '08'=>'Agustus', '09'=>'September',
    '10'=>'Oktober','11'=>'November','12'=>'Desember'
];
$periode_judul = $nama_bulan[$bulan] . ' ' . $tahun;

// Query dengan filter
$where_conditions = ["role != 'admin'"];
if (!empty($jabatan)) {
    $jabatan_safe = mysqli_real_escape_string($conn, $jabatan);
    $where_conditions[] = "(role = '$jabatan_safe' OR jabatan = '$jabatan_safe')";
}
if (!empty($afdeling)) {
    $where_conditions[] = "afdeling = '" . mysqli_real_escape_string($conn, $afdeling) . "'";
}
$where_clause  = "WHERE " . implode(' AND ', $where_conditions);
$query_users   = mysqli_query($conn, "SELECT * FROM users $where_clause ORDER BY name ASC");

// Pre-load semua data absensi bulan ini
$query_absen = mysqli_query($conn, "
    SELECT user_id, DAY(tanggal) as hari, status_kehadiran 
    FROM absensis 
    WHERE MONTH(tanggal) = '$bulan' AND YEAR(tanggal) = '$tahun'
");
$data_absen = [];
while ($row = mysqli_fetch_assoc($query_absen)) {
    $data_absen[$row['user_id']][$row['hari']] = $row['status_kehadiran'];
}

// Kumpulkan data untuk dokumen cetak
$all_users_data = [];
mysqli_data_seek($query_users, 0);
while ($u = mysqli_fetch_assoc($query_users)) {
    $all_users_data[] = $u;
}
?>

<style>
    /* Sticky columns untuk tabel absensi lebar */
    .table-absen { width: 100%; border-collapse: collapse; white-space: nowrap; }
    .table-absen th, .table-absen td {
        padding: 10px 8px; text-align: center; border-bottom: 1px solid #e2e8f0; font-size: 13px;
    }
    .table-absen th {
        background-color: #f8fafc; color: #475569; font-weight: 700;
        text-transform: uppercase; font-size: 11px; border-bottom: 2px solid #e2e8f0;
    }
    .table-absen tbody tr { background-color: #fff; }
    .table-absen tbody tr:hover td { background-color: #f1f5f9; }
    .table-absen thead tr:first-child th:nth-child(1),
    .table-absen tbody td:nth-child(1) { position: sticky; left: 0; z-index: 2; background-color: inherit; }
    .table-absen thead tr:first-child th:nth-child(2),
    .table-absen tbody td:nth-child(2) { position: sticky; left: 40px; z-index: 2; background-color: inherit; }
    .table-absen thead tr:first-child th:nth-child(3),
    .table-absen tbody td:nth-child(3) { position: sticky; left: 160px; z-index: 2; background-color: inherit; border-right: 2px solid #cbd5e1; }
    .table-absen thead tr:first-child th:nth-child(1),
    .table-absen thead tr:first-child th:nth-child(2),
    .table-absen thead tr:first-child th:nth-child(3) { z-index: 3; background-color: #f8fafc; }

    /* Status badges UI */
    .ab-badge {
        display: inline-flex; align-items: center; justify-content: center;
        width: 24px; height: 24px; border-radius: 5px; font-weight: 700; font-size: 11px;
    }
    .ab-H { background-color: #dcfce7; color: #166534; }
    .ab-A { background-color: #fee2e2; color: #991b1b; }
    .ab-T { background-color: #fef9c3; color: #854d0e; }
    .ab-I { background-color: #e0f2fe; color: #075985; }
    .ab-S { background-color: #ede9fe; color: #5b21b6; }
    .ab-C { background-color: #ffedd5; color: #9a3412; }
</style>

<!-- ========== WEB UI ========== -->
<div class="report-wrapper">
    <form method="GET" action="laporan.php" id="formFilter">
        <div class="report-top-bar">
            <div class="filter-group">
                <span class="filter-label">Filter:</span>

                <select name="afdeling" class="form-select" onchange="document.getElementById('formFilter').submit();">
                    <option value="">Semua Afdeling</option>
                    <?php
                    $afd_q = mysqli_query($conn, "SELECT nama_afdeling FROM afdelings ORDER BY nama_afdeling ASC");
                    while ($afd = mysqli_fetch_assoc($afd_q)):
                    ?>
                        <option value="<?= htmlspecialchars($afd['nama_afdeling']) ?>" <?= $afdeling == $afd['nama_afdeling'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($afd['nama_afdeling']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <select name="jabatan" class="form-select" onchange="document.getElementById('formFilter').submit();">
                    <option value="">Semua Jabatan</option>
                    <option value="mandor"   <?= $jabatan=='mandor'   ? 'selected':'' ?>>Mandor</option>
                    <option value="karyawan" <?= $jabatan=='karyawan' ? 'selected':'' ?>>Karyawan</option>
                    <option value="pengawas" <?= $jabatan=='pengawas' ? 'selected':'' ?>>Pengawas</option>
                    <option value="kerani"   <?= $jabatan=='kerani'   ? 'selected':'' ?>>Kerani</option>
                </select>

                <select name="bulan" class="form-select" onchange="document.getElementById('formFilter').submit();">
                    <?php foreach($nama_bulan as $num => $name): ?>
                        <option value="<?= $num ?>" <?= $bulan==$num ? 'selected':'' ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="tahun" class="form-select" onchange="document.getElementById('formFilter').submit();">
                    <?php for($y = date('Y')-2; $y <= date('Y'); $y++): ?>
                        <option value="<?= $y ?>" <?= $tahun==$y ? 'selected':'' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="report-title-center">
                <h2>Laporan Absensi</h2>
                <p>Periode: <strong><?= $periode_judul ?></strong>
                    <?= !empty($jabatan) ? ' &mdash; '.ucfirst($jabatan) : '' ?>
                    <?= !empty($afdeling) ? ' | '.htmlspecialchars($afdeling) : '' ?>
                </p>
            </div>

            <button type="button" class="btn btn-primary" onclick="window.print()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                Cetak Dokumen
            </button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table-absen">
            <thead>
                <tr>
                    <th rowspan="2" style="width:40px;">NO</th>
                    <th rowspan="2" style="text-align:left; min-width:120px;">NIK</th>
                    <th rowspan="2" style="text-align:left; min-width:180px;">NAMA</th>
                    <th colspan="<?= $jumlah_hari ?>">TANGGAL</th>
                    <th rowspan="2" style="min-width:90px;">JABATAN</th>
                    <th rowspan="2" style="min-width:80px;">TOTAL<br>HADIR</th>
                </tr>
                <tr>
                    <?php for($i = 1; $i <= $jumlah_hari; $i++): ?>
                        <th style="min-width:32px;"><?= $i ?></th>
                    <?php endfor; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                if (count($all_users_data) > 0):
                    foreach ($all_users_data as $user):
                        $total_hadir = 0;
                ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td style="text-align:left;"><?= htmlspecialchars($user['nik']) ?></td>
                        <td style="text-align:left; font-weight:600;"><?= htmlspecialchars($user['name']) ?></td>

                        <?php for($i = 1; $i <= $jumlah_hari; $i++):
                            $st = $data_absen[$user['id']][$i] ?? null;
                            $kode = ''; $cls = '';
                            if (in_array($st, ['hadir','tepat_waktu','terlambat'])) { $kode='H'; $cls='ab-H'; $total_hadir++; }
                            elseif ($st=='alpha')  { $kode='A'; $cls='ab-A'; }
                            elseif ($st=='izin')   { $kode='I'; $cls='ab-I'; }
                            elseif ($st=='sakit')  { $kode='S'; $cls='ab-S'; }
                            elseif ($st=='cuti')   { $kode='C'; $cls='ab-C'; }
                        ?>
                            <td><?php if($kode): ?><span class="ab-badge <?= $cls ?>"><?= $kode ?></span><?php else: ?><span style="color:#e2e8f0;">·</span><?php endif; ?></td>
                        <?php endfor; ?>

                        <td><?= ucfirst(htmlspecialchars($user['jabatan'] ?? '-')) ?></td>
                        <td style="font-weight:700;"><?= $total_hadir ?> Hari</td>
                    </tr>
                <?php
                    endforeach;
                else:
                ?>
                    <tr><td colspan="<?= $jumlah_hari + 5 ?>" style="padding:30px; color:#94a3b8; text-align:center;">Tidak ada data karyawan ditemukan.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
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
        <h3>Laporan Absensi &mdash; <?= $periode_judul ?></h3>
    </div>
    <div class="doc-meta">
        <div><strong>Afdeling&nbsp;:</strong> <?= !empty($afdeling) ? htmlspecialchars($afdeling) : 'Semua Afdeling' ?></div>
        <div><strong>Jabatan&nbsp;&nbsp;:</strong> <?= !empty($jabatan) ? ucfirst(htmlspecialchars($jabatan)) : 'Semua Jabatan' ?></div>
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
                <th rowspan="2" style="width:60px;">Jabatan</th>
                <th rowspan="2" style="width:40px;">Total Hadir</th>
            </tr>
            <tr>
                <?php for($i=1; $i<=$jumlah_hari; $i++): ?>
                    <th style="width:18px; font-size:8pt;"><?= $i ?></th>
                <?php endfor; ?>
            </tr>
        </thead>
        <tbody>
            <?php
            $pno = 1;
            foreach($all_users_data as $user):
                $total = 0;
            ?>
            <tr>
                <td class="text-center"><?= $pno++ ?></td>
                <td><?= htmlspecialchars($user['nik']) ?></td>
                <td><?= htmlspecialchars($user['name']) ?></td>
                <?php for($i=1; $i<=$jumlah_hari; $i++):
                    $st = $data_absen[$user['id']][$i] ?? null;
                    $k = ''; $dc = '';
                    if(in_array($st,['hadir','tepat_waktu','terlambat'])) { $k='H'; $dc='doc-status-H'; $total++; }
                    elseif($st=='alpha') { $k='A'; $dc='doc-status-A'; }
                    elseif($st=='izin')  { $k='I'; $dc='doc-status-I'; }
                    elseif($st=='sakit') { $k='S'; $dc='doc-status-S'; }
                    elseif($st=='cuti')  { $k='C'; $dc='doc-status-C'; }
                ?>
                    <td class="text-center <?= $dc ?>" style="font-size:8pt; font-weight:bold;"><?= $k ?></td>
                <?php endfor; ?>
                <td class="text-center"><?= ucfirst(htmlspecialchars($user['jabatan']??'-')) ?></td>
                <td class="text-center" style="font-weight:bold;"><?= $total ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($all_users_data)): ?>
            <tr><td colspan="<?= $jumlah_hari+5 ?>" class="text-center" style="padding:16px;">Tidak ada data.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Tanda Tangan -->
    <div class="doc-signature">
        <div class="doc-signature-col">
            <p>Mengetahui,<br>Manager / Askep</p>
            <span class="sig-name">(_____________________)</span>
        </div>
        <div class="doc-signature-col">
            <p>Menyetujui,<br>Kepala Administrasi</p>
            <span class="sig-name">(_____________________)</span>
        </div>
        <div class="doc-signature-col">
            <p>Dibuat Oleh,<br>Kerani / Admin</p>
            <span class="sig-name">(_____________________)</span>
        </div>
    </div>

    <div class="doc-footer">
        Dokumen ini dicetak secara otomatis oleh Sistem Informasi PT Damai Jaya Lestari pada <?= date('d F Y \p\u\k\u\l H:i') ?> WIB.
    </div>
</div>

<?php include '../templates/footer.php'; ?>