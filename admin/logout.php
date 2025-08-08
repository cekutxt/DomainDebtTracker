<?php
session_start();

// Oturumu temizle
$_SESSION = array();

// Session cookie'sini sil
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Oturumu sonlandır
session_destroy();

// Login sayfasına yönlendir
header('Location: login.php');
exit;
?>