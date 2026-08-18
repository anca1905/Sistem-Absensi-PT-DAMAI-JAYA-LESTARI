<?php
require 'c:/laragon/www/amanda/amanda/config/config.php';

// Check the column type
$result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'role'");
$row = mysqli_fetch_assoc($result);

if (strpos($row['Type'], 'keuangan') === false) {
    // We need to add 'keuangan' to the enum
    $new_type = str_replace(")", ",'keuangan')", $row['Type']);
    $alter_query = "ALTER TABLE users MODIFY COLUMN role $new_type";
    if (mysqli_query($conn, $alter_query)) {
        echo "Successfully altered table to add keuangan to role enum.\n";
    } else {
        echo "Error altering table: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "Role 'keuangan' is already in the enum.\n";
}
?>
