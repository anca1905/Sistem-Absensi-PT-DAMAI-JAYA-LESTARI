<?php
require '../../config/config.php';
include '../templates/header.php';

// Filter parameter
$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');

// Array nama bulan
$nama_bulan = array(
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
    '04' => 'April', '05' => 'Mei', '06' => 'Juni',
    '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
    '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
);
$tgl_pecah = explode('-', $tanggal);
$format_tanggal = $tgl_pecah[2] . ' ' . $nama_bulan[$tgl_pecah[1]] . ' ' . $tgl_pecah[0];

// Ambil data users (karyawan) beserta kinerja harinya
$tgl_safe = mysqli_real_escape_string($conn, $tanggal);
$query_users = mysqli_query($conn, "
    SELECT u.id, u.nik, u.name, u.jabatan, lk.status as lk_status, lk.blok, lk.luas_ha, lk.objek_kerja, lk.hasil_ton, lk.hasil_kg, lk.prestasi_ton, lk.prestasi_kg, m.name as mandor_name
    FROM users u 
    LEFT JOIN logbook_kinerja lk ON u.id = lk.user_id AND lk.tanggal = '$tgl_safe'
    LEFT JOIN users m ON lk.mandor_id = m.id
    WHERE u.role='karyawan' 
    ORDER BY u.name ASC
");
?>

<style>
    /* Global Report Styles (Sama dengan Laporan Absensi) */
    .report-wrapper {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        padding: 24px;
        animation: fadeIn 0.4s ease-out;
        border: 1px solid #f1f5f9;
    }

    .report-top-bar {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 24px;
        gap: 20px;
    }

    .filter-group-left {
        display: flex;
        align-items: center;
        gap: 12px;
        flex: 1;
        min-width: 250px;
    }

    .filter-group-right {
        display: flex;
        align-items: flex-end;
        justify-content: flex-end;
        gap: 12px;
        flex: 1;
    }

    .report-title-center {
        text-align: center;
        flex: 2;
        min-width: 300px;
    }

    .report-title-center h2 {
        font-size: 24px;
        font-weight: 800;
        color: #1e293b;
        margin-bottom: 4px;
        letter-spacing: -0.5px;
    }

    .report-title-center p {
        color: #64748b;
        font-size: 14px;
        margin: 0;
    }

    /* Inputs & Selects */
    .form-input {
        padding: 10px 16px;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        background-color: #f8fafc;
        font-size: 14px;
        color: #334155;
        font-family: inherit;
        outline: none;
        transition: all 0.2s;
    }

    .form-input:focus {
        border-color: #3b82f6;
        background-color: #ffffff;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }

    /* Buttons */
    .btn-action {
        padding: 10px 20px;
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
        height: 40px;
    }

    .btn-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        box-shadow: 0 4px 10px rgba(59, 130, 246, 0.25);
    }

    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 15px rgba(59, 130, 246, 0.35);
    }

    /* File Button / Link Style */
    .btn-file {
        background: #f1f5f9;
        color: #3b82f6;
        border: 1px dashed #94a3b8;
        padding: 6px 16px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-file:hover {
        background: #e0f2fe;
        border-color: #3b82f6;
        color: #2563eb;
    }

    /* Table Styles */
    .table-responsive {
        overflow-x: auto;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
    }

    .table-absen {
        width: 100%;
        border-collapse: collapse;
        white-space: nowrap;
        font-size: 14px;
    }

    .table-absen th, .table-absen td {
        padding: 14px 16px;
        border: 1px solid #e2e8f0;
        text-align: center;
    }

    .table-absen th {
        background-color: #f8fafc;
        color: #475569;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 13px;
        letter-spacing: 0.5px;
    }

    .table-absen tbody tr:hover {
        background-color: #f1f5f9;
    }

    .text-left { text-align: left !important; }

    /* Modal Styles */
    .modal-overlay {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(4px);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }

    .modal-overlay.active {
        opacity: 1;
        visibility: visible;
    }

    .modal-content {
        background: #fff;
        border-radius: 16px;
        width: 90%;
        max-width: 900px;
        max-height: 90vh;
        overflow-y: auto;
        padding: 24px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        transform: scale(0.95);
        transition: transform 0.3s ease;
    }

    .modal-overlay.active .modal-content {
        transform: scale(1);
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .modal-header-info h3 {
        margin: 0; font-size: 20px; color: #0f172a; font-weight: 800;
    }
    .modal-header-info p {
        margin: 4px 0 0 0; color: #64748b; font-size: 14px;
    }

    .btn-close {
        background: #f1f5f9;
        border: none;
        width: 36px; height: 36px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
        color: #64748b;
        transition: background 0.2s;
    }
    .btn-close:hover { background: #e2e8f0; color: #0f172a; }

    /* Badge */
    .badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
    }
    .badge-success { background: #dcfce7; color: #166534; }
    .badge-warning { background: #fef9c3; color: #854d0e; }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Print styles */
    @media print {
        body * { visibility: hidden; }
        .main-content { margin: 0 !important; padding: 0 !important; }
        .report-wrapper, .report-wrapper * { visibility: visible; }
        .report-wrapper { position: absolute; left: 0; top: 0; width: 100%; box-shadow: none; border: none; padding: 0; }
        .btn-action, .top-header, .sidebar, .sidebar-overlay, .btn-file { display: none !important; }
        .form-input { border: none; appearance: none; font-weight: bold; padding: 0; }
        
        /* Jika modal print */
        .modal-overlay.active, .modal-overlay.active * { visibility: visible; }
        .modal-overlay.active { position: absolute; left: 0; top: 0; width: 100%; background: none; }
        .modal-content { box-shadow: none; max-width: 100%; width: 100%; padding: 0; transform: none !important; }
        .btn-close { display: none !important; }
    }
</style>

<div class="report-wrapper">
    <!-- Top Action Bar -->
    <div class="report-top-bar">
        
        <div class="filter-group-left">
            <span style="font-weight: 600; font-size: 14px; color: #475569;">Pilih Periode:</span>
            <input type="date" class="form-input" value="<?= $tanggal ?>" onchange="window.location.href='kinerja.php?tanggal='+this.value">
        </div>

        <div class="report-title-center">
            <h2>Laporan Kinerja Hari Ini</h2>
            <p>Tanggal: <strong><?= $format_tanggal ?></strong></p>
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

    <!-- Tabel Utama -->
    <div class="table-responsive">
        <table class="table-absen">
            <thead>
                <tr>
                    <th style="width: 50px;">NO</th>
                    <th class="text-left" style="width: 150px;">NIK</th>
                    <th class="text-left">NAMA</th>
                    <th>LAPORAN</th>
                    <th style="width: 150px;">STATUS</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                if(mysqli_num_rows($query_users) > 0):
                    while($user = mysqli_fetch_assoc($query_users)): 
                        $status = $user['lk_status'] ? ucfirst($user['lk_status']) : 'Belum';
                        $status_badge = 'badge-warning';
                        if (strtolower($status) == 'diterima' || strtolower($status) == 'selesai') $status_badge = 'badge-success';
                        
                        $dataJSON = htmlspecialchars(json_encode([
                            'kategori' => $user['kategori_task'] ?? 'perawatan',
                            'objek' => $user['objek_kerja'] ?? '-',
                            'blok' => $user['blok'] ?? '-',
                            'luas' => $user['luas_ha'] ?? '-',
                            'mandor' => $user['mandor_name'] ?? '-',
                            'h_ton' => $user['hasil_ton'] ?? '0',
                            'h_kg' => $user['hasil_kg'] ?? '0',
                            'p_ton' => $user['prestasi_ton'] ?? '0',
                            'p_kg' => $user['prestasi_kg'] ?? '0',
                            'tbs' => $user['tbs'] ?? '0',
                            'kosong' => $user['tandan_kosong'] ?? '0',
                            'brondol' => $user['tandan_brondol'] ?? '0',
                            'total' => $user['total_tandan'] ?? '0',
                            'langsir_kg' => $user['hasil_langsir_kg'] ?? '0',
                            'jam' => $user['jumlah_jam_kerja'] ?? '0',
                            'aksi' => ucfirst($user['aksi'] ?? 'Belum'),
                            'status' => $status,
                            'status_badge' => $status_badge
                        ]), ENT_QUOTES, 'UTF-8');
                ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td class="text-left"><?= htmlspecialchars($user['nik']) ?></td>
                        <td class="text-left" style="font-weight: 600;"><?= htmlspecialchars($user['name']) ?></td>
                        <td>
                            <?php if ($user['lk_status']): ?>
                            <button onclick="openReportModal('<?= htmlspecialchars((string)$user['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars((string)$user['jabatan'], ENT_QUOTES) ?>', <?= $dataJSON ?>)" class="btn-file">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                    <polyline points="10 9 9 9 8 9"></polyline>
                                </svg>
                                File
                            </button>
                            <?php else: ?>
                            <span style="color:#94a3b8; font-size:12px; font-style:italic;">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?= $status_badge ?>">
                                <?= $status ?>
                            </span>
                        </td>
                    </tr>
                <?php 
                    endwhile;
                else: 
                ?>
                    <tr>
                        <td colspan="5" style="padding: 30px; color: #64748b;">Tidak ada data.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL: Isi File Detail Pekerjaan -->
<div id="fileModal" class="modal-overlay">
    <div class="modal-content">
        
        <div class="modal-header">
            <div class="modal-header-info">
                <h3 id="modalTanggal"><?= $format_tanggal ?></h3>
                <p id="modalInfoSub">Isi di dalam file / <span style="font-style: italic;">misal objek kerjanya langsir manual</span></p>
            </div>
            
            <div style="display: flex; gap: 12px; align-items: center;">
                <button type="button" class="btn-action btn-primary" onclick="window.print()" style="white-space: nowrap; flex-shrink: 0;">Cetak PDF</button>
                <button class="btn-close" onclick="closeReportModal()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
        </div>

        <div class="table-responsive" style="margin-bottom: 12px;">
            <table class="table-absen">
                <thead id="modalThead">
                </thead>
                <tbody id="modalTableBody">
                </tbody>
            </table>
        </div>

        <p style="font-size: 13px; color: #64748b; font-style: italic; margin-top: 20px;">
            * Bentuk file bisa berubah-ubah sesuai objek kerja.
        </p>

    </div>
</div>

<script>
    // Menyimpan tanggal saat ini di JS untuk ditampilkan di modal
    const currentDate = "<?= $format_tanggal ?>";

    function openReportModal(name, role, data) {
        document.getElementById('modalTanggal').innerText = currentDate + ' - ' + name;
        document.getElementById('modalInfoSub').innerText = 'Objek Kerja: ' + data.objek;
        
        let thead = '';
        let tbody = '';
        let cat = data.kategori;
        let obj = data.objek.toLowerCase();

        if (cat === 'langsir' || obj.includes('membabat') || obj.includes('langsir')) {
            thead = `<tr><th rowspan="2">Blok</th><th rowspan="2">Luas Ha</th><th rowspan="2">Mandor</th><th colspan="2">Hasil</th><th colspan="2">Prestasi</th><th rowspan="2">Aksi</th><th rowspan="2">Status</th></tr><tr><th>Ton</th><th>Kg</th><th>Ton</th><th>Kg</th></tr>`;
            tbody = `<tr><td>${data.blok}</td><td>${data.luas}</td><td>${data.mandor}</td><td>${data.h_ton}</td><td>${data.h_kg}</td><td>${data.p_ton}</td><td>${data.p_kg}</td><td>${data.aksi}</td><td><span class="badge ${data.status_badge}">${data.status}</span></td></tr>`;
        } else if (cat === 'potong_buah' || obj.includes('potong') || obj.includes('panen')) {
            thead = `<tr><th rowspan="2">Blok</th><th rowspan="2">Luas Ha</th><th rowspan="2">Mandor</th><th colspan="4">Jumlah Janjangan</th><th rowspan="2">Aksi</th><th rowspan="2">Status</th></tr><tr><th>TBS</th><th>Kosong</th><th>Brondol</th><th>Total</th></tr>`;
            tbody = `<tr><td>${data.blok}</td><td>${data.luas}</td><td>${data.mandor}</td><td>${data.tbs}</td><td>${data.kosong}</td><td>${data.brondol}</td><td>${data.total}</td><td>${data.aksi}</td><td><span class="badge ${data.status_badge}">${data.status}</span></td></tr>`;
        } else if (cat === 'muat_tbs' || obj.includes('muat')) {
            thead = `<tr><th>Blok</th><th>Luas Ha</th><th>Mandor</th><th>Hasil Langsir (Kg)</th><th>Jam Kerja</th><th>Aksi</th><th>Status</th></tr>`;
            tbody = `<tr><td>${data.blok}</td><td>${data.luas}</td><td>${data.mandor}</td><td>${data.langsir_kg}</td><td>${data.jam}</td><td>${data.aksi}</td><td><span class="badge ${data.status_badge}">${data.status}</span></td></tr>`;
        } else if (cat === 'jaga' || obj.includes('jaga')) {
            thead = `<tr><th>Blok</th><th>Luas Ha / Mandor</th><th>Jam Kerja</th><th>Aksi</th><th>Status</th></tr>`;
            tbody = `<tr><td>${data.blok}</td><td>${data.luas} / ${data.mandor}</td><td>${data.jam}</td><td>${data.aksi}</td><td><span class="badge ${data.status_badge}">${data.status}</span></td></tr>`;
        } else {
            thead = `<tr><th>Blok</th><th>Luas Ha</th><th>Mandor</th><th>Aksi</th><th>Status</th></tr>`;
            tbody = `<tr><td>${data.blok}</td><td>${data.luas}</td><td>${data.mandor}</td><td>${data.aksi}</td><td><span class="badge ${data.status_badge}">${data.status}</span></td></tr>`;
        }

        document.getElementById('modalThead').innerHTML = thead;
        document.getElementById('modalTableBody').innerHTML = tbody;
        document.getElementById('fileModal').classList.add('active');
    }

    function closeReportModal() {
        document.getElementById('fileModal').classList.remove('active');
    }

    // Tutup modal jika klik di luar area konten
    document.getElementById('fileModal').addEventListener('click', function(e) {
        if(e.target === this) {
            closeReportModal();
        }
    });
</script>

<?php include '../templates/footer.php'; ?>
