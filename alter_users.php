<?php
require 'c:/laragon/www/amanda/amanda/config/config.php';
mysqli_query($conn, "ALTER TABLE users ADD COLUMN no_hp VARCHAR(20) NULL");
echo "Done";
?>
