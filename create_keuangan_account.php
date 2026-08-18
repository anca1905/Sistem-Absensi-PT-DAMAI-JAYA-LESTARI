<?php
require 'c:/laragon/www/amanda/amanda/config/config.php';

$nik = 'K-001';
$name = 'Admin Keuangan';
$email = 'keuangan@gmail.com';
$password = 'password';
$role = 'keuangan';
$afdeling = '';

// Check if email already exists
$check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
if (mysqli_num_rows($check) > 0) {
    echo "Akun keuangan sudah ada.\n";
} else {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $query = "INSERT INTO users (nik, name, email, password, role, afdeling) 
              VALUES ('$nik', '$name', '$email', '$hashed_password', '$role', '$afdeling')";
              
    if (mysqli_query($conn, $query)) {
        echo "Akun keuangan berhasil ditambahkan.\n";
        echo "Email: $email\n";
        echo "Password: $password\n";
    } else {
        echo "Error: " . mysqli_error($conn) . "\n";
    }
}
?>
