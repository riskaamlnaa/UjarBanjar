<?php
session_start();

// Hapus semua session admin
unset($_SESSION['admin_logged_in']);
unset($_SESSION['admin_username']);
unset($_SESSION['login_time']);

// Hancurkan session
session_destroy();

// Redirect ke halaman login
header('Location: login.php');
exit;
?