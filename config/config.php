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


// Load Functions
require_once 'functions.php';
require_once 'wa_helper.php';
require_once 'alert_helper.php';

define('BASE_URL', 'http://localhost/amanda/amanda/');
