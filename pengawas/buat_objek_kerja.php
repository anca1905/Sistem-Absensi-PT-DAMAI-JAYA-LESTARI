<?php
require '../config/config.php';
include 'templates/header.php';

$pengawas_id = $_SESSION['user_id'];
$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');
$tgl_safe = mysqli_real_escape_string($conn, $tanggal);

// Ambil afdeling milik pengawas yang login
$q_pengawas = mysqli_query($conn, "SELECT afdeling FROM users WHERE id=$pengawas_id LIMIT 1");
$pengawas_data = mysqli_fetch_assoc($q_pengawas);
$afdeling_pengawas = $pengawas_data ? mysqli_real_escape_string($conn, $pengawas_data['afdeling']) : '';

// Ambil daftar mandor yang seafdeling dengan pengawas
$query_mandor = mysqli_query($conn, "SELECT id, name FROM users WHERE role='mandor' AND afdeling='$afdeling_pengawas' ORDER BY name ASC");
$list_mandor = [];
while ($m = mysqli_fetch_assoc($query_mandor)) {
    $list_mandor[] = $m;
}

// Data Master (Ditanam di code seperti permintaan client)
$list_objek = [
    'Langsir manual',
    'Membabat gawangan',
    'Rawat jalan',
    'Panen',
    'Penunasan',
    'Racun piringan',
    'Perawatan',
    'Muat TBS ke truk',
    'Muat TBS ke jonder'
];
$list_blok = [
    'H.39' => '8.66',
    'H.40' => '0.91',
    'I.39' => '29.26',
    'I.40' => '26.18',
    'J.39' => '31.01',
    'J.40' => '27.05',
    'K.39' => '20.98',
    'K.40' => '28.52',
    'L.39' => '31.17',
    'L.40' => '17.74',
    'O.44' => '20.44',
    'J.30' => '15.00'
];

// Handle Simpan Data
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['rows'])) {
    $saved = 0;
    foreach ($_POST['rows'] as $row_data) {
        $mandor_id = (int)($row_data['mandor'] ?? 0);
        if ($mandor_id == 0) continue; // Skip jika tidak pilih mandor

        $objek = mysqli_real_escape_string($conn, $row_data['objek'] ?? '');
        $tenaga_l = (int)($row_data['tenaga_l'] ?? 0);
        $tenaga_w = (int)($row_data['tenaga_w'] ?? 0);
        $blok = mysqli_real_escape_string($conn, $row_data['blok'] ?? '');
        $luas = mysqli_real_escape_string($conn, $row_data['luas'] ?? '');

        mysqli_query($conn, "INSERT INTO rencana_kerja_pengawas (tanggal, pengawas_id, mandor_id, objek_kerja, tenaga_l, tenaga_w, blok, luas_ha) 
                             VALUES ('$tgl_safe', $pengawas_id, $mandor_id, '$objek', $tenaga_l, $tenaga_w, '$blok', '$luas')");
        $saved++;
    }
    if ($saved > 0) {
        swalRedirect("$saved rencana objek kerja berhasil dibuat!", "buat_objek_kerja.php?tanggal=$tgl_safe", 'success');
        exit;
    }
}

// Ambil data yang sudah ada
$query_rencana = mysqli_query($conn, "
    SELECT r.*, m.name as mandor_name 
    FROM rencana_kerja_pengawas r 
    JOIN users m ON r.mandor_id = m.id 
    WHERE r.tanggal = '$tgl_safe' AND r.pengawas_id = $pengawas_id
    ORDER BY r.id ASC
");
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

    .form-input,
    .form-select {
        width: 100%;
        padding: 10px 14px;
        border-radius: 10px;
        border: 1.5px solid #e2e8f0;
        background-color: #f8fafc;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-dark);
        font-family: inherit;
        outline: none;
        transition: all 0.2s;
        box-sizing: border-box;
    }

    .form-input:focus,
    .form-select:focus {
        border-color: var(--primary-start);
        box-shadow: 0 0 0 4px rgba(66, 88, 255, 0.1);
        background: white;
    }

    .table-responsive {
        width: 100%;
        overflow-x: auto;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        -webkit-overflow-scrolling: touch;
        background: white;
        margin-bottom: 16px;
    }

    .table-rencana {
        border-collapse: collapse;
        white-space: nowrap;
        font-size: 13px;
        width: 100%;
        min-width: 800px;
    }

    .table-rencana th,
    .table-rencana td {
        padding: 12px 10px;
        border: 1px solid #e2e8f0;
        vertical-align: middle;
        text-align: center;
    }

    .table-rencana th {
        background-color: var(--primary-light);
        color: var(--primary-end);
        font-weight: 800;
        font-size: 11px;
        text-transform: uppercase;
    }

    .table-rencana td.left {
        text-align: left;
    }

    .input-mini {
        width: 60px;
        text-align: center;
    }

    .btn-submit {
        width: 100%;
        background: linear-gradient(135deg, var(--primary-start) 0%, var(--primary-end) 100%);
        color: white;
        border: none;
        padding: 16px;
        border-radius: 14px;
        font-size: 15px;
        font-weight: 800;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        transition: all 0.2s;
        box-shadow: 0 4px 15px rgba(66, 88, 255, 0.25);
    }

    .btn-submit:active {
        transform: scale(0.98);
        box-shadow: none;
    }

    .btn-add {
        background: #f1f5f9;
        color: var(--primary-end);
        border: 1px dashed #cbd5e1;
        padding: 12px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        width: 100%;
        margin-bottom: 24px;
        transition: all 0.2s;
    }

    .btn-add:hover {
        background: #e2e8f0;
        border-style: solid;
    }

    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: var(--text-muted);
        text-decoration: none;
        font-weight: 700;
        font-size: 13px;
        margin-bottom: 16px;
        background: white;
        padding: 8px 14px;
        border-radius: 20px;
        border: 1.5px solid #e2e8f0;
    }

    .btn-delete {
        background: #fee2e2;
        color: #ef4444;
        border: none;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .btn-delete:hover {
        background: #fca5a5;
        color: white;
    }
</style>

<div class="animate-up">
    <a href="index.php" class="btn-back">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        Kembali
    </a>

    <h2 class="page-title" style="margin: 0 0 20px 0; font-size: 20px;">Membuat Objek Kerja</h2>

    <form id="filterForm" method="GET" style="margin-bottom: 20px;">
        <label style="font-size: 12px; font-weight: 700; color: #64748b; margin-bottom:6px; display:block;">Pilih Tanggal Rencana Kerja</label>
        <input type="date" name="tanggal" class="form-input" value="<?= $tanggal ?>" onchange="document.getElementById('filterForm').submit()">
    </form>

    <div class="card-container">

        <?php if (mysqli_num_rows($query_rencana) > 0): ?>
            <h3 style="font-size:14px; margin-bottom:12px; color:var(--text-dark);">Rencana Kerja Hari Ini</h3>
            <div class="table-responsive" style="margin-bottom:32px;">
                <table class="table-rencana">
                    <thead>
                        <tr>
                            <th rowspan="2">NO</th>
                            <th rowspan="2">NAMA MANDOR</th>
                            <th rowspan="2">OBJEK KERJA</th>
                            <th colspan="2">TENAGA</th>
                            <th rowspan="2">BLOK</th>
                            <th rowspan="2">LUAS HA</th>
                        </tr>
                        <tr>
                            <th>LAKI-LAKI</th>
                            <th>WANITA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1;
                        while ($r = mysqli_fetch_assoc($query_rencana)): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td class="left" style="font-weight:700;"><?= htmlspecialchars($r['mandor_name']) ?></td>
                                <td><?= htmlspecialchars($r['objek_kerja']) ?></td>
                                <td style="font-weight:700; color:#3b82f6;"><?= $r['tenaga_l'] ?></td>
                                <td style="font-weight:700; color:#ec4899;"><?= $r['tenaga_w'] ?></td>
                                <td style="font-weight:700;"><?= htmlspecialchars($r['blok']) ?></td>
                                <td><?= htmlspecialchars($r['luas_ha']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <hr style="border:none; border-top:1px dashed #cbd5e1; margin-bottom:24px;">
        <?php endif; ?>

        <h3 style="font-size:14px; margin-bottom:12px; color:var(--text-dark);">Tambah Objek Kerja Baru</h3>

        <form method="POST">
            <div class="table-responsive">
                <table class="table-rencana" id="tableForm">
                    <thead>
                        <tr>
                            <th rowspan="2">NO</th>
                            <th rowspan="2">NAMA MANDOR</th>
                            <th rowspan="2">OBJEK KERJA<br><span style="font-size:9px; font-weight:normal; text-transform:none;">(ditanam di code)</span></th>
                            <th colspan="2">TENAGA (Diisi)</th>
                            <th rowspan="2">BLOK<br><span style="font-size:9px; font-weight:normal; text-transform:none;">(ditanam di code)</span></th>
                            <th rowspan="2">LUAS HA</th>
                            <th rowspan="2">#</th>
                        </tr>
                        <tr>
                            <th>Laki2</th>
                            <th>Wanita</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyForm">
                        <!-- Baris akan diisi JS -->
                    </tbody>
                </table>
            </div>

            <button type="button" class="btn-add" onclick="tambahBaris()">+ Tambah Baris Kosong</button>

            <button type="submit" class="btn-submit">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                Simpan Rencana Objek Kerja
            </button>
        </form>
    </div>
</div>

<script>
    const listMandor = <?= json_encode($list_mandor) ?>;
    const listObjek = <?= json_encode($list_objek) ?>;
    const listBlok = <?= json_encode($list_blok) ?>;
    let rowCount = 0;

    function tambahBaris() {
        const tbody = document.getElementById('tbodyForm');

        // Options for Mandor
        let optMandor = '<option value="">Pilih Mandor</option>';
        listMandor.forEach(m => {
            optMandor += `<option value="${m.id}">${m.name}</option>`;
        });

        // Options for Objek
        let optObjek = '<option value="">Pilih Objek</option>';
        listObjek.forEach(o => {
            optObjek += `<option value="${o}">${o}</option>`;
        });

        // Options for Blok
        let optBlok = '<option value="" data-luas="">Pilih Blok</option>';
        for (const [blok, luas] of Object.entries(listBlok)) {
            optBlok += `<option value="${blok}" data-luas="${luas}">${blok}</option>`;
        }

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>#</td>
            <td><select name="rows[${rowCount}][mandor]" class="form-select" required>${optMandor}</select></td>
            <td><select name="rows[${rowCount}][objek]" class="form-select" required>${optObjek}</select></td>
            <td><input type="number" min="0" name="rows[${rowCount}][tenaga_l]" class="form-input input-mini" placeholder="0"></td>
            <td><input type="number" min="0" name="rows[${rowCount}][tenaga_w]" class="form-input input-mini" placeholder="0"></td>
            <td><select name="rows[${rowCount}][blok]" class="form-select" onchange="isiLuas(this, 'luas_${rowCount}')" required>${optBlok}</select></td>
            <td><input type="text" id="luas_${rowCount}" name="rows[${rowCount}][luas]" class="form-input input-mini" style="background:#f1f5f9;" readonly placeholder="0.00"></td>
            <td>
                <button type="button" class="btn-delete" onclick="this.closest('tr').remove()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
        rowCount++;
    }

    function isiLuas(selectObj, targetInputId) {
        const selectedOption = selectObj.options[selectObj.selectedIndex];
        const luas = selectedOption.getAttribute('data-luas');
        document.getElementById(targetInputId).value = luas ? luas : '';
    }

    // Default 1 baris
    window.onload = function() {
        tambahBaris();
    };
</script>

<?php include 'templates/footer.php'; ?>