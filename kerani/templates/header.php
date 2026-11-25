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

// Ambil data lengkap kerani untuk biodata dropdown & header
$header_uid = (int)($_SESSION['user_id'] ?? 0);
$header_user = null;
if (isset($conn) && $header_uid > 0) {
    $q_hu = mysqli_query($conn, "SELECT nik, name, email, no_hp, role, jabatan, afdeling, jenis_kelamin, alamat FROM users WHERE id=$header_uid");
    if ($q_hu && mysqli_num_rows($q_hu) > 0) {
        $header_user = mysqli_fetch_assoc($q_hu);
    }
}
$header_name     = $header_user['name'] ?? $_SESSION['nama'] ?? 'Kerani';
$header_initial  = strtoupper(substr($header_name, 0, 1));
$header_afdeling = $header_user['afdeling'] ?? $_SESSION['afdeling'] ?? '';
$header_nik      = $header_user['nik'] ?? '-';
$header_jabatan  = $header_user['jabatan'] ?: 'Kerani';
$header_email    = $header_user['email'] ?? '-';
$header_nohp     = $header_user['no_hp'] ?? '-';
$header_gender   = $header_user['jenis_kelamin'] ?? '-';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/print.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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

        .nav-badge-count {
            margin-left: auto;
            background: #ef4444;
            color: white;
            font-size: 11px;
            font-weight: 800;
            padding: 2px 7px;
            border-radius: 10px;
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

        /* PROFILE DROPDOWN WRAPPER */
        .profile-dropdown-wrap {
            position: relative;
        }

        .account-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f8fafc;
            padding: 7px 16px;
            border-radius: 30px;
            border: 1.5px solid #e2e8f0;
            cursor: pointer;
            transition: all 0.2s;
            outline: none;
            user-select: none;
        }

        .account-btn:hover, .account-btn.active {
            background: #ffffff;
            border-color: #3b82f6;
            box-shadow: 0 4px 14px rgba(59, 130, 246, 0.15);
        }

        .avatar {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            flex-shrink: 0;
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25);
        }

        .profile-dropdown-menu {
            display: none;
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            width: 330px;
            background: #ffffff;
            border-radius: 18px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 14px 40px rgba(15, 23, 42, 0.16);
            z-index: 1000;
            overflow: hidden;
            animation: dropdownFadeIn 0.22s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .profile-dropdown-menu.show {
            display: block;
        }

        @keyframes dropdownFadeIn {
            from {
                opacity: 0;
                transform: translateY(-8px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .dropdown-header-card {
            padding: 18px 20px;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .dropdown-avatar-circle {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 800;
            border: 2px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35);
            flex-shrink: 0;
        }

        .dropdown-user-name {
            font-size: 15px;
            font-weight: 800;
            color: white;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 220px;
        }

        .dropdown-user-badges {
            display: flex;
            gap: 6px;
            margin-top: 5px;
            flex-wrap: wrap;
        }

        .badge-pill-role {
            background: #3b82f6;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-pill-afd {
            background: #10b981;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 700;
        }

        .dropdown-biodata-list {
            padding: 14px 18px;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .bio-item-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12.5px;
            gap: 10px;
        }

        .bio-item-label {
            color: #64748b;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            width: 95px;
            flex-shrink: 0;
        }

        .bio-item-label i {
            width: 14px;
            color: #3b82f6;
            font-size: 12px;
            text-align: center;
        }

        .bio-item-value {
            color: #0f172a;
            font-weight: 700;
            text-align: right;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 190px;
        }

        .dropdown-divider-line {
            height: 1px;
            background: #f1f5f9;
        }

        .dropdown-actions-box {
            padding: 12px 14px;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .dropdown-act-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 14px;
            border-radius: 9px;
            font-size: 12.5px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.15s;
        }

        .btn-open-profile {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }

        .btn-open-profile:hover {
            background: #dbeafe;
            color: #1e40af;
        }

        .btn-logout-kerani {
            background: #ffffff;
            color: #ef4444;
            border: 1px solid #fecaca;
        }

        .btn-logout-kerani:hover {
            background: #fee2e2;
            color: #dc2626;
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

            <a href="objek_kerja.php" class="nav-item <?= $current_page == 'objek_kerja.php' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"></path>
                    <rect x="9" y="3" width="6" height="4" rx="1" ry="1"></rect>
                    <line x1="9" y1="12" x2="15" y2="12"></line>
                    <line x1="9" y1="16" x2="13" y2="16"></line>
                </svg>
                Objek Kerja
            </a>

            <a href="laporan_keseluruhan.php" class="nav-item <?= $current_page == 'laporan_keseluruhan.php' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                    <line x1="3" y1="9" x2="21" y2="9"></line>
                    <line x1="3" y1="15" x2="21" y2="15"></line>
                    <line x1="9" y1="9" x2="9" y2="21"></line>
                </svg>
                Lap. Keseluruhan
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
                <span>Validasi Izin</span>
                <?php
                $q_nav_p = mysqli_query($conn, "SELECT COUNT(*) as total FROM perizinan WHERE status='pending'");
                $nav_total_p = ($q_nav_p) ? (int)mysqli_fetch_assoc($q_nav_p)['total'] : 0;
                if ($nav_total_p > 0):
                ?>
                    <span class="nav-badge-count"><?= $nav_total_p ?></span>
                <?php endif; ?>
            </a>

            <a href="kirim_laporan.php" class="nav-item <?= $current_page == 'kirim_laporan.php' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 2L11 13"></path>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                </svg>
                <span>Kirim ke Keuangan</span>
                <?php
                $afd_nav_k = mysqli_real_escape_string($conn, $_SESSION['afdeling'] ?? '');
                $q_nav_rev = mysqli_query($conn, "SELECT COUNT(*) as total FROM laporan_pengajuan WHERE afdeling='$afd_nav_k' AND status='ditolak'");
                $nav_rev_total = ($q_nav_rev) ? (int)mysqli_fetch_assoc($q_nav_rev)['total'] : 0;
                if ($nav_rev_total > 0):
                ?>
                    <span class="nav-badge-count" style="background:#ef4444;" title="Ada laporan perlu revisi"><?= $nav_rev_total ?></span>
                <?php endif; ?>
            </a>

            <!-- DISABLED: Laporan Kinerja -->
            <a href="#" class="nav-item" style="display: none;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
                Laporan Kinerja
            </a>

            <!-- DISABLED: Slip Gaji / Penggajian -->
            <a href="#" class="nav-item" style="display: none;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
                Slip Gaji
            </a>

            <div style="flex: 1;"></div>

            <a href="profil.php" class="nav-item <?= $current_page == 'profil.php' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span>Profil &amp; Biodata</span>
            </a>

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
                $current_page == 'index.php' ? 'Dashboard' : ($current_page == 'data_personil.php' ? 'Data Personil' : ($current_page == 'objek_kerja.php' ? 'Laporan Objek Kerja' : ($current_page == 'laporan_keseluruhan.php' ? 'Laporan Keseluruhan' : ($current_page == 'scanner.php' ? 'Sistem Absensi Scanner' : ($current_page == 'laporan_absensi.php' ? 'Laporan Absensi' : ($current_page == 'validasi_izin.php' ? 'Validasi Izin / Sakit / Cuti' : ($current_page == 'kirim_laporan.php' ? 'Kirim Laporan ke Keuangan' : ($current_page == 'profil.php' ? 'Profil &amp; Biodata Kerani' : 'Kerani'))))))))
                ?>
            </div>

            <!-- Profile Dropdown Container -->
            <div class="profile-dropdown-wrap">
                <button type="button" class="account-btn" id="profileDropdownBtn" onclick="toggleProfileDropdown(event)" aria-expanded="false" title="Klik untuk melihat Biodata &amp; Akun">
                    <div class="avatar"><?= $header_initial ?></div>
                    <div style="font-size: 13px; font-weight: 600; color: #334155; display: flex; align-items: center; gap: 7px;">
                        <span><?= htmlspecialchars($header_name) ?></span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" id="profileChevron" style="transition: transform 0.2s;">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                </button>

                <!-- Dropdown Menu / Biodata Card -->
                <div class="profile-dropdown-menu" id="profileDropdownMenu">
                    <div class="dropdown-header-card">
                        <div class="dropdown-avatar-circle">
                            <?= $header_initial ?>
                        </div>
                        <div style="flex: 1; overflow: hidden;">
                            <div class="dropdown-user-name" title="<?= htmlspecialchars($header_name) ?>"><?= htmlspecialchars($header_name) ?></div>
                            <div class="dropdown-user-badges">
                                <span class="badge-pill-role">Kerani</span>
                                <?php if (!empty($header_afdeling)): ?>
                                    <span class="badge-pill-afd"><?= htmlspecialchars($header_afdeling) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="dropdown-biodata-list">
                        <div class="bio-item-row">
                            <span class="bio-item-label"><i class="fa-solid fa-id-card"></i> NIK</span>
                            <span class="bio-item-value"><?= htmlspecialchars($header_nik) ?></span>
                        </div>
                        <div class="bio-item-row">
                            <span class="bio-item-label"><i class="fa-solid fa-briefcase"></i> Jabatan</span>
                            <span class="bio-item-value"><?= htmlspecialchars($header_jabatan) ?></span>
                        </div>
                        <div class="bio-item-row">
                            <span class="bio-item-label"><i class="fa-solid fa-map-pin"></i> Afdeling</span>
                            <span class="bio-item-value"><?= htmlspecialchars($header_afdeling ?: '-') ?></span>
                        </div>
                        <div class="bio-item-row">
                            <span class="bio-item-label"><i class="fa-brands fa-whatsapp"></i> No. WA</span>
                            <span class="bio-item-value"><?= htmlspecialchars($header_nohp) ?></span>
                        </div>
                        <div class="bio-item-row">
                            <span class="bio-item-label"><i class="fa-solid fa-envelope"></i> Email</span>
                            <span class="bio-item-value" title="<?= htmlspecialchars($header_email) ?>"><?= htmlspecialchars($header_email) ?></span>
                        </div>
                        <div class="bio-item-row">
                            <span class="bio-item-label"><i class="fa-solid fa-venus-mars"></i> Gender</span>
                            <span class="bio-item-value"><?= htmlspecialchars($header_gender) ?></span>
                        </div>
                    </div>

                    <div class="dropdown-divider-line"></div>

                    <div class="dropdown-actions-box">
                        <a href="profil.php" class="dropdown-act-link btn-open-profile">
                            <i class="fa-solid fa-user-pen"></i>
                            Buka &amp; Edit Profil Lengkap
                        </a>
                        <a href="../logout.php" class="dropdown-act-link btn-logout-kerani">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                            Keluar dari Sistem
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <script>
        function toggleProfileDropdown(e) {
            e.stopPropagation();
            const menu = document.getElementById('profileDropdownMenu');
            const btn = document.getElementById('profileDropdownBtn');
            const chevron = document.getElementById('profileChevron');
            const isShown = menu.classList.contains('show');

            if (isShown) {
                menu.classList.remove('show');
                btn.classList.remove('active');
                if (chevron) chevron.style.transform = 'rotate(0deg)';
                btn.setAttribute('aria-expanded', 'false');
            } else {
                menu.classList.add('show');
                btn.classList.add('active');
                if (chevron) chevron.style.transform = 'rotate(180deg)';
                btn.setAttribute('aria-expanded', 'true');
            }
        }

        document.addEventListener('click', function(e) {
            const wrap = document.querySelector('.profile-dropdown-wrap');
            if (wrap && !wrap.contains(e.target)) {
                const menu = document.getElementById('profileDropdownMenu');
                const btn = document.getElementById('profileDropdownBtn');
                const chevron = document.getElementById('profileChevron');
                if (menu && menu.classList.contains('show')) {
                    menu.classList.remove('show');
                    if (btn) btn.classList.remove('active');
                    if (chevron) chevron.style.transform = 'rotate(0deg)';
                    if (btn) btn.setAttribute('aria-expanded', 'false');
                }
            }
        });
        </script>

        <!-- Dynamic Content injected here -->
        <div class="content-container animate-in">
