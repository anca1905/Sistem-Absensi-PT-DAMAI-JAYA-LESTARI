<?php
// config/wa_helper.php

/**
 * Helper untuk mengirim pesan WhatsApp via Node.js Bot
 */
function sendWA($nomor, $pesan) {
    if (empty($nomor)) return false;

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://localhost:3000/send-message',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 2, // Timeout cepat agar tidak terlalu lama loading (Fire and forget)
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => http_build_query(array(
            'number' => $nomor,
            'message' => $pesan
        )),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/x-www-form-urlencoded'
        ),
    ));
    
    $response = curl_exec($curl);
    curl_close($curl);
    
    return $response;
}
?>
