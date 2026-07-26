<?php
require '../config/config.php';
include 'templates/header.php';

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$tanggal = isset($_GET['tanggal']) ? mysqli_real_escape_string($conn, $_GET['tanggal']) : date('Y-m-d');

$query_user = mysqli_query($conn, "SELECT name FROM users WHERE id=$user_id");
$user = mysqli_fetch_assoc($query_user);
$user_name = $user ? $user['name'] : 'Unknown';

$query_log = mysqli_query($conn, "SELECT l.*, u.name as mandor_name FROM logbook_kinerja l LEFT JOIN users u ON l.mandor_id = u.id WHERE l.user_id=$user_id AND l.tanggal='$tanggal'");
?>

<style>
    .header-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }

    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--text-main);
        text-decoration: none;
        font-weight: 700;
        font-size: 14px;
        background: white;
        padding: 10px 20px;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        transition: all 0.2s;
    }
    .btn-back:hover { background: #f8fafc; }

    .btn-print {
        background: var(--accent);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }
    .btn-print:hover { background: #2563eb; }

    .card {
        background: white;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        overflow: hidden;
    }

    .card-header {
        padding: 24px;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
        text-align: center;
    }
    
    .card-title {
        font-size: 20px;
        font-weight: 800;
        color: var(--text-main);
        margin: 0 0 8px 0;
    }

    .card-subtitle {
        font-size: 16px;
        color: var(--text-muted);
        font-weight: 600;
        margin: 0;
    }

    .table-container {
        width: 100%;
        overflow-x: auto;
    }

    .table-data {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
        text-align: center;
    }

    .table-data th, .table-data td {
        padding: 16px;
        border: 1px solid #e2e8f0;
    }

    .table-data th {
        background: white;
        color: var(--text-muted);
        font-weight: 800;
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.5px;
    }

    .table-data tbody tr:hover {
        background: #f8fafc;
    }

    @media print {
        body * { visibility: hidden; }
        .main-content { margin-left: 0; }
        .sidebar, .topbar, .header-actions { display: none !important; }
        .card, .card * { visibility: visible; }
        .card { position: absolute; left: 0; top: 0; border: none; box-shadow: none; width: 100%; }
    }
</style>

<div class="header-actions">
    <a href="laporan_kinerja.php" class="btn-back">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
        Kembali
    </a>
    
    <button class="btn-print" onclick="window.print()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
        Cetak PDF
    </button>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><?= date('d F Y', strtotime($tanggal)) ?></h3>
        <p class="card-subtitle">Karyawan: <?= htmlspecialchars($user_name) ?></p>
    </div>
    
    <div class="table-container">
        <table class="table-data">
            <thead>
                <tr>
                    <th>KATEGORI</th>
                    <th>OBJEK KERJA</th>
                    <th>BLOK</th>
                    <th>LUAS HA</th>
                    <th>DETAIL KINERJA</th>
                    <th>STATUS</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($query_log)): ?>
                <tr>
                    <td style="font-weight: 700; text-transform: uppercase; color: var(--text-muted);"><?= str_replace('_', ' ', $row['kategori_task']) ?></td>
                    <td style="font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($row['objek_kerja']) ?></td>
                    <td style="font-weight: 600; color: var(--text-muted);"><?= htmlspecialchars($row['blok']) ?></td>
                    <td style="font-weight: 600; color: var(--text-muted);"><?= htmlspecialchars($row['luas_ha']) ?></td>
                    <td style="text-align: left; font-size: 13px;">
                        <?php if($row['kategori_task'] == 'perawatan' || $row['kategori_task'] == 'jaga'): ?>
                            Aksi: <b><?= strtoupper($row['aksi']) ?></b> <br>
                            <?= $row['jumlah_jam_kerja'] > 0 ? "Jam Kerja: <b>{$row['jumlah_jam_kerja']} Jam</b>" : "" ?>
                        <?php elseif($row['kategori_task'] == 'potong_buah'): ?>
                            TBS: <b><?= $row['tbs'] ?></b>, Kosong: <b><?= $row['tandan_kosong'] ?></b><br>
                            Brondol: <b><?= $row['tandan_brondol'] ?></b>, Total: <b><?= $row['total_tandan'] ?></b>
                        <?php elseif($row['kategori_task'] == 'langsir'): ?>
                            Hasil: <b><?= $row['hasil_ton'] ?> Ton / <?= $row['hasil_kg'] ?> Kg</b><br>
                            Prestasi: <b><?= $row['prestasi_ton'] ?> Ton / <?= $row['prestasi_kg'] ?> Kg</b>
                        <?php elseif($row['kategori_task'] == 'muat_tbs'): ?>
                            Langsiran: <b><?= $row['hasil_langsir_kg'] ?> Kg</b><br>
                            Jam Kerja: <b><?= $row['jumlah_jam_kerja'] ?> Jam</b>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span style="font-weight: 800; color: <?= $row['status']=='diterima'?'#166534':'#d97706' ?>; background: <?= $row['status']=='diterima'?'#dcfce7':'#fef3c7' ?>; padding: 6px 12px; border-radius: 6px;">
                            <?= strtoupper($row['status']) ?>
                        </span>
                    </td>
                </tr>
                <?php endwhile; ?>
                
                <?php if(mysqli_num_rows($query_log) == 0): ?>
                <tr>
                    <td colspan="6" style="padding: 20px;">Tidak ada logbook.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
