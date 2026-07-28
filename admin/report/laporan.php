<?php
require '../../config/config.php';
include '../templates/header.php';
<style type="text/css" media="print">@page { size: landscape; }</style>


// Filter parameter
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$jabatan = isset($_GET['jabatan']) ? $_GET['jabatan'] : '';
$afdeling = isset($_GET['afdeling']) ? $_GET['afdeling'] : '';

$jumlah_hari = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);

// Array of Indonesian months for display
$nama_bulan = array(
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
    '04' => 'April', '05' => 'Mei', '06' => 'Juni',
    '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
    '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
);
$periode_judul = $nama_bulan[$bulan] . ' ' . $tahun;

// Ambil data users (karyawan, mandor, dll)
$where_conditions = ["role != 'admin'"];
if (!empty($jabatan)) {
    // Dropdown filter jabatan di UI sebenarnya memilih role (mandor, karyawan, dll)
    $jabatan_safe = mysqli_real_escape_string($conn, $jabatan);
    $where_conditions[] = "(role = '$jabatan_safe' OR jabatan = '$jabatan_safe')";
}
if (!empty($afdeling)) {
    $where_conditions[] = "afdeling = '" . mysqli_real_escape_string($conn, $afdeling) . "'";
}
$where_clause = "WHERE " . implode(' AND ', $where_conditions);
$query_users = mysqli_query($conn, "SELECT * FROM users $where_clause ORDER BY name ASC");

// Ambil data absensi sebulan untuk optimasi query
$query_absen = mysqli_query($conn, "
    SELECT user_id, DAY(tanggal) as hari, status_kehadiran 
    FROM absensis 
    WHERE MONTH(tanggal) = '$bulan' AND YEAR(tanggal) = '$tahun'
");
$data_absen = [];
while ($row = mysqli_fetch_assoc($query_absen)) {
    $data_absen[$row['user_id']][$row['hari']] = $row['status_kehadiran'];
}
?>

<style>
    /* Global Report Styles */
    .report-wrapper {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        padding: 24px;
        animation: fadeIn 0.4s ease-out;
        border: 1px solid #f1f5f9;
    }

    /* Top Action Bar (Filters) */
    .report-top-bar {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 24px;
        gap: 20px;
    }

    .filter-group-left {
        display: flex;
        flex-direction: column;
        gap: 12px;
        flex: 1;
        min-width: 250px;
    }

    .filter-group-right {
        display: flex;
        align-items: flex-end;
        gap: 12px;
        flex-wrap: wrap;
    }

    .report-title-center {
        text-align: center;
        flex: 2;
        min-width: 300px;
    }

    .report-title-center h2 {
        font-size: 24px;
        font-weight: 800;
        color: #1e293b;
        margin-bottom: 4px;
        letter-spacing: -0.5px;
    }

    .report-title-center p {
        color: #64748b;
        font-size: 14px;
        margin: 0;
    }

    /* Inputs & Selects */
    .form-select, .form-input {
        padding: 10px 16px;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        background-color: #f8fafc;
        font-size: 14px;
        color: #334155;
        font-family: inherit;
        outline: none;
        transition: all 0.2s;
        box-shadow: inset 0 1px 2px rgba(0,0,0,0.01);
    }

    .form-select:focus, .form-input:focus {
        border-color: #3b82f6;
        background-color: #ffffff;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }

    /* Buttons */
    .btn-action {
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.2s;
        border: none;
        height: 40px;
    }

    .btn-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        box-shadow: 0 4px 10px rgba(59, 130, 246, 0.25);
    }

    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 15px rgba(59, 130, 246, 0.35);
    }

    .btn-success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        box-shadow: 0 4px 10px rgba(16, 185, 129, 0.25);
    }

    .btn-success:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 15px rgba(16, 185, 129, 0.35);
    }

    /* Table Styles */
    .table-responsive {
        overflow-x: auto;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px rgba(0,0,0,0.02);
    }

    .table-absen {
        width: 100%;
        border-collapse: collapse;
        white-space: nowrap;
        font-size: 13px;
    }

    .table-absen th, .table-absen td {
        padding: 12px 10px;
        border: 1px solid #e2e8f0;
        text-align: center;
    }

    .table-absen th {
        background-color: #f8fafc;
        color: #475569;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.5px;
    }

    .table-absen thead tr:first-child th {
        border-bottom: 2px solid #cbd5e1;
    }

    .table-absen tbody tr:hover {
        background-color: #f1f5f9;
    }

    .text-left {
        text-align: left !important;
    }

    /* Sticky Columns */
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
        left: 40px; /* Lebar kolom NO */
        z-index: 2;
        background-color: inherit;
    }
    
    .table-absen thead tr:first-child th:nth-child(3),
    .table-absen tbody td:nth-child(3) {
        position: sticky;
        left: 160px; /* 40px + 120px */
        z-index: 2;
        background-color: inherit;
        border-right: 2px solid #cbd5e1; /* Pembatas visual */
    }

    .table-absen thead tr:first-child th:nth-child(1),
    .table-absen thead tr:first-child th:nth-child(2),
    .table-absen thead tr:first-child th:nth-child(3) {
        z-index: 3; /* Header harus di atas cell */
        background-color: #f8fafc;
    }
    
    .table-absen tbody tr {
        background-color: #ffffff;
    }
    
    .table-absen tbody tr:hover td {
        background-color: #f1f5f9;
    }

    /* Status Badges */
    .status-badge {
        display: inline-block;
        width: 24px;
        height: 24px;
        line-height: 24px;
        border-radius: 6px;
        font-weight: bold;
        font-size: 11px;
    }
    
    .status-h { background-color: #dcfce7; color: #166534; } /* Hadir */
    .status-a { background-color: #fee2e2; color: #991b1b; } /* Alpha */
    .status-t { background-color: #fef9c3; color: #854d0e; } /* Terlambat */
    .status-i { background-color: #e0f2fe; color: #075985; } /* Izin */
    .status-s { background-color: #ede9fe; color: #5b21b6; } /* Sakit */
    .status-c { background-color: #ffedd5; color: #9a3412; } /* Cuti */

    /* Animasi Masuk */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="report-wrapper">
    <form method="GET" action="laporan.php" id="formFilter">
        <div class="report-top-bar">
            
            <!-- Kiri: Filter Afdeling & Jabatan -->
            <div class="filter-group-left">
                <select name="afdeling" class="form-select" onchange="document.getElementById('formFilter').submit();">
                    <option value="">-- Pilih Afdeling --</option>
                    <?php 
                    $afd_query = mysqli_query($conn, "SELECT nama_afdeling FROM afdelings ORDER BY nama_afdeling ASC");
                    while ($afd = mysqli_fetch_assoc($afd_query)): 
                    ?>
                        <option value="<?= htmlspecialchars($afd['nama_afdeling']) ?>" <?= $afdeling == $afd['nama_afdeling'] ? 'selected' : '' ?>><?= htmlspecialchars($afd['nama_afdeling']) ?></option>
                    <?php endwhile; ?>
                </select>

                <select name="jabatan" class="form-select" onchange="document.getElementById('formFilter').submit();">
                    <option value="">-- Pilih Jabatan --</option>
                    <option value="mandor" <?= $jabatan == 'mandor' ? 'selected' : '' ?>>Mandor</option>
                    <option value="karyawan" <?= $jabatan == 'karyawan' ? 'selected' : '' ?>>Karyawan</option>
                    <option value="pengawas" <?= $jabatan == 'pengawas' ? 'selected' : '' ?>>Pengawas</option>
                    <option value="kerani" <?= $jabatan == 'kerani' ? 'selected' : '' ?>>Kerani</option>
                </select>
            </div>

            <!-- Tengah: Judul -->
            <div class="report-title-center">
                <h2>Laporan Absensi</h2>
                <p>Periode: <strong><?= $periode_judul ?></strong></p>
                <?php if(!empty($jabatan)) echo "<p style='font-size:12px; margin-top:4px;'>Filter: ".ucfirst($jabatan)."</p>"; ?>
            </div>

            <!-- Kanan: Filter Periode & Cetak -->
            <div class="filter-group-right">
                <select name="bulan" class="form-select" onchange="document.getElementById('formFilter').submit();">
                    <?php foreach($nama_bulan as $num => $name): ?>
                        <option value="<?= $num ?>" <?= $bulan == $num ? 'selected' : '' ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="tahun" class="form-select" onchange="document.getElementById('formFilter').submit();">
                    <?php for($y = date('Y') - 2; $y <= date('Y'); $y++): ?>
                        <option value="<?= $y ?>" <?= $tahun == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>

                <button type="button" class="btn-action btn-success" onclick="window.print()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                    Cetak
                </button>
            </div>

        </div>
    </form>

    <!-- Tabel Absensi -->
    <div class="table-responsive">
        <table class="table-absen">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 40px;">NO</th>
                    <th rowspan="2" class="text-left" style="min-width: 120px;">NIK</th>
                    <th rowspan="2" class="text-left" style="min-width: 200px;">NAMA</th>
                    <th colspan="<?= $jumlah_hari ?>">TANGGAL</th>
                    <th rowspan="2" style="min-width: 100px;">JABATAN</th>
                    <th rowspan="2" style="min-width: 100px;">TOTAL<br>KEHADIRAN</th>
                </tr>
                <tr>
                    <?php for($i = 1; $i <= $jumlah_hari; $i++): ?>
                        <th style="min-width: 35px;"><?= $i ?></th>
                    <?php endfor; ?>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                if(mysqli_num_rows($query_users) > 0):
                    while($user = mysqli_fetch_assoc($query_users)): 
                        $total_hadir = 0;
                ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td class="text-left"><?= htmlspecialchars($user['nik']) ?></td>
                        <td class="text-left" style="font-weight: 600;"><?= htmlspecialchars($user['name']) ?></td>
                        
                        <?php 
                        for($i = 1; $i <= $jumlah_hari; $i++): 
                            $status_absen = isset($data_absen[$user['id']][$i]) ? $data_absen[$user['id']][$i] : null;
                            $kode = '-';
                            $class = '';

                            if(in_array($status_absen, ['hadir', 'tepat_waktu', 'terlambat'])) {
                                $kode = 'H'; // Hadir
                                $class = 'status-h';
                                $total_hadir++;
                            } elseif ($status_absen == 'alpha') {
                                $kode = 'A'; // Alpha
                                $class = 'status-a';
                            } elseif ($status_absen == 'izin') {
                                $kode = 'I'; // Izin
                                $class = 'status-i';
                            } elseif ($status_absen == 'sakit') {
                                $kode = 'S'; // Sakit
                                $class = 'status-s';
                            } elseif ($status_absen == 'cuti') {
                                $kode = 'C'; // Cuti
                                $class = 'status-c';
                            } else {
                                // Default kosong (belum diabsen)
                            }
                        ?>
                            <td>
                                <?php if($kode != '-'): ?>
                                    <span class="status-badge <?= $class ?>"><?= $kode ?></span>
                                <?php else: ?>
                                    <span style="color: #cbd5e1;">-</span>
                                <?php endif; ?>
                            </td>
                        <?php endfor; ?>

                        <td><?= ucfirst(htmlspecialchars($user['jabatan'] ?? 'Karyawan')) ?></td>
                        <td style="font-weight: 700; color: #0f172a;"><?= $total_hadir ?> Hari</td>
                    </tr>
                <?php 
                    endwhile;
                else: 
                ?>
                    <tr>
                        <td colspan="<?= $jumlah_hari + 5 ?>" style="padding: 30px; color: #64748b;">
                            Tidak ada data karyawan yang ditemukan.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Khusus untuk print media query -->
<style>
@media print {
    body * {
        visibility: hidden;
    }
    .main-content {
        margin: 0 !important;
        padding: 0 !important;
    }
    .report-wrapper, .report-wrapper * {
        visibility: visible;
    }
    .report-wrapper {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        box-shadow: none;
        border: none;
        padding: 0;
    }
    .btn-action, .top-header, .sidebar, .sidebar-overlay {
        display: none !important;
    }
    .form-select {
        border: none;
        appearance: none;
        background: transparent;
        font-weight: bold;
        padding: 0;
    }
}
</style>

<?php include '../templates/footer.php'; ?>