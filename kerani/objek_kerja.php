<?php
require '../config/config.php';

// --- Handler: Update Tenaga L/W dari Kerani ---
if (isset($_POST['simpan_tenaga'])) {
    $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal_tugas']);
    $rows = isset($_POST['rows']) ? $_POST['rows'] : [];
    $updated = 0;

    foreach ($rows as $id => $val) {
        $id = (int)$id;
        if (!$id) continue;

        // Dapatkan detail rencana pengawas
        $r_query = mysqli_query($conn, "SELECT mandor_id, objek_kerja, blok, luas_ha FROM rencana_kerja_pengawas WHERE id=$id");
        $rencana = mysqli_fetch_assoc($r_query);
        if (!$rencana) continue;

        $kategori_task = 'perawatan';
        $ok_lower = strtolower($rencana['objek_kerja']);
        if (strpos($ok_lower, 'langsir') !== false) $kategori_task = 'langsir';
        elseif (strpos($ok_lower, 'potong buah') !== false || strpos($ok_lower, 'panen') !== false) $kategori_task = 'potong_buah';
        elseif (strpos($ok_lower, 'muat') !== false) $kategori_task = 'muat_tbs';
        elseif (strpos($ok_lower, 'jaga') !== false) $kategori_task = 'jaga';

        // Hapus penugasan lama untuk rencana_id ini yang masih berstatus 'ditinjau'
        mysqli_query($conn, "DELETE FROM logbook_kinerja WHERE rencana_id = $id AND status = 'ditinjau'");

        // Kumpulkan semua tenaga yang dipilih (L & W)
        $tenaga_l = isset($val['tenaga_l']) && is_array($val['tenaga_l']) ? $val['tenaga_l'] : [];
        $tenaga_w = isset($val['tenaga_w']) && is_array($val['tenaga_w']) ? $val['tenaga_w'] : [];
        $all_tenaga = array_merge($tenaga_l, $tenaga_w);

        foreach ($all_tenaga as $uid) {
            $uid = (int)$uid;
            if ($uid > 0) {
                // Cek apakah karyawan ini sudah punya logbook di tanggal yang sama (mencegah double input di hari sama)
                $cek = mysqli_query($conn, "SELECT id FROM logbook_kinerja WHERE user_id=$uid AND tanggal='$tanggal'");
                if (mysqli_num_rows($cek) == 0) {
                    mysqli_query($conn, "INSERT INTO logbook_kinerja 
                        (user_id, mandor_id, tanggal, blok, luas_ha, objek_kerja, kategori_task, status, rencana_id) 
                        VALUES 
                        ($uid, {$rencana['mandor_id']}, '$tanggal', '{$rencana['blok']}', '{$rencana['luas_ha']}', '{$rencana['objek_kerja']}', '$kategori_task', 'ditinjau', $id)");
                    $updated++;
                } else {
                    // Update jika sudah ada (karyawan dipindah tugas ke rencana ini)
                    mysqli_query($conn, "UPDATE logbook_kinerja 
                        SET mandor_id={$rencana['mandor_id']}, blok='{$rencana['blok']}', luas_ha='{$rencana['luas_ha']}', 
                            objek_kerja='{$rencana['objek_kerja']}', kategori_task='$kategori_task', rencana_id=$id 
                        WHERE user_id=$uid AND tanggal='$tanggal'");
                    $updated++;
                }
            }
        }
    }
    echo json_encode(['success' => true, 'updated' => $updated]);
    exit;
}

// --- Handler: Kirim WA ke Mandor & Karyawan ---
if (isset($_POST['kirim_pesan'])) {
    $tanggal_tugas = mysqli_real_escape_string($conn, $_POST['tanggal_tugas']);
    $tgl_fmt = date('d/m/Y', strtotime($tanggal_tugas));
    $afdeling_kerani = isset($_SESSION['afdeling']) ? mysqli_real_escape_string($conn, $_SESSION['afdeling']) : '';

    $q = mysqli_query($conn, "SELECT r.*, m.name AS nama_mandor, m.no_hp AS hp_mandor
                               FROM rencana_kerja_pengawas r
                               LEFT JOIN users m ON r.mandor_id = m.id
                               WHERE r.tanggal = '$tanggal_tugas'
                               ORDER BY m.name ASC");

    $sent = 0;
    while ($row = mysqli_fetch_assoc($q)) {
        if (!empty($row['hp_mandor'])) {
            $pesan  = "📋 *Rencana Kerja - {$tgl_fmt}*\n\n";
            $pesan .= "Halo *{$row['nama_mandor']}*,\nBerikut rencana kerja besok:\n\n";
            $pesan .= "🔧 *Objek Kerja:* {$row['objek_kerja']}\n";
            $pesan .= "📍 *Blok:* {$row['blok']} | *Luas:* {$row['luas_ha']} Ha\n";
            $pesan .= "👥 *Tenaga:* L {$row['tenaga_l']} | W {$row['tenaga_w']}\n\n";
            $pesan .= "_Pesan otomatis dari Sistem Kerani PT DJL._";
            sendWA($row['hp_mandor'], $pesan);
            $sent++;
        }
    }

    echo json_encode(['success' => true, 'sent' => $sent]);
    exit;
}

include 'templates/header.php';

$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d', strtotime('+1 day'));
$tgl_safe = mysqli_real_escape_string($conn, $tanggal);
$afdeling_kerani = isset($_SESSION['afdeling']) ? mysqli_real_escape_string($conn, $_SESSION['afdeling']) : '';

// Ambil rencana kerja dari pengawas
$q_rencana = mysqli_query($conn, "
    SELECT r.id, r.mandor_id, r.objek_kerja, r.blok, r.luas_ha, r.tenaga_l, r.tenaga_w,
           m.name AS nama_mandor
    FROM rencana_kerja_pengawas r
    LEFT JOIN users m ON r.mandor_id = m.id
    WHERE r.tanggal = '$tgl_safe'
    ORDER BY m.name ASC, r.objek_kerja ASC
");

// Ambil daftar karyawan L dan W (berdasarkan afdeling)
$q_karyawan = mysqli_query($conn, "SELECT id, name, jenis_kelamin FROM users WHERE role='karyawan' AND afdeling='$afdeling_kerani' ORDER BY name ASC");
$karyawan_l = [];
$karyawan_w = [];
while ($k = mysqli_fetch_assoc($q_karyawan)) {
    if ($k['jenis_kelamin'] === 'Perempuan') {
        $karyawan_w[] = $k;
    } else { // Jika null atau Laki-laki, masuk ke Laki-laki
        $karyawan_l[] = $k;
    }
}

// Ambil assignment yang sudah ada di logbook_kinerja berdasarkan rencana_id
$q_logbook = mysqli_query($conn, "
    SELECT l.id, l.rencana_id, l.user_id, u.jenis_kelamin 
    FROM logbook_kinerja l
    JOIN users u ON l.user_id = u.id
    WHERE l.tanggal = '$tgl_safe' AND l.rencana_id IS NOT NULL
");
$logbook_assignments = [];
while ($lb = mysqli_fetch_assoc($q_logbook)) {
    $jk = ($lb['jenis_kelamin'] === 'Perempuan') ? 'W' : 'L';
    $logbook_assignments[$lb['rencana_id']][$jk][] = $lb['user_id'];
}

$rows_rencana = [];
while ($row = mysqli_fetch_assoc($q_rencana)) $rows_rencana[] = $row;

$total_tenaga_l = array_sum(array_column($rows_rencana, 'tenaga_l'));
$total_tenaga_w = array_sum(array_column($rows_rencana, 'tenaga_w'));
$total_tenaga   = $total_tenaga_l + $total_tenaga_w;
?>

<style>
    .ok-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }

    .ok-toolbar-left {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .ok-date-input {
        padding: 10px 16px;
        border: 1.5px solid #cbd5e1;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        color: var(--text-main);
        background: white;
        outline: none;
        cursor: pointer;
        transition: border-color 0.2s;
    }

    .ok-date-input:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }

    .btn-filter {
        padding: 10px 18px;
        background: var(--accent);
        color: white;
        border: none;
        border-radius: 10px;
        font-weight: 700;
        font-size: 13px;
        cursor: pointer;
        transition: background 0.2s;
    }

    .btn-filter:hover {
        background: #2563eb;
    }

    .btn-wa {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: linear-gradient(135deg, #25d366, #128c7e);
        color: white;
        border: none;
        border-radius: 10px;
        font-weight: 700;
        font-size: 13px;
        cursor: pointer;
        transition: opacity 0.2s, transform 0.15s;
        box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3);
    }

    .btn-wa:hover {
        opacity: 0.92;
        transform: translateY(-1px);
    }

    .btn-wa:disabled {
        background: #94a3b8;
        box-shadow: none;
        cursor: not-allowed;
        transform: none;
    }

    .stat-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .stat-mini {
        background: white;
        border-radius: 12px;
        padding: 16px 20px;
        border: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .stat-mini-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    .stat-mini-val {
        font-size: 22px;
        font-weight: 800;
        color: var(--text-main);
    }

    .stat-mini-label {
        font-size: 11px;
        color: var(--text-muted);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .card {
        background: white;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
        overflow: hidden;
    }

    .card-header {
        padding: 18px 24px;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .card-title {
        font-size: 16px;
        font-weight: 700;
        color: var(--text-main);
    }

    .card-badge {
        padding: 4px 12px;
        background: #eff6ff;
        color: #3b82f6;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
    }

    .table-ok {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }

    .table-ok th {
        background: white;
        color: var(--text-muted);
        font-weight: 700;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.5px;
        padding: 14px 16px;
        border-bottom: 2px solid #e2e8f0;
        text-align: left;
        white-space: nowrap;
    }

    .table-ok td {
        padding: 12px 16px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .table-ok tbody tr:last-child td {
        border-bottom: none;
    }

    .table-ok tbody tr:hover {
        background: #f8fafc;
    }

    .input-tenaga {
        width: 70px;
        padding: 7px 10px;
        border: 1.5px solid #e2e8f0;
        border-radius: 7px;
        font-size: 13px;
        font-weight: 700;
        text-align: center;
        color: var(--text-main);
        background: #f8fafc;
        outline: none;
        transition: border-color 0.2s;
    }

    .input-tenaga:focus {
        border-color: var(--accent);
        background: white;
    }

    .input-tenaga.laki {
        border-color: #bfdbfe;
    }

    .input-tenaga.laki:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }

    .input-tenaga.wanita {
        border-color: #fbcfe8;
    }

    .input-tenaga.wanita:focus {
        border-color: #ec4899;
        box-shadow: 0 0 0 3px rgba(236, 72, 153, 0.15);
    }

    .row-no {
        font-weight: 700;
        color: var(--text-muted);
    }

    .mandor-badge {
        display: inline-block;
        padding: 4px 10px;
        background: #fef3c7;
        color: #d97706;
        border-radius: 6px;
        font-weight: 700;
        font-size: 12px;
    }

    .blok-badge {
        display: inline-block;
        padding: 4px 10px;
        background: #f0fdf4;
        color: #16a34a;
        border-radius: 6px;
        font-weight: 700;
        font-size: 12px;
    }

    .luas-val {
        font-weight: 700;
        color: var(--text-main);
    }

    .objek-badge {
        display: inline-block;
        padding: 4px 10px;
        background: #f5f3ff;
        color: #7c3aed;
        border-radius: 6px;
        font-weight: 700;
        font-size: 12px;
    }

    .tenaga-wrap {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .tenaga-label {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .tenaga-label.l {
        color: #3b82f6;
    }

    .tenaga-label.w {
        color: #ec4899;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: var(--text-muted);
    }

    .empty-state i {
        font-size: 40px;
        color: #cbd5e1;
        margin-bottom: 12px;
        display: block;
    }

    .empty-state p {
        font-size: 15px;
        font-weight: 600;
    }

    .empty-state small {
        font-size: 13px;
    }

    .notice-banner {
        background: #eff6ff;
        border: 1.5px solid #bfdbfe;
        border-radius: 10px;
        padding: 12px 18px;
        color: #1d4ed8;
        font-size: 13px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
    }
</style>

<!-- Notice -->
<div class="notice-banner">
    <i class="fa-solid fa-circle-info"></i>
    Objek kerja dibuat oleh <strong>Pengawas</strong>. Kerani hanya dapat mengisi jumlah <strong>Tenaga Laki-laki</strong> dan <strong>Wanita</strong> sesuai realisasi.
</div>

<!-- Toolbar -->
<div class="ok-toolbar">
    <div class="ok-toolbar-left">
        <form method="GET" id="filterForm" style="display:flex;gap:10px;align-items:center;">
            <input type="date" name="tanggal" value="<?= htmlspecialchars($tanggal) ?>" class="ok-date-input" id="tanggalInput">
            <button type="submit" class="btn-filter">
                <i class="fa-solid fa-magnifying-glass" style="margin-right:4px;"></i>
                Tampilkan
            </button>
        </form>
    </div>
    <button class="btn-wa" id="btnKirimWA" <?= count($rows_rencana) == 0 ? 'disabled' : '' ?> onclick="kirimPesanWA()">
        <i class="fa-brands fa-whatsapp" style="font-size:16px;"></i>
        Kirim Pesan ke Mandor
    </button>
</div>

<!-- Stat Mini -->
<div class="stat-row">
    <div class="stat-mini">
        <div class="stat-mini-icon" style="background:#eff6ff;color:#3b82f6;"><i class="fa-solid fa-calendar-days"></i></div>
        <div>
            <div class="stat-mini-val"><?= date('d/m/Y', strtotime($tanggal)) ?></div>
            <div class="stat-mini-label">Tanggal Tugas</div>
        </div>
    </div>
    <div class="stat-mini">
        <div class="stat-mini-icon" style="background:#fef3c7;color:#d97706;"><i class="fa-solid fa-clipboard-list"></i></div>
        <div>
            <div class="stat-mini-val"><?= count($rows_rencana) ?></div>
            <div class="stat-mini-label">Objek Kerja</div>
        </div>
    </div>
    <div class="stat-mini">
        <div class="stat-mini-icon" style="background:#eff6ff;color:#3b82f6;"><i class="fa-solid fa-person"></i></div>
        <div>
            <div class="stat-mini-val"><?= $total_tenaga_l ?></div>
            <div class="stat-mini-label">Tenaga Laki-laki</div>
        </div>
    </div>
    <div class="stat-mini">
        <div class="stat-mini-icon" style="background:#fdf2f8;color:#ec4899;"><i class="fa-solid fa-user"></i></div>
        <div>
            <div class="stat-mini-val"><?= $total_tenaga_w ?></div>
            <div class="stat-mini-label">Tenaga Wanita</div>
        </div>
    </div>
    <div class="stat-mini">
        <div class="stat-mini-icon" style="background:#f0fdf4;color:#16a34a;"><i class="fa-solid fa-users"></i></div>
        <div>
            <div class="stat-mini-val"><?= $total_tenaga ?></div>
            <div class="stat-mini-label">Total Tenaga</div>
        </div>
    </div>
</div>

<!-- Tabel Objek Kerja dari Pengawas -->
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Rencana Kerja dari Pengawas</div>
            <div style="font-size:12px;color:var(--text-muted);margin-top:2px;">Tanggal: <?= date('d F Y', strtotime($tanggal)) ?></div>
        </div>
        <span class="card-badge"><?= count($rows_rencana) ?> Baris</span>
    </div>

    <div style="overflow-x:auto;">
        <table class="table-ok" id="tableOK">
            <thead>
                <tr>
                    <th width="40">No</th>
                    <th>Nama Mandor</th>
                    <th>Objek Kerja</th>
                    <th width="90">Blok</th>
                    <th width="90">Luas (Ha)</th>
                    <th width="200" style="text-align:center;">Tenaga (Isi disini)</th>
                </tr>
            </thead>
            <tbody id="tbodyOK">
                <?php if (count($rows_rencana) > 0): ?>
                    <?php $no = 1;
                    foreach ($rows_rencana as $row): ?>
                        <tr class="row-ok" data-id="<?= $row['id'] ?>">
                            <td class="row-no"><?= $no++ ?></td>
                            <td>
                                <span class="mandor-badge">
                                    <i class="fa-solid fa-user-tie" style="margin-right:4px;"></i>
                                    <?= htmlspecialchars($row['nama_mandor'] ?? '-') ?>
                                </span>
                            </td>
                            <td>
                                <span class="objek-badge"><?= htmlspecialchars($row['objek_kerja']) ?></span>
                            </td>
                            <td>
                                <span class="blok-badge"><?= htmlspecialchars($row['blok']) ?></span>
                            </td>
                            <td>
                                <span class="luas-val"><?= htmlspecialchars($row['luas_ha']) ?> Ha</span>
                            </td>
                            <td>
                                <div class="tenaga-wrap" style="justify-content:center; flex-direction:column; gap:8px;">
                                    <?php
                                    $assigned_l = isset($logbook_assignments[$row['id']]['L']) ? $logbook_assignments[$row['id']]['L'] : [];
                                    $assigned_w = isset($logbook_assignments[$row['id']]['W']) ? $logbook_assignments[$row['id']]['W'] : [];

                                    // Generate selects for Laki-laki
                                    for ($i = 0; $i < (int)$row['tenaga_l']; $i++) {
                                        $sel_val = isset($assigned_l[$i]) ? $assigned_l[$i] : '';
                                        echo '<div style="display:flex; align-items:center; gap:6px;">';
                                        echo '<span class="tenaga-label l">L</span>';
                                        echo '<select class="select-tenaga select-laki" style="padding:6px; border-radius:6px; border:1.5px solid #bfdbfe; font-size:12px; width:150px; outline:none; background:#f8fafc; font-weight:600;">';
                                        echo '<option value="">-- Pilih Karyawan --</option>';
                                        foreach ($karyawan_l as $kw) {
                                            $sel = ($sel_val == $kw['id']) ? 'selected' : '';
                                            echo "<option value=\"{$kw['id']}\" $sel>" . htmlspecialchars($kw['name']) . "</option>";
                                        }
                                        echo '</select></div>';
                                    }

                                    // Generate selects for Wanita
                                    for ($i = 0; $i < (int)$row['tenaga_w']; $i++) {
                                        $sel_val = isset($assigned_w[$i]) ? $assigned_w[$i] : '';
                                        echo '<div style="display:flex; align-items:center; gap:6px;">';
                                        echo '<span class="tenaga-label w">W</span>';
                                        echo '<select class="select-tenaga select-wanita" style="padding:6px; border-radius:6px; border:1.5px solid #fbcfe8; font-size:12px; width:150px; outline:none; background:#f8fafc; font-weight:600;">';
                                        echo '<option value="">-- Pilih Karyawan --</option>';
                                        foreach ($karyawan_w as $kw) {
                                            $sel = ($sel_val == $kw['id']) ? 'selected' : '';
                                            echo "<option value=\"{$kw['id']}\" $sel>" . htmlspecialchars($kw['name']) . "</option>";
                                        }
                                        echo '</select></div>';
                                    }

                                    if ((int)$row['tenaga_l'] == 0 && (int)$row['tenaga_w'] == 0) {
                                        echo '<span style="color:#94a3b8; font-size:12px; font-style:italic;">Tidak ada kuota</span>';
                                    }
                                    ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr id="emptyRow">
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="fa-solid fa-file-circle-exclamation"></i>
                                <p>Belum ada rencana kerja dari Pengawas</p>
                                <small>Pengawas belum membuat rencana kerja untuk tanggal <?= date('d F Y', strtotime($tanggal)) ?></small>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (count($rows_rencana) > 0): ?>
        <div style="padding: 14px 24px; border-top: 1px solid #e2e8f0; display:flex; justify-content:flex-end; gap:10px; background:#f8fafc;">
            <button type="button" class="btn-filter" style="background:#64748b;" onclick="window.location.reload()">Reset</button>
            <button type="button" class="btn-filter" onclick="simpanTenaga()">
                <i class="fa-solid fa-floppy-disk"></i> Simpan Tenaga
            </button>
        </div>
    <?php endif; ?>
</div>

<script>
    const tanggalTugas = '<?= $tanggal ?>';

    function hitungTotal() {
        // recalculate totals live
        let totalL = 0,
            totalW = 0;
        document.querySelectorAll('.input-tenaga.laki').forEach(inp => totalL += parseInt(inp.value || 0));
        document.querySelectorAll('.input-tenaga.wanita').forEach(inp => totalW += parseInt(inp.value || 0));
    }

    // ============= SIMPAN TENAGA =============
    function simpanTenaga() {
        const rows = document.querySelectorAll('tr.row-ok');
        if (rows.length === 0) return;

        const fd = new FormData();
        fd.append('simpan_tenaga', 1);
        fd.append('tanggal_tugas', tanggalTugas);

        rows.forEach(row => {
            const id = row.dataset.id;
            const selectsL = row.querySelectorAll('.select-laki');
            selectsL.forEach(s => {
                if (s.value) fd.append(`rows[${id}][tenaga_l][]`, s.value);
            });
            const selectsW = row.querySelectorAll('.select-wanita');
            selectsW.forEach(s => {
                if (s.value) fd.append(`rows[${id}][tenaga_w][]`, s.value);
            });
        });

        Swal.fire({
            title: 'Menyimpan...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        fetch('objek_kerja.php', {
                method: 'POST',
                body: fd
            })
            .then(r => r.json())
            .then(data => {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil Disimpan!',
                    text: `${data.updated} rencana kerja telah diperbarui.`,
                    confirmButtonText: 'OK'
                }).then(() => window.location.reload());
            }).catch(() => {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: 'Terjadi kesalahan saat menyimpan data.'
                });
            });
    }

    // ============= KIRIM WA =============
    function kirimPesanWA() {
        Swal.fire({
            icon: 'question',
            title: 'Kirim Pesan WhatsApp?',
            html: `Pesan rencana kerja akan dikirim ke semua <b>mandor</b> untuk tanggal <b>${new Date(tanggalTugas + 'T00:00:00').toLocaleDateString('id-ID', {day:'2-digit',month:'long',year:'numeric'})}</b>.`,
            showCancelButton: true,
            confirmButtonText: '✅ Ya, Kirim',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#25d366'
        }).then(result => {
            if (!result.isConfirmed) return;

            const fd = new FormData();
            fd.append('kirim_pesan', 1);
            fd.append('tanggal_tugas', tanggalTugas);

            Swal.fire({
                title: 'Mengirim...',
                text: 'Harap tunggu sebentar.',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch('objek_kerja.php', {
                    method: 'POST',
                    body: fd
                })
                .then(r => r.json())
                .then(data => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Pesan Terkirim!',
                        text: `${data.sent} pesan WhatsApp berhasil dikirim ke mandor.`
                    });
                }).catch(() => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Terjadi kesalahan saat mengirim pesan.'
                    });
                });
        });
    }
</script>

<?php include 'templates/footer.php'; ?>