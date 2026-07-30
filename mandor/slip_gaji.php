<?php
require '../config/config.php';
include 'templates/header.php';

$user_id = $_SESSION['user_id'];

// Ambil info user
$user_q = mysqli_query($conn, "SELECT name, nik, role, afdeling FROM users WHERE id=$user_id");
$user = mysqli_fetch_assoc($user_q);

// Ambil riwayat slip gaji yang sudah di-publish
$query_slip = mysqli_query($conn, "
    SELECT * FROM penggajian 
    WHERE user_id = $user_id AND status = 'published' 
    ORDER BY periode_tahun DESC, periode_bulan DESC
");

$nama_bulan = [
    '01'=>'Januari', '02'=>'Februari', '03'=>'Maret', '04'=>'April', 
    '05'=>'Mei', '06'=>'Juni', '07'=>'Juli', '08'=>'Agustus', 
    '09'=>'September', '10'=>'Oktober', '11'=>'November', '12'=>'Desember'
];
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
    
    .btn-back {
        display: inline-flex; align-items: center; gap: 6px;
        color: var(--text-muted); text-decoration: none; font-weight: 700;
        font-size: 13px; margin-bottom: 16px; background: white;
        padding: 8px 14px; border-radius: 20px; border: 1.5px solid #e2e8f0;
    }

    .slip-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }
    
    .slip-card {
        background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;
        padding: 16px; transition: all 0.2s; position: relative; overflow: hidden;
    }
    .slip-card:hover { border-color: var(--primary-start); box-shadow: 0 10px 25px rgba(66,88,255,0.1); transform: translateY(-3px); }
    
    .slip-card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
    .slip-title { font-weight: 800; color: var(--text-dark); font-size: 16px; }
    .slip-subtitle { font-size: 12px; color: #64748b; margin-top: 4px; }
    .slip-icon { color: var(--primary-start); opacity: 0.8; }
    
    .slip-amount { font-size: 20px; font-weight: 800; color: #166534; margin-bottom: 16px; display: block; }
    
    .btn-lihat {
        width: 100%; background: linear-gradient(135deg, var(--primary-start) 0%, var(--primary-end) 100%);
        color: white; border: none; padding: 10px; border-radius: 8px; font-size: 13px; font-weight: 700;
        cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-lihat:active { transform: scale(0.97); }

    .empty-state { text-align: center; padding: 40px 20px; color: #94a3b8; }
    .empty-state svg { width: 64px; height: 64px; margin-bottom: 16px; opacity: 0.5; }
    .empty-state p { font-weight: 600; font-size: 15px; margin: 0; }
</style>

<div class="animate-up">
    <a href="index.php" class="btn-back">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        Kembali
    </a>

    <h2 class="page-title" style="text-align: left; margin-bottom: 20px; font-size: 24px;">Slip Gaji Anda</h2>

    <div class="card-container">
        
        <?php if (mysqli_num_rows($query_slip) > 0): ?>
            <div class="slip-list">
                <?php while ($slip = mysqli_fetch_assoc($query_slip)): 
                    $bulan_nama = $nama_bulan[$slip['periode_bulan']] ?? $slip['periode_bulan'];
                    $periode_str = $bulan_nama . ' ' . $slip['periode_tahun'];
                    
                    // Siapkan JSON data untuk dikirim ke JS agar bisa dicetak
                    $dataJSON = htmlspecialchars(json_encode([
                        'nama' => $user['name'],
                        'nik' => $user['nik'],
                        'jabatan' => ucfirst($user['role']),
                        'afdeling' => $user['afdeling'],
                        'periode' => strtoupper($periode_str),
                        'hk' => $slip['hk_dibayar'],
                        'tarif' => number_format($slip['tarif_hk'], 0, ',', '.'),
                        'lembur' => number_format($slip['uang_lembur'], 0, ',', '.'),
                        'premi' => number_format($slip['uang_premi'], 0, ',', '.'),
                        'bpjs' => number_format($slip['potongan_bpjs'], 0, ',', '.'),
                        'koperasi' => number_format($slip['potongan_koperasi'], 0, ',', '.'),
                        'lainnya' => number_format($slip['potongan_lainnya'], 0, ',', '.'),
                        'kotor' => number_format($slip['gaji_kotor'], 0, ',', '.'),
                        'bersih' => number_format($slip['gaji_bersih'], 0, ',', '.')
                    ]), ENT_QUOTES, 'UTF-8');
                ?>
                    <div class="slip-card">
                        <div class="slip-card-header">
                            <div>
                                <div class="slip-title"><?= $periode_str ?></div>
                                <div class="slip-subtitle">Periode Penggajian</div>
                            </div>
                            <svg class="slip-icon" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                        </div>
                        
                        <span class="slip-amount">Rp <?= number_format($slip['gaji_bersih'], 0, ',', '.') ?></span>
                        
                        <button class="btn-lihat" onclick="cetakSlipGaji(<?= $dataJSON ?>)">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                <rect x="6" y="14" width="12" height="8"></rect>
                            </svg>
                            Lihat & Cetak Slip
                        </button>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                <p>Belum ada slip gaji yang diterbitkan untuk Anda.</p>
            </div>
        <?php endif; ?>

    </div>
</div>

<script>
function cetakSlipGaji(data) {
    const win = window.open('', '_blank', 'width=700,height=800');
    
    // Hitung total potongan di JS
    let tot_potong = parseInt(data.bpjs.replace(/\./g, '')) + parseInt(data.koperasi.replace(/\./g, '')) + parseInt(data.lainnya.replace(/\./g, ''));
    tot_potong = tot_potong.toLocaleString('id-ID');
    
    win.document.write(`<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Slip Gaji - ${data.nama} - ${data.periode}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Times New Roman', Times, serif; font-size: 11pt; color: #000; padding: 20px; }
    .slip-container { width: 100%; max-width: 800px; margin: 0 auto; border: 1px solid #000; padding: 20px; }
    
    .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 15px; margin-bottom: 20px; position: relative; }
    .header img { position: absolute; left: 10px; top: 0; width: 60px; }
    .header h1 { font-size: 16pt; text-transform: uppercase; margin-bottom: 4px; }
    .header p { font-size: 10pt; margin: 2px 0; }
    
    .title { text-align: center; font-size: 14pt; font-weight: bold; text-decoration: underline; margin-bottom: 5px; }
    .subtitle { text-align: center; font-size: 11pt; font-weight: bold; margin-bottom: 20px; }
    
    .info-table { width: 100%; margin-bottom: 20px; }
    .info-table td { padding: 4px 0; vertical-align: top; }
    .info-table td:nth-child(1) { width: 100px; }
    .info-table td:nth-child(2) { width: 15px; }
    
    .details-container { display: flex; justify-content: space-between; gap: 20px; margin-bottom: 20px; }
    .details-box { flex: 1; border: 1px solid #000; padding: 15px; }
    .details-box h3 { font-size: 11pt; font-weight: bold; text-align: center; border-bottom: 1px dashed #000; padding-bottom: 5px; margin-bottom: 10px; }
    
    .item-row { display: flex; justify-content: space-between; margin-bottom: 8px; }
    .item-row .val { font-weight: bold; }
    
    .summary-box { border: 1px solid #000; padding: 15px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; background-color: #f8f8f8; }
    .summary-box h2 { font-size: 14pt; }
    .summary-box .net-salary { font-size: 16pt; font-weight: bold; }
    
    .ttd-section { display: flex; justify-content: space-between; text-align: center; margin-top: 40px; }
    .ttd-box { flex: 1; }
    .ttd-space { height: 80px; }
    .ttd-name { font-weight: bold; text-decoration: underline; }
    
    @media print {
        body { padding: 0; }
        .slip-container { border: none; padding: 0; }
    }
</style>
</head>
<body>
    <div class="slip-container">
        <div class="header">
            <img src="../assets/img/logo.png" alt="Logo PT DJL" onerror="this.style.display='none'">
            <h1>PT DAMAI JAYA LESTARI</h1>
            <p>KEBUN KOLAKA TANI - KEC. POLINGGONA</p>
            <p>Jl. Perkebunan No. 1, Email: admin@djl.co.id</p>
        </div>
        
        <div class="title">SLIP GAJI KARYAWAN</div>
        <div class="subtitle">PERIODE: ${data.periode}</div>
        
        <table class="info-table">
            <tr>
                <td><strong>NIK</strong></td><td>:</td><td>${data.nik}</td>
                <td><strong>Afdeling</strong></td><td>:</td><td>${data.afdeling || '-'}</td>
            </tr>
            <tr>
                <td><strong>Nama</strong></td><td>:</td><td>${data.nama}</td>
                <td><strong>Jabatan</strong></td><td>:</td><td>${data.jabatan}</td>
            </tr>
        </table>
        
        <div class="details-container">
            <!-- PENDAPATAN -->
            <div class="details-box">
                <h3>PENERIMAAN</h3>
                <div class="item-row">
                    <span>Gaji Pokok / HK (${data.hk} HK)</span>
                    <span class="val">Rp ${data.hk_total_rp}</span>
                </div>
                <div class="item-row">
                    <span>Uang Lembur</span>
                    <span class="val">Rp ${data.lembur}</span>
                </div>
                <div class="item-row">
                    <span>Premi / Bonus</span>
                    <span class="val">Rp ${data.premi}</span>
                </div>
                <hr style="border:0; border-bottom:1px solid #000; margin:10px 0;">
                <div class="item-row" style="font-weight:bold;">
                    <span>TOTAL PENERIMAAN (A)</span>
                    <span>Rp ${data.kotor}</span>
                </div>
            </div>
            
            <!-- POTONGAN -->
            <div class="details-box">
                <h3>POTONGAN</h3>
                <div class="item-row">
                    <span>Askes / BPJS</span>
                    <span class="val">Rp ${data.bpjs}</span>
                </div>
                <div class="item-row">
                    <span>Koperasi / Gudang</span>
                    <span class="val">Rp ${data.koperasi}</span>
                </div>
                <hr style="border:0; border-bottom:1px solid #000; margin:10px 0; margin-top:33px;">
                <div class="item-row" style="font-weight:bold;">
                    <span>TOTAL POTONGAN (B)</span>
                    <span>Rp ${tot_potong}</span>
                </div>
            </div>
        </div>
        
        <!-- GAJI BERSIH -->
        <div class="summary-box">
            <h2>PENERIMAAN BERSIH (A - B)</h2>
            <div class="net-salary">Rp ${data.bersih}</div>
        </div>
        
        <div class="ttd-section">
            <div class="ttd-box">
                <p>Penerima,</p>
                <div class="ttd-space"></div>
                <p class="ttd-name">${data.nama}</p>
                <p>Karyawan</p>
            </div>
            <div class="ttd-box">
                <p>Mengetahui,</p>
                <div class="ttd-space"></div>
                <p class="ttd-name">Ka. Personalia / KTU</p>
                <p>PT Damai Jaya Lestari</p>
            </div>
        </div>
    </div>
</body>
</html>`);

    // Kalkulasi tarif*hk di js (karena ngga disimpen di json)
    let trf = parseInt(data.tarif.replace(/\./g, ''));
    let hk  = parseFloat(data.hk);
    let hk_tot = Math.round(trf * hk).toLocaleString('id-ID');
    
    // Inject hk_total_rp dynamically
    win.document.body.innerHTML = win.document.body.innerHTML.replace('Rp undefined', 'Rp ' + hk_tot);

    win.document.close();
    win.focus();
    setTimeout(() => { win.print(); }, 400);
}
</script>

<?php include 'templates/footer.php'; ?>
