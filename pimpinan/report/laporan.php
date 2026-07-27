<?php
require '../../config/config.php';
include '../templates/header.php';
?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<!-- Bootstrap CSS Form (for styling fixes selectively) -->
<style>
    /* 1. Container Utama */
    .report-container {
        width: 100%;
    }

    /* 2. Kartu Laporan (Card Style) */
    .report-card {
        background: #ffffff;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid #e2e8f0;
        overflow: hidden;
        margin-bottom: 24px;
    }

    /* Header Halaman */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 24px;
        flex-direction: column;
    }

    .page-title {
        font-size: 20px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 4px;
    }

    .page-desc {
        color: #64748b;
        font-size: 13px;
        margin: 0;
    }

    /* Body Form */
    .card-body {
        padding: 24px;
        background-color: #fff;
    }

    .form-row {
        display: flex;
        gap: 16px;
        align-items: flex-end;
        flex-wrap: wrap;
    }

    .form-group {
        flex: 1;
        min-width: 200px;
    }

    .form-label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: #475569;
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .input-date {
        width: 100%;
        padding: 10px 14px;
        font-size: 14px;
        color: #334155;
        background-color: #f8fafc;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        transition: all 0.2s ease;
        font-family: inherit;
    }

    .input-date:focus {
        outline: none;
        border-color: #3b82f6;
        background-color: #fff;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    select.input-date {
        appearance: none;
        background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23131313%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E');
        background-repeat: no-repeat;
        background-position: right 14px top 50%;
        background-size: 10px auto;
        padding-right: 36px;
    }

    .btn-export {
        background: #0ea5e9;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: background-color 0.2s;
        height: 42px;
        text-decoration: none;
    }

    .btn-export:hover {
        background: #0284c7;
        color: white;
    }

    .btn-primary-gradient {
        background: #3b82f6;
    }

    .btn-primary-gradient:hover {
        background: #2563eb;
    }

    /* Tabel Style */
    .table-laporan {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    .table-laporan th {
        background-color: #f8fafc;
        color: #64748b;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.5px;
        padding: 16px;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
        border-top: none;
    }

    .table-laporan td {
        padding: 16px;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
        font-size: 14px;
        vertical-align: middle;
    }

    .table-laporan tr:hover td {
        background-color: #f8fafc;
    }

    /* Badge & Labels */
    .badge-nik {
        background-color: #e0e7ff;
        color: #4338ca;
        padding: 4px 8px;
        border-radius: 4px;
        font-family: monospace;
        font-weight: 600;
        font-size: 12px;
    }

    .badge {
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        text-transform: capitalize;
    }

    .badge-success {
        background: #dcfce7;
        color: #166534;
    }

    .badge-warning {
        background: #fef9c3;
        color: #854d0e;
    }

    .badge-info {
        background: #e0f2fe;
        color: #075985;
    }

    .badge-primary {
        background: #ede9fe;
        color: #5b21b6;
    }

    .badge-danger {
        background: #fee2e2;
        color: #991b1b;
    }

    .badge-secondary {
        background: #f1f5f9;
        color: #475569;
    }

    /* Override DataTables */
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 4px 10px;
        margin-left: 4px;
        border: 1px solid #e2e8f0 !important;
        border-radius: 4px !important;
        background: #fff !important;
        font-size: 13px;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: #3b82f6 !important;
        color: white !important;
        border-color: #3b82f6 !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: #f1f5f9 !important;
        color: #1e293b !important;
    }

    .dataTables_wrapper .dataTables_filter input {
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        padding: 4px 10px;
        margin-left: 8px;
        font-size: 13px;
    }

    .dataTables_wrapper .dataTables_filter input:focus {
        outline: none;
        border-color: #3b82f6;
    }

    .dataTables_wrapper .dataTables_length select {
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        padding: 2px 5px;
        font-size: 13px;
    }

    @media (max-width: 768px) {
        .form-row {
            flex-direction: column;
        }

        .btn-export {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div>
    <div class="page-header">
        <div>
            <h1 class="page-title">Cetak Laporan Absensi</h1>
            <p class="page-desc">Pilih periode tanggal dan karyawan untuk memfilter daftar kehadiran.</p>
        </div>
    </div>

    <div class="report-card">
        <div class="card-body">
            <?php
            // Default filter params
            $filter_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
            $filter_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');
            $filter_user = isset($_GET['user_id']) ? $_GET['user_id'] : 'all';

            // Query Select Karyawan
            $users_q = mysqli_query($conn, "SELECT id, name FROM users WHERE role='karyawan' ORDER BY name ASC");
            ?>
            <form action="" method="GET">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Dari Tanggal</label>
                        <input type="date" name="tgl_awal" class="input-date" value="<?= htmlspecialchars($filter_awal) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Sampai Tanggal</label>
                        <input type="date" name="tgl_akhir" class="input-date" value="<?= htmlspecialchars($filter_akhir) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Karyawan</label>
                        <select name="user_id" class="input-date">
                            <option value="all" <?= $filter_user == 'all' ? 'selected' : '' ?>>Semua Karyawan</option>
                            <?php while ($u = mysqli_fetch_assoc($users_q)): ?>
                                <option value="<?= $u['id'] ?>" <?= $filter_user == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group" style="flex: 0 0 auto;">
                        <button type="submit" class="btn-export btn-primary-gradient">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                            Tampilkan
                        </button>
                    </div>
                </div>
            </form>

            <hr style="margin: 30px 0; border: none; border-top: 1px dashed #cbd5e1;">

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                <h3 style="font-size: 18px; font-weight: 800; color: #1e293b; margin: 0;">Rekap Kehadiran</h3>
                <a href="export_pdf.php?tgl_awal=<?= urlencode($filter_awal) ?>&tgl_akhir=<?= urlencode($filter_akhir) ?>&user_id=<?= urlencode($filter_user) ?>" target="_blank" class="btn-export" style="height: 42px; padding: 0 20px; font-size: 14px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    Cetak PDF / Export
                </a>
            </div>

            <?php
            // Setup query based on filter
            $q_str = "SELECT a.*, u.nik, u.name as nama_karyawan 
                      FROM absensis a 
                      JOIN users u ON a.user_id = u.id 
                      WHERE a.tanggal BETWEEN '$filter_awal' AND '$filter_akhir'";

            if ($filter_user !== 'all') {
                $q_str .= " AND a.user_id = " . intval($filter_user);
            }
            $q_str .= " ORDER BY a.tanggal DESC, u.name ASC";

            $data_absensi = mysqli_query($conn, $q_str);
            ?>

            <div style="overflow-x: auto;">
                <table class="table-laporan" id="laporanTable">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="15%">Tanggal</th>
                            <th width="15%">NIK</th>
                            <th width="30%">Nama Karyawan</th>
                            <th width="15%">Jam Masuk</th>
                            <th width="10%">Status</th>
                            <th width="10%" style="text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        if ($data_absensi && mysqli_num_rows($data_absensi) > 0):
                            while ($row = mysqli_fetch_assoc($data_absensi)):
                                $status_badge = '';
                                switch (strtolower($row['status_kehadiran'])) {
                                    case 'hadir':
                                        $status_badge = '<span class="badge badge-success">Hadir</span>';
                                        break;
                                    case 'terlambat':
                                        $status_badge = '<span class="badge badge-warning">Terlambat</span>';
                                        break;
                                    case 'izin':
                                        $status_badge = '<span class="badge badge-info">Izin</span>';
                                        break;
                                    case 'sakit':
                                        $status_badge = '<span class="badge badge-primary">Sakit</span>';
                                        break;
                                    case 'cuti':
                                        $status_badge = '<span class="badge badge-warning" style="background:#ffedd5;color:#9a3412;">Cuti</span>';
                                        break;
                                    case 'alpha':
                                        $status_badge = '<span class="badge badge-danger">Alpha</span>';
                                        break;
                                    default:
                                        $status_badge = '<span class="badge badge-secondary">' . htmlspecialchars($row['status_kehadiran']) . '</span>';
                                        break;
                                }
                        ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                                    <td><span class="badge-nik"><?= htmlspecialchars($row['nik']) ?></span></td>
                                    <td style="font-weight: 600; color: #334155;"><?= htmlspecialchars($row['nama_karyawan']) ?></td>
                                    <td><?= $row['waktu_masuk'] ? date('H:i', strtotime($row['waktu_masuk'])) : '-' ?></td>
                                    <td><?= $status_badge ?></td>
                                    <td style="text-align: center;">
                                        <button type="button" class="btn btn-sm btn-print-karyawan" style="background-color: #f8fafc; border: 1px solid #cbd5e1; color: #475569; padding: 4px 10px; border-radius: 4px; font-weight: 600; font-size: 12px; transition: all 0.2s; cursor: pointer;" onmouseover="this.style.backgroundColor='#e2e8f0'; this.style.borderColor='#94a3b8'" onmouseout="this.style.backgroundColor='#f8fafc'; this.style.borderColor='#cbd5e1'" data-bs-toggle="modal" data-bs-target="#modalCetakKaryawan" data-userid="<?= $row['user_id'] ?>" data-nama="<?= htmlspecialchars($row['nama_karyawan']) ?>">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 2px;">
                                                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                                <rect x="6" y="14" width="12" height="8"></rect>
                                            </svg> Cetak
                                        </button>
                                    </td>
                                </tr>
                        <?php
                            endwhile;
                        endif; ?>
                    </tbody>
                </table>
            </div>

        </div>

        <div style="background: #f8fafc; padding: 15px 30px; border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 12px; display: flex; align-items: center; gap: 8px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10" />
                <line x1="12" y1="16" x2="12" y2="12" />
                <line x1="12" y1="8" x2="12.01" y2="8" />
            </svg>
            Fitur pencarian karyawan di atas tabel dapat digunakan untuk mencari data tertentu di dalam tabel.
        </div>
    </div>
</div>

<!-- jQuery (Required for DataTables & Select2) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<!-- Select2 CSS & JS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
    /* Styling for Select2 to match existing inputs */
    .select2-container .select2-selection--single {
        height: 42px !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 6px !important;
        background-color: #f8fafc !important;
        display: flex;
        align-items: center;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #334155 !important;
        font-size: 14px;
        padding-left: 14px;
        font-weight: 400;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px !important;
        right: 10px !important;
    }

    .select2-container--open .select2-selection--single {
        border-color: #3b82f6 !important;
        background-color: #fff !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
    }

    .select2-dropdown {
        border: 1px solid #cbd5e1 !important;
        border-radius: 6px !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
    }

    .select2-search__field {
        border-radius: 4px !important;
        border: 1px solid #cbd5e1 !important;
    }

    .select2-search__field:focus {
        outline: none !important;
        border-color: #3b82f6 !important;
    }

    /* Minimal CSS for Bootstrap Modal due to missing framework CSS */
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        z-index: 1055;
        display: none;
        width: 100%;
        height: 100%;
        overflow-x: hidden;
        overflow-y: auto;
        outline: 0;
    }

    .modal.show {
        display: block;
    }

    .modal-dialog {
        position: relative;
        width: auto;
        margin: 1.75rem auto;
        pointer-events: none;
        max-width: 500px;
        display: flex;
        align-items: center;
        min-height: calc(100% - 3.5rem);
    }

    .modal-content {
        position: relative;
        display: flex;
        flex-direction: column;
        width: 100%;
        pointer-events: auto;
        background-color: #fff;
        background-clip: padding-box;
        border: 1px solid rgba(0, 0, 0, .15);
        border-radius: .4rem;
        outline: 0;
    }

    .modal-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        z-index: 1050;
        width: 100vw;
        height: 100vh;
        background-color: #000;
    }

    .modal-backdrop.fade {
        opacity: 0;
    }

    .modal-backdrop.show {
        opacity: .5;
    }

    .fade {
        transition: opacity .15s linear;
    }
</style>

<script>
    $(document).ready(function() {
        // Init DataTables
        $('#laporanTable').DataTable({
            "language": {
                "search": "Cari Karyawan:",
                "lengthMenu": "Tampilkan _MENU_ baris",
                "info": "Menampilkan _START_ s/d _END_ dari _TOTAL_ data",
                "infoEmpty": "Data tidak ditemukan",
                "zeroRecords": "Tidak ada data absensi pada periode yang dipilih.",
                "paginate": {
                    "first": "Awal",
                    "last": "Akhir",
                    "next": "Selanjutnya",
                    "previous": "Sebelumnya"
                }
            },
            "pageLength": 10,
            "ordering": false // Keep the original SQL ordering
        });

        // Init Select2 for employee search
        $('.select2-user').select2({
            placeholder: "Pilih Karyawan...",
            width: '100%',
            language: {
                noResults: function() {
                    return "Karyawan tidak ditemukan";
                }
            }
        });
    });
</script>

<!-- Modal Cetak Rekap Karyawan -->
<div class="modal fade" id="modalCetakKaryawan" tabindex="-1" aria-labelledby="modalCetakKaryawanLabel" aria-hidden="true" style="font-family: Arial, sans-serif;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 8px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
            <div class="modal-header" style="background-color: #f8fafc; border-bottom: 1px solid #e2e8f0; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; align-items: center; padding: 15px 20px;">
                <h5 class="modal-title" id="modalCetakKaryawanLabel" style="font-size: 16px; font-weight: 700; color: #1e293b; margin: 0;">
                    Cetak Absensi: <span id="modalNamaKaryawan" style="color: #3b82f6;"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="background: transparent; border: none; font-size: 24px; cursor: pointer; color: #64748b; line-height: 1; margin-top: -5px;">
                    &times;
                </button>
            </div>
            <form action="export_pdf.php" method="GET" target="_blank">
                <div class="modal-body" style="padding: 24px;">
                    <input type="hidden" name="user_id" id="modalUserId">

                    <p style="font-size: 13px; color: #64748b; margin-bottom: 16px;">Tentukan periode tanggal untuk mencetak rekapan.</p>

                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 6px; text-transform: uppercase;">Dari Tanggal</label>
                        <input type="date" name="tgl_awal" class="input-date" required value="<?= date('Y-m-01') ?>" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; color: #334155; background-color: #f8fafc; outline: none; transition: border-color 0.2s;" onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#cbd5e1'">
                    </div>

                    <div style="margin-bottom: 4px;">
                        <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 6px; text-transform: uppercase;">Sampai Tanggal</label>
                        <input type="date" name="tgl_akhir" class="input-date" required value="<?= date('Y-m-d') ?>" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; color: #334155; background-color: #f8fafc; outline: none; transition: border-color 0.2s;" onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#cbd5e1'">
                    </div>
                </div>
                <div class="modal-footer" style="padding: 16px 24px; border-top: 1px solid #e2e8f0; border-radius: 0 0 8px 8px; display: flex; justify-content: flex-end; gap: 10px; background-color: #fff;">
                    <button type="button" data-bs-dismiss="modal" style="background: #f1f5f9; color: #475569; border: none; padding: 10px 16px; border-radius: 6px; font-weight: 600; font-size: 14px; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.backgroundColor='#e2e8f0'" onmouseout="this.style.backgroundColor='#f1f5f9'">Batal</button>
                    <button type="submit" style="background: #10b981; color: white; border: none; padding: 10px 16px; border-radius: 6px; font-weight: 600; font-size: 14px; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: background 0.2s;" onmouseover="this.style.backgroundColor='#059669'" onmouseout="this.style.backgroundColor='#10b981'">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                        Cetak PDF
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Ensure Bootstrap forms and modals are injected if missing.
    if (typeof bootstrap === 'undefined') {
        var script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js';
        document.head.appendChild(script);
    }

    // Inject the modal JS once DOM is ready or Bootstrap is loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Wait briefly for bootstrap to load if injected
        setTimeout(function() {
            var myModalEl = document.getElementById('modalCetakKaryawan');
            if (myModalEl) {
                myModalEl.addEventListener('show.bs.modal', function(event) {
                    var button = event.relatedTarget;
                    var userid = button.getAttribute('data-userid');
                    var nama = button.getAttribute('data-nama');

                    document.getElementById('modalNamaKaryawan').textContent = nama;
                    document.getElementById('modalUserId').value = userid;
                });
            }
        }, 500);
    });
</script>

<?php include '../templates/footer.php'; ?>