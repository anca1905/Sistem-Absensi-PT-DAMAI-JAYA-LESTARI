<?php
require '../config/config.php';
include 'templates/header.php';

$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');
?>

<style>
    .header-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }

    .filter-select {
        padding: 10px 16px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        color: var(--text-main);
        background: white;
        outline: none;
        cursor: pointer;
    }
    .filter-select:focus { border-color: var(--accent); }

    .btn-print {
        background: white;
        color: var(--text-main);
        border: 1px solid #cbd5e1;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }
    .btn-print:hover { background: #f8fafc; border-color: #94a3b8; }

    .card {
        background: white;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        overflow: hidden;
    }

    .card-header {
        padding: 20px 24px;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
        text-align: center;
    }
    
    .card-title {
        font-size: 18px;
        font-weight: 700;
        color: var(--text-main);
        margin: 0;
    }

    .table-container {
        width: 100%;
        overflow-x: auto;
    }

    .table-data {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }

    .table-data th, .table-data td {
        padding: 16px 20px;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
    }

    .table-data th {
        background: white;
        color: var(--text-muted);
        font-weight: 700;
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.5px;
    }

    .table-data tbody tr:hover {
        background: #f8fafc;
    }

    .btn-file {
        background: #eff6ff;
        color: #3b82f6;
        border: 1px solid #bfdbfe;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
    }
    .btn-file:hover { background: #dbeafe; }

    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        background: #dcfce7;
        color: #166534;
    }
    
    @media print {
        body * { visibility: hidden; }
        .main-content { margin-left: 0; }
        .sidebar, .topbar, .header-actions { display: none !important; }
        .card, .card * { visibility: visible; }
        .card { position: absolute; left: 0; top: 0; border: none; box-shadow: none; width: 100%; }
        .btn-file { border: none; background: transparent; padding: 0; color: #0f172a; }
    }
</style>

<div class="header-actions">
    <form method="GET" id="filterForm">
        <input type="date" name="tanggal" class="filter-select" value="<?= $tanggal ?>" onchange="document.getElementById('filterForm').submit()">
    </form>
    
    <button class="btn-print" onclick="window.print()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
        Cetak PDF
    </button>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Laporan Kinerja Hari Ini (<?= date('d M Y', strtotime($tanggal)) ?>)</h3>
    </div>
    
    <div class="table-container">
        <table class="table-data">
            <thead>
                <tr>
                    <th width="50">NO</th>
                    <th>NIK</th>
                    <th>NAMA KARYAWAN</th>
                    <th>LAPORAN (Logbook)</th>
                    <th>STATUS</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $tanggal_safe = mysqli_real_escape_string($conn, $tanggal);
                $query = mysqli_query($conn, "SELECT l.user_id, l.status, u.nik, u.name 
                                            FROM logbook_kinerja l 
                                            JOIN users u ON l.user_id = u.id 
                                            WHERE l.tanggal = '$tanggal_safe' 
                                            GROUP BY l.user_id, l.status, u.nik, u.name");
                $no = 1;
                while($row = mysqli_fetch_assoc($query)): 
                ?>
                <tr>
                    <td style="color: var(--text-muted); font-weight: 600;"><?= $no++ ?></td>
                    <td style="font-weight: 700;"><?= htmlspecialchars($row['nik']) ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td>
                        <a href="laporan_kinerja_detail.php?user_id=<?= $row['user_id'] ?>&tanggal=<?= $tanggal ?>" class="btn-file">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                            Buka File
                        </a>
                    </td>
                    <td>
                        <span class="status-badge" style="
                            background: <?= $row['status'] == 'diterima' ? '#dcfce7' : ($row['status'] == 'ditolak' ? '#fee2e2' : '#fef3c7') ?>;
                            color: <?= $row['status'] == 'diterima' ? '#166534' : ($row['status'] == 'ditolak' ? '#b91c1c' : '#d97706') ?>;
                        ">
                            <?= strtoupper($row['status']) ?>
                        </span>
                    </td>
                </tr>
                <?php endwhile; ?>
                
                <?php if(mysqli_num_rows($query) == 0): ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 20px;">Belum ada laporan kinerja pada tanggal ini.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
