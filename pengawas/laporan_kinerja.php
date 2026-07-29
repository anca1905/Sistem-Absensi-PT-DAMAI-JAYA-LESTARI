<?php
require '../config/config.php';
include 'templates/header.php';

$tanggal          = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');
$pengawas_id      = $_SESSION['user_id'];
$afdeling_pengawas = isset($_SESSION['afdeling']) ? mysqli_real_escape_string($conn, $_SESSION['afdeling']) : '';

$nama_bulan_id = [
    '01'=>'Januari','02'=>'Februari','03'=>'Maret',
    '04'=>'April',  '05'=>'Mei',     '06'=>'Juni',
    '07'=>'Juli',   '08'=>'Agustus', '09'=>'September',
    '10'=>'Oktober','11'=>'November','12'=>'Desember'
];
$tgl_pecah      = explode('-', $tanggal);
$format_tanggal = $tgl_pecah[2] . ' ' . $nama_bulan_id[$tgl_pecah[1]] . ' ' . $tgl_pecah[0];

$tgl_safe = mysqli_real_escape_string($conn, $tanggal);
if (!empty($afdeling_pengawas)) {
    $query_logbook = mysqli_query($conn, "
        SELECT lk.*, u.nik, u.name as karyawan_name, u.afdeling, m.name as mandor_name
        FROM logbook_kinerja lk
        JOIN users u ON lk.user_id = u.id
        LEFT JOIN users m ON lk.mandor_id = m.id
        WHERE lk.tanggal = '$tgl_safe' AND u.afdeling = '$afdeling_pengawas'
        ORDER BY u.name ASC
    ");
} else {
    $query_logbook = mysqli_query($conn, "
        SELECT lk.*, u.nik, u.name as karyawan_name, u.afdeling, m.name as mandor_name
        FROM logbook_kinerja lk
        JOIN users u ON lk.user_id = u.id
        LEFT JOIN users m ON lk.mandor_id = m.id
        WHERE lk.tanggal = '$tgl_safe'
        ORDER BY u.name ASC
    ");
}

// Kumpulkan semua data
$all_rows = [];
while ($row = mysqli_fetch_assoc($query_logbook)) {
    $all_rows[] = $row;
}
?>

<style>
    /* ===== LAYOUT HALAMAN ===== */
    .card-container {
        background: #ffffff;
        border-radius: 16px;
        padding: 20px 16px;
        box-shadow: 0 4px 20px rgba(54, 72, 217, 0.05);
        border: 1px solid #f1f5f9;
        margin-bottom: 20px;
    }

    .btn-back {
        display: inline-flex; align-items: center; gap: 6px;
        color: var(--text-muted); text-decoration: none; font-weight: 700;
        font-size: 13px; margin-bottom: 16px; background: white;
        padding: 8px 14px; border-radius: 20px; border: 1.5px solid #e2e8f0;
    }

    .filter-row {
        display: flex; flex-wrap: wrap; justify-content: space-between;
        align-items: flex-end; margin-bottom: 16px; gap: 10px;
    }

    .form-input {
        padding: 10px 14px; border-radius: 10px; border: 1.5px solid #e2e8f0;
        background-color: #f8fafc; font-size: 13px; font-weight: 600;
        color: var(--text-dark); font-family: inherit; outline: none; transition: all 0.2s;
    }
    .form-input:focus { border-color: var(--primary-start); background: white; }

    .btn-print {
        background: linear-gradient(135deg, var(--primary-start) 0%, var(--primary-end) 100%);
        color: white; border: none; padding: 10px 16px; border-radius: 10px;
        font-size: 13px; font-weight: 800; cursor: pointer; display: flex;
        align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(66, 88, 255, 0.25);
        transition: all 0.2s; white-space: nowrap;
    }
    .btn-print:active { transform: scale(0.95); }

    /* ===== TABEL ===== */
    .table-responsive {
        width: 100%; overflow-x: auto; border-radius: 10px;
        border: 1px solid #e2e8f0; background: white;
    }
    .table-kinerja {
        border-collapse: collapse; white-space: nowrap;
        font-size: 12px; min-width: 560px; width: 100%;
    }
    .table-kinerja th {
        background-color: var(--primary-light); color: var(--primary-end);
        font-weight: 800; font-size: 11px; text-transform: uppercase;
        letter-spacing: 0.5px; padding: 12px 10px; border-bottom: 2px solid #c7d2fe;
        text-align: center;
    }
    .table-kinerja td {
        padding: 12px 10px; border-bottom: 1px solid #f1f5f9;
        vertical-align: middle; text-align: center; color: var(--text-dark);
    }
    .table-kinerja td:nth-child(3) { text-align: left; font-weight: 700; }
    .table-kinerja tbody tr:last-child td { border-bottom: none; }
    .table-kinerja tbody tr:hover td { background: #f8fafc; }

    .btn-detail {
        display: inline-flex; align-items: center; gap: 5px;
        background: #f1f5f9; color: #3b82f6; border: 1px solid #cbd5e1;
        padding: 6px 12px; border-radius: 8px; font-weight: 700; font-size: 11px;
        cursor: pointer; transition: all 0.2s;
    }
    .btn-detail:hover { background: #eff6ff; border-color: #93c5fd; }

    /* ===== MODAL DOKUMEN ===== */
    .modal-overlay {
        display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);
        z-index: 1000; align-items: center; justify-content: center; padding: 16px;
    }
    .modal-overlay.open { display: flex; }
    .modal-doc {
        background: white; border-radius: 16px; width: 100%; max-width: 680px;
        max-height: 92vh; overflow-y: auto;
        box-shadow: 0 20px 50px rgba(0,0,0,0.25);
        animation: slideUp 0.3s ease;
    }
    .modal-doc-header {
        padding: 14px 18px; border-bottom: 1px solid #e2e8f0;
        display: flex; justify-content: space-between; align-items: center;
        position: sticky; top: 0; background: white; z-index: 10;
    }
    .modal-doc-title { font-size: 15px; font-weight: 800; color: var(--text-dark); }
    .modal-doc-actions { display: flex; align-items: center; gap: 8px; }
    .btn-cetak-doc {
        background: linear-gradient(135deg, var(--primary-start) 0%, var(--primary-end) 100%);
        color: white; border: none; padding: 8px 14px; border-radius: 8px;
        font-size: 12px; font-weight: 700; cursor: pointer; display: flex;
        align-items: center; gap: 6px;
    }
    .btn-tutup-doc {
        background: #f1f5f9; border: none; width: 30px; height: 30px;
        border-radius: 50%; color: #64748b; font-size: 18px; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
    }
    .modal-doc-body { padding: 20px 18px; }

    /* Dokumen gaya resmi di dalam modal */
    .doc-kop {
        display: flex; align-items: center; border-bottom: 2px solid #1e293b;
        padding-bottom: 12px; margin-bottom: 14px;
    }
    .doc-kop-logo { width: 50px; height: auto; margin-right: 12px; }
    .doc-kop-text { flex: 1; text-align: center; }
    .doc-kop-text .nama-perusahaan { font-size: 15px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: #1e293b; }
    .doc-kop-text .sub-perusahaan { font-size: 11px; color: #475569; margin-top: 2px; }
    .doc-kop-text .alamat-perusahaan { font-size: 10px; color: #64748b; margin-top: 1px; }

    .doc-judul { text-align: center; margin-bottom: 12px; }
    .doc-judul h3 { font-size: 13px; font-weight: 800; text-transform: uppercase; text-decoration: underline; color: #1e293b; margin: 0 0 3px 0; }
    .doc-judul p { font-size: 11px; color: #475569; margin: 0; }

    .doc-info-karyawan {
        display: grid; grid-template-columns: 1fr 1fr; gap: 4px 20px;
        margin-bottom: 14px; font-size: 12px;
    }
    .doc-info-karyawan .info-item { display: flex; gap: 6px; }
    .doc-info-karyawan .info-label { color: #64748b; white-space: nowrap; }
    .doc-info-karyawan .info-value { font-weight: 700; color: #1e293b; }

    .doc-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; font-size: 11px; }
    .doc-table th {
        background: #e8ecff; color: var(--primary-end); font-weight: 800;
        font-size: 10px; text-transform: uppercase; border: 1px solid #c7d2fe;
        padding: 7px 8px; text-align: center;
    }
    .doc-table td {
        border: 1px solid #e2e8f0; padding: 7px 8px;
        color: #334155; text-align: center; font-size: 11px;
    }
    .doc-table td.text-left { text-align: left; }

    .doc-ttd {
        display: flex; justify-content: space-between; margin-top: 20px;
        text-align: center; font-size: 11px; flex-wrap: wrap; gap: 10px;
    }
    .doc-ttd-col { flex: 1; min-width: 120px; }
    .doc-ttd-col .ttd-label { color: #64748b; margin-bottom: 2px; }
    .doc-ttd-col .ttd-jabatan { font-size: 10px; color: #94a3b8; margin-bottom: 50px; }
    .doc-ttd-col .ttd-line { border-top: 1px solid #334155; padding-top: 4px; font-weight: 700; }

    .doc-footer-note {
        margin-top: 14px; font-size: 9px; color: #94a3b8;
        border-top: 1px solid #e2e8f0; padding-top: 8px; text-align: center;
    }

    @keyframes slideUp {
        from { transform: translateY(30px); opacity: 0; }
        to   { transform: translateY(0);    opacity: 1; }
    }
</style>

<div class="animate-up">
    <a href="index.php" class="btn-back">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
        Kembali
    </a>

    <h2 style="text-align: left; margin-bottom: 16px; font-size: 20px; font-weight: 800; color: var(--text-dark);">Laporan Kinerja Karyawan</h2>

    <div class="card-container">
        <div class="filter-row">
            <form id="filterForm" method="GET" style="flex: 1; min-width: 150px;">
                <label style="font-size: 11px; font-weight: 700; color: #64748b; margin-bottom:4px; display:block;">Pilih Tanggal</label>
                <input type="date" name="tanggal" class="form-input" value="<?= $tanggal ?>" onchange="document.getElementById('filterForm').submit()">
            </form>
            <button class="btn-print" onclick="cetakSemuaDokumen()">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                Cetak PDF
            </button>
        </div>

        <div class="table-responsive">
            <table class="table-kinerja">
                <thead>
                    <tr>
                        <th style="width:30px;">NO</th>
                        <th>NIK</th>
                        <th>NAMA</th>
                        <th>OBJEK KERJA</th>
                        <th>BLOK</th>
                        <th>JAM KERJA</th>
                        <th>STATUS</th>
                        <th>AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    if (count($all_rows) > 0):
                        foreach ($all_rows as $row):
                            $status_label = ucfirst($row['status'] ?? 'ditinjau');
                            $status_color = '#854d0e';
                            if ($row['status'] == 'diterima') $status_color = '#166534';
                            if ($row['status'] == 'ditolak')  $status_color = '#991b1b';

                            $dataJSON = htmlspecialchars(json_encode([
                                'nama'       => $row['karyawan_name'],
                                'nik'        => $row['nik'],
                                'afdeling'   => $row['afdeling'] ?? '-',
                                'kategori'   => $row['kategori_task'] ?? 'perawatan',
                                'objek'      => $row['objek_kerja'] ?? '-',
                                'blok'       => $row['blok'] ?? '-',
                                'luas'       => $row['luas_ha'] ?? '-',
                                'mandor'     => $row['mandor_name'] ?? '-',
                                'h_ton'      => $row['hasil_ton'] ?? '0',
                                'h_kg'       => $row['hasil_kg'] ?? '0',
                                'p_ton'      => $row['prestasi_ton'] ?? '0',
                                'p_kg'       => $row['prestasi_kg'] ?? '0',
                                'tbs'        => $row['tbs'] ?? '0',
                                'kosong'     => $row['tandan_kosong'] ?? '0',
                                'brondol'    => $row['tandan_brondol'] ?? '0',
                                'total'      => $row['total_tandan'] ?? '0',
                                'langsir_kg' => $row['hasil_langsir_kg'] ?? '0',
                                'jam'        => $row['jumlah_jam_kerja'] ?? '0',
                                'aksi'       => ucfirst($row['aksi'] ?? 'Belum'),
                                'status'     => $status_label,
                                'status_color' => $status_color,
                            ]), ENT_QUOTES, 'UTF-8');
                    ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['nik']) ?></td>
                            <td><?= htmlspecialchars($row['karyawan_name']) ?></td>
                            <td><?= htmlspecialchars($row['objek_kerja'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['blok'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['jumlah_jam_kerja'] ?? '-') ?></td>
                            <td><span style="font-weight:700; color:<?= $status_color ?>"><?= $status_label ?></span></td>
                            <td>
                                <button type="button" class="btn-detail" onclick="bukaModalDokumen(<?= $dataJSON ?>)">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                                    Detail
                                </button>
                            </td>
                        </tr>
                    <?php
                        endforeach;
                    else:
                    ?>
                        <tr>
                            <td colspan="8" style="text-align:center; padding:30px; color:var(--text-muted);">Belum ada laporan kinerja untuk tanggal ini.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <p style="font-size: 11px; color: var(--text-muted); font-style: italic; margin-top: 8px;">* Geser tabel jika layar terlalu kecil.</p>
    </div>
</div>

<!-- ===== MODAL DOKUMEN RESMI ===== -->
<div class="modal-overlay" id="modalDokumen">
    <div class="modal-doc">
        <!-- Header Modal -->
        <div class="modal-doc-header">
            <span class="modal-doc-title">📄 Dokumen Kinerja</span>
            <div class="modal-doc-actions">
                <button class="btn-cetak-doc" onclick="cetakDokumenIni()">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                    Cetak
                </button>
                <button class="btn-tutup-doc" onclick="tutupModal()">×</button>
            </div>
        </div>

        <!-- Isi Dokumen -->
        <div class="modal-doc-body" id="isiDokumen">
            <!-- Kop Surat -->
            <div class="doc-kop">
                <img src="../assets/img/logo.png" alt="" class="doc-kop-logo" onerror="this.style.display='none'">
                <div class="doc-kop-text">
                    <div class="nama-perusahaan">PT Damai Jaya Lestari</div>
                    <div class="sub-perusahaan">Laporan Kinerja Harian Karyawan</div>
                    <div class="alamat-perusahaan">Jl. Perkebunan No. 1 | Telp: (021) 000-0000 | admin@djl.co.id</div>
                </div>
            </div>

            <!-- Judul Dokumen -->
            <div class="doc-judul">
                <h3 id="docJudulNama">LAPORAN KINERJA HARIAN</h3>
                <p>Tanggal: <strong><?= $format_tanggal ?></strong></p>
            </div>

            <!-- Info Karyawan -->
            <div class="doc-info-karyawan">
                <div class="info-item"><span class="info-label">Nama&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</span><span class="info-value" id="docNama">-</span></div>
                <div class="info-item"><span class="info-label">Afdeling :</span><span class="info-value" id="docAfdeling">-</span></div>
                <div class="info-item"><span class="info-label">NIK&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</span><span class="info-value" id="docNIK">-</span></div>
                <div class="info-item"><span class="info-label">Mandor&nbsp;&nbsp;:</span><span class="info-value" id="docMandor">-</span></div>
            </div>

            <!-- Tabel Detail -->
            <table class="doc-table">
                <thead id="docThead"></thead>
                <tbody id="docTbody"></tbody>
            </table>

            <!-- Info Aksi -->
            <div id="docInfoAksi" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 12px; font-size:11px; color:#475569; margin-bottom:14px;"></div>

            <!-- Tanda Tangan -->
            <div class="doc-ttd">
                <div class="doc-ttd-col">
                    <div class="ttd-label">Mengetahui,</div>
                    <div class="ttd-jabatan">Manager / Askep</div>
                    <div class="ttd-line">(__________________)</div>
                </div>
                <div class="doc-ttd-col">
                    <div class="ttd-label">Diperiksa,</div>
                    <div class="ttd-jabatan">Pengawas Lapangan</div>
                    <div class="ttd-line">(__________________)</div>
                </div>
                <div class="doc-ttd-col">
                    <div class="ttd-label">Dibuat Oleh,</div>
                    <div class="ttd-jabatan">Kerani / Admin</div>
                    <div class="ttd-line">(__________________)</div>
                </div>
            </div>

            <div class="doc-footer-note">
                Dokumen ini dicetak melalui Sistem Informasi PT Damai Jaya Lestari pada <?= date('d F Y') ?>.
            </div>
        </div>
    </div>
</div>

<script>
let currentKaryawan = '';

function bukaModalDokumen(data) {
    currentKaryawan = data.nama;

    // Isi info karyawan
    document.getElementById('docJudulNama').textContent = 'LAPORAN KINERJA — ' + data.nama.toUpperCase();
    document.getElementById('docNama').textContent     = data.nama;
    document.getElementById('docNIK').textContent      = data.nik;
    document.getElementById('docAfdeling').textContent = data.afdeling;
    document.getElementById('docMandor').textContent   = data.mandor;

    // Build tabel berdasarkan kategori
    const cat = data.kategori;
    const obj = (data.objek || '').toLowerCase();
    const ts  = 'border:1px solid #c7d2fe; padding:7px 8px; font-size:11px;';
    const ths = 'background:#e8ecff; color:#3648d9; font-weight:800; font-size:10px; text-transform:uppercase; border:1px solid #c7d2fe; padding:7px 8px; text-align:center;';

    let thead = '', tbody = '', aksiInfo = '';

    if (cat === 'langsir' || obj.includes('langsir') || obj.includes('membabat')) {
        thead = `<tr>
            <th style="${ths}">Blok</th><th style="${ths}">Luas (Ha)</th><th style="${ths}">Objek Kerja</th>
            <th style="${ths}" colspan="2">Hasil Kerja</th><th style="${ths}" colspan="2">Prestasi</th><th style="${ths}">Status</th>
        </tr><tr>
            <th style="${ths}"></th><th style="${ths}"></th><th style="${ths}"></th>
            <th style="${ths}">Ton</th><th style="${ths}">Kg</th><th style="${ths}">Ton</th><th style="${ths}">Kg</th>
            <th style="${ths}"></th>
        </tr>`;
        tbody = `<tr>
            <td style="${ts}">${data.blok}</td><td style="${ts}">${data.luas}</td><td style="${ts}">${data.objek}</td>
            <td style="${ts}; text-align:center;">${data.h_ton}</td><td style="${ts}; text-align:center;">${data.h_kg}</td>
            <td style="${ts}; text-align:center;">${data.p_ton}</td><td style="${ts}; text-align:center;">${data.p_kg}</td>
            <td style="${ts}; text-align:center; font-weight:700; color:${data.status_color};">${data.status}</td>
        </tr>`;
        aksiInfo = `<strong>Aksi:</strong> ${data.aksi}`;
    } else if (cat === 'potong_buah' || obj.includes('potong') || obj.includes('panen')) {
        thead = `<tr>
            <th style="${ths}">Blok</th><th style="${ths}">Luas (Ha)</th><th style="${ths}">Objek Kerja</th>
            <th style="${ths}" colspan="4">Janjangan</th><th style="${ths}">Status</th>
        </tr><tr>
            <th style="${ths}"></th><th style="${ths}"></th><th style="${ths}"></th>
            <th style="${ths}">TBS</th><th style="${ths}">Kosong</th><th style="${ths}">Brondol</th><th style="${ths}">Total</th>
            <th style="${ths}"></th>
        </tr>`;
        tbody = `<tr>
            <td style="${ts}">${data.blok}</td><td style="${ts}">${data.luas}</td><td style="${ts}">${data.objek}</td>
            <td style="${ts}; text-align:center;">${data.tbs}</td><td style="${ts}; text-align:center;">${data.kosong}</td>
            <td style="${ts}; text-align:center;">${data.brondol}</td><td style="${ts}; text-align:center; font-weight:700;">${data.total}</td>
            <td style="${ts}; text-align:center; font-weight:700; color:${data.status_color};">${data.status}</td>
        </tr>`;
        aksiInfo = `<strong>Aksi:</strong> ${data.aksi}`;
    } else if (cat === 'muat_tbs' || obj.includes('muat')) {
        thead = `<tr>
            <th style="${ths}">Blok</th><th style="${ths}">Objek Kerja</th>
            <th style="${ths}">Langsir (Kg)</th><th style="${ths}">Jam Kerja</th><th style="${ths}">Status</th>
        </tr>`;
        tbody = `<tr>
            <td style="${ts}">${data.blok}</td><td style="${ts}">${data.objek}</td>
            <td style="${ts}; text-align:center; font-weight:700;">${data.langsir_kg} Kg</td>
            <td style="${ts}; text-align:center;">${data.jam}</td>
            <td style="${ts}; text-align:center; font-weight:700; color:${data.status_color};">${data.status}</td>
        </tr>`;
        aksiInfo = `<strong>Aksi:</strong> ${data.aksi}`;
    } else if (cat === 'jaga' || obj.includes('jaga')) {
        thead = `<tr>
            <th style="${ths}">Blok</th><th style="${ths}">Objek Kerja</th>
            <th style="${ths}">Jam Kerja</th><th style="${ths}">Status</th>
        </tr>`;
        tbody = `<tr>
            <td style="${ts}">${data.blok}</td><td style="${ts}">${data.objek}</td>
            <td style="${ts}; text-align:center;">${data.jam}</td>
            <td style="${ts}; text-align:center; font-weight:700; color:${data.status_color};">${data.status}</td>
        </tr>`;
        aksiInfo = `<strong>Aksi:</strong> ${data.aksi}`;
    } else {
        thead = `<tr>
            <th style="${ths}">Blok</th><th style="${ths}">Luas (Ha)</th>
            <th style="${ths}">Objek Kerja</th><th style="${ths}">Jam Kerja</th><th style="${ths}">Status</th>
        </tr>`;
        tbody = `<tr>
            <td style="${ts}">${data.blok}</td><td style="${ts}">${data.luas}</td>
            <td style="${ts}">${data.objek}</td><td style="${ts}; text-align:center;">${data.jam}</td>
            <td style="${ts}; text-align:center; font-weight:700; color:${data.status_color};">${data.status}</td>
        </tr>`;
        aksiInfo = `<strong>Aksi:</strong> ${data.aksi}`;
    }

    document.getElementById('docThead').innerHTML = thead;
    document.getElementById('docTbody').innerHTML = tbody;
    document.getElementById('docInfoAksi').innerHTML = aksiInfo || '<em style="color:#94a3b8;">Tidak ada keterangan tambahan.</em>';

    document.getElementById('modalDokumen').classList.add('open');
}

function tutupModal() {
    document.getElementById('modalDokumen').classList.remove('open');
}

// Cetak dokumen individual (buka window baru)
function cetakDokumenIni() {
    const content = document.getElementById('isiDokumen').innerHTML;
    const win = window.open('', '_blank', 'width=800,height=640');
    win.document.write(`<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Kinerja - ${currentKaryawan}</title>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: 'Times New Roman', Times, serif; padding: 18mm 15mm; font-size: 12pt; color: #000; }
  img { max-width: 60px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 14pt; }
  th, td { border: 1px solid #000; padding: 6pt 7pt; font-size: 10pt; }
  th { background: #e8e8e8; font-weight: bold; }
  .doc-kop { display: flex; align-items: center; border-bottom: 2px solid #000; padding-bottom: 10pt; margin-bottom: 14pt; }
  .doc-kop-text { flex: 1; text-align: center; }
  .nama-perusahaan { font-size: 16pt; font-weight: bold; text-transform: uppercase; }
  .sub-perusahaan { font-size: 11pt; }
  .alamat-perusahaan { font-size: 9pt; color: #555; }
  .doc-judul { text-align: center; margin-bottom: 10pt; }
  .doc-judul h3 { font-size: 13pt; font-weight: bold; text-decoration: underline; text-transform: uppercase; }
  .doc-info-karyawan { display: grid; grid-template-columns: 1fr 1fr; gap: 3pt 16pt; margin-bottom: 12pt; font-size: 11pt; }
  .info-label { color: #555; }
  .info-value { font-weight: bold; }
  .doc-ttd { display: flex; justify-content: space-between; margin-top: 30pt; text-align: center; font-size: 11pt; }
  .doc-ttd-col { flex: 1; }
  .ttd-jabatan { font-size: 9pt; color: #666; margin-bottom: 40pt; }
  .ttd-line { border-top: 1px solid #000; padding-top: 3pt; font-weight: bold; display: inline-block; min-width: 120pt; }
  .doc-footer-note { margin-top: 12pt; font-size: 8pt; color: #888; border-top: 1px solid #ccc; padding-top: 6pt; text-align: center; }
  .doc-kop-logo { width: 55pt; margin-right: 12pt; }
  @page { size: A4 portrait; margin: 18mm 15mm; }
</style>
</head>
<body>${content}</body>
</html>`);
    win.document.close();
    win.focus();
    setTimeout(() => { win.print(); }, 400);
}

// Cetak semua laporan (tombol Cetak PDF di halaman utama)
function cetakSemuaDokumen() {
    const rows = <?= json_encode(array_map(function($r) {
        return [
            'nama'       => $r['karyawan_name'],
            'nik'        => $r['nik'],
            'afdeling'   => $r['afdeling'] ?? '-',
            'objek'      => $r['objek_kerja'] ?? '-',
            'blok'       => $r['blok'] ?? '-',
            'luas'       => $r['luas_ha'] ?? '-',
            'mandor'     => $r['mandor_name'] ?? '-',
            'jam'        => $r['jumlah_jam_kerja'] ?? '-',
            'status'     => ucfirst($r['status'] ?? 'ditinjau'),
        ];
    }, $all_rows)) ?>;

    const tanggal = '<?= $format_tanggal ?>';
    const afdeling = '<?= htmlspecialchars($afdeling_pengawas) ?>';

    let tableRows = rows.map((r, i) => `
        <tr>
            <td style="text-align:center;">${i+1}</td>
            <td>${r.nik}</td>
            <td style="font-weight:bold;">${r.nama}</td>
            <td>${r.afdeling}</td>
            <td>${r.objek}</td>
            <td style="text-align:center;">${r.blok} / ${r.luas}</td>
            <td style="text-align:center;">${r.jam}</td>
            <td>${r.mandor}</td>
            <td style="text-align:center; font-weight:bold;">${r.status}</td>
        </tr>
    `).join('');

    const win = window.open('', '_blank', 'width=1000,height=720');
    win.document.write(`<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Kinerja - ${tanggal}</title>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: 'Times New Roman', Times, serif; padding: 12mm; font-size: 11pt; color: #000; }
  .kop { display:flex; align-items:center; border-bottom:3px solid #000; padding-bottom:10pt; margin-bottom:14pt; }
  .kop img { width:55pt; margin-right:12pt; }
  .kop-text { flex:1; text-align:center; }
  .kop-text h1 { font-size:16pt; font-weight:bold; text-transform:uppercase; margin:0; }
  .kop-text p { font-size:10pt; margin:2pt 0 0 0; }
  .judul { text-align:center; margin-bottom:10pt; }
  .judul h2 { font-size:13pt; font-weight:bold; text-decoration:underline; text-transform:uppercase; }
  .meta { display:flex; gap:40pt; margin-bottom:10pt; font-size:10.5pt; }
  table { width:100%; border-collapse:collapse; margin-bottom:14pt; }
  th, td { border:1px solid #000; padding:6pt 7pt; font-size:9.5pt; }
  th { background:#e8e8e8; font-weight:bold; text-align:center; text-transform:uppercase; }
  .ttd { display:flex; justify-content:space-between; margin-top:28pt; text-align:center; font-size:10.5pt; }
  .ttd-col { flex:1; }
  .ttd-col p { margin-bottom:40pt; }
  .ttd-col .garis { border-top:1px solid #000; padding-top:4pt; font-weight:bold; display:inline-block; min-width:110pt; }
  .catatan { margin-top:12pt; font-size:8pt; color:#888; border-top:1px solid #ccc; padding-top:6pt; }
  @page { size: A4 landscape; margin: 12mm; }
</style>
</head>
<body>
  <div class="kop">
    <img src="../assets/img/logo.png" onerror="this.style.display='none'" alt="">
    <div class="kop-text">
      <h1>PT Damai Jaya Lestari</h1>
      <p>Laporan Kinerja Harian Karyawan</p>
      <p>Jl. Perkebunan No. 1 | Telp: (021) 000-0000 | admin@djl.co.id</p>
    </div>
  </div>
  <div class="judul">
    <h2>Laporan Kinerja Harian — ${tanggal}</h2>
  </div>
  <div class="meta">
    <div><strong>Afdeling :</strong> ${afdeling || 'Semua Afdeling'}</div>
    <div><strong>Jumlah   :</strong> ${rows.length} karyawan</div>
    <div><strong>Dicetak  :</strong> ${new Date().toLocaleDateString('id-ID', {day:'2-digit',month:'long',year:'numeric'})}</div>
  </div>
  <table>
    <thead>
      <tr>
        <th style="width:25pt;">No</th><th>NIK</th><th>Nama Karyawan</th>
        <th>Afdeling</th><th>Objek Kerja</th><th>Blok / Luas (Ha)</th>
        <th>Jam Kerja</th><th>Mandor</th><th>Status</th>
      </tr>
    </thead>
    <tbody>${rows.length > 0 ? tableRows : '<tr><td colspan="9" style="text-align:center;padding:14pt;">Tidak ada data.</td></tr>'}</tbody>
  </table>
  <div class="ttd">
    <div class="ttd-col"><p>Mengetahui,<br><small>Manager / Askep</small></p><span class="garis">(__________________)</span></div>
    <div class="ttd-col"><p>Diperiksa,<br><small>Pengawas Lapangan</small></p><span class="garis">(__________________)</span></div>
    <div class="ttd-col"><p>Dibuat Oleh,<br><small>Kerani / Admin</small></p><span class="garis">(__________________)</span></div>
  </div>
  <div class="catatan">Dokumen ini dicetak secara otomatis oleh Sistem Informasi PT Damai Jaya Lestari.</div>
</body>
</html>`);
    win.document.close();
    win.focus();
    setTimeout(() => { win.print(); }, 400);
}

// Tutup modal saat klik di luar
document.getElementById('modalDokumen').addEventListener('click', function(e) {
    if (e.target === this) tutupModal();
});
</script>

<?php include 'templates/footer.php'; ?>
