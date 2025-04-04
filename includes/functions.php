<?php
// Bắt đầu session một cách an toàn
function start_session() {
    if (session_status() == PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            ini_set('session.cookie_secure', 1);
        }
        session_start();
    }
}

// Kiểm tra người dùng đã đăng nhập chưa
function is_logged_in() {
    start_session();
    return isset($_SESSION['user_id']);
}

// Yêu cầu đăng nhập nếu chưa
function require_login($redirect_url = 'login.php') {
    if (!is_logged_in()) {
        redirect($redirect_url);
    }
}

// Yêu cầu chưa đăng nhập (dùng cho trang login, register)
function require_guest($redirect_url = 'dashboard.php') {
     if (is_logged_in()) {
        redirect($redirect_url);
    }
}

// Lấy thông tin người dùng hiện tại
function get_current_user_id() {
    start_session();
    return $_SESSION['user_id'] ?? null;
}
function get_current_username() {
    start_session();
    return $_SESSION['username'] ?? null;
}


// Hàm chuyển hướng
function redirect($url) {
    if (defined('BASE_URL') && !filter_var($url, FILTER_VALIDATE_URL)) {
        $final_url = rtrim(BASE_URL, '/') . '/' . ltrim($url, '/');
    } else {
        $final_url = $url;
    }
    // Đảm bảo không có output nào trước header
    if (!headers_sent()) {
        header("Location: " . $final_url);
    } else {
        // Fallback nếu header đã gửi (ít xảy ra nếu code cẩn thận)
        echo '<script type="text/javascript">window.location.href="' . $final_url . '";</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=' . $final_url . '" /></noscript>';
    }
    exit();
}

// Hàm hiển thị thông báo lỗi/thành công (Flash messages)
function set_flash_message($type, $message) {
    start_session();
    $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
}

function display_flash_message() {
    start_session();
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        $alert_class = ($flash['type'] === 'error') ? 'alert-danger' : 'alert-success';
        echo '<div class="alert ' . $alert_class . ' alert-dismissible fade show" role="alert">'
           . htmlspecialchars($flash['message'])
           . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
           .'</div>';
        unset($_SESSION['flash_message']);
    }
}

// Hàm format tiền tệ Việt Nam
function format_currency($amount) {
    if (!is_numeric($amount)) {
        return 'N/A';
    }
    return number_format($amount, 0, ',', '.') . ' VND';
}

// Hàm tạo số tài khoản ngẫu nhiên (đơn giản) - sử dụng mysqli
function generate_account_number($db_connection) {
    global $conn; // Sử dụng biến kết nối toàn cục nếu $db_connection không được truyền
    if (!$db_connection) $db_connection = $conn;

    $max_attempts = 10;
    for ($i = 0; $i < $max_attempts; $i++) {
        $number = '';
        for ($j = 0; $j < 9; $j++) {
            $number .= random_int(0, 9);
        }

        $sql = "SELECT id FROM accounts WHERE account_number = ?";
        $stmt = mysqli_prepare($db_connection, $sql);
        if ($stmt === false) {
            // Ghi log lỗi thay vì throw trực tiếp ra ngoài có thể tốt hơn
            error_log("Lỗi chuẩn bị kiểm tra STK: " . mysqli_error($db_connection));
            continue; // Thử lại
        }

        mysqli_stmt_bind_param($stmt, 's', $number);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) === 0) {
            mysqli_stmt_close($stmt);
            return $number;
        }
        mysqli_stmt_close($stmt);
    }
    // Nếu không thành công sau nhiều lần thử
    throw new Exception("Không thể tạo số tài khoản duy nhất.");
}
?>