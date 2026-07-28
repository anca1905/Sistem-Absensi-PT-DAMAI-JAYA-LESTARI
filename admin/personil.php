<?php
require '../config/config.php';

// --- LOGIKA PHP (BACKEND) ---

// 1. Handle Tambah / Edit Data
if (isset($_POST['simpan_data'])) {
    $id = $_POST['id_karyawan']; // Hidden input
    $nik = mysqli_real_escape_string($conn, $_POST['nik']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $no_hp = isset($_POST['no_hp']) ? mysqli_real_escape_string($conn, $_POST['no_hp']) : '';
    $afdeling = mysqli_real_escape_string($conn, $_POST['afdeling']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);

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
        $cek = mysqli_query($conn, "SELECT id FROM users WHERE nik='$nik'");
        if (mysqli_num_rows($cek) > 0) {
            swalRedirect('NIK sudah terdaftar!', 'personil.php', 'error', 'NIK Duplikat!');
            exit;
        }

        $query = "INSERT INTO users (nik, name, email, no_hp, password, role, afdeling) 
                  VALUES ('$nik', '$nama', '$email', '$no_hp', '" . $_POST['password'] . "', '$role', '$afdeling')";
    } else {
        // --- LOGIKA UPDATE ---
        $query = "UPDATE users SET 
                  nik='$nik', 
                  name='$nama', 
                  email='$email', 
                  no_hp='$no_hp',
                  afdeling='$afdeling',
                  role='$role'
                  $password_sql
                  WHERE id='$id'";
    }

    if (mysqli_query($conn, $query)) {
        swalRedirect('Data berhasil disimpan!', 'personil.php', 'success');
    } else {
        swalAlert('Gagal menyimpan: ' . mysqli_error($conn), 'error');
    }
}

// 2. Handle Hapus Data
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM users WHERE id='$id'");
    swalRedirect('Data personil berhasil dihapus!', 'personil.php', 'success');
}

// 3. Ambil Data Personil
$where_conditions = ["role != 'admin'"];
if (isset($_GET['role_filter']) && $_GET['role_filter'] != '') {
    $filter_role = mysqli_real_escape_string($conn, $_GET['role_filter']);
    $where_conditions[] = "(jabatan LIKE '%$filter_role%' OR role = '$filter_role')";
}
if (isset($_GET['afdeling_filter']) && $_GET['afdeling_filter'] != '') {
    $filter_afdeling = mysqli_real_escape_string($conn, $_GET['afdeling_filter']);
    $where_conditions[] = "afdeling = '$filter_afdeling'";
}
$where_clause = "WHERE " . implode(' AND ', $where_conditions);
$data_karyawan = mysqli_query($conn, "SELECT * FROM users $where_clause ORDER BY id DESC");

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

    /* Print Styles */
    @media print {
        body {
            background-color: white;
            padding: 0;
            margin: 0;
        }

        /* Hide sidebar/header if they exist in templates */
        .sidebar,
        .navbar,
        .topbar {
            display: none !important;
        }

        /* Hide action buttons and filters */
        .page-header button,
        .btn,
        form,
        .aksi-column {
            display: none !important;
        }

        .page-header {
            margin-bottom: 20px;
        }

        .table-container {
            box-shadow: none;
            border: none;
        }

        table {
            width: 100%;
            border: 1px solid #000;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 8px;
            color: #000;
        }

        th {
            background-color: #f0f0f0;
        }

        /* Ensure table header prints on new pages */
        thead {
            display: table-header-group;
        }
    }
</style>

<div>
    <div class="page-header">
        <div>
            <h1 class="page-title">Data Personil</h1>
            <p style="color: #64748b; font-size: 13px; margin-top: 4px;">Kelola data pegawai dan akses sistem.</p>
        </div>
        <button onclick="openModal('add')" class="btn btn-primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 5v14M5 12h14" />
            </svg>
            Tambah Personil
        </button>
    </div>

    <!-- Filter & Aksi -->
    <div class="print-hide" style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
        <form method="GET" style="display: flex; gap: 10px; align-items: center;">
            <select name="afdeling_filter" class="form-input" style="width: auto; padding: 8px 12px;">
                <option value="">Semua Afdeling</option>
                <?php 
                $afd_query1 = mysqli_query($conn, "SELECT nama_afdeling FROM afdelings ORDER BY nama_afdeling ASC");
                while ($afd = mysqli_fetch_assoc($afd_query1)): 
                ?>
                    <option value="<?= htmlspecialchars($afd['nama_afdeling']) ?>" <?= (isset($_GET['afdeling_filter']) && $_GET['afdeling_filter'] == $afd['nama_afdeling']) ? 'selected' : '' ?>><?= htmlspecialchars($afd['nama_afdeling']) ?></option>
                <?php endwhile; ?>
            </select>
            <select name="role_filter" class="form-input" style="width: auto; padding: 8px 12px;">
                <option value="">Semua Jabatan</option>
                <option value="mandor" <?= (isset($_GET['role_filter']) && $_GET['role_filter'] == 'mandor') ? 'selected' : '' ?>>Mandor</option>
                <option value="karyawan" <?= (isset($_GET['role_filter']) && $_GET['role_filter'] == 'karyawan') ? 'selected' : '' ?>>Karyawan</option>
                <option value="pengawas" <?= (isset($_GET['role_filter']) && $_GET['role_filter'] == 'pengawas') ? 'selected' : '' ?>>Pengawas</option>
                <option value="kerani" <?= (isset($_GET['role_filter']) && $_GET['role_filter'] == 'kerani') ? 'selected' : '' ?>>Kerani</option>
            </select>
            <button type="submit" class="btn btn-primary" style="padding: 8px 15px; height: 38px;">Filter</button>
        </form>
        <button onclick="window.print()" class="btn" style="border: 1px solid #e2e8f0; background: #fff; color: #475569; height: 38px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                <rect x="6" y="14" width="12" height="8"></rect>
            </svg>
            Cetak PDF
        </button>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="30%">Nama Pegawai</th>
                    <th width="20%">NIK</th>
                    <th width="20%">Afdeling & Jabatan</th>
                    <th width="25%" style="text-align: right;" class="aksi-column">Aksi</th>
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
                        <td>
                            <div style="font-weight: 600; color: #475569;"><?= $row['afdeling'] ?: '-' ?></div>
                            <div style="font-size: 12px; color: #64748b; text-transform: capitalize;"><?= $row['role'] ?></div>
                        </td>
                        <td style="text-align: right;" class="aksi-column">
                            <a href="cetak_kartu.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-primary" style="padding: 6px 10px; font-size: 11px;">
                                ID Card
                            </a>

                            <button onclick='editData(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, "UTF-8") ?>)' class="btn btn-warning" style="padding: 6px 10px; font-size: 11px;">
                                Edit
                            </button>

                            <button onclick='konfirmasiHapus(<?= $row['id'] ?>)' class="btn btn-danger" style="padding: 6px 10px; font-size: 11px;">
                                Hapus
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <?php if ($no == 1): ?>
            <div style="padding: 40px; text-align: center; color: #94a3b8;">
                Belum ada data personil.
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="modalForm" class="modal-overlay">
    <div class="modal-box">
        <form method="POST">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Tambah Personil</h3>
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
                        <select name="role" id="role" class="form-input" required>
                            <option value="karyawan">Karyawan</option>
                            <option value="mandor">Mandor</option>
                            <option value="pengawas">Pengawas</option>
                            <option value="kerani">Kerani</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                        <label class="form-label">Afdeling</label>
                        <select name="afdeling" id="afdeling" class="form-input" required>
                            <option value="">Pilih Afdeling</option>
                            <?php 
                            $afd_query2 = mysqli_query($conn, "SELECT nama_afdeling FROM afdelings ORDER BY nama_afdeling ASC");
                            while ($afd = mysqli_fetch_assoc($afd_query2)): 
                            ?>
                                <option value="<?= htmlspecialchars($afd['nama_afdeling']) ?>"><?= htmlspecialchars($afd['nama_afdeling']) ?></option>
                            <?php endwhile; ?>
                        </select>
                </div>

                <div class="row" style="display: flex; gap: 15px;">
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">Email (Untuk Login)</label>
                        <input type="email" name="email" id="email" class="form-input" required placeholder="nama@kantor.com">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">No HP / WhatsApp</label>
                        <input type="text" name="no_hp" id="no_hp" class="form-input" placeholder="Cth: 08123456789">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div style="position:relative;">
                        <input type="password" id="password" name="password" class="form-input" placeholder="Isi hanya jika ingin mengubah password">
                        <button type="button" onclick="togglePassword()" 
                                style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:4px;display:flex;align-items:center;"
                                title="Tampilkan/Sembunyikan Password">
                            <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
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
    // Inisialisasi Data Edit
    function editData(data) {
        document.getElementById('modalTitle').innerText = 'Edit Personil';
        document.getElementById('id_karyawan').value = data.id;
        document.getElementById('nama').value = data.name;
        document.getElementById('nik').value = data.nik;
        document.getElementById('email').value = data.email || '';
        document.getElementById('no_hp').value = data.no_hp || '';
        document.getElementById('password').value = ''; // Selalu kosongkan password

        if (data.role) document.getElementById('role').value = data.role;
        if (data.afdeling) document.getElementById('afdeling').value = data.afdeling;

        document.getElementById('modalForm').classList.add('show');
    }

    function openModal(type) {
        if (type === 'add') {
            document.getElementById('modalTitle').innerText = 'Tambah Personil';
            document.getElementById('id_karyawan').value = '';
            document.getElementById('nama').value = '';
            document.getElementById('nik').value = '';
            document.getElementById('email').value = '';
            document.getElementById('no_hp').value = '';
            document.getElementById('role').value = 'karyawan';
            document.getElementById('afdeling').value = '';
        }
        document.getElementById('modalForm').classList.add('show');
    }

    function closeModal() {
        document.getElementById('modalForm').classList.remove('show');
    }

    function togglePassword() {
        const pwd = document.getElementById('password');
        const eyeIcon = document.getElementById('eye-icon');
        if (pwd.type === 'password') {
            pwd.type = 'text';
            eyeIcon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
        } else {
            pwd.type = 'password';
            eyeIcon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
        }
    }

    function konfirmasiHapus(id) {
        Swal.fire({
            title: 'Hapus Data?',
            text: 'Data personil ini akan dihapus secara permanen!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '?hapus=' + id;
            }
        });
    }
</script>

<?php include 'templates/footer.php'; ?>