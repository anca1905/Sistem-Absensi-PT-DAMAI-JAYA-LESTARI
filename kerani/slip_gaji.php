<?php
require '../config/config.php';
include 'templates/header.php';
?>

<style>
    .card {
        background: white;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        padding: 40px;
        display: flex;
        flex-direction: column;
        align-items: center;
        min-height: 60vh;
        justify-content: center;
    }

    .slip-preview {
        width: 100%;
        max-width: 400px;
        aspect-ratio: 1/1.4;
        background: #f8fafc;
        border: 2px dashed #cbd5e1;
        border-radius: 16px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        margin-bottom: 30px;
        color: #94a3b8;
        text-align: center;
        padding: 20px;
        transition: all 0.2s;
    }
    .slip-preview:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
    }

    .slip-preview svg {
        margin-bottom: 16px;
        color: var(--accent);
        opacity: 0.8;
    }

    .btn-print {
        background: var(--accent);
        color: white;
        border: none;
        padding: 14px 30px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 16px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: all 0.2s;
        box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
    }
    .btn-print:hover { background: #2563eb; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4); }
    .btn-print:active { transform: translateY(0); }

    @media print {
        body * { visibility: hidden; }
        .main-content { margin-left: 0; }
        .sidebar, .topbar { display: none !important; }
        .card, .card * { visibility: visible; }
        .card { position: absolute; left: 0; top: 0; border: none; box-shadow: none; width: 100%; }
        .btn-print { display: none; }
        .slip-preview { border: 1px solid #000; background: white; max-width: 100%; aspect-ratio: auto; min-height: 800px; }
    }
</style>

<div class="card">
    
    <!-- Kotak Slip Gaji (Sesuai Sketsa 7) -->
    <div class="slip-preview">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
            <line x1="16" y1="13" x2="8" y2="13"></line>
            <line x1="16" y1="17" x2="8" y2="17"></line>
            <polyline points="10 9 9 9 8 9"></polyline>
        </svg>
        <span style="font-size: 24px; font-weight: 800; color: var(--text-main); margin-bottom: 8px;">Slip Gaji</span>
        <span style="font-size: 15px; font-weight: 600;">(Preview Dokumen PDF)</span>
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

<?php include 'templates/footer.php'; ?>
