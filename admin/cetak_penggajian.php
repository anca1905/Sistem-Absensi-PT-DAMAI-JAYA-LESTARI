<?php
require '../config/config.php';

// Cek autentikasi dan otorisasi (pastikan admin)
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Akses ditolak.");
}

$afdeling = isset($_GET['afdeling']) ? $_GET['afdeling'] : '';
$jabatan = isset($_GET['jabatan']) ? $_GET['jabatan'] : '';
$bulan = isset($_GET['bulan']) ? str_pad($_GET['bulan'], 2, '0', STR_PAD_LEFT) : date('m');
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');

if (empty($afdeling)) {
    die("Afdeling belum dipilih.");
}

$nama_bulan = [
    '01'=>'JANUARI', '02'=>'FEBRUARI', '03'=>'MARET', '04'=>'APRIL', 
    '05'=>'MEI', '06'=>'JUNI', '07'=>'JULI', '08'=>'AGUSTUS', 
    '09'=>'SEPTEMBER', '10'=>'OKTOBER', '11'=>'NOVEMBER', '12'=>'DESEMBER'
];

$periode_str = $nama_bulan[$bulan];
$jabatan_label = empty($jabatan) ? "KARYAWAN" : strtoupper($jabatan);

$afd_safe = mysqli_real_escape_string($conn, $afdeling);
$query_str = "
    SELECT u.id, u.nik, u.name, u.role, p.hk_dibayar, p.tarif_hk, p.uang_lembur, p.uang_premi, p.potongan_bpjs, p.potongan_koperasi, p.gaji_kotor, p.gaji_bersih, p.status,
           (SELECT COUNT(id) FROM absensis a WHERE a.user_id = u.id AND MONTH(a.tanggal) = '$bulan' AND YEAR(a.tanggal) = '$tahun' AND a.status_kehadiran IN ('hadir', 'terlambat')) AS auto_hk
    FROM users u 
    LEFT JOIN penggajian p ON u.id = p.user_id AND p.periode_bulan = '$bulan' AND p.periode_tahun = '$tahun'
    WHERE u.afdeling = '$afd_safe' AND u.role IN ('karyawan', 'mandor', 'pengawas', 'kerani')
";

if (!empty($jabatan)) {
    $jabatan_safe = mysqli_real_escape_string($conn, $jabatan);
    $query_str .= " AND u.role = '$jabatan_safe' ";
}

$query_str .= " ORDER BY u.name ASC";
$query = mysqli_query($conn, $query_str);

$users = [];
$total_gaji_bersih = 0;
while ($row = mysqli_fetch_assoc($query)) {
    $users[] = $row;
    $total_gaji_bersih += ($row['gaji_bersih'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Penggajian - <?= htmlspecialchars($afdeling) ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Times+New+Roman&display=swap');
        
        body {
            font-family: 'Times New Roman', Times, serif;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 20px;
        }

        @media print {
            @page {
                size: landscape;
                margin: 10mm;
            }
            body {
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
        }

        .header-doc {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .header-company {
            font-weight: bold;
            font-size: 11pt;
            line-height: 1.2;
        }

        .header-title {
            text-align: center;
            font-weight: bold;
            font-size: 13pt;
            flex-grow: 1;
            line-height: 1.4;
        }

        .header-right {
            text-align: right;
            font-weight: bold;
            font-size: 12pt;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            margin-bottom: 30px;
        }

        th, td {
            border: 1px solid #000;
            padding: 6px 4px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        
        .number-fmt { font-family: 'Courier New', Courier, monospace; }

        .signature-area {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            text-align: center;
            font-size: 10pt;
        }

        .sig-box {
            width: 20%;
        }

        .sig-box p {
            margin: 0 0 70px 0;
            font-weight: bold;
        }

        .sig-name {
            font-weight: bold;
            text-decoration: underline;
            display: block;
            margin-bottom: 5px;
        }
        
        .sig-role {
            font-weight: bold;
        }

    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 8px 16px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer;">Print Dokumen</button>
    </div>

    <div class="header-doc">
        <div class="header-company">
            PT. DAMAI JAYA LESTARI<br>
            KEBUN KOLAKA TANI<br>
            KEC. TANGGETADA/WATUBANGGA<br>
            KAB. KOLAKA
        </div>
        <div class="header-title">
            DAFTAR GAJI <?= $jabatan_label ?> <br>
            AFDELING <?= htmlspecialchars(strtoupper($afdeling)) ?><br>
            PERIODE: <?= $periode_str ?> <?= $tahun ?>
        </div>
        <div class="header-right">
            TAHUN <?= $tahun ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width:3%;">NO</th>
                <th rowspan="2" style="width:7%;">NIK</th>
                <th rowspan="2" style="width:15%;">NAMA</th>
                <th rowspan="2" style="width:8%;">JABATAN</th>
                <th colspan="2">GAJI POKOK</th>
                <th rowspan="2">LEMBUR</th>
                <th rowspan="2">PREMI</th>
                <th rowspan="2">GAJI KOTOR</th>
                <th colspan="3">PEMOTONGAN</th>
                <th rowspan="2">GAJI BERSIH</th>
                <th rowspan="2" style="width:12%;">TANDA TANGAN</th>
            </tr>
            <tr>
                <th>JML HK</th>
                <th>TARIF / HK</th>
                <th>BPJS</th>
                <th>KOPERASI</th>
                <th>TOTAL</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($users) > 0): $no=1; foreach($users as $u): 
                $hk = $u['hk_dibayar'] ?? $u['auto_hk'] ?? 0;
                $tarif = $u['tarif_hk'] ?? 0;
                $lembur = $u['uang_lembur'] ?? 0;
                $premi = $u['uang_premi'] ?? 0;
                $bpjs = $u['potongan_bpjs'] ?? 0;
                $koperasi = $u['potongan_koperasi'] ?? 0;
                $gaji_kotor = $u['gaji_kotor'] ?? 0;
                $gaji_bersih = $u['gaji_bersih'] ?? 0;
                $total_potongan = $bpjs + $koperasi;
            ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td class="text-center"><?= htmlspecialchars($u['nik']) ?></td>
                <td><?= htmlspecialchars(strtoupper($u['name'])) ?></td>
                <td class="text-center"><?= htmlspecialchars(strtoupper($u['role'])) ?></td>
                <td class="text-center"><?= (float)$hk ?></td>
                <td class="text-right number-fmt"><?= number_format($tarif, 0, ',', '.') ?></td>
                <td class="text-right number-fmt"><?= number_format($lembur, 0, ',', '.') ?></td>
                <td class="text-right number-fmt"><?= number_format($premi, 0, ',', '.') ?></td>
                <td class="text-right number-fmt" style="font-weight:bold;"><?= number_format($gaji_kotor, 0, ',', '.') ?></td>
                <td class="text-right number-fmt"><?= number_format($bpjs, 0, ',', '.') ?></td>
                <td class="text-right number-fmt"><?= number_format($koperasi, 0, ',', '.') ?></td>
                <td class="text-right number-fmt"><?= number_format($total_potongan, 0, ',', '.') ?></td>
                <td class="text-right number-fmt" style="font-weight:bold;"><?= number_format($gaji_bersih, 0, ',', '.') ?></td>
                <td>
                    <div style="height: 25px; line-height: 25px;">
                        <?= ($no-1) ?> ....................
                    </div>
                </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="14" class="text-center" style="padding: 20px;">Tidak ada data karyawan pada filter ini.</td></tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="12" class="text-right" style="font-weight: bold; font-size: 10pt; padding: 10px;">TOTAL GAJI BERSIH</td>
                <td class="text-right number-fmt" style="font-weight: bold; font-size: 10pt; padding: 10px;"><?= number_format($total_gaji_bersih, 0, ',', '.') ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="signature-area">
        <div class="sig-box">
            <p>Disetujui oleh,</p>
            <span class="sig-name">Ir. Pikir Manurung</span>
            <span class="sig-role">Manager Kebun</span>
        </div>
        <div class="sig-box">
            <p>Diketahui oleh,</p>
            <span class="sig-name">I Nyoman Sukadana, SP</span>
            <span class="sig-role">KTU</span>
        </div>
        <div class="sig-box">
            <p>Diperiksa Oleh,</p>
            <span class="sig-name">Elinson Silalahi</span>
            <span class="sig-role">Asisten Afd <?= strtoupper(str_replace('Afdeling ', '', $afdeling)) ?></span>
        </div>
        <div class="sig-box">
            <p>Disusun oleh,</p>
            <span class="sig-name">Ernawati</span>
            <span class="sig-role">Adm. Penggajian</span>
        </div>
    </div>

</body>
</html>
