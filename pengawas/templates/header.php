<?php
// Cek session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'pengawas') {
    header("Location: ../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <title>Dashboard Pengawas - PT DJL</title>
    
    <link rel="stylesheet" href="../assets/css/print.css" media="print">
    
    <!-- Mobile-first CSS styling (Theme: Blue Premium) -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        :root {
            --primary-start: #4258ff;
            --primary-end: #3648d9;
            --primary-light: #eff1ff;
            --text-dark: #1e293b;
            --text-muted: #64748b;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: #f0f2f5; /* Sesuai body-bg dashboard utama */
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        .mobile-container {
            max-width: 480px; 
            margin: 0 auto;
            background-color: #f8fafc; /* Latar yang sangat terang tapi bukan putih murni */
            min-height: 100vh;
            box-shadow: 0 0 40px rgba(54, 72, 217, 0.15); /* Bayangan kebiruan */
            position: relative;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* Header Biru Khas Dashboard */
        .mobile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            background: linear-gradient(160deg, var(--primary-start) 0%, var(--primary-end) 100%);
            position: sticky;
            top: 0;
            z-index: 10;
            box-shadow: 0 4px 15px rgba(54, 72, 217, 0.3);
            border-bottom-left-radius: 20px;
            border-bottom-right-radius: 20px;
            margin-bottom: 10px;
        }

        .header-title-text {
            color: white;
            font-weight: 800;
            font-size: 16px;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .akun-btn, .keluar-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            color: white;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.2s;
        }

        .akun-btn:active, .keluar-btn:active { 
            background: rgba(255, 255, 255, 0.3); 
            transform: scale(0.95); 
        }

        .keluar-btn {
            background: rgba(239, 68, 68, 0.8); /* Sedikit merah untuk keluar */
            border: 1px solid rgba(239, 68, 68, 0.9);
        }

        .mobile-content {
            flex: 1;
            padding: 20px;
        }

        .page-title {
            font-size: 22px;
            font-weight: 800;
            color: var(--text-dark);
            margin: 0 0 24px 0;
            text-align: center;
            letter-spacing: -0.5px;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-up {
            animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
    </style>
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
</head>
<body>
    <div class="mobile-container">
        <!-- Hidden Print Header (Kop Surat) -->
        <div id="print-header">
            <!-- Asumsikan ada logo di assets/img/logo.png, jika tidak ada, alt text akan tampil -->
            <img src="../assets/img/logo.png" alt="Logo PT DJL" class="print-logo" onerror="this.style.display='none'">
            <div class="print-header-text">
                <h1>PT Damai Jaya Lestari</h1>
                <h2>Kantor Pusat / Cabang Operasional</h2>
                <p>Jl. Jend. Sudirman No. 123, Kel. Suka Maju, Kec. Perkebunan, Kab. Sejahtera</p>
                <p>Telp: (021) 1234567 | Email: info@damaijayalestari.co.id</p>
            </div>
        </div>

        <!-- Header Biru Mobile -->
        <header class="mobile-header">
            <a href="<?= BASE_URL ?>pengawas/index.php" class="akun-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </a>
            
            <div class="header-title-text">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" />
                </svg>
                PT DJL
            </div>

            <a href="<?= BASE_URL ?>logout.php" class="keluar-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
            </a>
        </header>

        <main class="mobile-content">