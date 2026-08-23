<?php
require '../config/config.php';

// --- Handler: Kirim Komentar via WA ke Kerani ---
if (isset($_POST['kirim_komentar'])) {
    header('Content-Type: application/json');
    $uid     = $_SESSION['user_id'];
    $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal'] ?? date('Y-m-d', strtotime('+1 day')));
    $komentar = mysqli_real_escape_string($conn, trim($_POST['komentar'] ?? ''));
    $objek    = mysqli_real_escape_string($conn, $_POST['objek'] ?? '');

    if (empty($komentar)) { echo json_encode(['success'=>false,'msg'=>'Komentar tidak boleh kosong.']); exit; }

    // Simpan komentar ke logbook_kinerja (jika kolom komentar sudah ada)
    $cek_col = mysqli_query($conn, "SHOW COLUMNS FROM logbook_kinerja LIKE 'komentar'");
    if (mysqli_num_rows($cek_col) == 0) {
        mysqli_query($conn, "ALTER TABLE logbook_kinerja ADD COLUMN komentar TEXT NULL AFTER status");
    }
    mysqli_query($conn, "UPDATE logbook_kinerja SET komentar='$komentar' WHERE user_id=$uid AND tanggal='$tanggal'");

    // Kirim WA ke Kerani afdeling yang sama
    $afdeling_user = isset($_SESSION['afdeling']) ? mysqli_real_escape_string($conn, $_SESSION['afdeling']) : '';
    $nama_karyawan = htmlspecialchars($_SESSION['nama'] ?? 'Karyawan');
    $q_kerani = mysqli_query($conn, "SELECT no_hp FROM users WHERE role='kerani'" . (!empty($afdeling_user) ? " AND afdeling='$afdeling_user'" : "") . " LIMIT 1");
    
    $sent = 0;
    if ($q_kerani && mysqli_num_rows($q_kerani) > 0) {
        $kerani = mysqli_fetch_assoc($q_kerani);
        if (!empty($kerani['no_hp'])) {
            $tgl_fmt = date('d/m/Y', strtotime($tanggal));
            $pesan = "💬 *Komentar dari Karyawan*\n\n";
            $pesan .= "Halo, ada pesan dari *{$nama_karyawan}* untuk tanggal *{$tgl_fmt}*:\n\n";
            $pesan .= "📋 *Objek Kerja:* {$objek}\n";
            $pesan .= "💬 *Pesan:* {$komentar}\n\n";
            $pesan .= "_Mohon ditindaklanjuti jika diperlukan._\n_Sistem PT DJL_";
            sendWA($kerani['no_hp'], $pesan);
            $sent = 1;
        }
    }
    echo json_encode(['success'=>true, 'sent'=>$sent]);
    exit;
}

include 'templates/header.php';

// Fungsi tipe tabel
function getTableType($objek) {
    if ($objek === 'Langsir manual') return 'T1';
    if (in_array($objek, ['Membabat gawangan','Semprot pingan','Rawat jalan','Kotrek anyangan'])) return 'T2';
    if (in_array($objek, ['Panen','Potong buah'])) return 'T3';
    if ($objek === 'Kutip brondolan') return 'T4';
    if (in_array($objek, ['Muat TBS ke truk','Muat TBS ke jondol'])) return 'T5';
    return 'T2';
}

$uid     = $_SESSION['user_id'];
$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d', strtotime('+1 day'));
$tgl_safe = mysqli_real_escape_string($conn, $tanggal);

// Ambil semua penugasan untuk karyawan ini pada tanggal tersebut
$q_tugas = mysqli_query($conn, "
    SELECT lk.*, m.name AS nama_mandor, m.no_hp AS hp_mandor
    FROM logbook_kinerja lk
    LEFT JOIN users m ON lk.mandor_id = m.id
    WHERE lk.user_id = $uid AND lk.tanggal = '$tgl_safe'
    ORDER BY lk.id ASC
");
$list_tugas = [];
while ($t = mysqli_fetch_assoc($q_tugas)) $list_tugas[] = $t;

$nama_bulan_id = ['01'=>'Jan','02'=>'Feb','03'=>'Mar','04'=>'Apr','05'=>'Mei','06'=>'Jun','07'=>'Jul','08'=>'Agu','09'=>'Sep','10'=>'Okt','11'=>'Nov','12'=>'Des'];
$tgl_display = date('d', strtotime($tanggal)) . ' ' . $nama_bulan_id[date('m', strtotime($tanggal))] . ' ' . date('Y', strtotime($tanggal));
$is_tomorrow = $tanggal === date('Y-m-d', strtotime('+1 day'));
?>

<style>
    .ok-date-bar {
        display: flex; gap: 8px; align-items: center;
        margin-bottom: 20px; overflow-x: auto; padding-bottom: 4px;
    }
    .ok-date-chip {
        flex-shrink: 0;
        padding: 7px 14px; border-radius: 20px; font-size: 13px; font-weight: 700;
        background: white; border: 1.5px solid #e2e8f0; color: var(--text-muted);
        cursor: pointer; transition: all .2s; text-decoration: none; display:inline-block;
    }
    .ok-date-chip.active, .ok-date-chip:active {
        background: var(--primary-start); color: white; border-color: var(--primary-start);
    }
    .ok-date-input-wrap {
        flex-shrink: 0;
        display: flex; align-items: center; gap: 6px;
        padding: 7px 12px; border-radius: 20px; font-size: 13px; font-weight: 700;
        background: white; border: 1.5px solid #e2e8f0;
    }
    .ok-date-input-wrap input[type="date"] {
        border: none; outline: none; font-size: 12px; font-weight: 700;
        color: var(--text-muted); font-family: inherit; background: transparent; cursor: pointer;
    }

    /* Kartu tugas */
    .tugas-card {
        background: white; border-radius: 16px; border: 1px solid #e2e8f0;
        box-shadow: 0 4px 12px rgba(0,0,0,.04); margin-bottom: 16px; overflow: hidden;
        animation: slideUp 0.35s cubic-bezier(0.16,1,0.3,1) forwards;
    }
    .tugas-header {
        padding: 14px 16px; background: linear-gradient(135deg, var(--primary-start) 0%, var(--primary-end) 100%);
        display: flex; align-items: center; justify-content: space-between; gap: 8px;
    }
    .tugas-objek-name {
        font-size: 15px; font-weight: 800; color: white; flex: 1;
    }
    .tipe-chip {
        padding: 3px 10px; border-radius: 20px; font-size: 10px; font-weight: 800;
        background: rgba(255,255,255,.2); color: white; letter-spacing: .5px;
    }

    /* Tabel data kerja */
    .tugas-table-wrap { overflow-x: auto; }
    .tugas-table {
        width: 100%; border-collapse: collapse; font-size: 12px; white-space: nowrap;
    }
    .tugas-table th {
        padding: 9px 10px; background: #f8fafc; color: #64748b;
        font-weight: 700; text-transform: uppercase; font-size: 10px; letter-spacing: .5px;
        border-bottom: 1px solid #e2e8f0; text-align: center;
    }
    .tugas-table td {
        padding: 10px 10px; border-bottom: 1px solid #f1f5f9;
        text-align: center; color: var(--text-dark); font-weight: 600;
    }
    .tugas-table td:first-child { text-align: left; font-weight: 700; color: #475569; }
    .val-num { font-weight: 800 !important; color: var(--primary-start) !important; }
    .val-empty { color: #cbd5e1 !important; font-style: italic; font-weight: 400 !important; }

    /* Komentar */
    .komentar-section {
        padding: 14px 16px; border-top: 1px solid #f1f5f9;
    }
    .komentar-label {
        font-size: 12px; font-weight: 800; color: #475569; margin-bottom: 8px;
        text-transform: uppercase; letter-spacing: .5px; display: flex; align-items: center; gap: 6px;
    }
    .komentar-existing {
        background: #f0fdf4; border: 1px solid #86efac; border-radius: 10px;
        padding: 10px 12px; font-size: 13px; color: #16a34a; font-weight: 600;
        margin-bottom: 8px; display: flex; gap: 8px; align-items: flex-start;
    }
    .komentar-textarea {
        width: 100%; box-sizing: border-box;
        padding: 10px 12px; border: 1.5px solid #e2e8f0; border-radius: 10px;
        font-size: 13px; font-family: inherit; font-weight: 500; color: var(--text-dark);
        resize: none; outline: none; transition: border-color .2s; min-height: 80px;
        background: #f8fafc;
    }
    .komentar-textarea:focus { border-color: var(--primary-start); background: white; }
    .btn-komentar {
        margin-top: 8px; width: 100%;
        padding: 12px; border-radius: 10px; font-size: 14px; font-weight: 800;
        border: none; cursor: pointer; display: flex; align-items: center;
        justify-content: center; gap: 8px; transition: all .2s;
        background: linear-gradient(135deg, var(--primary-start), var(--primary-end));
        color: white; box-shadow: 0 4px 12px rgba(66,88,255,.3);
    }
    .btn-komentar:active { transform: scale(0.98); box-shadow: none; }

    /* Empty state */
    .ok-empty {
        text-align: center; padding: 50px 20px;
        background: white; border-radius: 20px; border: 1px solid #e2e8f0;
    }
    .ok-empty-icon { font-size: 48px; margin-bottom: 16px; opacity: .5; }
    .ok-empty-title { font-size: 17px; font-weight: 800; color: var(--text-dark); margin-bottom: 8px; }
    .ok-empty-sub { font-size: 13px; color: var(--text-muted); }

    /* Mandor info */
    .mandor-info {
        display: flex; align-items: center; gap: 8px;
        padding: 10px 16px; background: #fef9c3; border-bottom: 1px solid #fde68a;
        font-size: 12px; font-weight: 700; color: #92400e;
    }
    .mandor-info svg { flex-shrink: 0; }

    /* Section title */
    .ok-section-title {
        font-size: 16px; font-weight: 800; color: var(--text-dark);
        margin: 0 0 14px 0; display: flex; align-items: center; gap: 8px;
    }

    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #64748b;
        text-decoration: none;
        font-weight: 700;
        font-size: 14px;
        margin-bottom: 16px;
        background: white;
        padding: 8px 16px;
        border-radius: 20px;
        border: 1px solid #e2e8f0;
    }
</style>

<a href="index.php" class="btn-back">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="19" y1="12" x2="5" y2="12"></line>
        <polyline points="12 19 5 12 12 5"></polyline>
    </svg>
    Kembali
</a>

<!-- Date picker bar -->
<div class="ok-date-bar">
    <a href="?tanggal=<?= date('Y-m-d') ?>" class="ok-date-chip <?= $tanggal == date('Y-m-d') ? 'active' : '' ?>">Hari Ini</a>
    <a href="?tanggal=<?= date('Y-m-d', strtotime('+1 day')) ?>" class="ok-date-chip <?= $tanggal == date('Y-m-d', strtotime('+1 day')) ? 'active' : '' ?>">Besok</a>
    <div class="ok-date-input-wrap">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        <input type="date" value="<?= $tanggal ?>" onchange="window.location='?tanggal='+this.value">
    </div>
</div>

<!-- Section title -->
<div class="ok-section-title">
    📋 Penugasan Kerja
    <span style="font-size:12px;font-weight:600;color:var(--text-muted);"><?= $tgl_display ?></span>
</div>

<?php if (empty($list_tugas)): ?>
<!-- Empty state -->
<div class="ok-empty animate-up">
    <div class="ok-empty-icon">🌿</div>
    <div class="ok-empty-title">Belum Ada Penugasan</div>
    <div class="ok-empty-sub">Belum ada objek kerja yang ditetapkan oleh kerani untuk tanggal ini. Coba cek kembali nanti.</div>
</div>

<?php else: ?>

<?php $nomor = 1; foreach ($list_tugas as $tugas): 
    $tipe = getTableType($tugas['objek_kerja']);
    $label_tipe = ['T1'=>'Langsir','T2'=>'Perawatan','T3'=>'Panen','T4'=>'Brondolan','T5'=>'Muat TBS'];

    // Cek apakah kolom komentar sudah ada
    $has_komentar_col = mysqli_num_rows(mysqli_query($conn, "SHOW COLUMNS FROM logbook_kinerja LIKE 'komentar'")) > 0;
    $komentar_lama = ($has_komentar_col && !empty($tugas['komentar'])) ? $tugas['komentar'] : '';
?>
<div class="tugas-card animate-up" style="animation-delay: <?= ($nomor-1)*0.08 ?>s;">

    <!-- Header kartu -->
    <div class="tugas-header">
        <div class="tugas-objek-name">
            <?= htmlspecialchars($tugas['objek_kerja']) ?>
        </div>
        <span class="tipe-chip"><?= $label_tipe[$tipe] ?></span>
    </div>

    <!-- Info mandor -->
    <div class="mandor-info">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Mandor: <?= htmlspecialchars($tugas['nama_mandor'] ?? '—') ?>
    </div>

    <!-- Tabel berdasarkan tipe -->
    <div class="tugas-table-wrap">
        <table class="tugas-table">

            <?php if ($tipe === 'T1'): // Langsir manual ?>
            <thead>
                <tr>
                    <th>Nama Mandor</th>
                    <th>Hasil Langsir (kg)</th>
                    <th>Prestasi (kg)</th>
                    <th>Blok</th>
                    <th>Luas Ha</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= htmlspecialchars($tugas['nama_mandor'] ?? '—') ?></td>
                    <td><?= !empty($tugas['hasil_langsir_kg']) ? '<span class="val-num">'.number_format($tugas['hasil_langsir_kg'],2).'</span>' : '<span class="val-empty">Belum diisi</span>' ?></td>
                    <td><?= !empty($tugas['prestasi_kg']) ? '<span class="val-num">'.number_format($tugas['prestasi_kg'],2).'</span>' : '<span class="val-empty">Belum diisi</span>' ?></td>
                    <td><?= htmlspecialchars($tugas['blok'] ?? '—') ?></td>
                    <td><?= !empty($tugas['luas_ha']) ? $tugas['luas_ha'] : '—' ?></td>
                </tr>
            </tbody>

            <?php elseif ($tipe === 'T2'): // Membabat, Semprot, Rawat, Kotrek ?>
            <thead>
                <tr>
                    <th>Nama Mandor</th>
                    <th>Blok</th>
                    <th>Luas Ha</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= htmlspecialchars($tugas['nama_mandor'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($tugas['blok'] ?? '—') ?></td>
                    <td><?= !empty($tugas['luas_ha']) ? $tugas['luas_ha'] : '—' ?></td>
                </tr>
            </tbody>

            <?php elseif ($tipe === 'T3'): // Panen, Potong buah ?>
            <thead>
                <tr>
                    <th>Nama Mandor</th>
                    <th>TBS (kg)</th>
                    <th>TS</th>
                    <th>TBS</th>
                    <th>Total Tandan</th>
                    <th>Blok</th>
                    <th>Luas Ha</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= htmlspecialchars($tugas['nama_mandor'] ?? '—') ?></td>
                    <td><?= !empty($tugas['hasil_ton']) ? '<span class="val-num">'.number_format($tugas['hasil_ton'],0).'</span>' : '<span class="val-empty">—</span>' ?></td>
                    <td><?= !empty($tugas['tandan_kosong']) ? '<span class="val-num">'.$tugas['tandan_kosong'].'</span>' : '<span class="val-empty">—</span>' ?></td>
                    <td><?= !empty($tugas['tbs']) ? '<span class="val-num">'.$tugas['tbs'].'</span>' : '<span class="val-empty">—</span>' ?></td>
                    <td><?= !empty($tugas['total_tandan']) ? '<span class="val-num">'.$tugas['total_tandan'].'</span>' : '<span class="val-empty">—</span>' ?></td>
                    <td><?= htmlspecialchars($tugas['blok'] ?? '—') ?></td>
                    <td><?= !empty($tugas['luas_ha']) ? $tugas['luas_ha'] : '—' ?></td>
                </tr>
            </tbody>

            <?php elseif ($tipe === 'T4'): // Kutip brondolan ?>
            <thead>
                <tr>
                    <th>Nama Mandor</th>
                    <th>Hasil (kg)</th>
                    <th>Prestasi (kg)</th>
                    <th>Blok</th>
                    <th>Luas Ha</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= htmlspecialchars($tugas['nama_mandor'] ?? '—') ?></td>
                    <td><?= !empty($tugas['hasil_kg']) ? '<span class="val-num">'.number_format($tugas['hasil_kg'],2).'</span>' : '<span class="val-empty">Belum diisi</span>' ?></td>
                    <td><?= !empty($tugas['prestasi_kg']) ? '<span class="val-num">'.number_format($tugas['prestasi_kg'],2).'</span>' : '<span class="val-empty">Belum diisi</span>' ?></td>
                    <td><?= htmlspecialchars($tugas['blok'] ?? '—') ?></td>
                    <td><?= !empty($tugas['luas_ha']) ? $tugas['luas_ha'] : '—' ?></td>
                </tr>
            </tbody>

            <?php elseif ($tipe === 'T5'): // Muat TBS ?>
            <thead>
                <tr>
                    <th>Nama Mandor</th>
                    <th>Hasil (kg)</th>
                    <th>Blok</th>
                    <th>Luas Ha</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= htmlspecialchars($tugas['nama_mandor'] ?? '—') ?></td>
                    <td><?= !empty($tugas['hasil_kg']) ? '<span class="val-num">'.number_format($tugas['hasil_kg'],0).'</span>' : '<span class="val-empty">Belum diisi</span>' ?></td>
                    <td><?= htmlspecialchars($tugas['blok'] ?? '—') ?></td>
                    <td><?= !empty($tugas['luas_ha']) ? $tugas['luas_ha'] : '—' ?></td>
                </tr>
            </tbody>
            <?php endif; ?>

        </table>
    </div>

    <!-- Komentar Section -->
    <div class="komentar-section">
        <div class="komentar-label">
            💬 Komentar ke Kerani
        </div>

        <?php if (!empty($komentar_lama)): ?>
        <div class="komentar-existing">
            <span>✅</span>
            <span>Komentar terkirim: "<?= htmlspecialchars($komentar_lama) ?>"</span>
        </div>
        <?php endif; ?>

        <textarea class="komentar-textarea" id="komentar_<?= $tugas['id'] ?>"
            placeholder="Tulis komentar jika ada masalah atau ingin tukar objek kerja dengan karyawan lain..."><?= htmlspecialchars($komentar_lama) ?></textarea>

        <button class="btn-komentar" onclick="kirimKomentar(<?= $tugas['id'] ?>, '<?= addslashes($tugas['objek_kerja']) ?>')">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            Kirim Komentar ke Kerani
        </button>
    </div>

</div>
<?php $nomor++; endforeach; ?>

<?php endif; ?>

<!-- Info catatan -->
<div style="margin-top:8px;padding:14px;background:#eff6ff;border-radius:12px;border:1px solid #bfdbfe;font-size:12px;color:#1e40af;font-weight:600;line-height:1.6;">
    ℹ️ <strong>Catatan:</strong> Data hasil kerja (kg, prestasi, dll.) diisi oleh Mandor setelah kamu pulang. Komentar akan dikirim langsung ke WhatsApp Kerani.
</div>

<script>
const tanggal = '<?= $tanggal ?>';

function kirimKomentar(logbookId, objek) {
    const textarea = document.getElementById('komentar_' + logbookId);
    const komentar = textarea.value.trim();
    if (!komentar) {
        Swal.fire({ icon:'warning', title:'Komentar Kosong', text:'Tulis komentar terlebih dahulu.', confirmButtonColor:'#4258ff' });
        return;
    }
    const fd = new FormData();
    fd.append('kirim_komentar', 1);
    fd.append('tanggal', tanggal);
    fd.append('komentar', komentar);
    fd.append('objek', objek);
    fetch('objek_kerja.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Komentar Terkirim!',
                    text: data.sent ? 'Komentar berhasil dikirim ke WhatsApp Kerani.' : 'Komentar tersimpan (kerani tidak memiliki nomor WA).',
                    confirmButtonColor: '#4258ff',
                    timer: 2500, timerProgressBar: true
                });
            } else {
                Swal.fire({ icon:'error', title:'Gagal', text: data.msg || 'Terjadi kesalahan.', confirmButtonColor:'#4258ff' });
            }
        }).catch(() => Swal.fire({ icon:'error', title:'Error', text:'Gagal terhubung ke server.', confirmButtonColor:'#4258ff' }));
}
</script>

<?php include 'templates/footer.php'; ?>
