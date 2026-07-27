<?php
require '../config/config.php';

// --- LOGIKA PHP (BACKEND) ---
if (isset($_POST['simpan_data'])) {
    $id = $_POST['id_karyawan'];
    $nik = mysqli_real_escape_string($conn, $_POST['nik']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    
    $email = isset($_POST['email']) ? mysqli_real_escape_string($conn, $_POST['email']) : '';
    $jabatan = isset($_POST['jabatan']) ? mysqli_real_escape_string($conn, $_POST['jabatan']) : '';
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
        $query = "INSERT INTO users (nik, name, email, no_hp, password, role, jabatan, afdeling) 
                  VALUES ('$nik', '$nama', '$email', '$no_hp', '$pw_hash', '$role', '$jabatan', '$afdeling')";
    } else {
        $query = "UPDATE users SET 
                  nik='$nik', name='$nama', email='$email', no_hp='$no_hp', jabatan='$jabatan', afdeling='$afdeling', role='$role' $password_sql 
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
    
    <button class="btn-add" onclick="openPersonilModal('add')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        Tambah <?= ucfirst($role_filter) ?>
    </button>
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
                            <button onclick='editPersonilData(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, "UTF-8") ?>)' class="btn-action btn-edit">Edit</button>
                            <a href="?role=<?= urlencode($role_filter) ?>&hapus=<?= $row['id'] ?>" onclick="return confirm('Yakin hapus data ini?')" class="btn-action btn-delete">Hapus</a>
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

<!-- Modal Form Personil -->
<div id="modalFormPersonil" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9999; justify-content: center; align-items: center;">
    <div class="modal-content" style="background: white; border-radius: 12px; width: 100%; max-width: 500px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
        <form method="POST">
            <input type="hidden" name="id_karyawan" id="id_karyawan">
            
            <div class="modal-header" style="padding: 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
                <h3 id="modalTitle" style="margin: 0; font-size: 18px; color: #1e293b;">Tambah Personil</h3>
                <button type="button" onclick="closePersonilModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b; padding: 0; line-height: 1;">&times;</button>
            </div>

            <div class="modal-body" style="padding: 20px; display: flex; flex-direction: column; gap: 15px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #475569;">Nama Lengkap</label>
                    <input type="text" name="nama" id="nama" required class="filter-select" style="width: 100%; box-sizing: border-box; font-weight: normal;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #475569;">NIK</label>
                    <input type="text" name="nik" id="nik" required class="filter-select" style="width: 100%; box-sizing: border-box; font-weight: normal;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #475569;">Email (Opsional)</label>
                    <input type="email" name="email" id="email" class="filter-select" style="width: 100%; box-sizing: border-box; font-weight: normal;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #475569;">Jabatan (Opsional)</label>
                    <input type="text" name="jabatan" id="jabatan" class="filter-select" style="width: 100%; box-sizing: border-box; font-weight: normal;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #475569;">No HP / WhatsApp (Opsional)</label>
                    <input type="text" name="no_hp" id="no_hp" class="filter-select" style="width: 100%; box-sizing: border-box; font-weight: normal;" placeholder="08xxxxxxxx">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #475569;">Password (Opsional)</label>
                    <input type="password" name="password" id="password" class="filter-select" style="width: 100%; box-sizing: border-box; font-weight: normal;" placeholder="Kosongkan jika tidak mengubah">
                </div>
                <div style="display: flex; gap: 15px;">
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #475569;">Peran</label>
                        <select name="role" id="role" class="filter-select" style="width: 100%; box-sizing: border-box; font-weight: normal;">
                            <option value="karyawan">Karyawan</option>
                            <option value="mandor">Mandor</option>
                            <option value="pengawas">Pengawas</option>
                            <option value="kerani">Kerani</option>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #475569;">Afdeling</label>
                        <select name="afdeling" id="afdeling" class="filter-select" style="width: 100%; box-sizing: border-box; font-weight: normal;">
                            <option value="">- Pilih Afdeling -</option>
                            <?php 
                            $afd_query = mysqli_query($conn, "SELECT nama_afdeling FROM afdelings ORDER BY nama_afdeling ASC");
                            while ($afd = mysqli_fetch_assoc($afd_query)): 
                            ?>
                                <option value="<?= htmlspecialchars($afd['nama_afdeling']) ?>"><?= htmlspecialchars($afd['nama_afdeling']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="modal-footer" style="padding: 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px; background: #f8fafc;">
                <button type="button" onclick="closePersonilModal()" class="filter-select" style="background: white; font-weight: 600;">Batal</button>
                <button type="submit" name="simpan_data" class="btn-add">Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<script>
    const modalPersonil = document.getElementById('modalFormPersonil');

    function editPersonilData(data) {
        document.getElementById('modalTitle').innerText = 'Edit Personil';
        document.getElementById('id_karyawan').value = data.id;
        document.getElementById('nama').value = data.name;
        document.getElementById('nik').value = data.nik;
        document.getElementById('email').value = data.email || '';
        document.getElementById('no_hp').value = data.no_hp || '';
        document.getElementById('jabatan').value = data.jabatan || '';
        document.getElementById('role').value = data.role || 'karyawan';
        document.getElementById('afdeling').value = data.afdeling || '';
        
        modalPersonil.style.display = 'flex';
    }

    function openPersonilModal(type) {
        if(type === 'add') {
            document.getElementById('modalTitle').innerText = 'Tambah Personil';
            document.getElementById('id_karyawan').value = '';
            document.getElementById('nama').value = '';
            document.getElementById('nik').value = '';
            document.getElementById('email').value = '';
            document.getElementById('no_hp').value = '';
            document.getElementById('jabatan').value = '';
            document.getElementById('role').value = '<?= $role_filter ?>';
            document.getElementById('afdeling').value = '<?= isset($_SESSION["afdeling"]) ? $_SESSION["afdeling"] : "" ?>';
        }
        modalPersonil.style.display = 'flex';
    }

    function closePersonilModal() {
        modalPersonil.style.display = 'none';
    }
</script>

<?php include 'templates/footer.php'; ?>
