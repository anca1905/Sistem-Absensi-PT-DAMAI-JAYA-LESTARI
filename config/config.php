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

define('BASE_URL', 'https://alecia-decem-matha.ngrok-free.dev/amanda/amanda/');
