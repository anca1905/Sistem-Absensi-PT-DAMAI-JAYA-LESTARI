<?php
require '../../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Pimpinan Role Check
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'pimpinan')) {
    die("Akses ditolak. Anda tidak memiliki izin untuk mengunduh laporan ini.");
}

// Cek input tanggal
if (!isset($_GET['tgl_awal']) || !isset($_GET['tgl_akhir'])) {
    die("Harap pilih rentang tanggal.");
}

$tgl_awal = $_GET['tgl_awal'];
$tgl_akhir = $_GET['tgl_akhir'];
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : 'all';

// Title halaman
$page_title = "Laporan_Absensi_" . $tgl_awal . "_sd_" . $tgl_akhir;
if ($user_id !== 'all') {
    // Get user logic to append to title if needed
    $u_res = mysqli_query($conn, "SELECT name FROM users WHERE id = " . intval($user_id));
    if ($u_row = mysqli_fetch_assoc($u_res)) {
        $page_title .= "_" . str_replace(' ', '_', $u_row['name']);
    }
}

// Query Data (Join tabel absensi dan user)
$q_str = "
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
";

if ($user_id !== 'all') {
    $q_str .= " AND a.user_id = " . intval($user_id);
}

$q_str .= " ORDER BY a.tanggal DESC, u.name ASC";

$query = mysqli_query($conn, $q_str);
?>

<!DOCTYPE html>
<html>

<head>
    <title><?= $page_title ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            margin: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 14px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f8fafc;
            font-weight: bold;
            color: #1e293b;
        }

        .text-center {
            text-align: center;
        }

        @media print {
            body {
                margin: 0;
                padding: 15px;
            }

            .no-print {
                display: none;
            }

            table {
                font-size: 12px;
            }

            th {
                background-color: #f1f5f9 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>

    <div class="no-print" style="margin-bottom: 20px; text-align: center;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">Cetak / Simpan PDF</button>
    </div>

    <h3 style="text-align: center; margin-bottom: 5px; color: #1e293b;">LAPORAN ABSENSI KARYAWAN</h3>
    <p style="text-align: center; margin-top: 0; color: #64748b;">Periode: <?= date('d M Y', strtotime($tgl_awal)) ?> s/d <?= date('d M Y', strtotime($tgl_akhir)) ?></p>

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
                    $status_color = in_array(strtolower($row['status_kehadiran']), ['alpha', 'alpa', 'alfa', 'terlambat']) ? 'color: red;' : 'color: green;';
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

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>

</html>