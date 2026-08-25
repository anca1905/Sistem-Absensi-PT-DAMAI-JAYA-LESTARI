<?php
require '../config/config.php';

// --- Handler: Simpan Penugasan Karyawan ---
if (isset($_POST['simpan_penugasan'])) {
    $tanggal_tugas = mysqli_real_escape_string($conn, $_POST['tanggal_tugas']);
    $mandor_id = (int)$_POST['mandor_id'];
    $objek_kerja = mysqli_real_escape_string($conn, $_POST['objek_kerja']);
    $blok = mysqli_real_escape_string($conn, $_POST['blok']);
    $luas_ha = mysqli_real_escape_string($conn, $_POST['luas_ha']);
    $karyawan_ids = isset($_POST['karyawan_ids']) ? $_POST['karyawan_ids'] : [];
    $kategori_task = 'perawatan';
    $ok_lower = strtolower($objek_kerja);
    if (strpos($ok_lower, 'langsir') !== false) $kategori_task = 'langsir';
    elseif (strpos($ok_lower, 'potong buah') !== false || strpos($ok_lower, 'panen') !== false) $kategori_task = 'potong_buah';
    elseif (strpos($ok_lower, 'muat') !== false) $kategori_task = 'muat_tbs';
    elseif (strpos($ok_lower, 'jaga') !== false) $kategori_task = 'jaga';
    $mandor_val = $mandor_id > 0 ? $mandor_id : 'NULL';
    $saved = 0;
    foreach ($karyawan_ids as $kid) {
        $kid = (int)$kid;
        if (!$kid) continue;
        $cek = mysqli_query($conn, "SELECT id FROM logbook_kinerja WHERE user_id=$kid AND tanggal='$tanggal_tugas'");
        if (mysqli_num_rows($cek) == 0) {
            mysqli_query($conn, "INSERT INTO logbook_kinerja (user_id, mandor_id, tanggal, blok, luas_ha, objek_kerja, kategori_task, jumlah_jam_kerja, status)
                                 VALUES ($kid, $mandor_val, '$tanggal_tugas', '$blok', '$luas_ha', '$objek_kerja', '$kategori_task', 7.0, 'ditinjau')");
        } else {
            mysqli_query($conn, "UPDATE logbook_kinerja SET mandor_id=$mandor_val, blok='$blok', luas_ha='$luas_ha', objek_kerja='$objek_kerja', kategori_task='$kategori_task'
                                 WHERE user_id=$kid AND tanggal='$tanggal_tugas'");
        }
        $saved++;
    }
    echo json_encode(['success' => true, 'saved' => $saved]);
    exit;
}

// --- Handler: Kirim WA ke Mandor & Karyawan ---
if (isset($_POST['kirim_pesan'])) {
    $tanggal_tugas = mysqli_real_escape_string($conn, $_POST['tanggal_tugas']);
    $tgl_fmt = date('d/m/Y', strtotime($tanggal_tugas));
    $afdeling_kerani = isset($_SESSION['afdeling']) ? mysqli_real_escape_string($conn, $_SESSION['afdeling']) : '';

    // Ambil semua logbook tanggal tersebut
    $q = mysqli_query($conn, "SELECT lk.*, u.name AS nama_karyawan, u.no_hp AS hp_karyawan,
                                     m.name AS nama_mandor, m.no_hp AS hp_mandor
                              FROM logbook_kinerja lk
                              JOIN users u ON lk.user_id = u.id
                              LEFT JOIN users m ON lk.mandor_id = m.id
                              WHERE lk.tanggal = '$tanggal_tugas'
                              " . (!empty($afdeling_kerani) ? "AND u.afdeling='$afdeling_kerani'" : "") . "
                              ORDER BY lk.mandor_id, lk.objek_kerja");

    $mandor_data = [];
    $karyawan_notif = [];

    while ($row = mysqli_fetch_assoc($q)) {
        $mid = $row['mandor_id'] ?: 'tanpa_mandor';
        if (!isset($mandor_data[$mid])) {
            $mandor_data[$mid] = [
                'nama_mandor' => $row['nama_mandor'] ?: 'Tanpa Mandor',
                'hp_mandor'   => $row['hp_mandor'],
                'objek_kerja' => $row['objek_kerja'],
                'blok'        => $row['blok'],
                'luas_ha'     => $row['luas_ha'],
                'anggota'     => []
            ];
        }
        $mandor_data[$mid]['anggota'][] = $row['nama_karyawan'];

        // Notif ke karyawan
        if (!empty($row['hp_karyawan'])) {
            $karyawan_notif[] = [
                'nama' => $row['nama_karyawan'],
                'hp'   => $row['hp_karyawan'],
                'objek' => $row['objek_kerja'],
                'blok'  => $row['blok'],
                'luas'  => $row['luas_ha'],
                'mandor' => $row['nama_mandor'] ?: '-',
            ];
        }
    }

    $sent = 0;
    // Kirim ke mandor
    foreach ($mandor_data as $data) {
        if (!empty($data['hp_mandor'])) {
            $anggota_list = implode(', ', $data['anggota']);
            $pesan = "📋 *Penugasan Kerja - {$tgl_fmt}*\n\n";
            $pesan .= "Halo *{$data['nama_mandor']}*,\nBerikut adalah penugasan untuk besok:\n\n";
            $pesan .= "🔧 *Objek Kerja:* {$data['objek_kerja']}\n";
            $pesan .= "📍 *Blok:* {$data['blok']} | *Luas:* {$data['luas_ha']} Ha\n";
            $pesan .= "👥 *Anggota Tim:*\n{$anggota_list}\n\n";
            $pesan .= "_Pesan otomatis dari Sistem Kerani PT DJL._";
            sendWA($data['hp_mandor'], $pesan);
            $sent++;
        }
    }
    // Kirim ke karyawan
    foreach ($karyawan_notif as $k) {
        $pesan = "📋 *Penugasan Kerja - {$tgl_fmt}*\n\n";
        $pesan .= "Halo *{$k['nama']}*,\nAnda ditugaskan untuk besok:\n\n";
        $pesan .= "🔧 *Objek Kerja:* {$k['objek']}\n";
        $pesan .= "📍 *Blok:* {$k['blok']} | *Luas:* {$k['luas']} Ha\n";
        $pesan .= "👷 *Mandor:* {$k['mandor']}\n\n";
        $pesan .= "Harap datang tepat waktu. Terima kasih! 🙏\n_Pesan otomatis Sistem PT DJL._";
        sendWA($k['hp'], $pesan);
        $sent++;
    }

    echo json_encode(['success' => true, 'sent' => $sent]);
    exit;
}

include 'templates/header.php';

$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d', strtotime('+1 day'));
$afdeling_kerani = isset($_SESSION['afdeling']) ? mysqli_real_escape_string($conn, $_SESSION['afdeling']) : '';

// Ambil ringkasan objek kerja per mandor per blok
$tgl_safe = mysqli_real_escape_string($conn, $tanggal);
$q_summary = mysqli_query($conn, "
    SELECT 
        lk.mandor_id,
        m.name AS nama_mandor,
        lk.objek_kerja,
        lk.blok,
        MAX(lk.luas_ha) AS luas_ha,
        COUNT(lk.user_id) AS jumlah_tenaga,
        GROUP_CONCAT(lk.user_id ORDER BY lk.user_id) AS user_ids,
        GROUP_CONCAT(u.name ORDER BY u.name SEPARATOR '|||') AS nama_karyawan_list
    FROM logbook_kinerja lk
    LEFT JOIN users m ON lk.mandor_id = m.id
    LEFT JOIN users u ON lk.user_id = u.id
    WHERE lk.tanggal = '$tgl_safe'
    " . (!empty($afdeling_kerani) ? "AND u.afdeling='$afdeling_kerani'" : "") . "
    GROUP BY lk.mandor_id, lk.objek_kerja, lk.blok
    ORDER BY m.name ASC, lk.objek_kerja ASC
");

// Ambil daftar mandor (afdeling kerani)
$q_mandor = mysqli_query($conn, "SELECT id, name FROM users WHERE role='mandor'" . (!empty($afdeling_kerani) ? " AND afdeling='$afdeling_kerani'" : "") . " ORDER BY name ASC");
$list_mandor = [];
while ($m = mysqli_fetch_assoc($q_mandor)) $list_mandor[] = $m;

// Ambil daftar karyawan (afdeling kerani)
$q_karyawan = mysqli_query($conn, "SELECT id, name, nik FROM users WHERE role='karyawan'" . (!empty($afdeling_kerani) ? " AND afdeling='$afdeling_kerani'" : "") . " ORDER BY name ASC");
$list_karyawan = [];
while ($k = mysqli_fetch_assoc($q_karyawan)) $list_karyawan[] = $k;

// List objek kerja
$list_objek = [
    'Langsir manual',
    'Membabat gawangan',
    'Semprot pingan',
    'Rawat jalan',
    'Kotrek anyangan',
    'Panen',
    'Potong buah',
    'Kutip brondolan',
    'Muat TBS ke truk',
    'Muat TBS ke jondol'
];
$list_blok = [
    'H.39' => '8.66',  'H.40' => '0.91',
    'I.39' => '29.26', 'I.40' => '26.18',
    'J.39' => '31.01', 'J.40' => '27.05',
    'K.39' => '20.98', 'K.40' => '28.52',
    'L.39' => '31.17', 'L.40' => '17.74'
];

$rows_summary = [];
while ($row = mysqli_fetch_assoc($q_summary)) $rows_summary[] = $row;
$total_tenaga_all = array_sum(array_column($rows_summary, 'jumlah_tenaga'));
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
    .ok-toolbar-left { display: flex; align-items: center; gap: 10px; }
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
    .ok-date-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }
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
    .btn-filter:hover { background: #2563eb; }
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
    .btn-wa:hover { opacity: 0.92; transform: translateY(-1px); }
    .btn-wa:disabled { background: #94a3b8; box-shadow: none; cursor: not-allowed; transform: none; }
    .btn-tambah-baris {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        background: #f0fdf4;
        color: #16a34a;
        border: 1.5px dashed #86efac;
        border-radius: 8px;
        font-weight: 700;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.2s;
        margin: 16px 24px;
    }
    .btn-tambah-baris:hover { background: #dcfce7; border-color: #4ade80; }

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
        width: 44px; height: 44px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px;
    }
    .stat-mini-val { font-size: 22px; font-weight: 800; color: var(--text-main); }
    .stat-mini-label { font-size: 11px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }

    .card {
        background: white;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 15px rgba(0,0,0,0.02);
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
    .card-title { font-size: 16px; font-weight: 700; color: var(--text-main); }
    .card-badge {
        padding: 4px 12px;
        background: #eff6ff;
        color: #3b82f6;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
    }

    .table-ok { width: 100%; border-collapse: collapse; font-size: 13px; }
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
    .table-ok tbody tr:last-child td { border-bottom: none; }
    .table-ok tbody tr:hover { background: #f8fafc; }

    .table-ok select, .table-ok input[type="text"], .table-ok input[type="number"] {
        padding: 7px 10px;
        border: 1.5px solid #e2e8f0;
        border-radius: 7px;
        font-size: 12px;
        font-weight: 600;
        color: var(--text-main);
        background: #f8fafc;
        outline: none;
        width: 100%;
        font-family: inherit;
        transition: border-color 0.2s;
    }
    .table-ok select:focus, .table-ok input:focus { border-color: var(--accent); background: white; }

    /* Tombol tenaga (klik buka popup) */
    .btn-tenaga {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        min-width: 70px;
        padding: 6px 12px;
        background: #eff6ff;
        color: #2563eb;
        border: 1.5px solid #bfdbfe;
        border-radius: 8px;
        font-weight: 700;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.15s;
    }
    .btn-tenaga:hover { background: #dbeafe; border-color: #93c5fd; transform: scale(1.04); }
    .btn-tenaga .arrow { font-size: 10px; color: #93c5fd; }
    .btn-tenaga.has-data { background: #f0fdf4; color: #16a34a; border-color: #86efac; }
    .btn-tenaga.has-data .arrow { color: #86efac; }

    .row-no { font-weight: 700; color: var(--text-muted); }
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
    .luas-val { font-weight: 700; color: var(--text-main); }
    .objek-badge {
        display: inline-block;
        padding: 4px 10px;
        background: #f5f3ff;
        color: #7c3aed;
        border-radius: 6px;
        font-weight: 700;
        font-size: 12px;
    }

    .btn-hapus-baris {
        padding: 5px 10px;
        background: #fee2e2;
        color: #ef4444;
        border: 1px solid #fecaca;
        border-radius: 6px;
        cursor: pointer;
        font-size: 12px;
        font-weight: 600;
        transition: all 0.15s;
    }
    .btn-hapus-baris:hover { background: #fecaca; }

    /* MODAL POPUP KARYAWAN */
    .modal-overlay-ok {
        display: none;
        position: fixed; inset: 0;
        background: rgba(15, 23, 42, 0.55);
        z-index: 9999;
        justify-content: center;
        align-items: center;
        backdrop-filter: blur(4px);
    }
    .modal-ok {
        background: white;
        border-radius: 16px;
        width: 100%;
        max-width: 460px;
        max-height: 85vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        animation: slideUpModal 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    @keyframes slideUpModal {
        from { opacity: 0; transform: translateY(20px) scale(0.97); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    .modal-ok-header {
        padding: 18px 20px;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
    }
    .modal-ok-title { font-size: 15px; font-weight: 700; color: var(--text-main); }
    .modal-ok-subtitle { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
    .modal-ok-close {
        background: none; border: none;
        font-size: 22px; cursor: pointer;
        color: #64748b; line-height: 1;
        padding: 2px 6px; border-radius: 6px;
        transition: background 0.15s;
    }
    .modal-ok-close:hover { background: #f1f5f9; }

    .modal-ok-search {
        padding: 12px 16px;
        border-bottom: 1px solid #f1f5f9;
        flex-shrink: 0;
    }
    .modal-ok-search input {
        width: 100%;
        padding: 8px 12px;
        border: 1.5px solid #e2e8f0;
        border-radius: 8px;
        font-size: 13px;
        outline: none;
        font-family: inherit;
        transition: border-color 0.2s;
    }
    .modal-ok-search input:focus { border-color: var(--accent); }

    .modal-ok-list {
        overflow-y: auto;
        flex: 1;
        padding: 8px 0;
    }
    .modal-ok-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 16px;
        cursor: pointer;
        transition: background 0.1s;
    }
    .modal-ok-item:hover { background: #f8fafc; }
    .modal-ok-item input[type="checkbox"] { width: 17px; height: 17px; cursor: pointer; accent-color: var(--accent); }
    .modal-ok-item-name { font-weight: 600; font-size: 13px; color: var(--text-main); }
    .modal-ok-item-nik { font-size: 11px; color: var(--text-muted); }
    .modal-ok-item.checked { background: #eff6ff; }

    .modal-ok-footer {
        padding: 14px 16px;
        border-top: 1px solid #e2e8f0;
        background: #f8fafc;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
        gap: 10px;
    }
    .modal-ok-count { font-size: 13px; color: var(--text-muted); font-weight: 600; }
    .modal-ok-count span { color: var(--accent); font-weight: 800; }
    .btn-ok-apply {
        padding: 9px 20px;
        background: var(--accent);
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 700;
        font-size: 13px;
        cursor: pointer;
        transition: background 0.2s;
    }
    .btn-ok-apply:hover { background: #2563eb; }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: var(--text-muted);
    }
    .empty-state svg { opacity: 0.3; margin-bottom: 16px; }
    .empty-state p { font-size: 15px; font-weight: 600; }
    .empty-state small { font-size: 13px; }
</style>

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
    <button class="btn-wa" id="btnKirimWA" <?= count($rows_summary) == 0 ? 'disabled' : '' ?> onclick="kirimPesanWA()">
        <i class="fa-brands fa-whatsapp" style="font-size:16px;"></i>
        Kirim Pesan ke Semua
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
            <div class="stat-mini-val"><?= count($rows_summary) ?></div>
            <div class="stat-mini-label">Objek Kerja</div>
        </div>
    </div>
    <div class="stat-mini">
        <div class="stat-mini-icon" style="background:#f0fdf4;color:#16a34a;"><i class="fa-solid fa-users"></i></div>
        <div>
            <div class="stat-mini-val"><?= $total_tenaga_all ?></div>
            <div class="stat-mini-label">Total Tenaga</div>
        </div>
    </div>
    <div class="stat-mini">
        <div class="stat-mini-icon" style="background:#f5f3ff;color:#7c3aed;"><i class="fa-solid fa-leaf"></i></div>
        <div>
            <div class="stat-mini-val"><?= count($list_karyawan) ?></div>
            <div class="stat-mini-label">Karyawan Tersedia</div>
        </div>
    </div>
</div>

<!-- Tabel Objek Kerja -->
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Laporan Objek Kerja</div>
            <div style="font-size:12px;color:var(--text-muted);margin-top:2px;">Tanggal: <?= date('d F Y', strtotime($tanggal)) ?></div>
        </div>
        <span class="card-badge"><?= count($rows_summary) ?> Baris</span>
    </div>

    <div style="overflow-x:auto;">
        <table class="table-ok" id="tableOK">
            <thead>
                <tr>
                    <th width="40">No</th>
                    <th>Nama Mandor</th>
                    <th>Objek Kerja</th>
                    <th width="100">Tenaga</th>
                    <th width="90">Blok</th>
                    <th width="90">Luas (Ha)</th>
                    <th width="60">Hapus</th>
                </tr>
            </thead>
            <tbody id="tbodyOK">
                <?php if (count($rows_summary) > 0): ?>
                    <?php $no = 1; foreach ($rows_summary as $row): 
                        $assigned_ids = $row['user_ids'] ? explode(',', $row['user_ids']) : [];
                        $assigned_names = $row['nama_karyawan_list'] ? explode('|||', $row['nama_karyawan_list']) : [];
                    ?>
                    <tr class="row-ok" data-row-id="<?= $no ?>">
                        <td class="row-no"><?= $no ?></td>
                        <td>
                            <select name="mandor_id[]" class="sel-mandor">
                                <option value="">— Pilih Mandor —</option>
                                <?php foreach ($list_mandor as $m): ?>
                                <option value="<?= $m['id'] ?>" <?= $row['mandor_id'] == $m['id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <select name="objek_kerja[]" class="sel-objek">
                                <?php foreach ($list_objek as $obj): ?>
                                <option value="<?= htmlspecialchars($obj) ?>" <?= $row['objek_kerja'] == $obj ? 'selected' : '' ?>><?= htmlspecialchars($obj) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td style="text-align:center;">
                            <button type="button" 
                                class="btn-tenaga <?= count($assigned_ids) > 0 ? 'has-data' : '' ?>"
                                data-row="<?= $no ?>"
                                data-selected='<?= json_encode($assigned_ids) ?>'
                                onclick="bukaPopupKaryawan(this)">
                                <span class="tenaga-count"><?= count($assigned_ids) ?></span>
                                <span class="arrow">▼</span>
                            </button>
                            <input type="hidden" name="karyawan_ids[<?= $no ?>]" class="hidden-karyawan-ids" value="<?= implode(',', $assigned_ids) ?>">
                        </td>
                        <td>
                            <select name="blok[]" class="sel-blok" onchange="updateLuas(this)">
                                <option value="">— Pilih —</option>
                                <?php foreach ($list_blok as $blok_name => $luas_val): ?>
                                <option value="<?= $blok_name ?>" data-luas="<?= $luas_val ?>" <?= $row['blok'] == $blok_name ? 'selected' : '' ?>><?= $blok_name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="number" name="luas_ha[]" class="inp-luas" step="0.01" value="<?= $row['luas_ha'] ?>" placeholder="Ha">
                        </td>
                        <td style="text-align:center;">
                            <button type="button" class="btn-hapus-baris" onclick="hapusBaris(this)"><i class="fa-solid fa-trash-can"></i></button>
                        </td>
                    </tr>
                    <?php $no++; endforeach; ?>
                <?php else: ?>
                    <tr id="emptyRow">
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="fa-solid fa-file-circle-exclamation" style="font-size:40px; color:#cbd5e1; margin-bottom:12px; display:block;"></i>
                                <p>Belum ada data objek kerja</p>
                                <small>Tambah baris baru atau tunggu laporan dari Pengawas</small>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <button type="button" class="btn-tambah-baris" onclick="tambahBaris()">
        <i class="fa-solid fa-plus" style="margin-right:4px;"></i>
        Tambah Baris Objek Kerja
    </button>

    <div style="padding: 14px 24px; border-top: 1px solid #e2e8f0; display:flex; justify-content:flex-end; gap:10px; background:#f8fafc;">
        <button type="button" class="btn-filter" style="background:#64748b;" onclick="window.location.reload()">Reset</button>
        <button type="button" class="btn-filter" onclick="simpanPenugasan()">
            <i class="fa-solid fa-floppy-disk"></i> Simpan Penugasan
        </button>
    </div>
</div>

<!-- MODAL POPUP KARYAWAN -->
<div class="modal-overlay-ok" id="modalKaryawan">
    <div class="modal-ok">
        <div class="modal-ok-header">
            <div>
                <div class="modal-ok-title">Pilih Karyawan</div>
                <div class="modal-ok-subtitle" id="modalSubtitle">Tentukan karyawan yang bertugas</div>
            </div>
            <button class="modal-ok-close" onclick="tutupPopup()">×</button>
        </div>
        <div class="modal-ok-search">
            <input type="text" id="searchKaryawan" placeholder="🔍  Cari nama atau NIK..." oninput="filterKaryawan()">
        </div>
        <div class="modal-ok-list" id="listKaryawan">
            <!-- diisi JS -->
        </div>
        <div class="modal-ok-footer">
            <div class="modal-ok-count">Dipilih: <span id="countSelected">0</span> orang</div>
            <div style="display:flex;gap:8px;">
                <button class="btn-ok-apply" style="background:#f1f5f9;color:#64748b;" onclick="pilihSemua()">Pilih Semua</button>
                <button class="btn-ok-apply" onclick="terapkanPilihan()">✓ Terapkan</button>
            </div>
        </div>
    </div>
</div>

<script>
// Data karyawan dari PHP
const allKaryawan = <?= json_encode($list_karyawan) ?>;
const tanggalTugas = '<?= $tanggal ?>';

let activeBtn = null;       // tombol tenaga yang sedang aktif
let selectedIds = new Set(); // ID karyawan yang dipilih saat ini

// ============= MODAL KARYAWAN =============
function bukaPopupKaryawan(btn) {
    activeBtn = btn;
    const row = btn.closest('tr.row-ok');
    const objek = row.querySelector('.sel-objek')?.value || '';
    const blok = row.querySelector('.sel-blok')?.value || '';
    document.getElementById('modalSubtitle').textContent = (objek ? objek : 'Objek Kerja') + (blok ? ' · Blok ' + blok : '');

    // Load ID yang sudah dipilih sebelumnya
    const existing = btn.dataset.selected ? JSON.parse(btn.dataset.selected) : [];
    selectedIds = new Set(existing.map(String));

    renderKaryawan(allKaryawan);
    updateCountSelected();
    document.getElementById('searchKaryawan').value = '';
    document.getElementById('modalKaryawan').style.display = 'flex';
}

function tutupPopup() {
    document.getElementById('modalKaryawan').style.display = 'none';
    activeBtn = null;
}

function renderKaryawan(list) {
    const container = document.getElementById('listKaryawan');
    if (list.length === 0) {
        container.innerHTML = '<div style="text-align:center;padding:30px;color:#94a3b8;font-size:13px;">Tidak ada karyawan ditemukan</div>';
        return;
    }
    container.innerHTML = list.map(k => {
        const isChecked = selectedIds.has(String(k.id));
        return `<label class="modal-ok-item ${isChecked ? 'checked' : ''}" data-id="${k.id}">
            <input type="checkbox" value="${k.id}" ${isChecked ? 'checked' : ''} onchange="toggleKaryawan(this)">
            <div>
                <div class="modal-ok-item-name">${k.name}</div>
                <div class="modal-ok-item-nik">NIK: ${k.nik}</div>
            </div>
        </label>`;
    }).join('');
}

function toggleKaryawan(cb) {
    const label = cb.closest('.modal-ok-item');
    if (cb.checked) {
        selectedIds.add(String(cb.value));
        label.classList.add('checked');
    } else {
        selectedIds.delete(String(cb.value));
        label.classList.remove('checked');
    }
    updateCountSelected();
}

function updateCountSelected() {
    document.getElementById('countSelected').textContent = selectedIds.size;
}

function filterKaryawan() {
    const q = document.getElementById('searchKaryawan').value.toLowerCase();
    const filtered = allKaryawan.filter(k => k.name.toLowerCase().includes(q) || k.nik.toLowerCase().includes(q));
    renderKaryawan(filtered);
}

function pilihSemua() {
    const visibleItems = document.querySelectorAll('#listKaryawan .modal-ok-item input[type="checkbox"]');
    const allChecked = Array.from(visibleItems).every(cb => cb.checked);
    visibleItems.forEach(cb => {
        cb.checked = !allChecked;
        if (!allChecked) {
            selectedIds.add(String(cb.value));
            cb.closest('.modal-ok-item').classList.add('checked');
        } else {
            selectedIds.delete(String(cb.value));
            cb.closest('.modal-ok-item').classList.remove('checked');
        }
    });
    updateCountSelected();
}

function terapkanPilihan() {
    if (!activeBtn) return;
    const ids = Array.from(selectedIds);
    activeBtn.dataset.selected = JSON.stringify(ids);

    // Update tampilan tombol
    const countSpan = activeBtn.querySelector('.tenaga-count');
    countSpan.textContent = ids.length;
    if (ids.length > 0) {
        activeBtn.classList.add('has-data');
    } else {
        activeBtn.classList.remove('has-data');
    }

    // Update hidden input
    const row = activeBtn.closest('tr.row-ok');
    const hiddenInput = row.querySelector('.hidden-karyawan-ids');
    if (hiddenInput) hiddenInput.value = ids.join(',');

    tutupPopup();
}

// ============= TAMBAH / HAPUS BARIS =============
let rowCounter = <?= max(count($rows_summary), 0) ?>;

function tambahBaris() {
    const emptyRow = document.getElementById('emptyRow');
    if (emptyRow) emptyRow.remove();

    rowCounter++;
    const tbody = document.getElementById('tbodyOK');

    const mandorOptions = <?= json_encode(array_map(fn($m) => "<option value='{$m['id']}'>" . htmlspecialchars($m['name']) . "</option>", $list_mandor)) ?>;
    const objekOptions = <?= json_encode(array_map(fn($o) => "<option value='" . htmlspecialchars($o) . "'>" . htmlspecialchars($o) . "</option>", $list_objek)) ?>;
    const blokOptions = <?= json_encode(array_map(fn($b, $l) => "<option value='$b' data-luas='$l'>$b</option>", array_keys($list_blok), array_values($list_blok))) ?>;

    const tr = document.createElement('tr');
    tr.className = 'row-ok';
    tr.dataset.rowId = rowCounter;
    tr.innerHTML = `
        <td class="row-no">${rowCounter}</td>
        <td><select name="mandor_id[]" class="sel-mandor"><option value="">— Pilih Mandor —</option>${mandorOptions.join('')}</select></td>
        <td><select name="objek_kerja[]" class="sel-objek">${objekOptions.join('')}</select></td>
        <td style="text-align:center;">
            <button type="button" class="btn-tenaga" data-row="${rowCounter}" data-selected='[]' onclick="bukaPopupKaryawan(this)">
                <span class="tenaga-count">0</span><span class="arrow">▼</span>
            </button>
            <input type="hidden" name="karyawan_ids[${rowCounter}]" class="hidden-karyawan-ids" value="">
        </td>
        <td><select name="blok[]" class="sel-blok" onchange="updateLuas(this)">
            <option value="">— Pilih —</option>${blokOptions.join('')}
        </select></td>
        <td><input type="number" name="luas_ha[]" class="inp-luas" step="0.01" placeholder="Ha"></td>
        <td style="text-align:center;"><button type="button" class="btn-hapus-baris" onclick="hapusBaris(this)">✕</button></td>
    `;
    tbody.appendChild(tr);
    renumberRows();
}

function hapusBaris(btn) {
    const tr = btn.closest('tr.row-ok');
    tr.remove();
    renumberRows();
    if (document.querySelectorAll('tr.row-ok').length === 0) {
        const tbody = document.getElementById('tbodyOK');
        tbody.innerHTML = `<tr id="emptyRow"><td colspan="7">
            <div class="empty-state">
                <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                <p>Belum ada data objek kerja</p><small>Klik "+ Tambah Baris Objek Kerja" untuk mulai</small>
            </div></td></tr>`;
    }
}

function renumberRows() {
    document.querySelectorAll('tr.row-ok').forEach((tr, i) => {
        const numCell = tr.querySelector('.row-no');
        if (numCell) numCell.textContent = i + 1;
    });
}

function updateLuas(sel) {
    const opt = sel.selectedOptions[0];
    const luas = opt ? opt.dataset.luas : '';
    const row = sel.closest('tr.row-ok');
    if (row) row.querySelector('.inp-luas').value = luas || '';
}

// ============= SIMPAN PENUGASAN =============
function simpanPenugasan() {
    const rows = document.querySelectorAll('tr.row-ok');
    if (rows.length === 0) {
        Swal.fire({ icon: 'warning', title: 'Tabel Kosong', text: 'Tambah baris objek kerja terlebih dahulu.' });
        return;
    }

    const promises = [];
    rows.forEach(row => {
        const mandor_id = row.querySelector('.sel-mandor')?.value || 0;
        const objek_kerja = row.querySelector('.sel-objek')?.value || '';
        const blok = row.querySelector('.sel-blok')?.value || '';
        const luas_ha = row.querySelector('.inp-luas')?.value || 0;
        const hiddenIds = row.querySelector('.hidden-karyawan-ids')?.value || '';
        const karyawan_ids = hiddenIds ? hiddenIds.split(',') : [];

        if (!objek_kerja) return;

        const fd = new FormData();
        fd.append('simpan_penugasan', 1);
        fd.append('tanggal_tugas', tanggalTugas);
        fd.append('mandor_id', mandor_id);
        fd.append('objek_kerja', objek_kerja);
        fd.append('blok', blok);
        fd.append('luas_ha', luas_ha);
        karyawan_ids.forEach(id => fd.append('karyawan_ids[]', id));

        promises.push(fetch('objek_kerja.php', { method: 'POST', body: fd }).then(r => r.json()));
    });

    Promise.all(promises).then(results => {
        const total = results.reduce((sum, r) => sum + (r.saved || 0), 0);
        Swal.fire({ icon: 'success', title: 'Berhasil Disimpan!', text: `${total} penugasan karyawan telah disimpan untuk tanggal ${new Date(tanggalTugas + 'T00:00:00').toLocaleDateString('id-ID', {day:'2-digit',month:'long',year:'numeric'})}.`, confirmButtonText: 'OK' });
    }).catch(() => {
        Swal.fire({ icon: 'error', title: 'Gagal', text: 'Terjadi kesalahan saat menyimpan data.' });
    });
}

// ============= KIRIM WA =============
function kirimPesanWA() {
    Swal.fire({
        icon: 'question',
        title: 'Kirim Pesan WhatsApp?',
        html: `Pesan penugasan akan dikirim ke semua <b>mandor</b> dan <b>karyawan</b> yang terdaftar untuk tanggal <b>${new Date(tanggalTugas + 'T00:00:00').toLocaleDateString('id-ID', {day:'2-digit',month:'long',year:'numeric'})}</b>.`,
        showCancelButton: true,
        confirmButtonText: '✅ Ya, Kirim',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#25d366'
    }).then(result => {
        if (!result.isConfirmed) return;

        const fd = new FormData();
        fd.append('kirim_pesan', 1);
        fd.append('tanggal_tugas', tanggalTugas);

        Swal.fire({ title: 'Mengirim...', text: 'Harap tunggu sebentar.', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        fetch('objek_kerja.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                Swal.fire({ icon: 'success', title: 'Pesan Terkirim!', text: `${data.sent} pesan WhatsApp berhasil dikirim ke mandor dan karyawan.` });
            }).catch(() => {
                Swal.fire({ icon: 'error', title: 'Gagal', text: 'Terjadi kesalahan saat mengirim pesan.' });
            });
    });
}

// Tutup modal saat klik di luar
document.getElementById('modalKaryawan').addEventListener('click', function(e) {
    if (e.target === this) tutupPopup();
});
</script>

<?php include 'templates/footer.php'; ?>
