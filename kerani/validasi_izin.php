<?php
require '../config/config.php';
include 'templates/header.php';

$role_filter = isset($_GET['role']) ? strtolower(trim($_GET['role'])) : 'karyawan';
if (!in_array($role_filter, ['semua', 'karyawan', 'mandor', 'pengawas'])) {
    $role_filter = 'karyawan';
}

// Proses verifikasi (Setujui / Tolak) oleh Kerani via SISTEM
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id']) && isset($_POST['status'])) {
    $id = (int)$_POST['id'];
    $status_raw = strtolower(trim($_POST['status']));
    $status = ($status_raw === 'ya' || $status_raw === 'setuju' || $status_raw === 'disetujui') ? 'disetujui' : 'ditolak';

    // Update status di database perizinan
    $update_query = mysqli_query($conn, "UPDATE perizinan SET status='$status' WHERE id=$id");

    if ($update_query) {
        // Jika disetujui, update atau insert data absensi
        if ($status == 'disetujui') {
            $q_izin = mysqli_query($conn, "SELECT user_id, tanggal_izin, jenis FROM perizinan WHERE id=$id");
            if ($d_izin = mysqli_fetch_assoc($q_izin)) {
                $uid   = (int)$d_izin['user_id'];
                $tgl   = $d_izin['tanggal_izin'];
                $jenis = strtolower($d_izin['jenis']);

                $c_abs = mysqli_query($conn, "SELECT id FROM absensis WHERE user_id='$uid' AND tanggal='$tgl'");
                if (mysqli_num_rows($c_abs) > 0) {
                    mysqli_query($conn, "UPDATE absensis SET status_kehadiran='$jenis' WHERE user_id='$uid' AND tanggal='$tgl'");
                } else {
                    mysqli_query($conn, "INSERT INTO absensis (user_id, tanggal, waktu_masuk, status_kehadiran) VALUES ('$uid', '$tgl', '00:00:00', '$jenis')");
                }
            }
        }

        // --- NOTIFIKASI WHATSAPP KE PEMOHON (WA HANYA NOTIFIKASI) ---
        $q_wa = mysqli_query($conn, "SELECT u.name, u.no_hp, p.jenis, p.tanggal_izin FROM perizinan p JOIN users u ON p.user_id = u.id WHERE p.id=$id");
        if ($row_wa = mysqli_fetch_assoc($q_wa)) {
            if (!empty($row_wa['no_hp'])) {
                $status_label = ($status == 'disetujui') ? 'DISETUJUI ✅' : 'DITOLAK ❌';
                $tgl_izin_fmt = date('d-m-Y', strtotime($row_wa['tanggal_izin']));

                $pesan = "🔔 *STATUS PENGAJUAN PERIZINAN* 🔔\n\n";
                $pesan .= "Halo *{$row_wa['name']}*,\n\n";
                $pesan .= "Pengajuan perizinan *{$row_wa['jenis']}* Anda untuk tanggal *{$tgl_izin_fmt}* telah *{$status_label}* oleh Kerani melalui sistem AMANDA.\n\n";
                $pesan .= "_Ini adalah pesan notifikasi otomatis dari Sistem Informasi Karyawan PT DJL._";

                sendWA($row_wa['no_hp'], $pesan);
            }
        }

        $msg_notif = ($status == 'disetujui') ? 'Pengajuan berhasil DISETUJUI dan notifikasi telah dikirim ke WhatsApp pemohon.' : 'Pengajuan telah DITOLAK dan notifikasi telah dikirim ke WhatsApp pemohon.';
        swalRedirect($msg_notif, "validasi_izin.php?role=" . urlencode($role_filter), 'success');
    } else {
        swalAlert('Gagal memproses pengajuan. ' . mysqli_error($conn), 'error');
    }
}

// Hitung total pending untuk setiap kategori role
$q_counts = mysqli_query($conn, "
    SELECT u.role, COUNT(*) as jml 
    FROM perizinan p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.status = 'pending' 
    GROUP BY u.role
");
$pending_counts = ['karyawan' => 0, 'mandor' => 0, 'pengawas' => 0];
$total_pending_all = 0;
if ($q_counts) {
    while ($c = mysqli_fetch_assoc($q_counts)) {
        $r = strtolower($c['role']);
        if (isset($pending_counts[$r])) {
            $pending_counts[$r] = (int)$c['jml'];
        }
        $total_pending_all += (int)$c['jml'];
    }
}
?>

<style>
    .header-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 24px;
    }

    .tab-pills {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .tab-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        background: #ffffff;
        color: #475569;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 700;
        font-size: 13px;
        transition: all 0.2s;
    }

    .tab-pill:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: #0f172a;
    }

    .tab-pill.active {
        background: #0f172a;
        color: #ffffff;
        border-color: #0f172a;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
    }

    .tab-pill .badge-count {
        background: #ef4444;
        color: #ffffff;
        font-size: 11px;
        font-weight: 800;
        padding: 2px 7px;
        border-radius: 20px;
    }

    .tab-pill.active .badge-count {
        background: #ef4444;
        color: #ffffff;
    }

    .card {
        background: white;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
        overflow: hidden;
    }

    .card-header {
        padding: 20px 24px;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .card-title {
        font-size: 17px;
        font-weight: 800;
        color: var(--text-main);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .table-container {
        width: 100%;
        overflow-x: auto;
    }

    .table-data {
        width: 100%;
        border-collapse: collapse;
        font-size: 13.5px;
    }

    .table-data th,
    .table-data td {
        padding: 16px 20px;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
    }

    .table-data th {
        background: #f8fafc;
        color: var(--text-muted);
        font-weight: 700;
        text-transform: uppercase;
        font-size: 11.5px;
        letter-spacing: 0.5px;
    }

    .table-data tbody tr:hover {
        background: #f8fafc;
    }

    .row-pending {
        background-color: #fffdf5;
    }

    .btn-file {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #cbd5e1;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
    }

    .btn-file:hover {
        background: #e2e8f0;
        color: #0f172a;
    }

    .action-group {
        display: flex;
        gap: 8px;
    }

    .btn-action {
        padding: 8px 14px;
        border-radius: 8px;
        font-size: 12.5px;
        font-weight: 700;
        cursor: pointer;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
    }

    .btn-ya {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
    }

    .btn-ya:hover {
        background: #22c55e;
        color: #ffffff;
        border-color: #22c55e;
    }

    .btn-tidak {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    .btn-tidak:hover {
        background: #ef4444;
        color: #ffffff;
        border-color: #ef4444;
    }

    .badge-jenis {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        background: #eff6ff;
        color: #2563eb;
    }

    .badge-role {
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
        text-transform: capitalize;
        background: #f1f5f9;
        color: #475569;
    }

    .badge-status {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.5px;
    }

    .badge-status.disetujui {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
    }

    .badge-status.ditolak {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    .badge-status.pending {
        background: #fef9c3;
        color: #854d0e;
        border: 1px solid #fef08a;
    }
</style>

<div class="header-actions">
    <div class="tab-pills">
        <a href="validasi_izin.php?role=semua" class="tab-pill <?= $role_filter == 'semua' ? 'active' : '' ?>">
            Semua
            <?php if ($total_pending_all > 0): ?>
                <span class="badge-count"><?= $total_pending_all ?></span>
            <?php endif; ?>
        </a>
        <a href="validasi_izin.php?role=karyawan" class="tab-pill <?= $role_filter == 'karyawan' ? 'active' : '' ?>">
            Karyawan
            <?php if ($pending_counts['karyawan'] > 0): ?>
                <span class="badge-count"><?= $pending_counts['karyawan'] ?></span>
            <?php endif; ?>
        </a>
        <a href="validasi_izin.php?role=mandor" class="tab-pill <?= $role_filter == 'mandor' ? 'active' : '' ?>">
            Mandor
            <?php if ($pending_counts['mandor'] > 0): ?>
                <span class="badge-count"><?= $pending_counts['mandor'] ?></span>
            <?php endif; ?>
        </a>
        <a href="validasi_izin.php?role=pengawas" class="tab-pill <?= $role_filter == 'pengawas' ? 'active' : '' ?>">
            Pengawas
            <?php if ($pending_counts['pengawas'] > 0): ?>
                <span class="badge-count"><?= $pending_counts['pengawas'] ?></span>
            <?php endif; ?>
        </a>
    </div>

    <div style="font-size: 13px; color: #64748b; font-weight: 600;">
        <i class="fa-solid fa-circle-info" style="color: #3b82f6;"></i>
        Verifikasi penuh dilakukan via sistem
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fa-solid fa-clipboard-check" style="color: #3b82f6;"></i>
            Validasi Surat Izin / Sakit / Cuti
        </h3>
        <span style="font-size: 13px; color: #64748b;">
            Kategori: <b><?= ucfirst($role_filter) ?></b>
        </span>
    </div>

    <div class="table-container">
        <table class="table-data">
            <thead>
                <tr>
                    <th width="40">NO</th>
                    <th>NIK</th>
                    <th>NAMA</th>
                    <th>PERAN</th>
                    <th>TGL IZIN</th>
                    <th>JENIS</th>
                    <th width="260">KETERANGAN / ALASAN</th>
                    <th>BUKTI</th>
                    <th width="180">STATUS / VERIFIKASI</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($role_filter === 'semua') {
                    $where_clause = "";
                } else {
                    $role_safe = mysqli_real_escape_string($conn, $role_filter);
                    $where_clause = "WHERE u.role = '$role_safe'";
                }

                // Prioritaskan status pending di paling atas, kemudian tanggal pengajuan terbaru
                $query = mysqli_query($conn, "SELECT p.*, u.nik, u.name as user_name, u.role as user_role 
                                            FROM perizinan p 
                                            JOIN users u ON p.user_id = u.id 
                                            $where_clause 
                                            ORDER BY (p.status = 'pending') DESC, p.tanggal_pengajuan DESC");
                $no = 1;
                while ($row = mysqli_fetch_assoc($query)):
                    $is_pending = ($row['status'] === 'pending');
                ?>
                    <tr class="<?= $is_pending ? 'row-pending' : '' ?>">
                        <td style="color: var(--text-muted); font-weight: 600;"><?= $no++ ?></td>
                        <td style="font-weight: 700;"><?= htmlspecialchars($row['nik']) ?></td>
                        <td>
                            <div style="font-weight: 700; color: #0f172a;"><?= htmlspecialchars($row['user_name']) ?></div>
                            <div style="font-size: 11px; color: #94a3b8;"><?= date('d/m/Y H:i', strtotime($row['tanggal_pengajuan'])) ?></div>
                        </td>
                        <td>
                            <span class="badge-role"><?= htmlspecialchars($row['user_role']) ?></span>
                        </td>
                        <td style="font-weight: 600; color: #1e293b;">
                            <?= date('d M Y', strtotime($row['tanggal_izin'])) ?>
                        </td>
                        <td>
                            <span class="badge-jenis"><?= htmlspecialchars($row['jenis']) ?></span>
                        </td>
                        <td style="color: #475569; font-size: 13px; line-height: 1.5;">
                            <?= nl2br(htmlspecialchars($row['keterangan'])) ?>
                        </td>
                        <td>
                            <?php if (!empty($row['bukti_file'])): ?>
                                <a href="../uploads/izin/<?= htmlspecialchars($row['bukti_file']) ?>" target="_blank" class="btn-file">
                                    <i class="fa-regular fa-file-lines"></i>
                                    Lihat File
                                </a>
                            <?php else: ?>
                                <span style="font-size: 12px; color: #94a3b8;">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($is_pending): ?>
                                <form method="POST" class="action-group">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button type="submit" name="status" value="setuju" class="btn-action btn-ya" onclick="return confirm('Apakah Anda yakin ingin MENYETUJUI pengajuan perizinan dari <?= addslashes($row['user_name']) ?>?');" title="Setujui Pengajuan">
                                        <i class="fa-solid fa-check"></i> Setujui
                                    </button>
                                    <button type="submit" name="status" value="tolak" class="btn-action btn-tidak" onclick="return confirm('Apakah Anda yakin ingin MENOLAK pengajuan perizinan dari <?= addslashes($row['user_name']) ?>?');" title="Tolak Pengajuan">
                                        <i class="fa-solid fa-xmark"></i> Tolak
                                    </button>
                                </form>
                            <?php elseif ($row['status'] === 'disetujui'): ?>
                                <span class="badge-status disetujui">
                                    <i class="fa-solid fa-circle-check"></i> DISETUJUI
                                </span>
                            <?php else: ?>
                                <span class="badge-status ditolak">
                                    <i class="fa-solid fa-circle-xmark"></i> DITOLAK
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>

                <?php if (mysqli_num_rows($query) == 0): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 40px 20px;">
                            <i class="fa-regular fa-folder-open" style="font-size: 32px; display: block; margin-bottom: 10px; color: #cbd5e1;"></i>
                            Belum ada data pengajuan perizinan untuk kategori ini.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
