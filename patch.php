<?php
$f = "keuangan/laporan_individu.php";
$c = file_get_contents($f);
$c = str_replace(
    '$uid = isset($_GET[\'user_id\']) ? (int)$_GET[\'user_id\'] : 0;
$penandatangan = getReportSignatories($conn, $_SESSION[\'afdeling\'] ?? \'\');
if ($uid === 0) {
    header("Location: lap_keseluruhan.php");
    exit;
}
$q_user = mysqli_query($conn, "SELECT name FROM users WHERE id=$uid AND (role IN (\'karyawan\', \'kerani\', \'mandor\', \'pengawas\') OR jabatan IN (\'karyawan\', \'kerani\', \'mandor\', \'pengawas\'))");
$u_data = mysqli_fetch_assoc($q_user);
$nama_karyawan = $u_data ? htmlspecialchars($u_data[\'name\']) : \'Tidak Ditemukan\';',
    '$uid = isset($_GET[\'user_id\']) ? (int)$_GET[\'user_id\'] : 0;
if ($uid === 0) {
    header("Location: lap_keseluruhan.php");
    exit;
}
$q_user = mysqli_query($conn, "SELECT name, afdeling FROM users WHERE id=$uid AND (role IN (\'karyawan\', \'kerani\', \'mandor\', \'pengawas\') OR jabatan IN (\'karyawan\', \'kerani\', \'mandor\', \'pengawas\'))");
$u_data = mysqli_fetch_assoc($q_user);
$nama_karyawan = $u_data ? htmlspecialchars($u_data[\'name\']) : \'Tidak Ditemukan\';
$afdeling_karyawan = $u_data ? $u_data[\'afdeling\'] : \'\';
$penandatangan = getReportSignatories($conn, $afdeling_karyawan);',
    $c
);
file_put_contents($f, $c);
