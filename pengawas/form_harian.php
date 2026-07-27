<?php
require '../config/config.php';
include 'templates/header.php';

// Filter parameter
$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');
$peran = isset($_GET['peran']) ? $_GET['peran'] : 'karyawan';
$pengawas_id = $_SESSION['user_id'];
$afdeling_pengawas = isset($_SESSION['afdeling']) ? mysqli_real_escape_string($conn, $_SESSION['afdeling']) : '';

// Ambil daftar karyawan dari afdeling pengawas
$peran_safe = mysqli_real_escape_string($conn, $peran);
if (!empty($afdeling_pengawas)) {
    $query_users = mysqli_query($conn, "SELECT id, nik, name, role FROM users WHERE role='$peran_safe' AND afdeling='$afdeling_pengawas' ORDER BY name ASC");
} else {
    $query_users = mysqli_query($conn, "SELECT id, nik, name, role FROM users WHERE role='$peran_safe' ORDER BY name ASC");
}

// Ambil daftar mandor dari DB
$query_mandor = mysqli_query($conn, "SELECT name FROM users WHERE role='mandor'" . (!empty($afdeling_pengawas) ? " AND afdeling='$afdeling_pengawas'" : "") . " ORDER BY name ASC");
$list_mandor = [];
while($m = mysqli_fetch_assoc($query_mandor)) { $list_mandor[] = $m['name']; }

// --- Data Master (blok & objek masih statis karena belum ada tabel khusus) ---
$list_objek = [
    'Langsir manual', 'Membabat gawangan', 'Kutip brondolan', 
    'Rawat jalan', 'Korek janjangan', 'Potong buah / panen', 
    'Muat TBS ke truk', 'Muat TBS ke jonder', 'Jaga genset', 
    'Jaga cuaca berat', 'Jaga buah'
];
$list_blok = [
    'H.39' => '8.66', 'H.40' => '0.41', 'I.39' => '29.96', 
    'I.40' => '26.18', 'J.39' => '31.01', 'J.40' => '27.05', 
    'K.39' => '30.98', 'L.39' => '31.17', 'L.40' => '30.22'
];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['rows'])) {
    $tgl_safe = mysqli_real_escape_string($conn, $tanggal);
    $saved = 0;
    foreach ($_POST['rows'] as $row_data) {
        $user_id_row = (int)$row_data['user_id'];
        $blok = mysqli_real_escape_string($conn, $row_data['blok'] ?? '');
        $luas = mysqli_real_escape_string($conn, $row_data['luas'] ?? '');
        $objek = mysqli_real_escape_string($conn, $row_data['objek'] ?? '');
        $mandor = mysqli_real_escape_string($conn, $row_data['mandor'] ?? '');
        $jam = (float)($row_data['jam'] ?? 0);

        if ($user_id_row && !empty($objek)) {
            // Cek duplikat
            $cek = mysqli_query($conn, "SELECT id FROM logbook_kinerja WHERE user_id=$user_id_row AND tanggal='$tgl_safe' AND objek_kerja='$objek' AND blok='$blok'");
            if (mysqli_num_rows($cek) == 0) {
                mysqli_query($conn, "INSERT INTO logbook_kinerja (user_id, tanggal, blok, luas_ha, objek_kerja, jumlah_jam_kerja, status) 
                                     VALUES ($user_id_row, '$tgl_safe', '$blok', '$luas', '$objek', $jam, 'ditinjau')");
                $saved++;
            }
        }
    }
    swalRedirect("$saved baris laporan berhasil disimpan!", 'index.php', 'success');
    exit;
}
?>

<style>
    .card-container {
        background: #ffffff;
        border-radius: 16px;
        padding: 20px 16px;
        box-shadow: 0 4px 20px rgba(54, 72, 217, 0.05);
        border: 1px solid #f1f5f9;
        margin-bottom: 20px;
    }

    .form-select, .form-input {
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
    
    .form-select:focus, .form-input:focus {
        border-color: var(--primary-start);
        box-shadow: 0 0 0 4px rgba(66, 88, 255, 0.1);
        background: white;
    }

    .form-group {
        margin-bottom: 12px;
    }

    .table-responsive {
        width: 100%;
        overflow-x: auto;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        -webkit-overflow-scrolling: touch;
        margin-top: 10px;
        background: white;
    }

    .table-absen {
        border-collapse: collapse;
        white-space: nowrap;
        font-size: 12px;
        min-width: 1000px; /* Force wide table */
    }

    .table-absen th, .table-absen td {
        padding: 12px 10px;
        border: 1px solid #e2e8f0;
        vertical-align: middle;
    }

    .table-absen th {
        background-color: var(--primary-light);
        color: var(--primary-end);
        font-weight: 800;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .table-absen tbody tr:nth-child(even) { background: #f8fafc; }

    /* Kolom Input dalam Tabel */
    .table-absen select, .table-absen input {
        padding: 8px 10px;
        border-radius: 6px;
        border: 1px solid #cbd5e1;
        font-size: 12px;
        min-width: 120px;
    }
    .table-absen select:focus, .table-absen input:focus {
        border-color: var(--primary-start);
        outline: none;
    }
    .input-readonly {
        background: #f1f5f9;
        color: #64748b;
        font-weight: 600;
        min-width: 80px !important;
        border-color: #e2e8f0 !important;
    }

    .status-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-weight: 800;
        font-size: 10px;
    }
    .status-h { background-color: #dcfce7; color: #166534; }
    .status-a { background-color: #fee2e2; color: #991b1b; }
    .status-i { background-color: #e0f2fe; color: #075985; }
    .status-s { background-color: #ede9fe; color: #5b21b6; }
    .status-c { background-color: #ffedd5; color: #9a3412; }

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
        margin-top: 16px;
        transition: all 0.2s;
        box-shadow: 0 4px 15px rgba(66, 88, 255, 0.25);
    }
    .btn-submit:active { transform: scale(0.98); box-shadow: none; }

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
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        Kembali
    </a>

    <h2 class="page-title" style="text-align: left; margin-bottom: 16px; font-size: 20px;">
        <span style="display:block; font-size:12px; color:var(--text-muted); font-weight:600; text-transform:uppercase; margin-bottom:4px;">Kehadiran Hari Ini</span>
        Form Laporan Absensi Harian <?= ucfirst($peran) ?>
    </h2>

    <div class="card-container">
        
        <!-- Filter Top -->
        <form id="filterForm" method="GET">
            <div style="display: flex; gap: 10px; margin-bottom: 16px;">
                <div style="flex: 1;">
                    <label style="font-size: 11px; font-weight: 700; color: #64748b; margin-bottom:4px; display:block;">Pilih Tanggal</label>
                    <input type="date" name="tanggal" class="form-input" value="<?= $tanggal ?>" onchange="document.getElementById('filterForm').submit()">
                </div>
                <div style="flex: 1;">
                    <label style="font-size: 11px; font-weight: 700; color: #64748b; margin-bottom:4px; display:block;">Pilih Peran</label>
                    <select name="peran" class="form-select" onchange="document.getElementById('filterForm').submit()">
                        <option value="karyawan" <?= $peran == 'karyawan' ? 'selected' : '' ?>>Karyawan</option>
                        <option value="mandor" <?= $peran == 'mandor' ? 'selected' : '' ?>>Mandor</option>
                        <option value="pengawas" <?= $peran == 'pengawas' ? 'selected' : '' ?>>Pengawas</option>
                    </select>
                </div>
            </div>
        </form>

        <?php if ($peran != 'mandor'): ?>
        <p style="font-size: 11px; color: var(--text-muted); font-style: italic; margin-bottom: 8px;">* Geser tabel ke kanan untuk melengkapi form alokasi kerja.</p>
        <?php endif; ?>

        <!-- Tabel Form Harian -->
        <form method="POST" action="form_harian.php?tanggal=<?= $tanggal ?>&peran=<?= $peran ?>">
            <div class="table-responsive">
                <table class="table-absen">
                    <thead>
                        <tr>
                            <th style="width:30px;">NO</th>
                            <th>NIK</th>
                            <th>NAMA</th>
                            <th>STATUS<br>KEHADIRAN</th>
                            <?php if ($peran != 'mandor'): ?>
                            <!-- Kolom Input Pengawas -->
                            <th>MANDOR</th>
                            <th>OBJEK KERJA</th>
                            <th>BLOK</th>
                            <th>LUAS HA</th>
                            <th>JAM KERJA</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        $tgl_safe = mysqli_real_escape_string($conn, $tanggal);
                        if(mysqli_num_rows($query_users) > 0):
                            while($user = mysqli_fetch_assoc($query_users)): 
                                // Ambil status absensi real dari DB
                                $q_status = mysqli_query($conn, "SELECT status_kehadiran FROM absensis WHERE user_id='{$user['id']}' AND tanggal='$tgl_safe'");
                                $absen_row = mysqli_fetch_assoc($q_status);
                                $hadir = $absen_row ? true : false;
                                $status_absen = $absen_row ? $absen_row['status_kehadiran'] : 'Belum Absen';
                        ?>
                            <tr>
                                <td style="text-align:center;"><?= $no++ ?></td>
                                <td><?= htmlspecialchars($user['nik']) ?></td>
                                <td style="font-weight:700; color:var(--text-dark);"><?= htmlspecialchars($user['name']) ?></td>
                                <td style="text-align:center;">
                                    <?php 
                                    if($hadir) {
                                        $sa = strtolower($status_absen);
                                        $badge_class = 'status-h';
                                        if (in_array($sa, ['alpha', 'alpa'])) $badge_class = 'status-a';
                                        elseif ($sa == 'izin') $badge_class = 'status-i';
                                        elseif ($sa == 'sakit') $badge_class = 'status-s';
                                        elseif ($sa == 'cuti') $badge_class = 'status-c';
                                        
                                        echo "<span class=\"status-badge {$badge_class}\">" . ucfirst($sa) . "</span>";
                                    } else {
                                        echo '<span class="status-badge status-a">Alpha</span>';
                                    }
                                    ?>
                                </td>
                                <?php if ($peran != 'mandor'): ?>
                                <input type="hidden" name="rows[<?= $no-1 ?>][user_id]" value="<?= $user['id'] ?>">
                                <!-- Select Mandor -->
                                <td>
                                    <select name="rows[<?= $no-1 ?>][mandor]">
                                        <option value="">-- Pilih Mandor --</option>
                                        <?php foreach($list_mandor as $m): ?>
                                            <option value="<?= $m ?>"><?= $m ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>

                                <!-- Select Objek Kerja -->
                                <td>
                                    <select name="rows[<?= $no-1 ?>][objek]">
                                        <option value="">-- Objek Kerja --</option>
                                        <?php foreach($list_objek as $o): ?>
                                            <option value="<?= $o ?>"><?= $o ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>

                                <!-- Select Blok (onchange akan mengisi luas) -->
                                <td>
                                    <select name="rows[<?= $no-1 ?>][blok]" onchange="isiLuas(this, 'luas_<?= $user['id'] ?>', 'luas_input_<?= $user['id'] ?>')">
                                        <option value="" data-luas="">-- Pilih Blok --</option>
                                        <?php foreach($list_blok as $blok => $luas): ?>
                                            <option value="<?= $blok ?>" data-luas="<?= $luas ?>"><?= $blok ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>

                                <!-- Input Luas Ha (Readonly / Autofill) -->
                                <td>
                                    <input type="text" id="luas_<?= $user['id'] ?>" name="rows[<?= $no-1 ?>][luas]" class="input-readonly" readonly placeholder="0.00">
                                </td>
                                
                                <!-- Jam Kerja -->
                                <td>
                                    <input type="number" step="0.5" min="0" max="24" name="rows[<?= $no-1 ?>][jam]" class="form-input" placeholder="0" style="width:60px;">
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php 
                            endwhile;
                        else: 
                        ?>
                            <tr>
                                <td colspan="<?= $peran == 'mandor' ? 4 : 9 ?>" style="text-align:center; padding:30px; color:var(--text-muted);">Belum ada karyawan di afdeling ini.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($peran != 'mandor'): ?>
            <button type="submit" class="btn-submit">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px; vertical-align: middle;">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                Simpan Laporan Kerja
            </button>
            <?php endif; ?>
        </form>

    </div>
</div>

<script>
    // JS Script untuk Autofill Luas berdasarkan Blok yang dipilih
    function isiLuas(selectObj, targetInputId) {
        var selectedOption = selectObj.options[selectObj.selectedIndex];
        var luas = selectedOption.getAttribute('data-luas');
        document.getElementById(targetInputId).value = luas ? luas : '';
    }
</script>

<?php include 'templates/footer.php'; ?>
