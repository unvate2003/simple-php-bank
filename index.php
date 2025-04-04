<?php
// File này đơn giản chỉ chuyển hướng đến trang login hoặc dashboard
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect('dashboard.php');
} else {
    redirect('login.php');
}
?>