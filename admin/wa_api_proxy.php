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

$action = $_GET['action'] ?? 'status';
$curl = curl_init();

if ($action === 'logout') {
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://localhost:3000/api/logout',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_CUSTOMREQUEST => 'POST',
    ));
} else {
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://localhost:3000/api/status',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
    ));
}

$response = curl_exec($curl);
$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$err = curl_error($curl);
curl_close($curl);

if ($response === false) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal terhubung ke WA Bot API: ' . $err]);
} else {
    http_response_code($httpcode ?: 200);
    echo $response;
}
?>
