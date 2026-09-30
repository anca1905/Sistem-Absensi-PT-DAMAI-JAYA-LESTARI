<?php
$f = "keuangan/laporan_absensi.php";
$c = file_get_contents($f);
$c = preg_replace('/(\$nama_kerani\s*=\s*\'.*?\';.*?\$nama_pengawas\s*=\s*\'.*?\';.*?)<div class="doc-signature">.*?<\/div>\s*<\/div>/is',
'
      $penandatangan = getReportSignatories($conn, $afdeling);
      $teks_afdeling = empty($afdeling) ? "Semua Afdeling" : "Afdeling " . htmlspecialchars($afdeling);
      if (empty($afdeling)) {
          $penandatangan["pengawas"] = "( ................................... )";
          $penandatangan["kerani"] = "( ................................... )";
          $penandatangan["afdeling"] = $teks_afdeling;
      }
      ?>
      <div class="doc-signature">
          <div class="doc-signature-col">
              <p>Diketahui oleh,</p>
              <span class="sig-name"><?= htmlspecialchars($penandatangan["pengawas"]) ?></span>
              <div style="font-weight:bold;">Pengawas <?= empty($afdeling) ? "" : "Afdeling " ?><?= htmlspecialchars($penandatangan["afdeling"]) ?></div>
          </div>
          <div class="doc-signature-col">
              <p>Disusun oleh,</p>
              <span class="sig-name"><?= htmlspecialchars($penandatangan["kerani"]) ?></span>
              <div style="font-weight:bold;">Kerani <?= empty($afdeling) ? "" : "Afdeling " ?><?= htmlspecialchars($penandatangan["afdeling"]) ?></div>
          </div>
      </div>
', $c);
file_put_contents($f, $c);
