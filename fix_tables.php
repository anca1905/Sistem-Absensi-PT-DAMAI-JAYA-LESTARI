<?php
$files = [
    "kerani/laporan_absensi.php", 
    "keuangan/laporan_absensi.php", 
    "pengawas/laporan_kinerja.php", 
    "kerani/laporan_keseluruhan.php", 
    "keuangan/lap_keseluruhan.php", 
    "pengawas/laporan_keseluruhan.php",
    "kerani/laporan_individu.php",
    "keuangan/laporan_individu.php",
    "pengawas/laporan_mingguan.php"
];

foreach ($files as $f) {
    if (file_exists($f)) {
        $c = file_get_contents($f);
        
        // Match colspan with either single or double quotes
        $c = preg_replace_callback("/<td colspan=['\"]([^'\"]+)['\"][^>]*>(?:Belum|Tidak) ada (?:data|laporan).*?<\/td>/is", function($m) {
            $colStr = $m[1];
            if (is_numeric($colStr)) {
                $cols = (int)$colStr;
                $emptyRow = "";
                for($i=0; $i<$cols; $i++) { 
                    $emptyRow .= "<td class=\"td-empty\">-</td>"; 
                }
                return $emptyRow;
            } else {
                $cleanCol = str_replace(array("<?=", "?>", "php", "echo", " "), "", $colStr);
                return "<?php for(\$i=0; \$i<" . $cleanCol . "; \$i++): ?><td class=\"td-empty\">-</td><?php endfor; ?>";
            }
        }, $c);

        file_put_contents($f, $c);
        echo "Updated $f\n";
    }
}
