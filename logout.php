<?php
/**
 * Fail: logout.php
 * Menghapus sesi pengguna dan mengarahkan kembali ke halaman login.
 */

// Memulai sesi untuk mengakses data yang akan dihapus
session_start();

// Menghapus semua variabel sesi
$_SESSION = array();

// Jika ingin menghapus cookie sesi juga (opsional tapi disarankan)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Menghancurkan sesi secara total
session_destroy();

// Mengarahkan pengguna kembali ke halaman login
header("Location: login.php?msg=Anda telah berhasil keluar");
exit();
?>