<?php
// config/wa_helper.php

/**
 * Mengambil Fonnte API Token dari tabel settings atau fallback konstanta FONNTE_TOKEN
 */
function getFonnteToken() {
    global $conn;
    if ($conn) {
        $q = mysqli_query($conn, "SELECT fonnte_token FROM settings LIMIT 1");
        if ($q && $row = mysqli_fetch_assoc($q)) {
            if (!empty($row['fonnte_token'])) {
                return trim($row['fonnte_token']);
            }
        }
    }
    if (defined('FONNTE_TOKEN') && !empty(FONNTE_TOKEN)) {
        return trim(FONNTE_TOKEN);
    }
    return '';
}

/**
 * Menyimpan Fonnte API Token ke tabel settings
 */
function saveFonnteToken($token) {
    global $conn;
    if (!$conn) return false;
    $safe_token = mysqli_real_escape_string($conn, trim($token));
    $cek = mysqli_query($conn, "SELECT id FROM settings LIMIT 1");
    if (mysqli_num_rows($cek) > 0) {
        return mysqli_query($conn, "UPDATE settings SET fonnte_token = '$safe_token'");
    } else {
        return mysqli_query($conn, "INSERT INTO settings (fonnte_token) VALUES ('$safe_token')");
    }
}

/**
 * Helper untuk melakukan HTTP POST ke Fonnte API
 */
function callFonnteApi($endpoint, $postFields = [], $token = null) {
    if ($token === null) {
        $token = getFonnteToken();
    }
    $token = trim($token);

    if (empty($token)) {
        return json_encode([
            'status' => false,
            'reason' => 'Token Fonnte belum dikonfigurasi'
        ]);
    }

    $url = 'https://api.fonnte.com/' . ltrim($endpoint, '/');

    $curl = curl_init();
    $curlOptions = array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_HTTPHEADER => array(
            'Authorization: ' . $token
        ),
    );

    if (!empty($postFields)) {
        $curlOptions[CURLOPT_POSTFIELDS] = $postFields;
    }

    curl_setopt_array($curl, $curlOptions);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        return json_encode([
            'status' => false,
            'reason' => 'cURL Error: ' . $err
        ]);
    }

    return $response;
}

/**
 * Helper utama pengiriman pesan WhatsApp via Fonnte API
 */
function sendWA($nomor, $pesan) {
    if (empty($nomor) || empty($pesan)) return false;

    // Bersihkan nomor (hanya digit)
    $clean_nomor = preg_replace('/[^0-9]/', '', $nomor);
    if (substr($clean_nomor, 0, 1) === '0') {
        $clean_nomor = '62' . substr($clean_nomor, 1);
    }

    return callFonnteApi('send', array(
        'target' => $clean_nomor,
        'message' => $pesan,
        'countryCode' => '62'
    ));
}

/**
 * Cek status perangkat Fonnte
 */
function checkFonnteStatus($token = null) {
    return callFonnteApi('device', [], $token);
}

/**
 * Ambil QR Code Fonnte
 */
function getFonnteQR($token = null) {
    return callFonnteApi('qr', [], $token);
}

/**
 * Putuskan koneksi perangkat Fonnte
 */
function disconnectFonnte($token = null) {
    return callFonnteApi('disconnect', [], $token);
}
?>
