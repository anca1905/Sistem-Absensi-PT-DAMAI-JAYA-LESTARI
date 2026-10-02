<?php
require '../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

header('Content-Type: application/json');
echo json_encode([
    'status' => 'info',
    'message' => 'Layanan WhatsApp kini menggunakan Fonnte Cloud API. Server Node.js lokal sudah tidak diperlukan lagi.'
]);
