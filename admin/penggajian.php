<?php
require '../config/config.php';
include 'templates/header.php';

// Filter parameter
$afdeling = isset($_GET['afdeling']) ? $_GET['afdeling'] : '';
$jabatan = isset($_GET['jabatan']) ? $_GET['jabatan'] : '';
$bulan = isset($_GET['bulan']) ? str_pad($_GET['bulan'], 2, '0', STR_PAD_LEFT) : date('m');
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');

$nama_bulan = [
    '01'=>'Januari', '02'=>'Februari', '03'=>'Maret', '04'=>'April', 
    '05'=>'Mei', '06'=>'Juni', '07'=>'Juli', '08'=>'Agustus', 
    '09'=>'September', '10'=>'Oktober', '11'=>'November', '12'=>'Desember'
];

// Menangani Form Submit (Simpan / Kirim)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action_status = ($_POST['action'] === 'publish') ? 'published' : 'draft';
    
    if (isset($_POST['gaji'])) {
        $saved = 0;
        foreach ($_POST['gaji'] as $uid => $d) {
            $user_id = (int)$uid;
            
            // Konversi format angka dari input form ke format float DB
            $hk = (float)str_replace(',', '.', $d['hk']);
            $tarif = (float)str_replace(['.', ','], ['', '.'], $d['tarif']);
            $lembur = (float)str_replace(['.', ','], ['', '.'], $d['lembur']);
            $premi = (float)str_replace(['.', ','], ['', '.'], $d['premi']);
            $bpjs = (float)str_replace(['.', ','], ['', '.'], $d['bpjs']);
            $koperasi = (float)str_replace(['.', ','], ['', '.'], $d['koperasi']);
            
            $gaji_kotor = ($hk * $tarif) + $lembur + $premi;
            $gaji_bersih = $gaji_kotor - ($bpjs + $koperasi);
            
            // Cek apakah data sudah ada
            $cek = mysqli_query($conn, "SELECT id FROM penggajian WHERE user_id=$user_id AND periode_bulan='$bulan' AND periode_tahun='$tahun'");
            if (mysqli_num_rows($cek) > 0) {
                // Update
                $sql = "UPDATE penggajian SET 
                            hk_dibayar=$hk, tarif_hk=$tarif, uang_lembur=$lembur, uang_premi=$premi, 
                            potongan_bpjs=$bpjs, potongan_koperasi=$koperasi, 
                            gaji_kotor=$gaji_kotor, gaji_bersih=$gaji_bersih, status='$action_status'
                        WHERE user_id=$user_id AND periode_bulan='$bulan' AND periode_tahun='$tahun'";
                mysqli_query($conn, $sql);
            } else {
                // Insert
                $sql = "INSERT INTO penggajian (user_id, periode_bulan, periode_tahun, hk_dibayar, tarif_hk, uang_lembur, uang_premi, potongan_bpjs, potongan_koperasi, gaji_kotor, gaji_bersih, status) 
                        VALUES ($user_id, '$bulan', '$tahun', $hk, $tarif, $lembur, $premi, $bpjs, $koperasi, $gaji_kotor, $gaji_bersih, '$action_status')";
                mysqli_query($conn, $sql);
            }
            $saved++;
        }
        
        $msg = ($action_status == 'published') ? "Slip gaji berhasil diterbitkan!" : "Draft gaji berhasil disimpan!";
        swalRedirect($msg, "penggajian.php?afdeling=$afdeling&jabatan=$jabatan&bulan=$bulan&tahun=$tahun", 'success');
        exit;
    }
}

// Ambil data karyawan jika afdeling dipilih
$users = [];
$total_published = 0;
if (!empty($afdeling)) {
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
    while ($row = mysqli_fetch_assoc($query)) {
        $users[] = $row;
        if ($row['status'] == 'published') $total_published++;
    }
}
?>

<style>
    .card-container {
        background: #ffffff;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(54, 72, 217, 0.05);
        border: 1px solid #f1f5f9;
        margin-bottom: 24px;
    }

    .filter-bar {
        display: flex; gap: 16px; align-items: flex-end; margin-bottom: 24px; flex-wrap: wrap;
    }
    
    .filter-bar .form-group { margin: 0; flex: 1; min-width: 150px; }
    
    .table-responsive { width: 100%; overflow-x: auto; border-radius: 10px; border: 1px solid #e2e8f0; }
    
    .table-gaji { width: 100%; border-collapse: collapse; white-space: nowrap; font-size: 13px; }
    .table-gaji th {
        background: var(--primary-light); color: var(--primary-end); font-weight: 800;
        font-size: 11px; text-transform: uppercase; padding: 14px 12px; border: 1px solid #c7d2fe; text-align: center;
    }
    .table-gaji td {
        padding: 10px 8px; border: 1px solid #e2e8f0; vertical-align: middle; text-align: center;
    }
    .table-gaji td.text-left { text-align: left; }
    
    .input-gaji {
        width: 90px; padding: 8px 10px; border: 1.5px solid #cbd5e1; border-radius: 8px;
        font-size: 13px; text-align: right; outline: none; transition: border-color 0.2s;
    }
    .input-gaji:focus { border-color: var(--primary-start); box-shadow: 0 0 0 3px rgba(66,88,255,0.1); }
    
    .input-gaji.readonly { background: #f8fafc; border-color: #e2e8f0; color: #64748b; font-weight: 700; cursor: not-allowed; width: 110px; }
    .input-hk { width: 60px; text-align: center; }

    .btn-action-group { display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px; }
    
    .badge-status { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 800; display: inline-block; }
    .status-draft { background: #ffedd5; color: #9a3412; }
    .status-published { background: #dcfce7; color: #166534; }
    .status-none { background: #f1f5f9; color: #64748b; }
</style>

<div class="report-wrapper">
    <div class="report-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h2 class="page-title">Data Penggajian Karyawan</h2>
        <?php if (!empty($afdeling)): ?>
        <a href="cetak_penggajian.php?afdeling=<?= urlencode($afdeling) ?>&jabatan=<?= urlencode($jabatan) ?>&bulan=<?= urlencode($bulan) ?>&tahun=<?= urlencode($tahun) ?>" target="_blank" class="btn-action btn-primary" style="text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            Cetak Laporan
        </a>
        <?php endif; ?>
    </div>

    <div class="card-container">
        <!-- Filter Form -->
        <form id="filterForm" method="GET" class="filter-bar">
            <div class="form-group">
                <label style="font-size:12px; font-weight:700; color:#64748b; margin-bottom:6px; display:block;">Bulan</label>
                <select name="bulan" class="form-select" onchange="document.getElementById('filterForm').submit()">
                    <?php foreach($nama_bulan as $num => $name): ?>
                        <option value="<?= $num ?>" <?= $bulan == $num ? 'selected' : '' ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label style="font-size:12px; font-weight:700; color:#64748b; margin-bottom:6px; display:block;">Tahun</label>
                <select name="tahun" class="form-select" onchange="document.getElementById('filterForm').submit()">
                    <?php for($y = date('Y')-2; $y <= date('Y'); $y++): ?>
                        <option value="<?= $y ?>" <?= $tahun == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group">
                <label style="font-size:12px; font-weight:700; color:#64748b; margin-bottom:6px; display:block;">Jabatan</label>
                <select name="jabatan" class="form-select" onchange="document.getElementById('filterForm').submit()">
                    <option value="">-- Semua Jabatan --</option>
                    <option value="karyawan" <?= $jabatan == 'karyawan' ? 'selected' : '' ?>>Karyawan</option>
                    <option value="mandor" <?= $jabatan == 'mandor' ? 'selected' : '' ?>>Mandor</option>
                    <option value="pengawas" <?= $jabatan == 'pengawas' ? 'selected' : '' ?>>Pengawas</option>
                    <option value="kerani" <?= $jabatan == 'kerani' ? 'selected' : '' ?>>Kerani</option>
                </select>
            </div>
            <div class="form-group">
                <label style="font-size:12px; font-weight:700; color:#64748b; margin-bottom:6px; display:block;">Afdeling</label>
                <select name="afdeling" class="form-select" onchange="document.getElementById('filterForm').submit()">
                    <option value="">-- Pilih Afdeling --</option>
                    <?php 
                    $afd_q = mysqli_query($conn, "SELECT nama_afdeling FROM afdelings ORDER BY nama_afdeling ASC");
                    while ($afd = mysqli_fetch_assoc($afd_q)): ?>
                        <option value="<?= $afd['nama_afdeling'] ?>" <?= $afdeling == $afd['nama_afdeling'] ? 'selected' : '' ?>><?= $afd['nama_afdeling'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </form>

        <?php if (empty($afdeling)): ?>
            <div style="text-align:center; padding:40px; color:#94a3b8; background:#f8fafc; border-radius:12px; border:2px dashed #cbd5e1;">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:12px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                <p style="margin:0; font-size:15px; font-weight:600;">Pilih Afdeling untuk mulai mengisi data penggajian.</p>
            </div>
        <?php else: ?>
            <form method="POST" id="gajiForm" onsubmit="unformatInputs()">
                <div class="table-responsive">
                    <table class="table-gaji" id="tabelGaji">
                        <thead>
                            <tr>
                                <th rowspan="2" style="width:30px;">No</th>
                                <th rowspan="2" style="min-width:180px;">Nama / NIK</th>
                                <th colspan="2">Gaji Pokok / HK</th>
                                <th colspan="2">Tambahan</th>
                                <th colspan="2">Potongan</th>
                                <th rowspan="2">Gaji Kotor</th>
                                <th rowspan="2">Gaji Bersih</th>
                                <th rowspan="2">Status</th>
                            </tr>
                            <tr>
                                <th>Jml HK</th>
                                <th>Tarif/HK (Rp)</th>
                                <th>Lembur (Rp)</th>
                                <th>Premi (Rp)</th>
                                <th>BPJS (Rp)</th>
                                <th>Koperasi (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($users) > 0): $no=1; foreach($users as $u): 
                                $status = $u['status'];
                                $badge = '<span class="badge-status status-none">Belum</span>';
                                if ($status == 'draft') $badge = '<span class="badge-status status-draft">Draft</span>';
                                if ($status == 'published') $badge = '<span class="badge-status status-published">Terkirim</span>';
                            ?>
                            <tr class="row-gaji">
                                <td><?= $no++ ?></td>
                                <td class="text-left">
                                    <strong style="color:var(--text-dark); display:block;"><?= htmlspecialchars($u['name']) ?></strong>
                                    <span style="color:#64748b; font-size:11px;"><?= htmlspecialchars($u['nik']) ?> &bull; <?= ucfirst($u['role']) ?></span>
                                </td>
                                <td><input type="number" step="0.5" name="gaji[<?= $u['id'] ?>][hk]" class="input-gaji input-hk val-hk" value="<?= $u['hk_dibayar'] ?? $u['auto_hk'] ?>" oninput="kalkulasiBaris(this)"></td>
                                <td><input type="text" name="gaji[<?= $u['id'] ?>][tarif]" class="input-gaji val-tarif format-rupiah" value="<?= number_format($u['tarif_hk'] ?? 0, 0, ',', '.') ?>" oninput="kalkulasiBaris(this)"></td>
                                <td><input type="text" name="gaji[<?= $u['id'] ?>][lembur]" class="input-gaji val-lembur format-rupiah" value="<?= number_format($u['uang_lembur'] ?? 0, 0, ',', '.') ?>" oninput="kalkulasiBaris(this)"></td>
                                <td><input type="text" name="gaji[<?= $u['id'] ?>][premi]" class="input-gaji val-premi format-rupiah" value="<?= number_format($u['uang_premi'] ?? 0, 0, ',', '.') ?>" oninput="kalkulasiBaris(this)"></td>
                                <td><input type="text" name="gaji[<?= $u['id'] ?>][bpjs]" class="input-gaji val-bpjs format-rupiah" value="<?= number_format($u['potongan_bpjs'] ?? 0, 0, ',', '.') ?>" oninput="kalkulasiBaris(this)"></td>
                                <td><input type="text" name="gaji[<?= $u['id'] ?>][koperasi]" class="input-gaji val-koperasi format-rupiah" value="<?= number_format($u['potongan_koperasi'] ?? 0, 0, ',', '.') ?>" oninput="kalkulasiBaris(this)"></td>
                                <td><input type="text" class="input-gaji readonly val-kotor" value="<?= number_format($u['gaji_kotor'] ?? 0, 0, ',', '.') ?>" readonly tabindex="-1"></td>
                                <td><input type="text" class="input-gaji readonly val-bersih" value="<?= number_format($u['gaji_bersih'] ?? 0, 0, ',', '.') ?>" readonly tabindex="-1" style="color:var(--primary-end);"></td>
                                <td><?= $badge ?></td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr><td colspan="11" style="padding:30px;">Tidak ada karyawan di Afdeling ini.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (count($users) > 0): ?>
                <div class="btn-action-group">
                    <input type="hidden" name="action" id="formAction" value="draft">
                    <button type="submit" class="btn-action btn-secondary" onclick="document.getElementById('formAction').value='draft'">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                        Simpan Draft
                    </button>
                    <button type="submit" class="btn-action btn-primary" onclick="return confirmPublish();">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                        Kirim Slip (Publish)
                    </button>
                </div>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
document.querySelectorAll('.format-rupiah').forEach(input => {
    input.addEventListener('keyup', function(e) {
        let val = this.value.replace(/[^0-9]/g, '');
        if (val) {
            this.value = parseInt(val, 10).toLocaleString('id-ID');
        } else {
            this.value = '0';
        }
    });
    input.addEventListener('focus', function() {
        if (this.value === '0') this.value = '';
    });
    input.addEventListener('blur', function() {
        if (this.value === '') this.value = '0';
    });
});

function getVal(inputElem) {
    let val = inputElem.value.replace(/\./g, '');
    return parseFloat(val) || 0;
}

function kalkulasiBaris(elem) {
    const row = elem.closest('tr');
    
    const hk = parseFloat(row.querySelector('.val-hk').value) || 0;
    const tarif = getVal(row.querySelector('.val-tarif'));
    const lembur = getVal(row.querySelector('.val-lembur'));
    const premi = getVal(row.querySelector('.val-premi'));
    
    const bpjs = getVal(row.querySelector('.val-bpjs'));
    const koperasi = getVal(row.querySelector('.val-koperasi'));
    
    const kotor = (hk * tarif) + lembur + premi;
    const bersih = kotor - (bpjs + koperasi);
    
    row.querySelector('.val-kotor').value = Math.round(kotor).toLocaleString('id-ID');
    row.querySelector('.val-bersih').value = Math.round(bersih).toLocaleString('id-ID');
}

function unformatInputs() {
    document.querySelectorAll('.format-rupiah').forEach(input => {
        let rawVal = input.value.replace(/\./g, '');
        input.value = rawVal;
    });
}

function confirmPublish() {
    document.getElementById('formAction').value = 'publish';
    return confirm("Yakin ingin mengirim semua Slip Gaji di atas ke karyawan? Data akan muncul di aplikasi mereka.");
}
</script>

<?php include 'templates/footer.php'; ?>
