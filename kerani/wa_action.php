<?php
// kerani/wa_action.php
require '../config/config.php';

// Validasi parameter
if (!isset($_GET['id']) || !isset($_GET['action']) || !isset($_GET['token'])) {
    die("Akses ditolak. Parameter tidak lengkap.");
}

$id = (int)$_GET['id'];
$action = $_GET['action']; // 'setuju' atau 'tolak'
$token = $_GET['token'];

// Verifikasi token (MD5 dari ID + Secret Key)
$secret_key = "DJL_AMANDA_SECRET";
$expected_token = md5($id . $secret_key);

if ($token !== $expected_token) {
    die("Akses ditolak. Token tidak valid.");
}

// Cek apakah data perizinan ada dan ambil info user
$q_cek = mysqli_query($conn, "SELECT p.*, u.name, u.no_hp FROM perizinan p JOIN users u ON p.user_id = u.id WHERE p.id = $id");
if (mysqli_num_rows($q_cek) === 0) {
    die("Data pengajuan tidak ditemukan.");
}

$row = mysqli_fetch_assoc($q_cek);

// Cegah validasi ulang jika sudah divalidasi
if ($row['status'] !== 'pending') {
    die("Pengajuan ini sudah divalidasi dengan status: <b>" . strtoupper($row['status']) . "</b>.");
}

// Update status
$status_db = ($action == 'setuju') ? 'disetujui' : 'ditolak';
$q_update = mysqli_query($conn, "UPDATE perizinan SET status='$status_db' WHERE id=$id");

if ($q_update) {
    // Kirim WA notifikasi ke pemohon
    if (!empty($row['no_hp'])) {
        $pesan = "Halo *{$row['name']}*,\n\nPengajuan *{$row['jenis']}* Anda untuk tanggal *{$row['tanggal_izin']}* telah *".strtoupper($status_db)."* oleh Kerani.\n\n_Ini adalah pesan otomatis dari Sistem Informasi Karyawan PT DJL._";
        sendWA($row['no_hp'], $pesan);
    }

    echo "<div style='font-family: sans-serif; text-align: center; margin-top: 50px;'>";
    if ($action == 'setuju') {
        echo "<h2 style='color: #16a34a;'>✅ Pengajuan Berhasil Disetujui</h2>";
    } else {
        echo "<h2 style='color: #dc2626;'>❌ Pengajuan Berhasil Ditolak</h2>";
    }
    echo "<p>Notifikasi telah dikirimkan ke WhatsApp pemohon ({$row['name']}). Anda sudah bisa menutup halaman ini.</p>";
    echo "</div>";
} else {
    echo "Gagal memperbarui database: " . mysqli_error($conn);
}
?>
