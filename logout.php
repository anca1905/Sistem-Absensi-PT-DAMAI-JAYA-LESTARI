<?php
// 1. Mulai session dulu (biar sistem tahu session mana yang mau dimatikan)
session_start();

// 2. Kosongkan semua variabel session array
$_SESSION = [];

// 3. Hapus session dari memori
session_unset();

// 4. Hancurkan session sepenuhnya
session_destroy();

// 5. (Opsional - Best Practice) Hapus juga cookie session di browser biar bersih total
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 6. Arahkan kembali ke Halaman Login (index.php)
header("Location: index.php");
exit;
