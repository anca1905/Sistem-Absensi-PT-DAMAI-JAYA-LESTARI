<?php
$f = "karyawan/laporan_keseluruhan.php";
$c = file_get_contents($f);
$pattern = '/\/\/ --- LOGIKA MENCARI NAMA KERANI BERDASARKAN AFDELING KARYAWAN ---.*?\/\/ ----------------------------------------------------------------/is';
$replacement = '// --- LOGIKA MENCARI NAMA KERANI BERDASARKAN AFDELING KARYAWAN ---
$teks_afdeling = empty($afdeling_karyawan) ? "Afdeling" : "Afd " . htmlspecialchars($afdeling_karyawan);
if (!empty($afdeling_karyawan)) {
    $penandatangan = getReportSignatories($conn, $afdeling_karyawan);
    $nama_kerani = $penandatangan["kerani"];
} else {
    $nama_kerani = "( ................................... )";
}
// ----------------------------------------------------------------';

$c = preg_replace($pattern, $replacement, $c);
file_put_contents($f, $c);
echo "Done $f\n";
