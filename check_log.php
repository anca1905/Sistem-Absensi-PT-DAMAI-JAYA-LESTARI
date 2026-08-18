<?php
require 'c:/laragon/www/amanda/amanda/config/config.php';
$res = mysqli_query($conn, 'SHOW COLUMNS FROM logbook_kinerja');
while($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . " ";
}
echo "\n";
?>
