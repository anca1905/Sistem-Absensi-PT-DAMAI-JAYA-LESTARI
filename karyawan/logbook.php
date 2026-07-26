<?php
require '../config/config.php';
include 'templates/header.php';

$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');

// Simulasi Data Dummy Tugas Hari Ini yang dikelompokkan berdasar kategori kerja
$tasks_perawatan = [];
$tasks_potong_buah = [];
$tasks_muat_tbs = [];

$tasks_langsir = [
    ['id'=>5, 'blok'=>'249', 'luas'=>'21.45', 'mandor'=>'Idris', 'objek'=>'Langsir manual']
];

$tasks_jaga = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'] ?? 1; // Fallback to 1 if not set
    $tgl_input = mysqli_real_escape_string($conn, $tanggal);
    
    $all_tasks = array_merge($tasks_perawatan, $tasks_potong_buah, $tasks_muat_tbs, $tasks_langsir, $tasks_jaga);
    
    foreach($all_tasks as $t) {
        $id = $t['id'];
        $blok = $t['blok'];
        $luas = $t['luas'];
        $objek = $t['objek'];
        
        $kategori = '';
        if(in_array($t, $tasks_perawatan)) $kategori = 'perawatan';
        if(in_array($t, $tasks_potong_buah)) $kategori = 'potong_buah';
        if(in_array($t, $tasks_muat_tbs)) $kategori = 'muat_tbs';
        if(in_array($t, $tasks_langsir)) $kategori = 'langsir';
        if(in_array($t, $tasks_jaga)) $kategori = 'jaga';

        $aksi = isset($_POST["aksi_$id"]) ? $_POST["aksi_$id"] : 'belum';
        $tbs = isset($_POST["tbs_$id"]) ? (int)$_POST["tbs_$id"] : 0;
        $kosong = isset($_POST["kosong_$id"]) ? (int)$_POST["kosong_$id"] : 0;
        $brondol = isset($_POST["brondol_$id"]) ? (int)$_POST["brondol_$id"] : 0;
        $total = isset($_POST["total_$id"]) ? (int)$_POST["total_$id"] : 0;
        $hasil_kg = isset($_POST["hasil_kg_$id"]) ? (float)$_POST["hasil_kg_$id"] : 0;
        $jam = isset($_POST["jam_$id"]) ? (float)$_POST["jam_$id"] : 0;
        $hasil_ton_l = isset($_POST["hasil_ton_$id"]) ? (float)$_POST["hasil_ton_$id"] : 0;
        $hasil_kg_l = isset($_POST["hasil_kg_langsir_$id"]) ? (float)$_POST["hasil_kg_langsir_$id"] : 0;
        $pres_ton = isset($_POST["prestasi_ton_$id"]) ? (float)$_POST["prestasi_ton_$id"] : 0;
        $pres_kg = isset($_POST["prestasi_kg_$id"]) ? (float)$_POST["prestasi_kg_$id"] : 0;
        $jam_jaga = isset($_POST["jam_jaga_$id"]) ? (float)$_POST["jam_jaga_$id"] : 0;

        $jam_kerja = $jam > 0 ? $jam : $jam_jaga;

        $check = mysqli_query($conn, "SELECT id FROM logbook_kinerja WHERE user_id=$user_id AND tanggal='$tgl_input' AND objek_kerja='$objek' AND blok='$blok'");
        if(mysqli_num_rows($check) > 0) {
            $row = mysqli_fetch_assoc($check);
            $log_id = $row['id'];
            mysqli_query($conn, "UPDATE logbook_kinerja SET 
                tbs=$tbs, tandan_kosong=$kosong, tandan_brondol=$brondol, total_tandan=$total,
                hasil_langsir_kg=$hasil_kg, jumlah_jam_kerja=$jam_kerja, aksi='$aksi',
                hasil_ton=$hasil_ton_l, hasil_kg=$hasil_kg_l, prestasi_ton=$pres_ton, prestasi_kg=$pres_kg, status='ditinjau'
                WHERE id=$log_id");
        } else {
            mysqli_query($conn, "INSERT INTO logbook_kinerja 
                (user_id, tanggal, blok, luas_ha, objek_kerja, kategori_task, tbs, tandan_kosong, tandan_brondol, total_tandan, hasil_langsir_kg, jumlah_jam_kerja, aksi, hasil_ton, hasil_kg, prestasi_ton, prestasi_kg, status)
                VALUES 
                ($user_id, '$tgl_input', '$blok', '$luas', '$objek', '$kategori', $tbs, $kosong, $brondol, $total, $hasil_kg, $jam_kerja, '$aksi', $hasil_ton_l, $hasil_kg_l, $pres_ton, $pres_kg, 'ditinjau')");
        }
    }
    swalRedirect('Semua data logbook berhasil disimpan!', "logbook.php?tanggal=$tgl_input", 'success');
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
        <button class="btn-print" onclick="window.print()">
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
        
        <?php if(count($tasks_perawatan) > 0): ?>
        <!-- TABEL 1: PERAWATAN (Sketsa 3.2) -->
        <div class="card-container">
            <h3 class="table-title">
                <span style="display:inline-block; width:12px; height:12px; background:var(--primary-start); border-radius:3px;"></span>
                Objek Kerja: Perawatan
            </h3>
            <div class="table-responsive">
                <table class="table-logbook table-perawatan">
                    <thead>
                        <tr>
                            <th>TANGGAL</th>
                            <th>BLOK</th>
                            <th>LUAS HA</th>
                            <th>MANDOR</th>
                            <th>OBJEK KERJA</th>
                            <th>AKSI</th>
                            <th>STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($tasks_perawatan as $t): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($tanggal)) ?></td>
                            <td style="color:#64748b; font-weight:600;"><?= $t['blok'] ?></td>
                            <td style="color:#64748b; font-weight:600;"><?= $t['luas'] ?></td>
                            <td style="color:#64748b; font-weight:600;"><?= $t['mandor'] ?></td>
                            <td style="text-align:left; font-weight:700;"><?= $t['objek'] ?></td>
                            <td>
                                <select name="aksi_<?= $t['id'] ?>" class="select-aksi">
                                    <option value="belum">Belum</option>
                                    <option value="selesai">Selesai</option>
                                </select>
                            </td>
                            <td><span style="background:#f1f5f9; color:#94a3b8; padding:4px 8px; border-radius:4px; font-size:10px; font-weight:800;">DITINJAU</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if(count($tasks_potong_buah) > 0): ?>
        <!-- TABEL 2: POTONG BUAH (Sketsa 3.3) -->
        <div class="card-container">
            <h3 class="table-title">
                <span style="display:inline-block; width:12px; height:12px; background:#f59e0b; border-radius:3px;"></span>
                Objek Kerja: Potong Buah / Panen
            </h3>
            <div class="table-responsive">
                <table class="table-logbook table-potong">
                    <thead>
                        <tr>
                            <th rowspan="2">TANGGAL</th>
                            <th rowspan="2">BLOK</th>
                            <th rowspan="2">LUAS HA</th>
                            <th rowspan="2">MANDOR</th>
                            <th colspan="4">JUMLAH JANJANGAN</th>
                            <th rowspan="2">AKSI</th>
                            <th rowspan="2">STATUS</th>
                        </tr>
                        <tr>
                            <th>TANDAN BUAH<br>SEGAR</th>
                            <th>TANDAN<br>KOSONG</th>
                            <th>TANDAN BUAH<br>BRONDOL</th>
                            <th>TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($tasks_potong_buah as $t): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($tanggal)) ?></td>
                            <td style="color:#64748b; font-weight:600;"><?= $t['blok'] ?></td>
                            <td style="color:#64748b; font-weight:600;"><?= $t['luas'] ?></td>
                            <td style="color:#64748b; font-weight:600;"><?= $t['mandor'] ?></td>
                            <td><input type="number" name="tbs_<?= $t['id'] ?>" class="input-mini" placeholder="0"></td>
                            <td><input type="number" name="tk_<?= $t['id'] ?>" class="input-mini" placeholder="0"></td>
                            <td><input type="number" name="tbb_<?= $t['id'] ?>" class="input-mini" placeholder="0"></td>
                            <td><input type="number" name="tot_<?= $t['id'] ?>" class="input-mini" placeholder="0"></td>
                            <td>
                                <select name="aksi_<?= $t['id'] ?>" class="select-aksi">
                                    <option value="belum">Belum</option>
                                    <option value="selesai">Selesai</option>
                                </select>
                            </td>
                            <td><span style="background:#f1f5f9; color:#94a3b8; padding:4px 8px; border-radius:4px; font-size:10px; font-weight:800;">DITINJAU</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if(count($tasks_muat_tbs) > 0): ?>
        <!-- TABEL 3: MUAT TBS (Sketsa 3.4) -->
        <div class="card-container">
            <h3 class="table-title">
                <span style="display:inline-block; width:12px; height:12px; background:#10b981; border-radius:3px;"></span>
                Objek Kerja: Muat TBS
            </h3>
            <div class="table-responsive">
                <table class="table-logbook table-muat">
                    <thead>
                        <tr>
                            <th>TANGGAL</th>
                            <th>BLOK</th>
                            <th>LUAS HA</th>
                            <th>MANDOR</th>
                            <th>HASIL LANGSIRAN<br>(KG)</th>
                            <th>JUMLAH JAM<br>KERJA</th>
                            <th>AKSI</th>
                            <th>STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($tasks_muat_tbs as $t): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($tanggal)) ?></td>
                            <td style="color:#64748b; font-weight:600;"><?= $t['blok'] ?></td>
                            <td style="color:#64748b; font-weight:600;"><?= $t['luas'] ?></td>
                            <td style="color:#64748b; font-weight:600;"><?= $t['mandor'] ?></td>
                            <td><input type="number" name="hasil_muat_<?= $t['id'] ?>" class="input-medium" placeholder="0"></td>
                            <td><input type="text" name="jam_kerja_<?= $t['id'] ?>" class="input-mini" placeholder="Ex: 8"></td>
                            <td>
                                <select name="aksi_<?= $t['id'] ?>" class="select-aksi">
                                    <option value="belum">Belum</option>
                                    <option value="selesai">Selesai</option>
                                </select>
                            </td>
                            <td><span style="background:#f1f5f9; color:#94a3b8; padding:4px 8px; border-radius:4px; font-size:10px; font-weight:800;">DITINJAU</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if(count($tasks_langsir) > 0): ?>
        <!-- TABEL 4: LANGSIR MANUAL (Sketsa 3 Awal) -->
        <div class="card-container">
            <h3 class="table-title">
                <span style="display:inline-block; width:12px; height:12px; background:#8b5cf6; border-radius:3px;"></span>
                Objek Kerja: Langsir Manual
            </h3>
            <div class="table-responsive">
                <table class="table-logbook table-langsir">
                    <thead>
                        <tr>
                            <th rowspan="2">TANGGAL</th>
                            <th rowspan="2">BLOK</th>
                            <th rowspan="2">LUAS HA</th>
                            <th rowspan="2">MANDOR</th>
                            <th colspan="2">HASIL</th>
                            <th colspan="2">PRESTASI</th>
                            <th rowspan="2">AKSI</th>
                            <th rowspan="2">STATUS</th>
                        </tr>
                        <tr>
                            <th>TANDAN</th>
                            <th>KG</th>
                            <th>TANDAN</th>
                            <th>KG</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($tasks_langsir as $t): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($tanggal)) ?></td>
                            <td style="color:#64748b; font-weight:600;"><?= $t['blok'] ?></td>
                            <td style="color:#64748b; font-weight:600;"><?= $t['luas'] ?></td>
                            <td style="color:#64748b; font-weight:600;"><?= $t['mandor'] ?></td>
                            <td><input type="number" name="hasil_ton_<?= $t['id'] ?>" class="input-mini" placeholder="0"></td>
                            <td><input type="number" name="hasil_kg_<?= $t['id'] ?>" class="input-mini" placeholder="0"></td>
                            <td><input type="number" name="prestasi_ton_<?= $t['id'] ?>" class="input-mini" placeholder="0"></td>
                            <td><input type="number" name="prestasi_kg_<?= $t['id'] ?>" class="input-mini" placeholder="0"></td>
                            <td>
                                <input type="text" class="input-mini" value="Belum" readonly style="background:#f1f5f9; border:none; width:60px; cursor:not-allowed;">
                            </td>
                            <td><span style="background:#f1f5f9; color:#94a3b8; padding:4px 8px; border-radius:4px; font-size:10px; font-weight:800;">DITINJAU</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if(count($tasks_jaga) > 0): ?>
        <!-- TABEL 5: JAGA (Sketsa 3.5) -->
        <div class="card-container">
            <h3 class="table-title">
                <span style="display:inline-block; width:12px; height:12px; background:#ec4899; border-radius:3px;"></span>
                Objek Kerja: Penjagaan
            </h3>
            <div class="table-responsive">
                <table class="table-logbook table-jaga">
                    <thead>
                        <tr>
                            <th>TANGGAL</th>
                            <th>BLOK</th>
                            <th>LUAS HA</th>
                            <th>MANDOR</th>
                            <th>OBJEK KERJA</th>
                            <th>JUMLAH JAM<br>KERJA</th>
                            <th>AKSI</th>
                            <th>STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($tasks_jaga as $t): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($tanggal)) ?></td>
                            <td style="color:#64748b; font-weight:600;"><?= $t['blok'] ?></td>
                            <td style="color:#64748b; font-weight:600;"><?= $t['luas'] ?></td>
                            <td style="color:#64748b; font-weight:600;"><?= $t['mandor'] ?></td>
                            <td style="text-align:left; font-weight:700;"><?= $t['objek'] ?></td>
                            <td><input type="text" name="jam_jaga_<?= $t['id'] ?>" class="input-mini" placeholder="Ex: 8"></td>
                            <td>
                                <select name="aksi_<?= $t['id'] ?>" class="select-aksi">
                                    <option value="belum">Belum</option>
                                    <option value="selesai">Selesai</option>
                                </select>
                            </td>
                            <td><span style="background:#f1f5f9; color:#94a3b8; padding:4px 8px; border-radius:4px; font-size:10px; font-weight:800;">DITINJAU</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <button type="submit" class="btn-submit">Simpan Logbook Hari Ini</button>
    </form>
</div>

<?php include 'templates/footer.php'; ?>
