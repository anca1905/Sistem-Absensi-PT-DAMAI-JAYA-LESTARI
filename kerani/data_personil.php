<?php
require '../config/config.php';

// --- LOGIKA PHP (BACKEND) ---
if (isset($_POST['simpan_data'])) {
    $id = $_POST['id_karyawan'];
    $nik = mysqli_real_escape_string($conn, $_POST['nik']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    
    $email = isset($_POST['email']) ? mysqli_real_escape_string($conn, $_POST['email']) : '';
    $no_hp = isset($_POST['no_hp']) ? mysqli_real_escape_string($conn, $_POST['no_hp']) : '';
    $afdeling = isset($_POST['afdeling']) ? mysqli_real_escape_string($conn, $_POST['afdeling']) : '';
    $role = isset($_POST['role']) ? mysqli_real_escape_string($conn, $_POST['role']) : (isset($_GET['role']) ? $_GET['role'] : 'karyawan');

    $password_sql = "";
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $password_sql = ", password='$password'";
    }

    if (empty($id)) {
        $cek = mysqli_query($conn, "SELECT id FROM users WHERE nik='$nik'");
        if (mysqli_num_rows($cek) > 0) {
            swalRedirect('NIK sudah terdaftar!', "data_personil.php?role=$role", 'error', 'NIK Duplikat!');
            exit;
        }
        $pw_hash = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : password_hash('123456', PASSWORD_DEFAULT);
        $query = "INSERT INTO users (nik, name, email, no_hp, password, role, afdeling) 
                  VALUES ('$nik', '$nama', '$email', '$no_hp', '$pw_hash', '$role', '$afdeling')";
    } else {
        $query = "UPDATE users SET 
                  nik='$nik', name='$nama', email='$email', no_hp='$no_hp', afdeling='$afdeling', role='$role' $password_sql 
                  WHERE id='$id'";
    }

    if (mysqli_query($conn, $query)) {
        swalRedirect('Data berhasil disimpan!', "data_personil.php?role=$role", 'success');
    } else {
        swalAlert('Gagal menyimpan: ' . mysqli_error($conn), 'error');
    }
}

if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $role_redirect = isset($_GET['role']) ? $_GET['role'] : 'karyawan';
    mysqli_query($conn, "DELETE FROM users WHERE id='$id'");
    swalRedirect('Data personil berhasil dihapus!', "data_personil.php?role=$role_redirect", 'success');
}

include 'templates/header.php';

$role_filter = isset($_GET['role']) ? $_GET['role'] : 'karyawan';
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

    .btn-add {
        background: var(--accent);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: background 0.2s;
    }
    .btn-add:hover { background: #2563eb; }

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
        padding: 16px 24px;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
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

    .action-group {
        display: flex;
        gap: 8px;
    }

    .btn-action {
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        border: 1px solid transparent;
        cursor: pointer;
    }

    .btn-idcard { background: #eff6ff; color: #3b82f6; border-color: #bfdbfe; }
    .btn-edit { background: #fef3c7; color: #d97706; border-color: #fde68a; }
    .btn-delete { background: #fee2e2; color: #ef4444; border-color: #fecaca; }
</style>

<div class="header-actions">
    <form method="GET" id="filterForm">
        <select name="role" class="filter-select" onchange="document.getElementById('filterForm').submit()">
            <option value="kerani" <?= $role_filter == 'kerani' ? 'selected' : '' ?>>Kerani</option>
            <option value="karyawan" <?= $role_filter == 'karyawan' ? 'selected' : '' ?>>Karyawan</option>
            <option value="mandor" <?= $role_filter == 'mandor' ? 'selected' : '' ?>>Mandor</option>
            <option value="pengawas" <?= $role_filter == 'pengawas' ? 'selected' : '' ?>>Pengawas</option>
        </select>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Data <?= ucfirst($role_filter) ?> AFDELING XII</h3>
    </div>
    
    <div class="table-container">
        <table class="table-data">
            <thead>
                <tr>
                    <th width="50">NO</th>
                    <th>NIK</th>
                    <th>NAMA LENGKAP</th>
                    <th width="250">AKSI</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $role_safe = mysqli_real_escape_string($conn, $role_filter);
                $afdeling_kerani = isset($_SESSION['afdeling']) ? mysqli_real_escape_string($conn, $_SESSION['afdeling']) : '';
                
                if (!empty($afdeling_kerani)) {
                    $query = mysqli_query($conn, "SELECT * FROM users WHERE role='$role_safe' AND afdeling='$afdeling_kerani' ORDER BY name ASC");
                } else {
                    $query = mysqli_query($conn, "SELECT * FROM users WHERE role='$role_safe' ORDER BY name ASC");
                }
                $no = 1;
                while($row = mysqli_fetch_assoc($query)): 
                ?>
                <tr>
                    <td style="color: var(--text-muted); font-weight: 600;"><?= $no++ ?></td>
                    <td style="font-weight: 700;"><?= htmlspecialchars($row['nik']) ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td>
                        <div class="action-group">
                            <a href="../admin/cetak_kartu.php?id=<?= $row['id'] ?>" target="_blank" class="btn-action btn-idcard">ID Card</a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                
                <?php if(mysqli_num_rows($query) == 0): ?>
                <tr>
                    <td colspan="4" style="text-align: center; color: var(--text-muted);">Belum ada data personil untuk peran ini.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>



<?php include 'templates/footer.php'; ?>
