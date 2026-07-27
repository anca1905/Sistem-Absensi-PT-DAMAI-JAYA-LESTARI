<?php
require '../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'pengawas') {
    header("Location: ../index.php");
    exit;
}

// --- LOGIKA PHP (BACKEND) ---

// 1. Handle Tambah / Edit Data
if (isset($_POST['simpan_data'])) {
    $id = $_POST['id_karyawan']; // Hidden input
    $nik = mysqli_real_escape_string($conn, $_POST['nik']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $jabatan = mysqli_real_escape_string($conn, $_POST['jabatan']);

    // Password opsional (hanya diupdate jika diisi)
    $password_sql = "";
    // ...
    if (!empty($_POST['password'])) {
        // JANGAN: $password = $_POST['password'];
        // GANTI DENGAN INI (HASHING):
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $password_sql = ", password='$password'";
    }
    // ...

    if (empty($id)) {
        // --- LOGIKA CREATE (INSERT) ---
        // Cek NIK duplikat
        $cek = mysqli_query($conn, "SELECT id FROM users WHERE nik='$nik'");
        if (mysqli_num_rows($cek) > 0) {
            swalRedirect('NIK sudah terdaftar!', 'karyawan.php', 'error', 'NIK Duplikat!');
            exit;
        }

        $afdeling_pengawas = isset($_SESSION['afdeling']) ? mysqli_real_escape_string($conn, $_SESSION['afdeling']) : '';
        $query = "INSERT INTO users (nik, name, email, password, role, jabatan, afdeling) 
                  VALUES ('$nik', '$nama', '$email', '" . $_POST['password'] . "', 'karyawan', '$jabatan', '$afdeling_pengawas')";
    } else {
        // --- LOGIKA UPDATE ---
        $query = "UPDATE users SET 
                  nik='$nik', 
                  name='$nama', 
                  email='$email', 
                  jabatan='$jabatan' 
                  $password_sql
                  WHERE id='$id'";
    }

    if (mysqli_query($conn, $query)) {
        swalRedirect('Data berhasil disimpan!', 'karyawan.php', 'success');
    } else {
        swalAlert('Gagal menyimpan: ' . mysqli_error($conn), 'error');
    }
}

// 2. Handle Hapus Data
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM users WHERE id='$id'");
    swalRedirect('Data berhasil dihapus!', 'karyawan.php', 'success');
}

// 3. Ambil Data Karyawan
$afdeling_pengawas = isset($_SESSION['afdeling']) ? mysqli_real_escape_string($conn, $_SESSION['afdeling']) : '';
if (!empty($afdeling_pengawas)) {
    $data_karyawan = mysqli_query($conn, "SELECT * FROM users WHERE role='karyawan' AND afdeling='$afdeling_pengawas' ORDER BY id DESC");
} else {
    $data_karyawan = mysqli_query($conn, "SELECT * FROM users WHERE role='karyawan' ORDER BY id DESC");
}
include 'templates/header.php';
?>

<style>
    /* 1. Header Halaman & Tombol */
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

    .btn-primary {
        background-color: #4f46e5;
        color: white;
        box-shadow: 0 2px 5px rgba(79, 70, 229, 0.3);
    }

    .btn-primary:hover {
        background-color: #4338ca;
        transform: translateY(-1px);
    }

    .btn-danger {
        background-color: #ef4444;
        color: white;
    }

    .btn-danger:hover {
        background-color: #dc2626;
    }

    .btn-warning {
        background-color: #f59e0b;
        color: white;
    }

    .btn-warning:hover {
        background-color: #d97706;
    }

    /* 2. Styling Tabel Professional */
    .table-container {
        background: white;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th {
        background-color: #f8fafc;
        color: #64748b;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.5px;
        padding: 16px;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
    }

    td {
        padding: 16px;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
        font-size: 14px;
    }

    tr:last-child td {
        border-bottom: none;
    }

    tr:hover td {
        background-color: #f8fafc;
    }

    /* Badge NIK */
    .badge-nik {
        background-color: #e0e7ff;
        color: #4338ca;
        padding: 4px 8px;
        border-radius: 4px;
        font-family: monospace;
        font-weight: 600;
    }

    /* 3. Modal (Pop-up) Styling */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 999;
        display: none;
        /* Hidden by default */
        justify-content: center;
        align-items: center;
        backdrop-filter: blur(2px);
        opacity: 0;
        transition: opacity 0.3s;
    }

    .modal-overlay.show {
        display: flex;
        opacity: 1;
    }

    .modal-box {
        background: white;
        width: 100%;
        max-width: 500px;
        border-radius: 12px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        overflow: hidden;
        transform: scale(0.95);
        transition: transform 0.3s;
    }

    .modal-overlay.show .modal-box {
        transform: scale(1);
    }

    .modal-header {
        padding: 20px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: #f8fafc;
    }

    .modal-title {
        font-weight: 700;
        color: #1e293b;
        font-size: 16px;
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 24px;
        color: #94a3b8;
        cursor: pointer;
    }

    .modal-body {
        padding: 24px;
    }

    /* Form Group */
    .form-group {
        margin-bottom: 16px;
    }

    .form-label {
        display: block;
        margin-bottom: 6px;
        font-size: 13px;
        font-weight: 600;
        color: #475569;
    }

    .form-input {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 14px;
        transition: border 0.2s;
    }

    .form-input:focus {
        outline: none;
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }

    .modal-footer {
        padding: 16px 24px;
        background-color: #f8fafc;
        border-top: 1px solid #e2e8f0;
        text-align: right;
    }
</style>

<div>
    <div class="page-header">
        <div>
            <h1 class="page-title">Data Karyawan</h1>
            <p style="color: #64748b; font-size: 13px; margin-top: 4px;">Kelola data pegawai dan akses sistem.</p>
        </div>
        <button onclick="openModal('add')" class="btn btn-primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 5v14M5 12h14" />
            </svg>
            Tambah Karyawan
        </button>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="25%">Nama Pegawai</th>
                    <th width="20%">NIK</th>
                    <th width="20%">Jabatan</th>
                    <th width="30%" style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1;
                while ($row = mysqli_fetch_assoc($data_karyawan)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td>
                            <div style="font-weight: 600;"><?= $row['name'] ?></div>
                            <div style="font-size: 12px; color: #94a3b8;"><?= $row['email'] ?></div>
                        </td>
                        <td><span class="badge-nik"><?= $row['nik'] ?></span></td>
                        <td><?= $row['jabatan'] ?></td>
                        <td style="text-align: right;">
                            <a href="cetak_kartu.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-primary" style="padding: 6px 10px; font-size: 11px;">
                                ID Card
                            </a>

                            <button onclick='editData(<?= json_encode($row) ?>)' class="btn btn-warning" style="padding: 6px 10px; font-size: 11px;">
                                Edit
                            </button>

                            <a href="?hapus=<?= $row['id'] ?>" onclick="return confirm('Yakin hapus data ini?')" class="btn btn-danger" style="padding: 6px 10px; font-size: 11px;">
                                Hapus
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <?php if ($no == 1): ?>
            <div style="padding: 40px; text-align: center; color: #94a3b8;">
                Belum ada data karyawan.
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="modalForm" class="modal-overlay">
    <div class="modal-box">
        <form method="POST">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Tambah Karyawan</h3>
                <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
            </div>

            <div class="modal-body">
                <input type="hidden" name="id_karyawan" id="id_karyawan">

                <div class="form-group">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" name="nama" id="nama" class="form-input" required placeholder="Contoh: Budi Santoso">
                </div>

                <div class="row" style="display: flex; gap: 15px;">
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">NIK (Nomor Induk)</label>
                        <input type="text" name="nik" id="nik" class="form-input" required placeholder="Cth: 2024001">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">Jabatan</label>
                        <input type="text" name="jabatan" id="jabatan" class="form-input" required placeholder="Cth: Staff Gudang">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Email (Untuk Login)</label>
                    <input type="email" name="email" id="email" class="form-input" required placeholder="nama@kantor.com">
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-input" placeholder="Isi hanya jika ingin mengubah password">
                    <small style="color: #94a3b8; font-size: 11px;">Default untuk user baru disarankan: 123456</small>
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

</script>

<?php include 'templates/footer.php'; ?>