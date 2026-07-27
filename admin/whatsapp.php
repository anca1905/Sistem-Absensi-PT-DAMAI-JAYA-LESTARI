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
?>

<style>
    .wa-container {
        max-width: 600px;
        margin: 40px auto;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        padding: 30px;
        text-align: center;
    }
    .wa-title {
        font-size: 22px;
        font-weight: bold;
        color: #1e293b;
        margin-bottom: 10px;
    }
    .wa-desc {
        color: #64748b;
        font-size: 14px;
        margin-bottom: 30px;
    }
    
    /* Server Status Badge */
    .server-status {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        margin-bottom: 20px;
    }
    .status-offline { background: #fee2e2; color: #ef4444; }
    .status-online { background: #dcfce7; color: #10b981; }
    
    /* Bot State Box */
    .bot-state-box {
        padding: 20px;
        border: 2px dashed #cbd5e1;
        border-radius: 8px;
        min-height: 250px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    
    .qr-container img {
        max-width: 200px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 10px;
        background: white;
    }
    
    .btn-wa {
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        border: none;
        display: inline-block;
        margin-top: 20px;
        transition: 0.2s;
    }
    .btn-start {
        background-color: #2563eb;
        color: white;
        box-shadow: 0 4px 6px rgba(37, 99, 235, 0.2);
    }
    .btn-start:hover { background-color: #1d4ed8; }
    .btn-stop {
        background-color: #ef4444;
        color: white;
    }
    .btn-stop:hover { background-color: #dc2626; }
    
    /* Loader */
    .loader {
        border: 4px solid #f3f3f3;
        border-top: 4px solid #3498db;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        animation: spin 1s linear infinite;
        margin-bottom: 15px;
    }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

</style>

<div>
    <div class="wa-container">
        <h1 class="wa-title">Koneksi WhatsApp Bot</h1>
        <p class="wa-desc">Pantau status bot WhatsApp untuk notifikasi dan absensi secara real-time.</p>
        
        <div id="serverBadge" class="server-status status-offline">Memeriksa Server...</div>
        
        <div class="bot-state-box" id="botStateBox">
            <div class="loader"></div>
            <p>Menghubungkan ke API WA...</p>
        </div>
        
        <div id="actionContainer" style="display: none;">
            <!-- Tombol Start muncul kalau server mati -->
            <button id="btnStartServer" class="btn-wa btn-start" onclick="startWAServer()">🚀 Nyalakan Server WA</button>
        </div>
        
        <div id="connectedActionContainer" style="display: none;">
            <!-- Tombol Logout muncul kalau bot siap/terhubung -->
            <button class="btn-wa btn-stop" onclick="logoutBot()">Keluar / Ganti Akun WA</button>
        </div>
    </div>
</div>

<script>
    const botStateBox = document.getElementById('botStateBox');
    const serverBadge = document.getElementById('serverBadge');
    const actionContainer = document.getElementById('actionContainer');
    const connectedActionContainer = document.getElementById('connectedActionContainer');
    
    let isPolling = false;

    async function checkStatus() {
        try {
            const response = await fetch('http://localhost:3000/api/status');
            const data = await response.json();
            
            // Server Online
            serverBadge.className = 'server-status status-online';
            serverBadge.innerText = 'Server Node.js AKTIF';
            actionContainer.style.display = 'none';
            
            renderBotState(data);
            
        } catch (error) {
            // Server Offline (Connection Refused)
            serverBadge.className = 'server-status status-offline';
            serverBadge.innerText = 'Server Node.js MATI';
            botStateBox.innerHTML = `
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" style="margin-bottom:15px">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <p style="color: #64748b; font-weight: 500;">Sistem tidak dapat terhubung ke WA Bot API.<br>Klik tombol di bawah untuk menyalakan server.</p>
            `;
            actionContainer.style.display = 'block';
            connectedActionContainer.style.display = 'none';
        }
    }

    function renderBotState(data) {
        if (data.status === 'INITIALIZING') {
            botStateBox.innerHTML = `
                <div class="loader"></div>
                <p style="color: #64748b; font-weight: 600;">Sedang membuka WhatsApp Web di latar belakang...</p>
                <small style="color: #94a3b8;">Mohon tunggu sekitar 5-15 detik.</small>
            `;
            connectedActionContainer.style.display = 'none';
        } 
        else if (data.status === 'QR_READY') {
            botStateBox.innerHTML = `
                <h3 style="color: #1e293b; margin-bottom:10px; font-size:16px;">Scan QR Code di bawah ini:</h3>
                <div class="qr-container">
                    <img src="${data.qr}" alt="QR Code WhatsApp">
                </div>
                <p style="margin-top:15px; color:#64748b; font-size:13px;">Buka WhatsApp di HP Anda > Tautkan Perangkat</p>
            `;
            connectedActionContainer.style.display = 'none';
        }
        else if (data.status === 'READY' || data.status === 'AUTHENTICATED') {
            botStateBox.innerHTML = `
                <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" style="margin-bottom:15px">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <h2 style="color: #10b981; margin-bottom: 5px;">Bot Terhubung & Aktif!</h2>
                <p style="color: #64748b;">Sistem siap mengirimkan notifikasi absensi dan izin.</p>
            `;
            connectedActionContainer.style.display = 'block';
        }
        else if (data.status === 'DISCONNECTED') {
            botStateBox.innerHTML = `
                <p style="color: #ef4444; font-weight: bold;">WhatsApp terputus / Logout!</p>
                <p style="color: #64748b;">Mencoba memuat ulang QR Code...</p>
            `;
            connectedActionContainer.style.display = 'none';
        }
    }

    async function startWAServer() {
        const btn = document.getElementById('btnStartServer');
        btn.innerHTML = 'Sedang menyalakan...';
        btn.disabled = true;
        
        try {
            const res = await fetch('start_wa_server.php');
            const data = await res.json();
            
            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: data.message,
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                Swal.fire('Gagal!', data.message, 'error');
            }
        } catch(e) {
            Swal.fire('Error', 'Gagal memanggil script launcher.', 'error');
        }
        
        btn.innerHTML = '🚀 Nyalakan Server WA';
        btn.disabled = false;
    }

    async function logoutBot() {
        Swal.fire({
            title: 'Yakin ingin Logout?',
            text: "Koneksi bot akan terputus dan Anda harus menscan ulang QR Code.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'Ya, Logout!'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    await fetch('http://localhost:3000/api/logout', { method: 'POST' });
                    Swal.fire('Terputus', 'Sistem memutus koneksi WA.', 'success');
                } catch(e) {
                    Swal.fire('Error', 'Gagal memutus koneksi.', 'error');
                }
            }
        });
    }

    // Polling setiap 2 detik
    setInterval(checkStatus, 2000);
    checkStatus(); // Panggil pertama kali

</script>

<?php include 'templates/footer.php'; ?>
