<?php
require '../config/config.php';

// -----------------------------------------------------
// LOGIKA COUNTDOWN 2 JAM & AUTO ALPHA (BERBASIS DATABASE)
// Bebas masalah file permission di hosting/server Linux
// -----------------------------------------------------
$today = date('Y-m-d');
$now_time = time();
$two_hours_in_seconds = 2 * 60 * 60; // 7200 detik

// Pastikan tabel scan_sessions ada
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS scan_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_date DATE NOT NULL UNIQUE,
    start_timestamp INT NOT NULL,
    start_time_formatted VARCHAR(10) NOT NULL
)");

$q_sess = mysqli_query($conn, "SELECT * FROM scan_sessions WHERE session_date = '$today' LIMIT 1");
if ($q_sess && mysqli_num_rows($q_sess) > 0) {
    $sess_row = mysqli_fetch_assoc($q_sess);
    $start_time = (int)$sess_row['start_timestamp'];
    $elapsed = $now_time - $start_time;
    $remaining_seconds = $two_hours_in_seconds - $elapsed;
} else {
    $start_formatted = date('H:i:s');
    mysqli_query($conn, "INSERT IGNORE INTO scan_sessions (session_date, start_timestamp, start_time_formatted) VALUES ('$today', $now_time, '$start_formatted')");
    $remaining_seconds = $two_hours_in_seconds;
}

$is_session_closed = false;

// Jika waktu habis, tandai ALPHA bagi yang belum absen
if ($remaining_seconds <= 0) {
    $is_session_closed = true;
    $remaining_seconds = 0;

    // Proses Alpha terpadu untuk hari ini
    checkAndSetAlpha($conn);
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
            padding: 18px 20px;
            text-align: center;
            border-bottom: 4px solid #f59e0b;
            color: white;
            position: relative;
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
            margin-top: 4px;
            margin-bottom: 0;
        }

        .btn-header-back {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.18);
            color: white;
            text-decoration: none;
            padding: 7px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
            border: 1px solid rgba(255, 255, 255, 0.25);
        }

        .btn-header-back:hover {
            background: rgba(255, 255, 255, 0.32);
            color: white;
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
            font-size: 15px;
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

        /* --- AREA KAMERA --- */
        #reader {
            width: 100%;
            min-height: 300px;
            background: #000;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
        }

        #reader video {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
            border-radius: 12px;
        }

        .loading-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 14px;
            z-index: 1;
        }

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
            padding: 14px 20px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
        }

        .btn-back {
            text-decoration: none;
            color: #64748b;
            font-size: 13px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s;
        }

        .btn-back:hover {
            color: #1e3a8a;
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
            max-width: 360px;
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            animation: popUp 0.3s ease-out;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
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
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 6px;
        }

        .modal-time {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 18px;
            background: #f1f5f9;
            padding: 6px 12px;
            border-radius: 6px;
            display: inline-block;
        }

        .modal-actions {
            display: flex;
            flex-direction: column;
            gap: 9px;
        }

        .btn-modal {
            background-color: #1e3a8a;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            width: 100%;
            font-size: 14px;
            transition: all 0.2s;
        }

        .btn-modal:hover {
            opacity: 0.95;
            transform: translateY(-1px);
        }

        .btn-modal-back {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #f8fafc;
            color: #475569;
            text-decoration: none;
            padding: 11px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            border: 1.5px solid #cbd5e1;
            transition: all 0.2s;
        }

        .btn-modal-back:hover {
            background: #e2e8f0;
            color: #1e293b;
        }
    </style>
</head>

<body>

    <div class="scan-container">
        <div class="scan-header">
            <a href="index.php" class="btn-header-back" title="Kembali ke Dashboard">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                <span>Kembali</span>
            </a>
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
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Kembali ke Dashboard
            </a>
        </div>
    </div>

    <!-- Modal Popup Hasil Scan -->
    <div id="resultModal" class="modal-overlay">
        <div class="modal-box">
            <span id="mIcon" class="modal-icon">✅</span>
            <h3 id="mTitle" class="modal-title">BERHASIL</h3>
            <p id="mName" class="modal-desc">Nama Personel</p>
            <div id="mInfo" class="modal-time">08:00:00</div>

            <div class="modal-actions">
                <button type="button" class="btn-modal" onclick="closeModal()">LANJUT SCAN</button>
                <a href="index.php" class="btn-modal-back">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Selesai &amp; Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>

    <script src="../assets/js/html5-qrcode.min.js"></script>

    <script>
        // 1. JAM DIGITAL
        setInterval(() => {
            const clockEl = document.getElementById('clock');
            if (clockEl) {
                clockEl.innerText = new Date().toLocaleTimeString('id-ID');
            }
        }, 1000);

        <?php if (!$is_session_closed): ?>
            // --- LOGIKA COUNTDOWN TIMER ---
            let remainingSeconds = <?= $remaining_seconds ?>;

            function updateCountdown() {
                if (remainingSeconds <= 0) {
                    document.getElementById('countdownTimer').innerText = "WAKTU HABIS!";
                    if (typeof html5QrCode !== 'undefined' && html5QrCode) {
                        try {
                            html5QrCode.stop().then(() => html5QrCode.clear());
                        } catch (e) {}
                    }

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

            setInterval(updateCountdown, 1000);
            updateCountdown();
        <?php endif; ?>

        // 2. SUARA BEEP
        const audioCtx = new(window.AudioContext || window.webkitAudioContext)();

        function playSound(type) {
            try {
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
            } catch (e) {}
        }

        // 3. INISIALISASI KAMERA DENGAN FALLBACK AMAN
        <?php if (!$is_session_closed): ?>
            const config = {
                fps: 10,
                qrbox: {
                    width: 250,
                    height: 250
                },
                aspectRatio: 1.0
            };

            let html5QrCode = new Html5Qrcode("reader");
            let isProcessing = false;

            function onScanSuccess(decodedText) {
                if (isProcessing) return;
                isProcessing = true;

                try { html5QrCode.pause(); } catch (e) {}

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

            // Jalankan kamera: coba kamera belakang dulu, jika gagal coba kamera depan/apa saja
            html5QrCode.start(
                { facingMode: "environment" },
                config,
                onScanSuccess
            ).catch(err => {
                console.warn("Kamera belakang tidak tersedia, mencoba kamera depan/default...", err);
                html5QrCode.start(
                    { facingMode: "user" },
                    config,
                    onScanSuccess
                ).catch(err2 => {
                    const readerEl = document.getElementById('reader');
                    if (readerEl) {
                        readerEl.innerHTML = "<div style='color:#ef4444; text-align:center; padding:24px;'><h3 style='margin-bottom:8px;'>Kamera Tidak Aktif</h3><p style='font-size:13px;'>Pastikan izin akses kamera telah diberikan pada browser Anda.</p></div>";
                    }
                });
            });

            // 4. MODAL LOGIC
            const modal = document.getElementById('resultModal');
            const mTitle = document.getElementById('mTitle');
            const mName = document.getElementById('mName');
            const mInfo = document.getElementById('mInfo');
            const mIcon = document.getElementById('mIcon');
            const btnModal = document.querySelector('.btn-modal');
            let autoCloseTimer = null;

            function showModal(data) {
                modal.style.display = 'flex';
                if (autoCloseTimer) clearTimeout(autoCloseTimer);

                if (data.status === 'success') {
                    playSound('success');
                    mIcon.innerHTML = "✅";
                    mTitle.innerText = "ABSEN " + data.type;
                    mTitle.style.color = "#15803d";
                    mName.innerText = data.nama;
                    mInfo.innerText = data.waktu + " • " + (data.ket || 'Tercatat');
                    btnModal.style.backgroundColor = "#15803d";
                    btnModal.innerText = "LANJUT SCAN";
                } else if (data.status === 'info') {
                    playSound('success');
                    mIcon.innerHTML = "ℹ️";
                    mTitle.innerText = "INFO ABSENSI";
                    mTitle.style.color = "#0284c7";
                    mName.innerText = data.nama || 'Informasi';
                    mInfo.innerText = data.message || 'Sudah tercatat';
                    btnModal.style.backgroundColor = "#0284c7";
                    btnModal.innerText = "LANJUT SCAN";
                } else {
                    playSound('error');
                    mIcon.innerHTML = "⛔";
                    mTitle.innerText = "GAGAL";
                    mTitle.style.color = "#b91c1c";
                    mName.innerText = data.message || 'Gagal memproses';
                    mInfo.innerText = "Silakan Coba Lagi";
                    btnModal.style.backgroundColor = "#b91c1c";
                    btnModal.innerText = "COBA LAGI";
                }

                autoCloseTimer = setTimeout(() => {
                    if (modal.style.display === 'flex') closeModal();
                }, 3500);
            }

            function closeModal() {
                if (autoCloseTimer) clearTimeout(autoCloseTimer);
                modal.style.display = 'none';
                setTimeout(() => {
                    isProcessing = false;
                    try { html5QrCode.resume(); } catch (e) {}
                }, 1000);
            }
        <?php endif; ?>
    </script>
</body>

</html>
