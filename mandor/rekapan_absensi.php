<?php
require '../config/config.php';
include 'templates/header.php';

$user_id = $_SESSION['user_id'];
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$jumlah_hari = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);

$nama_bulan = array(
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
    '04' => 'April', '05' => 'Mei', '06' => 'Juni',
    '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
    '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
);

// Ambil data absensi dari DB
$query_absen = mysqli_query($conn, "
    SELECT DAY(tanggal) as hari, status_kehadiran
    FROM absensis
    WHERE user_id = '$user_id' AND MONTH(tanggal) = '$bulan' AND YEAR(tanggal) = '$tahun'
");
$data_absen = [];
while ($row = mysqli_fetch_assoc($query_absen)) {
    $data_absen[$row['hari']] = $row['status_kehadiran'];
}
?>

<style>
    .card-container {
        background: #ffffff;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        border: 1px solid #e2e8f0;
        margin-bottom: 20px;
    }

    .form-select {
        width: 100%;
        padding: 12px 16px;
        border-radius: 10px;
        border: 2px solid #e2e8f0;
        background-color: #f8fafc;
        font-size: 14px;
        font-weight: 600;
        color: #1e293b;
        font-family: inherit;
        outline: none;
        margin-bottom: 10px;
        transition: all 0.2s;
    }
    
    .form-select:focus {
        border-color: var(--primary-start);
        box-shadow: 0 0 0 4px rgba(66, 88, 255, 0.1);
        background: white;
    }

    .table-responsive {
        width: 100%;
        overflow-x: auto;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        -webkit-overflow-scrolling: touch; /* Smooth scroll on iOS */
    }

    .table-absen {
        border-collapse: collapse;
        white-space: nowrap;
        font-size: 13px;
        min-width: 600px; /* Force scroll if too small */
    }

    .table-absen th, .table-absen td {
        padding: 10px 12px;
        border: 1px solid #e2e8f0;
        text-align: center;
    }

    .table-absen th {
        background-color: #f8fafc;
        color: #475569;
        font-weight: 700;
        font-size: 11px;
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
    
    .status-h { background-color: #dcfce7; color: #166534; }
    .status-t { background-color: #fef9c3; color: #854d0e; }

    .btn-print {
        width: 100%;
        background: linear-gradient(135deg, var(--primary-start) 0%, var(--primary-end) 100%);
        color: white;
        border: none;
        padding: 16px;
        border-radius: 14px;
        font-size: 16px;
        font-weight: 800;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        transition: all 0.2s;
        box-shadow: 0 4px 15px rgba(66, 88, 255, 0.25);
    }

    .btn-print:active {
        transform: scale(0.98);
        box-shadow: none;
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

    /* Print styles for mobile view */
    @media print {
        body * { visibility: hidden; }
        .mobile-container { box-shadow: none; max-width: 100%; width: 100%; margin: 0; padding: 0; }
        .mobile-content { padding: 0; background: white; }
        .animate-up, .animate-up * { visibility: visible; }
        .btn-back, .btn-print, .mobile-header, select { display: none !important; }
        .table-responsive { overflow: visible; border: none; }
        .card-container { border: none; box-shadow: none; padding: 0; }
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

    <h2 class="page-title" style="text-align: left; margin-bottom: 20px; font-size: 24px;">Rekapan Absensi</h2>

    <div class="card-container">
        <!-- Filter Form -->
        <form id="filterForm" method="GET">
            <label style="font-size: 13px; font-weight: 700; color: #475569; display: block; margin-bottom: 8px;">Pilih Periode</label>
            <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                <select name="bulan" class="form-select" onchange="document.getElementById('filterForm').submit()">
                    <?php foreach($nama_bulan as $num => $name): ?>
                        <option value="<?= $num ?>" <?= $bulan == $num ? 'selected' : '' ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="tahun" class="form-select" onchange="document.getElementById('filterForm').submit()">
                    <?php for($y = date('Y') - 2; $y <= date('Y'); $y++): ?>
                        <option value="<?= $y ?>" <?= $tahun == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </form>

        <div style="margin-bottom: 15px; font-size: 13px; color: #64748b;">
            Geser tabel ke kiri/kanan untuk melihat data lengkap.
        </div>

        <!-- Tabel Rekapan Absensi -->
        <div class="table-responsive" style="margin-bottom: 20px;">
            <table class="table-absen">
                <thead>
                    <tr>
                        <th colspan="<?= $jumlah_hari ?>">TANGGAL</th>
                        <th rowspan="2">KETERANGAN</th>
                        <th rowspan="2">TOTAL<br>KEHADIRAN</th>
                    </tr>
                    <tr>
                        <?php for($i = 1; $i <= $jumlah_hari; $i++): ?>
                            <th style="min-width: 25px;"><?= $i ?></th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <?php 
                        $total_hadir = 0;
                        for($i = 1; $i <= $jumlah_hari; $i++): 
                            $status = isset($data_absen[$i]) ? $data_absen[$i] : null;
                            if ($status == 'tepat_waktu' || $status == 'hadir') {
                                $total_hadir++;
                                echo '<td><span class="status-badge status-h">H</span></td>';
                            } elseif ($status == 'terlambat') {
                                $total_hadir++;
                                echo '<td><span class="status-badge status-t">T</span></td>';
                            } elseif (in_array($status, ['alfa', 'alpa', 'alpha'])) {
                                echo '<td><span class="status-badge" style="background:#fee2e2;color:#991b1b;">A</span></td>';
                            } else {
                                echo '<td><span style="color: #cbd5e1;">-</span></td>';
                            }
                        ?>
                        <?php endfor; ?>
                        
                        <td style="font-weight: 600; color: #334155;">-</td>
                        <td style="font-weight: 800; color: #0f172a;"><?= $total_hadir ?> Hari</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <button type="button" class="btn-print" onclick="window.print()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                <rect x="6" y="14" width="12" height="8"></rect>
            </svg>
            Cetak PDF
        </button>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
