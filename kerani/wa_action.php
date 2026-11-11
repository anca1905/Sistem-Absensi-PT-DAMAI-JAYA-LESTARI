<?php
// kerani/wa_action.php
require '../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_role = 'karyawan';

if ($id > 0) {
    $q_role = mysqli_query($conn, "SELECT u.role FROM perizinan p JOIN users u ON p.user_id = u.id WHERE p.id = $id");
    if ($d = mysqli_fetch_assoc($q_role)) {
        $user_role = $d['role'];
    }
}

// Jika pengguna sudah login sebagai kerani, langsung arahkan ke halaman validasi sistem
if (isset($_SESSION['role']) && $_SESSION['role'] === 'kerani') {
    header("Location: " . BASE_URL . "kerani/validasi_izin.php?role=" . urlencode($user_role));
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Melalui Sistem - PT DJL</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
        body {
            background-color: #f1f5f9;
            color: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .notice-card {
            background: #ffffff;
            max-width: 500px;
            width: 100%;
            border-radius: 20px;
            padding: 36px 30px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            border: 1px solid #e2e8f0;
            text-align: center;
        }
        .icon-box {
            width: 72px;
            height: 72px;
            border-radius: 20px;
            background: #eff6ff;
            color: #3b82f6;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin-bottom: 20px;
        }
        h2 {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 12px;
        }
        p {
            font-size: 14px;
            line-height: 1.6;
            color: #64748b;
            margin-bottom: 24px;
        }
        .info-pill {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 18px;
            text-align: left;
            font-size: 13px;
            color: #334155;
            margin-bottom: 24px;
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }
        .info-pill i {
            color: #3b82f6;
            margin-top: 2px;
            font-size: 16px;
        }
        .btn-portal {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 14px 20px;
            background: #0f172a;
            color: #ffffff;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            border-radius: 12px;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
        }
        .btn-portal:hover {
            background: #1e293b;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="notice-card">
        <div class="icon-box">
            <i class="fa-solid fa-shield-halved"></i>
        </div>
        <h2>Verifikasi Melalui Sistem</h2>
        <p>
            Konfirmasi persetujuan atau penolakan perizinan karyawan dilakukan sepenuhnya melalui <b>Sistem Web AMANDA</b> untuk menjaga keamanan dan keabsahan data.
        </p>
        <div class="info-pill">
            <i class="fa-solid fa-circle-info"></i>
            <div>
                WhatsApp hanya digunakan untuk <b>notifikasi pemberitahuan</b>. Silakan login menggunakan akun Kerani untuk memverifikasi perizinan.
            </div>
        </div>
        <a href="<?= BASE_URL ?>kerani/validasi_izin.php?role=<?= urlencode($user_role) ?>" class="btn-portal">
            <i class="fa-solid fa-arrow-right-to-bracket"></i>
            Buka Menu Validasi Izin Sistem
        </a>
    </div>
</body>
</html>
