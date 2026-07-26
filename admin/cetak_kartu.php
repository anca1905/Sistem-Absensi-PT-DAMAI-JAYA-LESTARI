<?php
require '../config/config.php';
require '../libs/phpqrcode/qrlib.php';

// Cek ID
if (!isset($_GET['id'])) die("Error: ID tidak ditemukan.");
$id = mysqli_real_escape_string($conn, $_GET['id']);
$karyawan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id='$id'"));
if (!$karyawan) die("Error: Karyawan tidak ditemukan.");

// Generate QR Code
$tempDir = "../uploads/qr_temp/";
if (!file_exists($tempDir)) mkdir($tempDir, 0775, true);
$fileName = "qr_" . $karyawan['nik'] . ".png";
$filePath = $tempDir . $fileName;
if (!file_exists($filePath)) {
    QRcode::png($karyawan['nik'], $filePath, QR_ECLEVEL_H, 4, 2);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Cetak Kartu - <?= htmlspecialchars($karyawan['name']) ?></title>
    <script src="../assets/js/html2canvas.min.js"></script>

    <style>
        /* --- CSS NATIVE (Tanpa Framework) --- */
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #e2e8f0;
            margin: 0;
            padding: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        h2 {
            color: #334155;
            margin-bottom: 10px;
        }

        p {
            color: #64748b;
            margin-bottom: 30px;
            font-size: 14px;
        }

        /* Container untuk menata kartu bersebelahan */
        .cards-wrapper {
            display: flex;
            gap: 40px;
            flex-wrap: wrap;
            justify-content: center;
            margin-bottom: 40px;
        }

        /* --- DESAIN KARTU (UKURAN PRESISI ID CARD) --- */
        /* Ukuran CR80: 85.6mm x 53.98mm. 
           Kita konversi ke Pixel (scale up biar tajam pas di print) */
        .id-card {
            width: 500px;
            /* Ukuran tampilan di layar (rasio tetap) */
            height: 315px;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            position: relative;
            background-color: #fff;
            /* Flexbox untuk layout isi kartu */
            display: flex;
        }

        /* --- SISI DEPAN (GRADIENT) --- */
        #cardFront {
            background: linear-gradient(135deg, #4f46e5 0%, #2563eb 100%);
            color: white;
        }

        .card-content-left {
            flex: 2;
            padding: 25px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            z-index: 2;
        }

        /* Hiasan background lingkaran transparan */
        .card-circle {
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            z-index: 1;
            pointer-events: none;
        }

        .company-name {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 2px;
            opacity: 0.8;
            margin-bottom: 5px;
        }

        .company-title {
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 25px;
        }

        .emp-name {
            font-size: 24px;
            font-weight: 800;
            text-transform: uppercase;
            line-height: 1.1;
            margin-bottom: 5px;
        }

        .emp-role {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 600;
        }

        .emp-nik-label {
            font-size: 10px;
            margin-top: 20px;
            opacity: 0.7;
            text-transform: uppercase;
        }

        .emp-nik {
            font-family: 'Courier New', monospace;
            font-size: 18px;
            letter-spacing: 1px;
            font-weight: bold;
        }

        .card-content-right {
            flex: 1;
            background: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-left: 4px solid #fbbf24;
            /* Aksen Kuning Emas */
        }

        .qr-box {
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }

        .qr-img {
            width: 100px;
            height: 100px;
        }

        .scan-text {
            color: #64748b;
            font-size: 10px;
            margin-top: 8px;
            font-weight: 600;
        }

        /* --- SISI BELAKANG (PUTIH BERSIH) --- */
        #cardBack {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            flex-direction: column;
            /* Stack vertikal */
            padding: 30px;
            justify-content: space-between;
        }

        .back-header {
            text-align: center;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }

        .back-title {
            color: #1e293b;
            font-weight: 800;
            font-size: 16px;
            text-transform: uppercase;
        }

        .rules-list {
            font-size: 12px;
            color: #475569;
            line-height: 1.6;
            padding-left: 20px;
        }

        .rules-list li {
            margin-bottom: 6px;
        }

        .back-footer {
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
            margin-top: auto;
        }

        /* --- TOMBOL --- */
        .btn-group {
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            transition: transform 0.1s;
        }

        .btn:active {
            transform: scale(0.95);
        }

        .btn-blue {
            background-color: #2563eb;
            color: white;
        }

        .btn-gray {
            background-color: #64748b;
            color: white;
        }

        .btn-green {
            background-color: #10b981;
            color: white;
        }

        /* Loading Overlay */
        #loading {
            display: none;
            margin-left: 10px;
            color: #475569;
            font-size: 14px;
        }
    </style>
</head>

<body>

    <h2>Preview Kartu Karyawan</h2>
    <p>Pastikan desain sudah benar sebelum di-download.</p>

    <div class="cards-wrapper">

        <div id="cardFront" class="id-card">
            <div class="card-circle"></div>
            <div class="card-content-left">
                <div class="company-name">ID Card Pegawai</div>
                <div class="company-title">PT DAMAI JAYA LESTARI</div>

                <div class="emp-name"><?= $karyawan['name'] ?></div>
                <div class="emp-role"><?= $karyawan['jabatan'] ?></div>

                <div class="emp-nik-label">Nomor Induk Pegawai</div>
                <div class="emp-nik"><?= $karyawan['nik'] ?></div>
            </div>
            <div class="card-content-right">
                <div class="qr-box">
                    <img src="<?= $filePath ?>" class="qr-img" alt="QR">
                </div>
                <div class="scan-text">SCAN ME</div>
            </div>
        </div>

        <div id="cardBack" class="id-card">
            <div class="back-header">
                <div class="back-title">Ketentuan Penggunaan</div>
            </div>
            <ul class="rules-list">
                <li>Kartu ini adalah milik PT Damai Jaya Lestari.</li>
                <li>Wajib dikenakan selama berada di lingkungan kerja.</li>
                <li>Gunakan kartu ini untuk melakukan absensi (Scan QR).</li>
                <li>Jika menemukan kartu ini, harap kembalikan ke kantor pusat.</li>
                <li>Kehilangan kartu akan dikenakan denda administrasi.</li>
            </ul>
            <div class="back-footer">
                Jl. Jendral Sudirman No. 123, Jakarta Pusat<br>
                Telp: (021) 123-4567 | www.djl-corp.com
            </div>
        </div>

    </div>

    <div class="btn-group">
        <a href="personil.php" class="btn btn-gray">
            &larr; Kembali
        </a>

        <button onclick="downloadCards()" class="btn btn-blue">
            ⬇️ Download Gambar (PNG)
        </button>

        <span id="loading">Sedang memproses...</span>
    </div>

    <script>
        function downloadCards() {
            // Tampilkan loading
            document.getElementById('loading').style.display = 'inline-block';

            // 1. Screenshot Bagian DEPAN
            html2canvas(document.getElementById('cardFront'), {
                scale: 2
            }).then(canvas => {
                // scale: 2 agar resolusi tinggi (HD)
                let link = document.createElement('a');
                link.download = 'ID_Depan_<?= $karyawan['nik'] ?>.png';
                link.href = canvas.toDataURL("image/png");
                link.click();
            });

            // 2. Screenshot Bagian BELAKANG (Kasih jeda dikit biar ga crash browser)
            setTimeout(() => {
                html2canvas(document.getElementById('cardBack'), {
                    scale: 2
                }).then(canvas => {
                    let link = document.createElement('a');
                    link.download = 'ID_Belakang_<?= $karyawan['nik'] ?>.png';
                    link.href = canvas.toDataURL("image/png");
                    link.click();

                    // Sembunyikan loading
                    document.getElementById('loading').style.display = 'none';
                });
            }, 500);
        }
    </script>

</body>

</html>