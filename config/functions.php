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

// Fungsi auto alpha dengan backfill tanggal lampau & libur hari Minggu
function checkAndSetAlpha($conn, $bulan = null, $tahun = null) {
    if (!$conn) return;

    $now_time = date('H:i:s');
    $today = date('Y-m-d');

    // Koreksi otomatis jika ada data yang sudah absen (waktu_masuk ada) tapi sempat tersimpan sebagai 'alpha'
    mysqli_query($conn, "UPDATE absensis SET status_kehadiran = 'hadir' WHERE waktu_masuk IS NOT NULL AND status_kehadiran = 'alpha'");

    // Tentukan rentang tanggal yang diperiksa
    if ($bulan !== null && $tahun !== null) {
        $bulan_str = str_pad((int)$bulan, 2, '0', STR_PAD_LEFT);
        $tahun_str = (int)$tahun;
        $start_date = "$tahun_str-$bulan_str-01";
        $days_in_month = (int)date('t', strtotime($start_date));
        $end_date = "$tahun_str-$bulan_str-" . str_pad($days_in_month, 2, '0', STR_PAD_LEFT);

        // Jangan proses masa depan
        if ($end_date > $today) {
            $end_date = $today;
        }
        if ($start_date > $today) {
            return;
        }
    } else {
        // Default: Periksa dari awal bulan berjalan sampai hari ini
        $start_date = date('Y-m-01');
        $end_date = $today;
    }

    $current = strtotime($start_date);
    $end = strtotime($end_date);

    while ($current <= $end) {
        $tgl = date('Y-m-d', $current);
        $dow = (int)date('N', $current); // 1 = Senin, ..., 7 = Minggu

        // Hari Minggu dihitung LIBUR, lewati
        if ($dow === 7) {
            $current = strtotime('+1 day', $current);
            continue;
        }

        // Jika hari ini dan belum lewat jam 15:00, lewati (karyawan masih bisa absen)
        if ($tgl === $today && $now_time <= '15:00:00') {
            $current = strtotime('+1 day', $current);
            continue;
        }

        // Masukkan Alpha untuk user operasional yang belum absen dan tidak ada perizinan disetujui
        $query = "
            INSERT INTO absensis (user_id, tanggal, waktu_masuk, status_kehadiran)
            SELECT u.id, '$tgl', NULL, 'alpha'
            FROM users u
            WHERE u.role IN ('karyawan', 'mandor', 'pengawas', 'kerani')
              AND (u.created_at IS NULL OR DATE(u.created_at) <= '$tgl')
              AND u.id NOT IN (
                  SELECT a.user_id FROM absensis a WHERE a.tanggal = '$tgl'
              )
              AND u.id NOT IN (
                  SELECT p.user_id FROM perizinan p WHERE p.tanggal_izin = '$tgl' AND p.status = 'disetujui'
              )
        ";
        mysqli_query($conn, $query);

        $current = strtotime('+1 day', $current);
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
