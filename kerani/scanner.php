<?php
require '../config/config.php';
// Jalankan pengecekan auto-alpha hari ini
checkAndSetAlpha($conn);

include 'templates/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nik'])) {
    $nik = mysqli_real_escape_string($conn, $_POST['nik']);
    $tanggal = date('Y-m-d');
    $waktu = date('H:i:s');
    
    // Ambil jam masuk dari settings
    $setting = mysqli_fetch_assoc(mysqli_query($conn, "SELECT jam_masuk FROM settings LIMIT 1"));
    $jamMasuk = $setting ? $setting['jam_masuk'] : '08:00:00';
    $status_kehadiran = ($waktu <= $jamMasuk) ? 'tepat_waktu' : 'terlambat';
    
    // Cek apakah user valid
    $cek_user = mysqli_query($conn, "SELECT id, name FROM users WHERE nik='$nik' AND role IN ('karyawan', 'mandor')");
    if(mysqli_num_rows($cek_user) > 0) {
        $user_row = mysqli_fetch_assoc($cek_user);
        $scan_user_id = $user_row['id'];
        
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
            // Cek apakah waktu saat ini antara 06:00 dan 07:00 untuk absen MASUK
            if ($waktu >= '06:00:00' && $waktu <= '07:00:00') {
                // Insert absen masuk
                mysqli_query($conn, "INSERT INTO absensis (user_id, tanggal, waktu_masuk, status_kehadiran) VALUES ($scan_user_id, '$tanggal', '$waktu', '$status_kehadiran')");
                swalRedirect('Absen MASUK berhasil dicatat!', 'scanner.php', 'success');
            } else {
                swalAlert('Batas waktu absen masuk adalah jam 06:00 - 07:00!', 'error');
            }
        }
    } else {
        swalAlert('QR Code / NIK tidak valid!', 'error');
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

    /* Scanner container */
    #reader {
        width: 100%;
        min-height: 300px;
        background: #0f172a;
        border-radius: 16px;
        margin-bottom: 30px;
        overflow: hidden;
        border: 4px solid var(--accent);
    }
    
    #reader video {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
        border-radius: 12px;
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

        <div id="reader"></div>
        
        <form id="scanForm" method="POST" style="display: none;">
            <input type="hidden" name="nik" id="scanned_nik">
        </form>

        <a href="index.php" class="btn-back">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            Kembali ke Dasbor
        </a>
    </div>
</div>

<script src="../assets/js/html5-qrcode.min.js"></script>
<script>
    // Realtime Clock
    function updateClock() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('id-ID', { hour12: false });
        document.getElementById('realtimeClock').textContent = timeString;
    }
    setInterval(updateClock, 1000);
    updateClock();

    // Inisialisasi Scanner
    const html5QrCode = new Html5Qrcode("reader");
    let isProcessing = false;

    function startCamera() {
        const config = {
            fps: 10,
            qrbox: { width: 250, height: 250 },
            aspectRatio: 1.0
        };

        html5QrCode.start(
            { facingMode: "environment" },
            config,
            onScanSuccess,
            onScanFailure
        ).catch(err => {
            console.warn("Gagal akses kamera belakang, mencoba mode user...", err);
            html5QrCode.start(
                { facingMode: "user" },
                config,
                onScanSuccess,
                onScanFailure
            ).catch(err2 => {
                console.error("Kamera Error: " + err2);
            });
        });
    }

    startCamera();

    function onScanSuccess(decodedText) {
        if (isProcessing) return;
        isProcessing = true;
        
        // Pause kamera agar tidak scan berulang
        html5QrCode.pause();
        
        // Mainkan suara BEEP (opsional)
        playSound();

        // Submit form
        document.getElementById('scanned_nik').value = decodedText;
        document.getElementById('scanForm').submit();
    }

    function onScanFailure(error) {
        // Abaikan error saat scan berlangsung
    }

    function playSound() {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        if (audioCtx.state === 'suspended') audioCtx.resume();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.type = 'sine';
        osc.frequency.setValueAtTime(880, audioCtx.currentTime);
        osc.frequency.exponentialRampToValueAtTime(440, audioCtx.currentTime + 0.1);
        gain.gain.setValueAtTime(0.1, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.3);
        osc.start();
        osc.stop(audioCtx.currentTime + 0.3);
    }
</script>

<?php include 'templates/footer.php'; ?>
