<?php
require '../config/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit;
}
include 'templates/header.php';
$currentToken = getFonnteToken();
?>

<style>
    .wa-wrapper {
        max-width: 900px;
        margin: 20px auto 40px auto;
    }
    .wa-card {
        background: #ffffff;
        border-radius: 14px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
        padding: 24px;
        margin-bottom: 24px;
        border: 1px solid #e2e8f0;
    }
    .wa-header-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 700;
    }
    .status-connected {
        background-color: #dcfce7;
        color: #15803d;
    }
    .status-disconnected {
        background-color: #fee2e2;
        color: #b91c1c;
    }
    .status-waiting {
        background-color: #fef3c7;
        color: #b45309;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
        margin-top: 18px;
    }
    .info-item {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 14px;
        text-align: center;
    }
    .info-label {
        font-size: 12px;
        color: #64748b;
        font-weight: 600;
        text-transform: uppercase;
        margin-bottom: 4px;
    }
    .info-value {
        font-size: 15px;
        font-weight: 700;
        color: #1e293b;
        word-break: break-all;
    }

    .token-input-group {
        display: flex;
        gap: 10px;
        margin-top: 8px;
    }
    .token-input {
        flex: 1;
        padding: 10px 14px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 14px;
        font-family: monospace;
        outline: none;
        transition: border-color 0.2s;
    }
    .token-input:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }
    
    .guide-box {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 10px;
        padding: 16px;
        color: #1e40af;
        font-size: 13px;
        line-height: 1.6;
        margin-top: 16px;
    }
    .guide-box ol {
        margin: 8px 0 0 20px;
        padding: 0;
    }
    .guide-box li {
        margin-bottom: 4px;
    }

    .btn-action {
        padding: 10px 18px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: 0.2s;
    }
    .btn-action-primary {
        background-color: #2563eb;
        color: #ffffff;
    }
    .btn-action-primary:hover { background-color: #1d4ed8; }
    .btn-action-success {
        background-color: #16a34a;
        color: #ffffff;
    }
    .btn-action-success:hover { background-color: #15803d; }
    .btn-action-danger {
        background-color: #dc2626;
        color: #ffffff;
    }
    .btn-action-danger:hover { background-color: #b91c1c; }
    .btn-action-secondary {
        background-color: #64748b;
        color: #ffffff;
    }
    .btn-action-secondary:hover { background-color: #475569; }

    .test-box {
        display: grid;
        grid-template-columns: 1fr 2fr auto;
        gap: 12px;
        align-items: flex-end;
    }
    @media (max-width: 768px) {
        .test-box {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="wa-wrapper">
    <!-- Header Page -->
    <div class="page-header" style="margin-bottom: 20px;">
        <div>
            <h1 class="page-title" style="display: flex; align-items: center; gap: 10px;">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                </svg>
                Koneksi WhatsApp Gateway (Fonnte API)
            </h1>
            <p class="page-subtitle">Kelola integrasi WhatsApp notifikasi sistem secara ringan melalui cloud gateway Fonnte.</p>
        </div>
    </div>

    <!-- Alert Keunggulan Fonnte -->
    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 14px 18px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px;">
        <span style="font-size: 24px;">⚡</span>
        <div style="font-size: 13px; color: #166534; line-height: 1.5;">
            <strong>Server Ringan & Hemat RAM:</strong> WhatsApp kini terintegrasi langsung via <b>Fonnte Cloud API</b>. Server Anda tidak lagi menjalankan bot Node.js / Chrome Headless, sehingga beban server 0% dan tidak berisiko kehabisan memori.
        </div>
    </div>

    <!-- Card 1: Status Perangkat Fonnte -->
    <div class="wa-card">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <div>
                <h3 style="margin: 0; font-size: 18px; color: #1e293b;">Status Koneksi WhatsApp</h3>
                <p style="margin: 4px 0 0 0; font-size: 13px; color: #64748b;">Informasi koneksi nomor WhatsApp di cloud Fonnte</p>
            </div>
            <div id="deviceBadge" class="wa-header-badge status-waiting">
                <span>🔄 Memeriksa Status...</span>
            </div>
        </div>

        <!-- Grid Detail Perangkat -->
        <div class="info-grid" id="deviceDetailsGrid">
            <div class="info-item">
                <div class="info-label">Nomor Perangkat</div>
                <div class="info-value" id="valDevice">-</div>
            </div>
            <div class="info-item">
                <div class="info-label">Nama Akun</div>
                <div class="info-value" id="valName">-</div>
            </div>
            <div class="info-item">
                <div class="info-label">Paket Fonnte</div>
                <div class="info-value" id="valPackage">-</div>
            </div>
            <div class="info-item">
                <div class="info-label">Sisa Kuota</div>
                <div class="info-value" id="valQuota">-</div>
            </div>
            <div class="info-item">
                <div class="info-label">Masa Berlaku</div>
                <div class="info-value" id="valExpired">-</div>
            </div>
        </div>

        <!-- Tombol Aksi Cepat -->
        <div style="margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
            <button type="button" class="btn-action btn-action-primary" onclick="loadStatus()">
                🔄 Refresh Status
            </button>
            <button type="button" class="btn-action btn-action-success" id="btnShowQR" onclick="getQR()" style="display: none;">
                📱 Scan QR Code
            </button>
            <button type="button" class="btn-action btn-action-danger" id="btnDisconnect" onclick="confirmDisconnect()" style="display: none;">
                ⚠️ Putuskan Koneksi / Logout
            </button>
            <a href="https://md.fonnte.com/" target="_blank" class="btn-action btn-action-secondary" style="text-decoration: none;">
                🌐 Buka Dashboard Fonnte
            </a>
        </div>
    </div>

    <!-- Card 2: Pengaturan API Token -->
    <div class="wa-card">
        <h3 style="margin: 0 0 6px 0; font-size: 18px; color: #1e293b;">Konfigurasi Token Fonnte</h3>
        <p style="margin: 0 0 16px 0; font-size: 13px; color: #64748b;">Masukkan Token Device Fonnte Anda untuk menghubungkan sistem dengan WhatsApp.</p>

        <form id="formToken" onsubmit="saveToken(event)">
            <label style="font-size: 13px; font-weight: 600; color: #334155;">Fonnte Device API Token:</label>
            <div class="token-input-group">
                <input type="text" id="fonnteTokenInput" class="token-input" placeholder="Masukkan token Fonnte (contoh: aBcDeF123456...)" value="<?= htmlspecialchars($currentToken) ?>" required>
                <button type="submit" class="btn-action btn-action-primary" id="btnSaveToken">
                    💾 Simpan Token
                </button>
            </div>
        </form>

        <div class="guide-box">
            <strong>Cara Mendapatkan Token Fonnte:</strong>
            <ol>
                <li>Daftar akun gratis atau berbayar di <a href="https://fonnte.com" target="_blank" style="color: #2563eb; font-weight: bold;">fonnte.com</a>.</li>
                <li>Masuk ke <b>Dashboard Fonnte</b> di <a href="https://md.fonnte.com" target="_blank" style="color: #2563eb; font-weight: bold;">md.fonnte.com</a>.</li>
                <li>Pilih menu <b>Device</b>, buat perangkat baru atau pilih perangkat yang sudah ada.</li>
                <li>Salin <b>Token</b> yang tertera, lalu tempelkan (paste) pada form di atas dan klik <b>Simpan Token</b>.</li>
                <li>Scan QR Code melalui dashboard Fonnte atau klik tombol <b>Scan QR Code</b> di atas.</li>
            </ol>
        </div>
    </div>

    <!-- Card 3: Uji Coba Pengiriman Pesan -->
    <div class="wa-card">
        <h3 style="margin: 0 0 6px 0; font-size: 18px; color: #1e293b;">Uji Coba Pengiriman WhatsApp</h3>
        <p style="margin: 0 0 16px 0; font-size: 13px; color: #64748b;">Kirim pesan tes ke nomor Anda untuk memastikan integrasi Fonnte berjalan sempurna.</p>

        <form id="formTestSend" onsubmit="sendTestMessage(event)">
            <div class="test-box">
                <div>
                    <label style="font-size: 12px; font-weight: 600; color: #475569; display: block; margin-bottom: 4px;">Nomor Tujuan (HP):</label>
                    <input type="text" id="testTarget" class="token-input" style="width: 100%;" placeholder="Cth: 08123456789" required>
                </div>
                <div>
                    <label style="font-size: 12px; font-weight: 600; color: #475569; display: block; margin-bottom: 4px;">Pesan Pengujian:</label>
                    <input type="text" id="testMessage" class="token-input" style="width: 100%;" value="Halo! Ini adalah pesan uji coba dari Sistem Absensi PT Damai Jaya Lestari via Fonnte." required>
                </div>
                <div>
                    <button type="submit" class="btn-action btn-action-success" id="btnTestSend" style="height: 42px;">
                        🚀 Kirim Pesan Tes
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    // Ambil elemen DOM
    const deviceBadge = document.getElementById('deviceBadge');
    const valDevice = document.getElementById('valDevice');
    const valName = document.getElementById('valName');
    const valPackage = document.getElementById('valPackage');
    const valQuota = document.getElementById('valQuota');
    const valExpired = document.getElementById('valExpired');
    const btnShowQR = document.getElementById('btnShowQR');
    const btnDisconnect = document.getElementById('btnDisconnect');
    const fonnteTokenInput = document.getElementById('fonnteTokenInput');

    async function loadStatus() {
        deviceBadge.className = 'wa-header-badge status-waiting';
        deviceBadge.innerHTML = '<span>🔄 Memeriksa Status...</span>';

        try {
            const res = await fetch('wa_api_proxy.php?action=status');
            const data = await res.json();

            if (!data.configured) {
                deviceBadge.className = 'wa-header-badge status-disconnected';
                deviceBadge.innerHTML = '<span>⚠️ Token Belum Diatur</span>';
                valDevice.innerText = '-';
                valName.innerText = '-';
                valPackage.innerText = '-';
                valQuota.innerText = '-';
                valExpired.innerText = '-';
                btnShowQR.style.display = 'none';
                btnDisconnect.style.display = 'none';
                return;
            }

            if (data.token) {
                fonnteTokenInput.value = data.token;
            }

            // Cek status dari Fonnte
            const statusStr = (data.device_status || '').toLowerCase();
            
            if (statusStr === 'connect' || data.status === 'connect') {
                deviceBadge.className = 'wa-header-badge status-connected';
                deviceBadge.innerHTML = '<span>✅ WhatsApp Terhubung</span>';
                btnShowQR.style.display = 'none';
                btnDisconnect.style.display = 'inline-flex';
            } else if (statusStr === 'disconnect' || data.device_status === 'disconnect') {
                deviceBadge.className = 'wa-header-badge status-disconnected';
                deviceBadge.innerHTML = '<span>❌ Belum Terhubung (Disconnect)</span>';
                btnShowQR.style.display = 'inline-flex';
                btnDisconnect.style.display = 'none';
            } else {
                // Token tidak valid atau error
                deviceBadge.className = 'wa-header-badge status-disconnected';
                deviceBadge.innerHTML = '<span>⚠️ ' + (data.reason || 'Token Tidak Valid') + '</span>';
                btnShowQR.style.display = 'none';
                btnDisconnect.style.display = 'none';
            }

            valDevice.innerText = data.device || '-';
            valName.innerText = data.name || '-';
            valPackage.innerText = data.package || '-';
            valQuota.innerText = (data.quota !== undefined ? data.quota : '-');
            valExpired.innerText = data.expired || '-';

        } catch (error) {
            deviceBadge.className = 'wa-header-badge status-disconnected';
            deviceBadge.innerHTML = '<span>❌ Gagal Cek Status</span>';
            console.error('Error loadStatus:', error);
        }
    }

    async function saveToken(e) {
        e.preventDefault();
        const token = fonnteTokenInput.value.trim();
        if (!token) {
            Swal.fire('Peringatan', 'Token Fonnte tidak boleh kosong!', 'warning');
            return;
        }

        const btn = document.getElementById('btnSaveToken');
        btn.disabled = true;
        btn.innerText = 'Menyimpan...';

        try {
            const formData = new FormData();
            formData.append('token', token);

            const res = await fetch('wa_api_proxy.php?action=save_token', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.status) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Token Fonnte berhasil disimpan.',
                    timer: 2000,
                    showConfirmButton: false
                });
                loadStatus();
            } else {
                Swal.fire('Gagal!', data.message || 'Gagal menyimpan token', 'error');
            }
        } catch (err) {
            Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
        } finally {
            btn.disabled = false;
            btn.innerText = '💾 Simpan Token';
        }
    }

    async function getQR() {
        Swal.fire({
            title: 'Mengambil QR Code...',
            text: 'Menghubungkan ke server Fonnte...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        try {
            const res = await fetch('wa_api_proxy.php?action=qr');
            const data = await res.json();

            if (data.status === true && data.url) {
                // QR image URL provided
                Swal.fire({
                    title: 'Scan QR WhatsApp',
                    html: `
                        <div style="text-align: center;">
                            <img src="${data.url}" style="max-width: 250px; border: 1px solid #ddd; border-radius: 8px; padding: 6px;" alt="QR Code">
                            <p style="margin-top: 12px; font-size: 13px; color: #64748b;">
                                Buka WhatsApp di HP Anda &gt; <b>Perangkat Tertaut</b> &gt; <b>Tautkan Perangkat</b>, lalu arahkan kamera ke QR di atas.
                            </p>
                        </div>
                    `,
                    confirmButtonText: 'Selesai Scan / Cek Status',
                    confirmButtonColor: '#2563eb'
                }).then(() => {
                    loadStatus();
                });
            } else if (data.status === true && data.qr) {
                // Base64 QR provided
                const qrSrc = data.qr.startsWith('data:') ? data.qr : 'data:image/png;base64,' + data.qr;
                Swal.fire({
                    title: 'Scan QR WhatsApp',
                    html: `
                        <div style="text-align: center;">
                            <img src="${qrSrc}" style="max-width: 250px; border: 1px solid #ddd; border-radius: 8px; padding: 6px;" alt="QR Code">
                            <p style="margin-top: 12px; font-size: 13px; color: #64748b;">
                                Buka WhatsApp di HP Anda &gt; <b>Perangkat Tertaut</b> &gt; <b>Tautkan Perangkat</b>, lalu arahkan kamera ke QR di atas.
                            </p>
                        </div>
                    `,
                    confirmButtonText: 'Selesai Scan / Cek Status',
                    confirmButtonColor: '#2563eb'
                }).then(() => {
                    loadStatus();
                });
            } else {
                Swal.fire({
                    icon: 'info',
                    title: 'Info QR Fonnte',
                    text: data.message || (data.reason || 'Perangkat mungkin sudah terhubung atau silakan scan langsung di dashboard Fonnte.'),
                    confirmButtonText: 'OK'
                }).then(() => {
                    loadStatus();
                });
            }
        } catch (e) {
            Swal.fire('Error', 'Gagal memuat QR Code. Anda juga dapat melakukan scan langsung di dashboard Fonnte (md.fonnte.com).', 'error');
        }
    }

    function confirmDisconnect() {
        Swal.fire({
            title: 'Putuskan Koneksi WhatsApp?',
            text: 'Nomor WhatsApp akan dikeluarkan dari Fonnte. Anda harus scan ulang untuk menghubungkan kembali.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Putuskan!',
            cancelButtonText: 'Batal'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const res = await fetch('wa_api_proxy.php?action=disconnect', { method: 'POST' });
                    const data = await res.json();
                    Swal.fire('Berhasil', data.detail || 'Koneksi perangkat telah diputuskan.', 'success');
                    loadStatus();
                } catch (e) {
                    Swal.fire('Error', 'Gagal memutus koneksi di Fonnte.', 'error');
                }
            }
        });
    }

    async function sendTestMessage(e) {
        e.preventDefault();
        const target = document.getElementById('testTarget').value.trim();
        const message = document.getElementById('testMessage').value.trim();

        if (!target || !message) {
            Swal.fire('Peringatan', 'Nomor tujuan dan pesan wajib diisi!', 'warning');
            return;
        }

        const btn = document.getElementById('btnTestSend');
        btn.disabled = true;
        btn.innerText = 'Mengirim...';

        try {
            const formData = new FormData();
            formData.append('target', target);
            formData.append('message', message);

            const res = await fetch('wa_api_proxy.php?action=test_send', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.status) {
                Swal.fire({
                    icon: 'success',
                    title: 'Pesan Terkirim!',
                    text: data.message || 'Pesan uji coba berhasil terkirim via Fonnte.',
                    confirmButtonColor: '#16a34a'
                });
                loadStatus(); // refresh kuota
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Pengiriman Gagal',
                    text: data.message || 'Periksa status koneksi Fonnte atau token Anda.',
                    confirmButtonColor: '#dc2626'
                });
            }
        } catch (err) {
            Swal.fire('Error', 'Terjadi kesalahan saat mengirim pesan uji coba.', 'error');
        } finally {
            btn.disabled = false;
            btn.innerText = '🚀 Kirim Pesan Tes';
        }
    }

    // Panggil saat halaman pertama kali dimuat
    document.addEventListener('DOMContentLoaded', () => {
        loadStatus();
    });
</script>

<?php include 'templates/footer.php'; ?>
