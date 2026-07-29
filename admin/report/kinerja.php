<?php
require '../../config/config.php';
include '../templates/header.php';

// Filter parameter
$tanggal  = isset($_GET['tanggal'])  ? $_GET['tanggal']  : date('Y-m-d');
$afdeling = isset($_GET['afdeling']) ? $_GET['afdeling'] : '';

$nama_bulan = [
    '01'=>'Januari','02'=>'Februari','03'=>'Maret',
    '04'=>'April',  '05'=>'Mei',     '06'=>'Juni',
    '07'=>'Juli',   '08'=>'Agustus', '09'=>'September',
    '10'=>'Oktober','11'=>'November','12'=>'Desember'
];
$tgl_pecah      = explode('-', $tanggal);
$format_tanggal = $tgl_pecah[2] . ' ' . $nama_bulan[$tgl_pecah[1]] . ' ' . $tgl_pecah[0];

// Filter query
$where_conditions = ["u.role='karyawan'"];
if (!empty($afdeling)) {
    $where_conditions[] = "u.afdeling = '" . mysqli_real_escape_string($conn, $afdeling) . "'";
}
$where_clause = "WHERE " . implode(' AND ', $where_conditions);

$tgl_safe    = mysqli_real_escape_string($conn, $tanggal);
$query_users = mysqli_query($conn, "
    SELECT u.id, u.nik, u.name, u.jabatan, u.afdeling,
           lk.status as lk_status, lk.kategori_task, lk.blok, lk.luas_ha,
           lk.objek_kerja, lk.hasil_ton, lk.hasil_kg, lk.prestasi_ton, lk.prestasi_kg,
           lk.tbs, lk.tandan_kosong, lk.tandan_brondol, lk.total_tandan,
           lk.hasil_langsir_kg, lk.jumlah_jam_kerja, lk.aksi,
           m.name as mandor_name
    FROM users u
    LEFT JOIN logbook_kinerja lk ON u.id = lk.user_id AND lk.tanggal = '$tgl_safe'
    LEFT JOIN users m ON lk.mandor_id = m.id
    $where_clause
    ORDER BY u.name ASC
");

// Kumpulkan + proses semua data
$print_data = [];
while ($user = mysqli_fetch_assoc($query_users)) {
    $cat = $user['kategori_task'] ?? '';
    $obj = strtolower($user['objek_kerja'] ?? '');
    $detail = '-';
    if ($cat==='langsir' || strpos($obj,'langsir')!==false || strpos($obj,'membabat')!==false) {
        $detail = "Hasil: {$user['hasil_ton']} Ton {$user['hasil_kg']} Kg | Prestasi: {$user['prestasi_ton']} Ton {$user['prestasi_kg']} Kg";
    } elseif ($cat==='potong_buah' || strpos($obj,'potong')!==false || strpos($obj,'panen')!==false) {
        $detail = "TBS: {$user['tbs']} | Kosong: {$user['tandan_kosong']} | Brondol: {$user['tandan_brondol']} | Total: {$user['total_tandan']}";
    } elseif ($cat==='muat_tbs' || strpos($obj,'muat')!==false) {
        $detail = "Langsir: {$user['hasil_langsir_kg']} Kg | Jam: {$user['jumlah_jam_kerja']}";
    } elseif ($cat==='jaga' || strpos($obj,'jaga')!==false) {
        $detail = "Jam Kerja: {$user['jumlah_jam_kerja']}";
    } elseif (!empty($user['objek_kerja'])) {
        $detail = "Aksi: " . ($user['aksi'] ?? '-');
    }
    $user['_detail'] = $detail;
    $print_data[] = $user;
}
?>

<style>
    /* Tombol file di tabel kinerja */
    .kinerja-tbl { width: 100%; border-collapse: collapse; }
    .kinerja-tbl th {
        background-color: #f8fafc; color: #475569; font-weight: 700; text-transform: uppercase;
        font-size: 11px; letter-spacing: 0.5px; padding: 14px 16px; text-align: left; border-bottom: 2px solid #e2e8f0;
    }
    .kinerja-tbl td {
        padding: 14px 16px; border-bottom: 1px solid #f1f5f9; color: #334155; font-size: 14px; vertical-align: middle;
    }
    .kinerja-tbl tbody tr:last-child td { border-bottom: none; }
    .kinerja-tbl tbody tr:hover td { background-color: #f8fafc; }

    /* Modal (UI only) */
    #fileModal.show { display: flex; opacity: 1; }
    .modal-table { width: 100%; border-collapse: collapse; }
    .modal-table th { background: #f8fafc; color: #475569; font-size: 12px; font-weight: 700; text-transform: uppercase; padding: 10px 12px; border-bottom: 2px solid #e2e8f0; text-align: center; }
    .modal-table td { padding: 12px; border-bottom: 1px solid #f1f5f9; color: #334155; font-size: 13px; text-align: center; }
    .modal-table tbody tr:last-child td { border-bottom: none; }
</style>

<!-- ========== WEB UI ========== -->
<div class="report-wrapper">
    <form method="GET" action="kinerja.php" id="formFilter">
        <div class="report-top-bar">
            <div class="filter-group">
                <span class="filter-label">Pilih Periode:</span>
                <input type="date" name="tanggal" class="form-input" value="<?= $tanggal ?>" onchange="document.getElementById('formFilter').submit();">

                <span class="filter-label">Afdeling:</span>
                <select name="afdeling" class="form-select" onchange="document.getElementById('formFilter').submit();">
                    <option value="">Semua</option>
                    <?php
                    $afd_q = mysqli_query($conn, "SELECT nama_afdeling FROM afdelings ORDER BY nama_afdeling ASC");
                    while ($afd = mysqli_fetch_assoc($afd_q)):
                    ?>
                        <option value="<?= htmlspecialchars($afd['nama_afdeling']) ?>" <?= $afdeling==$afd['nama_afdeling'] ? 'selected':'' ?>>
                            <?= htmlspecialchars($afd['nama_afdeling']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="report-title-center">
                <h2>Laporan Kinerja Harian</h2>
                <p>Tanggal: <strong><?= $format_tanggal ?></strong>
                    <?= !empty($afdeling) ? ' &mdash; '.htmlspecialchars($afdeling) : '' ?>
                </p>
            </div>

            <button type="button" class="btn btn-primary" onclick="window.print()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                Cetak Dokumen
            </button>
        </div>
    </form>

    <div class="table-container">
        <table class="kinerja-tbl">
            <thead>
                <tr>
                    <th width="4%" class="text-center">NO</th>
                    <th width="13%">NIK</th>
                    <th width="20%">NAMA</th>
                    <th width="13%">AFDELING</th>
                    <th width="25%" class="text-center">LAPORAN KERJA</th>
                    <th width="15%" class="text-center">STATUS</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($print_data) > 0): $no = 1; foreach ($print_data as $user):
                    $status = $user['lk_status'] ? ucfirst($user['lk_status']) : 'Belum';
                    $badge  = (strtolower($status)=='diterima'||strtolower($status)=='selesai') ? 'badge-success' : 'badge-warning';

                    $dataJSON = htmlspecialchars(json_encode([
                        'kategori'  => $user['kategori_task'] ?? 'perawatan',
                        'objek'     => $user['objek_kerja'] ?? '-',
                        'blok'      => $user['blok'] ?? '-',
                        'luas'      => $user['luas_ha'] ?? '-',
                        'mandor'    => $user['mandor_name'] ?? '-',
                        'h_ton'     => $user['hasil_ton'] ?? '0',
                        'h_kg'      => $user['hasil_kg'] ?? '0',
                        'p_ton'     => $user['prestasi_ton'] ?? '0',
                        'p_kg'      => $user['prestasi_kg'] ?? '0',
                        'tbs'       => $user['tbs'] ?? '0',
                        'kosong'    => $user['tandan_kosong'] ?? '0',
                        'brondol'   => $user['tandan_brondol'] ?? '0',
                        'total'     => $user['total_tandan'] ?? '0',
                        'langsir'   => $user['hasil_langsir_kg'] ?? '0',
                        'jam'       => $user['jumlah_jam_kerja'] ?? '0',
                        'aksi'      => ucfirst($user['aksi'] ?? '-'),
                        'status'    => $status,
                        'badge'     => $badge,
                    ]), ENT_QUOTES, 'UTF-8');
                ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td><?= htmlspecialchars($user['nik']) ?></td>
                        <td style="font-weight:600;"><?= htmlspecialchars($user['name']) ?></td>
                        <td><?= htmlspecialchars($user['afdeling'] ?? '-') ?></td>
                        <td class="text-center">
                            <?php if ($user['lk_status']): ?>
                                <button onclick="openModal('<?= htmlspecialchars($user['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($user['nik'], ENT_QUOTES) ?>', '<?= htmlspecialchars($user['afdeling'] ?? '-', ENT_QUOTES) ?>', <?= $dataJSON ?>)" class="btn-file">
                                    Lihat File
                                </button>
                            <?php else: ?>
                                <span class="text-muted" style="font-style:italic;">Belum lapor</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><span class="badge <?= $badge ?>"><?= $status ?></span></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="6" style="padding:30px; text-align:center; color:#94a3b8;">Tidak ada data karyawan.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ========== MODAL DOKUMEN KINERJA (Tampilan Dokumen Resmi) ========== -->
<div id="fileModal" class="modal-overlay">
    <div class="modal-box" style="max-width: 820px; max-height: 90vh; overflow-y: auto;">
        <!-- Header Modal -->
        <div class="modal-header">
            <div>
                <p class="modal-title">📄 Dokumen Kinerja Karyawan</p>
                <p class="modal-subtitle" id="modalSubtitle"><?= $format_tanggal ?></p>
            </div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <button class="btn btn-primary btn-sm" onclick="cetakDokumenModal()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                    Cetak Dokumen Ini
                </button>
                <button class="modal-close" onclick="tutupModal()">&times;</button>
            </div>
        </div>

        <!-- Isi Modal: Dokumen Resmi -->
        <div class="modal-body" style="padding: 28px;" id="docModalContent">

            <!-- Kop Surat -->
            <div style="display:flex; align-items:center; border-bottom: 3px solid #1e293b; padding-bottom: 14px; margin-bottom: 18px;">
                <img src="<?= BASE_URL ?>assets/img/logo.png" alt="" style="width:60px; height:auto; margin-right:16px;" onerror="this.style.display='none'">
                <div style="flex:1; text-align:center;">
                    <div style="font-size:18px; font-weight:800; text-transform:uppercase; letter-spacing:1px; color:#1e293b;">PT Damai Jaya Lestari</div>
                    <div style="font-size:13px; color:#475569; margin-top:2px;">Laporan Kinerja Harian Karyawan</div>
                    <div style="font-size:11px; color:#64748b; margin-top:2px;">Jl. Perkebunan No. 1 | Telp: (021) 000-0000 | Email: admin@djl.co.id</div>
                </div>
            </div>

            <!-- Judul -->
            <div style="text-align:center; margin-bottom:14px;">
                <div style="font-size:15px; font-weight:800; text-transform:uppercase; text-decoration:underline; color:#1e293b;" id="docJudul">LAPORAN KINERJA HARIAN</div>
                <div style="font-size:13px; color:#475569; margin-top:4px;" id="docTanggal">Tanggal: <?= $format_tanggal ?></div>
            </div>

            <!-- Info Karyawan -->
            <div style="display:flex; gap:30px; margin-bottom:16px; font-size:13px; flex-wrap:wrap;">
                <div><span style="color:#64748b;">Nama Karyawan&nbsp;:</span> <strong id="docNama">-</strong></div>
                <div><span style="color:#64748b;">NIK&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</span> <strong id="docNIK">-</strong></div>
                <div><span style="color:#64748b;">Afdeling&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</span> <strong id="docAfdeling">-</strong></div>
                <div><span style="color:#64748b;">Mandor&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</span> <strong id="docMandor">-</strong></div>
            </div>

            <!-- Tabel Detail Kinerja -->
            <table id="docTable" style="width:100%; border-collapse:collapse; font-size:13px; margin-bottom:20px;">
                <thead id="docThead" style="background:#f1f5f9;"></thead>
                <tbody id="docTbody"></tbody>
            </table>

            <!-- Info Tambahan -->
            <div id="docInfo" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px; font-size:12px; color:#475569; margin-bottom:20px;">
            </div>

            <!-- Tanda Tangan -->
            <div style="display:flex; justify-content:space-between; margin-top:30px; text-align:center; font-size:13px; flex-wrap:wrap; gap:20px;">
                <div style="min-width:160px;">
                    <div>Mengetahui,</div>
                    <div style="color:#64748b; font-size:12px;">Manager / Askep</div>
                    <div style="margin-top:60px; border-top:1px solid #334155; padding-top:4px; font-weight:700;">(_____________________)</div>
                </div>
                <div style="min-width:160px;">
                    <div>Diperiksa Oleh,</div>
                    <div style="color:#64748b; font-size:12px;">Pengawas Lapangan</div>
                    <div style="margin-top:60px; border-top:1px solid #334155; padding-top:4px; font-weight:700;">(_____________________)</div>
                </div>
                <div style="min-width:160px;">
                    <div>Dibuat Oleh,</div>
                    <div style="color:#64748b; font-size:12px;">Kerani / Admin</div>
                    <div style="margin-top:60px; border-top:1px solid #334155; padding-top:4px; font-weight:700;">(_____________________)</div>
                </div>
            </div>

            <!-- Footer Dokumen -->
            <div style="margin-top:20px; font-size:10px; color:#94a3b8; border-top:1px solid #e2e8f0; padding-top:8px;">
                Dokumen ini dicetak melalui Sistem Informasi PT Damai Jaya Lestari pada <?= date('d F Y') ?>.
            </div>
        </div>
    </div>
</div>

<!-- ========== DOKUMEN CETAK SEMUA (Cetak Dokumen tombol utama) ========== -->
<div id="official-print-doc">
    <style>@media print { @page { size: landscape; margin: 12mm; } }</style>

    <!-- Kop Surat -->
    <div class="doc-header">
        <img src="<?= BASE_URL ?>assets/img/logo.png" alt="" class="doc-header-logo" onerror="this.style.display='none'">
        <div class="doc-header-text">
            <h1>PT Damai Jaya Lestari</h1>
            <h2>Laporan Kinerja Harian Karyawan</h2>
            <p>Jl. Perkebunan No. 1 &nbsp;|&nbsp; Telp: (021) 000-0000 &nbsp;|&nbsp; Email: admin@djl.co.id</p>
        </div>
    </div>

    <div class="doc-title">
        <h3>Laporan Kinerja &mdash; <?= $format_tanggal ?></h3>
    </div>
    <div class="doc-meta">
        <div><strong>Afdeling&nbsp;:</strong> <?= !empty($afdeling) ? htmlspecialchars($afdeling) : 'Semua Afdeling' ?></div>
        <div><strong>Jumlah Karyawan:</strong> <?= count($print_data) ?> orang</div>
        <div><strong>Dicetak&nbsp;&nbsp;:</strong> <?= date('d F Y, H:i') ?> WIB</div>
    </div>

    <table class="doc-table">
        <thead>
            <tr>
                <th style="width:25px;">No</th>
                <th style="width:80px; text-align:left;">NIK</th>
                <th style="min-width:120px; text-align:left;">Nama Karyawan</th>
                <th style="width:70px; text-align:left;">Afdeling</th>
                <th style="min-width:100px; text-align:left;">Objek Kerja</th>
                <th style="width:80px;">Blok / Luas (Ha)</th>
                <th style="min-width:160px; text-align:left;">Detail Hasil Kerja</th>
                <th style="width:80px;">Mandor</th>
                <th style="width:60px;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($print_data) > 0): $pno=1; foreach($print_data as $p): ?>
            <tr>
                <td class="text-center"><?= $pno++ ?></td>
                <td><?= htmlspecialchars($p['nik']) ?></td>
                <td><?= htmlspecialchars($p['name']) ?></td>
                <td><?= htmlspecialchars($p['afdeling'] ?? '-') ?></td>
                <td><?= htmlspecialchars($p['objek_kerja'] ?? '-') ?></td>
                <td class="text-center"><?= htmlspecialchars($p['blok']??'-') ?> / <?= htmlspecialchars($p['luas_ha']??'-') ?></td>
                <td><?= htmlspecialchars($p['_detail']) ?></td>
                <td><?= htmlspecialchars($p['mandor_name']??'-') ?></td>
                <td class="text-center"><?= $p['lk_status'] ? ucfirst($p['lk_status']) : 'Belum' ?></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="9" class="text-center" style="padding:16px;">Tidak ada data kinerja karyawan pada tanggal ini.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="doc-signature">
        <div class="doc-signature-col">
            <p>Mengetahui,<br>Manager / Askep</p>
            <span class="sig-name">(_____________________)</span>
        </div>
        <div class="doc-signature-col">
            <p>Diperiksa Oleh,<br>Pengawas Lapangan</p>
            <span class="sig-name">(_____________________)</span>
        </div>
        <div class="doc-signature-col">
            <p>Dibuat Oleh,<br>Kerani / Admin</p>
            <span class="sig-name">(_____________________)</span>
        </div>
    </div>
    <div class="doc-footer">
        Dokumen ini dicetak secara otomatis oleh Sistem Informasi PT Damai Jaya Lestari pada <?= date('d F Y \p\u\k\u\l H:i') ?> WIB.
    </div>
</div>

<script>
const modal = document.getElementById('fileModal');

// Data per karyawan diisi saat openModal dipanggil
let currentDocName = '';

function openModal(name, nik, afdeling, data) {
    currentDocName = name;

    // Isi info karyawan di modal
    document.getElementById('docNama').textContent     = name;
    document.getElementById('docNIK').textContent      = nik;
    document.getElementById('docAfdeling').textContent = afdeling;
    document.getElementById('docMandor').textContent   = data.mandor;
    document.getElementById('docJudul').textContent    = 'LAPORAN KINERJA HARIAN — ' + name.toUpperCase();

    const cat = data.kategori, obj = (data.objek||'').toLowerCase();
    let thead = '', tbody = '', info = '';

    const tdStyle = 'border:1px solid #cbd5e1; padding:9px 12px; font-size:13px;';
    const thStyle = 'border:1px solid #cbd5e1; padding:9px 12px; background:#f1f5f9; font-weight:700; font-size:12px; text-transform:uppercase; color:#475569;';

    if (cat === 'langsir' || obj.includes('langsir') || obj.includes('membabat')) {
        thead = `<tr>
            <th style="${thStyle}">Blok</th><th style="${thStyle}">Luas (Ha)</th><th style="${thStyle}">Objek Kerja</th>
            <th style="${thStyle}" colspan="2">Hasil Kerja</th><th style="${thStyle}" colspan="2">Prestasi</th><th style="${thStyle}">Status</th>
        </tr><tr>
            <th style="${thStyle}"></th><th style="${thStyle}"></th><th style="${thStyle}"></th>
            <th style="${thStyle}">Ton</th><th style="${thStyle}">Kg</th><th style="${thStyle}">Ton</th><th style="${thStyle}">Kg</th>
            <th style="${thStyle}"></th>
        </tr>`;
        tbody = `<tr>
            <td style="${tdStyle}">${data.blok}</td><td style="${tdStyle}">${data.luas}</td><td style="${tdStyle}">${data.objek}</td>
            <td style="${tdStyle}; text-align:center;">${data.h_ton}</td><td style="${tdStyle}; text-align:center;">${data.h_kg}</td>
            <td style="${tdStyle}; text-align:center;">${data.p_ton}</td><td style="${tdStyle}; text-align:center;">${data.p_kg}</td>
            <td style="${tdStyle}; text-align:center; font-weight:700;">${data.status}</td>
        </tr>`;
        info = `<strong>Aksi:</strong> ${data.aksi}`;
    } else if (cat === 'potong_buah' || obj.includes('potong') || obj.includes('panen')) {
        thead = `<tr>
            <th style="${thStyle}">Blok</th><th style="${thStyle}">Luas (Ha)</th><th style="${thStyle}">Objek Kerja</th>
            <th style="${thStyle}" colspan="4">Data Janjangan</th><th style="${thStyle}">Status</th>
        </tr><tr>
            <th style="${thStyle}"></th><th style="${thStyle}"></th><th style="${thStyle}"></th>
            <th style="${thStyle}">TBS</th><th style="${thStyle}">Kosong</th><th style="${thStyle}">Brondol</th><th style="${thStyle}">Total</th>
            <th style="${thStyle}"></th>
        </tr>`;
        tbody = `<tr>
            <td style="${tdStyle}">${data.blok}</td><td style="${tdStyle}">${data.luas}</td><td style="${tdStyle}">${data.objek}</td>
            <td style="${tdStyle}; text-align:center;">${data.tbs}</td><td style="${tdStyle}; text-align:center;">${data.kosong}</td>
            <td style="${tdStyle}; text-align:center;">${data.brondol}</td><td style="${tdStyle}; text-align:center; font-weight:700;">${data.total}</td>
            <td style="${tdStyle}; text-align:center; font-weight:700;">${data.status}</td>
        </tr>`;
        info = `<strong>Aksi:</strong> ${data.aksi}`;
    } else if (cat === 'muat_tbs' || obj.includes('muat')) {
        thead = `<tr>
            <th style="${thStyle}">Blok</th><th style="${thStyle}">Objek Kerja</th>
            <th style="${thStyle}">Hasil Langsir (Kg)</th><th style="${thStyle}">Jam Kerja</th><th style="${thStyle}">Status</th>
        </tr>`;
        tbody = `<tr>
            <td style="${tdStyle}">${data.blok}</td><td style="${tdStyle}">${data.objek}</td>
            <td style="${tdStyle}; text-align:center; font-weight:700;">${data.langsir} Kg</td>
            <td style="${tdStyle}; text-align:center;">${data.jam}</td>
            <td style="${tdStyle}; text-align:center; font-weight:700;">${data.status}</td>
        </tr>`;
        info = `<strong>Aksi:</strong> ${data.aksi}`;
    } else if (cat === 'jaga' || obj.includes('jaga')) {
        thead = `<tr>
            <th style="${thStyle}">Blok</th><th style="${thStyle}">Objek Kerja</th>
            <th style="${thStyle}">Jam Kerja</th><th style="${thStyle}">Status</th>
        </tr>`;
        tbody = `<tr>
            <td style="${tdStyle}">${data.blok}</td><td style="${tdStyle}">${data.objek}</td>
            <td style="${tdStyle}; text-align:center;">${data.jam}</td>
            <td style="${tdStyle}; text-align:center; font-weight:700;">${data.status}</td>
        </tr>`;
        info = `<strong>Aksi:</strong> ${data.aksi}`;
    } else {
        thead = `<tr>
            <th style="${thStyle}">Blok</th><th style="${thStyle}">Luas (Ha)</th>
            <th style="${thStyle}">Objek Kerja</th><th style="${thStyle}">Aksi</th><th style="${thStyle}">Status</th>
        </tr>`;
        tbody = `<tr>
            <td style="${tdStyle}">${data.blok}</td><td style="${tdStyle}">${data.luas}</td>
            <td style="${tdStyle}">${data.objek}</td><td style="${tdStyle}">${data.aksi}</td>
            <td style="${tdStyle}; text-align:center; font-weight:700;">${data.status}</td>
        </tr>`;
    }

    document.getElementById('docThead').innerHTML = thead;
    document.getElementById('docTbody').innerHTML = tbody;
    document.getElementById('docInfo').innerHTML  = info || '<em style="color:#94a3b8;">Tidak ada keterangan tambahan.</em>';
    modal.classList.add('show');
}

function tutupModal() {
    modal.classList.remove('show');
}

// Cetak dokumen individual per karyawan menggunakan window baru
function cetakDokumenModal() {
    const content = document.getElementById('docModalContent').innerHTML;
    const win = window.open('', '_blank', 'width=900,height=700');
    win.document.write(`<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Kinerja - ${currentDocName}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Times New Roman', Times, serif; padding: 20mm; font-size: 12pt; color: #000; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 18pt; }
        th, td { border: 1px solid #000; padding: 6pt 8pt; }
        @page { size: A4 portrait; margin: 20mm; }
    </style>
</head>
<body>${content}</body>
</html>`);
    win.document.close();
    win.focus();
    setTimeout(() => { win.print(); }, 400);
}

modal.addEventListener('click', e => { if (e.target === modal) tutupModal(); });
</script>

<?php include '../templates/footer.php'; ?>

