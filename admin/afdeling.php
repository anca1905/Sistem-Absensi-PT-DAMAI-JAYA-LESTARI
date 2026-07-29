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

<!-- Styles dari admin-components.css -->

<div>
    <div class="page-header">
        <div>
            <h1 class="page-title">Manajemen Afdeling</h1>
            <p class="page-subtitle">Kelola wilayah kerja atau divisi secara dinamis.</p>
        </div>
        <button onclick="openModal('add')" class="btn btn-primary">
            + Tambah Afdeling
        </button>
    </div>

    <div class="table-container">
        <table class="data-table">
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
                            <button onclick='editData(<?= json_encode($row) ?>)' class="btn btn-warning btn-sm">
                                Edit
                            </button>
                            <a href="?hapus=<?= $row['id'] ?>" onclick="return confirm('Yakin hapus afdeling ini?')" class="btn btn-danger btn-sm">
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
                    <input type="text" name="nama_afdeling" id="nama_afdeling" class="form-input form-input-full" required placeholder="Contoh: Afdeling 1">
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
