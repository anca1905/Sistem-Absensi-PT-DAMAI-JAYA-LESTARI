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
$logFile = $dir . DIRECTORY_SEPARATOR . 'server.log';
// Menggunakan WScript.Shell agar berjalan di background tanpa mengganggu PHP, dan log error disimpan ke server.log
try {
    $WshShell = new COM("WScript.Shell");
    $cmd = "cmd /c cd /d \"$dir\" && node server.js > \"$logFile\" 2>&1";
    $WshShell->Run($cmd, 0, false);
    echo json_encode(['status' => 'success', 'message' => 'Node.js Server sedang dihidupkan di latar belakang...']);
} catch (Throwable $e) {
    // Fallback if COM is disabled
    $cmd = "start /B cmd /c cd /d \"$dir\" && node server.js > \"$logFile\" 2>&1";
    pclose(popen($cmd, "r"));
    echo json_encode(['status' => 'success', 'message' => 'Node.js Server sedang dihidupkan di latar belakang (fallback)...']);
}
