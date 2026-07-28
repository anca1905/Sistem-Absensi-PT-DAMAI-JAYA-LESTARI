<?php
require '../config/config.php';
include 'templates/header.php';
<style type="text/css" media="print">@page { size: landscape; }</style>


$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$jumlah_hari = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);

$nama_bulan = array(
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
    '04' => 'April', '05' => 'Mei', '06' => 'Juni',
    '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
    '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
);
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
    .status-a { background-color: #fee2e2; color: #991b1b; }
    .status-i { background-color: #e0f2fe; color: #075985; }
    .status-s { background-color: #ede9fe; color: #5b21b6; }
    .status-c { background-color: #ffedd5; color: #9a3412; }

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
                        $user_id = $_SESSION['user_id'] ?? 1;
                        for($i = 1; $i <= $jumlah_hari; $i++): 
                            $tgl_str = sprintf("%04d-%02d-%02d", $tahun, $bulan, $i);
                            $cek_absen = mysqli_query($conn, "SELECT status_kehadiran FROM absensis WHERE user_id=$user_id AND tanggal='$tgl_str'");
                            
                            if(mysqli_num_rows($cek_absen) > 0) {
                                $row_abs = mysqli_fetch_assoc($cek_absen);
                                if ($row_abs['status_kehadiran'] == 'alpha') {
                                    echo '<td><span class="status-badge status-a">A</span></td>';
                                } elseif ($row_abs['status_kehadiran'] == 'izin') {
                                    echo '<td><span class="status-badge status-i">I</span></td>';
                                } elseif ($row_abs['status_kehadiran'] == 'sakit') {
                                    echo '<td><span class="status-badge status-s">S</span></td>';
                                } elseif ($row_abs['status_kehadiran'] == 'cuti') {
                                    echo '<td><span class="status-badge status-c">C</span></td>';
                                } else {
                                    $total_hadir++;
                                    echo '<td><span class="status-badge status-h">H</span></td>';
                                }
                            } else {
                                // Fallback: Cek tabel perizinan jika absen kosong
                                $cek_izin = mysqli_query($conn, "SELECT jenis FROM perizinan WHERE user_id=$user_id AND tanggal_izin='$tgl_str' AND status='disetujui'");
                                if (mysqli_num_rows($cek_izin) > 0) {
                                    $row_izin = mysqli_fetch_assoc($cek_izin);
                                    if ($row_izin['jenis'] == 'izin') {
                                        echo '<td><span class="status-badge status-i">I</span></td>';
                                    } elseif ($row_izin['jenis'] == 'sakit') {
                                        echo '<td><span class="status-badge status-s">S</span></td>';
                                    } elseif ($row_izin['jenis'] == 'cuti') {
                                        echo '<td><span class="status-badge status-c">C</span></td>';
                                    }
                                } else {
                                    echo '<td><span style="color: #cbd5e1;">-</span></td>';
                                }
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
