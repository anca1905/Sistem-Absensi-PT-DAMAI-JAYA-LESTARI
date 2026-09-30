<?php
$f = "keuangan/laporan_absensi.php";
$c = file_get_contents($f);

$pattern = '/\/\/ --- LOGIKA TANDA TANGAN BERDASARKAN FILTER AFDELING ---.*?\/\/ -------------------------------------------------------/is';
$replacement = '// --- LOGIKA TANDA TANGAN BERDASARKAN FILTER AFDELING ---
$teks_afdeling = empty($afdeling) ? "Semua Afdeling" : "Afd " . htmlspecialchars($afdeling);
if (!empty($afdeling)) {
    $penandatangan = getReportSignatories($conn, $afdeling);
    $nama_pengawas = $penandatangan["pengawas"];
    $nama_kerani = $penandatangan["kerani"];
} else {
    // Jika filter Semua Afdeling, tampilkan garis titik-titik untuk diisi manual jika dicetak
    $nama_kerani = "( ................................... )";
    $nama_pengawas = "( ................................... )";
}
// -------------------------------------------------------';

$c = preg_replace($pattern, $replacement, $c);
file_put_contents($f, $c);
echo "Done.";
