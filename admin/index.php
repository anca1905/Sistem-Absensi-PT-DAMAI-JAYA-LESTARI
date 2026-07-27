<?php
require '../config/config.php';

// Cek session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- 1. LOGIKA STATISTIK KARTU ---
$total_karyawan = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM users WHERE role='karyawan'"));
$hadir_hari_ini = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM absensis WHERE tanggal = CURDATE()"));
$izin_sakit = 0; // TODO: Implementasi logika izin/sakit
$belum_hadir    = $total_karyawan - $hadir_hari_ini - $izin_sakit;

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

// --- 3. DATA KARYAWAN (Box 2) ---
$query_karyawan = mysqli_query($conn, "SELECT nik, name FROM users WHERE role='karyawan' ORDER BY id DESC LIMIT 3");

// --- 4. LAPORAN ABSENSI (Box 3) ---
$query_absensi = mysqli_query($conn, "SELECT u.nik, u.name, a.tanggal, a.status_kehadiran FROM absensis a JOIN users u ON a.user_id = u.id ORDER BY a.id DESC LIMIT 3");

// --- 5. LOGIKA AKTIVITAS TERKINI (Bottom Box) ---
$query_activity = mysqli_query($conn, "
    SELECT u.name, u.jabatan, a.waktu_masuk, a.status_kehadiran 
    FROM absensis a 
    JOIN users u ON a.user_id = u.id 
    WHERE a.tanggal = CURDATE() 
    ORDER BY a.waktu_masuk DESC 
    LIMIT 5
");

include 'templates/header.php';
?>

<style>
    /* Layout Grid Utama */
    .dashboard-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 25px;
    }

    /* Kartu Statistik - Desain Baru */
    .stat-card {
        background: #fff;
        padding: 20px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .icon-blue { background: #eff6ff; color: #3b82f6; }
    .icon-green { background: #ecfdf5; color: #10b981; }
    .icon-purple { background: #f5f3ff; color: #8b5cf6; }
    .icon-orange { background: #fff7ed; color: #f97316; }

    .stat-title {
        font-size: 11px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }

    .stat-num {
        font-size: 26px;
        font-weight: 800;
        color: #1e293b;
        line-height: 1;
    }

    .stat-desc {
        font-size: 12px;
        color: #94a3b8;
        margin-top: 4px;
    }

    /* Layout 2x2 Grid */
    .dashboard-grid-2x2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }

    /* Container Box Putih */
    .box-container {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        padding: 20px;
        display: flex;
        flex-direction: column;
    }

    .box-header {
        font-size: 16px;
        font-weight: 700;
        color: #334155;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .btn-outline {
        border: 1px solid #e2e8f0;
        background: #fff;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        color: #475569;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-outline:hover {
        background: #f8fafc;
    }

    /* Tabel Sederhana untuk Dashboard */
    .table-dashboard {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }
    
    .table-dashboard th {
        text-align: left;
        padding: 10px 8px;
        color: #64748b;
        font-weight: 600;
        border-bottom: 1px solid #e2e8f0;
        font-size: 11px;
        text-transform: uppercase;
    }
    
    .table-dashboard td {
        padding: 12px 8px;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
    }

    .table-dashboard tr:last-child td {
        border-bottom: none;
    }

    /* List Aktivitas (Horizontal) */
    .activity-bell {
        width: 40px;
        height: 40px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #64748b;
        margin: 20px auto;
    }

    @media (max-width: 900px) {
        .dashboard-stats {
            grid-template-columns: repeat(2, 1fr);
        }
        .dashboard-grid-2x2 {
            grid-template-columns: 1fr;
        }
    }
    @media (max-width: 600px) {
        .dashboard-stats {
            grid-template-columns: 1fr;
        }
    }
</style>

<div>
    <!-- Header Page -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px;">
        <div>
            <h2 style="font-size: 24px; font-weight: 700; color: #1e293b; margin: 0 0 5px 0;">Dashboard</h2>
            <p style="color: #64748b; margin: 0; font-size: 14px;">Ringkasan informasi kehadiran dan aktivitas perusahaan.</p>
        </div>
        <div style="background: #fff; padding: 10px 16px; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 13px; font-weight: 600; color: #475569; display: flex; align-items: center; gap: 10px; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #94a3b8;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            <?= date('d F Y') ?>
        </div>
    </div>

    <!-- 4 Stats Cards -->
    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon icon-blue">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            </div>
            <div>
                <div class="stat-title">TOTAL PEGAWAI</div>
                <div class="stat-num"><?= $total_karyawan ?></div>
                <div class="stat-desc">Orang</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-green">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line><polyline points="9 16 12 19 19 10"></polyline></svg>
            </div>
            <div>
                <div class="stat-title">HADIR HARI INI</div>
                <div class="stat-num"><?= $hadir_hari_ini ?></div>
                <div class="stat-desc">Orang</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-purple">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line><path d="M12 14v4"></path><path d="M10 16h4"></path></svg>
            </div>
            <div>
                <div class="stat-title">IZIN / SAKIT</div>
                <div class="stat-num"><?= $izin_sakit ?></div>
                <div class="stat-desc">Orang</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-orange">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="18" y1="8" x2="23" y2="13"></line><line x1="23" y1="8" x2="18" y2="13"></line></svg>
            </div>
            <div>
                <div class="stat-title">BELUM ABSEN</div>
                <div class="stat-num"><?= $belum_hadir ?></div>
                <div class="stat-desc">Orang</div>
            </div>
        </div>
    </div>

    <!-- 2x2 Grid Content -->
    <div class="dashboard-grid-2x2">
        
        <!-- Box 1: Grafik -->
        <div class="box-container">
            <div class="box-header">
                1. Tren Kehadiran (Bulan Ini)
                <button class="btn-outline">
                    7 Hari Terakhir
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </button>
            </div>
            <div style="position: relative; height: 250px; width: 100%;">
                <canvas id="attendanceChart"></canvas>
            </div>
        </div>

        <!-- Box 2: Data Personil -->
        <div class="box-container">
            <div class="box-header">
                2. Data Personil
                <button class="btn-outline">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                    Cetak PDF
                </button>
            </div>
            <table class="table-dashboard">
                <thead>
                    <tr>
                        <th width="10%">NO</th>
                        <th width="30%">NIK</th>
                        <th>NAMA PERSONIL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    if(mysqli_num_rows($query_karyawan) > 0) {
                        while($k = mysqli_fetch_assoc($query_karyawan)){ ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= $k['nik'] ?></td>
                            <td style="font-weight: 500; color: #1e293b;"><?= $k['name'] ?></td>
                        </tr>
                    <?php } } else { ?>
                        <tr><td colspan="3" style="text-align: center; color: #94a3b8;">Belum ada data</td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <!-- Box 3: Laporan Absensi -->
        <div class="box-container">
            <div class="box-header">
                3. Laporan Absensi (<?= date('d F Y') ?>)
                <button class="btn-outline">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                    Cetak PDF
                </button>
            </div>
            <table class="table-dashboard">
                <thead>
                    <tr>
                        <th width="5%">NO</th>
                        <th width="20%">NIK</th>
                        <th width="30%">NAMA</th>
                        <th width="20%">TANGGAL</th>
                        <th>STATUS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    if(mysqli_num_rows($query_absensi) > 0) {
                        while($a = mysqli_fetch_assoc($query_absensi)){ ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= $a['nik'] ?></td>
                            <td style="font-weight: 500; color: #1e293b;"><?= $a['name'] ?></td>
                            <td><?= date('d/m/Y', strtotime($a['tanggal'])) ?></td>
                            <td>
                                <span style="background: <?= $a['status_kehadiran']=='hadir' ? '#ecfdf5' : '#fef2f2' ?>; color: <?= $a['status_kehadiran']=='hadir' ? '#10b981' : '#ef4444' ?>; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase;">
                                    <?= $a['status_kehadiran'] ?>
                                </span>
                            </td>
                        </tr>
                    <?php } } else { ?>
                        <tr><td colspan="5" style="text-align: center; color: #94a3b8;">Belum ada absen hari ini</td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <!-- Box 4: Laporan Kinerja -->
        <div class="box-container">
            <div class="box-header">
                4. Laporan Kinerja Hari Ini
                <button class="btn-outline">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                    Cetak PDF
                </button>
            </div>
            <table class="table-dashboard">
                <thead>
                    <tr>
                        <th width="5%">NO</th>
                        <th width="20%">NIK</th>
                        <th width="30%">NAMA</th>
                        <th>LAPORAN</th>
                        <th width="15%">STATUS</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #94a3b8; padding: 30px 0;">Belum ada data kinerja hari ini</td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>

    <!-- Aktivitas Terkini -->
    <div class="box-container" style="margin-bottom: 30px;">
        <div class="box-header" style="margin-bottom: 5px;">Aktivitas Masuk Terkini</div>
        
        <?php if (mysqli_num_rows($query_activity) > 0): ?>
            <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 15px;">
                <?php while ($act = mysqli_fetch_assoc($query_activity)): ?>
                    <div style="display: flex; align-items: center; gap: 15px; padding: 12px; border: 1px solid #f1f5f9; border-radius: 8px;">
                        <div style="width: 36px; height: 36px; background: #eff6ff; color: #3b82f6; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: 600; font-size: 13px; color: #334155;">
                                <?= $act['name'] ?>
                            </div>
                            <div style="font-size: 11px; color: #64748b;"><?= $act['jabatan'] ?></div>
                        </div>
                        <div style="font-size: 12px; font-weight: 700; color: #1e293b; font-family: monospace;">
                            <?= date('H:i', strtotime($act['waktu_masuk'])) ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="activity-bell">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
            </div>
            <p style="text-align: center; color: #64748b; font-size: 13px; margin: 0;">Belum ada aktivitas masuk hari ini.</p>
        <?php endif; ?>
    </div>

</div>

<script src="../assets/js/chart.umd.min.js"></script>
<script>
    const labels = <?= $json_labels ?>; 
    const dataHadir = <?= $json_data ?>; 

    const ctx = document.getElementById('attendanceChart').getContext('2d');
    const myChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Jumlah Kehadiran',
                data: dataHadir,
                borderColor: '#4f46e5',
                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#4f46e5',
                pointBorderWidth: 2,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e293b',
                    padding: 10,
                    cornerRadius: 4
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        borderDash: [2, 4],
                        color: '#f1f5f9'
                    },
                    ticks: {
                        stepSize: 1,
                        color: '#64748b'
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#64748b' }
                }
            }
        }
    });
</script>

<?php include 'templates/footer.php'; ?>