<?php
require '../config/config.php';
include 'templates/header.php';
?>

<style>
    .card-container {
        background: #ffffff;
        border-radius: 16px;
        padding: 24px 16px;
        box-shadow: 0 4px 20px rgba(54, 72, 217, 0.05);
        border: 1px solid #f1f5f9;
        margin-bottom: 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .slip-preview {
        width: 100%;
        max-width: 300px;
        aspect-ratio: 3/4;
        background: #f8fafc;
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        margin-bottom: 24px;
        color: #94a3b8;
        text-align: center;
        padding: 20px;
    }

    .slip-preview svg {
        margin-bottom: 12px;
        color: var(--primary-start);
        opacity: 0.8;
    }

    .btn-print {
        width: 100%;
        background: linear-gradient(135deg, var(--primary-start) 0%, var(--primary-end) 100%);
        color: white;
        border: none;
        padding: 16px;
        border-radius: 14px;
        font-size: 16px;
        font-weight: 800;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        transition: all 0.2s;
        box-shadow: 0 4px 15px rgba(66, 88, 255, 0.25);
    }

    .btn-print:active {
        transform: scale(0.98);
        box-shadow: none;
    }

    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: var(--text-muted);
        text-decoration: none;
        font-weight: 700;
        font-size: 13px;
        margin-bottom: 16px;
        background: white;
        padding: 8px 14px;
        border-radius: 20px;
        border: 1.5px solid #e2e8f0;
    }

    @media print {
        body * { visibility: hidden; }
        .mobile-container { box-shadow: none; max-width: 100%; width: 100%; margin: 0; padding: 0; }
        .mobile-content { padding: 0; background: white; }
        .animate-up, .animate-up * { visibility: visible; }
        .btn-back, .btn-print, .mobile-header { display: none !important; }
        .card-container { border: none; box-shadow: none; padding: 0; }
        .slip-preview { border: 1px solid #000; background: white; }
    }
</style>

<div class="animate-up">
    <a href="index.php" class="btn-back">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        Kembali
    </a>

    <h2 class="page-title" style="text-align: left; margin-bottom: 20px; font-size: 24px;">Slip Gaji</h2>

    <div class="card-container">
        
        <!-- Kotak Slip Gaji (Sesuai Sketsa 7) -->
        <div class="slip-preview">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
            <span style="font-size: 20px; font-weight: 800; color: var(--text-dark); margin-bottom: 8px;">Slip Gaji</span>
            <span style="font-size: 13px;">Bulan ini<br>(Preview Dokumen)</span>
        </div>

        <!-- Tombol Cetak PDF -->
        <button class="btn-print" onclick="window.print()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                <rect x="6" y="14" width="12" height="8"></rect>
            </svg>
            Cetak PDF
        </button>

    </div>
</div>

<?php include 'templates/footer.php'; ?>
