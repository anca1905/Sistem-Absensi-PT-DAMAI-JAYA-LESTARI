<?php
require "config/config.php";

$out = "";
$res = mysqli_query($conn, "SHOW CREATE TABLE logbook_kinerja");
if ($res) {
    $row = mysqli_fetch_array($res);
    $out .= $row[1] . "\n\n";
}

$res2 = mysqli_query($conn, "SHOW CREATE TABLE rencana_kerja_pengawas");
if ($res2) {
    $row2 = mysqli_fetch_array($res2);
    $out .= $row2[1] . "\n\n";
}

$res3 = mysqli_query($conn, "SHOW CREATE TABLE perizinan");
if ($res3) {
    $row3 = mysqli_fetch_array($res3);
    $out .= $row3[1] . "\n\n";
}

$res4 = mysqli_query($conn, "SHOW CREATE TABLE absensis");
if ($res4) {
    $row4 = mysqli_fetch_array($res4);
    $out .= $row4[1] . "\n\n";
}

file_put_contents('schema_out.txt', $out);
echo "done";
