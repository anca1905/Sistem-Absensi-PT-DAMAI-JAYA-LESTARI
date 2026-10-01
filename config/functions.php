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

    $num = preg_replace('/[^0-9]/', '', $afdeling);
    $afdeling_safe = mysqli_real_escape_string($conn, $afdeling);

    if ($num !== '') {
        $clean_afdeling = $num;
        $label_afdeling = "Afdeling $num";
        $where_afd = "(afdeling = '$afdeling_safe' OR afdeling = 'Afd $num' OR afdeling = 'Afdeling $num' OR afdeling = '$num' OR afdeling = 'Afd. $num' OR afdeling LIKE '% $num' OR afdeling LIKE '%$num%')";
    } elseif ($afdeling !== '') {
        $clean_afdeling = (stripos($afdeling, 'Afd') === 0 ? trim(substr($afdeling, 3)) : $afdeling);
        $label_afdeling = (stripos($afdeling, 'Afd') !== false ? $afdeling : "Afdeling $afdeling");
        $where_afd = "(afdeling = '$afdeling_safe' OR afdeling LIKE '%$afdeling_safe%')";
    } else {
        $clean_afdeling = '';
        $label_afdeling = "Afdeling";
        $where_afd = "1=0";
    }

    $result = [
        'afdeling' => $clean_afdeling,
        'label_afdeling' => $label_afdeling,
        'teks_afdeling' => $label_afdeling,
        'kerani' => '( ................................... )',
        'pengawas' => '( ................................... )',
    ];

    // Cek session pengawas
    $sess_num = preg_replace('/[^0-9]/', '', $_SESSION['afdeling'] ?? '');
    if (($_SESSION['role'] ?? '') === 'pengawas' && !empty($_SESSION['nama'])) {
        if (empty($_SESSION['afdeling']) || ($num !== '' && $sess_num === $num) || trim($_SESSION['afdeling']) === $afdeling || stripos($_SESSION['afdeling'], $afdeling) !== false) {
            $result['pengawas'] = $_SESSION['nama'];
        }
    }

    // Cek session kerani
    if (($_SESSION['role'] ?? '') === 'kerani' && !empty($_SESSION['nama'])) {
        if (empty($_SESSION['afdeling']) || ($num !== '' && $sess_num === $num) || trim($_SESSION['afdeling']) === $afdeling || stripos($_SESSION['afdeling'], $afdeling) !== false) {
            $result['kerani'] = $_SESSION['nama'];
        }
    }

    // Cari pengawas di DB jika belum didapat dari session
    if ($result['pengawas'] === '( ................................... )' && $where_afd !== "1=0") {
        $q_pengawas = mysqli_query($conn, "SELECT name FROM users WHERE (role='pengawas' OR jabatan LIKE '%pengawas%') AND $where_afd ORDER BY (role='pengawas') DESC, id ASC LIMIT 1");
        if ($q_pengawas && ($data_pengawas = mysqli_fetch_assoc($q_pengawas))) {
            $result['pengawas'] = $data_pengawas['name'];
        }
    }

    // Cari kerani di DB jika belum didapat dari session
    if ($result['kerani'] === '( ................................... )' && $where_afd !== "1=0") {
        $q_kerani = mysqli_query($conn, "SELECT name FROM users WHERE (role='kerani' OR jabatan LIKE '%kerani%') AND $where_afd ORDER BY (role='kerani') DESC, id ASC LIMIT 1");
        if ($q_kerani && ($data_kerani = mysqli_fetch_assoc($q_kerani))) {
            $result['kerani'] = $data_kerani['name'];
        }
    }

    return $result;
}


/**
 * Fungsi terpusat: ubah nilai status_kehadiran dari DB jadi info tampilan.
 * Kode: H = Hadir, T = Terlambat, A = Alpha, I = Izin, S = Sakit, C = Cuti
 */
function getStatusInfo($status) {
    $s = strtolower(trim((string)$status));
    switch ($s) {
        case 'hadir':
        case 'tepat_waktu':
            return ['kode' => 'H', 'label' => 'Hadir',    'class' => 'status-h'];
        case 'terlambat':
        case 'alpha':
        case 'alpa':
        case 'alfa':
            return ['kode' => 'A', 'label' => 'Alpha',    'class' => 'status-a'];
        case 'izin':
            return ['kode' => 'I', 'label' => 'Izin',     'class' => 'status-i'];
        case 'sakit':
            return ['kode' => 'S', 'label' => 'Sakit',    'class' => 'status-s'];
        case 'cuti':
            return ['kode' => 'C', 'label' => 'Cuti',     'class' => 'status-c'];
        default:
            return ['kode' => '', 'label' => '', 'class' => ''];
    }
}

function isHadir($status) {
    $s = strtolower(trim((string)$status));
    return in_array($s, ['hadir', 'tepat_waktu']);
}

function getStatusBadge($status) {
    if ($status === null || $status === '') {
        return '<span style="color:#cbd5e1;">-</span>';
    }
    $info = getStatusInfo($status);
    if ($info['kode'] === '') {
        return '<span style="color:#cbd5e1;">-</span>';
    }
    return '<span class="status-badge ' . $info['class'] . '">' . $info['kode'] . '</span>';
}
