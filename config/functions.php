<?php
// Fungsi untuk mengecek menu aktif di sidebar
// Fungsi cek menu aktif (Support File & Folder)
function is_active($uri_segment)
{
    // Ambil URL lengkap saat ini (misal: /absensi/admin/report/index.php)
    $current_path = $_SERVER['PHP_SELF'];

    // Cek apakah kata '$uri_segment' (misal: 'report') ada di dalam URL
    if (strpos($current_path, $uri_segment) !== false) {
        return 'active';
    }
    return '';
}

// Fungsi format tanggal Indonesia (Opsional, biar keren)
function tgl_indo($tanggal)
{
    $bulan = array(
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    );
    $pecahkan = explode('-', $tanggal);
    return $pecahkan[2] . ' ' . $bulan[(int)$pecahkan[1]] . ' ' . $pecahkan[0];
}

// Fungsi Redirect aman (dengan exit)
function redirect($url)
{
    header("Location: $url");
    exit;
}

// Fungsi auto alpha
function checkAndSetAlpha($conn) {
    $today = date('Y-m-d');
    $time_now = date('H:i:s');
    
    // Jika sudah lewat jam 15:00
    if ($time_now > '15:00:00') {
        // Ambil semua user yang belum ada di tabel absensis hari ini
        $query = "
            SELECT id FROM users 
            WHERE id NOT IN (
                SELECT user_id FROM absensis WHERE tanggal = '$today'
            )
        ";
        $result = mysqli_query($conn, $query);
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $user_id = $row['id'];
                // Insert status alpha
                mysqli_query($conn, "INSERT INTO absensis (user_id, tanggal, waktu_masuk, status_kehadiran) VALUES ('$user_id', '$today', NULL, 'alpha')");
            }
        }
    }
}
