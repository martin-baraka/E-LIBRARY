<?php
session_start();
$_SESSION = array();
session_destroy();
// Clear remember me cookie
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
    // Clear token from DB
    require_once 'db.php';
    // token was stored — clear it if user is known
}
header("Location: index.php");
exit();
?>
