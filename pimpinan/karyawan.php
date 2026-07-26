<?php
require '../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Pimpinan Role Check
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'pimpinan')) {
    header("Location: ../index.php");
    exit;
}

// 1. Ambil Data Karyawan
$data_karyawan = mysqli_query($conn, "SELECT * FROM users WHERE role='karyawan' ORDER BY id DESC");

include 'templates/header.php';
?>

<style>
    /* 1. Header Halaman & Tombol */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }

    .page-title {
        font-size: 20px;
        font-weight: 700;
        color: #1e293b;
    }

    /* 2. Styling Tabel Professional */
    .table-container {
        background: white;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th {
        background-color: #f8fafc;
        color: #64748b;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.5px;
        padding: 16px;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
    }

    td {
        padding: 16px;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
        font-size: 14px;
    }

    tr:last-child td {
        border-bottom: none;
    }

    tr:hover td {
        background-color: #f8fafc;
    }

    /* Badge NIK */
    .badge-nik {
        background-color: #e0e7ff;
        color: #4338ca;
        padding: 4px 8px;
        border-radius: 4px;
        font-family: monospace;
        font-weight: 600;
    }
</style>

<div>
    <div class="page-header">
        <div>
            <h1 class="page-title">Data Karyawan</h1>
            <p style="color: #64748b; font-size: 13px; margin-top: 4px;">Daftar seluruh pegawai yang terdaftar di sistem.</p>
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="35%">Nama Pegawai</th>
                    <th width="30%">NIK</th>
                    <th width="30%">Jabatan</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1;
                while ($row = mysqli_fetch_assoc($data_karyawan)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td>
                            <div style="font-weight: 600;"><?= $row['name'] ?></div>
                            <div style="font-size: 12px; color: #94a3b8;"><?= $row['email'] ?></div>
                        </td>
                        <td><span class="badge-nik"><?= $row['nik'] ?></span></td>
                        <td><?= $row['jabatan'] ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <?php if ($no == 1): ?>
            <div style="padding: 40px; text-align: center; color: #94a3b8;">
                Belum ada data karyawan.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'templates/footer.php'; ?>