<?php
require 'c:/laragon/www/amanda/amanda/config/config.php';
mysqli_query($conn, 'ALTER TABLE users ADD COLUMN afdeling VARCHAR(50) DEFAULT NULL');
echo mysqli_error($conn);
?>
