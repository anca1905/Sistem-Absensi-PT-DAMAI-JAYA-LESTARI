<?php
require '../config/config.php';

// -----------------------------------------------------
// LOGIKA COUNTDOWN 2 JAM & AUTO ALPHA
// -----------------------------------------------------
$session_file = '../config/scan_session.json';
$today = date('Y-m-d');
$now_time = time();
$two_hours_in_seconds = 2 * 60 * 60; // 7200 detik

// Baca file session
$session_data = [];
if (file_exists($session_file)) {
    $content = file_get_contents($session_file);
    if (!empty($content)) {
        $session_data = json_decode($content, true);
    }
}

// Cek apakah ada session untuk hari ini
if (isset($session_data['date']) && $session_data['date'] === $today) {
    // Session hari ini sudah ada, hitung sisa waktu
    $start_time = $session_data['start_timestamp'];
    $elapsed = $now_time - $start_time;
    $remaining_seconds = $two_hours_in_seconds - $elapsed;
} else {
    // Session baru untuk hari ini
    $session_data = [
        'date' => $today,
        'start_timestamp' => $now_time,
        'start_time_formatted' => date('H:i:s')
    ];
    file_put_contents($session_file, json_encode($session_data));
    $remaining_seconds = $two_hours_in_seconds;
}

$is_session_closed = false;

// Jika waktu habis, tandai ALPHA bagi yang belum absen
if ($remaining_seconds <= 0) {
    $is_session_closed = true;
    $remaining_seconds = 0;

    // Proses Alpha untuk karyawan yang belum ada di tabel absensis hari ini
    $query_karyawan = mysqli_query($conn, "SELECT id FROM users WHERE role = 'karyawan'");
    while ($karyawan = mysqli_fetch_assoc($query_karyawan)) {
        $user_id = $karyawan['id'];

        // Cek apakah sudah absen hari ini
        $cek_absen = mysqli_query($conn, "SELECT id FROM absensis WHERE user_id = '$user_id' AND tanggal = '$today'");
        if (mysqli_num_rows($cek_absen) == 0) {
            // Belum absen -> Insert Alpha
            mysqli_query($conn, "INSERT INTO absensis (user_id, tanggal, waktu_masuk, status_kehadiran) VALUES ('$user_id', '$today', NULL, 'alpha')");
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Scanner Absensi - PT DJL</title>

    <style>
        /* --- STYLE GLOBAL --- */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #eef2f7;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #333;
            padding: 15px;
        }

        /* --- CONTAINER --- */
        .scan-container {
            width: 100%;
            max-width: 500px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: 1px solid #d1d5db;
            position: relative;
        }

        .scan-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #172554 100%);
            padding: 20px;
            text-align: center;
            border-bottom: 4px solid #f59e0b;
            color: white;
        }

        .scan-header h2 {
            font-size: 18px;
            font-weight: 700;
            text-transform: uppercase;
            margin: 0;
        }

        .scan-header p {
            color: #bfdbfe;
            font-size: 12px;
            margin-top: 5px;
            margin-bottom: 0;
        }

        .scan-body {
            padding: 20px;
            text-align: center;
        }

        .clock {
            font-size: 26px;
            font-weight: 800;
            color: #1e3a8a;
            margin-bottom: 5px;
            font-family: monospace;
            letter-spacing: 1px;
        }

        .countdown {
            font-size: 16px;
            font-weight: 700;
            color: #ef4444;
            margin-bottom: 15px;
            background: #fee2e2;
            padding: 5px 15px;
            border-radius: 20px;
            display: inline-block;
        }

        .session-closed {
            padding: 40px 20px;
            text-align: center;
            background: #fef2f2;
            border-radius: 12px;
            border: 2px dashed #fca5a5;
        }

        .session-closed h3 {
            color: #b91c1c;
            margin-bottom: 10px;
            font-size: 20px;
        }

        .session-closed p {
            color: #7f1d1d;
            font-size: 14px;
        }

        /* --- AREA KAMERA BARU --- */
        #reader {
            width: 100%;
            min-height: 300px;
            /* Tinggi minimum biar ga gepeng */
            background: #000;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
        }

        /* Memastikan video mengisi penuh kotak */
        #reader video {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
            border-radius: 12px;
        }

        /* Loading Indicator */
        .loading-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 14px;
            z-index: 1;
        }

        /* Kotak Error (Muncul jika kamera gagal) */
        #error-msg {
            display: none;
            margin-top: 15px;
            padding: 10px;
            background: #fef2f2;
            border: 1px solid #fca5a5;
            color: #b91c1c;
            font-size: 12px;
            border-radius: 6px;
            text-align: left;
        }

        /* Footer */
        .scan-footer {
            background-color: #f8fafc;
            padding: 15px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }

        .btn-back {
            text-decoration: none;
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        /* --- MODAL POPUP --- */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            z-index: 999;
            display: none;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(4px);
            padding: 20px;
        }

        .modal-box {
            background: white;
            width: 100%;
            max-width: 350px;
            border-radius: 16px;
            padding: 25px;
            text-align: center;
            animation: popUp 0.3s ease-out;
        }

        @keyframes popUp {
            from {
                transform: scale(0.9);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .modal-icon {
            font-size: 50px;
            margin-bottom: 10px;
            display: block;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 800;
            color: #1e3a8a;
            margin-bottom: 5px;
        }

        .modal-desc {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .modal-time {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 20px;
            background: #f1f5f9;
            padding: 5px 10px;
            border-radius: 4px;
            display: inline-block;
        }

        .btn-modal {
            background-color: #1e3a8a;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            width: 100%;
            font-size: 14px;
        }
    </style>
</head>

<body>

    <div class="scan-container">
        <div class="scan-header">
            <h2>Pos Security</h2>
            <p>SISTEM ABSENSI QR CODE</p>
        </div>

        <div class="scan-body">
            <div class="clock" id="clock">00:00:00</div>

            <?php if (!$is_session_closed): ?>
                <div class="countdown" id="countdownTimer">Sisa Waktu: --:--:--</div>
                <div id="reader">
                    <div class="loading-text">Memuat Kamera...</div>
                </div>
            <?php else: ?>
                <div class="session-closed">
                    <h3>⛔ Sesi Scan Ditutup</h3>
                    <p>Waktu scan absensi (2 Jam) telah habis.<br>Karyawan yang belum absen otomatis tercatat sebagai Alpha.</p>
                </div>
            <?php endif; ?>

            <div id="error-msg"></div>
        </div>

        <div class="scan-footer">
            <a href="index.php" class="btn-back">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7" />
                </svg>
                Kembali ke Dashboard
            </a>
        </div>
    </div>

    <div id="resultModal" class="modal-overlay">
        <div class="modal-box">
            <span id="mIcon" class="modal-icon">✅</span>
            <h3 id="mTitle" class="modal-title">BERHASIL</h3>
            <p id="mName" class="modal-desc">Nama Karyawan</p>
            <div id="mInfo" class="modal-time">08:00:00</div>
            <button class="btn-modal" onclick="closeModal()">LANJUT SCAN</button>
        </div>
    </div>

    <script src="../assets/js/html5-qrcode.min.js"></script>

    <script>
        // 1. JAM DIGITAL
        setInterval(() => {
            document.getElementById('clock').innerText = new Date().toLocaleTimeString('id-ID');
        }, 1000);

        <?php if (!$is_session_closed): ?>
            // --- LOGIKA COUNTDOWN TIMER ---
            let remainingSeconds = <?= $remaining_seconds ?>;

            function updateCountdown() {
                if (remainingSeconds <= 0) {
                    // WAKTU HABIS! Refresh halaman untuk memicu backend nulis Alpha
                    document.getElementById('countdownTimer').innerText = "WAKTU HABIS!";
                    if (typeof html5QrCode !== 'undefined' && html5QrCode) {
                        html5QrCode.stop().then(() => html5QrCode.clear());
                    }

                    // Beri alert kecil lalu reload
                    setTimeout(() => {
                        alert("Waktu scan telah habis! Halaman akan dimuat ulang.");
                        window.location.reload();
                    }, 500);
                    return;
                }

                let h = Math.floor(remainingSeconds / 3600);
                let m = Math.floor((remainingSeconds % 3600) / 60);
                let s = remainingSeconds % 60;

                let formatted =
                    (h < 10 ? "0" + h : h) + ":" +
                    (m < 10 ? "0" + m : m) + ":" +
                    (s < 10 ? "0" + s : s);

                document.getElementById('countdownTimer').innerText = "Sisa Waktu: " + formatted;
                remainingSeconds--;
            }

            // Jalankan tiap detik
            setInterval(updateCountdown, 1000);
            updateCountdown(); // Call first time immediately
        <?php endif; ?>

        // 2. SUARA BEEP
        const audioCtx = new(window.AudioContext || window.webkitAudioContext)();

        function playSound(type) {
            if (audioCtx.state === 'suspended') audioCtx.resume();
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.connect(gain);
            gain.connect(audioCtx.destination);

            if (type === 'success') {
                osc.type = 'sine';
                osc.frequency.setValueAtTime(880, audioCtx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(440, audioCtx.currentTime + 0.1);
            } else {
                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(150, audioCtx.currentTime);
            }
            gain.gain.setValueAtTime(0.1, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.3);
            osc.start();
            osc.stop(audioCtx.currentTime + 0.3);
        }

        // 3. INISIALISASI KAMERA (Kembali ke mode UI Bawaan Library agar kompatibel di semua HP)
        <?php if (!$is_session_closed): ?>
            const config = {
                fps: 10,
                qrbox: {
                    width: 250,
                    height: 250
                },
                supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA],
                aspectRatio: 1.0
            };

            // Gunakan API Langsung agar kamera otomatis menyala
            let html5QrCode = new Html5Qrcode("reader");
            let isProcessing = false;

            function onScanSuccess(decodedText) {
                if (isProcessing) return;
                isProcessing = true;

                // Jeda kamera saat memproses
                html5QrCode.pause();

                fetch('proses_absen.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            nik: decodedText
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        showModal(data);
                    })
                    .catch(err => {
                        showModal({
                            status: 'error',
                            message: 'Koneksi Server Gagal'
                        });
                    });
            }

            // Mulai kamera otomatis dengan kamera belakang (environment)
            html5QrCode.start(
                { facingMode: "environment" },
                config,
                onScanSuccess
            ).catch((err) => {
                document.getElementById('reader').innerHTML = "<div style='color:red; text-align:center; padding:20px;'><h3 style='margin-bottom:10px;'>Akses Kamera Ditolak</h3><p>Gagal mengakses kamera. Harap pastikan Anda telah memberikan izin kamera pada browser Anda.</p></div>";
                console.error("Gagal memulai kamera: ", err);
            });

            // 4. MODAL LOGIC
            const modal = document.getElementById('resultModal');
            const mTitle = document.getElementById('mTitle');
            const mName = document.getElementById('mName');
            const mInfo = document.getElementById('mInfo');
            const mIcon = document.getElementById('mIcon');
            const btnModal = document.querySelector('.btn-modal');

            function showModal(data) {
                modal.style.display = 'flex';

                if (data.status === 'success') {
                    playSound('success');
                    mIcon.innerHTML = "✅";
                    mTitle.innerText = "ABSEN " + data.type;
                    mTitle.style.color = "#15803d";
                    mName.innerText = data.nama;
                    mInfo.innerText = data.waktu + " • " + data.ket;
                    btnModal.style.backgroundColor = "#15803d";
                    btnModal.innerText = "OK, LANJUT";
                } else {
                    playSound('error');
                    mIcon.innerHTML = "⛔";
                    mTitle.innerText = "GAGAL";
                    mTitle.style.color = "#b91c1c";
                    mName.innerText = data.message;
                    mInfo.innerText = "Silakan Coba Lagi";
                    btnModal.style.backgroundColor = "#b91c1c";
                    btnModal.innerText = "COBA LAGI";
                }

                setTimeout(() => {
                    if (modal.style.display == 'flex') closeModal();
                }, 2500);
            }

            function closeModal() {
                modal.style.display = 'none';
                // Lanjutkan kamera setelah ditutup
                setTimeout(() => {
                    isProcessing = false;
                    html5QrCode.resume();
                }, 1500); // delay 1.5 detik agar gak kepencet dobel
            }
        <?php endif; ?>
    </script>
</body>

</html>