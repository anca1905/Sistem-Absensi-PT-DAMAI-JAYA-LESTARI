<?php
require '../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'pengawas') {
    header("Location: ../index.php");
    exit;
}

// Handle Aksi Terima/Tolak
if (isset($_POST['proses_izin'])) {
    $id_izin = $_POST['id_izin'];
    $status = $_POST['status']; // 'disetujui' atau 'ditolak'

    $query_update = "UPDATE perizinan SET status='$status' WHERE id='$id_izin'";
    if (mysqli_query($conn, $query_update)) {
        if ($status == 'disetujui') {
            $q_izin = mysqli_query($conn, "SELECT user_id, tanggal_izin, jenis FROM perizinan WHERE id='$id_izin'");
            if ($d_izin = mysqli_fetch_assoc($q_izin)) {
                $uid = $d_izin['user_id'];
                $tgl = $d_izin['tanggal_izin'];
                $jenis = strtolower($d_izin['jenis']);
                
                $c_abs = mysqli_query($conn, "SELECT id FROM absensis WHERE user_id='$uid' AND tanggal='$tgl'");
                if(mysqli_num_rows($c_abs) > 0) {
                    mysqli_query($conn, "UPDATE absensis SET status_kehadiran='$jenis' WHERE user_id='$uid' AND tanggal='$tgl'");
                } else {
                    mysqli_query($conn, "INSERT INTO absensis (user_id, tanggal, waktu_masuk, status_kehadiran) VALUES ('$uid', '$tgl', '00:00:00', '$jenis')");
                }
            }
        }
        swalRedirect('Status berhasil diupdate!', 'validasi_izin.php', 'success');
    } else {
        swalAlert('Gagal mengupdate status!', 'error');
    }
}

// Ambil semua data izin yang masih 'pending'
$query_pending = mysqli_query($conn, "
    SELECT p.*, u.name, u.nik 
    FROM perizinan p 
    JOIN users u ON p.user_id = u.id 
    ORDER BY p.tanggal_pengajuan DESC
");

include 'templates/header.php';
?>

<div style="margin-bottom: 24px;">
    <h1 style="font-size: 20px; font-weight: 700; color: #1e293b;">Validasi Izin & Sakit</h1>
    <p style="color: #64748b; font-size: 13px;">Tinjau pengajuan dari karyawan dan periksa surat buktinya.</p>
</div>

<div style="background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background-color: #f8fafc; text-align: left; font-size: 11px; text-transform: uppercase; color: #64748b;">
                <th style="padding: 16px;">Karyawan</th>
                <th style="padding: 16px;">Tgl Izin</th>
                <th style="padding: 16px;">Jenis & Alasan</th>
                <th style="padding: 16px; text-align: center;">Bukti Surat</th>
                <th style="padding: 16px; text-align: center;">Status</th>
                <th style="padding: 16px; text-align: right;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($query_pending) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($query_pending)): ?>
                    <tr style="border-top: 1px solid #f1f5f9;">
                        <td style="padding: 16px;">
                            <div style="font-weight: 600; color: #334155;"><?= htmlspecialchars($row['name']) ?></div>
                            <div style="font-size: 11px; color: #94a3b8;">NIK: <?= $row['nik'] ?></div>
                        </td>
                        <td style="padding: 16px; font-weight: 600;"><?= date('d M Y', strtotime($row['tanggal_izin'])) ?></td>
                        <td style="padding: 16px;">
                            <span style="font-weight:bold; text-transform: uppercase; font-size: 11px; color: #0284c7; background: #e0f2fe; padding: 2px 6px; border-radius: 4px;">
                                <?= $row['jenis'] ?>
                            </span>
                            <div style="font-size: 12px; margin-top: 4px; color: #475569;"><?= htmlspecialchars($row['keterangan']) ?></div>
                        </td>
                        <td style="padding: 16px; text-align: center;">
                            <a href="../uploads/izin/<?= $row['bukti_file'] ?>" target="_blank" style="color: #3b82f6; text-decoration: underline; font-size: 12px; font-weight: 600;">Lihat File</a>
                        </td>
                        <td style="padding: 16px; text-align: center;">
                            <?php if ($row['status'] == 'pending'): ?>
                                <span style="background: #fef3c7; color: #d97706; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold;">Menunggu</span>
                            <?php elseif ($row['status'] == 'disetujui'): ?>
                                <span style="background: #dcfce7; color: #15803d; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold;">Disetujui</span>
                            <?php else: ?>
                                <span style="background: #fee2e2; color: #b91c1c; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold;">Ditolak</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 16px; text-align: right;">
                            <?php if ($row['status'] == 'pending'): ?>
                                <form method="POST" style="display:inline-block;">
                                    <input type="hidden" name="id_izin" value="<?= $row['id'] ?>">
                                    <input type="hidden" name="status" value="disetujui">
                                    <button type="submit" name="proses_izin" style="background: #10b981; color: white; border: none; padding: 6px 10px; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 11px;">Terima</button>
                                </form>
                                <form method="POST" style="display:inline-block;">
                                    <input type="hidden" name="id_izin" value="<?= $row['id'] ?>">
                                    <input type="hidden" name="status" value="ditolak">
                                    <button type="submit" name="proses_izin" onclick="return confirm('Yakin ingin menolak izin ini?')" style="background: #ef4444; color: white; border: none; padding: 6px 10px; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 11px;">Tolak</button>
                                </form>
                            <?php else: ?>
                                <span style="font-size: 11px; color: #94a3b8;">Selesai diproses</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="padding: 40px; text-align: center; color: #94a3b8;">Belum ada pengajuan izin.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'templates/footer.php'; ?>