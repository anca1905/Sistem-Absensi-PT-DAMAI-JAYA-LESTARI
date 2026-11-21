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

// Sinkronisasi status Alpha otomatis untuk periode bulan yang dilihat
checkAndSetAlpha($conn, $bulan, $tahun);

$jumlah_hari = date('t', mktime(0, 0, 0, $bulan, 1, $tahun));

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
$periode_judul = $nama_bulan[$bulan] . ' ' . $tahun;

// List afdeling untuk dropdown & pengecekan status
$list_afdeling = [];
$afd_q = mysqli_query($conn, "SELECT DISTINCT afdeling FROM users WHERE role IN ('karyawan', 'kerani', 'mandor', 'pengawas') AND afdeling IS NOT NULL AND afdeling != '' ORDER BY afdeling ASC");
if ($afd_q) {
    while ($afd_row = mysqli_fetch_assoc($afd_q)) {
        $list_afdeling[] = $afd_row['afdeling'];
    }
}
if (empty($list_afdeling)) {
    $afd_q2 = mysqli_query($conn, "SELECT nama_afdeling FROM afdelings ORDER BY nama_afdeling ASC");
    if ($afd_q2) {
        while ($afd_row2 = mysqli_fetch_assoc($afd_q2)) {
            $list_afdeling[] = $afd_row2['nama_afdeling'];
        }
    }
}

// --- LOGIKA CEK PENGAJUAN LAPORAN OLEH KERANI ---
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

$all_users_data = [];
$data_absen = [];

// Query data absensi HANYA jika laporan sudah dikirim oleh Kerani
if ($bisa_tampil) {
    $where_conditions = ["role != 'admin'"];
    if (!empty($jabatan)) {
        $jabatan_safe = mysqli_real_escape_string($conn, $jabatan);
        $where_conditions[] = "(role = '$jabatan_safe' OR jabatan = '$jabatan_safe')";
    }
    if (!empty($afdeling)) {
        $where_conditions[] = "afdeling = '" . mysqli_real_escape_string($conn, $afdeling) . "'";
    } else {
        $afd_in = array_map(function($a) use ($conn) { return "'" . mysqli_real_escape_string($conn, $a) . "'"; }, $afdeling_diajukan);
        $where_conditions[] = "afdeling IN (" . implode(',', $afd_in) . ")";
    }
    $where_clause  = "WHERE " . implode(' AND ', $where_conditions);
    $query_users   = mysqli_query($conn, "SELECT * FROM users $where_clause ORDER BY afdeling ASC, name ASC");
    if ($query_users) {
        while ($u = mysqli_fetch_assoc($query_users)) {
            $all_users_data[] = $u;
        }
    }

    $bulan_int = (int)$bulan;
    $query_absen = mysqli_query($conn, "
        SELECT user_id, DAY(tanggal) as hari, status_kehadiran 
        FROM absensis 
        WHERE MONTH(tanggal) = $bulan_int AND YEAR(tanggal) = $tahun
    ");
    if ($query_absen) {
        while ($row = mysqli_fetch_assoc($query_absen)) {
            $data_absen[$row['user_id']][$row['hari']] = $row['status_kehadiran'];
        }
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

?>

<style>
    /* Sticky columns untuk tabel absensi lebar */
    .table-absen {
        width: 100%;
        border-collapse: collapse;
        white-space: nowrap;
    }

    .table-absen th,
    .table-absen td {
        padding: 10px 8px;
        text-align: center;
        border-bottom: 1px solid #e2e8f0;
        font-size: 13px;
    }

    .table-absen th {
        background-color: #f8fafc;
        color: #475569;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 11px;
        border-bottom: 2px solid #e2e8f0;
    }

    .table-absen tbody tr {
        background-color: #fff;
    }

    .table-absen tbody tr:hover td {
        background-color: #f1f5f9;
    }

    .table-absen thead tr:first-child th:nth-child(1),
    .table-absen tbody td:nth-child(1) {
        position: sticky;
        left: 0;
        z-index: 2;
        background-color: inherit;
    }

    .table-absen thead tr:first-child th:nth-child(2),
    .table-absen tbody td:nth-child(2) {
        position: sticky;
        left: 40px;
        z-index: 2;
        background-color: inherit;
    }

    .table-absen thead tr:first-child th:nth-child(3),
    .table-absen tbody td:nth-child(3) {
        position: sticky;
        left: 160px;
        z-index: 2;
        background-color: inherit;
        border-right: 2px solid #cbd5e1;
    }

    .table-absen thead tr:first-child th:nth-child(1),
    .table-absen thead tr:first-child th:nth-child(2),
    .table-absen thead tr:first-child th:nth-child(3) {
        z-index: 3;
        background-color: #f8fafc;
    }

    /* Status badges UI */
    .ab-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        border-radius: 5px;
        font-weight: 700;
        font-size: 11px;
    }

    .ab-H {
        background-color: #dcfce7;
        color: #166534;
    }

    .ab-A {
        background-color: #fee2e2;
        color: #991b1b;
    }

    .ab-T {
        background-color: #fef9c3;
        color: #854d0e;
    }

    .ab-I {
        background-color: #e0f2fe;
        color: #075985;
    }

    .ab-S {
        background-color: #ede9fe;
        color: #5b21b6;
    }

    .ab-C {
        background-color: #ffedd5;
        color: #9a3412;
    }
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
                    <option value="mandor" <?= $jabatan == 'mandor'   ? 'selected' : '' ?>>Mandor</option>
                    <option value="karyawan" <?= $jabatan == 'karyawan' ? 'selected' : '' ?>>Karyawan</option>
                    <option value="pengawas" <?= $jabatan == 'pengawas' ? 'selected' : '' ?>>Pengawas</option>
                    <option value="kerani" <?= $jabatan == 'kerani'   ? 'selected' : '' ?>>Kerani</option>
                </select>

                <select name="bulan" class="form-select" onchange="document.getElementById('formFilter').submit();">
                    <?php foreach ($nama_bulan as $num => $name): ?>
                        <option value="<?= $num ?>" <?= $bulan == $num ? 'selected' : '' ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="tahun" class="form-select" onchange="document.getElementById('formFilter').submit();">
                    <?php for ($y = date('Y') - 2; $y <= date('Y'); $y++): ?>
                        <option value="<?= $y ?>" <?= $tahun == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="report-title-center">
                <h2>Laporan Absensi</h2>
                <p>Periode: <strong><?= $periode_judul ?></strong>
                    <?= !empty($jabatan) ? ' &mdash; ' . ucfirst($jabatan) : '' ?>
                    <?= !empty($afdeling) ? ' | ' . htmlspecialchars($afdeling) : '' ?>
                </p>
            </div>

            <?php if ($bisa_tampil): ?>
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                    Cetak Dokumen
                </button>
            <?php else: ?>
                <button type="button" class="btn btn-primary" onclick="alert('Laporan absensi belum dikirim oleh Kerani untuk periode ini.');" style="opacity: 0.5; cursor: not-allowed;" title="Laporan belum diserahkan oleh Kerani">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                    Cetak Dokumen (Terkunci)
                </button>
            <?php endif; ?>
        </div>
    </form>

    <?php if (empty($afdeling)): ?>
        <!-- Status Seluruh Afdeling -->
        <div style="background: white; border-radius: 12px; padding: 14px 18px; border: 1px solid #e2e8f0; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
            <div style="font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 10px;">
                Status Pengajuan Laporan oleh Kerani (<?= $periode_judul ?>):
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <?php foreach ($list_afdeling as $afd_item): 
                    $st_item = $status_per_afdeling[$afd_item] ?? null;
                    $st_code = $st_item ? $st_item['status'] : 'draft';
                ?>
                    <a href="laporan.php?bulan=<?= $bulan ?>&tahun=<?= $tahun ?>&afdeling=<?= urlencode($afd_item) ?>&jabatan=<?= urlencode($jabatan) ?>" style="text-decoration: none; display: flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; transition: all 0.15s;
                        <?php if ($st_code === 'disetujui'): ?>
                            background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0;
                        <?php elseif ($st_code === 'menunggu_validasi'): ?>
                            background: #fef3c7; color: #b45309; border: 1px solid #fde68a;
                        <?php elseif ($st_code === 'ditolak'): ?>
                            background: #fee2e2; color: #991b1b; border: 1px solid #fecaca;
                        <?php else: ?>
                            background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0;
                        <?php endif; ?>
                    ">
                        <span><?= htmlspecialchars($afd_item) ?>:</span>
                        <?php if ($st_code === 'disetujui'): ?>
                            <span>✅ Disetujui Keuangan</span>
                        <?php elseif ($st_code === 'menunggu_validasi'): ?>
                            <span>⏳ Menunggu Validasi Keuangan</span>
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
        <!-- Status Spesifik Afdeling -->
        <div style="margin-bottom: 20px; border-radius: 12px; padding: 14px 18px; display: flex; align-items: center; gap: 14px;
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
            <div style="font-size: 24px;">
                <?php if (!$info_pengajuan): ?>⚠️<?php elseif ($info_pengajuan['status'] === 'menunggu_validasi'): ?>⏳<?php elseif ($info_pengajuan['status'] === 'disetujui'): ?>✅<?php else: ?>❌<?php endif; ?>
            </div>
            <div>
                <div style="font-size: 14px; font-weight: 800;">
                    <?php if (!$info_pengajuan): ?>
                        Laporan Belum Dikirim oleh Kerani <?= htmlspecialchars($afdeling) ?>
                    <?php elseif ($info_pengajuan['status'] === 'menunggu_validasi'): ?>
                        Laporan Telah Dikirim oleh Kerani (Menunggu Validasi Keuangan)
                    <?php elseif ($info_pengajuan['status'] === 'disetujui'): ?>
                        Laporan Telah Disetujui & Divalidasi oleh Bagian Keuangan
                    <?php elseif ($info_pengajuan['status'] === 'ditolak'): ?>
                        Laporan Sedang Dalam Proses Revisi oleh Kerani
                    <?php endif; ?>
                </div>
                <div style="font-size: 12px; margin-top: 2px; opacity: 0.9;">
                    <?php if (!$info_pengajuan): ?>
                        Kerani Afdeling <?= htmlspecialchars($afdeling) ?> belum menyelesaikan cross-check dan belum menyerahkan laporan periode <?= $periode_judul ?>. Data absensi belum tersedia untuk Admin.
                    <?php elseif ($info_pengajuan['status'] === 'menunggu_validasi'): ?>
                        Dikirim pada: <strong><?= date('d/m/Y H:i', strtotime($info_pengajuan['tanggal_kirim'])) ?></strong> oleh Kerani: <strong><?= htmlspecialchars($info_pengajuan['nama_kerani'] ?: 'Kerani') ?></strong>.
                    <?php elseif ($info_pengajuan['status'] === 'disetujui'): ?>
                        Divalidasi pada: <strong><?= date('d/m/Y H:i', strtotime($info_pengajuan['tanggal_validasi'])) ?></strong>. Laporan ini resmi sebagai dasar proses penggajian.
                    <?php elseif ($info_pengajuan['status'] === 'ditolak'): ?>
                        Catatan revisi: <em>"<?= htmlspecialchars($info_pengajuan['catatan_keuangan']) ?>"</em>. Menunggu Kerani memperbaiki dan mengirimkan kembali.
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!$bisa_tampil): ?>
        <div style="text-align: center; padding: 60px 20px; background: white; border-radius: 12px; border: 1.5px dashed #cbd5e1; margin-top: 10px;">
            <div style="font-size: 52px; line-height: 1; margin-bottom: 16px;">📭</div>
            <h3 style="font-size: 18px; font-weight: 800; color: #1e293b; margin-bottom: 8px;">
                <?= !empty($afdeling) ? 'Laporan Belum Dikirim oleh Kerani ' . htmlspecialchars($afdeling) : 'Belum Ada Laporan Absensi yang Dikirim' ?>
            </h3>
            <p style="font-size: 14px; color: #64748b; max-width: 540px; margin: 0 auto 16px; line-height: 1.6;">
                <?php if (!empty($afdeling)): ?>
                    Data absensi Afdeling <strong><?= htmlspecialchars($afdeling) ?></strong> untuk periode <strong><?= $periode_judul ?></strong> belum dikirimkan oleh Kerani. Data hanya dapat diakses setelah laporan resmi diajukan oleh Kerani.
                <?php else: ?>
                    Belum ada Kerani afdeling yang mengirimkan laporan absensi untuk periode <strong><?= $periode_judul ?></strong>. Data absensi baru akan muncul setelah diserahkan oleh masing-masing Kerani.
                <?php endif; ?>
            </p>
            <div style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 999px; font-size: 12px; font-weight: 600; color: #64748b;">
                <i class="fa-solid fa-clock-rotate-left" style="color: #f59e0b;"></i>
                Menunggu penyerahan laporan resmi dari Kerani
            </div>
        </div>
    <?php else: ?>
        <?php if (empty($afdeling) && count($afdeling_diajukan) < count($list_afdeling)): ?>
            <div style="margin-bottom: 14px; padding: 10px 16px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; font-size: 12px; color: #1e40af; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-circle-info"></i>
                <span>Menampilkan data dari Afdeling yang sudah menyerahkan laporan: <strong><?= implode(', ', $afdeling_diajukan) ?></strong>. Afdeling lainnya belum mengirimkan laporan periode ini.</span>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table-absen">
                <thead>
                    <tr>
                        <th rowspan="2" style="width:40px;">NO</th>
                        <th rowspan="2" style="text-align:left; min-width:120px;">NIK</th>
                        <th rowspan="2" style="text-align:left; min-width:180px;">NAMA</th>
                        <th colspan="<?= $jumlah_hari ?>">TANGGAL</th>
                        <th rowspan="2" style="min-width:90px;">JABATAN</th>
                        <th rowspan="2" style="min-width:30px;">H</th>
                        <th rowspan="2" style="min-width:30px;">I</th>
                        <th rowspan="2" style="min-width:30px;">A</th>
                        <th rowspan="2" style="min-width:30px;">S</th>
                        <th rowspan="2" style="min-width:30px;">C</th>
                        <th rowspan="2" style="min-width:80px;">TOTAL<br>HADIR</th>
                    </tr>
                    <tr>
                        <?php for ($i = 1; $i <= $jumlah_hari; $i++): ?>
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

                                <?php
                                $t_hadir = 0;
                                $t_izin = 0;
                                $t_alpha = 0;
                                $t_sakit = 0;
                                $t_cuti = 0;
                                for ($i = 1; $i <= $jumlah_hari; $i++):
                                    $st = $data_absen[$user['id']][$i] ?? null;
                                    $kode = '';
                                    $cls = '';
                                    if (in_array(strtolower((string)$st), ['hadir', 'tepat_waktu'])) {
                                        $kode = 'H';
                                        $cls = 'ab-H';
                                        $t_hadir++;
                                    } elseif (in_array(strtolower((string)$st), ['alpha', 'alpa', 'alfa', 'terlambat'])) {
                                        $kode = 'A';
                                        $cls = 'ab-A';
                                        $t_alpha++;
                                    } elseif (strtolower((string)$st) == 'izin') {
                                        $kode = 'I';
                                        $cls = 'ab-I';
                                        $t_izin++;
                                    } elseif (strtolower((string)$st) == 'sakit') {
                                        $kode = 'S';
                                        $cls = 'ab-S';
                                        $t_sakit++;
                                    } elseif (strtolower((string)$st) == 'cuti') {
                                        $kode = 'C';
                                        $cls = 'ab-C';
                                        $t_cuti++;
                                    }
                                ?>
                                    <td><?php if ($kode): ?><span class="ab-badge <?= $cls ?>"><?= $kode ?></span><?php else: ?><span style="color:#e2e8f0;">·</span><?php endif; ?></td>
                                <?php endfor; ?>

                                <td><?= ucfirst(htmlspecialchars($user['jabatan'] ?? '-')) ?></td>
                                <td style="font-weight:700; color:#166534;"><?= $t_hadir ?></td>
                                <td style="font-weight:700; color:#075985;"><?= $t_izin ?></td>
                                <td style="font-weight:700; color:#991b1b;"><?= $t_alpha ?></td>
                                <td style="font-weight:700; color:#5b21b6;"><?= $t_sakit ?></td>
                                <td style="font-weight:700; color:#9a3412;"><?= $t_cuti ?></td>
                                <td style="font-weight:700;"><?= $t_hadir ?> Hari</td>
                            </tr>
                        <?php
                        endforeach;
                    else:
                        ?>
                        <tr>
                            <td colspan="<?= $jumlah_hari + 10 ?>" style="padding:30px; color:#94a3b8; text-align:center;">Tidak ada data karyawan ditemukan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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

    <?php if (!$bisa_tampil): ?>
        <div style="text-align: center; padding: 60px 20px; border: 1.5px dashed #cbd5e1; border-radius: 8px; margin: 30px 0;">
            <h3 style="font-size: 14pt; color: #475569; margin-bottom: 8px;">LAPORAN BELUM DIKIRIM OLEH KERANI</h3>
            <p style="font-size: 10pt; color: #94a3b8; margin: 0;">Data absensi belum resmi diserahkan oleh Kerani untuk periode <?= $periode_judul ?>.</p>
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
                    <th rowspan="2" style="min-width:120px; text-align:left;">Nama Karyawan</th>
                    <th colspan="<?= $jumlah_hari ?>">Tanggal</th>
                    <th rowspan="2" style="width:60px;">Jabatan</th>
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
                    $total = 0;
                ?>
                    <tr>
                        <td class="text-center"><?= $pno++ ?></td>
                        <td><?= htmlspecialchars($user['nik']) ?></td>
                        <td><?= htmlspecialchars($user['name']) ?></td>
                        <?php
                        $p_h = 0;
                        $p_i = 0;
                        $p_a = 0;
                        $p_s = 0;
                        $p_c = 0;
                        for ($i = 1; $i <= $jumlah_hari; $i++):
                            $st = $data_absen[$user['id']][$i] ?? null;
                            $k = '';
                            $dc = '';
                            if (in_array(strtolower((string)$st), ['hadir', 'tepat_waktu'])) {
                                $k = 'H';
                                $dc = 'doc-status-H';
                                $p_h++;
                            } elseif (in_array(strtolower((string)$st), ['alpha', 'alpa', 'alfa', 'terlambat'])) {
                                $k = 'A';
                                $dc = 'doc-status-A';
                                $p_a++;
                            } elseif (strtolower((string)$st) == 'izin') {
                                $k = 'I';
                                $dc = 'doc-status-I';
                                $p_i++;
                            } elseif (strtolower((string)$st) == 'sakit') {
                                $k = 'S';
                                $dc = 'doc-status-S';
                                $p_s++;
                            } elseif (strtolower((string)$st) == 'cuti') {
                                $k = 'C';
                                $dc = 'doc-status-C';
                                $p_c++;
                            }
                        ?>
                            <td class="text-center <?= $dc ?>" style="font-size:8pt; font-weight:bold;"><?= $k ?></td>
                        <?php endfor; ?>
                        <td class="text-center"><?= ucfirst(htmlspecialchars($user['jabatan'] ?? '-')) ?></td>
                        <td class="text-center" style="font-weight:bold;"><?= $p_h ?></td>
                        <td class="text-center" style="font-weight:bold;"><?= $p_i ?></td>
                        <td class="text-center" style="font-weight:bold;"><?= $p_a ?></td>
                        <td class="text-center" style="font-weight:bold;"><?= $p_s ?></td>
                        <td class="text-center" style="font-weight:bold;"><?= $p_c ?></td>
                        <td class="text-center" style="font-weight:bold;"><?= $p_h ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($all_users_data)): ?>
                    <tr>
                        <td colspan="<?= $jumlah_hari + 10 ?>" class="text-center" style="padding:16px;">Tidak ada data.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>

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

<?php include '../templates/footer.php'; ?>