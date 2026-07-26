<?php
require '../config/config.php';
include 'templates/header.php';

$role_filter = isset($_GET['role']) ? $_GET['role'] : 'karyawan';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $status = $_POST['status'] == 'ya' ? 'disetujui' : 'ditolak';
    mysqli_query($conn, "UPDATE perizinan SET status='$status' WHERE id=$id");
    
    // --- INTEGRASI WHATSAPP CHATBOT (FONNTE API) ---
    $q_wa = mysqli_query($conn, "SELECT u.name, u.no_hp, p.jenis, p.tanggal_izin FROM perizinan p JOIN users u ON p.user_id = u.id WHERE p.id=$id");
    if($row_wa = mysqli_fetch_assoc($q_wa)) {
        if(!empty($row_wa['no_hp'])) {
            $pesan = "Halo *{$row_wa['name']}*,\n\nPengajuan *{$row_wa['jenis']}* Anda untuk tanggal *{$row_wa['tanggal_izin']}* telah *".strtoupper($status)."* oleh Kerani.\n\n_Ini adalah pesan otomatis dari Sistem Informasi Karyawan PT DJL._";
            // Send WA Request
            sendWA($row_wa['no_hp'], $pesan);
        }
    }
    
    swalRedirect('Status berhasil diperbarui & Notifikasi WA terkirim!', "validasi_izin.php?role=$role_filter", 'success');
}
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
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #cbd5e1;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
    }
    .btn-file:hover { background: #e2e8f0; }

    .action-group {
        display: flex;
        gap: 8px;
    }

    .btn-action {
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
    }

    .btn-ya { background: #dcfce7; color: #166534; }
    .btn-ya:hover { background: #bbf7d0; }
    
    .btn-tidak { background: #fee2e2; color: #b91c1c; }
    .btn-tidak:hover { background: #fecaca; }

    .badge-jenis {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        background: #f1f5f9;
        color: #475569;
    }
</style>

<div class="header-actions">
    <form method="GET" id="filterForm">
        <select name="role" class="filter-select" onchange="document.getElementById('filterForm').submit()">
            <option value="karyawan" <?= $role_filter == 'karyawan' ? 'selected' : '' ?>>Karyawan</option>
            <option value="mandor" <?= $role_filter == 'mandor' ? 'selected' : '' ?>>Mandor</option>
            <option value="pengawas" <?= $role_filter == 'pengawas' ? 'selected' : '' ?>>Pengawas</option>
        </select>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Validasi Surat Izin / Sakit / Cuti</h3>
    </div>
    
    <div class="table-container">
        <table class="table-data">
            <thead>
                <tr>
                    <th width="50">NO</th>
                    <th>NIK</th>
                    <th>NAMA</th>
                    <th>TANGGAL</th>
                    <th>JENIS PENGAJUAN</th>
                    <th width="250">ALASAN</th>
                    <th>BUKTI SURAT</th>
                    <th width="150">AKSI</th>
                </tr>
            </thead>
            <tbody>
            <tbody>
                <?php 
                $role_safe = mysqli_real_escape_string($conn, $role_filter);
                $query = mysqli_query($conn, "SELECT p.*, u.nik, u.name as user_name 
                                            FROM perizinan p 
                                            JOIN users u ON p.user_id = u.id 
                                            WHERE u.role = '$role_safe' 
                                            ORDER BY p.tanggal_pengajuan DESC");
                $no = 1;
                while($row = mysqli_fetch_assoc($query)): 
                ?>
                <tr>
                    <td style="color: var(--text-muted); font-weight: 600;"><?= $no++ ?></td>
                    <td style="font-weight: 700;"><?= htmlspecialchars($row['nik']) ?></td>
                    <td><?= htmlspecialchars($row['user_name']) ?></td>
                    <td><?= date('d M Y', strtotime($row['tanggal_izin'])) ?></td>
                    <td><span class="badge-jenis"><?= $row['jenis'] ?></span></td>
                    <td style="color: #64748b; font-size: 13px;"><?= htmlspecialchars($row['keterangan']) ?></td>
                    <td>
                        <?php if($row['bukti_file']): ?>
                            <a href="../uploads/izin/<?= $row['bukti_file'] ?>" target="_blank" class="btn-file">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                                Lihat File
                            </a>
                        <?php else: ?>
                            <span style="font-size: 12px; color: #94a3b8;">Tidak ada file</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if($row['status'] == 'pending'): ?>
                        <form method="POST" class="action-group">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <button type="submit" name="status" value="ya" class="btn-action btn-ya">Ya</button>
                            <button type="submit" name="status" value="tidak" class="btn-action btn-tidak">Tidak</button>
                        </form>
                        <?php else: ?>
                            <span style="font-size:12px; font-weight:bold; color: <?= $row['status']=='disetujui' ? '#166534' : '#b91c1c' ?>;">
                                <?= strtoupper($row['status']) ?>
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                
                <?php if(mysqli_num_rows($query) == 0): ?>
                <tr>
                    <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 20px;">Belum ada pengajuan untuk peran ini.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
