<?php
// Pastikan path ini sesuai dengan struktur folder Anda
// Jika file config.php ada di folder yang sama, cukup: require 'config.php';
// Jika di dalam folder config: require 'config/config.php';
require 'config/config.php';

// session_start(); 

if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // Query Cek User
    $query = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    $data = mysqli_fetch_assoc($query);

    if ($data) {
        if (password_verify($password, $data['password'])) {
            $_SESSION['user_id'] = $data['id'];
            $_SESSION['nama']    = $data['name'];
            $_SESSION['role']    = $data['role'];
            $_SESSION['afdeling']= $data['afdeling'];

            if ($data['role'] == 'admin' || $data['role'] == 'pimpinan') {
                header("Location: admin/index.php");
            } elseif ($data['role'] == 'pengawas') {
                header("Location: pengawas/index.php");
            } elseif ($data['role'] == 'mandor') {
                header("Location: mandor/index.php");
            } elseif ($data['role'] == 'kerani') {
                header("Location: kerani/index.php");
            } else {
                header("Location: karyawan/index.php");
            }
            exit;
        }
    }
    $error = "Email atau Password tidak terdaftar.";
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Sistem Informasi Absensi - PT DJL</title>

    <style>
        /* --- RESET DASAR --- */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #eef2f7;
            /* Abu-abu kebiruan */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #333;
        }

        /* --- CONTAINER LOGIN --- */
        .login-container {
            width: 100%;
            max-width: 420px;
            background-color: #ffffff;
            border: 1px solid #d1d5db;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 4px;
            overflow: hidden;
            margin: 20px;
        }

        /* --- HEADER KOTAK --- */
        .login-header {
            background-color: #1e3a8a;
            /* Biru Tua */
            padding: 25px 20px;
            text-align: center;
            border-bottom: 4px solid #f59e0b;
            /* Aksen Kuning/Emas */
        }

        .login-header h2 {
            color: #ffffff;
            font-size: 18px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .login-header p {
            color: #dbeafe;
            font-size: 12px;
        }

        /* --- FORM AREA --- */
        .login-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 600;
            color: #4b5563;
        }

        .form-input {
            width: 100%;
            padding: 10px 12px;
            font-size: 14px;
            border: 1px solid #9ca3af;
            border-radius: 4px;
            background-color: #f9fafb;
            transition: border-color 0.2s;
        }

        .form-input:focus {
            outline: none;
            border-color: #1e3a8a;
            background-color: #ffffff;
        }

        /* --- TOMBOL LOGIN (PRIMARY) --- */
        .btn-login {
            width: 100%;
            padding: 12px;
            background-color: #1e3a8a;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn-login:hover {
            background-color: #172554;
        }

        /* --- TOMBOL SCANNER (SECONDARY) --- */
        .btn-scan {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: #10b981;
            /* Hijau Emerald agar beda dengan login */
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn-scan:hover {
            background-color: #059669;
        }

        /* --- DIVIDER (PEMISAH) --- */
        .divider {
            margin: 25px 0;
            position: relative;
            text-align: center;
        }

        .divider::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 0;
            width: 100%;
            height: 1px;
            background-color: #e5e7eb;
            z-index: 1;
        }

        .divider span {
            background-color: #fff;
            padding: 0 10px;
            color: #9ca3af;
            font-size: 12px;
            position: relative;
            z-index: 2;
        }


        /* --- PESAN ERROR --- */
        .alert-error {
            background-color: #fef2f2;
            border: 1px solid #fca5a5;
            color: #b91c1c;
            padding: 10px;
            border-radius: 4px;
            font-size: 13px;
            margin-bottom: 20px;
            text-align: center;
        }

        /* --- FOOTER --- */
        .login-footer {
            background-color: #f3f4f6;
            padding: 15px;
            text-align: center;
            font-size: 11px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
        }

        /* --- LOGO PLACEHOLDER --- */
        .logo-area {
            width: 60px;
            height: 60px;
            background-color: white;
            border-radius: 50%;
            margin: 0 auto 10px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #1e3a8a;
            font-size: 24px;
        }

        /* --- MODAL PIN SCANNER --- */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
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
            max-width: 350px;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            transform: scale(0.9);
            transition: transform 0.3s;
            text-align: center;
        }

        .modal-overlay.show .modal-box {
            transform: scale(1);
        }

        .modal-header {
            padding: 20px;
            background-color: #1e3a8a;
            color: white;
            font-weight: bold;
            font-size: 16px;
        }

        .modal-body {
            padding: 25px;
        }

        .modal-body p {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 15px;
        }

        .pin-input {
            width: 100%;
            padding: 12px;
            font-size: 18px;
            text-align: center;
            letter-spacing: 5px;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: bold;
            color: #1e293b;
        }

        .pin-input:focus {
            outline: none;
            border-color: #10b981;
        }

        .pin-error {
            color: #ef4444;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 15px;
            display: none;
        }

        .modal-footer {
            display: flex;
            border-top: 1px solid #e5e7eb;
        }

        .btn-modal {
            flex: 1;
            padding: 15px;
            border: none;
            background: #fff;
            font-weight: bold;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-cancel {
            color: #64748b;
            border-right: 1px solid #e5e7eb;
        }

        .btn-cancel:hover {
            background: #f8fafc;
        }

        .btn-submit {
            color: #10b981;
        }

        .btn-submit:hover {
            background: #ecfdf5;
        }
    </style>
</head>

<body>

    <div class="login-container">
        <div class="login-header">
            <div class="logo-area">DJL</div>
            <h2>Sistem Absensi</h2>
            <p>PT DAMAI JAYA LESTARI</p>
        </div>

        <div class="login-body">

            <?php if (isset($error)): ?>
                <div class="alert-error">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Email Pegawai</label>
                    <input type="email" name="email" class="form-input" placeholder="Contoh: nama@kantor.com" required autocomplete="email">
                </div>

                <div class="form-group">
                    <label class="form-label">Kata Sandi</label>
                    <div style="position:relative;">
                        <input type="password" id="password" name="password" class="form-input" placeholder="Masukkan kata sandi Anda" required>
                        <button type="button" onclick="togglePassword()" 
                                style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:4px;display:flex;align-items:center;"
                                title="Tampilkan/Sembunyikan Password">
                            <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" name="login" class="btn-login">
                    MASUK SISTEM
                </button>
            </form>

            <div class="divider">
                <span>MODE PENGAWAS</span>
            </div>

            <a href="#" onclick="openModal()" class="btn-scan">
                📷 BUKA SCANNER ABSENSI
            </a>

        </div>

        <div class="login-footer">
            &copy; <?php echo date('Y'); ?> Tim IT PT Damai Jaya Lestari.<br>
            Sistem Informasi Manajemen Kepegawaian.
        </div>
    </div>

    <!-- Modal PIN -->
    <div class="modal-overlay" id="pinModal">
        <div class="modal-box">
            <div class="modal-header">
                Akses Keamanan
            </div>
            <div class="modal-body">
                <p>Masukkan PIN pengawas untuk membuka scanner QR Code.</p>
                <input type="password" id="pinInput" class="pin-input" placeholder="••••••" maxlength="6">
                <div class="pin-error" id="pinError">PIN yang dimasukkan salah!</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal btn-cancel" onclick="closeModal()">Batal</button>
                <button type="button" class="btn-modal btn-submit" onclick="submitPin()">Lanjutkan</button>
            </div>
        </div>
    </div>

    <script>
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
        const modal = document.getElementById('pinModal');
        const pinInput = document.getElementById('pinInput');
        const pinError = document.getElementById('pinError');

        function openModal() {
            modal.classList.add('show');
            pinInput.value = '';
            pinError.style.display = 'none';
            setTimeout(() => pinInput.focus(), 100);
        }

        function closeModal() {
            modal.classList.remove('show');
        }

        function submitPin() {
            const pin = pinInput.value;
            // Ganti '123456' dengan PIN yang Anda inginkan
            if (pin === '123456') {
                window.location.href = "pengawas/scan_pengawas.php";
            } else {
                pinError.style.display = 'block';
                pinInput.value = '';
                pinInput.focus();
            }
        }

        // Enter key support
        pinInput.addEventListener("keyup", function(event) {
            if (event.key === "Enter") {
                submitPin();
            }
        });
    </script>
</body>

</html>