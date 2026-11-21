<?php
require '../config/config.php';
include 'templates/header.php';

$afdeling = isset($_SESSION['afdeling']) ? trim($_SESSION['afdeling']) : '';
$afdeling_safe = mysqli_real_escape_string($conn, $afdeling);
$kerani_id = (int)$_SESSION['user_id'];
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');

$nama_bulan = [
    '01' => 'Januari',   '02' => 'Februari', '03' => 'Maret',
    '04' => 'April',     '05' => 'Mei',      '06' => 'Juni',
    '07' => 'Juli',      '08' => 'Agustus',  '09' => 'September',
    '10' => 'Oktober',   '11' => 'November', '12' => 'Desember'
];

// --- HANDLER POST: KIRIM LAPORAN KE KEUANGAN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'kirim_laporan') {
        $bln = str_pad((int)$_POST['bulan'], 2, '0', STR_PAD_LEFT);
        $thn = (int)$_POST['tahun'];
        $jns = trim($_POST['jenis_laporan']);
        $catatan = trim($_POST['catatan_kerani'] ?? '');
        $now = date('Y-m-d H:i:s');

        if (empty($afdeling)) {
            swalAlert('Gagal: Akun Kerani Anda tidak terhubung ke afdeling manapun.', 'error');
        } elseif (!in_array($jns, ['absensi', 'keseluruhan'])) {
            swalAlert('Gagal: Jenis laporan tidak valid.', 'error');
        } else {
            $catatan_safe = mysqli_real_escape_string($conn, $catatan);
            $jns_safe = mysqli_real_escape_string($conn, $jns);

            $sql = "INSERT INTO laporan_pengajuan 
                    (afdeling, bulan, tahun, jenis_laporan, status, kerani_id, tanggal_kirim, catatan_kerani)
                    VALUES 
                    ('$afdeling_safe', '$bln', $thn, '$jns_safe', 'menunggu_validasi', $kerani_id, '$now', '$catatan_safe')
                    ON DUPLICATE KEY UPDATE 
                    status = 'menunggu_validasi',
                    kerani_id = $kerani_id,
                    tanggal_kirim = '$now',
                    catatan_kerani = '$catatan_safe',
                    keuangan_id = NULL,
                    tanggal_validasi = NULL";

            if (mysqli_query($conn, $sql)) {
                // Notifikasi WA ke Keuangan
                $label_jns = ($jns === 'absensi') ? 'Absensi' : 'Keseluruhan Objek Kerja';
                $q_keu = mysqli_query($conn, "SELECT no_hp, name FROM users WHERE role='keuangan' AND no_hp IS NOT NULL AND no_hp != ''");
                while ($k_row = mysqli_fetch_assoc($q_keu)) {
                    $nama_k = $_SESSION['nama'] ?? 'Kerani';
                    $pesan_wa = "🔔 *LAPORAN MASUK DARI KERANI* 🔔\n\n";
                    $pesan_wa .= "Halo Bagian Keuangan,\n";
                    $pesan_wa .= "Laporan {$label_jns} untuk *{$afdeling}* periode *" . ($nama_bulan[$bln] ?? $bln) . " {$thn}* telah selesai di-crosscheck dan dikirim oleh *{$nama_k}*.\n";
                    if (!empty($catatan)) {
                        $pesan_wa .= "💬 Catatan: _{$catatan}_\n";
                    }
                    $pesan_wa .= "\nSilakan login ke portal AMANDA untuk memvalidasi laporan:\n";
                    $pesan_wa .= BASE_URL . "keuangan/validasi_laporan.php\n\n";
                    $pesan_wa .= "_Sistem Informasi Karyawan PT DJL_";
                    sendWA($k_row['no_hp'], $pesan_wa);
                }

                swalRedirect("Laporan $label_jns periode " . $nama_bulan[$bln] . " $thn berhasil dikirim ke Bagian Keuangan!", "kirim_laporan.php?tahun=$thn", 'success', 'Terkirim ke Keuangan!');
            } else {
                swalAlert('Gagal mengirim laporan: ' . mysqli_error($conn), 'error');
            }
        }
    } elseif ($action === 'tarik_laporan') {
        $id_pengajuan = (int)$_POST['id_pengajuan'];
        $del = mysqli_query($conn, "DELETE FROM laporan_pengajuan WHERE id=$id_pengajuan AND kerani_id=$kerani_id AND status='menunggu_validasi'");
        if ($del && mysqli_affected_rows($conn) > 0) {
            swalRedirect("Pengajuan laporan berhasil ditarik kembali untuk koreksi lebih lanjut.", "kirim_laporan.php?tahun=$tahun", 'success', 'Laporan Ditarik');
        } else {
            swalAlert("Laporan tidak dapat ditarik kembali (mungkin sudah divalidasi oleh Keuangan).", "error");
        }
    }
}

// Ambil semua pengajuan laporan afdeling ini di tahun terpilih
$data_pengajuan = [];
if (!empty($afdeling)) {
    $q_list = mysqli_query($conn, "
        SELECT lp.*, uk.name as nama_kerani, ug.name as nama_keuangan 
        FROM laporan_pengajuan lp
        LEFT JOIN users uk ON lp.kerani_id = uk.id
        LEFT JOIN users ug ON lp.keuangan_id = ug.id
        WHERE lp.afdeling = '$afdeling_safe' AND lp.tahun = $tahun
    ");
    while ($row = mysqli_fetch_assoc($q_list)) {
        $data_pengajuan[$row['bulan']][$row['jenis_laporan']] = $row;
    }
}
?>

<style>
    .kl-banner {
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        border-radius: 16px;
        padding: 28px 32px;
        color: white;
        margin-bottom: 24px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
    }

    .kl-banner h2 { font-size: 22px; font-weight: 800; margin-bottom: 6px; }
    .kl-banner p  { font-size: 13px; color: #cbd5e1; max-width: 650px; line-height: 1.5; }

    .kl-filter-box {
        background: white;
        padding: 16px 20px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
    }

    .kl-select {
        padding: 8px 14px;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        font-weight: 600;
        font-size: 14px;
        color: #334155;
        outline: none;
    }

    .card-table {
        background: white;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        overflow: hidden;
    }

    .table-kl {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }

    .table-kl th {
        background: #f8fafc;
        padding: 14px 18px;
        font-weight: 700;
        color: #475569;
        text-align: left;
        border-bottom: 1.5px solid #e2e8f0;
    }

    .table-kl td {
        padding: 16px 18px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .table-kl tr:hover { background: #f8fafc; }

    /* Badge Status */
    .badge-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .badge-draft     { background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; }
    .badge-pending   { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
    .badge-approved  { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .badge-rejected  { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

    /* Button Actions */
    .btn-action-kl {
        padding: 7px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: none;
        text-decoration: none;
        transition: all 0.15s;
    }

    .btn-check   { background: #e0f2fe; color: #0284c7; }
    .btn-check:hover { background: #bae6fd; }

    .btn-send    { background: #3b82f6; color: white; }
    .btn-send:hover { background: #2563eb; }

    .btn-resend  { background: #ef4444; color: white; }
    .btn-resend:hover { background: #dc2626; }

    .btn-cancel  { background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; }
    .btn-cancel:hover { background: #e2e8f0; color: #334155; }

    /* Modal Form */
    .modal-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(4px);
        display: none; align-items: center; justify-content: center;
        z-index: 999;
    }
    .modal-overlay.active { display: flex; }
    .modal-box {
        background: white; border-radius: 16px; width: 90%; max-width: 520px;
        padding: 28px; box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        animation: popIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @keyframes popIn {
        from { transform: scale(0.95); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
</style>

<div class="kl-banner">
    <div>
        <h2>Kirim Laporan ke Bagian Keuangan</h2>
        <p>
            Pastikan seluruh data kehadiran dan hasil kinerja karyawan di Afdeling <strong><?= htmlspecialchars($afdeling ?: 'Anda') ?></strong> sudah di-crosscheck dan dikoreksi dengan teliti sebelum dikirimkan ke Bagian Keuangan untuk proses verifikasi.
        </p>
    </div>
    <div style="background: rgba(255,255,255,0.1); padding: 12px 18px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.2);">
        <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8;">Afdeling Anda</div>
        <div style="font-size: 18px; font-weight: 800; color: #38bdf8;"><?= htmlspecialchars($afdeling ?: 'Belum diatur') ?></div>
    </div>
</div>

<div class="kl-filter-box">
    <form method="GET" id="formTahun" style="display: flex; align-items: center; gap: 10px;">
        <label style="font-weight: 700; font-size: 13px; color: #475569;">Pilih Tahun Laporan:</label>
        <select name="tahun" class="kl-select" onchange="document.getElementById('formTahun').submit()">
            <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                <option value="<?= $y ?>" <?= $tahun == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </form>
    <div style="font-size: 12px; color: #64748b;">
        <i class="fa-solid fa-circle-info" style="color: #3b82f6;"></i>
        Status laporan otomatis terhubung dengan modul Bagian Keuangan.
    </div>
</div>

<div class="card-table">
    <table class="table-kl">
        <thead>
            <tr>
                <th style="width: 50px;">No</th>
                <th style="width: 140px;">Bulan</th>
                <th>Laporan Absensi (Kehadiran)</th>
                <th>Laporan Keseluruhan (Objek Kerja)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($nama_bulan as $num_bln => $lbl_bln): 
                $p_absensi = $data_pengajuan[$num_bln]['absensi'] ?? null;
                $p_keseluruhan = $data_pengajuan[$num_bln]['keseluruhan'] ?? null;
            ?>
            <tr>
                <td style="font-weight: 700; color: #94a3b8;"><?= (int)$num_bln ?></td>
                <td>
                    <strong style="color: #1e293b; font-size: 14px;"><?= $lbl_bln ?></strong>
                    <div style="font-size: 11px; color: #64748b;"><?= $tahun ?></div>
                </td>

                <!-- KOLOM LAPORAN ABSENSI -->
                <td>
                    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;">
                        <div>
                            <?php if (!$p_absensi): ?>
                                <span class="badge-status badge-draft"><i class="fa-regular fa-clock"></i> Belum Dikirim</span>
                                <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">Belum diajukan ke Keuangan</div>
                            <?php elseif ($p_absensi['status'] === 'menunggu_validasi'): ?>
                                <span class="badge-status badge-pending"><i class="fa-solid fa-hourglass-half"></i> Menunggu Validasi</span>
                                <div style="font-size: 11px; color: #b45309; margin-top: 4px;">
                                    Dikirim: <?= date('d/m/Y H:i', strtotime($p_absensi['tanggal_kirim'])) ?>
                                </div>
                                <?php if (!empty($p_absensi['catatan_kerani'])): ?>
                                    <div style="font-size: 11px; color: #64748b; font-style: italic;">"<?= htmlspecialchars($p_absensi['catatan_kerani']) ?>"</div>
                                <?php endif; ?>
                            <?php elseif ($p_absensi['status'] === 'disetujui'): ?>
                                <span class="badge-status badge-approved"><i class="fa-solid fa-circle-check"></i> Disetujui Keuangan</span>
                                <div style="font-size: 11px; color: #15803d; margin-top: 4px;">
                                    Oleh: <?= htmlspecialchars($p_absensi['nama_keuangan'] ?: 'Keuangan') ?> (<?= date('d/m/Y', strtotime($p_absensi['tanggal_validasi'])) ?>)
                                </div>
                            <?php elseif ($p_absensi['status'] === 'ditolak'): ?>
                                <span class="badge-status badge-rejected"><i class="fa-solid fa-triangle-exclamation"></i> Perlu Revisi</span>
                                <div style="font-size: 11px; color: #b91c1c; font-weight: 600; margin-top: 4px;">
                                    Catatan Keuangan: "<?= htmlspecialchars($p_absensi['catatan_keuangan']) ?>"
                                </div>
                            <?php endif; ?>
                        </div>

                        <div style="display: flex; gap: 6px; flex-shrink: 0;">
                            <a href="laporan_absensi.php?bulan=<?= $num_bln ?>&tahun=<?= $tahun ?>&role=karyawan" class="btn-action-kl btn-check" title="Buka & Koreksi Data Absensi">
                                <i class="fa-solid fa-magnifying-glass"></i> Crosscheck
                            </a>

                            <?php if (!$p_absensi): ?>
                                <button type="button" class="btn-action-kl btn-send" onclick="bukaModalKirim('<?= $num_bln ?>', '<?= $tahun ?>', 'absensi', '<?= $lbl_bln ?>')">
                                    <i class="fa-solid fa-paper-plane"></i> Kirim
                                </button>
                            <?php elseif ($p_absensi['status'] === 'menunggu_validasi'): ?>
                                <form method="POST" onsubmit="return confirm('Tarik kembali pengajuan laporan absensi periode ini?');" style="display:inline;">
                                    <input type="hidden" name="action" value="tarik_laporan">
                                    <input type="hidden" name="id_pengajuan" value="<?= $p_absensi['id'] ?>">
                                    <button type="submit" class="btn-action-kl btn-cancel" title="Tarik kembali laporan untuk koreksi">
                                        <i class="fa-solid fa-rotate-left"></i> Tarik
                                    </button>
                                </form>
                            <?php elseif ($p_absensi['status'] === 'ditolak'): ?>
                                <button type="button" class="btn-action-kl btn-resend" onclick="bukaModalKirim('<?= $num_bln ?>', '<?= $tahun ?>', 'absensi', '<?= $lbl_bln ?>', true)">
                                    <i class="fa-solid fa-arrows-rotate"></i> Kirim Ulang
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>

                <!-- KOLOM LAPORAN KESELURUHAN OBJEK KERJA -->
                <td>
                    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;">
                        <div>
                            <?php if (!$p_keseluruhan): ?>
                                <span class="badge-status badge-draft"><i class="fa-regular fa-clock"></i> Belum Dikirim</span>
                                <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">Belum diajukan ke Keuangan</div>
                            <?php elseif ($p_keseluruhan['status'] === 'menunggu_validasi'): ?>
                                <span class="badge-status badge-pending"><i class="fa-solid fa-hourglass-half"></i> Menunggu Validasi</span>
                                <div style="font-size: 11px; color: #b45309; margin-top: 4px;">
                                    Dikirim: <?= date('d/m/Y H:i', strtotime($p_keseluruhan['tanggal_kirim'])) ?>
                                </div>
                                <?php if (!empty($p_keseluruhan['catatan_kerani'])): ?>
                                    <div style="font-size: 11px; color: #64748b; font-style: italic;">"<?= htmlspecialchars($p_keseluruhan['catatan_kerani']) ?>"</div>
                                <?php endif; ?>
                            <?php elseif ($p_keseluruhan['status'] === 'disetujui'): ?>
                                <span class="badge-status badge-approved"><i class="fa-solid fa-circle-check"></i> Disetujui Keuangan</span>
                                <div style="font-size: 11px; color: #15803d; margin-top: 4px;">
                                    Oleh: <?= htmlspecialchars($p_keseluruhan['nama_keuangan'] ?: 'Keuangan') ?> (<?= date('d/m/Y', strtotime($p_keseluruhan['tanggal_validasi'])) ?>)
                                </div>
                            <?php elseif ($p_keseluruhan['status'] === 'ditolak'): ?>
                                <span class="badge-status badge-rejected"><i class="fa-solid fa-triangle-exclamation"></i> Perlu Revisi</span>
                                <div style="font-size: 11px; color: #b91c1c; font-weight: 600; margin-top: 4px;">
                                    Catatan Keuangan: "<?= htmlspecialchars($p_keseluruhan['catatan_keuangan']) ?>"
                                </div>
                            <?php endif; ?>
                        </div>

                        <div style="display: flex; gap: 6px; flex-shrink: 0;">
                            <a href="laporan_keseluruhan.php?bulan=<?= $num_bln ?>&tahun=<?= $tahun ?>&objek=Panen" class="btn-action-kl btn-check" title="Buka & Cek Objek Kerja">
                                <i class="fa-solid fa-magnifying-glass"></i> Crosscheck
                            </a>

                            <?php if (!$p_keseluruhan): ?>
                                <button type="button" class="btn-action-kl btn-send" onclick="bukaModalKirim('<?= $num_bln ?>', '<?= $tahun ?>', 'keseluruhan', '<?= $lbl_bln ?>')">
                                    <i class="fa-solid fa-paper-plane"></i> Kirim
                                </button>
                            <?php elseif ($p_keseluruhan['status'] === 'menunggu_validasi'): ?>
                                <form method="POST" onsubmit="return confirm('Tarik kembali pengajuan laporan keseluruhan periode ini?');" style="display:inline;">
                                    <input type="hidden" name="action" value="tarik_laporan">
                                    <input type="hidden" name="id_pengajuan" value="<?= $p_keseluruhan['id'] ?>">
                                    <button type="submit" class="btn-action-kl btn-cancel" title="Tarik kembali laporan untuk koreksi">
                                        <i class="fa-solid fa-rotate-left"></i> Tarik
                                    </button>
                                </form>
                            <?php elseif ($p_keseluruhan['status'] === 'ditolak'): ?>
                                <button type="button" class="btn-action-kl btn-resend" onclick="bukaModalKirim('<?= $num_bln ?>', '<?= $tahun ?>', 'keseluruhan', '<?= $lbl_bln ?>', true)">
                                    <i class="fa-solid fa-arrows-rotate"></i> Kirim Ulang
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- MODAL KIRIM KE KEUANGAN -->
<div class="modal-overlay" id="modalKirim">
    <div class="modal-box">
        <h3 id="modalKirimTitle" style="font-size: 18px; font-weight: 800; color: #1e293b; margin-bottom: 8px;">Kirim Laporan ke Keuangan</h3>
        <p id="modalKirimDesc" style="font-size: 13px; color: #64748b; line-height: 1.5; margin-bottom: 18px;">
            Pastikan Anda sudah mengoreksi data sebelum mengirimkan laporan resmi ke Bagian Keuangan.
        </p>

        <form method="POST">
            <input type="hidden" name="action" value="kirim_laporan">
            <input type="hidden" name="bulan" id="inputBulan">
            <input type="hidden" name="tahun" id="inputTahun">
            <input type="hidden" name="jenis_laporan" id="inputJenis">

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">Catatan untuk Bagian Keuangan (Opsional):</label>
                <textarea name="catatan_kerani" class="kl-select" style="width: 100%; height: 90px; resize: vertical; font-size: 13px;" placeholder="Contoh: Seluruh data absensi telah dicrosscheck dengan logbook lapangan dan sudah valid."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn-action-kl btn-cancel" onclick="tutupModalKirim()" style="padding: 10px 16px;">Batal</button>
                <button type="submit" class="btn-action-kl btn-send" style="padding: 10px 20px;">
                    <i class="fa-solid fa-paper-plane"></i> Kirim Sekarang
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function bukaModalKirim(bln, thn, jenis, namaBulan, isResend = false) {
    document.getElementById('inputBulan').value = bln;
    document.getElementById('inputTahun').value = thn;
    document.getElementById('inputJenis').value = jenis;

    const labelJenis = jenis === 'absensi' ? 'Laporan Absensi' : 'Laporan Keseluruhan Objek Kerja';
    const titleText = (isResend ? 'Kirim Ulang ' : 'Kirim ') + labelJenis + ' (' + namaBulan + ' ' + thn + ')';
    document.getElementById('modalKirimTitle').innerText = titleText;
    document.getElementById('modalKirimDesc').innerText = 'Apakah Anda yakin data ' + labelJenis + ' untuk Afdeling <?= htmlspecialchars($afdeling) ?> periode ' + namaBulan + ' ' + thn + ' sudah di-crosscheck dan siap dikirim ke Bagian Keuangan?';

    document.getElementById('modalKirim').classList.add('active');
}

function tutupModalKirim() {
    document.getElementById('modalKirim').classList.remove('active');
}
</script>

<?php include 'templates/footer.php'; ?>
