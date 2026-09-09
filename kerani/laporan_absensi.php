<?php
require '../config/config.php';
include 'templates/header.php';
?>
<style type="text/css" media="print">
    @page {
        size: landscape;
    }
</style>
<?php


$role_filter = isset($_GET['role']) ? $_GET['role'] : 'karyawan';
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');

$jumlah_hari = date('t', mktime(0, 0, 0, $bulan, 1, $tahun));
$nama_bulan = array(
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
);
?>

<style>
    .header-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }

    .filter-group {
        display: flex;
        gap: 12px;
    }

    .filter-select {
        padding: 10px 16px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        color: var(--text-main);
        background: white;
        outline: none;
        cursor: pointer;
    }

    .filter-select:focus {
        border-color: var(--accent);
    }

    .btn-print {
        background: white;
        color: var(--text-main);
        border: 1px solid #cbd5e1;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }

    .btn-print:hover {
        background: #f8fafc;
        border-color: #94a3b8;
    }

    .card {
        background: white;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
        overflow: hidden;
    }

    .card-header {
        padding: 20px 24px;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
        text-align: center;
    }

    .card-title {
        font-size: 18px;
        font-weight: 700;
        color: var(--text-main);
        margin: 0;
    }

    .table-container {
        width: 100%;
        overflow-x: auto;
    }

    .table-data {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        white-space: nowrap;
    }

    .table-data th,
    .table-data td {
        padding: 12px 10px;
        text-align: center;
        border: 1px solid #e2e8f0;
    }

    .table-data th {
        background: white;
        color: var(--text-muted);
        font-weight: 700;
        text-transform: uppercase;
        font-size: 11px;
    }

    .table-data tbody tr:hover {
        background: #f8fafc;
    }

    .text-left {
        text-align: left !important;
    }

    .status-badge {
        display: inline-block;
        width: 20px;
        height: 20px;
        line-height: 20px;
        border-radius: 4px;
        font-weight: bold;
        font-size: 10px;
    }

    .status-h {
        background-color: #dcfce7;
        color: #166534;
    }

    .status-a {
        background-color: #fee2e2;
        color: #991b1b;
    }

    .status-i {
        background-color: #e0f2fe;
        color: #075985;
    }

    .status-s {
        background-color: #ede9fe;
        color: #5b21b6;
    }

    .status-c {
        background-color: #ffedd5;
        color: #9a3412;
    }

    /* Print styles are handled by print.css */
</style>

<div class="header-actions">
    <form method="GET" id="filterForm" class="filter-group">
        <select name="role" class="filter-select" onchange="document.getElementById('filterForm').submit()">
            <option value="kerani" <?= $role_filter == 'kerani' ? 'selected' : '' ?>>Kerani</option>
            <option value="karyawan" <?= $role_filter == 'karyawan' ? 'selected' : '' ?>>Karyawan</option>
            <option value="mandor" <?= $role_filter == 'mandor' ? 'selected' : '' ?>>Mandor</option>
            <option value="pengawas" <?= $role_filter == 'pengawas' ? 'selected' : '' ?>>Pengawas</option>
        </select>

        <select name="bulan" class="filter-select" onchange="document.getElementById('filterForm').submit()">
            <?php foreach ($nama_bulan as $num => $name): ?>
                <option value="<?= $num ?>" <?= $bulan == $num ? 'selected' : '' ?>><?= $name ?></option>
            <?php endforeach; ?>
        </select>

        <select name="tahun" class="filter-select" onchange="document.getElementById('filterForm').submit()">
            <?php for ($y = date('Y') - 2; $y <= date('Y'); $y++): ?>
                <option value="<?= $y ?>" <?= $tahun == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </form>

    <button class="btn-print" onclick="window.print()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <polyline points="6 9 6 2 18 2 18 9"></polyline>
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
            <rect x="6" y="14" width="12" height="8"></rect>
        </svg>
        Cetak PDF
    </button>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Laporan Absensi Periode <?= $nama_bulan[$bulan] ?> <?= $tahun ?></h3>
    </div>

    <div class="table-container">
        <table class="table-data">
            <thead>
                <tr>
                    <th rowspan="2" width="40">NO</th>
                    <th rowspan="2" class="text-left">NIK</th>
                    <th rowspan="2" class="text-left" width="200">NAMA LENGKAP</th>
                    <th colspan="<?= $jumlah_hari ?>">TGL</th>
                    <th rowspan="2" width="30">H</th>
                    <th rowspan="2" width="30">I</th>
                    <th rowspan="2" width="30">A</th>
                    <th rowspan="2" width="30">S</th>
                    <th rowspan="2" width="30">C</th>
                    <th rowspan="2">TOTAL<br>HADIR</th>
                </tr>
                <tr>
                    <?php for ($i = 1; $i <= $jumlah_hari; $i++): ?>
                        <th style="min-width: 25px; padding: 8px 4px;"><?= $i ?></th>
                    <?php endfor; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $role_safe = mysqli_real_escape_string($conn, $role_filter);
                $afdeling_kerani = isset($_SESSION['afdeling']) ? mysqli_real_escape_string($conn, $_SESSION['afdeling']) : '';

                if (!empty($afdeling_kerani)) {
                    $query = mysqli_query($conn, "SELECT id, nik, name FROM users WHERE role='$role_safe' AND afdeling='$afdeling_kerani' ORDER BY name ASC");
                } else {
                    $query = mysqli_query($conn, "SELECT id, nik, name FROM users WHERE role='$role_safe' ORDER BY name ASC");
                }

                $all_users_data = [];
                while ($user = mysqli_fetch_assoc($query)) {
                    $all_users_data[] = $user;
                }

                $no = 1;
                foreach ($all_users_data as $user):
                ?>
                    <tr>
                        <td style="color: var(--text-muted); font-weight: 600;"><?= $no++ ?></td>
                        <td class="text-left" style="font-weight: 700;"><?= htmlspecialchars($user['nik']) ?></td>
                        <td class="text-left"><?= htmlspecialchars($user['name']) ?></td>

                        <?php
                        $t_hadir = 0;
                        $t_izin = 0;
                        $t_sakit = 0;
                        $t_cuti = 0;
                        $t_alpha = 0;
                        $user_absen_data = []; // store for print
                        for ($i = 1; $i <= $jumlah_hari; $i++):
                            $tgl_str = sprintf("%04d-%02d-%02d", $tahun, $bulan, $i);
                            $cek_absen = mysqli_query($conn, "SELECT status_kehadiran FROM absensis WHERE user_id={$user['id']} AND tanggal='$tgl_str'");

                            if (mysqli_num_rows($cek_absen) > 0) {
                                $row_abs = mysqli_fetch_assoc($cek_absen);
                                $status = strtolower($row_abs['status_kehadiran']);
                                $user_absen_data[$i] = $status;
                                if (in_array($status, ['alpha', 'alpa', 'alfa'])) {
                                    $t_alpha++;
                                    echo '<td><span class="status-badge status-a">A</span></td>';
                                } elseif ($status == 'izin') {
                                    $t_izin++;
                                    echo '<td><span class="status-badge status-i">I</span></td>';
                                } elseif ($status == 'sakit') {
                                    $t_sakit++;
                                    echo '<td><span class="status-badge status-s">S</span></td>';
                                } elseif ($status == 'cuti') {
                                    $t_cuti++;
                                    echo '<td><span class="status-badge status-c">C</span></td>';
                                } else {
                                    $t_hadir++;
                                    echo '<td><span class="status-badge status-h">H</span></td>';
                                }
                            } else {
                                $user_absen_data[$i] = null;
                                echo '<td><span style="color: #cbd5e1;">-</span></td>';
                            }
                        ?>
                        <?php endfor; ?>
                        
                        <?php
                        // Simpan total ke dalam array untuk digunakan di cetak dokumen resmi
                        $user['t_hadir'] = $t_hadir;
                        $user['t_izin'] = $t_izin;
                        $user['t_alpha'] = $t_alpha;
                        $user['t_sakit'] = $t_sakit;
                        $user['t_cuti'] = $t_cuti;
                        $user['absen_data'] = $user_absen_data;
                        // Replace array element with modified user array
                        $all_users_data[$no-2] = $user; 
                        ?>

                        <td style="font-weight: 700; color: #166534;"><?= $t_hadir ?></td>
                        <td style="font-weight: 700; color: #075985;"><?= $t_izin ?></td>
                        <td style="font-weight: 700; color: #991b1b;"><?= $t_alpha ?></td>
                        <td style="font-weight: 700; color: #5b21b6;"><?= $t_sakit ?></td>
                        <td style="font-weight: 700; color: #9a3412;"><?= $t_cuti ?></td>
                        <td style="font-weight: 800; color: #0f172a;"><?= $t_hadir ?>/<?= date('t') ?></td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($all_users_data)): ?>
                    <tr>
                        <td colspan="<?= $jumlah_hari + 9 ?>" style="text-align:center; padding:20px;">Belum ada data personil.</td>
                    </tr>
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
        <h3>Laporan Absensi &mdash; <?= $nama_bulan[$bulan] ?> <?= $tahun ?></h3>
    </div>
    <div class="doc-meta">
        <div><strong>Jabatan&nbsp;&nbsp;:</strong> <?= !empty($role_filter) ? ucfirst(htmlspecialchars($role_filter)) : 'Semua Jabatan' ?></div>
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
            foreach ($all_users_data as $user):
            ?>
                <tr>
                    <td class="text-center"><?= $pno++ ?></td>
                    <td><?= htmlspecialchars($user['nik']) ?></td>
                    <td><?= htmlspecialchars($user['name']) ?></td>
                    <?php
                    for ($i = 1; $i <= $jumlah_hari; $i++):
                        $st = $user['absen_data'][$i] ?? null;
                        $k = '';
                        $dc = '';
                        if (in_array($st, ['hadir', 'tepat_waktu', 'terlambat'])) {
                            $k = 'H';
                            $dc = 'doc-status-H';
                        } elseif (in_array($st, ['alpha', 'alpa', 'alfa'])) {
                            $k = 'A';
                            $dc = 'doc-status-A';
                        } elseif ($st == 'izin') {
                            $k = 'I';
                            $dc = 'doc-status-I';
                        } elseif ($st == 'sakit') {
                            $k = 'S';
                            $dc = 'doc-status-S';
                        } elseif ($st == 'cuti') {
                            $k = 'C';
                            $dc = 'doc-status-C';
                        }
                    ?>
                        <td class="text-center <?= $dc ?>" style="font-size:8pt; font-weight:bold;"><?= $k ?></td>
                    <?php endfor; ?>
                    <td class="text-center" style="font-weight:bold;"><?= $user['t_hadir'] ?></td>
                    <td class="text-center" style="font-weight:bold;"><?= $user['t_izin'] ?></td>
                    <td class="text-center" style="font-weight:bold;"><?= $user['t_alpha'] ?></td>
                    <td class="text-center" style="font-weight:bold;"><?= $user['t_sakit'] ?></td>
                    <td class="text-center" style="font-weight:bold;"><?= $user['t_cuti'] ?></td>
                    <td class="text-center" style="font-weight:bold;"><?= $user['t_hadir'] ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($all_users_data)): ?>
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
            <span class="sig-name">Manda</span>
            <div style="font-weight:bold;">Pengawas Afd 9</div>
        </div>
        <div class="doc-signature-col">
            <p>Disusun oleh,</p>
            <span class="sig-name">Arsyad</span>
            <div style="font-weight:bold;">Kerani Afd 9</div>
        </div>
    </div>

    <div class="doc-footer">
        Dokumen ini dicetak secara otomatis oleh Sistem Informasi PT Damai Jaya Lestari pada <?= date('d F Y \p\u\k\u\l H:i') ?> WIB.
    </div>
</div>

<?php include 'templates/footer.php'; ?>