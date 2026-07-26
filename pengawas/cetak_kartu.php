<?php
require '../config/config.php';
require '../libs/phpqrcode/qrlib.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'pengawas') {
    header("Location: ../index.php");
    exit;
}

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
    <title>Cetak ID Card - <?= htmlspecialchars($karyawan['name']) ?></title>
    <script src="../assets/js/html2canvas.min.js"></script>
    <style>
        /* --- CSS RESET & GLOBAL --- */
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #eef2f7;
            margin: 0;
            padding: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .header-text { text-align: center; margin-bottom: 30px; }
        .header-text h2 { color: #1e293b; margin: 0 0 5px 0; font-size: 24px; }
        .header-text p { color: #64748b; margin: 0; font-size: 14px; }

        .cards-wrapper {
            display: flex; gap: 40px; flex-wrap: wrap; justify-content: center;
            margin-bottom: 40px;
        }

        /* --- UKURAN KARTU PORTRAIT (Standar CR80 Vertical) --- */
        /* Ratio asli: 54mm lebar x 86mm tinggi */
        .id-card {
            width: 320px;
            height: 508px;
            border-radius: 12px;
            box-shadow: 0 15px 30px -5px rgba(0,0,0,0.15);
            background-color: #fff;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            border: 1px solid #e2e8f0;
        }

        /* --- SISI DEPAN --- */
        #cardFront {
            background: #ffffff;
        }

        /* Header Melengkung di atas */
        .front-top {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            height: 140px;
            color: white;
            text-align: center;
            position: relative;
            padding-top: 25px;
            /* Lengkungan ke bawah */
            border-bottom-left-radius: 50% 20px;
            border-bottom-right-radius: 50% 20px;
        }
        
        .company-logo-text { font-size: 18px; font-weight: 800; letter-spacing: 1px; margin-bottom: 4px; }
        .company-sub { font-size: 10px; text-transform: uppercase; letter-spacing: 2px; opacity: 0.9; }

        /* Foto Profil (Placeholder) */
        .profile-pic-container {
            width: 130px; height: 130px;
            background: #ffffff;
            border-radius: 50%;
            position: absolute;
            bottom: -65px; left: 50%;
            transform: translateX(-50%);
            border: 4px solid #ffffff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            display: flex; justify-content: center; align-items: center;
            overflow: hidden;
        }
        
        .profile-img {
            width: 100%; height: 100%; object-fit: cover;
        }

        /* Konten Data Karyawan */
        .front-middle {
            flex: 1;
            text-align: center;
            padding: 85px 20px 20px 20px; /* Padding top besar untuk ruang foto */
            display: flex; flex-direction: column;
        }

        .emp-name { font-size: 22px; font-weight: 800; color: #1e293b; margin-bottom: 5px; line-height: 1.2; }
        .emp-role { font-size: 14px; font-weight: 600; color: #3b82f6; margin-bottom: 25px; }

        .data-row { margin-bottom: 12px; }
        .data-label { font-size: 10px; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
        .data-value { font-size: 16px; font-weight: 700; color: #334155; font-family: 'Courier New', monospace; letter-spacing: 1px; }

        /* Footer Aksen Kuning */
        .front-bottom {
            height: 15px;
            background: #f59e0b;
            width: 100%;
        }

        /* --- SISI BELAKANG --- */
        #cardBack {
            background-color: #ffffff;
            justify-content: space-between;
        }

        .back-top {
            background: #f8fafc;
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #e2e8f0;
        }
        .back-title { font-size: 14px; font-weight: 800; color: #1e293b; text-transform: uppercase; letter-spacing: 1px; }

        /* Area QR Code Besar di Belakang */
        .back-middle {
            flex: 1;
            display: flex; flex-direction: column; justify-content: center; align-items: center;
            padding: 20px;
        }
        .qr-wrapper {
            padding: 10px; background: white; border-radius: 12px;
            border: 2px solid #e2e8f0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 15px;
        }
        .qr-wrapper img { width: 150px; height: 150px; display: block; }
        .qr-scan-text { font-size: 12px; font-weight: 700; color: #3b82f6; letter-spacing: 2px; }

        /* Ketentuan Penggunaan */
        .back-rules {
            padding: 0 25px 20px 25px;
        }
        .rules-list { font-size: 10px; color: #64748b; line-height: 1.5; padding-left: 15px; margin: 0; }
        .rules-list li { margin-bottom: 4px; }

        .back-footer {
            background: #1e293b; color: #94a3b8; padding: 15px;
            text-align: center; font-size: 10px; line-height: 1.4;
        }

        /* --- TOMBOL AKSI --- */
        .btn-container { display: flex; gap: 15px; }
        .btn {
            padding: 12px 24px; border: none; border-radius: 8px; font-weight: 600;
            cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-size: 14px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: transform 0.1s;
        }
        .btn:active { transform: scale(0.96); box-shadow: none; }
        .btn-gray { background-color: #ffffff; color: #475569; border: 1px solid #cbd5e1; }
        .btn-blue { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: white; }
        #loading { display: none; font-size: 14px; color: #64748b; font-weight: 600; margin-left: 10px; }
    </style>
</head>
<body>

    <div class="header-text">
        <h2>Pratinjau ID Card Pegawai</h2>
        <p>Model Portrait (Vertical) - Sisi Depan dan Sisi Belakang</p>
    </div>

    <div class="cards-wrapper">

        <div id="cardFront" class="id-card">
            
            <div class="front-top">
                <div class="company-logo-text">PT. DAMAI JAYA LESTARI</div>
                <div class="company-sub">ID Card Pegawai Resmi</div>
                
                <div class="profile-pic-container">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($karyawan['name']) ?>&background=e2e8f0&color=334155&size=200&bold=true" class="profile-img" alt="Foto">
                </div>
            </div>

            <div class="front-middle">
                <div class="emp-name"><?= htmlspecialchars($karyawan['name']) ?></div>
                <div class="emp-role"><?= htmlspecialchars($karyawan['jabatan']) ?></div>

                <div class="data-row">
                    <div class="data-label">Nomor Induk Pegawai (NIK)</div>
                    <div class="data-value"><?= $karyawan['nik'] ?></div>
                </div>
                
                <div class="data-row">
                    <div class="data-label">Email</div>
                    <div class="data-value" style="font-size: 12px; font-family: Arial;"><?= $karyawan['email'] ?></div>
                </div>
            </div>

            <div class="front-bottom"></div>
        </div>

        <div id="cardBack" class="id-card">
            
            <div class="back-top">
                <div class="back-title">Akses Absensi & Keamanan</div>
            </div>

            <div class="back-middle">
                <div class="qr-wrapper">
                    <img src="<?= $filePath ?>" alt="QR Absensi">
                </div>
                <div class="qr-scan-text">SCAN UNTUK ABSEN</div>
            </div>

            <div class="back-rules">
                <ul class="rules-list">
                    <li>Kartu ini adalah milik sah PT Damai Jaya Lestari.</li>
                    <li>Wajib dipakai dengan *lanyard* di area kantor.</li>
                    <li>Gunakan sisi belakang (QR) untuk absen harian.</li>
                    <li>Jika hilang, wajib lapor HRD max 1x24 jam.</li>
                </ul>
            </div>

            <div class="back-footer">
                Jl. Jendral Sudirman No. 123, Jakarta Pusat<br>
                Tel: (021) 123-4567 • www.djl-corp.com
            </div>

        </div>

    </div>

    <div style="display: flex; align-items: center;">
        <div class="btn-container">
            <a href="karyawan.php" class="btn btn-gray">&larr; Kembali</a>
            <button onclick="downloadCards()" class="btn btn-blue">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Unduh Gambar (PNG)
            </button>
        </div>
        <span id="loading">Memproses gambar...</span>
    </div>

    <script>
        function downloadCards() {
            document.getElementById('loading').style.display = 'inline-block';

            // Screenshot Depan
            html2canvas(document.getElementById('cardFront'), { scale: 3 }).then(canvas => {
                let link = document.createElement('a');
                link.download = 'ID_Depan_<?= $karyawan['nik'] ?>.png';
                link.href = canvas.toDataURL("image/png");
                link.click();
            });

            // Screenshot Belakang (Jeda 500ms agar browser tidak lag)
            setTimeout(() => {
                html2canvas(document.getElementById('cardBack'), { scale: 3 }).then(canvas => {
                    let link = document.createElement('a');
                    link.download = 'ID_Belakang_<?= $karyawan['nik'] ?>.png';
                    link.href = canvas.toDataURL("image/png");
                    link.click();
                    
                    document.getElementById('loading').style.display = 'none';
                });
            }, 500);
        }
    </script>

</body>
</html>