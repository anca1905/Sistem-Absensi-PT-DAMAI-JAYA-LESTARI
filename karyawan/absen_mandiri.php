<?php
require '../config/config.php';
checkAndSetAlpha($conn);
include 'templates/header.php';

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$now = date('H:i:s');

// Ambil pengaturan jam masuk
$setting_query = mysqli_query($conn, "SELECT jam_masuk FROM settings LIMIT 1");
$setting = mysqli_fetch_assoc($setting_query);
$jamMasuk = $setting['jam_masuk'] ?? '08:00:00';

// Cek status absensi hari ini
$cekAbsen = mysqli_query($conn, "SELECT * FROM absensis WHERE user_id = '$user_id' AND tanggal = '$today'");
$dataAbsen = mysqli_fetch_assoc($cekAbsen);

$pesan = '';
$tipe_pesan = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['absen_masuk'])) {
        if (!$dataAbsen) {
            if ($now >= '06:00:00' && $now <= '07:00:00') {
                $status = ($now > $jamMasuk) ? 'terlambat' : 'tepat_waktu';
                $insert = mysqli_query($conn, "INSERT INTO absensis (user_id, tanggal, waktu_masuk, status_kehadiran) VALUES ('$user_id', '$today', '$now', '$status')");
                if ($insert) {
                    $pesan = "Berhasil absen masuk pada $now";
                    $tipe_pesan = "success";
                    // Refresh data
                    $cekAbsen = mysqli_query($conn, "SELECT * FROM absensis WHERE user_id = '$user_id' AND tanggal = '$today'");
                    $dataAbsen = mysqli_fetch_assoc($cekAbsen);
                } else {
                    $pesan = "Gagal melakukan absensi.";
                    $tipe_pesan = "error";
                }
            } else {
                $pesan = "Batas waktu absen masuk adalah jam 06:00 - 07:00!";
                $tipe_pesan = "error";
            }
        }
    }
}
?>

<style>
    .page-title {
        font-size: 22px;
        font-weight: 800;
        color: var(--text-dark);
        margin: 0 0 24px 0;
        text-align: center;
        letter-spacing: -0.5px;
    }

    .clock-container {
        background: linear-gradient(135deg, var(--primary-start) 0%, var(--primary-end) 100%);
        border-radius: 24px;
        padding: 32px 24px;
        color: white;
        text-align: center;
        box-shadow: 0 15px 30px rgba(54, 72, 217, 0.25);
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
    }

    .clock-container::after {
        content: '';
        position: absolute;
        top: -50px;
        right: -30px;
        width: 150px;
        height: 150px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
    }

    .time-display {
        font-size: 48px;
        font-weight: 800;
        letter-spacing: 2px;
        margin-bottom: 8px;
        font-variant-numeric: tabular-nums;
    }

    .date-display {
        font-size: 16px;
        opacity: 0.9;
        font-weight: 500;
    }

    .action-card {
        background: #ffffff;
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        border: 1px solid #e2e8f0;
        text-align: center;
    }

    .status-badge {
        display: inline-block;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 700;
        margin-bottom: 24px;
    }

    .status-belum {
        background: #f1f5f9;
        color: #64748b;
    }

    .status-masuk {
        background: #dcfce7;
        color: #166534;
    }

    .status-selesai {
        background: #eff1ff;
        color: var(--primary-start);
    }

    .btn-absen {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        width: 100%;
        padding: 16px;
        border-radius: 16px;
        font-size: 16px;
        font-weight: 700;
        border: none;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
    }

    .btn-masuk {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        box-shadow: 0 8px 20px rgba(16, 185, 129, 0.25);
    }

    .btn-pulang {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
        box-shadow: 0 8px 20px rgba(245, 158, 11, 0.25);
    }

    .btn-absen:hover, .btn-absen:active {
        transform: translateY(-2px);
    }
    
    .btn-absen:active {
        transform: translateY(0);
    }

    .info-list {
        text-align: left;
        margin-top: 24px;
        padding-top: 24px;
        border-top: 1px dashed #e2e8f0;
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 12px;
        font-size: 14px;
    }

    .info-label {
        color: var(--text-muted);
        font-weight: 500;
    }

    .info-value {
        font-weight: 700;
        color: var(--text-dark);
    }

    /* Alert Messages */
    .alert {
        padding: 16px;
        border-radius: 12px;
        margin-bottom: 20px;
        font-weight: 600;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .alert-success {
        background-color: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
    }
    .alert-error {
        background-color: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fecaca;
    }
</style>

<div class="animate-up">
    <h1 class="page-title">Absen Mandiri</h1>

    <?php if($pesan): ?>
        <div class="alert alert-<?= $tipe_pesan ?>">
            <?php if($tipe_pesan == 'success'): ?>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
            <?php else: ?>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <?php endif; ?>
            <?= htmlspecialchars($pesan) ?>
        </div>
    <?php endif; ?>

    <div class="clock-container">
        <div class="time-display" id="realtime-clock">--:--:--</div>
        <div class="date-display">
            <?php
            $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            $bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            echo $hari[date('w')] . ', ' . date('d') . ' ' . $bulan[date('n') - 1] . ' ' . date('Y');
            ?>
        </div>
    </div>

    <div class="action-card">
        <?php if(!$dataAbsen): ?>
            <!-- Belum Absen -->
            <div class="status-badge status-belum">Belum Absen Hari Ini</div>
            <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 24px;">Silahkan lakukan absensi masuk sekarang.</p>
            
            <form method="POST">
                <button type="submit" name="absen_masuk" class="btn-absen btn-masuk">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                    Absen Masuk
                </button>
            </form>

        <?php else: ?>
            <!-- Selesai Absen -->
            <div class="status-badge status-selesai">Absensi Selesai</div>
            <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 0;">Anda telah menyelesaikan absensi hari ini. Selamat beraktivitas!</p>
        <?php endif; ?>

        <div class="info-list">
            <div class="info-item">
                <span class="info-label">Jam Masuk Ketentuan</span>
                <span class="info-value"><?= substr($jamMasuk, 0, 5) ?></span>
            </div>
            <?php if($dataAbsen): ?>
                <div class="info-item">
                    <span class="info-label">Waktu Masuk Anda</span>
                    <span class="info-value"><?= substr($dataAbsen['waktu_masuk'], 0, 5) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Status Kehadiran</span>
                    <span class="info-value" style="color: <?= $dataAbsen['status_kehadiran'] == 'terlambat' ? '#e11d48' : '#10b981' ?>">
                        <?= ucwords(str_replace('_', ' ', $dataAbsen['status_kehadiran'])) ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function updateClock() {
        const now = new Date();
        const h = String(now.getHours()).padStart(2, '0');
        const m = String(now.getMinutes()).padStart(2, '0');
        const s = String(now.getSeconds()).padStart(2, '0');
        document.getElementById('realtime-clock').textContent = `${h}:${m}:${s}`;
    }
    setInterval(updateClock, 1000);
    updateClock();
</script>

<?php include 'templates/footer.php'; ?>
