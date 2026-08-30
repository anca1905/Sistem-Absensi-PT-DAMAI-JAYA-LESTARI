<?php
require 'config/config.php';
$check = mysqli_query($conn, "SHOW COLUMNS FROM logbook_kinerja LIKE 'rencana_id'");
if (mysqli_num_rows($check) == 0) {
    mysqli_query($conn, "ALTER TABLE logbook_kinerja ADD COLUMN rencana_id INT NULL DEFAULT NULL");
    echo "Added rencana_id";
} else {
    echo "Column exists";
}
