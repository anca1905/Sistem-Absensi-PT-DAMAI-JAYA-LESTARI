<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "amanda";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

session_start();
date_default_timezone_set('Asia/Makassar'); // Set ke WITA (+08:00) sesuai zona waktu user

// Load Functions
require_once 'functions.php';
require_once 'wa_helper.php';
require_once 'alert_helper.php';

// URL dasar aplikasi (ganti sesuai environment)
// Production: https://portal-djl.skillance.cloud/
// Development: http://localhost/amanda/amanda/
define('BASE_URL', 'https://portal-djl.skillance.cloud/');

// Secret key untuk generate token WA (jangan ganti sembarangan!)
define('APP_SECRET', 'DJL_AMANDA_SECRET_2025');
