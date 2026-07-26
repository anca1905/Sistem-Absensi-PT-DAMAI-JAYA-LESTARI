<?php
require '../config/config.php';
include 'templates/header.php';

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $jenis = mysqli_real_escape_string($conn, $_POST['jenis']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    
    $file_name = '';
    if(isset($_FILES['bukti']) && $_FILES['bukti']['error'] == 0){
        $target_dir = "../uploads/izin/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_name = time() . '_' . basename($_FILES["bukti"]["name"]);
        move_uploaded_file($_FILES["bukti"]["tmp_name"], $target_dir . $file_name);
    }

    $query = "INSERT INTO perizinan (user_id, tanggal_izin, jenis, keterangan, bukti_file) 
              VALUES ('$user_id', '$tanggal', '$jenis', '$keterangan', '$file_name')";
    if(mysqli_query($conn, $query)){
        $insert_id = mysqli_insert_id($conn);
        
        // Ambil data pemohon
        $q_pemohon = mysqli_query($conn, "SELECT name FROM users WHERE id=$user_id");
        $pemohon = mysqli_fetch_assoc($q_pemohon)['name'];

        // Cari nomor HP kerani
        $q_kerani = mysqli_query($conn, "SELECT no_hp FROM users WHERE role='kerani' LIMIT 1");
        if ($row_kerani = mysqli_fetch_assoc($q_kerani)) {
            $no_kerani = $row_kerani['no_hp'];
            if (!empty($no_kerani)) {
                $secret_key = "DJL_AMANDA_SECRET";
                $token = md5($insert_id . $secret_key);
                $base_url = BASE_URL . "kerani/wa_action.php";
                
                $link_setuju = "{$base_url}?id={$insert_id}&action=setuju&token={$token}";
                $link_tolak  = "{$base_url}?id={$insert_id}&action=tolak&token={$token}";
                
                $link_file = "";
                if ($file_name != '') {
                    $link_file = "\n📂 *Bukti Dokumen*: " . BASE_URL . "uploads/izin/" . $file_name . "\n";
                }

                $pesan = "🔔 *PENGUMUMAN PENGAJUAN BARU* 🔔\n\n";
                $pesan .= "Halo Kerani, ada pengajuan baru dari:\n";
                $pesan .= "👤 *Nama*: {$pemohon}\n";
                $pesan .= "📅 *Tanggal*: {$tanggal}\n";
                $pesan .= "📝 *Jenis*: {$jenis}\n";
                $pesan .= "💬 *Keterangan*: {$keterangan}\n";
                $pesan .= $link_file;
                $pesan .= "\nSilakan klik salah satu link di bawah ini untuk memvalidasi (Tanpa Login):\n\n";
                $pesan .= "✅ *SETUJU*:\n{$link_setuju}\n\n";
                $pesan .= "❌ *TOLAK*:\n{$link_tolak}\n\n";
                $pesan .= "_Sistem Informasi Karyawan PT DJL_";

                sendWA($no_kerani, $pesan);
            }
        }

        swalRedirect('Pengajuan berhasil dikirim!', 'index.php', 'success');
    } else {
        swalAlert('Gagal mengirim pengajuan.', 'error');
    }
}
?>

<style>
    .form-container {
        background: #ffffff;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        border: 1px solid #e2e8f0;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        font-weight: 700;
        font-size: 14px;
        color: #1e293b;
        margin-bottom: 8px;
    }

    .form-control {
        width: 100%;
        padding: 14px 16px;
        border-radius: 12px;
        border: 2px solid #e2e8f0;
        background-color: #f8fafc;
        font-size: 15px;
        font-family: inherit;
        color: #1e293b;
        transition: all 0.2s;
        box-sizing: border-box;
    }

    .form-control:focus {
        border-color: var(--primary-start);
        background-color: #ffffff;
        outline: none;
        box-shadow: 0 0 0 4px rgba(66, 88, 255, 0.1);
    }

    textarea.form-control {
        resize: vertical;
        min-height: 120px;
    }

    /* Custom File Input */
    .file-upload-wrapper {
        position: relative;
        overflow: hidden;
        display: inline-block;
        width: 100%;
    }

    .file-upload-wrapper input[type=file] {
        font-size: 100px;
        position: absolute;
        left: 0;
        top: 0;
        opacity: 0;
        cursor: pointer;
        height: 100%;
    }

    .btn-upload-fake {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #f1f5f9;
        border: 2px dashed #cbd5e1;
        padding: 16px;
        border-radius: 12px;
        color: #64748b;
        font-weight: 600;
        text-align: center;
        justify-content: center;
        transition: all 0.2s;
    }
    
    .file-upload-wrapper:hover .btn-upload-fake {
        border-color: var(--primary-start);
        color: var(--primary-start);
        background: var(--primary-light);
    }

    .btn-submit {
        width: 100%;
        background: linear-gradient(135deg, var(--primary-start) 0%, var(--primary-end) 100%);
        color: white;
        border: none;
        padding: 16px;
        border-radius: 14px;
        font-size: 16px;
        font-weight: 800;
        cursor: pointer;
        margin-top: 10px;
        transition: all 0.2s;
        box-shadow: 0 4px 15px rgba(66, 88, 255, 0.25);
    }

    .btn-submit:active {
        transform: scale(0.98);
        box-shadow: none;
    }

    /* Back button */
    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #64748b;
        text-decoration: none;
        font-weight: 700;
        font-size: 14px;
        margin-bottom: 20px;
        background: white;
        padding: 8px 16px;
        border-radius: 20px;
        border: 1px solid #e2e8f0;
    }
</style>

<div class="animate-up">
    <a href="index.php" class="btn-back">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        Kembali
    </a>

    <h2 class="page-title" style="text-align: left; margin-bottom: 24px; font-size: 24px;">Formulir Izin/Sakit/Cuti</h2>

    <div class="form-container">
        <form method="POST" action="form_izin.php" enctype="multipart/form-data">
            
            <div class="form-group">
                <label class="form-label">Tanggal Izin/Sakit/Cuti</label>
                <input type="date" name="tanggal" class="form-control" required value="<?= date('Y-m-d') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Jenis Pengajuan</label>
                <select name="jenis" class="form-control" required>
                    <option value="">Pilih Jenis</option>
                    <option value="izin">Izin</option>
                    <option value="sakit">Sakit</option>
                    <option value="cuti">Cuti</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Keterangan/Alasan</label>
                <textarea name="keterangan" class="form-control" placeholder="Jelaskan alasan secara singkat..." required></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Upload Bukti File (Opsional)</label>
                <div class="file-upload-wrapper">
                    <div class="btn-upload-fake" id="uploadText">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                        Tap untuk pilih file gambar/PDF
                    </div>
                    <input type="file" name="bukti" id="buktiInput" onchange="updateFileName(this)" accept="image/*,.pdf">
                </div>
            </div>

            <button type="submit" class="btn-submit">Kirim Pengajuan</button>
        </form>
    </div>
</div>

<script>
function updateFileName(input) {
    var textDiv = document.getElementById('uploadText');
    if (input.files && input.files.length > 0) {
        textDiv.innerHTML = '<span style="color:#10b981; font-weight:700;">✓ ' + input.files[0].name + '</span>';
        textDiv.style.borderColor = '#10b981';
        textDiv.style.background = '#ecfdf5';
    } else {
        textDiv.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg> Tap untuk pilih file gambar/PDF';
        textDiv.style.borderColor = '#cbd5e1';
        textDiv.style.background = '#f1f5f9';
    }
}
</script>

<?php include 'templates/footer.php'; ?>
