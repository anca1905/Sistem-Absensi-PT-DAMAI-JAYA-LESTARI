<?php
require '../config/config.php';

// Cek input tanggal
if (!isset($_GET['tgl_awal']) || !isset($_GET['tgl_akhir'])) {
    die("Harap pilih rentang tanggal.");
}

$tgl_awal = $_GET['tgl_awal'];
$tgl_akhir = $_GET['tgl_akhir'];

// Nama File saat didownload
$filename = "Laporan_Absensi_" . $tgl_awal . "_sd_" . $tgl_akhir . ".xls";

// --- HEADER AGAR BROWSER MEMBACA SEBAGAI EXCEL ---
header("Content-Type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Query Data (Join tabel absensi dan user)
$query = mysqli_query($conn, "
    SELECT 
        u.nik, 
        u.name, 
        u.jabatan, 
        a.tanggal, 
        a.waktu_masuk, 
        a.waktu_pulang, 
        a.status_kehadiran 
    FROM absensis a
    JOIN users u ON a.user_id = u.id
    WHERE a.tanggal BETWEEN '$tgl_awal' AND '$tgl_akhir'
    ORDER BY a.tanggal DESC, u.name ASC
");
?>

<!DOCTYPE html>
<html>

<head>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }
    </style>
</head>

<body>

    <h3 style="text-align: center;">LAPORAN ABSENSI KARYAWAN</h3>
    <p style="text-align: center;">Periode: <?= $tgl_awal ?> s/d <?= $tgl_akhir ?></p>
    <br>

    <table>
        <thead>
            <tr>
                <th width="5%" class="text-center">No</th>
                <th width="15%">Tanggal</th>
                <th width="15%">NIK</th>
                <th width="25%">Nama Karyawan</th>
                <th width="15%">Jabatan</th>
                <th width="10%" class="text-center">Jam Masuk</th>
                <th width="10%" class="text-center">Jam Pulang</th>
                <th width="15%" class="text-center">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            if (mysqli_num_rows($query) > 0):
                while ($row = mysqli_fetch_assoc($query)):
                    // Format Jam agar lebih rapi (hilangkan detik jika perlu, atau biarkan)
                    $masuk = $row['waktu_masuk'] ? date('H:i', strtotime($row['waktu_masuk'])) : '-';
                    $pulang = $row['waktu_pulang'] ? date('H:i', strtotime($row['waktu_pulang'])) : '-';

                    // Warna status untuk Excel (Opsional, kadang Excel mengabaikan style warna)
                    $status_color = ($row['status_kehadiran'] == 'terlambat') ? 'color: red;' : 'color: green;';
            ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td><?= $row['tanggal'] ?></td>
                        <td style="mso-number-format:'\@';"><?= $row['nik'] ?></td>
                        <td><?= $row['name'] ?></td>
                        <td><?= $row['jabatan'] ?></td>
                        <td class="text-center"><?= $masuk ?></td>
                        <td class="text-center"><?= $pulang ?></td>
                        <td class="text-center" style="<?= $status_color ?> font-weight:bold;">
                            <?= strtoupper(str_replace('_', ' ', $row['status_kehadiran'])) ?>
                        </td>
                    </tr>
                <?php
                endwhile;
            else:
                ?>
                <tr>
                    <td colspan="8" class="text-center">Tidak ada data absensi pada periode ini.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>

</html>