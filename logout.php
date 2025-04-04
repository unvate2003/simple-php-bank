<?php
require_once __DIR__ . '/includes/functions.php';

start_session();

// Xóa tất cả biến session
$_SESSION = [];

// Nếu dùng cookie cho session, xóa cả cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hủy session
session_destroy();

redirect('login.php');
?>