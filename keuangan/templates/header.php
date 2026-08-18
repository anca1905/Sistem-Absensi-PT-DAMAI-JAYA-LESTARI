<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'keuangan') {
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
    <title>Keuangan Dashboard - PT DJL</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        :root {
            --primary-start: #064e3b;
            --primary-end:   #065f46;
            --accent:        #10b981;
            --accent-light:  #d1fae5;
            --bg-color:      #f0fdf4;
            --text-main:     #064e3b;
            --text-muted:    #6b7280;
            --sidebar-width: 260px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', system-ui, -apple-system, sans-serif; }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-start) 0%, var(--primary-end) 100%);
            color: white;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            left: 0; top: 0;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
            z-index: 100;
        }

        .sidebar-header {
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header svg { color: var(--accent); }

        .brand-text { font-size: 20px; font-weight: 800; letter-spacing: 1px; }
        .brand-sub { font-size: 11px; color: #a7f3d0; font-weight: 500; text-transform: uppercase; letter-spacing: 2px; }

        .nav-list {
            padding: 20px 16px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            flex: 1;
            overflow-y: auto;
        }

        .nav-section-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #6ee7b7;
            padding: 12px 16px 4px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 16px;
            border-radius: 12px;
            color: #a7f3d0;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s;
        }

        .nav-item:hover { background: rgba(255, 255, 255, 0.08); color: white; }
        .nav-item.active { background: var(--accent); color: white; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4); }
        .nav-item svg { width: 20px; height: 20px; flex-shrink: 0; }

        /* ===== MAIN CONTENT ===== */
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
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
            position: sticky;
            top: 0;
            z-index: 90;
            border-bottom: 1px solid #e5e7eb;
        }

        .page-title-top { font-size: 18px; font-weight: 700; color: var(--text-main); }

        .account-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #f0fdf4;
            padding: 8px 16px;
            border-radius: 30px;
            border: 1px solid #d1fae5;
            cursor: pointer;
            transition: all 0.2s;
        }
        .account-btn:hover { background: #dcfce7; }

        .avatar {
            width: 32px; height: 32px;
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
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-in { animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards; }

        /* Shared Utilities */
        .card {
            background: white;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 20px rgba(6, 78, 59, 0.04);
            overflow: hidden;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, var(--accent) 0%, #059669 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
            text-decoration: none;
        }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(16,185,129,.35); }
        .btn-primary:active { transform: translateY(0); }
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
                <line x1="12" y1="1" x2="12" y2="23"></line>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
            <div>
                <div class="brand-text">PT DJL</div>
                <div class="brand-sub">Keuangan Portal</div>
            </div>
        </div>

        <nav class="nav-list">
            <div class="nav-section-label">Menu Utama</div>

            <a href="index.php" class="nav-item <?= $current_page == 'index.php' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                Dashboard
            </a>

            <a href="laporan_absensi.php" class="nav-item <?= $current_page == 'laporan_absensi.php' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                Lap. Absensi
            </a>

            <a href="lap_keseluruhan.php" class="nav-item <?= $current_page == 'lap_keseluruhan.php' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                    <line x1="3" y1="9" x2="21" y2="9"></line><line x1="3" y1="15" x2="21" y2="15"></line>
                    <line x1="9" y1="9" x2="9" y2="21"></line>
                </svg>
                Lap. Keseluruhan
            </a>

            <div style="flex: 1;"></div>

            <a href="../logout.php" class="nav-item" style="color: #fca5a5;">
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
                <?= $current_page == 'index.php' ? 'Dashboard' :
                    ($current_page == 'laporan_absensi.php' ? 'Laporan Absensi' :
                    ($current_page == 'lap_keseluruhan.php' ? 'Laporan Keseluruhan' : 'Keuangan'))
                ?>
            </div>
            <div class="account-btn">
                <div class="avatar">Keu</div>
                <div style="font-size: 13px; font-weight: 600; color: #064e3b;">
                    <?= htmlspecialchars($_SESSION['nama'] ?? 'Keuangan') ?>
                </div>
            </div>
        </header>

        <!-- Dynamic Content injected here -->
        <div class="content-container animate-in">
