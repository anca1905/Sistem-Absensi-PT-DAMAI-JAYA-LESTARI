<?php
require '../config/config.php';
include 'templates/header.php';

$user_id = (int)($_SESSION['user_id'] ?? 0);

// 1. Handle Update Biodata Sendiri
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_biodata'])) {
    $nama          = mysqli_real_escape_string($conn, trim($_POST['nama'] ?? ''));
    $email         = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
    $no_hp         = mysqli_real_escape_string($conn, trim($_POST['no_hp'] ?? ''));
    $jenis_kelamin = mysqli_real_escape_string($conn, trim($_POST['jenis_kelamin'] ?? ''));
    $alamat        = mysqli_real_escape_string($conn, trim($_POST['alamat'] ?? ''));

    if (empty($nama)) {
        swalAlert('Nama lengkap tidak boleh kosong!', 'warning');
    } elseif (empty($email)) {
        swalAlert('Email tidak boleh kosong!', 'warning');
    } else {
        // Cek duplikasi email jika email diubah
        $cek_email = mysqli_query($conn, "SELECT id FROM users WHERE email='$email' AND id != $user_id");
        if (mysqli_num_rows($cek_email) > 0) {
            swalAlert('Email sudah digunakan oleh pengguna lain!', 'error', 'Email Duplikat!');
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
                $_SESSION['name'] = $nama;
                swalRedirect('Biodata berhasil diperbarui!', 'profil.php', 'success');
            } else {
                swalAlert('Gagal memperbarui biodata: ' . mysqli_error($conn), 'error');
            }
        }
    }
}

// 2. Handle Ubah Password Sendiri
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
            swalRedirect('Password Anda berhasil diubah! Gunakan password baru ini saat login selanjutnya.', 'profil.php', 'success');
        } else {
            swalAlert('Gagal mengubah password: ' . mysqli_error($conn), 'error');
        }
    }
}

// Ambil data lengkap kerani
$query_me = mysqli_query($conn, "SELECT * FROM users WHERE id=$user_id");
$me = mysqli_fetch_assoc($query_me);
?>

<style>
    .btn-back-kerani {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #64748b;
        text-decoration: none;
        font-weight: 700;
        font-size: 13.5px;
        margin-bottom: 20px;
        padding: 8px 14px;
        background: white;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        transition: all 0.2s;
    }
    .btn-back-kerani:hover {
        color: var(--accent);
        border-color: var(--accent);
        background: #eff6ff;
    }

    .profile-banner-kerani {
        background: linear-gradient(135deg, var(--primary-start) 0%, var(--primary-end) 100%);
        border-radius: 20px;
        padding: 30px 36px;
        color: white;
        margin-bottom: 28px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12);
        display: flex;
        align-items: center;
        gap: 24px;
        position: relative;
        overflow: hidden;
    }

    .profile-banner-kerani::after {
        content: '';
        position: absolute;
        top: -60px;
        right: -40px;
        width: 220px;
        height: 220px;
        background: rgba(255, 255, 255, 0.04);
        border-radius: 50%;
    }

    .profile-avatar-big {
        width: 80px;
        height: 80px;
        border-radius: 22px;
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 34px;
        font-weight: 800;
        border: 3px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 8px 20px rgba(37, 99, 235, 0.35);
        flex-shrink: 0;
        position: relative;
        z-index: 1;
    }

    .profile-banner-info {
        flex: 1;
        position: relative;
        z-index: 1;
    }

    .profile-banner-info h1 {
        margin: 0 0 6px 0;
        font-size: 24px;
        font-weight: 800;
        letter-spacing: -0.3px;
        color: white;
    }

    .profile-meta-chips {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 8px;
    }

    .chip-item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
    }

    .chip-nik {
        background: rgba(255, 255, 255, 0.15);
        color: #f1f5f9;
        font-family: monospace;
        letter-spacing: 0.5px;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .chip-role {
        background: #3b82f6;
        color: white;
    }

    .chip-afd {
        background: #10b981;
        color: white;
    }

    /* Tab Navigasi */
    .tab-profile-nav {
        display: flex;
        background: white;
        border-radius: 14px;
        padding: 6px;
        gap: 6px;
        margin-bottom: 24px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
        max-width: 480px;
    }

    .tab-profile-btn {
        flex: 1;
        padding: 11px 18px;
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

    .tab-profile-btn.active {
        background: var(--accent);
        color: white;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
    }

    .tab-profile-btn:hover:not(.active) {
        background: #f8fafc;
        color: #0f172a;
    }

    /* Container Form */
    .profile-card-form {
        background: white;
        border-radius: 18px;
        padding: 30px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        border: 1px solid #e2e8f0;
        max-width: 760px;
    }

    .form-header-title {
        font-size: 17px;
        font-weight: 800;
        color: var(--text-main);
        margin: 0 0 22px 0;
        display: flex;
        align-items: center;
        gap: 10px;
        padding-bottom: 14px;
        border-bottom: 1.5px solid #f1f5f9;
    }

    .form-group-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 18px;
        margin-bottom: 18px;
    }

    .form-group-kerani {
        margin-bottom: 18px;
    }

    .form-label-kerani {
        display: block;
        font-weight: 700;
        font-size: 13px;
        color: #334155;
        margin-bottom: 7px;
    }

    .form-control-kerani {
        width: 100%;
        padding: 12px 16px;
        border-radius: 10px;
        border: 1.5px solid #cbd5e1;
        background-color: #f8fafc;
        font-size: 14px;
        font-family: inherit;
        color: #0f172a;
        transition: all 0.2s;
        outline: none;
    }

    .form-control-kerani:focus {
        border-color: var(--accent);
        background-color: #ffffff;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
    }

    .form-control-kerani[readonly] {
        background-color: #f1f5f9;
        color: #64748b;
        cursor: not-allowed;
        border-color: #e2e8f0;
    }

    .readonly-subtext {
        font-size: 11.5px;
        color: #94a3b8;
        margin-top: 5px;
    }

    .btn-submit-kerani {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        background: var(--accent);
        color: white;
        padding: 13px 28px;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 700;
        border: none;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
        transition: all 0.2s;
    }

    .btn-submit-kerani:hover {
        background: #2563eb;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(59, 130, 246, 0.35);
    }

    .pwd-input-wrap {
        position: relative;
    }

    .pwd-toggle-icon {
        position: absolute;
        right: 14px;
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

    .pwd-toggle-icon:hover {
        color: #475569;
    }

    .security-box {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 22px;
        font-size: 13px;
        color: #1e40af;
        line-height: 1.5;
        display: flex;
        gap: 12px;
        align-items: flex-start;
    }
</style>

<!-- Tombol Kembali -->
<a href="index.php" class="btn-back-kerani">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="15 18 9 12 15 6"></polyline>
    </svg>
    Kembali ke Dashboard
</a>

<!-- Header Card Biodata -->
<div class="profile-banner-kerani">
    <div class="profile-avatar-big">
        <?= strtoupper(substr($me['name'] ?? 'K', 0, 1)) ?>
    </div>
    <div class="profile-banner-info">
        <h1><?= htmlspecialchars($me['name'] ?? 'Kerani') ?></h1>
        <div style="font-size: 13px; opacity: 0.85;">
            Pengguna Resmi Portal Kerani Operasional PT Damai Jaya Lestari
        </div>
        <div class="profile-meta-chips">
            <span class="chip-item chip-nik"><i class="fa-solid fa-id-card"></i> <?= htmlspecialchars($me['nik'] ?? '-') ?></span>
            <span class="chip-item chip-role"><i class="fa-solid fa-user-check"></i> Kerani</span>
            <span class="chip-item chip-afd"><i class="fa-solid fa-map-pin"></i> <?= htmlspecialchars($me['afdeling'] ?: 'Semua Afdeling') ?></span>
        </div>
    </div>
</div>

<!-- Tab Switcher -->
<div class="tab-profile-nav">
    <button type="button" class="tab-profile-btn active" id="btnTabBiodata" onclick="pindahTab('biodata')">
        <i class="fa-solid fa-address-card"></i>
        Biodata Diri
    </button>
    <button type="button" class="tab-profile-btn" id="btnTabPassword" onclick="pindahTab('password')">
        <i class="fa-solid fa-lock"></i>
        Ganti Password
    </button>
</div>

<!-- SECTION 1: BIODATA -->
<div id="tabContentBiodata" class="profile-card-form">
    <h2 class="form-header-title">
        <i class="fa-solid fa-user-pen" style="color: var(--accent);"></i>
        Informasi Lengkap &amp; Perbarui Biodata
    </h2>

    <form method="POST">
        <div class="form-group-row">
            <div class="form-group-kerani">
                <label class="form-label-kerani">Nomor Induk Karyawan (NIK)</label>
                <input type="text" class="form-control-kerani" value="<?= htmlspecialchars($me['nik'] ?? '-') ?>" readonly>
                <div class="readonly-subtext">NIK ditetapkan oleh perusahaan dan bersifat permanen.</div>
            </div>

            <div class="form-group-kerani">
                <label class="form-label-kerani">Afdeling Penempatan</label>
                <input type="text" class="form-control-kerani" value="<?= htmlspecialchars($me['afdeling'] ?: '-') ?>" readonly>
                <div class="readonly-subtext">Wilayah kerja operasional Kerani yang terdaftar di sistem.</div>
            </div>
        </div>

        <div class="form-group-row">
            <div class="form-group-kerani">
                <label class="form-label-kerani">Nama Lengkap <span style="color:#ef4444;">*</span></label>
                <input type="text" name="nama" class="form-control-kerani" value="<?= htmlspecialchars($me['name'] ?? '') ?>" required placeholder="Masukkan nama lengkap">
            </div>

            <div class="form-group-kerani">
                <label class="form-label-kerani">Jabatan</label>
                <input type="text" class="form-control-kerani" value="<?= htmlspecialchars($me['jabatan'] ?: 'Kerani') ?>" readonly>
                <div class="readonly-subtext">Hak akses operasional Kerani.</div>
            </div>
        </div>

        <div class="form-group-row">
            <div class="form-group-kerani">
                <label class="form-label-kerani">Jenis Kelamin</label>
                <select name="jenis_kelamin" class="form-control-kerani">
                    <option value="">-- Pilih Jenis Kelamin --</option>
                    <option value="Laki-laki" <?= ($me['jenis_kelamin'] ?? '') === 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                    <option value="Perempuan" <?= ($me['jenis_kelamin'] ?? '') === 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                </select>
            </div>

            <div class="form-group-kerani">
                <label class="form-label-kerani">Nomor WhatsApp / HP Aktif <span style="color:#ef4444;">*</span></label>
                <input type="text" name="no_hp" class="form-control-kerani" value="<?= htmlspecialchars($me['no_hp'] ?? '') ?>" required placeholder="Contoh: 082212345678">
                <div class="readonly-subtext">Digunakan untuk notifikasi validasi laporan dari Keuangan.</div>
            </div>
        </div>

        <div class="form-group-kerani">
            <label class="form-label-kerani">Alamat Email (Username Login) <span style="color:#ef4444;">*</span></label>
            <input type="email" name="email" class="form-control-kerani" value="<?= htmlspecialchars($me['email'] ?? '') ?>" required placeholder="email@contoh.com">
        </div>

        <div class="form-group-kerani">
            <label class="form-label-kerani">Alamat Domisili / Tempat Tinggal</label>
            <textarea name="alamat" class="form-control-kerani" rows="3" placeholder="Masukkan alamat tempat tinggal Anda (opsional)"><?= htmlspecialchars($me['alamat'] ?? '') ?></textarea>
        </div>

        <div style="margin-top: 24px;">
            <button type="submit" name="update_biodata" class="btn-submit-kerani">
                <i class="fa-solid fa-floppy-disk"></i>
                Simpan Perubahan Biodata
            </button>
        </div>
    </form>
</div>

<!-- SECTION 2: PASSWORD -->
<div id="tabContentPassword" class="profile-card-form" style="display: none;">
    <h2 class="form-header-title">
        <i class="fa-solid fa-shield-halved" style="color: var(--accent);"></i>
        Keamanan Akun &amp; Ganti Password
    </h2>

    <div class="security-box">
        <i class="fa-solid fa-circle-info" style="font-size: 20px; flex-shrink: 0; margin-top: 2px;"></i>
        <div>
            <strong>Privasi &amp; Keamanan Kata Sandi:</strong><br>
            Kata sandi Anda dienkripsi secara aman. Admin maupun staf lain tidak dapat melihat password Anda. Silakan ganti kata sandi secara berkala untuk menjaga keamanan akun Anda.
        </div>
    </div>

    <form method="POST">
        <div class="form-group-kerani">
            <label class="form-label-kerani">Password Saat Ini (Password Lama) <span style="color:#ef4444;">*</span></label>
            <div class="pwd-input-wrap">
                <input type="password" name="password_lama" id="pwd_old" class="form-control-kerani" required placeholder="Masukkan password saat ini">
                <button type="button" class="pwd-toggle-icon" onclick="togglePasswordVisibility('pwd_old')">
                    <i class="fa-regular fa-eye" id="pwd_old_icon"></i>
                </button>
            </div>
            <div class="readonly-subtext">Jika password baru saja direset oleh admin, password defaultnya adalah <strong>123456</strong>.</div>
        </div>

        <div class="form-group-row">
            <div class="form-group-kerani">
                <label class="form-label-kerani">Password Baru (Minimal 6 Karakter) <span style="color:#ef4444;">*</span></label>
                <div class="pwd-input-wrap">
                    <input type="password" name="password_baru" id="pwd_new" class="form-control-kerani" required minlength="6" placeholder="Masukkan password baru">
                    <button type="button" class="pwd-toggle-icon" onclick="togglePasswordVisibility('pwd_new')">
                        <i class="fa-regular fa-eye" id="pwd_new_icon"></i>
                    </button>
                </div>
            </div>

            <div class="form-group-kerani">
                <label class="form-label-kerani">Ulangi Password Baru <span style="color:#ef4444;">*</span></label>
                <div class="pwd-input-wrap">
                    <input type="password" name="password_konfirmasi" id="pwd_confirm" class="form-control-kerani" required minlength="6" placeholder="Ketik ulang password baru">
                    <button type="button" class="pwd-toggle-icon" onclick="togglePasswordVisibility('pwd_confirm')">
                        <i class="fa-regular fa-eye" id="pwd_confirm_icon"></i>
                    </button>
                </div>
            </div>
        </div>

        <div style="margin-top: 24px;">
            <button type="submit" name="ubah_password" class="btn-submit-kerani">
                <i class="fa-solid fa-key"></i>
                Perbarui Password Sekarang
            </button>
        </div>
    </form>
</div>

<script>
function pindahTab(namaTab) {
    const btnBio = document.getElementById('btnTabBiodata');
    const btnPwd = document.getElementById('btnTabPassword');
    const contentBio = document.getElementById('tabContentBiodata');
    const contentPwd = document.getElementById('tabContentPassword');

    if (namaTab === 'biodata') {
        btnBio.classList.add('active');
        btnPwd.classList.remove('active');
        contentBio.style.display = 'block';
        contentPwd.style.display = 'none';
    } else {
        btnPwd.classList.add('active');
        btnBio.classList.remove('active');
        contentBio.style.display = 'none';
        contentPwd.style.display = 'block';
    }
}

function togglePasswordVisibility(fieldId) {
    const input = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '_icon');
    if (!input || !icon) return;

    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Buka tab password otomatis jika ada parameter URL ?tab=password
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('tab') === 'password') {
    pindahTab('password');
}
</script>

<?php include 'templates/footer.php'; ?>
