<?php
require '../config/config.php';
include 'templates/header.php';

$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');
$user_id = $_SESSION['user_id'];

// Ambil tugas/logbook yang sudah diassign pengawas ke karyawan ini
$tgl_safe = mysqli_real_escape_string($conn, $tanggal);
$query_tasks = mysqli_query($conn, "SELECT lk.*, m.name as mandor_name FROM logbook_kinerja lk LEFT JOIN users m ON lk.mandor_id = m.id WHERE lk.user_id=$user_id AND lk.tanggal='$tgl_safe' ORDER BY lk.id ASC");
$all_tasks = [];
while($t = mysqli_fetch_assoc($query_tasks)) {
    $all_tasks[] = $t;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tgl_input = $tgl_safe;
    
    foreach($all_tasks as $t) {
        $id = $t['id'];
        
        $tbs = isset($_POST["tbs_$id"]) ? (int)$_POST["tbs_$id"] : (int)$t['tbs'];
        $kosong = isset($_POST["kosong_$id"]) ? (int)$_POST["kosong_$id"] : (int)$t['tandan_kosong'];
        $brondol = isset($_POST["brondol_$id"]) ? (int)$_POST["brondol_$id"] : (int)$t['tandan_brondol'];
        $total = isset($_POST["total_$id"]) ? (int)$_POST["total_$id"] : (int)$t['total_tandan'];
        $hasil_langsir_kg = isset($_POST["hasil_langsir_$id"]) ? (float)$_POST["hasil_langsir_$id"] : (float)$t['hasil_langsir_kg'];
        $jam = isset($_POST["jam_$id"]) ? (float)$_POST["jam_$id"] : (float)$t['jumlah_jam_kerja'];
        $hasil_ton = isset($_POST["hasil_ton_$id"]) ? (float)$_POST["hasil_ton_$id"] : (float)$t['hasil_ton'];
        $hasil_kg = isset($_POST["hasil_kg_$id"]) ? (float)$_POST["hasil_kg_$id"] : (float)$t['hasil_kg'];
        $pres_ton = isset($_POST["prestasi_ton_$id"]) ? (float)$_POST["prestasi_ton_$id"] : (float)$t['prestasi_ton'];
        $pres_kg = isset($_POST["prestasi_kg_$id"]) ? (float)$_POST["prestasi_kg_$id"] : (float)$t['prestasi_kg'];
        $aksi = isset($_POST["aksi_$id"]) ? mysqli_real_escape_string($conn, $_POST["aksi_$id"]) : (!empty($t['aksi']) ? mysqli_real_escape_string($conn, $t['aksi']) : 'belum');

        mysqli_query($conn, "UPDATE logbook_kinerja SET 
            tbs=$tbs, tandan_kosong=$kosong, tandan_brondol=$brondol, total_tandan=$total,
            hasil_langsir_kg=$hasil_langsir_kg, jumlah_jam_kerja=$jam,
            hasil_ton=$hasil_ton, hasil_kg=$hasil_kg, prestasi_ton=$pres_ton, prestasi_kg=$pres_kg,
            aksi='$aksi', status='ditinjau'
            WHERE id=$id AND user_id=$user_id");
    }
    swalRedirect('Semua data logbook berhasil disimpan!', "logbook.php?tanggal=$tgl_input", 'success');
}

// Grouping logic for rendering different tables
$format1 = []; // Langsir, Membabat gawangan
$format2 = []; // Perawatan lain (Kutip, semprot, rawat jalan, korek)
$format3 = []; // Potong buah
$format4 = []; // Muat TBS
$format5 = []; // Jaga

foreach($all_tasks as $t) {
    $ok = strtolower($t['objek_kerja']);
    if ($t['kategori_task'] == 'langsir' || strpos($ok, 'membabat') !== false) {
        $format1[] = $t;
    } elseif ($t['kategori_task'] == 'perawatan') {
        $format2[] = $t;
    } elseif ($t['kategori_task'] == 'potong_buah') {
        $format3[] = $t;
    } elseif ($t['kategori_task'] == 'muat_tbs') {
        $format4[] = $t;
    } elseif ($t['kategori_task'] == 'jaga') {
        $format5[] = $t;
    } else {
        $format2[] = $t; // fallback
    }
}
?>

<style>
    .card-container {
        background: #ffffff;
        border-radius: 16px;
        padding: 20px 16px;
        box-shadow: 0 4px 20px rgba(54, 72, 217, 0.05);
        border: 1px solid #f1f5f9;
        margin-bottom: 24px;
    }

    .form-input {
        width: 100%;
        padding: 12px 14px;
        border-radius: 10px;
        border: 1.5px solid #e2e8f0;
        background-color: #f8fafc;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-dark);
        font-family: inherit;
        outline: none;
        transition: all 0.2s;
        box-sizing: border-box;
    }
    
    .form-input:focus {
        border-color: var(--primary-start);
        box-shadow: 0 0 0 4px rgba(66, 88, 255, 0.1);
        background: white;
    }

    .table-responsive {
        width: 100%;
        overflow-x: auto;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        -webkit-overflow-scrolling: touch;
        background: white;
    }

    .table-logbook {
        border-collapse: collapse;
        white-space: nowrap;
        font-size: 12px;
        width: 100%;
    }

    /* Minimal width berbeda tiap tabel karena jumlah kolom beda */
    .table-perawatan { min-width: 600px; }
    .table-potong { min-width: 900px; }
    .table-muat { min-width: 800px; }
    .table-langsir { min-width: 950px; }
    .table-jaga { min-width: 700px; }

    .table-logbook th, .table-logbook td {
        padding: 12px 8px;
        border: 1px solid #e2e8f0;
        vertical-align: middle;
        text-align: center;
    }

    .table-logbook th {
        background-color: var(--primary-light);
        color: var(--primary-end);
        font-weight: 800;
        font-size: 11px;
        text-transform: uppercase;
    }

    .input-mini {
        width: 60px;
        padding: 8px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        text-align: center;
        font-size: 12px;
        font-weight: 600;
        background: #fff;
    }
    .input-mini:focus { border-color: var(--primary-start); outline: none; }

    .input-medium {
        width: 100px;
        padding: 8px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        background: #fff;
    }
    .input-medium:focus { border-color: var(--primary-start); outline: none; }

    .select-aksi {
        padding: 8px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
        background: #fff;
        color: #1e293b;
        cursor: pointer;
    }

    .table-title {
        font-size: 16px;
        font-weight: 800;
        color: var(--text-dark);
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .btn-submit {
        width: 100%;
        background: linear-gradient(135deg, var(--primary-start) 0%, var(--primary-end) 100%);
        color: white;
        border: none;
        padding: 16px;
        border-radius: 14px;
        font-size: 15px;
        font-weight: 800;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        transition: all 0.2s;
        box-sizing: border-box;
        box-shadow: 0 4px 15px rgba(66, 88, 255, 0.25);
        margin-top: 24px;
    }
    .btn-submit:active { transform: scale(0.98); box-shadow: none; }

    .btn-print {
        background: white;
        color: var(--text-dark);
        border: 1px solid #e2e8f0;
        padding: 8px 12px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 12px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: var(--text-muted);
        text-decoration: none;
        font-weight: 700;
        font-size: 13px;
        margin-bottom: 16px;
        background: white;
        padding: 8px 14px;
        border-radius: 20px;
        border: 1.5px solid #e2e8f0;
    }
</style>

<div class="animate-up">
    <a href="index.php" class="btn-back">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
        Kembali
    </a>

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 16px;">
        <h2 class="page-title" style="margin: 0; font-size: 20px;">Logbook Kegiatan</h2>
        <button class="btn-print" onclick="window.print()" style="white-space: nowrap; flex-shrink: 0;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            PDF
        </button>
    </div>

    <!-- Filter Tanggal (Global) -->
    <form id="filterForm" method="GET" style="margin-bottom: 24px;">
        <label style="font-size: 12px; font-weight: 700; color: #64748b; margin-bottom:6px; display:block;">Pilih tahun/bulan/tanggal</label>
        <input type="date" name="tanggal" class="form-input" value="<?= $tanggal ?>" onchange="document.getElementById('filterForm').submit()">
    </form>

    <p style="font-size: 11px; color: var(--text-muted); font-style: italic; margin-bottom: 16px;">* Geser tiap tabel ke kanan untuk melihat selengkapnya.</p>

    <!-- FORM UTAMA -->
    <form method="POST">
        
        <?php if(count($all_tasks) > 0): ?>
        <div class="card-container">
            
            <?php 
            // ----------------------------------------------------
            // FORMAT 1: Langsir manual / Membabat gawangan
            // ----------------------------------------------------
            if (count($format1) > 0): ?>
            <h3 class="table-title"><span style="color:var(--primary-start)">■</span> Objek Kerja: Langsir Manual / Membabat Gawangan</h3>
            <div class="table-responsive">
                <table class="table-logbook" style="min-width: 900px;">
                    <thead>
                        <tr>
                            <th rowspan="2">Tanggal</th>
                            <th rowspan="2">Blok</th>
                            <th rowspan="2">Luas Ha</th>
                            <th rowspan="2">Mandor</th>
                            <th rowspan="2">Objek Kerja</th>
                            <th colspan="2">Hasil</th>
                            <th colspan="2">Prestasi</th>
                            <th rowspan="2">Aksi</th>
                            <th rowspan="2">Status</th>
                        </tr>
                        <tr>
                            <th>Ton</th><th>Kg</th><th>Ton</th><th>Kg</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($format1 as $t): 
                            $id = $t['id'];
                        ?>
                        <tr>
                            <td class="info-readonly"><?= date('d M Y', strtotime($t['tanggal'])) ?></td>
                            <td class="info-readonly"><?= htmlspecialchars($t['blok'] ?? '-') ?></td>
                            <td class="info-readonly"><?= htmlspecialchars($t['luas_ha'] ?? '-') ?></td>
                            <td class="info-readonly"><?= htmlspecialchars($t['mandor_name'] ?? '-') ?></td>
                            <td class="info-readonly"><?= htmlspecialchars($t['objek_kerja'] ?? '-') ?></td>
                            <td><input type="number" step="0.01" name="hasil_ton_<?= $id ?>" class="input-mini" value="<?= $t['hasil_ton'] ?>"></td>
                            <td><input type="number" step="0.01" name="hasil_kg_<?= $id ?>" class="input-mini" value="<?= $t['hasil_kg'] ?>"></td>
                            <td><input type="number" step="0.01" name="prestasi_ton_<?= $id ?>" class="input-mini" value="<?= $t['prestasi_ton'] ?>"></td>
                            <td><input type="number" step="0.01" name="prestasi_kg_<?= $id ?>" class="input-mini" value="<?= $t['prestasi_kg'] ?>"></td>
                            <td>
                                <select name="aksi_<?= $id ?>" class="select-aksi">
                                    <option value="belum" <?= $t['aksi'] == 'belum' ? 'selected' : '' ?>>Belum</option>
                                    <option value="selesai" <?= $t['aksi'] == 'selesai' ? 'selected' : '' ?>>Selesai</option>
                                </select>
                            </td>
                            <td><?= renderStatusBadge($t['status']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php 
            // ----------------------------------------------------
            // FORMAT 2: Perawatan Umum (Kutip, Semprot, dsb)
            // ----------------------------------------------------
            if (count($format2) > 0): ?>
            <h3 class="table-title"><span style="color:var(--primary-start)">■</span> Objek Kerja: Perawatan Umum</h3>
            <div class="table-responsive">
                <table class="table-logbook" style="min-width: 700px;">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Blok</th>
                            <th>Luas Ha</th>
                            <th>Mandor</th>
                            <th>Objek Kerja</th>
                            <th>Aksi</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($format2 as $t): $id = $t['id']; ?>
                        <tr>
                            <td class="info-readonly"><?= date('d M Y', strtotime($t['tanggal'])) ?></td>
                            <td class="info-readonly"><?= htmlspecialchars($t['blok'] ?? '-') ?></td>
                            <td class="info-readonly"><?= htmlspecialchars($t['luas_ha'] ?? '-') ?></td>
                            <td class="info-readonly"><?= htmlspecialchars($t['mandor_name'] ?? '-') ?></td>
                            <td class="info-readonly"><?= htmlspecialchars($t['objek_kerja'] ?? '-') ?></td>
                            <td>
                                <select name="aksi_<?= $id ?>" class="select-aksi">
                                    <option value="belum" <?= $t['aksi'] == 'belum' ? 'selected' : '' ?>>Belum</option>
                                    <option value="selesai" <?= $t['aksi'] == 'selesai' ? 'selected' : '' ?>>Selesai</option>
                                </select>
                            </td>
                            <td><?= renderStatusBadge($t['status']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php 
            // ----------------------------------------------------
            // FORMAT 3: Potong Buah / Panen
            // ----------------------------------------------------
            if (count($format3) > 0): ?>
            <h3 class="table-title"><span style="color:var(--primary-start)">■</span> Objek Kerja: Potong Buah / Panen</h3>
            <div class="table-responsive">
                <table class="table-logbook" style="min-width: 900px;">
                    <thead>
                        <tr>
                            <th rowspan="2">Tanggal</th>
                            <th rowspan="2">Blok</th>
                            <th rowspan="2">Luas Ha</th>
                            <th rowspan="2">Mandor</th>
                            <th colspan="4">Jumlah Janjangan</th>
                            <th rowspan="2">Aksi</th>
                            <th rowspan="2">Status</th>
                        </tr>
                        <tr>
                            <th>TBS</th><th>Kosong</th><th>Brondol</th><th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($format3 as $t): $id = $t['id']; ?>
                        <tr>
                            <td class="info-readonly"><?= date('d M Y', strtotime($t['tanggal'])) ?></td>
                            <td class="info-readonly"><?= htmlspecialchars($t['blok'] ?? '-') ?></td>
                            <td class="info-readonly"><?= htmlspecialchars($t['luas_ha'] ?? '-') ?></td>
                            <td class="info-readonly"><?= htmlspecialchars($t['mandor_name'] ?? '-') ?></td>
                            <td><input type="number" name="tbs_<?= $id ?>" class="input-mini" value="<?= $t['tbs'] ?>"></td>
                            <td><input type="number" name="kosong_<?= $id ?>" class="input-mini" value="<?= $t['tandan_kosong'] ?>"></td>
                            <td><input type="number" name="brondol_<?= $id ?>" class="input-mini" value="<?= $t['tandan_brondol'] ?>"></td>
                            <td><input type="number" name="total_<?= $id ?>" class="input-mini" value="<?= $t['total_tandan'] ?>"></td>
                            <td>
                                <select name="aksi_<?= $id ?>" class="select-aksi">
                                    <option value="belum" <?= $t['aksi'] == 'belum' ? 'selected' : '' ?>>Belum</option>
                                    <option value="selesai" <?= $t['aksi'] == 'selesai' ? 'selected' : '' ?>>Selesai</option>
                                </select>
                            </td>
                            <td><?= renderStatusBadge($t['status']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php 
            // ----------------------------------------------------
            // FORMAT 4: Muat TBS
            // ----------------------------------------------------
            if (count($format4) > 0): ?>
            <h3 class="table-title"><span style="color:var(--primary-start)">■</span> Objek Kerja: Muat TBS (ke Truk/Jonder)</h3>
            <div class="table-responsive">
                <table class="table-logbook" style="min-width: 800px;">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Blok</th>
                            <th>Luas Ha</th>
                            <th>Mandor</th>
                            <th>Hasil Langsir (Kg)</th>
                            <th>Jumlah Jam Kerja</th>
                            <th>Aksi</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($format4 as $t): $id = $t['id']; ?>
                        <tr>
                            <td class="info-readonly"><?= date('d M Y', strtotime($t['tanggal'])) ?></td>
                            <td class="info-readonly"><?= htmlspecialchars($t['blok'] ?? '-') ?></td>
                            <td class="info-readonly"><?= htmlspecialchars($t['luas_ha'] ?? '-') ?></td>
                            <td class="info-readonly"><?= htmlspecialchars($t['mandor_name'] ?? '-') ?></td>
                            <td><input type="number" step="0.01" name="hasil_langsir_<?= $id ?>" class="input-mini" value="<?= $t['hasil_langsir_kg'] ?>"></td>
                            <td><input type="number" step="0.5" name="jam_<?= $id ?>" class="input-mini" value="<?= $t['jumlah_jam_kerja'] ?>"></td>
                            <td>
                                <select name="aksi_<?= $id ?>" class="select-aksi">
                                    <option value="belum" <?= $t['aksi'] == 'belum' ? 'selected' : '' ?>>Belum</option>
                                    <option value="selesai" <?= $t['aksi'] == 'selesai' ? 'selected' : '' ?>>Selesai</option>
                                </select>
                            </td>
                            <td><?= renderStatusBadge($t['status']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php 
            // ----------------------------------------------------
            // FORMAT 5: Jaga
            // ----------------------------------------------------
            if (count($format5) > 0): ?>
            <h3 class="table-title"><span style="color:var(--primary-start)">■</span> Objek Kerja: Jaga (Genset/Alat/Buah)</h3>
            <div class="table-responsive">
                <table class="table-logbook" style="min-width: 700px;">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Blok</th>
                            <th>Luas Ha / Mandor</th>
                            <th>Aksi Jumlah Jam</th>
                            <th>Aksi</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($format5 as $t): $id = $t['id']; ?>
                        <tr>
                            <td class="info-readonly"><?= date('d M Y', strtotime($t['tanggal'])) ?></td>
                            <td class="info-readonly"><?= htmlspecialchars($t['blok'] ?? '-') ?></td>
                            <td class="info-readonly"><?= htmlspecialchars($t['luas_ha'] ?? '-') ?> / <?= htmlspecialchars($t['mandor_name'] ?? '-') ?></td>
                            <td><input type="number" step="0.5" name="jam_<?= $id ?>" class="input-mini" value="<?= $t['jumlah_jam_kerja'] ?>"></td>
                            <td>
                                <select name="aksi_<?= $id ?>" class="select-aksi">
                                    <option value="belum" <?= $t['aksi'] == 'belum' ? 'selected' : '' ?>>Belum</option>
                                    <option value="selesai" <?= $t['aksi'] == 'selesai' ? 'selected' : '' ?>>Selesai</option>
                                </select>
                            </td>
                            <td><?= renderStatusBadge($t['status']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

        </div>
        
        <button type="submit" class="btn-submit">Simpan Seluruh Logbook</button>
        
        <?php else: ?>
        <div class="card-container" style="text-align:center; padding:40px;">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5" style="margin-bottom:12px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
            <p style="color:#94a3b8; font-size:14px; font-weight:600;">Belum ada tugas untuk tanggal ini.</p>
            <p style="color:#cbd5e1; font-size:12px;">Pengawas belum mengisi Form Harian untuk Anda.</p>
        </div>
        <?php endif; ?>
        
    </form>
</div>

<?php 
function renderStatusBadge($st) {
    if (!$st) $st = 'ditinjau';
    $st_color = '#94a3b8'; $st_bg = '#f1f5f9';
    if($st == 'diterima' || $st == 'selesai') { $st_color = '#166534'; $st_bg = '#dcfce7'; }
    if($st == 'ditolak') { $st_color = '#991b1b'; $st_bg = '#fee2e2'; }
    return '<span style="background:'.$st_bg.'; color:'.$st_color.'; padding:4px 8px; border-radius:4px; font-size:10px; font-weight:800;">'.strtoupper($st).'</span>';
}
include 'templates/footer.php'; 
?>
