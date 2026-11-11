<?php
require '../config/config.php';
include 'templates/header.php';

$user_id = (int)$_SESSION['user_id'];

// 1. Handle Update Biodata Sendiri
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_biodata'])) {
    $nama          = mysqli_real_escape_string($conn, trim($_POST['nama']));
    $email         = mysqli_real_escape_string($conn, trim($_POST['email']));
    $no_hp         = mysqli_real_escape_string($conn, trim($_POST['no_hp']));
    $jenis_kelamin = mysqli_real_escape_string($conn, trim($_POST['jenis_kelamin']));
    $alamat        = mysqli_real_escape_string($conn, trim($_POST['alamat']));

    // Cek duplikasi email jika email diubah
    $cek_email = mysqli_query($conn, "SELECT id FROM users WHERE email='$email' AND id != $user_id");
    if (mysqli_num_rows($cek_email) > 0) {
        swalAlert('Email sudah digunakan oleh pengguna lain!', 'error');
    } else {
        $q_update = "UPDATE users SET 
                     name='$nama', 
                     email='$email', 
                     no_hp='$no_hp', 
                     jenis_kelamin='$jenis_kelamin', 
                     alamat='$alamat' 
                     WHERE id=$user_id";

        if (mysqli_query($conn, $q_update)) {
            $_SESSION['nama'] = $nama;
            swalRedirect('Biodata berhasil diperbarui!', 'profil.php', 'success');
        } else {
            swalAlert('Gagal memperbarui biodata: ' . mysqli_error($conn), 'error');
        }
    }
}

// 2. Handle Ubah Password Sendiri (Admin tidak dapat melihat password ini)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ubah_password'])) {
    $password_lama = $_POST['password_lama'] ?? '';
    $password_baru = $_POST['password_baru'] ?? '';
    $password_konf = $_POST['password_konfirmasi'] ?? '';

    // Ambil data user saat ini
    $q_user = mysqli_query($conn, "SELECT password FROM users WHERE id=$user_id");
    $user_data = mysqli_fetch_assoc($q_user);

    $old_valid = false;
    if ($user_data) {
        if (password_verify($password_lama, $user_data['password'])) {
            $old_valid = true;
        } elseif ($password_lama === $user_data['password']) {
            $old_valid = true;
        }
    }

    if (!$old_valid) {
        swalAlert('Password saat ini yang Anda masukkan salah!', 'error', 'Gagal Verifikasi!');
    } elseif (strlen($password_baru) < 6) {
        swalAlert('Password baru minimal harus 6 karakter!', 'warning');
    } elseif ($password_baru !== $password_konf) {
        swalAlert('Konfirmasi password baru tidak cocok!', 'warning');
    } else {
        $new_hash = password_hash($password_baru, PASSWORD_DEFAULT);
        $q_pwd = mysqli_query($conn, "UPDATE users SET password='$new_hash' WHERE id=$user_id");

        if ($q_pwd) {
            swalRedirect('Password Anda berhasil diubah! Gunakan password baru ini saat login.', 'profil.php', 'success');
        } else {
            swalAlert('Gagal mengubah password: ' . mysqli_error($conn), 'error');
        }
    }
}

// Ambil data lengkap karyawan
$query_me = mysqli_query($conn, "SELECT * FROM users WHERE id=$user_id");
$me = mysqli_fetch_assoc($query_me);
?>

<style>
    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #64748b;
        text-decoration: none;
        font-weight: 700;
        font-size: 14px;
        margin-bottom: 20px;
        transition: color 0.2s;
    }
    .btn-back:hover {
        color: var(--primary-start);
    }

    .profile-card {
        background: linear-gradient(135deg, var(--primary-start) 0%, var(--primary-end) 100%);
        border-radius: 20px;
        padding: 24px;
        color: white;
        margin-bottom: 24px;
        box-shadow: 0 10px 25px rgba(54, 72, 217, 0.25);
        display: flex;
        align-items: center;
        gap: 16px;
        position: relative;
        overflow: hidden;
    }

    .profile-card::after {
        content: '';
        position: absolute;
        top: -40px;
        right: -30px;
        width: 140px;
        height: 140px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
    }

    .profile-avatar {
        width: 64px;
        height: 64px;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(5px);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
        font-weight: 800;
        border: 2px solid rgba(255, 255, 255, 0.3);
        flex-shrink: 0;
    }

    .profile-info h2 {
        margin: 0 0 4px 0;
        font-size: 19px;
        font-weight: 800;
        letter-spacing: -0.3px;
    }

    .profile-info .nik-badge {
        display: inline-block;
        background: rgba(255, 255, 255, 0.25);
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        font-family: monospace;
        letter-spacing: 0.5px;
    }

    /* Tabs */
    .tab-nav {
        display: flex;
        background: #e2e8f0;
        border-radius: 14px;
        padding: 4px;
        gap: 4px;
        margin-bottom: 24px;
    }

    .tab-btn {
        flex: 1;
        padding: 12px;
        border-radius: 10px;
        border: none;
        background: transparent;
        color: #64748b;
        font-weight: 700;
        font-size: 13.5px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.2s;
    }

    .tab-btn.active {
        background: #ffffff;
        color: var(--primary-start);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }

    .form-container {
        background: #ffffff;
        border-radius: 18px;
        padding: 24px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
        border: 1px solid #e2e8f0;
        margin-bottom: 24px;
    }

    .form-section-title {
        font-size: 16px;
        font-weight: 800;
        color: var(--text-dark);
        margin: 0 0 18px 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .form-group {
        margin-bottom: 18px;
    }

    .form-label {
        display: block;
        font-weight: 700;
        font-size: 13px;
        color: #334155;
        margin-bottom: 6px;
    }

    .form-control {
        width: 100%;
        padding: 12px 14px;
        border-radius: 12px;
        border: 2px solid #e2e8f0;
        background-color: #f8fafc;
        font-size: 14.5px;
        font-family: inherit;
        color: #1e293b;
        transition: all 0.2s;
        box-sizing: border-box;
    }

    .form-control:focus {
        border-color: var(--primary-start);
        background-color: #ffffff;
        outline: none;
        box-shadow: 0 0 0 4px rgba(66, 88, 255, 0.1);
    }

    .form-control[readonly] {
        background-color: #f1f5f9;
        color: #64748b;
        cursor: not-allowed;
        border-color: #e2e8f0;
    }

    .readonly-hint {
        font-size: 11px;
        color: #94a3b8;
        margin-top: 4px;
    }

    .btn-submit {
        width: 100%;
        padding: 14px;
        border-radius: 12px;
        background: linear-gradient(135deg, var(--primary-start) 0%, var(--primary-end) 100%);
        color: white;
        font-size: 14.5px;
        font-weight: 700;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        box-shadow: 0 4px 12px rgba(66, 88, 255, 0.25);
        transition: all 0.2s;
    }

    .btn-submit:hover, .btn-submit:active {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(66, 88, 255, 0.35);
    }

    .security-notice {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 12px;
        padding: 14px;
        margin-bottom: 20px;
        font-size: 12.5px;
        color: #1e40af;
        line-height: 1.5;
        display: flex;
        gap: 10px;
        align-items: flex-start;
    }

    .pwd-wrapper {
        position: relative;
    }

    .pwd-toggle-btn {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        cursor: pointer;
        color: #94a3b8;
        padding: 4px;
        display: flex;
        align-items: center;
    }
</style>

<div class="animate-up">
    <!-- Tombol Kembali -->
    <a href="index.php" class="btn-back">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6"></polyline>
        </svg>
        Kembali ke Beranda
    </a>

    <!-- Kartu Profil Ringkas -->
    <div class="profile-card">
        <div class="profile-avatar">
            <?= strtoupper(substr($me['name'] ?? 'K', 0, 1)) ?>
        </div>
        <div class="profile-info">
            <h2><?= htmlspecialchars($me['name']) ?></h2>
            <div style="margin-bottom: 6px;">
                <span class="nik-badge"><?= htmlspecialchars($me['nik']) ?></span>
            </div>
            <div style="font-size: 12px; opacity: 0.9;">
                <?= ucfirst(htmlspecialchars($me['role'])) ?> &bull; Afdeling: <?= htmlspecialchars($me['afdeling'] ?: '-') ?>
            </div>
        </div>
    </div>

    <!-- Tab Navigasi -->
    <div class="tab-nav">
        <button type="button" class="tab-btn active" id="tabBtnBio" onclick="switchTab('biodata')">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            Biodata Diri
        </button>
        <button type="button" class="tab-btn" id="tabBtnPwd" onclick="switchTab('password')">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
            Ganti Password
        </button>
    </div>

    <!-- Section 1: Biodata Diri -->
    <div id="sectionBiodata" class="form-container">
        <h3 class="form-section-title">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
            </svg>
            Informasi &amp; Edit Biodata
        </h3>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Nomor Induk Karyawan (NIK)</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($me['nik']) ?>" readonly>
                <div class="readonly-hint">NIK ditetapkan resmi oleh perusahaan dan tidak dapat diubah sendiri.</div>
            </div>

            <div class="form-group">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($me['name']) ?>" required placeholder="Masukkan nama lengkap">
            </div>

            <div class="form-group">
                <label class="form-label">Afdeling &amp; Jabatan</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($me['afdeling'] ?: '-') ?> (<?= ucfirst(htmlspecialchars($me['role'])) ?>)" readonly>
                <div class="readonly-hint">Afdeling dan jabatan diatur oleh Administrator perusahaan.</div>
            </div>

            <div class="form-group">
                <label class="form-label">Jenis Kelamin</label>
                <select name="jenis_kelamin" class="form-control">
                    <option value="">Pilih Jenis Kelamin</option>
                    <option value="Laki-laki" <?= ($me['jenis_kelamin'] ?? '') === 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                    <option value="Perempuan" <?= ($me['jenis_kelamin'] ?? '') === 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">No. HP / WhatsApp Aktif</label>
                <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($me['no_hp'] ?? '') ?>" placeholder="Contoh: 08123456789" required>
                <div class="readonly-hint">Digunakan untuk menerima notifikasi perizinan dan konfirmasi dari Kerani.</div>
            </div>

            <div class="form-group">
                <label class="form-label">Alamat Email (Akun Login)</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($me['email'] ?? '') ?>" placeholder="nama@kantor.com" required>
            </div>

            <div class="form-group">
                <label class="form-label">Alamat Tempat Tinggal</label>
                <textarea name="alamat" class="form-control" rows="3" placeholder="Masukkan alamat domisili Anda"><?= htmlspecialchars($me['alamat'] ?? '') ?></textarea>
            </div>

            <button type="submit" name="update_biodata" class="btn-submit">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                Simpan Perubahan Biodata
            </button>
        </form>
    </div>

    <!-- Section 2: Ganti Password -->
    <div id="sectionPassword" class="form-container" style="display: none;">
        <h3 class="form-section-title">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
            Ubah Kata Sandi Akun
        </h3>

        <div class="security-notice">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <div>
                <b>Privasi Kata Sandi:</b> Kata sandi Anda terenkripsi secara aman. Admin maupun pihak lain tidak dapat melihat kata sandi Anda. Silakan ubah kata sandi bawaan dari admin agar akun Anda tetap aman.
            </div>
        </div>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Password Saat Ini (Lama)</label>
                <div class="pwd-wrapper">
                    <input type="password" name="password_lama" id="pwd_lama" class="form-control" required placeholder="Masukkan password saat ini">
                    <button type="button" class="pwd-toggle-btn" onclick="toggleField('pwd_lama')">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Password Baru (Minimal 6 Karakter)</label>
                <div class="pwd-wrapper">
                    <input type="password" name="password_baru" id="pwd_baru" class="form-control" required minlength="6" placeholder="Masukkan password baru">
                    <button type="button" class="pwd-toggle-btn" onclick="toggleField('pwd_baru')">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Konfirmasi Password Baru</label>
                <div class="pwd-wrapper">
                    <input type="password" name="password_konfirmasi" id="pwd_konf" class="form-control" required minlength="6" placeholder="Ulangi password baru">
                    <button type="button" class="pwd-toggle-btn" onclick="toggleField('pwd_konf')">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>
            </div>

            <button type="submit" name="ubah_password" class="btn-submit">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
                Perbarui Password Akun
            </button>
        </form>
    </div>
</div>

<script>
    function switchTab(tab) {
        const secBio = document.getElementById('sectionBiodata');
        const secPwd = document.getElementById('sectionPassword');
        const btnBio = document.getElementById('tabBtnBio');
        const btnPwd = document.getElementById('tabBtnPwd');

        if (tab === 'biodata') {
            secBio.style.display = 'block';
            secPwd.style.display = 'none';
            btnBio.classList.add('active');
            btnPwd.classList.remove('active');
        } else {
            secBio.style.display = 'none';
            secPwd.style.display = 'block';
            btnBio.classList.remove('active');
            btnPwd.classList.add('active');
        }
    }

    function toggleField(fieldId) {
        const input = document.getElementById(fieldId);
        if (input.type === 'password') {
            input.type = 'text';
        } else {
            input.type = 'password';
        }
    }
</script>

<?php include 'templates/footer.php'; ?>
