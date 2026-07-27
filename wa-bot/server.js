const express = require('express');
const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode');
const cors = require('cors');

const app = express();
const port = 3000;

app.use(express.json());
app.use(express.urlencoded({ extended: true }));
app.use(cors());

// Variables for state
let botStatus = 'INITIALIZING'; 
let currentQR = '';

// Inisialisasi WhatsApp Client
const client = new Client({
    authStrategy: new LocalAuth(),
    puppeteer: {
        headless: true,
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox'
        ]
    }
});

client.on('qr', async (qr) => {
    botStatus = 'QR_READY';
    try {
        currentQR = await qrcode.toDataURL(qr);
        console.log('QR Code generated for Web UI.');
    } catch (err) {
        console.error('Error generating QR DataURL', err);
    }
});

client.on('ready', () => {
    botStatus = 'READY';
    currentQR = '';
    console.log('WhatsApp Bot is READY!');
});

client.on('authenticated', () => {
    botStatus = 'AUTHENTICATED';
    console.log('WhatsApp Bot Authenticated!');
});

client.on('auth_failure', msg => {
    botStatus = 'AUTH_FAILURE';
    console.error('Authentication failure:', msg);
});

client.on('disconnected', (reason) => {
    botStatus = 'DISCONNECTED';
    currentQR = '';
    console.log('Client was logged out or disconnected', reason);
    // Kita inisialisasi ulang agar QR baru bisa digenerate jika putus
    botStatus = 'INITIALIZING';
    client.initialize(); 
});

client.initialize();

// --- ENDPOINTS BARU ---

// Endpoint untuk cek status
app.get('/api/status', (req, res) => {
    res.json({
        status: botStatus,
        qr: currentQR
    });
});

// Endpoint untuk logout / ganti akun
app.post('/api/logout', async (req, res) => {
    try {
        await client.logout();
        res.json({ status: 'success', message: 'Logged out successfully' });
    } catch (e) {
        res.status(500).json({ status: 'error', message: e.toString() });
    }
});

// --- ENDPOINT LAMA ---

// Endpoint untuk menerima request pengiriman WA dari PHP
app.post('/send-message', async (req, res) => {
    const { number, message } = req.body;

    if (!number || !message) {
        return res.status(400).json({ status: 'error', message: 'Number and message are required' });
    }

    if (botStatus !== 'READY') {
        return res.status(503).json({ status: 'error', message: 'WhatsApp Bot belum siap' });
    }

    // Format nomor (ubah awalan 0 menjadi 62, hilangkan spasi/strip)
    let formattedNumber = number.replace(/\D/g, '');
    if (formattedNumber.startsWith('0')) {
        formattedNumber = '62' + formattedNumber.substring(1);
    }
    const chatId = formattedNumber + "@c.us";

    try {
        await client.sendMessage(chatId, message);
        res.json({ status: 'success', message: 'Pesan berhasil dikirim' });
    } catch (err) {
        console.error('Error sending message:', err);
        res.status(500).json({ status: 'error', message: err.toString() });
    }
});

app.listen(port, () => {
    console.log(`WA Bot API berjalan di http://localhost:${port}`);
    console.log('Harap tunggu inisialisasi WhatsApp (Chrome Headless)...');
});
