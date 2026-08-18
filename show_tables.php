<?php
require 'c:/laragon/www/amanda/amanda/config/config.php';
$res = mysqli_query($conn, 'SHOW TABLES');
while($row = mysqli_fetch_row($res)) {
    echo $row[0]."\n";
}
?>
