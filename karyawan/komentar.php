<?php
/**
 * karyawan/komentar.php
 * Halaman publik untuk karyawan submit komentar/komplain via link WA
 * Bisa diakses TANPA LOGIN menggunakan token keamanan
 */
require '../config/config.php';

$id    = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

// Validasi token
$expected_token = md5($id . APP_SECRET);
if (!$id || $token !== $expected_token) {
    http_response_code(403);
    die("
    <div style='font-family:sans-serif;text-align:center;margin-top:80px;color:#dc2626;'>
        <div style='font-size:48px;margin-bottom:16px;'>&#x26D4;</div>
        <h2>Akses Ditolak</h2>
        <p>Link tidak valid atau sudah kadaluarsa.</p>
    </div>");
}

// Ambil detail logbook
$q = mysqli_query($conn, "
    SELECT lk.*, u.name AS nama_karyawan, u.no_hp AS hp_karyawan,
           m.name AS nama_mandor
    FROM logbook_kinerja lk
    LEFT JOIN users u ON lk.user_id = u.id
    LEFT JOIN users m ON lk.mandor_id = m.id
    WHERE lk.id = $id
");
if (!$q || mysqli_num_rows($q) == 0) {
    die("<div style='font-family:sans-serif;text-align:center;margin-top:80px;'><h2>Data tidak ditemukan.</h2></div>");
}
$logbook = mysqli_fetch_assoc($q);

// Handle POST: simpan komentar
$pesan_sukses = '';
$pesan_error  = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_komentar'])) {
    $komentar = trim(mysqli_real_escape_string($conn, $_POST['komentar'] ?? ''));
    if (empty($komentar)) {
        $pesan_error = 'Komentar tidak boleh kosong.';
    } else {
        $uid = $logbook['user_id'];
        mysqli_query($conn, "INSERT INTO komentar_objek_kerja (logbook_id, user_id, komentar, dibaca) 
                              VALUES ($id, $uid, '$komentar', 0)");

        // Kirim WA ke Kerani afdeling karyawan ini
        $afd_q = mysqli_query($conn, "SELECT afdeling FROM users WHERE id=$uid LIMIT 1");
        $afd_row = mysqli_fetch_assoc($afd_q);
        $afdeling = $afd_row['afdeling'] ?? '';

        $q_kerani = mysqli_query($conn, "SELECT no_hp, name FROM users WHERE role='kerani'" .
            (!empty($afdeling) ? " AND afdeling='" . mysqli_real_escape_string($conn, $afdeling) . "'" : "") . " LIMIT 1");

        if ($q_kerani && mysqli_num_rows($q_kerani) > 0) {
            $kerani = mysqli_fetch_assoc($q_kerani);
            if (!empty($kerani['no_hp'])) {
                $tgl_fmt = date('d/m/Y', strtotime($logbook['tanggal']));
                $link_ok  = BASE_URL . 'kerani/objek_kerja.php?tanggal=' . urlencode($logbook['tanggal']);
                $pesan_wa  = "Komentar/Komplain dari Karyawan\n\n";
                $pesan_wa .= "Dari: *{$logbook['nama_karyawan']}*\n";
                $pesan_wa .= "Tanggal Tugas: *{$tgl_fmt}*\n";
                $pesan_wa .= "Objek Kerja: *{$logbook['objek_kerja']}*\n";
                $pesan_wa .= "Blok: *{$logbook['blok']}*\n\n";
                $pesan_wa .= "*Pesan:*\n_{$komentar}_\n\n";
                $pesan_wa .= "Tindak lanjuti di: {$link_ok}\n";
                $pesan_wa .= "_Sistem PT DJL_";
                sendWA($kerani['no_hp'], $pesan_wa);
            }
        }
        $pesan_sukses = 'Komentar Anda berhasil dikirim ke Kerani! Terima kasih.';
    }
}

$tgl_display = date('d F Y', strtotime($logbook['tanggal']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Komentar Objek Kerja - PT DJL</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f1f5f9; min-height: 100vh; display: flex; align-items: flex-start; justify-content: center; padding: 24px 16px 40px; }
        .container { width: 100%; max-width: 480px; }
        .logo { text-align: center; margin-bottom: 24px; }
        .logo-title { font-size: 20px; font-weight: 800; color: #1e40af; }
        .logo-sub { font-size: 12px; color: #64748b; font-weight: 600; }
        .card { background: white; border-radius: 20px; box-shadow: 0 4px 24px rgba(0,0,0,.08); overflow: hidden; margin-bottom: 16px; }
        .card-header { background: linear-gradient(135deg, #1e40af, #3b82f6); padding: 20px; color: white; }
        .card-header h2 { font-size: 16px; font-weight: 800; margin-bottom: 4px; }
        .card-header p { font-size: 13px; opacity: .85; }
        .detail-row { display: flex; gap: 12px; align-items: flex-start; padding: 12px 20px; border-bottom: 1px solid #f1f5f9; }
        .detail-row:last-child { border-bottom: none; }
        .detail-icon { font-size: 18px; flex-shrink: 0; margin-top: 1px; }
        .detail-label { font-size: 10px; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; }
        .detail-value { font-size: 14px; font-weight: 700; color: #1e293b; }
        .form-card { background: white; border-radius: 20px; box-shadow: 0 4px 24px rgba(0,0,0,.08); padding: 20px; }
        .form-card h3 { font-size: 15px; font-weight: 800; color: #1e293b; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
        textarea { width: 100%; padding: 12px 14px; border: 1.5px solid #e2e8f0; border-radius: 12px; font-size: 14px; font-family: inherit; resize: none; min-height: 120px; outline: none; color: #1e293b; background: #f8fafc; transition: border-color .2s; }
        textarea:focus { border-color: #3b82f6; background: white; }
        .btn-submit { margin-top: 12px; width: 100%; padding: 14px; background: linear-gradient(135deg, #1e40af, #3b82f6); color: white; border: none; border-radius: 12px; font-size: 15px; font-weight: 800; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: opacity .2s, transform .15s; }
        .btn-submit:active { transform: scale(0.98); opacity: 0.9; }
        .alert-success { background: #f0fdf4; border: 1.5px solid #86efac; border-radius: 12px; padding: 14px 16px; color: #16a34a; font-weight: 700; font-size: 14px; margin-bottom: 16px; }
        .alert-error { background: #fef2f2; border: 1.5px solid #fca5a5; border-radius: 12px; padding: 14px 16px; color: #dc2626; font-weight: 700; font-size: 14px; margin-bottom: 16px; }
        .riwayat-item { background: #f8fafc; border-radius: 10px; padding: 12px 14px; margin-bottom: 8px; border-left: 3px solid #3b82f6; }
        .riwayat-text { font-size: 13px; color: #334155; font-weight: 600; line-height: 1.5; }
        .riwayat-time { font-size: 11px; color: #94a3b8; margin-top: 4px; }
    </style>
</head>
<body>
<div class="container">
    <div class="logo">
        <div class="logo-title">🌴 PT DAMAI JAYA LESTARI</div>
        <div class="logo-sub">Sistem Informasi Karyawan</div>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>📋 Detail Penugasan</h2>
            <p>Tanggal: <?= $tgl_display ?></p>
        </div>
        <div class="detail-row">
            <div class="detail-icon">👤</div>
            <div><div class="detail-label">Nama Karyawan</div><div class="detail-value"><?= htmlspecialchars($logbook['nama_karyawan']) ?></div></div>
        </div>
        <div class="detail-row">
            <div class="detail-icon">🔧</div>
            <div><div class="detail-label">Objek Kerja</div><div class="detail-value"><?= htmlspecialchars($logbook['objek_kerja']) ?></div></div>
        </div>
        <div class="detail-row">
            <div class="detail-icon">📍</div>
            <div><div class="detail-label">Blok & Luas</div><div class="detail-value"><?= htmlspecialchars($logbook['blok'] ?? '-') ?> &bull; <?= htmlspecialchars($logbook['luas_ha'] ?? '-') ?> Ha</div></div>
        </div>
        <div class="detail-row">
            <div class="detail-icon">👷</div>
            <div><div class="detail-label">Mandor</div><div class="detail-value"><?= htmlspecialchars($logbook['nama_mandor'] ?? '-') ?></div></div>
        </div>
    </div>

    <?php if ($pesan_sukses): ?><div class="alert-success">✅ <?= htmlspecialchars($pesan_sukses) ?></div><?php endif; ?>
    <?php if ($pesan_error): ?><div class="alert-error">⚠️ <?= htmlspecialchars($pesan_error) ?></div><?php endif; ?>

    <?php
    $q_riwayat = mysqli_query($conn, "SELECT komentar, created_at FROM komentar_objek_kerja WHERE logbook_id=$id ORDER BY created_at DESC LIMIT 5");
    $riwayat = [];
    while ($r = mysqli_fetch_assoc($q_riwayat)) $riwayat[] = $r;
    if (!empty($riwayat)): ?>
    <div class="form-card" style="margin-bottom:16px;">
        <h3>📝 Riwayat Komentar Anda</h3>
        <?php foreach ($riwayat as $r): ?>
        <div class="riwayat-item">
            <div class="riwayat-text"><?= htmlspecialchars($r['komentar']) ?></div>
            <div class="riwayat-time"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="form-card">
        <h3>💬 Tulis Komentar / Komplain</h3>
        <form method="POST">
            <textarea name="komentar" placeholder="Contoh: Saya keberatan dengan penugasan di blok ini karena... / Saya ingin tukar tugas..."><?= isset($_POST['komentar']) ? htmlspecialchars($_POST['komentar']) : '' ?></textarea>
            <button type="submit" name="submit_komentar" class="btn-submit">💬 Kirim ke Kerani</button>
        </form>
    </div>

    <p style="text-align:center;font-size:11px;color:#94a3b8;margin-top:16px;">
        Komentar akan diteruskan ke WhatsApp Kerani.<br>
        <em>Sistem Informasi Karyawan PT Damai Jaya Lestari</em>
    </p>
</div>
</body>
</html>
