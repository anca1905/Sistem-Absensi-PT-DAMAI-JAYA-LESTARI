<?php
require '../config/config.php';

// --- LOGIKA PHP (BACKEND) ---

// 1. Handle Tambah / Edit Data
if (isset($_POST['simpan_data'])) {
    $id = $_POST['id_karyawan']; // Hidden input
    $nik = mysqli_real_escape_string($conn, trim($_POST['nik']));
    $nama = mysqli_real_escape_string($conn, trim($_POST['nama']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $no_hp = isset($_POST['no_hp']) ? mysqli_real_escape_string($conn, trim($_POST['no_hp'])) : '';
    $afdeling = mysqli_real_escape_string($conn, trim($_POST['afdeling']));
    $role = mysqli_real_escape_string($conn, trim($_POST['role']));
    $jenis_kelamin = isset($_POST['jenis_kelamin']) ? mysqli_real_escape_string($conn, trim($_POST['jenis_kelamin'])) : '';
    $alamat = isset($_POST['alamat']) ? mysqli_real_escape_string($conn, trim($_POST['alamat'])) : '';

    // Password opsional (hanya diupdate jika diisi)
    // Admin tidak dapat melihat password plaintext karyawan karena otomatis di-hash
    $password_sql = "";
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $password_sql = ", password='$password'";
    }

    if (empty($id)) {
        // --- LOGIKA CREATE (INSERT) ---
        $cek = mysqli_query($conn, "SELECT id FROM users WHERE nik='$nik'");
        if (mysqli_num_rows($cek) > 0) {
            swalRedirect('NIK sudah terdaftar!', 'personil.php', 'error', 'NIK Duplikat!');
            exit;
        }

        // Default password dibuatkan oleh admin (default 123456 jika kosong)
        $raw_pw = !empty($_POST['password']) ? $_POST['password'] : '123456';
        $hashed_pw = password_hash($raw_pw, PASSWORD_DEFAULT);

        $query = "INSERT INTO users (nik, name, email, no_hp, password, role, afdeling, jenis_kelamin, alamat) 
                  VALUES ('$nik', '$nama', '$email', '$no_hp', '$hashed_pw', '$role', '$afdeling', '$jenis_kelamin', '$alamat')";
    } else {
        // --- LOGIKA UPDATE ---
        $query = "UPDATE users SET 
                  nik='$nik', 
                  name='$nama', 
                  email='$email', 
                  no_hp='$no_hp',
                  afdeling='$afdeling',
                  role='$role',
                  jenis_kelamin='$jenis_kelamin',
                  alamat='$alamat'
                  $password_sql
                  WHERE id='$id'";
    }

    if (mysqli_query($conn, $query)) {
        swalRedirect('Data personil berhasil disimpan!', 'personil.php', 'success');
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
$list_personil = [];
if ($data_karyawan) {
    while ($row = mysqli_fetch_assoc($data_karyawan)) {
        $list_personil[] = $row;
    }
}

// Data Tanda Tangan untuk Cetak Dokumen Resmi
$nama_kerani = '...................................';
$nama_pengawas = '...................................';
$teks_afdeling = !empty($_GET['afdeling_filter']) ? htmlspecialchars($_GET['afdeling_filter']) : '';

if (!empty($_GET['afdeling_filter'])) {
    $q_k = mysqli_query($conn, "SELECT name FROM users WHERE (jabatan = 'kerani' OR role = 'kerani') AND afdeling = '" . mysqli_real_escape_string($conn, $_GET['afdeling_filter']) . "' LIMIT 1");
    if ($rk = mysqli_fetch_assoc($q_k)) {
        $nama_kerani = $rk['name'];
    }
    $q_p = mysqli_query($conn, "SELECT name FROM users WHERE (jabatan = 'pengawas' OR role = 'pengawas') AND afdeling = '" . mysqli_real_escape_string($conn, $_GET['afdeling_filter']) . "' LIMIT 1");
    if ($rp = mysqli_fetch_assoc($q_p)) {
        $nama_pengawas = $rp['name'];
    }
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

    .btn-info {
        background-color: #0284c7;
        color: white;
    }

    .btn-info:hover {
        background-color: #0369a1;
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
            <h1 class="page-title">Data Personil</h1>
            <p style="color: #64748b; font-size: 13px; margin-top: 4px;">Kelola data pegawai dan akses sistem.</p>
        </div>
        <?php if ($_SESSION['role'] === 'admin'): ?>
            <button onclick="openModal('add')" class="btn btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                Tambah Personil
            </button>
        <?php endif; ?>
    </div>

    <!-- Filter & Aksi -->
    <div class="print-hide" style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
        <form method="GET" style="display: flex; gap: 10px; align-items: center;">
            <select name="afdeling_filter" class="form-input" style="width: auto; padding: 8px 12px;" onchange="this.form.submit()">
                <option value="">Semua Afdeling</option>
                <?php
                $afd_query1 = mysqli_query($conn, "SELECT nama_afdeling FROM afdelings ORDER BY nama_afdeling ASC");
                while ($afd = mysqli_fetch_assoc($afd_query1)):
                ?>
                    <option value="<?= htmlspecialchars($afd['nama_afdeling']) ?>" <?= (isset($_GET['afdeling_filter']) && $_GET['afdeling_filter'] == $afd['nama_afdeling']) ? 'selected' : '' ?>><?= htmlspecialchars($afd['nama_afdeling']) ?></option>
                <?php endwhile; ?>
            </select>
            <select name="role_filter" class="form-input" style="width: auto; padding: 8px 12px;" onchange="this.form.submit()">
                <option value="">Semua Jabatan</option>
                <option value="mandor" <?= (isset($_GET['role_filter']) && $_GET['role_filter'] == 'mandor') ? 'selected' : '' ?>>Mandor</option>
                <option value="karyawan" <?= (isset($_GET['role_filter']) && $_GET['role_filter'] == 'karyawan') ? 'selected' : '' ?>>Karyawan</option>
                <option value="pengawas" <?= (isset($_GET['role_filter']) && $_GET['role_filter'] == 'pengawas') ? 'selected' : '' ?>>Pengawas</option>
                <option value="kerani" <?= (isset($_GET['role_filter']) && $_GET['role_filter'] == 'kerani') ? 'selected' : '' ?>>Kerani</option>
                <option value="keuangan" <?= (isset($_GET['role_filter']) && $_GET['role_filter'] == 'keuangan') ? 'selected' : '' ?>>Keuangan</option>
            </select>
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
                foreach ($list_personil as $row): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td>
                            <div style="font-weight: 600;"><?= htmlspecialchars($row['name']) ?></div>
                            <div style="font-size: 12px; color: #94a3b8;"><?= htmlspecialchars($row['email']) ?></div>
                        </td>
                        <td><span class="badge-nik"><?= htmlspecialchars($row['nik']) ?></span></td>
                        <td>
                            <div style="font-weight: 600; color: #475569;"><?= htmlspecialchars($row['afdeling'] ?: '-') ?></div>
                            <div style="font-size: 12px; color: #64748b; text-transform: capitalize;"><?= htmlspecialchars($row['role']) ?></div>
                        </td>
                        <td style="text-align: right;" class="aksi-column">
                            <button onclick='lihatBiodata(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, "UTF-8") ?>)' class="btn btn-info" style="padding: 6px 10px; font-size: 11px;" title="Lihat Biodata Lengkap">
                                Biodata
                            </button>

                            <a href="cetak_kartu.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-primary" style="padding: 6px 10px; font-size: 11px;">
                                ID Card
                            </a>

                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                <button onclick='editData(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, "UTF-8") ?>)' class="btn btn-warning" style="padding: 6px 10px; font-size: 11px;" title="Edit Data Karyawan">
                                    Edit
                                </button>

                                <button onclick='konfirmasiHapus(<?= $row['id'] ?>)' class="btn btn-danger" style="padding: 6px 10px; font-size: 11px;" title="Hapus Karyawan">
                                    Hapus
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (empty($list_personil)): ?>
            <div style="padding: 40px; text-align: center; color: #94a3b8;">
                Belum ada data personil.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ========== DOKUMEN CETAK RESMI ========== -->
<div id="official-print-doc">
    <style>
        @media print {
            @page {
                size: landscape;
                margin: 10mm;
            }
        }
    </style>

    <!-- Kop Surat -->
    <div class="doc-header">
        <img src="<?= BASE_URL ?>assets/img/logo.png" alt="" class="doc-header-logo" onerror="this.style.display='none'">
        <div class="doc-header-text">
            <h1>PT Damai Jaya Lestari</h1>
            <h2>Data Master Personil &amp; Akses Sistem</h2>
            <p>Jl. Perkebunan No. 1 &nbsp;|&nbsp; Telp: (021) 000-0000 &nbsp;|&nbsp; Email: admin@djl.co.id</p>
        </div>
    </div>

    <!-- Judul & Metadata -->
    <div class="doc-title">
        <h3>DAFTAR DATA PERSONIL</h3>
    </div>
    <div class="doc-meta">
        <div><strong>Afdeling&nbsp;:</strong> <?= !empty($_GET['afdeling_filter']) ? htmlspecialchars($_GET['afdeling_filter']) : 'Semua Afdeling' ?></div>
        <div><strong>Jabatan&nbsp;&nbsp;:</strong> <?= !empty($_GET['role_filter']) ? ucfirst(htmlspecialchars($_GET['role_filter'])) : 'Semua Jabatan' ?></div>
        <div><strong>Total&nbsp;&nbsp;&nbsp;&nbsp;:</strong> <?= count($list_personil) ?> Personil</div>
        <div><strong>Dicetak&nbsp;&nbsp;:</strong> <?= date('d F Y, H:i') ?> WIB</div>
    </div>

    <!-- Tabel Dokumen -->
    <table class="doc-table">
        <thead>
            <tr>
                <th style="width: 35px;">No</th>
                <th style="width: 100px;">NIK</th>
                <th style="text-align: left;">Nama Lengkap</th>
                <th style="text-align: left; width: 220px;">Email</th>
                <th style="width: 130px;">No. HP / WA</th>
                <th style="width: 100px;">Afdeling</th>
                <th style="width: 110px;">Jabatan</th>
                <th style="width: 90px;">Jenis Kelamin</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($list_personil)): ?>
                <?php $pno = 1; foreach ($list_personil as $p): ?>
                    <tr>
                        <td class="text-center"><?= $pno++ ?></td>
                        <td class="text-center" style="font-weight: bold;"><?= htmlspecialchars($p['nik']) ?></td>
                        <td class="text-left" style="font-weight: 600;"><?= htmlspecialchars($p['name']) ?></td>
                        <td class="text-left"><?= htmlspecialchars($p['email'] ?: '-') ?></td>
                        <td class="text-center"><?= htmlspecialchars($p['no_hp'] ?: '-') ?></td>
                        <td class="text-center"><?= htmlspecialchars($p['afdeling'] ?: '-') ?></td>
                        <td class="text-center" style="text-transform: capitalize;"><?= htmlspecialchars($p['role'] ?: ($p['jabatan'] ?? '-')) ?></td>
                        <td class="text-center"><?= htmlspecialchars($p['jenis_kelamin'] ?: '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center" style="padding: 20px;">Tidak ada data personil.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Tanda Tangan -->
    <div class="doc-signature">
        <?php if (!empty($_GET['afdeling_filter'])): ?>
            <div class="doc-signature-col">
                <p>Diketahui oleh,</p>
                <span class="sig-name"><?= htmlspecialchars($nama_pengawas) ?></span>
                <div style="font-weight:bold; margin-top:4px;">Pengawas <?= $teks_afdeling ?></div>
            </div>
            <div class="doc-signature-col">
                <p>Disusun oleh,</p>
                <span class="sig-name"><?= htmlspecialchars($nama_kerani) ?></span>
                <div style="font-weight:bold; margin-top:4px;">Kerani <?= $teks_afdeling ?></div>
            </div>
        <?php else: ?>
            <div class="doc-signature-col">
                <p>Mengetahui,</p>
                <span class="sig-name">( ................................... )</span>
                <div style="font-weight:bold; margin-top:4px;">Pimpinan Perusahaan</div>
            </div>
            <div class="doc-signature-col">
                <p>Dicetak oleh,</p>
                <span class="sig-name"><?= htmlspecialchars($_SESSION['nama'] ?? 'Administrator') ?></span>
                <div style="font-weight:bold; margin-top:4px;">Administrator Sistem</div>
            </div>
        <?php endif; ?>
    </div>

    <div class="doc-footer">
        Dokumen ini dicetak secara otomatis oleh Sistem Informasi PT Damai Jaya Lestari pada <?= date('d F Y \p\u\k\u\l H:i') ?> WIB.
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
                            <option value="keuangan">Keuangan</option>
                        </select>
                    </div>
                </div>

                <div class="row" style="display: flex; gap: 15px;">
                    <div class="form-group" style="flex: 1;">
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
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">Jenis Kelamin</label>
                        <select name="jenis_kelamin" id="jenis_kelamin" class="form-input" required>
                            <option value="">Pilih Jenis Kelamin</option>
                            <option value="Laki-laki">Laki-laki</option>
                            <option value="Perempuan">Perempuan</option>
                        </select>
                    </div>
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
                    <label class="form-label">Alamat Lengkap</label>
                    <textarea name="alamat" id="alamat" class="form-input" rows="2" placeholder="Contoh: Jl. Sawit Raya Blok B No. 12"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Password Akun</label>
                    <div style="position:relative;">
                        <input type="password" id="password" name="password" class="form-input" placeholder="Kosongkan jika tidak ingin mereset password">
                        <button type="button" onclick="togglePassword()"
                            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:4px;display:flex;align-items:center;"
                            title="Tampilkan/Sembunyikan Password">
                            <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                    <div style="margin-top:6px; font-size:11.5px; color:#64748b; line-height: 1.4;">
                        🔒 <b>Privasi Password</b>: Password karyawan terenkripsi otomatis (Admin tidak dapat melihat password karyawan). Default awal user baru: <b>123456</b>. Karyawan dapat mengubah passwordnya sendiri melalui menu profil.
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" onclick="closeModal()" class="btn" style="background: #f1f5f9; color: #475569; margin-right: 10px;">Batal</button>
                <button type="submit" name="simpan_data" class="btn btn-primary">Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Detail Biodata Karyawan -->
<div id="modalBiodata" class="modal-overlay">
    <div class="modal-box" style="max-width: 550px;">
        <div class="modal-header">
            <h3 class="modal-title">Biodata Lengkap Pegawai</h3>
            <button type="button" class="modal-close" onclick="closeModalBiodata()">&times;</button>
        </div>
        <div class="modal-body" style="padding: 24px;">
            <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid #e2e8f0;">
                <div id="bio_avatar" style="width: 54px; height: 54px; border-radius: 14px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 800;">
                    K
                </div>
                <div>
                    <h3 id="bio_nama" style="margin: 0 0 4px 0; font-size: 18px; color: #0f172a; font-weight: 800;">Nama Karyawan</h3>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <span id="bio_role_badge" style="background: #e0e7ff; color: #4338ca; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase;">KARYAWAN</span>
                        <span id="bio_afdeling_badge" style="background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 600;">Afdeling</span>
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <div style="background: #f8fafc; padding: 10px 14px; border-radius: 8px; border: 1px solid #f1f5f9;">
                    <div style="font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase;">NIK (Nomor Induk)</div>
                    <div id="bio_nik" style="font-weight: 700; color: #0f172a; margin-top: 2px; font-family: monospace;">-</div>
                </div>
                <div style="background: #f8fafc; padding: 10px 14px; border-radius: 8px; border: 1px solid #f1f5f9;">
                    <div style="font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase;">Jenis Kelamin</div>
                    <div id="bio_jk" style="font-weight: 700; color: #0f172a; margin-top: 2px;">-</div>
                </div>
                <div style="background: #f8fafc; padding: 10px 14px; border-radius: 8px; border: 1px solid #f1f5f9;">
                    <div style="font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase;">Email Akun</div>
                    <div id="bio_email" style="font-weight: 600; color: #0f172a; margin-top: 2px; word-break: break-all;">-</div>
                </div>
                <div style="background: #f8fafc; padding: 10px 14px; border-radius: 8px; border: 1px solid #f1f5f9;">
                    <div style="font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase;">No. HP / WhatsApp</div>
                    <div id="bio_nohp" style="font-weight: 700; color: #0f172a; margin-top: 2px;">-</div>
                </div>
            </div>

            <div style="background: #f8fafc; padding: 12px 14px; border-radius: 8px; border: 1px solid #f1f5f9; margin-bottom: 14px;">
                <div style="font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase;">Alamat Lengkap</div>
                <div id="bio_alamat" style="font-size: 13.5px; color: #1e293b; margin-top: 4px; line-height: 1.5;">-</div>
            </div>

            <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 10px 14px; font-size: 12px; color: #1e40af; display: flex; align-items: flex-start; gap: 8px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" style="flex-shrink:0; margin-top: 2px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                <span><b>Keamanan Kata Sandi</b>: Terenkripsi satu arah dengan BCrypt. Admin tidak dapat melihat kata sandi karyawan demi privasi &amp; keamanan.</span>
            </div>
        </div>
        <div class="modal-footer" style="display: flex; justify-content: space-between;">
            <button type="button" onclick="closeModalBiodata()" class="btn" style="background: #f1f5f9; color: #475569;">Tutup</button>
            <button type="button" id="btnEditFromBio" class="btn btn-warning">
                Edit Data Ini
            </button>
        </div>
    </div>
</div>

<script>
    let currentSelectedData = null;

    function lihatBiodata(data) {
        currentSelectedData = data;
        document.getElementById('bio_nama').innerText = data.name || '-';
        document.getElementById('bio_avatar').innerText = (data.name || 'K').charAt(0).toUpperCase();
        document.getElementById('bio_nik').innerText = data.nik || '-';
        document.getElementById('bio_email').innerText = data.email || '-';
        document.getElementById('bio_nohp').innerText = data.no_hp || '-';
        document.getElementById('bio_role_badge').innerText = (data.role || 'Karyawan').toUpperCase();
        document.getElementById('bio_afdeling_badge').innerText = 'Afdeling: ' + (data.afdeling || '-');
        document.getElementById('bio_jk').innerText = data.jenis_kelamin || '-';
        document.getElementById('bio_alamat').innerText = data.alamat || 'Belum diisi';

        document.getElementById('modalBiodata').classList.add('show');
    }

    function closeModalBiodata() {
        document.getElementById('modalBiodata').classList.remove('show');
    }

    document.getElementById('btnEditFromBio').addEventListener('click', function() {
        closeModalBiodata();
        if (currentSelectedData) {
            editData(currentSelectedData);
        }
    });

    // Inisialisasi Data Edit
    function editData(data) {
        document.getElementById('modalTitle').innerText = 'Edit Data Personil';
        document.getElementById('id_karyawan').value = data.id;
        document.getElementById('nama').value = data.name || '';
        document.getElementById('nik').value = data.nik || '';
        document.getElementById('email').value = data.email || '';
        document.getElementById('no_hp').value = data.no_hp || '';
        document.getElementById('alamat').value = data.alamat || '';
        document.getElementById('password').value = ''; // Selalu kosongkan password

        if (data.role) document.getElementById('role').value = data.role;
        if (data.afdeling) document.getElementById('afdeling').value = data.afdeling;
        if (data.jenis_kelamin) document.getElementById('jenis_kelamin').value = data.jenis_kelamin;

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
            document.getElementById('alamat').value = '';
            document.getElementById('password').value = '';
            document.getElementById('role').value = 'karyawan';
            document.getElementById('afdeling').value = '';
            document.getElementById('jenis_kelamin').value = '';
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