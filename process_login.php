<?php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/includes/functions.php';

global $conn; // Sử dụng biến kết nối

start_session();
require_guest();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        set_flash_message('error', 'Vui lòng nhập tên đăng nhập và mật khẩu.');
        redirect('login.php');
    }

    $sql = "SELECT id, username, password_hash FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt === false) {
        error_log("MySQLi prepare error in login: " . mysqli_error($conn));
        set_flash_message('error', 'Lỗi hệ thống (DBP). Vui lòng thử lại sau.');
        redirect('login.php');
    }

    mysqli_stmt_bind_param($stmt, 's', $username);
    if (!mysqli_stmt_execute($stmt)) {
         error_log("MySQLi execute error in login: " . mysqli_stmt_error($stmt));
         mysqli_stmt_close($stmt);
         set_flash_message('error', 'Lỗi hệ thống (DBE). Vui lòng thử lại sau.');
         redirect('login.php');
    }

    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        redirect('dashboard.php');
    } else {
        set_flash_message('error', 'Tên đăng nhập hoặc mật khẩu không đúng.');
        redirect('login.php');
    }

} else {
    redirect('login.php');
}
?>