<?php
require '../config/config.php';
include 'templates/header.php';
?>

<style>
    .welcome-banner {
        background: linear-gradient(135deg, var(--primary-start) 0%, var(--primary-end) 100%);
        border-radius: 20px;
        padding: 40px;
        color: white;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.1);
        position: relative;
        overflow: hidden;
    }

    .welcome-banner::after {
        content: '';
        position: absolute;
        top: -50px;
        right: -50px;
        width: 300px;
        height: 300px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 50%;
    }

    .welcome-title {
        font-size: 32px;
        font-weight: 800;
        margin-bottom: 10px;
        position: relative;
        z-index: 1;
    }

    .welcome-subtitle {
        font-size: 16px;
        color: #cbd5e1;
        position: relative;
        z-index: 1;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 24px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        padding: 24px;
        border-radius: 16px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
        border: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        gap: 20px;
        transition: transform 0.2s;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.05);
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }

    .icon-blue {
        background: #eff6ff;
        color: #3b82f6;
    }

    .icon-green {
        background: #f0fdf4;
        color: #22c55e;
    }

    .icon-orange {
        background: #fff7ed;
        color: #f97316;
    }

    .stat-info h3 {
        font-size: 13px;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 5px;
    }

    .stat-info p {
        font-size: 28px;
        font-weight: 800;
        color: var(--text-main);
    }
</style>

<div class="welcome-banner">
    <h1 class="welcome-title">Selamat Datang, <?= htmlspecialchars($_SESSION['nama'] ?? 'Kerani') ?>! 👋</h1>
    <p class="welcome-subtitle">Portal Manajemen Administrasi & Personalia PT DJL</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon icon-blue">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
        </div>
        <div class="stat-info">
            <h3>Total Personil</h3>
            <p>124</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon icon-green">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
        </div>
        <div class="stat-info">
            <h3>Hadir Hari Ini</h3>
            <p>118</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon icon-orange">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <path d="M9 15l2 2 4-4"></path>
            </svg>
        </div>
        <div class="stat-info">
            <h3>Pengajuan Izin</h3>
            <p>6</p>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>