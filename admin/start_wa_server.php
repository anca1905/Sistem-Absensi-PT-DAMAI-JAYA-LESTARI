<?php
require '../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

$dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'wa-bot';
$cmd = "start /B cmd /c cd \"$dir\" && node server.js > NUL 2>&1";

try {
    pclose(popen($cmd, "r"));
    echo json_encode(['status' => 'success', 'message' => 'Node.js Server sedang dihidupkan di latar belakang...']);
} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => 'Gagal menjalankan server: ' . $e->getMessage()]);
}
