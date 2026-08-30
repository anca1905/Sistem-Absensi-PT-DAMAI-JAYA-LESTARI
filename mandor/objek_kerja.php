<?php
require '../config/config.php';
include 'templates/header.php';

$mandor_id = $_SESSION['user_id'];
$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');
$afdeling_mandor = isset($_SESSION['afdeling']) ? mysqli_real_escape_string($conn, $_SESSION['afdeling']) : '';
$tgl_safe = mysqli_real_escape_string($conn, $tanggal);

// 1. Handle Simpan Data & Verifikasi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_semua'])) {

    foreach ($_POST as $key => $val) {
        if (strpos($key, 'status_') === 0) {
            $id = (int) str_replace('status_', '', $key);
            $status = mysqli_real_escape_string($conn, $val);

            $update_sql = "UPDATE logbook_kinerja SET status='$status'";

            // Format 1: Langsir / Membabat
            if (isset($_POST["hasil_ton_$id"])) {
                $hasil_ton = (float)$_POST["hasil_ton_$id"];
                $update_sql .= ", hasil_ton=$hasil_ton";
            }
            if (isset($_POST["hasil_kg_$id"])) {
                $hasil_kg = (float)$_POST["hasil_kg_$id"];
                $update_sql .= ", hasil_kg=$hasil_kg";
            }

            // Format 3: Potong Buah / Panen
            if (isset($_POST["tbs_$id"])) {
                $update_sql .= ", tbs=" . (int)$_POST["tbs_$id"];
            }
            if (isset($_POST["kosong_$id"])) {
                $update_sql .= ", tandan_kosong=" . (int)$_POST["kosong_$id"];
            }
            if (isset($_POST["brondol_$id"])) {
                $update_sql .= ", tandan_brondol=" . (int)$_POST["brondol_$id"];
            }
            if (isset($_POST["total_$id"])) {
                $update_sql .= ", total_tandan=" . (int)$_POST["total_$id"];
            }

            // Format 4: Muat TBS
            if (isset($_POST["hasil_langsir_$id"])) {
                $update_sql .= ", hasil_langsir_kg=" . (float)$_POST["hasil_langsir_$id"];
            }

            $update_sql .= " WHERE id=$id AND mandor_id=$mandor_id";
            mysqli_query($conn, $update_sql);
        }
    }
    swalRedirect('Data objek kerja berhasil disimpan dan diverifikasi!', "objek_kerja.php?tanggal=$tgl_safe", 'success');
    exit;
}

// 2. Ambil Data
if (!empty($afdeling_mandor)) {
    $query_logbook = mysqli_query($conn, "
        SELECT lk.*, u.nik, u.name as karyawan_name
        FROM logbook_kinerja lk
        JOIN users u ON lk.user_id = u.id
        WHERE lk.tanggal = '$tgl_safe' AND u.afdeling = '$afdeling_mandor' AND lk.mandor_id = $mandor_id
        ORDER BY u.name ASC
    ");
} else {
    $query_logbook = mysqli_query($conn, "
        SELECT lk.*, u.nik, u.name as karyawan_name
        FROM logbook_kinerja lk
        JOIN users u ON lk.user_id = u.id
        WHERE lk.tanggal = '$tgl_safe' AND lk.mandor_id = $mandor_id
        ORDER BY u.name ASC
    ");
}

$all_tasks = [];
if ($query_logbook) {
    while ($row = mysqli_fetch_assoc($query_logbook)) {
        $all_tasks[] = $row;
    }
}

// 3. Grouping Berdasarkan Objek Kerja
$format1 = []; // Langsir, Membabat gawangan
$format2 = []; // Perawatan lain
$format3 = []; // Potong buah
$format4 = []; // Muat TBS
$format5 = []; // Jaga

foreach ($all_tasks as $t) {
    $ok = strtolower($t['objek_kerja']);
    if ($t['kategori_task'] == 'langsir' || strpos($ok, 'membabat') !== false || strpos($ok, 'langsir') !== false) {
        $format1[] = $t;
    } elseif ($t['kategori_task'] == 'potong_buah' || strpos($ok, 'potong') !== false || strpos($ok, 'panen') !== false) {
        $format3[] = $t;
    } elseif ($t['kategori_task'] == 'muat_tbs' || strpos($ok, 'muat') !== false) {
        $format4[] = $t;
    } elseif ($t['kategori_task'] == 'jaga' || strpos($ok, 'jaga') !== false) {
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
        padding: 24px;
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
        margin-bottom: 20px;
    }

    .table-logbook {
        border-collapse: collapse;
        white-space: nowrap;
        font-size: 13px;
        width: 100%;
    }

    .table-logbook th,
    .table-logbook td {
        padding: 14px 10px;
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
        width: 70px;
        padding: 10px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        text-align: center;
        font-size: 13px;
        font-weight: 700;
        background: #fff;
        color: var(--primary-end);
    }

    .input-mini:focus {
        border-color: var(--primary-start);
        outline: none;
    }

    .select-status {
        padding: 10px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
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
    }

    .btn-submit:active {
        transform: scale(0.98);
        box-shadow: none;
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

    .readonly-text {
        font-weight: 600;
        color: #64748b;
    }
</style>

<div class="animate-up">
    <a href="index.php" class="btn-back">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        Kembali
    </a>

    <h2 class="page-title" style="margin: 0 0 20px 0; font-size: 20px;">Objek Kerja & Verifikasi</h2>

    <form id="filterForm" method="GET" style="display:flex; gap:10px; align-items:flex-end; margin-bottom: 24px;">
        <div style="flex:1;">
            <label style="font-size: 12px; font-weight: 700; color: #64748b; margin-bottom:6px; display:block;">Pilih tgl, bulan, thn</label>
            <input type="date" name="tanggal" class="form-input" value="<?= $tanggal ?>" onchange="document.getElementById('filterForm').submit()">
        </div>
        <div>
            <button type="button" class="btn-back" style="margin-bottom:0; height:44px; border-radius:10px; background:#f8fafc;" onclick="alert('Fitur komentar akan segera hadir.')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                </svg>
                Komentar
            </button>
        </div>
    </form>

    <p style="font-size: 11px; color: var(--text-muted); font-style: italic; margin-bottom: 16px;">
        * Diisi mandor setelah karyawan pulang bekerja lalu melapor hasil kerjanya.<br>
        * Prestasi, Blok, dan Luas Ha adalah data bawaan (readonly).
    </p>

    <form method="POST">

        <?php if (count($all_tasks) > 0): ?>

            <!-- FORMAT 1: Langsir manual -->
            <?php if (count($format1) > 0): ?>
                <div class="card-container">
                    <h3 class="table-title"><span style="color:var(--primary-start)">■</span> Langsir Manual</h3>
                    <div class="table-responsive">
                        <table class="table-logbook">
                            <thead>
                                <tr>
                                    <th rowspan="2">NO</th>
                                    <th rowspan="2">NIK</th>
                                    <th rowspan="2">Nama Karyawan</th>
                                    <th colspan="2">Hasil (Diisi Mandor)</th>
                                    <th colspan="2">Prestasi (Readonly)</th>
                                    <th rowspan="2">Blok</th>
                                    <th rowspan="2">Luas Ha</th>
                                    <th rowspan="2">Verifikasi</th>
                                </tr>
                                <tr>
                                    <th>Tandan / Ton</th>
                                    <th>Kg</th>
                                    <th>Tandan / Ton</th>
                                    <th>Kg</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1;
                                foreach ($format1 as $t): $id = $t['id']; ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td class="readonly-text"><?= htmlspecialchars($t['nik']) ?></td>
                                        <td style="text-align:left; font-weight:700; color:var(--text-dark);"><?= htmlspecialchars($t['karyawan_name']) ?></td>
                                        <td><input type="number" step="0.01" name="hasil_ton_<?= $id ?>" class="input-mini" value="<?= $t['hasil_ton'] ?>"></td>
                                        <td><input type="number" step="0.01" name="hasil_kg_<?= $id ?>" class="input-mini" value="<?= $t['hasil_kg'] ?>"></td>
                                        <td class="readonly-text"><?= htmlspecialchars($t['prestasi_ton']) ?></td>
                                        <td class="readonly-text"><?= htmlspecialchars($t['prestasi_kg']) ?></td>
                                        <td class="readonly-text"><?= htmlspecialchars($t['blok'] ?? '-') ?></td>
                                        <td class="readonly-text"><?= htmlspecialchars($t['luas_ha'] ?? '-') ?></td>
                                        <td>
                                            <select name="status_<?= $id ?>" class="select-status">
                                                <option value="ditinjau" <?= $t['status'] == 'ditinjau' ? 'selected' : '' ?>>Ditinjau</option>
                                                <option value="diterima" <?= $t['status'] == 'diterima' ? 'selected' : '' ?>>Diterima</option>
                                                <option value="ditolak" <?= $t['status'] == 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- FORMAT 3: Potong Buah / Panen -->
            <?php if (count($format3) > 0): ?>
                <div class="card-container">
                    <h3 class="table-title"><span style="color:var(--primary-start)">■</span> Potong Buah / Panen</h3>
                    <div class="table-responsive">
                        <table class="table-logbook">
                            <thead>
                                <tr>
                                    <th rowspan="2">NO</th>
                                    <th rowspan="2">NIK</th>
                                    <th rowspan="2">Nama Karyawan</th>
                                    <th colspan="4">Hasil Janjangan (Diisi Mandor)</th>
                                    <th rowspan="2">Blok</th>
                                    <th rowspan="2">Luas Ha</th>
                                    <th rowspan="2">Verifikasi</th>
                                </tr>
                                <tr>
                                    <th>TBS</th>
                                    <th>Kosong</th>
                                    <th>Brondol</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1;
                                foreach ($format3 as $t): $id = $t['id']; ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td class="readonly-text"><?= htmlspecialchars($t['nik']) ?></td>
                                        <td style="text-align:left; font-weight:700; color:var(--text-dark);"><?= htmlspecialchars($t['karyawan_name']) ?></td>
                                        <td><input type="number" name="tbs_<?= $id ?>" class="input-mini" value="<?= $t['tbs'] ?>"></td>
                                        <td><input type="number" name="kosong_<?= $id ?>" class="input-mini" value="<?= $t['tandan_kosong'] ?>"></td>
                                        <td><input type="number" name="brondol_<?= $id ?>" class="input-mini" value="<?= $t['tandan_brondol'] ?>"></td>
                                        <td><input type="number" name="total_<?= $id ?>" class="input-mini" value="<?= $t['total_tandan'] ?>"></td>
                                        <td class="readonly-text"><?= htmlspecialchars($t['blok'] ?? '-') ?></td>
                                        <td class="readonly-text"><?= htmlspecialchars($t['luas_ha'] ?? '-') ?></td>
                                        <td>
                                            <select name="status_<?= $id ?>" class="select-status">
                                                <option value="ditinjau" <?= $t['status'] == 'ditinjau' ? 'selected' : '' ?>>Ditinjau</option>
                                                <option value="diterima" <?= $t['status'] == 'diterima' ? 'selected' : '' ?>>Diterima</option>
                                                <option value="ditolak" <?= $t['status'] == 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- FORMAT 2: Perawatan Umum -->
            <?php if (count($format2) > 0): ?>
                <div class="card-container">
                    <h3 class="table-title"><span style="color:var(--primary-start)">■</span> Perawatan Umum</h3>
                    <div class="table-responsive">
                        <table class="table-logbook">
                            <thead>
                                <tr>
                                    <th>NO</th>
                                    <th>NIK</th>
                                    <th>Nama Karyawan</th>
                                    <th>Objek Kerja</th>
                                    <th>Blok</th>
                                    <th>Luas Ha</th>
                                    <th>Verifikasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1;
                                foreach ($format2 as $t): $id = $t['id']; ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td class="readonly-text"><?= htmlspecialchars($t['nik']) ?></td>
                                        <td style="text-align:left; font-weight:700; color:var(--text-dark);"><?= htmlspecialchars($t['karyawan_name']) ?></td>
                                        <td class="readonly-text"><?= htmlspecialchars($t['objek_kerja'] ?? '-') ?></td>
                                        <td class="readonly-text"><?= htmlspecialchars($t['blok'] ?? '-') ?></td>
                                        <td class="readonly-text"><?= htmlspecialchars($t['luas_ha'] ?? '-') ?></td>
                                        <td>
                                            <select name="status_<?= $id ?>" class="select-status">
                                                <option value="ditinjau" <?= $t['status'] == 'ditinjau' ? 'selected' : '' ?>>Ditinjau</option>
                                                <option value="diterima" <?= $t['status'] == 'diterima' ? 'selected' : '' ?>>Diterima</option>
                                                <option value="ditolak" <?= $t['status'] == 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- FORMAT 4 & 5... (Disembunyikan jika kosong untuk kerapian) -->

            <button type="submit" name="simpan_semua" class="btn-submit">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                Simpan & Verifikasi Data
            </button>

        <?php else: ?>
            <div class="card-container" style="text-align:center; padding:40px;">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5" style="margin-bottom:12px;">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                </svg>
                <p style="color:#94a3b8; font-size:14px; font-weight:600;">Belum ada objek kerja untuk tanggal ini.</p>
            </div>
        <?php endif; ?>

    </form>
</div>

<?php include 'templates/footer.php'; ?>