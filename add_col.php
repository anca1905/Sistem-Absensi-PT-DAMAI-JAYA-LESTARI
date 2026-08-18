<?php
require 'c:/laragon/www/amanda/amanda/config/config.php';
$query = "ALTER TABLE users ADD COLUMN jenis_kelamin ENUM('Laki-laki', 'Perempuan') NULL DEFAULT NULL";
if(mysqli_query($conn, $query)) {
    echo "Column jenis_kelamin added successfully.\n";
} else {
    echo "Error adding column: " . mysqli_error($conn) . "\n";
}
?>
