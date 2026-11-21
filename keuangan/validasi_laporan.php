<?php
require '../config/config.php';
include 'templates/header.php';

$nama_bulan = [
    '01' => 'Januari',   '02' => 'Februari', '03' => 'Maret',
    '04' => 'April',     '05' => 'Mei',      '06' => 'Juni',
    '07' => 'Juli',      '08' => 'Agustus',  '09' => 'September',
    '10' => 'Oktober',   '11' => 'November', '12' => 'Desember'
];

$keuangan_id = (int)$_SESSION['user_id'];

// --- HANDLER POST: VALIDASI OLEH KEUANGAN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $id_pengajuan = (int)($_POST['id_pengajuan'] ?? 0);
    $catatan_keu = trim($_POST['catatan_keuangan'] ?? '');
    $catatan_safe = mysqli_real_escape_string($conn, $catatan_keu);
    $now = date('Y-m-d H:i:s');

    if ($id_pengajuan <= 0) {
        swalAlert('ID Pengajuan tidak valid.', 'error');
    } elseif ($action === 'setujui') {
        $sql = "UPDATE laporan_pengajuan SET 
                status = 'disetujui',
                keuangan_id = $keuangan_id,
                tanggal_validasi = '$now',
                catatan_keuangan = '$catatan_safe'
                WHERE id = $id_pengajuan";

        if (mysqli_query($conn, $sql)) {
            // Notifikasi WA ke Kerani
            $q_lp = mysqli_query($conn, "
                SELECT lp.*, u.no_hp, u.name as nama_kerani 
                FROM laporan_pengajuan lp 
                JOIN users u ON lp.kerani_id = u.id 
                WHERE lp.id = $id_pengajuan
            ");
            if ($row_lp = mysqli_fetch_assoc($q_lp)) {
                if (!empty($row_lp['no_hp'])) {
                    $label_jns = ($row_lp['jenis_laporan'] === 'absensi') ? 'Absensi' : 'Keseluruhan Objek Kerja';
                    $pesan_wa = "✅ *LAPORAN DISETUJUI OLEH KEUANGAN* ✅\n\n";
                    $pesan_wa .= "Halo *{$row_lp['nama_kerani']}*,\n";
                    $pesan_wa .= "Laporan {$label_jns} untuk *{$row_lp['afdeling']}* periode *" . ($nama_bulan[$row_lp['bulan']] ?? $row_lp['bulan']) . " {$row_lp['tahun']}* telah *DISETUJUI & DIVALIDASI* oleh Bagian Keuangan.\n";
                    if (!empty($catatan_keu)) {
                        $pesan_wa .= "💬 Catatan Keuangan: _{$catatan_keu}_\n";
                    }
                    $pesan_wa .= "\nLaporan ini resmi dan sah untuk proses pencatatan & penggajian.\n\n";
                    $pesan_wa .= "_Sistem Informasi Karyawan PT DJL_";
                    sendWA($row_lp['no_hp'], $pesan_wa);
                }
            }

            swalRedirect('Laporan berhasil disetujui & divalidasi!', 'validasi_laporan.php', 'success', 'Laporan Disetujui!');
        } else {
            swalAlert('Gagal memvalidasi laporan: ' . mysqli_error($conn), 'error');
        }
    } elseif ($action === 'tolak') {
        if (empty($catatan_keu)) {
            swalAlert('Mohon isi alasan / catatan revisi untuk Kerani.', 'warning');
        } else {
            $sql = "UPDATE laporan_pengajuan SET 
                    status = 'ditolak',
                    keuangan_id = $keuangan_id,
                    tanggal_validasi = '$now',
                    catatan_keuangan = '$catatan_safe'
                    WHERE id = $id_pengajuan";

            if (mysqli_query($conn, $sql)) {
                // Notifikasi WA ke Kerani
                $q_lp = mysqli_query($conn, "
                    SELECT lp.*, u.no_hp, u.name as nama_kerani 
                    FROM laporan_pengajuan lp 
                    JOIN users u ON lp.kerani_id = u.id 
                    WHERE lp.id = $id_pengajuan
                ");
                if ($row_lp = mysqli_fetch_assoc($q_lp)) {
                    if (!empty($row_lp['no_hp'])) {
                        $label_jns = ($row_lp['jenis_laporan'] === 'absensi') ? 'Absensi' : 'Keseluruhan Objek Kerja';
                        $pesan_wa = "⚠️ *PERMINTAAN REVISI LAPORAN* ⚠️\n\n";
                        $pesan_wa .= "Halo *{$row_lp['nama_kerani']}*,\n";
                        $pesan_wa .= "Laporan {$label_jns} untuk *{$row_lp['afdeling']}* periode *" . ($nama_bulan[$row_lp['bulan']] ?? $row_lp['bulan']) . " {$row_lp['tahun']}* dikembalikan oleh Bagian Keuangan untuk *DIREVISI*.\n\n";
                        $pesan_wa .= "📌 *Alasan / Catatan Perbaikan:*\n_{$catatan_keu}_\n\n";
                        $pesan_wa .= "Silakan cross-check dan perbaiki data melalui sistem AMANDA, kemudian kirim ulang laporan.\n\n";
                        $pesan_wa .= "_Sistem Informasi Karyawan PT DJL_";
                        sendWA($row_lp['no_hp'], $pesan_wa);
                    }
                }

                swalRedirect('Laporan dikembalikan ke Kerani untuk direvisi.', 'validasi_laporan.php', 'info', 'Permintaan Revisi Terkirim');
            } else {
                swalAlert('Gagal memproses penolakan: ' . mysqli_error($conn), 'error');
            }
        }
    }
}

// --- FILTER ---
$f_status   = isset($_GET['status'])   ? trim($_GET['status'])   : '';
$f_afdeling = isset($_GET['afdeling']) ? trim($_GET['afdeling']) : '';
$f_tahun    = isset($_GET['tahun'])    ? (int)$_GET['tahun']    : (int)date('Y');
$f_bulan    = isset($_GET['bulan'])    ? trim($_GET['bulan'])    : '';

$where_clause = "WHERE lp.tahun = $f_tahun";
if (!empty($f_status)) {
    $st_safe = mysqli_real_escape_string($conn, $f_status);
    $where_clause .= " AND lp.status = '$st_safe'";
}
if (!empty($f_afdeling)) {
    $afd_safe = mysqli_real_escape_string($conn, $f_afdeling);
    $where_clause .= " AND lp.afdeling = '$afd_safe'";
}
if (!empty($f_bulan)) {
    $bln_safe = mysqli_real_escape_string($conn, str_pad((int)$f_bulan, 2, '0', STR_PAD_LEFT));
    $where_clause .= " AND lp.bulan = '$bln_safe'";
}

// Hitung Statistik
$q_stat_pending = mysqli_query($conn, "SELECT COUNT(*) as total FROM laporan_pengajuan WHERE status='menunggu_validasi'");
$total_pending = ($q_stat_pending) ? (int)mysqli_fetch_assoc($q_stat_pending)['total'] : 0;

$q_stat_approved = mysqli_query($conn, "SELECT COUNT(*) as total FROM laporan_pengajuan WHERE status='disetujui' AND tahun=$f_tahun");
$total_approved = ($q_stat_approved) ? (int)mysqli_fetch_assoc($q_stat_approved)['total'] : 0;

$q_stat_rejected = mysqli_query($conn, "SELECT COUNT(*) as total FROM laporan_pengajuan WHERE status='ditolak' AND tahun=$f_tahun");
$total_rejected = ($q_stat_rejected) ? (int)mysqli_fetch_assoc($q_stat_rejected)['total'] : 0;

// List Afdeling
$q_afd = mysqli_query($conn, "SELECT DISTINCT afdeling FROM users WHERE role IN ('karyawan', 'kerani', 'mandor', 'pengawas') AND afdeling != '' ORDER BY afdeling ASC");
$list_afdeling = [];
while ($a = mysqli_fetch_assoc($q_afd)) $list_afdeling[] = $a['afdeling'];

// Query Data Pengajuan
$query_laporan = mysqli_query($conn, "
    SELECT lp.*, 
           uk.name as nama_kerani, uk.no_hp as no_hp_kerani,
           ug.name as nama_keuangan
    FROM laporan_pengajuan lp
    LEFT JOIN users uk ON lp.kerani_id = uk.id
    LEFT JOIN users ug ON lp.keuangan_id = ug.id
    $where_clause
    ORDER BY 
        CASE lp.status 
            WHEN 'menunggu_validasi' THEN 1 
            WHEN 'ditolak' THEN 2 
            WHEN 'disetujui' THEN 3 
            ELSE 4 
        END,
        lp.tanggal_kirim DESC
");
?>

<style>
    .vl-header {
        background: linear-gradient(135deg, #064e3b 0%, #065f46 50%, #10b981 100%);
        border-radius: 20px;
        padding: 30px 36px;
        color: white;
        margin-bottom: 28px;
        box-shadow: 0 10px 30px rgba(6, 78, 59, 0.2);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
    }
    .vl-header h1 { font-size: 24px; font-weight: 800; margin-bottom: 6px; }
    .vl-header p  { font-size: 13px; opacity: 0.9; max-width: 650px; line-height: 1.5; }

    /* Stat Cards */
    .vl-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 18px;
        margin-bottom: 28px;
    }
    .vl-stat-card {
        background: white;
        border-radius: 16px;
        padding: 20px 24px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 2px 12px rgba(0,0,0,0.03);
        display: flex;
        align-items: center;
        gap: 16px;
    }
    .vl-stat-icon {
        width: 48px; height: 48px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px;
    }
    .icon-pending  { background: #fef3c7; color: #d97706; }
    .icon-approved { background: #d1fae5; color: #059669; }
    .icon-rejected { background: #fee2e2; color: #dc2626; }

    .vl-stat-val { font-size: 26px; font-weight: 800; color: #1e293b; line-height: 1; }
    .vl-stat-lbl { font-size: 12px; font-weight: 600; color: #64748b; margin-top: 4px; }

    /* Filter Bar */
    .vl-filter-bar {
        background: white;
        padding: 18px 24px;
        border-radius: 14px;
        border: 1px solid #e5e7eb;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 14px;
    }
    .vl-filter-inputs {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    .vl-select {
        padding: 8px 14px;
        border-radius: 8px;
        border: 1px solid #d1d5db;
        font-weight: 600;
        font-size: 13px;
        color: #374151;
        outline: none;
    }

    /* Table */
    .vl-table-card {
        background: white;
        border-radius: 16px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 4px 20px rgba(0,0,0,0.02);
        overflow: hidden;
    }
    .vl-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }
    .vl-table th {
        background: #f9fafb;
        padding: 14px 20px;
        font-weight: 700;
        color: #4b5563;
        text-align: left;
        border-bottom: 1.5px solid #e5e7eb;
        white-space: nowrap;
    }
    .vl-table td {
        padding: 16px 20px;
        border-bottom: 1px solid #f3f4f6;
        vertical-align: middle;
    }
    .vl-table tr:hover { background: #f9fafb; }

    /* Badges */
    .badge-vl {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .badge-vl-pending  { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
    .badge-vl-approved { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
    .badge-vl-rejected { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

    /* Action Buttons */
    .btn-vl {
        padding: 7px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: none;
        text-decoration: none;
        transition: all 0.15s;
    }
    .btn-vl-check { background: #eff6ff; color: #1d4ed8; }
    .btn-vl-check:hover { background: #dbeafe; }

    .btn-vl-approve { background: #10b981; color: white; }
    .btn-vl-approve:hover { background: #059669; }

    .btn-vl-reject { background: #ef4444; color: white; }
    .btn-vl-reject:hover { background: #dc2626; }

    /* Modal */
    .vl-modal-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(4px);
        display: none; align-items: center; justify-content: center;
        z-index: 999;
    }
    .vl-modal-overlay.active { display: flex; }
    .vl-modal-box {
        background: white; border-radius: 16px; width: 90%; max-width: 520px;
        padding: 28px; box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    }
</style>

<div class="vl-header">
    <div>
        <h1>Validasi Laporan Kerani</h1>
        <p>
            Periksa dan validasi laporan absensi dan hasil kinerja yang telah di-crosscheck dan dikirimkan oleh Kerani dari setiap Afdeling. Laporan yang telah divalidasi resmi digunakan untuk pencatatan dan dasar penggajian.
        </p>
    </div>
    <div style="background: rgba(255,255,255,0.15); padding: 12px 20px; border-radius: 14px; border: 1px solid rgba(255,255,255,0.25); text-align: right;">
        <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; opacity: 0.9;">Menunggu Tindakan</div>
        <div style="font-size: 24px; font-weight: 800;"><?= $total_pending ?> Laporan</div>
    </div>
</div>

<div class="vl-stats">
    <div class="vl-stat-card">
        <div class="vl-stat-icon icon-pending">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
        </div>
        <div>
            <div class="vl-stat-val"><?= $total_pending ?></div>
            <div class="vl-stat-lbl">Menunggu Validasi</div>
        </div>
    </div>
    <div class="vl-stat-card">
        <div class="vl-stat-icon icon-approved">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        </div>
        <div>
            <div class="vl-stat-val"><?= $total_approved ?></div>
            <div class="vl-stat-lbl">Disetujui Tahun <?= $f_tahun ?></div>
        </div>
    </div>
    <div class="vl-stat-card">
        <div class="vl-stat-icon icon-rejected">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
        </div>
        <div>
            <div class="vl-stat-val"><?= $total_rejected ?></div>
            <div class="vl-stat-lbl">Perlu Revisi (Ditolak)</div>
        </div>
    </div>
</div>

<!-- FILTER -->
<div class="vl-filter-bar">
    <form method="GET" class="vl-filter-inputs" id="filterVal">
        <label style="font-weight: 700; font-size: 13px; color: #4b5563;">Filter Laporan:</label>

        <select name="status" class="vl-select" onchange="document.getElementById('filterVal').submit()">
            <option value="">Semua Status</option>
            <option value="menunggu_validasi" <?= $f_status == 'menunggu_validasi' ? 'selected' : '' ?>>Menunggu Validasi</option>
            <option value="disetujui" <?= $f_status == 'disetujui' ? 'selected' : '' ?>>Disetujui</option>
            <option value="ditolak" <?= $f_status == 'ditolak' ? 'selected' : '' ?>>Perlu Revisi</option>
        </select>

        <select name="afdeling" class="vl-select" onchange="document.getElementById('filterVal').submit()">
            <option value="">Semua Afdeling</option>
            <?php foreach ($list_afdeling as $afd): ?>
                <option value="<?= htmlspecialchars($afd) ?>" <?= $f_afdeling == $afd ? 'selected' : '' ?>><?= htmlspecialchars($afd) ?></option>
            <?php endforeach; ?>
        </select>

        <select name="bulan" class="vl-select" onchange="document.getElementById('filterVal').submit()">
            <option value="">Semua Bulan</option>
            <?php foreach ($nama_bulan as $num_b => $name_b): ?>
                <option value="<?= $num_b ?>" <?= $f_bulan == $num_b ? 'selected' : '' ?>><?= $name_b ?></option>
            <?php endforeach; ?>
        </select>

        <select name="tahun" class="vl-select" onchange="document.getElementById('filterVal').submit()">
            <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                <option value="<?= $y ?>" <?= $f_tahun == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </form>

    <div style="font-size: 12px; color: #6b7280;">
        Menampilkan <strong><?= mysqli_num_rows($query_laporan) ?></strong> berkas laporan
    </div>
</div>

<!-- TABLE LIST -->
<div class="vl-table-card">
    <table class="vl-table">
        <thead>
            <tr>
                <th style="width: 50px;">No</th>
                <th>Periode & Afdeling</th>
                <th>Jenis Laporan</th>
                <th>Pengirim (Kerani)</th>
                <th>Status</th>
                <th>Catatan</th>
                <th style="text-align: right;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            if (mysqli_num_rows($query_laporan) == 0): 
            ?>
            <tr>
                <td colspan="7" style="text-align: center; padding: 40px; color: #9ca3af;">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 8px; color: #cbd5e1;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                    <div>Tidak ada berkas pengajuan laporan sesuai filter ini.</div>
                </td>
            </tr>
            <?php endif; ?>

            <?php while ($row = mysqli_fetch_assoc($query_laporan)): 
                $label_jns = ($row['jenis_laporan'] === 'absensi') ? 'Laporan Absensi' : 'Laporan Keseluruhan Objek Kerja';
                $link_periksa = ($row['jenis_laporan'] === 'absensi') 
                    ? "laporan_absensi.php?bulan={$row['bulan']}&tahun={$row['tahun']}&afdeling=" . urlencode($row['afdeling'])
                    : "lap_keseluruhan.php?bulan={$row['bulan']}&tahun={$row['tahun']}&afdeling=" . urlencode($row['afdeling']);
            ?>
            <tr>
                <td style="color: #9ca3af; font-weight: 600;"><?= $no++ ?></td>
                <td>
                    <strong style="color: #111827; font-size: 14px;"><?= htmlspecialchars($row['afdeling']) ?></strong>
                    <div style="font-size: 12px; color: #6b7280; margin-top: 2px;">
                        <?= $nama_bulan[$row['bulan']] ?? $row['bulan'] ?> <?= $row['tahun'] ?>
                    </div>
                </td>
                <td>
                    <span style="font-weight: 700; color: #047857; background: #ecfdf5; padding: 4px 10px; border-radius: 6px; font-size: 12px;">
                        <?= $label_jns ?>
                    </span>
                </td>
                <td>
                    <div style="font-weight: 600; color: #374151;"><?= htmlspecialchars($row['nama_kerani'] ?: 'Kerani') ?></div>
                    <div style="font-size: 11px; color: #9ca3af;">
                        Dikirim: <?= date('d/m/Y H:i', strtotime($row['tanggal_kirim'])) ?>
                    </div>
                </td>
                <td>
                    <?php if ($row['status'] === 'menunggu_validasi'): ?>
                        <span class="badge-vl badge-vl-pending"><i class="fa-solid fa-hourglass-half"></i> Menunggu Validasi</span>
                    <?php elseif ($row['status'] === 'disetujui'): ?>
                        <span class="badge-vl badge-vl-approved"><i class="fa-solid fa-circle-check"></i> Disetujui</span>
                        <div style="font-size: 11px; color: #065f46; margin-top: 4px;">
                            Oleh: <?= htmlspecialchars($row['nama_keuangan'] ?: 'Keuangan') ?><br>
                            <?= date('d/m/Y H:i', strtotime($row['tanggal_validasi'])) ?>
                        </div>
                    <?php elseif ($row['status'] === 'ditolak'): ?>
                        <span class="badge-vl badge-vl-rejected"><i class="fa-solid fa-triangle-exclamation"></i> Perlu Revisi</span>
                        <div style="font-size: 11px; color: #991b1b; margin-top: 4px;">
                            <?= date('d/m/Y H:i', strtotime($row['tanggal_validasi'])) ?>
                        </div>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($row['catatan_kerani'])): ?>
                        <div style="font-size: 12px; color: #4b5563;"><strong>Kerani:</strong> "<?= htmlspecialchars($row['catatan_kerani']) ?>"</div>
                    <?php endif; ?>
                    <?php if (!empty($row['catatan_keuangan'])): ?>
                        <div style="font-size: 12px; color: <?= $row['status'] === 'ditolak' ? '#b91c1c' : '#047857' ?>; margin-top: 4px;">
                            <strong>Keuangan:</strong> "<?= htmlspecialchars($row['catatan_keuangan']) ?>"
                        </div>
                    <?php endif; ?>
                    <?php if (empty($row['catatan_kerani']) && empty($row['catatan_keuangan'])): ?>
                        <span style="color: #d1d5db;">-</span>
                    <?php endif; ?>
                </td>
                <td style="text-align: right; white-space: nowrap;">
                    <a href="<?= $link_periksa ?>" class="btn-vl btn-vl-check" target="_blank" title="Buka dan Periksa Data Rinci">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        Periksa
                    </a>

                    <?php if ($row['status'] === 'menunggu_validasi'): ?>
                        <button type="button" class="btn-vl btn-vl-approve" onclick="bukaModalValidasi(<?= $row['id'] ?>, 'setujui', '<?= htmlspecialchars(addslashes($row['afdeling'])) ?>', '<?= $nama_bulan[$row['bulan']] ?> <?= $row['tahun'] ?>', '<?= $label_jns ?>')" title="Setujui dan Validasi Laporan Ini">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            Setujui
                        </button>
                        <button type="button" class="btn-vl btn-vl-reject" onclick="bukaModalValidasi(<?= $row['id'] ?>, 'tolak', '<?= htmlspecialchars(addslashes($row['afdeling'])) ?>', '<?= $nama_bulan[$row['bulan']] ?> <?= $row['tahun'] ?>', '<?= $label_jns ?>')" title="Kembalikan ke Kerani untuk Diperbaiki">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                            Revisi
                        </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- MODAL VALIDASI / REVISI KEUANGAN -->
<div class="vl-modal-overlay" id="modalValidasiKeu">
    <div class="vl-modal-box">
        <h3 id="modalValTitle" style="font-size: 18px; font-weight: 800; color: #111827; margin-bottom: 8px;">Validasi Laporan</h3>
        <p id="modalValDesc" style="font-size: 13px; color: #6b7280; line-height: 1.5; margin-bottom: 18px;"></p>

        <form method="POST">
            <input type="hidden" name="action" id="actionVal">
            <input type="hidden" name="id_pengajuan" id="idPengajuanVal">

            <div style="margin-bottom: 18px;">
                <label id="labelCatatanVal" style="display: block; font-size: 12px; font-weight: 700; color: #374151; margin-bottom: 6px;">Catatan Keuangan:</label>
                <textarea name="catatan_keuangan" id="textareaCatatanVal" class="vl-select" style="width: 100%; height: 90px; resize: vertical; font-size: 13px;" placeholder="Tuliskan catatan verifikasi..."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn-vl" onclick="tutupModalValidasi()" style="background: #f3f4f6; color: #4b5563;">Batal</button>
                <button type="submit" id="btnSubmitVal" class="btn-vl btn-vl-approve" style="padding: 10px 18px;"></button>
            </div>
        </form>
    </div>
</div>

<script>
function bukaModalValidasi(id, action, afd, periode, jns) {
    document.getElementById('idPengajuanVal').value = id;
    document.getElementById('actionVal').value = action;

    const modalTitle = document.getElementById('modalValTitle');
    const modalDesc = document.getElementById('modalValDesc');
    const labelCatatan = document.getElementById('labelCatatanVal');
    const txtCatatan = document.getElementById('textareaCatatanVal');
    const btnSubmit = document.getElementById('btnSubmitVal');

    if (action === 'setujui') {
        modalTitle.innerText = 'Setujui & Validasi Laporan';
        modalTitle.style.color = '#059669';
        modalDesc.innerHTML = 'Apakah Anda telah memeriksa <strong>' + jns + '</strong> untuk <strong>' + afd + '</strong> periode <strong>' + periode + '</strong> dan menyatakan data tersebut sudah <u>valid dan disetujui</u>?';
        labelCatatan.innerText = 'Catatan Keuangan (Opsional):';
        txtCatatan.placeholder = 'Contoh: Laporan telah diperiksa dan disetujui untuk proses penggajian.';
        txtCatatan.required = false;
        btnSubmit.className = 'btn-vl btn-vl-approve';
        btnSubmit.innerHTML = '✅ Ya, Setujui Laporan';
    } else {
        modalTitle.innerText = 'Kembalikan Laporan untuk Revisi';
        modalTitle.style.color = '#dc2626';
        modalDesc.innerHTML = 'Kembalikan <strong>' + jns + '</strong> untuk <strong>' + afd + '</strong> periode <strong>' + periode + '</strong> ke Kerani untuk diperbaiki.';
        labelCatatan.innerHTML = 'Alasan / Catatan Revisi untuk Kerani <span style="color:#ef4444;">*</span>:';
        txtCatatan.placeholder = 'Contoh: Terdapat ketidaksesuaian absensi pada tanggal 12 untuk 3 orang pemanen, mohon dikoreksi.';
        txtCatatan.required = true;
        btnSubmit.className = 'btn-vl btn-vl-reject';
        btnSubmit.innerHTML = '↩️ Kembalikan untuk Revisi';
    }

    document.getElementById('modalValidasiKeu').classList.add('active');
}

function tutupModalValidasi() {
    document.getElementById('modalValidasiKeu').classList.remove('active');
}
</script>

<?php include 'templates/footer.php'; ?>
