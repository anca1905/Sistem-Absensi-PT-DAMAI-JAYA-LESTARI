<?php
// Cek session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'kerani') {
    header("Location: ../index.php");
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/print.css" media="print">
    <title>Kerani Dashboard - PT DJL</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        :root {
            --primary-start: #0f172a;
            --primary-end: #1e293b;
            --accent: #3b82f6;
            --bg-color: #f1f5f9;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --sidebar-width: 260px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-start) 0%, var(--primary-end) 100%);
            color: white;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.05);
            z-index: 100;
        }

        .sidebar-header {
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header svg {
            color: var(--accent);
        }

        .brand-text {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 1px;
        }

        .brand-sub {
            font-size: 11px;
            color: #94a3b8;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .nav-list {
            padding: 20px 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1;
            overflow-y: auto;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            border-radius: 12px;
            color: #cbd5e1;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.05);
            color: white;
        }

        .nav-item.active {
            background: var(--accent);
            color: white;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .nav-item svg {
            width: 20px;
            height: 20px;
        }

        /* MAIN CONTENT AREA */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* TOPBAR */
        .topbar {
            height: 70px;
            background: white;
            padding: 0 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
            position: sticky;
            top: 0;
            z-index: 90;
        }

        .page-title-top {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-main);
        }

        .account-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #f8fafc;
            padding: 8px 16px;
            border-radius: 30px;
            border: 1px solid #e2e8f0;
            cursor: pointer;
            transition: all 0.2s;
        }

        .account-btn:hover {
            background: #f1f5f9;
        }

        .avatar {
            width: 32px;
            height: 32px;
            background: var(--accent);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
        }

        /* CONTENT CONTAINER */
        .content-container {
            padding: 32px;
            flex: 1;
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-in {
            animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
    </style>
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
</head>

<body>

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" />
            </svg>
            <div>
                <div class="brand-text">PT DJL</div>
                <div class="brand-sub">Kerani Portal</div>
            </div>
        </div>

        <nav class="nav-list">
            <a href="index.php" class="nav-item <?= $current_page == 'index.php' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                Dashboard
            </a>

            <a href="data_personil.php" class="nav-item <?= $current_page == 'data_personil.php' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                Data Personil
            </a>

            <a href="scanner.php" class="nav-item <?= $current_page == 'scanner.php' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="12" y1="3" x2="12" y2="21"></line>
                </svg>
                Buka Scanner
            </a>

            <a href="laporan_absensi.php" class="nav-item <?= $current_page == 'laporan_absensi.php' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                Laporan Absensi
            </a>

            <a href="validasi_izin.php" class="nav-item <?= $current_page == 'validasi_izin.php' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <path d="M9 15l2 2 4-4"></path>
                </svg>
                Validasi Izin
            </a>

            <a href="laporan_kinerja.php" class="nav-item <?= $current_page == 'laporan_kinerja.php' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
                Laporan Kinerja
            </a>

            <div style="flex: 1;"></div>

            <a href="../logout.php" class="nav-item" style="color: #ef4444;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                Keluar
            </a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- TOPBAR -->
        <header class="topbar">
            <div class="page-title-top">
                <?=
                $current_page == 'index.php' ? 'Dashboard' : ($current_page == 'data_personil.php' ? 'Data Personil' : ($current_page == 'scanner.php' ? 'Sistem Absensi Scanner' : ($current_page == 'laporan_absensi.php' ? 'Laporan Absensi' : ($current_page == 'validasi_izin.php' ? 'Validasi Izin / Sakit / Cuti' : ($current_page == 'laporan_kinerja.php' ? 'Laporan Kinerja' : ($current_page == 'slip_gaji.php' ? 'Slip Gaji' : ''))))))
                ?>
            </div>

            <div class="account-btn">
                <div class="avatar">K</div>
                <div style="font-size: 13px; font-weight: 600; color: #334155;">
                    <?= htmlspecialchars($_SESSION['nama'] ?? 'Kerani') ?>
                </div>
            </div>
        </header>

        <!-- Dynamic Content injected here -->
        <div class="content-container animate-in">