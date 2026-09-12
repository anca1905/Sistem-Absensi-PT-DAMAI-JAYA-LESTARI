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

/**
 * Mengambil penandatangan laporan berdasarkan afdeling.
 * Jika laporan dicetak oleh Kerani pada afdeling tersebut, gunakan nama Kerani
 * yang sedang login agar tanda tangan selalu sesuai akun aktif.
 */
function getReportSignatories($conn, $afdeling = '')
{
    $afdeling = trim((string) $afdeling);
    if ($afdeling === '' && !empty($_SESSION['afdeling'])) {
        $afdeling = trim((string) $_SESSION['afdeling']);
    }

    $result = [
        'afdeling' => $afdeling,
        'kerani' => '-',
        'pengawas' => '-',
    ];

    if ($afdeling === '') {
        return $result;
    }

    if (($_SESSION['role'] ?? '') === 'kerani'
        && !empty($_SESSION['nama'])
        && trim((string) ($_SESSION['afdeling'] ?? '')) === $afdeling) {
        $result['kerani'] = $_SESSION['nama'];
    }

    $afdeling_safe = mysqli_real_escape_string($conn, $afdeling);
    if ($result['kerani'] === '-') {
        $q_kerani = mysqli_query($conn, "SELECT name FROM users WHERE (role='kerani' OR jabatan='kerani') AND afdeling='$afdeling_safe' ORDER BY id ASC LIMIT 1");
        if ($q_kerani && ($data_kerani = mysqli_fetch_assoc($q_kerani))) {
            $result['kerani'] = $data_kerani['name'];
        }
    }

    $q_pengawas = mysqli_query($conn, "SELECT name FROM users WHERE (role='pengawas' OR jabatan='pengawas') AND afdeling='$afdeling_safe' ORDER BY id ASC LIMIT 1");
    if ($q_pengawas && ($data_pengawas = mysqli_fetch_assoc($q_pengawas))) {
        $result['pengawas'] = $data_pengawas['name'];
    }

    return $result;
}
