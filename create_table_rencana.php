<?php
require 'c:/laragon/www/amanda/amanda/config/config.php';

$query = "CREATE TABLE IF NOT EXISTS rencana_kerja_pengawas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    pengawas_id INT NOT NULL,
    mandor_id INT NOT NULL,
    objek_kerja VARCHAR(100) NOT NULL,
    tenaga_l INT DEFAULT 0,
    tenaga_w INT DEFAULT 0,
    blok VARCHAR(50),
    luas_ha VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $query)) {
    echo "Table created successfully\n";
} else {
    echo "Error creating table: " . mysqli_error($conn) . "\n";
}
?>
