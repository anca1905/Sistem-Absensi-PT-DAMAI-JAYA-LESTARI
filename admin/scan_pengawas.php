<?php
require '../config/config.php';
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
            margin-bottom: 15px;
            font-family: monospace;
            letter-spacing: 1px;
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

            <div id="reader">
                <div class="loading-text">Memuat Kamera...</div>
            </div>

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

        // 3. INISIALISASI KAMERA (METODE LANGSUNG/PRO)
        const html5QrCode = new Html5Qrcode("reader");
        let isProcessing = false;

        function startCamera() {
            // Konfigurasi PENTING untuk HP
            const config = {
                fps: 10,
                qrbox: {
                    width: 250,
                    height: 250
                },
                aspectRatio: 1.0
            };

            // Paksa Kamera Belakang (facingMode: "environment")
            html5QrCode.start({
                    facingMode: "environment"
                },
                config,
                onScanSuccess,
                onScanFailure
            ).catch(err => {
                // Jika gagal (misal kamera belakang tidak terdeteksi), coba kamera apa saja
                console.warn("Gagal akses kamera belakang, mencoba mode user...", err);
                html5QrCode.start({
                        facingMode: "user"
                    },
                    config,
                    onScanSuccess,
                    onScanFailure
                ).catch(err2 => {
                    // Tampilkan Error di Layar HP
                    const errDiv = document.getElementById('error-msg');
                    errDiv.style.display = 'block';
                    errDiv.innerHTML = "<strong>Kamera Error:</strong> " + err2;
                });
            });
        }

        // Panggil fungsi start saat halaman siap
        startCamera();

        // Callback saat QR terbaca
        function onScanSuccess(decodedText) {
            if (isProcessing) return;
            isProcessing = true;

            html5QrCode.pause(); // Bekukan kamera saat proses

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
                .then(data => showModal(data))
                .catch(err => showModal({
                    status: 'error',
                    message: 'Koneksi Gagal'
                }));
        }

        // Callback saat scan gagal (biarkan kosong agar tidak spam log)
        function onScanFailure(error) {}

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
                mInfo.innerText = "Coba Lagi";
                btnModal.style.backgroundColor = "#b91c1c";
                btnModal.innerText = "TUTUP";
            }

            // Auto close 2.5 detik
            setTimeout(() => {
                if (modal.style.display == 'flex') closeModal();
            }, 2500);
        }

        function closeModal() {
            modal.style.display = 'none';
            html5QrCode.resume();
            setTimeout(() => {
                isProcessing = false;
            }, 500);
        }
    </script>
</body>

</html>