<?php
require '../config/config.php';
include 'templates/header.php';

// Hitung statistik ringkas
$uid_keu = $_SESSION['user_id'];
$today   = date('Y-m-d');
$bulan   = date('m');
$tahun   = date('Y');

// Total karyawan
$q_total_karyawan = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role='karyawan'");
$total_karyawan   = mysqli_fetch_assoc($q_total_karyawan)['total'];

// Hadir hari ini
$q_hadir_hari     = mysqli_query($conn, "SELECT COUNT(*) as total FROM absensis WHERE tanggal='$today' AND status_kehadiran IN ('hadir','tepat_waktu','terlambat')");
$hadir_hari       = mysqli_fetch_assoc($q_hadir_hari)['total'];

// Total izin bulan ini (pending)
$q_izin_pending   = mysqli_query($conn, "SELECT COUNT(*) as total FROM perizinan WHERE status='menunggu' AND MONTH(tanggal_izin)='$bulan' AND YEAR(tanggal_izin)='$tahun'");
$izin_pending     = mysqli_fetch_assoc($q_izin_pending)['total'];

// Total record logbook bulan ini
$q_logbook_total  = mysqli_query($conn, "SELECT COUNT(*) as total FROM logbook_kinerja WHERE MONTH(tanggal)='$bulan' AND YEAR(tanggal)='$tahun'");
$logbook_total    = mysqli_fetch_assoc($q_logbook_total)['total'];
?>

<style>
    .keu-welcome {
        background: linear-gradient(135deg, #064e3b 0%, #047857 50%, #10b981 100%);
        border-radius: 20px;
        padding: 28px 32px;
        color: white;
        margin-bottom: 28px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(6, 78, 59, 0.25);
    }
    .keu-welcome::before {
        content: '';
        position: absolute; top: -60px; right: -40px;
        width: 200px; height: 200px;
        background: rgba(255,255,255,0.07);
        border-radius: 50%;
    }
    .keu-welcome::after {
        content: '';
        position: absolute; bottom: -30px; right: 80px;
        width: 120px; height: 120px;
        background: rgba(255,255,255,0.05);
        border-radius: 50%;
    }
    .keu-welcome h1 { font-size: 26px; font-weight: 800; margin-bottom: 4px; }
    .keu-welcome p  { font-size: 14px; opacity: 0.85; }
    .keu-welcome .date-badge {
        display: inline-block;
        background: rgba(255,255,255,0.15);
        border: 1px solid rgba(255,255,255,0.25);
        border-radius: 20px;
        padding: 4px 14px;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 12px;
        backdrop-filter: blur(4px);
    }

    /* Stats */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 28px;
    }
    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 22px 24px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 2px 12px rgba(0,0,0,0.04);
        display: flex;
        align-items: center;
        gap: 16px;
        transition: all 0.2s;
    }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(6,78,59,0.08); border-color: #a7f3d0; }
    .stat-icon {
        width: 50px; height: 50px;
        border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .stat-icon.green  { background: #d1fae5; color: #059669; }
    .stat-icon.blue   { background: #dbeafe; color: #2563eb; }
    .stat-icon.amber  { background: #fef3c7; color: #d97706; }
    .stat-icon.purple { background: #ede9fe; color: #7c3aed; }
    .stat-value { font-size: 28px; font-weight: 800; color: #064e3b; line-height: 1; }
    .stat-label { font-size: 12px; color: #6b7280; font-weight: 600; margin-top: 4px; }

    /* Menu Cards */
    .menu-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 16px;
        margin-bottom: 28px;
    }
    .menu-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 24px;
        text-decoration: none;
        color: var(--text-main);
        display: flex;
        align-items: center;
        gap: 18px;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        position: relative;
        overflow: hidden;
    }
    .menu-card::after {
        content: '';
        position: absolute; bottom: 0; left: 0; right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--accent) 0%, #059669 100%);
        transform: scaleX(0);
        transform-origin: left;
        transition: transform 0.3s ease;
    }
    .menu-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(6,78,59,0.1); border-color: #a7f3d0; }
    .menu-card:hover::after { transform: scaleX(1); }
    .menu-card-icon {
        width: 52px; height: 52px;
        border-radius: 14px;
        background: var(--accent-light);
        color: var(--accent);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .menu-card-title { font-size: 15px; font-weight: 700; color: #064e3b; }
    .menu-card-desc  { font-size: 12px; color: #6b7280; margin-top: 4px; }
    .menu-card-arrow { margin-left: auto; color: #9ca3af; flex-shrink: 0; transition: transform 0.2s; }
    .menu-card:hover .menu-card-arrow { transform: translateX(4px); color: var(--accent); }

    /* Recent Activity */
    .section-title {
        font-size: 16px;
        font-weight: 800;
        color: #064e3b;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .section-title::before {
        content: '';
        width: 4px; height: 20px;
        background: var(--accent);
        border-radius: 4px;
        display: inline-block;
    }

    .activity-list { display: flex; flex-direction: column; gap: 10px; }
    .activity-item {
        display: flex;
        align-items: center;
        gap: 14px;
        background: #f9fafb;
        border: 1px solid #f3f4f6;
        border-radius: 12px;
        padding: 14px 16px;
        transition: background 0.2s;
    }
    .activity-item:hover { background: white; border-color: #e5e7eb; }
    .activity-dot {
        width: 10px; height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .dot-green  { background: #10b981; }
    .dot-amber  { background: #f59e0b; }
    .dot-red    { background: #ef4444; }
    .activity-name  { font-size: 14px; font-weight: 600; color: #1f2937; }
    .activity-meta  { font-size: 12px; color: #9ca3af; margin-top: 2px; }
    .activity-badge {
        margin-left: auto;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        flex-shrink: 0;
    }
    .badge-hadir   { background: #d1fae5; color: #065f46; }
    .badge-alpha   { background: #fee2e2; color: #991b1b; }
    .badge-terlambat { background: #fef3c7; color: #92400e; }
    .badge-izin    { background: #dbeafe; color: #1e40af; }
</style>

<div style="animation: fadeIn 0.4s ease;">

    <!-- Welcome Banner -->
    <div class="keu-welcome">
        <div class="date-badge">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="display:inline; margin-right:4px; vertical-align:middle;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            <?= date('d F Y') ?>
        </div>
        <h1>Halo, <?= htmlspecialchars($_SESSION['nama'] ?? 'Tim Keuangan') ?> 👋</h1>
        <p>Selamat datang di Panel Keuangan PT Damai Jaya Lestari. Berikut ringkasan aktivitas terkini.</p>
    </div>

    <!-- Stat Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon green">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            </div>
            <div>
                <div class="stat-value"><?= $total_karyawan ?></div>
                <div class="stat-label">Total Karyawan</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            </div>
            <div>
                <div class="stat-value"><?= $hadir_hari ?></div>
                <div class="stat-label">Hadir Hari Ini</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon amber">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            </div>
            <div>
                <div class="stat-value"><?= $izin_pending ?></div>
                <div class="stat-label">Izin Menunggu</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
            </div>
            <div>
                <div class="stat-value"><?= $logbook_total ?></div>
                <div class="stat-label">Entri Kinerja Bulan Ini</div>
            </div>
        </div>
    </div>

    <!-- Menu Quick Access -->
    <h2 class="section-title">Akses Cepat</h2>
    <div class="menu-grid">
        <a href="laporan_absensi.php" class="menu-card">
            <div class="menu-card-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            </div>
            <div>
                <div class="menu-card-title">Laporan Absensi</div>
                <div class="menu-card-desc">Rekapan kehadiran bulanan semua karyawan</div>
            </div>
            <svg class="menu-card-arrow" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
        </a>

        <a href="lap_keseluruhan.php" class="menu-card">
            <div class="menu-card-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="3" y1="15" x2="21" y2="15"></line><line x1="9" y1="9" x2="9" y2="21"></line></svg>
            </div>
            <div>
                <div class="menu-card-title">Laporan Keseluruhan</div>
                <div class="menu-card-desc">Data kinerja dari kerani, semua afdeling</div>
            </div>
            <svg class="menu-card-arrow" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
        </a>
    </div>

    <!-- Aktivitas Terkini -->
    <h2 class="section-title">Aktivitas Absensi Terkini (Hari Ini)</h2>
    <div class="card" style="padding: 16px;">
        <div class="activity-list">
            <?php
            $q_recent = mysqli_query($conn, "
                SELECT a.status_kehadiran, a.waktu_masuk, u.name, u.role
                FROM absensis a
                JOIN users u ON a.user_id = u.id
                WHERE a.tanggal = '$today'
                ORDER BY a.waktu_masuk DESC
                LIMIT 8
            ");
            if ($q_recent && mysqli_num_rows($q_recent) > 0):
                while($r = mysqli_fetch_assoc($q_recent)):
                    $s = strtolower($r['status_kehadiran'] ?? '');
                    $dot_class = 'dot-green';
                    $badge_class = 'badge-hadir';
                    $badge_text = ucfirst($s);
                    if (in_array($s, ['alpha','alpa','alfa'])) { $dot_class = 'dot-red'; $badge_class = 'badge-alpha'; }
                    elseif ($s == 'terlambat') { $dot_class = 'dot-amber'; $badge_class = 'badge-terlambat'; }
                    elseif ($s == 'izin') { $dot_class = 'dot-amber'; $badge_class = 'badge-izin'; }
            ?>
            <div class="activity-item">
                <div class="activity-dot <?= $dot_class ?>"></div>
                <div>
                    <div class="activity-name"><?= htmlspecialchars($r['name']) ?></div>
                    <div class="activity-meta"><?= ucfirst($r['role']) ?> · <?= $r['waktu_masuk'] ?? '-' ?></div>
                </div>
                <span class="activity-badge <?= $badge_class ?>"><?= $badge_text ?></span>
            </div>
            <?php endwhile; else: ?>
            <div style="text-align:center; padding: 32px; color: #9ca3af; font-size:14px;">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5" style="margin:0 auto 10px; display:block;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                Belum ada aktivitas absensi hari ini.
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
