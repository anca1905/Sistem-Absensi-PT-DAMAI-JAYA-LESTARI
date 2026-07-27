<?php
require '../config/config.php';
include 'templates/header.php';

$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');
$pengawas_id = $_SESSION['user_id'];
$afdeling_pengawas = isset($_SESSION['afdeling']) ? mysqli_real_escape_string($conn, $_SESSION['afdeling']) : '';

// Ambil logbook kinerja dari DB berdasarkan afdeling pengawas
$tgl_safe = mysqli_real_escape_string($conn, $tanggal);
if (!empty($afdeling_pengawas)) {
    $query_logbook = mysqli_query($conn, "
        SELECT lk.*, u.nik, u.name as karyawan_name, m.name as mandor_name
        FROM logbook_kinerja lk
        JOIN users u ON lk.user_id = u.id
        LEFT JOIN users m ON lk.mandor_id = m.id
        WHERE lk.tanggal = '$tgl_safe' AND u.afdeling = '$afdeling_pengawas'
        ORDER BY u.name ASC
    ");
} else {
    $query_logbook = mysqli_query($conn, "
        SELECT lk.*, u.nik, u.name as karyawan_name, m.name as mandor_name
        FROM logbook_kinerja lk
        JOIN users u ON lk.user_id = u.id
        LEFT JOIN users m ON lk.mandor_id = m.id
        WHERE lk.tanggal = '$tgl_safe'
        ORDER BY u.name ASC
    ");
}
?>

<style>
    .card-container {
        background: #ffffff;
        border-radius: 16px;
        padding: 20px 16px;
        box-shadow: 0 4px 20px rgba(54, 72, 217, 0.05);
        border: 1px solid #f1f5f9;
        margin-bottom: 20px;
    }

    .form-input {
        width: 100%;
        padding: 12px 14px;
        border-radius: 10px;
        border: 1.5px solid #e2e8f0;
        background-color: #f8fafc;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-dark);
        font-family: inherit;
        outline: none;
        transition: all 0.2s;
    }
    
    .form-input:focus {
        border-color: var(--primary-start);
        box-shadow: 0 0 0 4px rgba(66, 88, 255, 0.1);
        background: white;
    }

    .btn-print {
        background: linear-gradient(135deg, var(--primary-start) 0%, var(--primary-end) 100%);
        color: white;
        border: none;
        padding: 12px 16px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 800;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
        box-shadow: 0 4px 15px rgba(66, 88, 255, 0.25);
    }
    .btn-print:active { transform: scale(0.95); box-shadow: none; }

    .table-responsive {
        width: 100%;
        overflow-x: auto;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        -webkit-overflow-scrolling: touch;
        background: white;
    }

    .table-kinerja {
        border-collapse: collapse;
        white-space: nowrap;
        font-size: 12px;
        min-width: 600px; 
    }

    .table-kinerja th, .table-kinerja td {
        padding: 12px 10px;
        border: 1px solid #e2e8f0;
        vertical-align: middle;
        text-align: center;
    }

    .table-kinerja th {
        background-color: var(--primary-light);
        color: var(--primary-end);
        font-weight: 800;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .table-kinerja td:nth-child(3) { text-align: left; font-weight: 700; color: var(--text-dark); }
    .table-kinerja tbody tr:nth-child(even) { background: #f8fafc; }

    .btn-file {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #f1f5f9;
        color: #3b82f6;
        border: 1px solid #cbd5e1;
        padding: 6px 12px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 11px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .btn-file:hover { background: #e0f2fe; border-color: #7dd3fc; }

    .btn-file:hover { background: #e0f2fe; border-color: #7dd3fc; }

    /* MODAL CSS */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(4px);
        z-index: 100;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .modal-content {
        background: white;
        border-radius: 16px;
        width: 100%;
        max-width: 480px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        animation: slideUp 0.3s ease;
    }

    .modal-header {
        padding: 16px 20px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-title {
        font-size: 16px;
        font-weight: 800;
        color: var(--text-dark);
        margin: 0;
    }

    .btn-close {
        background: #f1f5f9;
        border: none;
        width: 32px; height: 32px;
        border-radius: 50%;
        color: #64748b;
        font-size: 18px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-body {
        padding: 20px;
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
</style>

<div class="animate-up">
    <a href="index.php" class="btn-back">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
        Kembali
    </a>

    <h2 class="page-title" style="text-align: left; margin-bottom: 16px; font-size: 20px;">Laporan Kinerja Karyawan</h2>

    <div class="card-container">
        
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px; gap: 10px;">
            <form id="filterForm" method="GET" style="flex: 1;">
                <label style="font-size: 11px; font-weight: 700; color: #64748b; margin-bottom:4px; display:block;">Pilih Tanggal</label>
                <input type="date" name="tanggal" class="form-input" value="<?= $tanggal ?>" onchange="document.getElementById('filterForm').submit()">
            </form>
            <button class="btn-print" onclick="window.print()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                Cetak PDF
            </button>
        </div>

        <div class="table-responsive">
            <table class="table-kinerja">
                <thead>
                    <tr>
                        <th style="width:30px;">NO</th>
                        <th>NIK</th>
                        <th>NAMA</th>
                        <th>OBJEK KERJA</th>
                        <th>BLOK</th>
                        <th>JAM KERJA</th>
                        <th>STATUS</th>
                        <th>AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    if($query_logbook && mysqli_num_rows($query_logbook) > 0):
                        while($row = mysqli_fetch_assoc($query_logbook)): 
                            $status_label = ucfirst($row['status'] ?? 'ditinjau');
                            $status_color = '#854d0e';
                            if($row['status'] == 'diterima') $status_color = '#166534';
                            if($row['status'] == 'ditolak') $status_color = '#991b1b';
                    ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['nik']) ?></td>
                            <td><?= htmlspecialchars($row['karyawan_name']) ?></td>
                            <td><?= htmlspecialchars($row['objek_kerja'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['blok'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['jumlah_jam_kerja'] ?? '-') ?></td>
                            <td><span style="font-weight:700; color:<?= $status_color ?>"><?= $status_label ?></span></td>
                            <td>
                                <?php 
                                    $dataJSON = htmlspecialchars(json_encode([
                                        'kategori' => $row['kategori_task'] ?? 'perawatan',
                                        'objek' => $row['objek_kerja'] ?? '-',
                                        'blok' => $row['blok'] ?? '-',
                                        'luas' => $row['luas_ha'] ?? '-',
                                        'mandor' => $row['mandor_name'] ?? '-',
                                        'h_ton' => $row['hasil_ton'] ?? '0',
                                        'h_kg' => $row['hasil_kg'] ?? '0',
                                        'p_ton' => $row['prestasi_ton'] ?? '0',
                                        'p_kg' => $row['prestasi_kg'] ?? '0',
                                        'tbs' => $row['tbs'] ?? '0',
                                        'kosong' => $row['tandan_kosong'] ?? '0',
                                        'brondol' => $row['tandan_brondol'] ?? '0',
                                        'total' => $row['total_tandan'] ?? '0',
                                        'langsir_kg' => $row['hasil_langsir_kg'] ?? '0',
                                        'jam' => $row['jumlah_jam_kerja'] ?? '0',
                                        'aksi' => ucfirst($row['aksi'] ?? 'Belum'),
                                        'status' => ucfirst($row['status'] ?? 'ditinjau'),
                                        'status_color' => $status_color
                                    ]), ENT_QUOTES, 'UTF-8');
                                ?>
                                <button type="button" class="btn-file" onclick="openModal('<?= date('d M Y', strtotime($tanggal)) ?> - <?= htmlspecialchars($row['objek_kerja'] ?? '') ?>', <?= $dataJSON ?>)">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                    Detail
                                </button>
                            </td>
                        </tr>
                    <?php 
                        endwhile;
                    else: 
                    ?>
                        <tr>
                            <td colspan="8" style="text-align:center; padding:30px; color:var(--text-muted);">Belum ada laporan kinerja untuk tanggal ini.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <p style="font-size: 11px; color: var(--text-muted); font-style: italic; margin-top: 8px;">* Geser tabel jika layar terlalu kecil.</p>
    </div>
</div>

<!-- MODAL POPUP -->
<div class="modal-overlay" id="fileModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">Laporan File</h3>
            <button class="btn-close" onclick="closeModal()">×</button>
        </div>
        <div class="modal-body">
            
            <p style="font-size: 11px; color: var(--text-muted); font-style: italic; margin-bottom: 8px;">* Geser tabel ke kanan untuk melihat lengkap.</p>
            
            <div class="table-responsive">
                <table class="table-kinerja" style="min-width:600px;">
                    <thead id="modalThead">
                    </thead>
                    <tbody id="modalTbody">
                    </tbody>
                </table>
            </div>
            
            <p style="font-size: 11px; color: var(--text-muted); font-style: italic; margin-top: 16px; text-align: center;">File objek kerja lainnya lihat di karyawan.</p>
        </div>
    </div>
</div>

<script>
    function openModal(titleInfo, data) {
        document.getElementById('modalTitle').innerText = titleInfo;
        
        let thead = '';
        let tbody = '';
        let cat = data.kategori;
        let obj = data.objek.toLowerCase();

        if (cat === 'langsir' || obj.includes('membabat')) {
            thead = `<tr><th rowspan="2">Blok</th><th rowspan="2">Luas Ha</th><th rowspan="2">Mandor</th><th colspan="2">Hasil</th><th colspan="2">Prestasi</th><th rowspan="2">Aksi</th><th rowspan="2">Status</th></tr><tr><th>Ton</th><th>Kg</th><th>Ton</th><th>Kg</th></tr>`;
            tbody = `<tr><td>${data.blok}</td><td>${data.luas}</td><td>${data.mandor}</td><td>${data.h_ton}</td><td>${data.h_kg}</td><td>${data.p_ton}</td><td>${data.p_kg}</td><td>${data.aksi}</td><td><span style="color:${data.status_color}; font-weight:bold;">${data.status}</span></td></tr>`;
        } else if (cat === 'potong_buah') {
            thead = `<tr><th rowspan="2">Blok</th><th rowspan="2">Luas Ha</th><th rowspan="2">Mandor</th><th colspan="4">Jumlah Janjangan</th><th rowspan="2">Aksi</th><th rowspan="2">Status</th></tr><tr><th>TBS</th><th>Kosong</th><th>Brondol</th><th>Total</th></tr>`;
            tbody = `<tr><td>${data.blok}</td><td>${data.luas}</td><td>${data.mandor}</td><td>${data.tbs}</td><td>${data.kosong}</td><td>${data.brondol}</td><td>${data.total}</td><td>${data.aksi}</td><td><span style="color:${data.status_color}; font-weight:bold;">${data.status}</span></td></tr>`;
        } else if (cat === 'muat_tbs') {
            thead = `<tr><th>Blok</th><th>Luas Ha</th><th>Mandor</th><th>Hasil Langsir (Kg)</th><th>Jam Kerja</th><th>Aksi</th><th>Status</th></tr>`;
            tbody = `<tr><td>${data.blok}</td><td>${data.luas}</td><td>${data.mandor}</td><td>${data.langsir_kg}</td><td>${data.jam}</td><td>${data.aksi}</td><td><span style="color:${data.status_color}; font-weight:bold;">${data.status}</span></td></tr>`;
        } else if (cat === 'jaga') {
            thead = `<tr><th>Blok</th><th>Luas Ha / Mandor</th><th>Jam Kerja</th><th>Aksi</th><th>Status</th></tr>`;
            tbody = `<tr><td>${data.blok}</td><td>${data.luas} / ${data.mandor}</td><td>${data.jam}</td><td>${data.aksi}</td><td><span style="color:${data.status_color}; font-weight:bold;">${data.status}</span></td></tr>`;
        } else {
            thead = `<tr><th>Blok</th><th>Luas Ha</th><th>Mandor</th><th>Aksi</th><th>Status</th></tr>`;
            tbody = `<tr><td>${data.blok}</td><td>${data.luas}</td><td>${data.mandor}</td><td>${data.aksi}</td><td><span style="color:${data.status_color}; font-weight:bold;">${data.status}</span></td></tr>`;
        }

        document.getElementById('modalThead').innerHTML = thead;
        document.getElementById('modalTbody').innerHTML = tbody;
        document.getElementById('fileModal').classList.add('active');
    }

    function closeModal() {
        document.getElementById('fileModal').classList.remove('active');
    }

    // Close when clicking outside
    window.onclick = function(event) {
        var modal = document.getElementById('fileModal');
        if (event.target == modal) {
            closeModal();
        }
    }
</script>

<?php include 'templates/footer.php'; ?>
