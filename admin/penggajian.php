<?php
require '../config/config.php';
include 'templates/header.php';

$afdeling = isset($_GET['afdeling']) ? $_GET['afdeling'] : '';
?>

<style>
    /* Global Styles */
    .report-wrapper {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        padding: 30px;
        animation: fadeIn 0.4s ease-out;
        border: 1px solid #f1f5f9;
        min-height: 500px;
        display: flex;
        flex-direction: column;
    }

    .report-top-bar {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 40px;
    }

    .form-select {
        padding: 12px 20px;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        background-color: #f8fafc;
        font-size: 14px;
        color: #334155;
        font-family: inherit;
        outline: none;
        transition: all 0.2s;
        min-width: 200px;
        font-weight: 600;
    }

    .form-select:focus {
        border-color: #3b82f6;
        background-color: #ffffff;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }

    /* Buttons */
    .btn-action {
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.2s;
        border: none;
    }

    .btn-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        box-shadow: 0 4px 10px rgba(59, 130, 246, 0.25);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(59, 130, 246, 0.35);
    }

    /* Upload Zone (Tengah Besar) */
    .upload-container {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .upload-box {
        width: 100%;
        max-width: 600px;
        height: 250px;
        border: 3px dashed #cbd5e1;
        border-radius: 20px;
        background-color: #f8fafc;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .upload-box:hover {
        border-color: #3b82f6;
        background-color: #eff6ff;
        transform: scale(1.02);
    }

    .upload-box.dragover {
        border-color: #10b981;
        background-color: #ecfdf5;
        transform: scale(1.05);
    }

    .upload-icon {
        color: #94a3b8;
        margin-bottom: 16px;
        transition: color 0.3s;
    }

    .upload-box:hover .upload-icon {
        color: #3b82f6;
    }

    .upload-text {
        font-size: 24px;
        font-weight: 800;
        color: #334155;
        margin: 0;
        letter-spacing: -0.5px;
    }
    
    .upload-subtext {
        color: #64748b;
        font-size: 14px;
        margin-top: 8px;
    }

    #fileInput {
        position: absolute;
        width: 100%;
        height: 100%;
        top: 0; left: 0;
        opacity: 0;
        cursor: pointer;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Print styling */
    @media print {
        body * { visibility: hidden; }
        .main-content { margin: 0 !important; padding: 0 !important; }
        .report-wrapper, .report-wrapper * { visibility: visible; }
        .report-wrapper { position: absolute; left: 0; top: 0; width: 100%; box-shadow: none; border: none; padding: 0; }
        .btn-action, .top-header, .sidebar, .sidebar-overlay { display: none !important; }
        .form-select { border: none; appearance: none; background: transparent; font-weight: bold; padding: 0; }
        .upload-box { border: 2px solid #ccc; background: transparent; }
    }
</style>

<div class="report-wrapper">
    
    <!-- Bagian Atas Sesuai Sketsa 3 -->
    <div class="report-top-bar">
        
        <div class="filter-group-left">
            <select name="afdeling" class="form-select" onchange="window.location.href='penggajian.php?afdeling='+this.value">
                <option value="">Pilih Afdeling</option>
                <?php 
                $afd_query = mysqli_query($conn, "SELECT nama_afdeling FROM afdelings ORDER BY nama_afdeling ASC");
                while ($afd = mysqli_fetch_assoc($afd_query)): 
                ?>
                    <option value="<?= htmlspecialchars($afd['nama_afdeling']) ?>" <?= $afdeling == $afd['nama_afdeling'] ? 'selected' : '' ?>><?= htmlspecialchars($afd['nama_afdeling']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="filter-group-right">
            <button type="button" class="btn-action btn-primary" onclick="window.print()" style="white-space: nowrap; flex-shrink: 0;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                Cetak PDF
            </button>
        </div>

    </div>

    <!-- Kotak Upload (Tengah) -->
    <div class="upload-container">
        
        <div class="upload-box" id="dropZone">
            <svg class="upload-icon" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="17 8 12 3 7 8"></polyline>
                <line x1="12" y1="3" x2="12" y2="15"></line>
            </svg>
            
            <h2 class="upload-text">Upload slip gaji</h2>
            <p class="upload-subtext">Tarik file ke sini atau klik untuk memilih file (PDF/Excel)</p>
            
            <input type="file" id="fileInput" accept=".pdf, .xls, .xlsx, .csv">
        </div>

    </div>

</div>

<script>
    // Interaktivitas UI Upload (Visual Only)
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.classList.add('dragover');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.classList.remove('dragover');
        }, false);
    });

    dropZone.addEventListener('drop', (e) => {
        let dt = e.dataTransfer;
        let files = dt.files;
        handleFiles(files);
    }, false);

    fileInput.addEventListener('change', function() {
        handleFiles(this.files);
    });

    function handleFiles(files) {
        if (files.length > 0) {
            alert('File "' + files[0].name + '" siap untuk di-upload! (Ini adalah demo UI form penggajian)');
            // Di sini nanti bisa ditambahkan logika AJAX untuk upload sebenarnya
        }
    }
</script>

<?php include 'templates/footer.php'; ?>
