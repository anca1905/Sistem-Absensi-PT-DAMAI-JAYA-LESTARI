<?php
require '../config/config.php';
include 'templates/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id'])) {
    $scan_user_id = (int)$_POST['user_id'];
    $tanggal = date('Y-m-d');
    $waktu = date('H:i:s');
    $status_kehadiran = (date('H') < 8) ? 'tepat_waktu' : 'terlambat';
    
    // Cek apakah user valid
    $cek_user = mysqli_query($conn, "SELECT name FROM users WHERE id=$scan_user_id");
    if(mysqli_num_rows($cek_user) > 0) {
        // Cek apakah sudah absen masuk hari ini
        $cek_absen = mysqli_query($conn, "SELECT id, waktu_masuk, waktu_pulang FROM absensis WHERE user_id=$scan_user_id AND tanggal='$tanggal'");
        if(mysqli_num_rows($cek_absen) > 0) {
            $absen = mysqli_fetch_assoc($cek_absen);
            if(empty($absen['waktu_pulang'])) {
                // Update absen pulang
                mysqli_query($conn, "UPDATE absensis SET waktu_pulang='$waktu' WHERE id={$absen['id']}");
                swalRedirect('Absen PULANG berhasil dicatat!', 'scanner.php', 'success');
            } else {
                swalRedirect('Anda sudah melakukan absen masuk dan pulang hari ini.', 'scanner.php', 'info', 'Info');
            }
        } else {
            // Insert absen masuk
            mysqli_query($conn, "INSERT INTO absensis (user_id, tanggal, waktu_masuk, status_kehadiran) VALUES ($scan_user_id, '$tanggal', '$waktu', '$status_kehadiran')");
            swalRedirect('Absen MASUK berhasil dicatat!', 'scanner.php', 'success');
        }
    } else {
        swalAlert('QR Code / User tidak valid!', 'error');
    }
}
?>

<style>
    .scanner-container {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: calc(100vh - 140px);
    }

    .scanner-box {
        background: white;
        border-radius: 24px;
        padding: 40px;
        width: 100%;
        max-width: 500px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.05);
        border: 1px solid #e2e8f0;
        text-align: center;
    }

    .scanner-title {
        font-size: 24px;
        font-weight: 800;
        color: var(--text-main);
        margin-bottom: 20px;
        letter-spacing: -0.5px;
    }

    .time-badge {
        display: inline-block;
        background: var(--bg-color);
        padding: 10px 20px;
        border-radius: 30px;
        font-family: monospace;
        font-size: 18px;
        font-weight: 700;
        color: var(--accent);
        margin-bottom: 30px;
        border: 1px solid #e2e8f0;
    }

    .camera-frame {
        width: 100%;
        aspect-ratio: 1/1;
        background: #0f172a;
        border-radius: 16px;
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        border: 4px solid var(--accent);
    }

    .camera-frame::before {
        content: '';
        position: absolute;
        width: 60%;
        height: 60%;
        border: 2px dashed rgba(255,255,255,0.3);
        border-radius: 8px;
    }

    /* Scanning line animation */
    .camera-frame::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 2px;
        background: #22c55e;
        box-shadow: 0 0 10px #22c55e, 0 0 20px #22c55e;
        animation: scan 2s linear infinite;
    }

    @keyframes scan {
        0% { top: 10%; opacity: 0; }
        10% { opacity: 1; }
        90% { opacity: 1; }
        100% { top: 90%; opacity: 0; }
    }

    .btn-back {
        background: white;
        color: var(--text-muted);
        border: 2px solid #e2e8f0;
        padding: 12px 24px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 14px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        transition: all 0.2s;
    }

    .btn-back:hover {
        background: var(--bg-color);
        color: var(--text-main);
    }
</style>

<div class="scanner-container">
    <div class="scanner-box">
        <h2 class="scanner-title">SISTEM ABSENSI QR CODE</h2>
        
        <div class="time-badge" id="realtimeClock">
            00:00:00
        </div>

        <div class="camera-frame">
            <div style="z-index: 10; text-align: center;">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="opacity: 0.5; margin-bottom: 10px;"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                <p style="font-size: 14px; font-weight: 600; color: #94a3b8;">Arahkan QR Code ke area ini</p>
                
                <!-- Simulasi Scanner -->
                <form method="POST" style="margin-top: 15px;">
                    <select name="user_id" required style="padding: 8px; border-radius: 6px; border: none; outline: none;">
                        <option value="">-- Simulasi Scan NIK --</option>
                        <?php 
                        $q = mysqli_query($conn, "SELECT id, name, role FROM users WHERE role IN ('karyawan', 'mandor')");
                        while($u = mysqli_fetch_assoc($q)) {
                            echo "<option value='{$u['id']}'>{$u['name']} ({$u['role']})</option>";
                        }
                        ?>
                    </select>
                    <button type="submit" style="margin-top: 10px; padding: 8px 16px; background: var(--accent); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight:bold;">Simulasi Scan</button>
                </form>

            </div>
        </div>

        <a href="index.php" class="btn-back">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            Kembali ke Dasbor
        </a>
    </div>
</div>

<script>
    // Realtime Clock
    function updateClock() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('id-ID', { hour12: false });
        document.getElementById('realtimeClock').textContent = timeString;
    }
    setInterval(updateClock, 1000);
    updateClock();
</script>

<?php include 'templates/footer.php'; ?>
