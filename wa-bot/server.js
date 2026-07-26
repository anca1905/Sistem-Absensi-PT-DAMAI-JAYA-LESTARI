const express = require('express');
const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');
const cors = require('cors');

const app = express();
const port = 3000;

app.use(express.json());
app.use(express.urlencoded({ extended: true }));
app.use(cors());

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

client.on('qr', (qr) => {
    // Generate QR di terminal agar bisa di-scan
    console.log('SCAN QR CODE INI MENGGUNAKAN WHATSAPP ANDA:');
    qrcode.generate(qr, { small: true });
});

client.on('ready', () => {
    console.log('WhatsApp Bot is READY!');
});

client.on('authenticated', () => {
    console.log('WhatsApp Bot Authenticated!');
});

client.on('auth_failure', msg => {
    console.error('Authentication failure:', msg);
});

client.initialize();

// Endpoint untuk menerima request pengiriman WA dari PHP
app.post('/send-message', async (req, res) => {
    const { number, message } = req.body;

    if (!number || !message) {
        return res.status(400).json({ status: 'error', message: 'Number and message are required' });
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
