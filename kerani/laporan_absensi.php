<?php
require '../config/config.php';
include 'templates/header.php';

$role_filter = isset($_GET['role']) ? $_GET['role'] : 'karyawan';
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
    .filter-select:focus { border-color: var(--accent); }

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
    .btn-print:hover { background: #f8fafc; border-color: #94a3b8; }

    .card {
        background: white;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 15px rgba(0,0,0,0.02);
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

    .table-data th, .table-data td {
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

    .text-left { text-align: left !important; }
    
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
    
    @media print {
        body * { visibility: hidden; }
        .main-content { margin-left: 0; }
        .sidebar, .topbar, .header-actions { display: none !important; }
        .card, .card * { visibility: visible; }
        .card { position: absolute; left: 0; top: 0; border: none; box-shadow: none; width: 100%; }
    }
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
            <?php foreach($nama_bulan as $num => $name): ?>
                <option value="<?= $num ?>" <?= $bulan == $num ? 'selected' : '' ?>><?= $name ?></option>
            <?php endforeach; ?>
        </select>
        
        <select name="tahun" class="filter-select" onchange="document.getElementById('filterForm').submit()">
            <?php for($y = date('Y') - 2; $y <= date('Y'); $y++): ?>
                <option value="<?= $y ?>" <?= $tahun == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </form>
    
    <button class="btn-print" onclick="window.print()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
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
                    <th rowspan="2">STATUS</th>
                    <th rowspan="2">TOTAL<br>KEHADIRAN</th>
                </tr>
                <tr>
                    <?php for($i = 1; $i <= $jumlah_hari; $i++): ?>
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
                
                $no = 1;
                while($user = mysqli_fetch_assoc($query)): 
                ?>
                <tr>
                    <td style="color: var(--text-muted); font-weight: 600;"><?= $no++ ?></td>
                    <td class="text-left" style="font-weight: 700;"><?= htmlspecialchars($user['nik']) ?></td>
                    <td class="text-left"><?= htmlspecialchars($user['name']) ?></td>
                    
                    <?php 
                    $total_hadir = 0;
                    for($i = 1; $i <= $jumlah_hari; $i++): 
                        $tgl_str = sprintf("%04d-%02d-%02d", $tahun, $bulan, $i);
                        $cek_absen = mysqli_query($conn, "SELECT status_kehadiran FROM absensis WHERE user_id={$user['id']} AND tanggal='$tgl_str'");
                        
                        if(mysqli_num_rows($cek_absen) > 0) {
                            $total_hadir++;
                            echo '<td><span class="status-badge status-h">H</span></td>';
                        } else {
                            echo '<td><span style="color: #cbd5e1;">-</span></td>';
                        }
                    ?>
                    <?php endfor; ?>
                    
                    <td style="color: #64748b;"><?= $total_hadir > 0 ? 'Aktif' : 'Kosong' ?></td>
                    <td style="font-weight: 800; color: #0f172a;"><?= $total_hadir ?> Hari</td>
                </tr>
                <?php endwhile; ?>
                
                <?php if(mysqli_num_rows($query) == 0): ?>
                <tr>
                    <td colspan="<?= $jumlah_hari + 5 ?>" style="text-align:center; padding:20px;">Belum ada data personil.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
