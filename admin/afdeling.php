<?php
require '../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'pimpinan')) {
    header("Location: ../index.php");
    exit;
}

// 1. Handle Tambah / Edit Data
if (isset($_POST['simpan_data'])) {
    $id = $_POST['id_afdeling']; 
    $nama_afdeling = mysqli_real_escape_string($conn, $_POST['nama_afdeling']);

    if (empty($id)) {
        // Cek duplikat
        $cek = mysqli_query($conn, "SELECT id FROM afdelings WHERE nama_afdeling='$nama_afdeling'");
        if (mysqli_num_rows($cek) > 0) {
            swalRedirect('Afdeling sudah ada!', 'afdeling.php', 'error', 'Duplikat!');
            exit;
        }

        $query = "INSERT INTO afdelings (nama_afdeling) VALUES ('$nama_afdeling')";
    } else {
        $query = "UPDATE afdelings SET nama_afdeling='$nama_afdeling' WHERE id='$id'";
    }

    if (mysqli_query($conn, $query)) {
        swalRedirect('Data berhasil disimpan!', 'afdeling.php', 'success');
    } else {
        swalAlert('Gagal menyimpan: ' . mysqli_error($conn), 'error');
    }
}

// 2. Handle Hapus Data
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    // Update users who have this afdeling
    $af_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama_afdeling FROM afdelings WHERE id='$id'"));
    if ($af_data) {
        $nama_af = $af_data['nama_afdeling'];
        mysqli_query($conn, "UPDATE users SET afdeling='' WHERE afdeling='$nama_af'");
    }
    
    mysqli_query($conn, "DELETE FROM afdelings WHERE id='$id'");
    swalRedirect('Data afdeling berhasil dihapus!', 'afdeling.php', 'success');
}

// 3. Ambil Data Afdeling
$data_afdeling = mysqli_query($conn, "SELECT * FROM afdelings ORDER BY nama_afdeling ASC");

include 'templates/header.php';
?>

<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }
    .page-title {
        font-size: 20px;
        font-weight: 700;
        color: #1e293b;
    }
    .btn {
        padding: 10px 20px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 13px;
        cursor: pointer;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        transition: all 0.2s;
    }
    .btn-primary { background-color: #4f46e5; color: white; box-shadow: 0 2px 5px rgba(79, 70, 229, 0.3); }
    .btn-primary:hover { background-color: #4338ca; transform: translateY(-1px); }
    .btn-danger { background-color: #ef4444; color: white; }
    .btn-danger:hover { background-color: #dc2626; }
    .btn-warning { background-color: #f59e0b; color: white; }
    .btn-warning:hover { background-color: #d97706; }
    .table-container {
        background: white; border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid #e2e8f0; overflow: hidden;
    }
    table { width: 100%; border-collapse: collapse; }
    th {
        background-color: #f8fafc; color: #64748b; font-weight: 600;
        text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;
        padding: 16px; text-align: left; border-bottom: 1px solid #e2e8f0;
    }
    td {
        padding: 16px; border-bottom: 1px solid #f1f5f9;
        color: #334155; font-size: 14px;
    }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background-color: #f8fafc; }
    
    .modal-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.5); z-index: 999; display: none;
        justify-content: center; align-items: center; backdrop-filter: blur(2px);
        opacity: 0; transition: opacity 0.3s;
    }
    .modal-overlay.show { display: flex; opacity: 1; }
    .modal-box {
        background: white; width: 100%; max-width: 400px; border-radius: 12px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); overflow: hidden;
        transform: scale(0.95); transition: transform 0.3s;
    }
    .modal-overlay.show .modal-box { transform: scale(1); }
    .modal-header {
        padding: 20px; border-bottom: 1px solid #e2e8f0; display: flex;
        justify-content: space-between; align-items: center; background-color: #f8fafc;
    }
    .modal-title { font-weight: 700; color: #1e293b; font-size: 16px; }
    .modal-close { background: none; border: none; font-size: 24px; color: #94a3b8; cursor: pointer; }
    .modal-body { padding: 24px; }
    .form-group { margin-bottom: 16px; }
    .form-label { display: block; margin-bottom: 6px; font-size: 13px; font-weight: 600; color: #475569; }
    .form-input {
        width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1;
        border-radius: 6px; font-size: 14px; transition: border 0.2s; box-sizing: border-box;
    }
    .form-input:focus { outline: none; border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
    .modal-footer { padding: 16px 24px; background-color: #f8fafc; border-top: 1px solid #e2e8f0; text-align: right; }
</style>

<div>
    <div class="page-header">
        <div>
            <h1 class="page-title">Manajemen Afdeling</h1>
            <p style="color: #64748b; font-size: 13px; margin-top: 4px;">Kelola wilayah kerja atau divisi secara dinamis.</p>
        </div>
        <button onclick="openModal('add')" class="btn btn-primary">
            + Tambah Afdeling
        </button>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th width="10%">No</th>
                    <th width="70%">Nama Afdeling</th>
                    <th width="20%" style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1;
                while ($row = mysqli_fetch_assoc($data_afdeling)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td>
                            <div style="font-weight: 600;"><?= $row['nama_afdeling'] ?></div>
                        </td>
                        <td style="text-align: right;">
                            <button onclick='editData(<?= json_encode($row) ?>)' class="btn btn-warning" style="padding: 6px 10px; font-size: 11px;">
                                Edit
                            </button>
                            <a href="?hapus=<?= $row['id'] ?>" onclick="return confirm('Yakin hapus afdeling ini?')" class="btn btn-danger" style="padding: 6px 10px; font-size: 11px;">
                                Hapus
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <?php if ($no == 1): ?>
            <div style="padding: 40px; text-align: center; color: #94a3b8;">
                Belum ada data afdeling.
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="modalForm" class="modal-overlay">
    <div class="modal-box">
        <form method="POST">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Tambah Afdeling</h3>
                <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
            </div>

            <div class="modal-body">
                <input type="hidden" name="id_afdeling" id="id_afdeling">

                <div class="form-group">
                    <label class="form-label">Nama Afdeling / Divisi</label>
                    <input type="text" name="nama_afdeling" id="nama_afdeling" class="form-input" required placeholder="Contoh: Afdeling 1">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" onclick="closeModal()" class="btn" style="background: #f1f5f9; color: #475569; margin-right: 10px;">Batal</button>
                <button type="submit" name="simpan_data" class="btn btn-primary">Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById('modalForm');

    function openModal(type) {
        modal.classList.add('show');
        if (type === 'add') {
            document.getElementById('modalTitle').innerText = 'Tambah Afdeling';
            document.getElementById('id_afdeling').value = '';
            document.getElementById('nama_afdeling').value = '';
        }
    }

    function closeModal() {
        modal.classList.remove('show');
    }

    function editData(data) {
        openModal('edit');
        document.getElementById('modalTitle').innerText = 'Edit Afdeling';
        document.getElementById('id_afdeling').value = data.id;
        document.getElementById('nama_afdeling').value = data.nama_afdeling;
    }
</script>

<?php include 'templates/footer.php'; ?>
