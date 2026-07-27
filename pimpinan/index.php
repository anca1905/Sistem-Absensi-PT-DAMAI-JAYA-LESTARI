<?php
require '../config/config.php';

// Cek session khusus Pimpinan (Admin)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'pimpinan')) {
    header("Location: ../index.php");
    exit;
}

// --- 1. LOGIKA STATISTIK KARTU ---
$total_karyawan = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users WHERE role='karyawan'"));
$hadir_hari_ini = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM absensis WHERE tanggal = CURDATE()"));

// Hitung yang Izin/Sakit (dan sudah disetujui Pengawas)
$izin_sakit = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM perizinan WHERE tanggal_izin = CURDATE() AND status = 'disetujui'"));

// Belum hadir = Total - Hadir - Izin/Sakit
$belum_hadir = $total_karyawan - $hadir_hari_ini - $izin_sakit;

// --- 2. LOGIKA GRAFIK (7 HARI TERAKHIR) ---
$labels = [];
$data_grafik = [];
for ($i = 6; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('d M', strtotime($tgl));
    $query_grafik = mysqli_query($conn, "SELECT COUNT(*) as total FROM absensis WHERE tanggal = '$tgl'");
    $row_grafik = mysqli_fetch_assoc($query_grafik);
    $data_grafik[] = $row_grafik['total'];
}
$json_labels = json_encode($labels);
$json_data   = json_encode($data_grafik);

// --- 3. LOGIKA AKTIVITAS TERKINI ---
$query_activity = mysqli_query($conn, "
    SELECT u.name, u.jabatan, a.waktu_masuk, a.status_kehadiran 
    FROM absensis a JOIN users u ON a.user_id = u.id 
    WHERE a.tanggal = CURDATE() ORDER BY a.waktu_masuk DESC LIMIT 5
");

include 'templates/header.php';
?>

<style>
    .dashboard-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: #fff;
        padding: 24px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        position: relative;
        overflow: hidden;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
    }

    .card-blue::before {
        background: #3b82f6;
    }

    .card-green::before {
        background: #10b981;
    }

    .card-purple::before {
        background: #8b5cf6;
    }

    .card-orange::before {
        background: #f59e0b;
    }

    .stat-title {
        font-size: 12px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
        display: block;
    }

    .stat-num {
        font-size: 32px;
        font-weight: 800;
        color: #1e293b;
        line-height: 1;
    }

    .dashboard-split {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 20px;
    }

    .box-container {
        background: #fff;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        padding: 20px;
    }

    .box-header {
        font-size: 16px;
        font-weight: 700;
        color: #334155;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
    }

    .activity-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .activity-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid #f1f5f9;
    }

    .act-icon {
        width: 36px;
        height: 36px;
        background: #f0f9ff;
        color: #0284c7;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .act-info {
        flex: 1;
    }

    .act-name {
        font-size: 14px;
        font-weight: 600;
        color: #334155;
        display: block;
    }

    .act-time {
        font-size: 12px;
        font-weight: 700;
        color: #1e293b;
        font-family: monospace;
    }

    .badge-late {
        color: #ef4444;
        font-size: 10px;
        background: #fef2f2;
        padding: 2px 6px;
        border-radius: 4px;
        margin-left: 5px;
    }

    @media (max-width: 900px) {
        .dashboard-split {
            grid-template-columns: 1fr;
        }
    }
</style>

<div>
    <h2 style="font-size: 22px; font-weight: 700; color: #1e293b; margin-bottom: 25px;">Ringkasan Eksekutif</h2>

    <div class="dashboard-stats">
        <div class="stat-card card-blue">
            <span class="stat-title">Total Pegawai</span>
            <span class="stat-num"><?= $total_karyawan ?></span>
        </div>
        <div class="stat-card card-green">
            <span class="stat-title">Hadir Hari Ini</span>
            <span class="stat-num"><?= $hadir_hari_ini ?></span>
        </div>
        <div class="stat-card card-purple">
            <span class="stat-title">Izin / Sakit</span>
            <span class="stat-num"><?= $izin_sakit ?></span>
        </div>
        <div class="stat-card card-orange">
            <span class="stat-title">Belum Absen</span>
            <span class="stat-num"><?= $belum_hadir ?></span>
        </div>
    </div>

    <div class="dashboard-split">
        <div class="box-container">
            <div class="box-header">Tren Kehadiran (7 Hari Terakhir)</div>
            <div style="position: relative; height: 300px; width: 100%;">
                <canvas id="attendanceChart"></canvas>
            </div>
        </div>

        <div class="box-container">
            <div class="box-header">Aktivitas Masuk Terkini <span style="font-size: 11px; color: #94a3b8;"><?= date('d M Y') ?></span></div>
            <?php if (mysqli_num_rows($query_activity) > 0): ?>
                <ul class="activity-list">
                    <?php while ($act = mysqli_fetch_assoc($query_activity)): ?>
                        <li class="activity-item">
                            <div class="act-icon">👤</div>
                            <div class="act-info">
                                <span class="act-name"><?= $act['name'] ?></span>
                                <span style="font-size: 11px; color: #64748b;"><?= $act['jabatan'] ?></span>
                            </div>
                            <div class="act-time"><?= date('H:i', strtotime($act['waktu_masuk'])) ?></div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <div style="text-align: center; padding: 40px 0; color: #94a3b8; font-size: 13px;">Belum ada yang hadir hari ini.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="../assets/js/chart.umd.min.js"></script>
<script>
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= $json_labels ?>,
            datasets: [{
                label: 'Kehadiran',
                data: <?= $json_data ?>,
                borderColor: '#4f46e5',
                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
</script>
<?php include 'templates/footer.php'; ?>