<?php
require '../config/config.php';
include 'templates/header.php';
?>

<style>
    /* Welcome Card */
    .welcome-card {
        background: linear-gradient(135deg, var(--primary-start) 0%, var(--primary-end) 100%);
        border-radius: 20px;
        padding: 24px;
        color: white;
        margin-bottom: 24px;
        box-shadow: 0 10px 25px rgba(54, 72, 217, 0.25);
        position: relative;
        overflow: hidden;
    }

    .welcome-card::after {
        content: '';
        position: absolute;
        top: -50px;
        right: -30px;
        width: 150px;
        height: 150px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
    }

    .welcome-title {
        font-size: 22px;
        font-weight: 800;
        margin: 0 0 4px 0;
    }
    
    .welcome-subtitle {
        font-size: 13px;
        opacity: 0.9;
        margin: 0;
    }

    /* Menu List */
    .menu-list {
        display: flex;
        flex-direction: column;
        gap: 14px;
        margin-bottom: 32px;
    }

    .menu-btn {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        padding: 16px 20px;
        border-radius: 16px;
        font-weight: 700;
        font-size: 15px;
        color: var(--text-dark);
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.03);
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .menu-btn::before {
        content: '';
        position: absolute;
        left: 0; top: 0; bottom: 0;
        width: 4px;
        background: var(--primary-start);
        opacity: 0;
        transition: opacity 0.2s;
    }

    .menu-btn:hover, .menu-btn:active {
        transform: translateY(-2px);
        box-shadow: 0 8px 15px rgba(54, 72, 217, 0.1);
        border-color: #cbd5e1;
    }
    .menu-btn:active { transform: translateY(0); }
    .menu-btn:hover::before { opacity: 1; }

    .menu-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        background: var(--primary-light);
        color: var(--primary-start);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .menu-text {
        flex: 1;
    }

    .menu-arrow {
        color: #94a3b8;
    }

    /* Status Card */
    .status-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        margin-bottom: 20px;
    }
    
    .status-card h3 {
        margin: 0 0 16px 0;
        font-size: 16px;
        font-weight: 800;
        color: var(--text-dark);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .status-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px;
        background: #f8fafc;
        border-radius: 12px;
        border: 1px solid #f1f5f9;
        margin-bottom: 12px;
        transition: background 0.2s;
    }
    .status-item:hover { background: white; border-color: #e2e8f0; }
    .status-item:last-child { margin-bottom: 0; }

    .status-text {
        font-weight: 700;
        font-size: 14px;
        color: var(--text-dark);
    }
    
    .status-desc {
        font-size: 12px;
        color: var(--text-muted);
        margin-top: 4px;
    }

    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.5px;
    }
    .status-badge-proses { background: #fef9c3; color: #a16207; }
    .status-badge-ditolak { background: #fee2e2; color: #b91c1c; }
    .status-badge-diterima { background: #dcfce7; color: #166534; }
</style>

<div class="animate-up" style="animation-delay: 0.1s;">
    
    <!-- Welcome Card -->
    <div class="welcome-card">
        <h1 class="welcome-title">Hai, <?= htmlspecialchars($_SESSION['nama'] ?? 'Karyawan') ?> 👋</h1>
        <p class="welcome-subtitle">Tetap semangat bekerja hari ini!</p>
    </div>

    <!-- 5 Menu Buttons Sesuai Sketsa 1 -->
    <div class="menu-list">
        <a href="absen_mandiri.php" class="menu-btn">
            <div class="menu-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            </div>
            <div class="menu-text">Jam Masuk</div>
            <svg class="menu-arrow" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
        </a>
        
        <a href="form_izin.php" class="menu-btn">
            <div class="menu-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
            </div>
            <div class="menu-text">Ajukan Izin/Sakit/Cuti</div>
            <svg class="menu-arrow" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
        </a>
        
        <a href="logbook.php" class="menu-btn">
            <div class="menu-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            </div>
            <div class="menu-text">Logbook Kegiatan</div>
            <svg class="menu-arrow" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
        </a>

        <a href="rekapan_absensi.php" class="menu-btn">
            <div class="menu-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            </div>
            <div class="menu-text">Rekapan Absensi</div>
            <svg class="menu-arrow" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
        </a>
        
        <a href="slip_gaji.php" class="menu-btn">
            <div class="menu-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
            </div>
            <div class="menu-text">Slip Gaji</div>
            <svg class="menu-arrow" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
        </a>
    </div>

    <!-- Status Izin/Sakit/Cuti Card -->
    <div class="status-card">
        <h3>
            <div class="menu-icon" style="width: 32px; height: 32px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            </div>
            Status Izin/Sakit/Cuti
        </h3>
        
        <div>
            <!-- Dummy Data -->
            <div class="status-item">
                <div>
                    <div class="status-text">Izin (Keperluan Keluarga)</div>
                    <div class="status-desc">Diajukan: 20 Juni 2024</div>
                </div>
                <span class="status-badge status-badge-proses">Proses</span>
            </div>
            
            <div class="status-item">
                <div>
                    <div class="status-text">Sakit (Tifus)</div>
                    <div class="status-desc">Keterangan kurang lengkap</div>
                </div>
                <span class="status-badge status-badge-ditolak">Ditolak</span>
            </div>
        </div>
    </div>

    <!-- Status Logbook Kegiatan Card -->
    <div class="status-card">
        <h3>
            <div class="menu-icon" style="width: 32px; height: 32px; background: #fef3c7; color: #d97706;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
            </div>
            Status Logbook Kegiatan
        </h3>
        
        <div>
            <!-- Dummy Data -->
            <div class="status-item">
                <div>
                    <div class="status-text">Langsir manual</div>
                    <div class="status-desc">03 Juni 2026</div>
                </div>
                <span class="status-badge status-badge-diterima">Diterima</span>
            </div>
            
            <div class="status-item">
                <div>
                    <div class="status-text">Membabat gawangan</div>
                    <div class="status-desc">04 Juni 2026</div>
                </div>
                <span class="status-badge status-badge-ditolak">Ditolak</span>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>